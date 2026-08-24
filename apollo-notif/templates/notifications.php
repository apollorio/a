<?php

/**
 * Notifications Page — /notificacoes
 * Blank Canvas Template — Swipe-to-delete card design.
 *
 * Features: swipe-to-reveal delete, clear-all cascade, empty state,
 * REST-powered pagination, polling every 60 s.
 *
 * @package Apollo\Notif
 */

defined('ABSPATH') || exit;

if (! is_user_logged_in()) {
    wp_redirect(home_url('/acesso'));
    exit;
}

$user_id    = get_current_user_id();
$rest_url   = rest_url('apollo/v1/notifications');
$snooze_url = rest_url('apollo/v1/notifications/preferences/snooze');
$nonce      = wp_create_nonce('wp_rest');

ob_start();
?>
    <script src="https://cdn.apollo.rio.br/v1.0.0/js/forms.js" defer></script>
    <script src="https://cdn.apollo.rio.br/v1.0.0/js/gsap-TextPlugin.chat.min.js" defer></script>
    <script src="<?php echo esc_url(plugins_url('apollo-chat/assets/js/apollo-gsap-text-fx.js') . '?v=2.0.0'); ?>" defer></script>
    <?php if (defined('APOLLO_TEMPLATES_URL') && defined('APOLLO_TEMPLATES_VERSION')) : ?>
        <link rel="stylesheet" href="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/css/navbar.css'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>">
    <?php endif; ?>
    <style>
        /* ═══ VARIABLES ═══ */
        :root {
            --ff-main: "Space Grotesk", system-ui, sans-serif;
            --ff-mono: "Space Mono", monospace;
            --ff-fun: "Syne", sans-serif;
            --primary: #FF9820;
            --ink: #121214;
            --smoke: #666;
            --ghost: #999;
            --mist: #c0c0c0;
            --bg: #ffffff;
            --surface: #fafafa;
            --border: rgba(var(--rgb-d), .07);
            --radius: 24px;
            --radius-card: 16px;
            --delete: #dc2626;
            --ease: cubic-bezier(.16, 1, .3, 1);
        }

        /* ═══ RESETS ═══ */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            outline: none;
        }

        body {
            background: var(--bg);
            color: var(--ink);
            font-family: var(--ff-main);
            font-size: 14px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            min-height: 100dvh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        img {
            display: block;
            max-width: 100%;
        }

        /* ═══ NOTIFICATION CENTER ═══ */
        .notification-center {
            max-width: 520px;
            margin: 0 auto;
            padding: 32px 16px 120px;
        }

        /* ═══ HEADER ═══ */
        .ntf-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .ntf-header h1 {
            font-size: clamp(24px, 5vw, 32px);
            font-weight: 800;
            letter-spacing: -.03em;
            line-height: 1;
        }

        .clear-all {
            font-family: var(--ff-mono);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ghost);
            background: none;
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 8px 18px;
            cursor: pointer;
            transition: all .3s var(--ease);
        }

        .clear-all:hover {
            color: var(--delete);
            border-color: var(--delete);
            background: rgba(220, 38, 38, .04);
        }

        /* ═══ FILTERS ═══ */
        .ntf-filters {
            display: flex;
            gap: 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .ntf-filter {
            font-family: var(--ff-mono);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .1em;
            padding: 12px 20px;
            color: var(--ink);
            border-bottom: 2px solid var(--ink);
            background: none;
            border-top: 0;
            border-left: 0;
            border-right: 0;
        }

        .ntf-filter .ntf-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 16px;
            padding: 0 5px;
            border-radius: 100px;
            font-size: 8px;
            font-weight: 700;
            margin-left: 6px;
            background: var(--ink);
            color: #fff;
        }

        /* ═══ READ DIVIDER ═══ */
        .ntf-read-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 28px 0 16px;
            font-family: var(--ff-mono);
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: .1em;
            color: var(--mist);
        }

        .ntf-read-divider::before,
        .ntf-read-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ═══ MUTED LIST (read items) ═══ */
        .ntf-list--muted .ntf-item .ntf-content {
            opacity: .5;
        }

        .ntf-list--muted .ntf-item .ntf-title {
            color: var(--smoke);
            font-weight: 500;
        }

        .ntf-list--muted .ntf-item .ntf-icon {
            background: var(--surface);
            color: var(--mist);
        }

        /* ═══ LIST ═══ */
        .ntf-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        /* ═══ NOTIFICATION ITEM ═══ */
        .ntf-item {
            position: relative;
            border-radius: var(--radius-card);
            overflow: hidden;
            touch-action: pan-y;
            user-select: none;
        }

        /* Delete action layer (behind content) */
        .ntf-actions {
            position: absolute;
            inset: 0;
            background: var(--delete);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 24px;
            border-radius: var(--radius-card);
        }

        .ntf-delete {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(var(--rgb-t), .2);
            border: none;
            color: #fff;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform .2s var(--ease);
        }

        .ntf-delete:hover {
            transform: scale(1.1);
        }

        /* Content layer (sits on top, slides on drag) */
        .ntf-content {
            position: relative;
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-card);
            cursor: pointer;
            transition: transform .3s var(--ease), box-shadow .3s var(--ease);
            z-index: 2;
            will-change: transform;
        }

        /* Orange accent line */
        .ntf-content::before {
            content: '';
            position: absolute;
            left: 0;
            top: 8px;
            bottom: 8px;
            width: 3px;
            background: var(--primary);
            border-radius: 0 3px 3px 0;
            opacity: 0;
            transition: opacity .3s var(--ease);
        }

        .ntf-item.unread .ntf-content::before {
            opacity: 1;
        }

        .ntf-content:hover {
            box-shadow: 0 4px 16px -4px rgba(var(--rgb-d), .06);
        }

        /* ═══ ITEM PARTS ═══ */
        .ntf-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 18px;
            background: var(--card);
            color: var(--smoke);
            transition: all .3s;
        }

        .ntf-item.unread .ntf-icon {
            background: rgba(244, 95, 0, .08);
            color: var(--primary);
        }

        .ntf-text {
            flex: 1;
            min-width: 0;
        }

        .ntf-title {
            font-size: 13px;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 2px;
        }

        .ntf-item.read .ntf-title {
            color: var(--smoke);
        }

        .ntf-desc {
            font-size: 12px;
            color: var(--ghost);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .ntf-time {
            font-family: var(--ff-mono);
            font-size: 9px;
            color: var(--mist);
            text-transform: uppercase;
            letter-spacing: .03em;
            flex-shrink: 0;
            white-space: nowrap;
        }

        /* ═══ LOADING ═══ */
        .ntf-loading {
            text-align: center;
            padding: 48px 16px;
            color: var(--ghost);
        }

        .ntf-loading-txt {
            font-family: var(--ff-mono);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--ghost);
            opacity: 1;
        }

        @keyframes ntfSpin {
            from {
                transform: rotate(0);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* ═══ EMPTY STATE ═══ */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--mist);
            display: block;
            margin-bottom: 16px;
            opacity: .4;
        }

        .empty-state h3 {
            font-family: var(--ff-fun);
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .empty-state p {
            font-family: var(--ff-mono);
            font-size: 11px;
            color: var(--ghost);
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        /* ═══ LOAD MORE ═══ */
        .ntf-load-more {
            text-align: center;
            padding: 24px 0;
            display: none;
        }

        .ntf-load-more button {
            font-family: var(--ff-mono);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--ghost);
            background: none;
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 10px 24px;
            cursor: pointer;
            transition: all .3s var(--ease);
        }

        .ntf-load-more button:hover {
            color: var(--ink);
            border-color: var(--ink);
        }

        /* ═══ TRANSITIONS ═══ */
        .ntf-item.removing .ntf-content {
            transform: translateX(-100%) !important;
        }

        .ntf-item.removing {
            transition: height .3s var(--ease), opacity .3s var(--ease), margin .3s var(--ease);
            height: 0 !important;
            opacity: 0;
            margin: 0 !important;
            overflow: hidden;
        }

        /* ═══ RESPONSIVE ═══ */
        @media (max-width: 560px) {
            .notification-center {
                padding: 20px 12px 120px;
            }

            .ntf-header h1 {
                font-size: 22px;
            }

            .ntf-content {
                padding: 14px 14px;
                gap: 10px;
            }

            .ntf-icon {
                width: 36px;
                height: 36px;
                border-radius: 10px;
                font-size: 16px;
            }
        }
    </style>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => __('Notificações — Apollo::Rio', 'apollo-notif'),
            'extra_head' => $extra_head,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>

<body>

    <?php apollo_render_navbar(); ?>

    <div class="notification-center" data-a-user="<?php echo esc_attr((string) $user_id); ?>" data-a-component="notifications">

        <!-- Header -->
        <div class="ntf-header">
            <h1>Notificações</h1>
            <button class="clear-all" id="clearAll">
                <i class="ri-delete-bin-6-line" style="font-size:12px;margin-right:4px;vertical-align:-1px"></i> Limpar Tudo
            </button>
        </div>

        <!-- Filters -->
        <div class="ntf-filters">
            <span class="ntf-filter active">Não lidas <span class="ntf-count" id="countUnread">0</span></span>
        </div>

        <!-- Notification list (unread) -->
        <div class="ntf-list" id="ntfList"></div>

        <!-- Read divider -->
        <div class="ntf-read-divider" id="readDivider" style="display:none">
            <span>Lidas recentes</span>
        </div>

        <!-- Read items (max 3, muted) -->
        <div class="ntf-list ntf-list--muted" id="ntfListRead"></div>

        <!-- Empty state -->
        <div class="empty-state" id="emptyState" style="display:none">
            <i class="ri-notification-off-line"></i>
            <h3>Tudo limpo por aqui</h3>
            <p>Sem notificações no momento</p>
        </div>

        <!-- Load more -->
        <div class="ntf-load-more" id="loadMore">
            <button onclick="NtfApp.loadMore()">
                <i class="ri-arrow-down-s-line" style="font-size:12px;margin-right:4px;vertical-align:-1px"></i> Carregar mais
            </button>
        </div>

    </div>

    <!-- Swipe-to-delete notification engine -->
    <script>
        const NtfApp = (function() {
            'use strict';

            const REST = <?php echo wp_json_encode(esc_url_raw($rest_url)); ?>;
            const SNOOZE_URL = <?php echo wp_json_encode(esc_url_raw($snooze_url)); ?>;
            const NONCE = <?php echo wp_json_encode($nonce); ?>;

            const REVEAL_THRESHOLD = 80;
            const DELETE_THRESHOLD_PCT = 0.45;

            const list = document.getElementById('ntfList');
            const listRead = document.getElementById('ntfListRead');
            const readDivider = document.getElementById('readDivider');
            const emptyEl = document.getElementById('emptyState');
            const loadMoreEl = document.getElementById('loadMore');

            let currentPage = 1;
            let _ntfLoadingFX  = null;   // ApolloTextFX.loading() handle

            /* ── Helpers ── */
            function hdrs() {
                return {
                    'X-WP-Nonce': NONCE,
                    'Content-Type': 'application/json'
                };
            }

            function esc(s) {
                if (!s) return '';
                const d = document.createElement('div');
                d.textContent = String(s);
                return d.innerHTML;
            }

            const TYPE_ICONS = {
                chat: 'ri-chat-1-line',
                new_user: 'ri-user-add-line',
                wow: 'ri-emotion-happy-line',
                group_join: 'ri-team-line',
                mention: 'ri-at-line',
                new_event: 'ri-calendar-event-line',
                fav_saved: 'ri-fire-line',
                depoimento: 'ri-quill-pen-line',
                membership_upgrade: 'ri-vip-crown-line',
                coauthor_invite: 'ri-user-shared-line',
                profile_visit: 'ri-eye-line',
                new_user: 'ri-user-add-line',
                user_login: 'ri-login-circle-line',
            };

            /* ── Render single notification ── */
            function renderItem(n) {
                const el = document.createElement('div');
                const cls = n.is_read ? 'read' : 'unread';
                const icn = n.icon || TYPE_ICONS[n.type] || 'ri-notification-3-line';

                el.className = 'ntf-item ' + cls;
                el.dataset.id = n.id;
                el.dataset.type = n.type || '';

                el.innerHTML =
                    '<div class="ntf-actions">' +
                    '  <button class="ntf-delete" data-id="' + n.id + '"><i class="ri-delete-bin-line"></i></button>' +
                    '</div>' +
                    '<div class="ntf-content">' +
                    '  <div class="ntf-icon"><i class="' + esc(icn) + '"></i></div>' +
                    '  <div class="ntf-text">' +
                    '    <div class="ntf-title">' + esc(n.title) + '</div>' +
                    (n.message ? '    <div class="ntf-desc">' + esc(n.message) + '</div>' : '') +
                    '  </div>' +
                    '  <div class="ntf-time">' + esc(n.time_ago || n.created_at || '') + '</div>' +
                    '</div>';

                // Click → mark read + navigate
                const content = el.querySelector('.ntf-content');
                content.addEventListener('click', function(e) {
                    if (e.target.closest('.ntf-delete')) return;
                    markRead(n.id, n.link, el);
                });

                // Delete button
                el.querySelector('.ntf-delete').addEventListener('click', function() {
                    removeItem(el, n.id);
                });

                // Init swipe
                initSwipe(el);

                return el;
            }

            /* ── Swipe-to-delete engine ── */
            function initSwipe(item) {
                const content = item.querySelector('.ntf-content');
                let startX = 0,
                    currentX = 0,
                    isDragging = false;

                function onStart(e) {
                    const pt = e.touches ? e.touches[0] : e;
                    startX = pt.clientX;
                    currentX = 0;
                    isDragging = true;
                    content.style.transition = 'none';
                }

                function onMove(e) {
                    if (!isDragging) return;
                    const pt = e.touches ? e.touches[0] : e;
                    const dx = pt.clientX - startX;
                    // Only allow left swipe (negative)
                    currentX = Math.min(0, dx);
                    content.style.transform = 'translateX(' + currentX + 'px)';
                }

                function onEnd() {
                    if (!isDragging) return;
                    isDragging = false;
                    content.style.transition = 'transform .3s var(--ease)';

                    const vw = window.innerWidth;
                    const deleteThreshold = vw * DELETE_THRESHOLD_PCT;

                    if (Math.abs(currentX) > deleteThreshold) {
                        // Auto-delete
                        const id = parseInt(item.dataset.id, 10);
                        removeItem(item, id);
                    } else if (Math.abs(currentX) > REVEAL_THRESHOLD) {
                        // Snap to reveal delete button
                        content.style.transform = 'translateX(-80px)';
                    } else {
                        // Snap back
                        content.style.transform = 'translateX(0)';
                    }
                    currentX = 0;
                }

                // Pointer events (mouse + touch)
                content.addEventListener('pointerdown', onStart, {
                    passive: true
                });
                content.addEventListener('pointermove', onMove, {
                    passive: true
                });
                content.addEventListener('pointerup', onEnd);
                content.addEventListener('pointerleave', onEnd);

                // Touch fallback
                content.addEventListener('touchstart', onStart, {
                    passive: true
                });
                content.addEventListener('touchmove', onMove, {
                    passive: true
                });
                content.addEventListener('touchend', onEnd);
            }

            /* ── Remove item with animation ── */
            async function removeItem(el, id) {
                // Animate out
                el.style.height = el.offsetHeight + 'px';
                el.offsetHeight; // force reflow
                el.classList.add('removing');

                // API call (fire-and-forget)
                fetch(REST + '/' + id, {
                    method: 'DELETE',
                    headers: hdrs(),
                    credentials: 'same-origin'
                }).catch(function() {});

                setTimeout(function() {
                    el.remove();
                    checkEmpty();
                    updateCounts();
                }, 350);
            }

            /* ── Mark as read ── */
            async function markRead(id, link, el) {
                el.classList.remove('unread');
                el.classList.add('read');
                fetch(REST + '/' + id + '/read', {
                    method: 'POST',
                    headers: hdrs(),
                    credentials: 'same-origin'
                }).catch(function() {});
                if (link) window.location.href = link;
            }

            /* ── Check if list is empty → show empty state ── */
            function checkEmpty() {
                const hasUnread = list.querySelector('.ntf-item');
                const hasRead = listRead.querySelector('.ntf-item');
                emptyEl.style.display = (hasUnread || hasRead) ? 'none' : '';
                loadMoreEl.style.display = 'none';
            }

            /* ── Load notifications — unified view (unread + 3 read) ── */
            async function loadNotifs() {
                list.innerHTML = '<div class="ntf-loading"><p class="ntf-loading-txt" aria-live="polite">Carregando notificações...</p></div>';
                listRead.innerHTML = '';
                readDivider.style.display = 'none';
                emptyEl.style.display = 'none';
                if (typeof ApolloTextFX !== 'undefined') {
                    _ntfLoadingFX = ApolloTextFX.loading(list.querySelector('.ntf-loading-txt'), [
                        'Carregando notificações...',
                        'Buscando atualizações...',
                        'Sincronizando...',
                        'Quase lá...'
                    ], { interval: 1.4, fadeTime: 0.2 });
                }

                try {
                    // Fetch ALL unread
                    const resUnread = await fetch(REST + '?unread_only=1&per_page=100', {
                        headers: hdrs(),
                        credentials: 'same-origin'
                    });
                    const unreadData = await resUnread.json();

                    // Fetch 3 most recent read
                    const resRead = await fetch(REST + '?unread_only=0&per_page=3', {
                        headers: hdrs(),
                        credentials: 'same-origin'
                    });
                    const readData = await resRead.json();

                    if (_ntfLoadingFX) { _ntfLoadingFX.stop(); _ntfLoadingFX = null; }
                    list.innerHTML = '';

                    // Render unread items
                    if (Array.isArray(unreadData) && unreadData.length) {
                        unreadData.forEach(function(n) {
                            list.appendChild(renderItem(n));
                        });
                    }

                    // Render read items (muted)
                    if (Array.isArray(readData) && readData.length) {
                        readDivider.style.display = '';
                        readData.forEach(function(n) {
                            listRead.appendChild(renderItem(n));
                        });
                    }

                    // Update unread count badge
                    const unreadCount = Array.isArray(unreadData) ? unreadData.length : 0;
                    const countEl = document.getElementById('countUnread');
                    if (countEl) countEl.textContent = unreadCount;

                    checkEmpty();
                    loadMoreEl.style.display = 'none';
                } catch (e) {
                    console.error('apollo-notif', e);
                    if (_ntfLoadingFX) { _ntfLoadingFX.stop(); _ntfLoadingFX = null; }
                    list.innerHTML = '';
                }
            }

            /* ── Update badge counts ── */
            async function updateCounts() {
                try {
                    const res = await fetch(REST + '?per_page=1&unread_only=1', {
                        headers: hdrs(),
                        credentials: 'same-origin'
                    });
                    const unread = parseInt(res.headers.get('X-WP-Total') || '0', 10);
                    const countEl = document.getElementById('countUnread');
                    if (countEl) countEl.textContent = unread;
                } catch (_) {}
            }

            /* ── Clear All (cascade delete) ── */
            document.getElementById('clearAll').addEventListener('click', async function() {
                const allItems = Array.from(document.querySelectorAll('.ntf-item'));
                if (!allItems.length) return;

                // Cascade animation — remove one by one
                for (let i = 0; i < allItems.length; i++) {
                    const item = allItems[i];
                    item.style.height = item.offsetHeight + 'px';
                    item.offsetHeight;

                    setTimeout(function() {
                        item.classList.add('removing');
                        setTimeout(function() {
                            item.remove();
                        }, 350);
                    }, i * 60);
                }

                // After cascade finishes, show empty state
                setTimeout(function() {
                    readDivider.style.display = 'none';
                    checkEmpty();
                }, allItems.length * 60 + 400);

                // API: mark all read then delete read
                fetch(REST + '/read-all', {
                    method: 'POST',
                    headers: hdrs(),
                    credentials: 'same-origin'
                }).then(function() {
                    return fetch(REST + '/read', {
                        method: 'DELETE',
                        headers: hdrs(),
                        credentials: 'same-origin'
                    });
                }).catch(function() {});
            });

            /* ── Load more (disabled in unified view) ── */
            function loadMore() {}

            /* ── Polling — every 60 s ── */
            let lastPoll = new Date().toISOString().replace('T', ' ').slice(0, 19);
            setInterval(async function() {
                try {
                    const res = await fetch(
                        REST + '?since=' + encodeURIComponent(lastPoll) + '&per_page=5&unread_only=1', {
                            headers: hdrs(),
                            credentials: 'same-origin'
                        }
                    );
                    const data = await res.json();
                    if (Array.isArray(data) && data.length) {
                        data.reverse().forEach(function(n) {
                            if (!document.querySelector('.ntf-item[data-id="' + n.id + '"]')) {
                                list.prepend(renderItem(n));
                            }
                        });
                        lastPoll = new Date().toISOString().replace('T', ' ').slice(0, 19);
                        emptyEl.style.display = 'none';
                        updateCounts();
                    }
                } catch (_) {}
            }, 60000);

            /* ── Init ── */
            function init() {
                loadNotifs();
            }

            // Wait for Apollo CDN ready if available, else DOMContentLoaded
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                init();
            } else {
                document.addEventListener('DOMContentLoaded', init);
            }
            document.addEventListener('apollo:ready', function() {
                if (typeof ApolloTextFX !== 'undefined') {
                    ApolloTextFX.initTimeAgoRefresh(15000);
                }
            });

            return {
                loadMore: loadMore
            };
        })();
    </script>

</body>

</html>