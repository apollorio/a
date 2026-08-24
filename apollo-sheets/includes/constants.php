<?php

/**
 * Plugin Constants
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// REST API namespace (shared Apollo-wide)
if ( ! defined( 'APOLLO_SHEETS_REST_NAMESPACE' ) ) {
	define( 'APOLLO_SHEETS_REST_NAMESPACE', 'apollo/v1' );
}

// Table scheme version (for migrations)
if ( ! defined( 'APOLLO_SHEETS_TABLE_SCHEME' ) ) {
	define( 'APOLLO_SHEETS_TABLE_SCHEME', 1 );
}

// Default rows/columns for new sheets
if ( ! defined( 'APOLLO_SHEETS_DEFAULT_ROWS' ) ) {
	define( 'APOLLO_SHEETS_DEFAULT_ROWS', 5 );
}
if ( ! defined( 'APOLLO_SHEETS_DEFAULT_COLS' ) ) {
	define( 'APOLLO_SHEETS_DEFAULT_COLS', 5 );
}

// Max pagination entries for frontend DataTables
if ( ! defined( 'APOLLO_SHEETS_MAX_ENTRIES' ) ) {
	define( 'APOLLO_SHEETS_MAX_ENTRIES', 100 );
}
