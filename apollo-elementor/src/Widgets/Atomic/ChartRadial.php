<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChartRadial extends BaseWidget {

	private const RADIUS        = 78;
	private const CIRCUMFERENCE = 490.088; // 2 * π * 78

	public function get_name(): string {
		return 'ae_chart_radial';
	}

	public function get_title(): string {
		return __( 'AE Chart Radial', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-counter-circle';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_radial', [
			'label' => __( 'Radial Chart', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$repeater = new Repeater();

		$repeater->add_control( 'label', [
			'label'   => __( 'Label', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Segment', 'apollo-elementor' ),
		] );

		$repeater->add_control( 'value', [
			'label'   => __( 'Value', 'apollo-elementor' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 25,
			'min'     => 0,
		] );

		$repeater->add_control( 'color', [
			'label'   => __( 'Colour', 'apollo-elementor' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#FF5C00',
		] );

		$this->add_control( 'segments', [
			'label'       => __( 'Segments', 'apollo-elementor' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => [
				[ 'label' => 'A', 'value' => 40, 'color' => '#FF5C00' ],
				[ 'label' => 'B', 'value' => 30, 'color' => '#00D084' ],
				[ 'label' => 'C', 'value' => 30, 'color' => '#0693E3' ],
			],
			'title_field' => '{{{ label }}} — {{{ value }}}',
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'atomic/chart-radial';
	}

	protected function cache_key_fields(): array {
		return [ 'segments' ];
	}

	protected function fetch_data( array $settings ): array {
		$raw_segments = $settings['segments'] ?? [];
		$total        = 0.0;

		foreach ( $raw_segments as $seg ) {
			$total += (float) ( $seg['value'] ?? 0 );
		}

		$offset   = 0.0;
		$segments = [];

		foreach ( $raw_segments as $seg ) {
			$value = (float) ( $seg['value'] ?? 0 );
			$pct   = $total > 0 ? $value / $total : 0;
			$dash  = $pct * self::CIRCUMFERENCE;
			$gap   = self::CIRCUMFERENCE - $dash;

			$segments[] = [
				'label'            => $seg['label'] ?? '',
				'value'            => $value,
				'color'            => $seg['color'] ?? '#FF5C00',
				'pct'              => round( $pct * 100, 1 ),
				'stroke_dasharray' => round( $dash, 2 ) . ' ' . round( $gap, 2 ),
				'stroke_offset'    => round( -$offset, 2 ),
			];

			$offset += $dash;
		}

		return [
			'segments'      => $segments,
			'total'         => $total,
			'circumference' => self::CIRCUMFERENCE,
			'radius'        => self::RADIUS,
		];
	}
}
