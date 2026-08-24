<?php

/**
 * VirtualPageTrait — Unified virtual page routing for Apollo ecosystem.
 *
 * Provides the standard way for ANY Apollo plugin to register URL-based
 * virtual pages that bypass the WP template hierarchy entirely.
 *
 * Pattern: parse_request → set query_var → template_redirect → include + exit
 *
 * Usage in any plugin:
 *   use Apollo\Core\Traits\VirtualPageTrait;
 *   use Apollo\Core\Traits\BlankCanvasTrait;
 *
 *   class MyPlugin {
 *       use BlankCanvasTrait;
 *       use VirtualPageTrait;
 *
 *       public function boot(): void {
 *           $this->init_virtual_pages(
 *               'apollo_myplugin_page',
 *               [
 *                   'minha-rota'       => 'pagina-1',
 *                   'outra-rota/{id}'  => 'pagina-2',
 *               ],
 *               MY_PLUGIN_DIR . 'templates/',
 *               true   // require auth
 *           );
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

trait VirtualPageTrait
{

    /**
     * Virtual page configuration.
     *
     * @var array{
     *     query_var: string,
     *     slug_map: array<string, string>,
     *     templates_dir: string,
     *     require_auth: bool,
     *     auth_redirect: string,
     *     priority: int,
     * }
     */
    private array $virtual_page_config = array();

    /**
     * Initialize virtual page routing.
     *
     * Registers query vars, parse_request handler, and template_redirect handler.
     *
     * @param string $query_var     Unique query variable name (e.g. 'apollo_chat_page').
     * @param array  $slug_map      Map of URL slugs to template identifiers. Example:
     *                              [ 'chat' => 'chat-list', 'chat/inbox' => 'chat-inbox' ].
     * @param string $templates_dir Absolute path to the templates directory (with trailing slash).
     * @param bool   $require_auth  Whether authentication is required (default true).
     * @param int    $priority      Priority for template_redirect hook (default 5).
     * @return void
     */
    protected function init_virtual_pages(
        string $query_var,
        array $slug_map,
        string $templates_dir,
        bool $require_auth = true,
        int $priority = 5
    ): void {
        $this->virtual_page_config = array(
            'query_var'     => $query_var,
            'slug_map'      => $slug_map,
            'templates_dir' => trailingslashit($templates_dir),
            'require_auth'  => $require_auth,
            'auth_redirect' => home_url('/acesso'),
            'priority'      => $priority,
        );

        // Register query var.
        add_filter('query_vars', array($this, 'vpt_register_query_vars'));

        // Intercept parse_request to map URLs → query vars.
        add_action('parse_request', array($this, 'vpt_parse_request'));

        // Handle template_redirect → include + exit.
        add_action('template_redirect', array($this, 'vpt_handle_redirect'), $priority);
    }

    /**
     * Register query vars (filter callback).
     *
     * @param array $vars Existing query vars.
     * @return array Modified query vars.
     */
    public function vpt_register_query_vars(array $vars): array
    {
        $vars[] = $this->virtual_page_config['query_var'];
        return $vars;
    }

    /**
     * Parse request and set query var if URL matches (action callback).
     *
     * Supports dynamic segments with {param} syntax:
     *   'grupo/{slug}' will capture 'slug' as a query var.
     *
     * @param \WP $wp WordPress request object.
     * @return void
     */
    public function vpt_parse_request(\WP $wp): void
    {
        $raw_uri = isset($_SERVER['REQUEST_URI'])
            ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
            : '';
        $path    = trim((string) parse_url($raw_uri, PHP_URL_PATH), '/');

        if (empty($path)) {
            return;
        }

        foreach ($this->virtual_page_config['slug_map'] as $pattern => $template_id) {
            // Check for dynamic segments: {param}.
            if (strpos($pattern, '{') !== false) {
                $regex = $this->vpt_pattern_to_regex($pattern);
                if (preg_match($regex, $path, $matches)) {
                    $wp->query_vars[$this->virtual_page_config['query_var']] = $template_id;

                    // Set dynamic segment values as query vars too.
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $wp->query_vars[$key] = sanitize_text_field($value);
                        }
                    }
                    return;
                }
            } elseif ($path === $pattern) {
                $wp->query_vars[$this->virtual_page_config['query_var']] = $template_id;
                return;
            }
        }
    }

    /**
     * Handle template_redirect: check auth, include template, exit.
     *
     * @return void
     */
    public function vpt_handle_redirect(): void
    {
        $page = get_query_var($this->virtual_page_config['query_var'], '');

        if (empty($page)) {
            return;
        }

        // Auth check.
        if ($this->virtual_page_config['require_auth'] && ! is_user_logged_in()) {
            wp_safe_redirect($this->virtual_page_config['auth_redirect']);
            exit;
        }

        // Resolve template file: templates_dir + page + .php.
        $template_file = $this->virtual_page_config['templates_dir'] . $page . '.php';

        if (! file_exists($template_file)) {
            // Try with directory separator replaced.
            $template_file = $this->virtual_page_config['templates_dir'] . str_replace('-', '/', $page) . '.php';
        }

        if (file_exists($template_file)) {
            $this->render_blank_canvas($template_file, $this->vpt_get_template_vars());
            // render_blank_canvas calls exit — execution stops here.
        }
    }

    /**
     * Get variables to pass to the virtual page template.
     *
     * Override this method in the consuming class to provide custom variables.
     *
     * @return array<string, mixed>
     */
    protected function vpt_get_template_vars(): array
    {
        return array(
            'apollo_page'    => get_query_var($this->virtual_page_config['query_var'], ''),
            'apollo_cdn_url' => defined('APOLLO_CDN_URL') ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/',
            'current_user'   => wp_get_current_user(),
        );
    }

    /**
     * Convert a URL pattern with {param} placeholders to a regex.
     *
     * Example: 'grupo/{slug}' → '#^grupo/(?P<slug>[^/]+)$#'
     *
     * @param string $pattern URL pattern with {param} segments.
     * @return string Regex pattern.
     */
    private function vpt_pattern_to_regex(string $pattern): string
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_]+)\}/',
            static function (array $matches): string {
                return '(?P<' . $matches[1] . '>[^/]+)';
            },
            preg_quote(str_replace(array('{', '}'), array('<<<', '>>>'), $pattern), '#')
        );

        // Restore the named groups that were quoted.
        $regex = str_replace(array('<<<', '>>>'), array('{', '}'), $regex);

        // Re-run proper replacement on the unquoted version.
        $regex = preg_quote($pattern, '#');
        $regex = preg_replace('/\\\\{([a-zA-Z_]+)\\\\}/', '(?P<$1>[^/]+)', $regex);

        return '#^' . $regex . '$#';
    }

    /**
     * Add rewrite rules for the virtual pages.
     *
     * Call this on plugin activation to flush rewrite rules.
     *
     * @return void
     */
    protected function vpt_add_rewrite_rules(): void
    {
        foreach ($this->virtual_page_config['slug_map'] as $pattern => $template_id) {
            // Only add rules for static patterns (no {param}).
            if (strpos($pattern, '{') === false) {
                add_rewrite_rule(
                    '^' . preg_quote($pattern, '/') . '/?$',
                    'index.php?' . $this->virtual_page_config['query_var'] . '=' . $template_id,
                    'top'
                );
            }
        }
    }
}
