<?php

/**
 * Apollo Core — Canonical blank-canvas document head.
 *
 * @package Apollo\Core
 * @since   6.1.0
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('apollo_document_head_debug')) {
    /**
     * NDJSON probe for document-head compliance (sessions 4ad441 + c0df4e).
     *
     * @param string               $message      Log message.
     * @param array<string, mixed> $data         Context payload.
     * @param string               $hypothesisId Hypothesis tag.
     */
    function apollo_document_head_debug(string $message, array $data = array(), string $hypothesisId = ''): void
    {
        // Intentionally empty — debug session instrumentation removed.
    }
}

if (! function_exists('apollo_document_head_was_rendered')) {
    /**
     * Whether the canonical document head has already been output.
     */
    function apollo_document_head_was_rendered(): bool
    {
        return ! empty($GLOBALS['apollo_document_head_rendered']);
    }
}

if (! function_exists('apollo_render_document_head')) {
    /**
     * Output canonical <head> contents for Apollo blank-canvas pages.
     *
     * @param array<string, mixed> $args {
     *     @type string $title         Page title.
     *     @type string $lang          HTML lang attribute. Default 'pt-BR'.
     *     @type string $theme         data-theme attribute. Default 'light'.
     *     @type string $extra_head    Trusted HTML appended before canvas hook.
     *     @type bool   $skip_seo      Skip do_action('apollo/seo/head').
     *     @type bool   $auth_lite     Emit window.__APOLLO_AUTH_LITE__ = true.
     *     @type string $apple_title   apple-mobile-web-app-title. Default 'apollo::rio'.
     *     @type string $root_style_id ID for the empty :root style block.
     * }
     */
    function apollo_render_document_head(array $args = array()): void
    {
        $defaults = array(
            'title'         => 'Apollo::Rio',
            'lang'          => 'pt-BR',
            'theme'         => 'light',
            'extra_head'    => '',
            'skip_seo'      => false,
            'auth_lite'     => false,
            'apple_title'   => 'apollo::rio',
            'root_style_id' => 'apollo-page-tokens',
        );
        $opts = wp_parse_args($args, $defaults);

        $GLOBALS['apollo_document_head_rendered'] = true;

        apollo_document_head_debug(
            'apollo_render_document_head',
            array(
                'title'     => $opts['title'],
                'auth_lite' => (bool) $opts['auth_lite'],
                'skip_seo'  => (bool) $opts['skip_seo'],
            ),
            'A'
        );

        $core_url = function_exists('apollo_cdn_core_js_url')
            ? apollo_cdn_core_js_url()
            : 'https://cdn.apollo.rio.br/v1.0.0/core.js?versao=bb';

        $local_root    = function_exists('apollo_cdn_local_root_url') ? apollo_cdn_local_root_url() : '';
        $core_fallback = '';
        if ($local_root !== '') {
            $core_fallback = $local_root . 'core.js?vers=d3v&dev=local';
        }
        if (defined('APOLLO_CDN_FORCE_LOCAL') && APOLLO_CDN_FORCE_LOCAL && $core_fallback !== '') {
            $core_url      = $core_fallback;
            $core_fallback = '';
        }

        $csp_nonce = isset($GLOBALS['apollo_csp_nonce']) ? (string) $GLOBALS['apollo_csp_nonce'] : '';
        $nonce_attr = $csp_nonce !== '' ? ' nonce="' . esc_attr($csp_nonce) . '"' : '';

        apollo_document_head_debug(
            'canonical head emit',
            array(
                'core_url'         => $core_url,
                'has_preconnect'   => true,
                'has_core_comment' => true,
                'has_csp_nonce'    => $csp_nonce !== '',
            ),
            'H-csp'
        );
        ?>
<meta charset="UTF-8">
<link rel="preconnect" href="https://assets.apollo.rio.br">
<link rel="preconnect" href="https://cdn.apollo.rio.br">
<?php if ((bool) $opts['auth_lite']) : ?>
<script<?php echo $nonce_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>window.__APOLLO_AUTH_LITE__ = true;</script>
<?php endif; ?>
<?php if ($local_root !== '') : ?>
<script<?php echo $nonce_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>window.__APOLLO_LOCAL_CDN_ROOT__ = <?php echo wp_json_encode($local_root); ?>;</script>
<?php endif; ?>
<?php if ($core_fallback !== '') : ?>
<script<?php echo $nonce_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>window.__APOLLO_CORE_FALLBACK__ = <?php echo wp_json_encode($core_fallback); ?>;</script>
<?php endif; ?>
<?php if ($csp_nonce !== '') : ?>
<meta name="apollo-csp-nonce" content="<?php echo esc_attr($csp_nonce); ?>">
<?php endif; ?>
<!--
Mandatory Apollo CORE.JS: global standard of theme and design to all apollo pages!
Loaded on core.js are:
> ICONS: 'Remixicon' + 'apolloIcons', comply with remixicons ´ri-{structure}-line´;
> ANIMATIONS: entire bundle `GSAP v3.15.0` + `Lenis`
> much more loaded injected by cdn.apollo.rio.br/v1.0.0/js/core.js
-->
<script id="apollo-core-js"<?php echo $nonce_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url($core_url); ?>" fetchpriority="high"<?php
        if ($core_fallback !== '') {
            echo ' onerror="if(window.__APOLLO_CORE_FALLBACK__&amp;&amp;!this.dataset.fellback){this.dataset.fellback=\'1\';this.onerror=null;this.src=window.__APOLLO_CORE_FALLBACK__;}"';
        }
        ?>></script>
<style id="<?php echo esc_attr((string) $opts['root_style_id']); ?>">
:root {
/* ALL TOKENS VIA MAIN :root { must be from core.js injected, ONLY LOCAL WEBPAGE EXTRA :root IF  NEEDED then allowed to do only :root for extra element to the page!!! And same: respct is mandatory to injected stylesheets of:  html {} body {} and *{} !important */
}
</style>
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover, interactive-widget=overlays-content">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="format-detection" content="telephone=no">
<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr((string) $opts['apple_title']); ?>">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#101010" media="(prefers-color-scheme: dark)">
<title><?php echo esc_html((string) $opts['title']); ?></title>
<?php
        if (! (bool) $opts['skip_seo']) {
            do_action('apollo/seo/head');
        }

        if (! empty($opts['extra_head'])) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted HTML from plugin templates.
            echo $opts['extra_head'];
        }

        do_action('apollo/canvas/head');
    }
}

if (! function_exists('apollo_json_for_script')) {
    /**
     * JSON-encode a value for safe emission INSIDE an inline <script> block.
     *
     * WHY THIS EXISTS (security, 2026-07-30)
     * --------------------------------------
     * The ecosystem-wide habit is:
     *
     *     <script>window.FOO = <?php echo wp_json_encode( $data ); ?>;</script>
     *
     * That is JSON-safe but NOT HTML-safe. wp_json_encode() does not escape
     * `<`, `>` or `&`, so any author-supplied string inside $data can contain
     *     </script><script>…</script>
     * and break out of the script element. Because the payloads in question
     * carry real DB content — post titles, excerpts, taxonomy term names,
     * user display names — that is a stored XSS reachable by anyone who can
     * publish content, which on this site includes promoters using the
     * frontend forms.
     *
     * The four HEX flags emit &lt; &amp; &#039; &quot; instead. JSON.parse and
     * plain JS literal evaluation both decode them back to the original
     * characters, so consumers see byte-identical values — this changes the
     * transport encoding only, never the data.
     *
     * Use this ANY time a PHP value is printed into an inline <script>.
     * (wp_localize_script() is already safe and remains preferred when the
     * data belongs to a real enqueued handle.)
     *
     * @param mixed $data Any JSON-serialisable value.
     * @return string JSON text safe to print between <script> tags.
     */
    function apollo_json_for_script($data): string
    {
        $json = wp_json_encode(
            $data,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        // wp_json_encode() returns false on malformed input (e.g. invalid
        // UTF-8 from a legacy row). Emit a valid literal rather than printing
        // nothing and producing a syntax error that kills the whole script.
        return false === $json ? 'null' : $json;
    }
}

if (! function_exists('apollo_render_document_open')) {
    /**
     * Output <!DOCTYPE>, <html>, and open <head> with canonical head contents.
     *
     * Does not close </head> — templates may append page-local assets first.
     *
     * @param array<string, mixed> $args Passed to apollo_render_document_head() plus:
     *     @type string $html_class Optional class on <html>.
     *     @type string $html_attrs Optional extra attributes on <html>.
     */
    function apollo_render_document_open(array $args = array()): void
    {
        $lang        = isset($args['lang']) ? (string) $args['lang'] : 'pt-BR';
        $theme       = isset($args['theme']) ? (string) $args['theme'] : 'light';
        $html_class  = isset($args['html_class']) ? (string) $args['html_class'] : '';
        $html_attrs  = isset($args['html_attrs']) ? (string) $args['html_attrs'] : '';

        apollo_document_head_debug(
            'apollo_render_document_open',
            array(
                'lang'  => $lang,
                'theme' => $theme,
            ),
            'A'
        );
        ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr($lang); ?>" data-theme="<?php echo esc_attr($theme); ?>"<?php
        if ($html_class !== '') {
            echo ' class="' . esc_attr($html_class) . '"';
        }
        if ($html_attrs !== '') {
            echo ' ' . $html_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Caller-controlled attrs.
        }
        ?>>
<head>
<?php
        apollo_render_document_head($args);
    }
}

if (! function_exists('apollo_render_document_close')) {
    /**
     * Close blank-canvas document (</body></html>).
     *
     * @param string $extra Optional trusted HTML/JS before </body>.
     */
    function apollo_render_document_close(string $extra = ''): void
    {
        if ($extra !== '') {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted HTML from plugin templates.
            echo $extra;
        }
        ?>
</body>
</html>
<?php
    }
}
