<?php

/**
 * System Section — SEO
 *
 * Page ID: page-sys-seo
 * 5 feed-tabs: General, Appearance, Social, Sitemaps, Crawl
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="page" id="page-sys-seo">
	<div class="feed-tabs">
		<button class="feed-tab active" data-sub="seo-general" title="General"><i class="ri-seo-line"></i></button>
		<button class="feed-tab" data-sub="seo-appearance" title="Appearance"><i class="ri-search-eye-line"></i></button>
		<button class="feed-tab" data-sub="seo-social" title="Social"><i class="ri-share-forward-line"></i></button>
		<button class="feed-tab" data-sub="seo-sitemap" title="Sitemaps"><i class="ri-sitemap-line"></i></button>
		<button class="feed-tab" data-sub="seo-crawl" title="Crawl"><i class="ri-spider-line"></i></button>
	</div>

	<!-- General SEO -->
	<div class="sub-content visible" id="sub-seo-general">
		<div class="panel">
			<div class="panel-header"><i class="ri-seo-line"></i> <?php esc_html_e( 'General SEO', 'apollo-admin' ); ?> 🔜 <span class="badge">apollo-seo</span></div>
			<div class="panel-body">
				<div class="form-grid">
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Site Type', 'apollo-admin' ); ?></label>
						<select class="select" name="apollo[seo_site_type]">
							<option value="person" <?php selected( $apollo['seo_site_type'] ?? 'organization', 'person' ); ?>><?php esc_html_e( 'Person', 'apollo-admin' ); ?></option>
							<option value="organization" <?php selected( $apollo['seo_site_type'] ?? 'organization', 'organization' ); ?>><?php esc_html_e( 'Organization', 'apollo-admin' ); ?></option>
						</select>
					</div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'Organization Name', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[seo_org_name]" value="<?php echo esc_attr( $apollo['seo_org_name'] ?? 'Apollo Rio' ); ?>"></div>
				</div>
				<div class="section-title"><?php esc_html_e( 'Webmaster Verification', 'apollo-admin' ); ?></div>
				<div class="form-grid">
					<div class="field"><label class="field-label"><?php esc_html_e( 'Google Search Console', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[seo_google_verify]" value="<?php echo esc_attr( $apollo['seo_google_verify'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Verification code', 'apollo-admin' ); ?>"></div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'Bing Webmaster', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[seo_bing_verify]" value="<?php echo esc_attr( $apollo['seo_bing_verify'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Verification code', 'apollo-admin' ); ?>"></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_indexnow]" value="1" <?php checked( $apollo['seo_indexnow'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable IndexNow', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Auto-ping search engines on publish', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_llms]" value="1" <?php checked( $apollo['seo_llms'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable llms.txt', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Generate llms.txt for AI crawler parsing', 'apollo-admin' ); ?></span></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Search Appearance -->
	<div class="sub-content" id="sub-seo-appearance">
		<div class="panel">
			<div class="panel-header"><i class="ri-search-eye-line"></i> <?php esc_html_e( 'Search Appearance', 'apollo-admin' ); ?> 🔜</div>
			<div class="panel-body">
				<div class="form-grid">
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Title Separator', 'apollo-admin' ); ?></label>
						<select class="select" name="apollo[seo_separator]">
							<?php foreach ( array( '–', '|', '·', '—', '»' ) as $sep ) : ?>
								<option value="<?php echo esc_attr( $sep ); ?>" <?php selected( $apollo['seo_separator'] ?? '–', $sep ); ?>><?php echo esc_html( $sep ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'Homepage Title', 'apollo-admin' ); ?></label><input type="text" class="apollo-input input" name="apollo[seo_home_title]" value="<?php echo esc_attr( $apollo['seo_home_title'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Custom homepage title template', 'apollo-admin' ); ?>"></div>
					<div class="field full"><label class="field-label"><?php esc_html_e( 'Homepage Description', 'apollo-admin' ); ?></label><textarea class="apollo-textarea input" rows="2" name="apollo[seo_home_desc]" placeholder="<?php esc_attr_e( 'Custom homepage meta description', 'apollo-admin' ); ?>"><?php echo esc_textarea( $apollo['seo_home_desc'] ?? '' ); ?></textarea></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_noindex_dates]" value="1" <?php checked( $apollo['seo_noindex_dates'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Noindex Date Archives', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Set noindex on date-based archives', 'apollo-admin' ); ?></span></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Social Networks -->
	<div class="sub-content" id="sub-seo-social">
		<div class="panel">
			<div class="panel-header"><i class="ri-share-forward-line"></i> <?php esc_html_e( 'Social Networks', 'apollo-admin' ); ?> 🔜</div>
			<div class="panel-body">
				<div class="form-grid">
					<div class="field">
						<label class="field-label"><?php esc_html_e( 'Twitter Card Type', 'apollo-admin' ); ?></label>
						<select class="select" name="apollo[seo_twitter_card]">
							<option value="summary_large_image" <?php selected( $apollo['seo_twitter_card'] ?? 'summary_large_image', 'summary_large_image' ); ?>><?php esc_html_e( 'Summary Large Image', 'apollo-admin' ); ?></option>
							<option value="summary" <?php selected( $apollo['seo_twitter_card'] ?? 'summary_large_image', 'summary' ); ?>><?php esc_html_e( 'Summary', 'apollo-admin' ); ?></option>
						</select>
					</div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'Facebook Page URL', 'apollo-admin' ); ?></label><input type="url" class="apollo-input input" name="apollo[seo_facebook]" value="<?php echo esc_attr( $apollo['seo_facebook'] ?? '' ); ?>" placeholder="https://facebook.com/..."></div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'Instagram URL', 'apollo-admin' ); ?></label><input type="url" class="apollo-input input" name="apollo[seo_instagram]" value="<?php echo esc_attr( $apollo['seo_instagram'] ?? '' ); ?>" placeholder="https://instagram.com/..."></div>
					<div class="field"><label class="field-label"><?php esc_html_e( 'Twitter/X URL', 'apollo-admin' ); ?></label><input type="url" class="apollo-input input" name="apollo[seo_twitter]" value="<?php echo esc_attr( $apollo['seo_twitter'] ?? '' ); ?>" placeholder="https://x.com/..."></div>
				</div>
			</div>
		</div>
	</div>

	<!-- XML Sitemaps -->
	<div class="sub-content" id="sub-seo-sitemap">
		<div class="panel">
			<div class="panel-header"><i class="ri-sitemap-line"></i> <?php esc_html_e( 'XML Sitemaps', 'apollo-admin' ); ?> 🔜</div>
			<div class="panel-body">
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_sitemap]" value="1" <?php checked( $apollo['seo_sitemap'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable XML Sitemap', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Generate and serve XML sitemap', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_news_sitemap]" value="1" <?php checked( $apollo['seo_news_sitemap'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable News Sitemap', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Generate Google News sitemap', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_breadcrumbs]" value="1" <?php checked( $apollo['seo_breadcrumbs'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Breadcrumbs', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable breadcrumb navigation schema', 'apollo-admin' ); ?></span></div>
				</div>
			</div>
		</div>
	</div>

	<!-- Crawl Optimization -->
	<div class="sub-content" id="sub-seo-crawl">
		<div class="panel">
			<div class="panel-header"><i class="ri-spider-line"></i> <?php esc_html_e( 'Crawl Optimization', 'apollo-admin' ); ?> 🔜</div>
			<div class="panel-body">
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_remove_api_links]" value="1" <?php checked( $apollo['seo_remove_api_links'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Remove REST API Links', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Remove REST API links from <head>', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_remove_emoji]" value="1" <?php checked( $apollo['seo_remove_emoji'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Remove Emoji Scripts', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Remove WordPress emoji scripts and styles', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_remove_rsd]" value="1" <?php checked( $apollo['seo_remove_rsd'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Remove RSD/WLW Links', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_remove_shortlink]" value="1" <?php checked( $apollo['seo_remove_shortlink'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Remove Shortlink Tag', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_strip_category]" value="1" <?php checked( $apollo['seo_strip_category'] ?? false ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Strip /category/ Base', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Remove /category/ from permalinks', 'apollo-admin' ); ?></span></div>
				</div>

				<div class="section-title"><?php esc_html_e( 'Redirect & 404 Monitor', 'apollo-admin' ); ?></div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_redirects]" value="1" <?php checked( $apollo['seo_redirects'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable Redirect Manager', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Enable 301/302 redirect rule management', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_404_monitor]" value="1" <?php checked( $apollo['seo_404_monitor'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Enable 404 Monitor', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Monitor and log 404 errors with referrer', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="form-grid" style="margin-top:8px">
					<div class="field">
						<label class="field-label"><?php esc_html_e( '404 Logging Mode', 'apollo-admin' ); ?></label>
						<select class="select" name="apollo[seo_404_mode]">
							<option value="simple" <?php selected( $apollo['seo_404_mode'] ?? 'simple', 'simple' ); ?>><?php esc_html_e( 'Simple', 'apollo-admin' ); ?></option>
							<option value="advanced" <?php selected( $apollo['seo_404_mode'] ?? 'simple', 'advanced' ); ?>><?php esc_html_e( 'Advanced (with referrer)', 'apollo-admin' ); ?></option>
						</select>
					</div>
				</div>

				<div class="section-title"><?php esc_html_e( 'Search Spam Filter', 'apollo-admin' ); ?></div>
				<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[seo_spam_filter]" value="1" <?php checked( $apollo['seo_spam_filter'] ?? true ); ?>><span class="switch-track"></span></label>
					<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Filter Search Spam', 'apollo-admin' ); ?></span><span class="toggle-desc"><?php esc_html_e( 'Block spam patterns (TALK:, QQ:, etc.) in search queries', 'apollo-admin' ); ?></span></div>
				</div>
				<div class="form-grid" style="margin-top:8px">
					<div class="field"><label class="field-label"><?php esc_html_e( 'Max Search Query Length', 'apollo-admin' ); ?></label><input type="number" class="apollo-input input" name="apollo[seo_max_query]" value="<?php echo esc_attr( $apollo['seo_max_query'] ?? 100 ); ?>"></div>
				</div>
				<div class="field" style="margin-top:16px"><label class="field-label"><?php esc_html_e( 'Custom robots.txt Rules', 'apollo-admin' ); ?></label><textarea class="apollo-textarea input" rows="4" name="apollo[seo_robots]" placeholder="User-agent: *&#10;Disallow: /wp-admin/&#10;Allow: /wp-admin/admin-ajax.php"><?php echo esc_textarea( $apollo['seo_robots'] ?? '' ); ?></textarea></div>
			</div>
		</div>
	</div>
</div>