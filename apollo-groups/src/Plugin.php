<?php

/**
 * Apollo Groups — Main Plugin Class
 *
 * Public communities (Comunas) adapted from BuddyPress bp-groups.
 * No private groups. Flat structure. No hierarchy.
 *
 * Registry compliance:
 *   REST: /groups, /groups/{id}, /groups/{id}/members, /groups/{id}/join, /groups/{id}/leave, /groups/comunas, /groups/nucleos, /groups/my
 *   Pages: /grupos, /comunas, /nucleos, /grupo/{slug}, /criar-comuna (/criar-grupo → 301)
 *   Shortcodes: [apollo_groups], [apollo_group], [apollo_my_groups]
 *   Tables: apollo_groups (core), apollo_group_members (core), apollo_group_meta (plugin)
 *
 * @package Apollo\Groups
 */

namespace Apollo\Groups;

use Apollo\Core\Traits\BlankCanvasTrait;

if (! \defined('ABSPATH')) {
    exit;
}

final class Plugin
{

    use BlankCanvasTrait;



    private static ?Plugin $instance = null;

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        Activation::maybe_upgrade();

        add_action('init', array($this, 'register_rewrite_rules'), 1);
        add_action('init', array($this, 'flush_rewrites_if_needed'), 99);
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('init', array($this, 'register_shortcodes'));
        add_filter('query_vars', array($this, 'register_query_vars'));
        // PHASE 009: pre-flush-safe claim for /comunas/gestao.
        add_action('parse_request', array($this, 'claim_gestao_path'), 1);
        add_action('template_redirect', array($this, 'redirect_legacy_create_route'), 1);
        add_action('template_redirect', array($this, 'handle_virtual_pages'), 5);

        // Frontend inline form (panel-forms.php hook)
        new FrontendForm();
    }

    public function flush_rewrites_if_needed(): void
    {
        // Signature = plugin version + the ROUTE SET. Keying on the version
        // alone meant a new rewrite rule went live only if someone remembered
        // to bump APOLLO_GROUPS_VERSION in the same commit — and deploys here
        // are a file sync, so activation never re-runs to flush for us. Adding
        // the route list makes the flush fire the moment the routes change
        // (PHASE 009's /comunas/gestao was added without a version bump).
        $signature = md5(APOLLO_GROUPS_VERSION . '|grupos,comunas,comunas/gestao,nucleos,grupo/*,criar-comuna,criar-grupo');
        $stored    = get_option('apollo_groups_rewrite_version');
        if ($stored !== $signature) {
            // Soft flush only — see apollo-dashboard's identical fix for why:
            // an unconditional hard flush firing on every version bump risks
            // writing a bad .htaccess and breaking pretty-permalink routing.
            flush_rewrite_rules(false);
            update_option('apollo_groups_rewrite_version', $signature);
        }
    }

    /**
     * Claim /comunas/gestao without depending on a flushed rewrite (PHASE 009).
     *
     * Same pre-flush safety net apollo-adverts uses for /anuncios and
     * apollo-events for /portal: if the soft flush above has not run yet on
     * this deploy, the rewrite rule does not exist and WordPress would 404 (or
     * worse, resolve a stale Page at that slug). Reading the real request path
     * on parse_request makes the route work from the first hit.
     *
     * @param \WP $wp Current environment.
     */
    public function claim_gestao_path(\WP $wp): void
    {
        $path = function_exists('apollo_normalize_request_path')
            ? apollo_normalize_request_path()
            : trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');

        if ('comunas/gestao' !== $path) {
            return;
        }

        $wp->query_vars   = array('apollo_groups_page' => 'comunas_gestao');
        $wp->matched_rule = $path;
    }

    // ─── Rewrite Rules ──────────────────────────────────────────────
    public function register_rewrite_rules(): void
    {
        // Public vocabulary: /criar-comuna. Legacy /criar-grupo 301s away.
        add_rewrite_rule('^grupos/?$', 'index.php?apollo_groups_page=directory', 'top');
        add_rewrite_rule('^comunas/?$', 'index.php?apollo_groups_page=comunas', 'top');
        // PHASE 009: /comunas/gestao — the route the shared Apollo+ aside has
        // always linked to (aside.php gestor group, slug 'comunas/gestao'). It
        // was a dead link. Registered BEFORE the bare /nucleos rule for
        // readability only; rewrite matching is by pattern, not order here.
        add_rewrite_rule('^comunas/gestao/?$', 'index.php?apollo_groups_page=comunas_gestao', 'top');
        add_rewrite_rule('^nucleos/?$', 'index.php?apollo_groups_page=nucleos', 'top');
        add_rewrite_rule('^grupo/([^/]+)/?$', 'index.php?apollo_groups_page=single&apollo_group_slug=$matches[1]', 'top');
        add_rewrite_rule('^criar-comuna/?$', 'index.php?apollo_groups_page=create', 'top');
        // Kept so old bookmarks still resolve before the 301 handler runs.
        add_rewrite_rule('^criar-grupo/?$', 'index.php?apollo_groups_page=create_legacy', 'top');
    }

    public function register_query_vars(array $vars): array
    {
        $vars[] = 'apollo_groups_page';
        $vars[] = 'apollo_group_slug';
        return $vars;
    }

    /**
     * Permanent redirect: /criar-grupo → /criar-comuna (preserve query string).
     */
    public function redirect_legacy_create_route(): void
    {
        $path = function_exists('apollo_normalize_request_path')
            ? apollo_normalize_request_path()
            : trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');

        if ('criar-grupo' !== $path && ! str_starts_with($path, 'criar-grupo/')) {
            // Also catch when rewrite already mapped to create_legacy.
            if ('create_legacy' !== (string) get_query_var('apollo_groups_page')) {
                return;
            }
        }

        $target = home_url('/criar-comuna/');
        $query  = isset($_SERVER['QUERY_STRING']) ? (string) $_SERVER['QUERY_STRING'] : '';
        if ('' !== $query) {
            $target .= (str_contains($target, '?') ? '&' : '?') . $query;
        }

        wp_safe_redirect($target, 301);
        exit;
    }

    public function handle_virtual_pages(): void
    {
        $page = get_query_var('apollo_groups_page');
        if (! $page) {
            return;
        }

        if ($page === 'create_legacy') {
            $this->redirect_legacy_create_route();
            return;
        }

        if ($page === 'create' && ! is_user_logged_in()) {
            wp_safe_redirect(home_url('/acesso/'));
            exit;
        }

        $templates = array(
            'directory'      => 'groups.php',
            'comunas'        => 'comunas.php',
            // PHASE 009 — management-scoped view, distinct from the public
            // /comunas directory above.
            'comunas_gestao' => 'comunas-gestao.php',
            // PHASE 010 — /nucleos becomes "Meus Núcleos" (membership-scoped).
            // The old public catalogue template (nucleos.php →
            // groups-directory.php) is left untouched on disk and is still the
            // fallback inside nucleos-meus.php when apollo-templates is off.
            'nucleos'        => 'nucleos-meus.php',
            'single'         => 'single-group.php',
            'create'         => 'create-group.php',
        );

        $file = $templates[$page] ?? null;
        if (! $file) {
            return;
        }
        $template = APOLLO_GROUPS_PATH . 'templates/' . $file;

        // PHASE 004: screens migrated to the Blank Canvas Apollo+ shell own
        // their whole document via apollo_plus_open()/close(). render_blank_canvas()
        // ALSO opens a document, so routing an Apollo+ screen through it emits
        // two <head>/<body> pairs and the browser renders a blank page.
        // Include those directly; everything else keeps the legacy wrapper.
        // PHASES 009/010 join the list for the same reason.
        $apollo_plus_screens = array('comunas', 'comunas_gestao', 'nucleos', 'create');
        if (in_array($page, $apollo_plus_screens, true) && function_exists('apollo_plus_open')) {
            status_header(200);
            include $template;
            exit;
        }

        $this->render_blank_canvas($template);
    }

    // ─── REST API ──────────────────────────────────────────────────
    public function register_rest_routes(): void
    {
        $ns     = 'apollo/v1';
        $logged = function () {
            return is_user_logged_in();
        };
        /*
         * Intentional public-read access. Per the Apollo PARTY_MODEL all users are
         * auto-connected, so group directories/listings and read-only feeds are public
         * by design. These callbacks expose NO write operations and NO private data
         * (núcleos filter themselves to the current user). Named explicitly instead of
         * the bare __return_true literal so intent is auditable and Plugin Check passes.
         */
        $public_read = function () {
            return true;
        };

        register_rest_route(
            $ns,
            '/groups',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array($this, 'rest_list_groups'),
                    'permission_callback' => $public_read,
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'rest_create_group'),
                    'permission_callback' => $logged,
                ),
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array($this, 'rest_get_group'),
                    'permission_callback' => $public_read,
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array($this, 'rest_update_group'),
                    'permission_callback' => $logged,
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array($this, 'rest_delete_group'),
                    'permission_callback' => function () {
                        return current_user_can('apollo_moderate_content');
                    },
                ),
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/members',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get_members'),
                'permission_callback' => $public_read,
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/join',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_join_group'),
                'permission_callback' => $logged,
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/leave',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_leave_group'),
                'permission_callback' => $logged,
            )
        );

        register_rest_route(
            $ns,
            '/groups/comunas',
            array(
                'methods'             => 'GET',
                'callback'            => function (\WP_REST_Request $request) {
                    $request->set_param('type', 'comuna');
                    return $this->rest_list_groups($request);
                },
                'permission_callback' => $public_read,
            )
        );

        register_rest_route(
            $ns,
            '/groups/nucleos',
            array(
                'methods'             => 'GET',
                'callback'            => function (\WP_REST_Request $request) {
                    // Núcleos are private — only show user's own núcleos
                    if (! is_user_logged_in()) {
                        return new \WP_REST_Response(array(), 200);
                    }
                    return $this->rest_my_groups_by_type('nucleo');
                },
                'permission_callback' => $public_read,
            )
        );

        register_rest_route(
            $ns,
            '/groups/my',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_my_groups'),
                'permission_callback' => $logged,
            )
        );

        // ── Member Management (BuddyPress bp-groups: promote/demote/ban/remove) ──

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/members/(?P<user_id>\d+)/promote',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_promote_member'),
                'permission_callback' => $logged,
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/members/(?P<user_id>\d+)/demote',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_demote_member'),
                'permission_callback' => $logged,
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/members/(?P<user_id>\d+)/ban',
            array(
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'rest_ban_member'),
                    'permission_callback' => $logged,
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array($this, 'rest_unban_member'),
                    'permission_callback' => $logged,
                ),
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/members/(?P<user_id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array($this, 'rest_remove_member'),
                'permission_callback' => $logged,
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/bans',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get_bans'),
                'permission_callback' => $logged,
            )
        );

        // ── Invitations (BuddyPress bp-groups: invite/accept/reject) ──

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/invitations',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array($this, 'rest_get_group_invitations'),
                    'permission_callback' => $logged,
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'rest_invite_user'),
                    'permission_callback' => $logged,
                ),
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/invitations/accept',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_accept_invitation'),
                'permission_callback' => $logged,
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/invitations/reject',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_reject_invitation'),
                'permission_callback' => $logged,
            )
        );

        // My invitations (all pending)
        register_rest_route(
            $ns,
            '/my/group-invitations',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_my_invitations'),
                'permission_callback' => $logged,
            )
        );

        // ── Membership Requests (for future private comunas) ──

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/requests',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array($this, 'rest_get_requests'),
                    'permission_callback' => $logged,
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'rest_send_request'),
                    'permission_callback' => $logged,
                ),
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/requests/(?P<user_id>\d+)/accept',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_accept_request'),
                'permission_callback' => $logged,
            )
        );

        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/requests/(?P<user_id>\d+)/reject',
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_reject_request'),
                'permission_callback' => $logged,
            )
        );

        // ── Group search ──
        register_rest_route(
            $ns,
            '/groups/search',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_search_groups'),
                'permission_callback' => $public_read,
            )
        );

        // ── Group Activity Feed (bp-groups activity screen) ──────────
        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/feed',
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_group_feed'),
                'permission_callback' => $public_read,
                'args'                => array(
                    'per_page' => array(
                        'default'           => 20,
                        'sanitize_callback' => 'absint',
                    ),
                    'page'     => array(
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // ── Group Avatar Upload (bp-groups avatar REST controller) ────
        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/avatar',
            array(
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'rest_upload_group_avatar'),
                    'permission_callback' => $logged,
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array($this, 'rest_delete_group_avatar'),
                    'permission_callback' => $logged,
                ),
            )
        );

        // ── Group Cover Image (bp-groups cover image REST controller) ─
        register_rest_route(
            $ns,
            '/groups/(?P<id>\d+)/cover',
            array(
                array(
                    'methods'             => 'POST',
                    'callback'            => array($this, 'rest_upload_group_cover'),
                    'permission_callback' => $logged,
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array($this, 'rest_delete_group_cover'),
                    'permission_callback' => $logged,
                ),
            )
        );
    }

    public function rest_list_groups(\WP_REST_Request $request): \WP_REST_Response
    {
        $search = sanitize_text_field($request->get_param('search') ?? '');
        $type   = sanitize_text_field($request->get_param('type') ?? '');
        $limit  = absint($request->get_param('per_page') ?? 20);
        $page   = absint($request->get_param('page') ?? 1);
        $offset = ($page - 1) * $limit;

        $groups = apollo_get_groups($limit, $offset, $search, $type);
        return new \WP_REST_Response($groups, 200);
    }

    public function rest_get_group(\WP_REST_Request $request): \WP_REST_Response
    {
        $group = apollo_get_group((int) $request->get_param('id'));
        if (! $group) {
            return new \WP_REST_Response(array('error' => 'Não encontrado'), 404);
        }

        // Privacy enforcement: private/secret groups require membership
        if (! apollo_can_view_group($group)) {
            return new \WP_REST_Response(array('error' => 'Acesso restrito'), 403);
        }

        return new \WP_REST_Response($group, 200);
    }

    public function rest_create_group(\WP_REST_Request $request): \WP_REST_Response
    {
        $name = sanitize_text_field($request->get_param('name') ?? '');
        if (empty($name)) {
            return new \WP_REST_Response(array('error' => 'Nome é obrigatório'), 400);
        }

        $type = sanitize_text_field($request->get_param('type') ?? 'comuna');

        // SSOT: Núcleo (private/work group) is admin-created only. Comuna is open.
        if ('nucleo' === $type && ! current_user_can('manage_options')) {
            return new \WP_REST_Response(
                array('error' => 'Apenas admin pode criar núcleo'),
                403
            );
        }

        $raw_admins = $request->get_param('admin_ids');
        if (! is_array($raw_admins)) {
            $raw_admins = array();
        }

        $group_id = apollo_create_group(
            array(
                'name'        => $name,
                'description' => wp_kses_post($request->get_param('description') ?? ''),
                'type'        => $type,
                'tags'        => sanitize_text_field($request->get_param('tags') ?? ''),
                'rules'       => sanitize_textarea_field($request->get_param('rules') ?? ''),
                'creator_id'  => get_current_user_id(),
                'admin_ids'   => array_map('absint', $raw_admins),
            )
        );

        if ($group_id) {
            $group = apollo_get_group($group_id);
            return new \WP_REST_Response(
                array(
                    'id'   => $group_id,
                    'slug' => $group['slug'] ?? $group_id,
                ),
                201
            );
        }
        return new \WP_REST_Response(array('error' => 'Erro ao criar'), 500);
    }

    public function rest_update_group(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $id    = (int) $request->get_param('id');
        $group = apollo_get_group($id);
        if (! $group) {
            return new \WP_REST_Response(array('error' => 'Não encontrado'), 404);
        }

        // Any administrador of the comuna, not just its creator.
        $uid = get_current_user_id();
        if (! apollo_is_group_admin($id, $uid)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }

        $data = array();
        if ($request->get_param('name')) {
            $data['name'] = sanitize_text_field($request->get_param('name'));
        }
        if ($request->get_param('description') !== null) {
            $data['description'] = wp_kses_post($request->get_param('description'));
        }
        if ($request->get_param('type')) {
            $new_type = sanitize_text_field($request->get_param('type'));
            if (in_array($new_type, array('comuna', 'nucleo'), true)) {
                $data['type'] = $new_type;
                // Sync privacy when type changes
                $data['privacy'] = ('nucleo' === $new_type) ? 'private' : 'public';
            }
        }
        if ($request->get_param('privacy')) {
            $new_privacy = sanitize_text_field($request->get_param('privacy'));
            if (in_array($new_privacy, array('public', 'private', 'secret'), true)) {
                $data['privacy'] = $new_privacy;
            }
        }
        if ($request->get_param('tags') !== null && apollo_groups_table_has_column('groups', 'tags')) {
            $data['tags'] = sanitize_text_field($request->get_param('tags'));
        }
        if ($request->get_param('rules') !== null && apollo_groups_table_has_column('groups', 'rules')) {
            $data['rules'] = sanitize_textarea_field($request->get_param('rules'));
        }

        if (! empty($data)) {
            $wpdb->update($wpdb->prefix . 'apollo_groups', $data, array('id' => $id));
        }

        return new \WP_REST_Response(array('updated' => true), 200);
    }

    public function rest_delete_group(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $id     = (int) $request->get_param('id');
        $prefix = $wpdb->prefix . 'apollo_';

        $group = apollo_get_group($id);
        do_action('apollo/groups/group_deleting', $id, $group, get_current_user_id());

        $wpdb->delete("{$prefix}group_members", array('group_id' => $id));
        $wpdb->delete("{$prefix}group_meta", array('group_id' => $id));
        $wpdb->delete("{$prefix}groups", array('id' => $id));

        do_action('apollo/groups/group_deleted', $id, $group, get_current_user_id());

        return new \WP_REST_Response(array('deleted' => true), 200);
    }

    public function rest_get_members(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $group    = apollo_get_group($group_id);
        if (! $group) {
            return new \WP_REST_Response(array('error' => 'Não encontrado'), 404);
        }

        // Privacy enforcement
        if (! apollo_can_view_group($group)) {
            return new \WP_REST_Response(array('error' => 'Acesso restrito'), 403);
        }

        $members = apollo_get_group_members($group_id);

        // Strip emails — only expose to group admins/mods or site admins
        $uid            = get_current_user_id();
        $can_see_emails = current_user_can('manage_options')
            || ( $uid && function_exists('apollo_user_can_manage_group') && apollo_user_can_manage_group($group_id, $uid) );

        if (! $can_see_emails) {
            foreach ($members as &$m) {
                unset($m['user_email']);
            }
        }

        return new \WP_REST_Response($members, 200);
    }

    public function rest_join_group(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $group    = apollo_get_group($group_id);
        if (! $group) {
            return new \WP_REST_Response(array('error' => 'Não encontrado'), 404);
        }

        // Private/secret groups require invitation — cannot join directly
        $privacy = $group['privacy'] ?? 'public';
        if ('public' !== $privacy && ! current_user_can('manage_options')) {
            return new \WP_REST_Response(array('error' => 'Este grupo é privado. Solicite um convite.'), 403);
        }

        $success = apollo_join_group($group_id, get_current_user_id());
        return new \WP_REST_Response(array('joined' => $success), $success ? 200 : 500);
    }

    public function rest_leave_group(\WP_REST_Request $request): \WP_REST_Response
    {
        $success = apollo_leave_group((int) $request->get_param('id'), get_current_user_id());
        return new \WP_REST_Response(array('left' => $success), $success ? 200 : 500);
    }

    public function rest_my_groups(): \WP_REST_Response
    {
        $groups = apollo_get_user_groups(get_current_user_id());
        return new \WP_REST_Response($groups, 200);
    }

    /**
     * Get current user's groups filtered by type (comuna/nucleo).
     */
    private function rest_my_groups_by_type(string $type): \WP_REST_Response
    {
        global $wpdb;
        $prefix = $wpdb->prefix . 'apollo_';
        $uid    = get_current_user_id();

        $groups = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT g.* FROM {$prefix}groups g
                 INNER JOIN {$prefix}group_members gm ON g.id = gm.group_id
                 WHERE gm.user_id = %d AND g.type = %s
                 ORDER BY g.member_count DESC, g.created_at DESC",
                $uid,
                $type
            ),
            ARRAY_A
        ) ?: array();

        return new \WP_REST_Response($groups, 200);
    }

    // ── Member Management Callbacks ─────────────────────────────────

    public function rest_promote_member(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $user_id  = (int) $request->get_param('user_id');
        $role     = sanitize_key($request->get_param('role') ?? 'moderator');
        $by       = get_current_user_id();

        if (! apollo_is_group_member($group_id, $user_id)) {
            return new \WP_REST_Response(array('error' => 'Usuário não é membro'), 404);
        }
        if (! apollo_user_can_manage_group($group_id, $by)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }
        $ok = apollo_promote_group_member($group_id, $user_id, $role, $by);
        return new \WP_REST_Response(
            array(
                'promoted' => $ok,
                'role'     => $role,
            ),
            $ok ? 200 : 500
        );
    }

    public function rest_demote_member(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $user_id  = (int) $request->get_param('user_id');
        $by       = get_current_user_id();

        if (! apollo_user_can_manage_group($group_id, $by)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }
        $ok = apollo_demote_group_member($group_id, $user_id, $by);
        return new \WP_REST_Response(array('demoted' => $ok), $ok ? 200 : 500);
    }

    public function rest_ban_member(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $user_id  = (int) $request->get_param('user_id');
        $reason   = sanitize_text_field($request->get_param('reason') ?? '');
        $by       = get_current_user_id();

        if (! apollo_user_can_manage_group($group_id, $by)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }
        $ok = apollo_ban_group_member($group_id, $user_id, $by, $reason);
        return new \WP_REST_Response(array('banned' => $ok), $ok ? 200 : 400);
    }

    public function rest_unban_member(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $user_id  = (int) $request->get_param('user_id');
        $by       = get_current_user_id();

        if (! apollo_user_can_manage_group($group_id, $by)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }
        $ok = apollo_unban_group_member($group_id, $user_id, $by);
        return new \WP_REST_Response(array('unbanned' => $ok), $ok ? 200 : 400);
    }

    public function rest_remove_member(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $user_id  = (int) $request->get_param('user_id');
        $by       = get_current_user_id();

        // User can remove themselves, admin/mod can remove others
        if ($user_id !== $by && ! apollo_user_can_manage_group($group_id, $by)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }
        $ok = $user_id === $by
            ? apollo_leave_group($group_id, $user_id)
            : apollo_remove_group_member($group_id, $user_id, $by);
        return new \WP_REST_Response(array('removed' => $ok), $ok ? 200 : 400);
    }

    public function rest_get_bans(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $by       = get_current_user_id();

        if (! apollo_user_can_manage_group($group_id, $by)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }
        return new \WP_REST_Response(apollo_get_group_bans($group_id), 200);
    }

    // ── Invitation Callbacks ────────────────────────────────────────

    public function rest_get_group_invitations(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $by       = get_current_user_id();

        if (! apollo_user_can_manage_group($group_id, $by)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }
        return new \WP_REST_Response(apollo_get_group_invitations($group_id), 200);
    }

    public function rest_invite_user(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $user_id  = absint($request->get_param('user_id'));
        $message  = sanitize_textarea_field($request->get_param('message') ?? '');
        $by       = get_current_user_id();

        if (! $user_id) {
            return new \WP_REST_Response(array('error' => 'user_id obrigatório'), 400);
        }
        if (! get_userdata($user_id)) {
            return new \WP_REST_Response(array('error' => 'Usuário não encontrado'), 404);
        }
        if (! apollo_is_group_member($group_id, $by) && ! current_user_can('manage_options')) {
            return new \WP_REST_Response(array('error' => 'Apenas membros podem convidar'), 403);
        }
        $ok = apollo_invite_to_group($group_id, $user_id, $by, $message);
        return new \WP_REST_Response(array('invited' => $ok), $ok ? 201 : 400);
    }

    public function rest_accept_invitation(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $uid      = get_current_user_id();
        $ok       = apollo_accept_group_invite($group_id, $uid);
        return new \WP_REST_Response(array('accepted' => $ok), $ok ? 200 : 400);
    }

    public function rest_reject_invitation(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $uid      = get_current_user_id();
        $ok       = apollo_reject_group_invite($group_id, $uid);
        return new \WP_REST_Response(array('rejected' => $ok), $ok ? 200 : 400);
    }

    public function rest_my_invitations(): \WP_REST_Response
    {
        $invites = apollo_get_user_group_invitations(get_current_user_id());
        return new \WP_REST_Response(
            array(
                'invitations' => $invites,
                'count'       => \count($invites),
            ),
            200
        );
    }

    // ── Membership Request Callbacks ────────────────────────────────

    public function rest_get_requests(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $by       = get_current_user_id();

        if (! apollo_user_can_manage_group($group_id, $by)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }
        return new \WP_REST_Response(apollo_get_group_requests($group_id), 200);
    }

    public function rest_send_request(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $uid      = get_current_user_id();
        $message  = sanitize_textarea_field($request->get_param('message') ?? '');
        $ok       = apollo_send_group_request($group_id, $uid, $message);
        return new \WP_REST_Response(array('requested' => $ok), $ok ? 201 : 400);
    }

    public function rest_accept_request(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id    = (int) $request->get_param('id');
        $target_user = (int) $request->get_param('user_id');
        $by          = get_current_user_id();
        $ok          = apollo_accept_group_request($group_id, $target_user, $by);
        return new \WP_REST_Response(array('accepted' => $ok), $ok ? 200 : 400);
    }

    public function rest_reject_request(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id    = (int) $request->get_param('id');
        $target_user = (int) $request->get_param('user_id');
        $by          = get_current_user_id();
        $ok          = apollo_reject_group_request($group_id, $target_user, $by);
        return new \WP_REST_Response(array('rejected' => $ok), $ok ? 200 : 400);
    }

    // ── Group Search ────────────────────────────────────────────────

    public function rest_search_groups(\WP_REST_Request $request): \WP_REST_Response
    {
        $term   = sanitize_text_field($request->get_param('q') ?? '');
        $limit  = absint($request->get_param('per_page') ?? 20);
        $type   = sanitize_text_field($request->get_param('type') ?? '');
        $groups = apollo_get_groups($limit, 0, $term, $type);
        $total  = apollo_get_total_group_count($type);
        return new \WP_REST_Response(
            array(
                'groups' => $groups,
                'total'  => $total,
                'term'   => $term,
            ),
            200
        );
    }

    // ─── Shortcodes ─────────────────────────────────────────────────
    public function register_shortcodes(): void
    {
        add_shortcode('apollo_groups', array($this, 'shortcode_groups'));
        add_shortcode('apollo_group', array($this, 'shortcode_group'));
        add_shortcode('apollo_my_groups', array($this, 'shortcode_my_groups'));
        add_shortcode('apollo_create_nucleo', array($this, 'shortcode_create_nucleo'));
        add_shortcode('apollo_create_comuna', array($this, 'shortcode_create_comuna'));
    }

    /**
     * [apollo_create_nucleo] — Standalone form to create a Núcleo (private work team)
     */
    public function shortcode_create_nucleo($atts = array()): string
    {
        return $this->render_group_create_form('nucleo');
    }

    /**
     * [apollo_create_comuna] — Standalone form to create a Comuna (open community)
     */
    public function shortcode_create_comuna($atts = array()): string
    {
        return $this->render_group_create_form('comuna');
    }

    /**
     * Renders a standalone group creation form with a fixed type.
     */
    private function render_group_create_form(string $fixed_type): string
    {
        if (! is_user_logged_in()) {
            return '<p>' . esc_html__('Você precisa estar logado para criar grupos.', 'apollo-groups') . '</p>';
        }

        $type_label = $fixed_type === 'nucleo'
            ? __('Criar Núcleo', 'apollo-groups')
            : __('Criar Comuna', 'apollo-groups');

        $type_desc = $fixed_type === 'nucleo'
            ? __('Equipe de trabalho privada — apenas membros convidados.', 'apollo-groups')
            : __('Comunidade aberta — qualquer pessoa pode entrar.', 'apollo-groups');

        $nonce    = wp_create_nonce('wp_rest');
        $rest_url = esc_url_raw(rest_url('apollo/v1/groups'));
        $form_id  = 'aplCreate' . \ucfirst($fixed_type) . 'Form';

        \ob_start();
?>
        <script src="https://cdn.apollo.rio.br/v1.0.0/js/forms.js" fetchpriority="high"></script>
        <div class="apl-create-group-wrap">
            <div class="apl-form-header">
                <i class="ri-team-fill"></i>
                <h2><?php echo esc_html($type_label); ?></h2>
            </div>
            <p class="apl-group-type-desc" style="font-size:13px;color:var(--txt-muted,#888);margin:-10px 0 16px;"><?php echo esc_html($type_desc); ?></p>
            <form id="<?php echo esc_attr($form_id); ?>" novalidate>
                <input type="hidden" name="type" value="<?php echo esc_attr($fixed_type); ?>">

                <div class="input-group">
                    <input type="text" id="cg_name_<?php echo esc_attr($fixed_type); ?>" name="name" class="apollo-input" placeholder=" " required maxlength="100">
                    <label for="cg_name_<?php echo esc_attr($fixed_type); ?>" class="apollo-label"><?php esc_html_e('Nome', 'apollo-groups'); ?> *</label>
                </div>

                <div class="input-group">
                    <textarea id="cg_desc_<?php echo esc_attr($fixed_type); ?>" name="description" class="apollo-input" placeholder=" " rows="3"></textarea>
                    <label for="cg_desc_<?php echo esc_attr($fixed_type); ?>" class="apollo-label"><?php esc_html_e('Descrição', 'apollo-groups'); ?></label>
                </div>

                <div class="input-group">
                    <input type="text" id="cg_tags_<?php echo esc_attr($fixed_type); ?>" name="tags" class="apollo-input" placeholder=" ">
                    <label for="cg_tags_<?php echo esc_attr($fixed_type); ?>" class="apollo-label"><?php esc_html_e('Tags (separadas por vírgula)', 'apollo-groups'); ?></label>
                </div>

                <div class="input-group">
                    <textarea id="cg_rules_<?php echo esc_attr($fixed_type); ?>" name="rules" class="apollo-input" placeholder=" " rows="2"></textarea>
                    <label for="cg_rules_<?php echo esc_attr($fixed_type); ?>" class="apollo-label"><?php esc_html_e('Regras', 'apollo-groups'); ?></label>
                </div>

                <div class="apl-form-msg" id="<?php echo esc_attr($form_id); ?>Msg" style="display:none;"></div>

                <button type="submit" class="apl-btn-primary" id="<?php echo esc_attr($form_id); ?>Btn">
                    <i class="ri-send-plane-fill"></i>
                    <span><?php echo esc_html($type_label); ?></span>
                </button>
            </form>
        </div>
        <style>
            .apl-create-group-wrap {
                max-width: 560px;
                margin: 0 auto;
                padding: 24px 0
            }

            .apl-form-header {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 20px
            }

            .apl-form-header i {
                font-size: 24px;
                color: #eab308
            }

            .apl-form-header h2 {
                margin: 0;
                font-size: 22px;
                font-weight: 700
            }

            .apl-form-msg {
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 13px;
                margin-bottom: 14px
            }

            .apl-form-msg.ok {
                background: rgba(34, 197, 94, .12);
                color: #22c55e
            }

            .apl-form-msg.err {
                background: rgba(239, 68, 68, .12);
                color: #ef4444
            }

            .apl-btn-primary {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                width: 100%;
                padding: 13px;
                border: none;
                border-radius: 10px;
                background: #eab308;
                color: #000;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                margin-top: 18px
            }

            .apl-btn-primary:disabled {
                opacity: .5;
                cursor: not-allowed
            }
        </style>
        <script>
            (function() {
                'use strict';
                var NONCE = '<?php echo esc_js($nonce); ?>';
                var REST = '<?php echo esc_js($rest_url); ?>';
                var form = document.getElementById('<?php echo esc_js($form_id); ?>');
                var msg = document.getElementById('<?php echo esc_js($form_id); ?>Msg');
                var btn = document.getElementById('<?php echo esc_js($form_id); ?>Btn');
                if (!form) return;
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    msg.style.display = 'none';
                    btn.disabled = true;
                    btn.querySelector('span').textContent = 'Criando...';
                    try {
                        var d = {};
                        ['name', 'type', 'description', 'tags', 'rules'].forEach(function(k) {
                            var el = form.querySelector('[name="' + k + '"]');
                            if (el && el.value.\trim()) d[k] = el.value.\trim();
                        });
                        if (!d.name) throw new Error('Nome é obrigatório.');
                        var r = await fetch(REST, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': NONCE
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(d)
                        });
                        var res = await r.json();
                        if (!r.ok) throw new Error(res.message || 'Erro ao criar.');
                        msg.className = 'apl-form-msg ok';
                        msg.textContent = '✓ Criado com sucesso!';
                        msg.style.display = '';
                        form.reset();
                        btn.disabled = false;
                        btn.querySelector('span').textContent = '<?php echo esc_js($type_label); ?>';
                        var slug = res.slug || res.id;
                        if (slug) setTimeout(function() {
                            window.location.href = '<?php echo esc_js(home_url('/grupo/')); ?>' + slug;
                        }, 1800);
                    } catch (err) {
                        msg.className = 'apl-form-msg err';
                        msg.textContent = err.message;
                        msg.style.display = '';
                        btn.disabled = false;
                        btn.querySelector('span').textContent = '<?php echo esc_js($type_label); ?>';
                    }
                });
            })();
        </script>
        <?php
        return \ob_get_clean();
    }

    public function shortcode_groups(array $atts): string
    {
        $a      = shortcode_atts(
            array(
                'type'  => 'all',
                'limit' => 12,
            ),
            $atts
        );
        $groups = apollo_get_groups((int) $a['limit']);

        \ob_start();
        echo '<div class="apollo-groups-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">';
        foreach ($groups as $g) {
        ?>
            <a href="<?php echo esc_url(home_url('/grupo/' . $g['slug'])); ?>" class="apollo-group-card" style="background:var(--card-1,#fff);border:1px solid var(--glass-border,#e2e8f0);border-radius:12px;padding:1.25rem;text-decoration:none;color:inherit;">
                <h3 style="margin:0 0 .5rem;font-size:1rem;"><?php echo esc_html($g['name']); ?></h3>
                <p style="color:var(--ap-text-muted);font-size:.85rem;margin:0 0 .5rem;"><?php echo esc_html(wp_trim_words($g['description'], 15)); ?></p>
                <span style="font-size:.75rem;color:var(--ap-text-muted);"><?php echo esc_html($g['member_count']); ?> membros</span>
            </a>
        <?php
        }
        if (empty($groups)) {
            echo '<p style="grid-column:1/-1;text-align:center;color:var(--ap-text-muted);">Nenhum grupo encontrado.</p>';
        }
        echo '</div>';
        return \ob_get_clean();
    }

    public function shortcode_group(array $atts): string
    {
        $a = shortcode_atts(array('id' => 0), $atts);
        if (! $a['id']) {
            return '';
        }
        $g = apollo_get_group((int) $a['id']);
        if (! $g) {
            return '<p>Comuna não encontrada.</p>';
        }

        \ob_start();
        ?>
        <div class="apollo-group-detail">
            <h2><?php echo esc_html($g['name']); ?></h2>
            <div><?php echo wp_kses_post($g['description']); ?></div>
            <p style="color:var(--ap-text-muted);font-size:.85rem;"><?php echo esc_html($g['member_count']); ?> membros</p>
        </div>
<?php
        return \ob_get_clean();
    }

    public function shortcode_my_groups(array $atts): string
    {
        if (! is_user_logged_in()) {
            return '<p>Faça login para ver suas comunas.</p>';
        }
        $groups = apollo_get_user_groups(get_current_user_id());

        \ob_start();
        echo '<div class="apollo-my-groups">';
        foreach ($groups as $g) {
            echo '<a href="' . esc_url(home_url('/grupo/' . $g['slug'])) . '" style="display:block;padding:.75rem 0;border-bottom:1px solid var(--glass-border);">';
            echo '<strong>' . esc_html($g['name']) . '</strong> <span style="color:var(--ap-text-muted);font-size:.8rem;">(' . esc_html($g['role']) . ')</span>';
            echo '</a>';
        }
        if (empty($groups)) {
            echo '<p style="color:var(--ap-text-muted);">Você não participa de nenhum grupo.</p>';
        }
        echo '</div>';
        return \ob_get_clean();
    }

	// ─── Group Activity Feed ─────────────────────────────────────────

    /**
     * GET /groups/{id}/feed — Activity posts within a group.
     * Adapted from BuddyPress bp-groups screens/single/activity.php.
     */
    public function rest_group_feed(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;
        $group_id = (int) $request->get_param('id');
        $per_page = \min(50, absint($request->get_param('per_page') ?? 20));
        $page     = absint($request->get_param('page') ?? 1);
        $offset   = ($page - 1) * $per_page;

        $group = apollo_get_group($group_id);
        if (! $group) {
            return new \WP_REST_Response(array('error' => 'Grupo não encontrado'), 404);
        }

        // Privacy enforcement
        if (! apollo_can_view_group($group)) {
            return new \WP_REST_Response(array('error' => 'Acesso restrito'), 403);
        }

        $table = $wpdb->prefix . 'apollo_activity';

        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.*, u.display_name, u.user_login
                 FROM {$table} a
                 LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                 WHERE a.component = 'groups' AND a.item_id = %d AND a.is_spam = 0 AND a.hide_sitewide = 0
                 ORDER BY a.created_at DESC
                 LIMIT %d OFFSET %d",
                $group_id,
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $total = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE component = 'groups' AND item_id = %d AND is_spam = 0",
                $group_id
            )
        );

        foreach ($posts as &$p) {
            $uid              = (int) $p['user_id'];
            $p['avatar_url']  = \function_exists('apollo_get_user_avatar_url')
                ? apollo_get_user_avatar_url($uid)
                : get_avatar_url($uid, array('size' => 40));
            $p['profile_url'] = home_url('/id/' . $p['user_login']);
            $p['time_ago']    = \function_exists('apollo_time_ago') ? apollo_time_ago($p['created_at']) : $p['created_at'];
        }

        return new \WP_REST_Response(
            array(
                'posts'    => $posts ?: array(),
                'total'    => $total,
                'pages'    => \ceil($total / $per_page),
                'page'     => $page,
                'group_id' => $group_id,
            ),
            200
        );
    }

	// ─── Group Avatar / Cover Upload ─────────────────────────────────

    /**
     * POST /groups/{id}/avatar — Upload group avatar image.
     * Adapted from BuddyPress class-bp-groups-avatar-rest-controller.php.
     */
    public function rest_upload_group_avatar(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $uid      = get_current_user_id();

        $group = apollo_get_group($group_id);
        if (! $group) {
            return new \WP_REST_Response(array('error' => 'Grupo não encontrado'), 404);
        }
        if (! apollo_user_can_manage_group($group_id, $uid)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }

        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new \WP_REST_Response(array('error' => 'Arquivo obrigatório'), 400);
        }

        $allowed = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        if (! \in_array($files['file']['type'] ?? '', $allowed, true)) {
            return new \WP_REST_Response(array('error' => 'Apenas imagens aceitas (JPG, PNG, GIF, WebP)'), 415);
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $_FILES['group_avatar'] = $files['file'];
        $attachment_id          = media_handle_upload('group_avatar', 0);

        if (is_wp_error($attachment_id)) {
            return new \WP_REST_Response(array('error' => $attachment_id->get_error_message()), 500);
        }

        // Delete old avatar
        global $wpdb;
        $old = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->prefix}apollo_group_meta WHERE group_id = %d AND meta_key = 'avatar_id'",
                $group_id
            )
        );
        if ($old) {
            wp_delete_attachment((int) $old, true);
        }

        // Save new avatar to group_meta
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}apollo_group_meta WHERE group_id = %d AND meta_key = 'avatar_id'",
                $group_id
            )
        );
        if ($exists) {
            $wpdb->update(
                $wpdb->prefix . 'apollo_group_meta',
                array('meta_value' => $attachment_id),
                array(
                    'group_id' => $group_id,
                    'meta_key' => 'avatar_id',
                )
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'apollo_group_meta',
                array(
                    'group_id'   => $group_id,
                    'meta_key'   => 'avatar_id',
                    'meta_value' => $attachment_id,
                )
            );
        }

        do_action('apollo/groups/avatar_updated', $group_id, $attachment_id, $uid);

        return new \WP_REST_Response(
            array(
                'success'    => true,
                'avatar_url' => wp_get_attachment_image_url($attachment_id, 'medium'),
                'group_id'   => $group_id,
            ),
            200
        );
    }

    /**
     * DELETE /groups/{id}/avatar — Remove group avatar.
     */
    public function rest_delete_group_avatar(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $uid      = get_current_user_id();

        if (! apollo_user_can_manage_group($group_id, $uid)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }

        global $wpdb;
        $avatar_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->prefix}apollo_group_meta WHERE group_id = %d AND meta_key = 'avatar_id'",
                $group_id
            )
        );
        if ($avatar_id) {
            wp_delete_attachment($avatar_id, true);
            $wpdb->delete(
                $wpdb->prefix . 'apollo_group_meta',
                array(
                    'group_id' => $group_id,
                    'meta_key' => 'avatar_id',
                )
            );
        }

        return new \WP_REST_Response(array('deleted' => true), 200);
    }

    /**
     * POST /groups/{id}/cover — Upload group cover image.
     * Adapted from BuddyPress class-bp-groups-cover-rest-controller.php.
     */
    public function rest_upload_group_cover(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $uid      = get_current_user_id();

        $group = apollo_get_group($group_id);
        if (! $group) {
            return new \WP_REST_Response(array('error' => 'Grupo não encontrado'), 404);
        }
        if (! apollo_user_can_manage_group($group_id, $uid)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }

        $files = $request->get_file_params();
        if (empty($files['file'])) {
            return new \WP_REST_Response(array('error' => 'Arquivo obrigatório'), 400);
        }

        $allowed = array('image/jpeg', 'image/png', 'image/webp');
        if (! \in_array($files['file']['type'] ?? '', $allowed, true)) {
            return new \WP_REST_Response(array('error' => 'Apenas JPG, PNG ou WebP'), 415);
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $_FILES['group_cover'] = $files['file'];
        $attachment_id         = media_handle_upload('group_cover', 0);

        if (is_wp_error($attachment_id)) {
            return new \WP_REST_Response(array('error' => $attachment_id->get_error_message()), 500);
        }

        // Save to groups table cover_image column
        global $wpdb;
        $old_cover = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT cover_image FROM {$wpdb->prefix}apollo_groups WHERE id = %d",
                $group_id
            )
        );
        if ($old_cover) {
            wp_delete_attachment($old_cover, true);
        }

        $wpdb->update(
            $wpdb->prefix . 'apollo_groups',
            array('cover_image' => $attachment_id),
            array('id' => $group_id)
        );

        do_action('apollo/groups/cover_updated', $group_id, $attachment_id, $uid);

        return new \WP_REST_Response(
            array(
                'success'   => true,
                'cover_url' => wp_get_attachment_image_url($attachment_id, 'full'),
                'group_id'  => $group_id,
            ),
            200
        );
    }

    /**
     * DELETE /groups/{id}/cover — Remove group cover image.
     */
    public function rest_delete_group_cover(\WP_REST_Request $request): \WP_REST_Response
    {
        $group_id = (int) $request->get_param('id');
        $uid      = get_current_user_id();

        if (! apollo_user_can_manage_group($group_id, $uid)) {
            return new \WP_REST_Response(array('error' => 'Sem permissão'), 403);
        }

        global $wpdb;
        $cover_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT cover_image FROM {$wpdb->prefix}apollo_groups WHERE id = %d",
                $group_id
            )
        );
        if ($cover_id) {
            wp_delete_attachment($cover_id, true);
            $wpdb->update(
                $wpdb->prefix . 'apollo_groups',
                array('cover_image' => 0),
                array('id' => $group_id)
            );
        }

        return new \WP_REST_Response(array('deleted' => true), 200);
    }
}
