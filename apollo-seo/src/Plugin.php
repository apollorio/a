<?php

/**
 * Apollo SEO — Main Plugin Class
 *
 * Orchestrates all SEO modules: Meta, Schema, Sitemap, Admin.
 * Hooks into wp_head for standard WP pages, and provides
 * apollo_seo_head() for blank canvas templates.
 *
 * Extra features (v1.1.0):
 * - robots.txt custom rules + AI crawler blocking
 * - Feed X-Robots-Tag: noindex header
 * - Attachment-page redirect to parent post
 * - Comment-page canonical redirect to parent post
 * - oEmbed Discord enhancement
 * - REST API for headless SEO data
 * - SEO quality column in WP admin post list
 *
 * @package Apollo\SEO
 */

declare(strict_types=1);

namespace Apollo\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {



	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		/* ── Frontend ── */
		if ( ! is_admin() ) {
			add_action( 'wp_head', array( Meta::class, 'output_head' ), 1 );
			add_filter( 'pre_get_document_title', array( Meta::class, 'filter_document_title' ), 10 );
			add_filter( 'document_title_parts', array( Meta::class, 'filter_title_parts' ), 99 );

			/* Remove WP defaults we replace */
			remove_action( 'wp_head', 'rel_canonical' );
			remove_action( 'wp_head', 'wp_shortlink_wp_head' );
			remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
			remove_action( 'wp_head', 'wp_generator' );

			/* Sitemap */
			Sitemap::init();
			add_action( 'template_redirect', array( Sitemap::class, 'render' ), 0 );

			/* ── Attachment redirect ── */
			add_action( 'template_redirect', array( self::class, 'redirect_attachment_pages' ), 1 );

			/* ── Comment page canonical redirect ── */
			add_action( 'template_redirect', array( self::class, 'redirect_comment_pages' ), 1 );
		}

		/* ── Robots.txt ── */
		add_filter( 'robots_txt', array( self::class, 'filter_robots_txt' ), 99, 2 );

		/* ── Feed noindex header ── */
		add_action( 'wp', array( self::class, 'send_feed_noindex_header' ) );

		/* ── oEmbed Discord enhancement ── */
		if ( Settings::get( 'oembed_discord_enhance' ) ) {
			add_filter( 'oembed_response_data', array( self::class, 'enhance_oembed_for_discord' ), 10, 2 );
		}

		/* ── Admin ── */
		if ( is_admin() ) {
			add_action( 'admin_menu', array( Admin::class, 'add_menu_page' ), 20 );
			add_action( 'admin_init', array( Admin::class, 'register_settings' ) );
			add_action( 'add_meta_boxes', array( Metabox::class, 'register' ) );
			add_action( 'save_post', array( Metabox::class, 'save' ) );

			/* Enqueue admin JS/CSS for metabox on post editors */
			add_action(
				'admin_enqueue_scripts',
				function ( string $hook ) {
					if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
						wp_enqueue_style( 'apollo-seo-admin', APOLLO_SEO_URL . 'assets/css/admin.css', array(), APOLLO_SEO_VERSION );
						wp_enqueue_script( 'apollo-seo-admin', APOLLO_SEO_URL . 'assets/js/admin.js', array(), APOLLO_SEO_VERSION, true );
					}
				}
			);

			/* Term metabox */
			$taxonomies = self::get_seo_taxonomies();
			foreach ( $taxonomies as $tax ) {
				add_action( "{$tax}_edit_form_fields", array( TermMetabox::class, 'render_fields' ) );
				add_action( "edited_{$tax}", array( TermMetabox::class, 'save_fields' ) );
			}

			/* ── SEO quality column ── */
			if ( Settings::get( 'admin_seo_column' ) ) {
				self::register_seo_column();
			}
		}

		/* ── Sitemap cache invalidation ── */
		add_action( 'save_post', array( Sitemap::class, 'invalidate_cache' ), 20, 2 );
		add_action(
			'publish_post',
			function () {
				Sitemap::ping_engines();
			}
		);

		/* ── Global hook for blank canvas templates ── */
		add_action( 'apollo/seo/head', array( Meta::class, 'output_head_tags_only' ) );

		/* ── REST API ── */
		if ( Settings::get( 'rest_api_enabled' ) ) {
			add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
		}
	}

	/*
	═══════════════════════════════════════════════════════════════
		ROBOTS.TXT
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Append custom rules and AI-blocker directives to robots.txt.
	 *
	 * @param string $output   Current robots.txt content.
	 * @param bool   $public   Whether the site is public.
	 */
	public static function filter_robots_txt( string $output, bool $public ): string {
		if ( ! $public ) {
			return $output;
		}

		$additions = '';

		/* ── AI crawler blocking ── */
		if ( Settings::get( 'robots_block_ai' ) ) {
			$ai_bots = array(
				'GPTBot',            /* OpenAI */
				'ChatGPT-User',      /* OpenAI ChatGPT */
				'CCBot',             /* Common Crawl */
				'anthropic-ai',      /* Anthropic Claude */
				'Claude-Web',        /* Anthropic */
				'Omgilibot',         /* Omgili */
				'FacebookBot',       /* Meta AI */
				'Google-Extended',   /* Google Bard/Gemini */
				'Applebot-Extended', /* Apple AI */
				'PerplexityBot',     /* Perplexity AI */
				'YouBot',            /* You.com */
				'Bytespider',        /* ByteDance TikTok AI */
				'cohere-ai',         /* Cohere */
				'Diffbot',           /* Diffbot */
				'ImagesiftBot',      /* Imagesift */
				'magpie-crawler',    /* Magpie */
				'Moz MJ12bot',       /* Moz */
			);
			foreach ( $ai_bots as $bot ) {
				$additions .= "\nUser-agent: {$bot}\nDisallow: /\n";
			}
		}

		/* ── Sitemap reference ── */
		if ( Settings::get( 'sitemap_enabled' ) ) {
			$additions .= "\nSitemap: " . esc_url( home_url( '/sitemap.xml' ) ) . "\n";
		}

		/* ── Custom rules ── */
		$custom = trim( (string) Settings::get( 'robots_txt_custom' ) );
		if ( $custom ) {
			$additions .= "\n# Custom Apollo SEO Rules\n" . $custom . "\n";
		}

		return $output . $additions;
	}

	/*
	═══════════════════════════════════════════════════════════════
		FEED NOINDEX
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Send X-Robots-Tag: noindex header for feeds.
	 */
	public static function send_feed_noindex_header(): void {
		if ( ! is_feed() ) {
			return;
		}
		if ( ! Settings::get( 'noindex_feeds' ) ) {
			return;
		}
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, follow', true );
		}
	}

	/*
	═══════════════════════════════════════════════════════════════
		ATTACHMENT REDIRECT
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Redirect attachment pages to the parent post (or home if orphaned).
	 * Prevents thin-content attachment pages from being indexed.
	 */
	public static function redirect_attachment_pages(): void {
		if ( ! is_attachment() ) {
			return;
		}
		if ( ! Settings::get( 'redirect_attachments' ) ) {
			return;
		}

		$post   = get_post();
		$parent = $post ? $post->post_parent : 0;

		if ( $parent ) {
			$redirect = get_permalink( $parent );
		} else {
			$redirect = home_url( '/' );
		}

		wp_redirect( $redirect, 301 );
		exit;
	}

	/*
	═══════════════════════════════════════════════════════════════
		COMMENT PAGE CANONICAL REDIRECT
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Redirect comment pagination pages back to the parent post.
	 * Prevents /post-slug/comment-page-2/ from being indexed separately.
	 */
	public static function redirect_comment_pages(): void {
		if ( ! Settings::get( 'redirect_comment_pages' ) ) {
			return;
		}

		/* Comment pages: ?cpage= or /comment-page-N/ */
		$cpage = get_query_var( 'cpage' );
		if ( ! $cpage ) {
			return;
		}

		if ( is_singular() ) {
			$canonical = get_permalink();
			if ( $canonical ) {
				wp_redirect( $canonical, 301 );
				exit;
			}
		}
	}

	/*
	═══════════════════════════════════════════════════════════════
		OEMBED DISCORD ENHANCEMENT
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Enhance oEmbed response for better Discord unfurl.
	 * Discord reads the oEmbed JSON to build link previews.
	 *
	 * @param array    $data    oEmbed response data.
	 * @param \WP_Post $post    Post object.
	 */
	public static function enhance_oembed_for_discord( array $data, \WP_Post $post ): array {
		$id    = $post->ID;
		$image = Meta::resolve_image( array( 'post_id' => $id ) );

		if ( ! empty( $image['url'] ) ) {
			$data['thumbnail_url']    = $image['url'];
			$data['thumbnail_width']  = $image['width'] ?? 1200;
			$data['thumbnail_height'] = $image['height'] ?? 630;
		}

		$desc = Meta::build_description( array( 'post_id' => $id ) );
		if ( $desc ) {
			$data['description'] = $desc;
		}

		/* Provider branding */
		$data['provider_name'] = Settings::get( 'site_title' ) ?: get_bloginfo( 'name' );
		$data['provider_url']  = home_url( '/' );

		return $data;
	}

	/*
	═══════════════════════════════════════════════════════════════
		SEO QUALITY COLUMN
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Register the SEO Bar column on all SEO-enabled post type list tables.
	 */
	private static function register_seo_column(): void {
		$post_types = self::get_seo_post_types();

		foreach ( $post_types as $pt ) {
			add_filter(
				"manage_{$pt}_posts_columns",
				function ( array $cols ) {
					$cols['apollo_seo_bar'] = '◆ SEO';
					return $cols;
				}
			);

			add_action(
				"manage_{$pt}_posts_custom_column",
				function ( string $column, int $post_id ) {
					if ( $column !== 'apollo_seo_bar' ) {
						return;
					}
					self::render_seo_bar( $post_id );
				},
				10,
				2
			);
		}
	}

	/**
	 * Render a color-coded SEO quality indicator for a post.
	 *
	 * States: green = good, yellow = warning, red = error, gray = undefined.
	 *
	 * @param int $post_id Post ID.
	 */
	private static function render_seo_bar( int $post_id ): void {
		$meta = Settings::get_post_meta( $post_id );
		$post = get_post( $post_id );

		/* ── Title quality ── */
		$custom_title = $meta['title'] ?? '';
		$auto_title   = $post ? get_the_title( $post ) : '';
		$title_used   = $custom_title ?: $auto_title;
		$title_len    = mb_strlen( $title_used );
		$title_state  = 'gray';
		$title_tip    = 'Título: indefinido';

		if ( $title_len >= 30 && $title_len <= 70 ) {
			$title_state = 'green';
			$title_tip   = "Título: ótimo ({$title_len} chars)";
		} elseif ( $title_len > 0 && $title_len < 30 ) {
			$title_state = 'yellow';
			$title_tip   = "Título: muito curto ({$title_len} chars)";
		} elseif ( $title_len > 70 ) {
			$title_state = 'yellow';
			$title_tip   = "Título: muito longo ({$title_len} chars)";
		}

		/* ── Description quality ── */
		$custom_desc = $meta['description'] ?? '';
		$auto_desc   = $post ? wp_trim_words( wp_strip_all_tags( $post->post_excerpt ?: $post->post_content ), 30, '' ) : '';
		$desc_used   = $custom_desc ?: $auto_desc;
		$desc_len    = mb_strlen( $desc_used );
		$desc_state  = 'gray';
		$desc_tip    = 'Descrição: indefinida';

		if ( $desc_len >= 70 && $desc_len <= 160 ) {
			$desc_state = 'green';
			$desc_tip   = "Descrição: ótima ({$desc_len} chars)";
		} elseif ( $desc_len > 0 && $desc_len < 70 ) {
			$desc_state = 'yellow';
			$desc_tip   = "Descrição: muito curta ({$desc_len} chars)";
		} elseif ( $desc_len > 160 ) {
			$desc_state = 'yellow';
			$desc_tip   = "Descrição: muito longa ({$desc_len} chars)";
		}

		/* ── Noindex ── */
		$noindex       = ! empty( $meta['noindex'] );
		$noindex_state = $noindex ? 'red' : 'green';
		$noindex_tip   = $noindex ? 'noindex ativo — página não indexada' : 'Indexação: ativa';

		/* ── Image ── */
		$has_image   = has_post_thumbnail( $post_id ) || ! empty( $meta['social_image'] );
		$image_state = $has_image ? 'green' : 'yellow';
		$image_tip   = $has_image ? 'Imagem: presente' : 'Imagem: ausente';

		/* ── Output ── */
		$dot_style = 'display:inline-block;width:10px;height:10px;border-radius:50%;margin:0 2px;cursor:help;';
		$colors    = array(
			'green'  => '#00a32a',
			'yellow' => '#dba617',
			'red'    => '#d63638',
			'gray'   => '#999',
		);

		$bars = array(
			array(
				'state' => $title_state,
				'tip'   => $title_tip,
			),
			array(
				'state' => $desc_state,
				'tip'   => $desc_tip,
			),
			array(
				'state' => $noindex_state,
				'tip'   => $noindex_tip,
			),
			array(
				'state' => $image_state,
				'tip'   => $image_tip,
			),
		);

		echo '<span style="display:flex;align-items:center;gap:2px;">';
		foreach ( $bars as $bar ) {
			$color = $colors[ $bar['state'] ] ?? $colors['gray'];
			printf(
				'<span style="%s background:%s;" title="%s"></span>',
				esc_attr( $dot_style ),
				esc_attr( $color ),
				esc_attr( $bar['tip'] )
			);
		}
		echo '</span>';
	}

	/*
	═══════════════════════════════════════════════════════════════
		REST API
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Register REST routes for headless SEO.
	 *
	 * GET  apollo/v1/seo/post/{id}   — SEO data for a post
	 * GET  apollo/v1/seo/term/{id}   — SEO data for a term
	 * GET  apollo/v1/seo/home        — SEO data for the homepage
	 */
	public static function register_rest_routes(): void {
		$ns = defined( 'APOLLO_REST_NAMESPACE' ) ? APOLLO_REST_NAMESPACE : 'apollo/v1';

		register_rest_route(
			$ns,
			'/seo/post/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_get_post_seo' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$ns,
			'/seo/term/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_get_term_seo' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'validate_callback' => fn( $v ) => is_numeric( $v ) && $v > 0,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			$ns,
			'/seo/home',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'rest_get_home_seo' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST: SEO data for a specific post.
	 */
	public static function rest_get_post_seo( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || $post->post_status !== 'publish' ) {
			return new \WP_REST_Response( array( 'error' => 'Post not found or not published' ), 404 );
		}

		$args         = array( 'post_id' => $id );
		$meta         = Settings::get_post_meta( $id );
		$custom_title = $meta['title'] ?? '';
		$custom_desc  = $meta['description'] ?? '';

		$data = array(
			'title'          => Meta::build_title( $args ),
			'description'    => Meta::build_description( $args ),
			'canonical'      => get_permalink( $id ),
			'og_title'       => $meta['og_title'] ?? $custom_title ?: get_the_title( $post ),
			'og_description' => $meta['og_description'] ?? $custom_desc,
			'og_image'       => Meta::resolve_image( $args ),
			'noindex'        => ! empty( $meta['noindex'] ),
			'nofollow'       => ! empty( $meta['nofollow'] ),
			'schema'         => null,
			'post_type'      => $post->post_type,
			'modified'       => $post->post_modified_gmt,
		);

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * REST: SEO data for a specific term.
	 */
	public static function rest_get_term_seo( \WP_REST_Request $request ): \WP_REST_Response {
		$id   = $request->get_param( 'id' );
		$term = get_term( $id );

		if ( ! $term || is_wp_error( $term ) ) {
			return new \WP_REST_Response( array( 'error' => 'Term not found' ), 404 );
		}

		$meta = Settings::get_term_meta( (int) $term->term_id );

		$data = array(
			'title'       => $meta['title'] ?? $term->name,
			'description' => $meta['description'] ?? $term->description,
			'canonical'   => get_term_link( $term ),
			'noindex'     => ! empty( $meta['noindex'] ),
			'taxonomy'    => $term->taxonomy,
			'count'       => $term->count,
		);

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * REST: SEO data for the homepage.
	 */
	public static function rest_get_home_seo( \WP_REST_Request $request ): \WP_REST_Response {
		$data = array(
			'title'          => Settings::get( 'homepage_title' ) ?: get_bloginfo( 'name' ),
			'description'    => Settings::get( 'homepage_desc' ) ?: get_bloginfo( 'description' ),
			'canonical'      => home_url( '/' ),
			'og_title'       => Settings::get( 'homepage_og_title' ) ?: Settings::get( 'homepage_title' ),
			'og_description' => Settings::get( 'homepage_og_desc' ) ?: Settings::get( 'homepage_desc' ),
			'og_image'       => array(
				'url' => Settings::get( 'default_og_image', 'https://assets.apollo.rio.br/img/thumb/thumb.jpg' ),
			),
			'site_name'      => Settings::get( 'site_title' ) ?: get_bloginfo( 'name' ),
			'twitter_site'   => Settings::get( 'twitter_site' ),
		);

		return new \WP_REST_Response( $data, 200 );
	}

	/*
	═══════════════════════════════════════════════════════════════
		HELPERS
		═══════════════════════════════════════════════════════════════ */

	/**
	 * All public CPTs that should get SEO.
	 */
	public static function get_seo_post_types(): array {
		return array( 'event', 'dj', 'local', 'classified', 'hub', 'post', 'page' );
	}

	/**
	 * All taxonomies that should get SEO term metabox.
	 */
	public static function get_seo_taxonomies(): array {
		return array(
			'event_category',
			'event_type',
			'event_tag',
			'sound',
			'season',
			'local_type',
			'local_area',
			'classified_domain',
			'classified_intent',
			'category',
			'post_tag',
		);
	}
}
