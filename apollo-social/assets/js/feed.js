/**
 * Apollo Feed — Main JS Controller
 *
 * Handles: compose, 280 char counting (URL-excluded), URL embed detection,
 * media URL bar (SoundCloud/Spotify/Event/YouTube), feed loading,
 * infinite scroll, delete, safety modal, toasts.
 *
 * Security: NO image upload. Posts are text + URL only.
 *
 * @package Apollo\Social
 * @since   2.1.0
 */
var ApolloFeed = (function () {
    'use strict';

    /* ─── Config ────────────────────────────────────────────────── */
    let REST, NONCE, LIMIT, MY_ID;

    /* ─── URL Patterns (mirror PHP ContentParser) ─────────────── */
    const RE_URL           = /https?:\/\/[^\s<>"']+/gi;
    const RE_APOLLO_EVENT  = /(?:https?:\/\/)?(?:www\.)?(?:apollo\.rio\.br|localhost[:\d]*)\/evento\/([a-zA-Z0-9_-]+)\/?/i;
    const RE_SOUNDCLOUD    = /(?:https?:\/\/)?(?:www\.)?soundcloud\.com\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)/i;
    const RE_SPOTIFY       = /(?:https?:\/\/)?open\.spotify\.com\/(?:intl-[a-z]+\/)?(track|album|playlist)\/([a-zA-Z0-9]+)/i;
    const RE_YOUTUBE       = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/i;

    /* ─── DOM Refs ──────────────────────────────────────────────── */
    let $textarea, $postBtn, $charCounter, $charText, $charRingFill;
    let $container, $loadMore, $loadMoreBtn, $feedEnd, $embedPreview;
    let $deleteModal, $compose;
    let $urlBar, $urlInput, $urlIcon, $urlAddBtn, $urlCloseBtn;
    let $safetyModal, $safetyCheck, $safetyProceed, $safetyCancel;

    let currentPage    = 1;
    let currentComp    = '';
    let isLoading      = false;
    let deleteTarget   = null;
    let lastDetectedUrls = '';
    let activeMediaType  = null;
    let safetyCallback   = null;

    const RING_CIRCUMFERENCE = 97.4; // 2 * PI * 15.5

    /* ─── HELPERS ───────────────────────────────────────────────── */
    function h() {
        return { 'X-WP-Nonce': NONCE, 'Content-Type': 'application/json' };
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function relativeTime(dateStr) {
        if (!dateStr) return '';
        var now  = Date.now();
        var then = new Date(dateStr.replace(' ', 'T') + 'Z').getTime();
        var diff = Math.floor((now - then) / 1000);
        if (diff < 60)       return diff + 's';
        if (diff < 3600)     return Math.floor(diff / 60) + 'min';
        if (diff < 86400)    return Math.floor(diff / 3600) + 'h';
        if (diff < 604800)   return Math.floor(diff / 86400) + 'd';
        if (diff < 31536000) return Math.floor(diff / 604800) + 'w';
        return Math.floor(diff / 31536000) + 'y';
    }

    /**
     * Apollo standard time-ago HTML block.
     * Input: compact string e.g. '53min', '2h', '7d'
     * Output: <i class="tempo-v"></i>&nbsp;<span class="time-ago">N</span><span class="when-ago">unit</span>
     */
    function tempoHTML(str) {
        if (!str) return '';
        var m = String(str).match(/^(\d+)([a-z]+)$/i);
        var num  = m ? m[1] : str;
        var unit = m ? m[2] : '';
        return '<i class="tempo-v"></i>\u00a0<span class="time-ago">' + num + '</span><span class="when-ago">' + unit + '</span>';
    }

    /* ─── CHARACTER COUNTER ─────────────────────────────────────── */
    function updateCharCounter() {
        var text = $textarea.value;
        // Strip URLs from character count
        var textOnly = text.replace(RE_URL, '').replace(/\s+/g, ' ').trim();
        var count    = textOnly.length;
        var remaining = LIMIT - count;

        $charText.textContent = remaining;

        // Ring animation
        var progress = Math.min(count / LIMIT, 1);
        var offset   = RING_CIRCUMFERENCE * (1 - progress);
        $charRingFill.style.strokeDashoffset = offset;

        // State classes
        $charCounter.classList.remove('warn', 'danger', 'over');
        if (remaining < 0) {
            $charCounter.classList.add('over');
            $postBtn.disabled = true;
        } else if (remaining <= 20) {
            $charCounter.classList.add('danger');
            $postBtn.disabled = false;
        } else if (remaining <= 50) {
            $charCounter.classList.add('warn');
            $postBtn.disabled = false;
        } else {
            $postBtn.disabled = false;
        }

        // Disable if truly empty (no text AND no URLs)
        if (count === 0 && !text.match(RE_URL)) {
            $postBtn.disabled = true;
        }

        detectEmbeds(text);
    }

    /* Auto-resize textarea */
    function autoResize() {
        $textarea.style.height = 'auto';
        $textarea.style.height = Math.min($textarea.scrollHeight, 200) + 'px';
    }

    /* ─── EMBED DETECTION (Compose Preview) ─────────────────────── */
    function detectEmbeds(text) {
        var urls = text.match(RE_URL) || [];
        var sig  = urls.join('|');
        if (sig === lastDetectedUrls) return;
        lastDetectedUrls = sig;

        if (!urls.length) {
            $embedPreview.style.display = 'none';
            $embedPreview.innerHTML = '';
            return;
        }

        var html = '';
        for (var i = 0; i < urls.length; i++) {
            var url = urls[i];
            var m;

            if ((m = url.match(RE_APOLLO_EVENT))) {
                html += '<div class="embed-preview-item embed-preview-event">'
                    + '<i class="ri-calendar-event-fill"></i>'
                    + '<span>Evento Apollo: <strong>' + esc(m[1]) + '</strong></span>'
                    + '<i class="ri-close-line embed-preview-remove" data-url="' + esc(url) + '"></i>'
                    + '</div>';
            } else if ((m = url.match(RE_SOUNDCLOUD))) {
                html += '<div class="embed-preview-item embed-preview-soundcloud">'
                    + '<i class="ri-soundcloud-fill"></i>'
                    + '<span>SoundCloud: <strong>' + esc(m[1] + '/' + m[2]) + '</strong></span>'
                    + '<i class="ri-close-line embed-preview-remove" data-url="' + esc(url) + '"></i>'
                    + '</div>';
            } else if ((m = url.match(RE_SPOTIFY))) {
                html += '<div class="embed-preview-item embed-preview-spotify">'
                    + '<i class="ri-spotify-fill"></i>'
                    + '<span>Spotify ' + esc(m[1]) + '</span>'
                    + '<i class="ri-close-line embed-preview-remove" data-url="' + esc(url) + '"></i>'
                    + '</div>';
            } else if ((m = url.match(RE_YOUTUBE))) {
                html += '<div class="embed-preview-item embed-preview-youtube">'
                    + '<i class="ri-youtube-fill"></i>'
                    + '<span>YouTube: <strong>' + esc(m[1]) + '</strong></span>'
                    + '<i class="ri-close-line embed-preview-remove" data-url="' + esc(url) + '"></i>'
                    + '</div>';
            }
        }

        if (html) {
            $embedPreview.innerHTML = html;
            $embedPreview.style.display = '';
            $embedPreview.querySelectorAll('.embed-preview-remove').forEach(function (btn) {
                btn.onclick = function () {
                    var u = this.dataset.url;
                    $textarea.value = $textarea.value.replace(u, '').trim();
                    updateCharCounter();
                    autoResize();
                };
            });
        } else {
            $embedPreview.style.display = 'none';
            $embedPreview.innerHTML = '';
        }
    }

    /* ─── POST ──────────────────────────────────────────────────── */
    function handlePost() {
        var content = $textarea.value.trim();
        if (!content) return;

        $postBtn.disabled = true;
        $postBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i>';

        fetch(REST + '/feed/post', {
            method: 'POST',
            headers: h(),
            credentials: 'same-origin',
            body: JSON.stringify({ content: content })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.error) {
                showToast(data.error, 'error');
            } else if (data.id) {
                $textarea.value = '';
                $embedPreview.style.display = 'none';
                $embedPreview.innerHTML = '';
                lastDetectedUrls = '';
                autoResize();
                updateCharCounter();
                showToast('Publicado!', 'success');
                currentPage = 1;
                loadFeed(1, false);
            }
        })
        .catch(function (e) {
            console.error(e);
            showToast('Erro ao publicar', 'error');
        })
        .finally(function () {
            $postBtn.disabled = false;
            $postBtn.innerHTML = 'Publicar';
        });
    }

    /* ─── FEED LOADING ──────────────────────────────────────────── */
    function loadFeed(page, append) {
        if (isLoading) return;
        isLoading = true;

        if (!append) {
            $container.innerHTML = '<div class="feed-loading"><div class="feed-spinner"></div><span>Carregando feed...</span></div>';
            $feedEnd.style.display = 'none';
        } else {
            $loadMoreBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Carregando...';
            $loadMoreBtn.disabled = true;
        }

        var url = REST + '/feed?page=' + page + '&per_page=15';
        if (currentComp) url += '&component=' + currentComp;

        fetch(url, { headers: h(), credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (!append) $container.innerHTML = '';

            if (data.length === 0 && page === 1) {
                $container.innerHTML = renderEmptyState();
                $loadMore.style.display = 'none';
                $feedEnd.style.display = 'none';
                return;
            }

            for (var i = 0; i < data.length; i++) {
                $container.appendChild(renderFeedItem(data[i]));
            }

            if (data.length < 15) {
                $loadMore.style.display = 'none';
                $feedEnd.style.display = '';
            } else {
                $loadMore.style.display = '';
                $feedEnd.style.display = 'none';
            }
        })
        .catch(function (e) {
            console.error(e);
            if (!append) {
                $container.innerHTML = '<div class="feed-empty"><i class="ri-error-warning-line"></i><span>Erro ao carregar feed</span></div>';
            }
        })
        .finally(function () {
            $loadMoreBtn.innerHTML = '<i class="ri-arrow-down-line"></i> Carregar mais';
            $loadMoreBtn.disabled = false;
            isLoading = false;
        });
    }

    /* ─── RENDER FEED ITEM ──────────────────────────────────────── */
    function renderFeedItem(item) {
        var el = document.createElement('article');
        el.className = 'feed-item';
        el.dataset.id = item.id;

        // Badge
        var badgeHtml = '';
        if (item.badge && item.badge.type && item.badge.type !== 'nao-verificado' && item.badge.ri_icon) {
            badgeHtml = '<i class="' + esc(item.badge.ri_icon) + ' feed-badge" style="color:' + esc(item.badge.color) + ';" title="' + esc(item.badge.label) + '"></i>';
        }

        // Component tag
        var tagHtml = '';
        if (item.component === 'events') {
            tagHtml = '<span class="feed-tag feed-tag-event"><i class="ri-calendar-event-fill"></i> Evento</span>';
        } else if (item.component === 'classifieds') {
            tagHtml = '<span class="feed-tag feed-tag-classified"><i class="ri-exchange-box-line"></i> Anúncio</span>';
        } else if (item.component === 'content' && item.type === 'new_post') {
            tagHtml = '<span class="feed-tag feed-tag-content"><i class="ri-article-fill"></i> Artigo</span>';
        }

        // TODO 05: Comment label — CPT pages (events, loc, dj) → "Depoimentos", else → "Comentários"
        var commentLabel = 'Comentários';
        if (item.component === 'events' || item.component === 'loc' || item.component === 'djs') {
            commentLabel = 'Depoimentos';
        }

        // Time
        var timeStr = item.time_ago || relativeTime(item.created_at);

        // Delete button (own posts or moderator)
        var canDelete = parseInt(item.user_id) === MY_ID;
        var deleteHtml = canDelete
            ? '<button class="feed-action-btn feed-delete-btn" data-id="' + item.id + '" title="Excluir"><i class="ri-more-fill"></i></button>'
            : '';

        // Content — use server-rendered HTML with embeds if available
        var contentHtml = '';
        if (item.content_html) {
            contentHtml = '<div class="feed-content">' + item.content_html + '</div>';
        } else if (item.content) {
            contentHtml = '<div class="feed-content"><div class="post-text">' + esc(item.content) + '</div></div>';
        }

        // Primary link (for content/events/classifieds — NOT social posts)
        var linkHtml = '';
        if (item.primary_link && item.component !== 'social') {
            linkHtml = '<a href="' + esc(item.primary_link) + '" class="feed-primary-link" target="_blank">'
                + '<i class="ri-external-link-line"></i> Ver detalhes</a>';
        }

        el.innerHTML =
            '<div class="feed-item-header">'
            +   '<a href="' + esc(item.profile_url || '') + '" class="feed-avatar-link">'
            +       '<img src="' + esc(item.avatar_url || '') + '" alt="" class="feed-avatar">'
            +   '</a>'
            +   '<div class="feed-item-meta">'
            +       '<div class="feed-item-meta-top">'
            +           '<a href="' + esc(item.profile_url || '') + '" class="feed-author">' + esc(item.display_name || '') + '</a>'
            +           badgeHtml
            +           '<span class="feed-handle">@' + esc(item.user_login || '') + '</span>'
            +           '<span class="feed-dot">&middot;</span>'
            +           '<time class="feed-time" title="' + esc(item.created_at || '') + '">' + tempoHTML(timeStr) + '</time>'
            +           tagHtml
            +       '</div>'
            +       (item.action_text && item.component !== 'social'
                        ? '<div class="feed-action-label">' + esc(item.action_text) + '</div>'
                        : '')
            +   '</div>'
            +   deleteHtml
            + '</div>'
            + contentHtml
            + linkHtml
            + '<div class="feed-item-actions">'
            +   '<button class="feed-action-btn" title="' + esc(commentLabel) + '"><i class="ri-chat-1-line"></i><span>' + esc(commentLabel) + '</span></button>'
            +   '<button class="feed-action-btn" title="Compartilhar"><i class="ri-repeat-line"></i></button>'
            +   '<button class="feed-action-btn feed-wow-btn" title="Wow"><i class="ri-fire-line"></i><span>0</span></button>'
            +   '<button class="feed-action-btn" title="Salvar"><i class="ri-bookmark-line"></i></button>'
            +   (item.component === 'classifieds'
                    ? '<button class="feed-action-btn feed-classified-chat-btn" title="Contatar anunciante" data-a-user="' + item.user_id + '" data-item-id="' + item.id + '"><i class="ri-chat-1-fill"></i><span>Contatar</span></button>'
                    : '')
            +   '<button class="feed-action-btn feed-report-btn" title="Reportar" data-apollo-report-trigger><i class="ri-flag-line"></i></button>'
            + '</div>';

        // Bind delete
        var btns = el.querySelectorAll('.feed-delete-btn');
        for (var i = 0; i < btns.length; i++) {
            (function (b) {
                b.onclick = function (e) {
                    e.stopPropagation();
                    openDeleteModal(parseInt(b.dataset.id));
                };
            })(btns[i]);
        }

        // Bind classified chat → safety modal (TODO 06)
        var chatBtns = el.querySelectorAll('.feed-classified-chat-btn');
        for (var j = 0; j < chatBtns.length; j++) {
            (function (b) {
                b.onclick = function (e) {
                    e.stopPropagation();
                    openSafetyModal(function () {
                        // After consent → open chat/inbox
                        var chatUrl = '/chat?user=' + b.dataset.userId + '&ref=classified-' + b.dataset.itemId;
                        window.location.href = chatUrl;
                    });
                };
            })(chatBtns[j]);
        }

        return el;
    }

    function renderEmptyState() {
        return '<div class="feed-empty">'
            + '<i class="ri-chat-smile-2-line"></i>'
            + '<h3>Nenhuma atividade ainda</h3>'
            + '<p>Seja o primeiro a publicar algo!</p>'
            + '</div>';
    }

    /* ─── DELETE MODAL ──────────────────────────────────────────── */
    function openDeleteModal(id) {
        deleteTarget = id;
        $deleteModal.style.display = 'flex';
    }

    function closeDeleteModal() {
        deleteTarget = null;
        $deleteModal.style.display = 'none';
    }

    function confirmDelete() {
        if (!deleteTarget) return;
        var btn = document.getElementById('modal-delete-confirm');
        btn.disabled = true;
        btn.textContent = 'Excluindo...';

        fetch(REST + '/activity/' + deleteTarget, {
            method: 'DELETE',
            headers: h(),
            credentials: 'same-origin'
        })
        .then(function () {
            var el = document.querySelector('.feed-item[data-id="' + deleteTarget + '"]');
            if (el) {
                el.style.opacity = '0';
                el.style.transform = 'scale(0.95)';
                setTimeout(function () { el.remove(); }, 300);
            }
            showToast('Post excluído', 'success');
        })
        .catch(function () {
            showToast('Erro ao excluir', 'error');
        })
        .finally(function () {
            btn.disabled = false;
            btn.textContent = 'Excluir';
            closeDeleteModal();
        });
    }

    /* ─── TOAST ─────────────────────────────────────────────────── */
    function showToast(msg, type) {
        var toast = document.createElement('div');
        toast.className = 'apollo-toast apollo-toast-' + (type || 'info');
        toast.innerHTML = '<span>' + esc(msg) + '</span>';
        document.body.appendChild(toast);
        requestAnimationFrame(function () { toast.classList.add('show'); });
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }

    /* ─── SAFETY MODAL (TODO 06 — Classifieds Disclaimer) ─────── */
    function openSafetyModal(callback) {
        safetyCallback = callback;
        $safetyCheck.checked = false;
        $safetyProceed.disabled = true;
        $safetyModal.style.display = 'flex';
    }

    function closeSafetyModal() {
        safetyCallback = null;
        $safetyModal.style.display = 'none';
    }

    /* ─── MEDIA URL BAR (TODO 04 — SoundCloud/Spotify/Event/YouTube) ── */
    var mediaConfig = {
        soundcloud: { icon: 'ri-soundcloud-fill', placeholder: 'Cole a URL do SoundCloud...', color: '#ff5500' },
        spotify:    { icon: 'ri-spotify-fill',     placeholder: 'Cole a URL do Spotify...',     color: '#1db954' },
        event:      { icon: 'ri-calendar-event-fill', placeholder: 'Cole a URL do evento Apollo...', color: '#7c3aed' },
        youtube:    { icon: 'ri-youtube-fill',     placeholder: 'Cole a URL do YouTube...',     color: '#ff0000' }
    };

    function openUrlBar(type) {
        var cfg = mediaConfig[type];
        if (!cfg) return;
        activeMediaType = type;
        $urlIcon.className = cfg.icon;
        $urlIcon.style.color = cfg.color;
        $urlInput.placeholder = cfg.placeholder;
        $urlInput.value = '';
        $urlBar.style.display = '';
        $urlInput.focus();
        // Highlight active button
        document.querySelectorAll('.compose-media-btn').forEach(function (b) {
            b.classList.toggle('active', b.dataset.media === type);
        });
    }

    function closeUrlBar() {
        activeMediaType = null;
        $urlBar.style.display = 'none';
        $urlInput.value = '';
        document.querySelectorAll('.compose-media-btn').forEach(function (b) {
            b.classList.remove('active');
        });
    }

    function addUrlFromBar() {
        var url = $urlInput.value.trim();
        if (!url) return;

        // Validate URL matches the expected media type
        var valid = false;
        if (activeMediaType === 'soundcloud' && RE_SOUNDCLOUD.test(url)) valid = true;
        if (activeMediaType === 'spotify' && RE_SPOTIFY.test(url)) valid = true;
        if (activeMediaType === 'event' && RE_APOLLO_EVENT.test(url)) valid = true;
        if (activeMediaType === 'youtube' && RE_YOUTUBE.test(url)) valid = true;

        if (!valid) {
            showToast('URL inválida para ' + activeMediaType, 'error');
            return;
        }

        // Append URL to textarea content
        var text = $textarea.value;
        if (text && !text.endsWith(' ') && !text.endsWith('\n')) {
            text += ' ';
        }
        $textarea.value = text + url;
        updateCharCounter();
        autoResize();
        closeUrlBar();
        showToast('URL adicionada!', 'success');
    }

    /* ─── TRENDING SIDEBAR ──────────────────────────────────────── */
    function loadTrending() {
        var $trending = document.getElementById('sidebar-trending');
        if (!$trending) return;

        fetch(REST + '/feed?per_page=5&component=events', {
            headers: h(),
            credentials: 'same-origin'
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.length) {
                $trending.innerHTML = data.map(function (item) {
                    return '<a href="' + esc(item.primary_link || '#') + '" class="trending-item">'
                        + '<span class="trending-title">' + esc(item.action_text || '') + '</span>'
                        + '<span class="trending-time">' + tempoHTML(item.time_ago || relativeTime(item.created_at)) + '</span>'
                        + '</a>';
                }).join('');
            } else {
                $trending.innerHTML = '<div class="sidebar-placeholder">Nenhum evento recente</div>';
            }
        })
        .catch(function () {
            $trending.innerHTML = '<div class="sidebar-placeholder">&mdash;</div>';
        });
    }

    /* ─── INIT ──────────────────────────────────────────────────── */
    function init(cfg) {
        REST  = cfg.rest;
        NONCE = cfg.nonce;
        LIMIT = cfg.limit || 280;
        MY_ID = cfg.userId;

        // DOM refs
        $textarea     = document.getElementById('feed-compose-text');
        $postBtn      = document.getElementById('feed-post-btn');
        $charCounter  = document.getElementById('char-counter');
        $charText     = document.getElementById('char-count-text');
        $charRingFill = document.querySelector('.char-ring-fill');
        $container    = document.getElementById('feed-container');
        $loadMore     = document.getElementById('feed-load-more');
        $loadMoreBtn  = document.getElementById('feed-load-more-btn');
        $feedEnd      = document.getElementById('feed-end');
        $embedPreview = document.getElementById('compose-embed-preview');
        $deleteModal  = document.getElementById('modal-delete');
        $compose      = document.getElementById('feed-compose');

        // Media URL bar refs (TODO 04)
        $urlBar      = document.getElementById('compose-url-bar');
        $urlInput    = document.getElementById('compose-url-input');
        $urlIcon     = document.getElementById('compose-url-icon');
        $urlAddBtn   = document.getElementById('compose-url-add');
        $urlCloseBtn = document.getElementById('compose-url-close');

        // Safety modal refs (TODO 06)
        $safetyModal   = document.getElementById('modal-safety');
        $safetyCheck   = document.getElementById('safety-consent-check');
        $safetyProceed = document.getElementById('safety-proceed-btn');
        $safetyCancel  = document.getElementById('safety-cancel-btn');

        // Events — textarea
        $textarea.addEventListener('input', function () {
            updateCharCounter();
            autoResize();
        });
        $textarea.addEventListener('focus', function () {
            $compose.classList.add('focused');
        });
        $textarea.addEventListener('blur', function () {
            if (!$textarea.value.trim()) {
                $compose.classList.remove('focused');
            }
        });
        $textarea.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                if (!$postBtn.disabled) handlePost();
            }
        });

        // Post button
        $postBtn.onclick = handlePost;

        // Load more
        $loadMoreBtn.onclick = function () {
            currentPage++;
            loadFeed(currentPage, true);
        };

        // Tabs
        var tabs = document.querySelectorAll('.feed-tab');
        for (var i = 0; i < tabs.length; i++) {
            (function (btn) {
                btn.onclick = function () {
                    for (var j = 0; j < tabs.length; j++) tabs[j].classList.remove('active');
                    btn.classList.add('active');
                    currentComp = btn.dataset.component;
                    currentPage = 1;
                    loadFeed(1, false);
                };
            })(tabs[i]);
        }

        // Delete modal
        document.getElementById('modal-delete-confirm').onclick = confirmDelete;
        $deleteModal.onclick = function (e) {
            if (e.target === $deleteModal) closeDeleteModal();
        };

        // Expose closeDeleteModal globally (used by cancel button onclick)
        window.closeDeleteModal = closeDeleteModal;

        // Media URL bar events (TODO 04)
        if ($urlAddBtn) {
            $urlAddBtn.onclick = addUrlFromBar;
        }
        if ($urlCloseBtn) {
            $urlCloseBtn.onclick = closeUrlBar;
        }
        if ($urlInput) {
            $urlInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addUrlFromBar();
                }
                if (e.key === 'Escape') {
                    closeUrlBar();
                }
            });
        }
        // Media action buttons
        document.querySelectorAll('.compose-media-btn').forEach(function (btn) {
            btn.onclick = function () {
                var type = btn.dataset.media;
                if (activeMediaType === type) {
                    closeUrlBar();
                } else {
                    openUrlBar(type);
                }
            };
        });

        // Safety modal events (TODO 06)
        if ($safetyCheck) {
            $safetyCheck.onchange = function () {
                $safetyProceed.disabled = !this.checked;
            };
        }
        if ($safetyProceed) {
            $safetyProceed.onclick = function () {
                if (typeof safetyCallback === 'function') {
                    safetyCallback();
                }
                closeSafetyModal();
            };
        }
        if ($safetyCancel) {
            $safetyCancel.onclick = closeSafetyModal;
        }
        if ($safetyModal) {
            $safetyModal.onclick = function (e) {
                if (e.target === $safetyModal) closeSafetyModal();
            };
        }

        // Infinite scroll
        var scrollTimer;
        window.addEventListener('scroll', function () {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(function () {
                if (isLoading) return;
                var threshold = document.documentElement.scrollHeight - window.innerHeight - 400;
                if (window.scrollY >= threshold && $loadMore.style.display !== 'none') {
                    currentPage++;
                    loadFeed(currentPage, true);
                }
            }, 100);
        });

        // Initial load
        loadFeed(1, false);
        loadTrending();
        updateCharCounter();
    }

    /* ─── PUBLIC API ────────────────────────────────────────────── */
    return { init: init };

})();
