/**
 * Apollo Chat v2.0 — Premium Instant Messaging Engine
 *
 * Features:
 *  - Real-time AJAX polling (3s) with BroadcastChannel cross-tab sync
 *  - Typing indicators with 3s debounce
 *  - Read receipts (sent → delivered → read)
 *  - File/image/audio/video attachments (drag-drop + clipboard paste)
 *  - GIF search & send (Tenor API)
 *  - Emoji picker (native Unicode grid)
 *  - Message reactions (toggle emoji)
 *  - Message editing & soft-deletion
 *  - Reply-to (quote) threading
 *  - User presence / online heartbeat
 *  - Message search with highlight
 *  - Sound + browser push notifications
 *  - BroadcastChannel for cross-tab dedup
 *  - Scroll-up (infinite) pagination
 *  - Group conversations management
 *  - User blocking
 *  - Context menu on right-click
 *  - Image lightbox
 *  - Toast notifications
 *  - Mobile-first responsive behaviour
 *  - Dark mode awareness
 *
 * @package Apollo\Chat
 */

/* eslint-disable no-unused-vars */
;(function () {
    'use strict';

    /* ═══════════════════════════════════════════════════════════════════
       CONFIGURATION
       ═══════════════════════════════════════════════════════════════════ */

    const CFG = window.ApolloChat || {};

    // Normalize URLs: strip any origin to get path-only, then prepend current origin.
    // This guarantees API calls always hit the same host the browser loaded.
    function normalizeUrl(url, fallback) {
        if (!url) return fallback;
        try {
            const u = new URL(url, window.location.origin);
            return u.pathname;  // path only, no origin
        } catch (_e) {
            return url.startsWith('/') ? url : '/' + url;
        }
    }

    const REST       = normalizeUrl(CFG.rest_url, '/wp-json/apollo/v1/chat');
    const AJAX_URL   = normalizeUrl(CFG.ajax_url, '/wp-admin/admin-ajax.php');
    const NONCE      = CFG.nonce     || '';
    const MY_ID      = parseInt(CFG.user_id, 10) || 0;
    const MY_NAME    = CFG.user_name || '';
    const MY_AVATAR  = CFG.user_avatar || '';
    const INITIAL_TID = parseInt(CFG.thread_id, 10) || 0;
    const USERS_URL   = normalizeUrl(CFG.users_url, '/wp-json/wp/v2/users');
    const POLL_MS     = 3000;
    const TYPING_DEBOUNCE = 3000;
    const PRESENCE_MS = 60000;
    const EDIT_WINDOW_MS = 15 * 60 * 1000;  // 15 min

    /* ═══════════════════════════════════════════════════════════════════
       STATE
       ═══════════════════════════════════════════════════════════════════ */

    let activeThreadId  = 0;
    let activeThreadMeta = null;
    let threads         = [];
    let messages        = [];
    let pollTimer       = null;
    let presenceTimer   = null;
    let lastPollTS      = '';
    let replyTo         = null;   // { id, sender_name, text }
    let pendingGif      = null;   // { url, preview, title }
    let emojiPickerOpen = false;
    let searchOpen      = false;
    let contextMsg      = null;   // message object for context menu
    let typingSent      = false;
    let typingTimer     = null;
    let oldestMsgId     = 0;
    let loadingOlder    = false;
    let hasMoreOlder    = true;
    let isMobile        = window.innerWidth <= 768;
    let notifSound      = null;
    let notifPermission = 'default';
    let bc              = null;   // BroadcastChannel
    let _msgLoadingFX   = null;   // ApolloTextFX.loading() handle — message area
    let _gifLoadingFX   = null;   // ApolloTextFX.loading() handle — GIF grid

    /* ═══════════════════════════════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════════════════════════════ */

    const $ = (sel, ctx) => (ctx || document).querySelector(sel);
    const $$ = (sel, ctx) => [...(ctx || document).querySelectorAll(sel)];

    function headers(json) {
        const h = { 'X-WP-Nonce': NONCE };
        if (json) h['Content-Type'] = 'application/json';
        return h;
    }

    async function api(path, opts = {}) {
        const url = REST + path;
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: headers(opts.json !== false && opts.method && opts.method !== 'GET'),
            ...opts,
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            throw new Error(err.error || err.message || `HTTP ${res.status}`);
        }
        return res.json();
    }

    function esc(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.textContent = String(s);
        return d.innerHTML;
    }

    /** Apollo standard time-ago HTML block. Input: '53min' → icon+spans. */
    function tempoHTML(str) {
        if (!str) return '';
        const m = String(str).match(/^(\d+)([a-z]+)$/i);
        const num  = m ? m[1] : str;
        const unit = m ? m[2] : '';
        return '<i class="tempo-v"></i>\u00a0<span class="time-ago">' + num + '</span><span class="when-ago">' + unit + '</span>';
    }

    function nl2br(s) {
        return esc(s).replace(/\n/g, '<br>');
    }

    function linkify(text) {
        const escaped = esc(text);
        return escaped.replace(
            /(https?:\/\/[^\s<]+)/g,
            '<a href="$1" target="_blank" rel="noopener">$1</a>'
        ).replace(/\n/g, '<br>');
    }

    function timeAgo(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr.replace(' ', 'T') + 'Z');
        const diff = (Date.now() - d.getTime()) / 1000;
        if (diff < 60) return 'agora';
        if (diff < 3600) return Math.floor(diff / 60) + 'min';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        if (diff < 2592000) return Math.floor(diff / 86400) + 'd';
        return d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr.replace(' ', 'T') + 'Z');
        return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr.replace(' ', 'T') + 'Z');
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        if (d.toDateString() === today.toDateString()) return 'Hoje';
        if (d.toDateString() === yesterday.toDateString()) return 'Ontem';
        return d.toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function debounce(fn, ms) {
        let t;
        return function (...a) { clearTimeout(t); t = setTimeout(() => fn.apply(this, a), ms); };
    }

    /* ═══════════════════════════════════════════════════════════════════
       TOAST NOTIFICATIONS
       ═══════════════════════════════════════════════════════════════════ */

    function toast(text, icon = 'ri-chat-1-line') {
        const container = $('.ac-toast-container');
        if (!container) return;
        const t = document.createElement('div');
        t.className = 'ac-toast';
        t.innerHTML = `<i class="ac-toast-icon ${esc(icon)}"></i><span class="ac-toast-text">${esc(text)}</span>`;
        container.appendChild(t);
        setTimeout(() => {
            t.classList.add('removing');
            t.addEventListener('animationend', () => t.remove());
        }, 3500);
    }

    /* ═══════════════════════════════════════════════════════════════════
       SOUND & BROWSER NOTIFICATIONS
       ═══════════════════════════════════════════════════════════════════ */

    function initNotifications() {
        // Build a small audio beep via AudioContext if available
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (AudioCtx) {
                const ctx = new AudioCtx();
                notifSound = () => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.value = 880;
                    gain.gain.value = 0.08;
                    osc.start();
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
                    osc.stop(ctx.currentTime + 0.3);
                };
            }
        } catch (_e) { /* no audio */ }

        if ('Notification' in window) {
            notifPermission = Notification.permission;
            if (notifPermission === 'default') {
                Notification.requestPermission().then(p => { notifPermission = p; });
            }
        }
    }

    function playNotifSound() {
        if (notifSound) {
            try { notifSound(); } catch (_e) { /* swallow */ }
        }
    }

    function showBrowserNotif(title, body, threadId) {
        if (notifPermission !== 'granted') return;
        if (document.hasFocus()) return;
        try {
            const n = new Notification(title, { body, icon: MY_AVATAR, tag: 'ac-' + threadId });
            n.onclick = () => { window.focus(); openThread(threadId); n.close(); };
            setTimeout(() => n.close(), 5000);
        } catch (_e) { /* swallow */ }
    }

    /* ═══════════════════════════════════════════════════════════════════
       BROADCASTCHANNEL — Cross-tab Sync
       ═══════════════════════════════════════════════════════════════════ */

    function initBroadcastChannel() {
        if (!('BroadcastChannel' in window)) return;
        bc = new BroadcastChannel('apollo-chat-' + MY_ID);
        bc.onmessage = (ev) => {
            const { type, data } = ev.data || {};
            if (type === 'thread-opened' && data.threadId && data.threadId === activeThreadId) {
                // Another tab opened same thread, reload
                loadMessages(activeThreadId);
            }
            if (type === 'message-sent') {
                loadThreadList();
                if (data.threadId === activeThreadId) {
                    loadMessages(activeThreadId);
                }
            }
            if (type === 'thread-deleted') {
                loadThreadList();
                if (data.threadId === activeThreadId) {
                    activeThreadId = 0;
                    renderEmptyMain();
                }
            }
        };
    }

    function bcSend(type, data = {}) {
        if (bc) try { bc.postMessage({ type, data }); } catch (_e) { /* ok */ }
    }

    /* ═══════════════════════════════════════════════════════════════════
       THREAD LIST
       ═══════════════════════════════════════════════════════════════════ */

    async function loadThreadList() {
        try {
            threads = await api('/threads');
            renderThreadList();
        } catch (e) {
            console.error('[ApolloChat] loadThreadList:', e);
        }
    }

    function renderThreadList() {
        const list = $('.ac-thread-list');
        if (!list) return;

        if (!threads.length) {
            list.innerHTML =
                '<div class="ac-thread-empty">' +
                    '<i class="ri-chat-new-line"></i>' +
                    '<p>Nenhuma conversa</p>' +
                '</div>';
            return;
        }

        list.innerHTML = threads.map(t => {
            const tid    = t.thread_id || t.id;
            const active = tid == activeThreadId ? ' active' : '';
            const unread = t.unread_count > 0 ? ' unread' : '';
            const muted  = t.is_muted && parseInt(t.is_muted, 10) === 1 ? ' muted' : '';
            const isGrp  = t.is_group;
            const online = !isGrp && t.is_online;
            const onlineDot = online ? '<span class="ac-online-dot"></span>' : '';

            // Avatar
            let avatarHtml;
            if (isGrp && t.participants && t.participants.length >= 2 && !t.avatar_url) {
                const p = t.participants;
                avatarHtml = `<div class="ac-group-avatar">
                    <img src="${esc(p[0].avatar_url || '')}" alt="">
                    <img src="${esc(p[1].avatar_url || '')}" alt="">
                </div>`;
            } else {
                avatarHtml = `<div class="ac-thread-avatar">
                    <img src="${esc(t.avatar_url || '')}" alt="">
                    ${onlineDot}
                </div>`;
            }

            // Badge
            const badge = t.unread_count > 0
                ? `<span class="ac-badge">${t.unread_count > 99 ? '99+' : t.unread_count}</span>`
                : '';

            // Preview
            const prevSender = t.preview_sender ? `<strong>${esc(t.preview_sender)}:</strong> ` : '';
            const prevText = esc(t.preview || t.last_message || t.subject || '');

            return `<div class="ac-thread${active}${unread}${muted}" data-tid="${tid}">
                ${avatarHtml}
                <div class="ac-thread-body">
                    <div class="ac-thread-row">
                        <span class="ac-thread-name">${esc(t.display_name || 'Conversa')}</span>
                        <span class="ac-thread-time">${tempoHTML(t.time_ago || '')}</span>
                    </div>
                    <div class="ac-thread-meta">
                        <span class="ac-thread-preview">${prevSender}${prevText}</span>
                        ${badge}
                    </div>
                </div>
            </div>`;
        }).join('');

        // Click handlers
        $$('.ac-thread', list).forEach(el => {
            el.addEventListener('click', () => {
                const tid = parseInt(el.dataset.tid, 10);
                if (tid) openThread(tid);
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════════════
       OPEN THREAD
       ═══════════════════════════════════════════════════════════════════ */

    async function openThread(threadId) {
        if (!threadId) return;
        activeThreadId = threadId;
        replyTo = null;
        pendingGif = null;
        hasMoreOlder = true;
        oldestMsgId = 0;

        // Highlight in sidebar
        $$('.ac-thread').forEach(el => el.classList.toggle('active', parseInt(el.dataset.tid, 10) === threadId));

        // Mobile: hide sidebar, show main
        if (isMobile) {
            const sidebar = $('.ac-sidebar');
            const main = $('.ac-main');
            if (sidebar) sidebar.classList.add('hidden');
            if (main) main.style.display = 'flex';
        }

        // Show loading
        const msgArea = $('.ac-messages');
        if (msgArea) {
            if (_msgLoadingFX) { _msgLoadingFX.stop(); _msgLoadingFX = null; }
            msgArea.innerHTML = '<div class="ac-loading"><p class="ac-loading-txt" aria-live="polite">Carregando mensagens...</p></div>';
            if (typeof ApolloTextFX !== 'undefined') {
                _msgLoadingFX = ApolloTextFX.loading(
                    msgArea.querySelector('.ac-loading-txt'),
                    [
                        'Carregando mensagens...', 
                        'Carregando histórico...',
                        'Sincronizando chat...',
                        'Quase lá...'
                    ]
                );
            }
        }

        // Show header + compose
        const header = $('.ac-chat-header');
        const compose = $('.ac-compose');
        if (header) header.style.display = '';
        if (compose) compose.style.display = '';

        try {
            const data = await api(`/threads/${threadId}`);
            messages = data.messages || [];
            activeThreadMeta = data.meta || {};

            renderChatHeader();
            if (_msgLoadingFX) { _msgLoadingFX.stop(); _msgLoadingFX = null; }
            renderMessages();
            scrollToBottom();

            // Mark as read
            api(`/threads/${threadId}/read`, { method: 'POST', body: '{}' }).catch(() => {});

            // Clear unread badge in sidebar
            const threadEl = $(`.ac-thread[data-tid="${threadId}"]`);
            if (threadEl) {
                threadEl.classList.remove('unread');
                const badge = $('.ac-badge', threadEl);
                if (badge) badge.remove();
            }

            // Focus input
            const input = $('.ac-compose-input');
            if (input && !isMobile) input.focus();

            // Update URL without reload
            if (window.history.replaceState) {
                window.history.replaceState(null, '', '/mensagens/' + threadId);
            }

            // BroadcastChannel notify
            bcSend('thread-opened', { threadId });

        } catch (e) {
            console.error('[ApolloChat] openThread:', e);
            if (msgArea) msgArea.innerHTML = '<div class="ac-empty-chat"><i class="ri-error-warning-line"></i><p>Erro ao carregar conversa</p></div>';
        }
    }

    async function loadMessages(threadId) {
        if (!threadId || threadId !== activeThreadId) return;
        try {
            const data = await api(`/threads/${threadId}`);
            messages = data.messages || [];
            activeThreadMeta = data.meta || {};
            renderMessages();
            scrollToBottom();
        } catch (_e) { /* swallow */ }
    }

    /* ═══════════════════════════════════════════════════════════════════
       CHAT HEADER
       ═══════════════════════════════════════════════════════════════════ */

    function renderChatHeader() {
        const header = $('.ac-chat-header');
        if (!header) return;

        // Find thread in list
        const t = threads.find(th => (th.thread_id || th.id) == activeThreadId) || {};
        const meta = activeThreadMeta || {};
        const name = t.display_name || meta.subject || 'Conversa';
        const avatar = t.avatar_url || '';
        const isOnline = !t.is_group && t.is_online;
        const statusText = t.is_group
            ? `${t.member_count || ''} participantes`
            : (isOnline ? 'Online' : 'Offline');
        const statusClass = isOnline ? ' online' : '';

        header.innerHTML = `
            <button class="ac-icon-btn ac-back-btn" title="Voltar"><span class="navbar-highlighted"><i class="ri-arrow-left-line"></i></span></button>
            <div class="ac-header-avatar">
                <img src="${esc(avatar)}" alt="">
                ${isOnline ? '<span class="ac-online-dot"></span>' : ''}
            </div>
            <div class="ac-header-info">
                <div class="ac-header-name">${esc(name)}</div>
                <div class="ac-header-status${statusClass}">${esc(statusText)}</div>
            </div>
            <div class="ac-header-actions">
                <button class="ac-icon-btn" data-action="search" title="Buscar"><span class="navbar-highlighted"><i class="ri-search-line"></i></span></button>
                ${t.is_group ? '<button class="ac-icon-btn" data-action="members" title="Membros"><span class="navbar-highlighted"><i class="ri-group-line"></i></span></button>' : ''}
                <button class="ac-icon-btn" data-action="more" title="Mais opções"><span class="navbar-highlighted"><i class="ri-more-2-fill"></i></span></button>
            </div>`;

        // Back button (mobile)
        const backBtn = $('.ac-back-btn', header);
        if (backBtn) {
            backBtn.addEventListener('click', () => {
                activeThreadId = 0;
                const sidebar = $('.ac-sidebar');
                if (sidebar) sidebar.classList.remove('hidden');
                if (isMobile) {
                    const main = $('.ac-main');
                    if (main) main.style.display = '';
                }
                if (window.history.replaceState) {
                    window.history.replaceState(null, '', '/mensagens');
                }
            });
        }

        // Action buttons
        $$('[data-action]', header).forEach(btn => {
            btn.addEventListener('click', () => {
                const action = btn.dataset.action;
                if (action === 'search') toggleSearch();
                if (action === 'members') openMembersModal();
                if (action === 'more') openThreadMenu();
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════════════
       RENDER MESSAGES
       ═══════════════════════════════════════════════════════════════════ */

    function renderMessages() {
        const area = $('.ac-messages');
        if (!area) return;

        if (!messages.length) {
            area.innerHTML = '<div class="ac-empty-chat"><i class="ri-chat-smile-2-line"></i><p>Envie a primeira mensagem!</p></div>';
            return;
        }

        let html = '';
        let lastDate = '';

        // Track oldest message for scroll-up pagination
        if (messages.length) {
            oldestMsgId = parseInt(messages[0].id, 10);
        }

        messages.forEach((m, idx) => {
            const msgDate = formatDate(m.created_at);
            if (msgDate !== lastDate) {
                html += `<div class="ac-date-sep"><span>${esc(msgDate)}</span></div>`;
                lastDate = msgDate;
            }
            html += renderMessageBubble(m);
        });

        // Typing indicator (GSAP-powered text animation)
        html += `<div class="ac-typing-indicator" id="ac-typing" aria-live="polite" aria-label="Usuário digitando">
            <span class="ac-typing-text">digitando</span>
            <span class="ac-typing-dots" aria-hidden="true">
                <span class="ac-dot"></span><span class="ac-dot"></span><span class="ac-dot"></span>
            </span>
        </div>`;

        area.innerHTML = html;

        // Attach bubble event listeners
        bindMessageEvents(area);
    }

    function renderMessageBubble(m) {
        const isMine = m.is_mine || parseInt(m.sender_id, 10) === MY_ID;
        const dir    = isMine ? 'sent' : 'received';
        const mid    = m.id;
        const deleted = m.is_deleted && parseInt(m.is_deleted, 10) === 1;

        // Avatar (received only)
        const avatarHtml = !isMine
            ? `<div class="ac-msg-avatar"><img src="${esc(m.avatar_url || '')}" alt=""></div>`
            : '';

        // Sender name (received in groups)
        const isGroup = activeThreadMeta && activeThreadMeta.is_group;
        const senderHtml = (!isMine && isGroup)
            ? `<div class="ac-msg-sender">${esc(m.sender_name || m.display_name || '')}</div>`
            : '';

        // Deleted
        if (deleted) {
            return `<div class="ac-msg-row ${dir}" data-mid="${mid}">
                ${avatarHtml}
                <div class="ac-bubble">
                    ${senderHtml}
                    <div class="ac-msg-content ac-msg-deleted"><i class="ri-forbid-line"></i> Mensagem apagada</div>
                    <div class="ac-msg-footer">
                        <span class="ac-msg-time">${formatTime(m.created_at)}</span>
                    </div>
                </div>
            </div>`;
        }

        // Reply-to quote — PHP keys: {id, sender, sender_id, preview, type}
        let replyHtml = '';
        if (m.reply_to_preview) {
            const rp = m.reply_to_preview;
            replyHtml = `<div class="ac-reply-quote" data-goto-msg="${rp.id || ''}">
                <div class="ac-reply-sender">${esc(rp.sender_name || rp.sender || '')}</div>
                <div class="ac-reply-text">${esc(rp.message || rp.preview || '')}</div>
            </div>`;
        }

        // Message content
        let contentHtml = `<div class="ac-msg-content">${linkify(m.message || '')}</div>`;

        // Edited tag
        const editedTag = m.is_edited ? '<span class="ac-edited-tag">editado</span>' : '';

        // GIF or legacy attachment
        let attachHtml = '';
        if (m.message_type === 'gif' && m.message) {
            // GIF message — the message field contains the GIF URL
            attachHtml = `<div class="ac-msg-gif"><img src="${esc(m.message)}" alt="GIF" loading="lazy" data-full="${esc(m.message)}"></div>`;
            contentHtml = ''; // don't show URL as text
        } else if (m.attachment) {
            // Legacy: old attachment messages (backward-compat for existing data)
            const att = m.attachment;
            if (att.file_type === 'image' || (att.mime_type && att.mime_type.startsWith('image/'))) {
                attachHtml = `<div class="ac-msg-image"><img src="${esc(att.file_url || att.url)}" alt="${esc(att.file_name)}" data-full="${esc(att.file_url || att.url)}" loading="lazy"></div>`;
            } else if (att.file_type === 'audio' || (att.mime_type && att.mime_type.startsWith('audio/'))) {
                attachHtml = `<div class="ac-msg-audio"><audio controls preload="none"><source src="${esc(att.file_url || att.url)}" type="${esc(att.mime_type)}"></audio></div>`;
            } else if (att.file_type === 'video' || (att.mime_type && att.mime_type.startsWith('video/'))) {
                attachHtml = `<div class="ac-msg-video"><video controls preload="none"><source src="${esc(att.file_url || att.url)}" type="${esc(att.mime_type)}"></video></div>`;
            } else {
                attachHtml = `<a class="ac-msg-file" href="${esc(att.file_url || att.url)}" target="_blank" rel="noopener">
                    <i class="ri-file-text-line"></i>
                    <div>
                        <div class="ac-file-name">${esc(att.file_name || 'Arquivo')}</div>
                        <div class="ac-file-size">${formatBytes(att.file_size || 0)}</div>
                    </div>
                </a>`;
            }
        }

        // Read receipt (sent messages only)
        let receiptHtml = '';
        if (isMine) {
            receiptHtml = '<span class="ac-receipt"><i class="ri-check-double-line"></i></span>';
        }

        // Reactions — normalize: PHP may return array [{emoji,count,users}] or object {emoji: {count,users}}
        let reactionsHtml = '';
        const reactList = Array.isArray(m.reactions) ? m.reactions : Object.values(m.reactions || {});
        if (reactList.length) {
            const rItems = reactList.map(r => {
                const emoji = r.emoji || '';
                const count = r.count || 0;
                const userIds = (r.users || []).map(u => typeof u === 'object' ? parseInt(u.id, 10) : parseInt(u, 10));
                const mine = userIds.includes(MY_ID) ? ' mine' : '';
                return `<span class="ac-reaction${mine}" data-emoji="${esc(emoji)}" data-mid="${mid}">${emoji} <span class="count">${count > 1 ? count : ''}</span></span>`;
            }).join('');
            reactionsHtml = `<div class="ac-reactions">${rItems}</div>`;
        }

        // Message actions (hover toolbar)
        const canEdit = isMine && m.created_at && (Date.now() - new Date(m.created_at.replace(' ', 'T') + 'Z').getTime()) < EDIT_WINDOW_MS;
        const actionsHtml = `<div class="ac-msg-actions">
            <button data-act="react" title="Reagir"><i class="ri-emotion-line"></i></button>
            <button data-act="reply" title="Responder"><i class="ri-reply-line"></i></button>
            ${canEdit ? '<button data-act="edit" title="Editar"><i class="ri-pencil-line"></i></button>' : ''}
            ${isMine ? '<button data-act="delete" title="Apagar"><i class="ri-delete-bin-line"></i></button>' : ''}
        </div>`;

        return `<div class="ac-msg-row ${dir}" data-mid="${mid}">
            ${avatarHtml}
            <div class="ac-bubble">
                ${senderHtml}
                ${replyHtml}
                ${attachHtml}
                ${contentHtml}
                ${editedTag}
                <div class="ac-msg-footer">
                    <span class="ac-msg-time">${formatTime(m.created_at)}</span>
                    ${receiptHtml}
                </div>
                ${actionsHtml}
            </div>
            ${reactionsHtml}
        </div>`;
    }

    function bindMessageEvents(area) {
        // Message action buttons
        $$('[data-act]', area).forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const mid = parseInt(btn.closest('.ac-msg-row').dataset.mid, 10);
                const msg = messages.find(m => parseInt(m.id, 10) === mid);
                if (!msg) return;

                const act = btn.dataset.act;
                if (act === 'reply') setReplyTo(msg);
                if (act === 'edit') promptEditMessage(msg);
                if (act === 'delete') confirmDeleteMessage(msg);
                if (act === 'react') showQuickReact(btn, msg);
            });
        });

        // Reaction toggle
        $$('.ac-reaction', area).forEach(el => {
            el.addEventListener('click', () => {
                const mid = parseInt(el.dataset.mid, 10);
                const emoji = el.dataset.emoji;
                if (mid && emoji) toggleReaction(mid, emoji);
            });
        });

        // Reply-quote click (scroll to original message)
        $$('.ac-reply-quote', area).forEach(el => {
            el.addEventListener('click', () => {
                const goto = el.dataset.gotoMsg;
                if (goto) {
                    const target = $(`.ac-msg-row[data-mid="${goto}"]`);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        target.classList.add('highlight');
                        setTimeout(() => target.classList.remove('highlight'), 1500);
                    }
                }
            });
        });

        // Image lightbox
        $$('.ac-msg-image img', area).forEach(img => {
            img.addEventListener('click', () => openLightbox(img.dataset.full || img.src));
        });

        // Context menu (right-click)
        $$('.ac-msg-row', area).forEach(row => {
            row.addEventListener('contextmenu', (e) => {
                e.preventDefault();
                const mid = parseInt(row.dataset.mid, 10);
                contextMsg = messages.find(m => parseInt(m.id, 10) === mid);
                if (contextMsg) showContextMenu(e.clientX, e.clientY, contextMsg);
            });
        });

        // Avatar click → user info for received messages
        $$('.ac-msg-row.received .ac-msg-avatar img', area).forEach(img => {
            img.style.cursor = 'pointer';
            img.addEventListener('click', (e) => {
                e.stopPropagation();
                const row = img.closest('.ac-msg-row');
                const mid = parseInt(row.dataset.mid, 10);
                const msg = messages.find(m => parseInt(m.id, 10) === mid);
                if (msg) {
                    openUserInfoPanel(
                        parseInt(msg.sender_id, 10),
                        msg.sender_name || msg.display_name || '',
                        msg.avatar_url || ''
                    );
                }
            });
        });
    }

    function scrollToBottom(smooth) {
        const area = $('.ac-messages');
        if (!area) return;
        if (smooth) {
            area.scrollTo({ top: area.scrollHeight, behavior: 'smooth' });
        } else {
            area.scrollTop = area.scrollHeight;
        }
    }

    function renderEmptyMain() {
        const msgArea = $('.ac-messages');
        if (msgArea) {
            msgArea.innerHTML = '<div class="ac-empty-chat"><i class="ri-message-3-line"></i><p>Selecione uma conversa</p></div>';
        }
        const header = $('.ac-chat-header');
        const compose = $('.ac-compose');
        if (header) header.style.display = 'none';
        if (compose) compose.style.display = 'none';
    }

    /* ═══════════════════════════════════════════════════════════════════
       SEND MESSAGE
       ═══════════════════════════════════════════════════════════════════ */

    async function sendMessage() {
        const input = $('.ac-compose-input');
        if (!input) return;
        const text = input.value.trim();
        if (!text && !pendingGif) return;
        if (!activeThreadId) return;

        const msgBody = {
            thread_id: activeThreadId,
            message: pendingGif ? pendingGif.url : text,
        };
        if (replyTo) msgBody.reply_to_id = replyTo.id;
        if (pendingGif) {
            msgBody.type = 'gif';
        }

        // Optimistic render
        const tempId = 'temp-' + Date.now();
        const optimistic = {
            id: tempId,
            thread_id: activeThreadId,
            sender_id: MY_ID,
            sender_name: MY_NAME,
            message: pendingGif ? pendingGif.url : text,
            message_type: pendingGif ? 'gif' : 'text',
            is_mine: true,
            is_deleted: 0,
            is_edited: false,
            created_at: new Date().toISOString().replace('T', ' ').substring(0, 19),
            avatar_url: MY_AVATAR,
            reactions: {},
            reply_to_preview: replyTo ? { id: replyTo.id, sender_name: replyTo.sender_name, sender: replyTo.sender_name, message: replyTo.text, preview: replyTo.text } : null,
            attachment: null,
        };

        messages.push(optimistic);
        const area = $('.ac-messages');
        if (area) {
            // Remove typing indicator temporarily, append message, re-add
            const typing = $('#ac-typing', area);
            const msgHtml = renderMessageBubble(optimistic);
            if (typing) typing.insertAdjacentHTML('beforebegin', msgHtml);
            else area.insertAdjacentHTML('beforeend', msgHtml);
            bindMessageEvents(area);
        }
        scrollToBottom(true);

        // Clear input state
        input.value = '';
        input.style.height = '';
        clearReplyTo();
        clearGifPreview();

        // Stop typing
        sendTyping(false);

        try {
            const resp = await api('/send', {
                method: 'POST',
                body: JSON.stringify(msgBody),
            });

            // Replace temp ID
            const tempRow = $(`.ac-msg-row[data-mid="${tempId}"]`);
            if (tempRow && resp.thread_id) {
                // Good, update in-memory
                const idx = messages.findIndex(m => m.id === tempId);
                if (idx !== -1) messages[idx].id = resp.thread_id; // Actually this is the thread_id not msg_id
            }

            bcSend('message-sent', { threadId: activeThreadId });
            loadThreadList();

        } catch (e) {
            toast('Erro ao enviar mensagem', 'ri-error-warning-line');
            console.error('[ApolloChat] send:', e);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       REPLY-TO
       ═══════════════════════════════════════════════════════════════════ */

    function setReplyTo(msg) {
        replyTo = {
            id: msg.id,
            sender_name: msg.is_mine || parseInt(msg.sender_id, 10) === MY_ID ? 'Você' : (msg.sender_name || msg.display_name || ''),
            text: (msg.message || '').substring(0, 100),
        };
        const bar = $('.ac-reply-bar');
        if (bar) {
            const sender = $('.ac-reply-bar-sender', bar);
            const preview = $('.ac-reply-bar-preview', bar);
            if (sender) sender.textContent = replyTo.sender_name;
            if (preview) preview.textContent = replyTo.text;
            bar.classList.add('show');
        }
        const input = $('.ac-compose-input');
        if (input) input.focus();
    }

    function clearReplyTo() {
        replyTo = null;
        const bar = $('.ac-reply-bar');
        if (bar) bar.classList.remove('show');
    }

    /* ═══════════════════════════════════════════════════════════════════
       EDIT MESSAGE
       ═══════════════════════════════════════════════════════════════════ */

    function promptEditMessage(msg) {
        const newText = prompt('Editar mensagem:', msg.message || '');
        if (newText === null || newText.trim() === '' || newText.trim() === (msg.message || '').trim()) return;
        editMessage(msg.id, newText.trim());
    }

    async function editMessage(mid, text) {
        try {
            await api(`/messages/${mid}`, {
                method: 'PUT',
                body: JSON.stringify({ message: text }),
            });
            // Update locally
            const m = messages.find(x => parseInt(x.id, 10) === parseInt(mid, 10));
            if (m) {
                m.message = text;
                m.is_edited = true;
            }
            renderMessages();
            scrollToBottom();
            toast('Mensagem editada', 'ri-pencil-line');
        } catch (e) {
            toast('Erro ao editar', 'ri-error-warning-line');
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       DELETE MESSAGE
       ═══════════════════════════════════════════════════════════════════ */

    function confirmDeleteMessage(msg) {
        if (!confirm('Apagar essa mensagem?')) return;
        deleteMessage(msg.id);
    }

    async function deleteMessage(mid) {
        try {
            await api(`/messages/${mid}`, { method: 'DELETE' });
            const m = messages.find(x => parseInt(x.id, 10) === parseInt(mid, 10));
            if (m) {
                m.is_deleted = 1;
                m.message = '';
            }
            renderMessages();
            scrollToBottom();
            toast('Mensagem apagada', 'ri-delete-bin-line');
        } catch (e) {
            toast('Erro ao apagar', 'ri-error-warning-line');
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       REACTIONS
       ═══════════════════════════════════════════════════════════════════ */

    const QUICK_EMOJIS = ['❤️', '👍', '😂', '😮', '😢', '🔥'];

    function showQuickReact(anchorBtn, msg) {
        // Remove existing picker
        const existing = $('.ac-quick-react');
        if (existing) existing.remove();

        const popup = document.createElement('div');
        popup.className = 'ac-quick-react';
        popup.style.cssText = 'position:absolute;bottom:100%;left:0;display:flex;gap:2px;background:var(--ac-card-bg);border:1px solid var(--ac-border);border-radius:var(--ac-radius-full);padding:4px 6px;box-shadow:var(--ac-shadow-md);z-index:20;';
        popup.innerHTML = QUICK_EMOJIS.map(e =>
            `<span style="cursor:pointer;font-size:1.15rem;padding:2px 4px;border-radius:4px;transition:transform .15s;"
                  data-re="${e}"
                  onmouseover="this.style.transform='scale(1.3)'"
                  onmouseout="this.style.transform=''">${e}</span>`
        ).join('');

        const row = anchorBtn.closest('.ac-msg-row');
        if (row) row.querySelector('.ac-bubble').appendChild(popup);

        popup.querySelectorAll('[data-re]').forEach(s => {
            s.addEventListener('click', (ev) => {
                ev.stopPropagation();
                toggleReaction(parseInt(msg.id, 10), s.dataset.re);
                popup.remove();
            });
        });

        // Close on outside click
        const closeHandler = (ev) => {
            if (!popup.contains(ev.target)) { popup.remove(); document.removeEventListener('click', closeHandler); }
        };
        setTimeout(() => document.addEventListener('click', closeHandler), 50);
    }

    async function toggleReaction(mid, emoji) {
        try {
            const result = await api(`/messages/${mid}/react`, {
                method: 'POST',
                body: JSON.stringify({ emoji }),
            });
            // Update locally — PHP returns {action, reactions: [{emoji, count, users}]}
            const m = messages.find(x => parseInt(x.id, 10) === mid);
            if (m && result) {
                m.reactions = result.reactions || result;
            }
            renderMessages();
            // Keep scroll position instead of jumping to bottom for reactions
        } catch (e) {
            console.error('[ApolloChat] reaction:', e);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       TYPING INDICATOR
       ═══════════════════════════════════════════════════════════════════ */

    function handleTypingInput() {
        if (!activeThreadId) return;
        if (!typingSent) {
            sendTyping(true);
            typingSent = true;
        }
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            sendTyping(false);
            typingSent = false;
        }, TYPING_DEBOUNCE);
    }

    function sendTyping(typing) {
        if (!activeThreadId) return;
        api('/typing', {
            method: 'POST',
            body: JSON.stringify({ thread_id: activeThreadId, typing }),
        }).catch(() => {});
    }

    function updateTypingIndicator(typingUsers) {
        const el = $('#ac-typing');
        if (!el) return;

        if (typingUsers && typingUsers.length > 0) {
            const wasHidden = !el.classList.contains('show');
            el.classList.add('show');

            /* GSAP: animate typing text on first show */
            if (wasHidden && typeof gsap !== 'undefined') {
                const txtEl = el.querySelector('.ac-typing-text');
                if (txtEl) {
                    /* Shimmer in the label, then pulse */
                    gsap.fromTo(txtEl,
                        { opacity: 0, x: -6 },
                        { opacity: 1, x: 0, duration: 0.35, ease: 'power3.out' }
                    );
                }
                /* Stagger the dots in */
                const dots = el.querySelectorAll('.ac-dot');
                if (dots.length) {
                    gsap.fromTo(dots,
                        { scaleY: 0, opacity: 0 },
                        { scaleY: 1, opacity: 1, duration: 0.3,
                          ease: 'back.out(2)', stagger: 0.08 }
                    );
                }
            }

            scrollToBottom(true);
        } else {
            if (el.classList.contains('show') && typeof gsap !== 'undefined') {
                gsap.to(el, {
                    opacity: 0, y: 4, duration: 0.2, ease: 'power2.in',
                    onComplete: function () {
                        el.classList.remove('show');
                        gsap.set(el, { opacity: 1, y: 0 });
                    }
                });
            } else {
                el.classList.remove('show');
            }
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       POLLING — Real-time Updates
       ═══════════════════════════════════════════════════════════════════ */

    async function poll() {
        try {
            const params = new URLSearchParams();
            if (lastPollTS) params.set('since', lastPollTS);
            if (activeThreadId) params.set('thread_id', activeThreadId);

            const data = await api('/poll?' + params.toString());
            lastPollTS = data.timestamp || '';

            // ── New messages ──
            if (data.new_messages && data.new_messages.length > 0) {
                let refreshSidebar = false;
                data.new_messages.forEach(nm => {
                    refreshSidebar = true;
                    if (parseInt(nm.thread_id, 10) === activeThreadId) {
                        // Add to current conversation
                        const exists = messages.find(m => parseInt(m.id, 10) === parseInt(nm.id, 10));
                        if (!exists) {
                            messages.push(nm);
                            const area = $('.ac-messages');
                            if (area) {
                                const typing = $('#ac-typing', area);
                                const html = renderMessageBubble(nm);
                                if (typing) typing.insertAdjacentHTML('beforebegin', html);
                                else area.insertAdjacentHTML('beforeend', html);
                                bindMessageEvents(area);
                                scrollToBottom(true);
                            }
                        }
                    }
                    // Notification
                    playNotifSound();
                    showBrowserNotif(nm.sender_name || 'Nova mensagem', nm.message || '', nm.thread_id);
                });
                if (refreshSidebar) loadThreadList();

                // Auto mark-read if in active thread
                if (activeThreadId) {
                    api(`/threads/${activeThreadId}/read`, { method: 'POST', body: '{}' }).catch(() => {});
                }
            }

            // ── Typing indicators ──
            updateTypingIndicator(data.typing);

            // ── Online status updates ──
            if (data.online && data.online.length > 0) {
                updateOnlineStatus(data.online);
            }

            // ── Message updates (edits/deletes) ──
            if (data.updates && data.updates.length > 0) {
                let needsRerender = false;
                data.updates.forEach(u => {
                    if (parseInt(u.thread_id, 10) !== activeThreadId) return;
                    const m = messages.find(x => parseInt(x.id, 10) === parseInt(u.id, 10));
                    if (!m) return;
                    if (u.action === 'deleted') { m.is_deleted = 1; m.message = ''; needsRerender = true; }
                    if (u.action === 'edited') { m.message = u.message; m.is_edited = true; needsRerender = true; }
                });
                if (needsRerender) { renderMessages(); scrollToBottom(); }
            }

            // ── Read receipts ──
            if (data.read_receipts) {
                updateReadReceipts(data.read_receipts);
            }

            // ── Unread count for global badge ──
            updateGlobalUnread(data.unread_messages || 0);

        } catch (e) {
            console.error('[ApolloChat] poll error:', e);
        }
    }

    function updateOnlineStatus(onlineList) {
        const onlineIds = onlineList.map(o => parseInt(o.user_id || o, 10));
        threads.forEach(t => {
            if (!t.is_group && t.other_user_id) {
                t.is_online = onlineIds.includes(t.other_user_id);
            }
        });
        // Update sidebar dots
        $$('.ac-thread').forEach(el => {
            const tid = parseInt(el.dataset.tid, 10);
            const t = threads.find(th => (th.thread_id || th.id) === tid);
            if (!t || t.is_group) return;
            const dot = $('.ac-online-dot', el);
            if (t.is_online && !dot) {
                const avatarWrap = $('.ac-thread-avatar', el);
                if (avatarWrap) avatarWrap.insertAdjacentHTML('beforeend', '<span class="ac-online-dot"></span>');
            } else if (!t.is_online && dot) {
                dot.remove();
            }
        });
        // Update header
        if (activeThreadId) {
            const t = threads.find(th => (th.thread_id || th.id) === activeThreadId);
            if (t && !t.is_group) {
                const status = $('.ac-header-status');
                if (status) {
                    status.textContent = t.is_online ? 'Online' : 'Offline';
                    status.classList.toggle('online', t.is_online);
                }
            }
        }
    }

    function updateReadReceipts(receipts) {
        if (!receipts || !receipts.length) return;
        // Build map: last_read_message_id → array of readers
        const maxReadId = Math.max(...receipts.map(r => parseInt(r.last_read_message_id, 10) || 0));
        const area = $('.ac-messages');
        if (!area) return;

        $$('.ac-msg-row.sent', area).forEach(row => {
            const mid = parseInt(row.dataset.mid, 10);
            const icon = $('.ac-receipt i', row);
            if (!icon) return;
            const parent = icon.parentElement;
            if (!parent) return;

            if (mid <= maxReadId) {
                // Read — double blue check
                parent.classList.add('read');
                icon.className = 'ri-check-double-line';
                icon.style.color = 'var(--ac-primary)';
            } else {
                // Delivered but not read — double grey check
                icon.className = 'ri-check-double-line';
                icon.style.color = '';
                parent.classList.remove('read');
            }
        });
    }

    function updateGlobalUnread(count) {
        // Update header notification badge if exists
        const badge = document.querySelector('[data-notif-chat]');
        if (badge) {
            badge.textContent = count > 0 ? (count > 99 ? '99+' : count) : '';
            badge.style.display = count > 0 ? '' : 'none';
        }
        // Update document title
        if (count > 0) {
            document.title = `(${count}) Mensagens • Apollo`;
        } else {
            document.title = 'Mensagens • Apollo';
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       PRESENCE — Heartbeat
       ═══════════════════════════════════════════════════════════════════ */

    function sendPresence() {
        api('/presence', { method: 'POST', body: '{}' }).catch(() => {});
    }

    /* ═══════════════════════════════════════════════════════════════════
       GIF PICKER — Tenor API (server-proxied)
       ═══════════════════════════════════════════════════════════════════ */

    let gifPickerOpen = false;
    let gifSearchTimer = null;
    let gifNextPos = '';

    function initGifPicker() {
        const btn = $('.ac-gif-btn');
        if (!btn) return;
        btn.addEventListener('click', toggleGifPicker);

        const closeBtn = $('.ac-gif-close');
        if (closeBtn) closeBtn.addEventListener('click', () => toggleGifPicker(false));

        const searchInput = $('.ac-gif-search');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(gifSearchTimer);
                gifSearchTimer = setTimeout(() => {
                    gifNextPos = '';
                    searchGifs(searchInput.value.trim());
                }, 350);
            });
        }

        // Infinite scroll in GIF grid
        const grid = $('.ac-gif-grid');
        if (grid) {
            grid.addEventListener('scroll', () => {
                if (grid.scrollTop + grid.clientHeight >= grid.scrollHeight - 60 && gifNextPos) {
                    const q = ($('.ac-gif-search') || {}).value || '';
                    searchGifs(q.trim(), true);
                }
            });
        }
    }

    function toggleGifPicker(forceState) {
        const picker = $('.ac-gif-picker');
        if (!picker) return;

        gifPickerOpen = typeof forceState === 'boolean' ? forceState : !gifPickerOpen;
        picker.classList.toggle('show', gifPickerOpen);

        if (gifPickerOpen) {
            const input = $('.ac-gif-search');
            if (input) { input.value = ''; input.focus(); }
            gifNextPos = '';
            searchGifs(''); // load trending/featured
        }
    }

    async function searchGifs(query, append) {
        const grid = $('.ac-gif-grid');
        if (!grid) return;

        if (!append) {
            if (_gifLoadingFX) { _gifLoadingFX.stop(); _gifLoadingFX = null; }
            grid.innerHTML = '<div class="ac-gif-loading"><p class="ac-loading-txt" aria-live="polite">Buscando GIFs...</p></div>';
            if (typeof ApolloTextFX !== 'undefined') {
                _gifLoadingFX = ApolloTextFX.loading(
                    grid.querySelector('.ac-loading-txt'),
                    ['Buscando GIFs...', 'Carregando imagens...', 'Aguarde...']
                );
            }
        }

        const params = new URLSearchParams({ q: query, limit: '20' });
        if (append && gifNextPos) params.set('pos', gifNextPos);

        try {
            const data = await api('/gif-search?' + params.toString());

            if (!append) {
                if (_gifLoadingFX) { _gifLoadingFX.stop(); _gifLoadingFX = null; }
                grid.innerHTML = '';
            }

            if (!data.results || !data.results.length) {
                if (!append) {
                    grid.innerHTML = '<div class="ac-gif-empty"><i class="ri-emotion-sad-line"></i><p>Nenhum GIF encontrado</p></div>';
                }
                gifNextPos = '';
                return;
            }

            gifNextPos = data.next || '';

            data.results.forEach(gif => {
                const item = document.createElement('div');
                item.className = 'ac-gif-item';
                item.innerHTML = `<img src="${esc(gif.preview)}" alt="${esc(gif.title)}" loading="lazy">`;
                item.addEventListener('click', () => selectGif(gif));
                grid.appendChild(item);
            });
        } catch (e) {
            if (!append) {
                grid.innerHTML = '<div class="ac-gif-empty"><i class="ri-error-warning-line"></i><p>Erro ao buscar GIFs</p></div>';
            }
        }
    }

    function selectGif(gif) {
        // Set pending GIF and immediately send
        pendingGif = { url: gif.url, preview: gif.preview, title: gif.title };
        toggleGifPicker(false);
        sendMessage();
    }

    function clearGifPreview() {
        pendingGif = null;
        const bar = $('.ac-attach-preview');
        if (bar) { bar.classList.remove('show'); bar.innerHTML = ''; }
    }

    /* ═══════════════════════════════════════════════════════════════════
       EMOJI PICKER
       ═══════════════════════════════════════════════════════════════════ */

    const EMOJI_DATA = {
        'Frequentes':  ['😂','❤️','🔥','👍','😍','🥰','😢','😮','🙏','✨','💯','🎉','😎','🤔','😅','🥺','💪','😭','🤣','😊'],
        'Smileys':     ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😉','😊','😇','🥰','😍','🤩','😘','😗','😚','😙','🥲','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🤫','🤔','🫡','🤐','🤨','😐','😑','😶','🫥','😏','😒','🙄','😬','😮‍💨','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🥵','🥶','🥴','😵','🤯','🤠','🥳','🥸','😎','🤓','🧐','😕','🫤','😟','🙁','☹️','😮','😯','😲','😳','🥺','🥹','😦','😧','😨','😰','😥','😢','😭','😱','😖','😣','😞','😓','😩','😫','🥱','😤','😡','😠','🤬','😈','👿','💀','☠️','💩','🤡','👹','👺','👻','👽','👾','🤖'],
        'Gestos':      ['👋','🤚','🖐️','✋','🖖','🫱','🫲','🫳','🫴','👌','🤌','🤏','✌️','🤞','🫰','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝️','🫵','👍','👎','✊','👊','🤛','🤜','👏','🙌','🫶','👐','🤲','🤝','🙏','✍️','💅','🤳','💪','🫎'],
        'Amor':        ['💋','❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❤️‍🔥','❤️‍🩹','💕','💞','💓','💗','💖','💘','💝','💟','♥️','💌','💐','🌹','🌺','🌷','🌸'],
        'Objetos':     ['⚽','🏀','🏈','⚾','🎾','🏐','🎱','🏓','🎮','🕹️','🎲','🧩','🎭','🎨','🎬','🎤','🎧','🎵','🎶','🎹','🥁','🎷','🎺','🎸','📱','💻','⌨️','🖥️','📷','📸','📹','🎥','📺','📻','🔋','💡','🔦','📚','📖','✏️','📝','📌','📎','✂️','🔑','🔒','🔓','🧲','💰','💵','💳'],
        'Natureza':    ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐻‍❄️','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🙈','🙉','🙊','🐒','🐔','🐧','🐦','🐤','🦆','🦅','🦉','🦇','🐺','🐗','🦋','🐛','🐌','🐜','🐝','🌲','🌳','🌴','🌱','🌿','☘️','🍀','🌵','🌾','🍁','🍂','🍃','🌍','🌎','🌏','🌙','⭐','🌟','✨','⚡','🔥','💥','☀️','🌈','☁️','❄️','💧','🌊'],
        'Comida':      ['🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🥑','🍆','🌽','🌶️','🫑','🥒','🥬','🥦','🧅','🧄','🍄','🥜','🫘','🍞','🥐','🥖','🥨','🧀','🍖','🍗','🥩','🌭','🍔','🍟','🍕','🌮','🌯','🥗','🍝','🍜','🍲','🍱','🍣','🍙','🍚','🍘','🥟','🍤','🍩','🍪','🎂','🍰','🧁','🥧','🍫','🍬','🍭','🍮','🍯','☕','🍵','🧃','🥤','🧋','🍺','🍻','🥂','🍷','🥃','🍸','🍹','🍾'],
    };

    function initEmojiPicker() {
        const trigger = $('.ac-emoji-trigger');
        if (!trigger) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleEmojiPicker();
        });
    }

    function toggleEmojiPicker() {
        const picker = $('.ac-emoji-picker');
        if (!picker) return;
        emojiPickerOpen = !emojiPickerOpen;
        picker.classList.toggle('show', emojiPickerOpen);

        if (emojiPickerOpen) {
            buildEmojiGrid();
            const searchInput = $('.ac-emoji-picker-search input', picker);
            if (searchInput) searchInput.focus();
        }
    }

    function buildEmojiGrid(filter) {
        const grid = $('.ac-emoji-grid');
        if (!grid) return;

        let html = '';
        const filterLower = (filter || '').toLowerCase();
        for (const [category, emojis] of Object.entries(EMOJI_DATA)) {
            // Rough text-based emoji search: filter by category name match
            const categoryMatch = !filterLower || category.toLowerCase().includes(filterLower);
            const visibleEmojis = categoryMatch ? emojis : [];
            if (!visibleEmojis.length) continue;
            html += `<div class="ac-emoji-category-label">${esc(category)}</div>`;
            visibleEmojis.forEach(e => {
                html += `<button class="ac-emoji-item" data-emoji="${e}" type="button">${e}</button>`;
            });
        }
        if (!html) html = '<div style="padding:1rem;text-align:center;color:var(--ac-text-muted);font-size:.85rem;">Nenhum emoji encontrado</div>';
        grid.innerHTML = html;

        // Click handlers
        $$('.ac-emoji-item', grid).forEach(btn => {
            btn.addEventListener('click', () => {
                insertEmoji(btn.dataset.emoji);
            });
        });
    }

    function insertEmoji(emoji) {
        const input = $('.ac-compose-input');
        if (!input) return;
        const start = input.selectionStart || input.value.length;
        const before = input.value.substring(0, start);
        const after = input.value.substring(input.selectionEnd || start);
        input.value = before + emoji + after;
        input.focus();
        const pos = start + emoji.length;
        input.setSelectionRange(pos, pos);
        // Close picker
        toggleEmojiPicker();
    }

    /* ═══════════════════════════════════════════════════════════════════
       SEARCH
       ═══════════════════════════════════════════════════════════════════ */

    function toggleSearch() {
        const overlay = $('.ac-search-overlay');
        if (!overlay) return;
        searchOpen = !searchOpen;
        overlay.classList.toggle('show', searchOpen);
        if (searchOpen) {
            const input = $('input', overlay);
            if (input) { input.value = ''; input.focus(); }
            const results = $('.ac-search-results', overlay);
            if (results) results.innerHTML = '';
        }
    }

    const doSearch = debounce(async function (q) {
        const results = $('.ac-search-results');
        if (!results) return;
        if (q.length < 2) { results.innerHTML = ''; return; }

        try {
            const data = await api('/search?q=' + encodeURIComponent(q));
            if (!data.length) {
                results.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--ac-text-muted);font-size:.85rem;">Nenhum resultado</div>';
                return;
            }
            results.innerHTML = data.map(r => {
                const highlight = esc(r.message || '').replace(
                    new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi'),
                    '<mark>$1</mark>'
                );
                return `<div class="ac-search-result" data-tid="${r.thread_id}" data-mid="${r.id}">
                    <div class="ac-result-sender">${esc(r.sender_name || r.display_name || '')}</div>
                    <div class="ac-result-text">${highlight}</div>
                    <div class="ac-result-time">${timeAgo(r.created_at)}</div>
                </div>`;
            }).join('');

            $$('.ac-search-result', results).forEach(el => {
                el.addEventListener('click', () => {
                    const tid = parseInt(el.dataset.tid, 10);
                    toggleSearch();
                    if (tid) openThread(tid);
                });
            });
        } catch (e) {
            results.innerHTML = '<div style="padding:1rem;text-align:center;color:var(--ac-text-muted);">Erro na busca</div>';
        }
    }, 400);

    /* ═══════════════════════════════════════════════════════════════════
       SCROLL-UP PAGINATION
       ═══════════════════════════════════════════════════════════════════ */

    function initScrollPagination() {
        const area = $('.ac-messages');
        if (!area) return;

        area.addEventListener('scroll', () => {
            if (area.scrollTop < 80 && !loadingOlder && hasMoreOlder && activeThreadId && oldestMsgId > 0) {
                loadOlderMessages();
            }
        });
    }

    async function loadOlderMessages() {
        if (loadingOlder || !hasMoreOlder || !activeThreadId) return;
        loadingOlder = true;

        const area = $('.ac-messages');
        const prevHeight = area ? area.scrollHeight : 0;

        try {
            const older = await api(`/threads/${activeThreadId}/older?before=${oldestMsgId}`);
            if (!older.length || older.length < 30) hasMoreOlder = false;
            if (older.length) {
                oldestMsgId = parseInt(older[0].id, 10);
                messages = [...older, ...messages];
                renderMessages();
                // Preserve scroll position
                if (area) {
                    const newHeight = area.scrollHeight;
                    area.scrollTop = newHeight - prevHeight;
                }
            }
        } catch (e) {
            console.error('[ApolloChat] loadOlderMessages:', e);
        }
        loadingOlder = false;
    }

    /* ═══════════════════════════════════════════════════════════════════
       CONTEXT MENU
       ═══════════════════════════════════════════════════════════════════ */

    function showContextMenu(x, y, msg) {
        // Remove existing
        let menu = $('.ac-context-menu');
        if (menu) menu.remove();

        const isMine = msg.is_mine || parseInt(msg.sender_id, 10) === MY_ID;
        const deleted = msg.is_deleted && parseInt(msg.is_deleted, 10) === 1;
        if (deleted) return;

        const canEdit = isMine && msg.created_at && (Date.now() - new Date(msg.created_at.replace(' ', 'T') + 'Z').getTime()) < EDIT_WINDOW_MS;

        const items = [
            { label: 'Responder', icon: 'ri-reply-line', action: 'reply' },
            { label: 'Reagir', icon: 'ri-emotion-line', action: 'react' },
            { label: 'Copiar', icon: 'ri-file-copy-line', action: 'copy' },
            { label: 'Encaminhar', icon: 'ri-chat-forward-line', action: 'forward' },
            { label: 'Fixar', icon: 'ri-pushpin-line', action: 'pin' },
        ];
        if (canEdit) items.push({ label: 'Editar', icon: 'ri-pencil-line', action: 'edit' });
        if (isMine) items.push({ label: 'Apagar', icon: 'ri-delete-bin-line', action: 'delete', danger: true });

        menu = document.createElement('div');
        menu.className = 'ac-context-menu';
        menu.style.cssText = `position:fixed;top:${y}px;left:${x}px;z-index:9998;background:var(--ac-card-bg);border:1px solid var(--ac-border);border-radius:var(--ac-radius-md);box-shadow:var(--ac-shadow-lg);padding:4px 0;min-width:160px;`;

        menu.innerHTML = items.map(it =>
            `<div class="ac-ctx-item${it.danger ? ' danger' : ''}" data-act="${it.action}" style="display:flex;align-items:center;gap:10px;padding:8px 14px;cursor:pointer;font-size:.85rem;transition:background .15s;${it.danger ? 'color:var(--ac-danger);' : ''}">
                <i class="${it.icon}" style="font-size:1rem;width:18px;"></i> ${it.label}
            </div>`
        ).join('');

        document.body.appendChild(menu);

        // Adjust position if off-screen
        const rect = menu.getBoundingClientRect();
        if (rect.right > window.innerWidth) menu.style.left = (x - rect.width) + 'px';
        if (rect.bottom > window.innerHeight) menu.style.top = (y - rect.height) + 'px';

        // Hover effects
        $$('.ac-ctx-item', menu).forEach(el => {
            el.addEventListener('mouseenter', () => el.style.background = 'var(--ac-hover)');
            el.addEventListener('mouseleave', () => el.style.background = '');
        });

        // Click handlers
        $$('[data-act]', menu).forEach(el => {
            el.addEventListener('click', () => {
                const act = el.dataset.act;
                if (act === 'reply') setReplyTo(msg);
                if (act === 'edit') promptEditMessage(msg);
                if (act === 'delete') confirmDeleteMessage(msg);
                if (act === 'copy') { navigator.clipboard.writeText(msg.message || '').then(() => toast('Copiado!')).catch(() => {}); }
                if (act === 'forward') openForwardModal(msg);
                if (act === 'pin') pinMessageFromCtx(msg);
                if (act === 'react') {
                    // Show quick react near the message
                    const row = $(`.ac-msg-row[data-mid="${msg.id}"]`);
                    if (row) {
                        const btn = $('[data-act="react"]', row);
                        if (btn) showQuickReact(btn, msg);
                    }
                }
                menu.remove();
            });
        });

        // Close on outside click
        const close = (ev) => {
            if (!menu.contains(ev.target)) { menu.remove(); document.removeEventListener('click', close); }
        };
        setTimeout(() => document.addEventListener('click', close), 50);
    }

    /** Pin a message from the context menu. */
    async function pinMessageFromCtx(msg) {
        if (!activeThreadId || !msg) return;
        try {
            await api(`/threads/${activeThreadId}/pin`, {
                method: 'POST',
                body: JSON.stringify({ message_id: parseInt(msg.id, 10) }),
            });
            toast('Mensagem fixada', 'ri-pushpin-line');
        } catch (e) {
            toast('Erro ao fixar', 'ri-error-warning-line');
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       THREAD MORE MENU (mute, pinned, info, block, delete)
       ═══════════════════════════════════════════════════════════════════ */

    function openThreadMenu() {
        const t = threads.find(th => (th.thread_id || th.id) == activeThreadId);
        if (!t) return;

        let menu = $('.ac-thread-menu');
        if (menu) menu.remove();

        const isMuted = t.is_muted && parseInt(t.is_muted, 10) === 1;

        const items = [
            { label: isMuted ? 'Desmutar conversa' : 'Silenciar conversa', icon: isMuted ? 'ri-volume-up-line' : 'ri-volume-mute-line', action: 'toggle-mute' },
            { label: 'Mensagens fixadas', icon: 'ri-pushpin-line', action: 'pinned' },
        ];
        if (!t.is_group && t.other_user_id) {
            items.push({ label: 'Info do contato', icon: 'ri-user-3-line', action: 'user-info' });
            items.push({ label: 'Bloquear usuário', icon: 'ri-user-unfollow-line', action: 'block', danger: true });
        }
        items.push({ label: 'Excluir conversa', icon: 'ri-delete-bin-5-line', action: 'delete-thread', danger: true });

        const btn = $('[data-action="more"]');
        const rect = btn ? btn.getBoundingClientRect() : { bottom: 60, right: 100 };

        menu = document.createElement('div');
        menu.className = 'ac-thread-menu';
        menu.style.cssText = `position:fixed;top:${rect.bottom + 4}px;right:${window.innerWidth - rect.right}px;z-index:9998;background:var(--ac-card-bg);border:1px solid var(--ac-border);border-radius:var(--ac-radius-md);box-shadow:var(--ac-shadow-lg);padding:4px 0;min-width:200px;`;
        menu.innerHTML = items.map(it =>
            `<div data-act="${it.action}" style="display:flex;align-items:center;gap:10px;padding:8px 14px;cursor:pointer;font-size:.85rem;${it.danger ? 'color:var(--ac-danger);' : ''}">
                <i class="${it.icon}" style="font-size:1rem;width:18px;"></i> ${it.label}
            </div>`
        ).join('');

        document.body.appendChild(menu);

        // Adjust position if off-screen
        const menuRect = menu.getBoundingClientRect();
        if (menuRect.right > window.innerWidth) menu.style.left = (rect.left) + 'px';
        if (menuRect.bottom > window.innerHeight) menu.style.top = (rect.top - menuRect.height) + 'px';

        $$('[data-act]', menu).forEach(el => {
            el.addEventListener('mouseenter', () => el.style.background = 'var(--ac-hover)');
            el.addEventListener('mouseleave', () => el.style.background = '');
            el.addEventListener('click', async () => {
                const act = el.dataset.act;
                if (act === 'toggle-mute') {
                    try {
                        const newMute = !(isMuted);
                        await api(`/threads/${activeThreadId}/mute`, { method: 'POST', body: JSON.stringify({ mute: newMute }) });
                        t.is_muted = newMute ? 1 : 0;
                        toast(newMute ? 'Conversa silenciada' : 'Conversa com som', newMute ? 'ri-volume-mute-line' : 'ri-volume-up-line');
                    } catch (e) { toast('Erro', 'ri-error-warning-line'); }
                }
                if (act === 'pinned') {
                    openPinnedMessagesPanel();
                }
                if (act === 'user-info' && t.other_user_id) {
                    openUserInfoPanel(t.other_user_id, t.display_name, t.avatar_url);
                }
                if (act === 'delete-thread') {
                    if (confirm('Excluir essa conversa?')) {
                        try {
                            await api(`/threads/${activeThreadId}`, { method: 'DELETE' });
                            bcSend('thread-deleted', { threadId: activeThreadId });
                            activeThreadId = 0;
                            renderEmptyMain();
                            loadThreadList();
                            toast('Conversa excluída');
                        } catch (e) { toast('Erro ao excluir', 'ri-error-warning-line'); }
                    }
                }
                if (act === 'block' && t.other_user_id) {
                    if (confirm('Bloquear esse usuário?')) {
                        try {
                            await api(`/block/${t.other_user_id}`, { method: 'POST', body: '{}' });
                            toast('Usuário bloqueado');
                        } catch (e) { toast('Erro', 'ri-error-warning-line'); }
                    }
                }
                menu.remove();
            });
        });

        const close = (ev) => {
            if (!menu.contains(ev.target) && ev.target !== btn) { menu.remove(); document.removeEventListener('click', close); }
        };
        setTimeout(() => document.addEventListener('click', close), 50);
    }

    /* ═══════════════════════════════════════════════════════════════════
       PINNED MESSAGES PANEL
       ═══════════════════════════════════════════════════════════════════ */

    async function openPinnedMessagesPanel() {
        if (!activeThreadId) return;
        try {
            const pinned = await api(`/threads/${activeThreadId}/pinned`);
            const overlay = $('.ac-modal-overlay');
            if (!overlay) return;
            const modal = $('.ac-modal', overlay);
            if (!modal) return;

            const header = $('.ac-modal-header h3', modal);
            if (header) header.textContent = 'Mensagens Fixadas';

            const body = $('.ac-modal-body', modal);
            if (body) {
                if (!pinned.length) {
                    body.innerHTML = '<div style="padding:2rem;text-align:center;color:var(--ac-text-muted);"><i class="ri-pushpin-line" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>Nenhuma mensagem fixada</div>';
                } else {
                    body.innerHTML = `<div class="ac-pinned-list">${pinned.map(p => `
                        <div class="ac-pinned-item" data-mid="${p.id}" style="border-bottom:1px solid var(--ac-border);padding:10px 0;">
                            <div style="font-size:.75rem;color:var(--ac-text-muted);margin-bottom:2px;">${esc(p.sender_name || '')} · ${formatTime(p.created_at)}</div>
                            <div style="font-size:.85rem;color:var(--ac-text-primary);">${esc(p.message || '')}</div>
                            <button class="ac-unpin-btn" data-mid="${p.id}" style="margin-top:4px;font-size:.7rem;color:var(--ac-danger);background:none;border:none;cursor:pointer;"><i class="ri-unpin-line"></i> Desafixar</button>
                        </div>`).join('')}</div>`;

                    // Unpin buttons
                    $$('.ac-unpin-btn', body).forEach(btn => {
                        btn.addEventListener('click', async () => {
                            const mid = parseInt(btn.dataset.mid, 10);
                            try {
                                await api(`/threads/${activeThreadId}/pin`, { method: 'DELETE', body: JSON.stringify({ message_id: mid }) });
                                toast('Mensagem desafixada', 'ri-unpin-line');
                                openPinnedMessagesPanel(); // Refresh
                            } catch (e) { toast('Erro', 'ri-error-warning-line'); }
                        });
                    });

                    // Click to scroll
                    $$('.ac-pinned-item', body).forEach(el => {
                        el.addEventListener('click', (e) => {
                            if (e.target.closest('.ac-unpin-btn')) return;
                            const mid = el.dataset.mid;
                            overlay.classList.remove('show');
                            const target = $(`.ac-msg-row[data-mid="${mid}"]`);
                            if (target) {
                                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                target.classList.add('highlight');
                                setTimeout(() => target.classList.remove('highlight'), 1500);
                            }
                        });
                    });
                }
            }

            const footer = $('.ac-modal-footer', modal);
            if (footer) {
                footer.innerHTML = '<button class="ac-btn" onclick="document.querySelector(\'.ac-modal-overlay\').classList.remove(\'show\')">Fechar</button>';
            }
            overlay.classList.add('show');
        } catch (e) {
            toast('Erro ao carregar fixadas', 'ri-error-warning-line');
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       FORWARD MESSAGE
       ═══════════════════════════════════════════════════════════════════ */

    async function openForwardModal(msg) {
        if (!msg) return;
        const overlay = $('.ac-modal-overlay');
        if (!overlay) return;
        const modal = $('.ac-modal', overlay);
        if (!modal) return;

        const header = $('.ac-modal-header h3', modal);
        if (header) header.textContent = 'Encaminhar mensagem';

        const body = $('.ac-modal-body', modal);
        if (body) {
            body.innerHTML = `
                <div style="padding:10px;background:var(--ac-bg-secondary);border-radius:var(--ac-radius-md);margin-bottom:12px;font-size:.85rem;color:var(--ac-text-muted);">
                    <i class="ri-chat-forward-line"></i> ${esc((msg.message || '').substring(0, 100))}
                </div>
                <p style="font-size:.8rem;color:var(--ac-text-muted);margin-bottom:8px;">Selecione a conversa de destino:</p>
                <div class="ac-fwd-thread-list" style="max-height:250px;overflow-y:auto;">
                    ${threads.filter(t => (t.thread_id || t.id) != activeThreadId).map(t => {
                        const tid = t.thread_id || t.id;
                        return `<div class="ac-fwd-thread" data-tid="${tid}" style="display:flex;align-items:center;gap:10px;padding:8px;cursor:pointer;border-radius:var(--ac-radius-md);transition:background .15s;">
                            <img src="${esc(t.avatar_url || '')}" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                            <span style="font-size:.85rem;font-weight:500;">${esc(t.display_name || 'Conversa')}</span>
                        </div>`;
                    }).join('') || '<div style="padding:1rem;text-align:center;color:var(--ac-text-muted);">Nenhuma conversa disponível</div>'}
                </div>`;

            $$('.ac-fwd-thread', body).forEach(el => {
                el.addEventListener('mouseenter', () => el.style.background = 'var(--ac-hover)');
                el.addEventListener('mouseleave', () => el.style.background = '');
                el.addEventListener('click', async () => {
                    const targetTid = parseInt(el.dataset.tid, 10);
                    try {
                        await api(`/messages/${msg.id}/forward`, { method: 'POST', body: JSON.stringify({ thread_id: targetTid }) });
                        overlay.classList.remove('show');
                        toast('Mensagem encaminhada', 'ri-chat-forward-line');
                        loadThreadList();
                    } catch (e) {
                        toast('Erro ao encaminhar', 'ri-error-warning-line');
                    }
                });
            });
        }

        const footer = $('.ac-modal-footer', modal);
        if (footer) {
            footer.innerHTML = '<button class="ac-btn" onclick="document.querySelector(\'.ac-modal-overlay\').classList.remove(\'show\')">Cancelar</button>';
        }
        overlay.classList.add('show');
    }

    /* ═══════════════════════════════════════════════════════════════════
       USER INFO PANEL
       ═══════════════════════════════════════════════════════════════════ */

    function openUserInfoPanel(userId, name, avatarUrl) {
        const overlay = $('.ac-modal-overlay');
        if (!overlay) return;
        const modal = $('.ac-modal', overlay);
        if (!modal) return;

        const header = $('.ac-modal-header h3', modal);
        if (header) header.textContent = 'Info do contato';

        const isOnl = threads.find(t => t.other_user_id === userId)?.is_online;

        const body = $('.ac-modal-body', modal);
        if (body) {
            body.innerHTML = `
                <div style="text-align:center;padding:1rem 0;">
                    <div style="position:relative;display:inline-block;">
                        <img src="${esc(avatarUrl || '')}" alt="" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--ac-primary);">
                        ${isOnl ? '<span style="position:absolute;bottom:2px;right:2px;width:14px;height:14px;border-radius:50%;background:#22c55e;border:2px solid var(--ac-card-bg);"></span>' : ''}
                    </div>
                    <h3 style="margin:.75rem 0 .25rem;font-size:1.1rem;color:var(--ac-text-primary);">${esc(name || '')}</h3>
                    <span style="font-size:.8rem;color:${isOnl ? '#22c55e' : 'var(--ac-text-muted)'};">${isOnl ? 'Online agora' : 'Offline'}</span>
                </div>
                <div style="display:flex;justify-content:center;gap:12px;padding:.75rem 0;border-top:1px solid var(--ac-border);border-bottom:1px solid var(--ac-border);margin:.5rem 0;">
                    <a href="/id/${esc(name || '')}" class="ac-btn" style="font-size:.8rem;text-decoration:none;" target="_blank"><i class="ri-user-line"></i> Ver perfil</a>
                    <button class="ac-btn" data-act="block-user" style="font-size:.8rem;color:var(--ac-danger);"><i class="ri-user-unfollow-line"></i> Bloquear</button>
                </div>`;

            const blockBtn = $('[data-act="block-user"]', body);
            if (blockBtn) {
                blockBtn.addEventListener('click', async () => {
                    if (confirm('Bloquear esse usuário?')) {
                        try {
                            await api(`/block/${userId}`, { method: 'POST', body: '{}' });
                            toast('Usuário bloqueado');
                            overlay.classList.remove('show');
                        } catch (e) { toast('Erro', 'ri-error-warning-line'); }
                    }
                });
            }
        }

        const footer = $('.ac-modal-footer', modal);
        if (footer) {
            footer.innerHTML = '<button class="ac-btn" onclick="document.querySelector(\'.ac-modal-overlay\').classList.remove(\'show\')">Fechar</button>';
        }
        overlay.classList.add('show');
    }

    /* ═══════════════════════════════════════════════════════════════════
       NEW THREAD MODAL
       ═══════════════════════════════════════════════════════════════════ */

    let selectedRecipients = [];

    function initNewThreadModal() {
        const btn = $('#ac-new-thread-btn');
        if (btn) btn.addEventListener('click', openNewThreadModal);
    }

    function openNewThreadModal() {
        selectedRecipients = [];
        const overlay = $('.ac-modal-overlay');
        if (!overlay) return;
        overlay.classList.add('show');

        // Clear fields
        const search = $('#ac-nt-search', overlay);
        const msg = $('#ac-nt-message', overlay);
        const results = $('#ac-nt-results', overlay);
        const tags = $('#ac-nt-tags', overlay);
        if (search) search.value = '';
        if (msg) msg.value = '';
        if (results) results.innerHTML = '';
        if (tags) tags.innerHTML = '';

        // Close button
        const closeBtn = $('.ac-modal-close', overlay);
        if (closeBtn) closeBtn.addEventListener('click', closeNewThreadModal, { once: true });

        // Overlay click to close
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeNewThreadModal();
        }, { once: true });

        // Search users
        if (search) {
            search.oninput = debounce(async function () {
                const q = search.value.trim();
                if (q.length < 2) { if (results) results.innerHTML = ''; return; }

                try {
                    const res = await fetch(USERS_URL + '?search=' + encodeURIComponent(q) + '&per_page=10', {
                        headers: headers(),
                        credentials: 'same-origin',
                    });
                    const users = await res.json();
                    if (results) {
                        results.innerHTML = users
                            .filter(u => u.id !== MY_ID)
                            .map(u => {
                                const selected = selectedRecipients.find(r => r.id === u.id) ? ' selected' : '';
                                const avatar = u.avatar_urls ? (u.avatar_urls['48'] || u.avatar_urls['24'] || '') : '';
                                return `<div class="ac-user-item${selected}" data-uid="${u.id}" data-uname="${esc(u.name)}">
                                    <img src="${esc(avatar)}" alt="">
                                    <span class="ac-user-name">${esc(u.name)}</span>
                                    <i class="ac-user-check ri-check-line"></i>
                                </div>`;
                            }).join('');

                        $$('.ac-user-item', results).forEach(el => {
                            el.addEventListener('click', () => toggleRecipient(el));
                        });
                    }
                } catch (e) { console.error(e); }
            }, 300);
        }

        // Send button
        const sendBtn = $('#ac-nt-send');
        if (sendBtn) {
            sendBtn.onclick = async () => {
                if (!selectedRecipients.length) { toast('Selecione um destinatário'); return; }
                const msgText = msg ? msg.value.trim() : '';
                if (!msgText) { toast('Escreva uma mensagem'); return; }

                try {
                    const isGroup = selectedRecipients.length > 1;
                    const resp = await api('/send', {
                        method: 'POST',
                        body: JSON.stringify({
                            recipients: selectedRecipients.map(r => r.id),
                            message: msgText,
                            subject: 'Chat',
                            is_group: isGroup,
                        }),
                    });
                    closeNewThreadModal();
                    await loadThreadList();
                    if (resp.thread_id) openThread(resp.thread_id);
                    bcSend('message-sent', { threadId: resp.thread_id });
                } catch (e) {
                    toast('Erro ao criar conversa', 'ri-error-warning-line');
                }
            };
        }
    }

    function toggleRecipient(el) {
        const uid = parseInt(el.dataset.uid, 10);
        const name = el.dataset.uname;
        const idx = selectedRecipients.findIndex(r => r.id === uid);

        if (idx !== -1) {
            selectedRecipients.splice(idx, 1);
            el.classList.remove('selected');
        } else {
            selectedRecipients.push({ id: uid, name });
            el.classList.add('selected');
        }
        renderRecipientTags();
    }

    function renderRecipientTags() {
        const tags = $('#ac-nt-tags');
        if (!tags) return;
        tags.innerHTML = selectedRecipients.map(r =>
            `<span class="ac-selected-tag">${esc(r.name)} <span class="ac-tag-remove" data-uid="${r.id}"><i class="ri-close-line"></i></span></span>`
        ).join('');

        $$('.ac-tag-remove', tags).forEach(el => {
            el.addEventListener('click', () => {
                const uid = parseInt(el.dataset.uid, 10);
                selectedRecipients = selectedRecipients.filter(r => r.id !== uid);
                renderRecipientTags();
                // Update visual in list
                const item = $(`.ac-user-item[data-uid="${uid}"]`);
                if (item) item.classList.remove('selected');
            });
        });
    }

    function closeNewThreadModal() {
        const overlay = $('.ac-modal-overlay');
        if (overlay) overlay.classList.remove('show');
    }

    /* ═══════════════════════════════════════════════════════════════════
       MEMBERS MODAL (Groups)
       ═══════════════════════════════════════════════════════════════════ */

    async function openMembersModal() {
        if (!activeThreadId) return;
        try {
            const members = await api(`/threads/${activeThreadId}/members`);
            const overlay = $('.ac-modal-overlay');
            if (!overlay) return;

            const modal = $('.ac-modal', overlay);
            if (!modal) return;

            const header = $('.ac-modal-header h3', modal);
            if (header) header.textContent = 'Membros do grupo';

            const body = $('.ac-modal-body', modal);
            if (body) {
                body.innerHTML = `
                    <div class="ac-user-list">
                        ${(members || []).map(m => `
                            <div class="ac-user-item" data-uid="${m.user_id}">
                                <img src="${esc(m.avatar_url || '')}" alt="">
                                <span class="ac-user-name">${esc(m.display_name || '')}</span>
                                ${m.role === 'admin' ? '<span style="font-size:.68rem;color:var(--ac-primary);font-weight:600;">Admin</span>' : ''}
                            </div>`).join('')}
                    </div>`;
            }

            const footer = $('.ac-modal-footer', modal);
            if (footer) {
                footer.innerHTML = '<button class="ac-btn" onclick="document.querySelector(\'.ac-modal-overlay\').classList.remove(\'show\')">Fechar</button>';
            }

            overlay.classList.add('show');
        } catch (e) {
            toast('Erro ao carregar membros', 'ri-error-warning-line');
        }
    }

    /* ═══════════════════════════════════════════════════════════════════
       LIGHTBOX
       ═══════════════════════════════════════════════════════════════════ */

    function openLightbox(src) {
        const lb = $('.ac-lightbox');
        if (!lb) return;
        const img = $('img', lb);
        if (img) img.src = src;
        lb.classList.add('show');
    }

    function initLightbox() {
        const lb = $('.ac-lightbox');
        if (!lb) return;
        lb.addEventListener('click', () => lb.classList.remove('show'));
    }

    /* ═══════════════════════════════════════════════════════════════════
       COMPOSE BAR — Auto-resize & Key bindings
       ═══════════════════════════════════════════════════════════════════ */

    function initCompose() {
        const input = $('.ac-compose-input');
        if (!input) return;

        // Auto-resize textarea
        input.addEventListener('input', () => {
            input.style.height = '';
            input.style.height = Math.min(input.scrollHeight, 140) + 'px';
            handleTypingInput();
        });

        // Enter to send (Shift+Enter for new line)
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Send button
        const sendBtn = $('.ac-send-btn');
        if (sendBtn) sendBtn.addEventListener('click', (e) => { e.preventDefault(); sendMessage(); });

        // Reply-bar close button
        const replyClose = $('.ac-reply-bar-close');
        if (replyClose) replyClose.addEventListener('click', clearReplyTo);
    }

    /* ═══════════════════════════════════════════════════════════════════
       SIDEBAR — Search/Filter threads
       ═══════════════════════════════════════════════════════════════════ */

    function initSidebarSearch() {
        const input = $('.ac-sidebar-search input');
        if (!input) return;

        input.addEventListener('input', debounce(() => {
            const q = input.value.trim().toLowerCase();
            if (!q) {
                renderThreadList();
                return;
            }
            // Filter locally
            const filtered = threads.filter(t =>
                (t.display_name || '').toLowerCase().includes(q) ||
                (t.preview || '').toLowerCase().includes(q) ||
                (t.subject || '').toLowerCase().includes(q)
            );
            const original = threads;
            threads = filtered;
            renderThreadList();
            threads = original; // restore
        }, 250));
    }

    /* ═══════════════════════════════════════════════════════════════════
       RESPONSIVE
       ═══════════════════════════════════════════════════════════════════ */

    function initResponsive() {
        const check = () => {
            isMobile = window.innerWidth <= 768;
            if (!isMobile) {
                const sidebar = $('.ac-sidebar');
                if (sidebar) sidebar.classList.remove('hidden');
            }
        };
        window.addEventListener('resize', debounce(check, 200));
        check();
    }

    /* ═══════════════════════════════════════════════════════════════════
       SEARCH OVERLAY EVENTS
       ═══════════════════════════════════════════════════════════════════ */

    function initSearchOverlay() {
        const overlay = $('.ac-search-overlay');
        if (!overlay) return;

        const input = $('input', overlay);
        if (input) {
            input.addEventListener('input', () => doSearch(input.value.trim()));
        }

        const closeBtn = $('.ac-search-close', overlay);
        if (closeBtn) closeBtn.addEventListener('click', toggleSearch);
    }

    /* ═══════════════════════════════════════════════════════════════════
       KEYBOARD SHORTCUTS
       ═══════════════════════════════════════════════════════════════════ */

    function initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Escape closes modals/pickers/search
            if (e.key === 'Escape') {
                if (gifPickerOpen) { toggleGifPicker(false); return; }
                if (emojiPickerOpen) { toggleEmojiPicker(); return; }
                if (searchOpen) { toggleSearch(); return; }
                const modal = $('.ac-modal-overlay.show');
                if (modal) { modal.classList.remove('show'); return; }
                const lb = $('.ac-lightbox.show');
                if (lb) { lb.classList.remove('show'); return; }
                const ctx = $('.ac-context-menu');
                if (ctx) { ctx.remove(); return; }
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════════════
       INITIALIZATION
       ═══════════════════════════════════════════════════════════════════ */

    function init() {
        // Init subsystems
        initNotifications();
        initBroadcastChannel();
        initCompose();
        initGifPicker();
        initEmojiPicker();
        initNewThreadModal();
        initLightbox();
        initSidebarSearch();
        initScrollPagination();
        initSearchOverlay();
        initResponsive();
        initKeyboardShortcuts();

        // Load thread list
        loadThreadList().then(() => {
            if (INITIAL_TID) {
                openThread(INITIAL_TID);
            } else {
                renderEmptyMain();
            }
        });

        // Start polling
        pollTimer = setInterval(poll, POLL_MS);

        // Presence heartbeat
        sendPresence();
        presenceTimer = setInterval(sendPresence, PRESENCE_MS);

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            clearInterval(pollTimer);
            clearInterval(presenceTimer);
        });

        // Visibility change — pause/resume polling
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(pollTimer);
                pollTimer = null;
            } else {
                if (!pollTimer) pollTimer = setInterval(poll, POLL_MS);
                sendPresence();
                poll(); // immediate
            }
        });
    }

    // ── Boot ────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
