<?php
/**
 * Apollo Safe Sanitization Helpers
 *
 * REVIEW: Provide WP 4.9-compatible wrapper functions for sanitization and escaping.
 * Severity: PATCH (defensive helpers for ecosystem-wide use)
 *
 * Usage: require_once this file early (e.g. in apollo-core bootstrap) or merge into
 * apollo-core/includes/functions.php.
 *
 * @package Apollo\Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'apollo_safe_text' ) ) {
    /**
     * Sanitize a string for safe text storage.
     *
     * @param mixed $val Input value.
     * @return string Sanitized string.
     */
    function apollo_safe_text( $val ) {
        return sanitize_text_field( (string) $val );
    }
}

if ( ! function_exists( 'apollo_safe_html' ) ) {
    /**
     * Sanitize HTML content (allow safe tags via wp_kses_post).
     *
     * @param mixed $html Input HTML.
     * @return string Sanitized HTML.
     */
    function apollo_safe_html( $html ) {
        return wp_kses_post( (string) $html );
    }
}

if ( ! function_exists( 'apollo_safe_attr' ) ) {
    /**
     * Escape a value for use inside an HTML attribute.
     *
     * @param mixed $val Input value.
     * @return string Escaped attribute string.
     */
    function apollo_safe_attr( $val ) {
        return esc_attr( (string) $val );
    }
}

if ( ! function_exists( 'apollo_safe_url' ) ) {
    /**
     * Escape a URL for safe output in HTML.
     * Use esc_url_raw() instead when storing in the database.
     *
     * @param mixed  $url      Input URL.
     * @param string $context  'display' for output (default) or 'db' for storage.
     * @return string Escaped URL.
     */
    function apollo_safe_url( $url, $context = 'display' ) {
        $url = (string) $url;
        return 'db' === $context ? esc_url_raw( $url ) : esc_url( $url );
    }
}

if ( ! function_exists( 'apollo_prepare_sql' ) ) {
    /**
     * Wrapper around $wpdb->prepare() with safety guard.
     *
     * @param string $sql  SQL query with placeholders (%s, %d, %f).
     * @param mixed  ...$args Placeholder values.
     * @return string|null Prepared SQL or null on failure.
     */
    function apollo_prepare_sql( $sql, ...$args ) {
        global $wpdb;
        if ( empty( $args ) ) {
            return $sql;
        }
        return $wpdb->prepare( $sql, ...$args );
    }
}

/**
 * Inline-emphasis sanitizer — one <em>, nothing else.
 *
 * Registered as the sanitize_callback for `_dj_statement` (and intended for
 * `_local_description`). Both fields render a pinned scrub sentence in which a
 * single word carries the accent colour. The alternative — a second meta key
 * holding "which word is gold" — desynchronises the instant somebody edits the
 * sentence, so the emphasis lives inside the field it belongs to.
 *
 * Deliberately narrower than wp_kses_post(): these strings are display copy on
 * a public page, not post content. <em> is the entire allowance.
 *
 * @since 2026-08-11
 * @see   _inventory/registry/21-mockup-field-contract.json
 *
 * @param mixed $value Raw meta value.
 * @return string Sanitized value.
 */
function apollo_core_kses_inline_em( $value ): string {
	if ( ! is_string( $value ) ) {
		return '';
	}

	return trim(
		wp_kses(
			$value,
			array(
				'em' => array( 'class' => array() ),
			)
		)
	);
}
