/**
 * Apollo Users — Profile Page JS
 *
 * SoundCloud player, rating voting, feed tabs, depoimentos, admin voter modal.
 * Reads config from window.apolloProfile set by single-profile.php.
 *
 * @package Apollo\Users
 */

(function () {
    'use strict';

    const P = window.apolloProfile || {};

    // ═══════════════════ TOAST ═══════════════════

    function showToast(msg, type) {
        let toast = document.querySelector('.apollo-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'apollo-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = msg;
        toast.className = 'apollo-toast ' + (type || '');
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // ═══════════════════ RATINGS ═══════════════════

    function initRatings() {
        const canVote = P.isLoggedIn && !P.isOwn;
        const rows = document.querySelectorAll('.rating-row');

        rows.forEach(row => {
            const category = row.dataset.category;
            const emojis = row.querySelectorAll('.rating-emoji');

            if (!canVote) {
                // Display-only: show averages, disable pointer
                emojis.forEach(e => {
                    e.style.cursor = 'default';
                    e.style.pointerEvents = 'none';
                });
                renderAverages();
                return;
            }

            // Interactive voting
            emojis.forEach((emoji, idx) => {
                const score = parseInt(emoji.dataset.score, 10);

                // Hover preview
                emoji.addEventListener('mouseenter', () => {
                    emojis.forEach((e, i) => {
                        e.classList.toggle('filled', i < score);
                    });
                });

                // Reset on leave (back to user's vote)
                row.addEventListener('mouseleave', () => {
                    const current = P.userVotes[category] || 0;
                    emojis.forEach((e, i) => {
                        e.classList.toggle('filled', (i + 1) <= current);
                    });
                });

                // Click to vote
                emoji.addEventListener('click', () => {
                    // Optimistic UI
                    P.userVotes[category] = score;
                    emojis.forEach((e, i) => {
                        e.classList.toggle('filled', (i + 1) <= score);
                    });

                    // AJAX
                    const fd = new FormData();
                    fd.append('action', 'apollo_submit_rating');
                    fd.append('nonce', P.nonce);
                    fd.append('target_id', P.targetId);
                    fd.append('category', category);
                    fd.append('score', score);

                    fetch(P.ajaxUrl, { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                P.averages = res.data.averages;
                                P.userVotes = res.data.user_votes;
                                showToast('Voto registrado!', 'success');
                            } else {
                                showToast(res.data?.message || 'Erro', 'error');
                            }
                        })
                        .catch(() => showToast('Erro de rede', 'error'));
                });
            });
        });
    }

    function renderAverages() {
        const rows = document.querySelectorAll('.rating-row');
        rows.forEach(row => {
            const cat = row.dataset.category;
            const avg = P.averages[cat] || 0;
            const emojis = row.querySelectorAll('.rating-emoji');
            emojis.forEach((e, i) => {
                e.classList.toggle('filled', (i + 1) <= Math.round(avg));
            });
        });
    }

    // ═══════════════════ FEED TABS ═══════════════════

    function initFeedTabs() {
        const tabs = document.querySelectorAll('.feed-tab');
        const cards = document.querySelectorAll('.f-card');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const filter = tab.dataset.filter;
                cards.forEach(card => {
                    if (filter === 'all' || card.dataset.type === filter) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    }

    // ═══════════════════ DEPOIMENTOS ═══════════════════

    function initDepoimentos() {
        const form = document.getElementById('depo-submit-form');
        if (form) {
            form.addEventListener('submit', e => {
                e.preventDefault();
                const text = document.getElementById('depo-text').value.trim();
                if (!text) return;

                const fd = new FormData();
                fd.append('action', 'apollo_submit_depoimento');
                fd.append('nonce', P.nonce);
                fd.append('target_id', P.targetId);
                fd.append('text', text);

                fetch(P.ajaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            showToast('Depoimento enviado!', 'success');
                            form.style.display = 'none';
                            // Prepend new card
                            const grid = document.getElementById('depo-grid');
                            const empty = grid.querySelector('.depo-empty');
                            if (empty) empty.remove();
                            grid.insertAdjacentHTML('afterbegin', res.data.html);
                        } else {
                            showToast(res.data?.message || 'Erro', 'error');
                        }
                    })
                    .catch(() => showToast('Erro de rede', 'error'));
            });
        }

        // Delete buttons
        document.addEventListener('click', e => {
            const btn = e.target.closest('.depo-delete-btn');
            if (!btn) return;
            if (!confirm('Remover este depoimento?')) return;

            const depoId = btn.dataset.depoId;
            const fd = new FormData();
            fd.append('action', 'apollo_delete_depoimento');
            fd.append('nonce', P.nonce);
            fd.append('depo_id', depoId);

            fetch(P.ajaxUrl, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const card = btn.closest('.depo-card');
                        if (card) card.remove();
                        showToast('Depoimento removido.', 'success');
                    } else {
                        showToast(res.data?.message || 'Erro', 'error');
                    }
                });
        });
    }

    // ═══════════════════ SOUNDCLOUD PLAYER ═══════════════════

    function initSCPlayer() {
        const iframe = document.getElementById('sc-widget');
        if (!iframe || typeof SC === 'undefined' || !SC.Widget) {
            // Retry if API not loaded yet
            if (iframe) setTimeout(initSCPlayer, 500);
            return;
        }

        const widget = SC.Widget(iframe);
        const playBtn = document.getElementById('play-btn');
        const playIcon = document.getElementById('play-icon');
        const progressBar = document.getElementById('progress-bar');
        const progressTrack = document.getElementById('progress-track');
        const timeDisplay = document.getElementById('time-display');
        let duration = 0;

        widget.bind(SC.Widget.Events.READY, () => {
            widget.getDuration(d => { duration = d; });
            widget.getCurrentSound(sound => {
                if (sound) {
                    const nameEl = document.getElementById('track-name');
                    const artistEl = document.getElementById('track-artist');
                    if (sound.title && nameEl) nameEl.textContent = sound.title;
                    if (sound.user?.username && artistEl) artistEl.textContent = sound.user.username;
                }
            });
        });

        widget.bind(SC.Widget.Events.PLAY, () => {
            if (playIcon) playIcon.textContent = 'pause';
            if (playBtn) playBtn.classList.add('is-playing');
        });
        widget.bind(SC.Widget.Events.PAUSE, () => {
            if (playIcon) playIcon.textContent = 'play_arrow';
            if (playBtn) playBtn.classList.remove('is-playing');
        });
        widget.bind(SC.Widget.Events.FINISH, () => {
            if (playIcon) playIcon.textContent = 'play_arrow';
            if (playBtn) playBtn.classList.remove('is-playing');
            if (progressBar) progressBar.style.width = '0%';
            if (timeDisplay) timeDisplay.textContent = '0:00';
        });

        // Progress polling
        setInterval(() => {
            if (!duration) return;
            try {
                widget.getPosition(pos => {
                    if (progressBar) progressBar.style.width = (pos / duration * 100) + '%';
                    if (timeDisplay) {
                        const m = Math.floor(pos / 60000);
                        const s = Math.floor((pos % 60000) / 1000);
                        timeDisplay.textContent = m + ':' + (s < 10 ? '0' : '') + s;
                    }
                });
            } catch (e) {}
        }, 200);

        if (playBtn) {
            playBtn.addEventListener('click', () => widget.toggle());
        }
        if (progressTrack) {
            progressTrack.addEventListener('click', e => {
                if (!duration) return;
                const rect = progressTrack.getBoundingClientRect();
                widget.seekTo(duration * ((e.clientX - rect.left) / rect.width));
            });
        }
    }

    // ═══════════════════ ADMIN MODAL ═══════════════════

    function initAdminModal() {
        if (!P.isAdmin) return;

        const btn = document.querySelector('.admin-voters-btn');
        const modal = document.getElementById('admin-voters-modal');
        if (!btn || !modal) return;

        const backdrop = modal.querySelector('.admin-modal__backdrop');
        const closeBtn = modal.querySelector('.admin-modal__close');
        const body = document.getElementById('admin-voters-list');

        function close() { modal.style.display = 'none'; }
        backdrop.addEventListener('click', close);
        closeBtn.addEventListener('click', close);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'block') close();
        });

        btn.addEventListener('click', () => {
            modal.style.display = 'block';
            body.innerHTML = '<p style="text-align:center;color:var(--txt-muted);">Carregando…</p>';

            fetch(P.ajaxUrl + '?action=apollo_admin_get_voters&target_id=' + P.targetId)
                .then(r => r.json())
                .then(res => {
                    if (!res.success || !res.data.voters.length) {
                        body.innerHTML = '<p style="text-align:center;color:var(--txt-muted);">Nenhum voto registrado.</p>';
                        return;
                    }
                    let html = '';
                    res.data.voters.forEach(v => {
                        html += `<div class="admin-vote-row">
                            <span class="admin-vote-user">${v.display_name || v.user_login} <small style="color:var(--gray-8);">(@${v.user_login})</small></span>
                            <span class="admin-vote-cat">${v.category}</span>
                            <span class="admin-vote-score">${v.score}/${P.maxScore}</span>
                            <div class="admin-vote-actions">
                                <select data-rid="${v.id}">
                                    ${[0,1,2,3].map(s => `<option value="${s}" ${parseInt(v.score)===s?'selected':''}>${s}</option>`).join('')}
                                </select>
                                <button data-rid="${v.id}" class="admin-del-vote" title="Remover voto">✕</button>
                            </div>
                        </div>`;
                    });
                    body.innerHTML = html;

                    // Adjust handlers
                    body.querySelectorAll('select').forEach(sel => {
                        sel.addEventListener('change', () => {
                            const fd = new FormData();
                            fd.append('action', 'apollo_admin_adjust_rating');
                            fd.append('nonce', P.adminNonce);
                            fd.append('rating_id', sel.dataset.rid);
                            fd.append('score', sel.value);
                            fetch(P.ajaxUrl, { method: 'POST', body: fd })
                                .then(r => r.json())
                                .then(r => showToast(r.success ? 'Atualizado' : 'Erro', r.success ? 'success' : 'error'));
                        });
                    });

                    // Delete handlers
                    body.querySelectorAll('.admin-del-vote').forEach(b => {
                        b.addEventListener('click', () => {
                            if (!confirm('Remover este voto?')) return;
                            const fd = new FormData();
                            fd.append('action', 'apollo_admin_delete_rating');
                            fd.append('nonce', P.adminNonce);
                            fd.append('rating_id', b.dataset.rid);
                            fetch(P.ajaxUrl, { method: 'POST', body: fd })
                                .then(r => r.json())
                                .then(r => {
                                    if (r.success) {
                                        b.closest('.admin-vote-row').remove();
                                        showToast('Voto removido', 'success');
                                    }
                                });
                        });
                    });
                });
        });
    }

    // ═══════════════════ INTERSECTION OBSERVER ═══════════════════

    function initScrollAnimations() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeUp 0.6s ease-out forwards';
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.f-card, .depo-card').forEach(el => {
            el.style.opacity = '0';
            observer.observe(el);
        });
    }

    // ═══════════════════ INIT ═══════════════════

    document.addEventListener('DOMContentLoaded', () => {
        initRatings();
        initFeedTabs();
        initDepoimentos();
        initSCPlayer();
        initAdminModal();
        initScrollAnimations();
    });
})();
