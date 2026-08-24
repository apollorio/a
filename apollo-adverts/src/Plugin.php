<?php

/**
 * Main Plugin Class (Singleton)
 *
 * Adapted from WPAdverts main class + Apollo Core pattern.
 * Handles script registration, REST API init, admin menus.
 *
 * @package Apollo\Adverts
 */

declare(strict_types=1);

namespace Apollo\Adverts;

use Apollo\Core\Traits\BlankCanvasTrait;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{

    use BlankCanvasTrait;



    private static ?Plugin $instance = null;

    public static string $version = '1.0.0';

    public string $directory_path;
    public string $directory_url;

    public static function get_instance(): Plugin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->directory_path = APOLLO_ADVERTS_DIR;
        $this->directory_url  = APOLLO_ADVERTS_URL;
    }

    /**
     * Initialize plugin
     * Adapted from WPAdverts init hooks
     */
    public function init(): void
    {
        // Register scripts and styles
        add_action('init', array($this, 'register_scripts_and_styles'));
        add_action('init', array($this, 'register_image_sizes'));
        add_action('init', array($this, 'register_rewrite_rules'), 1);
        // Rewrite-independent route claim, so /anuncios works even before
        // flush_rewrite_rules() has run on this deploy (same pattern
        // apollo-events uses for /portal).
        add_action('parse_request', array($this, 'claim_marketplace_path'), 1);
        // PHASE 008: soft-flush so /anuncios/meus goes live without a manual
        // Save Permalinks click.
        add_action('init', array($this, 'flush_rewrites_if_needed'), 99);

        // Admin menu
        add_action('admin_menu', array($this, 'admin_menu'));

        // Admin scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));

        // Frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'frontend_scripts'));

        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Admin columns for classified CPT
        add_filter('manage_' . APOLLO_CPT_CLASSIFIED . '_posts_columns', array($this, 'admin_columns'));
        add_action('manage_' . APOLLO_CPT_CLASSIFIED . '_posts_custom_column', array($this, 'admin_column_value'), 10, 2);

        // Save post meta from admin
        add_action('save_post_' . APOLLO_CPT_CLASSIFIED, array($this, 'save_meta_box'), 10, 2);

        // Meta box for classified data
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

        // Single classified: increment views
        add_action('template_redirect', array($this, 'track_views'));

        // Initialize components (adapted from WPAdverts snippets).
        new ForceFeaturedImage();
        new LimitActiveListings();
        new RelatedAds();
        new SpamProtection();
        new FrontendForm();

        // Virtual pages (create classified)
        add_filter(
            'query_vars',
            function ($vars) {
                $vars[] = 'apollo_adverts_page';
                return $vars;
            }
        );
        add_action('template_redirect', array($this, 'handle_virtual_pages'), 5);

        // PHASE 003: the CPT ARCHIVE itself must render the Marketplace too.
        // /anuncios resolves to is_post_type_archive('classified'), and with no
        // template filter registered the ACTIVE THEME rendered it (verified
        // live: Twenty Twenty-Five printing "Arquivos: Anúncios"). The virtual
        // -route claim above only covers the rewrite path; this covers the
        // archive WordPress resolves on its own. Same mechanism apollo-events
        // uses for the events archive.
        add_filter('template_include', array($this, 'marketplace_archive_template'), 99);
    }

    /**
     * Register rewrite rules for /novo-anuncio
     */
    public function register_rewrite_rules(): void
    {
        // PHASE 003: /anuncios is the canonical Marketplace route — it is what
        // the Apollo+ aside links to and what the CPT's own rewrite slug
        // ('anuncio') implies. It was NEVER registered: only /marketplace was,
        // so /anuncios fell through to the plain CPT archive and the ACTIVE
        // THEME rendered it (verified live: Twenty Twenty-Five printing
        // "Arquivos: Anúncios / Nada foi encontrado"). Registered first, 'top',
        // alongside the existing alias.
        add_rewrite_rule('^anuncios/?$', 'index.php?apollo_adverts_page=marketplace', 'top');
        add_rewrite_rule('^marketplace/?$', 'index.php?apollo_adverts_page=marketplace', 'top');
        add_rewrite_rule('^novo-anuncio/?$', 'index.php?apollo_adverts_page=create', 'top');
        add_rewrite_rule('^criar-anuncio/?$', 'index.php?apollo_adverts_page=create', 'top');
        // /anuncios/meus — PHASE 008: real "Meus Anúncios" screen, Blank Canvas
        // Apollo+ (apollo_plus_open shell, shared .ax-aside). Additive: the
        // existing BuddyPress profile tab at /members/{user}/anuncios/meus/
        // (apollo_adverts_bp_setup_nav in includes/buddypress.php) is a
        // completely different URL namespace (BP mounts under the member's own
        // domain, not as a top-level rewrite) and keeps working unchanged.
        add_rewrite_rule('^anuncios/meus/?$', 'index.php?apollo_adverts_page=my_listings', 'top');
    }

    /**
     * Soft-flush rewrite rules when the registered route set changes, so new
     * rules go live without a manual "Save Permalinks" — FreeFileSync only
     * copies files, it never re-runs plugin activation. Same pattern used by
     * apollo-hub/apollo-events (PHASE 005-007).
     */
    public function flush_rewrites_if_needed(): void
    {
        $signature = md5(APOLLO_ADVERTS_VERSION . '|anuncios,marketplace,novo-anuncio,criar-anuncio,anuncios/meus|v1');
        $stored    = get_option('apollo_adverts_rewrite_version');
        if ($signature === $stored) {
            return;
        }
        flush_rewrite_rules(false);
        update_option('apollo_adverts_rewrite_version', $signature, false);
    }

    /**
     * Serve the Marketplace screen for the classified post-type archive.
     *
     * @param string $template Template WordPress resolved.
     * @return string
     */
    public function marketplace_archive_template(string $template): string
    {
        if (! is_post_type_archive(APOLLO_CPT_CLASSIFIED)) {
            return $template;
        }
        $custom = $this->directory_path . 'templates/archive-classified.php';
        return is_readable($custom) ? $custom : $template;
    }

    /**
     * Claim /anuncios (and its aliases) without depending on flushed rewrites.
     */
    public function claim_marketplace_path(\WP $wp): void
    {
        $path = trim((string) parse_url(isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '', PHP_URL_PATH), '/');

        // PHASE 008: /anuncios/meus needs the same pre-flush-safe claim as
        // /anuncios itself, so the new screen works immediately on deploy.
        if ('anuncios/meus' === $path) {
            $wp->query_vars   = array('apollo_adverts_page' => 'my_listings');
            $wp->matched_rule = $path;
            return;
        }

        if (! in_array($path, array('anuncios', 'marketplace'), true)) {
            return;
        }
        $wp->query_vars = array('apollo_adverts_page' => 'marketplace');
        $wp->matched_rule = $path;
    }

    /**
     * Handle virtual pages (create classified)
     */
    public function handle_virtual_pages(): void
    {
        $page = get_query_var('apollo_adverts_page');
        if (! $page) {
            return;
        }

        // #region agent log
        if (function_exists('apollo_is_dev_mode') && apollo_is_dev_mode()) {
            $log = wp_json_encode(array('sessionId' => '4ad441', 'hypothesisId' => 'A', 'location' => 'Plugin.php:handle_virtual_pages', 'message' => 'apollo_adverts_page matched', 'data' => array('page' => (string) $page), 'timestamp' => (int) round(microtime(true) * 1000))) . "\n";
            @file_put_contents(WP_CONTENT_DIR . '/debug-4ad441.log', $log, FILE_APPEND | LOCK_EX);
        }
        // #endregion

        if ($page === 'marketplace') {
            $template = $this->directory_path . 'templates/archive-classified.php';
            if (! is_readable($template)) {
                return;
            }
            status_header(200);
            include $template;
            exit;
        }

        if (! is_user_logged_in()) {
            wp_redirect(home_url('/acesso'));
            exit;
        }

        if ($page === 'create') {
            // BUGFIX (found while building PHASE 008): this branch used to
            // blank-include templates/form.php via render_blank_canvas()
            // with NO $vars — but form.php's own doc-comment says it is
            // "Rendered by [apollo_classified_form] shortcode" and requires
            // $form/$edit_id/$errors/$message/$post to be extracted first.
            // Reached this way, $form was undefined and $form->render()
            // fataled — a real, live break in both /novo-anuncio and
            // /criar-anuncio (the latter also shadows the real, working
            // "Criar Anúncio" WP Page the shortcode normally renders on,
            // created by Activation::activate() at post_name
            // 'criar-anuncio' — same stale-slug-shadowing shape as the
            // apollo-hub /hub collision, except here the rewrite rule wins
            // and was itself broken).
            //
            // Fix: delegate to the real, tested shortcode function — it
            // already reads $_GET['edit'], verifies ownership, runs the
            // POST/validation/save flow, and returns rendered HTML. No
            // duplicated logic, just wired into the Apollo+ shell like every
            // other virtual page in this ecosystem.
            if (function_exists('apollo_plus_open') && function_exists('apollo_adverts_shortcode_form')) {
                $ac_edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
                $ac_html    = apollo_adverts_shortcode_form();
                status_header(200);
                apollo_plus_open(
                    array(
                        'title'  => get_bloginfo('name') . ' — ' . ($ac_edit_id ? __('Editar Anúncio', 'apollo-adverts') : __('Criar Anúncio', 'apollo-adverts')),
                        'screen' => 'anuncios/novo',
                    )
                );
                echo '<div class="ax-main-inner apollo-adverts-form-wrap-outer">' . $ac_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted, already-escaped template HTML.
                apollo_plus_close();
                exit;
            }

            // apollo-templates inactive — fall back to the old bare include
            // (still broken without $vars, but matches this branch's
            // original pre-fix behaviour exactly when the shell is
            // unavailable, rather than guessing a replacement shell).
            $template = $this->directory_path . 'templates/form.php';
            $this->render_blank_canvas($template);
        }

        // MEUS ANÚNCIOS — real "Meus Anúncios" listing (PHASE 008), serves
        // only /anuncios/meus. Blank Canvas Apollo+ the same way phases
        // 005-007 do it (apollo_plus_open owns the whole document, so it
        // must NOT also go through render_blank_canvas_plus — double
        // <head>/<body>, see comunas.php's note on this exact failure mode).
        // Purely additive: the existing BuddyPress "Meus Anúncios" profile
        // tab (/members/{user}/anuncios/meus/, includes/buddypress.php)
        // keeps rendering unchanged — different URL, different chrome.
        if ('my_listings' === $page) {
            $template = $this->directory_path . 'templates/my-listings.php';
            if (function_exists('apollo_plus_open') && is_readable($template)) {
                status_header(200);
                include $template;
                exit;
            }
            // apollo-templates inactive — no legacy predecessor for this
            // exact screen to fall back to; die honestly rather than half
            // -render without the shell.
            wp_die(esc_html__('Apollo Templates: shell API indisponível.', 'apollo-adverts'));
        }
    }

    /**
     * Register scripts and styles
     * Adapted from WPAdverts register_scripts_and_styles()
     */
    public function register_scripts_and_styles(): void
    {
        $v = self::$version;

        // Admin
        wp_register_script('apollo-adverts-admin', $this->directory_url . 'assets/js/admin.js', array('jquery'), $v, true);
        wp_register_style('apollo-adverts-admin', $this->directory_url . 'assets/css/admin.css', array(), $v);

        // Frontend
        wp_register_script('apollo-adverts', $this->directory_url . 'assets/js/classifieds.js', array('jquery'), $v, true);
        wp_register_script('apollo-adverts-gallery', $this->directory_url . 'assets/js/gallery.js', array('jquery', 'plupload-all'), $v, true);
        // Marketplace assets (migrated from apollo-classifieds — FASE 1)
        wp_register_script('apollo-adverts-marketplace', $this->directory_url . 'assets/js/marketplace.js', array('jquery'), $v, true);
        wp_register_style('apollo-adverts-marketplace', $this->directory_url . 'assets/css/marketplace.css', array(), $v);

        $front_css = file_exists(get_stylesheet_directory() . '/apollo-adverts.css')
            ? get_stylesheet_directory_uri() . '/apollo-adverts.css'
            : $this->directory_url . 'assets/css/classifieds.css';
        wp_register_style('apollo-adverts', $front_css, array(), $v);

        // Localize admin JS
        wp_localize_script(
            'apollo-adverts-admin',
            'apolloAdvertsAdmin',
            array(
                'ajax_url'   => admin_url('admin-ajax.php'),
                'rest_url'   => esc_url_raw(rest_url(APOLLO_ADVERTS_REST_NAMESPACE . '/')),
                'rest_nonce' => wp_create_nonce('wp_rest'),
                'nonce'      => wp_create_nonce('apollo_adverts_admin'),
            )
        );

        // Localize frontend JS
        wp_localize_script(
            'apollo-adverts',
            'apolloAdvertsData',
            array(
                'ajax_url'   => admin_url('admin-ajax.php'),
                'rest_url'   => esc_url_raw(rest_url(APOLLO_ADVERTS_REST_NAMESPACE . '/')),
                'nonce'      => wp_create_nonce('apollo_adverts_front'),
                'rest_nonce' => wp_create_nonce('wp_rest'),
                'max_images' => APOLLO_ADVERTS_MAX_IMAGES,
                'i18n'       => array(
                    'confirm_delete' => __('Tem certeza que deseja excluir?', 'apollo-adverts'),
                    'uploading'      => __('Enviando...', 'apollo-adverts'),
                    'upload_error'   => __('Erro no upload', 'apollo-adverts'),
                ),
            )
        );
    }

    /**
     * Register image sizes
     * Adapted from WPAdverts add_image_size calls
     */
    public function register_image_sizes(): void
    {
        foreach (APOLLO_ADVERTS_IMAGE_SIZES as $name => $size) {
            add_image_size($name, $size['width'], $size['height'], $size['crop']);
        }
    }

    /**
     * Admin menu
     * Adapted from WPAdverts admin_menu
     */
    public function admin_menu(): void
    {
        $cap = 'manage_options';

        add_submenu_page(
            'edit.php?post_type=' . APOLLO_CPT_CLASSIFIED,
            __('Configurações', 'apollo-adverts'),
            __('Configurações', 'apollo-adverts'),
            $cap,
            'apollo-adverts-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'edit.php?post_type=' . APOLLO_CPT_CLASSIFIED,
            __('Dashboard', 'apollo-adverts'),
            __('Dashboard', 'apollo-adverts'),
            $cap,
            'apollo-adverts-dashboard',
            array($this, 'render_dashboard_page')
        );
    }

    /**
     * Admin scripts
     */
    public function admin_scripts(): void
    {
        $screen = get_current_screen();
        if (! $screen) {
            return;
        }

        if ($screen->post_type === APOLLO_CPT_CLASSIFIED || strpos($screen->id, 'apollo-adverts') !== false) {
            wp_enqueue_script('apollo-adverts-admin');
            wp_enqueue_style('apollo-adverts-admin');
        }
    }

    /**
     * Frontend scripts
     */
    public function frontend_scripts(): void
    {
        // Enqueued on-demand by shortcodes (not globally)
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes(): void
    {
        if (class_exists('\Apollo\Adverts\API\ClassifiedsController')) {
            (new API\ClassifiedsController())->register_routes();
        }
        if (class_exists('\Apollo\Adverts\API\SearchController')) {
            (new API\SearchController())->register_routes();
        }
    }

    /**
     * Add admin columns
     * Adapted from WPAdverts admin-post-type.php
     */
    public function admin_columns(array $columns): array
    {
        $new = array();
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'title') {
                $new['classified_price']   = __('Valor Ref.', 'apollo-adverts');
                $new['classified_intent']  = __('Intenção', 'apollo-adverts');
                $new['classified_expires'] = __('Expira', 'apollo-adverts');
            }
        }
        return $new;
    }

    /**
     * Render admin column values
     */
    public function admin_column_value(string $column, int $post_id): void
    {
        switch ($column) {
            case 'classified_price':
                $price = apollo_adverts_get_the_price($post_id);
                echo $price ? esc_html($price) : '—';
                break;
            case 'classified_intent':
                echo esc_html(apollo_adverts_get_intent_label($post_id));
                break;
            case 'classified_expires':
                $exp = get_post_meta($post_id, '_classified_expires_at', true);
                if ($exp) {
                    $is_expired = apollo_adverts_is_expired($post_id);
                    $color      = $is_expired ? 'color:#d63638' : '';
                    printf('<span style="%s">%s</span>', esc_attr($color), esc_html($exp));
                } else {
                    echo '—';
                }
                break;
        }
    }

    /**
     * Add meta boxes
     * Adapted from WPAdverts adverts_data_box
     */
    public function add_meta_boxes(): void
    {
        add_meta_box(
            'apollo_classified_data',
            __('Dados do Anúncio', 'apollo-adverts'),
            array($this, 'render_meta_box'),
            APOLLO_CPT_CLASSIFIED,
            'normal',
            'high'
        );
    }

    /**
     * Render classified data meta box
     * Adapted from WPAdverts admin meta box
     */
    public function render_meta_box(\WP_Post $post): void
    {
        wp_nonce_field('apollo_adverts_save_meta', 'apollo_adverts_meta_nonce');

        $meta_keys  = APOLLO_ADVERTS_META_KEYS;
        $conditions = APOLLO_ADVERTS_CONDITIONS;
        $intents    = APOLLO_ADVERTS_INTENTS;

        echo '<table class="form-table">';

        // ── Advert kind ───────────────────────────────────────────────────
        // _classified_type drives every marketplace query and every
        // type-specific field group below, yet had no admin control at all —
        // so a mis-typed advert could not be repaired from wp-admin, only
        // through the REST API. Aliases are shown as their canonical value
        // (see apollo_adverts_canonical_type) so the two vocabularies can
        // never diverge again from this screen.
        $type_raw = (string) get_post_meta($post->ID, '_classified_type', true);
        $type     = function_exists('apollo_adverts_canonical_type')
            ? apollo_adverts_canonical_type($type_raw)
            : ($type_raw ?: 'general');
        $type_labels = array(
            'general'       => __('Geral', 'apollo-adverts'),
            'ticket'        => __('Ingresso (revenda)', 'apollo-adverts'),
            'accommodation' => __('Hospedagem', 'apollo-adverts'),
        );
        echo '<tr><th><label for="_classified_type">' . esc_html__('Tipo de anúncio', 'apollo-adverts') . '</label></th><td><select name="_classified_type" id="_classified_type">';
        foreach ($type_labels as $val => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($type, $val, false), esc_html($label));
        }
        echo '</select>';
        if ($type_raw && $type_raw !== $type) {
            printf(
                '<p class="description">%s</p>',
                esc_html(
                    sprintf(
                        /* translators: 1: legacy stored value, 2: canonical value. */
                        __('Este anúncio foi salvo como "%1$s"; ao salvar será normalizado para "%2$s".', 'apollo-adverts'),
                        $type_raw,
                        $type
                    )
                )
            );
        }
        echo '</td></tr>';

        // Reference value (informational only)
        $price = get_post_meta($post->ID, '_classified_price', true);
        printf(
            '<tr><th><label for="_classified_price">%s</label></th><td><input type="text" name="_classified_price" id="_classified_price" value="%s" class="regular-text" /><p class="description">%s</p></td></tr>',
            esc_html__('Valor de Referência (R$)', 'apollo-adverts'),
            esc_attr($price),
            esc_html__('Apenas informativo. Apollo conecta pessoas — não processa transações.', 'apollo-adverts')
        );

        // Currency
        $currency   = get_post_meta($post->ID, '_classified_currency', true) ?: 'BRL';
        $currencies = array(
            'BRL' => 'R$ — Real',
            'USD' => '$ — Dólar',
            'EUR' => '€ — Euro',
        );
        echo '<tr><th><label for="_classified_currency">' . esc_html__('Moeda', 'apollo-adverts') . '</label></th><td><select name="_classified_currency" id="_classified_currency">';
        foreach ($currencies as $val => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($currency, $val, false), esc_html($label));
        }
        echo '</select></td></tr>';

        // Negotiable
        $negotiable = get_post_meta($post->ID, '_classified_negotiable', true);
        printf(
            '<tr><th><label for="_classified_negotiable">%s</label></th><td><input type="checkbox" name="_classified_negotiable" id="_classified_negotiable" value="1" %s /></td></tr>',
            esc_html__('Negociável', 'apollo-adverts'),
            checked($negotiable, '1', false)
        );

        // Condition
        $condition = get_post_meta($post->ID, '_classified_condition', true);
        echo '<tr><th><label for="_classified_condition">' . esc_html__('Condição', 'apollo-adverts') . '</label></th><td><select name="_classified_condition" id="_classified_condition">';
        foreach ($conditions as $val => $label) {
            printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($condition, $val, false), esc_html($label));
        }
        echo '</select></td></tr>';

        // Location
        $location = get_post_meta($post->ID, '_classified_loc', true);
        printf(
            '<tr><th><label for="_classified_loc">%s</label></th><td><input type="text" name="_classified_loc" id="_classified_loc" value="%s" class="regular-text" /></td></tr>',
            esc_html__('Localização', 'apollo-adverts'),
            esc_attr($location)
        );

        // Phone
        $phone = get_post_meta($post->ID, '_classified_contact_phone', true);
        printf(
            '<tr><th><label for="_classified_contact_phone">%s</label></th><td><input type="text" name="_classified_contact_phone" id="_classified_contact_phone" value="%s" class="regular-text" /></td></tr>',
            esc_html__('Telefone', 'apollo-adverts'),
            esc_attr($phone)
        );

        // WhatsApp
        $whatsapp = get_post_meta($post->ID, '_classified_contact_whatsapp', true);
        printf(
            '<tr><th><label for="_classified_contact_whatsapp">%s</label></th><td><input type="text" name="_classified_contact_whatsapp" id="_classified_contact_whatsapp" value="%s" class="regular-text" /></td></tr>',
            esc_html__('WhatsApp', 'apollo-adverts'),
            esc_attr($whatsapp)
        );

        // Expires at
        $expires = get_post_meta($post->ID, '_classified_expires_at', true);
        printf(
            '<tr><th><label for="_classified_expires_at">%s</label></th><td><input type="date" name="_classified_expires_at" id="_classified_expires_at" value="%s" /></td></tr>',
            esc_html__('Expira em', 'apollo-adverts'),
            esc_attr($expires)
        );

        // Featured — editorial flag, admin only (see save_meta_box). A seller
        // must not be able to promote their own advert.
        if (current_user_can('manage_options')) {
            $featured = get_post_meta($post->ID, '_classified_featured', true);
            printf(
                '<tr><th><label for="_classified_featured">%s</label></th><td><input type="checkbox" name="_classified_featured" id="_classified_featured" value="1" %s /></td></tr>',
                esc_html__('Destaque', 'apollo-adverts'),
                checked($featured, '1', false)
            );
        }

        echo '</table>';

        // ── Ingresso (revenda) ────────────────────────────────────────────
        // The event snapshot + quantity that card-ticket.php renders. The
        // frontend sell form always collected these, but nothing persisted
        // them and wp-admin had no fields either, so every resale card
        // rendered with an empty event block. Folded away unless the advert
        // is a ticket.
        $is_ticket_ad = in_array($type, APOLLO_ADVERTS_TICKET_TYPES, true);
        $ev_id        = (int) get_post_meta($post->ID, '_classified_event_id', true);
        $ev_title     = get_post_meta($post->ID, '_classified_event_title', true);
        $ev_date      = get_post_meta($post->ID, '_classified_event_date', true);
        $ev_loc       = get_post_meta($post->ID, '_classified_event_loc', true);
        $qty          = get_post_meta($post->ID, '_classified_quantity', true);

        echo '<div id="apollo-ticket-block" style="' . ($is_ticket_ad ? '' : 'display:none;') . '">';
        echo '<hr><h3 style="margin:14px 0 4px;">' . esc_html__('Ingresso (revenda)', 'apollo-adverts') . '</h3>';
        echo '<table class="form-table">';

        // Link to a real Apollo event — selecting one refreshes the snapshot
        // fields below on save.
        $events = get_posts(
            array(
                'post_type'      => 'event',
                'post_status'    => 'publish',
                'posts_per_page' => 200,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );
        echo '<tr><th><label for="_classified_event_id">' . esc_html__('Evento vinculado', 'apollo-adverts') . '</label></th><td><select name="_classified_event_id" id="_classified_event_id">';
        printf('<option value="0">%s</option>', esc_html__('— Nenhum (preencher manualmente) —', 'apollo-adverts'));
        foreach ($events as $ev) {
            printf('<option value="%d" %s>%s</option>', (int) $ev->ID, selected($ev_id, $ev->ID, false), esc_html($ev->post_title));
        }
        echo '</select><p class="description">' . esc_html__('Ao selecionar um evento, título/data/local abaixo são preenchidos automaticamente ao salvar.', 'apollo-adverts') . '</p></td></tr>';

        printf(
            '<tr><th><label for="_classified_event_title">%s</label></th><td><input type="text" name="_classified_event_title" id="_classified_event_title" value="%s" class="regular-text" /></td></tr>',
            esc_html__('Evento', 'apollo-adverts'),
            esc_attr($ev_title)
        );
        printf(
            '<tr><th><label for="_classified_event_date">%s</label></th><td><input type="date" name="_classified_event_date" id="_classified_event_date" value="%s" /></td></tr>',
            esc_html__('Data do evento', 'apollo-adverts'),
            esc_attr($ev_date)
        );
        printf(
            '<tr><th><label for="_classified_event_loc">%s</label></th><td><input type="text" name="_classified_event_loc" id="_classified_event_loc" value="%s" class="regular-text" /></td></tr>',
            esc_html__('Local do evento', 'apollo-adverts'),
            esc_attr($ev_loc)
        );
        printf(
            '<tr><th><label for="_classified_quantity">%s</label></th><td><input type="number" min="1" step="1" name="_classified_quantity" id="_classified_quantity" value="%s" class="small-text" /></td></tr>',
            esc_html__('Quantidade de ingressos', 'apollo-adverts'),
            esc_attr((string) ($qty !== '' ? $qty : 1))
        );

        echo '</table></div>';

        // ── Hospedagem ────────────────────────────────────────────────────
        // Rendered for every advert but only relevant to accommodation types
        // — the JS below folds the whole block away when the advert isn't a
        // stay, so a ticket seller never meets fields that mean nothing to
        // them. Values persist either way; nothing is destroyed by toggling.
        $type          = get_post_meta($post->ID, '_classified_type', true);
        $is_accom      = in_array($type, APOLLO_ADVERTS_ACCOMMODATION_TYPES, true);
        $hostel        = get_post_meta($post->ID, '_classified_hostel', true);
        $hostel_url    = get_post_meta($post->ID, '_classified_hostel_url', true);
        $min_nights    = get_post_meta($post->ID, '_classified_min_nights', true);
        $max_days      = get_post_meta($post->ID, '_classified_max_days', true);
        $avail_start   = get_post_meta($post->ID, '_classified_avail_start', true);
        $avail_end     = get_post_meta($post->ID, '_classified_avail_end', true);

        echo '<div id="apollo-accom-block" style="' . ($is_accom ? '' : 'display:none;') . '">';
        echo '<hr><h3 style="margin:14px 0 4px;">' . esc_html__('Hospedagem', 'apollo-adverts') . '</h3>';
        echo '<p class="description" style="margin-bottom:10px;">' . esc_html__('Aplica-se apenas a anúncios de hospedagem (acomodação / aluguel de espaço).', 'apollo-adverts') . '</p>';
        echo '<table class="form-table">';

        /*
         * The hostel switch is ADMIN ONLY, and is now rendered as such
         * (2026-08-17). save_meta_box() enforces manage_options; showing the
         * control to someone whose save will silently ignore it is worse than
         * not showing it, because it reads as a setting that did not stick.
         * A non-admin sees the current state as read-only text instead.
         */
        if (current_user_can('manage_options')) {
            // Hostel switch — the privacy decision for the whole listing.
            printf(
                '<tr><th><label for="_classified_hostel">%s</label></th><td><label><input type="checkbox" name="_classified_hostel" id="_classified_hostel" value="1" %s /> %s</label><p class="description">%s</p></td></tr>',
                esc_html__('Hostel', 'apollo-adverts'),
                checked($hostel, '1', false),
                esc_html__('Este anúncio é de um hostel', 'apollo-adverts'),
                esc_html__('Hostel é um negócio público e já divulgado mundialmente — o anúncio fica aberto (sem cadeado) e o botão de contato leva ao site do hostel em vez do chat. Hospedagens que NÃO são hostel continuam bloqueadas para visitantes.', 'apollo-adverts')
            );

            // Hostel URL — only meaningful with the switch on.
            printf(
                '<tr class="apollo-accom-hostel-only" style="%s"><th><label for="_classified_hostel_url">%s</label></th><td><input type="url" name="_classified_hostel_url" id="_classified_hostel_url" value="%s" class="regular-text" placeholder="https://" /><p class="description">%s</p></td></tr>',
                $hostel === '1' ? '' : 'display:none;',
                esc_html__('URL do hostel', 'apollo-adverts'),
                esc_attr($hostel_url),
                esc_html__('Substitui o botão de chat. Obrigatório quando "Hostel" está marcado.', 'apollo-adverts')
            );
        } elseif ('1' === $hostel) {
            printf(
                '<tr><th>%s</th><td><strong>%s</strong><p class="description">%s</p></td></tr>',
                esc_html__('Hostel', 'apollo-adverts'),
                esc_html__('Sim — anúncio oficial', 'apollo-adverts'),
                esc_html__('Somente a administração pode alterar este status.', 'apollo-adverts')
            );
        }

        // Stay limits — apply to hostel AND non-hostel.
        printf(
            '<tr><th><label for="_classified_min_nights">%s</label></th><td><input type="number" min="0" step="1" name="_classified_min_nights" id="_classified_min_nights" value="%s" class="small-text" /><p class="description">%s</p></td></tr>',
            esc_html__('Mínimo de noites', 'apollo-adverts'),
            esc_attr((string) $min_nights),
            esc_html__('Número mínimo de noites que o espaço pode ser alugado ou compartilhado. 0 = sem mínimo.', 'apollo-adverts')
        );

        printf(
            '<tr><th><label for="_classified_max_days">%s</label></th><td><input type="number" min="0" step="1" name="_classified_max_days" id="_classified_max_days" value="%s" class="small-text" /><p class="description">%s</p></td></tr>',
            esc_html__('Máximo de dias', 'apollo-adverts'),
            esc_attr((string) $max_days),
            esc_html__('Número máximo de dias que o mesmo usuário pode ficar. 0 = sem máximo.', 'apollo-adverts')
        );

        // Availability window — non-hostel only (hostels run year-round).
        printf(
            '<tr class="apollo-accom-nonhostel-only" style="%s"><th><label for="_classified_avail_start">%s</label></th><td><input type="date" name="_classified_avail_start" id="_classified_avail_start" value="%s" /></td></tr>',
            $hostel === '1' ? 'display:none;' : '',
            esc_html__('Disponível a partir de', 'apollo-adverts'),
            esc_attr($avail_start)
        );

        printf(
            '<tr class="apollo-accom-nonhostel-only" style="%s"><th><label for="_classified_avail_end">%s</label></th><td><input type="date" name="_classified_avail_end" id="_classified_avail_end" value="%s" /><p class="description">%s</p></td></tr>',
            $hostel === '1' ? 'display:none;' : '',
            esc_html__('Disponível até', 'apollo-adverts'),
            esc_attr($avail_end),
            esc_html__('Janela de disponibilidade — apenas para hospedagens que não são hostel (hostel está sempre disponível).', 'apollo-adverts')
        );

        echo '</table></div>';
        ?>
        <script>
            (function () {
                /* Hostel switch reveals/hides its dependent rows. */
                var box = document.getElementById('_classified_hostel');
                if (box) {
                    var syncHostel = function () {
                        var on = box.checked;
                        document.querySelectorAll('.apollo-accom-hostel-only').forEach(function (r) {
                            r.style.display = on ? '' : 'none';
                        });
                        document.querySelectorAll('.apollo-accom-nonhostel-only').forEach(function (r) {
                            r.style.display = on ? 'none' : '';
                        });
                    };
                    box.addEventListener('change', syncHostel);
                    syncHostel();
                }

                /* Advert kind reveals the matching field group, so an editor
                   only ever sees the block that applies to what they're
                   editing — and sees it immediately on switching type,
                   without saving first. */
                var typeSel = document.getElementById('_classified_type');
                var ticketBlock = document.getElementById('apollo-ticket-block');
                var accomBlock = document.getElementById('apollo-accom-block');
                if (typeSel) {
                    var syncType = function () {
                        var v = typeSel.value;
                        if (ticketBlock) ticketBlock.style.display = (v === 'ticket') ? '' : 'none';
                        if (accomBlock) accomBlock.style.display = (v === 'accommodation') ? '' : 'none';
                    };
                    typeSel.addEventListener('change', syncType);
                    syncType();
                }
            })();
        </script>
        <?php
    }

    /**
     * Save meta box data
     * Adapted from WPAdverts save_post handler
     */
    public function save_meta_box(int $post_id, \WP_Post $post): void
    {
        if (! isset($_POST['apollo_adverts_meta_nonce']) || ! wp_verify_nonce($_POST['apollo_adverts_meta_nonce'], 'apollo_adverts_save_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        $text_fields = array('_classified_loc', '_classified_contact_phone', '_classified_contact_whatsapp', '_classified_expires_at', '_classified_condition');
        foreach ($text_fields as $key) {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $key, sanitize_text_field(wp_unslash($_POST[$key])));
            }
        }

        // ── Advert kind ───────────────────────────────────────────────────
        // Normalised through the same canonicaliser every other write path
        // uses, so wp-admin can never reintroduce an alias spelling.
        if (isset($_POST['_classified_type'])) {
            update_post_meta(
                $post_id,
                '_classified_type',
                apollo_adverts_canonical_type(sanitize_text_field(wp_unslash($_POST['_classified_type'])))
            );
        }

        // ── Ingresso (revenda) ────────────────────────────────────────────
        // Order matters: the manual event fields are written FIRST, then
        // apollo_adverts_link_event() overwrites them from the linked event.
        // Picking an event is therefore authoritative, while leaving the
        // select on "Nenhum" keeps whatever was typed by hand.
        foreach (array('_classified_event_title', '_classified_event_loc') as $ev_key) {
            if (isset($_POST[$ev_key])) {
                update_post_meta($post_id, $ev_key, sanitize_text_field(wp_unslash($_POST[$ev_key])));
            }
        }
        if (isset($_POST['_classified_event_date'])) {
            $ev_date = sanitize_text_field(wp_unslash($_POST['_classified_event_date']));
            update_post_meta($post_id, '_classified_event_date', preg_match('/^\d{4}-\d{2}-\d{2}$/', $ev_date) ? $ev_date : '');
        }
        if (isset($_POST['_classified_quantity'])) {
            update_post_meta($post_id, '_classified_quantity', max(1, (int) wp_unslash($_POST['_classified_quantity'])));
        }
        if (isset($_POST['_classified_event_id'])) {
            apollo_adverts_link_event($post_id, (int) wp_unslash($_POST['_classified_event_id']));
        }

        // Price
        if (isset($_POST['_classified_price'])) {
            $price = str_replace(array('.', ','), array('', '.'), sanitize_text_field(wp_unslash($_POST['_classified_price'])));
            update_post_meta($post_id, '_classified_price', (float) $price);
        }

        // Currency
        if (isset($_POST['_classified_currency'])) {
            $currency = sanitize_text_field(wp_unslash($_POST['_classified_currency']));
            if (in_array($currency, array('BRL', 'USD', 'EUR'), true)) {
                update_post_meta($post_id, '_classified_currency', $currency);
            }
        }

        // Checkboxes
        update_post_meta($post_id, '_classified_negotiable', isset($_POST['_classified_negotiable']) ? '1' : '');

        /*
         * ── EDITORIAL FLAGS — ADMIN ONLY (enforced here since 2026-08-17) ──
         *
         * _classified_featured, _classified_hostel and _classified_hostel_url
         * are listed in APOLLO_ADVERTS_ADMIN_ONLY_META, whose comment states
         * the pair is "Enforced at the write boundary, not just hidden in the
         * UI." That was TRUE for REST — includes/cpt.php branches the
         * register_meta auth_callback to manage_options — and FALSE here. This
         * method guarded only on nonce + current_user_can('edit_post'), so
         * ANY user who could edit a classified could tick the hostel box, and
         * the hostel switch is what bypasses the marketplace auth gate and
         * publishes a listing to the whole internet. _classified_featured had
         * the same hole: a seller could promote their own advert.
         *
         * The capability check now matches the REST one. Non-admins simply
         * leave these values untouched — no silent overwrite, no error.
         */
        if (current_user_can('manage_options')) {
            update_post_meta($post_id, '_classified_featured', isset($_POST['_classified_featured']) ? '1' : '');

            $is_hostel = isset($_POST['_classified_hostel']) ? '1' : '';
            update_post_meta($post_id, '_classified_hostel', $is_hostel);

            // Hostel URL is only stored when the switch is on — otherwise a stale
            // URL left over from an un-ticked hostel could resurface later and
            // send guests to an unrelated site.
            if ('1' === $is_hostel && isset($_POST['_classified_hostel_url'])) {
                update_post_meta(
                    $post_id,
                    '_classified_hostel_url',
                    esc_url_raw(trim((string) wp_unslash($_POST['_classified_hostel_url'])))
                );
            } elseif ('1' !== $is_hostel) {
                delete_post_meta($post_id, '_classified_hostel_url');
            }

            // The relation that supersedes the boolean (see MetaRegistry).
            if (isset($_POST['_classified_hostel_id'])) {
                update_post_meta($post_id, '_classified_hostel_id', absint($_POST['_classified_hostel_id']));
            }
        }

        // Stay limits — hostel and non-hostel alike. Clamped at 0 (no limit).
        foreach (array('_classified_min_nights', '_classified_max_days') as $num_key) {
            if (isset($_POST[$num_key])) {
                update_post_meta($post_id, $num_key, max(0, (int) wp_unslash($_POST[$num_key])));
            }
        }

        // Availability window — non-hostel only, and only accepted in strict
        // Y-m-d form (the same guard apollo_adverts_sanitize_meta_date uses).
        foreach (array('_classified_avail_start', '_classified_avail_end') as $date_key) {
            if ('1' === $is_hostel) {
                delete_post_meta($post_id, $date_key);
                continue;
            }
            if (! isset($_POST[$date_key])) {
                continue;
            }
            $raw = sanitize_text_field(wp_unslash($_POST[$date_key]));
            update_post_meta($post_id, $date_key, preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) ? $raw : '');
        }
    }

    /**
     * Track views on single classified
     */
    public function track_views(): void
    {
        if (is_singular(APOLLO_CPT_CLASSIFIED) && ! is_admin()) {
            $post_id = get_queried_object_id();
            if ($post_id && get_post_type($post_id) === APOLLO_CPT_CLASSIFIED) {
                apollo_adverts_increment_views($post_id);
            }
        }
    }

    /**
     * Render settings page
     */
    public function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        include APOLLO_ADVERTS_DIR . 'templates/admin/settings.php';
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        include APOLLO_ADVERTS_DIR . 'templates/admin/dashboard.php';
    }
}