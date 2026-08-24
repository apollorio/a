<?php

/**
 * Task Reminder Cron — scans tasks with reminders enabled and queues
 * email alerts based on each task's due_date and reminder_offset.
 *
 * Processing flow (runs every 5 min via WP-Cron):
 *   1. Query tasks: reminder_enabled = 1, status NOT IN (canceled, delivered),
 *      due_date IS NOT NULL, not yet in reminder_log for this cycle.
 *   2. For each task, calculate reminder_at = due_date − offset.
 *   3. If NOW ≥ reminder_at → check user pref → queue email → log.
 *
 * @package Apollo\Gestor\Cron
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Gestor\Cron;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TaskReminderCron {

	/** Cron hook name. */
	public const HOOK = 'apollo_gestor_task_reminders';

	/** Valid offset values → seconds before due_date. */
	private const OFFSETS = array(
		'1h'  => 3600,
		'3h'  => 10800,
		'6h'  => 21600,
		'24h' => 86400,
		'48h' => 172800,
		'72h' => 259200,
		'1w'  => 604800,
	);

	/**
	 * Register cron schedule + hook.
	 */
	public function register(): void {
		add_filter( 'cron_schedules', array( $this, 'addInterval' ) );
		add_action( self::HOOK, array( $this, 'process' ) );
		add_action( 'admin_init', array( $this, 'ensureScheduled' ) );
	}

	/**
	 * Add 5-min interval (reuses name from apollo-email if already present).
	 */
	public function addInterval( array $schedules ): array {
		if ( ! isset( $schedules['apollo_five_minutes'] ) ) {
			$schedules['apollo_five_minutes'] = array(
				'interval' => 300,
				'display'  => 'A cada 5 minutos (Apollo Gestor)',
			);
		}
		return $schedules;
	}

	/**
	 * Ensure cron event is scheduled.
	 */
	public function ensureScheduled(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), 'apollo_five_minutes', self::HOOK );
		}
	}

	/**
	 * Main processing — called by WP-Cron every 5 minutes.
	 *
	 * ONE-WAY SYSTEM RUN — execution flow:
	 *   1. CronLogger::start() → opens execution log row.
	 *   2. Query tasks with reminder_enabled=1 not yet in reminder_log.
	 *   3. For each: calculate reminder_at → check prefs → fire hook → log.
	 *   4. CronLogger::finish() → writes final timestamp + recipients JSON.
	 */
	public function process(): void {
		global $wpdb;

		// ── Open execution log entry in unified cron log ──────────
		$cron_log_id = 0;
		if ( class_exists( '\\Apollo\\Email\\Core\\CronLogger' ) ) {
			$cron_log_id = \Apollo\Email\Core\CronLogger::start( self::HOOK );
		}

		$tasks_table = $wpdb->prefix . 'apollo_gestor_tasks';
		$log_table   = $wpdb->prefix . 'apollo_gestor_reminder_log';

		// Get all reminder-eligible tasks not yet notified.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$tasks = $wpdb->get_results(
			"SELECT t.* FROM {$tasks_table} t
			 LEFT JOIN {$log_table} rl ON rl.task_id = t.id AND rl.user_id = t.assignee_id
			 WHERE t.reminder_enabled = 1
			   AND t.due_date IS NOT NULL
			   AND t.status NOT IN ('canceled','delivered')
			   AND t.assignee_id > 0
			   AND rl.id IS NULL
			 ORDER BY t.due_date ASC
			 LIMIT 100",
			ARRAY_A
		);

		if ( empty( $tasks ) ) {
			if ( $cron_log_id ) {
				\Apollo\Email\Core\CronLogger::finish( $cron_log_id );
			}
			return;
		}

		$now = time();

		foreach ( $tasks as $task ) {
			$offset_key     = $task['reminder_offset'] ?? '24h';
			$offset_seconds = self::OFFSETS[ $offset_key ] ?? self::OFFSETS['24h'];
			$due_timestamp  = strtotime( $task['due_date'] . ' 00:00:00' );

			if ( ! $due_timestamp ) {
				continue;
			}

			$reminder_at = $due_timestamp - $offset_seconds;

			if ( $now < $reminder_at ) {
				continue; // Not yet time.
			}

			$user_id = absint( $task['assignee_id'] );
			$user    = get_userdata( $user_id );

			if ( ! $user ) {
				$this->logReminder( $task, $user_id, 'skipped' );
				if ( $cron_log_id ) {
					\Apollo\Email\Core\CronLogger::addError( $cron_log_id, 'Task #' . $task['id'] . ' — user #' . $user_id . ' not found' );
				}
				continue;
			}

			// Check user email preference for gestor reminders.
			$prefs = get_user_meta( $user_id, '_apollo_email_prefs', true );
			if ( is_array( $prefs ) && isset( $prefs['gestor_reminders'] ) && empty( $prefs['gestor_reminders'] ) ) {
				$this->logReminder( $task, $user_id, 'skipped' );
				continue;
			}

			// Build email data and fire the hook (handled by apollo-email Plugin.php).
			$email_data = $this->buildEmailData( $task, $user );

			/**
			 * Fires when a task reminder should be sent.
			 *
			 * @since 1.1.0
			 * @param int   $user_id    Assignee user ID.
			 * @param array $email_data Merge tag data for the email template.
			 * @param array $task       Raw task row from DB.
			 */
			do_action( 'apollo/gestor/task_reminder', $user_id, $email_data, $task );

			// Log so we don't re-send.
			$this->logReminder( $task, $user_id, 'sent' );

			// Log to gestor activity.
			$this->logActivity( $task, $user_id );

			// ── Log recipient to unified cron execution log ───────
			if ( $cron_log_id ) {
				\Apollo\Email\Core\CronLogger::addRecipient(
					$cron_log_id,
					$user_id,
					$user->user_email,
					'task-reminder',
					$task['title']
				);
			}
		}

		// ── Close execution log entry ─────────────────────────────
		if ( $cron_log_id ) {
			\Apollo\Email\Core\CronLogger::finish( $cron_log_id );
		}
	}

	/**
	 * Build the merge-tag data array for the task-reminder email.
	 */
	private function buildEmailData( array $task, \WP_User $user ): array {
		$event_title = '';
		$event_url   = '';
		if ( ! empty( $task['event_id'] ) ) {
			$event = get_post( absint( $task['event_id'] ) );
			if ( $event ) {
				$event_title = $event->post_title;
				$event_url   = get_permalink( $event->ID );
			}
		}

		$creator_name = '';
		$creator_login = '';
		if ( ! empty( $task['created_by'] ) ) {
			$creator = get_userdata( absint( $task['created_by'] ) );
			if ( $creator ) {
				$creator_name  = $creator->display_name;
				$creator_login = $creator->user_login;
			}
		}

		$task_url = home_url( '/gestor/?event=' . absint( $task['event_id'] ) . '&task=' . absint( $task['id'] ) );

		return array(
			'user_id'            => $user->ID,
			'user_name'          => $user->display_name,
			'user_email'         => $user->user_email,
			'task_name'          => $task['title'],
			'task_date'          => wp_date( 'd/m/Y', strtotime( $task['due_date'] ) ),
			'task_deadline_label' => self::deadlineLabel( $task['due_date'] ),
			'task_priority'      => ucfirst( $task['priority'] ?? 'medium' ),
			'task_status'        => $task['status'],
			'task_project'       => $event_title,
			'task_project_url'   => $event_url ?: home_url( '/gestor/' ),
			'task_assigned_by'   => $creator_name,
			'assigned_by_url'    => $creator_login ? home_url( '/id/' . $creator_login ) : '',
			'task_url'           => $task_url,
			'task_id'            => absint( $task['id'] ),
			'event_id'           => absint( $task['event_id'] ),
			'site_name'          => get_bloginfo( 'name' ),
			'site_url'           => home_url( '/' ),
		);
	}

	/**
	 * Compute a human-friendly deadline label relative to today.
	 *
	 * @param string $due_date Date string (Y-m-d).
	 * @return string e.g. "Amanhã", "Hoje", "em 3 dias", "Atrasado 2 dias".
	 */
	public static function deadlineLabel( string $due_date ): string {
		$today = new \DateTimeImmutable( 'today', wp_timezone() );
		$due   = new \DateTimeImmutable( $due_date, wp_timezone() );
		$diff  = (int) $today->diff( $due )->format( '%r%a' );

		if ( $diff === 0 ) {
			return 'Hoje';
		}
		if ( $diff === 1 ) {
			return 'Amanhã';
		}
		if ( $diff > 1 ) {
			return sprintf( 'em %d dias', $diff );
		}
		// Past due
		$abs = abs( $diff );
		if ( $abs === 1 ) {
			return 'Atrasado 1 dia';
		}
		return sprintf( 'Atrasado %d dias', $abs );
	}

	/**
	 * Insert a record in the reminder log table.
	 */
	private function logReminder( array $task, int $user_id, string $status ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'apollo_gestor_reminder_log',
			array(
				'task_id'         => absint( $task['id'] ),
				'user_id'         => $user_id,
				'event_id'        => absint( $task['event_id'] ),
				'reminder_offset' => $task['reminder_offset'] ?? '24h',
				'email_status'    => $status,
				'sent_at'         => current_time( 'mysql' ),
				'meta'            => wp_json_encode( array(
					'task_title'  => $task['title'],
					'task_status' => $task['status'],
					'due_date'    => $task['due_date'],
					'priority'    => $task['priority'],
				) ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Log reminder activity in the gestor activity table.
	 */
	private function logActivity( array $task, int $user_id ): void {
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'apollo_gestor_activity',
			array(
				'event_id'    => absint( $task['event_id'] ),
				'user_id'     => $user_id,
				'action'      => 'reminder_sent',
				'entity_type' => 'task',
				'entity_id'   => absint( $task['id'] ),
				'meta'        => wp_json_encode( array(
					'offset'   => $task['reminder_offset'] ?? '24h',
					'due_date' => $task['due_date'],
				) ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s' )
		);
	}

	/**
	 * Unschedule the cron event (on plugin deactivation).
	 */
	public static function unschedule(): void {
		$ts = wp_next_scheduled( self::HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::HOOK );
		}
	}
}
