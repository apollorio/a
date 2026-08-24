# 🗺 ADD NEW EVENT — Complete Field Map (form → DB → single page)

**Why each question is asked**: every input exists because a specific element of the
single event page (`/evento/{slug}`, mockup `event-single-page.html`) consumes it.
Chain: form part (PHP) → hidden/named input → `collectPayload()` (create-bridge.js) →
`POST/PUT apollo/v1/eventos` (EventsController) → post/meta → single-page part.

| # | Form card (part file) | Input | Payload key | Meta / storage | Renders on single page as |
|---|---|---|---|---|---|
| 1 | Informações Básicas (`form-basic.php`) | Título | `title` | `post_title` | Hero `<h1 class="ev-title">` (split lines + accent) |
| 2 | ″ | Cor de fundo (pill ao lado do título) | `bg_color` | `_event_bg_color` | Hero `background-color` — base of the color→image→video fallback chain |
| 3 | ″ | Widget data/hora (4 hidden) | `start_date/start_time/end_date/end_time` | `_event_start_date` etc. | Hero meta line + facts strip + expiration engine (`_event_is_gone` 30min after end) |
| 4 | ″ | Sobre o Evento | `content` | `post_content` | "Sobre" section |
| 5 | Local e Endereço (`form-venue.php`) | Buscar Local (datalist) → hidden `ev-loc-id` | `loc_id` | `_event_loc_id` | Venue section: name, address, gallery, Leaflet map (via `apollo_event_get_loc`) |
| 6 | ″ | Modal "Cadastrar Local" | `POST apollo/v1/local` `{title,address,lat,lng}` | new `local` CPT post | Same as above once selected |
| 7 | Line Up (`form-lineup.php` + DJ modal) | pick-DJ / quick-add + horários + badge → hidden `ev-dj-ids`/`ev-dj-slots` | `dj_ids`, `dj_slots` | `_event_dj_ids`, `_event_dj_slots` | LINE UP marquee + DJ cards + timetable (name, foto, horários, badge) |
| 8 | Mídia e Links (`form-media.php`) | Imagem de Capa (wp.media) → hidden `ev-banner` | `banner` | `_event_banner` + post thumbnail | Hero background image (layer 2 of fallback) + cards/OG |
| 9 | ″ | URL do Vídeo | `video_url` | `_event_video_url` | Hero ambient YouTube layer (layer 3, autoplay/mute) |
| 10 | ″ | Playlist Spotify/SoundCloud | `audio_url` | `_event_audio_url` | Player/embed section |
| 11 | ″ | Galeria (3 slots) → hidden `ev-gallery` | `gallery` | `_event_gallery` (int[]) | Gallery grid + lightbox |
| 12 | Taxonomia e Status (`form-taxonomy.php`) | Temporada | `seasons` | `season` tax terms | Season tag/badge |
| 13 | ″ | Status dos Ingressos | `ticket_status` | `_event_ticket_status` | Availability badge (Gratuito/Disponível/Esgotando/Esgotado) |
| 14 | ″ | Status do Evento | `event_status` | `_event_status` | Cancelled/postponed banners, card states |
| 15 | ″ | Privacidade | `privacy` | `_event_privacy` | Visibility gating (REST `can_view_event`) |
| 16 | ″ | Sons/Gêneros (search + chips, cap 5) | `sounds` | `sound` tax terms | Genre tags on hero/cards + archive filter |
| 17 | Early Bird e Listas (`form-access.php` + lightbox) | "+ Novo Ticket ou Lista" → hidden `ev-access-buttons` | `access_buttons` | `_event_access_buttons` `[{kind,style,label,sub,url}]` | ACCESS section: TYPE001 `ev-ticket` / TYPE002 `is-soft` / TYPE003 `is-lista`·`is-fem` / TYPE004 `ev-lista-cta` — all `target="_blank"` |
| 18 | Cupons (`form-coupons.php`) | toggle + tags → hidden `ev-coupon-code` | `coupon_code` | `_event_coupon_code` (CSV) | Coupon copy-box (`ri-coupon-3-line`, "COPIAR") |
| 19 | Equipe do Evento (`form-coauthors.php`) | search + chips (cap 20) → hidden `ev-coauthors` | `coauthors` | `_coauthors` (apollo-coauthor) | NOT rendered publicly — grants edit rights (not delete); remote `GET /users` |

Removed this cycle (now lightbox-only or retired): Estilo Botão Ingresso/Lista,
Preço do Ingresso, URL dos Ingressos, URL da Lista Amiga, Texto CTA da Lista —
all replaced by the `access_buttons` repeater.

## The 3 bugs that made it "barely working" (all fixed)

1. **DJ select empty** — `buildDJOptions()` ran on `DOMContentLoaded`, but the `.as2`
   combobox engine (create-shell.js) snapshots options synchronously at load →
   wired an empty list forever. Now populated synchronously before shell.js.
2. **LOC select empty** — form + classic metabox queried `post_type='loc'`;
   the CPT is `local` (`APOLLO_LOCAL_CPT`) → `get_posts` returned [] every time.
3. **Quick-add DJ/LOC dead for non-admins** —
   `POST apollo/v1/local` demanded `manage_options` (full admin, both Router.php and
   LocalsController.php) and `POST apollo/v1/djs` demanded `edit_posts` → the modals
   silently 403'd for organizers. Now: logged-in may create; **publishers go live
   instantly, everyone else lands in `pending`** (moderation kept — render helpers
   only show `publish`). Loc quick-add now also sends the geocoded `lat/lng` it was
   previously throwing away. Update/Delete remain admin-only.

## DJ / LOC standalone "add new" forms

The event form's modals ARE the add-new surfaces for dj/loc inside this flow — they
persist real `dj`/`local` CPT posts via the same REST endpoints as everything else,
so fixing the endpoints fixes all three forms at once. (wp-admin classic editors for
dj/loc remain as before.)

## Verified

Full payload trace: every key `collectPayload()` sends has a matching server-side
handler (meta_map / array handler / taxonomy) — zero orphans. All PHP balanced, all
JS + inline scripts pass `node --check`. No PHP binary in sandbox → `php -l` the
changed files: `apollo-loc/src/API/{Router,LocalsController,Endpoints/CreateEndpoint}.php`,
`apollo-djs/src/API/DJsController.php`, plus the apollo-events files from this cycle.

## Registry compliance (data-registry.json = SSOT)

Cross-checked every field the registry declares against the live REST controllers.

**EVENT** — 23 round-trip REST fields in the registry (`id`/`chat`/`participantes`
excluded: URL-param + runtime + table, not form-saved). **All 23 accepted and
persisted** by `EventsController` (meta_map + array handlers + taxonomy + post
title/content). `MISSING = NONE`. Save→render round trip confirmed: single page reads
`_event_access_buttons`, `_event_bg_color`, and the rest via
`apollo_event_build_access_payload()` + `single-event.php`.

**DJ quick-add (lightbox)** — posts `{title, instagram}` → `create_dj` sets
`post_title` + `_dj_name`, `save_meta` maps `instagram → _dj_instagram` (both registry
meta keys). Publisher → `publish` (renders immediately); others → `pending` (moderated).

**LOC quick-add (lightbox)** — posts `{title, address, lat, lng}` → `CreateEndpoint`
sets `post_title`, `LocalSchema::save_meta` maps `address → _local_address`,
`lat/lng → _local_lat/_local_lng` (all registry keys). Same publish/pending contract.

Because both quick-adds persist through the same REST endpoints the standalone dj/loc
editors use, fixing the endpoints made **all three forms** compliant at once.

**Registry gaps deliberately NOT touched** (STRICT — registry marks them
`DECISION_REQUIRED`, "not minor adjusts"): `local.testimonials` (no writer — runtime/
comment-fed) and `local.marquee` (mapped to `_local_amenities`, no dedicated meta).
These are feature builds awaiting a product decision, not compliance bugs.
