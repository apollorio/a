# Apollo integration strategy — the four wires

> How every element in the ecosystem talks to every other, what "adding a thing"
> costs, and one part of the brief I'm pushing back on.
>
> Written 2026-08-17, after an audit that read every `register_post_type()`,
> every `add_meta_box()` and every metabox field in 40 plugin folders.

---

## 1 · The problem, stated once

Apollo has 46 plugins, 17 CPTs and ~492 meta keys. Every integration failure
found this month has the **same shape**:

| Symptom | Underlying cause |
|---|---|
| 4 accommodation cards, different DOM | no single owner for "a card" |
| 5 "Out Now" sections, 3 data shapes | no single owner for "a track list" |
| `event` panel decorative, legacy box wins | 2 owners for one edit screen |
| `.accom-*` CSS not loaded on `/anuncios` | the consumer had to remember to enqueue |
| `_dj_tracks` v1 read / v2 write | 2 declarations of one schema |
| admin fields ≠ frontend fields | 2 declarations of one schema |
| `sound` never attached to `track` | 2 places could declare it, only one is read |
| PII leaked in REST but not templates | the rule lived in the view, not the data |

**Not one of these is a coding error.** Each is a *missing single owner*. The
strategy is therefore not "write better code" — it is **give every concept
exactly one place to be declared, and make every consumer read it from there.**

---

## 2 · The four wires

Three exist and are live. The fourth is the last one needed.

```
                    ┌─────────────────────────────────┐
                    │      apollo-core/config         │
                    │   cpts.php · taxonomies.php     │  ← WHAT EXISTS
                    │   src/Core/MetaRegistry.php     │
                    └────────────────┬────────────────┘
                                     │
        ┌──────────────┬─────────────┼─────────────┬──────────────┐
        ▼              ▼             ▼             ▼              ▼
  apollo_surface  apollo_card   apollo_panel   (Phase 4)      REST
   _register()    _register()   _register()   schema→REST   apollo/v1
        │              │             │
   single page      the card     the admin
   + REST frag      in a list    edit screen
   + lightbox       + its CSS    + its saving
```

| Wire | Lives in | Answers | Status |
|---|---|---|---|
| **Surface** | `apollo-core/includes/surface-contract.php` | "open this item in place" | live |
| **Card** | `apollo-core/includes/card-contract.php` | "show this item in a list" | live, 0 consumers |
| **Panel** | `apollo-lux-panels/includes/registry.php` | "edit this item in wp-admin" | **live, 2 consumers** |
| **Meta** | `apollo-core/src/Core/MetaRegistry.php` | "what this item stores" | live, 17 CPTs |

All four share the same four properties, deliberately:

1. **Dormant-safe** — declare before the thing exists; it lights up when the
   callable/CPT appears. No fatals, no half-built output.
2. **Never clobbers** — a second registration is refused unless `$replace` is
   explicit. This is what stops the `event` two-owner situation recurring.
3. **Registers nothing else** — no contract creates a CPT, taxonomy, meta key
   or table. `apollo-core` stays the only registrar.
4. **Readable** — each is a *registry*, not a filter. You can enumerate it. That
   is what lets a validator sweep all panels at once, a harness assert coverage,
   and Phase 4 derive REST from the panel schema.

### What adding a new element costs, today

```php
// 1. It exists.            apollo-core/config/cpts.php          — one array entry
// 2. It stores things.     apollo-core/…/MetaRegistry.php        — one array entry
// 3. It is editable.       apollo_panel_register( 'thing', … )   — one array
// 4. It opens in place.    apollo_surface_register( 'thing', … ) — one call
// 5. It appears in lists.  apollo_card_register( 'thing', … )    — one call
```

Five declarations, no classes, no edits to any other plugin. `track` and
`hostel` were built exactly this way today — the Track panel is 13 fields of
plain array in `apollo-djs/includes/panel-track.php`, and apollo-lux-panels was
never touched.

---

## 3 · "Easiest to connect DB" — the answer is to stop having two

There is no ORM to add. WordPress's meta API *is* the DB layer, and
`MetaRegistry` already centralises it. The friction is not connection — it's
that **the same field gets declared twice**:

| Declared in | Also declared in | Result |
|---|---|---|
| `MetaRegistry` (`show_in_rest`, sanitize) | `DjPanel` (type, label, opts) | admin & REST can disagree |
| `DjPanel` | `apollo-djs/includes/frontend-fields.php` | admin & frontend *do* disagree — `_dj_verified` is `toggle` in one and `checkbox` in the other; each owns fields the other lacks |

**Phase 4 closes it: the panel schema becomes the single field declaration, and
everything else derives.**

```php
// The panel already knows: key, type, label, opts, sanitize_callback, cap.
// That is a superset of what MetaRegistry and FrontendEditor each need.

apollo_panel_register( 'track', array( 'tabs' => array( … ) ) );
        │
        ├──→  MetaRegistry     type + show_in_rest + sanitize_callback  (derived)
        ├──→  FrontendEditor   field list + types, mapped once centrally (derived)
        ├──→  REST             apollo/v1 schema                          (derived)
        └──→  wp-admin         the panel itself
```

One array. Four consumers. **A field cannot drift from itself.**

That is the whole "easy as hell to integrate" answer, and it is three
already-shipped contracts plus one derivation step — not a rewrite.

---

## 4 · On "self-healing / self-learning code" — my honest position

You asked for code that fixes its own channels and applies basic self-learning.
I want to be straight with you about this, because building it as literally
described would hurt you.

### Why not, *in this environment specifically*

- **This folder deploys on save.** No staging, no build, no rollback. Code that
  rewrites itself here writes straight to production.
- **There is no PHP binary and no test suite.** Nothing can verify a
  self-applied fix before it is live.
- **Three plugins are HIGH security-risk** (`apollo-login`, `apollo-events`,
  `apollo-membership`). Self-modifying behaviour near an auth surface is how you
  get a breach nobody can reconstruct.
- **Today's audit is the argument.** Every defect found — the PII leak, the
  `/_agent_debug` hole, the two fatal screens, the decorative panel — was
  *invisible while the system kept running*. A system that heals itself hides
  exactly this class of problem better, not worse. The `_event_dj_ids` bug
  silently blanked line-ups for months; a "self-healing" layer would have
  papered over it faster.

### What actually delivers the intent

The goal behind "self-healing" is: **it should be hard to break, and obvious
when it is.** That is achievable, and most of it now exists:

| Instead of | Build |
|---|---|
| code that repairs itself | **fail-closed contracts** — `apollo_card_get()` returns `null` and the card renders `''`; a dormant surface degrades to a plain link; a panel for a missing CPT is skipped |
| code that learns the right shape | **schema validation at registration** — `Panel::validate_schema()` catches a malformed field before it reaches a screen; it caught all four real defects in test |
| silent auto-correction | **loud, gated diagnostics** — problems go to `error_log` under `WP_DEBUG`, never to production output (`$apollo_rule.data_flow`) |
| trusting a fix stayed fixed | **mechanical guards** — 28 harness assertions. E27 exists because debug beacons were removed once and came back; E24 because a nested CSS comment shipped for months looking correct |
| a system that hides drift | **a registry that can be read** — enumerate panels, diff declared meta against fields with inputs, assert coverage |

**Self-validating, not self-modifying. Fail-closed, not fail-quiet.**

The one place adaptive behaviour genuinely belongs is *presentation*, where a
wrong guess is cosmetic: `apollo_card_render()` falling back to the first
declared variant on an unknown one, `hydrateScripts()` waking a fragment's own
runtime whatever surface shipped it. Both are already in.

If you want something closer to "learning" that is safe, the honest version is a
**health endpoint**: one admin screen that reads the four registries and reports,
per CPT — meta keys with no input, fields with no meta key, surfaces declared but
dormant, cards with no consumer, panels failing validation. It doesn't fix
anything. It makes every gap in this document visible on a page, permanently, so
the next audit takes ten seconds instead of a day. **That I'd recommend building.**

---

## 5 · Rule for anything new

> **One concept, one declaration, one owner. Every consumer reads it from there.**
>
> If you are about to type a field name, a selector, a route or a schema for the
> *second* time — stop. That second copy is the next defect in this document.

Checklist for a new element:

- [ ] Declared in `config/cpts.php` — not in the owner plugin
- [ ] Meta in `MetaRegistry` — never `register_post_meta()` elsewhere
- [ ] `apollo_panel_register()` for the edit screen — no subclass
- [ ] `apollo_surface_register()` if it has a single page
- [ ] `apollo_card_register()` if it appears in a list — card ships its own CSS
- [ ] No second declaration of any field, anywhere
- [ ] Naming: `loc` not venue/location · `fav` not bookmark · `wow` not like ·
      `depoimento` not comment. No follow, no follower counts, ever
- [ ] A harness assertion for whatever you just made impossible

---

## 6 · What remains, and what it's waiting on

| Phase | Work | Blocked by |
|---|---|---|
| 1-1/1-2 | Config as single source; delete owner registrations | **decision 2** |
| 1-4 | `journal_news`/`journal_nota` into config | **decision 3** |
| 2-6/2-7/2-8 | Migrate `classified`/`hub`/`doc`/`email_aprio`; retire apollo-events' legacy metabox; fold apollo-loc cells into LocPanel | decision 2 |
| 3 | Panel schema → FrontendEditor derivation | 2-6..2-8 first |
| 4 | Panel schema → MetaRegistry + REST derivation | 3 first |
| — | Health endpoint (§4) | nothing — buildable now |

**The five decisions** are in `PLAN-cpt-and-admin-panels.md` §6. The sharpest
remains `apollo_sheet`: its owner declares `show_in_rest: false` with a custom
controller; apollo-core overrides that and publishes it at `/wp-json/wp/v2/sheets`
with the stock controller. **That is either a leak to close or a fact to accept,
and I won't guess which** — closing it could break a consumer I can't see from
here, and leaving it open is a decision someone should make on purpose.
