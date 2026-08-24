<?php

/**
 * Apollo Admin — User Preferences.
 *
 * Per-user admin preferences stored in user meta. Each user can customize
 * their admin experience independently.
 * Adapted from UiPress Lite UserPreferences class.
 *
 * @package Apollo\Admin
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User Preferences.
 *
 * @since 1.1.0
 */
final class UserPreferences {


	/**
	 * Meta key for storing user preferences.
	 */
	private const META_KEY = '_apollo_admin_prefs';

	/** @var UserPreferences|null */
	private static ?UserPreferences $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return UserPreferences
	 */
	public static function get_instance(): UserPreferences {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Get a user preference.
	 *
	 * Adapted from UiPress UserPreferences::get().
	 *
	 * @param string|null $key     Preference key (null returns all prefs).
	 * @param int|null    $user_id User ID (defaults to current user).
	 * @return mixed
	 */
	public static function get( ?string $key = null, ?int $user_id = null ): mixed {
		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) {
			return null;
		}

		$prefs = get_user_meta( $user_id, self::META_KEY, true );
		if ( ! is_array( $prefs ) ) {
			$prefs = array();
		}

		if ( null === $key ) {
			return $prefs;
		}

		return $prefs[ $key ] ?? null;
	}

	/**
	 * Set a user preference.
	 *
	 * Adapted from UiPress UserPreferences::update().
	 *
	 * @param string   $key     Preference key.
	 * @param mixed    $value   Preference value.
	 * @param int|null $user_id User ID (defaults to current user).
	 * @return bool
	 */
	public static function set( string $key, mixed $value, ?int $user_id = null ): bool {
		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$prefs = get_user_meta( $user_id, self::META_KEY, true );
		if ( ! is_array( $prefs ) ) {
			$prefs = array();
		}

		$prefs[ $key ] = $value;

		return (bool) update_user_meta( $user_id, self::META_KEY, $prefs );
	}

	/**
	 * Delete a specific user preference.
	 *
	 * @param string   $key     Preference key.
	 * @param int|null $user_id User ID (defaults to current user).
	 * @return bool
	 */
	public static function delete( string $key, ?int $user_id = null ): bool {
		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		$prefs = get_user_meta( $user_id, self::META_KEY, true );
		if ( ! is_array( $prefs ) ) {
			return true;
		}

		unset( $prefs[ $key ] );

		return (bool) update_user_meta( $user_id, self::META_KEY, $prefs );
	}

	/**
	 * Replace all user preferences.
	 *
	 * @param array    $prefs   Complete preferences array.
	 * @param int|null $user_id User ID (defaults to current user).
	 * @return bool
	 */
	public static function replace_all( array $prefs, ?int $user_id = null ): bool {
		$user_id = $user_id ?: get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}

		return (bool) update_user_meta( $user_id, self::META_KEY, $prefs );
	}

	/**
	 * Register REST endpoints.
	 */
	public function register_rest_routes(): void {
		$namespace = APOLLO_ADMIN_REST_NAMESPACE;

		// GET current user preferences.
		register_rest_route(
			$namespace,
			'/preferences',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_get_preferences' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		// POST update current user preferences.
		register_rest_route(
			$namespace,
			'/preferences',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_update_preferences' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		// DELETE a specific preference.
		register_rest_route(
			$namespace,
			'/preferences/(?P<key>[a-z0-9_-]+)',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'rest_delete_preference' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
				'args'                => array(
					'key' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
				),
			)
		);
	}

	/**
	 * REST callback — get user preferences.
	 *
	 * @return \WP_REST_Response
	 */
	public function rest_get_preferences(): \WP_REST_Response {
		$prefs = self::get();

		return new \WP_REST_Response(
			array(
				'user_id'     => get_current_user_id(),
				'preferences' => $prefs ?: new \stdClass(),
			),
			200
		);
	}

	/**
	 * REST callback — update user preferences.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function rest_update_preferences( \WP_REST_Request $request ): \WP_REST_Response {
		$body = $request->get_json_params();

		if ( ! is_array( $body ) || empty( $body ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Dados inválidos.', 'apollo-admin' ),
				),
				400
			);
		}

		// Sanitize keys and values.
		$sanitized = array();
		foreach ( $body as $key => $value ) {
			$clean_key = sanitize_key( $key );
			if ( is_string( $value ) ) {
				$sanitized[ $clean_key ] = sanitize_text_field( $value );
			} elseif ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
				$sanitized[ $clean_key ] = $value;
			} elseif ( is_array( $value ) ) {
				// Allow arrays for complex prefs (e.g., widget order).
				$sanitized[ $clean_key ] = map_deep( $value, 'sanitize_text_field' );
			}
		}

		// Merge with existing.
		$current = self::get() ?: array();
		$merged  = array_merge( $current, $sanitized );
		self::replace_all( $merged );

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'message'     => __( 'Preferências salvas.', 'apollo-admin' ),
				'preferences' => $merged,
			),
			200
		);
	}

	/**
	 * REST callback — delete a preference.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function rest_delete_preference( \WP_REST_Request $request ): \WP_REST_Response {
		$key = $request->get_param( 'key' );
		self::delete( $key );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => sprintf(
					/* translators: %s: preference key */
					__( 'Preferência "%s" removida.', 'apollo-admin' ),
					$key
				),
			),
			200
		);
	}
}
