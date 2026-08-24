<?php

/**
 * BlankCanvasTrait — Unified blank canvas rendering for Apollo ecosystem.
 *
 * Provides the standard way for ANY Apollo plugin to render a full-page
 * template without wp_head()/wp_footer() interference (Blank Canvas pattern).
 *
 * Usage in any plugin:
 *   use Apollo\Core\Traits\BlankCanvasTrait;
 *   class MyPlugin { use BlankCanvasTrait; }
 *   $this->render_blank_canvas( PLUGIN_DIR . 'templates/my-page.php', [ 'title' => 'Page' ] );
 *
 * @package Apollo\Core\Traits
 * @since   6.0.0
 */

declare(strict_types=1);

namespace Apollo\Core\Traits;

if (! defined('ABSPATH')) {
    exit;
}

trait BlankCanvasTrait
{

    /**
     * Render a blank canvas template and terminate execution.
     *
     * Sets HTTP 200 status, no-cache headers, extracts variables
     * into the template scope, includes the file, then exits.
     *
     * Variants (mandatory ecosystem layout contract):
     *   'apollo' — bare canvas. Head + page content only, no chrome.
     *   'plus'   — head + MANDATORY shell (burger + topbar with activities /
     *              apps / profile panels, or the login control when logged out).
     *
     * Templates read the active variant with apollo_blank_canvas_is_plus() and
     * open their body with apollo_render_blank_canvas_body(), which prints the
     * shell automatically for 'plus'. Default stays 'apollo' so every existing
     * caller keeps its exact current behaviour.
     *
     * @param string $template_path Absolute path to the template file.
     * @param array  $vars          Associative array of variables to extract into template scope.
     * @param int    $status_code   HTTP status code (default 200).
     * @param string $variant       'apollo' (bare) | 'plus' (with shell).
     * @return never
     */
    protected function render_blank_canvas(string $template_path, array $vars = array(), int $status_code = 200, string $variant = 'apollo'): void
    {
        if (! defined('APOLLO_CANVAS_VARIANT')) {
            define('APOLLO_CANVAS_VARIANT', 'plus' === $variant ? 'plus' : 'apollo');
        }

        if (! file_exists($template_path)) {
            status_header(404);
            wp_die(
                esc_html('Template não encontrado.'),
                'Apollo — 404',
                array('response' => 404)
            );
        }

        // Prevent WordPress from treating this as 404.
        global $wp_query;
        if ($wp_query instanceof \WP_Query) {
            $wp_query->is_404  = false;
            $wp_query->is_page = true;
        }

        status_header($status_code);
        nocache_headers();

        // Remove any remaining output buffers from plugins/themes.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Extract variables into template scope.
        if (! empty($vars)) {
            // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Intentional: passes named vars to template.
            extract($vars, EXTR_SKIP);
        }

        include $template_path;
        exit;
    }

    /**
     * Render a Blank Canvas APOLLO+ page (head + mandatory shell chrome).
     *
     * Sugar for render_blank_canvas( $path, $vars, $status, 'plus' ) so a
     * consumer migrates by renaming the call only — no other edits.
     *
     * @param string $template_path Absolute path to the template file.
     * @param array  $vars          Variables extracted into template scope.
     * @param int    $status_code   HTTP status code (default 200).
     * @return never
     */
    protected function render_blank_canvas_plus(string $template_path, array $vars = array(), int $status_code = 200): void
    {
        $this->render_blank_canvas($template_path, $vars, $status_code, 'plus');
    }

    /**
     * Render a blank canvas template and return the HTML (no exit).
     *
     * Useful for shortcodes or AJAX responses that need the rendered HTML.
     *
     * @param string $template_path Absolute path to the template file.
     * @param array  $vars          Associative array of variables to extract into template scope.
     * @return string Rendered HTML.
     */
    protected function render_blank_canvas_to_string(string $template_path, array $vars = array()): string
    {
        if (! file_exists($template_path)) {
            return '';
        }

        if (! empty($vars)) {
            // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Intentional: passes named vars to template.
            extract($vars, EXTR_SKIP);
        }

        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Get Apollo CDN base URL.
     *
     * @return string CDN URL with trailing slash.
     */
    protected function get_apollo_cdn_url(): string
    {
        return defined('APOLLO_CDN_URL') ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/';
    }

    /**
     * Get Apollo CDN core.js URL.
     *
     * @return string Full URL to core.js.
     */
    protected function get_apollo_cdn_core_js(): string
    {
        return function_exists('apollo_cdn_core_js_url')
            ? apollo_cdn_core_js_url()
            : (defined('APOLLO_CDN_CORE_JS') ? APOLLO_CDN_CORE_JS : 'https://cdn.apollo.rio.br/v1.0.0/core.js?versao=bb');
    }

    /**
     * Output the standard Apollo <head> for blank canvas templates.
     *
     * Includes charset, viewport, theme-color, and CDN core.js.
     *
     * @param string $title Page title.
     * @param string $extra Optional extra HTML to inject in <head>.
     * @return void
     */
    protected function blank_canvas_head(string $title = 'Apollo::Rio', string $extra = ''): void
    {
        if (function_exists('apollo_render_document_open')) {
            apollo_render_document_open(
                array(
                    'title'      => $title,
                    'extra_head' => $extra,
                )
            );
            return;
        }
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
    <meta charset="UTF-8">
    <title><?php echo esc_html($title); ?></title>
    <script src="<?php echo esc_url($this->get_apollo_cdn_core_js()); ?>" fetchpriority="high"></script>
    <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted HTML from plugin code.
            echo $extra;
            ?>
</head>
<?php
    }

    /**
     * Output the blank canvas closing tags.
     *
     * @param string $extra Optional extra HTML/JS before </body>.
     * @return void
     */
    protected function blank_canvas_footer(string $extra = ''): void
    {
        if (function_exists('apollo_render_document_close')) {
            apollo_render_document_close($extra);
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted HTML from plugin code.
        echo $extra;
    ?>
</body>

</html>
<?php
    }
}