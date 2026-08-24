<?php

/**
 * Apollo Moderation — Main Plugin Class
 *
 * Content moderation queue, flagging, reports — adapted from BuddyPress bp-moderation.
 *
 * Registry compliance:
 *   REST: /mod/queue, /mod/queue/{id}/approve, /mod/queue/{id}/reject, /mod/queue/{id}/flag, /mod/log, /mod/stats
 *   Tables: apollo_mod_reports (core), apollo_mod_actions (core)
 *   Meta: _mod_status, _mod_notes, _mod_reviewed_by, _mod_reviewed_at
 *
 * @package Apollo\Mod
 */

declare(strict_types=1);

namespace Apollo\Mod;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {


	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// AJAX: report content from frontend
		add_action( 'wp_ajax_apollo_report_content', array( $this, 'ajax_report' ) );

		// Frontend report form (panel-forms.php hook)
		new FrontendForm();
	}

	// ─── REST API ──────────────────────────────────────────────────
	public function register_rest_routes(): void {
		$ns  = 'apollo/v1';
		$mod = function () {
			return current_user_can( 'apollo_moderate_content' ) || current_user_can( 'manage_options' );
		};

		register_rest_route(
			$ns,
			'/mod/queue',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_queue' ),
				'permission_callback' => $mod,
				'args'                => array(
					'status' => array(
						'default'           => 'pending',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			$ns,
			'/mod/queue/(?P<id>\d+)/approve',
			array(
				'methods'             => 'POST',
				'callback'            => function ( $r ) {
					return $this->rest_action( $r, 'approved' );
				},
				'permission_callback' => $mod,
			)
		);

		register_rest_route(
			$ns,
			'/mod/queue/(?P<id>\d+)/reject',
			array(
				'methods'             => 'POST',
				'callback'            => function ( $r ) {
					return $this->rest_action( $r, 'rejected' );
				},
				'permission_callback' => $mod,
			)
		);

		register_rest_route(
			$ns,
			'/mod/queue/(?P<id>\d+)/flag',
			array(
				'methods'             => 'POST',
				'callback'            => function ( $r ) {
					return $this->rest_action( $r, 'flagged' );
				},
				'permission_callback' => $mod,
			)
		);

		register_rest_route(
			$ns,
			'/mod/log',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_log' ),
				'permission_callback' => $mod,
			)
		);

		register_rest_route(
			$ns,
			'/mod/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'rest_get_stats' ),
				'permission_callback' => $mod,
			)
		);

		// Public report endpoint
		register_rest_route(
			$ns,
			'/mod/report',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_report' ),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);
	}

	public function rest_get_queue( \WP_REST_Request $request ): \WP_REST_Response {
		$status = $request->get_param( 'status' );
		$queue  = apollo_get_mod_queue( $status );
		return new \WP_REST_Response( $queue, 200 );
	}

	private function rest_action( \WP_REST_Request $request, string $action ): \WP_REST_Response {
		$report_id    = (int) $request->get_param( 'id' );
		$moderator_id = get_current_user_id();

		// Get report details
		global $wpdb;
		$report = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_mod_reports WHERE id = %d",
				$report_id
			),
			ARRAY_A
		);

		if ( ! $report ) {
			return new \WP_REST_Response( array( 'error' => 'Report não encontrado' ), 404 );
		}

		// Resolve report
		apollo_resolve_report( $report_id, $moderator_id, 'actioned', $action );

		// Log mod action on the actual content
		apollo_mod_action(
			$moderator_id,
			$action,
			$report['object_type'],
			(int) $report['object_id'],
			sanitize_text_field( $request->get_param( 'reason' ) ?? $action )
		);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'action'  => $action,
			),
			200
		);
	}

	public function rest_get_log( \WP_REST_Request $request ): \WP_REST_Response {
		global $wpdb;
		$table = $wpdb->prefix . 'apollo_mod_actions';
		$log   = $wpdb->get_results(
			"SELECT a.*, u.display_name as moderator_name
             FROM {$table} a
             LEFT JOIN {$wpdb->users} u ON a.moderator_id = u.ID
             ORDER BY a.created_at DESC LIMIT 50",
			ARRAY_A
		);
		return new \WP_REST_Response( $log ?: array(), 200 );
	}

	public function rest_get_stats(): \WP_REST_Response {
		return new \WP_REST_Response( apollo_get_mod_stats(), 200 );
	}

	public function rest_report( \WP_REST_Request $request ): \WP_REST_Response {
		$result = apollo_report_content(
			get_current_user_id(),
			sanitize_text_field( $request->get_param( 'object_type' ) ?? '' ),
			(int) ( $request->get_param( 'object_id' ) ?? 0 ),
			sanitize_text_field( $request->get_param( 'reason' ) ?? '' ),
			sanitize_textarea_field( $request->get_param( 'details' ) ?? '' )
		);

		if ( $result ) {
			return new \WP_REST_Response( array( 'report_id' => $result ), 201 );
		}
		return new \WP_REST_Response( array( 'error' => 'Erro ao reportar' ), 500 );
	}

	// ─── AJAX ────────────────────────────────────────────────────────
	public function ajax_report(): void {
		check_ajax_referer( 'apollo_mod_nonce', 'nonce' );

		$result = apollo_report_content(
			get_current_user_id(),
			sanitize_text_field( $_POST['object_type'] ?? '' ),
			(int) ( $_POST['object_id'] ?? 0 ),
			sanitize_text_field( $_POST['reason'] ?? '' ),
			sanitize_textarea_field( $_POST['details'] ?? '' )
		);

		wp_send_json(
			array(
				'success'   => (bool) $result,
				'report_id' => $result,
			)
		);
	}

	// ─── Admin Menu ──────────────────────────────────────────────────
	public function add_admin_menu(): void {
		add_submenu_page(
			'apollo',
			'Moderação',
			'Moderação',
			'manage_options',
			'apollo-mod',
			array( $this, 'render_admin_page' )
		);
	}

	public function render_admin_page(): void {
		$stats = apollo_get_mod_stats();
		$queue = apollo_get_mod_queue( 'pending' );
		?>
		<div class="wrap">
			<h1>Apollo Moderação</h1>
			<div style="display:flex;gap:1rem;margin:1rem 0;">
				<div style="background:#fff;padding:1rem;border-radius:8px;border:1px solid #ddd;flex:1;">
					<strong><?php echo $stats['pending']; ?></strong><br><small>Pendentes</small>
				</div>
				<div style="background:#fff;padding:1rem;border-radius:8px;border:1px solid #ddd;flex:1;">
					<strong><?php echo $stats['actioned']; ?></strong><br><small>Resolvidos</small>
				</div>
				<div style="background:#fff;padding:1rem;border-radius:8px;border:1px solid #ddd;flex:1;">
					<strong><?php echo $stats['total']; ?></strong><br><small>Total</small>
				</div>
			</div>

			<h2>Fila de Moderação</h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>ID</th>
						<th>Tipo</th>
						<th>Razão</th>
						<th>Reporter</th>
						<th>Data</th>
						<th>Ações</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $queue as $report ) : ?>
						<tr>
							<td><?php echo $report['id']; ?></td>
							<td><?php echo esc_html( $report['object_type'] ); ?> #<?php echo $report['object_id']; ?></td>
							<td><?php echo esc_html( $report['reason'] ); ?></td>
							<td><?php echo esc_html( $report['reporter_name'] ?? 'N/A' ); ?></td>
							<td><?php echo esc_html( $report['created_at'] ); ?></td>
							<td>
								<button class="button" onclick="apolloMod(<?php echo $report['id']; ?>,'approve')">Aprovar</button>
								<button class="button" onclick="apolloMod(<?php echo $report['id']; ?>,'reject')">Rejeitar</button>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if ( empty( $queue ) ) : ?>
						<tr>
							<td colspan="6" style="text-align:center;">Nenhum report pendente.</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<script>
			function apolloMod(id, action) {
				fetch('<?php echo esc_url( rest_url( 'apollo/v1/mod/queue/' ) ); ?>' + id + '/' + action, {
					method: 'POST',
					headers: {
						'X-WP-Nonce': '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>'
					},
					credentials: 'same-origin'
				}).then(r => r.json()).then(d => {
					if (d.success) location.reload();
				});
			}
		</script>
		<?php
	}
}
