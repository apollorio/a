'use strict';
const fs = require('fs');
const path = require('path');

const ROOT = 'D:/dev/_apollo.rio.br/plugins';
const SKIP = new Set(['vendor', 'node_modules', '.git', 'tests', 'test']);

function walk(dir, acc = [], depth = 0) {
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
      if (SKIP.has(e.name.toLowerCase())) continue;
      walk(p, acc, depth + 1);
    } else if (/\.php$/i.test(e.name)) acc.push(p);
  }
  return acc;
}

function hasAbspath(t) {
  return /defined\s*\(\s*['"]ABSPATH['"]\s*\)|ABSPATH/.test(t.slice(0, 800));
}

const abspathTargets = [
  'apollo-pane-engine',
  'apollo-email',
  'apollo-dj-sync',
  'apollo-wow',
];
const prepareTargets = ['apollo-docs', 'apollo-hub', 'apollo-fav', 'apollo-mod'];

console.log('=== ABSPATH missing files ===');
for (const slug of abspathTargets) {
  const files = walk(path.join(ROOT, slug));
  const missing = [];
  for (const f of files) {
    const t = fs.readFileSync(f, 'utf8');
    if (!hasAbspath(t)) missing.push(path.relative(path.join(ROOT, slug), f));
  }
  console.log(
    '\n' + slug + ':',
    missing.length + '/' + files.length,
    'missing'
  );
  missing.slice(0, 40).forEach((m) => console.log('  ', m));
}

console.log('\n=== Raw $wpdb without prepare nearby ===');
for (const slug of prepareTargets) {
  const files = walk(path.join(ROOT, slug));
  console.log('\n' + slug);
  for (const f of files) {
    const t = fs.readFileSync(f, 'utf8');
    const lines = t.split(/\n/);
    lines.forEach((line, i) => {
      if (!/\$wpdb\s*->\s*(query|get_var|get_row|get_col|get_results|prepare)\s*\(/.test(line))
        return;
      if (/\$wpdb\s*->\s*prepare/.test(line)) return;
      // flag if line has variable interpolation without prepare on same or prev 3 lines
      const window = lines.slice(Math.max(0, i - 3), i + 1).join('\n');
      if (/\$wpdb\s*->\s*prepare/.test(window)) return;
      if (/["'`].*\$/.test(line) || /\.\s*\$/.test(line) || /\{\$/.test(line)) {
        console.log(
          '  ' + path.relative(path.join(ROOT, slug), f) + ':' + (i + 1),
          line.trim().slice(0, 140)
        );
      }
    });
  }
}
