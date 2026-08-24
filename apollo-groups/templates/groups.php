<?php
/**
 * Groups Directory — /grupos
 *
 * Shows ALL groups (comunas + núcleos).
 * Wrapper that loads groups-directory.php with $page_type = 'all'.
 *
 * Registry: { slug: "grupos", template: "groups.php", type: "virtual" }
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

// Force "all" context
$page_type = 'all';

require __DIR__ . '/groups-directory.php';
