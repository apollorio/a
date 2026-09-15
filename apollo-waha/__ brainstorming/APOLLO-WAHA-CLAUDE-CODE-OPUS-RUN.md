# APOLLO WAHA — Claude Code / Opus

**TO:** Claude Code in VS Code
**MODEL:** Opus + thinking + extra-high
**SSOT:** waha-registry.json v1.1.1 only
**Do not** create new .md plans. Update registry pct_built/status when a stub becomes real.

## Every run — read this first

```
waha-registry.json
apollo-waha.php
inc/class-plugin.php
```

Then only the files named in that run.

Registry facts you must not contradict:

```
disk slug            apollo-waha
prefix               apollo_wa_
session              apollo
engine               GOWS
REST ns              apollo/v1   paths /wa/*
hooks                apollo/whatsapp/*
cap                  apollo_manage_whatsapp || manage_options
boot                 plugins_loaded:20
inventory            absent (48 canonical; promotion = 49th, not this run)
runtime_ready        false
overall              ~8%
webhook              registered, verify() hardcoded false → 401
client               returns {ok:false,status:0}
session              is_working() always false
admin menu()         empty — views exist unwired
shortcode            [apollo_wa_join] empty div
ops number           5521933008447
candidates           5521933008447 + 552133008447
ops number is NOT    Apollo Business
secrets              never in JSON, git, DOM
autoload false       apollo_wa_api_key, apollo_wa_hmac_key
```

never[] in the registry is law. No CPT, no cache invent, no active_plugins, no new REST ns, no mu/force-load, webhook never approves queue.

One run at a time. Stop when acceptance hits.

---

# RUN 0 — off the WP plugin tree

Only if the folder still lives at plugins/apollo-waha and --strict is red because of it.

```
You are Claude Code / Opus. One topic. No PHP features.

SSOT: plugins/apollo-waha/waha-registry.json v1.1.1
membership.verify_structure = deliberate_red_undeclared
membership.inventory = absent_from_PLUGIN-INVENTORY
canonical live SSOT plugin count = 48. Do not add a 49th.

1. Checkpoint copy to
   D:\dev\_apollo.rio.br\_lab\apollo-waha-checkpoint-YYYYMMDD
2. Move plugins/apollo-waha → D:\dev\_apollo.rio.br\_lab\apollo-waha
3. Zero apollo-waha.php left under plugins/
4. Do not write extra markdown except a 6-line MOVED.txt inside _lab/apollo-waha
   (old path, new path, date, option 1, not plugin 49).
5. Do not edit inventory, scan-plugins.js, gen-manifests.js, apollo-core, brain, cpts.php.
6. Do not change waha-registry.json except you MAY set
   membership.verify_structure note to relocated_to_lab if you touch it at all.

Stop. Report paths + file count.
```

If it is already in _lab/, skip RUN 0.

---

# RUN 1 — Client + Session + Settings

Registry modules: client (mvp_order 1), session (mvp_order 2), admin settings+strip only.

Do not implement webhook HMAC, phone, queue process, pane, flows.

```
You are Claude Code / Opus. Fill stubs. Do not invent modules.

WORK TREE (use the one that exists; do not move files this run):
D:\dev\_apollo.rio.br\_lab\apollo-waha
OR plugins/apollo-waha if RUN 0 was skipped on purpose.

READ ONLY FIRST:
waha-registry.json
apollo-waha.php
inc/class-plugin.php
inc/Client/class-client.php
inc/Session/class-session.php
admin/class-admin.php
admin/views/settings.php
docs/WEBHOOK-SECURITY.md

MAY EDIT:
inc/Client/class-client.php
inc/Session/class-session.php
admin/class-admin.php
admin/views/settings.php
assets/admin.css
assets/admin.js
waha-registry.json
  (pct_built/status only for modules you actually filled)

MAY ADD one file if needed:
inc/class-options.php
  get/set apollo_wa_* from registry.options.keys
  apollo_wa_api_key and apollo_wa_hmac_key autoload false
  never echo full secrets

MUST NOT EDIT:
inc/Webhook/class-webhook.php
inc/Phone/class-phone.php
inc/Queue/class-queue.php
inc/Pane/class-pane.php
inc/Flow/class-flows.php
public/**
docs/**
JOIN-CHAIN.md ARCHITECTURE.md CONTRACT.md BOOT-BOUNDARY.md
apollo-core/** apollo-brain.php config/cpts.php
_inventory/** scan-plugins.js gen-manifests.js

IMPLEMENT EXACTLY

A. Apollo_Waha_Client  modules[id=client]
   get/post/put via wp_remote_request
   base = option apollo_wa_base_url default http://127.0.0.1:3000
   header X-Api-Key = option apollo_wa_api_key
   timeout 20s
   return array ok, status, body
   never log the key
   session path: GET /api/sessions/apollo
   (outbound_waha.endpoints.session_get)

B. Apollo_Waha_Session  modules[id=session]
   ping() uses Client
   is_working() true iff body status === WORKING
   last ping may be a 30s transient or option apollo_wa_last_ping
   no plugin load/unload
   no new object-cache groups / page-cache keys

C. Admin settings + strip only
   screens.1_settings + screens.2_session_strip
   Wire admin_menu. Parent: search this checkout for apollo-admin menu slug.
   If UNKNOWN after search, top-level "Apollo WhatsApp". Do not invent a parent hook.
   Fields: apollo_wa_base_url, apollo_wa_api_key (password),
           apollo_wa_session, apollo_wa_group_id
   On first save if hmac empty: wp_generate_password(48,true,true)
   into apollo_wa_hmac_key autoload false. Do NOT implement HMAC verify.
   Button Testar: nonce + cap apollo_manage_whatsapp || manage_options
   Show WORKING | SCAN_QR_CODE | STARTING | FAILED | STOPPED | down
   If not WORKING print: session down — nada sai
   No secrets in data-* or page source. Mask keys.
   Do not wire queue/pane/flows menus this run.

D. Registry patch when done (numbers only):
   modules client / session / admin
   features waha_http_client, session_working_gate, settings_secrets
   screens 1_settings + 2_session_strip
   layers.core_bridge and build_totals.runtime_bridge_pct honest re-score
   Do not set verdict.runtime_ready true
   Do not claim HMAC/phone/join built

ACCEPTANCE
- Intelephense clean on touched PHP
- boot still plugins_loaded:20 and still requires apollo-core
- webhook verify() still hardcoded false
- Testar with WAHA down = not WORKING, no fatal
- keys not in HTML source
- no new REST routes
- no join add

STOP. Report files changed, option keys, menu parent slug
(path:line or top-level fallback), LocalWP test steps.
```

---

# RUN 2 — HMAC only (after RUN 1 green)

```
You are Claude Code / Opus.
SSOT waha-registry.json v1.1.1 + docs/WEBHOOK-SECURITY.md
webhook.pipeline[] is the spec.

EDIT ONLY: inc/Webhook/class-webhook.php
and registry pct fields for webhook.

Implement webhook.pipeline[] in order. Fail closed.
hash_hmac sha512 + hash_equals. Timestamp 300s.
Transient apollo_wa_hook_{id} 10 min.
payload.session must equal option apollo_wa_session (default apollo).
Body > 256kb → 413.
Events this run: session.status updates Session strip.
message may store last payload option for debug.
MUST NOT change queue status.
Webhook never approves queue.

Unsigned POST = 401. Stale ts = 401. Bad hmac = 401.
Replay = 200 empty. Good = 200.

No Phone. No Queue process. No new routes.

Patch registry webhook.code_pct / modules webhook /
features webhook_hmac_pipeline honestly.
STOP.
```

---

# RUN 3 — queue row only (after RUN 2)

```
You are Claude Code / Opus. SSOT waha-registry.json v1.1.1

Goal: logged-in user with _apollo_whatsapp_phone
→ status queued visible in admin Fila.

Do NOT run join_chain.
Process button: if !is_working() show session_down; else leave queued.

Store: if apollo-core table helper is not proven in this checkout,
use option apollo_wa_queue_rows JSON list and leave store.pct_built UNKNOWN.
No CPT. No invented core APIs.

Wire screens.3_join_queue menu + join button markup only.
apollo/login/phone_verified: fill callback only if hook payload is proven;
else leave empty and keep UNKNOWN.

STOP. No participants/add. No Phone check-exists.
```

---

# RUN 4 — Phone + join chain (later)

Use br_phone.self_test_vector and join_chain[] 12 steps.
First fixture: 5521933008447 / 552133008447.
Webhook still never approves.

---

Do not paste RUN 2-4 until the previous acceptance is green.
No extra ARCHITECTURE rewrite. Registry is the map.
Opus updates percentages when code stops being a stub.
