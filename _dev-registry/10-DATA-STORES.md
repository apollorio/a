# Data stores

## Custom tables

50 declared in `apollo-core/config/tables.php`; 
76 referenced via `$wpdb->prefix` in code.

| table | declared owner | group | refs | units |
|---|---|---|---|---|
| `apollo_` | **undeclared** | — | 89 | `apollo-chat` `apollo-core` `apollo-email` `apollo-groups` `apollo-notif` `apollo-social` |
| `apollo_activity` | `apollo-social` | social | 15 | `apollo-dashboard` `apollo-fav` `apollo-groups` `apollo-notif` `apollo-social` `apollo-users` |
| `apollo_notifications` | `apollo-notif` | communication | 15 | `apollo-notif` |
| `apollo_stats_content` | `apollo-statistics` | admin | 13 | `apollo-statistics` |
| `apollo_event_rsvp` | **undeclared** | — | 12 | `apollo-events` `apollo-statistics` `apollo-users` |
| `apollo_groups` | `apollo-groups` | social | 11 | `apollo-dashboard` `apollo-groups` |
| `apollo_reminders` | **undeclared** | — | 11 | `apollo-remind` |
| `apollo_stats_events` | `apollo-statistics` | admin | 11 | `apollo-statistics` |
| `apollo_stats_users` | `apollo-statistics` | admin | 11 | `apollo-statistics` |
| `apollo_user_ratings` | **undeclared** | — | 11 | `apollo-users` |
| `apollo_email_queue` | `apollo-email` | communication | 9 | `apollo-email` |
| `apollo_group_members` | `apollo-groups` | social | 9 | `apollo-dashboard` `apollo-groups` `apollo-users` |
| `apollo_signatures` | `apollo-sign` | documents | 8 | `apollo-sign` |
| `apollo_blocks` | **undeclared** | — | 7 | `apollo-social` |
| `apollo_remind_push_subs` | **undeclared** | — | 7 | `apollo-remind` |
| `apollo_remind_telegram` | **undeclared** | — | 7 | `apollo-core` `apollo-remind` |
| `apollo_favs` | `apollo-fav` | social | 5 | `apollo-dashboard` `apollo-fav` `apollo-users` |
| `apollo_gestor_activity` | `apollo-gestor` | documents | 5 | `apollo-gestor` |
| `apollo_gestor_tasks` | `apollo-gestor` | documents | 5 | `apollo-gestor` |
| `apollo_remind_log` | **undeclared** | — | 5 | `apollo-remind` |
| `apollo_wow_reactions` | **undeclared** | — | 5 | `apollo-wow` |
| `apollo_audit_log` | `apollo-core` | core | 4 | `apollo-core` |
| `apollo_doc_downloads` | `apollo-docs` | documents | 4 | `apollo-docs` |
| `apollo_doc_versions` | `apollo-docs` | documents | 4 | `apollo-docs` |
| `apollo_group_meta` | `apollo-groups` | social | 4 | `apollo-groups` |
| `apollo_mod_reports` | **undeclared** | — | 4 | `apollo-mod` |
| `apollo_notif_prefs` | `apollo-notif` | communication | 4 | `apollo-notif` |
| `apollo_stats_pageviews` | **undeclared** | — | 4 | `apollo-statistics` |
| `apollo_stats_radio` | **undeclared** | — | 4 | `apollo-statistics` |
| `apollo_stats_sessions` | **undeclared** | — | 4 | `apollo-statistics` |
| `apollo_chat_participants` | `apollo-chat` | communication | 3 | `apollo-chat` `apollo-users` |
| `apollo_connections` | `apollo-social` | social | 3 | `apollo-dashboard` `apollo-fav` `apollo-social` |
| `apollo_gestor_reminder_log` | **undeclared** | — | 3 | `apollo-gestor` |
| `apollo_matchmaking` | `apollo-users` | auth | 3 | `apollo-users` |
| `apollo_profile_views` | `apollo-users` | auth | 3 | `apollo-users` |
| `apollo_signature_audit` | `apollo-sign` | documents | 3 | `apollo-sign` |
| `apollo_stats_clicks` | **undeclared** | — | 3 | `apollo-statistics` |
| `apollo_chat_blocks` | `apollo-chat` | communication | 2 | `apollo-chat` |
| `apollo_chat_messages` | `apollo-chat` | communication | 2 | `apollo-chat` `apollo-users` |
| `apollo_gestor_milestones` | `apollo-gestor` | documents | 2 | `apollo-gestor` |
| `apollo_gestor_payments` | `apollo-gestor` | documents | 2 | `apollo-gestor` |
| `apollo_gestor_team` | `apollo-gestor` | documents | 2 | `apollo-gestor` |
| `apollo_mod_actions` | **undeclared** | — | 2 | `apollo-mod` |
| `apollo_telegram_verif` | **undeclared** | — | 2 | `apollo-telegram` |
| `apollo_wows` | `apollo-wow` | social | 2 | `apollo-users` `apollo-wow` |
| `statistics_pages` | **undeclared** | — | 2 | `apollo-fav` `apollo-statistics` |
| `apollo_achievements` | `apollo-membership` | membership | 1 | `apollo-membership` |
| `apollo_chat_attachments` | `apollo-chat` | communication | 1 | `apollo-chat` |
| `apollo_chat_presence` | `apollo-chat` | communication | 1 | `apollo-chat` |
| `apollo_chat_threads` | `apollo-chat` | communication | 1 | `apollo-chat` |
| `apollo_chat_typing` | `apollo-chat` | communication | 1 | `apollo-chat` |
| `apollo_cron_execution_log` | **undeclared** | — | 1 | `apollo-email` |
| `apollo_email_log` | `apollo-email` | communication | 1 | `apollo-email` |
| `apollo_follows` | **undeclared** | — | 1 | `apollo-social` |
| `apollo_gestor_income` | **undeclared** | — | 1 | `apollo-gestor` |
| `apollo_gestor_staff` | **undeclared** | — | 1 | `apollo-gestor` |
| `apollo_jwt_refresh` | **undeclared** | — | 1 | `apollo-login` |
| `apollo_login_attempts` | `apollo-login` | auth | 1 | `apollo-login` |
| `apollo_membership_log` | `apollo-membership` | membership | 1 | `apollo-membership` |
| `apollo_mod_log` | `apollo-mod` | admin | 1 | `apollo-mod` |
| `apollo_mod_queue` | `apollo-mod` | admin | 1 | `apollo-mod` |
| `apollo_newsletter_subscribers` | **undeclared** | — | 1 | `apollo-email` |
| `apollo_points` | `apollo-membership` | membership | 1 | `apollo-membership` |
| `apollo_quiz_results` | `apollo-login` | auth | 1 | `apollo-login` |
| `apollo_ranks` | `apollo-membership` | membership | 1 | `apollo-membership` |
| `apollo_simon_scores` | `apollo-login` | auth | 1 | `apollo-login` |
| `apollo_social_posts` | **undeclared** | — | 1 | `apollo-groups` |
| `apollo_steps` | `apollo-membership` | membership | 1 | `apollo-membership` |
| `APOLLO_TELEGRAM_chat` | **undeclared** | — | 1 | `apollo-telegram` |
| `apollo_triggers` | `apollo-membership` | membership | 1 | `apollo-membership` |
| `apollo_url_rewrites` | `apollo-login` | auth | 1 | `apollo-login` |
| `apollo_user_fields` | `apollo-users` | auth | 1 | `apollo-users` |
| `apollo_wow_types` | `apollo-wow` | social | 1 | `apollo-wow` |
| `bp_activity` | **undeclared** | — | 1 | `apollo-fav` |
| `statistics_visit` | **undeclared** | — | 1 | `apollo-fav` |
| `statistics_visitor` | **undeclared** | — | 1 | `apollo-fav` |


### Declared but never referenced in code

| table | owner | group |
|---|---|---|
| `apollo_user_appointments` | `apollo-calendar` | calendar |
| `apollo_holidays` | `apollo-calendar` | calendar |
| `apollo_industry_calendar` | `apollo-cena` | industry |


## Options

22 declared; 159 touched in code.
Top 120 by call-site count:

| option | declared | sites | units |
|---|---|---|---|
| `active_plugins` | — | 22 | `apollo-admin` `apollo-adverts` `apollo-calendar` `apollo-core` `apollo-dashboard` `apollo-djs` `apollo-events` `apollo-gestor` `apollo-hub` `apollo-journal` `apollo-loc` `apollo-maps` `apollo-membership` `apollo-remind` `apollo-scheduler` `apollo-sheets` `apollo-templates` `apollo-users` `mu-plugin` |
| `apollo_admin_settings` | `apollo-admin` | 13 | `apollo-admin` `apollo-email` `apollo-statistics` |
| `apollo_telegram_linked_chats` | — | 10 | `apollo-telegram` |
| `apollo_email_settings` | — | 9 | `apollo-email` |
| `apollo_adverts_submit_page_id` | — | 7 | `apollo-adverts` |
| `apollo_dj_settings` | — | 6 | `apollo-djs` |
| `apollo_membership_trigger_points` | — | 6 | `apollo-membership` |
| `apollo_radio_json_cdn` | — | 6 | `apollo-radio` |
| `apollo_radio_json_fallback` | — | 6 | `apollo-radio` |
| `date_format` | — | 6 | `apollo-hub` `apollo-membership` `apollo-sheets` `apollo-telegram` |
| `endurance_cache_level` | — | 6 | `mu-plugin` |
| `apollo_adverts_settings` | — | 5 | `apollo-adverts` |
| `apollo_firewall_blacklist` | — | 5 | `apollo-login` |
| `apollo_radio_sc_proxy_url` | — | 5 | `apollo-radio` |
| `apollo_sheets_tables` | — | 5 | `apollo-sheets` |
| `apollo_sign_db_version` | `apollo-sign` | 5 | `apollo-sign` |
| `mm_cache_settings` | — | 5 | `mu-plugin` |
| `_apollo_matchmaking_dirty_pairs` | — | 4 | `apollo-users` |
| `aj_nrep_prefix` | — | 4 | `apollo-journal` |
| `apollo_adverts_archive_page_id` | — | 4 | `apollo-adverts` |
| `apollo_docs_db_version` | `apollo-docs` | 4 | `apollo-docs` |
| `apollo_event_settings` | — | 4 | `apollo-events` |
| `apollo_gestor_db_version` | `apollo-gestor` | 4 | `apollo-gestor` |
| `apollo_local_settings` | — | 4 | `apollo-loc` |
| `apollo_membership_settings` | — | 4 | `apollo-membership` |
| `apollo_statistics_db_version` | — | 4 | `apollo-statistics` |
| `apollo_tg_debug_start_trail` | — | 4 | `apollo-telegram` |
| `epc_filetype_expirations` | — | 4 | `mu-plugin` |
| `home` | — | 4 | `mu-plugin` |
| `rewrite_rules` | — | 4 | `apollo-login` `apollo-pane-engine` `apollo-statistics` `apollo-templates` |
| `siteurl` | — | 4 | `mu-plugin` |
| `apollo_coauthor_version` | — | 3 | `apollo-coauthor` |
| `apollo_dashboard_page_id` | — | 3 | `apollo-dashboard` |
| `apollo_dj_global_message` | — | 3 | `apollo-dj-sync` `apollo-login` |
| `apollo_dj_maintenance_mode` | — | 3 | `apollo-dj-sync` `apollo-login` |
| `apollo_dj_min_version` | — | 3 | `apollo-dj-sync` `apollo-login` |
| `apollo_email_db_version` | `apollo-email` | 3 | `apollo-email` |
| `apollo_login_page_id` | — | 3 | `apollo-login` |
| `apollo_membership_db_version` | — | 3 | `apollo-membership` |
| `apollo_newsletter_db_version` | — | 3 | `apollo-email` |
| `apollo_remind_db_version` | — | 3 | `apollo-remind` |
| `apollo_remind_telegram_token` | — | 3 | `apollo-remind` |
| `apollo_sheets_settings` | — | 3 | `apollo-sheets` |
| `apollo_templates_settings` | — | 3 | `apollo-templates` |
| `apollo_test_spreadsheet` | — | 3 | `apollo-templates` |
| `apollo_vapid_public_key` | — | 3 | `apollo-notif` `apollo-remind` |
| `epc_skip_404_handling` | — | 3 | `mu-plugin` |
| `_mm_refresh_token` | — | 2 | `mu-plugin` |
| `admin_email` | — | 2 | `apollo-core` `apollo-email` |
| `apollo_` | — | 2 | `apollo-core` |
| `apollo_admin_frontend_rewrite_v2` | — | 2 | `apollo-admin` |
| `apollo_adverts_rewrite_version` | — | 2 | `apollo-adverts` |
| `apollo_adverts_spam_log` | — | 2 | `apollo-adverts` |
| `apollo_adverts_version` | — | 2 | `apollo-adverts` |
| `apollo_chat_rewrite_v` | — | 2 | `apollo-chat` |
| `apollo_coauthor_keep_data` | — | 2 | `apollo-coauthor` |
| `apollo_coauthor_settings` | — | 2 | `apollo-coauthor` |
| `apollo_dashboard_settings` | — | 2 | `apollo-dashboard` |
| `apollo_dashboard_version` | — | 2 | `apollo-dashboard` |
| `apollo_dashboard_widgets` | — | 2 | `apollo-dashboard` |
| `apollo_delete_data_on_uninstall` | — | 2 | `apollo-elementor-pro` |
| `apollo_dj_allowed_memberships_nicotine` | — | 2 | `apollo-dj-sync` `apollo-login` |
| `apollo_dj_allowed_roles_login` | — | 2 | `apollo-dj-sync` `apollo-login` |
| `apollo_elementor_settings` | — | 2 | `apollo-elementor-pro` |
| `apollo_email_cpt_href_repair_v1` | — | 2 | `apollo-email` |
| `apollo_email_logo_png_v1` | — | 2 | `apollo-email` |
| `apollo_email_transactional_shell_v2` | — | 2 | `apollo-email` |
| `apollo_event_requests` | — | 2 | `apollo-adverts` |
| `apollo_events_rewrite_version` | — | 2 | `apollo-events` |
| `apollo_events_static_shadows` | — | 2 | `apollo-events` |
| `apollo_fav_db_version` | — | 2 | `apollo-fav` |
| `apollo_groups_db_version` | — | 2 | `apollo-groups` |
| `apollo_hub_rewrite_version` | — | 2 | `apollo-hub` |
| `apollo_hub_static_shadows` | — | 2 | `apollo-hub` |
| `apollo_hub_version` | — | 2 | `apollo-hub` |
| `apollo_login_flush_rewrites` | — | 2 | `apollo-login` |
| `apollo_login_settings` | — | 2 | `apollo-login` |
| `apollo_maps_center_lat` | — | 2 | `apollo-maps` |
| `apollo_maps_center_lng` | — | 2 | `apollo-maps` |
| `apollo_maps_default_zoom` | — | 2 | `apollo-maps` |
| `apollo_membership_badge_types_seeded` | — | 2 | `apollo-membership` |
| `apollo_modelo_seeded` | — | 2 | `apollo-core` |
| `apollo_pane_engine_version` | — | 2 | `apollo-pane-engine` |
| `apollo_radio_sc_client_id` | — | 2 | `apollo-radio` |
| `apollo_route_setup_version` | — | 2 | `mu-plugin` |
| `apollo_sheets_db_version` | `apollo-sheets` | 2 | `apollo-sheets` |
| `apollo_sheets_version` | — | 2 | `apollo-sheets` |
| `apollo_social_version` | — | 2 | `apollo-social` |
| `apollo_telegram_rewrites_ver` | — | 2 | `apollo-telegram` |
| `apollo_users_version` | — | 2 | `apollo-users` |
| `apollo_vapid_private_key` | — | 2 | `apollo-notif` `apollo-remind` |
| `elementor_cpt_support` | — | 2 | `apollo-admin` |
| `mm_brand` | — | 2 | `mu-plugin` |
| `site_icon` | — | 2 | `apollo-core` `mu-plugin` |
| `time_format` | — | 2 | `apollo-membership` `apollo-sheets` |
| `users_can_register` | — | 2 | `apollo-login` |
| `aj_nrep_format` | — | 1 | `apollo-journal` |
| `apollo_admin_activated` | — | 1 | `apollo-admin` |
| `apollo_admin_version` | — | 1 | `apollo-admin` |
| `apollo_analytics_admin_override` | — | 1 | `apollo-statistics` |
| `apollo_calendar_db_version` | — | 1 | `apollo-calendar` |
| `apollo_calendar_version` | — | 1 | `apollo-calendar` |
| `apollo_cdn_enabled` | — | 1 | `apollo-core` |
| `apollo_cdn_registered` | — | 1 | `apollo-core` |
| `apollo_cdn_url` | — | 1 | `apollo-elementor-pro` |
| `apollo_chat_bad_words_enabled` | — | 1 | `apollo-chat` |
| `apollo_chat_flood_max` | — | 1 | `apollo-chat` |
| `apollo_chat_quota_` | — | 1 | `apollo-chat` |
| `apollo_chat_retention_days` | — | 1 | `apollo-chat` |
| `apollo_chat_version` | — | 1 | `apollo-chat` |
| `apollo_debug_mode` | `apollo-core` | 1 | `apollo-core` |
| `apollo_dj_daily_limit` | — | 1 | `apollo-login` |
| `apollo_dj_min_accuracy` | — | 1 | `apollo-login` |
| `apollo_dj_quantize_level` | — | 1 | `apollo-login` |
| `apollo_dj_version` | — | 1 | `apollo-djs` |
| `apollo_email_installed_at` | — | 1 | `apollo-email` |
| `apollo_event_version` | — | 1 | `apollo-events` |
| `apollo_fav_settings` | — | 1 | `apollo-fav` |
| `apollo_gestor_settings` | — | 1 | `apollo-gestor` |
| `apollo_groups_rewrite_version` | — | 1 | `apollo-groups` |


## Constants

416 distinct constants defined. A constant defined in more than one unit is
a redefinition hazard — PHP emits a notice and keeps the first value.

| constant | define sites | units | flag |
|---|---|---|---|
| `APOLLO_NAVBAR_LOADED` | 6 | `apollo-admin` `apollo-templates` | **multi-unit** |
| `WP_USE_THEMES` | 4 | `apollo-core` `apollo-djs` `apollo-statistics` `apollo-telegram` | **multi-unit** |
| `ABSPATH` | 2 | `apollo-core` `apollo-events` | **multi-unit** |
| `APOLLO_APP_SHELL_LOADED` | 2 | `apollo-admin` `apollo-templates` | **multi-unit** |
| `APOLLO_CASA_RT_CSS` | 2 | `apollo-templates` |  |
| `APOLLO_CASA_RT_JS` | 2 | `apollo-templates` |  |
| `APOLLO_EVENT_DIR` | 2 | `apollo-events` |  |
| `APOLLO_EVENT_SINGLE_RENDERING` | 2 | `apollo-events` |  |
| `APOLLO_MAPS_REST_NAMESPACE` | 2 | `apollo-maps` |  |
| `APOLLO_WEBP_FORCE_LOADED` | 2 | `apollo-core` `mu-plugin` | **multi-unit** |
| `APOLLO_ADMIN_BASENAME` | 1 | `apollo-admin` |  |
| `APOLLO_ADMIN_DIR` | 1 | `apollo-admin` |  |
| `APOLLO_ADMIN_FILE` | 1 | `apollo-admin` |  |
| `APOLLO_ADMIN_LAYERS` | 1 | `apollo-admin` |  |
| `APOLLO_ADMIN_OPTION_KEY` | 1 | `apollo-admin` |  |
| `APOLLO_ADMIN_REST_NAMESPACE` | 1 | `apollo-admin` |  |
| `APOLLO_ADMIN_URL` | 1 | `apollo-admin` |  |
| `APOLLO_ADMIN_VERSION` | 1 | `apollo-admin` |  |
| `APOLLO_ADVERTS_ACCOMMODATION_TYPES` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_ADMIN_ONLY_META` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_BASENAME` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_CONDITIONS` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_CURRENCY` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_DEFAULT_EXPIRATION` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_DIR` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_FILE` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_IMAGE_SIZES` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_INTENTS` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_MAX_IMAGES` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_MEMBER_ONLY_META` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_META_KEYS` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_POSTS_PER_PAGE` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_REST_NAMESPACE` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_TICKET_DOMAINS` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_TICKET_TYPES` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_TYPE_CANONICAL` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_URL` | 1 | `apollo-adverts` |  |
| `APOLLO_ADVERTS_VERSION` | 1 | `apollo-adverts` |  |
| `APOLLO_AE_DIR` | 1 | `apollo-elementor` |  |
| `APOLLO_AE_FILE` | 1 | `apollo-elementor` |  |
| `APOLLO_AE_URL` | 1 | `apollo-elementor` |  |
| `APOLLO_AE_VERSION` | 1 | `apollo-elementor` |  |
| `APOLLO_ASSETS_GRAIN_URL` | 1 | `apollo-events` |  |
| `APOLLO_BRAIN_LOADED` | 1 | `mu-plugin` |  |
| `APOLLO_CALENDAR_BASENAME` | 1 | `apollo-calendar` |  |
| `APOLLO_CALENDAR_DIR` | 1 | `apollo-calendar` |  |
| `APOLLO_CALENDAR_FILE` | 1 | `apollo-calendar` |  |
| `APOLLO_CALENDAR_URL` | 1 | `apollo-calendar` |  |
| `APOLLO_CALENDAR_VERSION` | 1 | `apollo-calendar` |  |
| `APOLLO_CANVAS_VARIANT` | 1 | `apollo-core` |  |
| `APOLLO_CASA_CELL_LIGHTBOX_EVENT` | 1 | `apollo-templates` |  |
| `APOLLO_CASA_CELL_MOTION` | 1 | `apollo-templates` |  |
| `APOLLO_CDN_BASE` | 1 | `apollo-templates` |  |
| `APOLLO_CHAT_FILE` | 1 | `apollo-chat` |  |
| `APOLLO_CHAT_PATH` | 1 | `apollo-chat` |  |
| `APOLLO_CHAT_URL` | 1 | `apollo-chat` |  |
| `APOLLO_CHAT_VERSION` | 1 | `apollo-chat` |  |
| `APOLLO_COAUTHOR_BASENAME` | 1 | `apollo-coauthor` |  |
| `APOLLO_COAUTHOR_CACHE_GROUP` | 1 | `apollo-coauthor` |  |
| `APOLLO_COAUTHOR_DIR` | 1 | `apollo-coauthor` |  |
| `APOLLO_COAUTHOR_FILE` | 1 | `apollo-coauthor` |  |
| `APOLLO_COAUTHOR_META_KEY` | 1 | `apollo-coauthor` |  |
| `APOLLO_COAUTHOR_POST_TYPES` | 1 | `apollo-coauthor` |  |
| `APOLLO_COAUTHOR_REST_NAMESPACE` | 1 | `apollo-coauthor` |  |
| `APOLLO_COAUTHOR_TAX` | 1 | `apollo-coauthor` |  |
| `APOLLO_COAUTHOR_URL` | 1 | `apollo-coauthor` |  |
| `APOLLO_COAUTHOR_VERSION` | 1 | `apollo-coauthor` |  |
| `APOLLO_COLOR_INPUT_HANDLE` | 1 | `apollo-core` |  |
| `APOLLO_COLOR_INPUT_VERSION` | 1 | `apollo-core` |  |
| `APOLLO_COMMENT_FILE` | 1 | `apollo-comment` |  |
| `APOLLO_COMMENT_PATH` | 1 | `apollo-comment` |  |
| `APOLLO_COMMENT_URL` | 1 | `apollo-comment` |  |
| `APOLLO_COMMENT_VERSION` | 1 | `apollo-comment` |  |
| `APOLLO_CORE_BOOTSTRAPPED` | 1 | `apollo-core` |  |
| `APOLLO_CORE_FILE` | 1 | `apollo-core` |  |
| `APOLLO_CORE_PATH` | 1 | `apollo-core` |  |
| `APOLLO_CORE_URL` | 1 | `apollo-core` |  |
| `APOLLO_CORE_VERSION` | 1 | `apollo-core` |  |
| `APOLLO_CPT_CLASSIFIED` | 1 | `apollo-adverts` |  |
| `APOLLO_DASHBOARD_BASENAME` | 1 | `apollo-dashboard` |  |
| `APOLLO_DASHBOARD_DIR` | 1 | `apollo-dashboard` |  |
| `APOLLO_DASHBOARD_FILE` | 1 | `apollo-dashboard` |  |
| `APOLLO_DASHBOARD_REST_NAMESPACE` | 1 | `apollo-dashboard` |  |
| `APOLLO_DASHBOARD_URL` | 1 | `apollo-dashboard` |  |
| `APOLLO_DASHBOARD_VERSION` | 1 | `apollo-dashboard` |  |
| `APOLLO_DEBUG` | 1 | `apollo-core` |  |
| `APOLLO_DJ_BASENAME` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_CACHE_GROUP` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_CACHE_TTL` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_CPT` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_DEFAULT_STYLE` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_DIR` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_FILE` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_META_KEYS` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_REST_NAMESPACE` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_SINGLE_PARTS` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_SYNC_APP_ID` | 1 | `apollo-dj-sync` |  |
| `APOLLO_DJ_SYNC_PATH` | 1 | `apollo-dj-sync` |  |
| `APOLLO_DJ_SYNC_VERSION` | 1 | `apollo-dj-sync` |  |
| `APOLLO_DJ_TAX_SOUND` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_URL` | 1 | `apollo-djs` |  |
| `APOLLO_DJ_VERSION` | 1 | `apollo-djs` |  |
| `APOLLO_DOCS_BASENAME` | 1 | `apollo-docs` |  |
| `APOLLO_DOCS_DB_VERSION` | 1 | `apollo-docs` |  |
| `APOLLO_DOCS_DIR` | 1 | `apollo-docs` |  |
| `APOLLO_DOCS_FILE` | 1 | `apollo-docs` |  |
| `APOLLO_DOCS_URL` | 1 | `apollo-docs` |  |
| `APOLLO_DOCS_VERSION` | 1 | `apollo-docs` |  |
| `APOLLO_ELEMENTOR_DIR` | 1 | `apollo-elementor-pro` |  |
| `APOLLO_ELEMENTOR_FILE` | 1 | `apollo-elementor-pro` |  |
| `APOLLO_ELEMENTOR_URL` | 1 | `apollo-elementor-pro` |  |
| `APOLLO_ELEMENTOR_VERSION` | 1 | `apollo-elementor-pro` |  |
| `APOLLO_EMAIL_BASENAME` | 1 | `apollo-email` |  |
| `APOLLO_EMAIL_BATCH_SIZE` | 1 | `apollo-email` |  |
| `APOLLO_EMAIL_CRON_HOOK` | 1 | `apollo-email` |  |
| `APOLLO_EMAIL_DB_VERSION` | 1 | `apollo-email` |  |
| `APOLLO_EMAIL_FILE` | 1 | `apollo-email` |  |
| `APOLLO_EMAIL_MAX_RETRIES` | 1 | `apollo-email` |  |
| `APOLLO_EMAIL_MIN_PHP` | 1 | `apollo-email` |  |
| `APOLLO_EMAIL_MIN_WP` | 1 | `apollo-email` |  |
| `APOLLO_EMAIL_PATH` | 1 | `apollo-email` |  |
| `APOLLO_EMAIL_SLUG` | 1 | `apollo-email` |  |
| `APOLLO_EMAIL_URL` | 1 | `apollo-email` |  |
| `APOLLO_EMAIL_VERSION` | 1 | `apollo-email` |  |
| `APOLLO_EVENT_BASENAME` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_CACHE_GROUP` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_CACHE_TTL` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_CPT` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_DEFAULT_SHARE_IMAGE` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_DEFAULT_STYLE` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_FILE` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_GONE_OFFSET_MINUTES` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_META_KEYS` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_REST_NAMESPACE` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_REVEAL_ANIM` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_REVEAL_FLOOR` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_SINGLE_PARTS` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_STYLES` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_TAX_CATEGORY` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_TAX_SEASON` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_TAX_SOUND` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_TAX_TAG` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_TAX_TYPE` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_URL` | 1 | `apollo-events` |  |
| `APOLLO_EVENT_VERSION` | 1 | `apollo-events` |  |
| `APOLLO_FAV_FILE` | 1 | `apollo-fav` |  |
| `APOLLO_FAV_PATH` | 1 | `apollo-fav` |  |
| `APOLLO_FAV_TABLE` | 1 | `apollo-fav` |  |
| `APOLLO_FAV_URL` | 1 | `apollo-fav` |  |
| `APOLLO_FAV_VERSION` | 1 | `apollo-fav` |  |
| `APOLLO_GESTOR_BASENAME` | 1 | `apollo-gestor` |  |
| `APOLLO_GESTOR_DB_VERSION` | 1 | `apollo-gestor` |  |
| `APOLLO_GESTOR_DIR` | 1 | `apollo-gestor` |  |
| `APOLLO_GESTOR_FILE` | 1 | `apollo-gestor` |  |
| `APOLLO_GESTOR_URL` | 1 | `apollo-gestor` |  |
| `APOLLO_GESTOR_VERSION` | 1 | `apollo-gestor` |  |
| `APOLLO_GROUPS_FILE` | 1 | `apollo-groups` |  |
| `APOLLO_GROUPS_PATH` | 1 | `apollo-groups` |  |
| `APOLLO_GROUPS_URL` | 1 | `apollo-groups` |  |
| `APOLLO_GROUPS_VERSION` | 1 | `apollo-groups` |  |
| `APOLLO_HTACCESS_LOCK_BOOTED` | 1 | `apollo-core` |  |
| `APOLLO_HUB_BASENAME` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_BIO_MAX_LEN` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_BLOCK_TYPES` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_CACHE_GROUP` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_CACHE_TTL` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_CPT` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_DIR` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_EDIT_SLUG` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_FILE` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_LINKS_MAX` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_META_KEYS` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_REST_NAMESPACE` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_SLUG` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_SOCIAL_ICONS` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_THEMES` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_URL` | 1 | `apollo-hub` |  |
| `APOLLO_HUB_VERSION` | 1 | `apollo-hub` |  |
| `APOLLO_JOURNAL_BASENAME` | 1 | `apollo-journal` |  |
| `APOLLO_JOURNAL_DIR` | 1 | `apollo-journal` |  |
| `APOLLO_JOURNAL_FILE` | 1 | `apollo-journal` |  |
| `APOLLO_JOURNAL_NREP_PREFIX` | 1 | `apollo-journal` |  |
| `APOLLO_JOURNAL_REST_NAMESPACE` | 1 | `apollo-journal` |  |
| `APOLLO_JOURNAL_URL` | 1 | `apollo-journal` |  |
| `APOLLO_JOURNAL_VERSION` | 1 | `apollo-journal` |  |
| `APOLLO_LISTING_HEADER_KERNEL` | 1 | `apollo-templates` |  |
| `APOLLO_LISTING_HEADER_RUNTIME` | 1 | `apollo-templates` |  |
| `APOLLO_LOCAL_BASENAME` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_CACHE_GROUP` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_CACHE_TTL` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_CPT` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_DEFAULT_STYLE` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_DIR` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_FILE` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_META_KEYS` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_REST_NAMESPACE` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_TAX_AREA` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_TAX_TYPE` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_URL` | 1 | `apollo-loc` |  |
| `APOLLO_LOCAL_VERSION` | 1 | `apollo-loc` |  |
| `APOLLO_LOCALE` | 1 | `mu-plugin` |  |
| `APOLLO_LOCALE_HTML` | 1 | `mu-plugin` |  |
| `APOLLO_LOCKOUT_TIER_0` | 1 | `apollo-login` |  |
| `APOLLO_LOCKOUT_TIER_1` | 1 | `apollo-login` |  |
| `APOLLO_LOCKOUT_TIER_2` | 1 | `apollo-login` |  |
| `APOLLO_LOCKOUT_TIER_3` | 1 | `apollo-login` |  |
| `APOLLO_LOCKOUT_TIER_4` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_BASENAME` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_BOOTSTRAPPED` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_CUSTOM_LOGIN_SLUG` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_CUSTOM_REGISTER_SLUG` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_DIR` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_FILE` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_LOCKOUT_DURATION` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_LOGIN_SLUG_ALIASES` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_LOGOUT_IN_PROGRESS` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_MAX_ATTEMPTS` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_MIN_REGISTRATION_AGE` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_PAGE_RESET` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_QUIZ_STAGES` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_REACTION_TARGETS` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_REST_NAMESPACE` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_SIMON_LEVELS` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_TABLE_APP_ACTIVITY` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_TABLE_LOGIN_ATTEMPTS` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_TABLE_QUIZ_RESULTS` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_TABLE_SIMON_SCORES` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_TABLE_URL_REWRITES` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_URL` | 1 | `apollo-login` |  |
| `APOLLO_LOGIN_VERSION` | 1 | `apollo-login` |  |
| `APOLLO_LUX_DIR` | 1 | `apollo-lux-panels` |  |
| `APOLLO_LUX_FILE` | 1 | `apollo-lux-panels` |  |
| `APOLLO_LUX_URL` | 1 | `apollo-lux-panels` |  |
| `APOLLO_LUX_VERSION` | 1 | `apollo-lux-panels` |  |
| `APOLLO_MAPS_BASENAME` | 1 | `apollo-maps` |  |
| `APOLLO_MAPS_DIR` | 1 | `apollo-maps` |  |
| `APOLLO_MAPS_FILE` | 1 | `apollo-maps` |  |
| `APOLLO_MAPS_URL` | 1 | `apollo-maps` |  |
| `APOLLO_MAPS_VERSION` | 1 | `apollo-maps` |  |
| `APOLLO_MEMBERSHIP_BADGE_TYPES` | 1 | `apollo-membership` |  |
| `APOLLO_MEMBERSHIP_BASENAME` | 1 | `apollo-membership` |  |
| `APOLLO_MEMBERSHIP_DEFAULT_BADGE_IMAGE` | 1 | `apollo-membership` |  |
| `APOLLO_MEMBERSHIP_DEFAULT_POINT_IMAGE` | 1 | `apollo-membership` |  |
| `APOLLO_MEMBERSHIP_DEFAULT_RANK_IMAGE` | 1 | `apollo-membership` |  |
| `APOLLO_MEMBERSHIP_DIR` | 1 | `apollo-membership` |  |
| `APOLLO_MEMBERSHIP_FILE` | 1 | `apollo-membership` |  |
| `APOLLO_MEMBERSHIP_REST_NAMESPACE` | 1 | `apollo-membership` |  |
| `APOLLO_MEMBERSHIP_URL` | 1 | `apollo-membership` |  |
| `APOLLO_MEMBERSHIP_VERSION` | 1 | `apollo-membership` |  |
| `APOLLO_META_BIRTH_DATE` | 1 | `apollo-login` |  |
| `APOLLO_META_EMAIL_VERIFIED` | 1 | `apollo-login` |  |
| `APOLLO_META_EXE_CURRENT_ACTION` | 1 | `apollo-login` |  |
| `APOLLO_META_EXE_LAST_SEEN` | 1 | `apollo-login` |  |
| `APOLLO_META_EXE_VERSION` | 1 | `apollo-login` |  |
| `APOLLO_META_LAST_LOGIN` | 1 | `apollo-login` |  |
| `APOLLO_META_LOCKOUT_TIER` | 1 | `apollo-login` |  |
| `APOLLO_META_LOCKOUT_UNTIL` | 1 | `apollo-login` |  |
| `APOLLO_META_LOGIN_ATTEMPTS` | 1 | `apollo-login` |  |
| `APOLLO_META_PARTY_ROLE` | 1 | `apollo-login` |  |
| `APOLLO_META_PASSWORD_RESET_EXPIRES` | 1 | `apollo-login` |  |
| `APOLLO_META_PASSWORD_RESET_TOKEN` | 1 | `apollo-login` |  |
| `APOLLO_META_PHONE` | 1 | `apollo-login` |  |
| `APOLLO_META_QUIZ_SCORE` | 1 | `apollo-login` |  |
| `APOLLO_META_SOCIAL_NAME` | 1 | `apollo-login` |  |
| `APOLLO_META_TELEGRAM_CHAT_ID` | 1 | `apollo-login` |  |
| `APOLLO_META_VERIFICATION_TOKEN` | 1 | `apollo-login` |  |
| `APOLLO_META_ZODIAC_SIGN` | 1 | `apollo-login` |  |
| `APOLLO_MOBILE_CELL_BOOT` | 1 | `apollo-templates` |  |
| `APOLLO_MOBILE_CELL_GESTURES` | 1 | `apollo-templates` |  |
| `APOLLO_MOBILE_CELL_SUPERVISOR` | 1 | `apollo-templates` |  |
| `APOLLO_MOBILE_CELL_UNLOCK` | 1 | `apollo-templates` |  |
| `APOLLO_MOD_FILE` | 1 | `apollo-mod` |  |
| `APOLLO_MOD_PATH` | 1 | `apollo-mod` |  |
| `APOLLO_MOD_URL` | 1 | `apollo-mod` |  |
| `APOLLO_MOD_VERSION` | 1 | `apollo-mod` |  |
| `APOLLO_NAVBAR_V2_LOADED` | 1 | `apollo-templates` |  |
| `APOLLO_NOTIF_FILE` | 1 | `apollo-notif` |  |
| `APOLLO_NOTIF_PATH` | 1 | `apollo-notif` |  |
| `APOLLO_NOTIF_URL` | 1 | `apollo-notif` |  |
| `APOLLO_NOTIF_VERSION` | 1 | `apollo-notif` |  |
| `APOLLO_PANE_ENGINE_MANIFEST` | 1 | `apollo-pane-engine` |  |
| `APOLLO_PANE_ENGINE_PATH` | 1 | `apollo-pane-engine` |  |
| `APOLLO_PANE_ENGINE_URL` | 1 | `apollo-pane-engine` |  |
| `APOLLO_PANE_ENGINE_VERSION` | 1 | `apollo-pane-engine` |  |
| `APOLLO_PLUS_ASIDE_LOADED` | 1 | `apollo-templates` |  |
| `APOLLO_PLUS_ASIDE_STYLES` | 1 | `apollo-templates` |  |
| `APOLLO_PLUS_TOPBAR_SCRIPTS` | 1 | `apollo-templates` |  |
| `APOLLO_PLUS_TOPBAR_STYLES` | 1 | `apollo-templates` |  |
| `APOLLO_RADIO_FILE` | 1 | `apollo-radio` |  |
| `APOLLO_RADIO_PATH` | 1 | `apollo-radio` |  |
| `APOLLO_RADIO_URL` | 1 | `apollo-radio` |  |
| `APOLLO_RADIO_VERSION` | 1 | `apollo-radio` |  |
| `APOLLO_RATE_TIER_0` | 1 | `apollo-login` |  |
| `APOLLO_RATE_TIER_1` | 1 | `apollo-login` |  |
| `APOLLO_RATE_TIER_2` | 1 | `apollo-login` |  |
| `APOLLO_RATE_TIER_3` | 1 | `apollo-login` |  |
| `APOLLO_RATE_TIER_TTL` | 1 | `apollo-login` |  |
| `APOLLO_REGISTRY_PATH` | 1 | `mu-plugin` |  |
| `APOLLO_REMIND_BASENAME` | 1 | `apollo-remind` |  |
| `APOLLO_REMIND_DB_VERSION` | 1 | `apollo-remind` |  |
| `APOLLO_REMIND_FILE` | 1 | `apollo-remind` |  |
| `APOLLO_REMIND_PATH` | 1 | `apollo-remind` |  |
| `APOLLO_REMIND_URL` | 1 | `apollo-remind` |  |
| `APOLLO_REMIND_VERSION` | 1 | `apollo-remind` |  |
| `APOLLO_SC_DIR` | 1 | `apollo-soundcloud` |  |
| `APOLLO_SC_FILE` | 1 | `apollo-soundcloud` |  |
| `APOLLO_SC_PREVIEW_END_PCT` | 1 | `apollo-soundcloud` |  |
| `APOLLO_SC_PREVIEW_MIN_SECONDS` | 1 | `apollo-soundcloud` |  |
| `APOLLO_SC_PREVIEW_START_PCT` | 1 | `apollo-soundcloud` |  |
| `APOLLO_SC_URL` | 1 | `apollo-soundcloud` |  |
| `APOLLO_SC_VERSION` | 1 | `apollo-soundcloud` |  |
| `APOLLO_SCHEDULER_APPOINTMENT_META` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_BASENAME` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_CACHE_GROUP` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_CACHE_TTL` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_CPT_APPOINTMENT` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_CPT_RESOURCE` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_CPT_SERVICE` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_DIR` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_FILE` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_RESOURCE_META` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_REST_BASE` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_REST_NAMESPACE` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_SERVICE_META` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_URL` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_USER_META_KEYS` | 1 | `apollo-scheduler` |  |
| `APOLLO_SCHEDULER_VERSION` | 1 | `apollo-scheduler` |  |
| `APOLLO_SEO_FILE` | 1 | `apollo-seo` |  |
| `APOLLO_SEO_OPTION` | 1 | `apollo-seo` |  |
| `APOLLO_SEO_PATH` | 1 | `apollo-seo` |  |
| `APOLLO_SEO_POST_META` | 1 | `apollo-seo` |  |
| `APOLLO_SEO_TERM_META` | 1 | `apollo-seo` |  |
| `APOLLO_SEO_URL` | 1 | `apollo-seo` |  |
| `APOLLO_SEO_VERSION` | 1 | `apollo-seo` |  |
| `APOLLO_SHEETS_BASENAME` | 1 | `apollo-sheets` |  |
| `APOLLO_SHEETS_CPT` | 1 | `apollo-sheets` |  |
| `APOLLO_SHEETS_DB_VERSION` | 1 | `apollo-sheets` |  |
| `APOLLO_SHEETS_DEFAULT_COLS` | 1 | `apollo-sheets` |  |
| `APOLLO_SHEETS_DEFAULT_ROWS` | 1 | `apollo-sheets` |  |
| `APOLLO_SHEETS_DIR` | 1 | `apollo-sheets` |  |
| `APOLLO_SHEETS_FILE` | 1 | `apollo-sheets` |  |
| `APOLLO_SHEETS_MAX_ENTRIES` | 1 | `apollo-sheets` |  |
| `APOLLO_SHEETS_REST_NAMESPACE` | 1 | `apollo-sheets` |  |
| `APOLLO_SHEETS_TABLE_SCHEME` | 1 | `apollo-sheets` |  |
| `APOLLO_SHEETS_URL` | 1 | `apollo-sheets` |  |
| `APOLLO_SHEETS_VERSION` | 1 | `apollo-sheets` |  |
| `APOLLO_SIGN_BASENAME` | 1 | `apollo-sign` |  |
| `APOLLO_SIGN_DB_VERSION` | 1 | `apollo-sign` |  |
| `APOLLO_SIGN_DIR` | 1 | `apollo-sign` |  |
| `APOLLO_SIGN_FILE` | 1 | `apollo-sign` |  |
| `APOLLO_SIGN_URL` | 1 | `apollo-sign` |  |
| `APOLLO_SIGN_VERSION` | 1 | `apollo-sign` |  |
| `APOLLO_SOCIAL_FILE` | 1 | `apollo-social` |  |
| `APOLLO_SOCIAL_PATH` | 1 | `apollo-social` |  |
| `APOLLO_SOCIAL_URL` | 1 | `apollo-social` |  |
| `APOLLO_SOCIAL_VERSION` | 1 | `apollo-social` |  |
| `APOLLO_STATISTICS_FILE` | 1 | `apollo-statistics` |  |
| `APOLLO_STATISTICS_PATH` | 1 | `apollo-statistics` |  |
| `APOLLO_STATISTICS_URL` | 1 | `apollo-statistics` |  |
| `APOLLO_STATISTICS_VERSION` | 1 | `apollo-statistics` |  |
| `APOLLO_STATS_DB_VERSION` | 1 | `apollo-statistics` |  |
| `APOLLO_STATS_FILE` | 1 | `apollo-statistics` |  |
| `APOLLO_STATS_PATH` | 1 | `apollo-statistics` |  |
| `APOLLO_STATS_URL` | 1 | `apollo-statistics` |  |
| `APOLLO_STATS_VERSION` | 1 | `apollo-statistics` |  |
| `APOLLO_TABLE_ACHIEVEMENTS` | 1 | `apollo-membership` |  |
| `APOLLO_TABLE_MEMBERSHIP_LOG` | 1 | `apollo-membership` |  |
| `APOLLO_TABLE_POINTS` | 1 | `apollo-membership` |  |
| `APOLLO_TABLE_RANKS` | 1 | `apollo-membership` |  |
| `APOLLO_TABLE_STEPS` | 1 | `apollo-membership` |  |
| `APOLLO_TABLE_TRIGGERS` | 1 | `apollo-membership` |  |
| `APOLLO_TELEGRAM_BASENAME` | 1 | `apollo-telegram` |  |
| `APOLLO_TELEGRAM_DB_VERSION` | 1 | `apollo-telegram` |  |
| `APOLLO_TELEGRAM_DIR` | 1 | `apollo-telegram` |  |
| `APOLLO_TELEGRAM_FILE` | 1 | `apollo-telegram` |  |
| `APOLLO_TELEGRAM_MENUS_SLUG` | 1 | `apollo-telegram` |  |
| `APOLLO_TELEGRAM_OPTIONS_KEY` | 1 | `apollo-telegram` |  |
| `APOLLO_TELEGRAM_OPTIONS_KEY_DB_VERSION` | 1 | `apollo-telegram` |  |
| `APOLLO_TELEGRAM_SLUG` | 1 | `apollo-telegram` |  |
| `APOLLO_TELEGRAM_URL` | 1 | `apollo-telegram` |  |
| `APOLLO_TELEGRAM_VERSION` | 1 | `apollo-telegram` |  |
| `APOLLO_TEMPLATES_BASENAME` | 1 | `apollo-templates` |  |
| `APOLLO_TEMPLATES_BOOTSTRAPPED` | 1 | `apollo-templates` |  |
| `APOLLO_TEMPLATES_DIR` | 1 | `apollo-templates` |  |
| `APOLLO_TEMPLATES_FILE` | 1 | `apollo-templates` |  |
| `APOLLO_TEMPLATES_REST_NAMESPACE` | 1 | `apollo-templates` |  |
| `APOLLO_TEMPLATES_URL` | 1 | `apollo-templates` |  |
| `APOLLO_TEMPLATES_VERSION` | 1 | `apollo-templates` |  |
| `APOLLO_UI_DIR` | 1 | `apollo-ui` |  |
| `APOLLO_UI_FILE` | 1 | `apollo-ui` |  |
| `APOLLO_UI_OPTION` | 1 | `apollo-ui` |  |
| `APOLLO_UI_URL` | 1 | `apollo-ui` |  |
| `APOLLO_UI_VERSION` | 1 | `apollo-ui` |  |
| `APOLLO_USERS_BASENAME` | 1 | `apollo-users` |  |
| `APOLLO_USERS_DIR` | 1 | `apollo-users` |  |
| `APOLLO_USERS_FILE` | 1 | `apollo-users` |  |
| `APOLLO_USERS_MEMBERSHIP_TYPES` | 1 | `apollo-users` |  |
| `APOLLO_USERS_PRIVACY_LEVELS` | 1 | `apollo-users` |  |
| `APOLLO_USERS_PROFILE_SLUG` | 1 | `apollo-users` |  |
| `APOLLO_USERS_REST_NAMESPACE` | 1 | `apollo-users` |  |
| `APOLLO_USERS_TABLE_FIELDS` | 1 | `apollo-users` |  |
| `APOLLO_USERS_TABLE_MATCHMAKING` | 1 | `apollo-users` |  |
| `APOLLO_USERS_TABLE_MATCHMAKING_SCORES` | 1 | `apollo-users` |  |
| `APOLLO_USERS_TABLE_PROFILE_VIEWS` | 1 | `apollo-users` |  |
| `APOLLO_USERS_TABLE_RATINGS` | 1 | `apollo-users` |  |
| `APOLLO_USERS_URL` | 1 | `apollo-users` |  |
| `APOLLO_USERS_VERSION` | 1 | `apollo-users` |  |
| `APOLLO_VAPID_PRIVATE_KEY` | 1 | `apollo-remind` |  |
| `APOLLO_VAPID_PUBLIC_KEY` | 1 | `apollo-remind` |  |
| `APOLLO_WOW_FILE` | 1 | `apollo-wow` |  |
| `APOLLO_WOW_PATH` | 1 | `apollo-wow` |  |
| `APOLLO_WOW_URL` | 1 | `apollo-wow` |  |
| `APOLLO_WOW_VERSION` | 1 | `apollo-wow` |  |
| `DB_HOST` | 1 | `apollo-email` |  |
| `EPC_VERSION` | 1 | `mu-plugin` |  |
| `NFD_EPC_MARKER` | 1 | `mu-plugin` |  |


## Roles and capabilities

| role | declared |
|---|---|
| `roles` | {"administrator":{"display":"apollo","level":10},"editor":{"display":"MOD","level":7},"aut |
| `membership_types` | ["nao-verificado","apollo","prod","dj","host","govern","business-pers"] |
| `form_levels` | [{"role":null,"access":"none"},{"role":"subscriber","access":"basic"},{"role":"contributor |
| `gestor_roles` | {"adm":{"label":"Administrador","can_manage":true,"can_finance":true},"gestor":{"label":"G |
| `cena_roles` | {"member":{"label":"Membro","level":1},"verified":{"label":"Verificado","level":2},"admin" |


Capabilities actually checked with `current_user_can()`:

| capability | checks | units |
|---|---|---|
| `manage_options` | 180 | `apollo-admin` `apollo-adverts` `apollo-calendar` `apollo-chat` `apollo-core` `apollo-dj-sync` `apollo-djs` `apollo-docs` `apollo-elementor-pro` `apollo-email` `apollo-events` `apollo-fav` `apollo-gestor` `apollo-groups` `apollo-hub` `apollo-journal` `apollo-login` `apollo-membership` `apollo-mod` `apollo-notif` `apollo-radio` `apollo-remind` `apollo-seo` `apollo-sheets` `apollo-sign` `apollo-statistics` `apollo-telegram` `apollo-templates` `apollo-ui` `apollo-users` `mu-plugin` |
| `edit_post` | 27 | `apollo-adverts` `apollo-coauthor` `apollo-djs` `apollo-docs` `apollo-email` `apollo-events` `apollo-hub` `apollo-journal` `apollo-loc` `apollo-lux-panels` `apollo-seo` `apollo-sheets` `apollo-templates` |
| `edit_posts` | 26 | `apollo-adverts` `apollo-coauthor` `apollo-core` `apollo-djs` `apollo-events` `apollo-journal` `apollo-scheduler` `apollo-sheets` `apollo-templates` |
| `apollo_moderate_content` | 10 | `apollo-events` `apollo-groups` `apollo-mod` `apollo-social` |
| `publish_posts` | 7 | `apollo-djs` `apollo-events` `apollo-journal` `apollo-loc` |
| `list_users` | 5 | `apollo-coauthor` `apollo-users` |
| `administrator` | 4 | `apollo-login` |
| `edit_user` | 4 | `apollo-core` `apollo-dj-sync` `apollo-users` |
| `moderate_comments` | 4 | `apollo-comment` `apollo-events` `apollo-sheets` |
| `read_post` | 4 | `apollo-coauthor` `apollo-core` `apollo-djs` `apollo-events` |
| `edit_users` | 3 | `apollo-scheduler` `apollo-sheets` |
| `upload_files` | 3 | `apollo-core` `apollo-events` `mu-plugin` |
| `delete_posts` | 2 | `apollo-sheets` |
| `edit_others_posts` | 2 | `apollo-coauthor` `apollo-events` |
| `read` | 2 | `apollo-fav` |
| `apollo_access_cena_rio` | 1 | `apollo-events` |
| `apollo_cena_submit_events` | 1 | `apollo-events` |
| `delete_post` | 1 | `apollo-sheets` |
| `delete_users` | 1 | `apollo-coauthor` |
| `edit_comment` | 1 | `apollo-sheets` |
| `manage_categories` | 1 | `apollo-seo` |
| `unfiltered_html` | 1 | `apollo-sheets` |


---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
