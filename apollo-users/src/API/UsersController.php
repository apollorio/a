<?php

/**
 * Users REST API Controller
 *
 * @package Apollo\Users
 */

declare(strict_types=1);

namespace Apollo\Users\API;

use Apollo\Users\Components\MatchmakingEngine;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Users Controller class
 */
class UsersController {


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
		// Get current user
		register_rest_route(
			$this->namespace,
			'/users/me',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_current_user' ),
				'permission_callback' => array( $this, 'check_user_logged_in' ),
			)
		);

		// Update current user
		register_rest_route(
			$this->namespace,
			'/users/me',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_current_user' ),
				'permission_callback' => array( $this, 'check_user_logged_in' ),
			)
		);

		// Current user's top matches
		register_rest_route(
			$this->namespace,
			'/users/me/matches',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_my_matches' ),
				'permission_callback' => array( $this, 'check_user_logged_in' ),
				'args'                => array(
					'limit' => array(
						'default'           => 20,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// User search (BEFORE generic username route)
		register_rest_route(
			$this->namespace,
			'/users/search',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_users' ),
				'permission_callback' => array( $this, 'check_can_view_directory' ),
				'args'                => array(
					'q'        => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'page'     => array(
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'default'           => 20,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// User radar (BEFORE generic username route)
		register_rest_route(
			$this->namespace,
			'/users/radar',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_radar_users' ),
				// Public discovery (PARTY_MODEL) — payload is list-safe (no email/phone).
				'permission_callback' => '__return_true',
				'args'                => array(
					'limit'   => array(
						'default'           => 50,
						'sanitize_callback' => 'absint',
					),
					'orderby' => array(
						'default'           => 'registered',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'role'    => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get user by username (AFTER specific routes)
		register_rest_route(
			$this->namespace,
			'/users/(?P<username>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_by_username' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'username' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_user',
					),
				),
			)
		);

		// User directory (radar)
		register_rest_route(
			$this->namespace,
			'/users',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_users_directory' ),
				'permission_callback' => array( $this, 'check_can_view_directory' ),
				'args'                => array(
					'page'     => array(
						'default'           => 1,
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'default'           => 20,
						'sanitize_callback' => 'absint',
					),
					'search'   => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'location' => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'role'     => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Get user by ID
		register_rest_route(
			$this->namespace,
			'/users/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_by_id' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Update user by ID
		register_rest_route(
			$this->namespace,
			'/users/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_user_by_id' ),
				'permission_callback' => array( $this, 'check_can_edit_user' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// User preferences
		register_rest_route(
			$this->namespace,
			'/users/(?P<id>\d+)/preferences',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_preferences' ),
				'permission_callback' => array( $this, 'check_can_view_user' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Update user preferences
		register_rest_route(
			$this->namespace,
			'/users/(?P<id>\d+)/preferences',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_user_preferences' ),
				'permission_callback' => array( $this, 'check_can_edit_user' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// User matchmaking
		register_rest_route(
			$this->namespace,
			'/users/(?P<id>\d+)/matchmaking',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_matches' ),
				'permission_callback' => array( $this, 'check_can_view_user' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// User fields
		register_rest_route(
			$this->namespace,
			'/users/(?P<id>\d+)/fields',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user_fields' ),
				'permission_callback' => array( $this, 'check_can_view_user' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Update user fields
		register_rest_route(
			$this->namespace,
			'/users/(?P<id>\d+)/fields',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_user_fields' ),
				'permission_callback' => array( $this, 'check_can_edit_user' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Public profile data
		register_rest_route(
			$this->namespace,
			'/profile/(?P<username>[a-zA-Z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_public_profile' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'username' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_user',
					),
				),
			)
		);

		// Record profile view
		register_rest_route(
			$this->namespace,
			'/profile/(?P<username>[a-zA-Z0-9_-]+)/view',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'record_profile_view' ),
				'permission_callback' => '__return_true', // UNPROTECTED — no nonce, no rate limit, no dedup: every call INSERTs a row into apollo_profile_views and fires apollo/users/profile_visited. Needs a product decision first — a view counter sits badly beside NO_EGO_COUNTERS. plan-003 S-1.
				'args'                => array(
					'username' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_user',
					),
				),
			)
		);
	}

	/**
	 * Check if user is logged in
	 *
	 * @return bool
	 */
	public function check_user_logged_in(): bool {
		return is_user_logged_in();
	}

	/**
	 * Check if user can view directory
	 *
	 * @return bool
	 */
	public function check_can_view_directory(): bool {
		return is_user_logged_in();
	}

	/**
	 * Check if current user can view another user's data.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function check_can_view_user( \WP_REST_Request $request ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$target_id = (int) $request->get_param( 'id' );

		if ( get_current_user_id() === $target_id ) {
			return true;
		}

		return current_user_can( 'list_users' );
	}

	/**
	 * Check if current user can edit another user's data.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function check_can_edit_user( \WP_REST_Request $request ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$target_id = (int) $request->get_param( 'id' );

		if ( get_current_user_id() === $target_id ) {
			return true;
		}

		return current_user_can( 'edit_user', $target_id );
	}

	/**
	 * Get current user data
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_current_user( \WP_REST_Request $request ): \WP_REST_Response {
		$user = wp_get_current_user();

		return rest_ensure_response( $this->prepare_user_data( $user, true ) );
	}

	/**
	 * Update current user
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_current_user( \WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$params  = $request->get_json_params();

		// Allowed fields to update
		$allowed_meta = array(
			'_apollo_social_name',
			'_apollo_bio',
			'_apollo_phone',
			'user_location',
			'instagram',
			'_apollo_website',
			'_apollo_privacy_profile',
			'_apollo_privacy_email',
		);

		foreach ( $allowed_meta as $key ) {
			if ( isset( $params[ $key ] ) ) {
				update_user_meta( $user_id, $key, sanitize_text_field( $params[ $key ] ) );
			}
		}

		// Update display name if social name provided
		if ( ! empty( $params['_apollo_social_name'] ) ) {
			wp_update_user(
				array(
					'ID'           => $user_id,
					'display_name' => sanitize_text_field( $params['_apollo_social_name'] ),
				)
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Perfil atualizado com sucesso.' ),
			)
		);
	}

	/**
	 * Get user by username (public profile)
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_user_by_username( \WP_REST_Request $request ) {
		$username = $request->get_param( 'username' );
		$user     = get_user_by( 'login', $username );

		if ( ! $user ) {
			// Try by nicename (slug)
			$user = get_user_by( 'slug', $username );
		}

		if ( ! $user ) {
			return new \WP_Error(
				'user_not_found',
				__( 'Usuário não encontrado.' ),
				array( 'status' => 404 )
			);
		}

		$visible = $this->assert_profile_visible( $user );
		if ( is_wp_error( $visible ) ) {
			return $visible;
		}

		// Track profile view
		$this->track_profile_view( $user->ID );

		$is_own = get_current_user_id() === (int) $user->ID;

		return rest_ensure_response( $this->prepare_user_data( $user, $is_own ) );
	}

	/**
	 * Get users directory
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_users_directory( \WP_REST_Request $request ): \WP_REST_Response {
		$page     = $request->get_param( 'page' );
		$per_page = min( $request->get_param( 'per_page' ), 50 ); // Max 50
		$search   = $request->get_param( 'search' );
		$location = $request->get_param( 'location' );
		$role     = $request->get_param( 'role' );

		$args = array(
			'number'  => $per_page,
			'offset'  => ( $page - 1 ) * $per_page,
			'orderby' => 'registered',
			'order'   => 'DESC',
		);

		// Only exclude explicitly private profiles
		// All other users (public, members, no setting) are shown to logged-in users
		$args['meta_query'] = array(
			'relation' => 'OR',
			// Users with any privacy setting except 'private'
			array(
				'key'     => '_apollo_privacy_profile',
				'value'   => 'private',
				'compare' => '!=',
			),
			// Users who never set privacy (most users)
			array(
				'key'     => '_apollo_privacy_profile',
				'compare' => 'NOT EXISTS',
			),
		);

		// Search filter
		if ( $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_login', 'user_nicename', 'display_name' );
		}

		// Location filter - wrap privacy query in AND with location
		if ( $location ) {
			$args['meta_query'] = array(
				'relation' => 'AND',
				// Privacy conditions
				array(
					'relation' => 'OR',
					array(
						'key'     => '_apollo_privacy_profile',
						'value'   => 'private',
						'compare' => '!=',
					),
					array(
						'key'     => '_apollo_privacy_profile',
						'compare' => 'NOT EXISTS',
					),
				),
				// Location filter
				array(
					'key'     => 'user_location',
					'value'   => $location,
					'compare' => 'LIKE',
				),
			);
		}

		// Role filter
		if ( $role && in_array( $role, array_keys( wp_roles()->roles ), true ) ) {
			$args['role'] = $role;
		}

		$query = new \WP_User_Query( $args );
		$users = $query->get_results();
		$total = $query->get_total();

		$data = array();
		foreach ( $users as $user ) {
			$data[] = $this->prepare_user_data( $user, false, true );
		}

		return rest_ensure_response(
			array(
				'users'    => $data,
				'total'    => $total,
				'pages'    => ceil( $total / $per_page ),
				'page'     => $page,
				'per_page' => $per_page,
			)
		);
	}

	/**
	 * Prepare user data for response
	 *
	 * @param \WP_User $user        User object.
	 * @param bool     $is_own      Is this the user's own data.
	 * @param bool     $is_list     Is this for a list view.
	 * @return array
	 */
	private function prepare_user_data( \WP_User $user, bool $is_own = false, bool $is_list = false ): array {
		// Public-safe fields only (PARTY_MODEL discovery). Never leak PII here.
		$data = array(
			'id'           => $user->ID,
			'username'     => $user->user_login,
			'display_name' => $user->display_name,
			'social_name'  => get_user_meta( $user->ID, '_apollo_social_name', true ),
			'avatar_url'   => function_exists( 'apollo_get_user_avatar_url' )
				? apollo_get_user_avatar_url( $user->ID, 'medium' )
				: get_avatar_url( $user->ID ),
			'profile_url'  => function_exists( 'apollo_get_profile_url' )
				? apollo_get_profile_url( $user )
				: home_url( '/id/' . $user->user_login ),
			'location'     => get_user_meta( $user->ID, 'user_location', true ),
			'registered'   => $user->user_registered,
		);

		// List view - minimal data
		if ( $is_list ) {
			return $data;
		}

		// Full public profile data (still no email/phone)
		$data['bio']       = get_user_meta( $user->ID, '_apollo_bio', true );
		$data['website']   = get_user_meta( $user->ID, '_apollo_website', true );
		$data['instagram'] = get_user_meta( $user->ID, 'instagram', true );
		$data['cover_url'] = function_exists( 'apollo_get_user_cover_url' )
			? apollo_get_user_cover_url( $user->ID )
			: '';

		// P1 hardening: email/phone ONLY on own profile. Default public payloads must not
		// expose contact PII (previous logic leaked email whenever privacy_email was unset).
		if ( $is_own ) {
			$hide_email              = get_user_meta( $user->ID, '_apollo_privacy_email', true );
			$data['email']           = $user->user_email;
			$data['phone']           = get_user_meta( $user->ID, '_apollo_phone', true );
			$data['privacy_profile'] = get_user_meta( $user->ID, '_apollo_privacy_profile', true ) ?: 'public';
			$data['privacy_email']   = (bool) $hide_email;
			$data['roles']           = $user->roles;
		}

		return $data;
	}

	/**
	 * Enforce profile privacy for public REST reads.
	 *
	 * @param \WP_User $user Target user.
	 * @return true|\WP_Error True if viewable, WP_Error otherwise.
	 */
	private function assert_profile_visible( \WP_User $user ) {
		$privacy   = get_user_meta( $user->ID, '_apollo_privacy_profile', true ) ?: 'public';
		$viewer_id = get_current_user_id();

		if ( $viewer_id === (int) $user->ID ) {
			return true;
		}

		if ( $privacy === 'private' ) {
			return new \WP_Error(
				'profile_private',
				__( 'Este perfil é privado.', 'apollo-users' ),
				array( 'status' => 403 )
			);
		}

		if ( $privacy === 'members' && ! is_user_logged_in() ) {
			return new \WP_Error(
				'login_required',
				__( 'Faça login para ver este perfil.', 'apollo-users' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	/**
	 * Track profile view
	 *
	 * @param int $profile_user_id Viewed user ID.
	 * @return void
	 */
	private function track_profile_view( int $profile_user_id ): void {
		global $wpdb;

		$viewer_id = get_current_user_id();

		// Don't track own views
		if ( $viewer_id === $profile_user_id ) {
			return;
		}

		$table = $wpdb->prefix . 'apollo_profile_views';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return;
		}

		$wpdb->insert(
			$table,
			array(
				'profile_user_id' => $profile_user_id,
				'viewer_user_id'  => $viewer_id ?: 0,
				'viewer_ip'       => $viewer_id ? '' : apollo_get_client_ip(),
				'viewed_at'       => current_time( 'mysql' ),
			)
		);

		/**
		 * Fires after a profile view is tracked.
		 *
		 * @param int $profile_user_id Profile owner.
		 * @param int $viewer_id       Viewer user ID (0 if guest).
		 */
		do_action( 'apollo/users/profile_visited', $profile_user_id, $viewer_id ?: 0 );
	}

	/**
	 * Get user by ID
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_user_by_id( \WP_REST_Request $request ) {
		$user_id = $request->get_param( 'id' );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new \WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
		}

		$visible = $this->assert_profile_visible( $user );
		if ( is_wp_error( $visible ) ) {
			return $visible;
		}

		$is_own = get_current_user_id() === (int) $user->ID;

		return new \WP_REST_Response( $this->prepare_user_data( $user, $is_own ), 200 );
	}

	/**
	 * Update user by ID
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_user_by_id( \WP_REST_Request $request ) {
		$user_id = $request->get_param( 'id' );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new \WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
		}

		// TODO: Implement update logic
		return new \WP_REST_Response( array( 'message' => 'Update not implemented yet' ), 200 );
	}

	/**
	 * Get user preferences
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_user_preferences( \WP_REST_Request $request ) {
		$user_id = $request->get_param( 'id' );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new \WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
		}

		$preferences = array(
			'sound_preferences' => get_user_meta( $user_id, '_apollo_sound_preferences', true ),
			'membership'        => function_exists( 'apollo_get_user_membership' )
				? apollo_get_user_membership( $user_id )
				: ( function_exists( 'apollo_membership_get_user_slugs' )
					? apollo_membership_get_user_slugs( $user_id )
					: array( 'nao-verificado' ) ),
			'privacy_profile'   => get_user_meta( $user_id, '_apollo_privacy_profile', true ),
			'privacy_email'     => get_user_meta( $user_id, '_apollo_privacy_email', true ),
		);

		return new \WP_REST_Response( $preferences, 200 );
	}

	/**
	 * Update user preferences
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_user_preferences( \WP_REST_Request $request ) {
		$user_id = $request->get_param( 'id' );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new \WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
		}

		// TODO: Implement update logic
		return new \WP_REST_Response( array( 'message' => 'Update not implemented yet' ), 200 );
	}

	/**
	 * Get user matches
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_user_matches( \WP_REST_Request $request ) {
		$user_id = absint( $request->get_param( 'id' ) );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new \WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
		}

		$viewer_id = get_current_user_id();

		// Unauthenticated: return shell with no scores.
		if ( ! $viewer_id ) {
			return new \WP_REST_Response(
				array(
					'compatibility' => null,
					'mutual'        => array( 'me_to_them' => null, 'them_to_me' => null, 'average' => null ),
					'breakdown'     => array(),
					'top_matches'   => array(),
					'computed_at'   => null,
				),
				200
			);
		}

		$score     = MatchmakingEngine::get_score( $viewer_id, $user_id );
		$breakdown = MatchmakingEngine::get_score_breakdown( $viewer_id, $user_id );
		$mutual    = MatchmakingEngine::get_mutual_affinity( $viewer_id, $user_id );
		$top       = MatchmakingEngine::get_top_matches( $viewer_id, 20 );

		return new \WP_REST_Response(
			array(
				'compatibility' => $score,
				'mutual'        => $mutual,
				'breakdown'     => $breakdown,
				'top_matches'   => $top,
				'computed_at'   => $breakdown['computed_at'] ?? null,
			),
			200
		);
	}

	/**
	 * Get current user's top matches.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_my_matches( \WP_REST_Request $request ) {
		$viewer_id = get_current_user_id();
		$limit     = absint( $request->get_param( 'limit' ) ) ?: 20;

		$top = MatchmakingEngine::get_top_matches( $viewer_id, min( $limit, 100 ) );

		return new \WP_REST_Response(
			array(
				'matches' => $top,
				'total'   => count( $top ),
			),
			200
		);
	}

	/**
	 * Get user fields
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_user_fields( \WP_REST_Request $request ) {
		$user_id = $request->get_param( 'id' );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new \WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
		}

		$fields = array(
			'bio'        => get_user_meta( $user_id, '_apollo_bio', true ),
			'website'    => get_user_meta( $user_id, '_apollo_website', true ),
			'phone'      => get_user_meta( $user_id, '_apollo_phone', true ),
			'birth_date' => get_user_meta( $user_id, '_apollo_birth_date', true ),
		);

		return new \WP_REST_Response( $fields, 200 );
	}

	/**
	 * Update user fields
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_user_fields( \WP_REST_Request $request ) {
		$user_id = $request->get_param( 'id' );
		$user    = get_userdata( $user_id );

		if ( ! $user ) {
			return new \WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
		}

		// TODO: Implement update logic
		return new \WP_REST_Response( array( 'message' => 'Update not implemented yet' ), 200 );
	}

	/**
	 * Search users
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function search_users( \WP_REST_Request $request ) {
		$query    = $request->get_param( 'q' );
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );

		$args = array(
			'search'         => '*' . $query . '*',
			'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
			'number'         => $per_page,
			'offset'         => ( $page - 1 ) * $per_page,
		);

		$user_query = new \WP_User_Query( $args );
		$users      = $user_query->get_results();

		$data = array_map( array( $this, 'prepare_user_data' ), $users );

		return new \WP_REST_Response(
			array(
				'users' => $data,
				'total' => $user_query->get_total(),
			),
			200
		);
	}

	/**
	 * Get radar users
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_radar_users( \WP_REST_Request $request ) {
		$limit   = min( absint( $request->get_param( 'limit' ) ) ?: 50, 100 );
		$orderby = sanitize_key( (string) $request->get_param( 'orderby' ) ) ?: 'registered';
		$role    = sanitize_text_field( (string) $request->get_param( 'role' ) );

		// Allowlist orderby to avoid raw query abuse.
		$allowed_orderby = array( 'registered', 'display_name', 'login', 'nicename', 'ID' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'registered';
		}

		$args = array(
			'number'     => $limit,
			'orderby'    => $orderby,
			'order'      => 'DESC',
			// Exclude private profiles from public radar (members-only still listed when logged in).
			'meta_query' => is_user_logged_in()
				? array(
					'relation' => 'OR',
					array(
						'key'     => '_apollo_privacy_profile',
						'value'   => 'private',
						'compare' => '!=',
					),
					array(
						'key'     => '_apollo_privacy_profile',
						'compare' => 'NOT EXISTS',
					),
				)
				: array(
					'relation' => 'OR',
					array(
						'key'     => '_apollo_privacy_profile',
						'value'   => 'public',
						'compare' => '=',
					),
					array(
						'key'     => '_apollo_privacy_profile',
						'compare' => 'NOT EXISTS',
					),
				),
		);

		if ( $role ) {
			$args['role'] = $role;
		}

		$user_query = new \WP_User_Query( $args );
		$users      = $user_query->get_results();

		// List-safe public payload (no email/phone).
		$data = array_map(
			function ( $user ) {
				return $this->prepare_user_data( $user, false, true );
			},
			$users
		);

		return new \WP_REST_Response(
			array(
				'users' => $data,
				'total' => $user_query->get_total(),
			),
			200
		);
	}

	/**
	 * Get public profile
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_public_profile( \WP_REST_Request $request ) {
		$username = $request->get_param( 'username' );
		$user     = get_user_by( 'login', $username );

		if ( ! $user ) {
			$user = get_user_by( 'slug', $username );
		}

		if ( ! $user ) {
			return new \WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
		}

		$visible = $this->assert_profile_visible( $user );
		if ( is_wp_error( $visible ) ) {
			return $visible;
		}

		$is_own = get_current_user_id() === (int) $user->ID;

		return new \WP_REST_Response( $this->prepare_user_data( $user, $is_own ), 200 );
	}

	/**
	 * Record profile view
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function record_profile_view( \WP_REST_Request $request ) {
		$username = $request->get_param( 'username' );
		$user     = get_user_by( 'login', $username );

		if ( ! $user ) {
			return new \WP_Error( 'user_not_found', 'User not found', array( 'status' => 404 ) );
		}

		$this->track_profile_view( $user->ID );

		return new \WP_REST_Response( array( 'success' => true ), 200 );
	}
}

/**
 * Get client IP helper
 *
 * @return string
 */
function apollo_get_client_ip(): string {
	$ip_keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );

	foreach ( $ip_keys as $key ) {
		if ( ! empty( $_SERVER[ $key ] ) ) {
			$ip = explode( ',', $_SERVER[ $key ] )[0];
			$ip = trim( $ip );

			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				return $ip;
			}
		}
	}

	return '';
}
