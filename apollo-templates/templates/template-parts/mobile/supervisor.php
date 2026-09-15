<?php

/**
 * CELL · supervisor — isolated parallel unit runner
 *
 * @package Apollo\Templates
 * @since   1.6.0
 */

if (! defined('ABSPATH')) {
    exit;
}
if (defined('APOLLO_MOBILE_CELL_SUPERVISOR')) {
    return;
}
define('APOLLO_MOBILE_CELL_SUPERVISOR', true);

$url = function_exists('apollo_asset_url')
    ? apollo_asset_url(APOLLO_TEMPLATES_DIR, APOLLO_TEMPLATES_URL, 'assets/js/mobile/supervisor.js', APOLLO_TEMPLATES_VERSION)
    : APOLLO_TEMPLATES_URL . 'assets/js/mobile/supervisor.js?ver=' . rawurlencode((string) APOLLO_TEMPLATES_VERSION);
?>
<script<?php echo function_exists('apollo_csp_nonce_attr') ? apollo_csp_nonce_attr() : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url($url); ?>"></script>
