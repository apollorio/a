<?php
/**
 * Widgets Loader
 *
 * Registers and loads all widget files.
 * 3 widgets per registry spec.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include all widget files and register widgets
 */
function apollo_membership_load_widgets(): void {
	$widget_dir = APOLLO_MEMBERSHIP_DIR . 'includes/widgets/';

	require_once $widget_dir . 'class-earned-achievements-widget.php';
	require_once $widget_dir . 'class-earned-points-widget.php';
	require_once $widget_dir . 'class-earned-ranks-widget.php';

	register_widget( 'Apollo_Earned_Achievements_Widget' );
	register_widget( 'Apollo_Earned_Points_Widget' );
	register_widget( 'Apollo_Earned_Ranks_Widget' );
}
add_action( 'widgets_init', 'apollo_membership_load_widgets' );
