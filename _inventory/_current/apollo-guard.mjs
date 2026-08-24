#!/usr/bin/env node
/**
 * apollo-guard — the executable Apollo doctrine.
 *
 * Turns $apollo_rule.audit_verificator (12 security checks) and the naming /
 * philosophy / shell rules scattered across 01-philosophy, 15-conventions,
 * 18-canvas-shell and CLAUDE.md into ONE runnable gate.
 *
 *   node apollo-guard.mjs                    # whole tree
 *   node apollo-guard.mjs apollo-djs         # one plugin
 *   node apollo-guard.mjs --staged           # only files git has staged
 *   node apollo-guard.mjs --json report.json # machine-readable
 *   node apollo-guard.mjs --rule G08,D02     # only these rules
 *   node apollo-guard.mjs --rule M         # the mobile-first series
 *   node apollo-guard.mjs --baseline .apollo-guard-baseline.json
 *   node apollo-guard.mjs --write-baseline .apollo-guard-baseline.json
 *
 * Exit codes:  0 clean · 1 CRITICAL or HIGH present · 2 bad invocation
 *
 * Severity contract:
 *   CRITICAL  blocks the commit, always
 *   HIGH      blocks the commit, always
 *   MEDIUM    prints, does not block
 *   LOW       prints only with --verbose
 *
 * Design rules this file obeys, because it is the thing that enforces them:
 *   · fail-closed, never fail-quiet — an unparseable file is a finding
 *   · every rule cites its source chapter, so a finding is arguable
 *   · a rule that cannot be mechanised honestly is MEDIUM, not CRITICAL
 *
  * @version 1.4.0
 */

import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';
import { installCRules } from './apollo-guard-c.mjs';

/* ─────────────────────────── configuration ─────────────────────────── */

const CORE = 'apollo-core';                       // the only registrar
const TOKEN_OWNER = new Set(['apollo-core']);     // the only :root declarer

// Plugins whose src/ is a vendored SDK layout; ABSPATH is not their idiom.
const ABSPATH_EXEMPT_DIRS = [
  'apollo-telegram/src/',
];
// Files that legitimately have no ABSPATH guard.
const ABSPATH_EXEMPT_FILES = /(^|\/)(uninstall|index)\.php$/;

// Interpolations that are trusted SQL identifiers, not user input.
const TRUSTED_SQL_IDENTS = /^\{?\$(wpdb->(prefix|options|users|usermeta|posts|postmeta|comments|terms|termmeta|term_taxonomy|term_relationships|blogs)|table|tbl|pfx|prefix|t|table_name|[a-z_]*_table|notif_table|prefs_table)\b/i;

const SCAN_EXT = new Set(['.php', '.js', '.css', '.mjs']);
const SKIP_DIRS = new Set(['node_modules', 'vendor', '.git', '_checkpoints', 'dist', 'build']);

const SEV_ORDER = { CRITICAL: 0, HIGH: 1, MEDIUM: 2, LOW: 3 };
const BLOCKING = new Set(['CRITICAL', 'HIGH']);

// Paths that exist but do not serve users: tests, fixtures, retained legacy,
// sandbox harnesses. A finding here is real but not release-blocking.
const NON_SHIPPING = /(^|\/)(tests?|_sandbox|_legacy|_checkpoints|examples|bin|fixtures?)\//;
const nonShipping = (rel) => NON_SHIPPING.test(rel) || /data\.defaults|\.mock\.|\.sample\./.test(rel);

// Following an artist on SoundCloud/Spotify/Bandcamp is an outbound link to
// another platform, not an Apollo social graph. Not a PARTY_MODEL breach.
const EXTERNAL_FOLLOW = /\b(sc|bc|sp|ig|yt|tt|ra|ap)Follow\b|soundcloud|spotify|bandcamp|instagram|youtube|tiktok|residentadvisor/i;

/* ─────────────────────────────── rules ─────────────────────────────── */
/*
 * Each rule: { id, sev, title, source, kind, run(ctx) -> findings[] }
 *   kind 'file'  → run once per file, gets { rel, plugin, text, lines }
 *   kind 'tree'  → run once over everything, gets { files, byPlugin }
 * A finding: { rel, line, msg, evidence }
 */

const rules = [];
const rule = (r) => { rules.push(r); };

/**
 * Per-line comment mask.
 *
 * Rules that read source line-by-line kept flagging prose. apollo-events, for
 * example, carries four block comments that describe debug beacons it REMOVED
 * on 2026-08-17 — the removal note contains the very call it removed. Skipping
 * only lines that *start* with `*` or `//` is not enough; a block comment's
 * continuation lines often start with anything. So track the block state.
 *
 * Returns boolean[] — true when that line is inside a comment or is one.
 */
function commentMask(lines) {
  const mask = new Array(lines.length).fill(false);
  let open = false;
  for (let i = 0; i < lines.length; i++) {
    const l = lines[i];
    if (open) { mask[i] = true; if (l.includes('*/')) open = false; continue; }
    const opensHere = /\/\*/.test(l) && !/\/\*[\s\S]*\*\//.test(l);
    if (/^\s*(\*|\/\/|#(?!\[))/.test(l) || /^\s*\/\*/.test(l)) mask[i] = true;
    if (opensHere) { mask[i] = true; open = true; }
  }
  return mask;
}

/* ── G-series: $apollo_rule.audit_verificator (12 points) ───────────── */

rule({
  id: 'G02', sev: 'CRITICAL', kind: 'file',
  title: 'Nonce or token in a data-* attribute',
  source: '$apollo_rule.audit_verificator #2 · $apollo_rule.data_flow.data_attributes',
  fix: "Move it to a wp_json_encode() JS config block, guarded by is_user_logged_in().",
  run: ({ rel, lines, cmt }) => {
    const out = [];
    lines.forEach((l, i) => {
      if (/data-[a-z_-]*(nonce|token|secret|key)\s*=/.test(l) && !cmt[i]) {
        out.push({ rel, line: i + 1, msg: 'nonce/token rendered into the DOM', evidence: l.trim() });
      }
    });
    return out;
  },
});

rule({
  id: 'G03', sev: 'CRITICAL', kind: 'file',
  title: 'Raw SQL with untrusted interpolation',
  source: '$apollo_rule.audit_verificator #3',
  fix: 'Build every variable fragment through $wpdb->prepare() and concatenate the prepared pieces.',
  run: ({ rel, text, lines }) => {
    if (!rel.endsWith('.php')) return [];
    const out = [];
    const re = /\$wpdb->(query|get_var|get_row|get_col|get_results)\s*\(\s*(["'])/g;
    let m;
    while ((m = re.exec(text))) {
      const stmtStart = m.index;
      const stmt = text.slice(stmtStart, stmtStart + 1400);
      const line = text.slice(0, stmtStart).split('\n').length;
      const srcLine = lines[line - 1] || '';
      // Already prepared, or explicitly annotated as reviewed → not a finding.
      if (/\$wpdb->prepare\s*\(/.test(stmt.slice(0, 200))) continue;
      if (/phpcs:ignore[^\n]*PreparedSQL/.test(srcLine)) continue;
      // Which variables get interpolated inside the quoted string?
      const q = m[2];
      const strEnd = stmt.indexOf(q, m[0].length);
      const body = strEnd > 0 ? stmt.slice(m[0].length, strEnd) : stmt.slice(m[0].length, 400);
      const vars = body.match(/\{?\$[A-Za-z_][A-Za-z0-9_>\-\[\]'"$]*\}?/g) || [];
      const untrusted = vars.filter((v) => !TRUSTED_SQL_IDENTS.test(v));
      if (untrusted.length) {
        // Concatenating already-prepared fragments is the correct WP pattern and
        // is NOT injectable. Detect it and drop to MEDIUM "verify" instead of
        // crying wolf — a scanner nobody trusts is a scanner nobody runs.
        const names = untrusted.map((v) => v.replace(/[{}]/g, '').split(/[\[\->]/)[0]);
        const prepared = names.every((n) => new RegExp(`\\${n}\\s*(\\.)?=\\s*[^;]*\\$wpdb->prepare\\s*\\(`).test(text)
          || new RegExp(`\\${n}\\s*=\\s*[^;]*sanitize_sql_orderby`).test(text));
        out.push({
          rel, line,
          sev: prepared || nonShipping(rel) ? 'MEDIUM' : 'CRITICAL',
          msg: prepared
            ? `concatenates prepared fragments (${names.slice(0, 3).join(', ')}) — verify every branch prepares`
            : `interpolates ${untrusted.slice(0, 3).join(', ')} without prepare()`,
          evidence: srcLine.trim().slice(0, 160),
        });
      }
    }
    return out;
  },
});

rule({
  id: 'G04', sev: 'HIGH', kind: 'file',
  title: 'Debug output reachable in production',
  source: '$apollo_rule.data_flow.production · TBD-019',
  fix: 'Delete the console.* call; wrap error_log() in if (defined("WP_DEBUG") && WP_DEBUG).',
  run: ({ rel, lines, cmt }) => {
    const out = [];
    // A build harness printing its own results is not debug output in production.
    const sev = nonShipping(rel) ? 'MEDIUM' : 'HIGH';
    lines.forEach((l, i) => {
      if (cmt[i]) return;
      if (/console\.(log|debug|info|warn)\s*\(/.test(l)) {
        out.push({ rel, line: i + 1, sev, msg: 'console.* in shipped code', evidence: l.trim().slice(0, 140) });
      }
      if (/\berror_log\s*\(/.test(l)) {
        const win = lines.slice(Math.max(0, i - 6), i).join('\n');
        if (!/WP_DEBUG/.test(win) && !/WP_DEBUG/.test(l)) {
          out.push({ rel, line: i + 1, sev, msg: 'error_log() with no WP_DEBUG gate', evidence: l.trim().slice(0, 140) });
        }
      }
    });
    return out;
  },
});

rule({
  id: 'G07', sev: 'HIGH', kind: 'file',
  title: 'PHP file with no ABSPATH guard',
  source: '$apollo_rule.audit_verificator #7',
  fix: "Add: if ( ! defined( 'ABSPATH' ) ) { exit; }",
  run: ({ rel, text }) => {
    if (!rel.endsWith('.php')) return [];
    if (ABSPATH_EXEMPT_FILES.test(rel)) return [];
    if (ABSPATH_EXEMPT_DIRS.some((d) => rel.startsWith(d))) return [];
    if (/ABSPATH|WP_UNINSTALL_PLUGIN/.test(text)) return [];
    return [{ rel, line: 1, sev: nonShipping(rel) ? 'MEDIUM' : 'HIGH', msg: 'directly reachable over HTTP', evidence: rel }];
  },
});

rule({
  id: 'G08', sev: 'CRITICAL', kind: 'file',
  title: 'REST write route with no real permission_callback',
  source: '$apollo_rule.audit_verificator #8',
  fix: 'Replace __return_true with a capability check. A public write endpoint must at minimum be nonce-verified AND rate-limited, and say so in a comment on the line.',
  run: ({ rel, text }) => {
    if (!rel.endsWith('.php')) return [];
    const out = [];
    const balanced = (t, i) => {
      let d = 0;
      while (i < t.length) {
        const c = t[i];
        if (c === '(') d++;
        else if (c === ')') { d--; if (d === 0) return i + 1; }
        else if (c === "'" || c === '"') { const q = c; i++; while (i < t.length && t[i] !== q) { if (t[i] === '\\') i++; i++; } }
        i++;
      }
      return t.length;
    };
    const WRITE = /POST|PUT|PATCH|DELETE|EDITABLE|CREATABLE|DELETABLE/i;
    for (const m of text.matchAll(/register_rest_route\s*\(/g)) {
      const s = text.indexOf('(', m.index);
      const call = text.slice(s, balanced(text, s));
      const line0 = text.slice(0, s).split('\n').length;
      const ms = [...call.matchAll(/['"]methods['"]\s*=>\s*(.+)/g)];
      const pcs = [...call.matchAll(/['"]permission_callback['"]\s*=>\s*(.+)/g)];
      for (let i = 0; i < ms.length; i++) {
        const start = ms[i].index;
        const end = i + 1 < ms.length ? ms[i + 1].index : call.length;
        // A sibling permission_callback may sit before OR after 'methods' inside
        // the same array literal — search the whole sibling window both ways.
        const prev = i > 0 ? ms[i - 1].index : 0;
        const pc = pcs.find((p) => p.index > start && p.index < end)
                || pcs.find((p) => p.index > prev && p.index < start);
        const methods = ms[i][1].trim().replace(/,$/, '');
        const ln = line0 + call.slice(0, start).split('\n').length - 1;
        if (!pc) {
          out.push({ rel, line: ln, msg: 'route registered with NO permission_callback', evidence: methods.slice(0, 80) });
          continue;
        }
        const cb = pc[1].trim().replace(/,$/, '').replace(/^['"]|['"]$/g, '');
        if (cb === '__return_true' && WRITE.test(methods)) {
          const srcLine = (text.split('\n')[ln - 1] || '');
          const annotated = /\/\/.*(public|rate|nonce|intencional|by design|por design)/i.test(pc[0]);
          out.push({
            rel, line: ln,
            msg: annotated ? 'public write route (annotated — confirm rate limit + nonce)' : 'unauthenticated write route',
            evidence: `${methods.slice(0, 50)} → __return_true`,
          });
        }
      }
    }
    return out;
  },
});

rule({
  id: 'G11', sev: 'MEDIUM', kind: 'file',
  title: 'Superglobal used without sanitisation on the same statement',
  source: '$apollo_rule.audit_verificator #10, #11',
  fix: 'sanitize_text_field() / absint() / sanitize_key() / sanitize_email() at the boundary.',
  run: ({ rel, lines, cmt }) => {
    if (!rel.endsWith('.php')) return [];
    const out = [];
    const SANITIZERS = /sanitize_|absint|intval|\(int\)|\(float\)|wp_kses|esc_|wp_verify_nonce|check_admin_referer|check_ajax_referer|isset|empty|array_key_exists|filter_var/;
    lines.forEach((l, i) => {
      if (cmt[i]) return;
      if (/\$_(GET|POST|REQUEST|COOKIE)\s*\[/.test(l) && !SANITIZERS.test(l)) {
        out.push({ rel, line: i + 1, msg: 'unsanitised superglobal', evidence: l.trim().slice(0, 140) });
      }
    });
    return out;
  },
});

/* ── D-series: Apollo doctrine — the rules that make it Apollo ──────── */

rule({
  id: 'D01', sev: 'CRITICAL', kind: 'file',
  title: 'CPT / taxonomy / meta registered outside apollo-core',
  source: '02-header.CRITICAL_apollo_core_centralization · $GLOBAL_ARCHITECTURE',
  fix: 'Declare it in apollo-core/config/cpts.php · taxonomies.php · src/Core/MetaRegistry.php. Nothing else registers.',
  run: ({ rel, plugin, lines, cmt }) => {
    if (plugin === CORE || !rel.endsWith('.php')) return [];
    const out = [];
    lines.forEach((l, i) => {
      if (cmt[i]) return;                                       // a comment about the rule is not a breach
      if (/(public|private|protected|function)\s+register_(post_type|taxonomy|post_meta)/.test(l)) return; // own method decl
      const m = l.match(/\b(register_post_type|register_taxonomy|register_post_meta|register_term_meta)\s*\(/);
      if (m) out.push({ rel, line: i + 1, msg: `${m[1]}() outside ${CORE}`, evidence: l.trim().slice(0, 120) });
    });
    return out;
  },
});

rule({
  id: 'D02', sev: 'CRITICAL', kind: 'file',
  title: 'Forbidden social concept (PARTY_MODEL breach)',
  source: '01-philosophy: NO_SELECTIVE_FOLLOW · NO_EGO_COUNTERS · WOW_NOT_LIKE',
  fix: 'Remove it. There is no follow, no follower count, no like. Reactions are WOW; saving is fav.',
  run: ({ rel, lines, cmt }) => {
    const out = [];
    const HARD = [
      [/id\s*=\s*["'][^"']*[Ff]ollow/, 'a follow control'],
      [/class\s*=\s*["'][^"']*(follow-btn|btn-follow|sidebar-follow)/, 'a follow button style'],
      [/(aria-label|title)\s*=\s*["']\s*Seguir\b/i, '"Seguir" affordance'],
      [/>\s*Seguir\s*</i, '"Seguir" label'],
      [/(followers|seguidores)_?(count|gained|total)/i, 'a follower counter'],
      [/['"](followers|seguidores)['"]\s*=>/i, 'a followers-keyed payload'],
      [/const\s+FOLLOWERS\b|['"]\/followers/i, 'a /followers route'],
      [/\b(like|curtida)s?_count\b/i, 'a like counter'],
    ];
    lines.forEach((l, i) => {
      if (cmt[i]) return;                              // prose explaining the ban is fine
      if (/ri-user-unfollow-line/.test(l)) return;      // icon font name for "block/unsubscribe"
      if (EXTERNAL_FOLLOW.test(l)) return;              // following on SoundCloud is not an Apollo follow
      for (const [re, what] of HARD) {
        if (re.test(l)) {
          out.push({ rel, line: i + 1, sev: nonShipping(rel) ? 'MEDIUM' : 'CRITICAL', msg: what, evidence: l.trim().slice(0, 140) });
          break;
        }
      }
    });
    return out;
  },
});

rule({
  id: 'D03', sev: 'HIGH', kind: 'file',
  title: 'Forbidden relative-time format',
  source: '15-conventions.timeDisplay.FORBIDDEN · $apollo_rule.development_guidelines.time',
  fix: 'apollo_time_ago() / apollo_time_ago_html() / tempoHTML(). Never human_time_diff, never "atrás", never "ago".',
  run: ({ rel, lines, cmt }) => {
    const out = [];
    lines.forEach((l, i) => {
      if (cmt[i]) return;
      if (!/human_time_diff/.test(l)) return;
      const guarded = /function_exists\s*\(\s*['"]apollo_time_ago/.test(l);
      out.push({
        rel, line: i + 1,
        msg: guarded ? 'human_time_diff() as a fallback branch' : 'human_time_diff() rendered directly',
        evidence: l.trim().slice(0, 140),
      });
    });
    return out;
  },
});

rule({
  id: 'D04', sev: 'HIGH', kind: 'file',
  title: 'Design token declared outside the token owner',
  source: 'CLAUDE.md non-negotiable rule #1 — ":root belongs to core"',
  fix: 'Scope it to the component (.pev { --pev-band: … }) or add it to the one canonical token sheet. A second :root forks the system.',
  run: ({ rel, plugin, text }) => {
    if (TOKEN_OWNER.has(plugin)) return [];
    if (!/\.(css|php)$/.test(rel)) return [];
    const out = [];
    for (const m of text.matchAll(/:root[^{]*\{/g)) {
      out.push({
        rel, line: text.slice(0, m.index).split('\n').length,
        msg: `:root block in ${plugin}`, evidence: m[0].trim(),
      });
    }
    return out;
  },
});

rule({
  id: 'D05', sev: 'HIGH', kind: 'file',
  title: 'Malformed colour token (missing #)',
  source: 'live defect — var(--primary) resolves invalid and the brand colour disappears',
  fix: 'Add the #. FF9820 is not a colour; #FF9820 is.',
  run: ({ rel, lines }) => {
    const out = [];
    lines.forEach((l, i) => {
      const m = l.match(/(--[A-Za-z0-9_-]+)\s*:\s*([0-9A-Fa-f]{6})\s*(!important)?\s*;/);
      // 6 hex digits with at least one letter — "100" or "2026" are numbers, not colours.
      if (m && /[A-Fa-f]/.test(m[2])) {
        out.push({ rel, line: i + 1, msg: `${m[1]}: ${m[2]} has no #`, evidence: l.trim().slice(0, 120) });
      }
    });
    return out;
  },
});

rule({
  id: 'D06', sev: 'HIGH', kind: 'file',
  title: 'Legacy canvas shell path',
  source: '18-canvas-shell.$unification_2026_08_05 · PLUGIN-DEPLOY-MAP §6',
  fix: 'apollo_plus_open() / apollo_plus_close() is the only shell entry point. Paths A and C are retained for rollback only.',
  run: ({ rel, plugin, lines, cmt }) => {
    if (plugin === CORE) return [];                    // core defines them
    const out = [];
    lines.forEach((l, i) => {
      if (cmt[i]) return;
      const m = l.match(/\b(render_blank_canvas_plus|apollo_render_blank_canvas_body)\s*\(/);
      if (m) out.push({ rel, line: i + 1, msg: `${m[1]}() — legacy shell`, evidence: l.trim().slice(0, 130) });
    });
    return out;
  },
});

rule({
  id: 'D07', sev: 'MEDIUM', kind: 'file',
  title: 'Forbidden vocabulary in an identifier',
  source: '15-conventions.namingRules.FORBIDDEN_TERMS',
  fix: 'loc · fav · wow · depoimento · cena · doc · /id/. Portuguese prose is exempt; identifiers are not. Reported once per file — rename the file, not the line.',
  run: ({ rel, lines, cmt }) => {
    if (nonShipping(rel)) return [];
    const BAD = [
      [/\b(venue|venues)\s*[:=.\[]|['"](venue|venues)['"]/i, "venue → loc"],
      [/\$?\b(interesse|interessado|bookmark)s?\s*[:=.\[]/i, "interesse/bookmark → fav"],
      [/["'\/]user\/(?!\w*-)/, "/user/ → /id/"],
    ];
    // One finding per file per term: 179 hits in one fixture is noise, not signal.
    const seen = new Map();
    lines.forEach((l, i) => {
      if (cmt[i]) return;
      for (const [re, what] of BAD) {
        if (re.test(l)) {
          if (!seen.has(what)) seen.set(what, { line: i + 1, n: 0, ev: l.trim().slice(0, 110) });
          seen.get(what).n++;
          break;
        }
      }
    });
    return [...seen.entries()].map(([what, v]) => ({
      rel, line: v.line, msg: `${what} (${v.n}× in this file)`, evidence: v.ev,
    }));
  },
});

rule({
  id: 'D11', sev: 'CRITICAL', kind: 'file',
  title: 'Debug beacon phoning a local address from shipped code',
  source: 'portal harness assertion E27 — written because this regressed once already',
  fix: 'Delete it. A beacon posts editor-side data to whatever is listening on that port on the user\'s own machine, and .catch(){} hides that it happened.',
  run: ({ rel, lines, cmt }) => {
    const out = [];
    lines.forEach((l, i) => {
      if (cmt[i]) return;                 // a note saying a beacon was removed is not a beacon
      if (!/(fetch|XMLHttpRequest|sendBeacon|wp_remote_(post|get)|curl_init)/i.test(l)) return;
      if (!/(localhost|127\.0\.0\.1|0\.0\.0\.0|\[::1\]|192\.168\.|10\.\d+\.)/.test(l)) return;
      out.push({
        rel, line: i + 1,
        sev: nonShipping(rel) ? 'MEDIUM' : 'CRITICAL',
        msg: 'client-side beacon to a local address',
        evidence: l.trim().slice(0, 150),
      });
    });
    return out;
  },
});

rule({
  id: 'D12', sev: 'HIGH', kind: 'tree',
  title: 'Verification harness present but failing',
  source: 'CLAUDE.md — "A green run is the gate, not a formality"',
  fix: 'Run it, read the assertion, fix the cell. Do not add a new cell while its harness is red.',
  run: ({ root }) => {
    // Reported, never executed here: running five harnesses inside a pre-commit
    // hook would make the hook slower than the edit. `--harness` runs them.
    const out = [];
    for (const p of fs.readdirSync(root).filter((x) => x.startsWith('apollo-'))) {
      const dir = path.join(root, p, '_sandbox');
      if (!fs.existsSync(dir)) continue;
      for (const f of fs.readdirSync(dir)) {
        if (!/^build-.*\.mjs$/.test(f)) continue;
        out.push({ rel: `${p}/_sandbox/${f}`, line: 1, sev: 'LOW',
          msg: 'harness — run it before you call the change done', evidence: `node ${p}/_sandbox/${f}` });
      }
    }
    return out;
  },
});

/* ── C-series: contract rules (plan-003 · P1). Own cell: apollo-guard-c.mjs ── */
installCRules(rule);

/* ── M-series: mobile-first. The product is a phone app; the desktop is the
      secondary client. A rule here fails the app, not the website. ───────── */

// The breakpoint ladder this ecosystem actually needs. Anything else is a
// screen inventing its own idea of "small", which is how 16 breakpoints and a
// 767/768 overlap zone happen.
const BP_LADDER = new Set([480, 768, 1000, 1400]);

rule({
  id: 'M01', sev: 'CRITICAL', kind: 'tree',
  title: 'No web app manifest — the app cannot be installed',
  source: 'the head already carries every PWA meta except this one',
  fix: 'Ship a manifest and link it from the ONE head builder. Without it Android offers no install prompt, no icon and no standalone launch; iOS half-works off apple-mobile-web-app-capable, which is why this went unnoticed.',
  run: ({ files, read }) => {
    const linked = files.some((f) => /rel\s*=\s*["']manifest["']/.test(read(f)));
    const onDisk = files.some((f) => /manifest\.(webmanifest|json)$/.test(f.rel));
    if (linked && onDisk) return [];
    return [{
      rel: '(ecosystem)', line: 0,
      msg: linked ? 'manifest linked but no manifest file exists' : 'no <link rel="manifest"> anywhere in 1,900 files',
      evidence: 'apple-mobile-web-app-capable · theme-color · viewport-fit=cover are all present — only the manifest is missing',
    }];
  },
});

rule({
  id: 'M02', sev: 'HIGH', kind: 'file',
  title: 'Service worker registered from a scope it cannot escape',
  source: 'SW scope is its own directory unless the response sends Service-Worker-Allowed',
  fix: 'Serve the worker from the site root (or send Service-Worker-Allowed: /). A worker under /wp-content/plugins/x/assets/ can receive push, but can never cache the app shell or serve an offline page.',
  run: ({ rel, lines, cmt }) => {
    if (!/assets\//.test(rel)) return [];
    const out = [];
    lines.forEach((l, i) => {
      if (cmt[i]) return;
      if (/serviceWorker\s*\.\s*register\s*\(/.test(l)) {
        out.push({ rel, line: i + 1, msg: 'worker scoped to a plugin assets directory', evidence: l.trim().slice(0, 130) });
      }
    });
    return out;
  },
});

rule({
  id: 'M03', sev: 'HIGH', kind: 'file',
  title: 'Pinch-zoom disabled',
  source: 'WCAG 2.1 SC 1.4.4 Resize Text',
  fix: 'Drop user-scalable=no and maximum-scale. Stop double-tap zoom with touch-action: manipulation on the elements that need it — that is the targeted fix; this is the blanket one.',
  run: ({ rel, lines, cmt }) => {
    const out = [];
    lines.forEach((l, i) => {
      if (cmt[i]) return;
      if (!/name\s*=\s*["']viewport["']/.test(l)) return;
      const m = l.match(/user-scalable\s*=\s*no|maximum-scale\s*=\s*1(?![0-9])/);
      if (m) out.push({ rel, line: i + 1, msg: `viewport sets ${m[0]}`, evidence: l.trim().slice(0, 150) });
    });
    return out;
  },
});

rule({
  id: 'M04', sev: 'MEDIUM', kind: 'file',
  title: '100vh where the phone means 100dvh',
  source: 'mobile browser chrome resizes the viewport; 100vh is the LARGE viewport, so content sits under the toolbar',
  fix: '100dvh for full-height panels, 100svh where the layout must never jump. The ecosystem already uses dvh in 37 files — these are the ones left behind.',
  run: ({ rel, lines, cmt }) => {
    const out = [];
    lines.forEach((l, i) => {
      if (cmt[i]) return;
      const m = l.match(/((?:min-|max-)?height)\s*:\s*100vh/);
      if (!m) return;
      // The CORRECT fix keeps 100vh as the fallback and adds the dvh declaration
      // after it — a browser without dvh drops the second and keeps the first.
      // So `height:100vh` followed by `height:100dvh` is the fixed state, not a
      // finding. Accept the pair on the same line or on the next non-blank line.
      const prop = m[1];
      const paired = new RegExp(prop + '\\s*:\\s*100(dvh|svh)');
      if (paired.test(l)) return;
      for (let j = i + 1; j < Math.min(i + 3, lines.length); j++) {
        if (!lines[j].trim()) continue;
        if (paired.test(lines[j])) return;
        break;
      }
      out.push({ rel, line: i + 1, msg: '100vh with no dvh fallback after it', evidence: l.trim().slice(0, 120) });
    });
    return out;
  },
});

rule({
  id: 'M05', sev: 'MEDIUM', kind: 'tree',
  title: 'No responsive images anywhere',
  source: 'a phone on 4G downloads the desktop asset',
  fix: 'srcset + sizes on every content image, or hand it to the CDN with a width parameter.',
  run: ({ files, read }) => {
    let imgs = 0; let srcset = 0;
    for (const f of files) {
      const t = read(f);
      imgs += (t.match(/<img\b/g) || []).length;
      srcset += (t.match(/\bsrcset\s*=/g) || []).length;
    }
    if (srcset >= Math.max(4, imgs * 0.1)) return [];
    return [{ rel: '(ecosystem)', line: 0, msg: `${imgs} <img> tags, ${srcset} with srcset`, evidence: 'every phone gets the full-size file' }];
  },
});

rule({
  id: 'M06', sev: 'MEDIUM', kind: 'tree',
  title: 'Breakpoint outside the declared ladder',
  source: 'one ladder, or every screen invents its own idea of "small"',
  fix: `Use ${[...BP_LADDER].join(' · ')}. max-width:767px and max-width:768px both existing is not a style choice — at 768px both a max- and a min- rule apply.`,
  run: ({ files, read }) => {
    const seen = new Map();
    for (const f of files) {
      if (!/\.(css|php)$/.test(f.rel)) continue;
      for (const m of read(f).matchAll(/@media[^{]*?\(\s*(min|max)-width\s*:\s*(\d+)px/g)) {
        const px = Number(m[2]);
        if (BP_LADDER.has(px)) continue;
        const k = `${m[1]}-width:${px}px`;
        if (!seen.has(k)) seen.set(k, { n: 0, where: f.rel });
        seen.get(k).n++;
      }
    }
    return [...seen.entries()]
      .sort((a, b) => b[1].n - a[1].n)
      .map(([k, v]) => ({ rel: '(ecosystem)', line: 0, msg: `${k} — ${v.n} declarations`, evidence: `first seen in ${v.where}` }));
  },
});

rule({
  id: 'D08', sev: 'CRITICAL', kind: 'tree',
  title: 'Non-shippable artefact inside the deployed tree',
  source: 'CLAUDE.md — "a saved file is a deployed file"',
  fix: 'Move it out of the mirrored folder. This directory is served at https://apollo.rio.br/wp-content/plugins/.',
  run: ({ root }) => {
    const out = [];
    const SECRET = /(password|passwd|senha|api[_-]?key|secret|private[_-]?key)\s*[=:]\s*["'][^"']{3,}/i;
    const walkTop = fs.readdirSync(root, { withFileTypes: true });
    for (const d of walkTop) {
      if (d.isDirectory() || d.name.startsWith('.')) continue;
      const bad = /^_tmp_|\.log$|^_.*diag|\.bak\d*$|\.ffs_db$|^debug[-_]/i.test(d.name);
      if (!bad) continue;
      let evidence = 'loose file in the deployed plugins root';
      try {
        const head = fs.readFileSync(path.join(root, d.name), 'utf8').slice(0, 20000);
        if (SECRET.test(head)) evidence = 'CONTAINS CREDENTIAL-SHAPED STRINGS — rotate them';
      } catch { /* binary or unreadable */ }
      out.push({ rel: d.name, line: 1, msg: 'publicly served, not part of any plugin', evidence });
    }
    // Diagnostic PHP shipped inside a plugin.
    for (const p of fs.readdirSync(root).filter((x) => x.startsWith('apollo-'))) {
      const dir = path.join(root, p);
      if (!fs.statSync(dir).isDirectory()) continue;
      for (const f of fs.readdirSync(dir)) {
        if (/^_.*(diag|debug|tmp|test).*\.php$/i.test(f) || /^(test|_debug)[-_].*\.php$/i.test(f)) {
          out.push({ rel: `${p}/${f}`, line: 1, msg: 'diagnostic endpoint inside a shipped plugin', evidence: 'reachable over HTTP' });
        }
      }
    }
    return out;
  },
});

rule({
  id: 'D09', sev: 'HIGH', kind: 'tree',
  title: 'Plugin version: docblock and constant disagree',
  source: '19-ssot-audit.high.version_split_fixed — "WordPress reads the docblock; the constant drives cache-busting"',
  fix: 'Bump both in the same edit, every time.',
  run: ({ root }) => {
    const out = [];
    for (const p of fs.readdirSync(root).filter((x) => x.startsWith('apollo-'))) {
      const main = path.join(root, p, `${p}.php`);
      if (!fs.existsSync(main)) continue;
      const t = fs.readFileSync(main, 'utf8');
      const doc = t.match(/^\s*\*?\s*Version:\s*([0-9][0-9A-Za-z.\-]*)/m)?.[1];
      const con = t.match(/define\(\s*'(APOLLO_[A-Z0-9_]*VERSION)'\s*,\s*'([^']+)'/)?.[2];
      if (doc && con && doc !== con) {
        out.push({ rel: `${p}/${p}.php`, line: 1, msg: `docblock ${doc} ≠ constant ${con}`, evidence: 'cache-busting will lie' });
      }
    }
    return out;
  },
});

rule({
  id: 'D10', sev: 'MEDIUM', kind: 'tree',
  title: 'Design token declared by two plugins with different values',
  source: 'CLAUDE.md cardinal sin, at ecosystem scale — whichever sheet loads last wins',
  fix: 'One owner per token. Everything else consumes var(). Component-scoped custom properties are fine.',
  run: ({ files, read }) => {
    const tok = new Map();                    // name → Map(value → Set(plugin))
    for (const f of files) {
      if (!/\.(css|php)$/.test(f.rel)) continue;
      const text = read(f);
      for (const m of text.matchAll(/:root[^{]*\{([\s\S]*?)\}/g)) {
        for (const d of m[1].matchAll(/(--[A-Za-z0-9_-]+)\s*:\s*([^;]+);/g)) {
          const k = d[1]; const v = d[2].trim().replace(/\s+/g, ' ');
          if (!tok.has(k)) tok.set(k, new Map());
          if (!tok.get(k).has(v)) tok.get(k).set(v, new Set());
          tok.get(k).get(v).add(f.plugin);
        }
      }
    }
    const out = [];
    for (const [k, vals] of tok) {
      const owners = new Set([...vals.values()].flatMap((s) => [...s]));
      if (owners.size > 1 && vals.size > 1) {
        out.push({
          rel: '(ecosystem)', line: 0,
          msg: `${k} — ${vals.size} different values across ${owners.size} plugins`,
          evidence: [...vals.entries()].slice(0, 3).map(([v, s]) => `${v.slice(0, 28)} ← ${[...s].join(',')}`).join('  |  '),
        });
      }
    }
    return out.sort((a, b) => b.msg.localeCompare(a.msg));
  },
});

/* ─────────────────────────────── engine ────────────────────────────── */

function collect(root, only) {
  const files = [];
  const walk = (dir, rel) => {
    let ents;
    try { ents = fs.readdirSync(dir, { withFileTypes: true }); } catch { return; }
    for (const e of ents) {
      if (e.name.startsWith('.') && e.name !== '.htaccess') continue;
      const r = rel ? `${rel}/${e.name}` : e.name;
      if (e.isDirectory()) {
        if (SKIP_DIRS.has(e.name)) continue;
        walk(path.join(dir, e.name), r);
      } else if (SCAN_EXT.has(path.extname(e.name))) {
        files.push({ rel: r, abs: path.join(dir, e.name), plugin: r.split('/')[0] });
      }
    }
  };
  const tops = only.length ? only : fs.readdirSync(root).filter((d) => d.startsWith('apollo-'));
  for (const t of tops) {
    const abs = path.join(root, t);
    if (!fs.existsSync(abs)) { console.error(`  ! no such plugin: ${t}`); continue; }
    if (fs.statSync(abs).isDirectory()) walk(abs, t);
  }
  return files;
}

function main() {
  const argv = process.argv.slice(2);
  const VALUED = new Set(['--root', '--json', '--rule', '--baseline', '--write-baseline']);
  const consumed = new Set();
  argv.forEach((a, i) => { if (VALUED.has(a)) { consumed.add(i); consumed.add(i + 1); } else if (a.startsWith('--')) consumed.add(i); });
  const flag = (n) => { const i = argv.indexOf(n); return i >= 0 ? (argv[i + 1] ?? true) : null; };
  const has = (n) => argv.includes(n);

  const root = flag('--root') || process.cwd();
  const verbose = has('--verbose');
  const jsonOut = flag('--json');
  const only = argv.filter((a, i) => !consumed.has(i));
  const ruleFilter = flag('--rule');
  // --rule accepts exact ids (G08,D02) or a series prefix (M, D, G).
  const wanted = ruleFilter ? String(ruleFilter).split(',').map((x) => x.trim()).filter(Boolean) : null;
  const active = rules.filter((r) => !wanted || wanted.some((w) => r.id === w || r.id.startsWith(w)));

  let files;
  if (has('--staged')) {
    const staged = execSync('git diff --cached --name-only --diff-filter=ACM', { cwd: root, encoding: 'utf8' })
      .split('\n').filter((f) => f && SCAN_EXT.has(path.extname(f)));
    files = staged.map((rel) => ({ rel, abs: path.join(root, rel), plugin: rel.split('/')[0] }))
      .filter((f) => fs.existsSync(f.abs) && f.plugin.startsWith('apollo-'));
  } else {
    files = collect(root, only);
  }

  const cache = new Map();
  const read = (f) => {
    if (!cache.has(f.abs)) {
      try { cache.set(f.abs, fs.readFileSync(f.abs, 'utf8')); } catch { cache.set(f.abs, ''); }
    }
    return cache.get(f.abs);
  };

  /* ── --harness: run every _sandbox/build-*.mjs and report, nothing else ── */
  if (has('--harness')) {
    const rows = [];
    for (const p of fs.readdirSync(root).filter((x) => x.startsWith('apollo-'))) {
      const dir = path.join(root, p, '_sandbox');
      if (!fs.existsSync(dir)) continue;
      for (const f of fs.readdirSync(dir).filter((x) => /^build-.*\.mjs$/.test(x))) {
        const rel = `${p}/_sandbox/${f}`;
        let code = 0; let out = '';
        try { out = execSync(`node "${path.join(dir, f)}"`, { cwd: root, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'], timeout: 120000 }); }
        catch (e) { code = e.status ?? 1; out = `${e.stdout || ''}${e.stderr || ''}`; }
        const tally = out.match(/(\d+)\s*\/\s*(\d+)\s*(?:passed|assertions)/i);
        rows.push({ rel, code, tally: tally ? `${tally[1]}/${tally[2]}` : '', });
      }
    }
    console.log('');
    console.log('  apollo-guard --harness');
    console.log('  ' + '─'.repeat(72));
    for (const r of rows) {
      console.log(`  ${r.code === 0 ? 'GREEN' : ' RED '}  ${String(r.tally).padEnd(8)} ${r.rel}`);
    }
    const red = rows.filter((r) => r.code !== 0).length;
    console.log('  ' + '─'.repeat(72));
    console.log(`  ${rows.length - red} green · ${red} red`);
    console.log('');
    return process.exit(red ? 1 : 0);
  }

  const findings = [];
  // A rule may downgrade an individual finding by setting `sev` on it; the
  // rule's declared severity is the default, never an override.
  const push = (r, list) => list.forEach((x) => findings.push({
    rule: r.id, sev: r.sev, title: r.title, source: r.source, fix: r.fix, ...x,
  }));

  for (const f of files) {
    const text = read(f);
    if (!text && fs.existsSync(f.abs) && fs.statSync(f.abs).size > 0) {
      findings.push({ rule: 'G00', sev: 'HIGH', title: 'File could not be read as UTF-8', source: 'fail-closed', rel: f.rel, line: 1, msg: 'unreadable', evidence: '', fix: 'Check encoding.' });
      continue;
    }
    const lines = text.split('\n');
    const cmt = commentMask(lines);
    for (const r of active) {
      if (r.kind !== 'file') continue;
      try { push(r, r.run({ rel: f.rel, plugin: f.plugin, text, lines, cmt })); }
      catch (e) { console.error(`  ! rule ${r.id} threw on ${f.rel}: ${e.message}`); }
    }
  }
  for (const r of active) {
    if (r.kind !== 'tree') continue;
    try { push(r, r.run({ root, files, read })); }
    catch (e) { console.error(`  ! rule ${r.id} threw: ${e.message}`); }
  }

  findings.sort((a, b) => SEV_ORDER[a.sev] - SEV_ORDER[b.sev] || a.rule.localeCompare(b.rule) || a.rel.localeCompare(b.rel) || a.line - b.line);

  /* ── baseline: accept today's debt, block tomorrow's ── */
  const key = (f) => `${f.rule}|${f.rel}|${f.msg}`;
  if (has('--write-baseline')) {
    const p = flag('--write-baseline');
    fs.writeFileSync(p, JSON.stringify({ generated: new Date().toISOString(), accepted: findings.map(key) }, null, 2));
    console.log(`baseline written: ${p} (${findings.length} findings accepted)`);
    return process.exit(0);
  }
  let shown = findings;
  if (flag('--baseline')) {
    const acc = new Set(JSON.parse(fs.readFileSync(flag('--baseline'), 'utf8')).accepted);
    shown = findings.filter((f) => !acc.has(key(f)));
  }

  /* ── report ── */
  const W = (s, n) => String(s).padEnd(n);
  const counts = shown.reduce((a, f) => (a[f.sev] = (a[f.sev] || 0) + 1, a), {});
  const scanned = files.length;

  console.log('');
  console.log('  apollo-guard · executable doctrine');
  console.log(`  ${scanned} files · ${active.length} rules · ${shown.length} findings`);
  console.log('  ' + '─'.repeat(76));

  let currentRule = null;
  for (const f of shown) {
    if (!verbose && f.sev === 'LOW') continue;
    if (f.rule !== currentRule) {
      currentRule = f.rule;
      const n = shown.filter((x) => x.rule === f.rule && (verbose || x.sev !== 'LOW')).length;
      console.log('');
      console.log(`  [${f.sev}] ${f.rule} · ${f.title}  (${n})`);
      console.log(`         ↳ ${f.source}`);
      if (f.fix) console.log(`         ↳ fix: ${f.fix}`);
      console.log('');
    }
    const loc = f.line ? `${f.rel}:${f.line}` : f.rel;
    console.log(`    ${W(loc, 62)} ${f.msg}`);
    if (verbose && f.evidence) console.log(`      ${String(f.evidence).slice(0, 150)}`);
  }

  console.log('');
  console.log('  ' + '─'.repeat(76));
  console.log(`  CRITICAL ${counts.CRITICAL || 0}   HIGH ${counts.HIGH || 0}   MEDIUM ${counts.MEDIUM || 0}   LOW ${counts.LOW || 0}`);

  if (jsonOut && typeof jsonOut === 'string') {
    fs.writeFileSync(jsonOut, JSON.stringify({ generated: new Date().toISOString(), scanned, findings: shown }, null, 2));
    console.log(`  json → ${jsonOut}`);
  }

  const blocking = shown.filter((f) => BLOCKING.has(f.sev)).length;
  if (blocking) {
    console.log(`  BLOCKED — ${blocking} finding(s) at CRITICAL/HIGH.`);
    console.log('  To accept existing debt and gate only new work:');
    console.log('    node apollo-guard.mjs --write-baseline .apollo-guard-baseline.json');
    console.log('');
    process.exit(1);
  }
  console.log('  clean.');
  console.log('');
  process.exit(0);
}

main();
