<?php
/**
 * Statistics Merge — O "Canhão" de Métricas
 *
 * Ponte arquitetural entre apollo-fav e o futuro apollo-statistics.
 * Extrai e normaliza dados de múltiplas fontes para consolidação de métricas:
 *
 * 1. WP Statistics — Views/Visitors base
 * 2. WP Events Manager — Contagem de RSVP e views de evento
 * 3. UsersWP / Apollo Users — Views de perfil
 * 4. BuddyPress / Apollo Social — Atividade social / Engajamento
 * 5. UIPress Lite — Preparação de dados para gráficos visuais
 *
 * NOTA: Este módulo é uma BRIDGE. Ele provê a interface para que
 * apollo-statistics possa consumir dados unificados.
 *
 * @package Apollo\Fav
 */

declare(strict_types=1);

namespace Apollo\Fav;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Statistics_Merge {

	/**
	 * Inicializa o merge de estatísticas.
	 */
	public function init(): void {
		// Registra os providers de dados como filtros que apollo-statistics pode consumir
		add_filter( 'apollo/statistics/data_providers', array( $this, 'register_providers' ) );

		// Endpoint REST para dados agregados de favoritos (consumido por apollo-statistics)
		add_filter( 'apollo/statistics/fav_data', array( $this, 'get_fav_statistics' ) );

		// Hook para apollo-statistics solicitar dados normalizados
		add_action( 'apollo/statistics/collect', array( $this, 'collect_all_data' ) );

		// Cron para pré-calcular estatísticas diárias
		if ( ! wp_next_scheduled( 'apollo_fav_daily_stats' ) ) {
			wp_schedule_event( time(), 'daily', 'apollo_fav_daily_stats' );
		}
		add_action( 'apollo_fav_daily_stats', array( $this, 'calculate_daily_stats' ) );
	}

	/**
	 * Registra os providers de dados disponíveis.
	 * Apollo-statistics chama este filtro para saber de quais fontes pode extrair dados.
	 *
	 * @param array $providers  Providers já registrados.
	 * @return array             Com os providers do apollo-fav adicionados.
	 */
	public function register_providers( array $providers ): array {
		$providers['fav'] = array(
			'name'        => 'Apollo Fav',
			'description' => 'Dados de favoritos do sistema Apollo',
			'methods'     => array(
				'get_fav_statistics',
				'get_most_faved_content',
				'get_fav_trends',
				'get_user_engagement',
			),
			'available'   => true,
		);

		$providers['wp_statistics'] = array(
			'name'        => 'WP Statistics',
			'description' => 'Views e visitors base do site',
			'methods'     => array(
				'get_page_views',
				'get_visitors_count',
				'get_top_pages',
				'get_referrers',
			),
			'available'   => $this->is_wp_statistics_active(),
		);

		$providers['events_manager'] = array(
			'name'        => 'WP Events Manager',
			'description' => 'RSVP counts e views de eventos',
			'methods'     => array(
				'get_events_manager_stats',
				'get_rsvp_counts',
				'get_event_views',
			),
			'available'   => $this->is_events_manager_active(),
		);

		$providers['users'] = array(
			'name'        => 'Apollo Users',
			'description' => 'Views de perfil e atividade de usuários',
			'methods'     => array(
				'get_userswp_views',
				'get_profile_view_stats',
				'get_user_activity_stats',
			),
			'available'   => $this->is_apollo_users_active(),
		);

		$providers['social'] = array(
			'name'        => 'Apollo Social / BuddyPress',
			'description' => 'Atividade social e métricas de engajamento',
			'methods'     => array(
				'get_social_engagement',
				'get_activity_feed_stats',
				'get_follow_growth',
			),
			'available'   => $this->is_social_active(),
		);

		$providers['uipress'] = array(
			'name'        => 'UIPress Lite',
			'description' => 'Dados formatados para gráficos visuais',
			'methods'     => array(
				'get_chart_data',
				'get_overview_widgets',
			),
			'available'   => $this->is_uipress_active(),
		);

		return $providers;
	}

	// ═══════════════════════════════════════════════════════════════
	// FONTE 1: DADOS DE FAVORITOS (NATIVO)
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Retorna estatísticas gerais do sistema de favoritos.
	 *
	 * @param array $data  Dados existentes.
	 * @return array        Dados com estatísticas de favoritos.
	 */
	public function get_fav_statistics( array $data = array() ): array {
		global $wpdb;

		$table = $wpdb->prefix . APOLLO_FAV_TABLE;

		// Total de favoritos no sistema (%i = safe table identifier, WP 6.2+)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['total_favs'] = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );

		// Total de usuários que favoritaram algo
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['unique_users'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(DISTINCT user_id) FROM %i', $table )
		);

		// Total de posts únicos favoritados
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['unique_posts'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(DISTINCT post_id) FROM %i', $table )
		);

		// Distribuição por tipo de CPT
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['by_type'] = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT post_type, COUNT(*) AS total, COUNT(DISTINCT user_id) AS unique_users, COUNT(DISTINCT post_id) AS unique_posts
             FROM %i
             GROUP BY post_type
             ORDER BY total DESC',
				$table
			),
			ARRAY_A
		) ?: array();

		// Favoritos nos últimos 7 dias
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['last_7_days'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
				$table
			)
		);

		// Favoritos nos últimos 30 dias
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['last_30_days'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
				$table
			)
		);

		// Top 10 posts mais favoritados
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$data['top_posts'] = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.post_id, f.post_type, p.post_title, COUNT(DISTINCT f.user_id) AS fav_count
             FROM %i f
             INNER JOIN {$wpdb->posts} p ON f.post_id = p.ID
             WHERE p.post_status = 'publish'
             GROUP BY f.post_id, f.post_type, p.post_title
             ORDER BY fav_count DESC
             LIMIT 10",
				$table
			),
			ARRAY_A
		) ?: array();

		// Top 10 usuários com mais favoritos (os mais engajados)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$data['top_users'] = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.user_id, u.display_name, COUNT(*) AS fav_count
             FROM %i f
             INNER JOIN {$wpdb->users} u ON f.user_id = u.ID
             GROUP BY f.user_id, u.display_name
             ORDER BY fav_count DESC
             LIMIT 10",
				$table
			),
			ARRAY_A
		) ?: array();

		// Tendência diária (últimos 30 dias)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['daily_trend'] = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM %i
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC',
				$table
			),
			ARRAY_A
		) ?: array();

		return $data;
	}

	// ═══════════════════════════════════════════════════════════════
	// FONTE 2: WP STATISTICS (Views/Visitors)
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Extrai dados do WP Statistics.
	 * Obtém page views, visitors e top pages.
	 *
	 * @return array  Dados normalizados do WP Statistics.
	 */
	public function get_wp_statistics_data(): array {
		$data = array(
			'source'    => 'wp_statistics',
			'available' => false,
		);

		if ( ! $this->is_wp_statistics_active() ) {
			return $data;
		}

		$data['available'] = true;

		// Verifica se a classe WP_Statistics existe
		if ( class_exists( 'WP_STATISTICS\\Menus' ) ) {
			global $wpdb;

			// Total de page views (tabela wp_statistics_visit)
			$visit_table = $wpdb->prefix . 'statistics_visit';
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $visit_table ) ) === $visit_table ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$data['total_visits'] = (int) $wpdb->get_var(
					$wpdb->prepare( 'SELECT SUM(visit) FROM %i', $visit_table )
				);

				// Visits últimos 30 dias
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$data['visits_30d'] = (int) $wpdb->get_var(
					$wpdb->prepare(
						'SELECT SUM(visit) FROM %i WHERE last_counter >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
						$visit_table
					)
				);
			}

			// Total de visitors (tabela wp_statistics_visitor)
			$visitor_table = $wpdb->prefix . 'statistics_visitor';
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $visitor_table ) ) === $visitor_table ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$data['total_visitors'] = (int) $wpdb->get_var(
					$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $visitor_table )
				);

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$data['visitors_30d'] = (int) $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM %i WHERE last_counter >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
						$visitor_table
					)
				);
			}

			// Top páginas visitadas (tabela wp_statistics_pages)
			$pages_table = $wpdb->prefix . 'statistics_pages';
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $pages_table ) ) === $pages_table ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$data['top_pages'] = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id AS post_id, uri, SUM(count) AS views
                     FROM %i
                     WHERE type = %s
                     GROUP BY id, uri
                     ORDER BY views DESC
                     LIMIT 20",
						$pages_table,
						'post'
					),
					ARRAY_A
				) ?: array();
			}
		}

		return $data;
	}

	// ═══════════════════════════════════════════════════════════════
	// FONTE 3: WP EVENTS MANAGER (RSVP + Event Views)
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Extrai dados do WP Events Manager / Apollo Events.
	 * Obtém contagem de RSVPs e views de evento.
	 *
	 * @return array  Dados normalizados de eventos.
	 */
	public function get_events_manager_stats(): array {
		$data = array(
			'source'    => 'events_manager',
			'available' => false,
		);

		if ( ! $this->is_events_manager_active() ) {
			return $data;
		}

		$data['available'] = true;

		global $wpdb;

		// Total de eventos publicados
		$data['total_events'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
				'event',
				'publish'
			)
		);

		// Eventos futuros (upcoming)
		$today                   = current_time( 'Y-m-d' );
		$data['upcoming_events'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'event'
             AND p.post_status = 'publish'
             AND pm.meta_key = '_event_start_date'
             AND pm.meta_value >= %s",
				$today
			)
		);

		// Eventos mais favoritados (cross-reference com apollo_favs)
		$fav_table = $wpdb->prefix . APOLLO_FAV_TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$data['most_faved_events'] = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.post_id, p.post_title, COUNT(DISTINCT f.user_id) as fav_count
				 FROM %i f
				 INNER JOIN {$wpdb->posts} p ON f.post_id = p.ID
				 WHERE f.post_type = %s
				 AND p.post_status = %s
				 GROUP BY f.post_id, p.post_title
				 ORDER BY fav_count DESC
				 LIMIT 10",
				$fav_table,
				'event',
				'publish'
			),
			ARRAY_A
		) ?: array();

		// DJs mais favoritados
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$data['most_faved_djs'] = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT f.post_id, p.post_title, COUNT(DISTINCT f.user_id) as fav_count
				 FROM %i f
				 INNER JOIN {$wpdb->posts} p ON f.post_id = p.ID
				 WHERE f.post_type = %s
				 AND p.post_status = %s
				 GROUP BY f.post_id, p.post_title
				 ORDER BY fav_count DESC
				 LIMIT 10",
				$fav_table,
				'dj',
				'publish'
			),
			ARRAY_A
		) ?: array();

		// Contagem de RSVP (se meta existir)
		$data['rsvp_total'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s",
				'_event_rsvp_count'
			)
		);

		return $data;
	}

	// ═══════════════════════════════════════════════════════════════
	// FONTE 4: APOLLO USERS / UsersWP (Profile Views)
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Extrai dados de views de perfil do Apollo Users.
	 * Usa a tabela apollo_profile_views e user_meta _apollo_profile_views.
	 *
	 * @return array  Dados normalizados de perfis.
	 */
	public function get_userswp_views(): array {
		$data = array(
			'source'    => 'apollo_users',
			'available' => false,
		);

		if ( ! $this->is_apollo_users_active() ) {
			return $data;
		}

		$data['available'] = true;

		global $wpdb;

		// Total de profile views registradas (via user_meta)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['total_profile_views'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(CAST(meta_value AS UNSIGNED))
             FROM {$wpdb->usermeta}
             WHERE meta_key = %s",
				'_apollo_profile_views'
			)
		);

		// Top perfis mais visitados
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['top_profiles'] = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT um.user_id, u.display_name, CAST(um.meta_value AS UNSIGNED) AS views
             FROM {$wpdb->usermeta} um
             INNER JOIN {$wpdb->users} u ON um.user_id = u.ID
             WHERE um.meta_key = %s
             AND CAST(um.meta_value AS UNSIGNED) > 0
             ORDER BY views DESC
             LIMIT 10",
				'_apollo_profile_views'
			),
			ARRAY_A
		) ?: array();

		// Total de usuários registrados
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['total_users'] = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $wpdb->users )
		);

		// Usuários registrados nos últimos 30 dias
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['new_users_30d'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i
             WHERE user_registered >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
				$wpdb->users
			)
		);

		// Perfis completados (meta _apollo_profile_completed = 100)
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$data['completed_profiles'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->usermeta}
             WHERE meta_key = %s
             AND CAST(meta_value AS UNSIGNED) = 100",
				'_apollo_profile_completed'
			)
		);

		return $data;
	}

	// ═══════════════════════════════════════════════════════════════
	// FONTE 5: BUDDYPRESS / APOLLO SOCIAL (Engajamento)
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Extrai métricas de engajamento social.
	 * Usa tabelas do BuddyPress (se disponível) ou apollo_activity / apollo_connections.
	 *
	 * @return array  Dados normalizados de atividade social.
	 */
	public function get_social_engagement(): array {
		$data = array(
			'source'    => 'social',
			'available' => false,
		);

		if ( ! $this->is_social_active() ) {
			return $data;
		}

		$data['available'] = true;

		global $wpdb;

		// Verifica se BuddyPress está ativo
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) ) {
			$bp_activity = $wpdb->prefix . 'bp_activity';

			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $bp_activity ) ) === $bp_activity ) {
				// Total de atividades
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$data['total_activities'] = (int) $wpdb->get_var(
					$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $bp_activity )
				);

				// Atividades nos últimos 30 dias
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$data['activities_30d'] = (int) $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM %i
                     WHERE date_recorded >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
						$bp_activity
					)
				);

				// Usuários mais ativos
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$data['top_active_users'] = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT user_id, COUNT(*) AS activity_count
                     FROM %i
                     WHERE date_recorded >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     GROUP BY user_id
                     ORDER BY activity_count DESC
                     LIMIT 10',
						$bp_activity
					),
					ARRAY_A
				) ?: array();
			}
		}

		// Apollo Social tables (apollo_activity, apollo_connections)
		$activity_table    = $wpdb->prefix . 'apollo_activity';
		$connections_table = $wpdb->prefix . 'apollo_connections';

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $activity_table ) ) === $activity_table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$data['apollo_activities'] = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $activity_table )
			);
		}

		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $connections_table ) ) === $connections_table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$data['total_connections'] = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM %i', $connections_table )
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$data['connections_30d'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
					$connections_table
				)
			);
		}

		return $data;
	}

	// ═══════════════════════════════════════════════════════════════
	// PREPARAÇÃO PARA UIPress Lite (Dados para Gráficos)
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Prepara dados formatados para consumo pelo UIPress (gráficos visuais).
	 * Retorna arrays no formato esperado por Chart.js / UIPress cards.
	 *
	 * @return array  Dados formatados para gráficos.
	 */
	public function get_chart_data(): array {
		$fav_stats = $this->get_fav_statistics();

		$data = array(
			'source'    => 'uipress_bridge',
			'available' => true,
		);

		// Gráfico de linha: Tendência diária de favoritos (30 dias)
		$data['charts']['fav_trend'] = array(
			'type'     => 'line',
			'title'    => 'Favoritos - Últimos 30 dias',
			'labels'   => array_column( $fav_stats['daily_trend'] ?? array(), 'day' ),
			'datasets' => array(
				array(
					'label'           => 'Novos Favoritos',
					'data'            => array_map( 'intval', array_column( $fav_stats['daily_trend'] ?? array(), 'total' ) ),
					'borderColor'     => '#ff6b6b',
					'backgroundColor' => 'rgba(255, 107, 107, 0.1)',
				),
			),
		);

		// Gráfico de pizza: Distribuição por tipo de CPT
		$data['charts']['fav_distribution'] = array(
			'type'     => 'doughnut',
			'title'    => 'Favoritos por Tipo',
			'labels'   => array_column( $fav_stats['by_type'] ?? array(), 'post_type' ),
			'datasets' => array(
				array(
					'data'            => array_map( 'intval', array_column( $fav_stats['by_type'] ?? array(), 'total' ) ),
					'backgroundColor' => array(
						'#ff6b6b',
						'#ffd93d',
						'#6bcb77',
						'#4d96ff',
						'#9b59b6',
						'#ff8c42',
						'#2ed8a3',
					),
				),
			),
		);

		// Widgets de overview (cards informativos)
		$data['widgets'] = array(
			array(
				'title' => 'Total de Favoritos',
				'value' => $fav_stats['total_favs'] ?? 0,
				'icon'  => 'ri-fire-line',
				'color' => '#ff6b6b',
			),
			array(
				'title' => 'Usuários Engajados',
				'value' => $fav_stats['unique_users'] ?? 0,
				'icon'  => 'ri-user-heart-line',
				'color' => '#4d96ff',
			),
			array(
				'title' => 'Últimos 7 dias',
				'value' => $fav_stats['last_7_days'] ?? 0,
				'icon'  => 'ri-calendar-check-line',
				'color' => '#6bcb77',
			),
			array(
				'title' => 'Últimos 30 dias',
				'value' => $fav_stats['last_30_days'] ?? 0,
				'icon'  => 'ri-calendar-2-line',
				'color' => '#ffd93d',
			),
		);

		return $data;
	}

	// ═══════════════════════════════════════════════════════════════
	// COLETA CONSOLIDADA
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Coleta dados de TODAS as fontes e retorna pacote normalizado.
	 * Chamado pelo apollo-statistics quando precisa de dados consolidados.
	 *
	 * @return array  Dados consolidados de todas as fontes.
	 */
	public function collect_all_data(): array {
		return array(
			'timestamp'      => current_time( 'mysql' ),
			'fav'            => $this->get_fav_statistics(),
			'wp_statistics'  => $this->get_wp_statistics_data(),
			'events_manager' => $this->get_events_manager_stats(),
			'users'          => $this->get_userswp_views(),
			'social'         => $this->get_social_engagement(),
			'charts'         => $this->get_chart_data(),
		);
	}

	/**
	 * Calcula e armazena estatísticas diárias (cron job).
	 * Salva um snapshot diário para histórico de tendências.
	 */
	public function calculate_daily_stats(): void {
		$stats = array(
			'date'      => current_time( 'Y-m-d' ),
			'fav_total' => apollo_get_most_faved( null, 9999 ),
			'fav_stats' => $this->get_fav_statistics(),
		);

		// Armazena como option com rotação de 90 dias
		$key     = 'apollo_fav_daily_' . current_time( 'Y-m-d' );
		$old_key = 'apollo_fav_daily_' . wp_date( 'Y-m-d', strtotime( '-90 days' ) );

		update_option( $key, $stats, false );
		delete_option( $old_key );

		// Dispara hook para apollo-statistics processar
		do_action( 'apollo/statistics/daily_snapshot', $stats );
	}

	// ═══════════════════════════════════════════════════════════════
	// VERIFICAÇÃO DE DISPONIBILIDADE DE PLUGINS
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Verifica se WP Statistics está ativo.
	 */
	private function is_wp_statistics_active(): bool {
		return class_exists( 'WP_STATISTICS\\Menus' ) || is_plugin_active( 'wp-statistics/wp-statistics.php' );
	}

	/**
	 * Verifica se o WP Events Manager / Apollo Events está ativo.
	 */
	private function is_events_manager_active(): bool {
		return post_type_exists( 'event' )
			|| is_plugin_active( 'apollo-events/apollo-events.php' )
			|| is_plugin_active( 'apollo-event/apollo-event.php' );
	}

	/**
	 * Verifica se o Apollo Users está ativo.
	 */
	private function is_apollo_users_active(): bool {
		return is_plugin_active( 'apollo-users/apollo-users.php' )
			|| class_exists( 'UsersWP' );
	}

	/**
	 * Verifica se módulo social está ativo (BuddyPress ou Apollo Social).
	 */
	private function is_social_active(): bool {
		return is_plugin_active( 'apollo-social/apollo-social.php' )
			|| function_exists( 'bp_is_active' );
	}

	/**
	 * Verifica se UIPress Lite está ativo.
	 */
	private function is_uipress_active(): bool {
		return is_plugin_active( 'uipress-lite/uipress-lite.php' )
			|| class_exists( 'uipress' );
	}
}
