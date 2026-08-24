<?php
/**
 * Template: Admin Settings Page
 *
 * Rendered by Plugin::render_settings_page()
 * Adapted from WPAdverts admin options page pattern.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle form save
if ( isset( $_POST['apollo_adverts_settings_nonce'] )
	&& wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_adverts_settings_nonce'] ) ), 'apollo_adverts_save_settings' )
) {
	$settings = array(
		'expiration_days'      => absint( $_POST['expiration_days'] ?? 30 ),
		'max_images'           => absint( $_POST['max_images'] ?? 8 ),
		'posts_per_page'       => absint( $_POST['posts_per_page'] ?? 12 ),
		'moderation'           => in_array( $_POST['moderation'] ?? '', array( 'auto', 'manual' ), true ) ? $_POST['moderation'] : 'auto',
		'allow_guest'          => ! empty( $_POST['allow_guest'] ),
		'notify_admin_new'     => ! empty( $_POST['notify_admin_new'] ),
		'notify_admin_expire'  => ! empty( $_POST['notify_admin_expire'] ),
		'notify_author_expire' => ! empty( $_POST['notify_author_expire'] ),
		'expiring_days_before' => absint( $_POST['expiring_days_before'] ?? 3 ),
		'bp_profile_tab'       => ! empty( $_POST['bp_profile_tab'] ),
		'fav_enabled'          => ! empty( $_POST['fav_enabled'] ),
		'wow_enabled'          => ! empty( $_POST['wow_enabled'] ),
	);

	update_option( 'apollo_adverts_settings', $settings );
	echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Configurações salvas.', 'apollo-adverts' ) . '</p></div>';
}

$config = apollo_adverts_config();
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Apollo Adverts — Configurações', 'apollo-adverts' ); ?></h1>

	<form method="post">
		<?php wp_nonce_field( 'apollo_adverts_save_settings', 'apollo_adverts_settings_nonce' ); ?>

		<h2 class="title"><?php esc_html_e( 'Geral', 'apollo-adverts' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="expiration_days"><?php esc_html_e( 'Dias de validade', 'apollo-adverts' ); ?></label></th>
				<td>
					<input type="number" name="expiration_days" id="expiration_days" value="<?php echo esc_attr( (string) $config['expiration_days'] ); ?>" class="apollo-input" min="1" max="365" class="small-text" />
					<p class="description"><?php esc_html_e( 'Quantos dias um anúncio fica ativo antes de expirar.', 'apollo-adverts' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="max_images"><?php esc_html_e( 'Máximo de fotos', 'apollo-adverts' ); ?></label></th>
				<td>
					<input type="number" name="max_images" id="max_images" value="<?php echo esc_attr( (string) $config['max_images'] ); ?>" class="apollo-input" min="1" max="20" class="small-text" />
				</td>
			</tr>
			<tr>
				<th><label for="posts_per_page"><?php esc_html_e( 'Anúncios por página', 'apollo-adverts' ); ?></label></th>
				<td>
					<input type="number" name="posts_per_page" id="posts_per_page" value="<?php echo esc_attr( (string) $config['posts_per_page'] ); ?>" class="apollo-input" min="1" max="100" class="small-text" />
				</td>
			</tr>
			<tr>
				<th><label for="moderation"><?php esc_html_e( 'Moderação', 'apollo-adverts' ); ?></label></th>
				<td>
					<select name="moderation" id="moderation">
						<option value="auto" <?php selected( $config['moderation'], 'auto' ); ?>><?php esc_html_e( 'Aprovação automática', 'apollo-adverts' ); ?></option>
						<option value="manual" <?php selected( $config['moderation'], 'manual' ); ?>><?php esc_html_e( 'Aprovação manual', 'apollo-adverts' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Notificações', 'apollo-adverts' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Emails', 'apollo-adverts' ); ?></th>
				<td>
					<label class="custom-checkbox"><input type="checkbox" name="notify_admin_new" value="1" <?php checked( $config['notify_admin_new'] ); ?> /> <?php esc_html_e( 'Notificar admin sobre novos anúncios', 'apollo-adverts' ); ?></label><br>
					<label class="custom-checkbox"><input type="checkbox" name="notify_admin_expire" value="1" <?php checked( $config['notify_admin_expire'] ); ?> /> <?php esc_html_e( 'Notificar admin sobre anúncios expirados', 'apollo-adverts' ); ?></label><br>
					<label class="custom-checkbox"><input type="checkbox" name="notify_author_expire" value="1" <?php checked( $config['notify_author_expire'] ); ?> /> <?php esc_html_e( 'Notificar autor sobre expiração próxima', 'apollo-adverts' ); ?></label>
				</td>
			</tr>
			<tr>
				<th><label for="expiring_days_before"><?php esc_html_e( 'Aviso de expiração (dias antes)', 'apollo-adverts' ); ?></label></th>
				<td>
					<input type="number" name="expiring_days_before" id="expiring_days_before" value="<?php echo esc_attr( (string) $config['expiring_days_before'] ); ?>" class="apollo-input" min="1" max="30" class="small-text" />
				</td>
			</tr>
		</table>

		<h2 class="title"><?php esc_html_e( 'Integrações', 'apollo-adverts' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'BuddyPress', 'apollo-adverts' ); ?></th>
				<td>
					<label class="custom-checkbox"><input type="checkbox" name="bp_profile_tab" value="1" <?php checked( $config['bp_profile_tab'] ); ?> /> <?php esc_html_e( 'Aba "Anúncios" no perfil BuddyPress', 'apollo-adverts' ); ?></label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Apollo Fav', 'apollo-adverts' ); ?></th>
				<td>
					<label class="custom-checkbox"><input type="checkbox" name="fav_enabled" value="1" <?php checked( $config['fav_enabled'] ); ?> /> <?php esc_html_e( 'Favoritar anúncios', 'apollo-adverts' ); ?></label>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Apollo WOW', 'apollo-adverts' ); ?></th>
				<td>
					<label class="custom-checkbox"><input type="checkbox" name="wow_enabled" value="1" <?php checked( $config['wow_enabled'] ); ?> /> <?php esc_html_e( 'Reações em anúncios', 'apollo-adverts' ); ?></label>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Salvar Configurações', 'apollo-adverts' ) ); ?>
	</form>
</div>