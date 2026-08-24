<?php

/**
 * Portal de Eventos — styles: Modals
 *
 * Event quick-view modal and the rail lightbox.
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
<style id="apollo-pev-modals">
/* ── Detail modal ── */
.pev-modal {
  position: fixed;
  inset: 0;
  z-index: 9960;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  background: rgba(var(--rgb-diff), .45);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transition: opacity .3s var(--ease), visibility .3s;
  padding: 0;
}
.pev-modal.is-open {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
}
.pev-modal-card {
  width: 100%;
  max-width: 560px;
  max-height: min(88dvh, 720px);
  overflow-y: auto;
  background: var(--bg);
  border-radius: var(--r-lg) var(--r-lg) 0 0;
  box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .9), inset 0 0 0 1px rgba(var(--rgb-theme), .4);
  border: 1px solid rgba(var(--rgb-diff), .04);
  transform: translateY(24px);
  transition: transform .35s var(--ease);
  padding-bottom: calc(20px + var(--safe-bottom, 0px));
}
.pev-modal.is-open .pev-modal-card { transform: translateY(0); }
.pev-modal-cover {
  width: 100%;
  aspect-ratio: 16 / 9;
  object-fit: cover;
  display: block;
  box-shadow: none !important;
  border: 0 !important;
}
.pev-modal-body { padding: 18px 18px 8px; }
.pev-modal-body h2 {
  font-family: var(--ff-heading);
  font-size: calc(var(--fs-r, 1) * 1.35rem);
  font-weight: 800;
  color: var(--txt-heading);
  margin: 0 0 8px;
  letter-spacing: -.02em;
}
.pev-modal-body p {
  font-size: calc(var(--fs-r, 1) * 13px);
  color: var(--txt-color);
  line-height: 1.55;
  margin: 0 0 14px;
}
.pev-modal-meta {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
  font-size: calc(var(--fs-r, 1) * 12px);
  color: var(--txt-heading);
}
.pev-modal-meta span { display: flex; align-items: center; gap: 8px; }
.pev-modal-meta i { color: var(--muted); font-size: 16px; }
.pev-lineup {
  list-style: none;
  margin: 0 0 16px;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.pev-lineup li {
  display: flex;
  justify-content: space-between;
  gap: 10px;
  padding: 8px 10px;
  border-radius: var(--r-xs);
  background: var(--surface);
  font-size: calc(var(--fs-r, 1) * 12px);
  color: var(--txt-heading);
}
.pev-lineup li small { font-family: var(--ff-mono); color: var(--muted); }
.pev-modal-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 0 18px 18px;
}
.pev-modal-close {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 36px;
  height: 36px;
  border-radius: var(--r-pill);
  background: rgba(var(--rgb-diff), .55);
  color: var(--white-1);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 2;
  font-size: 18px;
  border: 0;
}
.pev-modal-card { position: relative; }

@media (min-width: 720px) {
  .pev-modal { align-items: center; padding: 24px; }
  .pev-modal-card {
    border-radius: var(--r-lg);
    max-height: min(90dvh, 780px);
  }
}
</style>
