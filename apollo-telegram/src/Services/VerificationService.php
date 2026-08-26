<?php

/**
 * Phone verification state machine (apollo_telegram_verif table).
 *
 * @package Apollo\Telegram\Services
 */

declare(strict_types=1);

namespace Apollo\Telegram\Services;

use Apollo\Telegram\Security\RateLimiter;

if (! defined('ABSPATH')) {
	exit;
}

final class VerificationService
{
	public const STEP_REQUESTED       = 'requested';
	public const STEP_CONTACT_SHARED  = 'contact_shared';
	public const STEP_CODE_DELIVERED  = 'code_delivered';
	public const STATUS_PENDING       = 'pending';
	public const STATUS_VERIFIED      = 'verified';
	public const STATUS_FAILED        = 'failed';

	/**
	 * Ensure verification table exists / is upgraded.
	 */
	public static function install_schema(): void
	{
		global $wpdb;

		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			request_id CHAR(36) NOT NULL,
			web_user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			phone VARCHAR(20) NOT NULL,
			code_hash VARCHAR(255) NULL,
			telegram_chat_id BIGINT UNSIGNED NULL,
			step VARCHAR(32) NOT NULL DEFAULT 'requested',
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			verify_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
			expires_at DATETIME NOT NULL,
			created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uniq_request_id (request_id),
			KEY idx_phone_status_exp (phone, status, expires_at),
			KEY idx_chat_status (telegram_chat_id, status),
			KEY idx_status_exp (status, expires_at)
		) {$charset_collate};";

		dbDelta($sql);
	}

	public static function table(): string
	{
		global $wpdb;

		return $wpdb->prefix . 'apollo_telegram_verif';
	}

	/**
	 * Create pending verification request.
	 *
	 * @return array{success:bool,message?:string,request_id?:string,deep_link?:string,expires_minutes?:int}
	 */
	public static function createPending(string $phone, int $web_user_id = 0): array
	{
		$normalized = apollo_telegram_normalize_phone($phone);
		if (null === $normalized) {
			return array(
				'success' => false,
				'message' => __('Número de telefone inválido.', 'apollo-telegram'),
			);
		}

		$ip = RateLimiter::client_ip();
		if (! RateLimiter::allow('req_ver_ip:' . $ip, 5, 15 * MINUTE_IN_SECONDS)) {
			return array(
				'success' => false,
				'message' => __('Muitas solicitações. Aguarde alguns minutos.', 'apollo-telegram'),
				'code'    => 'rate_limited',
			);
		}
		if (! RateLimiter::allow('req_ver_phone:' . $normalized, 3, HOUR_IN_SECONDS)) {
			return array(
				'success' => false,
				'message' => __('Limite de solicitações para este número. Tente mais tarde.', 'apollo-telegram'),
				'code'    => 'rate_limited',
			);
		}

		global $wpdb;

		$request_id = wp_generate_uuid4();
		$expires    = gmdate('Y-m-d H:i:s', time() + 15 * MINUTE_IN_SECONDS);
		$inserted   = $wpdb->insert(
			self::table(),
			array(
				'request_id'   => $request_id,
				'web_user_id'  => max(0, $web_user_id),
				'phone'        => $normalized,
				'step'         => self::STEP_REQUESTED,
				'status'       => self::STATUS_PENDING,
				'expires_at'   => $expires,
			),
			array('%s', '%d', '%s', '%s', '%s', '%s')
		);

		if (false === $inserted) {
			return array(
				'success' => false,
				'message' => __('Não foi possível registrar a solicitação.', 'apollo-telegram'),
			);
		}

		$bot_username = apollo_telegram_bot_username() ?: 'apolloRio_bot';
		$deep_link    = 'https://t.me/' . rawurlencode($bot_username) . '?start=' . rawurlencode($request_id);

		apollo_telegram_debug_log(
			'verify_requested',
			array(
				'request_id' => $request_id,
				'phone'      => apollo_telegram_phone_log_suffix($normalized),
			)
		);

		return array(
			'success'         => true,
			'request_id'      => $request_id,
			'deep_link'       => $deep_link,
			'expires_minutes' => 15,
			'message'         => __('Solicitação registrada. Abra o Telegram — o bot envia o código na hora.', 'apollo-telegram'),
		);
	}

	/**
	 * Fast path: deliver OTP as soon as /start {request_id} deep-link is opened.
	 * Deep-link UUID is the secret; no contact-share required for code delivery.
	 *
	 * @return array{success:bool,message?:string,code?:string,phone?:string}
	 */
	public static function deliverCodeForStart(string $request_id, int $chat_id): array
	{
		$request_id = strtolower(trim($request_id));
		if ('' === $request_id || $chat_id <= 0) {
			return array(
				'success' => false,
				'message' => __('Solicitação inválida.', 'apollo-telegram'),
			);
		}

		if (! RateLimiter::allow('start_code:' . $chat_id, 5, 10 * MINUTE_IN_SECONDS)) {
			return array(
				'success' => false,
				'message' => __('Limite de tentativas atingido. Aguarde alguns minutos.', 'apollo-telegram'),
			);
		}

		global $wpdb;
		$table = self::table();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE request_id = %s AND status = %s AND expires_at > UTC_TIMESTAMP() LIMIT 1",
				$request_id,
				self::STATUS_PENDING
			)
		);

		if (! $row) {
			apollo_telegram_debug_log(
				'start_code_not_found',
				array(
					'request_id' => $request_id,
					'chat_id'    => $chat_id,
				)
			);

			return array(
				'success' => false,
				'message' => __('Solicitação expirada ou não encontrada. Volte ao site e peça um novo código.', 'apollo-telegram'),
			);
		}

		// Re-send existing pending OTP if already generated for this request.
		$code = '';
		if (! empty($row->code_hash) && self::STEP_CODE_DELIVERED === (string) $row->step) {
			// Cannot recover plaintext from hash — mint a fresh code and overwrite.
		}

		$code      = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
		$code_hash = wp_hash_password($code);

		$updated = $wpdb->update(
			$table,
			array(
				'telegram_chat_id' => $chat_id,
				'code_hash'        => $code_hash,
				'step'             => self::STEP_CODE_DELIVERED,
				'updated_at'       => current_time('mysql', true),
			),
			array(
				'id'     => (int) $row->id,
				'status' => self::STATUS_PENDING,
			),
			array('%d', '%s', '%s', '%s'),
			array('%d', '%s')
		);

		if (false === $updated) {
			return array(
				'success' => false,
				'message' => __('Não foi possível gerar o código.', 'apollo-telegram'),
			);
		}

		$linked = get_option('apollo_telegram_linked_chats', array());
		if (! is_array($linked)) {
			$linked = array();
		}
		$linked[ $chat_id ] = array_merge(
			is_array($linked[ $chat_id ] ?? null) ? $linked[ $chat_id ] : array(),
			array(
				'phone'     => (string) $row->phone,
				'linked_at' => time(),
				'source'    => 'start_deep_link',
			)
		);
		update_option('apollo_telegram_linked_chats', $linked, false);

		apollo_telegram_debug_log(
			'start_code_delivered',
			array(
				'request_id' => $request_id,
				'chat_id'    => $chat_id,
				'phone'      => apollo_telegram_phone_log_suffix((string) $row->phone),
			)
		);

		// #region agent log
		$trail = get_option('apollo_tg_debug_start_trail', array());
		if (! is_array($trail)) {
			$trail = array();
		}
		$trail[] = array(
			'hypothesisId' => 'A',
			'event'        => 'start_code_delivered',
			'request_id'   => $request_id,
			'chat_id'      => $chat_id,
			'timestamp'    => time(),
		);
		update_option('apollo_tg_debug_start_trail', array_slice($trail, -20), false);
		// #endregion

		return array(
			'success' => true,
			'code'    => $code,
			'phone'   => (string) $row->phone,
			'message' => __('Código gerado.', 'apollo-telegram'),
		);
	}

	/**
	 * Bind Telegram contact share; generate and deliver code via bot reply text.
	 *
	 * @return array{success:bool,message?:string,code?:string}
	 */
	public static function bindContact(string $phone, int $chat_id, ?string $request_id = null): array
	{
		if (! RateLimiter::allow('contact_chat:' . $chat_id, 3, 10 * MINUTE_IN_SECONDS)) {
			return array(
				'success' => false,
				'message' => __('Limite de tentativas atingido. Aguarde alguns minutos.', 'apollo-telegram'),
			);
		}

		$normalized = apollo_telegram_normalize_phone($phone);
		if (null === $normalized) {
			return array(
				'success' => false,
				'message' => __('Contato inválido.', 'apollo-telegram'),
			);
		}

		global $wpdb;
		$table = self::table();

		if ($request_id) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE request_id = %s AND phone = %s AND status = %s AND expires_at > UTC_TIMESTAMP() LIMIT 1",
					$request_id,
					$normalized,
					self::STATUS_PENDING
				)
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE phone = %s AND status = %s AND expires_at > UTC_TIMESTAMP() ORDER BY id DESC LIMIT 1",
					$normalized,
					self::STATUS_PENDING
				)
			);
		}

		if (! $row) {
			return array(
				'success' => false,
				'message' => __('Esse número não está em processo de verificação no site.', 'apollo-telegram'),
			);
		}

		$code      = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
		$code_hash = wp_hash_password($code);

		$updated = $wpdb->update(
			self::table(),
			array(
				'telegram_chat_id' => $chat_id,
				'code_hash'        => $code_hash,
				'step'             => self::STEP_CODE_DELIVERED,
				'updated_at'       => current_time('mysql', true),
			),
			array(
				'id'     => (int) $row->id,
				'status' => self::STATUS_PENDING,
			),
			array('%d', '%s', '%s', '%s'),
			array('%d', '%s')
		);

		if (false === $updated) {
			return array(
				'success' => false,
				'message' => __('Não foi possível vincular o contato.', 'apollo-telegram'),
			);
		}

		apollo_telegram_debug_log(
			'code_delivered',
			array(
				'request_id' => $row->request_id,
				'phone'      => apollo_telegram_phone_log_suffix($normalized),
				'chat_id'    => $chat_id,
			)
		);

		// Every contact-shared chat becomes an "open chat" broadcast recipient.
		$linked = get_option('apollo_telegram_linked_chats', array());
		if (! is_array($linked)) {
			$linked = array();
		}
		$linked[$chat_id] = array_merge(
			is_array($linked[$chat_id] ?? null) ? $linked[$chat_id] : array(),
			array(
				'phone'     => $normalized,
				'linked_at' => time(),
				'source'    => 'phone_verification',
			)
		);
		update_option('apollo_telegram_linked_chats', $linked);

		return array(
			'success' => true,
			'code'    => $code,
			'message' => __('Código gerado.', 'apollo-telegram'),
		);
	}

	/**
	 * Verify 6-digit code from web UI.
	 *
	 * @return array{success:bool,message?:string,phone?:string}
	 */
	public static function verifyCode(string $request_id, string $phone, string $code): array
	{
		$normalized = apollo_telegram_normalize_phone($phone);
		$code       = preg_replace('/\D/', '', $code) ?? '';

		if (null === $normalized || 6 !== strlen($code)) {
			return array(
				'success' => false,
				'message' => __('Dados inválidos.', 'apollo-telegram'),
			);
		}

		$ip = RateLimiter::client_ip();
		if (! RateLimiter::allow('verify:' . $normalized . ':' . $ip, 5, 10 * MINUTE_IN_SECONDS)) {
			return array(
				'success' => false,
				'message' => __('Muitas tentativas. Aguarde 10 minutos.', 'apollo-telegram'),
				'code'    => 'rate_limited',
			);
		}

		global $wpdb;
		$table = self::table();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE request_id = %s AND phone = %s AND status = %s AND expires_at > UTC_TIMESTAMP() LIMIT 1",
				sanitize_text_field($request_id),
				$normalized,
				self::STATUS_PENDING
			)
		);

		if (! $row || empty($row->code_hash)) {
			apollo_telegram_debug_log(
				'verify_failed',
				array(
					'request_id' => $request_id,
					'reason'     => 'not_found_or_no_code',
				)
			);

			return array(
				'success' => false,
				'message' => __('Código inválido, expirado ou telefone não confirmado no Telegram.', 'apollo-telegram'),
			);
		}

		if (! wp_check_password($code, (string) $row->code_hash)) {
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET verify_attempts = verify_attempts + 1 WHERE id = %d",
					(int) $row->id
				)
			);

			apollo_telegram_debug_log(
				'verify_failed',
				array(
					'request_id' => $request_id,
					'reason'     => 'bad_code',
				)
			);

			return array(
				'success' => false,
				'message' => __('Código incorreto. Tente novamente.', 'apollo-telegram'),
			);
		}

		$updated = $wpdb->update(
			self::table(),
			array(
				'status'     => self::STATUS_VERIFIED,
				'step'       => 'verified',
				'updated_at' => current_time('mysql', true),
			),
			array(
				'id'     => (int) $row->id,
				'status' => self::STATUS_PENDING,
			),
			array('%s', '%s', '%s'),
			array('%d', '%s')
		);

		if (false === $updated) {
			return array(
				'success' => false,
				'message' => __('Não foi possível confirmar. Tente novamente.', 'apollo-telegram'),
			);
		}

		apollo_telegram_debug_log(
			'verify_success',
			array(
				'request_id' => $request_id,
				'phone'      => apollo_telegram_phone_log_suffix($normalized),
			)
		);

		return array(
			'success' => true,
			'message' => __('Telefone confirmado com sucesso!', 'apollo-telegram'),
			'phone'   => $normalized,
		);
	}

	/**
	 * Safe progress snapshot for the web UI (no code, no chat id).
	 *
	 * @return array{success:bool,step?:string,status?:string,message?:string}
	 */
	public static function getStatus(string $request_id, string $phone): array
	{
		$normalized = apollo_telegram_normalize_phone($phone);
		if (null === $normalized || ! wp_is_uuid(strtolower($request_id))) {
			return array(
				'success' => false,
				'message' => __('Dados inválidos.', 'apollo-telegram'),
			);
		}

		global $wpdb;
		$table = self::table();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT step, status, expires_at FROM {$table} WHERE request_id = %s AND phone = %s LIMIT 1",
				strtolower(sanitize_text_field($request_id)),
				$normalized
			)
		);

		if (! $row) {
			return array(
				'success' => false,
				'message' => __('Solicitação não encontrada.', 'apollo-telegram'),
			);
		}

		return array(
			'success' => true,
			'step'    => (string) $row->step,
			'status'  => (string) $row->status,
		);
	}

	/**
	 * Confirm a verified Telegram row is valid for registration submit.
	 *
	 * @return array{phone: string, telegram_chat_id: int|null}|null
	 */
	public static function assertVerifiedForRegistration(string $request_id, string $phone): ?array
	{
		$normalized = apollo_telegram_normalize_phone($phone);
		if (null === $normalized || ! wp_is_uuid(strtolower($request_id))) {
			return null;
		}

		global $wpdb;
		$table = self::table();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT phone, telegram_chat_id, updated_at FROM {$table}
				WHERE request_id = %s AND phone = %s AND status = %s
				AND updated_at > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 MINUTE)
				LIMIT 1",
				strtolower(sanitize_text_field($request_id)),
				$normalized,
				self::STATUS_VERIFIED
			)
		);

		if (! $row) {
			return null;
		}

		return array(
			'phone'            => (string) $row->phone,
			'telegram_chat_id' => ! empty($row->telegram_chat_id) ? (int) $row->telegram_chat_id : null,
		);
	}

	/**
	 * Whether normalized phone is already linked to a user account.
	 */
	public static function phoneAlreadyRegistered(string $normalized_phone): bool
	{
		global $wpdb;

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				'_apollo_phone',
				$normalized_phone
			)
		);

		return ! empty($existing);
	}

	/**
	 * Delete expired pending rows (cron).
	 */
	public static function cleanup_expired(): void
	{
		global $wpdb;

		$table = self::table();
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE status = %s AND expires_at < UTC_TIMESTAMP()",
				self::STATUS_PENDING
			)
		);
	}
}
