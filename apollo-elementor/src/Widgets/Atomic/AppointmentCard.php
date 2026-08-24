<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AppointmentCard extends BaseWidget {

	public function get_name(): string {
		return 'ae_appointment_card';
	}

	public function get_title(): string {
		return __( 'AE Appointment Card', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_appointment', [
			'label' => __( 'Appointment Card', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'title', [
			'label'   => __( 'Title', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Meeting', 'apollo-elementor' ),
		] );

		$this->add_control( 'time_start', [
			'label'   => __( 'Start time', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '09:00',
		] );

		$this->add_control( 'time_end', [
			'label'   => __( 'End time', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '10:00',
		] );

		$this->add_control( 'type', [
			'label'   => __( 'Type', 'apollo-elementor' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'appt',
			'options' => [
				'appt'   => __( 'Appointment', 'apollo-elementor' ),
				'apollo' => __( 'Apollo', 'apollo-elementor' ),
				'focus'  => __( 'Focus', 'apollo-elementor' ),
				'health' => __( 'Health', 'apollo-elementor' ),
			],
		] );

		$this->add_control( 'venue', [
			'label'   => __( 'Venue', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'atomic/appointment-card';
	}

	protected function cache_key_fields(): array {
		return [ 'title', 'time_start', 'time_end', 'type', 'venue' ];
	}

	protected function fetch_data( array $settings ): array {
		return [
			'title'      => $settings['title'] ?? '',
			'time_start' => $settings['time_start'] ?? '',
			'time_end'   => $settings['time_end'] ?? '',
			'type'       => $settings['type'] ?? 'appt',
			'venue'      => $settings['venue'] ?? '',
		];
	}
}
