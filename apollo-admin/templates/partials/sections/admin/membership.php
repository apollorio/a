<?php
/**
 * Admin Section — Membership Tiers & Points
 *
 * Page ID: page-admin-membership
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page" id="page-admin-membership">
	<div class="stats-grid">
		<div class="stat-card green">
			<div class="stat-icon green"><i class="ri-vip-crown-line"></i></div>
			<span class="stat-label"><?php esc_html_e( 'Active Members', 'apollo-admin' ); ?></span>
			<span class="stat-value" id="stat-active-members">—</span>
		</div>
		<div class="stat-card orange">
			<div class="stat-icon orange"><i class="ri-trophy-line"></i></div>
			<span class="stat-label"><?php esc_html_e( 'Achievements Given', 'apollo-admin' ); ?></span>
			<span class="stat-value" id="stat-achievements">—</span>
		</div>
		<div class="stat-card blue">
			<div class="stat-icon blue"><i class="ri-coin-line"></i></div>
			<span class="stat-label"><?php esc_html_e( 'Total Points Earned', 'apollo-admin' ); ?></span>
			<span class="stat-value" id="stat-points">—</span>
		</div>
		<div class="stat-card yellow">
			<div class="stat-icon yellow"><i class="ri-medal-line"></i></div>
			<span class="stat-label"><?php esc_html_e( 'Ranks Achieved', 'apollo-admin' ); ?></span>
			<span class="stat-value" id="stat-ranks">—</span>
		</div>
	</div>

	<div class="panel">
		<div class="panel-header"><i class="ri-shield-user-fill"></i> <?php esc_html_e( 'Membership Tiers', 'apollo-admin' ); ?></div>
		<div class="panel-body" style="padding:0">
			<table class="data-table" id="membership-tiers-table">
				<thead><tr>
					<th><?php esc_html_e( 'Tier', 'apollo-admin' ); ?></th>
					<th><?php esc_html_e( 'Points Required', 'apollo-admin' ); ?></th>
					<th><?php esc_html_e( 'Members', 'apollo-admin' ); ?></th>
					<th><?php esc_html_e( 'Benefits', 'apollo-admin' ); ?></th>
					<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
					<th></th>
				</tr></thead>
				<tbody>
					<tr><td><strong>Explorer</strong></td><td style="font-family:var(--ff-mono)">0</td><td>—</td><td><?php esc_html_e( 'Basic access, social feed', 'apollo-admin' ); ?></td><td><span class="pill active"><?php esc_html_e( 'Active', 'apollo-admin' ); ?></span></td><td><button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-edit-line"></i></button></td></tr>
					<tr><td><strong>Insider</strong></td><td style="font-family:var(--ff-mono)">500</td><td>—</td><td><?php esc_html_e( 'Early access, guest list priority', 'apollo-admin' ); ?></td><td><span class="pill active"><?php esc_html_e( 'Active', 'apollo-admin' ); ?></span></td><td><button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-edit-line"></i></button></td></tr>
					<tr><td><strong>VIP</strong></td><td style="font-family:var(--ff-mono)">2,000</td><td>—</td><td><?php esc_html_e( 'VIP access, exclusive events', 'apollo-admin' ); ?></td><td><span class="pill active"><?php esc_html_e( 'Active', 'apollo-admin' ); ?></span></td><td><button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-edit-line"></i></button></td></tr>
					<tr><td><strong>Legendary</strong></td><td style="font-family:var(--ff-mono)">10,000</td><td>—</td><td><?php esc_html_e( 'All access, backstage, merch', 'apollo-admin' ); ?></td><td><span class="pill active"><?php esc_html_e( 'Active', 'apollo-admin' ); ?></span></td><td><button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-edit-line"></i></button></td></tr>
				</tbody>
			</table>
		</div>
	</div>

	<div class="panel">
		<div class="panel-header"><i class="ri-compasses-2-line"></i> <?php esc_html_e( 'Points Rules Engine', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[pts_checkin]" value="1" checked><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Event Check-in (+50 pts)', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Award points when user checks in at an event', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[pts_social_post]" value="1" checked><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Social Post (+10 pts)', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Award points for creating a social feed post', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[pts_depoimento]" value="1" checked><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Depoimento Written (+25 pts)', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Award points for writing a testimonial', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[pts_profile_complete]" value="1"><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Profile Completed (+100 pts)', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'One-time bonus for reaching 100% profile', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[pts_referral]" value="1" checked><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Referral Signup (+200 pts)', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Award points when referred user signs up', 'apollo-admin' ); ?></span></div>
			</div>
		</div>
	</div>
</div>