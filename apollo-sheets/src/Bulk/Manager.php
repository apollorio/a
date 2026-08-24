<?php

/**
 * Bulk Manager — central orchestrator for the bulk spreadsheet editor
 *
 * Auto-detects all registered CPTs, users, and comments.
 * Manages providers and column registry.
 * Dispatches AJAX load/save operations.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets\Bulk;

if (! defined('ABSPATH')) {
    exit;
}

class Manager
{



    private static ?Manager $instance = null;

    private ColumnRegistry $columns;
    private PostsProvider $posts_provider;
    private UsersProvider $users_provider;
    private CommentsProvider $comments_provider;

    /**
     * Minimum capability required (admin + mod only)
     */
    public const REQUIRED_CAP = 'manage_options';

    /**
     * Content types excluded from bulk editing
     */
    private const EXCLUDED_POST_TYPES = array(
        'attachment',
        'revision',
        'nav_menu_item',
        'custom_css',
        'customize_changeset',
        'oembed_cache',
        'user_request',
        'wp_block',
        'wp_template',
        'wp_template_part',
        'wp_global_styles',
        'wp_navigation',
        'wp_font_family',
        'wp_font_face',
        'apollo_sheet', // Our own CPT — edited via the sheets admin
    );

    public static function get_instance(): Manager
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->columns           = new ColumnRegistry();
        $this->posts_provider    = new PostsProvider($this->columns);
        $this->users_provider    = new UsersProvider($this->columns);
        $this->comments_provider = new CommentsProvider($this->columns);
    }

    /**
     * Get column registry instance
     */
    public function get_column_registry(): ColumnRegistry
    {
        return $this->columns;
    }

    /**
     * Get posts provider
     */
    public function get_posts_provider(): PostsProvider
    {
        return $this->posts_provider;
    }

    /**
     * Get users provider
     */
    public function get_users_provider(): UsersProvider
    {
        return $this->users_provider;
    }

    /**
     * Get comments provider
     */
    public function get_comments_provider(): CommentsProvider
    {
        return $this->comments_provider;
    }

    /**
     * Initialize the bulk system — register AJAX handlers, enqueue assets
     */
    public function init(): void
    {
        // AJAX handlers
        add_action('wp_ajax_apollo_bulk_load', array($this, 'ajax_load'));
        add_action('wp_ajax_apollo_bulk_save', array($this, 'ajax_save'));
        add_action('wp_ajax_apollo_bulk_insert', array($this, 'ajax_insert'));
        add_action('wp_ajax_apollo_bulk_delete', array($this, 'ajax_delete'));

        // Register columns after all CPTs are registered (init:99)
        add_action('init', array($this, 'register_all_columns'), 99);
    }

    /**
     * Register columns for every available content type
     */
    public function register_all_columns(): void
    {
        // Post types
        foreach ($this->get_allowed_post_types() as $post_type) {
            $this->columns->register_post_type_columns($post_type);
        }

        // Users
        $this->columns->register_user_columns();

        // Comments
        $this->columns->register_comment_columns();
    }

    /**
     * Get all post types that should have bulk editors
     *
     * @return string[]
     */
    public function get_allowed_post_types(): array
    {
        $all_types = get_post_types(array('show_ui' => true), 'names');

        // Also include public post types even without show_ui
        $public_types = get_post_types(array('public' => true), 'names');
        $all_types    = array_unique(array_merge($all_types, $public_types));

        // Always include core + Apollo CPTs
        $force_include = array('post', 'page', 'event', 'dj', 'loc', 'classified', 'supplier', 'doc', 'email_aprio', 'hub');
        $all_types     = array_unique(array_merge($all_types, $force_include));

        // Remove excluded
        $all_types = array_diff($all_types, self::EXCLUDED_POST_TYPES);

        /**
         * Filter allowed post types for bulk editing
         *
         * @param string[] $post_types
         */
        $all_types = apply_filters('apollo/sheets/bulk/allowed_post_types', $all_types);

        return array_values($all_types);
    }

    /**
     * Get all available content types for menu generation
     *
     * Returns structured array: [ { slug, label, icon, type (post_type|users|comments) } ]
     *
     * @return array
     */
    public function get_content_types(): array
    {
        $types = array();

        // Post types
        foreach ($this->get_allowed_post_types() as $pt_slug) {
            $pt_obj = get_post_type_object($pt_slug);
            $label  = $pt_obj ? $pt_obj->labels->name : ucfirst(str_replace('_', ' ', $pt_slug));
            $icon   = $pt_obj ? ($pt_obj->menu_icon ?: 'dashicons-admin-post') : 'dashicons-admin-post';

            $types[] = array(
                'slug'  => $pt_slug,
                'label' => $label,
                'icon'  => $icon,
                'type'  => 'post_type',
            );
        }

        // Users
        $types[] = array(
            'slug'  => 'users',
            'label' => __('Usuários', 'apollo-sheets'),
            'icon'  => 'dashicons-admin-users',
            'type'  => 'users',
        );

        // Comments
        $types[] = array(
            'slug'  => 'comments',
            'label' => __('Comentários', 'apollo-sheets'),
            'icon'  => 'dashicons-admin-comments',
            'type'  => 'comments',
        );

        return $types;
    }

	// ═══════════════════════════════════════════════════════════════════════
	// AJAX HANDLERS
	// ═══════════════════════════════════════════════════════════════════════

    /**
     * AJAX: Load rows
     */
    public function ajax_load(): void
    {
        $this->verify_ajax_request();

        $content_type = sanitize_key($_POST['content_type'] ?? '');
        $entity_type  = sanitize_key($_POST['entity_type'] ?? 'post_type');

        $args = array(
            'per_page' => absint($_POST['per_page'] ?? 50),
            'page'     => absint($_POST['page'] ?? 1),
            'search'   => sanitize_text_field($_POST['search'] ?? ''),
            'filter'   => sanitize_text_field($_POST['filter'] ?? ''),
        );

        try {
            if ($entity_type === 'users') {
                $args['role'] = $args['filter'] ?: '';
                $result       = $this->users_provider->load_rows($args);
            } elseif ($entity_type === 'comments') {
                $args['status'] = $args['filter'] ?: 'all';
                $result         = $this->comments_provider->load_rows($args);
            } else {
                // Post type
                if (empty($content_type)) {
                    wp_send_json_error(
                        array(
                            'message' => __('Tipo de conteúdo inválido.', 'apollo-sheets'),
                            'details' => 'content_type está vazio',
                        )
                    );
                }

                $args['status'] = $args['filter'] ?: 'any';
                $result         = $this->posts_provider->load_rows($content_type, $args);
            }

            // Get column config for plain HTML table
            $columns = array();
            foreach ($this->columns->get_columns($content_type) as $key => $config) {
                $columns[] = array(
                    'key'      => $key,
                    'title'    => $config['title'],
                    'type'     => $config['type'],
                    'editable' => $config['editable'],
                );
            }

            wp_send_json_success(
                array(
                    'rows'   => $result['rows'],
                    'total'  => $result['total'],
                    'pages'  => $result['pages'],
                    'columns' => $columns,
                )
            );
        } catch (\Exception $e) {
            wp_send_json_error(
                array(
                    'message' => __('Erro ao carregar dados.', 'apollo-sheets'),
                    'details' => $e->getMessage(),
                )
            );
        }
    }

    /**
     * AJAX: Save rows
     */
    public function ajax_save(): void
    {
        $this->verify_ajax_request();

        $content_type = sanitize_key($_POST['content_type'] ?? '');
        $entity_type  = sanitize_key($_POST['entity_type'] ?? 'post_type');

        // Decode JSON changes from POST
        $raw_changes = $_POST['changes'] ?? '[]';
        if (is_string($raw_changes)) {
            $changes = json_decode(wp_unslash($raw_changes), true);
        } else {
            $changes = $raw_changes;
        }

        if (! is_array($changes) || empty($changes)) {
            wp_send_json_error(array('message' => __('Nenhuma alteração para salvar.', 'apollo-sheets')));
        }

        if ($entity_type === 'users') {
            $result = $this->users_provider->save_changes($changes);
        } elseif ($entity_type === 'comments') {
            $result = $this->comments_provider->save_changes($changes);
        } else {
            $result = $this->posts_provider->save_changes($content_type, $changes);
        }

        if (! empty($result['errors'])) {
            wp_send_json_success(
                array(
                    'message' => sprintf(__('%1$d registros salvos com %2$d erros.', 'apollo-sheets'), $result['saved'], count($result['errors'])),
                    'saved'   => $result['saved'],
                    'errors'  => $result['errors'],
                )
            );
        } else {
            wp_send_json_success(
                array(
                    'message' => sprintf(__('%d registros salvos com sucesso.', 'apollo-sheets'), $result['saved']),
                    'saved'   => $result['saved'],
                    'errors'  => array(),
                )
            );
        }
    }

    /**
     * AJAX: Insert new rows
     */
    public function ajax_insert(): void
    {
        $this->verify_ajax_request();

        $content_type = sanitize_key($_POST['content_type'] ?? '');
        $entity_type  = sanitize_key($_POST['entity_type'] ?? 'post_type');
        $count        = absint($_POST['count'] ?? 1);

        if ($entity_type !== 'post_type') {
            wp_send_json_error(array('message' => __('Inserção em massa disponível apenas para posts.', 'apollo-sheets')));
        }

        if (! current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permissão insuficiente.', 'apollo-sheets')));
        }

        $ids = $this->posts_provider->insert_rows($content_type, $count);

        if (empty($ids)) {
            wp_send_json_error(array('message' => __('Falha ao inserir posts.', 'apollo-sheets')));
        }

        // Reload the newly created rows
        $result = $this->posts_provider->load_rows(
            $content_type,
            array(
                'per_page' => count($ids),
                'page'     => 1,
                'search'   => '',
                'status'   => 'any',
                'orderby'  => 'ID',
                'order'    => 'DESC',
            )
        );

        // Filter only the new IDs
        $new_rows = array_filter(
            $result['rows'],
            function ($row) use ($ids) {
                return in_array((int) $row['ID'], $ids, true);
            }
        );

        wp_send_json_success(
            array(
                'message' => sprintf(__('%d posts criados.', 'apollo-sheets'), count($ids)),
                'rows'    => array_values($new_rows),
                'ids'     => $ids,
            )
        );
    }

    /**
     * AJAX: Delete rows
     */
    public function ajax_delete(): void
    {
        $this->verify_ajax_request();

        $entity_type = sanitize_key($_POST['entity_type'] ?? 'post_type');

        $raw_ids = $_POST['ids'] ?? '[]';
        if (is_string($raw_ids)) {
            $ids = json_decode(wp_unslash($raw_ids), true);
        } else {
            $ids = $raw_ids;
        }

        if (! is_array($ids) || empty($ids)) {
            wp_send_json_error(array('message' => __('Nenhum ID fornecido.', 'apollo-sheets')));
        }

        $ids = array_map('absint', $ids);

        if ($entity_type === 'post_type') {
            $deleted = $this->posts_provider->delete_rows($ids);
        } else {
            wp_send_json_error(array('message' => __('Exclusão em massa disponível apenas para posts.', 'apollo-sheets')));
            return;
        }

        wp_send_json_success(
            array(
                'message' => sprintf(__('%d registros excluídos.', 'apollo-sheets'), $deleted),
                'deleted' => $deleted,
            )
        );
    }

	// ═══════════════════════════════════════════════════════════════════════
	// HELPERS
	// ═══════════════════════════════════════════════════════════════════════

    /**
     * Verify AJAX request — nonce + capability
     */
    private function verify_ajax_request(): void
    {
        if (! check_ajax_referer('apollo_bulk_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Nonce inválido.', 'apollo-sheets')), 403);
        }

        if (! current_user_can(self::REQUIRED_CAP)) {
            wp_send_json_error(array('message' => __('Permissão insuficiente. Apenas administradores e moderadores.', 'apollo-sheets')), 403);
        }
    }
}
