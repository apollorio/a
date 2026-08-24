<?php
/**
 * Widgets Loader
 *
 * Registers classified-related widgets.
 * Adapted from WPAdverts widget registration pattern.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register widgets
 */
function apollo_adverts_register_widgets(): void {
	require_once APOLLO_ADVERTS_DIR . 'includes/widgets/class-classifieds-widget.php';
	require_once APOLLO_ADVERTS_DIR . 'includes/widgets/class-categories-widget.php';

	register_widget( 'Apollo_Classifieds_Widget' );
	register_widget( 'Apollo_Classifieds_Categories_Widget' );
}
add_action( 'widgets_init', 'apollo_adverts_register_widgets' );
