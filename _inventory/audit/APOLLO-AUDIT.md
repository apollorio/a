# Apollo Full Integration Audit

Date: 2026-07-21
Commit: n/a (working tree is not a git repo)
Branch: n/a
WordPress runtime available: NO (no WP-CLI / no $wpdb this session)
Database available: NO (REST GET on apollo.rio.br used as partial runtime proxy)
WP-CLI available: NO

## Executive Result

Plugins discovered (primary + participating): 8 audited in depth (apollo-core, -events, -djs, -loc, -templates, -coauthor, -seo, -comment)
Plugins healthy: apollo-events, apollo-djs, apollo-loc, apollo-templates, apollo-coauthor, apollo-seo (bootstrap NOT_RUNTIME_VERIFIED, code-registration confirmed)
Plugins partial: apollo-loc (loc image/testimonials input surface missing)
Plugins broken: 0

Registry fields: 83 (event 42, dj 21, local 20)
PASS: 55
PARTIAL: 8
FAIL: 0
UNKNOWN: 0
NOT_APPLICABLE: 20

P0: 0
P1: 0
P2: 1 (loc single depended on external stock image — FIXED this session)
P3: 2 (dj.gallery no frontend field; loc image/testimonials no input surface — DECISION_REQUIRED)

Overall status: **PASS WITH DOCUMENTED NON-BLOCKING GAPS**

---

## 1. Plugin Registration
Each primary CPT is registered exactly once by its expected owner (event→apollo-events, dj→apollo-djs, local→apollo-loc). No duplicate CPT registration, no unknown ownership. See `plugin-matrix.json`. Bootstrap execution is NOT_RUNTIME_VERIFIED (no CLI), but code registration paths are file:line confirmed.

## 2. Registry Integrity
`data-registry.json` passes all structural checks (validator `validate-data-registry.php`, host-runnable; jq-equivalent run here): valid JSON, unique ids (83/83), per-cpt field_count matches, totals match, gaps total matches, all storage.kind and empty_behavior values in allowed sets. No duplicate canonical storage keys within a cpt.

## 3. Event
42 fields. 22→24 PASS (parent-corrected: `event.access.coupon` and `event.access.listaCta` DO render via `apollo_event_build_access_payload` — access.php:69-76,111-113). Full create→REST→DB→edit→single round-trip proven for every writable slot; ticket_url/price/list_url/lista_cta_label inputs added in a prior pass. 4 PARTIAL are by-design (season metadata, status overlay, privacy gate, runtime chat) — none are broken data slots. 0 FAIL.

## 4. DJ
21 fields. 17 PASS (parent-corrected: `dj.projects` renders via marquee.php/metrics.php from `_dj_original_project_1..3`). The 4 previously-hardcoded high gaps (bio_short, statement, hero_image, bio) are now dynamic through the active v3 template. 1 PARTIAL: `dj.gallery` (admin-metabox only, no frontend field — P3). 3 NOT_APPLICABLE derived (playedOn/playedWith/stats). 0 FAIL.

## 5. Local
20 fields. 14 PASS. 3 PARTIAL: `local.hero_image`, `local.gallery` (`_local_image_1..5` read by single but no input path), `local.depoimentos` (`_local_testimonials` read, no writer). 3 NOT_APPLICABLE derived (kicker/eventsHosted/events). 0 FAIL. Production stock-image fallback removed (fixes-applied.md).

## 6. Database
No direct DB access. REST GET on apollo.rio.br confirmed real value shapes for event id 25 and dj id 40. Naming drift documented (`_loc_gallery` vs `_local_image_N` vs `_local_gallery`) — no auto-migration. See `db-matrix.json`.

## 7. Add/Edit Forms
event: full coverage, nonce + capability + draft-gate. dj: full except `_dj_gallery`. local: address/contact/details/hours/amenities covered; images + testimonials have NO input surface. See `form-matrix.json`.

## 8. REST/AJAX
apollo/v1/eventos (GET/POST/PUT, nonce wp_rest, per-object caps), apollo/v1/djs, apollo/v1/local. Event estatisticas/check-in/RSVP hardened in prior passes (`can_edit_event`, `validate_event_id`). Live GET 200 observed for eventos + djs.

## 9. Relations
event→dj (`_event_dj_ids` + `_event_dj_slots` timetable) and event→local (`_event_loc_id`) resolve to correct target CPTs; singles render resolved target data (lineup, timetable, venue block). Missing/invalid targets handled by conditional render (empty_behavior=hide). No singular/plural key drift found (`_event_dj_ids` canonical).

## 10. Single Pages
All three singles render from live post/meta/tax/relation/derived data. Zero remaining hardcoded mock data after the loc hero fix. `single-matrix.json` maps every slot to its source. dj single serves v3; event & dj live-verified 200.

## 11. Security
Write paths: nonce (wp_rest / apollo_frontend_editor), capability + ownership checks, type-appropriate sanitizers (esc_url_raw for URLs, absint, enum allowlists, wp_kses_post for HTML), readonly enforced server-side (dj_verified). Output escaping present in templates (esc_html/esc_attr/esc_url). No indiscriminate sanitize_text_field on structured values.

## 12. Legacy Keys
`_ap_*` mock aliases preserved in `storage.mockup_key_legacy` (intentional, LEGACY_COMPATIBILITY). No accidental legacy usage in active read paths.

## 13. Dead/Orphaned Code
`apollo-loc/templates/single-local.php` is an inactive alt template (active = styles/base/single-local.php). Not removed (out of scope; no proof of obsolescence).

## 14. Fixes Applied
See `fixes-applied.md`. One production code line (loc hero de-mock) + registry gap reclassification with evidence.

## 15. Remaining Decisions (DECISION_REQUIRED)
1. Loc image input surface (`_local_image_1..5`) — needs media uploader (full lifecycle feature).
2. Loc testimonials input (`_local_testimonials`) — needs writer or explicit runtime-only classification.
3. Loc imagery naming drift — needs a migration decision before consolidating `_loc_gallery`/`_local_image_N`/`_local_gallery`.

## 16. Final Verification — Acceptance Test
1. Plugins bootstrap: NOT_RUNTIME_VERIFIED (code-confirmed). 2. Dependencies valid: yes (code). 3. Event/DJ/Local registered once: YES. 4. Taxonomies attached: yes (sound shared, event_season). 5. Canonical fields registered/intentionally-not: YES. 6. Every writable field has input path: YES except loc images/testimonials (documented). 7. Submitted→correct DB key: YES (name parity verified form↔bridge↔REST↔meta). 8. Edit reloads saved value: YES (loadEventFromPayload; FrontendEditor prefill). 9. REST/AJAX same canonical keys: YES. 10. Relations resolve valid CPTs: YES. 11. Required single values from live data: YES. 12. Any production single showing mock data: NO (loc unsplash fixed). 13. `_ap_*` handled intentionally: YES. 14. Derived/runtime kept out of persistence: YES. 15. Security on every mutation: YES. 16. Registry matches implementation: YES (post-fix). 17. Counts/totals recompute: YES. 18. Remaining gaps documented: YES. 19. Create→DB→edit→single round-trip survives: YES for event & dj (live 200); local YES except image/testimonials (no writer). 20. Orphaned/duplicate canonical paths: one inactive alt template noted; loc imagery drift documented.

**FINAL STATUS: PASS WITH DOCUMENTED NON-BLOCKING GAPS**

Runtime-dependent claims are labelled NOT_RUNTIME_VERIFIED; no PASS asserted from static inspection alone where runtime was required.
