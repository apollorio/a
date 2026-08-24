<?php

/**
 * Portal de Eventos — styles: Browse mini event cards
 *
 * Standalone .pev-mini-* component for .pev-browse only.
 * Does NOT reuse .grid-layout / .a-eve-* — those stay full-size on #pevRails.
 *
 * Grid: 4 columns mobile, 6 columns >=1000px.
 *
 * @package Apollo\Event
 * @since   1.5.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-pev-browse-mini">
/* ── Mini event cards · standalone component · .pev-browse only ─────────── */
.pev-browse .eve-body {
  position: relative;
  overflow-x: hidden;
  overflow-y: visible;
  max-width: 100%;
}

.pev-mini-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 8px;
  margin: 0;
  width: 100%;
  box-sizing: border-box;
}

.pev-mini {
  display: block;
  min-width: 0;
}

.pev-mini-card {
  display: block;
  position: relative;
  width: 100%;
  text-decoration: none;
  cursor: pointer;
  background: transparent;
  transition: transform .35s var(--ease);
}
.pev-mini-card:hover { transform: translateY(-3px); }

.pev-mini-date {
  position: absolute;
  top: 3px;
  left: 4px;
  width: 30px;
  height: 26px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  line-height: 1;
  z-index: 2;
  pointer-events: none;
}
.pev-mini-day {
  font-family: var(--ff-heading);
  font-size: calc(13px * var(--fs-u, 1));
  font-weight: 700;
  color: var(--txt-heading);
  display: block;
  line-height: .95;
}
.pev-mini-month {
  font-family: var(--ff-mono);
  font-size: calc(7.5px * var(--fs-u, 1));
  font-weight: 600;
  text-transform: uppercase;
  color: var(--muted);
  line-height: 1;
}

.pev-mini-media {
  position: relative;
  aspect-ratio: 1 / 1;
  overflow: hidden;
  border-radius: var(--r);
  border: 1px solid rgba(var(--rgb-diff), .04);
  box-shadow:
    inset 0 1px 0 0 rgba(var(--rgb-theme), .2),
    inset 0 0 0 1px rgba(var(--rgb-theme), .075);
  background: var(--surface-hover);
  transition: box-shadow .35s var(--ease);
  --r: 6px;
  --s: 6px;
  --x: 24px;
  --y: 20px;
  --_m: /calc(2 * var(--r)) calc(2 * var(--r)) radial-gradient(#000 70%, #0000 72%);
  --_g: conic-gradient(at var(--r) var(--r), #000 75%, #0000 0);
  --_d: (var(--s) + var(--r));
  mask:
    calc(var(--_d) + var(--x)) 0 var(--_m),
    0 calc(var(--_d) + var(--y)) var(--_m),
    radial-gradient(var(--s) at 0 0, #0000 99%, #000 calc(100% + 1px)) calc(var(--r) + var(--x)) calc(var(--r) + var(--y)),
    var(--_g) calc(var(--_d) + var(--x)) 0,
    var(--_g) 0 calc(var(--_d) + var(--y));
  mask-repeat: no-repeat;
  -webkit-mask:
    calc(var(--_d) + var(--x)) 0 var(--_m),
    0 calc(var(--_d) + var(--y)) var(--_m),
    radial-gradient(var(--s) at 0 0, #0000 99%, #000 calc(100% + 1px)) calc(var(--r) + var(--x)) calc(var(--r) + var(--y)),
    var(--_g) calc(var(--_d) + var(--x)) 0,
    var(--_g) 0 calc(var(--_d) + var(--y));
  -webkit-mask-repeat: no-repeat;
}
.pev-mini-card:hover .pev-mini-media {
  box-shadow:
    inset 0 1px 0 0 rgba(var(--rgb-theme), .35),
    inset 0 0 0 1px rgba(var(--rgb-theme), .12);
}
.pev-mini-media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  border: none;
  box-shadow: none;
  transition: transform .35s var(--ease);
}
.pev-mini-card:hover .pev-mini-media img { transform: scale(1.05); }

.pev-mini-tags {
  display: none;
  position: absolute;
  bottom: 5px;
  right: 5px;
  gap: 4px;
  z-index: 3;
  pointer-events: none;
}
.pev-mini-tag {
  padding: 2px 5px;
  border-radius: var(--r-xs);
  border: 1px solid rgba(var(--rgb-theme), .2);
  background: linear-gradient(30deg, rgba(var(--rgb-theme), .1) -49%, rgba(var(--rgb-theme), .35) 160%);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  font-family: var(--ff-mono);
  font-size: calc(6px * var(--fs-u, 1));
  color: rgba(var(--rgb-theme), 1);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .4px;
}

.pev-mini-body {
  padding: .55rem .2rem .4rem;
  width: 100%;
  box-sizing: border-box;
}
.pev-mini-title {
  font-family: var(--ff-heading);
  font-size: calc(10.5px * var(--fs-u, 1));
  font-weight: 700;
  color: var(--txt-heading);
  line-height: 1.22;
  margin: 0 0 .3rem;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  line-clamp: 2;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.pev-mini-meta {
  color: var(--txt-color);
  font-size: calc(9px * var(--fs-u, 1));
  line-height: 1.25;
  display: flex;
  align-items: center;
  gap: 4px;
  margin: 0 0 .2rem;
  min-width: 0;
}
.pev-mini-meta i {
  font-size: calc(10px * var(--fs-u, 1));
  flex-shrink: 0;
  opacity: .8;
  color: var(--muted);
}
.pev-mini-meta span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  min-width: 0;
}

/* Mobile: title + location only — lineup/genres/tags from tablet up */
.pev-mini-meta--wide { display: none; }

@media (min-width: 640px) {
  .pev-mini-grid { gap: 10px; }
  .pev-mini-day { font-size: calc(15px * var(--fs-u, 1)); }
  .pev-mini-month { font-size: calc(8.5px * var(--fs-u, 1)); }
  .pev-mini-title { font-size: calc(12px * var(--fs-u, 1)); }
  .pev-mini-meta { font-size: calc(10px * var(--fs-u, 1)); }
  .pev-mini-meta i { font-size: calc(11px * var(--fs-u, 1)); }
  .pev-mini-meta--wide { display: flex; }
  .pev-mini-tags { display: flex; }
}

@media (min-width: 1000px) {
  .pev-mini-grid {
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 12px;
  }
  .pev-mini-date {
    width: 36px;
    height: 32px;
    top: 4px;
    left: 5px;
  }
  .pev-mini-media {
    --r: 7px;
    --s: 7px;
    --x: 28px;
    --y: 24px;
  }
}
</style>
