<?php
/**
 * Email Section — Subscribers
 *
 * Page ID: page-email-subscribers
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-email-subscribers">
	<div class="stats-grid">
		<div class="stat-card green"><div class="stat-icon green"><i class="ri-mail-check-line"></i></div><span class="stat-label"><?php esc_html_e( 'Total Subscribers', 'apollo-admin' ); ?></span><span class="stat-value">—</span></div>
		<div class="stat-card blue"><div class="stat-icon blue"><i class="ri-mail-open-line"></i></div><span class="stat-label"><?php esc_html_e( 'Avg Open Rate', 'apollo-admin' ); ?></span><span class="stat-value">—</span></div>
		<div class="stat-card orange"><div class="stat-icon orange"><i class="ri-cursor-line"></i></div><span class="stat-label"><?php esc_html_e( 'Avg Click Rate', 'apollo-admin' ); ?></span><span class="stat-value">—</span></div>
		<div class="stat-card red"><div class="stat-icon red"><i class="ri-user-unfollow-line"></i></div><span class="stat-label"><?php esc_html_e( 'Unsubscribes', 'apollo-admin' ); ?></span><span class="stat-value">—</span></div>
	</div>
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search subscribers...', 'apollo-admin' ); ?>">
			<select class="select" style="height:32px;font-size:11px;width:160px">
				<option><?php esc_html_e( 'All Segments', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Newsletter General', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Event Alerts', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Social Digest', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'DJ/Artist Updates', 'apollo-admin' ); ?></option>
			</select>
			<button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Export', 'apollo-admin' ); ?>"><i class="ri-download-line"></i></button>
			<span class="spreadsheet-count">— <?php esc_html_e( 'subscribers', 'apollo-admin' ); ?></span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th style="width:30px"><input type="checkbox"></th>
						<th><?php esc_html_e( 'Email', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Name', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Segments', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Subscribed', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Opens', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
					</tr>
				</thead>
				<tbody id="apollo-subscribers-body">
					<tr>
						<td colspan="7" style="text-align:center;padding:40px;color:var(--c-muted)">
							<i class="ri-loader-4-line ri-spin" style="font-size:24px"></i><br>
							<?php esc_html_e( 'Loading subscribers...', 'apollo-admin' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>