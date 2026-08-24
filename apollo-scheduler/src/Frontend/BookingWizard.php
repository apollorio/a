<?php
/**
 * Luxury booking wizard frontend.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BookingWizard {

	public function __construct() {
		add_shortcode( 'apollo_scheduler_book', array( $this, 'shortcode' ) );
	}

	public function shortcode( $atts ): string {
		ob_start();
		self::render_markup( (int) ( $atts['nucleo_id'] ?? 0 ) );
		return (string) ob_get_clean();
	}

	public static function render_page(): void {
		self::render_markup( 0 );
	}

	private static function render_markup( int $nucleo_id ): void {
		$config = wp_json_encode( Assets::build_config( array( 'nucleoId' => $nucleo_id ) ) );

		include APOLLO_SCHEDULER_DIR . 'templates/booking-wizard.php';
	}
}
