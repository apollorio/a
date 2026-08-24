<?php
/**
 * Apollo Calendar — /agenda Blank Canvas Page
 *
 * Registers the /agenda rewrite rule and intercepts the request to
 * render the calendar template via BlankCanvasTrait.
 *
 * @package Apollo\Calendar\Frontend
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar\Frontend;

use Apollo\Core\Traits\BlankCanvasTrait;
use Apollo\Calendar\Activation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CalendarPage {

	use BlankCanvasTrait;

	public function init(): void {
		// Register rewrite rule every request (safe — no-op if already registered).
		add_action( 'init', array( Activation::class, 'register_rewrite_rules' ) );

		// Intercept matching request.
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
	}

	public function maybe_render(): void {
		if ( ! get_query_var( 'apollo_calendar_page' ) ) {
			return;
		}

		// Guest → redirect to login.
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( home_url( '/agenda' ) ) );
			exit;
		}

		$user     = wp_get_current_user();
		$user_id  = $user->ID;

		// Build JS config (nonce only for logged-in — never guest).
		$js_config = wp_json_encode( array(
			'apiRoot'   => esc_url_raw( rest_url( 'apollo/v1' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'userId'    => $user_id,
			'userName'  => esc_html( $user->display_name ),
			'timezone'  => esc_html( get_user_meta( $user_id, '_apollo_calendar_timezone', true ) ?: 'America/Sao_Paulo' ),
			'cdnCore'   => esc_url( $this->get_apollo_cdn_core_js() ),
		) );

		$template = APOLLO_CALENDAR_DIR . 'templates/agenda.php';

		// Blank Canvas APOLLO+ — agenda is an app surface, carries shell chrome.
		$this->render_blank_canvas_plus( $template, array(
			'user'      => $user,
			'js_config' => $js_config,
		) );
	}
}
