<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets\Atomic;

use Apollo\ElementorAE\Data\DJRepository;
use Apollo\ElementorAE\Widgets\BaseWidget;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BentoGrid extends BaseWidget {

	public function get_name(): string {
		return 'ae_bento_grid';
	}

	public function get_title(): string {
		return __( 'AE Bento Grid', 'apollo-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_bento', [
			'label' => __( 'Bento Grid', 'apollo-elementor' ),
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
		return 'atomic/bento-grid';
	}

	protected function cache_key_fields(): array {
		return [ 'dj_user_id' ];
	}

	protected function fetch_data( array $settings ): array {
		$user_id = absint( $settings['dj_user_id'] ?? 0 );

		if ( 0 === $user_id ) {
			$user_id = (int) get_post_field( 'post_author', get_the_ID() );
		}

		$dj = DJRepository::get( $user_id );

		return [
			'dj'    => $dj,
			'cells' => [
				[
					'kicker' => __( 'Membership', 'apollo-elementor' ),
					'num'    => $dj['membership'] ?: '—',
					'body'   => '',
				],
				[
					'kicker' => __( 'City', 'apollo-elementor' ),
					'num'    => $dj['city'] ?: '—',
					'body'   => '',
				],
				[
					'kicker' => __( 'Wow', 'apollo-elementor' ),
					'num'    => (string) $dj['wow_count'],
					'body'   => '',
				],
				[
					'kicker' => __( 'Favourites', 'apollo-elementor' ),
					'num'    => (string) $dj['fav_count'],
					'body'   => '',
				],
				[
					'kicker' => __( 'Bio', 'apollo-elementor' ),
					'num'    => '',
					'body'   => $dj['bio'],
				],
			],
		];
	}
}
