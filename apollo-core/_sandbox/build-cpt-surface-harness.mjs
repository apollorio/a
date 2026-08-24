#!/usr/bin/env node
/**
 * ═══════════════════════════════════════════════════════════════════════
 * CPT SURFACE HARNESS — gate G2 from PLAN-CPT-SINGLE-PAGES-2026-08-11.md
 * ═══════════════════════════════════════════════════════════════════════
 *
 *   node apollo-core/_sandbox/build-cpt-surface-harness.mjs
 *
 * One question, asked of every surface: does it offer every field the
 * schema declares? A field can now only be added in MetaRegistry, so the
 * only way to drift is to forget a surface — and this is what notices.
 *
 * It is a static reader. No PHP binary and no WordPress here, so it parses
 * the same files a developer would read, and it never mutates anything.
 *
 * Surfaces checked
 *   A  admin metabox   apollo-lux-panels/includes/{Dj,Loc}Panel.php
 *   B  public form     apollo-{djs,loc} shortcodes
 *   C  REST create     apollo-loc CreateEndpoint, apollo-djs DJsController
 *
 * Exit 0 = green. Anything else = do not start Phase 3.
 */

import { readFileSync, existsSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const read = (p) => (existsSync(resolve(ROOT, p)) ? readFileSync(resolve(ROOT, p), 'utf8') : '');

/* ── the schema: MetaRegistry::load_definitions() ──────────────────── */
function cptBlock(src, cpt) {
  const m = new RegExp(`'${cpt}'\\s*=>\\s*array\\(`).exec(src);
  if (!m) return '';
  let d = 0;
  for (let j = m.index + m[0].length - 1; j < src.length; j++) {
    if (src[j] === '(') d++;
    else if (src[j] === ')' && --d === 0) return src.slice(m.index, j + 1);
  }
  return '';
}
const KEY = /'(_[a-z0-9_]+)'\s*=>\s*array\(/g;
const keysIn = (s) => [...s.matchAll(KEY)].map((m) => m[1]);

const MR = read('apollo-core/src/Core/MetaRegistry.php');
const schema = {
  dj: new Set(keysIn(cptBlock(MR, 'dj'))),
  local: new Set(keysIn(cptBlock(MR, 'local'))),
};
/* apollo-loc contributes through the apollo_core_register_post_meta filter */
for (const k of read('apollo-loc/src/CPT/MetaRegistrar.php').match(/'(_local_[a-z0-9_]+)'\s*=>/g) || [])
  schema.local.add(k.replace(/'|\s|=>/g, ''));

/* Mirrors apollo_form_admin_only_keys() — keep the two in step. */
const ADMIN_ONLY = new Set([
  '_dj_user_id', '_local_user_id', '_dj_verified',
  '_local_visit_count', '_local_region', '_local_testimonials',
]);

/* ── surfaces ──────────────────────────────────────────────────────── */
const panelKeys = (f) => new Set([...read(f).matchAll(/'key'\s*=>\s*'(_[a-z0-9_]+)'/g)].map((m) => m[1]));
/* A surface may write meta two ways: a literal key, or a `'field' => '_meta_key'`
   MAP iterated with update_post_meta($id, $meta_key, …). Counting only literals
   under-reports a mapped writer to near zero — which is exactly what happened in
   the first run of this harness on DJsController (reported 8%, actually ~83%).
   Both forms count. */
const metaWrites = (f) => {
  const src = read(f);
  const literal = [...src.matchAll(/update_post_meta\(\s*[^,]+,\s*'(_[a-z0-9_]+)'/g)].map((m) => m[1]);
  const mapped = /update_post_meta\(\s*\$\w+\s*,\s*\$\w*(?:meta_)?key/.test(src)
    ? [...src.matchAll(/=>\s*'(_[a-z0-9_]+)'/g)].map((m) => m[1])
    : [];
  return new Set([...literal, ...mapped]);
};
/* A schema-driven surface names no keys — it names the schema. */
const isSchemaDriven = (f) => /apollo_form_schema\s*\(/.test(read(f));

const SURFACES = {
  dj: {
    metabox: { file: 'apollo-lux-panels/includes/DjPanel.php', keys: panelKeys('apollo-lux-panels/includes/DjPanel.php') },
    form: { file: 'apollo-djs/src/Shortcodes.php', keys: panelKeys('apollo-djs/src/Shortcodes.php') },
    rest: { file: 'apollo-djs/src/API/DJsController.php', keys: metaWrites('apollo-djs/src/API/DJsController.php') },
  },
  local: {
    metabox: { file: 'apollo-lux-panels/includes/LocPanel.php', keys: panelKeys('apollo-lux-panels/includes/LocPanel.php') },
    form: { file: 'apollo-loc/src/Shortcodes/AddLocShortcode.php', keys: panelKeys('apollo-loc/src/Shortcodes/AddLocShortcode.php') },
    rest: { file: 'apollo-loc/src/API/Endpoints/CreateEndpoint.php', keys: metaWrites('apollo-loc/src/API/Endpoints/CreateEndpoint.php') },
  },
};

const failures = [];
console.log('\n  CPT surface coverage — gate G2');
console.log('  ' + '─'.repeat(72));

for (const cpt of ['dj', 'local']) {
  const declared = [...schema[cpt]].filter((k) => !ADMIN_ONLY.has(k)).sort();
  console.log(`\n  ${cpt.toUpperCase()} — ${schema[cpt].size} registered · ${declared.length} public-facing`);

  for (const [name, s] of Object.entries(SURFACES[cpt])) {
    if (isSchemaDriven(s.file)) {
      console.log(`    ${name.padEnd(8)} SCHEMA-DRIVEN  ✔  (${s.file.split('/').pop()})`);
      continue;
    }
    const have = declared.filter((k) => s.keys.has(k));
    const miss = declared.filter((k) => !s.keys.has(k));
    const pct = declared.length ? Math.round((100 * have.length) / declared.length) : 0;
    const mark = pct === 100 ? '✔' : '✗';
    console.log(`    ${name.padEnd(8)} ${String(pct).padStart(3)}%  ${mark}  ${have.length}/${declared.length}  (${s.file.split('/').pop()})`);
    if (pct < 100) {
      failures.push(
        `${cpt}.${name} at ${pct}% — ${miss.length} field(s) the schema declares and this surface cannot edit:\n` +
        `        ${miss.join(' ')}\n` +
        `        Fix: drive it from apollo_form_schema('${cpt}') instead of hand-listing keys.`
      );
    }
  }
}

console.log('\n  ' + '─'.repeat(72));
if (failures.length) {
  console.log(`  ✗ G2 FAILS — ${failures.length} surface(s) below 100%\n`);
  failures.forEach((f) => console.log('    ' + f + '\n'));
  console.log('  Phase 3 does not start until this is green (PLAN §9).\n');
  process.exit(1);
}
console.log('  ✓ G2 GREEN — every surface offers every declared field\n');
