const fs = require("fs");
const p = "D:/dev/_apollo.rio.br/plugins/_inventory/apollo-registry.json";
const j = JSON.parse(fs.readFileSync(p, "utf8"));
const layers = j.architecture && j.architecture.layers;
const out = {
  topKeys: Object.keys(j),
  pluginKeys: Object.keys(j.plugins || {}).sort(),
  layerIsArray: Array.isArray(layers),
  layerKeys: layers && !Array.isArray(layers) ? Object.keys(layers) : null,
  layersPreview: Array.isArray(layers) ? layers.slice(0, 50) : null,
  hasDollarPhilosophy: !!j.$philosophy,
  hasPhilosophy: !!j.philosophy,
  namingRules: !!j.namingRules,
  constants: !!j.constants,
  quick_lookup: !!j.quick_lookup,
  cdn: !!j.cdn
};
if (layers && !Array.isArray(layers)) {
  out.layerSummary = {};
  for (const [k, v] of Object.entries(layers)) {
    if (Array.isArray(v)) out.layerSummary[k] = v;
    else if (v && typeof v === "object" && Array.isArray(v.plugins)) out.layerSummary[k] = v.plugins;
    else if (v && typeof v === "object") out.layerSummary[k] = Object.keys(v);
    else out.layerSummary[k] = typeof v;
  }
}
fs.writeFileSync("D:/dev/_apollo.rio.br/plugins/_inventory/_coord-probe.json", JSON.stringify(out, null, 2));
console.log(JSON.stringify({
  topKeys: out.topKeys,
  pluginCount: out.pluginKeys.length,
  pluginKeys: out.pluginKeys,
  layerIsArray: out.layerIsArray,
  layerKeys: out.layerKeys,
  layersPreview: out.layersPreview,
  layerSummary: out.layerSummary,
  flags: {
    hasDollarPhilosophy: out.hasDollarPhilosophy,
    hasPhilosophy: out.hasPhilosophy,
    namingRules: out.namingRules,
    constants: out.constants,
    quick_lookup: out.quick_lookup,
    cdn: out.cdn
  }
}, null, 2));