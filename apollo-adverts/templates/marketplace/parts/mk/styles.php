<?php

/**
 * Marketplace — screen styles (PHASE 003)
 *
 * Ported from screen/_official_layout/js/view.market.js's STYLE array (the
 * mockup's own Portal de Anuncios stylesheet), unwrapped from its JS string
 * form into plain CSS. Values unchanged.
 *
 * STRICT TOKEN RULE: every custom property referenced below is one core.js
 * actually injects on Apollo pages. Verified live against /portal before
 * writing this file -- --accent, --accent-sunset-gold, --info-blue,
 * --shadow-1, --border-hover, --surface-hover, --ease, --r, --r-pill, --card,
 * --surface, --bg, --border, --muted, --txt-color, --txt-heading, --ff-mono
 * all resolve. The mockup's --fsx does NOT exist in this ecosystem, so its
 * references are rewritten to core.js's real font-scale token var(--fs-u, 1).
 * NOTHING here declares a token; :root belongs to core.js alone.
 *
 * @package Apollo\Adverts
 * @since   1.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-mk-screen">
.mk-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:6px 2px 18px;}
.mk-head h1{font-size:calc(var(--fs-u, 1)*26px);font-weight:650;letter-spacing:-.02em;color:var(--txt-heading);margin:0;}
.mk-head .sub{font-family:var(--ff-mono);font-size:10px;text-transform:uppercase;letter-spacing:.14em;color:var(--muted);margin-top:4px;display:block;}
.mk-safety{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--border);background:var(--card);border-radius:var(--r-pill);padding:9px 16px;font-family:var(--ff-mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--txt-color);cursor:pointer;transition:all .3s ease;}
.mk-safety:hover{border-color:var(--border-hover);background:var(--surface-hover);}
.mk-safety i{color:var(--info-blue);font-size:14px;}
.mk-pills{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:22px;}
.mk-pill{font-family:var(--ff-mono);font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--txt-color);background:var(--surface);border:1px solid var(--border);border-radius:var(--r-pill);padding:8px 16px;cursor:pointer;transition:all .3s ease;user-select:none;}
.mk-pill.is-active{background:var(--txt-heading);color:var(--bg);border-color:transparent;}
.mk-sec-lbl{font-family:var(--ff-mono);font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);display:flex;align-items:center;gap:8px;margin:26px 0 14px;}
.mk-sec-lbl i{color:var(--accent);font-size:14px;}
.mk-grid{display:grid;grid-template-columns:1fr;gap:16px;}
@media(min-width:760px){.mk-grid{grid-template-columns:repeat(2,1fr);}}
@media(min-width:1240px){.mk-grid{grid-template-columns:repeat(3,1fr);}}
.mk-ticket{border:1px solid var(--border);border-radius:var(--r);overflow:hidden;background:var(--card);transition:all .45s var(--ease);display:flex;flex-direction:column;}
.mk-ticket:hover{border-color:var(--border-hover);box-shadow:var(--shadow-1);transform:translateY(-3px);}
.mk-tk-cover{position:relative;aspect-ratio:16/8;overflow:hidden;background:var(--surface);}
.mk-tk-cover img{width:100%;height:100%;object-fit:cover;transition:transform .6s var(--ease);}
.mk-ticket:hover .mk-tk-cover img{transform:scale(1.05);}
.mk-tk-price{position:absolute;bottom:12px;right:12px;background:rgba(255,255,255,.9);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);border-radius:var(--r-pill);padding:7px 14px;font-family:var(--ff-mono);font-size:13px;font-weight:700;color:rgba(10,10,10,.95);}
.mk-tk-mine{position:absolute;top:12px;left:12px;font-family:var(--ff-mono);font-size:8.5px;letter-spacing:.12em;text-transform:uppercase;background:var(--accent);color:#fff;border-radius:var(--r-pill);padding:5px 10px;}
.mk-tk-body{padding:14px 16px 16px;display:flex;flex-direction:column;gap:10px;flex:1;}
.mk-tk-title{font-size:16px;font-weight:650;letter-spacing:-.015em;color:var(--txt-heading);line-height:1.2;}
.mk-tk-meta{font-size:12px;color:var(--muted);display:flex;flex-direction:column;gap:4px;}
.mk-tk-meta span{display:inline-flex;align-items:center;gap:7px;}
.mk-tk-meta i{font-size:13px;opacity:.6;}
.mk-tk-seller{display:flex;align-items:center;gap:9px;margin-top:auto;padding-top:12px;border-top:1px solid var(--border);}
.mk-tk-seller img{width:28px;height:28px;border-radius:50%;object-fit:cover;}
.mk-tk-seller b{font-size:12px;color:var(--txt-heading);display:block;line-height:1.1;}
.mk-tk-seller span{font-family:var(--ff-mono);font-size:9.5px;color:var(--muted);}
.mk-tk-seller .btn-sm{margin-left:auto;}
.mk-accom{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;}
.mk-ac{border:1px solid var(--border);border-radius:var(--r);overflow:hidden;background:var(--card);transition:all .4s var(--ease);}
.mk-ac:hover{border-color:var(--border-hover);transform:translateY(-2px);box-shadow:var(--shadow-1);}
.mk-ac-cover{position:relative;aspect-ratio:4/3;overflow:hidden;}
.mk-ac-cover img{width:100%;height:100%;object-fit:cover;}
.mk-ac-badge{position:absolute;top:10px;left:10px;font-family:var(--ff-mono);font-size:8.5px;letter-spacing:.1em;text-transform:uppercase;background:rgba(255,255,255,.9);color:rgba(10,10,10,.85);border-radius:var(--r-pill);padding:4px 9px;}
.mk-ac-body{padding:12px 14px 14px;}
.mk-ac-body b{font-size:13.5px;color:var(--txt-heading);display:block;}
.mk-ac-body .loc{font-size:11px;color:var(--muted);display:block;margin:3px 0 8px;}
.mk-ac-row{display:flex;align-items:center;justify-content:space-between;}
.mk-ac-row .rate{font-family:var(--ff-mono);font-size:11px;color:var(--txt-color);display:inline-flex;align-items:center;gap:4px;}
.mk-ac-row .rate i{color:var(--accent-sunset-gold);font-size:13px;}
.mk-ac-row .price{font-family:var(--ff-mono);font-size:12px;font-weight:700;color:var(--txt-heading);}
.mk-empty{border:1px dashed var(--border);border-radius:var(--r);padding:48px 20px;text-align:center;color:var(--muted);}
.mk-empty i{font-size:30px;display:block;margin-bottom:10px;opacity:.5;}
</style>
