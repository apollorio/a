<?php

/**
 * TemplateLocatorTrait — Unified template location with style fallback.
 *
 * Provides the standard 4-level template lookup for Apollo CPT plugins:
 *   1. child-theme/apollo-{plugin}/{style}/{template}
 *   2. parent-theme/apollo-{plugin}/{style}/{template}
 *   3. plugin/styles/{style}/{template}
 *   4. plugin/styles/base/{template}
 *
 * Usage in any CPT plugin:
 *   use Apollo\Core\Traits\TemplateLocatorTrait;
 *
 *   class TemplateLoader {
 *       use TemplateLocatorTrait;
 *
 *       public function __construct() {
 *           $this->init_locator( 'apollo-events', APOLLO_EVENT_DIR, 'card' );
 *           add_filter( 'single_template', [ $this, 'single_template' ] );
 *       }
 *
 *       public function single_template( $template ) {
 *           if ( is_singular( APOLLO_EVENT_CPT ) ) {
 *               return $this->locate( 'single-event.php' ) ?: $template;
 *           }
 *           return $template;
 *       }
 *   }
 *
 * @package Apollo\Core\Traits
 * @since   6.0.0
 */

declare(strict_types=1);

namespace Apollo\Core\Traits;

if (! defined('ABSPATH')) {
    exit;
}

trait TemplateLocatorTrait
{

    /**
     * Theme directory name for template overrides (e.g. 'apollo-events').
     *
     * @var string
     */
    private string $locator_theme_dir = '';

    /**
     * Plugin base directory path (absolute, with trailing slash).
     *
     * @var string
     */
    private string $locator_plugin_dir = '';

    /**
     * Active style name (e.g. 'card', 'grid', 'list').
     *
     * @var string
     */
    private string $locator_style = 'base';

    /**
     * Initialize the template locator.
     *
     * @param string $theme_dir  Directory name in theme for overrides (e.g. 'apollo-events').
     * @param string $plugin_dir Absolute path to the plugin directory.
     * @param string $style      Active style name (default 'base').
     * @return void
     */
    protected function init_locator(string $theme_dir, string $plugin_dir, string $style = 'base'): void
    {
        $this->locator_theme_dir  = $theme_dir;
        $this->locator_plugin_dir = trailingslashit($plugin_dir);
        $this->locator_style      = $style;
    }

    /**
     * Locate a template file using 4-level fallback.
     *
     * @param string      $template_name Filename (e.g. 'single-event.php').
     * @param string|null $style         Override style (null = use default).
     * @return string Absolute path to the template, or empty string if not found.
     */
    protected function locate(string $template_name, ?string $style = null): string
    {
        $style = $style ?? $this->locator_style;

        $paths = array(
            // 1. Child theme override.
            get_stylesheet_directory() . '/' . $this->locator_theme_dir . '/' . $style . '/' . $template_name,
            // 2. Parent theme override.
            get_template_directory() . '/' . $this->locator_theme_dir . '/' . $style . '/' . $template_name,
            // 3. Plugin style directory.
            $this->locator_plugin_dir . 'styles/' . $style . '/' . $template_name,
            // 4. Plugin base fallback.
            $this->locator_plugin_dir . 'styles/base/' . $template_name,
        );

        // Also check templates/ directory as last resort (for plugins without styles/).
        $paths[] = $this->locator_plugin_dir . 'templates/' . $template_name;

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return '';
    }

    /**
     * Render a located template with variables.
     *
     * @param string $template_name Filename to locate and render.
     * @param array  $vars          Variables to extract into template scope.
     * @return void
     */
    protected function render_located(string $template_name, array $vars = array()): void
    {
        $path = $this->locate($template_name);

        if (! $path) {
            return;
        }

        if (! empty($vars)) {
            // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Intentional: passes named vars to template.
            extract($vars, EXTR_SKIP);
        }

        include $path;
    }

    /**
     * Render a located template and return the HTML as string.
     *
     * @param string $template_name Filename to locate and render.
     * @param array  $vars          Variables to extract into template scope.
     * @return string Rendered HTML, or empty string if template not found.
     */
    protected function render_located_to_string(string $template_name, array $vars = array()): string
    {
        $path = $this->locate($template_name);

        if (! $path) {
            return '';
        }

        if (! empty($vars)) {
            // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Intentional: passes named vars to template.
            extract($vars, EXTR_SKIP);
        }

        ob_start();
        include $path;
        return ob_get_clean();
    }

    /**
     * Get the active style name.
     *
     * @return string
     */
    protected function get_active_style(): string
    {
        return $this->locator_style;
    }

    /**
     * Set the active style name.
     *
     * @param string $style Style name.
     * @return void
     */
    protected function set_active_style(string $style): void
    {
        $this->locator_style = $style;
    }
}
