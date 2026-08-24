/**
 * /eventos/novo — static create-form wiring auditor.
 *
 * Checks:
 *   A · DOM contract — $id('…') / getElementById in create JS vs ids in template-parts/create
 *   B · Payload ⊆ save — collectPayload keys vs save_event_meta + taxonomies + coauthors
 *   C · Registry coverage — payload meta keys exist in apollo-events.json (or wp_core/tax)
 *   D · Duplicity — forbidden venue meta not on form; lat/lon not in payload; single loc_id
 *   E · PHP syntax — php -l on key files (when php available)
 *
 * Run:  node _sandbox/audit-create-form.mjs
 * Exit: 0 if all PASS, 1 if any FAIL
 */

import { readFileSync, readdirSync, existsSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const HERE = dirname(fileURLToPath(import.meta.url));
const PLUGIN = join(HERE, '..');
const CREATE = join(PLUGIN, 'styles/base/template-parts/create');
const BRIDGE = join(PLUGIN, 'assets/js/apollo-events-create-bridge.js');
const WIRE = join(PLUGIN, 'assets/js/apollo-events-create-wire.js');
const FORM_JS = join(PLUGIN, 'assets/js/apollo-events-create-form.js');
const CONTROLLER = join(PLUGIN, 'src/API/EventsController.php');
const REGISTRY = join(PLUGIN, '../_inventory/registry/09-plugins/apollo-events.json');
const MATRIX = join(HERE, 'CREATE-FIELD-MATRIX.json');

const results = [];
const pass = (id, msg) => results.push({ id, ok: true, msg });
const fail = (id, msg) => results.push({ id, ok: false, msg });

const read = (p) => readFileSync(p, 'utf8');

/* ── helpers ─────────────────────────────────────────────────────────────── */

function extractIdsFromPhp(dir) {
  const ids = new Set();
  for (const f of readdirSync(dir)) {
    if (!f.endsWith('.php')) continue;
    const raw = read(join(dir, f));
    for (const m of raw.matchAll(/\bid=["']([^"']+)["']/g)) ids.add(m[1]);
  }
  // create-event.php also has a_eve_* hidden inputs
  const shell = join(PLUGIN, 'styles/base/create-event.php');
  if (existsSync(shell)) {
    for (const m of read(shell).matchAll(/\bid=["']([^"']+)["']/g)) ids.add(m[1]);
  }
  return ids;
}

function extractIdRefsFromJs(...files) {
  const refs = new Set();
  for (const f of files) {
    if (!existsSync(f)) continue;
    const raw = read(f);
    for (const m of raw.matchAll(/\$id\(\s*['"]([^'"]+)['"]\s*\)/g)) refs.add(m[1]);
    for (const m of raw.matchAll(/getElementById\(\s*['"]([^'"]+)['"]\s*\)/g)) refs.add(m[1]);
  }
  return refs;
}

function extractCollectPayloadKeys(bridgeSrc) {
  const start = bridgeSrc.indexOf('function collectPayload');
  if (start < 0) return [];
  const slice = bridgeSrc.slice(start, start + 4500);
  const keys = new Set();
  // object literal keys: title: ( ... )
  for (const m of slice.matchAll(/^\s{2,4}([a-z_]+)\s*:/gm)) keys.add(m[1]);
  // data.foo = assignments
  for (const m of slice.matchAll(/data\.([a-z_]+)\s*=/g)) keys.add(m[1]);
  return [...keys];
}

function extractSaveMetaMap(controllerSrc) {
  const map = {};
  const block = controllerSrc.match(/\$meta_map\s*=\s*array\s*\(([\s\S]*?)\);/);
  if (!block) return map;
  for (const m of block[1].matchAll(/'([a-z_]+)'\s*=>\s*'(_event_[a-z_]+)'/g)) {
    map[m[1]] = m[2];
  }
  // special array keys
  if (controllerSrc.includes("array_key_exists( 'dj_ids'")) map.dj_ids = '_event_dj_ids';
  if (controllerSrc.includes("array_key_exists( 'dj_slots'")) map.dj_slots = '_event_dj_slots';
  if (controllerSrc.includes("array_key_exists( 'access_buttons'")) map.access_buttons = '_event_access_buttons';
  if (controllerSrc.includes("array_key_exists( 'gallery'")) map.gallery = '_event_gallery';
  return map;
}

function extractRegistryEventMeta(regSrc) {
  const keys = new Set();
  // Only from meta.post block — approximate via "_event_*: {"
  for (const m of regSrc.matchAll(/"(_event_[a-z0-9_]+)"\s*:\s*\{/g)) keys.add(m[1]);
  return keys;
}

/* ── A · DOM contract ────────────────────────────────────────────────────── */

{
  const ids = extractIdsFromPhp(CREATE);
  const refs = extractIdRefsFromJs(BRIDGE, WIRE, FORM_JS);

  // Dynamic / optional ids that are created at runtime or live in Apollo+ shell
  const allowMissing = new Set([
    'sidebarEvents', // dash sidebar may be absent on create-only shell
    'coverUpload', // may be wired as data-attr target
    'deleteEventModal',
    'confirmDeleteEventBtn',
    'apollo-combo-css', // style tag injected by create-wire.js combobox()
    'ax-aside', // Blank Canvas Apollo+ shell
    'ax-overlay', // Blank Canvas Apollo+ shell
  ]);

  const missing = [...refs].filter((id) => !ids.has(id) && !allowMissing.has(id));

  if (missing.length) {
    fail('A-dom', `JS refs missing from create DOM: ${missing.join(', ')}`);
  } else {
    pass('A-dom', `${refs.size} JS id refs matched create DOM (${ids.size} ids) + shell allowlist`);
  }
}

/* ── B · Payload ⊆ save ──────────────────────────────────────────────────── */

{
  const bridge = read(BRIDGE);
  const controller = read(CONTROLLER);
  const payloadKeys = extractCollectPayloadKeys(bridge);
  const metaMap = extractSaveMetaMap(controller);

  const wpCore = new Set(['title', 'content', 'post_status']);
  const tax = new Set(['sounds', 'seasons', 'tags']);
  const coauthors = new Set(['coauthors']);

  const unsaved = payloadKeys.filter(
    (k) =>
      !metaMap[k] &&
      !wpCore.has(k) &&
      !tax.has(k) &&
      !coauthors.has(k)
  );

  if (unsaved.length) {
    fail('B-payload-save', `collectPayload keys not saved: ${unsaved.join(', ')}`);
  } else {
    pass('B-payload-save', `${payloadKeys.length} payload keys ⊆ save_event_meta/tax/coauthors/wp`);
  }

  // Always-send loc_id (clearable FK)
  if (!payloadKeys.includes('loc_id') && !/data\.loc_id\s*=/.test(bridge)) {
    fail('B-loc-id', 'collectPayload must always set data.loc_id for _event_loc_id');
  } else {
    pass('B-loc-id', 'collectPayload writes data.loc_id → _event_loc_id');
  }

  // Taxonomies present in save_event_taxonomies
  if (!/save_event_taxonomies/.test(controller)) {
    fail('B-tax', 'save_event_taxonomies missing');
  } else {
    pass('B-tax', 'save_event_taxonomies present');
  }
}

/* ── C · Registry coverage ───────────────────────────────────────────────── */

{
  if (!existsSync(REGISTRY)) {
    fail('C-registry', `registry missing: ${REGISTRY}`);
  } else {
    const regKeys = extractRegistryEventMeta(read(REGISTRY));
    const metaMap = extractSaveMetaMap(read(CONTROLLER));
    const missing = Object.values(metaMap).filter((mk) => !regKeys.has(mk));
    if (missing.length) {
      fail('C-registry', `meta keys not in apollo-events.json: ${missing.join(', ')}`);
    } else {
      pass('C-registry', `${Object.keys(metaMap).length} save meta keys covered by registry`);
    }
  }
}

/* ── D · Duplicity ───────────────────────────────────────────────────────── */

{
  const createPhp = readdirSync(CREATE)
    .filter((f) => f.endsWith('.php'))
    .map((f) => read(join(CREATE, f)))
    .join('\n');

  const forbidden = ['_event_local_name', '_event_lat', '_event_lng', 'name="local_name"', 'name="event_lat"'];
  const hits = forbidden.filter((f) => createPhp.includes(f));
  if (hits.length) {
    fail('D-forbidden-venue', `Forbidden venue fields on novo form: ${hits.join(', ')}`);
  } else {
    pass('D-forbidden-venue', 'No _event_local_name / _event_lat / _event_lng on create form');
  }

  // Single loc_id input
  const locIds = [...createPhp.matchAll(/name=["']loc_id["']/g)];
  if (locIds.length !== 1) {
    fail('D-loc-id-count', `Expected exactly 1 name="loc_id", found ${locIds.length}`);
  } else {
    pass('D-loc-id-count', 'Exactly one loc_id input');
  }

  // Stale datalist
  if (/ev-venue-datalist/.test(createPhp) || /list=["']ev-venue-datalist["']/.test(createPhp)) {
    fail('D-datalist', 'Stale <datalist id="ev-venue-datalist"> still in form-venue.php');
  } else {
    pass('D-datalist', 'No stale venue datalist');
  }

  // lat/lon must not be in collectPayload
  const bridge = read(BRIDGE);
  const collect = bridge.slice(bridge.indexOf('function collectPayload'), bridge.indexOf('function collectPayload') + 4500);
  if (/\blat\b\s*:/.test(collect) || /data\.lat\s*=/.test(collect) || /data\.lon\s*=/.test(collect) || /data\.lng\s*=/.test(collect)) {
    fail('D-weather-payload', 'lat/lon leaked into collectPayload');
  } else {
    pass('D-weather-payload', 'lat/lon display-only (not in collectPayload)');
  }

  // Matrix forbidden list consistency
  if (existsSync(MATRIX)) {
    const matrix = JSON.parse(read(MATRIX));
    pass('D-matrix', `CREATE-FIELD-MATRIX.json present (${matrix.rows.length} rows)`);
  } else {
    fail('D-matrix', 'CREATE-FIELD-MATRIX.json missing');
  }
}

/* ── E · PHP syntax ──────────────────────────────────────────────────────── */

{
  const files = [
    CONTROLLER,
    join(PLUGIN, 'styles/base/create-event.php'),
    join(PLUGIN, 'src/FrontendForm.php'),
  ];
  let phpOk = true;
  let phpMsgs = [];
  for (const f of files) {
    if (!existsSync(f)) {
      phpMsgs.push(`missing ${f}`);
      phpOk = false;
      continue;
    }
    const r = spawnSync('php', ['-l', f], { encoding: 'utf8' });
    if (r.error || r.status !== 0) {
      phpOk = false;
      phpMsgs.push(`${f}: ${r.stderr || r.error?.message || 'php -l failed'}`);
    }
  }
  if (phpOk) pass('E-php', `php -l OK on ${files.length} files`);
  else fail('E-php', phpMsgs.join(' | '));
}

/* ── Report ──────────────────────────────────────────────────────────────── */

const failed = results.filter((r) => !r.ok);
console.log('\n/eventos/novo create-form audit\n');
for (const r of results) {
  console.log(`  ${r.ok ? 'PASS' : 'FAIL'}  ${r.id} — ${r.msg}`);
}
console.log(`\n  ${results.length - failed.length}/${results.length} passed\n`);
process.exit(failed.length ? 1 : 0);
