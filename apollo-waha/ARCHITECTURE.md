# apollo-waha — cell architecture

Directory on disk: `plugins/apollo-waha`
Product name: Apollo WhatsApp
WordPress plugin slug: `apollo-waha` (must match folder)
Internal prefix: `apollo_wa_`
REST: `/wp-json/apollo/v1/wa/*`
Hooks: `apollo/whatsapp/{action}`
SSOT: `waha-registry.json`

## Boot

```
active plugin (NOT mu-plugin, NOT force-load)
  → plugins_loaded
      → Apollo_Waha_Plugin::boot()
```

BRAIN-01 may already have `method` by then. `logged_in` is available after core_ready. Do not filter `pre_option_active_plugins`. Do not require this file from a mu-plugin.

## Owns

- WAHA HTTP client
- HMAC webhook verify
- join queue store access via core
- operator pane store
- keyword DM router
- admin screens 1–5
- public join button + my-requests

## Consumes

- `apollo-core`
- BRAIN context as read-only (`method`, `logged_in`; `slug` only on front)
- `apollo-login` verified phone meta
- `apollo-users`
- `apollo-events` upcoming
- `apollo-admin` menu parent

## Never

- CPT registration
- cache keys / drop-ins / transients-as-page-cache
- `active_plugins` mutation
- force-load / mu top-level require
- new REST namespace
- new hook family outside `apollo/whatsapp/*`
- treating `rest` / `cpt` BRAIN fields as known
- using the verified Apollo Business number on WAHA
