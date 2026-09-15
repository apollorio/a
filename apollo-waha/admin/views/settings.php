<?php
/**
 * Tela 1 — Settings, with Tela 2 (session strip) embedded at the top.
 * Apollo rule: render only — no WAHA HTTP happens in this file.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'apollo_manage_whatsapp' ) && ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Sem permissão.', 'apollo-waha' ) );
}

$apollo_wa_session   = Apollo_Waha_Session::instance();
$apollo_wa_ping      = $apollo_wa_session->ping();
$apollo_wa_state     = $apollo_wa_session->status_label( false, $apollo_wa_ping );
$apollo_wa_working   = $apollo_wa_session->is_working( false );
$apollo_wa_last_ping = (int) Apollo_Waha_Options::get( 'apollo_wa_last_ping', '' );

$apollo_wa_base_url = (string) Apollo_Waha_Options::get( 'apollo_wa_base_url' );
$apollo_wa_api_key  = (string) Apollo_Waha_Options::get( 'apollo_wa_api_key' );
$apollo_wa_hmac_key = (string) Apollo_Waha_Options::get( 'apollo_wa_hmac_key' );
$apollo_wa_sess     = (string) Apollo_Waha_Options::get( 'apollo_wa_session' );
$apollo_wa_group_id = (string) Apollo_Waha_Options::get( 'apollo_wa_group_id' );

$apollo_wa_known_states = array( 'WORKING', 'SCAN_QR_CODE', 'STARTING', 'FAILED', 'STOPPED' );
$apollo_wa_state_shown  = in_array( $apollo_wa_state, $apollo_wa_known_states, true ) ? $apollo_wa_state : 'DOWN';
?>
<div class="wrap apollo-wa-settings">
	<h1><?php esc_html_e( 'Apollo WhatsApp', 'apollo-waha' ); ?></h1>

	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Configurações salvas.', 'apollo-waha' ); ?></p></div>
	<?php endif; ?>

	<!-- Tela 2 — session strip, embedded per registry.screens.2_session_strip.parent -->
	<div
		id="apollo-wa-strip"
		class="apollo-wa-strip apollo-wa-strip--<?php echo esc_attr( strtolower( $apollo_wa_state_shown ) ); ?>"
		data-nonce="<?php echo esc_attr( wp_create_nonce( 'apollo_wa_test_connection' ) ); ?>"
	>
		<span class="apollo-wa-strip__dot" aria-hidden="true"></span>
		<span class="apollo-wa-strip__label"><?php echo esc_html( $apollo_wa_state_shown ); ?></span>
		<?php if ( $apollo_wa_last_ping > 0 ) : ?>
			<span class="apollo-wa-strip__ping">
				<?php
				printf(
					/* translators: %s: human-readable time since last ping, e.g. "2 minutos" */
					esc_html__( 'último ping há %s', 'apollo-waha' ),
					esc_html( human_time_diff( $apollo_wa_last_ping ) )
				);
				?>
			</span>
		<?php endif; ?>
		<button type="button" class="button" id="apollo-wa-test"><?php esc_html_e( 'Testar', 'apollo-waha' ); ?></button>
	</div>

	<?php if ( ! $apollo_wa_working ) : ?>
		<div class="notice notice-warning">
			<p><strong><?php esc_html_e( 'session down — nada sai', 'apollo-waha' ); ?></strong></p>
		</div>
	<?php endif; ?>

	<!-- Tela 1 — connection + group cards -->
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'apollo_wa_save_settings' ); ?>
		<input type="hidden" name="action" value="apollo_wa_save_settings" />

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="apollo_wa_base_url"><?php esc_html_e( 'Base URL do WAHA', 'apollo-waha' ); ?></label></th>
				<td>
					<input type="url" id="apollo_wa_base_url" name="apollo_wa_base_url" class="regular-text code"
						value="<?php echo esc_attr( $apollo_wa_base_url ); ?>" placeholder="http://127.0.0.1:3000" />
					<p class="description"><?php esc_html_e( 'Bind local ou rede Docker privada — nunca internet pública.', 'apollo-waha' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="apollo_wa_api_key"><?php esc_html_e( 'API Key', 'apollo-waha' ); ?></label></th>
				<td>
					<input type="password" id="apollo_wa_api_key" name="apollo_wa_api_key" class="regular-text" value="" autocomplete="off"
						placeholder="<?php echo '' !== $apollo_wa_api_key ? esc_attr__( 'já configurada — deixe em branco para manter', 'apollo-waha' ) : ''; ?>" />
					<?php if ( '' !== $apollo_wa_api_key ) : ?>
						<p class="description">
							<?php
							printf(
								/* translators: %s: masked key, e.g. "ab••••••cd" */
								esc_html__( 'Atual: %s', 'apollo-waha' ),
								esc_html( Apollo_Waha_Options::mask( $apollo_wa_api_key ) )
							);
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="apollo_wa_session"><?php esc_html_e( 'Sessão WAHA', 'apollo-waha' ); ?></label></th>
				<td><input type="text" id="apollo_wa_session" name="apollo_wa_session" class="regular-text"
					value="<?php echo esc_attr( $apollo_wa_sess ); ?>" placeholder="apollo" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="apollo_wa_group_id"><?php esc_html_e( 'Group ID', 'apollo-waha' ); ?></label></th>
				<td>
					<input type="text" id="apollo_wa_group_id" name="apollo_wa_group_id" class="regular-text code"
						value="<?php echo esc_attr( $apollo_wa_group_id ); ?>" placeholder="120363...@g.us" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Operação', 'apollo-waha' ); ?></th>
				<td>
					<p class="description">
						<?php esc_html_e( '+55 21 93300-8447 — Rio de Janeiro. Número de operação, nunca o Apollo Business verificado.', 'apollo-waha' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'HMAC', 'apollo-waha' ); ?></th>
				<td>
					<?php if ( '' !== $apollo_wa_hmac_key ) : ?>
						<p class="description">
							<?php
							printf(
								/* translators: %s: masked HMAC key */
								esc_html__( 'Gerada: %s — verificação de assinatura ainda não implementada (Run 2).', 'apollo-waha' ),
								esc_html( Apollo_Waha_Options::mask( $apollo_wa_hmac_key ) )
							);
							?>
						</p>
					<?php else : ?>
						<p class="description"><?php esc_html_e( 'Será gerada automaticamente ao salvar (wp_generate_password, 48 chars).', 'apollo-waha' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Salvar', 'apollo-waha' ) ); ?>
	</form>
</div>
