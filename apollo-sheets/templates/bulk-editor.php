<?php

/**
 * Bulk Editor Spreadsheet Template
 *
 * Renders a full-width spreadsheet-style table for bulk editing.
 * Data loaded via AJAX, edited inline, saved via AJAX batch.
 *
 * @package Apollo\Sheets
 * @var string $content_slug   Content type slug (post_type name, 'users', or 'comments')
 * @var string $entity_type    Entity type: post_type|users|comments
 * @var string $content_label  Human-readable label
 */

if (! defined('ABSPATH')) {
    exit;
}

/*
 * Load Apollo CDN for icons and base styles.
 *
 * This was a PHP TEMPLATE nested inside a single-quoted PHP echo:
 *   echo '<script src="<?php echo esc_url( function_exists('apollo_cdn…
 * The inner quote opening 'apollo_cdn_core_js_url' terminated the outer
 * string, so the file was a parse error and every request for the bulk
 * editor screen fataled. Resolve the URL first, then echo once — the same
 * shape apollo-admin/templates/frontend/*.php already uses.
 */
$apollo_cdn_src = function_exists('apollo_cdn_core_js_url')
    ? apollo_cdn_core_js_url()
    : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb';

printf(
    '<script src="%s" fetchpriority="high" crossorigin="anonymous"></script>',
    esc_url($apollo_cdn_src)
);

$nonce = wp_create_nonce('apollo_bulk_nonce');
?>
<div class="wrap apollo-bulk-editor-wrap" id="apollo-bulk-wrap"
    data-content-type="<?php echo esc_attr($content_slug); ?>"
    data-entity-type="<?php echo esc_attr($entity_type); ?>"
    data-nonce="<?php echo esc_attr($nonce); ?>">

    <!-- ═══ HEADER ═══ -->
    <div class="apollo-bulk-header">
        <div class="apollo-bulk-header-left">
            <a href="<?php echo esc_url(admin_url('admin.php?page=apollo-bulk')); ?>" class="apollo-bulk-back" title="<?php esc_attr_e('Voltar', 'apollo-sheets'); ?>">
                <i class="ri-arrow-left-s-line"></i>
            </a>
            <h1 class="wp-heading-inline">
                <?php echo esc_html($content_label); ?>
                <span class="apollo-bulk-badge" id="bulk-total-badge">0</span>
            </h1>
        </div>

        <div class="apollo-bulk-header-right">
            <div class="apollo-search-composite">
                <div class="apollo-composite-select-wrap">
                    <select class="apollo-composite-select" id="bulk-filter-select">
                        <option value=""><?php esc_html_e('All', 'apollo-sheets'); ?></option>
                        <?php if ($entity_type === 'users') : ?>
                            <option value="administrator"><?php esc_html_e('Admin', 'apollo-sheets'); ?></option>
                            <option value="editor"><?php esc_html_e('Editor', 'apollo-sheets'); ?></option>
                            <option value="author"><?php esc_html_e('Author', 'apollo-sheets'); ?></option>
                            <option value="contributor"><?php esc_html_e('Contributor', 'apollo-sheets'); ?></option>
                            <option value="subscriber"><?php esc_html_e('Subscriber', 'apollo-sheets'); ?></option>
                        <?php elseif ($entity_type === 'post_type') : ?>
                            <option value="publish"><?php esc_html_e('Published', 'apollo-sheets'); ?></option>
                            <option value="draft"><?php esc_html_e('Draft', 'apollo-sheets'); ?></option>
                            <option value="pending"><?php esc_html_e('Pending', 'apollo-sheets'); ?></option>
                            <option value="private"><?php esc_html_e('Private', 'apollo-sheets'); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <input type="text" placeholder="<?php esc_attr_e('Search...', 'apollo-sheets'); ?>" class="apollo-input" class="apollo-composite-input" id="bulk-search">
                <button class="apollo-composite-btn" id="bulk-search-btn">
                    <i class="ri-search-line"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══ TOOLBAR ═══ -->
    <div class="apollo-bulk-toolbar">
        <div class="apollo-bulk-toolbar-left">
            <button type="button" id="bulk-btn-save" class="button button-primary" title="<?php esc_attr_e('Salvar alterações', 'apollo-sheets'); ?>" aria-label="<?php esc_attr_e('Salvar alterações', 'apollo-sheets'); ?>" disabled>
                <i class="ri-save-line"></i> <?php esc_html_e('Salvar', 'apollo-sheets'); ?>
            </button>
            <button type="button" id="bulk-btn-export" class="button" title="<?php esc_attr_e('Exportar CSV', 'apollo-sheets'); ?>" aria-label="<?php esc_attr_e('Exportar CSV', 'apollo-sheets'); ?>">
                <i class="ri-download-line"></i> <?php esc_html_e('Exportar', 'apollo-sheets'); ?>
            </button>
        </div>

        <div class="apollo-bulk-toolbar-right">
            <div class="apollo-bulk-pagination">
                <button type="button" id="bulk-page-prev" class="button" title="<?php esc_attr_e('Página anterior', 'apollo-sheets'); ?>" aria-label="<?php esc_attr_e('Página anterior', 'apollo-sheets'); ?>" disabled>
                    <i class="ri-arrow-left-s-line"></i>
                </button>
                <span class="apollo-bulk-page-info">
                    <?php esc_html_e('Page', 'apollo-sheets'); ?>
                    <input type="number" id="bulk-page-current" value="1" min="1" class="apollo-input apollo-bulk-page-input">
                    <?php esc_html_e('of', 'apollo-sheets'); ?> <span id="bulk-page-total">1</span>
                </span>
                <button type="button" id="bulk-page-next" class="button" title="<?php esc_attr_e('Próxima página', 'apollo-sheets'); ?>" aria-label="<?php esc_attr_e('Próxima página', 'apollo-sheets'); ?>" disabled>
                    <i class="ri-arrow-right-s-line"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══ STATUS CONSOLE ═══ -->
    <div id="bulk-console" class="apollo-bulk-console">
        <?php esc_html_e('Carregando dados…', 'apollo-sheets'); ?>
    </div>

    <!-- ═══ SPREADSHEET CONTAINER ═══ -->
    <div class="apollo-sheets">
        <table role="grid" id="apollo-bulk-table">
            <thead>
                <tr>
                    <th></th>
                    <?php
                    foreach ($columns as $col_key => $col) {
                        echo '<th>' . esc_html($col['title']) . '</th>';
                    }
                    ?>
                </tr>
            </thead>
            <tbody id="apollo-bulk-tbody">
                <!-- Data rows will be loaded via AJAX -->
            </tbody>
        </table>
    </div>

    <!-- ═══ SAVE MODAL ═══ -->
    <div id="bulk-save-modal" class="apollo-bulk-modal" style="display:none;">
        <div class="apollo-bulk-modal-content">
            <h3><?php esc_html_e('Salvando Alterações', 'apollo-sheets'); ?></h3>
            <p><?php esc_html_e('As alterações estão sendo salvas. Não feche esta janela.', 'apollo-sheets'); ?></p>
            <div class="apollo-bulk-progress">
                <div class="apollo-bulk-progress-bar" id="bulk-progress-bar"></div>
            </div>
            <div id="bulk-save-response" class="apollo-bulk-save-response"></div>
        </div>
    </div>
</div>