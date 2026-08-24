<?php

/**
 * CSS Style Inliner for email templates.
 *
 * Converts <style> blocks into inline styles for maximum
 * email client compatibility (Gmail, Outlook, Apple Mail, etc.).
 *
 * @package Apollo\Email\Template
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email\Template;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class StyleInliner {

	/**
	 * Inline CSS from <style> blocks into HTML elements.
	 *
	 * @param string $html HTML with <style> blocks.
	 * @return string HTML with inline styles.
	 */
	public static function inline( string $html ): string {
		if ( empty( $html ) ) {
			return $html;
		}

		// Extract styles from <style> blocks
		$styles = array();
		preg_match_all( '/<style[^>]*>(.*?)<\/style>/is', $html, $matches );

		foreach ( $matches[1] as $css ) {
			$rules  = self::parseCssRules( $css );
			$styles = array_merge( $styles, $rules );
		}

		if ( empty( $styles ) ) {
			return $html;
		}

		// Apply inline styles
		foreach ( $styles as $selector => $properties ) {
			$html = self::applyInlineStyle( $html, $selector, $properties );
		}

		return $html;
	}

	/**
	 * Parse CSS rules from a stylesheet string.
	 *
	 * @param string $css Raw CSS.
	 * @return array<string, string> Selector => properties.
	 */
	private static function parseCssRules( string $css ): array {
		$rules = array();

		// Remove comments
		$css = preg_replace( '/\/\*.*?\*\//s', '', $css );

		// Remove media queries (keep content simple for email)
		$css = preg_replace( '/@media[^{]*\{([^{}]*(\{[^{}]*\})*[^{}]*)\}/s', '', $css );

		// Parse rules
		preg_match_all( '/([^{]+)\{([^}]+)\}/s', $css, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			$selector   = trim( $match[1] );
			$properties = trim( $match[2] );

			// Skip pseudo-classes/elements
			if ( str_contains( $selector, ':hover' ) || str_contains( $selector, ':before' ) || str_contains( $selector, ':after' ) ) {
				continue;
			}

			// Normalize properties
			$properties         = preg_replace( '/\s+/', ' ', $properties );
			$rules[ $selector ] = $properties;
		}

		return $rules;
	}

	/**
	 * Apply inline style to matching elements.
	 *
	 * @param string $html       Input HTML.
	 * @param string $selector   CSS selector (simple: tag, .class, #id).
	 * @param string $properties CSS properties string.
	 * @return string Modified HTML.
	 */
	private static function applyInlineStyle( string $html, string $selector, string $properties ): string {
		// Only support simple selectors for email
		$selector = trim( $selector );

		// Class selector
		if ( str_starts_with( $selector, '.' ) ) {
			$class = substr( $selector, 1 );
			$html  = preg_replace_callback(
				'/(<\w+)([^>]*class=["\'][^"\']*\b' . preg_quote( $class, '/' ) . '\b[^"\']*["\'][^>]*)(>)/i',
				function ( $m ) use ( $properties ) {
					return self::mergeStyle( $m[1], $m[2], $m[3], $properties );
				},
				$html
			) ?? $html;
		}
		// ID selector
		elseif ( str_starts_with( $selector, '#' ) ) {
			$id   = substr( $selector, 1 );
			$html = preg_replace_callback(
				'/(<\w+)([^>]*id=["\']' . preg_quote( $id, '/' ) . '["\'][^>]*)(>)/i',
				function ( $m ) use ( $properties ) {
					return self::mergeStyle( $m[1], $m[2], $m[3], $properties );
				},
				$html
			) ?? $html;
		}
		// Tag selector (only common email-safe tags)
		elseif ( preg_match( '/^(body|table|tr|td|th|p|h[1-6]|a|img|span|div|ul|ol|li)$/i', $selector ) ) {
			$html = preg_replace_callback(
				'/(<' . preg_quote( $selector, '/' ) . ')(\s[^>]*|)(>)/i',
				function ( $m ) use ( $properties ) {
					return self::mergeStyle( $m[1], $m[2], $m[3], $properties );
				},
				$html
			) ?? $html;
		}

		return $html;
	}

	/**
	 * Merge inline style into an element's existing style attribute.
	 */
	private static function mergeStyle( string $tagOpen, string $attrs, string $tagClose, string $properties ): string {
		if ( preg_match( '/style=["\']([^"\']*)["\']/', $attrs, $m ) ) {
			$existing = rtrim( trim( $m[1] ), ';' );
			$new      = $existing . '; ' . $properties;
			$attrs    = str_replace( $m[0], 'style="' . $new . '"', $attrs );
		} else {
			$attrs .= ' style="' . $properties . '"';
		}

		return $tagOpen . $attrs . $tagClose;
	}
}
