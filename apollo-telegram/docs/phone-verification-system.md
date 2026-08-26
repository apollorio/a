# Phone Verification System — Complete Reference

## Purpose
Document every file and line that powers the `/telegram` phone verification flow, so it can be replicated for Apollo user registration.

---

## 1. Page Rendering (`/telegram`)

| Step | File | Lines | What it does |
|------|------|-------|--------------|
| 1 | `apollo-telegram.php` | 322-327 | `add_rewrite_rule('^telegram/?$', 'index.php?apollo_telegram_test=1', 'top')` on `init` priority 5 |
| 2 | `apollo-telegram.php` | 335-343 | `query_vars` filter adds `apollo_telegram_test` to recognized query vars |
| 3 | `apollo-telegram.php` | 352-403 | `template_redirect` priority 1: checks query var, serves `views/telegram-phone-support.php`, sets `$apollo_telegram_page_config` with `restUrl`, `nonce`, `botUser` |
| 4 | `apollo-telegram.php` | 407-429 | `apollo/error/should_intercept` filter: prevents error interception for `/telegram` URIs |

**To replicate for registration:** Copy lines 322-327 + 335-343 + 352-403, change route to `/register` and view to a new file.

---

## 2. REST API Endpoints

All registered in one call at `apollo-telegram.php:201`:
```php
add_action('rest_api_init', array(\Apollo\Telegram\API\VerificationController::class, 'register_routes'));
```

| Endpoint | File | Lines | Method | Purpose |
|----------|------|-------|--------|---------|
| `/request-support-verification` | `VerificationController.php` | 26-46 | POST | Create pending verification, return deep_link |
| `/verify-support-code` | `VerificationController.php` | 48-78 | POST | Check 6-digit code against bcrypt hash |
| `/verification-status` | `VerificationController.php` | 80-105 | POST | Poll step/status of a request |
| `/poll-tick` | `VerificationController.php` | 107-130 | POST | Local dev: trigger getUpdates polling |
| `/telegram` | `VerificationController.php` | 132-144 | GET/POST | Legacy debug: generate link key |
| `/broadcast` | `VerificationController.php` | 146-174 | POST | Admin: send message to all chats |

**Permission callbacks:**
- `permission_verify_nonce` (line 177-180): checks `X-Apollo-Telegram-Nonce` header or `nonce` param against `wp_verify_nonce('apollo_telegram_verify')`
- `permission_legacy_debug` (line 182-189): same + requires `WP_DEBUG`
- Broadcast (line 152-154): requires `manage_options` capability

---

## 3. VerificationService (Core Logic)

**File:** `src/Services/VerificationService.php`

| Method | Lines | Purpose |
|--------|-------|---------|
| `install_schema()` | 31-61 | Creates `wp_apollo_telegram_verif` table with 4 indexes |
| `table()` | 63-68 | Returns full table name with prefix |
| `createPending()` | 75-143 | Validates phone, rate limits (IP + phone), inserts DB row, returns `{request_id, deep_link}` |
| `bindContact()` | 150-251 | Receives Telegram contact share, generates 6-digit code, stores bcrypt hash, saves to `apollo_telegram_linked_chats` option |
| `verifyCode()` | 258-363 | Checks code against hash via `wp_check_password()`, marks status=verified |
| `getStatus()` | 370-403 | Returns step/status for polling |
| `cleanup_expired()` | 408-419 | Cron: deletes expired pending rows |

**Database table schema (line 40-58):**
```sql
CREATE TABLE wp_apollo_telegram_verif (
  id BIGINT UNSIGNED AUTO_INCREMENT,
  request_id CHAR(36) NOT NULL UNIQUE,      -- UUID
  web_user_id BIGINT UNSIGNED DEFAULT 0,     -- WP user ID (0 for anonymous)
  phone VARCHAR(20) NOT NULL,                -- normalized digits
  code_hash VARCHAR(255) NULL,               -- bcrypt of 6-digit code
  telegram_chat_id BIGINT UNSIGNED NULL,      -- Telegram chat ID after contact share
  step VARCHAR(32) DEFAULT 'requested',       -- requested | contact_shared | code_delivered | verified
  status VARCHAR(20) DEFAULT 'pending',       -- pending | verified | failed
  verify_attempts TINYINT UNSIGNED DEFAULT 0,
  expires_at DATETIME NOT NULL,              -- 15 minutes from creation
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE,
  PRIMARY KEY (id),
  KEY idx_phone_status_exp (phone, status, expires_at),
  KEY idx_chat_status (telegram_chat_id, status),
  KEY idx_status_exp (status, expires_at)
);
```

---

## 4. Telegram Bot Commands

### StartCommand — `/start {request_id}`
**File:** `src/Telegram/Commands/UserCommands/StartCommand.php`

| Lines | What it does |
|-------|--------------|
| 22-27 | Gets message, chat_id, text |
| 28-33 | Rate limit: 10 requests per 5 minutes per chat |
| 35-38 | Extracts UUID payload from `/start {uuid}` |
| 42-44 | Stores UUID in transient `apollo_tg_start_{chat_id}` (15 min TTL) |
| 46-57 | Builds Telegram keyboard with "Share Contact" button |
| 59-73 | Sends welcome message + contact request button |

### GenericCommand — handles contact share + events
**File:** `src/Telegram/Commands/UserCommands/GenericCommand.php`

| Lines | What it does |
|-------|--------------|
| 23-28 | Gets message, chat_id, text |
| 34-87 | **Contact share handler:** validates phone, resolves request_id from transient, calls `VerificationService::bindContact()`, sends 6-digit code back to user |
| 89-94 | UUID-only text: reminds user to use the contact button |
| 96-107 | **Events intelligence:** calls `EventsBotService::handle()` with `class_exists()` guard (graceful degradation) |
| 109 | Returns empty response if nothing matched |

---

## 5. Front-End UI (`views/telegram-phone-support.php`)

### Structure
| Section | Lines | Purpose |
|---------|-------|---------|
| PHP config injection | 5-14 | Sets `$apollo_telegram_page_config` with REST URL, nonce, bot username |
| CSP nonce | 12-13 | Reads `$GLOBALS['apollo_csp_nonce']` for inline script CSP |
| Apollo Core.js | 29-30 | Loads design system from `https://cdn.apollo.rio.br/v1.0.0/core.js?versao=bb` |
| Config JSON | 31-33 | `window.apolloTelegramConfig = {...}` |
| CSS (design tokens) | 35-567 | Full Apollo design system using CSS variables (`--surface`, `--card`, `--txt-heading`, `--r-pill`, `--ins-shadow`, etc.) |
| Phone input HTML | 581-609 | Country flag selector + phone input + validate button |
| Verification modal | 613-663 | Lightbox with Telegram pre-step overlay + 6-digit code inputs |
| JavaScript | 665-1095 | All client-side logic |

### JavaScript Flow (lines 669-1094)
| Function | Lines | What it does |
|----------|-------|--------------|
| `restPost()` | 779-791 | Generic POST to REST API with nonce header |
| Phone validation | 860-898 | Validates phone, calls `/request-support-verification`, opens modal |
| `openModal()` | 950-967 | Shows lightbox, resets to pre-step muted state |
| Telegram button click | 911-919 | Opens deep link, un-mutes code inputs, starts polling |
| `startPolling()` | 923-941 | Every 5s: calls `/poll-tick` + `/verification-status` |
| Code submission | 1022-1064 | Calls `/verify-support-code`, shows success/error |
| Resend | 1067-1093 | Calls `/request-support-verification` again with same phone |

### Visual Design Tokens (for replication)
The UI uses Apollo's CSS custom properties. To replicate the same style:

```html
<!-- Required: Apollo Core.js provides the design system -->
<script src="https://cdn.apollo.rio.br/v1.0.0/core.js?vers=d98893v&versao=bb"></script>

<!-- Use these CSS variables -->
var(--surface)          /* card background */
var(--card)             /* elevated surface */
var(--bg)               /* page background */
var(--txt-heading)      /* heading text color */
var(--txt-color)        /* body text color */
var(--muted)            /* secondary text */
var(--r-pill)           /* pill border radius */
var(--r)                /* standard radius */
var(--r-lg)             /* large radius */
var(--ins-shadow)       /* inset shadow for depth */
var(--t-ui)             /* UI transition timing */
var(--ease-snappy)      /* snappy easing */
var(--ease)             /* smooth easing */
var(--ff-main)          /* main font family */
var(--ff-heading)       /* heading font family */
var(--ff-mono)          /* monospace font */
var(--fs-h3)            /* heading 3 size */
var(--fs-h4)            /* heading 4 size */
var(--fs-body-sm)       /* small body text */
var(--fs-caption)       /* caption text */
var(--fs-r)             /* font scale ratio */
var(--s-4)              /* spacing level 4 */
var(--z-base)           /* base z-index */
var(--z-pop)            /* popover z-index */
var(--z-modal)          /* modal z-index */
var(--black-1)          /* near-black */
var(--black-4)          /* lighter black */
var(--white-5)          /* 5% white overlay */
var(--white-8)          /* 8% white overlay */
var(--white-10)         /* 10% white overlay */
var(--rgb-primary)      /* primary color as RGB */
var(--rgb-theme)        /* theme color as RGB */
var(--rgb-diff)         /* contrast color as RGB */
var(--accent)           /* accent color */
var(--alert-red)        /* error red */
var(--img-border)       /* image border shadow */
```

---

## 6. Webhook (Production Message Delivery)

| File | Lines | Purpose |
|------|-------|---------|
| `WebhookService.php` | 23-39 | `getSecretToken()` — generates/stores 32-char secret |
| `WebhookService.php` | 44-54 | `validateIncoming()` — checks `X-Telegram-Bot-Api-Secret-Token` header |
| `WebhookService.php` | 61-100 | `registerWebhook()` — calls Telegram `setWebhook` API with secret_token |
| `GetMessage.php` | 23-30 | `checkPermission()` — validates incoming webhook secret |
| `GetMessage.php` | 32-46 | `handle()` — instantiates Telegram, calls `$telegram->handle()` |
| `apollo-telegram.php` | 235-255 | Daily webhook check: registers if missing, retries on failure |

---

## 7. Broadcast System (Admin Notification)

| File | Lines | Purpose |
|------|-------|---------|
| `TelegramService.php` | 221-285 | `broadcastToAll()` — sends message to all known chats with rate limiting |
| `TelegramService.php` | 303-359 | `getAllKnownChats()` — unions 3 sources: linked_chats option, verification table, longman chat table |
| `BroadcastMenu.php` | 41-159 | Admin page: status panel, webhook info, recipient list, broadcast form |
| `BroadcastMenu.php` | 166-196 | `maybeHandleBroadcast()` — POST handler with nonce + capability check |

---

## 8. Rewrite Self-Heal (Why 404 Happened)

| File | Lines | Purpose |
|------|-------|---------|
| `apollo-telegram.php` | 203-219 | Versioned one-shot flush: checks `apollo_telegram_rewrites_ver` option, calls `flush_rewrite_rules(true)` if mismatched |
| `apollo-telegram.php` | 221-233 | Admin notice confirming flush happened |
| `apollo-telegram.php` | 282-284 | Activation hook: adds rewrite rule + flush |
| `apollo-telegram.php` | 309-310 | Deactivation hook: clears cron + flush |

---

## 9. How to Replicate for User Registration

### New files needed:
1. `views/telegram-registration.php` — copy of `telegram-phone-support.php` with additional fields (name, email, password)
2. `src/Services/RegistrationService.php` — extends verification with `wp_insert_user()` call

### Changes to existing files:
| File | What to add |
|------|-------------|
| `apollo-telegram.php` | New rewrite rule for `/register` (copy lines 322-327) |
| `apollo-telegram.php` | New `template_redirect` handler for `/register` (copy lines 352-403) |
| `src/API/VerificationController.php` | New endpoint `/register-user` that calls `wp_insert_user()` after verification |
| `VerificationService.php` | After `verifyCode()` succeeds (line 328), add hook or call to create WP user |

### Flow:
```
1. User visits /register → enters phone + name + email
2. POST /request-support-verification → returns deep_link
3. User opens Telegram, shares contact → bot sends 6-digit code
4. User enters code → POST /verify-support-code → verified
5. POST /register-user → wp_insert_user() with verified phone
6. User is logged in automatically