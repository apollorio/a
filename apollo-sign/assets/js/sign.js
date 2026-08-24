/* ═══════════════════════════════════════════════════════════
   Apollo Sign — Admin Signatures Controller
   ICP-Brasil Digital Signature Management
   ═══════════════════════════════════════════════════════════ */
(function ($) {
    'use strict';

    if (typeof ApolloSign === 'undefined') return;

    /* ── State ── */
    const S = {
        signatures: [],
        filter: '',
        page: 1,
        loading: false
    };

    /* ── Helpers ── */
    function ajax(action, data, cb) {
        if (S.loading) return;
        S.loading = true;
        const fd = (data instanceof FormData) ? data : (() => {
            const f = new FormData();
            Object.entries(data || {}).forEach(([k, v]) => f.append(k, v));
            return f;
        })();
        fd.append('action', 'apollo_sign_' + action);
        fd.append('nonce', ApolloSign.nonce);

        $.ajax({
            url: ApolloSign.ajax_url,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success(r) {
                S.loading = false;
                if (r.success) { cb && cb(r.data); }
                else { toast(r.data || 'Erro', 'error'); }
            },
            error() {
                S.loading = false;
                toast('Erro de conexão', 'error');
            }
        });
    }

    function esc(str) {
        if (!str) return '';
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function maskCpf(cpf) {
        if (!cpf || cpf.length < 11) return cpf || '—';
        const c = cpf.replace(/\D/g, '');
        return c.substring(0, 3) + '.***.***-' + c.substring(9);
    }

    function maskEmail(email) {
        if (!email) return '—';
        const [u, d] = email.split('@');
        if (!d) return email;
        return u[0] + '***@' + d;
    }

    function fmtDate(d) {
        if (!d) return '—';
        const dt = new Date(d);
        return dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    function statusLabel(s) {
        const map = {
            pending: '<span class="sign-status pending"><i class="ri-time-line"></i> Pendente</span>',
            signed:  '<span class="sign-status signed"><i class="ri-checkbox-circle-fill"></i> Assinado</span>',
            revoked: '<span class="sign-status revoked"><i class="ri-close-circle-fill"></i> Revogado</span>',
            expired: '<span class="sign-status expired"><i class="ri-timer-line"></i> Expirado</span>'
        };
        return map[s] || '<span class="sign-status">' + esc(s) + '</span>';
    }

    function actionLabel(a) {
        const map = {
            created:  'Assinatura criada',
            signed:   'Documento assinado',
            verified: 'Verificação realizada',
            revoked:  'Assinatura revogada',
            failed:   'Falha na assinatura'
        };
        return map[a] || a;
    }

    /* ── Load Signatures ── */
    function loadSignatures() {
        const data = { page: S.page };
        if (S.filter) data.status = S.filter;

        ajax('load_signatures', data, function (res) {
            S.signatures = res.signatures || res || [];
            renderTable();
        });
    }

    /* ── Render Table ── */
    function renderTable() {
        const $tbody = $('#sign-tbody');

        if (!S.signatures.length) {
            $tbody.html(
                '<tr><td colspan="8">' +
                '<div class="sign-empty"><i class="ri-shield-keyhole-line"></i>' +
                '<p>Nenhuma assinatura encontrada</p></div>' +
                '</td></tr>'
            );
            return;
        }

        let html = '';
        S.signatures.forEach(function (sig) {
            const isSigned = sig.status === 'signed';
            const isPending = sig.status === 'pending';
            const certHtml = sig.certificate_cn
                ? '<div class="sign-cert-info"><span class="sign-cert-cn">' + esc(sig.certificate_cn) + '</span>' +
                  '<span class="sign-cert-issuer">' + esc(sig.certificate_issuer || '') + '</span></div>'
                : '<span style="color:var(--sign-text-muted)">—</span>';

            html += '<tr data-id="' + sig.id + '">' +
                '<td style="color:var(--sign-text-muted)">#' + sig.id + '</td>' +
                '<td><a href="#" class="sign-doc-link" data-doc="' + sig.doc_id + '">' + esc(sig.doc_title || 'Doc #' + sig.doc_id) + '</a></td>' +
                '<td><div class="sign-signer">' +
                    '<span class="sign-signer-name">' + esc(sig.signer_name || 'Sem nome') + '</span>' +
                    '<span class="sign-signer-email">' + esc(maskEmail(sig.signer_email)) + '</span>' +
                '</div></td>' +
                '<td><span class="sign-cpf">' + esc(maskCpf(sig.signer_cpf)) + '</span></td>' +
                '<td>' + statusLabel(sig.status) + '</td>' +
                '<td>' + certHtml + '</td>' +
                '<td style="white-space:nowrap">' + fmtDate(sig.signed_at || sig.created_at) + '</td>' +
                '<td><div class="sign-actions">' +
                    '<button class="sign-action-btn verify" title="Verificar" data-action="verify" data-id="' + sig.id + '"' + (isPending ? ' disabled' : '') + '><i class="ri-shield-check-line"></i></button>' +
                    '<button class="sign-action-btn" title="Trilha de Auditoria" data-action="audit" data-id="' + sig.id + '"><i class="ri-history-line"></i></button>' +
                    '<button class="sign-action-btn revoke" title="Revogar" data-action="revoke" data-id="' + sig.id + '"' + (!isSigned ? ' disabled' : '') + '><i class="ri-close-circle-line"></i></button>' +
                '</div></td>' +
            '</tr>';
        });

        $tbody.html(html);
    }

    /* ── Modals ── */
    function openModal(html) {
        $('#sign-modal').html(html);
        $('#sign-modal-overlay').addClass('active');
    }

    function closeModal() {
        $('#sign-modal-overlay').removeClass('active');
        $('#sign-modal').html('');
    }

    /* ── Verify ── */
    function verifySignature(id) {
        ajax('verify_signature', { signature_id: id }, function (res) {
            const valid = res.valid;
            const html =
                '<div class="sign-modal-head">' +
                    '<h3><i class="ri-shield-check-line"></i> Verificação</h3>' +
                    '<button class="sign-modal-close" data-close>&times;</button>' +
                '</div>' +
                '<div class="sign-modal-body">' +
                    '<div class="sign-verify-result ' + (valid ? 'valid' : 'invalid') + '">' +
                        '<div class="sign-verify-icon"><i class="ri-' + (valid ? 'checkbox-circle-fill' : 'close-circle-fill') + '"></i></div>' +
                        '<div class="sign-verify-title">' + (valid ? 'Assinatura Válida' : 'Assinatura Inválida') + '</div>' +
                        '<div class="sign-verify-desc">' + esc(res.message || '') + '</div>' +
                    '</div>' +
                    (res.signature ? '<div class="sign-info-grid">' +
                        '<div class="sign-info-item"><span class="sign-info-label">Certificado</span><span class="sign-info-value">' + esc(res.signature.certificate_cn) + '</span></div>' +
                        '<div class="sign-info-item"><span class="sign-info-label">Emissor</span><span class="sign-info-value">' + esc(res.signature.certificate_issuer) + '</span></div>' +
                        '<div class="sign-info-item"><span class="sign-info-label">Algoritmo</span><span class="sign-info-value">' + esc(res.signature.algorithm) + '</span></div>' +
                        '<div class="sign-info-item"><span class="sign-info-label">Assinado em</span><span class="sign-info-value">' + fmtDate(res.signature.signed_at) + '</span></div>' +
                    '</div>' : '') +
                '</div>' +
                '<div class="sign-modal-footer">' +
                    '<button class="sign-admin-btn" data-close>Fechar</button>' +
                '</div>';
            openModal(html);
        });
    }

    /* ── Audit Trail ── */
    function showAudit(id) {
        ajax('load_audit', { signature_id: id }, function (res) {
            const trail = res.trail || res || [];
            let items = '';
            trail.forEach(function (e) {
                items += '<li class="sign-audit-item ' + esc(e.action) + '">' +
                    '<div class="sign-audit-dot"></div>' +
                    '<div class="sign-audit-content">' +
                        '<div class="sign-audit-action">' + esc(actionLabel(e.action)) + '</div>' +
                        '<div class="sign-audit-meta">' +
                            '<span>' + esc(e.actor_name || 'Sistema') + '</span>' +
                            '<span>' + fmtDate(e.created_at) + '</span>' +
                            '<span>' + esc(e.actor_ip || '') + '</span>' +
                        '</div>' +
                        (e.details ? '<div class="sign-audit-detail">' + esc(e.details) + '</div>' : '') +
                    '</div>' +
                '</li>';
            });

            if (!trail.length) {
                items = '<li class="sign-audit-item"><div class="sign-audit-content"><div class="sign-audit-action" style="color:var(--sign-text-dim)">Nenhum registro</div></div></li>';
            }

            const html =
                '<div class="sign-modal-head">' +
                    '<h3><i class="ri-history-line"></i> Trilha de Auditoria</h3>' +
                    '<button class="sign-modal-close" data-close>&times;</button>' +
                '</div>' +
                '<div class="sign-modal-body">' +
                    '<ul class="sign-audit-list">' + items + '</ul>' +
                '</div>' +
                '<div class="sign-modal-footer">' +
                    '<button class="sign-admin-btn" data-close>Fechar</button>' +
                '</div>';
            openModal(html);
        });
    }

    /* ── Revoke ── */
    function revokeSignature(id) {
        const html =
            '<div class="sign-modal-head">' +
                '<h3><i class="ri-error-warning-line"></i> Revogar Assinatura</h3>' +
                '<button class="sign-modal-close" data-close>&times;</button>' +
            '</div>' +
            '<div class="sign-modal-body">' +
                '<p style="color:var(--sign-danger);font-weight:500;margin:0 0 16px">' +
                    'Esta ação é irreversível. A assinatura digital será permanentemente revogada.' +
                '</p>' +
                '<div class="sign-form-group">' +
                    '<label class="sign-form-label">Motivo da revogação</label>' +
                    '<input type="text" class="sign-form-input" id="revoke-reason" placeholder="Motivo (obrigatório)">' +
                '</div>' +
            '</div>' +
            '<div class="sign-modal-footer">' +
                '<button class="sign-admin-btn" data-close>Cancelar</button>' +
                '<button class="sign-admin-btn danger" id="confirm-revoke" data-id="' + id + '">Revogar</button>' +
            '</div>';
        openModal(html);
    }

    /* ── Sign Document (PFX upload) ── */
    function showSignModal(id) {
        const html =
            '<div class="sign-modal-head">' +
                '<h3><i class="ri-shield-keyhole-fill"></i> Assinar Documento</h3>' +
                '<button class="sign-modal-close" data-close>&times;</button>' +
            '</div>' +
            '<div class="sign-modal-body">' +
                '<div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;padding:12px;background:rgba(0,104,71,.1);border:1px solid rgba(0,104,71,.3);border-radius:8px">' +
                    '<i class="ri-shield-check-fill" style="color:var(--sign-icpbr);font-size:20px"></i>' +
                    '<span style="font-size:12px;color:var(--sign-text-dim)">Assinatura digital ICP-Brasil com certificado A1 (.pfx)</span>' +
                '</div>' +
                '<div class="sign-form-group">' +
                    '<label class="sign-form-label">Certificado Digital (.pfx / .p12)</label>' +
                    '<input type="file" class="sign-form-input" id="sign-pfx-file" accept=".pfx,.p12">' +
                '</div>' +
                '<div class="sign-form-group">' +
                    '<label class="sign-form-label">Senha do Certificado</label>' +
                    '<input type="password" class="sign-form-input" id="sign-pfx-password" placeholder="Senha do certificado">' +
                '</div>' +
            '</div>' +
            '<div class="sign-modal-footer">' +
                '<button class="sign-admin-btn" data-close>Cancelar</button>' +
                '<button class="sign-admin-btn primary" id="confirm-sign" data-id="' + id + '"><i class="ri-quill-pen-fill"></i> Assinar</button>' +
            '</div>';
        openModal(html);
    }

    /* ── Toast ── */
    let toastTimer;
    function toast(msg, type) {
        type = type || 'info';
        let $t = $('#sign-toast');
        if (!$t.length) {
            $('body').append('<div id="sign-toast" class="sign-toast"></div>');
            $t = $('#sign-toast');
        }
        $t.text(msg).removeClass('show success error info').addClass(type);
        setTimeout(() => $t.addClass('show'), 10);
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => $t.removeClass('show'), 3500);
    }

    /* ══════════════════════════════════════════════════════
       Events
       ══════════════════════════════════════════════════════ */

    /* Filter */
    $(document).on('change', '#sign-filter-status', function () {
        S.filter = $(this).val();
        S.page = 1;
        loadSignatures();
    });

    /* Refresh */
    $(document).on('click', '#sign-refresh', function () {
        loadSignatures();
    });

    /* Action buttons */
    $(document).on('click', '.sign-action-btn', function () {
        const action = $(this).data('action');
        const id = $(this).data('id');
        if (!action || !id) return;

        switch (action) {
            case 'verify': verifySignature(id); break;
            case 'audit':  showAudit(id);       break;
            case 'revoke': revokeSignature(id);  break;
            case 'sign':   showSignModal(id);    break;
        }
    });

    /* Pending row click to sign */
    $(document).on('click', '.sign-status.pending', function () {
        const id = $(this).closest('tr').data('id');
        if (id) showSignModal(id);
    });

    /* Modal close */
    $(document).on('click', '[data-close]', closeModal);
    $(document).on('click', '#sign-modal-overlay', function (e) {
        if (e.target === this) closeModal();
    });

    /* Confirm revoke */
    $(document).on('click', '#confirm-revoke', function () {
        const id = $(this).data('id');
        const reason = $.trim($('#revoke-reason').val());
        if (!reason) {
            toast('Informe o motivo da revogação', 'error');
            return;
        }
        ajax('revoke_signature', { signature_id: id, reason: reason }, function () {
            toast('Assinatura revogada', 'success');
            closeModal();
            loadSignatures();
        });
    });

    /* Confirm sign */
    $(document).on('click', '#confirm-sign', function () {
        const id = $(this).data('id');
        const file = document.getElementById('sign-pfx-file')?.files?.[0];
        const pw = $.trim($('#sign-pfx-password').val());

        if (!file) { toast('Selecione o certificado .pfx', 'error'); return; }
        if (!pw)   { toast('Informe a senha do certificado', 'error'); return; }

        const fd = new FormData();
        fd.append('signature_id', id);
        fd.append('pfx_file', file);
        fd.append('pfx_password', pw);

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="ri-loader-4-line" style="animation:spin .8s linear infinite"></i> Assinando...');

        ajax('sign_document', fd, function (res) {
            toast('Documento assinado com sucesso!', 'success');
            closeModal();
            loadSignatures();
        });
    });

    /* Keyboard */
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && $('#sign-modal-overlay').hasClass('active')) {
            closeModal();
        }
    });

    /* ── Init ── */
    $(document).ready(function () {
        if ($('#apollo-sign-admin').length) {
            loadSignatures();
        }
    });

})(jQuery);
