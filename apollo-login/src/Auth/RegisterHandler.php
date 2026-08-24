<?php

/**
 * Register Handler
 *
 * @package Apollo\Login
 */

declare(strict_types=1);

namespace Apollo\Login\Auth;

use Apollo\Login\Quiz\SimonGame;

// Prevent direct access.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Register Handler class
 */
class RegisterHandler
{


	/**
	 * Constructor
	 */
	public function __construct()
	{
		// AJAX registration handler — JS sends action:'apollo_register' to admin-ajax.php
		add_action('wp_ajax_nopriv_apollo_register', array($this, 'handle_ajax_register'));
		add_action('wp_ajax_apollo_register', array($this, 'handle_ajax_register'));

		add_filter('registration_errors', array($this, 'validate_quiz_completion'), 10, 3);
		add_filter('registration_errors', array($this, 'validate_cpf'), 15, 3);

		// NOTE: on_user_register is only for NON-AJAX registrations (e.g. wp-admin).
		// handle_ajax_register() manages its own meta/tokens/hooks to avoid double-processing.
		add_action('user_register', array($this, 'on_user_register'));

		// AJAX CPF validation (real-time, both logged-in and not)
		add_action('wp_ajax_nopriv_apollo_validate_cpf', array($this, 'ajax_validate_cpf'));
		add_action('wp_ajax_apollo_validate_cpf', array($this, 'ajax_validate_cpf'));

		// Client-side debug beacon (same-origin → debug-568f44.log when WP_DEBUG).
		add_action('wp_ajax_nopriv_apollo_auth_debug_log', array($this, 'handle_client_debug_log'));
		add_action('wp_ajax_apollo_auth_debug_log', array($this, 'handle_client_debug_log'));

		// Fallback verification email — wp_mail only when apollo-email did not send @10.
		add_action('apollo/login/verification_email', array($this, 'send_verification_email_fallback'), 99, 2);
	}

	/**
	 * Plain-text fallback when apollo-email template send failed or plugin inactive.
	 *
	 * @param int    $user_id    Registered user ID.
	 * @param string $verify_url Full verification URL with token.
	 * @return void
	 */
	public function send_verification_email_fallback(int $user_id, string $verify_url): void
	{
		if (\Apollo\Login\apollo_login_verification_email_was_sent()) {
			return;
		}

		$user = get_userdata($user_id);
		if (! $user) {
			return;
		}

		$to      = sanitize_email($user->user_email);
		$name    = sanitize_text_field((string) get_user_meta($user_id, '_apollo_social_name', true) ?: $user->display_name);
		$subject = __('Confirme seu e-mail — Apollo', 'apollo-login');

		/* translators: 1: user display name, 2: verification URL */
		$body = sprintf(
			__(
				"Olá %1\$s,\n\nConfirme seu endereço de e-mail clicando no link abaixo:\n\n%2\$s\n\nO link expira em 24 horas.\n\nApollo",
				'apollo-login'
			),
			$name,
			esc_url_raw($verify_url)
		);

		$headers = array('Content-Type: text/plain; charset=UTF-8');

		if (wp_mail($to, $subject, $body, $headers)) {
			\Apollo\Login\apollo_login_mark_verification_email_sent();
		} elseif (defined('WP_DEBUG') && WP_DEBUG) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				sprintf(
					'[apollo-login] verification wp_mail fallback failed for user #%d (%s)',
					$user_id,
					$to
				)
			);
		}
	}

	/**
	 * Receive client-side debug beacons (same-origin; WP_DEBUG only).
	 *
	 * @return void
	 */
	public function handle_client_debug_log(): void
	{
		if (! defined('WP_DEBUG') || ! WP_DEBUG) {
			wp_send_json_success(array('logged' => false));
		}

		$nonce_valid = check_ajax_referer('apollo_auth_nonce', 'nonce', false)
			|| check_ajax_referer('apollo_register_nonce', 'nonce', false);

		if (! $nonce_valid) {
			wp_send_json_error(array('code' => 'nonce_failed'), 403);
		}

		$location     = isset($_POST['location']) ? sanitize_text_field(wp_unslash((string) $_POST['location'])) : '';
		$message      = isset($_POST['message']) ? sanitize_text_field(wp_unslash((string) $_POST['message'])) : '';
		$hypothesisId = isset($_POST['hypothesisId']) ? sanitize_text_field(wp_unslash((string) $_POST['hypothesisId'])) : '';
		$data_raw     = isset($_POST['data']) ? wp_unslash((string) $_POST['data']) : '{}';
		$data         = json_decode($data_raw, true);

		if (! is_array($data)) {
			$data = array();
		}

		\Apollo\Login\apollo_login_debug_log($location, $message, $data, $hypothesisId);

		wp_send_json_success(array('logged' => true));
	}

	/**
	 * Handle AJAX registration from /acesso → aptitude quiz → FINALIZAR REGISTRO
	 *
	 * @return void
	 */
	public function handle_ajax_register(): void
	{
		// Verify nonce (accept auth + register nonces)
		$nonce_valid = check_ajax_referer('apollo_auth_nonce', 'nonce', false)
			|| check_ajax_referer('apollo_register_nonce', 'nonce', false);

		\Apollo\Login\apollo_login_debug_log(
			'RegisterHandler::handle_ajax_register',
			'ajax_register_entry',
			array(
				'nonce_valid'            => $nonce_valid,
				'users_can_register'     => (bool) get_option('users_can_register'),
				'has_nome'               => isset($_POST['nome']),
				'has_email'              => isset($_POST['email']),
				'quiz_passed_raw'        => isset($_POST['quiz_passed']) ? sanitize_text_field(wp_unslash((string) $_POST['quiz_passed'])) : '',
				'terms_accepted_raw'     => isset($_POST['terms_accepted']) ? sanitize_text_field(wp_unslash((string) $_POST['terms_accepted'])) : '',
				'instagram_len'          => isset($_POST['instagram']) ? strlen(sanitize_user(wp_unslash((string) $_POST['instagram']))) : 0,
			),
			'H2'
		);

		if (! $nonce_valid) {
			wp_send_json_error(
				array(
					'message' => __('Verificação de segurança falhou. Recarregue a página.', 'apollo-login'),
					'code'    => 'nonce_failed',
				),
				403
			);
		}

		// Check if registration is open
		if (! get_option('users_can_register')) {
			wp_send_json_error(
				array(
					'message' => __('Registro de novos usuários está desabilitado.', 'apollo-login'),
					'code'    => 'registration_disabled',
				),
				403
			);
		}

		$result = self::process_registration_from_request(wp_unslash($_POST));

		if (! $result['success']) {
			wp_send_json_error($result['data'], $result['status']);
		}

		wp_send_json_success($result['data']);
	}

	/**
	 * Shared registration pipeline for AJAX and REST entry points.
	 *
	 * @param array<string, mixed> $input Registration payload (POST fields or REST body).
	 * @return array{success: bool, status: int, data: array<string, mixed>}
	 */
	public static function process_registration_from_request(array $input): array
	{
		/*
		 * apollo-email register path (AJAX apollo_register or REST /apollo/v1/auth/register):
		 *   RegisterHandler::process_registration_from_request()
		 *   → wp_insert_user
		 *   → do_action( 'apollo/login/registered' )        → welcome (apollo_send_email)
		 *   → apollo_login_send_verification_email()
		 *   → do_action( 'apollo/login/verification_email' ) → verification template
		 */
		\Apollo\Login\apollo_login_debug_log(
			'RegisterHandler::process_registration_from_request',
			'pipeline_entry',
			array(
				'keys'               => array_keys($input),
				'nome_len'           => isset($input['nome']) ? mb_strlen((string) $input['nome']) : 0,
				'email'              => isset($input['email']) ? sanitize_email((string) $input['email']) : '',
				'instagram'          => isset($input['instagram']) ? sanitize_user(ltrim((string) $input['instagram'], '@')) : '',
				'doc_type'           => isset($input['doc_type']) ? sanitize_text_field((string) $input['doc_type']) : '',
				'cpf_len'            => isset($input['cpf']) ? strlen(preg_replace('/[^0-9]/', '', (string) $input['cpf'])) : 0,
				'passport_len'       => isset($input['passport']) ? strlen((string) $input['passport']) : 0,
				'quiz_passed'        => ! empty($input['quiz_passed']),
				'terms_accepted'     => ! empty($input['terms_accepted']),
				'sounds_count'       => isset($input['sounds']) ? count((array) $input['sounds']) : 0,
			),
			'H1'
		);

		// Sanitize input
		$social_name = isset($input['nome']) ? sanitize_text_field($input['nome']) : '';
		$instagram   = isset($input['instagram']) ? sanitize_user(ltrim($input['instagram'], '@')) : '';
		$email       = isset($input['email']) ? sanitize_email($input['email']) : '';
		$password    = isset($input['senha']) ? $input['senha'] : '';
		$doc_type    = isset($input['doc_type']) ? sanitize_text_field($input['doc_type']) : '';
		$cpf         = isset($input['cpf']) ? preg_replace('/[^0-9]/', '', sanitize_text_field($input['cpf'])) : '';
		$passport    = isset($input['passport']) ? sanitize_text_field(strtoupper($input['passport'])) : '';
		$passport_co = isset($input['passport_country']) ? sanitize_text_field($input['passport_country']) : '';

		if ('' === $doc_type && ! empty($cpf)) {
			$doc_type = 'cpf';
		} elseif ('' === $doc_type && ! empty($passport)) {
			$doc_type = 'passport';
		}
		$clubber_universe = isset($input['clubber_universe']) ? sanitize_text_field($input['clubber_universe']) : '';
		$sounds      = isset($input['sounds']) ? array_map('sanitize_text_field', (array) $input['sounds']) : array();
		$terms       = ! empty($input['terms_accepted']);
		$marketing   = ! empty($input['marketing_opt_in']);
		$quiz_passed = ! empty($input['quiz_passed']);
		$quiz_token_raw = isset($input['apollo_quiz_token']) ? sanitize_text_field((string) $input['apollo_quiz_token']) : '';
		$party_role  = isset($input['party_role']) ? sanitize_text_field((string) $input['party_role']) : '';
		$birth_result = \Apollo\Login\apollo_login_parse_birth_date_from_input($input);
		$phone_raw          = isset($input['phone']) ? sanitize_text_field((string) $input['phone']) : '';
		$phone_request_id   = isset($input['phone_request_id']) ? sanitize_text_field((string) $input['phone_request_id']) : '';
		$phone_verified     = ! empty($input['phone_verified']);
		$normalized_phone   = null;
		$telegram_chat_id   = null;

		// --- Validation ---
		$errors = array();

		// Social name
		if (empty($social_name) || mb_strlen($social_name) < 2) {
			$errors[] = __('Nome social deve ter pelo menos 2 caracteres.', 'apollo-login');
		}

		// Instagram (becomes WordPress username)
		if (empty($instagram)) {
			$errors[] = __('Instagram é obrigatório.', 'apollo-login');
		} elseif (! preg_match('/^[a-z0-9._]{1,30}$/', strtolower($instagram))) {
			$errors[] = __('Instagram inválido. Use apenas letras, números, ponto e underscore.', 'apollo-login');
		} elseif (username_exists($instagram)) {
			$errors[] = __('Este Instagram já está registrado.', 'apollo-login');
		}

		// Email
		$email_conflict = null;
		if (empty($email) || ! is_email($email)) {
			$errors[] = __('E-mail inválido.', 'apollo-login');
		} elseif (email_exists($email)) {
			$existing_id    = (int) email_exists($email);
			$email_conflict = \Apollo\Login\apollo_login_email_registration_conflict($existing_id);
			if (defined('WP_DEBUG') && WP_DEBUG) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log(sprintf('[apollo-login] registration: email_exists hit for existing_id=%d conflict_code=%s', $existing_id, $email_conflict['code'] ?? 'unknown'));
			}
			$errors[] = $email_conflict['message'];
		}

		// Password
		if (empty($password) || strlen($password) < 8) {
			$errors[] = __('Senha deve ter pelo menos 8 caracteres.', 'apollo-login');
		}

		// Birthday (18+)
		if (empty($birth_result['valid'])) {
			$errors[] = $birth_result['error'] ?: __('Data de nascimento inválida.', 'apollo-login');
		}

		// Party personality
		$allowed_party_roles = array_keys(\Apollo\Login\apollo_login_party_roles());
		if (empty($party_role) || ! in_array($party_role, $allowed_party_roles, true)) {
			$errors[] = __('Selecione como você é em uma festa.', 'apollo-login');
		}

		// Phone (Telegram verification required in production; local bypass via filter)
		$bypass_phone = apply_filters(
			'apollo_login_bypass_phone_verification',
			function_exists('wp_get_environment_type') && 'local' === wp_get_environment_type()
		);

		if (empty($phone_raw) || ! $phone_verified) {
			$errors[] = __('Confirme seu telefone via Telegram antes de finalizar.', 'apollo-login');
		} elseif ($bypass_phone) {
			$normalized_phone = function_exists('apollo_telegram_normalize_phone')
				? apollo_telegram_normalize_phone($phone_raw)
				: null;
			if (null === $normalized_phone) {
				$errors[] = __('Telefone inválido.', 'apollo-login');
			} elseif (
				class_exists(\Apollo\Telegram\Services\VerificationService::class)
				&& \Apollo\Telegram\Services\VerificationService::phoneAlreadyRegistered($normalized_phone)
			) {
				$errors[] = __('Este telefone já está registrado.', 'apollo-login');
			}
		} elseif (! class_exists(\Apollo\Telegram\Services\VerificationService::class)) {
			$errors[] = __('Verificação de telefone indisponível. Tente novamente mais tarde.', 'apollo-login');
		} else {
			$verified = \Apollo\Telegram\Services\VerificationService::assertVerifiedForRegistration(
				$phone_request_id,
				$phone_raw
			);
			if (null === $verified) {
				$errors[] = __('Telefone não verificado via Telegram ou verificação expirada.', 'apollo-login');
			} elseif (\Apollo\Telegram\Services\VerificationService::phoneAlreadyRegistered($verified['phone'])) {
				$errors[] = __('Este telefone já está registrado.', 'apollo-login');
			} else {
				$normalized_phone = $verified['phone'];
				$telegram_chat_id = $verified['telegram_chat_id'] ?? null;
			}
		}

		// Document validation
		if ('' === $doc_type) {
			$errors[] = __('Documento é obrigatório.', 'apollo-login');
		} elseif ('cpf' === $doc_type) {
			if (empty($cpf) || ! \Apollo\Login\apollo_validate_cpf($cpf)) {
				$errors[] = __('CPF inválido.', 'apollo-login');
			} else {
				if (self::cpf_exists($cpf)) {
					$errors[] = __('Este CPF já está registrado.', 'apollo-login');
				}
			}
		} elseif ('passport' === $doc_type) {
			if (empty($passport) || ! \Apollo\Login\apollo_validate_passport($passport)) {
				$errors[] = __('Número de passaporte inválido.', 'apollo-login');
			}
		}

		// Clubber universe — optional (profile can be completed later).
		$allowed_universes = array('underground', 'both', 'mainstream');
		if ($clubber_universe === '' || ! in_array($clubber_universe, $allowed_universes, true)) {
			$clubber_universe = 'both';
		}

		// Sounds — optional at signup (no DJ/genre gate on registration).
		if (! empty($sounds)) {
			if (count($sounds) > 5) {
				$errors[] = __('Máximo de 5 gêneros musicais.', 'apollo-login');
			} else {
				$allowed_slugs = \Apollo\Login\apollo_login_sounds_catalog_slugs();
				if (! empty($allowed_slugs)) {
					foreach ($sounds as $sound_slug) {
						if (! in_array($sound_slug, $allowed_slugs, true)) {
							$errors[] = __('Gênero musical inválido na seleção.', 'apollo-login');
							break;
						}
					}
				}
			}
		}

		// Terms
		if (! $terms) {
			$errors[] = __('Você deve aceitar os termos de uso.', 'apollo-login');
		}

		// Quiz
		if (! $quiz_passed) {
			$errors[] = __('Teste de aptidão é obrigatório.', 'apollo-login');
		}

		if (empty($quiz_token_raw)) {
			$errors[] = __('Token do teste de aptidão é obrigatório.', 'apollo-login');
		} elseif (false === get_transient('apollo_quiz_' . $quiz_token_raw)) {
			$errors[] = __('Teste de aptidão expirado ou inválido. Refaça o teste.', 'apollo-login');
		}

		if (! empty($errors)) {
			\Apollo\Login\apollo_login_debug_log(
				'RegisterHandler::process_registration_from_request',
				'validation_failed',
				array(
					'errors'       => $errors,
					'code'         => 'validation_failed',
					'doc_type'     => $doc_type,
					'cpf_len'      => strlen($cpf),
					'passport_len' => strlen($passport),
					'email_conflict_code' => $email_conflict['code'] ?? null,
				),
				'H1'
			);

			$fail_data = array(
				'message' => implode(' ', $errors),
				'errors'  => $errors,
				'code'    => 'validation_failed',
			);
			if (is_array($email_conflict)) {
				$fail_data['email_conflict_code'] = $email_conflict['code'];
				$fail_data['action_url']          = $email_conflict['action_url'];
			}

			return array(
				'success' => false,
				'status'  => 400,
				'data'    => $fail_data,
			);
		}

		// --- Create User ---
		\Apollo\Login\apollo_login_debug_log(
			'RegisterHandler::process_registration_from_request',
			'before_wp_insert_user',
			array(
				'user_login' => strtolower($instagram),
				'email'      => $email,
			),
			'H3'
		);

		$user_id = wp_insert_user(
			array(
				'user_login'   => strtolower($instagram),
				'user_email'   => $email,
				'user_pass'    => $password,
				'display_name' => $social_name,
				'role'         => 'subscriber',
			)
		);

		if (is_wp_error($user_id)) {
			\Apollo\Login\apollo_login_debug_log(
				'RegisterHandler::process_registration_from_request',
				'wp_insert_user_failed',
				array(
					'code'    => $user_id->get_error_code(),
					'message' => $user_id->get_error_message(),
				),
				'H3'
			);

			return array(
				'success' => false,
				'status'  => 500,
				'data'    => array(
					'message' => $user_id->get_error_message(),
					'code'    => 'creation_failed',
				),
			);
		}

		\Apollo\Login\apollo_login_debug_log(
			'RegisterHandler::process_registration_from_request',
			'wp_insert_user_ok',
			array('user_id' => (int) $user_id),
			'H3'
		);

		// Flag: prevent on_user_register() from double-processing
		update_user_meta($user_id, '_apollo_registered_via_ajax', true);

		// --- Save User Meta ---
		update_user_meta($user_id, '_apollo_social_name', $social_name);
		update_user_meta($user_id, '_apollo_instagram', strtolower($instagram));
		update_user_meta($user_id, '_apollo_sound_preferences', $sounds);
		update_user_meta(
			$user_id,
			'_apollo_quiz_answers',
			array(
				'clubber_universe' => $clubber_universe,
				'terms_accepted'   => $terms ? 1 : 0,
				'sounds_selected'  => array_values($sounds),
				'birth_date'       => $birth_result['date'],
				'zodiac_sign'      => $birth_result['zodiac'],
				'party_role'       => $party_role,
				'phone'            => $normalized_phone,
			)
		);
		apollo_membership_assign($user_id, array('nao-verificado'));
		update_user_meta($user_id, '_apollo_email_verified', false);
		update_user_meta($user_id, '_apollo_login_attempts', 0);
		update_user_meta(
			$user_id,
			'_apollo_email_prefs',
			array(
				'transactional'    => true,
				'marketing'        => $marketing,
				'digest'           => $marketing,
				'gestor_reminders' => true,
			)
		);

		// Document
		update_user_meta($user_id, '_apollo_doc_type', $doc_type);
		if ('cpf' === $doc_type && ! empty($cpf)) {
			update_user_meta($user_id, '_apollo_cpf', $cpf);
		} elseif ('passport' === $doc_type && ! empty($passport)) {
			update_user_meta($user_id, '_apollo_passport', $passport);
			update_user_meta($user_id, '_apollo_passport_country', $passport_co);
		}

		update_user_meta($user_id, APOLLO_META_BIRTH_DATE, $birth_result['date']);
		update_user_meta($user_id, APOLLO_META_ZODIAC_SIGN, $birth_result['zodiac']);
		update_user_meta($user_id, APOLLO_META_PARTY_ROLE, $party_role);

		if (! empty($normalized_phone)) {
			update_user_meta($user_id, APOLLO_META_PHONE, $normalized_phone);
			if (! empty($telegram_chat_id)) {
				update_user_meta($user_id, APOLLO_META_TELEGRAM_CHAT_ID, (int) $telegram_chat_id);
			}
		}

		// --- Email Verification ---
		$token = \Apollo\Login\apollo_generate_verification_token($user_id);

		// --- Persist quiz transient (scores, DB rows, Simon) ---
		if (! empty($quiz_token_raw)) {
			self::persist_quiz_transient_for_user((int) $user_id, $quiz_token_raw);
		}

		$verify_url = add_query_arg(
			array(
				'user'  => $user_id,
				'token' => $token,
			),
			home_url('/verificar-email/')
		);

		\Apollo\Login\apollo_login_debug_log(
			'RegisterHandler::process_registration_from_request',
			'before_apollo_login_registered',
			array('user_id' => (int) $user_id),
			'H5'
		);

		do_action(
			'apollo/login/registered',
			$user_id,
			array(
				'social_name' => $social_name,
			)
		);

		\Apollo\Login\apollo_login_debug_log(
			'RegisterHandler::process_registration_from_request',
			'after_apollo_login_registered',
			array('user_id' => (int) $user_id),
			'H5'
		);

		$email_sent = \Apollo\Login\apollo_login_send_verification_email($user_id, $verify_url);

		\Apollo\Login\apollo_login_debug_log(
			'RegisterHandler::process_registration_from_request',
			'after_verification_email_dispatch',
			array(
				'user_id'    => (int) $user_id,
				'email_sent' => $email_sent,
			),
			'H5'
		);

		// --- Auto-login ---
		wp_set_current_user($user_id);
		wp_set_auth_cookie($user_id, false);

		$redirect = apply_filters(
			'apollo_login_registration_redirect',
			home_url('/verificar-email/?pending=1'),
			$user_id
		);

		return array(
			'success' => true,
			'status'  => 200,
			'data'    => array_merge(
				array(
					'message'    => $email_sent
						? __('Cadastro realizado! Enviamos um e-mail de confirmação — verifique sua caixa de entrada (e o spam).', 'apollo-login')
						: __('Cadastro realizado, mas não conseguimos enviar o e-mail agora. Use o reenvio na próxima tela.', 'apollo-login'),
					'redirect'   => $redirect,
					'user_id'    => $user_id,
					'email_sent' => $email_sent,
					'email_verified' => false,
				),
				\Apollo\Login\apollo_login_is_local_dev()
					? array_merge(
						array( 'dev_verify_url' => $verify_url ),
						\Apollo\Login\apollo_login_smtp_is_live()
							? array(
								'local_mail_hint' => __('Verifique sua caixa de entrada e a pasta de spam.', 'apollo-login'),
							)
							: array(
								'local_mail_hint' => __('Ambiente local: o e-mail vai para o Mailpit do Local (não para Gmail). Use o link na tela ou abra Mailpit no app Local.', 'apollo-login'),
							)
					)
					: array()
			),
		);
	}

	/**
	 * Validate quiz completion before registration
	 *
	 * @param \WP_Error $errors               Error object.
	 * @param string    $sanitized_user_login Username.
	 * @param string    $user_email           Email.
	 * @return \WP_Error
	 */
	public function validate_quiz_completion(\WP_Error $errors, string $sanitized_user_login, string $user_email): \WP_Error
	{
		// Check if quiz completion is stored in session/transient
		$quiz_token = $_POST['apollo_quiz_token'] ?? '';

		if (empty($quiz_token)) {
			$errors->add(
				'quiz_required',
				__('<strong>ERROR</strong>: You must complete the aptitude quiz before registering.', 'apollo-login')
			);
			return $errors;
		}

		// Verify quiz token
		$quiz_data = get_transient('apollo_quiz_' . $quiz_token);

		if (false === $quiz_data) {
			$errors->add(
				'quiz_expired',
				__('<strong>ERROR</strong>: Quiz results expired. Please complete the quiz again.', 'apollo-login')
			);
			return $errors;
		}

		// Check if all 4 stages completed
		$required_stages = array('pattern', 'simon', 'ethics', 'reaction');
		foreach ($required_stages as $stage) {
			if (! isset($quiz_data[$stage])) {
				$errors->add(
					'quiz_incomplete',
					sprintf(
						__('<strong>ERROR</strong>: Quiz stage "%s" not completed.', 'apollo-login'),
						$stage
					)
				);
			}
		}

		return $errors;
	}

	/**
	 * Validate CPF during registration
	 *
	 * Full validation chain:
	 * 1. Strip non-digits
	 * 2. Check 11-digit length
	 * 3. Reject all-same-digit sequences (000…, 111…, etc.)
	 * 4. Mod-11 check-digit algorithm (Receita Federal standard)
	 * 5. Uniqueness — reject if CPF already registered to another user
	 *
	 * @param \WP_Error $errors               Error object.
	 * @param string    $sanitized_user_login Username.
	 * @param string    $user_email           Email.
	 * @return \WP_Error
	 */
	public function validate_cpf(\WP_Error $errors, string $sanitized_user_login, string $user_email): \WP_Error
	{
		$doc_type = isset($_POST['doc_type']) ? sanitize_text_field(wp_unslash($_POST['doc_type'])) : 'cpf';

		// Only validate when document type is CPF
		if ('cpf' !== $doc_type) {
			return $errors;
		}

		$raw_cpf = isset($_POST['cpf']) ? sanitize_text_field(wp_unslash($_POST['cpf'])) : '';
		$cpf     = preg_replace('/[^0-9]/', '', $raw_cpf);

		// Required check
		if (empty($cpf)) {
			$errors->add(
				'cpf_required',
				__('<strong>ERRO</strong>: CPF é obrigatório.', 'apollo-login')
			);
			return $errors;
		}

		// Validate format + check digits via helper function
		if (! \Apollo\Login\apollo_validate_cpf($cpf)) {
			$errors->add(
				'cpf_invalid',
				__('<strong>ERRO</strong>: CPF inválido. Verifique os números digitados.', 'apollo-login')
			);
			return $errors;
		}

		// Uniqueness — check if CPF already belongs to another user
		if (self::cpf_exists($cpf)) {
			$errors->add(
				'cpf_exists',
				__('<strong>ERRO</strong>: Este CPF já está registrado.', 'apollo-login')
			);
			return $errors;
		}

		return $errors;
	}

	/**
	 * Persist quiz transient data for a newly registered user.
	 */
	private static function persist_quiz_transient_for_user(int $user_id, string $quiz_token): void
	{
		$quiz_data = get_transient('apollo_quiz_' . $quiz_token);
		if (false === $quiz_data || ! is_array($quiz_data)) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . APOLLO_LOGIN_TABLE_QUIZ_RESULTS;

		foreach ($quiz_data as $stage => $data) {
			if ('_ip' === $stage || ! is_array($data)) {
				continue;
			}

			if ('simon' === $stage) {
				SimonGame::save_score(
					$user_id,
					(int) ($data['level'] ?? 0),
					(array) ($data['sequence'] ?? array()),
					(bool) ($data['success'] ?? false)
				);
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'user_id'      => $user_id,
					'stage'        => (string) $stage,
					'score'        => (int) ($data['score'] ?? 0),
					'answers'      => wp_json_encode($data['answers'] ?? array()),
					'completed_at' => current_time('mysql'),
				),
				array('%d', '%s', '%d', '%s', '%s')
			);
		}

		$stage_scores = array();
		foreach ($quiz_data as $stage => $data) {
			if ('_ip' === $stage || ! is_array($data)) {
				continue;
			}
			$stage_scores[] = (int) ($data['score'] ?? 0);
		}

		if (! empty($stage_scores)) {
			update_user_meta($user_id, APOLLO_META_QUIZ_SCORE, array_sum($stage_scores));
		}

		delete_transient('apollo_quiz_' . $quiz_token);
	}

	/**
	 * Check if a CPF already exists in the database
	 *
	 * @param string $cpf Clean CPF (11 digits).
	 * @return bool
	 */
	private static function cpf_exists(string $cpf): bool
	{
		global $wpdb;

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '_apollo_cpf' AND meta_value = %s LIMIT 1",
				$cpf
			)
		);

		return ! empty($existing);
	}

	/**
	 * AJAX handler — real-time CPF validation
	 *
	 * Performs full validation (format + algorithm + uniqueness) and returns
	 * a structured JSON response for the frontend to display live feedback.
	 *
	 * @return void
	 */
	public function ajax_validate_cpf(): void
	{
		// Accept both nonce names for flexibility
		$nonce_valid = check_ajax_referer('apollo_auth_nonce', 'nonce', false)
			|| check_ajax_referer('apollo_register_nonce', 'nonce', false);

		if (! $nonce_valid) {
			wp_send_json_error(array('message' => __('Sessão expirada.', 'apollo-login')), 403);
		}

		$raw_cpf = isset($_POST['cpf']) ? sanitize_text_field(wp_unslash($_POST['cpf'])) : '';
		$cpf     = preg_replace('/[^0-9]/', '', $raw_cpf);

		// Step 1 — length
		if (strlen($cpf) !== 11) {
			wp_send_json_error(
				array(
					'message' => __('CPF deve conter 11 dígitos.', 'apollo-login'),
					'code'    => 'cpf_length',
				)
			);
		}

		// Step 2 — all-same-digit reject
		if (preg_match('/^(\d)\1{10}$/', $cpf)) {
			wp_send_json_error(
				array(
					'message' => __('CPF inválido — sequência rejeitada.', 'apollo-login'),
					'code'    => 'cpf_sequence',
				)
			);
		}

		// Step 3 — Mod-11 algorithm
		if (! \Apollo\Login\apollo_validate_cpf($cpf)) {
			wp_send_json_error(
				array(
					'message' => __('CPF inválido — dígitos verificadores incorretos.', 'apollo-login'),
					'code'    => 'cpf_checkdigit',
				)
			);
		}

		// Step 4 — uniqueness
		if (self::cpf_exists($cpf)) {
			wp_send_json_error(
				array(
					'message' => __('Este CPF já está registrado.', 'apollo-login'),
					'code'    => 'cpf_exists',
				)
			);
		}

		// All checks passed
		wp_send_json_success(
			array(
				'message' => __('CPF válido e disponível.', 'apollo-login'),
				'code'    => 'cpf_valid',
			)
		);
	}

	/**
	 * Handle user registration (non-AJAX only, e.g. wp-admin user creation)
	 *
	 * AJAX registrations are fully handled by handle_ajax_register() which
	 * sets a _apollo_registered_via_ajax flag to prevent double-processing.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function on_user_register(int $user_id): void
	{
		// Skip if already processed by handle_ajax_register()
		if (get_user_meta($user_id, '_apollo_registered_via_ajax', true)) {
			delete_user_meta($user_id, '_apollo_registered_via_ajax');
			return;
		}

		// Get quiz token
		$quiz_token = $_POST['apollo_quiz_token'] ?? '';

		if (empty($quiz_token)) {
			return;
		}

		// Get quiz data
		$quiz_data = get_transient('apollo_quiz_' . $quiz_token);

		if (false === $quiz_data) {
			return;
		}

		// Save quiz results to database
		global $wpdb;
		$table = $wpdb->prefix . \APOLLO_LOGIN_TABLE_QUIZ_RESULTS;

		foreach ($quiz_data as $stage => $data) {
			$wpdb->insert(
				$table,
				array(
					'user_id'      => $user_id,
					'stage'        => $stage,
					'score'        => $data['score'] ?? 0,
					'answers'      => wp_json_encode($data['answers'] ?? array()),
					'completed_at' => current_time('mysql'),
				),
				array('%d', '%s', '%d', '%s', '%s')
			);
		}

		// Calculate total score
		$total_score = array_sum(array_column($quiz_data, 'score'));
		update_user_meta($user_id, '_apollo_quiz_score', $total_score);

		// Store quiz answers
		update_user_meta($user_id, '_apollo_quiz_answers', $quiz_data);

		// Set default membership
		apollo_membership_assign($user_id, array('nao-verificado'));

		// Email not verified yet
		update_user_meta($user_id, '_apollo_email_verified', false);

		// Save CPF or Passport (document identity)
		$doc_type = isset($_POST['doc_type']) ? sanitize_text_field(wp_unslash($_POST['doc_type'])) : 'cpf';
		update_user_meta($user_id, '_apollo_doc_type', $doc_type);

		if ('cpf' === $doc_type) {
			$cpf = isset($_POST['cpf']) ? preg_replace('/[^0-9]/', '', sanitize_text_field(wp_unslash($_POST['cpf']))) : '';
			if (! empty($cpf) && \Apollo\Login\apollo_validate_cpf($cpf)) {
				update_user_meta($user_id, '_apollo_cpf', $cpf);
			}
		} elseif ('passport' === $doc_type) {
			$passport         = isset($_POST['passport']) ? sanitize_text_field(wp_unslash($_POST['passport'])) : '';
			$passport_country = isset($_POST['passport_country']) ? sanitize_text_field(wp_unslash($_POST['passport_country'])) : '';
			if (! empty($passport)) {
				update_user_meta($user_id, '_apollo_passport', strtoupper($passport));
				update_user_meta($user_id, '_apollo_passport_country', $passport_country);
			}
		}

		// Save social name (TRANS/QUEER INCLUSIVE)
		$social_name = isset($_POST['social_name']) ? sanitize_text_field($_POST['social_name']) : '';
		if (! empty($social_name)) {
			update_user_meta($user_id, '_apollo_social_name', $social_name);
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => $social_name,
				)
			);
		}

		// Save Instagram username
		$instagram = isset($_POST['instagram_username']) ? sanitize_user($_POST['instagram_username']) : '';
		if (! empty($instagram)) {
			update_user_meta($user_id, '_apollo_instagram', $instagram);

			// Fetch Instagram profile picture on first registration
			// User can edit/update later in profile settings
		}

		// Save sound preferences
		$sounds = isset($_POST['sounds']) ? (array) $_POST['sounds'] : array();
		if (! empty($sounds)) {
			// Sanitize sound preferences
			$valid_sounds = array_map('sanitize_text_field', $sounds);
			// Save to user meta (for matchmaking)
			update_user_meta($user_id, '_apollo_sound_preferences', $valid_sounds);
		}

		// Generate verification token and send email
		$token = \Apollo\Login\apollo_generate_verification_token($user_id);

		$verify_url = add_query_arg(
			array(
				'user'  => $user_id,
				'token' => $token,
			),
			home_url('/verificar-email/')
		);

		/**
		 * Fires after a new user registers — triggers apollo-email welcome email.
		 *
		 * @since 1.0.0
		 * @param int   $user_id User ID.
		 * @param array $data    Registration data.
		 */
		do_action(
			'apollo/login/registered',
			$user_id,
			array(
				'social_name' => $social_name,
			)
		);

		/**
		 * Fires to request a verification email — triggers apollo-email if active.
		 *
		 * @since 1.0.0
		 * @param int    $user_id    User ID.
		 * @param string $verify_url Verification URL.
		 */
		\Apollo\Login\apollo_login_send_verification_email($user_id, $verify_url);

		// Delete quiz transient
		delete_transient('apollo_quiz_' . $quiz_token);
	}

	/**
	 * Fetch Instagram profile picture and save as avatar
	 *
	 * @param int    $user_id  User ID.
	 * @param string $username Instagram username.
	 * @return void
	 */
	private function fetch_instagram_avatar(int $user_id, string $username): void
	{
		if (empty($username)) {
			return;
		}

		$pic_url = $this->get_instagram_profile_pic($username);

		if (! $pic_url) {
			return;
		}

		// Require WordPress media functions
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download image
		$attachment_id = media_sideload_image($pic_url, 0, null, 'id');

		if (is_wp_error($attachment_id)) {
			// Fallback: just save the URL
			update_user_meta($user_id, '_apollo_avatar_url', $pic_url);
			return;
		}

		// Save attachment ID and URL
		$local_url = wp_get_attachment_url($attachment_id);
		update_user_meta($user_id, '_apollo_avatar_attachment_id', $attachment_id);
		update_user_meta($user_id, '_apollo_avatar_url', $local_url);

		// Get relative path for UsersWP compatibility
		$upload_dir = wp_upload_dir();
		$relative   = str_replace($upload_dir['baseurl'], '', $local_url);
		update_user_meta($user_id, 'avatar_thumb', $relative);
	}

	/**
	 * Get Instagram profile picture URL
	 *
	 * @param string $username Instagram username.
	 * @return string|null Profile picture URL or null on failure.
	 */
	private function get_instagram_profile_pic(string $username): ?string
	{
		// wp_remote_get: WP HTTP API handles SSL (bundled CA), no cURL extension required.
		$url      = 'https://www.instagram.com/' . rawurlencode($username) . '/';
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 10,
				'user-agent' => 'facebookexternalhit/1.1',
				'sslverify'  => true,
			)
		);

		if (is_wp_error($response)) {
			return null;
		}

		$http_code = (int) wp_remote_retrieve_response_code($response);
		if (200 !== $http_code) {
			return null;
		}

		$html = wp_remote_retrieve_body($response);
		if (empty($html)) {
			return null;
		}

		// Try to extract profile pic from shared data
		if (preg_match('/<script type="text\/javascript">window\._sharedData = (.*);<\/script>/', $html, $matches)) {
			$data = json_decode($matches[1], true);
			if (isset($data['entry_data']['ProfilePage'][0]['graphql']['user']['profile_pic_url_hd'])) {
				return $data['entry_data']['ProfilePage'][0]['graphql']['user']['profile_pic_url_hd'];
			}
		}

		// Fallback: try meta og:image
		if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $matches)) {
			return $matches[1];
		}

		return null;
	}
}
