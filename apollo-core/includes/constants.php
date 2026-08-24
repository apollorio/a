<?php
/**
 * Apollo Core Constants
 *
 * Central constant definitions for the entire Apollo ecosystem.
 * Now powered by config/constants.php — single source of truth.
 *
 * @package Apollo\Core
 * @since 6.0.0
 * @updated 6.1.0 — Migrated to ConfigLoader pattern
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// LOAD FROM CENTRAL CONFIG
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Load all constants from config/constants.php via ConfigLoader.
 *
 * The config file returns an associative array of CONSTANT_NAME => value.
 * Each is defined with a ! defined() guard for backward compatibility.
 *
 * Note: ConfigLoader is available because the PSR-4 autoloader is
 * registered BEFORE this file is loaded in apollo-core.php.
 */
\Apollo\Core\Config\ConfigLoader::define_constants();

// ═══════════════════════════════════════════════════════════════════════════
// RUNTIME CONSTANTS (cannot be in config — require function calls)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Apollo Debug Mode — loaded from wp_options at runtime.
 */
if ( ! defined( 'APOLLO_DEBUG' ) ) {
	define( 'APOLLO_DEBUG', get_option( 'apollo_debug_mode', false ) );
}
