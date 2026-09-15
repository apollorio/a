# Shortcodes

86 distinct tags across 89 registration sites.

`add_shortcode()` **silently overwrites** an existing tag. Where two units
register the same tag, the one that runs later wins and the earlier
implementation vanishes with no warning. A `shortcode_exists()` guard is how
a unit yields instead of clobbering.

## No tag is registered twice.

## All shortcodes

| tag | unit | guarded | file:line |
|---|---|---|---|
| `[a-eve]` | `apollo-events` | no | `plugins/apollo-events/src/Shortcodes.php:51` |
| `[apollo_achievement]` | `apollo-membership` | no | `plugins/apollo-membership/includes/shortcodes/apollo-achievement-single.php:108` |
| `[apollo_achievements]` | `apollo-membership` | no | `plugins/apollo-membership/includes/shortcodes/apollo-achievements-list.php:106` |
| `[apollo_add_dj]` | `apollo-djs` | no | `plugins/apollo-djs/src/Shortcodes.php:26` |
| `[apollo_add_loc]` | `apollo-loc` | no | `plugins/apollo-loc/src/Shortcodes/ShortcodeRegistry.php:24` |
| `[apollo_add_news]` | `apollo-journal` | no | `plugins/apollo-journal/src/Shortcodes.php:41` |
| `[apollo_add_nrep]` | `apollo-journal` | no | `plugins/apollo-journal/src/Shortcodes.php:42` |
| `[apollo_adverts_chat_button]` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/integrations.php:360` |
| `[apollo_calendar]` | `apollo-templates` | no | `plugins/apollo-templates/includes/class-shortcodes.php:58` |
| `[apollo_cena_submit_event]` | `apollo-events` | no | `plugins/apollo-events/src/Cena_Rio_Submissions.php:38` |
| `[apollo_classified]` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/shortcodes.php:28` |
| `[apollo_classified_form]` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/shortcodes.php:29` |
| `[apollo_classifieds]` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/shortcodes.php:27` |
| `[apollo_classifieds_rent_space]` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/shortcodes.php:32` |
| `[apollo_classifieds_sell_ticket]` | `apollo-adverts` | no | `plugins/apollo-adverts/includes/shortcodes.php:31` |
| `[apollo_coauthor_count]` | `apollo-coauthor` | no | `plugins/apollo-coauthor/src/Components/Shortcodes.php:43` |
| `[apollo_coauthors]` | `apollo-coauthor` | no | `plugins/apollo-coauthor/src/Components/Shortcodes.php:42` |
| `[apollo_create_comuna]` | `apollo-groups` | no | `plugins/apollo-groups/src/Plugin.php:999` |
| `[apollo_create_nucleo]` | `apollo-groups` | no | `plugins/apollo-groups/src/Plugin.php:998` |
| `[apollo_dashboard]` | `apollo-dashboard` | no | `plugins/apollo-dashboard/includes/class-plugin.php:79` |
| `[apollo_dashboard_menu]` | `apollo-dashboard` | no | `plugins/apollo-dashboard/includes/class-plugin.php:80` |
| `[apollo_depoimento_form]` | `apollo-comment` | no | `plugins/apollo-comment/src/Shortcodes.php:21` |
| `[apollo_depoimentos]` | `apollo-comment` | no | `plugins/apollo-comment/src/Shortcodes.php:20` |
| `[apollo_dj]` | `apollo-djs` | no | `plugins/apollo-djs/src/Shortcodes.php:24` |
| `[apollo_dj_carousel]` | `apollo-djs` | no | `plugins/apollo-djs/src/Shortcodes.php:25` |
| `[apollo_djs]` | `apollo-djs` | no | `plugins/apollo-djs/src/Shortcodes.php:23` |
| `[apollo_email_prefs]` | `apollo-email` | no | `plugins/apollo-email/src/Plugin.php:114` |
| `[apollo_event]` | `apollo-templates` | no | `plugins/apollo-templates/includes/class-shortcodes.php:57` |
| `[apollo_event_form]` | `apollo-templates` | no | `plugins/apollo-templates/includes/class-shortcodes.php:59` |
| `[apollo_event_single]` | `apollo-events` | no | `plugins/apollo-events/includes/render-single.php:1043` |
| `[apollo_events]` | `apollo-templates` | no | `plugins/apollo-templates/includes/class-shortcodes.php:56` |
| `[apollo_evidence]` | `apollo-membership` | no | `plugins/apollo-membership/includes/shortcodes/apollo-evidence.php:95` |
| `[apollo_fav]` | `apollo-fav` | no | `plugins/apollo-fav/includes/class-dashboard.php:65` |
| `[apollo_fav_dashboard]` | `apollo-fav` | no | `plugins/apollo-fav/includes/class-fav-sound-dashboard.php:28` |
| `[apollo_feed]` | `apollo-social` | no | `plugins/apollo-social/src/Plugin.php:583` |
| `[apollo_follow_btn]` | `apollo-social` | no | `plugins/apollo-social/src/Plugin.php:584` |
| `[apollo_group]` | `apollo-groups` | no | `plugins/apollo-groups/src/Plugin.php:996` |
| `[apollo_groups]` | `apollo-groups` | no | `plugins/apollo-groups/src/Plugin.php:995` |
| `[apollo_hub]` | `apollo-hub` | no | `plugins/apollo-hub/src/Shortcodes.php:24` |
| `[apollo_hub_builder]` | `apollo-hub` | no | `plugins/apollo-hub/src/Shortcodes.php:25` |
| `[apollo_interesse_dashboard]` | `apollo-fav` | no | `plugins/apollo-fav/includes/class-fav-sound-dashboard.php:30` |
| `[apollo_journal]` | `apollo-journal` | no | `plugins/apollo-journal/src/Shortcodes.php:38` |
| `[apollo_journal_card]` | `apollo-journal` | no | `plugins/apollo-journal/src/Shortcodes.php:40` |
| `[apollo_journal_marquee]` | `apollo-journal` | no | `plugins/apollo-journal/src/Shortcodes.php:39` |
| `[apollo_leaderboard]` | `apollo-membership` | no | `plugins/apollo-membership/includes/shortcodes/apollo-leaderboard.php:96` |
| `[apollo_local]` | `apollo-loc` | no | `plugins/apollo-loc/src/Shortcodes/ShortcodeRegistry.php:22` |
| `[apollo_locals]` | `apollo-loc` | no | `plugins/apollo-loc/src/Shortcodes/ShortcodeRegistry.php:21` |
| `[apollo_login]` | `apollo-login` | no | `plugins/apollo-login/src/Core/Plugin.php:339` |
| `[apollo_map]` | `apollo-loc` | no | `plugins/apollo-loc/src/Shortcodes/ShortcodeRegistry.php:23` |
| `[apollo_maps]` | `apollo-maps` | no | `plugins/apollo-maps/src/Shortcodes/MapShortcode.php:22` |
| `[apollo_matchmaking]` | `apollo-users` | no | `plugins/apollo-users/src/Plugin.php:160` |
| `[apollo_my_favs]` | `apollo-fav` | no | `plugins/apollo-fav/includes/class-dashboard.php:62` |
| `[apollo_my_groups]` | `apollo-groups` | no | `plugins/apollo-groups/src/Plugin.php:997` |
| `[apollo_newsletter]` | `apollo-email` | no | `plugins/apollo-email/src/Newsletter.php:62` |
| `[apollo_notif]` | `apollo-notif` | no | `plugins/apollo-notif/src/Plugin.php:534` |
| `[apollo_notif_badge]` | `apollo-notif` | no | `plugins/apollo-notif/src/Plugin.php:535` |
| `[apollo_password_reset]` | `apollo-login` | no | `plugins/apollo-login/src/Core/Plugin.php:343` |
| `[apollo_points]` | `apollo-membership` | no | `plugins/apollo-membership/includes/shortcodes/apollo-points.php:107` |
| `[apollo_profile]` | `apollo-users` | no | `plugins/apollo-users/src/Plugin.php:156` |
| `[apollo_profile_edit]` | `apollo-users` | no | `plugins/apollo-users/src/Plugin.php:157` |
| `[apollo_profile_fields]` | `apollo-users` | no | `plugins/apollo-users/src/Plugin.php:161` |
| `[apollo_quiz]` | `apollo-login` | no | `plugins/apollo-login/src/Core/Plugin.php:341` |
| `[apollo_radar]` | `apollo-users` | no | `plugins/apollo-users/src/Plugin.php:158` |
| `[apollo_radio]` | `apollo-radio` | no | `plugins/apollo-radio/src/Shortcode/RadioShortcode.php:19` |
| `[apollo_rank]` | `apollo-membership` | no | `plugins/apollo-membership/includes/shortcodes/apollo-rank-single.php:79` |
| `[apollo_ranks]` | `apollo-membership` | no | `plugins/apollo-membership/includes/shortcodes/apollo-ranks.php:75` |
| `[apollo_register]` | `apollo-login` | no | `plugins/apollo-login/src/Core/Plugin.php:340` |
| `[apollo_related_classifieds]` | `apollo-adverts` | no | `plugins/apollo-adverts/src/RelatedAds.php:42` |
| `[apollo_scheduler_book]` | `apollo-scheduler` | no | `plugins/apollo-scheduler/src/Frontend/BookingWizard.php:19` |
| `[apollo_scheduler_manager]` | `apollo-scheduler` | no | `plugins/apollo-scheduler/src/Frontend/ManagerCalendar.php:19` |
| `[apollo_signature_pad]` | `apollo-sign` | no | `plugins/apollo-sign/src/Plugin.php:56` |
| `[apollo_simon]` | `apollo-login` | no | `plugins/apollo-login/src/Core/Plugin.php:342` |
| `[apollo_soundcloud]` | `apollo-soundcloud` | no | `plugins/apollo-soundcloud/includes/render.php:170` |
| `[apollo_stats_widget]` | `apollo-statistics` | no | `plugins/apollo-statistics/includes/class-reports.php:33` |
| `[apollo_top_sounds]` | `apollo-fav` | no | `plugins/apollo-fav/includes/class-fav-ranking.php:51` |
| `[apollo_user_achievements]` | `apollo-membership` | no | `plugins/apollo-membership/includes/shortcodes/apollo-user-achievements.php:88` |
| `[apollo_user_badge]` | `apollo-templates` | no | `plugins/apollo-templates/examples/user-radar-examples.php:310` |
| `[apollo_user_card]` | `apollo-users` | no | `plugins/apollo-users/src/Plugin.php:159` |
| `[apollo_user_rank]` | `apollo-membership` | no | `plugins/apollo-membership/includes/shortcodes/apollo-user-rank.php:79` |
| `[apollo_user_ranking]` | `apollo-templates` | no | `plugins/apollo-templates/examples/user-radar-examples.php:332` |
| `[apollo_user_stats]` | `apollo-statistics` | no | `plugins/apollo-statistics/includes/class-user-stats-widget.php:33` |
| `[apollo_verify_email]` | `apollo-login` | no | `plugins/apollo-login/src/Core/Plugin.php:344` |
| `[apollo_wow]` | `apollo-wow` | no | `plugins/apollo-wow/src/Plugin.php:146` |
| `[apollo_wow_chart]` | `apollo-wow` | no | `plugins/apollo-wow/src/Plugin.php:147` |
| `[apollo-sheet]` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Shortcodes.php:24` |
| `[apollo-sheet-info]` | `apollo-sheets` | no | `plugins/apollo-sheets/src/Shortcodes.php:25` |
| _unresolved: $apollo_tag_ | `apollo-events` | yes | `plugins/apollo-events/src/Shortcodes.php:67` |
| _unresolved: $class::$tag_ | `apollo-telegram` | no | `plugins/apollo-telegram/src/Shortcodes/ShortcodeManager.php:24` |
| _unresolved: $tag_ | `apollo-ui` | yes | `plugins/apollo-ui/includes/shortcode.php:149` |


---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
