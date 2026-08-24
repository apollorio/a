# apollo::rio — plugin ecosystem map & safe-edit rules

Derived from the full registry chapter set in `_inventory/registry/` (00–21 + `09-plugins/*.json`,
46 plugin entries, registry v1.5.0 / monolith v6.6.1). This file is the **companion reference** to
`CLAUDE.md` — read `CLAUDE.md` for deploy mechanics and the design system; read this file before
touching any specific `apollo-*` plugin, so you know its blast radius, risk band, and open landmines
before you edit it. Regenerate this file (or at least re-check the relevant plugin's registry chapter)
if `_inventory/registry/` changes meaningfully — it's a snapshot, not a live query.

## 1. Shape of the ecosystem

46 registry entries, **40 with a real folder on disk**, 350 REST endpoints (99 open/high-risk), 78
tables, 492 unique meta keys, ~259k PHP LOC. `apollo-core` is the only plugin marked `required: true`
— every other plugin depends on it, never the reverse.

### Layers (`08-architecture-layers.json`)

| Layer | Plugins |
|---|---|
| L0 foundation | apollo-core |
| L1 auth | apollo-login, apollo-users, apollo-membership |
| L2 content | apollo-adverts, apollo-calendar, apollo-dj-sync, apollo-djs, apollo-events, apollo-loc, apollo-maps, apollo-scheduler, apollo-suppliers* |
| L3 social | apollo-social, apollo-groups, apollo-wow, apollo-fav, apollo-comment |
| L4 communication | apollo-chat, apollo-email, apollo-notif, apollo-remind, apollo-telegram |
| L5 documents | apollo-docs, apollo-sign, apollo-gestor, apollo-journal |
| L6 frontend | apollo-templates, apollo-dashboard, apollo-hub, apollo-radio, apollo-pane-engine |
| L7 admin | apollo-admin, apollo-coauthor, apollo-lux-panels, apollo-mod, apollo-statistics |
| L8 industry | apollo-cena* |
| L9 pwa | apollo-pwa* |
| L10 seo | apollo-seo |
| L11 data | apollo-sheets |
| L12 runtime | apollo-runtime* |
| L13 page-builder | apollo-elementor, apollo-elementor-pro |

`*` = phantom, see §2.

## 2. Plugins that do not exist on disk — do not edit, do not build against

Six registry entries have **no folder in `D:\dev\_apollo.rio.br\plugins\`**. They're preserved in the
registry deliberately, not deleted. Treat any reference to them in a spec, mockup, or old doc as
aspirational, not real:

| Plugin | Status | Note |
|---|---|---|
| `apollo-cena` | LOCKED_NEXT_VERSION | Blocked, deferred implementation. |
| `apollo-classifieds` | **DEPRECATED** | Absorbed into `apollo-adverts`. Don't recreate it. |
| `apollo-pwa` | PLANNED | Never built. |
| `apollo-runtime` | PLANNED | Scaffolded only (uninstall.php + composer.json). Nominal owner of chapter 13 (`runtime_wordpress_packages`), which is a live-empty template until this ships. |
| `apollo-shortcodes` | **DEPRECATED** | Absorbed into `apollo-core` (`ShortcodeRegistry`/`ShortcodesController`). Don't recreate it. |
| `apollo-suppliers` | LOCKED_NEXT_VERSION | Blocked transitively — depends on the also-phantom `apollo-cena`. |

If a task says "edit apollo-classifieds" or "add a route to apollo-shortcodes," the real target is
`apollo-adverts` or `apollo-core` respectively — confirm with the user rather than scaffolding a new
plugin folder.

## 3. Blast radius — who ripples where

`apollo-core` fans out to ~31 plugins and is depended on by nearly everyone. Three more plugins carry
unusually wide reverse-coupling and deserve the same caution as core itself:

- **apollo-templates** — ~24–25 dependents (page-builder canvas, owns `/casa`, the DS shell files).
- **apollo-admin** — ~24 dependents (settings hub, `/modera` shell slot).
- **apollo-pane-engine** — ~24–25 dependents, but it's a read-only REST test harness at `/casa`, so its
  own edits are lower-stakes even though many plugins register into it.

Before changing a shared function, hook name, or REST route signature in any of these four, check
`10-coupling.json` for the specific edge — a change here can silently break a dozen unrelated screens.

## 4. Security risk bands

From `_deep_audit.security` / top-level `security.risk_band`, cross-checked against `16-summary.json`.

**HIGH** — extra scrutiny on every edit, not just security-labeled ones:

| Plugin | Score | Version | Why it's hot |
|---|---|---|---|
| apollo-login | 75 | 1.0.41 | 26 REST routes, 11 open-critical; auth surface for the whole site. |
| apollo-events | 69 | 1.7.1 | 24 REST routes, 12 open non-intentional; most actively-edited plugin (annotations dated through 2026-08-08). |
| apollo-membership | 53 | 1.0.3 | 23 REST routes, 11 open, gamification + points/ranks. |

**MEDIUM** — apollo-users, apollo-adverts, apollo-chat, apollo-core, apollo-djs, apollo-email,
apollo-hub, apollo-loc, apollo-mod, apollo-radio, apollo-remind, apollo-sheets.

Everything else is LOW. LOW doesn't mean risk-free — it means the automated audit found fewer red
flags, not that the plugin is unimportant.

## 5. Open CRITICAL/HIGH backlog — landmines still in the code (`17-backlog.json`)

These are **confirmed still-pending** as of the last audit (2026-05-20 re-check). If a task touches
these exact files, either fix the flagged issue as part of the change or flag it explicitly to the
user — don't paper over it:

| ID | Sev | File | Issue |
|---|---|---|---|
| TBD-002 | CRITICAL | `apollo-chat/.../Plugin.php` L1191/1193 | Raw SQL, no `$wpdb->prepare()` |
| TBD-003 | CRITICAL | `apollo-notif/.../Plugin.php` L1185, `Admin.php` L304 | Raw SQL, no `prepare()` |
| TBD-004 | CRITICAL | `apollo-adverts/.../Deactivation.php` L56 | Raw SQL in cleanup hook |
| TBD-005 | CRITICAL | apollo-fav, apollo-wow, apollo-adverts, apollo-events, apollo-membership, apollo-hub, apollo-sheets, apollo-statistics, apollo-pane-engine | Nonces exposed in `data-*` HTML attrs — move to `wp_json_encode()` JS config |
| TBD-006 | CRITICAL | apollo-groups | REST `permission_callback` is `__return_true` — unauthenticated mutation possible |
| TBD-007 | CRITICAL | `apollo-login/.../RegisterHandler.php` | Missing `CURLOPT_SSL_VERIFYPEER` on Instagram cURL fetch |
| TBD-008 | HIGH | apollo-djs (player.php, hero.php, bio-modal.php) | Unescaped echo |
| TBD-009 | HIGH | apollo-remind admin-dashboard.php | Unescaped echo |
| TBD-010 | HIGH | apollo-sign titan-stamp.php | Unescaped echo |
| TBD-012 | HIGH | apollo-fav | nopriv AJAX handler should require auth |
| TBD-013 | HIGH | apollo-hub | JSON blocks stored without schema validation |
| TBD-014 | HIGH | apollo-email newsletter endpoint | No rate-limiting (harvest risk) |
| TBD-015 | HIGH | apollo-statistics | `PUT /stats/metric/{slug}/toggle` capability check unverified |
| TBD-016 | HIGH | apollo-login AuthController | IP rate-limit tier not reset on successful login |

TBD-001 and TBD-011 are marked resolved on re-audit — don't re-flag them without checking current
source first.

## 6. Canvas shell contract — one chokepoint, a documented history of breaking it

Full detail lives in `18-canvas-shell.json` and `CLAUDE.md`'s Design System section. The load-bearing
fact for edits: **`apollo_plus_open()` / `apollo_plus_close()` is the only canonical Apollo+ shell.**
Three competing shell implementations existed simultaneously as of mid-2026 and produced a documented
failure mode — double `<head>/<body>` pairs, two owners of `.ax-main` with opposite geometry, a missing
gestor nav group on `/novo-evento` and `/meus-eventos`. That's exactly the "cardinal sin" CLAUDE.md
warns about, and it already happened once for real.

- Do not call `BlankCanvasTrait::render_blank_canvas_plus()` directly for new work (path A, legacy).
- Do not call `apollo_render_blank_canvas_body()` for new work (path C, legacy) — it still survives
  unconverted in `apollo-users/templates/user-radar.php` and `apollo-dashboard/includes/class-plugin.php:207`.
- apollo-events' `shared/{topbar,overlay,panels,aside}.php` are left on disk deliberately as a rollback
  fallback for path A — don't delete them until path A is fully retired project-wide.
- A **block** (`apollo_plus_part()`, e.g. `apollo_listing_header()`) owns a band inside `<main>`, not
  the document. A **shell** owns exactly one `<head>`/`<body>`/topbar/aside/`<main>`. Don't conflate them.
- The registry chapter that flagged `apollo-core` as stuck at version 6.2.3 is itself a stale
  snapshot: live source (`apollo-core/apollo-core.php`, checked 2026-08-14) is already at **6.2.9**,
  docblock and `APOLLO_CORE_VERSION` constant agree. The underlying rule still applies going
  forward — bump the docblock and the `APOLLO_*_VERSION` constant together, in the same edit,
  whenever you touch apollo-core (see §9) — but don't trust this registry's specific version numbers
  without spot-checking the live file first; the registry snapshot lags disk.

## 7. Naming & philosophy guards — enforced vocabulary, not style preference

`15-conventions.json` `namingRules.FORBIDDEN_TERMS` — several plugins additionally embed their own
`FORBIDDEN` word list in the registry, meaning these were deliberate product renames, not typos:

| Forbidden | Use instead | Enforced by |
|---|---|---|
| venue, local (in code identifiers), location | `loc` | apollo-loc |
| interesse, interessado, interest, bookmark | `fav` | apollo-fav |
| like, heart, reaction | `wow` | apollo-wow |
| comment, review | `depoimento` | apollo-comment |
| cult | `cena` | apollo-cena |
| document | `doc` | (general) |
| `/user/` (in routes) | `/id/` | (general) |
| follow / unfollow / followers count / friend count | *(remove entirely)* | `01-philosophy.json` — `PARTY_MODEL`, `NO_SELECTIVE_FOLLOW`, `NO_EGO_COUNTERS` |

The philosophy guard is stricter than a naming rule — it's a product-level ban. `21-mockup-field-contract.json`
caught four live violations still in mockups as of 2026-08-08 (a "4.1k · Seguidores" pill, a
`#dockFollow` "Seguir" button with a heart icon, "Siga o local" copy, and a `.map-note` that leaked raw
lat/lng plus an internal meta name in production). If you touch dj/local templates, check whether these
have been fixed — they were flagged CRITICAL, copy/UI fixes only, no new storage needed (the fav tables
already exist).

Also: `apollo_time_ago()` is mandatory for any relative-time display — never `human_time_diff()` or a
hand-rolled "X ago" string; it breaks CSS selectors (`.time-ago`/`.when-ago`) that depend on the exact
markup shape documented in `15-conventions.json`.

## 8. Known SSOT gaps to check before you rely on a field

- **`_dj_tracks` has a write/read version split.** Writes (REST + the `apollo-lux-panels` metabox) use
  schema v2 (title/artists/duration + bpm/release_date/label/genre/cover/url_*). Reads
  (`apollo_dj_get_tracks()`, `out-now.php`, `out-now-lightbox.php`, `dj-v3/sounds.php`) are **still on
  v1** and will render a v2-saved track blank. Fix either side fully, don't assume they match.
- **`/feed` route is ambiguous.** The registry's `APOLLO_ALL_ROUTES` says `/feed` → `feed.php`, but
  `apollo-social` actually serves `explore.php` on both `/explore` and `/mural`. Confirm current
  behavior before trusting the registry's route claim for `/feed`.
- **apollo-adverts and apollo-membership have registered pages with no template.** Classified
  archive/single/create templates and membership achievements/points/ranks/leaderboard templates are
  declared in the registry but don't exist yet — a route "existing" in the registry doesn't mean the
  page renders.
- Two dj/local mockup fields are flagged **STILL_BROKEN**: `_local_testimonials` is read by
  `single-local.php:231` but written by nothing and not in `MetaRegistry`. `_dj_verified` has a badge
  asset but nothing renders it.
- `apollo_core_bootstrap`, CPT/taxonomy/meta registration is **exclusively** apollo-core's job
  (`CRITICAL_apollo_core_centralization` in `02-header.json`). A new meta key or CPT declared inside
  any other plugin is a registry violation even if it works — it must go through
  `apollo-core/src/Core/MetaRegistry.php`.

## 9. Pre-edit checklist

Before editing any `apollo-*` plugin file in this workspace:

1. Confirm the plugin is real (§2) — not one of the six phantom entries.
2. Check its risk band (§4) and whether it's in the open-backlog table (§5). If yes, treat the edit as
   security-sensitive even if the task description isn't about security.
3. Check `10-coupling.json` for its dependents if you're changing a shared function, hook, or REST
   contract — especially for apollo-core, apollo-templates, apollo-admin, apollo-pane-engine (§3).
4. If the edit touches a page shell or navbar, use `apollo_plus_open()`/`apollo_plus_close()` only (§6).
   Never add a second `:root` block, never duplicate a selector another cell already owns (see
   CLAUDE.md's cardinal-sin note).
5. Run naming and philosophy terms in any new UI copy or identifier past §7.
6. If the change touches meta/CPT/taxonomy registration, it belongs in apollo-core's `MetaRegistry`,
   not the consuming plugin.
7. Verify structurally in the sandbox first (CLAUDE.md: no PHP binary here, so this is
   syntax/structure/consistency checking, not `php -l`). Save to the live-mirrored folder only when
   you're confident — a saved file is a deployed file, 30s propagation, no undo.
8. If you bump `apollo-core`'s version for a canvas-contract or MetaRegistry change, bump the
   `APOLLO_*_VERSION` constant in the same commit — WordPress reads the docblock, cache-busting reads
   the constant, and a mismatch silently serves stale assets.
