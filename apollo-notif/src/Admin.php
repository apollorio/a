<?php

/**
 * Apollo Notif — Admin Panel
 *
 * Provides a WP-admin page with:
 *  - Stats by notification type (top 10)
 *  - Live notification log with filters
 *  - Bulk actions (purge read, purge expired)
 *
 * @package Apollo\Notif
 */

declare(strict_types=1);

namespace Apollo\Notif;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin {

	/**
	 * Register admin menu page under the Apollo hub.
	 * Called from Plugin::register_admin_page() via admin_menu hook.
	 */
	public static function register_menu(): void {
		// Try to attach to Apollo admin hub first; fall back to standalone.
		$parent = menu_page_url( 'apollo-hub', false ) ? 'apollo-hub' : null;

		if ( $parent ) {
			add_submenu_page(
				'apollo-hub',
				__( 'Notificações — Log', 'apollo-notif' ),
				__( 'Notificações', 'apollo-notif' ),
				'manage_options',
				'apollo-notif-log',
				array( self::class, 'render_page' )
			);
		} else {
			add_menu_page(
				__( 'Notificações — Log', 'apollo-notif' ),
				__( 'Apollo Notif', 'apollo-notif' ),
				'manage_options',
				'apollo-notif-log',
				array( self::class, 'render_page' ),
				'dashicons-bell',
				30
			);
		}
	}

	/**
	 * Render the admin page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Acesso negado.', 'apollo-notif' ) );
		}

		// Handle bulk actions.
		if ( isset( $_POST['apollo_notif_action'], $_POST['_wpnonce'] ) ) {
			self::handle_bulk_action();
		}

		global $wpdb;
		$table = $wpdb->prefix . 'apollo_notifications';

		// Filters from GET.
		$filter_type   = sanitize_key( $_GET['filter_type'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$filter_user   = absint( $_GET['filter_user'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		$filter_status = sanitize_key( $_GET['filter_status'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$paged         = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$per_page      = 30;
		$offset        = ( $paged - 1 ) * $per_page;

		// ── Stats ───────────────────────────────────────────────────────
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		// Admin-only analytics reads. Only the trusted table name is interpolated (no user input);
		// table identifiers cannot be bound via prepare(). Real-time admin stats are intentionally uncached.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$stats = $wpdb->get_results(
			"SELECT type, COUNT(*) as total, SUM(is_read = 0) as unread
			 FROM {$table}
			 GROUP BY type
			 ORDER BY total DESC
			 LIMIT 15",
			ARRAY_A
		);

		$total_notifs  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		$total_unread  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_read = 0" );
		$total_expired = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE expires_at IS NOT NULL AND expires_at < NOW()" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// ── Log query ────────────────────────────────────────────────────
		$conditions = array( '1=1' );
		if ( $filter_type ) {
			$conditions[] = $wpdb->prepare( 'type = %s', $filter_type );
		}
		if ( $filter_user ) {
			$conditions[] = $wpdb->prepare( 'user_id = %d', $filter_user );
		}
		if ( $filter_status === 'unread' ) {
			$conditions[] = 'is_read = 0';
		} elseif ( $filter_status === 'read' ) {
			$conditions[] = 'is_read = 1';
		}
		$where = 'WHERE ' . implode( ' AND ', $conditions );

		$total_filtered = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$log            = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT n.id, n.user_id, n.type, n.title, n.message, n.severity, n.is_read, n.created_at, u.display_name
				 FROM {$table} n
				 LEFT JOIN {$wpdb->users} u ON u.ID = n.user_id
				 {$where}
				 ORDER BY n.created_at DESC
				 LIMIT %d OFFSET %d",
				$per_page,
				$offset
			),
			ARRAY_A
		);
		// phpcs:enable

		$total_pages = (int) ceil( $total_filtered / $per_page );
		$nonce       = wp_create_nonce( 'apollo_notif_admin_action' );

		// ── Render ───────────────────────────────────────────────────────
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apollo Notificações — Log & Stats', 'apollo-notif' ); ?></h1>

			<!-- Summary cards -->
			<div style="display:flex;gap:16px;margin:16px 0;flex-wrap:wrap;">
				<?php
				self::stat_card( __( 'Total', 'apollo-notif' ), (string) $total_notifs, '#0073aa' );
				self::stat_card( __( 'Não lidas', 'apollo-notif' ), (string) $total_unread, '#d63638' );
				self::stat_card( __( 'Expiradas', 'apollo-notif' ), (string) $total_expired, '#dba617' );
				?>
			</div>

			<!-- Bulk actions -->
			<form method="post" style="margin-bottom:16px;">
				<?php wp_nonce_field( 'apollo_notif_admin_action' ); ?>
				<input type="hidden" name="apollo_notif_action" value="">
				<button type="submit" class="button button-secondary"
					onclick="document.querySelector('input[name=apollo_notif_action]').value='purge_read';return confirm('Deletar todas as notificações lidas?')">
					<?php esc_html_e( 'Purgar lidas (todas)', 'apollo-notif' ); ?>
				</button>
				&nbsp;
				<button type="submit" class="button button-secondary"
					onclick="document.querySelector('input[name=apollo_notif_action]').value='purge_expired';return confirm('Deletar todas as expiradas?')">
					<?php esc_html_e( 'Purgar expiradas', 'apollo-notif' ); ?>
				</button>
			</form>

			<!-- Types stats -->
			<h2><?php esc_html_e( 'Top tipos', 'apollo-notif' ); ?></h2>
			<table class="widefat striped" style="max-width:600px;margin-bottom:24px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Tipo', 'apollo-notif' ); ?></th>
						<th><?php esc_html_e( 'Total', 'apollo-notif' ); ?></th>
						<th><?php esc_html_e( 'Não lidas', 'apollo-notif' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $stats ) ) : ?>
					<tr><td colspan="3"><?php esc_html_e( 'Nenhuma notificação ainda.', 'apollo-notif' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $stats as $row ) : ?>
						<tr>
							<td><code><?php echo esc_html( $row['type'] ); ?></code></td>
							<td><?php echo esc_html( $row['total'] ); ?></td>
							<td><?php echo esc_html( $row['unread'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<!-- Log filters -->
			<h2><?php esc_html_e( 'Log de notificações', 'apollo-notif' ); ?></h2>
			<form method="get" style="margin-bottom:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
				<input type="hidden" name="page" value="apollo-notif-log">
				<input type="text" name="filter_type" placeholder="<?php esc_attr_e( 'Tipo (slug)', 'apollo-notif' ); ?>"
					value="<?php echo esc_attr( $filter_type ); ?>" class="regular-text" style="width:160px;">
				<input type="number" name="filter_user" placeholder="<?php esc_attr_e( 'User ID', 'apollo-notif' ); ?>"
					value="<?php echo esc_attr( (string) $filter_user ); ?>" style="width:100px;">
				<select name="filter_status">
					<option value=""><?php esc_html_e( 'Todos', 'apollo-notif' ); ?></option>
					<option value="unread" <?php selected( $filter_status, 'unread' ); ?>><?php esc_html_e( 'Não lidas', 'apollo-notif' ); ?></option>
					<option value="read" <?php selected( $filter_status, 'read' ); ?>><?php esc_html_e( 'Lidas', 'apollo-notif' ); ?></option>
				</select>
				<button type="submit" class="button"><?php esc_html_e( 'Filtrar', 'apollo-notif' ); ?></button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-notif-log' ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Limpar', 'apollo-notif' ); ?>
				</a>
			</form>

			<p><?php printf( esc_html__( '%d resultado(s) encontrado(s)', 'apollo-notif' ), $total_filtered ); ?></p>

			<!-- Log table -->
			<table class="widefat striped">
				<thead>
					<tr>
						<th>ID</th>
						<th><?php esc_html_e( 'Usuário', 'apollo-notif' ); ?></th>
						<th><?php esc_html_e( 'Tipo', 'apollo-notif' ); ?></th>
						<th><?php esc_html_e( 'Título', 'apollo-notif' ); ?></th>
						<th><?php esc_html_e( 'Severidade', 'apollo-notif' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-notif' ); ?></th>
						<th><?php esc_html_e( 'Criada em', 'apollo-notif' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $log ) ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'Nenhuma notificação encontrada.', 'apollo-notif' ); ?></td></tr>
				<?php else : ?>
					<?php
					foreach ( $log as $row ) :
						$sev_colors = array(
							'info'    => '#0073aa',
							'success' => '#00a32a',
							'warning' => '#dba617',
							'alert'   => '#d63638',
						);
						$sev        = $row['severity'] ?? 'info';
						$color      = $sev_colors[ $sev ] ?? '#0073aa';
						?>
						<tr>
							<td><?php echo esc_html( $row['id'] ); ?></td>
							<td>
								<?php if ( $row['display_name'] ) : ?>
									<a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $row['user_id'] ) ); ?>">
										<?php echo esc_html( $row['display_name'] ); ?>
									</a>
								<?php else : ?>
									<code>#<?php echo esc_html( $row['user_id'] ); ?></code>
								<?php endif; ?>
							</td>
							<td><code><?php echo esc_html( $row['type'] ); ?></code></td>
							<td><?php echo esc_html( wp_trim_words( $row['title'], 10 ) ); ?></td>
							<td><span style="color:<?php echo esc_attr( $color ); ?>;font-weight:600;"><?php echo esc_html( $sev ); ?></span></td>
							<td>
								<?php if ( $row['is_read'] ) : ?>
									<span style="color:#00a32a;">✔ <?php esc_html_e( 'Lida', 'apollo-notif' ); ?></span>
								<?php else : ?>
									<span style="color:#d63638;">● <?php esc_html_e( 'Não lida', 'apollo-notif' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $row['created_at'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<div style="margin-top:12px;">
					<?php
					for ( $i = 1; $i <= $total_pages; $i++ ) {
						$url = add_query_arg(
							array(
								'page'          => 'apollo-notif-log',
								'paged'         => $i,
								'filter_type'   => $filter_type,
								'filter_user'   => $filter_user,
								'filter_status' => $filter_status,
							),
							admin_url( 'admin.php' )
						);
						printf(
							'<a href="%s" class="button%s" style="margin-right:4px;">%d</a>',
							esc_url( $url ),
							$i === $paged ? ' button-primary' : '',
							$i
						);
					}
					?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle form-based bulk actions.
	 */
	private static function handle_bulk_action(): void {
		if ( ! check_admin_referer( 'apollo_notif_admin_action' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$table  = $wpdb->prefix . 'apollo_notifications';
		$action = sanitize_key( $_POST['apollo_notif_action'] ?? '' );

		if ( $action === 'purge_read' ) {
			// Trusted table name only; no user input. Guarded by check_admin_referer + manage_options above.
			$wpdb->query( "DELETE FROM {$table} WHERE is_read = 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Notificações lidas purgadas.', 'apollo-notif' ) . '</p></div>';
				}
			);
		} elseif ( $action === 'purge_expired' ) {
			if ( function_exists( 'apollo_cleanup_expired_notifications' ) ) {
				apollo_cleanup_expired_notifications();
			}
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Notificações expiradas purgadas.', 'apollo-notif' ) . '</p></div>';
				}
			);
		}
	}

	/**
	 * Render a summary stat card.
	 */
	private static function stat_card( string $label, string $value, string $color ): void {
		printf(
			'<div style="background:#fff;border:1px solid #c3c4c7;border-left:4px solid %s;padding:12px 20px;border-radius:4px;min-width:120px;">' .
			'<div style="font-size:2rem;font-weight:700;color:%s;">%s</div>' .
			'<div style="color:#50575e;font-size:.85rem;">%s</div>' .
			'</div>',
			esc_attr( $color ),
			esc_attr( $color ),
			esc_html( $value ),
			esc_html( $label )
		);
	}
}