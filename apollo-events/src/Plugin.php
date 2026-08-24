<?php

/**
 * Plugin principal — Singleton
 *
 * Orquestra todos os componentes: Registry, Expiration, Shortcodes, REST, Templates, Dashboard.
 * Segue a filosofia: "Each plugin = ONE focused responsibility. Connected via apollo-core hooks."
 *
 * @package Apollo\Event
 */

namespace Apollo\Event;

use Apollo\Core\Traits\BlankCanvasTrait;

if (! \defined('ABSPATH')) {
    exit;
}

final class Plugin
{

    use BlankCanvasTrait;


    private static ?Plugin $instance = null;

    public static function get_instance(): Plugin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Inicializa todos os componentes do plugin
     */
    public function init(): void
    {
        // Carregar traduções
        add_action('init', array($this, 'load_textdomain'));

        // Virtual pages
        add_action('init', array($this, 'register_rewrite_rules'), 1);
        add_filter('query_vars', array($this, 'register_query_vars'));
        add_action('parse_request', array($this, 'parse_request_fallback'), 1);
        add_action('template_redirect', array($this, 'handle_virtual_pages'), 5);

        // A stale static file in the document root can shadow a virtual route
        // before PHP runs — quarantine it before the route maintenance below.
        add_action('init', array($this, 'quarantine_static_route_shadows'), 2);

        // One-time route maintenance (soft flush + stale page cleanup) on version bump.
        add_action('init', array($this, 'flush_rewrites_if_needed'), 99);

        /* Two debug hooks were removed here on 2026-08-17:
             init:0        debug_959e0d_probe_static_eventos — file I/O against
                           ABSPATH (is_file/filesize/file_get_contents/glob) on
                           EVERY WordPress boot.
             send_headers  debug_959e0d_send_headers — emitted an
                           X-Apollo-Dbg-959e0d response header on EVERY request,
                           publishing the absolute server path, whether it was
                           writable, and the names of any .shadow-*.bak files.
                           That is information disclosure to the open internet.
           Both methods are gone; debug_959e0d_log() is now a no-op. */

        // /portal é rota blank canvas (sem wp_head do tema)
        add_filter('apollo/routes/is_blank_canvas', array($this, 'mark_blank_canvas'), 10, 1);

        // Assets front-end
        add_action('init', array($this, 'register_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Co-authors manage events but must NOT surface on the public single page.
        add_filter('apollo/coauthor/append_byline', array($this, 'disable_event_coauthor_byline'), 10, 2);
        add_filter('the_author', array($this, 'keep_event_primary_author_name'), 99);

        // Equipe do Evento: grant edit/delete/read_post like the author.
        add_filter('map_meta_cap', array($this, 'map_event_team_meta_cap'), 10, 4);

        // Componentes
        $this->init_components();
    }

    /**
     * Never append co-author byline to event content on the public single.
     */
    public function disable_event_coauthor_byline(bool $append, int $post_id): bool
    {
        $post = get_post($post_id);
        if ($post && APOLLO_EVENT_CPT === $post->post_type) {
            return false;
        }
        return $append;
    }

    /**
     * Keep the primary author name on singular events (do not expand co-author names in bylines).
     */
    public function keep_event_primary_author_name(string $author): string
    {
        if (! is_singular(APOLLO_EVENT_CPT)) {
            return $author;
        }
        global $post;
        if (! $post instanceof \WP_Post || APOLLO_EVENT_CPT !== $post->post_type) {
            return $author;
        }
        $user = get_userdata((int) $post->post_author);
        return $user ? (string) $user->display_name : $author;
    }

    /**
     * Equipe do Evento: grant edit/read to team members — never delete.
     *
     * @param string[] $caps    Required capabilities.
     * @param string   $cap     Primitive capability being mapped.
     * @param int      $user_id User ID.
     * @param array    $args    Extra args (post ID at [0]).
     * @return string[]
     */
    public function map_event_team_meta_cap(array $caps, string $cap, int $user_id, array $args): array
    {
        if (! in_array($cap, array('edit_post', 'read_post'), true)) {
            return $caps;
        }

        $post_id = isset($args[0]) ? (int) $args[0] : 0;
        if ($post_id <= 0 || $user_id <= 0) {
            return $caps;
        }

        $post = get_post($post_id);
        if (! $post || APOLLO_EVENT_CPT !== $post->post_type) {
            return $caps;
        }

        if ((int) $post->post_author === $user_id) {
            return $caps;
        }

        if (! apollo_event_user_is_coauthor($post_id, $user_id)) {
            return $caps;
        }

        $pto = get_post_type_object(APOLLO_EVENT_CPT);
        if (! $pto) {
            return $caps;
        }

        $status = get_post_status($post);
        if ('read_post' === $cap) {
            if ('private' === $status) {
                return array($pto->cap->read_private_posts);
            }
            return array('read');
        }

        // edit_post — team may edit event information, not delete the post.
        if ('publish' === $status) {
            return array($pto->cap->edit_published_posts);
        }
        if ('private' === $status) {
            return array($pto->cap->edit_private_posts);
        }
        return array($pto->cap->edit_posts);
    }

    /**
     * Register rewrite rules for virtual event pages
     */
    public function register_rewrite_rules(): void
    {
        foreach ($this->route_map() as $regex => $page) {
            add_rewrite_rule($regex, 'index.php?apollo_event_page=' . $page, 'top');
        }
    }

    /**
     * Single source of truth for event virtual routes → apollo_event_page value.
     * Used by both register_rewrite_rules() and parse_request_fallback() so the
     * two can never drift, and by the flush signature so a rule change always
     * triggers a soft flush on the next request.
     *
     * @return array<string, string>
     */
    private function route_map(): array
    {
        return array(
            // CREATE (add-new event form → create-event.php)
            '^novo-evento/?$'    => 'create',
            '^criar-evento/?$'   => 'create',
            '^add-evento/?$'     => 'create',
            // /eventos/novo — alias matching the Blank Canvas Apollo+ nav's
            // route-naming convention (PHASE 007); same 'create' page, no
            // new template, additive only — the 3 legacy slugs above still
            // work unchanged.
            '^eventos/novo/?$'   => 'create',
            // /eventos/url — Shotgun/BlueTicket URL importer (standalone HTML).
            '^eventos/url/?$'    => 'url_import',
            // DASHBOARD / PAINEL DE EVENTOS (manage view → dashboard-event.php)
            '^meus-eventos/?$'   => 'dashboard',
            '^painel/?$'         => 'dashboard',
            '^painel/eventos/?$' => 'dashboard',
            '^dashboard/?$'      => 'dashboard',
            // /eventos/meus — PHASE 007: the real KPI "Painel de Eventos"
            // mockup screen, Blank Canvas Apollo+ (apollo_plus_open shell,
            // shared .ax-aside). Deliberately a NEW page value, not an alias
            // for 'dashboard' — the legacy 4 slugs above keep rendering the
            // older dashboard-event.php manage-list unchanged; nothing about
            // them is touched by this addition.
            '^eventos/meus/?$'   => 'meus_dashboard',
            // PORTAL DE EVENTOS (public promoter hub → archive-event.php)
            // PHASE 002: /portal used to point at 'dashboard', i.e. the
            // logged-in promoter panel. That was a routing divergence in two
            // directions at once: archive-event.php has always branched on
            // apollo_event_page === 'portal' (dead code, since the map never
            // produced it), and handle_virtual_pages() has always handled a
            // 'portal_archive' value that neither map could ever emit — so the
            // public portal was unreachable while /portal quietly served the
            // private panel. /portal is the PUBLIC events portal; the panel
            // keeps /meus-eventos, /painel and /dashboard.
            '^portal/?$'         => 'portal_archive',
            '^portal/eventos/?$' => 'portal_archive',
            // /eventos — the canonical Portal de Eventos route.
            // It was NOT claimed here, so WordPress resolved it to whatever
            // else answers that slug: production was serving an unrelated
            // "Discover Events" page (a WP Event Manager mockup with hardcoded
            // demo cards and its own full <html> document). Verified from the
            // live site: /eventos returned no #apollo-portal-root and none of
            // this plugin's provenance marker, i.e. the events archive template
            // was never reached. Registered 'top' like every other rule in this
            // map, so the plugin owns its own primary route.
            '^eventos/?$'        => 'portal_archive',
        );
    }

    /**
     * Flush-independent path → apollo_event_page map (no regex anchors).
     *
     * @return array<string, string>
     */
    private function path_map(): array
    {
        return array(
            'novo-evento'    => 'create',
            'criar-evento'   => 'create',
            'add-evento'     => 'create',
            'eventos/novo'   => 'create',
            'eventos/url'    => 'url_import',
            'meus-eventos'   => 'dashboard',
            'painel'         => 'dashboard',
            'painel/eventos' => 'dashboard',
            'dashboard'      => 'dashboard',
            'eventos/meus'   => 'meus_dashboard',
            // See route_map() — /portal is the PUBLIC Portal de Eventos.
            'portal'         => 'portal_archive',
            'portal/eventos' => 'portal_archive',
            'eventos'        => 'portal_archive',
        );
    }

    public function register_query_vars(array $vars): array
    {
        $vars[] = 'apollo_event_page';
        return $vars;
    }

    /**
     * Rewrite-independent route claim (nginx compat / unflushed rewrites).
     * Mirrors apollo-templates pages.php pattern so /portal works even before
     * flush_rewrite_rules() ran on this deploy.
     */
    public function parse_request_fallback(\WP $wp): void
    {
        $path = function_exists('apollo_normalize_request_path')
            ? apollo_normalize_request_path()
            : trim((string) parse_url(isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH), '/');

        $map = $this->path_map();
        if (! isset($map[$path])) {
            // #region agent log
            $this->debug_959e0d_log('D', 'Plugin.php:parse_request_fallback', 'path not in map', array(
                'path' => $path,
                'uri'  => isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '',
            ));
            // #endregion
            return;
        }

        // Authoritative claim — works even when rewrite rules are not (yet) flushed
        // and overrides any competing handler (e.g. apollo-dashboard's /painel/eventos)
        // so the mockup-matching event templates always render for these paths.
        $wp->query_vars['apollo_event_page'] = $map[$path];
        unset(
            $wp->query_vars['pagename'],
            $wp->query_vars['page'],
            $wp->query_vars['name'],
            $wp->query_vars['apollo_dashboard_page'],
            $wp->query_vars['apollo_dashboard_tab']
        );
        // #region agent log
        $this->debug_959e0d_log('D', 'Plugin.php:parse_request_fallback', 'claimed apollo_event_page', array(
            'path' => $path,
            'page' => $map[$path],
            'uri'  => isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '',
        ));
        // #endregion
    }

    // #region agent log
    /** @var array<string, mixed>|null */
    private static ?array $debug_959e0d_static = null;

    /** @var array<int, string> */
    private static array $debug_959e0d_quarantine = array();

    /**
     * NO-OP since 2026-08-17.
     *
     * This used to write every call to APOLLO_EVENT_DIR/debug-959e0d.log and
     * fire wp_remote_post() at http://127.0.0.1:7623 — on a live server, from a
     * path reached during routing. Its two companion methods
     * (debug_959e0d_probe_static_eventos and debug_959e0d_send_headers) are
     * deleted; see the note where their hooks were registered.
     *
     * Kept as an empty method rather than deleted because seven call sites
     * remain in this file. They are inert. Remove them and this stub together
     * in a follow-up — the point of this pass was to stop the I/O, not to churn
     * routing code that works.
     *
     * @param array<string, mixed> $data
     */
    private function debug_959e0d_log(string $hypothesisId, string $location, string $message, array $data): void
    {
    }

    /**
     * Blank canvas marker for apollo_is_blank_canvas_request().
     */
    public function mark_blank_canvas(bool $is_canvas): bool
    {
        if ($is_canvas) {
            return $is_canvas;
        }
        return (bool) get_query_var('apollo_event_page', '');
    }

    /**
     * Quarantine stale extensionless files in the document root that shadow a
     * virtual route.
     *
     * `try_files $uri $uri/ /index.php?$args` (and Apache's !-f equivalent)
     * serves a matching physical file before PHP ever boots, so no rewrite rule
     * or parse_request claim can win. Because the file has no extension the
     * server sends no Content-Type, and with X-Content-Type-Options: nosniff the
     * browser prints the markup as text instead of rendering it.
     *
     * Renames, never deletes, and only touches a file that is a complete HTML
     * document named exactly after one of our routes — so a legitimate
     * extensionless file can't be caught by mistake.
     */
    public function quarantine_static_route_shadows(): void
    {
        if (APOLLO_EVENT_VERSION === get_option('apollo_events_static_shadows')) {
            return;
        }

        $root = rtrim((string) ABSPATH, '/\\');

        foreach (array_keys($this->path_map()) as $slug) {
            // Only top-level slugs can be shadowed by a file in the root.
            if (str_contains($slug, '/')) {
                continue;
            }

            $file = $root . DIRECTORY_SEPARATOR . $slug;
            if (! is_file($file)) {
                continue;
            }

            $head = (string) @file_get_contents($file, false, null, 0, 2048);
            if (! preg_match('/^(?:\xEF\xBB\xBF)?\s*<(?:!doctype\s+html|html[\s>])/i', $head)) {
                continue;
            }

            $moved = @rename($file, $root . DIRECTORY_SEPARATOR . $slug . '.shadow-' . gmdate('Ymd-His') . '.bak');

            // #region agent log
            self::$debug_959e0d_quarantine[] = $slug . '=' . ($moved ? 'moved' : 'failed');
            $this->debug_959e0d_log('A', 'Plugin.php:quarantine_static_route_shadows', 'static route shadow quarantine', array(
                'slug'        => $slug,
                'file'        => $file,
                'moved'       => $moved,
                'dirWritable' => is_writable($root),
            ));
            // #endregion
        }

        // Sibling static directory: production /hub/ was a physical ABSPATH/hub/
        // folder (HubRio editor HTML) that nginx/Apache served before WordPress.
        // Same class of bug as the eventos file shadow — quarantine so apollo-hub
        // virtual routes can boot. Renames the directory; never deletes.
        $hub_dir = $root . DIRECTORY_SEPARATOR . 'hub';
        if (is_dir($hub_dir) && (is_file($hub_dir . DIRECTORY_SEPARATOR . 'index.html') || is_file($hub_dir . DIRECTORY_SEPARATOR . 'index.htm') || is_file($hub_dir . DIRECTORY_SEPARATOR . 'index.php'))) {
            $moved_hub = @rename($hub_dir, $root . DIRECTORY_SEPARATOR . 'hub.shadow-' . gmdate('Ymd-His') . '.bak');
            // #region agent log
            self::$debug_959e0d_quarantine[] = 'hub-dir=' . ($moved_hub ? 'moved' : 'failed');
            $this->debug_959e0d_log('A', 'Plugin.php:quarantine_static_route_shadows', 'static hub directory quarantine', array(
                'dir'         => $hub_dir,
                'moved'       => $moved_hub,
                'dirWritable' => is_writable($root),
            ));
            // #endregion
        }

        update_option('apollo_events_static_shadows', APOLLO_EVENT_VERSION);
    }

    /**
     * Soft flush + stale route cleanup, gated per plugin version.
     * FreeFileSync deploys files only — this makes new rewrites live without
     * manual "Save Permalinks". Soft flush never touches .htaccess.
     */
    public function flush_rewrites_if_needed(): void
    {
        // Flush when the RULE SET changes (signature), not just the plugin version —
        // this guarantees new/edited routes go live on the next request without a
        // manual "Save Permalinks" and without needing a version bump every time.
        $signature = md5(APOLLO_EVENT_VERSION . '|' . wp_json_encode($this->route_map()));
        $stored    = get_option('apollo_events_rewrite_version');
        if ($signature === $stored) {
            return;
        }

        // Stale apollo-djs activation artifact: WP Page 'portal' ("Portal DJ")
        // collides with the events portal virtual route — draft it once.
        $stale = get_page_by_path('portal', OBJECT, 'page');
        if ($stale instanceof \WP_Post && 'publish' === $stale->post_status) {
            wp_update_post(
                array(
                    'ID'          => $stale->ID,
                    'post_status' => 'draft',
                )
            );
        }

        flush_rewrite_rules(false);
        update_option('apollo_events_rewrite_version', $signature);
    }

    public function handle_virtual_pages(): void
    {
        $page = get_query_var('apollo_event_page');
        if (! $page) {
            return;
        }

        // Legacy public promoter archive (kept for direct value use / filters).
        if ('portal_archive' === $page) {
            // #region agent log
            $this->debug_959e0d_log('E', 'Plugin.php:handle_virtual_pages', 'rendering portal_archive', array(
                'uri' => isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '',
            ));
            // #endregion
            if (function_exists('apollo_event_ensure_helpers')) {
                apollo_event_ensure_helpers();
            }
            // Blank Canvas APOLLO+ — carries the mandatory shell chrome.
            $this->render_blank_canvas_plus(
                APOLLO_EVENT_DIR . 'styles/base/archive-event.php',
                array('apollo_portal_context' => true)
            );
            return;
        }

        // Every event virtual page below is a logged-in manage/create surface.
        if (! is_user_logged_in()) {
            wp_safe_redirect(home_url('/acesso'));
            exit;
        }

        if (function_exists('apollo_event_ensure_helpers')) {
            apollo_event_ensure_helpers();
        }

        // ADD NEW EVENT — create form (mockup: add-new/add-new-event.html).
        if ('create' === $page) {
            $this->render_blank_canvas_plus(APOLLO_EVENT_DIR . 'styles/base/create-event.php');
            return;
        }

        // URL IMPORTER — /eventos/url (modular PHP + enqueued JS, create-event pattern).
        if ('url_import' === $page) {
            $this->render_blank_canvas_plus(APOLLO_EVENT_DIR . 'styles/base/url-import.php');
            return;
        }

        // EVENTS PANEL / DASHBOARD — manage view (mockup: dashboard/dashboard.html).
        // Serves /meus-eventos, /painel, /painel/eventos, /dashboard.
        // (/portal moved to the public portal above — see route_map().)
        if ('dashboard' === $page) {
            $this->render_blank_canvas_plus(APOLLO_EVENT_DIR . 'styles/base/dashboard-event.php');
            return;
        }

        // MEUS EVENTOS — real KPI "Painel de Eventos" (PHASE 007), serves
        // only /eventos/meus. Blank Canvas Apollo+ the same way apollo-groups'
        // 'comunas'/'create' screens do it (apollo_plus_open owns the whole
        // document, so it must NOT also go through render_blank_canvas_plus —
        // that would emit two <head>/<body> pairs, see comunas.php's own
        // note on this exact failure mode).
        if ('meus_dashboard' === $page) {
            if (function_exists('apollo_plus_open')) {
                status_header(200);
                include APOLLO_EVENT_DIR . 'styles/base/dashboard-meus.php';
                exit;
            }
            // apollo-templates inactive — fall back to the existing manage
            // view rather than a hard failure.
            $this->render_blank_canvas_plus(APOLLO_EVENT_DIR . 'styles/base/dashboard-event.php');
            return;
        }
    }

    /**
     * Inicializa componentes internos
     */
    private function init_components(): void
    {
        // Registro de CPT/meta via hooks do apollo-core
        new Registry();

        /* Importador de eventos por URL (/eventos/url) — REST server-side.
           Autoloads via PSR-4 from src/Import/. Registers
           POST apollo/v1/eventos/importar-url{,/preview}, both gated on
           edit_posts. The fetch to the ticketing provider happens PHP-side, so
           the browser never makes a cross-origin request and CORS cannot break
           the importer the way it broke the old DOM-scraping approach. */
        new \Apollo\Event\Import\UrlImportController();

        // Sistema de expiração (30 min após end_date + end_time)
        new Expiration();

        // Shortcode [a-eve]
        new Shortcodes();

        // Template Loader
        new TemplateLoader();

        // Schema.org JSON-LD para SEO / rich snippets
        new StructuredData();

        // Integrações com outros plugins Apollo
        new Integrations();

        // Frontend inline form (panel-forms.php hook)
        new FrontendForm();

        // Admin Dashboard (aba em Apollo Dashboard)
        if (is_admin()) {
            new Admin\Dashboard();
            new Admin\Metabox();
        }

        // CENA-RIO (migrated from apollo-shortcodes)
        if ( ! class_exists( Cena_Rio_Submissions::class ) ) {
            $cena_file = APOLLO_EVENT_DIR . 'src/Cena_Rio_Submissions.php';
            if ( ! is_file( $cena_file ) ) {
                $legacy = APOLLO_EVENT_DIR . 'src/CenaRio.php';
                if ( is_file( $legacy ) ) {
                    $cena_file = $legacy;
                }
            }
            if ( is_file( $cena_file ) ) {
                require_once $cena_file;
            }
        }
        if ( class_exists( Cena_Rio_Submissions::class ) ) {
            Cena_Rio_Submissions::init();
        }
    }

    /**
     * Carrega traduções
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'apollo-events',
            false,
            \dirname(APOLLO_EVENT_BASENAME) . '/languages'
        );
    }

    /**
     * Registra assets globais do plugin
     */
    public function register_assets(): void
    {
        // CSS principal
        wp_register_style(
            'apollo-events',
            APOLLO_EVENT_URL . 'assets/css/apollo-events.css',
            array(),
            APOLLO_EVENT_VERSION
        );

        // CSS do calendário
        wp_register_style(
            'apollo-events-calendar',
            APOLLO_EVENT_URL . 'assets/css/apollo-events-calendar.css',
            array('apollo-events'),
            APOLLO_EVENT_VERSION
        );

        // CSS de cards
        wp_register_style(
            'apollo-events-cards',
            APOLLO_EVENT_URL . 'assets/css/apollo-events-cards.css',
            array('apollo-events'),
            APOLLO_EVENT_VERSION
        );

        // Leaflet (OSM map) — cdn.jsdelivr.net, not unpkg.com: unpkg isn't in
        // the CSP allowlist, so the tag was silently blocked (2026-08-01 audit).
        wp_register_style(
            'leaflet',
            'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );

        wp_register_script(
            'leaflet',
            'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );

        // JS principal
        wp_register_script(
            'apollo-events',
            APOLLO_EVENT_URL . 'assets/js/apollo-events.js',
            array('jquery'),
            APOLLO_EVENT_VERSION,
            true
        );

        // JS do calendário
        wp_register_script(
            'apollo-events-calendar',
            APOLLO_EVENT_URL . 'assets/js/apollo-events-calendar.js',
            array('apollo-events'),
            APOLLO_EVENT_VERSION,
            true
        );

        // JS do mapa
        wp_register_script(
            'apollo-events-map',
            APOLLO_EVENT_URL . 'assets/js/apollo-events-map.js',
            array('leaflet'),
            APOLLO_EVENT_VERSION,
            true
        );

        // Lineup Builder (DJ drag + reorder + timetable)
        wp_register_style(
            'apollo-lineup-builder',
            APOLLO_EVENT_URL . 'assets/css/lineup-builder.css',
            array(),
            APOLLO_EVENT_VERSION
        );

        wp_register_script(
            'apollo-lineup-builder',
            APOLLO_EVENT_URL . 'assets/js/lineup-builder.js',
            array(),
            APOLLO_EVENT_VERSION,
            true
        );
    }

    /**
     * Enfileira assets quando necessário
     */
    public function enqueue_assets(): void
    {
        // Variáveis JS globais para REST API
        wp_localize_script(
            'apollo-events',
            'apolloEvents',
            array(
                'rest_url'   => esc_url(rest_url(APOLLO_EVENT_REST_NAMESPACE)),
                'nonce'      => wp_create_nonce('wp_rest'),
                'plugin_url' => APOLLO_EVENT_URL,
                'i18n'       => array(
                    'loading'   => __('Carregando...', 'apollo-events'),
                    'no_events' => __('Nenhum evento encontrado.', 'apollo-events'),
                    'gone'      => __('Finalizado', 'apollo-events'),
                    'today'     => __('Hoje', 'apollo-events'),
                ),
            )
        );
    }

    /**
     * Registra rotas REST conforme apollo-registry.json
     */
    public function register_rest_routes(): void
    {
        $controller = new API\EventsController();
        $controller->register_routes();
    }
}
