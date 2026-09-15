# W2 CONTRACT — Drift apollo-core/config ↔ registry

**Worker:** W2 CONTRACT · **Coordinator:** Grok Bot · **Model:** Composer 2.5  
**Repo:** apollorio/a (`/workspace`) · **HEAD:** detached audit snapshot  
**Método:** Leitura estrutural de `apollo-core/config/{cpts,taxonomies,meta,tables,routes}.php` cruzada com capítulos registry `05`, `09-plugins/apollo-core` (`MASTER_REGISTRY`), `16-summary` (`quick_lookup`), `19-ssot-audit` (plugins fantasma). Sem PHP edits. Sem inventar slugs.

---

## files-read

### Config (escopo W2)

| Arquivo | Contagem extraída |
|---|---|
| `apollo-core/config/cpts.php` | **15** CPTs |
| `apollo-core/config/taxonomies.php` | **18** taxonomias |
| `apollo-core/config/meta.php` | **109** meta keys (lookup; não é o registrador) |
| `apollo-core/config/tables.php` | **50** tabelas |
| `apollo-core/config/routes.php` | **187** rotas REST (`apollo/v1`) |

### Config siblings (presentes, fora do escopo de diff)

`constants.php`, `hooks.php`, `options.php`, `registry.php`, `roles.php`

### Registry chapters

| Capítulo | Uso |
|---|---|
| `_inventory/registry/05-global-architecture.json` | Claims legados (10 CPT / 13 tax / 22 tables) |
| `_inventory/registry/09-plugins/apollo-core.json` | `MASTER_REGISTRY`, `tables[]` |
| `_inventory/registry/16-summary.json` | `quick_lookup.all_*` (union auditada) |
| `_inventory/registry/19-ssot-audit.json` | Plugins sem pasta em disco |

---

## Resumo executivo

| Domínio | Em `config/` | Registry canônico (`16-summary`) | Drift principal |
|---|---:|---:|---|
| CPTs | 15 | 17 | 2 CPTs on-disk (journal) fora de `cpts.php`; `MASTER_REGISTRY` stale (10 + typo `loc`) |
| Taxonomias | 18 | 12 | 4 tax journal + 2 classified em config ausentes do summary; `MASTER_REGISTRY` usa `loc_*` em vez de `local_*` |
| Tabelas | 50 | 78 | Três SSOTs divergentes (`tables.php` ≠ `apollo-core.tables[]` ≠ summary) |
| Meta (`meta.php`) | 8 blocos CPT + `_global` + `page` | — | 7 CPTs declarados sem bloco; journal fora da camada |

**Owner plugin ausente em disco:** `supplier` → `apollo-suppliers` (registry entry fantasma desde 2026-07-28; CPT existe só via fallback do core).

---

## CPTs

### Inventário `config/cpts.php` (15)

`event`, `dj`, `track`, `hostel`, `local`, `classified`, `supplier`, `doc`, `email_aprio`, `hub`, `apollo_sheet`, `appointment`, `service`, `resource`, `apollo_agent_log`

### declared-not-on-disk (config → pasta owner)

| CPT | Owner em `cpts.php` | Pasta `plugins/` |
|---|---|---|
| `supplier` | `apollo-suppliers` | **AUSENTE** |

Todos os demais owners têm pasta `apollo-*` presente (41 plugins on-disk).

### declared-not-in-registry (config → registry)

#### vs `MASTER_REGISTRY.cpts` (`09-plugins/apollo-core.json`)

| Slug | Nota |
|---|---|
| `track` | Adicionado 2026-08-17; ausente do MASTER |
| `hostel` | Idem |
| `local` | MASTER lista `loc` (typo) em vez de `local` |
| `apollo_sheet` | Owner `apollo-sheets` |
| `appointment`, `service`, `resource` | Owner `apollo-scheduler` |
| `apollo_agent_log` | Owner `apollo-membership`; `apollo-membership.json` tem `cpts: []` |

#### vs `16-summary.quick_lookup.all_cpt_slugs`

**Nenhum** — os 15 slugs de config estão no summary (17 total).

#### vs union `cpts[]` dos capítulos `09-plugins/*`

| Slug | Nota |
|---|---|
| `apollo_agent_log` | Declarado em config; nenhum capítulo de plugin lista ownership |

### on-disk-not-declared (registry / disco → config)

#### vs `MASTER_REGISTRY` e `16-summary` e plugin chapters

| Slug | Onde vive | Em `cpts.php`? |
|---|---|---|
| `journal_news` | `apollo-journal` (on-disk) | **NÃO** |
| `journal_nota` | `apollo-journal` (on-disk) | **NÃO** |
| `loc` | Só em `MASTER_REGISTRY` | **NÃO** (typo; CPT real é `local`) |

---

## Taxonomias

### Inventário `config/taxonomies.php` (18)

Core bridge (14): `sound`, `season`, `event_category`, `event_type`, `event_tag`, `local_type`, `local_area`, `classified_domain`, `classified_intent`, `doc_folder`, `doc_type`, `supplier_category`, `supplier_service`, `coauthor`

Journal (4): `music`, `culture`, `rio`, `formato` — owner `apollo-journal`, `object_types: [post]`

### declared-not-in-registry

#### vs `MASTER_REGISTRY.taxonomies` (14 slugs)

| Em config, ausente / errado no MASTER | Detalhe |
|---|---|
| `local_type`, `local_area` | MASTER tem `loc_type`, `loc_area` (typo) |
| `music`, `culture`, `rio`, `formato` | Journal; MASTER ignora |

#### vs `16-summary.quick_lookup.all_taxonomy_slugs` (12 slugs)

| Em config, ausente no summary |
|---|
| `classified_domain` |
| `classified_intent` |
| `music`, `culture`, `rio`, `formato` |

### on-disk-not-declared

#### vs `MASTER_REGISTRY`

| No MASTER, ausente em config |
|---|
| `loc_type` → substituído por `local_type` |
| `loc_area` → substituído por `local_area` |

#### vs `16-summary`

**Nenhum** — summary é subconjunto de config.

### declared-not-on-disk

Taxonomias journal (`music`, `culture`, `rio`, `formato`) estão em config com owner `apollo-journal`; **plugin on-disk**. Não há taxonomias declaradas para plugins fantasma.

---

## Meta (`config/meta.php`)

> Arquivo é **lookup table** (`ApolloMeta::`); registrador real é `MetaRegistry.php`. O próprio arquivo documenta drift interno (11 `dj` / 13 `local` keys vs 38/27 no registrador).

### Blocos CPT em `meta.php`

`event`, `dj`, `local`, `classified`, `supplier`, `doc`, `hub`, `email_aprio`, `_global`, `page`

### CPTs em `cpts.php` sem bloco em `meta.php`

| CPT |
|---|
| `track` |
| `hostel` |
| `apollo_sheet` |
| `appointment` |
| `service` |
| `resource` |
| `apollo_agent_log` |

### on-disk-not-declared (meta)

| CPT on-disk | Plugin | Em `meta.php`? |
|---|---|---|
| `journal_news` | apollo-journal | **NÃO** |
| `journal_nota` | apollo-journal | **NÃO** |

Meta keys do journal existem no capítulo `09-plugins/apollo-journal.json` (`_apollo_featured`, `_nrep_code`, …) mas fora de `config/meta.php`.

---

## Tabelas (`config/tables.php`)

### declared-not-in-registry

#### vs `09-plugins/apollo-core.json` → `tables[]` (23 slugs)

40 tabelas em `tables.php` ausentes do array `tables` do capítulo apollo-core, incluindo: `apollo_achievements`, `apollo_activity`, `apollo_chat_*` (8), `apollo_connections`, `apollo_doc_*`, `apollo_favs`, `apollo_gestor_*` (5), `apollo_group_meta`, `apollo_holidays`, `apollo_industry_calendar`, `apollo_membership_log`, `apollo_mod_*`, `apollo_notif_prefs`, `apollo_points`, `apollo_quiz_results`, `apollo_ranks`, `apollo_signature_*`, `apollo_simon_scores`, `apollo_stats_*`, `apollo_steps`, `apollo_triggers`, `apollo_url_rewrites`, `apollo_user_appointments`, `apollo_wow*`.

#### vs `16-summary.quick_lookup.all_table_names` (78 slugs)

6 em config ausentes do summary: `apollo_holidays`, `apollo_membership_log`, `apollo_quiz_results`, `apollo_simon_scores`, `apollo_url_rewrites`, `apollo_user_appointments`.

### on-disk-not-declared

#### vs `apollo-core.tables[]`

`apollo_blocks`, `apollo_email_verifications`, `apollo_favorites`, `apollo_follows`, `apollo_lockouts`, `apollo_mod_actions`, `apollo_mod_reports`, `apollo_quiz_sessions`, `apollo_registry_state`, `apollo_remind_telegram`, `apollo_settings`, `apollo_statistics`, `apollo_wow_reactions`

#### vs `16-summary` (34 extras)

Inclui tabelas de plugins on-disk cujo owner não está em `tables.php`: `apollo_event_rsvp`, `apollo_remind_*`, `apollo_telegram_verif`, `apollo_group_bans`, `apollo_social_posts`, `apollo_newsletter_subscribers`, etc.

### declared-not-on-disk (owner plugin)

| Tabela em `tables.php` | Owner | Pasta |
|---|---|---|
| `apollo_industry_calendar` | `apollo-cena` | **AUSENTE** |

---

## Rotas (`config/routes.php`)

187 endpoints declarados. Capítulo `14-routing.json` não foi diff linha-a-linha neste worker; rotas referenciam owners fantasma:

| Prefixo | Owner | Pasta |
|---|---|---|
| `/suppliers/*` | `apollo-suppliers` | AUSENTE |
| `/cena/*` | `apollo-cena` | AUSENTE |
| `/shortcodes/*`, `/search`, `/newsletter/subscribe` | `apollo-shortcodes` | AUSENTE |
| `/pwa/*` | `apollo-pwa` | AUSENTE |

---

## Stale claims (registry narrative)

| Fonte | Afirma | Real em `config/` |
|---|---|---|
| `05-global-architecture` | 10 CPTs, 13 tax, 22 tables | 15 / 18 / 50 |
| `MASTER_REGISTRY` header | 9 CPTs no docblock `cpts.php` | 15 keys |
| `MASTER_REGISTRY.cpts` | inclui `loc`, `journal_*`; omite 8 slugs scheduler/sheets/track/hostel/agent_log | ver tabelas acima |

---

## Veredito

1. **`cpts.php` é o SSOT operacional** para 15 CPTs; journal (2) vive fora da camada de declaração.
2. **`MASTER_REGISTRY` em `09-plugins/apollo-core.json` está materialmente defasado** (`loc` typo, contagem 10, omissões pós-2026-08).
3. **`16-summary.quick_lookup` está mais alinhado com config** para CPTs (17 = 15 + 2 journal), mas **subdeclara taxonomias** (12 vs 18) e **superset de tabelas** (78 vs 50).
4. **Um CPT com owner fantasma:** `supplier` / `apollo-suppliers`.
5. **Uma tabela com owner fantasma:** `apollo_industry_calendar` / `apollo-cena`.
6. **`meta.php` cobre 8/15 CPTs**; drift com `MetaRegistry.php` já auto-documentado no arquivo.

Nenhuma correção aplicada (escopo read-only).
