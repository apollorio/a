<?php
/**
 * Meus Eventos — Dashboard main styles (form.html card language, ev- prefix)
 *
 * Shell CSS comes from shared/shell-styles.php. This file only styles the
 * .ax-main dashboard content (stats, filters, manage cards, transactions).
 * Uses core.js tokens exclusively — never redeclares :root.
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style>
/* ── Page header ── */
.ev-dash-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* ── Stats ── */
.ev-stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--s-3, 16px); margin-bottom: var(--s-5, 32px); }
@media (min-width: 760px) { .ev-stats { grid-template-columns: repeat(4, 1fr); } }
.ev-stat { padding: 16px 18px; margin-bottom: 0; }
.ev-stat__lbl { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); text-transform: uppercase; letter-spacing: .1em; color: var(--muted); }
.ev-stat__val { font-family: var(--ff-heading, sans-serif); font-size: calc(var(--fs-r, 1) * var(--fs-h3, 28px)); font-weight: 800; letter-spacing: -.02em; color: var(--txt-heading); margin-top: 8px; line-height: 1; }
.ev-stat__sub { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-top: 8px; }

/* ── Filter bar ── */
.ev-filters { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.ev-filters__pills { display: flex; flex-wrap: wrap; gap: 6px; }
.ev-filter { padding: 8px 14px; border-radius: var(--r-pill, 99px); font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 10px); text-transform: uppercase; letter-spacing: .06em; background: var(--surface); color: var(--txt-color); cursor: pointer; border: 1px solid transparent; transition: background .3s var(--ease), color .3s var(--ease); }
.ev-filter:hover { color: var(--txt-heading); background: var(--card); }
.ev-filter.is-active { background: var(--black-1); color: var(--bg); }
.ev-filter span { opacity: .55; margin-left: 3px; }
.ev-filters__count { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 10px); color: var(--muted); white-space: nowrap; }

/* ── Manage grid + cards ── */
.ev-grid { display: grid; grid-template-columns: 1fr; gap: var(--s-4, 24px); }
@media (min-width: 640px) { .ev-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1120px) { .ev-grid { grid-template-columns: repeat(3, 1fr); } }
.ev-mcard { padding: 0; overflow: hidden; margin-bottom: 0; }
.ev-mcard__thumb { position: relative; aspect-ratio: 16 / 10; background: var(--surface); overflow: hidden; }
.ev-mcard__thumb img { width: 100%; height: 100%; object-fit: cover; box-shadow: none; }
.ev-mcard__thumb-ph { display: flex; align-items: center; justify-content: center; height: 100%; color: var(--muted); font-size: 30px; }
.ev-badge { position: absolute; top: 10px; left: 10px; padding: 4px 10px; border-radius: var(--r-pill, 99px); font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); text-transform: uppercase; letter-spacing: .06em; font-weight: 700; backdrop-filter: blur(6px); }
.ev-badge--published { background: rgba(52,199,89,.16); color: #1a8a3f; }
.ev-badge--draft { background: var(--white-6); color: var(--muted); }
.ev-badge--scheduled { background: rgba(var(--rgb-accent), .14); color: var(--orange-700); }
.ev-mcard__body { padding: 16px 18px 18px; }
.ev-mcard__name { font-family: var(--ff-heading, sans-serif); font-size: calc(var(--fs-r, 1) * var(--fs-h6, 16px)); font-weight: 700; color: var(--txt-heading); line-height: 1.2; }
.ev-mcard__meta { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 10px); text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-top: 6px; display: block; }
.ev-ca { display: flex; align-items: center; justify-content: space-between; margin-top: 12px; }
.ev-ca__list { display: flex; align-items: center; }
.ev-ca__av { width: 26px; height: 26px; border-radius: 50%; object-fit: cover; margin-left: -6px; border: 2px solid var(--bg); box-shadow: none; }
.ev-ca__av:first-child { margin-left: 0; }
.ev-ca__add { width: 26px; height: 26px; border-radius: 50%; margin-left: -6px; background: var(--surface); border: 2px solid var(--bg); display: flex; align-items: center; justify-content: center; color: var(--muted); cursor: pointer; }
.ev-ca__count { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); color: var(--muted); }
.ev-mcard__actions { display: flex; gap: 8px; margin-top: 16px; }
.ev-mcard__actions .btn { flex: 1; }

/* ── Empty state ── */
.ev-empty { grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--muted); }
.ev-empty i { font-size: 40px; opacity: .45; }
.ev-empty p { margin: 12px 0 18px; }

/* ── Transactions ── */
.ev-tx { margin-top: 40px; }
.ev-tx__row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px dashed rgba(var(--rgb-diff), .08); }
.ev-tx__row:last-child { border-bottom: none; }
.ev-tx__l { display: flex; align-items: center; gap: 12px; }
.ev-tx__ic { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--surface); color: var(--txt-heading); flex-shrink: 0; }
.ev-tx__name { font-weight: 600; color: var(--txt-heading); font-size: calc(var(--fs-r, 1) * 13px); }
.ev-tx__time { font-family: var(--ff-mono, monospace); font-size: calc(var(--fs-r, 1) * 9px); color: var(--muted); }
.ev-tx__amt { font-weight: 700; font-size: calc(var(--fs-r, 1) * 13px); color: var(--txt-heading); }
.ev-tx__amt--income { color: #1a8a3f; }
</style>
