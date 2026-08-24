<?php
/**
 * Dynamic Tag: Profile Field
 *
 * Outputs any Apollo user meta key for the current or a specified user.
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

class ProfileField extends Tag {

	public function get_name(): string {
		return 'apollo_profile_field';
	}

	public function get_title(): string {
		return __( 'Apollo Profile Field', 'apollo-elementor-pro' );
	}

	public function get_group(): string {
		return 'apollo';
	}

	public function get_categories(): array {
		return [
			Module::TEXT_CATEGORY,
			Module::URL_CATEGORY,
			Module::POST_META_CATEGORY,
		];
	}

	protected function register_controls(): void {
		$this->add_control( 'field_key', [
			'label'   => __( 'Meta key', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'display_name',
			'options' => [
				'display_name'           => __( 'Display name', 'apollo-elementor-pro' ),
				'user_email'             => __( 'Email', 'apollo-elementor-pro' ),
				'_apollo_membership'     => __( 'Membership tier', 'apollo-elementor-pro' ),
				'_apollo_bio'            => __( 'Bio', 'apollo-elementor-pro' ),
				'_apollo_city'           => __( 'City', 'apollo-elementor-pro' ),
				'_apollo_instagram'      => __( 'Instagram', 'apollo-elementor-pro' ),
				'_apollo_soundcloud'     => __( 'SoundCloud', 'apollo-elementor-pro' ),
				'_apollo_avatar_url'     => __( 'Avatar URL', 'apollo-elementor-pro' ),
			],
		] );

		$this->add_control( 'user_id', [
			'label'   => __( 'User ID (0 = current)', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 0,
			'min'     => 0,
		] );
	}

	public function render(): void {
		$key     = $this->get_settings( 'field_key' );
		$user_id = (int) $this->get_settings( 'user_id' );
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( ! $user_id ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		if ( in_array( $key, [ 'display_name', 'user_email' ], true ) ) {
			echo esc_html( $user->$key );
		} else {
			echo esc_html( (string) get_user_meta( $user_id, $key, true ) );
		}
	}
}
