# apollo-waha — boot boundary

```
FORCE-LOAD GROUP     ≠ this plugin
ACTIVE-PLUGIN GROUP  = this plugin
```

| Point | Allowed work |
| --- | --- |
| file evaluation | constants, classmap require, no WAHA HTTP |
| plugins_loaded | boot(), option load, hook registration |
| rest_api_init | register `/apollo/v1/wa/*` |
| admin_menu | screens 1–5 under apollo-admin |
| webhook hit | HMAC pipeline then event switch |
| muplugins_loaded:-100 | DO NOTHING HERE |

Callback gating (session WORKING) is allowed.
Plugin unload is not allowed.
Cache is not this cell.
