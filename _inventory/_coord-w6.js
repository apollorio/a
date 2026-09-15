const fs = require("fs");
const path = require("path");
const root = "D:/dev/_apollo.rio.br/plugins";
const outDir = path.join(root, "_inventory/_worker-reports");
const reg = JSON.parse(fs.readFileSync(path.join(root, "_inventory/apollo-registry.json"), "utf8"));
const layers = reg.architecture && reg.architecture.layers || {};
const layerOf = {};
for (const [layer, val] of Object.entries(layers)) {
  const list = Array.isArray(val) ? val : (val && val.plugins) || [];
  for (const slug of list) layerOf[slug] = layer;
}
const skip = new Set(["apollo-waha"]);
const dirs = fs.readdirSync(root, { withFileTypes: true })
  .filter(d => d.isDirectory() && d.name.startsWith("apollo-") && !skip.has(d.name))
  .map(d => d.name)
  .sort();

function pickPlugin(slug) {
  const p = (reg.plugins && reg.plugins[slug]) || {};
  const cpts = p.cpts || p.CPT || p.post_types || [];
  const rest = p.rest_prefixes || p.rest || p.rest_namespace || p.namespaces || [];
  const norm = (v) => {
    if (!v) return [];
    if (Array.isArray(v)) return v.map(x => typeof x === "string" ? x : (x.slug || x.name || x.prefix || null)).filter(Boolean);
    if (typeof v === "object") return Object.keys(v);
    if (typeof v === "string") return [v];
    return [];
  };
  return {
    slug,
    status: reg.plugins && reg.plugins[slug] ? (p.status || "active") : "on_disk_not_in_reg",
    layer: layerOf[slug] || p.layer || null,
    cpts: norm(cpts),
    rest_prefixes: norm(rest)
  };
}

const slim = {
  $philosophy: reg.$philosophy || {},
  architecture: { layers },
  plugins: Object.fromEntries(dirs.map(s => [s, pickPlugin(s)])),
  namingRules: reg.namingRules || {},
  constants: reg.constants || {},
  quick_lookup: reg.quick_lookup || {},
  cdn: reg.cdn || {}
};

// strip heavy nested junk from philosophy if huge
const json = JSON.stringify(slim);
let out = slim;
if (Buffer.byteLength(json) > 80 * 1024) {
  // shrink quick_lookup and philosophy extras
  out = {
    $philosophy: {
      FORBIDDEN_CONCEPTS: (reg.$philosophy && reg.$philosophy.FORBIDDEN_CONCEPTS) || [],
      WOW_NOT_LIKE: reg.$philosophy && reg.$philosophy.WOW_NOT_LIKE,
      NO_EGO_COUNTERS: reg.$philosophy && reg.$philosophy.NO_EGO_COUNTERS,
      NO_SELECTIVE_FOLLOW: reg.$philosophy && reg.$philosophy.NO_SELECTIVE_FOLLOW,
      NO_FRIENDS_HIERARCHY: reg.$philosophy && reg.$philosophy.NO_FRIENDS_HIERARCHY
    },
    architecture: { layers },
    plugins: Object.fromEntries(dirs.map(s => {
      const x = pickPlugin(s);
      return [s, { slug: x.slug, status: x.status, layer: x.layer, cpts: x.cpts.slice(0,20), rest_prefixes: x.rest_prefixes.slice(0,20) }];
    })),
    namingRules: reg.namingRules || {},
    constants: typeof reg.constants === "object" ? Object.fromEntries(Object.entries(reg.constants).slice(0,80)) : reg.constants,
    quick_lookup: typeof reg.quick_lookup === "object" ? Object.fromEntries(Object.entries(reg.quick_lookup).slice(0,50)) : {},
    cdn: reg.cdn || {}
  };
}

const draftPath = path.join(outDir, "W6-slim.draft.json");
const body = JSON.stringify(out, null, 2);
fs.writeFileSync(draftPath, body);
const bytes = Buffer.byteLength(body);
fs.writeFileSync(path.join(outDir, "W6-slim.md"), `# W6 SLIM\n- path: ${draftPath}\n- bytes: ${bytes}\n- max: 81920\n- keys: ${Object.keys(out).join(", ")}\n- plugins: ${dirs.length}\n- chapters/_slim.draft.json NOT written (chapters dir absent; coordinator rule)\n`);
console.log(JSON.stringify({ bytes, keys: Object.keys(out), plugins: dirs.length, under80k: bytes <= 81920 }, null, 2));