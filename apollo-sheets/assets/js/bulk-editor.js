/**
 * Apollo Bulk Editor — Plain HTML table with inline editing
 *
 * Handles: data loading, cell editing, batch saving, pagination, search, CSV export.
 *
 * Requires: jQuery, wp-util
 * Localized via ApolloBulk global object.
 *
 * @package Apollo\Sheets
 */

(function ($) {
    'use strict';

    // ═══════════════════════════════════════════════════════════════════
    // STATE
    // ═══════════════════════════════════════════════════════════════════

    const config = window.ApolloBulk || {};
    const state = {
        data: [],                     // Current rows
        originalData: [],             // Snapshot for change detection
        changedRows: new Set(),       // Set of row indices with changes
        currentPage: 1,
        totalPages: 1,
        totalRows: 0,
        perPage: 50,
        loading: false,
        saving: false,
        columns: [],
        contentType: config.contentType || '',
        entityType: config.entityType || 'post_type',
        searchTerm: '',
        filterValue: '',
    };

    // ═══════════════════════════════════════════════════════════════════
    // INIT
    // ═══════════════════════════════════════════════════════════════════

    $(document).ready(function () {
        initEventListeners();
        loadData();
    });

    function initEventListeners() {
        // Save button
        $('#bulk-btn-save').on('click', saveChanges);

        // Export CSV
        $('#bulk-btn-export').on('click', exportCSV);

        // Pagination
        $('#bulk-page-prev').on('click', function () {
            if (state.currentPage > 1) {
                state.currentPage--;
                loadData();
            }
        });
        $('#bulk-page-next').on('click', function () {
            if (state.currentPage < state.totalPages) {
                state.currentPage++;
                loadData();
            }
        });
        $('#bulk-page-current').on('change', function () {
            const page = parseInt($(this).val(), 10);
            if (page >= 1 && page <= state.totalPages) {
                state.currentPage = page;
                loadData();
            } else {
                $(this).val(state.currentPage);
            }
        });

        // Search
        let searchTimer = null;
        $('#bulk-search').on('input', function () {
            clearTimeout(searchTimer);
            const val = $(this).val();
            searchTimer = setTimeout(function () {
                state.searchTerm = val;
                state.currentPage = 1;
                loadData();
            }, 400);
        });

        // Filter
        $('#bulk-filter-select').on('change', function () {
            state.filterValue = $(this).val();
            state.currentPage = 1;
            loadData();
        });

        // Input changes
        $('#apollo-bulk-tbody').on('input', 'input', function () {
            const rowIndex = $(this).closest('tr').index();
            const colKey = $(this).data('col');
            const value = $(this).val();
            if (state.data[rowIndex]) {
                state.data[rowIndex][colKey] = value;
            }
            state.changedRows.add(rowIndex);
            $('#bulk-btn-save').prop('disabled', false);
        });

        // Warn before leaving with unsaved changes
        $(window).on('beforeunload', function () {
            if (state.changedRows.size > 0) {
                return 'Existem alterações não salvas. Deseja sair?';
            }
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // DATA LOADING
    // ═══════════════════════════════════════════════════════════════════

    function loadData() {
        if (state.loading) return;
        state.loading = true;

        setConsole(config.i18n.loading, 'info');

        const postData = {
            action: 'apollo_bulk_load',
            nonce: config.nonce,
            content_type: state.contentType,
            entity_type: state.entityType,
            per_page: state.perPage,
            page: state.currentPage,
            search: state.searchTerm,
            filter: state.filterValue,
        };

        $.post(config.ajaxUrl, postData, function (response) {
            state.loading = false;

            if (!response.success) {
                const errorMsg = response.data?.message || config.i18n.error;
                setConsole(errorMsg, 'error');
                console.error('Apollo Bulk Load Error:', response);
                return;
            }

            const d = response.data;

            state.data = d.rows || [];
            state.originalData = JSON.parse(JSON.stringify(state.data));
            state.totalRows = d.total || 0;
            state.totalPages = d.pages || 1;
            state.columns = d.columns || [];
            state.changedRows.clear();

            updatePagination();
            renderTable();

            if (state.data.length === 0) {
                setConsole(config.i18n.noData, 'info');
            } else {
                clearConsole();
            }

            $('#bulk-total-badge').text(state.totalRows);
        }).fail(function (xhr, status, error) {
            state.loading = false;
            setConsole(config.i18n.error, 'error');
            console.error('AJAX Error:', xhr.responseText);
        });
    }

    function renderTable() {
        const tbody = $('#apollo-bulk-tbody');
        tbody.empty();

        state.data.forEach((row, index) => {
            const tr = $('<tr></tr>');
            tr.append('<td>' + (index + 1) + '</td>');

            state.columns.forEach(col => {
                const colKey = col.key;
                const value = row[colKey] || '';
                const input = $('<input>').attr({
                    type: getInputType(col),
                    value: value,
                    'aria-label': col.title,
                    'data-col': colKey,
                });
                if (!col.editable) {
                    input.prop('readonly', true);
                }
                const td = $('<td></td>').append(input);
                tr.append(td);
            });

            tbody.append(tr);
        });
    }

    function getInputType(col) {
        if (col.type === 'numeric') return 'number';
        if (col.type === 'date') return 'date';
        if (col.type === 'email') return 'email';
        return 'text';
    }

    function updatePagination() {
        $('#bulk-page-current').val(state.currentPage);
        $('#bulk-page-total').text('of ' + state.totalPages);
        $('#bulk-page-prev').prop('disabled', state.currentPage <= 1);
        $('#bulk-page-next').prop('disabled', state.currentPage >= state.totalPages);
    }

    // ═══════════════════════════════════════════════════════════════════
    // SAVING
    // ═══════════════════════════════════════════════════════════════════

    function saveChanges() {
        if (state.saving || state.changedRows.size === 0) return;
        state.saving = true;

        $('#bulk-save-modal').fadeIn(200);
        setConsole(config.i18n.saving, 'info');

        const changes = [];
        state.changedRows.forEach(rowIndex => {
            const row = state.data[rowIndex];
            const original = state.originalData[rowIndex];
            const changedData = {};

            state.columns.forEach(col => {
                const colKey = col.key;
                const newValue = row[colKey];
                const oldValue = original[colKey];
                if (newValue !== oldValue) {
                    changedData[colKey] = newValue;
                }
            });

            if (Object.keys(changedData).length > 0) {
                changes.push({
                    id: row.ID || row.id,
                    data: changedData,
                });
            }
        });

        $.post(config.ajaxUrl, {
            action: 'apollo_bulk_save',
            nonce: config.nonce,
            content_type: state.contentType,
            entity_type: state.entityType,
            changes: changes,
        }, function (response) {
            state.saving = false;
            $('#bulk-save-modal').fadeOut(200);

            if (!response.success) {
                const errorMsg = response.data?.message || config.i18n.error;
                setConsole(errorMsg, 'error');
                console.error('Apollo Bulk Save Error:', response);
                return;
            }

            state.changedRows.clear();
            state.originalData = JSON.parse(JSON.stringify(state.data));
            $('#bulk-btn-save').prop('disabled', true);
            setConsole(config.i18n.saved, 'success');
        }).fail(function (xhr, status, error) {
            state.saving = false;
            $('#bulk-save-modal').fadeOut(200);
            setConsole(config.i18n.error, 'error');
            console.error('AJAX Error:', xhr.responseText);
        });
    }

    // ═══════════════════════════════════════════════════════════════════
    // EXPORT
    // ═══════════════════════════════════════════════════════════════════

    function exportCSV() {
        const csv = generateCSV();
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = state.contentType + '_bulk_export.csv';
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function generateCSV() {
        const headers = ['ID'].concat(state.columns.map(col => col.title));
        const rows = [headers];

        state.data.forEach(row => {
            const csvRow = [row.ID || row.id];
            state.columns.forEach(col => {
                csvRow.push(row[col.key] || '');
            });
            rows.push(csvRow);
        });

        return rows.map(row => row.map(cell => '"' + String(cell).replace(/"/g, '""') + '"').join(',')).join('\n');
    }

    // ═══════════════════════════════════════════════════════════════════
    // UTILITIES
    // ═══════════════════════════════════════════════════════════════════

    function setConsole(message, type = 'info') {
        const consoleEl = $('#bulk-console');
        consoleEl.removeClass('success error info').addClass(type).text(message).show();
    }

    function clearConsole() {
        $('#bulk-console').hide();
    }

})(jQuery);