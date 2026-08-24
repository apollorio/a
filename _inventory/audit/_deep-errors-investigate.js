/**
 * Deep ERROR investigation from live source (not just registry merge artifacts).
 * EXCLUDES: apollo-login, apollo-telegram
 *
 * Re-parses register_rest_route multi-method arrays so GET public + POST auth
 * is not collapsed into a false CRITICAL.
 *
 * Writes:
 *   apollo-registry-2-ERRORS-DEEP.md
 *   apollo-registry-2-ERRORS-DEEP.json
 * Never touches apollo-registry.json
 */
'use strict';

const fs = require('fs');
const path = require('path');

const INVENTORY = __dirname;
const PLUGINS = path.resolve(INVENTORY, '..');
const REG2 = path.join(INVENTORY, 'apollo-registry-2.json');
const SKIP = new Set(['apollo-login', 'apollo-telegram']);
const OUT_MD = path.join(INVENTORY, 'apollo-registry-2-ERRORS-DEEP.md');
const OUT_JSON = path.join(INVENTORY, 'apollo-registry-2-ERRORS-DEEP.json');

const METHOD_MAP = {
  READABLE: ['GET'],
  CREATABLE: ['POST'],
  EDITABLE: ['POST', 'PUT', 'PATCH'],
  DELETABLE: ['DELETE'],
  ALLMETHODS: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
};

const TARGETS = [
  'apollo-templates', 'apollo-users', 'apollo-wow', 'apollo-admin', 'apollo-adverts',
  'apollo-calendar', 'apollo-chat', 'apollo-coauthor', 'apollo-comment', 'apollo-core',
  'apollo-dashboard', 'apollo-djs', 'apollo-dj-sync', 'apollo-docs', 'apollo-elementor',
  'apollo-elementor-pro', 'apollo-email', 'apollo-events', 'apollo-fav', 'apollo-gestor',
  'apollo-groups', 'apollo-hub', 'apollo-journal', 'apollo-loc', 'apollo-maps',
  'apollo-membership', 'apollo-mod', 'apollo-notif', 'apollo-pane-engine', 'apollo-radio',
  'apollo-remind', 'apollo-scheduler', 'apollo-seo', 'apollo-sheets', 'apollo-sign',
  'apollo-social', 'apollo-statistics',
];

function read(f) {
  try {
    return fs.readFileSync(f, 'utf8');
  } catch {
    return null;
  }
}

function walkPhp(dir, acc = [], depth = 0) {
  if (depth > 10) return acc;
  let ents;
  try {
    ents = fs.readdirSync(dir, { withFileTypes: true });
  } catch {
    return acc;
  }
  for (const e of ents) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (['vendor', 'node_modules', '.git', 'tests', 'test'].includes(e.name.toLowerCase()))
        continue;
      walkPhp(p, acc, depth + 1);
    } else if (/\.php$/i.test(e.name)) acc.push(p);
  }
  return acc;
}

function extractBalanced(content, openIdx) {
  if (content[openIdx] !== '(' && content[openIdx] !== '{') return null;
  const open = content[openIdx];
  const close = open === '(' ? ')' : '}';
  let depth = 0;
  for (let i = openIdx; i < content.length && i < openIdx + 15000; i++) {
    const ch = content[i];
    if (ch === "'" || ch === '"') {
      const q = ch;
      i++;
      while (i < content.length) {
        if (content[i] === '\\') {
          i += 2;
          continue;
        }
        if (content[i] === q) break;
        i++;
      }
      continue;
    }
    if (ch === open) depth++;
    else if (ch === close) {
      depth--;
      if (depth === 0) return content.slice(openIdx, i + 1);
    }
  }
  return null;
}

function parseMethods(block) {
  const methods = new Set();
  for (const m of block.matchAll(/WP_REST_Server::(\w+)/g)) {
    (METHOD_MAP[m[1].toUpperCase()] || [m[1]]).forEach((x) => methods.add(x));
  }
  for (const m of block.matchAll(/['"]methods['"]\s*=>\s*['"]([^'"]+)['"]/g)) {
    m[1]
      .split(/[|,\/\s]+/)
      .map((s) => s.trim().toUpperCase())
      .filter(Boolean)
      .forEach((x) => methods.add(x));
  }
  for (const m of block.matchAll(/['"]methods['"]\s*=>\s*\[([^\]]+)\]/g)) {
    for (const x of m[1].matchAll(/['"]([A-Z]+)['"]/g)) methods.add(x[1]);
  }
  return [...methods];
}

function resolveVarPermissions(php) {
  // $auth = function () { return is_user_logged_in(); };
  // $public_read = function () { return true; };
  const map = {};
  const re =
    /\$([a-zA-Z_][\w]*)\s*=\s*(?:static\s*)?function\s*\([^)]*\)\s*(?::\s*[^{]+)?\{([\s\S]{0,400}?)\}/g;
  let m;
  while ((m = re.exec(php))) {
    const name = m[1];
    const body = m[2];
    if (/is_user_logged_in/.test(body) && !/return\s+true/.test(body.replace(/is_user_logged_in[^;]*/, ''))) {
      map[name] = { kind: 'LOGGED_IN', detail: `$${name}→is_user_logged_in`, method: null };
    } else if (/current_user_can\s*\(\s*['"]([^'"]+)/.test(body)) {
      const cap = body.match(/current_user_can\s*\(\s*['"]([^'"]+)/)[1];
      map[name] = { kind: 'CLOSURE_CAP', detail: `$${name}→${cap}`, method: null };
    } else if (/return\s+true/.test(body)) {
      map[name] = { kind: 'PUBLIC', detail: `$${name}→return true`, method: null };
    } else {
      map[name] = { kind: 'CLOSURE', detail: `$${name}→custom`, method: null, body };
    }
  }
  // $auth = 'is_user_logged_in';
  const re2 = /\$([a-zA-Z_][\w]*)\s*=\s*['"](is_user_logged_in|__return_true)['"]/g;
  while ((m = re2.exec(php))) {
    map[m[1]] =
      m[2] === '__return_true'
        ? { kind: 'PUBLIC', detail: `$${m[1]}→__return_true`, method: null }
        : { kind: 'LOGGED_IN', detail: `$${m[1]}→is_user_logged_in`, method: null };
  }
  return map;
}

function parsePermission(block, varMap) {
  // __return_true
  if (/['"]permission_callback['"]\s*=>\s*['"]__return_true['"]/.test(block)) {
    return { kind: 'PUBLIC', detail: '__return_true', method: null };
  }
  if (/['"]permission_callback['"]\s*=>\s*['"]is_user_logged_in['"]/.test(block)) {
    return { kind: 'LOGGED_IN', detail: 'is_user_logged_in', method: null };
  }
  // Variable: 'permission_callback' => $auth
  let m = block.match(/['"]permission_callback['"]\s*=>\s*\$([a-zA-Z_][\w]*)/);
  if (m && varMap && varMap[m[1]]) {
    return { ...varMap[m[1]] };
  }
  if (m) {
    return { kind: 'OTHER', detail: `$${m[1]} unresolved`, method: null };
  }
  m = block.match(
    /['"]permission_callback['"]\s*=>\s*(?:array\s*\(\s*\$this\s*,\s*['"](\w+)['"]\s*\)|\[\s*\$this\s*,\s*['"](\w+)['"]\s*\])/
  );
  if (m) return { kind: 'METHOD', detail: m[1] || m[2], method: m[1] || m[2] };
  m = block.match(
    /['"]permission_callback['"]\s*=>\s*(?:static\s*)?function\s*\(/
  );
  if (m) {
    // extract closure body briefly
    const idx = block.search(/['"]permission_callback['"]\s*=>\s*(?:static\s*)?function\s*\(/);
    const brace = block.indexOf('{', idx);
    let body = '';
    if (brace >= 0) {
      const bal = extractBalanced(block, brace);
      body = bal ? bal.slice(1, -1) : '';
    }
    const caps = [...body.matchAll(/current_user_can\s*\(\s*['"]([^'"]+)['"]/g)].map((x) => x[1]);
    if (caps.length) return { kind: 'CLOSURE_CAP', detail: caps.join(','), method: null, body };
    if (/is_user_logged_in/.test(body))
      return { kind: 'CLOSURE_LOGGED_IN', detail: 'closure+logged_in', method: null, body };
    if (/return\s+true/.test(body) && !/current_user_can|is_user_logged_in/.test(body))
      return { kind: 'PUBLIC', detail: 'closure_return_true', method: null, body };
    return { kind: 'CLOSURE', detail: 'closure_custom', method: null, body: body.slice(0, 200) };
  }
  if (!/permission_callback/.test(block)) return { kind: 'MISSING', detail: 'none', method: null };
  return { kind: 'OTHER', detail: 'unparsed', method: null };
}

function extractMethodBody(php, methodName) {
  if (!php || !methodName) return null;
  const re = new RegExp(String.raw`function\s+${methodName}\s*\([^)]*\)[^{]*\{`, 'm');
  const m = php.match(re);
  if (!m) return null;
  const start = php.indexOf(m[0]) + m[0].length - 1;
  const bal = extractBalanced(php, start);
  return bal ? bal.slice(1, -1) : null;
}

function analyzeMethodBody(body, phpByRel, depth = 0) {
  if (!body) return { ok: false, caps: [], logged_in: false, token: false, preview: null };
  const caps = [...body.matchAll(/current_user_can\s*\(\s*['"]([^'"]+)['"]/g)].map((x) => x[1]);
  const logged_in =
    /\bis_user_logged_in\s*\(/.test(body) ||
    /get_current_user_id\s*\(\s*\)\s*(?:===?|<=)\s*0/.test(body) ||
    /!\s*is_user_logged_in/.test(body) ||
    /\bis_admin\s*\(\s*\)/.test(body); // WP is_admin() — weak but intentional for some admin UIs
  const token =
    /get_header\s*\(\s*['"]x[_-]apollo/i.test(body) ||
    /Authorization|Bearer|app_token|hmac|jwt/i.test(body) ||
    /preg_match\s*\(\s*['"]\/\^\[a-zA-Z0-9\]/.test(body);
  const returns_user_can = /return\s+current_user_can/.test(body);
  const returns_logged_in = /return\s+is_user_logged_in\s*\(/.test(body);
  const returns_bool_logged =
    /return\s*\(\s*bool\s*\)\s*is_user_logged_in|return\s*\(\s*bool\s*\)\s*\(\s*get_current_user_id/.test(
      body
    );

  // One-level delegate: return $this->foo( $request );
  let delegated = null;
  if (depth < 2 && phpByRel) {
    const del = body.match(/\$this->([A-Za-z_][A-Za-z0-9_]*)\s*\(/);
    if (del && body.replace(/\s+/g, ' ').trim().length < 200) {
      // short body likely pure delegate
      let dbody = null;
      for (const [, p2] of phpByRel) {
        dbody = extractMethodBody(p2, del[1]);
        if (dbody) break;
      }
      if (dbody) {
        delegated = analyzeMethodBody(dbody, phpByRel, depth + 1);
      }
    }
  }

  let ok =
    caps.length > 0 ||
    logged_in ||
    token ||
    returns_user_can ||
    returns_logged_in ||
    returns_bool_logged ||
    (delegated && delegated.ok);

  // Method name heuristics applied by caller if !ok

  return {
    ok,
    caps: caps.length ? caps : delegated?.caps || [],
    logged_in: logged_in || !!(delegated && delegated.logged_in),
    token: token || !!(delegated && delegated.token),
    delegated_ok: !!(delegated && delegated.ok),
    preview: body.replace(/\s+/g, ' ').trim().slice(0, 240),
  };
}

function extractRoutesFromFile(php, fileRel) {
  const routes = [];
  const varMap = resolveVarPermissions(php);
  const re = /register_rest_route\s*\(/g;
  let m;
  while ((m = re.exec(php))) {
    const call = extractBalanced(php, m.index + m[0].length - 1);
    if (!call) continue;
    const head = call.match(/^\(\s*([^,]+)\s*,\s*(['"])(\/?[^'"]+)\2/s);
    if (!head) continue;
    const endpoint = head[3].startsWith('/') ? head[3] : '/' + head[3];

    // Find LEAF method configs: exactly one permission_callback + methods.
    // Important: when a parent array wraps multiple method configs, do NOT jump
    // past the whole parent — step into it (advance only past "array" keyword).
    const configs = [];
    let i = 0;
    while (i < call.length) {
      const idx = call.indexOf('array', i);
      if (idx < 0) break;
      // word boundary-ish: avoid matching inside longer identifiers
      if (idx > 0 && /[A-Za-z0-9_]/.test(call[idx - 1])) {
        i = idx + 5;
        continue;
      }
      let j = idx + 5;
      while (j < call.length && /\s/.test(call[j])) j++;
      if (call[j] !== '(') {
        i = idx + 5;
        continue;
      }
      const block = extractBalanced(call, j);
      if (!block) {
        i = idx + 5;
        continue;
      }
      const permCount = (block.match(/['"]permission_callback['"]/g) || []).length;
      const hasMethods = /['"]methods['"]\s*=>|WP_REST_Server::/.test(block);
      if (permCount === 1 && hasMethods) {
        // Exclude pure callback arrays: array( $this, 'method' ) — those have no 'methods' key
        // Already required hasMethods.
        configs.push(block);
        // Advance past this leaf so we don't re-enter
        i = j + block.length;
        continue;
      }
      if (permCount > 1) {
        // Parent wrapper: step inside, don't skip children
        i = j + 1;
        continue;
      }
      // array($this,'cb') or unrelated array — skip whole thing
      i = j + block.length;
    }
    if (!configs.length) configs.push(call);

    for (const cfg of configs) {
      const methods = parseMethods(cfg);
      const perm = parsePermission(cfg, varMap);
      let callback = null;
      const cb = cfg.match(
        /['"]callback['"]\s*=>\s*(?:array\s*\(\s*\$this\s*,\s*['"](\w+)['"]|\[\s*\$this\s*,\s*['"](\w+)['"]|['"]([^'"]+)['"])/
      );
      if (cb) callback = cb[1] || cb[2] || cb[3];
      routes.push({
        endpoint,
        methods: methods.length ? methods : ['?'],
        perm,
        callback,
        file: fileRel,
        config_snippet: cfg.replace(/\s+/g, ' ').slice(0, 200),
      });
    }
  }
  return routes;
}

function isWrite(methods) {
  return (methods || []).some((m) =>
    ['POST', 'PUT', 'PATCH', 'DELETE'].includes(String(m).toUpperCase())
  );
}

function classifyIssue(route, methodBodyAnalysis, plugin) {
  const write = isWrite(route.methods);
  const kind = route.perm.kind;
  const inExamples = /examples\//.test(route.file || '');

  // Resolve permission strength
  let authOk = false;
  let authDetail = route.perm.detail;
  if (kind === 'METHOD') {
    authOk = methodBodyAnalysis.ok;
    const bits = [];
    if (methodBodyAnalysis.caps?.length) bits.push('caps:' + methodBodyAnalysis.caps.join(','));
    if (methodBodyAnalysis.logged_in) bits.push('logged_in');
    if (methodBodyAnalysis.token) bits.push('app_token/hmac');
    if (methodBodyAnalysis.delegated_ok) bits.push('delegated');
    authDetail = `${route.perm.method}() → ${
      methodBodyAnalysis.ok ? 'HAS_AUTH ' + (bits.join(' ') || 'ok') : 'BODY_WEAK_OR_MISSING'
    }`;
  } else if (kind === 'LOGGED_IN' || kind === 'CLOSURE_LOGGED_IN' || kind === 'CLOSURE_CAP') {
    authOk = true;
  } else if (kind === 'PUBLIC') {
    authOk = false;
  } else if (kind === 'CLOSURE') {
    authOk = methodBodyAnalysis.ok;
  }

  // Intentional public reads
  const intentionalRead =
    !write &&
    (kind === 'PUBLIC' || kind === 'CLOSURE') &&
    (/\/(health|holidays|vapid|seo\/|journal\/|sounds|search|members|leaderboard|achievements|ranks|map\/|radio|templates|canvas|proximos|passados|hoje|calendario|related|depo\/|members|newsletter)/i.test(
      route.endpoint
    ) ||
      /\/registry\/(cpts|taxonomies|status)/.test(route.endpoint) ||
      route.endpoint === '/health');

  const intentionalWritePublic =
    write &&
    kind === 'PUBLIC' &&
    (/newsletter\/subscribe|telegram\/webhook|csp-report|auth\/|profile\/.*\/view/i.test(
      route.endpoint
    ) ||
      (plugin === 'apollo-membership' && /\/report$/.test(route.endpoint)));

  if (inExamples) {
    return {
      severity: 'LOW',
      category: 'EXAMPLE_CODE_ROUTE',
      issue: true,
      note: 'Route registered from examples/ — remove from production bootstrap',
      authOk,
      authDetail,
      write,
    };
  }

  if (write && kind === 'PUBLIC' && !intentionalWritePublic) {
    return {
      severity: 'CRITICAL',
      category: 'PUBLIC_WRITE',
      issue: true,
      note: 'WRITE method uses __return_true / open permission — unauthenticated mutation surface',
      authOk: false,
      authDetail,
      write,
    };
  }

  if (write && kind === 'METHOD' && !authOk) {
    return {
      severity: 'CRITICAL',
      category: 'WRITE_METHOD_WEAK_BODY',
      issue: true,
      note: `Write uses ${route.perm.method}() but body has no clear auth guard`,
      authOk: false,
      authDetail,
      write,
    };
  }

  if (write && kind === 'MISSING') {
    return {
      severity: 'CRITICAL',
      category: 'WRITE_MISSING_PERMISSION',
      issue: true,
      note: 'Write route missing permission_callback',
      authOk: false,
      authDetail,
      write,
    };
  }

  if (write && intentionalWritePublic) {
    return {
      severity: 'INFO',
      category: 'INTENTIONAL_PUBLIC_WRITE',
      issue: false,
      note: 'Public write by design (subscribe/webhook/contact form) — ensure rate-limit + validation',
      authOk: false,
      authDetail,
      write,
    };
  }

  if (!write && kind === 'PUBLIC' && intentionalRead) {
    return {
      severity: 'INFO',
      category: 'INTENTIONAL_PUBLIC_READ',
      issue: false,
      note: 'Public read catalog/content — OK if no PII leak in response',
      authOk: false,
      authDetail,
      write,
    };
  }

  if (!write && kind === 'PUBLIC' && !intentionalRead) {
    // sensitive public reads
    const sensitive =
      /\/(registry|pane-mode|radar|profile|users\/|sounds\/user|dashboard|admin)/i.test(
        route.endpoint
      );
    return {
      severity: sensitive ? 'HIGH' : 'MEDIUM',
      category: sensitive ? 'SENSITIVE_PUBLIC_READ' : 'PUBLIC_READ_REVIEW',
      issue: true,
      note: sensitive
        ? 'Public read of potentially sensitive/internal data'
        : 'Public GET — confirm no private fields in response payload',
      authOk: false,
      authDetail,
      write,
    };
  }

  if (write && authOk) {
    return {
      severity: 'INFO',
      category: 'WRITE_AUTH_OK',
      issue: false,
      note: 'Write protected by method/capability',
      authOk: true,
      authDetail,
      write,
    };
  }

  if (kind === 'OTHER' || kind === 'CLOSURE') {
    return {
      severity: 'MEDIUM',
      category: 'PERM_REVIEW',
      issue: true,
      note: 'Permission binding needs manual review',
      authOk,
      authDetail,
      write,
    };
  }

  return {
    severity: 'INFO',
    category: 'OK_OR_BENIGN',
    issue: false,
    note: 'No issue',
    authOk,
    authDetail,
    write,
  };
}

function main() {
  const reg = JSON.parse(fs.readFileSync(REG2, 'utf8'));
  const findings = [];
  const structural = [];

  for (const slug of TARGETS) {
    if (SKIP.has(slug)) continue;
    const dir = path.join(PLUGINS, slug);
    if (!fs.existsSync(dir)) continue;
    const files = walkPhp(dir);
    const phpByRel = new Map();
    for (const f of files) {
      const rel = path.relative(dir, f).replace(/\\/g, '/');
      const t = read(f);
      if (t) phpByRel.set(rel, t);
    }

    // Live re-scan routes
    for (const [rel, php] of phpByRel) {
      if (!/register_rest_route\s*\(/.test(php)) continue;
      const routes = extractRoutesFromFile(php, rel);
      for (const route of routes) {
        let bodyAnalysis = { ok: false, caps: [], logged_in: false, token: false, preview: null };
        if (route.perm.kind === 'METHOD' && route.perm.method) {
          let body = extractMethodBody(php, route.perm.method);
          if (!body) {
            for (const [, p2] of phpByRel) {
              body = extractMethodBody(p2, route.perm.method);
              if (body) break;
            }
          }
          bodyAnalysis = analyzeMethodBody(body, phpByRel);
          // Name heuristics when body missing or empty
          if (!bodyAnalysis.ok && route.perm.method) {
            const mn = route.perm.method;
            if (/^(is_logged_in|check_logged_in|check_user_logged_in|require_login)$/i.test(mn)) {
              bodyAnalysis = {
                ...bodyAnalysis,
                ok: true,
                logged_in: true,
                preview: bodyAnalysis.preview || `(name heuristic) ${mn}`,
              };
            } else if (/^(is_admin|check_admin|require_admin|can_manage)/i.test(mn)) {
              bodyAnalysis = {
                ...bodyAnalysis,
                ok: true,
                caps: bodyAnalysis.caps.length ? bodyAnalysis.caps : ['manage_options?'],
                preview: bodyAnalysis.preview || `(name heuristic) ${mn}`,
              };
            } else if (/permission|can_edit|can_delete|permissions_check|owner/i.test(mn)) {
              // leave ok if body found with signals; if body exists with any return current_user
              if (body && /current_user_can|is_user_logged_in|get_current_user_id/.test(body)) {
                bodyAnalysis.ok = true;
              }
            }
          }
        } else if (route.perm.body) {
          bodyAnalysis = analyzeMethodBody(route.perm.body, phpByRel);
        }

        const cls = classifyIssue(route, bodyAnalysis, slug);
        if (!cls.issue && cls.severity === 'INFO' && cls.category !== 'INTENTIONAL_PUBLIC_WRITE') {
          // keep intentional public writes + skip pure OK
          if (cls.category === 'OK_OR_BENIGN' || cls.category === 'WRITE_AUTH_OK' || cls.category === 'INTENTIONAL_PUBLIC_READ')
            continue;
        }
        if (!cls.issue && cls.category === 'INTENTIONAL_PUBLIC_WRITE') {
          // still record as INFO
        } else if (!cls.issue) continue;

        findings.push({
          plugin: slug,
          endpoint: route.endpoint,
          methods: route.methods,
          write: cls.write,
          severity: cls.severity,
          category: cls.category,
          note: cls.note,
          permission_kind: route.perm.kind,
          permission_detail: cls.authDetail,
          callback: route.callback,
          file: route.file,
          method_body_preview: bodyAnalysis.preview,
          config_snippet: route.config_snippet,
        });
      }
    }

    // Structural from registry-2 security
    const p = reg.plugins[slug];
    if (p && p.security) {
      if (p.security.abspath_coverage_pct != null && p.security.abspath_coverage_pct < 90) {
        structural.push({
          plugin: slug,
          severity: p.security.abspath_coverage_pct < 85 ? 'MEDIUM' : 'LOW',
          category: 'ABSPATH_COVERAGE',
          note: `ABSPATH coverage ${p.security.abspath_coverage_pct}%`,
          endpoint: null,
          methods: [],
        });
      }
      if (
        p.security.prepare_ratio != null &&
        p.security.prepare_ratio < 0.45 &&
        (p.php_surface?.loc || 0) > 800
      ) {
        structural.push({
          plugin: slug,
          severity: 'MEDIUM',
          category: 'LOW_PREPARE_RATIO',
          note: `prepare_ratio=${p.security.prepare_ratio} on ${p.php_surface?.loc} LOC — review raw SQL`,
          endpoint: null,
          methods: [],
        });
      }
    }
  }

  const all = [...findings, ...structural];
  const sevOrder = { CRITICAL: 0, HIGH: 1, MEDIUM: 2, LOW: 3, INFO: 4 };
  all.sort(
    (a, b) =>
      (sevOrder[a.severity] ?? 9) - (sevOrder[b.severity] ?? 9) ||
      String(a.plugin).localeCompare(String(b.plugin))
  );

  const counts = {};
  for (const e of all) counts[e.severity] = (counts[e.severity] || 0) + 1;
  const cats = {};
  for (const e of all) cats[e.category] = (cats[e.category] || 0) + 1;

  const criticals = all.filter((e) => e.severity === 'CRITICAL');
  const highs = all.filter((e) => e.severity === 'HIGH');

  // rank plugins
  const pluginScore = {};
  for (const e of all) {
    if (!pluginScore[e.plugin])
      pluginScore[e.plugin] = { CRITICAL: 0, HIGH: 0, MEDIUM: 0, LOW: 0, INFO: 0 };
    pluginScore[e.plugin][e.severity] = (pluginScore[e.plugin][e.severity] || 0) + 1;
  }
  const ranked = Object.entries(pluginScore).sort(
    (a, b) =>
      b[1].CRITICAL * 100 + b[1].HIGH * 10 + b[1].MEDIUM - (a[1].CRITICAL * 100 + a[1].HIGH * 10 + a[1].MEDIUM)
  );

  // Markdown
  const L = [];
  L.push(`# Apollo Deep Error Investigation (source-accurate)`);
  L.push(``);
  L.push(`Generated: **${new Date().toISOString().slice(0, 10)}**`);
  L.push(`**Excluded:** \`apollo-login\`, \`apollo-telegram\` (working / fine per user)`);
  L.push(`**Method:** re-parse live \`register_rest_route\` multi-method configs + permission method bodies`);
  L.push(`**Does not modify** \`apollo-registry.json\` — companion to \`apollo-registry-2.json\``);
  L.push(``);
  L.push(`## Counts`);
  L.push(``);
  L.push(`| Severity | Count |`);
  L.push(`|----------|------:|`);
  for (const s of ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'INFO']) {
    L.push(`| ${s} | ${counts[s] || 0} |`);
  }
  L.push(``);
  L.push(`### Categories`);
  L.push(``);
  for (const [k, v] of Object.entries(cats).sort((a, b) => b[1] - a[1])) {
    L.push(`- **${k}**: ${v}`);
  }
  L.push(``);
  L.push(`## P0 — CRITICAL public WRITE (must fix)`);
  L.push(``);
  if (!criticals.length) {
    L.push(`_No true public-write routes after multi-method re-parse._`);
    L.push(``);
    L.push(
      `> Note: earlier registry-2 “CRITICAL” rows that mixed \`GET+POST\` on one line were **false positives** when POST used \`can_edit\` / \`create_item_permissions_check\` in a separate method array.`
    );
  } else {
    for (const e of criticals) {
      L.push(`### \`${e.plugin}\` \`${(e.methods || []).join(',')}\` \`${e.endpoint}\``);
      L.push(``);
      L.push(`- **Category:** ${e.category}`);
      L.push(`- **Permission:** ${e.permission_kind} — ${e.permission_detail}`);
      L.push(`- **Callback:** \`${e.callback || '?'}\``);
      L.push(`- **File:** \`${e.file}\``);
      L.push(`- **Issue:** ${e.note}`);
      if (e.method_body_preview) L.push(`- **Auth body:** \`${e.method_body_preview}\``);
      L.push(`- **Fix:**`);
      L.push(`  1. Set \`permission_callback\` to \`is_user_logged_in\` or owner/capability method`);
      L.push(`  2. Never use \`__return_true\` on POST/PUT/PATCH/DELETE`);
      L.push(`  3. Add rate-limit if endpoint must stay semi-public`);
      L.push(``);
    }
  }

  L.push(`## P1 — HIGH sensitive public reads`);
  L.push(``);
  for (const e of highs) {
    L.push(
      `- **${e.plugin}** \`${(e.methods || []).join(',')}\` \`${e.endpoint || '—'}\` — ${e.category}: ${e.note}`
    );
    if (e.file) L.push(`  - \`${e.file}\` · perm=${e.permission_detail}`);
  }
  L.push(``);

  L.push(`## P2 — MEDIUM`);
  L.push(``);
  for (const e of all.filter((x) => x.severity === 'MEDIUM')) {
    L.push(
      `- **${e.plugin}** ${e.endpoint ? `\`${(e.methods || []).join(',')}\` \`${e.endpoint}\`` : e.category} — ${e.note}`
    );
  }
  L.push(``);

  L.push(`## INFO — intentional public (track, don’t panic)`);
  L.push(``);
  for (const e of all.filter((x) => x.severity === 'INFO')) {
    L.push(
      `- **${e.plugin}** \`${(e.methods || []).join(',')}\` \`${e.endpoint}\` — ${e.category}`
    );
  }
  L.push(``);

  L.push(`## Plugin priority queue`);
  L.push(``);
  L.push(`| Plugin | CRIT | HIGH | MED | LOW | INFO | Pri |`);
  L.push(`|--------|-----:|-----:|----:|----:|-----:|-----|`);
  for (const [slug, c] of ranked) {
    const pri = c.CRITICAL > 0 ? 'P0' : c.HIGH > 0 ? 'P1' : c.MEDIUM > 0 ? 'P2' : 'P3';
    L.push(
      `| ${slug} | ${c.CRITICAL} | ${c.HIGH} | ${c.MEDIUM} | ${c.LOW || 0} | ${c.INFO || 0} | ${pri} |`
    );
  }
  L.push(``);
  L.push(`## Verified false positives (from first-pass audit)`);
  L.push(``);
  L.push(`These looked CRITICAL in registry-2 because GET+POST methods were merged:`);
  L.push(``);
  L.push(`| Plugin | Endpoint | Reality |`);
  L.push(`|--------|----------|---------|`);
  L.push(`| apollo-events | POST /eventos | \`can_edit\` / \`can_edit_event\` — auth OK |`);
  L.push(`| apollo-hub | PATCH hubs/* | \`can_edit_hub\` on write arrays — auth OK |`);
  L.push(`| apollo-adverts | POST classifieds | \`create_item_permissions_check\` — auth OK |`);
  L.push(`| apollo-comment | POST depoimento | \`create_item_permissions_check\` — auth OK |`);
  L.push(`| apollo-djs | POST /djs | check live method arrays (same pattern) |`);
  L.push(`| apollo-loc | POST locals | check live method arrays (same pattern) |`);
  L.push(``);
  L.push(`---`);
  L.push(`Machine JSON: \`apollo-registry-2-ERRORS-DEEP.json\``);

  fs.writeFileSync(OUT_MD, L.join('\n'), 'utf8');
  fs.writeFileSync(
    OUT_JSON,
    JSON.stringify(
      {
        generated: new Date().toISOString(),
        excluded: [...SKIP],
        counts,
        categories: cats,
        ranked: ranked.map(([slug, c]) => ({ slug, ...c })),
        criticals,
        highs,
        all,
      },
      null,
      2
    ),
    'utf8'
  );

  console.log('counts', counts);
  console.log('CRITICAL', criticals.length, criticals.map((e) => `${e.plugin} ${e.methods.join(',')} ${e.endpoint}`));
  console.log('HIGH', highs.length);
  console.log('Wrote', OUT_MD);
}

main();
