/**
 * Apollo Email — Admin JavaScript
 *
 * Handles:
 *  - AJAX test email sending
 *  - Transport field toggling
 *  - Queue bulk actions
 *  - Confirmation dialogs
 *
 * @package Apollo\Email
 * @since   1.0.0
 */

(function ($) {
    'use strict';

    // Config injected via wp_localize_script
    const config = window.apolloEmailAdmin || {};

    /**
     * Transport field visibility toggle.
     */
    function initTransportToggle() {
        const $transport = $('select[name="apollo_email_settings[transport]"]');
        if (!$transport.length) return;

        const fieldMap = {
            smtp: ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_user', 'smtp_pass', 'smtp_encryption'],
            ses: ['ses_region', 'ses_key', 'ses_secret'],
            sendgrid: ['sendgrid_key']
        };

        const allFields = Object.values(fieldMap).flat();

        function toggleFields() {
            const val = $transport.val();

            // Hide all transport-specific fields
            allFields.forEach(function (key) {
                const $row = $('input[name="apollo_email_settings[' + key + ']"], select[name="apollo_email_settings[' + key + ']"]')
                    .closest('tr');
                $row.hide();
            });

            // Show relevant fields
            if (fieldMap[val]) {
                fieldMap[val].forEach(function (key) {
                    const $row = $('input[name="apollo_email_settings[' + key + ']"], select[name="apollo_email_settings[' + key + ']"]')
                        .closest('tr');
                    $row.show();
                });
            }
        }

        $transport.on('change', toggleFields);
        toggleFields(); // Run on load
    }

    /**
     * AJAX test email via REST API.
     */
    function initTestEmail() {
        const $form = $('.apollo-email-test-form');
        if (!$form.length) return;

        $form.on('submit', function (e) {
            // Only intercept if REST API is available
            if (!config.restUrl || !config.nonce) return;

            e.preventDefault();

            const $btn = $form.find('button[type="submit"]');
            const $email = $form.find('#test_email');
            const email = $email.val().trim();

            if (!email) {
                alert('Informe um e-mail válido.');
                return;
            }

            $btn.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: config.restUrl + 'email/test',
                method: 'POST',
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', config.nonce);
                },
                data: JSON.stringify({ to: email }),
                contentType: 'application/json',
                success: function (response) {
                    if (response.success) {
                        showNotice('E-mail de teste enviado com sucesso!', 'success');
                    } else {
                        showNotice('Falha ao enviar: ' + (response.error || 'Erro desconhecido'), 'error');
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Erro ao comunicar com o servidor.';
                    showNotice(msg, 'error');
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Enviar Teste');
                }
            });
        });
    }

    /**
     * Queue action confirmations.
     */
    function initQueueActions() {
        $(document).on('submit', 'form', function () {
            const action = $(this).find('input[name="queue_action"]').val();
            if (action === 'cancel') {
                return confirm('Cancelar este e-mail da fila?');
            }
            return true;
        });
    }

    /**
     * Show admin notice dynamically.
     */
    function showNotice(message, type) {
        type = type || 'success';
        const $notice = $(
            '<div class="notice notice-' + type + ' is-dismissible">' +
            '<p>' + escHtml(message) + '</p>' +
            '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>' +
            '</div>'
        );

        $('.apollo-email-admin h1').first().after($notice);

        $notice.find('.notice-dismiss').on('click', function () {
            $notice.fadeOut(200, function () { $notice.remove(); });
        });

        // Auto-dismiss after 6s
        setTimeout(function () {
            $notice.fadeOut(300, function () { $notice.remove(); });
        }, 6000);
    }

    /**
     * Escape HTML for safe insertion.
     */
    function escHtml(str) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(str).replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    /**
     * Auto-dismiss WP admin notices after 5s.
     */
    function initAutoDismiss() {
        setTimeout(function () {
            $('.apollo-email-admin .notice.is-dismissible').fadeOut(400);
        }, 5000);
    }

    // ── Init ────────────────────────────────────────────────
    $(document).ready(function () {
        initTransportToggle();
        initTestEmail();
        initQueueActions();
        initAutoDismiss();
    });

})(jQuery);
