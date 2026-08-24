<?php

/**
 * Portal de Eventos — styles: Base
 *
 * Host scoping (.pev-host/.pev) plus the shared reveal/sentinel rules used by every infinite-load section on the screen.
 *
 * PHASE 002: split out of the single 979-line portal-styles.php. Values are
 * unchanged from the mockup's portal-eventos.css -- this is a split, not a
 * restyle. NEVER redeclare :root; core.js owns the tokens.
 *
 * @package Apollo\Event
 * @since   1.5.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-pev-base">
.pev-host,
.pev {
  color: var(--txt-color);
  font-family: var(--ff-main);
  position: relative;
  overflow: visible !important;
  padding-bottom: 80px;
  box-sizing: border-box;
}
</style>
