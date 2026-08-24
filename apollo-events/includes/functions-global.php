<?php
/**
 * Global aliases for template-layer helpers (no namespace).
 *
 * Templates call apollo_event_*() in the global namespace.
 * Implementations live in namespace Apollo\Event\ (includes/functions.php).
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_event_user_initials' ) ) {
	function apollo_event_user_initials( string $name ): string {
		return \Apollo\Event\apollo_event_user_initials( $name );
	}
}

if ( ! function_exists( 'apollo_event_user_is_coauthor' ) ) {
	function apollo_event_user_is_coauthor( int $post_id, int $user_id ): bool {
		return \Apollo\Event\apollo_event_user_is_coauthor( $post_id, $user_id );
	}
}

if ( ! function_exists( 'apollo_event_get_user_manageable_events' ) ) {
	function apollo_event_get_user_manageable_events( int $user_id, int $limit = 50 ): array {
		return \Apollo\Event\apollo_event_get_user_manageable_events( $user_id, $limit );
	}
}

if ( ! function_exists( 'apollo_event_prepare_form_payload' ) ) {
	function apollo_event_prepare_form_payload( int $post_id ): array {
		return \Apollo\Event\apollo_event_prepare_form_payload( $post_id );
	}
}

if ( ! function_exists( 'apollo_event_build_access_payload' ) ) {
	function apollo_event_build_access_payload( int $post_id ): array {
		return \Apollo\Event\apollo_event_build_access_payload( $post_id );
	}
}

if ( ! function_exists( 'apollo_event_get_coauthor_ids' ) ) {
	function apollo_event_get_coauthor_ids( int $post_id ): array {
		return \Apollo\Event\apollo_event_get_coauthor_ids( $post_id );
	}
}

if ( ! function_exists( 'apollo_event_save_coauthors' ) ) {
	function apollo_event_save_coauthors( int $post_id, array $user_ids ): void {
		\Apollo\Event\apollo_event_save_coauthors( $post_id, $user_ids );
	}
}

if ( ! function_exists( 'apollo_event_get_all_users_for_coauthor_picker' ) ) {
	function apollo_event_get_all_users_for_coauthor_picker( int $exclude_user_id = 0 ): array {
		return \Apollo\Event\apollo_event_get_all_users_for_coauthor_picker( $exclude_user_id );
	}
}

if ( ! function_exists( 'apollo_event_get_banner' ) ) {
	function apollo_event_get_banner( int $post_id, string $size = 'large' ): string {
		return \Apollo\Event\apollo_event_get_banner( $post_id, $size );
	}
}

if ( ! function_exists( 'apollo_event_set_banner' ) ) {
	/**
	 * @param mixed $ref Attachment id, URL, or empty.
	 * @return int|\WP_Error
	 */
	function apollo_event_set_banner( int $post_id, $ref ) {
		return \Apollo\Event\apollo_event_set_banner( $post_id, $ref );
	}
}

if ( ! function_exists( 'apollo_event_parse_date' ) ) {
	function apollo_event_parse_date( string $date ): array {
		return \Apollo\Event\apollo_event_parse_date( $date );
	}
}

if ( ! function_exists( 'apollo_event_dj_thumb' ) ) {
	function apollo_event_dj_thumb( int $dj_id ): string {
		return \Apollo\Event\apollo_event_dj_thumb( $dj_id );
	}
}

if ( ! function_exists( 'apollo_event_asset_ver' ) ) {
	function apollo_event_asset_ver( string $relative ): string {
		return \Apollo\Event\apollo_event_asset_ver( $relative );
	}
}

if ( ! function_exists( 'apollo_event_image_url' ) ) {
	function apollo_event_image_url( $ref, string $size = 'large' ): string {
		return \Apollo\Event\apollo_event_image_url( $ref, $size );
	}
}

if ( ! function_exists( 'apollo_event_get_loc' ) ) {
	function apollo_event_get_loc( int $post_id ): ?array {
		return \Apollo\Event\apollo_event_get_loc( $post_id );
	}
}

if ( ! function_exists( 'apollo_event_get_djs' ) ) {
	function apollo_event_get_djs( int $post_id ): array {
		return \Apollo\Event\apollo_event_get_djs( $post_id );
	}
}

if ( ! function_exists( 'apollo_event_is_gone' ) ) {
	function apollo_event_is_gone( int $post_id ): bool {
		return \Apollo\Event\apollo_event_is_gone( $post_id );
	}
}

if ( ! function_exists( 'apollo_event_option' ) ) {
	function apollo_event_option( string $key, $default = null ) {
		return \Apollo\Event\apollo_event_option( $key, $default );
	}
}

if ( ! function_exists( 'apollo_event_get_active_style' ) ) {
	function apollo_event_get_active_style( string $shortcode_style = '' ): string {
		return \Apollo\Event\apollo_event_get_active_style( $shortcode_style );
	}
}

if ( ! function_exists( 'apollo_event_query' ) ) {
	function apollo_event_query( array $args = array() ): \WP_Query {
		return \Apollo\Event\apollo_event_query( $args );
	}
}

if ( ! function_exists( 'apollo_event_flush_cache' ) ) {
	function apollo_event_flush_cache( int $post_id ): void {
		\Apollo\Event\apollo_event_flush_cache( $post_id );
	}
}
