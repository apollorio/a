<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Data\EventRepository;
use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EventLineup extends BaseWidget {

	public function get_name(): string {
		return 'ae_event_lineup';
	}

	public function get_title(): string {
		return __( 'AE Event Lineup', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-post-list';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_lineup', [
			'label' => __( 'Event Lineup', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'event_id', [
			'label'       => __( 'Event ID', 'apollo-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'description' => __( '0 = current post.', 'apollo-elementor' ),
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'atomic/event-lineup';
	}

	protected function cache_key_fields(): array {
		return [ 'event_id' ];
	}

	protected function fetch_data( array $settings ): array {
		$event_id = absint( $settings['event_id'] ?? 0 );

		if ( 0 === $event_id ) {
			$event_id = (int) get_the_ID();
		}

		$event = EventRepository::get( $event_id );

		return [
			'event_title' => $event['title'] ?? '',
			'lineup'      => $event['lineup'] ?? [],
		];
	}
}
