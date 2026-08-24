<?php

/**
 * Apollo Navbar Component — Redirect to v2
 *
 * This file is kept for backward-compatibility only.
 * All plugins that include this path will load navbar.v2.php (the unified navbar).
 *
 * @package Apollo\Templates
 * @since 1.0.0
 * @deprecated 6.2.0 Use apollo_render_navbar() or include navbar.v2.php directly.
 */

if (! defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/navbar.v2.php';
