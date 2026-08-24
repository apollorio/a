<?php
/**
 * Identity Section — Login Settings
 *
 * Page ID: page-id-login
 * Sections: Core Login, Login URLs, Social OAuth, Security & Lockout
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-id-login">
	<div class="panel">
		<div class="panel-header"><i class="ri-fingerprint-fill"></i> <?php esc_html_e( 'Registration & Access', 'apollo-admin' ); ?> <span class="badge">apollo-login</span></div>
		<div class="panel-body">

			<!-- Core Login Settings -->
			<div class="section-title"><?php esc_html_e( 'Core Login Settings', 'apollo-admin' ); ?></div>

			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[login_enable_registration]" value="1" <?php checked( $apollo['login_enable_registration'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Registration', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Allow new user registration on the platform', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[login_registration_quiz]" value="1" <?php checked( $apollo['login_registration_quiz'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Registration Quiz', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Show quiz during registration flow (anti-bot + culture filter)', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[login_disable_wp_login]" value="1" <?php checked( $apollo['login_disable_wp_login'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Disable wp-login.php', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Hide default WordPress login page, redirect to Apollo login', 'apollo-admin' ); ?></span></div>
			</div>

			<div class="form-grid" style="margin-top:16px">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Login Redirect URL', 'apollo-admin' ); ?></label>
					<input type="text" class="apollo-input input" name="apollo[login_redirect_url]" value="<?php echo esc_attr( $apollo['login_redirect_url'] ?? '/' ); ?>" placeholder="/">
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Login Page', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[login_page]">
						<option value=""><?php esc_html_e( '— Select page —', 'apollo-admin' ); ?></option>
						<option value="/login" <?php selected( $apollo['login_login_page'] ?? '', '/login' ); ?>>/login</option>
						<option value="/entrar" <?php selected( $apollo['login_login_page'] ?? '', '/entrar' ); ?>>/entrar</option>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Registration Page', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[login_register_page]">
						<option value=""><?php esc_html_e( '— Select page —', 'apollo-admin' ); ?></option>
						<option value="/register" <?php selected( $apollo['login_register_page'] ?? '', '/register' ); ?>>/register</option>
						<option value="/cadastro" <?php selected( $apollo['login_register_page'] ?? '', '/cadastro' ); ?>>/cadastro</option>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Password Reset Page', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[login_reset_page]">
						<option value=""><?php esc_html_e( '— Select page —', 'apollo-admin' ); ?></option>
						<option value="/reset-password" <?php selected( $apollo['login_reset_page'] ?? '', '/reset-password' ); ?>>/reset-password</option>
					</select>
				</div>
			</div>

			<!-- Social OAuth -->
			<div class="section-title"><?php esc_html_e( 'Social OAuth Login', 'apollo-admin' ); ?> 🔜</div>

			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[login_oauth_facebook]" value="1" <?php checked( $apollo['login_oauth_facebook'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Facebook OAuth', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable Facebook social login', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:8px">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Facebook App ID', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[login_fb_app_id]" value="<?php echo esc_attr( $apollo['login_fb_app_id'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Enter App ID', 'apollo-admin' ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Facebook App Secret', 'apollo-admin' ); ?></label><input type="password" class="apollo-input input" name="apollo[login_fb_app_secret]" value="<?php echo esc_attr( $apollo['login_fb_app_secret'] ?? '' ); ?>" placeholder="••••••••"></div>
			</div>

			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[login_oauth_google]" value="1" <?php checked( $apollo['login_oauth_google'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Google OAuth', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable Google social login', 'apollo-admin' ); ?></span></div>
			</div>
			<div class="form-grid" style="margin-top:8px">
				<div class="field"><label class="field-label"><?php esc_html_e( 'Google Client ID', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[login_google_client_id]" value="<?php echo esc_attr( $apollo['login_google_client_id'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Enter Client ID', 'apollo-admin' ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Google Client Secret', 'apollo-admin' ); ?></label><input type="password" class="apollo-input input" name="apollo[login_google_client_secret]" value="<?php echo esc_attr( $apollo['login_google_client_secret'] ?? '' ); ?>" placeholder="••••••••"></div>
			</div>

			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[login_oauth_apple]" value="1" <?php checked( $apollo['login_oauth_apple'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Apple Sign-In', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable Apple social login', 'apollo-admin' ); ?></span></div>
			</div>

			<!-- Security & Lockout -->
			<div class="section-title"><?php esc_html_e( 'Security & Lockout', 'apollo-admin' ); ?> 🔜</div>

			<div class="form-grid">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Password Complexity', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[login_password_complexity]">
						<option value="low" <?php selected( $apollo['login_password_complexity'] ?? 'medium', 'low' ); ?>><?php esc_html_e( 'Low', 'apollo-admin' ); ?></option>
						<option value="medium" <?php selected( $apollo['login_password_complexity'] ?? 'medium', 'medium' ); ?>><?php esc_html_e( 'Medium', 'apollo-admin' ); ?></option>
						<option value="high" <?php selected( $apollo['login_password_complexity'] ?? 'medium', 'high' ); ?>><?php esc_html_e( 'High', 'apollo-admin' ); ?></option>
						<option value="custom" <?php selected( $apollo['login_password_complexity'] ?? 'medium', 'custom' ); ?>><?php esc_html_e( 'Custom', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Max Login Attempts', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[login_max_attempts]" value="<?php echo esc_attr( $B::get( 'login_max_attempts', 5 ) ); ?>"></div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Lockout Duration (min)', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[login_lockout_duration]" value="<?php echo esc_attr( $B::get( 'login_lockout_duration', 15 ) ); ?>"></div>
			</div>

			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[login_invite_codes]" value="1" <?php checked( $apollo['login_invite_codes'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Invite Codes', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Restrict registration to invite code holders only', 'apollo-admin' ); ?></span></div>
			</div>

		</div>
	</div>
</div>

<!-- ═══════════════════════════════════════════════════════════════ -->
<!-- apolloDJ.exe — Live Activity Master Table                        -->
<!-- ═══════════════════════════════════════════════════════════════ -->
<div class="panel" id="panel-exe-activity" style="margin-top:24px">
	<div class="panel-header">
		<i class="ri-broadcast-line"></i>
		<?php esc_html_e( 'apolloDJ.exe — Live Activity', 'apollo-admin' ); ?>
		<span class="badge">manage_options</span>
		<button type="button" id="apollo-actlog-refresh" style="margin-left:auto;background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:13px;display:flex;align-items:center;gap:4px">
			<i class="ri-refresh-line"></i> <?php esc_html_e( 'Refresh', 'apollo-admin' ); ?>
		</button>
	</div>
	<div class="panel-body" style="padding:0">
		<div id="apollo-actlog-wrap" style="overflow-x:auto">
			<table id="apollo-actlog-table" style="width:100%;border-collapse:collapse;font-size:13px">
				<thead>
					<tr style="background:rgba(0,0,0,.03)">
						<th style="padding:10px 16px;text-align:left;font-weight:600;white-space:nowrap"><?php esc_html_e( 'User', 'apollo-admin' ); ?></th>
						<th style="padding:10px 16px;text-align:left;font-weight:600;white-space:nowrap"><?php esc_html_e( 'Action', 'apollo-admin' ); ?></th>
						<th style="padding:10px 16px;text-align:left;font-weight:600;white-space:nowrap"><?php esc_html_e( 'Version', 'apollo-admin' ); ?></th>
						<th style="padding:10px 16px;text-align:left;font-weight:600;white-space:nowrap"><?php esc_html_e( 'IP', 'apollo-admin' ); ?></th>
						<th style="padding:10px 16px;text-align:left;font-weight:600;white-space:nowrap"><?php esc_html_e( 'Last Seen', 'apollo-admin' ); ?></th>
						<th style="padding:10px 16px;text-align:left;font-weight:600;white-space:nowrap"><?php esc_html_e( 'Logged At', 'apollo-admin' ); ?></th>
					</tr>
				</thead>
				<tbody id="apollo-actlog-body">
					<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--text-muted)"><?php esc_html_e( 'Loading…', 'apollo-admin' ); ?></td></tr>
				</tbody>
			</table>
		</div>
		<div id="apollo-actlog-pagination" style="padding:12px 16px;display:flex;gap:8px;align-items:center;font-size:13px;color:var(--text-muted)"></div>
	</div>
</div>

<script>
(function () {
	'use strict';

	const REST  = '<?php echo esc_js( rest_url( 'apollo/v1/activity/log' ) ); ?>';
	const NONCE = '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>';
	let currentPage = 1;

	function escHtml( str ) {
		if ( ! str ) { return ''; }
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function timeSince( dateStr ) {
		if ( ! dateStr ) { return '—'; }
		const diff = Math.floor( ( Date.now() - new Date( dateStr + ' UTC' ).getTime() ) / 1000 );
		if ( diff < 60 )   { return diff + 's ago'; }
		if ( diff < 3600 ) { return Math.floor( diff / 60 ) + 'm ago'; }
		if ( diff < 86400) { return Math.floor( diff / 3600 ) + 'h ago'; }
		return Math.floor( diff / 86400 ) + 'd ago';
	}

	function loadPage( page ) {
		currentPage = page;
		const tbody = document.getElementById( 'apollo-actlog-body' );
		tbody.innerHTML = '<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--text-muted)">Loading…</td></tr>';

		fetch( REST + '?per_page=50&page=' + page, {
			headers: { 'X-WP-Nonce': NONCE }
		} )
		.then( function ( r ) {
			if ( ! r.ok ) { throw new Error( r.status ); }
			return r.json();
		} )
		.then( function ( data ) {
			if ( ! data.rows || data.rows.length === 0 ) {
				tbody.innerHTML = '<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--text-muted)">No activity recorded yet.</td></tr>';
				document.getElementById( 'apollo-actlog-pagination' ).innerHTML = '';
				return;
			}

			tbody.innerHTML = data.rows.map( function ( row ) {
				return '<tr style="border-top:1px solid rgba(0,0,0,.05)">'
					+ '<td style="padding:10px 16px">' + escHtml( row.display_name || '#' + row.user_id ) + '</td>'
					+ '<td style="padding:10px 16px"><code style="background:rgba(0,0,0,.05);padding:2px 6px;border-radius:6px;font-size:12px">' + escHtml( row.action ) + '</code></td>'
					+ '<td style="padding:10px 16px;color:var(--text-muted)">' + escHtml( row.exe_version || '—' ) + '</td>'
					+ '<td style="padding:10px 16px;color:var(--text-muted);font-size:12px">' + escHtml( row.ip_address || '—' ) + '</td>'
					+ '<td style="padding:10px 16px;color:var(--text-muted)">' + escHtml( timeSince( row.last_seen ) ) + '</td>'
					+ '<td style="padding:10px 16px;color:var(--text-muted);font-size:12px">' + escHtml( row.logged_at ) + '</td>'
					+ '</tr>';
			} ).join( '' );

			// Pagination.
			const pag = document.getElementById( 'apollo-actlog-pagination' );
			let html  = '<span>Page ' + page + ' of ' + data.pages + ' &nbsp;·&nbsp; ' + data.total + ' total</span>';
			if ( page > 1 ) {
				html += '<button type="button" onclick="apolloActlogLoad(' + ( page - 1 ) + ')" style="margin-left:8px;background:none;border:1px solid rgba(0,0,0,.15);border-radius:8px;padding:3px 10px;cursor:pointer;font-size:12px">‹ Prev</button>';
			}
			if ( page < data.pages ) {
				html += '<button type="button" onclick="apolloActlogLoad(' + ( page + 1 ) + ')" style="margin-left:4px;background:none;border:1px solid rgba(0,0,0,.15);border-radius:8px;padding:3px 10px;cursor:pointer;font-size:12px">Next ›</button>';
			}
			pag.innerHTML = html;
		} )
		.catch( function ( err ) {
			tbody.innerHTML = '<tr><td colspan="6" style="padding:24px;text-align:center;color:#c00">Error loading activity (' + escHtml( String( err ) ) + ')</td></tr>';
		} );
	}

	// Expose for inline pagination buttons.
	window.apolloActlogLoad = loadPage;

	// Initial load + manual refresh button.
	document.addEventListener( 'DOMContentLoaded', function () {
		loadPage( 1 );
		document.getElementById( 'apollo-actlog-refresh' ).addEventListener( 'click', function () {
			loadPage( currentPage );
		} );
	} );

	// Auto-refresh every 60 seconds.
	setInterval( function () { loadPage( currentPage ); }, 60000 );
}());
</script>