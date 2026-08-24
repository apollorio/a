<?php

/**
 * Apollo+ Shell — Topbar behaviour (#ic-apps / #ic-pf panels).
 *
 * WHY THIS FILE EXISTS (root-cause fix, 2026-07-30)
 * -------------------------------------------------
 * app-shell.php renders #ic-apps (Apollo Suite grid) and #ic-pf (profile
 * panel) plus their #apps-pop / #panel-profile containers on every Blank
 * Canvas Apollo+ screen, but NOTHING bound them: the only wiring lived in
 * page-home.php's own inline script (i.e. /casa). So on /feed, /eventos,
 * /anuncios, /comunas, /hub, /mapa, /eventos/meus and /anuncios/meus both
 * buttons were dead — two visible, focusable controls in the topbar that did
 * nothing when tapped.
 *
 * aside.php already owns #burger → .ax-aside and reuses .ax-overlay; this
 * file adds the two right-hand controls using the SAME open/close vocabulary
 * (.open on the panel, .on on the shared overlay) so all three surfaces
 * behave identically and only one can be open at a time.
 *
 * House rules honoured (apollo-rio dev skill, "FORBIDDEN JS PATTERNS"):
 *   · no observers created, so none to leak;
 *   · single delegated document listener instead of per-node handlers;
 *   · no scroll lock invented here — the panels are fixed overlays and the
 *     aside is the only surface that ever needed one, so nothing to restore;
 *   · idempotent via a window flag, so a screen that closes the shell twice
 *     cannot double-bind.
 *
 * @package Apollo\Templates
 * @since   1.5.0
 * @see     template-parts/app-shell.php                  markup
 * @see     template-parts/apollo-plus/topbar-styles.php   .open/.on styles
 */

if (! defined('ABSPATH')) {
    exit;
}

if (defined('APOLLO_PLUS_TOPBAR_SCRIPTS')) {
    return;
}
define('APOLLO_PLUS_TOPBAR_SCRIPTS', true);

/* Guest pages render no #ic-apps/#ic-pf at all — skip the payload entirely. */
if (! is_user_logged_in()) {
    return;
}
?>
<script>
(function (d) {
    'use strict';
    if (window.__apolloPlusTopbar) { return; }
    window.__apolloPlusTopbar = true;

    var overlay = d.querySelector('.ax-overlay');
    var aside   = d.getElementById('ax-aside');

    /* Every surface the topbar can open, keyed by the control that opens it. */
    var PANELS = {
        'ic-apps': 'apps-pop',
        'ic-pf':   'panel-profile'
    };

    function panelEls() {
        var out = [];
        for (var k in PANELS) {
            if (!Object.prototype.hasOwnProperty.call(PANELS, k)) { continue; }
            var el = d.getElementById(PANELS[k]);
            if (el) { out.push(el); }
        }
        return out;
    }

    function anyOpen() {
        var list = panelEls();
        for (var i = 0; i < list.length; i++) {
            if (list[i].classList.contains('open')) { return true; }
        }
        return false;
    }

    function syncOverlay() {
        if (!overlay) { return; }
        /* The aside owns the overlay too — never switch it off while the
           drawer is still open (that would strand an un-dismissable menu). */
        var asideOpen = !!(aside && aside.classList.contains('open'));
        overlay.classList.toggle('on', asideOpen || anyOpen());
    }

    function closeAll() {
        panelEls().forEach(function (el) {
            el.classList.remove('open');
            el.setAttribute('aria-hidden', 'true');
        });
        syncOverlay();
    }

    function toggle(id) {
        var el = d.getElementById(id);
        if (!el) { return; }
        var willOpen = !el.classList.contains('open');
        closeAll();
        if (willOpen) {
            el.classList.add('open');
            el.setAttribute('aria-hidden', 'false');
        }
        syncOverlay();
    }

    d.addEventListener('click', function (e) {
        var trigger = e.target.closest('#ic-apps, #ic-pf');
        if (trigger) {
            e.preventDefault();
            e.stopPropagation();
            toggle(PANELS[trigger.id]);
            return;
        }

        /* Explicit close controls rendered by app-shell.php. */
        if (e.target.closest('[data-close-apps], [data-close="panel-profile"]')) {
            e.preventDefault();
            closeAll();
            return;
        }

        /* Click-away: anywhere outside an open panel (and outside its own
           trigger, handled above) dismisses it — including the overlay. */
        if (anyOpen() && !e.target.closest('.apps-pop, .panel-r')) {
            closeAll();
        }
    });

    d.addEventListener('keydown', function (e) {
        if ('Escape' === e.key && anyOpen()) { closeAll(); }
    });
})(document);
</script>
