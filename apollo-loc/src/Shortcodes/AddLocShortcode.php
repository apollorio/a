<?php
/**
 * AddLocShortcode — [apollo_add_loc] formulário para adicionar local
 *
 * Exibe formulário SE o usuário tem permissão para publicar posts.
 * Submissions são enviadas via REST API apollo/v1/local.
 *
 * @package Apollo\Local\Shortcodes
 */

declare(strict_types=1);

namespace Apollo\Local\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AddLocShortcode {

	/**
	 * Renderiza o formulário de adição.
	 *
	 * @param array|string $atts Atributos do shortcode.
	 * @return string HTML resultante.
	 */
	public function render( $atts ): string {
		if ( ! is_user_logged_in() || ! current_user_can( 'publish_posts' ) ) {
			return '<p class="apollo-auth-notice">'
				. esc_html__( 'Faça login para adicionar um local.', 'apollo-local' )
				. '</p>';
		}

		wp_enqueue_script( 'apollo-loc-ui' );

		wp_localize_script(
			'apollo-loc-ui',
			'apolloAddLoc',
			array(
				'rest_url' => esc_url_raw( rest_url( APOLLO_LOCAL_REST_NAMESPACE . '/local' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
			)
		);

		ob_start();
		?>
		<form class="apollo-add-loc-form" id="apolloAddLocForm" novalidate>
			<div class="input-group">
				<label for="loc-name"><?php esc_html_e( 'Nome do local *', 'apollo-local' ); ?></label>
				<input type="text" id="loc-name" name="name" required maxlength="200" autocomplete="off">
			</div>
			<div class="input-group">
				<label for="loc-address"><?php esc_html_e( 'Endereço', 'apollo-local' ); ?></label>
				<input type="text" id="loc-address" name="address" maxlength="255">
			</div>
			<div class="form-row">
				<div class="input-group">
					<label for="loc-city"><?php esc_html_e( 'Cidade', 'apollo-local' ); ?></label>
					<input type="text" id="loc-city" name="city" value="Rio de Janeiro" maxlength="100">
				</div>
				<div class="input-group">
					<label for="loc-state"><?php esc_html_e( 'Estado', 'apollo-local' ); ?></label>
					<input type="text" id="loc-state" name="state" value="RJ" maxlength="50">
				</div>
			</div>
			<div class="input-group">
				<label for="loc-phone"><?php esc_html_e( 'Telefone / WhatsApp', 'apollo-local' ); ?></label>
				<input type="tel" id="loc-phone" name="phone" maxlength="20">
			</div>
			<div class="input-group">
				<label for="loc-instagram">Instagram</label>
				<input type="url" id="loc-instagram" name="instagram" placeholder="https://instagram.com/...">
			</div>
			<div id="apolloAddLocMsg" class="apollo-form-msg" hidden></div>
			<button type="submit" class="btn btn-primary">
				<?php esc_html_e( 'Enviar local', 'apollo-local' ); ?>
			</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}
}