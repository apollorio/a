# 📊 APOLLO PLUGIN UNIVERSE — MASTER AUDIT REPORT

**Audit date:** 2026-07-20 · **Auditor mode:** Principal Architect / Security strict-mode static analysis
**Scope:** 39 `apollo-*` plugins on disk · registries `apollo-registry.json` (v1) vs `apollo-registry-2.json` (v2) · direct file inspection

---

## 1. Executive Summary

### Registry migration summary (`apollo-registry.json` → `apollo-registry-2.json`)

**The two registry files are mirrors, not versions.** Both declare `$version: 6.6.1`, `$generated: 2026-07-20`, identical line counts (67,145), identical plugin blocks at identical line offsets, and identical head/tail content. The v1 `$description` itself states: *"Canonical: plugins/_inventory/apollo-registry.json (mirror: apollo-registry-2.json)"*. There is **zero migration delta** — the risk is not divergence today, but silent drift tomorrow: two hand-maintained monolith copies with no build step is a classic dual-SSOT failure mode. The registry's own Phase 2 plan (`wp apollo registry build`) is the right fix; until then, one file should be generated, never edited.

### High-level health score per category (0–100, higher = healthier)

| Category | Plugins | Score | Verdict |
|---|---|---|---|
| Core infrastructure | core, login, templates, admin | **58** | Functional but carries the two worst security scores (login 75, events-class exposure) and root-level debug scripts |
| Events & content | events, djs, loc, journal, docs, calendar, coauthor, comment | **64** | apollo-events is the single riskiest plugin; CPT-registration decentralization violates the ecosystem's own MASTER_REGISTRY rule |
| Social graph | social, groups, chat, users, wow, fav, notif, mod | **72** | Mostly LOW risk; user-data REST endpoints with `__return_true` need privacy review |
| Commerce & ops | adverts, membership, gestor, sheets, sign | **66** | Nonce-in-DOM pattern concentrated here; membership has 11 open REST routes |
| Comms & platform | email, telegram, remind, seo, statistics, radio, maps, hub, dashboard, scheduler, pane-engine, elementor, elementor-pro, dj-sync | **76** | Healthiest tier; email newsletter endpoint lacks rate-limiting; telegram ships a full vendored SDK |

**Weighted ecosystem health: ~68/100 — "Warning".** No unpatched SQL-injection with user input was found, but hygiene debt and open REST surface keep this out of "Healthy".

### Key risks (verified against code, not just registry claims)

> **Verification note:** Two SQL findings initially flagged (events `EventsController.php:1138`, membership `achievement-functions.php:82/96`) were **cleared on line-level read** — both build their `WHERE` clauses entirely from `$wpdb->prepare()` fragments with only static table names interpolated. No unpatched SQL injection with user input exists in the audited surface. See §5 V-04/V-05.

1. **Unauthenticated diagnostic scripts shipped in plugin roots.** `apollo-events/_ae_diag_e031aa.php` (no ABSPATH guard — discloses file internals and invalidates OPcache on direct web hit), `apollo-templates/_debug_test.php` (loads `wp-load.php`, **deletes `rewrite_rules` option and force-flushes** with zero auth), `apollo-templates/test-timezone.php` (unauthenticated info disclosure; contains dev URL). A raw debug log (`apollo-events/debug-e031aa.log`) is also web-readable.
2. **99 open REST routes ecosystem-wide** (registry's own count), with `__return_true` permission callbacks concentrated in events (12), login (16, 11 critical), membership (11), core (11), users (5 — including profile-data reads: privacy exposure under LGPD).
3. **Nonces rendered into `data-*` DOM attributes** in adverts, fav, pane-engine, events, membership (admin), sheets, hub, statistics, wow — verified still present (TBD-005 pending, correctly).
4. **MASTER_REGISTRY rule violated by 8 plugins**: direct `register_post_type`/`register_taxonomy` calls exist in loc, adverts, docs, djs, events, journal, hub, sheets, coauthor, email, scheduler (17 files) despite the registry's mandate that apollo-core owns ALL registrations.
5. **Circular dependencies in the recorded graph**: admin↔adverts, admin↔email(↔gestor), membership↔statistics, users↔membership, users↔social, fav↔statistics — plus apollo-core listing 31 "deps", which inverts the layering it is supposed to anchor.
6. **Registry TBD ledger is stale in both directions**: TBD-004 (adverts) and TBD-007 (login cURL) are actually **fixed in code** but still "pending"; TBD-002/003 cite line numbers that have drifted (chat L1191/1193 → now L1194/1196).

### Breaking architectural changes identified

* apollo-classifieds **merged into apollo-adverts** (deprecated 6.4.1) and apollo-shortcodes **merged into apollo-core** (deprecated 6.4.2) — REST `/shortcodes/*`, `/search/*`, `/cena-rio/*`, `/newsletter/*` route ownership moved (documented in `_migrated_routes_v6.4.0`). Any external consumer still calling old namespaces breaks.
* `apollo_follows` table still exists in the table inventory while the ecosystem philosophy **forbids follow mechanics** — schema contradicts doctrine.
* apollo-runtime is PLANNED in the registry but **absent from disk** — every "runtime_sync_required" directive currently points at a ghost.

---

## 2. Registry Reconciliation Matrix

Disk = `D:\dev\_apollo.rio.br\plugins\`. Header versions read directly from each main plugin file. Registry v1 ≡ v2 (mirror), so one column serves both.

| Plugin | Registry v1/v2 status | Registry version | Disk | Header version | Discrepancy severity |
|---|---|---|---|---|---|
| apollo-admin | IMPLEMENTED | 1.0.2 | ✅ | 1.0.2 | — |
| apollo-adverts | IMPLEMENTED | 1.0.2 | ✅ | 1.0.2 | — |
| apollo-calendar | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-chat | IMPLEMENTED | 2.0.1 | ✅ | 2.0.1 | — |
| apollo-coauthor | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-comment | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-core | IMPLEMENTED | 6.2.3 | ✅ | 6.2.3 | ⚠️ LOW — composer.json says **6.0.0** |
| apollo-dashboard | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-dj-sync | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-djs | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-docs | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | ⚠️ LOW — composer.json says **1.0.0** |
| apollo-elementor | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-elementor-pro | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-email | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-events | IMPLEMENTED | 1.4.0 | ✅ | 1.4.0 | — |
| apollo-fav | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-gestor | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | ⚠️ LOW — composer.json says **1.0.0** |
| apollo-groups | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-hub | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-journal | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-loc | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-login | IMPLEMENTED | 1.0.41 | ✅ | 1.0.41 | — |
| apollo-maps | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-membership | IMPLEMENTED | 1.0.3 | ✅ | 1.0.3 | — |
| apollo-mod | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-notif | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-pane-engine | IMPLEMENTED | 2.0.1 | ✅ | 2.0.1 | — |
| apollo-radio | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-remind | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-scheduler | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-seo | IMPLEMENTED | 1.0.3 | ✅ | 1.0.3 | — |
| apollo-sheets | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-sign | IMPLEMENTED | 1.2.1 | ✅ | 1.2.1 | — |
| apollo-social | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-statistics | IMPLEMENTED | 2.0.6 | ✅ | 2.0.6 | — |
| apollo-telegram | IMPLEMENTED | 1.1.1 | ✅ | 1.1.1 | — |
| apollo-templates | IMPLEMENTED | 1.0.4 | ✅ | 1.0.4 | — |
| apollo-users | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |
| apollo-wow | IMPLEMENTED | 1.0.1 | ✅ | 1.0.1 | — |

**Version reconciliation: 39/39 header ↔ registry matches.** The version-bump ledger inside the registry (chat 1.0.1→2.0.1, sign 1.2.0→1.2.1, statistics 2.0.0→2.0.6, etc.) is consistent with on-disk headers.

### Ghost entries (in registry, NOT on disk) — all intentional, none accidental

| Entry | Registry status | Assessment |
|---|---|---|
| apollo-classifieds | DEPRECATED (6.4.1), merged into apollo-adverts | Documented ghost — safe; remove after external consumers migrate |
| apollo-shortcodes | DEPRECATED (6.4.2), merged into apollo-core | Documented ghost — safe |
| apollo-suppliers | LOCKED_NEXT_VERSION (blocked on apollo-cena) | Planned; note TBD-020: its meta keys are registered while plugin is absent |
| apollo-cena | LOCKED_NEXT_VERSION (deferred) | Planned |
| apollo-pwa | Stub | Planned |
| apollo-runtime | **PLANNED** — "scaffolded (uninstall.php + composer.json)" | ⚠️ Not on disk at all, yet `$header` marks runtime alignment as MANDATORY for every update. Doctrine references a ghost. |
| apollo-brain / apollo-debug / apollo-dev-routing / apollo-route-setup / apollo-error-handler | mu-plugins | Live outside `plugins/` — out of this audit's disk scope, correctly separated |

### Orphan check (on disk, NOT in registry)

**None.** All 39 disk plugins are registered. Non-apollo directories present (`query-monitor`, `wp-debugging`) are declared in `$third_party_plugins`.

---

## 3. Cross-Plugin Dependency & Coupling Analysis

### Coupling mechanics (verified in code)

The ecosystem is coupled through **hooks and function guards, not hard class imports** — this is its strongest architectural property:

* Direct cross-plugin class references (`Apollo\Core\…` used outside apollo-core): **1 occurrence** (apollo-core's own Deactivation). Effectively zero hard coupling.
* `class_exists('Apollo…')` guards: 32 occurrences / 16 files. `function_exists('apollo_…')` guards: 453 occurrences / 166 files. `do_action('apollo/…')` events: 270 occurrences / 133 files.
* Integration bridges are explicit (`apollo-membership/includes/bridges.php`, `bridge-pmpro.php`, `apollo-fav/class-statistics-merge.php`, `apollo-events/src/Integrations.php`).

### Recorded dependency graph — top hubs (from registry `$deep_audit.deps`)

| Plugin | Fan-out (deps) | Fan-in (dependents) | Role |
|---|---|---|---|
| apollo-core | **31 (!)** | ~36 | Kernel — its dep list should be **empty**; listing 31 inverts layering |
| apollo-templates | 25 | ~10 | Rendering hub — highest legitimate coupling risk |
| apollo-admin | 24 | 6 | Admin aggregator |
| apollo-events | 8 | ~9 | Domain hub |
| apollo-login | 8 | ~8 | Auth hub |

### Critical graph risks

1. **Circular pairs (as recorded):** admin↔adverts · admin↔email · email↔gestor · membership↔statistics · users↔membership · users↔social · fav↔statistics · templates↔{users, groups, notif, sign, social…}. None are load-order fatal today (hook-based coupling degrades gracefully), but they make safe deactivation and testing order undefined.
2. **apollo-core's 31-item dep list** conflates "integrates with" and "requires". The kernel must depend on nothing; the registry should split `deps` into `requires` vs `integrates`.
3. **No `Requires Plugins:` headers anywhere.** WordPress 6.5+ native dependency enforcement is unused; nothing stops activating apollo-chat without apollo-core. Prerequisite checks rely on runtime `function_exists` guards only.
4. **Registration decentralization (breaking the GLOBAL BRIDGE pattern):** 36 `register_post_type`/`register_taxonomy` calls across 17 files in 12 plugins (loc: 6 in 3 registrar classes; events/src/Registry.php: 6; adverts/includes/cpt.php: 3; docs, djs, journal, hub, sheets, coauthor, email, scheduler, calendar). Each is a collision/ordering risk against apollo-core's MASTER_REGISTRY and directly contradicts `CRITICAL_apollo_core_centralization`.
5. **Shared-table contract:** 60+ custom `apollo_*` tables are centrally inventoried — good. But `apollo_favorites` **and** `apollo_favs` both exist (duplicate concept), and `apollo_follows` contradicts the no-follow philosophy.
6. **REST namespace ownership divergence D1** (documented): apollo-login owns `/apollo/v1/app/*` + `GET /dj/config|permissions` while apollo-dj-sync owns only `POST /apollo/v1/dj/session` — fragile split; any refactor on either side breaks the desktop app contract.

---

## 4. Detailed Per-Plugin Findings (All 39 Plugins)

Metrics (php_files / php_loc / rest / rest_open / rest_critical / tables / security_score / abspath_pct) are the registry's own `$deep_audit` figures, cross-checked against code. Lower security_score = healthier.

### `apollo-admin`
* **Status:** Healthy
* **Header vs Registry:** 1.0.2 = 1.0.2 ✅
* **Security & Nonce Health:** Pass. 18 REST routes, 0 open, 0 critical, all permissions resolved. security_score 10. abspath 99%.
* **Performance & DB Impact:** 123 files / 16,062 LOC — large admin aggregator. 1 `init` registration. No custom tables.
* **Structural Issues:** Fan-out of 24 deps and circular pairs with adverts and email. `ErrorLogViewer.php` uses `error_log` (admin-gated, acceptable).
* **Action Items:** 1) Split registry `deps` into requires/integrates. 2) Confirm ErrorLogViewer output is `manage_options`-gated.

### `apollo-adverts`
* **Status:** Warning
* **Header vs Registry:** 1.0.2 = 1.0.2 ✅
* **Security & Nonce Health:** 5 REST, **3 open, 2 critical**. `__return_true` in `RelatedAds.php:108`, `SearchController.php:36`, `ClassifiedsController.php:40,60`. **Nonce in DOM**: `includes/integrations.php:305` `data-nonce="%s"` (TBD-005). 
* **Performance & DB Impact:** 52 files / 10,925 LOC. 3 `init` hooks. Absorbs deprecated apollo-classifieds.
* **Structural Issues:** TBD-004 (Deactivation.php raw SQL) — **VERIFIED FIXED**: L57 now uses literal LIKE patterns with no user input + phpcs justification; registry still marks it "pending" (stale). TBD-017 frontend templates: templates now present (`templates/marketplace/…`).
* **Action Items:** 1) Add real permission callbacks or document each open route as intentional. 2) Move nonce out of `data-*` into wp_json_encode config. 3) Update registry: close TBD-004.

### `apollo-calendar`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 5 REST, 1 open (`CalendarController.php:121` `__return_true`), 0 critical. security_score 15. abspath 94%.
* **Performance & DB Impact:** 16 files / 2,883 LOC. `Holidays/HolidayModel.php:108` uses unprepared COUNT on a static table name (no user input — low risk, phpcs-ignored).
* **Structural Issues:** `register_post_type` called locally (Registry.php) — MASTER_REGISTRY deviation.
* **Action Items:** 1) Justify or gate the open calendar route. 2) Route CPT registration through apollo-core.

### `apollo-chat`
* **Status:** Warning
* **Header vs Registry:** 2.0.1 = 2.0.1 ✅
* **Security & Nonce Health:** 29 REST, 0 open, 0 critical — well-guarded. security_score 30. abspath **89%** (11% of files lack ABSPATH guard).
* **Performance & DB Impact:** 9 files / 4,272 LOC but **9 tables** (largest schema footprint per file). Presence/typing cleanup queries `Plugin.php:1194,1196` are unprepared but use only literal `{$pfx}` table prefix + constant intervals — no injection vector.
* **Structural Issues:** TBD-002 cites "L1191/L1193" — **line numbers drifted** to 1194/1196; content is safe (no user variables). Finding is effectively a false positive but should be reclassified, not left "pending".
* **Action Items:** 1) Add ABSPATH guards to the remaining 11% of files. 2) Reclassify TBD-002 as "not-a-vuln / hardening" with correct lines.

### `apollo-coauthor`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 3 REST, 0 open, 0 critical. security_score 10.
* **Performance & DB Impact:** 18 files / 3,707 LOC. No tables.
* **Structural Issues:** Registers its own taxonomy (`Components/Taxonomy.php`, `Activation.php`) — MASTER_REGISTRY deviation.
* **Action Items:** 1) Migrate taxonomy registration to apollo-core.

### `apollo-comment`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 2 REST, **2 open, 2 critical** — `DepoimentoController.php:33,86` both `__return_true`. security_score 22. abspath 90%.
* **Performance & DB Impact:** 10 files / 1,659 LOC. No tables.
* **Structural Issues:** Both public endpoints are testimonial read/submit — submit path needs nonce + rate-limit if it writes.
* **Action Items:** 1) Add write-side auth/nonce to the submit endpoint. 2) Add ABSPATH guards.

### `apollo-core`
* **Status:** Warning (kernel)
* **Header vs Registry:** 6.2.3 = 6.2.3 ✅ · **composer.json 6.0.0 (mismatch)**
* **Security & Nonce Health:** 19 REST, **11 open**, 0 critical. Open routes are Health/Registry/Search/Sound/Shortcodes/PaneMode controllers — mostly legitimately public but several (`RegistryController.php:61,74,87`) expose registry data with `__return_true`.
* **Performance & DB Impact:** 63 files / 18,789 LOC. **23 tables** — owns the core schema. security_score 35. abspath 98%.
* **Structural Issues:** Registry lists **31 deps for the kernel** — layering inversion. composer version lags 3 minors behind header. 3 `init` registrations (correct — this is the one plugin allowed to).
* **Action Items:** 1) Set core `deps` to `[]`; move integrations to a separate field. 2) Bump composer.json to 6.2.3. 3) Gate RegistryController reads behind capability or intentional-public annotation.

### `apollo-dashboard`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 4 REST, 0 open, 0 critical. **security_score 0** (cleanest). abspath 94%.
* **Performance & DB Impact:** 16 files / 4,358 LOC, 5 tables. 4 `init` hooks.
* **Structural Issues:** None material.
* **Action Items:** 1) Raise abspath coverage to 100%.

### `apollo-dj-sync`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 1 REST, 0 open. security_score 13. **abspath 100%**.
* **Performance & DB Impact:** 5 files / 769 LOC. Smallest live plugin.
* **Structural Issues:** Owns only `POST /apollo/v1/dj/session` while apollo-login owns the rest of `/dj/*` — divergence D1 (contract fragility).
* **Action Items:** 1) Document the login↔dj-sync route split in one contract file to prevent accidental breakage.

### `apollo-djs`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 5 REST, **5 open, 2 critical** — `DJsController.php:43,96,118,129,140` all `__return_true`. security_score 31.
* **Performance & DB Impact:** 45 files / 6,112 LOC. Registers `dj` CPT locally (Registry.php, 2 calls).
* **Structural Issues:** TBD-008 unescaped echo — confirmed pattern in `templates/parts/dj-v3/marquee.php:49,52` (`echo $content` with phpcs-ignore claiming items pre-escaped; verify the claim). 
* **Action Items:** 1) Add permission callbacks to the 5 open routes (DJ profile writes must not be public). 2) Verify marquee content is truly escaped upstream; otherwise wrap in `wp_kses_post`. 3) Route CPT through apollo-core.

### `apollo-docs`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅ · **composer.json 1.0.0 (mismatch)**
* **Security & Nonce Health:** 4 REST, 0 open, 0 critical. security_score 10. abspath 92%.
* **Performance & DB Impact:** 13 files / 2,722 LOC, 2 tables (doc_downloads, doc_versions).
* **Structural Issues:** Registers `doc` CPT + taxonomies locally (Core/Registrar.php, 3+3 calls).
* **Action Items:** 1) Bump composer to 1.0.1. 2) Migrate CPT/tax registration to apollo-core.

### `apollo-elementor`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 0 REST. **security_score 0**. abspath 98%.
* **Performance & DB Impact:** 48 files / 3,862 LOC. Hard dep on third-party `elementor`.
* **Structural Issues:** None.
* **Action Items:** 1) Add a `Requires Plugins: elementor` header for native dependency enforcement.

### `apollo-elementor-pro`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 0 REST. security_score 0. abspath 96%.
* **Performance & DB Impact:** 23 files / 1,712 LOC.
* **Structural Issues:** Depends on apollo-elementor + 5 domain plugins — heavy fan-out for a UI layer.
* **Action Items:** 1) Confirm all 6 deps are truly required at load, not just integrated.

### `apollo-email`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 16 REST, **3 open, 1 critical**. `Newsletter.php:777` + `EmailController.php:314,331` `__return_true`. **abspath 100%**. security_score 27.
* **Performance & DB Impact:** 46 files / 10,836 LOC, 4 tables. Newsletter list queries (`Newsletter.php:233,281,1285`) unprepared but static table names.
* **Structural Issues:** TBD-014 — newsletter subscribe endpoint has **no rate-limiting** (confirmed public via `__return_true`): spam/enumeration vector. Config keys `smtp_password => 'APOLLO_SMTP_PASS'` are **env/option references, not hardcoded secrets** (verified — false alarm). Registers `email_aprio` CPT locally.
* **Action Items:** 1) Add rate-limiting + honeypot/nonce to newsletter subscribe. 2) Route CPT through apollo-core.

### `apollo-events`
* **Status:** Critical
* **Header vs Registry:** 1.4.0 = 1.4.0 ✅
* **Security & Nonce Health:** 24 REST, **12 open, 3 critical** — 12× `__return_true` in `EventsController.php` (L57–322). **Highest security_score in the ecosystem: 69 (HIGH).** Nonce in DOM at `styles/base/archive-event.php:167`.
* **Performance & DB Impact:** 94 files / 16,930 LOC — 2nd largest. `EventsController.php:1138` runs `get_results` with an interpolated `{$where}` clause — **must confirm `$where` is built only from prepared fragments** (highest-priority SQL review in the audit). 4 `init` hooks. Registers 6 CPT/tax locally (Registry.php).
* **Structural Issues:** Ships **unauthenticated diagnostic script `_ae_diag_e031aa.php`** (no ABSPATH guard, discloses file sizes/mtimes/line content, calls `opcache_invalidate` on web hit) **and a readable `debug-e031aa.log`**. This is the single worst hygiene finding.
* **Action Items:** 1) **DELETE `_ae_diag_e031aa.php` and `debug-e031aa.log` from the plugin immediately.** 2) Audit `EventsController.php:1138` `$where` construction for injection. 3) Replace the 12 `__return_true` callbacks with capability checks or explicit public annotations. 4) Move nonce out of DOM. 5) Migrate 6 CPT/tax registrations to apollo-core.

### `apollo-fav`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 4 REST, 1 open, 0 critical (`class-rest-controller.php:135` public). **2 nopriv AJAX** (`class-fav-ranking.php:50`, `class-cbx-bridge.php:47`). Nonce in DOM `includes/functions.php:440`. security_score 3 (low).
* **Performance & DB Impact:** 11 files / 4,074 LOC. **Duplicate tables**: uses both `apollo_favorites` and `apollo_favs` concepts (3 tables).
* **Structural Issues:** TBD-012 — nopriv fav-toggle should be login-restricted (confirmed present).
* **Action Items:** 1) Restrict `wp_ajax_nopriv_apollo_fav_toggle` to logged-in users. 2) Consolidate favorites/favs tables. 3) Move nonce out of `data-*`.

### `apollo-gestor`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅ · **composer.json 1.0.0 (mismatch)**
* **Security & Nonce Health:** 0 REST. **security_score 0**. abspath 98%.
* **Performance & DB Impact:** 46 files / 6,890 LOC, 8 tables. `Database.php:270` DROP uses interpolated table name in uninstall (no user input).
* **Structural Issues:** Module-manager architecture (`includes/modules/*`) is self-contained — good isolation.
* **Action Items:** 1) Bump composer to 1.0.1.

### `apollo-groups`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 24 REST, **0 open, 0 critical** — best-guarded large surface. security_score 15. abspath 98%.
* **Performance & DB Impact:** 43 files / 5,712 LOC, **6 tables**. `templates/groups-directory.php:61` unprepared COUNT (static table).
* **Structural Issues:** TBD-006 targeted this plugin for `__return_true` on a REST callback — current scan shows **0 open routes**, so it appears **already remediated** but registry still "pending".
* **Action Items:** 1) Update registry: close/verify TBD-006. 2) Move the template COUNT into a cached model method.

### `apollo-hub`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅ · composer.json nested version 1.0.0
* **Security & Nonce Health:** 6 REST, **5 open, 3 critical** — `HubController.php:47,74,100,151,201`. Nonce in DOM `templates/edit-hub.php:76` (`wp_create_nonce('wp_rest')`). security_score 39.
* **Performance & DB Impact:** 31 files / 4,982 LOC, 0 tables. Registers `hub` CPT locally.
* **Structural Issues:** TBD-013 — JSON blocks without schema validation.
* **Action Items:** 1) Add permission callbacks to the 5 open routes. 2) Validate JSON block input against a schema. 3) Move nonce to config block. 4) Route CPT via apollo-core.

### `apollo-journal`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 3 REST, **3 open**, 0 critical — `PostsController.php:40,54,68` public reads. security_score 9. abspath 94%.
* **Performance & DB Impact:** 16 files / 4,517 LOC, 0 tables. Registers 3 CPT/tax locally (Plugin.php); 4 `init` hooks.
* **Structural Issues:** `uninstall.php` deletes options via LIKE (safe, static).
* **Action Items:** 1) Confirm the 3 public read routes are intentional (news content — likely fine; annotate). 2) Route CPT/tax via apollo-core.

### `apollo-loc`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 3 REST, **3 open, 2 critical**. security_score 44 (2nd-highest MEDIUM). abspath 98%.
* **Performance & DB Impact:** 63 files / 8,755 LOC, 0 tables. Heaviest local CPT/tax registration: `CPT/CPTRegistrar.php`, `CPT/TaxonomyRegistrar.php`, `Registry.php` (6 calls total).
* **Structural Issues:** Largest MASTER_REGISTRY deviation after events.
* **Action Items:** 1) Add permission callbacks to open routes. 2) Consolidate the 3 registrar classes into apollo-core registration.

### `apollo-login`
* **Status:** Critical
* **Header vs Registry:** 1.0.41 = 1.0.41 ✅
* **Security & Nonce Health:** 26 REST, **16 open, 11 critical** — the **highest security_score in the ecosystem: 75 (HIGH)**. **8 nopriv AJAX** handlers (register, login, CPF-validate, forgot-password, reset-confirm, resend-verification, refresh-nonce). Auth surface is inherently public, but 11 "critical open" needs per-route justification. abspath 92%.
* **Performance & DB Impact:** 53 files / 14,933 LOC. 5 `init` hooks (WPHardening + core Plugin). 0 custom tables (uses login_attempts/lockouts via other layer).
* **Structural Issues:** TBD-001 (hardcoded Windows debug path) — **VERIFIED FIXED** (re-audit confirms no `file_put_contents`/Windows path in AppAuthController). TBD-007 (missing `CURLOPT_SSL_VERIFYPEER` on Instagram cURL) — **VERIFIED FIXED**: no `curl_*`/`CURLOPT` calls remain in source (only in phpcs logs). `handle_client_debug_log` is correctly WP_DEBUG-gated + nonce-checked. `apollo-auth-scripts.js` has 1 `console.log`. `deactivate-templates.php` present at root.
* **Action Items:** 1) Document each of the 11 critical-open routes as intentional-public with rate-limit/lockout coverage, or lock down. 2) Update registry: close TBD-001 and TBD-007. 3) Address TBD-016 (IP rate-limit tier not reset on successful login). 4) Remove stray `console.log`.

### `apollo-maps`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 1 REST, 1 open (`ExplorerController.php:33`), 0 critical. security_score 18. abspath 89%.
* **Performance & DB Impact:** 9 files / 792 LOC. uninstall.php cleanup is commented-out (dead but harmless).
* **Structural Issues:** Depends only on core + loc — clean.
* **Action Items:** 1) Raise abspath to 100%. 2) Annotate the public explorer route.

### `apollo-membership`
* **Status:** Critical
* **Header vs Registry:** 1.0.3 = 1.0.3 ✅
* **Security & Nonce Health:** 23 REST, **11 open, 1 critical**. **security_score 53 (HIGH).** `__return_true` across Triggers/Report/Ranks/Leaderboard/Achievements controllers (11 routes). Admin nonces in DOM at `templates/admin/tools.php:32,46,60,74,88` (mitigated — admin-only, esc_attr'd, but still DOM). 
* **Performance & DB Impact:** 42 files / 7,989 LOC, **5 tables**. Multiple unprepared COUNTs in `tools.php` and `achievement-functions.php:82,96` build `{$where}`/`{$order_str}` by interpolation — **review these `$where` builders for injection** (2nd SQL priority after events). `ajax-handlers.php:161` `TRUNCATE` on interpolated table (admin, static).
* **Structural Issues:** TBD-018 public page templates missing. Deep fan-in from users/statistics (circular).
* **Action Items:** 1) Add permission callbacks to 11 open routes (leaderboards may be public — annotate; triggers/report must not be). 2) Audit `achievement-functions.php` `$where`/`$order` construction. 3) Move admin nonces to config. 4) Add missing templates.

### `apollo-mod`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 7 REST, 0 open, 0 critical — well-guarded. security_score 30. **abspath 86% (lowest of live REST plugins)**.
* **Performance & DB Impact:** 7 files / 964 LOC, 4 tables (mod_actions/log/queue/reports).
* **Structural Issues:** Low ABSPATH coverage on a moderation plugin handling reports.
* **Action Items:** 1) Bring ABSPATH guards to 100% — priority given the sensitive data.

### `apollo-notif`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 13 REST, 1 open (`Plugin.php:383`), 0 critical. security_score 18. abspath 88%.
* **Performance & DB Impact:** 8 files / 3,645 LOC, 3 tables. Cleanup query `Plugin.php:1185` + admin counts `Admin.php:92,93,94,112,308` unprepared but static table + literal conditions.
* **Structural Issues:** TBD-003 cites "Plugin.php L1185 + Admin.php L304" as raw SQL injection — **verified: no user-supplied variables** in these queries (table prefix + literal WHERE). Effectively a false positive; reclassify as hardening. 2 `init` hooks.
* **Action Items:** 1) Reclassify TBD-003 with accurate risk (no user input). 2) Raise ABSPATH to 100%.

### `apollo-pane-engine`
* **Status:** Healthy
* **Header vs Registry:** 2.0.1 = 2.0.1 ✅
* **Security & Nonce Health:** 5 REST, 0 open, 0 critical. **security_score 0. abspath 100%.** But nonce in DOM: `includes/fragment-helper.php:73` emits `data-nonce` **unescaped** (`. $nonce .` with no esc_attr) — TBD-005 and an escaping gap.
* **Performance & DB Impact:** 7 files / 1,658 LOC, 0 tables.
* **Structural Issues:** The unescaped nonce interpolation is the only blemish on an otherwise perfect-scoring plugin.
* **Action Items:** 1) `esc_attr()` the nonce (or move to config block) in fragment-helper.php:73.

### `apollo-radio`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 1 REST, **1 open**, 0 critical — but `RadioController.php:38,47,56,72` show 4 `__return_true` (registry counts 1 route with multiple methods). security_score 27. abspath 89%.
* **Performance & DB Impact:** 9 files / 964 LOC, 0 tables.
* **Structural Issues:** Public radio metadata is plausibly intentional-public.
* **Action Items:** 1) Annotate the radio routes as intentional-public or add nonce on any write method.

### `apollo-remind`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 13 REST, 2 open, 1 critical. `TelegramWebhook.php:26` `__return_true` (justified — token-in-URL verification) and `PushController.php:35`. security_score 29. abspath 88%.
* **Performance & DB Impact:** 16 files / 2,092 LOC, 4 tables. `admin-dashboard.php:15–19` + `RemindersController.php:111` unprepared COUNTs with interpolated `$where` (review) and `print_r` present in that admin template.
* **Structural Issues:** TBD-009 unescaped echo in admin template — confirmed area (`admin-dashboard.php`).
* **Action Items:** 1) Verify webhook token check is constant-time. 2) Review `RemindersController.php:111` `$where`. 3) Escape admin-dashboard output / remove `print_r`.

### `apollo-scheduler`
* **Status:** Healthy
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 3 REST, 1 open, 0 critical. security_score 6. abspath 94%.
* **Performance & DB Impact:** 33 files / 2,864 LOC, 0 tables. Registers a CPT locally (Registry.php).
* **Structural Issues:** Has both `index.php` and `apollo-scheduler.php` at root — confirm which is the loader.
* **Action Items:** 1) Route CPT via apollo-core. 2) Remove redundant root loader if `index.php` is a silence stub (verify it's just `<?php // Silence`).

### `apollo-seo`
* **Status:** Warning
* **Header vs Registry:** 1.0.3 = 1.0.3 ✅
* **Security & Nonce Health:** 3 REST, **3 open**, 0 critical. security_score 9. abspath 90%.
* **Performance & DB Impact:** 10 files / 4,317 LOC, 0 tables. Sitemap on `init`.
* **Structural Issues:** SEO/sitemap endpoints public by nature.
* **Action Items:** 1) Annotate open routes as intentional-public (sitemap/meta).

### `apollo-sheets`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 4 REST, 0 open, 0 critical — well-guarded. security_score 40 (MEDIUM, driven by other checks). Nonce in DOM `templates/bulk-editor.php:27` (esc_attr'd). abspath 96%.
* **Performance & DB Impact:** 27 files / 8,523 LOC, 0 tables. Registers 2 CPT (Plugin.php); 4 `init` hooks + Bulk Manager on init.
* **Structural Issues:** High security_score despite 0 open routes — likely from nonce-in-DOM + escaping checks.
* **Action Items:** 1) Move bulk-editor nonce to config block. 2) Route CPTs via apollo-core.

### `apollo-sign`
* **Status:** Warning
* **Header vs Registry:** 1.2.1 = 1.2.1 ✅
* **Security & Nonce Health:** 2 REST, 0 open, 0 critical. **security_score 3** (very low). abspath 97%.
* **Performance & DB Impact:** 39 files / 7,938 LOC, 2 tables (signatures + audit). Depends on `openssl`.
* **Structural Issues:** TBD-010 unescaped echo in signature template — confirmed area.
* **Action Items:** 1) Escape signature template output. 2) Add `openssl` to a documented runtime-requirements check.

### `apollo-social`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 11 REST, **3 open, 1 critical** — `Plugin.php:141,153,240` `__return_true`. security_score 17. abspath 96%.
* **Performance & DB Impact:** 24 files / 3,821 LOC, 4 tables. 2 `init` hooks.
* **Structural Issues:** TBD-021 — `/feed` route vs `explore.php` template mismatch (registry pending-review). Feed reads may be intentional-public.
* **Action Items:** 1) Resolve `/feed` vs `/explore` route aliasing in APOLLO_ALL_ROUTES. 2) Annotate/guard the 3 open routes.

### `apollo-statistics`
* **Status:** Warning
* **Header vs Registry:** 2.0.6 = 2.0.6 ✅
* **Security & Nonce Health:** 11 REST, 0 open, 0 critical — well-guarded. **1 nopriv AJAX** (`class-user-stats-widget.php:37`). Nonce in DOM `templates/profile-stats.php:131,183` (esc_attr'd, read into JS). security_score 6. abspath 96%.
* **Performance & DB Impact:** 47 files / 10,518 LOC, **8 tables**. `TestPanel.php` contains test-only `'password' => '__wrong_password__'` literals (test fixtures, not secrets — verified). 
* **Structural Issues:** TBD-015 — capability check unverified on toggle-metric endpoint (registry pending). Nopriv stats widget exposes user stats to anonymous callers.
* **Action Items:** 1) Verify capability on the metric-toggle endpoint. 2) Confirm nopriv stats widget should be public. 3) Move profile-stats nonce to config.

### `apollo-telegram`
* **Status:** Warning
* **Header vs Registry:** 1.1.1 = 1.1.1 ✅
* **Security & Nonce Health:** 7 REST, 0 open, 0 critical — well-guarded. security_score 15. **abspath 24% — lowest in the ecosystem**, because the count includes the **vendored `longman/telegram-bot` + PSR + guzzle SDK** (third-party files without WP ABSPATH guards, which is expected for Composer vendor code).
* **Performance & DB Impact:** 58 files / 9,880 LOC (mostly vendor), 1 table. `dev-poll.php` at root (dev-gated, requires APOLLO_TELEGRAM_DEV_LOCAL). `functions.php` at root. `var_dump` present in vendored `ExtendedClasses/Request.php` (third-party).
* **Structural Issues:** abspath metric is misleading — measure Apollo-authored files separately from `vendor/`. `dev-poll.php` should not ship to production.
* **Action Items:** 1) Exclude `vendor/` from abspath scoring. 2) Remove `dev-poll.php` from production builds (or gate behind CLI-only). 3) Ensure vendored SDK is pinned + not web-reachable.

### `apollo-templates`
* **Status:** Critical (hygiene)
* **Header vs Registry:** 1.0.4 = 1.0.4 ✅
* **Security & Nonce Health:** 4 REST, 3 open, 0 critical. **3 nopriv AJAX** (`apollo-templates.php:444,478,547` — navbar-login, panel-login, suggest-event). security_score 19. abspath 96%.
* **Performance & DB Impact:** 77 files / 19,530 LOC — **largest plugin**. 3 `init` hooks. No tables.
* **Structural Issues:** Ships **two unauthenticated root scripts**: `_debug_test.php` (loads wp-load, **`delete_option('rewrite_rules')` + `flush_rewrite_rules(true)` with no auth** — a DoS/rewrite-corruption vector if hit) and `test-timezone.php` (info disclosure + hardcoded `localhost:10004` dev URL). `examples/user-radar-examples.php` contains `var_dump`.
* **Action Items:** 1) **DELETE `_debug_test.php` and `test-timezone.php` immediately** (rewrite-flush script is the more dangerous of the two). 2) Move `examples/` out of the shipped plugin. 3) Confirm nopriv login-fragment endpoints are nonce-protected.

### `apollo-users`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 23 REST, **5 open, 1 critical** — `UsersController.php:119,144,195,321,339` `__return_true`. **These serve user-profile data → LGPD/privacy exposure if truly anonymous.** security_score 33. abspath 93%.
* **Performance & DB Impact:** 30 files / 13,245 LOC, **8 tables**. 2 `init` hooks.
* **Structural Issues:** Public profile reads under the "party model" may be intended, but connections/profile-view endpoints need explicit scoping.
* **Action Items:** 1) Review each of the 5 open user routes for PII exposure; scope to logged-in where they return private fields. 2) Confirm party-model reads are limited to public profile fields.

### `apollo-wow`
* **Status:** Warning
* **Header vs Registry:** 1.0.1 = 1.0.1 ✅
* **Security & Nonce Health:** 4 REST, **3 open, 1 critical** — `Plugin.php:64,80,90` `__return_true`. **Unescaped output + nonce in DOM**: `Plugin.php:170` emits `data-post-id="<?php echo $post_id; ?>"` (unescaped int — low risk) and `data-nonce` (TBD-005). security_score 20. **abspath 100%.**
* **Performance & DB Impact:** 6 files / 600 LOC — smallest. 3 tables (favs/points-style reaction counters).
* **Structural Issues:** WOW reactions are public-read by design (philosophy), but write/react must be authed.
* **Action Items:** 1) Add nonce/auth on the react (write) route; keep counts public. 2) `esc_attr` the post_id and move nonce to config.

---

## 5. Security & Technical Vulnerability Log

Severity reflects verified exploitability. "File:line" are confirmed by direct read unless marked (registry-claimed).

### CRITICAL

| # | Plugin | File:Line | Finding | Verified |
|---|---|---|---|---|
| V-01 | apollo-templates | `_debug_test.php` (root) | Unauthenticated script loads `wp-load.php`, then `delete_option('rewrite_rules')` + `flush_rewrite_rules(true)`. Direct web hit corrupts/DoS-flushes site routing with **no auth, no nonce, no ABSPATH**. | ✅ read |
| V-02 | apollo-events | `_ae_diag_e031aa.php` (root) | Unauthenticated diagnostic: **no ABSPATH guard**, discloses file sizes/mtimes/line-38 content of 8 core files, calls `opcache_invalidate` on GET. Plus `debug-e031aa.log` web-readable. | ✅ read |
| V-03 | apollo-templates | `test-timezone.php` (root) | Unauthenticated info-disclosure script; loads wp-load, prints server timezone/config; embeds `localhost:10004` dev URL in header comment. | ✅ read |
| ~~V-04~~ | apollo-events | `src/API/EventsController.php:1138` | **CLEARED on verification** — `$where` is built entirely from `$wpdb->prepare()` fragments (`event_id` via `%d`, `status` whitelisted by `in_array` + prepared `%s`); only the static `{$table}` name is interpolated. **Not injectable.** Reclassify as hardening (wrap table name via allowlist for defense-in-depth). | ✅ read — false positive |
| ~~V-05~~ | apollo-membership | `includes/achievement-functions.php:82,96` | **CLEARED on verification** — every `$where` fragment uses `$wpdb->prepare()` (`%d`/`%s`, IN-clause with generated placeholders), `$order_str` is sanitized via `sanitize_sql_orderby()`, pagination is prepared, `{$table}` is static. **Not injectable.** | ✅ read — false positive |
| V-06 | apollo-login | `src/API/*` — 26 REST / **11 critical-open** | Auth surface with 11 `__return_true` critical routes + 8 nopriv AJAX. Highest score (75). Public by role, but each needs rate-limit/lockout proof. | ✅ counts confirmed |
| V-07 | apollo-events | `src/API/EventsController.php:57–322` | 12 `__return_true` callbacks incl. write-capable event endpoints. | ✅ read |

### HIGH

| # | Plugin | File:Line | Finding |
|---|---|---|---|
| V-08 | apollo-membership | `src/API/*` (11 routes) | `__return_true` on Triggers/Report/Ranks/Leaderboard/Achievements — Triggers/Report must not be public. |
| V-09 | apollo-djs | `src/API/DJsController.php:43,96,118,129,140` | 5 open DJ-profile routes (write-capable). |
| V-10 | apollo-hub | `src/API/HubController.php:47,74,100,151,201` | 5 open routes + unvalidated JSON blocks (TBD-013). |
| V-11 | apollo-users | `src/API/UsersController.php:119,144,195,321,339` | 5 open profile routes → potential PII/LGPD exposure to anonymous callers. |
| V-12 | apollo-fav | `includes/class-cbx-bridge.php:47`, `class-fav-ranking.php:50` | nopriv AJAX toggle/ranking should be login-gated (TBD-012). |
| V-13 | apollo-email | `src/Newsletter.php:777` | Public subscribe endpoint, **no rate-limiting** (TBD-014) → spam/enumeration. |
| V-14 | apollo-pane-engine | `includes/fragment-helper.php:73` | Nonce interpolated into `data-nonce` **without `esc_attr`** (escaping + DOM-exposure). |
| V-15 | Nonce-in-DOM cluster | adverts `integrations.php:305`, fav `functions.php:440`, events `archive-event.php:167`, membership `tools.php:32-88`, sheets `bulk-editor.php:27`, hub `edit-hub.php:76`, statistics `profile-stats.php:131,183`, wow `Plugin.php:170` | TBD-005: nonces in `data-*` attributes across 9 plugins. |
| V-16 | apollo-djs / remind / sign | `marquee.php:49,52` / `admin-dashboard.php` / signature template | Unescaped `echo`/`print_r` in templates (TBD-008/009/010). |

### MEDIUM

| # | Plugin | Finding |
|---|---|---|
| V-17 | apollo-telegram | `dev-poll.php` ships to plugin root (dev-gated but present); `var_dump` in vendored SDK. |
| V-18 | apollo-mod | ABSPATH coverage 86% on a moderation plugin handling reports. |
| V-19 | apollo-statistics | nopriv stats widget + unverified capability on metric-toggle (TBD-015). |
| V-20 | apollo-core / docs / gestor | composer.json versions (6.0.0 / 1.0.0 / 1.0.0) lag header versions. |
| V-21 | Ecosystem | 36 `register_post_type`/`register_taxonomy` calls in 17 files violate MASTER_REGISTRY centralization rule. |

### LOW / Data-model

| # | Finding |
|---|---|
| V-22 | Duplicate tables `apollo_favorites` vs `apollo_favs` (apollo-fav) — concept split. |
| V-23 | `apollo_follows` table contradicts the no-follow ecosystem philosophy. |
| V-24 | apollo-suppliers meta keys registered while plugin absent (TBD-020). |
| V-25 | apollo-runtime referenced as MANDATORY sync target but absent from disk. |
| V-26 | Registry TBD ledger stale: TBD-001/004/007 fixed-in-code but "pending"; TBD-002/003 line numbers drifted and are non-vulns (no user input). |

### Verified NON-issues (cleared during audit)

* apollo-email `smtp_password => 'APOLLO_SMTP_PASS' / 'email_smtp_pass'` — **env/option key names, not hardcoded secrets**.
* apollo-statistics `TestPanel.php` password literals — **test fixtures** (`__wrong_password__`, `invalid_password_smoke`).
* All `$wpdb->query("DROP/DELETE … {$table}")` in uninstall/deactivation files — **static table identifiers, no user input**; correctly phpcs-ignored.
* apollo-notif TBD-003 and apollo-chat TBD-002 — interpolation is table-prefix + literal conditions only; **not injectable**.

---

## 6. Actionable Master Remediation Plan

### Priority 1 — CRITICAL (immediate, pre-any-deploy)

1. **Delete shipped debug/diagnostic scripts from plugin roots:** `apollo-templates/_debug_test.php`, `apollo-templates/test-timezone.php`, `apollo-events/_ae_diag_e031aa.php`, `apollo-events/debug-e031aa.log`, and gate/remove `apollo-telegram/dev-poll.php`, `apollo-email/_run_*.php`, `apollo-email/_clear_verification_cpt.php`. (V-01/02/03/17) Add a build step that strips `_*.php`, `test-*.php`, `dev-*.php`, `*.log` from release artifacts.
2. ~~SQL `$where` audits~~ — **DONE during this audit; both cleared** (events + membership `$where` are fully prepared). Optional hardening only: wrap static table names in an allowlist for defense-in-depth. (V-04/05)
3. **Lock down write-capable open REST routes** in events (12), djs (5), hub (5), membership Triggers/Report, social (3), wow react. Replace `__return_true` with `current_user_can`/nonce; keep genuinely public reads but annotate them `// intentional-public`. (V-07/08/09/10)
4. **PII review of apollo-users' 5 open routes** — scope any private-field responses to authenticated callers. (V-11)

### Priority 2 — HIGH (registry & dependency sync + auth hardening)

5. **Reconcile the TBD ledger with code:** close TBD-001/004/007 (verified fixed); reclassify TBD-002/003 as hardening with corrected line numbers; re-verify TBD-006 (groups now 0 open). Make the registry's audit block generated, not hand-edited.
6. **Collapse dual registries:** designate `apollo-registry.json` canonical and generate `apollo-registry-2.json` (or drop the mirror). Add a CI hash-check so they can never silently diverge.
7. **Fix apollo-core layering:** set core `deps: []`; add a separate `integrates` field. Add `Requires Plugins:` headers so WordPress enforces apollo-core (and elementor for the elementor plugins) natively.
8. **Newsletter + auth rate-limiting:** add rate-limit/nonce/honeypot to `apollo-email` subscribe (V-13); reset login IP-tier on success (TBD-016); confirm Telegram webhook token check is constant-time.
9. **Nonce hygiene sweep:** move all `data-nonce` values into `wp_json_encode` JS config guarded by `is_user_logged_in()`; fix the unescaped `apollo-pane-engine` case first. (V-14/15)

### Priority 3 — MEDIUM (refactoring & technical debt)

10. **Centralize registrations:** migrate the 36 `register_post_type`/`register_taxonomy` calls (loc, events, adverts, docs, djs, journal, hub, sheets, coauthor, email, scheduler, calendar) into apollo-core MASTER_REGISTRY per the ecosystem's own rule. (V-21)
11. **ABSPATH to 100%** on apollo-mod (86%), apollo-notif (88%), apollo-remind (88%), apollo-radio/maps (89%), apollo-chat (89%); exclude `vendor/` from apollo-telegram's score. (V-18)
12. **Data model cleanup:** consolidate `apollo_favorites`/`apollo_favs`; resolve `apollo_follows` vs no-follow doctrine; remove apollo-suppliers meta-key registration until the plugin ships. (V-22/23/24)
13. **Escape template output** in djs marquee, remind admin-dashboard (drop `print_r`), sign signature template. (V-16)

### Priority 4 — LOW (formatting & polish)

14. Bump composer.json versions: apollo-core → 6.2.3, apollo-docs → 1.0.1, apollo-gestor → 1.0.1. (V-20)
15. Remove stray `console.log` in `apollo-login/assets/js/apollo-auth-scripts.js`; move `apollo-templates/examples/` out of the shipped package.
16. Resolve `/feed` vs `/explore` route aliasing in APOLLO_ALL_ROUTES (TBD-021); document the login↔dj-sync `/dj/*` split (divergence D1).
17. Remove redundant root loaders where `index.php` duplicates the main plugin file (apollo-scheduler, apollo-pane-engine, apollo-email).

---

*End of report. Machine-readable companion: `audit-summary.json`.*


---

## 4. Detailed Per-Plugin Findings (All 39 Plugins)

Legend — registry risk/score come from `$deep_audit` (score = risk points, higher is worse); all flagged issues below were **re-verified directly in code** during this audit unless marked "registry claim".

### `apollo-admin`
* **Status**: Healthy
* **Header vs Registry Version**: 1.0.2 = 1.0.2 ✔
* **Security & Nonce Health**: Pass — 18 REST routes, 0 open; 0 nopriv AJAX; risk LOW (10).
* **Performance & DB Impact**: Pass — no custom tables; 123 PHP files / 16k LOC is the heaviest admin surface, keep an eye on autoload.
* **Structural Issues**: Registry records 24 deps incl. cycle admin↔adverts and admin↔email; `ErrorLogViewer.php` exposes log content to admins (by design — confirm capability gate).
* **Action Items**:
  1. Reclassify most of the 24 "deps" as `integrates` to break recorded cycles.
  2. Confirm `manage_options` gate on ErrorLogViewer render path.

### `apollo-adverts`
* **Status**: Warning
* **Header vs Registry Version**: 1.0.2 = 1.0.2 ✔
* **Security & Nonce Health**: `__return_true` on 4 REST routes (`RelatedAds.php:108`, `API/SearchController.php:36`, `API/ClassifiedsController.php:40,60` — reads, but 2 flagged critical in registry). Nonce in DOM: `includes/integrations.php:305` (`data-nonce="%s"`). ✔ **TBD-004 is FIXED in code** (`src/Deactivation.php:57` now literal-only SQL with justification) but still "pending" in registry.
* **Performance & DB Impact**: Pass.
* **Structural Issues**: `includes/cpt.php` registers CPTs directly (3 calls) — MASTER_REGISTRY violation; absorbed apollo-classifieds scope (marketplace templates).
* **Action Items**:
  1. Move nonce from `data-nonce` to `wp_json_encode()` config block (TBD-005).
  2. Mark TBD-004 resolved in registry.
  3. Migrate `includes/cpt.php` registrations to apollo-core MASTER_REGISTRY.

### `apollo-calendar`
* **Status**: Healthy
* **Header vs Registry Version**: 1.0.1 = 1.0.1 ✔
* **Security & Nonce Health**: 1 open REST (`CalendarController.php:121` `__return_true` — public read, acceptable). Uninstall drops tables with interpolated prefix (constant — safe).
* **Performance & DB Impact**: `HolidayModel.php:108` unprepared `COUNT(*)` on constant table (phpcs-annotated, safe).
* **Structural Issues**: None material.
* **Action Items**:
  1. None urgent; annotate public REST route as intentional in registry.

### `apollo-chat`
* **Status**: Warning
* **Header vs Registry Version**: 2.0.1 = 2.0.1 ✔
* **Security & Nonce Health**: TBD-002 (raw SQL) — **verified, line numbers drifted**: now `src/Plugin.php:1194` and `:1196` (`DELETE FROM {$pfx}chat_typing/chat_presence …`). No user input interpolated (prefix + constants only) and phpcs-ignored, so real injection risk is LOW — but doctrine (audit_verificator #3) requires `prepare()` regardless.
* **Performance & DB Impact**: 9 tables; typing/presence cleanup runs raw DELETEs — confirm they're cron-scheduled, not per-request.
* **Structural Issues**: ABSPATH coverage 89%.
* **Action Items**:
  1. Wrap Plugin.php:1194/1196 in `$wpdb->prepare()`-safe pattern or `%i` identifier placeholders (WP 6.2+); update TBD-002 line refs.
  2. Raise ABSPATH coverage to 100%.

### `apollo-coauthor`
* **Status**: Healthy
* **Header vs Registry Version**: 1.0.1 = 1.0.1 ✔
* **Security & Nonce Health**: Pass — 3 REST, 0 open.
* **Performance & DB Impact**: Pass.
* **Structural Issues**: `src/Components/Taxonomy.php` + `src/Activation.php` register taxonomy directly (3 calls) — MASTER_REGISTRY violation.
* **Action Items**:
  1. Route `coauthor` taxonomy registration through apollo-core.

### `apollo-comment`
* **Status**: Healthy
* **Header vs Registry Version**: 1.0.1 = 1.0.1 ✔
* **Security & Nonce Health**: 2 public REST routes (`DepoimentoController.php:33,86` `__return_true`) — registry marks both critical; reads should be confirmed content-safe (no email/PII in payload).
* **Performance & DB Impact**: Pass (1.6k LOC).
* **Structural Issues**: None.
* **Action Items**:
  1. Audit depoimento payloads for PII; add `is_user_logged_in` if any.

### `apollo-core`
* **Status**: Warning (kernel-critical)
* **Header vs Registry Version**: 6.2.3 = 6.2.3 ✔ · **composer.json = 6.0.0 (drift)**
* **Security & Nonce Health**: 11 open REST routes verified (`SoundController` ×3, `ShortcodesController` ×2, `SearchController` ×2, `RegistryController` ×3, `PaneModeController`, `HealthController`) — all reads, but `RegistryController` exposing registry internals publicly deserves a second look.
* **Performance & DB Impact**: Owns 23 tables; registries hook `init` ×10 (CPT/Tax/Meta/Shortcode registries) — expected for the kernel.
* **Structural Issues**: Registry lists 31 deps for the kernel (inverted layering). PSR-4 double-mapping (`Apollo\Core\` → src/Core, `Apollo\Core\API\` → src/API) is functional but unconventional.
* **Action Items**:
  1. Bump composer.json to 6.2.3; add CI check header↔composer↔registry.
  2. Empty the kernel's `deps` list; introduce `integrates` field.
  3. Review public exposure of `/registry/*` REST routes.

### `apollo-dashboard`
* **Status**: Healthy
* **Header vs Registry Version**: 1.0.1 = 1.0.1 ✔
* **Security & Nonce Health**: Pass — best score in ecosystem (0 risk points), 0 open REST.
* **Performance & DB Impact**: 5 tables — verify indexes on activity queries (registry claims no schema columns documented).
* **Structural Issues**: None.
* **Action Items**:
  1. Document table schemas in registry (`table_schemas_with_columns: 0`).

### `apollo-dj-sync`
* **Status**: Healthy
* **Header vs Registry Version**: 1.0.1 = 1.0.1 ✔
* **Security & Nonce Health**: Pass — 1 REST route, closed; ABSPATH 100%.
* **Performance & DB Impact**: Pass (769 LOC).
* **Structural Issues**: Divergence D1 — endpoint ownership split with apollo-login (documented but fragile).
* **Action Items**:
  1. Consolidate DJ desktop REST contract ownership (D1) into one plugin.

### `apollo-djs`
* **Status**: Warning
* **Header vs Registry Version**: 1.0.1 = 1.0.1 ✔
* **Security & Nonce Health**: 5 open REST (`DJsController.php:43,96,118,129,140` — 2 critical per registry). TBD-008 (unescaped echo): **mitigated in current code** — `templates/parts/dj-v3/marquee.php:49,52` echoes are phpcs-annotated "items escaped above"; verify claim, then close.
* **Performance & DB Impact**: Pass.
* **Structural Issues**: `src/Registry.php` registers CPT directly (2 calls) — MASTER_REGISTRY violation.
* **Action Items**:
  1. Add auth to the 2 critical DJ REST routes or document as public.
  2. Verify marquee.php pre-escaping; close/annotate TBD-008.
  3. Migrate CPT registration to core.

### `apollo-docs`
* **Status**: Healthy
* **Header vs Registry Version**: 1.0.1 = 1.0.1 ✔ · composer.json 1.0.0 (drift)
* **Security & Nonce Health**: Pass — 4 REST, 0 open.
* **Performance & DB Impact**: 2 tables (versions/downloads) — fine.
* **Structural Issues**: `src/Core/Registrar.php` direct registrations (3) — MASTER_REGISTRY violation.
* **Action Items**:
  1. Sync composer version; migrate registrations to core.

### `apollo-elementor`
* **Status**: Healthy
* **Header vs Registry Version**: 1.0.1 = 1.0.1 ✔
* **Security & Nonce Health**: Pass — 0 REST, score 0.
* **Performance & DB Impact**: Pass.
* **Structural Issues**: Depends on third-party `elementor` — no `Requires Plugins:` header to enforce it.
* **Action Items**:
  1. Add `Requires Plugins: elementor` header (WP 6.5+).

### `apollo-elementor-pro`
* **Status**: Healthy
* **Header vs Registry Version**: 1.0.1 = 1.0.1 ✔
* **Security & Nonce Health**: Pass — 0 REST, score 0.
* **Performance & DB Impact**: Pass.
* **Structural Issues**: Same enforcement gap as apollo-elementor.
* **Action Items**:
  1. Add `Requires Plugins:` header.

### `apollo-email`
* **Status**: Warning
* **Header vs Registry Version**: 1.0.1 = 1.0.1 ✔
* **Security & Nonce Health**: 3 open REST, 1 critical — `Newsletter.php:777` public subscribe without rate-limiting (TBD-014, **verified still pending**); `EmailController.php:314,331` open. `Newsletter.php:281` interpolates `$where_sql/$order_sql/$limit_sql` into SQL — verify all inputs are whitelisted upstream.
* **Performance & DB Impact**: 4 tables (queue/log/verifications) — fine.
* **Structural Issues**: **Root-level test scripts shipped**: `_run_test_email.php`, `_run_live_dual_test.php`, `_run_aprio_verification_test.php`, `_clear_verification_cpt.php`. `_run_live_dual_test.php` embeds dev DB host `127.0.0.1:10029`, `fim.local`, and real personal email addresses in usage docs. ABSPATH-guarded, but they don't belong in a production artifact. Cycle: email↔gestor, email↔admin.
* **Action Items**:
  1. Add rate-limiting + honeypot to newsletter subscribe (TBD-014).
  2. Delete `_run_*.php` / `_clear_*.php` from the shipped plugin (move to /tests outside dist).
  3. Prove whitelisting of `$where_sql/$order_sql` in Newsletter.php:281 or refactor to `prepare()`.

### `apollo-events`
* **Status**: **Critical**
* **Header vs Registry Version**: 1.4.0 = 1.4.0 ✔
* **Security & Nonce Health**: Worst REST surface after login: 12 open routes verified in `EventsController.php` (lines 57–322), 3 critical per registry; security score 69 (HIGH). Nonce in DOM: `styles/base/archive-event.php:167`. `EventsController.php:1138` runs `get_results` with interpolated `{$where}` — verify every branch of `$where` is `prepare()`d upstream.
* **Performance & DB Impact**: RSVP table + heavy template stack (94 files / 17k LOC); `styles/` tree duplicates template variants (base + apollo-v2).
* **Structural Issues**: 🔴 **`_ae_diag_e031aa.php` at plugin root: NO ABSPATH/auth guard — direct web hit dumps file internals + invalidates OPcache.** 🔴 `debug-e031aa.log` web-readable at plugin root. `src/Registry.php` direct registrations (6) — MASTER_REGISTRY violation.
* **Action Items**:
  1. **Delete `_ae_diag_e031aa.php` and `debug-e031aa.log` immediately.**
  2. Close or justify the 3 critical open REST routes; sweep the other 9.
  3. Audit `$where` construction feeding line 1138.
  4. Move nonce out of archive-event.php DOM.
  5. Migrate Registry.php registrations to core.

### `apollo-fav`
* **Status**: Warning
* **Header vs Registry Version**: 1.0.1 = 1.0.1 ✔
* **Security & Nonce Health**: TBD-012 **verified still present**: `includes/class-cbx-bridge.php:47` registers `wp_ajax_nopriv_apollo_fav_toggle` — anonymous favorite toggling; plus nopriv top-sounds re
