<?php
/**
 * Nucleo manager UI — assign users as agents.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler\Admin;

use Apollo\Scheduler\Models\AgentModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AgentAssignment {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function register_menu(): void {
		add_submenu_page(
			'users.php',
			__( 'Apollo Agents', 'apollo-scheduler' ),
			__( 'Apollo Agents', 'apollo-scheduler' ),
			'edit_users',
			'apollo-scheduler-agents',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_admin_assets( string $hook ): void {
		if ( 'users_page_apollo-scheduler-agents' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'apollo-scheduler-admin',
			APOLLO_SCHEDULER_URL . 'assets/css/scheduler.css',
			array(),
			APOLLO_SCHEDULER_VERSION
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'edit_users' ) ) {
			return;
		}

		if ( isset( $_POST['apollo_scheduler_assign_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_scheduler_assign_nonce'] ) ), 'apollo_scheduler_assign' ) ) {
			$user_id   = absint( $_POST['user_id'] ?? 0 );
			$nucleo_id = absint( $_POST['nucleo_id'] ?? 0 );

			if ( $user_id && $nucleo_id ) {
				( new AgentModel() )->assign( $user_id, $nucleo_id );
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Agent assigned.', 'apollo-scheduler' ) . '</p></div>';
			}
		}

		$config = wp_json_encode(
			array(
				'restUrl'   => esc_url_raw( rest_url( APOLLO_SCHEDULER_REST_NAMESPACE . '/' . APOLLO_SCHEDULER_REST_BASE ) ),
				'restNonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
		?>
		<div class="wrap apollo-scheduler-admin">
			<h1><?php esc_html_e( 'Assign Apollo Agents', 'apollo-scheduler' ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'apollo_scheduler_assign', 'apollo_scheduler_assign_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="user_id"><?php esc_html_e( 'User ID', 'apollo-scheduler' ); ?></label></th>
						<td><input type="number" name="user_id" id="user_id" required /></td>
					</tr>
					<tr>
						<th><label for="nucleo_id"><?php esc_html_e( 'Nucleo ID', 'apollo-scheduler' ); ?></label></th>
						<td><input type="number" name="nucleo_id" id="nucleo_id" required /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Assign as Agent', 'apollo-scheduler' ) ); ?>
			</form>
			<script type="application/json" id="apollo-scheduler-admin-config"><?php echo esc_html( (string) $config ); ?></script>
		</div>
		<?php
	}
}
