<?php

/**
 * Auth-only <head> extras (/acesso, /registre, /reset, /verificar-email).
 *
 * Canonical document head is rendered by apollo_render_document_head() in the parent template.
 * This partial outputs auth-uni.css and any auth-specific scripts only.
 *
 * @package Apollo\Login
 */

if (! defined('ABSPATH')) {
    exit;
}

if (function_exists('apollo_document_head_was_rendered') && ! apollo_document_head_was_rendered()) {
    if (function_exists('apollo_render_document_head')) {
        apollo_render_document_head(
            array(
                'title'     => esc_html__('Apollo::Rio - Terminal de Acesso', 'apollo-login'),
                'auth_lite' => false,
                'skip_seo'  => true,
            )
        );
    }
}
?>
<link rel="stylesheet"
    href="<?php echo esc_url(APOLLO_LOGIN_URL . 'assets/css/apollo-auth-uni.css?v=2.2.113322' . APOLLO_LOGIN_VERSION); ?>">