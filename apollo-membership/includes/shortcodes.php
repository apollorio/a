<?php
/**
 * Shortcodes Loader
 *
 * Registers and loads all shortcode files.
 * Adapted from BadgeOS shortcodes.php pattern.
 *
 * Registry spec: 9 shortcodes
 *   apollo_achievements, apollo_achievement, apollo_user_achievements,
 *   apollo_points, apollo_ranks, apollo_rank, apollo_user_rank,
 *   apollo_leaderboard, apollo_evidence
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include all shortcode files
 */
function apollo_membership_load_shortcodes(): void {
	$shortcode_dir = APOLLO_MEMBERSHIP_DIR . 'includes/shortcodes/';

	$shortcode_files = array(
		'apollo-achievements-list.php',
		'apollo-achievement-single.php',
		'apollo-user-achievements.php',
		'apollo-points.php',
		'apollo-ranks.php',
		'apollo-rank-single.php',
		'apollo-user-rank.php',
		'apollo-leaderboard.php',
		'apollo-evidence.php',
	);

	foreach ( $shortcode_files as $file ) {
		$path = $shortcode_dir . $file;
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
}
add_action( 'init', 'apollo_membership_load_shortcodes', 25 );
