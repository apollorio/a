<?php
/**
 * Email Section — Templates
 *
 * Page ID: page-email-templates
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-email-templates">
	<div class="panel">
		<div class="panel-header"><i class="ri-file-copy-line"></i> <?php esc_html_e( 'Email Templates', 'apollo-admin' ); ?> <span class="badge">7 templates</span></div>
		<div class="panel-body" style="padding:0">
			<table class="data-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Template', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Trigger', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Last Edited', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<tr><td><strong><?php esc_html_e( 'Verification Email', 'apollo-admin' ); ?></strong></td><td><?php esc_html_e( 'User registration', 'apollo-admin' ); ?></td><td style="font-family:var(--ff-mono);font-size:11px">—</td><td><span class="pill active"><?php esc_html_e( 'Active', 'apollo-admin' ); ?></span></td><td><button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit Template', 'apollo-admin' ); ?>"><i class="ri-edit-line"></i></button></td></tr>
					<tr><td><strong><?php esc_html_e( 'Welcome Email', 'apollo-admin' ); ?></strong></td><td><?php esc_html_e( 'Post-verification', 'apollo-admin' ); ?></td><td style="font-family:var(--ff-mono);font-size:11px">—</td><td><span class="pill active"><?php esc_html_e( 'Active', 'apollo-admin' ); ?></span></td><td><button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit Template', 'apollo-admin' ); ?>"><i class="ri-edit-line"></i></button></td></tr>
					<tr><td><strong><?php esc_html_e( 'Password Reset', 'apollo-admin' ); ?></strong></td><td><?php esc_html_e( 'Reset request', 'apollo-admin' ); ?></td><td style="font-family:var(--ff-mono);font-size:11px">—</td><td><span class="pill active"><?php esc_html_e( 'Active', 'apollo-admin' ); ?></span></td><td><button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit Template', 'apollo-admin' ); ?>"><i class="ri-edit-line"></i></button></td></tr>
					<tr><td><strong><?php esc_html_e( 'Weekly Digest', 'apollo-admin' ); ?></strong></td><td><?php esc_html_e( 'Cron: Weekly', 'apollo-admin' ); ?></td><td style="font-family:var(--ff-mono);font-size:11px">—</td><td><span class="pill active"><?php esc_html_e( 'Active', 'apollo-admin' ); ?></span></td><td><button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit Template', 'apollo-admin' ); ?>"><i class="ri-edit-line"></i></button></td></tr>
					<tr><td><strong><?php esc_html_e( 'Event Reminder', 'apollo-admin' ); ?></strong></td><td><?php esc_html_e( '24h before event', 'apollo-admin' ); ?></td><td style="font-family:var(--ff-mono);font-size:11px">—</td><td><span class="pill active"><?php esc_html_e( 'Active', 'apollo-admin' ); ?></span></td><td><button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit Template', 'apollo-admin' ); ?>"><i class="ri-edit-line"></i></button></td></tr>
					<tr><td><strong><?php esc_html_e( 'Notification', 'apollo-admin' ); ?></strong></td><td><?php esc_html_e( 'System notifications', 'apollo-admin' ); ?></td><td style="font-family:var(--ff-mono);font-size:11px">—</td><td><span class="pill active"><?php esc_html_e( 'Active', 'apollo-admin' ); ?></span></td><td><button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit Template', 'apollo-admin' ); ?>"><i class="ri-edit-line"></i></button></td></tr>
					<tr><td><strong><?php esc_html_e( 'Digest (RSS)', 'apollo-admin' ); ?></strong></td><td><?php esc_html_e( 'Content digest', 'apollo-admin' ); ?></td><td style="font-family:var(--ff-mono);font-size:11px">—</td><td><span class="pill inactive"><?php esc_html_e( 'Draft', 'apollo-admin' ); ?></span></td><td><button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit Template', 'apollo-admin' ); ?>"><i class="ri-edit-line"></i></button></td></tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
