# apollo-waha — contract

## Reads

| Source | Field | When authoritative |
| --- | --- | --- |
| BRAIN | method | after bootstrap / before most plugin work |
| BRAIN | logged_in | after apollo/brain/core_ready |
| BRAIN | slug | front only, after route match / template_redirect |
| BRAIN | rest, cpt | UNKNOWN — do not infer |
| user meta | `_apollo_whatsapp_phone` | after login verify |
| options | `apollo_wa_*` | after plugins_loaded |

## Writes

| Target | Key |
| --- | --- |
| user meta | `_apollo_wa_candidates`, `_apollo_wa_jid`, `_apollo_wa_lid`, `_apollo_wa_flow_state`, `_apollo_wa_flow_until`, `_apollo_wa_pane_mute`, `_apollo_whatsapp_optin` |
| options (autoload false for secrets) | `apollo_wa_api_key`, `apollo_wa_hmac_key` |
| options | `apollo_wa_base_url`, `apollo_wa_session`, `apollo_wa_group_id`, behavior flags |
| tables via core | `apollo_wa_queue`, `apollo_wa_messages` |

## Emits

`apollo/whatsapp/session_status`
`apollo/whatsapp/message_in`
`apollo/whatsapp/message_out`
`apollo/whatsapp/queue_requested`
`apollo/whatsapp/queue_processed`
`apollo/whatsapp/join_added`
`apollo/whatsapp/join_invited`
`apollo/whatsapp/join_rejected`
`apollo/whatsapp/flow_hit`
`apollo/whatsapp/handoff`
`apollo/whatsapp/wow_reaction`

## Listens

`apollo/login/phone_verified`
`apollo/event/published`
`rest_api_init`
`admin_menu`
`wp_ajax_` none unless admin-ajax is later authorized

## Capability

`apollo_manage_whatsapp` fallback `manage_options`
