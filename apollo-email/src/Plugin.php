<?php

/**
 * Main Plugin singleton — orchestrates all Apollo Email subsystems.
 *
 * @package Apollo\Email
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email;

use Apollo\Email\Core\CPT;
use Apollo\Email\Core\Schema;
use Apollo\Email\Core\Cron;
use Apollo\Email\Mailer\Sender;
use Apollo\Email\Mailer\Queue;
use Apollo\Email\Template\TemplateEngine;
use Apollo\Email\Log\Logger;
use Apollo\Email\API\EmailController;
use Apollo\Email\Admin\AdminPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure Newsletter class is loaded
require_once __DIR__ . '/Newsletter.php';

final class Plugin
{


    /** @var self|null */
    private static ?self $instance = null;

    /** @var bool */
    private bool $initialized = false;

    // ── Service containers ────────────────────────────────────────
    private Sender $sender;
    private Queue $queue;
    private TemplateEngine $templates;
    private Logger $logger;
    private CPT $cpt;
    private Cron $cron;

    private function __construct() {}
    private function __clone() {}
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Get singleton instance.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    /**
     * Initialize all plugin subsystems.
     */
    private function init(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        // ── Core services ────────────────────────────────────────
        $this->logger    = new Logger();
        $this->templates = new TemplateEngine();
        $this->sender    = new Sender($this->templates, $this->logger);
        $this->queue     = new Queue($this->sender, $this->logger);
        $this->cpt       = new CPT();
        $this->cron      = new Cron($this->queue);

        // ── Register CPT ─────────────────────────────────────────
        add_action('init', array($this->cpt, 'register'), 5);
        add_action('init', array(Activation::class, 'repairBrokenAuthTemplatesIfNeeded'), 20);
        add_action('init', array(Activation::class, 'refreshTransactionalTemplatesV2'), 21);
        add_action('init', array(Activation::class, 'migrateBrandLogoToPngIfNeeded'), 22);

        // ── Cron hooks ───────────────────────────────────────────
        add_action(APOLLO_EMAIL_CRON_HOOK, array($this->queue, 'processNext'));

        // Ensure cron is always scheduled (resilience against dropped events)
        add_action('admin_init', array($this->cron, 'ensureScheduled'));

        // ── Admin ────────────────────────────────────────────────
        if (is_admin()) {
            $admin = new AdminPage($this);
            $admin->boot();
        }

        // ── REST API ─────────────────────────────────────────────
        add_action(
            'rest_api_init',
            function () {
                $controller = new EmailController($this);
                $controller->register_routes();
            }
        );

        // ── Shortcodes ───────────────────────────────────────────
        add_shortcode('apollo_email_prefs', array($this, 'renderEmailPrefsShortcode'));

        // ── Assets ───────────────────────────────────────────────
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminAssets'));

        // ── Cross-plugin hooks ───────────────────────────────────
        $this->registerHookIntegrations();

        // ── SMTP Transport: configure PHPMailer when settings exist ──
        add_action('phpmailer_init', array($this, 'configureSmtpTransport'));
        add_filter('wp_mail_from', static fn(): string => self::fromEmail());
        add_filter('wp_mail_from_name', static fn(): string => self::fromName());
        add_filter('apollo/email/headers', array($this, 'applyDeliverabilityHeaders'), 5, 2);
        add_action('wp_mail_failed', array($this, 'onWpMailFailed'), 10, 1);

        // ── Unsubscribe endpoint ──────────────────────────────────
        $this->registerUnsubscribeHandler();

        // ── Newsletter (migrated from apollo-shortcodes) ────────
        Native_Newsletter::init();

        /**
         * Fires after all Apollo Email services are initialized.
         *
         * @since 1.0.0
         */
        do_action('apollo/email/init', $this);
    }

    // ──────────────────────────────────────────────────────────────
    // SERVICE ACCESSORS
    // ──────────────────────────────────────────────────────────────

    public function sender(): Sender
    {
        return $this->sender;
    }
    public function queue(): Queue
    {
        return $this->queue;
    }
    public function templates(): TemplateEngine
    {
        return $this->templates;
    }
    public function logger(): Logger
    {
        return $this->logger;
    }
    public function cpt(): CPT
    {
        return $this->cpt;
    }
    public function cron(): Cron
    {
        return $this->cron;
    }

	// ──────────────────────────────────────────────────────────────
	// SETTINGS HELPERS
	// ──────────────────────────────────────────────────────────────

    /**
     * Get a plugin setting with optional default.
     *
     * Falls back to apollo-admin CPanel settings (apollo_admin_settings)
     * when a key is not set in apollo_email_settings.
     */
    public static function setting(string $key, mixed $default = null): mixed
    {
        // Highest priority: wp-config constants for SMTP + identity runtime.
        $config_constants = array(
            'smtp_host'       => 'APOLLO_SMTP_HOST',
            'smtp_tls_peer'   => 'APOLLO_SMTP_TLS_PEER',
            'smtp_username'   => 'APOLLO_SMTP_USER',
            'smtp_password'   => 'APOLLO_SMTP_PASS',
            'smtp_port'       => 'APOLLO_SMTP_PORT',
            'smtp_encryption' => 'APOLLO_SMTP_ENCRYPTION',
            'from_email'      => 'APOLLO_EMAIL_FROM',
            'from_name'       => 'APOLLO_EMAIL_FROM_NAME',
            'transport'       => 'APOLLO_EMAIL_TRANSPORT',
        );
        if (isset($config_constants[$key]) && defined($config_constants[$key])) {
            return constant($config_constants[$key]);
        }

        $settings = get_option('apollo_email_settings', array());
        if (isset($settings[$key])) {
            return $settings[$key];
        }

        // Bridge: read from apollo-admin CPanel (email_ prefixed keys)
        static $admin_map = array(
            'from_name'     => 'email_from_name',
            'from_email'    => 'email_from_email',
            'smtp_host'     => 'email_smtp_host',
            'smtp_port'     => 'email_smtp_port',
            'smtp_username' => 'email_smtp_user',
            'smtp_password' => 'email_smtp_pass',
            'transport'     => 'email_transport',
            'track_opens'   => 'email_track_opens',
            'track_clicks'  => 'email_track_clicks',
            'brand_color'   => 'email_brand_color',
            'footer_text'   => 'email_footer_text',
        );

        if (isset($admin_map[$key])) {
            $admin = get_option('apollo_admin_settings', array());
            if (isset($admin[$admin_map[$key]]) && $admin[$admin_map[$key]] !== '') {
                $value = $admin[$admin_map[$key]];

                // Decrypt SMTP password if it was encrypted by apollo-admin.
                if ($key === 'smtp_password' && ! empty($admin['_email_smtp_pass_encrypted'])) {
                    $cipher = 'aes-256-cbc';
                    $enc_key = hash('sha256', AUTH_KEY, true);
                    $iv      = substr(hash('sha256', SECURE_AUTH_KEY), 0, 16);
                    $decoded = base64_decode($value); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
                    if ($decoded !== false) {
                        $decrypted = openssl_decrypt($decoded, $cipher, $enc_key, 0, $iv);
                        if ($decrypted !== false) {
                            $value = $decrypted;
                        }
                    }
                }

                return $value;
            }
        }

        // Backward compatibility for early admin keys used by this plugin.
        if ($key === 'smtp_username' && isset($settings['smtp_user']) && $settings['smtp_user'] !== '') {
            return $settings['smtp_user'];
        }
        if ($key === 'smtp_password' && isset($settings['smtp_pass']) && $settings['smtp_pass'] !== '') {
            return $settings['smtp_pass'];
        }

        return $default;
    }

    /**
     * Update a single setting.
     */
    public static function updateSetting(string $key, mixed $value): void
    {
        $settings         = get_option('apollo_email_settings', array());
        $settings[$key] = $value;
        update_option('apollo_email_settings', $settings);
    }

    /**
     * Get from name for outgoing emails.
     */
    public static function fromName(): string
    {
        $name = apply_filters('apollo/email/from_name', self::setting('from_name', ''));
        return $name ?: get_bloginfo('name');
    }

    /**
     * Get from email for outgoing emails.
     */
    public static function fromEmail(): string
    {
        $email = apply_filters('apollo/email/from_address', self::setting('from_email', ''));
        return $email ?: get_bloginfo('admin_email');
    }

    /**
     * Get active transport name.
     */
    public static function activeTransport(): string
    {
        return self::setting('transport', 'wp_mail');
    }

    /**
     * Configure PHPMailer for SMTP when settings are provided.
     *
     * Hooks into 'phpmailer_init' — the WordPress-standard approach
     * for configuring SMTP without replacing wp_mail().
     *
     * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
     */
    public function configureSmtpTransport($phpmailer): void
    {
        $from_name  = self::fromName();
        $from_email = self::fromEmail();

        $use_mailpit = defined('WP_ENVIRONMENT_TYPE')
            && 'local' === WP_ENVIRONMENT_TYPE
            && (! defined('APOLLO_SMTP_LIVE') || ! APOLLO_SMTP_LIVE);

        if ($use_mailpit) {
            $mailpit_port = defined('APOLLO_MAILPIT_PORT') ? (int) APOLLO_MAILPIT_PORT : 0;
            if ($mailpit_port <= 0) {
                $ini_port = (int) ini_get('smtp_port');
                $mailpit_port = $ini_port > 0 ? $ini_port : 1025;
            }
            $phpmailer->isSMTP();
            $phpmailer->Host        = defined('APOLLO_MAILPIT_HOST') ? (string) APOLLO_MAILPIT_HOST : '127.0.0.1';
            $phpmailer->Port        = $mailpit_port;
            $phpmailer->SMTPAuth    = false;
            $phpmailer->SMTPSecure  = false;
            $phpmailer->SMTPAutoTLS = false;
        } else {
            $host = self::setting('smtp_host', '');
            if (empty($host)) {
                return;
            }

            $port       = (int) self::setting('smtp_port', 587);
            $username   = (string) self::setting('smtp_username', '');
            $password   = (string) self::setting('smtp_password', '');
            $encryption = (string) self::setting('smtp_encryption', 'tls');

            $phpmailer->isSMTP();
            $phpmailer->Host = $host;
            $phpmailer->Port = $port;

            if ('' === $encryption || 'none' === $encryption) {
                $phpmailer->SMTPSecure  = false;
                $phpmailer->SMTPAutoTLS = false;
            } else {
                $phpmailer->SMTPSecure  = $encryption;
                $phpmailer->SMTPAutoTLS = ('tls' === $encryption);
            }

            if ('' !== $username) {
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = $username;
                $phpmailer->Password = $password;
            }

            $tls_peer = (string) self::setting('smtp_tls_peer', '');
            if ('' === $tls_peer && filter_var($host, FILTER_VALIDATE_IP)) {
                $from_domain = substr(strrchr($from_email, '@') ?: '', 1);
                $tls_peer    = $from_domain ? 'mail.' . $from_domain : '';
            }
            if ('' !== $tls_peer) {
                $phpmailer->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer'       => true,
                        'verify_peer_name'  => true,
                        'allow_self_signed' => false,
                        'peer_name'         => $tls_peer,
                    ),
                );
            }
        }

        $phpmailer->CharSet       = 'UTF-8';
        $phpmailer->Timeout       = 30;
        $phpmailer->SMTPKeepAlive = false;

        if ('' !== $from_email && is_email($from_email)) {
            $phpmailer->setFrom($from_email, $from_name, false);
            $phpmailer->Sender = $from_email;
        }
    }

    /**
     * Headers that keep transactional mail reputation-safe (no-reply, auto-generated).
     *
     * @param string[] $headers
     * @return string[]
     */
    public function applyDeliverabilityHeaders(array $headers, string $template): array
    {
        $from = self::fromEmail();
        if (str_starts_with($from, 'no-reply@') || str_starts_with($from, 'noreply@')) {
            $headers[] = 'Auto-Submitted: auto-generated';
            $headers[] = 'X-Auto-Response-Suppress: All';
        }

        return $headers;
    }

	// ──────────────────────────────────────────────────────────────
	// UNSUBSCRIBE SYSTEM
	// ──────────────────────────────────────────────────────────────

    /**
     * Generate a signed, tamper-proof unsubscribe URL for a given email.
     *
     * The token is an HMAC-SHA256 of the email address signed with AUTH_KEY,
     * base64url-encoded. The handler verifies it before processing.
     *
     * @param string $email Recipient email.
     * @return string Full unsubscribe URL.
     */
    public static function unsubscribeUrl(string $email): string
    {
        $token = base64_encode(hash_hmac('sha256', $email, wp_salt('auth'), true));
        return add_query_arg(
            array(
                'apollo_unsub' => '1',
                'email'        => rawurlencode($email),
                'token'        => rawurlencode($token),
            ),
            home_url('/')
        );
    }

    /**
     * Register the unsubscribe request handler on 'init'.
     *
     * Handles both:
     *   - GET  → browser click from email → redirect to confirmation page.
     *   - POST → RFC 8058 One-Click List-Unsubscribe=One-Click → 200 OK.
     */
    private function registerUnsubscribeHandler(): void
    {
        add_action(
            'init',
            function () {
                if (empty($_REQUEST['apollo_unsub'])) {
                    return;
                }

                // phpcs:disable WordPress.Security.NonceVerification.Recommended
                $email = sanitize_email(rawurldecode(wp_unslash($_GET['email'] ?? '')));
                $token = sanitize_text_field(rawurldecode(wp_unslash($_GET['token'] ?? '')));
                // phpcs:enable

                $expected = base64_encode(hash_hmac('sha256', $email, wp_salt('auth'), true));

                if (! is_email($email) || ! hash_equals($expected, $token)) {
                    wp_die(
                        esc_html__('Link de cancelamento inválido ou expirado.', 'apollo-email'),
                        'Apollo',
                        array('response' => 400)
                    );
                }

                self::processUnsubscribeEmail($email);

                // RFC 8058 One-Click: POST body = "List-Unsubscribe=One-Click".
                if (isset($_SERVER['REQUEST_METHOD']) && 'POST' === $_SERVER['REQUEST_METHOD']) {
                    status_header(200);
                    header('Content-Type: text/plain');
                    echo 'Unsubscribed';
                    exit;
                }

                // GET: browser click → redirect to branded confirmation page.
                wp_safe_redirect(
                    add_query_arg('apollo_unsub_done', '1', home_url('/'))
                );
                exit;
            },
            1
        );
    }

    /**
     * Mark an email address as unsubscribed in all relevant stores.
     *
     * 1. wp_usermeta for registered WP users (disables marketing + digest).
     * 2. Apollo Newsletter subscriber table (sets status = 'unsubscribed').
     *
     * @param string $email Email to unsubscribe.
     */
    public static function processUnsubscribeEmail(string $email): void
    {
        // 1. Registered WP user.
        $user = get_user_by('email', $email);
        if ($user) {
            update_user_meta($user->ID, '_apollo_email_unsubscribed', '1');
            update_user_meta($user->ID, '_apollo_email_unsub_at', current_time('mysql'));

            $prefs = get_user_meta($user->ID, '_apollo_email_prefs', true);
            if (! is_array($prefs)) {
                $prefs = array();
            }
            $prefs['marketing'] = false;
            $prefs['digest']    = false;
            update_user_meta($user->ID, '_apollo_email_prefs', $prefs);
        }

        // 2. Newsletter subscriber table.
        global $wpdb;
        $table = $wpdb->prefix . 'apollo_newsletter_subscribers';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) {
            $wpdb->update(
                $table,
                array(
                    'status'          => 'unsubscribed',
                    'unsubscribed_at' => current_time('mysql'),
                ),
                array('email' => $email),
                array('%s', '%s'),
                array('%s')
            );
        }

        /**
         * Fires after a user unsubscribes from Apollo emails.
         *
         * @param string $email The unsubscribed email address.
         */
        do_action('apollo/email/unsubscribed', $email);
    }

    /**
     * Log SMTP failures with response class for monitoring / queue decisions.
     */
    public function onWpMailFailed(\WP_Error $error): void
    {
        $message = $error->get_error_message();
        $analysis = Mailer\SmtpDelivery::classify($message);
        if ('permanent' === $analysis['kind']) {
            error_log(sprintf(
                '[Apollo Email] Permanent SMTP rejection (code %d): %s',
                $analysis['code'],
                $message
            ));
        }
    }

	// ──────────────────────────────────────────────────────────────
	// SHORTCODES
	// ──────────────────────────────────────────────────────────────

    /**
     * Render the [apollo_email_prefs] shortcode.
     */
    public function renderEmailPrefsShortcode(array $atts = array()): string
    {
        if (! is_user_logged_in()) {
            return '<p class="apollo-email-login-required">' . esc_html__('Faça login para gerenciar suas preferências de email.', 'apollo-email') . '</p>';
        }

        $user_id = get_current_user_id();
        $prefs   = get_user_meta($user_id, '_apollo_email_prefs', true);
        if (! is_array($prefs)) {
            $prefs = array(
                'transactional'    => true,
                'marketing'        => true,
                'digest'           => true,
                'gestor_reminders' => true,
            );
        }

        ob_start();
?>
        <form class="apollo-email-prefs-form" method="post" data-apollo-email-prefs>
            <?php wp_nonce_field('apollo_email_prefs', '_apollo_email_prefs_nonce'); ?>
            <h3><?php esc_html_e('Preferências de Email', 'apollo-email'); ?></h3>
            <label>
                <input type="checkbox" name="prefs[transactional]" value="1" <?php checked(! empty($prefs['transactional'])); ?> disabled>
                <?php esc_html_e('Transacionais (obrigatório)', 'apollo-email'); ?>
            </label>
            <label>
                <input type="checkbox" name="prefs[marketing]" value="1" <?php checked(! empty($prefs['marketing'])); ?>>
                <?php esc_html_e('Marketing e novidades', 'apollo-email'); ?>
            </label>
            <label>
                <input type="checkbox" name="prefs[digest]" value="1" <?php checked(! empty($prefs['digest'])); ?>>
                <?php esc_html_e('Resumo semanal', 'apollo-email'); ?>
            </label>
            <label>
                <input type="checkbox" name="prefs[gestor_reminders]" value="1" <?php checked(! empty($prefs['gestor_reminders'])); ?>>
                <?php esc_html_e('Lembretes de tarefas do Gestor', 'apollo-email'); ?>
            </label>
            <button type="submit" class="apollo-btn"><?php esc_html_e('Salvar Preferências', 'apollo-email'); ?></button>
        </form>
<?php
        return ob_get_clean();
    }

    // ──────────────────────────────────────────────────────────────
    // CROSS-PLUGIN HOOK INTEGRATIONS
    // ──────────────────────────────────────────────────────────────
    //
    // TRIGGER MAP (send + AJAX entry points)
    // ─────────────────────────────────────────────────────────────
    // IMMEDIATE (apollo_send_email):
    //   apollo/login/registered              → onUserRegistered          welcome
    //   apollo/login/verification_email      → onVerificationEmail       verification
    //   apollo/login/password_reset_requested→ onPasswordResetRequested  password-reset
    //   apollo/membership/achievement_awarded→ onAchievementEarned       achievement
    //   apollo/groups/user_invited           → onGroupInvitation         group-invite
    //   apollo/chat/message_sent             → onChatMessage             chat (offline)
    //   apollo/gestor/task_reminder          → onTaskReminder            task-reminder
    //
    // QUEUED (apollo_queue_email):
    //   apollo/event/reminder                → onEventReminder
    //   apollo/notif/digest                  → onNotifDigest
    //   apollo/email/digest/*                → onDigestNotifications, fav_events, event_match, chat, comuna, news, social
    //
    // CRON:
    //   APOLLO_EMAIL_CRON_HOOK               → Queue::processNext
    //
    // REST (apollo/v1, admin):
    //   POST /email/send, /email/test, queue cancel/retry/purge, templates CRUD, prefs, stats, log
    //
    // ADMIN POST (no AJAX — form POST):
    //   admin_post_apollo_email_test, _template_test, _queue_action, _purge_queue, _purge_log
    //
    // LOGIN AJAX (apollo-login, not apollo-email):
    //   apollo_forgot_password  → PasswordReset → apollo/login/password_reset_requested
    //   apollo_register         → RegisterHandler → registered + verification_email
    // ─────────────────────────────────────────────────────────────

    private function registerHookIntegrations(): void
    {
        // Welcome email on user registration
        add_action('apollo/login/registered', array($this, 'onUserRegistered'), 10, 2);

        // Password reset email
        add_action('apollo/login/password_reset_requested', array($this, 'onPasswordResetRequested'), 10, 2);

        // Email verification
        add_action('apollo/login/verification_email', array($this, 'onVerificationEmail'), 10, 2);

        // Event reminder (from apollo-events cron)
        add_action('apollo/event/reminder', array($this, 'onEventReminder'), 10, 2);

        // Notification digest
        add_action('apollo/notif/digest', array($this, 'onNotifDigest'), 10, 2);

        // Segmented digests
        add_action('apollo/email/digest/notifications', array($this, 'onDigestNotifications'), 10, 2);
        add_action('apollo/email/digest/fav_events', array($this, 'onDigestFavEvents'), 10, 2);
        add_action('apollo/email/digest/event_match', array($this, 'onDigestEventMatch'), 10, 2);
        add_action('apollo/email/digest/chat', array($this, 'onDigestChat'), 10, 2);
        add_action('apollo/email/digest/comuna', array($this, 'onDigestComuna'), 10, 2);
        add_action('apollo/email/digest/news', array($this, 'onDigestNews'), 10, 2);
        add_action('apollo/email/digest/social', array($this, 'onDigestSocial'), 10, 2);

        // Membership achievement awarded (canonical bridge)
        add_action('apollo/membership/achievement_awarded', array($this, 'onAchievementEarned'), 10, 5);

        // Group invitation
        add_action('apollo/groups/user_invited', array($this, 'onGroupInvitation'), 10, 3);

        // New chat message (email notification if user offline)
        add_action('apollo/chat/message_sent', array($this, 'onChatMessage'), 10, 3);

        // Task reminder from apollo-gestor cron
        add_action('apollo/gestor/task_reminder', array($this, 'onTaskReminder'), 10, 3);
    }

    /**
     * Send welcome email on user registration.
     */
    public function onUserRegistered(int $user_id, array $data = array()): void
    {
        if (function_exists('Apollo\\Login\\apollo_login_debug_log')) {
            \Apollo\Login\apollo_login_debug_log(
                'Plugin::onUserRegistered',
                'hook_fired',
                array( 'user_id' => $user_id ),
                'H5'
            );
        }

        $user = get_userdata($user_id);
        if (! $user) {
            return;
        }

        $sent = apollo_send_email(
            $user->user_email,
            __('Bem-vindo(a) ao Apollo Rio! 🎉', 'apollo-email'),
            'welcome',
            array(
                'user_id'     => $user_id,
                'user_name'   => $data['social_name'] ?? $user->display_name,
                'username'    => $user->user_login,
                'profile_url' => home_url('/id/' . $user->user_login),
                'site_name'   => get_bloginfo('name'),
                'site_url'    => home_url('/'),
            )
        );

        if (function_exists('Apollo\\Login\\apollo_login_debug_log')) {
            \Apollo\Login\apollo_login_debug_log(
                'Plugin::onUserRegistered',
                'welcome_send_result',
                array( 'user_id' => $user_id, 'sent' => (bool) $sent ),
                'H5'
            );
        }
    }

    /**
     * Send password reset email.
     */
    public function onPasswordResetRequested(int $user_id, string $reset_url): void
    {
        if (function_exists('Apollo\\Login\\apollo_login_debug_log')) {
            \Apollo\Login\apollo_login_debug_log(
                'Plugin::onPasswordResetRequested',
                'hook_fired',
                array( 'user_id' => $user_id ),
                'H5'
            );
        }

        $user = get_userdata($user_id);
        if (! $user) {
            return;
        }

        $sent = apollo_send_email(
            $user->user_email,
            __('Sua nova chave de acesso — Apollo Rio', 'apollo-email'),
            'password-reset',
            array(
                'user_id'         => $user_id,
                'user_name'       => $user->display_name,
                'reset_url'       => $reset_url,
                'confirmation_link' => $reset_url,
                'site_name'       => get_bloginfo('name'),
                'expires_in'      => '1 hora',
                'expiration_time' => '1 hora',
            )
        );

        if ($sent) {
            \Apollo\Login\apollo_login_mark_password_reset_email_sent();
        }

        if (function_exists('Apollo\\Login\\apollo_login_debug_log')) {
            \Apollo\Login\apollo_login_debug_log(
                'Plugin::onPasswordResetRequested',
                'password_reset_send_result',
                array( 'user_id' => $user_id, 'sent' => (bool) $sent ),
                'H5'
            );
        }
    }

    /**
     * Send email verification.
     */
    public function onVerificationEmail(int $user_id, string $verify_url): void
    {
        if (function_exists('Apollo\\Login\\apollo_login_debug_log')) {
            \Apollo\Login\apollo_login_debug_log(
                'Plugin::onVerificationEmail',
                'hook_fired',
                array( 'user_id' => $user_id ),
                'H5'
            );
        }

        $user = get_userdata($user_id);
        if (! $user) {
            return;
        }

        $sent = apollo_send_email(
            $user->user_email,
            __('Verifique seu email — Apollo Rio', 'apollo-email'),
            'verification',
            array(
                'user_id'           => $user_id,
                'user_name'         => $user->display_name,
                'verify_url'        => $verify_url,
                'confirmation_link' => $verify_url,
                'site_name'         => get_bloginfo('name'),
            )
        );

        if ($sent) {
            \Apollo\Login\apollo_login_mark_verification_email_sent();
        }

        if (function_exists('Apollo\\Login\\apollo_login_debug_log')) {
            \Apollo\Login\apollo_login_debug_log(
                'Plugin::onVerificationEmail',
                'verification_send_result',
                array( 'user_id' => $user_id, 'sent' => (bool) $sent ),
                'H5'
            );
        }
    }

    /**
     * Send event reminder.
     */
    public function onEventReminder(int $event_id, array $user_ids): void
    {
        $event = get_post($event_id);
        if (! $event) {
            return;
        }

        foreach ($user_ids as $uid) {
            $user = get_userdata($uid);
            if (! $user) {
                continue;
            }

            // Check user prefs
            $prefs = get_user_meta($uid, '_apollo_email_prefs', true);
            if (is_array($prefs) && empty($prefs['transactional'])) {
                continue;
            }

            apollo_queue_email(
                $user->user_email,
                sprintf(__('Lembrete: %s é hoje! 🎶', 'apollo-email'), $event->post_title),
                'event-reminder',
                array(
                    'user_id'        => $uid,
                    'user_name'      => $user->display_name,
                    'event_title'    => $event->post_title,
                    'event_name'     => $event->post_title,
                    'event_url'      => get_permalink($event_id),
                    'event_date'     => get_post_meta($event_id, '_event_start_date', true),
                    'event_time'     => get_post_meta($event_id, '_event_start_time', true),
                    'event_location' => get_post_meta($event_id, '_event_loc_name', true),
                    'loc_name'       => get_post_meta($event_id, '_event_loc_name', true),
                    'site_name'      => get_bloginfo('name'),
                ),
                3 // high priority
            );
        }
    }

    /**
     * Send notification digest.
     */
    public function onNotifDigest(int $user_id, array $notifications): void
    {
        $this->queueDigestPart(
            $user_id,
            __('Seu digest de notificações — Apollo Rio', 'apollo-email'),
            __('Digest de Notificações', 'apollo-email'),
            $notifications,
            __('Confira suas notificações recentes.', 'apollo-email')
        );
    }

    /**
     * Segmented digest: notifications.
     */
    public function onDigestNotifications(int $user_id, array $items): void
    {
        $this->queueDigestPart(
            $user_id,
            __('Seu digest de notificações — Apollo Rio', 'apollo-email'),
            __('Digest de Notificações', 'apollo-email'),
            $items,
            __('Confira suas notificações recentes.', 'apollo-email')
        );
    }

    /**
     * Segmented digest: favorited events updates.
     */
    public function onDigestFavEvents(int $user_id, array $items): void
    {
        $this->queueDigestPart(
            $user_id,
            __('Atualizações dos seus eventos salvos — Apollo Rio', 'apollo-email'),
            __('Digest de Eventos Salvos', 'apollo-email'),
            $items,
            __('Mudanças de status e informações dos eventos salvos por você.', 'apollo-email')
        );
    }

    /**
     * Segmented digest: event matchmaking by sound profile.
     */
    public function onDigestEventMatch(int $user_id, array $items): void
    {
        $this->queueDigestPart(
            $user_id,
            __('Eventos que combinam com seu som — Apollo Rio', 'apollo-email'),
            __('Digest de Match de Som', 'apollo-email'),
            $items,
            __('Novos eventos com match no seu perfil de sons.', 'apollo-email')
        );
    }

    /**
     * Segmented digest: chat activity.
     */
    public function onDigestChat(int $user_id, array $items): void
    {
        $this->queueDigestPart(
            $user_id,
            __('Seu digest de chat — Apollo Rio', 'apollo-email'),
            __('Digest de Chat', 'apollo-email'),
            $items,
            __('Resumo de mensagens e conversas recentes.', 'apollo-email')
        );
    }

    /**
     * Segmented digest: groups (comuna) activity.
     */
    public function onDigestComuna(int $user_id, array $items): void
    {
        $this->queueDigestPart(
            $user_id,
            __('Novidades das suas comunas — Apollo Rio', 'apollo-email'),
            __('Digest de Comunas', 'apollo-email'),
            $items,
            __('Atividades novas nas comunas das quais você participa.', 'apollo-email')
        );
    }

    /**
     * Segmented digest: Apollo news.
     */
    public function onDigestNews(int $user_id, array $items): void
    {
        $this->queueDigestPart(
            $user_id,
            __('Apollo News da semana — Apollo Rio', 'apollo-email'),
            __('Digest de Apollo News', 'apollo-email'),
            $items,
            __('Atualizações e destaques do ecossistema Apollo.', 'apollo-email')
        );
    }

    /**
     * Segmented digest: social updates.
     */
    public function onDigestSocial(int $user_id, array $items): void
    {
        $this->queueDigestPart(
            $user_id,
            __('Movimento social no seu perfil — Apollo Rio', 'apollo-email'),
            __('Digest Social', 'apollo-email'),
            $items,
            __('Quem visitou seu perfil e reações no seu conteúdo.', 'apollo-email')
        );
    }

    /**
     * Queue one digest part email.
     */
    private function queueDigestPart(int $user_id, string $subject, string $title, array $items, string $intro = ''): void
    {
        $user = get_userdata($user_id);
        if (! $user) {
            return;
        }

        $prefs = get_user_meta($user_id, '_apollo_email_prefs', true);
        if (is_array($prefs) && empty($prefs['digest'])) {
            return;
        }

        if (empty($items)) {
            return;
        }

        $rows = array();
        foreach ($items as $item) {
            if (is_array($item)) {
                $rows[] = array(
                    'heading' => (string) ($item['title'] ?? ''),
                    'message' => (string) ($item['message'] ?? ''),
                    'time'    => (string) ($item['time'] ?? ''),
                    'url'     => (string) ($item['url'] ?? ''),
                );
                continue;
            }

            $rows[] = array(
                'heading' => '',
                'message' => (string) $item,
                'time'    => '',
                'url'     => '',
            );
        }

        apollo_queue_email(
            $user->user_email,
            $subject,
            'digest',
            array(
                'user_id'         => $user_id,
                'user_name'       => $user->display_name,
                'digest_title'    => $title,
                'digest_intro'    => $intro,
                'digest_sections' => array(
                    array(
                        'title' => $title,
                        'items' => $rows,
                    ),
                ),
                'site_name'       => get_bloginfo('name'),
                'site_url'        => home_url('/'),
            ),
            7
        );
    }

    /**
     * Send achievement earned notification.
     *
     * Canonical hook: apollo/membership/achievement_awarded
     * Params: user_id, achievement_id, trigger, site_id, args
     */
    public function onAchievementEarned(int $user_id, int $achievement_id, string $trigger = '', int $site_id = 0, array $args = array()): void
    {
        $user = get_userdata($user_id);
        if (! $user) {
            return;
        }

        $achievement_title = get_the_title($achievement_id) ?: __('Conquista', 'apollo-email');

        apollo_send_email(
            $user->user_email,
            sprintf(__('Conquista desbloqueada: %s 🏆', 'apollo-email'), $achievement_title),
            'notification',
            array(
                'user_id'     => $user_id,
                'user_name'   => $user->display_name,
                'title'       => $achievement_title,
                'message'     => sprintf(__('Parabéns! Você desbloqueou a conquista "%s".', 'apollo-email'), $achievement_title),
                'action_url'  => home_url('/minhas-conquistas'),
                'action_text' => __('Ver Conquistas', 'apollo-email'),
                'site_name'   => get_bloginfo('name'),
            )
        );
    }

    /**
     * Send group invitation email.
     */
    public function onGroupInvitation(int $user_id, int $group_id, int $invited_by): void
    {
        $user    = get_userdata($user_id);
        $inviter = get_userdata($invited_by);
        if (! $user || ! $inviter) {
            return;
        }

        apollo_send_email(
            $user->user_email,
            __('Convite para grupo — Apollo Rio', 'apollo-email'),
            'notification',
            array(
                'user_id'     => $user_id,
                'user_name'   => $user->display_name,
                'title'       => __('Convite para Grupo', 'apollo-email'),
                'message'     => sprintf(__('%s convidou você para participar de um grupo.', 'apollo-email'), $inviter->display_name),
                'action_url'  => home_url('/grupo/' . $group_id),
                'action_text' => __('Ver Grupo', 'apollo-email'),
                'site_name'   => get_bloginfo('name'),
            )
        );
    }

    /**
     * Send chat message notification (if user offline).
     */
    public function onChatMessage(int $thread_id, int $sender_id, int $recipient_id): void
    {
        $recipient   = get_userdata($recipient_id);
        $sender_user = get_userdata($sender_id);
        if (! $recipient || ! $sender_user) {
            return;
        }

        // Only send if user is offline
        $status = get_user_meta($recipient_id, '_apollo_chat_status', true);
        if ($status !== 'offline') {
            return;
        }

        // Check email prefs
        $prefs = get_user_meta($recipient_id, '_apollo_email_prefs', true);
        if (is_array($prefs) && empty($prefs['transactional'])) {
            return;
        }

        apollo_queue_email(
            $recipient->user_email,
            sprintf(__('Nova mensagem de %s — Apollo Rio', 'apollo-email'), $sender_user->display_name),
            'notification',
            array(
                'user_id'     => $recipient_id,
                'user_name'   => $recipient->display_name,
                'title'       => __('Nova Mensagem', 'apollo-email'),
                'message'     => sprintf(__('%s enviou uma mensagem para você.', 'apollo-email'), $sender_user->display_name),
                'action_url'  => home_url('/mensagens/' . $thread_id),
                'action_text' => __('Abrir Conversa', 'apollo-email'),
                'site_name'   => get_bloginfo('name'),
            ),
            4 // medium-high priority
        );
    }

    /**
     * Send task reminder email (fired by apollo-gestor cron).
     *
     * @param int   $user_id    Assignee user ID.
     * @param array $email_data Merge tag data.
     * @param array $task       Raw task row.
     */
    public function onTaskReminder(int $user_id, array $email_data, array $task = array()): void
    {
        $user = get_userdata($user_id);
        if (! $user) {
            return;
        }

        // Respect user email preferences.
        $prefs = get_user_meta($user_id, '_apollo_email_prefs', true);
        if (is_array($prefs) && isset($prefs['gestor_reminders']) && empty($prefs['gestor_reminders'])) {
            return;
        }

        $task_name = $email_data['task_name'] ?? $email_data['task_title'] ?? __('Tarefa', 'apollo-email');

        // Normalize aliases so both legacy and V3 template keys resolve correctly.
        $payload = $email_data;
        $payload['user_id']         = $payload['user_id'] ?? $user_id;
        $payload['user_name']       = $payload['user_name'] ?? $user->display_name;
        $payload['task_name']       = $payload['task_name'] ?? $payload['task_title'] ?? $task_name;
        $payload['task_title']      = $payload['task_title'] ?? $payload['task_name'];
        $payload['task_date']       = $payload['task_date'] ?? $payload['deadline_date'] ?? '';
        $payload['deadline_date']   = $payload['deadline_date'] ?? $payload['task_date'];
        $payload['task_deadline_label'] = $payload['task_deadline_label'] ?? $payload['deadline_label'] ?? '';
        $payload['deadline_label']  = $payload['deadline_label'] ?? $payload['task_deadline_label'];
        $payload['task_priority']   = $payload['task_priority'] ?? $payload['priority'] ?? '';
        $payload['priority']        = $payload['priority'] ?? $payload['task_priority'];
        $payload['task_project']    = $payload['task_project'] ?? $payload['project_name'] ?? '';
        $payload['project_name']    = $payload['project_name'] ?? $payload['task_project'];
        $payload['task_project_url'] = $payload['task_project_url'] ?? $payload['project_url'] ?? '';
        $payload['project_url']     = $payload['project_url'] ?? $payload['task_project_url'];
        $payload['task_assigned_by'] = $payload['task_assigned_by'] ?? $payload['assigned_by'] ?? '';
        $payload['assigned_by']     = $payload['assigned_by'] ?? $payload['task_assigned_by'];
        $payload['site_name']       = $payload['site_name'] ?? get_bloginfo('name');
        $payload['site_url']        = $payload['site_url'] ?? home_url('/');

        apollo_queue_email(
            $user->user_email,
            sprintf(__('Lembrete: %s — %s', 'apollo-email'), $task_name, get_bloginfo('name')),
            'task-deadline',
            $payload,
            2 // high priority — time-sensitive
        );
    }

    // ──────────────────────────────────────────────────────────────
    // ADMIN ASSETS
    // ──────────────────────────────────────────────────────────────

    public function enqueueAdminAssets(string $hook): void
    {
        if (! str_contains($hook, 'apollo-email')) {
            return;
        }

        wp_enqueue_style(
            'apollo-email-admin',
            APOLLO_EMAIL_URL . 'assets/css/admin-email.css',
            array(),
            APOLLO_EMAIL_VERSION
        );

        wp_enqueue_script(
            'apollo-email-admin',
            APOLLO_EMAIL_URL . 'assets/js/admin-email.js',
            array('jquery', 'wp-api-fetch'),
            APOLLO_EMAIL_VERSION,
            true
        );

        wp_localize_script(
            'apollo-email-admin',
            'apolloEmailAdmin',
            array(
                'restUrl'   => rest_url('apollo/v1/'),
                'nonce'     => wp_create_nonce('wp_rest'),
                'version'   => APOLLO_EMAIL_VERSION,
                'batchSize' => APOLLO_EMAIL_BATCH_SIZE,
                'pluginUrl' => APOLLO_EMAIL_URL,
                'adminUrl'  => admin_url(),
                'i18n'      => array(
                    'confirmDelete' => __('Tem certeza que deseja excluir?', 'apollo-email'),
                    'sending'       => __('Enviando...', 'apollo-email'),
                    'sent'          => __('Enviado!', 'apollo-email'),
                    'error'         => __('Erro ao enviar', 'apollo-email'),
                    'saved'         => __('Salvo!', 'apollo-email'),
                    'testSent'      => __('Email de teste enviado!', 'apollo-email'),
                ),
            )
        );
    }
}
