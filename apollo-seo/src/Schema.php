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

		/* Breadcrumbs */
		$breadcrumb = self::breadcrumb_list();
		if ( $breadcrumb && Settings::get( 'schema_breadcrumbs', true ) ) {
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
		$name = Meta::build_title();
		$desc = Meta::build_description();

		/* Determine type */
		$type = 'WebPage';
		if ( is_front_page() ) {
			$type = 'WebPage';
		} elseif ( is_singular() ) {
			$type = 'ItemPage';
		} elseif ( is_archive() || is_home() ) {
			$type = 'CollectionPage';
		} elseif ( is_search() ) {
			$type = 'SearchResultsPage';
		}

		$schema = array(
			'@type'      => $type,
			'@id'        => $url . '#webpage',
			'url'        => $url,
			'name'       => $name,
			'isPartOf'   => array( '@id' => $home . '#website' ),
			'inLanguage' => get_locale(),
		);

		if ( $desc ) {
			$schema['description'] = $desc;
		}

		/* Dates for singular */
		if ( is_singular() ) {
			$schema['datePublished'] = get_the_date( 'c' );
			$schema['dateModified']  = get_the_modified_date( 'c' );
		}

		if ( Settings::get( 'schema_breadcrumbs', true ) ) {
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

		/* Event dates */
		$start_date = get_post_meta( $id, '_event_start_date', true )
			?: get_post_meta( $id, '_event_date', true );
		$end_date   = get_post_meta( $id, '_event_end_date', true );
		$start_time = get_post_meta( $id, '_event_start_time', true );
		$end_time   = get_post_meta( $id, '_event_end_time', true );

		if ( $start_date && $start_time ) {
			$start_date = $start_date . 'T' . $start_time;
		}
		if ( $end_date && $end_time ) {
			$end_date = $end_date . 'T' . $end_time;
		}

		/* Location */
		$loc_id   = get_post_meta( $id, '_event_loc_id', true );
		$loc_name = get_post_meta( $id, '_event_loc_name', true );
		$loc_addr = get_post_meta( $id, '_event_address', true );

		$schema = array(
			'@type'       => 'Event',
			'@id'         => $url . '#event',
			'name'        => $title,
			'url'         => $url,
			'description' => $desc,
		);

		if ( $start_date ) {
			$schema['startDate'] = $start_date;
		}
		if ( $end_date ) {
			$schema['endDate'] = $end_date;
		}

		$schema['eventStatus']         = 'https://schema.org/EventScheduled';
		$schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';

		/* Image */
		if ( ! empty( $image['url'] ) ) {
			$schema['image'] = $image['url'];
		}

		/* Location */
		if ( $loc_name || $loc_addr ) {
			$place = array(
				'@type' => 'Place',
				'name'  => $loc_name ?: '',
			);
			if ( $loc_addr ) {
				$place['address'] = array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => $loc_addr,
					'addressLocality' => 'Rio de Janeiro',
					'addressRegion'   => 'RJ',
					'addressCountry'  => 'BR',
				);
			}
			$schema['location'] = $place;
		}

		/* Organizer */
		$schema['organizer'] = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/' ) . '#organization',
		);

		return $schema;
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
