<?php
/**
 * REST Controller para Apollo Statistics.
 *
 * Endpoints (todos admin-only):
 *   GET  /stats/overview
 *   GET  /stats/events
 *   GET  /stats/users
 *   GET  /stats/content
 *   GET  /stats/export
 *
 * @package Apollo\Statistics
 */

declare(strict_types=1);

namespace Apollo\Statistics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class REST_Controller {

	/** @var string Namespace da API. */
	private const NAMESPACE = 'apollo/v1';

	/**
	 * Registra todas as rotas.
	 */
	public function register_routes(): void {

		/* ── GET /stats/overview ── */
		register_rest_route(
			self::NAMESPACE,
			'/stats/overview',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_overview' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => array(
					'days' => array(
						'type'              => 'integer',
						'default'           => 30,
						'minimum'           => 1,
						'maximum'           => 365,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		/* ── GET /stats/events ── */
		register_rest_route(
			self::NAMESPACE,
			'/stats/events',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_events' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => $this->get_period_args(),
			)
		);

		/* ── GET /stats/users ── */
		register_rest_route(
			self::NAMESPACE,
			'/stats/users',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_users' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => $this->get_period_args(),
			)
		);

		/* ── GET /stats/content ── */
		register_rest_route(
			self::NAMESPACE,
			'/stats/content',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_content' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => array_merge(
					$this->get_period_args(),
					array(
						'post_type' => array(
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
							'description'       => 'Filtrar por CPT (event, dj, classified, etc.)',
						),
						'metric'    => array(
							'type'              => 'string',
							'default'           => 'view',
							'sanitize_callback' => 'sanitize_text_field',
							'description'       => 'Tipo de métrica (view, fav, share, wow).',
						),
					)
				),
			)
		);

		/* ── GET /stats/export ── */
		register_rest_route(
			self::NAMESPACE,
			'/stats/export',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export_csv' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => array(
					'type' => array(
						'type'              => 'string',
						'default'           => 'content',
						'enum'              => array( 'events', 'users', 'content' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'days' => array(
						'type'              => 'integer',
						'default'           => 30,
						'minimum'           => 1,
						'maximum'           => 365,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		/* ── GET /stats/health ── */
		register_rest_route(
			self::NAMESPACE,
			'/stats/health',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_health' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		/* ── GET /stats/trend ── */
		register_rest_route(
			self::NAMESPACE,
			'/stats/trend',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_trend' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => array(
					'metric' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'table'  => array(
						'type'              => 'string',
						'default'           => 'content',
						'enum'              => array( 'events', 'users', 'content' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'days'   => array(
						'type'              => 'integer',
						'default'           => 7,
						'minimum'           => 1,
						'maximum'           => 90,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		/* ── GET /stats/chart ── */
		register_rest_route(
			self::NAMESPACE,
			'/stats/chart',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_chart_data' ),
				'permission_callback' => array( $this, 'admin_permission' ),
				'args'                => array(
					'metric' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'table'  => array(
						'type'              => 'string',
						'default'           => 'content',
						'enum'              => array( 'events', 'users', 'content' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'days'   => array(
						'type'              => 'integer',
						'default'           => 30,
						'minimum'           => 1,
						'maximum'           => 365,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/* ───────────────────────── CALLBACKS ───────────────────────── */

	/**
	 * GET /stats/overview — Retorna overview geral.
	 */
	public function get_overview( \WP_REST_Request $request ): \WP_REST_Response {
		$days = $request->get_param( 'days' );

		$overview = apollo_stats_get_overview( $days );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $overview,
			)
		);
	}

	/**
	 * GET /stats/events — Retorna estatísticas de eventos.
	 */
	public function get_events( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$days  = $request->get_param( 'days' );
		$since = wp_date( 'Y-m-d', strtotime( "-{$days} days" ) );
		$table = $wpdb->prefix . 'apollo_stats_events';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.event_id, p.post_title AS event_name,
                SUM(CASE WHEN e.metric_type = 'view'    THEN e.metric_value ELSE 0 END) AS views,
                SUM(CASE WHEN e.metric_type = 'rsvp'    THEN e.metric_value ELSE 0 END) AS rsvps,
                SUM(CASE WHEN e.metric_type = 'fav'     THEN e.metric_value ELSE 0 END) AS favs,
                SUM(CASE WHEN e.metric_type = 'share'   THEN e.metric_value ELSE 0 END) AS shares,
                SUM(CASE WHEN e.metric_type = 'checkin' THEN e.metric_value ELSE 0 END) AS checkins
             FROM {$table} e
             LEFT JOIN {$wpdb->posts} p ON e.event_id = p.ID
             WHERE e.recorded_date >= %s
             GROUP BY e.event_id, p.post_title
             ORDER BY views DESC
             LIMIT 50",
				$since
			),
			ARRAY_A
		);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $rows ?: array(),
				'period'  => array(
					'days'  => $days,
					'since' => $since,
				),
			)
		);
	}

	/**
	 * GET /stats/users — Retorna estatísticas de usuários.
	 */
	public function get_users( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$days  = $request->get_param( 'days' );
		$since = wp_date( 'Y-m-d', strtotime( "-{$days} days" ) );
		$table = $wpdb->prefix . 'apollo_stats_users';

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.user_id, usr.display_name, usr.user_email,
                SUM(CASE WHEN u.metric_type = 'login'        THEN u.metric_value ELSE 0 END) AS logins,
                SUM(CASE WHEN u.metric_type = 'registration' THEN u.metric_value ELSE 0 END) AS registrations,
                SUM(CASE WHEN u.metric_type = 'profile_view' THEN u.metric_value ELSE 0 END) AS profile_views,
                SUM(CASE WHEN u.metric_type = 'action'       THEN u.metric_value ELSE 0 END) AS actions,
                COUNT(DISTINCT u.recorded_date) AS active_days
             FROM {$table} u
             LEFT JOIN {$wpdb->users} usr ON u.user_id = usr.ID
             WHERE u.recorded_date >= %s AND u.user_id > 0
             GROUP BY u.user_id, usr.display_name, usr.user_email
             ORDER BY active_days DESC
             LIMIT 50",
				$since
			),
			ARRAY_A
		);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $rows ?: array(),
				'period'  => array(
					'days'  => $days,
					'since' => $since,
				),
			)
		);
	}

	/**
	 * GET /stats/content — Retorna estatísticas de conteúdo.
	 */
	public function get_content( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;

		$days      = $request->get_param( 'days' );
		$post_type = $request->get_param( 'post_type' );
		$metric    = $request->get_param( 'metric' );
		$since     = wp_date( 'Y-m-d', strtotime( "-{$days} days" ) );
		$table     = $wpdb->prefix . 'apollo_stats_content';

		$where = array( 'c.recorded_date >= %s', 'c.metric_type = %s' );
		$args  = array( $since, $metric );

		if ( ! empty( $post_type ) ) {
			$where[] = 'c.post_type = %s';
			$args[]  = $post_type;
		}

		$where_sql = implode( ' AND ', $where );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.post_id, c.post_type, p.post_title,
                SUM(c.metric_value) AS total
             FROM {$table} c
             LEFT JOIN {$wpdb->posts} p ON c.post_id = p.ID
             WHERE {$where_sql}
             GROUP BY c.post_id, c.post_type, p.post_title
             ORDER BY total DESC
             LIMIT 50",
				...$args
			),
			ARRAY_A
		);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $rows ?: array(),
				'period'  => array(
					'days'  => $days,
					'since' => $since,
				),
				'filter'  => array(
					'post_type' => $post_type,
					'metric'    => $metric,
				),
			)
		);
	}

	/**
	 * GET /stats/export — Exporta dados como CSV.
	 */
	public function export_csv( \WP_REST_Request $request ): \WP_REST_Response {
		$type = $request->get_param( 'type' );
		$days = $request->get_param( 'days' );

		$csv = apollo_stats_export_csv( $type, $days );

		if ( empty( $csv ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Nenhum dado para exportar no período selecionado.',
				),
				404
			);
		}

		// Retorna CSV como download
		$response = new \WP_REST_Response( $csv );
		$response->header( 'Content-Type', 'text/csv; charset=utf-8' );
		$response->header(
			'Content-Disposition',
			sprintf(
				'attachment; filename="apollo-stats-%s-%sd-%s.csv"',
				$type,
				$days,
				wp_date( 'Y-m-d' )
			)
		);

		return $response;
	}

	/**
	 * GET /stats/health — Health score do ecossistema.
	 */
	public function get_health( \WP_REST_Request $request ): \WP_REST_Response {
		$processor = new Metrics_Processor();
		$health    = $processor->get_health_summary();

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $health,
			)
		);
	}

	/**
	 * GET /stats/trend — Tendência de uma métrica específica.
	 */
	public function get_trend( \WP_REST_Request $request ): \WP_REST_Response {
		$metric = $request->get_param( 'metric' );
		$table  = $request->get_param( 'table' );
		$days   = $request->get_param( 'days' );

		$processor = new Metrics_Processor();
		$trend     = $processor->calculate_trend( $metric, $table, $days );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $trend,
			)
		);
	}

	/**
	 * GET /stats/chart — Dados de série temporal para Chart.js.
	 */
	public function get_chart_data( \WP_REST_Request $request ): \WP_REST_Response {
		$metric = $request->get_param( 'metric' );
		$table  = $request->get_param( 'table' );
		$days   = $request->get_param( 'days' );

		$processor = new Metrics_Processor();
		$chart     = $processor->get_time_series( $metric, $table, $days );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $chart,
			)
		);
	}

	/* ───────────────────────── PERMISSIONS ───────────────────────── */

	/**
	 * Permissão: apenas admin.
	 *
	 * @return bool|\WP_Error
	 */
	public function admin_permission(): bool|\WP_Error {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		return new \WP_Error(
			'apollo_stats_forbidden',
			__( 'Acesso restrito a administradores.', 'apollo-statistics' ),
			array( 'status' => 403 )
		);
	}

	/* ───────────────────────── HELPERS ───────────────────────── */

	/**
	 * Arguments de período comuns a vários endpoints.
	 *
	 * @return array
	 */
	private function get_period_args(): array {
		return array(
			'days' => array(
				'type'              => 'integer',
				'default'           => 30,
				'minimum'           => 1,
				'maximum'           => 365,
				'sanitize_callback' => 'absint',
				'description'       => 'Período em dias para o relatório.',
			),
		);
	}
}
