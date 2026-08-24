<?php
/**
 * Plugin Constants
 *
 * @package Apollo\Journal
 */

declare(strict_types=1);

namespace Apollo\Journal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** REST API namespace shared across Apollo ecosystem. */
define( 'APOLLO_JOURNAL_REST_NAMESPACE', 'apollo/v1' );

/** NREP default prefix. */
define( 'APOLLO_JOURNAL_NREP_PREFIX', 'NREP.' );
