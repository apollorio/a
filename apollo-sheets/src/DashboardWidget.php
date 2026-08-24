<?php

/**
 * DashboardWidget — WordPress admin dashboard widget for Apollo Sheets
 *
 * Shows: total sheet count, recently modified sheets, quick-add link.
 * Inspired by uipress-lite dashboard block concepts applied to Apollo ecosystem.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DashboardWidget {


	/**
	 * Register the dashboard widget
	 */
	public function register(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'add_widget' ) );
	}

	/**
	 * Add the widget to the dashboard
	 */
	public function add_widget(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'apollo_sheets_dashboard_widget',
			__( 'Apollo Sheets', 'apollo-sheets' ),
			array( $this, 'render_widget' ),
			array( $this, 'render_widget_control' )
		);
	}

	/**
	 * Render the widget content
	 */
	public function render_widget(): void {
		$model  = new Model();
		$sheets = $model->load_all() ?: array();
		$total  = count( $sheets );

		// Sort by last_modified descending
		usort(
			$sheets,
			fn( $a, $b ) => strtotime( $b['last_modified'] ?? '0' ) <=> strtotime( $a['last_modified'] ?? '0' )
		);
		$recent = array_slice( $sheets, 0, 5 );

		?>
		<style>
			#apollo_sheets_dashboard_widget .apollo-dw-stats {
				display: flex;
				gap: 16px;
				margin-bottom: 12px;
			}

			#apollo_sheets_dashboard_widget .apollo-dw-stat {
				background: #f0f6fc;
				border-left: 3px solid #007cba;
				padding: 8px 12px;
				flex: 1;
				border-radius: 3px;
			}

			#apollo_sheets_dashboard_widget .apollo-dw-stat strong {
				display: block;
				font-size: 22px;
				line-height: 1.2;
				color: #007cba;
			}

			#apollo_sheets_dashboard_widget .apollo-dw-stat span {
				font-size: 11px;
				text-transform: uppercase;
				color: #555;
				letter-spacing: .5px;
			}

			#apollo_sheets_dashboard_widget .apollo-dw-list {
				margin: 0;
				padding: 0;
				list-style: none;
			}

			#apollo_sheets_dashboard_widget .apollo-dw-list li {
				display: flex;
				align-items: center;
				justify-content: space-between;
				padding: 6px 0;
				border-bottom: 1px solid #f0f0f0;
				font-size: 13px;
			}

			#apollo_sheets_dashboard_widget .apollo-dw-list li:last-child {
				border-bottom: none;
			}

			#apollo_sheets_dashboard_widget .apollo-dw-list .dw-sheet-meta {
				color: #888;
				font-size: 11px;
			}

			#apollo_sheets_dashboard_widget .apollo-dw-actions {
				margin-top: 12px;
				display: flex;
				gap: 8px;
			}
		</style>

		<div class="apollo-dw-stats">
			<div class="apollo-dw-stat">
				<strong><?php echo esc_html( $total ); ?></strong>
				<span><?php esc_html_e( 'Total de Sheets', 'apollo-sheets' ); ?></span>
			</div>
			<div class="apollo-dw-stat">
				<?php
				// Count sheets modified in last 7 days
				$recent_count = count(
					array_filter(
						$sheets,
						fn( $s ) => ! empty( $s['last_modified'] ) && strtotime( $s['last_modified'] ) > strtotime( '-7 days' )
					)
				);
				?>
				<strong><?php echo esc_html( $recent_count ); ?></strong>
				<span><?php esc_html_e( 'Últ. 7 dias', 'apollo-sheets' ); ?></span>
			</div>
		</div>

		<?php if ( ! empty( $recent ) ) : ?>
			<h4 style="margin:0 0 8px;font-size:12px;text-transform:uppercase;color:#555;letter-spacing:.5px;">
				<?php esc_html_e( 'Recentes', 'apollo-sheets' ); ?>
			</h4>
			<ul class="apollo-dw-list">
				<?php foreach ( $recent as $sheet ) : ?>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-sheets-add&sheet_id=' . $sheet['id'] ) ); ?>">
							<?php echo esc_html( $sheet['name'] ?: __( '(sem nome)', 'apollo-sheets' ) ); ?>
							<code style="font-size:10px;color:#888;margin-left:4px;">#<?php echo esc_html( $sheet['id'] ); ?></code>
						</a>
						<span class="dw-sheet-meta">
							<?php
							if ( ! empty( $sheet['last_modified'] ) ) {
								echo function_exists( 'apollo_time_ago_html' )
									? wp_kses_post( apollo_time_ago_html( $sheet['last_modified'] ) )
									: esc_html( apollo_time_ago( $sheet['last_modified'] ) );
							}
							?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p style="color:#888;font-style:italic;"><?php esc_html_e( 'Nenhuma sheet criada ainda.', 'apollo-sheets' ); ?></p>
		<?php endif; ?>

		<div class="apollo-dw-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-sheets-add' ) ); ?>" class="button button-primary button-small">
				<?php esc_html_e( '+ Nova Sheet', 'apollo-sheets' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-sheets' ) ); ?>" class="button button-small">
				<?php esc_html_e( 'Ver Todas', 'apollo-sheets' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-sheets-import' ) ); ?>" class="button button-small">
				<?php esc_html_e( 'Importar', 'apollo-sheets' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Render widget control (settings form) — shown in "Screen Options"
	 */
	public function render_widget_control(): void {
		?>
		<p style="color:#888;font-size:12px;"><?php esc_html_e( 'Exibe resumo de sheets recentes e atalhos rápidos.', 'apollo-sheets' ); ?></p>
		<?php
	}
}
