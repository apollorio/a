<?php
/**
 * DJ Single — cell: styles
 *
 * OWNS: the entire visual layer — single owner. Every selector is .dj-* or scoped under .dj-page, so nothing on the platform can reach in and nothing here leaks out
 *
 * GENERATED — do not hand-edit. Sliced from the approved mockup
 * (_sandbox/dj-single-page.mockup.html) by _sandbox/build-dj-cells.py, which
 * also namespaces every class to `dj-`. Edit the mockup, re-run the generator,
 * review the diff. Hand-editing here is how the page and the design drift.
 *
 * Dynamic values arrive as $dj_* in scope from apollo_dj_single_context().
 * Anything the runtime fills client-side is left as the empty container it
 * expects to find.
 *
 * @package Apollo\DJs
 * @since   1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-dj-styles">
/* ════════════════════════════════════════════════════════════════════════════
   DJ BUSINESS CARD — ultra-luxury white · Apple-release cadence · Apollo DS
   No aside. Uma única .dj-ev-top fixa (voltar · nome · compartilhar) como PRIMEIRO
   nó do <body> — nunca aninhada na figura do hero. O scroll é a interface.
   Depth = inset hairline only · squircle · accent = var(--accent) #d1860a:
   the progress hairline, .dj-page one italic word, .dj-page the live dot. Nothing else.
   core.js delivers real tokens/fonts/GSAP/Lenis — :root below is offline mirror
   and every value maps 1:1 onto the core.js injected token system.
   LAYOUT RULE: every section owns its own padding (.dj-sec / .dj-wrap). Body and
   html carry ZERO padding/margin — so full-bleed media (hero photo, .dj-page footer
   photo) sit as true DIRECT children of <header>/<footer> at width:100%,
   .dj-page flush to the viewport's top/left/right/bottom with nothing escaping a
   parent's padding via vw-hacks (that trick is scrollbar-unsafe; a direct,
   .dj-page unpadded-ancestor child at width:100% is the bulletproof version).
   ════════════════════════════════════════════════════════════════════════════ */
@font-face{font-family:"Black Ridge";font-weight:400;font-display:swap;src:url("https://assets.apollo.rio.br/fonts/black-ridge/black-ridge.woff2") format("woff2");}
@font-face{font-family:"Resolide Serif";font-weight:400;font-display:swap;src:url("https://assets.apollo.rio.br/fonts.h/resolide-serif/resolide-serif.otf") format("opentype");}
@font-face{font-family:"Mileast";font-weight:400;font-style:italic;font-display:swap;src:url("https://assets.apollo.rio.br/fonts/mileast/mileast-italic.woff2") format("woff2");}

:root{
  /* PAGE-SCOPED ONLY — these do not exist in core.js.
     The mockup mirrors the core token set so it can run offline; the
     generator drops every mirrored token (--rgb-theme, --rgb-diff, --bg, --txt, --muted, --surface, --surface-2, --accent, --ff-display, --ff-serif, --ff-serif-i, --ff-main, --ff-mono, --r, --r-sm, --r-pill, --ease)
     because core.js injects the identical values at runtime and a local
     copy would fork the token system on the first theme change. */
  --ink:rgba(var(--rgb-diff),.96);
  --faint:rgba(var(--rgb-diff),.26);
  --line:rgba(var(--rgb-diff),.08);
  --ins:inset 0 1px 0 0 rgba(var(--rgb-theme),.5), inset 0 0 0 1px rgba(var(--rgb-diff),.05);
  --pad:clamp(24px,6vw,96px);
  --st:env(safe-area-inset-top,0px);
  --sb:env(safe-area-inset-bottom,0px);
  --evtop-h:62px;
  --rl:1;
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;corner-shape:squircle;}
.dj-pill,[data-round]{corner-shape:round;}
html{-webkit-text-size-adjust:100%;scroll-padding-top:calc(var(--evtop-h) + var(--st) + 14px);}
html,body{margin:0;padding:0;}
body{
  background:var(--bg);color:var(--txt);
  font-family:var(--ff-main);font-size:15.5px;line-height:1.6;font-weight:400;
  -webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;text-rendering:optimizeLegibility;
  overflow-x:clip;overscroll-behavior:none;
}
html.dj-lenis,html.dj-lenis body{height:auto;}
/* core.js frames every img/figure by default — this page's media is frameless */
.dj-page img{display:block;max-width:100%;border:none!important;box-shadow:none!important;}
.dj-page figure{border:none!important;box-shadow:none!important;}
.dj-page ul{list-style:none;}
.dj-page a{color:inherit;text-decoration:none;}
.dj-page button{border:0;background:none;font:inherit;color:inherit;cursor:pointer;-webkit-user-select:none;user-select:none;}
.dj-page i[class^="ri-"],.dj-page i[class*=" ri-"]{font-style:normal;line-height:1;vertical-align:-.125em;}
::selection{background:rgba(209,134,10,.14);color:var(--ink);}

/* type atoms */
.dj-lbl{font-family:var(--ff-mono);font-size:10px;letter-spacing:.24em;text-transform:uppercase;color:var(--muted);}
.dj-lbl .dj-dot{display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--accent);margin-right:8px;vertical-align:1px;}
.dj-display{font-family:var(--ff-display);font-weight:400;color:var(--ink);line-height:.86;letter-spacing:-.005em;}
.dj-serif{font-family:var(--ff-serif);font-weight:400;color:var(--ink);line-height:.98;letter-spacing:-.01em;}

/* layout — Apple air · each section owns its padding, body/html never do */
.dj-wrap{width:min(100%,1280px);margin-inline:auto;padding-inline:var(--pad);}
.dj-sec{padding-block:clamp(96px,14vw,200px);}
.dj-sec-tight{padding-block:clamp(72px,10vw,140px);}
.dj-hairline{height:1px;background:var(--line);border:0;}
/* faixa cinza-claríssima — a alternância branco/quase-branco que dá ritmo às
   páginas de produto da Apple. Sem borda: só a mudança de plano. */
.dj-band{background:var(--surface);}

/* buttons — ink is the luxury primary */
.dj-btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;font-family:var(--ff-mono);font-size:11px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;padding:17px 30px;border-radius:var(--r-pill);white-space:nowrap;min-height:44px;transition:transform .45s var(--ease),opacity .45s var(--ease),background .45s var(--ease),color .45s var(--ease);}
.dj-btn:active{transform:scale(.97);}
.dj-btn-ink{background:var(--ink);color:var(--bg)!important;}
.dj-btn-ink:hover{opacity:.85;}
.dj-btn-line{box-shadow:inset 0 0 0 1px rgba(var(--rgb-diff),.16);color:var(--ink);}
.dj-btn-line:hover{background:var(--surface-2);}
.dj-btn-ghost{color:var(--muted);padding:0;}
.dj-btn-ghost:hover{color:var(--ink);}
.dj-icon-btn{width:54px;height:54px;border-radius:50%;display:grid;place-items:center;font-size:20px;background:var(--surface);color:var(--ink);box-shadow:var(--ins);transition:background .4s var(--ease);}
.dj-icon-btn:hover{background:var(--surface-2);}

/* progress hairline — the single running accent */
.dj-pg{position:fixed;top:0;left:0;right:0;height:2px;z-index:120;background:var(--accent);transform-origin:0 50%;transform:scaleX(0);}

/* ═══════════════════════════════════════════════════════════════════════════
   EV-TOP — barra REAL no topo do documento: primeiro nó do <body>, .dj-page position
   fixed, .dj-page top:0. NÃO vive mais dentro de .dj-hero-figure (onde ficava abaixo de
   `.dj-hero div.dj-wrap`, .dj-page no meio da página). ZERO gradiente: no branco ultra-luxo a
   profundidade vem de hairline + glass, .dj-page nunca de véu escuro. Estado is-solid
   entra no scroll e revela o nome do artista — cadência Apple.
   ═══════════════════════════════════════════════════════════════════════════ */
.dj-ev-top{
  position:fixed;top:0;left:0;right:0;z-index:115;
  height:calc(var(--evtop-h) + var(--st));
  padding:var(--st) max(14px,calc(var(--pad) - 40px)) 0;
  display:flex;align-items:center;justify-content:space-between;gap:14px;
  pointer-events:none;
  transition:background .7s var(--ease),backdrop-filter .7s var(--ease);
}
.dj-ev-top > *{pointer-events:auto;}
.dj-ev-top::after{
  content:"";position:absolute;left:0;right:0;bottom:0;height:1px;
  background:var(--line);transform:scaleX(0);transform-origin:50% 50%;
  transition:transform .9s var(--ease);
}
.dj-ev-top.dj-is-solid{
  background:rgba(var(--rgb-theme),.72);
  backdrop-filter:blur(36px) saturate(1.9);-webkit-backdrop-filter:blur(36px) saturate(1.9);
}
.dj-ev-top.dj-is-solid::after{transform:scaleX(1);}
/* identidade central — só aparece quando o nome gigante do hero já saiu de cena */
.dj-ev-top-id{
  flex:1 1 auto;min-width:0;text-align:center;pointer-events:none!important;
  display:flex;flex-direction:column;align-items:center;gap:3px;
  opacity:0;transform:translateY(10px);
  transition:opacity .6s var(--ease),transform .6s var(--ease);
}
.dj-ev-top.dj-is-solid .dj-ev-top-id{opacity:1;transform:none;}
.dj-ev-top-id b{
  font-family:var(--ff-display);font-weight:400;font-size:17px;line-height:1;
  color:var(--ink);letter-spacing:.01em;
  max-width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.dj-ev-top-id span{font-family:var(--ff-mono);font-size:8.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--faint);}
/* chips — ink sobre branco (nunca #fff sobre foto): glass + hairline interna */
.dj-ev-chip{
  width:44px;height:44px;flex:0 0 auto;border-radius:999px;
  display:grid;place-items:center;color:var(--ink);cursor:pointer;
  background:rgba(var(--rgb-theme),.6);box-shadow:var(--ins);
  backdrop-filter:blur(22px) saturate(1.6);-webkit-backdrop-filter:blur(22px) saturate(1.6);
  transition:transform .35s var(--ease),background .45s var(--ease),box-shadow .45s var(--ease);
}
.dj-ev-chip:hover{background:var(--surface-2);}
.dj-ev-chip:active{transform:scale(.94);}
/* com a barra sólida o glass é da barra — os chips ficam limpos */
.dj-ev-top.dj-is-solid .dj-ev-chip{background:transparent;box-shadow:none;backdrop-filter:none;-webkit-backdrop-filter:none;}
.dj-ev-top.dj-is-solid .dj-ev-chip:hover{background:var(--surface-2);}

/* ═══ HERO — text column padded via inner .dj-wrap; figure is a direct, .dj-page unpadded
   child of <header> so it hits width:100% edge-to-edge with zero gap.
   O padding-top agora reserva a altura da ev-top fixa. ═══ */
.dj-hero{padding-top:calc(var(--evtop-h) + var(--st) + clamp(40px,9vw,120px));}
.dj-hero-eyebrow{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:clamp(20px,3vw,36px);}
.dj-hero-live{display:inline-flex;align-items:center;gap:8px;font-family:var(--ff-mono);font-size:10px;letter-spacing:.18em;text-transform:uppercase;color:var(--muted);}
.dj-hero-live::before{content:"";width:7px;height:7px;border-radius:50%;background:var(--accent);animation:pulse 3s infinite;}
@keyframes pulse{0%{box-shadow:0 0 0 0 rgba(209,134,10,.4)}70%{box-shadow:0 0 0 10px rgba(209,134,10,0)}100%{box-shadow:0 0 0 0 rgba(209,134,10,0)}}
.dj-hero-name{font-size:clamp(52px,15vw,200px);overflow:visible;}
.dj-hero-name .dj-hn-line{display:block;overflow:hidden;white-space:nowrap;}
.dj-hero-name .dj-hn-line.dj-is-ac{color:var(--accent);}
.dj-hero-name .dj-ch-w{display:inline-block;overflow:hidden;vertical-align:top;}
.dj-hero-name .dj-ch{display:inline-block;will-change:transform;}
.dj-hero-under{display:flex;flex-direction:column;gap:26px;margin-top:clamp(24px,4vw,44px);}
.dj-hero-bio{font-size:clamp(15px,1.4vw,18px);line-height:1.7;color:var(--txt);max-width:44ch;}
.dj-hero-bio strong{color:var(--ink);font-weight:600;}
.dj-hero-ctas{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
/* full-bleed: direct child of <header class="hero"> (zero horizontal padding on
   that ancestor). Nasce como card recuado e ABRE pra full-bleed no scrub —
   o movimento-assinatura das páginas de produto da Apple. */
.dj-hero-figure{position:relative;margin-top:clamp(40px,6vw,88px);width:100%;overflow:hidden;aspect-ratio:4/5;background:var(--surface);will-change:clip-path;}
.dj-hero-figure img{width:100%;height:100%;object-fit:cover;object-position:center top;filter:saturate(.88) contrast(1.02);will-change:transform;}
.dj-ev-chip i{font-size:20px;line-height:1;}
.dj-hero-pills{position:absolute;left:max(14px,var(--pad));bottom:16px;right:max(14px,var(--pad));display:flex;gap:8px;flex-wrap:wrap;z-index:3;}
.dj-gpill{backdrop-filter:blur(28px) saturate(1.8);-webkit-backdrop-filter:blur(28px) saturate(1.8);background:rgba(255,255,255,.1);border-radius:var(--r-pill);padding:12px 20px;display:inline-flex;align-items:baseline;gap:9px;box-shadow:var(--ins);}
.dj-gpill strong{font-family:var(--ff-display);font-size:20px;color:rgba(10,10,10,.96);line-height:1;}
.dj-gpill span{font-family:var(--ff-mono);font-size:9px;letter-spacing:.14em;text-transform:uppercase;color:rgba(10,10,10,.4);}
.dj-hero-cue{
  position:absolute;top:16px;right:max(16px,var(--pad));z-index:3;
  font-family:var(--ff-mono);font-size:9px;letter-spacing:.22em;text-transform:uppercase;
  color:#fff;opacity:.78;display:flex;align-items:center;gap:6px;
  animation:cueY 2.2s ease-in-out infinite;text-shadow:0 1px 8px rgba(0,0,0,.35);
}
@keyframes cueY{0%,100%{transform:translateY(0)}50%{transform:translateY(6px)}}
@media(min-width:900px){
  .dj-hero-under{flex-direction:row;justify-content:space-between;align-items:flex-end;}
  .dj-hero-figure{aspect-ratio:21/10;}
  .dj-hero-figure img{object-position:center top;}
}

/* ═══ STATEMENT — Apple word-fill on scrub ═══ */
.dj-stmt{padding-block:clamp(120px,18vw,260px);}
.dj-stmt-txt{font-family:var(--ff-serif);font-size:clamp(30px,5.6vw,68px);line-height:1.22;letter-spacing:-.01em;color:var(--ink);max-width:22ch;}
.dj-stmt-txt .dj-wd{display:inline-block;margin-right:.24em;}
.dj-stmt-txt em.dj-gold{font-family:var(--ff-serif-i);font-style:italic;color:var(--accent);}

/* ═══ MARQUEE ═══ */
.dj-mq{overflow:hidden;padding-block:clamp(20px,3vw,30px);border-top:1px solid var(--line);border-bottom:1px solid var(--line);}
.dj-mq-inner{display:flex;gap:44px;width:max-content;animation:mq 30s linear infinite;}
.dj-mq span{font-family:var(--ff-mono);font-size:10.5px;letter-spacing:.26em;text-transform:uppercase;color:var(--faint);white-space:nowrap;}
.dj-mq span.dj-on{color:var(--ink);}
@keyframes mq{to{transform:translateX(-50%);}}

/* ═══ SECTION HEADS ═══ */
.dj-sh{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:clamp(40px,6vw,84px);}
.dj-sh .dj-lbl{display:block;margin-bottom:16px;}
.dj-sh-t{font-size:clamp(52px,9vw,124px);overflow:visible;}
/* char reveal dos títulos de seção e do nome do rodapé — mesma mecânica do hero.
   O padding/margin negativo compensa descendentes dentro do overflow:hidden. */
.dj-sh-t .dj-ch-w,.dj-foot-name .dj-ch-w{display:inline-block;overflow:hidden;vertical-align:top;padding-bottom:.1em;margin-bottom:-.1em;}
.dj-sh-t .dj-ch,.dj-foot-name .dj-ch{display:inline-block;will-change:transform;}
.dj-sh-side{font-family:var(--ff-mono);font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--faint);padding-bottom:10px;}

/* ═══ TOCOU EM — pinned scrub carousel (desktop) · momentum rail (touch) ═══ */
.dj-po{position:relative;}
.dj-po-stage{position:relative;}
.dj-po-track{display:flex;gap:14px;padding-inline:var(--pad);overflow-x:auto;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;scrollbar-width:none;scroll-padding-inline:var(--pad);}
.dj-po-track::-webkit-scrollbar{display:none;}
.dj-po-card{flex:0 0 auto;width:min(80vw,320px);scroll-snap-align:start;border-radius:var(--r);overflow:hidden;background:var(--surface);box-shadow:var(--ins);}
.dj-po-cover{position:relative;aspect-ratio:4/3;overflow:hidden;}
.dj-po-cover img{width:116%;max-width:116%;height:100%;object-fit:cover;filter:saturate(.92);will-change:transform;}
.dj-po-date{position:absolute;top:14px;left:14px;background:rgba(255,255,255,.85);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border-radius:var(--r-pill);padding:7px 14px;font-family:var(--ff-mono);font-size:9.5px;letter-spacing:.08em;color:rgba(10,10,10,.96);}
.dj-po-body{padding:20px 22px 24px;}
.dj-po-title{font-family:var(--ff-serif);font-size:clamp(22px,2.4vw,30px);color:var(--ink);line-height:1;}
.dj-po-meta{margin-top:10px;display:flex;flex-wrap:wrap;gap:4px 16px;font-size:12.5px;color:var(--muted);}
.dj-po-meta span{display:inline-flex;align-items:center;gap:6px;}
.dj-po-meta i{color:var(--faint);font-size:13px;}
.dj-po-bar{margin:26px var(--pad) 0;height:1px;background:var(--line);position:relative;overflow:hidden;}
.dj-po-bar i{position:absolute;inset:0;background:var(--ink);transform-origin:0 50%;transform:scaleX(0);display:block;}
@media(min-width:900px){
  .dj-po-stage{height:100svh;display:flex;flex-direction:column;justify-content:center;overflow:hidden;}
  .dj-po-track{overflow:visible;scroll-snap-type:none;will-change:transform;}
  .dj-po-card{width:440px;}
}

/* ═══ EM NÚMEROS — spec-sheet luxury ═══ */
/* hairline "desenhada": o GSAP anima --rl (0→1) e o ::before lê a var.
   Sem JS/reduced-motion --rl fica 1 e a régua aparece inteira. */
.dj-rule-draw{position:relative;}
.dj-rule-draw::before{content:"";position:absolute;top:0;left:0;right:0;height:1px;background:var(--line);transform:scaleX(var(--rl));transform-origin:0 50%;}
.dj-nums{display:grid;grid-template-columns:1fr 1fr;gap:clamp(28px,4vw,56px) clamp(20px,4vw,64px);}
.dj-num{position:relative;padding-top:clamp(18px,2.6vw,30px);}
.dj-num::before{content:"";position:absolute;top:0;left:0;right:0;height:1px;background:var(--line);transform:scaleX(var(--rl));transform-origin:0 50%;}
.dj-num-v{font-family:var(--ff-display);font-size:clamp(58px,9vw,128px);line-height:.9;color:var(--ink);letter-spacing:-.005em;display:flex;align-items:baseline;gap:4px;}
.dj-num-v small{font-family:var(--ff-serif);font-size:.36em;color:var(--muted);}
.dj-num-l{margin-top:12px;font-size:13px;color:var(--muted);max-width:24ch;line-height:1.55;}
@media(min-width:900px){.dj-nums{grid-template-columns:repeat(4,1fr);}}

/* ═══ DIVIDIU A CABINE — editorial roster ═══ */
.dj-roster{position:relative;}
.dj-roster::before{content:"";position:absolute;top:0;left:0;right:0;height:1px;background:var(--line);transform:scaleX(var(--rl));transform-origin:0 50%;}
.dj-ro{position:relative;display:grid;grid-template-columns:64px 1fr auto;gap:clamp(16px,3vw,32px);align-items:center;padding-block:clamp(18px,2.6vw,28px);transition:padding .5s var(--ease),background .5s var(--ease);}
.dj-ro::after{content:"";position:absolute;bottom:0;left:0;right:0;height:1px;background:var(--line);transform:scaleX(var(--rl));transform-origin:0 50%;}
.dj-ro:hover{padding-left:14px;background:var(--surface);}
.dj-ro-av{width:60px;height:60px;border-radius:50%;overflow:hidden;background:var(--surface);box-shadow:var(--ins);}
.dj-ro-av img{width:100%;height:100%;object-fit:cover;object-position:center 20%;filter:grayscale(1);transition:filter .55s var(--ease),transform .55s var(--ease);}
.dj-ro:hover .dj-ro-av img{filter:grayscale(0);transform:scale(1.06);}
.dj-ro-name{font-family:var(--ff-serif);font-size:clamp(26px,4.4vw,46px);color:var(--ink);line-height:1;}
.dj-ro-x{display:flex;align-items:center;gap:18px;}
.dj-ro-role{font-family:var(--ff-mono);font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--faint);white-space:nowrap;}
.dj-ro-arrow{font-size:20px;color:var(--faint);transition:transform .45s var(--ease),color .45s var(--ease);}
.dj-ro:hover .dj-ro-arrow{transform:translate(4px,-4px);color:var(--ink);}

/* ═══ OUT NOW ═══ */
.dj-trk{position:relative;display:grid;grid-template-columns:34px 1fr auto auto;gap:clamp(14px,2.6vw,28px);align-items:center;padding-block:clamp(18px,2.4vw,26px);cursor:pointer;transition:padding .5s var(--ease),background .5s var(--ease);}
.dj-trk::after{content:"";position:absolute;bottom:0;left:0;right:0;height:1px;background:var(--line);transform:scaleX(var(--rl));transform-origin:0 50%;}
.dj-trk:hover{padding-left:14px;background:var(--surface);}
.dj-trk-i{font-family:var(--ff-mono);font-size:11px;color:var(--faint);}
.dj-trk-t{font-family:var(--ff-serif);font-size:clamp(22px,3.6vw,36px);color:var(--ink);line-height:1.05;}
.dj-trk-m{font-family:var(--ff-mono);font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-top:6px;}
.dj-trk-share{width:44px;height:44px;border-radius:50%;display:grid;place-items:center;font-size:16px;color:var(--muted);transition:color .4s var(--ease),background .4s var(--ease);}
.dj-trk-share:hover{color:var(--ink);background:var(--surface-2);}
.dj-trk-play{width:50px;height:50px;border-radius:50%;display:grid;place-items:center;font-size:18px;background:var(--surface);color:var(--ink);box-shadow:var(--ins);transition:background .4s var(--ease),color .4s var(--ease);}
.dj-trk:hover .dj-trk-play,.dj-trk.dj-is-on .dj-trk-play{background:var(--ink);color:var(--bg);}
.dj-sc-shelf{overflow:hidden;max-height:0;opacity:0;transition:max-height .8s var(--ease),opacity .6s var(--ease),margin .6s var(--ease);border-radius:var(--r-sm);box-shadow:var(--ins);}
.dj-sc-shelf.dj-open{max-height:200px;opacity:1;margin-top:28px;}
.dj-sc-shelf iframe{width:100%;height:166px;border:0;display:block;}

/* ═══ KIT DE IMPRENSA — the single ink moment ═══ */
.dj-kit{background:var(--ink);color:var(--bg);border-radius:var(--r);padding:clamp(36px,7vw,88px);position:relative;overflow:hidden;scroll-margin-top:calc(var(--evtop-h) + var(--st) + 24px);will-change:transform;}
/* sheen — luz varrendo o único bloco ink da página (scrub no scroll) */
.dj-kit::before{
  content:"";position:absolute;inset:-40% -10%;pointer-events:none;z-index:1;
  background:radial-gradient(42% 52% at var(--sx,20%) 12%,rgba(255,255,255,.10),transparent 70%);
}
/* empilhamento do bloco ink: marca d'água (0) → sheen (1) → conteúdo (2) */
.dj-kit > *{position:relative;z-index:2;}
.dj-kit-wm{position:absolute;right:-4%;bottom:-14%;z-index:0!important;font-family:var(--ff-display);font-size:clamp(140px,26vw,360px);line-height:.8;color:rgba(255,255,255,.045);pointer-events:none;white-space:nowrap;}
.dj-kit .dj-lbl{color:rgba(255,255,255,.4);}
.dj-kit .dj-lbl .dj-dot{background:var(--accent);}
.dj-kit-t{font-family:var(--ff-serif);font-size:clamp(40px,7.4vw,84px);line-height:.98;color:#fff;margin:18px 0 16px;}
.dj-kit-p{color:rgba(255,255,255,.52);max-width:46ch;line-height:1.75;font-size:14.5px;margin-bottom:34px;}
.dj-kit-ctas{display:flex;gap:12px;flex-wrap:wrap;position:relative;z-index:2;}
.dj-kit .dj-btn-white{background:#fff;color:rgba(10,10,10,.96);}
.dj-kit .dj-btn-white:hover{opacity:.86;}
.dj-kit .dj-btn-out{box-shadow:inset 0 0 0 1px rgba(255,255,255,.22);color:#fff;}
.dj-kit .dj-btn-out:hover{background:rgba(255,255,255,.08);}
.dj-kit-meta{display:flex;gap:26px;flex-wrap:wrap;margin-top:36px;position:relative;z-index:2;}
.dj-kit-meta div{font-family:var(--ff-mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.34);}
.dj-kit-meta b{display:block;font-family:var(--ff-main);font-size:14px;letter-spacing:0;text-transform:none;color:#fff;margin-bottom:4px;font-weight:500;}

/* ═══ SOBRE — vídeo tem prioridade sobre a foto: se o DJ tiver vídeo, .dj-page ele cobre
   a mesma caixa 4/5 em loop mudo (parece uma foto viva); senão cai pra uma foto
   DIFERENTE da imagem principal do hero (nunca repete o 1º img). ═══ */
.dj-about{display:grid;grid-template-columns:1fr;gap:clamp(36px,6vw,88px);align-items:center;}
.dj-about-img{border-radius:var(--r);overflow:hidden;aspect-ratio:4/5;box-shadow:var(--ins);will-change:clip-path;}
.dj-about-img img{width:100%;height:100%;object-fit:cover;object-position:center top;filter:saturate(.9);will-change:transform;}
.dj-about-img video{width:100%;height:100%;object-fit:cover;object-position:center center;display:block;}
.dj-about-p{font-size:clamp(16px,1.6vw,20px);line-height:1.85;color:var(--txt);max-width:52ch;}
.dj-about-p strong{color:var(--ink);font-weight:600;}
.dj-tags{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;}
.dj-tag{background:var(--surface);box-shadow:var(--ins);border-radius:var(--r-pill);padding:9px 17px;font-family:var(--ff-mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);}
@media(min-width:900px){.dj-about{grid-template-columns:5fr 7fr;}}

/* ═══ FOOTER — name block padded via .dj-wrap; foot-img is a direct, .dj-page unpadded
   child of <footer> so it hits width:100% flush to the document's bottom ═══ */
.dj-foot{border-top:1px solid var(--line);padding-top:clamp(56px,8vw,110px);overflow:hidden;}
.dj-foot-name{font-family:var(--ff-display);font-size:clamp(96px,24vw,360px);line-height:.82;text-transform:uppercase;color:var(--ink);white-space:nowrap;will-change:transform;}
.dj-foot-grid{display:flex;flex-direction:column;gap:28px;padding-block:clamp(36px,5vw,64px);}
.dj-socials{display:flex;gap:10px;flex-wrap:wrap;}
.dj-soc{width:50px;height:50px;border-radius:50%;display:grid;place-items:center;font-size:19px;background:var(--surface);color:var(--ink);box-shadow:var(--ins);transition:background .4s var(--ease);}
.dj-soc:hover{background:var(--surface-2);}
.dj-foot-copy{font-family:var(--ff-mono);font-size:10px;letter-spacing:.1em;color:var(--faint);}
@media(min-width:900px){.dj-foot-grid{flex-direction:row;justify-content:space-between;align-items:center;}}
/* footer image — direct child of <footer> (zero horizontal padding on that ancestor),
   .dj-page width:100% guarantees flush left/right without any vw/scrollbar mismatch;
   it is the LAST node in <footer> and body/html carry no padding-bottom,
   .dj-page so this is the literal last pixel row of the document — true bottom:0. */
.dj-foot-img{position:relative;width:100%;display:block;height:min(48vh,440px);overflow:hidden;background:var(--surface);margin:0!important;}
.dj-foot-img img{width:100%;height:126%;object-fit:cover;filter:saturate(.85) contrast(1.02) brightness(.92);will-change:transform;}
/* hardening — zero gap forçado no rodapé, .dj-page sem depender de cascade externa */
.dj-foot,.dj-foot-img{margin-bottom:0!important;padding-bottom:0!important;}
.dj-foot-img-ov{
  position:absolute!important;inset:0!important;bottom:0!important;margin:0!important;
  display:grid;place-content:center;text-align:center;
  background:linear-gradient(180deg,rgba(0,0,0,.12),rgba(0,0,0,.42));
}
.dj-foot-img-ov small{font-family:var(--ff-mono);font-size:10px;letter-spacing:.26em;text-transform:uppercase;color:rgba(255,255,255,.65);}

/* ═══ DOCK — 100% icon-only (luxury grade, .dj-page igual à página de local) ═══ */
.dj-dock{position:fixed;left:50%;bottom:calc(16px + var(--sb));transform:translate(-50%,180%);z-index:110;display:flex;gap:4px;padding:8px;border-radius:var(--r-pill);background:rgba(255,255,255,.7);backdrop-filter:blur(34px) saturate(1.8);-webkit-backdrop-filter:blur(34px) saturate(1.8);box-shadow:var(--ins),0 24px 60px -30px rgba(10,10,10,.25);transition:transform .6s var(--ease);max-width:calc(100vw - 24px);overflow-x:auto;scrollbar-width:none;-webkit-overflow-scrolling:touch;}
.dj-dock::-webkit-scrollbar{display:none;}
.dj-dock.dj-show{transform:translate(-50%,0);}
.dj-dock .dj-icon-btn{width:47px;height:47px;flex-shrink:0;background:transparent;box-shadow:none;}
.dj-dock .dj-icon-btn.dj-is-active{color:var(--accent);}

/* toast */
.dj-toast{position:fixed;left:50%;bottom:calc(92px + var(--sb));transform:translateX(-50%) translateY(14px);z-index:130;background:var(--ink);color:var(--bg);font-family:var(--ff-mono);font-size:11.5px;letter-spacing:.04em;padding:12px 20px;border-radius:var(--r-pill);display:inline-flex;align-items:center;gap:9px;opacity:0;pointer-events:none;transition:opacity .4s var(--ease),transform .4s var(--ease);white-space:nowrap;}
.dj-toast.dj-show{opacity:1;transform:translateX(-50%) translateY(0);}

/* ═══ OUT NOW LIGHTBOX ═══ */
.dj-outnow-lb{position:fixed;inset:0;z-index:9700;opacity:0;pointer-events:none;display:flex;align-items:flex-end;justify-content:center;background:rgba(5,5,5,.72);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);transition:opacity .35s ease;padding:max(16px,var(--st)) 16px calc(16px + var(--sb));}
.dj-outnow-lb.dj-open{opacity:1;pointer-events:auto;}
.dj-outnow-lb-panel{width:min(100%,640px);max-height:min(82vh,720px);overflow:auto;background:var(--bg);border-radius:var(--r);box-shadow:var(--ins);padding:clamp(20px,4vw,36px);transform:translateY(24px);transition:transform .4s var(--ease);}
.dj-outnow-lb.dj-open .dj-outnow-lb-panel{transform:none;}
.dj-outnow-lb-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px;}
.dj-outnow-lb-head h3{font-family:var(--ff-serif);font-size:clamp(28px,5vw,40px);color:var(--ink);line-height:1;}
.dj-outnow-lb-x{width:44px;height:44px;border-radius:50%;display:grid;place-items:center;font-size:20px;background:var(--surface);color:var(--ink);box-shadow:var(--ins);flex:0 0 auto;}
.dj-trk-more .dj-trk-t{font-size:clamp(18px,3vw,28px);}
.dj-trk-more .dj-trk-play{background:var(--ink);color:var(--bg);}

/* reveal fallback */
.dj-rv{opacity:0;transform:translateY(28px);transition:opacity 1s var(--ease),transform 1s var(--ease);}
.dj-rv.dj-in{opacity:1;transform:none;}
@media(prefers-reduced-motion:reduce){
  .dj-rv{opacity:1;transform:none;transition:none;}
  .dj-mq-inner{animation:none;}
  .dj-hero-cue{animation:none;}
}
</style>
