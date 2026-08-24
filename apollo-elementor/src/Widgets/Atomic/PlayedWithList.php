<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Data\DJRepository;
use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PlayedWithList extends BaseWidget {

	public function get_name(): string {
		return 'ae_played_with';
	}

	public function get_title(): string {
		return __( 'AE Played With', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_played_with', [
			'label' => __( 'Played With', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'dj_user_id', [
			'label'       => __( 'DJ User ID', 'apollo-elementor' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'description' => __( '0 = current post author.', 'apollo-elementor' ),
		] );

		$this->end_controls_section();

		$this->register_cache_section();
		$this->register_accent_section();
	}

	protected function template_slug(): string {
		return 'atomic/played-with-list';
	}

	protected function cache_key_fields(): array {
		return [ 'dj_user_id' ];
	}

	protected function fetch_data( array $settings ): array {
		$user_id = absint( $settings['dj_user_id'] ?? 0 );

		if ( 0 === $user_id ) {
			$user_id = (int) get_post_field( 'post_author', get_the_ID() );
		}

		return [
			'partners' => DJRepository::played_with( $user_id ),
		];
	}
}
