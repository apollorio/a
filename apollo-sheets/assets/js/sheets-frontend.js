/**
 * Apollo Sheets — Frontend JS
 *
 * Minimal wrapper for DataTables initialization.
 * The actual init calls are injected inline by Render::collect_datatables_init()
 * and printed in wp_footer. This file ensures DataTables is ready.
 *
 * @package Apollo\Sheets
 */

(function ($) {
    'use strict';

    // Verify DataTables is loaded
    if (typeof $.fn.DataTable === 'undefined') {
        console.warn('Apollo Sheets: DataTables library not loaded.');
        return;
    }

    // Global DataTables defaults for Apollo
    $.extend($.fn.dataTable.defaults, {
        dom: '<"apollo-sheet-dt-controls"lf>rt<"apollo-sheet-dt-footer"ip>',
        autoWidth: false,
        stateSave: false,
        deferRender: true,
        processing: true,
    });

    // Responsive: on window resize, adjust tables
    var resizeTimer;
    $(window).on('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            $('.apollo-sheet-dt').each(function () {
                var table = $(this).DataTable();
                if (table && typeof table.columns === 'function') {
                    table.columns.adjust();
                }
            });
        }, 250);
    });

})(jQuery);
