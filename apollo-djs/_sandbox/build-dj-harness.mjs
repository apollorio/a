/**
 * DJ Single — sandbox harness builder + structural verifier.
 *
 * WHY: the page is composed from 18 cells and finished client-side by a runtime
 * that queries ~40 ids. There is no PHP binary and no headless browser here, so
 * "it renders" cannot be proven — but everything that historically broke this
 * kind of screen CAN be:
 *
 *   A · every emitted <script> parses
 *   B · the markup the cells emit is tag-balanced
 *   C · every #id the runtime queries exists in the markup that builds it
 *   D · no selector is declared by two cells
 *   E · the cells still match the approved mockup (no hand-edit drift)
 *   F · the data contract matches the registry's meta keys
 *
 * Run:  node apollo-djs/_sandbox/build-dj-harness.mjs
 * Out:  _sandbox/dj-harness.html  +  a PASS/FAIL report
 */

import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const HERE = dirname(fileURLToPath(import.meta.url));
const PLUGIN = join(HERE, '..');
const CELLS_DIR = join(PLUGIN, 'styles/base/template-parts/single');
const MOCKUP = join(HERE, 'dj-mockup.html');
const RENDER = join(PLUGIN, 'includes/render-single.php');
const REGISTRY = join(PLUGIN, '../_inventory/registry/09-plugins/apollo-djs.json');

/* Cell order — mirrors APOLLO_DJ_SINGLE_PARTS, parsed from the PHP so the two
   cannot disagree about what the page is made of. */
const PARTS = (() => {
  const php = readFileSync(RENDER, 'utf8');
  const block = php.slice(php.indexOf("'APOLLO_DJ_SINGLE_PARTS'"), php.indexOf('function apollo_dj_can_view'));
  return [...block.matchAll(/'([a-z-]+)',/g)].map((m) => m[1]);
})();

const cell = (n) => readFileSync(join(CELLS_DIR, `${n}.php`), 'utf8');
/** Strip the PHP header/guard, keep what the cell emits. */
const emitted = (n) => {
  const s = cell(n);
  const i = s.indexOf('?>\n');
  return i < 0 ? s : s.slice(i + 3);
};
/** Drop PHP tags so the residue can be treated as markup. */
const markupOf = (n) => emitted(n)
  .replace(/<\?php[\s\S]*?\?>/g, '')
  .replace(/<\?=[\s\S]*?\?>/g, '')
  /* HTML comments carry prose that mentions tags — the mockup's own notes say
     "primeiro nó do <body>" — and a naive tag scan reads those as real nodes. */
  .replace(/<!--[\s\S]*?-->/g, '');

let pass = 0;
const rows = [];
const check = (name, fn) => {
  try { rows.push(['PASS', name, fn() || '']); pass++; }
  catch (e) { rows.push(['FAIL', name, e.message]); }
};

/* ── A · JS parses ───────────────────────────────────────────────────────── */
check('A · runtime parses', () => {
  const js = emitted('scripts').replace(/^<script[^>]*>/, '').replace(/<\/script>\s*$/, '');
  new Function(js);
  return `${js.length} chars`;
});

check('A · data cell emits valid JSON assignment', () => {
  const s = emitted('data');
  if (!/window\.APOLLO_DJ = <\?php echo wp_json_encode\( \$dj_payload \); \?>;/.test(s)) {
    throw new Error('data cell does not publish $dj_payload via wp_json_encode');
  }
  if (/<\?php\s+echo\s+\$/.test(s.replace(/wp_json_encode/g, ''))) {
    throw new Error('data cell interpolates a raw variable — must go through wp_json_encode');
  }
  return 'wp_json_encode( $dj_payload )';
});

/* ── B · markup balance ──────────────────────────────────────────────────── */
check('B · every markup cell is tag-balanced', () => {
  const VOID = new Set(['img', 'br', 'hr', 'input', 'meta', 'link', 'source', 'track', 'wbr']);
  const bad = [];
  for (const p of PARTS) {
    if (p === 'styles' || p === 'scripts' || p === 'data') continue;
    const stack = [];
    for (const m of markupOf(p).matchAll(/<(\/?)([a-zA-Z][\w-]*)[^>]*?(\/?)>/g)) {
      const [, close, tag, self] = m;
      if (VOID.has(tag.toLowerCase()) || self) continue;
      if (close) { if (stack.pop() !== tag) bad.push(`${p}: </${tag}> mismatched`); }
      else stack.push(tag);
    }
    if (stack.length) bad.push(`${p}: unclosed <${stack.join('>, <')}>`);
  }
  if (bad.length) throw new Error(bad.slice(0, 4).join(' · '));
  return `${PARTS.length - 3} cells balanced`;
});

/* ── A · PHP control-flow balance ─────────────────────────────────────────── */
check('A · every alternative-syntax block is closed', () => {
  /* THE CHECK THAT WAS MISSING, 2026-08-17. A substitution opened
     `<?php if ( … ) : ?>` inside a <p> and left the matching `<?php endif; ?>`
     to a second rule that never fired. Two cells shipped an unterminated if,
     which is a PHP fatal, and /dj/{slug} returned an EMPTY RESPONSE in
     production. Brace-balance linting is blind to it — alternative syntax has
     no braces — so nothing caught it before the browser did. */
  const bad = [];
  for (const p of PARTS) {
    const s = cell(p);
    const pairs = [['if\\s*\\(', 'endif'], ['foreach\\s*\\(', 'endforeach'],
                   ['\\bfor\\s*\\(', 'endfor'], ['while\\s*\\(', 'endwhile'],
                   ['switch\\s*\\(', 'endswitch']];
    for (const [open, close] of pairs) {
      /* Only alternative syntax counts: `<?php if ( … ) :` ending in a colon.
         A brace-style if in the same file is balanced by the brace linter. */
      const o = (s.match(new RegExp(`<\\?php\\s+${open}[^?]*?\\)\\s*:`, 'g')) || []).length;
      const c = (s.match(new RegExp(`${close}\\s*;`, 'g')) || []).length;
      if (o !== c) bad.push(`${p}: ${o} ${open.replace(/\\\\[sb(*]|\\\\/g, '')} vs ${c} ${close}`);
    }
  }
  if (bad.length) throw new Error(`unterminated PHP block — fatal at render: ${bad.join(' · ')}`);
  return `${PARTS.length} cells, all control flow closed`;
});

/* ── C · DOM contract ────────────────────────────────────────────────────── */
const allMarkup = PARTS.filter((p) => !['styles', 'scripts', 'data'].includes(p)).map(markupOf).join('\n');

check('C · every id the runtime queries exists in the cells', () => {
  const js = emitted('scripts');
  const wanted = new Set([...js.matchAll(/q\('#([A-Za-z0-9_-]+)'/g)].map((m) => m[1]));
  /* Nodes the runtime CREATES rather than finds are not a contract violation. */
  const created = new Set([...js.matchAll(/id\s*=\s*['"]([A-Za-z0-9_-]+)['"]/g)].map((m) => m[1]));
  const missing = [...wanted].filter((id) => !created.has(id) && !allMarkup.includes(`id="${id}"`));
  if (missing.length) throw new Error(`runtime queries ids no cell emits: ${missing.join(', ')}`);
  return `${wanted.size} ids resolved`;
});

check('C · runtime mount points are present and empty', () => {
  /* These are filled client-side; a cell that pre-fills one would double-render. */
  for (const id of ['poTrack', 'roster', 'trkList', 'outNowLbList', 'mqInner', 'aboutFig']) {
    const re = new RegExp(`id="${id}"[^>]*>\\s*</`);
    if (!re.test(allMarkup)) throw new Error(`#${id} is missing or not empty in the markup`);
  }
  return '6 mount points empty';
});

/* ── D · single-owner ────────────────────────────────────────────────────── */
check('D · styles is the only cell declaring CSS', () => {
  const offenders = PARTS.filter((p) => p !== 'styles' && /<style[\s>]/.test(emitted(p)));
  if (offenders.length) throw new Error(`${offenders.join(', ')} declare <style> — styles.php is the single owner`);
  return 'one style owner';
});

check('D · scripts and data are the only cells with <script>', () => {
  const offenders = PARTS.filter((p) => !['scripts', 'data'].includes(p) && /<script[\s>]/.test(emitted(p)));
  if (offenders.length) throw new Error(`${offenders.join(', ')} emit <script>`);
  return 'behaviour isolated from markup';
});

/* ── The rule the skill puts first ───────────────────────────────────────── */
check('D · no core.js token is redeclared in :root', () => {
  const css = emitted('styles');
  const root = (css.match(/:root\s*\{[\s\S]*?\}/) || [''])[0];
  const CORE = ['--rgb-theme', '--rgb-diff', '--rgb-canvas', '--rgb-ink', '--bg', '--card', '--glass',
    '--txt', '--muted', '--accent', '--primary', '--surface', '--surface-2', '--ff-main', '--ff-mono',
    '--ff-heading', '--ff-display', '--ff-serif', '--r', '--r-sm', '--r-lg', '--r-pill', '--ease'];
  const forked = CORE.filter((t) => new RegExp(`(^|[;{\\s])${t}\\s*:`).test(root));
  if (forked.length) {
    throw new Error(`:root redeclares core.js tokens (${forked.join(', ')}) — forks the token system`);
  }
  if (!/--evtop-h/.test(root)) throw new Error(':root lost --evtop-h, which core.js does not provide');
  return `${(root.match(/--[a-z-]+\s*:/g) || []).length} page-scoped tokens, zero core tokens`;
});

/* ── E · fidelity to the approved mockup ─────────────────────────────────── */
check('E · cells still match the mockup (no hand-edit drift)', () => {
  if (!existsSync(MOCKUP)) throw new Error('mockup missing — cells can no longer be verified against the design');
  const mk = readFileSync(MOCKUP, 'utf8');
  /* Cells are namespaced (.wrap -> .dj-wrap), so compare the ORIGINAL name back
     against the mockup. A class whose un-prefixed form is absent from the mockup
     is markup someone typed by hand instead of regenerating. */
  const classes = new Set();
  for (const m of allMarkup.matchAll(/class="([^"<>]*)"/g)) {
    m[1].split(/\s+/).filter(Boolean).forEach((c) => {
      if (!c.includes('<') && !c.includes('$')) classes.add(c);
    });
  }
  const alien = [...classes].filter((c) => {
    if (c.startsWith('ri-')) return false;
    const bare = c.replace(/^dj-/, '');
    return !mk.includes(bare);
  });
  if (alien.length) throw new Error(`classes absent from the mockup: ${alien.slice(0, 6).join(', ')}`);
  return `${classes.size} classes, all traceable to the mockup`;
});

/* ── The fix for "quite far away from the mockup" ─────────────────────────── */
check('E · every class is namespaced — nothing can collide', () => {
  const bad = [];
  for (const m of allMarkup.matchAll(/class="([^"<>]*)"/g)) {
    for (const c of m[1].split(/\s+/)) {
      if (!c || c.includes('<') || c.includes('$') || c.includes('?')) continue;
      if (!c.startsWith('dj-') && !c.startsWith('ri-')) bad.push(c);
    }
  }
  if (bad.length) {
    throw new Error(`un-namespaced classes would collide with the DS: ${[...new Set(bad)].slice(0, 8).join(', ')}`);
  }
  const css = emitted('styles');
  /* Every class SELECTOR must be dj-* too, or the stylesheet reaches outside
     this page and the DS reaches in. */
  /* Scan selectors only: @font-face bodies and url() values are full of dots
     that are not class selectors (assets.apollo.rio.br, .woff2, .otf, core.js). */
  const scan = css
    .replace(/@font-face\s*\{[\s\S]*?\}/g, '')
    .replace(/url\([^)]*\)/g, '')
    .replace(/\/\*[\s\S]*?\*\//g, '')
    .replace(/\{[^{}]*\}/g, '{}');
  const sel = [...scan.matchAll(/\.(-?[A-Za-z_][\w-]*)/g)].map((x) => x[1]);
  const leak = [...new Set(sel)].filter((c) => !c.startsWith('dj-') && !c.startsWith('ri-'));
  if (leak.length) throw new Error(`stylesheet declares un-namespaced selectors: ${leak.slice(0, 8).join(', ')}`);
  return 'markup + stylesheet fully under .dj-*';
});

check('E · bare element rules are scoped, and the wrapper exists', () => {
  const css = emitted('styles').replace(/\/\*[\s\S]*?\*\//g, '');
  const unscoped = [];
  for (const m of css.matchAll(/(^|\})\s*([^{}@]+?)\s*\{/g)) {
    for (const one of m[2].split(',')) {
      const s = one.trim();
      const head = /^([a-zA-Z][\w-]*)/.exec(s);
      if (head && !['html', 'body', 'from', 'to', 'iframe'].includes(head[1])) unscoped.push(s);
    }
  }
  if (unscoped.length) {
    throw new Error(`element rules not scoped under .dj-page (theme will fight them): ${unscoped.slice(0, 5).join(' · ')}`);
  }
  /* Scoped CSS is useless if nothing carries the scope. */
  const render = readFileSync(RENDER, 'utf8');
  if (!/class="dj-page"/.test(render)) {
    throw new Error('renderer does not wrap output in .dj-page — every scoped rule would miss');
  }
  return `${(css.match(/\.dj-page /g) || []).length} scoped element rules, wrapper emitted by the renderer`;
});

check('E · generator is present and cells declare themselves generated', () => {
  if (!existsSync(join(HERE, 'build-dj-cells.py'))) throw new Error('build-dj-cells.py missing — cells cannot be regenerated');
  const unmarked = PARTS.filter((p) => !/GENERATED — do not hand-edit/.test(cell(p)));
  if (unmarked.length) throw new Error(`cells missing the generated banner: ${unmarked.join(', ')}`);
  return `${PARTS.length} cells traceable to the generator`;
});

/* ── F · data contract vs the registry ───────────────────────────────────── */
check('F · every meta key the context reads is declared in the registry', () => {
  if (!existsSync(REGISTRY)) return 'registry chapter not present in this checkout — skipped';
  const reg = JSON.parse(readFileSync(REGISTRY, 'utf8'));
  const declared = new Set(Object.keys((reg.meta && reg.meta.post) || {}));
  if (!declared.size) throw new Error('registry declares no post meta for apollo-djs');
  const used = new Set([...readFileSync(RENDER, 'utf8').matchAll(/'(_dj_[a-z_]+|_loc_city|_event_[a-z_]+)'/g)].map((m) => m[1]));
  const undeclared = [...used].filter((k) => !declared.has(k));
  if (undeclared.length) throw new Error(`context reads meta absent from the registry: ${undeclared.join(', ')}`);
  return `${used.size} meta keys, all declared`;
});

check('F · context reuses shipped helpers instead of re-deriving', () => {
  const php = readFileSync(RENDER, 'utf8');
  /* These exist in includes/functions.php. Re-implementing any of them creates a
     second source of truth that disagrees the first time either changes. */
  for (const fn of ['apollo_dj_get_tracks', 'apollo_dj_compute_stats', 'apollo_dj_map_played_on',
                    'apollo_dj_get_played_with', 'apollo_dj_get_sounds', 'apollo_dj_get_banner']) {
    if (!php.includes(fn)) throw new Error(`${fn}() exists in the plugin but the context does not use it`);
  }
  if (/new WP_Query/.test(php)) throw new Error('context runs its own WP_Query — apollo_dj_get_past_events() already does this');
  return '6 shipped helpers reused, no re-derivation';
});

check('F · surface renderer is wired and the page composes cells', () => {
  const surface = readFileSync(join(PLUGIN, 'includes/surface.php'), 'utf8');
  if (!surface.includes("'renderer'  => 'apollo_dj_render_single'")) throw new Error('surface renderer not pointed at apollo_dj_render_single');
  const boot = readFileSync(join(PLUGIN, 'apollo-djs.php'), 'utf8');
  /* Compare the require STATEMENTS, not any mention of the filename — the
     comment above the require also names surface.php. */
  const reqLine = (f) => boot.split('\n').findIndex((l) => l.includes('require_once') && l.includes(f));
  if (reqLine('render-single.php') > reqLine('surface.php')) {
    throw new Error('render-single.php loads AFTER surface.php — the surface would register dormant');
  }
  const tpl = readFileSync(join(PLUGIN, 'styles/base/single-dj.php'), 'utf8');
  if (!tpl.includes('apollo_dj_render_single(')) throw new Error('page template does not compose the cells');
  /* Look for the legacy classes in a real class attribute, not in the docblock
     that explains what they were — prose about the old markup is documentation,
     not a regression. */
  if (/class=["'][^"']*a-dj-single__/.test(tpl)) {
    throw new Error('page template still emits the legacy monolith markup');
  }
  if (!existsSync(join(PLUGIN, 'styles/base/_legacy/single-dj.monolith.php'))) throw new Error('legacy template not preserved for rollback');
  return 'renderer wired, load order correct, legacy preserved';
});

/* ── harness artifact ────────────────────────────────────────────────────── */
const html = `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>DJ single — harness</title>
<script src="https://cdn.apollo.rio.br/v1.0.0/core.js" fetchpriority="high"></script>
${emitted('styles')}
</head><body>
${PARTS.filter((p) => !['styles', 'scripts', 'data'].includes(p)).map(markupOf).join('\n')}
<script>window.APOLLO_DJ = ${JSON.stringify({
  name: 'Leo Janeiro', soundcloud: '', bandcamp: '', spotify: '', bookingEmail: '', mediaKitUrl: '',
  videoUrl: '', aboutPhoto: '', genres: ['Hard Groove', 'Peak Time'], playedOn: [], playedWith: [], tracks: []
})};</script>
${emitted('scripts')}
</body></html>`;
writeFileSync(join(HERE, 'dj-harness.html'), html);

const W = 54;
console.log('\n  DJ SINGLE — structural verification');
console.log('  ' + '─'.repeat(74));
for (const [st, name, note] of rows) {
  console.log(`  ${st}  ${name.slice(0, W).padEnd(W)} ${note}`);
}
console.log('  ' + '─'.repeat(74));
console.log(`  ${pass}/${rows.length} passed → _sandbox/dj-harness.html\n`);
process.exit(pass === rows.length ? 0 : 1);
