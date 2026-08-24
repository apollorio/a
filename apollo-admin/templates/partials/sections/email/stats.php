<?php
/**
 * Email Section — Statistics
 *
 * Page ID: page-email-stats
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-email-stats">
	<div class="stats-grid">
		<div class="stat-card orange"><div class="stat-icon orange"><i class="ri-send-plane-2-line"></i></div><span class="stat-label"><?php esc_html_e( 'Campaigns Sent', 'apollo-admin' ); ?></span><span class="stat-value">—</span></div>
		<div class="stat-card green"><div class="stat-icon green"><i class="ri-mail-open-line"></i></div><span class="stat-label"><?php esc_html_e( 'Total Opens', 'apollo-admin' ); ?></span><span class="stat-value">—</span></div>
		<div class="stat-card blue"><div class="stat-icon blue"><i class="ri-cursor-line"></i></div><span class="stat-label"><?php esc_html_e( 'Total Clicks', 'apollo-admin' ); ?></span><span class="stat-value">—</span></div>
		<div class="stat-card red"><div class="stat-icon red"><i class="ri-spam-2-line"></i></div><span class="stat-label"><?php esc_html_e( 'Bounce Rate', 'apollo-admin' ); ?></span><span class="stat-value">—</span></div>
	</div>

	<div class="panel">
		<div class="panel-header"><i class="ri-bar-chart-box-line"></i> <?php esc_html_e( 'Campaign Performance', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<div class="chart-placeholder" style="height:280px">
				<i class="ri-line-chart-line" style="font-size:28px;margin-right:10px"></i>
				<?php esc_html_e( 'Email campaign performance chart renders here', 'apollo-admin' ); ?>
			</div>
		</div>
	</div>

	<div class="panel">
		<div class="panel-header"><i class="ri-history-line"></i> <?php esc_html_e( 'Campaign History', 'apollo-admin' ); ?></div>
		<div class="panel-body" style="padding:0">
			<table class="data-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Campaign', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Sent', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Delivered', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Opened', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Clicked', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Unsub', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Date', 'apollo-admin' ); ?></th>
					</tr>
				</thead>
				<tbody id="apollo-email-campaign-body">
					<tr>
						<td colspan="7" style="text-align:center;padding:40px;color:var(--c-muted)">
							<?php esc_html_e( 'No campaigns sent yet', 'apollo-admin' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
