#!/usr/bin/env node
/**
 * W6 SLIM INDEX worker — builds runtime JSON draft from on-disk apollo-* +
 * targeted registry chapter reads. No monolith edits. Max 80 KB.
 */
'use strict';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '../..');
const REG = path.join(ROOT, '_inventory/registry');
const OUT_JSON = path.join(__dirname, 'W6-slim.draft.json');
const OUT_MD = path.join(__dirname, 'W6-slim.md');
const MAX_BYTES = 80 * 1024;

const read = (p) => JSON.parse(fs.readFileSync(p, 'utf8'));

/** Parse apollo-core/config/constants.php return array (no PHP binary). */
function parseCoreConstants() {
  const src = fs.readFileSync(path.join(ROOT, 'apollo-core/config/constants.php'), 'utf8');
  const out = {};
  const re = /'([A-Z][A-Z0-9_]+)'\s*=>\s*'([^']*)'/g;
  let m;
  while ((m = re.exec(src))) out[m[1]] = m[2];
  return out;
}

/** Build slug → layer from architecture.layers */
function layerMap(layers) {
  const map = {};
  for (const [layer, slugs] of Object.entries(layers)) {
    for (const slug of slugs) map[slug] = layer;
  }
  return map;
}

/** First path segment after namespace from REST endpoint, e.g. /chat/threads → chat */
function restPrefixes(restArr) {
  if (!Array.isArray(restArr)) return [];
  const set = new Set();
  for (const r of restArr) {
    const ep = r.endpoint || r.route || '';
    const seg = String(ep).replace(/^\/+/, '').split('/')[0];
    if (seg) set.add(seg);
  }
  return [...set].sort();
}

/** Slim cdn — runtime load contract only, drop nav_header_v6 doc block */
function slimCdn(cdn) {
  const keep = ['url', 'version', 'mandatory', 'load_priority', 'includes', 'global_objects', 'html_load', 'wp_enqueue'];
  const slim = {};
  for (const k of keep) if (cdn[k] !== undefined) slim[k] = cdn[k];
  return slim;
}

function onDiskSlugs() {
  return fs
    .readdirSync(ROOT)
    .filter((n) => n.startsWith('apollo-') && fs.statSync(path.join(ROOT, n)).isDirectory())
    .sort();
}

function loadPluginChapter(slug) {
  const p = path.join(REG, '09-plugins', `${slug}.json`);
  if (!fs.existsSync(p)) return null;
  const body = read(p);
  const { $chapter, ...entry } = body;
  return entry;
}

function build() {
  const philosophy = read(path.join(REG, '01-philosophy.json')).$philosophy;
  const arch = read(path.join(REG, '08-architecture-layers.json')).architecture;
  const layers = arch.layers;
  const layerBySlug = layerMap(layers);

  const conventions = read(path.join(REG, '15-conventions.json'));
  const summary = read(path.join(REG, '16-summary.json'));
  const cdnFull = read(path.join(REG, '06-cdn.json')).cdn;

  const disk = new Set(onDiskSlugs());
  const regDir = path.join(REG, '09-plugins');
  const regSlugs = fs
    .readdirSync(regDir)
    .filter((f) => f.endsWith('.json'))
    .map((f) => f.slice(0, -5))
    .sort();

  const allSlugs = [...new Set([...disk, ...regSlugs])].sort();
  const plugins = {};
  const stats = { on_disk: 0, reg_only_missing: 0, no_registry: 0 };

  for (const slug of allSlugs) {
    const onDisk = disk.has(slug);
    const chapter = loadPluginChapter(slug);

    if (!onDisk && chapter) {
      stats.reg_only_missing++;
      plugins[slug] = {
        slug,
        status: 'missing',
        layer: layerBySlug[slug] ?? null,
        cpts: chapter.cpts ?? [],
        rest_prefixes: restPrefixes(chapter.rest),
      };
      continue;
    }

    if (onDisk && !chapter) {
      stats.no_registry++;
      plugins[slug] = {
        slug,
        status: 'on_disk_unregistered',
        layer: layerBySlug[slug] ?? null,
        cpts: [],
        rest_prefixes: [],
      };
      continue;
    }

    if (onDisk && chapter) {
      stats.on_disk++;
      plugins[slug] = {
        slug,
        status: chapter.status ?? 'UNKNOWN',
        layer: chapter.layer ?? layerBySlug[slug] ?? null,
        cpts: chapter.cpts ?? chapter.MASTER_REGISTRY?.cpts ?? [],
        rest_prefixes: restPrefixes(chapter.rest),
      };
    }
  }

  const registryConstants = conventions.constants ?? {};
  const coreConstants = parseCoreConstants();
  const constants = {
    ...registryConstants,
    APOLLO_VERSION: coreConstants.APOLLO_VERSION ?? registryConstants.APOLLO_VERSION,
    APOLLO_CORE_VERSION: coreConstants.APOLLO_CORE_VERSION,
    APOLLO_MIN_WP: coreConstants.APOLLO_MIN_WP ?? registryConstants.APOLLO_MIN_WP,
    APOLLO_MIN_PHP: coreConstants.APOLLO_MIN_PHP ?? registryConstants.APOLLO_MIN_PHP,
    APOLLO_REST_NAMESPACE: coreConstants.APOLLO_REST_NAMESPACE ?? registryConstants.APOLLO_REST_NAMESPACE,
    APOLLO_TABLE_PREFIX: coreConstants.APOLLO_TABLE_PREFIX ?? registryConstants.APOLLO_TABLE_PREFIX,
    APOLLO_CDN_URL: coreConstants.APOLLO_CDN_URL ?? registryConstants.APOLLO_CDN_URL,
    APOLLO_CDN_VERSION: coreConstants.APOLLO_CDN_VERSION,
    APOLLO_REGISTRY_VER: coreConstants.APOLLO_REGISTRY_VER,
    APOLLO_META_PREFIX: coreConstants.APOLLO_META_PREFIX,
  };

  const slim = {
    $slim: {
      worker: 'W6',
      generated: new Date().toISOString().slice(0, 10),
      source: 'on-disk apollo-* + registry chapters + apollo-core/config/constants.php',
      max_bytes: MAX_BYTES,
    },
    $philosophy: philosophy,
    architecture: { layers },
    plugins,
    namingRules: conventions.namingRules,
    constants,
    quick_lookup: summary.quick_lookup,
    cdn: slimCdn(cdnFull),
  };

  let json = JSON.stringify(slim, null, 2) + '\n';
  let bytes = Buffer.byteLength(json, 'utf8');

  if (bytes > MAX_BYTES) {
    // Trim quick_lookup tables if over budget (keep prefixes + cpts + patterns)
    const ql = { ...slim.quick_lookup };
    delete ql.all_table_names;
    slim.quick_lookup = ql;
    json = JSON.stringify(slim, null, 2) + '\n';
    bytes = Buffer.byteLength(json, 'utf8');
  }

  fs.writeFileSync(OUT_JSON, json);

  const md = `# W6 SLIM INDEX — draft report

| Field | Value |
| --- | --- |
| Worker | W6 (SLIM INDEX) |
| Generated | ${slim.$slim.generated} |
| Output | \`W6-slim.draft.json\` |
| Byte size | **${bytes}** (${(bytes / 1024).toFixed(1)} KiB) |
| Budget | ${MAX_BYTES} bytes (80 KiB) |
| Within budget | ${bytes <= MAX_BYTES ? 'yes' : '**NO — trim required**'} |

## Coverage

| Metric | Count |
| --- | ---: |
| Plugins on disk | ${stats.on_disk} |
| REG-only (status \`missing\`) | ${stats.reg_only_missing} |
| On disk, no registry chapter | ${stats.no_registry} |
| Total plugin entries | ${Object.keys(plugins).length} |
| Architecture layers | ${Object.keys(layers).length} |

## Keys emitted

- \`$philosophy\`
- \`architecture.layers\`
- \`plugins.{slug,status,layer,cpts,rest_prefixes}\`
- \`namingRules\`
- \`constants\` (registry + live \`apollo-core/config/constants.php\`)
- \`quick_lookup\`
- \`cdn\` (runtime load contract; \`nav_header_v6\` omitted)

## REG-only missing (no folder on disk)

${allSlugs.filter((s) => !disk.has(s) && regSlugs.includes(s)).map((s) => `- \`${s}\``).join('\n') || '_none_'}

## On-disk without registry chapter

${allSlugs.filter((s) => disk.has(s) && !regSlugs.includes(s)).map((s) => `- \`${s}\``).join('\n') || '_none_'}

## Build

\`\`\`bash
node _inventory/_worker-reports/build-w6-slim.mjs
\`\`\`
`;

  fs.writeFileSync(OUT_MD, md);

  console.log(`W6 slim: ${bytes} bytes, ${Object.keys(plugins).length} plugins`);
  if (bytes > MAX_BYTES) {
    console.error(`OVER BUDGET by ${bytes - MAX_BYTES} bytes`);
    process.exit(1);
  }
}

build();
