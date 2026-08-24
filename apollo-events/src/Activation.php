<?php
/**
 * Ativação do plugin
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activation {

	/**
	 * Executa na ativação do plugin
	 */
	public static function activate(): void {
		self::set_defaults();
		self::create_pages();
		self::create_tables();

		// Agenda cron de expiração
		if ( ! wp_next_scheduled( 'apollo_event_check_expiration' ) ) {
			wp_schedule_event( time(), 'every_five_minutes', 'apollo_event_check_expiration' );
		}
	}

	/**
	 * Cria tabelas customizadas: RSVP/attendees
	 */
	public static function create_tables(): void {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$table   = $wpdb->prefix . 'apollo_event_rsvp';

		$sql = "CREATE TABLE {$table} (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id    BIGINT UNSIGNED NOT NULL,
			user_id     BIGINT UNSIGNED NOT NULL,
			status      VARCHAR(20) NOT NULL DEFAULT 'going',
			checked_in  TINYINT(1) NOT NULL DEFAULT 0,
			created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY event_user (event_id, user_id),
			KEY event_id (event_id),
			KEY user_id (user_id),
			KEY status (status)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Cria páginas virtuais conforme apollo-registry.json
	 * - criar-evento → [apollo_event_form]
	 */
	private static function create_pages(): void {
		$pages = array(
			array(
				'slug'    => 'criar-evento',
				'title'   => 'Criar Evento',
				'content' => '[apollo_event_form]',
			),
		);

		foreach ( $pages as $page_data ) {
			// Check if page already exists by slug (replaces deprecated get_page_by_path)
			$existing = new \WP_Query(
				array(
					'post_type'              => 'page',
					'name'                   => $page_data['slug'],
					'posts_per_page'         => 1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			if ( $existing->have_posts() ) {
				continue;
			}

			wp_insert_post(
				array(
					'post_title'   => $page_data['title'],
					'post_name'    => $page_data['slug'],
					'post_content' => $page_data['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_author'  => get_current_user_id() ?: 1,
					'meta_input'   => array(
						'_apollo_virtual_page' => true,
						'_apollo_require_auth' => true,
					),
				)
			);
		}
	}

	/**
	 * Define opções padrão
	 */
	private static function set_defaults(): void {
		$defaults = array(
			'default_style'       => 'base',
			'enable_osm_map'      => true,
			'enable_expiration'   => true,
			'show_gone_events'    => true,
			'styles_enabled'      => array( 'base', 'apollo-v1', 'ui-thim', 'ui-lis' ),
			'gone_offset_minutes' => 30,
		);

		if ( ! get_option( 'apollo_event_settings' ) ) {
			update_option( 'apollo_event_settings', $defaults );
		}
	}
}
