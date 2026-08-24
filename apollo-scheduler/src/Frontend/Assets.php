<?php
/**
 * Frontend asset registration — Apollo CDN (GSAP, Lenis, RemixIcon).
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Assets {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets(): void {
		$page = get_query_var( 'apollo_scheduler_page', '' );
		if ( '' === $page && ! is_singular( array( APOLLO_SCHEDULER_CPT_APPOINTMENT, APOLLO_SCHEDULER_CPT_SERVICE ) ) ) {
			return;
		}

		$cdn = defined( 'APOLLO_CDN_URL' ) ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br';

		wp_enqueue_style( 'remixicon', $cdn . '/remixicon/remixicon.css', array(), APOLLO_SCHEDULER_VERSION );
		wp_enqueue_style(
			'apollo-scheduler',
			APOLLO_SCHEDULER_URL . 'assets/css/scheduler.css',
			array( 'remixicon' ),
			APOLLO_SCHEDULER_VERSION
		);

		wp_enqueue_script( 'gsap', $cdn . '/gsap/gsap.min.js', array(), '3.12.5', true );
		wp_enqueue_script( 'gsap-flip', $cdn . '/gsap/Flip.min.js', array( 'gsap' ), '3.12.5', true );
		wp_enqueue_script( 'lenis', $cdn . '/lenis/lenis.min.js', array(), '1.1.18', true );

		wp_enqueue_script(
			'apollo-scheduler-wizard',
			APOLLO_SCHEDULER_URL . 'assets/js/booking-wizard.js',
			array( 'gsap', 'gsap-flip', 'lenis' ),
			APOLLO_SCHEDULER_VERSION,
			true
		);

		wp_enqueue_script(
			'apollo-scheduler-calendar',
			APOLLO_SCHEDULER_URL . 'assets/js/manager-calendar.js',
			array( 'gsap', 'lenis' ),
			APOLLO_SCHEDULER_VERSION,
			true
		);

		wp_enqueue_script(
			'apollo-scheduler-picker',
			APOLLO_SCHEDULER_URL . 'assets/js/multi-resource-picker.js',
			array( 'gsap', 'apollo-scheduler-wizard' ),
			APOLLO_SCHEDULER_VERSION,
			true
		);
	}

	/**
	 * @param array<string, mixed> $extra
	 * @return array<string, mixed>
	 */
	public static function build_config( array $extra = array() ): array {
		return array_merge(
			array(
				'restUrl'   => esc_url_raw( rest_url( APOLLO_SCHEDULER_REST_NAMESPACE . '/' . APOLLO_SCHEDULER_REST_BASE ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
				'userId'    => get_current_user_id(),
				'i18n'      => array(
					'confirm'   => __( 'Confirm booking', 'apollo-scheduler' ),
					'loading'   => __( 'Loading availability…', 'apollo-scheduler' ),
					'unavailable' => __( 'Unavailable', 'apollo-scheduler' ),
				),
			),
			$extra
		);
	}
}
