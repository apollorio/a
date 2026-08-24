<?php
/**
 * Funções globais (API pública) do Apollo Statistics
 *
 * @package Apollo\Statistics
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra uma métrica para um evento.
 *
 * @param int    $event_id     ID do evento.
 * @param string $metric_type  Tipo: 'view', 'rsvp', 'fav', 'share', 'checkin'.
 * @param int    $value        Valor da métrica (default 1).
 * @param string $date         Data (Y-m-d). Default: hoje.
 * @return bool
 */
function apollo_stats_record_event( int $event_id, string $metric_type, int $value = 1, string $date = '' ): bool {
	global $wpdb;

	$table = $wpdb->prefix . 'apollo_stats_events';
	$date  = $date ?: current_time( 'Y-m-d' );

	// UPSERT — incrementa se já existir (ON DUPLICATE KEY UPDATE)
	$sql = $wpdb->prepare(
		"INSERT INTO {$table} (event_id, metric_type, metric_value, recorded_date)
         VALUES (%d, %s, %d, %s)
         ON DUPLICATE KEY UPDATE metric_value = metric_value + %d",
		$event_id,
		$metric_type,
		$value,
		$date,
		$value
	);

	return (bool) $wpdb->query( $sql );
}

/**
 * Registra uma métrica para um usuário.
 *
 * @param int    $user_id      ID do usuário (0 = global).
 * @param string $metric_type  Tipo: 'registration', 'login', 'profile_view', 'action'.
 * @param int    $value        Valor.
 * @param string $date         Data.
 * @return bool
 */
function apollo_stats_record_user( int $user_id, string $metric_type, int $value = 1, string $date = '' ): bool {
	global $wpdb;

	$table = $wpdb->prefix . 'apollo_stats_users';
	$date  = $date ?: current_time( 'Y-m-d' );

	$sql = $wpdb->prepare(
		"INSERT INTO {$table} (user_id, metric_type, metric_value, recorded_date)
         VALUES (%d, %s, %d, %s)
         ON DUPLICATE KEY UPDATE metric_value = metric_value + %d",
		$user_id,
		$metric_type,
		$value,
		$date,
		$value
	);

	return (bool) $wpdb->query( $sql );
}

/**
 * Registra uma métrica para um conteúdo (CPT genérico).
 *
 * @param int    $post_id      ID do post.
 * @param string $metric_type  Tipo: 'view', 'fav', 'wow', 'share'.
 * @param int    $value        Valor.
 * @param string $date         Data.
 * @return bool
 */
function apollo_stats_record_content( int $post_id, string $metric_type, int $value = 1, string $date = '' ): bool {
	global $wpdb;

	$table     = $wpdb->prefix . 'apollo_stats_content';
	$date      = $date ?: current_time( 'Y-m-d' );
	$post_type = get_post_type( $post_id ) ?: 'unknown';

	$sql = $wpdb->prepare(
		"INSERT INTO {$table} (post_id, post_type, metric_type, metric_value, recorded_date)
         VALUES (%d, %s, %s, %d, %s)
         ON DUPLICATE KEY UPDATE metric_value = metric_value + %d",
		$post_id,
		$post_type,
		$metric_type,
		$value,
		$date,
		$value
	);

	return (bool) $wpdb->query( $sql );
}

/**
 * Retorna overview geral de estatísticas.
 *
 * @param int $days  Período em dias (default 30).
 * @return array
 */
function apollo_stats_get_overview( int $days = 30 ): array {
	global $wpdb;

	$since = wp_date( 'Y-m-d', strtotime( "-{$days} days" ) );

	// Estatísticas de conteúdo
	$content_table = $wpdb->prefix . 'apollo_stats_content';
	$events_table  = $wpdb->prefix . 'apollo_stats_events';
	$users_table   = $wpdb->prefix . 'apollo_stats_users';

	$overview = array();

	// Total de views no período
	$overview['total_views'] = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(metric_value), 0) FROM {$content_table}
         WHERE metric_type = 'view' AND recorded_date >= %s",
			$since
		)
	);

	// Total de favoritos no período
	$overview['total_favs'] = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(metric_value), 0) FROM {$content_table}
         WHERE metric_type = 'fav' AND recorded_date >= %s",
			$since
		)
	);

	// Novos usuários no período
	$overview['new_users'] = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(metric_value), 0) FROM {$users_table}
         WHERE metric_type = 'registration' AND recorded_date >= %s",
			$since
		)
	);

	// Logins no período
	$overview['total_logins'] = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(metric_value), 0) FROM {$users_table}
         WHERE metric_type = 'login' AND recorded_date >= %s",
			$since
		)
	);

	// Eventos criados no período
	$overview['events_created'] = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts}
         WHERE post_type = 'event' AND post_status = 'publish'
         AND post_date >= %s",
			$since . ' 00:00:00'
		)
	);

	// Trend diário de views
	$overview['daily_views'] = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT recorded_date AS day, SUM(metric_value) AS total
         FROM {$content_table}
         WHERE metric_type = 'view' AND recorded_date >= %s
         GROUP BY recorded_date
         ORDER BY recorded_date ASC",
			$since
		),
		ARRAY_A
	) ?: array();

	// Top conteúdos por views
	$overview['top_content'] = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT c.post_id, c.post_type, p.post_title, SUM(c.metric_value) AS views
         FROM {$content_table} c
         INNER JOIN {$wpdb->posts} p ON c.post_id = p.ID
         WHERE c.metric_type = 'view' AND c.recorded_date >= %s
         GROUP BY c.post_id, c.post_type, p.post_title
         ORDER BY views DESC
         LIMIT 10",
			$since
		),
		ARRAY_A
	) ?: array();

	$overview['period_days'] = $days;
	$overview['since']       = $since;

	return $overview;
}

/**
 * Exporta dados de estatísticas como CSV.
 *
 * @param string $type    Tipo: 'events', 'users', 'content'.
 * @param int    $days    Período em dias.
 * @return string         CSV formatado.
 */
function apollo_stats_export_csv( string $type = 'content', int $days = 30 ): string {
	global $wpdb;

	$since = wp_date( 'Y-m-d', strtotime( "-{$days} days" ) );

	switch ( $type ) {
		case 'events':
			$table = $wpdb->prefix . 'apollo_stats_events';
			$rows  = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT e.event_id, p.post_title AS event_name, e.metric_type, e.metric_value, e.recorded_date
                 FROM {$table} e
                 LEFT JOIN {$wpdb->posts} p ON e.event_id = p.ID
                 WHERE e.recorded_date >= %s
                 ORDER BY e.recorded_date DESC",
					$since
				),
				ARRAY_A
			);
			break;

		case 'users':
			$table = $wpdb->prefix . 'apollo_stats_users';
			$rows  = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT u.user_id, usr.display_name, u.metric_type, u.metric_value, u.recorded_date
                 FROM {$table} u
                 LEFT JOIN {$wpdb->users} usr ON u.user_id = usr.ID
                 WHERE u.recorded_date >= %s
                 ORDER BY u.recorded_date DESC",
					$since
				),
				ARRAY_A
			);
			break;

		case 'content':
		default:
			$table = $wpdb->prefix . 'apollo_stats_content';
			$rows  = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT c.post_id, p.post_title, c.post_type, c.metric_type, c.metric_value, c.recorded_date
                 FROM {$table} c
                 LEFT JOIN {$wpdb->posts} p ON c.post_id = p.ID
                 WHERE c.recorded_date >= %s
                 ORDER BY c.recorded_date DESC",
					$since
				),
				ARRAY_A
			);
			break;
	}

	if ( empty( $rows ) ) {
		return '';
	}

	// Monta CSV
	$csv = '';

	// Header
	$csv .= implode( ',', array_keys( $rows[0] ) ) . "\n";

	// Rows
	foreach ( $rows as $row ) {
		$csv .= implode(
			',',
			array_map(
				function ( $val ) {
					return '"' . str_replace( '"', '""', (string) $val ) . '"';
				},
				$row
			)
		) . "\n";
	}

	return $csv;
}
