<?php

namespace Apollo\Telegram\Services;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Apollo Telegram Service
 * 
 * Powerful, independent Telegram Bot service for Apollo Rio.
 * 
 * Features:
 * - User linking via secure one-time keys (for registration flow)
 * - Open chats for all linked users
 * - High-quality broadcast system for reminders and admin notifications (send to ALL in one run)
 * - Rich messaging: HTML, inline keyboards, rate limiting, retries, logging
 * - Fully isolated - no hooks into apollo-core/login/registry yet
 * - Test mode ready with detailed logging and simulation
 * 
 * Philosophy: Every user who links gets an "open chat". Admin can notify everyone easily.
 * Quality: Enterprise-grade error handling, security, scalability for future.
 * 
 * @package Apollo\Telegram
 */
class TelegramService
{
    private string $botToken;
    private string $botUsername;
    private bool $testMode;
    private string $logFile;

    public function __construct()
    {
        $options = get_option('apollo_telegram_options', []);

        $this->botToken = apollo_telegram_bot_token();
        $this->botUsername = apollo_telegram_bot_username() ?: 'apolloRio_bot';
        $this->testMode = $options['test_mode'] ?? true;

        $this->logFile = APOLLO_TELEGRAM_DIR . 'logs/telegram-' . date('Y-m-d') . '.log';

        // Ensure logs dir
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    /**
     * Log with levels - rich details for debugging pro code.
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        $line = "[{$timestamp}] [{$level}] {$message}{$contextStr}\n";

        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);

        if ($this->testMode && in_array($level, ['ERROR', 'WARNING'])) {
            error_log("[ApolloTelegram] {$message}");
        }
    }

    /**
     * Core sendMessage with retries, rate limit handling, rich features.
     * Supports HTML, Markdown, buttons (inline_keyboard).
     */
    public function sendMessage(int|string $chatId, string $text, array $options = []): bool|array
    {
        if (empty($this->botToken)) {
            $this->log('ERROR', 'No bot token configured');
            return false;
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
            'disable_notification' => false,
        ], $options);

        // Rich: support inline buttons for pro UX
        if (!empty($options['reply_markup'])) {
            $payload['reply_markup'] = json_encode($options['reply_markup']);
        }

        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $response = wp_remote_post($url, [
                'body' => $payload,
                'timeout' => 20,
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            ]);

            if (is_wp_error($response)) {
                $this->log('ERROR', 'WP Remote error', ['attempt' => $attempt, 'error' => $response->get_error_message()]);
                sleep(1);
                continue;
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if ($code === 200 && ($body['ok'] ?? false)) {
                $this->log('INFO', 'Message sent successfully', ['chat_id' => $chatId, 'message_id' => $body['result']['message_id'] ?? null]);
                return $body['result'] ?? true;
            }

            // Handle rate limit (429)
            if ($code === 429) {
                $retryAfter = $body['parameters']['retry_after'] ?? 1;
                $this->log('WARNING', 'Rate limited', ['retry_after' => $retryAfter]);
                sleep($retryAfter + 1);
                continue;
            }

            $this->log('ERROR', 'Telegram API error', [
                'attempt' => $attempt,
                'code' => $code,
                'body' => $body,
                'chat_id' => $chatId,
            ]);

            if ($code >= 400 && $code < 500) {
                // Don't retry client errors
                break;
            }

            sleep(1 + $attempt);
        }

        return false;
    }

    /**
     * Generate secure one-time key for user linking (registering flow).
     * Phone optional - can be used to match later with apollo users.
     * Returns deep_link using web.telegram.org/k/ (works on desktop + redirects to app on mobile).
     */
    public function generateLinkKey(?string $phone = null, array $extra = []): array
    {
        $key = wp_generate_uuid4();

        $data = array_merge([
            'phone' => $phone,
            'created' => time(),
            'used' => false,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown', // for audit
        ], $extra);

        $transient = 'apollo_telegram_link_key_' . $key;
        set_transient($transient, $data, 15 * MINUTE_IN_SECONDS);

        $botUsername = $this->botUsername;
        if (strpos($botUsername, '@') === 0) $botUsername = substr($botUsername, 1);

        // Use web.telegram.org as main deep link (works on desktop, redirects to app on mobile)
        // Using ?text=KEY as per required method to prefill the key in chat
        $deepLink = "https://web.telegram.org/k/#@{$botUsername}?text=" . urlencode($key);

        $this->log('INFO', 'Link key generated', ['key' => $key, 'phone' => $phone]);

        return [
            'success' => true,
            'key' => $key,
            'deep_link' => $deepLink,
            'expires_in' => 15 * 60,
        ];
    }

    /**
     * Validate and consume a key from /start.
     * Returns chat data or false.
     */
    public function validateAndLinkChat(string $key, int $chatId, string $username = ''): bool|array
    {
        $transient = 'apollo_telegram_link_key_' . $key;
        $data = get_transient($transient);

        if (!$data || ($data['used'] ?? false)) {
            $this->log('WARNING', 'Invalid or used key attempt', ['key' => $key, 'chat_id' => $chatId]);
            return false;
        }

        // Mark used
        $data['used'] = true;
        $data['chat_id'] = $chatId;
        $data['telegram_username'] = $username;
        set_transient($transient, $data, 15 * MINUTE_IN_SECONDS);

        // Save to linked users - this is the "open chat"
        $linked = get_option('apollo_telegram_linked_chats', []);
        $linked[$chatId] = [
            'key' => $key,
            'phone' => $data['phone'] ?? null,
            'linked_at' => time(),
            'telegram_username' => $username,
            'extra' => $data['extra'] ?? [],
        ];
        update_option('apollo_telegram_linked_chats', $linked);

        $this->log('INFO', 'User linked successfully', ['chat_id' => $chatId, 'phone' => $data['phone']]);

        return $data;
    }

    /**
     * POWERFUL BROADCAST: Send to all (or filtered) linked users in one run.
     * Rich details: supports per-user personalization, buttons, logging, stats.
     * Perfect for Apollo admin notifications + reminders.
     */
    public function broadcastToAll(string $message, array $options = []): array
    {
        $linked = get_option('apollo_telegram_linked_chats', []);

        // Default recipients: every chat that ever opened a message to the bot.
        $chatIds = $options['chat_ids'] ?? array_column($this->getAllKnownChats(), 'chat_id');

        if (empty($chatIds)) {
            return ['success' => false, 'sent' => 0, 'failed' => 0, 'message' => 'No linked users yet.'];
        }

        $parseMode = $options['parse_mode'] ?? 'HTML';
        $replyMarkup = $options['reply_markup'] ?? null; // for buttons

        $sent = 0;
        $failed = 0;
        $errors = [];
        $results = [];

        $this->log('INFO', 'Broadcast started', ['total' => count($chatIds), 'message_preview' => substr($message, 0, 100)]);

        foreach ($chatIds as $chatId) {
            $userData = $linked[$chatId] ?? [];

            // Personalization example (rich feature)
            $personalMessage = str_replace(
                ['{phone}', '{username}', '{chat_id}'],
                [$userData['phone'] ?? 'N/A', $userData['telegram_username'] ?? '', $chatId],
                $message
            );

            $sendOptions = ['parse_mode' => $parseMode];
            if ($replyMarkup) {
                $sendOptions['reply_markup'] = $replyMarkup;
            }

            $result = $this->sendMessage($chatId, $personalMessage, $sendOptions);

            if ($result) {
                $sent++;
                $results[$chatId] = 'sent';
            } else {
                $failed++;
                $errors[] = "Failed for {$chatId}";
                $results[$chatId] = 'failed';
            }

            // Pro rate limiting
            usleep(250000); // 250ms
        }

        $summary = [
            'success' => $failed === 0,
            'sent' => $sent,
            'failed' => $failed,
            'total' => count($chatIds),
            'errors' => $errors,
            'results' => $results,
            'timestamp' => time(),
        ];

        $this->log('INFO', 'Broadcast completed', $summary);

        return $summary;
    }

    /**
     * Get all linked users (for admin dashboard).
     */
    public function getAllLinkedUsers(): array
    {
        return get_option('apollo_telegram_linked_chats', []);
    }

    /**
     * Every chat that ever opened a message to the bot — union of:
     * 1. linked_chats option (key link + phone verification)
     * 2. verification table rows carrying a telegram_chat_id
     * 3. longman bot DB chat table (private chats that messaged the bot)
     *
     * @return array<int, array{chat_id:int, phone:?string, username:?string, source:string}>
     */
    public function getAllKnownChats(): array
    {
        global $wpdb;

        $known = [];

        foreach ((array) get_option('apollo_telegram_linked_chats', []) as $chatId => $data) {
            $chatId = (int) $chatId;
            if ($chatId <= 0) {
                continue;
            }
            $known[$chatId] = [
                'chat_id'  => $chatId,
                'phone'    => $data['phone'] ?? null,
                'username' => $data['telegram_username'] ?? null,
                'source'   => $data['source'] ?? 'linked',
            ];
        }

        $verifTable = $wpdb->prefix . 'apollo_telegram_verif';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $verifTable)) === $verifTable) {
            $rows = $wpdb->get_results(
                "SELECT DISTINCT telegram_chat_id, phone FROM {$verifTable} WHERE telegram_chat_id IS NOT NULL AND telegram_chat_id > 0"
            );
            foreach ((array) $rows as $row) {
                $chatId = (int) $row->telegram_chat_id;
                if (! isset($known[$chatId])) {
                    $known[$chatId] = [
                        'chat_id'  => $chatId,
                        'phone'    => $row->phone,
                        'username' => null,
                        'source'   => 'verification',
                    ];
                }
            }
        }

        $chatTable = $wpdb->prefix . 'APOLLO_TELEGRAM_chat';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $chatTable)) === $chatTable) {
            $rows = $wpdb->get_results(
                "SELECT id, username FROM `{$chatTable}` WHERE type = 'private'"
            );
            foreach ((array) $rows as $row) {
                $chatId = (int) $row->id;
                if ($chatId > 0 && ! isset($known[$chatId])) {
                    $known[$chatId] = [
                        'chat_id'  => $chatId,
                        'phone'    => null,
                        'username' => $row->username,
                        'source'   => 'bot_chat',
                    ];
                }
            }
        }

        return array_values($known);
    }

    /**
     * Send a rich reminder to a specific user (or all).
     * Example usage for the reminder system.
     */
    public function sendReminder($chatIdOrAll, string $title, string $details, ?string $actionUrl = null): bool|array
    {
        $message = "🔔 <b>{$title}</b>\n\n{$details}";

        if ($actionUrl) {
            $message .= "\n\n<a href=\"{$actionUrl}\">View Details →</a>";
        }

        if ($chatIdOrAll === 'all' || $chatIdOrAll === null) {
            return $this->broadcastToAll($message);
        }

        return $this->sendMessage($chatIdOrAll, $message);
    }
}
