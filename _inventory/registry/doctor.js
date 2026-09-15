#!/usr/bin/env node
/**
 * Apollo Registry — doctor.
 *
 * build.js proves the chapters are STRUCTURALLY sound (they parse, they merge,
 * nobody double-owns a key). It cannot tell you whether they are TRUE.
 *
 * doctor.js compares registry claims against the actual disk:
 *
 *   node doctor.js           # human report
 *   node doctor.js --json    # machine-readable, for CI or an AI agent
 *   node doctor.js --strict  # exit 1 if any ERROR-level finding
 *
 * Findings are graded:
 *   ERROR  — the registry states something false about the code
 *   WARN   — drift that is explainable but should be reconciled
 *   INFO   — bookkeeping worth seeing, no action forced
 *
 * Written 2026-08-31 after an audit found 15 stale plugin versions, 10
 * undocumented mu-plugins, three contradictory plugin counts, and a monolith
 * that no longer matched its own chapters.
 */
'use strict';
const fs = require('fs');
const path = require('path');

const DIR      = __dirname;                       // …/_inventory/registry
const INV      = path.resolve(DIR, '..');          // …/_inventory
const PLUGINS  = path.resolve(INV, '..');          // …/plugins
const MU       = path.resolve(PLUGINS, '..', 'mu-plugin');
const JSON_OUT = process.argv.includes('--json');
const STRICT   = process.argv.includes('--strict');

const findings = [];
const add = (level, area, msg, fix) => findings.push({ level, area, msg, fix });
const readJson = p => JSON.parse(fs.readFileSync(p, 'utf8'));
const exists = p => { try { return fs.existsSync(p); } catch { return false; } };

// ── inputs ────────────────────────────────────────────────────────────────
const map = readJson(path.join(DIR, '00-registry-map.json'));

const registrySlugs = fs.readdirSync(path.join(DIR, '09-plugins'))
  .filter(f => f.endsWith('.json')).map(f => f.slice(0, -5)).sort();

const diskSlugs = fs.readdirSync(PLUGINS, { withFileTypes: true })
  .filter(d => d.isDirectory() && d.name.startsWith('apollo-'))
  .map(d => d.name).sort();

// ── 1. registry entry ↔ plugin folder ─────────────────────────────────────
const phantom   = registrySlugs.filter(s => !diskSlugs.includes(s));
const unrecorded = diskSlugs.filter(s => !registrySlugs.includes(s));

const declaredAbsent = (map.chapters['09-plugins'] || {}).absent_from_disk || [];
for (const s of phantom) {
  if (declaredAbsent.includes(s)) {
    add('INFO', 'plugins', `${s}: entry with no folder — already declared absent_from_disk`);
  } else {
    add('ERROR', 'plugins', `${s}: registry entry has NO folder on disk and is not in absent_from_disk`,
        `add "${s}" to 00-registry-map.json chapters["09-plugins"].absent_from_disk, or delete the entry`);
  }
}
for (const s of declaredAbsent) {
  if (diskSlugs.includes(s))
    add('ERROR', 'plugins', `${s}: declared absent_from_disk but the folder EXISTS`,
        `remove "${s}" from absent_from_disk`);
}
for (const s of unrecorded)
  add('ERROR', 'plugins', `${s}: plugin folder on disk has NO registry entry`,
      `create 09-plugins/${s}.json`);

// ── 2. declared counts ↔ reality ──────────────────────────────────────────
const ch09 = map.chapters['09-plugins'] || {};
if (ch09.count !== registrySlugs.length)
  add('ERROR', 'counts', `map chapters["09-plugins"].count=${ch09.count} but ${registrySlugs.length} files exist`,
      `set count to ${registrySlugs.length}`);
if (Array.isArray(ch09.files) && ch09.files.length !== registrySlugs.length)
  add('ERROR', 'counts', `map chapters["09-plugins"].files lists ${ch09.files.length}, ${registrySlugs.length} exist`,
      'regenerate the files array');
if (Array.isArray(ch09.files)) {
  const missingFromList = registrySlugs.filter(s => !ch09.files.includes(s));
  if (missingFromList.length)
    add('ERROR', 'counts', `files array missing: ${missingFromList.join(', ')}`, 'regenerate the files array');
}
if (ch09.on_disk != null) {
  const realOnDisk = registrySlugs.filter(s => diskSlugs.includes(s)).length;
  if (ch09.on_disk !== realOnDisk)
    add('ERROR', 'counts', `map on_disk=${ch09.on_disk} but ${realOnDisk} registry entries have folders`,
        `set on_disk to ${realOnDisk}`);
}

// cross-chapter count agreement
const arch = readJson(path.join(DIR, '08-architecture-layers.json')).architecture || {};
const summ = readJson(path.join(DIR, '16-summary.json')).summary || {};
const counts = {
  'map.chapters["09-plugins"].count': ch09.count,
  'architecture.total_plugins':       arch.total_plugins,
  'summary.total_plugins':            summ.total_plugins,
  'actual 09-plugins/*.json':         registrySlugs.length,
};
const distinct = [...new Set(Object.values(counts).filter(v => v != null))];
if (distinct.length > 1)
  add('ERROR', 'counts', `plugin count disagrees across chapters: ${JSON.stringify(counts)}`,
      `make all equal ${registrySlugs.length}`);

// ── 3. every registry entry has an architecture layer ─────────────────────
const layered = new Set(Object.values(arch.layers || {}).flat());
for (const s of registrySlugs)
  if (!layered.has(s))
    add('WARN', 'layers', `${s}: has a registry entry but no architecture layer`,
        'place it in 08-architecture-layers.json layers.*');
for (const s of layered)
  if (!registrySlugs.includes(s))
    add('ERROR', 'layers', `${s}: placed in a layer but has no 09-plugins entry`, 'remove or create the entry');

if (Array.isArray(arch.$layers_absent_from_disk)) {
  const a = [...arch.$layers_absent_from_disk].sort().join(',');
  const b = [...declaredAbsent].sort().join(',');
  if (a !== b)
    add('ERROR', 'layers', `architecture.$layers_absent_from_disk [${a}] != map absent_from_disk [${b}]`,
        'make the two lists identical');
}

// ── 4. version drift: registry ↔ docblock ↔ constant ──────────────────────
for (const slug of registrySlugs) {
  if (!diskSlugs.includes(slug)) continue;
  const main = path.join(PLUGINS, slug, `${slug}.php`);
  if (!exists(main)) { add('WARN', 'versions', `${slug}: no ${slug}.php main file to read a version from`); continue; }

  const src  = fs.readFileSync(main, 'utf8');
  const doc  = (src.match(/^[\s*]*Version:\s*([0-9][0-9.]*)/mi)      || [])[1] || null;
  const cons = (src.match(/define\s*\(\s*'APOLLO_[A-Z_]*VERSION'\s*,\s*'([0-9][0-9.]*)'/) || [])[1] || null;
  const reg  = (readJson(path.join(DIR, '09-plugins', `${slug}.json`)).version) || null;

  if (doc && cons && doc !== cons)
    add('ERROR', 'versions', `${slug}: docblock ${doc} != APOLLO_*_VERSION constant ${cons}`,
        'bump both together — WP reads the docblock, cache-busting reads the constant');
  if (reg && doc && reg !== doc)
    add('WARN', 'versions', `${slug}: registry ${reg} != disk ${doc}`, `set registry version to ${doc}`);
}

// ── 5. mu-plugins layer ↔ disk ────────────────────────────────────────────
if (!exists(MU)) {
  add('INFO', 'mu', `mu-plugin dir not reachable from here (${MU}) — skipped`);
} else {
  const muFiles = fs.readdirSync(MU).filter(f => f.endsWith('.php')).sort();
  let muChapter = null;
  try { muChapter = readJson(path.join(DIR, '22-mu-plugins.json')).$mu_plugins; } catch { /* absent */ }

  if (!muChapter) {
    add('ERROR', 'mu', '22-mu-plugins.json missing or unreadable — the boot layer is undocumented',
        'create the chapter');
  } else {
    // only object-valued, non-$ keys name a real file; scalars like
    // "description" are prose about the layer itself.
    const documented = new Set([
      ...Object.keys(muChapter).filter(k =>
        !k.startsWith('$') && muChapter[k] && typeof muChapter[k] === 'object'),
      ...Object.keys(muChapter.$third_party_mu || {}),
    ]);
    for (const f of muFiles) {
      const key = f.replace(/\.php$/, '');
      if (!documented.has(key))
        add('ERROR', 'mu', `${f}: mu-plugin on disk is NOT documented — it runs on every request and cannot be disabled from wp-admin`,
            `add "${key}" to 22-mu-plugins.json`);
    }
    for (const key of documented)
      if (!muFiles.includes(`${key}.php`))
        add('WARN', 'mu', `${key}: documented in 22-mu-plugins but no such file on disk`, 'remove the entry');

    const scan = muChapter.$disk_scan || {};
    if (scan.php_files != null && scan.php_files !== muFiles.length)
      add('WARN', 'mu', `$disk_scan.php_files=${scan.php_files} but ${muFiles.length} .php files exist`,
          `set php_files to ${muFiles.length}`);
  }
}

// ── 6. monolith freshness ─────────────────────────────────────────────────
const mono = path.join(INV, 'apollo-registry.json');
if (exists(mono)) {
  const monoMtime = fs.statSync(mono).mtimeMs;
  const newer = [];
  const walk = d => fs.readdirSync(d, { withFileTypes: true }).forEach(e => {
    const p = path.join(d, e.name);
    if (e.isDirectory()) return walk(p);
    if (!e.name.endsWith('.json') || e.name === '00-registry-map.json') return;
    if (fs.statSync(p).mtimeMs > monoMtime) newer.push(path.relative(DIR, p));
  });
  walk(DIR);
  if (newer.length)
    add('ERROR', 'build', `${newer.length} chapter(s) modified AFTER the monolith was built: ${newer.slice(0, 6).join(', ')}${newer.length > 6 ? ' …' : ''}`,
        'run: node build.js --write');
} else {
  add('WARN', 'build', 'apollo-registry.json monolith not found', 'run: node build.js --write');
}

// ── 7. self-invalidating metadata ─────────────────────────────────────────
for (const [cid, ch] of Object.entries(map.chapters)) {
  if (!ch.file || ch.bytes == null) continue;
  const p = path.join(DIR, ch.file);
  if (!exists(p)) continue;
  const actual = fs.statSync(p).size;
  if (actual !== ch.bytes)
    add('WARN', 'map', `${cid}: declared bytes=${ch.bytes}, actual=${actual}`,
        'byte counts invalidate on every edit — drop the field rather than maintaining it');
}

// ── report ────────────────────────────────────────────────────────────────
const by = l => findings.filter(f => f.level === l);
if (JSON_OUT) {
  console.log(JSON.stringify({
    generated: new Date().toISOString(),
    totals: { error: by('ERROR').length, warn: by('WARN').length, info: by('INFO').length },
    findings,
  }, null, 2));
} else {
  const icon = { ERROR: '✗', WARN: '!', INFO: 'i' };
  let area = null;
  console.log('\nApollo Registry Doctor — registry claims vs. disk reality\n');
  for (const level of ['ERROR', 'WARN', 'INFO']) {
    for (const f of by(level)) {
      if (f.area !== area) { console.log(`\n── ${f.area} ──`); area = f.area; }
      console.log(`${icon[f.level]} ${f.msg}`);
      if (f.fix) console.log(`    → ${f.fix}`);
    }
  }
  console.log(`\n${by('ERROR').length} error · ${by('WARN').length} warn · ${by('INFO').length} info\n`);
}

if (STRICT && by('ERROR').length) process.exit(1);
