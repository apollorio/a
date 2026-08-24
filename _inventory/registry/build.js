#!/usr/bin/env node
/**
 * Apollo Modular Registry — builder.
 *
 * Reassembles the chaptered SSOT in plugins/_inventory/registry/ back into the
 * canonical monolith, using the strategy declared in 00-registry-map.json
 * (deep_merge_last_wins, arrays replaced wholesale, "$chapter" stripped).
 *
 *   node build.js            # validate + report only (no writes)
 *   node build.js --write    # also write monolith + runtime + mirror
 *
 * Exit code 1 on any validation failure.
 */
'use strict';
const fs = require('fs');
const path = require('path');

const DIR  = __dirname;
const INV  = path.resolve(DIR, '..');
const MAP  = path.join(DIR, '00-registry-map.json');
const WRITE = process.argv.includes('--write');

const read = p => JSON.parse(fs.readFileSync(p, 'utf8'));
const errs = [];
const warn = [];

const map = read(MAP);
const rules = map.build_rules;
const STRIP = rules.strip_key || '$chapter';

/** deep merge, last wins; arrays replaced wholesale */
function merge(target, src) {
  for (const k of Object.keys(src)) {
    if (k === STRIP) continue;
    const v = src[k];
    if (v && typeof v === 'object' && !Array.isArray(v) &&
        target[k] && typeof target[k] === 'object' && !Array.isArray(target[k])) {
      merge(target[k], v);
    } else {
      target[k] = v;
    }
  }
  return target;
}

const out = {};
const ownership = new Map(); // topLevelKey -> [chapters]
let pluginCount = 0;

for (const id of map.load_order) {
  const ch = map.chapters[id];
  if (!ch) { errs.push(`load_order references unknown chapter "${id}"`); continue; }

  if (ch.dir) {
    const dir = path.join(DIR, ch.dir);
    if (!fs.existsSync(dir)) { errs.push(`missing dir ${ch.dir}`); continue; }
    const files = fs.readdirSync(dir).filter(f => f.endsWith('.json')).sort();
    out.plugins = out.plugins || {};
    for (const f of files) {
      const slug = f.slice(0, -5);
      const body = read(path.join(dir, f));
      const entry = {};
      for (const k of Object.keys(body)) if (k !== STRIP) entry[k] = body[k];
      out.plugins[slug] = entry;
      pluginCount++;
    }
    if (ch.count != null && ch.count !== pluginCount)
      errs.push(`plugin count ${pluginCount} != declared ${ch.count}`);
    (ownership.get('plugins') || ownership.set('plugins', []).get('plugins')).push(id);
    continue;
  }

  const p = path.join(DIR, ch.file);
  if (!fs.existsSync(p)) { errs.push(`missing chapter file ${ch.file}`); continue; }
  let body;
  try { body = read(p); }
  catch (e) { errs.push(`${ch.file} does not parse: ${e.message}`); continue; }

  for (const k of Object.keys(body)) {
    if (k === STRIP) continue;
    if (!ownership.has(k)) ownership.set(k, []);
    ownership.get(k).push(id);
  }
  merge(out, body);
}

// ── validations ────────────────────────────────────────────────────────────
const ALLOWED_SHARED = new Set(['$deep_audit']); // declared four-way split
for (const [k, owners] of ownership)
  if (owners.length > 1 && !ALLOWED_SHARED.has(k))
    errs.push(`top-level key "${k}" claimed by ${owners.length} chapters: ${owners.join(', ')}`);

if (rules.require_apollo_core_master && !out.plugins?.['apollo-core'])
  errs.push('apollo-core master entry missing');

// round-trip against the canonical monolith
const monoPath = path.join(INV, 'apollo-registry.json');
if (fs.existsSync(monoPath)) {
  const mono = read(monoPath);
  const canon = o => JSON.stringify(o, Object.keys(flatten(o)).sort ? undefined : undefined);
  function sortDeep(v) {
    if (Array.isArray(v)) return v.map(sortDeep);
    if (v && typeof v === 'object')
      return Object.keys(v).sort().reduce((a, k) => (a[k] = sortDeep(v[k]), a), {});
    return v;
  }
  function flatten(o) { return o; }
  const a = JSON.stringify(sortDeep(mono));
  const b = JSON.stringify(sortDeep(out));
  if (a !== b) {
    const ka = new Set(Object.keys(mono)), kb = new Set(Object.keys(out));
    const only_mono = [...ka].filter(k => !kb.has(k));
    const only_built = [...kb].filter(k => !ka.has(k));
    const differing = [...ka].filter(k => kb.has(k) &&
      JSON.stringify(sortDeep(mono[k])) !== JSON.stringify(sortDeep(out[k])));
    warn.push(`built output differs from monolith — only_in_monolith=[${only_mono}] only_in_built=[${only_built}] differing=[${differing}]`);
  } else {
    console.log('round-trip: IDENTICAL to apollo-registry.json');
  }
}

// ── report ─────────────────────────────────────────────────────────────────
console.log(`chapters merged : ${map.load_order.length}`);
console.log(`plugin entries  : ${pluginCount}`);
console.log(`top-level keys  : ${Object.keys(out).length}`);
warn.forEach(w => console.log('WARN  ' + w));
errs.forEach(e => console.log('ERROR ' + e));

if (errs.length) { console.log(`\nBUILD FAILED — ${errs.length} error(s)`); process.exit(1); }

if (WRITE) {
  const json = JSON.stringify(out, null, 2) + '\n';
  const targets = [
    path.join(INV, path.basename(rules.output.monolith)),
    path.join(INV, path.basename(rules.output.mirror)),
  ];
  for (const t of targets) { fs.writeFileSync(t, json); console.log('wrote ' + t); }
  console.log(`runtime target (deploy step, not written here): ${rules.output.runtime}`);
} else {
  console.log('\nOK — dry run. Re-run with --write to emit monolith + mirror.');
}
