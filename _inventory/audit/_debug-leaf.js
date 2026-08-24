'use strict';
const fs = require('fs');

function extractBalanced(content, openIdx) {
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

const php = fs.readFileSync(
  'D:/dev/_apollo.rio.br/plugins/apollo-events/src/API/EventsController.php',
  'utf8'
);
const re = /register_rest_route\s*\(/g;
let m;
let n = 0;
while ((m = re.exec(php)) && n < 4) {
  const call = extractBalanced(php, m.index + m[0].length - 1);
  const head = call.match(/['"](\/[^'"]+)['"]/);
  console.log('\nROUTE', head && head[1]);
  let i = 0;
  let leaves = 0;
  while (i < call.length) {
    const idx = call.indexOf('array', i);
    if (idx < 0) break;
    let j = idx + 5;
    while (j < call.length && /\s/.test(call[j])) j++;
    if (call[j] !== '(') {
      i = idx + 5;
      continue;
    }
    const block = extractBalanced(call, j);
    if (!block) break;
    const permCount = (block.match(/['"]permission_callback['"]/g) || []).length;
    const hasMethods = /['"]methods['"]|WP_REST_Server::/.test(block);
    if (permCount === 1 && hasMethods) {
      const methods = [...block.matchAll(/WP_REST_Server::(\w+)/g)].map((x) => x[1]);
      const perm = (block.match(/permission_callback['"]\s*=>\s*([^,\n]+)/) || [])[1];
      console.log(
        '  LEAF',
        methods,
        (perm || '').trim().slice(0, 70),
        'snippet',
        block.replace(/\s+/g, ' ').slice(0, 120)
      );
      leaves++;
    } else if (permCount > 1) {
      console.log('  PARENT perms', permCount);
    }
    i = j + block.length;
  }
  console.log('  leaves', leaves);
  n++;
}
