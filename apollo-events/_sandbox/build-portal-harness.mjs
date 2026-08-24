/**
 * Portal de Eventos — sandbox harness builder + structural verifier.
 *
 * WHY: the portal is 100% client-rendered inside the Blank Canvas Apollo+ shell,
 * so its layout can only be judged with the shell CSS present. This script
 * stitches the real cells — no copies, it READS the shipped files — into one
 * standalone HTML that opens in any browser, and then runs the assertions that
 * do not need a layout engine:
 *
 *   A · JS syntax of every emitted <script>
 *   B · tag balance of skeleton() and of the hero fallback
 *   C · DOM contract — every id/class the JS queries exists in the markup
 *   D · single-owner — no selector declared by two style cells (the exact bug
 *       class that let `.pev-chrome{position:absolute}` print the month label
 *       on top of the hero)
 *
 * Run:  node _sandbox/build-portal-harness.mjs
 * Out:  _sandbox/portal-harness.html  +  a PASS/FAIL report on stdout
 */

import { readFileSync, writeFileSync, mkdirSync, rmSync, existsSync, readdirSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const HERE = dirname(fileURLToPath(import.meta.url));
const PLUGIN = join(HERE, '..');
const PORTAL = join(PLUGIN, 'styles/base/template-parts/archive/portal');
const TEMPLATES = join(PLUGIN, '../apollo-templates');
const SHELL = join(TEMPLATES, 'templates/template-parts/apollo-plus');

const RENDER_SINGLE = join(PLUGIN, 'includes/render-single.php');

/* ── Reveal selector contract (P3, 2026-08-07) ───────────────────────────────
   The list of "elements that animate in and must never strand invisible" used
   to be hand-copied into apollo-single-event.js, portal/bootstrap.php and
   portal/styles-lightbox.php. PHP now owns it. The harness parses the constants
   straight out of render-single.php so these assertions verify the SHIPPED
   contract, not a fourth copy of the same literal. ── */
const phpArray = (name) => {
  const src = readFileSync(RENDER_SINGLE, 'utf8');
  const at = src.indexOf(`'${name}',`);
  if (at < 0) throw new Error(`${name} not defined in includes/render-single.php`);
  const open = src.indexOf('array(', at);
  const close = src.indexOf(')', open);
  return [...src.slice(open, close).matchAll(/'([^']+)'/g)].map((m) => m[1]);
};

const REVEAL_ANIM = phpArray('APOLLO_EVENT_REVEAL_ANIM');
const REVEAL_FLOOR = ['.ev-reveal', ...REVEAL_ANIM, '.ev-dj', '[data-reveal-word]'];

/** Resolve the PHP expressions a style cell emits, so the CSS can be asserted. */
const resolvePhp = (css) =>
  css.replace(
    /<\?php[\s\S]*?apollo_event_reveal_css_selector\(\s*'([^']*)'[\s\S]*?\?>/g,
    (_, scope) => REVEAL_FLOOR.map((s) => scope + s).join(',\n')
  );

/** Strip the PHP guard preamble/epilogue, keep only the emitted markup. */
const emit = (file) => {
  const raw = readFileSync(file, 'utf8');
  const out = [];
  const re = /<(style|script)\b[^>]*>[\s\S]*?<\/\1>/g;
  let m;
  while ((m = re.exec(raw)) !== null) out.push(m[0]);
  if (!out.length) throw new Error(`no <style>/<script> emitted by ${file}`);
  return resolvePhp(out.join('\n'));
};

/* Mirrors the loop in portal/styles.php, in order. `chrome` and `masthead` left
   with the header they styled (1.7.1); `rhythm` is what survived of masthead —
   the `--pev-*` scale — and is still the LAST-WORD cell for band geometry. */
/* 'lightbox' added 2026-08-20 (plan-003 · P5-5). styles.php loads ELEVEN cells;
   this array carried TEN, so styles-lightbox.php — 450 lines — sat outside the
   D-series duplicate-selector audit that exists to catch exactly the cardinal
   sin. It loads AFTER rhythm, so it goes last here too. */
const CELLS = ['base', 'btn-force', 'ds', 'browse', 'hero', 'rails', 'browse-mini', 'modals', 'responsive', 'rhythm', 'lightbox'];
const LAST_WORD_CELL = 'rhythm';

const portalCss = CELLS.map((s) => emit(join(PORTAL, `styles-${s}.php`))).join('\n');
const shellCss = [emit(join(SHELL, 'topbar-styles.php')), emit(join(SHELL, 'aside-styles.php'))].join('\n');
const helpersJs = emit(join(PORTAL, 'helpers.php'));
const appJs = emit(join(PORTAL, 'app.php'));

/* Shell-fit block lives inline at the bottom of styles.php, after the loop. */
const shellFit = (readFileSync(join(PORTAL, 'styles.php'), 'utf8').match(/<style id="apollo-pev-shell-fit">[\s\S]*?<\/style>/) || [''])[0];

/* ── Token stub ─────────────────────────────────────────────────────────────
   core.js owns :root in production and is not reachable from this sandbox, so
   the harness declares a STUB with the same token NAMES. Colours are indicative
   only — this harness proves GEOMETRY (does anything overlap? does the rail
   reserve its height? do the fallback layers stack in order?), not brand
   colour. Values follow apollo-hub/assets/css/home/tokens.css where it and the
   portal share a name. ── */
const tokenStub = `<style id="sandbox-token-stub">
:root{
  --rgb-theme:255,255,255; --rgb-diff:10,10,10; --rgb-accent:255,107,53;
  --bg:#fff; --card:#fff; --surface:#f7f6f4; --surface-hover:#efeeea;
  --txt-color:#2b2b2b; --txt-heading:#0a0a0a; --muted:#8a8a8a;
  --muted-txt:#8a8a8a; --muted-bg:#e8e8e8;
  --accent:#ff6b35; --primary:#ff6b35; --alert-red:#ff6b6b;
  --white-1:#fff; --white-2:rgba(10,10,10,.02); --white-3:rgba(10,10,10,.04);
  --white-6:rgba(10,10,10,.10); --white-7:rgba(10,10,10,.12); --white-13:rgba(10,10,10,.24);
  --gray-18:#5a5a5a; --black-1:#121214; --orange-700:#e2551f;
  --ff-main:"Space Grotesk",system-ui,sans-serif; --ff-heading:"Space Grotesk",system-ui,sans-serif;
  --ff-mono:"Space Mono",ui-monospace,monospace;
  --fs-h1:clamp(2.15rem,8.5vw,3.1rem); --fs-h6:1rem; --fs-body-sm:.875rem;
  --fs-r:1; --fs-u:1;
  --r:16px; --r-lg:20px; --r-sm:12px; --r-xs:8px; --r-pill:999px;
  --ease:cubic-bezier(.4,0,.2,1); --ease-snappy:cubic-bezier(.34,1.16,.64,1); --ease-smooth:cubic-bezier(.16,1,.3,1);
  --safe-top:0px; --safe-bottom:0px;
  --card-hover:#f2f1ee;
}
html,body{margin:0}
body{background:var(--bg);font-family:var(--ff-main);-webkit-font-smoothing:antialiased}
/* Sandbox-only ruler: paints the header band so overlap is visible by eye. */
body.show-rail .alh{outline:1px dashed rgba(255,0,128,.55);outline-offset:-1px}
body.show-rail .pev-hero{outline:1px dashed rgba(0,140,255,.55);outline-offset:-1px}
</style>`;

/* ── Button-layer stub ───────────────────────────────────────────────────────
   Mirrors styles-btn-force.php (portal's forced match after core.js). The
   sandbox still needs a pre-portal reset so UA button borders don't leak;
   --fsx is aliased like production force cell so the 11px exception resolves.
   ── */
const btnStub = `<style id="sandbox-btn-stub">
button, .btn { border: none; background: none; cursor: pointer; font-family: inherit; }
.pev, .ax-main { --fsx: var(--fs-u, 1); }
.btn { corner-shape: squircle; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 7px 14px; border-radius: var(--r-pill); font-family: var(--ff-main); font-size: var(--fs-body-sm); font-weight: 500; color: var(--txt-heading); background: var(--surface); cursor: pointer; position: relative; overflow: hidden; white-space: nowrap; }
.btn-secondary { corner-shape: squircle; background: var(--card); color: var(--txt-heading); border: 1px solid rgba(var(--rgb-diff), .04); box-shadow: inset 0 1px 0 0 rgba(var(--rgb-theme), .2), inset 0 0 0 1px rgba(var(--rgb-theme), .075); }
.btn:active { transform: scale(.97); }
.btn-secondary:hover { background: var(--card-hover); transform: translateY(-1px); }
.ax-body .btn, .ax-ff-section .btn { font-family: var(--ff-main) !important; }
.ax-main .btn { font-size: calc(var(--fsx) * 11px) !important; font-weight: 400 !important; }
.btn.btn { background: var(--surface); }
.btn.btn-secondary { background: var(--card); border: 1px solid rgba(var(--rgb-diff), .04); }
.btn.btn-secondary:hover { background: var(--card-hover); }
</style>`;

/* No highlight:true anywhere → forces the FINAL fallback layer under test.
   The current-month rows are dated against the REAL clock, because todayStart()
   reads it: a hard-coded August would silently stop exercising the today-window
   rails the moment this repo is opened in September. `d(n)` = today + n days.

   The three December rows are the NON-CURRENT month fixture (M1–M2 below). They
   are deliberately written out of order — 24, 05, 31 — so `byMonth()`'s ascending
   sort is proven by the assertion instead of accidentally satisfied by the way
   the array happens to be typed. */
const iso = (n) => {
  const t = new Date();
  return new Date(t.getFullYear(), t.getMonth(), t.getDate() + n).toISOString().slice(0, 10);
};
const OTHER_YM = `${new Date().getFullYear()}-12`;

const FIXTURE = [
  { id: 'e1', title: 'Dismantle 2', startDate: iso(2), startTime: '23:00', season: 'winter', genres: ['techno'], status: 'active', tickets: 'available', cover: '', venue: { name: 'Fundição Progresso' } },
  { id: 'e2', title: 'Noite Carioca', startDate: iso(12), startTime: '22:00', season: 'winter', genres: ['house', 'disco'], status: 'active', tickets: 'free', cover: '', venue: { name: 'Circo Voador' } },
  { id: 'e3', title: 'Subsolo', startDate: iso(19), startTime: '23:30', season: 'winter', genres: ['techno'], status: 'active', tickets: 'soldout_soon', cover: '', venue: { name: 'Lapa 40' } },
  { id: 'd1', title: 'Réveillon Aterro', startDate: `${OTHER_YM}-24`, startTime: '22:00', season: 'summer', genres: ['house'], status: 'active', tickets: 'available', cover: '', venue: { name: 'Aterro do Flamengo' } },
  { id: 'd2', title: 'Solstício', startDate: `${OTHER_YM}-05`, startTime: '23:00', season: 'summer', genres: ['techno'], status: 'active', tickets: 'free', cover: '', venue: { name: 'Pedra do Sal' } },
  { id: 'd3', title: 'Virada', startDate: `${OTHER_YM}-31`, startTime: '21:00', season: 'summer', genres: ['disco'], status: 'active', tickets: 'available', cover: '', venue: { name: 'Copacabana' } },
];

/* ── The listing header (1.7.1) ─────────────────────────────────────────────
   Since the masthead was retired, the header is PHP that runs before the
   portal runtime — so, unlike every other cell here, it cannot be harvested by
   reading a file. render-portal-header.php executes the shipped code path
   (portal/header.php → apollo_listing_header() → the apollo-templates parts)
   under a thin WP shim and prints what the browser would receive.

   Shelling out to `php` is the price of not keeping a snapshot copy of the
   header markup in this file — a copy would be the exact second owner this
   harness exists to forbid. When php is unreachable the harness still builds
   and still reports; it just says so, and the header assertions register as
   skipped rather than silently passing. */
const renderHeader = () => {
  /* The fixture is scratch, not an artifact: it goes to the OS temp dir, never
     into _sandbox/. Some checkouts of this folder are mounted read-delete-only
     (RealTimeSync mirrors), and a writable-but-not-unlinkable directory made the
     whole harness die on the `rmSync` in the finally block — a green suite
     turned into a stack trace by its own cleanup. */
  const fixtureFile = join(tmpdir(), 'apollo-portal-harness-fixture.json');
  writeFileSync(fixtureFile, JSON.stringify(FIXTURE), 'utf8');
  try {
    const r = spawnSync('php', [join(HERE, 'render-portal-header.php'), fixtureFile], {
      encoding: 'utf8',
      maxBuffer: 32 * 1024 * 1024
    });
    if (r.error) return { ok: false, html: '', why: `php not runnable: ${r.error.message}` };
    if (r.status !== 0) return { ok: false, html: '', why: (r.stderr || '').trim().split('\n')[0] || `exit ${r.status}` };
    return { ok: true, html: r.stdout, why: '' };
  } finally {
    rmSync(fixtureFile, { force: true });
  }
};

const header = renderHeader();

const html = `<!DOCTYPE html>
<html lang="pt-BR" data-theme="light" class="ax-body">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>SANDBOX · Portal de Eventos — listing header + hero fallback</title>
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
${tokenStub}
${btnStub}
${shellCss}
${portalCss}
${shellFit}
</head>
<body class="ax-shell show-rail">
<div class="ax-top-blur" aria-hidden="true"></div>
<header class="ax-top" role="banner">
  <div class="ax-top-l">
    <button class="ax-burger" id="burger" aria-label="Menu"><i class="ri-menu-line"></i></button>
    <a class="ax-top-brand" href="#"><span class="ax-top-wm">apollo<b>::rio</b></span></a>
  </div>
  <div class="ax-top-r">
    <button class="ax-ic" id="ic-apps" aria-label="Apps"><i class="ri-apps-2-line"></i></button>
    <button class="ax-avb" id="ic-pf" aria-label="Perfil"><span class="ax-avb-init">VA</span></button>
  </div>
</header>
<aside class="ax-aside" id="ax-aside"><div class="s"><div class="hd"><span class="wm">apollo<b>::rio</b></span></div></div></aside>
<div class="ax-overlay" id="ax-overlay" aria-hidden="true"></div>

<main class="ax-main" id="axMain" data-screen="eventos">
${header.ok ? header.html : `<!-- listing header NOT rendered: ${header.why} -->`}
  <div id="apollo-portal-root" class="pev-host" data-today="2026-08-01"></div>
</main>

<script>window.APOLLO_EVENTS = ${JSON.stringify(FIXTURE, null, 2)};</script>
${helpersJs}
${appJs}
<script>
document.addEventListener('DOMContentLoaded', function () {
  var host = document.getElementById('apollo-portal-root');
  if (host && window.AppPortalEventos) window.AppPortalEventos.init(host);
});
</script>
</body>
</html>`;

mkdirSync(HERE, { recursive: true });
writeFileSync(join(HERE, 'portal-harness.html'), html, 'utf8');

/* ═══════════════════════════════ ASSERTIONS ═══════════════════════════════ */
const results = [];

/* A check may SKIP. Skipping is for a missing PRECONDITION OF THE ENVIRONMENT —
   no php binary, no network — never for a condition of the code. A skipped check
   is loud (printed SKIP, counted separately) but does not fail the suite, so
   "red" keeps meaning "a real defect". Added 2026-08-20, plan-003 · P6.
   Anything that is a property of the source must FAIL, never skip. */
class Skip extends Error { constructor(why) { super(why); this.skip = true; } }
const check = (name, fn) => {
  try { const d = fn(); results.push({ ok: true, name, detail: d || '' }); }
  catch (e) {
    if (e && e.skip) { results.push({ ok: false, skip: true, name, detail: e.message }); }
    else { results.push({ ok: false, name, detail: e.message }); }
  }
};

const stripTags = (s) => s.replace(/^<(style|script)\b[^>]*>/, '').replace(/<\/(style|script)>$/, '');
const appSrc = stripTags(appJs);
const helpersSrc = stripTags(helpersJs);

/* A · JS syntax — a broken portal renders NOTHING, so this gates everything. */
check('A · app.php JS parses', () => { new Function(appSrc); return `${appSrc.length} chars`; });
check('A · helpers.php JS parses', () => { new Function(helpersSrc); return `${helpersSrc.length} chars`; });

/* B · tag balance of the two strings the JS builds by concatenation. */
const balance = (frag) => {
  const VOID = new Set(['img', 'br', 'hr', 'input', 'meta', 'link', 'source']);
  const stack = [];
  const re = /<(\/?)([a-zA-Z][\w-]*)\b[^>]*?(\/?)>/g;
  let m;
  while ((m = re.exec(frag)) !== null) {
    const [, close, tag, self] = m;
    if (VOID.has(tag.toLowerCase()) || self) continue;
    if (close) {
      const top = stack.pop();
      if (top !== tag) throw new Error(`</${tag}> closes <${top || 'nothing'}>`);
    } else stack.push(tag);
  }
  if (stack.length) throw new Error(`unclosed: <${stack.join('>, <')}>`);
  return 'balanced';
};

/* Evaluate the two builders in isolation by extracting their return strings. */
const grab = (fnName) => {
  const i = appSrc.indexOf(`function ${fnName}()`);
  if (i < 0) throw new Error(`${fnName}() not found`);
  let depth = 0, started = false, j = i;
  for (; j < appSrc.length; j++) {
    if (appSrc[j] === '{') { depth++; started = true; }
    else if (appSrc[j] === '}') { depth--; if (started && depth === 0) { j++; break; } }
  }
  const body = appSrc.slice(i, j);
  /* Hoist the module-scope `var NAME = '…';` constants the builder closes over. */
  const consts = [...appSrc.matchAll(/^\s*var\s+([A-Z][A-Z0-9_]*)\s*=\s*('[^']*'|"[^"]*"|-?\d+);/gm)]
    .map((m) => `var ${m[1]} = ${m[2]};`).join('\n');
  /* Stubs for the sibling helpers a builder calls. `root` is a live DOM node in
     the browser; here the harness only needs the string shape, so the fallback
     src resolves to the proxy URL the template would have printed. */
  const stubs = `
    var root = { getAttribute: function () { return '/wp-json/apollo/v1/iframe/highlighted-fallback'; } };
    function esc(s){ return String(s == null ? '' : s).replace(/[&<>"]/g, function(c){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c]; }); }
    function heroFallbackSrc(){ return root.getAttribute('data-iframe-fallback') || ''; }
    function ticketChip(){ return ''; }
    function dateShort(){ return ''; }
  `;
  // eslint-disable-next-line no-new-func
  return new Function(`${stubs}\n${consts}\n${body}; return ${fnName}();`)();
};

/** Source with comments stripped — so a check never matches its own docblock. */
const stripComments = (s) => s.replace(/\/\*[\s\S]*?\*\//g, '').replace(/^\s*\/\/.*$/gm, '');

check('B · skeleton() markup balanced', () => balance(grab('skeleton')));
check('B · heroFallbackHTML() balanced', () => balance(grab('heroFallbackHTML')));

/* C · DOM contract — every id the JS reaches for must exist in the skeleton. */
const skel = grab('skeleton');
check('C · every #id queried by app.php exists in skeleton()', () => {
  const wanted = [...appSrc.matchAll(/querySelector\((['"])#([\w-]+)\1\)/g)].map((m) => m[2]);
  const present = new Set([...skel.matchAll(/\bid="([\w-]+)"/g)].map((m) => m[1]));
  const missing = [...new Set(wanted)].filter((id) => !present.has(id));
  if (missing.length) throw new Error(`missing: #${missing.join(', #')}`);
  return `${new Set(wanted).size} ids resolved`;
});

/* ── HEADER, 1.7.1 ──────────────────────────────────────────────────────────
   The month band left skeleton() and became PHP: apollo_listing_header() in
   apollo-templates, wired by portal/header.php + portal/header-bridge.php.
   These four replace the old "masthead contract present" check. ── */

check('H1 · skeleton() no longer builds a header', () => {
  for (const dead of ['pev-masthead', 'pev-chrome', 'pev-month-row', 'pev-filter-toggle']) {
    if (skel.includes(dead)) throw new Error(`skeleton() still emits .${dead} — two headers would stack`);
  }
  /* The header is a SIBLING of #apollo-portal-root, never a child: skeleton()
     overwrites that div's innerHTML on every mount, so anything inside it
     cannot survive — nor exist in the first paint. */
  const tpl = readFileSync(join(PLUGIN, 'styles/base/archive-event.php'), 'utf8');
  const headerAt = tpl.indexOf("portal/header.php");
  const rootAt = tpl.indexOf('id="apollo-portal-root"');
  if (headerAt < 0) throw new Error('archive-event.php never includes portal/header.php');
  if (rootAt < 0 || headerAt > rootAt) throw new Error('header is not rendered before #apollo-portal-root');
  if (!tpl.includes('portal/header-bridge.php')) throw new Error('archive-event.php never includes the bridge');
  if (tpl.indexOf('portal/header-bridge.php') < tpl.indexOf('portal/scripts.php')) {
    throw new Error('bridge is included before scripts.php — it would bind before the app exists');
  }
  return 'header before root, bridge after scripts';
});

check('H2 · the shipped header renders', () => {
  /* No php on PATH is an environment gap, not a defect. A php that RAN and
     failed is a defect and still fails below. */
  if (!header.ok && /php not runnable/.test(header.why)) throw new Skip(header.why + ' — install php to run H2/H3');
  if (!header.ok) throw new Error(header.why);
  for (const hook of ['data-alh-root', 'data-alh-scrub', 'data-alh-month', 'data-alh-search-overlay', 'data-alh-filter-overlay']) {
    if (!header.html.includes(hook)) throw new Error(`rendered header has no [${hook}]`);
  }
  /* Server-rendered, not JS-appended: twelve real <button>s in the first paint
     is the whole reason the block is PHP. */
  const segs = (header.html.match(/data-alh-seg="/g) || []).length;
  if (segs !== 12) throw new Error(`${segs} scrubber segments in the HTML, expected 12`);
  if (!/<i class="ri-[a-z-]+"/.test(header.html)) throw new Error('icons are not bare ri-* elements for the core.js runtime');
  if (/<svg/.test(header.html)) throw new Error('hand-written SVG in the markup — the icon runtime owns that');
  return `${segs} segments, both overlays, icons deferred to core.js`;
});

check('H3 · header JS parses and never invents data', () => {
  if (!header.ok && /php not runnable/.test(header.why)) throw new Skip(header.why + ' — depends on H2');
  const scripts = [...header.html.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)].map((m) => m[1]);
  if (scripts.length < 3) throw new Error(`${scripts.length} script blocks; expected runtime + boot + bridge`);
  scripts.forEach((s, i) => { try { new Function(s); } catch (e) { throw new Error(`script ${i + 1}: ${e.message}`); } });

  /* Registry 03-apollo-rule data_flow: the lab built its chips from a
     hard-coded APOLLO_TAXONOMY literal in the page's own script. Porting that
     would have made the DOM the source of truth. */
  const kernel = readFileSync(join(TEMPLATES, 'templates/template-parts/listing-header/kernel-scripts.php'), 'utf8');
  if (/APOLLO_TAXONOMY|buildChipsHTML/.test(stripComments(kernel))) {
    throw new Error('the lab taxonomy literal / chip builder was ported into the kernel');
  }
  return `${scripts.length} blocks parse; chips come from PHP`;
});

check('H4 · payloads printed into <script> are HEX-escaped', () => {
  /* Month names come from date_i18n() and filter options from editor-authored
     terms — author-influenced text inside a <script>. Plain wp_json_encode()
     leaves `</script>` intact and ends the block. */
  for (const rel of [
    join(TEMPLATES, 'templates/template-parts/listing-header/boot.php'),
    join(PORTAL, 'header.php')
  ]) {
    const src = readFileSync(rel, 'utf8');
    if (!src.includes('apollo_json_for_script')) throw new Error(`${rel.split(/[\\/]/).pop()} does not use apollo_json_for_script()`);
    /* The fallback for a missing apollo-core must carry the flags itself. */
    const bare = /wp_json_encode\(\s*\$\w+\s*\)/.test(src);
    if (bare) throw new Error(`${rel.split(/[\\/]/).pop()} still has a flagless wp_json_encode() into a script block`);
  }
  return 'HEX_TAG|HEX_AMP|HEX_APOS|HEX_QUOT on both payloads';
});

/* ── H5/H6 · the apple skin's approved LAYOUT and TYPE contract ─────────────
   Three of these describe pixels no assertion in this file can measure — there
   is no layout engine here — so they assert the DECLARATION that makes the
   pixel rather than the pixel itself. That is enough, because every one of the
   bugs they lock was a wrong declaration and not a wrong number:

     · `.alh__seg` is a <span> and was styled `height:3px` with no `display`.
       Height does not apply to an inline box, so all twelve bars computed to
       zero: present in the DOM, correct in every other assertion here, and not
       on the screen.
     · the selected segment grew with `scaleY`, which reads as a chart. Twelve
       bars of one height with one of them in --accent reads as a rail. The
       approved contract is COLOUR ONLY, so a transform on `.is-active` is a
       regression even though it looks like a flourish.
     · a revision shrank the month to ~1.5rem to fit one row. The display type
       IS the screen's identity; 3rem is the approved size and the reason the
       header is two rows in the first place.

   REPLACED the one-row contract these used to assert, 2026-08-08. A stale
   assertion that keeps passing against a rejected design is worse than none:
   it reports the header is correct while the screen says otherwise. ── */
const appleCss = () =>
  emit(join(TEMPLATES, 'templates/template-parts/listing-header/skins/apple.php'))
    .replace(/\/\*[\s\S]*?\*\//g, '');

/** Body of the first rule whose selector matches, or throw. */
const cssRule = (css, sel, label) => {
  const m = new RegExp(`${sel}\\s*\\{([^}]*)\\}`).exec(css);
  if (!m) throw new Error(`no \`${label}\` rule`);
  return m[1];
};
/** One declaration out of a rule body, '' when absent. */
const cssDecl = (body, prop) => {
  const m = new RegExp(`(?:^|;)\\s*${prop}:\\s*([^;]+)`, 'i').exec(body);
  return m ? m[1].trim() : '';
};

check('H5 · apple skin: row 1 month + controls, row 2 twelve bars', () => {
  const css = appleCss();

  /* TWO ROWS. The grid is what puts the scrubber on its own line; if the skin
     goes back to a single flex row the month has to shrink to fit and the
     rejected design is back. */
  const root = cssRule(css, '\\.alh--apple', '.alh--apple');
  if ('grid' !== cssDecl(root, 'display')) {
    throw new Error(`.alh--apple is display:${cssDecl(root, 'display') || '(unset)'} — the two-row grid is the approved layout`);
  }
  if (!/minmax\(0,\s*1fr\)\s+auto/.test(cssDecl(root, 'grid-template-columns'))) {
    throw new Error('.alh--apple columns are not `minmax(0, 1fr) auto` — a long month name would blow the grid out');
  }

  /* ROW 1 · the controls are the only explicitly placed item. */
  const top = cssRule(css, '\\.alh--apple \\.alh__top', '.alh__top');
  if ('2' !== cssDecl(top, 'grid-column') || '1' !== cssDecl(top, 'grid-row')) {
    throw new Error('.alh__top is not pinned to row 1 / column 2');
  }

  /* ROW 1 · the month at the lab's size. `min(3rem, …)` only bites under
     ~384px, so this still reads as 3rem on every mainstream handset. */
  const type = cssRule(css, '\\.alh--apple \\.alh__month,\\s*\\n\\.alh--apple \\.alh__year', '.alh__month/.alh__year');
  const size = cssDecl(type, 'font-size');
  if (!/(^|[^\d.])3rem/.test(size)) throw new Error(`month type is "${size}", expected 3rem`);

  /* ROW 1 · ONE glyph size for the cluster, arrow lifted out of flow.
     2026-08-09: search and filter used to declare their size separately, both
     at 1rem, so the cluster's type could be changed in one place and stay
     wrong in the other. They are now a single rule, and it is asserted as a
     single rule — a re-split would put the drift back. */
  const icons = cssRule(
    css,
    '\\.alh--apple \\.alh__ic i,\\s*\\n\\.alh--apple \\.alh__ic--filter i',
    '.alh__ic i / .alh__ic--filter i'
  );
  const glyph = cssDecl(icons, 'font-size');
  if (!/^calc\(\s*\d+px\s*\*\s*var\(--fs-u,\s*1\)\s*\)$/.test(glyph)) {
    throw new Error(`cluster glyph is "${glyph}", expected calc(Npx * var(--fs-u, 1)) so it tracks the user scale`);
  }
  if ((css.match(/\.alh__ic--filter i\b/g) || []).length !== 1) {
    throw new Error('.alh__ic--filter i is targeted more than once — the cluster glyph size has two owners again');
  }
  const step = cssRule(css, '\\.alh--apple \\.alh__step', '.alh__step');
  if ('absolute' !== cssDecl(step, 'position') || '-25px' !== cssDecl(step, 'top')) {
    throw new Error(`.alh__step is ${cssDecl(step, 'position')}/top:${cssDecl(step, 'top')}, expected absolute/top:-25px`);
  }
  if ('relative' !== cssDecl(cssRule(css, '\\.alh--apple \\.alh__icons', '.alh__icons'), 'position')) {
    throw new Error('.alh__icons is not positioned — the arrow would resolve top:-25px against the wrong box');
  }
  /* Quietness must be a colour alpha: `opacity` makes the button a group and
     the count badge would fade with it. */
  const filter = cssRule(css, '\\.alh--apple \\.alh__ic--filter', '.alh__ic--filter');
  if ('1' !== cssDecl(filter, 'opacity')) throw new Error('.alh__ic--filter dims with opacity — the count badge would fade too');
  if (!/rgba\(var\(--rgb-diff\),\s*\.\d+\)/.test(cssDecl(filter, 'color'))) {
    throw new Error('.alh__ic--filter is not quieted by a token colour alpha');
  }

  /* ROW 2 · twelve bars, all one height, active by colour only. */
  const seg = cssRule(css, '\\.alh--apple \\.alh__seg', '.alh__seg');
  if ('block' !== cssDecl(seg, 'display')) {
    throw new Error('.alh__seg is not display:block — height on an inline <span> is ignored, all 12 bars compute to 0');
  }
  const h = /^([\d.]+)px$/.exec(cssDecl(seg, 'height'));
  if (!h || Number(h[1]) <= 0) throw new Error(`.alh__seg height is "${cssDecl(seg, 'height')}", expected a non-zero px value`);
  if ('100%' !== cssDecl(seg, 'width')) throw new Error('.alh__seg has no width:100% — the bar would collapse to its content');
  if (!/grid-column:\s*1\s*\/\s*-1/.test(cssRule(css, '\\.alh--apple \\.alh__scrub', '.alh__scrub'))) {
    throw new Error('.alh__scrub does not span the full width on row 2');
  }

  /* No rule anywhere may give the twelve bars different heights. */
  for (const m of css.matchAll(/([^{}]*\.alh__seg\b[^{}]*)\{([^}]*)\}/g)) {
    const [, sel, body] = m;
    if (/\.alh__seg-hit\s*\{/.test(`${sel}{`)) continue;
    if (/(^|;)\s*transform:/.test(body)) {
      throw new Error(`\`${sel.trim()}\` transforms the bar — the selected month must differ by colour only`);
    }
    if (/is-active/.test(sel) && /(^|;)\s*height:/.test(body)) {
      throw new Error('the active segment overrides height — all twelve bars must be one height');
    }
  }
  const active = cssRule(css, '\\.alh--apple \\.alh__seg-hit\\.is-active \\.alh__seg', '.is-active .alh__seg');
  if (!/var\(--accent\)/.test(cssDecl(active, 'background'))) {
    throw new Error('the active segment is not painted with var(--accent)');
  }

  /* Mobile: search stands down so row 1 fits 3rem of type. */
  const mobile = /@media\s*\(max-width:\s*767px\)\s*\{([\s\S]*?)\n\}/.exec(css);
  if (!mobile || !/\.alh__ic--search\s*\{[^}]*display:\s*none/.test(mobile[1])) {
    throw new Error('no max-width:767px rule hiding .alh__ic--search');
  }

  /* Stable ids — the handle a screen or a test targets, kept clear of the
     overlays' own `{id}-search` / `{id}-filter`. `pevHeader-btn-next` used to
     be in this list; the control was removed from parts/actions.php on
     2026-08-09 (the scrubber is the month navigator), so its absence is now
     the contract and is asserted below rather than left untested. */
  if (header.ok) {
    for (const id of ['pevHeader-btn-search', 'pevHeader-btn-filter']) {
      if (!header.html.includes(`id="${id}"`)) throw new Error(`rendered header has no #${id}`);
    }
  }
  return `grid 2 rows, month ${size}, glyphs ${glyph}, arrow top:-25px, 12 × ${h[1]}px bars, active = colour`;
});

/* H7 · the next-month arrow is GONE, not hidden ────────────────────────────
   Hiding it in CSS was explicitly rejected: a display:none button still ships
   its label to assistive tech and still exists for anything walking the DOM.
   This asserts the markup, the default and the kernel's tolerance together —
   flipping any one of the three back on its own is what would regress. */
check('H7 · no next-month control is rendered at all', () => {
  const actions = readFileSync(join(TEMPLATES, 'templates/template-parts/listing-header/parts/actions.php'), 'utf8');
  if (/<button[^>]*alh__step--next/.test(stripComments(actions))) {
    throw new Error('parts/actions.php still renders the alh__step--next button');
  }
  const api = readFileSync(join(TEMPLATES, 'includes/listing-header-api.php'), 'utf8');
  if (!/'next'\s*=>\s*false/.test(stripComments(api))) {
    throw new Error("listing-header-api.php no longer defaults 'next' to false");
  }
  /* The kernel must keep tolerating an absent button, or removing it here
     would throw on every page that boots a header. */
  const kernel = readFileSync(join(TEMPLATES, 'templates/template-parts/listing-header/kernel-scripts.php'), 'utf8');
  if (!/if \(nextBtn\)/.test(kernel)) {
    throw new Error('kernel-scripts.php binds [data-alh-month-next] without a null guard');
  }
  if (header.ok) {
    /* Markup only: the kernel's own `[data-alh-month-next]` lookup is the null
       guard asserted above and legitimately survives in the <script> block. */
    const markup = header.html.replace(/<script\b[\s\S]*?<\/script>/g, '');
    for (const dead of ['alh__step--next', 'data-alh-month-next', 'pevHeader-btn-next']) {
      if (markup.includes(dead)) throw new Error(`rendered header still carries ${dead}`);
    }
  }
  return 'not in markup, default false, kernel null-guarded';
});

/* H6 · the overlays are the only content on the screen while they are open, so
   nothing in them may be sized like a caption. --fs-p4 is
   `calc(var(--fs-u) * clamp(.5625rem, …, .75rem))` and --fs-u is a user scale
   preference, which is how the filter chips ended up rendering near 7px. */
check('H6 · filter/search panels are readable at any user scale', () => {
  const css = emit(join(TEMPLATES, 'templates/template-parts/listing-header/kernel-styles.php'))
    .replace(/\/\*[\s\S]*?\*\//g, '');

  if (/--fs-p[3-6]\b/.test(css)) {
    const at = /([^{}]*)\{[^}]*--fs-p[3-6]\b/.exec(css);
    throw new Error(`\`${(at ? at[1] : '?').trim()}\` still uses a caption-grade token in a full-screen panel`);
  }

  /* Bumping the token is not enough on its own: --fs-u can still scale a
     primary control back under 10px. Every readable surface carries a floor. */
  const floored = [
    ['\\.alh-fx__chip label', 'filter chips'],
    ['\\.alh-fx__empty', 'filter empty state'],
    ['\\.alh-fx__btn-clear,\\s*\\n\\.alh-fx__btn-apply', 'filter footer buttons'],
    ['\\.alh-search__input', 'search input'],
    ['\\.alh-ov__hd h3', 'overlay title']
  ];
  for (const [sel, label] of floored) {
    const size = cssDecl(cssRule(css, sel, label), 'font-size');
    const m = /^max\(\s*var\(--fs-p[12]\)\s*,\s*(\d+)px\s*\)$/.exec(size);
    if (!m) throw new Error(`${label} font-size is "${size}", expected max(var(--fs-p1|p2), Npx)`);
    if (Number(m[1]) < 10) throw new Error(`${label} floor is ${m[1]}px, under the 10px legibility floor`);
  }
  /* iOS zooms the page when a focused input is under 16px. */
  const input = cssDecl(cssRule(css, '\\.alh-search__input', 'search input'), 'font-size');
  if (!/,\s*16px\s*\)$/.test(input)) throw new Error(`search input floor is "${input}" — under 16px iOS zooms on focus`);

  /* The remaining literals are mono micro-labels, not body copy — but they
     still may not drop under the floor. */
  for (const m of css.matchAll(/font-size:\s*(\d+)px/g)) {
    if (Number(m[1]) < 9) throw new Error(`a ${m[1]}px literal survives in the overlay stylesheet`);
  }
  return 'no --fs-p3..p6; chips/buttons/empty ≥13px, input ≥16px, title ≥16px';
});

check('C · hero fallback is the LAST layer and inert', () => {
  const f = grab('heroFallbackHTML');
  const need = ['aria-hidden="true"', 'tabindex="-1"', 'sandbox="allow-scripts"', 'pev-hero-fallback-shade', 'is-empty'];
  const miss = need.filter((n) => !f.includes(n));
  if (miss.length) throw new Error(`fallback missing: ${miss.join(' | ')}`);
  if (f.indexOf('pev-hero-fallback-shade') < f.indexOf('<iframe')) throw new Error('shade must come after the iframe');
  if (f.indexOf('is-empty') < f.indexOf('pev-hero-fallback-shade')) throw new Error('message must come after the shade');
  return 'iframe → shade → message, sandboxed + inert';
});

/* The X-Frame-Options fix: the embed must be SAME-ORIGIN, i.e. sourced from the
   REST proxy via a data attribute, never a hard-coded assets.apollo.rio.br URL. */
check('C · fallback iframe is same-origin (proxied), not cross-origin', () => {
  /* Comments stripped: the docblock explaining the fix legitimately names the
     CDN host, and matching that would be the check failing on its own prose. */
  if (/assets\.apollo\.rio\.br/.test(stripComments(appSrc))) {
    throw new Error('app.php still builds a cross-origin assets.apollo.rio.br iframe src');
  }
  if (!appSrc.includes("getAttribute('data-iframe-fallback')")) {
    throw new Error('app.php does not read the proxied src from data-iframe-fallback');
  }
  const tpl = readFileSync(join(PLUGIN, 'styles/base/archive-event.php'), 'utf8');
  if (!tpl.includes('apollo_event_iframe_proxy_url')) {
    throw new Error('archive-event.php does not emit data-iframe-fallback');
  }
  const proxy = readFileSync(join(PLUGIN, 'includes/iframe-proxy.php'), 'utf8');
  if (!proxy.includes('apollo_event_iframe_proxy_map')) throw new Error('proxy has no allowlist map');
  if (/\$request->get_param\(\s*['"]url['"]\s*\)/.test(proxy)) {
    throw new Error('SSRF: proxy accepts a caller-supplied URL');
  }
  const boot = readFileSync(join(PLUGIN, 'includes/bootstrap.php'), 'utf8');
  if (!boot.includes('iframe-proxy.php')) throw new Error('proxy never loaded by bootstrap.php');
  return 'REST proxy, slug allowlist, wired in bootstrap';
});

/* A card click must open the FULL single-event page in the lightbox — the same
   PHP renderer as /evento/{slug} — not the small quick-view summary. */
check('C · card click opens the full-page lightbox', () => {
  if (!/function openModal\([\s\S]{0,400}ApolloEventLightbox\.open/.test(appSrc)) {
    throw new Error('openModal() does not delegate to ApolloEventLightbox.open');
  }
  if (!appSrc.includes('function openQuickView(')) {
    throw new Error('quick-view fallback was removed instead of being kept as a fallback');
  }
  /* RSVP repaint must NOT re-enter the lightbox — that would swap the user's
     whole screen on every RSVP click. */
  const rsvpRepaints = [...appSrc.matchAll(/dataset\.openId\)\s*openModal\(/g)];
  if (rsvpRepaints.length) throw new Error('a quick-view repaint still calls openModal()');
  const tpl = readFileSync(join(PLUGIN, 'styles/base/archive-event.php'), 'utf8');
  if (!tpl.includes('apollo_event_lightbox_boot')) {
    throw new Error('archive-event.php never boots the lightbox runtime');
  }
  return 'openModal → lightbox; quick-view kept as fallback';
});

/* ── The five blockers from the 2026-08-01 lightbox audit ─────────────────── */

/* BLOCKER 1 — the runtime must actually LOAD. `type="text/apollo-defer"` with
   an external src is never executed by the browser and is skipped by core.js's
   inline-only promoter, so ApolloEventLightbox never existed. */
check('E1 · lightbox runtime loads as a real <script src>', () => {
  const rs = readFileSync(join(PLUGIN, 'includes/render-single.php'), 'utf8');
  const bootFn = rs.slice(rs.indexOf('function apollo_event_lightbox_boot'));
  const body = bootFn.slice(0, bootFn.indexOf('\n}'));
  for (const f of ['apollo-single-event.js', 'apollo-event-lightbox.js']) {
    const tag = body.split('\n').find((l) => l.includes(f));
    if (!tag) throw new Error(`${f} is not emitted by apollo_event_lightbox_boot()`);
    if (/type=["']text\/apollo-defer/.test(tag)) {
      throw new Error(`${f} still uses type="text/apollo-defer" — the browser will not execute it`);
    }
  }
  return 'both runtimes emitted as <script defer src>';
});

/* BLOCKER 2 — the documented trigger contract: data-ev-open + real permalink. */
check('E2 · cards use the documented data-ev-open + permalink', () => {
  const src = stripComments(appSrc);
  if (/data-pev-open/.test(src)) {
    throw new Error('a card/handler still uses the non-standard data-pev-open attribute');
  }
  /*
   * TIGHTENED 2026-08-07. This used to assert only that data-ev-open was
   * emitted, while REPORTING "data-ev-open + href={permalink}" — it never
   * checked for an href at all. The live DOM disagreed: 0 of 10 cards on
   * /portal carried one. An assertion that reports more than it verifies is
   * worse than no assertion. It now checks each builder for a real permalink.
   */
  const builders = {
    eveCardHTML: true,      // <article data-ev-open><a href={permalink}>
    eveCardMiniHTML: true,  // idem
    /*
     * KNOWN GAP — eventRowHTML emits <div role="button"> with no anchor, so the
     * compact rail rows have no fragment-failure fallback, no middle-click /
     * ⌘-click / "open in new tab", and nothing for a crawler. Out of scope for
     * the P1–P3 packaging pass; the fix is to make the row an <a class=
     * "event-row" href={e.url}> and drop tabindex/role (a link is natively
     * focusable, so that is also an a11y improvement) — but .event-row is a DS
     * component in styles-ds.php and the swap needs a visual pass.
     * Recorded rather than silently tolerated: flip this to true when fixed and
     * the assertion enforces it from then on.
     */
    eventRowHTML: false
  };
  const gaps = [];
  for (const [fn, needsHref] of Object.entries(builders)) {
    const i = src.indexOf(`function ${fn}(`);
    if (i < 0) throw new Error(`${fn}() missing`);
    const body = src.slice(i, i + 2200);
    if (!body.includes('data-ev-open=')) throw new Error(`${fn}() does not emit data-ev-open`);
    const hasHref = /href="'\s*\+\s*esc\(e\.url/.test(body);
    if (needsHref && !hasHref) {
      throw new Error(`${fn}() emits data-ev-open with no permalink href — no fallback, no middle-click`);
    }
    if (!needsHref && hasHref) {
      throw new Error(`${fn}() now has an href — flip its flag to true so this stays enforced`);
    }
    if (!needsHref) gaps.push(fn);
  }
  if (/href="#"/.test(src)) throw new Error('a card anchor still hard-codes href="#" instead of the permalink');
  return `2 builders carry a real permalink; ${gaps.join(', ')} = known gap`;
});

/* BLOCKER 3 — the portal must not intercept the click when the lightbox is
   present, or its delegated listener never sees it. */
check('E3 · portal yields the click to the lightbox', () => {
  const src = stripComments(appSrc);
  if (!/ApolloEventLightbox[\s\S]{0,120}return;/.test(src)) {
    throw new Error('onClick does not bail out when ApolloEventLightbox is available');
  }
  return 'onClick returns early; lightbox owns the event';
});

/* BLOCKER 4/5 — full viewport, and above the Apollo+ shell (.ax-top 9901 /
   .ax-aside 9902). A "fullscreen" overlay under the topbar is not fullscreen. */
check('E4 · lightbox is full-viewport and clears the shell', () => {
  const css = emit(join(PORTAL, 'styles-lightbox.php')).replace(/\/\*[\s\S]*?\*\//g, '');
  const z = /\.ev-lb\s*\{[^}]*z-index:\s*(\d+)/.exec(css);
  if (!z) throw new Error('styles-lightbox.php does not set .ev-lb z-index');
  if (Number(z[1]) <= 9902) throw new Error(`z-index ${z[1]} is under .ax-aside (9902)`);
  if (!/--ev-lb-w:\s*100vw/.test(css)) throw new Error('panel width is not 100vw');
  if (!/--ev-vw:\s*100vw/.test(css)) {
    throw new Error('--ev-vw not widened: full-bleed blocks would lay out against the old 560px sheet');
  }
  const loader = readFileSync(join(PORTAL, 'styles.php'), 'utf8');
  if (!/'lightbox'/.test(loader)) throw new Error('styles.php does not load the lightbox cell');
  return `z-index ${z[1]}, 100vw panel, --ev-vw widened`;
});

/* The single-event fragment scrolls in its OWN box, so ScrollTrigger has to be
   pointed at it — otherwise every reveal fires at mount or never. */
check('E5 · ScrollTrigger is proxied to the lightbox scroller', () => {
  const boot = emit(join(PORTAL, 'bootstrap.php'));
  if (!boot.includes('scrollerProxy')) throw new Error('no ScrollTrigger.scrollerProxy for .ev-lb-scroll');
  if (!/\.ev-lb\.is-open/.test(boot)) throw new Error('scroll lock does not account for the open lightbox');
  new Function(stripTags(boot)); /* must still parse */
  return 'scrollerProxy + scroll lock wired';
});

/* THE scroll fix: Lenis preventDefaults wheel/touch globally — and explicitly
   does so while stopped — so a nested scroller is frozen without this opt-out. */
check('E6 · lightbox scroller opts out of Lenis', () => {
  const rs = readFileSync(join(PLUGIN, 'includes/render-single.php'), 'utf8');
  const shell = rs.slice(rs.indexOf('function apollo_event_lightbox_shell'));
  const markup = shell.slice(0, shell.indexOf('\n}'));
  if (!/data-ev-lb-scroll[^>]*data-lenis-prevent|data-lenis-prevent[^>]*data-ev-lb-scroll/.test(markup)) {
    throw new Error('.ev-lb-scroll is missing data-lenis-prevent — Lenis will eat its wheel events');
  }
  const css = emit(join(PORTAL, 'styles-lightbox.php')).replace(/\/\*[\s\S]*?\*\//g, '');
  if (!/overscroll-behavior:\s*contain/.test(css)) throw new Error('no overscroll-behavior:contain — gesture chains to the locked page');
  if (!/touch-action:\s*pan-y/.test(css)) throw new Error('no touch-action:pan-y — iOS hands vertical drags back to the page');
  return 'data-lenis-prevent + contain + pan-y';
});

/* Close control: glyph and colour only. */
check('E7 · close button carries no background or border', () => {
  const css = emit(join(PORTAL, 'styles-lightbox.php')).replace(/\/\*[\s\S]*?\*\//g, '');
  const rule = /\.ev-lb\.ev-lb \.ev-lb-x\s*\{([^}]*)\}/.exec(css);
  if (!rule) throw new Error('styles-lightbox.php does not restyle .ev-lb-x at override specificity');
  const body = rule[1];
  for (const [prop, want] of [['background', /none|transparent/], ['border', /^0/], ['box-shadow', /none/], ['backdrop-filter', /none/]]) {
    const m = new RegExp(`(?:^|;)\\s*${prop}:\\s*([^;]+)`, 'i').exec(body);
    if (!m) throw new Error(`.ev-lb-x does not neutralise ${prop}`);
    if (!want.test(m[1].trim())) throw new Error(`.ev-lb-x ${prop} is "${m[1].trim()}", expected none/transparent`);
  }
  return 'bare glyph — no bg, border, shadow or blur';
});

/* ── CASCADE TRAP ──────────────────────────────────────────────────────────
   assets/css/apollo-single-event.css is printed by apollo_event_lightbox_boot()
   at the END OF <body>; this cell ships in <head>. Later source order wins a
   specificity TIE, so any override written at equal weight silently loses and
   the bug is invisible in the source — it reads correct and does nothing.
   Every selector here that also exists in the base sheet must therefore be
   written heavier (.ev-lb.ev-lb …). */
check('E10 · lightbox overrides out-specify the base sheet', () => {
  const baseCss = readFileSync(join(PLUGIN, 'assets/css/apollo-single-event.css'), 'utf8')
    .replace(/\/\*[\s\S]*?\*\//g, '');
  const cellCss = emit(join(PORTAL, 'styles-lightbox.php')).replace(/\/\*[\s\S]*?\*\//g, '');

  const selectorsOf = (css) => {
    const out = new Set();
    for (const m of css.matchAll(/(^|[}])\s*([^{}@]+?)\s*\{/g)) {
      for (const s of m[2].split(',')) {
        const t = s.trim();
        if (t && !/^(from|to|\d)/.test(t)) out.add(t);
      }
    }
    return out;
  };

  const baseSel = selectorsOf(baseCss);
  const weak = [];
  for (const sel of selectorsOf(cellCss)) {
    if (!/\.ev-lb/.test(sel)) continue;
    if (/\.ev-lb\.ev-lb/.test(sel)) continue;      /* already heavier */
    if (!baseSel.has(sel)) continue;               /* nothing to fight */
    weak.push(sel);
  }
  if (weak.length) {
    throw new Error(`same-weight override(s) the base sheet will beat: ${weak.join(' ; ')}`);
  }
  return `${baseSel.size} base selectors compared; no ties`;
});

/* Nothing inside the lightbox is selectable — except real inputs. */
check('E8 · lightbox blocks user selection on every descendant', () => {
  const css = emit(join(PORTAL, 'styles-lightbox.php')).replace(/\/\*[\s\S]*?\*\//g, '');
  if (!/\.ev-lb\.ev-lb,\s*\.ev-lb\.ev-lb \*\s*\{[^}]*user-select:\s*none\s*!important/.test(css)) {
    throw new Error('no `.ev-lb.ev-lb, .ev-lb.ev-lb *` user-select:none !important rule');
  }
  if (!/\.ev-lb\.ev-lb input[\s\S]{0,260}user-select:\s*text\s*!important/.test(css)) {
    throw new Error('inputs are not exempted — the user could not type into a form');
  }
  return 'all descendants none; inputs/contenteditable exempt';
});

/* Content must never be left invisible by a reveal that did not resolve. */
check('E9 · reveals cannot strand content invisible', () => {
  const css = emit(join(PORTAL, 'styles-lightbox.php')).replace(/\/\*[\s\S]*?\*\//g, '');
  /* The floor is now generated from APOLLO_EVENT_REVEAL_FLOOR (P3), so assert
     the whole contract is covered rather than the one legacy `.ev-reveal` rule
     — that literal used to be the only thing checked here while the nine
     selectors that actually strand content sat in a separate hand-copied
     block, unverified. */
  const floorRule = (css.match(/[^{}]*\.ev-reveal[^{}]*\{[^}]*opacity:\s*1[^}]*\}/) || [''])[0];
  if (!floorRule) throw new Error('no CSS floor making reveals visible once the panel is open');
  const missing = REVEAL_FLOOR.filter((sel) => !floorRule.includes(`.is-open ${sel}`));
  if (missing.length) {
    throw new Error(`CSS floor does not cover ${missing.join(', ')} — those can strand invisible`);
  }
  if (!/\.ev-force-visible[\s\S]{0,200}opacity:\s*1\s*!important/.test(css)) {
    throw new Error('no .ev-force-visible escape hatch');
  }
  const boot = stripTags(emit(join(PORTAL, 'bootstrap.php')));
  if (!boot.includes('getComputedStyle')) throw new Error('bootstrap never verifies the rendered outcome');
  if (!boot.includes('ev-force-visible')) throw new Error('bootstrap never applies the escape hatch');
  new Function(boot);
  return 'CSS floor + computed-opacity audit after mount';
});

/* ══ SURFACE CONTRACT, 2026-08-07 ═══════════════════════════════════════════
   The audit found apollo-events was the only plugin with a render SSOT, a card
   contract, a public enqueue API or cache-safe asset versions — djs, loc, hub,
   social, users and adverts had none of the four. The machinery moved to
   apollo-core so a plugin opts in with one call instead of growing a divergent
   copy. These guard the wire. ══ */
const CORE = join(PLUGIN, '../apollo-core');

check('E17 · apollo-core owns the surface contract', () => {
  let php;
  try { php = readFileSync(join(CORE, 'includes/surface-contract.php'), 'utf8'); }
  catch { throw new Error('apollo-core/includes/surface-contract.php is missing'); }

  for (const fn of ['apollo_surface_register', 'apollo_surface_get', 'apollo_surface_open_attrs',
                    'apollo_surface_render', 'apollo_surface_can_view', 'apollo_surface_js_config']) {
    if (!php.includes(`function ${fn}(`)) throw new Error(`${fn}() missing from the contract`);
  }
  /* A surface whose renderer does not exist yet must be inert, never fatal —
     that is what lets djs/loc declare intent before they can render. */
  if (!/is_callable\(\s*\$s\['renderer'\]\s*\)/.test(php)) {
    throw new Error('apollo_surface_get() does not gate on a callable renderer — dormant surfaces would fatal');
  }
  /* Registering a route that a plugin already owns would replace a working
     handler. apollo-events ships apollo/v1/eventos/{id}/fragmento. */
  if (!/rest_get_server\(\)->get_routes\(\)/.test(php) || !/in_array\(\s*\$route,\s*\$existing/.test(php)) {
    throw new Error('route registration does not check for an existing route — would clobber apollo-events');
  }
  /* The fragment endpoint is public; visibility is the only gate, and a
     surface that omits can_view must fail closed. */
  if (!/'publish'\s*===\s*\$post->post_status\s*\|\|\s*current_user_can/.test(php)) {
    throw new Error('apollo_surface_can_view() has no strict fallback — an unguarded surface could leak drafts');
  }
  if (!/register_post_type|register_post_meta|register_taxonomy/.test(php) === false) {
    throw new Error('surface contract registers CPT/meta — that belongs to apollo-core registries only');
  }
  return 'register/get/attrs/render/can_view/js_config; dormant-safe; no route clobber';
});

check('E18 · every surface is declared in PHP, never hard-coded in JS', () => {
  const js = readFileSync(join(PLUGIN, 'assets/js/apollo-event-lightbox.js'), 'utf8');

  if (!/data-ap-open/.test(js)) throw new Error('runtime does not understand the generic card contract');
  if (!/data-ev-open/.test(js)) throw new Error('legacy data-ev-open support was dropped — /portal cards would die');
  if (!/w\.APOLLO_SURFACES/.test(js)) throw new Error('runtime does not read the PHP-published surface table');

  /* No endpoint path for a non-event type may be written in JS, or adding a
     surface becomes a two-file change and the two drift. */
  const paths = js.match(/apollo\/v1\/[a-z]+/g) || [];
  const nonEvent = paths.filter((p) => !p.endsWith('/eventos'));
  if (nonEvent.length) throw new Error(`hard-coded surface path in JS: ${nonEvent.join(', ')}`);

  /* Cache must be type-scoped or event 5 and dj 5 collide. */
  if (!/var key = \(type \|\| 'event'\) \+ ':' \+ id/.test(js)) {
    throw new Error('fragment cache is keyed on id alone — two surfaces would serve each other');
  }
  /* An unknown type must fall through to the anchor, not swallow the click. */
  if (!/if \('event' !== t\.type && !surfaces\[t\.type\]\) \{ return; \}/.test(js)) {
    throw new Error('runtime claims clicks for types it cannot serve — dormant surface = dead click');
  }
  const php = readFileSync(RENDER_SINGLE, 'utf8');
  if (!php.includes('apollo_surface_register(')) throw new Error('apollo-events never registers itself as a surface');
  if (!php.includes('APOLLO_SURFACES')) throw new Error('surface table is never published to the browser');
  return 'data-ap-open + data-ev-open alias, table from PHP, type-scoped cache';
});

check('E19 · asset URLs move when the file does', () => {
  let php;
  try { php = readFileSync(join(CORE, 'includes/asset-version.php'), 'utf8'); }
  catch { throw new Error('apollo-core/includes/asset-version.php is missing'); }

  for (const f of ['script_loader_src', 'style_loader_src']) {
    if (!php.includes(`add_filter( '${f}'`)) throw new Error(`${f} filter not registered`);
  }
  if (!/filemtime/.test(php)) throw new Error('no filemtime — the whole point');
  /* Must never touch the CDN: several of those tags carry SRI hashes, and
     06-cdn.json pins the core.js contract. */
  if (!/plugins_url\(\)/.test(php) || !/strpos\(\s*\$bare,\s*\$base\s*\)/.test(php)) {
    throw new Error('no scope gate on the plugins directory — would rewrite CDN and SRI-pinned URLs');
  }
  if (!/strpos\(\s*\$relative,\s*'apollo-'\s*\)/.test(php)) {
    throw new Error('no apollo-* scope gate — would rewrite third-party plugin assets');
  }
  if (!/WP_PLUGIN_DIR/.test(php) || !/wp_normalize_path/.test(php)) {
    throw new Error('no traversal guard on the resolved path');
  }
  /* Stamping an already-stamped URL twice would grow it every request. */
  if (!/substr\(\s*\$declared,\s*-strlen/.test(php)) throw new Error('not idempotent — mtime would stack');

  const boot = readFileSync(join(CORE, 'apollo-core.php'), 'utf8');
  if (!boot.includes("includes/asset-version.php")) throw new Error('asset-version.php is never required');
  return 'both loader filters, plugins-dir + apollo-* scoped, traversal-guarded, idempotent';
});

/* ══════════════════════════════════════════════════════════════════════════
   E20–E23 · THE READER SURFACE CONTRACT (2026-08-17)

   The requirement: whatever is behind a fragment is SOLID WHITE, always, in
   both themes, with a close control that is always reachable. These four
   assertions exist because every part of that sentence has already been
   broken once by a change that looked correct in the file it was made in.
   ══════════════════════════════════════════════════════════════════════════ */

const LIGHTBOX_CSS = join(PORTAL, 'styles-lightbox.php');

/* Pull one rule block's body out of the cell. Crude on purpose — a real CSS
   parser here would be a dependency, and the cell is hand-written and stable. */
function ruleBody(css, selector) {
  const at = css.indexOf(selector + ' {');
  if (at < 0) throw new Error(`selector not found in styles-lightbox.php: ${selector}`);
  const open = css.indexOf('{', at);
  const close = css.indexOf('}', open);
  if (close < 0) throw new Error(`unterminated rule: ${selector}`);
  return css.slice(open + 1, close);
}

/* Strip comments before pattern-matching, or the long explanatory note inside
   the block (which legitimately mentions var(--ev-bg) and rgba) fails the test
   it exists to explain. */
function decomment(s) {
  return s.replace(/\/\*[\s\S]*?\*\//g, '');
}

check('E20 · the reader panel is an opaque literal, never a themed token', () => {
  const css = decomment(readFileSync(LIGHTBOX_CSS, 'utf8'));
  const body = ruleBody(css, '.ev-lb.ev-lb .ev-lb-panel');

  const bg = /(?:^|[;{])\s*background\s*:\s*([^;]+);/.exec(body);
  if (!bg) throw new Error('.ev-lb-panel declares no background here — the base sheet token wins and flips in dark mode');

  const value = bg[1].trim().toLowerCase();
  if (value.includes('var(')) {
    throw new Error(`panel background is a token (${value}) — --white-1 resolves to #0b0b0d under html.dark-mode`);
  }
  if (value.includes('transparent') || /rgba\([^)]*,\s*0?\.\d+\s*\)/.test(value)) {
    throw new Error(`panel background is not opaque: ${value}`);
  }
  if (!/^#(fff|ffffff)$/.test(value)) {
    throw new Error(`panel background must be solid white, got: ${value}`);
  }

  /* THE TRAP. Pinning bg without ink gives white-on-white, which renders as a
     blank panel with a working ✕ — indistinguishable from the 2026-08-07
     stale-asset failure, and far harder to diagnose the second time. */
  for (const tok of ['--ev-ink', '--ev-mute', '--ev-faint', '--ev-line', '--ev-line2', '--ev-soft']) {
    if (!body.includes(tok + ':')) {
      throw new Error(`panel pins background but not ${tok} — dark mode inverts it independently and text goes white-on-white`);
    }
  }
  /* Component-scoped only. A :root here forks the token system ecosystem-wide. */
  if (/:root/.test(css)) throw new Error('styles-lightbox.php declares :root — that belongs to core.js');

  return 'opaque #fff + full --ev-* pin, component-scoped';
});

check('E21 · the backdrop carries no alpha and no blur', () => {
  const css = decomment(readFileSync(LIGHTBOX_CSS, 'utf8'));
  const body = ruleBody(css, '.ev-lb.ev-lb .ev-lb-bd');

  const bg = /(?:^|[;{])\s*background\s*:\s*([^;]+);/.exec(body);
  if (!bg) throw new Error('.ev-lb-bd declares no background — the base sheet rgba(0,0,0,.52) wins');

  const value = bg[1].trim().toLowerCase();
  if (/rgba|hsla|transparent|var\(/.test(value)) {
    throw new Error(`backdrop must be an opaque literal, got: ${value} — it IS the surface during the entry frames`);
  }
  if (!/backdrop-filter\s*:\s*none/.test(body)) {
    throw new Error('backdrop-filter is not neutralised — the page behind reads through while the panel transitions');
  }
  return 'opaque literal, blur off';
});

check('E22 · the panel always ships exactly one close control', () => {
  const php = readFileSync(RENDER_SINGLE, 'utf8');
  const at = php.indexOf('function apollo_event_lightbox_shell');
  if (at < 0) throw new Error('apollo_event_lightbox_shell() is gone');
  const shell = php.slice(at, php.indexOf('\n}', at));

  const closers = shell.match(/data-ev-lb-close/g) || [];
  /* Two by design: the backdrop and the button. The BUTTON is the one that
     must never disappear — the backdrop is not discoverable and not focusable,
     and a keyboard user with Escape swallowed by an inner overlay has nothing
     else. */
  if (closers.length < 2) throw new Error('shell lost a close affordance');
  if (!/<button[^>]*class="ev-lb-x"[^>]*data-ev-lb-close/.test(shell)) {
    throw new Error('the ✕ button is missing or no longer carries data-ev-lb-close');
  }
  if (!/aria-label=/.test(shell)) throw new Error('close control has no accessible name');
  if (!/role="dialog"/.test(shell) || !/aria-modal="true"/.test(shell)) {
    throw new Error('panel is not announced as a modal dialog');
  }

  const js = readFileSync(join(PLUGIN, 'assets/js/apollo-event-lightbox.js'), 'utf8');
  if (!/'Escape' !== e\.key/.test(js)) throw new Error('Escape no longer closes the panel');
  return 'button + backdrop, labelled, dialog semantics, Escape bound';
});

check('E23 · every surface can be woken and gated', () => {
  const js = readFileSync(join(PLUGIN, 'assets/js/apollo-event-lightbox.js'), 'utf8');

  /* Hydration is what makes a surface that ships an inline runtime — apollo-djs
     does, from a GENERATED cell that must not be hand-edited — actually work.
     Without it a DJ fragment injects as correct but dead html. */
  if (!/function hydrateScripts/.test(js)) {
    throw new Error('no script hydration — any surface whose runtime is inline injects dead');
  }
  if (!/state\.injectToken/.test(js)) {
    throw new Error('hydration has no generation token — two fast opens can interleave two runtimes');
  }
  if (!/EXECUTABLE/.test(js)) {
    throw new Error('hydration re-executes every <script> type — application/json config blocks are data, not code');
  }
  /* The event path must survive untouched; it is the one in production. */
  if (!/ApolloEventSingle\.mount/.test(js)) {
    throw new Error('the event surface lost its explicit mount');
  }

  /* Fail-closed gate: a surface whose renderer is callable but which declares
     no can_view falls back to published-only. That is safe, but silent — a
     surface with drafts would hide them from their own author with no clue why.
     Every plugin that declares a surface must say what visible means. */
  for (const plugin of ['apollo-djs', 'apollo-loc']) {
    let src;
    try { src = readFileSync(join(PLUGIN, '..', plugin, 'includes/surface.php'), 'utf8'); }
    catch { continue; } /* Surface not declared yet — nothing to assert. */

    if (!src.includes(`apollo_surface_register(`)) throw new Error(`${plugin} surface.php registers nothing`);
    if (!/'can_view'\s*=>/.test(src)) throw new Error(`${plugin} declares a surface with no can_view — fails closed silently`);
    if (!/'renderer'\s*=>/.test(src)) throw new Error(`${plugin} declares a surface with no renderer`);
    /* A stale "not yet live" note on a surface whose renderer exists is how a
       second implementation gets written. Caught once, on 2026-08-17. */
    if (/NOT YET LIVE|not yet live/.test(src)) {
      const renderer = /'renderer'\s*=>\s*'([a-z_]+)'/.exec(src);
      if (renderer) {
        const impl = join(PLUGIN, '..', plugin, 'includes/render-single.php');
        if (existsSync(impl) && readFileSync(impl, 'utf8').includes(`function ${renderer[1]}`)) {
          throw new Error(`${plugin}/includes/surface.php still says "not yet live" but ${renderer[1]}() exists — stale doc invites a duplicate implementation`);
        }
      }
    }
  }
  return 'hydration + token + type gate; dj/loc declare renderer and can_view';
});

/* E24 · CSS comments do not nest — and one of ours had stopped closing.
   FOUND LIVE 2026-08-17 in styles-lightbox.php §6, which documented the
   reading-column fix with an inline annotation:

     :root { --ev-max: 440px }        [slash-star] 500px above 540px wide [star-slash]
     .ev-wrap { width: min(100%, var(--ev-max)); margin-inline: auto }

   CSS has no nested comments, so the outer block ENDED at that inner close.
   Everything after it — a live-looking .ev-wrap rule and fourteen lines of
   English prose — was handed to the CSS parser as source. The .ev-wrap rule
   escaped into the global scope (unscoped by .ev-lb, so it reached the real
   single page too) and the prose survived only because the parser's error
   recovery swallowed it silently.

   It shipped for months looking like a comment in every editor, because
   syntax highlighters DO nest and the browser does not. That gap is the whole
   reason this assertion is mechanical rather than a review note. */
check('E24 · no style cell opens a comment inside a comment', () => {
  /* Read the directory rather than the CELLS list on purpose: a style cell
     added tomorrow is covered without anyone remembering to extend this.
     Scoped to styles*.php — those are pure CSS-in-PHP, where a second comment
     opener inside an open span is unambiguously the defect. The behaviour
     cells (app.php, bootstrap.php) are JavaScript, where those same two
     characters can live legitimately inside a string or a regex literal. */
  const cells = readdirSync(PORTAL).filter((f) => /^styles.*\.php$/.test(f));
  const offenders = [];

  for (const file of cells) {
    let src;
    try { src = readFileSync(join(PORTAL, file), 'utf8'); } catch { continue; }

    /* Walk comment spans the way a parser does: open at the first opener,
       close at the very next closer. A second opener inside that span is the
       defect — which is why this comment spells both out in words. */
    let i = 0;
    while (true) {
      const open = src.indexOf('/*', i);
      if (open < 0) break;
      const close = src.indexOf('*/', open + 2);
      if (close < 0) {
        offenders.push(`${file}: unterminated comment at offset ${open}`);
        break;
      }
      const inner = src.slice(open + 2, close).indexOf('/*');
      if (inner >= 0) {
        const line = src.slice(0, open + 2 + inner).split('\n').length;
        offenders.push(`${file}:${line} opens a comment inside a comment`);
      }
      i = close + 2;
    }
  }

  if (offenders.length) throw new Error(offenders.join(' · '));
  return `${cells.length} cells, no nested comment spans`;
});

/* E25 · GENERATED CELLS MUST NOT BE POISONED BY THEIR OWN SOURCE.
   FOUND 2026-08-17. apollo-djs ships an approved mockup and a generator that
   slices it into the 17 template-part cells the DJ page is composed from. The
   generator's own header says "Edit the mockup, re-run the generator".

   The mockup is character-corrupted and the shipped cells are not: 152 lines
   of the mockup carry double-encoding mojibake ("CartÃ£o", "pÃ¡gina",
   "â•\x90"), the cells carry zero. The corruption therefore happened AFTER
   generation, and following the documented workflow today would replace 17
   clean cells with 17 broken ones — in a folder that deploys on save.

   This asserts the invariant that actually matters: whatever the mockup's
   state, NO SHIPPED CELL carries the signature. The generator itself now
   fails closed and offers --repair-mockup; this is the second lock, because
   a cell can also be corrupted by a hand edit that never runs the generator. */
check('E25 · no generated DJ cell carries double-encoding damage', () => {
  const DJ_CELLS = join(PLUGIN, '../apollo-djs/styles/base/template-parts/single');
  if (!existsSync(DJ_CELLS)) return 'apollo-djs cells not present — skipped';

  /* Signatures that only occur when UTF-8 was read as CP1252 and re-saved.
     Deliberately NOT bare 'Ã' — that is a legitimate Portuguese letter and
     appears correctly in SEÇÃO and NÃO inside these very cells. Matching it
     would fail the assertion on healthy files, which is how a guard gets
     switched off. */
  const SIGNATURES = ['â€', 'â•', 'Ã£', 'Ã¡', 'Ã©', 'Ã³', 'Ãµ', 'Ã§', 'Ãº', 'Ãª', 'Ã­', 'Ã¢', 'Ã ', 'Â·', 'Â '];

  const hits = [];
  for (const f of readdirSync(DJ_CELLS).filter((n) => n.endsWith('.php'))) {
    const src = readFileSync(join(DJ_CELLS, f), 'utf8');
    const found = SIGNATURES.filter((s) => src.includes(s));
    if (found.length) hits.push(`${f} (${found.join(' ')})`);
  }
  if (hits.length) {
    throw new Error(`generated cells carry mojibake: ${hits.join(' · ')} — do NOT regenerate; run build-dj-cells.py --repair-mockup first`);
  }

  /* The generator must still be armed. If someone removes the guard, the trap
     is live again and this assertion would go quiet for the wrong reason. */
  const gen = join(PLUGIN, '../apollo-djs/_sandbox/build-dj-cells.py');
  if (existsSync(gen)) {
    const py = readFileSync(gen, 'utf8');
    if (!py.includes('MOJIBAKE_MARKERS')) throw new Error('build-dj-cells.py lost its mockup-integrity guard');
    if (!py.includes('--repair-mockup')) throw new Error('build-dj-cells.py lost its repair path');
    if (!py.includes('def check_ranges')) throw new Error('build-dj-cells.py lost its CELLS range validation');
  }
  return 'cells clean; generator fails closed with a repair path';
});

/* E26 · THE CARD CONTRACT holds the same guarantees as the surface contract.
   Added 2026-08-17 with apollo-core/includes/card-contract.php. A card is the
   sibling of a surface: a surface is the page you open, a card is the item you
   click. It exists because the accommodation card had four divergent copies,
   apollo-dashboard redeclared apollo-adverts' bare .accom-* selectors, and the
   live marketplace shipped cards with NO card CSS — archive-classified.php
   stopped linking marketplace.css and nothing replaced it.

   The print-once style ledger is the load-bearing part: it is what makes
   "the CSS is missing on this screen" structurally impossible, because the
   consumer never had to know the card had styles. */
check('E26 · apollo-core owns the card contract', () => {
  const f = join(CORE, 'includes/card-contract.php');
  if (!existsSync(f)) throw new Error('apollo-core/includes/card-contract.php is missing');
  const php = readFileSync(f, 'utf8');

  for (const fn of ['apollo_card_register', 'apollo_card_get', 'apollo_card_render',
                    'apollo_card_styles_once', 'apollo_card_registry']) {
    if (!php.includes(`function ${fn}(`)) throw new Error(`card contract is missing ${fn}()`);
  }

  /* Dormant-safe: a card whose renderer is not callable must be inert, exactly
     like apollo_surface_get(). Without this a screen written ahead of its card
     fatals instead of degrading. */
  if (!/is_callable\(\s*\$c\['renderer'\]\s*\)/.test(php)) {
    throw new Error('apollo_card_get() does not gate on a callable renderer — a dormant card would fatal');
  }
  /* Never clobbers: first registration wins unless replacement is explicit. */
  if (!/\$replace/.test(php)) {
    throw new Error('apollo_card_register() has no clobber guard — two plugins could silently swap one card');
  }
  /* The ledger. A per-call emit would put the same <style> in every grid cell. */
  if (!/static \$printed/.test(php)) {
    throw new Error('apollo_card_styles_once() has no print-once ledger');
  }
  /* Same rule as the surface contract: this file registers nothing. */
  if (/register_post_type|register_post_meta|register_taxonomy/.test(php)) {
    throw new Error('card contract registers CPT/meta — that belongs to apollo-core registries only');
  }

  const boot = readFileSync(join(CORE, 'apollo-core.php'), 'utf8');
  if (!boot.includes('includes/card-contract.php')) throw new Error('card-contract.php is never required');

  /* Docblock and constant must agree — WordPress reads the docblock, cache
     busting reads the constant, and a mismatch means cache-busting lies. */
  const doc = /^\s*\*\s*Version:\s*([0-9.]+)/m.exec(boot);
  const con = /APOLLO_CORE_VERSION',\s*'([0-9.]+)'/.exec(boot);
  if (!doc || !con) throw new Error('apollo-core version could not be read');
  if (doc[1] !== con[1]) {
    throw new Error(`apollo-core docblock ${doc[1]} != APOLLO_CORE_VERSION ${con[1]} — cache-busting would lie`);
  }

  return `register/get/render/styles_once; dormant-safe; no-clobber; ledger; v${con[1]}`;
});

/* E27 · NO DEBUG BEACONS IN SHIPPED CODE.
   Removed once before (apollo-events.json $portal_header_swap records stripping
   one from eveCardHTML) and back again by 2026-08-17, when the sweep found TEN
   across apollo-lux-panels, apollo-events, apollo-loc and apollo-templates:

     · client-side fetch() to http://127.0.0.1:7514 and :7623, running in every
       visitor's browser and blocked as mixed content on HTTPS
     · server-side file_put_contents() to D:/dev/_livro.rvalle.com.br/… — an
       absolute path belonging to a different project on one developer's machine
     · an X-Apollo-Dbg-959e0d response header publishing the absolute server
       path and its writability on EVERY request
     · a REST route /_agent_debug open to any logged-in user that wrote request
       bodies into the WEB-SERVED uploads directory and read them back

   A code review already failed to keep this out twice. This is mechanical. */
check('E27 · no debug beacons ship to production', () => {
  const ROOTS = [
    ['apollo-events', ['src', 'includes', 'assets/js']],
    ['apollo-lux-panels', ['includes']],
    ['apollo-loc', ['src']],
    ['apollo-templates', ['assets/js']],
    ['apollo-adverts', ['src', 'includes']],
    ['apollo-djs', ['src', 'includes']],
  ];

  /* Each pattern is a THING THAT EXECUTES, not a mention. The removal comments
     left behind deliberately name the old URLs so the reason survives in the
     tree, and must not trip this. */
  const BANNED = [
    [/fetch\(\s*['"]https?:\/\/127\.0\.0\.1/, 'client-side fetch to localhost'],
    [/wp_remote_post\(\s*\n?\s*['"]https?:\/\/127\.0\.0\.1/, 'server-side POST to localhost'],
    [/file_put_contents\(\s*['"][A-Za-z]:\//, 'write to an absolute developer path'],
    [/_livro\.rvalle\.com\.br\/[^\s'"]*\.log['"]/, 'write to the _livro project log'],
    [/['"]\/_agent_debug['"]/, 'the /_agent_debug route'],
    /* Must match the header() CALL, not the string — the removal note in
       apollo-events/src/Plugin.php names the header it deleted, and a guard
       that fires on its own tombstone is a guard people switch off. */
    [/header\(\s*\n?\s*['"]X-Apollo-Dbg-/, 'debug response header'],
  ];

  const walk = (dir, acc = []) => {
    if (!existsSync(dir)) return acc;
    for (const e of readdirSync(dir, { withFileTypes: true })) {
      const p = join(dir, e.name);
      if (e.isDirectory()) {
        if (['node_modules', 'vendor', '_sandbox', 'phpcs-logs', '_legacy'].includes(e.name)) continue;
        walk(p, acc);
      } else if (/\.(php|js)$/.test(e.name)) {
        acc.push(p);
      }
    }
    return acc;
  };

  const hits = [];
  for (const [plugin, subs] of ROOTS) {
    for (const sub of subs) {
      for (const file of walk(join(PLUGIN, '..', plugin, sub))) {
        const src = readFileSync(file, 'utf8');
        for (const [re, why] of BANNED) {
          if (re.test(src)) hits.push(`${plugin}/${sub}/…/${file.split(/[\\/]/).pop()}: ${why}`);
        }
      }
    }
  }

  if (hits.length) throw new Error(`debug beacons are back: ${hits.join(' · ')}`);
  return `${ROOTS.length} plugins scanned, no executing beacon`;
});

/* E28 · THE PANEL SCHEMA VALIDATOR EXISTS AND STILL CHECKS THE FOUR REAL DEFECTS.
   All four shipped simultaneously and none was visible until a human opened the
   screen — two of them FATALED the DJ and Local edit screens on PHP 8. */
check('E28 · lux panel schemas are validated at registration', () => {
  const f = join(PLUGIN, '../apollo-lux-panels/includes/Panel.php');
  if (!existsSync(f)) return 'apollo-lux-panels not present — skipped';
  const php = readFileSync(f, 'utf8');

  if (!/function validate_schema/.test(php)) throw new Error('Panel::validate_schema() is gone');
  if (!/KNOWN_TYPES/.test(php)) throw new Error('the field-type allow-list is gone');
  if (!/\$this->validate_schema\(\)/.test(php)) throw new Error('validate_schema() is never called from boot()');

  for (const [needle, why] of [
    [/opts\.cols/, 'the repeater cols rule (the DJ/Local fatal)'],
    [/reads `name`|read `name`/, 'the repeater_card key-vs-name rule (renders but never saves)'],
    [/choices/, 'the select opts-wrapping rule'],
  ]) {
    if (!needle.test(php)) throw new Error(`validator lost ${why}`);
  }

  /* The two crash sites must stay defensive even with the declarations fixed —
     a schema defect should degrade to a missing field, never a dead screen. */
  if (!/\$f\['opts'\]\['cols'\] \?\?/.test(php)) throw new Error('render_repeater lost its cols fallback');

  /* sanitize_callback was honoured for exactly one type before 2026-08-17. */
  if (!/function apply_field_callback/.test(php)) throw new Error('per-field sanitize_callback support is gone');
  /* select is a closed set and must be validated on save. */
  if (!/in_array\( \$val, \$allowed, true \)/.test(php)) throw new Error('select values are no longer validated on save');
  /* per-field capability, the pair of gates. */
  if (!/current_user_can\( \$f\['cap'\] \)/.test(php)) throw new Error('per-field capability gate is gone');

  return 'validator + cols fallback + sanitize_callback + select enum + per-field cap';
});

/* E29 · THE FOUR WIRES ARE ALL PRESENT AND SHARE THEIR SAFETY PROPERTIES.
   Surface, card, panel and health. Each answers one question — open it in place,
   show it in a list, edit it in wp-admin, and where do these disagree — and each
   must be dormant-safe and refuse to clobber. Those two properties are what stop
   the failures this ecosystem keeps paying for: a dormant contract degrades to a
   plain link instead of a fatal, and a clobber guard is what would have prevented
   `event` ending up with two live metabox systems where the legacy one silently
   wins on DOM order. */
check('E29 · surface, card, panel and health contracts hold their invariants', () => {
  const LUX = join(PLUGIN, '../apollo-lux-panels');

  const panelReg = join(LUX, 'includes/registry.php');
  if (!existsSync(panelReg)) throw new Error('apollo-lux-panels/includes/registry.php is missing');
  const p = readFileSync(panelReg, 'utf8');

  for (const fn of ['apollo_panel_register', 'apollo_panel_registry', 'apollo_panel_boot_registered']) {
    if (!p.includes(`function ${fn}(`)) throw new Error(`panel contract is missing ${fn}()`);
  }
  if (!/class DeclarativePanel extends Panel/.test(p)) {
    throw new Error('DeclarativePanel is gone — panels would need a subclass again');
  }
  /* Dormant-safe: a panel for a CPT that never registered must be skipped, not
     turned into an orphan metabox on no screen. */
  if (!/post_type_exists\( \$cpt \)/.test(p)) throw new Error('panel boot does not check post_type_exists');
  /* Never clobbers — the `event` two-owner defect must not be reproducible. */
  if (!/\$replace/.test(p)) throw new Error('apollo_panel_register() has no clobber guard');
  /* Must load after Panel.php, which it extends. */
  const boot = readFileSync(join(LUX, 'apollo-lux-panels.php'), 'utf8');
  const iPanel = boot.indexOf("includes/Panel.php");
  const iReg = boot.indexOf("includes/registry.php");
  if (iReg < 0) throw new Error('registry.php is never required');
  if (iPanel < 0 || iReg < iPanel) throw new Error('registry.php loads before Panel.php, which it extends');

  /* The health report reads the contracts and must never write. */
  const h = join(CORE, 'includes/ecosystem-health.php');
  if (!existsSync(h)) throw new Error('apollo-core/includes/ecosystem-health.php is missing');
  const hs = readFileSync(h, 'utf8');
  if (!/function apollo_health_report/.test(hs)) throw new Error('apollo_health_report() is gone');
  for (const [re, why] of [
    [/update_post_meta|delete_post_meta|update_option|wp_insert_post|wp_update_post/, 'writes to the database'],
    [/file_put_contents|unlink|rename/, 'writes to the filesystem'],
  ]) {
    if (re.test(hs)) throw new Error(`ecosystem-health ${why} — it must report, never repair`);
  }
  if (!/current_user_can\( 'manage_options' \)/.test(hs)) throw new Error('health page is not capability-gated');

  const coreBoot = readFileSync(join(CORE, 'apollo-core.php'), 'utf8');
  for (const f of ['surface-contract.php', 'card-contract.php', 'ecosystem-health.php']) {
    if (!coreBoot.includes(f)) throw new Error(`${f} is never required`);
  }

  return 'panel contract dormant-safe + no-clobber, loaded after Panel; health reports and never writes';
});

/* E16 · the entry class is load-bearing and must have a floor.
   VERIFIED BLANK ON /portal, 2026-08-07: .ev-lb-body ships opacity:0 and is
   cleared only by .is-entered. Every assertion in this file passed while the
   panel rendered as a white rectangle with a working ✕ — fragment in the DOM,
   runtime mounted, GSAP mid-tween, all inside a transparent container. A
   two-rAF handoff is not a guarantee; anything that re-enters inject() can
   strip the class and return without restoring it. */
check('E16 · .ev-lb-body can never be left invisible', () => {
  const css = emit(join(PORTAL, 'styles-lightbox.php')).replace(/\/\*[\s\S]*?\*\//g, '');
  const hidden = /\.ev-lb-body\s*\{[^}]*opacity:\s*0/.test(css);
  const js = readFileSync(join(PLUGIN, 'assets/js/apollo-event-lightbox.js'), 'utf8');

  if (!hidden) return 'ev-lb-body is not opacity-gated — no floor needed';

  if (!/\.ev-lb-body\.is-entered\s*\{[^}]*opacity:\s*1/.test(css)) {
    throw new Error('ev-lb-body is hidden but .is-entered never restores opacity');
  }
  /* The class must be reachable by something other than the rAF pair. */
  const at = js.indexOf('function inject(');
  const body = js.slice(at, js.indexOf('\n  }', at));
  if (!/requestAnimationFrame/.test(body)) throw new Error('inject() lost the rAF entry handoff');
  if (!/setTimeout\(\s*enter|setTimeout\(function[^)]*is-entered/.test(body)) {
    throw new Error('no timed floor on .is-entered — a re-entered inject() leaves the panel blank');
  }
  if (!/clearTimeout/.test(body)) {
    throw new Error('entry fallback timer is never cleared — stale timer can re-show a closed panel');
  }
  return 'opacity gate + .is-entered rule + rAF handoff + timed floor';
});

/* E15 · PHP/HTML mode integrity — the bug this harness did NOT catch.
   Editing a template that interleaves <?php … ?> with raw markup can consume a
   reopening `<?php` and drop the block that follows into HTML mode. Brace
   balance stays perfect, PHP still parses, and the file lints clean — but a
   doc comment is printed verbatim to the browser. On 2026-08-07 that shipped
   the literal text `<script defer src>` from a comment in
   apollo_event_lightbox_boot(); the parser opened a real script element with an
   empty src, which then SWALLOWED the genuine
   <script src="…apollo-single-event.js"> that followed. ApolloEventSingle never
   came into existence and every card opened an unmounted fragment.

   So: walk the PHP/HTML mode machine and assert nothing that looks like code
   ends up in HTML mode. */
check('E15 · no PHP block comment escapes into HTML output', () => {
  const files = [
    'includes/render-single.php',
    'styles/base/template-parts/archive/portal/styles-lightbox.php',
    'styles/base/template-parts/archive/portal/bootstrap.php',
    'styles/base/template-parts/archive/portal/styles.php',
    'styles/base/archive-event.php',
    'styles/base/single-event.php',
    'styles/base/template-parts/archive/portal/header.php',
    'styles/base/template-parts/archive/portal/header-bridge.php'
  ];
  const leaks = [];
  for (const rel of files) {
    const src = readFileSync(join(PLUGIN, rel), 'utf8');
    let i = 0;
    let inPhp = false;
    let line = 1;
    let html = '';
    while (i < src.length) {
      if (src[i] === '\n') line++;
      if (!inPhp) {
        if (src.startsWith('<?php', i) || src.startsWith('<?=', i)) { inPhp = true; i += 3; continue; }
        html += src[i];
        /* A `?>` reachable in HTML mode is orphaned by construction: the only
           way to reach one is from PHP mode, which the branch below consumes.
           This is the precise signature of a swallowed `<?php` — and CSS/JS
           comments inside <style>/<script> stay quiet, unlike a naive /* scan. */
        if (src.startsWith('?>', i)) leaks.push(`${rel}:${line} — orphan ?>`);
        i++;
        continue;
      }
      if (src.startsWith('?>', i)) { inPhp = false; i += 2; continue; }
      /* Skip strings and comments so their contents cannot false-positive. */
      if (src.startsWith('//', i) || (src[i] === '#' && src[i + 1] !== '[')) {
        while (i < src.length && src[i] !== '\n') i++;
        continue;
      }
      if (src.startsWith('/*', i)) {
        const e = src.indexOf('*/', i + 2);
        for (let k = i; k < (e < 0 ? src.length : e); k++) if (src[k] === '\n') line++;
        i = e < 0 ? src.length : e + 2;
        continue;
      }
      if (src[i] === "'" || src[i] === '"') {
        const q = src[i++];
        while (i < src.length) {
          if (src[i] === '\\') { i += 2; continue; }
          if (src[i] === q) { i++; break; }
          if (src[i] === '\n') line++;
          i++;
        }
        continue;
      }
      i++;
    }
    /* Belt and braces: PHPDoc prose that reached HTML mode OUTSIDE a
       <style>/<script> body. `\n\t * ` is the continuation line of a PHP block
       comment and has no business in markup — it is what carried the literal
       `<script defer src>` into the parser on 2026-08-07. */
    const outsideBlocks = html.replace(/<(style|script)\b[\s\S]*?<\/\1>/g, '');
    if (/\n\s*\*\s/.test(outsideBlocks)) {
      leaks.push(`${rel} — PHP doc-comment prose emitted as markup`);
    }
  }
  if (leaks.length) {
    throw new Error(`PHP comment/close-tag leaked into markup at ${[...new Set(leaks)].slice(0, 6).join(', ')}`);
  }
  return `${files.length} interleaved templates; mode machine clean`;
});

/* ══ P1–P3, 2026-08-07 ═══════════════════════════════════════════════════════
   Packaging assertions. The lightbox worked on /eventos and /portal but could
   not be loaded by any OTHER plugin, and its selector contract was triplicated.
   These four guard the fix. ══ */

/* E11 · the public enqueue API registers everything the shell needs. */
check('E11 · apollo_event_lightbox_enqueue() is a complete, idempotent contract', () => {
  const php = readFileSync(RENDER_SINGLE, 'utf8');
  if (!/function apollo_event_lightbox_enqueue\(\)/.test(php)) {
    throw new Error('no public enqueue API — another plugin has no way in');
  }
  /* Everything apollo_event_lightbox_boot() prints inline must also be
     reachable through the enqueue path, or a plugin that calls the API gets a
     half-loaded lightbox. */
  const fn = php.slice(php.indexOf('function apollo_event_lightbox_enqueue()'));
  const body = fn.slice(0, fn.indexOf('\n}\n') + 3);
  if (!body.includes('apollo_event_enqueue_single_assets( true )')) {
    throw new Error('enqueue API does not pull the single-event assets + lightbox runtime');
  }
  if (!body.includes('apollo_event_lightbox_styles()') || !body.includes('wp_add_inline_style')) {
    throw new Error('enqueue API ships no full-viewport override CSS → 560px card, not a page');
  }
  if (!body.includes("apollo_event_lightbox_state( 'armed' )")) {
    throw new Error('enqueue API never arms the wp_footer auto-boot → no shell, dead data-ev-open');
  }
  /* Idempotence: a second call must not double-enqueue. */
  if (!/\$state\['assets'\]\s*\)\s*\{\s*return;/.test(body)) {
    throw new Error('enqueue API is not idempotent — ten widgets would queue it ten times');
  }
  /* The lightbox runtime must depend on the single-event runtime, not race it. */
  if (!/'apollo-event-lightbox',[\s\S]{0,220}array\( 'apollo-single-event' \)/.test(php)) {
    throw new Error('apollo-event-lightbox is not declared dependent on apollo-single-event');
  }
  return 'assets + override CSS + footer arm, guarded, deps declared';
});

/* E12 · ONE shell per document — the double-print the runtime cannot survive. */
check('E12 · every boot path shares one print-once ledger', () => {
  const php = readFileSync(RENDER_SINGLE, 'utf8');
  if (!/function apollo_event_lightbox_state\(/.test(php)) {
    throw new Error('no shared ledger — the three boot paths cannot see each other');
  }
  /* No path may keep a private static, or it becomes invisible to the others. */
  for (const fn of ['apollo_event_lightbox_shell', 'apollo_event_lightbox_boot']) {
    const at = php.indexOf(`function ${fn}(`);
    const body = php.slice(at, php.indexOf('\n}\n', at));
    if (/static\s+\$printed/.test(body)) {
      throw new Error(`${fn}() still owns a private static — reintroduces the double-shell bug`);
    }
    if (!body.includes('apollo_event_lightbox_state(')) {
      throw new Error(`${fn}() does not consult the shared ledger`);
    }
  }
  /* The footer hook must stand down when a template already booted inline —
     /eventos calls apollo_event_lightbox_boot() directly. */
  const at = php.indexOf('function apollo_event_lightbox_footer_boot(');
  const boot = php.slice(at, php.indexOf('\n}\n', at));
  if (!/\$state\['armed'\]/.test(boot) || !/\$state\['shell'\]/.test(boot)) {
    throw new Error('footer auto-boot is not guarded on both armed AND shell → /eventos gets two shells');
  }
  if (!/add_action\(\s*'wp_footer',\s*'apollo_event_lightbox_footer_boot'/.test(php)) {
    throw new Error('footer auto-boot is never hooked');
  }
  /* The CSS cell is reachable from two paths now; it must claim the ledger. */
  const cell = readFileSync(join(PORTAL, 'styles-lightbox.php'), 'utf8');
  if (!cell.includes("apollo_event_lightbox_state( 'css' )")) {
    throw new Error('styles-lightbox.php does not claim the ledger → CSS printed twice off-portal');
  }
  return 'shell/assets/css/armed ledger; footer hook stands down for inline boot';
});

/* E13 · the reveal selector list has exactly one owner. */
check('E13 · reveal selectors come from PHP, not three hand-synced copies', () => {
  if (REVEAL_ANIM.length < 5) throw new Error('APOLLO_EVENT_REVEAL_ANIM looks empty or unparsed');

  const js = readFileSync(join(PLUGIN, 'assets/js/apollo-single-event.js'), 'utf8');
  const boot = readFileSync(join(PORTAL, 'bootstrap.php'), 'utf8');
  const cell = readFileSync(join(PORTAL, 'styles-lightbox.php'), 'utf8');

  if (!/w\.APOLLO_EVENT_REVEAL\s*&&\s*w\.APOLLO_EVENT_REVEAL\.anim/.test(js)) {
    throw new Error('apollo-single-event.js does not read the published contract');
  }
  if (!/window\.APOLLO_EVENT_REVEAL\s*&&\s*window\.APOLLO_EVENT_REVEAL\.floor/.test(boot)) {
    throw new Error('bootstrap.php does not read the published contract');
  }
  if (!cell.includes('apollo_event_reveal_css_selector')) {
    throw new Error('styles-lightbox.php still hard-codes the floor selector list');
  }
  /* The literals that remain must be fallbacks only — one per consumer. */
  const hits = (s) => (s.match(/\.ev-spotify,\.ev-gallery/g) || []).length;
  if (hits(js) > 1 || hits(boot) > 1) {
    throw new Error('a hand-copied selector list survives outside the offline fallback');
  }
  /* And the published contract must actually reach the browser on both paths. */
  const php = readFileSync(RENDER_SINGLE, 'utf8');
  if (!php.includes("'APOLLO_EVENT_REVEAL',")) throw new Error('enqueue path never localizes the contract');
  if (!/function apollo_event_reveal_js_config\(/.test(php)) throw new Error('inline boot path never publishes the contract');
  return `${REVEAL_ANIM.length} anim / ${REVEAL_FLOOR.length} floor selectors, one PHP owner`;
});

/* E14 · scenes animate in AND reverse out, without stranding content. */
check('E14 · reveals play on enter and reverse on leave', () => {
  const js = readFileSync(join(PLUGIN, 'assets/js/apollo-single-event.js'), 'utf8');
  if (!/toggleActions:\s*'play none none reverse'/.test(js)) {
    throw new Error('no reverse leg — scenes still fire once and die');
  }
  /* Reverse is only safe while the scroller is resolved per instance; a
     non-once trigger against `scroller: window` inside the Lenis-locked panel
     never resolves and strands its element at inline opacity:0 forever. */
  const at = js.indexOf('function stCfg(');
  const cfg = js.slice(at, js.indexOf('\n  }', at));
  if (!cfg.includes('scroller: self.scroller')) {
    throw new Error('reverse enabled without a resolved scroller — reintroduces stranded content');
  }
  /* Scoped to scrollTrigger objects — `{ once: true }` is also a legitimate
     addEventListener option elsewhere in this file. */
  if (/scrollTrigger:\s*\{[^}]*once:\s*true/.test(js)) {
    throw new Error('a scroll scene still carries once:true');
  }
  /* The anti-strand net must not eat the animation it is protecting: rescuing
     a below-the-fold element nails it to opacity:1 !important before its
     trigger ever fires. */
  const boot = stripTags(emit(join(PORTAL, 'bootstrap.php')));
  if (!boot.includes('getBoundingClientRect')) {
    throw new Error('unstrand() is unbounded — it force-visibles every below-fold reveal and deletes the animation');
  }
  if (!/r\.bottom <= box\.top \|\| r\.top >= box\.bottom/.test(boot)) {
    throw new Error('unstrand() does not restrict rescue to the panel viewport');
  }
  return 'play/reverse on resolved scroller; rescue bounded to the panel viewport';
});

/* ══ MONTH CHANGE ═══════════════════════════════════════════════════════════
   The two default rails used to ignore `ym` entirely: buildRails received it and
   never read it, so December rendered August's today-windows under the labels
   "Esse FDS" and "Próximos eventos". The screen was not stale — it was wrong,
   and it was wrong in the one place a user goes to check what is happening in a
   month they chose.

   M1 runs the SHIPPED helpers.php against the real fixture rather than asserting
   on its source text: the whole point is the returned pools, and a regex over
   `title:` would keep passing if the branch stopped selecting the right one. ══ */

/** Boot the shipped helpers.php in a bare window and return APOLLO_PORTAL. */
const portalApi = (events) => {
  const win = { APOLLO_EVENTS: events };
  // eslint-disable-next-line no-new-func
  new Function('window', `${helpersSrc}`).call(win, win);
  if (!win.APOLLO_PORTAL) throw new Error('helpers.php did not publish window.APOLLO_PORTAL');
  return win.APOLLO_PORTAL;
};

const CURRENT_YM = (() => { const t = new Date(); return `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}`; })();

check('M1 · buildRails is month-aware for the two default rails', () => {
  const P = portalApi(FIXTURE);
  const pick = (rails, key) => {
    const r = rails.find((x) => x.key === key);
    if (!r) throw new Error(`buildRails dropped the '${key}' rail — it is a data contract`);
    return r;
  };

  /* CURRENT MONTH — the today-relative regime is unchanged. */
  const now = P.buildRails(CURRENT_YM, 'mes');
  const nowA = pick(now, 'neste-fds');
  const nowB = pick(now, 'prox-fds');
  if (nowA.title !== 'Esse FDS') throw new Error(`current month rail 1 is "${nowA.title}", expected "Esse FDS"`);
  if (nowB.title !== 'Próximos eventos') throw new Error(`current month rail 2 is "${nowB.title}"`);
  if (nowB.visible === false) throw new Error('prox-fds is hidden in the current month — it is the whole point of the current month');
  const windowIds = P.filterByMode('neste-fds', P.todayStart(), FIXTURE).map((e) => e.id);
  if (nowA.eventIds.join() !== windowIds.join()) {
    throw new Error('current-month rail 1 no longer uses the hoje→+4 window');
  }

  /* OTHER MONTH — rail 1 becomes the month list, rail 2 leaves. */
  const other = P.buildRails(OTHER_YM, 'mes');
  const othA = pick(other, 'neste-fds');
  const othB = pick(other, 'prox-fds');
  if (othA.title !== 'Neste mês') throw new Error(`other-month rail 1 is "${othA.title}", expected "Neste mês"`);
  if (othA.key !== 'neste-fds' || othB.key !== 'prox-fds') {
    throw new Error('a key was renamed with the label — filterByMode, the header period group and sectionViews all match on it');
  }

  /* Day 01 → end of month, ascending. The fixture is typed 24, 05, 31. */
  const monthIds = P.byMonth(OTHER_YM).map((e) => e.id);
  if (othA.eventIds.join() !== monthIds.join()) {
    throw new Error(`other-month rail 1 pool is [${othA.eventIds}], expected byMonth() → [${monthIds}]`);
  }
  const days = othA.eventIds.map((id) => FIXTURE.find((e) => e.id === id).startDate.slice(8));
  if (days.join() !== [...days].sort().join()) throw new Error(`month list is not ascending by day: ${days.join(' → ')}`);
  for (const id of othA.eventIds) {
    if (!FIXTURE.find((e) => e.id === id).startDate.startsWith(OTHER_YM)) {
      throw new Error('a foreign-month event leaked into the month list');
    }
  }

  /* Rail 2 must be empty AND hidden — either one alone is a bug. Empty-only
     leaves a full band of chrome and an empty state for nothing; hidden-only
     keeps a stale pool alive behind the lightbox. */
  if (othB.eventIds.length) throw new Error(`prox-fds still carries ${othB.eventIds.length} events outside the current month`);
  if (othB.visible !== false) throw new Error('prox-fds is not marked invisible outside the current month — the gap comes back');

  return `now: "${nowA.title}"+"${nowB.title}"; ${OTHER_YM}: "${othA.title}" ${othA.eventIds.length} evts (days ${days.join(',')}), prox hidden`;
});

check('M2 · renderRails does surgery, never a full #pevRails wipe', () => {
  const src = stripComments(appSrc);
  const at = src.indexOf('function renderRails()');
  if (at < 0) throw new Error('renderRails() not found');
  let depth = 0, started = false, j = at;
  for (; j < src.length; j++) {
    if (src[j] === '{') { depth++; started = true; }
    else if (src[j] === '}') { depth--; if (started && depth === 0) { j++; break; } }
  }
  const body = src.slice(at, j);

  /* The wipe is allowed EXACTLY once, behind the first-paint guard. An
     unconditional one is what made the title morph and the collapse impossible:
     you cannot animate a node that was just destroyed and rebuilt. */
  const wipes = [...body.matchAll(/host\.innerHTML\s*=/g)].length;
  if (wipes !== 1) throw new Error(`${wipes} \`host.innerHTML =\` in renderRails — expected exactly 1, inside the first-paint branch`);
  if (!/if \(!mounted\) \{\s*host\.innerHTML/.test(body)) {
    throw new Error('the #pevRails wipe is not guarded by the first-paint check');
  }
  for (const need of ['morphRailTitle(', 'collapseRail(', 'expandRail(', "querySelector('[data-rail=\"'"]) {
    if (!body.includes(need)) throw new Error(`renderRails no longer calls ${need}`);
  }
  /* A feed mounted inside a display:none section never gets an intersection. */
  if (!/if \(visible && evs\.length\) mountSectionFeed/.test(body)) {
    throw new Error('renderRails mounts feeds without checking visibility — an observer in a hidden section never fires');
  }
  if (!/lastDefaultRails\[rail\.key\] = rail/.test(body)) {
    throw new Error('renderRails no longer publishes lastDefaultRails — the lightbox falls back to hard-coded copy');
  }
  return `1 guarded wipe; morph + collapse + expand keyed by data-rail`;
});

check('M3 · the title mask reuses the header recipe, not a second one', () => {
  /* The wipe is the listing header's, note for note. If the header ever retunes
     it, this fails rather than letting the two drift into near-identical. */
  const kernel = readFileSync(join(TEMPLATES, 'templates/template-parts/listing-header/kernel-scripts.php'), 'utf8');
  const recipe = (s) => {
    const out = /clipPath:\s*'inset\(0 100% 0 0\)',\s*duration:\s*([\d.]+),\s*ease:\s*'([\w.]+)'/.exec(s);
    const back = /clipPath:\s*'inset\(0 0% 0 0\)',\s*duration:\s*([\d.]+),\s*ease:\s*'([\w.]+)'/.exec(s);
    if (!out || !back) throw new Error('no clip-path wipe found');
    return { out: `${out[1]}/${out[2]}`, back: `${back[1]}/${back[2]}` };
  };
  const header = recipe(kernel);
  const rail = recipe(stripComments(appSrc));
  if (header.out !== rail.out || header.back !== rail.back) {
    throw new Error(`rail wipe is ${rail.out} → ${rail.back}; header is ${header.out} → ${header.back}`);
  }
  /* Motion may never be the only carrier of the label. */
  const at = stripComments(appSrc).indexOf('function morphRailTitle(');
  const body = stripComments(appSrc).slice(at, at + 700);
  if (!/if \(!w\.gsap \|\| reducedMotion\(\)\)[^;]*textContent = next/.test(body)) {
    throw new Error('morphRailTitle has no instant-swap fallback for reduced motion / no GSAP');
  }
  return `out ${rail.out}, back ${rail.back} — same as paintMonth`;
});

check('M4 · a collapsed rail leaves the flex flow, gap included', () => {
  const css = emit(join(PORTAL, 'styles-rails.php')).replace(/\/\*[\s\S]*?\*\//g, '');
  if (!/\.pev-rail\[hidden\]\s*\{[^}]*display:\s*none/.test(css)) {
    throw new Error('styles-rails.php does not take [hidden] rails out of layout — .pev-rails gap keeps the hole');
  }
  if (!/\.pev-rail\.is-exiting\s*\{[^}]*overflow:\s*hidden/.test(css)) {
    throw new Error('.is-exiting does not clip — animating height on a visible-overflow box shows the content shrinking past its box');
  }
  const title = /\[data-rail-title\]\s*\{([^}]*)\}/.exec(css);
  if (!title) throw new Error('no [data-rail-title] rule — the mask has no target');
  if (!/display:\s*inline-block/.test(title[1])) {
    throw new Error('[data-rail-title] is not inline-block — clip-path on an inline box clips line boxes, not the word');
  }
  /* The rest of the collapse is JS-driven, so the settle step is what matters:
     without the attribute the section animates to height 0 and still bills the
     column for a full --pev-band. */
  if (!/setAttribute\('hidden', ''\)/.test(stripComments(appSrc))) {
    throw new Error('collapseRail never sets [hidden] — height 0 still occupies a gap');
  }
  return '[hidden] → display:none; is-exiting clips; title is inline-block';
});

check('M5 · the lightbox has no second copy of the rail copy', () => {
  const src = stripComments(appSrc);
  const at = src.indexOf('function openRailLightbox(');
  const body = src.slice(at, src.indexOf('\n    function closeRailLightbox', at));
  if (!body.includes('lastDefaultRails[key]')) {
    throw new Error('openRailLightbox does not read the rendered rail — it would recompute the window and disagree with the screen');
  }
  /* The literals may survive ONLY in the pre-first-render fallback, after the
     lastDefaultRails branch. Before it, they are a second owner. */
  const mirror = body.indexOf('lastDefaultRails[key]');
  const hard = body.indexOf("'Esse FDS'");
  if (hard >= 0 && hard < mirror) {
    throw new Error("'Esse FDS' is decided before the rendered rail is consulted");
  }
  return 'mirrors the rendered rail; literals only in the pre-render fallback';
});

/* D · single-owner — the bug class that caused the collision. */
check('D · no selector declared by two style cells', () => {
  const owners = new Map();
  for (const slug of CELLS) {
    const css = emit(join(PORTAL, `styles-${slug}.php`)).replace(/\/\*[\s\S]*?\*\//g, '');
    for (const m of css.matchAll(/(^|[}])\s*([^{}@]+?)\s*\{/g)) {
      for (let sel of m[2].split(',')) {
        sel = sel.trim();
        if (!sel || sel.startsWith('@') || sel.startsWith('from') || sel.startsWith('to') || /^\d/.test(sel)) continue;
        if (!owners.has(sel)) owners.set(sel, new Set());
        owners.get(sel).add(slug);
      }
    }
  }
  const contested = [...owners].filter(([, s]) => s.size > 1)
    /* rhythm is the declared LAST-WORD cell for band geometry; it is allowed
       to re-declare the gutters/gaps it explicitly documents owning. */
    .filter(([, s]) => !(s.size === 2 && s.has(LAST_WORD_CELL)));
  if (contested.length) throw new Error(contested.map(([sel, s]) => `${sel} ← ${[...s].join('+')}`).join(' ; '));
  const shared = [...owners].filter(([, s]) => s.size > 1).map(([sel]) => sel);
  return `${owners.size} selectors; ${shared.length} intentionally overridden by ${LAST_WORD_CELL}`;
});

check('D · .pev-chrome has no live declaration anywhere', () => {
  const live = CELLS.map((s) => [s, emit(join(PORTAL, `styles-${s}.php`)).replace(/\/\*[\s\S]*?\*\//g, '')])
    .filter(([, css]) => /\.pev-chrome/.test(css));
  if (live.length) throw new Error(`still declared in: ${live.map((l) => l[0]).join(', ')}`);
  if (/\.pev-chrome/.test(shellFit.replace(/\/\*[\s\S]*?\*\//g, ''))) throw new Error('still declared in styles.php shell-fit');
  return 'comments only';
});

/* Button SURFACE is owned by styles-btn-force.php (apollo-pev-btn-force).
   Other cells may not declare `.btn` or re-paint idle `.pev-tax-btn` chrome.
   browse keeps scroller / truncate / `.is-on` only. */
check('D · btn-force owns .btn; other cells never fork idle chips', () => {
  const forks = (p) =>
    p === 'background' || p.startsWith('background-') ||
    p === 'border' || p.startsWith('border-') ||
    p === 'box-shadow' || p === 'color' || p === 'corner-shape' || p === 'transform' ||
    p === 'font' || p.startsWith('font-') ||
    p === 'padding' || p.startsWith('padding-') ||
    p === 'line-height' || p === 'letter-spacing' || p === 'text-transform';

  const force = emit(join(PORTAL, 'styles-btn-force.php')).replace(/\/\*[\s\S]*?\*\//g, '');
  if (!/--fsx:\s*var\(--fs-u/.test(force)) throw new Error('btn-force missing --fsx: var(--fs-u)');
  if (!/\.ax-main\s+\.btn\s*\{[^}]*11px/.test(force)) throw new Error('btn-force .ax-main .btn must use 11px');
  if (!/font-weight:\s*400\s*!important/.test(force)) throw new Error('btn-force .ax-main .btn must set font-weight 400');
  if (!/\.btn-secondary\s*\{/.test(force)) throw new Error('btn-force missing .btn-secondary');
  const loader = readFileSync(join(PORTAL, 'styles.php'), 'utf8');
  if (!/'btn-force'/.test(loader)) throw new Error('styles.php does not load btn-force');

  const offenders = [];
  let chipRules = 0;
  for (const slug of CELLS) {
    if (slug === 'btn-force') continue; /* sole owner of the .btn layer */
    const css = emit(join(PORTAL, `styles-${slug}.php`)).replace(/\/\*[\s\S]*?\*\//g, '');
    for (const m of css.matchAll(/([^{}@]+?)\s*\{([^{}]*)\}/g)) {
      const sel = m[1].trim();
      if (/(^|[\s,>+~])\.btn\b/.test(sel)) offenders.push(`${slug}: "${sel}" declares .btn (owned by btn-force)`);
      if (!/\.pev-tax-btn\b/.test(sel)) continue;
      chipRules++;
      const selected = /\.is-on\b/.test(sel);
      for (const decl of m[2].split(';')) {
        const prop = decl.split(':')[0].trim().toLowerCase();
        if (!prop) continue;
        /* Selected invert (black/white): surface + type only, never accent. */
        if (selected && (prop === 'background' || prop === 'border-color' || prop === 'color' || prop === 'box-shadow')) continue;
        /* Idle chips may carry the shared .65s ease for the invert transition. */
        if (prop === 'transition') continue;
        if (forks(prop)) offenders.push(`${slug}: "${sel}" sets ${prop}`);
      }
    }
  }
  if (offenders.length) throw new Error(offenders.join(' ; '));
  return `btn-force owns .btn @11px/400; ${chipRules} chip deltas clean`;
});

check('D · .pev-host selector now matches real markup', () => {
  const tpl = readFileSync(join(PLUGIN, 'styles/base/archive-event.php'), 'utf8');
  if (!/id="apollo-portal-root"[\s\S]{0,80}class="pev-host"/.test(tpl)) throw new Error('archive-event.php root div lacks class="pev-host"');
  return 'class="pev-host" on #apollo-portal-root';
});

/* ═══════════════════════════════ REPORT ═══════════════════════════════════ */
const pad = (s, n) => (s + ' '.repeat(n)).slice(0, n);
console.log('\n  PORTAL HARNESS — structural verification\n  ' + '─'.repeat(74));
for (const r of results) console.log(`  ${r.ok ? 'PASS' : r.skip ? 'SKIP' : 'FAIL'}  ${pad(r.name, 48)} ${r.detail}`);
const failed = results.filter((r) => !r.ok && !r.skip).length;
const skipped = results.filter((r) => r.skip).length;
console.log('  ' + '─'.repeat(74));
console.log(`  ${results.length - failed - skipped}/${results.length - skipped} passed`
  + (skipped ? `, ${skipped} skipped (environment)` : '')
  + ' → _sandbox/portal-harness.html\n');
process.exit(failed ? 1 : 0);
