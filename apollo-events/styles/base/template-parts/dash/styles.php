<?php

/**
 * Meus Eventos — screen styles (PHASE 007).
 *
 * Tokens only, per apollo-rio's core-tokens.md — no local :root. Prefix
 * "aed-" (Apollo Event Dashboard) to avoid colliding with the create form's
 * own classes in the same plugin.
 *
 * @package Apollo\Event
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<style id="apollo-aed-screen">
.aed-screen{max-width:1180px;margin:0 auto;padding:0 var(--s-4,24px) 60px;}
.aed-hero{text-align:center;padding:40px 16px 28px;max-width:720px;margin:0 auto;}
.aed-kicker{font-family:var(--ff-mono);font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:var(--muted);margin:0 0 6px;}
.aed-hero h1{font-size:var(--fs-h3);font-weight:700;letter-spacing:-.03em;color:var(--txt-heading);margin:0 0 18px;}
.aed-hero-kpis{display:flex;justify-content:center;gap:28px;flex-wrap:wrap;margin-bottom:22px;}
.aed-hero-kpi{display:flex;flex-direction:column;align-items:center;}
.aed-hero-kpi strong{font-size:1.7rem;font-weight:700;color:var(--txt-heading);line-height:1;}
.aed-hero-kpi span{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-top:4px;}
.aed-hero-actions{display:flex;justify-content:center;gap:10px;}
.aed-grid{display:grid;grid-template-columns:1fr;gap:16px;margin-bottom:16px;}
.aed-grid.aed-cols-2{grid-template-columns:repeat(2,1fr);}
.aed-grid.aed-cols-3{grid-template-columns:repeat(3,1fr);}
@media (max-width:860px){.aed-grid.aed-cols-2,.aed-grid.aed-cols-3{grid-template-columns:1fr;}}
/* Card law from the Design System showcase (.sh01 / .ins / .vidro):
   1px rgba(--rgb-diff,.04) border + the inset double highlight. A flat
   var(--border) reads noticeably heavier than every other Apollo surface. */
.aed-card{background:var(--card);border:1px solid rgba(var(--rgb-diff),.04);
  box-shadow:inset 0 1px 0 0 rgba(var(--rgb-theme),.2),inset 0 0 0 1px rgba(var(--rgb-theme),.075);
  border-radius:var(--r);padding:20px;}
.aed-card-hd{display:flex;align-items:center;gap:8px;font-size:13.5px;font-weight:700;color:var(--txt-heading);margin-bottom:16px;}
.aed-card-hd i{color:var(--accent);font-size:15px;}
.aed-card-sub{font-weight:400;font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-left:auto;}
.aed-empty{font-size:12.5px;color:var(--muted);margin:0;}
.aed-donut{width:140px;height:140px;border-radius:50%;margin:0 auto 16px;display:grid;place-items:center;}
.aed-donut-hole{width:88px;height:88px;border-radius:50%;background:var(--card);display:grid;place-items:center;}
.aed-donut-hole strong{font-size:1.3rem;color:var(--txt-heading);}
.aed-legend{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px;}
.aed-legend li{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--txt-color);}
.aed-legend b{margin-left:auto;color:var(--txt-heading);}
.aed-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.aed-barlist{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px;}
.aed-barlist li{display:flex;align-items:center;gap:10px;font-size:12.5px;}
.aed-bl-label{width:110px;flex-shrink:0;color:var(--txt-color);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.aed-bl-track{flex:1;height:8px;border-radius:var(--r-pill);background:var(--surface);overflow:hidden;}
.aed-bl-fill{display:block;height:100%;border-radius:var(--r-pill);background:var(--accent);transition:width .5s var(--ease);}
.aed-barlist b{width:26px;text-align:right;color:var(--txt-heading);flex-shrink:0;}
.aed-timeline{display:flex;align-items:flex-end;gap:14px;height:160px;padding-top:10px;}
.aed-tl-col{flex:1;display:flex;flex-direction:column;align-items:center;height:100%;justify-content:flex-end;gap:8px;}
.aed-tl-bar{width:100%;max-width:36px;background:var(--accent);border-radius:var(--r-sm) var(--r-sm) 0 0;display:flex;align-items:flex-start;justify-content:center;padding-top:4px;min-height:4px;}
.aed-tl-bar b{font-size:11px;color:#fff;}
.aed-tl-col span{font-size:10.5px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;}
.aed-readiness{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:12px;}
.aed-readiness li{display:flex;align-items:center;gap:10px;font-size:12.5px;}
.aed-readiness i{color:var(--muted);font-size:15px;width:18px;text-align:center;flex-shrink:0;}
.aed-table{width:100%;border-collapse:collapse;font-size:12.5px;}
.aed-table th{text-align:left;font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);padding:0 10px 10px;font-weight:600;}
.aed-table td{padding:10px;border-top:1px solid var(--border);color:var(--txt-color);}
.aed-table tr td:first-child{color:var(--txt-heading);font-weight:600;}
.aed-tag{display:inline-block;padding:3px 9px;border-radius:var(--r-pill);font-size:10.5px;font-weight:600;color:#fff;}
.aed-disclaimer{font-size:11px;color:var(--muted);text-align:center;max-width:640px;margin:20px auto 0;line-height:1.5;}
.ri-list-check-2::before{content:"";display:inline-block;width:1em;height:1em;background-color:currentColor;-webkit-mask-image:url('https://assets.apollo.rio.br/i/list-check-2.svg');mask-image:url('https://assets.apollo.rio.br/i/list-check-2.svg');-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;}
.ri-gallery-view::before{content:"";display:inline-block;width:1em;height:1em;background-color:currentColor;-webkit-mask-image:url('https://assets.apollo.rio.br/i/function-v.svg');mask-image:url('https://assets.apollo.rio.br/i/function-v.svg');-webkit-mask-size:contain;mask-size:contain;-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;}
</style>
