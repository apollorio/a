<?php

/**
 * Apollo SEO — Meta Tags Engine
 *
 * Generates and outputs ALL SEO meta tags: title, description,
 * canonical, robots, Open Graph, Twitter Cards.
 * Works with standard WP pages AND blank canvas templates.
 *
 * Adapted from The SEO Framework's architecture —
 * simplified for Apollo's specific CPT ecosystem.
 *
 * @package Apollo\SEO
 */

declare(strict_types=1);

namespace Apollo\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Meta {


	/*
	═══════════════════════════════════════════════════════════════
		TITLE ENGINE
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Filter for pre_get_document_title.
	 */
	public static function filter_document_title( string $title ): string {
		return self::build_title();
	}

	/**
	 * Filter for document_title_parts.
	 */
	public static function filter_title_parts( array $parts ): array {
		$title = self::build_title();
		return array( 'title' => $title );
	}

	/**
	 * Build the full <title> string.
	 */
	public static function build_title( array $args = array() ): string {
		$virtual = self::virtual_context();
		/* Virtual routes may ship a complete document title (already branded). */
		if ( $virtual && ! empty( $virtual['title'] ) && ! empty( $virtual['title_complete'] ) ) {
			return self::sanitize_title( (string) $virtual['title'] );
		}

		$sep       = Settings::separator();
		$site_name = Settings::get( 'site_title' ) ?: get_bloginfo( 'name' );
		$location  = Settings::get( 'title_location', 'right' );

		/* Custom title from post meta */
		$custom = self::get_custom_title( $args );
		if ( $custom ) {
			$page_title = $custom;
		} else {
			$page_title = self::get_generated_title( $args );
		}

		if ( ! $page_title ) {
			$page_title = $site_name;
			return $page_title;
		}

		/* Branding */
		if ( $location === 'right' ) {
			$full = $page_title . ' ' . $sep . ' ' . $site_name;
		} else {
			$full = $site_name . ' ' . $sep . ' ' . $page_title;
		}

		return self::sanitize_title( $full );
	}

	/**
	 * Get custom title from post/term meta.
	 */
	private static function get_custom_title( array $args = array() ): string {
		if ( is_singular() || ! empty( $args['post_id'] ) ) {
			$id = $args['post_id'] ?? get_the_ID();
			if ( $id ) {
				$custom = Settings::get_post_meta( (int) $id, 'title' );
				if ( $custom ) {
					return $custom;
				}
			}
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term && isset( $term->term_id ) ) {
				$custom = Settings::get_term_meta( $term->term_id, 'title' );
				if ( $custom ) {
					return $custom;
				}
			}
		}

		return '';
	}

	/**
	 * Auto-generate title from context.
	 */
	private static function get_generated_title( array $args = array() ): string {
		$virtual = self::virtual_context();
		if ( $virtual && ! empty( $virtual['title'] ) ) {
			return (string) $virtual['title'];
		}

		/* Homepage */
		if ( is_front_page() || is_home() ) {
			$ht = Settings::get( 'homepage_title' );
			return $ht ?: get_bloginfo( 'name' );
		}

		/* Archive titles from settings */
		if ( is_post_type_archive( 'event' ) ) {
			return Settings::get( 'archive_title_event', 'Eventos' );
		}
		if ( is_post_type_archive( 'dj' ) ) {
			return Settings::get( 'archive_title_dj', 'DJs' );
		}
		if ( is_post_type_archive( 'local' ) ) {
			return Settings::get( 'archive_title_loc', 'GPS' );
		}
		if ( is_post_type_archive( 'classified' ) ) {
			return Settings::get( 'archive_title_classified', 'Classificados' );
		}

		/* Singular */
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post ) {
				/* Apollo entity titles carry WHEN and WHERE, not just the name —
				   see entity_title(). Falls back to the plain post title. */
				$entity = self::entity_title( $post );
				return '' !== $entity ? $entity : get_the_title( $post );
			}
		}

		/* Taxonomy */
		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term ) {
				return $term->name;
			}
		}

		/* Author */
		if ( is_author() ) {
			$author = get_queried_object();
			return $author->display_name ?? 'Perfil';
		}

		/* Search */
		if ( is_search() ) {
			return 'Busca: ' . get_search_query();
		}

		/* 404 */
		if ( is_404() ) {
			return 'Página não encontrada';
		}

		return '';
	}

	/**
	 * Written fallback when a post carries no "about" text.
	 *
	 * Built from the REAL fields the post does have (date, loc, lineup), never
	 * generic filler — a description that says nothing gets rewritten by Google
	 * anyway, and an identical string across 200 events reads as boilerplate to
	 * both readers and rankers. Ends on an invitation, since these pages exist
	 * to be clicked.
	 *
	 * @param \WP_Post $post Queried object.
	 * @return string
	 */
	private static function entity_description_fallback( \WP_Post $post ): string {
		$name = wp_strip_all_tags( get_the_title( $post ) );

		switch ( $post->post_type ) {
			case 'event':
				$bits = array();

				$start = (string) get_post_meta( $post->ID, '_event_start_date', true );
				$time  = (string) get_post_meta( $post->ID, '_event_start_time', true );
				if ( '' !== $start ) {
					$ts = strtotime( $start );
					if ( $ts ) {
						$bits[] = date_i18n( 'j \d\e F', $ts ) . ( '' !== $time ? ', ' . $time : '' );
					}
				}

				$loc      = function_exists( 'apollo_event_get_loc' ) ? apollo_event_get_loc( $post->ID ) : null;
				$loc_name = is_array( $loc ) ? (string) ( $loc['title'] ?? '' ) : '';
				if ( '' !== $loc_name ) {
					$bits[] = $loc_name;
				}

				$djs = function_exists( 'apollo_event_get_djs' ) ? apollo_event_get_djs( $post->ID ) : array();
				$names = array();
				foreach ( (array) $djs as $dj ) {
					if ( ! empty( $dj['title'] ) ) {
						$names[] = (string) $dj['title'];
					}
				}
				if ( $names ) {
					$bits[] = __( 'Line-up: ', 'apollo-seo' ) . implode( ', ', array_slice( $names, 0, 4 ) );
				}

				$head = $bits
					? sprintf( '%s — %s.', $name, implode( ' · ', $bits ) )
					: sprintf( '%s no Rio de Janeiro.', $name );

				return $head . ' ' . __( 'Veja line-up, local e ingressos no apollo::rio.', 'apollo-seo' );

			case 'dj':
				return sprintf(
					/* translators: %s: DJ name */
					__( '%s na cena carioca. Veja próximos eventos, sets e onde tocar no apollo::rio.', 'apollo-seo' ),
					$name
				);

			case 'local':
				return sprintf(
					/* translators: %s: venue name */
					__( '%s no Rio de Janeiro. Veja a agenda, fotos e como chegar no apollo::rio.', 'apollo-seo' ),
					$name
				);
		}

		return sprintf(
			/* translators: %s: post title */
			__( '%s no apollo::rio — cultura e vida noturna do Rio de Janeiro.', 'apollo-seo' ),
			$name
		);
	}

	/**
	 * Apollo entity title — WHAT @ WHEN — WHERE.
	 *
	 * A party is only searchable by three things at once: its name, its night
	 * and its house. A bare post_title ("INNERSOUNDS") competes with every other
	 * listing of the same party on every ticketing site; "INNERSOUNDS @ 08AGO —
	 * D-Edge Rio" answers the query people actually type ("innersounds d-edge",
	 * "o que fazer 8 de agosto rio") and is self-describing in a shared link.
	 *
	 *   event  →  "Festa Rara @ 25ABR — Arena de Eventos"
	 *   dj     →  "Magri — DJ no Rio de Janeiro"
	 *   local  →  "D-Edge Rio — Casa de Show no Rio de Janeiro"
	 *
	 * Every part is DROPPED when its data is absent rather than emitted empty:
	 * an undated event yields "Festa Rara — Arena de Eventos", never
	 * "Festa Rara @  — ". A separator that survives its own content is the
	 * classic way generated titles start looking broken in SERPs.
	 *
	 * Reads through the ecosystem helpers (apollo_event_get_loc) rather than
	 * touching meta directly, so it follows the same loc resolution the cards
	 * and the single page use.
	 *
	 * @param \WP_Post $post Queried object.
	 * @return string '' when this post type has no entity format.
	 */
	private static function entity_title( \WP_Post $post ): string {
		$name = wp_strip_all_tags( get_the_title( $post ) );
		if ( '' === $name ) {
			return '';
		}

		switch ( $post->post_type ) {
			case 'event':
				$parts = $name;

				/* WHEN — "25ABR", uppercase 3-letter pt-BR month. date_i18n()
				   gives the localised abbreviation; mb_strtoupper keeps it
				   correct for months that carry accents. */
				$start = (string) get_post_meta( $post->ID, '_event_start_date', true );
				if ( '' !== $start ) {
					$ts = strtotime( $start );
					if ( $ts ) {
						$day   = date_i18n( 'd', $ts );
						$month = mb_strtoupper( date_i18n( 'M', $ts ), 'UTF-8' );
						$month = rtrim( $month, '.' );
						$parts .= ' @ ' . $day . $month;
					}
				}

				/* WHERE — resolved loc name. */
				$loc = function_exists( 'apollo_event_get_loc' ) ? apollo_event_get_loc( $post->ID ) : null;
				$loc_name = is_array( $loc ) ? (string) ( $loc['title'] ?? '' ) : '';
				if ( '' !== $loc_name ) {
					$parts .= ' — ' . $loc_name;
				}

				return $parts;

			case 'dj':
				return $name . ' — ' . __( 'DJ no Rio de Janeiro', 'apollo-seo' );

			case 'local':
				return $name . ' — ' . __( 'Casa de Show no Rio de Janeiro', 'apollo-seo' );
		}

		return '';
	}

	/**
	 * Sanitize title string.
	 */
	private static function sanitize_title( string $title ): string {
		$title = wp_strip_all_tags( $title );
		$title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );
		$title = preg_replace( '/\s+/', ' ', $title );
		return trim( $title );
	}

	/*
	═══════════════════════════════════════════════════════════════
		DESCRIPTION ENGINE
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Build meta description.
	 */
	public static function build_description( array $args = array() ): string {
		/* Custom from meta */
		$custom = self::get_custom_description( $args );
		if ( $custom ) {
			return self::clamp( $custom, 160 );
		}

		return self::clamp( self::get_generated_description( $args ), 160 );
	}

	/**
	 * Custom description from post/term meta.
	 */
	private static function get_custom_description( array $args = array() ): string {
		if ( is_singular() || ! empty( $args['post_id'] ) ) {
			$id = $args['post_id'] ?? get_the_ID();
			if ( $id ) {
				return Settings::get_post_meta( (int) $id, 'description' ) ?: '';
			}
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term && isset( $term->term_id ) ) {
				return Settings::get_term_meta( $term->term_id, 'description' ) ?: '';
			}
		}

		return '';
	}

	/**
	 * Auto-generate description from content.
	 */
	private static function get_generated_description( array $args = array() ): string {
		$virtual = self::virtual_context();
		if ( $virtual && ! empty( $virtual['description'] ) ) {
			return self::clamp( (string) $virtual['description'], 160 );
		}

		/* Homepage */
		if ( is_front_page() || is_home() ) {
			$home_desc = Settings::get( 'homepage_desc' );
			if ( $home_desc ) {
				return $home_desc;
			}
			$global = trim( (string) Settings::get( 'site_description' ) );
			if ( $global !== '' ) {
				return $global;
			}
			return get_bloginfo( 'description' );
		}

		/* Archives */
		if ( is_post_type_archive( 'event' ) ) {
			return Settings::get( 'archive_desc_event' );
		}
		if ( is_post_type_archive( 'dj' ) ) {
			return Settings::get( 'archive_desc_dj' );
		}
		if ( is_post_type_archive( 'local' ) ) {
			return Settings::get( 'archive_desc_loc' );
		}
		if ( is_post_type_archive( 'classified' ) ) {
			return Settings::get( 'archive_desc_classified' );
		}

		/* Singular — excerpt or content, then the Apollo entity fallback */
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( $post ) {
				$excerpt = self::extract_excerpt( $post );
				if ( '' !== trim( $excerpt ) ) {
					return $excerpt;
				}
				/* No "about" text was written. An empty meta description lets
				   Google invent one from whatever markup it finds first — usually
				   the nav — so a written fallback is strictly better than none. */
				return self::entity_description_fallback( $post );
			}
		}

		/* Taxonomy */
		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term && $term->description ) {
				return $term->description;
			}
			return sprintf( '%s — conteúdo selecionado na Apollo::Rio', $term->name ?? '' );
		}

		/* Author */
		if ( is_author() ) {
			$author = get_queried_object();
			$bio    = get_the_author_meta( 'description', $author->ID ?? 0 );
			return $bio ?: 'Perfil de membro da comunidade Apollo::Rio';
		}

		return '';
	}

	/**
	 * Extract excerpt from post content.
	 */
	private static function extract_excerpt( \WP_Post $post ): string {
		if ( ! empty( $post->post_excerpt ) ) {
			return wp_strip_all_tags( $post->post_excerpt );
		}

		$content = $post->post_content;
		$content = strip_shortcodes( $content );
		$content = wp_strip_all_tags( $content );
		$content = preg_replace( '/\s+/', ' ', $content );

		return trim( $content );
	}

	/**
	 * Clamp text to N characters at sentence boundary.
	 */
	private static function clamp( string $text, int $max = 160 ): string {
		$text = wp_strip_all_tags( $text );
		$text = preg_replace( '/\s+/', ' ', trim( $text ) );

		if ( mb_strlen( $text ) <= $max ) {
			return $text;
		}

		$cut = mb_substr( $text, 0, $max );
		$pos = mb_strrpos( $cut, '.' );
		if ( $pos !== false && $pos > $max * 0.5 ) {
			return mb_substr( $cut, 0, $pos + 1 );
		}

		$pos = mb_strrpos( $cut, ' ' );
		if ( $pos !== false ) {
			return mb_substr( $cut, 0, $pos ) . '…';
		}

		return $cut . '…';
	}

	/*
	═══════════════════════════════════════════════════════════════
		VIRTUAL ROUTE CONTEXT (blank canvas — /casa, /portal, …)
		═══════════════════════════════════════════════════════════════ */

	/**
	 * SEO context for Apollo virtual routes (no WP queried object).
	 *
	 * Blank-canvas routes render under a neutralized main query, so every
	 * WP conditional (is_singular, is_post_type_archive…) is false and the
	 * default engines emit generic tags. This resolver gives the canonical
	 * routes real titles, descriptions, canonicals and PT+EN keywords.
	 *
	 * apollo-events registers /eventos, /portal and /portal/eventos as
	 * apollo_event_page=portal_archive (not the legacy value "portal").
	 *
	 * @return array<string,mixed>|null
	 */
	public static function virtual_context(): ?array {
		$context = null;
		$event_page = (string) get_query_var( 'apollo_event_page' );

		/* Path fallback: blank-canvas head can run before query vars are
		   visible to get_query_var() on some boots; /eventos is the portal. */
		$req_path = trim( (string) ( wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ) ?: '' ), '/' );
		$is_portal = in_array( $event_page, array( 'portal_archive', 'portal' ), true )
			|| in_array( $req_path, array( 'eventos', 'portal', 'portal/eventos' ), true );

		/* Portal de Eventos — /eventos/ (canonical) + /portal aliases. */
		if ( $is_portal ) {
			$thumb = content_url( 'uploads/2026/09/thumb-eventos.webp' );
			$context = array(
				'title'            => 'Eventos no Rio | Explore, Descubra e Celebre | Apollo',
				'title_complete'   => true,
				'description'      => 'Explore eventos no Rio de Janeiro: festas, shows, festivais, cultura e experiências para viver a cidade do seu jeito.',
				'canonical'        => home_url( '/eventos/' ),
				'robots'           => 'index, follow',
				'theme_color'      => '#0B0B0D',
				'site_name'        => 'Apollo Rio',
				'og_title'         => 'Portal de Eventos no Rio | Explore, Descubra e Celebre',
				'og_description'   => 'Festas, shows, festivais, cultura e experiências. Descubra o que move o Rio, em um só lugar.',
				'schema_type'      => 'CollectionPage',
				'schema_name'      => 'Portal de Eventos no Rio',
				'keywords'         => self::base_keywords( 'events' ),
				'image'            => array(
					'url'    => $thumb,
					'width'  => 1200,
					'height' => 630,
					'type'   => 'image/webp',
					'alt'    => 'Apollo Rio — Portal de Eventos',
				),
			);
		} elseif ( get_query_var( 'apollo_home_page' ) ) {
			$context = array(
				'title'       => 'Apollo::Rio — Hub Cultural de Eventos, Festas e DJs do Rio de Janeiro',
				'description' => 'Apollo::Rio conecta você à cena cultural carioca: agenda de eventos, festas, ' .
					'DJs, locais e música eletrônica no Rio de Janeiro. Your bridge to Rio de Janeiro nightlife, events and electronic music.',
				'canonical'   => home_url( '/casa' ),
				'keywords'    => self::base_keywords( 'landing' ),
			);
		}

		/**
		 * Allow plugins to register SEO context for their own virtual routes.
		 *
		 * @param array|null $context Resolved context or null.
		 */
		return apply_filters( 'apollo/seo/virtual_context', $context );
	}

	/*
	═══════════════════════════════════════════════════════════════
		KEYWORDS ENGINE — automatic PT-BR + EN keywords
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Curated bilingual keyword sets per context.
	 *
	 * @param string $set landing|events|dj|loc.
	 * @return array<int, string>
	 */
	private static function base_keywords( string $set = 'events' ): array {
		$sets = array(
			'landing' => array(
				'apollo rio', 'eventos rio de janeiro', 'festas rio de janeiro', 'agenda cultural rio',
				'o que fazer no rio de janeiro', 'música eletrônica rio', 'cena eletrônica carioca',
				'djs rio de janeiro', 'baladas rio', 'rio de janeiro events', 'rio nightlife',
				'parties in rio de janeiro', 'electronic music rio', 'what to do in rio de janeiro',
			),
			'events'  => array(
				'eventos rio de janeiro', 'festas rio de janeiro', 'agenda de eventos rio', 'festas hoje rio',
				'baladas rio de janeiro', 'música eletrônica rio', 'rave rio de janeiro', 'festivais rio',
				'rio de janeiro events', 'parties in rio de janeiro', 'rio nightlife guide',
				'electronic music events rio', 'clubs in rio de janeiro', 'events today rio',
			),
			'dj'      => array(
				'dj rio de janeiro', 'djs brasil', 'lineup rio', 'música eletrônica rio',
				'booking dj rio', 'cena eletrônica carioca', 'brazilian dj', 'dj in rio de janeiro',
				'electronic music artist rio', 'rio de janeiro dj lineup',
			),
			'loc'     => array(
				'casa de shows rio de janeiro', 'club rio de janeiro', 'espaço de eventos rio',
				'onde sair no rio', 'baladas rio', 'nightclub rio de janeiro', 'venue in rio de janeiro',
				'event space rio', 'where to party in rio',
			),
		);

		return $sets[ $set ] ?? $sets['events'];
	}

	/**
	 * Build the meta keywords list for the current request (PT-BR + EN).
	 *
	 * Priority: custom post meta → virtual context → CPT-aware generation
	 * (event/dj/loc singles enrich the curated set with real taxonomy terms,
	 * DJ names and loc names) → archive/taxonomy sets.
	 *
	 * @param array|null $virtual Pre-resolved virtual context (optional).
	 */
	public static function keywords_content( ?array $virtual = null ): string {
		$keywords = array();

		/* Custom from post meta always wins */
		if ( is_singular() ) {
			$id = get_the_ID();
			if ( $id ) {
				$custom = Settings::get_post_meta( (int) $id, 'keywords' );
				if ( is_string( $custom ) && '' !== trim( $custom ) ) {
					return self::sanitize_keywords( explode( ',', $custom ) );
				}
			}
		}

		/* Virtual routes (/casa, /portal, /portal/eventos, …) */
		$virtual = $virtual ?? self::virtual_context();
		if ( $virtual && ! empty( $virtual['keywords'] ) ) {
			return self::sanitize_keywords( (array) $virtual['keywords'] );
		}

		/* CPT singles — enrich curated set with real entity data */
		if ( is_singular( 'event' ) ) {
			$id       = (int) get_the_ID();
			$keywords = array_merge(
				array( get_the_title( $id ) ),
				self::term_names( $id, array( 'sound', 'event_category', 'event_type', 'season' ) ),
				self::event_entity_names( $id ),
				self::base_keywords( 'events' )
			);
		} elseif ( is_singular( 'dj' ) ) {
			$id       = (int) get_the_ID();
			$keywords = array_merge(
				array( get_the_title( $id ) ),
				self::term_names( $id, array( 'sound' ) ),
				self::base_keywords( 'dj' )
			);
		} elseif ( is_singular( 'local' ) ) {
			$id       = (int) get_the_ID();
			$keywords = array_merge(
				array( get_the_title( $id ) ),
				self::term_names( $id, array( 'local_type', 'local_area' ) ),
				self::base_keywords( 'loc' )
			);
		} elseif ( is_post_type_archive( 'event' ) ) {
			$keywords = self::base_keywords( 'events' );
		} elseif ( is_post_type_archive( 'dj' ) ) {
			$keywords = self::base_keywords( 'dj' );
		} elseif ( is_post_type_archive( 'local' ) ) {
			$keywords = self::base_keywords( 'loc' );
		} elseif ( is_tax( array( 'sound', 'event_category', 'event_type', 'season' ) ) ) {
			$term     = get_queried_object();
			$keywords = array_merge(
				$term instanceof \WP_Term ? array( $term->name, $term->name . ' rio de janeiro' ) : array(),
				self::base_keywords( 'events' )
			);
		} elseif ( is_front_page() || is_home() ) {
			$keywords = self::base_keywords( 'landing' );
		}

		return self::sanitize_keywords( $keywords );
	}

	/**
	 * Taxonomy term names for a post across multiple taxonomies.
	 *
	 * @param int                $post_id    Post ID.
	 * @param array<int, string> $taxonomies Taxonomy slugs.
	 * @return array<int, string>
	 */
	private static function term_names( int $post_id, array $taxonomies ): array {
		$names = array();
		foreach ( $taxonomies as $tax ) {
			if ( ! taxonomy_exists( $tax ) ) {
				continue;
			}
			$terms = get_the_terms( $post_id, $tax );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$names[] = $term->name;
				}
			}
		}
		return $names;
	}

	/**
	 * DJ and loc display names attached to an event.
	 *
	 * @param int $event_id Event post ID.
	 * @return array<int, string>
	 */
	private static function event_entity_names( int $event_id ): array {
		$names = array();

		$dj_ids = get_post_meta( $event_id, '_event_dj_ids', true );
		if ( is_array( $dj_ids ) ) {
			foreach ( array_slice( $dj_ids, 0, 6 ) as $dj_id ) {
				$dj_title = get_the_title( (int) $dj_id );
				if ( $dj_title ) {
					$names[] = $dj_title;
				}
			}
		}

		$loc_id = (int) get_post_meta( $event_id, '_event_loc_id', true );
		if ( $loc_id ) {
			$loc_title = get_the_title( $loc_id );
			if ( $loc_title ) {
				$names[] = $loc_title;
			}
		}

		return $names;
	}

	/**
	 * Dedupe, trim, cap and join keywords.
	 *
	 * @param array<int, string> $keywords Raw keyword list.
	 */
	private static function sanitize_keywords( array $keywords ): string {
		$clean = array();
		$seen  = array();

		foreach ( $keywords as $kw ) {
			$kw = trim( wp_strip_all_tags( (string) $kw ) );
			if ( '' === $kw ) {
				continue;
			}
			$key = mb_strtolower( $kw );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$clean[]      = $kw;
			if ( count( $clean ) >= 20 ) {
				break;
			}
		}

		return implode( ', ', $clean );
	}

	/*
	═══════════════════════════════════════════════════════════════
		IMAGE ENGINE
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Resolve OG image URL with fallback chain.
	 *
	 * 1. Custom social image (post meta)
	 * 2. Featured image
	 * 3. First image in content
	 * 4. Default OG image from settings
	 */
	public static function resolve_image( array $args = array() ): array {
		$post_id = $args['post_id'] ?? ( is_singular() ? get_the_ID() : 0 );
		$post_id = (int) $post_id;

		/*
		 * Event CPT — featured image is the ONLY share image (og + twitter + WhatsApp).
		 * Classifieds / DJ / loc keep the chain below (do not regress /anuncios).
		 */
		if ( $post_id && 'event' === get_post_type( $post_id ) && function_exists( 'apollo_event_share_image' ) ) {
			$img = apollo_event_share_image( $post_id, 'full' );
			if ( ! empty( $img['url'] ) ) {
				// #region agent log
				if ( function_exists( 'apollo_event_debug_log_837565' ) ) {
					apollo_event_debug_log_837565(
						'Meta.php:resolve_image',
						'event OG image resolved',
						array(
							'post_id'      => $post_id,
							'thumb_id'     => (int) get_post_thumbnail_id( $post_id ),
							'banner_meta'  => get_post_meta( $post_id, '_event_banner', true ),
							'resolved_url' => (string) $img['url'],
							'resolved_id'  => (int) ( $img['id'] ?? 0 ),
						),
						'H1'
					);
				}
				// #endregion
				$alt = (string) ( $img['alt'] ?? '' );
				if ( $alt === '' ) {
					$alt = get_the_title( $post_id );
				}
				return array(
					'url'    => (string) $img['url'],
					'width'  => (int) ( $img['width'] ?? 1200 ),
					'height' => (int) ( $img['height'] ?? 630 ),
					'alt'    => $alt,
				);
			}
		}

		/* 1. Custom social image */
		if ( $post_id ) {
			$custom = Settings::get_post_meta( (int) $post_id, 'social_image' );
			if ( $custom ) {
				return array(
					'url'    => $custom,
					'width'  => 1200,
					'height' => 630,
				);
			}
		}

		/* 2. Featured image */
		if ( $post_id && has_post_thumbnail( $post_id ) ) {
			$thumb_id = get_post_thumbnail_id( $post_id );
			$src      = wp_get_attachment_image_src( $thumb_id, 'full' );
			if ( ! $src ) {
				$src = wp_get_attachment_image_src( $thumb_id, 'large' );
			}
			if ( $src ) {
				return array(
					'url'    => $src[0],
					'width'  => $src[1],
					'height' => $src[2],
					'alt'    => get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ),
				);
			}
		}

		/* 3. First content image */
		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( $post && preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/', $post->post_content, $m ) ) {
				return array(
					'url'    => $m[1],
					'width'  => 1200,
					'height' => 630,
				);
			}
		}

		/* 4. Apollo event banner meta */
		if ( $post_id ) {
			$banner = get_post_meta( $post_id, '_event_banner', true )
				?: get_post_meta( $post_id, '_dj_image', true )
				?: get_post_meta( $post_id, '_dj_banner', true );
			if ( $banner ) {
				if ( is_numeric( $banner ) ) {
					$src = wp_get_attachment_image_src( (int) $banner, 'large' );
					if ( $src ) {
						return array(
							'url'    => $src[0],
							'width'  => $src[1],
							'height' => $src[2],
						);
					}
				} else {
					return array(
						'url'    => $banner,
						'width'  => 1200,
						'height' => 630,
					);
				}
			}
		}

		/* 5. Default fallback */
		$default = Settings::get( 'default_og_image', 'https://assets.apollo.rio.br/img/thumb/thumb.jpg' );
		return array(
			'url'    => $default,
			'width'  => 1200,
			'height' => 630,
		);
	}

	/*
	═══════════════════════════════════════════════════════════════
		CANONICAL URL
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Build canonical URL.
	 */
	public static function canonical_url(): string {
		$virtual = self::virtual_context();
		if ( $virtual && ! empty( $virtual['canonical'] ) ) {
			return esc_url( (string) $virtual['canonical'] );
		}

		if ( is_singular() ) {
			$id = get_the_ID();
			if ( $id ) {
				$custom = Settings::get_post_meta( $id, 'canonical' );
				if ( $custom ) {
					return esc_url( $custom );
				}
			}
			return esc_url( get_permalink() );
		}

		if ( is_front_page() ) {
			return esc_url( home_url( '/' ) );
		}

		if ( is_post_type_archive() ) {
			return esc_url( get_post_type_archive_link( get_query_var( 'post_type' ) ) );
		}

		if ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term ) {
				return esc_url( get_term_link( $term ) );
			}
		}

		if ( is_author() ) {
			return esc_url( get_author_posts_url( get_queried_object_id() ) );
		}

		if ( Settings::get( 'pagination_canonical', true ) && is_paged() ) {
			$page1 = get_pagenum_link( 1, false );
			if ( $page1 ) {
				return esc_url( $page1 );
			}
		}

		return esc_url( home_url( $_SERVER['REQUEST_URI'] ?? '/' ) );
	}

	/*
	═══════════════════════════════════════════════════════════════
		ROBOTS
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Build robots meta content.
	 */
	public static function robots_content(): string {
		$directives = array( 'index', 'follow' );

		/* Post-level noindex */
		if ( is_singular() ) {
			$id = get_the_ID();
			if ( $id && Settings::get_post_meta( $id, 'noindex' ) ) {
				$directives[0] = 'noindex';
			}
			if ( $id && Settings::get_post_meta( $id, 'nofollow' ) ) {
				$directives[1] = 'nofollow';
			}
		}

		/* Global noindex rules */
		if ( is_search() && Settings::get( 'noindex_search' ) ) {
			$directives[0] = 'noindex';
		}

		if ( is_paged() && Settings::get( 'noindex_paginated' ) ) {
			$directives[0] = 'noindex';
		}

		if (
			Settings::get( 'noindex_archives' )
			&& (
				is_category()
				|| is_tag()
				|| is_tax()
				|| is_post_type_archive()
				|| ( is_home() && ! is_front_page() )
			)
		) {
			$directives[0] = 'noindex';
		}

		if ( is_404() ) {
			$directives[0] = 'noindex';
		}

		if ( is_date() && Settings::get( 'noindex_date_archives' ) ) {
			$directives[0] = 'noindex';
		}

		if ( is_author() && Settings::get( 'noindex_author_archives' ) ) {
			$directives[0] = 'noindex';
		}

		if ( is_feed() && Settings::get( 'noindex_feeds' ) ) {
			$directives[0] = 'noindex';
		}

		$directives[] = 'max-snippet:-1';
		$directives[] = 'max-image-preview:large';
		$directives[] = 'max-video-preview:-1';

		return implode( ', ', $directives );
	}

	/*
	═══════════════════════════════════════════════════════════════
		OG TYPE
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Determine og:type.
	 */
	private static function og_type(): string {
		if ( is_singular() ) {
			$type = get_post_type();
			if ( $type === 'event' ) {
				return 'website';
			}
			return 'article';
		}
		if ( is_author() ) {
			return 'profile';
		}
		return 'website';
	}

	/*
	═══════════════════════════════════════════════════════════════
		HEAD OUTPUT — Standard WP (wp_head hook)
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Output all SEO meta tags in wp_head.
	 */
	public static function output_head(): void {
		echo "\n<!-- Apollo SEO v" . APOLLO_SEO_VERSION . " -->\n";
		self::print_tags();
		echo "<!-- /Apollo SEO -->\n\n";
	}

	/**
	 * Output tags only (for blank canvas via do_action).
	 */
	public static function output_head_tags_only(): void {
		self::print_tags();
	}

	/**
	 * Print all meta tags.
	 */
	private static function print_tags(): void {
		$virtual     = self::virtual_context();
		$title       = self::build_title();
		$description = self::build_description();
		$canonical   = self::canonical_url();
		$robots      = self::robots_content();
		$image       = self::resolve_image();
		$og_type     = self::og_type();
		$locale      = get_locale();
		$site_name   = Settings::get( 'site_title' ) ?: get_bloginfo( 'name' );
		$keywords    = self::keywords_content( $virtual );

		/* Virtual routes override the query-based engines */
		if ( $virtual ) {
			$title       = ! empty( $virtual['title'] ) ? (string) $virtual['title'] : $title;
			$description = ! empty( $virtual['description'] ) ? self::clamp( (string) $virtual['description'], 160 ) : $description;
			$canonical   = ! empty( $virtual['canonical'] ) ? esc_url( (string) $virtual['canonical'] ) : $canonical;
			$robots      = ! empty( $virtual['robots'] )
				? (string) $virtual['robots']
				: 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';
			$og_type     = 'website';
			if ( ! empty( $virtual['site_name'] ) ) {
				$site_name = (string) $virtual['site_name'];
			}
			if ( ! empty( $virtual['image'] ) && is_array( $virtual['image'] ) && ! empty( $virtual['image']['url'] ) ) {
				$image = array(
					'url'    => (string) $virtual['image']['url'],
					'width'  => (int) ( $virtual['image']['width'] ?? 1200 ),
					'height' => (int) ( $virtual['image']['height'] ?? 630 ),
					'alt'    => (string) ( $virtual['image']['alt'] ?? '' ),
					'type'   => (string) ( $virtual['image']['type'] ?? '' ),
				);
			}
		}

		/* OG title / description overrides */
		$og_title = $title;
		$og_desc  = $description;

		if ( $virtual ) {
			if ( ! empty( $virtual['og_title'] ) ) {
				$og_title = (string) $virtual['og_title'];
			}
			if ( ! empty( $virtual['og_description'] ) ) {
				$og_desc = (string) $virtual['og_description'];
			}
		}

		if ( is_singular() ) {
			$id = get_the_ID();
			if ( $id ) {
				$ot = Settings::get_post_meta( $id, 'og_title' );
				$od = Settings::get_post_meta( $id, 'og_description' );
				if ( $ot ) {
					$og_title = $ot;
				}
				if ( $od ) {
					$og_desc = $od;
				}
			}
		}

		if ( is_front_page() ) {
			$ot = Settings::get( 'homepage_og_title' );
			$od = Settings::get( 'homepage_og_desc' );
			if ( $ot ) {
				$og_title = $ot;
			}
			if ( $od ) {
				$og_desc = $od;
			}
		}

		/* ── Robots ── */
		printf( '<meta name="robots" content="%s">' . "\n", esc_attr( $robots ) );

		/* ── Canonical ── */
		if ( $canonical ) {
			printf( '<link rel="canonical" href="%s">' . "\n", $canonical );
		}

		/* ── Description ── */
		if ( $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		}

		/* ── Keywords (automatic PT-BR + EN) ── */
		if ( $keywords ) {
			printf( '<meta name="keywords" content="%s">' . "\n", esc_attr( $keywords ) );
		}

		/* ── Open Graph ── */
		if ( Settings::get( 'og_tags' ) ) {
			printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $og_type ) );
			printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( $locale ) );
			printf( '<meta property="og:locale:alternate" content="%s">' . "\n", 'pt_BR' === $locale ? 'en_US' : 'pt_BR' );
			printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( $site_name ) );
			printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $og_title ) );

			if ( $og_desc ) {
				printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $og_desc ) );
			}

			if ( $canonical ) {
				printf( '<meta property="og:url" content="%s">' . "\n", $canonical );
			}

			if ( ! empty( $image['url'] ) ) {
				$img_url = esc_url( $image['url'] );
				printf( '<meta property="og:image" content="%s">' . "\n", $img_url );
				if ( 0 === stripos( (string) $image['url'], 'https://' ) ) {
					printf( '<meta property="og:image:secure_url" content="%s">' . "\n", $img_url );
				}
				if ( ! empty( $image['width'] ) ) {
					printf( '<meta property="og:image:width" content="%d">' . "\n", $image['width'] );
				}
				if ( ! empty( $image['height'] ) ) {
					printf( '<meta property="og:image:height" content="%d">' . "\n", $image['height'] );
				}
				if ( ! empty( $image['alt'] ) ) {
					printf( '<meta property="og:image:alt" content="%s">' . "\n", esc_attr( $image['alt'] ) );
				}
				/* og:image:type — explicit virtual type, else detect from URL */
				$img_type = ! empty( $image['type'] ) ? (string) $image['type'] : '';
				if ( '' === $img_type ) {
					$ext_map = array(
						'jpg'  => 'image/jpeg',
						'jpeg' => 'image/jpeg',
						'png'  => 'image/png',
						'gif'  => 'image/gif',
						'webp' => 'image/webp',
						'svg'  => 'image/svg+xml',
					);
					$ext = strtolower( pathinfo( wp_parse_url( $image['url'], PHP_URL_PATH ) ?? '', PATHINFO_EXTENSION ) );
					if ( isset( $ext_map[ $ext ] ) ) {
						$img_type = $ext_map[ $ext ];
					}
				}
				if ( '' !== $img_type ) {
					printf( '<meta property="og:image:type" content="%s">' . "\n", esc_attr( $img_type ) );
				}
			}

			/* Article dates */
			if ( is_singular() ) {
				printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( get_the_date( 'c' ) ) );
				printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( get_the_modified_date( 'c' ) ) );

				/* article:author */
				if ( Settings::get( 'og_article_author' ) ) {
					$author_id  = (int) get_the_author_meta( 'ID' );
					$author_url = get_author_posts_url( $author_id );
					if ( $author_url ) {
						printf( '<meta property="article:author" content="%s">' . "\n", esc_url( $author_url ) );
					}
				}

				/* article:publisher */
				$publisher = Settings::get( 'og_article_publisher' );
				if ( ! $publisher ) {
					$publisher = Settings::get( 'social_facebook' );
				}
				if ( $publisher ) {
					printf( '<meta property="article:publisher" content="%s">' . "\n", esc_url( $publisher ) );
				}
			}

			/* Facebook App ID */
			$fb_app = Settings::get( 'facebook_app_id' );
			if ( $fb_app ) {
				printf( '<meta property="fb:app_id" content="%s">' . "\n", esc_attr( $fb_app ) );
			}
		}

		/* ── Twitter Cards ── */
		if ( Settings::get( 'twitter_tags' ) ) {
			printf( '<meta name="twitter:card" content="%s">' . "\n", esc_attr( Settings::get( 'twitter_card_type', 'summary_large_image' ) ) );

			$tw_site = Settings::get( 'twitter_site' );
			if ( $tw_site ) {
				printf( '<meta name="twitter:site" content="%s">' . "\n", esc_attr( $tw_site ) );
			}

			printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $og_title ) );

			if ( $og_desc ) {
				printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $og_desc ) );
			}

			if ( ! empty( $image['url'] ) ) {
				printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image['url'] ) );
				if ( ! empty( $image['alt'] ) ) {
					printf( '<meta name="twitter:image:alt" content="%s">' . "\n", esc_attr( $image['alt'] ) );
				}
			}
		}

		/* ── Webmaster Verification ── */
		$google = Settings::get( 'google_verification' );
		if ( $google ) {
			printf( '<meta name="google-site-verification" content="%s">' . "\n", esc_attr( $google ) );
		}

		$bing = Settings::get( 'bing_verification' );
		if ( $bing ) {
			printf( '<meta name="msvalidate.01" content="%s">' . "\n", esc_attr( $bing ) );
		}

		$pinterest = Settings::get( 'pinterest_verification' );
		if ( $pinterest ) {
			printf( '<meta name="p:domain_verify" content="%s">' . "\n", esc_attr( $pinterest ) );
		}

		$yandex = Settings::get( 'yandex_verification' );
		if ( $yandex ) {
			printf( '<meta name="yandex-verification" content="%s">' . "\n", esc_attr( $yandex ) );
		}

		/* ── Schema.org JSON-LD ── */
		if ( Settings::get( 'schema_enabled' ) ) {
			$schema = Schema::build();
			if ( $schema ) {
				echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . '</script>' . "\n";
			}
		}
	}

	/*
	═══════════════════════════════════════════════════════════════
		BLANK CANVAS HELPER
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Generate meta tags as a string for blank canvas templates.
	 * Usage in template: <?php echo \Apollo\SEO\Meta::head_string( $args ); ?>
	 */
	public static function head_string( array $args = array() ): string {
		ob_start();
		self::print_tags();
		return ob_get_clean();
	}

	/**
	 * Get title for use in <title> tag in blank canvas.
	 * Usage: <title><?php echo \Apollo\SEO\Meta::title_for( $args ); ?></title>
	 */
	public static function title_for( array $args = array() ): string {
		return esc_html( self::build_title( $args ) );
	}
}
