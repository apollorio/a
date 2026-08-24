/**
 * Apollo Depoimentos — Frontend interactions
 *
 * - Form submission via REST API
 * - Character counter
 * - Load more pagination
 *
 * @package Apollo\Comment
 */

(function () {
    'use strict';

    if (typeof apolloDepoimento === 'undefined') return;

    const cfg = apolloDepoimento;

    /* ─── Form: char counter + submit ────────────────────────── */
    const textarea = document.getElementById('depoimento-new-text');
    const charEl   = document.getElementById('depoimento-char-current');
    const submitEl = document.getElementById('depoimento-submit');

    if (textarea && charEl && submitEl) {
        textarea.addEventListener('input', function () {
            const len = this.value.trim().length;
            charEl.textContent = len;
            submitEl.disabled = len < 3;
        });

        submitEl.addEventListener('click', async function () {
            const content = textarea.value.trim();
            if (content.length < 3) return;

            const wrapper = textarea.closest('.depoimento-form-wrapper');
            const postId  = wrapper ? wrapper.dataset.postId : null;
            if (!postId) return;

            submitEl.disabled = true;
            submitEl.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Enviando…';

            try {
                const res = await fetch(cfg.rest_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': cfg.nonce,
                    },
                    body: JSON.stringify({
                        post_id: parseInt(postId, 10),
                        content: content,
                    }),
                });

                if (!res.ok) throw new Error(cfg.i18n.error);

                const data = await res.json();

                // Insert new card at top of list
                const list = document.querySelector('.depoimentos-list');
                if (list && data.author) {
                    const card = buildCard(data);
                    list.insertAdjacentHTML('afterbegin', card);
                }

                // Clear form
                textarea.value = '';
                charEl.textContent = '0';
                submitEl.innerHTML = '<i class="ri-check-line"></i> ' + cfg.i18n.success;

                setTimeout(function () {
                    submitEl.innerHTML = '<i class="ri-send-plane-fill"></i> ' + cfg.i18n.submit;
                    submitEl.disabled = true;
                }, 2000);

                // Update counter
                const countEl = document.querySelector('.depoimentos-count');
                if (countEl) {
                    const current = parseInt(countEl.textContent.replace(/[^\d]/g, ''), 10) || 0;
                    countEl.textContent = '(' + (current + 1) + ')';
                }

            } catch (err) {
                submitEl.innerHTML = '<i class="ri-error-warning-line"></i> ' + cfg.i18n.error;
                setTimeout(function () {
                    submitEl.innerHTML = '<i class="ri-send-plane-fill"></i> ' + cfg.i18n.submit;
                    submitEl.disabled = false;
                }, 3000);
            }
        });
    }

    /* ─── Load More ──────────────────────────────────────────── */
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.depoimento-load-more-btn');
        if (!btn) return;

        const postId = btn.dataset.postId;
        const offset = parseInt(btn.dataset.offset, 10);
        const total  = parseInt(btn.dataset.total, 10);

        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Carregando…';

        try {
            const url = cfg.rest_url + '?post_id=' + postId + '&limit=10&offset=' + offset;
            const res = await fetch(url);
            if (!res.ok) throw new Error('Load failed');

            const data = await res.json();
            const list = document.querySelector('.depoimentos-list');

            if (list && data.depoimentos) {
                data.depoimentos.forEach(function (d) {
                    list.insertAdjacentHTML('beforeend', buildCard(d));
                });
            }

            const newOffset = offset + (data.depoimentos ? data.depoimentos.length : 0);
            if (newOffset >= total) {
                btn.parentElement.remove();
            } else {
                btn.dataset.offset = newOffset;
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-arrow-down-line"></i> Carregar mais depoimentos';
            }

        } catch (err) {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri-arrow-down-line"></i> Tentar novamente';
        }
    });

    /* ─── Build card HTML from REST data ─────────────────────── */
    function buildCard(d) {
        const author = d.author || {};
        const avatar = escAttr(author.avatar || '');
        const name   = escHtml(author.name || 'Anônimo');
        const badge  = author.badge && author.badge.ri_icon
            ? ' <i class="' + escAttr(author.badge.ri_icon) + '" style="color:' + escAttr(author.badge.color || '') + '" title="' + escAttr(author.badge.label || '') + '"></i>'
            : '';
        const groups = (author.groups || []).map(function (g) { return escHtml(g.name); }).join(' · ');
        const url    = author.profile_url ? escAttr(author.profile_url) : '';
        const text   = escHtml(d.content || '');

        return '<div class="depoimento-card">' +
            '<span class="depoimento-avatar">' +
                (url ? '<a href="' + url + '">' : '') +
                '<img src="' + avatar + '" alt="' + escAttr(name) + '" class="depoimento-avatar-img" loading="lazy">' +
                (url ? '</a>' : '') +
            '</span>' +
            '<div class="depoimento-body">' +
                '<p class="depoimento-text">' + text + '</p>' +
                '<div class="depoimento-meta">' +
                    '<p class="depoimento-author">' +
                        (url ? '<a href="' + url + '">' + name + '</a>' : name) +
                        badge +
                    '</p>' +
                    (groups ? '<p class="depoimento-groups">' + groups + '</p>' : '') +
                '</div>' +
            '</div>' +
        '</div>';
    }

    function escHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function escAttr(s) {
        return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

})();
