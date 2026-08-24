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

		// Save float coords
		foreach ( self::FLOAT_KEYS as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$val = (float) sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				update_post_meta( $post_id, $key, (string) $val );
			}
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

		// Auto-geocode if coords are missing
		Geocoder::maybe_geocode( $post_id );

		do_action( 'apollo/loc/meta_saved', $post_id );
	}
}
