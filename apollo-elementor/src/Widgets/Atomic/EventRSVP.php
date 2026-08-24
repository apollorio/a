<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Data\EventRepository;
use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EventRSVP extends BaseWidget {

	public function get_name(): string {
		return 'ae_event_rsvp';
	}

	public function get_title(): string {
		return __( 'AE Event RSVP', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-check-circle';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_rsvp', [
			'label' => __( 'Event RSVP', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'event_id', [
			'label'       => __( 'Event ID', 'apollo-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'description' => __( '0 = current post.', 'apollo-elementor' ),
		] );

		$this->add_control( 'button_text', [
			'label'   => __( 'Button Text', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Reserve a spot', 'apollo-elementor' ),
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'atomic/event-rsvp';
	}

	protected function cache_key_fields(): array {
		return [ 'event_id', 'button_text' ];
	}

	protected function fetch_data( array $settings ): array {
		$event_id = absint( $settings['event_id'] ?? 0 );

		if ( 0 === $event_id ) {
			$event_id = (int) get_the_ID();
		}

		$event = EventRepository::get( $event_id );

		return [
			'event_id'    => $event_id,
			'event_title' => $event['title'] ?? '',
			'capacity'    => $event['capacity'] ?? 0,
			'rsvp_count'  => $event['rsvp_count'] ?? 0,
			'button_text' => $settings['button_text'] ?? __( 'Reserve a spot', 'apollo-elementor' ),
		];
	}
}
