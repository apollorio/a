# apollo-waha tree

Copy this folder to:

```
D:\dev\_apollo.rio.br\plugins\apollo-waha
```

```
apollo-waha/
├── apollo-waha.php              bootstrap only
├── uninstall.php
├── ARCHITECTURE.md
├── CONTRACT.md
├── BOOT-BOUNDARY.md
├── JOIN-CHAIN.md
├── ADMIN-SURFACES.md
├── STRUCTURE.md
├── waha-registry.json           SSOT names
├── docs/
│   └── WEBHOOK-SECURITY.md
├── inc/
│   ├── class-plugin.php         orchestrator
│   ├── Client/class-client.php
│   ├── Session/class-session.php
│   ├── Webhook/class-webhook.php
│   ├── Phone/class-phone.php
│   ├── Queue/class-queue.php
│   ├── Pane/class-pane.php
│   └── Flow/class-flows.php
├── admin/
│   ├── class-admin.php
│   └── views/
│       ├── settings.php
│       ├── queue.php
│       ├── pane.php
│       └── flows.php
├── public/
│   ├── class-public.php
│   └── views/
│       ├── join-button.php
│       └── my-requests.php
└── assets/
    ├── admin.css
    ├── admin.js
    ├── public.css
    └── public.js
```

One class per file. Views only echo. HTTP only in Client. HMAC only in Webhook. BR only in Phone. Add only in Queue.
