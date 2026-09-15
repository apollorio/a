/**
 * apollo-chat — surface-system harness builder + verification gate
 *
 *   node apollo-chat/_sandbox/verify-luxe-surfaces.mjs
 *
 * There is no PHP and no headless browser in this environment, so this reads
 * the real cells (never copies them) and asserts the things that actually
 * broke on this screen. Written 2026-09-12 alongside the "luxe" pass.
 *
 * It also emits `_sandbox/chat-surfaces-harness.html` — the thread view
 * stitched from the shipped stylesheets and from the peer-chip markup lifted
 * out of chat.js, openable in any browser. Verify there FIRST: this folder is
 * mirrored to apollo.rio.br, so a saved file is a deployed file.
 *
 * Assertions
 * ----------
 *  A  every `var(--ac-*)` used anywhere resolves to a token declared in
 *     chat-shell.css :root  — a typo'd token is silent in CSS, the property
 *     just drops and you get the browser default (usually transparent)
 *  B  no property in the OWNED set is declared for the same selector by two
 *     cells (chat-premium.css / chat-shell.css / the inline critical block in
 *     templates/chat.php) — the repo's cardinal sin, and how the pane ended up
 *     with three different whites in three files
 *  C  the inline critical block never hard-codes a surface colour; it may only
 *     reference a token, because it is LAST in the cascade and its !important
 *     beats chat-shell.css at equal specificity
 *  D  every class the peer-chip markup in chat.js emits is styled somewhere
 *  E  THE REGRESSION GATE: the elevated plane and the recessed ground are
 *     separated by a real luminance step, not by a hairline. This is the
 *     defect the pass was opened for ("white on white divided by a border
 *     line") — encoded as a number so it cannot quietly come back.
 */

import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const ROOT = join(HERE, '..');
const read = (p) => readFileSync(join(ROOT, p), 'utf8');

const SHELL = read('assets/css/chat-shell.css');
const PREMIUM = read('assets/css/chat-premium.css');
const TEMPLATE = read('templates/chat.php');
const CHAT_JS = read('assets/js/chat.js');

const CRITICAL = (TEMPLATE.match(
  /<style id="apollo-chat-critical">([\s\S]*?)<\/style>/
) || [, ''])[1];

const failures = [];
const notes = [];
const fail = (a, msg) => failures.push(`${a}  ${msg}`);

/* ── tiny CSS reader ───────────────────────────────────────────────────
   Not a parser. It strips comments and at-rule braces, then walks
   `selector { decls }` pairs. That is all these files are. */
function rules(css) {
  const clean = css.replace(/\/\*[\s\S]*?\*\//g, '');
  const out = [];
  const re = /([^{}]+)\{([^{}]*)\}/g;
  let m;
  while ((m = re.exec(clean)) !== null) {
    const sel = m[1].trim().replace(/\s+/g, ' ');
    if (!sel || sel.startsWith('@')) continue;
    const decls = new Map();
    for (const d of m[2].split(';')) {
      const i = d.indexOf(':');
      if (i < 0) continue;
      decls.set(d.slice(0, i).trim(), d.slice(i + 1).trim());
    }
    for (const one of sel.split(',')) {
      const s = one.trim();
      if (s) out.push({ sel: s, decls });
    }
  }
  return out;
}

const CELLS = {
  'chat-premium.css': rules(PREMIUM),
  'chat-shell.css': rules(SHELL),
  'chat.php <style id="apollo-chat-critical">': rules(CRITICAL),
};

/* ── A: every --ac-* token used is declared ──────────────────────────── */
const declared = new Set();
for (const { sel, decls } of CELLS['chat-shell.css']) {
  if (sel !== ':root') continue;
  for (const prop of decls.keys()) if (prop.startsWith('--ac-')) declared.add(prop);
}
const used = new Set();
for (const src of [SHELL, PREMIUM, CRITICAL]) {
  for (const m of src.matchAll(/var\(\s*(--ac-[\w-]+)/g)) used.add(m[1]);
}
for (const tok of [...used].sort()) {
  if (!declared.has(tok)) fail('A', `var(${tok}) is used but never declared in chat-shell.css :root`);
}
notes.push(`A  ${declared.size} --ac-* tokens declared, ${used.size} referenced, all resolve`);

/* ── B: single ownership of the surface system ───────────────────────── */
const OWNED = {
  '.ac-main': ['background'],
  '.ac-messages': ['background'],
  '.ac-peer-chip': ['background', 'border', 'box-shadow'],
  '.ac-header-name': ['font-size', 'font-weight', 'color'],
  '.ac-header-status': ['font-size', 'color', 'opacity'],
  '.ac-header-status.online': ['color'],
  '.ac-header-avatar': ['width', 'height', 'background'],
  '.ac-compose-form': ['background', 'border', 'box-shadow'],
  '.ac-date-sep': ['padding'],
  '.ac-date-sep span': ['background', 'color', 'font-size'],
  '.sent .ac-bubble': ['background', 'color'],
  '.received .ac-bubble': ['background', 'color'],
  '.ac-sidebar-search input': ['background', 'color', 'box-shadow'],
};
for (const [sel, props] of Object.entries(OWNED)) {
  for (const prop of props) {
    const owners = [];
    for (const [cell, rs] of Object.entries(CELLS)) {
      // Exact selector only: a media-query variant is the same string here, so
      // count each cell once — cross-CELL duplication is what this catches.
      if (rs.some((r) => r.sel === sel && r.decls.has(prop))) owners.push(cell);
    }
    if (owners.length > 1) {
      fail('B', `\`${sel} { ${prop} }\` is declared by ${owners.length} cells: ${owners.join(' + ')}`);
    }
  }
}
notes.push(`B  ${Object.keys(OWNED).length} owned selectors checked, one cell each`);

/* ── C: the last-in-cascade block may not hard-code a surface colour ─── */
for (const { sel, decls } of CELLS['chat.php <style id="apollo-chat-critical">']) {
  const bg = decls.get('background');
  if (!bg) continue;
  if (/#[0-9a-f]{3,8}\b|\brgba?\(/i.test(bg) && !/var\(/.test(bg)) {
    fail('C', `critical block hard-codes \`${sel} { background: ${bg} }\` — it is last in the cascade, so this silently beats chat-shell.css. Reference a token instead.`);
  }
}
notes.push('C  critical block references tokens only');

/* ── D: every class the peer chip emits is styled ────────────────────── */
const chipFn = (CHAT_JS.match(/function renderChatHeader\(\)[\s\S]*?\n    \}/) || [''])[0];
if (!chipFn) fail('D', 'renderChatHeader() not found in chat.js — update this gate');
const emitted = new Set();
for (const m of chipFn.matchAll(/class="([^"$]+)"/g)) {
  for (const c of m[1].split(/\s+/)) if (c && !c.startsWith('ri-')) emitted.add(c);
}
const ALL_CSS = SHELL + PREMIUM + CRITICAL;
for (const cls of [...emitted].sort()) {
  if (!ALL_CSS.includes('.' + cls)) fail('D', `.${cls} is emitted by renderChatHeader() but has no rule in any cell`);
}
notes.push(`D  ${emitted.size} peer-chip classes emitted, all styled`);

/* ── E: the regression gate — ground vs raise must be a real step ────── */
const rootDecls = (CELLS['chat-shell.css'].find((r) => r.sel === ':root') || { decls: new Map() }).decls;

function srgbToLin(c) {
  const s = c / 255;
  return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
}
function luminance([r, g, b]) {
  return 0.2126 * srgbToLin(r) + 0.7152 * srgbToLin(g) + 0.0722 * srgbToLin(b);
}
function resolve(value, depth = 0) {
  if (!value || depth > 4) return null;
  const v = value.trim();
  const varRef = v.match(/^var\(\s*(--[\w-]+)\s*\)$/);
  if (varRef) return resolve(rootDecls.get(varRef[1]), depth + 1);
  const triplet = v.match(/^rgb\(\s*var\(\s*(--[\w-]+)\s*\)\s*\)$/);
  if (triplet) {
    const raw = rootDecls.get(triplet[1]);
    if (!raw) return null;
    const n = raw.split(',').map((x) => parseInt(x.trim(), 10));
    return n.length === 3 && n.every(Number.isFinite) ? n : null;
  }
  const hex = v.match(/^#([0-9a-f]{6}|[0-9a-f]{3})$/i);
  if (hex) {
    const h = hex[1].length === 3 ? hex[1].replace(/./g, '$&$&') : hex[1];
    return [0, 2, 4].map((i) => parseInt(h.slice(i, i + 2), 16));
  }
  const rgb = v.match(/^rgba?\(([^)]+)\)$/i);
  if (rgb) {
    const n = rgb[1].split(',').slice(0, 3).map((x) => parseInt(x.trim(), 10));
    return n.length === 3 && n.every(Number.isFinite) ? n : null;
  }
  return null;
}

const ground = resolve(rootDecls.get('--ac-ground'));
const raise = resolve(rootDecls.get('--ac-raise'));

if (!ground || !raise) {
  fail('E', '--ac-ground / --ac-raise could not be resolved to colours — update this gate');
} else {
  /* Contrast ratio, WCAG formula. Two planes that a person can tell apart
     without a border need roughly 1.10; the old pane/chip pair was #fff on
     #fff = 1.00 (the chip was 92% white, so ~1.00 either way) and the whole
     separation rested on a 1px rgba(29,29,31,.08) line.
     1.08 is deliberately below the 1.12 this pass ships so a legitimate
     re-tune has room — it only fires when the step has effectively gone. */
  const lo = Math.min(luminance(ground), luminance(raise));
  const hi = Math.max(luminance(ground), luminance(raise));
  const ratio = (hi + 0.05) / (lo + 0.05);
  const MIN = 1.08;
  const shown = ratio.toFixed(3);
  if (ratio < MIN) {
    fail('E', `--ac-ground vs --ac-raise contrast is ${shown}:1, below the ${MIN}:1 floor. The conversation pane and the chrome floating on it are the same plane again — a hairline is not hierarchy. See the header note in chat-shell.css.`);
  } else {
    notes.push(`E  ground/raise contrast ${shown}:1 (floor ${MIN}:1)`);
  }
}

/* ── harness ──────────────────────────────────────────────────────────
   The chip markup is LIFTED from chat.js, not retyped, so this page cannot
   drift from what ships. Every `${…}` in that template literal is resolved
   through the table below; an expression that is not in the table is a hard
   error rather than a silent blank, which is what makes the harness trustworthy
   after someone edits renderChatHeader(). */
const SAMPLES = {
  'esc(name)': 'roots roots',
  'statusClass': '',
  'statusHTML':
    '<span class="ac-header-handle">@roots</span>' +
    '<span class="ac-header-sep" aria-hidden="true">·</span>Offline',
  "avatar ? `<img src=\"${esc(avatar)}\" alt=\"\">` : `<span class=\"ac-avatar-fallback\"><i class=\"ri-user-3-line\"></i></span>`":
    '<span class="ac-avatar-fallback"><i class="ri-user-3-line"></i></span>',
  "isOnline ? '<span class=\"ac-online-dot\"></span>' : ''": '',
  "isGroup ? '<button class=\"ac-icon-btn\" data-action=\"members\" title=\"Membros\" type=\"button\" aria-label=\"Membros\"><i class=\"ri-group-line\"></i></button>' : ''":
    '',
};

const chipTemplate = (chipFn.match(/header\.innerHTML = `([\s\S]*?)`;/) || [, ''])[1];
if (!chipTemplate) fail('D', 'could not lift the peer-chip template literal out of renderChatHeader()');

/* Brace-balanced scan, not a regex: the avatar expression contains a nested
   template literal with its own `${…}`, which any non-greedy regex splits in
   the wrong place. */
function fillTemplate(tpl) {
  let out = '';
  for (let i = 0; i < tpl.length; ) {
    if (tpl[i] === '$' && tpl[i + 1] === '{') {
      let depth = 1;
      let j = i + 2;
      for (; j < tpl.length && depth > 0; j++) {
        if (tpl[j] === '{') depth++;
        else if (tpl[j] === '}') depth--;
      }
      const key = tpl.slice(i + 2, j - 1).trim();
      if (key in SAMPLES) out += SAMPLES[key];
      else fail('D', `harness has no sample for \`\${${key}}\` in the peer-chip markup — add one to SAMPLES in this file`);
      i = j;
    } else {
      out += tpl[i];
      i++;
    }
  }
  return out;
}

const chipHTML = chipTemplate ? fillTemplate(chipTemplate) : '';

const bubble = (side, text, time) =>
  `<div class="ac-msg-row ${side}"><div class="ac-bubble">${text}<span class="ac-msg-time">${time}</span></div></div>`;

writeFileSync(
  join(HERE, 'chat-surfaces-harness.html'),
  `<!DOCTYPE html>
<html lang="pt-BR" class="apollo-chat-page">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>apollo-chat · surfaces harness</title>
<!-- GENERATED by _sandbox/verify-luxe-surfaces.mjs — do not edit. Re-run the script. -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
<style>${PREMIUM}</style>
<style>${SHELL}</style>
<style>${CRITICAL}</style>
<style>
  /* Harness only — stands in for core.js tokens and for .is-open, which the
     real page gets from chat.js. Nothing here is part of the product. */
  :root { --txt-rgb: 29, 29, 31; --primary: #ff9820; --ff-main: "Space Grotesk", system-ui, sans-serif; }
  .ac-main { display: flex !important; }
  .ac-sidebar { display: none; }
  @media (min-width: 768px) { .ac-sidebar { display: flex; } }
</style>
</head>
<body class="apollo-chat-page">
<div class="apollo-chat-wrap"><div class="ac-layout">
  <div class="ac-sidebar">
    <div class="ac-sidebar-header">
      <div class="ac-header-top">
        <h2 class="ac-header-title">Bate-Papo<span class="dim" style="opacity:.5">::rio</span></h2>
        <button class="ac-icon-btn ac-btn-new" type="button"><i class="ri-chat-new-line"></i></button>
      </div>
      <div class="ac-sidebar-search">
        <i class="ri-search-line ac-search-icon"></i>
        <input type="text" placeholder="Buscar conversas..." class="apollo-input">
      </div>
    </div>
    <div class="ac-thread-list">
      <div class="ac-thread active"><div class="ac-thread-avatar"></div><div><div class="ac-thread-name">roots roots</div><div class="ac-thread-preview">Ontem à noite deu certo</div></div></div>
      <div class="ac-thread"><div class="ac-thread-avatar"></div><div><div class="ac-thread-name">Marina</div><div class="ac-thread-preview"><strong>Marina:</strong> fechado</div></div></div>
    </div>
  </div>
  <div class="ac-main is-open">
    <div class="ac-chat-header">${chipHTML}</div>
    <div class="ac-messages">
      <div class="ac-date-sep"><span>Ontem</span></div>
      ${bubble('received', 'Fala! Tudo certo pro sábado?', '21:04')}
      ${bubble('sent', 'Tudo. Chego lá umas 22h, levo os cabos.', '21:06')}
      ${bubble('received', 'Perfeito. O line-up fecha 23h.', '21:07')}
      <div class="ac-date-sep"><span>Hoje</span></div>
      ${bubble('sent', 'Cheguei.', '09:12')}
    </div>
    <div class="ac-compose">
      <div class="ac-compose-form">
        <div class="ac-compose-center">
          <textarea class="apollo-textarea ac-compose-input" placeholder="Escreva uma mensagem…" rows="1"></textarea>
          <span class="ac-emoji-trigger"><i class="ri-emotion-happy-line"></i></span>
        </div>
        <button class="ac-send-btn" type="button"><i class="ri-send-plane-2-fill"></i></button>
      </div>
    </div>
  </div>
</div></div>
</body>
</html>
`,
  'utf8'
);
notes.push('H  _sandbox/chat-surfaces-harness.html written (open it in a browser)');

/* ── report ──────────────────────────────────────────────────────────── */
for (const n of notes) console.log('  ok   ' + n);
if (failures.length) {
  console.error('\n' + failures.length + ' FAILURE(S):');
  for (const f of failures) console.error('  FAIL ' + f);
  process.exit(1);
}
console.log('\nAll assertions passed.');
