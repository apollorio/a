<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Composites;

use Apollo\ElementorAE\Data\DJRepository;
use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DJSinglePage extends BaseWidget {

	public function get_name(): string {
		return 'ae_dj_single_page';
	}

	public function get_title(): string {
		return __( 'AE DJ Single Page', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-single-page';
	}

	public function get_style_depends(): array {
		return [ 'apollo-elementor-base', 'apollo-elementor-dj-single-page' ];
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_dj', [
			'label' => __( 'DJ Single Page', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'dj_user_id', [
			'label'   => __( 'DJ User ID', 'apollo-elementor' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 0,
			'min'     => 0,
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'composites/dj-single-page';
	}

	protected function cache_key_fields(): array {
		return [ 'dj_user_id' ];
	}

	protected function fetch_data( array $settings ): array {
		$user_id = absint( $settings['dj_user_id'] ?? 0 );

		return [
			'dj'          => DJRepository::get( $user_id ),
			'played_with' => DJRepository::played_with( $user_id ),
		];
	}
}
