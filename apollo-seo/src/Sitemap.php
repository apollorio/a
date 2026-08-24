<?php

/**
 * Apollo SEO — XML Sitemap Generator
 *
 * Generates XML sitemaps for all Apollo CPTs and taxonomies.
 * Supports: sitemap index, per-type sitemaps, XSL stylesheet.
 * Uses WP transient cache for performance.
 *
 * @package Apollo\SEO
 */

declare(strict_types=1);

namespace Apollo\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Sitemap {



	private const CACHE_KEY    = 'apollo_seo_sitemap_';
	private const CACHE_EXPIRY = 12 * HOUR_IN_SECONDS;

	/**
	 * Max URLs per sitemap file (posts or terms query limit).
	 */
	private static function max_urls_per_file(): int {
		$n = (int) Settings::get( 'sitemap_max_urls', 2000 );
		return max( 1, min( 50000, $n ) );
	}

	/**
	 * Initialize sitemap routes.
	 */
	public static function init(): void {
		if ( ! Settings::get( 'sitemap_enabled' ) ) {
			return;
		}

		add_action( 'init', array( self::class, 'register_rewrite_rules' ) );
		add_filter( 'query_vars', array( self::class, 'add_query_vars' ) );
	}

	/**
	 * Register rewrite rules for sitemaps.
	 */
	public static function register_rewrite_rules(): void {
		add_rewrite_rule(
			'sitemap\.xml$',
			'index.php?apollo_sitemap=index',
			'top'
		);
		add_rewrite_rule(
			'sitemap-([a-z0-9_-]+)\.xml$',
			'index.php?apollo_sitemap=$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'sitemap-xsl\.xsl$',
			'index.php?apollo_sitemap=xsl',
			'top'
		);
	}

	/**
	 * Add query vars.
	 */
	public static function add_query_vars( array $vars ): array {
		$vars[] = 'apollo_sitemap';
		return $vars;
	}

	/**
	 * Render sitemap on template_redirect.
	 */
	public static function render(): void {
		$sitemap = get_query_var( 'apollo_sitemap' );
		if ( ! $sitemap ) {
			return;
		}

		/* Disable WP default sitemaps when ours is active */
		add_filter( 'wp_sitemaps_enabled', '__return_false' );

		if ( $sitemap === 'xsl' ) {
			self::render_xsl();
		} elseif ( $sitemap === 'index' ) {
			self::render_index();
		} elseif ( str_starts_with( $sitemap, 'tax-' ) ) {
			$taxonomy = substr( $sitemap, 4 );
			self::render_taxonomy( $taxonomy );
		} else {
			self::render_post_type( $sitemap );
		}

		exit;
	}

	/*
	═══════════════════════════════════════════════════════════════
		SITEMAP INDEX
		═══════════════════════════════════════════════════════════════ */

	private static function render_index(): void {
		$home = home_url( '/' );

		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow' );

		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( $home . 'sitemap-xsl.xsl' ) . '"?>' . "\n";
		echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		/* Post type sitemaps */
		$post_types = Settings::get( 'sitemap_post_types', array() );
		if ( is_string( $post_types ) ) {
			$post_types = array_filter( array_map( 'trim', explode( ',', $post_types ) ) );
		}

		foreach ( $post_types as $pt ) {
			$count = wp_count_posts( $pt );
			if ( ! $count || ( (int) ( $count->publish ?? 0 ) === 0 ) ) {
				continue;
			}
			$lastmod = self::get_post_type_lastmod( $pt );
			echo '  <sitemap>' . "\n";
			echo '    <loc>' . esc_url( $home . 'sitemap-' . $pt . '.xml' ) . '</loc>' . "\n";
			if ( $lastmod ) {
				echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
			}
			echo '  </sitemap>' . "\n";
		}

		/* Taxonomy sitemaps */
		$taxonomies = Settings::get( 'sitemap_taxonomies', array() );
		if ( is_string( $taxonomies ) ) {
			$taxonomies = array_filter( array_map( 'trim', explode( ',', $taxonomies ) ) );
		}

		foreach ( $taxonomies as $tax ) {
			$count = wp_count_terms(
				array(
					'taxonomy'   => $tax,
					'hide_empty' => true,
				)
			);
			if ( ! $count || is_wp_error( $count ) || (int) $count === 0 ) {
				continue;
			}
			echo '  <sitemap>' . "\n";
			echo '    <loc>' . esc_url( $home . 'sitemap-tax-' . $tax . '.xml' ) . '</loc>' . "\n";
			echo '  </sitemap>' . "\n";
		}

		echo '</sitemapindex>' . "\n";
	}

	/*
	═══════════════════════════════════════════════════════════════
		POST TYPE SITEMAP
		═══════════════════════════════════════════════════════════════ */

	private static function render_post_type( string $post_type ): void {
		$cache_key = self::CACHE_KEY . 'pt_' . $post_type;
		$cached    = get_transient( $cache_key );

		if ( $cached !== false ) {
			header( 'Content-Type: application/xml; charset=UTF-8' );
			header( 'X-Robots-Tag: noindex, follow' );
			echo $cached;
			return;
		}

		$home  = home_url( '/' );
		$limit = self::max_urls_per_file();
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		/* Priority mapping */
		$priorities = array(
			'event'      => '0.8',
			'dj'         => '0.7',
			'local'      => '0.8',
			'classified' => '0.6',
			'hub'        => '0.5',
			'post'       => '0.6',
			'page'       => '0.7',
		);
		$priority   = $priorities[ $post_type ] ?? '0.5';
		$changefreq = ( $post_type === 'event' ) ? 'daily' : 'weekly';

		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( $home . 'sitemap-xsl.xsl' ) . '"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $posts as $pid ) {
			/* Skip noindex posts */
			$noindex = Settings::get_post_meta( $pid, 'noindex' );
			if ( $noindex ) {
				continue;
			}

			$url     = get_permalink( $pid );
			$lastmod = get_the_modified_date( 'c', $pid );

			echo '  <url>' . "\n";
			echo '    <loc>' . esc_url( $url ) . '</loc>' . "\n";
			echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
			echo '    <changefreq>' . $changefreq . '</changefreq>' . "\n";
			echo '    <priority>' . $priority . '</priority>' . "\n";
			echo '  </url>' . "\n";
		}

		echo '</urlset>' . "\n";
		$output = ob_get_clean();

		set_transient( $cache_key, $output, self::CACHE_EXPIRY );

		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow' );
		echo $output;
	}

	/*
	═══════════════════════════════════════════════════════════════
		TAXONOMY SITEMAP
		═══════════════════════════════════════════════════════════════ */

	private static function render_taxonomy( string $taxonomy ): void {
		$cache_key = self::CACHE_KEY . 'tax_' . $taxonomy;
		$cached    = get_transient( $cache_key );

		if ( $cached !== false ) {
			header( 'Content-Type: application/xml; charset=UTF-8' );
			header( 'X-Robots-Tag: noindex, follow' );
			echo $cached;
			return;
		}

		$home  = home_url( '/' );
		$limit = self::max_urls_per_file();
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'number'     => $limit,
			)
		);

		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		echo '<?xml-stylesheet type="text/xsl" href="' . esc_url( $home . 'sitemap-xsl.xsl' ) . '"?>' . "\n";
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		foreach ( $terms as $term ) {
			$url = get_term_link( $term );
			if ( is_wp_error( $url ) ) {
				continue;
			}

			echo '  <url>' . "\n";
			echo '    <loc>' . esc_url( $url ) . '</loc>' . "\n";
			echo '    <changefreq>weekly</changefreq>' . "\n";
			echo '    <priority>0.4</priority>' . "\n";
			echo '  </url>' . "\n";
		}

		echo '</urlset>' . "\n";
		$output = ob_get_clean();

		set_transient( $cache_key, $output, self::CACHE_EXPIRY );

		header( 'Content-Type: application/xml; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, follow' );
		echo $output;
	}

	/*
	═══════════════════════════════════════════════════════════════
		XSL STYLESHEET
		═══════════════════════════════════════════════════════════════ */

	private static function render_xsl(): void {
		header( 'Content-Type: text/xsl; charset=UTF-8' );
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		?>
		<xsl:stylesheet version="2.0"
			xmlns:html="http://www.w3.org/TR/REC-html40"
			xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
			xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
			<xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes" />
			<xsl:template match="/">
				<html xmlns="http://www.w3.org/1999/xhtml">

				<head>
					<title>Apollo SEO — Sitemap XML</title>
					<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
					<style type="text/css">
						* {
							margin: 0;
							padding: 0;
							box-sizing: border-box
						}

						body {
							font-family: 'Space Grotesk', system-ui, sans-serif;
							background: #121214;
							color: #e0e0e0
						}

						.container {
							max-width: 1200px;
							margin: 0 auto;
							padding: 2rem
						}

						h1 {
							font-family: 'Shrikhand', cursive;
							font-size: 2rem;
							color: #ff9820;
							margin-bottom: .5rem
						}

						p.info {
							color: #999;
							margin-bottom: 2rem;
							font-size: .875rem
						}

						table {
							width: 100%;
							border-collapse: collapse;
							border-radius: 12px;
							overflow: hidden
						}

						th {
							background: #ff9820;
							color: #fff;
							text-align: left;
							padding: 12px 16px;
							font-weight: 600;
							font-size: .8125rem;
							text-transform: uppercase;
							letter-spacing: .04em
						}

						td {
							padding: 10px 16px;
							border-bottom: 1px solid rgba(255, 255, 255, 0.08);
							font-size: .875rem
						}

						tr:hover td {
							background: rgba(244, 95, 0, .04)
						}

						a {
							color: #ff9820;
							text-decoration: none
						}

						a:hover {
							text-decoration: underline
						}

						.badge {
							display: inline-block;
							padding: 2px 8px;
							border-radius: 4px;
							font-size: .75rem;
							background: rgba(244, 95, 0, .12);
							color: #ff9820
						}
					</style>
				</head>

				<body>
					<div class="container">
						<h1>Apollo::SEO Sitemap</h1>
						<p class="info">Sitemap XML gerado automaticamente pelo Apollo SEO. Otimizado para mecanismos de busca.</p>

						<xsl:choose>
							<xsl:when test="//sitemap:sitemapindex">
								<table>
									<tr>
										<th>Sitemap</th>
										<th>Última Modificação</th>
									</tr>
									<xsl:for-each select="//sitemap:sitemapindex/sitemap:sitemap">
										<tr>
											<td><a>
													<xsl:attribute name="href"><xsl:value-of select="sitemap:loc" /></xsl:attribute><xsl:value-of select="sitemap:loc" />
												</a></td>
											<td><xsl:value-of select="concat(substring(sitemap:lastmod,1,10),' ',substring(sitemap:lastmod,12,5))" /></td>
										</tr>
									</xsl:for-each>
								</table>
							</xsl:when>
							<xsl:otherwise>
								<table>
									<tr>
										<th>URL</th>
										<th>Prioridade</th>
										<th>Frequência</th>
										<th>Última Mod.</th>
									</tr>
									<xsl:for-each select="//sitemap:urlset/sitemap:url">
										<tr>
											<td><a>
													<xsl:attribute name="href"><xsl:value-of select="sitemap:loc" /></xsl:attribute><xsl:value-of select="sitemap:loc" />
												</a></td>
											<td><span class="badge"><xsl:value-of select="sitemap:priority" /></span></td>
											<td><xsl:value-of select="sitemap:changefreq" /></td>
											<td><xsl:value-of select="concat(substring(sitemap:lastmod,1,10),' ',substring(sitemap:lastmod,12,5))" /></td>
										</tr>
									</xsl:for-each>
								</table>
							</xsl:otherwise>
						</xsl:choose>
					</div>
				</body>

				</html>
			</xsl:template>
		</xsl:stylesheet>
		<?php
	}

	/*
	═══════════════════════════════════════════════════════════════
		HELPERS
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Get the last modified date for a post type.
	 */
	private static function get_post_type_lastmod( string $post_type ): string {
		global $wpdb;
		$date = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_modified_gmt FROM {$wpdb->posts}
             WHERE post_type = %s AND post_status = 'publish'
             ORDER BY post_modified_gmt DESC LIMIT 1",
				$post_type
			)
		);

		return $date ? gmdate( 'c', strtotime( $date ) ) : '';
	}

	/**
	 * Invalidate cached sitemaps on content change.
	 */
	public static function invalidate_cache( int $post_id, \WP_Post $post ): void {
		$pt = $post->post_type;
		delete_transient( self::CACHE_KEY . 'pt_' . $pt );

		/* Also clear taxonomy caches for this post's terms */
		$taxonomies = get_object_taxonomies( $pt );
		foreach ( $taxonomies as $tax ) {
			delete_transient( self::CACHE_KEY . 'tax_' . $tax );
		}
	}

	/**
	 * Ping search engines.
	 */
	public static function ping_engines(): void {
		if ( ! Settings::get( 'sitemap_enabled' ) ) {
			return;
		}

		$url = urlencode( home_url( '/sitemap.xml' ) );

		/* Google */
		wp_remote_get(
			'https://www.google.com/ping?sitemap=' . $url,
			array(
				'blocking' => false,
				'timeout'  => 3,
			)
		);

		/* Bing / IndexNow */
		wp_remote_get(
			'https://www.bing.com/ping?sitemap=' . $url,
			array(
				'blocking' => false,
				'timeout'  => 3,
			)
		);
	}
}
