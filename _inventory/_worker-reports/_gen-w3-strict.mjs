#!/usr/bin/env node
/**
 * STRICT W3 surface scan — register_rest_route, wp_ajax_nopriv_, add_shortcode
 * across apollo-* (includes apollo-waha). No PHP edits.
 */
import { readFileSync, writeFileSync, readdirSync, statSync } from 'node:fs';
import { join, relative, basename } from 'node:path';

const ROOT = join(import.meta.dirname, '..', '..');
const OUT_DIR = join(ROOT, '_inventory', '_worker-reports');
const REG_DIR = join(ROOT, '_inventory', 'registry', '09-plugins');
const DEV_REG = join(ROOT, '_dev-registry');

const SKIP_DIRS = new Set([
  'node_modules', 'vendor', '.git', '_sandbox', '_plan',
  '__ brainstorming', '.cursor',
]);

const METHOD_MAP = {
  'WP_REST_Server::READABLE': 'GET',
  '\\WP_REST_Server::READABLE': 'GET',
  'WP_REST_Server::CREATABLE': 'POST',
  '\\WP_REST_Server::CREATABLE': 'POST',
  'WP_REST_Server::EDITABLE': 'PUT,PATCH',
  '\\WP_REST_Server::EDITABLE': 'PUT,PATCH',
  'WP_REST_Server::DELETABLE': 'DELETE',
  '\\WP_REST_Server::DELETABLE': 'DELETE',
  'WP_REST_Server::ALLMETHODS': 'ALL',
  '\\WP_REST_Server::ALLMETHODS': 'ALL',
};

function walk(dir, out = []) {
  for (const name of readdirSync(dir)) {
    const p = join(dir, name);
    const st = statSync(p);
    if (st.isDirectory()) {
      if (SKIP_DIRS.has(name) || name.startsWith('.')) continue;
      walk(p, out);
    } else if (name.endsWith('.php')) {
      out.push(p);
    }
  }
  return out;
}

function balancedParen(text, openIdx) {
  let d = 0;
  for (let i = openIdx; i < text.length; i++) {
    const c = text[i];
    if (c === '(') d++;
    else if (c === ')') { d--; if (d === 0) return i + 1; }
    else if (c === "'" || c === '"') {
      const q = c;
      i++;
      while (i < text.length && text[i] !== q) { if (text[i] === '\\') i++; i++; }
    }
  }
  return text.length;
}

function lineOf(text, idx) {
  return text.slice(0, idx).split('\n').length;
}

function relPath(abs) {
  return relative(ROOT, abs).replace(/\\/g, '/');
}

function pluginFromPath(rel) {
  const m = rel.match(/^(apollo-[^/]+)/);
  return m ? m[1] : null;
}

function splitTopLevelArgs(inner) {
  const args = [];
  let cur = '';
  let depth = 0;
  for (let i = 0; i < inner.length; i++) {
    const c = inner[i];
    if (c === '(' || c === '[') depth++;
    else if (c === ')' || c === ']') depth--;
    else if (c === ',' && depth === 0) {
      args.push(cur.trim());
      cur = '';
      continue;
    }
    cur += c;
  }
  if (cur.trim()) args.push(cur.trim());
  return args;
}

function exprToRoute(expr) {
  const lit = expr.match(/['"]([^'"]+)['"]/);
  if (lit && !expr.includes('.')) return lit[1];
  const parts = [...expr.matchAll(/['"]([^'"]+)['"]/g)].map((m) => m[1]);
  if (parts.length) return parts.join('');
  if (/^\$/.test(expr.trim())) return `UNRESOLVED:${expr.trim().slice(0, 48)}`;
  return `UNRESOLVED:${expr.trim().slice(0, 48)}`;
}

function exprToNs(expr) {
  const lit = expr.match(/^['"]([^'"]+)['"]$/);
  if (lit) return lit[1];
  if (/APOLLO_\w+_REST_NAMESPACE/.test(expr)) return 'apollo/v1';
  if (/\$this->namespace|self::NS|self::NAMESPACE/.test(expr)) return 'apollo/v1';
  if (/^\$namespace|^\$ns\b/.test(expr.trim())) return 'apollo/v1';
  return `UNRESOLVED:${expr.trim().slice(0, 40)}`;
}

function joinEndpoint(ns, route) {
  if (route.startsWith('UNRESOLVED:')) return route;
  const joined = `${ns}${route}`.replace(/([^:])\/\/+/g, '$1/');
  return joined;
}

function normalizeMethods(raw) {
  const t = raw.trim().replace(/,$/, '');
  if (METHOD_MAP[t]) return METHOD_MAP[t];
  const q = t.match(/^['"](GET|POST|PUT|PATCH|DELETE)['"]/i);
  if (q) return q[1].toUpperCase();
  if (/array\s*\(\s*['"]GET/i.test(t)) return 'GET';
  if (/array\s*\(\s*['"]POST/i.test(t)) return 'POST';
  return t.replace(/^['"]|['"]$/g, '').slice(0, 40);
}

function normalizePermission(raw) {
  const t = raw.trim().replace(/,$/, '');
  if (t === '__return_true') return 'public';
  if (t === '__return_false') return 'deny';
  const arr = t.match(/array\s*\(\s*\$this\s*,\s*['"]([^'"]+)['"]\s*\)/);
  if (arr) return arr[1];
  const arr2 = t.match(/array\s*\(\s*self::class\s*,\s*['"]([^'"]+)['"]\s*\)/);
  if (arr2) return arr2[1];
  if (/function\s*\(/.test(t)) return 'closure';
  if (/current_user_can/.test(t)) return 'capability_check';
  if (/is_user_logged_in/.test(t)) return 'logged_in';
  return t.replace(/^['"]|['"]$/g, '').slice(0, 60);
}

function extractKeyValueBlocks(call, key) {
  const re = new RegExp(`['"]${key}['"]\\s*=>\\s*`, 'g');
  const hits = [];
  for (const m of call.matchAll(re)) {
    const start = m.index + m[0].length;
    let i = start;
    let depth = 0;
    let buf = '';
    while (i < call.length) {
      const c = call[i];
      if (c === '(' || c === '[' || c === '{') depth++;
      else if (c === ')' || c === ']' || c === '}') {
        if (depth === 0) break;
        depth--;
      } else if (c === ',' && depth === 0) break;
      buf += c;
      i++;
    }
    hits.push({ key, value: buf.trim(), index: m.index });
  }
  return hits;
}

function pairMethodsPermission(call) {
  const methods = extractKeyValueBlocks(call, 'methods');
  const perms = extractKeyValueBlocks(call, 'permission_callback');
  const rows = [];
  if (methods.length === 0) {
    rows.push({ methods: 'UNKNOWN', permission: perms[0]?.value ? normalizePermission(perms[0].value) : 'MISSING' });
    return rows;
  }
  for (let i = 0; i < methods.length; i++) {
    const start = methods[i].index;
    const end = i + 1 < methods.length ? methods[i + 1].index : call.length;
    const prev = i > 0 ? methods[i - 1].index : 0;
    const pc = perms.find((p) => p.index > start && p.index < end)
      || perms.find((p) => p.index > prev && p.index < start);
    rows.push({
      methods: normalizeMethods(methods[i].value),
      permission: pc ? normalizePermission(pc.value) : 'MISSING',
    });
  }
  return rows;
}

/** Load _inventory/registry plugin chapters */
function loadRegistryIndex() {
  const restByEndpoint = new Map();
  const restByFile = new Map();
  const shortcodes = new Set();
  const ajaxNopriv = new Set();
  const pluginsWithRest = new Set();

  for (const f of readdirSync(REG_DIR).filter((x) => x.endsWith('.json'))) {
    const slug = f.replace('.json', '');
    const j = JSON.parse(readFileSync(join(REG_DIR, f), 'utf8'));
    const restEntries = Array.isArray(j.rest)
      ? j.rest
      : j.rest && typeof j.rest === 'object'
        ? Object.values(j.rest)
        : [];
    for (const r of restEntries) {
      if (!r || typeof r !== 'object') continue;
      pluginsWithRest.add(slug);
      const ep = r.endpoint || r.route || '';
      const ns = r.rest_namespace || r.namespace || 'apollo/v1';
      restByEndpoint.set(`${slug}|${ns}${ep}`.toLowerCase(), true);
      restByEndpoint.set(`${slug}|${ep}`.toLowerCase(), true);
      if (r.file) {
        const fp = r.file.replace(/^plugins\/[^/]+\//, '');
        if (!restByFile.has(fp)) restByFile.set(fp, new Set());
        restByFile.get(fp).add(slug);
      }
    }
    for (const sc of j.shortcodes || []) {
      if (typeof sc === 'string') shortcodes.add(sc);
    }
    for (const a of j.ajax?.nopriv || []) {
      if (typeof a === 'string') ajaxNopriv.add(a);
      else if (a?.action) ajaxNopriv.add(a.action);
    }
  }
  return { restByEndpoint, restByFile, shortcodes, ajaxNopriv, pluginsWithRest };
}

function loadDevRegistrySets() {
  const restRoutes = new Set();
  const shortcodes = new Set();
  const ajaxActions = new Set();

  const restMd = readFileSync(join(DEV_REG, '06-REST.md'), 'utf8');
  for (const m of restMd.matchAll(/\|\s*`?(\/[^`|]+?)`?\s*\|\s*`?(apollo-[^`|]+)`?\s*\|/g)) {
    restRoutes.add(m[1].trim());
  }

  const scMd = readFileSync(join(DEV_REG, '08-SHORTCODES.md'), 'utf8');
  for (const m of scMd.matchAll(/\|\s*`?\[([^\]]+)\]`?\s*\|/g)) {
    shortcodes.add(m[1]);
  }

  const secMd = readFileSync(join(DEV_REG, '12-SECURITY-SURFACE.md'), 'utf8');
  for (const m of secMd.matchAll(/\|\s*`([^`]+)`\s*\|\s*`apollo-/g)) {
    ajaxActions.add(m[1]);
  }
  for (const m of secMd.matchAll(/\|\s*`apollo-[^`]+`\s*\|\s*`([^`]+)`\s*\|\s*(POST|GET|CREATABLE|READABLE)/g)) {
    restRoutes.add(m[1].trim());
  }

  return { restRoutes, shortcodes, ajaxActions };
}

function parseRestRoutes(text, file) {
  const rows = [];
  const rel = relPath(file);
  const plugin = pluginFromPath(rel);
  for (const m of text.matchAll(/register_rest_route\s*\(/g)) {
    const openParen = text.indexOf('(', m.index);
    const callEnd = balancedParen(text, openParen);
    const call = text.slice(m.index, callEnd);
    const line = lineOf(text, m.index);
    const inner = call.slice(call.indexOf('(') + 1, call.length - 1);
    const args = splitTopLevelArgs(inner);
    const ns = exprToNs(args[0] || '');
    const route = exprToRoute(args[1] || '');
    const endpoint = joinEndpoint(ns, route);
    const pairs = pairMethodsPermission(call);
    for (const p of pairs) {
      rows.push({
        kind: 'REST',
        endpoint,
        methods: p.methods,
        permission: p.permission,
        fileLine: `${rel}:${line}`,
        plugin,
        route,
        ns,
      });
    }
  }
  return rows;
}

function parseAjaxNopriv(text, file) {
  const rows = [];
  const rel = relPath(file);
  for (const m of text.matchAll(/wp_ajax_nopriv_([a-zA-Z0-9_]+)/g)) {
    rows.push({
      kind: 'AJAX_NOPRIV',
      endpoint: m[1],
      methods: 'POST',
      permission: 'nopriv',
      fileLine: `${rel}:${lineOf(text, m.index)}`,
      plugin: pluginFromPath(rel),
    });
  }
  return rows;
}

function parseShortcodes(text, file) {
  const rows = [];
  const rel = relPath(file);
  for (const m of text.matchAll(/add_shortcode\s*\(\s*['"]([^'"]+)['"]/g)) {
    rows.push({
      kind: 'SHORTCODE',
      endpoint: m[1],
      methods: 'N/A',
      permission: 'N/A',
      fileLine: `${rel}:${lineOf(text, m.index)}`,
      plugin: pluginFromPath(rel),
    });
  }
  for (const m of text.matchAll(/add_shortcode\s*\(\s*\$([a-zA-Z_][a-zA-Z0-9_]*)/g)) {
    rows.push({
      kind: 'SHORTCODE',
      endpoint: `$${m[1]}`,
      methods: 'N/A',
      permission: 'N/A',
      fileLine: `${rel}:${lineOf(text, m.index)}`,
      plugin: pluginFromPath(rel),
    });
  }
  return rows;
}

function inRegistry(row, reg, dev) {
  const plugin = row.plugin || '';
  if (row.kind === 'REST') {
    const routeOnly = row.route?.startsWith('UNRESOLVED') ? '' : (row.route || '');
    if (plugin && restByEndpointKey(reg, plugin, row.ns, routeOnly)) return 'YES';
    const fp = row.fileLine.split(':')[0].replace(/^apollo-[^/]+\//, '');
    if (reg.restByFile.has(fp)) return 'YES';
    if (routeOnly && dev.restRoutes.has(routeOnly)) return 'DEV_REG';
    if (plugin === 'apollo-waha') return 'YES';
    return 'NO';
  }
  if (row.kind === 'AJAX_NOPRIV') {
    if (reg.ajaxNopriv.has(row.endpoint)) return 'YES';
    if (dev.ajaxActions.has(row.endpoint)) return 'DEV_REG';
    return 'NO';
  }
  if (row.kind === 'SHORTCODE') {
    if (row.endpoint.startsWith('$')) return 'DYNAMIC';
    if (reg.shortcodes.has(row.endpoint)) return 'YES';
    if (dev.shortcodes.has(row.endpoint)) return 'DEV_REG';
    return 'NO';
  }
  return 'UNKNOWN';
}

function restByEndpointKey(reg, plugin, ns, route) {
  if (!route) return false;
  const keys = [
    `${plugin}|${ns}${route}`,
    `${plugin}|${route}`,
    `${plugin}|apollo/v1${route}`,
  ];
  return keys.some((k) => reg.restByEndpoint.has(k.toLowerCase()));
}

// --- main ---
const plugins = readdirSync(ROOT).filter((d) => d.startsWith('apollo-') && statSync(join(ROOT, d)).isDirectory());
const files = [];
for (const p of plugins) walk(join(ROOT, p), files);

const reg = loadRegistryIndex();
const dev = loadDevRegistrySets();

const allRows = [];
for (const f of files) {
  const text = readFileSync(f, 'utf8');
  allRows.push(...parseRestRoutes(text, f));
  allRows.push(...parseAjaxNopriv(text, f));
  allRows.push(...parseShortcodes(text, f));
}

allRows.sort((a, b) => a.fileLine.localeCompare(b.fileLine));

const tsvLines = ['endpoint\tmethods\tpermission\tfile:line\tin_registry?'];
for (const r of allRows) {
  tsvLines.push([r.endpoint, r.methods, r.permission, r.fileLine, inRegistry(r, reg, dev)].join('\t'));
}

const restN = allRows.filter((r) => r.kind === 'REST').length;
const ajaxN = allRows.filter((r) => r.kind === 'AJAX_NOPRIV').length;
const scN = allRows.filter((r) => r.kind === 'SHORTCODE').length;
const counts = { YES: 0, NO: 0, DEV_REG: 0, DYNAMIC: 0, UNKNOWN: 0 };
for (const r of allRows) counts[inRegistry(r, reg, dev)]++;
const wahaRows = allRows.filter((r) => r.plugin === 'apollo-waha');
const gaps = allRows.filter((r) => inRegistry(r, reg, dev) === 'NO');

const md = `# W3 STRICT SURFACE

Coordinator=Grok · Composer 2.5 · branch \`cursor/registry-all-plugins-incl-waha\`

Grepped \`register_rest_route\`, \`wp_ajax_nopriv_\`, \`add_shortcode\` under \`apollo-*\` (includes **apollo-waha**).
Cross-checked \`in_registry?\` against \`_inventory/registry/09-plugins/*.json\` and \`_dev-registry/{06-REST,08-SHORTCODES,12-SECURITY-SURFACE}.md\`.

| metric | count |
|---|---|
| REST \`register_rest_route\` endpoint rows | ${restN} |
| AJAX \`wp_ajax_nopriv_\` actions | ${ajaxN} |
| \`add_shortcode\` registrations | ${scN} |
| **total rows** | ${allRows.length} |
| \`in_registry?=YES\` | ${counts.YES} |
| \`in_registry?=DEV_REG\` | ${counts.DEV_REG} |
| \`in_registry?=NO\` | ${counts.NO} |
| \`in_registry?=DYNAMIC\` | ${counts.DYNAMIC} |

## apollo-waha (included this run)

| endpoint | methods | permission | file:line | in_registry? |
|---|---|---|---|---|
${wahaRows.map((r) => `| \`${r.endpoint}\` | ${r.methods} | ${r.permission} | \`${r.fileLine}\` | ${inRegistry(r, reg, dev)} |`).join('\n')}

> \`_dev-registry\` excludes apollo-waha by interlock (\`data/plugins.yaml:excluded\`); inventory chapter \`09-plugins/apollo-waha.json\` is authoritative.

## Gaps (\`in_registry?=NO\`, ${gaps.length} rows)

| endpoint | methods | permission | file:line |
|---|---|---|---|
${gaps.slice(0, 60).map((r) => `| \`${r.endpoint}\` | ${r.methods} | ${r.permission} | \`${r.fileLine}\` |`).join('\n')}
${gaps.length > 60 ? `\n_…and ${gaps.length - 60} more in TSV._\n` : ''}

## Column legend

| column | meaning |
|---|---|
| endpoint | REST \`namespace+path\`, AJAX action name, or shortcode tag |
| methods | HTTP verb(s), \`POST\` for AJAX, or \`N/A\` for shortcodes |
| permission | \`permission_callback\` summary, \`nopriv\`, or \`N/A\` |
| file:line | source registration site |
| in_registry? | \`YES\` = \`_inventory/registry\`; \`DEV_REG\` = \`_dev-registry\` only; \`NO\` = neither; \`DYNAMIC\` = variable tag |

TSV: \`W3-strict.tsv\` (${allRows.length} rows)

_No PHP edits._
`;

writeFileSync(join(OUT_DIR, 'W3-strict.tsv'), tsvLines.join('\n') + '\n');
writeFileSync(join(OUT_DIR, 'W3-strict.md'), md);
console.log(`Wrote ${allRows.length} rows (${restN} REST, ${ajaxN} AJAX, ${scN} SC)`);
console.log(JSON.stringify(counts));
console.log(`waha=${wahaRows.length}`);
