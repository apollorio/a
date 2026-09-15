# apollo-whatsapp — webhook security SSOT
Updated: 2026-09-02
Plugin: apollo-whatsapp
Route: POST /wp-json/apollo/v1/wa/webhook
Auth: HMAC-SHA512 fail-closed. Not cookie. Not nonce. Not query token.

Webhook without HMAC is an open door: anyone POSTs `message` and the plugin
replies, processes queue, sends to the group. WAHA already signs. The plugin
MUST require the signature — never "if header present".

## Headers WAHA sends

| Header | Role |
| --- | --- |
| X-Webhook-Hmac | HMAC-SHA512 of raw body (hex) |
| X-Webhook-Hmac-Algorithm | must be `sha512` |
| X-Webhook-Request-Id | idempotency / replay |
| X-Webhook-Timestamp | unix ms window |

```
X-Webhook-Hmac = hex( HMAC_SHA512( hmac.key, raw_body ) )
```

Session webhook config (preferred over global env):

```json
{
  "url": "https://apollo.rio.br/wp-json/apollo/v1/wa/webhook",
  "events": [
    "session.status",
    "message",
    "message.any",
    "message.reaction",
    "group.v2.participants"
  ],
  "hmac": { "key": "<same as option apollo_wa_hmac_key>" }
}
```

Global env `WHATSAPP_HOOK_HMAC_KEY` is fallback only. Apollo stores per-session
key in option `apollo_wa_hmac_key` (autoload false).

## WP pipeline (strict order)

permission_callback = `apollo_wa_verify_webhook`. Not `is_user_logged_in`.

1. HTTPS in production. LocalWP lab allowed.
2. Raw body: `file_get_contents('php://input')`. JSON parse AFTER verify.
3. Missing `X-Webhook-Hmac` → 401, log, stop.
4. Algorithm allowlist: only `sha512`. Else 401.
5. `hash_hmac('sha512', $raw, $secret)` vs header via `hash_equals`.
6. `|now_ms - X-Webhook-Timestamp| > 300000` → 401 replay.
7. Transient `apollo_wa_hook_{Request-Id}` TTL 10 min. Repeat → 200 empty.
8. `payload.session` must equal option `apollo_wa_session`. Else drop.
9. Body > 256 KB → 413.
10. Then switch `event`. No eval. No raw SQL from body.

Secret: `wp_generate_password(48, true, true)` on activate / Tela 1.
Never JS, DOM, git, access log.

## Outbound WP → WAHA

- Every `wp_remote_post` sends `X-Api-Key`.
- Docker: `WAHA_API_KEY=sha512:{hash}`. Plain key lives only in WP option
  `apollo_wa_api_key` (autoload false).
- Prod dashboard off unless needed.
- WAHA bind 127.0.0.1 or private network. Not public internet.
- Media download only from allowlisted WAHA host + API key.

## Does not count as security

| Shortcut | Why it fails |
| --- | --- |
| `?token=` on webhook URL | leaks in logs / CDN / referrer |
| Trust JSON `session: apollo` | anyone forges JSON |
| IP allowlist alone | NAT/Docker moves; HMAC still required |
| Compare HMAC with `===` | use `hash_equals` |
| `permission_callback => __return_true` | core 12-point fail |
| Log body + hmac key | secret leak |

WP nonce is for logged-in admin actions (queue process, pane send).
Webhook is server-to-server. Different door.

## HMAC does not cover

- Replay inside 5 min → Request-Id transient.
- WAHA retry after add already done → Request-Id + queue status.
- SSRF if fetching `hasMedia` URL → allowlist WAHA host only.
- Group flood → 30 events/min/session.
- Admin composer → nonce + `apollo_manage_whatsapp`. Webhook never Approves.

## Status codes

| Case | HTTP |
| --- | --- |
| Auth fail (no/bad hmac, bad algo, stale ts, bad session) | 401 |
| Body too large | 413 |
| Valid replay (already seen Request-Id) | 200 empty |
| Valid new event accepted | 200 |
| Handler error after auth | 500 (WAHA will retry — handlers must be idempotent) |

401 must not leak "key almost correct".

## Audit checklist

- [ ] HMAC key ≥ 32 bytes, generated on activate
- [ ] SHA512 + hash_equals on raw body
- [ ] Timestamp ±5 min
- [ ] Dedupe Request-Id
- [ ] Session allowlist
- [ ] REST route cookie-less, fail-closed
- [ ] Keys never on front
- [ ] X-Api-Key on outbound
- [ ] WAHA not public
- [ ] 401 does not hint key proximity
- [ ] Webhook never changes queue to added/approved
