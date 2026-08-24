<?php

/**
 * Shortcodes — Embeddable journal components
 *
 * [apollo_journal]         — News grid widget (count, category, columns)
 * [apollo_journal_marquee] — Horizontal scrolling ticker
 * [apollo_journal_card]    — Single article card embed
 *
 * @package Apollo\Journal
 */

namespace Apollo\Journal;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode handler.
 */
class Shortcodes {

	/**
	 * Whether shared form CSS has been enqueued already.
	 *
	 * Prevents duplicate inline styles when multiple form shortcodes appear on
	 * the same page.
	 */
	private static bool $form_styles_enqueued = false;

	/**
	 * Register all shortcodes.
	 *
	 * @return void
	 */
	public function init(): void {
		add_shortcode( 'apollo_journal', array( $this, 'render_grid' ) );
		add_shortcode( 'apollo_journal_marquee', array( $this, 'render_marquee' ) );
		add_shortcode( 'apollo_journal_card', array( $this, 'render_card' ) );
		add_shortcode( 'apollo_add_news', array( $this, 'render_add_news' ) );
		add_shortcode( 'apollo_add_nrep', array( $this, 'render_add_nrep' ) );
	}

	// ─────────────────────────────────────────────────────────────────────
	// [apollo_journal] — News Grid
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Render a news grid.
	 *
	 * Usage:
	 *   [apollo_journal]
	 *   [apollo_journal count="9" category="news" columns="3"]
	 *   [apollo_journal taxonomy="music" term="funk"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_grid( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'count'    => 6,
				'category' => '',
				'taxonomy' => '',
				'term'     => '',
				'columns'  => 3,
				'offset'   => 0,
				'orderby'  => 'date',
				'loadmore' => 'false',
			),
			$atts,
			'apollo_journal'
		);

		$args = array(
			'post_type'      => array( 'post', 'journal_news' ),
			'posts_per_page' => absint( $atts['count'] ),
			'post_status'    => 'publish',
			'orderby'        => sanitize_key( $atts['orderby'] ),
			'order'          => 'DESC',
			'offset'         => absint( $atts['offset'] ),
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['category_name'] = sanitize_text_field( $atts['category'] );
		}

		if ( ! empty( $atts['taxonomy'] ) && ! empty( $atts['term'] ) ) {
			$allowed_tax = array( 'category', 'post_tag', 'music', 'culture', 'rio', 'formato' );
			$tax_slug    = sanitize_key( $atts['taxonomy'] );
			if ( in_array( $tax_slug, $allowed_tax, true ) ) {
				$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => $tax_slug,
						'field'    => 'slug',
						'terms'    => sanitize_text_field( $atts['term'] ),
					),
				);
			}
		}

		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			return '<div class="aj-empty"><i class="ri-newspaper-line"></i><p>' .
				esc_html__( 'Nenhum artigo encontrado.', 'apollo-journal' ) . '</p></div>';
		}

		// Prime caches to avoid N+1 queries.
		$post_ids = wp_list_pluck( $posts, 'ID' );
		update_post_meta_cache( $post_ids );
		update_object_term_cache( $post_ids, array( 'post', 'journal_news' ) );

		wp_enqueue_style( 'apollo-journal' );
		wp_enqueue_script( 'apollo-journal' );

		$cols     = absint( $atts['columns'] );
		$cols     = \max( 1, \min( 4, $cols ) );
		$loadmore = filter_var( $atts['loadmore'], FILTER_VALIDATE_BOOLEAN );

		\ob_start();
		?>
		<section class="aj-news-section" data-lazy-section>
			<div class="aj-news-grid" style="--aj-cols:<?php echo $cols; ?>" <?php echo $loadmore ? 'data-aj-loadmore' : ''; ?>>
				<?php
				global $post;
				foreach ( $posts as $p ) :
					$post = $p; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					setup_postdata( $p );
					?>
					<?php
					echo $this->render_card_html( $p ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
					?>
					<?php
				endforeach;
				wp_reset_postdata();
				?>
			</div>
		</section>
		<?php
		return \ob_get_clean();
	}

	// ─────────────────────────────────────────────────────────────────────
	// [apollo_journal_marquee] — Scrolling Ticker
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Render a horizontal scrolling news marquee/ticker.
	 *
	 * Usage:
	 *   [apollo_journal_marquee]
	 *   [apollo_journal_marquee count="10" speed="30" category="news"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_marquee( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'count'    => 8,
				'category' => '',
				'speed'    => 30,
				'pausable' => 'true',
			),
			$atts,
			'apollo_journal_marquee'
		);

		$args = array(
			'post_type'      => array( 'post', 'journal_news' ),
			'posts_per_page' => absint( $atts['count'] ),
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $atts['category'] ) ) {
			$args['category_name'] = sanitize_text_field( $atts['category'] );
		}

		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			return '';
		}

		// Prime caches to avoid N+1 queries in the marquee loop.
		$mq_ids = wp_list_pluck( $posts, 'ID' );
		update_post_meta_cache( $mq_ids );
		update_object_term_cache( $mq_ids, array( 'post', 'journal_news' ) );

		wp_enqueue_style( 'apollo-journal' );

		$speed    = absint( $atts['speed'] );
		$pausable = filter_var( $atts['pausable'], FILTER_VALIDATE_BOOLEAN );
		$duration = \max( 10, $speed );
		$uid      = 'aj-marquee-' . wp_unique_id();

		\ob_start();
		?>
		<div class="aj-marquee <?php echo $pausable ? 'aj-marquee--pausable' : ''; ?>" id="<?php echo esc_attr( $uid ); ?>">
			<div class="aj-marquee__track" style="animation-duration:<?php echo $duration; ?>s">
				<?php
				foreach ( $posts as $p ) :
					$cats     = get_the_category( $p->ID );
					$cat_name = ! empty( $cats ) ? $cats[0]->name : 'News';
					$nrep     = get_post_meta( $p->ID, '_nrep_code', true );
					$badge    = $nrep ?: \strtoupper( $cat_name );
					$badge_cl = $nrep ? 'aj-marquee__badge aj-marquee__badge--nrep' : 'aj-marquee__badge';
					?>
					<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="aj-marquee__item">
						<span class="<?php echo esc_attr( $badge_cl ); ?>"><?php echo esc_html( $badge ); ?></span>
						<span class="aj-marquee__title"><?php echo esc_html( get_the_title( $p ) ); ?></span>
						<span class="aj-marquee__sep">|</span>
					</a>
					<?php
				endforeach;
				wp_reset_postdata();
				?>
				<?php
				// Duplicate for seamless loop
				?>
				<?php
				foreach ( $posts as $p ) :
					$cats     = get_the_category( $p->ID );
					$cat_name = ! empty( $cats ) ? $cats[0]->name : 'News';
					$nrep     = get_post_meta( $p->ID, '_nrep_code', true );
					$badge    = $nrep ?: \strtoupper( $cat_name );
					$badge_cl = $nrep ? 'aj-marquee__badge aj-marquee__badge--nrep' : 'aj-marquee__badge';
					?>
					<a href="<?php echo esc_url( get_permalink( $p ) ); ?>" class="aj-marquee__item" aria-hidden="true">
						<span class="<?php echo esc_attr( $badge_cl ); ?>"><?php echo esc_html( $badge ); ?></span>
						<span class="aj-marquee__title"><?php echo esc_html( get_the_title( $p ) ); ?></span>
						<span class="aj-marquee__sep">|</span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return \ob_get_clean();
	}

	// ─────────────────────────────────────────────────────────────────────
	// [apollo_journal_card] — Single Article Card
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Render a single article card by ID or latest.
	 *
	 * Usage:
	 *   [apollo_journal_card id="123"]
	 *   [apollo_journal_card category="nota-de-repudio"]
	 *   [apollo_journal_card style="featured"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_card( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id'       => 0,
				'category' => '',
				'style'    => 'card',
			),
			$atts,
			'apollo_journal_card'
		);

		$post = null;

		if ( absint( $atts['id'] ) > 0 ) {
			$post = get_post( absint( $atts['id'] ) );
		} else {
			$args = array(
				'post_type'      => array( 'post', 'journal_news', 'journal_nota' ),
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			);

			if ( ! empty( $atts['category'] ) ) {
				$args['category_name'] = sanitize_text_field( $atts['category'] );
			}

			$found = get_posts( $args );
			$post  = $found[0] ?? null;
		}

		if ( ! $post ) {
			return '';
		}

		wp_enqueue_style( 'apollo-journal' );

		$style = sanitize_key( $atts['style'] );

		if ( 'featured' === $style ) {
			return $this->render_featured_html( $post );
		}

		return $this->render_card_html( $post );
	}

	// ─────────────────────────────────────────────────────────────────────
	// PRIVATE RENDERERS
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Render a standard card HTML block.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	private function render_card_html( \WP_Post $post ): string {
		$cats     = get_the_category( $post->ID );
		$cat_name = ! empty( $cats ) ? $cats[0]->name : 'News';
		$nrep     = get_post_meta( $post->ID, '_nrep_code', true );
		$badge    = $nrep ?: \strtoupper( $cat_name );
		$badge_cl = $nrep ? 'aj-ng-badge aj-ng-badge--nrep' : 'aj-ng-badge';

		$time_ago_dt = get_post_time( 'Y-m-d H:i:s', false, $post );

		$author = get_the_author_meta( 'display_name', $post->post_author );

		\ob_start();
		?>
		<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="aj-ng-item">
			<?php if ( has_post_thumbnail( $post ) ) : ?>
				<img class="aj-ng-item__img"
					src="<?php echo esc_url( get_the_post_thumbnail_url( $post, 'medium' ) ); ?>"
					alt="<?php echo esc_attr( get_the_title( $post ) ); ?>"
					loading="lazy">
			<?php endif; ?>
			<div class="aj-ng-item__body">
				<div class="aj-ng-item__top">
					<span class="<?php echo esc_attr( $badge_cl ); ?>"><?php echo esc_html( $badge ); ?></span>
					<span class="aj-ng-item__time"><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( $time_ago_dt ) : esc_html( human_time_diff( (int) strtotime( $time_ago_dt ), time() ) ) ); ?></span>
				</div>
				<div class="aj-ng-item__title"><?php echo esc_html( get_the_title( $post ) ); ?></div>
				<div class="aj-ng-item__author"><?php echo esc_html( $author ); ?></div>
			</div>
		</a>
		<?php
		return \ob_get_clean();
	}

	/**
	 * Render a featured (large) card HTML block.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string
	 */
	private function render_featured_html( \WP_Post $post ): string {
		$cats     = get_the_category( $post->ID );
		$cat_name = ! empty( $cats ) ? $cats[0]->name : 'Journal';
		$nrep     = get_post_meta( $post->ID, '_nrep_code', true );
		$badge    = $nrep ?: \strtoupper( $cat_name );
		$badge_cl = $nrep ? 'aj-featured__badge aj-card__badge--nrep' : 'aj-featured__badge';

		$time_ago_dt = get_post_time( 'Y-m-d H:i:s', false, $post );

		\ob_start();
		?>
		<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="aj-featured">
			<?php if ( has_post_thumbnail( $post ) ) : ?>
				<img class="aj-featured__img"
					src="<?php echo esc_url( get_the_post_thumbnail_url( $post, 'large' ) ); ?>"
					alt="<?php echo esc_attr( get_the_title( $post ) ); ?>"
					loading="lazy">
			<?php endif; ?>
			<div class="aj-featured__body">
				<span class="<?php echo esc_attr( $badge_cl ); ?>"><?php echo esc_html( $badge ); ?></span>
				<h2 class="aj-featured__title"><?php echo esc_html( get_the_title( $post ) ); ?></h2>
				<div class="aj-featured__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 30 ) ); ?></div>
				<div class="aj-featured__footer">
					<span><?php echo esc_html( get_the_author_meta( 'display_name', $post->post_author ) ); ?></span>
					<span>&middot;</span>
					<span><?php echo wp_kses_post( function_exists( 'apollo_time_ago_html' ) ? apollo_time_ago_html( $time_ago_dt ) : esc_html( human_time_diff( (int) strtotime( $time_ago_dt ), time() ) ) ); ?></span>
				</div>
			</div>
		</a>
		<?php
		return \ob_get_clean();
	}
	/**
	 * [apollo_add_news] — News creation form
	 */
	public function render_add_news( $atts = array() ): string {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Você precisa estar logado para publicar.', 'apollo-journal' ) . '</p>';
		}
		if ( ! current_user_can( 'publish_posts' ) ) {
			return '<p>' . esc_html__( 'Sem permissão para publicar artigos.', 'apollo-journal' ) . '</p>';
		}

		$nonce    = wp_create_nonce( 'wp_rest' );
		$rest_url = esc_url_raw( rest_url( 'wp/v2/journal_news' ) );
		$uid      = wp_unique_id( 'aj-news-' );

		wp_enqueue_script( 'apollo-forms-cdn', 'https://cdn.apollo.rio.br/v1.0.0/js/forms.js', array(), null, true );

		if ( ! self::$form_styles_enqueued ) {
			self::$form_styles_enqueued = true;
			$shared_css = '
				.apl-add-news-wrap,.apl-add-nrep-wrap{max-width:680px;margin:0 auto;padding:24px 0}
				.apl-form-header{display:flex;align-items:center;gap:10px;margin-bottom:20px}
				.apl-form-header i{font-size:24px;color:var(--primary,#FF9820)}
				.apl-form-header h2{margin:0;font-size:22px;font-weight:700}
				.apl-nrep-hint{font-size:13px;color:var(--txt-muted,#888);margin:-10px 0 16px}
				.apl-form-msg{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px}
				.apl-form-msg.ok{background:rgba(34,197,94,.12);color:#22c55e}
				.apl-form-msg.err{background:rgba(239,68,68,.12);color:#ef4444}
				.apl-btn-primary{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:13px;border:none;border-radius:10px;background:var(--primary,#FF9820);color:#fff;font-size:15px;font-weight:700;cursor:pointer;margin-top:18px}
				.apl-btn-primary:disabled{opacity:.5;cursor:not-allowed}
			';
			wp_add_inline_style( 'apollo-journal', $shared_css );
		}

		// Journey categories
		$cats = get_categories( array( 'hide_empty' => false, 'orderby' => 'name' ) );

		\ob_start();
		?>
		<div class="apl-add-news-wrap">
			<div class="apl-form-header">
				<i class="ri-newspaper-line"></i>
				<h2><?php esc_html_e( 'Publicar Notícia', 'apollo-journal' ); ?></h2>
			</div>
			<form id="<?php echo esc_attr( $uid ); ?>" novalidate>
				<div class="input-group">
					<input type="text" id="news_title" name="title" class="apollo-input" placeholder=" " required>
					<label for="news_title" class="apollo-label"><?php esc_html_e( 'Título', 'apollo-journal' ); ?> *</label>
				</div>
				<div class="input-group">
					<textarea id="news_excerpt" name="excerpt" class="apollo-input" placeholder=" " rows="2"></textarea>
					<label for="news_excerpt" class="apollo-label"><?php esc_html_e( 'Resumo (excerpt)', 'apollo-journal' ); ?></label>
				</div>
				<div class="input-group">
					<textarea id="news_content" name="content" class="apollo-input" placeholder=" " rows="8" required></textarea>
					<label for="news_content" class="apollo-label"><?php esc_html_e( 'Conteúdo', 'apollo-journal' ); ?> *</label>
				</div>
				<?php if ( $cats ) : ?>
				<div class="input-group">
					<label for="news_cats" class="apollo-label" style="position:static;margin-bottom:4px;"><?php esc_html_e( 'Categorias', 'apollo-journal' ); ?></label>
					<select id="news_cats" name="categories" class="apollo-input" multiple size="4">
						<?php foreach ( $cats as $cat ) : ?>
							<option value="<?php echo esc_attr( (string) $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
						<?php endforeach; ?>
					</select>
					<small><?php esc_html_e( 'Ctrl/Cmd para múltiplas', 'apollo-journal' ); ?></small>
				</div>
				<?php endif; ?>
				<div class="apl-form-msg" id="aplAddNewsMsg" style="display:none;"></div>
				<button type="submit" class="apl-btn-primary" id="aplAddNewsSubmit">
					<i class="ri-send-plane-fill"></i>
					<span><?php esc_html_e( 'Publicar', 'apollo-journal' ); ?></span>
				</button>
			</form>
		</div>
		<script>
		(function(){
			'use strict';
			var NONCE = '<?php echo esc_js( $nonce ); ?>';
			var REST  = '<?php echo esc_js( $rest_url ); ?>';
			var UID   = '<?php echo esc_js( $uid ); ?>';
			var form  = document.getElementById(UID);
			var msg   = document.getElementById(UID+'-msg');
			var btn   = document.getElementById(UID+'-submit');
			if (!form) return;
			form.addEventListener('submit', async function(e){
				e.preventDefault();
				msg.style.display='none'; btn.disabled=true;
				btn.querySelector('span').textContent='Publicando...';
				try {
					var ti=form.querySelector('[name="title"]').value.trim();
					var co=form.querySelector('[name="content"]').value.trim();
					var ex=form.querySelector('[name="excerpt"]');
					var catEl=form.querySelector('[name="categories"]');
					if(!ti) throw new Error('Título obrigatório.');
					if(!co) throw new Error('Conteúdo obrigatório.');
					var d={title:ti,content:co,status:'pending'};
					if(ex&&ex.value.trim()) d.excerpt=ex.value.trim();
					if(catEl){
						var cv=Array.from(catEl.selectedOptions).map(function(o){return parseInt(o.value,10);});
						if(cv.length) d.categories=cv;
					}
					var r=await fetch(REST,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},credentials:'same-origin',body:JSON.stringify(d)});
					var res=await r.json();
					if(!r.ok) throw new Error(res.message||'Erro ao publicar.');
					msg.className='apl-form-msg ok'; msg.textContent='✓ Enviado para revisão!'; msg.style.display='';
					form.reset(); btn.disabled=false; btn.querySelector('span').textContent='Publicar';
				}catch(err){
					msg.className='apl-form-msg err'; msg.textContent=err.message; msg.style.display='';
					btn.disabled=false; btn.querySelector('span').textContent='Publicar';
				}
			});
		})();
		</script>
		<?php
		return \ob_get_clean();
	}

	/**
	 * [apollo_add_nrep] — Nota de Repúdio form
	 */
	public function render_add_nrep( $atts = array() ): string {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Você precisa estar logado para enviar.', 'apollo-journal' ) . '</p>';
		}
		if ( ! current_user_can( 'publish_posts' ) ) {
			return '<p>' . esc_html__( 'Sem permissão para publicar.', 'apollo-journal' ) . '</p>';
		}

		$nonce    = wp_create_nonce( 'wp_rest' );
		$rest_url = esc_url_raw( rest_url( 'wp/v2/journal_nota' ) );
		$uid      = wp_unique_id( 'aj-nrep-' );

		wp_enqueue_script( 'apollo-forms-cdn', 'https://cdn.apollo.rio.br/v1.0.0/js/forms.js', array(), null, true );

		if ( ! self::$form_styles_enqueued ) {
			self::$form_styles_enqueued = true;
			$shared_css = '
				.apl-add-news-wrap,.apl-add-nrep-wrap{max-width:680px;margin:0 auto;padding:24px 0}
				.apl-form-header{display:flex;align-items:center;gap:10px;margin-bottom:20px}
				.apl-form-header i{font-size:24px;color:var(--primary,#FF9820)}
				.apl-form-header h2{margin:0;font-size:22px;font-weight:700}
				.apl-nrep-hint{font-size:13px;color:var(--txt-muted,#888);margin:-10px 0 16px}
				.apl-form-msg{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px}
				.apl-form-msg.ok{background:rgba(34,197,94,.12);color:#22c55e}
				.apl-form-msg.err{background:rgba(239,68,68,.12);color:#ef4444}
				.apl-btn-primary{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:13px;border:none;border-radius:10px;background:var(--primary,#FF9820);color:#fff;font-size:15px;font-weight:700;cursor:pointer;margin-top:18px}
				.apl-btn-primary:disabled{opacity:.5;cursor:not-allowed}
			';
			wp_add_inline_style( 'apollo-journal', $shared_css );
		}

		// Get or create nota-de-repudio category
		$nrep_cat = get_term_by( 'slug', 'nota-de-repudio', 'category' );
		$nrep_cat_id = $nrep_cat ? $nrep_cat->term_id : 0;

		\ob_start();
		?>
		<div class="apl-add-nrep-wrap">
			<div class="apl-form-header">
				<i class="ri-megaphone-line"></i>
				<h2><?php esc_html_e( 'Nota de Repúdio', 'apollo-journal' ); ?></h2>
			</div>
			<p class="apl-nrep-hint"><?php esc_html_e( 'O código NREP será atribuído automaticamente após a publicação.', 'apollo-journal' ); ?></p>
			<form id="<?php echo esc_attr( $uid ); ?>" novalidate>
				<div class="input-group">
					<input type="text" id="<?php echo esc_attr( $uid ); ?>-title" name="title" class="apollo-input" placeholder=" " required>
					<label for="<?php echo esc_attr( $uid ); ?>-title" class="apollo-label"><?php esc_html_e( 'Título da nota', 'apollo-journal' ); ?> *</label>
				</div>
				<div class="input-group">
					<textarea id="<?php echo esc_attr( $uid ); ?>-content" name="content" class="apollo-input" placeholder=" " rows="10" required></textarea>
					<label for="<?php echo esc_attr( $uid ); ?>-content" class="apollo-label"><?php esc_html_e( 'Texto da nota', 'apollo-journal' ); ?> *</label>
				</div>
				<div class="apl-form-msg" id="<?php echo esc_attr( $uid ); ?>-msg" style="display:none;"></div>
				<button type="submit" class="apl-btn-primary" id="<?php echo esc_attr( $uid ); ?>-submit">
					<i class="ri-send-plane-fill"></i>
					<span><?php esc_html_e( 'Enviar Nota', 'apollo-journal' ); ?></span>
				</button>
			</form>
		</div>
		<script>
		(function(){
			'use strict';
			var NONCE = '<?php echo esc_js( $nonce ); ?>';
			var REST  = '<?php echo esc_js( $rest_url ); ?>';
			var UID   = '<?php echo esc_js( $uid ); ?>';
			var form  = document.getElementById(UID);
			var msg   = document.getElementById(UID+'-msg');
			var btn   = document.getElementById(UID+'-submit');
			if(!form) return;
			form.addEventListener('submit',async function(e){
				e.preventDefault();
				msg.style.display='none'; btn.disabled=true;
				btn.querySelector('span').textContent='Enviando...';
				try{
					var ti=form.querySelector('[name="title"]').value.trim();
					var co=form.querySelector('[name="content"]').value.trim();
					if(!ti) throw new Error('Título obrigatório.');
					if(!co) throw new Error('Texto obrigatório.');
					var d={title:ti,content:co,status:'pending'};
					var r=await fetch(REST,{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},credentials:'same-origin',body:JSON.stringify(d)});
					var res=await r.json();
					if(!r.ok) throw new Error(res.message||'Erro ao enviar.');
					msg.className='apl-form-msg ok'; msg.textContent='✓ Nota enviada para revisão!'; msg.style.display='';
					form.reset(); btn.disabled=false; btn.querySelector('span').textContent='Enviar Nota';
				}catch(err){
					msg.className='apl-form-msg err'; msg.textContent=err.message; msg.style.display='';
					btn.disabled=false; btn.querySelector('span').textContent='Enviar Nota';
				}
			});
		})();
		</script>
		<?php
		return \ob_get_clean();
	}}
