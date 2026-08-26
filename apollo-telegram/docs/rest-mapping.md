# Apollo Telegram Plugin — REST `/telegram` Mapping

## Current Architecture

### Page Access
- **Rewrite Rule**: `^telegram/?$` → loads `views/telegram-phone-support.php`
- **Query Var**: `apollo_telegram_test`
- **Access**: `https://site.com/telegram` (pretty permalink) or `https://site.com/?telegram-test=1` (debug mode)

### Frontend View
**File**: `views/telegram-phone-support.php`
- Standalone HTML page with embedded JavaScript
- Phone input with country selector
- Verification modal with 6-digit code input
- REST calls to `wp-json/apollo-telegram/v1/*` endpoints

### Backend REST Endpoints
**Namespace**: `apollo-telegram/v1`

| Endpoint | Method | Purpose | Auth |
|----------|--------|---------|------|
| `/request-support-verification` | POST | Create phone verification request | Nonce (`apollo_telegram_verify`) |
| `/verify-support-code` | POST | Verify 6-digit code from bot | Nonce (`apollo_telegram_verify`) |
| `/telegram` | GET, POST | Legacy debug: generate link key | WP_DEBUG + Nonce |
| `/broadcast` | POST | Send message to all linked users | `manage_options` |
| `/get-message` | POST | Telegram webhook receiver | Secret token (`X-Telegram-Bot-Api-Secret-Token`) |
| `/get-message-polling` | GET | Local dev: getUpdates polling | Local env + admin + wp_rest nonce |

## System Flow

### Phone Verification Flow
```
1. User opens /telegram (page load)
   ↓
2. User enters phone → clicks validate
   ↓
3. POST /request-support-verification
   → VerificationService::createPending()
   → Creates DB record in wp_apollo_telegram_verif
   → Returns request_id + deep_link
   ↓
4. User clicks "Open Bot" → opens Telegram bot
   ↓
5. Bot interaction (via webhook/polling):
   /start command → StartCommand::execute()
   → Shows phone share button
   ↓
6. User shares contact → GenericCommand::execute()
   → VerificationService::bindContact()
   → Generates 6-digit code
   → Sends code via Telegram
   ↓
7. User pastes code → clicks "Sou eu mesmo!"
   ↓
8. POST /verify-support-code
   → VerificationService::verifyCode()
   → Validates code against hash
   → Updates DB: status = 'verified'
   ↓
9. Success → user can complete registration
```

### Telegram Bot System
**Class**: `TelegramService`
- `sendMessage()` — Send messages with retries, rate limiting
- `generateLinkKey()` — Create UUID keys for linking
- `validateAndLinkChat()` — Validate and link Telegram chat
- `broadcastToAll()` — Send to all linked users
- `sendReminder()` —Rich reminders with action URLs

**Webhook Handling**:
- `GetMessage` endpoint receives Telegram webhooks
- `Telegram::handle()` processes updates
- Commands: `start`, `cancel`, `callback_query`, `generic`
- `UpdateHandler` — Pre-execution filters
- `CallbackQueryHandler` — Button callbacks

## REST API Structure

### VerificationController
**File**: `src/API/VerificationController.php`

All routes registered under `apollo-telegram/v1`:

```php
// 1. Request verification
register_rest_route('apollo-telegram/v1', '/request-support-verification', [...]);

// 2. Verify code
register_rest_route('apollo-telegram/v1', '/verify-support-code', [...]);

// 3. Legacy debug (WP_DEBUG only)
register_rest_route('apollo-telegram/v1', '/telegram', [...]);

// 4. Broadcast (admin only)
register_rest_route('apollo-telegram/v1', '/broadcast', [...]);

// 5. Webhook receiver
getMessage(): GetMessage endpoint

// 6. Polling (local only)
getMessagePolling(): GetMessagePolling endpoint
```

### Permission Callbacks
- `permission_verify_nonce()` — Verifies `X-Apollo-Telegram-Nonce` header or `nonce` param
- `permission_legacy_debug()` — Requires `WP_DEBUG` + nonce
- Admin endpoints — `current_user_can('manage_options')`
- Webhook — `WebhookService::validateIncoming()` (secret token)

## Data Models

### Verification Table
**Table**: `wp_apollo_telegram_verif`

| Field | Type | Purpose |
|-------|------|---------|
| `id` | BIGINT UNSIGNED AI | Primary key |
| `request_id` | CHAR(36) | UUID for verification request |
| `web_user_id` | BIGINT UNSIGNED | Linked WordPress user ID |
| `phone` | VARCHAR(20) | Normalized phone number |
| `code_hash` | VARCHAR(255) | wp_hash_password() of 6-digit code |
| `telegram_chat_id` | BIGINT UNSIGNED | Telegram chat ID after contact share |
| `step` | VARCHAR(32) | `requested`, `contact_shared`, `code_delivered` |
| `status` | VARCHAR(20) | `pending`, `verified`, `failed` |
| `verify_attempts` | TINYINT UNSIGNED | Failed verification attempts |
| `expires_at` | DATETIME | Expiration timestamp |
| `created_at` | TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | Last update timestamp |

**Indexes**:
- `UNIQUE KEY uniq_request_id (request_id)`
- `KEY idx_phone_status_exp (phone, status, expires_at)`
- `KEY idx_chat_status (telegram_chat_id, status)`
- `KEY idx_status_exp (status, expires_at)`

### Linked Users Option
**Option**: `apollo_telegram_linked_chats`
```php
[
  chat_id => [
    'key' => 'uuid',
    'phone' => 'number',
    'linked_at' => timestamp,
    'telegram_username' => 'username',
    'extra' => []
  ]
]
```

## Key Helper Functions

**File**: `includes/helpers.php`

```php
// Configuration (options + wp-config overrides)
apollo_telegram_config(string $key, mixed $default = null): mixed

// Bot credentials
apollo_telegram_bot_token(): string
apollo_telegram_bot_username(): string

// Environment detection
apollo_telegram_uses_local_polling(): bool

// Database credentials (PDO-compatible)
apollo_telegram_wp_db_credentials(): array

// Phone normalization
apollo_telegram_normalize_phone(string $phone): ?string
apollo_telegram_phone_log_suffix(string $phone): string

// Debug logging (no tokens/codes)
apollo_telegram_debug_log(string $event, array $data = []): void

// REST nonce verification
apollo_telegram_verify_rest_nonce(WP_REST_Request $request): bool

// Rate limiting wrapper
apollo_rl(string $key, int $max = 5, int $window = 300): bool
```

## Integration Points

### WordPress Hooks
```php
// REST API initialization
add_action('rest_api_init', [...]);

// Rewrite rules
add_action('init', 'add_rewrite_rule(...)', 5);
add_filter('query_vars', [...]);

// Page rendering
add_action('template_redirect', [...]);

// Cron cleanup
add_action('apollo_clean_expired_verifs', [...]);

// Plugin activation/deactivation
register_activation_hook(__FILE__, [...]);  // Flush rewrite rules, schedule cron
register_deactivation_hook(__FILE__, [...]); // Clear cron, flush rewrite rules
```

### Telegram Bot Integration
- Uses `php-telegram-bot/core` library
- Extended classes: `Telegram`, `Request`, `Command`, `UserCommand`, `AdminCommand`, `SystemCommand`
- Commands directory: `src/Telegram/Commands/`
- Callbacks directory: `src/Telegram/CallbackQueries/`
- Handlers: `UpdateHandler`, `CallbackQueryHandler`

### Webhook vs Polling
- **Production**: Webhook via `setWebhook` → `GetMessage` endpoint
- **Local Development**: `getUpdates` polling via `GetMessagePolling` endpoint
- Auto-detection: `apollo_telegram_uses_local_polling()` checks `WP_DEBUG` or `local` environment

## Security

### Rate Limiting
**Class**: `RateLimiter`
- Object cache (Redis) with transient fallback
- Scopes: IP-based, phone-based, chat-based
- Examples:
  - `req_ver_ip:{ip}` — 5 requests per 15 minutes
  - `req_ver_phone:{phone}` — 3 requests per hour
  - `verify:{phone}:{ip}` — 5 attempts per 10 minutes
  - `contact_chat:{chat_id}` — 3 attempts per 10 minutes

### Token Security
- Bot token: `APOLLO_TELEGRAM_BOT_TOKEN` constant in `wp-config.php` only
- Webhook secret: Stored in plugin options, validated via `X-Telegram-Bot-Api-Secret-Token`
- Verification nonce: `wp_create_nonce('apollo_telegram_verify')`
- Code hashing: `wp_hash_password()` for 6-digit codes

## REST API Usage (Frontend)

```javascript
// Configuration (injected by PHP)
const CONFIG = {
  restUrl: '/wp-json/apollo-telegram/v1',
  nonce: 'wp_rest_nonce_here',
  botUser: 'apolloRio_bot'
};

// 1. Request verification
fetch(CONFIG.restUrl + '/request-support-verification', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-Apollo-Telegram-Nonce': CONFIG.nonce
  },
  body: JSON.stringify({
    phone: '+5512999999999',
    nonce: CONFIG.nonce
  })
});

// 2. Verify code
fetch(CONFIG.restUrl + '/verify-support-code', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-Apollo-Telegram-Nonce': CONFIG.nonce
  },
  body: JSON.stringify({
    phone: '+5512999999999',
    request_id: 'uuid-here',
    code: '123456',
    nonce: CONFIG.nonce
  })
});
```

## Dependencies

### Composer
- `longman/telegram-bot` — Telegram Bot API wrapper
- `guzzlehttp/guzzle` — HTTP client (via telegram-bot)

### WordPress Requirements
- WordPress 6.4+
- PHP 8.1+
- MySQL (for telegram-bot conversations + verification table)

## File Structure

```
apollo-telegram/
├── apollo-telegram.php          # Plugin bootstrap, rewrite rules, hooks
├── includes/
│   ├── helpers.php              # Global helper functions
│   └── autoload.php             # Class autoloader
├── src/
│   ├── API.php                  # REST API loader
│   ├── Core.php                 # Plugin core (DI container setup)
│   ├── Container.php            # Dependency injection container
│   ├── API/
│   │   ├── BaseEndpoint.php     # Abstract REST endpoint
│   │   ├── VerificationController.php  # Verification routes
│   │   └── Endpoints/
│   │       ├── GetMessage.php         # Webhook receiver
│   │       └── GetMessagePolling.php  # getUpdates polling
│   ├── Services/
│   │   ├── TelegramService.php        # Main bot service
│   │   ├── VerificationService.php    # Phone verification logic
│   │   └── WebhookService.php         # Webhook management
│   ├── Security/
│   │   └── RateLimiter.php            # Rate limiting
│   ├── Telegram/
│   │   ├── ExtendedClasses/
│   │   │   ├── Telegram.php           # Extended bot class
│   │   │   ├── Request.php            # Extended HTTP request
│   │   │   └── Commands/
│   │   │       ├── Command.php        # Base command
│   │   │       ├── UserCommand.php
│   │   │       └── AdminCommand.php
│   │   ├── Commands/UserCommands/
│   │   │   ├── StartCommand.php
│   │   │   ├── GenericCommand.php
│   │   │   ├── CancelCommand.php
│   │   │   └── CallbackQueryCommand.php
│   │   └── Handlers/
│   │       ├── UpdateHandler.php
│   │       └── CallbackQueryHandler.php
│   └── Helpers/
│       └── TelegramHelper.php         # Bot instantiation helper
├── views/
│   └── telegram-phone-support.php     # Frontend verification page
└── logs/                              # Debug and Telegram logs
```

## Summary

**Current State**:
- ✅ Phone verification page works via rewrite rule `/telegram`
- ✅ REST API endpoints under `apollo-telegram/v1/*`
- ✅ Full Telegram bot integration (webhook + polling)
- ✅ Phone verification with 6-digit codes via bot
- ✅ Broadcast system for linked users
- ✅ Rate limiting and security

**REST Coverage**:
- **100% of backend logic** is exposed via REST API
- **100% of frontend interactions** use REST API
- The only non-REST part is the initial page load (rewrite rule serving HTML)

**If you need everything under `/telegram` REST path**:
1. Create a new `TelegramController` with `index()` method
2. Move the HTML rendering from `template_redirect` to REST callback
3. Remove rewrite rules (optional)
4. Access page via `GET /wp-json/apollo-telegram/v1/telegram-page`

All core functionality already runs on REST — the page is just a shell that loads the JS which calls the REST API.