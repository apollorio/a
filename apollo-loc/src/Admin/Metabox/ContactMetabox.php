<?php
/**
 * ContactMetabox — Contato & Links sociais
 *
 * @package Apollo\Local\Admin\Metabox
 */

declare(strict_types=1);

namespace Apollo\Local\Admin\Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ContactMetabox {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
	}

	public function register(): void {
		add_meta_box(
			'apollo-loc-contact',
			'📞 ' . __( 'Contato & Links', 'apollo-local' ),
			array( $this, 'render' ),
			APOLLO_LOCAL_CPT,
			'normal',
			'default'
		);
	}

	public function render( \WP_Post $post ): void {
		$get = fn( string $k ) => esc_attr( (string) get_post_meta( $post->ID, $k, true ) );
		?>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:4px 0;">
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<span class="dashicons dashicons-phone" style="vertical-align:middle;"></span>
					<?php esc_html_e( 'Telefone / WhatsApp', 'apollo-local' ); ?>
				</label>
				<input type="tel" name="_local_phone" value="<?php echo $get( '_local_phone' ); ?>" class="widefat"
				       placeholder="+55 21 99999-9999">
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					<span class="dashicons dashicons-admin-site" style="vertical-align:middle;"></span>
					<?php esc_html_e( 'Website', 'apollo-local' ); ?>
				</label>
				<input type="url" name="_local_website" value="<?php echo esc_url( (string) get_post_meta( $post->ID, '_local_website', true ) ); ?>" class="widefat"
				       placeholder="https://...">
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					Instagram
				</label>
				<input type="url" name="_local_instagram" value="<?php echo esc_url( (string) get_post_meta( $post->ID, '_local_instagram', true ) ); ?>" class="widefat"
				       placeholder="https://instagram.com/...">
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					Facebook
				</label>
				<input type="url" name="_local_facebook" value="<?php echo esc_url( (string) get_post_meta( $post->ID, '_local_facebook', true ) ); ?>" class="widefat"
				       placeholder="https://facebook.com/...">
			</p>
			<p style="margin:0;">
				<label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px;">
					WhatsApp (número ou link wa.me)
				</label>
				<input type="text" name="_local_whatsapp" value="<?php echo $get( '_local_whatsapp' ); ?>" class="widefat"
				       placeholder="+5521999999999">
			</p>
		</div>
		<?php
	}
}