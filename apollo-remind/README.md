# Apollo::Rio Reminders (`apollo-remind`)

Multi-channel personal calendar reminders for the Apollo::Rio electronic music scene platform.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    USER CREATES REMINDER                     │
│  Frontend Calendar UI  ·  Telegram /remind  ·  Auto-RSVP    │
└──────────────────────────┬──────────────────────────────────┘
                           │
                    REST API / Webhook
                           │
              ┌────────────▼────────────────┐
              │    apollo_reminders table    │
              │  status=pending  due_at_gmt  │
              └────────────┬────────────────┘
                           │
                    WP-Cron (2 min)
                           │
              ┌────────────▼────────────────┐
              │     Cron\Processor::run()    │
              │   Picks due, dispatches →    │
              └──┬──────┬──────┬──────┬─────┘
                 │      │      │      │
          ┌──────▼┐ ┌───▼──┐ ┌▼────┐ ┌▼─────┐
          │Telegram│ │ Push │ │Email│ │Notif │
          │  Bot   │ │VAPID │ │     │ │In-app│
          └────────┘ └──────┘ └─────┘ └──────┘
                 │      │      │      │
              ┌──▼──────▼──────▼──────▼─────┐
              │    apollo_remind_log table   │
              │   Delivery audit per channel │
              └─────────────────────────────┘
```

## Channels

| Channel | Library | Requirement |
|---------|---------|-------------|
| **Telegram** | `wp_remote_post()` (native WP) | Bot token from @BotFather |
| **Web Push** | `minishlink/web-push` (composer) | VAPID keys + HTTPS |
| **Email** | `apollo-email` → `wp_mail()` fallback | None (works out of the box) |
| **In-app** | Direct insert to `apollo_notifications` | `apollo-notif` active |

## Setup

### 1. Install

```bash
cd wp-content/plugins/
# Copy apollo-remind/ folder
```

### 2. Activate

Activate via wp-admin → Plugins. Requires `apollo-core`.

### 3. Telegram Bot

```bash
# 1. Create bot via @BotFather on Telegram → get token
# 2. Add to wp-config.php:
define( 'APOLLO_TELEGRAM_BOT_TOKEN', 'YOUR_TOKEN_HERE' );

# 3. Set webhook (choose one):
# Option A: wp-admin → Apollo → Reminders → "Set Webhook" button
# Option B: CLI
php wp-content/plugins/apollo-remind/setup.php webhook
```

**User linking flow:**
1. User opens `@ApolloRioBot` on Telegram, sends `/start`
2. User sends `/link` → bot replies with 6-char code
3. User enters code in Apollo dashboard settings → accounts linked
4. All reminders with `telegram` channel now deliver to their chat

### 4. Web Push (VAPID)

```bash
# Generate VAPID keys:
php wp-content/plugins/apollo-remind/setup.php vapid

# Add output to wp-config.php:
define( 'APOLLO_VAPID_PUBLIC_KEY',  'BLong...Base64URL...' );
define( 'APOLLO_VAPID_PRIVATE_KEY', 'Short...Base64URL...' );

# For production (encrypted payloads):
cd wp-content/plugins/apollo-remind/
composer require minishlink/web-push
```

### 5. Auto-RSVP Reminders

When `apollo-events` is active and a user RSVPs to an event, a reminder is automatically created 2 hours before the event start time. Delivered via `push`, `telegram`, and `notif` channels.

## REST API

All under `apollo/v1/remind` namespace. Requires authentication (logged-in user).

| Route | Method | Description |
|-------|--------|-------------|
| `/remind` | GET | List user's reminders (filter: `status`, `per_page`, `page`) |
| `/remind` | POST | Create reminder |
| `/remind/{id}` | GET | Get single reminder |
| `/remind/{id}` | PUT | Update pending reminder |
| `/remind/{id}` | DELETE | Cancel reminder |
| `/remind/calendar` | GET | Calendar view (`from`, `to` date range) |
| `/remind/channels` | GET | Available channels for current user |
| `/remind/telegram/link` | POST | Link Telegram account via code |
| `/remind/telegram/status` | GET | Check Telegram link status |
| `/remind/push/subscribe` | POST | Subscribe device for Web Push |
| `/remind/push/unsubscribe` | DELETE | Unsubscribe device |
| `/remind/push/vapid-key` | GET | Get VAPID public key (public) |

### Create Reminder (POST /remind)

```json
{
    "title": "Festa na Lapa!",
    "message": "Get ready for the party at Circo Voador",
    "due_at_gmt": "2026-04-15 23:00:00",
    "channels": ["telegram", "push", "notif"],
    "context": "manual",
    "ref_type": "event",
    "ref_id": 1234,
    "recurrence": "none"
}
```

## Telegram Bot Commands

| Command | Description |
|---------|-------------|
| `/start` | Welcome + setup instructions |
| `/link` | Generate link code (15 min TTL) |
| `/remind HH:MM message` | Quick-create reminder (BRT timezone) |
| `/reminders` | List next 10 pending reminders |
| `/cancel {id}` | Cancel a pending reminder |
| `/help` | Show commands |

## Database Tables

| Table | Purpose |
|-------|---------|
| `apollo_reminders` | Core queue — all reminders with status, channels, recurrence |
| `apollo_remind_telegram` | User ↔ Telegram chat_id mapping |
| `apollo_remind_push_subs` | Per-device Web Push subscriptions |
| `apollo_remind_log` | Delivery audit trail (channel, status, response) |

## Hooks

**Fires:**
- `apollo/remind/created` → `($id, $user_id, $args)` — after reminder created
- `apollo/remind/sent` → `($id, $user_id, $channels)` — after successful delivery
- `apollo/remind/failed` → `($id, $user_id)` — after max attempts exhausted

**Listens:**
- `apollo/event/rsvp` → Auto-creates 2h-before reminder
- `apollo/event/published` → Reserved for future use

## Key Libraries (GitHub)

- **Telegram Bot API**: Uses native `wp_remote_post()` — no library needed
- **Web Push**: [`web-push-libs/web-push-php`](https://github.com/web-push-libs/web-push-php) (minishlink/web-push) — VAPID/RFC 8292
- **Reference**: [`php-telegram-bot/core`](https://github.com/php-telegram-bot/core) — If you need advanced bot features (conversations, inline keyboards)
- **Reference**: [`WPGov/telegram-bot`](https://github.com/WPGov/telegram-bot) — WP Telegram plugin patterns

## wp-config.php Constants

```php
// Telegram Bot (required for Telegram channel)
define( 'APOLLO_TELEGRAM_BOT_TOKEN', '123456789:AABBccDDeeFF...' );

// VAPID keys (required for Web Push)
define( 'APOLLO_VAPID_PUBLIC_KEY',  'BLongBase64URLPublicKey...' );
define( 'APOLLO_VAPID_PRIVATE_KEY', 'ShortBase64URLPrivateKey...' );
```

## License

GPL-2.0-or-later
