<?php

/**
 * Admin Section — Overview & Support (Global Settings)
 *
 * Page ID: page-admin-global
 * Feed tabs: Overview | Support
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="page visible" id="page-admin-global">
	<div class="feed-tabs">
		<button class="feed-tab active" data-sub="ag-overview"><i class="ri-dashboard-line"></i> Overview</button>
		<button class="feed-tab" data-sub="ag-support"><i class="ri-customer-service-2-fill"></i> Support</button>
	</div>

	<!-- SUB: Overview -->
	<div class="sub-content visible" id="sub-ag-overview">
		<!-- Primary Stats -->
		<div class="stats-grid">
			<div class="stat-card orange">
				<div class="stat-icon orange"><i class="ri-user-line"></i></div>
				<span class="stat-label"><?php esc_html_e( 'Total Users', 'apollo-admin' ); ?></span>
				<span class="stat-value" id="stat-total-users">—</span>
				<span class="stat-delta" id="stat-users-delta"></span>
			</div>
			<div class="stat-card green">
				<div class="stat-icon green"><i class="ri-calendar-event-line"></i></div>
				<span class="stat-label"><?php esc_html_e( 'Active Events', 'apollo-admin' ); ?></span>
				<span class="stat-value" id="stat-active-events">—</span>
				<span class="stat-delta" id="stat-events-delta"></span>
			</div>
			<div class="stat-card blue">
				<div class="stat-icon blue"><i class="ri-chat-3-line"></i></div>
				<span class="stat-label"><?php esc_html_e( 'Messages Today', 'apollo-admin' ); ?></span>
				<span class="stat-value" id="stat-messages-today">—</span>
				<div class="mini-bars">
					<div class="mini-bar" style="height:30%"></div>
					<div class="mini-bar" style="height:45%"></div>
					<div class="mini-bar" style="height:35%"></div>
					<div class="mini-bar" style="height:60%"></div>
					<div class="mini-bar" style="height:50%"></div>
					<div class="mini-bar" style="height:80%"></div>
					<div class="mini-bar" style="height:100%"></div>
				</div>
			</div>
			<div class="stat-card yellow">
				<div class="stat-icon yellow"><i class="ri-mail-line"></i></div>
				<span class="stat-label"><?php esc_html_e( 'Emails Sent', 'apollo-admin' ); ?></span>
				<span class="stat-value" id="stat-emails-sent">—</span>
				<span class="stat-delta" id="stat-emails-delta"></span>
			</div>
		</div>

		<!-- Secondary Stats -->
		<div class="stats-grid">
			<div class="stat-card">
				<span class="stat-label"><?php esc_html_e( 'Social Posts', 'apollo-admin' ); ?></span>
				<span class="stat-value" id="stat-social-posts">—</span>
			</div>
			<div class="stat-card">
				<span class="stat-label"><?php esc_html_e( 'Depoimentos', 'apollo-admin' ); ?></span>
				<span class="stat-value" id="stat-depoimentos">—</span>
			</div>
			<div class="stat-card">
				<span class="stat-label"><?php esc_html_e( 'Classificados', 'apollo-admin' ); ?></span>
				<span class="stat-value" id="stat-classifieds">—</span>
			</div>
			<div class="stat-card">
				<span class="stat-label"><?php esc_html_e( 'Memberships Active', 'apollo-admin' ); ?></span>
				<span class="stat-value" id="stat-memberships">—</span>
			</div>
		</div>

		<!-- Two Column: Plugin Status + Activity -->
		<div class="two-cols">
			<div class="panel">
				<div class="panel-header"><i class="ri-plug-line"></i> <?php esc_html_e( 'Plugin Status', 'apollo-admin' ); ?> <span class="badge" id="active-plugins-count"></span></div>
				<div class="panel-body" style="padding:0">
					<table class="data-table" id="plugin-status-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Plugin', 'apollo-admin' ); ?></th>
								<th><?php esc_html_e( 'Version', 'apollo-admin' ); ?></th>
								<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$apollo_plugins = array(
								'apollo-admin',
								'apollo-events',
								'apollo-users',
								'apollo-email',
								'apollo-membership',
								'apollo-chat',
								// apollo-shortcodes absorbed by apollo-core (FASE 2) — no longer on disk
								'apollo-login',
								'apollo-social',
								'apollo-djs',
								'apollo-loc',
								'apollo-fav',
								'apollo-wow',
								'apollo-comment',
								'apollo-notif',
								'apollo-groups',
								'apollo-templates',
								'apollo-dashboard',
								'apollo-coauthor',
								'apollo-statistics',
								// apollo-pwa not installed on this host
								'apollo-seo',
								'apollo-mod',
							);
							foreach ( $apollo_plugins as $slug ) :
								$plugin_file = $slug . '/' . $slug . '.php';
								$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
								$is_active   = is_plugin_active( $plugin_file );
								// #region agent log
								$__ap_exists = file_exists( $plugin_path );
								file_put_contents(
									WP_PLUGIN_DIR . '/debug-e031aa.log',
									wp_json_encode(
										array(
											'sessionId'    => 'e031aa',
											'runId'        => 'shortcodes-missing',
											'hypothesisId' => 'H1',
											'location'     => 'overview.php:plugin-status',
											'message'      => 'plugin file probe',
											'data'         => array(
												'slug'   => $slug,
												'path'   => $plugin_path,
												'exists' => $__ap_exists,
												'active' => $is_active,
											),
											'timestamp'    => (int) round( microtime( true ) * 1000 ),
										)
									) . "\n",
									FILE_APPEND
								);
								// #endregion
								?>
								<tr>
									<td><strong><?php echo esc_html( $slug ); ?></strong></td>
									<td style="font-family:var(--ff-mono);font-size:11px">
										<?php
										if ( $__ap_exists ) {
											$plugin_data = get_plugin_data( $plugin_path, false, false );
											echo esc_html( $plugin_data['Version'] ?? '—' );
										} else {
											echo '—';
										}
										?>
									</td>
									<td>
										<span class="pill <?php echo $is_active ? 'active' : 'inactive'; ?>">
											<?php echo $is_active ? esc_html__( 'Active', 'apollo-admin' ) : esc_html__( 'Inactive', 'apollo-admin' ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<div class="panel">
				<div class="panel-header"><i class="ri-line-chart-line"></i> <?php esc_html_e( 'Activity (Last 7 days)', 'apollo-admin' ); ?></div>
				<div class="panel-body">
					<div class="chart-placeholder"><i class="ri-bar-chart-2-line" style="font-size:24px;margin-right:8px"></i> <?php esc_html_e( 'Activity chart renders here', 'apollo-admin' ); ?></div>
				</div>
			</div>
		</div>

		<!-- Global Settings Panel -->
		<div class="panel">
			<div class="panel-header"><i class="ri-compasses-2-line"></i> <?php esc_html_e( 'Global Settings', 'apollo-admin' ); ?></div>
			<div class="panel-body">
				<div class="form-grid">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Site Name', 'apollo-admin' ); ?></label>
					<input type="text" class="apollo-input input" name="apollo[site_name]" value="<?php echo esc_attr( $B::get( 'site_name', get_bloginfo( 'name' ) ) ); ?>" placeholder="<?php esc_attr_e( 'Your platform name', 'apollo-admin' ); ?>">
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Primary Language', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[language]">
						<option value="pt_BR" <?php selected( $B::get( 'language', 'pt_BR' ), 'pt_BR' ); ?>>Português (BR)</option>
						<option value="en_US" <?php selected( $B::get( 'language', 'pt_BR' ), 'en_US' ); ?>>English</option>
						<option value="es_ES" <?php selected( $B::get( 'language', 'pt_BR' ), 'es_ES' ); ?>>Español</option>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'CDN Base URL', 'apollo-admin' ); ?></label>
					<input type="text" class="apollo-input input" name="apollo[cdn_url]" value="<?php echo esc_attr( $B::get( 'cdn_url', 'https://cdn.apollo.rio.br' ) ); ?>">
				</div>
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Timezone', 'apollo-admin' ); ?></label>
						<select class="select" name="apollo[timezone]">
							<option value="America/Sao_Paulo" selected>America/Sao_Paulo (UTC-3)</option>
							<option value="UTC">UTC</option>
						</select>
					</div>
					<div class="field full">
						<label class="field-label"><?php esc_html_e( 'API Keys', 'apollo-admin' ); ?></label>
						<div class="form-grid cols-3">
							<div class="field">
								<label class="field-label" style="font-size:11px;color:var(--gray-9)"><?php esc_html_e( 'Google Maps API', 'apollo-admin' ); ?></label>
								<input type="password" class="apollo-input input" name="apollo[gmaps_key]" value="<?php echo esc_attr( $B::get( 'gmaps_key', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Enter API Key', 'apollo-admin' ); ?>">
							</div>
							<div class="field">
								<label class="field-label" style="font-size:11px;color:var(--gray-9)"><?php esc_html_e( 'SMTP Key', 'apollo-admin' ); ?></label>
								<input type="password" class="apollo-input input" name="apollo[smtp_key]" value="<?php echo esc_attr( $apollo['smtp_key'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Enter SMTP Key', 'apollo-admin' ); ?>">
							</div>
							<div class="field">
								<label class="field-label" style="font-size:11px;color:var(--gray-9)"><?php esc_html_e( 'reCAPTCHA Site Key', 'apollo-admin' ); ?></label>
								<input type="password" class="apollo-input input" name="apollo[recaptcha_key]" value="<?php echo esc_attr( $B::get( 'recaptcha_key', '' ) ); ?>" placeholder="<?php esc_attr_e( 'Enter site key', 'apollo-admin' ); ?>">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- SUB: Support -->
	<div class="sub-content" id="sub-ag-support">
		<div class="panel">
			<div class="panel-header"><i class="ri-customer-service-2-fill"></i> <?php esc_html_e( 'Request Support', 'apollo-admin' ); ?></div>
			<div class="panel-body">
				<div class="form-grid cols-1">
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Subject', 'apollo-admin' ); ?> <span class="required">*</span></label>
						<input type="text" class="apollo-input input" name="support[subject]" placeholder="<?php esc_attr_e( 'Brief description of the issue', 'apollo-admin' ); ?>">
					</div>
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Category', 'apollo-admin' ); ?></label>
						<select class="select" name="support[category]">
							<option value="bug"><?php esc_html_e( 'Bug Report', 'apollo-admin' ); ?></option>
							<option value="feature"><?php esc_html_e( 'Feature Request', 'apollo-admin' ); ?></option>
							<option value="account"><?php esc_html_e( 'Account Issue', 'apollo-admin' ); ?></option>
							<option value="billing"><?php esc_html_e( 'Billing', 'apollo-admin' ); ?></option>
							<option value="other"><?php esc_html_e( 'Other', 'apollo-admin' ); ?></option>
						</select>
					</div>
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Description', 'apollo-admin' ); ?> <span class="required">*</span></label>
						<textarea class="apollo-textarea input" name="support[description]" rows="5" placeholder="<?php esc_attr_e( 'Describe the issue in detail...', 'apollo-admin' ); ?>"></textarea>
					</div>
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Priority', 'apollo-admin' ); ?></label>
						<select class="select" name="support[priority]">
							<option value="low"><?php esc_html_e( 'Low', 'apollo-admin' ); ?></option>
							<option value="medium" selected><?php esc_html_e( 'Medium', 'apollo-admin' ); ?></option>
							<option value="high"><?php esc_html_e( 'High', 'apollo-admin' ); ?></option>
							<option value="critical"><?php esc_html_e( 'Critical', 'apollo-admin' ); ?></option>
						</select>
					</div>
					<div><button class="btn btn-orange" type="button" id="submit-support-ticket" title="<?php esc_attr_e( 'Submit Ticket', 'apollo-admin' ); ?>"><i class="ri-send-plane-line"></i></button></div>
				</div>
			</div>
		</div>
	</div>
</div>
