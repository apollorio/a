'use strict';
const fs = require('fs');
const path = require('path');
const root = 'D:/dev/_apollo.rio.br/plugins';
const slugs = ['apollo-pane-engine', 'apollo-email', 'apollo-dj-sync', 'apollo-wow'];

function walk(d, a = [], n = 0) {
  if (n > 10) return a;
  for (const e of fs.readdirSync(d, { withFileTypes: true })) {
    const p = path.join(d, e.name);
    if (e.isDirectory()) {
      if (['vendor', 'node_modules'].includes(e.name)) continue;
      walk(p, a, n + 1);
    } else if (e.name.endsWith('.php')) a.push(p);
  }
  return a;
}

const guardRe =
  /\n?if\s*\(\s*!\s*defined\s*\(\s*['"]ABSPATH['"]\s*\)\s*\)\s*\{\s*\n\s*exit;\s*\n\s*\}\s*\n?/;

for (const s of slugs) {
  for (const f of walk(path.join(root, s))) {
    let t = fs.readFileSync(f, 'utf8');
    const di = t.search(/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/);
    const ai = t.search(/defined\s*\(\s*['"]ABSPATH['"]/);
    if (di < 0 || ai < 0 || ai >= di) continue;

    console.log('fixing order', path.relative(root, f));

    // Remove guard block
    const withoutGuard = t.replace(guardRe, '\n');
    // Find declare line end
    const m = withoutGuard.match(/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/);
    if (!m) continue;
    const declareEnd = withoutGuard.indexOf(m[0]) + m[0].length;

    // After namespace + uses if present
    let insertAt = declareEnd;
    const afterDeclare = withoutGuard.slice(declareEnd);
    const ns = afterDeclare.match(/^\s*namespace\s+[^;]+;/);
    if (ns) insertAt += ns[0].length;
    let rest = withoutGuard.slice(insertAt);
    while (true) {
      const u = rest.match(/^\s*use\s+[^;]+;/);
      if (!u) break;
      insertAt += u[0].length;
      rest = withoutGuard.slice(insertAt);
    }

    const guard = "\n\nif ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n\n";
    const fixed =
      withoutGuard.slice(0, insertAt).replace(/\s*$/, '') +
      guard +
      withoutGuard.slice(insertAt).replace(/^\s*/, '');
    fs.writeFileSync(f, fixed, 'utf8');
  }
}
console.log('done');
