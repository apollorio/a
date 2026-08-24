/**
 * apollo-guard · C-series — contract rules (plan-003 · P1)
 *
 * One scanner. Do not invent a second contract-test.mjs.
 *
 *   C01  hook arity          CRITICAL — listener needs more args than dispatcher
 *   C02  cross-plugin call   HIGH     — apollo_*() across plugins, unguarded
 *   C03  selector ownership  MEDIUM   — duplicate; CRITICAL for .ax-* (DS)
 *   C04  registry ⇄ routes   MEDIUM   — chapter lists a front route never seen live
 *
 * Severity honesty: if we cannot prove it cleanly, it stays MEDIUM.
 *
 * @owned-by plan-003 P1
 * @version 1.0.0
 */

import fs from 'node:fs';
import path from 'node:path';

const HOOK_RE = /['"](apollo\/[^'"]+)['"]/;
const FN_DEF_RE = /function\s+(apollo_[A-Za-z0-9_]+)\s*\(([^)]*)\)/g;
const FN_CALL_RE = /\b(apollo_[A-Za-z0-9_]+)\s*\(/g;

function arityOf(args) {
  if (!args || !String(args).trim()) return 0;
  let n = 1;
  let depth = 0;
  let quote = null;
  let esc = false;
  for (let i = 0; i < args.length; i++) {
    const c = args[i];
    if (quote) {
      if (esc) { esc = false; continue; }
      if (c === '\\') { esc = true; continue; }
      if (c === quote) quote = null;
      continue;
    }
    if (c === "'" || c === '"' || c === '`') { quote = c; continue; }
    if (c === '(' || c === '[' || c === '{') { depth++; continue; }
    if (c === ')' || c === ']' || c === '}') { depth--; continue; }
    if (c === ',' && depth === 0) n++;
  }
  return n;
}

function requiredParams(sig) {
  if (!sig || !String(sig).trim()) return 0;
  return sig.split(',')
    .map((p) => p.trim())
    .filter(Boolean)
    .filter((p) => !p.includes('=') && !p.startsWith('...'))
    .length;
}

function callArgs(text, openParenIdx) {
  let depth = 0;
  let quote = null;
  let esc = false;
  for (let i = openParenIdx; i < text.length; i++) {
    const c = text[i];
    if (quote) {
      if (esc) { esc = false; continue; }
      if (c === '\\') { esc = true; continue; }
      if (c === quote) quote = null;
      continue;
    }
    if (c === "'" || c === '"' || c === '`') { quote = c; continue; }
    if (c === '(') { depth++; continue; }
    if (c === ')') {
      depth--;
      if (depth === 0) return text.slice(openParenIdx + 1, i);
    }
  }
  return '';
}

function lineOf(text, idx) {
  return text.slice(0, idx).split('\n').length;
}

function splitTopLevel(args) {
  const parts = [];
  let depth = 0;
  let quote = null;
  let esc = false;
  let cur = '';
  for (let i = 0; i < args.length; i++) {
    const c = args[i];
    if (quote) {
      cur += c;
      if (esc) { esc = false; continue; }
      if (c === '\\') { esc = true; continue; }
      if (c === quote) quote = null;
      continue;
    }
    if (c === "'" || c === '"' || c === '`') { quote = c; cur += c; continue; }
    if (c === '(' || c === '[') { depth++; cur += c; continue; }
    if (c === ')' || c === ']') { depth--; cur += c; continue; }
    if (c === ',' && depth === 0) { parts.push(cur.trim()); cur = ''; continue; }
    cur += c;
  }
  if (cur.trim()) parts.push(cur.trim());
  return parts;
}

function cssRules(css) {
  css = String(css).replace(/\/\*[\s\S]*?\*\//g, (m) => m.replace(/[^\n]/g, ' '));
  const out = [];
  let depth = 0;
  let buf = '';
  let selBuf = '';
  let line = 1;
  let selLine = 1;
  for (let i = 0; i < css.length; i++) {
    const c = css[i];
    if (c === '\n') line++;
    if (c === '{') {
      if (depth === 0) selLine = line;
      depth++;
      if (depth === 1) { buf = ''; continue; }
    }
    if (c === '}') {
      depth--;
      if (depth === 0) {
        const sel = selBuf.trim();
        if (sel && !sel.startsWith('@')) out.push({ selector: sel, line: selLine });
        else if (/^@(media|supports)/.test(sel)) {
          for (const r of cssRules(buf)) out.push(r);
        }
        selBuf = '';
        buf = '';
        continue;
      }
    }
    if (depth === 0) selBuf += c;
    else buf += c;
  }
  return out;
}

function extractCssChunks(rel, text) {
  const chunks = [];
  if (/\.css$/i.test(rel)) chunks.push(text);
  for (const m of text.matchAll(/<style[^>]*>([\s\S]*?)<\/style>/gi)) chunks.push(m[1]);
  for (const m of text.matchAll(/<<<['"]?CSS['"]?\s*\n([\s\S]*?)\nCSS\s*;/g)) chunks.push(m[1]);
  return chunks;
}

function isDsSelector(sel) {
  return /(^|[^\w-])\.ax-[\w-]+/i.test(sel);
}

/**
 * @param {(r: object) => void} rule
 */
export function installCRules(rule) {
  rule({
    id: 'C01', sev: 'CRITICAL', kind: 'tree',
    title: 'Hook arity mismatch — listener declares more params than dispatcher sends',
    source: 'plan-003 P1-1 · the publish-fatal class',
    fix: 'Lower the callback required params, raise do_action arity, or set accepted_args to match.',
    run: ({ files, read }) => {
      const dispatched = new Map();
      const listeners = [];
      const signatures = new Map();

      for (const f of files) {
        if (!f.rel.endsWith('.php')) continue;
        const text = read(f);

        for (const m of text.matchAll(FN_DEF_RE)) {
          signatures.set(m[1], {
            req: requiredParams(m[2]),
            rel: f.rel,
            line: lineOf(text, m.index),
          });
        }

        for (const m of text.matchAll(/\bdo_action(?:_ref_array)?\s*\(/g)) {
          const open = text.indexOf('(', m.index);
          const args = callArgs(text, open);
          const hm = args.match(HOOK_RE);
          if (!hm) continue;
          const arity = Math.max(0, arityOf(args) - 1);
          if (!dispatched.has(hm[1])) dispatched.set(hm[1], []);
          dispatched.get(hm[1]).push({ arity, rel: f.rel, line: lineOf(text, m.index) });
        }

        for (const m of text.matchAll(/\badd_(?:action|filter)\s*\(/g)) {
          const open = text.indexOf('(', m.index);
          const args = callArgs(text, open);
          const hm = args.match(HOOK_RE);
          if (!hm) continue;
          const parts = splitTopLevel(args);
          const cb = (parts[1] || '').replace(/^['"]|['"]$/g, '');
          const accepted = parts[3] ? parseInt(parts[3], 10) : 1;
          listeners.push({
            hook: hm[1],
            cb,
            accepted: Number.isFinite(accepted) ? accepted : 1,
            rel: f.rel,
            line: lineOf(text, m.index),
          });
        }
      }

      const out = [];
      for (const L of listeners) {
        if (!/^apollo_[A-Za-z0-9_]+$/.test(L.cb)) continue;
        const sig = signatures.get(L.cb);
        if (!sig) continue;
        const fires = dispatched.get(L.hook) || [];
        if (!fires.length) continue;
        const maxSent = Math.max(...fires.map((d) => d.arity));
        if (sig.req > maxSent) {
          const sample = fires.find((d) => d.arity === maxSent) || fires[0];
          out.push({
            rel: L.rel,
            line: L.line,
            msg: `${L.hook}: ${L.cb} needs ${sig.req} args, dispatcher sends ${maxSent}`,
            evidence: `listener ${L.rel}:${L.line} · dispatcher ${sample.rel}:${sample.line} · signature ${sig.rel}:${sig.line}`,
          });
        }
      }
      return out;
    },
  });

  rule({
    id: 'C02', sev: 'HIGH', kind: 'tree',
    title: 'Cross-plugin apollo_*() call without function_exists() or registry provide',
    source: 'plan-003 P1-2 · load order is not a contract',
    fix: 'Wrap the call in function_exists() and degrade (plain link / empty string / skip panel).',
    run: ({ root, files, read }) => {
      const definedIn = new Map();
      const provides = new Set();

      const regDir = path.join(root, '_inventory', 'registry', '09-plugins');
      if (fs.existsSync(regDir)) {
        for (const name of fs.readdirSync(regDir).filter((x) => x.endsWith('.json'))) {
          try {
            const j = JSON.parse(fs.readFileSync(path.join(regDir, name), 'utf8'));
            for (const key of ['helpers', 'functions', 'exports']) {
              const list = j[key];
              if (!Array.isArray(list)) continue;
              for (const x of list) {
                if (typeof x === 'string' && x.startsWith('apollo_')) provides.add(x);
                if (x && typeof x === 'object' && typeof x.name === 'string') provides.add(x.name);
              }
            }
          } catch { /* ignore corrupt chapter */ }
        }
      }

      for (const f of files) {
        if (!f.rel.endsWith('.php')) continue;
        const text = read(f);
        for (const m of text.matchAll(FN_DEF_RE)) {
          definedIn.set(m[1], f.plugin);
        }
      }

      const out = [];
      const seen = new Set();
      for (const f of files) {
        if (!f.rel.endsWith('.php')) continue;
        const text = read(f);
        const lines = text.split('\n');
        for (const m of text.matchAll(FN_CALL_RE)) {
          const fn = m[1];
          const before = text.slice(Math.max(0, m.index - 24), m.index);
          if (/function\s+$/.test(before)) continue;
          const owner = definedIn.get(fn);
          if (!owner || owner === f.plugin) continue;
          if (provides.has(fn)) continue;
          const ln = lineOf(text, m.index);
          const window = lines.slice(Math.max(0, ln - 3), ln + 2).join('\n');
          if (new RegExp(`function_exists\\s*\\(\\s*['"]${fn}['"]`).test(window)) continue;
          const key = `${f.rel}|${fn}|${ln}`;
          if (seen.has(key)) continue;
          seen.add(key);
          out.push({
            rel: f.rel,
            line: ln,
            msg: `${fn}() defined in ${owner}, called from ${f.plugin} unguarded`,
            evidence: (lines[ln - 1] || '').trim().slice(0, 140),
          });
        }
      }
      return out;
    },
  });

  rule({
    id: 'C03', sev: 'MEDIUM', kind: 'tree',
    title: 'Selector declared by two owners',
    source: 'plan-003 P1-3 · lift of the /casa harness duplicate-selector detector',
    fix: 'One cell owns the selector. Delete the other declaration in the same commit. .ax-* belongs to the design-system cells (apollo-templates / apollo-core).',
    run: ({ files, read }) => {
      // Known DS owners — a second .ax-* declaration *inside* these is still a
      // fork, but a copy that merely consumes the shell is expected. CRITICAL
      // only when a non-DS plugin re-declares a DS selector.
      const DS_OWNERS = new Set(['apollo-templates', 'apollo-core']);

      const owners = new Map();
      for (const f of files) {
        if (!/\.(php|css)$/i.test(f.rel)) continue;
        if (/\/(vendor|node_modules|dist|build|_sandbox|phpcs-logs)\//.test(f.rel)) continue;
        // Minified / vendored admin skins drown the signal.
        if (/\/assets\/.*\.(min\.)?css$/i.test(f.rel) && /modera|vendor|third/i.test(f.rel)) continue;
        const text = read(f);
        for (const chunk of extractCssChunks(f.rel, text)) {
          for (const r of cssRules(chunk)) {
            for (const sel of r.selector.split(',').map((s) => s.trim()).filter(Boolean)) {
              if (sel.length < 2) continue;
              if (!owners.has(sel)) owners.set(sel, []);
              owners.get(sel).push({ rel: f.rel, line: r.line, plugin: f.plugin });
            }
          }
        }
      }
      const out = [];
      for (const [sel, where] of owners) {
        // Same plugin, many cells — cascade is allowed. Cardinal sin is
        // two *plugins* claiming the same selector.
        const byPlugin = new Map();
        for (const w of where) {
          if (!byPlugin.has(w.plugin)) byPlugin.set(w.plugin, w);
        }
        if (byPlugin.size < 2) continue;
        const list = [...byPlugin.values()];
        const ds = isDsSelector(sel);
        const nonDs = list.filter((x) => !DS_OWNERS.has(x.plugin));
        // DS selector: only fire when a non-DS plugin re-declares it.
        if (ds && nonDs.length === 0) continue;
        const first = ds ? (nonDs[0] || list[0]) : list[0];
        const others = list.filter((x) => x !== first).map((x) => `${x.plugin}:${x.rel}:${x.line}`).join(', ');
        out.push({
          rel: first.rel,
          line: first.line,
          // Plan risk note: noisy CRITICAL gets the whole gate ignored.
          // DS forks from non-DS plugins stay CRITICAL; everything else MEDIUM.
          sev: (ds && nonDs.length > 0) ? 'CRITICAL' : 'MEDIUM',
          msg: ds
            ? `DS selector ${sel} re-declared outside the design system`
            : `${sel} declared across ${byPlugin.size} plugins`,
          evidence: others,
        });
      }
      return out;
    },
  });

  rule({
    id: 'C04', sev: 'MEDIUM', kind: 'tree',
    title: 'Registry route has no live evidence in the plugin tree',
    source: 'plan-003 P1-4 · the class that left /feed ambiguous for months',
    fix: 'Either teach the live route_map()/rewrites about this path, or delete it from the 09-plugins chapter.',
    run: ({ root, files, read }) => {
      const regDir = path.join(root, '_inventory', 'registry', '09-plugins');
      if (!fs.existsSync(regDir)) return [];

      const live = new Set();
      for (const f of files) {
        if (!f.rel.endsWith('.php')) continue;
        const text = read(f);
        for (const m of text.matchAll(/['"]((?:[a-z0-9_-]+\/)+[a-z0-9_-]+|[a-z0-9_-]{3,})['"]/gi)) {
          const s = m[1].replace(/^\/+|\/+$/g, '');
          if (/^(eventos|portal|casa|feed|mapa|hub|acesso|anuncios|comunas|locais|djs)/i.test(s) || s.includes('/')) {
            live.add(s.toLowerCase());
          }
        }
        for (const m of text.matchAll(/add_rewrite_rule\s*\(\s*['"]([^'"]+)/g)) {
          const cleaned = m[1]
            .replace(/^\^|\$$/g, '')
            .replace(/\(\?[^)]*\)/g, '')
            .replace(/\/\?/g, '/')
            .replace(/[^a-z0-9_\/-]+/gi, '')
            .replace(/^\/+|\/+$/g, '')
            .toLowerCase();
          if (cleaned) live.add(cleaned);
        }
      }

      const out = [];
      for (const name of fs.readdirSync(regDir).filter((x) => x.endsWith('.json'))) {
        let j;
        try {
          j = JSON.parse(fs.readFileSync(path.join(regDir, name), 'utf8'));
        } catch {
          continue;
        }
        const routes = [];
        if (Array.isArray(j.routes)) {
          for (const r of j.routes) {
            if (typeof r === 'string') routes.push(r);
            else if (r && typeof r.route === 'string') routes.push(r.route);
            else if (r && typeof r.slug === 'string' && (r.rewrite || r.query || r.template)) routes.push(r.slug);
          }
        } else {
          const walk = (node, depth = 0) => {
            if (!node || typeof node !== 'object' || depth > 6) return;
            if (Array.isArray(node)) { node.forEach((x) => walk(x, depth + 1)); return; }
            if (typeof node.route === 'string') routes.push(node.route);
            for (const v of Object.values(node)) walk(v, depth + 1);
          };
          walk(j.routing || j.pages || {});
        }

        const seen = new Set();
        for (const raw of routes) {
          const route = String(raw).replace(/^\/+|\/+$/g, '').toLowerCase();
          if (!route || route.length < 2 || seen.has(route)) continue;
          seen.add(route);
          if (live.has(route)) continue;
          const hit = [...live].some((l) => l === route || l.startsWith(`${route}/`) || route.startsWith(`${l}/`));
          if (hit) continue;
          out.push({
            rel: `_inventory/registry/09-plugins/${name}`,
            line: 1,
            msg: `route "${route}" claimed, no live evidence`,
            evidence: `slug chapter ${j.slug || name}`,
          });
        }
      }
      return out;
    },
  });
}
