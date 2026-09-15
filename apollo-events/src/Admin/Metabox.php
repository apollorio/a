<?php

/**
 * Metabox — Admin metaboxes para o CPT "event"
 *
 * Cobre TODOS os 17 meta keys + 5 taxonomias do apollo-registry.json
 * Aba "Dados do Evento" no editor WP (clássico + bloco).
 *
 * @package Apollo\Event\Admin
 */

namespace Apollo\Event\Admin;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

final class Metabox {

	private const POST_TYPE = 'event';
	private const NONCE     = 'apollo_event_metabox_nonce';
	private const ACTION    = 'apollo_event_save_meta';

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	// ─── Registro ─────────────────────────────────────────────────────────────

	public function register(): void {
		add_meta_box(
			'apollo-event-dates',
			__( '📅 Data & Horário', 'apollo-events' ),
			array( $this, 'render_dates' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
		add_meta_box(
			'apollo-event-local',
			__( '📍 Local & Lineup', 'apollo-events' ),
			array( $this, 'render_local_lineup' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
		add_meta_box(
			'apollo-event-tickets',
			__( '🎟 Ingressos', 'apollo-events' ),
			array( $this, 'render_tickets' ),
			self::POST_TYPE,
			'normal',
			'default'
		);
		add_meta_box(
			'apollo-event-media',
			__( '🖼 Mídia & Galeria', 'apollo-events' ),
			array( $this, 'render_media' ),
			self::POST_TYPE,
			'normal',
			'default'
		);
		add_meta_box(
			'apollo-event-classify',
			__( '🏷 Taxonomias', 'apollo-events' ),
			array( $this, 'render_taxonomies' ),
			self::POST_TYPE,
			'side',
			'default'
		);
		add_meta_box(
			'apollo-event-config',
			__( '⚙ Configurações', 'apollo-events' ),
			array( $this, 'render_config' ),
			self::POST_TYPE,
			'side',
			'default'
		);
	}

	// ─── Assets ───────────────────────────────────────────────────────────────

	public function enqueue_assets( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== self::POST_TYPE ) {
			return;
		}
		wp_enqueue_style(
			'apollo-event-metabox',
			plugins_url( 'assets/css/admin-metabox.css', APOLLO_EVENT_FILE ),
			array(),
			APOLLO_EVENT_VERSION
		);
		wp_enqueue_style(
			'apollo-lineup-builder',
			plugins_url( 'assets/css/lineup-builder.css', APOLLO_EVENT_FILE ),
			array(),
			APOLLO_EVENT_VERSION
		);
		wp_enqueue_script(
			'apollo-lineup-builder',
			plugins_url( 'assets/js/lineup-builder.js', APOLLO_EVENT_FILE ),
			array(),
			APOLLO_EVENT_VERSION,
			true
		);
		wp_enqueue_media();
	}

	// ─── Renders ──────────────────────────────────────────────────────────────

	public function render_dates( \WP_Post $post ): void {
		wp_nonce_field( self::ACTION, self::NONCE );
		$s_date = get_post_meta( $post->ID, '_event_start_date', true );
		$e_date = get_post_meta( $post->ID, '_event_end_date', true );
		$s_time = get_post_meta( $post->ID, '_event_start_time', true );
		$e_time = get_post_meta( $post->ID, '_event_end_time', true );
		?>
		<div class="apl-mb-grid two">
			<p>
				<label><?php esc_html_e( 'Data início *', 'apollo-events' ); ?></label>
				<input type="date" name="_event_start_date" value="<?php echo esc_attr( $s_date ); ?>" class="widefat" required>
			</p>
			<p>
				<label><?php esc_html_e( 'Data fim', 'apollo-events' ); ?></label>
				<input type="date" name="_event_end_date" value="<?php echo esc_attr( $e_date ); ?>" class="widefat">
			</p>
			<p>
				<label><?php esc_html_e( 'Hora início', 'apollo-events' ); ?></label>
				<input type="time" name="_event_start_time" value="<?php echo esc_attr( $s_time ); ?>" class="widefat">
			</p>
			<p>
				<label><?php esc_html_e( 'Hora fim', 'apollo-events' ); ?></label>
				<input type="time" name="_event_end_time" value="<?php echo esc_attr( $e_time ); ?>" class="widefat">
			</p>
		</div>
		<?php
	}

	public function render_local_lineup( \WP_Post $post ): void {
		$loc_id  = (int) get_post_meta( $post->ID, '_event_loc_id', true );
		$dj_ids  = (array) get_post_meta( $post->ID, '_event_dj_ids', true );
		$dj_ids  = \array_map( 'intval', $dj_ids );
		$slots   = get_post_meta( $post->ID, '_event_dj_slots', true );
		if ( ! \is_array( $slots ) ) {
			$slots = array();
		}

		$locals = get_posts( array( 'post_type' => \defined( 'APOLLO_LOCAL_CPT' ) ? APOLLO_LOCAL_CPT : 'local', 'posts_per_page' => 500, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish' ) );
		$djs    = get_posts( array( 'post_type' => 'dj', 'posts_per_page' => 500, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish' ) );

		// Build existing slots for JS init
		$existing_slots_json = wp_json_encode( \array_map( function( $s ) {
			return array(
				'dj_id'      => absint( $s['dj_id'] ?? 0 ),
				'start_time' => sanitize_text_field( $s['start_time'] ?? '' ),
				'end_time'   => sanitize_text_field( $s['end_time'] ?? '' ),
				'badge'      => sanitize_text_field( $s['badge'] ?? '' ),
			);
		}, $slots ) );

		// Build DJ list for JS
		$dj_list_json = wp_json_encode( \array_map( function( $dj ) {
			return array(
				'id'    => $dj->ID,
				'name'  => $dj->post_title,
				'thumb' => get_the_post_thumbnail_url( $dj->ID, 'thumbnail' ) ?: '',
			);
		}, $djs ) );
		?>
		<p>
			<label><?php esc_html_e( 'Local do Evento', 'apollo-events' ); ?></label>
			<select name="_event_loc_id" class="widefat">
				<option value=""><?php esc_html_e( '— Selecione —', 'apollo-events' ); ?></option>
				<?php foreach ( $locals as $loc ) : ?>
					<option value="<?php echo esc_attr( (string) $loc->ID ); ?>" <?php selected( $loc_id, $loc->ID ); ?>>
						<?php echo esc_html( $loc->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . ( \defined( 'APOLLO_LOCAL_CPT' ) ? APOLLO_LOCAL_CPT : 'local' ) ) ); ?>" target="_blank" style="display:inline-block;margin-top:4px;font-size:12px;color:FF9820;">
					+ <?php esc_html_e( 'Cadastrar novo Local', 'apollo-events' ); ?>
				</a>
			<?php endif; ?>
		</p>

		<p><strong><?php esc_html_e( 'DJs (Lineup) — arraste para reordenar, defina horários', 'apollo-events' ); ?></strong></p>

		<div class="lu-builder" id="lineup-builder-admin">
			<div class="lu-search-wrap" style="position:relative;">
				<input type="text" class="lu-search" placeholder="<?php esc_attr_e( 'Buscar DJ...', 'apollo-events' ); ?>" style="width:100%;padding:6px 10px;border:1px solid #dcdcde;border-radius:4px;">
			</div>
			<div class="lu-options">
				<?php foreach ( $djs as $dj ) :
					$dj_thumb = get_the_post_thumbnail_url( $dj->ID, 'thumbnail' ) ?: '';
				?>
					<div class="lu-opt"
						 data-id="<?php echo esc_attr( (string) $dj->ID ); ?>"
						 data-name="<?php echo esc_attr( $dj->post_title ); ?>"
						 data-thumb="<?php echo esc_url( $dj_thumb ); ?>">
						<?php if ( $dj_thumb ) : ?>
							<img src="<?php echo esc_url( $dj_thumb ); ?>" class="lu-opt__thumb" alt="" style="width:20px;height:20px;border-radius:50%;object-fit:cover;">
						<?php endif; ?>
						<?php echo esc_html( $dj->post_title ); ?>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="lu-selected"
				 data-empty="<?php esc_attr_e( 'Clique em um DJ para adicionar ao lineup', 'apollo-events' ); ?>">
			</div>
			<!-- Hidden fields read by save() -->
			<input type="hidden" name="_event_dj_ids_json" class="lu-hidden-ids" value="<?php echo esc_attr( wp_json_encode( $dj_ids ) ); ?>">
			<input type="hidden" name="_event_dj_slots" class="lu-hidden-slots" value="<?php echo esc_attr( $existing_slots_json ); ?>">
		</div>

		<?php if ( current_user_can( 'manage_options' ) ) : ?>
			<p>
				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=dj' ) ); ?>" target="_blank" style="font-size:12px;color:FF9820;">
					+ <?php esc_html_e( 'Cadastrar novo DJ', 'apollo-events' ); ?>
				</a>
			</p>
		<?php endif; ?>

		<script>
		(function(){
			if (typeof ApolloLineupBuilder === 'undefined') return;
			var djData = <?php echo $dj_list_json; // Already escaped per wp_json_encode ?>;
			var existingSlots = <?php echo $existing_slots_json; ?>;
			new ApolloLineupBuilder('#lineup-builder-admin', djData, existingSlots);
		})();
		</script>
		<?php
	}

	public function render_tickets( \WP_Post $post ): void {
		$ticket_url    = get_post_meta( $post->ID, '_event_ticket_url', true );
		$ticket_price  = get_post_meta( $post->ID, '_event_ticket_price', true );
		$ticket_status = get_post_meta( $post->ID, '_event_ticket_status', true ) ?: 'available';
		$coupon_code   = get_post_meta( $post->ID, '_event_coupon_code', true );
		$list_url      = get_post_meta( $post->ID, '_event_list_url', true );
		$lista_cta     = get_post_meta( $post->ID, '_event_lista_cta_label', true );

		// Early Bird e Listas — unified repeater, SAME data contract as the
		// frontend /novo-evento form. If this event was never saved under the
		// new UI, seed the rows from the legacy toggles so nothing is lost.
		if ( metadata_exists( 'post', $post->ID, '_event_access_buttons' ) ) {
			$access_buttons = get_post_meta( $post->ID, '_event_access_buttons', true );
			$access_buttons = \is_array( $access_buttons ) ? $access_buttons : array();
		} else {
			$access_buttons = array();
			if ( (string) get_post_meta( $post->ID, '_event_earlybird_enabled', true ) === '1' ) {
				$access_buttons[] = array(
					'kind'  => 'ticket',
					'style' => 'soft',
					'label' => (string) get_post_meta( $post->ID, '_event_earlybird_name', true ) ?: __( 'Early Bird', 'apollo-events' ),
					'sub'   => (string) get_post_meta( $post->ID, '_event_earlybird_sub', true ),
					'url'   => (string) get_post_meta( $post->ID, '_event_earlybird_url', true ),
				);
			}
			if ( (string) get_post_meta( $post->ID, '_event_lista_geral_enabled', true ) === '1' ) {
				$access_buttons[] = array(
					'kind'  => 'lista',
					'style' => 'lista',
					'label' => __( 'Lista Geral', 'apollo-events' ),
					'sub'   => (string) get_post_meta( $post->ID, '_event_lista_geral_sub', true ),
					'url'   => '',
				);
			}
			if ( (string) get_post_meta( $post->ID, '_event_lista_fem_enabled', true ) === '1' ) {
				$access_buttons[] = array(
					'kind'  => 'lista',
					'style' => 'fem',
					'label' => __( 'Lista Feminina', 'apollo-events' ),
					'sub'   => (string) get_post_meta( $post->ID, '_event_lista_fem_sub', true ),
					'url'   => '',
				);
			}
		}
		?>
		<div class="apl-mb-grid two">
			<p>
				<label><?php esc_html_e( 'URL dos Ingressos', 'apollo-events' ); ?></label>
				<input type="url" name="_event_ticket_url" value="<?php echo esc_attr( $ticket_url ); ?>" class="widefat" placeholder="https://...">
			</p>
			<p>
				<label><?php esc_html_e( 'Preço', 'apollo-events' ); ?></label>
				<input type="text" name="_event_ticket_price" value="<?php echo esc_attr( $ticket_price ); ?>" class="widefat" placeholder="R$ 50,00">
			</p>
			<p>
				<label><?php esc_html_e( 'Status do ingresso', 'apollo-events' ); ?></label>
				<select name="_event_ticket_status" class="widefat">
					<option value="free" <?php selected( $ticket_status, 'free' ); ?>><?php esc_html_e( 'Gratuito', 'apollo-events' ); ?></option>
					<option value="available" <?php selected( $ticket_status, 'available' ); ?>><?php esc_html_e( 'Disponível', 'apollo-events' ); ?></option>
					<option value="soldout_soon" <?php selected( $ticket_status, 'soldout_soon' ); ?>><?php esc_html_e( 'Sold-out soon', 'apollo-events' ); ?></option>
					<option value="sold_out" <?php selected( $ticket_status, 'sold_out' ); ?>><?php esc_html_e( 'Sold-out', 'apollo-events' ); ?></option>
				</select>
			</p>
			<p>
				<label><?php esc_html_e( 'Código de Cupom', 'apollo-events' ); ?></label>
				<input type="text" name="_event_coupon_code" value="<?php echo esc_attr( $coupon_code ); ?>" class="widefat" placeholder="APOLLO20">
			</p>
			<p>
				<label><?php esc_html_e( 'URL Lista Amiga', 'apollo-events' ); ?></label>
				<input type="url" name="_event_list_url" value="<?php echo esc_attr( $list_url ); ?>" class="widefat" placeholder="https://...">
			</p>
			<p>
				<label><?php esc_html_e( 'Texto CTA da Lista', 'apollo-events' ); ?></label>
				<input type="text" name="_event_lista_cta_label" value="<?php echo esc_attr( $lista_cta ); ?>" class="widefat" placeholder="Entrar para uma Lista">
			</p>
		</div>

		<hr>
		<p><strong><?php esc_html_e( 'Early Bird e Listas', 'apollo-events' ); ?></strong><br>
		<span class="description"><?php esc_html_e( 'Mesmos botões do formulário front-end (/novo-evento). Linhas sem texto são descartadas ao salvar.', 'apollo-events' ); ?></span></p>

		<div id="apl-axb-rows">
			<?php
			$axb_rows = $access_buttons ? $access_buttons : array();
			$axb_rows[] = array(); // one trailing empty row as "add new"
			foreach ( $axb_rows as $btn ) :
				$b_kind  = ( $btn['kind'] ?? 'ticket' ) === 'lista' ? 'lista' : 'ticket';
				$b_style = \in_array( ( $btn['style'] ?? '' ), array( 'main', 'soft', 'lista', 'fem', 'cta' ), true ) ? $btn['style'] : 'soft';
				?>
			<div class="apl-axb-row" style="display:grid;grid-template-columns:110px 110px 1fr 1fr 1.2fr 30px;gap:6px;align-items:center;margin-bottom:6px;">
				<select name="apl_axb_kind[]">
					<option value="ticket" <?php selected( $b_kind, 'ticket' ); ?>><?php esc_html_e( '🎟 Ingresso', 'apollo-events' ); ?></option>
					<option value="lista" <?php selected( $b_kind, 'lista' ); ?>><?php esc_html_e( '📋 Lista', 'apollo-events' ); ?></option>
				</select>
				<select name="apl_axb_style[]">
					<option value="main" <?php selected( $b_style, 'main' ); ?>><?php esc_html_e( 'Principal', 'apollo-events' ); ?></option>
					<option value="soft" <?php selected( $b_style, 'soft' ); ?>><?php esc_html_e( 'Early Bird (suave)', 'apollo-events' ); ?></option>
					<option value="lista" <?php selected( $b_style, 'lista' ); ?>><?php esc_html_e( 'Lista', 'apollo-events' ); ?></option>
					<option value="fem" <?php selected( $b_style, 'fem' ); ?>><?php esc_html_e( 'Lista Fem', 'apollo-events' ); ?></option>
					<option value="cta" <?php selected( $b_style, 'cta' ); ?>><?php esc_html_e( 'CTA Lista (largo)', 'apollo-events' ); ?></option>
				</select>
				<input type="text" name="apl_axb_label[]" value="<?php echo esc_attr( (string) ( $btn['label'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Texto do botão', 'apollo-events' ); ?>">
				<input type="text" name="apl_axb_sub[]" value="<?php echo esc_attr( (string) ( $btn['sub'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Condição (opcional)', 'apollo-events' ); ?>">
				<input type="url" name="apl_axb_url[]" value="<?php echo esc_attr( (string) ( $btn['url'] ?? '' ) ); ?>" placeholder="https://...">
				<button type="button" class="button-link apl-axb-rm" title="<?php esc_attr_e( 'Remover', 'apollo-events' ); ?>" style="color:#b32d2e;">&times;</button>
			</div>
			<?php endforeach; ?>
		</div>
		<p><button type="button" class="button button-small" id="apl-axb-add">+ <?php esc_html_e( 'Novo Ticket ou Lista', 'apollo-events' ); ?></button></p>
		<script>
		(function(){
			var box = document.getElementById('apl-axb-rows');
			var add = document.getElementById('apl-axb-add');
			if (!box || !add) return;
			box.addEventListener('click', function(e){
				if (!e.target.classList.contains('apl-axb-rm')) return;
				e.preventDefault();
				var rows = box.querySelectorAll('.apl-axb-row');
				var row = e.target.closest('.apl-axb-row');
				if (rows.length > 1) { row.remove(); }
				else { row.querySelectorAll('input').forEach(function(i){ i.value = ''; }); }
			});
			add.addEventListener('click', function(e){
				e.preventDefault();
				var first = box.querySelector('.apl-axb-row');
				var clone = first.cloneNode(true);
				clone.querySelectorAll('input').forEach(function(i){ i.value = ''; });
				clone.querySelectorAll('select').forEach(function(s){ s.selectedIndex = 0; });
				box.appendChild(clone);
			});
		})();
		</script>
		<?php
	}

	public function render_media( \WP_Post $post ): void {
		$video_url = get_post_meta( $post->ID, '_event_video_url', true );
		$audio_url = get_post_meta( $post->ID, '_event_audio_url', true );
		$gallery   = (array) get_post_meta( $post->ID, '_event_gallery', true );
		$gallery   = \array_filter( \array_map( 'intval', $gallery ) );
		$gallery_v = \implode( ',', $gallery );
		?>
		<p class="description">
			<?php esc_html_e( 'A capa (imagem destacada) fica no box Publicar, ao lado de Salvar — é a mesma imagem do card WhatsApp / Twitter.', 'apollo-events' ); ?>
		</p>
		<p>
			<label><?php esc_html_e( 'Cor de Fundo do Hero (fallback)', 'apollo-events' ); ?></label>
			<input type="color" name="_event_bg_color" value="<?php echo esc_attr( preg_match( '/^#[0-9a-fA-F]{3,6}$/', (string) get_post_meta( $post->ID, '_event_bg_color', true ) ) ? get_post_meta( $post->ID, '_event_bg_color', true ) : '#0a0a0a' ); ?>" style="width:60px;height:34px;padding:2px;vertical-align:middle;">
			<span class="description"><?php esc_html_e( 'Base do hero — capa e vídeo têm prioridade sobre a cor.', 'apollo-events' ); ?></span>
		</p>
		<p>
			<label><?php esc_html_e( 'Vídeo Promocional (YouTube / .mp4 .webm .mov)', 'apollo-events' ); ?></label>
			<input type="url" name="_event_video_url" value="<?php echo esc_attr( $video_url ); ?>" class="widefat" placeholder="YouTube ou https://…/video.mp4|.webm|.mov">
		</p>
		<p>
			<label><?php esc_html_e( 'Áudio / Spot (URL)', 'apollo-events' ); ?></label>
			<input type="url" name="_event_audio_url" value="<?php echo esc_attr( $audio_url ); ?>" class="widefat" placeholder="https://open.spotify.com/...">
		</p>
		<p>
			<label><?php esc_html_e( 'Galeria (IDs separados por vírgula, máx 3)', 'apollo-events' ); ?></label>
			<input type="text" name="_event_gallery" id="apl-event-gallery-ids" value="<?php echo esc_attr( $gallery_v ); ?>" class="widefat" placeholder="100,101,102">
			<button type="button" class="button button-small apl-media-gallery-pick"><?php esc_html_e( 'Escolher imagens', 'apollo-events' ); ?></button>
		</p>
		<script>
		(function($){
			/* Debug beacon removed 2026-08-17 — POSTed to http://127.0.0.1:7514
			   from the editor's browser on every event edit screen. */
			window.__apolloAgentDbg = window.__apolloAgentDbg || function(){};
			function ensureMedia() {
				if (typeof wp === 'undefined' || !wp.media) {
					window.alert('Biblioteca de mídia indisponível. Recarregue a página.');
					return false;
				}
				return true;
			}
			$(document).on('click', '.apl-media-pick', function(e){
				e.preventDefault();
				// #region agent log
				window.__apolloAgentDbg({runId:'pre-fix',hypothesisId:'A',location:'Metabox.php:event-banner-pick',message:'Escolher banner click',data:{hasWp:typeof wp!=='undefined',hasMedia:!!(typeof wp!=='undefined'&&wp.media),target:$(this).data('target')||null}});
				// #endregion
				if (!ensureMedia()) { return; }
				var targetId = $(this).data('target');
				var frame = wp.media({ title: 'Selecionar imagem', multiple: false, library: { type: 'image' } });
				frame.on('select', function(){
					var att = frame.state().get('selection').first().toJSON();
					var $input = $('#' + targetId);
					$input.val(att.id);
					// #region agent log
					window.__apolloAgentDbg({runId:'pre-fix',hypothesisId:'C',location:'Metabox.php:event-banner-select',message:'banner selected',data:{id:att.id,inputVal:$input.val(),hasUrl:!!att.url}});
					// #endregion
					var url = (att.sizes && att.sizes.thumbnail && att.sizes.thumbnail.url) || att.url;
					var $prev = $input.siblings('img.apl-event-media-prev');
					if ($prev.length) {
						$prev.attr('src', url).show();
					} else if (url) {
						$input.before('<img class="apl-event-media-prev" src="' + url + '" style="max-width:100px;margin-bottom:6px;display:block;">');
					}
				});
				frame.open();
			});
			$(document).on('click', '.apl-media-gallery-pick', function(e){
				e.preventDefault();
				if (!ensureMedia()) { return; }
				var frame = wp.media({ title: 'Galeria do evento', multiple: true, library: { type: 'image' } });
				frame.on('select', function(){
					var ids = frame.state().get('selection').map(function(a){ return a.id; }).slice(0, 3);
					var input = document.getElementById('apl-event-gallery-ids');
					input.value = ids.join(',');
				});
				frame.open();
			});
		})(jQuery);
		</script>
		<?php
	}

	public function render_taxonomies( \WP_Post $post ): void {
		/*
		 * sound + season are owned by apollo-lux-panels (multi-select chip picker
		 * for genres). Keeping them here dual-wrote the same terms and the save()
		 * else-branch wiped Lux multi-select on every Gutenberg meta-box POST.
		 */
		$taxonomies = array(
			'event_category' => __( 'Categorias', 'apollo-events' ),
			'event_type'     => __( 'Tipos', 'apollo-events' ),
			'event_tag'      => __( 'Tags', 'apollo-events' ),
		);

		echo '<input type="hidden" name="apl_tax_present" value="1">';

		foreach ( $taxonomies as $tax_slug => $tax_label ) :
			$all_terms     = get_terms( array( 'taxonomy' => $tax_slug, 'hide_empty' => false ) );
			$current_terms = wp_get_post_terms( $post->ID, $tax_slug, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $all_terms ) ) {
				continue;
			}
			?>
			<p>
				<strong><?php echo esc_html( $tax_label ); ?></strong><br>
				<?php foreach ( $all_terms as $term ) : ?>
					<label style="display:block;margin:2px 0;">
						<input type="checkbox"
							name="apl_tax[<?php echo esc_attr( $tax_slug ); ?>][]"
							value="<?php echo esc_attr( (string) $term->term_id ); ?>"
							<?php checked( \in_array( $term->term_id, (array) $current_terms, true ) ); ?>>
						<?php echo esc_html( $term->name ); ?>
					</label>
				<?php endforeach; ?>
			</p>
		<?php endforeach;
	}

	public function render_config( \WP_Post $post ): void {
		$privacy     = get_post_meta( $post->ID, '_event_privacy', true ) ?: 'public';
		$status      = get_post_meta( $post->ID, '_event_status', true ) ?: 'scheduled';
		$is_gone     = get_post_meta( $post->ID, '_event_is_gone', true );
		$highlighted = get_post_meta( $post->ID, '_event_highlighted', true );
		$tbtn        = get_post_meta( $post->ID, '_event_ticket_btn_style', true ) ?: 'main';
		$lbtn        = get_post_meta( $post->ID, '_event_list_btn_style', true ) ?: 'lista';
		/*
		 * ONE INPUT PER ELEMENT (2026-08-29).
		 *
		 * Before this, an editor saw the button style TWICE: here as a single
		 * event-wide dropdown, and again per-row inside "Early Bird e Listas"
		 * (render_tickets(), the _event_access_buttons repeater) a few metabox
		 * sections up. They were never in sync — the repeater's per-row style
		 * is what actually renders once an event has been touched under the
		 * new UI (functions.php's metadata_exists('_event_access_buttons')
		 * migration marker), so these two selects kept accepting input that
		 * silently did nothing for any migrated event.
		 *
		 * Same marker, same rule as the frontend: once an event has a saved
		 * _event_access_buttons value (even []), these globals are retired for
		 * it and the repeater is the only place style lives. An event never
		 * touched under the new UI still needs them — hence the condition, not
		 * a flat removal.
		 */
		$axb_migrated = metadata_exists( 'post', $post->ID, '_event_access_buttons' );
		?>
		<p style="background:#fff8ee;border:1px solid #ffd28a;border-radius:4px;padding:8px 10px;">
			<label>
				<input type="checkbox" name="_event_highlighted" value="1" <?php checked( $highlighted, '1' ); ?>>
				<strong>⭐ <?php esc_html_e( 'Destacar (Highlighted)', 'apollo-events' ); ?></strong>
			</label><br>
			<span class="description"><?php esc_html_e( 'Eventos marcados aqui entram no slider de destaque (hero) das telas de Eventos.', 'apollo-events' ); ?></span>
		</p>
		<?php if ( $axb_migrated ) : ?>
		<p class="description"><?php esc_html_e( 'Estilo de botão: definido por linha em "Early Bird e Listas" acima — este evento já usa o repetidor unificado.', 'apollo-events' ); ?></p>
		<?php else : ?>
		<p>
			<label><?php esc_html_e( 'Estilo botão Ingresso', 'apollo-events' ); ?></label>
			<select name="_event_ticket_btn_style" class="widefat">
				<option value="main" <?php selected( $tbtn, 'main' ); ?>><?php esc_html_e( 'Principal (destaque)', 'apollo-events' ); ?></option>
				<option value="soft" <?php selected( $tbtn, 'soft' ); ?>><?php esc_html_e( 'Suave', 'apollo-events' ); ?></option>
			</select>
		</p>
		<p>
			<label><?php esc_html_e( 'Estilo botão Lista', 'apollo-events' ); ?></label>
			<select name="_event_list_btn_style" class="widefat">
				<option value="lista" <?php selected( $lbtn, 'lista' ); ?>><?php esc_html_e( 'Lista', 'apollo-events' ); ?></option>
				<option value="fem" <?php selected( $lbtn, 'fem' ); ?>><?php esc_html_e( 'Lista Fem', 'apollo-events' ); ?></option>
			</select>
		</p>
		<?php endif; ?><?php
		?>
		<p>
			<label><?php esc_html_e( 'Privacidade', 'apollo-events' ); ?></label>
			<select name="_event_privacy" class="widefat">
				<option value="public"  <?php selected( $privacy, 'public' ); ?>><?php esc_html_e( 'Público', 'apollo-events' ); ?></option>
				<option value="private" <?php selected( $privacy, 'private' ); ?>><?php esc_html_e( 'Privado', 'apollo-events' ); ?></option>
				<option value="invite"  <?php selected( $privacy, 'invite' ); ?>><?php esc_html_e( 'Apenas Convidados', 'apollo-events' ); ?></option>
			</select>
		</p>
		<p>
			<label><?php esc_html_e( 'Status', 'apollo-events' ); ?></label>
			<select name="_event_status" class="widefat">
				<option value="scheduled"  <?php selected( $status, 'scheduled' ); ?>><?php esc_html_e( 'Agendado', 'apollo-events' ); ?></option>
				<option value="ongoing"    <?php selected( $status, 'ongoing' ); ?>><?php esc_html_e( 'Save the date', 'apollo-events' ); ?></option>
				<option value="postponed"  <?php selected( $status, 'postponed' ); ?>><?php esc_html_e( 'Adiado', 'apollo-events' ); ?></option>
				<option value="cancelled"  <?php selected( $status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelado', 'apollo-events' ); ?></option>
				<option value="finished"   <?php selected( $status, 'finished' ); ?>><?php esc_html_e( 'Finalizado', 'apollo-events' ); ?></option>
			</select>
		</p>
		<p>
			<label>
				<input type="checkbox" name="_event_is_gone" value="1" <?php checked( $is_gone, '1' ); ?>>
				<?php esc_html_e( 'Marcar como Expirado (_event_is_gone)', 'apollo-events' ); ?>
			</label>
		</p>
		<?php
	}

	// ─── Save ─────────────────────────────────────────────────────────────────

	public function save( int $post_id, \WP_Post $post ): void {
		// Nonce
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::ACTION ) ) {
			return;
		}
		// Autosave / revision / capacidade
		if ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( $post->post_type !== self::POST_TYPE ) {
			return;
		}

		// ─ Enum whitelists (same rules as EventsController::sanitize_meta_value)
		$enums = array(
			'_event_privacy'          => array( array( 'public', 'private', 'invite' ), 'public' ),
			'_event_status'           => array( array( 'scheduled', 'cancelled', 'postponed', 'ongoing', 'finished' ), 'scheduled' ),
			'_event_ticket_status'    => array( array( 'free', 'available', 'soldout_soon', 'sold_out' ), 'available' ),
			'_event_ticket_btn_style' => array( array( 'main', 'soft', 'lista' ), 'main' ),
			'_event_list_btn_style'   => array( array( 'main', 'soft', 'lista', 'fem' ), 'lista' ),
		);
		foreach ( $enums as $key => [$allowed, $fallback] ) {
			if ( isset( $_POST[ $key ] ) ) {
				$v = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				update_post_meta( $post_id, $key, \in_array( $v, $allowed, true ) ? $v : $fallback );
			}
		}

		// ─ String simples
		$string_keys = array(
			'_event_start_date',
			'_event_end_date',
			'_event_start_time',
			'_event_end_time',
			'_event_ticket_price',
			'_event_coupon_code',
			'_event_lista_cta_label',
		);
		foreach ( $string_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		// ─ Cor de fundo (hex only)
		if ( isset( $_POST['_event_bg_color'] ) ) {
			$hex = sanitize_hex_color( wp_unslash( (string) $_POST['_event_bg_color'] ) );
			update_post_meta( $post_id, '_event_bg_color', $hex ?: '#0a0a0a' );
		}

		// ─ Early Bird e Listas — unified repeater (writes _event_access_buttons,
		//   the SAME canonical meta the frontend/REST path writes; identical
		//   whitelists). Writing the key — even as [] — is the migration marker
		//   that retires the legacy toggle fields for this event.
		$axb_labels = isset( $_POST['apl_axb_label'] ) ? (array) wp_unslash( $_POST['apl_axb_label'] ) : array();
		$axb_kinds  = isset( $_POST['apl_axb_kind'] ) ? (array) wp_unslash( $_POST['apl_axb_kind'] ) : array();
		$axb_styles = isset( $_POST['apl_axb_style'] ) ? (array) wp_unslash( $_POST['apl_axb_style'] ) : array();
		$axb_subs   = isset( $_POST['apl_axb_sub'] ) ? (array) wp_unslash( $_POST['apl_axb_sub'] ) : array();
		$axb_urls   = isset( $_POST['apl_axb_url'] ) ? (array) wp_unslash( $_POST['apl_axb_url'] ) : array();
		if ( isset( $_POST['apl_axb_label'] ) ) {
			$axb_clean = array();
			foreach ( $axb_labels as $i => $raw_label ) {
				$label = sanitize_text_field( (string) $raw_label );
				if ( '' === $label ) {
					continue; // empty rows discarded — including the trailing "add" row
				}
				$kind  = ( $axb_kinds[ $i ] ?? '' ) === 'lista' ? 'lista' : 'ticket';
				$style = \in_array( ( $axb_styles[ $i ] ?? '' ), array( 'main', 'soft', 'lista', 'fem', 'cta' ), true ) ? $axb_styles[ $i ] : 'soft';
				$axb_clean[] = array(
					'kind'  => $kind,
					'style' => $style,
					'label' => $label,
					'sub'   => sanitize_text_field( (string) ( $axb_subs[ $i ] ?? '' ) ),
					'url'   => esc_url_raw( (string) ( $axb_urls[ $i ] ?? '' ) ),
				);
			}
			update_post_meta( $post_id, '_event_access_buttons', $axb_clean );
		}

		// ─ URL
		$url_keys = array( '_event_ticket_url', '_event_list_url', '_event_video_url', '_event_audio_url', '_event_earlybird_url' );
		foreach ( $url_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, esc_url_raw( wp_unslash( (string) $_POST[ $key ] ) ) );
			}
		}

		// ─ Banner / featured (must stay equal). URL field wins when it is a
		// new remote image; otherwise the attachment id from the Publish box.
		$cover_url = isset( $_POST['_event_cover_url'] )
			? esc_url_raw( wp_unslash( (string) $_POST['_event_cover_url'] ) )
			: '';
		$banner_raw = isset( $_POST['_event_banner'] )
			? wp_unslash( $_POST['_event_banner'] )
			: null;

		if ( '' !== $cover_url || null !== $banner_raw ) {
			$current_full = (string) ( get_the_post_thumbnail_url( $post_id, 'full' ) ?: '' );
			$ref          = $banner_raw;

			if ( '' !== $cover_url && $cover_url !== $current_full ) {
				$from_url = attachment_url_to_postid( $cover_url );
				$ref      = $from_url > 0 ? $from_url : $cover_url;
			}

			if ( function_exists( 'apollo_event_set_banner' ) ) {
				$result = \Apollo\Event\apollo_event_set_banner( $post_id, $ref );
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
		}

		if ( function_exists( 'apollo_event_heal_cover' ) ) {
			\apollo_event_heal_cover( $post_id );
		}

		// ─ Inteiros
		$int_keys = array( '_event_loc_id' );
		foreach ( $int_keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, absint( $_POST[ $key ] ) );
			}
		}

		/*
		 * ─ DJ IDs (JSON array from the lineup builder's hidden field)
		 *
		 * THE WRITE IS NOW INSIDE THE GUARD (fixed 2026-08-17). It used to run
		 * unconditionally, with an else-branch setting $dj_ids = array() — so
		 * ANY save of this post that did not carry _event_dj_ids_json wiped the
		 * line-up. Every other key in this method is written only when its
		 * field is present; this one was the exception, and the exception was
		 * silent data loss. A quick-edit, a bulk action, a programmatic
		 * wp_update_post() or a metabox reordered out of the form would all
		 * blank the DJs.
		 *
		 * Absent field now means "not submitted", which is what it means
		 * everywhere else in this file.
		 */
		if ( isset( $_POST['_event_dj_ids_json'] ) ) {
			$ids_raw = sanitize_text_field( wp_unslash( $_POST['_event_dj_ids_json'] ) );
			$ids_arr = \json_decode( $ids_raw, true );
			$dj_ids  = \is_array( $ids_arr ) ? \array_map( 'absint', $ids_arr ) : array();
			update_post_meta( $post_id, '_event_dj_ids', $dj_ids );
		}

		// ─ DJ Slots (JSON array from lineup builder hidden field)
		if ( isset( $_POST['_event_dj_slots'] ) ) {
			$slots_raw = sanitize_text_field( wp_unslash( $_POST['_event_dj_slots'] ) );
			$slots     = \json_decode( $slots_raw, true );
			if ( \is_array( $slots ) ) {
				$slots = \array_map( function( $s ) {
					return array(
						'dj_id'      => absint( $s['dj_id'] ?? 0 ),
						'start_time' => sanitize_text_field( $s['start_time'] ?? '' ),
						'end_time'   => sanitize_text_field( $s['end_time'] ?? '' ),
						'badge'      => sanitize_text_field( $s['badge'] ?? '' ),
					);
				}, $slots );
			} else {
				$slots = array();
			}
			update_post_meta( $post_id, '_event_dj_slots', $slots );
		}

		// ─ Gallery (string CSV → array ints)
		if ( isset( $_POST['_event_gallery'] ) ) {
			$gallery_raw = sanitize_text_field( wp_unslash( $_POST['_event_gallery'] ) );
			$gallery     = \array_filter( \array_map( 'absint', \explode( ',', $gallery_raw ) ) );
			update_post_meta( $post_id, '_event_gallery', \array_values( $gallery ) );
		}

		// ─ is_gone
		$is_gone = isset( $_POST['_event_is_gone'] ) ? '1' : '';
		update_post_meta( $post_id, '_event_is_gone', $is_gone );

		// ─ highlighted (feeds the hero slider on /eventos + /casa)
		$highlighted = isset( $_POST['_event_highlighted'] ) ? '1' : '';
		update_post_meta( $post_id, '_event_highlighted', $highlighted );

		// ─ Taxonomias (category/type/tag only). sound/season → apollo-lux-panels.
		// Never wipe when apl_tax is absent: that path destroyed Lux multi-select
		// genres on every block-editor meta-box-loader POST.
		if ( isset( $_POST['apl_tax_present'] ) ) {
			$allowed = array( 'event_category', 'event_type', 'event_tag' );
			$apl_tax = ( isset( $_POST['apl_tax'] ) && \is_array( $_POST['apl_tax'] ) )
				? wp_unslash( $_POST['apl_tax'] )
				: array();
			foreach ( $allowed as $tax_slug ) {
				if ( ! taxonomy_exists( $tax_slug ) ) {
					continue;
				}
				$term_ids = isset( $apl_tax[ $tax_slug ] )
					? \array_map( 'absint', (array) $apl_tax[ $tax_slug ] )
					: array();
				wp_set_post_terms( $post_id, $term_ids, $tax_slug );
			}
		}

		/* A debug log write lived here (removed 2026-08-17). On every event save
		   it appended JSON to D:/dev/_livro.rvalle.com.br/… — an absolute path
		   belonging to a different project on one developer's machine — and to
		   WP_CONTENT_DIR/debug-c3f157.log on the live server.
		   $apollo_rule.data_flow: no debug output in production. */
	}
}