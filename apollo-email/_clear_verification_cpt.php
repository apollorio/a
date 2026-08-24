<?php
/**
 * One-shot: clear corrupted verification CPT (WordPress strips <head>/<style> from post_content).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'DB_HOST' ) ) {
	define( 'DB_HOST', '127.0.0.1:10025' );
}
$_SERVER['HTTP_HOST'] = 'aprio.local';
require dirname( __FILE__, 4 ) . '/wp-load.php';

$posts = get_posts(
	array(
		'post_type'   => 'email_aprio',
		'name'        => 'verification',
		'post_status' => 'any',
		'numberposts' => 1,
	)
);

if ( empty( $posts ) ) {
	echo "No verification CPT found.\n";
	exit( 0 );
}

$id = (int) $posts[0]->ID;
wp_update_post(
	array(
		'ID'           => $id,
		'post_content' => '<!-- Managed by templates/emails/verification.php (standalone). -->',
	)
);

echo "Cleared verification CPT #{$id} — file template is now source of truth.\n";
