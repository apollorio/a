<?php

/**
 * Plugin — Singleton orquestrador do Apollo Hub
 *
 * @package Apollo\Hub
 */

declare(strict_types=1);

namespace Apollo\Hub;

use Apollo\Core\Traits\BlankCanvasTrait;

if (! defined('ABSPATH')) {
    exit;
}

class Plugin
{

    use BlankCanvasTrait;

    private static ?Plugin $instance = null;

    public function __construct()
    {
        if (null !== self::$instance) {
            return;
        }
        self::$instance = $this;

        new Registry();
        new TemplateLoader();
        new Shortcodes();
        new Integrations();
        new HomePage();

        // Wire block migration hooks
        new Migration();

        if (is_admin()) {
            new Admin\HubAdmin();
        }

        add_action('wp_enqueue_scripts', array($this, 'register_assets'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('init', array($this, 'register_edit_hub_page'), 20);
        add_action('init', array($this, 'register_directory_routes'), 20);
        add_action('parse_request', array($this, 'parse_request_fallback'), 1);
        add_action('init', array($this, 'quarantine_static_hub_shadow'), 2);
        add_action('init', array($this, 'flush_rewrites_if_needed'), 99);
    }

    /**
     * Quarantine a physical ABSPATH/hub file or directory that shadows /hub
     * before WordPress boots (same class of bug as apollo-events /eventos).
     */
    public function quarantine_static_hub_shadow(): void
    {
        if (APOLLO_HUB_VERSION === get_option('apollo_hub_static_shadows')) {
            return;
        }

        $root = rtrim((string) ABSPATH, '/\\');
        $path = $root . DIRECTORY_SEPARATOR . 'hub';
        $dest = $root . DIRECTORY_SEPARATOR . 'hub.shadow-' . gmdate('Ymd-His') . '.bak';

        if (is_file($path)) {
            $head = (string) @file_get_contents($path, false, null, 0, 2048);
            if (preg_match('/^(?:\xEF\xBB\xBF)?\s*<(?:!doctype\s+html|html[\s>])/i', $head)) {
                @rename($path, $dest);
            }
        } elseif (is_dir($path)) {
            $index = $path . DIRECTORY_SEPARATOR . 'index.html';
            $index_htm = $path . DIRECTORY_SEPARATOR . 'index.htm';
            if (is_file($index) || is_file($index_htm) || is_file($path . DIRECTORY_SEPARATOR . 'index.php')) {
                @rename($path, $dest);
            }
        }

        update_option('apollo_hub_static_shadows', APOLLO_HUB_VERSION, false);
    }

    /**
     * Single source of truth for Hub.rio virtual directory routes.
     *
     * @return array<string, string> regex => apollo_hub_view value
     */
    private function directory_route_map(): array
    {
        return array(
            '^hub/?$'          => 'hub',
            '^hub/app/?$'      => 'hub-app',
            '^hub/projetos/?$' => 'hub-projetos',
            '^hub/tarefas/?$'  => 'hub-tarefas',
        );
    }

    /**
     * Flush-independent path → apollo_hub_view map.
     *
     * @return array<string, string>
     */
    private function directory_path_map(): array
    {
        return array(
            'hub'          => 'hub',
            'hub/app'      => 'hub-app',
            'hub/projetos' => 'hub-projetos',
            'hub/tarefas'  => 'hub-tarefas',
        );
    }

    /**
     * Registra as rotas virtuais /hub, /hub/app, /hub/projetos e /hub/tarefas.
     * /hub        — Hub.rio directory (official #/hub) for guests
     * /hub/app    — former /hub HubRio page editor
     * /hub/{user} — single CPT (Registry) — untouched
     *
     * 'top' é obrigatório: sem isso, um post "hub" cujo slug por acaso fosse
     * "projetos" ou "tarefas" poderia vencer a regra do CPT antes desta.
     */
    public function register_directory_routes(): void
    {
        foreach ($this->directory_route_map() as $regex => $view) {
            add_rewrite_rule($regex, 'index.php?apollo_hub_view=' . $view, 'top');
        }
        add_filter('query_vars', array($this, 'add_directory_query_vars'));
        add_action('template_redirect', array($this, 'handle_directory_page'), 5);
    }

    /**
     * Claim /hub* before WP page resolution when rewrites are stale.
     */
    public function parse_request_fallback(\WP $wp): void
    {
        $path = function_exists('apollo_normalize_request_path')
            ? apollo_normalize_request_path()
            : trim((string) parse_url(isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH), '/');

        $map = $this->directory_path_map();
        if (! isset($map[$path])) {
            return;
        }

        $wp->query_vars['apollo_hub_view'] = $map[$path];
        unset(
            $wp->query_vars['pagename'],
            $wp->query_vars['page'],
            $wp->query_vars['name'],
            $wp->query_vars['page_id'],
            $wp->query_vars[APOLLO_HUB_CPT],
            $wp->query_vars['post_type'],
            $wp->query_vars['error']
        );
    }

    /**
     * Adiciona query var da directory.
     *
     * @param array $vars Query vars.
     * @return array
     */
    public function add_directory_query_vars(array $vars): array
    {
        $vars[] = 'apollo_hub_view';
        return $vars;
    }

    /**
     * Renderiza /hub, /hub/app, /hub/projetos ou /hub/tarefas.
     */
    public function handle_directory_page(): void
    {
        $view = (string) get_query_var('apollo_hub_view');
        if ('' === $view) {
            return;
        }

        // Guests get the official Hub.rio directory at /hub.
        // Logged-in users landing on bare /hub go to the page editor at /hub/app
        // (the surface that previously occupied /hub).
        if ('hub' === $view && is_user_logged_in()) {
            wp_safe_redirect(home_url('/hub/app'));
            exit;
        }

        if ('hub-app' === $view) {
            status_header(200);
            include APOLLO_HUB_DIR . 'templates/hub-app.php';
            exit;
        }

        status_header(200);
        include APOLLO_HUB_DIR . 'templates/directory.php';
        exit;
    }

    /**
     * Soft flush + stale route cleanup, gated per rule-set signature — mesmo
     * padrão do apollo-events: FreeFileSync só copia arquivos, então isto
     * publica as novas rotas /hub/* sem exigir "Salvar Permalinks" manual.
     */
    public function flush_rewrites_if_needed(): void
    {
        $signature = md5(APOLLO_HUB_VERSION . '|' . wp_json_encode($this->directory_route_map()) . '|hub-app-v1');
        $stored    = get_option('apollo_hub_rewrite_version');
        if ($signature === $stored) {
            return;
        }

        // Same class of collision apollo-events hit with a stale "portal" WP
        // Page: a published Page at the literal slug "hub" (pre-dating this
        // virtual route) wins WordPress's own page-vs-rewrite resolution
        // before our 'top' rule gets a chance, so /hub kept rendering that
        // Page's content (the HubRio editor) instead of this screen.
        // Draft it — never trash/delete — exactly like the portal fix did.
        $stale = get_page_by_path('hub', OBJECT, 'page');
        if ($stale instanceof \WP_Post && 'publish' === $stale->post_status) {
            wp_update_post(
                array(
                    'ID'          => $stale->ID,
                    'post_status' => 'draft',
                )
            );
        }

        flush_rewrite_rules(false);
        update_option('apollo_hub_rewrite_version', $signature, false);
    }

    /**
     * Registra rewrite virtual para /editar-hub
     */
    public function register_edit_hub_page(): void
    {
        add_rewrite_rule(
            '^' . APOLLO_HUB_EDIT_SLUG . '/?$',
            'index.php?apollo_hub_edit=1',
            'top'
        );
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_edit_hub_page'));
    }

    /**
     * Adiciona query var
     *
     * @param array $vars Query vars.
     * @return array
     */
    public function add_query_vars(array $vars): array
    {
        $vars[] = 'apollo_hub_edit';
        return $vars;
    }

    /**
     * Renderiza página do editor do hub
     */
    public function handle_edit_hub_page(): void
    {
        if (! get_query_var('apollo_hub_edit')) {
            return;
        }

        if (! is_user_logged_in()) {
            wp_redirect(wp_login_url(home_url('/' . APOLLO_HUB_EDIT_SLUG)));
            exit;
        }

        $this->render_blank_canvas(APOLLO_HUB_DIR . 'templates/edit-hub.php');
    }

    /**
     * Registra assets CSS/JS
     */
    public function register_assets(): void
    {
        // CSS principal do Hub
        wp_register_style(
            'apollo-hub',
            APOLLO_HUB_URL . 'assets/css/hub.css',
            array(),
            APOLLO_HUB_VERSION
        );

        // JS do Hub público
        wp_register_script(
            'apollo-hub',
            APOLLO_HUB_URL . 'assets/js/hub.js',
            array(),
            APOLLO_HUB_VERSION,
            true
        );

        // JS do Hub Builder (editor)
        wp_register_script(
            'apollo-hub-builder',
            APOLLO_HUB_URL . 'assets/js/hub-builder.js',
            array('apollo-hub'),
            APOLLO_HUB_VERSION,
            true
        );

        wp_localize_script(
            'apollo-hub',
            'apolloHub',
            array(
                'rest_url'     => esc_url_raw(rest_url(APOLLO_HUB_REST_NAMESPACE)),
                'nonce'        => wp_create_nonce('wp_rest'),
                'plugin_url'   => APOLLO_HUB_URL,
                'edit_url'     => home_url('/' . APOLLO_HUB_EDIT_SLUG),
                'logged_in'    => is_user_logged_in(),
                'current_user' => is_user_logged_in() ? wp_get_current_user()->user_login : '',
                'themes'       => APOLLO_HUB_THEMES,
                'social_icons' => APOLLO_HUB_SOCIAL_ICONS,
                'block_types'  => APOLLO_HUB_BLOCK_TYPES,
                'i18n'         => array(
                    'copy_link'   => __('Copiar link', 'apollo-hub'),
                    'copied'      => __('Link copiado!', 'apollo-hub'),
                    'save'        => __('Salvar', 'apollo-hub'),
                    'saving'      => __('Salvando...', 'apollo-hub'),
                    'saved'       => __('Salvo!', 'apollo-hub'),
                    'add_link'    => __('Adicionar link', 'apollo-hub'),
                    'delete_link' => __('Remover link', 'apollo-hub'),
                    'share'       => __('Compartilhar', 'apollo-hub'),
                    'edit_hub'    => __('Editar Hub', 'apollo-hub'),
                    'no_links'    => __('Nenhum link adicionado ainda.', 'apollo-hub'),
                    'max_links'   => sprintf(__('Máximo de %d links atingido.', 'apollo-hub'), APOLLO_HUB_LINKS_MAX),
                    'bio_max'     => sprintf(__('Bio: máximo %d caracteres.', 'apollo-hub'), APOLLO_HUB_BIO_MAX_LEN),
                    'error_save'  => __('Erro ao salvar. Tente novamente.', 'apollo-hub'),
                ),
            )
        );
    }

    /**
     * Registra rotas REST
     */
    public function register_rest_routes(): void
    {
        $controller = new API\HubController();
        $controller->register_routes();
    }

    /**
     * Retorna instância singleton.
     */
    public static function instance(): ?Plugin
    {
        return self::$instance;
    }
}
