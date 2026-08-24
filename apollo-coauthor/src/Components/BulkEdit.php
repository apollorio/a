<?php
/**
 * Apollo CoAuthor — Bulk Edit Component.
 *
 * Adds a co-author selector to the WordPress bulk edit box so admins
 * can add/remove co-authors for multiple posts at once.
 *
 * Adapted from Co-Authors Plus _action_bulk_edit_custom_box /
 * action_bulk_edit_update_coauthors.
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
 * Bulk edit co-authors support.
 *
 * @since 1.1.0
 */
class BulkEdit {

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_action( 'bulk_edit_custom_box', array( $this, 'render_bulk_edit_box' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_bulk_edit' ) );

		// REST endpoint for bulk update.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Render the co-author field in bulk edit.
	 *
	 * Adapted from Co-Authors Plus _action_bulk_edit_custom_box.
	 *
	 * @param string $column_name Column being rendered.
	 * @param string $post_type   Current post type.
	 */
	public function render_bulk_edit_box( string $column_name, string $post_type ): void {
		// Only show for supported post types, on the 'author' column (or first custom).
		$supported = function_exists( 'apollo_coauthor_get_supported_post_types' )
			? apollo_coauthor_get_supported_post_types()
			: APOLLO_COAUTHOR_POST_TYPES;

		if ( ! in_array( $post_type, $supported, true ) ) {
			return;
		}

		// Only render once — hook fires per column, restrict to first custom column.
		static $rendered = false;
		if ( $rendered ) {
			return;
		}
		$rendered = true;

		wp_nonce_field( 'apollo_coauthor_bulk', 'apollo_coauthor_bulk_nonce' );
		?>
		<fieldset class="inline-edit-col-right inline-edit-coauthors">
			<div class="inline-edit-col">
				<label class="inline-edit-group">
					<span class="title"><?php esc_html_e( 'Co-Autores', 'apollo-coauthor' ); ?></span>
				</label>

				<div class="apollo-bulk-coauthor-wrap" style="margin: 4px 0;">
					<select name="apollo_bulk_coauthors[]"
							id="apollo-bulk-coauthors"
							multiple="multiple"
							style="width:100%; min-width:200px;"
							data-placeholder="<?php esc_attr_e( 'Buscar co-autores...', 'apollo-coauthor' ); ?>">
					</select>

					<p class="howto" style="margin-top:4px;">
						<?php esc_html_e( 'Selecione para adicionar. Deixe vazio para não alterar.', 'apollo-coauthor' ); ?>
					</p>

					<label style="margin-top:4px; display:block;">
						<input type="checkbox"
								name="apollo_bulk_coauthors_replace"
								value="1" />
						<?php esc_html_e( 'Substituir co-autores existentes', 'apollo-coauthor' ); ?>
					</label>
				</div>
			</div>
		</fieldset>

		<script>
		(function($) {
			$(function() {
				var $sel = $('#apollo-bulk-coauthors');
				if (!$sel.length || typeof $.fn.select2 === 'undefined') return;

				$sel.select2({
					ajax: {
						url: '<?php echo esc_url( rest_url( APOLLO_COAUTHOR_REST_NAMESPACE . '/coauthors/search' ) ); ?>',
						dataType: 'json',
						delay: 250,
						headers: { 'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>' },
						data: function(params) { return { q: params.term }; },
						processResults: function(data) {
							return {
								results: (data || []).map(function(u) {
									return { id: u.id || u.ID, text: u.display_name || u.name };
								})
							};
						}
					},
					minimumInputLength: 2,
					placeholder: '<?php echo esc_js( __( 'Buscar co-autores...', 'apollo-coauthor' ) ); ?>'
				});
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Save co-authors from bulk edit.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_bulk_edit( int $post_id ): void {
		// Only run during bulk edit.
		if ( ! isset( $_REQUEST['bulk_edit'] ) ) {
			return;
		}

		if ( ! isset( $_REQUEST['apollo_coauthor_bulk_nonce'] )
			|| ! wp_verify_nonce( $_REQUEST['apollo_coauthor_bulk_nonce'], 'apollo_coauthor_bulk' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// If no co-authors submitted, don't change anything.
		if ( empty( $_REQUEST['apollo_bulk_coauthors'] ) || ! is_array( $_REQUEST['apollo_bulk_coauthors'] ) ) {
			return;
		}

		$user_ids = array_map( 'absint', $_REQUEST['apollo_bulk_coauthors'] );
		$user_ids = array_filter( $user_ids );

		if ( empty( $user_ids ) ) {
			return;
		}

		$replace = ! empty( $_REQUEST['apollo_bulk_coauthors_replace'] );

		if ( function_exists( 'apollo_set_coauthors' ) ) {
			apollo_set_coauthors( $post_id, $user_ids, ! $replace );
		}
	}

	/**
	 * Register REST route for bulk co-author operations.
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			APOLLO_COAUTHOR_REST_NAMESPACE,
			'/coauthors/bulk',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_bulk_update' ),
				'permission_callback' => function () {
					return current_user_can( 'edit_others_posts' );
				},
				'args'                => array(
					'post_ids' => array(
						'required'          => true,
						'type'              => 'array',
						'items'             => array( 'type' => 'integer' ),
						'sanitize_callback' => function ( $ids ) {
							return array_map( 'absint', (array) $ids );
						},
					),
					'user_ids' => array(
						'required'          => true,
						'type'              => 'array',
						'items'             => array( 'type' => 'integer' ),
						'sanitize_callback' => function ( $ids ) {
							return array_map( 'absint', (array) $ids );
						},
					),
					'replace'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}

	/**
	 * REST: Bulk update co-authors for multiple posts.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function rest_bulk_update( \WP_REST_Request $request ): \WP_REST_Response {
		$post_ids = $request->get_param( 'post_ids' );
		$user_ids = $request->get_param( 'user_ids' );
		$replace  = (bool) $request->get_param( 'replace' );

		if ( ! function_exists( 'apollo_set_coauthors' ) ) {
			return new \WP_REST_Response(
				array( 'error' => 'apollo_set_coauthors not available' ),
				500
			);
		}

		$updated = 0;
		$failed  = 0;

		foreach ( $post_ids as $pid ) {
			if ( ! current_user_can( 'edit_post', $pid ) ) {
				++$failed;
				continue;
			}

			$result = apollo_set_coauthors( $pid, $user_ids, ! $replace );
			if ( $result ) {
				++$updated;
			} else {
				++$failed;
			}
		}

		return new \WP_REST_Response(
			array(
				'updated' => $updated,
				'failed'  => $failed,
				'total'   => count( $post_ids ),
			),
			200
		);
	}
}