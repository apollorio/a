# AJAX and scheduled work

## AJAX actions

79 distinct actions. **`nopriv` means the action is reachable by a
logged-out visitor** — every one of those needs its own nonce and capability
check inside the handler, because WordPress performs none.

| action | units | nopriv | first site |
|---|---|---|---|
| `apollo_admin_adjust_rating` | `apollo-users` | no | `plugins/apollo-users/src/Components/RatingHandler.php:59` |
| `apollo_admin_create_draft` | `apollo-admin` | no | `plugins/apollo-admin/src/AdminPage.php:55` |
| `apollo_admin_delete_rating` | `apollo-users` | no | `plugins/apollo-users/src/Components/RatingHandler.php:60` |
| `apollo_admin_get_voters` | `apollo-users` | no | `plugins/apollo-users/src/Components/RatingHandler.php:58` |
| `apollo_adverts_change_status` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/ajax-handlers.php:62` |
| `apollo_adverts_dashboard_stats` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/ajax-handlers.php:142` |
| `apollo_adverts_gallery_delete` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/gallery.php:266` |
| `apollo_adverts_gallery_reorder` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/gallery.php:295` |
| `apollo_adverts_gallery_set_featured` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/gallery.php:324` |
| `apollo_adverts_gallery_upload` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/gallery.php:233` |
| `apollo_adverts_toggle_featured` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/ajax-handlers.php:92` |
| `apollo_approve_post` | `apollo-admin` | no | `plugins/apollo-admin/src/Frontend/Controller/PendingController.php:24` |
| `apollo_auth_debug_log` | `apollo-login` | **yes** | `plugins/apollo-login/src/Auth/RegisterHandler.php:48` |
| `apollo_auth_nonce` | `apollo-login` | **yes** | `plugins/apollo-login/src/Auth/LoginHandler.php:34` |
| `apollo_blacklist_ip` | `apollo-login` | no | `plugins/apollo-login/src/Security/Firewall.php:86` |
| `apollo_bulk_delete` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Bulk/Manager.php:116` |
| `apollo_bulk_insert` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Bulk/Manager.php:115` |
| `apollo_bulk_load` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Bulk/Manager.php:113` |
| `apollo_bulk_save` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Bulk/Manager.php:114` |
| `apollo_cpanel_save` | `apollo-admin` | no | `plugins/apollo-admin/src/AdminPage.php:52` |
| `apollo_delete_avatar` | `apollo-users` | no | `plugins/apollo-users/src/Components/ProfileHandler.php:30` |
| `apollo_delete_cover` | `apollo-users` | no | `plugins/apollo-users/src/Components/ProfileHandler.php:31` |
| `apollo_delete_depoimento` | `apollo-users` | no | `plugins/apollo-users/src/Components/DepoimentoHandler.php:29` |
| `apollo_dj_save_settings` | `apollo-djs` | no | `plugins/apollo-djs/src/Admin/Dashboard.php:21` |
| `apollo_docs_` | `apollo-docs` | no | `plugins/apollo-docs/src/Admin/Controller.php:35` |
| `apollo_event_save_settings` | `apollo-events` | no | `plugins/apollo-events/src/Admin/Dashboard.php:32` |
| `apollo_fav_load_more` | `apollo-fav` | no | `plugins/apollo-fav/includes/class-dashboard.php:71` |
| `apollo_fav_toggle` | `apollo-fav` | **yes** | `plugins/apollo-fav/includes/class-cbx-bridge.php:46` |
| `apollo_forgot_password` | `apollo-login` | **yes** | `plugins/apollo-login/src/Auth/PasswordReset.php:32` |
| `apollo_frontend_delete_image` | `apollo-templates` | no | `plugins/apollo-templates/src/FrontendEditor.php:81` |
| `apollo_frontend_save` | `apollo-templates` | no | `plugins/apollo-templates/src/FrontendEditor.php:79` |
| `apollo_frontend_upload` | `apollo-templates` | no | `plugins/apollo-templates/src/FrontendEditor.php:80` |
| `apollo_gestor_save_permissions` | `apollo-gestor` | no | `plugins/apollo-gestor/src/Admin/Controller.php:46` |
| `apollo_get_ranking_data` | `apollo-fav` | no | `plugins/apollo-fav/includes/class-fav-ranking.php:48` |
| `apollo_get_ratings` | `apollo-users` | no | `plugins/apollo-users/src/Components/RatingHandler.php:54` |
| `apollo_get_user_stats_widget` | `apollo-statistics` | **yes** | `plugins/apollo-statistics/includes/class-user-stats-widget.php:36` |
| `apollo_get_user_top_sounds` | `apollo-fav` | **yes** | `plugins/apollo-fav/includes/class-fav-ranking.php:49` |
| `apollo_get_voter_list` | `apollo-users` | no | `plugins/apollo-users/src/Components/RatingHandler.php:55` |
| `apollo_login` | `apollo-login` | **yes** | `plugins/apollo-login/src/Auth/LoginHandler.php:31` |
| `apollo_membership_recalc_achievements` | `apollo-membership` | no | `plugins/apollo-membership/includes/ajax-handlers.php:62` |
| `apollo_membership_recalc_points` | `apollo-membership` | no | `plugins/apollo-membership/includes/ajax-handlers.php:21` |
| `apollo_membership_recalc_ranks` | `apollo-membership` | no | `plugins/apollo-membership/includes/ajax-handlers.php:105` |
| `apollo_membership_reset_triggers` | `apollo-membership` | no | `plugins/apollo-membership/includes/ajax-handlers.php:146` |
| `apollo_membership_seed_defaults` | `apollo-membership` | no | `plugins/apollo-membership/includes/ajax-handlers.php:179` |
| `apollo_navbar_login` | `apollo-templates` | **yes** | `plugins/apollo-templates/apollo-templates.php:534` |
| `apollo_navbar_reset_defaults` | `apollo-templates` | no | `plugins/apollo-templates/includes/class-plugin.php:74` |
| `apollo_navbar_upload_image` | `apollo-templates` | no | `plugins/apollo-templates/includes/class-navbar-settings.php:52` |
| `apollo_panel_login` | `apollo-templates` | **yes** | `plugins/apollo-templates/apollo-templates.php:568` |
| `apollo_refresh_weather` | `apollo-templates` | no | `plugins/apollo-templates/includes/weather-helpers.php:259` |
| `apollo_register` | `apollo-login` | **yes** | `plugins/apollo-login/src/Auth/RegisterHandler.php:33` |
| `apollo_reject_post` | `apollo-admin` | no | `plugins/apollo-admin/src/Frontend/Controller/PendingController.php:25` |
| `apollo_report_content` | `apollo-mod` | no | `plugins/apollo-mod/src/Plugin.php:41` |
| `apollo_resend_verification` | `apollo-login` | **yes** | `plugins/apollo-login/src/Auth/EmailVerification.php:48` |
| `apollo_reset_confirm` | `apollo-login` | **yes** | `plugins/apollo-login/src/Auth/PasswordReset.php:34` |
| `apollo_save_test_spreadsheet` | `apollo-templates` | no | `plugins/apollo-templates/includes/pages.php:199` |
| `apollo_sheets_add_col` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Admin/Controller.php:35` |
| `apollo_sheets_add_row` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Admin/Controller.php:34` |
| `apollo_sheets_delete` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Admin/Controller.php:36` |
| `apollo_sheets_export` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Admin/Controller.php:38` |
| `apollo_sheets_export_zip` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Admin/Controller.php:41` |
| `apollo_sheets_import` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Admin/Controller.php:37` |
| `apollo_sheets_preview` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Admin/Controller.php:39` |
| `apollo_sheets_save` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Admin/Controller.php:33` |
| `apollo_sheets_save_settings` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Admin/Controller.php:40` |
| `apollo_sign_` | `apollo-sign` | no | `plugins/apollo-sign/src/Admin/Controller.php:42` |
| `apollo_stats_cleanup_tests` | `apollo-statistics` | no | `plugins/apollo-statistics/src/Admin/TestPanel.php:114` |
| `apollo_stats_run_test` | `apollo-statistics` | no | `plugins/apollo-statistics/src/Admin/TestPanel.php:113` |
| `apollo_submit_depoimento` | `apollo-users` | no | `plugins/apollo-users/src/Components/DepoimentoHandler.php:28` |
| `apollo_submit_rating` | `apollo-users` | no | `plugins/apollo-users/src/Components/RatingHandler.php:53` |
| `apollo_suggest_event` | `apollo-templates` | **yes** | `plugins/apollo-templates/apollo-templates.php:637` |
| `apollo_unblacklist_ip` | `apollo-login` | no | `plugins/apollo-login/src/Security/Firewall.php:87` |
| `apollo_unlock_ip` | `apollo-login` | no | `plugins/apollo-login/src/Security/Lockout.php:63` |
| `apollo_unlock_user` | `apollo-login` | no | `plugins/apollo-login/src/Security/Lockout.php:62` |
| `apollo_update_profile` | `apollo-users` | no | `plugins/apollo-users/src/Components/ProfileHandler.php:27` |
| `apollo_update_stats_visibility` | `apollo-statistics` | no | `plugins/apollo-statistics/includes/class-user-stats-widget.php:40` |
| `apollo_upload_avatar` | `apollo-users` | no | `plugins/apollo-users/src/Components/ProfileHandler.php:28` |
| `apollo_upload_cover` | `apollo-users` | no | `plugins/apollo-users/src/Components/ProfileHandler.php:29` |
| `apollo_validate_cpf` | `apollo-login` | **yes** | `plugins/apollo-login/src/Auth/RegisterHandler.php:44` |
| `heartbeat` | `apollo-membership` | no | `plugins/apollo-membership/includes/points/triggers.php:179` |


## Scheduled events

| hook | unit | call | file:line |
|---|---|---|---|
| `apollo_calendar_seed_holidays` | `apollo-calendar` | `wp_next_scheduled` | `plugins/apollo-calendar/src/Admin/HolidaysAdmin.php:232` |
| `apollo_calendar_seed_holidays` | `apollo-calendar` | `wp_next_scheduled` | `plugins/apollo-calendar/src/Deactivation.php:21` |
| `apollo_calendar_seed_holidays` | `apollo-calendar` | `wp_next_scheduled` | `plugins/apollo-calendar/uninstall.php:34` |
| `apollo_chat_cleanup` | `apollo-chat` | `wp_clear_scheduled_hook` | `plugins/apollo-chat/src/Deactivation.php:11` |
| `apollo_chat_cleanup` | `apollo-chat` | `wp_next_scheduled` | `plugins/apollo-chat/src/Plugin.php:81` |
| `apollo_chat_cleanup` | `apollo-chat` | `wp_schedule_event` | `plugins/apollo-chat/src/Plugin.php:82` |
| `apollo_chat_cleanup` | `apollo-chat` | `wp_next_scheduled` | `plugins/apollo-chat/uninstall.php:34` |
| `apollo_classifieds_check_expired` | `apollo-adverts` | `wp_next_scheduled` | `plugins/apollo-adverts/includes/cron.php:23` |
| `apollo_classifieds_check_expired` | `apollo-adverts` | `wp_schedule_event` | `plugins/apollo-adverts/includes/cron.php:24` |
| `apollo_classifieds_gc_temp` | `apollo-adverts` | `wp_next_scheduled` | `plugins/apollo-adverts/includes/cron.php:33` |
| `apollo_classifieds_gc_temp` | `apollo-adverts` | `wp_schedule_event` | `plugins/apollo-adverts/includes/cron.php:34` |
| `apollo_classifieds_notify_expiring` | `apollo-adverts` | `wp_next_scheduled` | `plugins/apollo-adverts/includes/cron.php:28` |
| `apollo_classifieds_notify_expiring` | `apollo-adverts` | `wp_schedule_event` | `plugins/apollo-adverts/includes/cron.php:29` |
| `apollo_clean_expired_verifs` | `apollo-telegram` | `wp_next_scheduled` | `plugins/apollo-telegram/apollo-telegram.php:321` |
| `apollo_clean_expired_verifs` | `apollo-telegram` | `wp_schedule_event` | `plugins/apollo-telegram/apollo-telegram.php:323` |
| `apollo_clean_expired_verifs` | `apollo-telegram` | `wp_clear_scheduled_hook` | `plugins/apollo-telegram/apollo-telegram.php:353` |
| `apollo_cleanup_audit_log` | `apollo-core` | `wp_next_scheduled` | `plugins/apollo-core/src/Core/ActivationHandler.php:279` |
| `apollo_cleanup_audit_log` | `apollo-core` | `wp_schedule_event` | `plugins/apollo-core/src/Core/ActivationHandler.php:280` |
| `apollo_cleanup_expired_sessions` | `apollo-core` | `wp_next_scheduled` | `plugins/apollo-core/src/Core/ActivationHandler.php:264` |
| `apollo_cleanup_expired_sessions` | `apollo-core` | `wp_schedule_event` | `plugins/apollo-core/src/Core/ActivationHandler.php:265` |
| `apollo_cleanup_old_lockouts` | `apollo-core` | `wp_next_scheduled` | `plugins/apollo-core/src/Core/ActivationHandler.php:269` |
| `apollo_cleanup_old_lockouts` | `apollo-core` | `wp_schedule_event` | `plugins/apollo-core/src/Core/ActivationHandler.php:270` |
| `apollo_daily_ranking_update` | `apollo-templates` | `wp_next_scheduled` | `plugins/apollo-templates/examples/user-radar-examples.php:115` |
| `apollo_daily_ranking_update` | `apollo-templates` | `wp_schedule_event` | `plugins/apollo-templates/examples/user-radar-examples.php:116` |
| `apollo_daily_statistics` | `apollo-core` | `wp_next_scheduled` | `plugins/apollo-core/src/Core/ActivationHandler.php:284` |
| `apollo_daily_statistics` | `apollo-core` | `wp_schedule_event` | `plugins/apollo-core/src/Core/ActivationHandler.php:285` |
| `apollo_email_activated` | `apollo-email` | `wp_next_scheduled` | `plugins/apollo-email/src/Deactivation.php:25` |
| `apollo_email_process_queue` | `apollo-email` | `wp_next_scheduled` | `plugins/apollo-email/uninstall.php:27` |
| `apollo_event_check_expiration` | `apollo-events` | `wp_next_scheduled` | `plugins/apollo-events/src/Activation.php:27` |
| `apollo_event_check_expiration` | `apollo-events` | `wp_next_scheduled` | `plugins/apollo-events/src/Deactivation.php:23` |
| `apollo_event_check_expiration` | `apollo-events` | `wp_next_scheduled` | `plugins/apollo-events/uninstall.php:20` |
| `apollo_fav_daily_stats` | `apollo-fav` | `wp_next_scheduled` | `plugins/apollo-fav/includes/class-statistics-merge.php:44` |
| `apollo_fav_daily_stats` | `apollo-fav` | `wp_schedule_event` | `plugins/apollo-fav/includes/class-statistics-merge.php:45` |
| `apollo_five_minutes` | `apollo-email` | `wp_next_scheduled` | `plugins/apollo-email/src/Activation.php:196` |
| `apollo_five_minutes` | `apollo-email` | `wp_schedule_event` | `plugins/apollo-email/src/Activation.php:197` |
| `apollo_five_minutes` | `apollo-email` | `wp_next_scheduled` | `plugins/apollo-email/src/Core/Cron.php:59` |
| `apollo_five_minutes` | `apollo-email` | `wp_schedule_event` | `plugins/apollo-email/src/Core/Cron.php:60` |
| `apollo_five_minutes` | `apollo-gestor` | `wp_next_scheduled` | `plugins/apollo-gestor/src/Cron/TaskReminderCron.php:67` |
| `apollo_five_minutes` | `apollo-gestor` | `wp_schedule_event` | `plugins/apollo-gestor/src/Cron/TaskReminderCron.php:68` |
| `apollo_journal_daily_digest` | `apollo-journal` | `wp_clear_scheduled_hook` | `plugins/apollo-journal/src/Deactivation.php:44` |
| `apollo_journal_retry_nrep` | `apollo-journal` | `wp_schedule_single_event` | `plugins/apollo-journal/src/NREP.php:104` |
| `apollo_jwt_purge_expired` | `apollo-login` | `wp_next_scheduled` | `plugins/apollo-login/src/Security/JWTAuth.php:96` |
| `apollo_jwt_purge_expired` | `apollo-login` | `wp_schedule_event` | `plugins/apollo-login/src/Security/JWTAuth.php:97` |
| `apollo_login_pending_registration_cleanup` | `apollo-login` | `wp_next_scheduled` | `plugins/apollo-login/src/Auth/PendingRegistrationCleanup.php:21` |
| `apollo_login_pending_registration_cleanup` | `apollo-login` | `wp_schedule_event` | `plugins/apollo-login/src/Auth/PendingRegistrationCleanup.php:22` |
| `apollo_login_purge_unverified_users` | `apollo-login` | `wp_next_scheduled` | `plugins/apollo-login/src/Activation.php:48` |
| `apollo_login_purge_unverified_users` | `apollo-login` | `wp_schedule_event` | `plugins/apollo-login/src/Activation.php:49` |
| `apollo_matchmaking_incremental` | `apollo-users` | `wp_next_scheduled` | `plugins/apollo-users/src/Activation.php:331` |
| `apollo_matchmaking_incremental` | `apollo-users` | `wp_clear_scheduled_hook` | `plugins/apollo-users/src/Deactivation.php:33` |
| `apollo_matchmaking_recompute` | `apollo-users` | `wp_next_scheduled` | `plugins/apollo-users/src/Activation.php:321` |
| `apollo_matchmaking_recompute` | `apollo-users` | `wp_schedule_event` | `plugins/apollo-users/src/Activation.php:327` |
| `apollo_matchmaking_recompute` | `apollo-users` | `wp_clear_scheduled_hook` | `plugins/apollo-users/src/Deactivation.php:32` |
| `apollo_membership_daily_check` | `apollo-membership` | `wp_clear_scheduled_hook` | `plugins/apollo-membership/src/Deactivation.php:24` |
| `apollo_membership_daily_cleanup` | `apollo-membership` | `wp_clear_scheduled_hook` | `plugins/apollo-membership/uninstall.php:76` |
| `apollo_membership_weekly_recap` | `apollo-membership` | `wp_clear_scheduled_hook` | `plugins/apollo-membership/uninstall.php:77` |
| `apollo_newsletter_send_scheduled` | `apollo-email` | `wp_next_scheduled` | `plugins/apollo-email/src/Newsletter.php:73` |
| `apollo_newsletter_send_scheduled` | `apollo-email` | `wp_schedule_event` | `plugins/apollo-email/src/Newsletter.php:74` |
| `apollo_newsletter_send_scheduled` | `apollo-email` | `wp_next_scheduled` | `plugins/apollo-email/uninstall.php:65` |
| `apollo_notif_cleanup` | `apollo-notif` | `wp_clear_scheduled_hook` | `plugins/apollo-notif/src/Deactivation.php:11` |
| `apollo_notif_cleanup` | `apollo-notif` | `wp_next_scheduled` | `plugins/apollo-notif/src/Plugin.php:91` |
| `apollo_notif_cleanup` | `apollo-notif` | `wp_schedule_event` | `plugins/apollo-notif/src/Plugin.php:92` |
| `apollo_notif_digest_dispatch` | `apollo-notif` | `wp_clear_scheduled_hook` | `plugins/apollo-notif/src/Deactivation.php:12` |
| `apollo_notif_digest_dispatch` | `apollo-notif` | `wp_next_scheduled` | `plugins/apollo-notif/src/Plugin.php:97` |
| `apollo_notif_digest_dispatch` | `apollo-notif` | `wp_schedule_event` | `plugins/apollo-notif/src/Plugin.php:98` |
| `apollo_process_email_queue` | `apollo-core` | `wp_next_scheduled` | `plugins/apollo-core/src/Core/ActivationHandler.php:274` |
| `apollo_remind_daily_cleanup` | `apollo-remind` | `wp_clear_scheduled_hook` | `plugins/apollo-remind/src/Core/Activation.php:19` |
| `apollo_remind_daily_cleanup` | `apollo-remind` | `wp_next_scheduled` | `plugins/apollo-remind/src/Core/Activation.php:101` |
| `apollo_remind_daily_cleanup` | `apollo-remind` | `wp_schedule_event` | `plugins/apollo-remind/src/Core/Activation.php:102` |
| `apollo_remind_daily_cleanup` | `apollo-remind` | `wp_clear_scheduled_hook` | `plugins/apollo-remind/uninstall.php:48` |
| `apollo_remind_process_queue` | `apollo-remind` | `wp_clear_scheduled_hook` | `plugins/apollo-remind/src/Core/Activation.php:18` |
| `apollo_remind_process_queue` | `apollo-remind` | `wp_next_scheduled` | `plugins/apollo-remind/src/Core/Activation.php:98` |
| `apollo_remind_process_queue` | `apollo-remind` | `wp_clear_scheduled_hook` | `plugins/apollo-remind/uninstall.php:47` |
| `apollo_remind_two_minutes` | `apollo-remind` | `wp_schedule_event` | `plugins/apollo-remind/src/Core/Activation.php:99` |
| `apollo_stats_cron_events_reminder_email` | `apollo-statistics` | `wp_clear_scheduled_hook` | `plugins/apollo-statistics/apollo-statistics.php:294` |
| `apollo_stats_cron_weekly_roundup_email` | `apollo-statistics` | `wp_clear_scheduled_hook` | `plugins/apollo-statistics/apollo-statistics.php:295` |
| `apollo_stats_daily_aggregate` | `apollo-statistics` | `wp_clear_scheduled_hook` | `plugins/apollo-statistics/apollo-statistics.php:291` |
| `apollo_stats_daily_aggregate` | `apollo-statistics` | `wp_next_scheduled` | `plugins/apollo-statistics/src/Admin/TestPanel.php:560` |
| `apollo_stats_daily_aggregate` | `apollo-statistics` | `wp_next_scheduled` | `plugins/apollo-statistics/src/Collectors/CronCollector.php:34` |
| `apollo_stats_daily_aggregate` | `apollo-statistics` | `wp_schedule_event` | `plugins/apollo-statistics/src/Collectors/CronCollector.php:35` |
| `apollo_stats_daily_collect` | `apollo-statistics` | `wp_clear_scheduled_hook` | `plugins/apollo-statistics/apollo-statistics.php:290` |
| `apollo_stats_daily_collect` | `apollo-statistics` | `wp_next_scheduled` | `plugins/apollo-statistics/includes/class-data-collector.php:67` |
| `apollo_stats_daily_collect` | `apollo-statistics` | `wp_schedule_event` | `plugins/apollo-statistics/includes/class-data-collector.php:68` |
| `apollo_stats_session_cleanup` | `apollo-statistics` | `wp_clear_scheduled_hook` | `plugins/apollo-statistics/apollo-statistics.php:293` |
| `apollo_stats_session_cleanup` | `apollo-statistics` | `wp_next_scheduled` | `plugins/apollo-statistics/src/Collectors/SessionCollector.php:31` |
| `apollo_stats_session_cleanup` | `apollo-statistics` | `wp_schedule_event` | `plugins/apollo-statistics/src/Collectors/SessionCollector.php:32` |
| `apollo_stats_weekly_rotate` | `apollo-statistics` | `wp_clear_scheduled_hook` | `plugins/apollo-statistics/apollo-statistics.php:292` |
| `apollo_stats_weekly_rotate` | `apollo-statistics` | `wp_next_scheduled` | `plugins/apollo-statistics/src/Admin/TestPanel.php:566` |
| `apollo_stats_weekly_rotate` | `apollo-statistics` | `wp_next_scheduled` | `plugins/apollo-statistics/src/Collectors/CronCollector.php:40` |
| `apollo_stats_weekly_rotate` | `apollo-statistics` | `wp_schedule_event` | `plugins/apollo-statistics/src/Collectors/CronCollector.php:41` |
| `Ativo` | `apollo-email` | `wp_next_scheduled` | `plugins/apollo-email/views/admin/settings.php:82` |
| `every_five_minutes` | `apollo-events` | `wp_schedule_event` | `plugins/apollo-events/src/Activation.php:28` |
| `five_minutes` | `apollo-core` | `wp_schedule_event` | `plugins/apollo-core/src/Core/ActivationHandler.php:275` |
| `five_minutes` | `apollo-users` | `wp_schedule_event` | `plugins/apollo-users/src/Activation.php:332` |
| `yearly` | `apollo-calendar` | `wp_schedule_event` | `plugins/apollo-calendar/src/Admin/HolidaysAdmin.php:233` |


---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
