<?php

/**
 * Marketplace — screen styles (PHASE 003 · luxury ticket pass 2026-08-24)
 *
 * OWNS: /anuncios visual contract for the live card markup
 *   · article[type=ticket]  — glass resale ticket, FIXED size (never follows thumb)
 *   · .accom-card           — accommodation grid below
 *   · data-mk-permalink     — card click opens /anuncio/{slug}/ (own single)
 *
 * MUST NOT declare: :root tokens (core.js), .ax-* shell chrome.
 *
 * The previous .mk-ticket / .mk-ac rules were dead — card-ticket.php and
 * card-accommodation.php never emit those classes. Replaced here with the
 * selectors the templates actually print.
 *
 * @package Apollo\Adverts
 * @since   1.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-mk-screen">
/* ── Screen chrome ───────────────────────────────────────────────────────── */
.mk-screen{--mk-ticket-h:440px;--mk-ticket-img:148px;--mk-ease:cubic-bezier(.16,1,.3,1);--mk-ease-snappy:cubic-bezier(.2,.8,.2,1);}
.mk-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:6px 2px 18px;}
.mk-head h1{font-size:calc(var(--fs-u, 1)*26px);font-weight:650;letter-spacing:-.02em;color:var(--txt-heading);margin:0;}
.mk-head .sub{font-family:var(--ff-mono);font-size:10px;text-transform:uppercase;letter-spacing:.14em;color:var(--muted);margin-top:4px;display:block;}
.mk-safety{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--border);background:var(--card);border-radius:var(--r-pill);padding:9px 16px;font-family:var(--ff-mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--txt-color);cursor:pointer;transition:border-color .3s var(--mk-ease),background .3s var(--mk-ease);}
.mk-safety:hover{border-color:var(--border-hover);background:var(--surface-hover);}
.mk-safety i{color:var(--info-blue);font-size:14px;}
.mk-pills{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:22px;}
.mk-pill{font-family:var(--ff-mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--txt-color);background:var(--surface);border:1px solid var(--border);border-radius:var(--r-pill);padding:8px 16px;cursor:pointer;transition:all .3s var(--mk-ease);user-select:none;}
.mk-pill.is-active{background:var(--txt-heading);color:var(--bg);border-color:transparent;}
.mk-sec-lbl{font-family:var(--ff-mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:8px;margin:26px 0 14px;}
.mk-sec-lbl i{color:var(--accent);font-size:14px;}
.mk-sec-count{opacity:.55;letter-spacing:.08em;}
.mk-empty{border:1px dashed var(--border);border-radius:var(--r);padding:48px 20px;text-align:center;color:var(--muted);}
.mk-empty i{font-size:30px;display:block;margin-bottom:10px;opacity:.5;}

/* ── Grids ───────────────────────────────────────────────────────────────── */
.mk-grid{display:grid;gap:18px;align-items:start;}
.mk-grid--tickets{grid-template-columns:repeat(auto-fill,minmax(280px,1fr));}
.mk-grid--accom{grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;}
.mk-grid--other{grid-template-columns:repeat(auto-fill,minmax(280px,1fr));}
@media(max-width:479px){
  .mk-grid--tickets,.mk-grid--other{grid-template-columns:1fr;}
  .mk-screen{--mk-ticket-h:420px;--mk-ticket-img:132px;}
}

/* ═══════════════════════════════════════════════════════════════════════════
   RESALE TICKET — fixed glass stub (never sized by the thumb)
   DS glass: rgba(theme,.25) + blur(20px) saturate(180%) + etched bevel
   ═══════════════════════════════════════════════════════════════════════════ */
article[type=ticket]{
  --tk-glass: rgba(var(--rgb-theme, 255,255,255), .28);
  position:relative;
  display:flex;
  flex-direction:column;
  width:100%;
  height:var(--mk-ticket-h);
  max-height:var(--mk-ticket-h);
  margin:0;
  padding:0;
  overflow:hidden;
  cursor:pointer;
  border-radius:var(--r-lg, var(--r, 14px));
  background:var(--tk-glass);
  backdrop-filter:blur(20px) saturate(180%);
  -webkit-backdrop-filter:blur(20px) saturate(180%);
  border:1px solid rgba(var(--rgb-diff, 0,0,0), .06);
  box-shadow:
    inset 0 1px 0 0 rgba(var(--rgb-theme, 255,255,255), .85),
    inset 0 0 0 1px rgba(var(--rgb-theme, 255,255,255), .35),
    0 10px 28px -14px rgba(var(--rgb-diff, 0,0,0), .35);
  font-family:var(--ff-heading, var(--ff-main, system-ui));
  transform:translateZ(0);
  transition:
    transform .55s var(--mk-ease),
    box-shadow .55s var(--mk-ease),
    border-color .4s var(--mk-ease);
  will-change:transform;
}
article[type=ticket]:hover{
  transform:translate3d(0,-6px,0) scale(1.012);
  border-color:rgba(var(--rgb-diff, 0,0,0), .12);
  box-shadow:
    inset 0 1px 0 0 rgba(var(--rgb-theme, 255,255,255), .95),
    inset 0 0 0 1px rgba(var(--rgb-theme, 255,255,255), .45),
    0 22px 48px -18px rgba(var(--rgb-diff, 0,0,0), .42);
}
article[type=ticket].is-opening,
article[type=ticket].is-staged{
  opacity:0;
  pointer-events:none;
  transform:scale(.96);
}
.ticket-header-block{
  flex:0 0 auto;
  background:rgba(var(--rgb-diff, 10,10,12), .88);
  color:#fff;
  text-align:center;
  padding:9px 12px;
  font-family:var(--ff-mono);
  font-size:9px;
  letter-spacing:.16em;
  text-transform:uppercase;
  font-weight:700;
}
article[type=ticket] .top{
  flex:1 1 auto;
  min-height:0;
  display:flex;
  flex-direction:column;
  gap:12px;
  padding:14px 16px 10px;
  background:transparent;
}
.ticket-user-info{display:flex;align-items:center;gap:12px;flex:0 0 auto;}
.ticket-avatar{
  width:40px;height:40px;border-radius:50%;object-fit:cover;flex:0 0 auto;
  border:1px solid rgba(var(--rgb-diff,0,0,0),.08);
  box-shadow:0 2px 8px rgba(var(--rgb-diff,0,0,0),.12);
}
.ticket-avatar--locked{
  display:inline-flex;align-items:center;justify-content:center;
  background:rgba(var(--rgb-diff,0,0,0),.06);color:var(--muted);
}
.ticket-user-meta{display:flex;flex-direction:column;min-width:0;}
.ticket-bandname{font-weight:700;font-size:14px;line-height:1.15;color:var(--txt-heading);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.ticket-username,.ticket-locked-note{font-family:var(--ff-mono);font-size:10px;color:var(--muted);margin-top:2px;letter-spacing:.04em;}
/* FIXED image plane — never drives card height */
.ticket-img{
  display:block;
  width:calc(100% + 32px) !important;
  max-width:none !important;
  height:var(--mk-ticket-img) !important;
  min-height:var(--mk-ticket-img) !important;
  max-height:var(--mk-ticket-img) !important;
  margin:0 -16px !important;
  padding:0 !important;
  object-fit:cover;
  object-position:center;
  flex:0 0 var(--mk-ticket-img);
  filter:grayscale(.35) contrast(1.05);
  transition:filter .55s var(--mk-ease), transform .7s var(--mk-ease);
  pointer-events:none;
}
article[type=ticket]:hover .ticket-img{filter:grayscale(0) contrast(1.08);transform:scale(1.03);}
.ticket-deetz{
  display:flex;justify-content:space-between;align-items:flex-end;gap:12px;
  margin-top:auto;min-height:0;
}
.ticket-meta-row{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1 1 auto;}
.ticket-event-title{
  font-family:var(--ff-heading, var(--ff-main));
  font-weight:800;font-size:18px;line-height:1.05;letter-spacing:-.03em;
  text-transform:uppercase;color:var(--txt-heading);
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}
.ticket-date,.ticket-location,.ticket-qty{
  font-family:var(--ff-mono);font-size:10px;color:var(--muted);
  text-transform:uppercase;letter-spacing:.06em;font-weight:500;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.ticket-price-tag{
  flex:0 0 auto;
  font-family:var(--ff-mono);font-size:22px;font-weight:700;
  color:var(--txt-heading);letter-spacing:-.04em;line-height:1;
}
article[type=ticket] .rip{
  flex:0 0 22px;height:22px;margin:0;position:relative;
  background:linear-gradient(90deg, transparent, rgba(var(--rgb-diff,0,0,0),.04), transparent);
}
.rip-line{
  position:absolute;top:50%;left:18px;right:18px;height:0;
  border-top:2px dashed rgba(var(--rgb-diff,0,0,0),.18);
  transform:translateY(-50%);
}
article[type=ticket] .rip::before,
article[type=ticket] .rip::after{
  content:"";position:absolute;width:22px;height:22px;top:50%;
  background:var(--bg, #f4f4f5);border-radius:50%;z-index:2;
  box-shadow:inset 0 0 0 1px rgba(var(--rgb-diff,0,0,0),.06);
}
article[type=ticket] .rip::before{left:-11px;transform:translateY(-50%);}
article[type=ticket] .rip::after{right:-11px;transform:translateY(-50%);}
article[type=ticket] .bottom{
  flex:0 0 auto;
  display:grid;grid-template-columns:1fr 44px;gap:12px;align-items:center;
  padding:10px 16px 16px;background:transparent;margin:0;
}
.barcode{
  background-image:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAF4AAAABCAYAAABXChlMAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAYdEVYdFNvZnR3YXJlAHBhaW50Lm5ldCA0LjAuOWwzfk4AAACPSURBVChTXVAJDsMgDOsrVpELiqb+/4c0DgStQ7JMYogNh2gdvg5VfXFCRIZaC6BOtnoNFpvaumNmwb/71Frrm8XvgYkker1/g9WzMOsohaOGNziRs5inDsAn8yEPengTapJ5bmdZ2Yv7VvfPN6AH2NJx7nOWPTf1/78hoqgxhzw3ZqYG1Dr/9ur3y8vMxgNZhcAUnR4xKgAAAABJRU5ErkJggg==);
  background-repeat:repeat;width:100%;height:36px;opacity:.22;border-radius:2px;
}
.btn-chat-ticket{
  display:inline-flex;align-items:center;justify-content:center;
  width:44px;height:44px;border:0;border-radius:12px;cursor:pointer;
  background:var(--txt-heading);color:var(--bg,#fff);font-size:18px;
  transition:transform .35s var(--mk-ease), filter .35s var(--mk-ease);
}
.btn-chat-ticket:hover{filter:brightness(1.15);transform:translateY(-2px);}
.btn-chat-ticket.is-locked{background:rgba(var(--rgb-diff,0,0,0),.12);color:var(--muted);}

/* ═══════════════════════════════════════════════════════════════════════════
   TICKET STAGE — open / flip / enlarge as popup
   ═══════════════════════════════════════════════════════════════════════════ */
#mkTicketStage{
  position:fixed;inset:0;z-index:10080;
  display:grid;place-items:center;
  padding:max(16px, env(safe-area-inset-top)) 16px max(16px, env(safe-area-inset-bottom));
  opacity:0;visibility:hidden;pointer-events:none;
  transition:opacity .45s var(--mk-ease), visibility 0s linear .45s;
}
#mkTicketStage.is-open{
  opacity:1;visibility:visible;pointer-events:auto;
  transition:opacity .45s var(--mk-ease), visibility 0s linear 0s;
}
#mkTicketStage .mk-tk-backdrop{
  position:absolute;inset:0;
  background:rgba(var(--rgb-diff, 8,8,10), .42);
  backdrop-filter:blur(20px) saturate(160%);
  -webkit-backdrop-filter:blur(20px) saturate(160%);
}
#mkTicketStage .mk-tk-fly{
  position:fixed;left:0;top:0;z-index:1;
  width:var(--w);height:var(--h);
  transform:translate3d(var(--x), var(--y), 0) rotateY(var(--ry, 0deg)) scale(var(--s, 1));
  transform-origin:center center;
  transform-style:preserve-3d;
  perspective:1200px;
  transition:
    transform .7s var(--mk-ease),
    width .7s var(--mk-ease),
    height .7s var(--mk-ease),
    border-radius .55s var(--mk-ease),
    box-shadow .55s var(--mk-ease);
  will-change:transform,width,height;
  border-radius:var(--r-lg, 14px);
  overflow:hidden;
  pointer-events:auto;
  box-shadow:
    inset 0 1px 0 0 rgba(var(--rgb-theme,255,255,255),.9),
    inset 0 0 0 1px rgba(var(--rgb-theme,255,255,255),.4),
    0 28px 64px -20px rgba(var(--rgb-diff,0,0,0),.55);
}
#mkTicketStage.is-open .mk-tk-fly{
  /* final values set by JS; keep transition smooth */
}
#mkTicketStage .mk-tk-fly article[type=ticket]{
  width:100%;height:100%;max-height:none;cursor:default;
  transform:none !important;opacity:1 !important;pointer-events:auto;
}
#mkTicketStage .mk-tk-fly article[type=ticket] .ticket-img{
  height:clamp(160px, 28vh, 240px) !important;
  min-height:clamp(160px, 28vh, 240px) !important;
  max-height:clamp(160px, 28vh, 240px) !important;
  flex-basis:auto;filter:grayscale(0);
}
#mkTicketStage .mk-tk-close{
  position:absolute;top:14px;right:14px;z-index:3;
  width:40px;height:40px;border:0;border-radius:999px;cursor:pointer;
  display:inline-flex;align-items:center;justify-content:center;
  background:rgba(var(--rgb-diff,0,0,0),.55);color:#fff;font-size:20px;
  backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);
  opacity:0;transform:scale(.85);
  transition:opacity .35s var(--mk-ease) .25s, transform .35s var(--mk-ease) .25s;
}
#mkTicketStage.is-settled .mk-tk-close{opacity:1;transform:scale(1);}
#mkTicketStage .mk-tk-close:hover{background:rgba(var(--rgb-diff,0,0,0),.75);}
@media (prefers-reduced-motion: reduce){
  article[type=ticket],article[type=ticket]:hover,.ticket-img,#mkTicketStage .mk-tk-fly{
    transition:none !important;transform:none !important;
  }
}

/* ═══════════════════════════════════════════════════════════════════════════
   ACCOMMODATION — luxury grid under tickets
   ═══════════════════════════════════════════════════════════════════════════ */
.accom-card{
  display:flex;flex-direction:column;overflow:hidden;height:100%;
  border-radius:var(--r-lg, var(--r, 14px));
  background:rgba(var(--rgb-theme,255,255,255),.22);
  backdrop-filter:blur(20px) saturate(170%);
  -webkit-backdrop-filter:blur(20px) saturate(170%);
  border:1px solid rgba(var(--rgb-diff,0,0,0),.06);
  box-shadow:
    inset 0 1px 0 0 rgba(var(--rgb-theme,255,255,255),.8),
    inset 0 0 0 1px rgba(var(--rgb-theme,255,255,255),.3),
    0 8px 22px -14px rgba(var(--rgb-diff,0,0,0),.3);
  transition:transform .45s var(--mk-ease), box-shadow .45s var(--mk-ease), border-color .35s var(--mk-ease);
}
.accom-card:hover{
  transform:translate3d(0,-4px,0);
  border-color:rgba(var(--rgb-diff,0,0,0),.12);
  box-shadow:
    inset 0 1px 0 0 rgba(var(--rgb-theme,255,255,255),.92),
    0 18px 40px -16px rgba(var(--rgb-diff,0,0,0),.38);
}
.accom-img-wrap{position:relative;aspect-ratio:4/3;overflow:hidden;flex:0 0 auto;background:var(--surface);}
.accom-img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .7s var(--mk-ease);}
.accom-card:hover .accom-img{transform:scale(1.04);}
.accom-badge{
  position:absolute;top:10px;left:10px;
  background:rgba(var(--rgb-theme,255,255,255),.85);
  backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);
  padding:5px 10px;border-radius:var(--r-pill);
  font-family:var(--ff-mono);font-size:9px;font-weight:700;
  letter-spacing:.1em;text-transform:uppercase;color:var(--txt-heading);
}
.accom-content{padding:14px 16px 16px;flex:1;display:flex;flex-direction:column;gap:8px;}
.accom-header{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;}
.accom-title{font-size:16px;font-weight:700;line-height:1.2;margin:0;color:var(--txt-heading);letter-spacing:-.02em;}
.accom-rating{
  font-family:var(--ff-mono);font-size:11px;font-weight:600;flex:0 0 auto;
  display:inline-flex;align-items:center;gap:4px;
  background:rgba(var(--rgb-diff,0,0,0),.05);padding:4px 8px;border-radius:999px;color:var(--txt-color);
}
.accom-rating i{color:var(--accent-sunset-gold, var(--accent));}
.accom-loc{font-family:var(--ff-mono);font-size:11px;color:var(--muted);display:flex;align-items:center;gap:6px;margin:0;}
.accom-footer{margin-top:auto;display:flex;justify-content:space-between;align-items:center;gap:10px;padding-top:6px;}
.accom-price{font-family:var(--ff-mono);font-size:15px;font-weight:700;color:var(--txt-heading);}
.accom-price span{font-size:10px;font-weight:400;color:var(--muted);margin-left:2px;}
.btn-accom{
  border:1px solid rgba(var(--rgb-diff,0,0,0),.2);
  background:transparent;color:var(--txt-heading);
  font-family:var(--ff-mono);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;
  padding:8px 14px;border-radius:var(--r-pill);cursor:pointer;
  transition:background .3s var(--mk-ease), color .3s var(--mk-ease), border-color .3s var(--mk-ease);
}
.btn-accom:hover{background:var(--txt-heading);color:var(--bg,#fff);border-color:transparent;}
.btn-accom.is-locked{opacity:.75;}
</style>
