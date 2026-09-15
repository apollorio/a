# Meta keys

Two populations, deliberately not merged:

- **governed** — declared in `apollo-core/config/meta.php`, so they have a
  type, optionally a REST projection, and a single owner. **141** keys.
- **touched** — a literal key passed to `get/update/add/delete_post_meta`
  anywhere in the codebase. **302** distinct keys across 1,164 call sites.

| population | count | meaning |
|---|---|---|
| governed **and** touched | 78 | healthy |
| touched, **not** governed | **224** | no type, no REST contract, no owner — silent schema |
| governed, never touched | 63 | declared contract with no reader or writer |


## Ungoverned keys

Each of these is written or read by real code but has no declaration in
`meta.php`. They carry no type, are invisible to REST, and nothing prevents
two plugins from disagreeing on the value shape.

| key | sites | writes | units | first site |
|---|---|---|---|---|
| `_achievement_hidden` | 1 | 0 | `apollo-membership` | `plugins/apollo-membership/includes/rules-engine.php:211` |
| `_achievement_maximum_earnings` | 2 | 0 | `apollo-membership` | `plugins/apollo-membership/includes/achievement-functions.php:306` |
| `_achievement_point_type` | 1 | 0 | `apollo-membership` | `plugins/apollo-membership/includes/achievement-functions.php:278` |
| `_achievement_points` | 4 | 0 | `apollo-membership` | `plugins/apollo-membership/includes/achievement-functions.php:276` |
| `_achievement_points_required` | 1 | 0 | `apollo-membership` | `plugins/apollo-membership/includes/rules-engine.php:110` |
| `_achievement_restricted_to` | 1 | 0 | `apollo-membership` | `plugins/apollo-membership/includes/rules-engine.php:197` |
| `_achievement_trigger` | 1 | 0 | `apollo-membership` | `plugins/apollo-membership/includes/rules-engine.php:95` |
| `_achievement_type` | 1 | 0 | `apollo-membership` | `plugins/apollo-membership/src/API/AchievementsController.php:426` |
| `_apollo_appointment_agent_id` | 2 | 1 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/AppointmentModel.php:54` |
| `_apollo_appointment_customer_id` | 2 | 1 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/AppointmentModel.php:57` |
| `_apollo_appointment_date` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/PlannerRepository.php:57` |
| `_apollo_appointment_end` | 4 | 2 | `apollo-calendar` `apollo-scheduler` | `plugins/apollo-calendar/src/CalendarAggregator.php:249` |
| `_apollo_appointment_event_id` | 2 | 1 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/AppointmentModel.php:63` |
| `_apollo_appointment_notes` | 1 | 1 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/AppointmentModel.php:59` |
| `_apollo_appointment_nucleo_id` | 2 | 1 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/AppointmentModel.php:56` |
| `_apollo_appointment_payment_status` | 1 | 1 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Bridge/PaymentBridge.php:32` |
| `_apollo_appointment_progress` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/PlannerRepository.php:68` |
| `_apollo_appointment_resource_id` | 2 | 1 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/AppointmentModel.php:55` |
| `_apollo_appointment_service_id` | 2 | 1 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/AppointmentModel.php:53` |
| `_apollo_appointment_service_title` | 1 | 0 | `apollo-calendar` | `plugins/apollo-calendar/src/CalendarAggregator.php:250` |
| `_apollo_appointment_start` | 4 | 2 | `apollo-calendar` `apollo-scheduler` | `plugins/apollo-calendar/src/CalendarAggregator.php:248` |
| `_apollo_appointment_status` | 2 | 1 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/AppointmentModel.php:58` |
| `_apollo_appointment_time_end` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/PlannerRepository.php:65` |
| `_apollo_appointment_time_start` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/PlannerRepository.php:64` |
| `_apollo_appointment_type` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/PlannerRepository.php:66` |
| `_apollo_appointment_venue` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/PlannerRepository.php:67` |
| `_apollo_capacity` | 2 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/ChartsRepository.php:25` |
| `_apollo_cena_confirmed_at` | 2 | 2 | `apollo-events` | `plugins/apollo-events/src/Cena_Rio_Submissions.php:295` |
| `_apollo_cena_confirmed_by` | 2 | 2 | `apollo-events` | `plugins/apollo-events/src/Cena_Rio_Submissions.php:294` |
| `_apollo_cena_status` | 5 | 3 | `apollo-events` | `plugins/apollo-events/src/Cena_Rio_Submissions.php:200` |
| `_apollo_cena_submitted_at` | 1 | 1 | `apollo-events` | `plugins/apollo-events/src/Cena_Rio_Submissions.php:710` |
| `_apollo_cena_submitted_by` | 1 | 1 | `apollo-events` | `plugins/apollo-events/src/Cena_Rio_Submissions.php:709` |
| `_apollo_city` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/EventRepository.php:37` |
| `_apollo_event_date` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/EventRepository.php:32` |
| `_apollo_event_time_end` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/EventRepository.php:34` |
| `_apollo_event_time_start` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/EventRepository.php:33` |
| `_apollo_fav_count` | 1 | 0 | `apollo-events` | `plugins/apollo-events/src/API/EventsController.php:1202` |
| `_apollo_headline` | 1 | 0 | `apollo-journal` | `plugins/apollo-journal/src/API/PostsController.php:235` |
| `_apollo_lineup` | 2 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/DJRepository.php:73` |
| `_apollo_note_type` | 1 | 0 | `apollo-journal` | `plugins/apollo-journal/src/API/PostsController.php:304` |
| `_apollo_resource_capacity` | 1 | 0 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/ResourceModel.php:30` |
| `_apollo_resource_loc_id` | 1 | 0 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/ResourceModel.php:32` |
| `_apollo_resource_nucleo_id` | 1 | 0 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/ResourceModel.php:31` |
| `_apollo_revenue` | 2 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/ChartsRepository.php:26` |
| `_apollo_rsvp_count` | 3 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/ChartsRepository.php:24` |
| `_apollo_service_duration` | 1 | 0 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/ServiceModel.php:31` |
| `_apollo_service_nucleo_id` | 1 | 0 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/ServiceModel.php:36` |
| `_apollo_service_price` | 1 | 0 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/ServiceModel.php:32` |
| `_apollo_service_slot_interval` | 1 | 0 | `apollo-scheduler` | `plugins/apollo-scheduler/src/Models/ServiceModel.php:33` |
| `_apollo_sheet_id` | 1 | 1 | `apollo-sheets` | `plugins/apollo-sheets/src/Model.php:158` |
| `_apollo_sheet_options` | 3 | 2 | `apollo-sheets` | `plugins/apollo-sheets/src/Model.php:156` |
| `_apollo_sheet_visibility` | 3 | 2 | `apollo-sheets` | `plugins/apollo-sheets/src/Model.php:157` |
| `_apollo_source` | 3 | 1 | `apollo-events` `apollo-journal` | `plugins/apollo-events/src/Cena_Rio_Submissions.php:273` |
| `_apollo_subtitle` | 1 | 0 | `apollo-journal` | `plugins/apollo-journal/src/API/PostsController.php:236` |
| `_apollo_venue` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/EventRepository.php:35` |
| `_apollo_venue_address` | 1 | 0 | `apollo-elementor` | `plugins/apollo-elementor/src/Data/EventRepository.php:36` |
| `_apollo_wow_count` | 1 | 0 | `apollo-events` | `plugins/apollo-events/src/API/EventsController.php:1203` |
| `_apollo_wows` | 1 | 0 | `apollo-users` | `plugins/apollo-users/templates/parts/profile-feed.php:54` |
| `_badge` | 1 | 1 | `apollo-adverts` | `plugins/apollo-adverts/includes/demo-data.php:150` |
| `_classified_avail_end` | 1 | 0 | `apollo-adverts` | `plugins/apollo-adverts/src/Plugin.php:778` |
| `_classified_avail_start` | 2 | 0 | `apollo-adverts` | `plugins/apollo-adverts/includes/rt-card.php:106` |
| `_classified_event_date` | 5 | 2 | `apollo-adverts` | `plugins/apollo-adverts/includes/cpt.php:318` |
| `_classified_event_id` | 5 | 4 | `apollo-adverts` | `plugins/apollo-adverts/includes/cpt.php:304` |
| `_classified_event_loc` | 5 | 2 | `apollo-adverts` | `plugins/apollo-adverts/includes/cpt.php:329` |
| `_classified_event_title` | 4 | 1 | `apollo-adverts` | `plugins/apollo-adverts/includes/cpt.php:314` |
| `_classified_expiring_notified` | 3 | 2 | `apollo-adverts` | `plugins/apollo-adverts/includes/cron.php:114` |
| `_classified_hostel` | 5 | 1 | `apollo-adverts` | `plugins/apollo-adverts/includes/cpt.php:433` |
| `_classified_hostel_id` | 2 | 1 | `apollo-adverts` | `plugins/apollo-adverts/includes/safety-gate.php:45` |
| `_classified_hostel_url` | 8 | 2 | `apollo-adverts` | `plugins/apollo-adverts/includes/cpt.php:464` |
| `_classified_max_days` | 2 | 1 | `apollo-adverts` | `plugins/apollo-adverts/src/API/ClassifiedsController.php:467` |
| `_classified_min_nights` | 2 | 1 | `apollo-adverts` | `plugins/apollo-adverts/src/API/ClassifiedsController.php:462` |
| `_classified_original_price` | 1 | 0 | `apollo-adverts` | `plugins/apollo-adverts/templates/single.php:28` |
| `_classified_quantity` | 5 | 2 | `apollo-adverts` | `plugins/apollo-adverts/includes/rt-card.php:116` |
| `_classified_status` | 1 | 0 | `apollo-fav` | `plugins/apollo-fav/includes/class-notif-triggers.php:227` |
| `_classified_type` | 11 | 5 | `apollo-adverts` | `plugins/apollo-adverts/includes/cpt.php:276` |
| `_classified_views` | 6 | 1 | `apollo-adverts` | `plugins/apollo-adverts/includes/functions.php:262` |
| `_coupon_code` | 1 | 0 | `apollo-templates` | `plugins/apollo-templates/templates/template-parts/home/events-listing.php:127` |
| `_depoimento_author_name` | 1 | 0 | `apollo-djs` | `plugins/apollo-djs/templates/parts/dj-v3/depoimentos.php:42` |
| `_depoimento_author_role` | 1 | 0 | `apollo-djs` | `plugins/apollo-djs/templates/parts/dj-v3/depoimentos.php:43` |
| `_depoimento_rating` | 1 | 0 | `apollo-djs` | `plugins/apollo-djs/templates/parts/dj-v3/depoimentos.php:38` |
| `_depoimento_source` | 1 | 0 | `apollo-djs` | `plugins/apollo-djs/templates/parts/dj-v3/depoimentos.php:44` |
| `_dj_about_photo` | 3 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/functions.php:689` |
| `_dj_about_video` | 3 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/functions.php:718` |
| `_dj_audio_url` | 1 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:615` |
| `_dj_bandcamp` | 2 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/functions.php:711` |
| `_dj_bio` | 4 | 1 | `apollo-djs` | `plugins/apollo-djs/includes/functions.php:723` |
| `_dj_booking` | 5 | 2 | `apollo-djs` | `plugins/apollo-djs/includes/functions.php:714` |
| `_dj_booking_status` | 3 | 2 | `apollo-djs` | `plugins/apollo-djs/src/Admin/Metabox.php:189` |
| `_dj_city` | 2 | 0 | `apollo-events` `apollo-pane-engine` | `plugins/apollo-events/styles/apollo-v2/single-dj.php:99` |
| `_dj_eyebrow` | 3 | 2 | `apollo-djs` | `plugins/apollo-djs/src/Admin/Metabox.php:190` |
| `_dj_footer_image` | 1 | 0 | `apollo-djs` | `plugins/apollo-djs/src/Admin/Metabox.php:191` |
| `_dj_gallery` | 4 | 1 | `apollo-djs` | `plugins/apollo-djs/src/Admin/Metabox.php:460` |
| `_dj_home_city` | 2 | 1 | `apollo-djs` | `plugins/apollo-djs/src/Admin/Metabox.php:188` |
| `_dj_media_kit_stats` | 3 | 2 | `apollo-djs` | `plugins/apollo-djs/src/Admin/Metabox.php:196` |
| `_dj_media_kit_url` | 3 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/functions.php:715` |
| `_dj_name` | 8 | 1 | `apollo-djs` | `plugins/apollo-djs/includes/functions.php:611` |
| `_dj_name_lines` | 3 | 2 | `apollo-djs` | `plugins/apollo-djs/src/Admin/Metabox.php:192` |
| `_dj_original_project_1` | 2 | 0 | `apollo-djs` | `plugins/apollo-djs/src/Admin/Metabox.php:170` |
| `_dj_original_project_2` | 2 | 0 | `apollo-djs` | `plugins/apollo-djs/src/Admin/Metabox.php:171` |
| `_dj_original_project_3` | 2 | 0 | `apollo-djs` | `plugins/apollo-djs/src/Admin/Metabox.php:172` |
| `_dj_preview` | 1 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:616` |
| `_dj_resident_at` | 1 | 0 | `apollo-events` | `plugins/apollo-events/styles/apollo-v2/single-dj.php:100` |
| `_dj_rider_url` | 2 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/functions.php:716` |
| `_dj_role` | 1 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:626` |
| `_dj_set_url` | 3 | 0 | `apollo-djs` `apollo-events` | `plugins/apollo-djs/includes/functions.php:717` |
| `_dj_soundcloud_url` | 1 | 0 | `apollo-events` | `plugins/apollo-events/styles/apollo-v2/single-dj.php:96` |
| `_dj_statement` | 4 | 1 | `apollo-djs` | `plugins/apollo-djs/includes/functions.php:700` |
| `_dj_tracks` | 6 | 2 | `apollo-djs` | `plugins/apollo-djs/includes/functions.php:381` |
| `_doc_clauses` | 1 | 0 | `apollo-sign` | `plugins/apollo-sign/templates/sign.php:49` |
| `_doc_issuer_cnpj` | 1 | 0 | `apollo-sign` | `plugins/apollo-sign/templates/sign.php:45` |
| `_doc_issuer_name` | 1 | 0 | `apollo-sign` | `plugins/apollo-sign/templates/sign.php:44` |
| `_doc_number` | 1 | 0 | `apollo-sign` | `plugins/apollo-sign/templates/sign.php:41` |
| `_doc_pdf_path` | 1 | 0 | `apollo-sign` | `plugins/apollo-sign/src/Plugin.php:105` |
| `_doc_signer_role` | 1 | 0 | `apollo-sign` | `plugins/apollo-sign/templates/sign.php:66` |
| `_doc_signers` | 9 | 6 | `apollo-docs` `apollo-sign` | `plugins/apollo-docs/src/Admin/Metabox.php:63` |
| `_edit_last` | 1 | 0 | `apollo-sheets` | `plugins/apollo-sheets/src/Model.php:194` |
| `_event_access_buttons` | 7 | 2 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1648` |
| `_event_audio_url` | 5 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1693` |
| `_event_bg_color` | 6 | 1 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1691` |
| `_event_coauthors` | 5 | 3 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1174` |
| `_event_date` | 7 | 1 | `apollo-adverts` `apollo-loc` `apollo-seo` `apollo-social` `apollo-templates` | `plugins/apollo-adverts/includes/demo-data.php:84` |
| `_event_date_start` | 2 | 0 | `apollo-hub` | `plugins/apollo-hub/src/API/HubController.php:633` |
| `_event_earlybird_enabled` | 5 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1652` |
| `_event_earlybird_name` | 5 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1656` |
| `_event_earlybird_sub` | 5 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1657` |
| `_event_earlybird_url` | 5 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1658` |
| `_event_highlighted` | 4 | 1 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1737` |
| `_event_import_id` | 1 | 1 | `apollo-events` | `plugins/apollo-events/src/Import/Pipeline/ImportPipeline.php:245` |
| `_event_import_provider` | 1 | 1 | `apollo-events` | `plugins/apollo-events/src/Import/Pipeline/ImportPipeline.php:244` |
| `_event_import_synced` | 1 | 1 | `apollo-events` | `plugins/apollo-events/src/Import/Pipeline/ImportPipeline.php:247` |
| `_event_import_url` | 1 | 1 | `apollo-events` | `plugins/apollo-events/src/Import/Pipeline/ImportPipeline.php:246` |
| `_event_int_rank` | 3 | 2 | `apollo-events` | `plugins/apollo-events/includes/functions.php:403` |
| `_event_lat` | 3 | 2 | `apollo-events` | `plugins/apollo-events/src/Cena_Rio_Submissions.php:228` |
| `_event_list_btn_style` | 4 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1712` |
| `_event_lista_cta_label` | 4 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1734` |
| `_event_lista_fem_enabled` | 5 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1670` |
| `_event_lista_fem_sub` | 5 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1675` |
| `_event_lista_geral_enabled` | 5 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1661` |
| `_event_lista_geral_sub` | 5 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1666` |
| `_event_lng` | 3 | 2 | `apollo-events` | `plugins/apollo-events/src/Cena_Rio_Submissions.php:229` |
| `_event_loc_name` | 4 | 0 | `apollo-email` `apollo-hub` `apollo-social` | `plugins/apollo-email/src/Plugin.php:829` |
| `_event_local_id` | 1 | 0 | `apollo-loc` | `plugins/apollo-loc/includes/functions.php:47` |
| `_event_local_name` | 4 | 1 | `apollo-events` `apollo-templates` | `plugins/apollo-events/src/Cena_Rio_Submissions.php:227` |
| `_event_location` | 1 | 0 | `apollo-calendar` | `plugins/apollo-calendar/src/CalendarAggregator.php:204` |
| `_event_name` | 1 | 0 | `apollo-templates` | `plugins/apollo-templates/templates/template-parts/home/classifieds.php:79` |
| `_event_organizer` | 1 | 0 | `apollo-templates` | `plugins/apollo-templates/templates/template-parts/home/events-listing.php:128` |
| `_event_price` | 1 | 0 | `apollo-pane-engine` | `plugins/apollo-pane-engine/includes/section-renderer.php:884` |
| `_event_reminder_sent` | 1 | 1 | `apollo-events` | `plugins/apollo-events/src/Expiration.php:263` |
| `_event_supplier_ids` | 5 | 2 | `apollo-gestor` | `plugins/apollo-gestor/includes/modules/proj-staff/backend/Proj_Staff.php:51` |
| `_event_ticket_btn_style` | 3 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1711` |
| `_event_ticket_status` | 6 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:1710` |
| `_event_title` | 1 | 1 | `apollo-adverts` | `plugins/apollo-adverts/includes/demo-data.php:83` |
| `_event_venue_name` | 1 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/functions.php:560` |
| `_event_view_count` | 4 | 1 | `apollo-events` | `plugins/apollo-events/src/API/EventsController.php:1201` |
| `_gestor_docs` | 1 | 0 | `apollo-gestor` | `plugins/apollo-gestor/includes/modules/editor/backend/Editor.php:56` |
| `_gestor_equipment` | 2 | 1 | `apollo-gestor` | `plugins/apollo-gestor/includes/modules/proj-equip/backend/Proj_Equip.php:50` |
| `_hub_avatar_type` | 2 | 1 | `apollo-hub` | `plugins/apollo-hub/includes/functions.php:53` |
| `_hub_blocks` | 7 | 3 | `apollo-hub` | `plugins/apollo-hub/includes/functions.php:66` |
| `_hub_view_count` | 1 | 0 | `apollo-admin` | `plugins/apollo-admin/templates/partials/sections/admin/spreadsheet.php:465` |
| `_loc` | 1 | 0 | `apollo-templates` | `plugins/apollo-templates/templates/template-parts/home/classifieds.php:82` |
| `_loc_address` | 2 | 0 | `apollo-events` `apollo-pane-engine` | `plugins/apollo-events/includes/functions.php:678` |
| `_loc_city` | 4 | 0 | `apollo-djs` `apollo-events` `apollo-fav` | `plugins/apollo-djs/templates/parts/dj-v3/agenda.php:47` |
| `_loc_gallery` | 2 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:662` |
| `_loc_lat` | 1 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:680` |
| `_loc_lng` | 1 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:681` |
| `_local_amenities` | 3 | 1 | `apollo-loc` | `plugins/apollo-loc/src/Admin/Metabox/DetailsMetabox.php:54` |
| `_local_bairro` | 1 | 0 | `apollo-events` | `plugins/apollo-events/styles/apollo-v2/single-loc.php:100` |
| `_local_description` | 4 | 1 | `apollo-loc` | `plugins/apollo-loc/src/Admin/Metabox/DetailsMetabox.php:36` |
| `_local_facebook` | 1 | 0 | `apollo-loc` | `plugins/apollo-loc/src/Admin/Metabox/ContactMetabox.php:64` |
| `_local_founded_year` | 3 | 2 | `apollo-loc` | `plugins/apollo-loc/src/Admin/Metabox/DetailsMetabox.php:70` |
| `_local_gallery` | 3 | 0 | `apollo-events` | `plugins/apollo-events/includes/functions.php:660` |
| `_local_hours` | 3 | 1 | `apollo-loc` | `plugins/apollo-loc/src/Admin/Metabox/DetailsMetabox.php:37` |
| `_local_image_` | 1 | 0 | `apollo-loc` | `plugins/apollo-loc/src/Admin/Metabox/GalleryMetabox.php:63` |
| `_local_image_1` | 3 | 0 | `apollo-loc` `apollo-templates` | `plugins/apollo-loc/includes/functions-address.php:105` |
| `_local_image_{$i}` | 3 | 0 | `apollo-loc` | `plugins/apollo-loc/src/API/Schema/LocalSchema.php:47` |
| `_local_region` | 3 | 2 | `apollo-loc` | `plugins/apollo-loc/src/Admin/Metabox/DetailsMetabox.php:68` |
| `_local_rooms` | 2 | 1 | `apollo-loc` | `plugins/apollo-loc/src/Admin/Metabox/DetailsMetabox.php:72` |
| `_local_tagline` | 2 | 1 | `apollo-loc` | `plugins/apollo-loc/src/Admin/Metabox/DetailsMetabox.php:69` |
| `_local_testimonials` | 3 | 0 | `apollo-loc` | `plugins/apollo-loc/src/Admin/Metabox/DetailsMetabox.php:76` |
| `_local_user_id` | 3 | 2 | `apollo-loc` | `plugins/apollo-loc/src/Admin/Metabox/DetailsMetabox.php:71` |
| `_local_visit_count` | 2 | 1 | `apollo-loc` | `plugins/apollo-loc/src/Integration/SocialIntegration.php:50` |
| `_membership_features` | 1 | 0 | `apollo-admin` | `plugins/apollo-admin/templates/frontend/memberships.php:315` |
| `_membership_period` | 1 | 0 | `apollo-admin` | `plugins/apollo-admin/templates/frontend/memberships.php:314` |
| `_membership_price` | 1 | 0 | `apollo-admin` | `plugins/apollo-admin/templates/frontend/memberships.php:313` |
| `_neighborhood` | 1 | 0 | `apollo-templates` | `plugins/apollo-templates/templates/template-parts/home/classifieds.php:130` |
| `_nrep_code` | 16 | 2 | `apollo-journal` | `plugins/apollo-journal/src/Admin.php:153` |
| `_nrep_seq` | 3 | 1 | `apollo-journal` | `plugins/apollo-journal/src/Admin.php:515` |
| `_nrep_year` | 2 | 1 | `apollo-journal` | `plugins/apollo-journal/src/Admin.php:514` |
| `_nucleo_role` | 1 | 0 | `apollo-templates` | `plugins/apollo-templates/includes/feed-data.php:253` |
| `_open_badge_criteria` | 1 | 0 | `apollo-membership` | `plugins/apollo-membership/src/API/AchievementsController.php:323` |
| `_open_badge_enable_baking` | 1 | 0 | `apollo-membership` | `plugins/apollo-membership/src/API/AchievementsController.php:330` |
| `_original_price` | 1 | 0 | `apollo-templates` | `plugins/apollo-templates/templates/template-parts/home/classifieds.php:84` |
| `_price` | 3 | 2 | `apollo-adverts` `apollo-templates` | `plugins/apollo-adverts/includes/demo-data.php:86` |
| `_price_per_night` | 1 | 0 | `apollo-templates` | `plugins/apollo-templates/templates/template-parts/home/classifieds.php:131` |
| `_rank_points_required` | 1 | 0 | `apollo-membership` | `plugins/apollo-membership/includes/ranks/rank-functions.php:90` |
| `_rank_priority` | 1 | 0 | `apollo-membership` | `plugins/apollo-membership/includes/ranks/rank-functions.php:89` |
| `_rating` | 1 | 1 | `apollo-adverts` | `plugins/apollo-adverts/includes/demo-data.php:148` |
| `_supplier_category` | 2 | 0 | `apollo-gestor` | `plugins/apollo-gestor/includes/modules/proj-staff/backend/Proj_Staff.php:69` |
| `_supplier_email` | 1 | 0 | `apollo-gestor` | `plugins/apollo-gestor/includes/modules/proj-staff/backend/Proj_Staff.php:71` |
| `_supplier_phone` | 1 | 0 | `apollo-gestor` | `plugins/apollo-gestor/includes/modules/proj-staff/backend/Proj_Staff.php:70` |
| `_ticket_type` | 1 | 0 | `apollo-templates` | `plugins/apollo-templates/templates/template-parts/home/classifieds.php:81` |
| `_ticket_url` | 1 | 0 | `apollo-templates` | `plugins/apollo-templates/templates/template-parts/home/events-listing.php:126` |
| `_track_artists` | 1 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/tracks.php:202` |
| `_track_cover_url` | 1 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/tracks.php:268` |
| `_track_dj_ids` | 2 | 1 | `apollo-djs` | `plugins/apollo-djs/bin/migrate-dj-tracks.php:224` |
| `_track_duration` | 1 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/tracks.php:525` |
| `_track_genre_legacy` | 1 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/tracks.php:489` |
| `_track_ghost_artists` | 1 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/tracks.php:151` |
| `_track_migrated_from` | 1 | 1 | `apollo-djs` | `plugins/apollo-djs/bin/migrate-dj-tracks.php:225` |
| `_track_preview_seconds` | 2 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/track-listen.php:202` |
| `_track_preview_start` | 2 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/track-listen.php:201` |
| `_track_preview_url` | 3 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/track-listen.php:220` |
| `_track_url_bandcamp` | 2 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/tracks.php:318` |
| `_track_url_download` | 2 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/track-listen.php:253` |
| `_track_url_soundcloud` | 4 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/track-listen.php:205` |
| `_track_url_spotify` | 2 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/tracks.php:317` |
| `_track_url_youtube` | 2 | 0 | `apollo-djs` | `plugins/apollo-djs/includes/tracks.php:320` |
| `_wp_attachment_image_alt` | 2 | 0 | `apollo-events` `apollo-seo` | `plugins/apollo-events/includes/functions.php:741` |
| `_wp_page_template` | 3 | 2 | `mu-plugin` | `mu-plugin/apollo-route-setup.php:138` |
| `canonical` | 1 | 0 | `apollo-seo` | `plugins/apollo-seo/src/Meta.php:886` |
| `nofollow` | 1 | 0 | `apollo-seo` | `plugins/apollo-seo/src/Meta.php:940` |
| `noindex` | 1 | 0 | `apollo-seo` | `plugins/apollo-seo/src/Meta.php:937` |
| `og_description` | 1 | 0 | `apollo-seo` | `plugins/apollo-seo/src/Meta.php:1088` |
| `og_title` | 1 | 0 | `apollo-seo` | `plugins/apollo-seo/src/Meta.php:1087` |


## Governed but never touched

| key | scope / group | type |
|---|---|---|
| `_apollo_achievement_count` | `user/membership` | integer |
| `_apollo_active_achievements` | `user/membership` | array |
| `_apollo_agent_for` | `user/profile` | array |
| `_apollo_avatar_attachment_id` | `user/login` | integer |
| `_apollo_avatar_url` | `user/login` | string |
| `_apollo_bio` | `user/profile` | string |
| `_apollo_birth_date` | `user/profile` | string |
| `_apollo_can_notify_user` | `user/membership` | boolean |
| `_apollo_cena_access` | `user/cena` | boolean |
| `_apollo_cena_role` | `user/cena` | string |
| `_apollo_chat_blocked_users` | `user/chat` | array |
| `_apollo_chat_last_seen` | `user/chat` | integer |
| `_apollo_chat_muted_threads` | `user/chat` | array |
| `_apollo_chat_preferences` | `user/chat` | array |
| `_apollo_chat_status` | `user/chat` | string |
| `_apollo_current_rank` | `user/membership` | string |
| `_apollo_dashboard_layout` | `user/dashboard` | array |
| `_apollo_disable_author_url` | `user/profile` | boolean |
| `_apollo_email_prefs` | `user/notifications` | array |
| `_apollo_email_verified` | `user/login` | boolean |
| `_apollo_instagram` | `user/login` | string |
| `_apollo_last_login` | `user/login` | string |
| `_apollo_lockout_until` | `user/login` | integer |
| `_apollo_login_attempts` | `user/login` | integer |
| `_apollo_matchmaking_data` | `user/profile` | array |
| `_apollo_membership` | `user/profile` | array |
| `_apollo_notif_prefs` | `user/notifications` | array |
| `_apollo_notif_unread` | `user/notifications` | integer |
| `_apollo_password_reset_expires` | `user/login` | integer |
| `_apollo_password_reset_token` | `user/login` | string |
| `_apollo_phone` | `user/profile` | string |
| `_apollo_points_total` | `user/membership` | integer |
| `_apollo_privacy_email` | `user/profile` | boolean |
| `_apollo_privacy_profile` | `user/profile` | string |
| `_apollo_profile_completed` | `user/profile` | integer |
| `_apollo_profile_views` | `user/profile` | integer |
| `_apollo_quiz_answers` | `user/login` | array |
| `_apollo_quiz_score` | `user/login` | integer |
| `_apollo_rank_entry_id` | `user/membership` | integer |
| `_apollo_seo` | `post/_global` | array |
| `_apollo_seo_term` | `term/seo` | array |
| `_apollo_simon_highscore` | `user/login` | integer |
| `_apollo_social_name` | `user/login` | string |
| `_apollo_sound_preferences` | `user/login` | array |
| `_apollo_triggered_triggers` | `user/membership` | array |
| `_apollo_user_verified` | `user/profile` | boolean |
| `_apollo_verification_token` | `user/login` | string |
| `_apollo_website` | `user/profile` | string |
| `_coauthors` | `post/_global` | array |
| `_supplier_address` | `post/supplier` | string |
| `_supplier_cnpj` | `post/supplier` | string |
| `_supplier_company` | `post/supplier` | string |
| `_supplier_contact_email` | `post/supplier` | string |
| `_supplier_contact_name` | `post/supplier` | string |
| `_supplier_contact_phone` | `post/supplier` | string |
| `_supplier_rating` | `post/supplier` | number |
| `_supplier_verified` | `post/supplier` | boolean |
| `_supplier_website` | `post/supplier` | string |
| `avatar_thumb` | `user/login` | string |
| `cover_image` | `user/profile` | integer |
| `custom_avatar` | `user/profile` | integer |
| `instagram` | `user/profile` | string |
| `user_location` | `user/profile` | string |


## Governed post meta, by CPT

### `event` — 18 governed keys

| key | type | format | in REST | default | code touches |
|---|---|---|---|---|---|
| `_event_start_date` | string | `Y-m-d` | yes | — | 65 |
| `_event_end_date` | string | `Y-m-d` | yes | — | 13 |
| `_event_start_time` | string | `H:i` | yes | — | 38 |
| `_event_end_time` | string | `H:i` | yes | — | 13 |
| `_event_dj_ids` | array | — | yes | — | 24 |
| `_event_dj_slots` | array | — | yes | — | 14 |
| `_event_loc_id` | integer | — | yes | — | 34 |
| `_event_banner` | integer | — | yes | — | 18 |
| `_event_ticket_url` | string | `url` | yes | — | 11 |
| `_event_ticket_price` | string | — | yes | — | 10 |
| `_event_privacy` | string | — | yes | — | 7 |
| `_event_status` | string | — | yes | — | 17 |
| `_event_is_gone` | string | — | yes | — | 7 |
| `_event_budget` | number | — | no | — | 2 |
| `_event_video_url` | string | `url` | yes | — | 5 |
| `_event_gallery` | array | — | yes | — | 8 |
| `_event_coupon_code` | string | — | yes | — | 5 |
| `_event_list_url` | string | `url` | yes | — | 4 |

### `dj` — 11 governed keys

| key | type | format | in REST | default | code touches |
|---|---|---|---|---|---|
| `_dj_image` | integer | — | yes | — | 7 |
| `_dj_banner` | integer | — | yes | — | 3 |
| `_dj_website` | string | `url` | yes | — | 2 |
| `_dj_instagram` | string | — | yes | — | 7 |
| `_dj_soundcloud` | string | `url` | yes | — | 4 |
| `_dj_spotify` | string | `url` | yes | — | 3 |
| `_dj_youtube` | string | `url` | yes | — | 1 |
| `_dj_mixcloud` | string | `url` | yes | — | 1 |
| `_dj_user_id` | integer | — | yes | — | 4 |
| `_dj_verified` | boolean | — | yes | — | 6 |
| `_dj_bio_short` | string | — | yes | — | 9 |

### `local` — 13 governed keys

| key | type | format | in REST | default | code touches |
|---|---|---|---|---|---|
| `_local_name` | string | — | yes | — | 3 |
| `_local_address` | string | — | yes | — | 13 |
| `_local_city` | string | — | yes | — | 12 |
| `_local_state` | string | — | yes | — | 6 |
| `_local_country` | string | — | yes | `"Brasil"` | 2 |
| `_local_postal` | string | — | yes | — | 2 |
| `_local_lat` | number | — | yes | — | 19 |
| `_local_lng` | number | — | yes | — | 19 |
| `_local_phone` | string | — | yes | — | 6 |
| `_local_website` | string | `url` | yes | — | 3 |
| `_local_instagram` | string | — | yes | — | 3 |
| `_local_capacity` | integer | — | yes | — | 9 |
| `_local_price_range` | string | — | yes | — | 7 |

### `classified` — 9 governed keys

| key | type | format | in REST | default | code touches |
|---|---|---|---|---|---|
| `_classified_price` | number | — | yes | — | 20 |
| `_classified_currency` | string | — | yes | `"BRL"` | 7 |
| `_classified_negotiable` | boolean | — | yes | — | 4 |
| `_classified_condition` | string | — | yes | — | 5 |
| `_classified_loc` | string | — | yes | — | 8 |
| `_classified_contact_phone` | string | — | yes | — | 1 |
| `_classified_contact_whatsapp` | string | — | yes | — | 1 |
| `_classified_expires_at` | string | `Y-m-d` | yes | — | 8 |
| `_classified_featured` | boolean | — | yes | — | 5 |

### `supplier` — 9 governed keys

| key | type | format | in REST | default | code touches |
|---|---|---|---|---|---|
| `_supplier_company` | string | — | yes | — | **0** |
| `_supplier_cnpj` | string | — | yes | — | **0** |
| `_supplier_contact_name` | string | — | yes | — | **0** |
| `_supplier_contact_email` | string | `email` | yes | — | **0** |
| `_supplier_contact_phone` | string | — | yes | — | **0** |
| `_supplier_website` | string | `url` | yes | — | **0** |
| `_supplier_address` | string | — | yes | — | **0** |
| `_supplier_verified` | boolean | — | yes | — | **0** |
| `_supplier_rating` | number | — | yes | — | **0** |

### `doc` — 8 governed keys

| key | type | format | in REST | default | code touches |
|---|---|---|---|---|---|
| `_doc_file_id` | integer | — | yes | — | 8 |
| `_doc_folder_id` | integer | — | yes | — | 4 |
| `_doc_access` | string | — | yes | — | 7 |
| `_doc_version` | string | — | yes | — | 9 |
| `_doc_downloads` | integer | — | yes | — | 6 |
| `_doc_status` | string | — | yes | — | 15 |
| `_doc_checksum` | string | — | no | — | 5 |
| `_doc_cpf` | string | — | no | — | 5 |

### `hub` — 7 governed keys

| key | type | format | in REST | default | code touches |
|---|---|---|---|---|---|
| `_hub_bio` | string | — | yes | — | 3 |
| `_hub_links` | array | — | yes | — | 6 |
| `_hub_socials` | array | — | yes | — | 6 |
| `_hub_theme` | string | — | yes | — | 5 |
| `_hub_avatar` | integer | — | yes | — | 4 |
| `_hub_cover` | integer | — | yes | — | 3 |
| `_hub_custom_css` | string | — | no | — | 5 |

### `email_aprio` — 3 governed keys

| key | type | format | in REST | default | code touches |
|---|---|---|---|---|---|
| `_email_subject` | string | — | yes | — | 10 |
| `_email_type` | string | — | yes | — | 9 |
| `_email_variables` | array | — | yes | — | 9 |

### `_global` — 9 governed keys

| key | type | format | in REST | default | code touches |
|---|---|---|---|---|---|
| `_fav_count` | integer | — | yes | `0` | 1 |
| `_wow_count` | integer | — | yes | `0` | 1 |
| `_wow_counts` | object | — | yes | — | 1 |
| `_coauthors` | array | — | yes | — | **0** |
| `_mod_status` | string | — | no | — | 1 |
| `_mod_notes` | string | — | no | — | 1 |
| `_mod_reviewed_by` | integer | — | no | — | 1 |
| `_mod_reviewed_at` | string | `datetime` | no | — | 1 |
| `_apollo_seo` | array | — | no | — | **0** |

### `page` — 2 governed keys

| key | type | format | in REST | default | code touches |
|---|---|---|---|---|---|
| `_apollo_template` | string | — | yes | — | 1 |
| `_apollo_canvas_data` | array | — | no | — | 1 |


## Governed user and term meta

### `user` / `login` — 16 keys

| key | type | required | in REST | default |
|---|---|---|---|---|
| `_apollo_social_name` | string | yes | no | — |
| `_apollo_instagram` | string | yes | no | — |
| `_apollo_avatar_url` | string | no | no | — |
| `_apollo_avatar_attachment_id` | integer | no | no | — |
| `avatar_thumb` | string | no | no | — |
| `_apollo_sound_preferences` | array | yes | no | — |
| `_apollo_quiz_score` | integer | no | no | — |
| `_apollo_simon_highscore` | integer | no | no | — |
| `_apollo_quiz_answers` | array | no | no | — |
| `_apollo_email_verified` | boolean | no | no | — |
| `_apollo_verification_token` | string | no | no | — |
| `_apollo_password_reset_token` | string | no | no | — |
| `_apollo_password_reset_expires` | integer | no | no | — |
| `_apollo_login_attempts` | integer | no | no | — |
| `_apollo_last_login` | string | no | no | — |
| `_apollo_lockout_until` | integer | no | no | — |

### `user` / `profile` — 17 keys

| key | type | required | in REST | default |
|---|---|---|---|---|
| `_apollo_user_verified` | boolean | no | no | — |
| `_apollo_membership` | array | no | yes | `["nao-verificado"]` |
| `_apollo_agent_for` | array | no | no | `[]` |
| `_apollo_profile_completed` | integer | no | no | — |
| `_apollo_matchmaking_data` | array | no | no | — |
| `cover_image` | integer | no | no | — |
| `custom_avatar` | integer | no | no | — |
| `instagram` | string | no | no | — |
| `user_location` | string | no | no | — |
| `_apollo_bio` | string | no | no | — |
| `_apollo_website` | string | no | no | — |
| `_apollo_phone` | string | no | no | — |
| `_apollo_birth_date` | string | no | no | — |
| `_apollo_privacy_profile` | string | no | no | `"public"` |
| `_apollo_privacy_email` | boolean | no | no | `true` |
| `_apollo_disable_author_url` | boolean | no | no | `true` |
| `_apollo_profile_views` | integer | no | no | `0` |

### `user` / `chat` — 5 keys

| key | type | required | in REST | default |
|---|---|---|---|---|
| `_apollo_chat_status` | string | no | no | `"offline"` |
| `_apollo_chat_last_seen` | integer | no | no | — |
| `_apollo_chat_preferences` | array | no | no | — |
| `_apollo_chat_blocked_users` | array | no | no | — |
| `_apollo_chat_muted_threads` | array | no | no | — |

### `user` / `notifications` — 3 keys

| key | type | required | in REST | default |
|---|---|---|---|---|
| `_apollo_notif_prefs` | array | no | no | — |
| `_apollo_notif_unread` | integer | no | no | `0` |
| `_apollo_email_prefs` | array | no | no | — |

### `user` / `membership` — 7 keys

| key | type | required | in REST | default |
|---|---|---|---|---|
| `_apollo_triggered_triggers` | array | no | no | — |
| `_apollo_can_notify_user` | boolean | no | no | `true` |
| `_apollo_active_achievements` | array | no | no | — |
| `_apollo_achievement_count` | integer | no | no | `0` |
| `_apollo_points_total` | integer | no | no | `0` |
| `_apollo_current_rank` | string | no | no | — |
| `_apollo_rank_entry_id` | integer | no | no | — |

### `user` / `cena` — 2 keys

| key | type | required | in REST | default |
|---|---|---|---|---|
| `_apollo_cena_access` | boolean | no | no | — |
| `_apollo_cena_role` | string | no | no | — |

### `user` / `dashboard` — 1 keys

| key | type | required | in REST | default |
|---|---|---|---|---|
| `_apollo_dashboard_layout` | array | no | no | — |

### `term` / `seo` — 1 keys

| key | type | required | in REST | default |
|---|---|---|---|---|
| `_apollo_seo_term` | array | no | no | — |


---

_Generated 2026-09-15T05:29:41.847Z from source at `D:/dev/_apollo.rio.br`. Regenerate: `node D:/dev/_cos/verify/gen-dev-registry.js`._
