<?php

/**
 * Apollo Email — Global Helper Functions.
 *
 * These functions provide a clean API for sending emails
 * from any Apollo plugin without directly coupling to classes.
 *
 * @package Apollo\Email
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send an email immediately using a template.
 *
 * @param string $to       Recipient email.
 * @param string $subject  Email subject (supports {{merge_tags}}).
 * @param string $template Template slug (e.g., 'welcome', 'password-reset').
 * @param array  $data     Merge tag data.
 * @return bool True on success.
 */
function apollo_send_email( string $to, string $subject, string $template, array $data = array() ): bool {
	if ( ! class_exists( 'Apollo\\Email\\Plugin' ) ) {
		// Plugin not loaded — do NOT fall back to raw wp_mail (would bypass template
		// engine, from-address, V3 shell and logging). Log and abort.
		error_log( sprintf(
			'[Apollo Email] apollo_send_email() called but plugin not loaded — email NOT sent. To: %s | Template: %s',
			$to,
			$template
		) );
		return false;
	}

	$plugin = \Apollo\Email\Plugin::instance();
	$result = $plugin->sender()->sendTemplate( $to, $subject, $template, $data );

	return $result['success'] ?? false;
}

/**
 * Queue an email for later sending.
 *
 * @param string $to       Recipient email.
 * @param string $subject  Email subject.
 * @param string $template Template slug.
 * @param array  $data     Merge tag data.
 * @param int    $priority Priority (1=highest, 10=lowest).
 * @return int|false Queue ID or false on failure.
 */
function apollo_queue_email( string $to, string $subject, string $template, array $data = array(), int $priority = 5 ): int|false {
	if ( ! class_exists( 'Apollo\\Email\\Plugin' ) ) {
		// Plugin not loaded — do NOT fall back to immediate send (would bypass queue,
		// retry logic and the template pipeline). Log and abort.
		error_log( sprintf(
			'[Apollo Email] apollo_queue_email() called but plugin not loaded — email NOT queued. To: %s | Template: %s',
			$to,
			$template
		) );
		return false;
	}

	$plugin = \Apollo\Email\Plugin::instance();
	return $plugin->queue()->enqueue( $to, $subject, '', $template, $data, $priority );
}

/**
 * Process the email queue (used by cron).
 *
 * @param int $batch_size Number of emails to process.
 */
function apollo_process_email_queue( int $batch_size = 50 ): void {
	if ( ! class_exists( 'Apollo\\Email\\Plugin' ) ) {
		return;
	}

	\Apollo\Email\Plugin::instance()->queue()->processNext();
}

/**
 * Get an email template by slug.
 *
 * @param string $template_slug Template slug.
 * @return array|null Template data or null.
 */
function apollo_get_email_template( string $template_slug ): ?array {
	if ( ! class_exists( 'Apollo\\Email\\Plugin' ) ) {
		return null;
	}

	return \Apollo\Email\Plugin::instance()->templates()->getTemplate( $template_slug );
}

/**
 * Render an email template with data.
 *
 * @param string $template Template slug.
 * @param array  $data     Merge tag data.
 * @return string Rendered HTML.
 */
function apollo_render_email( string $template, array $data = array() ): string {
	if ( ! class_exists( 'Apollo\\Email\\Plugin' ) ) {
		return '';
	}

	return \Apollo\Email\Plugin::instance()->templates()->render( $template, $data );
}

/**
 * Output a safe href for email templates.
 *
 * During CPT seeding, preserves {{merge_tag}} literals instead of running esc_url()
 * on placeholders (esc_url turns "{{reset_url}}" into "http://reset_url/").
 *
 * @param string $url       Resolved URL at send time.
 * @param string $merge_tag Merge tag name without braces (e.g. reset_url).
 * @return string
 */
function apollo_email_template_href( $url, string $merge_tag = '' ): string {
	if ( ! empty( $GLOBALS['apollo_email_seeding'] ) && '' !== $merge_tag ) {
		return '{{' . $merge_tag . '}}';
	}

	$url = (string) ( $url ?? '' );
	if ( '' === $url || '#' === $url ) {
		return '#';
	}

	return esc_url( $url );
}

/**
 * Plain-text URL shown in the fallback link box (merge tag during CPT seed).
 *
 * @param mixed  $url       Resolved URL at send time.
 * @param string $merge_tag Merge tag name without braces.
 * @return string
 */
function apollo_email_template_link_display( $url, string $merge_tag = '' ): string {
	if ( ! empty( $GLOBALS['apollo_email_seeding'] ) && '' !== $merge_tag ) {
		return '{{' . $merge_tag . '}}';
	}

	return (string) ( $url ?? '' );
}

/**
 * Canonical Apollo logo URL for transactional emails (PNG — broad client support).
 */
function apollo_email_default_logo_url(): string {
	return 'https://assets.apollo.rio.br/img/logo/logo-apollo.png';
}

/**
 * Resolve brand logo for email templates; maps legacy SVG/old assets to the PNG logo.
 *
 * @param string $override Optional brand_logo from settings or merge data.
 */
function apollo_email_brand_logo_url( string $override = '' ): string {
	$default  = apollo_email_default_logo_url();
	$override = trim( $override );

	if ( '' === $override ) {
		return $default;
	}

	$legacy_fragments = array(
		'apollo-s.svg',
		'apollo-s-email.png',
		'apollo-email-logo.png',
	);

	foreach ( $legacy_fragments as $fragment ) {
		if ( str_contains( $override, $fragment ) ) {
			return $default;
		}
	}

	return $override;
}
