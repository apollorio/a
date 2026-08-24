<?php
/**
 * Email Section — RSS Push
 *
 * Page ID: page-email-rss
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-email-rss">
	<div class="panel">
		<div class="panel-header"><i class="ri-rss-line"></i> <?php esc_html_e( 'Automated RSS-to-Email Push', 'apollo-admin' ); ?></div>
		<div class="panel-body">
			<p style="margin-bottom:20px;color:var(--gray-1);line-height:1.6"><?php esc_html_e( 'Automated delivery of latest news from your RSS feed. Configure breaking news pushes or curated top news digests.', 'apollo-admin' ); ?></p>

			<!-- Breaking News -->
			<div class="section-title"><?php esc_html_e( 'Breaking News — Send immediately after publishing', 'apollo-admin' ); ?></div>
			<div class="form-grid">
				<div class="field"><label class="field-label"><?php esc_html_e( 'RSS Feed URL', 'apollo-admin' ); ?></label><input type="url" class="apollo-input input" name="apollo[rss_feed_url]" value="<?php echo esc_attr( $apollo['rss_feed_url'] ?? 'https://apollo.rio.br/feed/' ); ?>" placeholder="https://yoursite.com/feed/"></div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Send To', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[rss_breaking_segment]">
						<option><?php esc_html_e( 'All Subscribers', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Breaking News Segment', 'apollo-admin' ); ?></option>
					</select>
				</div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[rss_breaking_enabled]" value="1" <?php checked( $apollo['rss_breaking_enabled'] ?? true ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Breaking News Auto-Push', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Immediately send email when new items are published on your RSS feed', 'apollo-admin' ); ?></span></div>
			</div>

			<!-- Top News -->
			<div class="section-title"><?php esc_html_e( 'Top News — Send most popular by period', 'apollo-admin' ); ?></div>
			<div class="form-grid">
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Period', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[rss_top_period]">
						<option value="24h"><?php esc_html_e( 'Last 24 hours', 'apollo-admin' ); ?></option>
						<option value="7d" selected><?php esc_html_e( 'Last 7 days', 'apollo-admin' ); ?></option>
						<option value="30d"><?php esc_html_e( 'Last 30 days', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field"><label class="field-label"><?php esc_html_e( 'Max Articles', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[rss_max_articles]" value="<?php echo esc_attr( $apollo['rss_max_articles'] ?? 5 ); ?>"></div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Send Every', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[rss_top_freq]">
						<option><?php esc_html_e( 'Daily', 'apollo-admin' ); ?></option>
						<option selected><?php esc_html_e( 'Weekly', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Monthly', 'apollo-admin' ); ?></option>
					</select>
				</div>
				<div class="field">
					<label class="field-label"><?php esc_html_e( 'Day of Week', 'apollo-admin' ); ?></label>
					<select class="select" name="apollo[rss_day_of_week]">
						<option><?php esc_html_e( 'Monday', 'apollo-admin' ); ?></option>
						<option selected><?php esc_html_e( 'Friday', 'apollo-admin' ); ?></option>
						<option><?php esc_html_e( 'Saturday', 'apollo-admin' ); ?></option>
					</select>
				</div>
			</div>
			<div class="toggle-row">
				<label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[rss_top_enabled]" value="1" <?php checked( $apollo['rss_top_enabled'] ?? false ); ?>><span class="switch-track"></span></label>
				<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Top News Digest', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Send curated popular content digest on schedule', 'apollo-admin' ); ?></span></div>
			</div>

		</div>
	</div>
</div>