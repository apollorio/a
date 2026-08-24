<?php
/**
 * Processador de métricas — normaliza, agrega e calcula tendências.
 *
 * Consome dados brutos das 3 tabelas e produz insights processados
 * para consumo pela classe Reports e pela REST API.
 *
 * @package Apollo\Statistics
 */

declare(strict_types=1);

namespace Apollo\Statistics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Metrics Processor — normaliza, agrega e calcula tendências.
 */
final class Metrics_Processor {

	/**
	 * Construtor.
	 */
	public function __construct() {
		// Hooks registrados no init() para manter padrão Apollo.
	}

	/**
	 * Inicializa hooks de processamento.
	 */
	public function init(): void {
		// Após coleta diária, processa aggregados.
		add_action( 'apollo/statistics/daily_collected', array( $this, 'process_daily_aggregates' ) );

		// Rotação de dados antigos (90 dias).
		add_action( 'apollo_stats_daily_collect', array( $this, 'rotate_old_data' ), 99 );
	}

	/* ───────────────────────── AGREGADOS DIÁRIOS ───────────────────────── */

	/**
	 * Processa agregados após a coleta diária.
	 *
	 * @param string $date Data da coleta.
	 */
	public function process_daily_aggregates( string $date ): void {
		$this->compute_content_rankings( $date );
		$this->compute_user_activity_score( $date );
		$this->compute_event_engagement( $date );

		// Cache do overview para 12h.
		delete_transient( 'apollo_stats_overview_7' );
		delete_transient( 'apollo_stats_overview_30' );
		delete_transient( 'apollo_stats_overview_90' );
	}

	/* ───────────────────────── RANKINGS DE CONTEÚDO ───────────────────────── */

	/**
	 * Calcula rankings de conteúdo mais popular.
	 *
	 * @param string $date Data de referência.
	 */
	private function compute_content_rankings( string $date ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_stats_content';
		$since = wp_date( 'Y-m-d', strtotime( '-7 days', strtotime( $date ) ) );

		// Score composto: views*1 + favs*3 + wows*2 + shares*5.
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rankings = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT post_id, post_type,
                SUM(CASE WHEN metric_type = 'view' THEN metric_value ELSE 0 END) AS views,
                SUM(CASE WHEN metric_type = 'fav'  THEN metric_value ELSE 0 END) AS favs,
                SUM(CASE WHEN metric_type = 'wow'  THEN metric_value ELSE 0 END) AS wows,
                SUM(CASE WHEN metric_type = 'share' THEN metric_value ELSE 0 END) AS shares,
                (
                    SUM(CASE WHEN metric_type = 'view' THEN metric_value ELSE 0 END) * 1 +
                    SUM(CASE WHEN metric_type = 'fav'  THEN metric_value ELSE 0 END) * 3 +
                    SUM(CASE WHEN metric_type = 'wow'  THEN metric_value ELSE 0 END) * 2 +
                    SUM(CASE WHEN metric_type = 'share' THEN metric_value ELSE 0 END) * 5
                ) AS score
             FROM {$table}
             WHERE recorded_date >= %s
             GROUP BY post_id, post_type
             HAVING score > 0
             ORDER BY score DESC
             LIMIT 50",
				$since
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $rankings ) ) {
			set_transient( 'apollo_stats_content_rankings', $rankings, DAY_IN_SECONDS );
		}
	}

	/* ───────────────────────── ATIVIDADE DE USUÁRIOS ───────────────────────── */

	/**
	 * Score de atividade por usuário (logins + ações).
	 *
	 * @param string $date Data de referência.
	 */
	private function compute_user_activity_score( string $date ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_stats_users';
		$since = wp_date( 'Y-m-d', strtotime( '-30 days', strtotime( $date ) ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$activity = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT user_id,
                SUM(CASE WHEN metric_type = 'login' THEN metric_value ELSE 0 END) AS logins,
                SUM(CASE WHEN metric_type = 'profile_view' THEN metric_value ELSE 0 END) AS profile_views,
                SUM(CASE WHEN metric_type = 'action' THEN metric_value ELSE 0 END) AS actions,
                COUNT(DISTINCT recorded_date) AS active_days
             FROM {$table}
             WHERE recorded_date >= %s AND user_id > 0
             GROUP BY user_id
             HAVING logins > 0
             ORDER BY active_days DESC
             LIMIT 100",
				$since
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $activity ) ) {
			set_transient( 'apollo_stats_user_activity', $activity, DAY_IN_SECONDS );
		}
	}

	/* ───────────────────────── ENGAGEMENT DE EVENTOS ───────────────────────── */

	/**
	 * Métricas de engagement por evento.
	 *
	 * @param string $date Data de referência.
	 */
	private function compute_event_engagement( string $date ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_stats_events';
		$since = wp_date( 'Y-m-d', strtotime( '-30 days', strtotime( $date ) ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$engagement = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT event_id,
                SUM(CASE WHEN metric_type = 'view'    THEN metric_value ELSE 0 END) AS views,
                SUM(CASE WHEN metric_type = 'rsvp'    THEN metric_value ELSE 0 END) AS rsvps,
                SUM(CASE WHEN metric_type = 'fav'     THEN metric_value ELSE 0 END) AS favs,
                SUM(CASE WHEN metric_type = 'share'   THEN metric_value ELSE 0 END) AS shares,
                SUM(CASE WHEN metric_type = 'checkin' THEN metric_value ELSE 0 END) AS checkins
             FROM {$table}
             WHERE recorded_date >= %s
             GROUP BY event_id
             ORDER BY views DESC
             LIMIT 50",
				$since
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! empty( $engagement ) ) {
			set_transient( 'apollo_stats_event_engagement', $engagement, DAY_IN_SECONDS );
		}
	}

	/* ───────────────────────── TENDÊNCIAS ───────────────────────── */

	/**
	 * Calcula tendência (growth %) entre dois períodos.
	 *
	 * @param string $metric_type Tipo de métrica.
	 * @param string $table_type  'content', 'events' ou 'users'.
	 * @param int    $days        Período de comparação.
	 * @return array ['current' => int, 'previous' => int, 'trend' => float, 'direction' => string]
	 */
	public function calculate_trend( string $metric_type, string $table_type = 'content', int $days = 7 ): array {
		global $wpdb;

		$table_map = array(
			'content' => $wpdb->prefix . 'apollo_stats_content',
			'events'  => $wpdb->prefix . 'apollo_stats_events',
			'users'   => $wpdb->prefix . 'apollo_stats_users',
		);

		$table = $table_map[ $table_type ] ?? $table_map['content'];
		$now   = current_time( 'Y-m-d' );

		$current_since  = wp_date( 'Y-m-d', strtotime( "-{$days} days" ) );
		$previous_since = wp_date( 'Y-m-d', strtotime( '-' . ( $days * 2 ) . ' days' ) );

		// Período atual.
		$current = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COALESCE(SUM(metric_value), 0) FROM {$table}
             WHERE metric_type = %s AND recorded_date >= %s",
				$metric_type,
				$current_since
			)
		);

		// Período anterior.
		$previous = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COALESCE(SUM(metric_value), 0) FROM {$table}
             WHERE metric_type = %s AND recorded_date >= %s AND recorded_date < %s",
				$metric_type,
				$previous_since,
				$current_since
			)
		);

		// Calcula tendência.
		if ( $previous > 0 ) {
			$trend = round( ( ( $current - $previous ) / $previous ) * 100, 1 );
		} elseif ( $current > 0 ) {
			$trend = 100.0;
		} else {
			$trend = 0.0;
		}

		$direction = $trend > 0 ? 'up' : ( $trend < 0 ? 'down' : 'stable' );

		return array(
			'current'   => $current,
			'previous'  => $previous,
			'trend'     => $trend,
			'direction' => $direction,
		);
	}

	/* ───────────────────────── SÉRIES TEMPORAIS ───────────────────────── */

	/**
	 * Retorna série temporal para gráficos (Chart.js format).
	 *
	 * @param string $metric_type  Tipo de métrica.
	 * @param string $table_type   'content', 'events' ou 'users'.
	 * @param int    $days         Período.
	 * @return array ['labels' => [...], 'datasets' => [...]]
	 */
	public function get_time_series( string $metric_type, string $table_type = 'content', int $days = 30 ): array {
		global $wpdb;

		$table_map = array(
			'content' => $wpdb->prefix . 'apollo_stats_content',
			'events'  => $wpdb->prefix . 'apollo_stats_events',
			'users'   => $wpdb->prefix . 'apollo_stats_users',
		);

		$table = $table_map[ $table_type ] ?? $table_map['content'];
		$since = wp_date( 'Y-m-d', strtotime( "-{$days} days" ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT recorded_date AS day, SUM(metric_value) AS total FROM {$table} WHERE metric_type = %s AND recorded_date >= %s GROUP BY recorded_date ORDER BY recorded_date ASC",
				$metric_type,
				$since
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Preenche dias sem dados com 0.
		$data_map = array();
		foreach ( $rows as $row ) {
			$data_map[ $row['day'] ] = (int) $row['total'];
		}

		$labels = array();
		$values = array();
		$period = new \DatePeriod(
			new \DateTime( $since ),
			new \DateInterval( 'P1D' ),
			new \DateTime( current_time( 'Y-m-d' ) . ' +1 day' )
		);

		foreach ( $period as $date ) {
			$key      = $date->format( 'Y-m-d' );
			$labels[] = $date->format( 'd/m' );
			$values[] = $data_map[ $key ] ?? 0;
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => ucfirst( $metric_type ),
					'data'            => $values,
					'borderColor'     => '#e91e63',
					'backgroundColor' => 'rgba(233, 30, 99, 0.1)',
					'fill'            => true,
					'tension'         => 0.3,
				),
			),
		);
	}

	/* ───────────────────────── SUMÁRIO RÁPIDO ───────────────────────── */

	/**
	 * Gera sumário rápido de health do ecossistema.
	 *
	 * @return array
	 */
	public function get_health_summary(): array {
		$views_trend  = $this->calculate_trend( 'view', 'content', 7 );
		$favs_trend   = $this->calculate_trend( 'fav', 'content', 7 );
		$logins_trend = $this->calculate_trend( 'login', 'users', 7 );
		$events_trend = $this->calculate_trend( 'view', 'events', 7 );

		// Health score: média das tendências (normalizada).
		$trends = array(
			$views_trend['trend'],
			$favs_trend['trend'],
			$logins_trend['trend'],
			$events_trend['trend'],
		);

		$avg_trend = array_sum( $trends ) / max( count( $trends ), 1 );

		// Score de 0 a 100.
		$health_score = min( 100, max( 0, 50 + $avg_trend ) );

		return array(
			'health_score' => round( $health_score, 1 ),
			'views'        => $views_trend,
			'favs'         => $favs_trend,
			'logins'       => $logins_trend,
			'events'       => $events_trend,
			'status'       => $health_score >= 60 ? 'healthy' : ( $health_score >= 30 ? 'warning' : 'critical' ),
		);
	}

	/* ───────────────────────── ROTAÇÃO DE DADOS ───────────────────────── */

	/**
	 * Remove dados com mais de 90 dias.
	 *
	 * Filtrável via `apollo/statistics/retention_days`.
	 *
	 * @return void
	 */
	public function rotate_old_data(): void {
		global $wpdb;

		$retention_days = (int) apply_filters( 'apollo/statistics/retention_days', 90 ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		$cutoff         = wp_date( 'Y-m-d', strtotime( "-{$retention_days} days" ) );

		$tables = array(
			$wpdb->prefix . 'apollo_stats_events',
			$wpdb->prefix . 'apollo_stats_users',
			$wpdb->prefix . 'apollo_stats_content',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"DELETE FROM {$table} WHERE recorded_date < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$cutoff
				)
			);
		}
	}

	/* ───────────────────────── COMPARATIVOS ───────────────────────── */

	/**
	 * Compara métricas entre dois post_types.
	 *
	 * @param string $type_a     CPT A.
	 * @param string $type_b     CPT B.
	 * @param string $metric     Métrica para comparar.
	 * @param int    $days       Período.
	 * @return array
	 */
	public function compare_cpt_metrics( string $type_a, string $type_b, string $metric = 'view', int $days = 30 ): array {
		global $wpdb;

		$table = $wpdb->prefix . 'apollo_stats_content';
		$since = wp_date( 'Y-m-d', strtotime( "-{$days} days" ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result_a = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COALESCE(SUM(metric_value), 0) FROM {$table} WHERE post_type = %s AND metric_type = %s AND recorded_date >= %s",
				$type_a,
				$metric,
				$since
			)
		);

		$result_b = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COALESCE(SUM(metric_value), 0) FROM {$table} WHERE post_type = %s AND metric_type = %s AND recorded_date >= %s",
				$type_b,
				$metric,
				$since
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			$type_a => $result_a,
			$type_b => $result_b,
			'diff'  => $result_a - $result_b,
			'ratio' => $result_b > 0 ? round( $result_a / $result_b, 2 ) : null,
		);
	}
}
