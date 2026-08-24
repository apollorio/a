<?php

/**
 * Core Email Sender — handles rendering, sending, and logging.
 *
 * Orchestrates template rendering, applies headers/defaults,
 * and delegates actual sending to wp_mail or configured transport.
 *
 * @package Apollo\Email\Mailer
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email\Mailer;

use Apollo\Email\Plugin;
use Apollo\Email\Template\TemplateEngine;
use Apollo\Email\Log\Logger;

if (! defined('ABSPATH')) {
	exit;
}

class Sender
{

	private TemplateEngine $templates;
	private Logger $logger;

	public function __construct(TemplateEngine $templates, Logger $logger)
	{
		$this->templates = $templates;
		$this->logger    = $logger;
	}

	/**
	 * Send an email immediately.
	 *
	 * STRICT MODE — every email dispatched through this system MUST use a registered
	 * template slug. Raw-HTML sends are rejected to guarantee consistent rendering,
	 * proper from-address, logging, and V3 shell wrapping. This is the single
	 * authorised dispatch point for the entire Apollo email system.
	 *
	 * @param Message $message The message to send.
	 * @return array{success: bool, error: string|null, log_id?: int}
	 */
	public function send(Message $message): array
	{
		// ── STRICT: template required ────────────────────────────
		if (empty($message->getTemplate())) {
			$blocked = sprintf(
				'[Apollo Email] BLOCKED — raw-body send to "%s" rejected. Every email must declare a template slug (e.g. \'notification\').',
				$message->getTo()
			);
			error_log($blocked);
			return array(
				'success' => false,
				'error'   => $blocked,
				'log_id'  => null,
			);
		}

		// ── Validate ─────────────────────────────────────────────
		$errors = $message->validate();

		// If template-based, render first
		if (! empty($message->getTemplate()) && empty($message->getHtml())) {
			$rendered = $this->templates->render($message->getTemplate(), $message->getData());
			$message->setHtml($rendered);

			if (empty($message->getSubject())) {
				$subject = $this->templates->renderSubject($message->getTemplate(), $message->getData());
				$message->setSubject($subject);
			}

			// Re-validate after rendering
			$errors = $message->validate();
		}

		if (! empty($errors)) {
			return array(
				'success' => false,
				'error'   => implode(' ', $errors),
			);
		}

		// ── Apply defaults ───────────────────────────────────────
		if (empty($message->getFrom())) {
			$message->setFrom(Plugin::fromEmail())
				->setFromName(Plugin::fromName());
		}

		// ── Reply-To default (hey@apollo.rio.br unless overridden in settings) ──
		if (empty($message->getReplyTo())) {
			$reply_to = Plugin::setting('reply_to', 'hey@apollo.rio.br');
			if ($reply_to) {
				$message->setReplyTo($reply_to);
			}
		}

		// ── Inject unsubscribe_url into template data ────────────
		// Always generated; templates can override via $data['unsubscribe_url'].
		$unsubscribe_url = Plugin::unsubscribeUrl($message->getTo());
		$message->setData(
			array_merge(
				array('unsubscribe_url' => $unsubscribe_url),
				$message->getData()
			)
		);

		// ── Before-send hook ─────────────────────────────────────
		$email_data = array(
			'to'       => $message->getTo(),
			'subject'  => $message->getSubject(),
			'template' => $message->getTemplate(),
		);

		/**
		 * Fires before an email is sent.
		 *
		 * @param array   $email_data Summary data.
		 * @param Message $message    Full message object.
		 */
		do_action('apollo/email/before_send', $email_data, $message);

		// ── Build headers ────────────────────────────────────────
		$headers = $message->buildHeaders();

		// ── List-Unsubscribe (RFC 5321 + RFC 8058 One-Click) ─────
		$headers[] = 'List-Unsubscribe: <' . $unsubscribe_url . '>';
		$headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';

		/**
		 * Filter email headers.
		 *
		 * @param string[] $headers  Headers array.
		 * @param string   $template Template slug.
		 */
		$headers = apply_filters('apollo/email/headers', $headers, $message->getTemplate());

		// ── Send via wp_mail ─────────────────────────────────────
		$result = wp_mail(
			$message->getTo(),
			$message->getSubject(),
			$message->getHtml(),
			$headers,
			$message->getAttachments()
		);

		// ── Log result ───────────────────────────────────────────
		if ($result) {
			$log_id = $this->logger->logSent(
				$message->getTo(),
				$message->getSubject(),
				$message->getTemplate(),
				$this->getEmailType($message)
			);

			/**
			 * Fires after an email is successfully sent.
			 *
			 * @param int     $log_id  Log entry ID.
			 * @param Message $message The sent message.
			 */
			do_action('apollo/email/sent', $log_id, $message);

			return array(
				'success' => true,
				'error'   => null,
				'log_id'  => $log_id,
			);
		}

		// ── Failed ───────────────────────────────────────────────
		global $phpmailer;
		$error_msg = '';
		if (isset($phpmailer) && $phpmailer instanceof \PHPMailer\PHPMailer\PHPMailer) {
			$error_msg = $phpmailer->ErrorInfo;
		}

		$log_id = $this->logger->logFailed(
			$message->getTo(),
			$message->getSubject(),
			$message->getTemplate(),
			$error_msg
		);

		/**
		 * Fires when an email fails to send.
		 *
		 * @param int     $log_id  Log entry ID.
		 * @param string  $error   Error message.
		 * @param Message $message The failed message.
		 */
		do_action('apollo/email/failed', $log_id, $error_msg, $message);

		return array(
			'success' => false,
			'error'   => $error_msg ?: __('Falha ao enviar email.', 'apollo-email'),
		);
	}

	/**
	 * Send a simple email (convenience method).
	 *
	 * @param string $to       Recipient email.
	 * @param string $subject  Email subject.
	 * @param string $template Template slug.
	 * @param array  $data     Merge tag data.
	 * @return array{success: bool, error: string|null}
	 */
	public function sendTemplate(string $to, string $subject, string $template, array $data = array()): array
	{
		$message = Message::fromTemplate($to, $template, $data);
		$message->setSubject($subject);

		return $this->send($message);
	}

	/**
	 * Send a test email to verify configuration.
	 *
	 * @param string $to Recipient email.
	 * @return array{success: bool, error: string|null}
	 */
	public function sendTest(string $to): array
	{
		$message = Message::make(
			$to,
			'[Apollo Email] Email de Teste',
			''
		);

		$message->setTemplate('notification')
			->setData(
				array(
					'user_name'   => 'Admin',
					'title'       => 'Email de Teste',
					'message'     => 'Se você está lendo isto, o Apollo Email está configurado corretamente! Este email foi enviado em ' . wp_date('d/m/Y H:i:s') . '.',
					'action_url'  => admin_url('admin.php?page=apollo-email'),
					'action_text' => 'Abrir Painel',
					'site_name'   => get_bloginfo('name'),
				)
			)
			->setTrackOpens(false)
			->setTrackClicks(false);

		return $this->send($message);
	}

	/**
	 * Determine email type from message.
	 */
	private function getEmailType(Message $message): string
	{
		$template = $message->getTemplate();
		if (! $template) {
			return 'transactional';
		}

		$tpl = $this->templates->getTemplate($template);
		return $tpl['type'] ?? 'transactional';
	}
}
