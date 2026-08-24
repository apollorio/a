/**
 * Apollo Journal — Main JavaScript
 *
 * Lightweight: scroll-based lazy reveal for news grid items.
 *
 * @package Apollo\Journal
 */

(function ($) {
    'use strict';

    /** Apollo standard time-ago HTML block. Input: '53min' → icon+spans. */
    function tempoHTML(str) {
        if (!str) return '';
        var m = String(str).match(/^(\d+)([a-z]+)$/i);
        var num  = m ? m[1] : str;
        var unit = m ? m[2] : '';
        return '<i class="tempo-v"></i>\u00a0<span class="time-ago">' + num + '</span><span class="when-ago">' + unit + '</span>';
    }

    const CONFIG = window.apolloJournalConfig || {
        ajaxUrl: '/wp-admin/admin-ajax.php',
        restUrl: '/wp-json/apollo/v1/',
        nonce: ''
    };

    $(function () {
        initReveal();
        initLoadMore();
    });

    // ── Reveal observer — module-level to prevent accumulation across calls. ──
    var _revealObserver = null;
    var _revealCount    = 0;   // Monotonic counter — persists so new items stagger after old ones.

    /**
     * Returns the shared IntersectionObserver, creating it once.
     * Returns null if the API is unavailable.
     */
    function ensureRevealObserver() {
        if (_revealObserver) return _revealObserver;
        if (!('IntersectionObserver' in window)) return null;

        _revealObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var el    = entry.target;
                // Delay stored in dataset — avoids O(n) indexOf inside this hot callback.
                var delay = parseInt(el.dataset.ajRevealDelay, 10) || 0;
                setTimeout(function () {
                    el.style.opacity   = '1';
                    el.style.transform = 'translateY(0)';
                }, delay);
                _revealObserver.unobserve(el);
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        return _revealObserver;
    }

    /**
     * Staggered reveal animation for .aj-ng-item and .aj-card elements.
     *
     * Safe to call multiple times — items already set up are skipped via
     * data-aj-reveal, so no opacity flicker occurs on repeated calls and
     * the observer is never recreated.
     */
    function initReveal() {
        var obs = ensureRevealObserver();
        if (!obs) return;

        var all = document.querySelectorAll('.aj-ng-item, .aj-card');
        all.forEach(function (el) {
            // Skip elements already wired up on a previous call.
            if (el.dataset.ajReveal) return;

            el.dataset.ajReveal      = '1';
            el.dataset.ajRevealDelay = String(_revealCount * 60);
            _revealCount++;

            el.style.opacity    = '0';
            el.style.transform  = 'translateY(16px)';
            el.style.transition = 'opacity .4s var(--ease-default, cubic-bezier(.16,1,.3,1)), transform .4s var(--ease-default)';

            obs.observe(el);
        });
    }

    /**
     * Optional "load more" for news grid via REST.
     * Activated by adding data-aj-loadmore to .aj-news-grid.
     */
    function initLoadMore() {
        var $grid = $('.aj-news-grid[data-aj-loadmore]');
        if (!$grid.length) return;

        var page = 2;
        var loading = false;
        var $btn = $('<button class="pnl-btn" style="margin:24px auto;display:block">' +
            '<i class="ri-add-line"></i> Carregar mais</button>');

        $grid.after($btn);

        $btn.on('click', function () {
            if (loading) return;
            loading = true;
            $btn.text('Carregando...');

            $.ajax({
                url: CONFIG.restUrl + 'journal/posts',
                method: 'GET',
                data: { page: page, per_page: 6 },
                beforeSend: function (xhr) {
                    if (CONFIG.nonce) {
                        xhr.setRequestHeader('X-WP-Nonce', CONFIG.nonce);
                    }
                },
                success: function (data) {
                    if (!data.length) {
                        $btn.remove();
                        return;
                    }

                    data.forEach(function (post) {
                        var $item = $('<a>', { href: post.link, class: 'aj-ng-item' });
                        if (post.thumbnail) {
                            $item.append($('<img>', { class: 'aj-ng-item__img', src: post.thumbnail, alt: '', loading: 'lazy' }));
                        }
                        var $body = $('<div>', { class: 'aj-ng-item__body' });
                        var $top = $('<div>', { class: 'aj-ng-item__top' });
                        $top.append($('<span>', { class: 'aj-ng-badge', text: post.badge || 'NEWS' }));
                        $top.append($('<span>', { class: 'aj-ng-item__time' }).html(tempoHTML(post.time_ago || '')));
                        $body.append($top);
                        $body.append($('<div>', { class: 'aj-ng-item__title', text: post.title.rendered ? $('<div>').html(post.title.rendered).text() : '' }));
                        $body.append($('<div>', { class: 'aj-ng-item__author', text: post.author_name || '' }));
                        $item.append($body);

                        $grid.append($item);
                    });

                    page++;
                    loading = false;
                    $btn.html('<i class="ri-add-line"></i> Carregar mais');
                    initReveal(); // Re-observe new items
                },
                error: function () {
                    loading = false;
                    $btn.text('Erro. Tentar novamente.');
                }
            });
        });
    }

})(jQuery);
