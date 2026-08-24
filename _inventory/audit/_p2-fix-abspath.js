'use strict';
/**
 * Add ABSPATH guards to PHP files missing them (P2 hygiene).
 * Skips pure "Silence is golden" index.php (rewrites those to guarded silence).
 */
const fs = require('fs');
const path = require('path');

const ROOT = 'D:/dev/_apollo.rio.br/plugins';
const SKIP_DIRS = new Set(['vendor', 'node_modules', '.git', 'tests', 'test']);
const TARGETS = [
  'apollo-pane-engine',
  'apollo-email',
  'apollo-dj-sync',
  'apollo-wow',
];

const GUARD = `if ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n`;

function walk(dir, acc = [], depth = 0) {
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
      // skip underscore test/dev scripts for email optional - still guard them
      walk(p, acc, depth + 1);
    } else if (/\.php$/i.test(e.name)) acc.push(p);
  }
  return acc;
}

function hasGuard(t) {
  return /defined\s*\(\s*['"]ABSPATH['"]\s*\)/.test(t.slice(0, 2000));
}

function inject(content) {
  if (hasGuard(content)) return null;

  // Silence is golden index stubs
  if (/^\s*<\?php\s*(\/\/\s*Silence is golden\.?\s*)?$/i.test(content.trim()) ||
      content.trim() === '<?php' ||
      /^<\?php\s*\/\/\s*Silence is golden/i.test(content.trim())) {
    return `<?php\n// Silence is golden.\nif ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n`;
  }

  // After <?php and optional declare/strict and docblock, before or after namespace
  if (!content.startsWith('<?php')) {
    return `<?php\n${GUARD}\n` + content;
  }

  // Find insertion point: after declare(strict_types=1); if present, else after first docblock, else after <?php
  let pos = content.indexOf('<?php') + 5;
  // skip shebang-like whitespace
  const rest = content.slice(pos);

  // If declare strict at top
  const declareM = rest.match(/^\s*declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/);
  if (declareM) {
    pos += declareM[0].length;
  }

  // Skip file-level docblock /**
  const after = content.slice(pos);
  const docM = after.match(/^\s*\/\*\*[\s\S]*?\*\/\s*/);
  if (docM) {
    pos += docM[0].length;
  }

  // Skip namespace declaration
  const after2 = content.slice(pos);
  const nsM = after2.match(/^\s*namespace\s+[^;]+;\s*/);
  if (nsM) {
    pos += nsM[0].length;
  }

  // Skip use statements block
  let after3 = content.slice(pos);
  while (true) {
    const useM = after3.match(/^\s*use\s+[^;]+;\s*/);
    if (!useM) break;
    pos += useM[0].length;
    after3 = content.slice(pos);
  }

  // Insert guard with blank lines
  const before = content.slice(0, pos).replace(/\s*$/, '\n\n');
  const afterAll = content.slice(pos).replace(/^\s*/, '');
  return before + GUARD + '\n' + afterAll;
}

let fixed = 0;
const report = [];

for (const slug of TARGETS) {
  const dir = path.join(ROOT, slug);
  const files = walk(dir);
  for (const f of files) {
    const t = fs.readFileSync(f, 'utf8');
    if (hasGuard(t)) continue;
    const next = inject(t);
    if (!next || next === t) continue;
    fs.writeFileSync(f, next, 'utf8');
    fixed++;
    report.push(path.relative(path.join(ROOT, slug), f).replace(/\\/g, '/'));
    console.log('fixed', slug + '/' + path.relative(path.join(ROOT, slug), f));
  }
}

console.log('\nTotal fixed:', fixed);
console.log(JSON.stringify(report, null, 2));
