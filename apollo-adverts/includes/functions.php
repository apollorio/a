<?php
/**
 * Helper Functions
 *
 * Utility functions for Apollo Adverts.
 * Adapted from WPAdverts functions.php (config, price, request helpers).
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// SETTINGS — adapted from adverts_config()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get plugin settings
 * Adapted from adverts_config()
 *
 * @param string|null $key Dot-notation key (e.g., 'general.posts_per_page')
 * @param mixed       $default Default value
 * @return mixed
 */
function apollo_adverts_config( ?string $key = null, $default = null ) {
	static $settings = null;

	if ( null === $settings ) {
		$defaults = array(
			'posts_per_page'  => APOLLO_ADVERTS_POSTS_PER_PAGE,
			'expiration_days' => APOLLO_ADVERTS_DEFAULT_EXPIRATION,
			'max_images'      => APOLLO_ADVERTS_MAX_IMAGES,
			/*
			 * 'moderation', NOT 'moderate_new' (corrected 2026-08-17).
			 *
			 * This defaults array declared `moderate_new => false`, a key NOTHING
			 * reads. Every actual consumer asks for `moderation` and compares it
			 * to the string 'manual':
			 *
			 *   src/API/ClassifiedsController.php:181
			 *   includes/shortcodes/apollo-classified-form.php:74, :92
			 *   templates/admin/settings.php:72-73
			 *
			 * and both writers store it as 'auto'|'manual' — src/Activation.php:44
			 * and templates/admin/settings.php:23.
			 *
			 * So on any install where the option row was missing a value, the
			 * read was an undefined-key warning that evaluated to null, null is
			 * never 'manual', and a listing went straight to `publish`. Manual
			 * moderation could not be switched on. Aligning the default with the
			 * writers and the readers is what makes the setting real.
			 */
			'moderation'      => 'auto', // auto|manual
			'allow_guests'    => false,
			'show_phone'      => true,
			'show_whatsapp'   => true,
			'notify_admin'    => true,
			'notify_expiring' => true,
			'currency_code'   => 'BRL',
			'currency_sign'   => 'R$',
		);
		$saved    = get_option( 'apollo_adverts_settings', array() );
		$settings = wp_parse_args( $saved, $defaults );
	}

	if ( null === $key ) {
		return $settings;
	}

	return $settings[ $key ] ?? $default;
}

/**
 * Get plugin directory path
 *
 * @return string
 */
function apollo_adverts_get_directory_path(): string {
	return APOLLO_ADVERTS_DIR;
}

// ═══════════════════════════════════════════════════════════════════════════
// REFERENCE VALUE FORMATTING — adapted from adverts_price()
// Apollo does NOT process payments. This is informational only.
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Format reference value with currency sign
 * Informational only — Apollo does not handle transactions
 * Adapted from adverts_price()
 *
 * @param float|string $price
 * @return string
 */
function apollo_adverts_format_price( $price ): string {
	if ( empty( $price ) && $price !== 0 && $price !== '0' ) {
		return '';
	}

	$c = APOLLO_ADVERTS_CURRENCY;

	$number = number_format(
		(float) $price,
		$c['decimals'],
		$c['char_decimal'],
		$c['char_thousand']
	);

	$sign = ! empty( $c['sign'] ) ? $c['sign'] : $c['code'];

	if ( $c['sign_type'] === 'p' ) {
		$formatted = $sign . ' ' . $number;
	} else {
		$formatted = $number . ' ' . $sign;
	}

	return apply_filters( 'apollo/classifieds/price_format', $formatted, $price );
}

/**
 * Get the formatted reference value for a classified
 * Informational only — Apollo is a bridge, not a marketplace
 *
 * @param int $post_id
 * @return string
 */
function apollo_adverts_get_the_price( int $post_id ): string {
	$price = get_post_meta( $post_id, '_classified_price', true );
	return apollo_adverts_format_price( $price );
}

// ═══════════════════════════════════════════════════════════════════════════
// REQUEST HELPERS — adapted from adverts_request()
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get value from $_POST or $_GET
 * Adapted from adverts_request()
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function apollo_adverts_request( string $key, $default = null ) {
	if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore
		return sanitize_text_field( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore
	}
	if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore
		return sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore
	}
	return $default;
}

// ═══════════════════════════════════════════════════════════════════════════
// CLASSIFIED HELPERS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Get the expiration timestamp for a classified
 *
 * @param int $post_id
 * @return int|false Timestamp or false
 */
function apollo_adverts_get_expiration( int $post_id ) {
	$date = get_post_meta( $post_id, '_classified_expires_at', true );
	if ( empty( $date ) ) {
		return false;
	}
	return strtotime( $date );
}

/**
 * Set expiration date for a classified
 *
 * @param int      $post_id
 * @param int|null $days Number of days from now (null = use default)
 * @return void
 */
function apollo_adverts_set_expiration( int $post_id, ?int $days = null ): void {
	if ( null === $days ) {
		$days = (int) apply_filters( 'apollo/classifieds/expiration_days', APOLLO_ADVERTS_DEFAULT_EXPIRATION );
	}
	$date = date( 'Y-m-d', strtotime( "+{$days} days" ) );
	update_post_meta( $post_id, '_classified_expires_at', $date );
}

/**
 * Check if a classified is expired
 *
 * @param int $post_id
 * @return bool
 */
function apollo_adverts_is_expired( int $post_id ): bool {
	$exp = apollo_adverts_get_expiration( $post_id );
	if ( ! $exp ) {
		return false;
	}
	return $exp < current_time( 'timestamp' );
}

/**
 * Check if a classified is featured
 *
 * @param int $post_id
 * @return bool
 */
function apollo_adverts_is_featured( int $post_id ): bool {
	return (bool) get_post_meta( $post_id, '_classified_featured', true );
}

/**
 * Get the main image URL for a classified
 * Adapted from adverts_get_main_image()
 *
 * @param int    $post_id
 * @param string $size
 * @return string
 */
function apollo_adverts_get_main_image( int $post_id, string $size = 'classified-list' ): string {
	$thumb_id = (int) get_post_thumbnail_id( $post_id );
	if ( $thumb_id ) {
		$src = wp_get_attachment_image_src( $thumb_id, $size );
		if ( $src ) {
			return $src[0];
		}
	}

	// Fallback: first attached image
	$attachments = get_children(
		array(
			'post_parent'    => $post_id,
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'posts_per_page' => 1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	if ( $attachments ) {
		$att = reset( $attachments );
		$src = wp_get_attachment_image_src( $att->ID, $size );
		if ( $src ) {
			return $src[0];
		}
	}

	return APOLLO_ADVERTS_URL . 'assets/images/placeholder.png';
}

/**
 * Increment view count for a classified
 *
 * @param int $post_id
 * @return void
 */
function apollo_adverts_increment_views( int $post_id ): void {
	$views = (int) get_post_meta( $post_id, '_classified_views', true );
	update_post_meta( $post_id, '_classified_views', $views + 1 );
}

/**
 * Get classified condition label
 *
 * @param int $post_id
 * @return string
 */
function apollo_adverts_get_condition_label( int $post_id ): string {
	$condition  = get_post_meta( $post_id, '_classified_condition', true );
	$conditions = APOLLO_ADVERTS_CONDITIONS;
	return $conditions[ $condition ] ?? '';
}

/**
 * Get classified intent label
 *
 * @param int $post_id
 * @return string
 */
function apollo_adverts_get_intent_label( int $post_id ): string {
	$terms = get_the_terms( $post_id, APOLLO_TAX_CLASSIFIED_INTENT );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	return $terms[0]->name ?? '';
}

/**
 * Get user's classifieds count
 *
 * @param int $user_id
 * @return int
 */
function apollo_adverts_user_ad_count( int $user_id ): int {
	return (int) count_user_posts( $user_id, APOLLO_CPT_CLASSIFIED, true );
}

// ═══════════════════════════════════════════════════════════════════════════
// TEMPLATE LOADING — adapted from adverts_template_load filter
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Load a template file with theme override support
 * Adapted from WPAdverts template override pattern
 *
 * @param string $template Template name (e.g., 'list.php')
 * @return string Full path to template file
 */
function apollo_adverts_get_template( string $template ): string {
	// Check theme override: theme/apollo-adverts/{template}
	$theme_file = get_stylesheet_directory() . '/apollo-adverts/' . $template;
	if ( file_exists( $theme_file ) ) {
		return $theme_file;
	}

	// Plugin template
	$plugin_file = APOLLO_ADVERTS_DIR . 'templates/' . $template;
	if ( file_exists( $plugin_file ) ) {
		return $plugin_file;
	}

	return '';
}

/**
 * Include a template file
 *
 * @param string $template Template name
 * @param array  $args Variables to extract into template scope
 * @return void
 */
function apollo_adverts_load_template( string $template, array $args = array() ): void {
	$file = apollo_adverts_get_template( $template );
	if ( ! $file ) {
		return;
	}
	if ( ! empty( $args ) ) {
		extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
	}
	include $file;
}

// ═══════════════════════════════════════════════════════════════════════════
// LOGGING
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Log a classified action
 *
 * @param int    $post_id
 * @param string $action
 * @param string $details
 * @return void
 */
function apollo_adverts_log( int $post_id, string $action, string $details = '' ): void {
	if ( function_exists( 'apollo_log_audit' ) ) {
		apollo_log_audit(
			$action,
			'classified',
			$post_id,
			array(
				'details' => $details,
				'user_id' => get_current_user_id(),
			)
		);
	}
}
