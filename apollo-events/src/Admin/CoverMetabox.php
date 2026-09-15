<?php

/**
 * CoverMetabox — featured image + image URL next to Save/Publish.
 *
 * The event cover is the CPT featured image. The URL field must validate as a
 * real image and stay in lockstep with that featured image (sideload on save).
 * Renders inside the Publish box, immediately above the button.
 *
 * @package Apollo\Event\Admin
 */

namespace Apollo\Event\Admin;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

final class CoverMetabox {

	private const POST_TYPE = 'event';
	private const NONCE     = 'apollo_event_cover_nonce';
	private const ACTION    = 'apollo_event_save_cover';

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'remove_native_featured' ), 20 );
		add_action( 'post_submitbox_misc_actions', array( $this, 'render' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'heal_after_save' ), 20, 2 );
		add_action( 'admin_notices', array( $this, 'cover_error_notice' ) );
	}

	public function cover_error_notice(): void {
		if ( empty( $_GET['apollo_event_cover_err'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$msg = sanitize_text_field( wp_unslash( (string) $_GET['apollo_event_cover_err'] ) );
		if ( '' === $msg ) {
			return;
		}
		echo '<div class="notice notice-error is-dismissible"><p><strong>';
		esc_html_e( 'Capa do evento:', 'apollo-events' );
		echo '</strong> ' . esc_html( $msg ) . '</p></div>';
	}

	public function remove_native_featured(): void {
		remove_meta_box( 'postimagediv', self::POST_TYPE, 'side' );
	}

	public function render(): void {
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		global $post;
		$post_id = $post instanceof \WP_Post ? (int) $post->ID : 0;
		if ( $post_id && \function_exists( 'apollo_event_heal_cover' ) ) {
			\apollo_event_heal_cover( $post_id );
		}

		$img     = $post_id && \function_exists( 'apollo_event_share_image' )
			? \apollo_event_share_image( $post_id, 'medium' )
			: array( 'url' => '', 'id' => 0 );
		$att_id  = (int) ( $img['id'] ?? 0 );
		if ( ! $att_id && $post_id ) {
			$att_id = (int) get_post_thumbnail_id( $post_id );
		}
		$full    = $att_id ? (string) ( wp_get_attachment_image_url( $att_id, 'full' ) ?: '' ) : (string) ( $img['url'] ?? '' );
		$preview = (string) ( $img['url'] ?? $full );
		wp_nonce_field( self::ACTION, self::NONCE );
		?>
		<div id="apollo-event-cover-row" class="apollo-event-cover-row">
			<label class="apollo-event-cover-label" for="apl-event-cover-url">
				<?php esc_html_e( 'Capa / imagem de compartilhamento', 'apollo-events' ); ?>
			</label>
			<p class="apollo-event-cover-hint">
				<?php esc_html_e( 'A mesma imagem da capa do post (featured) vai no WhatsApp, Twitter e Facebook. Cole um URL de imagem ou escolha da biblioteca.', 'apollo-events' ); ?>
			</p>
			<div class="apollo-event-cover-prev-wrap">
				<?php if ( $preview ) : ?>
					<img id="apl-event-cover-prev" class="apollo-event-cover-prev" src="<?php echo esc_url( $preview ); ?>" alt="">
				<?php else : ?>
					<img id="apl-event-cover-prev" class="apollo-event-cover-prev" src="" alt="" hidden>
				<?php endif; ?>
			</div>
			<input type="hidden" name="_event_banner" id="apl-event-banner-id" value="<?php echo esc_attr( (string) $att_id ); ?>">
			<p>
				<button type="button" class="button" id="apl-event-cover-pick">
					<?php esc_html_e( 'Escolher imagem destacada', 'apollo-events' ); ?>
				</button>
			</p>
			<p>
				<input type="url" name="_event_cover_url" id="apl-event-cover-url" class="widefat"
					value="<?php echo esc_url( $full ); ?>"
					placeholder="https://…/flyer.jpg"
					autocomplete="off">
			</p>
			<p id="apl-event-cover-status" class="apollo-event-cover-status" aria-live="polite"></p>
		</div>
		<style>
			.apollo-event-cover-row {
				margin: 10px 0 8px;
				padding: 10px 12px;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				background: #fff;
			}
			.apollo-event-cover-label {
				display: block;
				font-weight: 600;
				font-size: 12px;
				text-transform: uppercase;
				letter-spacing: .04em;
				margin-bottom: 4px;
			}
			.apollo-event-cover-hint {
				margin: 0 0 8px;
				font-size: 11px;
				color: #646970;
				line-height: 1.4;
			}
			.apollo-event-cover-prev {
				display: block;
				max-width: 100%;
				height: auto;
				max-height: 120px;
				margin-bottom: 8px;
				border-radius: 3px;
			}
			.apollo-event-cover-status {
				margin: 4px 0 0;
				font-size: 11px;
				min-height: 1.2em;
			}
			.apollo-event-cover-status.is-ok { color: #1e7e34; }
			.apollo-event-cover-status.is-err { color: #b32d2e; }
		</style>
		<script>
		(function () {
			var row = document.getElementById('apollo-event-cover-row');
			var anchor = document.getElementById('publishing-action');
			if (row && anchor && anchor.parentNode) {
				anchor.parentNode.insertBefore(row, anchor);
			}

			var pick = document.getElementById('apl-event-cover-pick');
			var idIn = document.getElementById('apl-event-banner-id');
			var urlIn = document.getElementById('apl-event-cover-url');
			var prev = document.getElementById('apl-event-cover-prev');
			var status = document.getElementById('apl-event-cover-status');

			function setStatus(msg, ok) {
				if (!status) return;
				status.textContent = msg || '';
				status.classList.toggle('is-ok', !!ok);
				status.classList.toggle('is-err', !ok && !!msg);
			}

			function paint(url) {
				if (!prev) return;
				if (url) {
					prev.src = url;
					prev.hidden = false;
				} else {
					prev.removeAttribute('src');
					prev.hidden = true;
				}
			}

			if (pick) {
				pick.addEventListener('click', function (e) {
					e.preventDefault();
					if (typeof wp === 'undefined' || !wp.media) {
						window.alert('Biblioteca de mídia indisponível. Recarregue a página.');
						return;
					}
					var frame = wp.media({ title: 'Capa do evento', multiple: false, library: { type: 'image' } });
					frame.on('select', function () {
						var att = frame.state().get('selection').first().toJSON();
						if (idIn) idIn.value = String(att.id);
						var full = att.url || '';
						if (urlIn) urlIn.value = full;
						paint((att.sizes && att.sizes.medium && att.sizes.medium.url) || full);
						setStatus('Capa = imagem destacada do post.', true);
					});
					frame.open();
				});
			}

			function validateUrl() {
				if (!urlIn) return;
				var raw = (urlIn.value || '').trim();
				if (!raw) {
					setStatus('', true);
					return;
				}
				if (!/^https?:\/\//i.test(raw)) {
					setStatus('Use um endereço https://', false);
					return;
				}
				setStatus('Validando imagem…', true);
				var img = new Image();
				img.onload = function () {
					if (img.naturalWidth < 200 || img.naturalHeight < 200) {
						setStatus('Imagem pequena demais (mín. 200×200, ideal 1200×630).', false);
						return;
					}
					if (idIn && urlIn && raw !== urlIn.defaultValue) {
						idIn.value = '0';
					}
					paint(raw);
					setStatus('OK · ' + img.naturalWidth + '×' + img.naturalHeight + ' · mesma capa no share.', true);
				};
				img.onerror = function () {
					setStatus('URL não carregou como imagem (HTML / 404).', false);
				};
				img.src = raw;
			}

			if (urlIn) {
				urlIn.addEventListener('blur', validateUrl);
				urlIn.addEventListener('change', validateUrl);
			}
		})();
		</script>
		<?php
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::ACTION ) ) {
			return;
		}
		if ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( $post->post_type !== self::POST_TYPE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$cover_url = isset( $_POST['_event_cover_url'] )
			? esc_url_raw( wp_unslash( (string) $_POST['_event_cover_url'] ) )
			: '';
		$banner_raw = isset( $_POST['_event_banner'] )
			? wp_unslash( $_POST['_event_banner'] )
			: '';

		$current_full = (string) ( get_the_post_thumbnail_url( $post_id, 'full' ) ?: '' );
		$ref          = $banner_raw;

		if ( '' !== $cover_url && $cover_url !== $current_full ) {
			$from_url = attachment_url_to_postid( $cover_url );
			$ref      = $from_url > 0 ? $from_url : $cover_url;
		}

		if ( ! \function_exists( 'apollo_event_set_banner' ) ) {
			return;
		}

		$result = \apollo_event_set_banner( $post_id, $ref );
		if ( is_wp_error( $result ) ) {
			add_filter(
				'redirect_post_location',
				static function ( string $location ) use ( $result ): string {
					return add_query_arg(
						'apollo_event_cover_err',
						rawurlencode( $result->get_error_message() ),
						$location
					);
				}
			);
		}
	}

	public function heal_after_save( int $post_id, \WP_Post $post ): void {
		if ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( $post->post_type !== self::POST_TYPE ) {
			return;
		}
		if ( \function_exists( 'apollo_event_heal_cover' ) ) {
			\apollo_event_heal_cover( $post_id );
		}
	}
}
