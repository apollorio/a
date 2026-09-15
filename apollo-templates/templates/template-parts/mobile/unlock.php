<?php

/**
 * CELL · unlock — Lenis gate + scroll locker purge + touch watchdog
 *
 * @package Apollo\Templates
 * @since   1.6.0
 */

if (! defined('ABSPATH')) {
    exit;
}
if (defined('APOLLO_MOBILE_CELL_UNLOCK')) {
    return;
}
define('APOLLO_MOBILE_CELL_UNLOCK', true);

$css = function_exists('apollo_asset_url')
    ? apollo_asset_url(APOLLO_TEMPLATES_DIR, APOLLO_TEMPLATES_URL, 'assets/css/mobile-premium.css', APOLLO_TEMPLATES_VERSION)
    : APOLLO_TEMPLATES_URL . 'assets/css/mobile-premium.css?ver=' . rawurlencode((string) APOLLO_TEMPLATES_VERSION);
$js = function_exists('apollo_asset_url')
    ? apollo_asset_url(APOLLO_TEMPLATES_DIR, APOLLO_TEMPLATES_URL, 'assets/js/mobile/unlock.js', APOLLO_TEMPLATES_VERSION)
    : APOLLO_TEMPLATES_URL . 'assets/js/mobile/unlock.js?ver=' . rawurlencode((string) APOLLO_TEMPLATES_VERSION);
?>
<link rel="stylesheet" href="<?php echo esc_url($css); ?>">
<script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url($js); ?>"></script>
