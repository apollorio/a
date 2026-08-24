<?php
/**
 * Single Part — Sidebar Stats
 *
 * Stats row: members, posts, online.
 * Expects: $member_count, $group_posts (array), $active_now (int)
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$post_count = is_array( $group_posts ) ? count( $group_posts ) : 0;
$active_now = $active_now ?? 0;
?>
<div class="sb-stats">
	<div class="sb-stat">
		<span class="sb-stat-num"><?php echo esc_html( $member_count ); ?></span>
		<span class="sb-stat-label">Membros</span>
	</div>
	<div class="sb-stat">
		<span class="sb-stat-num"><?php echo esc_html( $post_count ); ?></span>
		<span class="sb-stat-label">Posts</span>
	</div>
	<div class="sb-stat">
		<span class="sb-stat-num"><?php echo esc_html( $active_now ); ?></span>
		<span class="sb-stat-label">Online</span>
	</div>
</div>
