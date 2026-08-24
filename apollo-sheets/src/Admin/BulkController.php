<?php

/**
 * Admin Bulk Controller — registers admin menu pages & enqueues assets for bulk editor
 *
 * Creates a top-level "Bulk Editor" menu with submenus for each content type.
 * Restricted to manage_options capability (admin + mod only).
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets\Admin;

use Apollo\Sheets\Bulk\Manager;

if (! defined('ABSPATH')) {
    exit;
}

class BulkController
{


    private Manager $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Init hooks
     */
    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_menus'), 20);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Register admin menus — one submenu per content type under Sheets menu
     */
    public function register_menus(): void
    {
        $capability = Manager::REQUIRED_CAP;
        $types      = $this->manager->get_content_types();

        // Add separator
        add_submenu_page(
            'apollo-sheets',
            '',
            '── Bulk Editor ──',
            $capability,
            'apollo-sheets-bulk-separator',
            '__return_false'
        );

        // Overview page (lists all available content types)
        add_submenu_page(
            'apollo-sheets',
            __('Bulk Editor', 'apollo-sheets'),
            __('Bulk Editor', 'apollo-sheets'),
            $capability,
            'apollo-bulk',
            array($this, 'page_overview')
        );

        // One submenu per content type
        foreach ($types as $content) {
            $slug  = 'apollo-bulk-' . $content['slug'];
            $label = '↳ ' . $content['label'];

            add_submenu_page(
                'apollo-sheets',
                sprintf(__('Bulk: %s', 'apollo-sheets'), $content['label']),
                $label,
                $capability,
                $slug,
                array($this, 'page_editor')
            );
        }
    }

    /**
     * Enqueue bulk editor assets on bulk pages only
     *
     * @param string $hook_suffix
     */
    public function enqueue_assets(string $hook_suffix): void
    {
        // Only load on our bulk pages
        if (! isset($_GET['page']) || strpos($_GET['page'], 'apollo-bulk') !== 0) {
            return;
        }

        // Bulk editor CSS
        wp_enqueue_style(
            'apollo-bulk-editor',
            APOLLO_SHEETS_URL . 'assets/css/bulk-editor.css',
            array(),
            APOLLO_SHEETS_VERSION
        );

        // Bulk editor JS
        wp_enqueue_script(
            'apollo-bulk-editor',
            APOLLO_SHEETS_URL . 'assets/js/bulk-editor.js',
            array('jquery', 'wp-util'),
            APOLLO_SHEETS_VERSION,
            true
        );

        // Localize — pass nonce, AJAX URL, and initial config
        $content_slug = $this->get_current_content_slug();
        $entity_type  = $this->get_entity_type($content_slug);

        wp_localize_script(
            'apollo-bulk-editor',
            'ApolloBulk',
            array(
                'ajaxUrl'     => admin_url('admin-ajax.php'),
                'nonce'       => wp_create_nonce('apollo_bulk_nonce'),
                'contentType' => $content_slug,
                'entityType'  => $entity_type,
                'perPage'     => 50,
                'i18n'        => array(
                    'saving'        => __('Salvando…', 'apollo-sheets'),
                    'saved'         => __('Salvo com sucesso!', 'apollo-sheets'),
                    'error'         => __('Erro ao salvar.', 'apollo-sheets'),
                    'loading'       => __('Carregando…', 'apollo-sheets'),
                    'noData'        => __('Nenhum registro encontrado.', 'apollo-sheets'),
                    'confirmDelete' => __('Tem certeza que deseja excluir os registros selecionados?', 'apollo-sheets'),
                    'deleted'       => __('Excluído com sucesso.', 'apollo-sheets'),
                    'inserted'      => __('Registros inseridos.', 'apollo-sheets'),
                    'loadMore'      => __('Carregar Mais', 'apollo-sheets'),
                    'page'          => __('Página', 'apollo-sheets'),
                    'of'            => __('de', 'apollo-sheets'),
                    'total'         => __('Total', 'apollo-sheets'),
                    'search'        => __('Buscar…', 'apollo-sheets'),
                    'addRows'       => __('Adicionar Linhas', 'apollo-sheets'),
                    'save'          => __('Salvar Alterações', 'apollo-sheets'),
                    'delete'        => __('Excluir Selecionados', 'apollo-sheets'),
                    'export'        => __('Exportar CSV', 'apollo-sheets'),
                ),
            )
        );
    }

    /**
     * Overview page — lists all content types with links
     */
    public function page_overview(): void
    {
        if (! current_user_can(Manager::REQUIRED_CAP)) {
            wp_die(__('Acesso negado.', 'apollo-sheets'));
        }

        $types = $this->manager->get_content_types();

        include APOLLO_SHEETS_DIR . 'templates/bulk-overview.php';
    }

    /**
     * Editor page — renders a Handsontable spreadsheet for a single content type
     */
    public function page_editor(): void
    {
        if (! current_user_can(Manager::REQUIRED_CAP)) {
            wp_die(__('Acesso negado.', 'apollo-sheets'));
        }

        $content_slug  = $this->get_current_content_slug();
        $entity_type   = $this->get_entity_type($content_slug);
        $content_label = $this->get_content_label($content_slug);
        $columns       = $this->manager->get_column_registry()->get_columns($content_slug);

        include APOLLO_SHEETS_DIR . 'templates/bulk-editor.php';
    }

    /**
     * Extract content slug from page query parameter
     *
     * @return string
     */
    private function get_current_content_slug(): string
    {
        $page = sanitize_key($_GET['page'] ?? '');
        return str_replace('apollo-bulk-', '', $page);
    }

    /**
     * Determine entity type from content slug
     *
     * @param string $slug
     * @return string  post_type|users|comments
     */
    private function get_entity_type(string $slug): string
    {
        if ($slug === 'users') {
            return 'users';
        }
        if ($slug === 'comments') {
            return 'comments';
        }
        return 'post_type';
    }

    /**
     * Get human-readable label for a content slug
     *
     * @param string $slug
     * @return string
     */
    private function get_content_label(string $slug): string
    {
        if ($slug === 'users') {
            return __('Usuários', 'apollo-sheets');
        }
        if ($slug === 'comments') {
            return __('Comentários', 'apollo-sheets');
        }

        $pt = get_post_type_object($slug);
        return $pt ? $pt->labels->name : ucfirst($slug);
    }
}
