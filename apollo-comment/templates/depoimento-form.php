<?php
/**
 * Template: Depoimento Form (AJAX / REST-powered)
 *
 * Variable from shortcode: $post_id (int)
 *
 * @package Apollo\Comment
 */

use Apollo\Comment\Depoimento;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_logged = is_user_logged_in();
$user      = $is_logged ? wp_get_current_user() : null;
$avatar    = $is_logged ? Depoimento::get_avatar_url( $user->ID ) : '';
?>

<div class="depoimento-form-wrapper" data-post-id="<?php echo esc_attr( $post_id ); ?>">
	<?php if ( $is_logged ) : ?>
		<div class="depoimento-form-card">
			<!-- Current user avatar -->
			<span class="depoimento-avatar">
				<img src="<?php echo esc_url( $avatar ); ?>"
					alt="<?php echo esc_attr( $user->display_name ); ?>"
					class="depoimento-avatar-img" loading="lazy">
			</span>

			<div class="depoimento-form-body">
				<textarea class="apollo-textarea depoimento-textarea"
							id="depoimento-new-text"
							placeholder="Deixe seu depoimento…"
							rows="3"
							maxlength="1000"></textarea>

				<div class="depoimento-form-actions">
					<span class="depoimento-char-count">
						<span id="depoimento-char-current">0</span>/1000
					</span>
					<button type="button"
							class="depoimento-submit-btn"
							id="depoimento-submit"
							disabled>
						<i class="ri-send-plane-fill"></i> Enviar Depoimento
					</button>
				</div>
			</div>
		</div>
	<?php else : ?>
		<div class="depoimento-login-prompt">
			<i class="ri-lock-line"></i>
			<p>
				<a href="<?php echo esc_url( home_url( '/login/' ) ); ?>">Faça login</a>
				para deixar um depoimento.
			</p>
		</div>
	<?php endif; ?>
</div>