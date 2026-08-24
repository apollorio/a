/**
 * Build plugins/_inventory/data-registry.json from mockup SSOT + live CPT meta.
 * Run: node _build-data-registry.js
 */
'use strict';
const fs = require('fs');
const path = require('path');

const OUT = path.join(__dirname, 'data-registry.json');

/** @param {object} partial */
function field(partial) {
  const f = {
    id: partial.id,
    label: partial.label,
    cpt: partial.cpt,
    mockup: {
      file: partial.mockup.file,
      payload: partial.mockup.payload || null,
      section: partial.mockup.section || null,
      ui: partial.mockup.ui || null,
      hardcoded_in_html: !!partial.mockup.hardcoded_in_html,
      sample: partial.mockup.sample != null ? partial.mockup.sample : null
    },
    storage: {
      kind: partial.storage.kind,
      key: partial.storage.key,
      type: partial.storage.type,
      owner_cpt: partial.storage.owner_cpt || partial.cpt,
      mockup_key_legacy: partial.storage.mockup_key_legacy || null
    },
    payload_key: partial.payload_key || null,
    input: {
      frontend: partial.input?.frontend ?? null,
      metabox: partial.input?.metabox ?? null,
      rest: partial.input?.rest ?? null
    },
    required_for_render: partial.required_for_render !== false,
    empty_behavior: partial.empty_behavior || 'hide',
    notes: partial.notes || ''
  };
  return f;
}

const MOCK = {
  event: 'screen/single cpt/event/single-event/event-single-page.html',
  eventData: 'screen/single cpt/event/single-event/assets/data/simulated.data.js',
  dj: 'screen/single cpt/dj/dj-single-page.html',
  loc: 'screen/single cpt/location/loc-single-page.html',
  layout: 'screen/_official_layout/layout.html',
  layoutData: 'screen/_official_layout/simulated.data.js'
};

const eventFields = [
  field({ id: 'event.id', label: 'Event ID / slug', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'APOLLO_EVENT_SIMULATED.id', section: 'identity', sample: 'sunset-theory-vol-04' }, storage: { kind: 'post', key: 'ID|post_name', type: 'int|string' }, payload_key: 'id', input: { rest: 'id' }, required_for_render: true, empty_behavior: 'required' }),
  field({ id: 'event.share.title', label: 'Share title', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'share.title', section: 'share', sample: 'Sunset Theory Vol.04' }, storage: { kind: 'post', key: 'post_title', type: 'string' }, payload_key: 'share.title', input: { frontend: '#ev-title', metabox: 'title', rest: 'title' } }),
  field({ id: 'event.hero.kicker', label: 'Hero kicker', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'hero.kicker', section: 'hero', sample: 'Apollo · Rio' }, storage: { kind: 'derived', key: 'brand_static|seo', type: 'string' }, payload_key: 'hero.kicker', input: {}, empty_behavior: 'fallback', notes: 'Often static brand line; may become meta later.' }),
  field({ id: 'event.hero.titleLines', label: 'Hero title lines', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'hero.titleLines', section: 'hero', ui: '.ev-title', sample: ['Sunset','Theory'] }, storage: { kind: 'post', key: 'post_title', type: 'string' }, payload_key: 'hero.titleLines', input: { frontend: '#ev-title', rest: 'title' }, notes: 'Split client/PHP from post_title + optional accent.' }),
  field({ id: 'event.hero.titleAccent', label: 'Hero title accent', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'hero.titleAccent', section: 'hero', sample: 'Vol.04' }, storage: { kind: 'derived', key: 'post_title_split', type: 'string' }, payload_key: 'hero.titleAccent', input: { frontend: '#ev-title' }, empty_behavior: 'omit' }),
  field({ id: 'event.hero.image', label: 'Hero banner image', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'hero.image', section: 'hero', ui: '.ev-hero-media img' }, storage: { kind: 'meta', key: '_event_banner', type: 'attachment_id' }, payload_key: 'hero.image', input: { frontend: '#ev-banner', metabox: true, rest: 'banner' } }),
  field({ id: 'event.hero.youtubeId', label: 'Hero YouTube id', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'hero.youtubeId', section: 'hero', ui: '.ev-hero-yt' }, storage: { kind: 'meta', key: '_event_video_url', type: 'string_url' }, payload_key: 'hero.youtubeId', input: { frontend: '#ev-video', rest: 'video_url' }, empty_behavior: 'hide', notes: 'Parse YT id from URL at render.' }),
  field({ id: 'event.hero.youtubeDelayMs', label: 'YouTube reveal delay', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'hero.youtubeDelayMs', section: 'hero', sample: 2600 }, storage: { kind: 'runtime', key: 'ui_const', type: 'int' }, payload_key: 'hero.youtubeDelayMs', input: {}, required_for_render: false, empty_behavior: 'fallback', notes: 'Presentation constant, not DB.' }),
  field({ id: 'event.hero.meta', label: 'Hero meta chips', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'hero.meta[]', section: 'hero', ui: '.ev-hero-meta' }, storage: { kind: 'derived', key: '_event_start_date+_event_start_time+_event_loc_id', type: 'object[]' }, payload_key: 'hero.meta', input: {}, notes: 'Built from date/time + loc name.' }),
  field({ id: 'event.facts', label: 'Facts strip', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'facts[]', section: 'facts' }, storage: { kind: 'derived', key: 'schedule+loc', type: 'object[]' }, payload_key: 'facts', input: {} }),
  field({ id: 'event.start_date', label: 'Start date', cpt: 'event', mockup: { file: MOCK.eventData, payload: '→ facts/hero.meta', section: 'schedule' }, storage: { kind: 'meta', key: '_event_start_date', type: 'string_ymd' }, payload_key: 'start_date', input: { frontend: '#start_date', metabox: true, rest: 'start_date' } }),
  field({ id: 'event.end_date', label: 'End date', cpt: 'event', mockup: { file: MOCK.eventData, payload: '→ facts/hero.meta', section: 'schedule' }, storage: { kind: 'meta', key: '_event_end_date', type: 'string_ymd' }, payload_key: 'end_date', input: { frontend: '#end_date', rest: 'end_date' } }),
  field({ id: 'event.start_time', label: 'Start time', cpt: 'event', mockup: { file: MOCK.eventData, payload: '→ facts', section: 'schedule' }, storage: { kind: 'meta', key: '_event_start_time', type: 'string_hm' }, payload_key: 'start_time', input: { frontend: '#start_time', rest: 'start_time' } }),
  field({ id: 'event.end_time', label: 'End time', cpt: 'event', mockup: { file: MOCK.eventData, payload: '→ facts', section: 'schedule' }, storage: { kind: 'meta', key: '_event_end_time', type: 'string_hm' }, payload_key: 'end_time', input: { frontend: '#end_time', rest: 'end_time' } }),
  field({ id: 'event.genres', label: 'Genre marquee', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'genres[]', section: 'marquee', sample: ['Techno','Industrial'] }, storage: { kind: 'taxonomy', key: 'sound', type: 'term[]' }, payload_key: 'genres', input: { frontend: '.ev-genre', rest: 'sounds' } }),
  field({ id: 'event.season', label: 'Season', cpt: 'event', mockup: { file: MOCK.event, payload: null, section: 'taxonomy', hardcoded_in_html: false }, storage: { kind: 'taxonomy', key: 'event_season', type: 'term' }, payload_key: 'seasons', input: { frontend: '#ev-season', rest: 'seasons' }, required_for_render: false, empty_behavior: 'hide', notes: 'In form/live single; not always in simulated.data.js object.' }),
  field({ id: 'event.rsvp.title', label: 'RSVP block title', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'rsvp.title', section: 'rsvp' }, storage: { kind: 'runtime', key: 'i18n_copy', type: 'string' }, payload_key: 'rsvp.title', input: {}, required_for_render: false }),
  field({ id: 'event.rsvp.me', label: 'Current user RSVP persona', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'rsvp.me', section: 'rsvp' }, storage: { kind: 'runtime', key: 'wp_current_user', type: 'object' }, payload_key: 'rsvp.me', input: {}, notes: 'Not CPT meta — session user.' }),
  field({ id: 'event.rsvp.options', label: 'RSVP option avatars (will/maybe)', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'rsvp.options', section: 'rsvp' }, storage: { kind: 'table', key: 'apollo_event_rsvp', type: 'rows' }, payload_key: 'rsvp.options', input: { rest: 'participantes' }, notes: 'going / interested rows.' }),
  field({ id: 'event.lineup.djs', label: 'Line-up DJ cards', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'lineup.djs[]', section: 'lineup', ui: '.ev-lineup' }, storage: { kind: 'relation', key: '_event_dj_ids+_event_dj_slots', type: 'int[]+object[]', owner_cpt: 'event' }, payload_key: 'lineup.djs', input: { frontend: 'form-lineup', rest: 'dj_ids,dj_slots' }, notes: 'Resolved against CPT dj (name, photo, audio, badge, slot).' }),
  field({ id: 'event.dj_ids', label: 'DJ IDs', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'lineup.djs[].slug→id', section: 'lineup' }, storage: { kind: 'meta', key: '_event_dj_ids', type: 'int[]' }, payload_key: 'dj_ids', input: { frontend: '#ev-dj-ids', rest: 'dj_ids' } }),
  field({ id: 'event.dj_slots', label: 'DJ timetable slots', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'lineup.djs[].slot + timetable.rows', section: 'timetable' }, storage: { kind: 'meta', key: '_event_dj_slots', type: 'object[]' }, payload_key: 'dj_slots', input: { frontend: 'slots UI', rest: 'dj_slots' }, notes: '{dj_id,start,end,badge?}' }),
  field({ id: 'event.timetable', label: 'Timetable panel', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'timetable.rows[]', section: 'timetable' }, storage: { kind: 'derived', key: '_event_dj_slots', type: 'object[]' }, payload_key: 'timetable.rows', input: {}, empty_behavior: 'hide' }),
  field({ id: 'event.gallery', label: 'Event gallery', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'gallery[]', section: 'gallery' }, storage: { kind: 'meta', key: '_event_gallery', type: 'attachment_id[]' }, payload_key: 'gallery', input: { frontend: '#ev-gallery', rest: 'gallery' }, empty_behavior: 'hide' }),
  field({ id: 'event.about', label: 'About copy', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'about', section: 'about' }, storage: { kind: 'post', key: 'post_content', type: 'html' }, payload_key: 'about', input: { frontend: '#ev-about', rest: 'content' } }),
  field({ id: 'event.spotify', label: 'Audio / Spotify embed', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'spotify', section: 'audio' }, storage: { kind: 'meta', key: '_event_audio_url', type: 'string_url' }, payload_key: 'spotify', input: { frontend: '#ev-audio', rest: 'audio_url' }, empty_behavior: 'hide' }),
  field({ id: 'event.loc_id', label: 'Linked location', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'venue.*', section: 'venue' }, storage: { kind: 'meta', key: '_event_loc_id', type: 'int', owner_cpt: 'event' }, payload_key: 'loc_id', input: { frontend: '#ev-loc-id', rest: 'loc_id' }, notes: 'Resolves CPT local fields into venue.*' }),
  field({ id: 'event.venue.name', label: 'Venue name (resolved)', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'venue.name', section: 'venue' }, storage: { kind: 'relation', key: 'local.post_title|_local_name', type: 'string', owner_cpt: 'local' }, payload_key: 'venue.name', input: {} }),
  field({ id: 'event.venue.address', label: 'Venue address (resolved)', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'venue.addressLines', section: 'venue' }, storage: { kind: 'relation', key: '_local_address(+city/state)', type: 'string', owner_cpt: 'local' }, payload_key: 'venue.addressLines', input: {} }),
  field({ id: 'event.venue.loc', label: 'Venue lat/lng (resolved)', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'venue.loc', section: 'venue' }, storage: { kind: 'relation', key: '_local_lat+_local_lng', type: 'object', owner_cpt: 'local' }, payload_key: 'venue.loc', input: {} }),
  field({ id: 'event.venue.photos', label: 'Venue photos (resolved)', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'venue.photos[]', section: 'venue' }, storage: { kind: 'relation', key: '_local_image_1..N|_local_amenities gallery', type: 'url[]', owner_cpt: 'local' }, payload_key: 'venue.photos', input: {}, empty_behavior: 'hide' }),
  field({ id: 'event.access.tickets', label: 'Ticket buttons', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'access.tickets[]', section: 'access' }, storage: { kind: 'meta', key: '_event_access_buttons|_event_ticket_url|_event_earlybird_*', type: 'object[]' }, payload_key: 'access.tickets', input: { frontend: '#ev-access-buttons', rest: 'access_buttons' } }),
  field({ id: 'event.access.coupon', label: 'Coupon code', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'access.coupon', section: 'access' }, storage: { kind: 'meta', key: '_event_coupon_code', type: 'string' }, payload_key: 'access.coupon', input: { frontend: '#ev-coupon-code', rest: 'coupon_code' }, empty_behavior: 'hide' }),
  field({ id: 'event.access.listas', label: 'Lista entries', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'access.listas[]', section: 'access' }, storage: { kind: 'meta', key: '_event_access_buttons|_event_lista_*', type: 'object[]' }, payload_key: 'access.listas', input: { frontend: '#ev-access-buttons', rest: 'access_buttons' }, empty_behavior: 'hide' }),
  field({ id: 'event.access.listaCta', label: 'Lista CTA', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'access.listaCta', section: 'access' }, storage: { kind: 'meta', key: '_event_lista_cta_label|_event_list_url', type: 'object' }, payload_key: 'access.listaCta', input: { frontend: '#ev-lista-cta-label', rest: 'lista_cta_label,list_url' }, empty_behavior: 'hide' }),
  field({ id: 'event.ticket_status', label: 'Ticket availability status', cpt: 'event', mockup: { file: MOCK.event, payload: null, section: 'access' }, storage: { kind: 'meta', key: '_event_ticket_status', type: 'enum' }, payload_key: 'ticket_status', input: { frontend: '#ev-tickets', rest: 'ticket_status' }, required_for_render: false }),
  field({ id: 'event.status', label: 'Event status', cpt: 'event', mockup: { file: MOCK.event, payload: null, section: 'status' }, storage: { kind: 'meta', key: '_event_status', type: 'enum' }, payload_key: 'event_status', input: { frontend: '#ev-status', rest: 'event_status' }, required_for_render: false }),
  field({ id: 'event.privacy', label: 'Privacy', cpt: 'event', mockup: { file: MOCK.event, payload: null, section: 'status' }, storage: { kind: 'meta', key: '_event_privacy', type: 'enum' }, payload_key: 'privacy', input: { frontend: '#ev-privacy', rest: 'privacy' }, required_for_render: false }),
  field({ id: 'event.is_gone', label: 'Expired flag', cpt: 'event', mockup: { file: MOCK.event, payload: null, section: 'status' }, storage: { kind: 'meta', key: '_event_is_gone', type: 'bool' }, payload_key: null, input: {}, notes: 'Cron-derived 30min after end — never form input.' }),
  field({ id: 'event.bg_color', label: 'Background tint', cpt: 'event', mockup: { file: MOCK.event, payload: null, section: 'hero' }, storage: { kind: 'meta', key: '_event_bg_color', type: 'hex' }, payload_key: 'bg_color', input: { frontend: '#ev-bg-color', rest: 'bg_color' }, required_for_render: false, empty_behavior: 'fallback' }),
  field({ id: 'event.footer', label: 'Footer brand block', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'footer', section: 'footer' }, storage: { kind: 'derived', key: 'banner+title+date+venue', type: 'object' }, payload_key: 'footer', input: {} }),
  field({ id: 'event.chat', label: 'Warm-Up chat', cpt: 'event', mockup: { file: MOCK.eventData, payload: 'chat', section: 'chat' }, storage: { kind: 'runtime', key: 'apollo-chat channel', type: 'object' }, payload_key: 'chat', input: { rest: 'chat' }, required_for_render: false, empty_behavior: 'hide', notes: 'Not event meta — chat plugin channel keyed by event.' })
];

const djFields = [
  field({ id: 'dj.name', label: 'DJ display name', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.name', section: 'identity', ui: '#heroName,#evTopName,#footName', sample: 'Leo Janeiro' }, storage: { kind: 'meta', key: '_dj_name', type: 'string' }, payload_key: 'name', input: { frontend: 'hero', metabox: true, rest: 'name' }, notes: 'Fallback post_title.' }),
  field({ id: 'dj.bio_short', label: 'Hero short bio', cpt: 'dj', mockup: { file: MOCK.dj, payload: null, section: 'hero', ui: '.hero-bio', hardcoded_in_html: true }, storage: { kind: 'meta', key: '_dj_bio_short', type: 'string' }, payload_key: 'bioShort', input: { frontend: true, metabox: true, rest: true }, notes: 'GAP: still HTML-hardcoded in mockup; must join APOLLO_DJ.' }),
  field({ id: 'dj.statement', label: 'Statement word-fill', cpt: 'dj', mockup: { file: MOCK.dj, payload: null, section: 'statement', ui: '#stmtTxt', hardcoded_in_html: true }, storage: { kind: 'meta', key: '_dj_statement', type: 'string' }, payload_key: 'statement', input: { frontend: true, metabox: true }, notes: 'Fallback _dj_bio_short. GAP: hardcoded in mockup HTML.' }),
  field({ id: 'dj.hero_image', label: 'Hero image', cpt: 'dj', mockup: { file: MOCK.dj, payload: null, section: 'hero', ui: '#heroImg', hardcoded_in_html: true }, storage: { kind: 'meta', key: '_dj_banner|_dj_image', type: 'attachment_id|url' }, payload_key: 'heroImage', input: { frontend: true, metabox: true }, notes: 'GAP: hardcoded img src in mockup.' }),
  field({ id: 'dj.booking', label: 'Booking email', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.bookingEmail', section: 'actions', ui: '#heroBooking,#dockBooking' }, storage: { kind: 'meta', key: '_dj_booking', type: 'email' }, payload_key: 'bookingEmail', input: { frontend: true, metabox: true, rest: true } }),
  field({ id: 'dj.soundcloud', label: 'SoundCloud URL', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.soundcloud', section: 'platforms' }, storage: { kind: 'meta', key: '_dj_soundcloud', type: 'string_url' }, payload_key: 'soundcloud', input: { frontend: true, metabox: true, rest: true }, empty_behavior: 'hide' }),
  field({ id: 'dj.bandcamp', label: 'Bandcamp URL', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.bandcamp', section: 'platforms' }, storage: { kind: 'meta', key: '_dj_bandcamp', type: 'string_url' }, payload_key: 'bandcamp', input: { frontend: true, metabox: true }, empty_behavior: 'hide' }),
  field({ id: 'dj.spotify', label: 'Spotify URL', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.spotify', section: 'platforms' }, storage: { kind: 'meta', key: '_dj_spotify', type: 'string_url' }, payload_key: 'spotify', input: { frontend: true, metabox: true }, empty_behavior: 'hide' }),
  field({ id: 'dj.instagram', label: 'Instagram URL', cpt: 'dj', mockup: { file: MOCK.dj, payload: null, section: 'platforms', ui: 'footer .soc', hardcoded_in_html: true }, storage: { kind: 'meta', key: '_dj_instagram', type: 'string_url' }, payload_key: 'instagram', input: { frontend: true, metabox: true }, empty_behavior: 'hide', notes: 'In handoff map; not in APOLLO_DJ object yet.' }),
  field({ id: 'dj.media_kit_url', label: 'Promo kit Drive URL', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.mediaKitUrl', section: 'kit', ui: '#kitDl,#dockKit' }, storage: { kind: 'meta', key: '_dj_media_kit_url', type: 'string_url' }, payload_key: 'mediaKitUrl', input: { frontend: true, metabox: true }, empty_behavior: 'hide' }),
  field({ id: 'dj.about_video', label: 'About video', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.videoUrl', section: 'about', ui: '#aboutFig' }, storage: { kind: 'meta', key: '_dj_about_video', type: 'string_url' }, payload_key: 'videoUrl', input: { frontend: true, metabox: true }, empty_behavior: 'fallback', notes: 'Priority over about photo.' }),
  field({ id: 'dj.about_photo', label: 'About photo (≠ hero)', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.aboutPhoto', section: 'about', ui: '#aboutFig' }, storage: { kind: 'meta', key: '_dj_about_photo', type: 'attachment_id|url' }, payload_key: 'aboutPhoto', input: { frontend: true, metabox: true }, empty_behavior: 'hide' }),
  field({ id: 'dj.bio', label: 'About long bio', cpt: 'dj', mockup: { file: MOCK.dj, payload: null, section: 'about', ui: '.about-p', hardcoded_in_html: true }, storage: { kind: 'meta', key: '_dj_bio', type: 'html|string' }, payload_key: 'bio', input: { frontend: true, metabox: true }, notes: 'GAP: hardcoded in mockup HTML.' }),
  field({ id: 'dj.genres', label: 'Sound / genre marquee', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.genres[]', section: 'marquee', ui: '#mqInner' }, storage: { kind: 'taxonomy', key: 'sound', type: 'term[]' }, payload_key: 'genres', input: { frontend: true, metabox: true, rest: 'sounds' } }),
  field({ id: 'dj.tracks', label: 'Out now! tracks', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.tracks[]', section: 'out-now', ui: '#trkList,#outNowLb' }, storage: { kind: 'meta', key: '_dj_tracks', type: 'object[]' }, payload_key: 'tracks', input: { frontend: 'releases', metabox: true, rest: 'tracks' }, notes: '{title,url,year,duration}; display meta = year · RIO DE JANEIRO · duration; max 5 + Ver todos.' }),
  field({ id: 'dj.playedOn', label: 'Played-on event cards', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.playedOn[]', section: 'played-on', ui: '#poTrack' }, storage: { kind: 'derived', key: 'WP_Query event WHERE _event_dj_ids CONTAINS dj', type: 'object[]', owner_cpt: 'event' }, payload_key: 'playedOn', input: {}, notes: 'title,venue,date,withCount,cover from events+loc.' }),
  field({ id: 'dj.playedWith', label: 'Played-with co-DJs', cpt: 'dj', mockup: { file: MOCK.dj, payload: 'APOLLO_DJ.playedWith[]', section: 'played-with', ui: '#roster' }, storage: { kind: 'derived', key: 'co-DJs from shared lineups', type: 'object[]', owner_cpt: 'dj' }, payload_key: 'playedWith', input: {} }),
  field({ id: 'dj.stats', label: 'Numbers / stats strip', cpt: 'dj', mockup: { file: MOCK.dj, payload: null, section: 'numbers', ui: '[data-count]', hardcoded_in_html: true }, storage: { kind: 'derived', key: 'apollo_dj_compute_stats(past_events)', type: 'object' }, payload_key: 'stats', input: {}, notes: 'GAP: mockup hardcodes 86/6/9/14; live computes from past events.' }),
  field({ id: 'dj.gallery', label: 'Gallery', cpt: 'dj', mockup: { file: MOCK.dj, payload: null, section: 'gallery' }, storage: { kind: 'meta', key: '_dj_gallery', type: 'attachment_id[]' }, payload_key: 'gallery', input: { metabox: true }, required_for_render: false, empty_behavior: 'hide' }),
  field({ id: 'dj.projects', label: 'Original projects 1–3', cpt: 'dj', mockup: { file: MOCK.dj, payload: null, section: 'projects' }, storage: { kind: 'meta', key: '_dj_original_project_1..3', type: 'string' }, payload_key: 'projects', input: { frontend: true, metabox: true }, required_for_render: false, empty_behavior: 'hide' }),
  field({ id: 'dj.verified', label: 'Verified badge', cpt: 'dj', mockup: { file: MOCK.dj, payload: null, section: 'identity' }, storage: { kind: 'meta', key: '_dj_verified', type: 'bool' }, payload_key: 'verified', input: { metabox: true }, required_for_render: false, empty_behavior: 'hide' })
];

const locFields = [
  field({ id: 'local.name', label: 'Venue name', cpt: 'local', mockup: { file: MOCK.loc, payload: 'APOLLO_LOC.name', section: 'identity', ui: '#footName,#heroName', sample: 'D-Edge' }, storage: { kind: 'meta', key: '_local_name', type: 'string', mockup_key_legacy: '_ap_venue_name' }, payload_key: 'name', input: { metabox: true, rest: 'name' }, notes: 'Fallback post_title. Mock still labels _ap_*.' }),
  field({ id: 'local.hero_image', label: 'Hero image', cpt: 'local', mockup: { file: MOCK.loc, payload: null, section: 'hero', ui: '#heroImg', hardcoded_in_html: true }, storage: { kind: 'meta', key: '_local_image_1', type: 'attachment_id' }, payload_key: 'heroImage', input: { metabox: true }, notes: 'GAP: hardcoded in mockup; align to image_1 or dedicated banner.' }),
  field({ id: 'local.hero_kicker', label: 'Hero kicker', cpt: 'local', mockup: { file: MOCK.loc, payload: null, section: 'hero', ui: '.hero-kick', hardcoded_in_html: true }, storage: { kind: 'derived', key: '_local_city+_local_state+copy', type: 'string' }, payload_key: 'hero.kicker', input: {}, notes: 'GAP: hardcoded; productize as meta or derived.' }),
  field({ id: 'local.address', label: 'Address line', cpt: 'local', mockup: { file: MOCK.loc, payload: 'APOLLO_LOC.addr', section: 'map', ui: 'map/Uber/99' }, storage: { kind: 'meta', key: '_local_address', type: 'string', mockup_key_legacy: '_ap_address' }, payload_key: 'addr', input: { metabox: true, rest: 'address' } }),
  field({ id: 'local.city', label: 'City', cpt: 'local', mockup: { file: MOCK.loc, payload: '→ addr/hero', section: 'identity' }, storage: { kind: 'meta', key: '_local_city', type: 'string' }, payload_key: 'city', input: { metabox: true, rest: 'city' }, required_for_render: false }),
  field({ id: 'local.state', label: 'State', cpt: 'local', mockup: { file: MOCK.loc, payload: '→ addr', section: 'identity' }, storage: { kind: 'meta', key: '_local_state', type: 'string' }, payload_key: 'state', input: { metabox: true, rest: 'state' }, required_for_render: false }),
  field({ id: 'local.postal', label: 'Postal code', cpt: 'local', mockup: { file: MOCK.loc, payload: null, section: 'identity' }, storage: { kind: 'meta', key: '_local_postal', type: 'string' }, payload_key: 'postal', input: { metabox: true, rest: 'postal' }, required_for_render: false, empty_behavior: 'omit' }),
  field({ id: 'local.lat', label: 'Latitude', cpt: 'local', mockup: { file: MOCK.loc, payload: 'APOLLO_LOC.lat', section: 'map' }, storage: { kind: 'meta', key: '_local_lat', type: 'number', mockup_key_legacy: '_ap_geo.lat' }, payload_key: 'lat', input: { metabox: true, rest: 'lat' } }),
  field({ id: 'local.lng', label: 'Longitude', cpt: 'local', mockup: { file: MOCK.loc, payload: 'APOLLO_LOC.lng', section: 'map' }, storage: { kind: 'meta', key: '_local_lng', type: 'number', mockup_key_legacy: '_ap_geo.lng' }, payload_key: 'lng', input: { metabox: true, rest: 'lng' } }),
  field({ id: 'local.eventsHosted', label: 'Events hosted count', cpt: 'local', mockup: { file: MOCK.loc, payload: 'APOLLO_LOC.eventsHosted', section: 'facts', ui: '#factEvents', sample: 1180 }, storage: { kind: 'derived', key: 'COUNT(event WHERE _event_loc_id=ID)', type: 'int', owner_cpt: 'event' }, payload_key: 'eventsHosted', input: {}, notes: 'NEVER capacity/lotação. Auto count only.' }),
  field({ id: 'local.marquee', label: 'Marquee tags', cpt: 'local', mockup: { file: MOCK.loc, payload: 'APOLLO_LOC.marquee[]', section: 'marquee', ui: '#mqInner' }, storage: { kind: 'meta', key: '_local_amenities|custom marquee tags', type: 'string[]', mockup_key_legacy: '_ap_marquee_tags' }, payload_key: 'marquee', input: { metabox: true }, notes: 'Live may use amenities/hours; mock uses dedicated marquee repeater — decide SSOT.' }),
  field({ id: 'local.events', label: 'Agenda at this venue', cpt: 'local', mockup: { file: MOCK.loc, payload: 'APOLLO_LOC.events[]', section: 'agenda', ui: '#agTrack,#agFullList' }, storage: { kind: 'derived', key: 'WP_Query event WHERE _event_loc_id', type: 'object[]', owner_cpt: 'event' }, payload_key: 'events', input: {}, notes: 'title,day,month,time,tags,lineup,status,cover,url from event+tax.' }),
  field({ id: 'local.gallery', label: 'Venue gallery', cpt: 'local', mockup: { file: MOCK.loc, payload: 'APOLLO_LOC.gallery[]', section: 'gallery', ui: '#gal' }, storage: { kind: 'meta', key: '_local_image_1..5', type: 'attachment_id[]', mockup_key_legacy: '_ap_gallery' }, payload_key: 'gallery', input: { metabox: true }, notes: 'Mock {src,cap,bleed}; live image_1..5 — caption/bleed may need meta expansion.' }),
  field({ id: 'local.amenities', label: 'Amenities chips', cpt: 'local', mockup: { file: MOCK.loc, payload: 'APOLLO_LOC.amenities[]', section: 'amenities', ui: '#amen' }, storage: { kind: 'meta', key: '_local_amenities', type: 'object[]', mockup_key_legacy: '_ap_amenities' }, payload_key: 'amenities', input: { metabox: true }, empty_behavior: 'hide' }),
  field({ id: 'local.depoimentos', label: 'Testimonials', cpt: 'local', mockup: { file: MOCK.loc, payload: 'APOLLO_LOC.depoimentos[]', section: 'depoimentos', ui: '#depoTrack' }, storage: { kind: 'relation', key: '_local_testimonials|apollo-comment', type: 'object[]' }, payload_key: 'depoimentos', input: {}, empty_behavior: 'hide', notes: 'Mock treats as related posts; live may use _local_testimonials or comments.' }),
  field({ id: 'local.description', label: 'Description', cpt: 'local', mockup: { file: MOCK.loc, payload: null, section: 'about', hardcoded_in_html: true }, storage: { kind: 'meta', key: '_local_description', type: 'html' }, payload_key: 'description', input: { metabox: true, rest: true }, required_for_render: false }),
  field({ id: 'local.phone', label: 'Phone', cpt: 'local', mockup: { file: MOCK.loc, payload: null, section: 'contact' }, storage: { kind: 'meta', key: '_local_phone', type: 'string' }, payload_key: 'phone', input: { metabox: true, rest: 'phone' }, required_for_render: false, empty_behavior: 'hide' }),
  field({ id: 'local.capacity', label: 'Capacity (admin only)', cpt: 'local', mockup: { file: MOCK.loc, payload: null, section: 'admin' }, storage: { kind: 'meta', key: '_local_capacity', type: 'int' }, payload_key: 'capacity', input: { metabox: true }, required_for_render: false, empty_behavior: 'omit', notes: 'Explicitly NOT shown as public fact on mockup (eventsHosted ≠ capacity).' }),
  field({ id: 'local.socials', label: 'Website / IG / FB / WA', cpt: 'local', mockup: { file: MOCK.loc, payload: null, section: 'links' }, storage: { kind: 'meta', key: '_local_website|_local_instagram|_local_facebook|_local_whatsapp', type: 'string_url' }, payload_key: 'links', input: { metabox: true }, required_for_render: false, empty_behavior: 'hide' }),
  field({ id: 'local.hours', label: 'Opening hours', cpt: 'local', mockup: { file: MOCK.loc, payload: null, section: 'hero', ui: '.hero-meta', hardcoded_in_html: true }, storage: { kind: 'meta', key: '_local_hours', type: 'object|string' }, payload_key: 'hours', input: { metabox: true }, required_for_render: false })
];

const shellFields = [
  field({ id: 'shell.user', label: 'Logged-in user (aside/profile)', cpt: '_shell', mockup: { file: MOCK.layout, payload: 'APOLLO_SESSION.user', section: 'aside/profile', ui: '.urow,#panel-profile' }, storage: { kind: 'runtime', key: 'wp_current_user', type: 'object' }, payload_key: 'user', input: {} }),
  field({ id: 'shell.radar', label: 'Eventos Radar RSVP list', cpt: '_shell', mockup: { file: MOCK.layout, payload: 'APOLLO_RADAR[]', section: 'aside', ui: '#radarInn' }, storage: { kind: 'table', key: 'apollo_event_rsvp (quero-ir|vou)', type: 'object[]' }, payload_key: 'radar', input: { rest: 'rsvp' }, notes: 'Max 5 + Ver tudo → #/eventos/radar; resolves APOLLO_EVENTS.' }),
  field({ id: 'shell.gestor.counts', label: 'Gestor sidebar counts', cpt: '_shell', mockup: { file: MOCK.layout, payload: 'derived+APOLLO_GESTOR', section: 'aside', ui: '#col-mgr .cnt' }, storage: { kind: 'derived', key: 'events/anuncios/comunas/nucleos + APOLLO_GESTOR', type: 'object' }, payload_key: 'gestor', input: {} }),
  field({ id: 'shell.notifications', label: 'Activities panel feed', cpt: '_shell', mockup: { file: MOCK.layout, payload: 'panel-act feed', section: 'topbar', ui: '#panel-act' }, storage: { kind: 'runtime', key: 'apollo-notif', type: 'object[]' }, payload_key: 'notifications', input: {}, required_for_render: false })
];

const registry = {
  $schema: 'apollo/data-registry/v1',
  $id: 'plugins/_inventory/data-registry.json',
  $version: '1.0.0',
  $generated: '2026-07-22',
  $description: 'Deep technical audit of every DB-bound information spot shown on official mockups (event / dj / local singles + official layout shell). Canonical map: mockup payload → live storage → input surfaces.',
  $note_template: 'No external attachment was present at generation time. This file EMBEDS the mandatory field template under $field_template — every entry in cpts.*.fields MUST conform.',
  $field_template: {
    id: 'cpt.section.key — unique stable id',
    label: 'Human label',
    cpt: 'event | dj | local | _shell',
    mockup: {
      file: 'path under D:/dev/_dev web/',
      payload: 'JS object path or null',
      section: 'UI section id',
      ui: 'CSS selector hint or null',
      hardcoded_in_html: 'boolean — true if mock still hardcodes instead of payload',
      sample: 'optional sample value from mock'
    },
    storage: {
      kind: 'post | meta | taxonomy | attachment | relation | derived | runtime | table',
      key: 'canonical WP/live key',
      type: 'scalar/object type',
      owner_cpt: 'owning CPT when relation/derived',
      mockup_key_legacy: 'mock-only key if renamed (e.g. _ap_* → _local_*)'
    },
    payload_key: 'context/localize key',
    input: { frontend: 'selector|true|null', metabox: 'true|null', rest: 'REST field name|null' },
    required_for_render: 'boolean',
    empty_behavior: 'hide | fallback | omit | required',
    notes: 'free text'
  },
  sources: {
    mockups: [
      { id: 'event-single', path: MOCK.event, data: MOCK.eventData, payload: 'APOLLO_EVENT_SIMULATED' },
      { id: 'dj-single', path: MOCK.dj, payload: 'APOLLO_DJ' },
      { id: 'loc-single', path: MOCK.loc, payload: 'APOLLO_LOC' },
      { id: 'official-layout', path: MOCK.layout, data: MOCK.layoutData, payload: 'APOLLO_* shell' }
    ],
    live_plugins: {
      event: { plugin: 'apollo-events', cpt: 'event', constant: 'APOLLO_EVENT_CPT', meta_prefix: '_event_', context: 'apollo_event_* helpers / single parts' },
      dj: { plugin: 'apollo-djs', cpt: 'dj', constant: 'APOLLO_DJ_CPT', meta_prefix: '_dj_', context: 'apollo_get_dj_context($dj_id)' },
      local: { plugin: 'apollo-loc', cpt: 'local', constant: 'APOLLO_LOCAL_CPT', meta_prefix: '_local_', context: 'LocalsController + single-local.php' }
    }
  },
  cpts: {
    event: {
      slug: 'event',
      labels: { singular: 'Evento', plural: 'Eventos' },
      public_route: '/evento/{slug}',
      mockup_payload: 'APOLLO_EVENT_SIMULATED',
      field_count: eventFields.length,
      fields: eventFields
    },
    dj: {
      slug: 'dj',
      labels: { singular: 'DJ', plural: 'DJs' },
      public_route: '/dj/{slug}',
      mockup_payload: 'APOLLO_DJ',
      context_php: 'apollo_get_dj_context',
      field_count: djFields.length,
      fields: djFields
    },
    local: {
      slug: 'local',
      labels: { singular: 'Local', plural: 'Locais' },
      public_route: '/local/{slug}',
      mockup_payload: 'APOLLO_LOC',
      mockup_legacy_prefix: '_ap_',
      live_prefix: '_local_',
      field_count: locFields.length,
      fields: locFields
    }
  },
  surfaces: {
    shell: {
      id: 'official_layout',
      path: MOCK.layout,
      field_count: shellFields.length,
      fields: shellFields
    }
  },
  relations: [
    { from: 'event._event_loc_id', to: 'local.ID', used_by: ['event.venue.*', 'local.events'] },
    { from: 'event._event_dj_ids', to: 'dj.ID', used_by: ['event.lineup', 'event.timetable', 'dj.playedOn', 'dj.playedWith'] },
    { from: 'apollo_event_rsvp', to: 'event.ID + user.ID', used_by: ['event.rsvp', 'shell.radar'] }
  ],
  gaps: [
    { severity: 'high', cpt: 'dj', id: 'dj.bio_short', issue: 'Hero bio hardcoded in HTML — not in APOLLO_DJ payload' },
    { severity: 'high', cpt: 'dj', id: 'dj.statement', issue: 'Statement hardcoded — not in APOLLO_DJ payload' },
    { severity: 'high', cpt: 'dj', id: 'dj.hero_image', issue: 'Hero img src hardcoded — not in APOLLO_DJ payload' },
    { severity: 'high', cpt: 'dj', id: 'dj.bio', issue: 'About paragraph hardcoded — not in APOLLO_DJ payload' },
    { severity: 'medium', cpt: 'dj', id: 'dj.stats', issue: 'Stats data-count hardcoded; live uses apollo_dj_compute_stats' },
    { severity: 'high', cpt: 'local', id: 'local.hero_image', issue: 'Hero image hardcoded; mock _ap_gallery vs live _local_image_N' },
    { severity: 'medium', cpt: 'local', id: 'local.marquee', issue: 'Mock _ap_marquee_tags has no exact live twin — map or add meta' },
    { severity: 'medium', cpt: 'local', issue: 'Entire mock prefix _ap_* must be treated as legacy aliases of _local_*' },
    { severity: 'low', cpt: 'event', id: 'event.chat', issue: 'Warm-Up chat is runtime channel, not event meta' },
    { severity: 'low', cpt: 'event', id: 'event.hero.kicker', issue: 'Often static brand copy' }
  ],
  totals: {
    event_fields: eventFields.length,
    dj_fields: djFields.length,
    local_fields: locFields.length,
    shell_fields: shellFields.length,
    all_fields: eventFields.length + djFields.length + locFields.length + shellFields.length,
    gaps: 10
  }
};

fs.writeFileSync(OUT, JSON.stringify(registry, null, 2), 'utf8');
console.log('Wrote', OUT);
console.log('Totals', registry.totals);
