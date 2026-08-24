/**
 * Apollo Sheets — Admin Editor JS
 *
 * Handles: AJAX save, add/remove rows/cols, preview, import/export forms.
 *
 * @package Apollo\Sheets
 */

(function ($) {
    'use strict';

    if (typeof apolloSheetsAdmin === 'undefined') {
        return;
    }

    var config = apolloSheetsAdmin;

    // ═══════════════════════════════════════════════════════════════
    // EDITOR: Save Form
    // ═══════════════════════════════════════════════════════════════

    $('#apollo-sheets-form').on('submit', function (e) {
        e.preventDefault();
        saveSheet();
    });

    function saveSheet() {
        var $btn = $('#save-sheet');
        var $status = $('#save-status');

        $btn.prop('disabled', true).text('Salvando...');
        $status.removeClass('visible error');

        var formData = new FormData(document.getElementById('apollo-sheets-form'));
        formData.append('action', 'apollo_sheets_save');
        formData.append('_wpnonce', config.nonce);

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    $status.text(config.i18n.saved).addClass('visible');

                    // Redirect to edit page for new sheets
                    if (response.data && response.data.redirect && config.isNew) {
                        window.location.href = response.data.redirect;
                        return;
                    }

                    // Update sheet_id in form for newly created sheets
                    if (response.data && response.data.id) {
                        $('input[name="sheet_id"]').val(response.data.id);
                        config.sheetId = response.data.id;
                        config.isNew = false;
                    }
                } else {
                    $status.text(response.data || config.i18n.error).addClass('visible error');
                }
            },
            error: function () {
                $status.text(config.i18n.error).addClass('visible error');
            },
            complete: function () {
                $btn.prop('disabled', false).text(config.isNew ? 'Criar Sheet' : 'Salvar Alterações');

                setTimeout(function () {
                    $status.removeClass('visible');
                }, 3000);
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // EDITOR: Add / Remove Rows & Columns
    // ═══════════════════════════════════════════════════════════════

    $('#add-row').on('click', function () {
        var $tbody = $('.apollo-sheets-edit-table tbody');
        var $lastRow = $tbody.find('tr:last');
        var rowIndex = $tbody.find('tr').length;
        var colCount = $lastRow.find('.cell-input').length;

        var $newRow = $('<tr>');
        $newRow.append('<td class="row-num">' + (rowIndex + 1) + '</td>');

        for (var c = 0; c < colCount; c++) {
            $newRow.append(
                '<td><input type="text" name="data[' + rowIndex + '][' + c + ']" value="" class="cell-input" data-row="' + rowIndex + '" data-col="' + c + '"></td>'
            );
        }

        $tbody.append($newRow);
    });

    $('#add-col').on('click', function () {
        var $table = $('.apollo-sheets-edit-table');
        var colIndex = $table.find('thead th').length - 1; // subtract row-num col

        // Add header
        $table.find('thead tr').append('<th>' + colLetter(colIndex) + '</th>');

        // Add cell to each row
        $table.find('tbody tr').each(function () {
            var rowIndex = $(this).find('.row-num').text() - 1;
            $(this).append(
                '<td><input type="text" name="data[' + rowIndex + '][' + colIndex + ']" value="" class="cell-input" data-row="' + rowIndex + '" data-col="' + colIndex + '"></td>'
            );
        });
    });

    $('#remove-last-row').on('click', function () {
        var $rows = $('.apollo-sheets-edit-table tbody tr');
        if ($rows.length > 1) {
            $rows.last().remove();
        }
    });

    $('#remove-last-col').on('click', function () {
        var $headerCells = $('.apollo-sheets-edit-table thead th');
        if ($headerCells.length > 2) { // keep at least row-num + 1 col
            $headerCells.last().remove();
            $('.apollo-sheets-edit-table tbody tr').each(function () {
                $(this).find('td:last').remove();
            });
        }
    });

    // ═══════════════════════════════════════════════════════════════
    // EDITOR: Preview
    // ═══════════════════════════════════════════════════════════════

    $('#preview-sheet').on('click', function () {
        var $preview = $('#sheet-preview');
        var $content = $('#sheet-preview-content');

        if (!config.sheetId) {
            alert('Salve a sheet primeiro para gerar preview.');
            return;
        }

        $content.html('<em>Carregando...</em>');
        $preview.show();

        $.post(config.ajaxUrl, {
            action: 'apollo_sheets_preview',
            sheet_id: config.sheetId,
            _wpnonce: config.nonce
        }, function (response) {
            if (response.success && response.data && response.data.html) {
                $content.html(response.data.html);
            } else {
                $content.html('<p style="color:#d63638">Erro ao gerar preview.</p>');
            }
        }).fail(function () {
            $content.html('<p style="color:#d63638">Erro de conexão.</p>');
        });
    });

    // ═══════════════════════════════════════════════════════════════
    // EDITOR: Keyboard Navigation
    // ═══════════════════════════════════════════════════════════════

    $(document).on('keydown', '.cell-input', function (e) {
        var $cell = $(this);
        var row = parseInt($cell.data('row'), 10);
        var col = parseInt($cell.data('col'), 10);
        var targetRow = row;
        var targetCol = col;

        switch (e.key) {
            case 'Tab':
                e.preventDefault();
                targetCol = e.shiftKey ? col - 1 : col + 1;
                break;
            case 'Enter':
                e.preventDefault();
                targetRow = e.shiftKey ? row - 1 : row + 1;
                break;
            case 'ArrowUp':
                if (e.ctrlKey || e.metaKey) { targetRow = row - 1; }
                else { return; }
                break;
            case 'ArrowDown':
                if (e.ctrlKey || e.metaKey) { targetRow = row + 1; }
                else { return; }
                break;
            default:
                return;
        }

        var $target = $('.cell-input[data-row="' + targetRow + '"][data-col="' + targetCol + '"]');
        if ($target.length) {
            $target.focus().select();
        }
    });

    // ═══════════════════════════════════════════════════════════════
    // IMPORT FORM
    // ═══════════════════════════════════════════════════════════════

    $('#apollo-sheets-import-form').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');

        $btn.prop('disabled', true).text('Importando...');

        var formData = new FormData(this);
        formData.append('action', 'apollo_sheets_import');
        formData.append('_wpnonce', config.nonce);

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success && response.data && response.data.redirect) {
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data || 'Erro ao importar.');
                    $btn.prop('disabled', false).text('Importar');
                }
            },
            error: function () {
                alert('Erro de conexão.');
                $btn.prop('disabled', false).text('Importar');
            }
        });
    });

    // ═══════════════════════════════════════════════════════════════
    // EXPORT FORM
    // ═══════════════════════════════════════════════════════════════

    $('#apollo-sheets-export-form').on('submit', function (e) {
        e.preventDefault();

        var sheetId = $('#export-sheet').val();
        var format = $('#export-format').val();

        if (!sheetId) {
            alert('Selecione uma sheet.');
            return;
        }

        $.post(config.ajaxUrl, {
            action: 'apollo_sheets_export',
            sheet_id: sheetId,
            format: format,
            _wpnonce: config.nonce
        }, function (response) {
            if (response.success && response.data) {
                downloadFile(response.data.filename, response.data.content, response.data.mime);
            } else {
                alert(response.data || 'Erro ao exportar.');
            }
        }).fail(function () {
            alert('Erro de conexão.');
        });
    });

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Convert column index to letter (0→A, 1→B, ..., 25→Z, 26→AA)
     */
    function colLetter(index) {
        var letter = '';
        while (index >= 0) {
            letter = String.fromCharCode(65 + (index % 26)) + letter;
            index = Math.floor(index / 26) - 1;
        }
        return letter;
    }

    /**
     * Trigger file download from content
     */
    function downloadFile(filename, content, mime) {
        var blob = new Blob([content], { type: mime + ';charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

})(jQuery);
