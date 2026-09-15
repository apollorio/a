const fs=require("fs");const path=require("path");
const reg=JSON.parse(fs.readFileSync("D:/dev/_apollo.rio.br/plugins/_inventory/apollo-registry.json","utf8"));
const phil=reg.$philosophy||{};
const out={
  FORBIDDEN_CONCEPTS: phil.FORBIDDEN_CONCEPTS||phil.forbidden_concepts||phil.forbidden||null,
  philosophyKeys: Object.keys(phil),
  namingRules: reg.namingRules||null,
  apollo_rule: reg.$apollo_rule||null
};
fs.writeFileSync("D:/dev/_apollo.rio.br/plugins/_inventory/_worker-reports/W5-reg-excerpts.json", JSON.stringify(out,null,2));
console.log("keys", out.philosophyKeys);
console.log("forbidden_type", typeof out.FORBIDDEN_CONCEPTS, Array.isArray(out.FORBIDDEN_CONCEPTS)?out.FORBIDDEN_CONCEPTS.length: "");