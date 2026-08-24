<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class NowLine extends BaseWidget {

	public function get_name(): string {
		return 'ae_now_line';
	}

	public function get_title(): string {
		return __( 'AE Now Line', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-divider';
	}

	protected function register_controls(): void {
		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'atomic/now-line';
	}

	protected function fetch_data( array $settings ): array {
		$now = new \DateTimeImmutable( 'now', wp_timezone() );

		return [
			'hour'   => (int) $now->format( 'G' ),
			'minute' => (int) $now->format( 'i' ),
		];
	}
}
