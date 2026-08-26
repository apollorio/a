# apollo-telegram — Revival Plan

**Written:** 2026-08-24
**Sandbox:** `https://apollo.rio.br/` — RealTimeSync, ~30s propagation
**Trigger:** phone confirmation on `/registre` (apollo-login) never delivers a code.

---

## 1 · ROOT CAUSE — found, proven live

### The chain (every link verified)

| # | Link | State |
| --- | --- | --- |
| 1 | `/registre` prints `telegramNonce = wp_create_nonce('apollo_telegram_verify')` | ✅ `templates/register.php:66` |
| 2 | `apollo-auth-phone.js` sends it as header **and** body param | ✅ `restPost()` line 45-56 |
| 3 | `apollo_telegram_verify_rest_nonce()` verifies action `apollo_telegram_verify` | ✅ **matches** — `helpers.php:174` |
| 4 | 7 verification routes registered | ✅ **live** — `apollo-telegram/v1` namespace responds |
| 5 | `wp_apollo_telegram_verif` table | ✅ `install_schema()` runs on `plugins_loaded:20` **every request**, so file-sync deploys can't miss it |
| 6 | `createPending()` inserts row, returns `deep_link` | ✅ sound |
| 7 | Webhook receiver route exists | ✅ **live** — `POST /wp-json/APOLLO_TELEGRAM/v1/get-message` |
| 8 | **Webhook URL registered with Telegram** | ❌ **BROKEN — this is it** |

### The break

`apollo-telegram.php:247` registers the webhook using the **query-var form**:

```php
$webhook_url = add_query_arg('rest_route', '/APOLLO_TELEGRAM/v1/get-message', home_url('/'));
// → https://apollo.rio.br/?rest_route=/APOLLO_TELEGRAM/v1/get-message
```

Its inline comment says this form is *"immune to rewrite-rule corruption (unlike /wp-json/ pretty path)"*.
**On this install that is now inverted.** Verified live, 2026-08-24:

```
GET /wp-json/APOLLO_TELEGRAM/v1/get-message      → 200 JSON, route present  ✅
GET /?rest_route=/APOLLO_TELEGRAM/v1/get-message → 301 → /casa, HTML page   ❌
```

The query-var request never reaches REST routing at all. It falls through to the
front end, where `apollo-templates/includes/landing-router.php:37`
(`template_redirect`, priority 4) matches `path === ''` for a guest and 301s to
`/casa`.

> Note this also proves REST never claimed it: `template_redirect` does not fire
> on a REST request. And a GET against a POST-only route would return 404 *JSON*,
> not a redirect.

**Consequence:** Telegram POSTs every update to a URL that answers with the
homepage. `/start {request_id}` is never processed → no 6-digit code is ever
sent → the user waits forever. Every other part of the system is healthy, which
is why this looks like "the bot is dead" rather than a routing bug.

### The fix — one line

```php
// apollo-telegram.php:247
$webhook_url = rest_url('APOLLO_TELEGRAM/v1/get-message');
```

Then bump the guard transient so the daily check re-registers immediately:
`apollo_tg_webhook_checked_v3` → `..._v4`.

**Do not delete the old comment** — replace it with the measurement above, or the
next person will "fix" it back to the query-var form for the same stated reason.

### Verify after deploy (30s)

1. `GET https://api.telegram.org/bot<TOKEN>/getWebhookInfo` → `url` must be the
   `/wp-json/…` form, `last_error_message` empty, `pending_update_count` falling.
2. `/registre` → enter phone → open deep link → **code arrives**.

---

## 2 · SECONDARY FINDINGS (real, not blocking)

| # | Finding | Impact | Action |
| --- | --- | --- | --- |
| S1 | `install_schema()` runs `dbDelta()` on **every request** (`plugins_loaded:20`) | `dbDelta` issues `DESCRIBE` + `SHOW INDEX` on every page load, site-wide | Gate behind a schema-version option; keep the file-sync-safe intent |
| S2 | `Core` boot wrapped in `catch (\Throwable)` that is silent unless `WP_DEBUG` (`apollo-telegram.php:170`) | A boot failure silently removes the entire bot while verification routes stay live — indistinguishable from "bot ignoring me" | Always `apollo_telegram_debug_log()`; add an `admin_notice` when `$apolloTelegramCore === null` |
| S3 | Docs contradict code: `FLOW_ANALYSIS.md:1456` says `/wp-json/apollo-telegram/v1/get-message` (lowercase); code declares `APOLLO_TELEGRAM/v1` | Sent me down the wrong path; will do the same to the next dev | Correct the docs to the uppercase namespace |
| S4 | `MISSING_COMPONENTS_REPORT.md:316` — "Confirm database schema installed on activation" still unchecked | Stale — S1 shows it runs every request | Tick it, note the mechanism |
| S5 | `docs/phone-verification-system.md:228` lists a needed `/register-user` endpoint calling `wp_insert_user()` | Verification succeeds but **no account is created** — the flow stops half-way | Phase 3 below |

---

## 3 · FLOW MAP — user vs bot

### 3.1 Registration + phone confirmation (the broken flow)

```
USER (browser, /registre)                    APOLLO (PHP)                 TELEGRAM BOT
─────────────────────────                    ────────────                 ────────────
1. types phone
2. blur ────────────────────► POST /check-phone-available
                              ├─ normalize (E.164)
                              └─ already registered? ──► "já registrado"
3. taps "Confirmar" ────────► POST /request-support-verification
                              ├─ rate-limit IP  (5 / 15min)
                              ├─ rate-limit phone (3 / 1h)
                              ├─ INSERT verif row (step=requested)
                              └─ returns { request_id, deep_link }
4. opens deep_link ──────────────────────────────────────► /start {request_id}
                                                            ├─ look up request_id
                                                            ├─ generate 6-digit code
                                                            ├─ store bcrypt hash
                              ◄─────────────────────────────┤ step=code_sent
                                                            └─ SENDS CODE  ← ✗ DEAD TODAY
5. browser polls ───────────► POST /verification-status  (every ~3s)
                              └─ { step, status }
6. types code ──────────────► POST /verify-support-code
                              ├─ wp_check_password(code, hash)
                              ├─ max attempts guard
                              └─ status=verified
7. ▸ account creation ──────► ✗ NOT IMPLEMENTED  (see S5)
```

**Failure today is at step 4.** Steps 1-3 and 5-6 all work; the user simply never
receives anything, because Telegram's POST lands on `/casa`.

### 3.2 Bot conversation — target design

```
                      ┌──────────────────────┐
                      │   /start [payload]   │
                      └──────────┬───────────┘
                                 │
                 ┌───────────────┴───────────────┐
         payload = UUID?                    no payload
                 │                               │
      ┌──────────▼──────────┐        ┌───────────▼───────────┐
      │  VERIFICATION MODE  │        │     DISCOVERY MODE    │
      │  send 6-digit code  │        │   "o que rola hoje?"  │
      │  step → code_sent   │        └───────────┬───────────┘
      └──────────┬──────────┘                    │
                 │                    ┌──────────┼──────────┐
         user returns to web          │          │          │
                                  [Hoje]   [Fim de semana]  [Por estilo]
                                      │          │          │
                                      └──────────┼──────────┘
                                                 │
                                    ┌────────────▼────────────┐
                                    │  EventsBotService       │
                                    │  query CPT event        │
                                    │  → cards + deep links   │
                                    └────────────┬────────────┘
                                                 │
                                  ┌──────────────┼──────────────┐
                              [Ver evento]  [Mais assim]   [Seguir DJ]
                                   │             │              │
                              apollo.rio.br  same sound tax   apollo-fav
                              /evento/{slug}                  (needs link)
```

**Intent router (no AI, deterministic):**

| User says | Match | Bot answers |
| --- | --- | --- |
| `/start <uuid>` | payload is UUID | verification code |
| `/start` · `oi` · `menu` | greeting set | main menu (3 buttons) |
| `hoje` · `hj` · `tonight` | keyword set | events where `_event_start_date = today` |
| `fds` · `fim de semana` | keyword set | today → +4 days (reuses portal's `neste-fds` window) |
| `techno` · `house` · `funk` … | matches a `sound` term | events carrying that term |
| free text | fuzzy vs `sound` + `local` + DJ titles | best match, else main menu |
| `/cancelar` | command | clears conversation state |

Recommendation logic — **real data only**, mirroring the portal:
`sound` taxonomy for style, `_event_dj_ids` for DJ, `_event_loc_id` for venue.
No invented "similarity"; "mais assim" = same `sound` term, next by date.

---

## 4 · WP-ADMIN CONTROL PANEL (requested)

New tab under the existing Apollo Telegram menu — **all real options, no new tables**:

```
┌─ Apollo Telegram · Controles ────────────────────────────────┐
│                                                              │
│  CONEXÃO                                                     │
│   Bot token          [ •••••••• ]        ● conectado         │
│   Webhook            https://…/wp-json/… ● ok · 0 pendentes  │
│   [ Re-registrar webhook ]   ← forces setWebhook now         │
│                                                              │
│  CONFIRMAÇÃO DE TELEFONE                                     │
│   (•) Ligada — bot envia código de 6 dígitos                 │
│   ( ) Desligada — bot responde com a mensagem abaixo         │
│   ┌────────────────────────────────────────────────────┐     │
│   │ No momento não estamos confirmando telefone por    │     │
│   │ aqui. Você já pode usar o apollo::rio normalmente… │     │
│   └────────────────────────────────────────────────────┘     │
│   ↑ shown INSTEAD of the code when off                       │
│                                                              │
│  FLUXOS DO BOT                            ativo              │
│   Descoberta de eventos (hoje / fds)      [✓]                │
│   Recomendação por estilo                 [✓]                │
│   Recomendação por DJ                     [✓]                │
│   Broadcast                               [✓]                │
│                                                              │
│  MENSAGENS  (one textarea per flow, %placeholders%)          │
│   Boas-vindas · Sem eventos · Erro · Código enviado          │
└──────────────────────────────────────────────────────────────┘
```

Storage: extend the existing `APOLLO_TELEGRAM_OPTIONS_KEY` array — no new
option rows, no new tables.

**Behaviour when phone confirmation is OFF:**
`/start {uuid}` must still mark the request resolved (`status=disabled`) so the
web page stops polling and shows a clear message — otherwise the browser spins
forever. The bot replies with the admin's textarea text instead of a code.

---

## 5 · PHASES

### P0 — Unbreak the webhook *(minutes, one line)*
Change line 247 to `rest_url()`, bump transient to `_v4`.
**Gate:** `getWebhookInfo` shows the new URL, no `last_error_message`.
**Test:** `/registre` → phone → deep link → code arrives.

### P1 — Make failure visible *(S2)*
Log the `Core` boot throwable unconditionally; `admin_notice` when core is null.
Ship with P0 — without it the next boot failure is invisible again.
**Gate:** force a throw locally, confirm the notice.

### P2 — Stop the per-request `dbDelta` *(S1)*
Version-gate `install_schema()`.
**Gate:** schema still self-heals after a file-sync deploy (bump version, confirm).

### P3 — Close the registration loop *(S5)*
`POST /register-user` — creates the WP account **only** with a `status=verified`
row, single-use, then marks the row consumed.
**Gate:** verified phone → account exists → auto-login → `/feed`.
**Guard:** never create an account from an unverified/expired/consumed row.

### P4 — Admin control panel *(§4)*
Options + the OFF-path resolution described above.
**Gate:** toggling off makes the bot answer the textarea text and the web page
stop polling with a clear message.

### P5 — Conversation router *(§3.2)*
Deterministic keyword router + `EventsBotService` queries. Reuse the portal's
existing date windows so bot and site can't disagree.
**Gate:** each row of the intent table answers correctly; `/cancelar` clears state.

### P6 — Recommendations
"Mais assim" (same `sound`), "Seguir DJ" (needs a Telegram↔WP account link —
depends on P3).

**Order:** P0+P1 together (same deploy), then P2, then P3 → P4 → P5 → P6.

---

## 6 · WHAT I DID NOT DO, AND WHY

I stopped at diagnosis and did not apply the one-line fix.

Changing the webhook URL calls Telegram's `setWebhook` on the next request and
**repoints the live bot**. If the new URL were wrong, the bot would move from
"broken one way" to "broken another way", and I have no way to read
`getWebhookInfo` (needs the bot token) or to watch a real `/start` land.

The evidence is strong — the pretty path is provably live and the query-var form
provably redirects — but "provably live for GET on the namespace index" is not
the same as "verified receiving Telegram's POST with the secret-token header".
That check needs the token and a real device, which is a two-minute job for you
and impossible for me.

Apply P0, run the two verification steps in §1, and the rest of this plan
becomes straightforward.

---

## 7 · P5/P6 SHIPPED — VIBE QUIZ + GOLDEN TROPHY RANKING (2026-08-25)

Built ahead of P0. **None of it is reachable by a real Telegram user until P0
ships** — the webhook still points at a URL that never reaches REST routing
(§1). Everything below was verified structurally (brace/paren balance +
careful manual read; no PHP binary in this sandbox) and is ready to go live
the moment the webhook fix lands.

### 7.1 · What already existed (verified, not assumed)

`EventsBotService` was NOT a stub — it's a real, working 23KB
intent-detection engine already wired into `GenericCommand::execute()`,
handling "qual a boa", "esse fds", "hoje", "amanhã", neighborhoods, weather,
and a "me surpreende" random pick, entirely independent of this feature. The
gap was narrow and exact: `getEvents()` queried by date range only —
chronological order, no idea `_event_int_rank` or `_event_tag_*` existed.

### 7.2 · The gate — where the quiz gets asked

```
User: "qual a boa esse fds?"
   │
   ▼
GenericCommand::execute() ── free-text, not a slash command
   │
   ▼
EventsBotService::handle($text, $chat_id)
   │
   ├─ detectIntent() → {type: events, period: weekend, ...}
   │
   ├─ known_vibe = getKnownVibe($chat_id)   ← get_option('apollo_tg_vibe_prefs')
   │
   ├─ IF known_vibe === null:
   │     stashPendingIntent($chat_id, $intent)   ← transient, 10 min
   │     sendVibeQuiz($chat_id)                  ← its own message, inline keyboard
   │     return null                              ← GenericCommand sends nothing further
   │
   └─ ELSE:
         events = getEvents(start, end, neighborhood, known_vibe)
         return buildReply(events, intent)         ← 🏆 on the best-ranked event
```

The quiz is asked **once per chat, ever** (sticky, no expiry) — not once per
question. A person recommending parties to a friend doesn't re-interview them
every time they ask "what's good tonight?". `"muda minha vibe"` /
`"trocar vibe"` / `"outro tipo de festa"` (new `vibe_reset` intent) resets it
on demand.

`"me surpreende"` (`random` intent) deliberately **skips** the gate — asking
for a surprise is explicitly opting out of curation.

### 7.3 · Answering the quiz — two paths, one destination

```
Tap "B" button  ──▶  VibeQuizCallback::execute()   ──┐
                                                       ├──▶ EventsBotService::replyForVibeAnswer($chat_id, $answer)
Type "b" as text ──▶ GenericCommand (bare-letter,     │        │
  only if hasPendingIntent($chat_id) is true) ────────┘        ├─ setKnownVibe()        (persist, sticky)
                                                                ├─ popPendingIntent()    (resume what was asked,
                                                                │                         or default to "qual a boa")
                                                                ├─ getEvents(..., $answer)
                                                                └─ buildReply()  →  one HTML message, 🏆 on top
```

Both paths converge on the exact same method — tapping the inline button and
typing the bare letter behave identically, which is what "keeping natural
flow as if it were a person chatting" means in practice: the bot doesn't care
*how* you answered, only *what* you answered.

### 7.4 · The quiz → tag rule (single source of truth)

Lives in **apollo-events**, not apollo-telegram — the CPT-meta owner is the
only place the rule can drift from the data it's classifying:

```
apollo_event_vibe_quiz_definition()   // includes/functions.php, apollo-events 1.7.6
  a) Underground raiz          require: [underground]            exclude: [comercial]
  b) Underground + comercial   require: [underground, comercial] exclude: []
  c) Mainstream + comercial    require: [mainstream, comercial]  exclude: []
  d) Mainstream conceito       require: [mainstream]              exclude: [comercial]
```

Both the **question text** the bot shows and the **match rule** applied to
each event come from this one array. `apollo_event_classify_vibe_quiz($id)`
returns which letters an event satisfies (0, 1, or — edge case — 2, if an
admin ticked both Underground and Mainstream on the same event: it then
serves both groups rather than silently vanishing from recommendations over
a data-entry ambiguity). `apollo_event_matches_vibe_quiz($id, $answer)` is
the single-letter check `EventsBotService::getEvents()` calls per candidate.

**Literal-spec note:** letter b's *question text* mentions "um giro
LGBTQIA+", but its *match rule* is `underground + comercial` only, exactly as
given in the checkbox table — the `lgbtqia` tag is not a requirement. If the
intent was for b to also require `lgbtqia = true`, that's a one-line change
to the `require` array for `'b'` in `apollo_event_vibe_quiz_definition()`
and nowhere else (UI, save handler, and every reader all consume that same
function).

### 7.5 · Golden trophy — how "always recommend the best int-rank first" works

`EventsBotService::getEvents()` used to cap results to 8 (`MAX_EVENTS`)
*while looping* in date order — a bug for this feature, not just a gap: an
early-dated, unranked event could fill all 8 slots before a same-week,
rank-10 event ever got considered. Fixed: the query now fetches the full
candidate pool (already capped at 50 by `WP_Query`), sorts by
`_event_int_rank` descending (same tie-break as `apollo_event_get_top_ranked()`
in apollo-events — soonest date wins on equal rank, one consistent "what's
best" rule ecosystem-wide), **then** slices to `MAX_EVENTS`. `buildReply()`
prefixes index 0 with 🏆 instead of 🗓 — but only when that event's rank is
`> 0`. An all-zero pool (nothing curated yet) shows no trophy; a trophy on an
arbitrary chronological pick would be a lie about what "best" means.

### 7.6 · State storage (no new database tables)

| What | Where | Lifetime | Why |
|---|---|---|---|
| Known vibe per chat | one option row `apollo_tg_vibe_prefs` (non-autoloaded), `{chat_id: {answer, set_at}}` | sticky, no expiry | One row instead of one option per chat — avoids unbounded options-table growth as chats grow. If chat volume gets large, promote to a dedicated table (natural next step, not needed yet). |
| Pending intent (quiz interrupted this ask) | transient `apollo_tg_pending_intent_{chat_id}` | 10 minutes | Long enough for a real person to read 4 short lines and tap a button; short enough that a stale answer days later doesn't resurrect a dead context — falls back to "qual a boa" default instead. |

### 7.7 · Files touched this pass

- `apollo-events/includes/functions.php` — `apollo_event_vibe_quiz_definition()`, `apollo_event_classify_vibe_quiz()`, `apollo_event_matches_vibe_quiz()`.
- `apollo-events/includes/functions-global.php` — global aliases for the three above.
- `apollo-events/apollo-events.php` — version 1.7.5 → 1.7.6.
- `apollo-telegram/src/Services/Integrations/EventsBotService.php` — quiz gate in `handle()`, `vibe_reset` intent in `detectIntent()`, rank-aware `getEvents()`, golden-trophy `buildReply()`, new `replyForVibeAnswer()` / `hasPendingIntent()` (public) + `getKnownVibe()` / `setKnownVibe()` / `forgetVibe()` / `stashPendingIntent()` / `popPendingIntent()` / `sendVibeQuiz()` (private).
- `apollo-telegram/src/Telegram/CallbackQueries/VibeQuizCallback.php` — new file, inline-keyboard tap handler.
- `apollo-telegram/src/Telegram/Commands/UserCommands/GenericCommand.php` — bare-letter branch, gated on `hasPendingIntent()`.
- `apollo-telegram/apollo-telegram.php` — version 1.1.2 → 1.1.3.

### 7.8 · Deliberately not done — polish for a follow-up pass

- **Editing the original quiz message** to show the picked answer (Telegram
  `editMessageText` / `editMessageReplyMarkup`) instead of leaving the
  buttons live and sending a separate reply. Cosmetic; current behavior is
  fully functional, just one message busier than the smoothest version.
- **No `/vibe` slash command.** Free-text detection (`"muda minha vibe"`)
  and the bare-letter path cover it, but a discoverable slash command is
  cheap to add later for users who don't know the magic words.
- **Quiz-send itself isn't rate-limited** — only the eventual `getEvents()`
  call is, via the existing `events_chat:{chat_id}` limiter. A user who
  spams events-intent messages before ever answering could trigger repeated
  quiz sends. Low risk (Telegram's own flood control plus the existing
  `RateLimiter` on the *answer* path bound the real cost), but worth a
  dedicated `RateLimiter::allow('vibe_quiz_ask:' . $chat_id, ...)` guard in
  `handle()`'s gate branch if it's ever seen in practice.
- **No admin-facing analytics** on the vibe-answer distribution (how many
  chats picked a/b/c/d). Would need to stay as internal-only reporting per
  the same "never frontend" rule already applied to int-rank and the tags
  themselves — plausible P7, not requested this pass.
