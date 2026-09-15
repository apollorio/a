# W5 — FORBIDDEN / NAMING audit

**Worker:** W5 (FORBIDDEN/NAMING)  
**Coordinator:** Grok  
**Model:** Composer 2.5  
**Date:** 2026-09-15  
**Scope:** `apollo-*` grep, **excluding** `apollo-core/config/**` and **apollo-waha/**  
**Mode:** report-only (no product PHP edits)

---

## 1. Registry extracts

### 1.1 `$philosophy.FORBIDDEN_CONCEPTS` (`01-philosophy.json`)

| Concept |
| --- |
| follow button |
| unfollow button |
| followers count |
| following count |
| like button |
| friend request |
| friend count |

Related philosophy keys:

- `NO_SELECTIVE_FOLLOW` — no follow/unfollow; auto-connect only
- `NO_FRIENDS_HIERARCHY` — no friends/followers/following hierarchy
- `NO_EGO_COUNTERS` — no follower/following/friend counts as status
- `WOW_NOT_LIKE` — reactions use WOW, not likes

### 1.2 `namingRules.FORBIDDEN_TERMS` (`15-conventions.json`)

| Term | Use instead |
| --- | --- |
| venue, local, location | loc |
| interesse, interessado, interest, bookmark | fav |
| like, heart, reaction | wow |
| comment, review | depoimento |
| cult | cena |
| document | doc |
| /user/ | /id/ |

### 1.3 `$apollo_rule` (`03-apollo-rule.json`)

Relevant slices for this audit:

- `development_guidelines.naming` → `namingRules.FORBIDDEN_TERMS`
- `development_guidelines.time` → **MANDATORY:** `apollo_time_ago()` — never `human_time_diff()` + `'atrás'`
- `timeDisplay.FORBIDDEN` → `human_time_diff()` + `'ago'` / `'atrás'`, `Xd atrás`, etc.

### 1.4 CPT/meta SSOT (workspace rule, not a grep term)

CPT/taxonomy/meta registration is **exclusively** `apollo-core` (`MetaRegistry.php` / `CPTRegistry.php`). Plugins may contribute via `apollo_core_register_post_meta` filter; direct `register_post_meta()` outside core is a G1 violation.

---

## 2. Method

Grep patterns (case-insensitive where noted):

- `follow`, `unfollow`
- `like button` (and related UI: `like-btn`, `.like`, `btn-like` — none found)
- `friend request`
- `human_time_diff`
- `register_post_type`, `register_post_meta`

Excluded paths: `apollo-core/config/**`, `apollo-waha/**`, vendor trees (`apollo-telegram/vendor/**`), phpcs log dumps, `.bak` artefacts (listed separately as noise).

---

## 3. Summary counts

| Pattern | TRUE_VIOLATION | FALSE_POSITIVE | CORE_OK |
| --- | ---: | ---: | ---: |
| follow / unfollow | **28** | **42** | **6** |
| like button / heart-as-like | **1** | **12** | **0** |
| friend request | **0** | **0** | **0** |
| human_time_diff | **18** | **8** | **0** |
| register_post_type | **2** | **9** | **1** |
| register_post_meta | **2** | **3** | **2** |

**Top offenders (TRUE_VIOLATION):** `apollo-statistics`, `apollo-journal`, `apollo-hub`, `apollo-admin`, `apollo-social`, `apollo-notif`, `apollo-membership`, `apollo-pane-engine`.

**friend request / friend count:** zero disk hits in scope.

---

## 4. Hits list (classified)

Legend: **TV** = TRUE_VIOLATION · **FP** = FALSE_POSITIVE · **CO** = CORE_OK

### 4.1 follow / unfollow

| Plugin | File | Line(s) | Match | Verdict | Notes |
| --- | --- | --- | --- | --- | --- |
| apollo-statistics | `src/API/ProfileController.php` | 54, 104–105 | `followers` visibility + `is_following` | **TV** | Selective-follow gate for stats |
| apollo-statistics | `src/API/StatsController.php` | 233–236 | `followers` visibility | **TV** | Same |
| apollo-statistics | `src/Collectors/HookCollector.php` | 42, 54, 183–187 | `apollo/dj/followed`, `apollo/social/follow` | **TV** | Follow action metrics |
| apollo-statistics | `src/Core/MetricBootstrap.php` | 193, 353 | `follow_received`, `metric_type = 'follow'` | **TV** | Leaderboard / growth on follow |
| apollo-statistics | `includes/class-analytics.php` | 51–54, 94, 145 | `followers`, `apollo_is_following`, `followers_gained` | **TV** | Follower analytics surface |
| apollo-statistics | `includes/class-user-stats-widget.php` | 164–167, 214 | `followers_gained`, "New Followers", visibility option | **TV** | Ego counter UI |
| apollo-statistics | `README.md` | 115 | `public\|followers\|private` | **TV** | Documents forbidden visibility model |
| apollo-social | `assets/css/feed.css` | 1164–1235 | `.sidebar-follow-*`, `.sidebar-follow-btn` | **TV** | Dead or latent follow-button chrome |
| apollo-social | `includes/functions.php` | 143, 166 | `apollo_follows` table inserts | **CO** | Party-model auto-connect infra (not selective UI) |
| apollo-social | `src/Activation.php` | 63–92 | mutual `follows` batch insert | **CO** | Auto-connect on activation |
| apollo-social | `src/Plugin.php` | 12–13, 55–67, 539–547, 584, 634–637 | `apollo_follow_btn`, `is_following` filter | **FP/CO** | Shortcode stub returns "Conectado"; filter serves party model |
| apollo-pane-engine | `pane-engine-casa.json` | 183–184 | `/followers/*`, `/following/*` | **TV** | Routes name forbidden hierarchy |
| apollo-notif | `src/Plugin.php` | 877–879 | `get_followers` filter loop | **TV** | Notifies "followers" on event publish |
| apollo-notif | `src/Plugin.php` | 68, 790, 1432 | comments / type filter `follow` | **FP** | Guard-rail comments / legacy type cleanup |
| apollo-notif | `templates/notifications.php.bak` | 437, 513 | `Follow` option, `follow` icon | **FP** | `.bak` artefact only |
| apollo-membership | `src/API/TriggersController.php` | 68 | `apollo_dj_followed` → "Seguir DJ" | **TV** | Forbidden follow trigger label |
| apollo-fav | `includes/class-statistics-merge.php` | 110 | `get_follow_growth` | **TV** | Follow-growth metric hook |
| apollo-admin | `templates/partials/sections/social/activity.php` | 26–27 | "Enable Follow System" toggle | **TV** | Admin exposes selective follow |
| apollo-admin | `base-design-and-reference.html` | 2892 | same toggle (mock) | **TV** | Design reference duplicate |
| apollo-admin | `templates/partials/topbar.php` | 39 | `ri-user-unfollow-line` | **FP** | Email **unsubscribe** tab, not social unfollow |
| apollo-admin | `templates/partials/sections/email/unsub.php` | 16 | `ri-user-unfollow-line` | **FP** | Unsubscribe report |
| apollo-admin | `templates/partials/sections/email/subscribers.php` | 19 | `ri-user-unfollow-line` | **FP** | Unsubscribe stat card |
| apollo-admin | `assets/modera/js/views.js` | 190 | `ri-user-unfollow-line` | **FP** | Modera member-remove icon |
| apollo-chat | `assets/js/chat.js` | 1549, 1771 | `ri-user-unfollow-line`, block user | **FP** | Block user, not unfollow |
| apollo-hub | `templates/hub-app.html` | 191–193, 231 | `.analytics-followers` | **FP** | Misleading class name; label is **cliques totais** |
| apollo-remind | `src/Core/Plugin.php` | 111 | "auto-remind followers" comment | **FP** | Reserved hook comment |
| apollo-djs | `templates/parts/dj-card/toast.php` | 4 | "share / follow feedback" | **FP** | Comment only; no follow UI |
| apollo-djs | `includes/functions.php` | 360 | "follow-up" | **FP** | English prose |
| apollo-login | `templates/profile.php` | 56 | `robots: index,follow` | **FP** | SEO robots directive |
| apollo-login | `templates/verify-email.php` | 37 | `noindex,nofollow` | **FP** | SEO robots |
| apollo-login | `templates/reset.php` | 50 | `noindex,nofollow` | **FP** | SEO robots |
| apollo-login | `templates/register.php` | 92 | `noindex,nofollow` | **FP** | SEO robots |
| apollo-login | `src/Security/Firewall.php` | 453 | `noindex,nofollow` | **FP** | SEO robots |
| apollo-login | `src/Security/URLRewriter.php` | 158 | `noindex,nofollow` | **FP** | SEO robots |
| apollo-login | `src/API/ActivityLogController.php` | 326 | "Follows the same lazy-creation pattern" | **FP** | English prose |
| apollo-pane-engine | `templates/page-casa.php` | 65 | `noindex, nofollow` | **FP** | SEO robots |
| apollo-hub | `templates/edit-hub.php` | 43 | `noindex, nofollow` | **FP** | SEO robots |
| apollo-events | `assets/js/apollo-event-lightbox.js` | 217, 250 | `redirect: 'follow'` | **FP** | Fetch API redirect mode |
| apollo-events | multiple | — | "follow" in comments/prose | **FP** | Non-social English |
| apollo-loc | `includes/surface.php` | 19 | "lightbox follow" | **FP** | English prose |
| apollo-lux-panels | `includes/Panel.php` | 447 | "access followed by count()" | **FP** | English prose |
| apollo-adverts | `src/Safety/NativeGraph.php` | 10, 16 | Instagram follower list | **FP** | External-platform context |
| apollo-adverts | `src/API/SafetyController.php` | 8 | follower fetches | **FP** | Safety integration doc |
| apollo-adverts | `includes/safety-gate.php` | 339 | follower list | **FP** | Instagram API limitation |
| apollo-adverts | `templates/parts/safety/check.php` | 9 | follower fetches | **FP** | Comment |
| apollo-adverts | `templates/parts/safety/checks.php` | 37 | follower list | **FP** | Comment |
| apollo-adverts | `assets/css/safety-gate.css` | 8 | "DOES NOT FOLLOW THE USER'S THEME" | **FP** | English prose |
| apollo-core | `src/Config/ApolloRoute.php` | 63–64 | `FOLLOWERS`, `FOLLOWING` route constants | **TV** | Forbidden route vocabulary in core |
| apollo-core | `src/Core/ShortcodeRegistry.php` | 310–318 | `apollo_follow_button` registered | **TV** | Forbidden shortcode catalogued |
| apollo-core | `src/API/ShortcodesController.php` | 53 | `apollo_follow_button` | **TV** | REST lists forbidden shortcode |
| apollo-core | `src/Core/ActivationHandler.php` | 255 | `apollo_follow_users` cap | **TV** | Capability name encodes follow |
| apollo-core | `src/Core/DatabaseBuilder.php` | 267–278 | `follows` table schema | **CO** | Party-model storage (auto-connect pairs) |
| apollo-core | `src/Config/ApolloTable.php` | 78 | `FOLLOWS` const | **CO** | Table name for party model |
| apollo-core | `includes/blank-canvas-templates.php` | 26, 57, 71 | `index, follow` robots default | **FP** | SEO robots |
| apollo-telegram | `vendor/**`, `README.md` | many | "following conditions" | **FP** | Third-party vendor / docs (out of product scope) |

### 4.2 like button / heart (namingRules: like/heart → wow)

| Plugin | File | Line(s) | Match | Verdict | Notes |
| --- | --- | --- | --- | --- | --- |
| apollo-fav | `includes/functions.php` | 409–442 | "Apollo Heart" button, `apollo-heart-svg` | **TV** | Fav is correct vocab; **heart** icon/name violates `namingRules.heart → wow` |
| apollo-fav | `assets/css/apollo-fav.css` | 5, 43, 81–108 | Apollo Heart styles | **TV** | Same naming drift |
| apollo-fav | `assets/js/apollo-fav.js` | 30, 198 | Apollo Heart toggle | **TV** | Same |
| apollo-fav | `includes/class-cbx-bridge.php` | 201 | "botão Apollo Heart" | **TV** | Same |
| apollo-fav | `apollo-fav.php` | 191 | Apollo Heart CSS enqueue | **TV** | Same |
| apollo-loc | `styles/base/single-local.php` | 1117 | `ri-heart-*` fav icon | **FP** | Fav affordance; icon choice, not a like button |
| apollo-wow | `src/Plugin.php`, `apollo-wow.php` | 5 | "replacing like" | **FP** | Docblock explains migration |
| apollo-templates | `templates/.../new-home/cells/_manifest.php` | 43 | "No like / follow" | **FP** | Guard comment |
| apollo-notif, apollo-membership, etc. | various | — | SQL `LIKE` | **FP** | SQL operator |

**like button:** no literal `like button`, `like-btn`, `.like`, or `data-like` hits in live product PHP/JS (excluding vendor).

### 4.3 friend request

No matches in scope.

### 4.4 human_time_diff

| Plugin | File | Line(s) | Verdict | Notes |
| --- | --- | --- | --- | --- |
| apollo-social | `src/Components/SidebarRenderer.php` | 54 | **TV** | `human_time_diff(...) . ' atrás'` — no `apollo_time_ago_html` |
| apollo-journal | `src/API/PostsController.php` | 124, 240, 309 | **TV** | REST payload uses raw `human_time_diff` |
| apollo-journal | `src/Shortcodes.php` | 340, 381 | **TV** | Fallback path still ships forbidden function |
| apollo-journal | `templates/single-journal_news.php` | 465 | **TV** | Fallback path |
| apollo-journal | `templates/parts/news-grid.php` | 77 | **TV** | Fallback path |
| apollo-journal | `templates/archive-journal.php` | 324, 352 | **TV** | Fallback path |
| apollo-journal | `templates/single-journal_nota.php` | 367 | **TV** | Fallback path |
| apollo-journal | `templates/page-jornal.php` | 466, 493, 546 | **TV** | Fallback path |
| apollo-events | `styles/base/template-parts/single/depoimentos.php` | 59 | **TV** | No `apollo_time_ago_html` guard |
| apollo-gestor | `includes/modules/proj-board/backend/Proj_Board.php` | 67 | **TV** | Kanban `time_ago` field |
| apollo-scheduler | `includes/functions.php` | 140 | **TV** | `human_time_diff` + `'ago'` — double forbidden |
| apollo-templates | `includes/feed-data.php` | 85 | **TV** | Feed sidebar time |
| apollo-templates | `templates/_legacy/page-feed.monolith.php` | 827, 930 | **TV** | Legacy feed; line 930 also `' atrás'` |
| apollo-admin | `templates/frontend/pending.php` | 393 | **TV** | `human_time_diff` + `' atrás'` |
| apollo-adverts | `templates/parts/depoimentos.php` | 81 | **TV** | Depoimento time |
| apollo-users | `templates/parts/profile-header.php.bak` | 79 | **FP** | `.bak` only |
| apollo-templates | `templates/.../new-home/cells/_manifest.php` | 42 | **FP** | Guard comment |
| apollo-templates | `phpcs-logs/FINAL_VIPGO_*.json` | — | **FP** | Lint artefact |

**Clean (apollo_time_ago only, no human_time_diff in scope):** `apollo-users` (live), `apollo-groups`, `apollo-chat`, `apollo-notif`, `apollo-loc`, `apollo-fav`, `apollo-dashboard`, `apollo-sheets` (widget uses apollo helper per grep).

### 4.5 register_post_type

| Plugin | File | Line(s) | Verdict | Notes |
| --- | --- | --- | --- | --- |
| apollo-core | `src/Core/CPTRegistry.php` | 166 | **CO** | SSOT registrar |
| apollo-journal | `src/Plugin.php` | 117, 168 | **TV** | `journal_news` / `journal_nota` not in core registry |
| apollo-events | `src/Registry.php` | 69 | **TV** | Direct call (fallback guard absent on line 69 — always registers if reached) |
| apollo-hub | `src/Registry.php` | 58 | **FP** | Fallback with `post_type_exists` guard |
| apollo-hub | `src/Activation.php` | 45 | **FP** | Activation-time fallback |
| apollo-loc | `src/CPT/CPTRegistrar.php` | 29 | **FP** | Fallback with `post_type_exists` guard |
| apollo-loc | `src/Registry.php` | 9 | **FP** | Comment documenting duplicate |
| apollo-adverts | `includes/cpt.php` | 42 | **FP** | Fallback with guard |
| apollo-djs | `src/Registry.php` | 56, 122 | **FP** | Fallback with guard |
| apollo-email | `src/Core/CPT.php` | 32 | **FP** | Fallback with guard |
| apollo-docs | `src/Core/Registrar.php` | 31 | **FP** | Fallback with guard |
| apollo-scheduler | `src/Registry.php` | 70 | **FP** | Fallback with guard |
| apollo-sheets | `src/Plugin.php` | 113 | **FP** | Fallback with guard |
| apollo-sheets | `src/Bulk/Manager.php` | 129 | **FP** | Method name `register_post_type_columns` — not WP API |
| apollo-sheets | `src/Bulk/ColumnRegistry.php` | 73 | **FP** | Same |
| apollo-events | `_sandbox/build-portal-harness.mjs` | 915, 1266 | **FP** | Harness assertion (not runtime) |

### 4.6 register_post_meta

| Plugin | File | Line(s) | Verdict | Notes |
| --- | --- | --- | --- | --- |
| apollo-core | `src/Core/MetaRegistry.php` | 2243–2323 | **CO** | SSOT + filter dispatch |
| apollo-loc | `src/CPT/MetaRegistrar.php` | 48–80 | **CO** | Migrated to `apollo_core_register_post_meta` filter only |
| apollo-loc | `src/CPT/MetaRegistrar.php.bak-2026-08-11` | 52, 67, 87 | **FP** | Backup artefact |
| apollo-hub | `src/Registry.php` | 87–171 | **TV** | Direct `register_post_meta()` — G1 violation |
| apollo-journal | `src/Plugin.php` | 155, 205 | **TV** | Direct `register_post_meta()` loops |
| apollo-adverts | `includes/cpt.php` | 163 | **FP** | Comment: "must not call register_post_meta() directly" |
| apollo-djs | `src/Registry.php` | 27 | **CO** | Uses `apollo_core_register_meta` filter (correct pattern) |
| apollo-core | `src/Core/MetaRegistry.php.bak-2026-08-11` | 1708–1788 | **FP** | Backup artefact |

---

## 5. Priority findings

1. **Follow hierarchy still ships** — `apollo-statistics` (widgets, visibility, metrics), `apollo-pane-engine` routes, `apollo-admin` follow toggle, `apollo-notif` follower notifications, `apollo-membership` DJ-follow trigger.
2. **Time standard not universal** — 18 production files still call `human_time_diff`; worst: `apollo-scheduler` (`+ 'ago'`), `apollo-social` SidebarRenderer (`+ 'atrás'`), `apollo-journal` REST without fallback removal.
3. **Meta registration drift** — `apollo-hub` and `apollo-journal` still call `register_post_meta()` directly; `apollo-loc` shows the target pattern (filter-only).
4. **CPT registration drift** — `apollo-journal` owns CPTs absent from core; `apollo-events` Registry still registers inline.
5. **Naming: heart-as-fav** — `apollo-fav` brands the toggle "Apollo Heart"; registry maps `heart → wow` (reactions), but fav vocabulary is correct — icon/label rename to fav semantics recommended.
6. **Dead follow UI CSS** — `apollo-social/assets/css/feed.css` `.sidebar-follow-*` block has no PHP/JS references; safe deletion candidate.
7. **friend request / like button** — no live `friend request` hits; no literal `like button` UI; philosophy violations are structural (follow/stats) not literal string matches.

---

## 6. Exclusions applied

| Exclusion | Reason |
| --- | --- |
| `apollo-core/config/**` | Per W5 scope (includes `routes.php` `/following/{user_id}` — not scanned) |
| `apollo-waha/**` | Per W5 scope |
| `apollo-telegram/vendor/**` | Third-party |
| `*.bak`, `phpcs-logs/**`, `_sandbox/**` (except noted) | Non-production artefacts |
| `apollo-notif/templates/notifications.php.bak` | Backup only |

---

## 7. Worker verdict

**FORBIDDEN/NAMING debt is real and concentrated**, not scattered typos. The party-model auto-connect table (`apollo_follows`) in core/social is **CO**, but product surfaces built on top (`statistics`, `notif`, `admin`, `pane-engine` routes) still model selective follow and follower counts — **TV** against `$philosophy`.

No product PHP was modified in this pass.
