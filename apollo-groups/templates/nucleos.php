<?php
/**
 * Núcleos Directory — /nucleos
 *
 * Shows only núcleos (private work teams).
 * Wrapper that loads groups-directory.php with $page_type = 'nucleos'.
 *
 * Registry: { slug: "nucleos", template: "nucleos.php", type: "virtual" }
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

// Force "nucleos" context
$page_type = 'nucleos';

require __DIR__ . '/groups-directory.php';
