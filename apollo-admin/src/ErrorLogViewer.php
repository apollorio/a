<?php

/**
 * Apollo Admin — Error Log Viewer.
 *
 * REST endpoint to view and search PHP error logs with pagination.
 * Adapted from UiPress Lite ErrorLog class.
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
 * Error Log Viewer.
 *
 * @since 1.1.0
 */
final class ErrorLogViewer {


	/** @var ErrorLogViewer|null */
	private static ?ErrorLogViewer $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return ErrorLogViewer
	 */
	public static function get_instance(): ErrorLogViewer {
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
	 * Register REST endpoints.
	 */
	public function register_rest_routes(): void {
		$namespace = APOLLO_ADMIN_REST_NAMESPACE;

		// GET error log entries.
		register_rest_route(
			$namespace,
			'/errorlog',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_error_items' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'page'     => array(
						'default'           => 1,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'per_page' => array(
						'default'           => 20,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'search'   => array(
						'default'           => '',
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// DELETE clear error log.
		register_rest_route(
			$namespace,
			'/errorlog',
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'clear_error_log' ),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * REST callback — get error log entries.
	 *
	 * Adapted from UiPress ErrorLog::get_error_items().
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_error_items( \WP_REST_Request $request ) {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$search   = $request->get_param( 'search' );

		$logdir = $this->get_log_directory();

		if ( is_wp_error( $logdir ) ) {
			return $logdir;
		}

		$errors = $this->prepare_errors( $logdir, $per_page, 'desc', $search, $page );

		$response = new \WP_REST_Response( $errors['errors'] );
		$response->header( 'X-WP-Total', (string) $errors['totalFound'] );
		$response->header( 'X-WP-TotalPages', (string) $errors['totalPages'] );

		return $response;
	}

	/**
	 * REST callback — clear error log.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function clear_error_log() {
		$logdir = $this->get_log_directory();

		if ( is_wp_error( $logdir ) ) {
			return $logdir;
		}

		// Truncate the log file.
		$handle = fopen( $logdir, 'w' );
		if ( $handle ) {
			fclose( $handle );
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Log de erros limpo.', 'apollo-admin' ),
				),
				200
			);
		}

		return new \WP_Error(
			'apollo_errorlog_clear_failed',
			__( 'Não foi possível limpar o log.', 'apollo-admin' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Get the error log file path.
	 *
	 * Adapted from UiPress ErrorLog::get_log_directory().
	 *
	 * @return string|\WP_Error
	 */
	private function get_log_directory() {
		// First check WP_DEBUG_LOG constant.
		if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) && file_exists( WP_DEBUG_LOG ) ) {
			return WP_DEBUG_LOG;
		}

		// Check ini setting.
		$logdir = ini_get( 'error_log' );

		if ( is_string( $logdir ) && ! empty( $logdir ) && file_exists( $logdir ) ) {
			return $logdir;
		}

		// Try WP default location.
		$wp_debug_log = WP_CONTENT_DIR . '/debug.log';
		if ( file_exists( $wp_debug_log ) ) {
			return $wp_debug_log;
		}

		return new \WP_Error(
			'apollo_errorlog_not_found',
			__( 'Arquivo de log de erros não encontrado.', 'apollo-admin' ),
			array( 'status' => 404 )
		);
	}

	/**
	 * Parse error log and return paginated results.
	 *
	 * Adapted from UiPress ErrorLog::prepare_errors().
	 *
	 * @param string $logdir   Path to the error log file.
	 * @param int    $per_page Items per page.
	 * @param string $order    Sort order (asc|desc).
	 * @param string $search   Search filter.
	 * @param int    $page     Current page.
	 * @return array
	 */
	private function prepare_errors( string $logdir, int $per_page, string $order, string $search, int $page ): array {
		$errors = array();

		// Read file content safely with size limit (5MB max).
		$max_size = 5 * 1024 * 1024;
		$filesize = filesize( $logdir );

		if ( $filesize > $max_size ) {
			// Read only the last 5MB.
			$handle = fopen( $logdir, 'r' );
			fseek( $handle, -$max_size, SEEK_END );
			$content = fread( $handle, $max_size );
			fclose( $handle );
		} else {
			$content = file_get_contents( $logdir );
		}

		if ( empty( $content ) ) {
			return array(
				'errors'     => array(),
				'totalFound' => 0,
				'totalPages' => 0,
			);
		}

		// Split into lines and parse.
		$lines = explode( "\n", $content );

		// Parse log lines into structured entries.
		$parsed  = array();
		$current = '';

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			// New log entry starts with a date.
			if ( preg_match( '/^\[(\d{2}-\w{3}-\d{4}\s+\d{2}:\d{2}:\d{2})\s+\w+\]/', $line ) ) {
				if ( ! empty( $current ) ) {
					$parsed[] = $current;
				}
				$current = $line;
			} else {
				$current .= "\n" . $line;
			}
		}
		if ( ! empty( $current ) ) {
			$parsed[] = $current;
		}

		// Apply search filter.
		if ( ! empty( $search ) ) {
			$search_lower = mb_strtolower( $search );
			$parsed       = array_filter(
				$parsed,
				function ( $entry ) use ( $search_lower ) {
					return str_contains( mb_strtolower( $entry ), $search_lower );
				}
			);
			$parsed       = array_values( $parsed );
		}

		// Sort.
		if ( $order === 'desc' ) {
			$parsed = array_reverse( $parsed );
		}

		// Paginate.
		$total_found = count( $parsed );
		$total_pages = (int) ceil( $total_found / $per_page );
		$offset      = ( $page - 1 ) * $per_page;
		$page_items  = array_slice( $parsed, $offset, $per_page );

		// Format entries.
		foreach ( $page_items as $entry ) {
			$type = 'error';
			$date = '';

			if ( preg_match( '/^\[(\d{2}-\w{3}-\d{4}\s+\d{2}:\d{2}:\d{2})\s+\w+\]/', $entry, $m ) ) {
				$date = $m[1];
			}

			if ( stripos( $entry, 'warning' ) !== false ) {
				$type = 'warning';
			} elseif ( stripos( $entry, 'notice' ) !== false ) {
				$type = 'notice';
			} elseif ( stripos( $entry, 'deprecated' ) !== false ) {
				$type = 'deprecated';
			}

			$errors[] = array(
				'date'    => $date,
				'type'    => $type,
				'message' => esc_html( $entry ),
			);
		}

		return array(
			'errors'     => $errors,
			'totalFound' => $total_found,
			'totalPages' => $total_pages,
		);
	}
}
