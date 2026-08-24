<?php

/**
 * Main Plugin Class (Singleton)
 *
 * Orchestrates taxonomies, NREP auto-coding, admin columns,
 * template loading and frontend assets.
 *
 * @package Apollo\Journal
 */

declare(strict_types=1);

namespace Apollo\Journal;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin class.
 */
final class Plugin
{


    /** @var Plugin|null */
    private static ?Plugin $instance = null;

    /** @var bool Prevents duplicate hook registration. */
    private bool $initialized = false;

    /**
     * Singleton accessor.
     *
     * @return Plugin
     */
    public static function get_instance(): Plugin
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Private constructor — use get_instance(). */
    private function __construct() {}

    /**
     * Wire all hooks.
     *
     * @return void
     */
    public function init(): void
    {
        // Idempotency guard — prevents duplicate hooks if called more than once.
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        // CPTs — must fire before taxonomies attach to them.
        add_action('init', array($this, 'register_cpts'), 4);

        // Taxonomies.
        add_action('init', array($this, 'register_taxonomies'), 5);

        // Rewrite rules for /jornal page.
        add_action('init', array($this, 'register_rewrites'), 6);
        add_filter('query_vars', array($this, 'register_query_vars'));

        // Assets.
        add_action('init', array($this, 'register_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend'));

        // NREP auto-coding.
        $nrep = new NREP();
        $nrep->init();

        // Shortcodes.
        $shortcodes = new Shortcodes();
        $shortcodes->init();

        // REST API.
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Admin columns & meta box.
        if (is_admin()) {
            $admin = new Admin();
            $admin->init();
        }

        // Template overrides.
        add_filter('template_include', array($this, 'maybe_override_template'), 20);

        /**
         * Fires after Apollo Journal is fully initialised.
         *
         * @since 1.0.0
         */
        do_action('apollo/journal/initialized');
    }

	// ─────────────────────────────────────────────────────────────────────
	// CUSTOM POST TYPES
	// ─────────────────────────────────────────────────────────────────────

    /**
     * Register journal_news and journal_nota CPTs.
     *
     * @return void
     */
    public function register_cpts(): void
    {
        // ── journal_news ──
        if (! post_type_exists('journal_news')) {
            register_post_type('journal_news', array(
                'labels' => array(
                    'name'               => 'Notícias',
                    'singular_name'      => 'Notícia',
                    'add_new'            => 'Nova Notícia',
                    'add_new_item'       => 'Adicionar Notícia',
                    'edit_item'          => 'Editar Notícia',
                    'view_item'          => 'Ver Notícia',
                    'search_items'       => 'Pesquisar Notícias',
                    'not_found'          => 'Nenhuma notícia encontrada.',
                    'not_found_in_trash' => 'Nenhuma notícia no lixo.',
                    'all_items'          => 'Todas as Notícias',
                    'menu_name'          => 'Journal News',
                ),
                'public'             => true,
                'has_archive'        => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'show_in_rest'       => true,
                'menu_icon'          => 'dashicons-media-text',
                'menu_position'      => 6,
                'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions'),
                'rewrite'            => array('slug' => 'artigo', 'with_front' => false),
                'capability_type'    => 'post',
                'taxonomies'         => array('category', 'post_tag', 'music', 'culture', 'rio', 'formato'),
            ));
        }

        // ── journal_news meta — always register (idempotent, safe to repeat) ──
        $news_meta = array(
            '_nrep_code'        => 'string',
            '_nrep_year'        => 'string',
            '_nrep_seq'         => 'integer',
            '_apollo_headline'  => 'string',
            '_apollo_subtitle'  => 'string',
            '_apollo_featured'  => 'boolean',
        );
        foreach ($news_meta as $key => $type) {
            register_post_meta('journal_news', $key, array(
                'type'              => $type,
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'boolean' === $type ? 'rest_sanitize_boolean' : 'sanitize_text_field',
                'auth_callback'     => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ));
        }

        // ── journal_nota ──
        if (! post_type_exists('journal_nota')) {
            register_post_type('journal_nota', array(
                'labels' => array(
                    'name'               => 'Notas de Repúdio',
                    'singular_name'      => 'Nota de Repúdio',
                    'add_new'            => 'Nova Nota',
                    'add_new_item'       => 'Adicionar Nota',
                    'edit_item'          => 'Editar Nota',
                    'view_item'          => 'Ver Nota',
                    'search_items'       => 'Pesquisar Notas',
                    'not_found'          => 'Nenhuma nota encontrada.',
                    'not_found_in_trash' => 'Nenhuma nota no lixo.',
                    'all_items'          => 'Todas as Notas',
                    'menu_name'          => 'Journal Notas',
                ),
                'public'             => true,
                'has_archive'        => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'show_in_rest'       => true,
                'menu_icon'          => 'dashicons-megaphone',
                'menu_position'      => 7,
                'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions'),
                'rewrite'            => array('slug' => 'nota', 'with_front' => false),
                'capability_type'    => 'post',
                'taxonomies'         => array('category'),
            ));
        }

        // ── journal_nota meta — always register (idempotent) ──
        $nota_meta = array(
            '_nrep_code'        => 'string',
            '_nrep_year'        => 'string',
            '_nrep_seq'         => 'integer',
            '_apollo_note_type' => 'string',
            '_apollo_source'    => 'string',
        );
        foreach ($nota_meta as $key => $type) {
            register_post_meta('journal_nota', $key, array(
                'type'              => $type,
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback'     => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ));
        }
    }

	// ─────────────────────────────────────────────────────────────────────
	// REWRITE RULES
	// ─────────────────────────────────────────────────────────────────────

    /**
     * Register rewrite rule for the /jornal page.
     *
     * @return void
     */
    public function register_rewrites(): void
    {
        add_rewrite_rule(
            '^jornal/?$',
            'index.php?apollo_journal_page=1',
            'top'
        );
    }

    /**
     * Register custom query vars.
     *
     * @param array $vars Existing query vars.
     * @return array
     */
    public function register_query_vars( array $vars ): array
    {
        $vars[] = 'apollo_journal_page';
        return $vars;
    }

	// ─────────────────────────────────────────────────────────────────────
	// TAXONOMIES
	// ─────────────────────────────────────────────────────────────────────

    /**
     * Register custom hierarchical taxonomies for editorial content.
     *
     * @return void
     */
    public function register_taxonomies(): void
    {
        $taxonomies = array(
            'music'   => array(
                'name'     => 'Música',
                'singular' => 'Gênero Musical',
                'slug'     => 'music',
            ),
            'culture' => array(
                'name'     => 'Cultura',
                'singular' => 'Cultura',
                'slug'     => 'culture',
            ),
            'rio'     => array(
                'name'     => 'Rio',
                'singular' => 'Região',
                'slug'     => 'rio',
            ),
            'formato' => array(
                'name'     => 'Formato',
                'singular' => 'Formato',
                'slug'     => 'formato',
            ),
        );

        /**
         * Filter the list of taxonomies registered by Apollo Journal.
         *
         * @since 1.0.0
         * @param array $taxonomies Slug => config array.
         */
        $taxonomies = apply_filters('apollo/journal/taxonomies', $taxonomies);

        foreach ($taxonomies as $slug => $cfg) {
            if (taxonomy_exists($slug)) {
                // Ensure journal_news is attached even when taxonomy was registered elsewhere.
                register_taxonomy_for_object_type($slug, 'journal_news');
                continue;
            }

            $labels = array(
                'name'              => $cfg['name'],
                'singular_name'     => $cfg['singular'],
                'search_items'      => 'Pesquisar ' . $cfg['name'],
                'all_items'         => 'Todos',
                'parent_item'       => $cfg['singular'] . ' pai',
                'parent_item_colon' => $cfg['singular'] . ' pai:',
                'edit_item'         => 'Editar ' . $cfg['singular'],
                'update_item'       => 'Atualizar ' . $cfg['singular'],
                'add_new_item'      => 'Adicionar ' . $cfg['singular'],
                'new_item_name'     => 'Novo ' . $cfg['singular'],
                'menu_name'         => $cfg['name'],
            );

            register_taxonomy(
                $slug,
                array('post', 'journal_news'),
                array(
                    'hierarchical'      => true,
                    'labels'            => $labels,
                    'show_ui'           => true,
                    'show_in_rest'      => true,
                    'show_admin_column' => true,
                    'rewrite'           => array(
                        'slug'         => $cfg['slug'],
                        'with_front'   => false,
                        'hierarchical' => true,
                    ),
                    'public'            => true,
                    'show_in_nav_menus' => true,
                    'show_tagcloud'     => false,
                )
            );
        }
    }

	// ─────────────────────────────────────────────────────────────────────
	// ASSETS
	// ─────────────────────────────────────────────────────────────────────

    /**
     * Register CSS / JS handles.
     *
     * @return void
     */
    public function register_assets(): void
    {
        wp_register_style(
            'apollo-journal',
            APOLLO_JOURNAL_URL . 'assets/css/journal.css',
            array(),
            APOLLO_JOURNAL_VERSION
        );

        wp_register_script(
            'apollo-journal',
            APOLLO_JOURNAL_URL . 'assets/js/journal.js',
            array('jquery'),
            APOLLO_JOURNAL_VERSION,
            true
        );
    }

    /**
     * Enqueue on the frontend when needed.
     *
     * @return void
     */
    public function enqueue_frontend(): void
    {
        $is_journal = is_singular('post')
            || is_singular('journal_news')
            || is_singular('journal_nota')
            || is_category()
            || is_tax(array('music', 'culture', 'rio', 'formato'))
            || is_home()
            || is_front_page()
            || get_query_var('apollo_journal_page');

        if ($is_journal) {
            wp_enqueue_style('apollo-journal');
            wp_enqueue_script('apollo-journal');

            wp_localize_script(
                'apollo-journal',
                'apolloJournalConfig',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'restUrl' => rest_url(APOLLO_JOURNAL_REST_NAMESPACE . '/'),
                    'nonce'   => wp_create_nonce('wp_rest'),
                )
            );
        }
    }

	// ─────────────────────────────────────────────────────────────────────
	// TEMPLATE OVERRIDE
	// ─────────────────────────────────────────────────────────────────────

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_rest_routes(): void
    {
        $controller = new API\PostsController();
        $controller->register_routes();
    }

    /**
     * Optionally override category / taxonomy archive templates.
     *
     * @param string $template Current template path.
     * @return string
     */
    public function maybe_override_template( string $template ): string
    {
        $override = '';

        // /jornal page route.
        if (get_query_var('apollo_journal_page')) {
            $override = APOLLO_JOURNAL_DIR . 'templates/page-jornal.php';
        } elseif (is_category()) {
            $override = APOLLO_JOURNAL_DIR . 'templates/archive-journal.php';
        } elseif (is_tax(array('music', 'culture', 'rio', 'formato'))) {
            $override = APOLLO_JOURNAL_DIR . 'templates/archive-journal.php';
        } elseif (is_singular('journal_news')) {
            $override = APOLLO_JOURNAL_DIR . 'templates/single-journal_news.php';
        } elseif (is_singular('journal_nota')) {
            $override = APOLLO_JOURNAL_DIR . 'templates/single-journal_nota.php';
        }

        if ($override && file_exists($override)) {
            return $override;
        }

        return $template;
    }
}
