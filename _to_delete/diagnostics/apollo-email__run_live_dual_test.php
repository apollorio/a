<?php
/**
 * One-shot LIVE SMTP test — verification + welcome (registration flow).
 *
 * Usage:
 *   php _run_live_dual_test.php rafapevalle@gmail.com rafael@rvalle.com.br
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'DB_HOST' ) ) {
	define( 'DB_HOST', '127.0.0.1:10029' );
}
if ( ! defined( 'APOLLO_SMTP_LIVE' ) ) {
	define( 'APOLLO_SMTP_LIVE', true );
}

$_SERVER['HTTP_HOST']   = 'fim.local';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_NAME'] = 'fim.local';

$wp_root = dirname( __FILE__, 4 );
require_once $wp_root . '/wp-load.php';

$recipients = array();
foreach ( array_slice( $argv, 1 ) as $arg ) {
	$email = sanitize_email( $arg );
	if ( is_email( $email ) ) {
		$recipients[] = $email;
	}
}
if ( empty( $recipients ) ) {
	$recipients = array( 'rafapevalle@gmail.com', 'rafael@rvalle.com.br' );
}

$login_functions = WP_PLUGIN_DIR . '/apollo-login/includes/functions.php';
if ( file_exists( $login_functions ) ) {
	require_once $login_functions;
}

if ( ! class_exists( 'Apollo\\Email\\Plugin' ) ) {
	echo "FATAL: apollo-email not loaded.\n";
	exit( 1 );
}

$plugin = Apollo\Email\Plugin::instance();

echo "From:     " . Apollo\Email\Plugin::fromEmail() . ' (' . Apollo\Email\Plugin::fromName() . ")\n";
echo 'Transport: ' . ( ( defined( 'APOLLO_SMTP_LIVE' ) && APOLLO_SMTP_LIVE ) ? 'SMTP LIVE' : 'Mailpit' ) . "\n";
echo 'SMTP host: ' . ( defined( 'APOLLO_SMTP_HOST' ) ? APOLLO_SMTP_HOST : 'n/a' ) . "\n\n";

$all_ok = true;

foreach ( $recipients as $to ) {
	echo "=== {$to} ===\n";

	$user           = get_user_by( 'email', $to );
	$target_user_id = $user ? (int) $user->ID : 1;
	$userdata       = get_userdata( $target_user_id );

	if ( $user ) {
		echo "User: #{$target_user_id} {$userdata->user_login}\n";
	} else {
		echo "No WP user — token for admin #{$target_user_id}, deliver TO {$to}\n";
	}

	$token = function_exists( 'Apollo\\Login\\apollo_generate_verification_token' )
		? \Apollo\Login\apollo_generate_verification_token( $target_user_id )
		: wp_generate_password( 32, false );

	if ( ! function_exists( 'Apollo\\Login\\apollo_generate_verification_token' ) ) {
		update_user_meta( $target_user_id, '_apollo_verification_token', $token );
		update_user_meta( $target_user_id, '_apollo_verification_token_expiry', time() + DAY_IN_SECONDS );
	}

	$verify_url   = add_query_arg( array( 'user' => $target_user_id, 'token' => $token ), home_url( '/verificar-email/' ) );
	$display_name = $userdata->display_name ?: $userdata->user_login;

	$tests = array(
		'verification' => array(
			'subject' => '[Apollo Teste] Confirme seu email',
			'data'    => array(
				'user_id'           => $target_user_id,
				'user_name'         => $display_name,
				'username'          => $userdata->user_login,
				'user_email'        => $to,
				'verify_url'        => $verify_url,
				'confirmation_link' => $verify_url,
				'registration_date' => wp_date( 'd/m/Y', strtotime( $userdata->user_registered ) ),
				'expires_in'        => '24 horas',
				'expiration_time'   => '24 horas',
				'site_name'         => get_bloginfo( 'name' ),
				'site_url'          => home_url( '/' ),
			),
		),
		'welcome'      => array(
			'subject' => '[Apollo Teste] Bem-vindo(a)!',
			'data'    => array(
				'user_id'      => $target_user_id,
				'user_name'    => $display_name,
				'username'     => $userdata->user_login,
				'user_email'   => $to,
				'profile_url'  => home_url( '/id/' . $userdata->user_login ),
				'plan_name'    => 'Apollo Gratuito',
				'member_since' => wp_date( 'd/m/Y', strtotime( $userdata->user_registered ) ),
				'site_name'    => get_bloginfo( 'name' ),
				'site_url'     => home_url( '/' ),
			),
		),
	);

	foreach ( $tests as $slug => $config ) {
		$result = $plugin->sender()->sendTemplate( $to, $config['subject'], $slug, $config['data'] );
		if ( $result['success'] ) {
			echo "  ✓ {$slug} SENT";
			if ( ! empty( $result['log_id'] ) ) {
				echo " (log #{$result['log_id']})";
			}
			echo "\n";
		} else {
			$all_ok = false;
			echo "  ✗ {$slug} FAILED — " . ( $result['error'] ?? 'unknown' ) . "\n";
		}
	}

	echo "  Verify URL: {$verify_url}\n\n";
}

exit( $all_ok ? 0 : 1 );
