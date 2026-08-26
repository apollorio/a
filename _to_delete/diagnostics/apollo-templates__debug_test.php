<?php
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 3 ) . '/wp-load.php';

// Force flush
flush_rewrite_rules( true );

$rules = get_option( 'rewrite_rules' );
$found = 0;
if ( is_array( $rules ) ) {
	foreach ( $rules as $p => $q ) {
		if ( strpos( $p, 'test' ) !== false ) {
			echo "$p => $q\n";
			++$found;
		}
	}
}
echo "Found test rules: $found\n";

// Also try direct template loading approach
echo "\nChecking direct approach...\n";
echo "query_var registered: checking...\n";

// Delete and re-flush
delete_option( 'rewrite_rules' );
flush_rewrite_rules( true );
echo "Rewrite rules RE-FLUSHED!\n";

$rules2 = get_option( 'rewrite_rules' );
$found2 = 0;
if ( is_array( $rules2 ) ) {
	foreach ( $rules2 as $p => $q ) {
		if ( strpos( $p, 'test' ) !== false ) {
			echo "AFTER: $p => $q\n";
			++$found2;
		}
	}
}
echo "Found after re-flush: $found2\n";
