<?php
/**
 * Apollo CoAuthor — Shortcodes Component.
 *
 * Registered shortcodes:
 *   [apollo_coauthors]      — Display co-authors list for current/given post.
 *   [apollo_coauthor_count] — Display co-author count.
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\CoAuthor\Components;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes handler.
 *
 * @since 1.0.0
 */
class Shortcodes {

	/**
	 * Constructor — registers shortcodes.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Register all shortcodes.
	 *
	 * @since 1.0.0
	 */
	public function register(): void {
		add_shortcode( 'apollo_coauthors', array( $this, 'render_coauthors' ) );
		add_shortcode( 'apollo_coauthor_count', array( $this, 'render_count' ) );
	}

	/**
	 * Render co-authors list.
	 *
	 * Usage: [apollo_coauthors post_id="123" separator=", " link="true" avatar="false" avatar_size="24"]
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_coauthors( $atts ): string {
		$atts = shortcode_atts(
			array(
				'post_id'     => '',
				'separator'   => ', ',
				'link'        => 'true',
				'avatar'      => 'false',
				'avatar_size' => '24',
				'before'      => '',
				'after'       => '',
			),
			$atts,
			'apollo_coauthors'
		);

		$post_id = ! empty( $atts['post_id'] ) ? (int) $atts['post_id'] : get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$coauthors = apollo_get_coauthors( $post_id );
		if ( empty( $coauthors ) ) {
			return '';
		}

		$show_link   = filter_var( $atts['link'], FILTER_VALIDATE_BOOLEAN );
		$show_avatar = filter_var( $atts['avatar'], FILTER_VALIDATE_BOOLEAN );
		$avatar_size = absint( $atts['avatar_size'] );

		$parts = array();
		foreach ( $coauthors as $author ) {
			$name = esc_html( $author['display_name'] );

			$avatar_html = '';
			if ( $show_avatar && ! empty( $author['avatar_url'] ) ) {
				$avatar_html = sprintf(
					'<img src="%s" alt="%s" width="%d" height="%d" class="apollo-coauthor-avatar" style="border-radius:50%%;vertical-align:middle;margin-right:4px;"> ',
					esc_url( $author['avatar_url'] ),
					esc_attr( $author['display_name'] ),
					$avatar_size,
					$avatar_size
				);
			}

			if ( $show_link && ! empty( $author['profile_url'] ) ) {
				$parts[] = sprintf(
					'%s<a href="%s" class="apollo-coauthor-link">%s</a>',
					$avatar_html,
					esc_url( $author['profile_url'] ),
					$name
				);
			} else {
				$parts[] = $avatar_html . $name;
			}
		}

		$output = implode( esc_html( $atts['separator'] ), $parts );

		return sprintf(
			'<span class="apollo-coauthors">%s%s%s</span>',
			esc_html( $atts['before'] ),
			$output,
			esc_html( $atts['after'] )
		);
	}

	/**
	 * Render co-author count.
	 *
	 * Usage: [apollo_coauthor_count post_id="123"]
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_count( $atts ): string {
		$atts = shortcode_atts(
			array(
				'post_id' => '',
			),
			$atts,
			'apollo_coauthor_count'
		);

		$post_id = ! empty( $atts['post_id'] ) ? (int) $atts['post_id'] : get_the_ID();
		if ( ! $post_id ) {
			return '0';
		}

		return (string) apollo_coauthor_count( $post_id );
	}
}
