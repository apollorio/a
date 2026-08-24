<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MetaStrip extends BaseWidget {

	public function get_name(): string {
		return 'ae_meta_strip';
	}

	public function get_title(): string {
		return __( 'AE Meta Strip', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-info-bar';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_meta_strip', [
			'label' => __( 'Meta Strip', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$repeater = new Repeater();

		$repeater->add_control( 'label', [
			'label'   => __( 'Label', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Label', 'apollo-elementor' ),
		] );

		$repeater->add_control( 'value', [
			'label'   => __( 'Value', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Value', 'apollo-elementor' ),
		] );

		$repeater->add_control( 'sublabel', [
			'label'   => __( 'Sub-label', 'apollo-elementor' ),
			'type'    => Controls_Manager::TEXT,
			'default' => '',
		] );

		$this->add_control( 'cells', [
			'label'       => __( 'Cells', 'apollo-elementor' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => [
				[ 'label' => __( 'Genre', 'apollo-elementor' ), 'value' => 'Techno', 'sublabel' => '' ],
				[ 'label' => __( 'City', 'apollo-elementor' ), 'value' => 'Berlin', 'sublabel' => '' ],
				[ 'label' => __( 'Experience', 'apollo-elementor' ), 'value' => '10 yrs', 'sublabel' => '' ],
			],
			'title_field' => '{{{ label }}}',
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'atomic/meta-strip';
	}

	protected function cache_key_fields(): array {
		return [ 'cells' ];
	}

	protected function fetch_data( array $settings ): array {
		return [
			'cells' => $settings['cells'] ?? [],
		];
	}
}
