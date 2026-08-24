<?php

/**
 * Template Part: Preloader — Shrikhand "Apollo" pulse animation
 *
 * Dark overlay with luxury brand text that pulses while page loads.
 * Removed with GSAP or CSS transition after DOMContentLoaded.
 *
 * @package Apollo\Social
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="page-loader" id="pageLoader" aria-hidden="true">
	<div style="font-family:var(--ff-fun,'Shrikhand',cursive);font-size:28px;color:#fff;letter-spacing:1px;animation:pulse 2s ease-in-out infinite">Apollo</div>
</div>
