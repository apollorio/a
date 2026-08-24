<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChartLine extends BaseWidget {

	private const SVG_WIDTH  = 320;
	private const SVG_HEIGHT = 160;
	private const PADDING    = 16;

	public function get_name(): string {
		return 'ae_chart_line';
	}

	public function get_title(): string {
		return __( 'AE Chart Line', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-line-chart';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_line', [
			'label' => __( 'Line Chart', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'title', [
			'label'   => __( 'Title', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Revenue', 'apollo-elementor' ),
		] );

		$this->add_control( 'headline_number', [
			'label'   => __( 'Headline number', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '$12,400',
		] );

		$this->add_control( 'delta', [
			'label'   => __( 'Delta', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '+8.2%',
		] );

		$this->add_control( 'points', [
			'label'       => __( 'Points (y values, comma-separated)', 'apollo-elementor' ),
			'type'        => Controls_Manager::TEXTAREA,
			'default'     => '20,35,28,50,45,60,55,70,65,80',
			'description' => __( 'Comma-separated numeric y values.', 'apollo-elementor' ),
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'atomic/chart-line';
	}

	protected function cache_key_fields(): array {
		return [ 'title', 'headline_number', 'delta', 'points' ];
	}

	protected function fetch_data( array $settings ): array {
		$raw    = $settings['points'] ?? '';
		$values = array_map( 'floatval', array_filter( array_map( 'trim', explode( ',', $raw ) ), 'strlen' ) );
		$count  = count( $values );

		if ( 0 === $count ) {
			return [
				'title'           => $settings['title'] ?? '',
				'headline_number' => $settings['headline_number'] ?? '',
				'delta'           => $settings['delta'] ?? '',
				'path'            => '',
				'area_path'       => '',
				'points'          => [],
			];
		}

		$max_val = max( $values );
		$min_val = min( $values );
		$range   = $max_val - $min_val;
		if ( $range <= 0 ) {
			$range = 1;
		}

		$w       = self::SVG_WIDTH - ( self::PADDING * 2 );
		$h       = self::SVG_HEIGHT - ( self::PADDING * 2 );
		$step_x  = $count > 1 ? $w / ( $count - 1 ) : 0;

		$coords = [];
		foreach ( $values as $i => $v ) {
			$x = self::PADDING + ( $i * $step_x );
			$y = self::PADDING + $h - ( ( $v - $min_val ) / $range * $h );
			$coords[] = [ round( $x, 2 ), round( $y, 2 ) ];
		}

		$path_parts = [];
		foreach ( $coords as $i => $c ) {
			$path_parts[] = ( 0 === $i ? 'M' : 'L' ) . $c[0] . ',' . $c[1];
		}
		$line_path = implode( ' ', $path_parts );

		$bottom = self::SVG_HEIGHT - self::PADDING;
		$area_path = $line_path
			. ' L' . end( $coords )[0] . ',' . $bottom
			. ' L' . $coords[0][0] . ',' . $bottom
			. ' Z';

		return [
			'title'           => $settings['title'] ?? '',
			'headline_number' => $settings['headline_number'] ?? '',
			'delta'           => $settings['delta'] ?? '',
			'path'            => $line_path,
			'area_path'       => $area_path,
			'points'          => $coords,
			'svg_width'       => self::SVG_WIDTH,
			'svg_height'      => self::SVG_HEIGHT,
		];
	}
}
