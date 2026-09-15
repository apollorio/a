<?php

/**
 * Marketplace — bridge styles (FAB, empty, filters, expand).
 *
 * Visual contract lives in assets/css/market-screen.css (approved mockup).
 *
 * @package Apollo\Adverts
 * @since   1.2.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-mk-bridge">
.mk-screen .is-mk-hidden{display:none!important;}
.mk-head-row{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:8px;}
.mk-head-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center;}
.mk-head-actions .mk-safety > i,.mk-head-actions .mk-safety > span{pointer-events:none;}
.cta-card-body{flex:1;min-width:0;}
.mk-empty{display:flex;flex-direction:column;align-items:center;gap:12px;border:1px dashed var(--border);border-radius:var(--r-lg);padding:48px 20px;text-align:center;color:var(--muted);}
.mk-empty i{font-size:30px;opacity:.5;}
.mk-sec-lbl{font-family:var(--ff-mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:8px;margin:26px 0 14px;}
.mk-sec-count{opacity:.55;}
.mk-grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));align-items:start;}
/* Ticket cards keep the `carousel-item` class in BOTH modes, but
   market-screen.css:503 gives that class `position:absolute` because it is
   written for the fan carousel. The only rule that undoes it is scoped
   `#ticketCarousel.grid-view ...`, and section.php emits `id="ticketCarousel"`
   ONLY in carousel mode (section.php:76) — so in grid mode nothing ever
   cancels the absolute positioning. The card leaves the flow, .mk-grid
   collapses to height 0, and the card floats over the banner and filters.
   .mk-grid owns its own children's layout context, so the reset belongs here
   rather than as a second `#ticketCarousel` rule in market-screen.css. */
.mk-grid article[type=ticket].carousel-item:not(.is-popup){position:relative;top:auto;left:auto;transform:none;}
.mk-fab{position:fixed;right:max(16px,env(safe-area-inset-right));bottom:calc(18px + env(safe-area-inset-bottom,0px));z-index:9800;width:56px;height:56px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:var(--txt-heading);color:var(--bg);font-size:26px;text-decoration:none;box-shadow:0 12px 28px -12px rgba(var(--rgb-diff,0,0,0),.45);}
@media(min-width:1000px){.mk-fab{display:none;}}
article[type=ticket].is-open .rt-details{height:auto;opacity:1;}
#accomGrid.grid-layout .rt-card{max-width:none;width:100%;}
</style>
