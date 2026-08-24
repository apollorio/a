<?php
/**
 * Password Reset Form — matches /verificar-email auth card styling.
 *
 * @package Apollo\Login
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$token   = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

$recovery_url = add_query_arg( 'quero', 'recuperar-chave', \Apollo\Login\apollo_login_canonical_login_url() );
$login_url    = \Apollo\Login\apollo_login_canonical_login_url();
?>

<div class="apollo-email-verification apollo-password-reset" data-apollo-reset-form>

<?php if ( $token && $user_id > 0 ) : ?>
	<?php
	$validation = \Apollo\Login\apollo_login_validate_password_reset_token( $user_id, $token );
	$user       = get_userdata( $user_id );
	?>

	<?php if ( 'expired' === $validation ) : ?>
		<div class="apollo-verification-status">
			<div class="apollo-verification-icon" aria-hidden="true"><i class="ri-time-line"></i></div>
			<h2 class="apollo-verification-title"><?php esc_html_e( 'Link expirado', 'apollo-login' ); ?></h2>
			<p class="apollo-verification-lead"><?php esc_html_e( 'Este link de recuperação expirou. Solicite um novo envio.', 'apollo-login' ); ?></p>
			<a href="<?php echo esc_url( $recovery_url ); ?>" class="btn-primary apollo-verification-submit" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;">
				<span><?php esc_html_e( 'Solicitar novo link', 'apollo-login' ); ?></span>
				<i class="ri-refresh-line" aria-hidden="true"></i>
			</a>
		</div>

	<?php elseif ( 'invalid' === $validation || ! $user ) : ?>
		<div class="apollo-verification-status">
			<div class="apollo-verification-icon" aria-hidden="true"><i class="ri-shield-keyhole-line"></i></div>
			<h2 class="apollo-verification-title"><?php esc_html_e( 'Link inválido', 'apollo-login' ); ?></h2>
			<p class="apollo-verification-lead"><?php esc_html_e( 'Este link não é válido ou já foi utilizado.', 'apollo-login' ); ?></p>
			<a href="<?php echo esc_url( $recovery_url ); ?>" class="btn-primary apollo-verification-submit" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;">
				<span><?php esc_html_e( 'Solicitar novo link', 'apollo-login' ); ?></span>
				<i class="ri-mail-send-line" aria-hidden="true"></i>
			</a>
		</div>

	<?php else : ?>
		<div class="apollo-verification-status apollo-verification-status--pending">
			<div class="apollo-verification-icon" aria-hidden="true"><i class="ri-lock-password-line"></i></div>
			<h2 class="apollo-verification-title"><?php esc_html_e( 'Nova chave de acesso', 'apollo-login' ); ?></h2>
			<p class="apollo-verification-lead">
				<?php
				printf(
					/* translators: %s: user display name */
					esc_html__( 'Olá, %s. Defina sua nova senha abaixo.', 'apollo-login' ),
					esc_html( $user->display_name ?: $user->user_login )
				);
				?>
			</p>
			<p class="apollo-verification-hint"><?php esc_html_e( 'O link expira em 1 hora. Use pelo menos 8 caracteres.', 'apollo-login' ); ?></p>

			<form id="apollo-reset-confirm-form" method="post" class="apollo-verification-form" novalidate>
				<?php wp_nonce_field( 'apollo_reset_confirm_action', 'apollo_reset_nonce' ); ?>
				<input type="hidden" name="token" value="<?php echo esc_attr( $token ); ?>">
				<input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $user_id ); ?>">

				<div class="input-group">
					<div class="input-icon-wrap">
						<span class="input-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
						<input
							type="password"
							id="new_password"
							name="new_password"
							placeholder=" "
							class="apollo-input"
							autocomplete="new-password"
							minlength="8"
							required
						>
						<label class="apollo-label" for="new_password"><?php esc_html_e( 'Nova senha', 'apollo-login' ); ?></label>
					</div>
				</div>

				<div class="input-group">
					<div class="input-icon-wrap">
						<span class="input-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
						<input
							type="password"
							id="new_password_confirm"
							name="new_password_confirm"
							placeholder=" "
							class="apollo-input"
							autocomplete="new-password"
							minlength="8"
							required
						>
						<label class="apollo-label" for="new_password_confirm"><?php esc_html_e( 'Confirmar senha', 'apollo-login' ); ?></label>
					</div>
				</div>

				<button type="submit" class="btn-primary apollo-verification-submit" id="reset-confirm-submit">
					<span><?php esc_html_e( 'Salvar nova senha', 'apollo-login' ); ?></span>
					<i class="ri-check-line" aria-hidden="true"></i>
				</button>
			</form>

			<div id="reset-confirm-feedback" class="apollo-verification-feedback" aria-live="polite"></div>
		</div>

		<script>
		(function () {
			'use strict';

			var form = document.getElementById('apollo-reset-confirm-form');
			if (!form) {
				return;
			}

			var feedback = document.getElementById('reset-confirm-feedback');
			var submitBtn = document.getElementById('reset-confirm-submit');
			var submitLabel = submitBtn ? submitBtn.querySelector('span') : null;
			var loginUrl = <?php echo wp_json_encode( esc_url_raw( $login_url ) ); ?>;

			function parseAjaxError(data) {
				if (typeof data === 'string' && data.trim() !== '') {
					return data;
				}
				if (data && typeof data.message === 'string') {
					return data.message;
				}
				return <?php echo wp_json_encode( __( 'Não foi possível alterar a senha. Tente novamente.', 'apollo-login' ) ); ?>;
			}

			function showFeedback(message, type) {
				if (!feedback) {
					return;
				}
				feedback.textContent = message;
				feedback.className = 'apollo-verification-feedback ' + (type === 'success' ? 'is-ok' : 'is-err');
			}

			form.addEventListener('submit', function (e) {
				e.preventDefault();

				var pass = document.getElementById('new_password');
				var confirm = document.getElementById('new_password_confirm');
				if (!pass || !confirm) {
					return;
				}

				if (pass.value.length < 8) {
					showFeedback(<?php echo wp_json_encode( __( 'A senha deve ter pelo menos 8 caracteres.', 'apollo-login' ) ); ?>, 'error');
					return;
				}

				if (pass.value !== confirm.value) {
					showFeedback(<?php echo wp_json_encode( __( 'As senhas não coincidem.', 'apollo-login' ) ); ?>, 'error');
					return;
				}

				var originalLabel = submitLabel ? submitLabel.textContent : '';
				if (submitBtn) {
					submitBtn.disabled = true;
				}
				if (submitLabel) {
					submitLabel.textContent = <?php echo wp_json_encode( __( 'Salvando...', 'apollo-login' ) ); ?>;
				}
				showFeedback('', 'success');

				var formData = new FormData(form);
				formData.append('action', 'apollo_reset_confirm');
				formData.append('nonce', formData.get('apollo_reset_nonce') || '');

				var ajaxUrl = (window.apolloAuthConfig && window.apolloAuthConfig.ajaxUrl)
					? window.apolloAuthConfig.ajaxUrl
					: '/wp-admin/admin-ajax.php';

				fetch(ajaxUrl, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin'
				})
					.then(function (response) {
						return response.json().catch(function () {
							throw new Error('invalid_json');
						});
					})
					.then(function (result) {
						if (result && result.success) {
							showFeedback(
								(result.data && result.data.message)
									? result.data.message
									: <?php echo wp_json_encode( __( 'Senha alterada com sucesso!', 'apollo-login' ) ); ?>,
								'success'
							);
							setTimeout(function () {
								window.location.href = loginUrl;
							}, 1500);
							return;
						}

						showFeedback(parseAjaxError(result ? result.data : null), 'error');
						if (submitBtn) {
							submitBtn.disabled = false;
						}
						if (submitLabel) {
							submitLabel.textContent = originalLabel;
						}
					})
					.catch(function () {
						showFeedback(<?php echo wp_json_encode( __( 'Erro de conexão. Tente novamente.', 'apollo-login' ) ); ?>, 'error');
						if (submitBtn) {
							submitBtn.disabled = false;
						}
						if (submitLabel) {
							submitLabel.textContent = originalLabel;
						}
					});
			});
		})();
		</script>
	<?php endif; ?>

<?php else : ?>
	<div class="apollo-verification-status">
		<div class="apollo-verification-icon" aria-hidden="true"><i class="ri-mail-lock-line"></i></div>
		<h2 class="apollo-verification-title"><?php esc_html_e( 'Recuperar senha', 'apollo-login' ); ?></h2>
		<p class="apollo-verification-lead"><?php esc_html_e( 'Para receber um link de recuperação, use o formulário na página de acesso.', 'apollo-login' ); ?></p>
		<a href="<?php echo esc_url( $recovery_url ); ?>" class="btn-primary apollo-verification-submit" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;">
			<span><?php esc_html_e( 'Ir para recuperação', 'apollo-login' ); ?></span>
			<i class="ri-arrow-right-line" aria-hidden="true"></i>
		</a>
		<p class="apollo-verification-links" style="margin-top:16px;">
			<a href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Voltar ao login', 'apollo-login' ); ?></a>
		</p>
	</div>
<?php endif; ?>

</div>
