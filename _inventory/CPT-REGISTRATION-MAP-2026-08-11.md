# Where CPTs, meta and taxonomies are actually created

**Date:** 2026-08-11 · **Scope:** `dj` + `local`, ahead of converting both single-page
mockups to PHP and building the frontend "add new" forms.

---

## 1 · The registry's central claim is wrong

`registry/02-header.json` states:

> **CRITICAL_apollo_core_centralization:** *"MANDATORY: ALL meta keys, CPTs, and
> taxonomies MUST be registered EXCLUSIVELY through apollo-core. NO direct
> `register_post_meta`/`register_post_type`/`register_taxonomy` calls anywhere else."*

That is not what the code does, and `05-global-architecture.json` — same registry —
describes the real design two chapters later: *"10 CPTs with FALLBACK system — if
owner plugin not active, core registers."*

**The real contract:**

| Thing | Registered by | apollo-core's role |
| --- | --- | --- |
| CPT `dj` | `apollo-djs/src/Registry.php:55` | **fallback only** — `CPTRegistry::register_fallback_cpts()` runs on `init` P5 and skips anything `post_type_exists()` already returns true for |
| CPT `local` | `apollo-loc/src/CPT/CPTRegistrar.php:29` | fallback only |
| taxonomy `sound` | `apollo-djs/src/Registry.php:87` | fallback via `TaxonomyRegistry` |
| taxonomy `local_type`, `local_area` | `apollo-loc/src/CPT/TaxonomyRegistrar.php` | fallback |
| **post meta** | **`apollo-core/src/Core/MetaRegistry.php::load_definitions()`** | **owner — this one really is exclusive** |

So: **CPTs and taxonomies are owned by their plugins; meta is owned by core.** The
02-header sentence conflates the two. Left as-is and flagged — rewriting it is a
registry decision, not a fix.

### Two live consequences

**`apollo-core/config/meta.php` is dead.** 24 definitions for `dj` and `local`,
never referenced by `MetaRegistry`. It looks authoritative and is not. Anyone
adding a key there will watch it silently do nothing.

**`apollo-loc` registers 23 keys directly** in `src/CPT/MetaRegistrar.php`, and 21
of them are *also* defined in `MetaRegistry`. Double registration: whichever hook
runs later wins the `sanitize_callback`, and nothing tells you which. This is the
one genuine violation of the 02-header rule and it is worth closing.

---

## 2 · What was missing — and is now created

Traced every text node and every media `src` in both mockups against
`MetaRegistry::load_definitions()`.

### `dj` — 27 keys → **38**

**Orphans closed (5).** Read by templates, written by the apollo-lux-panels
DjPanel, registered by nobody. Unregistered meta still stores and reads through
`get/update_post_meta()`, so nothing looked broken — but it is invisible to REST,
runs no `sanitize_callback` and no `auth_callback`. `_dj_statement` puts user
prose straight onto a public page, which is precisely the case registration
exists for.

`_dj_statement` · `_dj_booking` · `_dj_about_photo` · `_dj_about_video` · `_dj_gallery`

**New, from the mockup audit (6).**

| Key | Type | Why it cannot be derived |
| --- | --- | --- |
| `_dj_home_city` | string | The `dj` CPT had **no city field at all**. `_loc_city` in the deep scan is an event/loc key that leaked in. Base city is not derivable from gig history — a Rio artist who mostly plays SP would render as an SP artist. |
| `_dj_booking_status` | enum `open\|selective\|closed` | `_dj_booking` is the **contact**. This is the **state**, rendered as the live dot. |
| `_dj_media_kit_stats` | `{value,label}[]` max 4 | A Drive folder cannot be introspected. Size, photo count and rider version are claims, not facts. |
| `_dj_eyebrow` | string, nullable | Override. Falls back to `_dj_home_city` + first two `sound` terms. |
| `_dj_name_lines` | `string[]` max 2, nullable | Override. Falls back to `explode(' ', _dj_name)`, which breaks on one- and three-word names. |
| `_dj_footer_image` | url, nullable | Override. Falls back to the newest cover from this DJ's played events — so every artist gets a real photo instead of the Unsplash placeholder currently in the mockup. |

### `local` — 24 keys → **27**

`_local_tagline` (the editorial half of the hero kicker) · `_local_founded_year`
(store the year, derive the "23+"— otherwise it is wrong every 1 January) ·
`_local_rooms` (count **and** names are printed; **not** `_local_capacity`, which
the mockup's own comment forbids rendering).

### Sanitizers created

- `apollo_core_kses_inline_em()` — `apollo-core/includes/apollo-sanitizers.php`.
  Allows exactly one `<em>` in `_dj_statement`. The alternative, a second key
  holding *which word is gold*, desynchronises the instant the sentence is edited.
- `apollo_dj_sanitize_kit_stats()` — `apollo-djs/includes/functions.php`. One
  function for **both** write paths, REST and metabox — the lesson from
  `_dj_tracks`, which ended up with two competing editors on one screen
  (`20-meta-schemas.$duplicate_metabox_found`).

---

## 3 · Metabox audit — is it "ahead of SEO"?

**Yes, by force.** `apollo-lux-panels` sets `#apollo_lux_panel{order:-999}` so it
sits above every other metabox including `apollo-seo/src/Metabox.php`, whose own
source comments discuss registration order. Nothing needs doing here.

**And it is genuinely capable.** `Panel.php` implements 20 field types:

```
text · textarea · wysiwyg · url · email · number · date · time · color · toggle
select · taxonomy · media_single · media_gallery · repeater · repeater_card
map · hours · lineup · user_multiselect
```

`map`, `hours`, `lineup` and `repeater_card` are the ones that matter — they are
why this can be a premium input surface rather than a wall of text fields.

### The gaps

| | `dj` | `local` |
| --- | --- | --- |
| registered | 38 | 27 |
| has a metabox field | 32 | 18 |
| **registered, no input** | **6** | **11** |
| **input, not registered** | 0 ✔ | **2** |

**`dj` — the 6 with no input are exactly the 6 new keys.** Expected; they need
DjPanel fields.

**`local` is the problem.** Eleven registered keys have no input in LocPanel,
including **all five gallery images**. Those are covered — but by a *second,
separate* metabox system, `apollo-loc/src/Admin/Metabox/GalleryMetabox.php`. So
`local` has two metabox providers on one edit screen. That is the exact shape of
the `_dj_tracks` duplicate-metabox incident, caught before it caused damage.

**And two keys are written with no registration:** `_local_amenities` and
`_local_hours`. LocPanel saves them; `MetaRegistry` does not know them. They feed
the Estrutura section and the hero hours line.

---

## 4 · Order of work, before any template is written

1. **Close the `local` double-registration.** Convert `apollo-loc/src/CPT/MetaRegistrar.php`
   to contribute through the `apollo_core_register_post_meta` filter —
   `MetaRegistry::apply_external_definitions()` exists for exactly this and is the
   legal seam. No `register_post_meta` call leaves the plugin.
2. **Register `_local_amenities` + `_local_hours`** in `MetaRegistry`. They are
   already being written.
3. **Decide `local`'s metabox owner** — LocPanel or `GalleryMetabox`, not both.
4. **Add the 6 new DjPanel fields** — `text`, `select`, `repeater_card`, `text`,
   `repeater`, `media_single` respectively. All four types already exist.
5. **Add the 3 new LocPanel fields** — `text`, `number`, `repeater`.
6. **Then, and only then,** convert the mockups: every value the template reads
   now resolves to a registered key with a sanitizer and a REST field.
7. **Bump `apollo-core`.** `19-ssot-audit` already records it sitting at 6.2.3
   with uncommitted contract changes; this pass adds more.

Frontend "add new" forms should read the same `MetaRegistry` definitions rather
than re-declaring fields — one schema, three surfaces (REST, metabox, frontend
form). That is the only version of this that stays consistent.
