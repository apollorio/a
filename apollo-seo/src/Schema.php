<?php

/**
 * Apollo SEO — Schema.org JSON-LD Generator
 *
 * Builds structured data @graph with:
 * - WebSite + SearchAction
 * - WebPage / ItemPage / CollectionPage
 * - Organization (Apollo::Rio)
 * - BreadcrumbList
 * - Event (for event CPT)
 * - LocalBusiness (for loc CPT)
 * - Person (for dj CPT / author pages)
 * - Product (for classified CPT)
 *
 * @package Apollo\SEO
 */

declare(strict_types=1);

namespace Apollo\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema {



	/**
	 * Build the full @graph array.
	 */
	public static function build(): array {
		$graph = array();

		/* Always present */
		$graph[] = self::website();
		$graph[] = self::organization();
		$graph[] = self::webpage();

		/* Breadcrumbs — skip for virtual CollectionPage (no crumb trail). */
		$virtual = Meta::virtual_context();
		$skip_crumbs = $virtual && ! empty( $virtual['schema_type'] ) && 'CollectionPage' === $virtual['schema_type'];
		$breadcrumb = self::breadcrumb_list();
		if ( $breadcrumb && ! $skip_crumbs && Settings::get( 'schema_breadcrumbs', true ) ) {
			$graph[] = $breadcrumb;
		}

		/* CPT-specific schemas */
		if ( is_singular( 'event' ) ) {
			$event_schema = self::event_schema();
			if ( $event_schema ) {
				$graph[] = $event_schema;
			}
		}

		if ( is_singular( 'local' ) ) {
			$local_biz = self::local_business();
			if ( $local_biz ) {
				$graph[] = $local_biz;
			}
		}

		if ( is_singular( 'dj' ) ) {
			$person = self::person_from_dj();
			if ( $person ) {
				$graph[] = $person;
			}
		}

		if ( is_singular( 'classified' ) ) {
			$product = self::product();
			if ( $product ) {
				$graph[] = $product;
			}
		}

		if ( is_author() ) {
			$author_person = self::person_from_author();
			if ( $author_person ) {
				$graph[] = $author_person;
			}
		}

		return array(
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		);
	}

	/*
	═══════════════════════════════════════════════════════════════
		CORE SCHEMAS
		═══════════════════════════════════════════════════════════════ */

	/**
	 * WebSite with SearchAction.
	 */
	private static function website(): array {
		$home = home_url( '/' );
		$name = Settings::get( 'site_title' ) ?: get_bloginfo( 'name' );
		$desc  = trim( (string) Settings::get( 'site_description' ) );
		if ( $desc === '' ) {
			$desc = (string) get_bloginfo( 'description' );
		}

		$schema = array(
			'@type'      => 'WebSite',
			'@id'        => $home . '#website',
			'url'        => $home,
			'name'       => $name,
			'description' => $desc,
			'inLanguage' => get_locale(),
			'publisher'  => array( '@id' => $home . '#organization' ),
		);

		if ( Settings::get( 'schema_searchbox', true ) ) {
			$schema['potentialAction'] = array(
				array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => $home . '?s={search_term_string}',
					),
					'query-input' => 'required name=search_term_string',
				),
			);
		}

		return $schema;
	}

	/**
	 * Organization.
	 */
	private static function organization(): array {
		$home    = home_url( '/' );
		$name    = Settings::get( 'knowledge_name', 'Apollo::Rio' );
		$logo    = Settings::get( 'knowledge_logo' );
		$same_as = array();

		/* Social profiles */
		$ig = Settings::get( 'social_instagram' );
		$tw = Settings::get( 'twitter_site' );
		$fb = Settings::get( 'social_facebook' );
		$yt = Settings::get( 'social_youtube' );
		$sc = Settings::get( 'social_soundcloud' );
		$li = Settings::get( 'social_linkedin' );
		$tt = Settings::get( 'social_tiktok' );

		if ( $ig ) {
			$ig_clean = ltrim( $ig, '@/' );
			// If it's a full URL keep it, otherwise build one
			$same_as[] = str_starts_with( $ig, 'http' ) ? $ig : 'https://www.instagram.com/' . $ig_clean;
		}
		if ( $tw ) {
			$tw_clean  = ltrim( $tw, '@' );
			$same_as[] = 'https://twitter.com/' . $tw_clean;
		}
		if ( $fb ) {
			$same_as[] = esc_url( $fb );
		}
		if ( $yt ) {
			$same_as[] = esc_url( $yt );
		}
		if ( $sc ) {
			$same_as[] = esc_url( $sc );
		}
		if ( $li ) {
			$same_as[] = esc_url( $li );
		}
		if ( $tt ) {
			$same_as[] = esc_url( $tt );
		}

		$x_url = trim( (string) Settings::get( 'social_twitter' ) );
		if ( $x_url !== '' ) {
			$same_as[] = esc_url_raw( $x_url );
		}

		$schema = array(
			'@type' => 'Organization',
			'@id'   => $home . '#organization',
			'name'  => $name,
			'url'   => $home,
		);

		if ( $logo ) {
			$schema['logo']  = array(
				'@type'      => 'ImageObject',
				'@id'        => $home . '#logo',
				'url'        => $logo,
				'contentUrl' => $logo,
				'caption'    => $name,
			);
			$schema['image'] = array( '@id' => $home . '#logo' );
		}

		if ( $same_as ) {
			$schema['sameAs'] = $same_as;
		}

		return $schema;
	}

	/**
	 * WebPage / CollectionPage / ItemPage.
	 */
	private static function webpage(): array {
		$home = home_url( '/' );
		$url  = Meta::canonical_url();
		$desc = Meta::build_description();
		$virtual = Meta::virtual_context();

		/* Determine type */
		$type = 'WebPage';
		if ( $virtual && ! empty( $virtual['schema_type'] ) ) {
			$type = (string) $virtual['schema_type'];
		} elseif ( is_front_page() ) {
			$type = 'WebPage';
		} elseif ( is_singular() ) {
			$type = 'ItemPage';
		} elseif ( is_archive() || is_home() ) {
			$type = 'CollectionPage';
		} elseif ( is_search() ) {
			$type = 'SearchResultsPage';
		}

		$name = Meta::build_title();
		if ( $virtual && ! empty( $virtual['schema_name'] ) ) {
			$name = (string) $virtual['schema_name'];
		}

		$site_name = Settings::get( 'site_title' ) ?: get_bloginfo( 'name' );
		if ( $virtual && ! empty( $virtual['site_name'] ) ) {
			$site_name = (string) $virtual['site_name'];
		}

		/* Portal CollectionPage: inline isPartOf WebSite (share-card shape). */
		if ( $virtual && 'CollectionPage' === $type ) {
			$is_part_of = array(
				'@type' => 'WebSite',
				'name'  => $site_name,
				'url'   => $home,
			);
		} else {
			$is_part_of = array( '@id' => $home . '#website' );
		}

		$schema = array(
			'@type'      => $type,
			'@id'        => $url . '#webpage',
			'url'        => $url,
			'name'       => $name,
			'isPartOf'   => $is_part_of,
			'inLanguage' => 'pt_BR' === get_locale() || 'pt-BR' === get_locale() ? 'pt-BR' : get_locale(),
		);

		if ( $desc ) {
			$schema['description'] = $desc;
		}

		/* Dates for singular */
		if ( is_singular() ) {
			$schema['datePublished'] = get_the_date( 'c' );
			$schema['dateModified']  = get_the_modified_date( 'c' );
		}

		if ( Settings::get( 'schema_breadcrumbs', true ) && ! ( $virtual && 'CollectionPage' === $type ) ) {
			$schema['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
		}

		return $schema;
	}

	/*
	═══════════════════════════════════════════════════════════════
		BREADCRUMB
		═══════════════════════════════════════════════════════════════ */

	/**
	 * BreadcrumbList schema.
	 */
	private static function breadcrumb_list(): ?array {
		$home  = home_url( '/' );
		$items = array();

		/* Home */
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => 'Home',
			'item'     => $home,
		);

		$pos = 2;

		if ( is_singular() ) {
			$post      = get_queried_object();
			$post_type = get_post_type();

			/* Archive crumb */
			$pt_object = get_post_type_object( $post_type );
			if ( $pt_object && $pt_object->has_archive ) {
				$archive_url = get_post_type_archive_link( $post_type );
				$items[]     = array(
					'@type'    => 'ListItem',
					'position' => $pos++,
					'name'     => $pt_object->labels->name ?? $pt_object->label,
					'item'     => $archive_url,
				);
			}

			/* Post crumb */
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => get_the_title( $post ),
				'item'     => get_permalink( $post ),
			);
		} elseif ( is_post_type_archive() ) {
			$pt_object = get_post_type_object( get_query_var( 'post_type' ) );
			if ( $pt_object ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $pos++,
					'name'     => $pt_object->labels->name ?? $pt_object->label,
					'item'     => get_post_type_archive_link( $pt_object->name ),
				);
			}
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$term = get_queried_object();
			if ( $term ) {
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $pos++,
					'name'     => $term->name,
					'item'     => get_term_link( $term ),
				);
			}
		} elseif ( is_author() ) {
			$author  = get_queried_object();
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $pos++,
				'name'     => $author->display_name ?? 'Perfil',
				'item'     => get_author_posts_url( $author->ID ?? 0 ),
			);
		}

		if ( count( $items ) < 2 ) {
			return null;
		}

		return array(
			'@type'           => 'BreadcrumbList',
			'@id'             => Meta::canonical_url() . '#breadcrumb',
			'itemListElement' => $items,
		);
	}

	/*
	═══════════════════════════════════════════════════════════════
		CPT-SPECIFIC SCHEMAS
		═══════════════════════════════════════════════════════════════ */

	/**
	 * Event schema for 'event' CPT.
	 */
	private static function event_schema(): ?array {
		$post = get_queried_object();
		if ( ! $post ) {
			return null;
		}

		$id    = $post->ID;
		$title = get_the_title( $post );
		$desc  = Meta::build_description( array( 'post_id' => $id ) );
		$url   = get_permalink( $post );
		$image = Meta::resolve_image( array( 'post_id' => $id ) );

		$start_date = get_post_meta( $id, '_event_start_date', true )
			?: get_post_meta( $id, '_event_date', true );
		$end_date   = get_post_meta( $id, '_event_end_date', true );
		$start_time = get_post_meta( $id, '_event_start_time', true ) ?: '00:00';
		$end_time   = get_post_meta( $id, '_event_end_time', true ) ?: '23:59';

		if ( ! $start_date ) {
			return null;
		}

		$tz        = wp_timezone_string();
		$start_iso = self::event_to_iso8601( $start_date, $start_time, $tz );
		$end_iso   = self::event_to_iso8601( $end_date ?: $start_date, $end_time, $tz );

		$schema = array(
			'@type'       => 'Event',
			'@id'         => $url . '#event',
			'name'        => $title,
			'url'         => $url,
			'description' => $desc,
			'startDate'   => $start_iso,
			'endDate'     => $end_iso,
		);

		$event_status = get_post_meta( $id, '_event_status', true );
		$is_gone      = (bool) get_post_meta( $id, '_event_is_gone', true );
		if ( $is_gone ) {
			$schema['eventStatus'] = 'https://schema.org/EventScheduled';
		} elseif ( $event_status === 'cancelled' ) {
			$schema['eventStatus'] = 'https://schema.org/EventCancelled';
		} elseif ( $event_status === 'postponed' ) {
			$schema['eventStatus'] = 'https://schema.org/EventPostponed';
		} else {
			$schema['eventStatus'] = 'https://schema.org/EventScheduled';
		}

		$ticket_url = (string) get_post_meta( $id, '_event_ticket_url', true );
		$attendance = 'https://schema.org/OfflineEventAttendanceMode';
		if ( $ticket_url && ( str_contains( $ticket_url, 'online' ) || str_contains( $ticket_url, 'live' ) ) ) {
			$attendance = 'https://schema.org/MixedEventAttendanceMode';
		}
		$schema['eventAttendanceMode'] = $attendance;

		if ( ! empty( $image['url'] ) ) {
			$schema['image'] = $image['url'];
		}

		$loc_id = (int) get_post_meta( $id, '_event_loc_id', true );
		if ( $loc_id && function_exists( 'apollo_event_get_loc' ) ) {
			$loc = apollo_event_get_loc( $id );
			if ( $loc ) {
				$place = array(
					'@type' => 'Place',
					'name'  => $loc['title'],
					'url'   => get_permalink( $loc['id'] ),
				);

				$postal = array( '@type' => 'PostalAddress' );
				if ( ! empty( $loc['address'] ) ) {
					$postal['streetAddress'] = $loc['address'];
				}
				if ( ! empty( $loc['city'] ) ) {
					$postal['addressLocality'] = $loc['city'];
				} else {
					$postal['addressLocality'] = 'Rio de Janeiro';
				}
				$postal['addressRegion']  = 'RJ';
				$postal['addressCountry'] = 'BR';

				if ( count( $postal ) > 1 ) {
					$place['address'] = $postal;
				}

				if ( ! empty( $loc['lat'] ) && ! empty( $loc['lng'] ) ) {
					$place['geo'] = array(
						'@type'     => 'GeoCoordinates',
						'latitude'  => (float) $loc['lat'],
						'longitude' => (float) $loc['lng'],
					);
				}

				$schema['location'] = $place;
			}
		}

		$performers = array();
		if ( function_exists( 'apollo_event_get_djs' ) ) {
			foreach ( apollo_event_get_djs( $id ) as $dj ) {
				$performer = array(
					'@type' => 'MusicGroup',
					'name'  => $dj['title'],
					'url'   => get_permalink( $dj['id'] ),
				);
				if ( ! empty( $dj['image'] ) ) {
					$performer['image'] = $dj['image'];
				}
				$performers[] = $performer;
			}
		}

		if ( $performers ) {
			$schema['performer'] = count( $performers ) === 1 ? $performers[0] : $performers;
		}

		$ticket_price = get_post_meta( $id, '_event_ticket_price', true );
		if ( $ticket_url || ( $ticket_price !== '' && $ticket_price !== null ) ) {
			$offers = array(
				'@type'         => 'Offer',
				'availability'  => 'https://schema.org/InStock',
				'priceCurrency' => 'BRL',
				'validFrom'     => $start_iso,
			);
			if ( $ticket_price !== '' && $ticket_price !== null ) {
				$offers['price'] = (float) $ticket_price;
			}
			if ( $ticket_url ) {
				$offers['url'] = esc_url( $ticket_url );
			}
			$schema['offers'] = $offers;
		}

		$schema['organizer'] = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/' ) . '#organization',
		);

		return $schema;
	}

	/**
	 * ISO 8601 date/time with site timezone (event singles).
	 */
	private static function event_to_iso8601( string $date, string $time, string $tz ): string {
		try {
			$dt = new \DateTimeImmutable( $date . 'T' . $time, new \DateTimeZone( $tz ) );
			return $dt->format( \DateTime::ATOM );
		} catch ( \Throwable $e ) {
			return $date . 'T' . $time;
		}
	}

	/**
	 * LocalBusiness for 'local' CPT.
	 */
	private static function local_business(): ?array {
		$post = get_queried_object();
		if ( ! $post ) {
			return null;
		}

		$id    = $post->ID;
		$title = get_the_title( $post );
		$url   = get_permalink( $post );
		$desc  = Meta::build_description( array( 'post_id' => $id ) );
		$image = Meta::resolve_image( array( 'post_id' => $id ) );

		$schema = array(
			'@type'       => 'LocalBusiness',
			'@id'         => $url . '#localbusiness',
			'name'        => $title,
			'url'         => $url,
			'description' => $desc,
		);

		if ( ! empty( $image['url'] ) ) {
			$schema['image'] = $image['url'];
		}

		/* Address */
		$address = get_post_meta( $id, '_local_address', true );
		if ( $address ) {
			$schema['address'] = array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => $address,
				'addressLocality' => 'Rio de Janeiro',
				'addressRegion'   => 'RJ',
				'addressCountry'  => 'BR',
			);
		}

		/* Geo */
		$lat = get_post_meta( $id, '_local_lat', true );
		$lng = get_post_meta( $id, '_local_lng', true );
		if ( $lat && $lng ) {
			$schema['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $lat,
				'longitude' => (float) $lng,
			);
		}

		/* Contact */
		$phone = get_post_meta( $id, '_local_phone', true );
		if ( $phone ) {
			$schema['telephone'] = $phone;
		}

		/* Type */
		$local_type = wp_get_post_terms( $id, 'local_type', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $local_type ) && $local_type ) {
			$schema['additionalType'] = implode( ', ', $local_type );
		}

		return $schema;
	}

	/**
	 * Person for 'dj' CPT.
	 */
	private static function person_from_dj(): ?array {
		$post = get_queried_object();
		if ( ! $post ) {
			return null;
		}

		$id    = $post->ID;
		$title = get_the_title( $post );
		$url   = get_permalink( $post );
		$desc  = Meta::build_description( array( 'post_id' => $id ) );
		$image = Meta::resolve_image( array( 'post_id' => $id ) );

		$schema = array(
			'@type'       => 'Person',
			'@id'         => $url . '#person',
			'name'        => $title,
			'url'         => $url,
			'description' => $desc,
			'jobTitle'    => 'DJ',
		);

		if ( ! empty( $image['url'] ) ) {
			$schema['image'] = $image['url'];
		}

		/* Genres */
		$sounds = wp_get_post_terms( $id, 'sound', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $sounds ) && $sounds ) {
			$schema['knowsAbout'] = $sounds;
		}

		/* Social links */
		$ig      = get_post_meta( $id, '_dj_instagram', true );
		$sc      = get_post_meta( $id, '_dj_soundcloud', true );
		$sp      = get_post_meta( $id, '_dj_spotify', true );
		$same_as = array_filter( array( $ig, $sc, $sp ) );
		if ( $same_as ) {
			$schema['sameAs'] = array_values( $same_as );
		}

		return $schema;
	}

	/**
	 * Person from author page.
	 */
	private static function person_from_author(): ?array {
		$author = get_queried_object();
		if ( ! $author || ! isset( $author->ID ) ) {
			return null;
		}

		$name   = $author->display_name;
		$url    = get_author_posts_url( $author->ID );
		$bio    = get_the_author_meta( 'description', $author->ID );
		$avatar = get_avatar_url( $author->ID, array( 'size' => 512 ) );

		$schema = array(
			'@type' => 'Person',
			'@id'   => $url . '#person',
			'name'  => $name,
			'url'   => $url,
		);

		if ( $bio ) {
			$schema['description'] = $bio;
		}
		if ( $avatar ) {
			$schema['image'] = $avatar;
		}

		return $schema;
	}

	/**
	 * Product for 'classified' CPT.
	 */
	private static function product(): ?array {
		$post = get_queried_object();
		if ( ! $post ) {
			return null;
		}

		$id    = $post->ID;
		$title = get_the_title( $post );
		$url   = get_permalink( $post );
		$desc  = Meta::build_description( array( 'post_id' => $id ) );
		$image = Meta::resolve_image( array( 'post_id' => $id ) );

		$schema = array(
			'@type'       => 'Product',
			'@id'         => $url . '#product',
			'name'        => $title,
			'url'         => $url,
			'description' => $desc,
		);

		if ( ! empty( $image['url'] ) ) {
			$schema['image'] = $image['url'];
		}

		/* Price */
		$price = get_post_meta( $id, '_classified_price', true );
		if ( $price ) {
			$schema['offers'] = array(
				'@type'         => 'Offer',
				'price'         => (float) $price,
				'priceCurrency' => 'BRL',
				'availability'  => 'https://schema.org/InStock',
				'url'           => $url,
			);
		}

		/* Category */
		$domains = wp_get_post_terms( $id, 'classified_domain', array( 'fields' => 'names' ) );
		if ( ! is_wp_error( $domains ) && $domains ) {
			$schema['category'] = implode( ', ', $domains );
		}

		return $schema;
	}
}
