# Hooks

552 distinct hooks published (`do_action` / `apply_filters`), 
537 distinct hooks consumed (`add_action` / `add_filter`).

## Apollo-namespaced hooks published

These are the ecosystem's own extension points — the contract other plugins
may bind to. A hook with **0 listeners** is a published contract nobody uses.

| hook | kind | publishers | listeners | first site |
|---|---|---|---|---|
| `APOLLO_TELEGRAM_before_execute_command` | apply_filters | 1 | 1 | `plugins/apollo-telegram/src/Telegram/ExtendedClasses/Telegram.php:416` |
| `APOLLO_TELEGRAM_before_get_commands_list` | apply_filters | 1 | **0** | `plugins/apollo-telegram/src/Telegram/ExtendedClasses/Telegram.php:369` |
| `APOLLO_TELEGRAM_before_validate_settings` | apply_filters | 1 | **0** | `plugins/apollo-telegram/src/Menus/Base.php:99` |
| `APOLLO_TELEGRAM_menus_main_fields` | apply_filters | 1 | **0** | `plugins/apollo-telegram/src/Menus/MainMenu.php:50` |
| `APOLLO_TELEGRAM_menus_main_tabs` | apply_filters | 1 | **0** | `plugins/apollo-telegram/src/Menus/MainMenu.php:37` |
| `APOLLO_TELEGRAM_menus_second_fields` | apply_filters | 1 | **0** | `plugins/apollo-telegram/src/Menus/SecondMenu.php:58` |
| `APOLLO_TELEGRAM_menus_submenus` | apply_filters | 1 | 1 | `plugins/apollo-telegram/src/Menu.php:61` |
| `APOLLO_TELEGRAM_validate_input_$key` | apply_filters | 1 | **0** | `plugins/apollo-telegram/src/Menus/Base.php:105` |
| `APOLLO_TELEGRAM_validate_settings` | do_action | 1 | **0** | `plugins/apollo-telegram/src/Menus/Base.php:113` |
| `apollo/admin/draft_created` | do_action | 1 | **0** | `plugins/apollo-admin/src/Admin/DraftHandler.php:75` |
| `apollo/admin/post_approved` | do_action | 2 | **0** | `plugins/apollo-admin/src/Frontend/Controller/PendingController.php:126` |
| `apollo/admin/post_rejected` | do_action | 2 | **0** | `plugins/apollo-admin/src/Frontend/Controller/PendingController.php:140` |
| `apollo/admin/report_submitted` | do_action | 1 | **0** | `plugins/apollo-admin/src/Admin/DraftHandler.php:97` |
| `apollo/admin/settings_saved` | do_action | 1 | **0** | `plugins/apollo-admin/src/Admin/SaveHandler.php:76` |
| `apollo/adverts/event_resynced` | do_action | 1 | **0** | `plugins/apollo-adverts/includes/cpt.php:411` |
| `apollo/adverts/form/load` | apply_filters | 1 | 3 | `plugins/apollo-adverts/src/Form.php:83` |
| `apollo/adverts/form/load/{$scheme}` | apply_filters | 1 | **0** | `plugins/apollo-adverts/src/Form.php:84` |
| `apollo/adverts/render_create_form` | do_action | 1 | 1 | `plugins/apollo-templates/templates/template-parts/new-home/panel-forms.php:115` |
| `apollo/brain/after_apollo_core_file` | do_action | 1 | **0** | `mu-plugin/apollo-brain.php:160` |
| `apollo/brain/before_apollo_core` | do_action | 1 | 1 | `mu-plugin/apollo-brain.php:156` |
| `apollo/brain/bootstrap_complete` | do_action | 1 | **0** | `mu-plugin/apollo-brain.php:170` |
| `apollo/brain/core_ready` | do_action | 1 | 1 | `mu-plugin/apollo-brain.php:358` |
| `apollo/brain/defer_protected_fragments` | apply_filters | 1 | **0** | `mu-plugin/apollo-brain.php:333` |
| `apollo/brain/defer_protected_handles` | apply_filters | 1 | **0** | `mu-plugin/apollo-brain.php:320` |
| `apollo/brain/loaded` | do_action | 1 | **0** | `mu-plugin/apollo-brain.php:599` |
| `apollo/brain/page_cache_maybe_init` | do_action | 1 | **0** | `mu-plugin/apollo-brain.php:242` |
| `apollo/canvas/before_close` | do_action | 1 | 2 | `plugins/apollo-core/includes/document-head.php:294` |
| `apollo/canvas/head` | do_action | 2 | 2 | `plugins/apollo-core/includes/document-head.php:192` |
| `apollo/casa/cells` | apply_filters | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/cells/_manifest.php:105` |
| `apollo/chat/bad_words` | apply_filters | 2 | **0** | `plugins/apollo-chat/includes/functions.php:1809` |
| `apollo/chat/flood_max_messages` | apply_filters | 1 | **0** | `plugins/apollo-chat/includes/functions.php:1770` |
| `apollo/chat/flood_window_seconds` | apply_filters | 1 | **0** | `plugins/apollo-chat/includes/functions.php:1771` |
| `apollo/chat/message_sent` | do_action | 1 | 3 | `plugins/apollo-chat/includes/functions.php:299` |
| `apollo/chat/render_list` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-chat-list.php:62` |
| `apollo/chat/render_panel` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-chat.php:28` |
| `apollo/chat/render_thread` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-chat-inbox.php:60` |
| `apollo/classifieds/created` | do_action | 1 | 3 | `plugins/apollo-adverts/src/API/ClassifiedsController.php:231` |
| `apollo/classifieds/deleted` | do_action | 2 | **0** | `plugins/apollo-adverts/includes/buddypress.php:443` |
| `apollo/classifieds/expiration_days` | apply_filters | 1 | **0** | `plugins/apollo-adverts/includes/functions.php:185` |
| `apollo/classifieds/expired` | do_action | 1 | 1 | `plugins/apollo-adverts/includes/cron.php:75` |
| `apollo/classifieds/expiring_soon` | do_action | 1 | **0** | `plugins/apollo-adverts/includes/cron.php:122` |
| `apollo/classifieds/featured` | do_action | 1 | **0** | `plugins/apollo-adverts/includes/ajax-handlers.php:83` |
| `apollo/classifieds/list_query_args` | apply_filters | 1 | **0** | `plugins/apollo-adverts/includes/shortcodes/apollo-classifieds-list.php:153` |
| `apollo/classifieds/max_active_listings` | apply_filters | 1 | **0** | `plugins/apollo-adverts/src/LimitActiveListings.php:147` |
| `apollo/classifieds/price_format` | apply_filters | 1 | **0** | `plugins/apollo-adverts/includes/functions.php:121` |
| `apollo/classifieds/renewed` | do_action | 1 | 1 | `plugins/apollo-adverts/includes/buddypress.php:417` |
| `apollo/classifieds/search_query_args` | apply_filters | 1 | **0** | `plugins/apollo-adverts/src/API/SearchController.php:144` |
| `apollo/classifieds/spam_detected` | do_action | 3 | 1 | `plugins/apollo-adverts/src/SpamProtection.php:102` |
| `apollo/classifieds/status_toggled` | do_action | 1 | **0** | `plugins/apollo-adverts/includes/buddypress.php:497` |
| `apollo/classifieds/updated` | do_action | 1 | 1 | `plugins/apollo-adverts/src/API/ClassifiedsController.php:280` |
| `apollo/coauthor/activated` | do_action | 1 | **0** | `plugins/apollo-coauthor/src/Activation.php:60` |
| `apollo/coauthor/added` | do_action | 2 | 1 | `plugins/apollo-coauthor/includes/functions.php:227` |
| `apollo/coauthor/append_byline` | apply_filters | 1 | 1 | `plugins/apollo-coauthor/src/Components/ContentFilter.php:132` |
| `apollo/coauthor/can_add` | apply_filters | 1 | **0** | `plugins/apollo-coauthor/includes/functions.php:63` |
| `apollo/coauthor/cleared` | do_action | 2 | **0** | `plugins/apollo-coauthor/src/Components/MetaBox.php:183` |
| `apollo/coauthor/comment_approved` | do_action | 1 | **0** | `plugins/apollo-coauthor/src/Components/NotifyCoauthors.php:153` |
| `apollo/coauthor/count_post_types` | apply_filters | 1 | **0** | `plugins/apollo-coauthor/src/Components/UserPostCount.php:73` |
| `apollo/coauthor/deactivated` | do_action | 1 | **0** | `plugins/apollo-coauthor/src/Deactivation.php:42` |
| `apollo/coauthor/display_format` | apply_filters | 1 | **0** | `plugins/apollo-coauthor/includes/functions.php:199` |
| `apollo/coauthor/guest_created` | do_action | 1 | **0** | `plugins/apollo-coauthor/src/Components/GuestAuthors.php:670` |
| `apollo/coauthor/guest_saved` | do_action | 1 | **0** | `plugins/apollo-coauthor/src/Components/GuestAuthors.php:429` |
| `apollo/coauthor/names_separator` | apply_filters | 1 | **0** | `plugins/apollo-coauthor/src/Components/ContentFilter.php:73` |
| `apollo/coauthor/removed` | do_action | 1 | **0** | `plugins/apollo-coauthor/includes/functions.php:267` |
| `apollo/coauthor/supported_post_types` | apply_filters | 1 | **0** | `plugins/apollo-coauthor/includes/functions.php:343` |
| `apollo/coauthor/updated` | do_action | 1 | **0** | `plugins/apollo-coauthor/includes/functions.php:137` |
| `apollo/color_input/enqueue_admin` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/color-input.php:124` |
| `apollo/color_input/enqueue_frontend` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/color-input.php:130` |
| `apollo/comment/initialized` | do_action | 1 | **0** | `plugins/apollo-comment/src/Plugin.php:54` |
| `apollo/comment/render_depoimento_form` | do_action | 1 | 1 | `plugins/apollo-templates/templates/template-parts/new-home/panel-forms.php:136` |
| `apollo/core/activated` | do_action | 1 | **0** | `plugins/apollo-core/src/Core/ActivationHandler.php:86` |
| `apollo/core/shortcodes/register` | do_action | 1 | **0** | `plugins/apollo-core/src/Core/ShortcodeRegistry.php:412` |
| `apollo/debug` | do_action | 1 | **0** | `mu-plugin/apollo-debug.php:129` |
| `apollo/depoimento/allowed_post_types` | apply_filters | 1 | 1 | `plugins/apollo-comment/src/API/DepoimentoController.php:200` |
| `apollo/depoimento/created` | do_action | 1 | 1 | `plugins/apollo-comment/src/API/DepoimentoController.php:230` |
| `apollo/depoimento/deleted` | do_action | 1 | **0** | `plugins/apollo-comment/src/API/DepoimentoController.php:321` |
| `apollo/depoimento/query_args` | apply_filters | 1 | **0** | `plugins/apollo-comment/src/Depoimento.php:240` |
| `apollo/depoimento/updated` | do_action | 1 | **0** | `plugins/apollo-comment/src/API/DepoimentoController.php:279` |
| `apollo/detail/render_panel` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-detail.php:78` |
| `apollo/dj/session_logged` | do_action | 1 | **0** | `plugins/apollo-dj-sync/src/API/DJPermissionsController.php:115` |
| `apollo/djs/after_bio` | do_action | 1 | **0** | `plugins/apollo-djs/templates/parts/dj-v3/bio.php:90` |
| `apollo/djs/depoimentos` | apply_filters | 1 | **0** | `plugins/apollo-djs/templates/parts/dj-v3/depoimentos.php:59` |
| `apollo/docs/created` | do_action | 1 | **0** | `plugins/apollo-docs/src/Model/Document.php:87` |
| `apollo/docs/deleted` | do_action | 1 | **0** | `plugins/apollo-docs/src/Model/Document.php:307` |
| `apollo/docs/finalized` | do_action | 1 | 1 | `plugins/apollo-docs/src/Model/Document.php:345` |
| `apollo/docs/group_access` | apply_filters | 1 | **0** | `plugins/apollo-docs/src/Model/Document.php:513` |
| `apollo/docs/initialized` | do_action | 1 | **0** | `plugins/apollo-docs/src/Plugin.php:60` |
| `apollo/docs/locked` | do_action | 1 | **0** | `plugins/apollo-docs/src/Model/Document.php:322` |
| `apollo/docs/signed` | do_action | 1 | **0** | `plugins/apollo-docs/src/Model/Document.php:355` |
| `apollo/docs/updated` | do_action | 1 | **0** | `plugins/apollo-docs/src/Model/Document.php:164` |
| `apollo/dynamic/after_content` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-dynamic.php:79` |
| `apollo/email/before_send` | do_action | 1 | **0** | `plugins/apollo-email/src/Mailer/Sender.php:125` |
| `apollo/email/digest/chat` | do_action | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1296` |
| `apollo/email/digest/comuna` | do_action | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1297` |
| `apollo/email/digest/event_match` | do_action | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1295` |
| `apollo/email/digest/fav_events` | do_action | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1294` |
| `apollo/email/digest/news` | do_action | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1298` |
| `apollo/email/digest/social` | do_action | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1299` |
| `apollo/email/failed` | do_action | 1 | 2 | `plugins/apollo-email/src/Mailer/Sender.php:196` |
| `apollo/email/from_address` | apply_filters | 1 | **0** | `plugins/apollo-email/src/Plugin.php:278` |
| `apollo/email/from_name` | apply_filters | 1 | **0** | `plugins/apollo-email/src/Plugin.php:269` |
| `apollo/email/headers` | apply_filters | 1 | 1 | `plugins/apollo-email/src/Mailer/Sender.php:140` |
| `apollo/email/init` | do_action | 1 | **0** | `plugins/apollo-email/src/Plugin.php:140` |
| `apollo/email/loaded` | do_action | 1 | 1 | `plugins/apollo-email/apollo-email.php:137` |
| `apollo/email/opened` | do_action | 1 | 2 | `plugins/apollo-email/src/Log/Logger.php:99` |
| `apollo/email/queue_processed` | do_action | 1 | 1 | `plugins/apollo-email/src/Mailer/Queue.php:181` |
| `apollo/email/queued` | do_action | 1 | 1 | `plugins/apollo-email/src/Mailer/Queue.php:86` |
| `apollo/email/retry_delay_base` | apply_filters | 1 | **0** | `plugins/apollo-email/src/Mailer/SmtpDelivery.php:94` |
| `apollo/email/sent` | do_action | 1 | 3 | `plugins/apollo-email/src/Mailer/Sender.php:166` |
| `apollo/email/template_data` | apply_filters | 1 | **0** | `plugins/apollo-email/src/Template/TemplateEngine.php:59` |
| `apollo/email/unsubscribed` | do_action | 1 | **0** | `plugins/apollo-email/src/Plugin.php:515` |
| `apollo/error/redirect_url` | apply_filters | 1 | **0** | `mu-plugin/apollo-error-handler.php:175` |
| `apollo/error/should_intercept` | apply_filters | 1 | 2 | `mu-plugin/apollo-error-handler.php:140` |
| `apollo/event/before-delete` | do_action | 1 | **0** | `plugins/apollo-events/src/API/EventsController.php:712` |
| `apollo/event/checked_in` | do_action | 1 | **0** | `plugins/apollo-events/src/API/EventsController.php:1459` |
| `apollo/event/cloned` | do_action | 1 | **0** | `plugins/apollo-events/src/API/EventsController.php:1188` |
| `apollo/event/created` | do_action | 1 | 1 | `plugins/apollo-events/src/API/EventsController.php:624` |
| `apollo/event/dashboard_transactions` | apply_filters | 1 | **0** | `plugins/apollo-events/styles/base/dashboard-event.php:75` |
| `apollo/event/deleted` | do_action | 1 | 1 | `plugins/apollo-events/src/API/EventsController.php:720` |
| `apollo/event/dj-added` | do_action | 2 | **0** | `plugins/apollo-events/src/API/EventsController.php:945` |
| `apollo/event/dj-removed` | do_action | 1 | **0** | `plugins/apollo-events/src/API/EventsController.php:982` |
| `apollo/event/fab_links` | apply_filters | 1 | **0** | `plugins/apollo-events/styles/base/template-parts/shared/fab-menu.php:95` |
| `apollo/event/gone` | do_action | 1 | 1 | `plugins/apollo-events/src/Integrations.php:169` |
| `apollo/event/iframe_proxy_map` | apply_filters | 1 | **0** | `plugins/apollo-events/includes/iframe-proxy.php:63` |
| `apollo/event/published` | do_action | 1 | 4 | `plugins/apollo-events/src/Registry.php:456` |
| `apollo/event/reminder` | do_action | 1 | **0** | `plugins/apollo-events/src/Expiration.php:259` |
| `apollo/event/rsvp` | do_action | 1 | 3 | `plugins/apollo-events/src/API/EventsController.php:1329` |
| `apollo/event/rsvp_cancelled` | do_action | 1 | 1 | `plugins/apollo-events/src/API/EventsController.php:1377` |
| `apollo/event/structured_data` | apply_filters | 1 | **0** | `plugins/apollo-events/src/StructuredData.php:243` |
| `apollo/event/updated` | do_action | 1 | 1 | `plugins/apollo-events/src/API/EventsController.php:697` |
| `apollo/events/render_create_form` | do_action | 1 | 1 | `plugins/apollo-templates/templates/template-parts/new-home/panel-forms.php:108` |
| `apollo/events/request_created` | do_action | 1 | **0** | `plugins/apollo-adverts/includes/event-selector.php:868` |
| `apollo/events/suggestion_created` | do_action | 1 | **0** | `plugins/apollo-templates/apollo-templates.php:633` |
| `apollo/events/team_updated` | do_action | 2 | **0** | `plugins/apollo-events/includes/functions.php:1258` |
| `apollo/explore/after_feed` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-explore.php:76` |
| `apollo/fav/added` | do_action | 1 | 4 | `plugins/apollo-fav/includes/functions.php:88` |
| `apollo/fav/allow_toggle` | apply_filters | 1 | **0** | `plugins/apollo-fav/includes/class-cbx-bridge.php:139` |
| `apollo/fav/fanboy_triggered` | do_action | 1 | **0** | `plugins/apollo-fav/includes/class-notif-triggers.php:192` |
| `apollo/fav/hype_triggered` | do_action | 1 | **0** | `plugins/apollo-fav/includes/class-notif-triggers.php:432` |
| `apollo/fav/marketplace_triggered` | do_action | 1 | **0** | `plugins/apollo-fav/includes/class-notif-triggers.php:283` |
| `apollo/fav/removed` | do_action | 1 | 2 | `plugins/apollo-fav/includes/functions.php:138` |
| `apollo/fav/removing` | do_action | 1 | **0** | `plugins/apollo-fav/includes/functions.php:122` |
| `apollo/fav/supported_cpts` | apply_filters | 1 | **0** | `plugins/apollo-fav/includes/class-cbx-bridge.php:56` |
| `apollo/feed/after_content` | do_action | 1 | 1 | `plugins/apollo-templates/templates/_legacy/page-feed.monolith.php:1127` |
| `apollo/firewall/blocked` | do_action | 1 | **0** | `plugins/apollo-login/src/Security/Firewall.php:447` |
| `apollo/form/admin_only_keys` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/form-schema.php:125` |
| `apollo/form/label` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/form-schema.php:216` |
| `apollo/form/saved` | do_action | 1 | **0** | `plugins/apollo-core/includes/form-schema.php:307` |
| `apollo/form/schema` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/form-schema.php:192` |
| `apollo/forms/extra_nav_items` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-forms.php:98` |
| `apollo/forms/extra_sections` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-forms.php:139` |
| `apollo/gestor/event_status_changed` | do_action | 1 | **0** | `plugins/apollo-gestor/includes/modules/projetos/backend/Projetos.php:193` |
| `apollo/gestor/income_before_delete` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Income.php:140` |
| `apollo/gestor/income_created` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Income.php:118` |
| `apollo/gestor/initialized` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Plugin.php:75` |
| `apollo/gestor/milestone_before_delete` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Milestone.php:151` |
| `apollo/gestor/milestone_created` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Milestone.php:85` |
| `apollo/gestor/milestone_from_appointment` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Bridge/CalendarBridge.php:86` |
| `apollo/gestor/milestone_toggled` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Milestone.php:135` |
| `apollo/gestor/modules_loaded` | do_action | 1 | **0** | `plugins/apollo-gestor/includes/core/Module_Manager.php:46` |
| `apollo/gestor/payment_before_delete` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Payment.php:222` |
| `apollo/gestor/payment_created` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Payment.php:158` |
| `apollo/gestor/payment_updated` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Payment.php:205` |
| `apollo/gestor/task_before_delete` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Task.php:226` |
| `apollo/gestor/task_created` | do_action | 1 | 1 | `plugins/apollo-gestor/src/Model/Task.php:139` |
| `apollo/gestor/task_reminder` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Cron/TaskReminderCron.php:161` |
| `apollo/gestor/task_updated` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Task.php:188` |
| `apollo/gestor/team_member_added` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Team.php:86` |
| `apollo/gestor/team_member_before_remove` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Team.php:162` |
| `apollo/gestor/team_member_updated` | do_action | 1 | **0** | `plugins/apollo-gestor/src/Model/Team.php:140` |
| `apollo/groups/admins_assigned` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:287` |
| `apollo/groups/allow_system_nucleo` | apply_filters | 1 | **0** | `plugins/apollo-groups/includes/functions.php:186` |
| `apollo/groups/avatar_updated` | do_action | 1 | **0** | `plugins/apollo-groups/src/Plugin.php:1419` |
| `apollo/groups/cover_updated` | do_action | 1 | **0** | `plugins/apollo-groups/src/Plugin.php:1520` |
| `apollo/groups/created` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:304` |
| `apollo/groups/group_deleted` | do_action | 1 | **0** | `plugins/apollo-groups/src/Plugin.php:692` |
| `apollo/groups/group_deleting` | do_action | 1 | **0** | `plugins/apollo-groups/src/Plugin.php:686` |
| `apollo/groups/invite_accepted` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:922` |
| `apollo/groups/member_banned` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:706` |
| `apollo/groups/member_demoted` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:654` |
| `apollo/groups/member_left` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:372` |
| `apollo/groups/member_promoted` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:605` |
| `apollo/groups/member_unbanned` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:750` |
| `apollo/groups/membership_requested` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:1065` |
| `apollo/groups/ownership_transferred` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:1366` |
| `apollo/groups/render_create_form` | do_action | 1 | 1 | `plugins/apollo-templates/templates/template-parts/new-home/panel-forms.php:122` |
| `apollo/groups/request_accepted` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:1127` |
| `apollo/groups/user_invited` | do_action | 1 | **0** | `plugins/apollo-groups/includes/functions.php:855` |
| `apollo/groups/user_joined` | do_action | 1 | 3 | `plugins/apollo-groups/includes/functions.php:345` |
| `apollo/home/after_content` | do_action | 2 | **0** | `plugins/apollo-templates/templates/page-home.php:1186` |
| `apollo/home/before_content` | do_action | 2 | **0** | `plugins/apollo-templates/templates/page-home.php:46` |
| `apollo/home/head` | do_action | 2 | **0** | `plugins/apollo-templates/templates/page-home.php:786` |
| `apollo/home/marquee_items` | apply_filters | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/marquee.php:89` |
| `apollo/hub/activated` | do_action | 1 | **0** | `plugins/apollo-hub/src/Activation.php:34` |
| `apollo/hub/auto_provision` | apply_filters | 1 | **0** | `plugins/apollo-hub/src/Integrations.php:89` |
| `apollo/hub/blocks_updated` | do_action | 1 | **0** | `plugins/apollo-hub/src/API/HubController.php:389` |
| `apollo/hub/created` | do_action | 1 | 1 | `plugins/apollo-hub/src/Integrations.php:112` |
| `apollo/hub/links_updated` | do_action | 1 | **0** | `plugins/apollo-hub/src/API/HubController.php:456` |
| `apollo/hub/migrated` | do_action | 1 | **0** | `plugins/apollo-hub/src/Migration.php:132` |
| `apollo/hub/rest_response` | apply_filters | 1 | **0** | `plugins/apollo-hub/src/API/HubController.php:601` |
| `apollo/hub/updated` | do_action | 1 | **0** | `plugins/apollo-hub/src/API/HubController.php:344` |
| `apollo/import/providers` | apply_filters | 1 | **0** | `plugins/apollo-events/src/Import/ProviderRegistry.php:33` |
| `apollo/journal/initialized` | do_action | 1 | **0** | `plugins/apollo-journal/src/Plugin.php:101` |
| `apollo/journal/news_per_page` | apply_filters | 1 | **0** | `plugins/apollo-journal/templates/page-jornal.php:18` |
| `apollo/journal/nota_per_page` | apply_filters | 1 | **0** | `plugins/apollo-journal/templates/page-jornal.php:19` |
| `apollo/journal/nrep_assigned` | do_action | 1 | 1 | `plugins/apollo-journal/src/NREP.php:129` |
| `apollo/journal/render_jornal` | do_action | 1 | **0** | `plugins/apollo-journal/templates/page-jornal.php:572` |
| `apollo/journal/render_news` | do_action | 1 | **0** | `plugins/apollo-journal/templates/single-journal_news.php:484` |
| `apollo/journal/render_nota` | do_action | 1 | **0** | `plugins/apollo-journal/templates/single-journal_nota.php:385` |
| `apollo/journal/taxonomies` | apply_filters | 1 | **0** | `plugins/apollo-journal/src/Plugin.php:287` |
| `apollo/listing_header/skins` | apply_filters | 1 | **0** | `plugins/apollo-templates/includes/listing-header-api.php:56` |
| `apollo/loc/before_delete` | do_action | 2 | **0** | `plugins/apollo-loc/src/API/Endpoints/DeleteEndpoint.php:26` |
| `apollo/loc/checkin` | do_action | 1 | **0** | `plugins/apollo-loc/src/Integration/SocialIntegration.php:53` |
| `apollo/loc/core_ready` | do_action | 1 | **0** | `plugins/apollo-loc/src/Integration/CoreIntegration.php:61` |
| `apollo/loc/created` | do_action | 2 | 1 | `plugins/apollo-loc/src/API/Endpoints/CreateEndpoint.php:49` |
| `apollo/loc/geocoded` | do_action | 1 | 1 | `plugins/apollo-loc/src/Geocoder.php:89` |
| `apollo/loc/meta_saved` | do_action | 1 | **0** | `plugins/apollo-loc/src/Admin/Metabox/MetaboxSaver.php:256` |
| `apollo/loc/shared` | do_action | 1 | **0** | `plugins/apollo-loc/src/Integration/SocialIntegration.php:66` |
| `apollo/loc/updated` | do_action | 2 | 1 | `plugins/apollo-loc/src/API/Endpoints/UpdateEndpoint.php:43` |
| `apollo/login/email_verified` | do_action | 1 | 1 | `plugins/apollo-login/includes/functions.php:525` |
| `apollo/login/failed_attempt` | do_action | 1 | 1 | `plugins/apollo-login/src/Auth/LoginHandler.php:309` |
| `apollo/login/jwt_authenticated` | do_action | 1 | **0** | `plugins/apollo-login/src/Security/JWTAuth.php:468` |
| `apollo/login/jwt_issued` | do_action | 1 | **0** | `plugins/apollo-login/src/Security/JWTAuth.php:321` |
| `apollo/login/logged_in` | do_action | 1 | **0** | `plugins/apollo-login/src/Auth/LoginHandler.php:332` |
| `apollo/login/logout_redirect_default` | apply_filters | 1 | **0** | `plugins/apollo-login/includes/logout.php:34` |
| `apollo/login/password_reset_requested` | do_action | 1 | 1 | `plugins/apollo-login/includes/functions.php:675` |
| `apollo/login/registered` | do_action | 2 | 2 | `plugins/apollo-login/src/Auth/RegisterHandler.php:553` |
| `apollo/login/render_panel` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-acesso.php:55` |
| `apollo/login/send_email` | apply_filters | 1 | **0** | `plugins/apollo-login/includes/functions.php:732` |
| `apollo/login/verification_email` | do_action | 1 | 2 | `plugins/apollo-login/includes/functions.php:580` |
| `apollo/lux/saved` | do_action | 1 | **0** | `plugins/apollo-lux-panels/includes/Panel.php:656` |
| `apollo/mapa/after_content` | do_action | 1 | **0** | `plugins/apollo-templates/templates/_legacy/page-mapa.monolith.php:311` |
| `apollo/mapa/head` | do_action | 1 | **0** | `plugins/apollo-templates/templates/_legacy/page-mapa.monolith.php:281` |
| `apollo/matchmaking/batch_completed` | do_action | 1 | **0** | `plugins/apollo-users/src/Components/MatchmakingEngine.php:465` |
| `apollo/matchmaking/score_updated` | do_action | 1 | **0** | `plugins/apollo-users/src/Components/MatchmakingEngine.php:408` |
| `apollo/matchmaking/signal_updated` | do_action | 1 | 1 | `plugins/apollo-users/src/Components/RatingHandler.php:119` |
| `apollo/media-webp/skip_path` | apply_filters | 2 | **0** | `plugins/apollo-core/mu-plugins/apollo-webp-force.php:192` |
| `apollo/membership/achievement_awarded` | do_action | 1 | 1 | `plugins/apollo-membership/includes/bridges.php:174` |
| `apollo/membership/achievement_trigger` | do_action | 1 | **0** | `plugins/apollo-statistics/src/Processors/GamificationBridge.php:79` |
| `apollo/membership/award_session_points` | do_action | 1 | **0** | `plugins/apollo-statistics/src/Processors/GamificationBridge.php:101` |
| `apollo/membership/badge_assigned` | do_action | 1 | 1 | `plugins/apollo-membership/includes/bridges.php:143` |
| `apollo/membership/credit_added` | do_action | 1 | **0** | `plugins/apollo-membership/includes/bridges.php:234` |
| `apollo/membership/level_changed` | do_action | 2 | **0** | `plugins/apollo-membership/bridge-pmpro.php:61` |
| `apollo/membership/points_awarded` | do_action | 1 | 1 | `plugins/apollo-membership/includes/bridges.php:43` |
| `apollo/membership/points_deducted` | do_action | 1 | 1 | `plugins/apollo-membership/includes/bridges.php:68` |
| `apollo/membership/points_reset` | do_action | 1 | **0** | `plugins/apollo-membership/includes/bridges.php:89` |
| `apollo/membership/points_updated` | do_action | 1 | **0** | `plugins/apollo-membership/includes/bridges.php:114` |
| `apollo/membership/rank_awarded` | do_action | 1 | 2 | `plugins/apollo-membership/includes/bridges.php:203` |
| `apollo/meta/registered` | do_action | 1 | **0** | `plugins/apollo-core/src/Core/MetaRegistry.php:2330` |
| `apollo/mobile/cells` | apply_filters | 1 | **0** | `plugins/apollo-templates/templates/template-parts/mobile/_manifest.php:62` |
| `apollo/mobile/enabled` | apply_filters | 1 | **0** | `plugins/apollo-templates/includes/mobile-runtime.php:32` |
| `apollo/mod/action_taken` | do_action | 1 | **0** | `plugins/apollo-mod/includes/functions.php:105` |
| `apollo/mod/render_report_form` | do_action | 1 | 1 | `plugins/apollo-templates/templates/template-parts/new-home/panel-forms.php:129` |
| `apollo/mod/report` | do_action | 1 | **0** | `plugins/apollo-social/src/Plugin.php:934` |
| `apollo/mod/report_resolved` | do_action | 1 | **0** | `plugins/apollo-mod/includes/functions.php:151` |
| `apollo/mod/reported` | do_action | 2 | 1 | `plugins/apollo-mod/includes/functions.php:57` |
| `apollo/modelo/map` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/modelo/modelo.php:67` |
| `apollo/modelo/seeded` | do_action | 1 | **0** | `plugins/apollo-core/includes/modelo/modelo.php:107` |
| `apollo/mural/after_content` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-mural.php:54` |
| `apollo/notif/admin_login_alert` | apply_filters | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:756` |
| `apollo/notif/created` | do_action | 1 | 1 | `plugins/apollo-notif/includes/functions.php:102` |
| `apollo/notif/deleted` | do_action | 1 | **0** | `plugins/apollo-notif/includes/functions.php:270` |
| `apollo/notif/deleted_all_read` | do_action | 1 | **0** | `plugins/apollo-notif/includes/functions.php:292` |
| `apollo/notif/digest` | do_action | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1289` |
| `apollo/notif/digest/batch_size` | apply_filters | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1260` |
| `apollo/notif/digest/chat_items` | apply_filters | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1349` |
| `apollo/notif/digest/classify_segments` | apply_filters | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1450` |
| `apollo/notif/digest/comuna_items` | apply_filters | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1350` |
| `apollo/notif/digest/event_match_items` | apply_filters | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1348` |
| `apollo/notif/digest/fav_events_items` | apply_filters | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1347` |
| `apollo/notif/digest/news_items` | apply_filters | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1351` |
| `apollo/notif/digest/notifications_items` | apply_filters | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1346` |
| `apollo/notif/digest/social_items` | apply_filters | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1352` |
| `apollo/notif/push_subscribed` | do_action | 1 | 1 | `plugins/apollo-notif/src/Plugin.php:1504` |
| `apollo/notif/push_unsubscribed` | do_action | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:1525` |
| `apollo/notif/render_panel` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-notif.php:28` |
| `apollo/notif/send` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Bridge/NotificationBridge.php:57` |
| `apollo/notif/type_snoozed` | do_action | 1 | **0** | `plugins/apollo-notif/includes/functions.php:391` |
| `apollo/pane/cpt_map` | apply_filters | 1 | **0** | `plugins/apollo-core/src/Core/PaneModeAdapter.php:98` |
| `apollo/pane/cpt_sections` | apply_filters | 1 | **0** | `plugins/apollo-pane-engine/includes/section-renderer.php:55` |
| `apollo/plus/before_close` | do_action | 1 | 2 | `plugins/apollo-templates/includes/apollo-plus-api.php:171` |
| `apollo/preferences/channel_allowed` | apply_filters | 1 | **0** | `plugins/apollo-core/src/Core/ChannelPreferences.php:108` |
| `apollo/radio/initialized` | do_action | 1 | **0** | `plugins/apollo-radio/apollo-radio.php:80` |
| `apollo/registry/data` | apply_filters | 1 | **0** | `plugins/apollo-core/src/Core/Registry.php:69` |
| `apollo/registry/data_after_inventory_sync` | apply_filters | 1 | **0** | `plugins/apollo-core/src/Core/PluginInventorySync.php:199` |
| `apollo/registry/runtime_packages` | apply_filters | 1 | **0** | `plugins/apollo-core/src/Core/PluginInventorySync.php:194` |
| `apollo/remind/created` | do_action | 1 | **0** | `plugins/apollo-remind/includes/functions.php:103` |
| `apollo/remind/failed` | do_action | 1 | **0** | `plugins/apollo-remind/src/Cron/Processor.php:126` |
| `apollo/remind/schedule` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Bridge/NotificationBridge.php:56` |
| `apollo/remind/sent` | do_action | 1 | **0** | `plugins/apollo-remind/src/Cron/Processor.php:114` |
| `apollo/route/before_render` | do_action | 1 | **0** | `plugins/apollo-core/src/Core/FrontRouteDispatcher.php:84` |
| `apollo/route/matched` | do_action | 1 | 1 | `plugins/apollo-core/src/Core/FrontRouteDispatcher.php:63` |
| `apollo/routes/index` | apply_filters | 1 | **0** | `plugins/apollo-core/src/Core/FrontRouteDispatcher.php:204` |
| `apollo/routes/is_blank_canvas` | apply_filters | 1 | 1 | `plugins/apollo-core/includes/route-helpers.php:202` |
| `apollo/routes/is_reserved` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/route-helpers.php:164` |
| `apollo/routes/register` | apply_filters | 1 | 1 | `plugins/apollo-core/src/Core/FrontRouteDispatcher.php:79` |
| `apollo/routes/reserved_slugs` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/route-helpers.php:140` |
| `apollo/scheduler/activated` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Activation.php:34` |
| `apollo/scheduler/agent_assigned` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Models/AgentModel.php:67` |
| `apollo/scheduler/agents_for_nucleo` | apply_filters | 1 | **0** | `plugins/apollo-scheduler/src/Models/AgentModel.php:52` |
| `apollo/scheduler/availability_updated` | do_action | 2 | **0** | `plugins/apollo-scheduler/src/API/ManagerController.php:121` |
| `apollo/scheduler/available_hours` | apply_filters | 1 | **0** | `plugins/apollo-scheduler/src/Engine/AvailabilityEngine.php:105` |
| `apollo/scheduler/book_advance_timeout` | apply_filters | 1 | **0** | `plugins/apollo-scheduler/src/Engine/AvailabilityEngine.php:161` |
| `apollo/scheduler/booked` | do_action | 1 | 1 | `plugins/apollo-scheduler/src/Models/AppointmentModel.php:68` |
| `apollo/scheduler/cache_flushed` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Cache/AvailabilityCache.php:32` |
| `apollo/scheduler/calendar_linked` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Bridge/CalendarBridge.php:36` |
| `apollo/scheduler/can_manage_nucleo` | apply_filters | 1 | 1 | `plugins/apollo-scheduler/includes/functions.php:119` |
| `apollo/scheduler/confirmation` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Bridge/NotificationBridge.php:40` |
| `apollo/scheduler/create_event` | apply_filters | 1 | **0** | `plugins/apollo-scheduler/src/Bridge/CalendarBridge.php:29` |
| `apollo/scheduler/deactivated` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Deactivation.php:27` |
| `apollo/scheduler/future_booking_limit` | apply_filters | 1 | **0** | `plugins/apollo-scheduler/src/Engine/AvailabilityEngine.php:181` |
| `apollo/scheduler/init` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Plugin.php:50` |
| `apollo/scheduler/payment_processed` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Bridge/PaymentBridge.php:34` |
| `apollo/scheduler/process_payment` | apply_filters | 1 | **0** | `plugins/apollo-scheduler/src/Bridge/PaymentBridge.php:21` |
| `apollo/scheduler/rate_limited` | do_action | 1 | **0** | `plugins/apollo-scheduler/src/Security/BookingRateLimit.php:45` |
| `apollo/scheduler/rescheduled` | do_action | 1 | 1 | `plugins/apollo-scheduler/src/API/BookingController.php:176` |
| `apollo/security/csp_directives` | apply_filters | 2 | **0** | `plugins/apollo-login/src/Security/SecurityHeaders.php:157` |
| `apollo/security/ip_locked` | do_action | 1 | **0** | `plugins/apollo-login/src/Security/Lockout.php:236` |
| `apollo/security/is_blank_canvas_csp` | apply_filters | 1 | **0** | `plugins/apollo-login/src/Security/SecurityHeaders.php:357` |
| `apollo/security/rate_limited` | do_action | 1 | **0** | `plugins/apollo-login/src/Security/RateLimiter.php:144` |
| `apollo/security/rate_limited_page` | do_action | 1 | **0** | `plugins/apollo-login/src/Security/RateLimiter.php:172` |
| `apollo/security/user_locked` | do_action | 1 | **0** | `plugins/apollo-login/src/Security/Lockout.php:136` |
| `apollo/security/user_unlocked` | do_action | 1 | **0** | `plugins/apollo-login/src/Security/Lockout.php:151` |
| `apollo/seo/head` | do_action | 2 | 1 | `plugins/apollo-core/includes/document-head.php:184` |
| `apollo/seo/metabox_priority` | apply_filters | 1 | **0** | `plugins/apollo-seo/src/Metabox.php:52` |
| `apollo/seo/virtual_context` | apply_filters | 1 | 1 | `plugins/apollo-seo/src/Meta.php:546` |
| `apollo/sheets/added` | do_action | 1 | **0** | `plugins/apollo-sheets/src/Model.php:164` |
| `apollo/sheets/before_save_settings` | do_action | 1 | **0** | `plugins/apollo-sheets/src/Settings.php:129` |
| `apollo/sheets/bulk/allowed_post_types` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Bulk/Manager.php:164` |
| `apollo/sheets/bulk/comment_query_args` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Bulk/CommentsProvider.php:67` |
| `apollo/sheets/bulk/query_args` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Bulk/PostsProvider.php:70` |
| `apollo/sheets/bulk/register_columns` | do_action | 1 | **0** | `plugins/apollo-sheets/src/Bulk/ColumnRegistry.php:238` |
| `apollo/sheets/bulk/register_comment_columns` | do_action | 1 | **0** | `plugins/apollo-sheets/src/Bulk/ColumnRegistry.php:496` |
| `apollo/sheets/bulk/register_user_columns` | do_action | 1 | **0** | `plugins/apollo-sheets/src/Bulk/ColumnRegistry.php:385` |
| `apollo/sheets/bulk/user_query_args` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Bulk/UsersProvider.php:70` |
| `apollo/sheets/cell_content` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Render.php:334` |
| `apollo/sheets/cell_css_class` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Render.php:522` |
| `apollo/sheets/copied` | do_action | 1 | **0** | `plugins/apollo-sheets/src/Model.php:317` |
| `apollo/sheets/datatables_params` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Render.php:659` |
| `apollo/sheets/deleted` | do_action | 1 | **0** | `plugins/apollo-sheets/src/Model.php:293` |
| `apollo/sheets/formula_evaluated_data` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Formula.php:77` |
| `apollo/sheets/formula_function` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Formula.php:416` |
| `apollo/sheets/import_fix_common_url_mistakes` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Import.php:280` |
| `apollo/sheets/initialized` | do_action | 1 | **0** | `plugins/apollo-sheets/src/Plugin.php:95` |
| `apollo/sheets/pre_delete` | do_action | 1 | **0** | `plugins/apollo-sheets/src/Model.php:279` |
| `apollo/sheets/render_data` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Render.php:179` |
| `apollo/sheets/rest_created` | do_action | 1 | **0** | `plugins/apollo-sheets/src/API/SheetsController.php:296` |
| `apollo/sheets/rest_deleted` | do_action | 1 | **0** | `plugins/apollo-sheets/src/API/SheetsController.php:375` |
| `apollo/sheets/rest_imported` | do_action | 1 | **0** | `plugins/apollo-sheets/src/API/SheetsController.php:456` |
| `apollo/sheets/rest_updated` | do_action | 1 | **0** | `plugins/apollo-sheets/src/API/SheetsController.php:351` |
| `apollo/sheets/row_css_class` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Render.php:496` |
| `apollo/sheets/saved` | do_action | 1 | **0** | `plugins/apollo-sheets/src/Model.php:262` |
| `apollo/sheets/saved_settings` | do_action | 1 | **0** | `plugins/apollo-sheets/src/Settings.php:139` |
| `apollo/sheets/span_trigger_keywords` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Render.php:170` |
| `apollo/sheets/table_css_classes` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Render.php:371` |
| `apollo/sheets/table_output` | apply_filters | 1 | **0** | `plugins/apollo-sheets/src/Render.php:205` |
| `apollo/shortcodes/allowed_render` | apply_filters | 1 | **0** | `plugins/apollo-core/src/API/ShortcodesController.php:230` |
| `apollo/sign/before_email` | do_action | 1 | **0** | `plugins/apollo-sign/src/Notifications.php:133` |
| `apollo/sign/created` | do_action | 1 | **0** | `plugins/apollo-sign/src/Model/Signature.php:71` |
| `apollo/sign/doc_finalized` | do_action | 1 | **0** | `plugins/apollo-sign/src/Plugin.php:156` |
| `apollo/sign/initialized` | do_action | 1 | **0** | `plugins/apollo-sign/src/Plugin.php:64` |
| `apollo/sign/signed` | do_action | 1 | 1 | `plugins/apollo-sign/src/Model/Signature.php:446` |
| `apollo/social/activity_created` | do_action | 1 | 2 | `plugins/apollo-social/includes/functions.php:51` |
| `apollo/social/get_followers` | apply_filters | 1 | **0** | `plugins/apollo-notif/src/Plugin.php:878` |
| `apollo/social/is_following` | apply_filters | 2 | 1 | `plugins/apollo-statistics/src/API/ProfileController.php:105` |
| `apollo/social/render_feed` | do_action | 1 | **0** | `plugins/apollo-templates/templates/template-parts/new-home/panel-explore.php:60` |
| `apollo/social/reply_created` | do_action | 1 | 2 | `plugins/apollo-social/src/Plugin.php:831` |
| `apollo/social/trending_tracks` | apply_filters | 1 | **0** | `plugins/apollo-social/src/Components/SidebarRenderer.php:111` |
| `apollo/social/user_blocked` | do_action | 1 | **0** | `plugins/apollo-social/src/Plugin.php:1023` |
| `apollo/social/user_unblocked` | do_action | 1 | **0** | `plugins/apollo-social/src/Plugin.php:1043` |
| `apollo/soundcloud/provider` | apply_filters | 1 | **0** | `plugins/apollo-soundcloud/apollo-soundcloud.php:124` |
| `apollo/statistics/click_recorded` | do_action | 1 | **0** | `plugins/apollo-statistics/src/API/TrackController.php:188` |
| `apollo/statistics/daily_aggregate_completed` | do_action | 1 | 2 | `plugins/apollo-statistics/src/Collectors/CronCollector.php:51` |
| `apollo/statistics/daily_collected` | do_action | 1 | 1 | `plugins/apollo-statistics/includes/class-data-collector.php:146` |
| `apollo/statistics/daily_snapshot` | do_action | 1 | **0** | `plugins/apollo-fav/includes/class-statistics-merge.php:719` |
| `apollo/statistics/data_providers` | apply_filters | 1 | 1 | `plugins/apollo-statistics/includes/class-data-collector.php:103` |
| `apollo/statistics/data_rotated` | do_action | 1 | **0** | `plugins/apollo-statistics/src/Collectors/CronCollector.php:147` |
| `apollo/statistics/event_recorded` | do_action | 1 | **0** | `plugins/apollo-statistics/src/API/TrackController.php:279` |
| `apollo/statistics/initialized` | do_action | 1 | **0** | `plugins/apollo-statistics/src/Plugin.php:41` |
| `apollo/statistics/pageview_recorded` | do_action | 1 | **0** | `plugins/apollo-statistics/src/API/TrackController.php:165` |
| `apollo/statistics/radio_` | do_action | 1 | **0** | `plugins/apollo-statistics/src/API/TrackController.php:317` |
| `apollo/statistics/register` | do_action | 1 | 1 | `plugins/apollo-statistics/src/Core/MetricRegistry.php:43` |
| `apollo/statistics/retention_days` | apply_filters | 1 | **0** | `plugins/apollo-statistics/includes/class-metrics-processor.php:364` |
| `apollo/statistics/session_` | do_action | 1 | **0** | `plugins/apollo-statistics/src/API/TrackController.php:247` |
| `apollo/statistics/tracked_cpts` | apply_filters | 1 | **0** | `plugins/apollo-statistics/includes/class-data-collector.php:322` |
| `apollo/taxonomies/registered` | do_action | 1 | **0** | `plugins/apollo-core/src/Core/TaxonomyRegistry.php:216` |
| `apollo/templates/has_aside` | apply_filters | 1 | **0** | `plugins/apollo-templates/templates/template-parts/navbar.v2.php:76` |
| `apollo/ui/enabled` | apply_filters | 1 | **0** | `plugins/apollo-ui/includes/enqueue.php:49` |
| `apollo/users/profile_visited` | do_action | 2 | 2 | `plugins/apollo-users/includes/functions.php:276` |
| `apollo/users/query_members_args` | apply_filters | 1 | **0** | `plugins/apollo-users/includes/functions.php:856` |
| `apollo/wow/added` | do_action | 1 | 3 | `plugins/apollo-wow/includes/functions.php:76` |
| `apollo/wow/removed` | do_action | 1 | **0** | `plugins/apollo-wow/includes/functions.php:103` |
| `apollo/wow/types` | apply_filters | 1 | **0** | `plugins/apollo-wow/includes/functions.php:16` |
| `apollo_achievement_object` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/achievement-functions.php:290` |
| `apollo_activity_triggers` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/triggers.php:62` |
| `apollo_admin_route_map` | apply_filters | 1 | **0** | `plugins/apollo-admin/src/Admin/SettingsBridge.php:30` |
| `apollo_adverts_event_resync_max` | apply_filters | 1 | **0** | `plugins/apollo-adverts/includes/cpt.php:383` |
| `apollo_adverts_safety_cleared` | do_action | 1 | **0** | `plugins/apollo-adverts/includes/safety-gate.php:165` |
| `apollo_adverts_safety_witness_link` | do_action | 1 | **0** | `plugins/apollo-adverts/includes/safety-notify.php:103` |
| `apollo_after_home_content` | do_action | 1 | **0** | `plugins/apollo-templates/templates/page-sobre.php:328` |
| `apollo_after_mural_content` | do_action | 1 | **0** | `plugins/apollo-templates/templates/page-mural.php:306` |
| `apollo_after_revoke_achievement` | do_action | 1 | **0** | `plugins/apollo-membership/includes/achievement-functions.php:245` |
| `apollo_after_revoke_rank` | do_action | 1 | **0** | `plugins/apollo-membership/includes/ranks/rank-functions.php:320` |
| `apollo_after_template_part` | do_action | 1 | **0** | `plugins/apollo-templates/includes/functions.php:100` |
| `apollo_auth_brand_subtitle` | apply_filters | 1 | **0** | `plugins/apollo-login/templates/parts/new_header.php:15` |
| `apollo_auth_config` | apply_filters | 3 | **0** | `plugins/apollo-login/templates/login.php:34` |
| `apollo_auth_node_name` | apply_filters | 1 | **0** | `plugins/apollo-login/templates/parts/new_footer.php:25` |
| `apollo_author_protection_allowed_routes` | apply_filters | 1 | **0** | `plugins/apollo-users/src/Components/AuthorProtection.php:182` |
| `apollo_award_achievement` | do_action | 1 | 2 | `plugins/apollo-membership/includes/achievement-functions.php:175` |
| `apollo_award_points_to_user` | do_action | 1 | **0** | `plugins/apollo-membership/includes/points/point-rules-engine.php:94` |
| `apollo_award_rank` | do_action | 1 | 1 | `plugins/apollo-membership/includes/ranks/rank-functions.php:294` |
| `apollo_award_user_points` | do_action | 1 | 2 | `plugins/apollo-membership/includes/points/point-functions.php:172` |
| `apollo_before_home_content` | do_action | 1 | **0** | `plugins/apollo-templates/templates/page-sobre.php:22` |
| `apollo_before_revoke_achievement` | do_action | 1 | **0** | `plugins/apollo-membership/includes/achievement-functions.php:210` |
| `apollo_before_revoke_rank` | do_action | 1 | **0** | `plugins/apollo-membership/includes/ranks/rank-functions.php:310` |
| `apollo_before_template_part` | do_action | 1 | **0** | `plugins/apollo-templates/includes/functions.php:90` |
| `apollo_card_render` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/card-contract.php:265` |
| `apollo_core_register_meta` | apply_filters | 1 | 6 | `plugins/apollo-core/src/Core/MetaRegistry.php:2285` |
| `apollo_core_register_post_meta` | apply_filters | 1 | 1 | `plugins/apollo-core/src/Core/MetaRegistry.php:2290` |
| `apollo_core_register_term_meta` | apply_filters | 1 | **0** | `plugins/apollo-core/src/Core/MetaRegistry.php:2300` |
| `apollo_core_register_user_meta` | apply_filters | 1 | 2 | `plugins/apollo-core/src/Core/MetaRegistry.php:2295` |
| `apollo_daily_visit_internal` | do_action | 1 | **0** | `plugins/apollo-membership/includes/triggers.php:361` |
| `apollo_dashboard_widgets` | apply_filters | 1 | **0** | `plugins/apollo-dashboard/includes/functions.php:52` |
| `apollo_deduct_points_from_user` | do_action | 1 | 1 | `plugins/apollo-membership/includes/points/point-rules-engine.php:174` |
| `apollo_deduct_user_points` | do_action | 1 | 1 | `plugins/apollo-membership/includes/points/point-functions.php:199` |
| `apollo_default_rank` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/ranks/rank-functions.php:112` |
| `apollo_dj_card_avatar_size` | apply_filters | 1 | **0** | `plugins/apollo-djs/templates/dj-card.php:24` |
| `apollo_dj_card_stats` | apply_filters | 1 | **0** | `plugins/apollo-djs/includes/functions.php:665` |
| `apollo_dj_footer_brand` | apply_filters | 1 | **0** | `plugins/apollo-djs/templates/parts/dj/footer.php:11` |
| `apollo_dj_render_single` | apply_filters | 1 | **0** | `plugins/apollo-djs/includes/render-single.php:370` |
| `apollo_dj_rest_before_delete` | do_action | 1 | **0** | `plugins/apollo-djs/src/API/DJsController.php:295` |
| `apollo_dj_rest_created` | do_action | 1 | 1 | `plugins/apollo-djs/src/API/DJsController.php:246` |
| `apollo_dj_rest_updated` | do_action | 1 | **0** | `plugins/apollo-djs/src/API/DJsController.php:284` |
| `apollo_dj_roster_label` | apply_filters | 1 | **0** | `plugins/apollo-djs/templates/parts/dj/header.php:11` |
| `apollo_dj_single_after` | do_action | 1 | **0** | `plugins/apollo-events/styles/apollo-v2/single-dj.php:336` |
| `apollo_dj_single_before_scripts` | do_action | 2 | **0** | `plugins/apollo-djs/templates/single-dj-v3.php:128` |
| `apollo_dj_single_body_end` | do_action | 2 | **0** | `plugins/apollo-djs/templates/single-dj-v3.php:132` |
| `apollo_dj_single_body_start` | do_action | 2 | **0** | `plugins/apollo-djs/templates/single-dj-v3.php:101` |
| `apollo_dj_single_context` | apply_filters | 1 | **0** | `plugins/apollo-djs/includes/render-single.php:254` |
| `apollo_dj_single_head_after` | do_action | 2 | **0** | `plugins/apollo-djs/templates/single-dj-v3.php:97` |
| `apollo_dj_single_head_before` | do_action | 2 | **0** | `plugins/apollo-djs/templates/single-dj-v3.php:88` |
| `apollo_editable_post_types` | apply_filters | 1 | 2 | `plugins/apollo-templates/src/FrontendRouter.php:118` |
| `apollo_editor_after_fields` | do_action | 1 | **0** | `plugins/apollo-templates/templates/edit-post.php:449` |
| `apollo_editor_after_save_{$post_type}` | do_action | 1 | **0** | `plugins/apollo-templates/src/FrontendEditor.php:759` |
| `apollo_editor_after_section_{$section_key}` | do_action | 1 | **0** | `plugins/apollo-templates/src/FrontendEditor.php:371` |
| `apollo_editor_before_fields` | do_action | 1 | **0** | `plugins/apollo-templates/templates/edit-post.php:213` |
| `apollo_editor_before_section_{$section_key}` | do_action | 1 | **0** | `plugins/apollo-templates/src/FrontendEditor.php:365` |
| `apollo_editor_can_create_{$post_type}` | apply_filters | 1 | **0** | `plugins/apollo-templates/src/FrontendRouter.php:215` |
| `apollo_editor_can_edit_{$post_type}` | apply_filters | 1 | **0** | `plugins/apollo-templates/src/FrontendEditor.php:624` |
| `apollo_editor_config_{$post_type}` | apply_filters | 1 | **0** | `plugins/apollo-templates/src/FrontendEditor.php:578` |
| `apollo_editor_draft_created_{$post_type}` | do_action | 1 | **0** | `plugins/apollo-templates/src/FrontendRouter.php:249` |
| `apollo_editor_field_visible_{$name}` | apply_filters | 1 | **0** | `plugins/apollo-templates/src/FrontendEditor.php:393` |
| `apollo_editor_footer` | do_action | 1 | **0** | `plugins/apollo-templates/templates/edit-post.php:519` |
| `apollo_editor_head` | do_action | 1 | **0** | `plugins/apollo-templates/templates/edit-post.php:76` |
| `apollo_editor_render_field_{$type}` | do_action | 1 | **0** | `plugins/apollo-templates/src/FrontendFields.php:74` |
| `apollo_event_after_card` | do_action | 1 | 2 | `plugins/apollo-events/src/Shortcodes.php:184` |
| `apollo_event_after_map` | do_action | 1 | **0** | `plugins/apollo-events/src/Shortcodes.php:706` |
| `apollo_event_after_render` | do_action | 1 | **0** | `plugins/apollo-events/src/Shortcodes.php:206` |
| `apollo_event_before_card` | do_action | 1 | **0** | `plugins/apollo-events/src/Shortcodes.php:173` |
| `apollo_event_before_render` | do_action | 1 | **0** | `plugins/apollo-events/src/Shortcodes.php:138` |
| `apollo_event_dashboard_after` | do_action | 1 | **0** | `plugins/apollo-events/styles/base/dashboard-event.php:138` |
| `apollo_event_dashboard_before` | do_action | 1 | **0** | `plugins/apollo-events/styles/base/dashboard-event.php:116` |
| `apollo_event_dj_added` | do_action | 1 | 1 | `plugins/apollo-events/src/API/EventsController.php:944` |
| `apollo_event_dj_removed` | do_action | 1 | **0** | `plugins/apollo-events/src/API/EventsController.php:981` |
| `apollo_event_gone` | do_action | 1 | 3 | `plugins/apollo-events/src/Expiration.php:116` |
| `apollo_event_kses_about_allowed` | apply_filters | 1 | **0** | `plugins/apollo-events/includes/functions.php:1509` |
| `apollo_event_register_integrations` | do_action | 1 | **0** | `plugins/apollo-events/src/Integrations.php:70` |
| `apollo_event_render_single` | apply_filters | 1 | **0** | `plugins/apollo-events/includes/render-single.php:556` |
| `apollo_event_rest_before_delete` | do_action | 1 | **0** | `plugins/apollo-events/src/API/EventsController.php:711` |
| `apollo_event_rest_created` | do_action | 1 | 2 | `plugins/apollo-events/src/API/EventsController.php:610` |
| `apollo_event_rest_data` | apply_filters | 1 | **0** | `plugins/apollo-events/src/API/EventsController.php:1743` |
| `apollo_event_rest_updated` | do_action | 1 | 1 | `plugins/apollo-events/src/API/EventsController.php:687` |
| `apollo_event_single_after` | do_action | 1 | **0** | `plugins/apollo-events/styles/base/single-event.php:122` |
| `apollo_event_single_before` | do_action | 1 | **0** | `plugins/apollo-events/styles/base/single-event.php:115` |
| `apollo_event_single_context` | apply_filters | 1 | **0** | `plugins/apollo-events/includes/render-single.php:393` |
| `apollo_event_weather_enabled` | apply_filters | 1 | **0** | `plugins/apollo-events/styles/base/create-event.php:272` |
| `apollo_fav_added` | do_action | 1 | **0** | `plugins/apollo-fav/includes/functions.php:91` |
| `apollo_fav_enqueue_assets` | apply_filters | 1 | **0** | `plugins/apollo-fav/apollo-fav.php:213` |
| `apollo_frontend_fields_{$cpt}` | apply_filters | 1 | 1 | `plugins/apollo-templates/includes/panel-to-frontend.php:272` |
| `apollo_frontend_fields_{$post_type}` | apply_filters | 1 | **0** | `plugins/apollo-templates/src/FrontendEditor.php:197` |
| `apollo_frontend_image_id_metas` | apply_filters | 1 | **0** | `plugins/apollo-templates/src/FrontendEditor.php:830` |
| `apollo_get_dj_context` | apply_filters | 1 | **0** | `plugins/apollo-djs/includes/functions.php:739` |
| `apollo_loc_single_after` | do_action | 1 | **0** | `plugins/apollo-events/styles/apollo-v2/single-loc.php:403` |
| `apollo_loc_single_after_content` | do_action | 1 | 1 | `plugins/apollo-events/styles/apollo-v2/single-loc.php:286` |
| `apollo_loc_single_sidebar` | do_action | 1 | **0** | `plugins/apollo-events/styles/apollo-v2/single-loc.php:396` |
| `apollo_local_single_head` | do_action | 1 | **0** | `plugins/apollo-loc/templates/single-local.php:107` |
| `apollo_locate_template` | apply_filters | 1 | **0** | `plugins/apollo-templates/includes/functions.php:71` |
| `apollo_login_auth_bg_video_poster` | apply_filters | 1 | **0** | `plugins/apollo-login/templates/parts/auth-background.php:26` |
| `apollo_login_auth_bg_video_sources` | apply_filters | 1 | **0** | `plugins/apollo-login/templates/parts/auth-background.php:12` |
| `apollo_login_bypass_phone_verification` | apply_filters | 1 | **0** | `plugins/apollo-login/src/Auth/RegisterHandler.php:303` |
| `apollo_login_redirect` | apply_filters | 2 | **0** | `plugins/apollo-login/src/API/AuthController.php:414` |
| `apollo_login_registration_redirect` | apply_filters | 1 | **0** | `plugins/apollo-login/src/Auth/RegisterHandler.php:584` |
| `apollo_membership_badge_assigned` | do_action | 1 | 1 | `plugins/apollo-membership/includes/functions.php:243` |
| `apollo_membership_credit_added` | do_action | 1 | 1 | `plugins/apollo-membership/includes/functions.php:608` |
| `apollo_mural_ticker_items` | apply_filters | 1 | **0** | `plugins/apollo-templates/templates/page-mural.php:198` |
| `apollo_mural_weather_condition` | apply_filters | 1 | 1 | `plugins/apollo-templates/templates/template-parts/mural/weather-hero.php:19` |
| `apollo_mural_weather_icon` | apply_filters | 1 | 1 | `plugins/apollo-templates/templates/template-parts/mural/weather-hero.php:20` |
| `apollo_mural_weather_location` | apply_filters | 1 | **0** | `plugins/apollo-templates/templates/template-parts/mural/weather-hero.php:21` |
| `apollo_mural_weather_temp` | apply_filters | 1 | 1 | `plugins/apollo-templates/templates/template-parts/mural/weather-hero.php:18` |
| `apollo_mural_weather_video` | apply_filters | 1 | **0** | `plugins/apollo-templates/templates/template-parts/mural/weather-hero.php:22` |
| `apollo_newsletter_subscriber_confirmed` | do_action | 1 | **0** | `plugins/apollo-email/src/Newsletter.php:420` |
| `apollo_point_award_triggers` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/points/triggers.php:49` |
| `apollo_point_deduct_triggers` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/points/triggers.php:65` |
| `apollo_rank_object` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/ranks/rank-functions.php:93` |
| `apollo_rank_triggers` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/ranks/triggers.php:30` |
| `apollo_register_shortcodes` | do_action | 1 | **0** | `plugins/apollo-core/src/Core/ShortcodeRegistry.php:414` |
| `apollo_registration_sounds` | apply_filters | 2 | **0** | `plugins/apollo-login/includes/sounds-catalog.php:141` |
| `apollo_safety_exemption` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/safety-contract.php:116` |
| `apollo_safety_ig_mutuals` | apply_filters | 2 | **0** | `plugins/apollo-adverts/src/API/SafetyController.php:130` |
| `apollo_safety_is_verified` | apply_filters | 1 | **0** | `plugins/apollo-adverts/src/API/SafetyController.php:369` |
| `apollo_safety_mutuals` | apply_filters | 3 | 1 | `plugins/apollo-adverts/src/API/SafetyController.php:127` |
| `apollo_safety_mutuals_limit` | apply_filters | 1 | **0** | `plugins/apollo-adverts/src/Safety/NativeGraph.php:131` |
| `apollo_safety_registry` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/safety-contract.php:57` |
| `apollo_safety_render` | do_action | 1 | 1 | `plugins/apollo-core/includes/safety-contract.php:177` |
| `apollo_safety_trust_votes` | apply_filters | 1 | **0** | `plugins/apollo-adverts/src/API/SafetyController.php:138` |
| `apollo_safety_url` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/safety-contract.php:158` |
| `apollo_safety_vouch_request` | apply_filters | 1 | **0** | `plugins/apollo-adverts/src/API/SafetyController.php:213` |
| `apollo_safety_vouch_requested` | do_action | 1 | 1 | `plugins/apollo-adverts/src/API/SafetyController.php:236` |
| `apollo_search_options_{$context}` | apply_filters | 1 | **0** | `plugins/apollo-core/includes/search-helpers.php:224` |
| `apollo_should_check_rank` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/ranks/triggers.php:54` |
| `apollo_statistics_track_view` | do_action | 1 | **0** | `plugins/apollo-djs/src/Integrations.php:84` |
| `apollo_stats_events_reminder_body` | apply_filters | 1 | **0** | `plugins/apollo-statistics/src/Collectors/WeeklyDigestCron.php:90` |
| `apollo_stats_events_reminder_completed` | do_action | 1 | **0** | `plugins/apollo-statistics/src/Collectors/WeeklyDigestCron.php:98` |
| `apollo_stats_events_reminder_subject` | apply_filters | 1 | **0** | `plugins/apollo-statistics/src/Collectors/WeeklyDigestCron.php:85` |
| `apollo_stats_weekly_roundup_body` | apply_filters | 1 | **0** | `plugins/apollo-statistics/src/Collectors/WeeklyDigestCron.php:124` |
| `apollo_stats_weekly_roundup_completed` | do_action | 1 | **0** | `plugins/apollo-statistics/src/Collectors/WeeklyDigestCron.php:133` |
| `apollo_stats_weekly_roundup_lines` | apply_filters | 1 | **0** | `plugins/apollo-statistics/src/Collectors/WeeklyDigestCron.php:113` |
| `apollo_stats_weekly_roundup_subject` | apply_filters | 1 | **0** | `plugins/apollo-statistics/src/Collectors/WeeklyDigestCron.php:118` |
| `apollo_step_is_complete` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/rules-engine.php:174` |
| `apollo_track_query_args` | apply_filters | 1 | **0** | `plugins/apollo-djs/includes/tracks.php:103` |
| `apollo_trigger_processing` | do_action | 2 | **0** | `plugins/apollo-membership/includes/triggers.php:130` |
| `apollo_trigger_processing_complete` | do_action | 2 | **0** | `plugins/apollo-membership/includes/triggers.php:141` |
| `apollo_ui_surfaces` | apply_filters | 1 | **0** | `plugins/apollo-ui/includes/surfaces.php:84` |
| `apollo_update_users_points` | do_action | 1 | 2 | `plugins/apollo-membership/includes/points/point-functions.php:98` |
| `apollo_user_deserves_achievement` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/rules-engine.php:64` |
| `apollo_user_deserves_point_award` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/points/point-rules-engine.php:74` |
| `apollo_user_deserves_point_deduction` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/points/point-rules-engine.php:145` |
| `apollo_user_deserves_trigger` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/triggers.php:124` |
| `apollo_user_has_access_to_achievement` | apply_filters | 1 | **0** | `plugins/apollo-membership/includes/rules-engine.php:223` |
| `apollo_user_meta_initialized` | do_action | 1 | **0** | `plugins/apollo-users/src/Plugin.php:494` |
| `apollo_user_points_reset` | do_action | 1 | 1 | `plugins/apollo-membership/includes/points/point-rules-engine.php:220` |
| `apollo_user_verified` | do_action | 2 | **0** | `plugins/apollo-users/includes/functions.php:612` |
| `apollo_users_fields` | apply_filters | 1 | **0** | `plugins/apollo-users/src/Components/UserFields.php:247` |
| `apollo_users_match_created` | do_action | 1 | **0** | `plugins/apollo-users/src/API/ProfileController.php:429` |


## Most-consumed WordPress hooks

| hook | listeners |
|---|---|
| `init` | 152 |
| `plugins_loaded` | 64 |
| `rest_api_init` | 61 |
| `admin_notices` | 45 |
| `template_redirect` | 45 |
| `admin_menu` | 37 |
| `admin_enqueue_scripts` | 30 |
| `admin_init` | 25 |
| `wp_enqueue_scripts` | 24 |
| `query_vars` | 23 |
| `add_meta_boxes` | 16 |
| `save_post_` | 13 |
| `wp_head` | 11 |
| `manage_` | 10 |
| `parse_request` | 10 |
| `cron_schedules` | 9 |
| `save_post` | 9 |
| `transition_post_status` | 7 |
| `user_register` | 7 |
| `wp` | 7 |
| `wp_footer` | 7 |
| `shutdown` | 6 |
| `admin_bar_menu` | 5 |
| `template_include` | 5 |
| `wp_dashboard_setup` | 5 |
| `wp_login` | 5 |
| `bp_template_content` | 4 |
| `script_loader_tag` | 4 |
| `single_template` | 4 |
| `widgets_init` | 4 |
| `admin_footer` | 3 |
| `archive_template` | 3 |
| `comment_post` | 3 |
| `edit_user_profile` | 3 |
| `login_init` | 3 |
| `plugin_action_links_` | 3 |
| `pre_get_posts` | 3 |
| `rest_pre_dispatch` | 3 |
| `the_author` | 3 |
| `the_content` | 3 |


## Publishers by unit

| unit | hooks published |
|---|---|
| `apollo-templates` | 66 |
| `apollo-events` | 50 |
| `apollo-membership` | 46 |
| `apollo-core` | 40 |
| `apollo-login` | 38 |
| `apollo-adverts` | 37 |
| `apollo-sheets` | 30 |
| `apollo-djs` | 27 |
| `apollo-notif` | 24 |
| `apollo-statistics` | 24 |
| `apollo-scheduler` | 23 |
| `apollo-gestor` | 18 |
| `apollo-groups` | 18 |
| `apollo-coauthor` | 15 |
| `apollo-email` | 15 |
| `apollo-loc` | 15 |
| `mu-plugin` | 15 |
| `apollo-users` | 12 |
| `apollo-fav` | 11 |
| `apollo-telegram` | 9 |
| `apollo-admin` | 8 |
| `apollo-docs` | 8 |
| `apollo-hub` | 8 |
| `apollo-journal` | 8 |
| `apollo-social` | 7 |
| `apollo-comment` | 6 |
| `apollo-sign` | 6 |
| `apollo-chat` | 5 |
| `apollo-mod` | 3 |
| `apollo-remind` | 3 |
| `apollo-wow` | 3 |
| `apollo-seo` | 2 |
| `apollo-ui` | 2 |
| `apollo-dashboard` | 1 |
| `apollo-dj-sync` | 1 |
| `apollo-lux-panels` | 1 |
| `apollo-pane-engine` | 1 |
| `apollo-radio` | 1 |
| `apollo-soundcloud` | 1 |


---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
