const fs = require("fs");
const path = require("path");
const root = "D:/dev/_apollo.rio.br";
const plugins = path.join(root, "plugins");
const outDir = path.join(plugins, "_inventory/_worker-reports");
fs.mkdirSync(outDir, { recursive: true });
const reg = JSON.parse(fs.readFileSync(path.join(plugins,"_inventory/apollo-registry.json"),"utf8"));
const mu = reg.$mu_plugins || reg.mu_plugins || null;

function walkFind(start, names, maxDepth=4){
  const hits=[];
  function rec(dir, depth){
    if(depth>maxDepth) return;
    let ents; try{ ents=fs.readdirSync(dir,{withFileTypes:true}); }catch{ return; }
    for(const e of ents){
      const p=path.join(dir,e.name);
      if(names.includes(e.name)) hits.push(p);
      if(e.isDirectory() && !e.name.startsWith(".") && e.name!=="node_modules" && e.name!=="_to_delete" && e.name!=="superseeded") rec(p, depth+1);
    }
  }
  rec(start,0);
  return hits;
}

const brainHits = walkFind(root, ["apollo-brain.php"], 5);
const muDirs = walkFind(root, ["mu-plugins"], 4).filter(p=>fs.statSync(p).isDirectory());

const md=[];
md.push("# W4 BOOT");
md.push("");
md.push("## REG.$mu_plugins");
md.push("```json");
md.push(JSON.stringify(mu,null,2)?.slice(0,8000) || "null");
md.push("```");
md.push("");
md.push("## apollo-brain.php candidates");
brainHits.forEach(p=>md.push("- "+p));
if(!brainHits.length) md.push("- (not found under D:\\dev\\_apollo.rio.br within depth)");
md.push("");
for(const bp of brainHits.slice(0,3)){
  const src=fs.readFileSync(bp,"utf8");
  const lines=src.split(/\r?\n/).slice(0,80);
  md.push("### headers/constants from "+bp);
  md.push("```php");
  lines.filter(l=>/APOLLO_|define\s*\(|require|mu-plugin|REGISTRY/i.test(l)).slice(0,60).forEach(l=>md.push(l));
  md.push("```");
  const m=src.match(/APOLLO_REGISTRY_PATH[^;]+;/);
  if(m) md.push("APOLLO_REGISTRY_PATH snippet: `"+m[0].replace(/`/g,"'")+"`");
}
md.push("");
md.push("## mu-plugins dirs");
muDirs.forEach(d=>{
  md.push("### "+d);
  try{
    fs.readdirSync(d).forEach(f=>md.push("- "+f));
  }catch(e){ md.push("- err "+e.message); }
});
md.push("");
md.push("## plugins-root drift (report only)");
["force-load-apollo-events.php","apollo-events-helpers-guard.php","debug-161c5c.log","debug-e031aa.log"].forEach(f=>{
  const p=path.join(plugins,f);
  md.push(`- ${f}: ${fs.existsSync(p)?"PRESENT":"absent"}`);
});
md.push("");
md.push("## conclusion");
md.push("- Runtime registry path must remain wp-content/apollo-registry.json, NOT plugins/_inventory/apollo-registry.json monolith.");
md.push("- force-load-* / *-guard.php / debug logs = DRIFT temporary, do not inventory as features.");
md.push("- No edits made.");
fs.writeFileSync(path.join(outDir,"W4-boot.md"), md.join("\n"));
console.log("W4_OK", "brains="+brainHits.length, "muDirs="+muDirs.length);