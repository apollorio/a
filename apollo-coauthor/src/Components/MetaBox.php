<?php
/**
 * Apollo CoAuthor — MetaBox Component.
 *
 * Renders a Select2-powered co-author metabox with drag & drop ordering
 * on all supported post types.
 *
 * @package Apollo\CoAuthor
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\CoAuthor\Components;

use WP_Post;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin metabox for co-author management.
 *
 * @since 1.0.0
 */
class MetaBox {

	/** @var string Nonce action. */
	private const NONCE_ACTION = 'apollo_coauthor_metabox';

	/** @var string Nonce field name. */
	private const NONCE_FIELD = '_apollo_coauthor_nonce';

	/**
	 * Constructor — hooks into admin.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register metabox on supported post types.
	 *
	 * @since 1.0.0
	 */
	public function add(): void {
		$post_types = apollo_coauthor_get_supported_post_types();

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'apollo-coauthors',
				__( 'Co-Autores', 'apollo-coauthor' ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the metabox.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post Current post object.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$coauthor_ids = get_post_meta( $post->ID, APOLLO_COAUTHOR_META_KEY, true );
		$coauthor_ids = is_array( $coauthor_ids ) ? $coauthor_ids : array();

		// Build existing co-authors data for the UI.
		$coauthors_data = array();
		foreach ( $coauthor_ids as $uid ) {
			$user = get_userdata( (int) $uid );
			if ( ! $user ) {
				continue;
			}
			$coauthors_data[] = array(
				'id'           => $user->ID,
				'display_name' => $user->display_name,
				'user_login'   => $user->user_login,
				'avatar_url'   => get_avatar_url( $user->ID, array( 'size' => 28 ) ),
			);
		}
		?>
		<div id="apollo-coauthor-metabox" data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>">
			<p class="description" style="margin-bottom: 8px;">
				<?php esc_html_e( 'Arraste para reordenar. Use o campo abaixo para buscar e adicionar co-autores.', 'apollo-coauthor' ); ?>
			</p>

			<!-- Sortable list -->
			<ul class="apollo-coauthor-list" id="apollo-coauthor-list">
				<?php foreach ( $coauthors_data as $author ) : ?>
					<li data-a-user="<?php echo esc_attr( (string) $author['id'] ); ?>">
						<span class="drag-handle dashicons dashicons-menu"></span>
						<img src="<?php echo esc_url( $author['avatar_url'] ); ?>" alt="">
						<span class="coauthor-name">
							<?php echo esc_html( $author['display_name'] ); ?>
							<small>(@<?php echo esc_html( $author['user_login'] ); ?>)</small>
						</span>
						<button type="button" class="remove-coauthor" title="<?php esc_attr_e( 'Remover', 'apollo-coauthor' ); ?>">&times;</button>
						<input type="hidden" name="apollo_coauthors[]" value="<?php echo esc_attr( (string) $author['id'] ); ?>">
					</li>
				<?php endforeach; ?>
			</ul>

			<!-- Select2 search -->
			<select id="apollo-coauthor-select" style="width: 100%;">
				<option value=""><?php esc_html_e( 'Buscar usuários...', 'apollo-coauthor' ); ?></option>
			</select>
		</div>
		<?php
	}

	/**
	 * Save metabox data.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public function save( int $post_id, WP_Post $post ): void {
		// Verify nonce.
		if (
			! isset( $_POST[ self::NONCE_FIELD ] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ),
				self::NONCE_ACTION
			)
		) {
			return;
		}

		// Skip autosave & revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Check post type.
		if ( ! in_array( $post->post_type, apollo_coauthor_get_supported_post_types(), true ) ) {
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

        // Process co-authors.
        if ( isset( $_POST['apollo_coauthors'] ) && is_array( $_POST['apollo_coauthors'] ) ) {
            $user_ids = array_map( 'absint', $_POST['apollo_coauthors'] );
            $user_ids = array_filter( $user_ids ); // Remove zeroes.
            if ( 'event' === $post->post_type && function_exists( 'apollo_event_save_coauthors' ) ) {
                apollo_event_save_coauthors( $post_id, array_values( $user_ids ) );
            } else {
                apollo_set_coauthors( $post_id, $user_ids );
            }
        } else {
            // Clear co-authors.
            if ( 'event' === $post->post_type && function_exists( 'apollo_event_save_coauthors' ) ) {
                apollo_event_save_coauthors( $post_id, array() );
            } else {
                delete_post_meta( $post_id, APOLLO_COAUTHOR_META_KEY );
                wp_set_object_terms( $post_id, array(), APOLLO_COAUTHOR_TAX );

                /**
                 * Fires when all co-authors are removed from a post.
                 *
                 * @since 1.0.0
                 *
                 * @param int $post_id Post ID.
                 */
                do_action( 'apollo/coauthor/cleared', $post_id );
            }
        }
	}
}