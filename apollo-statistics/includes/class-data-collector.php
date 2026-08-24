<?php
/**
 * Coletor de dados — recolhe métricas de todas as fontes registradas.
 *
 * Consumidores externos (ex.: apollo-fav/Statistics_Merge) registram
 * provedores via filter `apollo/statistics/data_providers` e disparam
 * dados via action `apollo/statistics/collect`.
 *
 * @package Apollo\Statistics
 */

declare(strict_types=1);

namespace Apollo\Statistics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Data Collector — recolhe métricas de todas as fontes registradas.
 */
final class Data_Collector {

	/**
	 * Provedores registrados.
	 *
	 * @var array
	 */
	private array $providers = array();

	/**
	 * Inicializa hooks.
	 */
	public function __construct() {
		// Hooks registrados no init() para manter padrão Apollo.
	}

	/**
	 * Inicializa o coletor de dados — registra todos os hooks.
	 */
	public function init(): void {
		// Registrar provedores padrão primeiro.
		add_action( 'init', array( $this, 'register_core_providers' ), 5 );

		// Cron diário para coleta.
		add_action( 'apollo_stats_daily_collect', array( $this, 'run_daily_collection' ) );

		// Escuta coletas manuais de outros plugins.
		add_action( 'apollo/statistics/collect', array( $this, 'handle_external_collection' ), 10, 3 );

		// Hook para registrar métricas em tempo real.
		add_action( 'wp_login', array( $this, 'track_login' ), 10, 2 );
		add_action( 'user_register', array( $this, 'track_registration' ) );
		add_action( 'template_redirect', array( $this, 'track_content_view' ) );

		// Escuta favoritos do apollo-fav.
		add_action( 'apollo/fav/added', array( $this, 'track_fav_added' ), 10, 3 );
		add_action( 'apollo/fav/removed', array( $this, 'track_fav_removed' ), 10, 3 );

		// Escuta eventos de email do apollo-email.
		add_action( 'apollo/email/sent', array( $this, 'track_email_sent' ), 10, 2 );
		add_action( 'apollo/email/failed', array( $this, 'track_email_failed' ), 10, 3 );
		add_action( 'apollo/email/opened', array( $this, 'track_email_opened' ) );

		// Agenda cron se não existir.
		if ( ! wp_next_scheduled( 'apollo_stats_daily_collect' ) ) {
			wp_schedule_event( time(), 'daily', 'apollo_stats_daily_collect' );
		}
	}


	/* ───────────────────────── PROVEDORES ───────────────────────── */

	/**
	 * Registra provedores core (internos).
	 */
	public function register_core_providers(): void {
		$this->providers = array(
			'wp_core'        => array(
				'label'    => 'WordPress Core',
				'callback' => array( $this, 'collect_wp_core' ),
				'active'   => true,
			),
			'events_manager' => array(
				'label'    => 'Events Manager',
				'callback' => array( $this, 'collect_events_manager' ),
				'active'   => class_exists( 'EM_Events' ) || post_type_exists( 'event' ),
			),
			'wp_statistics'  => array(
				'label'    => 'WP Statistics',
				'callback' => array( $this, 'collect_wp_statistics' ),
				'active'   => class_exists( 'WP_Statistics' ) || defined( 'WP_STATISTICS_VERSION' ),
			),
		);

		/**
		 * Filtra os provedores de dados.
		 * Outros plugins (ex.: apollo-fav) registram seus provedores aqui.
		 *
		 * @param array $providers Array de provedores ['slug' => ['label', 'callback', 'active']].
		 */
		$this->providers = apply_filters( 'apollo/statistics/data_providers', $this->providers ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
	}

	/**
	 * Retorna lista de provedores registrados.
	 *
	 * @return array
	 */
	public function get_providers(): array {
		return $this->providers;
	}

	/* ───────────────────────── COLETA DIÁRIA ───────────────────────── */

	/**
	 * Executa coleta diária de todos os provedores ativos.
	 */
	public function run_daily_collection(): void {
		$date = current_time( 'Y-m-d' );

		foreach ( $this->providers as $slug => $provider ) {
			if ( empty( $provider['active'] ) || ! is_callable( $provider['callback'] ) ) {
				continue;
			}

			try {
				call_user_func( $provider['callback'], $date );
			} catch ( \Throwable $e ) {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					sprintf(
						'[Apollo Statistics] Erro na coleta do provedor "%s": %s',
						$slug,
						$e->getMessage()
					)
				);
			}
		}

		/**
		 * Ação após coleta diária completa.
		 *
		 * @param string $date Data da coleta (Y-m-d).
		 */
		do_action( 'apollo/statistics/daily_collected', $date ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
	}

	/* ───────────────────────── COLETA: WP CORE ───────────────────────── */

	/**
	 * Coleta dados básicos do WordPress.
	 *
	 * @param string $date Data da coleta.
	 */
	public function collect_wp_core( string $date ): void {
		global $wpdb;

		// Contagem de posts publicados por CPT.
		$cpts = array( 'event', 'dj', 'classified', 'loc', 'hub', 'supplier', 'doc' );

		foreach ( $cpts as $cpt ) {
			$count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->posts}
                 WHERE post_type = %s AND post_status = 'publish'
                 AND DATE(post_date) = %s",
					$cpt,
					$date
				)
			);

			if ( $count > 0 ) {
				apollo_stats_record_content( 0, 'daily_' . $cpt . '_count', $count, $date );
			}
		}

		// Novos comentários do dia.
		$comments = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->comments}
             WHERE comment_approved = '1' AND DATE(comment_date) = %s",
				$date
			)
		);

		if ( $comments > 0 ) {
			apollo_stats_record_content( 0, 'daily_comments', $comments, $date );
		}
	}

	/* ───────────────────────── COLETA: EVENTS MANAGER ───────────────────────── */

	/**
	 * Coleta dados do Events Manager.
	 *
	 * @param string $date Data da coleta.
	 */
	public function collect_events_manager( string $date ): void {
		global $wpdb;

		if ( ! post_type_exists( 'event' ) ) {
			return;
		}

		// Eventos do dia.
		$events_table = $wpdb->prefix . 'apollo_stats_events';

		// Eventos que acontecem hoje.
		$today_events = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT p.ID, pm.meta_value AS start_date
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_event_start_date'
             WHERE p.post_type = 'event' AND p.post_status = 'publish'
             AND DATE(pm.meta_value) = %s",
				$date
			)
		);

		foreach ( $today_events as $event ) {
			apollo_stats_record_event( (int) $event->ID, 'scheduled', 1, $date );
		}

		// RSVPs — tabela nativa apollo_event_rsvp.
		$rsvp_table = $wpdb->prefix . 'apollo_event_rsvp';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $rsvp_table ) ) === $rsvp_table ) {
			$rsvps = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT event_id, status, COUNT(*) AS total FROM {$rsvp_table} WHERE DATE(created_at) = %s GROUP BY event_id, status",
					$date
				)
			);

			foreach ( $rsvps as $rsvp ) {
				apollo_stats_record_event( (int) $rsvp->event_id, 'rsvp_' . $rsvp->status, (int) $rsvp->total, $date );
			}

			// Total de going por evento (todos os tempos, para ranking).
			$going_counts = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT event_id, COUNT(*) AS total FROM {$rsvp_table} WHERE status = 'going' GROUP BY event_id" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
			foreach ( $going_counts as $row ) {
				update_post_meta( (int) $row->event_id, '_event_rsvp_going_count', (int) $row->total );
			}
		}
	}

	/* ───────────────────────── COLETA: WP STATISTICS ───────────────────────── */

	/**
	 * Coleta dados do WP Statistics (se disponível).
	 *
	 * @param string $date Data da coleta.
	 */
	public function collect_wp_statistics( string $date ): void {
		global $wpdb;

		if ( ! defined( 'WP_STATISTICS_VERSION' ) && ! class_exists( 'WP_Statistics' ) ) {
			return;
		}

		// Table do WP Statistics para pageviews.
		$pages_table = $wpdb->prefix . 'statistics_pages';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $pages_table ) ) !== $pages_table ) {
			return;
		}

		// Pageviews por post.
		$views = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id AS post_id, type, count AS views FROM {$pages_table} WHERE date = %s AND type = 'post' ORDER BY count DESC LIMIT 100",
				$date
			)
		);

		foreach ( $views as $view ) {
			apollo_stats_record_content( (int) $view->post_id, 'view', (int) $view->views, $date );
		}
	}

	/* ───────────────────────── TRACKING EM TEMPO REAL ───────────────────────── */

	/**
	 * Registra login do usuário.
	 *
	 * @param string   $user_login Login.
	 * @param \WP_User $user       Objeto do usuário.
	 */
	public function track_login( string $user_login, \WP_User $user ): void {
		apollo_stats_record_user( $user->ID, 'login' );
	}

	/**
	 * Registra novo cadastro.
	 *
	 * @param int $user_id ID do novo usuário.
	 */
	public function track_registration( int $user_id ): void {
		apollo_stats_record_user( $user_id, 'registration' );
	}

	/**
	 * Registra view de conteúdo singular.
	 */
	public function track_content_view(): void {
		if ( ! is_singular() || is_admin() ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		// Apenas CPTs do ecossistema.
		$tracked_cpts = apply_filters(
			'apollo/statistics/tracked_cpts', // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
			array( 'event', 'dj', 'classified', 'loc', 'hub', 'supplier', 'doc' )
		);

		if ( ! in_array( $post->post_type, $tracked_cpts, true ) ) {
			return;
		}

		// Evitar dupla contagem com cookie simples.
		$cookie_key = 'apollo_sv_' . $post->ID;
		if ( isset( $_COOKIE[ $cookie_key ] ) ) {
			return;
		}

		apollo_stats_record_content( $post->ID, 'view' );

		// Se for evento, registra também na tabela de eventos.
		if ( 'event' === $post->post_type ) {
			apollo_stats_record_event( $post->ID, 'view' );
		}

		// Cookie por 1h.
		if ( ! headers_sent() ) {
			setcookie( $cookie_key, '1', time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	/**
	 * Registra favorito adicionado.
	 *
	 * @param int    $user_id   ID do usuário.
	 * @param int    $post_id   ID do post.
	 * @param string $post_type CPT.
	 */
	public function track_fav_added( int $user_id, int $post_id, string $post_type ): void {
		apollo_stats_record_content( $post_id, 'fav' );

		if ( 'event' === $post_type ) {
			apollo_stats_record_event( $post_id, 'fav' );
		}
	}

	/**
	 * Registra favorito removido.
	 *
	 * @param int    $user_id   ID do usuário.
	 * @param int    $post_id   ID do post.
	 * @param string $post_type CPT.
	 */
	public function track_fav_removed( int $user_id, int $post_id, string $post_type ): void {
		apollo_stats_record_content( $post_id, 'unfav' );

		if ( 'event' === $post_type ) {
			apollo_stats_record_event( $post_id, 'unfav' );
		}
	}

	/* ───────────────────────── TRACKING: EMAIL ───────────────────────── */

	/**
	 * Registra e-mail enviado com sucesso.
	 *
	 * @param int    $log_id  ID do registro no log.
	 * @param object $message Objeto de mensagem (duck-typed).
	 */
	public function track_email_sent( int $log_id, object $message ): void {
		// Registra como métrica de conteúdo global (post_id=0).
		apollo_stats_record_content( 0, 'email_sent' );
	}

	/**
	 * Registra e-mail com falha de envio.
	 *
	 * @param int    $log_id    ID do registro no log.
	 * @param string $error_msg Mensagem de erro.
	 * @param object $message   Objeto de mensagem.
	 */
	public function track_email_failed( int $log_id, string $error_msg, object $message ): void {
		apollo_stats_record_content( 0, 'email_failed' );
	}

	/**
	 * Registra e-mail aberto (pixel tracking).
	 *
	 * @param int $log_id ID do registro no log.
	 */
	public function track_email_opened( int $log_id ): void {
		apollo_stats_record_content( 0, 'email_opened' );
	}

	/* ───────────────────────── COLETA EXTERNA ───────────────────────── */

	/**
	 * Recebe dados de plugins externos via action.
	 *
	 * @param string $table_type 'events', 'users' ou 'content'.
	 * @param int    $entity_id  ID da entidade.
	 * @param array  $data       ['metric_type' => string, 'metric_value' => int, 'date' => string].
	 */
	public function handle_external_collection( string $table_type, int $entity_id, array $data ): void {
		$metric_type  = sanitize_text_field( $data['metric_type'] ?? '' );
		$metric_value = absint( $data['metric_value'] ?? 1 );
		$date         = sanitize_text_field( $data['date'] ?? '' );

		if ( empty( $metric_type ) ) {
			return;
		}

		switch ( $table_type ) {
			case 'events':
				apollo_stats_record_event( $entity_id, $metric_type, $metric_value, $date );
				break;
			case 'users':
				apollo_stats_record_user( $entity_id, $metric_type, $metric_value, $date );
				break;
			case 'content':
			default:
				apollo_stats_record_content( $entity_id, $metric_type, $metric_value, $date );
				break;
		}
	}
}
