#!/usr/bin/env php
<?php

// plan-001 · G07 — file was directly reachable over HTTP: CLI still works, the web does not.
if ( PHP_SAPI !== 'cli' && ! defined( 'ABSPATH' ) ) { exit; }
/**
 * Apollo Remind — CLI Setup Helper
 *
 * Usage (from plugin dir):
 *   php setup.php vapid       → Generate VAPID key pair
 *   php setup.php webhook     → Set Telegram webhook URL
 *   php setup.php webhook-info → Check current webhook status
 *   php setup.php test-tg     → Send test message to a chat_id
 *
 * Requires: WordPress loaded (run via WP-CLI or include wp-load.php)
 */

if ( php_sapi_name() !== 'cli' ) {
    die( 'CLI only.' );
}

// Try to load WordPress
$wp_load_paths = [
    dirname( __FILE__, 4 ) . '/wp-load.php',        // standard: wp-content/plugins/apollo-remind/
    dirname( __FILE__, 5 ) . '/wp-load.php',         // nested
];

$wp_loaded = false;
foreach ( $wp_load_paths as $path ) {
    if ( file_exists( $path ) ) {
        require_once $path;
        $wp_loaded = true;
        break;
    }
}

$cmd = $argv[1] ?? 'help';

switch ( $cmd ) {

    case 'vapid':
        echo "Generating VAPID key pair...\n\n";

        // Check if openssl P-256 is available
        $config = [
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name'       => 'prime256v1',
        ];
        $res = openssl_pkey_new( $config );
        if ( ! $res ) {
            die( "Error: OpenSSL cannot generate P-256 keys. Check your PHP openssl extension.\n" );
        }

        openssl_pkey_export( $res, $pem );
        $details = openssl_pkey_get_details( $res );

        // Extract raw keys and encode as base64url
        $x = rtrim( strtr( base64_encode( $details['ec']['x'] ), '+/', '-_' ), '=' );
        $y = rtrim( strtr( base64_encode( $details['ec']['y'] ), '+/', '-_' ), '=' );
        $d = rtrim( strtr( base64_encode( $details['ec']['d'] ), '+/', '-_' ), '=' );

        // Public key = 04 + x + y (uncompressed point)
        $publicKeyBin = "\x04" . $details['ec']['x'] . $details['ec']['y'];
        $publicKeyB64 = rtrim( strtr( base64_encode( $publicKeyBin ), '+/', '-_' ), '=' );

        echo "Add these to your wp-config.php:\n\n";
        echo "define( 'APOLLO_VAPID_PUBLIC_KEY',  '$publicKeyB64' );\n";
        echo "define( 'APOLLO_VAPID_PRIVATE_KEY', '$d' );\n\n";

        echo "Public Key (for JS):  $publicKeyB64\n";
        echo "Private Key (server): $d\n";
        break;

    case 'webhook':
        if ( ! $wp_loaded ) {
            die( "WordPress not found. Run from plugin directory.\n" );
        }
        $token = apollo_remind_get_bot_token();
        if ( ! $token ) {
            die( "No bot token configured. Set APOLLO_TELEGRAM_BOT_TOKEN in wp-config.php\n" );
        }
        $url = rest_url( 'apollo/v1/remind/telegram/webhook' );
        echo "Setting webhook to: $url\n";

        $resp = wp_remote_post( "https://api.telegram.org/bot{$token}/setWebhook", [
            'body' => [ 'url' => $url ],
        ] );
        echo wp_remote_retrieve_body( $resp ) . "\n";
        break;

    case 'webhook-info':
        if ( ! $wp_loaded ) die( "WordPress not found.\n" );
        $token = apollo_remind_get_bot_token();
        $resp  = wp_remote_get( "https://api.telegram.org/bot{$token}/getWebhookInfo" );
        echo wp_remote_retrieve_body( $resp ) . "\n";
        break;

    case 'test-tg':
        if ( ! $wp_loaded ) die( "WordPress not found.\n" );
        $chat_id = $argv[2] ?? '';
        if ( ! $chat_id ) die( "Usage: php setup.php test-tg CHAT_ID\n" );

        $token = apollo_remind_get_bot_token();
        $resp  = wp_remote_post( "https://api.telegram.org/bot{$token}/sendMessage", [
            'body' => [
                'chat_id' => $chat_id,
                'text'    => '🔔 Apollo::Rio test reminder — if you see this, Telegram delivery works!',
            ],
        ] );
        echo wp_remote_retrieve_body( $resp ) . "\n";
        break;

    default:
        echo <<<HELP
        Apollo Remind — CLI Setup

        Commands:
          vapid          Generate VAPID key pair for Web Push
          webhook        Set Telegram Bot webhook URL
          webhook-info   Check current webhook status
          test-tg ID     Send test message to Telegram chat_id

        HELP;
        break;
}
