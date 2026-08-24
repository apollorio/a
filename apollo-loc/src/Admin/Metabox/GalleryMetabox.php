<?php
/**
 * GalleryMetabox — coleta as imagens do Local (_local_image_1 … _local_image_5)
 *
 * O single (styles/base/single-local.php) já LÊ _local_image_1..5 (ID de anexo
 * ou URL) para o hero + galeria, mas até então não havia NENHUMA superfície de
 * input para preenchê-las (gap "local.images_input" em data-registry.json).
 * Este metabox usa o mesmo padrão wp.media já provado em apollo-djs
 * (Metabox::render_gallery) e grava exatamente as 5 chaves discretas que o
 * single consome. Save fica em MetaboxSaver::save().
 *
 * @package Apollo\Local\Admin\Metabox
 */

declare(strict_types=1);

namespace Apollo\Local\Admin\Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GalleryMetabox {

	/** Número de slots de imagem que o single-local consome (1..5). */
	private const SLOTS = 5;

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	public function register(): void {
		add_meta_box(
			'apollo-loc-gallery',
			'🖼 ' . __( 'Imagens do Local', 'apollo-local' ),
			array( $this, 'render' ),
			APOLLO_LOCAL_CPT,
			'normal',
			'default'
		);
	}

	public function enqueue( string $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || APOLLO_LOCAL_CPT !== $screen->post_type ) {
			return;
		}
		wp_enqueue_media();
	}

	public function render( \WP_Post $post ): void {
		?>
		<p class="description" style="margin:0 0 10px;">
			<?php esc_html_e( 'A 1ª imagem é o hero do single. Até 5 imagens compõem a galeria.', 'apollo-local' ); ?>
		</p>
		<div id="apl-loc-gallery" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-start;">
			<?php
			for ( $i = 1; $i <= self::SLOTS; $i++ ) :
				$val = get_post_meta( $post->ID, '_local_image_' . $i, true );
				$url = '';
				if ( $val ) {
					$url = is_numeric( $val )
						? (string) wp_get_attachment_image_url( (int) $val, 'medium' )
						: (string) $val;
				}
				?>
				<div class="apl-loc-slot" data-slot="<?php echo esc_attr( (string) $i ); ?>"
					style="width:110px;text-align:center;">
					<div class="apl-loc-thumb"
						style="width:110px;height:110px;border:1px dashed #c3c4c7;border-radius:8px;background:#f6f7f7 center/cover no-repeat;<?php echo $url ? 'background-image:url(' . esc_url( $url ) . ');border-style:solid;' : ''; ?>"></div>
					<input type="hidden" name="_local_image_<?php echo esc_attr( (string) $i ); ?>"
						class="apl-loc-input" value="<?php echo esc_attr( (string) $val ); ?>">
					<div style="margin-top:5px;display:flex;gap:4px;justify-content:center;">
						<button type="button" class="button button-small apl-loc-pick"><?php esc_html_e( 'Escolher', 'apollo-local' ); ?></button>
						<button type="button" class="button button-small apl-loc-clear" aria-label="<?php esc_attr_e( 'Remover', 'apollo-local' ); ?>">✕</button>
					</div>
				</div>
			<?php endfor; ?>
		</div>
		<script>
		(function () {
			var root = document.getElementById('apl-loc-gallery');
			if (!root) { return; }

			/* A debug beacon lived here (removed 2026-08-17): it POSTed to
			   http://127.0.0.1:7514 from the editor's browser on every load of
			   this metabox — mixed content on an HTTPS admin, and a request that
			   can only ever succeed on one developer's laptop. */

			function setSlot(slot, id, url) {
				slot.querySelector('.apl-loc-input').value = id ? String(id) : '';
				var t = slot.querySelector('.apl-loc-thumb');
				if (url) { t.style.backgroundImage = 'url(' + url + ')'; t.style.borderStyle = 'solid'; }
				else { t.style.backgroundImage = ''; t.style.borderStyle = 'dashed'; }
			}

			root.addEventListener('click', function (e) {
				var slot = e.target.closest('.apl-loc-slot');
				if (!slot) { return; }
				if (e.target.classList.contains('apl-loc-clear')) {
					e.preventDefault();
					setSlot(slot, '', '');
					return;
				}
				if (e.target.classList.contains('apl-loc-pick')) {
					e.preventDefault();
					/*
					 * Do NOT check wp.media at IIFE parse time — metabox HTML
					 * renders before admin footer scripts. Check on click.
					 */
					if (typeof wp === 'undefined' || !wp.media) {
						window.alert('Biblioteca de mídia indisponível. Recarregue a página.');
						return;
					}
					var frame = wp.media({ title: 'Imagem do Local', multiple: false, library: { type: 'image' } });
					frame.on('select', function () {
						var a = frame.state().get('selection').first().toJSON();
						var url = (a.sizes && a.sizes.medium) ? a.sizes.medium.url : a.url;
						setSlot(slot, a.id, url);
					});
					frame.open();
				}
			});
		})();
		</script>
		<?php
	}
}
