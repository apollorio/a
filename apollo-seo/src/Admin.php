<?php

/**
 * Apollo SEO — Admin Settings Page
 *
 * Premium wp-admin interface with tabbed settings.
 * Tabs: General · Títulos · Social · Schema · Sitemap · Robots · Webmaster · Home · Archives
 *
 * @package Apollo\SEO
 */

declare(strict_types=1);

namespace Apollo\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {



	/**
	 * Add menu page under Apollo or Settings.
	 */
	public static function add_menu_page(): void {
		/* Try to add under an existing Apollo menu */
		$parent = 'apollo';

		$page   = add_submenu_page(
			$parent,
			'Apollo SEO',
			'SEO',
			'manage_options',
			'apollo-seo',
			array( self::class, 'render_page' )
		);

		/* Fallback: add under standard Settings menu */
		if ( ! $page ) {
			add_options_page(
				'Apollo SEO',
				'Apollo SEO',
				'manage_options',
				'apollo-seo',
				array( self::class, 'render_page' )
			);
		}

		/* Enqueue admin assets */
		add_action(
			'admin_enqueue_scripts',
			function ( string $hook ) {
				if ( ! str_contains( $hook, 'apollo-seo' ) ) {
					return;
				}
				wp_enqueue_style(
					'apollo-seo-admin',
					APOLLO_SEO_URL . 'assets/css/admin.css',
					array(),
					APOLLO_SEO_VERSION
				);
				wp_enqueue_script(
					'apollo-seo-admin',
					APOLLO_SEO_URL . 'assets/js/admin.js',
					array(),
					APOLLO_SEO_VERSION,
					true
				);
			}
		);
	}

	/**
	 * Register settings.
	 */
	public static function register_settings(): void {
		register_setting(
			'apollo_seo_group',
			APOLLO_SEO_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize_options' ),
			)
		);
	}

	/**
	 * Sanitize all options.
	 */
	public static function sanitize_options( $input ): array {
		if ( ! is_array( $input ) ) {
			return Settings::defaults();
		}

		$defaults  = Settings::defaults();
		$sanitized = array();

		foreach ( $defaults as $key => $default ) {
			if ( ! isset( $input[ $key ] ) ) {
				$sanitized[ $key ] = $default;
				continue;
			}

			$val = $input[ $key ];

			/* Booleans */
			if ( is_bool( $default ) ) {
				$sanitized[ $key ] = ! empty( $val );
				continue;
			}

			/* Arrays */
			if ( is_array( $default ) ) {
				$sanitized[ $key ] = is_array( $val )
					? array_map( 'sanitize_text_field', $val )
					: $default;
				continue;
			}

			/* Integer (stored as option) */
			if ( 'sitemap_max_urls' === $key ) {
				$sanitized[ $key ] = max( 1, min( 50000, absint( $val ) ) );
				continue;
			}

			if ( 'social_twitter' === $key ) {
				$sanitized[ $key ] = esc_url_raw( (string) $val );
				continue;
			}

			/* Strings */
			$textarea_keys = array( 'robots_txt_custom', 'site_description' );
			if ( in_array( $key, $textarea_keys, true ) ) {
				$sanitized[ $key ] = sanitize_textarea_field( (string) $val );
			} else {
				$sanitized[ $key ] = sanitize_text_field( (string) $val );
			}
		}

		return $sanitized;
	}

	/*
	═══════════════════════════════════════════════════════════════
		RENDER PAGE
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Render the settings page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'apollo-seo' ) );
		}

		$active_tab = sanitize_text_field( $_GET['tab'] ?? 'general' );
		$options    = Settings::all();

		$tabs = array(
			'general'   => 'Geral',
			'titles'    => 'Títulos',
			'social'    => 'Social',
			'schema'    => 'Schema',
			'sitemap'   => 'Sitemap',
			'robots'    => 'Robots',
			'webmaster' => 'Webmaster',
			'home'      => 'Homepage',
			'archives'  => 'Arquivos',
			'avancado'  => 'Avançado',
		);

		?>
		<div class="wrap apollo-seo-wrap">

			<div class="aseo-header">
				<h1 class="aseo-title">
					<span class="aseo-logo">◆</span>
					Apollo<span class="aseo-accent">::</span>SEO
				</h1>
				<p class="aseo-subtitle">Motor de otimização para mecanismos de busca</p>
			</div>

			<nav class="aseo-tabs">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-seo&tab=' . $slug ) ); ?>"
						class="aseo-tab <?php echo $slug === $active_tab ? 'aseo-tab--active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php" class="aseo-form">
				<?php settings_fields( 'apollo_seo_group' ); ?>

				<div class="aseo-panel">
					<?php
					$method = 'tab_' . $active_tab;
					if ( method_exists( self::class, $method ) ) {
						self::$method( $options );
					}
					?>
				</div>

				<div class="aseo-actions">
					<?php submit_button( 'Salvar Configurações', 'primary large', 'submit', false ); ?>
				</div>

			</form>
		</div>
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════
		TAB RENDERERS
		═══════════════════════════════════════════════════════════════ */

	private static function tab_general( array $o ): void {
		?>
		<h2 class="aseo-section-title">Configurações Gerais</h2>

		<table class="form-table aseo-table">
			<tr>
				<th><label for="aseo-site-title">Nome do Site</label></th>
				<td>
					<input type="text" id="aseo-site-title" name="<?php echo APOLLO_SEO_OPTION; ?>[site_title]"
						value="<?php echo esc_attr( $o['site_title'] ?? '' ); ?>" class="regular-text">
					<p class="description">Deixe em branco para usar o nome do WordPress.</p>
				</td>
			</tr>
			<tr>
				<th><label for="aseo-site-description">Descrição global do site</label></th>
				<td>
					<textarea id="aseo-site-description" name="<?php echo APOLLO_SEO_OPTION; ?>[site_description]"
						rows="3" class="large-text"><?php echo esc_textarea( $o['site_description'] ?? '' ); ?></textarea>
					<p class="description">Usada em Schema (WebSite), fallback de meta descrição e alinhada à identidade da marca. Se vazio, usa a descrição padrão do WordPress.</p>
				</td>
			</tr>
			<tr>
				<th><label for="aseo-separator">Separador do Título</label></th>
				<td>
					<select id="aseo-separator" name="<?php echo APOLLO_SEO_OPTION; ?>[separator]">
						<?php
						$seps = array(
							'pipe'   => '| pipe',
							'hyphen' => '- hyphen',
							'ndash'  => '– ndash',
							'mdash'  => '— mdash',
							'bull'   => '• bull',
							'middot' => '· middot',
							'laquo'  => '« laquo',
							'raquo'  => '» raquo',
						);
						foreach ( $seps as $key => $label ) :
							$sel = selected( $o['separator'] ?? 'pipe', $key, false );
							echo '<option value="' . esc_attr( $key ) . '" ' . $sel . '>' . esc_html( $label ) . '</option>';
						endforeach;
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="aseo-title-loc">Posição do Branding</label></th>
				<td>
					<select id="aseo-title-loc" name="<?php echo APOLLO_SEO_OPTION; ?>[title_location]">
						<option value="right" <?php selected( $o['title_location'] ?? 'right', 'right' ); ?>>Título | Site (direita)</option>
						<option value="left" <?php selected( $o['title_location'] ?? 'right', 'left' ); ?>>Site | Título (esquerda)</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="aseo-default-og-img">OG Image Padrão</label></th>
				<td>
					<input type="url" id="aseo-default-og-img" name="<?php echo APOLLO_SEO_OPTION; ?>[default_og_image]"
						value="<?php echo esc_url( $o['default_og_image'] ?? '' ); ?>" class="large-text">
					<p class="description">Imagem padrão para Open Graph / Twitter quando o conteúdo não tem imagem.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function tab_titles( array $o ): void {
		?>
		<h2 class="aseo-section-title">Configuração de Títulos</h2>
		<p class="aseo-info">Títulos gerados automaticamente para cada tipo de conteúdo. Sobrescrito por títulos personalizados nos posts. As <strong>meta descrições</strong> dos arquivos estão na aba <strong>Arquivos</strong>.</p>

		<table class="form-table aseo-table">
			<tr>
				<th><label>Título — Eventos</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[archive_title_event]"
						value="<?php echo esc_attr( $o['archive_title_event'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label>Título — DJs</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[archive_title_dj]"
						value="<?php echo esc_attr( $o['archive_title_dj'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label>Título — GPS (Locais)</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[archive_title_loc]"
						value="<?php echo esc_attr( $o['archive_title_loc'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label>Título — Classificados</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[archive_title_classified]"
						value="<?php echo esc_attr( $o['archive_title_classified'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
		</table>
		<?php
	}

	private static function tab_social( array $o ): void {
		?>
		<h2 class="aseo-section-title">Open Graph &amp; Twitter Cards</h2>

		<h3 class="aseo-subsection">Tags de Compartilhamento</h3>
		<table class="form-table aseo-table">
			<tr>
				<th><label>Open Graph Tags</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[og_tags]" value="1"
							<?php checked( $o['og_tags'] ?? true ); ?>>
						Gerar meta tags Open Graph (Facebook, WhatsApp, Telegram)
					</label>
				</td>
			</tr>
			<tr>
				<th><label>Twitter Cards</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[twitter_tags]" value="1"
							<?php checked( $o['twitter_tags'] ?? true ); ?>>
						Gerar meta tags Twitter Cards
					</label>
				</td>
			</tr>
			<tr>
				<th><label>Tipo de Card</label></th>
				<td>
					<select name="<?php echo APOLLO_SEO_OPTION; ?>[twitter_card_type]">
						<option value="summary_large_image" <?php selected( $o['twitter_card_type'] ?? '', 'summary_large_image' ); ?>>Summary Large Image</option>
						<option value="summary" <?php selected( $o['twitter_card_type'] ?? '', 'summary' ); ?>>Summary</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label>Incluir article:author</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[og_article_author]" value="1"
							<?php checked( $o['og_article_author'] ?? true ); ?>>
						Adicionar <code>article:author</code> apontando para a página do autor
					</label>
				</td>
			</tr>
			<tr>
				<th><label>article:publisher (URL)</label></th>
				<td>
					<input type="url" name="<?php echo APOLLO_SEO_OPTION; ?>[og_article_publisher]"
						value="<?php echo esc_url( $o['og_article_publisher'] ?? '' ); ?>" class="regular-text"
						placeholder="https://www.facebook.com/apollorio">
					<p class="description">URL da página no Facebook usada como publisher. Se em branco, usa a URL da Facebook Page acima.</p>
				</td>
			</tr>
		</table>

		<h3 class="aseo-subsection">Contas Sociais (Schema sameAs)</h3>
		<table class="form-table aseo-table">
			<tr>
				<th><label>Twitter @site</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[twitter_site]"
						value="<?php echo esc_attr( $o['twitter_site'] ?? '' ); ?>" class="regular-text"
						placeholder="@apollorio">
					<p class="description">Handle usado em Twitter Cards e (junto com a URL abaixo) no Schema <code>sameAs</code>.</p>
				</td>
			</tr>
			<tr>
				<th><label>URL do perfil X (Twitter)</label></th>
				<td>
					<input type="url" name="<?php echo APOLLO_SEO_OPTION; ?>[social_twitter]"
						value="<?php echo esc_url( $o['social_twitter'] ?? '' ); ?>" class="large-text"
						placeholder="https://x.com/apollorio">
					<p class="description">Link completo para o Schema <code>sameAs</code> (rede social).</p>
				</td>
			</tr>
			<tr>
				<th><label>Instagram</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[social_instagram]"
						value="<?php echo esc_attr( $o['social_instagram'] ?? '' ); ?>" class="regular-text"
						placeholder="apollo.rio">
				</td>
			</tr>
			<tr>
				<th><label>Facebook Page URL</label></th>
				<td>
					<input type="url" name="<?php echo APOLLO_SEO_OPTION; ?>[social_facebook]"
						value="<?php echo esc_url( $o['social_facebook'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label>Facebook App ID</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[facebook_app_id]"
						value="<?php echo esc_attr( $o['facebook_app_id'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label>YouTube</label></th>
				<td>
					<input type="url" name="<?php echo APOLLO_SEO_OPTION; ?>[social_youtube]"
						value="<?php echo esc_url( $o['social_youtube'] ?? '' ); ?>" class="regular-text"
						placeholder="https://youtube.com/@apollorio">
				</td>
			</tr>
			<tr>
				<th><label>SoundCloud</label></th>
				<td>
					<input type="url" name="<?php echo APOLLO_SEO_OPTION; ?>[social_soundcloud]"
						value="<?php echo esc_url( $o['social_soundcloud'] ?? '' ); ?>" class="regular-text"
						placeholder="https://soundcloud.com/apollorio">
				</td>
			</tr>
			<tr>
				<th><label>LinkedIn</label></th>
				<td>
					<input type="url" name="<?php echo APOLLO_SEO_OPTION; ?>[social_linkedin]"
						value="<?php echo esc_url( $o['social_linkedin'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label>TikTok</label></th>
				<td>
					<input type="url" name="<?php echo APOLLO_SEO_OPTION; ?>[social_tiktok]"
						value="<?php echo esc_url( $o['social_tiktok'] ?? '' ); ?>" class="regular-text"
						placeholder="https://tiktok.com/@apollorio">
				</td>
			</tr>
		</table>
		<?php
	}

	private static function tab_schema( array $o ): void {
		?>
		<h2 class="aseo-section-title">Schema.org (Dados Estruturados)</h2>

		<table class="form-table aseo-table">
			<tr>
				<th><label>Schema JSON-LD</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[schema_enabled]" value="1"
							<?php checked( $o['schema_enabled'] ?? true ); ?>>
						Gerar dados estruturados JSON-LD
					</label>
				</td>
			</tr>
			<tr>
				<th><label>BreadcrumbList</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[schema_breadcrumbs]" value="1"
							<?php checked( $o['schema_breadcrumbs'] ?? true ); ?>>
						Incluir <code>BreadcrumbList</code> no grafo quando aplicável
					</label>
				</td>
			</tr>
			<tr>
				<th><label>SearchAction (caixa de busca)</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[schema_searchbox]" value="1"
							<?php checked( $o['schema_searchbox'] ?? true ); ?>>
						Incluir <code>potentialAction</code> de busca no tipo <code>WebSite</code>
					</label>
				</td>
			</tr>
			<tr>
				<th><label>Tipo de Entidade</label></th>
				<td>
					<select name="<?php echo APOLLO_SEO_OPTION; ?>[knowledge_type]">
						<option value="organization" <?php selected( $o['knowledge_type'] ?? '', 'organization' ); ?>>Organização</option>
						<option value="person" <?php selected( $o['knowledge_type'] ?? '', 'person' ); ?>>Pessoa</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label>Nome da Entidade</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[knowledge_name]"
						value="<?php echo esc_attr( $o['knowledge_name'] ?? '' ); ?>" class="regular-text">
				</td>
			</tr>
			<tr>
				<th><label>Logo URL</label></th>
				<td>
					<input type="url" name="<?php echo APOLLO_SEO_OPTION; ?>[knowledge_logo]"
						value="<?php echo esc_url( $o['knowledge_logo'] ?? '' ); ?>" class="large-text">
				</td>
			</tr>
		</table>
		<?php
	}

	private static function tab_sitemap( array $o ): void {
		$all_pts = array( 'event', 'dj', 'local', 'classified', 'hub', 'post', 'page' );
		$all_tax = array( 'event_category', 'event_type', 'event_tag', 'sound', 'season', 'local_type', 'local_area', 'classified_domain', 'classified_intent', 'category', 'post_tag' );

		$active_pts = $o['sitemap_post_types'] ?? $all_pts;
		$active_tax = $o['sitemap_taxonomies'] ?? $all_tax;
		if ( is_string( $active_pts ) ) {
			$active_pts = array_filter( explode( ',', $active_pts ) );
		}
		if ( is_string( $active_tax ) ) {
			$active_tax = array_filter( explode( ',', $active_tax ) );
		}

		?>
		<h2 class="aseo-section-title">XML Sitemap</h2>
		<p class="aseo-info">
			Sitemap: <a href="<?php echo esc_url( home_url( '/sitemap.xml' ) ); ?>" target="_blank"><?php echo esc_url( home_url( '/sitemap.xml' ) ); ?></a>
		</p>

		<table class="form-table aseo-table">
			<tr>
				<th><label>Sitemap Ativo</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[sitemap_enabled]" value="1"
							<?php checked( $o['sitemap_enabled'] ?? true ); ?>>
						Gerar XML Sitemap
					</label>
				</td>
			</tr>
			<tr>
				<th><label>Post Types no Sitemap</label></th>
				<td>
					<?php foreach ( $all_pts as $pt ) : ?>
						<label style="display:block;margin-bottom:4px">
							<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[sitemap_post_types][]"
								value="<?php echo esc_attr( $pt ); ?>"
								<?php checked( in_array( $pt, $active_pts, true ) ); ?>>
							<?php echo esc_html( $pt ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><label>Taxonomias no Sitemap</label></th>
				<td>
					<?php foreach ( $all_tax as $tax ) : ?>
						<label style="display:block;margin-bottom:4px">
							<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[sitemap_taxonomies][]"
								value="<?php echo esc_attr( $tax ); ?>"
								<?php checked( in_array( $tax, $active_tax, true ) ); ?>>
							<?php echo esc_html( $tax ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th><label for="aseo-sitemap-max">Máximo de URLs por sitemap</label></th>
				<td>
					<input type="number" id="aseo-sitemap-max" name="<?php echo APOLLO_SEO_OPTION; ?>[sitemap_max_urls]"
						value="<?php echo esc_attr( (string) ( $o['sitemap_max_urls'] ?? 2000 ) ); ?>"
						min="1" max="50000" step="1" class="small-text">
					<p class="description">Limite de URLs por arquivo de post type ou taxonomia (1–50000). Padrão 2000.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function tab_robots( array $o ): void {
		?>
		<h2 class="aseo-section-title">Robots &amp; Indexação</h2>

		<h3 class="aseo-subsection">Noindex por Contexto</h3>
		<table class="form-table aseo-table">
			<tr>
				<th><label>Noindex — Busca</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[noindex_search]" value="1"
							<?php checked( $o['noindex_search'] ?? true ); ?>>
						Aplicar <code>noindex</code> em páginas de busca
					</label>
				</td>
			</tr>
			<tr>
				<th><label>Noindex — Paginados</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[noindex_paginated]" value="1"
							<?php checked( $o['noindex_paginated'] ?? true ); ?>>
						Aplicar <code>noindex</code> em páginas paginadas (/page/2, etc.)
					</label>
				</td>
			</tr>
			<tr>
				<th><label>Noindex — Arquivos (taxonomias e CPT)</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[noindex_archives]" value="1"
							<?php checked( $o['noindex_archives'] ?? false ); ?>>
						Aplicar <code>noindex</code> em listagens de categorias, tags, taxonomias custom, arquivos de post type e na página de posts do blog (quando separada da home)
					</label>
				</td>
			</tr>
			<tr>
				<th><label>Noindex — Arquivos de Data</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[noindex_date_archives]" value="1"
							<?php checked( $o['noindex_date_archives'] ?? true ); ?>>
						Aplicar <code>noindex</code> em arquivos por data (dia/mês/ano)
					</label>
				</td>
			</tr>
			<tr>
				<th><label>Noindex — Autores</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[noindex_author_archives]" value="1"
							<?php checked( $o['noindex_author_archives'] ?? false ); ?>>
						Aplicar <code>noindex</code> em páginas de arquivos do autor
					</label>
				</td>
			</tr>
			<tr>
				<th><label>Noindex — Feeds</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[noindex_feeds]" value="1"
							<?php checked( $o['noindex_feeds'] ?? true ); ?>>
						Enviar <code>X-Robots-Tag: noindex</code> em feeds RSS/Atom
					</label>
				</td>
			</tr>
		</table>

		<h3 class="aseo-subsection">Canonical</h3>
		<table class="form-table aseo-table">
			<tr>
				<th><label>Canonical da primeira página</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[pagination_canonical]" value="1"
							<?php checked( $o['pagination_canonical'] ?? true ); ?>>
						Em URLs paginadas (exceto singular já tratado), apontar canonical para a página 1 quando fizer sentido
					</label>
					<p class="description">Reduz conteúdo duplicado em <code>/page/2/</code> em buscas, autores e outros contextos que usam o fallback de URL.</p>
				</td>
			</tr>
		</table>

		<h3 class="aseo-subsection">Redirecionamentos</h3>
		<table class="form-table aseo-table">
			<tr>
				<th><label>Redirecionar Anexos</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[redirect_attachments]" value="1"
							<?php checked( $o['redirect_attachments'] ?? true ); ?>>
						Redirecionar (301) páginas de anexo para o post pai
					</label>
					<p class="description">Evita conteúdo duplicado em páginas de mídia.</p>
				</td>
			</tr>
			<tr>
				<th><label>Redirecionar Páginas de Comentários</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[redirect_comment_pages]" value="1"
							<?php checked( $o['redirect_comment_pages'] ?? true ); ?>>
						Redirecionar (301) <code>?cpage=N</code> de volta ao post principal
					</label>
				</td>
			</tr>
		</table>

		<h3 class="aseo-subsection">Bloqueio de Crawlers de IA</h3>
		<table class="form-table aseo-table">
			<tr>
				<th><label>Bloquear Bots de IA</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[robots_block_ai]" value="1"
							<?php checked( $o['robots_block_ai'] ?? false ); ?>>
						Adicionar regras de bloqueio no <code>robots.txt</code> para GPTBot, Claude, Google-Extended, Apple, Perplexity, ByteSpider e mais
					</label>
				</td>
			</tr>
		</table>

		<h3 class="aseo-subsection">Regras Personalizadas</h3>
		<table class="form-table aseo-table">
			<tr>
				<th><label for="aseo-robots-custom">Regras Extras (robots.txt)</label></th>
				<td>
					<textarea id="aseo-robots-custom"
						name="<?php echo APOLLO_SEO_OPTION; ?>[robots_txt_custom]"
						rows="8" class="large-text code"
						placeholder="User-agent: BadBot&#10;Disallow: /"><?php echo esc_textarea( $o['robots_txt_custom'] ?? '' ); ?></textarea>
					<p class="description">Inseridas no final do <code>robots.txt</code>. Uma diretiva por linha.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function tab_webmaster( array $o ): void {
		?>
		<h2 class="aseo-section-title">Verificação de Webmasters</h2>
		<p class="aseo-info">Adiciona meta tags de verificação ao <code>&lt;head&gt;</code> do site.</p>

		<table class="form-table aseo-table">
			<tr>
				<th><label>Google Search Console</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[google_verification]"
						value="<?php echo esc_attr( $o['google_verification'] ?? '' ); ?>" class="large-text"
						placeholder="Código de verificação do Google">
					<p class="description">Código do atributo <code>content</code> da meta <code>google-site-verification</code>.</p>
				</td>
			</tr>
			<tr>
				<th><label>Bing Webmaster Tools</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[bing_verification]"
						value="<?php echo esc_attr( $o['bing_verification'] ?? '' ); ?>" class="large-text"
						placeholder="Código de verificação do Bing">
					<p class="description">Código do atributo <code>content</code> da meta <code>msvalidate.01</code>.</p>
				</td>
			</tr>
			<tr>
				<th><label>Pinterest Site Verification</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[pinterest_verification]"
						value="<?php echo esc_attr( $o['pinterest_verification'] ?? '' ); ?>" class="large-text"
						placeholder="Código de verificação do Pinterest">
					<p class="description">Código do atributo <code>content</code> da meta <code>p:domain_verify</code>.</p>
				</td>
			</tr>
			<tr>
				<th><label>Yandex Webmaster</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[yandex_verification]"
						value="<?php echo esc_attr( $o['yandex_verification'] ?? '' ); ?>" class="large-text"
						placeholder="Código de verificação do Yandex">
					<p class="description">Código do atributo <code>content</code> da meta <code>yandex-verification</code>.</p>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function tab_home( array $o ): void {
		?>
		<h2 class="aseo-section-title">Homepage SEO</h2>

		<table class="form-table aseo-table">
			<tr>
				<th><label>Título da Home</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[homepage_title]"
						value="<?php echo esc_attr( $o['homepage_title'] ?? '' ); ?>" class="large-text"
						data-aseo-counter="70">
					<p class="description aseo-counter" data-max="70">
						<span class="aseo-count">0</span>/70 caracteres
					</p>
				</td>
			</tr>
			<tr>
				<th><label>Descrição da Home</label></th>
				<td>
					<textarea name="<?php echo APOLLO_SEO_OPTION; ?>[homepage_desc]" rows="3"
						class="large-text" data-aseo-counter="160"><?php echo esc_textarea( $o['homepage_desc'] ?? '' ); ?></textarea>
					<p class="description aseo-counter" data-max="160">
						<span class="aseo-count">0</span>/160 caracteres
					</p>
				</td>
			</tr>
			<tr>
				<th><label>OG Title (Home)</label></th>
				<td>
					<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[homepage_og_title]"
						value="<?php echo esc_attr( $o['homepage_og_title'] ?? '' ); ?>" class="large-text">
					<p class="description">Deixe em branco para usar o título da Home.</p>
				</td>
			</tr>
			<tr>
				<th><label>OG Description (Home)</label></th>
				<td>
					<textarea name="<?php echo APOLLO_SEO_OPTION; ?>[homepage_og_desc]" rows="2"
						class="large-text"><?php echo esc_textarea( $o['homepage_og_desc'] ?? '' ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
	}

	private static function tab_archives( array $o ): void {
		$archives = array(
			'event'      => 'Eventos (/eventos)',
			'dj'         => 'DJs (/djs)',
			'loc'        => 'GPS — Locais (/gps)',
			'classified' => 'Classificados (/anuncios)',
		);

		?>
		<h2 class="aseo-section-title">SEO de Arquivos (CPTs)</h2>

		<?php foreach ( $archives as $slug => $name ) : ?>
			<h3 class="aseo-subsection"><?php echo esc_html( $name ); ?></h3>
			<table class="form-table aseo-table">
				<tr>
					<th><label>Título</label></th>
					<td>
						<input type="text" name="<?php echo APOLLO_SEO_OPTION; ?>[archive_title_<?php echo $slug; ?>]"
							value="<?php echo esc_attr( $o[ 'archive_title_' . $slug ] ?? '' ); ?>" class="regular-text">
					</td>
				</tr>
				<tr>
					<th><label>Descrição</label></th>
					<td>
						<textarea name="<?php echo APOLLO_SEO_OPTION; ?>[archive_desc_<?php echo $slug; ?>]" rows="2"
							class="large-text" data-aseo-counter="160"><?php echo esc_textarea( $o[ 'archive_desc_' . $slug ] ?? '' ); ?></textarea>
						<p class="description aseo-counter" data-max="160">
							<span class="aseo-count">0</span>/160 caracteres
						</p>
					</td>
				</tr>
			</table>
		<?php endforeach; ?>
		<?php
	}

	private static function tab_avancado( array $o ): void {
		?>
		<h2 class="aseo-section-title">Configurações Avançadas</h2>

		<h3 class="aseo-subsection">REST API (Headless SEO)</h3>
		<table class="form-table aseo-table">
			<tr>
				<th><label>REST API de SEO</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[rest_api_enabled]" value="1"
							<?php checked( $o['rest_api_enabled'] ?? true ); ?>>
						Ativar endpoints REST para SEO headless
					</label>
					<p class="description">
						Disponibiliza:
						<code>GET apollo/v1/seo/post/{id}</code>,
						<code>GET apollo/v1/seo/term/{id}</code>,
						<code>GET apollo/v1/seo/home</code>
					</p>
				</td>
			</tr>
		</table>

		<h3 class="aseo-subsection">oEmbed &amp; Discord</h3>
		<table class="form-table aseo-table">
			<tr>
				<th><label>Melhorar oEmbed para Discord</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[oembed_discord_enhance]" value="1"
							<?php checked( $o['oembed_discord_enhance'] ?? true ); ?>>
						Enriquecer respostas oEmbed com imagem, descrição e provider para melhor preview no Discord
					</label>
				</td>
			</tr>
		</table>

		<h3 class="aseo-subsection">Interface do Admin</h3>
		<table class="form-table aseo-table">
			<tr>
				<th><label>Coluna SEO nas Listas</label></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo APOLLO_SEO_OPTION; ?>[admin_seo_column]" value="1"
							<?php checked( $o['admin_seo_column'] ?? true ); ?>>
						Mostrar coluna <strong>◆ SEO</strong> com indicadores de qualidade nas listas de posts
					</label>
					<p class="description">Pontos coloridos indicam qualidade de título, descrição, indexação e imagem (verde = ótimo, amarelo = aviso, vermelho = erro).</p>
				</td>
			</tr>
		</table>
		<?php
	}
}