<?php
/**
 * Abstract Base Widget
 *
 * Provides shared helpers used by every Apollo Elementor widget:
 * – Apollo widget category
 * – Asset enqueue helpers
 * – REST API helper (nonce + site URL)
 * – Shared layout/style control sections
 *
 * @package Apollo\Elementor\Widgets
 */

declare(strict_types=1);

namespace Apollo\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Base extends Widget_Base {

	/** @return string[] */
	public function get_categories(): array {
		return [ 'apollo' ];
	}

	/** Shared CSS handle – always enqueued for Apollo widgets on the frontend. */
	public function get_style_depends(): array {
		return [ 'apollo-elementor-widgets' ];
	}

	/** Shared JS handle – always enqueued for Apollo widgets on the frontend. */
	public function get_script_depends(): array {
		return [ 'apollo-elementor-widgets' ];
	}

	/* ─── REST helpers ──────────────────────────────────────────── */

	/**
	 * Print a <script> block with Apollo REST config for JS hydration.
	 */
	protected function print_rest_config( string $endpoint_prefix ): void {
		$config = [
			'root'   => esc_url_raw( rest_url( 'apollo/v1/' . ltrim( $endpoint_prefix, '/' ) ) ),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
		];
		printf(
			'<script>window.apolloRest=window.apolloRest||{};window.apolloRest[%s]=%s;</script>',
			wp_json_encode( $endpoint_prefix ),
			wp_json_encode( $config )
		);
	}

	/* ─── Shared control sections ────────────────────────────────── */

	/**
	 * Register a "Content" heading control + per_page selector.
	 * Call from register_controls() in concrete classes.
	 */
	protected function add_content_section( array $options = [] ): void {
		$this->start_controls_section( 'section_content', [
			'label' => __( 'Content', 'apollo-elementor-pro' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$defaults = [
			'per_page'     => 6,
			'columns'      => 3,
			'show_filter'  => true,
		];
		$options = array_merge( $defaults, $options );

		$this->add_control( 'per_page', [
			'label'   => __( 'Items per page', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => $options['per_page'],
			'min'     => 1,
			'max'     => 48,
		] );

		$this->add_control( 'columns', [
			'label'   => __( 'Columns', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => (string) $options['columns'],
			'options' => [
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
			],
		] );

		if ( $options['show_filter'] ) {
			$this->add_control( 'show_filter', [
				'label'        => __( 'Show filter bar', 'apollo-elementor-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'apollo-elementor-pro' ),
				'label_off'    => __( 'No', 'apollo-elementor-pro' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			] );
		}

		$this->end_controls_section();
	}

	/**
	 * Register shared color / spacing style section.
	 */
	protected function add_shared_style_section(): void {
		$this->start_controls_section( 'section_shared_style', [
			'label' => __( 'Apollo Style', 'apollo-elementor-pro' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'accent_color', [
			'label'   => __( 'Accent colour', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::COLOR,
			'default' => '#FF9820',
			'selectors' => [
				'{{WRAPPER}} .apollo-accent' => 'color: {{VALUE}}; border-color: {{VALUE}};',
				'{{WRAPPER}} .apollo-accent-bg' => 'background: {{VALUE}};',
			],
		] );

		$this->add_control( 'card_radius', [
			'label'     => __( 'Card radius (px)', 'apollo-elementor-pro' ),
			'type'      => Controls_Manager::SLIDER,
			'range'     => [ 'px' => [ 'min' => 0, 'max' => 40 ] ],
			'default'   => [ 'size' => 12, 'unit' => 'px' ],
			'selectors' => [ '{{WRAPPER}} .apollo-card' => 'border-radius: {{SIZE}}{{UNIT}};' ],
		] );

		$this->end_controls_section();
	}
}
