# PLAN — Track releases: ghost credits, previews, and the /casa rail

> Written 2026-08-17 against live code. The `track` CPT, its 14 meta keys, its
> admin panel and the four contracts already exist — this is what sits on top.
>
> Companions: `PLAN-outnow-and-hostel.md` (why `track` became a CPT),
> `STRATEGY-integration.md` (the four wires), `PLAN-cpt-and-admin-panels.md`.

---

## 0 · What exists, measured

| Piece | State |
|---|---|
| `track` CPT · `/track/{slug}` · `/tracks` | live |
| 14 meta keys in `MetaRegistry` | live |
| Track admin panel (13 fields, declarative) | live |
| `_dj_tracks` v2 legacy meta on `dj` | live, migration pending |
| `apollo_get_latest_dj_tracks()` | live, **unbounded N+1** |
| `/casa` "Out Now!" section | live, **card markup duplicated twice** |
| The locked-guest design | live — **preserved verbatim, see §3** |
| `apollo_card_register()` | live, **0 consumers** |

### Two defects this plan closes on the way

**D1 — the /casa card is written twice.** `new-home/tracks.php` renders ~55
lines of card markup for real tracks, then the *same* ~55 lines again for the
simulated placeholders. Only the data source differs. Any change to the locked
design has to be made in both, and the second copy is where it will be forgotten.

**D2 — `apollo_get_latest_dj_tracks()` is an N+1.** `get_posts(post_type=dj,
-1, fields=ids)` then one `get_post_meta` per published DJ, uncached, on every
`/casa` render. It exists because tracks used to live in meta. They don't now.

---

## 1 · Credit model — real DJ **or** ghost

A release must credit an artist who may not have a `dj` post. The mapping:

| Storage | Holds | Frontend-editable |
|---|---|---|
| `_track_dj_ids` *(exists)* | array of real `dj` post IDs | yes (multi-select) |
| `_track_ghost_artists` **(new)** | comma-separated names with no post | yes (text) |
| `_track_artists` *(exists)* | the **derived** display string | read-mostly |

```php
apollo_track_credits( int $track_id ): array
// [ ['type'=>'dj',    'id'=>42, 'name'=>'Leo Janeiro', 'url'=>'/dj/leo-janeiro'],
//   ['type'=>'ghost', 'id'=>0,  'name'=>'Convidado',   'url'=>''] ]
```

**Why two scalars and not one repeater.** A repeater cannot be edited from the
front end — `FrontendEditor::sanitize_field()` returns `string` and has no array
branch. That limitation is the entire reason `track` became a CPT instead of
staying meta; re-introducing a repeater here would rebuild the wall we just
removed. Two flat fields are frontend-editable today, with no new machinery.

**Why ghosts are not auto-created as `dj` posts.** A ghost is a credit, not a
profile. Auto-creating a stub `dj` for every featured artist would fill the
roster with empty posts nobody claims, and `/djs` is a curated surface. A ghost
becomes real when someone actually registers — and `apollo_track_credits()` will
resolve them by name at that point without touching stored data.

**Ordering.** Credits render DJ-first, then ghosts, each in declared order. The
display string `_track_artists` is regenerated from both on save, so it never
drifts from the structured truth.

---

## 2 · Preview audio — three providers, one contract

```php
apollo_track_preview( int $track_id ): array
// [ 'provider' => 'youtube'|'catbox'|'archive'|'direct'|'',
//   'src'      => '…',        // <audio src> or embed URL
//   'mode'     => 'audio'|'iframe',
//   'start'    => 0,          // seconds
//   'seconds'  => 30 ]
```

| Provider | Detected by host | Mode | Notes |
|---|---|---|---|
| **Catbox** | `catbox.moe`, `files.catbox.moe` | `audio` | Best case — direct MP3 hotlink, no account, 200 MB. Native `<audio>`, real scrubbing, no third-party script |
| **Archive.org** | `archive.org`, `ia*.us.archive.org` | `audio` | Permanent, free, direct stream. Upload needs an account |
| **YouTube** | `youtube.com`, `youtu.be` | `iframe` | Practical fallback. Privacy-friendly host, no related videos, no controls chrome |
| direct | any other `.mp3`/`.ogg`/`.m4a` | `audio` | Escape hatch for self-hosted |

New meta, all flat:

- `_track_preview_url` — the source
- `_track_preview_start` — integer seconds offset (default 0)
- `_track_preview_seconds` — integer preview length (default 30)

**The preview is separate from the release links on purpose.** `_track_url_*`
are the *destinations* — where a listener goes to hear the whole thing on the
artist's own platform. `_track_preview_url` is a short in-page taste. Conflating
them would mean either a full Spotify embed on `/casa` (heavy, account-walled)
or no preview at all where the release lives somewhere unembeddable.

**Guests never get audio.** The preview player only mounts for members. A guest
clicking a locked card gets the auth CTA — which is the existing design, not a
new rule.

---

## 3 · The locked design — preserved, extracted, reusable

> **Strict requirement: the locked-track treatment is not deleted, not
> redesigned, and not re-tuned. It is lifted out verbatim and made reusable.**

What it is, exactly, as it ships today:

```
.nh-track-card.is-guest  +  data-auth-required  +  aria-disabled="true"
  .nh-track-artwork
    .nh-track-blur          duplicate cover, blurred, aria-hidden
    .nh-track-image         empty alt + aria-hidden for guests
    .nh-track-ring          vinyl ring
    .nh-track-ring--outer   second ring
    .nh-track-play-overlay
      .nh-track-play-btn.is-locked   disabled, ri-lock-2-line
  .nh-track-info
    h4.nh-track-locked-lbl          ● ● ●
    .nh-track-artist.nh-track-locked-lbl   ● ● ●
    .nh-track-meta.nh-track-locked-lbl     ri-lock-2-line + ● ● ●
```

**New home:** `apollo-templates/templates/template-parts/track/card.php`

One part, parameterised, rendering both states. Registered as the first consumer
of the card contract:

```php
apollo_card_register( 'track', array(
    'renderer'  => 'apollo_track_render_card',
    'styles'    => 'apollo_track_card_styles',
    'post_type' => 'track',
    'variants'  => array( 'rail', 'grid', 'compact' ),
) );
```

Then `/casa` becomes:

```php
echo apollo_card_render_many( 'track', $ids, array( 'variant' => 'rail' ) );
```

**D1 dies here** — one card, one owner, both the real and the placeholder paths
rendering through it. The DOM a guest sees is byte-identical to today's.

**Where else it gets used** (the reason for extraction): `/tracks` archive,
`/dj/{id}` Out Now section, search results, a member's saved tracks. Each is
`apollo_card_render()` with a different `variant` — never a copy.

---

## 4 · The /casa rail — 15 cards, scroll-x, click to preview

Today: `.nh-tracks-grid`, 5 real or 8 simulated, static grid.
Target: **15 newest, horizontal scroll, click plays a short preview.**

- Markup stays `.nh-track-card` — the card part is unchanged. The **container**
  becomes `.nh-tracks-rail` with `scroll-snap-type: x mandatory` and
  `overflow-x: auto`.
- **CSS belongs to the card's style callback**, emitted once by the contract's
  print-once ledger — not to a `/casa` stylesheet. That is what stopped
  `.accom-*` shipping unstyled on `/anuncios`.
- Lenis is active on `/casa`. The rail needs `data-lenis-prevent`, the same
  opt-out the event lightbox uses, or horizontal wheel gestures get swallowed.
- Reduced motion: `scroll-behavior: auto` under
  `prefers-reduced-motion: reduce`.

**The player.** One instance for the whole rail, not one per card — 15 `<audio>`
elements would preload 15 files. Click a card → the single player re-points at
that track's source, seeks to `start`, plays, and auto-stops at
`start + seconds`. Clicking another card swaps the source. Clicking the playing
card stops it.

---

## 5 · Phases

### Phase T1 — storage *(no UI)*
- [ ] **T1-1** `_track_ghost_artists`, `_track_preview_url`,
      `_track_preview_start`, `_track_preview_seconds` in `MetaRegistry`
- [ ] **T1-2** Add the four to the Track admin panel
- [ ] **T1-3** `20-meta-schemas.json` — record the preview provider contract

### Phase T2 — derivation *(pure functions, no rendering)*
- [ ] **T2-1** `apollo_track_credits()` — DJ posts + ghosts, one ordered list
- [ ] **T2-2** `apollo_track_preview()` — provider detection, fail-closed to `''`
- [ ] **T2-3** `apollo_track_query()` — the single WP_Query. **Kills D2**
- [ ] **T2-4** `apollo_get_latest_dj_tracks()` becomes a thin wrapper, keeping
      its name so `/casa` and every other consumer stay untouched

### Phase T3 — the card *(design preserved)*
- [ ] **T3-1** Extract `template-parts/track/card.php`, both states, verbatim DOM
- [ ] **T3-2** `apollo_track_card_styles()` — lift the existing `.nh-track-*`
      rules into the card's own style callback
- [ ] **T3-3** `apollo_card_register( 'track', … )` — first consumer of the contract
- [ ] **T3-4** Repoint `/casa` at `apollo_card_render_many()`. **Kills D1**
- [ ] **T3-5** Harness: guest DOM before == guest DOM after

### Phase T4 — the rail + player
- [ ] **T4-1** `.nh-tracks-rail`, scroll-snap, `data-lenis-prevent`, 15 cards
- [ ] **T4-2** One player per rail; click to preview, auto-stop, swap on next click
- [ ] **T4-3** Members only — guests keep the existing locked CTA
- [ ] **T4-4** `Ver Todos →` repointed from the dead `/lancamentos` to `/tracks`

### Phase T5 — DJ self-service
- [ ] **T5-1** "Adicionar lançamento" on `/dj/{id}`, own profile only
- [ ] **T5-2** Create path: `post_author = current_user_id()`,
      `_track_dj_ids = [own dj id]`, status `pending` unless `publish_posts` —
      the apollo-adverts shape, already proven
- [ ] **T5-3** Ghost credits via the plain text field
- [ ] **T5-4** Edit/delete guarded by `post_author` or `_dj_user_id` match

### Phase T6 — migration
- [ ] **T6-1** `_dj_tracks` → `track` posts, dry-run first, `_track_migrated_from` set
- [ ] **T6-2** `apollo_dj_get_tracks()` merges both sources for one release
- [ ] **T6-3** Retire the `_dj_tracks` repeater from `DjPanel` once verified

---

## 6 · Sequencing

| # | Phase | Risk | Why |
|---|---|---|---|
| 1 | T1 storage | low | additive, nothing reads it |
| 2 | T2 derivation | low | pure functions; T2-3 removes an N+1 from `/casa` |
| 3 | T3 card | **medium — visual** | the design must not move. Harness-gated on DOM parity |
| 4 | T4 rail + player | medium | new interaction on the busiest page |
| 5 | T5 authoring | medium | new write path |
| 6 | T6 migration | medium | dry-run, reversible |

**T3 is the one to be careful with.** Everything else is additive; T3 touches a
page everyone sees. The mitigation is the parity assertion — capture the guest
DOM before, assert it byte-identical after.

---

## 7 · Decisions

1. **Ghost artists** — plain names in `_track_ghost_artists` (recommended), or
   auto-created stub `dj` posts? The plan takes the first; the second fills
   `/djs` with unclaimed profiles.
2. **Preview length** — 30 s default. Fixed, or per-track?
3. **Rail count** — 15 on `/casa` as specified. Same on `/dj/{id}`, or fewer?
4. **Who may add a release** — any logged-in user with a linked `dj` post, or
   any member at all? The plan takes the first.
