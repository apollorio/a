<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Data\PlannerRepository;
use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class KanbanColumn extends BaseWidget {

	public function get_name(): string {
		return 'ae_kanban_column';
	}

	public function get_title(): string {
		return __( 'AE Kanban Column', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-columns';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_kanban', [
			'label' => __( 'Kanban Column', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'date', [
			'label'       => __( 'Date (ISO)', 'apollo-elementor' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => gmdate( 'Y-m-d' ),
			'description' => __( 'ISO date for this column, e.g. 2026-05-12', 'apollo-elementor' ),
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
		return 'atomic/kanban-column';
	}

	protected function cache_key_fields(): array {
		return [ 'date', 'user_id' ];
	}

	protected function fetch_data( array $settings ): array {
		$user_id = absint( $settings['user_id'] ?? 0 );
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		$target_date = sanitize_text_field( $settings['date'] ?? gmdate( 'Y-m-d' ) );
		$week        = PlannerRepository::week( $user_id );

		$day_data = [
			'date'  => $target_date,
			'label' => '',
			'day'   => 0,
			'items' => [],
		];

		foreach ( $week as $day ) {
			if ( ( $day['date'] ?? '' ) === $target_date ) {
				$day_data = $day;
				break;
			}
		}

		return $day_data;
	}
}
