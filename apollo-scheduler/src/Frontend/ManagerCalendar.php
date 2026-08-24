<?php
/**
 * Manager / agent calendar views.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ManagerCalendar {

	public function __construct() {
		add_shortcode( 'apollo_scheduler_manager', array( $this, 'shortcode' ) );
	}

	/**
	 * @param array<string, mixed>|string $atts
	 */
	public function shortcode( $atts ): string {
		$atts = shortcode_atts( array( 'nucleo_id' => 0 ), (array) $atts, 'apollo_scheduler_manager' );
		ob_start();
		self::render_markup( (int) $atts['nucleo_id'], '' );
		return (string) ob_get_clean();
	}

	public static function render_page( string $nucleo_slug ): void {
		$nucleo_id = 0;
		if ( $nucleo_slug && taxonomy_exists( 'nucleo' ) ) {
			$term = get_term_by( 'slug', $nucleo_slug, 'nucleo' );
			if ( $term instanceof \WP_Term ) {
				$nucleo_id = (int) $term->term_id;
			}
		}

		self::render_markup( $nucleo_id, $nucleo_slug );
	}

	public static function render_agent_page(): void {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$config = wp_json_encode(
			Assets::build_config(
				array(
					'mode'    => 'agent',
					'agentId' => get_current_user_id(),
				)
			)
		);

		include APOLLO_SCHEDULER_DIR . 'templates/agent-availability.php';
	}

	private static function render_markup( int $nucleo_id, string $nucleo_slug ): void {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$config = wp_json_encode(
			Assets::build_config(
				array(
					'mode'       => 'manager',
					'nucleoId'   => $nucleo_id,
					'nucleoSlug' => $nucleo_slug,
				)
			)
		);

		include APOLLO_SCHEDULER_DIR . 'templates/manager-calendar.php';
	}
}
