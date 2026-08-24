/**
 * Apollo Navbar Apps — Admin Settings JS
 *
 * Handles repeater UI: add, remove, reorder, image upload, live preview.
 *
 * @package Apollo\Templates
 * @since 1.1.0
 */
(function ($) {
    'use strict';

    const config = window.apolloNavbarAdmin || {};
    let appIndex = 0;

    /**
     * Initialize
     */
    function init() {
        const $list = $('#apollo-apps-list');
        if (!$list.length) return;

        // Find highest existing index
        $list.find('.apollo-app-row').each(function () {
            const idx = parseInt($(this).data('index'), 10);
            if (!isNaN(idx) && idx >= appIndex) {
                appIndex = idx + 1;
            }
        });

        // Sortable
        $list.sortable({
            handle: '.app-row-handle',
            placeholder: 'apollo-app-row-placeholder',
            opacity: 0.7,
            tolerance: 'pointer',
            update: function () {
                reindexRows();
                updatePreview();
            }
        });

        // Add app
        $('#apollo-add-app').on('click', addApp);

        // Reset defaults
        $('#apollo-reset-defaults').on('click', resetDefaults);

        // Delegated events
        $list.on('click', '.app-remove-row', removeApp);
        $list.on('click', '.app-upload-image', uploadImage);
        $list.on('click', '.app-remove-image', removeImage);
        $list.on('input change', 'input', updatePreview);
        $list.on('input', '.app-field-icon, .app-field-bg-color, .app-field-icon-color', updateRowPreview);

        // Initial preview
        updatePreview();
    }

    /**
     * Add new app row
     */
    function addApp() {
        const template = document.getElementById('apollo-app-row-template');
        if (!template) return;

        let html = template.innerHTML;
        html = html.replace(/__INDEX__/g, appIndex);
        appIndex++;

        const $row = $(html);
        $('#apollo-apps-list').append($row);
        $row.hide().slideDown(200);

        reindexRows();
        updatePreview();
    }

    /**
     * Remove app row
     */
    function removeApp() {
        const $row = $(this).closest('.apollo-app-row');
        const label = $row.find('.app-field-label').val();

        if (label && !confirm(config.i18n?.removeConfirm || 'Remover este app?')) {
            return;
        }

        $row.slideUp(200, function () {
            $(this).remove();
            reindexRows();
            updatePreview();
        });
    }

    /**
     * Reindex all rows after sort/remove
     */
    function reindexRows() {
        $('#apollo-apps-list .apollo-app-row').each(function (i) {
            $(this).attr('data-index', i);

            // Update all input names
            $(this).find('input').each(function () {
                const name = $(this).attr('name');
                if (name) {
                    const updated = name.replace(
                        /apollo_navbar_apps\[\d+\]/,
                        'apollo_navbar_apps[' + i + ']'
                    );
                    $(this).attr('name', updated);
                }
            });
        });
    }

    /**
     * Update single row icon preview
     */
    function updateRowPreview() {
        const $row = $(this).closest('.apollo-app-row');
        const icon = $row.find('.app-field-icon').val() || 'ri-apps-fill';
        const bgColor = $row.find('.app-field-bg-color').val() || 'FF9820';
        const iconColor = $row.find('.app-field-icon-color').val() || '#ffffff';

        const $preview = $row.find('.app-row-icon-preview');
        $preview.css({
            background: bgColor,
            color: iconColor
        });
        $preview.find('i').attr('class', icon);
    }

    /**
     * Upload background image via WP Media
     */
    function uploadImage() {
        const $row = $(this).closest('.apollo-app-row');
        const $input = $row.find('.app-field-bg-image');

        const frame = wp.media({
            title: config.i18n?.selectImage || 'Selecionar imagem',
            button: { text: config.i18n?.useImage || 'Usar esta imagem' },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            const url = attachment.sizes?.thumbnail?.url || attachment.url;
            $input.val(url).trigger('change');

            // Add remove button if not present
            if (!$row.find('.app-remove-image').length) {
                $input.after(
                    '<button type="button" class="button app-remove-image">' +
                    '<span class="dashicons dashicons-no" style="margin-top:3px;"></span>' +
                    '</button>'
                );
            }
        });

        frame.open();
    }

    /**
     * Remove background image
     */
    function removeImage() {
        const $row = $(this).closest('.apollo-app-row');
        $row.find('.app-field-bg-image').val('').trigger('change');
        $(this).remove();
    }

    /**
     * Update the live preview grid
     */
    function updatePreview() {
        const $preview = $('#apollo-preview-grid');
        if (!$preview.length) return;

        let html = '';

        $('#apollo-apps-list .apollo-app-row').each(function () {
            const label = $(this).find('.app-field-label').val() || '?';
            const icon = $(this).find('.app-field-icon').val() || 'ri-apps-fill';
            const bgColor = $(this).find('.app-field-bg-color').val() || 'FF9820';
            const iconColor = $(this).find('.app-field-icon-color').val() || '#ffffff';
            const bgImage = $(this).find('.app-field-bg-image').val() || '';

            let style = 'background:' + bgColor + ';color:' + iconColor + ';';
            if (bgImage) {
                style = 'background:url(' + bgImage + ') center/cover;color:' + iconColor + ';';
            }

            html += '<div class="preview-app-item">';
            html += '<div class="preview-app-icon" style="' + style + '">';
            html += '<i class="' + escAttr(icon) + '"></i>';
            html += '</div>';
            html += '<span class="preview-app-label">' + escHtml(label) + '</span>';
            html += '</div>';
        });

        $preview.html(html);
    }

    /**
     * Reset to defaults
     */
    function resetDefaults() {
        if (!confirm('Restaurar todos os apps para os valores padrão? As alterações não salvas serão perdidas.')) {
            return;
        }

        // Submit form to options.php with empty data won't work.
        // Instead, delete the option and reload.
        $.post(config.ajaxUrl, {
            action: 'apollo_navbar_reset_defaults',
            nonce: config.nonce
        }, function () {
            window.location.reload();
        }).fail(function () {
            // Fallback: just reload, the defaults will kick in
            window.location.reload();
        });
    }

    /**
     * Escape HTML
     */
    function escHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    /**
     * Escape attribute
     */
    function escAttr(str) {
        return str.replace(/[&<>"']/g, function (m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
        });
    }

    $(document).ready(init);

})(jQuery);
