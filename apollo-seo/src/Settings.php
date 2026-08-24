<?php

/**
 * Apollo SEO — Settings Defaults & Helpers
 *
 * @package Apollo\SEO
 */

declare(strict_types=1);

namespace Apollo\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {


	/**
	 * Default option values.
	 */
	public static function defaults(): array {
		return array(
			/* ── General ── */
			'separator'                => 'pipe',
			'title_location'           => 'right',
			'site_title'               => '',
			'site_description'         => 'Plataforma cultural e de vida noturna do Rio de Janeiro',
			'default_og_image'         => 'https://assets.apollo.rio.br/img/thumb/thumb.jpg',
			'knowledge_type'           => 'organization',
			'knowledge_name'           => 'Apollo::Rio',
			'knowledge_logo'           => 'https://assets.apollo.rio.br/img/logo/apollo-logo-og.png',

			/* ── Social ── */
			'og_tags'                  => true,
			'twitter_tags'             => true,
			'twitter_card_type'        => 'summary_large_image',
			'twitter_site'             => '@apollorio',
			'facebook_app_id'          => '',

			/* ── Social URLs (sameAs) ── */
			'social_instagram'         => 'https://instagram.com/apollo.rio',
			'social_facebook'          => '',
			'social_twitter'           => 'https://x.com/apollorio',
			'social_youtube'           => '',
			'social_soundcloud'        => '',
			'social_linkedin'          => '',
			'social_tiktok'            => '',

			/* ── Schema ── */
			'schema_enabled'           => true,
			'schema_breadcrumbs'       => true,
			'schema_searchbox'         => true,

			/* ── Sitemap ── */
			'sitemap_enabled'          => true,
			'sitemap_post_types'       => array( 'event', 'dj', 'local', 'classified', 'hub', 'post', 'page' ),
			'sitemap_taxonomies'       => array( 'event_category', 'sound', 'local_type', 'local_area' ),
			'sitemap_max_urls'         => 2000,

			/* ── Robots ── */
			'noindex_search'           => true,
			'noindex_archives'         => false,
			'noindex_paginated'        => true,

			/* ── Webmaster ── */
			'google_verification'      => '',
			'bing_verification'        => '',

			/* ── Homepage ── */
			'homepage_title'           => 'Apollo::Rio — Vida Noturna & Cultura do Rio de Janeiro',
			'homepage_desc'            => 'Eventos, DJs, locais, classificados e comunidades da cena cultural carioca. A plataforma definitiva da noite do Rio.',
			'homepage_og_title'        => '',
			'homepage_og_desc'         => '',

			/* ── Archive Titles ── */
			'archive_title_event'      => 'Eventos — Agenda Cultural do Rio',
			'archive_desc_event'       => 'Descubra os melhores eventos, festas e experiências culturais do Rio de Janeiro. Filtros por som, data e categoria.',
			'archive_title_dj'         => 'DJs — Os Artistas da Cena Noturna',
			'archive_desc_dj'          => 'Conheça os DJs que movem a vida noturna do Rio. Sets, sons e próximos eventos.',
			'archive_title_loc'        => 'GPS — Mapeando os Melhores Espaços',
			'archive_desc_loc'         => 'Descubra os melhores locais e espaços para eventos no Rio de Janeiro. Casas noturnas, bares, rooftops e mais.',
			'archive_title_classified' => 'Classificados — Oportunidades da Cena',
			'archive_desc_classified'  => 'Classificados da comunidade cultural carioca. Equipamentos, serviços, vagas e oportunidades.',

			/* ── Robots Avançado ── */
			'noindex_date_archives'    => true,
			'noindex_author_archives'  => false,
			'noindex_feeds'            => true,
			'redirect_attachments'     => true,
			'redirect_comment_pages'   => true,
			'pagination_canonical'     => true,
			'robots_block_ai'          => false,
			'robots_txt_custom'        => '',

			/* ── Webmaster Extended ── */
			'pinterest_verification'   => '',
			'yandex_verification'      => '',

			/* ── Social OG Extended ── */
			'og_article_author'        => true,
			'og_article_publisher'     => '',

			/* ── oEmbed ── */
			'oembed_discord_enhance'   => true,

			/* ── REST API ── */
			'rest_api_enabled'         => true,

			/* ── Admin Column ── */
			'admin_seo_column'         => true,
		);
	}

	/**
	 * Get a single option.
	 */
	public static function get( string $key, mixed $fallback = '' ): mixed {
		$opts = get_option( APOLLO_SEO_OPTION, array() );
		if ( isset( $opts[ $key ] ) && $opts[ $key ] !== '' ) {
			return $opts[ $key ];
		}
		$defaults = self::defaults();
		return $defaults[ $key ] ?? $fallback;
	}

	/**
	 * Get all options merged with defaults.
	 */
	public static function all(): array {
		$opts = get_option( APOLLO_SEO_OPTION, array() );
		return wp_parse_args( $opts, self::defaults() );
	}

	/**
	 * Get separator character.
	 */
	public static function separator(): string {
		$map = array(
			'pipe'   => '|',
			'hyphen' => '-',
			'ndash'  => '–',
			'mdash'  => '—',
			'bull'   => '•',
			'middot' => '·',
			'laquo'  => '«',
			'raquo'  => '»',
		);
		$key = self::get( 'separator', 'pipe' );
		return $map[ $key ] ?? '|';
	}

	/**
	 * Get post meta SEO fields.
	 */
	public static function get_post_meta( int $post_id, string $field = '' ): mixed {
		$meta = get_post_meta( $post_id, APOLLO_SEO_POST_META, true );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}
		if ( $field ) {
			return $meta[ $field ] ?? '';
		}
		return $meta;
	}

	/**
	 * Get term meta SEO fields.
	 */
	public static function get_term_meta( int $term_id, string $field = '' ): mixed {
		$meta = get_term_meta( $term_id, APOLLO_SEO_TERM_META, true );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}
		if ( $field ) {
			return $meta[ $field ] ?? '';
		}
		return $meta;
	}
}
