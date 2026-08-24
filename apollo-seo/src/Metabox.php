<?php

/**
 * Apollo SEO — Post Editor Metabox
 *
 * SEO fields in the post editor for all Apollo CPTs.
 * Tabs: General (título, descrição, canonical) · Visibility (robots) · Social (OG/Twitter overrides + image)
 *
 * @package Apollo\SEO
 */

declare(strict_types=1);

namespace Apollo\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Metabox {


	/**
	 * Register the metabox for all SEO-enabled post types.
	 */
	public static function register(): void {
		$post_types = Plugin::get_seo_post_types();

		/*
		 * PRIORITY 'low', NOT 'high' (2026-08-01).
		 *
		 * This box registered at 'high' in the 'normal' context, the same slot
		 * apollo-events uses for "📅 Data & Horário" and "📍 Local & Lineup".
		 * Within one context WordPress orders by priority first and
		 * registration order second, and apollo-seo's add_meta_boxes callback
		 * was winning the tie — so on the event editor the SEO panel rendered
		 * ABOVE every field that actually defines the event, and an author had
		 * to scroll past a large optional block to reach the date pickers.
		 *
		 * SEO overrides are supplementary to a post's own content on EVERY post
		 * type, not just events: you describe the thing, then you tune how it is
		 * described to crawlers. 'low' encodes that ordering once, centrally,
		 * instead of every content plugin having to outbid this one.
		 *
		 * Filterable so a site that genuinely wants SEO-first can say so without
		 * patching the plugin.
		 *
		 * @param string $priority One of 'high'|'core'|'default'|'low'.
		 * @param string $pt       Post type being registered.
		 */
		foreach ( $post_types as $pt ) {
			$priority = (string) apply_filters( 'apollo/seo/metabox_priority', 'low', $pt );

			add_meta_box(
				'apollo-seo-metabox',
				'◆ Apollo SEO',
				array( self::class, 'render' ),
				$pt,
				'normal',
				in_array( $priority, array( 'high', 'core', 'default', 'low' ), true ) ? $priority : 'low'
			);
		}
	}

	/**
	 * Render the metabox.
	 */
	public static function render( \WP_Post $post ): void {
		wp_nonce_field( 'apollo_seo_metabox', 'apollo_seo_nonce' );

		$meta = get_post_meta( $post->ID, APOLLO_SEO_POST_META, true );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		$fields = array(
			'title'          => '',
			'description'    => '',
			'canonical'      => '',
			'noindex'        => '',
			'nofollow'       => '',
			'noarchive'      => '',
			'og_title'       => '',
			'og_description' => '',
			'twitter_title'  => '',
			'twitter_desc'   => '',
			'social_image'   => '',
		);

		$meta = wp_parse_args( $meta, $fields );

		?>
		<div class="aseo-metabox">

			<div class="aseo-metabox-tabs">
				<button type="button" class="aseo-mtab aseo-mtab--active" data-tab="general">Geral</button>
				<button type="button" class="aseo-mtab" data-tab="visibility">Visibilidade</button>
				<button type="button" class="aseo-mtab" data-tab="social">Social</button>
			</div>

			<!-- ═══ GENERAL ═══ -->
			<div class="aseo-mtab-panel aseo-mtab-panel--active" data-panel="general">

				<div class="aseo-field">
					<label for="aseo-meta-title">Título SEO</label>
					<input type="text" id="aseo-meta-title"
						name="<?php echo APOLLO_SEO_POST_META; ?>[title]"
						value="<?php echo esc_attr( $meta['title'] ); ?>"
						class="large-text" data-aseo-counter="70"
						placeholder="Título customizado para mecanismos de busca">
					<p class="aseo-counter" data-max="70"><span class="aseo-count">0</span>/70</p>
				</div>

				<div class="aseo-field">
					<label for="aseo-meta-desc">Descrição SEO</label>
					<textarea id="aseo-meta-desc"
						name="<?php echo APOLLO_SEO_POST_META; ?>[description]"
						rows="3" class="large-text" data-aseo-counter="160"
						placeholder="Descrição customizada para buscadores e redes sociais"><?php echo esc_textarea( $meta['description'] ); ?></textarea>
					<p class="aseo-counter" data-max="160"><span class="aseo-count">0</span>/160</p>
				</div>

				<div class="aseo-field">
					<label for="aseo-meta-canonical">URL Canônica</label>
					<input type="url" id="aseo-meta-canonical"
						name="<?php echo APOLLO_SEO_POST_META; ?>[canonical]"
						value="<?php echo esc_url( $meta['canonical'] ); ?>"
						class="large-text"
						placeholder="Deixe em branco para usar a URL padrão">
				</div>

				<!-- SERP Preview -->
				<div class="aseo-preview">
					<p class="aseo-preview-label">Pré-visualização no Google</p>
					<div class="aseo-serp">
						<div class="aseo-serp-title" id="aseo-serp-title"><?php echo esc_html( get_the_title( $post ) ); ?></div>
						<div class="aseo-serp-url"><?php echo esc_url( get_permalink( $post ) ); ?></div>
						<div class="aseo-serp-desc" id="aseo-serp-desc"><?php echo esc_html( wp_trim_words( $post->post_excerpt ?: $post->post_content, 25, '…' ) ); ?></div>
					</div>
				</div>

			</div>

			<!-- ═══ VISIBILITY ═══ -->
			<div class="aseo-mtab-panel" data-panel="visibility">

				<div class="aseo-field">
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_POST_META; ?>[noindex]" value="1"
							<?php checked( $meta['noindex'] ); ?>>
						<strong>noindex</strong> — Não indexar esta página nos mecanismos de busca
					</label>
				</div>

				<div class="aseo-field">
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_POST_META; ?>[nofollow]" value="1"
							<?php checked( $meta['nofollow'] ); ?>>
						<strong>nofollow</strong> — Não seguir links desta página
					</label>
				</div>

				<div class="aseo-field">
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_POST_META; ?>[noarchive]" value="1"
							<?php checked( $meta['noarchive'] ); ?>>
						<strong>noarchive</strong> — Não mostrar versão em cache
					</label>
				</div>

			</div>

			<!-- ═══ SOCIAL ═══ -->
			<div class="aseo-mtab-panel" data-panel="social">

				<div class="aseo-field">
					<label for="aseo-og-title">OG Title (Facebook / WhatsApp)</label>
					<input type="text" id="aseo-og-title"
						name="<?php echo APOLLO_SEO_POST_META; ?>[og_title]"
						value="<?php echo esc_attr( $meta['og_title'] ); ?>"
						class="large-text" placeholder="Título para compartilhamento social">
				</div>

				<div class="aseo-field">
					<label for="aseo-og-desc">OG Description</label>
					<textarea id="aseo-og-desc"
						name="<?php echo APOLLO_SEO_POST_META; ?>[og_description]"
						rows="2" class="large-text"
						placeholder="Descrição para compartilhamento social"><?php echo esc_textarea( $meta['og_description'] ); ?></textarea>
				</div>

				<div class="aseo-field">
					<label for="aseo-tw-title">Twitter Title</label>
					<input type="text" id="aseo-tw-title"
						name="<?php echo APOLLO_SEO_POST_META; ?>[twitter_title]"
						value="<?php echo esc_attr( $meta['twitter_title'] ); ?>"
						class="large-text" placeholder="Título para Twitter Card">
				</div>

				<div class="aseo-field">
					<label for="aseo-tw-desc">Twitter Description</label>
					<textarea id="aseo-tw-desc"
						name="<?php echo APOLLO_SEO_POST_META; ?>[twitter_desc]"
						rows="2" class="large-text"
						placeholder="Descrição para Twitter Card"><?php echo esc_textarea( $meta['twitter_desc'] ); ?></textarea>
				</div>

				<div class="aseo-field">
					<label for="aseo-social-img">Imagem Social (URL)</label>
					<input type="url" id="aseo-social-img"
						name="<?php echo APOLLO_SEO_POST_META; ?>[social_image]"
						value="<?php echo esc_url( $meta['social_image'] ); ?>"
						class="large-text"
						placeholder="URL da imagem para OG/Twitter (1200×630 recomendado)">
				</div>

			</div>

		</div>
		<?php
	}

	/**
	 * Save metabox data.
	 */
	public static function save( int $post_id ): void {
		/* Nonce check */
		if (
			! isset( $_POST['apollo_seo_nonce'] ) ||
			! wp_verify_nonce( $_POST['apollo_seo_nonce'], 'apollo_seo_metabox' )
		) {
			return;
		}

		/* Autosave */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		/* Permission */
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$raw = $_POST[ APOLLO_SEO_POST_META ] ?? array();
		if ( ! is_array( $raw ) ) {
			return;
		}

		$clean = array(
			'title'          => sanitize_text_field( $raw['title'] ?? '' ),
			'description'    => sanitize_textarea_field( $raw['description'] ?? '' ),
			'canonical'      => esc_url_raw( $raw['canonical'] ?? '' ),
			'noindex'        => ! empty( $raw['noindex'] ) ? '1' : '',
			'nofollow'       => ! empty( $raw['nofollow'] ) ? '1' : '',
			'noarchive'      => ! empty( $raw['noarchive'] ) ? '1' : '',
			'og_title'       => sanitize_text_field( $raw['og_title'] ?? '' ),
			'og_description' => sanitize_textarea_field( $raw['og_description'] ?? '' ),
			'twitter_title'  => sanitize_text_field( $raw['twitter_title'] ?? '' ),
			'twitter_desc'   => sanitize_textarea_field( $raw['twitter_desc'] ?? '' ),
			'social_image'   => esc_url_raw( $raw['social_image'] ?? '' ),
		);

		/* Remove empty values to save space */
		$clean = array_filter(
			$clean,
			function ( $v ) {
				return $v !== '';
			}
		);

		if ( $clean ) {
			update_post_meta( $post_id, APOLLO_SEO_POST_META, $clean );
		} else {
			delete_post_meta( $post_id, APOLLO_SEO_POST_META );
		}

		/* Invalidate sitemap cache */
		$post = get_post( $post_id );
		if ( $post ) {
			Sitemap::invalidate_cache( $post_id, $post );
		}
	}
}