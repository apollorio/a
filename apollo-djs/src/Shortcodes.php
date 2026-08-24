<?php

/**
 * Shortcodes — apollo-djs
 *
 * Registry shortcodes:
 * - [apollo_djs]       → DJ listing (attrs: limit, sound, featured)
 * - [apollo_dj]        → Single DJ embed (attrs: id)
 * - [apollo_dj_carousel] → DJ carousel slider
 *
 * @package Apollo\DJs
 */

namespace Apollo\DJs;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcodes {

	public function __construct() {
		add_shortcode( 'apollo_djs', array( $this, 'render_djs' ) );
		add_shortcode( 'apollo_dj', array( $this, 'render_dj' ) );
		add_shortcode( 'apollo_dj_carousel', array( $this, 'render_carousel' ) );
		add_shortcode( 'apollo_add_dj', array( $this, 'render_add_dj' ) );

		// Registra no apollo-shortcodes se disponível
		add_filter( 'apollo_shortcodes_registry', array( $this, 'register_in_apollo_shortcodes' ) );
	}

	/**
	 * [apollo_djs] — DJ listing grid
	 */
	public function render_djs( $atts ): string {
		$atts = shortcode_atts(
			array(
				'limit'    => 12,
				'sound'    => '',
				'featured' => '',
			),
			$atts,
			'apollo_djs'
		);

		wp_enqueue_style( 'apollo-djs-v1' );
		wp_enqueue_script( 'apollo-djs' );

		$args = array(
			'post_type'      => APOLLO_DJ_CPT,
			'posts_per_page' => (int) $atts['limit'],
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		if ( ! empty( $atts['sound'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => APOLLO_DJ_TAX_SOUND,
					'field'    => 'slug',
					'terms'    => \array_map( 'trim', \explode( ',', $atts['sound'] ) ),
				),
			);
		}

		if ( $atts['featured'] ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_dj_verified',
					'value' => '1',
				),
			);
		}

		$query = new \WP_Query( $args );

		if ( ! $query->have_posts() ) {
			return '<div class="a-dj-empty">' . esc_html__( 'Nenhum DJ encontrado.', 'apollo-djs' ) . '</div>';
		}

		\ob_start();
		echo '<div class="a-dj-grid">';

		while ( $query->have_posts() ) {
			$query->the_post();
			$this->load_template(
				'dj-card',
				array(
					'dj_id'             => get_the_ID(),
					'dj_name'           => get_the_title(),
					'dj_image'          => apollo_dj_get_image( get_the_ID() ),
					'dj_sounds'         => apollo_dj_get_sounds( get_the_ID() ),
					'dj_verified'       => apollo_dj_is_verified( get_the_ID() ),
					'dj_bio'            => get_post_meta( get_the_ID(), '_dj_bio_short', true ),
					'dj_links'          => apollo_dj_get_links( get_the_ID() ),
					'dj_url'            => get_permalink(),
					'dj_upcoming_count' => apollo_dj_count_upcoming_events( get_the_ID() ),
				)
			);
		}
		wp_reset_postdata();

		echo '</div>';
		return \ob_get_clean();
	}

	/**
	 * [apollo_dj id="123"] — Single DJ embed
	 */
	public function render_dj( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'apollo_dj'
		);

		$dj_id = (int) $atts['id'];
		if ( ! $dj_id || get_post_type( $dj_id ) !== APOLLO_DJ_CPT ) {
			return '';
		}

		wp_enqueue_style( 'apollo-djs-v1' );
		wp_enqueue_script( 'apollo-djs' );

		\ob_start();
		$this->load_template(
			'dj-card',
			array(
				'dj_id'             => $dj_id,
				'dj_name'           => get_the_title( $dj_id ),
				'dj_image'          => apollo_dj_get_image( $dj_id ),
				'dj_sounds'         => apollo_dj_get_sounds( $dj_id ),
				'dj_verified'       => apollo_dj_is_verified( $dj_id ),
				'dj_bio'            => get_post_meta( $dj_id, '_dj_bio_short', true ),
				'dj_links'          => apollo_dj_get_links( $dj_id ),
				'dj_url'            => get_permalink( $dj_id ),
				'dj_upcoming_count' => apollo_dj_count_upcoming_events( $dj_id ),
			)
		);
		return \ob_get_clean();
	}

	/**
	 * [apollo_dj_carousel] — Carousel/slider de DJs
	 */
	public function render_carousel( $atts ): string {
		$atts = shortcode_atts(
			array(
				'limit' => 10,
				'sound' => '',
			),
			$atts,
			'apollo_dj_carousel'
		);

		wp_enqueue_style( 'apollo-djs-v1' );
		wp_enqueue_script( 'apollo-djs' );

		$args = array(
			'post_type'      => APOLLO_DJ_CPT,
			'posts_per_page' => (int) $atts['limit'],
			'post_status'    => 'publish',
			'orderby'        => 'rand',
		);

		if ( ! empty( $atts['sound'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => APOLLO_DJ_TAX_SOUND,
					'field'    => 'slug',
					'terms'    => \array_map( 'trim', \explode( ',', $atts['sound'] ) ),
				),
			);
		}

		$query = new \WP_Query( $args );
		if ( ! $query->have_posts() ) {
			return '';
		}

		\ob_start();
		echo '<div class="a-dj-carousel" data-carousel>';

		echo '<div class="a-dj-carousel__track">';
		while ( $query->have_posts() ) {
			$query->the_post();
			echo '<div class="a-dj-carousel__slide">';
			$this->load_template(
				'dj-card',
				array(
					'dj_id'             => get_the_ID(),
					'dj_name'           => get_the_title(),
					'dj_image'          => apollo_dj_get_image( get_the_ID() ),
					'dj_sounds'         => apollo_dj_get_sounds( get_the_ID() ),
					'dj_verified'       => apollo_dj_is_verified( get_the_ID() ),
					'dj_bio'            => get_post_meta( get_the_ID(), '_dj_bio_short', true ),
					'dj_links'          => apollo_dj_get_links( get_the_ID() ),
					'dj_url'            => get_permalink(),
					'dj_upcoming_count' => apollo_dj_count_upcoming_events( get_the_ID() ),
				)
			);
			echo '</div>';
		}
		wp_reset_postdata();
		echo '</div>';

		echo '<button class="a-dj-carousel__prev" aria-label="Anterior">&lsaquo;</button>';
		echo '<button class="a-dj-carousel__next" aria-label="Próximo">&rsaquo;</button>';

		echo '</div>';
		return \ob_get_clean();
	}

	/**
	 * Carrega template com fallback
	 */
	private function load_template( string $template, array $data = array() ): void {
		$style = APOLLO_DJ_DEFAULT_STYLE;

		$paths = array(
			get_stylesheet_directory() . '/apollo-djs/' . $style . '/' . $template . '.php',
			get_template_directory() . '/apollo-djs/' . $style . '/' . $template . '.php',
			APOLLO_DJ_DIR . 'styles/' . $style . '/' . $template . '.php',
			APOLLO_DJ_DIR . 'styles/base/' . $template . '.php',
		);

		foreach ( $paths as $path ) {
			if ( \file_exists( $path ) ) {
				\extract( $data, EXTR_SKIP );
				include $path;
				return;
			}
		}
	}

	/**
	 * Registra no apollo-shortcodes
	 */
	public function register_in_apollo_shortcodes( array $shortcodes ): array {
		$shortcodes['apollo_djs'] = array(
			'tag'         => 'apollo_djs',
			'description' => 'DJ listing',
			'plugin'      => 'apollo-djs',
			'attrs'       => array( 'limit', 'sound', 'featured' ),
		);

		$shortcodes['apollo_dj'] = array(
			'tag'         => 'apollo_dj',
			'description' => 'Single DJ',
			'plugin'      => 'apollo-djs',
			'attrs'       => array( 'id' ),
		);

		$shortcodes['apollo_dj_carousel'] = array(
			'tag'         => 'apollo_dj_carousel',
			'description' => 'DJ carousel slider',
			'plugin'      => 'apollo-djs',
			'attrs'       => array(),
		);

		$shortcodes['apollo_add_dj'] = array(
			'tag'         => 'apollo_add_dj',
			'description' => 'Formulário de cadastro de novo DJ',
			'plugin'      => 'apollo-djs',
			'attrs'       => array(),
		);

		return $shortcodes;
	}

	/**
	 * [apollo_add_dj] — Standalone DJ registration form
	 */
	public function render_add_dj( $atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="apl-notice">' . esc_html__( 'Você precisa estar logado para cadastrar um DJ.', 'apollo-djs' ) . '</p>';
		}

		$nonce    = wp_create_nonce( 'wp_rest' );
		$rest_url = esc_url_raw( rest_url( 'apollo/v1/djs' ) );

		$sounds = array();
		if ( taxonomy_exists( 'sound' ) ) {
			$terms = get_terms( array( 'taxonomy' => 'sound', 'hide_empty' => false ) );
			if ( ! is_wp_error( $terms ) ) {
				$sounds = $terms;
			}
		}

		\ob_start();
		?>
		<link rel="stylesheet" href="https://cdn.apollo.rio.br/v1.0.0/js/forms.js" as="style" onload="this.rel='stylesheet'">
		<script src="https://cdn.apollo.rio.br/v1.0.0/js/forms.js" fetchpriority="high"></script>
		<div class="apl-add-dj-wrap" id="aplAddDjWrap">
			<div class="apl-form-header">
				<i class="ri-disc-line"></i>
				<h2><?php esc_html_e( 'Cadastrar DJ', 'apollo-djs' ); ?></h2>
			</div>
			<form id="aplAddDjForm" novalidate>

				<div class="input-group">
					<input type="text" id="add_dj_name" name="title" class="apollo-input" placeholder=" " required>
					<label for="add_dj_name" class="apollo-label"><?php esc_html_e( 'Nome artístico do DJ', 'apollo-djs' ); ?> *</label>
				</div>

				<div class="input-group">
					<textarea id="add_dj_bio" name="bio_short" class="apollo-input" placeholder=" " rows="3" maxlength="280"></textarea>
					<label for="add_dj_bio" class="apollo-label"><?php esc_html_e( 'Biografia curta (máx. 280 chars)', 'apollo-djs' ); ?></label>
				</div>

				<div class="input-group">
					<input type="text" id="add_dj_instagram" name="instagram" class="apollo-input" placeholder=" ">
					<label for="add_dj_instagram" class="apollo-label">Instagram (@handle)</label>
				</div>

				<div class="input-group">
					<input type="url" id="add_dj_soundcloud" name="soundcloud" class="apollo-input" placeholder=" ">
					<label for="add_dj_soundcloud" class="apollo-label">SoundCloud (URL)</label>
				</div>

				<div class="input-group">
					<input type="url" id="add_dj_spotify" name="spotify" class="apollo-input" placeholder=" ">
					<label for="add_dj_spotify" class="apollo-label">Spotify (URL)</label>
				</div>

				<div class="input-group">
					<input type="url" id="add_dj_mixcloud" name="mixcloud" class="apollo-input" placeholder=" ">
					<label for="add_dj_mixcloud" class="apollo-label">Mixcloud (URL)</label>
				</div>

				<div class="input-group">
					<input type="url" id="add_dj_youtube" name="youtube" class="apollo-input" placeholder=" ">
					<label for="add_dj_youtube" class="apollo-label">YouTube (URL)</label>
				</div>

				<div class="input-group">
					<input type="url" id="add_dj_website" name="website" class="apollo-input" placeholder=" ">
					<label for="add_dj_website" class="apollo-label"><?php esc_html_e( 'Website', 'apollo-djs' ); ?></label>
				</div>

				<?php if ( $sounds ) : ?>
				<div class="input-group">
					<select id="add_dj_sounds" name="sounds" class="apollo-input" multiple size="4">
						<?php foreach ( $sounds as $term ) : ?>
							<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<label for="add_dj_sounds" class="apollo-label" style="position:static;margin-bottom:4px;"><?php esc_html_e( 'Gêneros musicais', 'apollo-djs' ); ?></label>
				</div>
				<?php endif; ?>

				<div class="apl-form-msg" id="aplAddDjMsg" style="display:none;"></div>

				<button type="submit" class="apl-btn-primary" id="aplAddDjSubmit">
					<i class="ri-save-line"></i>
					<span><?php esc_html_e( 'Cadastrar DJ', 'apollo-djs' ); ?></span>
				</button>
			</form>
		</div>

		<script>
		(function(){
			'use strict';
			var NONCE = '<?php echo esc_js( $nonce ); ?>';
			var REST  = '<?php echo esc_js( $rest_url ); ?>';
			var form  = document.getElementById('aplAddDjForm');
			var msg   = document.getElementById('aplAddDjMsg');
			var btn   = document.getElementById('aplAddDjSubmit');
			if (!form) return;
			form.addEventListener('submit', async function(e){
				e.preventDefault();
				msg.style.display = 'none';
				btn.disabled = true;
				btn.querySelector('span').textContent = 'Salvando...';
				try {
					var d = {};
					['title','bio_short','instagram','website'].forEach(function(k){
						var el = form.querySelector('[name="'+k+'"]');
						if (el && el.value.trim()) d[k] = el.value.trim();
					});
					['soundcloud','spotify','mixcloud','youtube'].forEach(function(k){
						var el = form.querySelector('[name="'+k+'"]');
						if (el && el.value.trim()) d[k] = el.value.trim();
					});
					var soundSel = form.querySelector('[name="sounds"]');
					if (soundSel) {
						var sv = Array.from(soundSel.selectedOptions).map(function(o){ return o.value; });
						if (sv.length) d.sounds = sv;
					}
					if (!d.title) throw new Error('Nome artístico é obrigatório.');
					var r = await fetch(REST, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
						credentials: 'same-origin',
						body: JSON.stringify(d)
					});
					var res = await r.json();
					if (!r.ok) throw new Error(res.message || 'Erro ao cadastrar DJ.');
					msg.className = 'apl-form-msg ok';
					msg.textContent = '✓ DJ cadastrado com sucesso!';
					msg.style.display = '';
					form.reset();
					btn.querySelector('span').textContent = 'Cadastrar DJ';
					btn.disabled = false;
					if (res.permalink) setTimeout(function(){ window.location.href = res.permalink; }, 1800);
				} catch(err) {
					msg.className = 'apl-form-msg err';
					msg.textContent = err.message;
					msg.style.display = '';
					btn.disabled = false;
					btn.querySelector('span').textContent = 'Cadastrar DJ';
				}
			});
		})();
		</script>
		<style>
		.apl-add-dj-wrap { max-width: 560px; margin: 0 auto; padding: 24px 0; }
		.apl-form-header { display: flex; align-items:center; gap: 10px; margin-bottom: 20px; }
		.apl-form-header i { font-size: 24px; color: var(--primary,FF9820); }
		.apl-form-header h2 { margin: 0; font-size: 22px; font-weight: 700; }
		.apl-form-msg { padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 14px; }
		.apl-form-msg.ok { background: rgba(34,197,94,.12); color: #22c55e; }
		.apl-form-msg.err { background: rgba(239,68,68,.12); color: #ef4444; }
		.apl-btn-primary {
			display: flex; align-items: center; justify-content:center; gap: 8px;
			width: 100%; padding: 13px; border: none; border-radius: 10px;
			background: var(--primary,FF9820); color: #fff; font-size: 15px;
			font-weight: 700; cursor: pointer; margin-top: 18px;
		}
		.apl-btn-primary:disabled { opacity:.5; cursor:not-allowed; }
		</style>
		<?php
		return \ob_get_clean();
	}
}
