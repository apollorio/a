# PLAN — apollo-chat structural audit & fix order
**Date:** 2026-09-03 · **Plugin version on disk:** 2.0.12 · **Status:** diagnosis complete, nothing deployed

Scope: `apollo-chat` (11 files, ~11k lines) traced end-to-end against `apollo-core`
token contract and the Blank Canvas Apollo+ rules in `CLAUDE.md`. Every finding
below carries a file:line and was read from live source, not inferred.

**Nothing in this plan has been written to disk yet.** `D:\dev\_apollo.rio.br\plugins`
is a deployed tree — a saved file is a live file — so the fix order at the bottom
is sequenced so the sandbox-verifiable changes land first.

---

## 0. The cascade nobody documented

`src/Plugin.php` contains **zero** `wp_enqueue_style` calls. All CSS is emitted
from the template, in this order:

| # | Cell | Emitted at | Weight |
| - | ---- | ---------- | ------ |
| 1 | `assets/css/chat-premium.css` (2071 ln) | `styles.php:9` | base |
| 2 | `assets/css/chat-shell.css` (986 ln) | `styles.php:10` | override |
| 3 | **`<style id="apollo-chat-critical">`** `chat.php:79–190` | `chat.php:242` | **last word — every rule `!important`** |
| — | `assets/css/chat.css` (643 ln) | never linked | **dead code** |

Two consequences the codebase is not accounting for:

- The **inline block in `chat.php` is the real last-word cell**, not
  `chat-shell.css`. Its header comments claim shell owns composer and pane
  geometry; it does not.
- `chat.css` is confirmed unreachable (`.app-shell`, `.sidebar`, `.compose-bar` —
  none of those class names exist in `chat.php`; `chat.php:47-50` says so). It
  still answers greps for `.compose-bar`, `:root`, `FF9820`, which is how a
  "footer bar" rule keeps looking live during debugging. **Delete it.**

**87 selector+media pairs are declared twice or more. 77 of them are
premium ↔ shell.** That is not incidental drift — the two cells own the entire
component surface simultaneously. This is the cardinal sin at full scale.

---

## 1. BRAIN-01 — the input has a background; it is painted the same colour as the pane

This is the one you reported as "background not showing". The rule is present and
correct. The **pane** underneath it was recoloured.

`chat-shell.css:17` and `:506`
```css
--ac-surface-2: var(--white-3, #f5f5f7);
.ac-compose-input { background: var(--ac-surface-2) !important; border-radius: 20px !important; }
```

`chat-shell.css:293` intended the pane to stay white, and says so in a comment:
```css
/* Messages pane is now pure white (--ac-surface) — the received bubble
   needs the neutral tint instead of white … */
.ac-messages { background: var(--ac-surface) !important; }   /* #ffffff */
```

`chat.php:150–154` — later in `<head>`, same specificity, also `!important`:
```css
.ac-messages {
    padding-left: 14px !important;
    padding-right: 14px !important;
    background: var(--white-3) !important;      /* ← #f5f5f7 */
}
```

**Pane `#f5f5f7`, pill `#f5f5f7`. Contrast 1.00:1.** The pill is invisible, not
unstyled.

The same inversion silently erases five more surfaces that assume
`--ac-surface` (white) underneath `--ac-surface-2` (tint):

| Element | Rule | Result |
| --- | --- | --- |
| `.ac-compose-input` | shell:507 | invisible |
| **`.received .ac-bubble`** | shell:478 | **every incoming bubble invisible** |
| `.ac-date-sep span` | shell:327 | invisible |
| `.ac-btn-new` | shell:136 | invisible (`.ac-sidebar-header` forced tint at `chat.php:158`) |
| `.ac-sidebar-search input` | shell:160 | invisible |

**FIX-01** — delete `background: var(--white-3) !important;` from `chat.php:153`
and `:158`. Keep the padding lines. One deletion restores six components.

### FIX-02 — the "footer" is a geometry ghost, not a background

No live rule paints a full-width composer band. `.ac-compose` is
`background: transparent !important` in both places shell declares it (`:491`,
`:666`). What survives is the height reservation of the old bar:

`chat-premium.css:1166`
```css
.ac-compose-form { display:flex; align-items:flex-end; gap:8px; padding:8px 12px; min-height:56px; }
```
`chat-shell.css:500` overrides `align-items`, `gap`, `padding` — and **forgets
`min-height`**. So a 56px full-column band is reserved around a 40px pill
(`shell:510`), which reads as an edge-to-edge footer even though it is
transparent.

The ancestor proves the intent — dead `chat.css:420`:
```css
.compose-bar { display:flex; padding:8px 10px; background:#fff;
               border-top:1px solid rgba(var(--rgb-d),0.06); min-height:56px; }
```
`chat-premium.css` is a verbatim port of that edge-to-edge footer. **If
`chat-shell.css` ever 404s or arrives stale inside the ~30s RealTimeSync window,
the composer renders as the old bar exactly.** That is worth knowing when a fix
appears not to take.

Fix: move `min-height` ownership to shell and set it to the pill height.
```css
/* chat-shell.css — composer cell owns ALL composer geometry */
.ac-compose-form { display:flex; align-items:flex-end; gap:8px; padding:2px 0 0; min-height:40px; }
```
and delete `min-height:56px`, `display:flex` from `chat-premium.css:1166` with a
pointer comment. Padding around the text is already correct
(`padding:10px 40px 10px 14px !important`, shell:508).

### FIX-03 — the pill and the send button have user-agent borders

`chat-premium.css:5–13` resets `box-sizing`, `margin`, `padding`,
`-webkit-tap-highlight-color` — **not `border`**. `grep "^\s*border:"` across
both live sheets returns zero matches. Dead `chat.css:431` and `:453` both
carried `border:none`; the port dropped it. A `<textarea>` at
`border-radius:20px` with a default 1–2px inset border is exactly "the pill looks
wrong" territory.

```css
.ac-compose-input, .ac-send-btn { border: 0; outline: none; }
```

### FIX-04 — mobile gutter mismatch

At `<768px` the message column is forced to `14px` (`chat.php:151`) while
`.ac-compose` uses `--ac-pad-x: 12px` (shell:598). The composer is 2px wider per
side than the messages. At ≥768 and ≥1100 they agree only because `chat.php:179`
and `:185` hardcode the same numbers shell derives from `--ac-pad-x-msg`.
Fix: delete the padding overrides from `chat.php` too and let shell's tokens own
the gutters, per the "one cell owns the gutters" rule.

---

## 2. BRAIN-02 — the send bug: the message is committed, then the request keeps working

**Confirmed shape: the row is durable long before the response is built.**

`includes/functions.php:198` commits the INSERT. `apollo_send_message()` then
does five more things before it returns at `:300` — attachment link, thread meta
update `:250`, unread increment `:262`, `apollo_create_notification()` fan-out
`:273–297`, `do_action('apollo/chat/message_sent')` `:299`.

Control returns to `Plugin.php:684`, which does more still:

```php
if ($result) {
    apollo_chat_set_typing($thread_id ?: $result, $sender_id, false);
    $preview = wp_trim_words($message, 10, '...');
    apollo_chat_maybe_notify_by_email($result, $sender_id, $preview);   // ← line 689
    // … then, finally:
    return new \WP_REST_Response(array('thread_id' => $result, 'success' => true), 201);
}
```

`apollo_chat_maybe_notify_by_email()` (`functions.php:1951`) runs
**`wp_mail()` synchronously, once per recipient, inside the request the UI is
awaiting** (`:2023`). Any SMTP stall, PHPMailer exception, PHP notice, or
FPM/nginx timeout there is reported to the author as "Erro ao enviar mensagem"
— while the row is already committed and the recipient sees it on refresh.

The client cannot tell the difference. `chat.js:120–131`:
```js
if (!res.ok) { … throw e; }
return res.json();                       // ← also throws if the body is poisoned
```
`chat.js:950`:
```js
} catch (e) { toast('Erro ao enviar mensagem', 'ri-error-warning-line'); }
```
Three distinct failures collapse into one message: HTTP ≥400 (pre-INSERT only),
unparseable 2xx body (post-INSERT — any PHP warning printed after the write),
and `fetch()` rejection (post-INSERT — the response never arrived).

**Ruled out by reading the code:** the nonce is correct (`chat.php:31` mints
`wp_create_nonce('wp_rest')`, `chat.js:108` sends `X-WP-Nonce` — exactly what
`rest_cookie_check_errors` verifies, and a bad nonce 403s *before* dispatch, so
nothing would persist). `201` is not the problem — `res.ok` covers 200–299. The
SQL is properly `prepare()`d throughout.

### FIX-05 — make the response the last thing that can fail

```php
if ($result) {
    apollo_chat_set_typing($thread_id ?: $result, $sender_id, false);

    wp_schedule_single_event(
        time(),
        'apollo_chat_notify_email',
        array((int) $result, (int) $sender_id, wp_trim_words($message, 10, '...'))
    );

    return new \WP_REST_Response(
        array(
            'thread_id'  => (int) $result,
            'message_id' => (int) $msg_id,        // ← see FIX-06
            'success'    => true,
        ),
        201
    );
}
```
plus `add_action('apollo_chat_notify_email', 'apollo_chat_maybe_notify_by_email', 10, 3);`
in `functions.php`.

### FIX-06 — the server never returns the message id, and the client needs it

`apollo_send_message()` returns `$r['thread_id']` (`functions.php:300`). The
client then does, at `chat.js:939`:

```js
const idx = messages.findIndex(m => m.id === tempId);
if (idx !== -1) messages[idx].id = resp.thread_id;  // Actually this is the thread_id not msg_id
```

The comment admits it. The in-memory message takes the **thread** id while the
DOM node keeps `data-mid="temp-…"`. Every downstream lookup —
`chat.js:779, 794, 824, 836` — runs `parseInt('temp-…')` → `NaN`, so **reply,
edit, delete and react on your own just-sent message silently do nothing** until
you reopen the thread. Worse, if a real message id happens to equal the thread
id, those actions hit the wrong row.

`updateReadReceipts()` (`chat.js:1391`) hits the same `NaN`, so your own message
never gets its read tick — which reinforces the "it didn't send" impression even
when the toast is suppressed.

Fix: return `array('thread_id' => …, 'message_id' => $msg_id)` from
`apollo_send_message()` and reconcile both the array entry and `data-mid`.

### FIX-07 — the error branch must not contradict the optimistic bubble

Today the user gets a bubble **and** an error toast. Replace `chat.js:950–953`:

```js
} catch (e) {
    const row = $(`.ac-msg-row[data-mid="${tempId}"]`);
    if (row) row.classList.add('is-unconfirmed');   // don't claim it failed
    loadMessages(activeThreadId);                    // authoritative re-sync
    if (e.status && e.status < 500) toast('Erro ao enviar mensagem', 'ri-error-warning-line');
    console.error('[ApolloChat] send:', e);
}
```
Add `.ac-msg-row.is-unconfirmed .ac-bubble { opacity:.55 }` to `chat-shell.css`
(composer/message cell) — one owner, no duplicate.

### Diagnostics already in place

You do not need new logging to confirm which branch fires. `Plugin.php:613`
already sets an `X-Apollo-Debug-Chat` response header on every `/chat/send`, and
`:604` writes `wp-content/debug-161c5c.log`:

- header present + `send OK` + JS error → body poisoned, or response never arrived (the email path)
- header present + `apollo_send_message returned false` → real DB failure, `db_error` filled
- header absent → died before the callback; a different bug entirely

---

## 3. Why the friend has to refresh — the live stream drops messages by design

`setInterval(poll, 3000)` at `chat.js:2288`, started once in `init()`, never
rebound per thread. No SSE, no WebSocket, no Heartbeat.

### FIX-08 — the same-second hole (primary cause)

`Plugin.php:756`
```sql
AND m.created_at > %s AND m.sender_id != %d
```
`created_at` is a `DATETIME` with **1-second resolution**, the comparison is
**strict `>`**, and `lastPollTS = data.timestamp` (`chat.js:1277`) is
`current_time('mysql')` captured at `Plugin.php:838` — *after* the SELECT has
already run.

Any message written in the same second as a poll response is skipped, and
`since` has already advanced past it. **It is permanently lost from the live
stream** and only reappears on a reload. With a 3s cadence this is a routine
occurrence, not an edge case.

Fix: stop tracking a timestamp. Track `MAX(m.id)`:
```sql
WHERE r.user_id = %d AND r.is_deleted = 0
  AND m.id > %d AND m.sender_id != %d AND m.is_deleted = 0
ORDER BY m.id ASC LIMIT 50
```
and return `last_id` instead of `timestamp`. Monotonic, gap-free, no clock
involved. `knownMsgIds` (already maintained at `chat.js:1283`) stays as the
belt-and-braces dedupe.

### FIX-09 — thread ids are renumbered under the user mid-session (secondary cause)

The client only appends when the ids match (`chat.js:1284`):
```js
if (parseInt(nm.thread_id, 10) === activeThreadId) { … }
```
But `apollo_chat_consolidate_dm_pair()` **renumbers threads continuously**. It
runs on every `GET /chat/threads/{id}` (`Plugin.php:509`) and for every 1:1 row
in every `GET /chat/threads` (`functions.php:377`) — and `loadThreadList()` fires
on init, after every send (`chat.js:948`) and on every poll that reports a new
message (`chat.js:1309`). The canonical thread is picked as *whichever duplicate
most recently received a message* (`functions.php:1354`, `:1449`), then rows are
physically moved (`functions.php:1473`
`UPDATE chat_messages SET thread_id = %d WHERE thread_id = %d`).

So `activeThreadId` goes stale mid-session. The message **is** in the poll
payload, but `nm.thread_id !== activeThreadId`, so it is never appended — the
recipient gets only the sound and a sidebar bump (`chat.js:1306`). A refresh
re-runs `openThread` → consolidation → canonical id → the message appears.
**Exactly the reported symptom.**

Fix, in two parts:
1. A **one-time migration** plus a unique index on the DM pair, so consolidation
   stops running inside idempotent GETs every 3 seconds per active user with no
   locking. Write queries in a GET are the underlying defect.
2. Until then, have `/poll` return the canonical id and have the client adopt it:
   `if (data.canonical_thread_id) activeThreadId = data.canonical_thread_id;`

### FIX-10 — thread history loads the *oldest* 50

`apollo_get_thread_messages()` (`functions.php:440`) is
`ORDER BY m.created_at ASC LIMIT 50 OFFSET 0`. In any thread with more than 50
messages the user opens ancient history and **new messages never appear even
after a refresh**. It then writes `last_read_message_id` from that stale page
(`functions.php:456–473`), corrupting read receipts.
Fix: select `ORDER BY m.created_at DESC LIMIT 50` then `array_reverse()`.

### FIX-11 — poll appends new messages backwards

`Plugin.php:764` is `ORDER BY m.created_at DESC LIMIT 50`; `chat.js:1282` appends
each to the end. Two messages inside one 3s window render newest-first.
(Superseded automatically by the `ORDER BY m.id ASC` in FIX-08.)

### FIX-12 — the "new bubble emerges" animation exists but never gets to run cleanly

`animateMsgEnter(row)` is wired at `chat.js:918` (optimistic) and `:1296` (poll),
and `scripts.php:302` has the GSAP enter tween. It is not missing — it is being
starved by FIX-08/09 (message never appended) and by two render bugs:

- **First message into an empty thread:** `renderMessages()` paints
  `.ac-empty-chat` and returns early (`chat.js:566`), so `#ac-typing` never
  exists. `sendMessage` falls to the `else` at `:917` and appends *after* the
  placeholder, which is `flex:1` (`chat-premium.css:666`) and eats the pane.
  Result: "Envie a primeira mensagem!" stays on screen above the new bubble.
  Fix: clear `.ac-empty-chat` before the first append.
- **`bindMessageEvents(area)` re-runs over the entire message area after every
  single append** (`chat.js:918`, `:1296`). After N appends the first bubble
  carries N listeners — one reaction click fires N toggles. Fix: bind the new row
  only, or delegate once from `.ac-messages`.

---

## 4. The clock defect — one root cause, six visible symptoms

Timestamps are **written** with `current_time('mysql')` (site-local,
America/Sao_Paulo, UTC−3) and **compared** against MySQL `NOW()` (UTC on a
typical host). Verified across both files:

| Location | Code | Consequence |
| --- | --- | --- |
| `functions.php:655` vs `:668`, `:686` | writes `current_time('mysql')`, reads `last_seen > DATE_SUB(NOW(), INTERVAL 2 MINUTE)` | **online dots can never show** |
| `functions.php:607` vs `:633` | `updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)` | **typing indicator can never show** |
| `functions.php:1967` | `p.last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)` | **every recipient always reads as offline → `wp_mail()` runs on every single send** (this is what makes §2 fire constantly rather than occasionally) |
| `functions.php:767` | `if ((time() - strtotime($msg->created_at)) > 900) return false;` | every message reads 3h old → **editing your own message always 403s** |
| `chat.js:747` | `new Date(m.created_at.replace(' ','T') + 'Z')` | same assumption client-side → **edit button never renders**; every timestamp 3h off (`formatTime` `:175`, `timeAgo` `:164`) |
| `Plugin.php:1223` | `DELETE … WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)` | the hourly cron **wipes the entire presence table** every run |

**FIX-13** — pick one clock and hold it. Recommended: write everything with
`current_time('mysql', true)` (GMT) and replace every SQL `NOW()` with a bound
`%s` of `gmdate('Y-m-d H:i:s')`. Affected: `functions.php:633, 668, 686, 1967,
2047, 2048`; `Plugin.php:1221, 1223`. Then `functions.php:767` compares against
`time()` correctly and `chat.js:747`'s `+ 'Z'` becomes true rather than
accidental.

Note this is a **data migration**, not just a code change — existing
`last_seen`/`created_at` rows are in local time. Ship the read-side fix and a
one-time `UPDATE … SET col = CONVERT_TZ(col, '-03:00', '+00:00')` together, or
the two halves disagree for one deploy window.

---

## 5. The CSS nits you called out

### `.ac-thread-preview strong` — already 11px, single owner

`chat-shell.css:237` — sole declaration, uncontested:
```css
/* 2026-09-03: sender name in inbox preview smaller than message text */
.ac-thread-preview strong { font-size: 11px; font-weight: 600; color: var(--ac-ink-2); letter-spacing: -0.01em; }
```
Parent `.ac-thread-preview` is `12.5px` (`chat-premium.css:500`). If it still
reads large, the cause is not this rule — check that `chat-shell.css` is
actually arriving (see §1 FIX-02's stale-cascade note). **FIX-14:** take it to
`10.5px` / `font-weight:650` if you want more separation, and while you are
there fix the latent inheritance bug: `chat-premium.css:411` sets the unread
colour on the *span*, but the `strong` child hardcodes `var(--ac-ink-2)`, so
unread threads brighten the message text and not the sender name.

### `.ac-reaction` — the blur is already gone

`chat-shell.css:430` is the last word and documents the removal in place:
```css
.ac-reaction {
	…
	background: rgba(255, 255, 255, 0.92);
	/* 2026-09-03: backdrop-filter/filter (blur) removed from emoji reaction
	   pills — emojis render crisp, no glass effect. */
```
`chat-premium.css:943` has no blur either. No `backdrop-filter` on `.ac-reaction`
in any live cell, and `scripts.php:302` tweens only opacity/scale/y/rotation.
**Nothing to change.** If pills still look soft, it is the `rgba(255,255,255,.92)`
translucency over a tinted pane — which FIX-01 changes.

---

## 6. `/mensagens/{user-id}` — the namespace collision has to be decided first

Current rewrites (`Plugin.php:86`):
```php
add_rewrite_rule('^mensagens/?$',        'index.php?apollo_chat_page=inbox', 'top');
add_rewrite_rule('^mensagens/(\d+)/?$',  'index.php?apollo_chat_page=thread&apollo_thread_id=$matches[1]', 'top');
```

**A user id and a thread id are both bare integers in the same URL slot.** They
cannot be disambiguated from the path. `/mensagens/42` is already thread 42.

There is also a live dead end: `apollo_chat_get_thread_url_for_user()`
(`functions.php:1568–1598`) emits `/mensagens?new=1&to={user_id}` when no thread
exists — and **the client never reads those query params.** `chat.js:63` only
reads `CFG.thread_id`. Every "message this user" link for a first-contact pair
currently lands on an empty inbox.

**FIX-15 — recommended shape**, a distinct segment plus a server-side redirect
to the canonical thread URL, so the address bar stays honest and no state is
duplicated:

```php
add_rewrite_rule('^mensagens/u/(\d+)/?$', 'index.php?apollo_chat_page=dm&apollo_peer_id=$matches[1]', 'top');
```
```php
// handle_virtual_pages(), before render:
if ('dm' === $page) {
    $peer = (int) get_query_var('apollo_peer_id');
    $tid  = apollo_chat_find_or_create_dm(get_current_user_id(), $peer);  // reuses consolidate logic
    wp_safe_redirect(home_url('/mensagens/' . $tid), 302);
    exit;
}
```
Then point `apollo_chat_get_thread_url_for_user()` at `/mensagens/u/{id}`
unconditionally and delete the `?new=1&to=` branch — one URL shape for both
cases, existing thread or not.

Two things to settle before writing it:
- **Rate limiting.** `/mensagens/u/{n}` creates a thread row on GET. It needs the
  same flood guard as `/send`, or a crawler enumerates threads for every user id.
- **`apollo-notif` links.** `functions.php:290` and `:1980` hardcode
  `/mensagens/{thread_id}` in notification and email bodies. Those stay correct
  under this design — worth confirming nothing else builds chat URLs by hand.

If you would rather have the bare `/mensagens/{n}` resolve both, the rule is
"try thread first, fall back to user": look up the id in `chat_threads`, and if
the current user is not a participant, treat it as a peer id. It works, but it
makes every thread URL a two-query lookup and silently changes meaning when a
thread is deleted. I would not ship it.

---

## 7. Design-system violations (`CLAUDE.md` §Non-negotiable rules)

### `:root` declared in template and plugin cells — rule 1

Three violating blocks, 17 tokens, all live.

**V1 — `chat.php:79`, the serious one.** This redefines **core's own tokens**, at
`:root`, for every consumer on the page:
```css
:root { --bg: #fff; --white-3: #f5f5f7; }
```
Verified against the DS reference (`apollo-admin/templates/partials/styles.php:91`
and `base-design-and-reference.html:139`, `:213`): core defines
`--white-3: #fafafa` in light and **`#161618` in dark**. Hardcoding `#f5f5f7`
here pins chat to a light-mode value permanently — **the dark theme cannot reach
this screen**, and every other component on the page that reads `--white-3`
inherits chat's fork. Delete both lines (this is also FIX-01).

**V2 — `chat-shell.css:5–27`** (12 `--ac-*` tokens) and **V3 — `:563`, `:604`,
`:670`** (three media-scoped `:root` re-declarations of `--ac-pad-x`,
`--ac-pad-x-msg`, `--ac-react-inset`). Every one of these is consumed only inside
`.apollo-chat-wrap`'s subtree, so **FIX-16** is a verbatim move to
`.apollo-chat-wrap { … }` with zero behaviour change — the sanctioned
component-scoped pattern from `CLAUDE.md` (`.pev { --pev-band: … }`).

While there: `--ac-line` (`:11`) and `--ac-radius` (`:22`) are declared and never
referenced. And `chat.js:1897` sets `el.style.background = 'var(--ac-hover)'` —
**`--ac-hover` is defined nowhere**, so that hover state silently does nothing.

**V4 — `chat.css:4`** `:root{--primary:var(--black-1)!important;}` — dead file,
but an `!important` custom property at `:root` would hard-fork `--primary`
ecosystem-wide if it were ever re-linked. Another reason to delete the file.

### Duplicate selectors — the cardinal sin, ranked

**Tier 1 — wrong on screen today**

1. `.ac-messages` background — three-way: `chat-premium.css:654` → `chat-shell.css:293 !important` → **`chat.php:153 !important` wins**. §1. *The single most damaging conflict in the plugin.*
2. `.ac-compose-form` `min-height` — premium:1171 survives because shell:500 forgot to restate it. §1 FIX-02.
3. `.ac-sidebar-header` background — premium:109 → shell:109 → **`chat.php:158` wins**. Kills `.ac-btn-new` and `.ac-sidebar-search input`.
4. `.ac-chat-header` — **four cells, one selector**: premium:560, shell:262, shell:651 (@≥768), chat.php:158.
5. `.ac-compose-input` — six conflicting props. Shell needs `!important` on three of them purely to out-rank premium at equal specificity. Textbook symptom.
6. `.ac-sidebar-header::before/::after` — three declarations each; the sparkle animations at premium:128/:137 are already dead by premium:120, then killed a third time by shell:119.
7. `.ac-layout::before/::after` — four declarations each. The starfield at premium:47–120 plus its keyframes (~40 lines) is unreachable three times over.

**Tier 2 — measurable geometry drift**

`.ac-thread-avatar` 50px vs 48px · `.ac-send-btn` 42px vs 40px · `.ac-bubble`
padding/radius/line-height · `.ac-thread` padding+gap (**and an intra-rule
duplicate: `chat-shell.css:195` `border-radius:0` then `:197`
`border-radius:var(--ac-radius-sm)` in the same block**) · `@≥768 .ac-messages`
padding declared by three cells · `@≥768 .ac-sidebar` width · `.ac-date-sep span`
4 props · `.ac-reactions` — premium:936 is 100% dead, shell:416 re-authors it
entirely.

**Tier 3 — colour/token drift**

`.ac-header-status.online` `#86efac` vs `#34c759` · `.ac-ctx-item.danger`
`#ef4444` vs `#e11d48` · `.sent .ac-bubble` `var(--black-1,#131517)` vs `#1d1d1f`
· `.ac-msg-row` max-width declared 3× · plus ~30 more premium↔shell pairs.

**FIX-17** — the structural fix, in the project's own idiom: split the two sheets
by concern instead of by vintage, and put the ownership in each file header.

```
chat-tokens.css      .apollo-chat-wrap { --ac-* }        ← V2/V3 land here
chat-shell.css       layout, sidebar, header, panes
chat-thread.css      bubbles, reactions, reply quotes, date separators
chat-composer.css    .ac-compose*, .ac-send-btn, .ac-reply-bar   ← FIX-02/03 land here
```
`chat-premium.css` becomes animation + modal only. `chat.php`'s inline block
keeps **only** the viewport lock (`html/body` `100dvh`, `overflow:hidden`), the
Lenis/scrollbar kill-switches and the starfield kill-switch — every colour and
padding rule leaves it.

**FIX-18** — extend `_sandbox/build-portal-harness.mjs`'s **assertion D**
(no selector declared by two style cells) to the chat cells. This is the exact
class of bug it already caught on `.pev-recent`; 87 pairs is what it looks like
when a component is not covered.

### Dead selectors created by the duplication

`.ac-compose-left` (premium:1174/1180/1188), `.ac-attach-preview` +
`.ac-attach-name` + `.ac-attach-close` (premium:1136–1163), `.ac-gif-btn` /
`.ac-gif-label` / `.ac-gif-footer` (premium:1332+/1431) — all target markup that
no longer exists under the text-only policy (`apollo-chat.php:25`).

### Token hygiene

- **The DS radius scale is used zero times.** `--r-lg --r --r-sm --r-xs --r-pill`:
  0 occurrences. Instead: five spellings of "pill" (`999px`, `99px`, `100%`,
  `50%`, and `--ac-radius`) and eight ad-hoc radii. **FIX-19.**
- **`--ease` used 3×, the identical curve hardcoded 12×** as
  `cubic-bezier(.16,1,.3,1)` (premium:96, 202, 1320, 1563, 1595, 1626, 1688,
  1715, 1885; shell:865). `--ease-snappy` / `--ease-smooth` never used.
- **Malformed colour fallbacks — invalid CSS, declaration dropped at parse:**
  `var(--primary, FF9820)` (missing `#`) at `chat-premium.css:403, 518, 1218,
  1398, 1633`. `chat-shell.css:210` is the only correct spelling. Note the DS
  reference itself carries the same typo at
  `apollo-admin/base-design-and-reference.html:42` — worth fixing at the source.
  Low severity while core.js defines `--primary`, but `.ac-compose-input:focus`
  is one of the five, so **the composer focus ring currently does nothing.**
- **Two names for one concept:** `--muted` (premium ×3) vs `--muted-txt`
  (shell ×2). And `--txt-rgb` has two different fallbacks —
  `19,21,23` in premium (×58) vs `29,29,31` in shell (×6).
- `--ff-heading` never used; display type falls back to
  `var(--ff-main, "Space Grotesk", …)` at premium:19. No mono eyebrows anywhere.

---

## 8. Shipping debug instrumentation

Per this workspace's own rule — a saved file is a deployed file — **all of this
is live on apollo.rio.br right now.**

**Client beacons** to `http://127.0.0.1:7754/ingest/da9d552b-…`, header
`X-Debug-Session-Id: 161c5c`:
`chat.js:123, 559, 628, 645, 992, 1023, 1091, 2000, 2063` and
`scripts.php:324` — the last one sits **inside `animateReactionEnter()`, so it
fires once per reaction pill rendered.** From an `https://` page these are
mixed-content requests: blocked by the browser, not merely failing.

**Server-side:** `@file_put_contents()` to `wp-content/debug-161c5c.log` at
`Plugin.php:604` and `functions.php:72, 118, 157, 214, 1429, 1522`, plus the
`X-Apollo-Debug-Chat` response header on every send (`Plugin.php:613`).

`chat.php:215–220` documents that the *same class* of beacon was already stripped
from that file once ("removed inventory() — it POSTed a full DOM/class audit …
on every real visitor's page load"). These survived that pass.

**FIX-20 — and one trap to avoid while doing it.** In `functions.php`, the
`return false;` guards at **line 235** (message-insert failure) and **line 177**
(thread-insert failure) sit *inside* `// #region agent log` … `// #endregion`
markers. A mechanical "strip the agent-log regions" pass **deletes the failure
guards and turns a failed INSERT into a silent success.** Move both `return
false;` statements outside the markers *before* any cleanup pass. Keep the
`X-Apollo-Debug-Chat` header until FIX-05 is verified in production — it is how
you will confirm the fix landed.

---

## 9. Remaining defects (send/receive path)

| # | Defect | Location |
| - | ------ | -------- |
| 21 | **Scroll-up pagination broken both ways.** `apollo_chat_get_older_messages()` returns DESC (`functions.php:1079`, `:1095`); client prepends `[...older, ...messages]` (`chat.js:1610`) so history is backwards, and `oldestMsgId = older[0].id` takes the *newest* of the batch → the next page refetches the same rows forever. | `functions.php:1079`, `chat.js:1609` |
| 22 | `oldestMsgId` poisoned by the optimistic entry — `chat.js:576` reads `messages[0].id`, which is `NaN` in a thread holding only a temp bubble; `:1592` (`oldestMsgId > 0`) then silently disables pagination. | `chat.js:576` |
| 23 | **Notification sound + desktop popup fire for the thread you are actively reading** — `chat.js:1306` sits outside the `nm.thread_id === activeThreadId` block. | `chat.js:1306` |
| 24 | **Stale-response race on fast thread switching.** `openThread()` awaits at `chat.js:431` then renders into shared globals without re-checking `activeThreadId` — unlike `loadMessages()`, which *does* guard (`:478`). Switch quickly and the slower response paints over the newer thread. | `chat.js:431` |
| 25 | **No `AbortController` anywhere.** `poll()`, `loadThreadList()`, `loadMessages()` and `openThread()` interleave freely; `poll()` pushes into `messages` (`:1288`) that a concurrent `loadMessages()` is about to replace wholesale. | global |
| 26 | **No double-submit guard.** Enter and click both call `sendMessage()` (`chat.js:2163`) with no in-flight flag and no button disable. Server flood control is 10/60s, so it will not catch two fast sends. | `chat.js:2163` |
| 27 | `typingSent` never reset on send — `chat.js:931` calls `sendTyping(false)` but leaves `typingSent = true` and `typingTimer` pending (`:1202`); the stale timer fires 3s later and sends a second `typing:false`. | `chat.js:931` |
| 28 | The `updates` poll query has no `m.sender_id != %d` filter (`Plugin.php:794–817`), so you receive echoes of your own edits and deletes. | `Plugin.php:794` |
| 29 | `register_rest_route` declares **no `args` schema on any route** except `/chat/thread-for-context` and `/chat/gif-search`. Sanitization is hand-rolled per handler. `permission_callback` is `is_user_logged_in()` everywhere; per-object authorization is delegated to helpers (correct where checked: `functions.php:59, 429, 1061, 1635`) but `/chat/block/{id}` (`Plugin.php:272`) does no existence check on the target. | `Plugin.php:117` |
| 30 | `tempoHTML()` (`chat.js:142`) — when the input fails `/^(\d+)([a-z]+)$/i`, `num = str` is injected into `innerHTML` unescaped at `:364`, fed from `t.time_ago`, which falls back to the raw `last_message_at` column (`Plugin.php:1256`). Not user-controlled today; it is the one HTML sink in the file that bypasses `esc()`. | `chat.js:142` |
| 31 | Consolidation runs write queries (`UPDATE chat_messages SET thread_id`, `UPDATE chat_participants SET is_deleted`) inside an idempotent GET, per 1:1 row per request, every 3s per active user, **with no locking**. Two users consolidating the same pair concurrently can interleave. Should be a migration + unique index. | `functions.php:377` |

**Checked and clean:** SQL is properly `prepare()`d throughout. The two raw
queries are safe — `functions.php:1379` (`INFORMATION_SCHEMA`, only
`$wpdb->prefix` interpolated) and `:1652` (`IN ({$ids})` built from
`array_map('intval', …)`). Search uses `$wpdb->esc_like()` correctly (`:848`).
Note this contradicts `_inventory/registry/04-audit.json:73`, which still flags
"apollo-chat Plugin.php L1191/L1193 raw SQL without prepare()" — those lines are
the `DELETE … chat_typing` / `chat_presence` cleanups, now at `Plugin.php:1221`
and `:1223`, with no user input in them. **The registry entry is stale; update
it.** (Consistent with the `CLAUDE.md` warning that registry chapters lag disk.)

---

## Fix order

Sequenced so each stage is verifiable before the next lands, and so the two
stages that touch live behaviour ship last.

**Stage 1 — CSS, zero risk, immediately visible**
FIX-01 (delete `--white-3` overrides from `chat.php`) → FIX-02 (`min-height`) →
FIX-03 (`border:0`) → FIX-04 (gutters) → FIX-14 (`strong` sizing) → delete
`assets/css/chat.css`. *Sandbox: assertion D over the chat cells (FIX-18) before
saving anything.* FIX-01 alone restores six invisible components and unbreaks
dark mode.

**Stage 2 — the send bug**
FIX-05 (defer `wp_mail` to cron) → FIX-06 (return `message_id`) → FIX-07 (error
branch stops lying). Verify with the `X-Apollo-Debug-Chat` header still in place.

**Stage 3 — the live stream**
FIX-08 (`MAX(id)` cursor instead of a timestamp) → FIX-11 (folded in) → FIX-10
(newest 50) → FIX-12 (empty-thread placeholder + listener binding). This is what
makes the new-bubble animation actually appear.

**Stage 4 — the clock**
FIX-13, code and data migration together in one window. Restores presence dots,
typing indicators, message editing, correct timestamps — and stops the email path
firing on every send, which is the aggravator behind Stage 2.

**Stage 5 — routing**
FIX-15 (`/mensagens/u/{id}` + 302), after the peer-lookup helper is settled and
rate-limited.

**Stage 6 — structure and hygiene**
FIX-16 (`:root` → `.apollo-chat-wrap`) → FIX-17 (split cells by concern) →
FIX-19 (radius/easing tokens) → FIX-20 (strip beacons — **move the two `return
false;` guards out of the `#region` markers first**) → items 21–31.

**Open decisions for you**

1. `/mensagens/u/{id}` versus overloading the bare numeric segment (§6). My
   recommendation is the distinct segment; the overload silently changes meaning
   when a thread is deleted.
2. FIX-13 needs a maintenance window for the `CONVERT_TZ` migration — or an
   accepted one-deploy window where old and new rows disagree.
3. FIX-17 changes the cell layout `styles.php` links. If any theme overrides a
   chat cell by filename today, that override needs re-pointing.

---

## Corrections to prior notes

- **`_inventory/registry/04-audit.json:73`** lists apollo-chat `Plugin.php`
  L1191/L1193 as CRITICAL unprepared SQL. Re-read at current line numbers
  (`:1221`, `:1223`) those are constant-string `DELETE` cleanups with no user
  input. Stale — clear it.
- `src/Activation.php` and `src/Deactivation.php` **do exist** on disk, and so
  does `assets/js/apollo-gsap-text-fx.js`. Nothing missing there.
- `--rgb-t`, `--rgb-d`, `--white-3`, `--txt-rgb`, `--black-1`, `--primary` are
  **real core.js tokens** (mirrored in `apollo-admin/templates/partials/styles.php:36–109`).
  The bare `var(--rgb-t)` usages in `chat-premium.css` are fine; only the
  malformed `FF9820` fallbacks and the `:root` fork in `chat.php` are defects.
- There is **no `apollo-ui` plugin** in this tree. The design-token layer you are
  referring to is core.js + the two Blank Canvas Apollo+ DS files named in
  `CLAUDE.md`.
