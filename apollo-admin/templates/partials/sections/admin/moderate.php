<?php
/**
 * Admin Section — Moderation (Flagged Users)
 *
 * Page ID: page-admin-moderate
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-admin-moderate">
	<div class="filter-bar">
		<input type="text" class="apollo-input input" placeholder="<?php esc_attr_e( 'Search users to moderate...', 'apollo-admin' ); ?>">
		<select class="select">
			<option><?php esc_html_e( 'All Flags', 'apollo-admin' ); ?></option>
			<option><?php esc_html_e( 'Reported', 'apollo-admin' ); ?></option>
			<option><?php esc_html_e( 'Suspended', 'apollo-admin' ); ?></option>
			<option><?php esc_html_e( 'Under Review', 'apollo-admin' ); ?></option>
		</select>
		<button class="btn btn-outline"><i class="ri-filter-3-line"></i> <?php esc_html_e( 'Filter', 'apollo-admin' ); ?></button>
	</div>
	<div class="panel">
		<div class="panel-header"><i class="ri-alarm-warning-fill"></i> <?php esc_html_e( 'Flagged Users', 'apollo-admin' ); ?> <span class="badge" id="flagged-count"></span></div>
		<div class="panel-body" style="padding:0">
			<table class="data-table" id="flagged-users-table">
				<thead><tr>
					<th><?php esc_html_e( 'User', 'apollo-admin' ); ?></th>
					<th><?php esc_html_e( 'Reason', 'apollo-admin' ); ?></th>
					<th><?php esc_html_e( 'Reports', 'apollo-admin' ); ?></th>
					<th><?php esc_html_e( 'Date', 'apollo-admin' ); ?></th>
					<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'apollo-admin' ); ?></th>
				</tr></thead>
				<tbody>
					<!-- Dynamic content loaded via REST API -->
					<tr><td colspan="6" style="text-align:center;color:var(--gray-9);padding:40px"><?php esc_html_e( 'No flagged users at this time.', 'apollo-admin' ); ?></td></tr>
				</tbody>
			</table>
		</div>
	</div>
</div>