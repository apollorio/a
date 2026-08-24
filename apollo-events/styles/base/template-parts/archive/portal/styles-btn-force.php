<?php

/**
 * Portal de Eventos — styles: forced `.btn` / `.btn-secondary` match.
 *
 * core.js already injects the universal button layer from <head>, but on
 * /eventos · /portal · /portal/eventos that is not enough to guarantee the
 * visual contract:
 *
 *   1. `.ax-main .btn { font-size: calc(var(--fsx) * …) }` references `--fsx`,
 *      which production does not ship (only `--fs-u`). Invalid calc() is
 *      dropped and the type scale never lands.
 *   2. Portal cells print AFTER core.js, so equal-specificity paint here wins
 *      by order. Re-declaring the mandatory stack in this cell is the brutal
 *      match — not another "trust core only" strip.
 *
 * The ONLY intentional fork vs the shared paste is `.ax-main .btn` at 11px /
 * font-weight 400 (the paste's 12.5px / 500 does not apply on this page family).
 *
 * Tax-chip deltas (scroller, truncation, `.is-on`) stay in styles-browse.php.
 *
 * @package Apollo\Event
 * @since   1.7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-pev-btn-force">
/* Alias so .ax-main .btn calcs resolve on this page family. */
.pev,
.ax-main {
  --fsx: var(--fs-u, 1);
}

.btn {
  corner-shape: squircle;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 7px 14px;
  border-radius: var(--r-pill);
  font-family: var(--ff-main);
  font-size: var(--fs-body-sm);
  font-weight: 500;
  color: var(--txt-heading);
  background: var(--surface);
  cursor: pointer;
  position: relative;
  overflow: hidden;
  white-space: nowrap;
}
.btn-secondary {
  corner-shape: squircle;
  background: var(--card);
  color: var(--txt-heading);
  border: 1px solid rgba(var(--rgb-diff), .04);
  box-shadow:
    inset 0 1px 0 0 rgba(var(--rgb-theme), .2),
    inset 0 0 0 1px rgba(var(--rgb-theme), .075);
}
.btn:active {
  transform: scale(.97);
}
.btn-secondary:hover {
  background: var(--card-hover);
  transform: translateY(-1px);
}
.btn.btn {
  background: var(--surface);
}
.btn.btn-secondary {
  background: var(--card);
  border: 1px solid rgba(var(--rgb-diff), .04);
}
.btn.btn-secondary:hover {
  background: var(--card-hover);
}
.ax-body .btn,
.ax-ff-section .btn {
  font-family: var(--ff-main) !important;
}

/* ONLY exception vs the shared 12.5px / weight-from-.btn paste. */
.ax-main .btn {
  font-size: calc(var(--fsx) * 11px) !important;
  font-weight: 400 !important;
}
</style>
