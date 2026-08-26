<?php
/**
 * Timezone Debug Script
 * Access: http://localhost:10004/wp-content/plugins/apollo-templates/test-timezone.php
 */

// Load WordPress
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/wp-load.php';

echo '<h1>WordPress Timezone Debug</h1>';
echo '<pre>';

echo 'PHP date():           ' . date( 'Y-m-d H:i:s' ) . "\n";
echo 'PHP timezone:         ' . date_default_timezone_get() . "\n\n";

echo "WP current_time('mysql'):      " . current_time( 'mysql' ) . "\n";
echo "WP current_time('timestamp'):  " . current_time( 'timestamp' ) . "\n";
echo "WP current_time('Y-m-d H:i:s'): " . current_time( 'Y-m-d H:i:s' ) . "\n";
echo "WP current_time('G'):          " . current_time( 'G' ) . " (hour without leading zero)\n";
echo "WP current_time('H'):          " . current_time( 'H' ) . " (hour with leading zero)\n";
echo "WP current_time('N'):          " . current_time( 'N' ) . " (weekday 1-7)\n\n";

echo 'WP timezone_string:   ' . get_option( 'timezone_string' ) . "\n";
echo 'WP gmt_offset:        ' . get_option( 'gmt_offset' ) . "\n\n";

$hour = (int) current_time( 'G' );
echo 'Extracted hour (int): ' . $hour . "\n\n";

if ( $hour < 6 ) {
	$greeting = 'Boa madrugada';
} elseif ( $hour < 12 ) {
	$greeting = 'Bom dia';
} elseif ( $hour < 18 ) {
	$greeting = 'Boa tarde';
} else {
	$greeting = 'Boa noite';
}

echo 'Greeting should be:   <strong>' . $greeting . "</strong>\n";

echo '</pre>';
