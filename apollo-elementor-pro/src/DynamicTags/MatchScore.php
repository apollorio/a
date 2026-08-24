<?php
/**
 * Dynamic Tag: Match Score
 *
 * Returns the Apollo compatibility score between the current user and a target.
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

class MatchScore extends Tag {

	public function get_name(): string {
		return 'apollo_match_score';
	}

	public function get_title(): string {
		return __( 'Apollo Match Score', 'apollo-elementor-pro' );
	}

	public function get_group(): string {
		return 'apollo';
	}

	public function get_categories(): array {
		return [ Module::TEXT_CATEGORY, Module::NUMBER_CATEGORY ];
	}

	protected function register_controls(): void {
		$this->add_control( 'target_user_id', [
			'label'       => __( 'Target user ID', 'apollo-elementor-pro' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'description' => __( '0 = use current post author.', 'apollo-elementor-pro' ),
			'min'         => 0,
		] );

		$this->add_control( 'format', [
			'label'   => __( 'Format', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'percent',
			'options' => [
				'percent' => __( 'Percentage (75%)', 'apollo-elementor-pro' ),
				'raw'     => __( 'Raw score (0–100)', 'apollo-elementor-pro' ),
			],
		] );
	}

	public function render(): void {
		$target  = (int) $this->get_settings( 'target_user_id' );
		$current = get_current_user_id();
		if ( ! $current ) {
			return;
		}
		if ( ! $target ) {
			$target = (int) get_post_field( 'post_author', get_the_ID() );
		}

		// Fetch score from apollo meta (stored by apollo-users matching engine).
		$score_key = '_apollo_match_' . min( $current, $target ) . '_' . max( $current, $target );
		$score     = (int) get_transient( $score_key );

		if ( 'percent' === $this->get_settings( 'format' ) ) {
			echo esc_html( $score . '%' );
		} else {
			echo esc_html( (string) $score );
		}
	}
}
