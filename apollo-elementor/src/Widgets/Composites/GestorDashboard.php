<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Composites;

use Apollo\ElementorAE\Data\ChartsRepository;
use Apollo\ElementorAE\Data\EventRepository;
use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class GestorDashboard extends BaseWidget {

	public function get_name(): string {
		return 'ae_gestor_dashboard';
	}

	public function get_title(): string {
		return __( 'AE Gestor Dashboard', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-apps';
	}

	public function get_style_depends(): array {
		return [ 'apollo-elementor-base', 'apollo-elementor-gestor-dashboard' ];
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_gestor', [
			'label' => __( 'Gestor Dashboard', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'composites/gestor-dashboard';
	}

	protected function cache_key_fields(): array {
		return [];
	}

	protected function fetch_data( array $settings ): array {
		return [
			'global_kpis' => ChartsRepository::global_kpis(),
			'events'      => EventRepository::grid( [
				'per_page'      => 12,
				'upcoming_only' => false,
			] ),
		];
	}
}
