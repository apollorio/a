'use strict';
const fs = require('fs');
const path = require('path');
const root = 'D:/dev/_apollo.rio.br/plugins';
const samples = [
  'apollo-core',
  'apollo-login',
  'apollo-events',
  'apollo-users',
  'apollo-chat',
  'apollo-calendar',
  'apollo-membership',
  'apollo-admin',
];

function walk(d, a = [], n = 0) {
  if (n > 7) return a;
  let e;
  try {
    e = fs.readdirSync(d, { withFileTypes: true });
  } catch {
    return a;
  }
  for (const x of e) {
    const p = path.join(d, x.name);
    if (x.isDirectory()) {
      if (['vendor', 'node_modules', '.git', 'tests', 'test'].includes(x.name.toLowerCase()))
        continue;
      walk(p, a, n + 1);
    } else if (/\.php$/i.test(x.name)) a.push(p);
  }
  return a;
}

const patterns = {
  register_post_meta: /register_post_meta\s*\(/g,
  register_meta: /register_meta\s*\(/g,
  register_term_meta: /register_term_meta\s*\(/g,
  register_user_meta: /register_meta\s*\(\s*['"]user['"]/g,
  add_rewrite_rule: /add_rewrite_rule\s*\(/g,
  add_rewrite_tag: /add_rewrite_tag\s*\(/g,
  query_vars: /query_vars/g,
  dbDelta: /dbDelta\s*\(/g,
  CREATE_TABLE: /CREATE\s+TABLE/gi,
  add_menu_page: /add_(?:menu|submenu|options)_page\s*\(/g,
  wp_enqueue: /wp_enqueue_(?:script|style)\s*\(/g,
  register_widget: /register_widget\s*\(/g,
  current_user_can: /current_user_can\s*\(/g,
  wp_verify_nonce: /wp_verify_nonce\s*\(/g,
  check_ajax_referer: /check_ajax_referer\s*\(/g,
  permission_callback: /permission_callback/g,
  __return_true: /__return_true/g,
  do_action: /do_action(?:_ref_array)?\s*\(/g,
  apply_filters: /apply_filters(?:_ref_array)?\s*\(/g,
  add_shortcode: /add_shortcode\s*\(/g,
  register_setting: /register_setting\s*\(/g,
  update_option: /update_option\s*\(/g,
  class_decl: /^\s*(?:abstract\s+|final\s+)?class\s+\w+/gm,
  interface_decl: /^\s*interface\s+\w+/gm,
  trait_decl: /^\s*trait\s+\w+/gm,
  prepare: /\$wpdb\s*->\s*prepare\s*\(/g,
  raw_get: /\$_GET\s*\[/g,
  raw_post: /\$_POST\s*\[/g,
  raw_request: /\$_REQUEST\s*\[/g,
  echo_: /\becho\s+/g,
  ABSPATH: /defined\s*\(\s*['"]ABSPATH['"]/g,
};

for (const s of samples) {
  const files = walk(path.join(root, s));
  const pat = {};
  for (const k of Object.keys(patterns)) pat[k] = 0;
  let sampleRest = [];
  let sampleMeta = [];
  let sampleRewrite = [];
  for (const f of files) {
    const t = fs.readFileSync(f, 'utf8');
    for (const [k, re] of Object.entries(patterns)) {
      re.lastIndex = 0;
      const m = t.match(re);
      if (m) pat[k] += m.length;
    }
    if (sampleMeta.length < 3) {
      const mm = t.match(/register_post_meta\s*\(\s*[^,]+,\s*['"]([^'"]+)['"]/g);
      if (mm) sampleMeta.push(...mm.slice(0, 2));
    }
    if (sampleRewrite.length < 3) {
      const mm = t.match(/add_rewrite_rule\s*\(\s*['"]([^'"]+)['"]/g);
      if (mm) sampleRewrite.push(...mm.slice(0, 2));
    }
  }
  console.log('\n===', s, 'php_files=', files.length, '===');
  console.log(JSON.stringify(pat, null, 0));
  if (sampleMeta.length) console.log('meta samples', sampleMeta.slice(0, 4));
  if (sampleRewrite.length) console.log('rewrite samples', sampleRewrite.slice(0, 4));
}
