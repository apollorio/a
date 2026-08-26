<?php
/**
 * One-shot: send registration verification email (same template/hook as /registre finalize).
 *
 * Usage (LocalWP site shell or):
 *   php ... _run_aprio_verification_test.php rafapevalle@gmail.com
 *   php ... _run_aprio_verification_test.php rafapevalle@gmail.com live
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'DB_HOST' ) ) {
	define( 'DB_HOST', '127.0.0.1:10025' );
}

$live_arg = in_array( 'live', $argv, true );
if ( $live_arg && ! defined( 'APOLLO_SMTP_LIVE' ) ) {
	define( 'APOLLO_SMTP_LIVE', true );
}

$_SERVER['HTTP_HOST']   = 'aprio.local';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_NAME'] = 'aprio.local';

$wp_root = dirname( __FILE__, 4 );
require_once $wp_root . '/wp-load.php';

$to = 'rafapevalle@gmail.com';
foreach ( array_slice( $argv, 1 ) as $arg ) {
	if ( 'live' === $arg ) {
		continue;
	}
	if ( str_contains( $arg, '@' ) ) {
		$to = sanitize_email( $arg );
	}
}
if ( ! is_email( $to ) ) {
	echo "Invalid email: {$to}\n";
	exit( 1 );
}

$user = get_user_by( 'email', $to );
$target_user_id = $user ? (int) $user->ID : 1;
$userdata       = get_userdata( $target_user_id );

if ( $user ) {
	echo "User: #{$target_user_id} {$user->user_login} ({$user->user_email})\n";
} else {
	echo "No WP user for {$to} — using admin #{$target_user_id}, delivering TO {$to}\n";
}

$token = \Apollo\Login\apollo_generate_verification_token( $target_user_id );
$verify_url = add_query_arg(
	array(
		'user'  => $target_user_id,
		'token' => $token,
	),
	home_url( '/verificar-email/' )
);

echo "Token:      {$token}\n";
echo "Verify URL: {$verify_url}\n";
echo 'Transport:  ' . ( ( defined( 'APOLLO_SMTP_LIVE' ) && APOLLO_SMTP_LIVE ) ? 'SMTP LIVE' : 'Mailpit (local)' ) . "\n\n";

if ( ! class_exists( 'Apollo\\Email\\Plugin' ) ) {
	echo "FATAL: apollo-email not loaded.\n";
	exit( 1 );
}

$plugin = Apollo\Email\Plugin::instance();
$result = $plugin->sender()->sendTemplate(
	$to,
	__( 'Verifique seu email — Apollo Rio', 'apollo-email' ),
	'verification',
	array(
		'user_id'           => $target_user_id,
		'user_name'         => $userdata->display_name ?: $userdata->user_login,
		'username'          => $userdata->user_login,
		'verify_url'        => $verify_url,
		'confirmation_link' => $verify_url,
		'site_name'         => get_bloginfo( 'name' ),
		'site_url'          => home_url( '/' ),
	)
);

if ( $result['success'] ) {
	echo "✓ SENT verification email to {$to}\n";
	if ( ! empty( $result['log_id'] ) ) {
		echo "  Log #{$result['log_id']}\n";
	}
	echo "\n" . ( ( defined( 'APOLLO_SMTP_LIVE' ) && APOLLO_SMTP_LIVE )
		? "Delivered via SMTP (no-reply@apollo.rio.br).\n"
		: "Mailpit inbox: http://localhost:10005 (SMTP port APOLLO_MAILPIT_PORT)\n" );
	exit( 0 );
}

echo "✗ FAILED: " . ( $result['error'] ?? 'unknown' ) . "\n";
exit( 1 );
