#!/usr/bin/env node
/**
 * W2 STRICT — compare apollo-core/config/*.php vs _dev-registry/03|04|05-*.md
 * No PHP edits. Output: _inventory/_worker-reports/W2-strict.md
 */
import fs from "fs";
import path from "path";

const ROOT = path.resolve(import.meta.dirname, "..");
const CFG = path.join(ROOT, "apollo-core/config");
const DEV = path.join(ROOT, "_dev-registry");
const OUT = path.join(ROOT, "_inventory/_worker-reports/W2-strict.md");

const FILES = ["cpts.php", "taxonomies.php", "meta.php", "tables.php", "routes.php"];

function read(p) {
  try {
    return fs.readFileSync(p, "utf8");
  } catch {
    return null;
  }
}

/** Top-level keys: exactly one indent step before `=> array(`. */
function topLevelKeys(src, indentRe) {
  const keys = [];
  for (const line of src.split(/\r?\n/)) {
    const m = line.match(indentRe);
    if (m) keys.push(m[1]);
  }
  return [...new Set(keys)].sort();
}

const CPT_TOP = /^\s{4}['"]([a-z][a-z0-9_]*)['"]\s*=>\s*array\s*\(/;
const TAB_TOP = /^\t['"]([a-z][a-z0-9_]*)['"]\s*=>\s*array\s*\(/;
const ROUTE_TOP = /^\t['"](\/[^'"]+)['"]\s*=>\s*array\s*\(/;

const STRUCTURAL = new Set([
  "type",
  "format",
  "rest",
  "default",
  "items",
  "enum",
  "max",
  "min",
  "required",
  "taxonomy",
  "manage_only",
  "post",
  "user",
  "term",
  "event",
  "dj",
  "local",
  "classified",
  "supplier",
  "doc",
  "hub",
  "email_aprio",
  "page",
  "_global",
  "login",
  "profile",
  "chat",
  "notifications",
  "membership",
  "cena",
  "dashboard",
  "seo",
]);

function metaKeys(src) {
  const keys = new Set();
  const lines = src.split(/\r?\n/);
  for (let i = 0; i < lines.length; i++) {
    const m = lines[i].match(/^\s*['"]([_a-z][a-z0-9_]*)['"]\s*=>\s*array\s*\(/);
    if (!m) continue;
    const k = m[1];
    if (STRUCTURAL.has(k)) continue;
    const block = lines.slice(i, i + 6).join("\n");
    if (/'type'\s*=>/.test(block)) keys.add(k);
  }
  return [...keys].sort();
}

function mdCptDeclared(src) {
  const keys = [];
  for (const m of src.matchAll(/^### `([a-z][a-z0-9_]*)`/gm)) keys.push(m[1]);
  return [...new Set(keys)].sort();
}

function mdCptOwnerOnly(src) {
  const start = src.indexOf("## Registered but NOT declared");
  if (start < 0) return [];
  const rest = src.slice(start);
  const end = rest.search(/\n---\n/);
  const block = end > 0 ? rest.slice(0, end) : rest;
  const keys = [];
  for (const m of block.matchAll(/^\| `([a-z][a-z0-9_]*)` \|/gm)) keys.push(m[1]);
  return [...new Set(keys)].sort();
}

function mdTaxDeclared(src) {
  const block = src.split("## Registration sites")[0] || src;
  const keys = [];
  for (const m of block.matchAll(/^\| `([a-z][a-z0-9_]*)` \|/gm)) keys.push(m[1]);
  return [...new Set(keys)].sort();
}

function mdMetaGoverned(src) {
  const keys = new Set();
  const governedBlocks = [
    "## Governed but never touched",
    "## Governed post meta, by CPT",
    "## Governed user and term meta",
  ];
  for (const heading of governedBlocks) {
    const start = src.indexOf(heading);
    if (start < 0) continue;
    const rest = src.slice(start);
    const end = rest.search(/\n## /);
    const block = end > 0 ? rest.slice(0, end) : rest;
    for (const m of block.matchAll(/^\| `([^`]+)` \|/gm)) {
      const k = m[1];
      if (k === "key" || k.includes("/")) continue;
      keys.add(k);
    }
  }
  return [...keys].sort();
}

function diff(declared, onDisk) {
  const d = new Set(declared);
  const o = new Set(onDisk);
  return {
    declaredNotOnDisk: declared.filter((x) => !o.has(x)).sort(),
    onDiskNotDeclared: onDisk.filter((x) => !d.has(x)).sort(),
  };
}

function section(title, declared, onDisk) {
  const { declaredNotOnDisk, onDiskNotDeclared } = diff(declared, onDisk);
  let s = `## ${title}\n\n`;
  s += `- declared (_dev-registry): **${declared.length}**\n`;
  s += `- on-disk (apollo-core/config): **${onDisk.length}**\n\n`;
  s += "### declared-not-on-disk\n\n";
  s += declaredNotOnDisk.length
    ? declaredNotOnDisk.map((x) => `- \`${x}\``).join("\n")
    : "- _(none)_";
  s += "\n\n### on-disk-not-declared\n\n";
  s += onDiskNotDeclared.length
    ? onDiskNotDeclared.map((x) => `- \`${x}\``).join("\n")
    : "- _(none)_";
  s += "\n\n";
  return s;
}

const src = {};
const sizes = {};
for (const f of FILES) {
  const p = path.join(CFG, f);
  src[f] = read(p);
  sizes[f] = src[f] ? src[f].length : null;
}

const diskCpts = topLevelKeys(src["cpts.php"] || "", CPT_TOP);
const diskTax = topLevelKeys(src["taxonomies.php"] || "", TAB_TOP);
const diskMeta = metaKeys(src["meta.php"] || "");
const diskTables = topLevelKeys(src["tables.php"] || "", TAB_TOP);
const diskRoutes = topLevelKeys(src["routes.php"] || "", ROUTE_TOP);

const cptMd = read(path.join(DEV, "03-CPT.md")) || "";
const taxMd = read(path.join(DEV, "04-TAXONOMIES.md")) || "";
const metaMd = read(path.join(DEV, "05-META-KEYS.md")) || "";

const regCpts = mdCptDeclared(cptMd);
const regCptsOwnerOnly = mdCptOwnerOnly(cptMd);
const regTax = mdTaxDeclared(taxMd);
const regMeta = mdMetaGoverned(metaMd);

const md = [];
md.push("# W2 STRICT CONTRACT");
md.push("");
md.push(`Generated: ${new Date().toISOString()}`);
md.push("Coordinator: Grok · Worker: Composer 2.5 · **No PHP edits**");
md.push("Branch: `cursor/registry-all-plugins-incl-waha`");
md.push("");
md.push("## files-read");
for (const f of FILES) {
  const sz = sizes[f];
  md.push(sz == null ? `- \`${f}\`: **MISSING**` : `- \`apollo-core/config/${f}\`: ${sz} bytes`);
}
md.push("");
md.push("## registry docs matched");
md.push("- `_dev-registry/03-CPT.md`");
md.push("- `_dev-registry/04-TAXONOMIES.md`");
md.push("- `_dev-registry/05-META-KEYS.md` (governed population only; ungoverned/touched keys excluded)");
md.push("- `tables.php` / `routes.php`: read for inventory; no strict MD chapter");
md.push("");
md.push(section("CPT (`03-CPT.md` declared vs `cpts.php`)", regCpts, diskCpts));

if (regCptsOwnerOnly.length) {
  md.push("### CPT — owner-registered, not in central `cpts.php` (documented gap, not drift)\n");
  for (const s of regCptsOwnerOnly) md.push(`- \`${s}\``);
  md.push("");
}

md.push(section("Taxonomies (`04-TAXONOMIES.md` vs `taxonomies.php`)", regTax, diskTax));
md.push(section("Meta keys (`05-META-KEYS.md` governed vs `meta.php`)", regMeta, diskMeta));

md.push("## tables.php (on-disk inventory)\n");
md.push(`**${diskTables.length}** suffix keys under \`{wp_prefix}apollo_*\`.\n`);

md.push("## routes.php (on-disk inventory)\n");
md.push(`**${diskRoutes.length}** paths under namespace \`apollo/v1\`.\n`);

const cptDiff = diff(regCpts, diskCpts);
const taxDiff = diff(regTax, diskTax);
const metaDiff = diff(regMeta, diskMeta);

md.push("## summary\n");
md.push("| surface | declared | on-disk | declared-not-on-disk | on-disk-not-declared |");
md.push("|---|---:|---:|---:|---:|");
md.push(
  `| CPT | ${regCpts.length} | ${diskCpts.length} | ${cptDiff.declaredNotOnDisk.length} | ${cptDiff.onDiskNotDeclared.length} |`
);
md.push(
  `| Taxonomies | ${regTax.length} | ${diskTax.length} | ${taxDiff.declaredNotOnDisk.length} | ${taxDiff.onDiskNotDeclared.length} |`
);
md.push(
  `| Meta (governed) | ${regMeta.length} | ${diskMeta.length} | ${metaDiff.declaredNotOnDisk.length} | ${metaDiff.onDiskNotDeclared.length} |`
);
md.push(
  `| Tables | — | ${diskTables.length} | — | — |`
);
md.push(
  `| Routes | — | ${diskRoutes.length} | — | — |`
);
md.push("");
md.push("## notes");
md.push("- **declared** = generated `_dev-registry` markdown (03/04/05).");
md.push("- **on-disk** = `apollo-core/config/*.php` return-array keys.");
md.push("- Meta governed set excludes the 224 *touched-but-ungoverned* keys listed in 05-META-KEYS.md § Ungoverned.");
md.push("- `meta.php` header documents drift vs `MetaRegistry.php` registrar; this report compares MD ↔ `meta.php` only.");
md.push("- No PHP edits made.");

fs.mkdirSync(path.dirname(OUT), { recursive: true });
fs.writeFileSync(OUT, md.join("\n"));
fs.writeFileSync(
  path.join(path.dirname(OUT), "W2-strict.json"),
  JSON.stringify(
    {
      disk: { cpts: diskCpts, tax: diskTax, meta: diskMeta, tables: diskTables, routes: diskRoutes },
      registry: { cpts: regCpts, cptsOwnerOnly: regCptsOwnerOnly, tax: regTax, meta: regMeta },
      diff: { cpt: cptDiff, tax: taxDiff, meta: metaDiff },
    },
    null,
    2
  )
);

console.log("W2_STRICT_OK");
console.log(JSON.stringify({ cpt: cptDiff, tax: taxDiff, meta: metaDiff }, null, 2));
