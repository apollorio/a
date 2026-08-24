<?php

/**
 * Apollo Adverts — Force Featured Image.
 *
 * Requires a featured image before publishing a classified.
 * If no featured image is set, auto-selects the first gallery image.
 * Adapted from WPAdverts snippet force-featured-image.
 *
 * @package Apollo\Adverts
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Adverts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force Featured Image for classifieds.
 *
 * @since 1.1.0
 */
class ForceFeaturedImage {


	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		// Frontend form validation.
		add_filter( 'apollo/classifieds/validate_submission', array( $this, 'validate_featured_image' ), 10, 2 );

		// Admin: prevent publishing without thumbnail.
		add_action( 'transition_post_status', array( $this, 'check_on_publish' ), 10, 3 );

		// Auto-select first gallery image as thumbnail if none set.
		add_action( 'save_post_' . APOLLO_CPT_CLASSIFIED, array( $this, 'auto_set_thumbnail' ), 20, 2 );
	}

	/**
	 * Validate featured image on frontend form submission.
	 *
	 * Adapted from WPAdverts force_featured_image_add().
	 *
	 * @param array $errors Current validation errors.
	 * @param int   $post_id Post ID.
	 * @return array
	 */
	public function validate_featured_image( array $errors, int $post_id ): array {
		if ( ! has_post_thumbnail( $post_id ) ) {
			// Try to auto-set.
			$auto_set = $this->try_auto_set( $post_id );
			if ( ! $auto_set ) {
				$errors[] = array(
					'field'   => 'featured_image',
					'message' => __( 'Pelo menos uma imagem é obrigatória para publicar o anúncio.', 'apollo-adverts' ),
				);
			}
		}

		return $errors;
	}

	/**
	 * Prevent publishing without thumbnail in admin.
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 */
	public function check_on_publish( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( $post->post_type !== APOLLO_CPT_CLASSIFIED ) {
			return;
		}

		if ( $new_status !== 'publish' ) {
			return;
		}

		if ( ! has_post_thumbnail( $post->ID ) ) {
			// Try auto-set before blocking.
			$this->try_auto_set( $post->ID );

			// If still no thumbnail and in admin, add notice.
			if ( ! has_post_thumbnail( $post->ID ) && is_admin() ) {
				// Revert to draft.
				remove_action( 'transition_post_status', array( $this, 'check_on_publish' ), 10 );
				wp_update_post(
					array(
						'ID'          => $post->ID,
						'post_status' => $old_status === 'publish' ? 'draft' : $old_status,
					)
				);
				add_action( 'transition_post_status', array( $this, 'check_on_publish' ), 10, 3 );

				// Set admin notice.
				set_transient(
					'apollo_adverts_notice_' . get_current_user_id(),
					__( 'Não é possível publicar sem imagem destacada.', 'apollo-adverts' ),
					30
				);
			}
		}
	}

	/**
	 * Auto-set first attachment as thumbnail when saving.
	 *
	 * Adapted from WPAdverts force_featured_image().
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function auto_set_thumbnail( int $post_id, \WP_Post $post ): void {
		if ( has_post_thumbnail( $post_id ) ) {
			return;
		}

		$this->try_auto_set( $post_id );
	}

	/**
	 * Try to auto set the first gallery image as featured image.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True if a thumbnail was set.
	 */
	private function try_auto_set( int $post_id ): bool {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_parent'    => $post_id,
				'post_mime_type' => 'image',
				'posts_per_page' => 1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $attachments ) ) {
			set_post_thumbnail( $post_id, $attachments[0] );
			return true;
		}

		return false;
	}
}
