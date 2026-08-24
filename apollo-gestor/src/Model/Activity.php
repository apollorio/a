<?php

/**
 * Activity Model — Event activity log
 *
 * Records all actions (task created, member added, payment made, etc.)
 * for auditing and timeline display.
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activity {



	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'apollo_gestor_activity';
	}

	/**
	 * Log an activity
	 *
	 * @param array $data {
	 *     @type int    $event_id    Event ID
	 *     @type int    $user_id     Actor user ID (0 = system)
	 *     @type string $action      Action slug (task_created, member_added, payment_created, etc.)
	 *     @type string $entity_type Entity type (task, team, payment, milestone, event)
	 *     @type int    $entity_id   Entity ID
	 *     @type array  $meta        Extra data (serialized)
	 * }
	 * @return int|false  Insert ID or false
	 */
	public static function log( array $data ): int|false {
		global $wpdb;

		$insert = array(
			'event_id'    => absint( $data['event_id'] ?? 0 ),
			'user_id'     => absint( $data['user_id'] ?? get_current_user_id() ),
			'action'      => sanitize_key( $data['action'] ?? '' ),
			'entity_type' => sanitize_key( $data['entity_type'] ?? '' ),
			'entity_id'   => absint( $data['entity_id'] ?? 0 ),
			'meta'        => isset( $data['meta'] ) ? wp_json_encode( $data['meta'] ) : null,
		);

		$result = $wpdb->insert( self::table(), $insert );

		return $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Get activity feed for an event
	 *
	 * @param int $event_id  Event ID (0 = all events)
	 * @param int $limit     Max records
	 * @param int $offset    Offset for pagination
	 * @return array
	 */
	public static function get_by_event( int $event_id = 0, int $limit = 30, int $offset = 0 ): array {
		global $wpdb;
		$table = self::table();

		if ( $event_id > 0 ) {
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL
				$wpdb->prepare(
					"SELECT a.*, u.display_name
                     FROM {$table} a
                     LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
                     WHERE a.event_id = %d
                     ORDER BY a.created_at DESC
                     LIMIT %d OFFSET %d",
					$event_id,
					$limit,
					$offset
				),
				ARRAY_A
			) ?: array();
		} else {
			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL
				$wpdb->prepare(
					"SELECT a.*, u.display_name
                     FROM {$table} a
                     LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
                     ORDER BY a.created_at DESC
                     LIMIT %d OFFSET %d",
					$limit,
					$offset
				),
				ARRAY_A
			) ?: array();
		}

		// Decode meta and enrich
		foreach ( $rows as &$row ) {
			$row['meta']       = $row['meta'] ? json_decode( $row['meta'], true ) : array();
			$row['avatar_url'] = $row['user_id'] ? get_avatar_url( (int) $row['user_id'], array( 'size' => 44 ) ) : '';
			$row['time_ago']   = self::time_ago( $row['created_at'] );
		}

		return $rows;
	}

	/**
	 * Get recent activity across all user's events
	 *
	 * @param int $user_id
	 * @param int $limit
	 * @return array
	 */
	public static function get_by_user( int $user_id, int $limit = 20 ): array {
		$event_ids = Team::get_user_event_ids( $user_id );
		if ( empty( $event_ids ) ) {
			return array();
		}

		global $wpdb;
		$table = self::table();

		$placeholders = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );
		$params       = array_merge( $event_ids, array( $limit ) );

		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->prepare(
				"SELECT a.*, u.display_name
                 FROM {$table} a
                 LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
                 WHERE a.event_id IN ({$placeholders})
                 ORDER BY a.created_at DESC
                 LIMIT %d",
				...$params
			),
			ARRAY_A
		) ?: array();

		foreach ( $rows as &$row ) {
			$row['meta']       = $row['meta'] ? json_decode( $row['meta'], true ) : array();
			$row['avatar_url'] = $row['user_id'] ? get_avatar_url( (int) $row['user_id'], array( 'size' => 44 ) ) : '';
			$row['time_ago']   = self::time_ago( $row['created_at'] );
		}

		return $rows;
	}

	/**
	 * Human-readable action labels (pt-BR)
	 */
	public static function action_label( string $action ): string {
		$labels = array(
			'task_created'      => 'criou uma tarefa',
			'task_updated'      => 'atualizou uma tarefa',
			'task_completed'    => 'concluiu uma tarefa',
			'task_deleted'      => 'removeu uma tarefa',
			'member_added'      => 'adicionou um membro',
			'member_removed'    => 'removeu um membro',
			'member_updated'    => 'atualizou um membro',
			'payment_created'   => 'registrou um pagamento',
			'payment_updated'   => 'atualizou pagamento',
			'payment_deleted'   => 'removeu um pagamento',
			'payment_paid'      => 'confirmou pagamento',
			'milestone_created' => 'criou um marco',
			'milestone_toggled' => 'atualizou marco',
			'status_changed'    => 'alterou status do evento',
			'perms_updated'     => 'atualizou permissões',
		);

		return $labels[ $action ] ?? $action;
	}

	/**
	 * Time ago in pt-BR
	 *
	 * @param string $datetime
	 * @return string
	 */
	/**
	 * Compact numeric time-ago per Apollo standard.
	 * Returns e.g. '53min', '2h', '7d', '3w', '1y' — no 'atrás' suffix.
	 */
	private static function time_ago( string $datetime ): string {
		$diff = max( 0, (int) current_time( 'timestamp' ) - (int) strtotime( $datetime ) );
		if ( $diff < 60 ) {
			return $diff . 's';
		}
		if ( $diff < 3600 ) {
			return floor( $diff / 60 ) . 'min';
		}
		if ( $diff < 86400 ) {
			return floor( $diff / 3600 ) . 'h';
		}
		if ( $diff < 604800 ) {
			return floor( $diff / 86400 ) . 'd';
		}
		if ( $diff < 31536000 ) {
			return floor( $diff / 604800 ) . 'w';
		}
		return floor( $diff / 31536000 ) . 'y';
	}
}
