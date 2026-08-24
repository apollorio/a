<?php
/**
 * Apollo CoAuthor — CoAuthor Iterator.
 *
 * Provides a template-friendly iterator for looping through co-authors
 * of a post while temporarily swapping the global $authordata so that
 * template tags like `get_the_author()` return each co-author in turn.
 *
 * Usage in themes/templates:
 *   $iter = new \Apollo\CoAuthor\Components\CoauthorIterator( $post->ID );
 *   while ( $iter->iterate() ) {
 *       echo get_the_author();          // each co-author
 *       if ( ! $iter->is_last() ) echo ', ';
 *   }
 *   // After loop, $authordata is restored to original.
 *
 * Adapted from Co-Authors Plus class-coauthors-iterator.php.
 *
 * @package Apollo\CoAuthor
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\CoAuthor\Components;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Iterator for co-authors of a post.
 *
 * @since 1.1.0
 */
class CoauthorIterator {

	/**
	 * Current position in the array (-1 = not started / finished).
	 *
	 * @var int
	 */
	public int $position = -1;

	/**
	 * Original $authordata to restore after iteration.
	 *
	 * @var \WP_User|object|null
	 */
	public $original_authordata;

	/**
	 * Current co-author in the loop.
	 *
	 * @var \WP_User|object|null
	 */
	public $current_author;

	/**
	 * Array of co-author objects.
	 *
	 * @var array
	 */
	public array $authordata_array = array();

	/**
	 * Total number of co-authors.
	 *
	 * @var int
	 */
	public int $count = 0;

	/**
	 * Constructor.
	 *
	 * @param int $post_id Post ID. Defaults to current $post->ID.
	 */
	public function __construct( int $post_id = 0 ) {
		global $post, $authordata;

		if ( ! $post_id && $post ) {
			$post_id = (int) $post->ID;
		}

		if ( ! $post_id ) {
			return;
		}

		$this->original_authordata = $authordata;
		$this->current_author      = $authordata;

		// Use apollo_get_coauthors if available, otherwise fall back to post meta.
		if ( function_exists( 'apollo_get_coauthors' ) ) {
			$this->authordata_array = apollo_get_coauthors( $post_id );
		} else {
			$coauthor_ids = get_post_meta( $post_id, APOLLO_COAUTHOR_META_KEY, true );
			if ( is_array( $coauthor_ids ) && ! empty( $coauthor_ids ) ) {
				foreach ( $coauthor_ids as $uid ) {
					$user = get_userdata( (int) $uid );
					if ( $user ) {
						$this->authordata_array[] = $user;
					}
				}
			}

			// If no co-authors, at least include the post author.
			if ( empty( $this->authordata_array ) ) {
				$post_obj = get_post( $post_id );
				if ( $post_obj && $post_obj->post_author ) {
					$user = get_userdata( (int) $post_obj->post_author );
					if ( $user ) {
						$this->authordata_array[] = $user;
					}
				}
			}
		}

		$this->count = count( $this->authordata_array );
	}

	/**
	 * Advance the iterator.
	 *
	 * Sets the global $authordata to the next co-author. Returns false
	 * when the loop is finished and restores the original $authordata.
	 *
	 * @return bool True if there is a current co-author, false if done.
	 */
	public function iterate(): bool {
		global $authordata;

		++$this->position;

		// End of loop — restore original.
		if ( $this->position > $this->count - 1 ) {
			$authordata           = $this->original_authordata; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			$this->current_author = $this->original_authordata;
			$this->position       = -1;

			return false;
		}

		// Save original at start.
		if ( 0 === $this->position && ! empty( $authordata ) ) {
			$this->original_authordata = $authordata;
		}

		$authordata           = $this->authordata_array[ $this->position ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$this->current_author = $this->authordata_array[ $this->position ];

		return true;
	}

	/**
	 * Get current position.
	 *
	 * @return int|false Position (0-indexed) or false if not iterating.
	 */
	public function get_position() {
		return -1 === $this->position ? false : $this->position;
	}

	/**
	 * Check if current co-author is the last one.
	 *
	 * @return bool
	 */
	public function is_last(): bool {
		return $this->position === $this->count - 1;
	}

	/**
	 * Check if current co-author is the first one.
	 *
	 * @return bool
	 */
	public function is_first(): bool {
		return 0 === $this->position;
	}

	/**
	 * Get total number of co-authors.
	 *
	 * @return int
	 */
	public function count(): int {
		return $this->count;
	}

	/**
	 * Get all co-author objects.
	 *
	 * @return array
	 */
	public function get_all(): array {
		return $this->authordata_array;
	}
}
