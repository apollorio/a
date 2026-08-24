<?php
/**
 * Dynamic Tag: Fav Count
 *
 * Returns the total Favourite (bookmark) count for a post or user.
 *
 * @package Apollo\Elementor\DynamicTags
 */

declare(strict_types=1);

namespace Apollo\Elementor\DynamicTags;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FavCount extends Tag {

	public function get_name(): string {
		return 'apollo_fav_count';
	}

	public function get_title(): string {
		return __( 'Apollo Fav Count', 'apollo-elementor-pro' );
	}

	public function get_group(): string {
		return 'apollo';
	}

	public function get_categories(): array {
		return [ Module::TEXT_CATEGORY, Module::NUMBER_CATEGORY ];
	}

	protected function register_controls(): void {
		$this->add_control( 'target', [
			'label'   => __( 'Target', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'post',
			'options' => [
				'post' => __( 'Current post', 'apollo-elementor-pro' ),
				'user' => __( 'Profile user', 'apollo-elementor-pro' ),
			],
		] );
	}

	public function render(): void {
		$target = $this->get_settings( 'target' );
		$count  = 0;

		if ( 'post' === $target ) {
			$count = (int) get_post_meta( get_the_ID(), '_apollo_fav_count', true );
		} else {
			$user_id = (int) get_query_var( 'apollo_user_id', get_current_user_id() );
			$count   = (int) get_user_meta( $user_id, '_apollo_fav_total', true );
		}

		echo esc_html( number_format_i18n( $count ) );
	}
}
