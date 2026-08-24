<?php

/**
 * Apollo Auth — compact card header (apollo::rio shell).
 *
 * @package Apollo\Login
 */

if (! defined('ABSPATH')) {
    exit;
}

$brand_subtitle = isset($apollo_auth_page_subtitle)
    ? $apollo_auth_page_subtitle
    : apply_filters('apollo_auth_brand_subtitle', 'Terminal de Acesso');
?>

<header class="auth-hd" data-tooltip="<?php esc_attr_e('Cabeçalho Apollo', 'apollo-social'); ?>">
    <div class="auth-lg" aria-hidden="true">
        <i class="ri-record-circle-line"></i>
    </div>
    <div class="auth-hd-text">
        <span class="auth-wm">apollo<b>::</b>rio</span>
        <?php if ('' !== $brand_subtitle) : ?>
            <span class="auth-sub"><?php echo esc_html($brand_subtitle); ?></span>
        <?php endif; ?>
    </div>
</header>
