<?php

/**
 * Portal de Eventos — mount
 *
 * Replaces the mockup's view.eventos.js router glue. This page has no SPA
 * router, so it mounts once on DOMContentLoaded.
 *
 * PHASE 002: split out of the single 1258-line portal-scripts.php so each
 * concern is independently readable, replaceable and themeable.
 *
 * @package Apollo\Event
 * @since   1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>

	/* Bootstrap — replaces the mockup's view.eventos.js router glue (this
	   page has no SPA router; it mounts once on load). */
	document.addEventListener('DOMContentLoaded', function () {
		var host = document.getElementById('apollo-portal-root');
		if (!host || !window.AppPortalEventos) { return; }

		window.AppPortalEventos.init(host);

		/* ── Portal census (dev only) ────────────────────────────────────────
		   REMOVED FROM THE PRODUCTION PATH (2026-08-05).

		   This block used to POST a card census to
		   http://127.0.0.1:7704/ingest/… on EVERY /eventos and /portal load,
		   for every visitor. Against https://apollo.rio.br that request can
		   never succeed and is not harmless:

		     · mixed content — an http:// subresource requested from an https://
		       document is blocked outright by every modern browser;
		     · CORS — a cross-origin POST with a custom X-Debug-Session-Id
		       header forces a preflight to a host that answers no CORS headers
		       (and on a visitor's machine usually nothing is listening at all);
		     · it fires inside the mount handler, so its console noise lands
		       exactly where a real portal render error would, burying it.

		   The .catch() swallowed the rejection, which is why it survived: it
		   broke nothing functionally while permanently polluting the console
		   and the network panel of the one screen we keep debugging.

		   Dev telemetry must be opt-in and same-origin. Gate any future census
		   behind a server-side APOLLO_DEV_MODE check in PHP so the payload is
		   never emitted to visitors at all, rather than emitted-and-caught. */

		/* ── Scroll-engine sync (hardening, 2026-07-30) ──────────────────────
		   This screen is 100% client-rendered: #apollo-portal-root is an empty
		   div until init() above runs, and every section then keeps GROWING as
		   mountInfinite() pages more cards in. Lenis (the ecosystem scroll
		   engine, gsap-lenis.js) caches its scroll limit in
		   Lenis.dimensions and gsap-lenis.js only refreshes it on
		   window 'resize'. Lenis 1.3.x does carry its own ResizeObserver on
		   documentElement, so it does eventually self-heal — but only after a
		   250ms debounce, and ScrollTrigger is never told at all
		   (autoRefreshEvents is 'DOMContentLoaded,load,resize', none of which
		   fire when JS appends nodes).

		   Net effect without this: a window right after mount, and after each
		   infinite-load page, where the scroll limit is shorter than the real
		   document and the last cards cannot be reached. Nudging both engines
		   explicitly removes that window instead of relying on the debounce.

		   No observer is created here on purpose — mountInfinite() already owns
		   an IntersectionObserver per feed and disconnects it when the list
		   ends; adding a MutationObserver over the same DOM would be exactly
		   the "observer leak" the house rules forbid. Two rAF-deferred passes
		   cover mount + first paint; 'apollo:ready' covers a late CDN boot. */
		var sync = function () {
			if (window.lenis && typeof window.lenis.resize === 'function') {
				window.lenis.resize();
			}
			if (window.ScrollTrigger && typeof window.ScrollTrigger.refresh === 'function') {
				window.ScrollTrigger.refresh();
			}
		};
		requestAnimationFrame(function () { requestAnimationFrame(sync); });
		window.addEventListener('apollo:ready', sync, { once: true });
		/* Images land after mount and change section heights as they decode. */
		window.addEventListener('load', sync, { once: true });

		/* ── Modal scroll containment ────────────────────────────────────────
		   .pev-modal is a fixed full-viewport sheet, but nothing ever stopped
		   the page behind it: opening an event card and dragging scrolled the
		   document under the sheet, and on touch the gesture bled from the
		   sheet's own overflow-y:auto card into the page.

		   Apollo.lockScroll is the primitive the house rules mandate, but it is
		   NOT present in the current CDN build (checked core.js and js/script.js
		   at v1.0.0 — neither defines it), so the guarded lenis.stop()/start()
		   branch below is what actually runs today. The Apollo.lockScroll call
		   is kept first and feature-detected so this switches over by itself the
		   day the CDN ships it, with no edit here. body.style is never touched
		   directly either way, and every lock is paired 1:1 with an unlock so no
		   page can be left frozen.

		   The class is toggled by app.php's openModal/closeModal, which run
		   before this observer-free check, so a plain class watch on the two
		   known modals is enough — read on click, act once, no polling. */
		var lockDepth = 0;
		var setLock = function (want) {
			if (want === (lockDepth > 0)) { return; }
			lockDepth = want ? 1 : 0;
			if (window.Apollo && typeof window.Apollo.lockScroll === 'function') {
				window.Apollo.lockScroll(want);
			} else if (window.lenis) {
				want ? window.lenis.stop() : window.lenis.start();
			}
		};
		var anyModalOpen = function () {
			return !!document.querySelector('.pev-modal.is-open, .ev-lb.is-open');
		};
		/* app.php opens/closes modals from delegated document clicks and from
		   Escape; re-checking on the same two events (after they bubble) keeps
		   this in step without duplicating any of its logic. */
		['click', 'keyup'].forEach(function (evt) {
			document.addEventListener(evt, function () {
				requestAnimationFrame(function () { setLock(anyModalOpen()); });
			});
		});
		window.addEventListener('pagehide', function () { setLock(false); });

		/* ── Lightbox ↔ scroll engine handoff ────────────────────────────────
		   The full-viewport lightbox is its OWN scroll container
		   (.ev-lb-scroll, overflow-y:auto). Two things follow, and neither
		   happens by itself:

		     · Lenis is bound to the document. While the lightbox is open the
		       document must not move, and the panel must scroll natively —
		       that's the setLock() above, now also watching .ev-lb.is-open.
		     · ScrollTrigger inside the injected fragment measures against
		       `scroller: window` by default, but its content lives in a
		       different scrolling box. Without being told, every reveal in the
		       single-event page either fires at mount or never fires — which
		       is exactly the "no ScrollTrigger, no GSAP feel" report.

		   ScrollTrigger.scrollerProxy() is the documented way to point it at a
		   custom scroller. Registered once, on first open, and only when both
		   the engine and the panel actually exist — this must never throw on a
		   page where the CDN bundle didn't boot.

		   A plain 'scroll' listener on the panel drives ScrollTrigger.update()
		   so triggers stay live while the user scrolls inside it. The listener
		   is passive (never blocks the compositor) and is attached exactly once
		   to a node that lives for the life of the document. */
		var lbWired = false;
		var wireLightboxScroll = function () {
			if (lbWired) { return; }
			var panel = document.querySelector('.ev-lb-scroll');
			var ST = window.ScrollTrigger;
			if (!panel || !ST || typeof ST.scrollerProxy !== 'function') { return; }
			lbWired = true;

			ST.scrollerProxy(panel, {
				scrollTop: function (value) {
					if (arguments.length) { panel.scrollTop = value; }
					return panel.scrollTop;
				},
				getBoundingClientRect: function () {
					return { top: 0, left: 0, width: window.innerWidth, height: window.innerHeight };
				},
				/* The panel is a normal overflow box, not transform-driven. */
				pinType: 'fixed'
			});

			panel.addEventListener('scroll', function () { ST.update(); }, { passive: true });
			ST.addEventListener && ST.addEventListener('refresh', function () { panel.scrollTop = panel.scrollTop; });
		};

		/* ── Anti-strand net ─────────────────────────────────────────────────
		   .ev-reveal ships opacity:0 and is cleared by ScrollTrigger. Any
		   trigger that fails to resolve inside the panel leaves that section
		   permanently invisible — present in the DOM, unreadable on screen,
		   which is the worst possible failure mode because it looks like
		   missing content rather than a broken animation.

		   So we verify the OUTCOME rather than trusting the mechanism: read
		   computed opacity a beat after mount and, for anything still at zero
		   inside the viewport-height of the panel, hand it the escape-hatch
		   class. Runs twice (600ms / 1600ms) to cover a slow image decode
		   shifting layout, then stops. No observer, no polling loop.

		   SELECTOR (2026-08-07): the list is no longer written here. PHP owns it
		   — APOLLO_EVENT_REVEAL_FLOOR in includes/render-single.php — and
		   publishes it on window.APOLLO_EVENT_REVEAL, which the CSS floor in
		   styles-lightbox.php and apollo-single-event.js's initAnim() also read.
		   It was previously hand-copied into all three with a "keep in sync
		   manually" note; the literal below is now the offline fallback only.

		   VIEWPORT BOUND (2026-08-07) — THIS IS WHY REVEALS NEVER ANIMATED.
		   The comment above has always said "inside the viewport-height of the
		   panel", but the code never checked: it force-visibled EVERY match at
		   opacity < .05, and below-the-fold reveals are legitimately at zero
		   because their trigger has not fired yet. So 600ms after open, every
		   section below the fold was nailed to `opacity:1 !important` and the
		   scroll animation was destroyed — not broken, deleted. Rescue is now
		   restricted to what is actually on screen inside the panel, which is
		   the only place "invisible" can mean "stranded". Anything below the
		   fold is left alone so GSAP can play it in, and reverse it out. */
		var REVEAL_FLOOR = (window.APOLLO_EVENT_REVEAL && window.APOLLO_EVENT_REVEAL.floor) ||
			'.ev-reveal,.ev-fact,.ev-panel,[data-ev="access"],[data-ev="about"],.ev-spotify,.ev-gallery,.v-slider,.ev-map,.ev-vbody,.ev-dj,[data-reveal-word]';

		var unstrand = function (panel) {
			if (!panel) { return; }
			var box = panel.getBoundingClientRect();
			panel.querySelectorAll(REVEAL_FLOOR).forEach(function (n) {
				var r = n.getBoundingClientRect();
				/* On screen inside the panel right now? Zero-height nodes are
				   skipped — nothing to strand, and their rect lies. */
				if (r.height <= 0 || r.bottom <= box.top || r.top >= box.bottom) { return; }
				var cs = window.getComputedStyle(n);
				if (parseFloat(cs.opacity) < 0.05 || cs.visibility === 'hidden') {
					n.classList.add('ev-force-visible');
					/* GSAP writes inline styles; the class alone cannot beat
					   them, so clear the two it sets on a stranded element. */
					n.style.opacity = '';
					n.style.transform = '';
				}
			});
		};

		/* The fragment lands asynchronously, so measure AFTER it is in the DOM:
		   ApolloEventLightbox.open() resolves once injected and mounted. Two
		   rAFs let layout and first paint settle before ScrollTrigger measures,
		   the same pattern the portal already uses for its own feeds. */
		document.addEventListener('click', function (e) {
			if (!e.target.closest || !e.target.closest('[data-ev-open]')) { return; }
			var t = 0;
			var poll = setInterval(function () {
				var mounted = document.querySelector('.ev-lb.is-open .ev-lb-body [data-ev-root]');
				if (mounted) {
					clearInterval(poll);
					wireLightboxScroll();
					var panel = document.querySelector('.ev-lb-scroll');
					requestAnimationFrame(function () {
						requestAnimationFrame(function () {
							if (window.ScrollTrigger) { window.ScrollTrigger.refresh(); }
						});
					});
					setTimeout(function () { unstrand(panel); }, 600);
					setTimeout(function () {
						unstrand(panel);
						/* Final measure once images have settled — a hero that
						   decodes late changes every trigger start below it. */
						if (window.ScrollTrigger) { window.ScrollTrigger.refresh(); }
					}, 1600);
				} else if (++t > 40) {
					clearInterval(poll); /* ~4s: fragment never arrived, give up quietly */
				}
			}, 100);
		});
	});
</script>
