# Apollo Chat — live `/mensagens` send HTML-instead-of-JSON + missing composer

**Date:** 2026-09-11  
**Constraint:** diagnose only. No product PHP/JS/CSS patches.  
**Companion:** `_inventory/audit/APOLLO-CHAT-COS-LOCK-AUDIT.md`  
**Live evidence:** `chat.js?v=2.0.12:971` on `apollo.rio.br`  
`SyntaxError: Unexpected token '<', "<br />\n<b>"... is not valid JSON`  
`sendMessage @ chat.js?v=2.0.12:971`  
`127.0.0.1:7754/ingest` — local beacon, ignore.

Repo on disk is still `APOLLO_CHAT_VERSION = 2.0.2`. Production JS query is `v=2.0.12` (`Last-Modified: 2026-09-06`). Fetched live `chat.js` — `sendMessage` catch is **line 971**, same string `[ApolloChat] send:`. Repo catch is line 872. Logic matches; live file is longer (`knownMsgIds`, `animateMsgEnter`).

---

## 1. Client: what `sendMessage` actually does

Live `https://apollo.rio.br/wp-content/plugins/apollo-chat/assets/js/chat.js` (also `apollo-chat/assets/js/chat.js` in repo):

| Step | Live line | Repo line | What |
|---|---|---|---|
| Guard | 894–898 | 797–802 | Requires `.ac-compose-input`, non-empty text (or leftover `pendingGif`), `activeThreadId` |
| Body | 900–907 | 804–811 | `{ thread_id, message [, reply_to_id] [, type:'gif'] }` |
| Optimistic paint | 909–941 | 813–842 | Appends bubble **before** fetch — `"oiii"` on screen does **not** prove the POST succeeded |
| Fetch | 953–956 | 854–857 | `api('/send', { method:'POST', body: JSON.stringify(msgBody) })` |
| Catch | 969–971 | 870–872 | toast + `console.error('[ApolloChat] send:', e)` — **this is the reported stack** |

`api()` (`chat.js` repo 110–121 / live same helper):

```javascript
const url = REST + path;           // CFG.rest_url path + '/send'
                                   // default '/wp-json/apollo/v1/chat' + '/send'
fetch(url, { credentials:'same-origin', headers: { 'X-WP-Nonce': NONCE, 'Content-Type':'application/json' }, ...opts })
if (!res.ok) { await res.json().catch(() => ({})); throw Error(err.error || HTTP status) }
return res.json();                 // ← SyntaxError only if status is 2xx AND body is not JSON
```

**Status implication:** a non-JSON **4xx/5xx** would log `HTTP 401/500`, not `Unexpected token '<'`. The live error means the response was **`res.ok` (2xx)** with a body starting with PHP HTML.

`REST` is path-only from `window.ApolloChat.rest_url` (`chat.php` `rest_url('apollo/v1/chat')`). Full URL: `https://apollo.rio.br/wp-json/apollo/v1/chat/send`.

Headers: cookie + `X-WP-Nonce` only. No HMAC.

---

## 2. HTML prefix `<br />\n<b>`

That is PHP `display_errors` HTML, not WordPress REST JSON and not `wp_die()`’s default markup.

Typical first bytes:

```
<br />
<b>Fatal error</b>: Uncaught TypeError: ...
```

or `<b>Warning</b>` / `<b>Notice</b>` / `<b>Deprecated</b>`.

If it is a **Warning** prepended to real JSON, `res.json()` still throws the same SyntaxError. If it is a **Fatal**, there is no JSON after the HTML; HTTP status can still be 200/201 because WP REST already committed the status before the fatal.

**Confirm on the next send:** DevTools → Network → `chat/send` → Response. Copy the `<b>…</b>` title and the file:line PHP prints.

---

## 3. POST `/apollo/v1/chat/send` — every HTML leak

Order in `Plugin::rest_send_message` (`src/Plugin.php:514–585`):

1. Read params, force `$msg_type = 'text'`
2. Empty check → JSON 400 (not HTML)
3. `apollo_chat_check_flood` → JSON 429
4. `apollo_chat_is_over_quota` → JSON 403
5. `apollo_chat_filter_bad_words`
6. `apollo_chat_is_blocked_in_thread` → JSON 403
7. **`apollo_send_message()`** (`includes/functions.php:27–178`)
   - `$wpdb` insert thread/participants/message
   - optional `apollo_create_notification()` (`apollo-notif/includes/functions.php:31`) — signature matches; table miss = `$wpdb` warning **if** `display_errors`, not a TypeError
   - **`do_action('apollo/chat/message_sent', $msg_id, $thread_id, $sender_id)`** (`functions.php:176`)
8. `apollo_chat_set_typing(..., false)`
9. **`apollo_chat_maybe_notify_by_email($result, $sender_id, $preview)`** (`Plugin.php:574` → `functions.php:1625–1698`)
10. `WP_REST_Response` JSON 201

HMAC (`HMACVerifier`) and adverts safety-gate return `WP_Error` JSON, not `<br /><b>`. They do not match this symptom.

### 3.1 Most likely fatal — `apollo_queue_email` arity/type

`apollo-email/includes/functions.php:54`:

```php
function apollo_queue_email( string $to, string $subject, string $template, array $data = array(), int $priority = 5 ): int|false
```

Every in-plugin caller (`apollo-email/src/Plugin.php:817, 989, 1092, 1152`) passes **positional strings**.

Chat (`includes/functions.php:1670–1685`) does:

```php
if (function_exists('apollo_queue_email')) {
    apollo_queue_email(
        array(
            'to'       => $r['user_email'],
            'subject'  => $subject,
            'template' => 'chat_notification',
            'vars'     => array(...),
            'priority' => 5,
        )
    );
}
```

PHP 8.1+ (plugin `Requires PHP: 8.1`):

`TypeError: apollo_queue_email(): Argument #1 ($to) must be of type string, array given`

HTML:

```
<br />
<b>Fatal error</b>: Uncaught TypeError: apollo_queue_email(): Argument #1 ($to) must be of type string, array given in .../apollo-chat/includes/functions.php:1671
```

**When it runs:** only if `apollo-email` is loaded **and** `maybe_notify` found at least one unmuted, non-opted-out recipient with email whose `chat_presence.last_seen` is NULL or older than 5 minutes (`functions.php:1631–1646`).

**Why messages still show:** `apollo_send_message()` already committed the row **before** this call (`Plugin.php:555` then `:574`). Optimistic UI also paints first. Persistence + failed JSON is consistent with this fault.

**When it would not run:** all other participants look “online” in `chat_presence` → early `return` → clean 201. Then this is **not** the HTML source (look at warnings in the Network body).

### 3.2 `apollo/chat/message_sent` listeners — can they fatal?

Fired as `(int $msg_id, int $thread_id, int $sender_id)`.

| Listener | Signature | Fatal on send? | Notes |
|---|---|---|---|
| `apollo-notif` `on_message_sent` | `(int, int, int)` match | **No** | `function_exists('apollo_get_thread_participants')` is false (helper **does not exist**). Returns at `Plugin.php:587–589`. Dead, safe. |
| `apollo-email` `onChatMessage` | `(int $thread_id, int $sender_id, int $recipient_id)` | **No TypeError** | Receives `(msg_id, thread_id, sender_id)`. `get_userdata($thread_id)` as “sender” usually fails → early return. If it proceeds, it calls `apollo_queue_email` **correctly** (strings). `_apollo_chat_status !== 'offline'` also exits. Miswired, not the HTML crash. |
| `apollo-users` `MatchmakingEngine::on_signal_chat` | `(int, int, int)` match | **Unlikely** | `$wpdb->prepare` on participants; `enqueue_dirty(int,int)` is defensive. |
| `apollo-statistics` `on_chat_message` | `(int $message_id, int $user_id)`, `accepted_args=2` | **No** | Records `chat_message` against **thread_id as user_id**. Wrong metric, not a fatal. |

**Conclusion:** prior-audit hook defects are real but **do not explain** `<br />\n<b>` on send. The crash that matches the bytes is `functions.php:1671` after persist.

### 3.3 Other HTML emitters (lower probability)

| Source | Why it could print HTML | Why weaker |
|---|---|---|
| `$wpdb` “table doesn’t exist” with `display_errors` | Warning HTML in front of 201 JSON | Would also break first GET `/threads` |
| `apollo_create_notification` missing table | Same | Inbox/unread already query notif |
| `wp_mail` fallback | Only if `apollo_queue_email` **missing**; then no TypeError | Email plugin is active in this ecosystem |
| `wp_die` / REST permission | HTML or JSON error, usually **not** 2xx | `api()` would throw `HTTP 4xx` |
| Undefined function in a listener | Fatal HTML | Current listeners are guarded or typed ints |
| HMAC / safety-gate | JSON `WP_Error` | Not `<br /><b>` |

---

## 4. Composer: DOM exists; “styled bar” can still be invisible

### 4.1 Where the bar is built

Markup is **server HTML**, not JS:

`apollo-chat/templates/chat.php:203–234`

```html
<div class="ac-compose" style="display:none;">
  <div class="ac-reply-bar">...</div>
  <div class="ac-compose-form">
    <div class="ac-compose-center">
      <textarea class="apollo-textarea ac-compose-input" ...>
      <span class="ac-emoji-trigger">...</span>
    </div>
    <button class="ac-send-btn" type="button">...</button>
  </div>
</div>
```

Shown by `openThread()` (`chat.js` repo 414–418 / live equivalent): `compose.style.display = ''` (clears inline `none`, does **not** set `flex`/`block`). Hidden again by `renderEmptyMain()` (788–790).

Bind: `initCompose()` (2008–2034) — Enter and `.ac-send-btn` click → `sendMessage`.

Styles: `chat.php` requires `template-parts/chat/styles.php`, which **does** link `chat-premium.css` (the `.ac-compose*` / `.ac-send-btn` rules). `$chat_css` pointing at legacy `chat.css` is **assigned and never echoed** (`chat.php:47` vs `:72`). Legacy `chat.css` has **zero** `.ac-*` compose selectors (old `.app-shell` / `.sidebar` names).

### 4.2 Why list can show and the bar cannot

**Hypothesis A — viewport clip (strongest for “styled bar missing” on desktop).**

- `chat.php:76–83` sets `:root { --height-html: 55px }` and `html { padding-top: 55px !important; }` (DS violation; also eats 55px of the canvas).
- `chat-premium.css:2262–2295` `@media (min-width: 768px)`: `.ac-layout { height: 100vh / 100dvh; }` and `.ac-main { display: flex; flex: 1; }`.
- `.apollo-chat-wrap { height: 100%; overflow: hidden }` (`chat-premium.css:29–34`).
- `.ac-compose-form { min-height: 56px }` (`chat-premium.css:1393–1399`) sits at the **bottom** of `.ac-main`.

`100vh + 55px padding` + `overflow: hidden` clips ~the compose strip. `.ac-messages { flex: 1 }` still paints bubbles. Matches: messages (“oiii”) visible, designed input+send gone.

**Hypothesis B — `/mensagens/@roots` never opens a thread (strong if that is the real URL).**

Rewrites are only `^mensagens/?$` and `^mensagens/(\d+)/?$` (`Plugin.php:86–87`). `@roots` does not set `apollo_thread_id`. `INITIAL_TID` stays 0 → `renderEmptyMain()` keeps `.ac-compose { display:none }`. Sidebar previews can still show last text. **But** `sendMessage` requires a visible/filled `.ac-compose-input` and `activeThreadId` — so a send console error means they **did** open a numeric thread (or JS set `activeThreadId`) after load. Treat `@roots` as “inbox URL / handle”, not as the send URL, unless Network shows `thread_id` 0.

**Hypothesis C — mobile `.ac-main { display: none }`.**

`chat-premium.css:790` `.ac-main { display: none }` until desktop MQ or `openThread` sets `main.style.display = 'flex'` **only when `isMobile`** (`chat.js:389–393`). Desktop relies on the MQ. If the MQ fails to apply (stylesheet 404 / old cached CSS), main stays `display:none` — then **messages would also hide**. Weaker if bubbles are clearly in `.ac-main`.

**Hypothesis D — GSAP reveal stuck at opacity 0.**

`scripts.php:229–243` `MutationObserver` calls `gsap.fromTo(compose, { opacity: 0, y: 14 }, { opacity: 1, ... })` with **no** `typeof gsap` guard. CDN script in `chat.php` is `gsap-TextPlugin.chat.min.js` (TextPlugin), not obviously core `gsap`. If `gsap` exists and the tween is interrupted, opacity can remain 0 (DOM present, “not rendering”). If `gsap` is undefined, `fromTo` throws and opacity stays default 1 — would **not** hide the bar.

### 4.3 Not a missing template part

`styles.php` and the compose markup are required from `chat.php`. No conditional PHP omits the bar for `@roots` or guests (guests redirect `/acesso`).

---

## 5. Recommended surgical fix outline (do not implement here)

1. **Send JSON (P0).** In `apollo_chat_maybe_notify_by_email`, call `apollo_queue_email` with the email plugin’s positional contract: `$to, $subject, $template, $data, $priority` — same as `apollo-email/src/Plugin.php:1092`. Optionally wrap **all** post-persist side effects (`do_action`, notify, typing) in `try/catch (\Throwable)` so `/chat/send` always returns JSON after a successful insert.
2. **Verify before coding.** One send with Network open: if the body names `functions.php:1671` / `TypeError` / `apollo_queue_email`, fix (1) only. If the body is a Warning from another file, fix that file — do not “harden `api()`” first.
3. **Composer clip.** Stop declaring `:root` / `html { padding-top: 55px }` in `chat.php`. Size `.ac-layout` to the visible canvas (`100dvh` minus the real topbar, or `height: 100%` on `html.ax-body` without double padding). `openThread` should set `compose.style.display = 'flex'` (or `'block'`), not `''`.
4. **Do not** “fix” notif/email/statistics hook signatures as the send-JSON bug — they are not the HTML prefix. Track them as separate debt from the COS audit.
5. **`/mensagens/@user`.** Either reject non-numeric segments or add an explicit rewrite; today `@roots` is not a thread id.

---

## 6. Verify-only checklist

1. Network `POST /wp-json/apollo/v1/chat/send` → status, `content-type`, **first 20 lines of Response**.
2. Whether the peer is offline in `wp_apollo_chat_presence` (gates `maybe_notify`).
3. Computed style of `.ac-compose`: `display`, `opacity`, `height`, and whether it sits below `document.documentElement.clientHeight`.
4. Computed style of `html` padding-top vs `.ac-layout` height.
5. Request URL path: `/mensagens/{digits}` vs `/mensagens/@roots`.
