'use strict';
const fs = require('fs');
const path = 'D:/dev/_apollo.rio.br/plugins/_inventory/';
const reg2 = JSON.parse(fs.readFileSync(path + 'apollo-registry-2.json', 'utf8'));
const reg1stat = fs.statSync(path + 'apollo-registry.json');
const reg2stat = fs.statSync(path + 'apollo-registry-2.json');
console.log('registry.json mtime', reg1stat.mtime.toISOString(), 'size', reg1stat.size);
console.log('registry-2.json mtime', reg2stat.mtime.toISOString(), 'size', reg2stat.size);
console.log('output_policy', reg2.$audit && reg2.$audit.output_policy);
console.log('audit_status', reg2.$audit && reg2.$audit.status);
console.log('description_head', (reg2.$description || '').slice(0, 120));

const core = reg2.plugins['apollo-core'];
console.log('\nCORE rest sample:');
for (const r of core.rest.slice(0, 8)) {
  console.log(
    JSON.stringify({
      ep: r.endpoint,
      methods: r.methods,
      auth: r.auth,
      risk: r.permission_risk,
      bind: r.permission_binding,
      sum: r.permission_summary,
      caps: r.permission_capabilities,
    })
  );
}
console.log('CORE class counts', core._deep_audit.rest.effective_class_counts);
console.log('CORE admin_cap', core._deep_audit.rest.admin_cap_routes);
console.log('CORE open non-intent', core._deep_audit.rest.open_routes.filter((r) => !r.intentional_public));

const login = reg2.plugins['apollo-login'];
console.log('\nLOGIN class counts', login._deep_audit.rest.effective_class_counts);
console.log('LOGIN security', login.security);
console.log(
  'LOGIN open non-intentional',
  login._deep_audit.rest.open_routes.filter((r) => !r.intentional_public)
);

let crit = 0,
  intentional = 0,
  resolved = 0,
  unresolved = 0,
  highPlugins = [];
for (const s of Object.keys(reg2.plugins)) {
  const p = reg2.plugins[s];
  if (!p._deep_audit || !p._deep_audit.rest) continue;
  crit += p._deep_audit.rest.write_critical_public || 0;
  intentional += p._deep_audit.rest.intentional_public_count || 0;
  resolved += p._deep_audit.rest.permission_body_resolved || 0;
  unresolved += p._deep_audit.rest.permission_body_unresolved || 0;
  if (p.security && p.security.risk_band === 'HIGH') highPlugins.push(s + ':' + p.security.risk_score);
}
console.log('\nGLOBAL resolved', resolved, 'unresolved', unresolved);
console.log('write_critical_public', crit, 'intentional_public', intentional);
console.log('HIGH plugins', highPlugins);

// show a real ADMIN resolve example
const adminRoute = core.rest.find((r) => r.auth === 'ADMIN_CAPABILITY');
console.log('\nADMIN example', adminRoute);

// show method not found if any
const notFound = [];
for (const s of Object.keys(reg2.plugins)) {
  const p = reg2.plugins[s];
  for (const r of p.rest || []) {
    if (r.auth === 'METHOD_NOT_FOUND' || (r.permission_summary || '').includes('not found')) {
      notFound.push(s + ' ' + r.endpoint + ' ' + r.permission_method);
    }
  }
}
console.log('METHOD_NOT_FOUND count', notFound.length, notFound.slice(0, 10));
