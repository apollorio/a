<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Composites;

use Apollo\ElementorAE\Data\EventRepository;
use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MonthlyCalendar extends BaseWidget {

	public function get_name(): string {
		return 'ae_monthly_calendar';
	}

	public function get_title(): string {
		return __( 'AE Monthly Calendar', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	public function get_style_depends(): array {
		return [ 'apollo-elementor-base', 'apollo-elementor-monthly-calendar' ];
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_calendar', [
			'label' => __( 'Monthly Calendar', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'user_id', [
			'label'   => __( 'User ID', 'apollo-elementor' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 0,
			'min'     => 0,
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'composites/monthly-calendar';
	}

	protected function cache_key_fields(): array {
		return [ 'user_id' ];
	}

	protected function fetch_data( array $settings ): array {
		return [
			'events' => EventRepository::grid( [
				'per_page'      => 50,
				'upcoming_only' => false,
			] ),
		];
	}
}
