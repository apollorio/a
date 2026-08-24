/**
 * Apollo DEEP Technical Audit → apollo-registry-2.json
 *
 * Not a header/REST-count skim. Full PHP tree static analysis:
 *  - REST routes + methods + permission_callback risk class
 *  - CREATE TABLE / dbDelta schemas (columns, keys)
 *  - Meta keys (register_* + config arrays + get_/update_ meta)
 *  - Rewrite rules, query vars, virtual pages
 *  - CPT / taxonomy registration + constants
 *  - Classes / interfaces / traits (FQCN)
 *  - Public function surface (includes/)
 *  - Hooks fired & listened (apollo/* + critical WP)
 *  - AJAX (priv / nopriv), cron, shortcodes
 *  - Admin menus, settings, options keys
 *  - Enqueued script/style handles
 *  - Security posture (ABSPATH, prepare, nonce, caps, raw superglobals, __return_true)
 *  - Cross-plugin coupling graph
 *  - Activation / deactivation / uninstall signals
 *  - File inventory + LOC by directory
 *
 * Usage: node deep-audit-registry2.js
 */
'use strict';

const fs = require('fs');
const path = require('path');

const INVENTORY_DIR = __dirname;
const PLUGINS_ROOT = path.resolve(INVENTORY_DIR, '..');
const SRC_REGISTRY = path.join(INVENTORY_DIR, 'apollo-registry.json');
// Canonical monolith SSOT (user-requested full refresh after P1/P2/P3 remediations)
const OUT_REGISTRY = path.join(INVENTORY_DIR, 'apollo-registry.json');
// Mirror copy for diff/history
const OUT_REGISTRY_MIRROR = path.join(INVENTORY_DIR, 'apollo-registry-2.json');
const OUT_REPORT = path.join(INVENTORY_DIR, 'apollo-registry-deep-report.json');
const OUT_MD = path.join(INVENTORY_DIR, 'apollo-registry-DEEP-AUDIT.md');

const TARGETS = [
  'apollo-templates', 'apollo-users', 'apollo-wow', 'apollo-admin', 'apollo-adverts',
  'apollo-calendar', 'apollo-chat', 'apollo-coauthor', 'apollo-comment', 'apollo-core',
  'apollo-dashboard', 'apollo-djs', 'apollo-dj-sync', 'apollo-docs', 'apollo-elementor',
  'apollo-elementor-pro', 'apollo-email', 'apollo-events', 'apollo-fav', 'apollo-gestor',
  'apollo-groups', 'apollo-hub', 'apollo-journal', 'apollo-loc', 'apollo-login',
  'apollo-maps', 'apollo-membership', 'apollo-mod', 'apollo-notif', 'apollo-pane-engine',
  'apollo-radio', 'apollo-remind', 'apollo-scheduler', 'apollo-seo', 'apollo-sheets',
  'apollo-sign', 'apollo-social', 'apollo-statistics', 'apollo-telegram',
];

const SKIP_DIRS = new Set([
  'vendor', 'node_modules', '.git', '.svn', 'dist', 'build', 'coverage',
  'tests', 'test', '__tests__', '.github', 'cache', 'tmp',
  'examples', // P3: example code not production SSOT
  'phpcs-logs',
]);

const REST_METHOD_MAP = {
  READABLE: ['GET'],
  CREATABLE: ['POST'],
  EDITABLE: ['POST', 'PUT', 'PATCH'],
  DELETABLE: ['DELETE'],
  ALLMETHODS: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
};

// ─── FS helpers ─────────────────────────────────────────────────────────────

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

function walkFiles(dir, pred, acc = [], depth = 0) {
  if (depth > 12) return acc;
  let ents;
  try {
    ents = fs.readdirSync(dir, { withFileTypes: true });
  } catch {
    return acc;
  }
  for (const e of ents) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (SKIP_DIRS.has(e.name.toLowerCase())) continue;
      walkFiles(p, pred, acc, depth + 1);
    } else if (pred(e.name, p)) {
      acc.push(p);
    }
  }
  return acc;
}

function walkPhp(dir) {
  return walkFiles(dir, (n) => /\.php$/i.test(n));
}

function uniqueSorted(arr) {
  return [...new Set((arr || []).filter((x) => x != null && x !== ''))].sort();
}

function rel(from, file) {
  return path.relative(from, file).replace(/\\/g, '/');
}

// ─── Header / composer ──────────────────────────────────────────────────────

function parsePluginHeader(content) {
  if (!content) return {};
  const head = content.slice(0, 14000);
  const get = (label) => {
    const re = new RegExp(`^[ \\t]*\\*?[ \\t]*${label}[ \\t]*:[ \\t]*(.+)$`, 'im');
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

function parseComposer(dir) {
  const raw = readText(path.join(dir, 'composer.json'));
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

// ─── REST deep ──────────────────────────────────────────────────────────────

function parseMethodsNear(slice) {
  const methods = [];
  for (const m of slice.matchAll(/WP_REST_Server::(\w+)/g)) {
    const mapped = REST_METHOD_MAP[m[1].toUpperCase()];
    if (mapped) methods.push(...mapped);
    else methods.push(m[1]);
  }
  for (const m of slice.matchAll(/['"]methods['"]\s*=>\s*['"]([^'"]+)['"]/gi)) {
    methods.push(
      ...m[1]
        .split(/[|,\/\s]+/)
        .map((s) => s.trim().toUpperCase())
        .filter((s) => /^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)$/.test(s))
    );
  }
  for (const m of slice.matchAll(/['"]methods['"]\s*=>\s*\[([^\]]+)\]/gi)) {
    for (const x of m[1].matchAll(/['"]([A-Z]+)['"]/g)) methods.push(x[1]);
  }
  return uniqueSorted(methods);
}

/**
 * Index all PHP methods in a plugin: name -> [{ className, file, body, signature }]
 */
function indexPhpMethods(contentsByFile) {
  const byName = new Map(); // method -> entries[]
  for (const [file, content] of contentsByFile) {
    const fileRel = file; // caller may pass rel path as key
    // track class context roughly
    let currentClass = null;
    const lines = content.split(/\r?\n/);
    for (let i = 0; i < lines.length; i++) {
      const classM = lines[i].match(
        /^\s*(?:abstract\s+|final\s+)?class\s+([A-Za-z_][A-Za-z0-9_]*)/
      );
      if (classM) currentClass = classM[1];
      const fnM = lines[i].match(
        /^\s*(?:public|private|protected|static|final|abstract|\s)*function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\(([^)]*)\)/
      );
      if (!fnM) continue;
      const name = fnM[1];
      // extract body by brace matching from this line
      let startIdx = content.indexOf(lines[i]);
      // find opening brace after function
      let braceStart = content.indexOf('{', startIdx);
      // skip abstract / interface without body
      const headerSlice = content.slice(startIdx, startIdx + 200);
      if (/;\s*$/.test(headerSlice.split('\n')[0]) && !headerSlice.includes('{')) continue;
      if (braceStart < 0 || braceStart - startIdx > 300) continue;
      let depth = 0;
      let end = braceStart;
      for (let j = braceStart; j < content.length && j < braceStart + 8000; j++) {
        const ch = content[j];
        if (ch === '{') depth++;
        else if (ch === '}') {
          depth--;
          if (depth === 0) {
            end = j;
            break;
          }
        }
      }
      const body = content.slice(braceStart + 1, end);
      const entry = {
        name,
        className: currentClass,
        file: fileRel,
        signature: fnM[2].replace(/\s+/g, ' ').trim().slice(0, 120),
        body,
        body_preview: body.replace(/\s+/g, ' ').trim().slice(0, 280),
      };
      if (!byName.has(name)) byName.set(name, []);
      byName.get(name).push(entry);
    }
  }
  return byName;
}

/**
 * Deep analysis of a permission method / closure body.
 */
function analyzePermissionBody(body, meta = {}) {
  const b = body || '';
  const compact = b.replace(/\s+/g, ' ').trim();
  const signals = {
    current_user_can: [],
    is_user_logged_in: /\bis_user_logged_in\s*\(/.test(b),
    is_super_admin: /\bis_super_admin\s*\(/.test(b),
    get_current_user_id: /\bget_current_user_id\s*\(/.test(b),
    wp_verify_nonce: /\bwp_verify_nonce\s*\(/.test(b),
    check_ajax_referer: /\bcheck_ajax_referer\s*\(/.test(b),
    rest_cookie_check:
      /\bwp_validate_auth_cookie\b|\brest_cookie_check_errors\b|\bdetermine_current_user\b/.test(b),
    return_true_literal: /return\s+true\s*;/.test(b),
    return_false_literal: /return\s+false\s*;/.test(b),
    return_true_only:
      /^\s*return\s+true\s*;\s*$/.test(compact) ||
      /^return\s+true\s*;?$/.test(compact),
    return_expression: null,
    calls_other_methods: [],
    user_meta_checks: /get_user_meta\s*\(/.test(b),
    ownership_compare:
      /get_current_user_id\s*\([^\)]*\)\s*(?:===?|!==?)\s*\$|\$\w+\s*(?:===?|!==?)\s*get_current_user_id/.test(
        b
      ) ||
      /\$request->get_param\s*\(\s*['"]user_id['"]/.test(b),
    empty_body: compact.length < 3,
  };

  for (const m of b.matchAll(/current_user_can\s*\(\s*['"]([^'"]+)['"]/g)) {
    signals.current_user_can.push(m[1]);
  }
  // current_user_can( $var ) or with variables
  if (/current_user_can\s*\(/.test(b) && !signals.current_user_can.length) {
    signals.current_user_can.push('(dynamic)');
  }

  for (const m of b.matchAll(/\$this->([A-Za-z_][A-Za-z0-9_]*)\s*\(/g)) {
    signals.calls_other_methods.push(m[1]);
  }
  // return statements
  const returns = [...b.matchAll(/return\s+([^;]+);/g)].map((x) =>
    x[1].replace(/\s+/g, ' ').trim().slice(0, 100)
  );
  signals.return_expression = returns[returns.length - 1] || null;

  // Classification
  let effective = 'UNKNOWN';
  let risk = 'REVIEW';
  let summary = '';
  const caps = uniqueSorted(signals.current_user_can);

  if (signals.empty_body) {
    effective = 'EMPTY_BODY';
    risk = 'CRITICAL';
    summary = 'Permission method body empty or unreadable';
  } else if (signals.return_true_only && !signals.current_user_can.length && !signals.is_user_logged_in) {
    effective = 'RETURNS_TRUE_UNCONDITIONALLY';
    risk = 'HIGH';
    summary = 'Body is only `return true` — no auth check';
  } else if (
    signals.return_true_literal &&
    !caps.length &&
    !signals.is_user_logged_in &&
    !signals.ownership_compare &&
    returns.length === 1
  ) {
    effective = 'RETURNS_TRUE_UNCONDITIONALLY';
    risk = 'HIGH';
    summary = 'Unconditional return true';
  } else if (caps.includes('manage_options') || caps.includes('manage_network')) {
    effective = 'ADMIN_CAPABILITY';
    risk = 'LOW';
    summary = `Requires capability: ${caps.join(', ')}`;
  } else if (caps.length && caps.some((c) => c !== '(dynamic)')) {
    effective = 'CAPABILITY_CHECK';
    risk = 'LOW';
    summary = `Requires capability: ${caps.join(', ')}`;
  } else if (caps.includes('(dynamic)')) {
    effective = 'DYNAMIC_CAPABILITY';
    risk = 'REVIEW';
    summary = 'current_user_can() with dynamic cap';
  } else if (signals.ownership_compare && signals.is_user_logged_in) {
    effective = 'OWNER_OR_SELF';
    risk = 'LOW';
    summary = 'Logged-in + ownership/self comparison';
  } else if (signals.ownership_compare) {
    effective = 'OWNERSHIP_CHECK';
    risk = 'LOW';
    summary = 'Compares current user to resource owner';
  } else if (signals.is_user_logged_in && signals.return_expression && /is_user_logged_in/.test(signals.return_expression)) {
    effective = 'LOGGED_IN_ONLY';
    risk = 'MEDIUM';
    summary = 'Only checks is_user_logged_in()';
  } else if (signals.is_user_logged_in) {
    effective = 'LOGGED_IN_PLUS';
    risk = 'MEDIUM';
    summary = 'is_user_logged_in present; may have extra checks';
  } else if (signals.is_super_admin) {
    effective = 'SUPER_ADMIN';
    risk = 'LOW';
    summary = 'is_super_admin() check';
  } else if (signals.calls_other_methods.length) {
    effective = 'DELEGATES_TO_METHOD';
    risk = 'REVIEW';
    summary = `Delegates to $this->${signals.calls_other_methods[0]}() (may need recursive resolve)`;
  } else if (signals.return_false_literal && !signals.return_true_literal) {
    effective = 'ALWAYS_DENY';
    risk = 'LOW';
    summary = 'Returns false (deny-all or stub)';
  } else {
    effective = 'CUSTOM_LOGIC';
    risk = 'REVIEW';
    summary = `Custom permission logic; returns: ${signals.return_expression || 'n/a'}`;
  }

  // Write methods with weak auth escalate
  const http = (meta.methods || []).map((x) => String(x).toUpperCase());
  const isWrite = http.some((h) => ['POST', 'PUT', 'PATCH', 'DELETE'].includes(h));
  if (
    isWrite &&
    (effective === 'RETURNS_TRUE_UNCONDITIONALLY' || effective === 'EMPTY_BODY')
  ) {
    risk = 'CRITICAL';
    summary += ' [WRITE method is publicly callable]';
  } else if (isWrite && effective === 'LOGGED_IN_ONLY') {
    // still ok-ish but note
    summary += ' [WRITE allowed for any logged-in user]';
  }

  // Known intentional public auth surfaces (still public, annotated)
  const ep = meta.endpoint || '';
  const intentionalPublic =
    effective === 'RETURNS_TRUE_UNCONDITIONALLY' &&
    /\/(auth\/(login|register|reset|forgot|verify)|health|csp-report|quiz\/|dj\/config)/i.test(
      ep
    );
  if (intentionalPublic) {
    summary += ' [likely intentional public surface]';
    // keep HIGH for write auth endpoints, but mark intentional
  }

  return {
    effective_class: effective,
    risk,
    summary,
    capabilities: caps,
    signals: {
      is_user_logged_in: signals.is_user_logged_in,
      return_true_only: signals.return_true_only || (signals.return_true_literal && returns.length === 1 && returns[0] === 'true'),
      ownership_compare: signals.ownership_compare,
      nonce: signals.wp_verify_nonce || signals.check_ajax_referer,
      delegates: uniqueSorted(signals.calls_other_methods),
      returns,
    },
    body_preview: compact.slice(0, 280),
    resolved: true,
    intentional_public: intentionalPublic,
  };
}

function classifyPermission(slice, methodIndex, fileRel, httpMethods, endpoint) {
  // Route-local slice only (already balanced). Allow quoted 'permission_callback' keys.
  const s = slice;
  const baseMeta = { methods: httpMethods, endpoint };
  const PC = String.raw`['"]permission_callback['"]\s*=>`;

  // __return_true
  if (
    new RegExp(PC + String.raw`\s*['"]__return_true['"]`).test(s) ||
    new RegExp(PC + String.raw`\s*__return_true\b`).test(s)
  ) {
    const analysis = analyzePermissionBody('return true;', baseMeta);
    analysis.binding = '__return_true';
    analysis.effective_class = 'PUBLIC_UNAUTHENTICATED';
    analysis.resolved = true;
    analysis.summary = 'permission_callback is __return_true';
    // re-run write escalation (only once)
    const http = (httpMethods || []).map((x) => String(x).toUpperCase());
    if (http.some((h) => ['POST', 'PUT', 'PATCH', 'DELETE'].includes(h))) {
      analysis.risk = 'CRITICAL';
      analysis.summary += ' [WRITE method is publicly callable]';
    } else {
      analysis.risk = 'HIGH';
    }
    if (
      /\/(auth\/(login|register|reset|forgot|verify|resend|check-|logout|token)|health|csp-report|quiz\/|simon\/|dj\/config)/i.test(
        endpoint || ''
      )
    ) {
      analysis.intentional_public = true;
      analysis.summary += ' [likely intentional public surface]';
    }
    return {
      class: analysis.effective_class,
      risk: analysis.risk,
      method: null,
      analysis,
    };
  }

  // is_user_logged_in string callback
  if (new RegExp(PC + String.raw`\s*['"]is_user_logged_in['"]`).test(s)) {
    const analysis = analyzePermissionBody('return is_user_logged_in();', baseMeta);
    analysis.binding = 'is_user_logged_in';
    analysis.effective_class = 'LOGGED_IN_ONLY';
    analysis.risk = 'MEDIUM';
    analysis.summary = 'WP core is_user_logged_in callback';
    return { class: 'LOGGED_IN_ONLY', risk: 'MEDIUM', method: 'is_user_logged_in', analysis };
  }

  // array( $this, 'method' )  — allow spaces: array( $this, 'check_admin' )
  let m = s.match(
    new RegExp(
      PC +
        String.raw`\s*(?:array\s*\(\s*\$this\s*,\s*['"](\w+)['"]\s*\)|\[\s*\$this\s*,\s*['"](\w+)['"]\s*\])`
    )
  );
  if (m) {
    const method = m[1] || m[2];
    return resolveMethodPermission(method, null, methodIndex, fileRel, baseMeta, 'INSTANCE_METHOD');
  }

  // array( Class, 'method' ) or [ Class::class, 'method' ]
  m = s.match(
    new RegExp(
      PC +
        String.raw`\s*(?:array\s*\(\s*([A-Za-z0-9_\\:]+)\s*,\s*['"](\w+)['"]\s*\)|\[\s*([A-Za-z0-9_\\:]+)\s*,\s*['"](\w+)['"]\s*\])`
    )
  );
  if (m) {
    const target = (m[1] || m[3] || '').replace(/^\\/, '').replace(/::class$/, '');
    const method = m[2] || m[4];
    // $this already handled; skip if target is $this
    if (target === '$this') {
      return resolveMethodPermission(method, null, methodIndex, fileRel, baseMeta, 'INSTANCE_METHOD');
    }
    return resolveMethodPermission(method, target, methodIndex, fileRel, baseMeta, 'STATIC_OR_CLASS_METHOD');
  }

  // closure / arrow — return types may be bool|\WP_Error etc.
  if (new RegExp(PC + String.raw`\s*(?:static\s*)?(?:function\s*\(|fn\s*\()`).test(s)) {
    let body = '';
    // Find function keyword after permission_callback and extract balanced braces
    const fnIdx = s.search(
      /['"]permission_callback['"]\s*=>\s*(?:static\s*)?function\s*\(/
    );
    if (fnIdx >= 0) {
      const braceIdx = s.indexOf('{', fnIdx);
      if (braceIdx >= 0) {
        let depth = 0;
        let end = braceIdx;
        for (let i = braceIdx; i < s.length && i < braceIdx + 4000; i++) {
          if (s[i] === '{') depth++;
          else if (s[i] === '}') {
            depth--;
            if (depth === 0) {
              end = i;
              break;
            }
          }
        }
        body = s.slice(braceIdx + 1, end);
      }
    }
    if (!body) {
      const arrow = s.match(
        new RegExp(
          PC +
            String.raw`\s*(?:static\s*)?fn\s*\([^)]*\)\s*(?::\s*[^=]+)?=>\s*([^,]+)`
        )
      );
      if (arrow) body = `return ${arrow[1]};`;
    }
    body = (body || '').slice(0, 1500);
    const analysis = analyzePermissionBody(body, baseMeta);
    analysis.binding = 'closure';
    return {
      class: analysis.effective_class,
      risk: analysis.risk,
      method: null,
      analysis,
    };
  }

  if (!new RegExp(PC).test(s) && !/permission_callback/.test(s)) {
    return {
      class: 'MISSING_PERMISSION_CALLBACK',
      risk: 'CRITICAL',
      method: null,
      analysis: {
        effective_class: 'MISSING',
        risk: 'CRITICAL',
        summary: 'No permission_callback in route registration',
        resolved: false,
      },
    };
  }

  // Last-resort: capture raw permission_callback value
  const raw = s.match(
    /['"]permission_callback['"]\s*=>\s*([^,]+(?:,\s*(?=array|'|\"|function|fn|\[))?)/
  );
  return {
    class: 'OTHER',
    risk: 'REVIEW',
    method: null,
    analysis: {
      effective_class: 'UNPARSED',
      risk: 'REVIEW',
      summary: 'Could not parse permission_callback binding',
      resolved: false,
      raw_snippet: (raw ? raw[0] : s.match(/permission_callback[\s\S]{0,160}/)?.[0] || null),
    },
  };
}

function resolveMethodPermission(method, targetClass, methodIndex, fileRel, baseMeta, bindClass) {
  const candidates = (methodIndex && methodIndex.get(method)) || [];
  let hit = null;
  if (candidates.length === 1) hit = candidates[0];
  else if (candidates.length > 1) {
    // prefer same file
    hit =
      candidates.find((c) => c.file === fileRel) ||
      (targetClass &&
        candidates.find(
          (c) =>
            c.className === targetClass ||
            (targetClass && targetClass.endsWith(c.className || ''))
        )) ||
      candidates[0];
  }

  if (!hit) {
    // Heuristic from method name when body not found in tree
    let guessClass = 'METHOD_NOT_FOUND';
    let guessRisk = 'HIGH';
    let guessSum = `permission method ${method}() not found in plugin PHP tree`;
    if (/logged_in|is_auth|check_auth|require_login/i.test(method)) {
      guessClass = 'LOGGED_IN_ONLY';
      guessRisk = 'MEDIUM';
      guessSum = `Method ${method}() not found in index; name implies logged-in check`;
    } else if (/admin|manage_options|check_admin/i.test(method)) {
      guessClass = 'ADMIN_CAPABILITY';
      guessRisk = 'LOW';
      guessSum = `Method ${method}() not found in index; name implies admin check`;
    }
    return {
      class: guessClass,
      risk: guessRisk,
      method,
      target: targetClass,
      analysis: {
        effective_class: guessClass,
        risk: guessRisk,
        summary: guessSum,
        resolved: false,
        method,
        target: targetClass,
        body_preview: null,
      },
    };
  }

  let analysis = analyzePermissionBody(hit.body, baseMeta);
  analysis.binding = `${hit.className || targetClass || 'class'}::${method}`;
  analysis.method_file = hit.file;
  analysis.method_signature = hit.signature;
  analysis.resolved = true;

  // One-level recursive resolve for simple delegates: return $this->foo();
  if (
    analysis.effective_class === 'DELEGATES_TO_METHOD' &&
    analysis.signals &&
    analysis.signals.delegates &&
    analysis.signals.delegates.length === 1
  ) {
    const del = analysis.signals.delegates[0];
    const delHits = (methodIndex && methodIndex.get(del)) || [];
    const delHit =
      delHits.find((c) => c.file === hit.file) ||
      delHits.find((c) => c.className === hit.className) ||
      delHits[0];
    if (delHit) {
      const inner = analyzePermissionBody(delHit.body, baseMeta);
      analysis.delegated_to = {
        method: del,
        file: delHit.file,
        effective_class: inner.effective_class,
        risk: inner.risk,
        summary: inner.summary,
        capabilities: inner.capabilities,
        body_preview: inner.body_preview,
      };
      // elevate classification to inner if stronger signal
      if (inner.effective_class !== 'UNKNOWN' && inner.effective_class !== 'CUSTOM_LOGIC') {
        analysis.effective_class = inner.effective_class;
        analysis.risk = inner.risk;
        analysis.summary = `Delegates to ${del}(): ${inner.summary}`;
        analysis.capabilities = inner.capabilities;
      }
    }
  }

  return {
    class: analysis.effective_class,
    risk: analysis.risk,
    method,
    target: hit.className || targetClass,
    analysis,
  };
}

/**
 * Extract balanced (...) block starting at openParen index in content.
 */
function extractBalanced(content, openParenIdx) {
  if (openParenIdx < 0 || content[openParenIdx] !== '(') return null;
  let depth = 0;
  for (let i = openParenIdx; i < content.length && i < openParenIdx + 12000; i++) {
    const ch = content[i];
    // skip strings
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
    if (ch === '(') depth++;
    else if (ch === ')') {
      depth--;
      if (depth === 0) return content.slice(openParenIdx, i + 1);
    }
  }
  return content.slice(openParenIdx, Math.min(content.length, openParenIdx + 4000));
}

/**
 * A single register_rest_route may register multiple method configs in an outer array.
 * Split top-level array( array(...), array(...) ) configs when present.
 */
function splitRouteConfigs(argsBlock) {
  // argsBlock is full ( ns, route, config )
  // Find third argument starting region
  const configs = [];
  // Find all 'permission_callback' occurrences with local windows of their parent array
  // Simpler: if multiple permission_callback, treat whole block as multi and emit one route per methods+permission pair
  const permMatches = [...argsBlock.matchAll(/['"]permission_callback['"]\s*=>/g)];
  if (permMatches.length <= 1) {
    return [argsBlock];
  }
  // Multi-method registration: split on array( that contain permission_callback
  // Walk for nested array( ... ) at depth that contains permission_callback
  let i = 0;
  while (i < argsBlock.length) {
    const idx = argsBlock.indexOf('array', i);
    if (idx < 0) break;
    // find (
    let j = idx + 5;
    while (j < argsBlock.length && /\s/.test(argsBlock[j])) j++;
    if (argsBlock[j] !== '(') {
      i = idx + 5;
      continue;
    }
    const block = extractBalanced(argsBlock, j);
    if (!block) break;
    if (/permission_callback/.test(block) && /methods/.test(block)) {
      configs.push(block);
    }
    i = j + block.length;
  }
  return configs.length ? configs : [argsBlock];
}

function extractRestDeep(content, fileRel, methodIndex) {
  const routes = [];
  const re = /register_rest_route\s*\(/g;
  let m;
  while ((m = re.exec(content))) {
    const fullCall = extractBalanced(content, m.index + m[0].length - 1);
    if (!fullCall) continue;

    // Parse namespace + route from start of call
    const head = fullCall.slice(0, 400);
    const headM = head.match(
      /^\(\s*([^,]+?)\s*,\s*(['"])(\/?[^'"]+)\2/s
    );
    if (!headM) continue;
    let route = headM[3];
    if (!route.startsWith('/')) route = `/${route}`;
    if (/^[A-Z_]+$/.test(route.slice(1)) && route.length < 3) continue;

    const nsExpr = headM[1].replace(/\s+/g, ' ').trim();
    let ns = 'apollo/v1';
    const lit = nsExpr.match(/^['"]([^'"]+)['"]$/);
    if (lit) ns = lit[1];
    else {
      const cns = content.match(
        /(?:const|public\s+const)\s+NAMESPACE\s*=\s*['"]([^'"]+)['"]/
      );
      if (cns) ns = cns[1];
      else {
        const asn = content.match(
          /(?:\$this->namespace|\$namespace|protected\s+\$namespace)\s*=\s*['"]([^'"]+)['"]/
        );
        if (asn) ns = asn[1];
      }
    }

    const configs = splitRouteConfigs(fullCall);
    for (const slice of configs) {
      // CRITICAL: only use this route's balanced block — never next route
      const methods = parseMethodsNear(slice);
      const perm = classifyPermission(slice, methodIndex, fileRel, methods, route);
      let callback = null;
      const cb = slice.match(
        /['"]callback['"]\s*=>\s*(?:array\s*\(\s*\$this\s*,\s*['"](\w+)['"]|\[\s*\$this\s*,\s*['"](\w+)['"])/
      );
      if (cb) callback = cb[1] || cb[2];

      routes.push({
        namespace: ns,
        endpoint: route,
        methods: methods.length ? methods : ['?'],
        permission: perm,
        callback: callback,
        file: fileRel,
      });
    }
  }
  return routes;
}

// ─── Schema / tables deep ───────────────────────────────────────────────────

function parseColumnsFromCreateSql(sql) {
  const cols = [];
  // strip CREATE TABLE ... (
  const bodyMatch = sql.match(/\(\s*([\s\S]*)\)\s*(?:\$\{?charset|ENGINE|DEFAULT|CHARSET|;|\s*$)/i) ||
    sql.match(/\(\s*([\s\S]*)\)\s*$/);
  if (!bodyMatch) return cols;
  // rough line split
  const lines = bodyMatch[1].split(/,\s*(?=(?:PRIMARY|KEY|UNIQUE|INDEX|CONSTRAINT|FULLTEXT)?\s)/i);
  // simpler: match col definitions
  for (const line of bodyMatch[1].split('\n')) {
    const t = line.trim().replace(/,$/, '');
    if (!t) continue;
    if (/^(PRIMARY\s+KEY|KEY|UNIQUE|INDEX|CONSTRAINT|FULLTEXT)/i.test(t)) {
      cols.push({ kind: 'index', def: t.slice(0, 120) });
      continue;
    }
    const cm = t.match(/^`?([a-zA-Z_][a-zA-Z0-9_]*)`?\s+([A-Za-z0-9()_\s]+)/);
    if (cm) {
      cols.push({
        kind: 'column',
        name: cm[1],
        type: cm[2].trim().split(/\s+/).slice(0, 3).join(' ').slice(0, 60),
      });
    }
  }
  return cols;
}

function extractTableSchemas(content) {
  const schemas = [];
  let m;

  // Local prefix vars: $prefix = $wpdb->prefix . 'apollo_'; then {$prefix}chat_threads
  const prefixVars = new Set(['this->prefix']);
  let pre =
    /\$([a-zA-Z_][\w]*)\s*=\s*\$wpdb\s*->\s*prefix\s*\.\s*['"]apollo_['"]/g;
  while ((m = pre.exec(content))) prefixVars.add(m[1]);

  for (const pv of prefixVars) {
    const esc = pv.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    // CREATE TABLE {$prefix}chat_threads ( ... )
    const reA = new RegExp(
      'CREATE\\s+TABLE\\s+(?:IF\\s+NOT\\s+EXISTS\\s+)?\\{\\$' +
        esc +
        '\\}([a-z0-9_]+)\\s*\\(([\\s\\S]*?)\\)\\s*(?:\\{\\$charset\\}|\\$charset|ENGINE|;|\\n\\s*")',
      'gi'
    );
    while ((m = reA.exec(content))) {
      schemas.push({
        table: 'apollo_' + m[1],
        columns: parseColumnsFromCreateSql('(' + m[2] + ')'),
        source: 'create_var_' + pv,
        defined: true,
      });
    }
    // {$prefix}foo or $prefix . 'foo'
    const reRef = new RegExp(
      '(?:\\{\\$' + esc + '\\}([a-z0-9_]+)|\\$' + esc + '\\s*\\.\\s*[\'"]([a-z0-9_]+)[\'"])',
      'g'
    );
    while ((m = reRef.exec(content))) {
      const suf = m[1] || m[2];
      if (suf && !/^(version|option|path|url|file|dir)$/i.test(suf)) {
        schemas.push({
          table: 'apollo_' + suf,
          columns: [],
          source: 'prefix_var_ref_' + pv,
          ref_only: true,
        });
      }
    }
  }

  // CREATE TABLE {$wpdb->prefix}apollo_x
  let re =
    /CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?\{\$wpdb->prefix\}(apollo_[a-z0-9_]+)\s*\(([\s\S]*?)\)/gi;
  while ((m = re.exec(content))) {
    schemas.push({
      table: m[1],
      columns: parseColumnsFromCreateSql('(' + m[2] + ')'),
      source: 'create_wpdb_prefix',
      defined: true,
    });
  }

  // CREATE TABLE ... apollo_xxx literal
  re =
    /CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`']?(apollo_[a-z0-9_]+)[`']?\s*\(([\s\S]*?)\)/gi;
  while ((m = re.exec(content))) {
    schemas.push({
      table: m[1],
      columns: parseColumnsFromCreateSql('(' + m[2] + ')'),
      source: 'create_literal_apollo',
      defined: true,
    });
  }

  // $wpdb->prefix . 'apollo_xxx'
  re = /\$wpdb\s*->\s*prefix\s*\.\s*['"](apollo_[a-z0-9_]+)['"]/gi;
  while ((m = re.exec(content))) {
    schemas.push({
      table: m[1],
      columns: [],
      source: 'wpdb_concat',
      ref_only: true,
    });
  }

  // Known table-like string names
  re = /['"](apollo_[a-z][a-z0-9_]{2,})['"]/g;
  while ((m = re.exec(content))) {
    const name = m[1];
    if (
      /_(threads|messages|participants|sessions|attempts|queue|log|events|users|views|scores|results|reports|actions|attachments|reactions|presence|typing|blocks|verif|signatures|audit|fields|members|notifications|favorites|follows|lockouts|settings|state|clicks|pageviews|content|radio|rsvp|refresh|activity|appointments|stats_|jwt_|url_rewrites|matchmaking|profile_views|user_fields|wow_)/i.test(
        name
      )
    ) {
      schemas.push({
        table: name,
        columns: [],
        source: 'string_table_name',
        ref_only: true,
      });
    }
  }

  // DatabaseBuilder schema array
  re =
    /['"]([a-z0-9_]+)['"]\s*=>\s*array\s*\(\s*['"]plugin['"]\s*=>\s*['"]([^'"]+)['"][\s\S]{0,300}?CREATE\s+TABLE\s+\{\$this->prefix\}([a-z0-9_]+)/gi;
  while ((m = re.exec(content))) {
    schemas.push({
      table: 'apollo_' + m[3],
      owner_plugin_in_schema: m[2],
      schema_key: m[1],
      columns: [],
      source: 'schema_array',
      defined: true,
    });
  }

  const map = new Map();
  for (const s of schemas) {
    if (!s.table || !/^apollo_[a-z0-9_]+$/.test(s.table) || s.table.length > 64) continue;
    const colLen = (s.columns || []).filter((c) => c && c.kind === 'column').length;
    const prev = map.get(s.table);
    const prevLen = prev
      ? (prev.columns || []).filter((c) => c && c.kind === 'column').length
      : -1;
    const defined = !!s.defined || /create_/i.test(s.source) || s.source === 'schema_array';
    if (!prev || colLen > prevLen) {
      map.set(s.table, {
        ...s,
        defined: defined || (prev && prev.defined),
        ref_only: defined ? false : !!s.ref_only,
      });
    } else if (prev && defined) {
      prev.defined = true;
      prev.ref_only = false;
      if (s.source) prev.source = s.source;
    }
  }
  return [...map.values()].sort((a, b) => a.table.localeCompare(b.table));
}

// ─── Meta deep ──────────────────────────────────────────────────────────────

function extractMetaDeep(content) {
  const post = new Set();
  const user = new Set();
  const term = new Set();
  const registered = [];

  // register_post_meta( $type, 'key'
  let re = /register_post_meta\s*\(\s*([^,]+)\s*,\s*['"]([^'"]+)['"]/g;
  let m;
  while ((m = re.exec(content))) {
    post.add(m[2]);
    registered.push({ kind: 'post', object: m[1].trim(), key: m[2] });
  }
  re = /register_term_meta\s*\(\s*([^,]+)\s*,\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) {
    term.add(m[2]);
    registered.push({ kind: 'term', object: m[1].trim(), key: m[2] });
  }
  re = /register_meta\s*\(\s*['"](\w+)['"]\s*,\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) {
    if (m[1] === 'user') user.add(m[2]);
    else if (m[1] === 'term') term.add(m[2]);
    else post.add(m[2]);
    registered.push({ kind: m[1], key: m[2] });
  }

  // Config array keys: '_apollo_xxx' => array(
  re = /['"]((?:_apollo_|apollo_)[a-z0-9_]+)['"]\s*=>\s*array\s*\(/gi;
  while ((m = re.exec(content))) {
    const k = m[1];
    if (/user|membership|profile|login|verified|quiz|simon|password|email/i.test(k)) {
      user.add(k);
    } else {
      post.add(k);
    }
  }

  // get_post_meta / update_post_meta / get_user_meta hardcoded keys
  re = /get_post_meta\s*\(\s*[^,]+,\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) if (/apollo/i.test(m[1]) || m[1].startsWith('_')) post.add(m[1]);
  re = /update_post_meta\s*\(\s*[^,]+,\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) if (/apollo/i.test(m[1]) || m[1].startsWith('_')) post.add(m[1]);
  re = /get_user_meta\s*\(\s*[^,]+,\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) if (/apollo/i.test(m[1]) || m[1].startsWith('_')) user.add(m[1]);
  re = /update_user_meta\s*\(\s*[^,]+,\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) if (/apollo/i.test(m[1]) || m[1].startsWith('_')) user.add(m[1]);
  re = /get_term_meta\s*\(\s*[^,]+,\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) if (/apollo/i.test(m[1]) || m[1].startsWith('_')) term.add(m[1]);

  // define META constants
  re = /define\s*\(\s*['"]([A-Z0-9_]*META[A-Z0-9_]*)['"]\s*,\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) {
    if (/user/i.test(m[1])) user.add(m[2]);
    else post.add(m[2]);
  }

  return {
    post_meta: uniqueSorted([...post]).filter((k) => k.length < 80),
    user_meta: uniqueSorted([...user]).filter((k) => k.length < 80),
    term_meta: uniqueSorted([...term]).filter((k) => k.length < 80),
    registered,
  };
}

// ─── Rewrites / pages ───────────────────────────────────────────────────────

function extractRewrites(content, fileRel) {
  const rules = [];
  // add_rewrite_rule( 'pattern', 'query', 'top' )
  const re = /add_rewrite_rule\s*\(\s*(['"])([\s\S]*?)\1\s*,\s*(['"])([\s\S]*?)\3(?:\s*,\s*(['"])(\w+)\5)?/g;
  let m;
  while ((m = re.exec(content))) {
    rules.push({
      pattern: m[2].replace(/\s+/g, ' ').trim(),
      query: m[4].replace(/\s+/g, ' ').trim(),
      position: m[6] || 'bottom',
      file: fileRel,
    });
  }
  // multiline add_rewrite_rule( '^agenda/?$' , ...
  const re2 =
    /add_rewrite_rule\s*\(\s*\r?\n\s*(['"])([^'"]+)\1\s*,\s*\r?\n\s*(['"])([^'"]+)\3/g;
  while ((m = re2.exec(content))) {
    if (!rules.find((r) => r.pattern === m[2])) {
      rules.push({
        pattern: m[2],
        query: m[4],
        position: 'top?',
        file: fileRel,
      });
    }
  }
  return rules;
}

function extractQueryVars(content) {
  const vars = new Set();
  // $vars[] = 'apollo_xxx'
  let re = /\$vars\s*\[\s*\]\s*=\s*['"]([a-z0-9_]+)['"]/gi;
  let m;
  while ((m = re.exec(content))) vars.add(m[1]);
  // query_vars filter arrays
  re = /['"]query_vars['"]\s*=>\s*\[([^\]]+)\]/g;
  while ((m = re.exec(content))) {
    for (const x of m[1].matchAll(/['"]([a-z0-9_]+)['"]/g)) vars.add(x[1]);
  }
  // get_query_var( 'apollo_x'
  re = /get_query_var\s*\(\s*['"]([a-z0-9_]+)['"]/g;
  while ((m = re.exec(content))) if (/apollo|event|dj|loc|hub|agenda/i.test(m[1])) vars.add(m[1]);
  return uniqueSorted([...vars]);
}

function pagesFromRewrites(rules) {
  const pages = [];
  for (const r of rules) {
    // pattern like ^agenda/?$ or ^id/([^/]+)/?$
    let slug = r.pattern
      .replace(/^\^/, '')
      .replace(/\/?\$$/, '')
      .replace(/\/\?\$?$/, '')
      .replace(/\(\[.*?\][+*]\)/g, '{param}')
      .replace(/\([^)]+\)/g, '{param}');
    const q = r.query;
    const qv = [...q.matchAll(/([a-z0-9_]+)=([^&]+)/gi)].map((x) => `${x[1]}=${x[2]}`);
    pages.push({
      slug: slug || r.pattern,
      rewrite: r.pattern,
      query: r.query,
      query_vars: qv,
      type: 'virtual_rewrite',
      _source: 'live_scan',
    });
  }
  return pages;
}

// ─── CPT / Tax ──────────────────────────────────────────────────────────────

function extractCptsDeep(content) {
  const cpts = new Map(); // slug -> info
  let re = /register_post_type\s*\(\s*['"]([a-z0-9_-]+)['"]\s*,\s*(array\s*\(|\[)/g;
  let m;
  while ((m = re.exec(content))) {
    const slice = content.slice(m.index, m.index + 2000);
    const pub = /['"]public['"]\s*=>\s*(true|false)/.exec(slice);
    const rest = /['"]show_in_rest['"]\s*=>\s*(true|false)/.exec(slice);
    const menu = /['"]menu_icon['"]\s*=>\s*['"]([^'"]+)['"]/.exec(slice);
    cpts.set(m[1], {
      slug: m[1],
      registered_locally: true,
      public: pub ? pub[1] === 'true' : null,
      show_in_rest: rest ? rest[1] === 'true' : null,
      menu_icon: menu ? menu[1] : null,
    });
  }
  re =
    /(?:define\s*\(\s*['"][A-Z0-9_]*CPT[A-Z0-9_]*['"]\s*,\s*['"]([a-z0-9_-]+)['"]|[A-Z0-9_]*CPT[A-Z0-9_]*\s*=\s*['"]([a-z0-9_-]+)['"])/g;
  while ((m = re.exec(content))) {
    const slug = m[1] || m[2];
    if (!cpts.has(slug)) {
      cpts.set(slug, { slug, registered_locally: false, via_constant: true });
    }
  }
  return [...cpts.values()];
}

function extractTaxDeep(content) {
  const tax = new Map();
  let re = /register_taxonomy\s*\(\s*['"]([a-z0-9_-]+)['"]\s*,/g;
  let m;
  while ((m = re.exec(content))) {
    tax.set(m[1], { slug: m[1], registered_locally: true });
  }
  re =
    /(?:define\s*\(\s*['"][A-Z0-9_]*TAX[A-Z0-9_]*['"]\s*,\s*['"]([a-z0-9_-]+)['"]|[A-Z0-9_]*(?:TAX|TAXONOMY)[A-Z0-9_]*\s*=\s*['"]([a-z0-9_-]+)['"])/g;
  while ((m = re.exec(content))) {
    const slug = m[1] || m[2];
    if (!tax.has(slug)) tax.set(slug, { slug, registered_locally: false, via_constant: true });
  }
  return [...tax.values()];
}

// ─── Classes / architecture ─────────────────────────────────────────────────

function extractClasses(content, fileRel, fileNs) {
  const out = [];
  // namespace
  const nsM = content.match(/^\s*namespace\s+([A-Za-z0-9_\\]+)\s*;/m);
  const ns = nsM ? nsM[1] : fileNs || '';
  const re =
    /^\s*(abstract\s+|final\s+)?(class|interface|trait)\s+([A-Za-z_][A-Za-z0-9_]*)(?:\s+extends\s+([A-Za-z0-9_\\]+))?(?:\s+implements\s+([A-Za-z0-9_\\,\s]+))?/gm;
  let m;
  while ((m = re.exec(content))) {
    out.push({
      kind: m[2],
      name: m[3],
      fqcn: ns ? `${ns}\\${m[3]}` : m[3],
      extends: m[4] || null,
      implements: m[5] ? m[5].split(',').map((s) => s.trim()) : [],
      modifiers: (m[1] || '').trim() || null,
      file: fileRel,
    });
  }
  return out;
}

function extractPublicFunctions(content, fileRel) {
  const fns = [];
  // only procedural files (no class context simple heuristic: function at column 0 or after ABSPATH)
  const re = /^function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/gm;
  let m;
  while ((m = re.exec(content))) {
    if (m[1].startsWith('apollo_') || m[1].startsWith('_apollo')) {
      fns.push({ name: m[1], file: fileRel });
    }
  }
  return fns;
}

// ─── Hooks / ajax / cron / shortcodes ───────────────────────────────────────

function extractHooks(content) {
  const fired = new Set();
  const listened = new Set();
  let re = /(?:do_action|apply_filters)(?:_ref_array)?\s*\(\s*['"]([^'"]+)['"]/g;
  let m;
  while ((m = re.exec(content))) fired.add(m[1]);
  re = /add_(?:action|filter)\s*\(\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) listened.add(m[1]);
  return {
    fired: uniqueSorted([...fired]),
    listened: uniqueSorted([...listened]),
    apollo_fired: uniqueSorted([...fired].filter((h) => h.startsWith('apollo'))),
    apollo_listened: uniqueSorted([...listened].filter((h) => h.startsWith('apollo'))),
  };
}

function extractAjax(content) {
  const priv = new Set();
  const nopriv = new Set();
  let re = /add_action\s*\(\s*['"]wp_ajax_([^'"]+)['"]/g;
  let m;
  while ((m = re.exec(content))) priv.add(m[1]);
  re = /add_action\s*\(\s*['"]wp_ajax_nopriv_([^'"]+)['"]/g;
  while ((m = re.exec(content))) nopriv.add(m[1]);
  return { priv: uniqueSorted([...priv]), nopriv: uniqueSorted([...nopriv]) };
}

function extractCron(content) {
  const hooks = new Set();
  let re = /wp_schedule_(?:event|single_event)\s*\([\s\S]{0,200}?['"]([a-z0-9_\/\-]+)['"]/gi;
  let m;
  while ((m = re.exec(content))) {
    if (!/^(hourly|twicedaily|daily|weekly)$/i.test(m[1])) hooks.add(m[1]);
  }
  re = /add_action\s*\(\s*['"]([a-z0-9_]*(?:cron|daily|hourly|weekly)[a-z0-9_]*)['"]/gi;
  while ((m = re.exec(content))) hooks.add(m[1]);
  return uniqueSorted([...hooks]);
}

function extractShortcodes(content) {
  const tags = new Set();
  const re = /add_shortcode\s*\(\s*['"]([^'"]+)['"]/g;
  let m;
  while ((m = re.exec(content))) tags.add(m[1]);
  return uniqueSorted([...tags]);
}

// ─── Admin / assets / options ───────────────────────────────────────────────

function extractAdminMenus(content) {
  const menus = [];
  const re =
    /add_(?:menu|submenu|options)_page\s*\(\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]\s*,\s*['"]([^'"]+)['"]/g;
  let m;
  while ((m = re.exec(content))) {
    menus.push({
      page_title: m[1],
      menu_title: m[2],
      capability: m[3],
      menu_slug: m[4],
    });
  }
  return menus;
}

function extractAssets(content) {
  const scripts = new Set();
  const styles = new Set();
  let re = /wp_enqueue_script\s*\(\s*['"]([^'"]+)['"]/g;
  let m;
  while ((m = re.exec(content))) scripts.add(m[1]);
  re = /wp_register_script\s*\(\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) scripts.add(m[1]);
  re = /wp_enqueue_style\s*\(\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) styles.add(m[1]);
  re = /wp_register_style\s*\(\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) styles.add(m[1]);
  return { scripts: uniqueSorted([...scripts]), styles: uniqueSorted([...styles]) };
}

function extractOptions(content) {
  const keys = new Set();
  let re = /(?:get|update|add|delete)_option\s*\(\s*['"]([^'"]+)['"]/g;
  let m;
  while ((m = re.exec(content))) if (/apollo/i.test(m[1])) keys.add(m[1]);
  re = /register_setting\s*\(\s*['"][^'"]+['"]\s*,\s*['"]([^'"]+)['"]/g;
  while ((m = re.exec(content))) keys.add(m[1]);
  return uniqueSorted([...keys]);
}

function extractConstants(content) {
  const consts = [];
  let re = /define\s*\(\s*['"]([A-Z0-9_]+)['"]\s*,\s*([^)]+)\)/g;
  let m;
  while ((m = re.exec(content))) {
    if (/^APOLLO_/.test(m[1])) {
      let val = m[2].trim();
      if (/^['"]/.test(val)) {
        const vm = val.match(/^['"]([^'"]*)['"]/);
        val = vm ? vm[1] : val.slice(0, 40);
      } else {
        val = val.slice(0, 50);
      }
      consts.push({ name: m[1], value: val });
    }
  }
  return consts;
}

// ─── Security posture ───────────────────────────────────────────────────────

function securityPosture(files, contentsByFile) {
  let php = 0;
  let abspath = 0;
  let prepare = 0;
  let query_raw = 0;
  let nonce = 0;
  let caps = 0;
  let raw_get = 0;
  let raw_post = 0;
  let raw_request = 0;
  let return_true_perm = 0;
  let esc = 0;
  let echo_count = 0;
  let eval_count = 0;
  let unserialize = 0;
  let file_get = 0;

  for (const [file, t] of contentsByFile) {
    php++;
    if (/defined\s*\(\s*['"]ABSPATH['"]/.test(t) || /ABSPATH/.test(t.slice(0, 500))) abspath++;
    prepare += (t.match(/\$wpdb\s*->\s*prepare\s*\(/g) || []).length;
    query_raw += (t.match(/\$wpdb\s*->\s*(?:query|get_results|get_var|get_row|get_col)\s*\(/g) || []).length;
    nonce += (t.match(/wp_verify_nonce\s*\(|check_ajax_referer\s*\(|wp_create_nonce\s*\(|check_admin_referer\s*\(/g) || []).length;
    caps += (t.match(/current_user_can\s*\(/g) || []).length;
    raw_get += (t.match(/\$_GET\s*\[/g) || []).length;
    raw_post += (t.match(/\$_POST\s*\[/g) || []).length;
    raw_request += (t.match(/\$_REQUEST\s*\[/g) || []).length;
    return_true_perm += (t.match(/permission_callback[^\n]{0,80}__return_true/g) || []).length;
    esc += (t.match(/esc_(?:html|attr|url|js|textarea)\s*\(|wp_kses/g) || []).length;
    echo_count += (t.match(/\becho\s+/g) || []).length;
    eval_count += (t.match(/\beval\s*\(/g) || []).length;
    unserialize += (t.match(/\bunserialize\s*\(/g) || []).length;
    file_get += (t.match(/file_get_contents\s*\(\s*\$/g) || []).length;
  }

  const abspath_pct = php ? Math.round((abspath / php) * 100) : 0;
  const prepare_ratio = query_raw ? +(prepare / query_raw).toFixed(2) : prepare ? 1 : null;

  // crude risk score 0-100 (higher = more risk signals)
  let risk = 0;
  if (abspath_pct < 90) risk += 15;
  if (return_true_perm > 0) risk += Math.min(25, return_true_perm * 3);
  if (raw_get + raw_post + raw_request > 20) risk += 10;
  if (prepare_ratio !== null && prepare_ratio < 0.3 && query_raw > 5) risk += 15;
  if (nonce === 0 && (raw_post > 5 || return_true_perm > 0)) risk += 10;
  if (eval_count > 0) risk += 30;
  if (unserialize > 0) risk += 10;
  risk = Math.min(100, risk);

  return {
    php_files: php,
    abspath_guard_files: abspath,
    abspath_coverage_pct: abspath_pct,
    wpdb_prepare_calls: prepare,
    wpdb_query_calls: query_raw,
    prepare_to_query_ratio: prepare_ratio,
    nonce_checks: nonce,
    current_user_can_calls: caps,
    raw_superglobal: { get: raw_get, post: raw_post, request: raw_request },
    permission_callback_return_true: return_true_perm,
    escape_helpers: esc,
    echo_statements: echo_count,
    eval_calls: eval_count,
    unserialize_calls: unserialize,
    dynamic_file_get_contents: file_get,
    risk_score: risk,
    risk_band: risk >= 50 ? 'HIGH' : risk >= 25 ? 'MEDIUM' : 'LOW',
  };
}

// ─── Coupling ───────────────────────────────────────────────────────────────

function extractCoupling(slug, content, allSlugs) {
  const deps = new Set();
  for (const other of allSlugs) {
    if (other === slug) continue;
    // folder name references, constants, namespaces
    const short = other.replace(/^apollo-/, '');
    const ns = 'Apollo\\' + short.split('-').map((p) => p.charAt(0).toUpperCase() + p.slice(1)).join('');
    const constPrefix = 'APOLLO_' + short.replace(/-/g, '_').toUpperCase();
    if (
      content.includes(other) ||
      content.includes(constPrefix) ||
      content.includes(ns) ||
      new RegExp(`function_exists\\s*\\(\\s*['"]apollo_${short.replace(/-/g, '_')}`, 'i').test(content)
    ) {
      // require stronger signal than just string in comments - count occurrences
      const re = new RegExp(other.replace(/-/g, '[-_]'), 'gi');
      const hits = (content.match(re) || []).length;
      const constHits = (content.match(new RegExp(constPrefix, 'g')) || []).length;
      if (hits + constHits >= 2) deps.add(other);
    }
  }
  if (slug !== 'apollo-core' && /APOLLO_CORE_|Apollo\\Core|apollo-core/.test(content)) {
    deps.add('apollo-core');
  }
  return uniqueSorted([...deps]);
}

// ─── File inventory ─────────────────────────────────────────────────────────

function fileInventory(dir) {
  const byExt = {};
  const byTopDir = {};
  let totalBytes = 0;
  function walk(d, depth = 0, top = '') {
    if (depth > 12) return;
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
        const t = depth === 0 ? e.name : top;
        walk(p, depth + 1, t || e.name);
      } else {
        const ext = (path.extname(e.name) || '.none').toLowerCase();
        byExt[ext] = (byExt[ext] || 0) + 1;
        const t = top || '(root)';
        byTopDir[t] = (byTopDir[t] || 0) + 1;
        try {
          totalBytes += fs.statSync(p).size;
        } catch {
          /* ignore */
        }
      }
    }
  }
  walk(dir);
  return { by_extension: byExt, by_top_directory: byTopDir, total_bytes: totalBytes };
}

// ─── Activation ─────────────────────────────────────────────────────────────

function extractLifecycle(content) {
  return {
    register_activation_hook: /register_activation_hook\s*\(/.test(content),
    register_deactivation_hook: /register_deactivation_hook\s*\(/.test(content),
    register_uninstall_hook: /register_uninstall_hook\s*\(/.test(content),
    uninstall_php: false, // set later
  };
}

// ─── Per-plugin audit ───────────────────────────────────────────────────────

function auditPluginDeep(slug, oldEntry, allSlugs) {
  const dir = path.join(PLUGINS_ROOT, slug);
  const t0 = Date.now();

  if (!fs.existsSync(dir)) {
    return {
      entry: {
        slug,
        status: 'NOT_INSTALLED',
        description: 'Directory missing',
        dependencies: [],
        provides: [],
        cpts: [],
        taxonomies: [],
        tables: [],
        meta: {},
        rest: [],
        pages: [],
        shortcodes: [],
        _deep_audit: { exists: false },
      },
      report: { slug, status: 'MISSING' },
    };
  }

  const mainPhp = path.join(dir, `${slug}.php`);
  const mainContent = readText(mainPhp) || '';
  const header = parsePluginHeader(mainContent);
  const composer = parseComposer(dir);
  const phpFiles = walkPhp(dir);
  const inv = fileInventory(dir);

  const contentsByFile = new Map();
  let budget = 12 * 1024 * 1024;
  for (const f of phpFiles) {
    const t = readText(f);
    if (!t) continue;
    if (t.length > budget) {
      contentsByFile.set(f, t.slice(0, Math.max(budget, 0)));
      budget = 0;
    } else {
      contentsByFile.set(f, t);
      budget -= t.length;
    }
  }

  // Aggregate
  const restAll = [];
  const schemasAll = [];
  const rewritesAll = [];
  const queryVars = new Set();
  const cptsAll = new Map();
  const taxAll = new Map();
  const classesAll = [];
  const functionsAll = [];
  const hooksAgg = { fired: new Set(), listened: new Set(), apollo_fired: new Set(), apollo_listened: new Set() };
  const ajaxAgg = { priv: new Set(), nopriv: new Set() };
  const cronAll = new Set();
  const shortcodesAll = new Set();
  const menusAll = [];
  const assetsAgg = { scripts: new Set(), styles: new Set() };
  const optionsAll = new Set();
  const constantsAll = [];
  const metaAgg = { post: new Set(), user: new Set(), term: new Set(), registered: [] };

  // Method index keyed by relative path (for permission body resolution)
  const contentsByRel = new Map();
  for (const [f, t] of contentsByFile) {
    contentsByRel.set(rel(dir, f), t);
  }
  const methodIndex = indexPhpMethods(contentsByRel);

  let allText = '';
  let phpLoc = 0;

  for (const [f, t] of contentsByFile) {
    const fileRel = rel(dir, f);
    phpLoc += t.split(/\r?\n/).length;
    allText += `\n/*FILE:${fileRel}*/\n` + t;

    for (const r of extractRestDeep(t, fileRel, methodIndex)) restAll.push(r);
    for (const s of extractTableSchemas(t)) schemasAll.push(s);
    for (const r of extractRewrites(t, fileRel)) rewritesAll.push(r);
    for (const v of extractQueryVars(t)) queryVars.add(v);
    for (const c of extractCptsDeep(t)) {
      if (!cptsAll.has(c.slug) || c.registered_locally) cptsAll.set(c.slug, c);
    }
    for (const tx of extractTaxDeep(t)) {
      if (!taxAll.has(tx.slug) || tx.registered_locally) taxAll.set(tx.slug, tx);
    }
    classesAll.push(...extractClasses(t, fileRel));
    functionsAll.push(...extractPublicFunctions(t, fileRel));
    const h = extractHooks(t);
    h.fired.forEach((x) => hooksAgg.fired.add(x));
    h.listened.forEach((x) => hooksAgg.listened.add(x));
    h.apollo_fired.forEach((x) => hooksAgg.apollo_fired.add(x));
    h.apollo_listened.forEach((x) => hooksAgg.apollo_listened.add(x));
    const aj = extractAjax(t);
    aj.priv.forEach((x) => ajaxAgg.priv.add(x));
    aj.nopriv.forEach((x) => ajaxAgg.nopriv.add(x));
    extractCron(t).forEach((x) => cronAll.add(x));
    extractShortcodes(t).forEach((x) => shortcodesAll.add(x));
    menusAll.push(...extractAdminMenus(t));
    const as = extractAssets(t);
    as.scripts.forEach((x) => assetsAgg.scripts.add(x));
    as.styles.forEach((x) => assetsAgg.styles.add(x));
    extractOptions(t).forEach((x) => optionsAll.add(x));
    if (fileRel === `${slug}.php` || /constants\.php$|config\//.test(fileRel)) {
      constantsAll.push(...extractConstants(t));
    }
    const meta = extractMetaDeep(t);
    meta.post_meta.forEach((x) => metaAgg.post.add(x));
    meta.user_meta.forEach((x) => metaAgg.user.add(x));
    meta.term_meta.forEach((x) => metaAgg.term.add(x));
    meta.registered.forEach((x) => metaAgg.registered.push(x));
  }

  // Dedupe schemas
  const schemaMap = new Map();
  for (const s of schemasAll) {
    const prev = schemaMap.get(s.table);
    if (!prev || (s.columns || []).length > (prev.columns || []).length) schemaMap.set(s.table, s);
  }
  const tablesDetailed = [...schemaMap.values()].sort((a, b) => a.table.localeCompare(b.table));
  // SSOT tables = CREATE TABLE / schema-defined only (not option names, ajax actions, etc.)
  const ownedTables = tablesDetailed
    .filter(
      (t) =>
        t.defined ||
        (t.columns || []).some((c) => c.kind === 'column') ||
        t.source === 'wpdb_concat' ||
        (t.source && String(t.source).startsWith('create_'))
    )
    .map((t) => t.table);
  const tableRefs = tablesDetailed.map((t) => t.table);
  const tablesForRegistry = uniqueSorted(ownedTables);

  // REST dedupe
  const restMap = new Map();
  for (const r of restAll) {
    const k = `${r.namespace}|${r.endpoint}|${r.methods.join(',')}`;
    if (!restMap.has(k)) restMap.set(k, r);
  }
  const rest = [...restMap.values()];

  const security = securityPosture(phpFiles, contentsByFile);
  // Body-aware REST risk adjustments on security score
  security.rest_permission_critical = 0; // filled after rest built — placeholder
  security.rest_permission_unresolved = 0;
  const coupling = extractCoupling(slug, allText, allSlugs);
  const lifecycle = extractLifecycle(allText);
  lifecycle.uninstall_php = fs.existsSync(path.join(dir, 'uninstall.php'));

  const packageNs =
    (mainContent.match(/@package\s+([A-Za-z0-9_\\]+)/) || [])[1] ||
    (composer && composer.namespaces && composer.namespaces[0]) ||
    (classesAll[0] && classesAll[0].fqcn.split('\\').slice(0, -1).join('\\')) ||
    (oldEntry && oldEntry.namespace) ||
    null;

  const version =
    header.version ||
    (constantsAll.find((c) => /VERSION$/.test(c.name)) || {}).value ||
    (oldEntry && oldEntry.version) ||
    null;

  const description =
    header.description ||
    (composer && composer.description) ||
    (oldEntry && oldEntry.description) ||
    '';

  // dependencies: header + coupling + old
  const deps = new Set([...(oldEntry && oldEntry.dependencies) || [], ...coupling]);
  if (header.requires_plugins) {
    header.requires_plugins.split(/[,\s]+/).filter((d) => d.startsWith('apollo-')).forEach((d) => deps.add(d));
  }
  deps.delete(slug);

  const pagesLive = pagesFromRewrites(rewritesAll);
  const pages =
    pagesLive.length > 0
      ? pagesLive
      : (oldEntry && oldEntry.pages) || [];

  // REST for registry shape (includes permission body analysis)
  const restRegistry = rest.map((r) => {
    const a = (r.permission && r.permission.analysis) || {};
    return {
      endpoint: r.endpoint,
      methods: r.methods,
      description:
        ((oldEntry && oldEntry.rest) || []).find((o) => o.endpoint === r.endpoint)?.description ||
        `Live ${r.namespace} (${r.permission.class})`,
      auth: r.permission.class,
      permission_risk: r.permission.risk,
      permission_method: r.permission.method || null,
      permission_binding: a.binding || null,
      permission_summary: a.summary || null,
      permission_capabilities: a.capabilities || [],
      permission_resolved: a.resolved !== false,
      permission_body_preview: a.body_preview || null,
      permission_delegated_to: a.delegated_to || null,
      intentional_public: !!a.intentional_public,
      callback: r.callback,
      rest_namespace: r.namespace,
      file: r.file,
      _source: 'deep_live_scan_perm_body',
    };
  });

  const metaStructured = {
    post: Object.fromEntries(
      uniqueSorted([...metaAgg.post]).map((k) => [k, { _source: 'deep_scan' }])
    ),
    user: Object.fromEntries(
      uniqueSorted([...metaAgg.user]).map((k) => [k, { _source: 'deep_scan' }])
    ),
    term: Object.fromEntries(
      uniqueSorted([...metaAgg.term]).map((k) => [k, { _source: 'deep_scan' }])
    ),
  };

  // Security REST summary (body-aware)
  const restRisk = { CRITICAL: 0, HIGH: 0, MEDIUM: 0, LOW: 0, REVIEW: 0 };
  const effectiveClassCounts = {};
  let permResolved = 0;
  let permUnresolved = 0;
  let intentionalPublic = 0;
  let writeCritical = 0;
  for (const r of rest) {
    const risk = r.permission.risk || 'REVIEW';
    restRisk[risk] = (restRisk[risk] || 0) + 1;
    const ec = r.permission.class || 'UNKNOWN';
    effectiveClassCounts[ec] = (effectiveClassCounts[ec] || 0) + 1;
    const a = r.permission.analysis || {};
    if (a.resolved) permResolved++;
    else permUnresolved++;
    if (a.intentional_public) intentionalPublic++;
    if (risk === 'CRITICAL') writeCritical++;
  }

  // Escalate plugin security score from permission-body findings
  security.rest_permission_critical = writeCritical;
  security.rest_permission_unresolved = permUnresolved;
  security.rest_open_high = restRisk.HIGH || 0;
  security.rest_open_critical = restRisk.CRITICAL || 0;
  security.rest_intentional_public = intentionalPublic;
  if (writeCritical > 0) {
    security.risk_score = Math.min(100, security.risk_score + Math.min(40, writeCritical * 8));
  }
  if (permUnresolved > 0) {
    security.risk_score = Math.min(100, security.risk_score + Math.min(15, permUnresolved * 3));
  }
  // HIGH open non-intentional still counts (already partially via return_true_perm)
  const nonIntentionalOpen = rest.filter(
    (r) =>
      (r.permission.risk === 'HIGH' || r.permission.risk === 'CRITICAL') &&
      !(r.permission.analysis && r.permission.analysis.intentional_public)
  ).length;
  security.rest_open_non_intentional = nonIntentionalOpen;
  if (nonIntentionalOpen > 5) {
    security.risk_score = Math.min(100, security.risk_score + 10);
  }
  security.risk_band =
    security.risk_score >= 50 ? 'HIGH' : security.risk_score >= 25 ? 'MEDIUM' : 'LOW';

  const deep = {
    audited_at: new Date().toISOString(),
    audit_level: 'DEEP_STATIC_PHP_TREE_PERM_BODY',
    duration_ms: 0,
    path: dir,
    main_file: fs.existsSync(mainPhp) ? `${slug}.php` : null,
    plugin_header: header,
    composer: composer
      ? {
          name: composer.name,
          namespaces: composer.namespaces,
          require: composer.require,
        }
      : null,
    inventory: inv,
    php: {
      files: phpFiles.length,
      loc: phpLoc,
      classes: classesAll.length,
      interfaces: classesAll.filter((c) => c.kind === 'interface').length,
      traits: classesAll.filter((c) => c.kind === 'trait').length,
      public_apollo_functions: functionsAll.length,
    },
    architecture: {
      namespace: packageNs,
      classes: classesAll.slice(0, 200),
      public_functions: functionsAll.slice(0, 100),
    },
    rest: {
      count: rest.length,
      routes: rest,
      risk_breakdown: restRisk,
      effective_class_counts: effectiveClassCounts,
      permission_body_resolved: permResolved,
      permission_body_unresolved: permUnresolved,
      intentional_public_count: intentionalPublic,
      write_critical_public: writeCritical,
      open_routes: rest
        .filter((r) => r.permission.risk === 'HIGH' || r.permission.risk === 'CRITICAL')
        .map((r) => ({
          endpoint: r.endpoint,
          methods: r.methods,
          class: r.permission.class,
          risk: r.permission.risk,
          method: r.permission.method || null,
          summary: (r.permission.analysis && r.permission.analysis.summary) || null,
          binding: (r.permission.analysis && r.permission.analysis.binding) || null,
          body_preview: (r.permission.analysis && r.permission.analysis.body_preview) || null,
          capabilities: (r.permission.analysis && r.permission.analysis.capabilities) || [],
          delegated_to: (r.permission.analysis && r.permission.analysis.delegated_to) || null,
          intentional_public: !!(r.permission.analysis && r.permission.analysis.intentional_public),
          file: r.file,
        })),
      admin_cap_routes: rest
        .filter((r) => r.permission.class === 'ADMIN_CAPABILITY')
        .map((r) => r.endpoint),
      logged_in_routes: rest
        .filter((r) => /LOGGED_IN/.test(r.permission.class || ''))
        .map((r) => r.endpoint),
    },
    database: {
      tables_owned_or_defined: uniqueSorted(ownedTables),
      tables_referenced: uniqueSorted(tableRefs),
      tables_for_registry: tablesForRegistry,
      schemas_with_columns: tablesDetailed.filter((t) =>
        (t.columns || []).some((c) => c.kind === 'column')
      ).length,
      schemas: tablesDetailed.map((t) => ({
        table: t.table,
        column_count: (t.columns || []).filter((c) => c.kind === 'column').length,
        columns: (t.columns || [])
          .filter((c) => c.kind === 'column')
          .map((c) => `${c.name}:${c.type}`),
        indexes: (t.columns || []).filter((c) => c.kind === 'index').map((c) => c.def),
        source: t.source,
        ref_only: !!t.ref_only,
        defined: !!t.defined,
        owner_plugin_in_schema: t.owner_plugin_in_schema || null,
      })),
    },
    meta: {
      post_count: metaAgg.post.size,
      user_count: metaAgg.user.size,
      term_count: metaAgg.term.size,
      post_keys: uniqueSorted([...metaAgg.post]),
      user_keys: uniqueSorted([...metaAgg.user]),
      term_keys: uniqueSorted([...metaAgg.term]),
      registered_calls: metaAgg.registered.slice(0, 50),
    },
    routing: {
      rewrite_rules: rewritesAll,
      query_vars: uniqueSorted([...queryVars]),
      pages_derived: pagesLive,
    },
    cpts: [...cptsAll.values()],
    taxonomies: [...taxAll.values()],
    hooks: {
      fired_count: hooksAgg.fired.size,
      listened_count: hooksAgg.listened.size,
      apollo_fired: uniqueSorted([...hooksAgg.apollo_fired]),
      apollo_listened: uniqueSorted([...hooksAgg.apollo_listened]),
      critical_wp_listened: uniqueSorted(
        [...hooksAgg.listened].filter((h) =>
          /^(init|plugins_loaded|admin_init|rest_api_init|template_redirect|wp_enqueue_scripts|admin_menu|save_post|user_register|wp_login)/.test(
            h
          )
        )
      ),
    },
    ajax: {
      priv: uniqueSorted([...ajaxAgg.priv]),
      nopriv: uniqueSorted([...ajaxAgg.nopriv]),
    },
    cron: uniqueSorted([...cronAll]),
    shortcodes: uniqueSorted([...shortcodesAll]),
    admin_menus: menusAll,
    assets: {
      scripts: uniqueSorted([...assetsAgg.scripts]),
      styles: uniqueSorted([...assetsAgg.styles]),
    },
    options: uniqueSorted([...optionsAll]),
    constants: constantsAll.slice(0, 80),
    security,
    coupling: {
      depends_on: uniqueSorted([...deps]),
      signals: coupling,
    },
    lifecycle,
    delta_vs_old: {
      was_in_registry: Boolean(oldEntry),
      old_status: oldEntry ? oldEntry.status : null,
      old_version: oldEntry && oldEntry.version ? oldEntry.version : null,
      new_version: version,
      version_changed: Boolean(
        oldEntry && oldEntry.version && version && String(oldEntry.version) !== String(version)
      ),
      old_rest: oldEntry && oldEntry.rest ? oldEntry.rest.length : 0,
      live_rest: rest.length,
      old_pages: oldEntry && oldEntry.pages ? oldEntry.pages.length : 0,
      live_pages: pagesLive.length,
      new_plugin: !oldEntry,
    },
  };

  deep.duration_ms = Date.now() - t0;

  const entry = {
    ...(oldEntry || {}),
    slug,
    namespace: packageNs,
    priority: oldEntry && oldEntry.priority !== undefined ? oldEntry.priority : 50,
    status: 'IMPLEMENTED',
    version,
    description,
    dependencies: uniqueSorted([...deps]),
    provides:
      oldEntry && oldEntry.provides && oldEntry.provides.length
        ? oldEntry.provides
        : [slug.replace(/^apollo-/, ''), rest.length ? 'rest-api' : null, tablesDetailed.length ? 'db' : null].filter(Boolean),
    cpts: [...cptsAll.values()].map((c) => c.slug),
    taxonomies: [...taxAll.values()].map((t) => t.slug),
    tables: tablesForRegistry,
    meta: metaStructured,
    rest: restRegistry,
    pages,
    shortcodes: uniqueSorted([...shortcodesAll]).map((tag) => ({ tag, _source: 'deep_live_scan' })),
    ajax: {
      priv: uniqueSorted([...ajaxAgg.priv]),
      nopriv: uniqueSorted([...ajaxAgg.nopriv]),
    },
    cron: uniqueSorted([...cronAll]),
    hooks_fired: uniqueSorted([...hooksAgg.apollo_fired]).slice(0, 100),
    hooks_listened: uniqueSorted([...hooksAgg.apollo_listened]).slice(0, 100),
    admin_menus: menusAll,
    assets: deep.assets,
    options: deep.options,
    security: {
      risk_band: security.risk_band,
      risk_score: security.risk_score,
      abspath_coverage_pct: security.abspath_coverage_pct,
      rest_open_routes: (restRisk.HIGH || 0) + (restRisk.CRITICAL || 0),
      rest_critical_write_public: writeCritical,
      rest_open_non_intentional: nonIntentionalOpen,
      rest_intentional_public: intentionalPublic,
      permission_methods_resolved: permResolved,
      permission_methods_unresolved: permUnresolved,
      prepare_ratio: security.prepare_to_query_ratio,
    },
    database_schemas: deep.database.schemas,
    rewrite_rules: rewritesAll.map((r) => ({ pattern: r.pattern, query: r.query })),
    query_vars: uniqueSorted([...queryVars]),
    php_surface: {
      files: phpFiles.length,
      loc: phpLoc,
      classes: classesAll.length,
      functions: functionsAll.length,
    },
    _deep_audit: deep,
  };

  const report = {
    slug,
    status: 'DEEP_AUDITED',
    version,
    namespace: packageNs,
    php_files: phpFiles.length,
    php_loc: phpLoc,
    classes: classesAll.length,
    rest: rest.length,
    rest_open: (restRisk.HIGH || 0) + (restRisk.CRITICAL || 0),
    rest_critical: writeCritical,
    rest_open_non_intentional: nonIntentionalOpen,
    perm_resolved: permResolved,
    perm_unresolved: permUnresolved,
    tables: tablesForRegistry.length,
    tables_defined: uniqueSorted(ownedTables).length,
    table_schemas_with_columns: tablesDetailed.filter((t) =>
      (t.columns || []).some((c) => c.kind === 'column')
    ).length,
    meta_post: metaAgg.post.size,
    meta_user: metaAgg.user.size,
    rewrites: rewritesAll.length,
    pages: pagesLive.length,
    shortcodes: shortcodesAll.size,
    ajax_priv: ajaxAgg.priv.size,
    ajax_nopriv: ajaxAgg.nopriv.size,
    cron: cronAll.size,
    security_risk: security.risk_band,
    security_score: security.risk_score,
    abspath_pct: security.abspath_coverage_pct,
    deps: uniqueSorted([...deps]),
    new_plugin: !oldEntry,
    version_changed: deep.delta_vs_old.version_changed,
    old_version: deep.delta_vs_old.old_version,
    duration_ms: deep.duration_ms,
  };

  return { entry, report };
}

// ─── Summary / graph ────────────────────────────────────────────────────────

function buildCouplingGraph(registry) {
  const edges = [];
  for (const [slug, p] of Object.entries(registry.plugins || {})) {
    for (const d of p.dependencies || []) {
      edges.push({ from: slug, to: d });
    }
  }
  return edges;
}

function recomputeSummary(registry, reports) {
  const plugins = registry.plugins || {};
  const statuses = {};
  const cptSet = new Set();
  const taxSet = new Set();
  const tableSet = new Set();
  const metaSet = new Set();
  let rest = 0;
  let restOpen = 0;
  let pages = 0;
  let shortcodes = 0;
  let phpLoc = 0;
  let classes = 0;

  for (const p of Object.values(plugins)) {
    const st = p.status || 'UNKNOWN';
    statuses[st] = (statuses[st] || 0) + 1;
    (p.cpts || []).forEach((c) => cptSet.add(typeof c === 'string' ? c : c.slug));
    (p.taxonomies || []).forEach((t) => taxSet.add(typeof t === 'string' ? t : t.slug));
    (p.tables || []).forEach((t) => tableSet.add(typeof t === 'string' ? t : t.name || t));
    if (p.meta) {
      Object.keys(p.meta.post || {}).forEach((k) => metaSet.add(`post:${k}`));
      Object.keys(p.meta.user || {}).forEach((k) => metaSet.add(`user:${k}`));
      Object.keys(p.meta.term || {}).forEach((k) => metaSet.add(`term:${k}`));
    }
    rest += (p.rest || []).length;
    restOpen += (p.security && p.security.rest_open_routes) || 0;
    pages += (p.pages || []).length;
    shortcodes += (p.shortcodes || []).length;
    if (p.php_surface) {
      phpLoc += p.php_surface.loc || 0;
      classes += p.php_surface.classes || 0;
    }
  }

  const deepReports = reports.filter((r) => r.status === 'DEEP_AUDITED');
  const highRisk = deepReports.filter((r) => r.security_risk === 'HIGH');
  const medRisk = deepReports.filter((r) => r.security_risk === 'MEDIUM');

  return {
    total_plugins: Object.keys(plugins).length,
    total_cpts_unique: cptSet.size,
    total_taxonomies_unique: taxSet.size,
    total_tables_unique: tableSet.size,
    total_meta_keys_unique: metaSet.size,
    total_rest_endpoints: rest,
    total_rest_open_high_risk: restOpen,
    total_pages: pages,
    total_shortcodes: shortcodes,
    total_php_loc_audited: phpLoc,
    total_classes_audited: classes,
    status_breakdown: statuses,
    deep_audited: deepReports.length,
    security_high_risk_plugins: highRisk.map((r) => r.slug),
    security_medium_risk_plugins: medRisk.map((r) => r.slug),
    plugins_new_vs_old_registry: deepReports.filter((r) => r.new_plugin).map((r) => r.slug),
    plugins_version_changed: deepReports
      .filter((r) => r.version_changed)
      .map((r) => ({ slug: r.slug, from: r.old_version, to: r.version })),
    target_audit_count: TARGETS.length,
    completion_percentage: `${Math.round(
      (Object.values(plugins).filter((p) => p.status === 'IMPLEMENTED').length /
        Math.max(Object.keys(plugins).length, 1)) *
        100
    )}%`,
    registry_version: registry.$version,
    last_updated: today(),
    last_deep_investigation: today(),
    audit_mode: 'DEEP_TECHNICAL_STATIC_ANALYSIS',
    note: 'Deep PHP tree scan is SSOT for audited plugins. pages/meta/rest enriched from live code; narrative-only fields may remain from prior registry clone.',
  };
}

function writeMarkdownReport(registry, reports) {
  const s = registry.summary;
  const lines = [];
  lines.push(`# Apollo Deep Technical Audit`);
  lines.push(``);
  lines.push(`Generated: **${today()}**`);
  lines.push(`Mode: **DEEP_TECHNICAL_STATIC_ANALYSIS**`);
  lines.push(``);
  lines.push(`## Ecosystem totals`);
  lines.push(``);
  lines.push(`| Metric | Value |`);
  lines.push(`|--------|------:|`);
  lines.push(`| Plugins in registry | ${s.total_plugins} |`);
  lines.push(`| Deep-audited targets | ${s.deep_audited} |`);
  lines.push(`| PHP LOC (audited) | ${s.total_php_loc_audited} |`);
  lines.push(`| Classes | ${s.total_classes_audited} |`);
  lines.push(`| REST endpoints (live) | ${s.total_rest_endpoints} |`);
  lines.push(`| Open REST (HIGH/CRITICAL perm) | ${s.total_rest_open_high_risk} |`);
  lines.push(`| Unique CPTs | ${s.total_cpts_unique} |`);
  lines.push(`| Unique taxonomies | ${s.total_taxonomies_unique} |`);
  lines.push(`| Unique tables | ${s.total_tables_unique} |`);
  lines.push(`| Unique meta keys | ${s.total_meta_keys_unique} |`);
  lines.push(`| Pages/rewrites | ${s.total_pages} |`);
  lines.push(`| Shortcodes | ${s.total_shortcodes} |`);
  lines.push(``);
  lines.push(`## New plugins (not in old registry)`);
  lines.push(``);
  for (const p of s.plugins_new_vs_old_registry || []) lines.push(`- \`${p}\``);
  lines.push(``);
  lines.push(`## Version changes`);
  lines.push(``);
  for (const v of s.plugins_version_changed || []) {
    lines.push(`- \`${v.slug}\`: ${v.from} → **${v.to}**`);
  }
  lines.push(``);
  lines.push(`## Security risk bands`);
  lines.push(``);
  lines.push(`### HIGH`);
  for (const p of s.security_high_risk_plugins || []) lines.push(`- \`${p}\``);
  lines.push(``);
  lines.push(`### MEDIUM`);
  for (const p of s.security_medium_risk_plugins || []) lines.push(`- \`${p}\``);
  lines.push(``);
  lines.push(`## Per-plugin deep scores`);
  lines.push(``);
  lines.push(
    `| Plugin | Ver | PHP | LOC | Classes | REST | Open | Tables | MetaP/U | Rewrites | Risk | Score |`
  );
  lines.push(`|--------|-----|----:|----:|--------:|-----:|-----:|-------:|--------:|---------:|------|------:|`);
  const sorted = [...reports].sort((a, b) => b.security_score - a.security_score);
  for (const r of sorted) {
    if (r.status !== 'DEEP_AUDITED') continue;
    lines.push(
      `| ${r.slug} | ${r.version || '?'} | ${r.php_files} | ${r.php_loc} | ${r.classes} | ${r.rest} | ${r.rest_open} | ${r.tables} | ${r.meta_post}/${r.meta_user} | ${r.rewrites} | ${r.security_risk} | ${r.security_score} |`
    );
  }
  lines.push(``);
  lines.push(`## Open REST routes (HIGH/CRITICAL) — permission body analysis`);
  lines.push(``);
  lines.push(
    `Methods are resolved from PHP source. \`ADMIN_CAPABILITY\` means body calls \`current_user_can('manage_options')\` etc. \`RETURNS_TRUE_UNCONDITIONALLY\` / \`__return_true\` means no auth. WRITE+public = CRITICAL.`
  );
  lines.push(``);
  for (const slug of TARGETS) {
    const p = registry.plugins[slug];
    if (!p || !p._deep_audit) continue;
    const open = p._deep_audit.rest.open_routes || [];
    if (!open.length) continue;
    lines.push(`### ${slug}`);
    for (const r of open) {
      const intent = r.intentional_public ? ' · intentional?' : '';
      const body = r.body_preview ? ` · \`${r.body_preview.slice(0, 100)}\`` : '';
      const bind = r.binding || r.method || r.class;
      lines.push(
        `- **${r.risk}** \`${(r.methods || []).join(',')}\` \`${r.endpoint}\` — **${r.class}** via \`${bind}\`${intent}`
      );
      if (r.summary) lines.push(`  - ${r.summary}`);
      if (r.capabilities && r.capabilities.length) {
        lines.push(`  - caps: ${r.capabilities.join(', ')}`);
      }
      if (r.delegated_to) {
        lines.push(
          `  - delegates → \`${r.delegated_to.method}\`: ${r.delegated_to.summary} (${r.delegated_to.risk})`
        );
      }
      lines.push(`  - file: \`${r.file}\`${body}`);
    }
    lines.push(``);
  }
  lines.push(`## Permission effective classes (all routes)`);
  lines.push(``);
  for (const slug of TARGETS) {
    const p = registry.plugins[slug];
    if (!p || !p._deep_audit || !p._deep_audit.rest) continue;
    const counts = p._deep_audit.rest.effective_class_counts || {};
    if (!Object.keys(counts).length) continue;
    const parts = Object.entries(counts)
      .sort((a, b) => b[1] - a[1])
      .map(([k, v]) => `${k}:${v}`)
      .join(', ');
    lines.push(`- **${slug}**: ${parts}`);
  }
  lines.push(``);
  lines.push(`## Coupling edges (depends_on)`);
  lines.push(``);
  const edges = buildCouplingGraph({ plugins: Object.fromEntries(TARGETS.map((s) => [s, registry.plugins[s]])) });
  for (const e of edges.sort((a, b) => a.from.localeCompare(b.from))) {
    lines.push(`- \`${e.from}\` → \`${e.to}\``);
  }
  lines.push(``);
  lines.push(`---`);
  lines.push(
    `Canonical: \`apollo-registry.json\` · Mirror: \`apollo-registry-2.json\` · Report: \`apollo-registry-deep-report.json\``
  );
  return lines.join('\n');
}

// ─── Main ───────────────────────────────────────────────────────────────────

function main() {
  if (!fs.existsSync(SRC_REGISTRY)) {
    console.error('Missing', SRC_REGISTRY);
    process.exit(1);
  }

  const registry = JSON.parse(fs.readFileSync(SRC_REGISTRY, 'utf8'));
  const oldPlugins = registry.plugins || {};
  const reports = [];

  console.log(`DEEP auditing ${TARGETS.length} plugins...\n`);
  const tAll = Date.now();

  for (const slug of TARGETS) {
    process.stdout.write(`  ◆ ${slug} ... `);
    const { entry, report } = auditPluginDeep(slug, oldPlugins[slug], TARGETS);
    registry.plugins[slug] = entry;
    reports.push(report);
    console.log(
      `${report.status} v${report.version || '?'} rest=${report.rest} tbl=${report.tables} meta=${report.meta_post + report.meta_user} risk=${report.security_risk}(${report.security_score}) ${report.duration_ms}ms`
    );
  }

  registry.$generated = today();
  registry.$description = `Apollo Ecosystem — LIVE DEEP AUDIT (${today()}) after P1/P2/P3 remediations — ${TARGETS.length} plugins. Permission-body REST analysis + filesystem SSOT. Canonical: plugins/_inventory/apollo-registry.json (mirror: apollo-registry-2.json).`;
  registry.$audit = {
    ...(registry.$audit || {}),
    last_full_technical_audit: today(),
    last_security_remediation: today(),
    audit_script: 'plugins/_inventory/deep-audit-registry2.js',
    audit_level: 'DEEP',
    audit_targets: TARGETS.length,
    audit_method:
      'Deep PHP tree + REST permission_callback BODY resolution; CREATE TABLE schemas; meta; rewrites; security; coupling. Post P1/P2/P3 remediations baked into live code.',
    status: 'LIVE_SSOT_AFTER_P1_P2_P3',
    output_policy: 'apollo-registry.json canonical + apollo-registry-2.json mirror',
    remediations: {
      P1: {
        date: today(),
        items: [
          'apollo-users: prepare_user_data never exposes email/phone publicly; assert_profile_visible on by-id/public/radar; radar privacy filters + list-safe payloads',
          'apollo-core: GET /pane-mode/status public response reduced to mode_active + plugin_active booleans only',
          'apollo-dashboard: GET /dashboard/widgets requires check_logged_in',
        ],
      },
      P2: {
        date: today(),
        items: [
          'ABSPATH guards: apollo-pane-engine, apollo-email, apollo-dj-sync, apollo-wow (and related stubs)',
          'SQL prepare/%i: apollo-docs DROP tables, apollo-mod uninstall, apollo-fav stats+uninstall, apollo-hub uninstall deletes/transients',
        ],
      },
      P3: {
        date: today(),
        items: [
          'apollo-templates/examples/user-radar-examples.php: ABSPATH + APOLLO_LOAD_TEMPLATE_EXAMPLES gate (no production REST/cron registration)',
          'deep audit skips examples/ and phpcs-logs directories',
        ],
      },
    },
  };

  registry.summary = recomputeSummary(registry, reports);
  registry.summary.remediation_pass = 'P1+P2+P3';
  registry.summary.registry_canonical = 'plugins/_inventory/apollo-registry.json';

  registry.$deep_audit = {
    generated: today(),
    duration_ms: Date.now() - tAll,
    targets: TARGETS,
    reports: reports.sort((a, b) => a.slug.localeCompare(b.slug)),
    coupling_edges: buildCouplingGraph(registry).filter((e) => TARGETS.includes(e.from)),
    high_risk_plugins: reports.filter((r) => r.security_risk === 'HIGH'),
    open_rest_total: reports.reduce((a, r) => a + (r.rest_open || 0), 0),
    remediations_applied: ['P1', 'P2', 'P3'],
  };

  // Annotate remediated plugins with security notes (non-destructive)
  const remediated = {
    'apollo-users': {
      note: 'P1: public profile payloads strip email/phone; privacy gate on all public reads',
      public_email: false,
    },
    'apollo-core': {
      note: 'P1: pane-mode/status public payload minimized (booleans only)',
    },
    'apollo-dashboard': {
      note: 'P1: /dashboard/widgets requires login',
    },
    'apollo-docs': { note: 'P2: uninstall/schema DROP uses $wpdb->prepare %i' },
    'apollo-fav': { note: 'P2: stats queries prepared; uninstall DROP prepared' },
    'apollo-hub': { note: 'P2: uninstall deletes prepared' },
    'apollo-mod': { note: 'P2: uninstall DROP/delete prepared' },
    'apollo-pane-engine': { note: 'P2: ABSPATH coverage complete' },
    'apollo-email': { note: 'P2: ABSPATH guards added; declare order fixed' },
    'apollo-dj-sync': { note: 'P2: ABSPATH on uninstall' },
    'apollo-wow': { note: 'P2: ABSPATH on uninstall' },
    'apollo-templates': {
      note: 'P3: examples gated by APOLLO_LOAD_TEMPLATE_EXAMPLES (default off)',
    },
  };
  for (const [slug, meta] of Object.entries(remediated)) {
    if (registry.plugins[slug]) {
      registry.plugins[slug].security_remediation = {
        ...(registry.plugins[slug].security_remediation || {}),
        ...meta,
        last_updated: today(),
      };
    }
  }

  // quick_lookup refresh
  const cpts = new Set();
  const tax = new Set();
  const tables = new Set();
  const restPref = new Set();
  for (const p of Object.values(registry.plugins)) {
    (p.cpts || []).forEach((c) => cpts.add(typeof c === 'string' ? c : c.slug));
    (p.taxonomies || []).forEach((t) => tax.add(typeof t === 'string' ? t : t.slug));
    (p.tables || []).forEach((t) => tables.add(typeof t === 'string' ? t : t.name));
    (p.rest || []).forEach((r) => {
      const seg = String(r.endpoint || '')
        .split('/')
        .filter(Boolean)[0];
      if (seg) restPref.add(seg);
    });
  }
  registry.quick_lookup = {
    ...(registry.quick_lookup || {}),
    all_cpt_slugs: [...cpts].sort(),
    all_taxonomy_slugs: [...tax].sort(),
    all_table_names: [...tables].sort(),
    all_rest_prefixes: [...restPref].sort(),
  };

  const jsonOut = JSON.stringify(registry, null, 4);
  fs.writeFileSync(OUT_REGISTRY, jsonOut, 'utf8');
  fs.writeFileSync(OUT_REGISTRY_MIRROR, jsonOut, 'utf8');
  fs.writeFileSync(
    OUT_REPORT,
    JSON.stringify(
      {
        generated: today(),
        summary: registry.summary,
        remediations: registry.$audit.remediations,
        reports: registry.$deep_audit.reports,
        coupling: registry.$deep_audit.coupling_edges,
        high_risk: registry.$deep_audit.high_risk_plugins,
      },
      null,
      2
    ),
    'utf8'
  );
  fs.writeFileSync(OUT_MD, writeMarkdownReport(registry, reports), 'utf8');

  console.log('\n════════ DEEP AUDIT COMPLETE ════════');
  console.log('Canonical registry:', OUT_REGISTRY);
  console.log('Mirror:', OUT_REGISTRY_MIRROR);
  console.log('JSON report:', OUT_REPORT);
  console.log('Markdown:', OUT_MD);
  console.log('Duration:', Date.now() - tAll, 'ms');
  console.log(JSON.stringify(registry.summary, null, 2));
}

main();
