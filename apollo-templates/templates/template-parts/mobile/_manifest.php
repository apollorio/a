<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * Blank Canvas — MOBILE CELL MANIFEST
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Plug-n-play socket board for premium mobile UX on Apollo / Apollo+.
 * Same contract as /casa cells: one file, one owns, filterable catalogue.
 *
 * Filter: apollo/mobile/cells
 * Emit:   apollo_mobile_render_cells()  (all layers, dependency-ordered)
 *
 * @package Apollo\Templates
 * @since   1.6.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * @return array<int,array<string,mixed>>
 */
function apollo_mobile_cells(): array
{
    $cells = array(
        array(
            'id'    => 'supervisor',
            'file'  => 'supervisor.php',
            'owns'  => 'window.ApolloSupervisor',
            'needs' => array(),
            'layer' => 'kernel',
            'guest' => true,
        ),
        array(
            'id'    => 'unlock',
            'file'  => 'unlock.php',
            'owns'  => 'window.ApolloMobileUnlock · lenis.gate · scroll.lockers.purge · touch.watchdog',
            'needs' => array('supervisor'),
            'layer' => 'unlock',
            'guest' => true,
        ),
        array(
            'id'    => 'gestures',
            'file'  => 'gestures.php',
            'owns'  => 'window.ApolloGestures · .ap-hover · .ap-ctx-menu · intelligent URL map',
            'needs' => array('supervisor'),
            'layer' => 'gestures',
            'guest' => true,
        ),
        array(
            'id'    => 'boot',
            'file'  => 'boot.php',
            'owns'  => 'registerUnits + runAll · window.__APOLLO_MOBILE_STATUS__',
            'needs' => array('supervisor', 'unlock', 'gestures'),
            'layer' => 'boot',
            'guest' => true,
        ),
    );

    return (array) apply_filters('apollo/mobile/cells', $cells);
}

/**
 * Emit mobile cells in dependency order (all layers).
 */
function apollo_mobile_render_cells(): void
{
    static $emitted = array();
    if (! empty($emitted['__done'])) {
        return;
    }

    $dir   = __DIR__ . '/';
    $cells = apollo_mobile_cells();
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
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('apollo_debug_log')) {
                apollo_debug_log('[mobile] cell file missing: ' . $cell['file']);
            }
            return;
        }

        $emitted[$cell['id']] = true;
        require $path;
    };

    foreach ($cells as $cell) {
        if (empty($cell['guest']) && ! is_user_logged_in()) {
            continue;
        }
        $emit($cell);
    }

    $emitted['__done'] = true;
}
