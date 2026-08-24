<?php

/**
 * New Home Navbar — Redirect to unified v2
 *
 * @package Apollo\Templates
 * @since 6.2.0
 * @deprecated 6.2.0 Use apollo_render_navbar() or include navbar.v2.php directly.
 */

if (! defined('ABSPATH')) {
    exit;
}

require dirname(__DIR__) . '/navbar.v2.php';
