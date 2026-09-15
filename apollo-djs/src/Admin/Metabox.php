<?php

/**
 * Metabox — Admin metaboxes para o CPT "dj"
 *
 * Cobre TODOS os 11 meta keys do apollo-registry.json
 *
 * @package Apollo\DJs\Admin
 */

namespace Apollo\DJs\Admin;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

final class Metabox {

	private const POST_TYPE = 'dj';
	private const NONCE     = 'apollo_dj_metabox_nonce';
	private const ACTION    = 'apollo_dj_save_meta';

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'register_taxonomy_args', array( $this, 'sound_select_metabox' ), 10, 2 );
	}

	/**
	 * Render the `sound` taxonomy as a multi-<select> instead of a checkbox list.
	 *
	 * ⚠ MULTI, NOT SINGLE — deliberately different from apollo-loc's taxonomy
	 * selects. A venue has one type and one zone, so those collapse to a single
	 * choice. A DJ genuinely plays several genres, and _dj_eyebrow's derivation
	 * explicitly reads "the first two `sound` terms" — collapsing to one would
	 * break that fallback. So this is a <select multiple>: still a select as
	 * requested, still honest about the cardinality.
	 *
	 * Only meta_box_cb is overridden. `sound` is a shared bridge taxonomy
	 * (dj + event, registered by apollo-core/apollo-events with apollo-djs only
	 * providing a fallback), so its registration args are otherwise untouched —
	 * and this filter deliberately does NOT fire for the `event` screen, which
	 * keeps its default control.
	 *
	 * @param array<string,mixed> $args     Taxonomy args.
	 * @param string              $taxonomy Taxonomy slug.
	 * @return array<string,mixed>
	 */
	public function sound_select_metabox( array $args, string $taxonomy ): array {
		if ( APOLLO_DJ_TAX_SOUND !== $taxonomy ) {
			return $args;
		}
		$args['meta_box_cb'] = array( $this, 'render_sound_select' );

		return $args;
	}

	/**
	 * The `sound` multi-select control.
	 *
	 * @param \WP_Post            $post Current post.
	 * @param array<string,mixed> $box  Metabox args.
	 */
	public function render_sound_select( \WP_Post $post, array $box ): void {
		$taxonomy = isset( $box['args']['taxonomy'] ) ? (string) $box['args']['taxonomy'] : APOLLO_DJ_TAX_SOUND;

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		$assigned = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
		$assigned = is_wp_error( $assigned ) ? array() : array_map( 'intval', $assigned );

		wp_nonce_field( 'apollo_dj_tax_save', 'apollo_dj_tax_nonce' );
		?>
		<select name="apollo_dj_sound[]" multiple size="8" class="widefat" style="margin-top:6px;">
			<?php foreach ( $terms as $term ) : ?>
				<option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php echo in_array( (int) $term->term_id, $assigned, true ) ? 'selected' : ''; ?>>
					<?php echo esc_html( ( $term->parent ? '— ' : '' ) . $term->name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description" style="margin-top:6px;">
			<?php esc_html_e( 'Ctrl/Cmd para escolher vários. Os dois primeiros alimentam o eyebrow do hero.', 'apollo-djs' ); ?>
		</p>
		<?php if ( empty( $terms ) ) : ?>
			<p class="description"><?php esc_html_e( 'Nenhum gênero cadastrado ainda.', 'apollo-djs' ); ?></p>
		<?php endif; ?>
		<?php
	}

	public function register(): void {
		add_meta_box(
			'apollo-dj-info',
			__( '🎧 Informações do DJ', 'apollo-djs' ),
			array( $this, 'render_info' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
		add_meta_box(
			'apollo-dj-links',
			__( '🔗 Links & Redes Sociais', 'apollo-djs' ),
			array( $this, 'render_links' ),
			self::POST_TYPE,
			'normal',
			'default'
		);
		add_meta_box(
			'apollo-dj-tracks',
			__( '🎵 Out now! (lançamentos)', 'apollo-djs' ),
			array( $this, 'render_tracks' ),
			self::POST_TYPE,
			'normal',
			'default'
		);
		add_meta_box(
			'apollo-dj-gallery',
			__( '🖼 Galeria', 'apollo-djs' ),
			array( $this, 'render_gallery' ),
			self::POST_TYPE,
			'normal',
			'default'
		);
		add_meta_box(
			'apollo-dj-booking',
			__( '📩 Booking & EPK', 'apollo-djs' ),
			array( $this, 'render_booking' ),
			self::POST_TYPE,
			'side',
			'default'
		);
		add_meta_box(
			'apollo-dj-config',
			__( '⚙ Configurações', 'apollo-djs' ),
			array( $this, 'render_config' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	public function enqueue_assets( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== self::POST_TYPE ) {
			return;
		}
		wp_enqueue_media();
	}

	public function render_info( \WP_Post $post ): void {
		wp_nonce_field( self::ACTION, self::NONCE );
		$dj_name     = get_post_meta( $post->ID, '_dj_name', true );
		$bio_short   = get_post_meta( $post->ID, '_dj_bio_short', true );
		$bio_long    = get_post_meta( $post->ID, '_dj_bio', true );
		$statement   = get_post_meta( $post->ID, '_dj_statement', true );
		$image_id    = (int) get_post_meta( $post->ID, '_dj_image', true );
		$banner_id   = (int) get_post_meta( $post->ID, '_dj_banner', true );
		$about_photo = (int) get_post_meta( $post->ID, '_dj_about_photo', true );
		$about_video = get_post_meta( $post->ID, '_dj_about_video', true );
		$proj1       = get_post_meta( $post->ID, '_dj_original_project_1', true );
		$proj2       = get_post_meta( $post->ID, '_dj_original_project_2', true );
		$proj3       = get_post_meta( $post->ID, '_dj_original_project_3', true );

		/* ─── 2026-08-25 · CLOSING THE NO-INPUT GAP ───────────────────────
		   These six keys were added to apollo-core's MetaRegistry by the
		   21-mockup-field-contract "REGISTER-BEFORE-BUILD" pass, but no control
		   was ever added to go with them. Registered + unfillable = the hero
		   eyebrow, the stacked name lines and the footer image were guaranteed
		   to render empty in production forever. That chapter's own words:
		   "Shipping a section fed by a key with no input path is how the mockup
		   ends up permanently empty in production."

		   Two of them are OVERRIDES by design and must stay empty by default —
		   _dj_eyebrow falls back to home_city + the first two `sound` terms,
		   and _dj_name_lines falls back to splitting _dj_name. Filling them in
		   is an editorial act, so the placeholders show the derivation rather
		   than pre-seeding a value that would freeze the fallback. */
		$home_city     = get_post_meta( $post->ID, '_dj_home_city', true );
		$booking_stat  = get_post_meta( $post->ID, '_dj_booking_status', true );
		$eyebrow       = get_post_meta( $post->ID, '_dj_eyebrow', true );
		$footer_image  = (int) get_post_meta( $post->ID, '_dj_footer_image', true );
		$name_lines    = get_post_meta( $post->ID, '_dj_name_lines', true );
		if ( ! is_array( $name_lines ) ) {
			$name_lines = array();
		}
		$media_stats   = get_post_meta( $post->ID, '_dj_media_kit_stats', true );
		if ( ! is_array( $media_stats ) ) {
			$media_stats = array();
		}

		$booking_options = array(
			''          => __( '— Não informado (esconde o selo) —', 'apollo-djs' ),
			'open'      => __( 'Booking aberto', 'apollo-djs' ),
			'selective' => __( 'Booking seletivo', 'apollo-djs' ),
			'closed'    => __( 'Agenda fechada', 'apollo-djs' ),
		);
		?>
		<p>
			<label><?php esc_html_e( 'Nome artístico', 'apollo-djs' ); ?></label>
			<input type="text" name="_dj_name" value="<?php echo esc_attr( (string) $dj_name ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Se vazio, usa o título do post', 'apollo-djs' ); ?>">
			<span class="description"><?php esc_html_e( 'Exibido no cartão (hero, ev-top, rodapé). Meta: _dj_name', 'apollo-djs' ); ?></span>
		</p>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
			<p>
				<label><?php esc_html_e( 'Cidade base', 'apollo-djs' ); ?></label>
				<input type="text" name="_dj_home_city" value="<?php echo esc_attr( (string) $home_city ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Rio de Janeiro', 'apollo-djs' ); ?>">
				<span class="description"><?php esc_html_e( 'Fato sobre o artista — não é derivável do histórico de shows. Meta: _dj_home_city', 'apollo-djs' ); ?></span>
			</p>
			<p>
				<label><?php esc_html_e( 'Status de booking', 'apollo-djs' ); ?></label>
				<select name="_dj_booking_status" class="widefat">
					<?php foreach ( $booking_options as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ); ?>" <?php selected( (string) $booking_stat, $val ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<span class="description"><?php esc_html_e( 'Estado atual, separado de _dj_booking (que guarda o e-mail). O ano ao lado é derivado.', 'apollo-djs' ); ?></span>
			</p>
		</div>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
			<p>
				<label><?php esc_html_e( 'Eyebrow do hero (override)', 'apollo-djs' ); ?></label>
				<input type="text" name="_dj_eyebrow" value="<?php echo esc_attr( (string) $eyebrow ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Vazio = cidade base + 2 primeiros gêneros', 'apollo-djs' ); ?>">
				<span class="description"><?php esc_html_e( 'Deixe vazio para derivar automaticamente e evitar que as duas metades divirjam.', 'apollo-djs' ); ?></span>
			</p>
			<p>
				<label><?php esc_html_e( 'Quebra do nome no hero (override)', 'apollo-djs' ); ?></label>
				<input type="text" name="_dj_name_lines" value="<?php echo esc_attr( implode( ' | ', array_map( 'strval', $name_lines ) ) ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'LEO | JANEIRO', 'apollo-djs' ); ?>">
				<span class="description"><?php esc_html_e( 'Separe as linhas com "|". Vazio = divide o nome no primeiro espaço.', 'apollo-djs' ); ?></span>
			</p>
		</div>
		<?php /* Media-kit stats: free rows so a press kit can carry whatever
		         numbers it actually has, without inventing a fixed schema.
		         NO_EGO_COUNTERS still applies — this is press-kit material, not
		         a public vanity counter, and nothing renders it on the profile. */ ?>
		<p>
			<label><?php esc_html_e( 'Media kit — números (rótulo=valor por linha)', 'apollo-djs' ); ?></label>
			<textarea name="_dj_media_kit_stats" rows="3" class="widefat" placeholder="<?php esc_attr_e( "Shows em 2025=42\nCidades=9", 'apollo-djs' ); ?>"><?php
				$stat_lines = array();
			foreach ( $media_stats as $k => $v ) {
				$stat_lines[] = ( is_int( $k ) ? '' : $k . '=' ) . ( is_scalar( $v ) ? (string) $v : '' );
			}
				echo esc_textarea( implode( "\n", $stat_lines ) );
			?></textarea>
			<span class="description"><?php esc_html_e( 'Somente para o kit de imprensa — não aparece no perfil público.', 'apollo-djs' ); ?></span>
		</p>
		<p>
			<label><?php esc_html_e( 'Biografia Curta (máx. 280 caracteres)', 'apollo-djs' ); ?></label>
			<textarea name="_dj_bio_short" rows="3" maxlength="280" class="widefat"><?php echo esc_textarea( (string) $bio_short ); ?></textarea>
		</p>
		<p>
			<label><?php esc_html_e( 'Statement (seção word-fill)', 'apollo-djs' ); ?></label>
			<textarea name="_dj_statement" rows="3" class="widefat" placeholder="<?php esc_attr_e( 'Frase editorial pinada no scroll…', 'apollo-djs' ); ?>"><?php echo esc_textarea( (string) $statement ); ?></textarea>
		</p>
		<p>
			<label><?php esc_html_e( 'Biografia Completa (seção "Sobre")', 'apollo-djs' ); ?></label>
			<textarea name="_dj_bio" rows="6" class="widefat"><?php echo esc_textarea( (string) $bio_long ); ?></textarea>
		</p>
		<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
			<p>
				<label><?php esc_html_e( 'Projeto original 1', 'apollo-djs' ); ?></label>
				<input type="text" name="_dj_original_project_1" value="<?php echo esc_attr( (string) $proj1 ); ?>" class="widefat">
			</p>
			<p>
				<label><?php esc_html_e( 'Projeto original 2', 'apollo-djs' ); ?></label>
				<input type="text" name="_dj_original_project_2" value="<?php echo esc_attr( (string) $proj2 ); ?>" class="widefat">
			</p>
			<p>
				<label><?php esc_html_e( 'Projeto original 3', 'apollo-djs' ); ?></label>
				<input type="text" name="_dj_original_project_3" value="<?php echo esc_attr( (string) $proj3 ); ?>" class="widefat">
			</p>
		</div>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
			<p>
				<label><?php esc_html_e( 'Foto de Perfil / Hero (ID)', 'apollo-djs' ); ?></label><br>
				<?php if ( $image_id ) : ?>
					<img class="apl-dj-media-prev" src="<?php echo esc_url( (string) wp_get_attachment_image_url( $image_id, 'thumbnail' ) ); ?>" style="max-width:80px;margin-bottom:4px;display:block;object-fit:cover;object-position:center top;">
				<?php endif; ?>
				<input type="number" name="_dj_image" id="apl-dj-image-id" value="<?php echo esc_attr( (string) $image_id ); ?>" class="widefat" min="0">
				<button type="button" class="button button-small apl-dj-media-pick" data-target="apl-dj-image-id"><?php esc_html_e( 'Escolher', 'apollo-djs' ); ?></button>
			</p>
			<p>
				<label><?php esc_html_e( 'Banner / rodapé (ID)', 'apollo-djs' ); ?></label><br>
				<?php if ( $banner_id ) : ?>
					<img class="apl-dj-media-prev" src="<?php echo esc_url( (string) wp_get_attachment_image_url( $banner_id, 'thumbnail' ) ); ?>" style="max-width:80px;margin-bottom:4px;display:block;object-fit:cover;object-position:center top;">
				<?php endif; ?>
				<input type="number" name="_dj_banner" id="apl-dj-banner-id" value="<?php echo esc_attr( (string) $banner_id ); ?>" class="widefat" min="0">
				<button type="button" class="button button-small apl-dj-media-pick" data-target="apl-dj-banner-id"><?php esc_html_e( 'Escolher', 'apollo-djs' ); ?></button>
			</p>
			<p>
				<?php /* Distinct from _dj_banner: the banner sits at the top of
				         the profile, this one closes it. Registered since the
				         mockup pass but had no control until 2026-08-25. */ ?>
				<label><?php esc_html_e( 'Imagem de rodapé (ID)', 'apollo-djs' ); ?></label><br>
				<?php if ( $footer_image ) : ?>
					<img class="apl-dj-media-prev" src="<?php echo esc_url( (string) wp_get_attachment_image_url( $footer_image, 'thumbnail' ) ); ?>" style="max-width:80px;margin-bottom:4px;display:block;object-fit:cover;object-position:center top;">
				<?php endif; ?>
				<input type="number" name="_dj_footer_image" id="apl-dj-footer-id" value="<?php echo esc_attr( (string) $footer_image ); ?>" class="widefat" min="0">
				<button type="button" class="button button-small apl-dj-media-pick" data-target="apl-dj-footer-id"><?php esc_html_e( 'Escolher', 'apollo-djs' ); ?></button>
			</p>
		</div>
		<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
			<p>
				<label><?php esc_html_e( 'Foto "Sobre" (ID — diferente do hero)', 'apollo-djs' ); ?></label><br>
				<?php if ( $about_photo ) : ?>
					<img class="apl-dj-media-prev" src="<?php echo esc_url( (string) wp_get_attachment_image_url( $about_photo, 'thumbnail' ) ); ?>" style="max-width:80px;margin-bottom:4px;display:block;object-fit:cover;object-position:center top;">
				<?php endif; ?>
				<input type="number" name="_dj_about_photo" id="apl-dj-about-photo-id" value="<?php echo esc_attr( (string) $about_photo ); ?>" class="widefat" min="0">
				<button type="button" class="button button-small apl-dj-media-pick" data-target="apl-dj-about-photo-id"><?php esc_html_e( 'Escolher', 'apollo-djs' ); ?></button>
			</p>
			<p>
				<label><?php esc_html_e( 'Vídeo "Sobre" (URL, opcional)', 'apollo-djs' ); ?></label>
				<input type="url" name="_dj_about_video" value="<?php echo esc_attr( (string) $about_video ); ?>" class="widefat" placeholder="https://... (mp4/webm)">
			</p>
		</div>
		<script>
		(function($){
			/* Debug beacon removed 2026-08-20 (plan-001 · D-2). It POSTed every
			   metabox interaction to http://127.0.0.1:7514/ingest/… AND to
			   apollo/v1/_agent_debug carrying the editor's X-WP-Nonce, both wrapped
			   in .catch(){} so neither ever surfaced. apollo-events removed the
			   same beacons and the /_agent_debug route on 2026-08-17; this copy was
			   missed. Portal harness assertion E27 exists for exactly this.
			   $apollo_rule.data_flow.production: no debug output, ever. */
			function openImageFrame(targetId, previewImg) {
				if (typeof wp === 'undefined' || !wp.media) {
					window.alert('Biblioteca de mídia indisponível. Recarregue a página.');
					return;
				}
				var frame = wp.media({ title: 'Selecionar imagem', multiple: false, library: { type: 'image' } });
				frame.on('select', function(){
					var att = frame.state().get('selection').first().toJSON();
					var $input = $('#' + targetId);
					$input.val(att.id);
					var url = (att.sizes && att.sizes.thumbnail && att.sizes.thumbnail.url) || att.url;
					var $prev = $input.siblings('img.apl-dj-media-prev');
					if (!$prev.length && previewImg) { $prev = $(previewImg); }
					if ($prev.length) {
						$prev.attr('src', url).show();
					} else if (url) {
						$input.before('<img class="apl-dj-media-prev" src="' + url + '" style="max-width:80px;margin-bottom:4px;display:block;object-fit:cover;object-position:center top;">');
					}
				});
				frame.open();
			}
			$(document).on('click', '.apl-dj-media-pick', function(e){
				e.preventDefault();
				openImageFrame($(this).data('target'));
			});
		})(jQuery);
		</script>
		<?php
	}

	public function render_links( \WP_Post $post ): void {
		$fields = array(
			'_dj_website'          => array( '🌐', __( 'Website', 'apollo-djs' ), 'https://...' ),
			'_dj_instagram'        => array( '📷', __( 'Instagram (@handle)', 'apollo-djs' ), '@djname' ),
			'_dj_soundcloud'       => array( '☁', __( 'SoundCloud (URL)', 'apollo-djs' ), 'https://soundcloud.com/...' ),
			'_dj_spotify'          => array( '🎧', __( 'Spotify (URL)', 'apollo-djs' ), 'https://open.spotify.com/...' ),
			'_dj_bandcamp'         => array( '🎵', __( 'Bandcamp (URL)', 'apollo-djs' ), 'https://artista.bandcamp.com' ),
			'_dj_beatport'         => array( '💿', __( 'Beatport (URL)', 'apollo-djs' ), 'https://beatport.com/artist/...' ),
			'_dj_youtube'          => array( '▶', __( 'YouTube (URL)', 'apollo-djs' ), 'https://youtube.com/...' ),
			'_dj_mixcloud'         => array( '🔀', __( 'Mixcloud (URL)', 'apollo-djs' ), 'https://mixcloud.com/...' ),
			'_dj_resident_advisor' => array( '📅', __( 'Resident Advisor (URL)', 'apollo-djs' ), 'https://ra.co/dj/...' ),
			'_dj_facebook'         => array( '👍', __( 'Facebook (URL)', 'apollo-djs' ), 'https://facebook.com/...' ),
			'_dj_twitter'          => array( '𝕏', __( 'X / Twitter (URL)', 'apollo-djs' ), 'https://x.com/...' ),
			'_dj_tiktok'           => array( '🎬', __( 'TikTok (URL)', 'apollo-djs' ), 'https://tiktok.com/@...' ),
			'_dj_set_url'          => array( '🎚', __( 'DJ Set em destaque (URL)', 'apollo-djs' ), 'https://soundcloud.com/...' ),
			'_dj_mix_url'          => array( '🎶', __( 'Mix em destaque (URL)', 'apollo-djs' ), 'https://mixcloud.com/...' ),
		);
		echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">';
		foreach ( $fields as $key => $meta ) {
			$val  = get_post_meta( $post->ID, $key, true );
			$type = '_dj_instagram' === $key ? 'text' : 'url';
			printf(
				'<p><label>%1$s %2$s</label><input type="%3$s" name="%4$s" value="%5$s" class="widefat" placeholder="%6$s"></p>',
				esc_html( $meta[0] ),
				esc_html( $meta[1] ),
				esc_attr( $type ),
				esc_attr( $key ),
				esc_attr( (string) $val ),
				esc_attr( $meta[2] )
			);
		}
		echo '</div>';
	}

	public function render_tracks( \WP_Post $post ): void {
		$tracks = get_post_meta( $post->ID, '_dj_tracks', true );
		if ( ! \is_array( $tracks ) ) {
			$tracks = array();
		}
		?>
		<p class="description"><?php esc_html_e( 'Lançamentos da seção Out now!. Título, URL, duração e ano de postagem. O front exibe: "{ano} · RIO DE JANEIRO · {duração}".', 'apollo-djs' ); ?></p>
		<div id="apl-dj-tracks" data-empty="<?php esc_attr_e( 'Nenhuma faixa ainda.', 'apollo-djs' ); ?>">
			<?php
			$rows = ! empty( $tracks ) ? $tracks : array( array( 'title' => '', 'url' => '', 'duration' => '', 'year' => '' ) );
			foreach ( $rows as $t ) :
				// Legacy freeform meta → seed year/duration when missing
				$year     = (string) ( $t['year'] ?? '' );
				$duration = (string) ( $t['duration'] ?? '' );
				if ( ( '' === $year || '' === $duration ) && ! empty( $t['meta'] ) ) {
					$parts = \array_map( 'trim', \explode( '·', (string) $t['meta'] ) );
					if ( '' === $year && isset( $parts[0] ) && \preg_match( '/^\d{4}$/', $parts[0] ) ) {
						$year = $parts[0];
					}
					if ( '' === $duration && ! empty( $parts ) ) {
						$last = \end( $parts );
						if ( \is_string( $last ) && \preg_match( '/^\d+:\d{2}$/', $last ) ) {
							$duration = $last;
						}
					}
				}
				?>
				<div class="apl-dj-track-row" style="display:grid;grid-template-columns:1.4fr 1.6fr 0.7fr 0.55fr auto;gap:8px;margin-bottom:8px;align-items:center;">
					<input type="text" name="_dj_tracks_title[]" value="<?php echo esc_attr( (string) ( $t['title'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Título', 'apollo-djs' ); ?>" class="widefat">
					<input type="url" name="_dj_tracks_url[]" value="<?php echo esc_attr( (string) ( $t['url'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'URL da faixa', 'apollo-djs' ); ?>" class="widefat">
					<input type="text" name="_dj_tracks_duration[]" value="<?php echo esc_attr( $duration ); ?>" placeholder="<?php esc_attr_e( 'Duração (6:12)', 'apollo-djs' ); ?>" class="widefat">
					<input type="text" name="_dj_tracks_year[]" value="<?php echo esc_attr( $year ); ?>" placeholder="<?php esc_attr_e( 'Ano', 'apollo-djs' ); ?>" class="widefat" maxlength="4" inputmode="numeric">
					<button type="button" class="button button-small apl-dj-track-remove" aria-label="<?php esc_attr_e( 'Remover', 'apollo-djs' ); ?>">✕</button>
				</div>
			<?php endforeach; ?>
		</div>
		<button type="button" class="button" id="apl-dj-track-add">+ <?php esc_html_e( 'Adicionar faixa', 'apollo-djs' ); ?></button>
		<script>
		(function(){
			var wrap = document.getElementById('apl-dj-tracks');
			var add  = document.getElementById('apl-dj-track-add');
			if (!wrap || !add) return;
			add.addEventListener('click', function(){
				var row = wrap.querySelector('.apl-dj-track-row');
				var clone = row.cloneNode(true);
				clone.querySelectorAll('input').forEach(function(i){ i.value=''; });
				wrap.appendChild(clone);
			});
			wrap.addEventListener('click', function(e){
				if (!e.target.classList.contains('apl-dj-track-remove')) return;
				var rows = wrap.querySelectorAll('.apl-dj-track-row');
				if (rows.length > 1) e.target.closest('.apl-dj-track-row').remove();
				else e.target.closest('.apl-dj-track-row').querySelectorAll('input').forEach(function(i){ i.value=''; });
			});
		})();
		</script>
		<?php
	}

	public function render_gallery( \WP_Post $post ): void {
		$gallery = (array) get_post_meta( $post->ID, '_dj_gallery', true );
		$gallery = \array_filter( \array_map( 'intval', $gallery ) );
		$value   = \implode( ',', $gallery );
		?>
		<p>
			<label><?php esc_html_e( 'Galeria (IDs de imagens separados por vírgula)', 'apollo-djs' ); ?></label>
			<input type="text" name="_dj_gallery" id="apl-dj-gallery-ids" value="<?php echo esc_attr( $value ); ?>" class="widefat" placeholder="100,101,102">
			<button type="button" class="button button-small apl-dj-gallery-pick"><?php esc_html_e( 'Adicionar imagens', 'apollo-djs' ); ?></button>
		</p>
		<script>
		(function($){
			$(document).on('click', '.apl-dj-gallery-pick', function(e){
				e.preventDefault();
				if (typeof wp === 'undefined' || !wp.media) {
					window.alert('Biblioteca de mídia indisponível. Recarregue a página.');
					return;
				}
				var frame = wp.media({ title: 'Selecionar imagens', multiple: true, library: { type: 'image' } });
				frame.on('select', function(){
					var ids = frame.state().get('selection').map(function(a){ return a.id; });
					var input = document.getElementById('apl-dj-gallery-ids');
					var cur = input.value.trim();
					input.value = (cur ? cur + ',' : '') + ids.join(',');
				});
				frame.open();
			});
		})(jQuery);
		</script>
		<?php
	}

	public function render_booking( \WP_Post $post ): void {
		$booking = get_post_meta( $post->ID, '_dj_booking', true );
		$kit_url = get_post_meta( $post->ID, '_dj_media_kit_url', true );
		$rider   = get_post_meta( $post->ID, '_dj_rider_url', true );
		?>
		<p>
			<label>📩 <?php esc_html_e( 'E-mail de Booking', 'apollo-djs' ); ?></label>
			<input type="email" name="_dj_booking" value="<?php echo esc_attr( (string) $booking ); ?>" class="widefat" placeholder="booking@...">
			<span class="description"><?php esc_html_e( 'CTA "Contato booking" no cartão (mailto).', 'apollo-djs' ); ?></span>
		</p>
		<p>
			<label>📁 <?php esc_html_e( 'Kit Promo — URL Drive', 'apollo-djs' ); ?></label>
			<input type="url" name="_dj_media_kit_url" value="<?php echo esc_attr( (string) $kit_url ); ?>" class="widefat" placeholder="https://drive.google.com/...">
			<span class="description"><?php esc_html_e( 'Botão "Acessar Kit Promo" abre esta pasta.', 'apollo-djs' ); ?></span>
		</p>
		<p>
			<label>📄 <?php esc_html_e( 'Rider Técnico (URL)', 'apollo-djs' ); ?></label>
			<input type="url" name="_dj_rider_url" value="<?php echo esc_attr( (string) $rider ); ?>" class="widefat" placeholder="https://...">
		</p>
		<?php
	}

	public function render_config( \WP_Post $post ): void {
		$user_id  = (int) get_post_meta( $post->ID, '_dj_user_id', true );
		$verified = get_post_meta( $post->ID, '_dj_verified', true );
		?>
		<p>
			<label><?php esc_html_e( 'User ID vinculado', 'apollo-djs' ); ?></label>
			<input type="number" name="_dj_user_id" value="<?php echo esc_attr( (string) $user_id ); ?>" class="widefat" min="0">
			<span class="description"><?php esc_html_e( 'ID da conta WordPress do DJ', 'apollo-djs' ); ?></span>
		</p>
		<p>
			<label>
				<input type="checkbox" name="_dj_verified" value="1" <?php checked( $verified, '1' ); ?>>
				<?php esc_html_e( 'DJ Verificado', 'apollo-djs' ); ?>
			</label>
		</p>
		<?php
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::ACTION ) ) {
			return;
		}
		if ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( $post->post_type !== self::POST_TYPE ) {
			return;
		}

		// Textareas
		if ( isset( $_POST['_dj_bio_short'] ) ) {
			update_post_meta( $post_id, '_dj_bio_short', sanitize_textarea_field( wp_unslash( $_POST['_dj_bio_short'] ) ) );
		}
		if ( isset( $_POST['_dj_bio'] ) ) {
			update_post_meta( $post_id, '_dj_bio', sanitize_textarea_field( wp_unslash( $_POST['_dj_bio'] ) ) );
		}
		if ( isset( $_POST['_dj_statement'] ) ) {
			update_post_meta( $post_id, '_dj_statement', sanitize_textarea_field( wp_unslash( $_POST['_dj_statement'] ) ) );
		}

		// Plain strings
		$text_keys = array( '_dj_name', '_dj_instagram', '_dj_original_project_1', '_dj_original_project_2', '_dj_original_project_3' );
		foreach ( $text_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		// Email
		if ( isset( $_POST['_dj_booking'] ) ) {
			update_post_meta( $post_id, '_dj_booking', sanitize_email( wp_unslash( $_POST['_dj_booking'] ) ) );
		}

		// URLs
		$url_keys = array(
			'_dj_website', '_dj_soundcloud', '_dj_spotify', '_dj_youtube', '_dj_mixcloud',
			'_dj_bandcamp', '_dj_beatport', '_dj_resident_advisor', '_dj_facebook',
			'_dj_twitter', '_dj_tiktok', '_dj_set_url', '_dj_mix_url',
			'_dj_media_kit_url', '_dj_rider_url', '_dj_about_video',
		);
		foreach ( $url_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, esc_url_raw( wp_unslash( (string) $_POST[ $key ] ) ) );
			}
		}

		// Ints
		$int_keys = array( '_dj_image', '_dj_banner', '_dj_user_id', '_dj_about_photo', '_dj_footer_image' );
		foreach ( $int_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, absint( $_POST[ $key ] ) );
			}
		}

		/* ─── 2026-08-25 · save path for the previously unreachable keys ───
		   Inputs added in render_info(); without this block they would post and
		   be silently discarded, which is worse than having no field at all. */

		if ( isset( $_POST['_dj_home_city'] ) ) {
			update_post_meta( $post_id, '_dj_home_city', sanitize_text_field( wp_unslash( $_POST['_dj_home_city'] ) ) );
		}

		// Enum — an unknown value is dropped rather than stored, so the profile
		// can only ever render one of the three states the i18n map knows.
		if ( isset( $_POST['_dj_booking_status'] ) ) {
			$allowed_status = array( '', 'open', 'selective', 'closed' );
			$status         = sanitize_text_field( wp_unslash( $_POST['_dj_booking_status'] ) );
			if ( \in_array( $status, $allowed_status, true ) ) {
				if ( '' === $status ) {
					delete_post_meta( $post_id, '_dj_booking_status' );
				} else {
					update_post_meta( $post_id, '_dj_booking_status', $status );
				}
			}
		}

		/* Override keys — a blank box must DELETE, never store ''. These two
		   fall back to a derivation (eyebrow → city + sound terms; name_lines →
		   split _dj_name), and an empty-string row is still "set", which would
		   freeze the fallback off and leave the hero blank. */
		if ( isset( $_POST['_dj_eyebrow'] ) ) {
			$eyebrow_in = sanitize_text_field( wp_unslash( $_POST['_dj_eyebrow'] ) );
			if ( '' === $eyebrow_in ) {
				delete_post_meta( $post_id, '_dj_eyebrow' );
			} else {
				update_post_meta( $post_id, '_dj_eyebrow', $eyebrow_in );
			}
		}

		if ( isset( $_POST['_dj_name_lines'] ) ) {
			$lines_raw = sanitize_text_field( wp_unslash( $_POST['_dj_name_lines'] ) );
			$lines     = \array_values( \array_filter( \array_map( 'trim', \explode( '|', $lines_raw ) ), static function ( $l ) {
				return '' !== $l;
			} ) );
			if ( empty( $lines ) ) {
				delete_post_meta( $post_id, '_dj_name_lines' );
			} else {
				update_post_meta( $post_id, '_dj_name_lines', $lines );
			}
		}

		// Media-kit stats — "rótulo=valor" per line → associative array.
		// A line with no "=" is kept as a bare value so nothing typed is lost.
		if ( isset( $_POST['_dj_media_kit_stats'] ) ) {
			$stats_raw = sanitize_textarea_field( wp_unslash( $_POST['_dj_media_kit_stats'] ) );
			$stats     = array();
			foreach ( \preg_split( '/\R/', $stats_raw ) ?: array() as $line ) {
				$line = \trim( (string) $line );
				if ( '' === $line ) {
					continue;
				}
				if ( \str_contains( $line, '=' ) ) {
					list( $k, $v ) = \array_map( 'trim', \explode( '=', $line, 2 ) );
					if ( '' !== $k ) {
						$stats[ $k ] = $v;
					}
				} else {
					$stats[] = $line;
				}
			}
			if ( empty( $stats ) ) {
				delete_post_meta( $post_id, '_dj_media_kit_stats' );
			} else {
				update_post_meta( $post_id, '_dj_media_kit_stats', $stats );
			}
		}

		// Gallery (CSV → array ints)
		if ( isset( $_POST['_dj_gallery'] ) ) {
			$gallery_raw = sanitize_text_field( wp_unslash( $_POST['_dj_gallery'] ) );
			$gallery     = \array_values( \array_filter( \array_map( 'absint', \explode( ',', $gallery_raw ) ) ) );
			update_post_meta( $post_id, '_dj_gallery', $gallery );
		}

		// Tracks repeater (parallel arrays → list of {title, url, duration, year})
		if ( isset( $_POST['_dj_tracks_title'] ) && \is_array( $_POST['_dj_tracks_title'] ) ) {
			$titles    = \array_map( 'sanitize_text_field', \array_map( 'wp_unslash', (array) $_POST['_dj_tracks_title'] ) );
			$urls      = isset( $_POST['_dj_tracks_url'] ) ? \array_map( 'esc_url_raw', \array_map( 'wp_unslash', (array) $_POST['_dj_tracks_url'] ) ) : array();
			$durations = isset( $_POST['_dj_tracks_duration'] ) ? \array_map( 'sanitize_text_field', \array_map( 'wp_unslash', (array) $_POST['_dj_tracks_duration'] ) ) : array();
			$years     = isset( $_POST['_dj_tracks_year'] ) ? \array_map( 'sanitize_text_field', \array_map( 'wp_unslash', (array) $_POST['_dj_tracks_year'] ) ) : array();
			$tracks    = array();
			foreach ( $titles as $i => $t ) {
				$t = (string) $t;
				$u = (string) ( $urls[ $i ] ?? '' );
				if ( '' === $t && '' === $u ) {
					continue; // skip empty rows
				}
				$year = \preg_replace( '/\D/', '', (string) ( $years[ $i ] ?? '' ) );
				if ( \strlen( $year ) > 4 ) {
					$year = \substr( $year, 0, 4 );
				}
				$tracks[] = array(
					'title'    => $t,
					'url'      => $u,
					'duration' => (string) ( $durations[ $i ] ?? '' ),
					'year'     => $year,
				);
			}
			update_post_meta( $post_id, '_dj_tracks', $tracks );
		}

		// Bool
		$verified = isset( $_POST['_dj_verified'] ) ? '1' : '';
		update_post_meta( $post_id, '_dj_verified', $verified );

		/* `sound` multi-select (2026-08-25). Saved from this handler rather than
		   a second save_post hook, so there is exactly one saver on this screen.
		   Its own nonce means a request without the control is a clean no-op.
		   Note the isset() sits OUTSIDE: an empty multi-select posts NOTHING at
		   all, so "user deselected everything" and "control absent" look
		   identical in $_POST — the nonce is what tells them apart. */
		if ( isset( $_POST['apollo_dj_tax_nonce'] ) &&
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_dj_tax_nonce'] ) ), 'apollo_dj_tax_save' ) ) {

			$sound_ids = isset( $_POST['apollo_dj_sound'] ) ? array_map( 'absint', (array) $_POST['apollo_dj_sound'] ) : array();
			$sound_ids = array_values( array_filter( $sound_ids ) );

			// Only keep ids that really are terms of this taxonomy.
			$valid = array();
			foreach ( $sound_ids as $tid ) {
				$term = get_term( $tid, APOLLO_DJ_TAX_SOUND );
				if ( $term && ! is_wp_error( $term ) ) {
					$valid[] = (int) $tid;
				}
			}

			wp_set_object_terms( $post_id, $valid, APOLLO_DJ_TAX_SOUND, false );
		}
	}
}