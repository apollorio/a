# Apollo doctrine — the memory

Read this before the first edit of every session. It is short on purpose.
Everything here is enforced by `node _inventory/_current/apollo-guard.mjs`; if the two
ever disagree, the guard is a bug — fix the guard, do not ignore the rule.

---

## The one sentence

> **One concept, one declaration, one owner. Every consumer reads it from there.**

Every defect this ecosystem has produced — four accommodation cards, five "Out
Now" sections, two `_dj_tracks` schemas, three canvas shells, 107 conflicting
design tokens — is the same defect wearing a different hat: *something got
declared twice, and whichever loaded last won silently.*

---

## The bans — product decisions, not style preferences

| Never | Because | Rule |
|---|---|---|
| follow / unfollow / "Seguir" | `PARTY_MODEL` — everyone is auto-connected on registration. There is nothing to follow. | D02 |
| follower count, following count, friend count | `NO_EGO_COUNTERS` — Apollo is against numbers-as-status. | D02 |
| like button, heart icon on a reaction | `WOW_NOT_LIKE` — reactions are WOW (fire, clap). | D02 |
| `human_time_diff()`, `" atrás"`, `" ago"` | `apollo_time_ago()` only. The forbidden form also breaks `.time-ago`/`.when-ago` CSS. | D03 |
| a second `:root { }` | `:root` belongs to the one token sheet. A second one forks the token system and hides the real defect. Component scope (`.pev { --pev-band: … }`) is fine and encouraged. | D04 |
| `register_post_type` / `register_taxonomy` / `register_post_meta` outside `apollo-core` | apollo-core is the MASTER REGISTRY. A plugin declaring its own is a violation *even when it works*. | D01 |
| `render_blank_canvas_plus()` / `apollo_render_blank_canvas_body()` in new work | `apollo_plus_open()` / `apollo_plus_close()` is the only shell. The other two are retained for rollback. | D06 |
| a nonce in a `data-*` attribute | Truth never lives in the DOM. Nonces go in a `wp_json_encode()` JS config block, guarded by `is_user_logged_in()`. | G02 |
| `__return_true` on a POST/PUT/PATCH/DELETE route | Unauthenticated mutation. If it must be public, it must be nonce-checked *and* rate-limited, and the line must say so in a comment. | G08 |
| `console.log`, ungated `error_log` | `$apollo_rule.data_flow.production`: no debug output, ever. | G04 |
| `venue` · `location` · `interesse` · `bookmark` · `like` · `heart` · `review` · `cult` · `/user/` | Deliberate product renames: `loc` · `fav` · `wow` · `depoimento` · `cena` · `/id/`. Portuguese prose is exempt; identifiers are not. | D07 |

## The environment facts that change how you work

- **`plugins/` is mirrored to production by RealTimeSync, ~30 s.** A saved file
  is a deployed file. Never save a half-finished edit "to come back to it".
  Work in a worktree at `../wt-*`, which is *not* mirrored.
- **No PHP binary, no headless browser here.** Verification is structural plus
  the runnable harnesses. There are **five**, not one, and as of 2026-08-20
  **three are red** — check before you assume your change broke them:

  | Harness | State |
  |---|---|
  | `apollo-djs/_sandbox/build-dj-harness.mjs` | 13/13 green — the model to copy |
  | `apollo-email/_sandbox/build-email-harness.mjs` | 26/26 green |
  | `apollo-events/_sandbox/build-portal-harness.mjs` | 51/53 — E24 and E27 closed 2026-08-20; H2/H3 need a PHP binary |
  | `apollo-core/_sandbox/build-cpt-surface-harness.mjs` | red — `local.form` 0%, `local.rest` 0% |
  | `apollo-templates/_sandbox/build-casa-harness.mjs` | red — 31 assertions, duplicate selectors on /casa |

  `node _inventory/_current/apollo-guard.mjs --harness` runs all five and
  reports green/red in one table.
- **The registry lags disk.** It has been wrong in both directions — reporting
  fixed items as open and missing real ones. Spot-check live source before
  trusting a version number or a backlog status.
- **Blast radius:** `apollo-core` ≈ 31 dependents; `apollo-templates`,
  `apollo-admin`, `apollo-pane-engine` ≈ 24–25 each. Check `10-coupling.json`
  before changing a shared function, hook or REST signature in any of them.
- **HIGH security band:** `apollo-login`, `apollo-events`, `apollo-membership`.
  Extra scrutiny on *every* edit there, not only security-labelled ones.

## The five declarations a new element needs

```php
// 1. It exists.            apollo-core/config/cpts.php           — one array entry
// 2. It stores things.     apollo-core/src/Core/MetaRegistry.php — one array entry
// 3. It is editable.       apollo_panel_register( 'thing', … )   — one array
// 4. It opens in place.    apollo_surface_register( 'thing', … ) — one call
// 5. It appears in lists.  apollo_card_register( 'thing', … )    — one call
```

No classes. No edits to any other plugin. If you are about to type a field
name, a selector, a route or a schema for the **second** time — stop. That
second copy is the next defect.

## Version discipline

WordPress reads the **docblock**. Cache-busting reads the **constant**. Bump
both in the same edit, or the site serves stale assets while claiming it
didn't. (`D09` catches the split.)

## Two rules earned the expensive way

**Run the guard from the real root, never a copy.** A parallel copy of the tree made
to work around the slow mount silently produced **25 zero-byte files**, and the scan
of that copy generated two false findings that reached a published report — a
"missing ABSPATH guard" list naming files that have guards, and a legacy-shell count
of 4 when the real number is 9. `node _inventory/_current/apollo-guard.mjs` from
`plugins/` takes ~40 seconds. Take the forty seconds.

**A spec older than the code it describes is a hazard, not a head start.**
`_sandbox/SPEC-rail-masthead.md` (2026-08-06) instructs you to swap `.pev-masthead`,
delete `styles-masthead.php`, and preserve `#pevMonthMain` / `#pevFilterMenu`. All
four were removed from source in 1.7.0/1.7.1. Following it would have re-introduced
ids the harness does not protect, into a screen whose header now lives in a different
plugin. Before executing any spec, re-verify its anchors against source — and if it
self-dates, weigh that date against the file's `mtime`.

## Before you say it is done

```bash
node _inventory/_current/apollo-guard.mjs <plugin>   # doctrine + the 12 checks
node _inventory/_current/apollo-guard.mjs --harness  # all five sandbox harnesses
```

Green is the gate, not a formality. The portal harness already caught a
three-way owner conflict on `.pev-recent` that four pairs of eyes had missed —
and assertion **E27** exists because a debug beacon was removed once and came
back. It came back a third time in `apollo-djs/src/Admin/Metabox.php` and was
removed again on 2026-08-20 — that copy also POSTed to `apollo/v1/_agent_debug`
carrying the editor's `X-WP-Nonce`. Assume it will return; that is what the
assertion is for.
