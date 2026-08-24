<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Composites;

use Apollo\ElementorAE\Data\PlannerRepository;
use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WeeklyPlanner extends BaseWidget {

	public function get_name(): string {
		return 'ae_weekly_planner';
	}

	public function get_title(): string {
		return __( 'AE Weekly Planner', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	public function get_style_depends(): array {
		return [ 'apollo-elementor-base', 'apollo-elementor-weekly-planner' ];
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_planner', [
			'label' => __( 'Weekly Planner', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'user_id', [
			'label'   => __( 'User ID', 'apollo-elementor' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 0,
			'min'     => 0,
		] );

		$this->add_control( 'week_offset', [
			'label'   => __( 'Week Offset', 'apollo-elementor' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 0,
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'composites/weekly-planner';
	}

	protected function cache_key_fields(): array {
		return [ 'user_id', 'week_offset' ];
	}

	protected function fetch_data( array $settings ): array {
		$user_id     = absint( $settings['user_id'] ?? 0 );
		$week_offset = (int) ( $settings['week_offset'] ?? 0 );

		return [
			'days' => PlannerRepository::week( $user_id, $week_offset ),
		];
	}
}
