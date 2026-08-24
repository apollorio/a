<?php
/**
 * AddressMetabox — Endereço & Coordenadas
 *
 * @package Apollo\Local\Admin\Metabox
 */

declare(strict_types=1);

namespace Apollo\Local\Admin\Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AddressMetabox {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
	}

	public function register(): void {
		add_meta_box(
			'apollo-loc-address',
			'📍 ' . __( 'Endereço & Localização', 'apollo-local' ),
			array( $this, 'render' ),
			APOLLO_LOCAL_CPT,
			'normal',
			'high'
		);
	}

	public function render( \WP_Post $post ): void {
		wp_nonce_field( 'apollo_loc_save_meta', 'apollo_loc_metabox_nonce' );

		$get = fn( string $k ) => esc_attr( (string) get_post_meta( $post->ID, $k, true ) );
		?>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:4px 0;">
			<p style="grid-column:1/-1;margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Nome alternativo (exibição)', 'apollo-local' ); ?>
				</label>
				<input type="text" name="_local_name" value="<?php echo $get( '_local_name' ); ?>" class="widefat"
				       placeholder="<?php esc_attr_e( 'Se diferente do título do post', 'apollo-local' ); ?>">
			</p>
			<p style="grid-column:1/-1;margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Endereço (rua, número)', 'apollo-local' ); ?>
				</label>
				<input type="text" name="_local_address" value="<?php echo $get( '_local_address' ); ?>" class="widefat"
				       placeholder="Rua Francisco Otaviano, 67">
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Cidade', 'apollo-local' ); ?>
				</label>
				<input type="text" name="_local_city" value="<?php echo $get( '_local_city' ); ?>" class="widefat"
				       placeholder="Rio de Janeiro">
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Estado (UF)', 'apollo-local' ); ?>
				</label>
				<input type="text" name="_local_state" value="<?php echo $get( '_local_state' ); ?>" class="widefat"
				       placeholder="RJ" maxlength="2">
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'País', 'apollo-local' ); ?>
				</label>
				<input type="text" name="_local_country" value="<?php echo $get( '_local_country' ) ?: 'Brasil'; ?>" class="widefat">
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'CEP', 'apollo-local' ); ?>
				</label>
				<input type="text" name="_local_postal" value="<?php echo $get( '_local_postal' ); ?>" class="widefat"
				       placeholder="00000-000" maxlength="10">
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Latitude', 'apollo-local' ); ?> <em style="color:#999;font-weight:400;">(auto via geocodificação)</em>
				</label>
				<input type="text" name="_local_lat" value="<?php echo $get( '_local_lat' ); ?>" class="widefat"
				       placeholder="-22.9068">
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<?php esc_html_e( 'Longitude', 'apollo-local' ); ?> <em style="color:#999;font-weight:400;">(auto via geocodificação)</em>
				</label>
				<input type="text" name="_local_lng" value="<?php echo $get( '_local_lng' ); ?>" class="widefat"
				       placeholder="-43.1729">
			</p>
		</div>
		<p style="margin:8px 0 0;font-size:11px;color:#666;">
			<span class="dashicons dashicons-info" style="font-size:13px;vertical-align:middle;"></span>
			<?php esc_html_e( 'Deixe lat/lng em branco — serão preenchidos automaticamente ao salvar (Nominatim OSM).', 'apollo-local' ); ?>
		</p>
		<?php
	}
}