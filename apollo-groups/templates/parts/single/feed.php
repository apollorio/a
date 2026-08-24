<?php
/**
 * Single Part — Feed
 *
 * Renders the feed by iterating group_posts and including post.php part.
 * Expects: $group_posts (array), $members (array for role lookup)
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $group_posts ) ) {
	return;
}

// Build role map from members
$role_map = array();
if ( ! empty( $members ) ) {
	foreach ( $members as $m ) {
		if ( isset( $m['user_id'] ) ) {
			$role_map[ (int) $m['user_id'] ] = $m['role'] ?? 'member';
		}
	}
}
?>
<div class="feed" id="groupFeed">
	<?php
	foreach ( $group_posts as $gp ) :
		// Skip pinned (already shown above)
		if ( ! empty( $gp['is_pinned'] ) ) {
			continue;
		}

		// Enrich with role
		$uid        = (int) ( $gp['user_id'] ?? 0 );
		$gp['role'] = $role_map[ $uid ] ?? '';

		$post_data = $gp;
		require __DIR__ . '/post.php';
	endforeach;
	?>
</div>
