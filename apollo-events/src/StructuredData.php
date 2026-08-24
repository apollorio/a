<?php
/**
 * Structured Data — Schema.org/MusicEvent JSON-LD
 *
 * Injeta <script type="application/ld+json"> nas páginas de single event
 * para SEO e rich snippets no Google (rich results).
 *
 * @see https://schema.org/MusicEvent
 * @see https://developers.google.com/search/docs/appearance/structured-data/event
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StructuredData {

	public function __construct() {
		add_action( 'wp_head', array( $this, 'output' ), 1 );
	}

	/**
	 * Injeta o JSON-LD no <head> apenas em páginas de single event.
	 */
	public function output(): void {
		if ( ! is_singular( APOLLO_EVENT_CPT ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$schema = $this->build( $post );
		if ( empty( $schema ) ) {
			return;
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );

		echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}

	/**
	 * Constrói o array schema.org/MusicEvent.
	 *
	 * @param \WP_Post $post
	 * @return array
	 */
	private function build( \WP_Post $post ): array {
		$post_id    = $post->ID;
		$start_date = get_post_meta( $post_id, '_event_start_date', true );
		$end_date   = get_post_meta( $post_id, '_event_end_date', true );
		$start_time = get_post_meta( $post_id, '_event_start_time', true ) ?: '00:00';
		$end_time   = get_post_meta( $post_id, '_event_end_time', true ) ?: '23:59';

		if ( ! $start_date ) {
			return array();
		}

		// ── Datas em ISO 8601 ──
		$tz        = wp_timezone_string();
		$start_iso = $this->to_iso8601( $start_date, $start_time, $tz );
		$end_iso   = $this->to_iso8601( $end_date ?: $start_date, $end_time, $tz );

		// ── Banner / imagem ──
		$banner_id = get_post_meta( $post_id, '_event_banner', true );
		$image_url = '';
		if ( $banner_id ) {
			$img = wp_get_attachment_image_src( (int) $banner_id, 'large' );
			if ( $img ) {
				$image_url = $img[0];
			}
		}
		if ( ! $image_url && has_post_thumbnail( $post_id ) ) {
			$img = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'large' );
			if ( $img ) {
				$image_url = $img[0];
			}
		}

		// ── Localização (apollo-loc) ──
		$loc_id   = (int) get_post_meta( $post_id, '_event_loc_id', true );
		$location = array(
			'@type' => 'MusicVenue',
			'name'  => get_bloginfo( 'name' ),
		);
		if ( $loc_id && get_post( $loc_id ) ) {
			$loc_name    = get_the_title( $loc_id );
			$loc_address = get_post_meta( $loc_id, '_local_address', true );
			$loc_city    = get_post_meta( $loc_id, '_local_city', true );
			$loc_state   = get_post_meta( $loc_id, '_local_state', true );
			$loc_country = get_post_meta( $loc_id, '_local_country', true ) ?: 'BR';
			$loc_postal  = get_post_meta( $loc_id, '_local_postal', true );
			$loc_lat     = get_post_meta( $loc_id, '_local_lat', true );
			$loc_lng     = get_post_meta( $loc_id, '_local_lng', true );

			$location = array(
				'@type' => 'MusicVenue',
				'name'  => esc_html( $loc_name ),
				'url'   => esc_url( get_permalink( $loc_id ) ),
			);

			$postal_address = array( '@type' => 'PostalAddress' );
			if ( $loc_address ) {
				$postal_address['streetAddress'] = esc_html( $loc_address );
			}
			if ( $loc_city ) {
				$postal_address['addressLocality'] = esc_html( $loc_city );
			}
			if ( $loc_state ) {
				$postal_address['addressRegion'] = esc_html( $loc_state );
			}
			if ( $loc_postal ) {
				$postal_address['postalCode'] = esc_html( $loc_postal );
			}
			$postal_address['addressCountry'] = esc_html( $loc_country );

			if ( count( $postal_address ) > 1 ) {
				$location['address'] = $postal_address;
			}

			if ( $loc_lat && $loc_lng ) {
				$location['geo'] = array(
					'@type'     => 'GeoCoordinates',
					'latitude'  => (float) $loc_lat,
					'longitude' => (float) $loc_lng,
				);
			}
		}

		// ── Performers (DJs) ──
		$performers = array();
		$dj_ids_raw = get_post_meta( $post_id, '_event_dj_ids', true );
		$dj_ids     = array();
		if ( is_string( $dj_ids_raw ) && $dj_ids_raw ) {
			$dj_ids = array_filter( array_map( 'intval', explode( ',', $dj_ids_raw ) ) );
		} elseif ( is_array( $dj_ids_raw ) ) {
			$dj_ids = array_filter( array_map( 'intval', $dj_ids_raw ) );
		}

		foreach ( $dj_ids as $dj_id ) {
			$dj_post = get_post( $dj_id );
			if ( ! $dj_post || $dj_post->post_status !== 'publish' ) {
				continue;
			}
			$performer = array(
				'@type' => 'MusicGroup',
				'name'  => esc_html( $dj_post->post_title ),
				'url'   => esc_url( get_permalink( $dj_post ) ),
			);
			$dj_image  = get_post_meta( $dj_id, '_dj_image', true );
			if ( $dj_image ) {
				$img_src = wp_get_attachment_image_src( (int) $dj_image, 'medium' );
				if ( $img_src ) {
					$performer['image'] = $img_src[0];
				}
			}
			$performers[] = $performer;
		}

		// ── Offers (ingresso) ──
		$ticket_url   = get_post_meta( $post_id, '_event_ticket_url', true );
		$ticket_price = get_post_meta( $post_id, '_event_ticket_price', true );
		$offers       = null;
		if ( $ticket_url || $ticket_price ) {
			$offers = array(
				'@type'         => 'Offer',
				'availability'  => 'https://schema.org/InStock',
				'priceCurrency' => 'BRL',
			);
			if ( $ticket_price !== '' && $ticket_price !== null ) {
				$offers['price'] = (float) $ticket_price;
			}
			if ( $ticket_url ) {
				$offers['url'] = esc_url( $ticket_url );
			}
			$offers['validFrom'] = $start_iso;
		}

		// ── EventStatus ──
		$event_status = get_post_meta( $post_id, '_event_status', true );
		$is_gone      = (bool) get_post_meta( $post_id, '_event_is_gone', true );
		if ( $is_gone ) {
			$schema_status = 'https://schema.org/EventScheduled'; // evento ocorreu, não cancelado
		} elseif ( $event_status === 'cancelled' ) {
			$schema_status = 'https://schema.org/EventCancelled';
		} elseif ( $event_status === 'postponed' ) {
			$schema_status = 'https://schema.org/EventPostponed';
		} else {
			$schema_status = 'https://schema.org/EventScheduled';
		}

		// ── EventAttendanceMode ──
		$attendance_mode = 'https://schema.org/OfflineEventAttendanceMode';
		if ( $ticket_url && ( str_contains( $ticket_url, 'online' ) || str_contains( $ticket_url, 'live' ) ) ) {
			$attendance_mode = 'https://schema.org/MixedEventAttendanceMode';
		}

		// ── Monta o schema ──
		$schema = array(
			'@context'            => 'https://schema.org',
			'@type'               => 'MusicEvent',
			'name'                => esc_html( $post->post_title ),
			'description'         => esc_html( wp_strip_all_tags( $post->post_content ) ),
			'startDate'           => $start_iso,
			'endDate'             => $end_iso,
			'eventStatus'         => $schema_status,
			'eventAttendanceMode' => $attendance_mode,
			'location'            => $location,
			'url'                 => esc_url( get_permalink( $post ) ),
			'organizer'           => array(
				'@type' => 'Organization',
				'name'  => esc_html( get_bloginfo( 'name' ) ),
				'url'   => esc_url( home_url( '/' ) ),
			),
		);

		if ( $image_url ) {
			$schema['image'] = array( $image_url );
		}

		if ( ! empty( $performers ) ) {
			$schema['performer'] = count( $performers ) === 1 ? $performers[0] : $performers;
		}

		if ( $offers ) {
			$schema['offers'] = $offers;
		}

		// Excerpt como alternativa para description vazia
		if ( empty( $schema['description'] ) ) {
			$schema['description'] = esc_html( get_the_excerpt( $post ) );
		}

		/**
		 * Filtra o schema antes da saída — permite que outros plugins completem/alterem.
		 *
		 * @param array    $schema  Schema.org array.
		 * @param \WP_Post $post    Post do evento.
		 */
		return apply_filters( 'apollo/event/structured_data', $schema, $post );
	}

	/**
	 * Converte data + hora para ISO 8601 com timezone.
	 *
	 * @param string $date  Y-m-d
	 * @param string $time  H:i  (pode conter segundos)
	 * @param string $tz    Timezone string (ex: America/Sao_Paulo)
	 * @return string
	 */
	private function to_iso8601( string $date, string $time, string $tz ): string {
		try {
			$dt = new \DateTimeImmutable( $date . 'T' . $time, new \DateTimeZone( $tz ) );
			return $dt->format( \DateTime::ATOM );
		} catch ( \Throwable $e ) {
			return $date . 'T' . $time;
		}
	}
}
