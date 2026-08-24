<?php

/**
 * Apollo+ Shell — Aside styles
 *
 * Ported VERBATIM from screen/_official_layout/layout.html's <style> block
 * (the .ax-aside / .s / .nb / .ni / .si / .fs / .ft / .adj / .urow rules).
 * Values are unchanged — this is the Design System's own sidebar, not a
 * re-interpretation of it. Tokens only; core.js owns :root.
 *
 * Loaded once per page by apollo-plus/shell.php. Idempotent via the
 * APOLLO_PLUS_ASIDE_STYLES guard so a screen that renders the shell twice
 * (or a nested part that defensively calls it) can never duplicate the CSS.
 *
 * @package Apollo\Templates
 * @since   1.4.0
 */

if (! defined('ABSPATH')) {
    exit;
}

if (defined('APOLLO_PLUS_ASIDE_STYLES')) {
    return;
}
define('APOLLO_PLUS_ASIDE_STYLES', true);
?>
<style id="apollo-plus-aside">
/* ── Shell frame ─────────────────────────────────────────────────────────── */
.ax-aside{position:fixed;top:0;left:0;width:100vw;height:100dvh;z-index:9902;background:rgba(var(--rgb-theme),.8);backdrop-filter:blur(100px);-webkit-backdrop-filter:blur(100px);display:flex;transform:translateX(-100%);transition:transform .45s var(--ease-snappy)}
.ax-aside.open{transform:translateX(0)}
.ax-main{padding:0;width:100%;max-width:none;min-width:0;box-sizing:border-box;overflow-x:clip;--ax-pad-x:0px;margin-left:0;margin-right:0;padding-left:0!important}

/* Kill the legacy 601px "desktop" offset — the aside only pins at 1000px+ */
@media (min-width:601px) and (max-width:999px){
  .ax-main{padding-left:0!important;padding-right:0!important;margin-left:0!important;margin-right:0!important;width:100%!important;--ax-pad-x:0px!important}
}
@media (min-width:1000px){
  .ax-burger{display:none}
  .ax-shell,.ax-body{--aside-w:clamp(200px,20vw,225px)}
  .ax-aside{top:0;left:0;bottom:0;height:100dvh;width:var(--aside-w);transform:none}
  .ax-overlay{display:none}
  .ax-main{margin-left:calc(12px + var(--aside-w) + clamp(24px,5vw,52px));margin-right:clamp(28px,4.5vw,64px);width:auto!important;max-width:none!important;min-width:0;padding-left:0!important}
}

/* ── Sidebar body ────────────────────────────────────────────────────────── */
.s{display:flex;flex-direction:column;width:100%;height:100dvh;min-height:100%;font-size:calc(var(--fs-r,1)*11px);color:rgba(var(--rgb-diff),.66);text-align:left;overflow:hidden;position:relative;--aside-pad-x:12px;--aside-row-gap:9px}
.s .ni,.s .si,.s .fr,.s .fni,.s .adj,.s .urow{min-width:0;max-width:100%;box-sizing:border-box}
.s .sn,.s .fn,.s .un,.s .uro{display:block;min-width:0;flex:1 1 auto;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.si,.fr{width:100%;max-width:100%}
.si .cnt,.fr .fd,.s .ni>i,.s .si>i,.s .fr>i,.s .fni>i,.s .um,.s .pill,.tog{flex-shrink:0}
.si .cnt{margin-left:auto}
@media (max-width:1000px){.s{border-radius:0}}

.s .hd{padding:20px 14px 15px 20px;display:flex;align-items:center;gap:9px;flex-shrink:0}
.s .lg{display:flex;align-items:center;justify-content:center;flex-shrink:0}
.s .lg i{font-size:calc(var(--fs-r,1)*22px);transform:translateY(2px);color:var(--white-7)}
.s .lg i svg{width:1em;height:1em;display:inline-block}
.s .wm{font-size:calc(var(--fs-r,1)*16.5px);font-weight:500;color:var(--gray-18);letter-spacing:.01em}
.s .wm b{color:var(--white-13);font-size:80%;font-weight:900;font-family:sans-serif}

.ax-aside-scroll{flex:1 1 auto;min-height:0;overflow-y:auto;-webkit-overflow-scrolling:touch;overscroll-behavior:contain;scrollbar-width:none;-ms-overflow-style:none}
.ax-aside-scroll::-webkit-scrollbar{display:none;width:0;height:0}

/* ── Primary nav ─────────────────────────────────────────────────────────── */
.nb{padding:5px 8px}
.ni{display:flex;align-items:center;gap:var(--aside-row-gap);padding:8px var(--aside-pad-x);cursor:pointer;margin-bottom:2px;color:rgba(var(--rgb-diff),.60);font-size:calc(var(--fs-r,1)*13px);border-radius:var(--r-sm);position:relative;text-decoration:none}
.ni .sn{font-size:inherit;color:inherit;font-weight:inherit}
.ni i{font-size:calc(var(--fs-r,1)*16px);flex-shrink:0;opacity:.46}
.ni:hover{color:rgba(var(--rgb-diff),.90);background:rgba(var(--rgb-diff),.03)}
.ni:hover i{opacity:.9}
.ni.on{color:rgba(var(--rgb-diff),.90);background:rgba(var(--rgb-diff),.04);box-shadow:inset 0 1px 0 0 rgba(var(--rgb-theme),.35),inset 0 0 0 1px rgba(var(--rgb-theme),.085)}
.ni.on i{opacity:1}
.ni.on::before{content:'';position:absolute;left:0;top:50%;transform:translateY(-50%);width:3px;height:16px;background:var(--accent);border-radius:0 4px 4px 0}

/* ── Collapsible sections ────────────────────────────────────────────────── */
.sh{display:flex;align-items:center;justify-content:space-between;padding:8px var(--aside-pad-x) 2px;cursor:pointer;user-select:none}
.sh:hover{opacity:.72}
.slbl{font-family:var(--ff-mono);font-size:calc(var(--fs-r,1)*9.5px);letter-spacing:.12em;text-transform:uppercase;color:rgba(var(--rgb-diff),.35)}
.tog{transition:transform .3s var(--ease)}
.sh.shut .tog{transform:rotate(-90deg)}
.col{overflow:hidden;height:auto;min-height:0;transition:height .35s var(--ease)}
.col.shut{height:0!important}
.inn{min-height:0;overflow:hidden}
.si{display:flex;align-items:center;gap:var(--aside-row-gap);padding:7px var(--aside-pad-x);cursor:pointer;font-size:calc(var(--fs-r,1)*12.5px);color:rgba(var(--rgb-diff),.60);border-radius:var(--r-xs);margin:0 4px;text-decoration:none}
.si i{font-size:calc(var(--fs-r,1)*15px);flex-shrink:0;opacity:.46}
.si:hover{color:rgba(var(--rgb-diff),.85);background:rgba(var(--rgb-diff),.03)}
.si.on,.si.active{color:rgba(var(--rgb-diff),.90);background:rgba(var(--rgb-diff),.04)}
.si.on i,.si.active i{opacity:1}
.cnt{font-family:var(--ff-mono);font-size:calc(var(--fs-r,1)*9px);padding:2px 6px;border-radius:var(--r-pill);background:var(--white-3);color:rgba(var(--rgb-diff),.42)}

/* ── Radar rows ──────────────────────────────────────────────────────────── */
.fs{padding:0 8px 4px}
.fr{display:flex;align-items:center;gap:var(--aside-row-gap);padding:2px var(--aside-pad-x);cursor:pointer;border-radius:var(--r-xs);margin:0 4px;color:rgba(var(--rgb-diff),.60);text-decoration:none}
.fr:hover{color:rgba(var(--rgb-diff),.90);background:rgba(var(--rgb-diff),.03)}
.fr.on{color:rgba(var(--rgb-diff),.90);background:rgba(var(--rgb-diff),.04)}
.fr i{font-size:calc(var(--fs-r,1)*14px);flex-shrink:0;opacity:.55}
.fr .fd{flex-shrink:0;width:2.75em;min-width:2.75em;text-align:left;font-family:var(--ff-mono);font-size:calc(var(--fs-r,1)*8.5px);color:rgba(var(--rgb-diff),.35);white-space:nowrap}
.fr .fn{font-size:calc(var(--fs-r,1)*11.5px);color:inherit;min-width:0;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}
.va{display:block;text-align:right;font-size:calc(var(--fs-r,1)*10px);color:rgba(var(--rgb-diff),.35);cursor:pointer;padding:4px var(--aside-pad-x) 0;font-family:var(--ff-mono);text-transform:uppercase;letter-spacing:.06em;flex-shrink:0;text-decoration:none}
.va:hover{color:rgba(var(--rgb-diff),.90)}
.muted-hover{opacity:.55}
.fr:hover .muted-hover{opacity:.9}
.dv{height:1px;background:rgba(var(--rgb-diff),.06);margin:6px 0}

.ax-aside-close{display:none;position:absolute;top:calc(var(--safe-top,0px) + 10px);right:8px;width:36px;height:36px;border-radius:var(--r-pill);align-items:center;justify-content:center;color:var(--muted);cursor:pointer;font-size:calc(var(--fs-r,1)*18px);z-index:2;border:none;background:transparent}
.ax-aside-close:hover{background:var(--surface);color:var(--txt-heading)}
@media (max-width:1000px){.ax-aside-close{display:flex}}

/* ── Footer: Suporte · Ajustes · user ────────────────────────────────────── */
.ft{padding:8px 8px calc(12px + var(--safe-bottom,0px));flex-shrink:0}
.fni .sn{font-size:inherit;color:inherit}
.fni i,.adjl svg,.adjl i{font-size:calc(var(--fs-r,1)*16px);opacity:.46}
.adj,.fni{padding:4px var(--aside-pad-x);display:flex;align-items:center;gap:var(--aside-row-gap);cursor:pointer;font-size:calc(var(--fs-r,1)*13px);color:rgba(var(--rgb-diff),.60);border-radius:var(--r-sm);border:none;background:none;width:100%;box-sizing:border-box;font-family:inherit;text-align:left}
.fni:hover,.supportBTN:hover,.supportBTN:hover>i,.supportBTN:hover>svg{color:var(--orange-700)!important;background:rgba(var(--rgb-diff),.03)}
.adj:hover{color:rgba(var(--rgb-diff),.85);background:rgba(var(--rgb-diff),.03)}
.adjl{display:flex;align-items:center;gap:var(--aside-row-gap);min-width:0;flex:1 1 auto;overflow:hidden;border:none;background:none;padding:0;margin:0;font:inherit;color:inherit;cursor:pointer;text-align:left;text-decoration:none}
.adjl .sn{font-size:inherit;color:inherit}
.adj .pill{border:none;cursor:pointer;font:inherit;box-shadow:rgba(0,0,0,.12) 0 0 0 1px inset,rgba(0,0,0,.25) 0 1px 0 0 inset}
.adjl i{font-size:calc(var(--fs-r,1)*16px);opacity:.46;flex-shrink:0}
.pill{width:30px;height:16px;border-radius:var(--r-pill);display:flex;align-items:center;padding:2px;flex-shrink:0;background:var(--white-6);transition:background .35s var(--ease)}
.pill.on{background:var(--primary)}
.pill .th{width:12px;height:12px;border-radius:50%;background:var(--bg);transition:transform .35s var(--ease-snappy)}
.pill.on .th{transform:translateX(15px)}
.urow{display:flex;align-items:center;gap:var(--aside-row-gap);padding:10px var(--aside-pad-x);margin-top:4px;cursor:pointer;border-radius:var(--r-sm);text-decoration:none}
.urow:hover{background:rgba(var(--rgb-diff),.03)}
.avw{position:relative;flex-shrink:0}
.av{width:45px;height:45px;border-radius:50%;background:var(--surface-hover);display:flex;align-items:center;justify-content:center;font-size:calc(var(--fs-r,1)*11px);font-weight:600;color:var(--txt-heading);overflow:hidden;position:relative}
.av img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity .3s;box-shadow:none}
.aon{width:8px;height:8px;border-radius:50%;background:#34C759;position:absolute;bottom:-1px;right:-1px}
.ui{flex:1 1 auto;min-width:0;overflow:hidden}
.ui>.un{font-size:calc(var(--fs-r,1)*12.5px);font-weight:500;color:rgba(var(--rgb-diff),.85)}
.ui>.uro{font-size:calc(var(--fs-r,1)*10px);color:rgba(var(--rgb-diff),.35)}
.um i{font-size:calc(var(--fs-r,1)*14px);color:rgba(var(--rgb-diff),.35)}
</style>
<style id="apollo-plus-aside-fixes">
/* Suporte: the row is the target. Children never intercept the pointer, so a
   click on the glyph or the label reaches the button that carries
   [data-apollo-suporte]. */
.supportBTN{width:100%;text-align:left;cursor:pointer}
.supportBTN>i,.supportBTN>svg,.supportBTN>.sn{pointer-events:none}
</style>
<?php
/* ── REMOVED: --fsx token declaration ────────────────────────────────────────
   A previous pass declared --fsx here to repair the Design System's
   calc(Npx * var(--fsx)) sizing. That was WRONG on the project's own rule:
   :root design tokens come from core.js and from nowhere else, on every Apollo
   page. Declaring one in a template — even scoped to .ax-shell/body — forks the
   token system and hides the real defect.

   Verified against production: neither /casa nor /portal defines --fsx, so
   core.js does not ship that token at all. The copied ds-components.css simply
   references a token from the mockup's own :root, which we correctly do not
   port. The fix belongs in the COPY, not in a new declaration: styles-ds.php
   now rewrites var(--fsx) to core.js's real user font-scale token, var(--fs-u),
   as a documented port-time substitution. ── */
?>
