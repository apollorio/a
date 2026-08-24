/**
 * admin-test.js — live test runner for Apollo Statistics TestPanel.
 *
 * Runs all tests via WP admin-ajax one-by-one, updating the UI in real time.
 * Sends cleanup beacon on page leave so test data is always removed.
 *
 * @package Apollo\Statistics
 * @since   2.0.0
 */

/* global apolloTestPanel, jQuery */
(function ($) {
    'use strict';

    var cfg     = window.apolloTestPanel || {};
    var tests   = cfg.tests   || [];
    var total   = cfg.totalTests || tests.length;
    var running = false;
    var counts  = { ok: 0, warn: 0, fail: 0, ran: 0 };
    var LS_AUTORUN = 'apollo_atp_autorun';
    var domAutoKickDone = false;

    function getAutorunEnabled() {
        try {
            var v = localStorage.getItem(LS_AUTORUN);
            if (v === null || v === '') {
                return true;
            }
            return v === '1' || v === 'true';
        } catch (e) {
            return true;
        }
    }

    function setAutorunEnabled(on) {
        try {
            localStorage.setItem(LS_AUTORUN, on ? '1' : '0');
        } catch (e) { /* ignore */ }
    }

    function domHasTestRows() {
        var root = document.getElementById('atp-sections');
        return !!(root && root.querySelectorAll('.atp-row').length > 0);
    }

    function tryDomAutoKick() {
        if (domAutoKickDone || running || !getAutorunEnabled()) {
            return;
        }
        if (!domHasTestRows()) {
            return;
        }
        domAutoKickDone = true;
        setTimeout(runAll, 80);
    }

    function wireDomAutorun() {
        if (!getAutorunEnabled()) {
            return;
        }

        if (domHasTestRows()) {
            tryDomAutoKick();
            return;
        }

        var root = document.getElementById('atp-sections');
        if (!root) {
            tryDomAutoKick();
            return;
        }

        var mo = new MutationObserver(function () {
            if (domHasTestRows()) {
                mo.disconnect();
                tryDomAutoKick();
            }
        });
        mo.observe(root, { childList: true, subtree: true });

        setTimeout(function () {
            mo.disconnect();
            if (domHasTestRows()) {
                tryDomAutoKick();
            }
        }, 8000);
    }

    /* ──────────────── UI helpers ──────────────── */

    var ICONS = {
        idle:    'ri-checkbox-blank-circle-line',
        testing: 'ri-loader-4-line',
        ok:      'ri-checkbox-circle-fill',
        warn:    'ri-error-warning-fill',
        fail:    'ri-close-circle-fill',
    };

    var LABELS = {
        idle:    'Aguardando',
        testing: 'Testando…',
        ok:      'Aprovado',
        warn:    'Atenção',
        fail:    'Falhou',
    };

    function setCheck(id, status) {
        var $el = $('#atp-check-' + id);
        $el.attr('class', 'atp-check atp-check--' + status);
        $el.find('i').attr('class', ICONS[status] || ICONS.idle);
    }

    function setBadge(id, status, detail) {
        $('#atp-badge-' + id)
            .attr('class', 'atp-badge atp-badge--' + status)
            .text(LABELS[status] || status);

        $('#atp-detail-' + id).text(detail || '–');
    }

    function resetRow(id) {
        setCheck(id, 'idle');
        setBadge(id, 'idle', '');
    }

    function updateProgress() {
        var pct = total > 0 ? Math.round((counts.ran / total) * 100) : 0;
        $('#atp-progress-fill').css('width', pct + '%');

        $('#atp-summary').html(
            '<span class="atp-s-ok">' + counts.ok + ' ✓</span> ' +
            '<span class="atp-s-warn">' + counts.warn + ' ⚠</span> ' +
            '<span class="atp-s-fail">' + counts.fail + ' ✗</span> ' +
            '<span class="atp-total">/ ' + total + '</span>'
        );

        // Update per-section badges.
        document.querySelectorAll('.atp-section').forEach(function (sec) {
            var sid  = sec.dataset.section;
            var $b   = $('#sec-badge-' + sid);
            var rows = sec.querySelectorAll('.atp-badge');
            var fail = 0, warn = 0, ok = 0, pending = 0;

            rows.forEach(function (badge) {
                if (badge.classList.contains('atp-badge--fail'))    { fail++;    }
                else if (badge.classList.contains('atp-badge--warn')){ warn++;    }
                else if (badge.classList.contains('atp-badge--ok'))  { ok++;      }
                else                                                  { pending++; }
            });

            $b.attr('class', 'atp-sec-badge' +
                (fail    ? ' atp-sec-badge--fail' :
                 warn    ? ' atp-sec-badge--warn' :
                 pending ? '' : ' atp-sec-badge--ok'));
            $b.find('span').text(ok > 0 && !fail && !warn && !pending ? '✓' :
                                 fail ? fail + ' ✗' :
                                 warn ? warn + ' ⚠' : rows.length);
        });
    }

    /* ──────────────── AJAX test runner ────────────── */

    function runTest(id) {
        return $.Deferred(function (dfd) {

            setCheck(id, 'testing');
            setBadge(id, 'testing', '');

            // Scroll test row into view gently.
            var el = document.getElementById('atp-row-' + id);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }

            $.post(cfg.ajaxUrl, {
                action: 'apollo_stats_run_test',
                nonce:  cfg.nonce,
                test:   id,
            })
            .done(function (res) {
                var status = 'fail';
                var detail = 'Resposta inválida do servidor';

                if (res && res.success && res.data) {
                    status = res.data.status || 'fail';
                    detail = res.data.detail || '';
                } else if (res && res.data && res.data.message) {
                    detail = res.data.message;
                }

                setCheck(id, status);
                setBadge(id, status, detail);
                counts[status] = (counts[status] || 0) + 1;
            })
            .fail(function (xhr) {
                setCheck(id, 'fail');
                setBadge(id, 'fail', 'Erro AJAX HTTP ' + xhr.status);
                counts.fail++;
            })
            .always(function () {
                counts.ran++;
                updateProgress();
                dfd.resolve();
            });

        }).promise();
    }

    function runCleanup() {
        return $.post(cfg.ajaxUrl, {
            action: 'apollo_stats_cleanup_tests',
            nonce:  cfg.nonce,
        });
    }

    function sleep(ms) {
        return $.Deferred(function (dfd) {
            setTimeout(dfd.resolve, ms);
        }).promise();
    }

    /* ──────────────── Main orchestrator ────────────── */

    function runAll() {
        if (running || !tests.length) {
            return;
        }
        running = true;
        counts  = { ok: 0, warn: 0, fail: 0, ran: 0 };

        // Reset all rows.
        tests.forEach(function (id) { resetRow(id); });

        $('#atp-run-all')
            .prop('disabled', true)
            .html('<i class="ri-loader-4-line atp-spin"></i> Rodando…');

        // Clear leftover test data + reset rate limit before running.
        runCleanup().always(function () {

            // Run tests sequentially using a promise chain.
            var chain = $.Deferred().resolve().promise();

            tests.forEach(function (id) {
                chain = chain.then(function () {
                    return runTest(id);
                }).then(function () {
                    return sleep(120); // small visual gap between each test
                });
            });

            chain.always(function () {
                var done_label = counts.fail > 0 ? 'Concluído com falhas' : 'Todos aprovados';
                $('#atp-run-all')
                    .prop('disabled', false)
                    .html('<i class="ri-restart-line"></i> ' + done_label + ' — Rodar Novamente');
                running = false;
            });
        });
    }

    /* ──────────────── Page lifecycle ────────────── */

    $(document).ready(function () {
        if (!tests.length) {
            return;
        }

        $('#atp-run-all').on('click', runAll);

        var $chk = $('#atp-autorun');
        if ($chk.length) {
            $chk.prop('checked', getAutorunEnabled());
            $chk.on('change', function () {
                var on = $(this).prop('checked');
                setAutorunEnabled(on);
                if (on && !running && counts.ran === 0) {
                    domAutoKickDone = false;
                    wireDomAutorun();
                }
            });
        }

        wireDomAutorun();
    });

    // Cleanup on page leave via sendBeacon (fire-and-forget).
    function sendCleanupBeacon() {
        if (navigator.sendBeacon) {
            var data = new URLSearchParams({
                action: 'apollo_stats_cleanup_tests',
                nonce:  cfg.nonce,
            });
            navigator.sendBeacon(cfg.ajaxUrl, data);
        }
    }

    window.addEventListener('pagehide',     sendCleanupBeacon);
    window.addEventListener('beforeunload', sendCleanupBeacon);

}(jQuery));
