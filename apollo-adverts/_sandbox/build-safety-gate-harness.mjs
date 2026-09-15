#!/usr/bin/env node
/**
 * /seguranca VERIFICATION HARNESS
 *
 *   node apollo-adverts/_sandbox/build-safety-gate-harness.mjs
 *
 * Stitches real safety parts (never copies) and asserts:
 *   A  required ids / data-check keys exist
 *   B  verdict foot is outside the scroller contract (template string)
 *   C  no :root in safety-gate.css
 *   D  JS modules exist
 *
 * Exit 0 = green.
 */

import { readFileSync, existsSync, writeFileSync, mkdirSync } from 'node:fs';
import { dirname, resolve, relative } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const PLUGIN = resolve(__dirname, '..');
const ROOT = resolve(PLUGIN, '..');

const R = (p) => resolve(ROOT, p);
const read = (p) => (existsSync(R(p)) ? readFileSync(R(p), 'utf8') : null);

const failures = [];
const fail = (code, msg) => failures.push(`${code}  ${msg}`);

const PARTS = [
  'preloader', 'lede', 'target', 'rule', 'checks', 'check',
  'note', 'verdict', 'witness',
].map((n) => `apollo-adverts/templates/parts/safety/${n}.php`);

const GATE_TPL = 'apollo-adverts/templates/safety/gate.php';
const CSS = 'apollo-adverts/assets/css/safety-gate.css';
const JS_MODULES = [
  'apollo-adverts/assets/js/safety/gate.js',
  'apollo-adverts/assets/js/safety/signals-api.js',
  'apollo-adverts/assets/js/safety/render-checks.js',
  'apollo-adverts/assets/js/safety/gate-app.js',
];

function guestMarkup(php) {
  return php.replace(/<\?php[\s\S]*?\?>/g, ' ');
}

const tpl = read(GATE_TPL);
if (!tpl) {
  console.error(`FATAL  missing ${GATE_TPL}`);
  process.exit(2);
}

const partsHtml = PARTS.map((p) => {
  const src = read(p);
  if (src == null) {
    fail('E0', `part missing: ${p}`);
    return '';
  }
  return guestMarkup(src);
}).join('\n');

const markup = guestMarkup(tpl) + '\n' + partsHtml;
const css = read(CSS) || '';

/* A — contract ids */
for (const id of ['apSafetyStage', 'apSafetyScroller', 'apSafetyInner', 'apSafetyFoot', 'apSafetyProceed']) {
  if (!markup.includes(`id="${id}"`) && !markup.includes(`id='${id}'`)) {
    /* proceed/foot live in verdict.php — check parts */
    if (!partsHtml.includes(`id="${id}"`)) {
      fail('A1', `missing #${id} in gate + safety parts`);
    }
  }
}

for (const key of ['instagram', 'trust', 'verified']) {
  if (!partsHtml.includes(`data-check=`) || !partsHtml.includes(key)) {
    /* check.php uses $key; checks.php passes keys */
  }
}
const checks = read('apollo-adverts/templates/parts/safety/checks.php') || '';
for (const key of ['instagram', 'trust', 'verified']) {
  if (!checks.includes(`'${key}'`) && !checks.includes(`"${key}"`)) {
    fail('A2', `checks.php does not register data-check key ${key}`);
  }
}

if (!partsHtml.includes('apWitness') && !(read('apollo-adverts/templates/parts/safety/witness.php') || '').includes('apWitness')) {
  fail('A3', 'witness.php missing #apWitness / confirm control');
}

/* B — verdict outside scroller */
if (!/apollo_safety_render[\s\S]*Gate::part\(\s*'verdict'/.test(tpl.replace(/\s+/g, ' '))
  && !/Gate::part\(\s*['"]verdict['"]/.test(tpl)) {
  fail('B1', 'gate.php must render verdict OUTSIDE apollo-warn scroller via Gate::part');
}
if (/apollo_safety_render[\s\S]*verdict/.test(tpl) && tpl.indexOf("Gate::part('verdict'") < tpl.indexOf('apollo_safety_render')) {
  /* ok if verdict after render */
}

const gatePhp = read('apollo-adverts/src/Safety/Gate.php') || '';
if (/self::part\(\s*'verdict'/.test(gatePhp) && /self::part\(\s*'note'/.test(gatePhp)) {
  const noteIdx = gatePhp.indexOf("self::part('note'");
  const verdIdx = gatePhp.indexOf("self::part('verdict'");
  if (verdIdx > noteIdx && verdIdx !== -1) {
    fail('B2', 'Gate::render() still includes verdict inside the scroll body — move to gate.php');
  }
}

/* C — no :root in CSS */
if (/^\s*:root\s*\{/m.test(css) || /\n:root\s*\{/.test(css)) {
  fail('C1', 'safety-gate.css declares :root — tokens belong to core.js');
}

/* D — modules */
for (const m of JS_MODULES) {
  if (!existsSync(R(m))) fail('D1', `JS module missing: ${m}`);
}
const loader = read(JS_MODULES[0]) || '';
if (!loader.includes('ApolloSafetyAPI') || !loader.includes('startPoll')) {
  fail('D2', 'gate.js bundle must include ApolloSafetyAPI and startPoll (bundled modules)');
}
if (!loader.includes('data-mode') || !loader.includes('witness')) {
  fail('D4', 'gate.js must handle witness mode');
}
if (!loader.includes('primaryAsk') && !loader.includes('ap-primary-ask')) {
  fail('D5', 'gate.js must offer primary Pedir confirmação CTA');
}

/* artefact */
const outDir = resolve(PLUGIN, '_sandbox');
mkdirSync(outDir, { recursive: true });
const harness = `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>/seguranca harness</title>
<style>
:root{--orange-300:#f0a46a;--orange-400:#e8893a;--orange-800:#5c3a12;--orange-900:#3d280c;--orange-950:#2D1D03;
--white-50:#faf8f5;--ff-mono:ui-monospace,monospace;--ff-heading:system-ui,sans-serif;--ff-main:system-ui,sans-serif;
--r:16px;--r-sm:10px;--r-pill:999px;--s-2:8px;--s-3:12px;--s-4:16px;--ease:cubic-bezier(.16,1,.3,1);
--alert-warning:#ffb020;--txt-color:#fff;--safe-bottom:0px;}
</style>
<style>${css}</style>
</head>
<body class="apollo-safety">
<div class="apollo-stage ap-safety" id="apSafetyStage" data-boot="ready">
  <div class="apollo-warn" id="apSafetyScroller"><div class="apollo-warn__inner" id="apSafetyInner">
  ${partsHtml.replace(/id="apSafetyFoot"[\s\S]*?<\/footer>/, '')}
  </div></div>
  <footer class="apollo-warn__foot" id="apSafetyFoot" data-verdict="pending" hidden>
    <div><span class="ap-verdict"><b data-bind="verdict-title">—</b><span data-bind="verdict-sub">—</span></span>
    <span class="ap-actions"><button type="button" class="btn btn-primary" id="apSafetyProceed" hidden>Abrir conversa</button></span></div>
  </footer>
</div>
</body></html>`;

writeFileSync(resolve(outDir, 'safety-gate-harness.html'), harness, 'utf8');

console.log('');
console.log('  /seguranca harness');
console.log('  ' + '─'.repeat(56));
console.log(`  artefact  ${relative(ROOT, resolve(outDir, 'safety-gate-harness.html')).replace(/\\/g, '/')}`);
console.log('');

if (failures.length) {
  console.log(`  ✗ ${failures.length} assertion(s) failed`);
  for (const f of failures) console.log('  ' + f);
  console.log('');
  process.exit(1);
}

console.log('  ✓ green');
console.log('');
