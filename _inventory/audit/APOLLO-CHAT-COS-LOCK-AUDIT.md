# Apollo Chat — COS lock / never[] / routes audit

**Date:** 2026-09-11  
**Scope:** `apollo-chat` (live `2.0.2`) plus coupling hubs (core, brain/MU, templates, pane-engine, admin, adverts, events, notif, email, users).  
**Method:** tree inventory + ripgrep (`never[]`, write-lock, COS, `files_allowed`, `register_rest_route`, `apollo/v1`, chat) + line-traced send/read paths.  
**Constraint:** report-only. `source_files_changed` for product PHP/JS/CSS = 0.  
**Disk convention:** `_inventory/audit/` is the existing CPT-style non-product verify home (`AUDIT-REPORT-CPT-ALIGNMENT.md`, `APOLLO-AUDIT.md`). No `_cos/` directory exists in this repo.

---

## 1. Inventory

### 1.1 On-disk tree (18 files)

| Path | Role | Runtime |
|---|---|---|
| `apollo-chat/apollo-chat.php` | Bootstrap: constants `APOLLO_CHAT_VERSION=2.0.2`, `spl_autoload_register` for `Apollo\Chat\`, activation/deactivation hooks, `plugins_loaded` P15 gate on `APOLLO_CORE_BOOTSTRAPPED` then `includes/functions.php` + `Plugin::instance()` | **LIVE** |
| `apollo-chat/src/Plugin.php` | Rewrite, REST, blank-canvas page, cron cleanup, REST handlers | **LIVE** |
| `apollo-chat/src/Activation.php` | `dbDelta` for 9 tables + `apollo_chat_version` + `flush_rewrite_rules` | **LIVE** on activate |
| `apollo-chat/src/Deactivation.php` | Unschedules `apollo_chat_cleanup`, flushes rewrites | **LIVE** on deactivate |
| `apollo-chat/includes/functions.php` | Storage + business helpers (`apollo_send_message` and `apollo_chat_*`) | **LIVE** |
| `apollo-chat/templates/chat.php` | Blank-canvas `/mensagens` markup + `window.ApolloChat` config | **LIVE** |
| `apollo-chat/templates/template-parts/chat/styles.php` | Extra CSS include from `chat.php` | **LIVE** |
| `apollo-chat/templates/template-parts/chat/scripts.php` | GSAP motion (loads after `chat.js`) | **LIVE** decorative |
| `apollo-chat/assets/js/chat.js` | Polling client, `fetch` to `apollo/v1/chat/*` | **LIVE** |
| `apollo-chat/assets/js/apollo-gsap-text-fx.js` | Text FX helper; also enqueued by `apollo-login` on non-login pages | **LIVE** decorative |
| `apollo-chat/assets/css/chat.css` | Screen CSS | **LIVE** |
| `apollo-chat/assets/css/chat-premium.css` | Extra CSS file | **UNKNOWN** — not referenced from `chat.php` |
| `apollo-chat/uninstall.php` | Drops 7 of 9 tables + `apollo_chat_%` options | uninstall-only |
| `apollo-chat/composer.json` | Declares PSR-4; runtime uses the `spl_autoload_register` in the bootstrap, not Composer | **DEAD** for WP load |
| `apollo-chat/correct.md` | Broken PHPCS PowerShell dump | **DEAD** |
| `apollo-chat/phpcs-logs/*.json` | 2026-02-12 VIP-Go PHPCS snapshots | **DEAD** |

No `_sandbox/`, no PHPUnit, no websocket client, no `wp_ajax_*` handler, no `register_post_type` / `register_taxonomy` / `register_meta` inside `apollo-chat`.

### 1.2 Autoload / boot

1. WordPress loads `apollo-chat.php`.
2. Autoload maps `Apollo\Chat\X` → `src/X.php` (`Plugin`, `Activation`, `Deactivation` only).
3. `plugins_loaded` P15: if `APOLLO_CORE_BOOTSTRAPPED` is missing, the plugin **exits silently** (no REST, no `/mensagens`).
4. Otherwise `functions.php` (global functions, not namespaced) + `Plugin` singleton.

`composer.json` autoload is unused at request time.

### 1.3 Storage (created by Activation, not CPT)

Prefix used in queries is `$wpdb->prefix . 'apollo_'` → tables `{$wpdb->prefix}apollo_chat_*`.

| Table | Created | In `apollo-core` `config/tables.php` / `ApolloTable` | Uninstall drops |
|---|---|---|---|
| `apollo_chat_threads` | yes | yes | yes |
| `apollo_chat_messages` | yes | yes | yes |
| `apollo_chat_participants` | yes | yes | yes |
| `apollo_chat_reactions` | yes | **NO** | **NO** |
| `apollo_chat_typing` | yes | yes | yes |
| `apollo_chat_presence` | yes | yes | yes |
| `apollo_chat_blocks` | yes | yes | yes |
| `apollo_chat_attachments` | yes | yes | yes |
| `apollo_chat_reports` | yes | **NO** | **NO** |

Pins live in dynamic options `apollo_chat_pinned_{thread_id}` (`functions.php:1094`). Those keys are **not** in `apollo-core/config/options.php` (which only lists `apollo_chat_db_version`, `apollo_chat_poll_interval`, `apollo_chat_typing_ttl`). Activation writes `apollo_chat_version`, not `apollo_chat_db_version`.

User meta declared in `MetaRegistry` (`_apollo_chat_status`, `_apollo_chat_last_seen`, `_apollo_chat_preferences`, `_apollo_chat_blocked_users`, `_apollo_chat_muted_threads`) is **not written** by `apollo-chat` runtime. Presence/blocks/mutes use SQL tables / `chat_participants.is_muted`. `_apollo_chat_blocked_users` is listed in `FieldEncryption` (`FieldEncryption.php:45`) — encrypts a key the chat plugin never updates.

### 1.4 Pages / chrome

| Surface | Contract | Actual |
|---|---|---|
| `/mensagens`, `/mensagens/{id}` | Rewrite → `apollo_chat_page` / `apollo_thread_id` → `handle_virtual_pages` → `BlankCanvasTrait::render_blank_canvas` (default variant `'apollo'`, not Apollo+) | **LIVE** |
| Guest | Redirect `/acesso` | **LIVE** (`Plugin.php:104`, `chat.php:18`) |
| Templates left-slider panels | `do_action('apollo/chat/render_*')` | **DEAD** — `apollo-chat` never `add_action`s those hooks |
| Events “Warm-Up” overlay | `apollo-events/.../single/chat.php` | **Separate UI** — not `apollo/v1/chat` |
| Admin `/modera` social chat | Toggles for DM/group/attachments/voice | **UI only** — not read by `Plugin.php` |

`chat.php:76` declares `:root { --height-html; --bg }` — violates the DS rule that `:root` belongs to `core.js`.

---

## 2. never[]

**Finding: no `never[]`, `never_list`, or `denylist` identifier exists** in `apollo-chat` or the searched hubs (`files_allowed` / `write_lock` / `never[` also return zero matches). Marked **UNKNOWN as a named structure**.

Nearest live and declared equivalents:

| Candidate | Where | Load path | Enforced on | Bypass |
|---|---|---|---|---|
| `apollo/chat/can_message` | Declared `ApolloHook::FILTER_CHAT_CAN_MESSAGE` + `apollo-core/config/hooks.php:352` | **Never `apply_filters`'d** | nowhere | Entire send surface |
| `apollo/chat/bad_words` | Hardcoded arrays in `functions.php:1389` and `:1441` | `apply_filters` inside `apollo_chat_filter_bad_words` / `apollo_chat_count_bad_words` | `rest_send_message` only (`Plugin.php:548`) | `apollo_send_message()` direct, `rest_thread_for_context` first message, `apollo_chat_forward_message`, `rest_edit_message` |
| `apollo_chat_blocks` table | `Activation.php:142`, helpers `688–728` | SQL | `rest_send_message` via `apollo_chat_is_blocked_in_thread`; `rest_thread_for_context` pairwise COUNT | `/chat/send` to a new thread with `recipients[]` (block check is **thread-scoped**, not recipient-scoped); system ban `blocker_id=0` (`functions.php:1597`) never joins a participant row |
| `_apollo_chat_blocked_users` user meta | `MetaRegistry.php:2130`, `show_in_rest => true` | WP user meta REST | **Unused** by chat PHP | N/A — shadow store |
| Admin `chat_allowed_types` | `apollo-admin/.../social/chat.php:55` default `jpg,png,webp,pdf,mp3` | Saved under admin `apollo[]` bag | **Not read** by chat | Upload already 403s; allowlist is ornamental |
| HMAC prefix list | `HMACVerifier::PROTECTED_PREFIXES` includes `/apollo/v1/chat/` | `rest_pre_dispatch` P5 **only if** `APOLLO_HMAC_MASTER_KEY` is defined | POST/PUT/PATCH/DELETE | GETs (poll/search/export/older); unconfigured key → no-op |
| PHP `never` | `BlankCanvasTrait` `@return never` (`BlankCanvasTrait.php:49`) | Type/docblock | Exit-after-render | Unrelated to denylist |

`apollo_chat_count_bad_words()` is defined and **never called**.

There is no agent/ops `never[]` in this plugins tree. If a Cowork/COS denylist lives outside `apollorio/a` (MU `apollo-brain`, `wp-content`, or an ops repo), it is **UNKNOWN** here — `mu-plugins/` is not in this workspace (`_inventory/audit/AUDIT-REPORT-APOLLO-UNIVERSE.md` already marks brain as out of disk scope).

---

## 3. COS lock

**Finding: no `COS`, `cos_lock`, `write_lock`, or `files_allowed` symbol** in `_inventory/`, `_inventory/_current/`, `.githooks/`, or plugin PHP/JS. No `_cos/` directory. **UNKNOWN** as a named lock.

Nearest write-lock / allowlist surfaces that *do* interact with chat routes:

### 3.1 Text-only write-lock (LIVE, incomplete)

Documented 2026-08-25 in `apollo-chat.php:25–28` and `Plugin.php:20–36`.

| Route / function | Intended lock | Actual |
|---|---|---|
| `POST /chat/send` → `rest_send_message` | Ignore client `type` / attachment; force `message_type='text'` | **LIVE** (`Plugin.php:522–529`). Does not pass `attachment_id`. |
| `POST /chat/upload` → `rest_upload` | Always 403 | **LIVE** (`Plugin.php:1063–1068`). Still registered so old clients get 403 not 404. |
| `GET /chat/gif-search` → `rest_gif_search` | Always 403 | **LIVE** (`Plugin.php:979–984`). |
| `apollo_send_message()` | Should be text-only | **STILL ACCEPTS** `type` + `attachment_id` (`functions.php:41–42`, insert at `108–110`). |
| `apollo_chat_forward_message()` | — | Writes `type='forwarded'` and copies `attachment_id` (`functions.php:1049–1056`) — **policy bypass**. |
| `rest_thread_for_context` | — | Calls `apollo_send_message` with raw `message` (already sanitized as text field) — no type force, no flood/quota/bad_words. |
| `chat.js` | UI text-only | `pendingGif` is a documented no-op (`chat.js:1259`) but `sendMessage` still *would* POST `type:'gif'` if anything set it (`chat.js:809–811`). Server would ignore type on `/chat/send`. |

Read-only backward compat: `rest_get_thread` / `rest_poll` / `rest_older_messages` still hydrate `attachment` via `apollo_chat_get_attachment`. `chat.js:630` still renders legacy attachment bubbles.

### 3.2 Adverts safety write-lock (LIVE on one route only)

`apollo-adverts/includes/safety-gate.php:186–221` hooks `rest_pre_dispatch` and 403s `/chat/thread-for-context` unless `classified_id` / `context_id` is safety-cleared.

**Gaps:**

- Guard is substring match on the route, then requires a post id. `POST /chat/send` with `recipients:[seller_id]` is **not** guarded.
- `apollo_chat_thread_url()` / `apollo_chat_find_or_create_thread()` create threads in PHP with **no** REST dispatch — gate never runs.
- Marketplace JS (`modal-chat.js:31–36`) opens `/mensagens?to=&context=classified&ref=` and does **not** call the REST route. `chat.js` never reads `location.search` `to`/`context`/`ref`. Deep-link is **dead**; safety REST lock is therefore not on the path the button actually takes.

Comment in `modal-chat.js:9` says the registry route is **GET** `thread-for-context`. Live registration is **POST** (`Plugin.php:358–361`).

### 3.3 HMAC write-lock (CONDITIONAL / UNKNOWN in production)

`HMACVerifier` protects `/apollo/v1/chat/` mutations when `APOLLO_HMAC_MASTER_KEY` is non-empty (`HMACVerifier.php:43–56`, `85–87`, `103–107`). `chat.js` `headers()` sends only `X-WP-Nonce` (`chat.js:104–107`) — **no** `X-Apollo-Signature` / `X-Apollo-Timestamp`.

- If the key **is** set in `wp-config` (not in this repo): every chat mutation from the official UI 401s. **UNKNOWN** whether production defines it (statistics test panel covers `APOLLO_DJ_HMAC_SECRET`, not `APOLLO_HMAC_MASTER_KEY`).
- If the key **is not** set: verifier returns early (`HMACVerifier.php:99–101`). Cookie + `wp_rest` nonce is the only mutation gate.

### 3.4 Flood / quota (LIVE on `/chat/send` only)

`apollo_chat_check_flood` (transient, default 10 / 60s) and `apollo_chat_is_over_quota` (option `apollo_chat_quota_{role}`, default 0 = unlimited) run only in `rest_send_message`. Bypassed by `thread-for-context`, `forward`, and any PHP caller of `apollo_send_message`.

### 3.5 Admin “files allowed” (DEAD)

`apollo-admin` schema (`enable_dm`, `enable_group_chat`, `max_message_length`) and the social chat panel (`chat_dm`, `chat_group`, `chat_max_length`, `chat_attachments`, `chat_voice`, `chat_allowed_types`, …) are **not consumed** by `apollo-chat`. `chat.js` hardcodes `POLL_MS = 3000`; `apollo_chat_poll_interval` option is unused. Attachment/voice toggles default **on** in admin while the plugin policy is text-only — TARGET/ACTUAL split.

### 3.6 Agent / ops surface

No chat-specific agent, MU, or COS lock file in this repo. `apollo-brain` is documented at `mu-plugins/apollo-brain.php` (`14-routing.json:27–44`) and is **absent from disk here** → **UNKNOWN**. No chat hooks are listed on the brain spine (`05-global-architecture.json` ecosystem hook spine).

---

## 4. Routes table

**Auth legend**

- `login` = `permission_callback` closure `is_user_logged_in()` (`Plugin.php:117–119`).
- `cap:manage_options` = admin reports only.
- **Nonce:** cookie REST uses `X-WP-Nonce: wp_rest` minted in `chat.php:31`. `permission_callback` itself does **not** verify nonce (WP cookie auth does via core). HMAC nonce/signature: see §3.3.
- **AJAX:** `AJAX_URL` is assigned in `chat.js:58` and **never used**. No `wp_ajax_*` / `admin_post_*` in the plugin. `chat.php` still ships `ajax_url` in `ApolloChat`.
- **Websockets:** none.
- **Rewrites:** `^mensagens/?$` → `apollo_chat_page=inbox`; `^mensagens/(\d+)/?$` → thread (`Plugin.php:86–87`).

| Method | Path | Cap | Nonce / extra | Handler | File:line | Runtime |
|---|---|---|---|---|---|---|
| GET | `/apollo/v1/chat/threads` | login | `wp_rest` | `rest_get_threads` | `Plugin.php:122–129` / `473–478` | LIVE |
| GET | `/apollo/v1/chat/threads/(?P<id>\d+)` | login | `wp_rest` | `rest_get_thread` | `132–139` / `480–512` | LIVE; meta **no membership check** |
| POST | `/apollo/v1/chat/send` | login | `wp_rest` (+ HMAC if key) | `rest_send_message` | `142–149` / `514–585` | LIVE canonical send |
| GET | `/apollo/v1/chat/unread` | login | `wp_rest` | `rest_unread_count` | `152–159` / `587–595` | LIVE |
| GET | `/apollo/v1/chat/poll` | login | `wp_rest` | `rest_poll` | `162–169` / `597–701` | LIVE 3s client |
| GET | `/apollo/v1/chat/more` | login | `wp_rest` | `rest_more_threads` | `172–179` / `703–709` | LIVE |
| DELETE | `/apollo/v1/chat/threads/(?P<id>\d+)` | login | `wp_rest` (+ HMAC if key) | `rest_delete_thread` | `182–189` / `711–716` | LIVE soft-delete own row |
| POST | `/apollo/v1/chat/typing` | login | `wp_rest` (+ HMAC if key) | `rest_typing` | `195–202` / `720–726` | LIVE; **no membership** |
| POST | `/apollo/v1/chat/threads/(?P<id>\d+)/read` | login | `wp_rest` (+ HMAC if key) | `rest_mark_read` | `206–213` / `728–733` | LIVE; update is user-scoped (harmless if not member) |
| PUT | `/apollo/v1/chat/messages/(?P<id>\d+)` | login | `wp_rest` (+ HMAC if key) | `rest_edit_message` | `217–224` / `735–744` | LIVE; sender + 15 min |
| DELETE | `/apollo/v1/chat/messages/(?P<id>\d+)` | login | `wp_rest` (+ HMAC if key) | `rest_delete_message` | `228–235` / `746–751` | LIVE; sender only |
| POST | `/apollo/v1/chat/messages/(?P<id>\d+)/react` | login | `wp_rest` (+ HMAC if key) | `rest_react_message` | `239–246` / `753–762` | LIVE; **no membership** |
| GET | `/apollo/v1/chat/search` | login | `wp_rest` | `rest_search` | `250–257` / `764–771` | LIVE; participant-scoped LIKE |
| POST | `/apollo/v1/chat/block/(?P<user_id>\d+)` | login | `wp_rest` (+ HMAC if key) | `rest_block_user` | `261–268` / `773–777` | LIVE; no target validation |
| DELETE | `/apollo/v1/chat/block/(?P<user_id>\d+)` | login | `wp_rest` (+ HMAC if key) | `rest_unblock_user` | `270–277` / `779–783` | LIVE |
| POST | `/apollo/v1/chat/presence` | login | `wp_rest` (+ HMAC if key) | `rest_presence` | `281–288` / `785–789` | LIVE table heartbeat |
| GET | `/apollo/v1/chat/threads/(?P<id>\d+)/older` | login | `wp_rest` | `rest_older_messages` | `292–299` / `791–811` | LIVE; membership checked |
| GET/POST/DELETE | `/apollo/v1/chat/threads/(?P<id>\d+)/members` | login | `wp_rest` (+ HMAC if key) | `rest_thread_members` | `303–310` / `813–836` | LIVE; admin role for add/remove-other |
| POST | `/apollo/v1/chat/threads/(?P<id>\d+)/mute` | login | `wp_rest` (+ HMAC if key) | `rest_mute_thread` | `314–321` / `839–851` | LIVE participant row |
| POST | `/apollo/v1/chat/messages/(?P<id>\d+)/forward` | login | `wp_rest` (+ HMAC if key) | `rest_forward_message` | `325–332` / `854–875` | LIVE; target membership only |
| POST/DELETE | `/apollo/v1/chat/threads/(?P<id>\d+)/pin` | login | `wp_rest` (+ HMAC if key) | `rest_pin_message` | `336–343` / `878–898` | LIVE; membership checked |
| GET | `/apollo/v1/chat/threads/(?P<id>\d+)/pinned` | login | `wp_rest` | `rest_get_pinned` | `347–354` / `900–905` | LIVE |
| POST | `/apollo/v1/chat/thread-for-context` | login | `wp_rest` (+ HMAC if key); adverts safety if classified id present | `rest_thread_for_context` | `358–384` / `912–970` | LIVE |
| GET | `/apollo/v1/chat/gif-search` | login | `wp_rest` | `rest_gif_search` | `387–408` / `979–985` | **LIVE 403 stub** |
| POST | `/apollo/v1/chat/messages/(?P<id>\d+)/report` | login | `wp_rest` (+ HMAC if key) | `rest_report_message` | `412–419` / `990–1001` | LIVE; **no membership** |
| GET | `/apollo/v1/chat/threads/(?P<id>\d+)/export` | login | `wp_rest` | `rest_export_thread` | `423–430` / `1004–1022` | LIVE; CSV includes `user_login` |
| GET | `/apollo/v1/chat/reports` | `manage_options` | `wp_rest` | `rest_get_reports` | `434–443` / `1025–1032` | LIVE admin |
| POST | `/apollo/v1/chat/reports/(?P<id>\d+)/action` | `manage_options` | `wp_rest` (+ HMAC if key) | `rest_resolve_report` | `447–456` / `1035–1048` | LIVE admin |
| POST | `/apollo/v1/chat/upload` | login | `wp_rest` (+ HMAC if key) | `rest_upload` | `460–467` / `1063–1069` | **LIVE 403 stub** |

### 4.1 Ghost routes (documented or called, **not registered**)

| Caller | Method / path | Evidence |
|---|---|---|
| `apollo-templates/.../panel-chat-inbox.php:105,146` | GET/POST `apollo/v1/chat/{id}/messages` | Fetches `REST + id + '/messages'`. **404.** |
| `apollo-pane-engine/includes/section-renderer.php:705,755` | GET/POST `apollo/v1/chat/threads/{id}/messages` | Internal fetch + htmx POST. **404.** |
| `apollo-templates/templates/page-test.php:1135–1243` | Lists `/messages`, `/online`, `/block` (no id), `/unblock`, `/mute`, `/unmute`; POST on `/threads`; PUT on `/presence` | Catalog drift vs `Plugin.php` |
| Registry `09-plugins/apollo-chat.json:110` | Claims `/chat/send` is “used alongside `/chat/threads/{id}/messages`” | That sibling route does not exist |

### 4.2 Adjacent routes chat.js depends on

| Method | Path | Cap | Handler | File:line |
|---|---|---|---|---|
| GET | `/apollo/v1/users?search=&per_page=10` | `check_can_view_directory` = `is_user_logged_in()` | `UsersController::get_users_directory` | `apollo-users/src/API/UsersController.php:156–186` |

Chat explicitly avoids `wp/v2/users` (`chat.php:32–37`, `chat.js:64`).

---

## 5. Data flow

### 5.1 Canonical send (LIVE)

```
chat.js sendMessage()
  → POST /wp-json/apollo/v1/chat  + /send
  → headers: X-WP-Nonce (no HMAC)
  → Plugin::rest_send_message
       flood → quota → bad_words → is_blocked_in_thread (if thread_id)
       type forced 'text'
  → apollo_send_message()
       if thread_id: must be participant
       else: insert thread + participants from recipients[]
       insert chat_messages (wp_kses_post)
       bump unread, last_message_*
       apollo_create_notification(...) if function exists   ← LIVE in-process
       do_action('apollo/chat/message_sent', $msg_id, $thread_id, $sender_id)
  → apollo_chat_maybe_notify_by_email()                    ← LIVE (presence table + opt-out meta apollo_chat_email_notify)
  → 201 { thread_id, success }
```

`chat.js` then treats `resp.thread_id` as if it were a message id (`chat.js:864`) — client bug, not a security issue.

New-thread modal: same `/send` with `recipients`, `is_group` (`chat.js:1886–1894`). No recipient-level block check.

### 5.2 Read / poll (LIVE)

```
GET /threads            → apollo_get_user_threads (participant join)
GET /threads/{id}       → apollo_get_thread_messages (membership) + apollo_chat_get_thread_meta (NO membership)
GET /poll?since&thread_id
       new messages: participant join, not own, not deleted
       typing: apollo_chat_get_typing(thread_id) — no membership
       read_receipts: participant rows of that thread — no membership
       presence heartbeat
GET /threads/{id}/older → membership
```

Soft-deleted messages are **included** in `apollo_get_thread_messages` (no `is_deleted=0` filter, `functions.php:253–259`) so the client can show an empty tombstone.

### 5.3 Integration send (PARTIAL / DEAD)

| Path | Status |
|---|---|
| Adverts button → `/mensagens?to=` | **DEAD** — `chat.js` ignores query string |
| Adverts button `data-rest-url` thread-for-context | Minted in PHP (`integrations.php:341`) but `modal-chat.js` never POSTs it |
| `POST /chat/thread-for-context` | **LIVE** if something calls it; safety-gated only with classified/context id |
| `apollo_chat_thread_url()` | **LIVE PHP** — find-or-create as a side effect of URL generation |
| Pane / templates `/messages` | **DEAD** ghost routes |
| Events Warm-Up | **Other product** — local overlay, not this plugin |

### 5.4 Hook fan-out from `apollo/chat/message_sent`

Fired as `(msg_id, thread_id, sender_id)` (`functions.php:176`).

| Listener | Signature | Result |
|---|---|---|
| `apollo-notif` `on_message_sent` | `(msg_id, thread_id, sender_id)` match | Calls `apollo_get_thread_participants()` — **function does not exist** → returns immediately. **DEAD.** Also would link `/chat/{id}` not `/mensagens/{id}`. In-process `apollo_create_notification` in `apollo_send_message` is the live notif path. |
| `apollo-email` `onChatMessage` | `(thread_id, sender_id, recipient_id)` | **Miswired.** Receives `(msg_id, thread_id, sender_id)`. Looks up `_apollo_chat_status` (unused by chat heartbeat). Live email is `apollo_chat_maybe_notify_by_email`. |
| `apollo-users` `MatchmakingEngine::on_signal_chat` | `(msg_id, thread_id, sender_id)` match | **LIVE** — dirties pairs from `apollo_chat_participants`. |
| `apollo-statistics` `on_chat_message` | `(message_id, user_id)` with `accepted_args=2` | Records `chat_message` against **thread_id as user_id**. **Mis-attributed.** |
| `apollo-statistics` `on_chat_thread` | `apollo/chat/thread_created` | **DEAD** — action never fired. |

Declared filter `apollo/chat/can_message` is never applied.

### 5.5 Cron

Hourly `apollo_chat_cleanup` (`Plugin.php:76–80`, `1072–1085`): delete typing >10s, presence >5min, then `apollo_chat_cleanup_old_messages` if `apollo_chat_retention_days > 0` (default 0 = off).

---

## 6. Coupling

| From → to | Edge | Evidence |
|---|---|---|
| `apollo-chat` → `apollo-core` | Boot gate, `BlankCanvasTrait`, `apollo_render_document_open`, table/meta/option/hook **declarations**, HMAC verifier, `apollo_time_ago` / avatar helpers | `apollo-chat.php:53`, `Plugin.php:48`, `chat.php:108` |
| `apollo-chat` → `apollo-notif` | Optional `apollo_create_notification` inside send | `functions.php:150` |
| `apollo-chat` → `apollo-email` | Optional `apollo_queue_email` in `apollo_chat_maybe_notify_by_email` | `functions.php:1670` |
| `apollo-chat` → `apollo-users` | Client GET `/apollo/v1/users`; matchmaking listens to send hook | `chat.js:1838`, `MatchmakingEngine.php:75` |
| `apollo-chat` → `apollo-membership` | Optional badge HTML | `functions.php:1297`, `Plugin.php:1120` |
| `apollo-core` → `apollo-chat` | Meta/table/option registry ownership; HMAC prefix | `MetaRegistry.php:2097+`, `HMACVerifier.php:45` |
| `apollo-login` → `apollo-chat` | Enqueues `apollo-gsap-text-fx.js` (asset only) | `apollo-login/templates/parts/auth-scripts.php:21–23` |
| `apollo-adverts` → `apollo-chat` | Chat button + safety `rest_pre_dispatch` | `integrations.php:307–341`, `safety-gate.php:186` |
| `apollo-templates` → `apollo-chat` | Panels fire hooks chat does not handle; ghost `/messages` | `panel-chat.php:28`, `panel-chat-inbox.php:60,105` |
| `apollo-pane-engine` → `apollo-chat` | Inbox GET `/chat/threads` **works**; thread GET/POST `/messages` **does not** | `section-renderer.php:501,705,755` |
| `apollo-admin` → `apollo-chat` | Settings schema + social panel; runtime ignores | `Schema/apollo-chat.php`, `social/chat.php` |
| `apollo-email` → chat meta | Reads `_apollo_chat_status` | `Plugin.php:1081` — unused by chat presence |
| `apollo-users` / `apollo-statistics` | Direct SQL on chat tables | `MatchmakingEngine.php:137–138,561` |
| `apollo-brain` (MU) | Boot before plugins | **UNKNOWN** — file not in this repo |
| PARTY_MODEL | Chat has block/mute only; no follow/like | `ri-user-unfollow-line` is an icon name for **block**, not a follow API |

**Hook-name compliance:** the one emitted action is `apollo/chat/message_sent` (correct `apollo/{plugin}/{action}`). Cron hook `apollo_chat_cleanup` is underscored WP-cron style, not the ecosystem pattern. Filters `apollo/chat/flood_*` and `apollo/chat/bad_words` match the pattern.

**REST compliance:** all chat routes are `apollo/v1`. Chat does not register `wp/v2`. User meta `show_in_rest` on `_apollo_chat_*` still exposes those keys on **core user REST** if that namespace is reachable.

**CPT/meta compliance:** chat does not register CPT/tax/meta itself. It **does** create custom tables and ad-hoc options (`apollo_chat_pinned_*`, `apollo_chat_version`, flood transients). Pins/options are outside MetaRegistry.

**`$apollo_rule` DOM:** `chat.php:130` `data-a-user="{user_id}"` and `window.ApolloChat.user_id` / `user_name`. Templates panels repeat `data-a-user`. Nonce is in the JSON config (good vs `data-*`), uid is in both DOM and JS. Email is not printed on the canvas; export CSV includes `user_login`.

---

## 7. Findings (P0–P3)

| ID | Sev | Finding | Evidence |
|---|---|---|---|
| F1 | **P0** | Adverts safety write-lock is bypassable. Any logged-in user can `POST /chat/send` `{recipients:[seller], message}` or call `apollo_chat_find_or_create_thread` in PHP. Gate only wraps `thread-for-context` when a classified id is present. | `safety-gate.php:192–201`; `Plugin.php:142–149`; `functions.php:1151` |
| F2 | **P1** | `GET /chat/threads/{id}` leaks thread meta (subject, group, **all participant ids/names/avatars/online**) to any logged-in non-member. Messages array is empty (membership on `apollo_get_thread_messages`) but `apollo_chat_get_thread_meta` has no check. Returns HTTP 200. | `Plugin.php:480–511`; `functions.php:237–251` vs `937–975` |
| F3 | **P1** | Membership not checked on typing write, poll typing/receipts for arbitrary `thread_id`, react, report, or forward **source** message. Forward also copies attachments and sets `type=forwarded`. | `Plugin.php:720–726,644–682,753–762,990–996,854–864`; `functions.php:1016–1056` |
| F4 | **P1** | Text-only lock is not in `apollo_send_message`. Any in-process caller (forward, future plugin, WP-CLI) can insert non-text + `attachment_id`. Upload/GIF 403s do not close this. | `functions.php:48–49,108–123`; `1049–1056` |
| F5 | **P1** | Declared deny-gate `apollo/chat/can_message` is never applied. Closest thing this repo has to a `never[]` enforcement point is unused. | `ApolloHook.php:133`; `hooks.php:352`; no `apply_filters` in `apollo-chat` |
| F6 | **P1** | Marketplace / templates / pane chat UIs do not hit the live send path. `?to=` ignored; `/chat/{id}/messages` and `/chat/threads/{id}/messages` unregistered. Official `/mensagens` + `/chat/send` is the only working UI. | `modal-chat.js:31`; `panel-chat-inbox.php:105,146`; `section-renderer.php:705,755`; `chat.js` no `location.search` |
| F7 | **P1** | HMAC vs client mismatch. If `APOLLO_HMAC_MASTER_KEY` is set, official `chat.js` mutations 401. If unset, mutations are login+cookie nonce only. Production key: **UNKNOWN**. | `HMACVerifier.php:85–134`; `chat.js:104–116` |
| F8 | **P2** | Dual / dead block stores. Live: `chat_blocks`. Shadow: `_apollo_chat_blocked_users` (`show_in_rest`, encrypted). System ban `blocker_id=0` cannot match `is_blocked_in_thread` (join requires blocker as participant). | `functions.php:688–728,1593–1600`; `MetaRegistry.php:2130`; `FieldEncryption.php:45` |
| F9 | **P2** | Hook contract drift: email listener wrong arity; notif helper missing; statistics records thread_id as user; `thread_created` never emitted; `render_*` hooks never handled. Risk of **double or zero** email/notif depending on which path is noticed. | `functions.php:176`; `apollo-email/.../Plugin.php:1072`; `apollo-notif/.../Plugin.php:587–588`; `HookCollector.php:57–58,195` |
| F10 | **P2** | `$apollo_rule` / DS: `data-a-user` + `:root` tokens in `chat.php`; uid/name in `ApolloChat`. Admin attachment/voice defaults contradict text-only policy. | `chat.php:76–83,130`; `ApolloChat` encode `59–69`; `social/chat.php:36–55` |
| F11 | **P2** | Schema drift: `chat_reactions` / `chat_reports` missing from core table map and uninstall; pin options unregistered; Activation option name ≠ core `apollo_chat_db_version`. | `Activation.php:103–189`; `uninstall.php:19–27`; `config/tables.php:162–186`; `options.php:94–153` |
| F12 | **P2** | No max length / group-size / DM-disable enforcement despite admin fields. Export CSV leaks `user_login`. Search is unescaped `LIKE` (prepared, still broad). | `Schema/apollo-chat.php`; `functions.php:657–681,1776–1825` |
| F13 | **P2** | `rest_send_message` does not apply recipient-level block when creating a thread. Blocked users can be opened via `recipients[]`. | `Plugin.php:550–567` vs `551` (thread-only) |
| F14 | **P3** | Registry/docs stale: chapter version `2.0.1` still advertises file/voice; `page-test.php` ghost catalog; TBD-002 cites `Plugin.php` L1191/1193 — file ends at L1146; leftover `pendingGif` / lightbox / `AJAX_URL`. | `09-plugins/apollo-chat.json:13–26`; `17-backlog.json:29–37`; `page-test.php:1135+`; `chat.js:58,82` |
| F15 | **P3** | `never[]` / COS / `files_allowed` **absent**. Do not invent a COS module. Any ops lock lives outside this tree (**UNKNOWN**). | repo-wide grep; no `_cos/` |

TBD-002 as currently written is **not reproducible** on this file (stale line numbers). Current raw-SQL spots that *do* interpolate trusted prefixes or imploded int ids: `cleanup_ephemeral` (`Plugin.php:1080–1082`), `apollo_chat_get_pinned_messages` IN-list (`functions.php:1326–1333`), `INFORMATION_SCHEMA` context column probe (`functions.php:1207–1212`). Treat as P3 hygiene unless a user-controlled identifier is later shown in those strings.

---

## 8. ACTUAL vs TARGET

| Source | TARGET (claimed) | ACTUAL (this tree, 2026-09-11) |
|---|---|---|
| Plugin header / class docblock `2.0.2` | Text-only; `/upload` + `/gif-search` 403; `/send` ignores client type | **Mostly true** for `/chat/send` + those two stubs. **False** for `apollo_send_message` / forward. |
| Registry `09-plugins/apollo-chat.json` `2.0.1` | File/voice attachments, voice_messages, “send alongside `/threads/{id}/messages`” | **False.** No voice recorder. No `/messages` collection route. Version behind live `2.0.2`. |
| `apollo-admin` social chat panel | Attachments/voice on; `chat_allowed_types` allowlist | **UI TARGET only.** Runtime ignores; upload 403. Closest `files_allowed` stand-in is this dead field. |
| Templates / pane / page-test | Nested `/threads/{id}/messages`, `/online`, `/block` without id | **Ghost TARGET.** Live is flat `/chat/send`, `/chat/block/{user_id}`, no `/online`. |
| `ApolloHook` + `hooks.php` | `apollo/chat/can_message`, `thread_created`, `message_sent(msg_id, thread_id)` | `can_message` unused; `thread_created` unused; `message_sent` fires **3** args; listeners disagree. |
| MetaRegistry chat user meta | Presence, prefs, blocked list, muted threads as user meta (REST) | **Shadow TARGET.** Runtime uses SQL tables. |
| Adverts modal comment | GET `thread-for-context`; query-string handoff | POST only; query-string unused by `chat.js`. |
| `PLUGIN-DEPLOY-MAP` TBD-002 | Raw SQL at `Plugin.php` L1191/1193 | **Stale.** File is 1146 lines. |
| COS / `never[]` / `files_allowed` | Named lock + denylist + allowlist interacting with chat + agents | **Not in this repo.** UNKNOWN outside. |
| PARTY_MODEL | WOW only; no follow/like | **Honoured** inside `apollo-chat` (block ≠ follow). |
| CPT/tax/meta via core only | No local `register_*` | **Honoured.** Custom tables/options still owned by the plugin. |
| REST `apollo/v1` only | No `wp/v2` for `apollo_*` | Chat routes comply. Chat **user meta** is `show_in_rest` on core user routes. |

---

## 9. Recommended next verify-only steps (no implementation)

1. **Confirm COS/never[] off-tree.** Read-only inspect production `wp-content/mu-plugins/apollo-brain.php` and any ops repo for `never`, `files_allowed`, `write_lock`, `COS`. If absent there too, close F15 as “does not exist” rather than “not loaded.”
2. **HMAC production fact.** Check live `wp-config` / env for `APOLLO_HMAC_MASTER_KEY` without printing the value. Then one authenticated `POST /wp-json/apollo/v1/chat/presence` from `/mensagens` (browser Network): 200 ⇒ key unset (lock dead); 401 `hmac_missing` ⇒ official UI is broken (F7).
3. **Safety-gate probe (authorized staging).** After **not** clearing an advert safety gate: `POST /chat/thread-for-context` (expect 403) vs `POST /chat/send` with that seller in `recipients` (F1). Do not run this against production members.
4. **IDOR probe (own two test accounts).** Account A creates a thread; account B `GET /chat/threads/{A's id}` and `GET /chat/poll?thread_id={A's id}`. Confirm meta/typing/receipts leak (F2/F3).
5. **Forward / attachment replay.** Against a pre-policy row with `attachment_id > 0`, `POST /chat/messages/{id}/forward` to a thread B belongs to; `GET` the target and inspect `message_type` + `attachment` (F4).
6. **UI surface matrix.** Load `/mensagens` (expect working poll/send), `/casa` pane chat thread (expect send 404), templates chat-inbox (expect 404), advert “INICIAR CHAT” (expect inbox without prefilled recipient). Screenshot Network tab only — no code changes.
7. **Hook arity.** In a WP-debug staging log, send one message and count `apollo_create_notification` + `apollo_queue_email` + email `onChatMessage` + notif `on_message_sent`. Expect in-process notif + `maybe_notify_by_email`; email/notif hook listeners no-op or mis-fire (F9).
8. **Registry rebuild (verify-only).** `cd _inventory/registry && node build.js` and diff `09-plugins/apollo-chat` version/`provides` against `APOLLO_CHAT_VERSION` — do not “fix” the chapter in the same motion as a product patch unless asked.
9. **TBD-002 re-audit.** Re-scan `Plugin.php` + `functions.php` for unprepared SQL; update backlog status as verify, not as a silent code fix.
10. **Do not** implement `never[]`, COS, or `files_allowed` in this plugins tree until the off-tree search in step 1 returns a real owner. Inventing a COS plugin would violate the “absent path = UNKNOWN / do not invent plugins” rule.

---

## Appendix — live vs dead cheat sheet

| Piece | Verdict |
|---|---|
| `/mensagens` + `chat.js` + `POST /chat/send` + `GET /chat/poll` | LIVE product |
| `/chat/upload`, `/chat/gif-search` | LIVE 403 locks |
| `apollo_send_message` attachment/type columns | LIVE write path, policy-dead |
| `pendingGif`, `AJAX_URL`, `chat-premium.css`, `correct.md` | DEAD leftovers |
| `apollo/chat/render_*`, `/chat/.../messages`, `?to=` handoff | DEAD callers |
| `apollo/chat/can_message`, `thread_created`, `apollo_get_thread_participants`, `apollo_chat_count_bad_words` | DEAD declarations |
| `_apollo_chat_*` user meta + admin attachment toggles | DEAD stores / UI |
| HMAC on chat | UNKNOWN (config not in repo) |
| `never[]` / COS / `files_allowed` | UNKNOWN / absent |
| `apollo-brain` MU | UNKNOWN (not in this repo) |
