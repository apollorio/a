# Rail Flow masthead — cell breakdown for `/eventos` + `/portal`

Spec, not yet implemented. Written 2026-08-06 at the end of a long session so the
next pass can execute without re-deriving anything.

Replaces `<header class="pev-masthead" id="pevMasthead">` (built by `app.php`'s
`skeleton()`, styled by `styles-masthead.php`).

---

## 0 · The one design change vs the mockup

The mockup rail is a **fixed Jan–Dec of one year**, index 0-11, wrapping with
`% 12`. The portal needs a **rolling window**: `current − 1`, current,
`+1`, `+2`, `+3` …

That is not a cosmetic change — it breaks every `% 12` assumption in the mockup
JS, because the window crosses year boundaries (Dec 2026 → Jan 2027). Model a
month as an **absolute index** instead:

```js
// monthIndex = year * 12 + month0   (month0 = 0..11)
function mIdx(y, m0) { return y * 12 + m0; }
function mYear(i)    { return Math.floor(i / 12); }
function mMonth0(i)  { return ((i % 12) + 12) % 12; }   // safe for negatives
```

Window built from `now`, never from a hard-coded year:

```js
var BASE   = mIdx(now.getFullYear(), now.getMonth());
var WINDOW = { back: 1, fwd: 11 };          // −1 … +11  → 13 chips
var FIRST  = BASE - WINDOW.back;
var COUNT  = WINDOW.back + WINDOW.fwd + 1;
```

Label = `MONTHS_ABBR[mMonth0(i)]`, plus the year when `mYear(i) !== mYear(BASE)`
so "JAN" in a different year is never ambiguous.

`ACTIVE` starts at `BASE`, **not** at a literal month.

---

## 1 · Cells

Follows the existing portal convention: one file, one concern, one owner.
Loader order in `portal/styles.php` — **`masthead` stays last** (declared
last-word cell for masthead geometry).

| New file | Owns | Notes |
| --- | --- | --- |
| `portal/styles-rail.php` | `.hd-hero`, `.hd-top`, `.hd-mark`, `.hd-icbtn`, `.hd-h1`, `.hd-count`, `.hd-filter-trigger`, `.h1-rail*`, `.h1-m`, `.h1-filter-panel*` | New selectors only — no overlap with `styles-masthead.php`, so harness D stays green. Add **after** `masthead` in the loader. |
| `portal/styles-filter.php` | `.fx-group*`, `.fx-chips`, `.fx-chip`, `.fx-empty`, `.fx-footer`, `.fx-btn-*` | Shared filter UI. Separate cell because a future sheet/popover variant reuses it verbatim. |
| `portal/rail.php` | `APOLLO_PORTAL_RAIL` — month-window derivation + `setActiveMonth()` | Pure logic, holds no DOM. Mirrors `helpers.php`'s role. |
| `portal/filter.php` | `APOLLO_PORTAL_FILTER` — taxonomy chip build + badge sync + apply/clear | |

`app.php` changes: `skeleton()` emits the new markup; the month/mode handlers
call into `APOLLO_PORTAL_RAIL` instead of owning the logic.

---

## 2 · Hidden scrollbar (asked for explicitly)

```css
.h1-rail {
  overflow-x: auto;
  scrollbar-width: none;            /* Firefox */
  -ms-overflow-style: none;         /* legacy Edge */
  overscroll-behavior-x: contain;   /* don't chain to the page */
  scroll-snap-type: x proximity;
}
.h1-rail::-webkit-scrollbar { width: 0; height: 0; display: none; }
.h1-m { scroll-snap-align: center; }
```

`overscroll-behavior-x: contain` matters here: without it a flick at either end
of the rail chains to the document and fights Lenis.

**Do not** add `data-lenis-prevent` to the rail — it is a horizontal scroller and
Lenis only drives vertical; the attribute would also block the vertical page
scroll that starts on top of the rail.

---

## 3 · Contract ids the harness asserts — KEEP THESE

`build-portal-harness.mjs` assertion C resolves every `#id` that `app.php`
queries against the markup `skeleton()` builds. Preserve or the gate fails:

- `#pevMonthMain` — keep on the `<h1>` (it is the month label)
- `#pevFilterMenu` — keep on the filter panel
- `#pevMonthEyebrow` — keep, or drop it from the JS query in the same commit
- `data-pev-shift` / `data-pev-mode` — keep as the behavioural contract so
  `app.php`'s existing month-shift and period-mode handlers are **reused**, not
  reimplemented

Assertion D also records "5 selectors intentionally overridden by masthead" —
re-check that list after the swap.

---

## 4 · Open decision (blocks step 4 of the build)

The mockup's filter is **taxonomy chips** (`event_category`, `event_type`,
tags). The current header's filter is **period modes** (Passados / Neste FDS /
Mês / Próximos eventos / Próximos). These are different filters.

Both must survive somewhere: period drives `state.mode` which drives the whole
rail/feed render, and taxonomy is what the user asked to expose. Cheapest
coherent answer: **rail = period + month**, **panel = taxonomy**. Confirm before
building, because it decides whether `data-pev-mode` lives in the panel or the
rail.

`APOLLO_TAXONOMY` must come from **real terms**, not the mockup's hardcoded ids.
Source: derive from `window.APOLLO_EVENTS[].genres` / `.tags` (already emitted by
`archive-event.php`), or localise a term list alongside it. Do not ship the
mockup's literal term ids — they are demo data.

---

## 5 · Order + gate

1. `styles-rail.php` + `styles-filter.php` (additive; harness stays green)
2. `rail.php` window logic + unit-check the year-boundary case (Dec → Jan)
3. `skeleton()` markup swap, keeping the ids in §3
4. Wire filter to real taxonomy (after §4 is decided)
5. Delete the superseded `.pev-masthead` rules from `styles-masthead.php` **in
   the same commit** as the markup swap — never leave both live, that is the
   two-owners failure this codebase keeps hitting
6. Extend the harness for the new cells, then `node _sandbox/build-portal-harness.mjs`

Step 5 is the one that bites. The masthead currently has a single owner; a
partial migration gives it two.
