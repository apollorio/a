<?php

/**
 * Blank Canvas Apollo+ — shell API.
 *
 * ONE entry point every screen (every phase) uses, so the shell exists in
 * exactly one place and a phase only ever writes its own content:
 *
 *   apollo_plus_open( array( 'title' => 'Feed — Apollo::Rio' ) );
 *       ... screen template-parts ...
 *   apollo_plus_close();
 *
 * "Apollo+" is the shell variant that carries chrome (topbar + aside +
 * panels), as opposed to plain "Apollo" which is zero-chrome (single-event,
 * single-dj, /acesso). See registry chapter 18-canvas-shell.json. The plus is
 * spelled out because "+" is not usable in a PHP identifier.
 *
 * @package Apollo\Templates
 * @since   1.4.0
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('apollo_plus_part')) {
    /**
     * Render one template-part of the Apollo+ system.
     *
     * Resolution order (first hit wins), so a screen can be overridden without
     * editing the plugin that ships it:
     *   1. child theme  /apollo-plus/{slug}.php
     *   2. parent theme /apollo-plus/{slug}.php
     *   3. apollo-templates templates/template-parts/{slug}.php
     *
     * @param string               $slug Part path without extension, e.g. 'feed/stream'.
     * @param array<string, mixed> $vars Extracted into the part's scope.
     */
    function apollo_plus_part(string $slug, array $vars = array()): void
    {
        $slug = ltrim(str_replace(array('..', "\0"), '', $slug), '/');

        $candidates = array(
            get_stylesheet_directory() . '/apollo-plus/' . $slug . '.php',
            get_template_directory() . '/apollo-plus/' . $slug . '.php',
            APOLLO_TEMPLATES_DIR . 'templates/template-parts/' . $slug . '.php',
        );

        foreach ($candidates as $file) {
            if (is_readable($file)) {
                if (! empty($vars)) {
                    // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- deliberate part-scope injection.
                    extract($vars, EXTR_SKIP);
                }
                include $file;
                return;
            }
        }

        // Silent in production; loud for whoever is building the screen.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            echo '<!-- apollo_plus_part: missing part "' . esc_html($slug) . '" -->';
        }
    }
}

if (! function_exists('apollo_plus_open')) {
    /**
     * Open a Blank Canvas Apollo+ document: <head>, topbar, aside, <main>.
     *
     * @param array<string, mixed> $args {
     *     @type string $title      Document title.
     *     @type string $extra_head Trusted HTML appended to <head>.
     *     @type string $screen     Screen slug → data-screen on <main>, used by
     *                              per-screen CSS/JS to scope itself.
     *     @type string $main_class Extra classes on <main class="ax-main">.
     * }
     */
    function apollo_plus_open(array $args = array()): void
    {
        $args = wp_parse_args(
            $args,
            array(
                'title'       => get_bloginfo('name'),
                'extra_head'  => '',
                'screen'      => '',
                'main_class'  => '',
                /* UNIFICATION (2026-08-05) — these two exist so the legacy
                   hand-rolled Apollo+ documents can migrate onto this function
                   without losing behaviour they already had. create-event.php
                   and dashboard-event.php opened their own document with
                   theme:'dark' + html_class:'is-logged'; without these args
                   converting them would have been a silent visual regression,
                   which is exactly how a "unification" turns into a rewrite
                   nobody trusts. Defaults reproduce the previous output byte
                   for byte, so every existing caller is unaffected. */
                'theme'       => '',
                'html_class'  => '',
                'theme_color' => '',
                'skip_seo'    => false,
            )
        );

        /* `ax-body` is the shell contract class (topbar-styles.php hangs
           .ax-body{padding-top:56px} off it) — always present, callers may only
           ADD to it. */
        $html_class = trim('ax-body ' . (string) $args['html_class']);

        if (function_exists('apollo_render_document_open')) {
            $doc = array(
                'title'      => $args['title'],
                'extra_head' => $args['extra_head'],
                'html_class' => $html_class,
                'skip_seo'   => (bool) $args['skip_seo'],
            );
            if ('' !== $args['theme']) {
                $doc['theme'] = $args['theme'];
            }
            if ('' !== (string) $args['theme_color']) {
                $doc['theme_color'] = (string) $args['theme_color'];
            }
            apollo_render_document_open($doc);
        }
        echo "</head>\n<body class=\"ax-shell\">\n";

        /* Topbar CSS BEFORE the topbar markup. app-shell.php has always
           printed .ax-top / #apps-pop / #panel-profile, but their stylesheet
           lived only in apollo-events' shared/shell-styles.php and in
           page-home.php (i.e. /casa) — never here. Without it .ax-top was not
           fixed and the two panels, which are hidden BY their CSS, rendered as
           visible in-flow blocks that pushed the real screen content hundreds
           of pixels down. That was the "content cut in half / can't scroll to
           the end on mobile" defect, on every Apollo+ screen at once. */
        apollo_plus_part('apollo-plus/topbar-styles');

        /* Topbar first (it owns #burger, which the aside binds to), then the
           aside, then the content well. */
        if (function_exists('apollo_render_app_shell')) {
            apollo_render_app_shell();
        }

        apollo_plus_part('apollo-plus/aside');

        printf(
            '<main class="ax-main%s" id="axMain"%s>',
            $args['main_class'] !== '' ? ' ' . esc_attr($args['main_class']) : '',
            $args['screen'] !== '' ? ' data-screen="' . esc_attr($args['screen']) . '"' : ''
        );
    }
}

if (! function_exists('apollo_plus_close')) {
    /**
     * Close the document opened by apollo_plus_open().
     *
     * @param string $extra Trusted HTML emitted before </body>.
     */
    function apollo_plus_close(string $extra = ''): void
    {
        echo '</main>';

        /* Topbar behaviour — #ic-apps and #ic-pf were rendered by
           app-shell.php but bound by nobody outside /casa, so both controls
           were dead on every Apollo+ screen. Loaded here (after the markup,
           like every other shell script) rather than in the head. */
        apollo_plus_part('apollo-plus/topbar-scripts');

        /* Support runtime — every [data-apollo-suporte] control on the page
           binds to this, so it loads once at the shell level rather than each
           screen remembering to include it. */
        do_action('apollo/plus/before_close');

        if (function_exists('apollo_render_document_close')) {
            apollo_render_document_close($extra);
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted caller HTML.
            echo $extra . '</body></html>';
        }
    }
}

if (! function_exists('apollo_plus_user_role_label')) {
    /**
     * Human label for a user's Apollo role.
     *
     * Uses the ecosystem's own role vocabulary (registry 15-conventions), not
     * WordPress's: administrator→apollo, editor→MOD, author→cena+,
     * contributor→cena, subscriber→clubber.
     */
    function apollo_plus_user_role_label(?WP_User $user): string
    {
        if (! $user instanceof WP_User) {
            return '';
        }
        $map = array(
            'administrator' => 'apollo',
            'editor'        => 'MOD',
            'author'        => 'cena+',
            'contributor'   => 'cena',
            'subscriber'    => 'clubber',
        );
        foreach ($map as $role => $label) {
            if (in_array($role, (array) $user->roles, true)) {
                return $label;
            }
        }
        return '';
    }
}

if (! function_exists('apollo_plus_gestor_count')) {
    /**
     * Count for one "Gestor" sidebar row, from real queries.
     *
     * Cached per request — the sidebar renders on every Apollo+ page and this
     * would otherwise fire six COUNT queries on each one. Unknown keys return
     * 0 rather than throwing, so a row can ship before its data layer exists.
     *
     * @param string $key One of the data-cnt keys used in the aside.
     */
    function apollo_plus_gestor_count(string $key): int
    {
        static $cache = array();
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $uid = get_current_user_id();
        if (! $uid) {
            return $cache[$key] = 0;
        }

        $count_posts = static function (string $post_type) use ($uid): int {
            if (! post_type_exists($post_type)) {
                return 0;
            }
            $q = new WP_Query(
                array(
                    'post_type'      => $post_type,
                    'author'         => $uid,
                    'post_status'    => array('publish', 'pending', 'draft'),
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'no_found_rows'  => false,
                )
            );
            return (int) $q->found_posts;
        };

        switch ($key) {
            case 'meus-eventos':
                $n = $count_posts('event');
                break;
            case 'meus-anuncios':
                $n = $count_posts('classified');
                break;
            case 'comunas-gestao':
                $n = $count_posts('comuna') ?: $count_posts('group');
                break;
            case 'meus-nucleos':
                $n = $count_posts('nucleo');
                break;
            case 'meus-projetos':
                $n = $count_posts('projeto');
                break;
            case 'meus-tarefas':
                $n = $count_posts('tarefa');
                break;
            default:
                $n = 0;
        }

        return $cache[$key] = $n;
    }
}

if (! function_exists('apollo_plus_radar_rows')) {
    /**
     * The user's "Eventos Radar" rows — upcoming events they RSVP'd to.
     *
     * Reads apollo-events' RSVP store when present. Returns [] rather than
     * inventing rows if that store isn't available, so the aside shows its
     * empty state instead of fiction.
     *
     * @param int $limit Max rows.
     * @return array<int, array{date:string,title:string,url:string}>
     */
    function apollo_plus_radar_rows(int $limit = 5): array
    {
        $uid = get_current_user_id();
        if (! $uid || ! post_type_exists('event')) {
            return array();
        }

        $ids = function_exists('apollo_event_get_user_rsvps')
            ? (array) apollo_event_get_user_rsvps($uid)
            : array();

        if (empty($ids)) {
            return array();
        }

        $q = new WP_Query(
            array(
                'post_type'      => 'event',
                'post__in'       => array_map('absint', $ids),
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                'meta_key'       => '_event_start_date',
                'orderby'        => 'meta_value',
                'order'          => 'ASC',
                'meta_query'     => array(
                    array(
                        'key'     => '_event_start_date',
                        'value'   => current_time('Y-m-d'),
                        'compare' => '>=',
                        'type'    => 'DATE',
                    ),
                ),
            )
        );

        $rows = array();
        foreach ($q->posts as $p) {
            $start = (string) get_post_meta($p->ID, '_event_start_date', true);
            $rows[] = array(
                'date'  => $start ? date_i18n('d/m', strtotime($start)) : '--/--',
                'title' => get_the_title($p),
                'url'   => (string) get_permalink($p),
            );
        }
        return $rows;
    }
}
