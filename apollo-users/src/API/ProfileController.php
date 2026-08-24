<?php

/**
 * Profile REST API Controller
 *
 * @package Apollo\Users
 */

declare(strict_types=1);

namespace Apollo\Users\API;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Profile Controller class
 */
class ProfileController {


	/**
	 * API namespace
	 *
	 * @var string
	 */
	private string $namespace = 'apollo/v1';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Routes registered externally by Plugin::register_rest_routes()
	}

	/**
	 * Register REST routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Upload avatar
		register_rest_route(
			$this->namespace,
			'/profile/avatar',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'upload_avatar' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		// Delete avatar
		register_rest_route(
			$this->namespace,
			'/profile/avatar',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_avatar' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		// Upload cover
		register_rest_route(
			$this->namespace,
			'/profile/cover',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'upload_cover' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		// Delete cover
		register_rest_route(
			$this->namespace,
			'/profile/cover',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_cover' ),
				'permission_callback' => 'is_user_logged_in',
			)
		);

		// Get profile views (who viewed me)
		register_rest_route(
			$this->namespace,
			'/profile/views',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_profile_views' ),
				'permission_callback' => 'is_user_logged_in',
				'args'                => array(
					'limit' => array(
						'default'           => 10,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Matchmaking endpoints - DISABLED: All users are connected
		// register_rest_route( $this->namespace, '/profile/match', [
		// 'methods'             => \WP_REST_Server::CREATABLE,
		// 'callback'            => [ $this, 'create_match' ],
		// 'permission_callback' => 'is_user_logged_in',
		// 'args'                => [
		// 'target_user_id' => [
		// 'required'          => true,
		// 'sanitize_callback' => 'absint',
		// ],
		// 'action' => [
		// 'required' => true,
		// 'enum'     => [ 'like', 'pass', 'superlike' ],
		// ],
		// ],
		// ] );

		// register_rest_route( $this->namespace, '/profile/matches', [
		// 'methods'             => \WP_REST_Server::READABLE,
		// 'callback'            => [ $this, 'get_matches' ],
		// 'permission_callback' => 'is_user_logged_in',
		// ] );
	}

	/**
	 * Upload avatar
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function upload_avatar( \WP_REST_Request $request ) {
		$files = $request->get_file_params();

		if ( empty( $files['file'] ) ) {
			return new \WP_Error(
				'no_file',
				__( 'Nenhum arquivo enviado.' ),
				array( 'status' => 400 )
			);
		}

		/* Only allow image files for avatars */
		$allowed = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		$mime    = $files['file']['type'] ?? '';
		if ( ! in_array( $mime, $allowed, true ) ) {
			return new \WP_Error( 'invalid_type', __( 'Apenas imagens são aceitas (JPG, PNG, GIF, WebP).' ), array( 'status' => 415 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$user_id = get_current_user_id();

		// Upload file
		$_FILES['upload'] = $files['file'];
		$attachment_id    = media_handle_upload( 'upload', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			return new \WP_Error(
				'upload_failed',
				$attachment_id->get_error_message(),
				array( 'status' => 500 )
			);
		}

		// Delete old avatar
		$old_avatar = get_user_meta( $user_id, 'custom_avatar', true );
		if ( $old_avatar ) {
			wp_delete_attachment( $old_avatar, true );
		}

		// Save new avatar
		update_user_meta( $user_id, 'custom_avatar', $attachment_id );
		update_user_meta( $user_id, 'avatar_thumb', wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) );

		return rest_ensure_response(
			array(
				'success'    => true,
				'message'    => __( 'Avatar atualizado!' ),
				'avatar_url' => wp_get_attachment_image_url( $attachment_id, 'medium' ),
			)
		);
	}

	/**
	 * Delete avatar
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function delete_avatar( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();

		$avatar_id = get_user_meta( $user_id, 'custom_avatar', true );
		if ( $avatar_id ) {
			wp_delete_attachment( $avatar_id, true );
		}

		delete_user_meta( $user_id, 'custom_avatar' );
		delete_user_meta( $user_id, 'avatar_thumb' );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Avatar removido.' ),
			)
		);
	}

	/**
	 * Upload cover image
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function upload_cover( \WP_REST_Request $request ) {
		$files = $request->get_file_params();

		if ( empty( $files['file'] ) ) {
			return new \WP_Error(
				'no_file',
				__( 'Nenhum arquivo enviado.' ),
				array( 'status' => 400 )
			);
		}

		/* Only allow image files for cover */
		$allowed = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		$mime    = $files['file']['type'] ?? '';
		if ( ! in_array( $mime, $allowed, true ) ) {
			return new \WP_Error( 'invalid_type', __( 'Apenas imagens são aceitas (JPG, PNG, GIF, WebP).' ), array( 'status' => 415 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$user_id = get_current_user_id();

		// Upload file
		$_FILES['upload'] = $files['file'];
		$attachment_id    = media_handle_upload( 'upload', 0 );

		if ( is_wp_error( $attachment_id ) ) {
			return new \WP_Error(
				'upload_failed',
				$attachment_id->get_error_message(),
				array( 'status' => 500 )
			);
		}

		// Delete old cover
		$old_cover = get_user_meta( $user_id, 'cover_image', true );
		if ( $old_cover ) {
			wp_delete_attachment( $old_cover, true );
		}

		// Save new cover
		update_user_meta( $user_id, 'cover_image', $attachment_id );

		return rest_ensure_response(
			array(
				'success'   => true,
				'message'   => __( 'Capa atualizada!' ),
				'cover_url' => wp_get_attachment_image_url( $attachment_id, 'full' ),
			)
		);
	}

	/**
	 * Delete cover image
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function delete_cover( \WP_REST_Request $request ): \WP_REST_Response {
		$user_id = get_current_user_id();

		$cover_id = get_user_meta( $user_id, 'cover_image', true );
		if ( $cover_id ) {
			wp_delete_attachment( $cover_id, true );
		}

		delete_user_meta( $user_id, 'cover_image' );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Capa removida.' ),
			)
		);
	}

	/**
	 * Get profile views
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_profile_views( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$user_id = get_current_user_id();
		$limit   = min( $request->get_param( 'limit' ), 50 );
		$table   = $wpdb->prefix . 'apollo_profile_views';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return rest_ensure_response( array( 'views' => array() ) );
		}

		$views = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT viewer_user_id, MAX(viewed_at) as last_viewed
			 FROM {$table}
			 WHERE profile_user_id = %d AND viewer_user_id > 0
			 GROUP BY viewer_user_id
			 ORDER BY last_viewed DESC
			 LIMIT %d",
				$user_id,
				$limit
			)
		);

		$data = array();
		foreach ( $views as $view ) {
			$viewer = get_user_by( 'ID', $view->viewer_user_id );
			if ( ! $viewer ) {
				continue;
			}

			$data[] = array(
				'user_id'      => $viewer->ID,
				'display_name' => $viewer->display_name,
				'avatar_url'   => function_exists( 'apollo_get_user_avatar_url' )
					? apollo_get_user_avatar_url( $viewer->ID, 'thumbnail' )
					: get_avatar_url( $viewer->ID ),
				'profile_url'  => function_exists( 'apollo_get_profile_url' )
					? apollo_get_profile_url( $viewer )
					: home_url( '/id/' . $viewer->user_login ),
				'viewed_at'    => $view->last_viewed,
			);
		}

		return rest_ensure_response( array( 'views' => $data ) );
	}

	/**
	 * Create match action (like/pass/superlike)
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_match( \WP_REST_Request $request ) {
		global $wpdb;

		$user_id        = get_current_user_id();
		$target_user_id = $request->get_param( 'target_user_id' );
		$action         = $request->get_param( 'action' );

		// Can't match yourself
		if ( $user_id === $target_user_id ) {
			return new \WP_Error(
				'invalid_target',
				__( 'Você não pode dar match em si mesmo.' ),
				array( 'status' => 400 )
			);
		}

		// Check target exists
		if ( ! get_user_by( 'ID', $target_user_id ) ) {
			return new \WP_Error(
				'user_not_found',
				__( 'Usuário não encontrado.' ),
				array( 'status' => 404 )
			);
		}

		$table = $wpdb->prefix . 'apollo_matchmaking';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return new \WP_Error(
				'feature_unavailable',
				__( 'Sistema de matchmaking não configurado.' ),
				array( 'status' => 500 )
			);
		}

		// Map action to status
		$status_map = array(
			'like'      => 'liked',
			'pass'      => 'passed',
			'superlike' => 'superliked',
		);
		$status     = $status_map[ $action ];

		// Insert or update match
		$wpdb->replace(
			$table,
			array(
				'user_id'        => $user_id,
				'target_user_id' => $target_user_id,
				'status'         => $status,
				'created_at'     => current_time( 'mysql' ),
			)
		);

		// Check for mutual match
		$mutual = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table}
			 WHERE user_id = %d AND target_user_id = %d AND status IN ('liked', 'superliked')",
				$target_user_id,
				$user_id
			)
		);

		$is_match = false;
		if ( $mutual && in_array( $action, array( 'like', 'superlike' ), true ) ) {
			$is_match = true;

			// Trigger match hook
			do_action( 'apollo_users_match_created', $user_id, $target_user_id );
		}

		return rest_ensure_response(
			array(
				'success'  => true,
				'is_match' => $is_match,
				'message'  => $is_match
					? __( 'É um match! 🎉' )
					: __( 'Ação registrada.' ),
			)
		);
	}

	/**
	 * Get user's matches
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_matches( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$user_id = get_current_user_id();
		$table   = $wpdb->prefix . 'apollo_matchmaking';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return rest_ensure_response( array( 'matches' => array() ) );
		}

		// Find mutual matches
		$matches = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m1.target_user_id, m1.created_at
			 FROM {$table} m1
			 INNER JOIN {$table} m2 ON m1.user_id = m2.target_user_id AND m1.target_user_id = m2.user_id
			 WHERE m1.user_id = %d
			   AND m1.status IN ('liked', 'superliked')
			   AND m2.status IN ('liked', 'superliked')
			 ORDER BY m1.created_at DESC",
				$user_id
			)
		);

		$data = array();
		foreach ( $matches as $match ) {
			$user = get_user_by( 'ID', $match->target_user_id );
			if ( ! $user ) {
				continue;
			}

			$data[] = array(
				'user_id'      => $user->ID,
				'username'     => $user->user_login,
				'display_name' => $user->display_name,
				'avatar_url'   => function_exists( 'apollo_get_user_avatar_url' )
					? apollo_get_user_avatar_url( $user->ID, 'thumbnail' )
					: get_avatar_url( $user->ID ),
				'profile_url'  => function_exists( 'apollo_get_profile_url' )
					? apollo_get_profile_url( $user )
					: home_url( '/id/' . $user->user_login ),
				'matched_at'   => $match->created_at,
			);
		}

		return rest_ensure_response( array( 'matches' => $data ) );
	}
}
