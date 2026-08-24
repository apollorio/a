/**
 * Apollo full technical audit → apollo-registry-2.json
 * Option A: full clone of registry-v3 + deep live code audit of target plugins.
 *
 * Scans each plugin tree for:
 *  - WP plugin headers + version constants
 *  - composer.json (name, require, PSR-4 namespaces)
 *  - register_post_type / register_taxonomy
 *  - register_rest_route
 *  - $wpdb tables (apollo_*)
 *  - add_shortcode
 *  - add_action/add_filter hook names (apollo/*)
 *  - wp_ajax_ / wp_ajax_nopriv_
 *  - wp_schedule_event / cron hooks
 *  - Defines / Requires Plugins
 *  - Cross-plugin dependency signals
 *
 * Usage: node audit-plugins-to-registry2.js
 */
'use strict';

const fs = require('fs');
const path = require('path');

const INVENTORY_DIR = __dirname;
const PLUGINS_ROOT = path.resolve(INVENTORY_DIR, '..');
const SRC_REGISTRY = path.join(INVENTORY_DIR, 'apollo-registry.json');
const OUT_REGISTRY = path.join(INVENTORY_DIR, 'apollo-registry-2.json');
const OUT_REPORT = path.join(INVENTORY_DIR, 'apollo-registry-2-audit-report.json');

const TARGETS = [
  'apollo-templates',
  'apollo-users',
  'apollo-wow',
  'apollo-admin',
  'apollo-adverts',
  'apollo-calendar',
  'apollo-chat',
  'apollo-coauthor',
  'apollo-comment',
  'apollo-core',
  'apollo-dashboard',
  'apollo-djs',
  'apollo-dj-sync',
  'apollo-docs',
  'apollo-elementor',
  'apollo-elementor-pro',
  'apollo-email',
  'apollo-events',
  'apollo-fav',
  'apollo-gestor',
  'apollo-groups',
  'apollo-hub',
  'apollo-journal',
  'apollo-loc',
  'apollo-login',
  'apollo-maps',
  'apollo-membership',
  'apollo-mod',
  'apollo-notif',
  'apollo-pane-engine',
  'apollo-radio',
  'apollo-remind',
  'apollo-scheduler',
  'apollo-seo',
  'apollo-sheets',
  'apollo-sign',
  'apollo-social',
  'apollo-statistics',
  'apollo-telegram',
];

const SKIP_DIRS = new Set([
  'vendor',
  'node_modules',
  '.git',
  '.svn',
  'dist',
  'build',
  'coverage',
  'tests',
  'test',
  '__tests__',
  '.github',
  'cache',
]);

const ALL_APOLLO_SLUGS = new Set([
  ...TARGETS,
  'apollo-classifieds',
  'apollo-suppliers',
  'apollo-shortcodes',
  'apollo-cena',
  'apollo-pwa',
  'apollo-runtime',
  'apollo-brain',
]);

function today() {
  return new Date().toISOString().slice(0, 10);
}

function readText(file) {
  try {
    return fs.readFileSync(file, 'utf8');
  } catch {
    return null;
  }
}

function walkPhpFiles(dir, acc = [], depth = 0) {
  if (depth > 10) return acc;
  let ents;
  try {
    ents = fs.readdirSync(dir, { withFileTypes: true });
  } catch {
    return acc;
  }
  for (const e of ents) {
    if (e.name.startsWith('.') && e.name !== '.php') {
      // skip hidden except we don't care
    }
    const p = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (SKIP_DIRS.has(e.name.toLowerCase())) continue;
      walkPhpFiles(p, acc, depth + 1);
    } else if (/\.php$/i.test(e.name)) {
      acc.push(p);
    }
  }
  return acc;
}

function parsePluginHeader(content) {
  if (!content) return {};
  // Headers live in the first ~8KB docblock/comment
  const head = content.slice(0, 12000);
  const get = (label) => {
    const re = new RegExp(
      `^[ \\t]*\\*?[ \\t]*${label}[ \\t]*:[ \\t]*(.+)$`,
      'im'
    );
    const m = head.match(re);
    return m ? m[1].trim() : null;
  };
  return {
    plugin_name: get('Plugin Name'),
    plugin_uri: get('Plugin URI'),
    description: get('Description'),
    version: get('Version'),
    author: get('Author'),
    author_uri: get('Author URI'),
    license: get('License'),
    text_domain: get('Text Domain'),
    domain_path: get('Domain Path'),
    requires_at_least: get('Requires at least'),
    requires_php: get('Requires PHP'),
    requires_plugins: get('Requires Plugins'),
    network: get('Network'),
  };
}

function extractPackageNamespace(content) {
  if (!content) return null;
  const m = content.slice(0, 15000).match(/@package\s+([A-Za-z0-9_\\]+)/);
  return m ? m[1] : null;
}

function extractPhpNamespaces(files) {
  const ns = new Set();
  for (const f of files.slice(0, 80)) {
    const t = readText(f);
    if (!t) continue;
    // first namespace declaration only per file
    const m = t.match(/^\s*namespace\s+([A-Za-z0-9_\\]+)\s*;/m);
    if (m) ns.add(m[1]);
  }
  return [...ns].sort();
}

function extractVersionConstants(content) {
  if (!content) return [];
  const out = [];
  const re =
    /define\s*\(\s*['"]([A-Z0-9_]+VERSION[A-Z0-9_]*)['"]\s*,\s*['"]([^'"]+)['"]\s*\)/gi;
  let m;
  while ((m = re.exec(content))) {
    out.push({ constant: m[1], value: m[2] });
  }
  // also const FOO_VERSION = 'x'
  const re2 =
    /(?:const|define)\s+([A-Z0-9_]*VERSION[A-Z0-9_]*)\s*=\s*['"]([^'"]+)['"]/gi;
  while ((m = re2.exec(content))) {
    if (!out.find((x) => x.constant === m[1])) {
      out.push({ constant: m[1], value: m[2] });
    }
  }
  return out;
}

function parseComposer(dir) {
  const p = path.join(dir, 'composer.json');
  const raw = readText(p);
  if (!raw) return null;
  try {
    const j = JSON.parse(raw);
    const psr4 = (j.autoload && j.autoload['psr-4']) || {};
    return {
      name: j.name || null,
      description: j.description || null,
      type: j.type || null,
      require: j.require || {},
      require_dev: j['require-dev'] || {},
      autoload_psr4: psr4,
      namespaces: Object.keys(psr4).map((k) => k.replace(/\\$/, '')),
    };
  } catch (e) {
    return { parse_error: String(e.message || e) };
  }
}

function uniqueSorted(arr) {
  return [...new Set(arr.filter(Boolean))].sort();
}

function extractStringLiteralsNear(callRe, content, max = 200) {
  // Find call sites and grab first string arg
  const results = [];
  const re = new RegExp(callRe.source + String.raw`\s*\(\s*(['"])([^'"]+)\1`, 'g');
  let m;
  while ((m = re.exec(content))) {
    results.push(m[2]);
    if (results.length >= max) break;
  }
  return results;
}

const REST_METHOD_MAP = {
  READABLE: ['GET'],
  CREATABLE: ['POST'],
  EDITABLE: ['POST', 'PUT', 'PATCH'],
  DELETABLE: ['DELETE'],
  ALLMETHODS: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
};

function parseMethodsNear(slice) {
  const methods = [];
  // WP_REST_Server::READABLE etc (possibly multiple method arrays)
  for (const m of slice.matchAll(/WP_REST_Server::(\w+)/g)) {
    const mapped = REST_METHOD_MAP[m[1].toUpperCase()];
    if (mapped) methods.push(...mapped);
    else methods.push(m[1]);
  }
  // 'methods' => 'GET' or "POST"
  for (const m of slice.matchAll(/['"]methods['"]\s*=>\s*['"]([^'"]+)['"]/gi)) {
    methods.push(
      ...m[1]
        .split(/[|,\/\s]+/)
        .map((s) => s.trim().toUpperCase())
        .filter((s) => /^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)$/.test(s))
    );
  }
  // 'methods' => [ 'GET', 'POST' ]
  for (const m of slice.matchAll(/['"]methods['"]\s*=>\s*\[([^\]]+)\]/gi)) {
    for (const x of m[1].matchAll(/['"]([A-Z]+)['"]/g)) {
      methods.push(x[1]);
    }
  }
  return uniqueSorted(methods);
}

function resolveRestNamespace(nsExpr, content) {
  const expr = (nsExpr || '').replace(/\s+/g, ' ').trim();
  if (!expr) return 'apollo/v1';
  // string literal
  const lit = expr.match(/^['"]([^'"]+)['"]$/);
  if (lit) return lit[1];
  // self::NAMESPACE / static::NAMESPACE / ApolloRoute::NAMESPACE
  if (/NAMESPACE|REST_NAMESPACE|REST_BASE/i.test(expr)) {
    // try class const NAMESPACE = '...'
    const constNs = content.match(
      /(?:const|public\s+const|private\s+const|protected\s+const)\s+NAMESPACE\s*=\s*['"]([^'"]+)['"]/
    );
    if (constNs) return constNs[1];
    const defineNs = content.match(
      /define\s*\(\s*['"]APOLLO_[A-Z0-9_]*REST_NAMESPACE['"]\s*,\s*['"]([^'"]+)['"]/
    );
    if (defineNs) return defineNs[1];
    if (/APOLLO_REST_NAMESPACE|ApolloRoute::NAMESPACE/i.test(expr)) return 'apollo/v1';
  }
  // $this->namespace / $namespace — look for assignment
  const assign = content.match(
    /(?:\$this->namespace|\$namespace|protected\s+\$namespace|private\s+\$namespace|public\s+\$namespace)\s*=\s*['"]([^'"]+)['"]/
  );
  if (assign) return assign[1];
  // default Apollo REST
  if (/namespace/i.test(expr)) return 'apollo/v1';
  return expr.slice(0, 80);
}

function extractRestRoutes(content) {
  const routes = [];
  // Multiline-safe: register_rest_route( NS_EXPR , '/path'
  const re =
    /register_rest_route\s*\(\s*([^,]+?)\s*,\s*(['"])(\/[^'"]*|[^'"]+)\2/gs;
  let m;
  while ((m = re.exec(content))) {
    const nsExpr = m[1];
    // skip comment-only / doc examples with ApolloRoute::EVENTS style route constants without leading slash sometimes
    let route = m[3];
    if (!route.startsWith('/')) route = `/${route}`;
    // ignore pure constant route placeholders without path chars if too weird
    if (/^[A-Z_]+$/.test(route.slice(1))) continue;

    const slice = content.slice(m.index, m.index + 900);
    const methods = parseMethodsNear(slice);
    const ns = resolveRestNamespace(nsExpr, content);

    routes.push({
      namespace: ns,
      endpoint: route,
      methods: methods.length ? methods : ['?'],
    });
  }
  return routes;
}

function extractCpts(content) {
  const cpts = new Set(extractStringLiteralsNear(/register_post_type/, content));
  // Constants: APOLLO_*_CPT = 'event' or define('APOLLO_EVENT_CPT', 'event')
  let re =
    /(?:define\s*\(\s*['"][A-Z0-9_]*CPT[A-Z0-9_]*['"]\s*,\s*['"]([a-z0-9_-]+)['"]|[A-Z0-9_]*CPT[A-Z0-9_]*\s*=\s*['"]([a-z0-9_-]+)['"])/g;
  let m;
  while ((m = re.exec(content))) {
    const v = m[1] || m[2];
    if (v && !/^(apollo|true|false)$/i.test(v) && v.length < 40) cpts.add(v);
  }
  // post_type => 'xxx' in register arrays owned by plugin
  re = /['"]post_type['"]\s*=>\s*['"]([a-z0-9_-]+)['"]/g;
  while ((m = re.exec(content))) {
    if (!['post', 'page', 'attachment', 'revision', 'nav_menu_item'].includes(m[1])) {
      // weak signal — only if looks like apollo content
      if (/^(event|dj|loc|local|classified|supplier|doc|hub|journal|sheet|email|appointment|resource|service)/i.test(m[1])) {
        cpts.add(m[1]);
      }
    }
  }
  return [...cpts];
}

function extractTaxonomies(content) {
  const tax = new Set(extractStringLiteralsNear(/register_taxonomy/, content));
  let re =
    /(?:define\s*\(\s*['"][A-Z0-9_]*TAX[A-Z0-9_]*['"]\s*,\s*['"]([a-z0-9_-]+)['"]|[A-Z0-9_]*(?:TAX|TAXONOMY)[A-Z0-9_]*\s*=\s*['"]([a-z0-9_-]+)['"])/g;
  let m;
  while ((m = re.exec(content))) {
    const v = m[1] || m[2];
    if (v && v.length < 40) tax.add(v);
  }
  return [...tax];
}

function extractShortcodes(content) {
  return extractStringLiteralsNear(/add_shortcode/, content);
}

function isLikelyTableName(name) {
  if (!name || typeof name !== 'string') return false;
  // must be lowercase-ish db table style: apollo_snake_case, no constants
  if (!/^apollo_[a-z][a-z0-9_]*$/.test(name)) return false;
  if (name.length > 64) return false;
  // reject option/transient/meta-like suffixes
  const reject =
    /(nonce|version|url|path|file|dir|basename|option|cache|hook|cron|slug|meta_key|capability|permission|rewrite|activated|settings|config)$/i;
  if (reject.test(name)) return false;
  return true;
}

function extractTables(content) {
  const tables = new Set();
  let m;

  // $wpdb->prefix . 'apollo_xxx'
  let re = /\$wpdb\s*->\s*prefix\s*\.\s*['"](apollo_[a-z0-9_]+)['"]/gi;
  while ((m = re.exec(content))) {
    if (isLikelyTableName(m[1])) tables.add(m[1]);
  }

  // "{$wpdb->prefix}apollo_xxx"
  re = /\{?\s*\$wpdb\s*->\s*prefix\s*\}?(apollo_[a-z0-9_]+)/gi;
  while ((m = re.exec(content))) {
    if (isLikelyTableName(m[1])) tables.add(m[1]);
  }

  // define( 'APOLLO_FAV_TABLE', 'apollo_favs' ) or *_TABLE* = 'apollo_x'
  re =
    /(?:define\s*\(\s*['"][A-Z0-9_]*TABLE[A-Z0-9_]*['"]\s*,\s*['"](apollo_[a-z0-9_]+)['"]|[A-Z0-9_]*TABLE[A-Z0-9_]*\s*=\s*['"](apollo_[a-z0-9_]+)['"])/g;
  while ((m = re.exec(content))) {
    const v = m[1] || m[2];
    if (isLikelyTableName(v)) tables.add(v);
  }

  // CREATE TABLE IF NOT EXISTS {$wpdb->prefix}apollo_x
  re =
    /CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:\{?\$wpdb->prefix\}?|`?\{prefix\}`?)?(apollo_[a-z0-9_]+)/gi;
  while ((m = re.exec(content))) {
    if (isLikelyTableName(m[1])) tables.add(m[1]);
  }

  // dbDelta SQL strings with table name only when clearly prefixed pattern
  re = /CREATE\s+TABLE[^\n]*?(apollo_[a-z0-9_]+)/gi;
  while ((m = re.exec(content))) {
    if (isLikelyTableName(m[1])) tables.add(m[1]);
  }

  return [...tables];
}

function extractAjax(content) {
  const actions = new Set();
  const re = /add_action\s*\(\s*['"]wp_ajax(?:_nopriv)?_([^'"]+)['"]/g;
  let m;
  while ((m = re.exec(content))) actions.add(m[1]);
  return [...actions].sort();
}

function extractCron(content) {
  const hooks = new Set();
  // wp_schedule_event( ..., 'hook' )
  let re = /wp_schedule_(?:event|single_event)\s*\([^;]*?['"]([a-z0-9_\/\-]+)['"]/gis;
  let m;
  while ((m = re.exec(content))) {
    const h = m[1];
    if (!/^(hourly|twicedaily|daily|weekly)$/i.test(h)) hooks.add(h);
  }
  re = /add_action\s*\(\s*['"]([a-z0-9_]*cron[a-z0-9_]*)['"]/gi;
  while ((m = re.exec(content))) hooks.add(m[1]);
  return [...hooks].sort();
}

function extractApolloHooks(content) {
  const fired = new Set();
  const listened = new Set();
  let re = /(?:do_action|apply_filters)(?:_ref_array)?\s*\(\s*['"](apollo[^'"]*)['"]/g;
  let m;
  while ((m = re.exec(content))) fired.add(m[1]);
  re = /add_(?:action|filter)\s*\(\s*['"](apollo[^'"]*)['"]/g;
  while ((m = re.exec(content))) listened.add(m[1]);
  return {
    hooks_fired: [...fired].sort(),
    hooks_listened: [...listened].sort(),
  };
}

function extractProvidesGuess(slug, description, rest, cpts, tables) {
  const provides = new Set();
  const base = slug.replace(/^apollo-/, '');
  provides.add(base);
  if (cpts.length) provides.add('cpt');
  if (tables.length) provides.add('db-tables');
  if (rest.length) provides.add('rest-api');
  if (/master registry|fallback|foundation/i.test(description || '')) {
    provides.add('master-registry');
  }
  return [...provides];
}

function detectDependencies(slug, contentAll, header, composer, existingDeps) {
  const deps = new Set(existingDeps || []);
  // Requires Plugins header (comma-separated slugs)
  if (header.requires_plugins) {
    header.requires_plugins.split(/[,\s]+/).filter(Boolean).forEach((d) => {
      if (d.startsWith('apollo-')) deps.add(d);
    });
  }
  // composer require apollo/*
  if (composer && composer.require) {
    for (const k of Object.keys(composer.require)) {
      if (/apollo/i.test(k)) {
        const slugGuess = k.split('/').pop();
        if (slugGuess.startsWith('apollo-')) deps.add(slugGuess);
      }
    }
  }
  // function_exists / class_exists / is_plugin_active / defined constants
  const rePlugin =
    /(?:is_plugin_active|plugin_basename)\s*\(\s*['"]([^'"]*apollo-[^'"]+)['"]/gi;
  let m;
  while ((m = rePlugin.exec(contentAll))) {
    const part = m[1].split('/')[0];
    if (part.startsWith('apollo-') && part !== slug) deps.add(part);
  }
  // defined( 'APOLLO_CORE_VERSION' ) etc → dependency signal
  const constMap = {
    APOLLO_CORE_: 'apollo-core',
    APOLLO_LOGIN_: 'apollo-login',
    APOLLO_USERS_: 'apollo-users',
    APOLLO_EVENTS_: 'apollo-events',
    APOLLO_TEMPLATES_: 'apollo-templates',
    APOLLO_ELEMENTOR_: 'apollo-elementor',
    APOLLO_CHAT_: 'apollo-chat',
    APOLLO_NOTIF_: 'apollo-notif',
    APOLLO_MEMBERSHIP_: 'apollo-membership',
    APOLLO_RADIO_: 'apollo-radio',
    APOLLO_PANE_: 'apollo-pane-engine',
  };
  for (const [prefix, dep] of Object.entries(constMap)) {
    if (dep === slug) continue;
    if (contentAll.includes(prefix) && contentAll.includes('defined')) {
      // weak signal — only if defined() check for that family appears
      const re = new RegExp(
        `defined\\s*\\(\\s*['"]${prefix}[A-Z0-9_]*['"]\\s*\\)`,
        'i'
      );
      if (re.test(contentAll)) deps.add(dep);
    }
  }
  // Almost everything depends on core if not core
  if (slug !== 'apollo-core' && /Apollo\\\\Core|apollo-core|APOLLO_CORE/i.test(contentAll)) {
    deps.add('apollo-core');
  }
  deps.delete(slug);
  return [...deps].sort();
}

function countPhpLoc(files) {
  let loc = 0;
  for (const f of files) {
    const t = readText(f);
    if (!t) continue;
    loc += t.split(/\r?\n/).length;
  }
  return loc;
}

function fileTreeSummary(dir) {
  const counts = { php: 0, js: 0, css: 0, json: 0, other: 0 };
  function walk(d, depth = 0) {
    if (depth > 8) return;
    let ents;
    try {
      ents = fs.readdirSync(d, { withFileTypes: true });
    } catch {
      return;
    }
    for (const e of ents) {
      const p = path.join(d, e.name);
      if (e.isDirectory()) {
        if (SKIP_DIRS.has(e.name.toLowerCase())) continue;
        walk(p, depth + 1);
      } else {
        const ext = path.extname(e.name).toLowerCase();
        if (ext === '.php') counts.php++;
        else if (ext === '.js' || ext === '.mjs' || ext === '.ts') counts.js++;
        else if (ext === '.css' || ext === '.scss') counts.css++;
        else if (ext === '.json') counts.json++;
        else counts.other++;
      }
    }
  }
  walk(dir);
  return counts;
}

function mergeRest(oldRest, audited) {
  // LIVE CODE is SSOT — do not keep ghost endpoints from outdated registry.
  // Enrich with old description/auth when the same endpoint still exists.
  const oldByEndpoint = new Map();
  for (const r of oldRest || []) {
    if (!r || !r.endpoint) continue;
    if (!oldByEndpoint.has(r.endpoint)) oldByEndpoint.set(r.endpoint, []);
    oldByEndpoint.get(r.endpoint).push(r);
  }
  const out = [];
  const seen = new Set();
  for (const a of audited) {
    const ep = a.endpoint;
    const methodKey = (a.methods || []).join(',');
    const key = `${methodKey}|${ep}`;
    if (seen.has(key)) continue;
    seen.add(key);
    const prevList = oldByEndpoint.get(ep) || [];
    const prev =
      prevList.find(
        (p) => (p.methods || []).join(',') === methodKey
      ) || prevList[0] || {};
    const methods =
      a.methods && a.methods[0] !== '?'
        ? a.methods
        : prev.methods || a.methods;
    out.push({
      endpoint: ep,
      methods,
      description: prev.description || `Live route (${a.namespace || 'apollo/v1'})`,
      ...(prev.auth !== undefined ? { auth: prev.auth } : {}),
      ...(a.namespace ? { rest_namespace: a.namespace } : {}),
      _source: 'live_scan',
    });
  }
  return out;
}

function restGhosts(oldRest, audited) {
  const liveEps = new Set((audited || []).map((a) => a.endpoint));
  return (oldRest || [])
    .filter((r) => r && r.endpoint && !liveEps.has(r.endpoint))
    .map((r) => ({
      endpoint: r.endpoint,
      methods: r.methods || [],
      description: r.description || null,
    }));
}

function auditPlugin(slug, oldEntry) {
  const dir = path.join(PLUGINS_ROOT, slug);
  const result = {
    slug,
    audit: {
      path: dir,
      exists: fs.existsSync(dir),
      audited_at: new Date().toISOString(),
      source: 'live-filesystem-scan',
    },
  };

  if (!result.audit.exists) {
    return {
      entry: {
        slug,
        status: 'NOT_INSTALLED',
        description: (oldEntry && oldEntry.description) || 'Directory missing on disk',
        dependencies: (oldEntry && oldEntry.dependencies) || [],
        provides: (oldEntry && oldEntry.provides) || [],
        cpts: [],
        taxonomies: [],
        tables: [],
        meta: {},
        rest: [],
        pages: (oldEntry && oldEntry.pages) || [],
        shortcodes: [],
        _audit: result.audit,
      },
      report: { slug, status: 'MISSING_DIR' },
    };
  }

  const mainPhp = path.join(dir, `${slug}.php`);
  const mainContent = readText(mainPhp);
  const header = parsePluginHeader(mainContent);
  const packageNs = extractPackageNamespace(mainContent);
  const versionConsts = extractVersionConstants(mainContent || '');
  const composer = parseComposer(dir);
  const phpFiles = walkPhpFiles(dir);
  const namespaces = extractPhpNamespaces(phpFiles);
  const fileCounts = fileTreeSummary(dir);
  const phpLoc = countPhpLoc(phpFiles);

  let allContent = mainContent || '';
  // Cap total scan size ~8MB to stay sane
  let budget = 8 * 1024 * 1024;
  for (const f of phpFiles) {
    if (f === mainPhp) continue;
    const t = readText(f);
    if (!t) continue;
    if (t.length > budget) {
      allContent += '\n' + t.slice(0, budget);
      budget = 0;
      break;
    }
    allContent += '\n' + t;
    budget -= t.length;
  }

  const cpts = uniqueSorted(extractCpts(allContent));
  const taxonomies = uniqueSorted(extractTaxonomies(allContent));
  const shortcodes = uniqueSorted(extractShortcodes(allContent));
  const tables = uniqueSorted(extractTables(allContent));
  const restRaw = extractRestRoutes(allContent);
  // dedupe rest
  const restMap = new Map();
  for (const r of restRaw) {
    const k = `${r.namespace}|${r.endpoint}|${r.methods.join(',')}`;
    if (!restMap.has(k)) restMap.set(k, r);
  }
  const restAudited = [...restMap.values()];
  const ajax = extractAjax(allContent);
  const cron = extractCron(allContent);
  const hooks = extractApolloHooks(allContent);

  const namespace =
    (composer && composer.namespaces && composer.namespaces[0]) ||
    packageNs ||
    (namespaces[0] || null) ||
    (oldEntry && oldEntry.namespace) ||
    null;

  const version =
    header.version ||
    (versionConsts[0] && versionConsts[0].value) ||
    (oldEntry && oldEntry.version) ||
    null;

  const description =
    header.description ||
    (composer && composer.description) ||
    (oldEntry && oldEntry.description) ||
    '';

  const dependencies = detectDependencies(
    slug,
    allContent,
    header,
    composer,
    (oldEntry && oldEntry.dependencies) || []
  );

  const provides =
    (oldEntry && oldEntry.provides && oldEntry.provides.length
      ? oldEntry.provides
      : extractProvidesGuess(slug, description, restAudited, cpts, tables));

  // Build merged entry: start from old (preserve rich docs), overlay live audit facts
  const entry = {
    ...(oldEntry || {}),
    slug,
    namespace: namespace || (oldEntry && oldEntry.namespace),
    priority: oldEntry && oldEntry.priority !== undefined ? oldEntry.priority : 50,
    status: 'IMPLEMENTED',
    version,
    description,
    dependencies,
    provides,
    // Live scan wins for structural inventory when it found anything
    cpts: cpts.length ? uniqueSorted(cpts) : (oldEntry && oldEntry.cpts) || [],
    taxonomies: taxonomies.length
      ? uniqueSorted(taxonomies)
      : (oldEntry && oldEntry.taxonomies) || [],
    tables: tables.length
      ? uniqueSorted(tables)
      : ((oldEntry && oldEntry.tables) || []).filter(
          (t) => typeof t === 'string' && isLikelyTableName(t)
        ),
    meta: (oldEntry && oldEntry.meta) || {},
    rest: mergeRest((oldEntry && oldEntry.rest) || [], restAudited),
    pages: (oldEntry && oldEntry.pages) || [],
    shortcodes: shortcodes.length
      ? shortcodes.map((s) =>
          typeof s === 'string' ? { tag: s, _source: 'live_scan' } : s
        )
      : (oldEntry && oldEntry.shortcodes) || [],
  };

  const ghosts = restGhosts((oldEntry && oldEntry.rest) || [], restAudited);
  if (ghosts.length) {
    entry._rest_removed_vs_old_registry = ghosts;
  }

  if (ajax.length) entry.ajax = ajax;
  if (cron.length) entry.cron = cron;
  if (hooks.hooks_fired.length) entry.hooks_fired = hooks.hooks_fired;
  if (hooks.hooks_listened.length) entry.hooks_listened = hooks.hooks_listened;

  entry._audit = {
    ...result.audit,
    main_file: fs.existsSync(mainPhp) ? `${slug}.php` : null,
    plugin_header: header,
    version_constants: versionConsts,
    composer: composer
      ? {
          name: composer.name,
          description: composer.description,
          namespaces: composer.namespaces,
          require: composer.require,
        }
      : null,
    php_namespaces_found: namespaces,
    file_counts: fileCounts,
    php_file_count: phpFiles.length,
    php_loc_approx: phpLoc,
    live: {
      cpts,
      taxonomies,
      tables,
      shortcodes,
      rest_routes: restAudited,
      rest_count: restAudited.length,
      ajax,
      cron,
      hooks_fired_count: hooks.hooks_fired.length,
      hooks_listened_count: hooks.hooks_listened.length,
    },
    delta_vs_old: {
      was_in_registry: Boolean(oldEntry),
      old_status: oldEntry ? oldEntry.status : null,
      old_version: oldEntry ? oldEntry.version || null : null,
      version_changed: Boolean(
        oldEntry &&
          oldEntry.version &&
          version &&
          String(oldEntry.version) !== String(version)
      ),
      old_rest_count: oldEntry && oldEntry.rest ? oldEntry.rest.length : 0,
      live_rest_count: restAudited.length,
      old_cpt_count: oldEntry && oldEntry.cpts ? oldEntry.cpts.length : 0,
      live_cpt_count: cpts.length,
      old_table_count: oldEntry && oldEntry.tables ? oldEntry.tables.length : 0,
      live_table_count: tables.length,
      new_plugin: !oldEntry,
    },
  };

  // Preserve pages from old registry but flag audit didn't re-derive pages from rewrite rules
  entry._audit.pages_note =
    'pages[] preserved from previous registry when present; rewrite/page audit not fully automated.';

  const report = {
    slug,
    status: 'AUDITED',
    version,
    namespace,
    php_files: phpFiles.length,
    php_loc_approx: phpLoc,
    rest: restAudited.length,
    cpts,
    taxonomies,
    tables,
    shortcodes,
    ajax_count: ajax.length,
    cron,
    dependencies,
    new_plugin: !oldEntry,
    version_changed: entry._audit.delta_vs_old.version_changed,
    old_version: entry._audit.delta_vs_old.old_version,
  };

  return { entry, report };
}

function recomputeSummary(registry, auditReports) {
  const plugins = registry.plugins || {};
  const statuses = {};
  const cptSet = new Set();
  const taxSet = new Set();
  const tableSet = new Set();
  let rest = 0;
  let pages = 0;
  let shortcodes = 0;

  for (const p of Object.values(plugins)) {
    const st = p.status || 'UNKNOWN';
    statuses[st] = (statuses[st] || 0) + 1;
    for (const c of p.cpts || []) {
      cptSet.add(typeof c === 'string' ? c : c.slug || JSON.stringify(c));
    }
    for (const t of p.taxonomies || []) {
      taxSet.add(typeof t === 'string' ? t : t.slug || JSON.stringify(t));
    }
    for (const t of p.tables || []) {
      const name = typeof t === 'string' ? t : t.name || '';
      if (name) tableSet.add(name);
    }
    rest += (p.rest || []).length;
    pages += (p.pages || []).length;
    shortcodes += (p.shortcodes || []).length;
  }
  const cpts = cptSet.size;
  const tax = taxSet.size;
  const tables = tableSet.size;

  const audited = auditReports.filter((r) => r.status === 'AUDITED').length;
  const missing = auditReports.filter((r) => r.status === 'MISSING_DIR').length;
  const newPlugins = auditReports.filter((r) => r.new_plugin).length;
  const versionChanged = auditReports.filter((r) => r.version_changed).length;

  return {
    total_plugins: Object.keys(plugins).length,
    total_cpts: cpts,
    total_taxonomies: tax,
    total_tables: tables,
    total_rest_endpoints: rest,
    total_pages: pages,
    total_shortcodes: shortcodes,
    status_breakdown: statuses,
    plugins_in_workspace_audited: audited,
    plugins_missing_on_disk: missing,
    plugins_new_vs_old_registry: newPlugins,
    plugins_version_changed: versionChanged,
    target_audit_count: TARGETS.length,
    completion_percentage: `${Math.round(
      (Object.values(plugins).filter((p) => p.status === 'IMPLEMENTED').length /
        Math.max(Object.keys(plugins).length, 1)) *
        100
    )}%`,
    registry_version: registry.$version,
    last_updated: today(),
    last_deep_investigation: today(),
    audit_mode: 'FULL_TECHNICAL_LIVE_SCAN',
    note: 'summary recounts after live audit of 39 target plugins; non-target plugins preserved from source registry.',
  };
}

function recomputeQuickLookup(registry) {
  const cpts = new Set();
  const tax = new Set();
  const tables = new Set();
  const restPrefixes = new Set();

  for (const p of Object.values(registry.plugins || {})) {
    (p.cpts || []).forEach((c) => cpts.add(typeof c === 'string' ? c : c.slug || c));
    (p.taxonomies || []).forEach((t) => {
      if (typeof t === 'string') tax.add(t);
      else if (t && t.slug) tax.add(t.slug);
    });
    (p.tables || []).forEach((t) => tables.add(typeof t === 'string' ? t : t.name || t));
    (p.rest || []).forEach((r) => {
      if (r.endpoint) {
        const seg = String(r.endpoint).split('/').filter(Boolean)[0];
        if (seg) restPrefixes.add(seg);
      }
    });
  }

  const prev = registry.quick_lookup || {};
  return {
    ...prev,
    all_cpt_slugs: [...cpts].sort(),
    all_taxonomy_slugs: [...tax].sort(),
    all_table_names: [...tables].sort(),
    all_rest_prefixes: [...restPrefixes].sort(),
  };
}

function main() {
  if (!fs.existsSync(SRC_REGISTRY)) {
    console.error('Source registry not found:', SRC_REGISTRY);
    process.exit(1);
  }

  const raw = fs.readFileSync(SRC_REGISTRY, 'utf8');
  const registry = JSON.parse(raw);

  // Full clone is already via JSON.parse of source
  const oldPlugins = registry.plugins || {};
  const reports = [];
  const auditedSlugs = [];

  console.log(`Auditing ${TARGETS.length} plugins...`);
  for (const slug of TARGETS) {
    process.stdout.write(`  → ${slug} ... `);
    const { entry, report } = auditPlugin(slug, oldPlugins[slug]);
    registry.plugins[slug] = entry;
    reports.push(report);
    auditedSlugs.push(slug);
    console.log(
      `${report.status} v${report.version || '?'} rest=${report.rest || 0} php=${report.php_files || 0}`
    );
  }

  // Bump identity metadata
  const prevVersion = registry.$version || '6.6.1';
  registry.$version = prevVersion; // keep version scheme; document audit in description
  registry.$generated = today();
  registry.$description = `Apollo Ecosystem — FULL TECHNICAL LIVE AUDIT (${today()}) — ${TARGETS.length} plugins rescanned from filesystem; base registry clone from v${prevVersion}. New/updated plugins: ${reports
    .filter((r) => r.new_plugin)
    .map((r) => r.slug)
    .join(', ') || 'none'}.`;
  registry.$audit = {
    ...(registry.$audit || {}),
    last_full_technical_audit: today(),
    audit_script: 'plugins/_inventory/audit-plugins-to-registry2.js',
    audit_targets: TARGETS.length,
    audit_method:
      'Deep PHP tree scan: plugin headers, composer.json, CPT/taxonomy/REST/tables/shortcodes/ajax/cron/hooks',
    status: 'LIVE_RESYNC',
    note: 'Previous registry treated as outdated; live code is source of truth for audited fields. Rich narrative fields (pages, meta schema docs) preserved when scan cannot derive them.',
  };

  registry.summary = recomputeSummary(registry, reports);
  registry.quick_lookup = recomputeQuickLookup(registry);

  // Attach audit appendix (does not break consumers that ignore unknown keys)
  registry.$live_audit = {
    generated: today(),
    targets: auditedSlugs,
    reports,
    plugins_not_in_target_list: Object.keys(oldPlugins).filter(
      (s) => !TARGETS.includes(s)
    ),
    new_plugins_added: reports.filter((r) => r.new_plugin).map((r) => r.slug),
    version_changes: reports
      .filter((r) => r.version_changed)
      .map((r) => ({ slug: r.slug, from: r.old_version, to: r.version })),
  };

  fs.writeFileSync(OUT_REGISTRY, JSON.stringify(registry, null, 4), 'utf8');
  fs.writeFileSync(
    OUT_REPORT,
    JSON.stringify(
      {
        generated: today(),
        out: OUT_REGISTRY,
        summary: registry.summary,
        reports: reports.sort((a, b) => a.slug.localeCompare(b.slug)),
        new_plugins: registry.$live_audit.new_plugins_added,
        version_changes: registry.$live_audit.version_changes,
        not_in_target: registry.$live_audit.plugins_not_in_target_list,
      },
      null,
      2
    ),
    'utf8'
  );

  console.log('\n=== DONE ===');
  console.log('Wrote:', OUT_REGISTRY);
  console.log('Report:', OUT_REPORT);
  console.log('Summary:', JSON.stringify(registry.summary, null, 2));
  console.log('New plugins:', registry.$live_audit.new_plugins_added);
  console.log('Version changes:', registry.$live_audit.version_changes.length);
}

main();
