# Apollo REST/Route Audit — English → Young Portuguese

**Date:** 2026-07-19  **Scope:** all page routes ("rests") in `apollo-registry.json`

## 1. Inventory result

~70 routes scanned. **Almost all slugs are already Portuguese** (acesso, registre,
sair, verificar-email, conquistas, pontos, niveis, placar, eventos, evento,
criar-evento, djs, local, mapa, anuncios, fornecedores, grupos, comunas, nucleos,
notificacoes, mensagens, documentos, assinar, jornal, sobre, casa, painel, ...).

### Kept in English (owner decision)
`reset`, `marketplace`, `feed`, `hub`, `offline` — kept as-is.
`radar` slug kept (word exists in PT). Legacy 301 redirects (`explore`, `about-us`,
`home`) left in place — translating them defeats their alias purpose.

## 2. Translated: `cult` → `cena` (industry-hidden area)

`cult` reads wrong in PT-BR. Renamed the entire industry-hidden concept to **`cena`**,
kept **strictly distinct** from the existing public **`cena-rio`** (apollo-events /
`CenaRio.php`, untouched).

| Surface | Before | After |
|---|---|---|
| Plugin key / slug | `apollo-cult` | `apollo-cena` |
| Namespace | `Apollo\Cult` | `Apollo\Cena` |
| Routes | `/cult`, `/cult/calendario`, `/cult/membros` | `/cena`, `/cena/calendario`, `/cena/membros` |
| REST | `/cult/calendar*`, `/cult/members`, `/cult/access/request` | `/cena/...` |
| Templates (spec) | `cult.php`, `cult-calendar.php`, `cult-members.php` | `cena.php`, `cena-calendar.php`, `cena-members.php` |
| User meta | `_apollo_cult_access`, `_apollo_cult_role` | `_apollo_cena_access`, `_apollo_cena_role` |
| Capability | `apollo_cult_access` | `apollo_cena_access` |
| Table owner | `apollo-cult` | `apollo-cena` |
| Shortcode | `apollo_cult_calendar` | `apollo_cena_calendar` |
| Config array | `cult_roles` | `cena_roles` |
| PHP consts | `USER_CULT_ACCESS/ROLE`, `ApolloRoute::CULT` | `USER_CENA_ACCESS/ROLE`, `ApolloRoute::CENA` |

### Naming rules reversed
`cult` is now the **forbidden/deprecated** term (`USE 'cena' INSTEAD`); `cena-rio` /
`cenario` are no longer forbidden.

## 3. Role badges (frontend relabel only — WP backend slugs unchanged)

| Backend role | Old display | New display | Level |
|---|---|---|---|
| administrator | apollo | apollo | 10 |
| editor | MOD | MOD | 7 |
| author | **cult::rio** | **cena+** | 5 |
| contributor | **cena::rio** | **cena** | 3 |
| subscriber | clubber | clubber | 1 |

## 4. Files changed

Registry: `_inventory/apollo-registry.json` (validated OK).
Code: `apollo-core/config/{meta,roles,routes,tables}.php`,
`apollo-core/includes/{functions,route-helpers}.php`,
`apollo-core/src/Config/{ApolloMeta,ApolloRoute}.php`,
`apollo-core/src/Core/{ActivationHandler,MetaRegistry}.php`,
`apollo-docs/src/Model/Document.php`,
`apollo-gestor/src/Admin/RoleAccess.php`,
`apollo-pane-engine/pane-engine-casa.json` (validated OK),
`apollo-templates/templates/page-test.php`.

## 5. Render / design-system audit

`apollo-cena` is **LOCKED_NEXT_VERSION** (deferred) — **no plugin folder, no
render templates exist yet**. Nothing to force into design-system compliance now.
Connector naming (route-ownership map, pane-engine map, REST namespace, meta, cap,
table owner, plugin key) is **consistent** across all surfaces — verified 0
`apollo-cult` leftovers.

**When apollo-cena is implemented**, its templates (`cena.php`, `cena-calendar.php`,
`cena-members.php`) must comply with the design system at
`_theme/Design System-handoff/apollo-rio-design-system/project/` (tokens, base.css,
components.css, shell.css).

## Open item
`user_industry` icon title in registry still reads `"Cena::rio"` — left as-is
(it's a membership/industry marker, not a role badge). Change on request.
