<?php

/**
 * Single classified — screen styles.
 *
 * OWNS: .mk-single* geometry and contact/gate stack on /anuncio/{slug}/.
 * MUST NOT declare :root tokens or .ax-* shell chrome.
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<style id="apollo-mk-single">
.mk-single{
  --mk-ease:cubic-bezier(.16,1,.3,1);
  max-width:920px;margin:0 auto;padding:8px 0 48px;
}
.mk-single__nav{
  display:flex;align-items:center;gap:10px;margin-bottom:18px;
}
.mk-single__nav a{
  font-family:var(--ff-mono);font-size:10px;letter-spacing:.12em;text-transform:uppercase;
  color:var(--muted);text-decoration:none;display:inline-flex;align-items:center;gap:6px;
}
.mk-single__nav a:hover{color:var(--txt-heading);}
.mk-single__grid{
  display:grid;gap:22px;
  grid-template-columns:minmax(0,1fr);
}
@media(min-width:820px){
  .mk-single__grid{grid-template-columns:minmax(280px,360px) minmax(0,1fr);align-items:start;}
}
.mk-single__card{
  position:sticky;top:72px;
}
.mk-single__card article[type=ticket],
.mk-single__card .accom-card,
.mk-single__card .rt-card{
  cursor:default;
  max-width:100%;
  width:100%;
}
.mk-single__card article[type=ticket]{height:auto;max-height:none;min-height:420px;}
.mk-single__card article[type=ticket] .ticket-img{height:160px;min-height:160px;max-height:160px;}
.mk-single__card article[type=ticket] .btn-chat-ticket,
.mk-single__card .btn-accom{display:none;}
.mk-single__panel{
  display:flex;flex-direction:column;gap:16px;
}
.mk-single__eyebrow{
  font-family:var(--ff-mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin:0;
}
.mk-single__title{
  font-size:clamp(1.35rem,2.4vw,1.85rem);font-weight:800;letter-spacing:-.03em;
  color:var(--txt-heading);margin:0;line-height:1.15;
}
.mk-single__share{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.mk-single-share{background:var(--surface);color:var(--txt-heading);box-shadow:inset 0 0 0 1px var(--border);}
.mk-single__share-hint{font-family:var(--ff-mono);font-size:10px;color:var(--muted);letter-spacing:.04em;}
.mk-single__meta{
  display:flex;flex-wrap:wrap;gap:8px 14px;
  font-family:var(--ff-mono);font-size:11px;color:var(--muted);
}
.mk-single__price{
  font-family:var(--ff-mono);font-size:1.25rem;font-weight:700;color:var(--txt-heading);margin:0;
}
.mk-single__price small{font-size:11px;font-weight:400;color:var(--muted);margin-left:6px;}
.mk-single__body{
  font-size:15px;line-height:1.55;color:var(--txt-color);
}
.mk-single__body p:first-child{margin-top:0;}
.mk-single__box{
  border-radius:var(--r-lg,14px);
  background:rgba(var(--rgb-theme,255,255,255),.22);
  backdrop-filter:blur(20px) saturate(170%);
  -webkit-backdrop-filter:blur(20px) saturate(170%);
  border:1px solid rgba(var(--rgb-diff,0,0,0),.06);
  box-shadow:
    inset 0 1px 0 0 rgba(var(--rgb-theme,255,255,255),.8),
    inset 0 0 0 1px rgba(var(--rgb-theme,255,255,255),.3);
  padding:16px 18px;
}
.mk-single__box h3{
  font-family:var(--ff-mono);font-size:10px;letter-spacing:.12em;text-transform:uppercase;
  color:var(--muted);margin:0 0 10px;font-weight:600;
}
.mk-single__box p{margin:0 0 6px;font-size:14px;}
.mk-single__box p:last-child{margin-bottom:0;}
.mk-single__contact{scroll-margin-top:80px;}
.mk-single__gate-note{margin:0 0 14px;font-size:14px;color:var(--muted);line-height:1.45;}
.mk-single__contact .apollo-adverts-chat-btn{
  display:inline-flex;align-items:center;gap:8px;
  border-radius:var(--r-pill);padding:12px 18px;text-decoration:none;
  font-family:var(--ff-mono);font-size:11px;letter-spacing:.08em;text-transform:uppercase;
  background:var(--txt-heading);color:var(--bg,#fff);border:0;cursor:pointer;
}
</style>
<?php
/* Reuse marketplace ticket/accom glass so the single card matches /anuncios. */
$mk_styles = dirname(__DIR__, 2) . '/marketplace/parts/mk/styles.php';
if (is_readable($mk_styles)) {
    require $mk_styles;
}
