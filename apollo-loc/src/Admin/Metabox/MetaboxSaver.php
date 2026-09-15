<?php
/**
 * MetaboxSaver — salva TODOS os meta keys do CPT "local"
 *
 * Registrado em save_post_local com priority 20 (após metaboxes renderizarem).
 * Dispara geocodificação automática se lat/lng estiverem ausentes.
 *
 * @package Apollo\Local\Admin\Metabox
 */

declare(strict_types=1);

namespace Apollo\Local\Admin\Metabox;

use Apollo\Local\Geocoder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MetaboxSaver {

	/** Nonce action/field emitidos por AddressMetabox */
	private const NONCE_ACTION = 'apollo_loc_save_meta';
	private const NONCE_FIELD  = 'apollo_loc_metabox_nonce';

	/** Meta keys gerenciados por este saver */
	private const STRING_KEYS = array(
		'_local_name',
		'_local_address',
		'_local_city',
		'_local_state',
		'_local_country',
		'_local_postal',
		'_local_phone',
	);

	private const URL_KEYS = array(
		'_local_website',
		'_local_instagram',
		'_local_facebook',
		'_local_whatsapp',
	);

	private const FLOAT_KEYS = array(
		'_local_lat',
		'_local_lng',
	);

	public function __construct() {
		add_action( 'save_post_' . APOLLO_LOCAL_CPT, array( $this, 'save' ), 20, 2 );
	}

	public function save( int $post_id, \WP_Post $post ): void {
		// Nonce check
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ||
		     ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		// Guard: autosave, revision, permissions
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save string fields
		foreach ( self::STRING_KEYS as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		// Save URL fields
		foreach ( self::URL_KEYS as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, esc_url_raw( wp_unslash( $_POST[ $key ] ) ) );
			}
		}

		/* Save float coords.
		   ─────────────────────────────────────────────────────────────────
		   ⚠ A BLANK FIELD MUST DELETE THE KEY, NOT WRITE "0".

		   This loop used to do `(float) ''` → 0.0 → update_post_meta(…, "0")
		   for every empty coordinate box. Two lines below, Geocoder::
		   maybe_geocode() decides whether to look the address up by testing
		   `$lat !== ''`. "0" is not '', so the guard passed and geocoding was
		   skipped — permanently, on every save. The venue was then pinned at
		   0,0 (Gulf of Guinea), and apollo-maps' shape_loc_payload() drops it,
		   which is why /map/explorer returned an empty locs array with every
		   loc in the database sitting at 0,0.

		   Deleting instead leaves the key genuinely absent, so maybe_geocode()
		   below can do its job. A real 0 is not a loss: latitude 0 / longitude 0
		   is open ocean off Africa and can never be a Rio venue. */
		foreach ( self::FLOAT_KEYS as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw = trim( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
			if ( '' === $raw || ! is_numeric( $raw ) || 0.0 === (float) $raw ) {
				delete_post_meta( $post_id, $key );
				continue;
			}
			update_post_meta( $post_id, $key, (string) (float) $raw );
		}

		// Gallery images (_local_image_1..5) — attachment ID or URL string.
		// Rendered by GalleryMetabox; consumed by single-local.php hero + galeria.
		for ( $i = 1; $i <= 5; $i++ ) {
			$img_key = '_local_image_' . $i;
			if ( ! isset( $_POST[ $img_key ] ) ) {
				continue;
			}
			$raw = sanitize_text_field( wp_unslash( $_POST[ $img_key ] ) );
			if ( '' === $raw ) {
				delete_post_meta( $post_id, $img_key );
			} elseif ( is_numeric( $raw ) ) {
				update_post_meta( $post_id, $img_key, absint( $raw ) );
			} else {
				update_post_meta( $post_id, $img_key, esc_url_raw( $raw ) );
			}
		}

		// Capacity (int)
		if ( isset( $_POST['_local_capacity'] ) ) {
			update_post_meta( $post_id, '_local_capacity', absint( $_POST['_local_capacity'] ) );
		}

		// Price range (allow $ symbols)
		if ( isset( $_POST['_local_price_range'] ) ) {
			$allowed = array( '', '$', '$$', '$$$', '$$$$' );
			$price   = sanitize_text_field( wp_unslash( $_POST['_local_price_range'] ) );
			if ( in_array( $price, $allowed, true ) ) {
				update_post_meta( $post_id, '_local_price_range', $price );
			}
		}

		// Description
		if ( isset( $_POST['_local_description'] ) ) {
			update_post_meta(
				$post_id,
				'_local_description',
				wp_kses_post( wp_unslash( $_POST['_local_description'] ) )
			);
		}

		// Opening hours — per-day indexed array (0=Seg … 6=Dom), time string per day
		if ( isset( $_POST['_local_hours'] ) && is_array( $_POST['_local_hours'] ) ) {
			$hours_in  = array_map( 'sanitize_text_field', array_map( 'wp_unslash', (array) $_POST['_local_hours'] ) );
			$hours_out = array();
			foreach ( $hours_in as $idx => $val ) {
				$hours_out[ (int) $idx ] = (string) $val; // '' = Fechado
			}
			ksort( $hours_out );
			update_post_meta( $post_id, '_local_hours', $hours_out );
		}

		// Amenities repeater (parallel arrays → list of {icon, name, sub})
		if ( isset( $_POST['_local_amenities_name'] ) && is_array( $_POST['_local_amenities_name'] ) ) {
			$icons = isset( $_POST['_local_amenities_icon'] ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', (array) $_POST['_local_amenities_icon'] ) ) : array();
			$names = array_map( 'sanitize_text_field', array_map( 'wp_unslash', (array) $_POST['_local_amenities_name'] ) );
			$subs  = isset( $_POST['_local_amenities_sub'] ) ? array_map( 'sanitize_text_field', array_map( 'wp_unslash', (array) $_POST['_local_amenities_sub'] ) ) : array();
			$amenities = array();
			foreach ( $names as $i => $name ) {
				$name = (string) $name;
				$icon = (string) ( $icons[ $i ] ?? '' );
				if ( '' === $name && '' === $icon ) {
					continue; // skip empty rows
				}
				$amenities[] = array(
					'icon' => $icon,
					'name' => $name,
					'sub'  => (string) ( $subs[ $i ] ?? '' ),
				);
			}
			update_post_meta( $post_id, '_local_amenities', $amenities );
		}

		/* ─── 2026-08-25 · keys that were registered but unreachable ───────
		   Each of these existed in apollo-core's MetaRegistry with no control
		   and no save path, so it could never hold a value. Inputs added in
		   DetailsMetabox; this is the other half. */

		// Region — validated against the same five zone keys the accommodation
		// map filters on. An unknown value is dropped rather than stored, so a
		// tampered POST cannot introduce a zone the map has no polygon for.
		if ( isset( $_POST['_local_region'] ) ) {
			$allowed_regions = array( '', 'zona-sul', 'zona-norte', 'centro', 'zona-oeste', 'niteroi-sg' );
			$region          = sanitize_text_field( wp_unslash( $_POST['_local_region'] ) );
			if ( in_array( $region, $allowed_regions, true ) ) {
				if ( '' === $region ) {
					delete_post_meta( $post_id, '_local_region' );
				} else {
					update_post_meta( $post_id, '_local_region', $region );
				}
			}
		}

		if ( isset( $_POST['_local_tagline'] ) ) {
			update_post_meta( $post_id, '_local_tagline', sanitize_text_field( wp_unslash( $_POST['_local_tagline'] ) ) );
		}

		// Founded year — a blank box must clear the key, not store 0, or the
		// derived "X anos" on the single reads as ~2026 years old.
		if ( isset( $_POST['_local_founded_year'] ) ) {
			$year = absint( $_POST['_local_founded_year'] );
			if ( $year < 1900 || $year > (int) current_time( 'Y' ) ) {
				delete_post_meta( $post_id, '_local_founded_year' );
			} else {
				update_post_meta( $post_id, '_local_founded_year', $year );
			}
		}

		if ( isset( $_POST['_local_user_id'] ) ) {
			$uid = absint( $_POST['_local_user_id'] );
			if ( 0 === $uid ) {
				delete_post_meta( $post_id, '_local_user_id' );
			} else {
				update_post_meta( $post_id, '_local_user_id', $uid );
			}
		}

		// Rooms — edited as one comma-separated line, stored as a clean array.
		// The room COUNT shown on the single is derived from this array, so
		// empty fragments are stripped rather than kept as blank entries.
		if ( isset( $_POST['_local_rooms'] ) ) {
			$rooms_raw = sanitize_text_field( wp_unslash( $_POST['_local_rooms'] ) );
			$rooms     = array_values( array_filter( array_map( 'trim', explode( ',', $rooms_raw ) ), static function ( $r ) {
				return '' !== $r;
			} ) );
			update_post_meta( $post_id, '_local_rooms', $rooms );
		}

		/* _local_testimonials is deliberately NOT saved here. Depoimentos are
		   apollo-comment's vocabulary and storage (15-conventions maps
		   comment/review → depoimento); DetailsMetabox only prints the count so
		   an editor can see the section is fed. Writing it here would create a
		   second owner of the same data. */

		/* Taxonomy selects (local_type / local_area). Driven from here so this
		   screen keeps ONE save_post handler — a second one would race with
		   this at an undefined priority. TaxonomySelectMetabox::save() carries
		   its own nonce check, so a screen without those selects is a no-op. */
		TaxonomySelectMetabox::save( $post_id );

		// Auto-geocode if coords are missing
		Geocoder::maybe_geocode( $post_id );

		do_action( 'apollo/loc/meta_saved', $post_id );
	}
}
