/**
 * Apollo Chat Motion — luxury GSAP layer for /mensagens
 *
 * GSAP 3 (full bundle) + Lenis arrive via core.js. This page kills Lenis
 * (viewport-locked chat); all motion here is GSAP only.
 *
 * Public API on window.ApolloChatMotion:
 *   searchOpen / searchClose   .ac-search-overlay
 *   panelOpen  / panelClose    .ac-thread-menu / .ac-context-menu
 *   flySend                    composer → sent bubble
 *   msgEnter                   received / non-flight balloons
 *   staggerIn                  search results
 *
 * @package Apollo\Chat
 */
;(function (w, d) {
    'use strict';

    var reduced = false;
    try {
        reduced = !!(w.matchMedia && w.matchMedia('(prefers-reduced-motion: reduce)').matches);
    } catch (_e) { /* ignore */ }

    function gs() {
        return w.gsap;
    }

    function whenReady(fn) {
        if (gs()) {
            fn();
            return;
        }
        w.addEventListener('apollo:ready', fn, { once: true });
    }

    function kill(el) {
        var g = gs();
        if (g && el) g.killTweensOf(el);
    }

    function originFrom(el, originEl) {
        if (!el || !originEl || !originEl.getBoundingClientRect) return '92% 8%';
        var a = el.getBoundingClientRect();
        var b = originEl.getBoundingClientRect();
        if (!a.width || !a.height) return '92% 8%';
        var x = ((b.left + b.width / 2 - a.left) / a.width) * 100;
        var y = ((b.top + b.height / 2 - a.top) / a.height) * 100;
        return Math.max(0, Math.min(100, x)).toFixed(2) + '% ' + Math.max(0, Math.min(100, y)).toFixed(2) + '%';
    }

    function clearMotionProps(el) {
        var g = gs();
        if (!g || !el) return;
        g.set(el, {
            clearProps: 'clipPath,webkitClipPath,filter,transform,opacity,visibility,scale,x,y,rotation',
        });
    }

    /* ── Search overlay ─────────────────────────────────────────────── */

    function searchOpen(overlay, originEl) {
        if (!overlay) return;
        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');

        var header = overlay.querySelector('.ac-search-header');
        var input = overlay.querySelector('input');
        var results = overlay.querySelector('.ac-search-results');
        var g = gs();

        if (!g || reduced) {
            overlay.style.display = 'flex';
            overlay.style.opacity = '1';
            if (input) input.focus();
            return;
        }

        kill(overlay);
        if (header) kill(header);
        if (results) kill(results);

        var origin = originFrom(overlay, originEl);
        g.set(overlay, {
            display: 'flex',
            autoAlpha: 1,
            pointerEvents: 'auto',
            transformOrigin: origin,
        });

        var tl = g.timeline({
            defaults: { ease: 'expo.out' },
            onComplete: function () {
                if (input) input.focus();
            },
        });

        tl.fromTo(
            overlay,
            {
                autoAlpha: 0,
                scale: 0.972,
                filter: 'blur(18px) saturate(1.15)',
                clipPath: 'inset(10% 9% 74% 9% round 28px)',
            },
            {
                autoAlpha: 1,
                scale: 1,
                filter: 'blur(0px) saturate(1)',
                clipPath: 'inset(0% 0% 0% 0% round 0px)',
                duration: 0.92,
            }
        );

        if (header) {
            tl.fromTo(
                header,
                { y: -22, autoAlpha: 0, filter: 'blur(10px)', scale: 0.96 },
                {
                    y: 0,
                    autoAlpha: 1,
                    filter: 'blur(0px)',
                    scale: 1,
                    duration: 0.72,
                    ease: 'power4.out',
                },
                0.2
            );
        }

        if (input) {
            tl.fromTo(
                input,
                { x: -8, autoAlpha: 0 },
                { x: 0, autoAlpha: 1, duration: 0.55, ease: 'power3.out' },
                0.34
            );
        }

        overlay._acMotionTl = tl;
    }

    function searchClose(overlay) {
        if (!overlay) return Promise.resolve();
        var g = gs();
        var header = overlay.querySelector('.ac-search-header');

        function hide() {
            overlay.classList.remove('show');
            overlay.setAttribute('aria-hidden', 'true');
            overlay.style.display = '';
            clearMotionProps(overlay);
            if (header) clearMotionProps(header);
        }

        if (!g || reduced) {
            hide();
            return Promise.resolve();
        }

        kill(overlay);
        if (header) kill(header);

        return new Promise(function (resolve) {
            var tl = g.timeline({
                onComplete: function () {
                    hide();
                    resolve();
                },
            });
            if (header) {
                tl.to(
                    header,
                    {
                        y: -14,
                        autoAlpha: 0,
                        scale: 0.97,
                        filter: 'blur(8px)',
                        duration: 0.28,
                        ease: 'power2.in',
                    },
                    0
                );
            }
            tl.to(
                overlay,
                {
                    clipPath: 'inset(12% 10% 78% 10% round 26px)',
                    filter: 'blur(14px)',
                    autoAlpha: 0,
                    scale: 0.98,
                    duration: 0.5,
                    ease: 'power3.in',
                },
                0.04
            );
        });
    }

    /* ── Glass dropdowns (thread menu + context) ────────────────────── */

    function panelOpen(menu, originEl) {
        if (!menu) return;
        var g = gs();
        var items = menu.querySelectorAll('.ac-ctx-item');

        if (!g || reduced) {
            menu.style.opacity = '1';
            return;
        }

        kill(menu);
        items.forEach(function (it) { kill(it); });

        var origin = 'top right';
        if (originEl && originEl.getBoundingClientRect) {
            var mr = menu.getBoundingClientRect();
            var br = originEl.getBoundingClientRect();
            origin =
                (br.left + br.width / 2 - mr.left).toFixed(1) +
                'px ' +
                (br.top + br.height / 2 - mr.top).toFixed(1) +
                'px';
        }

        g.set(menu, {
            transformOrigin: origin,
            autoAlpha: 1,
            pointerEvents: 'auto',
            filter: 'none',
            clipPath: 'none',
        });

        var tl = g.timeline({
            defaults: { ease: 'expo.out' },
            onComplete: function () {
                g.set(menu, { clearProps: 'filter,clipPath' });
            },
        });
        tl.fromTo(
            menu,
            {
                autoAlpha: 0,
                y: -10,
                scale: 0.96,
            },
            {
                autoAlpha: 1,
                y: 0,
                scale: 1,
                duration: 0.42,
                clearProps: 'filter,clipPath',
            }
        );

        if (items.length) {
            tl.fromTo(
                items,
                { autoAlpha: 0, y: 8, x: 6 },
                {
                    autoAlpha: 1,
                    y: 0,
                    x: 0,
                    duration: 0.42,
                    stagger: 0.042,
                    ease: 'power3.out',
                },
                0.16
            );
        }

        menu._acMotionTl = tl;
    }

    function panelClose(menu, done) {
        if (!menu) {
            if (done) done();
            return Promise.resolve();
        }
        var g = gs();

        function finish() {
            if (menu.parentNode) menu.parentNode.removeChild(menu);
            if (done) done();
        }

        if (!g || reduced) {
            finish();
            return Promise.resolve();
        }

        kill(menu);
        var items = menu.querySelectorAll('.ac-ctx-item');
        items.forEach(function (it) { kill(it); });

        return new Promise(function (resolve) {
            var tl = g.timeline({
                onComplete: function () {
                    finish();
                    resolve();
                },
            });
            if (items.length) {
                tl.to(
                    items,
                    {
                        autoAlpha: 0,
                        y: -4,
                        duration: 0.14,
                        stagger: 0.018,
                        ease: 'power2.in',
                    },
                    0
                );
            }
            tl.to(
                menu,
                {
                    autoAlpha: 0,
                    y: -8,
                    scale: 0.96,
                    duration: 0.22,
                    ease: 'power3.in',
                },
                0.04
            );
        });
    }

    /* ── Message entrance (received / history-fresh) ────────────────── */

    function msgEnter(row) {
        if (!row) return;
        var g = gs();
        var bubble = row.querySelector('.ac-bubble') || row;
        var sent = row.classList.contains('sent');

        if (!g || reduced) {
            bubble.style.opacity = '1';
            return;
        }

        kill(bubble);
        g.fromTo(
            bubble,
            {
                autoAlpha: 0,
                y: sent ? 36 : 22,
                x: sent ? 10 : -14,
                scale: 0.94,
                filter: 'blur(6px)',
                transformOrigin: sent ? 'bottom right' : 'bottom left',
            },
            {
                autoAlpha: 1,
                y: 0,
                x: 0,
                scale: 1,
                filter: 'blur(0px)',
                duration: 0.64,
                ease: 'power3.out',
                clearProps: 'transform,filter',
            }
        );
    }

    /* ── Search results ─────────────────────────────────────────────── */

    function staggerIn(els) {
        var g = gs();
        if (!g || reduced || !els || !els.length) return;
        g.fromTo(
            els,
            { autoAlpha: 0, y: 10, filter: 'blur(6px)' },
            {
                autoAlpha: 1,
                y: 0,
                filter: 'blur(0px)',
                duration: 0.42,
                stagger: 0.035,
                ease: 'power3.out',
                clearProps: 'transform,filter',
            }
        );
    }

    /* ── Send: bubble flies from composer to the thread ─────────────── */

    function measureText(el, text) {
        var cs = w.getComputedStyle(el);
        var span = d.createElement('span');
        span.style.cssText =
            'position:absolute;left:-9999px;top:0;visibility:hidden;pointer-events:none;' +
            'white-space:pre-wrap;word-break:break-word;padding:0;margin:0;' +
            'font:' +
            cs.font +
            ';font-size:' +
            cs.fontSize +
            ';line-height:' +
            cs.lineHeight +
            ';letter-spacing:' +
            cs.letterSpacing +
            ';max-width:' +
            Math.max(40, el.clientWidth) +
            'px';
        span.textContent = text || '';
        d.body.appendChild(span);
        var r = span.getBoundingClientRect();
        span.remove();
        return { width: Math.ceil(r.width), height: Math.ceil(r.height) };
    }

    function flySend(opts) {
        opts = opts || {};
        var fromEl = opts.fromEl;
        var destRow = opts.destRow;
        var text = opts.text || '';
        var composeForm = opts.composeForm;
        var sendBtn = opts.sendBtn;
        var g = gs();

        if (!destRow) return;
        destRow.dataset.acEntered = '1';

        var destBubble = destRow.querySelector('.ac-bubble') || destRow;

        if (!g || reduced || !fromEl || !text) {
            if (g && destBubble) {
                msgEnter(destRow);
            } else {
                destBubble.style.opacity = '1';
            }
            return;
        }

        var from = fromEl.getBoundingClientRect();
        var textBox = measureText(fromEl, text);
        var padX = 13;
        var padY = 9;
        var startW = Math.min(from.width, Math.max(48, textBox.width + padX * 2));
        var startH = Math.max(from.height, textBox.height + padY * 2);
        var startLeft = from.left;
        var startTop = from.top + (from.height - startH) / 2;

        g.set(destBubble, { autoAlpha: 0, scale: 0.96, transformOrigin: 'bottom right' });

        if (composeForm) {
            kill(composeForm);
            g.fromTo(
                composeForm,
                { scale: 1 },
                {
                    scale: 0.978,
                    duration: 0.16,
                    yoyo: true,
                    repeat: 1,
                    ease: 'power2.inOut',
                    transformOrigin: '50% 100%',
                }
            );
        }
        if (sendBtn) {
            kill(sendBtn);
            g.fromTo(
                sendBtn,
                { scale: 1, rotate: 0 },
                {
                    scale: 0.82,
                    rotate: 12,
                    duration: 0.14,
                    yoyo: true,
                    repeat: 1,
                    ease: 'power2.inOut',
                }
            );
        }

        w.setTimeout(function () {
            if (destBubble.isConnected) {
                g.set(destBubble, { autoAlpha: 1, clearProps: 'visibility' });
            }
        }, 1400);

        var run = function () {
            var to;
            try {
                to = destBubble.getBoundingClientRect();
            } catch (_err) {
                g.set(destBubble, { autoAlpha: 1, scale: 1, clearProps: 'transform' });
                return;
            }
            if (!to.width || !to.height) {
                g.set(destBubble, { autoAlpha: 1, scale: 1, clearProps: 'transform' });
                return;
            }

            var destCs = w.getComputedStyle(destBubble);
            var formCs = composeForm ? w.getComputedStyle(composeForm) : null;
            var destBg = destCs.backgroundColor;
            if (!destBg || destBg === 'transparent' || destBg === 'rgba(0, 0, 0, 0)') {
                destBg = '#111113';
            }
            var destFg = destCs.color && destCs.color !== 'rgba(0, 0, 0, 0)' ? destCs.color : '#f5f5f7';

            var ghost = d.createElement('div');
            ghost.className = 'ac-send-ghost';
            ghost.setAttribute('aria-hidden', 'true');
            ghost.textContent = text;
            d.body.appendChild(ghost);

            g.set(ghost, {
                position: 'fixed',
                left: startLeft,
                top: startTop,
                x: 0,
                y: 0,
                width: startW,
                height: startH,
                autoAlpha: 1,
                rotation: -5,
                scale: 1,
                padding: '9px 13px',
                transformOrigin: 'left top',
                backgroundColor: formCs ? formCs.backgroundColor : '#ffffff',
                color: 'rgba(29, 29, 31, 0.92)',
                borderRadius: '22px',
                fontSize: destCs.fontSize,
                fontFamily: destCs.fontFamily,
                fontWeight: destCs.fontWeight,
                lineHeight: destCs.lineHeight,
                letterSpacing: destCs.letterSpacing,
                boxShadow: formCs ? formCs.boxShadow : '0 8px 24px -12px rgba(29,29,31,0.22)',
                force3D: true,
            });

            var dx = to.left - startLeft;
            var dy = to.top - startTop;
            var lift = Math.max(64, Math.min(168, Math.abs(dy) * 0.38 + 52));
            var drift = Math.max(12, Math.min(48, Math.abs(dx) * 0.08));
            var proxy = { t: 0 };

            var morph = g.timeline({
                onComplete: function () {
                    if (!destBubble.isConnected) {
                        if (ghost.parentNode) ghost.parentNode.removeChild(ghost);
                        return;
                    }
                    g.set(destBubble, { autoAlpha: 1, scale: 1 });
                    g.to(ghost, {
                        autoAlpha: 0,
                        duration: 0.14,
                        ease: 'power1.out',
                        onComplete: function () {
                            if (ghost.parentNode) ghost.parentNode.removeChild(ghost);
                        },
                    });
                    g.fromTo(
                        destBubble,
                        { scale: 0.985 },
                        {
                            scale: 1,
                            duration: 0.28,
                            ease: 'back.out(1.6)',
                            clearProps: 'transform',
                        }
                    );
                },
            });

            morph.to(
                ghost,
                {
                    width: to.width,
                    height: to.height,
                    backgroundColor: destBg,
                    color: destFg,
                    borderRadius: destCs.borderRadius,
                    boxShadow: destCs.boxShadow,
                    paddingTop: destCs.paddingTop,
                    paddingRight: destCs.paddingRight,
                    paddingBottom: destCs.paddingBottom,
                    paddingLeft: destCs.paddingLeft,
                    duration: 0.82,
                    ease: 'power3.inOut',
                },
                0
            );

            morph.to(
                proxy,
                {
                    t: 1,
                    duration: 0.82,
                    ease: 'power3.inOut',
                    onUpdate: function () {
                        var t = proxy.t;
                        var u = 1 - t;
                        var cx = startLeft + dx * 0.5 + drift;
                        var cy = Math.min(startTop, to.top) - lift;
                        var x = u * u * startLeft + 2 * u * t * cx + t * t * to.left;
                        var y = u * u * startTop + 2 * u * t * cy + t * t * to.top;
                        var rot = -5 * u;
                        var sc = 1 + Math.sin(t * Math.PI) * 0.055;
                        var blur = Math.sin(t * Math.PI) * 0.7;
                        g.set(ghost, {
                            left: x,
                            top: y,
                            rotation: rot,
                            scale: sc,
                            filter: 'blur(' + blur.toFixed(2) + 'px)',
                        });
                    },
                },
                0
            );
        };

        w.requestAnimationFrame(function () {
            w.requestAnimationFrame(run);
        });
    }

    /* ── Quick-react pop ────────────────────────────────────────────── */

    function popIn(el) {
        var g = gs();
        if (!el) return;
        if (!g || reduced) {
            el.style.opacity = '1';
            return;
        }
        kill(el);
        g.fromTo(
            el,
            { autoAlpha: 0, y: 8, scale: 0.72, filter: 'blur(6px)' },
            {
                autoAlpha: 1,
                y: 0,
                scale: 1,
                filter: 'blur(0px)',
                duration: 0.42,
                ease: 'back.out(1.7)',
                clearProps: 'transform,filter',
            }
        );
    }

    var API = {
        reduced: reduced,
        searchOpen: searchOpen,
        searchClose: searchClose,
        panelOpen: panelOpen,
        panelClose: panelClose,
        flySend: flySend,
        msgEnter: msgEnter,
        staggerIn: staggerIn,
        popIn: popIn,
    };

    w.ApolloChatMotion = API;

    whenReady(function () {
        d.documentElement.classList.add('ac-motion-ready');
    });
})(window, document);
