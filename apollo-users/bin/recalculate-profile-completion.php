#!/usr/bin/env php
<?php
/**
 * Recalculate Profile Completion for All Users
 *
 * Usage: wp eval-file wp-content/plugins/apollo-users/bin/recalculate-profile-completion.php
 *
 * @package Apollo\Users
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	// Load WordPress if running as CLI script
	if ( PHP_SAPI === 'cli' ) {
		require_once dirname( __DIR__, 4 ) . '/wp-load.php';
	} else {
		exit( 'Direct access not allowed' );
	}
}

use Apollo\Users\Plugin;

echo "═══════════════════════════════════════════════════════\n";
echo "Apollo Users - Recalculate Profile Completion\n";
echo "Registry Compliance Tool\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Get all users
$users   = get_users( array( 'fields' => 'ID' ) );
$total   = count( $users );
$updated = 0;

echo "Found {$total} users to process...\n\n";

$plugin = Plugin::get_instance();

foreach ( $users as $user_id ) {
	$user = get_userdata( $user_id );

	// Initialize missing meta fields
	if ( ! metadata_exists( 'user', $user_id, '_apollo_user_verified' ) ) {
		update_user_meta( $user_id, '_apollo_user_verified', false );
	}

	if ( ! metadata_exists( 'user', $user_id, '_apollo_profile_completed' ) ) {
		update_user_meta( $user_id, '_apollo_profile_completed', 0 );
	}

	if ( ! metadata_exists( 'user', $user_id, '_apollo_privacy_profile' ) ) {
		update_user_meta( $user_id, '_apollo_privacy_profile', 'public' );
	}

	if ( ! metadata_exists( 'user', $user_id, '_apollo_privacy_email' ) ) {
		update_user_meta( $user_id, '_apollo_privacy_email', true );
	}

	if ( ! metadata_exists( 'user', $user_id, '_apollo_disable_author_url' ) ) {
		update_user_meta( $user_id, '_apollo_disable_author_url', true );
	}

	if ( ! metadata_exists( 'user', $user_id, '_apollo_profile_views' ) ) {
		update_user_meta( $user_id, '_apollo_profile_views', 0 );
	}

	// Recalculate profile completion
	$percentage = $plugin->update_profile_completion( $user_id );

	echo "✓ User #{$user_id} ({$user->user_login}): {$percentage}% complete\n";
	++$updated;
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "✅ COMPLETED\n";
echo "═══════════════════════════════════════════════════════\n";
echo "Total users processed: {$updated}/{$total}\n";
echo "All users now have registry-compliant meta fields.\n";
echo "═══════════════════════════════════════════════════════\n";
