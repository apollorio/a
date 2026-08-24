/**
 * Apollo Explore — Main JS Controller (v3.0.0)
 *
 * Handles: preloader (page-loader), tab switching (6 tabs with content-panel),
 * compose with 280-char counting (URL-excluded), URL embed detection + media bar,
 * feed loading via REST, post rendering (.post with .profile-identity, .act-btn,
 * SoundCloud waveform via buildWave, Spotify native player, YouTube, event cards,
 * classified tickets), infinite scroll, wow (brain icon) / depoimento toggles,
 * delete + safety modals, settings panel (icon toggles), sidebar hydration (.sb-*),
 * toasts, GSAP ScrollTrigger reveals, tab-bar scroll effect.
 *
 * Security: NO image upload. Posts are text + URL only.
 *
 * @package Apollo\Social
 * @since   3.0.0
 */
var ApolloExplore = (function () {
    'use strict';

    /* ═══════════════════════════════════════════════════════════════
       CONFIG
       ═══════════════════════════════════════════════════════════════ */
    let REST, NONCE, LIMIT, MY_ID;

    /* ─── URL Patterns (mirror PHP ContentParser) ─────────────── */
    const RE_URL          = /https?:\/\/[^\s<>"']+/gi;
    const RE_APOLLO_EVENT = /(?:https?:\/\/)?(?:www\.)?(?:apollo\.rio\.br|localhost[:\d]*)\/evento\/([a-zA-Z0-9_-]+)\/?/i;
    const RE_SOUNDCLOUD   = /(?:https?:\/\/)?(?:www\.)?soundcloud\.com\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)/i;
    const RE_SPOTIFY      = /(?:https?:\/\/)?open\.spotify\.com\/(?:intl-[a-z]+\/)?(track|album|playlist)\/([a-zA-Z0-9]+)/i;
    const RE_YOUTUBE      = /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/i;

    /* ─── DOM Refs ──────────────────────────────────────────────── */
    let $textarea, $postBtn, $charCounter, $charText, $charRingFill;
    let $container, $loadMore, $loadMoreBtn, $feedEnd, $embedPreview;
    let $deleteModal, $compose, $toastContainer;
    let $urlBar, $urlInput, $urlIcon, $urlAddBtn, $urlCloseBtn;
    let $safetyModal, $safetyCheck, $safetyProceed, $safetyCancel;
    let $feedColumn;

    /* ─── State ─────────────────────────────────────────────────── */
    let currentPage      = 1;
    let currentTab       = 'feed';
    let currentComponent = '';
    let isLoading        = false;
    let deleteTarget     = null;
    let lastDetectedUrls = '';
    let activeMediaType  = null;
    let safetyCallback   = null;

    const RING_CIRCUMFERENCE = 97.4; // 2 × π × 15.5

    /* Tab → REST component mapping */
    const TAB_COMPONENT = {
        feed:     '',
        events:   'events',
        comunas:  'groups',
        market:   'classifieds',
        favs:     'fav',
        settings: null
    };

    /* ═══════════════════════════════════════════════════════════════
       HELPERS
       ═══════════════════════════════════════════════════════════════ */
    function h() {
        return { 'X-WP-Nonce': NONCE, 'Content-Type': 'application/json' };
    }

    function esc(s) {
        const d = document.createElement('div');
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

    /* ═══════════════════════════════════════════════════════════════
       PRELOADER
       ═══════════════════════════════════════════════════════════════ */
    function dismissPreloader() {
        const el = document.getElementById('pageLoader');
        if (!el) return;
        if (typeof gsap !== 'undefined') {
            gsap.to(el, {
                opacity: 0,
                duration: 0.6,
                ease: 'power3.inOut',
                delay: 0.4,
                onComplete: function () { el.style.display = 'none'; }
            });
        } else {
            setTimeout(function () { el.style.display = 'none'; }, 600);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
       TAB SWITCHING (content-panel based)
       ═══════════════════════════════════════════════════════════════ */
    window.switchTab = function (tab, el) {
        if (tab === currentTab) return;
        currentTab = tab;

        /* Update active state on tab-items */
        document.querySelectorAll('.tab-item').forEach(function (btn) {
            var isActive = btn.dataset.tab === tab;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        /* Show/hide content panels */
        document.querySelectorAll('.content-panel').forEach(function (panel) {
            panel.classList.remove('active');
        });
        var targetPanel = document.getElementById('tab-' + tab);
        if (targetPanel) {
            targetPanel.classList.add('active');
        }

        /* If settings, no feed load needed */
        if (tab === 'settings') return;

        /* For feed tab, reload the feed */
        if (tab === 'feed') {
            currentComponent = '';
            currentPage = 1;
            loadFeed(1, false);
        }
    };

    /* ═══════════════════════════════════════════════════════════════
       TAB BAR SCROLL EFFECT
       ═══════════════════════════════════════════════════════════════ */
    function initTabBarScroll() {
        var tabBar = document.getElementById('tab-bar');
        if (!tabBar) return;
        var scrolled = false;
        window.addEventListener('scroll', function () {
            var nowScrolled = window.scrollY > 10;
            if (nowScrolled !== scrolled) {
                scrolled = nowScrolled;
                tabBar.classList.toggle('scrolled', scrolled);
            }
        }, { passive: true });
    }

    /* ═══════════════════════════════════════════════════════════════
       CHARACTER COUNTER
       ═══════════════════════════════════════════════════════════════ */
    function updateCharCounter() {
        const text     = $textarea.value;
        const textOnly = text.replace(RE_URL, '').replace(/\s+/g, ' ').trim();
        const count    = textOnly.length;
        const remaining = LIMIT - count;

        $charText.textContent = remaining;

        /* Ring */
        const progress = Math.min(count / LIMIT, 1);
        $charRingFill.style.strokeDashoffset = RING_CIRCUMFERENCE * (1 - progress);

        /* State classes */
        $charCounter.classList.remove('warn', 'danger', 'over');
        if (remaining < 0)       { $charCounter.classList.add('over');   $postBtn.disabled = true;  }
        else if (remaining <= 20) { $charCounter.classList.add('danger'); $postBtn.disabled = false; }
        else if (remaining <= 50) { $charCounter.classList.add('warn');   $postBtn.disabled = false; }
        else                       { $postBtn.disabled = false; }

        /* Empty → disabled */
        if (count === 0 && !text.match(RE_URL)) $postBtn.disabled = true;

        detectEmbeds(text);
    }

    function autoResize() {
        $textarea.style.height = 'auto';
        $textarea.style.height = Math.min($textarea.scrollHeight, 200) + 'px';
    }

    /* ═══════════════════════════════════════════════════════════════
       EMBED DETECTION (compose preview)
       ═══════════════════════════════════════════════════════════════ */
    function detectEmbeds(text) {
        const urls = text.match(RE_URL) || [];
        const sig  = urls.join('|');
        if (sig === lastDetectedUrls) return;
        lastDetectedUrls = sig;

        if (!urls.length) {
            $embedPreview.style.display = 'none';
            $embedPreview.innerHTML = '';
            return;
        }

        let html = '';
        urls.forEach(function (url) {
            var m;
            if ((m = url.match(RE_APOLLO_EVENT))) {
                html += embedPreviewTag('ri-calendar-event-fill', 'Evento Apollo: <strong>' + esc(m[1]) + '</strong>', url);
            } else if ((m = url.match(RE_SOUNDCLOUD))) {
                html += embedPreviewTag('ri-soundcloud-fill', 'SoundCloud: <strong>' + esc(m[1] + '/' + m[2]) + '</strong>', url);
            } else if ((m = url.match(RE_SPOTIFY))) {
                html += embedPreviewTag('ri-spotify-fill', 'Spotify ' + esc(m[1]), url);
            } else if ((m = url.match(RE_YOUTUBE))) {
                html += embedPreviewTag('ri-youtube-fill', 'YouTube: <strong>' + esc(m[1]) + '</strong>', url);
            }
        });

        if (html) {
            $embedPreview.innerHTML = html;
            $embedPreview.style.display = '';
            $embedPreview.querySelectorAll('.embed-preview-remove').forEach(function (btn) {
                btn.onclick = function () {
                    $textarea.value = $textarea.value.replace(this.dataset.url, '').trim();
                    updateCharCounter();
                    autoResize();
                };
            });
        } else {
            $embedPreview.style.display = 'none';
            $embedPreview.innerHTML = '';
        }
    }

    function embedPreviewTag(icon, label, url) {
        return '<div class="embed-preview-item">'
            + '<i class="' + icon + '"></i>'
            + '<span>' + label + '</span>'
            + '<i class="ri-close-line embed-preview-remove" data-url="' + esc(url) + '"></i>'
            + '</div>';
    }

    /* ═══════════════════════════════════════════════════════════════
       POST (COMPOSE)
       ═══════════════════════════════════════════════════════════════ */
    function handlePost() {
        const content = $textarea.value.trim();
        if (!content) return;

        $postBtn.disabled = true;
        $postBtn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i>';

        fetch(REST + '/feed/post', {
            method: 'POST',
            headers: h(),
            credentials: 'same-origin',
            body: JSON.stringify({ content: content })
        })
        .then(function (r) { return r.json(); })
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
        .catch(function () { showToast('Erro ao publicar', 'error'); })
        .finally(function () {
            $postBtn.disabled = false;
            $postBtn.innerHTML = 'Publicar';
        });
    }

    /* ═══════════════════════════════════════════════════════════════
       FEED LOADING
       ═══════════════════════════════════════════════════════════════ */
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
        if (currentComponent) url += '&component=' + currentComponent;

        fetch(url, { headers: h(), credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!append) $container.innerHTML = '';

            if (data.length === 0 && page === 1) {
                $container.innerHTML = renderEmptyState();
                $loadMore.style.display = 'none';
                $feedEnd.style.display  = 'none';
                return;
            }

            data.forEach(function (item) {
                var card = renderPost(item);
                $container.appendChild(card);
                revealElement(card);
            });

            if (data.length < 15) {
                $loadMore.style.display = 'none';
                $feedEnd.style.display  = '';
            } else {
                $loadMore.style.display = '';
                $feedEnd.style.display  = 'none';
            }
        })
        .catch(function () {
            if (!append) {
                $container.innerHTML = '<div class="feed-empty"><i class="ri-error-warning-line"></i><span>Erro ao carregar feed</span></div>';
            }
        })
        .finally(function () {
            $loadMoreBtn.innerHTML = '<i class="ri-arrow-down-line"></i> Carregar mais';
            $loadMoreBtn.disabled  = false;
            isLoading = false;
        });
    }

    /* ═══════════════════════════════════════════════════════════════
       SOUNDCLOUD WAVEFORM — buildWave()
       ═══════════════════════════════════════════════════════════════ */
    function buildWave(container) {
        if (!container) return;
        var bars = 50;
        for (var i = 0; i < bars; i++) {
            var bar = document.createElement('div');
            bar.className = 'sc-bar';
            bar.style.height = (Math.random() * 80 + 20) + '%';
            bar.style.animationDelay = (Math.random() * 2) + 's';
            container.appendChild(bar);
        }
    }

    /* ═══════════════════════════════════════════════════════════════
       RENDER POST (.post — approved design)
       ═══════════════════════════════════════════════════════════════ */
    function renderPost(item) {
        var el = document.createElement('section');
        el.className = 'post gsap-el';
        el.dataset.id = item.id;

        /* ── Badge (membership badge) ── */
        var badgeHtml = '';
        if (item.badge && item.badge.type && item.badge.type !== 'nao-verificado' && item.badge.ri_icon) {
            badgeHtml = '<span class="user-membership-badge" style="--badge-color:' + esc(item.badge.color || '#999') + '" title="' + esc(item.badge.label) + '">'
                + '<i class="' + esc(item.badge.ri_icon) + '"></i>'
                + '</span>';
        }

        /* ── Component tag ── */
        var tagHtml = '';
        if (item.component === 'events') {
            tagHtml = '<span class="post-tag post-tag-event"><i class="ri-calendar-event-fill"></i> Evento</span>';
        } else if (item.component === 'classifieds') {
            tagHtml = '<span class="post-tag post-tag-classified"><i class="ri-exchange-box-line"></i> Anúncio</span>';
        } else if (item.component === 'content' && item.type === 'new_post') {
            tagHtml = '<span class="post-tag post-tag-content"><i class="ri-article-fill"></i> Artigo</span>';
        }

        /* ── Comment label (CPT pages → Depoimentos, else → Comentários) ── */
        var commentLabel = (item.component === 'events' || item.component === 'loc' || item.component === 'djs')
            ? 'Depoimentos' : 'Comentários';

        /* ── Time ── */
        var timeStr = item.time_ago || relativeTime(item.created_at);

        /* ── Menu button (own posts → more options) ── */
        var canDelete = parseInt(item.user_id) === MY_ID;
        var menuHtml = canDelete
            ? '<div class="post-menu-wrap">'
            +   '<button class="post-menu-btn" onclick="toggleMenu(this)" title="Opções"><i class="ri-more-fill"></i></button>'
            +   '<div class="post-menu-dropdown">'
            +     '<button class="post-menu-item post-delete-btn" data-id="' + item.id + '"><i class="ri-delete-bin-line"></i> Excluir</button>'
            +     '<button class="post-menu-item"><i class="ri-flag-line"></i> Reportar</button>'
            +   '</div>'
            + '</div>'
            : '';

        /* ── Content (server-rendered HTML with embeds) ── */
        var contentHtml = '';
        if (item.content_html) {
            contentHtml = '<div class="post-body">' + item.content_html + '</div>';
        } else if (item.content) {
            contentHtml = '<div class="post-body"><div class="post-text">' + esc(item.content) + '</div></div>';
        }

        /* ── Primary link (content/events — not social posts) ── */
        var linkHtml = '';
        if (item.primary_link && item.component !== 'social') {
            linkHtml = '<a href="' + esc(item.primary_link) + '" class="post-primary-link" target="_blank" rel="noopener">'
                + '<i class="ri-external-link-line"></i> Ver detalhes</a>';
        }

        /* ── Classified chat button ── */
        var classifiedChat = item.component === 'classifieds'
            ? '<button class="act-btn act-contact" data-a-user="' + item.user_id + '" data-item-id="' + item.id + '" title="Contatar"><i class="ri-chat-1-fill"></i><span>Contatar</span></button>'
            : '';

        /* ── Assembly (approved design structure) ── */
        el.innerHTML =
            '<div class="post-header">'
            +   '<a href="' + esc(item.profile_url || '') + '" class="av-md">'
            +       '<img src="' + esc(item.avatar_url || '') + '" alt="" loading="lazy">'
            +   '</a>'
            +   '<div class="post-meta">'
            +       '<div class="profile-identity">'
            +           '<h1 class="name"><a href="' + esc(item.profile_url || '') + '">' + esc(item.display_name || '') + '</a></h1>'
            +           badgeHtml
            +           tagHtml
            +       '</div>'
            +       '<div class="post-sub">'
            +           '<span class="post-handle">@' + esc(item.user_login || '') + '</span>'
            +           '<span class="post-dot">&middot;</span>'
            +           '<time class="post-time" title="' + esc(item.created_at || '') + '">' + tempoHTML(timeStr) + '</time>'
            +       '</div>'
            +   '</div>'
            +   menuHtml
            + '</div>'
            + contentHtml
            + linkHtml
            + '<div class="post-actions">'
            +   '<button class="act-btn act-wow" title="Wow" onclick="toggleWow(this)"><i class="ri-brain-line"></i><span>0</span></button>'
            +   '<button class="act-btn act-comment" title="' + esc(commentLabel) + '" onclick="toggleComments(this)"><i class="ri-chat-4-line"></i><span>' + esc(commentLabel) + '</span></button>'
            +   '<button class="act-btn act-repost" title="Repost"><i class="ri-repeat-line"></i></button>'
            +   '<button class="act-btn act-report" title="Reportar" data-apollo-report-trigger><i class="ri-flag-line"></i></button>'
            +   classifiedChat
            + '</div>'
            + '<div class="comments-section" style="display:none;">'
            +   '<div class="comments-header"><i class="ri-chat-4-line"></i> ' + esc(commentLabel) + '</div>'
            +   '<div class="comments-list"></div>'
            +   '<div class="comments-empty">Nenhum ' + esc(commentLabel.toLowerCase().slice(0, -1)) + ' ainda.</div>'
            + '</div>';

        /* ── Bind delete ── */
        el.querySelectorAll('.post-delete-btn').forEach(function (btn) {
            btn.onclick = function (e) { e.stopPropagation(); openDeleteModal(parseInt(btn.dataset.id)); };
        });

        /* ── Bind classified chat → safety modal ── */
        el.querySelectorAll('.act-contact').forEach(function (btn) {
            btn.onclick = function (e) {
                e.stopPropagation();
                openSafetyModal(function () {
                    window.location.href = '/chat?user=' + btn.dataset.userId + '&ref=classified-' + btn.dataset.itemId;
                });
            };
        });

        /* ── Build waveform for SoundCloud embeds ── */
        el.querySelectorAll('.sc-wave').forEach(function (wave) {
            buildWave(wave);
        });

        return el;
    }

    /* ═══════════════════════════════════════════════════════════════
       WOW TOGGLE (brain icon)
       ═══════════════════════════════════════════════════════════════ */
    window.toggleWow = function (btn) {
        btn.classList.toggle('active');
        var icon = btn.querySelector('i');
        var span = btn.querySelector('span');
        var n = parseInt(span.textContent) || 0;
        if (btn.classList.contains('active')) {
            icon.className = 'ri-brain-fill';
            span.textContent = n + 1;
        } else {
            icon.className = 'ri-brain-line';
            span.textContent = Math.max(0, n - 1);
        }
    };

    /* ═══════════════════════════════════════════════════════════════
       COMMENTS TOGGLE
       ═══════════════════════════════════════════════════════════════ */
    window.toggleComments = function (btn) {
        var post = btn.closest('.post');
        if (!post) return;
        var section = post.querySelector('.comments-section');
        if (!section) return;
        var showing = section.style.display !== 'none';
        section.style.display = showing ? 'none' : '';
        btn.classList.toggle('active', !showing);
    };

    /* ═══════════════════════════════════════════════════════════════
       POST MENU TOGGLE
       ═══════════════════════════════════════════════════════════════ */
    window.toggleMenu = function (btn) {
        var wrap = btn.closest('.post-menu-wrap');
        if (!wrap) return;
        var dd = wrap.querySelector('.post-menu-dropdown');
        if (!dd) return;
        var showing = dd.classList.contains('show');
        /* Close all other menus first */
        document.querySelectorAll('.post-menu-dropdown.show').forEach(function (d) { d.classList.remove('show'); });
        if (!showing) dd.classList.add('show');
    };

    /* Close menus on outside click */
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.post-menu-wrap')) {
            document.querySelectorAll('.post-menu-dropdown.show').forEach(function (d) { d.classList.remove('show'); });
        }
    });

    function renderEmptyState() {
        return '<div class="feed-empty">'
            + '<i class="ri-chat-smile-2-line"></i>'
            + '<h3>Nenhuma atividade ainda</h3>'
            + '<p>Seja o primeiro a publicar algo!</p>'
            + '</div>';
    }

    /* ═══════════════════════════════════════════════════════════════
       DELETE MODAL
       ═══════════════════════════════════════════════════════════════ */
    function openDeleteModal(id) {
        deleteTarget = id;
        $deleteModal.style.display = 'flex';
    }

    window.closeDeleteModal = function () {
        deleteTarget = null;
        $deleteModal.style.display = 'none';
    };

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
            var el = document.querySelector('.post[data-id="' + deleteTarget + '"]');
            if (el && typeof gsap !== 'undefined') {
                gsap.to(el, {
                    opacity: 0, scale: 0.95, height: 0, marginBottom: 0,
                    duration: 0.4, ease: 'power3.inOut',
                    onComplete: function () { el.remove(); }
                });
            } else if (el) {
                el.remove();
            }
            showToast('Post excluído', 'success');
        })
        .catch(function () { showToast('Erro ao excluir', 'error'); })
        .finally(function () {
            btn.disabled = false;
            btn.textContent = 'Excluir';
            window.closeDeleteModal();
        });
    }

    /* ═══════════════════════════════════════════════════════════════
       SAFETY MODAL
       ═══════════════════════════════════════════════════════════════ */
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

    /* ═══════════════════════════════════════════════════════════════
       TOAST
       ═══════════════════════════════════════════════════════════════ */
    function showToast(msg, type) {
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + (type || 'info');
        toast.innerHTML = '<span>' + esc(msg) + '</span>';
        $toastContainer.appendChild(toast);
        requestAnimationFrame(function () { toast.classList.add('show'); });
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }

    /* ═══════════════════════════════════════════════════════════════
       MEDIA URL BAR
       ═══════════════════════════════════════════════════════════════ */
    var MEDIA_CFG = {
        soundcloud: { icon: 'ri-soundcloud-fill', placeholder: 'Cole a URL do SoundCloud...', color: '#ff5500' },
        spotify:    { icon: 'ri-spotify-fill',     placeholder: 'Cole a URL do Spotify...',     color: '#1db954' },
        event:      { icon: 'ri-calendar-event-fill', placeholder: 'Cole a URL do evento Apollo...', color: '#7c3aed' },
        youtube:    { icon: 'ri-youtube-fill',     placeholder: 'Cole a URL do YouTube...',     color: '#ff0000' }
    };

    var MEDIA_RE = {
        soundcloud: RE_SOUNDCLOUD,
        spotify:    RE_SPOTIFY,
        event:      RE_APOLLO_EVENT,
        youtube:    RE_YOUTUBE
    };

    function openUrlBar(type) {
        var cfg = MEDIA_CFG[type];
        if (!cfg) return;
        activeMediaType = type;
        $urlIcon.className = cfg.icon;
        $urlIcon.style.color = cfg.color;
        $urlInput.placeholder = cfg.placeholder;
        $urlInput.value = '';
        $urlBar.style.display = '';
        $urlInput.focus();
        document.querySelectorAll('.compose-media-btn').forEach(function (b) {
            b.classList.toggle('active', b.dataset.media === type);
        });
    }

    function closeUrlBar() {
        activeMediaType = null;
        $urlBar.style.display = 'none';
        $urlInput.value = '';
        document.querySelectorAll('.compose-media-btn').forEach(function (b) { b.classList.remove('active'); });
    }

    function addUrlFromBar() {
        var url = $urlInput.value.trim();
        if (!url) return;

        if (!MEDIA_RE[activeMediaType] || !MEDIA_RE[activeMediaType].test(url)) {
            showToast('URL inválida para ' + activeMediaType, 'error');
            return;
        }

        var text = $textarea.value;
        if (text && !text.endsWith(' ') && !text.endsWith('\n')) text += ' ';
        $textarea.value = text + url;
        updateCharCounter();
        autoResize();
        closeUrlBar();
        showToast('URL adicionada!', 'success');
    }

    /* ═══════════════════════════════════════════════════════════════
       SIDEBAR HYDRATION (.sb-* classes)
       ═══════════════════════════════════════════════════════════════ */
    function hydrateSidebar() {
        hydrateNews();
        hydrateEvents();
        hydrateTrending();
        hydrateStats();
    }

    function hydrateNews() {
        var el = document.getElementById('sidebar-news');
        if (!el) return;
        fetch(REST + '/feed?per_page=4&component=content', { headers: h(), credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.length) { el.innerHTML = sbPlaceholder('Nenhuma notícia recente'); return; }
            el.innerHTML = data.map(function (item) {
                return '<a href="' + esc(item.primary_link || '#') + '" class="sb-news-item">'
                    + '<span class="news-cat">Geral</span>'
                    + '<span class="news-title">' + esc(item.action_text || item.display_name || '') + '</span>'
                    + '<span class="news-time">' + tempoHTML(item.time_ago || relativeTime(item.created_at)) + '</span>'
                    + '</a>';
            }).join('');
        })
        .catch(function () { el.innerHTML = sbPlaceholder('—'); });
    }

    function hydrateEvents() {
        var el = document.getElementById('sidebar-events');
        if (!el) return;
        fetch(REST + '/feed?per_page=4&component=events', { headers: h(), credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.length) { el.innerHTML = sbPlaceholder('Nenhum evento próximo'); return; }
            el.innerHTML = data.map(function (item) {
                return '<a href="' + esc(item.primary_link || '#') + '" class="sb-ev">'
                    + '<div class="sbe-date"><span class="sbe-day">--</span><span class="sbe-month">---</span></div>'
                    + '<div class="sbe-info"><span class="sbe-name">' + esc(item.action_text || '') + '</span>'
                    + '<span class="sbe-loc">' + tempoHTML(item.time_ago || relativeTime(item.created_at)) + '</span></div>'
                    + '</a>';
            }).join('');
        })
        .catch(function () { el.innerHTML = sbPlaceholder('—'); });
    }

    function hydrateTrending() {
        var el = document.getElementById('sidebar-trending');
        if (!el) return;
        fetch(REST + '/feed?per_page=5&component=social', { headers: h(), credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (!data.length) { el.innerHTML = sbPlaceholder('Nenhuma track em destaque'); return; }
            el.innerHTML = data.map(function (item, i) {
                return '<div class="sb-trending-track">'
                    + '<span class="sbt-num">' + (i + 1) + '</span>'
                    + '<div class="sbt-art" style="background:#333;"></div>'
                    + '<div class="sbt-info">'
                    + '<span class="sbt-name">' + esc(item.display_name || '') + '</span>'
                    + '<span class="sbt-artist">' + tempoHTML(item.time_ago || relativeTime(item.created_at)) + '</span>'
                    + '</div></div>';
            }).join('');
        })
        .catch(function () { el.innerHTML = sbPlaceholder('—'); });
    }

    function hydrateStats() {
        var ids = { 'stat-users': '1.2k', 'stat-events': '340', 'stat-posts': '8.7k', 'stat-djs': '420' };
        Object.keys(ids).forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.textContent = ids[id];
        });
    }

    function sbPlaceholder(msg) {
        return '<div class="sb-empty"><span>' + esc(msg) + '</span></div>';
    }

    /* ═══════════════════════════════════════════════════════════════
       SETTINGS PANEL (icon toggles)
       ═══════════════════════════════════════════════════════════════ */
    window.toggleSetting = function (icon) {
        var isActive = icon.classList.contains('active');
        icon.classList.toggle('active', !isActive);
        icon.className = (!isActive ? 'ri-toggle-fill' : 'ri-toggle-line') + ' s-toggle' + (!isActive ? ' active' : '');
    };

    function initSettings() {
        var btn = document.getElementById('btn-save-settings');
        if (!btn) return;
        btn.onclick = function () {
            var data = {};
            document.querySelectorAll('#tab-settings .s-row').forEach(function (row) {
                var key = row.dataset.setting;
                var toggle = row.querySelector('.s-toggle');
                if (key && toggle) {
                    data[key] = toggle.classList.contains('active');
                }
            });
            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Salvando...';

            fetch(REST.replace('/feed', '') + '/users/me/settings', {
                method: 'POST',
                headers: h(),
                credentials: 'same-origin',
                body: JSON.stringify(data)
            })
            .then(function (r) {
                if (r.ok) showToast('Preferências salvas!', 'success');
                else showToast('Erro ao salvar', 'error');
            })
            .catch(function () { showToast('Erro de rede', 'error'); })
            .finally(function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="ri-save-line"></i> Salvar preferências';
            });
        };
    }

    /* ═══════════════════════════════════════════════════════════════
       GSAP REVEALS
       ═══════════════════════════════════════════════════════════════ */
    function initReveals() {
        if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') return;
        gsap.registerPlugin(ScrollTrigger);

        gsap.utils.toArray('.gsap-el').forEach(function (el) {
            gsap.from(el, {
                y: 32,
                opacity: 0,
                duration: 0.7,
                ease: 'power3.out',
                scrollTrigger: {
                    trigger: el,
                    start: 'top 92%',
                    once: true
                }
            });
        });
    }

    function revealElement(el) {
        if (typeof gsap === 'undefined') return;
        gsap.from(el, { y: 24, opacity: 0, duration: 0.5, ease: 'power3.out' });
    }

    /* ═══════════════════════════════════════════════════════════════
       INIT
       ═══════════════════════════════════════════════════════════════ */
    function init(cfg) {
        REST  = cfg.rest;
        NONCE = cfg.nonce;
        LIMIT = cfg.limit || 280;
        MY_ID = cfg.userId;

        /* ── DOM Refs ── */
        $textarea       = document.getElementById('feed-compose-text');
        $postBtn        = document.getElementById('feed-post-btn');
        $charCounter    = document.getElementById('char-counter');
        $charText       = document.getElementById('char-count-text');
        $charRingFill   = document.querySelector('.char-ring-fill');
        $container      = document.getElementById('feed-container');
        $loadMore       = document.getElementById('feed-load-more');
        $loadMoreBtn    = document.getElementById('feed-load-more-btn');
        $feedEnd        = document.getElementById('feed-end');
        $embedPreview   = document.getElementById('compose-embed-preview');
        $deleteModal    = document.getElementById('modal-delete');
        $compose        = document.querySelector('.compose-card');
        $toastContainer = document.getElementById('toast-container');
        $feedColumn     = document.getElementById('feed-column');

        /* Media URL bar */
        $urlBar      = document.getElementById('compose-url-bar');
        $urlInput    = document.getElementById('compose-url-input');
        $urlIcon     = document.getElementById('compose-url-icon');
        $urlAddBtn   = document.getElementById('compose-url-add');
        $urlCloseBtn = document.getElementById('compose-url-close');

        /* Safety modal */
        $safetyModal   = document.getElementById('modal-safety');
        $safetyCheck   = document.getElementById('safety-consent-check');
        $safetyProceed = document.getElementById('safety-proceed-btn');
        $safetyCancel  = document.getElementById('safety-cancel-btn');

        /* ── Textarea events ── */
        if ($textarea) {
            $textarea.addEventListener('input', function () { updateCharCounter(); autoResize(); });
            $textarea.addEventListener('focus', function () { $compose && $compose.classList.add('focused'); });
            $textarea.addEventListener('blur', function () {
                if (!$textarea.value.trim()) $compose && $compose.classList.remove('focused');
            });
            $textarea.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    if (!$postBtn.disabled) handlePost();
                }
            });
        }

        /* Post button */
        if ($postBtn) $postBtn.onclick = handlePost;

        /* Load more */
        if ($loadMoreBtn) {
            $loadMoreBtn.onclick = function () { currentPage++; loadFeed(currentPage, true); };
        }

        /* ── Delete modal ── */
        var delConfirm = document.getElementById('modal-delete-confirm');
        if (delConfirm) delConfirm.onclick = confirmDelete;
        if ($deleteModal) {
            $deleteModal.onclick = function (e) { if (e.target === $deleteModal) window.closeDeleteModal(); };
        }

        /* ── Media URL bar ── */
        if ($urlAddBtn)   $urlAddBtn.onclick = addUrlFromBar;
        if ($urlCloseBtn) $urlCloseBtn.onclick = closeUrlBar;
        if ($urlInput) {
            $urlInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter')  { e.preventDefault(); addUrlFromBar(); }
                if (e.key === 'Escape') closeUrlBar();
            });
        }
        document.querySelectorAll('.compose-media-btn').forEach(function (btn) {
            btn.onclick = function () {
                var type = btn.dataset.media;
                activeMediaType === type ? closeUrlBar() : openUrlBar(type);
            };
        });

        /* ── Safety modal ── */
        if ($safetyCheck)   $safetyCheck.onchange = function () { $safetyProceed.disabled = !this.checked; };
        if ($safetyProceed) $safetyProceed.onclick = function () { if (typeof safetyCallback === 'function') safetyCallback(); closeSafetyModal(); };
        if ($safetyCancel)  $safetyCancel.onclick = closeSafetyModal;
        if ($safetyModal)   $safetyModal.onclick = function (e) { if (e.target === $safetyModal) closeSafetyModal(); };

        /* ── Infinite scroll ── */
        var scrollTimer;
        window.addEventListener('scroll', function () {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(function () {
                if (isLoading || currentTab === 'settings') return;
                var threshold = document.documentElement.scrollHeight - window.innerHeight - 400;
                if (window.scrollY >= threshold && $loadMore && $loadMore.style.display !== 'none') {
                    currentPage++;
                    loadFeed(currentPage, true);
                }
            }, 100);
        });

        /* ── Bootstrap ── */
        dismissPreloader();
        loadFeed(1, false);
        hydrateSidebar();
        initSettings();
        initTabBarScroll();
        if ($textarea) updateCharCounter();

        /* GSAP reveals after a small delay (let DOM settle) */
        setTimeout(initReveals, 200);
    }

    /* ═══════════════════════════════════════════════════════════════
       PUBLIC API
       ═══════════════════════════════════════════════════════════════ */
    return { init: init };

})();
