const fs = require("fs");
const path = require("path");
const root = "D:/dev/_apollo.rio.br/plugins";
const cfg = path.join(root, "apollo-core/config");
const outDir = path.join(root, "_inventory/_worker-reports");
fs.mkdirSync(outDir, { recursive: true });

function read(p){ try { return fs.readFileSync(p,"utf8"); } catch(e){ return null; } }
function extractPhpSymbols(src, patterns){
  const hits = [];
  if (!src) return hits;
  const lines = src.split(/\r?\n/);
  lines.forEach((line,i)=>{
    for (const re of patterns){
      const m = line.match(re);
      if (m) hits.push({line:i+1, match:m[1]||m[0], raw:line.trim().slice(0,200)});
    }
  });
  return hits;
}

const files = ["cpts.php","taxonomies.php","meta.php","tables.php","routes.php"];
const readFiles = [];
const declared = {cpts:[], tax:[], meta:[], tables:[], routes:[]};
for (const f of files){
  const p = path.join(cfg,f);
  const src = read(p);
  if (src==null){ readFiles.push(f+": MISSING"); continue; }
  readFiles.push(f+": "+src.length+" bytes");
  if (f==="cpts.php"){
    // common patterns: 'apollo_xxx' => or register keys
    const re = /['\"]([a-z0-9_-]+)['\"]\s*=>/gi;
    let m; while((m=re.exec(src))){ if(!m[1].startsWith("label") && m[1].length>2) declared.cpts.push(m[1]); }
    const re2 = /['\"]post_type['\"]\s*=>\s*['\"]([^'\"]+)['\"]/gi;
    while((m=re2.exec(src))) declared.cpts.push(m[1]);
    const re3 = /register_post_type\s*\(\s*['\"]([^'\"]+)['\"]/gi;
    while((m=re3.exec(src))) declared.cpts.push(m[1]);
  }
  if (f==="taxonomies.php"){
    let m; const re = /register_taxonomy\s*\(\s*['\"]([^'\"]+)['\"]/gi;
    while((m=re.exec(src))) declared.tax.push(m[1]);
    const re2 = /['\"]([a-z0-9_-]+)['\"]\s*=>/gi;
    while((m=re2.exec(src))) { if(m[1].includes("tax")||m[1].startsWith("apollo_")||m[1].length>3) declared.tax.push(m[1]); }
  }
  if (f==="meta.php"){
    let m; const re = /register_post_meta\s*\(\s*['\"][^'\"]*['\"]\s*,\s*['\"]([^'\"]+)['\"]/gi;
    while((m=re.exec(src))) declared.meta.push(m[1]);
    const re2 = /['\"](_?[a-z0-9_]+)['\"]\s*=>/gi;
    while((m=re2.exec(src))) { if(m[1].startsWith("_")||m[1].includes("apollo")||m[1].startsWith("meta")) declared.meta.push(m[1]); }
  }
  if (f==="tables.php"){
    let m; const re = /['\"]([a-z0-9_]+)['\"]\s*=>/gi;
    while((m=re.exec(src))) declared.tables.push(m[1]);
    const re2 = /CREATE TABLE[^`]*`([^`]+)`/gi;
    while((m=re2.exec(src))) declared.tables.push(m[1]);
  }
  if (f==="routes.php"){
    let m; const re = /['\"](\/[a-z0-9_\-\/{}]+)['\"]/gi;
    while((m=re.exec(src))) declared.routes.push(m[1]);
    const re2 = /namespace['\"]?\s*=>\s*['\"]([^'\"]+)['\"]/gi;
    while((m=re2.exec(src))) declared.routes.push("ns:"+m[1]);
  }
}
const uniq = a => [...new Set(a)].sort();
declared.cpts = uniq(declared.cpts);
declared.tax = uniq(declared.tax);
declared.meta = uniq(declared.meta).slice(0,500);
declared.tables = uniq(declared.tables);
declared.routes = uniq(declared.routes);

// REG targeted: collect cpts from plugins
const reg = JSON.parse(fs.readFileSync(path.join(root,"_inventory/apollo-registry.json"),"utf8"));
const regCpts = new Set();
const regTax = new Set();
const regMeta = new Set();
for (const [slug,p] of Object.entries(reg.plugins||{})){
  const push = (v,set)=>{
    if(!v) return;
    if(Array.isArray(v)) v.forEach(x=>set.add(typeof x==="string"?x:(x.slug||x.name||JSON.stringify(x))));
    else if(typeof v==="object") Object.keys(v).forEach(k=>set.add(k));
  };
  push(p.cpts||p.CPT||p.post_types, regCpts);
  push(p.taxonomies||p.tax, regTax);
  push(p.meta||p.meta_keys, regMeta);
}

const onDiskDirs = fs.readdirSync(root,{withFileTypes:true}).filter(d=>d.isDirectory()&&d.name.startsWith("apollo-")&&d.name!=="apollo-waha").map(d=>d.name);

const md = [];
md.push("# W2 CONTRACT");
md.push("");
md.push("## files-read");
readFiles.forEach(x=>md.push("- "+x));
md.push("");
md.push("## core config symbol counts");
md.push(`- cpts keys/symbols: ${declared.cpts.length}`);
md.push(`- tax symbols: ${declared.tax.length}`);
md.push(`- meta symbols (capped extract): ${declared.meta.length}`);
md.push(`- tables symbols: ${declared.tables.length}`);
md.push(`- routes symbols: ${declared.routes.length}`);
md.push("");
md.push("## core CPT-like symbols (sample/full)");
md.push("```");
md.push(declared.cpts.join("\n")||"(none parsed)");
md.push("```");
md.push("");
md.push("## REG plugin CPT keys collected");
md.push("```");
md.push([...regCpts].sort().join("\n")||"(none on plugin objects)");
md.push("```");
md.push("");
md.push("## declared-not-on-disk (REG plugins missing folders)");
const missing = Object.keys(reg.plugins||{}).filter(s=>!onDiskDirs.includes(s)&&s!=="apollo-ui").sort();
// actually: plugins in REG not on disk
const missingDisk = Object.keys(reg.plugins||{}).filter(s=>!fs.existsSync(path.join(root,s))).sort();
missingDisk.forEach(s=>md.push("- "+s));
md.push("");
md.push("## on-disk-not-declared (apollo-* dirs absent from REG.plugins)");
onDiskDirs.filter(s=>!(reg.plugins||{})[s]).forEach(s=>md.push("- "+s));
md.push("");
md.push("## notes");
md.push("- Heuristic PHP parse only; Coordinator should treat ambiguous keys as DRIFT not patches.");
md.push("- No PHP edits made.");
fs.writeFileSync(path.join(outDir,"W2-contract.md"), md.join("\n"));
fs.writeFileSync(path.join(outDir,"W2-contract.json"), JSON.stringify({declared, regCpts:[...regCpts], regTax:[...regTax], missingDisk, onDiskNotInReg: onDiskDirs.filter(s=>!(reg.plugins||{})[s])}, null, 2));
console.log("W2_OK", declared.cpts.length, missingDisk.length);