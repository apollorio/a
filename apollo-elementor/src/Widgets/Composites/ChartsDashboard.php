<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Composites;

use Apollo\ElementorAE\Data\ChartsRepository;
use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ChartsDashboard extends BaseWidget {

	public function get_name(): string {
		return 'ae_charts_dashboard';
	}

	public function get_title(): string {
		return __( 'AE Charts Dashboard', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-skill-bar';
	}

	public function get_style_depends(): array {
		return [ 'apollo-elementor-base', 'apollo-elementor-charts-dashboard' ];
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_charts', [
			'label' => __( 'Charts Dashboard', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'event_id', [
			'label'       => __( 'Event ID', 'apollo-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'min'         => 0,
			'description' => __( 'Specific event for stats. Leave 0 for global.', 'apollo-elementor' ),
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'composites/charts-dashboard';
	}

	protected function cache_key_fields(): array {
		return [ 'event_id' ];
	}

	protected function fetch_data( array $settings ): array {
		$event_id = absint( $settings['event_id'] ?? 0 );

		return [
			'event_stats' => $event_id > 0 ? ChartsRepository::event_stats( $event_id ) : [],
			'global_kpis' => ChartsRepository::global_kpis(),
		];
	}
}
