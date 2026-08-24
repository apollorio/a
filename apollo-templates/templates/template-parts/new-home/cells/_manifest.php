<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * /casa — CELL MANIFEST
 * ═══════════════════════════════════════════════════════════════════════
 *
 * OWNS: the cell catalogue and the load contract. Nothing else. This file
 * declares no markup, no CSS and no behaviour — it is the socket board the
 * cells plug into.
 *
 * A CELL is one file that owns one concern and declares its ownership in
 * its own header. It may emit markup, a <style> block and a <script> block,
 * in that order, and it must be safe to include zero or many times (each
 * cell guards itself with a define()). Removing a cell from the manifest
 * must remove it from the page completely — no orphan selector, no orphan
 * listener, no console error. That is the whole test of "plug n play".
 *
 * ── Contract ──────────────────────────────────────────────────────────
 *   id        stable key; also the define() guard  APOLLO_CASA_CELL_{ID}
 *   file      relative to this directory
 *   owns      one line, the selector prefix / global this cell owns
 *   needs     cells that must load BEFORE it (topological, not textual)
 *   layer     'motion' | 'overlay' | 'section' — drives emit order
 *   guest     true if the cell renders for a logged-out visitor
 *
 * ── Why order matters ─────────────────────────────────────────────────
 * `motion` cells must be in the DOM before any `section` binds a
 * ScrollTrigger, or ScrollTrigger measures a layout the motion layer is
 * about to change. `overlay` cells go last so their fixed layers stack
 * above everything without a z-index arms race.
 *
 * ── Non-negotiables inherited from the registry ───────────────────────
 *   · `:root` belongs to core.js. A cell may declare component-scoped
 *     custom properties (`.cell { --x: … }`) and nothing else.
 *     → registry 03-apollo-rule, CLAUDE.md rule 1
 *   · No nonce in a data-* attribute. Ever. → registry 17-backlog TBD-005
 *   · No console.log in production.        → registry 17-backlog TBD-019
 *   · Every var() in a GEOMETRY declaration carries a fallback — core.js
 *     is a CDN and an unresolved token invalidates the declaration at
 *     computed-value time.                → _inventory/CASA-MAP-2026-08-08.md F-02
 *   · apollo_time_ago(), never human_time_diff().  → registry 15-conventions
 *   · No like / follow / friend vocabulary.        → registry 01-philosophy
 *
 * @package Apollo\Templates
 * @since   1.5.0
 * @see     _inventory/CASA-MAP-2026-08-08.md
 * @see     apollo-templates/_sandbox/build-casa-harness.mjs   the gate
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * The catalogue. Order inside a layer is the emit order.
 *
 * @return array<int,array<string,mixed>>
 */
function apollo_casa_cells(): array
{
    $cells = array(

        /* ── MOTION ────────────────────────────────────────────────────
           One cell owns scroll. Lenis is created by core.js; this cell
           only marries it to ScrollTrigger and publishes the timeline
           helpers the sections use. Nothing else may call
           gsap.registerPlugin() or ScrollTrigger.create() directly. */
        array(
            'id'    => 'motion',
            'file'  => 'motion.php',
            'owns'  => 'window.ApolloCasaMotion · ScrollTrigger/Lenis marriage · [data-casa-*] motion attributes',
            'needs' => array(),
            'layer' => 'motion',
            'guest' => true,
        ),

        /* ── OVERLAY ───────────────────────────────────────────────────
           The single-event reader. Before this cell existed, every event
           card on /casa carried `data-to="event-page" href="#"` and was
           bound by nobody — ApolloSlider is referenced in six panel-*.php
           files and defined in none of them, and page-home.php includes
           no panel at all. Guests are 100% of /casa traffic (mural-router
           302s members to /feed), so the primary CTA of the events
           section was dead for every visitor. This cell is the fix. */
        array(
            'id'    => 'lightbox-event',
            'file'  => 'lightbox-event.php',
            'owns'  => '.cev-* · window.ApolloEventLightbox · [data-casa-event] triggers',
            'needs' => array('motion'),
            'layer' => 'overlay',
            'guest' => true,
        ),
    );

    /**
     * Filter the /casa cell catalogue.
     *
     * A theme or plugin adds a cell by appending to this array; it removes
     * one by unsetting it. Nothing else in the page reads the cell list, so
     * this filter is the entire extension surface.
     *
     * @param array $cells The catalogue.
     */
    return (array) apply_filters('apollo/casa/cells', $cells);
}

/**
 * Emit one layer of the catalogue.
 *
 * Resolves `needs` before emitting, skips a cell whose file is missing
 * rather than fataling the page, and refuses to emit the same cell twice.
 *
 * @param string $layer 'motion' | 'overlay' | 'section'.
 */
function apollo_casa_render_cells(string $layer): void
{
    static $emitted = array();

    $dir   = __DIR__ . '/';
    $cells = apollo_casa_cells();
    $index = array();
    foreach ($cells as $cell) {
        $index[$cell['id']] = $cell;
    }

    $emit = static function (array $cell) use (&$emit, $index, $dir, &$emitted): void {
        if (isset($emitted[$cell['id']])) {
            return;
        }
        foreach ((array) ($cell['needs'] ?? array()) as $dep) {
            if (isset($index[$dep])) {
                $emit($index[$dep]);
            }
        }

        $path = $dir . $cell['file'];
        if (! is_readable($path)) {
            /* A missing cell is a deployment fault, not a reason to blank
               the page. Surface it where developers look, nowhere else. */
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('apollo_debug_log')) {
                apollo_debug_log('[casa] cell file missing: ' . $cell['file']);
            }
            return;
        }

        $emitted[$cell['id']] = true;
        require $path;
    };

    foreach ($cells as $cell) {
        if (($cell['layer'] ?? '') !== $layer) {
            continue;
        }
        if (empty($cell['guest']) && ! is_user_logged_in()) {
            continue;
        }
        $emit($cell);
    }
}
