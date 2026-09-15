const fs = require("fs");
const path = require("path");
const root = "D:/dev/_apollo.rio.br/plugins";
const reg = JSON.parse(fs.readFileSync(path.join(root, "_inventory/apollo-registry.json"), "utf8"));
const ignoreExact = new Set(["apollo-waha","elementor","loginizer","loginizer-security","query-monitor","really-simple-ssl","wp-debugging","_to_delete","superseeded","mcps","_dev-registry","_inventory"]);
const ignorePrefix = [".","elementor"];
const dirs = fs.readdirSync(root, { withFileTypes: true }).filter(d => d.isDirectory()).map(d => d.name);
const layerOf = {};
const layers = (reg.architecture && reg.architecture.layers) || {};
for (const [layer, val] of Object.entries(layers)) {
  const list = Array.isArray(val) ? val : (val && val.plugins) || [];
  for (const slug of list) layerOf[slug] = layer;
}
const regPlugins = new Set(Object.keys(reg.plugins || {}));
const diskApollo = new Set();
const rows = [];
for (const name of dirs.sort()) {
  if (ignoreExact.has(name) || name.startsWith(".") || name === "elementor" || name.startsWith("loginizer")) {
    if (name.startsWith("apollo-")) {
      rows.push([name, "yes", regPlugins.has(name) ? "yes" : "no", layerOf[name] || "", "SKIP"].join("\t"));
    }
    continue;
  }
  if (!name.startsWith("apollo-")) continue;
  diskApollo.add(name);
  const inReg = regPlugins.has(name);
  const layer = layerOf[name] || "";
  let status = "MATCH";
  if (!inReg) status = "MISSING_IN_REG";
  rows.push([name, "yes", inReg ? "yes" : "no", layer, status].join("\t"));
}
for (const slug of [...regPlugins].sort()) {
  if (diskApollo.has(slug)) continue;
  rows.push([slug, "no", "yes", layerOf[slug] || "", "MISSING_ON_DISK"].join("\t"));
}
const header = "slug\ton_disk\tin_registry\tlayer\tstatus";
const tsv = [header, ...rows].join("\n") + "\n";
const outDir = path.join(root, "_inventory/_worker-reports");
fs.writeFileSync(path.join(outDir, "W1-disk-map.tsv"), tsv);
const missingDisk = rows.filter(r => r.endsWith("MISSING_ON_DISK"));
const missingReg = rows.filter(r => r.endsWith("MISSING_IN_REG"));
const match = rows.filter(r => r.endsWith("\tMATCH"));
const md = [
  "# W1 disk map",
  "",
  `- MATCH: ${match.length}`,
  `- MISSING_ON_DISK: ${missingDisk.length}`,
  `- MISSING_IN_REG: ${missingReg.length}`,
  "",
  "## MISSING_ON_DISK",
  ...missingDisk.map(r => `- ${r.split("\t")[0]}`),
  "",
  "## MISSING_IN_REG",
  ...missingReg.map(r => `- ${r.split("\t")[0]}`),
  ""
].join("\n");
fs.writeFileSync(path.join(outDir, "W1-disk-map.md"), md);
console.log(md);
console.log("TSV_LINES", rows.length);