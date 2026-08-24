<?php
/**
 * Single Part — Loader
 *
 * Full-screen page loader.
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="loader" id="pageLoader">
	<span>apollo</span>
</div>

<?php
if ( function_exists( 'apollo_render_navbar' ) ) {
	apollo_render_navbar();
}
?>
