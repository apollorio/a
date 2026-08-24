<?php
/**
 * Expiration — Sistema de expiração automática
 *
 * Evento vira "gone" (classe CSS + status visual) exatamente 30 minutos
 * após _event_end_date + _event_end_time.
 *
 * Usa WP-Cron para varredura periódica + verificação on-the-fly.
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Expiration {

	public function __construct() {
		// Registrar intervalo customizado do cron
		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );

		// Hook do cron para varredura
		add_action( 'apollo_event_check_expiration', array( $this, 'check_all_events' ) );

		// Hook do cron para reminders (24h antes do evento)
		add_action( 'apollo_event_check_expiration', array( $this, 'send_upcoming_reminders' ) );

		// Hook quando evento é visualizado (verificação on-the-fly)
		add_action( 'the_post', array( $this, 'check_single_event' ) );

		// Hook personalizado para quando evento expira
		add_action( 'apollo_event_gone', array( $this, 'on_event_gone' ), 10, 1 );

		// View counter — incrementa ao visitar single event
		add_action( 'wp', array( $this, 'track_view_count' ) );
	}

	/**
	 * Incrementa contador de visualizações no single event.
	 * Ignora admins e bots.
	 */
	public function track_view_count(): void {
		if ( ! is_singular( APOLLO_EVENT_CPT ) || is_admin() ) {
			return;
		}
		// Ignorar crawlers comuns
		$ua = strtolower( $_SERVER['HTTP_USER_AGENT'] ?? '' );
		if ( str_contains( $ua, 'bot' ) || str_contains( $ua, 'crawler' ) || str_contains( $ua, 'spider' ) ) {
			return;
		}
		$post_id = get_queried_object_id();
		if ( ! $post_id ) {
			return;
		}
		$count = (int) get_post_meta( $post_id, '_event_view_count', true );
		update_post_meta( $post_id, '_event_view_count', $count + 1 );
	}

	/**
	 * Adiciona intervalo de 5 minutos ao cron
	 */
	public function add_cron_interval( array $schedules ): array {
		$schedules['every_five_minutes'] = array(
			'interval' => 300,
			'display'  => __( 'A cada 5 minutos', 'apollo-events' ),
		);
		return $schedules;
	}

	/**
	 * Varredura de todos os eventos que podem ter expirado
	 *
	 * Busca apenas eventos publicados que ainda não estão marcados como gone.
	 */
	public function check_all_events(): void {
		// Verificar se expiração está ativa nas configurações
		if ( ! apollo_event_option( 'enable_expiration', true ) ) {
			return;
		}

		$now = current_time( 'Y-m-d H:i:s' );

		$events = get_posts(
			array(
				'post_type'      => APOLLO_EVENT_CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_event_is_gone',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_event_start_date',
						'value'   => '',
						'compare' => '!=',
					),
				),
				'fields'         => 'ids',
			)
		);

		foreach ( $events as $event_id ) {
			if ( apollo_event_is_gone( $event_id ) ) {
				/**
				 * Dispara quando evento acaba de expirar
				 *
				 * @param int $event_id ID do evento.
				 */
				do_action( 'apollo_event_gone', $event_id );
			}
		}
	}

	/**
	 * Verificação on-the-fly quando evento é exibido
	 *
	 * @param \WP_Post $post Post atual.
	 */
	public function check_single_event( \WP_Post $post ): void {
		if ( APOLLO_EVENT_CPT !== $post->post_type ) {
			return;
		}

		if ( apollo_event_is_gone( $post->ID ) ) {
			// Adiciona classe CSS ao body
			add_filter(
				'body_class',
				function ( array $classes ) {
					$classes[] = 'apollo-event-gone';
					return $classes;
				}
			);

			add_filter(
				'post_class',
				function ( array $classes ) {
					$classes[] = 'a-eve-gone';
					return $classes;
				}
			);
		}
	}

	/**
	 * Callback quando evento expira — integrações
	 *
	 * @param int $event_id ID do evento.
	 */
	public function on_event_gone( int $event_id ): void {
		// Notificação via apollo-notif (se ativo)
		if ( function_exists( 'apollo_send_notification' ) ) {
			$event = get_post( $event_id );
			if ( $event ) {
				apollo_send_notification(
					array(
						'type'    => 'event_gone',
						'user_id' => (int) $event->post_author,
						'message' => sprintf(
						/* translators: %s: título do evento */
							__( 'Seu evento "%s" foi encerrado.', 'apollo-events' ),
							$event->post_title
						),
						'link'    => get_permalink( $event_id ),
					)
				);
			}
		}

		// Log via apollo-social (se ativo)
		if ( function_exists( 'apollo_log_activity' ) ) {
			$event = get_post( $event_id );
			if ( $event ) {
				apollo_log_activity(
					array(
						'user_id'      => (int) $event->post_author,
						'component'    => 'events',
						'type'         => 'event_gone',
						'action_text'  => sprintf( 'O evento "%s" foi encerrado', $event->post_title ),
						'item_id'      => $event_id,
						'primary_link' => get_permalink( $event_id ),
					)
				);
			}
		}
	}

	/**
	 * Send reminders for events starting within the next 24 hours.
	 *
	 * Fires `apollo/event/reminder` for each RSVP'd user who hasn't been reminded yet.
	 * Reuses the existing 5-min cron schedule from expiration checks.
	 */
	public function send_upcoming_reminders(): void {
		global $wpdb;

		$now     = current_time( 'Y-m-d H:i:s' );
		$in_24h  = wp_date( 'Y-m-d H:i:s', strtotime( '+24 hours' ) );

		// Find events starting in the next 24h that haven't had reminders sent.
		$events = get_posts(
			array(
				'post_type'      => APOLLO_EVENT_CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_event_start_date',
						'value'   => array( $now, $in_24h ),
						'compare' => 'BETWEEN',
						'type'    => 'DATETIME',
					),
					array(
						'key'     => '_event_reminder_sent',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_event_is_gone',
						'compare' => 'NOT EXISTS',
					),
				),
				'fields' => 'ids',
			)
		);

		if ( empty( $events ) ) {
			return;
		}

		$rsvp_table = $wpdb->prefix . 'apollo_event_rsvp';

		foreach ( $events as $event_id ) {
			// Get all RSVP'd users (going/maybe).
			$user_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT user_id FROM {$rsvp_table} WHERE event_id = %d AND status IN ('going','maybe')",
					$event_id
				)
			);

			if ( ! empty( $user_ids ) ) {
				/**
				 * Fires for each upcoming event that needs reminders.
				 *
				 * Ecosystem contract: argument #2 is ALWAYS a string action name
				 * (typed collectors in other Apollo plugins depend on it).
				 *
				 * @param int    $event_id Event ID.
				 * @param string $action   Action name.
				 * @param int[]  $user_ids Array of RSVP'd user IDs to notify.
				 */
				do_action( 'apollo/event/reminder', $event_id, 'reminder', $user_ids );
			}

			// Mark reminder as sent to avoid duplicates.
			update_post_meta( $event_id, '_event_reminder_sent', $now );
		}
	}
}
