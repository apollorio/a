<?php

/**
 * Comunas — screen styles (PHASE 004)
 *
 * Ported VERBATIM from screen/_official_layout/css/comunas.css.
 *
 * STRICT TOKEN RULE: core.js is the only source of :root tokens, so before
 * writing this file every custom property the mockup uses was probed against a
 * live Apollo page. All resolve EXCEPT --fs-body-sm and --fs-caption, which
 * this ecosystem does not ship; those references are rewritten to core.js's
 * real font-scale token (--fs-u) at port time. Nothing here declares a token
 * and there is no :root block — same rule that phases 001-003 now pass.
 *
 * @package Apollo\Groups
 * @since   3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-cmn-screen">
/* APOLLO::RIO · comunas.css
   Comunas portal chrome — blend of lean hero + UNI structure.
   Cards stay on DS (.gallery-card). Tokens only — no :root.
   Prefix: cmn-
*/
#view-comunas.cmn-host,
#view-comunas .cmn {
  color: var(--txt-color);
  font-family: var(--ff-main);
  padding-bottom: 72px;
  box-sizing: border-box;
}

/* ── Hero: half UNI energy, half current restraint ── */
.cmn-hero {
  position: relative;
  overflow: hidden;
  padding: 8px 0 4px;
  margin-bottom: 8px;
}
.cmn-hero-bg {
  position: absolute;
  top: -12px;
  left: -8px;
  font-family: var(--ff-heading);
  font-size: min(22vw, 180px);
  font-weight: 800;
  line-height: .85;
  color: transparent;
  -webkit-text-stroke: 1px color-mix(in srgb, var(--txt-heading) 6%, transparent);
  white-space: nowrap;
  pointer-events: none;
  user-select: none;
  z-index: 0;
}
.cmn-hero-inner { position: relative; z-index: 1; }
.cmn-hero .display-text {
  font-size: clamp(2rem, 6vw, 3.25rem);
  line-height: .92;
  letter-spacing: -.04em;
  margin: 0 0 10px;
}
.cmn-hero .display-text .thin {
  font-weight: 300;
  color: var(--muted);
}
.cmn-hero-desc {
  font-size: var(--fs-p4, calc(var(--fs-u, 1) * 13px));
  color: var(--muted);
  max-width: 42ch;
  line-height: 1.45;
  margin: 0 0 14px;
}

.cmn-pill-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 18px;
}
.cmn-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: var(--r-pill);
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 10px);
  text-transform: uppercase;
  letter-spacing: .08em;
  color: var(--txt-heading);
  background: var(--surface);
  border: 1px solid rgba(var(--rgb-diff), .04);
  box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .9), inset 0 0 0 1px rgba(var(--rgb-theme), .4);
}
.cmn-pill i { font-size: 13px; color: var(--primary); }

.cmn-cta-shine {
  margin-top: 8px;
  cursor: pointer;
  transition: transform .3s var(--ease-snappy), background .3s var(--ease);
}
.cmn-cta-shine:hover {
  transform: translateY(-2px);
  background: var(--card);
}

/* Compact stats strip (not 4 heavy .stat-card blocks) */
.cmn-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 28px 36px;
  padding: 16px 0;
  margin-bottom: 22px;
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
}
.cmn-stat-num {
  display: block;
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 1.5rem);
  font-weight: 700;
  letter-spacing: -.03em;
  line-height: 1;
  color: var(--txt-heading);
}
.cmn-stat-num.is-live { color: var(--primary); }
.cmn-stat-lbl {
  display: block;
  margin-top: 4px;
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 9px);
  text-transform: uppercase;
  letter-spacing: .1em;
  color: var(--muted);
}

/* Tabs row — DS .tag, left-aligned like UNI */
.cmn-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 18px;
  justify-content: flex-start !important;
}
.cmn-tabs .tag { cursor: pointer; }
.cmn-tabs .tag .cmn-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 18px;
  height: 16px;
  padding: 0 5px;
  margin-left: 6px;
  border-radius: var(--r-pill);
  font-size: 9px;
  font-weight: 700;
  background: var(--surface);
  color: var(--muted);
}
.cmn-tabs .tag.tag-primary .cmn-count {
  background: color-mix(in srgb, var(--bg) 25%, transparent);
  color: inherit;
}

.cmn-search {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  margin-bottom: 28px;
}
.cmn-search .field-input { flex: 1; min-width: 180px; }

/* Trending chips */
.cmn-sec-lbl {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 10px);
  text-transform: uppercase;
  letter-spacing: .14em;
  color: var(--muted);
  margin: 0 0 12px;
}
.cmn-sec-lbl i { color: var(--primary); font-size: 14px; }

.cmn-trend {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
  padding-bottom: 4px;
  margin-bottom: 28px;
}
.cmn-trend::-webkit-scrollbar { display: none; }

.cmn-trend-chip {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 190px;
  max-width: 240px;
  padding: 10px 14px;
  border-radius: var(--r);
  background: var(--surface);
  border: 1px solid rgba(var(--rgb-diff), .04);
  box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .9), inset 0 0 0 1px rgba(var(--rgb-theme), .4);
  cursor: pointer;
  transition: background .3s var(--ease), transform .3s var(--ease-snappy);
}
.cmn-trend-chip:hover { background: var(--card); transform: translateY(-2px); }
.cmn-trend-chip img {
  width: 40px;
  height: 40px;
  border-radius: var(--r-sm);
  object-fit: cover;
  flex-shrink: 0;
  filter: grayscale(.85);
  transition: filter .35s var(--ease);
}
.cmn-trend-chip:hover img { filter: grayscale(0); }
.cmn-trend-chip strong {
  display: block;
  font-size: calc(var(--fs-r, 1) * 13px);
  color: var(--txt-heading);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.cmn-trend-chip span {
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 9px);
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .04em;
}

/* Featured — one big cover, short copy (not UNI essay) */
.cmn-feat {
  display: block;
  position: relative;
  border-radius: var(--r-lg);
  overflow: hidden;
  margin-bottom: 32px;
  cursor: pointer;
  background: var(--surface);
  border: 1px solid rgba(var(--rgb-diff), .04);
  box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .9), inset 0 0 0 1px rgba(var(--rgb-theme), .4);
  transition: transform .4s var(--ease-snappy);
}
.cmn-feat:hover { transform: translateY(-3px); }
.cmn-feat-cover {
  height: min(240px, 42vw);
  min-height: 180px;
  position: relative;
  overflow: hidden;
}
.cmn-feat-cover img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: grayscale(.7);
  transition: filter .5s var(--ease), transform .55s var(--ease-smooth);
}
.cmn-feat:hover .cmn-feat-cover img {
  filter: grayscale(0);
  transform: scale(1.03);
}
.cmn-feat-cover::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(transparent 35%, rgba(var(--rgb-diff), .72) 100%);
  pointer-events: none;
}
.cmn-feat-badge {
  position: absolute;
  top: 14px;
  left: 14px;
  z-index: 2;
  font-family: var(--ff-mono);
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: .12em;
  padding: 5px 12px;
  border-radius: var(--r-pill);
  background: var(--primary);
  color: var(--bg);
}
.cmn-feat-body {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 2;
  padding: 20px 22px;
}
.cmn-feat-body .tag {
  margin-bottom: 8px;
  background: rgba(var(--rgb-theme), .16);
  color: var(--white-1);
  border: 0;
}
.cmn-feat-body h3 {
  margin: 0 0 6px;
  font-family: var(--ff-heading);
  font-size: clamp(1.35rem, 3.5vw, 2rem);
  font-weight: 800;
  letter-spacing: -.03em;
  line-height: .95;
  color: var(--white-1);
}
.cmn-feat-body p {
  margin: 0;
  max-width: 46ch;
  font-size: calc(var(--fs-r, 1) * 13px);
  color: rgba(var(--rgb-theme), .78);
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.cmn-feat-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: center;
  margin-top: 12px;
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 10px);
  color: rgba(var(--rgb-theme), .55);
}
.cmn-feat-avs { display: flex; }
.cmn-feat-avs img {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  border: 2px solid rgba(var(--rgb-diff), .55);
  margin-right: -7px;
  object-fit: cover;
  filter: grayscale(.8);
  transition: filter .35s var(--ease);
}
.cmn-feat:hover .cmn-feat-avs img { filter: grayscale(0); }

.cmn-grid-block { margin-bottom: 28px; }

@media (max-width: 640px) {
  .cmn-stats { gap: 18px 24px; }
  .cmn-search { flex-direction: column; align-items: stretch; }
  .cmn-search .btn { width: 100%; justify-content: center; }
  .cmn-feat-cover { height: 200px; }
}

</style>
