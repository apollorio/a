<?php
/**
 * Email Section — Unsubscribes
 *
 * Page ID: page-email-unsub
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-email-unsub">
	<div class="panel">
		<div class="panel-header"><i class="ri-user-unfollow-line"></i> <?php esc_html_e( 'Unsubscribe Report', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<div class="chart-placeholder" style="height:200px">
				<i class="ri-line-chart-line" style="font-size:24px;margin-right:8px"></i>
				<?php esc_html_e( 'Unsubscribe trend chart renders here', 'apollo-admin' ); ?>
			</div>
		</div>
	</div>
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search...', 'apollo-admin' ); ?>">
			<span class="spreadsheet-count">— <?php esc_html_e( 'unsubscribes', 'apollo-admin' ); ?></span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Email', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Reason', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Segment', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Date', 'apollo-admin' ); ?></th>
					</tr>
				</thead>
				<tbody id="apollo-unsub-body">
					<tr>
						<td colspan="4" style="text-align:center;padding:40px;color:var(--c-muted)">
							<?php esc_html_e( 'No unsubscribes recorded', 'apollo-admin' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>