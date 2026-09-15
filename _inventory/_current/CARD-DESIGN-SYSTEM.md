# The Apollo advert card — one structure, four screens

Written 2026-08-26. Lives at `_inventory/_current/CARD-DESIGN-SYSTEM.md`.

This is the reference for the ticket and accommodation cards. It exists because the same
card is rendered on four surfaces and was being styled by two different stylesheets that had
already drifted apart.

---

## 1 · The four surfaces, and the one contract

| Screen | Route | Template | `$mk_single_mode` |
|---|---|---|---|
| Marketplace | `/anuncios` · `/marketplace` · `/classificados` | `archive-classified.php` → `mk/layout.php` | not set |
| Single advert | `/anuncio/{slug}` | `single-classified.php` → `parts/single/layout.php:43` | **true** |
| Ticket carousel | inside `/anuncios` | `marketplace/parts/ticket-carousel.php:49` | not set |
| Stay grid | inside `/anuncios` | `marketplace/parts/accommodation-grid.php:50` | not set |

All four include the *same two partials*: `parts/card-ticket.php` and
`parts/card-accommodation.php`. That was a deliberate decision, recorded in `mk/layout.php:8-11`:

> *Ticket and accommodation cards are the EXISTING hardened parts — the ones carrying the
> seller privacy and hostel lock rules. This screen reuses them rather than shipping a second
> card implementation that could drift from those guarantees.*

**Therefore: any change to a card is a change to all four screens.** `/anuncio/{slug}` in
particular is easy to forget, because `$mk_single_mode` suppresses the permalink wrapper, the
tabindex and the CTA — so a card looks different there while being the same file.

### The frozen contract

These are read by JS, AJAX or CSS in more than one file. **Nothing here may be renamed.**

```
article[type=ticket]          .carousel-item        .reveal-up
data-mk-domains               data-classified-id    data-mk-permalink
.top  .bottom  .rip  .rip-line  .barcode
.ticket-user-info  .ticket-user-info--locked  .ticket-avatar  .ticket-avatar--locked
.ticket-bandname  .ticket-tourname  .ticket-locked-note
.ticket-deetz  .ticket-meta-row  .ticket-event-title  .ticket-date  .ticket-location
.ticket-qty  .ticket-price-tag  .ticket-img  .ticket-header-block
.btn-chat-ticket  .is-locked
.accom-card  .accom-img-wrap  .accom-badge  .accom-img  .accom-content
.accom-header  .accom-title  .accom-rating  .accom-loc
.accom-footer  .accom-price  .accom-footer-actions
.btn-accom  .btn-accom--hostel  .ap-advert-share  .accom-share
```

`article[type=ticket]` is invalid HTML — `type` is not an attribute of `<article>`. It is also
**load-bearing**: four stylesheets, two scripts and the design mockup all select on it. Correct
it everywhere in one commit or not at all.

---

## 2 · The stylesheet split — the actual root cause

apollo-adverts ships **two complete stylesheets** for these cards.

| | `assets/css/marketplace/*` | `templates/marketplace/parts/mk/styles.php` |
|---|---|---|
| Registered | `src/Plugin.php:341`, handle `apollo-adverts-marketplace` | injected via `extra_head` |
| Enqueued on | `create-classified.php:23` **only** | every marketplace screen |
| Contains | the fan carousel — `--active` / `--x` / `--rot`, driven per card by `carousel-state.js` | a later independent rewrite, "PHASE 003 · luxury ticket pass" |

They duplicate roughly ten selectors with different values, and the rewrite **mistyped one**:
markup emits `.ticket-tourname`, the sheet styled `.ticket-username`. The correctly spelled
rule exists in `_ticket-content.css:52` — in the sheet this screen never loads. So the seller
handle rendered with no styling at all, on every card, for as long as that split has existed.

That is the cardinal sin in miniature: one concept, two declarations, and the copy drifted.

**The fan carousel your reference asks for is already written.** Getting it onto `/anuncios`
is an enqueue plus a filter change — the current `display:none` filter in `mk/layout.php:59`
would desync the engine's index maths, and the mockup already solves that with
`refreshItems(visibleCards)` (`app-market.js:501`).

---

## 3 · The design system

Tokens come from `cdn.apollo.rio.br/v1.0.0/core.js`. **Never declare a colour here.**

### Spacing — one ramp, base 4

`xs 4 · sm 8 · md 12 · lg 16 · 2xl 24 · 3xl 32 · 4xl 48`

- `xs` icon-to-label, meta lines
- `sm` chip padding, button-group gaps
- `md` between fields inside one block
- `lg` card edge padding, all four sides, uniform
- `2xl` card-to-card gutter, section-header bottom
- `3xl` above a new section label
- `4xl` empty-state padding

Off-ramp values still in the sheet: `9px` (`.ticket-header-block`), `14px` (`.top`,
`.accom-content`), `3px` (`.ticket-meta-row`), `10px` (`.accom-header`, `.accom-footer`),
`6px`/`18px`/`26px` (`.mk-head`, `.mk-sec-lbl`). Card gutters disagree: `18px` tickets,
`16px` stays — no reason for two.

### Type — three weights, no more

`400` everything quiet · `600` structural markers · `700` the few things a glance must resolve.

Delete `500` (imperceptible against 400 at 10–11px) and `800` (falsely outranks the price,
which is its equal).

| Role | Size | Weight | Tracking | Colour |
|---|---|---|---|---|
| eyebrow / intent | 10 | 600 | `.08em` | `--muted` |
| event title | 17 | 700 | `-.02em` | `--txt-heading` |
| date · location · qty | 11 | 400 | `.04em` | `--muted` |
| price | 20 | 700 | `-.02em` | `--txt-heading` |
| seller name | 14 | 700 | 0 | `--txt-heading` |
| seller handle | 12 | 400 | `.04em` | `--muted` |
| locked note | 12 | 400 | 0 | `--muted` |
| stay title | 16 | 700 | `-.02em` | `--txt-heading` |
| rating | 12 | 600 | 0 | `--txt-color` |
| price / night | 15 | 700 | `-.02em` | `--txt-heading` (unit 11 / 400 / `--muted`) |
| stay badge | 9 | 600 | `.08em` | `--txt-heading` |

Four tracking values only: `-.02em` · `0` · `.04em` · `.08em`.

### Surfaces — each must earn its place

Ticket: **4** (shell, chat button, share button, avatar). Stay: **5** (shell, photo, badge,
CTA, share).

Merge or remove: `.ticket-header-block` (a full-bleed chip doing the work of a text row);
`.rip` + its two circle pseudo-elements (three surfaces standing in for one hairline);
`.barcode` (zero information); `.accom-rating`'s chip (at 12px, size and colour already say it).

Keep `.accom-badge` as a chip — it sits over a photograph, where plain text is illegible.

### Motion — 150–220ms, no exceptions

Enter and hover `180ms` on `--mk-ease`. Press `150ms` on `--mk-ease-snappy`.
Animate only `opacity`, `transform`, `color`, `border-color`, `box-shadow`.
**Never** `filter`, never a layout property, never anything on `:focus`.

### Responsive — hierarchy, not column counts

- **Desktop ≥1024** content at full contrast; chat and share present but at `opacity:.45`,
  rising to 1 on card hover. That is what "discreet utility toolbar" means here.
- **Tablet 640–1023** hover is unreliable, so the toolbar sits at full opacity permanently;
  icons and gaps compress instead.
- **Mobile <640** hierarchy changes: seller demotes to avatar + first name, date and location
  merge onto one line, chat stays the single full-contrast 44px action, share drops its chrome.
  Still complete: what the card *is* and what to *do next* never degrade.

---

## 4 · Shipped 2026-08-26

| Change | Evidence |
|---|---|
| **Screen gutter.** Rendered the real partials through PHP 8.4 and measured in a browser: the first card's left edge was **x=0 at 390, 768 and 1280**, with 2px of horizontal overflow on a phone. The shell is full-bleed by contract, so the screen owns its gutter and never claimed it. Cards ran into the glass, clipping the ticket's own perforated edge. | after: overflow 0, left 16, every width. Column counts held by lowering the auto-fill floor 20px, so no breakpoint was silently re-laid-out. |
| **`.ticket-tourname` styled** for the first time on this screen. | see §2 |
| **`.btn-accom` 29px → 44px**, `.ap-advert-share` 36px → 40px. Three sizes for three buttons on one card was not a system; share is deliberately one step down, which is a decision rather than the accident 36 was. | measured |
| **Focus states.** The sheet contained **zero** `:focus` or `:focus-visible` rules across 442 lines, while every card carries `tabindex="0"`. Keyboard users had the browser default ring, on a dark shell, over a photograph. | verified: `outline: rgb(255,152,32) solid 2px`, `:focus-visible` matching |
| **Motion** — all seven live card transitions were `.3s`–`.7s`; now `180ms`/`150ms`. | 7/7 |
| **Resting glass flattened.** `backdrop-filter: blur(20px) saturate(180%)` plus a three-layer inset bevel, on a card sitting over a flat ground. Elevation now appears on hover only. The two blurs inside `#mkTicketStage` are kept — a modal over live content is the one case where sampling what is behind you carries information. | brief: "excessive glass", "excessive blur" |
| **Touch no longer sticks in `:hover`.** Zero `hover`/`pointer` media features existed, so every hover rule fired on tap with nothing to release it. | `@media (hover:none)` |
| **Event snapshot invalidation.** `apollo_adverts_link_event()` promises in its own docblock to refresh "if the event later moves or is renamed" — and ran only when the **advert** saved. A renamed or relocated event left every resale advert stale, silently, forever. Now hooked to `save_post_event`, bounded and filterable. | `includes/cpt.php` |

A declaration that was written and then removed rather than shipped: a companion box-shadow
hairline on the focus ring. Measured in a browser, it never applied — something earlier
out-specifies it — and shipping CSS that silently does nothing is the habit this file is being
cleaned of.

---

## 5 · Held back, with the reason

**Dereferencing the linked event at render time — NOT YET.** `_classified_event_id` is a real
relation and `apollo_adverts_link_event()` snapshots title, date and venue onto the advert.
Reading the event live instead needs a read-time guard that does not exist anywhere:
`link_event()` validates `post_status` at *write* time only. A resale advert whose event was
deleted, unpublished or switched to a draft must still show something truthful, and today
nothing decides what. There is also an undecided product question underneath it: if a seller
typed a title that differs from the event's, **whose wins?** That is a decision, not an
implementation detail.

**The pre-contact popup — NOT YET.** The server-side gate is sound and portable
(`includes/safety-gate.php`), but three things must be true first:

1. `apollo-adverts-safety-gate` script and style are registered always and **enqueued only by
   `templates/safety/gate.php`**. A popup on a card renders on the grid, the carousel and the
   single page — none of which enqueue it. Without that, `ApolloSafety` is undefined and every
   call to `/apollo/v1/safety/*` fails on a missing nonce, which looks identical to "not cleared".
2. The **trust-voters** signal has no provider. `SafetyController.php:135` reads it through
   `apply_filters('apollo_safety_trust_votes', array(), $seller)` and nothing anywhere adds
   that filter — it always returns empty. A control that can never succeed is worse than absent.
3. The **Instagram** label is wrong. `src/Safety/NativeGraph.php:9-19` deliberately does *not*
   touch Instagram — it computes mutuals from Apollo's own comment graph, having rejected the
   follow table because everyone is auto-connected. The mockup's copy says "Amigos em comum no
   Instagram". Shipping that verbatim tells the user something untrue.

The **verified badge** signal is real (`get_user_meta($seller, '_apollo_verified')`) and can
ship as designed.

Whatever the UI becomes, the authorisation stays server-side. A page navigation has a URL, a
back button and a server check; a popup has none of those unless they are built.

---

## 6 · Next, in order

1. Enqueue the marketplace stylesheet on `/anuncios` and retire the duplicated selectors from
   `mk/styles.php`, so one file owns the card. Load-order first, deletions second — the reverse
   leaves a flash of unstyled card.
2. Port the fan carousel with `refreshItems()` filtering, not `display:none`.
3. Decide the cache-vs-truth question for event data, then add the read-time guard.
4. Enqueue the safety assets wherever a gated card renders, hide the two unbacked signals, and
   only then consider the popup.
