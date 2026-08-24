#!/usr/bin/env node
/**
 * ═══════════════════════════════════════════════════════════════════════
 * /casa VERIFICATION HARNESS
 * ═══════════════════════════════════════════════════════════════════════
 *
 * Sibling of apollo-events/_sandbox/build-portal-harness.mjs, same contract:
 * it READS the real cells (never copies them) and asserts structural
 * invariants that no PHP binary and no headless browser are available to
 * check in this environment.
 *
 *   node apollo-templates/_sandbox/build-casa-harness.mjs
 *
 * ── Assertions ────────────────────────────────────────────────────────
 *  A  every #id / .class the /casa JS queries exists in the markup that
 *     /casa actually prints (guest branch — logged users are 302'd to
 *     /feed by mural-router.php, so the guest tree is the real tree)
 *  B  no selector is declared by two style cells (the cardinal sin)
 *  C  no custom property is consumed without a fallback unless this repo
 *     declares it — core.js is a CDN we cannot verify at build time, so
 *     "undeclared + no fallback" is a hard fail, not a warning
 *  D  x-axis containment: every rule that can be wider than the viewport
 *     must sit inside an ancestor this file also clips
 *  E  each style cell declares its ownership header
 *
 * Exit code 0 = green. Anything else = do not deploy.
 * ═══════════════════════════════════════════════════════════════════════
 */

import { readFileSync, existsSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const PLUGIN = resolve(__dirname, '..');
const ROOT = resolve(PLUGIN, '..');

const R = (p) => resolve(ROOT, p);
const read = (p) => (existsSync(R(p)) ? readFileSync(R(p), 'utf8') : null);

/* ── /casa cell inventory ─────────────────────────────────────────────
   Ownership is declared here, once. If a cell is added to page-home.php
   and not added here, assertion E fails — that is the point. */
const TEMPLATE = 'apollo-templates/templates/page-home.php';
const SHELL = 'apollo-templates/templates/template-parts/app-shell.php';
const PARTS = [
  'hero', 'marquee', 'tracks', 'events', 'classifieds',
  'crash', 'map', 'footer', 'menu-fab', 'auth-lightbox',
].map((n) => `apollo-templates/templates/template-parts/new-home/${n}.php`);

/* Plug-n-play cells. Adding a cell here is the whole registration step —
   the assertions below then hold it to the same contract as every other
   cell: one owner per selector, geometry fallbacks, an ownership header. */
const CELL_DIR = 'apollo-templates/templates/template-parts/new-home/cells/';
const CELLS = ['motion', 'lightbox-event'];

const STYLE_CELLS = [
  { id: 'new-home.css', file: 'apollo-templates/assets/css/new-home.css', kind: 'css' },
  { id: 'page-home.php#apollo-home-styles', file: TEMPLATE, kind: 'inline' },
  ...CELLS.map((c) => ({ id: `cells/${c}.php`, file: `${CELL_DIR}${c}.php`, kind: 'inline' })),
];
/* DS cells — NOT loaded by /casa today (see FINDING S1). Listed so the
   duplicate-selector check can prove the fork. */
const DS_CELLS = [
  { id: 'apollo-plus/topbar-styles.php', file: 'apollo-templates/templates/template-parts/apollo-plus/topbar-styles.php', kind: 'inline' },
  { id: 'apollo-plus/aside-styles.php', file: 'apollo-templates/templates/template-parts/apollo-plus/aside-styles.php', kind: 'inline' },
];

const JS_CELL = 'apollo-templates/assets/js/new-home.js';

const failures = [];
const notes = [];
const fail = (code, msg) => failures.push(`${code}  ${msg}`);
const note = (msg) => notes.push(`    · ${msg}`);

/* ═══ helpers ═══════════════════════════════════════════════════════ */

/** Strip PHP so we can read the markup a guest actually receives. */
function guestMarkup(php) {
  return php
    // `<?php if (! $is_logged) : ?>` / `<?php if (! $is_logged_in) : ?>` → keep body
    .replace(/<\?php\s+if\s*\(\s*!\s*\$is_logged(_in)?\s*\)\s*:\s*\?>/g, '')
    // `<?php if ($ash_logged) : ?> … <?php endif; ?>` → drop body (guest tree)
    .replace(/<\?php\s+if\s*\(\s*\$(ash_logged|is_logged_in|is_logged)\s*\)\s*:\s*\?>[\s\S]*?<\?php\s+end(if|foreach);\s*\?>/g, '')
    .replace(/<\?php[\s\S]*?\?>/g, ' ');
}

/** Pull the contents of every <style> block. */
function inlineCss(php) {
  return [...php.matchAll(/<style[^>]*>([\s\S]*?)<\/style>/g)].map((m) => m[1]).join('\n');
}

/** Very small CSS rule tokenizer: returns [{selector, body, line}].
 *  Comments are blanked (newlines preserved so line numbers stay true) BEFORE
 *  tokenizing — a comment containing a brace, e.g. `#apollo-home{overflow-x:clip}`
 *  quoted inside an explanation, otherwise throws the depth counter off. */
function rules(css) {
  css = css.replace(/\/\*[\s\S]*?\*\//g, (m) => m.replace(/[^\n]/g, ' '));
  const out = [];
  let depth = 0, buf = '', selBuf = '', line = 1, selLine = 1;
  for (let i = 0; i < css.length; i++) {
    const c = css[i];
    if (c === '\n') line++;
    if (c === '{') {
      if (depth === 0) { selLine = line; }
      depth++;
      if (depth === 1) { buf = ''; continue; }
    }
    if (c === '}') {
      depth--;
      if (depth === 0) {
        const sel = selBuf.replace(/\/\*[\s\S]*?\*\//g, '').trim();
        if (sel && !sel.startsWith('@')) out.push({ selector: sel, body: buf, line: selLine });
        else if (sel.startsWith('@media') || sel.startsWith('@supports')) {
          for (const r of rules(buf)) out.push({ ...r, selector: r.selector, media: sel, line: selLine });
        }
        selBuf = ''; buf = '';
        continue;
      }
    }
    if (depth === 0) selBuf += c; else buf += c;
  }
  return out;
}

const decl = (body, prop) => {
  const m = body.match(new RegExp(`(?:^|;|\\{)\\s*${prop}\\s*:\\s*([^;}]+)`, 'i'));
  return m ? m[1].trim() : null;
};

/* ═══ load ══════════════════════════════════════════════════════════ */

const tpl = read(TEMPLATE);
if (!tpl) { console.error(`FATAL  missing ${TEMPLATE}`); process.exit(2); }

const shell = read(SHELL) ?? '';
const js = read(JS_CELL) ?? '';

const markupSources = [
  { id: TEMPLATE, php: tpl },
  { id: SHELL, php: shell },
  ...PARTS.map((p) => ({ id: p, php: read(p) ?? '' })),
  ...CELLS.map((c) => ({ id: `${CELL_DIR}${c}.php`, php: read(`${CELL_DIR}${c}.php`) ?? '' })),
];
const markup = markupSources.map((s) => guestMarkup(s.php)).join('\n');

const loaded = [];
for (const cell of STYLE_CELLS) {
  const src = read(cell.file);
  if (src == null) { fail('E0', `style cell missing: ${cell.file}`); continue; }
  loaded.push({ ...cell, css: cell.kind === 'inline' ? inlineCss(src) : src, raw: src });
}
const dsLoaded = DS_CELLS.map((c) => {
  const src = read(c.file);
  return src == null ? null : { ...c, css: inlineCss(src), raw: src };
}).filter(Boolean);

/* ═══ A — id / class contract ═══════════════════════════════════════ */

const idsInMarkup = new Set([...markup.matchAll(/\bid=["']([^"']+)["']/g)].map((m) => m[1]));
const jsIds = new Set([
  ...[...js.matchAll(/getElementById\(\s*['"]([^'"]+)['"]/g)].map((m) => m[1]),
  ...[...js.matchAll(/querySelector(?:All)?\(\s*['"]#([A-Za-z0-9_-]+)/g)].map((m) => m[1]),
  ...[...tpl.matchAll(/getElementById\(\s*['"]([^'"]+)['"]/g)].map((m) => m[1]),
]);

const orphanIds = [...jsIds].sort().filter((id) => !idsInMarkup.has(id));
if (orphanIds.length) {
  fail('A1', `new-home.js queries ${orphanIds.length} ids that /casa never prints — ${orphanIds.map((i) => '#' + i).join(' ')}\n`
    + `they belong to the legacy nh-navbar, which page-home.php suppresses via APOLLO_NAVBAR_LOADED.\n`
    + `every branch behind them is dead weight shipped to every visitor.`);
}

/* Shell contract ids core.js binds with zero extra JS (CLAUDE.md). */
const CONTRACT = ['burger', 'ax-overlay', 'ic-act', 'ic-apps', 'ic-pf', 'apps-pop'];
for (const id of CONTRACT) {
  if (!idsInMarkup.has(id)) note(`shell contract id #${id} absent from the guest tree (expected for logged-only controls)`);
}

/* ═══ B — one selector, one owner ═══════════════════════════════════ */

const owners = new Map(); // "media||selector" -> [cellId:line]
for (const cell of loaded) {
  for (const r of rules(cell.css)) {
    for (const sel of r.selector.split(',').map((s) => s.trim()).filter(Boolean)) {
      const key = `${r.media ?? ''}||${sel}`;
      if (!owners.has(key)) owners.set(key, []);
      owners.get(key).push(`${cell.id}:${r.line}`);
    }
  }
}
for (const [key, where] of owners) {
  if (where.length > 1) {
    const cells = new Set(where.map((w) => w.split(':')[0]));
    if (cells.size > 1) {
      const [media, sel] = key.split('||');
      fail('B1', `two cells declare \`${sel}\`${media ? ` inside ${media}` : ''} → ${where.join(' , ')}`);
    }
  }
}

/* The DS owns the topbar. Any .ax-* selector /casa re-declares is a fork. */
const dsSelectors = new Set();
for (const cell of dsLoaded) {
  for (const r of rules(cell.css)) {
    for (const s of r.selector.split(',').map((x) => x.trim())) dsSelectors.add(s);
  }
}
const forked = new Set();
for (const cell of loaded) {
  for (const r of rules(cell.css)) {
    for (const s of r.selector.split(',').map((x) => x.trim())) {
      if (dsSelectors.has(s) && /^\.ax-|^\.apps-pop|^\.panel-r/.test(s)) forked.add(`${s}  (${cell.id}:${r.line})`);
    }
  }
}
for (const f of [...forked].sort()) {
  fail('B2', `/casa re-declares a DS-owned selector: ${f} — copy from the DS cell or load it, do not re-tune`);
}

/* ═══ C — GEOMETRY tokens consumed without a fallback ═══════════════
   Tokens legitimately live in core.js (`:root` belongs to core.js), so a
   missing colour token is only ugly. A missing token inside a geometry
   declaration is different in kind: the declaration becomes invalid at
   computed-value time, the property falls back to its INITIAL value, and
   a fixed element with `bottom:auto` lands at its static position — i.e.
   somewhere near the top of the document. That is a misalignment, not a
   recolour, so only geometry is a hard fail. */
const GEOMETRY = /^(top|right|bottom|left|inset|width|height|min-width|max-width|min-height|max-height|margin|margin-top|margin-right|margin-bottom|margin-left|padding|padding-top|padding-right|padding-bottom|padding-left|gap|row-gap|column-gap|transform|translate|flex-basis|grid-template-columns)$/i;

const allCss = [...loaded, ...dsLoaded].map((c) => c.css).join('\n');
const declaredHere = new Set([...allCss.matchAll(/(--[A-Za-z0-9_-]+)\s*:/g)].map((m) => m[1]));

for (const cell of loaded) {
  for (const r of rules(cell.css)) {
    for (const m of r.body.matchAll(/(?:^|;)\s*([a-z-]+)\s*:\s*([^;}]+)/gi)) {
      const [, prop, value] = m;
      if (!GEOMETRY.test(prop.trim())) continue;
      for (const v of value.matchAll(/var\(\s*(--[A-Za-z0-9_-]+)\s*\)/g)) {
        if (declaredHere.has(v[1])) continue;
        fail('C1', `${r.selector} (${cell.id}:${r.line}) — \`${prop.trim()}: ${value.trim()}\`\n`
          + `     var(${v[1]}) has NO fallback and nothing in this repo declares it. If core.js is slow,\n`
          + `     blocked or renamed, this declaration is invalid and ${prop.trim()} reverts to its initial value.\n`
          + `     Fix: var(${v[1]}, 0px) — the same file already writes var(--safe-top, 0px) elsewhere.`);
      }
    }
  }
}

/* A malformed function inside a geometry value is the same failure mode. */
for (const cell of loaded) {
  for (const r of rules(cell.css)) {
    for (const m of r.body.matchAll(/(transform|translate)\s*:\s*([^;}]+)/gi)) {
      const v = m[2];
      const t = v.match(/translate(?:3d|X|Y)?\(([^)]*)\)/i);
      if (!t) continue;
      const args = t[1].split(',').map((a) => a.trim()).filter(Boolean);
      const bad = args.filter((a) => a !== '0' && !/(px|%|r?em|v[wh]|dv[wh]|ch|calc\(|var\()/.test(a));
      if (bad.length) {
        fail('C2', `${r.selector} (${cell.id}:${r.line}) — \`${m[1]}: ${v.trim()}\` has unitless/invalid argument(s) [${bad.join(', ')}]; the whole declaration is dropped`);
      }
    }
  }
}
/* Same check inside @keyframes, which the rule tokenizer skips. */
for (const cell of loaded) {
  for (const kf of cell.css.matchAll(/@keyframes\s+([\w-]+)\s*\{([\s\S]*?)\n\}/g)) {
    for (const t of kf[2].matchAll(/translate(?:3d|X|Y)?\(([^)]*)\)/gi)) {
      const args = t[1].split(',').map((a) => a.trim()).filter(Boolean);
      const bad = args.filter((a) => a !== '0' && !/(px|%|r?em|v[wh]|dv[wh]|ch|calc\(|deg)/.test(a));
      if (bad.length) fail('C2', `@keyframes ${kf[1]} (${cell.id}) — translate(${t[1]}) has unitless/invalid argument(s) [${bad.join(', ')}]; that keyframe is dropped`);
    }
  }
}

/* ═══ D — x-axis containment ════════════════════════════════════════ */

/* Ancestors this page clips horizontally, and at which widths. */
const clipped = new Map(); // selector -> media list
for (const cell of loaded) {
  for (const r of rules(cell.css)) {
    const ox = decl(r.body, 'overflow-x') ?? decl(r.body, 'overflow');
    if (ox && /clip|hidden|auto|scroll/.test(ox)) {
      for (const s of r.selector.split(',').map((x) => x.trim())) {
        if (!clipped.has(s)) clipped.set(s, []);
        clipped.get(s).push(r.media ?? 'all');
      }
    }
  }
}

/* Rules that can render wider than the viewport. */
for (const cell of loaded) {
  for (const r of rules(cell.css)) {
    const w = decl(r.body, 'width');
    const mw = decl(r.body, 'max-width');
    const ml = decl(r.body, 'margin-left');
    const mr = decl(r.body, 'margin-right');
    const sel = r.selector;

    const over100vw = (v) => v && /(\d+(?:\.\d+)?)vw/.test(v) && parseFloat(v.match(/(\d+(?:\.\d+)?)vw/)[1]) > 100;
    if (over100vw(w) || over100vw(mw)) {
      const parent = sel.split(/\s+/)[0];
      const isClipped = [...clipped.keys()].some((c) => c === parent || sel.startsWith(c));
      if (!isClipped) fail('D1', `${sel} (${cell.id}:${r.line}) is > 100vw wide and no ancestor rule in this cell clips it`);
      else note(`${sel} is ${w || mw} — clipped by an ancestor, OK, but vw ignores the scrollbar; prefer 100%`);
    }

    /* Negative bleed must be <= the gutter its container actually has. */
    const neg = (v) => v && /^-\s*(\d+(?:\.\d+)?)px/.test(v.trim());
    if (neg(ml) || neg(mr)) {
      const px = Math.abs(parseFloat((ml || mr).match(/-?\s*(\d+(?:\.\d+)?)px/)[1]));
      note(`${sel} bleeds ${px}px (${cell.id}:${r.line}${r.media ? ` @ ${r.media}` : ''}) — must be <= .container's gutter at every width in that range`);
      if (r.media && /min-width:\s*768px/.test(r.media) && px > 38.4) {
        fail('D2', `${sel} bleeds ${px}px from 768px up, but .container's gutter is clamp(32px,5vw,56px) = 38.4px at 768px → ${(px - 38.4).toFixed(1)}px of horizontal overflow in the 768–${Math.round((px / 0.05))}px band, where nothing clips it`);
      }
    }

    /* absolute/fixed + left:0 + a viewport-sized max-width = right-edge overflow
       unless the element is anchored to the viewport, not to a mid-page parent. */
    const pos = decl(r.body, 'position');
    const left = decl(r.body, 'left');
    if (mw && /calc\([^)]*100vw/.test(mw) && left === '0' && pos !== 'fixed') {
      /* Anchoring to a viewport-relative cap is only correct when the element's
         containing block starts at the container gutter. The page can arrange
         that by resetting the mid-page ancestor to `position: static` in the
         same media context — so look for that before failing. */
      const sameCtx = rules(cell.css).filter((x) => (x.media ?? 'all') === (r.media ?? 'all'));
      const handoff = sameCtx.find((x) => /^static$/i.test((decl(x.body, 'position') ?? '').trim()));
      if (handoff) {
        note(`${sel} is capped against the viewport and ${handoff.selector} is reset to position:static in the same block — containing block handed to the gutter-aligned ancestor, OK`);
      } else {
        fail('D3', `${sel} (${cell.id}:${r.line}) is absolutely positioned at left:0 of a mid-page parent but capped with a VIEWPORT-relative max-width (${mw}); its right edge lands at parentLeft + ${mw}, not at the viewport edge`);
      }
    }
  }
}

/* ═══ E — ownership headers ═════════════════════════════════════════ */

for (const cell of loaded) {
  /* For an inline cell the header that matters is the top of the <style>
     block it contributes, not the top of the PHP file that happens to
     contain it — the file may own several concerns, the cell owns one. */
  const head = (cell.kind === 'inline' ? cell.css : cell.raw).slice(0, 1200);
  if (!/owns|ownership|source of truth|last word/i.test(head)) {
    fail('E1', `${cell.id} has no ownership statement in its header — state what this cell owns`);
  }
}

/* ═══ runnable artefact ═════════════════════════════════════════════ */

const outDir = resolve(PLUGIN, '_sandbox');
mkdirSync(outDir, { recursive: true });
const harness = `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>/casa harness</title>
<style>
/* core.js is a CDN; the harness stubs ONLY the tokens it injects so the real
   cells below can be read at their true cascade position. */
:root{--fs-u:1;--fs-r:1;--safe-top:0px;--safe-bottom:0px;--rgb-diff:19,21,23;--rgb-t:255,255,255;
--rgb-theme:255,255,255;--surface:#fafafa;--card:#fff;--border:#e6e6e6;--border-ultra:#f0f0f0;
--border-light:#ededed;--muted:#8a8a8a;--muted-light:#b4b4b4;--text:#131517;--txt-body:#131517;
--txt-heading:#0a0a0a;--txt-color:#3a3a3a;--txt-color-hover:#111;--black-1:#131517;--black-2:#000;
--white-1:#fff;--white-3:#dcdcdc;--white-15:#d0d0d0;--onyx-700:#1b1b1e;--onyx-800:#111;--onyx-600:#232326;
--platinum-400:#e3e3e3;--primary:#FF6B35;--accent:#FF7A00;--ff-main:system-ui,sans-serif;
--ff-mono:ui-monospace,monospace;--ff-heading:system-ui,sans-serif;--ff-fun:system-ui,sans-serif;
--r:16px;--r-sm:10px;--r-xs:6px;--r-pill:999px;--radius:16px;--fs-xs:.72rem;--fs-h6:1rem;
--ease:cubic-bezier(.16,1,.3,1);--ease-out:cubic-bezier(.22,1,.36,1);--ease-spring:cubic-bezier(.34,1.56,.64,1);
--ease-default:ease;--shadow-lg:0 20px 50px rgba(0,0,0,.12);--a-eve-r:14px;--a-eve-border:#e6e6e6;
--a-eve-shadow:rgba(0,0,0,.06);--a-eve-shadow-hover:rgba(0,0,0,.12);--a-eve-text-primary:#111;
--a-eve-text-sec:#8a8a8a;--accent-lime:#d4ff3f;--gray-1:#5a5a5a;--gray-5:#c8c8c8;--bg:#fff;}
/* ── ruler: paints anything that pokes past the viewport ── */
html{outline:0}
.__probe{position:fixed;left:0;right:0;top:0;height:100%;pointer-events:none;z-index:2147483647;
  box-shadow:inset 3px 0 0 0 #0f0,inset -3px 0 0 0 #0f0}
body.__x .__probe{box-shadow:inset 3px 0 0 0 red,inset -3px 0 0 0 red}
.__hud{position:fixed;bottom:8px;left:8px;z-index:2147483647;font:600 12px/1.4 ui-monospace,monospace;
  background:#111;color:#fff;padding:8px 10px;border-radius:8px;white-space:pre}
</style>
<style>/* ═══ REAL CELL: assets/css/new-home.css ═══ */
${loaded.find((c) => c.id === 'new-home.css')?.css ?? ''}
</style>
<style>/* ═══ REAL CELL: page-home.php #apollo-home-styles ═══ */
${loaded.find((c) => c.id.startsWith('page-home'))?.css ?? ''}
</style>
</head>
<body class="ax-body" data-apollo-page="landing">
<div class="__probe"></div><div class="__hud">measuring…</div>
${guestMarkup(shell)}
<main id="apollo-home">
${PARTS.filter((p) => !/auth-lightbox|menu-fab/.test(p)).map((p) => guestMarkup(read(p) ?? '')).join('\n')}
</main>
${guestMarkup(read('apollo-templates/templates/template-parts/new-home/menu-fab.php') ?? '')}
<script>
/* Reveal everything — GSAP/IO are not present in the harness. */
document.querySelectorAll('.ai,.nh-reveal').forEach(function(e){e.classList.add('is-visible');});
function scan(){
  var vw = document.documentElement.clientWidth, bad = [];
  document.querySelectorAll('body *').forEach(function(el){
    if (el.closest('.__probe,.__hud')) return;
    var r = el.getBoundingClientRect();
    if (r.width === 0 && r.height === 0) return;
    if (r.right > vw + 0.5 || r.left < -0.5) {
      bad.push({ el: el, sel: (el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') +
        (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\\s+/).slice(0,3).join('.') : '')),
        l: Math.round(r.left), r: Math.round(r.right) });
    }
  });
  document.body.classList.toggle('__x', bad.length > 0);
  document.querySelector('.__hud').textContent =
    'viewport ' + vw + 'px  ·  scrollWidth ' + document.documentElement.scrollWidth + 'px\\n' +
    (bad.length ? bad.length + ' element(s) break the x-axis:\\n' +
      bad.slice(0,12).map(function(b){return '  ' + b.sel + '  [' + b.l + ' → ' + b.r + ']';}).join('\\n')
     : 'x-axis clean');
  bad.forEach(function(b){ b.el.style.outline = '2px solid red'; });
  console.table(bad.map(function(b){return {selector:b.sel,left:b.l,right:b.r};}));
}
addEventListener('load', scan); addEventListener('resize', scan); setTimeout(scan, 400);
</script>
</body></html>`;

const outFile = resolve(outDir, 'casa-harness.html');
writeFileSync(outFile, harness, 'utf8');

/* ═══ report ════════════════════════════════════════════════════════ */

const rel = (p) => relative(ROOT, p).replace(/\\/g, '/');
console.log('');
console.log('  /casa harness');
console.log('  ' + '─'.repeat(66));
console.log(`  cells read      ${loaded.length} style · ${markupSources.length} markup · 1 js`);
console.log(`  artefact        ${rel(outFile)}`);
console.log('                  open it, resize to 320 / 360 / 390 / 768 / 800px,');
console.log('                  red rails + the HUD list every x-axis breach.');
console.log('');
if (notes.length) { console.log('  notes'); console.log(notes.join('\n')); console.log(''); }
if (failures.length) {
  console.log(`  ✗ ${failures.length} assertion${failures.length > 1 ? 's' : ''} failed`);
  console.log('  ' + '─'.repeat(66));
  for (const f of failures) console.log('  ' + f.replace(/\n/g, '\n     '));
  console.log('');
  process.exit(1);
}
console.log('  ✓ green');
console.log('');
