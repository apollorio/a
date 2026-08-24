<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Marquee extends BaseWidget {

	public function get_name(): string {
		return 'ae_marquee';
	}

	public function get_title(): string {
		return __( 'AE Marquee', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-carousel';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_marquee', [
			'label' => __( 'Marquee', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'items', [
			'label'       => __( 'Items (comma-separated)', 'apollo-elementor' ),
			'type'        => Controls_Manager::TEXTAREA,
			'default'     => 'Techno, House, Minimal, Trance, Drum & Bass',
			'description' => __( 'Comma-separated list of genres or labels.', 'apollo-elementor' ),
		] );

		$this->add_control( 'speed', [
			'label'   => __( 'Speed (px/s)', 'apollo-elementor' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 38,
			'min'     => 1,
			'max'     => 200,
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'atomic/marquee';
	}

	protected function cache_key_fields(): array {
		return [ 'items', 'speed' ];
	}

	protected function fetch_data( array $settings ): array {
		$raw   = $settings['items'] ?? '';
		$items = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

		return [
			'items' => array_values( $items ),
			'speed' => absint( $settings['speed'] ?? 38 ),
		];
	}
}
