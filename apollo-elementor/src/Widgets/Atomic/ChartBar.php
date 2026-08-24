<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChartBar extends BaseWidget {

	public function get_name(): string {
		return 'ae_chart_bar';
	}

	public function get_title(): string {
		return __( 'AE Chart Bar', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-skill-bar';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_bar', [
			'label' => __( 'Bar Chart', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$repeater = new Repeater();

		$repeater->add_control( 'label', [
			'label'   => __( 'Label', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Bar', 'apollo-elementor' ),
		] );

		$repeater->add_control( 'value', [
			'label'   => __( 'Value', 'apollo-elementor' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 50,
			'min'     => 0,
		] );

		$repeater->add_control( 'color', [
			'label'   => __( 'Colour', 'apollo-elementor' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#FF5C00',
		] );

		$this->add_control( 'bars', [
			'label'       => __( 'Bars', 'apollo-elementor' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => [
				[ 'label' => 'Mon', 'value' => 60, 'color' => '#FF5C00' ],
				[ 'label' => 'Tue', 'value' => 80, 'color' => '#FF5C00' ],
				[ 'label' => 'Wed', 'value' => 45, 'color' => '#FF5C00' ],
				[ 'label' => 'Thu', 'value' => 90, 'color' => '#FF5C00' ],
				[ 'label' => 'Fri', 'value' => 70, 'color' => '#FF5C00' ],
			],
			'title_field' => '{{{ label }}} — {{{ value }}}',
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'atomic/chart-bar';
	}

	protected function cache_key_fields(): array {
		return [ 'bars' ];
	}

	protected function fetch_data( array $settings ): array {
		$raw  = $settings['bars'] ?? [];
		$max  = 0.0;

		foreach ( $raw as $bar ) {
			$v = (float) ( $bar['value'] ?? 0 );
			if ( $v > $max ) {
				$max = $v;
			}
		}

		$bars = [];
		foreach ( $raw as $bar ) {
			$value = (float) ( $bar['value'] ?? 0 );
			$bars[] = [
				'label' => $bar['label'] ?? '',
				'value' => $value,
				'color' => $bar['color'] ?? '#FF5C00',
				'pct'   => $max > 0 ? round( ( $value / $max ) * 100, 1 ) : 0,
			];
		}

		return [
			'bars' => $bars,
			'max'  => $max,
		];
	}
}
