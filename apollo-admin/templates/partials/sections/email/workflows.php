<?php
/**
 * Email Section — Workflows & Campaigns
 *
 * Page ID: page-email-workflows
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-email-workflows">

	<div class="filter-bar">
		<button class="btn btn-orange" type="button" title="<?php esc_attr_e( 'New Campaign', 'apollo-admin' ); ?>"><i class="ri-add-line"></i></button>
		<button class="btn btn-outline" type="button" title="<?php esc_attr_e( 'Filter', 'apollo-admin' ); ?>"><i class="ri-filter-3-line"></i></button>
	</div>

	<!-- Workflow Cards -->
	<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
		<div class="workflow-card">
			<div class="wf-head"><div class="wf-status live"></div><span class="wf-name"><?php esc_html_e( 'Welcome Sequence', 'apollo-admin' ); ?></span></div>
			<div class="wf-meta">3 emails · 847 enrolled · Started Jan 15</div>
			<div style="display:flex;gap:4px;flex-wrap:wrap">
				<span class="pill active"><?php esc_html_e( 'Live', 'apollo-admin' ); ?></span>
				<span class="pill" style="background:var(--blue-pale);color:var(--blue)"><?php esc_html_e( 'Automated', 'apollo-admin' ); ?></span>
			</div>
			<div style="display:flex;gap:6px;margin-top:4px">
				<button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-pencil-line"></i></button>
				<button class="btn btn-sm btn-outline" style="color:var(--red)" title="<?php esc_attr_e( 'Pause', 'apollo-admin' ); ?>"><i class="ri-pause-line"></i></button>
			</div>
		</div>

		<div class="workflow-card">
			<div class="wf-head"><div class="wf-status live"></div><span class="wf-name"><?php esc_html_e( 'Event Reminder Flow', 'apollo-admin' ); ?></span></div>
			<div class="wf-meta">2 emails · 1,204 enrolled · Started Dec 01</div>
			<div style="display:flex;gap:4px;flex-wrap:wrap">
				<span class="pill active"><?php esc_html_e( 'Live', 'apollo-admin' ); ?></span>
				<span class="pill" style="background:var(--blue-pale);color:var(--blue)"><?php esc_html_e( 'Trigger-based', 'apollo-admin' ); ?></span>
			</div>
			<div style="display:flex;gap:6px;margin-top:4px">
				<button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-pencil-line"></i></button>
				<button class="btn btn-sm btn-outline" style="color:var(--red)" title="<?php esc_attr_e( 'Pause', 'apollo-admin' ); ?>"><i class="ri-pause-line"></i></button>
			</div>
		</div>

		<div class="workflow-card">
			<div class="wf-head"><div class="wf-status draft"></div><span class="wf-name"><?php esc_html_e( 'Re-engagement', 'apollo-admin' ); ?></span></div>
			<div class="wf-meta">4 emails · 0 enrolled · Draft</div>
			<div style="display:flex;gap:4px;flex-wrap:wrap"><span class="pill inactive"><?php esc_html_e( 'Draft', 'apollo-admin' ); ?></span></div>
			<div style="display:flex;gap:6px;margin-top:4px">
				<button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-pencil-line"></i></button>
				<button class="btn btn-sm btn-primary" title="<?php esc_attr_e( 'Activate', 'apollo-admin' ); ?>"><i class="ri-play-line"></i></button>
			</div>
		</div>
	</div>

	<!-- Create New Campaign -->
	<div class="panel">
		<div class="panel-header"><i class="ri-add-circle-line"></i> <?php esc_html_e( 'Create New Campaign', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<div class="form-grid">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Campaign Name', 'apollo-admin' ); ?> <span class="required">*</span></label><input type="text" class="apollo-input input" name="campaign[name]" placeholder="<?php esc_attr_e( 'e.g., Carnaval Push', 'apollo-admin' ); ?>"></div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Send To Segment', 'apollo-admin' ); ?></label>
					<select class="select" name="campaign[segment]">
						<option><?php esc_html_e( 'All Subscribers', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Event Attendees', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Active Members', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Inactive (30+ days)', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'VIP+ Tier', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'First Push Date', 'apollo-admin' ); ?></label><input type="datetime-local" class="input" name="campaign[push_date]" data-apollo-date-picker="true"></div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Campaign Lifetime', 'apollo-admin' ); ?></label>
					<select class="select" name="campaign[lifetime]">
						<option value="7"><?php esc_html_e( '7 days', 'apollo-admin' ); ?></option>
						<option value="14"><?php esc_html_e( '14 days', 'apollo-admin' ); ?></option>
						<option value="30" selected><?php esc_html_e( '30 days', 'apollo-admin' ); ?></option>
						<option value="60"><?php esc_html_e( '60 days', 'apollo-admin' ); ?></option>
						<option value="ongoing"><?php esc_html_e( 'Ongoing', 'apollo-admin' ); ?></option>
					</select>
				</div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Email Sequence Blocks', 'apollo-admin' ); ?></div>

			<div class="form-grid cols-1">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Block 1: Select Template', 'apollo-admin' ); ?></label>
					<select class="select" name="campaign[block1]">
						<option><?php esc_html_e( 'Welcome to Apollo', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Check Your Activities', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Check Your Messages & Notifications', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Check Latest Events for Your Sounds', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Custom Template', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Block 2: Select Template (optional)', 'apollo-admin' ); ?></label>
					<select class="select" name="campaign[block2]">
						<option><?php esc_html_e( '— None —', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Welcome to Apollo', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Check Your Activities', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Check Your Messages & Notifications', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Check Latest Events for Your Sounds', 'apollo-admin' ); ?></option>
					</select>
				</div>
			</div>

			<div class="section-title"><?php esc_html_e( 'Digest Settings', 'apollo-admin' ); ?></div>

			<div class="form-grid">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Email News Limit', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="campaign[news_limit]" value="5" placeholder="<?php esc_attr_e( 'Max articles per digest', 'apollo-admin' ); ?>"></div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Send Digest Every', 'apollo-admin' ); ?></label>
					<select class="select" name="campaign[digest_freq]">
						<option><?php esc_html_e( 'Daily', 'apollo-admin' ); ?></option>
						<option selected><?php esc_html_e( 'Weekly', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Bi-weekly', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Monthly', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Start At', 'apollo-admin' ); ?></label><input type="time" class="input" name="campaign[start_time]" value="09:00" data-apollo-date-picker="true"></div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Timezone', 'apollo-admin' ); ?></label>
					<select class="select" name="campaign[timezone]">
						<option selected>America/Sao_Paulo</option>
						<option>UTC</option>
					</select>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Article UTM Source', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="campaign[utm_source]" value="apollo_digest" placeholder="<?php esc_attr_e( 'UTM source parameter', 'apollo-admin' ); ?>"></div>
			</div>

			<div style="margin-top:16px">
				<button class="btn btn-orange" type="button" title="<?php esc_attr_e( 'Save Campaign', 'apollo-admin' ); ?>"><i class="ri-save-line"></i></button>
			</div>
		</div>
	</div>
</div>