<?php

/**
 * Mapa — screen styles (PHASE 006).
 *
 * Ported VERBATIM from _official_layout/css/mapa.css. Every custom property
 * here already resolves against core.js (checked at port time in the
 * mockup's own header comment: --bg/--card/--surface/--muted/--txt-heading/
 * --accent/--black-1/--black-4/--rgb-diff/--ease/--ease-snappy/--r/--r-sm/
 * --r-pill). The only new custom properties are page-specific geometry
 * prefixed "--mapa-", which does not collide with any reserved token
 * family. Same rule phases 001-005 already pass: no local :root.
 *
 * Scoped under .mapa-app (the host div layout.php renders), including the
 * Leaflet overrides, so nothing leaks into the OTHER Leaflet maps already
 * active elsewhere in the shell (Market's Acomodações map, the Local map in
 * the add-new-event form).
 *
 * Dropped from the original mockup CSS (per phase 006's "real data only"
 * decision — see includes/mapa-data.php): none of the rules below reference
 * the retired POI categories; the filter-bloom grid still works unchanged
 * with 3 real entries (Todos/Eventos/Locais) instead of 10.
 *
 * @package Apollo\Templates
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<style id="apollo-mapa-screen">
.mapa-app{--mapa-card-w:clamp(240px,82dvw,280px);--mapa-card-h:clamp(320px,66dvh,380px);--mapa-radius:24px;--mapa-inset:5px;--mapa-inner-r:calc(var(--mapa-radius) - var(--mapa-inset));--mapa-pad:clamp(14px,3.5dvw,20px);--mapa-shadow:0 10px 40px rgba(var(--rgb-diff),.16), 0 2px 8px rgba(var(--rgb-diff),.07);position:fixed;inset:0;z-index:1;overflow:hidden;user-select:none;-webkit-user-select:none;touch-action:manipulation;-webkit-tap-highlight-color:transparent;background:var(--surface);}
html.mapa-scroll-lock,html.mapa-scroll-lock body{overflow:hidden!important;overscroll-behavior:none;}
.mapa-app #map{position:absolute;inset:0;z-index:0;background:var(--surface);}
.mapa-app .leaflet-tile-pane{filter:grayscale(1) contrast(.84) brightness(1.1);}
.mapa-app .leaflet-control-container,.mapa-app .leaflet-popup-content-wrapper,.mapa-app .leaflet-popup-tip{display:none!important;}
.mapa-app .apollo-leaflet-icon{background:none!important;border:none!important;}
.mapa-app .apl-m{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;box-shadow:0 0 0 .5px rgba(var(--rgb-diff),.10),0 1px 1.5px rgba(var(--rgb-diff),.05),0 4px 12px rgba(var(--rgb-diff),.16);border:.25px solid rgba(255,255,255,.9);transition:box-shadow .4s var(--ease),transform .4s var(--ease);cursor:pointer;position:relative;}
.mapa-app .apl-m:hover{box-shadow:0 0 0 .5px rgba(var(--rgb-diff),.08),0 2px 4px rgba(var(--rgb-diff),.06),0 8px 20px rgba(var(--rgb-diff),.2);transform:scale(1.14);}
.mapa-app .apl-m.active{transform:scale(1.28)!important;z-index:9999!important;box-shadow:0 0 0 3px rgba(255,255,255,.96),0 6px 24px rgba(var(--rgb-diff),.32);}
.mapa-app .apl-m i{font-size:10px;line-height:1;pointer-events:none;}
@keyframes mapaPulseRing{0%{transform:translate(-50%,-50%) scale(1);opacity:.55;}100%{transform:translate(-50%,-50%) scale(3);opacity:0;}}
.mapa-app .apl-pulse{position:absolute;width:22px;height:22px;border-radius:50%;top:50%;left:50%;transform:translate(-50%,-50%);pointer-events:none;z-index:-1;animation:mapaPulseRing 2s cubic-bezier(.24,0,.38,1) infinite;}
.mapa-app .apl-cluster-basket{position:relative;width:64px;height:28px;cursor:pointer;}
.mapa-app .apl-m-mini{position:absolute;width:17px;height:17px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:7px;border:1.5px solid rgba(255,255,255,.9);box-shadow:0 2px 6px rgba(var(--rgb-diff),.32);transform-origin:bottom center;transition:filter .25s;}
.mapa-app .apl-cluster-basket:active .apl-m-mini{filter:brightness(1.18);}
.mapa-app .apl-label{font-size:10px;font-weight:600;color:#f8fafc;background:rgba(5,5,5,.88);box-shadow:inset 0 1px 0 0 rgba(255,255,255,.35),inset 0 0 0 1px rgba(255,255,255,.15),0 4px 12px -4px rgba(5,5,5,.3),0 12px 24px -8px rgba(5,5,5,.3);border:1px solid rgba(5,5,5,.04);padding:7px 9px;border-radius:var(--r-sm,15px);white-space:nowrap;pointer-events:none;letter-spacing:-.01em;}
.mapa-app .nav-pill{position:fixed;z-index:3;top:calc(var(--safe-top,0px) + 72px);right:18px;display:flex;align-items:center;gap:2px;}
.mapa-app .nav-filter-btn{width:36px;height:36px;border-radius:var(--r-pill,99px);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:20px;color:var(--muted);background:var(--card);box-shadow:inset 0 1px 0 0 rgba(var(--rgb-theme),.2),inset 0 0 0 1px rgba(var(--rgb-theme),.075);border:1px solid rgba(var(--rgb-diff),.04);transition:color .3s var(--ease),background .3s var(--ease),transform .3s var(--ease-snappy);}
.mapa-app .nav-filter-btn:hover{color:var(--txt-heading);}
.mapa-app .nav-filter-btn.on{color:var(--bg);background:var(--black-1);}
.mapa-app .nav-filter-btn:active{transform:scale(.9);}
.mapa-app .filter-bloom{position:fixed;z-index:3;top:calc(var(--safe-top,0px) + 114px);left:50%;transform:translateX(-50%) translateY(-6px) scale(.95);width:min(340px,92dvw);background:var(--bg);box-shadow:var(--mapa-shadow),inset 0 1px 0 0 rgba(var(--rgb-theme),.9),inset 0 0 0 1px rgba(var(--rgb-theme),.4);border:1px solid rgba(var(--rgb-diff),.04);border-radius:var(--r,22px);padding:14px;display:grid;grid-template-columns:repeat(3,1fr);gap:6px;opacity:0;pointer-events:none;transition:opacity .28s var(--ease),transform .34s var(--ease-snappy);}
.mapa-app .filter-bloom.open{opacity:1;pointer-events:auto;transform:translateX(-50%) translateY(0) scale(1);}
.mapa-app .fb-item{display:flex;flex-direction:column;align-items:center;gap:5px;padding:11px 6px 9px;border-radius:var(--r-sm,14px);cursor:pointer;border:none;background:var(--surface);transition:background .2s var(--ease),transform .22s var(--ease-snappy);animation:fbStagger .3s var(--ease) both;}
@keyframes fbStagger{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0) scale(1);}}
.mapa-app .fb-item.on{background:var(--black-1);}
.mapa-app .fb-icon{font-size:24px;color:var(--muted);transition:color .18s;}
.mapa-app .fb-item.on .fb-icon{color:var(--bg);}
.mapa-app .fb-count{font-size:.9rem;font-weight:700;color:var(--txt-heading);line-height:1;}
.mapa-app .fb-item.on .fb-count{color:var(--bg);}
.mapa-app .fb-name{font-size:.58rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:var(--muted);line-height:1;}
.mapa-app .fb-item.on .fb-name{color:rgba(255,255,255,.55);}
.mapa-app #bloomScrim{position:fixed;inset:0;z-index:2;display:none;pointer-events:none;}
.mapa-app #bloomScrim.on{display:block;pointer-events:auto;}
.mapa-app .map-fab-wrap{position:fixed;right:16px;z-index:4;transition:bottom .52s var(--ease);}
.mapa-app .map-fab{width:44px;height:44px;border-radius:50%;background:var(--bg);border:1px solid rgba(var(--rgb-diff),.04);box-shadow:var(--mapa-shadow),inset 0 1px 0 0 rgba(var(--rgb-theme),.9),inset 0 0 0 1px rgba(var(--rgb-theme),.4);color:var(--muted);font-size:1.3rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .28s var(--ease-snappy),color .2s var(--ease);}
.mapa-app .map-fab:hover{color:var(--txt-heading);}
.mapa-app .map-fab:active{transform:scale(.86);}
.mapa-app .card-stage{position:absolute;left:0;right:0;z-index:5;display:flex;justify-content:center;padding:0 16px;pointer-events:none;transition:bottom .46s var(--ease);}
.mapa-app .place-card{position:relative;width:var(--mapa-card-w);background:var(--bg);border-radius:var(--mapa-radius);box-shadow:var(--mapa-shadow);pointer-events:auto;animation:mapaCardIn .44s var(--ease) both;cursor:default;}
@keyframes mapaCardIn{from{opacity:0;transform:translateY(32px) scale(.94);}to{opacity:1;transform:translateY(0) scale(1);}}
.mapa-app .place-card.out{animation:mapaCardOut .28s var(--ease) both;pointer-events:none;}
@keyframes mapaCardOut{to{opacity:0;transform:translateY(24px) scale(.94);}}
.mapa-app .place-card--loading{opacity:.65;pointer-events:none;}
.mapa-app .card__imgwrap{position:relative;overflow:hidden;background:var(--surface);}
.mapa-app .card__imgwrap::after{content:'';position:absolute;inset:0;background:linear-gradient(105deg,transparent 20%,rgba(255,255,255,.5) 50%,transparent 80%);background-size:250% 100%;animation:mapaShimmer 1.5s ease-in-out infinite;z-index:2;pointer-events:none;transition:opacity .4s;}
.mapa-app .card__imgwrap.loaded::after{opacity:0;animation:none;}
@keyframes mapaShimmer{from{background-position:120% 0;}to{background-position:-20% 0;}}
.mapa-app .card__img{display:block;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity .5s var(--ease);position:relative;z-index:1;}
.mapa-app .card__img.visible{opacity:1;}
.mapa-app .place-card--overlay{height:var(--mapa-card-h);}
.mapa-app .place-card--overlay .card__imgwrap{position:absolute;inset:var(--mapa-inset);border-radius:var(--mapa-inner-r);}
.mapa-app .place-card--overlay .card__img{position:absolute;inset:0;border-radius:var(--mapa-inner-r);}
.mapa-app .card__gradient{position:absolute;inset:0;border-radius:var(--mapa-inner-r);background:linear-gradient(to top,rgba(5,5,5,.92) 0%,rgba(5,5,5,.5) 40%,rgba(5,5,5,.1) 70%,transparent 100%);z-index:3;pointer-events:none;}
.mapa-app .place-card--overlay .card__content{position:absolute;bottom:0;left:0;right:0;padding:0 var(--mapa-pad) var(--mapa-pad);z-index:4;}
.mapa-app .place-card--overlay .card__name{color:#fff;}
.mapa-app .place-card--overlay .card__category{color:rgba(255,255,255,.6);}
.mapa-app .place-card--overlay .card__drow{color:rgba(255,255,255,.78);}
.mapa-app .place-card--overlay .card__icon{color:rgba(255,255,255,.5);}
.mapa-app .place-card--overlay .card__loctag{color:var(--accent);}
.mapa-app .place-card--split{display:flex;flex-direction:column;}
.mapa-app .place-card--split .card__imgwrap{margin:var(--mapa-inset) var(--mapa-inset) 0;border-radius:var(--mapa-inner-r) var(--mapa-inner-r) 10px 10px;aspect-ratio:1/1;flex-shrink:0;}
.mapa-app .place-card--split .card__content{padding:12px var(--mapa-pad) var(--mapa-pad);flex:1;}
.mapa-app .place-card--split .card__name{color:var(--txt-heading);}
.mapa-app .place-card--split .card__category{color:var(--muted);}
.mapa-app .place-card--split .card__drow{color:var(--txt-color,var(--muted));}
.mapa-app .place-card--split .card__icon{color:var(--muted);}
.mapa-app .card__close{position:absolute;top:9px;left:9px;z-index:20;background:none;border:none;padding:5px;cursor:pointer;color:rgba(255,255,255,.85);font-size:1rem;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5));display:none;align-items:center;transition:transform .25s var(--ease-snappy);}
.mapa-app .place-card--overlay .card__close{display:flex;}
.mapa-app .card__close:active{transform:scale(.82);}
.mapa-app .card__loctag{display:inline-flex;align-items:center;gap:4px;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:5px;}
.mapa-app .card__name{font-size:clamp(1.05rem,3.8dvw,1.28rem);font-weight:600;letter-spacing:-.03em;line-height:1.1;margin-bottom:3px;}
.mapa-app .card__category{font-size:.75rem;font-weight:400;line-height:1.3;margin-bottom:12px;}
.mapa-app .card__details{display:flex;align-items:flex-start;justify-content:space-between;gap:6px;margin-bottom:14px;width:100%;}
.mapa-app .card__drow{display:flex;align-items:flex-start;gap:5px;font-size:.75rem;line-height:1.3;min-width:0;}
.mapa-app .card__icon{flex-shrink:0;margin-top:1px;}
.mapa-app .card__dtxt{display:flex;flex-direction:column;gap:1px;min-width:0;}
.mapa-app .card__dtxt span{font-size:.75rem;font-weight:400;}
.mapa-app .card__dtxt strong{font-size:.68rem;font-weight:600;letter-spacing:.01em;}
.mapa-app .card__actions{display:flex;align-items:center;gap:8px;width:100%;}
.mapa-app .card__cta{flex:1;min-width:0;background:var(--black-1);color:var(--bg);border:none;border-radius:var(--r-pill,100px);font-size:.74rem;font-weight:600;letter-spacing:.02em;padding:11px 14px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;transition:background .2s var(--ease),transform .15s var(--ease-snappy);}
.mapa-app .card__cta:hover{background:var(--black-4);}
.mapa-app .card__cta:active{transform:scale(.97);}
.mapa-app #mapScrim{position:fixed;inset:0;z-index:4;pointer-events:none;display:none;}
.mapa-app #mapScrim.on{display:block;pointer-events:auto;}
.mapa-app .map-sheet{position:fixed;left:0;right:0;bottom:0;z-index:6;background:var(--bg);border-radius:var(--r,26px) var(--r,26px) 0 0;box-shadow:0 -1px 0 rgba(var(--rgb-diff),.06),0 -10px 40px rgba(var(--rgb-diff),.1);height:0;overflow:hidden;will-change:height;transition:height .52s var(--ease);user-select:none;-webkit-user-select:none;}
.mapa-app .sheet-drag{padding:14px 0;min-height:44px;display:flex;justify-content:center;cursor:grab;flex-shrink:0;touch-action:none;}
.mapa-app .sheet-drag:active{cursor:grabbing;}
.mapa-app .sheet-handle{width:36px;height:4px;border-radius:2px;background:rgba(var(--rgb-diff),.14);transition:background .2s;}
.mapa-app .map-sheet:active .sheet-handle{background:rgba(var(--rgb-diff),.24);}
.mapa-app .sheet-inner{overflow-y:auto;overflow-x:hidden;overscroll-behavior:contain;-webkit-overflow-scrolling:touch;height:calc(100% - 44px);scrollbar-width:none;user-select:none;-webkit-user-select:none;}
.mapa-app .sheet-inner::-webkit-scrollbar{display:none;}
.mapa-app .sheet-hd{padding:2px 18px 12px;}
.mapa-app .sheet-hd-row{display:flex;align-items:flex-end;justify-content:space-between;}
.mapa-app .sheet-title{font-size:1.45rem;font-weight:600;letter-spacing:-.025em;color:var(--txt-heading);line-height:1;}
.mapa-app .sheet-count{font-size:.8rem;font-weight:600;color:var(--muted);}
.mapa-app .sheet-sub{font-size:.75rem;color:var(--muted);margin-top:5px;display:flex;align-items:center;gap:5px;}
.mapa-app .loc-dot{width:6px;height:6px;border-radius:50%;background:#34C759;flex-shrink:0;animation:mapaLocPulse 2.4s ease-out infinite;}
@keyframes mapaLocPulse{0%{box-shadow:0 0 0 0 rgba(52,199,89,.5);}60%{box-shadow:0 0 0 6px rgba(52,199,89,0);}100%{box-shadow:0 0 0 0 rgba(52,199,89,0);}}
.mapa-app .place-rail{position:relative;overflow:hidden;height:224px;margin:0;}
.mapa-app .pcm{position:absolute;width:184px;top:4px;left:50%;margin-left:-92px;border-radius:var(--r,20px);overflow:hidden;background:var(--card);cursor:pointer;user-select:none;box-shadow:inset 0 1px 0 0 rgba(var(--rgb-theme),.9),inset 0 0 0 1px rgba(var(--rgb-theme),.4);border:1px solid rgba(var(--rgb-diff),.04);transition:box-shadow .28s var(--ease),transform .28s var(--ease-snappy);}
.mapa-app .pcm:active{transform:scale(.98);}
.mapa-app .rail-controls{display:flex;justify-content:space-between;align-items:center;padding:0 18px 14px;}
.mapa-app .rail-btn{width:36px;height:36px;border-radius:50%;background:var(--surface);border:none;color:var(--muted);font-size:1.3rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:color .2s var(--ease),background .2s var(--ease),transform .2s var(--ease-snappy);}
.mapa-app .rail-btn:hover{color:var(--txt-heading);background:var(--card);}
.mapa-app .rail-btn:active{transform:scale(.85);}
.mapa-app .pcm-img-wrap{position:relative;overflow:hidden;background:var(--surface);height:114px;}
.mapa-app .pcm-img-wrap::after{content:'';position:absolute;inset:0;background:linear-gradient(to bottom,transparent 55%,rgba(5,5,5,.28) 100%);pointer-events:none;z-index:1;}
.mapa-app .pcm-img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .6s var(--ease);}
.mapa-app .pcm:active .pcm-img{transform:scale(1.04);}
.mapa-app .pcm-body{padding:11px 13px 14px;}
.mapa-app .pcm-cat{display:flex;align-items:center;gap:6px;font-size:.64rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px;}
.mapa-app .pcm-cat-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;}
.mapa-app .pcm-name{font-size:.94rem;font-weight:600;color:var(--txt-heading);letter-spacing:-.015em;line-height:1.22;margin-bottom:5px;}
.mapa-app .pcm-meta{display:flex;align-items:center;gap:5px;font-size:.72rem;color:var(--muted);}
.mapa-app .pcm-meta i{font-size:.68rem;}
.mapa-app .place-sep{height:1px;background:rgba(var(--rgb-diff),.06);margin:2px 18px 12px;}
.mapa-app .place-list{padding:0 14px;padding-bottom:calc(var(--safe-bottom,0px) + 20px);}
.mapa-app .pr{display:flex;align-items:center;gap:12px;padding:10px 8px;border-radius:var(--r-sm,16px);cursor:pointer;transition:background .18s var(--ease);}
.mapa-app .pr:active{background:var(--surface);}
.mapa-app .pr-thumb{width:52px;height:52px;border-radius:var(--r-xs,13px);object-fit:cover;flex-shrink:0;}
.mapa-app .pr-info{flex:1;min-width:0;}
.mapa-app .pr-name{font-size:.92rem;font-weight:600;color:var(--txt-heading);letter-spacing:-.01em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.mapa-app .pr-sub{display:flex;align-items:center;gap:6px;font-size:.75rem;color:var(--muted);margin-top:3px;}
.mapa-app .pr-cat-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;}
.mapa-app .pr-arr{font-size:.95rem;color:rgba(var(--rgb-diff),.2);flex-shrink:0;}
.mapa-app .mapa-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:36px 18px;color:var(--muted);text-align:center;}
.mapa-app .mapa-empty i{font-size:28px;opacity:.5;}
.mapa-app .mapa-empty span{font-size:.85rem;}
</style>
