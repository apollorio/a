#!/usr/bin/env node
/**
 * W3 SURFACE worker — scan apollo-* (skip apollo-waha) for REST, AJAX nopriv, shortcodes.
 */
import fs from 'fs';
import path from 'path';

const ROOT = path.resolve(import.meta.dirname, '../..');
const OUT_DIR = path.resolve(import.meta.dirname);
const REGISTRY_DIR = path.join(ROOT, '_inventory/registry/09-plugins');

const WP_METHOD_MAP = {
  'WP_REST_Server::READABLE': 'GET',
  '\\WP_REST_Server::READABLE': 'GET',
  'WP_REST_Server::CREATABLE': 'POST',
  '\\WP_REST_Server::CREATABLE': 'POST',
  'WP_REST_Server::EDITABLE': 'POST,PUT,PATCH',
  '\\WP_REST_Server::EDITABLE': 'POST,PUT,PATCH',
  'WP_REST_Server::DELETABLE': 'DELETE',
  '\\WP_REST_Server::DELETABLE': 'DELETE',
  'WP_REST_Server::ALLMETHODS': 'GET,POST,PUT,PATCH,DELETE',
  '\\WP_REST_Server::ALLMETHODS': 'GET,POST,PUT,PATCH,DELETE',
};

const NS_CONSTS = {
  APOLLO_ADMIN_REST_NAMESPACE: 'apollo/v1',
  APOLLO_ADVERTS_REST_NAMESPACE: 'apollo/v1',
  APOLLO_COAUTHOR_REST_NAMESPACE: 'apollo/v1',
  APOLLO_JOURNAL_REST_NAMESPACE: 'apollo/v1',
  APOLLO_MAPS_REST_NAMESPACE: 'apollo/v1',
  APOLLO_LOGIN_REST_NAMESPACE: 'apollo/v1',
  APOLLO_LOCAL_REST_NAMESPACE: 'apollo/v1',
  APOLLO_HUB_REST_NAMESPACE: 'apollo/v1',
  APOLLO_MEMBERSHIP_REST_NAMESPACE: 'apollo/v1',
  APOLLO_EVENT_REST_NAMESPACE: 'apollo/v1',
  APOLLO_SCHEDULER_REST_NAMESPACE: 'apollo/v1',
  APOLLO_REST_NAMESPACE: 'apollo/v1',
  'ApolloRoute::NAMESPACE': 'apollo/v1',
  'Apollo\\Core\\Config\\ApolloRoute::NAMESPACE': 'apollo/v1',
  'self::NS': 'apollo/v1',
  'self::NAMESPACE': 'apollo/v1',
  '$ns': 'apollo/v1',
  '$namespace': 'apollo/v1',
  '$this->namespace': 'apollo/v1',
};

const REST_BASE_CONSTS = {
  APOLLO_SCHEDULER_REST_BASE: 'scheduler',
};

function walkPhp(dir, out = []) {
  if (!fs.existsSync(dir)) return out;
  for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
    if (['vendor', 'node_modules', '.git', 'tests', 'test'].includes(ent.name)) continue;
    const p = path.join(dir, ent.name);
    if (ent.isDirectory()) walkPhp(p, out);
    else if (ent.name.endsWith('.php')) out.push(p);
  }
  return out;
}

function rel(p) {
  return path.relative(ROOT, p).replace(/\\/g, '/');
}

function stripPhpComments(code) {
  let out = '';
  let i = 0;
  while (i < code.length) {
    if (code[i] === '/' && code[i + 1] === '/') {
      while (i < code.length && code[i] !== '\n') i++;
      continue;
    }
    if (code[i] === '#') {
      while (i < code.length && code[i] !== '\n') i++;
      continue;
    }
    if (code[i] === '/' && code[i + 1] === '*') {
      i += 2;
      while (i < code.length && !(code[i] === '*' && code[i + 1] === '/')) i++;
      i += 2;
      continue;
    }
    out += code[i];
    i++;
  }
  return out;
}

function loadPluginConstants(pluginDir) {
  const defs = {};
  const candidates = [
    path.join(pluginDir, 'includes/constants.php'),
    path.join(pluginDir, 'includes/constants.php'.replace('includes/', '')),
  ];
  for (const f of candidates) {
    if (!fs.existsSync(f)) continue;
    const t = fs.readFileSync(f, 'utf8');
    for (const m of t.matchAll(/define\s*\(\s*['"]([A-Z_]+)['"]\s*,\s*['"]([^'"]+)['"]/g)) {
      defs[m[1]] = m[2];
    }
  }
  return defs;
}

function getPluginDir(filePath) {
  const parts = filePath.split(/[/\\]/);
  const slug = parts.find((p) => p.startsWith('apollo-'));
  return slug ? path.join(ROOT, slug) : null;
}

function extractFileContext(content, filePath) {
  const ctx = { namespace: 'apollo/v1', rest_base: null, base: null, rb: null };
  const pluginDir = getPluginDir(filePath);
  const pluginConsts = pluginDir ? loadPluginConstants(pluginDir) : {};
  const ns =
    content.match(/(?:protected|private|public)\s+(?:string\s+)?\$namespace\s*=\s*['"]([^'"]+)['"]/) ||
    content.match(/(?:protected|private|public)\s+(?:string\s+)?\$namespace\s*=\s*([A-Z_]+)/);
  if (ns) ctx.namespace = NS_CONSTS[ns[1]] || ns[1];

  const rb =
    content.match(/(?:protected|private|public)\s+(?:string\s+)?\$rest_base\s*=\s*['"]([^'"]+)['"]/) ||
    content.match(/(?:protected|private|public)\s+(?:string\s+)?\$rest_base\s*=\s*([A-Z_]+)/) ||
    content.match(/\$this->rest_base\s*=\s*['"]([^'"]+)['"]/) ||
    content.match(/\$this->rest_base\s*=\s*([A-Z_]+)/);
  if (rb) ctx.rest_base = REST_BASE_CONSTS[rb[1]] || pluginConsts[rb[1]] || rb[1];

  const base = content.match(/(?:private|protected)\s+string\s+\$base\s*=\s*['"]([^'"]+)['"]/);
  if (base) ctx.base = base[1];

  const selfBase = content.match(/private\s+const\s+BASE\s*=\s*['"]([^'"]+)['"]/);
  if (selfBase) ctx.rest_base = selfBase[1];

  if (!ctx.rest_base && pluginConsts.APOLLO_SCHEDULER_REST_BASE) {
    ctx.rest_base = pluginConsts.APOLLO_SCHEDULER_REST_BASE;
  }

  const localNs = content.match(/\$ns\s*=\s*['"]([^'"]+)['"]/);
  if (localNs) ctx.namespace = localNs[1];

  const rbAssign = content.match(/\$rb\s*=\s*['"]\/['"]\s*\.\s*\$this->rest_base/);
  if (rbAssign && ctx.rest_base) ctx.rb = `/${ctx.rest_base}`;

  return ctx;
}

function resolveExpr(expr, ctx) {
  expr = expr.trim().replace(/^['"]|['"]$/g, '');
  if (NS_CONSTS[expr]) return NS_CONSTS[expr];
  if (REST_BASE_CONSTS[expr]) return REST_BASE_CONSTS[expr];
  if (expr === '$this->namespace') return ctx.namespace;
  if (expr === '$this->rest_base' && ctx.rest_base) return ctx.rest_base;
  if (expr === '$this->base' && ctx.base) return ctx.base;
  if (expr === 'self::BASE' && ctx.rest_base) return ctx.rest_base;
  if (/^['"][^'"]+['"]$/.test(expr.trim())) return expr.replace(/^['"]|['"]$/g, '');
  return expr;
}

function resolveRoutePath(raw, ctx) {
  let p = raw.trim();
  // Pure literal route: '/events' (no concatenation)
  if (/^['"][^'"]+['"]$/.test(p)) return p.slice(1, -1);

  // '/' . $this->rest_base . '/foo'
  const concat = p.match(/^['"]\/['"]\s*\.\s*\$this->(rest_base|base)\s*(?:\.\s*['"]([^'"]+)['"])?/);
  if (concat) {
    const base = concat[1] === 'base' ? ctx.base : ctx.rest_base;
    const suffix = concat[2] || '';
    return base ? `/${base}${suffix}` : p;
  }

  // '/' . self::BASE . '/toggle/...'
  const selfConcat = p.match(/^['"]\/['"]\s*\.\s*self::BASE\s*(?:\.\s*['"]([^'"]+)['"])?/);
  if (selfConcat && ctx.rest_base) {
    return `/${ctx.rest_base}${selfConcat[1] || ''}`;
  }

  // $rb . '/proximos'
  const rbConcat = p.match(/^\$rb\s*\.\s*['"]([^'"]+)['"]/);
  if (rbConcat && ctx.rb) return `${ctx.rb}${rbConcat[1]}`;
  if (p === '$rb' && ctx.rb) return ctx.rb;

  // '/' . $def['rest_base'] . '/(?P<id>\d+)/fragmento'
  const defConcat = p.match(/^['"]\/['"]\s*\.\s*\$def\[['"]rest_base['"]\]\s*\.\s*['"]([^'"]+)['"]/);
  if (defConcat) return `/{rest_base}${defConcat[1]}`;

  // '/search/' . $type
  const typeConcat = p.match(/^['"]([^'"]*)['"]\s*\.\s*\$(\w+)/);
  if (typeConcat) return `${typeConcat[1]}{$${typeConcat[2]}}`;

  // '/pane/section/' . $safe_slug . '/(?P<id>\d+)'
  const slugConcat = p.match(/^['"]([^'"]*)['"]\s*\.\s*\$(\w+)\s*\.\s*['"]([^'"]+)['"]/);
  if (slugConcat) return `${slugConcat[1]}{$${slugConcat[2]}}${slugConcat[3]}`;

  if (p.startsWith('/')) return p;
  if (p.startsWith('$')) return p;
  return p;
}

function findMatchingParen(s, openIdx) {
  let depth = 0;
  let inStr = null;
  let esc = false;
  for (let i = openIdx; i < s.length; i++) {
    const c = s[i];
    if (inStr) {
      if (esc) esc = false;
      else if (c === '\\') esc = true;
      else if (c === inStr) inStr = null;
      continue;
    }
    if (c === "'" || c === '"') {
      inStr = c;
      continue;
    }
    if (c === '(') depth++;
    else if (c === ')') {
      depth--;
      if (depth === 0) return i;
    }
  }
  return -1;
}

function parseMethods(block) {
  const methods = new Set();
  const re = /['"]methods['"]\s*=>\s*([^,\n]+)/g;
  let m;
  while ((m = re.exec(block)) !== null) {
    let val = m[1].trim();
    if (val.startsWith('array(')) {
      const inner = val.slice(6, -1);
      for (const part of inner.split(',')) {
        const lit = part.trim().replace(/^['"]|['"]$/g, '');
        if (lit) {
          if (WP_METHOD_MAP[lit]) WP_METHOD_MAP[lit].split(',').forEach((x) => methods.add(x));
          else methods.add(lit.toUpperCase());
        }
      }
    } else if (WP_METHOD_MAP[val]) {
      WP_METHOD_MAP[val].split(',').forEach((x) => methods.add(x));
    } else if (/^['"][A-Z,]+['"]$/.test(val)) {
      val.replace(/['"]/g, '').split(',').forEach((x) => methods.add(x.trim()));
    } else if (val.includes('::')) {
      const mapped = WP_METHOD_MAP[val];
      if (mapped) mapped.split(',').forEach((x) => methods.add(x));
      else methods.add(val.replace(/^\\/, ''));
    } else if (val.startsWith('$')) {
      methods.add(val);
    } else {
      methods.add(val.toUpperCase());
    }
  }
  return methods.size ? [...methods].sort().join(',') : '?';
}

function parsePermission(block) {
  const m = block.match(/['"]permission_callback['"]\s*=>\s*/);
  if (!m) return '?';
  let i = m.index + m[0].length;
  while (block[i] === ' ') i++;
  if (block.slice(i, i + 6) === 'array(') {
    const close = findMatchingParen(block, i + 5);
    return block.slice(i + 6, close).replace(/\s+/g, ' ').trim();
  }
  if (block[i] === '(') {
    const close = findMatchingParen(block, i);
    const p = block.slice(i, close + 1);
    return p.length > 80 ? p.slice(0, 77) + '...' : p;
  }
  if (block.slice(i, i + 8) === 'function') {
    const close = findMatchingParen(block, block.indexOf('(', i));
    const p = block.slice(i, close + 1);
    return p.length > 80 ? p.slice(0, 77) + '...' : p;
  }
  const rest = block.slice(i).match(/^([^,\n]+)/);
  return rest ? rest[1].trim() : '?';
}

function splitTopLevelArgs(s) {
  const args = [];
  let cur = '';
  let depth = 0;
  let inStr = null;
  let esc = false;
  for (let i = 0; i < s.length; i++) {
    const c = s[i];
    if (inStr) {
      cur += c;
      if (esc) esc = false;
      else if (c === '\\') esc = true;
      else if (c === inStr) inStr = null;
      continue;
    }
    if (c === "'" || c === '"') {
      inStr = c;
      cur += c;
      continue;
    }
    if (c === '(' || c === '[') depth++;
    if (c === ')' || c === ']') depth--;
    if (c === ',' && depth === 0) {
      args.push(cur.trim());
      cur = '';
      continue;
    }
    cur += c;
  }
  if (cur.trim()) args.push(cur.trim());
  return args;
}

function splitRouteBlocks(opts) {
  const inner = opts.trim().replace(/^array\s*\(/, '').replace(/\)\s*$/, '');
  const blocks = [];
  let cur = '';
  let depth = 0;
  let inStr = null;
  let esc = false;
  for (let i = 0; i < inner.length; i++) {
    const c = inner[i];
    if (inStr) {
      cur += c;
      if (esc) esc = false;
      else if (c === '\\') esc = true;
      else if (c === inStr) inStr = null;
      continue;
    }
    if (c === "'" || c === '"') {
      inStr = c;
      cur += c;
      continue;
    }
    if (c === '(' || c === '[') depth++;
    if (c === ')' || c === ']') depth--;
    if (c === ',' && depth === 0) {
      if (cur.trim()) blocks.push(cur.trim());
      cur = '';
      continue;
    }
    cur += c;
  }
  if (cur.trim()) blocks.push(cur.trim());
  return blocks.filter((b) => b.includes('methods'));
}

function lineAt(content, idx) {
  return content.slice(0, idx).split('\n').length;
}

function normalizePath(ep) {
  let p = ep.replace(/^apollo\/v\d+\/?/, '').replace(/^apollo-telegram\/v\d+\/?/, '');
  if (!p.startsWith('/')) p = '/' + p;
  p = p.replace(/\/+/g, '/');
  // unify regex escapes for matching
  p = p.replace(/\\\\/g, '\\');
  return p;
}

function loadRegistry() {
  const reg = { rest: new Set(), ajax: new Set(), shortcodes: new Set() };
  if (!fs.existsSync(REGISTRY_DIR)) return reg;
  for (const f of fs.readdirSync(REGISTRY_DIR).filter((x) => x.endsWith('.json'))) {
    const data = JSON.parse(fs.readFileSync(path.join(REGISTRY_DIR, f), 'utf8'));
    if (Array.isArray(data.rest)) {
      for (const r of data.rest) {
        const ep = r.endpoint || r.path || '';
        if (ep) reg.rest.add(normalizePath(ep));
      }
    }
    const ajax = data.ajax;
    if (ajax && typeof ajax === 'object') {
      for (const key of ['nopriv', 'priv']) {
        if (Array.isArray(ajax[key])) {
          for (const a of ajax[key]) reg.ajax.add(a.replace(/^nopriv_/, ''));
        }
      }
    }
    if (Array.isArray(data.shortcodes)) {
      for (const sc of data.shortcodes) {
        const tag = typeof sc === 'string' ? sc : sc.tag;
        if (tag) reg.shortcodes.add(tag);
      }
    }
    if (data.$deep_scan?.shortcodes) {
      for (const tag of data.$deep_scan.shortcodes) reg.shortcodes.add(tag);
    }
  }
  return reg;
}

function inRegistry(kind, value, registry) {
  if (kind === 'REST') return registry.rest.has(normalizePath(value)) ? 'yes' : 'no';
  if (kind === 'AJAX') return registry.ajax.has(value) ? 'yes' : 'no';
  if (kind === 'SHORTCODE') return registry.shortcodes.has(value) ? 'yes' : 'no';
  return '?';
}

function extractRestRoutes(content, filePath) {
  const stripped = stripPhpComments(content);
  const ctx = extractFileContext(content, filePath);
  const rows = [];
  const re = /register_rest_route\s*\(/g;
  let match;
  while ((match = re.exec(stripped)) !== null) {
    const start = match.index;
    const open = stripped.indexOf('(', start);
    const close = findMatchingParen(stripped, open);
    if (close < 0) continue;
    const call = stripped.slice(open + 1, close);
    const args = splitTopLevelArgs(call);
    if (args.length < 2) continue;

    let ns = resolveExpr(args[0], ctx);
    if (ns.includes('defined(')) {
      const t = ns.match(/:\s*['"]([^'"]+)['"]/);
      ns = t ? t[1] : 'apollo/v1';
    }
    if (NS_CONSTS[ns]) ns = NS_CONSTS[ns];

    const routePath = resolveRoutePath(args[1], ctx);
    const opts = args[2] || '';

    const emit = (block) => {
      const methods = parseMethods(block);
      const permission = parsePermission(block);
      const ep = `${ns}${routePath.startsWith('/') ? routePath : '/' + routePath}`;
      rows.push({
        kind: 'REST',
        endpoint: ep.replace(/\/+/g, '/'),
        methods,
        permission,
        fileLine: `${rel(filePath)}:${lineAt(content, start)}`,
      });
    };

    if (opts.trim().startsWith('array(') && !opts.includes("'methods'") && !opts.includes('"methods"')) {
      for (const block of splitRouteBlocks(opts)) emit(block);
    } else {
      emit(opts || call);
    }
  }
  return rows;
}

function scan() {
  const plugins = fs
    .readdirSync(ROOT)
    .filter((d) => d.startsWith('apollo-') && d !== 'apollo-waha' && fs.statSync(path.join(ROOT, d)).isDirectory())
    .sort();

  const registry = loadRegistry();
  const allRows = [];

  for (const plugin of plugins) {
    for (const file of walkPhp(path.join(ROOT, plugin))) {
      const content = fs.readFileSync(file, 'utf8');

      for (const row of extractRestRoutes(content, file)) {
        row.inRegistry = inRegistry('REST', row.endpoint, registry);
        allRows.push(row);
      }

      const ajaxRe = /wp_ajax_nopriv_([a-zA-Z0-9_-]+)/g;
      let am;
      while ((am = ajaxRe.exec(content)) !== null) {
        allRows.push({
          kind: 'AJAX',
          endpoint: `admin-ajax.php?action=${am[1]}`,
          methods: 'POST',
          permission: 'nopriv',
          fileLine: `${rel(file)}:${lineAt(content, am.index)}`,
          inRegistry: inRegistry('AJAX', am[1], registry),
        });
      }

      const scRe = /add_shortcode\s*\(\s*['"]([^'"]+)['"]/g;
      let sm;
      while ((sm = scRe.exec(content)) !== null) {
        allRows.push({
          kind: 'SHORTCODE',
          endpoint: `[${sm[1]}]`,
          methods: 'render',
          permission: 'public',
          fileLine: `${rel(file)}:${lineAt(content, sm.index)}`,
          inRegistry: inRegistry('SHORTCODE', sm[1], registry),
        });
      }
    }
  }

  allRows.sort((a, b) => {
    const k = { REST: 0, AJAX: 1, SHORTCODE: 2 };
    return k[a.kind] - k[b.kind] || a.endpoint.localeCompare(b.endpoint) || a.fileLine.localeCompare(b.fileLine);
  });

  return { allRows, registry, plugins };
}

function tsvEscape(s) {
  return String(s).replace(/\t/g, ' ').replace(/\n/g, ' ');
}

function writeOutputs({ allRows, plugins }) {
  const header = 'endpoint\tmethods\tpermission\tfile:line\tin_registry?';
  const lines = [header];
  for (const r of allRows) {
    lines.push([r.endpoint, r.methods, r.permission, r.fileLine, r.inRegistry].map(tsvEscape).join('\t'));
  }
  fs.writeFileSync(path.join(OUT_DIR, 'W3-surface.tsv'), lines.join('\n') + '\n');

  const rest = allRows.filter((r) => r.kind === 'REST');
  const ajax = allRows.filter((r) => r.kind === 'AJAX');
  const sc = allRows.filter((r) => r.kind === 'SHORTCODE');
  const restMissing = rest.filter((r) => r.inRegistry === 'no');
  const ajaxMissing = ajax.filter((r) => r.inRegistry === 'no');
  const scMissing = sc.filter((r) => r.inRegistry === 'no');

  const md = `# W3 SURFACE Report

**Worker:** W3 SURFACE (Composer 2.5)  
**Generated:** ${new Date().toISOString().slice(0, 10)}  
**Scope:** \`apollo-*\` plugins (excludes \`apollo-waha\`)  
**Sources:** live PHP scan + \`_inventory/registry/09-plugins/*.json\`  
**Reference docs:** \`_inventory/APOLLO_ROUTES_INVENTORY.md\`, \`_inventory/registry/11-security.json\`  
**Note:** chapter files \`06-REST\` / \`08-SHORTCODES\` / \`12-SECURITY\` not present on disk (registry uses \`11-security.json\`, \`14-routing.json\`)

## Summary

| Surface | Count | In registry | Missing from registry |
|---------|------:|------------:|----------------------:|
| REST (\`register_rest_route\`) | ${rest.length} | ${rest.length - restMissing.length} | ${restMissing.length} |
| AJAX (\`wp_ajax_nopriv_\`) | ${ajax.length} | ${ajax.length - ajaxMissing.length} | ${ajaxMissing.length} |
| Shortcodes (\`add_shortcode\`) | ${sc.length} | ${sc.length - scMissing.length} | ${scMissing.length} |
| **Total rows** | **${allRows.length}** | | |

**Plugins scanned:** ${plugins.length}

## TSV columns

\`endpoint | methods | permission | file:line | in_registry?\`

Full machine-readable output: [W3-surface.tsv](./W3-surface.tsv)

## REST — not in registry (${restMissing.length})

${restMissing.length ? restMissing.slice(0, 80).map((r) => `- \`${r.endpoint}\` ${r.methods} — ${r.fileLine}`).join('\n') + (restMissing.length > 80 ? `\n- … and ${restMissing.length - 80} more (see TSV)` : '') : '_none_'}

## AJAX nopriv — not in registry (${ajaxMissing.length})

${ajaxMissing.length ? ajaxMissing.map((r) => `- \`${r.endpoint}\` — ${r.fileLine}`).join('\n') : '_none_'}

## Shortcodes — not in registry (${scMissing.length})

${scMissing.length ? scMissing.map((r) => `- \`${r.endpoint}\` — ${r.fileLine}`).join('\n') : '_none_'}

## Notes

- Registry REST paths matched on normalized route suffix (namespace stripped, regex escapes unified).
- \`permission\` = raw \`permission_callback\` expression (or \`nopriv\`/\`public\` for AJAX/shortcodes).
- Dynamic routes left unresolved when \`rest_base\`/\`base\` cannot be inferred from the same file.
- Example-only surfaces: \`apollo-templates/examples/user-radar-examples.php\`.
`;

  fs.writeFileSync(path.join(OUT_DIR, 'W3-surface.md'), md);
  console.log(`Wrote ${allRows.length} rows (${rest.length} REST, ${ajax.length} AJAX, ${sc.length} SC)`);
  console.log(`Missing: REST ${restMissing.length}, AJAX ${ajaxMissing.length}, SC ${scMissing.length}`);
}

writeOutputs(scan());
