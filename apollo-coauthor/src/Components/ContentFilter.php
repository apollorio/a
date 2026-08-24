<?php
/**
 * Apollo CoAuthor — Content Filter Component.
 *
 * Filters author display name, byline, and provides template tag support
 * for co-authored posts.
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
 * Content display filters.
 *
 * @since 1.0.0
 */
class ContentFilter {

	/**
	 * Constructor — hooks into content filters.
	 */
	public function __construct() {
		add_filter( 'the_author', array( $this, 'filter_the_author' ) );
		add_filter( 'author_link', array( $this, 'filter_author_link' ), 10, 3 );
		add_filter( 'the_content', array( $this, 'maybe_append_byline' ), 20 );
	}

	/**
	 * Filter the author name to include co-authors.
	 *
	 * @since 1.0.0
	 *
	 * @param string $author Original author display name.
	 * @return string
	 */
	public function filter_the_author( string $author ): string {
		global $post;

		if ( ! $post instanceof \WP_Post ) {
			return $author;
		}

		// Only filter on supported post types.
		if ( ! in_array( $post->post_type, apollo_coauthor_get_supported_post_types(), true ) ) {
			return $author;
		}

		$coauthors = apollo_get_coauthors( $post->ID );
		if ( empty( $coauthors ) ) {
			return $author;
		}

		$names = array_column( $coauthors, 'display_name' );

		/**
		 * Filters how co-author names are joined in the byline.
		 *
		 * @since 1.0.0
		 *
		 * @param string $separator The separator between names.
		 * @param array  $names     Array of display names.
		 * @param int    $post_id   Post ID.
		 */
		$separator = apply_filters( 'apollo/coauthor/names_separator', ', ', $names, $post->ID );

		// Add primary author to the beginning if not already in co-authors.
		$primary_id   = (int) $post->post_author;
		$coauthor_ids = array_column( $coauthors, 'user_id' );

		if ( ! in_array( $primary_id, $coauthor_ids, true ) ) {
			array_unshift( $names, $author );
		}

		return implode( $separator, $names );
	}

	/**
	 * Filter the author link for the primary author.
	 *
	 * This doesn't change the link itself but could be used to build
	 * a multi-author link set in templates.
	 *
	 * @since 1.0.0
	 *
	 * @param string $link       Author archive link.
	 * @param int    $author_id  Author ID.
	 * @param string $author_nicename Author nicename.
	 * @return string
	 */
	public function filter_author_link( string $link, int $author_id, string $author_nicename ): string {
		// No modification needed — author archive query integration handles
		// showing co-authored posts on author pages. This filter is a hook
		// point for theme customization.
		return $link;
	}

	/**
	 * Optionally append a co-author byline to post content.
	 *
	 * Only applies if the theme doesn't already call the_author() in the
	 * template. Controlled by the `apollo/coauthor/append_byline` filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function maybe_append_byline( string $content ): string {
		global $post;

		if ( ! $post instanceof \WP_Post || ! is_singular() || ! is_main_query() ) {
			return $content;
		}

		/**
		 * Whether to append the co-author byline to post content.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $append  Whether to append. Default false.
		 * @param int  $post_id Post ID.
		 */
		$append = apply_filters( 'apollo/coauthor/append_byline', false, $post->ID );
		if ( ! $append ) {
			return $content;
		}

		$coauthors = apollo_get_coauthors( $post->ID );
		if ( empty( $coauthors ) ) {
			return $content;
		}

		$links = array();
		foreach ( $coauthors as $author ) {
			if ( ! empty( $author['profile_url'] ) ) {
				$links[] = sprintf(
					'<a href="%s" rel="author">%s</a>',
					esc_url( $author['profile_url'] ),
					esc_html( $author['display_name'] )
				);
			} else {
				$links[] = esc_html( $author['display_name'] );
			}
		}

		$byline = sprintf(
			'<div class="apollo-coauthor-byline"><strong>%s</strong> %s</div>',
			esc_html__( 'Co-autores:', 'apollo-coauthor' ),
			implode( ', ', $links )
		);

		return $content . $byline;
	}
}
