<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DiscographyList extends BaseWidget {

	public function get_name(): string {
		return 'ae_discography_list';
	}

	public function get_title(): string {
		return __( 'AE Discography', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-headphones';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_discography', [
			'label' => __( 'Discography', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$repeater = new Repeater();

		$repeater->add_control( 'title', [
			'label'   => __( 'Title', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Track Title', 'apollo-elementor' ),
		] );

		$repeater->add_control( 'year', [
			'label'   => __( 'Year', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		] );

		$repeater->add_control( 'genre', [
			'label'   => __( 'Genre', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		] );

		$repeater->add_control( 'duration', [
			'label'   => __( 'Duration', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		] );

		$this->add_control( 'tracks', [
			'label'       => __( 'Tracks', 'apollo-elementor' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => [],
			'title_field' => '{{{ title }}}',
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'atomic/discography-list';
	}

	protected function cache_key_fields(): array {
		return [ 'tracks' ];
	}

	protected function fetch_data( array $settings ): array {
		return [
			'tracks' => $settings['tracks'] ?? [],
		];
	}
}
