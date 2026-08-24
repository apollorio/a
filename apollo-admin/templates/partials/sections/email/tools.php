<?php
/**
 * Email Section — Tools (Queue Management + Data Tools)
 *
 * Page ID: page-email-tools
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-email-tools">
	<div class="two-cols">
		<div class="panel">
			<div class="panel-header"><i class="ri-tools-line"></i> <?php esc_html_e( 'Queue Management', 'apollo-admin' ); ?></div>
			<div class="panel-body">
				<p style="margin-bottom:12px;color:var(--gray-1)"><?php esc_html_e( 'Manage the email send queue. Clear stuck emails or retry failed ones.', 'apollo-admin' ); ?></p>
				<div style="display:flex;gap:8px;flex-wrap:wrap">
					<button class="btn btn-outline" type="button" title="<?php esc_attr_e( 'Retry Failed', 'apollo-admin' ); ?>"><i class="ri-refresh-line"></i></button>
					<button class="btn btn-danger" type="button" title="<?php esc_attr_e( 'Clear Queue', 'apollo-admin' ); ?>"><i class="ri-delete-bin-line"></i></button>
				</div>
				<div style="margin-top:16px;padding:12px;background:var(--card);border-radius:var(--r-xs);font-family:var(--ff-mono);font-size:11px;color:var(--gray-1)">
					<?php esc_html_e( 'Queue: 0 pending · 0 processing · 0 completed · 0 failed', 'apollo-admin' ); ?>
				</div>
			</div>
		</div>

		<div class="panel">
			<div class="panel-header"><i class="ri-database-2-line"></i> <?php esc_html_e( 'Data Tools', 'apollo-admin' ); ?></div>
			<div class="panel-body">
				<div style="display:flex;flex-direction:column;gap:8px">
					<button class="btn btn-outline" type="button" style="justify-content:flex-start" title="<?php esc_attr_e( 'Export All Subscribers (CSV)', 'apollo-admin' ); ?>"><i class="ri-download-line"></i></button>
					<button class="btn btn-outline" type="button" style="justify-content:flex-start" title="<?php esc_attr_e( 'Import Subscribers (CSV)', 'apollo-admin' ); ?>"><i class="ri-upload-line"></i></button>
					<button class="btn btn-outline" type="button" style="justify-content:flex-start" title="<?php esc_attr_e( 'Sync with User Database', 'apollo-admin' ); ?>"><i class="ri-refresh-line"></i></button>
					<button class="btn btn-outline" type="button" style="justify-content:flex-start;color:var(--red)" title="<?php esc_attr_e( 'Purge Inactive Subscribers', 'apollo-admin' ); ?>"><i class="ri-delete-bin-line"></i></button>
				</div>
			</div>
		</div>
	</div>
</div>
