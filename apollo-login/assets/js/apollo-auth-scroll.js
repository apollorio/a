/**
 * Auth pages: bind Apollo luxury Y-scrollbar (#apollo-sb-y) to .scroll-area only.
 * Prevents double scrollbars (native .scroll-area thumb + document apollo-sb-y).
 *
 * @package Apollo\Login
 */
(function (w, d) {
    'use strict';

    function initAuthScroll() {
        var body = d.body;
        if (!body || body.getAttribute('data-apollo-auth') !== '1') {
            return;
        }

        var scroller = d.querySelector('#auth-card .scroll-area');
        var sbY = d.getElementById('apollo-sb-y');
        if (!scroller || !sbY) {
            return;
        }

        var thY = sbY.querySelector('.apollo-sb-thumb');
        if (!thY) {
            return;
        }

        var sbX = d.getElementById('apollo-sb-x');
        if (sbX) {
            sbX.classList.remove('apollo-sb-x--on');
        }

        function metrics() {
            var scrollLen = scroller.scrollHeight - scroller.clientHeight;
            return {
                overflow: scrollLen > 1,
                scrollLen: scrollLen,
                scrollPos: scroller.scrollTop,
            };
        }

        function updY() {
            var m = metrics();
            if (!m.overflow) {
                sbY.classList.remove('apollo-sb-y--on');
                return;
            }
            sbY.classList.add('apollo-sb-y--on');
            var trackH = sbY.clientHeight;
            var thumbH = Math.max(30, (scroller.clientHeight / scroller.scrollHeight) * trackH);
            thY.style.height = thumbH + 'px';
            thY.style.top = m.scrollLen > 0 ? (m.scrollPos / m.scrollLen) * (trackH - thumbH) + 'px' : '0px';
        }

        scroller.addEventListener('scroll', updY, { passive: true });
        w.addEventListener('resize', updY, { passive: true });

        if (w.ResizeObserver) {
            try {
                new w.ResizeObserver(updY).observe(scroller);
            } catch (_) {}
        }

        sbY.addEventListener(
            'click',
            function (e) {
                if (e.target === thY || e.target.classList.contains('apollo-sb-arrow')) {
                    return;
                }
                var m = metrics();
                if (!m.overflow) {
                    return;
                }
                var rect = sbY.getBoundingClientRect();
                var target = ((e.clientY - rect.top) / sbY.clientHeight) * m.scrollLen;
                scroller.scrollTo({ top: target, behavior: 'smooth' });
            },
            true
        );

        var dragging = false;
        var startY = 0;
        var startScroll = 0;

        thY.addEventListener(
            'mousedown',
            function (e) {
                var m = metrics();
                if (!m.overflow) {
                    return;
                }
                dragging = true;
                startY = e.clientY;
                startScroll = scroller.scrollTop;
                thY.classList.add('is-dragging');
                d.body.style.userSelect = 'none';
                e.preventDefault();
                e.stopImmediatePropagation();
            },
            true
        );

        d.addEventListener('mousemove', function (e) {
            if (!dragging) {
                return;
            }
            var m = metrics();
            if (!m.overflow) {
                return;
            }
            var trackH = sbY.clientHeight - thY.clientHeight;
            var ratio = m.scrollLen / trackH;
            scroller.scrollTop = startScroll + (e.clientY - startY) * ratio;
        });

        d.addEventListener('mouseup', function () {
            if (!dragging) {
                return;
            }
            dragging = false;
            thY.classList.remove('is-dragging');
            d.body.style.userSelect = '';
        });

        updY();
    }

    function boot() {
        initAuthScroll();
        w.addEventListener('apollo:ready', initAuthScroll, { once: true });
    }

    if (d.readyState === 'loading') {
        d.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
})(window, document);
