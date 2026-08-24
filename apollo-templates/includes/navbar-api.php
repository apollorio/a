<?php

/**
 * Global Navbar API — Single entry-point for ALL plugins.
 *
 * Provides apollo_render_navbar() and apollo_get_navbar() in the global
 * namespace so any plugin can load the unified navbar.v2 without
 * hard-coding include paths or plugin dependencies.
 *
 * @package Apollo\Templates
 * @since   6.2.0
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Render the unified Apollo navbar (v2).
 *
 * Prevents double-load via the APOLLO_NAVBAR_LOADED constant
 * (set inside navbar.v2.php on first include).
 */
function apollo_render_navbar(): void
{
    /* 1 — MANDATORY layout contract: the canonical Apollo topbar/app-shell.
       Idempotent (APOLLO_APP_SHELL_LOADED), so every one of the ~26 legacy
       apollo_render_navbar() call sites across the ecosystem now renders the
       approved .ax-top chrome without editing a single consumer plugin. */
    if (function_exists('apollo_render_app_shell')) {
        apollo_render_app_shell();
    }

    /* 2 — navbar.v2 still supplies the FAB + navigation sheet (its unique
       contribution). Its legacy nh-navbar block self-skips now that the
       app-shell is present, so no double topbar can occur. */
    if (defined('APOLLO_NAVBAR_V2_LOADED')) {
        return;
    }
    define('APOLLO_NAVBAR_V2_LOADED', true);

    $path = APOLLO_TEMPLATES_DIR . 'templates/template-parts/navbar.v2.php';
    if (file_exists($path)) {
        include $path;
    }
}

/**
 * Alias for apollo_render_navbar() — legacy compatibility.
 */
function apollo_get_navbar(): void
{
    apollo_render_navbar();
}

/**
 * Render the canonical Apollo APP-SHELL topbar (showcase contract).
 *
 * Single source of truth for .ax-top / .ax-overlay / .apps-pop / .panel-profile
 * across every Apollo page — including "blank canvas apollo+" templates, which
 * render no chrome of their own. Auth-aware (guest → login · logged → showcase
 * ic-act / ic-apps / ax-avb) and self-excluding on /acesso and /registre.
 *
 * Any template, any plugin:
 *   if ( function_exists( 'apollo_render_app_shell' ) ) { apollo_render_app_shell(); }
 */
function apollo_render_app_shell(): void
{
    if (defined('APOLLO_APP_SHELL_LOADED')) {
        return;
    }
    define('APOLLO_APP_SHELL_LOADED', true);

    $path = APOLLO_TEMPLATES_DIR . 'templates/template-parts/app-shell.php';
    if (file_exists($path)) {
        include $path;
    }
}
