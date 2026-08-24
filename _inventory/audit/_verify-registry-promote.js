'use strict';
const fs = require('fs');
const path = 'D:/dev/_apollo.rio.br/plugins/_inventory/';
const r = JSON.parse(fs.readFileSync(path + 'apollo-registry.json', 'utf8'));
const r2 = JSON.parse(fs.readFileSync(path + 'apollo-registry-2.json', 'utf8'));
console.log('audit.status', r.$audit && r.$audit.status);
console.log('output_policy', r.$audit && r.$audit.output_policy);
console.log('remediation keys', r.$audit && Object.keys(r.$audit.remediations || {}));
console.log(
  'dashboard widgets',
  (r.plugins['apollo-dashboard'].rest || []).find((x) =>
    String(x.endpoint).includes('widgets')
  )
);
console.log(
  'pane status',
  (r.plugins['apollo-core'].rest || []).find((x) =>
    String(x.endpoint).includes('pane-mode/status')
  )
);
console.log('users rem', r.plugins['apollo-users'].security_remediation);
console.log('templates rem', r.plugins['apollo-templates'].security_remediation);
console.log('templates rest', (r.plugins['apollo-templates'].rest || []).map((x) => x.endpoint));
console.log('summary.remediation_pass', r.summary.remediation_pass);
console.log(
  'sizes equal',
  fs.statSync(path + 'apollo-registry.json').size ===
    fs.statSync(path + 'apollo-registry-2.json').size
);
console.log(
  'generated match',
  r.$generated,
  r2.$generated,
  r.$generated === r2.$generated
);
