<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Composites;

use Apollo\ElementorAE\Data\EventRepository;
use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EventSinglePage extends BaseWidget {

	public function get_name(): string {
		return 'ae_event_single_page';
	}

	public function get_title(): string {
		return __( 'AE Event Single Page', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-single-page';
	}

	public function get_style_depends(): array {
		return [ 'apollo-elementor-base', 'apollo-elementor-event-single-page' ];
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_event', [
			'label' => __( 'Event Single Page', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'event_id', [
			'label'   => __( 'Event ID', 'apollo-elementor' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 0,
			'min'     => 0,
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'composites/event-single-page';
	}

	protected function cache_key_fields(): array {
		return [ 'event_id' ];
	}

	protected function fetch_data( array $settings ): array {
		$event_id = absint( $settings['event_id'] ?? 0 );

		return [
			'event' => EventRepository::get( $event_id ),
		];
	}
}
