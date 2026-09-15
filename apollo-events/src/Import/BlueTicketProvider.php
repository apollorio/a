<?php

/**
 * BlueTicket URL importer — provider.
 *
 * WHY THIS EXISTS / WHY THE OLD APPROACH RETURNED NO DATA
 * ======================================================
 * The importer used to read the public event PAGE and pull values out of the
 * rendered DOM (.event-name, .v-image__image--cover's background-image, …).
 * That can never work, for two independent reasons:
 *
 *  1. blueticket.com.br is a client-rendered Nuxt SPA. Verified 2026-08-05:
 *     GET https://www.blueticket.com.br/evento/41379?c=apollo returns 3,086
 *     bytes containing a loading spinner, four <script src> tags and
 *     window.__NUXT__={config:{…}} — no event name, no image, no date, no
 *     og: tags, no JSON-LD. The markup the browser shows is built by JS
 *     AFTER load, so a server-side fetch of that URL is structurally
 *     incapable of seeing it. Scraping it harder cannot fix that.
 *
 *  2. Fetching it from the BROWSER instead is blocked by CORS: blueticket
 *     sends no Access-Control-Allow-Origin for apollo.rio.br, so the response
 *     is unreadable to our JS even when the request succeeds.
 *
 * So the DOM was never the data source. The real one is the API the SPA
 * itself calls, found in its bundle (_nuxt/28970b0.js):
 *
 *     GET /events/{id}/detail?coupon={coupon}&password={password}
 *
 * against the production CDN host. That returns clean structured JSON —
 * id, info{name,slug,description}, media[], dates{}, venue{}, sale{} — which
 * is strictly better than any DOM scrape: no markup coupling, so a BlueTicket
 * redesign cannot break us, and every field is already typed.
 *
 * This runs SERVER-SIDE (wp_remote_get, PHP → API). Server-to-server requests
 * are not subject to the same-origin policy at all, so CORS is structurally
 * out of the picture rather than worked around.
 *
 * @package Apollo\Event
 * @since   1.7.0
 */

declare(strict_types=1);

namespace Apollo\Event\Import;

use Apollo\Event\Import\Contracts\ImportProviderInterface;

if (! defined('ABSPATH')) {
    exit;
}

final class BlueTicketProvider implements ImportProviderInterface
{
    /** Host that answers /events/{id}/detail in production, then the fallback. */
    private const API_HOSTS = array(
        'https://api2-cdn.blueticket.com.br',
        'https://api2.blueticket.com.br',
    );

    private const TIMEOUT = 15;

    public static function provider(): string
    {
        return 'blueticket';
    }

    /**
     * Does this provider own the given URL?
     */
    public static function handles(string $url): bool
    {
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        return (bool) preg_match('~(^|\.)blueticket\.com\.br$~i', $host);
    }

    /**
     * Pull the BlueTicket numeric event id out of any of its URL shapes:
     *   /evento/41379            /evento/41379?c=apollo
     *   /evento/41379/qualquer   /event/41379
     *
     * @return int 0 when the URL carries no id.
     */
    public static function event_id(string $url): int
    {
        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        if (preg_match('~/(?:evento|event)/(\d+)~i', $path, $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    /**
     * Coupon carried by the URL (?c=apollo), falling back to the house coupon.
     *
     * The coupon is NOT cosmetic: it is passed straight through to the detail
     * endpoint, so prices and campaign blocks come back already discounted,
     * and it is preserved on the ticket link we store so the visitor lands on
     * the discounted checkout.
     */
    public static function coupon(string $url, string $default = 'apollo'): string
    {
        $qs = (string) wp_parse_url($url, PHP_URL_QUERY);
        if ('' !== $qs) {
            parse_str($qs, $q);
            foreach (array('c', 'coupon', 'cupom') as $key) {
                if (! empty($q[$key])) {
                    return sanitize_text_field((string) $q[$key]);
                }
            }
        }
        return $default;
    }

    /**
     * Fetch + normalise one BlueTicket event.
     *
     * @param string $url    Public event URL.
     * @param string $coupon Coupon to apply (defaults to the URL's, else 'apollo').
     * @return array<string,mixed>|\WP_Error Normalised payload.
     */
    public static function fetch(string $url, string $coupon = ''): array|\WP_Error
    {
        $id = self::event_id($url);
        if (! $id) {
            return new \WP_Error(
                'apollo_bt_no_id',
                __('Não encontrei o ID do evento nessa URL do BlueTicket. Esperado algo como /evento/41379.', 'apollo-events')
            );
        }

        $coupon = '' !== $coupon ? sanitize_text_field($coupon) : self::coupon($url);
        $raw    = self::request_detail($id, $coupon);
        if (is_wp_error($raw)) {
            return $raw;
        }

        return self::normalise($raw, $id, $coupon, $url);
    }

    /**
     * Ticket link SSOT: the URL the operator submitted for import.
     *
     * Rebuilds only when the submitted string is empty/unusable. Keeps an
     * existing ?c= coupon; otherwise appends the resolved coupon so checkout
     * still lands discounted.
     */
    public static function ticket_url_from_source(string $source_url, int $id, string $coupon): string
    {
        $source_url = esc_url_raw(trim($source_url));
        $base       = '';

        if ('' !== $source_url && self::event_id($source_url) === $id) {
            $base = $source_url;
        }
        if ('' === $base) {
            $base = 'https://www.blueticket.com.br/evento/' . $id;
        }

        /* Submitted URL already carries ?c= / coupon / cupom — keep it. */
        if ('' !== self::coupon($base, '')) {
            return $base;
        }

        $coupon = sanitize_text_field($coupon);
        if ('' === $coupon) {
            return $base;
        }

        return (string) add_query_arg(array('c' => $coupon), $base);
    }

    /**
     * GET /events/{id}/detail with host failover.
     *
     * @return array<string,mixed>|\WP_Error
     */
    private static function request_detail(int $id, string $coupon): array|\WP_Error
    {
        $last = null;

        foreach (self::API_HOSTS as $host) {
            $endpoint = $host . '/events/' . $id . '/detail';
            $endpoint = add_query_arg(array('coupon' => rawurlencode($coupon)), $endpoint);

            $res = wp_remote_get(
                $endpoint,
                array(
                    'timeout'     => self::TIMEOUT,
                    'redirection' => 3,
                    'headers'     => array(
                        'Accept'     => 'application/json',
                        // Some edges 403 an empty UA. Identify honestly.
                        'User-Agent' => 'apollo.rio.br event importer (+https://apollo.rio.br)',
                    ),
                )
            );

            if (is_wp_error($res)) {
                $last = $res;
                continue;
            }

            $code = (int) wp_remote_retrieve_response_code($res);
            $body = (string) wp_remote_retrieve_body($res);

            if (200 !== $code) {
                $last = new \WP_Error(
                    'apollo_bt_http',
                    sprintf(
                        /* translators: 1: HTTP status, 2: host */
                        __('BlueTicket respondeu HTTP %1$d em %2$s.', 'apollo-events'),
                        $code,
                        $host
                    )
                );
                continue;
            }

            $json = json_decode($body, true);
            if (! is_array($json)) {
                $last = new \WP_Error(
                    'apollo_bt_json',
                    __('BlueTicket respondeu algo que não é JSON (possível bloqueio ou página de erro).', 'apollo-events')
                );
                continue;
            }

            /* The endpoint answers either the object itself or {data:{…}}. */
            $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : $json;
            if (empty($data['info']['name'])) {
                $last = new \WP_Error(
                    'apollo_bt_shape',
                    __('Resposta do BlueTicket sem info.name — formato mudou ou evento indisponível.', 'apollo-events')
                );
                continue;
            }

            return $data;
        }

        return $last instanceof \WP_Error
            ? $last
            : new \WP_Error('apollo_bt_unreachable', __('Não consegui falar com a API do BlueTicket.', 'apollo-events'));
    }

    /**
     * Map BlueTicket's shape onto Apollo's event contract.
     *
     * @param array<string,mixed> $d          BlueTicket detail payload.
     * @param int                 $id         BlueTicket event id.
     * @param string              $coupon     Resolved coupon code.
     * @param string              $source_url URL the operator submitted.
     * @return array<string,mixed>
     */
    private static function normalise(array $d, int $id, string $coupon, string $source_url = ''): array
    {
        $info  = isset($d['info']) && is_array($d['info']) ? $d['info'] : array();
        $venue = isset($d['venue']) && is_array($d['venue']) ? $d['venue'] : array();
        $dates = isset($d['dates']) && is_array($d['dates']) ? $d['dates'] : array();
        $sale  = isset($d['sale']) && is_array($d['sale']) ? $d['sale'] : array();

        $raw_name = (string) ($info['name'] ?? '');
        $split    = self::split_presenter($raw_name);

        /* Cover: media[] entry with type 1. Falls back to the first entry that
           looks like an image, so a type-code change does not cost us the art.
           Video URLs (YouTube / file) land in video_url when present. */
        $cover     = '';
        $video_url = '';
        $cover_raw = array();
        foreach ((array) ($d['media'] ?? array()) as $m) {
            if (! is_array($m) || empty($m['url'])) {
                continue;
            }
            $url = (string) $m['url'];
            if ('' === $video_url && preg_match('~(youtube\.com|youtu\.be|vimeo\.com|\.(?:mp4|webm|mov)(?:\?|$))~i', $url)) {
                $video_url = $url;
                continue;
            }
            $is_type_cover = (1 === (int) ($m['type'] ?? 0));
            $looks_image   = (bool) preg_match('~\.(jpe?g|png|webp|avif)(\?|$)~i', $url);
            if ($is_type_cover || $looks_image) {
                $cover_raw[] = array(
                    'url'      => $url,
                    'source'   => $is_type_cover ? 'blueticket_media_type1' : 'blueticket_media_image',
                    'priority' => $is_type_cover ? 1 : 4,
                );
                if ('' === $cover && ($is_type_cover || $looks_image)) {
                    $cover = $url;
                }
            }
        }

        /* dateTs is a unix timestamp in the venue's local time. Render it in
           the site's timezone so the stored date matches what a carioca sees,
           not UTC. */
        $start_date = '';
        $start_time = '';
        if (! empty($dates['dateTs'])) {
            $ts         = (int) $dates['dateTs'];
            $start_date = wp_date('Y-m-d', $ts);
            $start_time = wp_date('H:i', $ts);
        }
        /* displayTime ("Abertura: 23:00") is the door time and is more precise
           than the timestamp when the two disagree. */
        if (! empty($dates['displayTime']) && preg_match('~(\d{1,2}):(\d{2})~', (string) $dates['displayTime'], $m)) {
            $start_time = sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
        }

        /*
         * End schedule: BlueTicket rarely ships an explicit end. Apollo parties
         * are overnight by default — door 23:00 → end 07:00 next calendar day.
         * If the API ever exposes endTs / displayEndTime, prefer those.
         */
        $end_time = '07:00';
        if (! empty($dates['endTs'])) {
            $end_time = wp_date('H:i', (int) $dates['endTs']);
        } elseif (! empty($dates['displayEndTime']) && preg_match('~(\d{1,2}):(\d{2})~', (string) $dates['displayEndTime'], $em)) {
            $end_time = sprintf('%02d:%02d', (int) $em[1], (int) $em[2]);
        }
        $end_date = '';
        if ('' !== $start_date && '' !== $start_time) {
            $end_date = PtDate::end_date($start_date, $start_time, $end_time);
            if ('' === $end_date) {
                $end_date = $start_date;
            }
        }

        /* Submitted page URL is the ticket CTA href (coupon kept / appended). */
        $ticket_url = self::ticket_url_from_source($source_url, $id, $coupon);

        $loc_name = (string) ($venue['name'] ?? $split['presenter']);

        return array(
            'provider'      => self::provider(),
            'provider_id'   => (string) $id,
            'source_url'    => $ticket_url,
            'coupon'        => strtoupper(sanitize_text_field($coupon)),

            'title'         => $split['title'],
            'presenter'     => $split['presenter'],
            'raw_name'      => $raw_name,

            'cover'         => $cover,
            'cover_candidates_raw' => $cover_raw,
            'about'         => wp_kses_post((string) ($info['description'] ?? '')),
            'video_url'     => $video_url,

            'start_date'    => $start_date,
            'start_time'    => $start_time,
            'end_date'      => $end_date,
            'end_time'      => $end_time,
            'display_date'  => (string) ($dates['displayDatetime'] ?? ''),

            'ticket_url'    => $ticket_url,
            /* Button title on /evento/… — not a currency amount. */
            'ticket_price'  => __('Ingressos do Evento', 'apollo-events'),
            'ticket_status' => ! empty($sale['onSale']) ? 'available' : 'sold_out',
            'cancelled'     => ! empty($sale['isCancelled']),

            'dj_ids'        => array(),
            'dj_slots'      => array(),

            'loc' => array(
                'name'     => $loc_name,
                'slug'     => self::loc_slug($loc_name),
                'address'  => (string) ($venue['address'] ?? ''),
                'city'     => (string) ($venue['city'] ?? ''),
                'state'    => (string) ($venue['state'] ?? ''),
                'lat'      => isset($venue['latitude']) ? (string) $venue['latitude'] : '',
                'lng'      => isset($venue['longitude']) ? (string) $venue['longitude'] : '',
                'capacity' => (int) ($venue['maxCapacity'] ?? 0),
            ),
        );
    }

    /**
     * Split "<PRESENTER> apresenta <TITLE>" into its two halves.
     *
     * BlueTicket names carry the house in front of the actual party:
     *   "D-EDGE RIO apresenta INNERSOUNDS"  →  presenter "D-EDGE RIO", title "INNERSOUNDS"
     *
     * The house belongs on the loc, not in the event title — an Apollo event
     * titled "D-EDGE RIO apresenta INNERSOUNDS" would repeat the venue on every
     * card that already shows it. Also handles the common variants so this is
     * not a one-venue hack: apresenta / apresentam / presents / pres. / feat.
     *
     * Falls back to the whole string as the title when no separator is present,
     * which is the safe direction — we never lose the name.
     *
     * @return array{presenter:string,title:string}
     */
    public static function split_presenter(string $name): array
    {
        $name = trim(preg_replace('~\s+~u', ' ', $name) ?? $name);

        $separators = '(?:apresenta(?:m)?|apresentando|presents?|pres\.|feat\.)';
        if (preg_match('~^(.{2,80}?)\s+' . $separators . '\s+(.+)$~iu', $name, $m)) {
            return array(
                'presenter' => trim($m[1]),
                'title'     => trim($m[2]),
            );
        }

        return array('presenter' => '', 'title' => $name);
    }

    /**
     * Compact loc slug: "D-Edge Rio" → "dedge", "Fundição Progresso" → "fundicaoprogresso".
     *
     * Deliberately strips the separators sanitize_title() would keep, because
     * the house slug in Apollo is the compact form ("dedge", "baiuca"), not the
     * hyphenated one. City suffixes are dropped so "D-Edge Rio" and "D-Edge SP"
     * do not become two different locs for the same brand.
     */
    public static function loc_slug(string $venue_name): string
    {
        $s = remove_accents($venue_name);
        $s = preg_replace('~\b(rio|rj|sp|sao paulo|são paulo|bh|mg)\b~iu', '', $s) ?? $s;
        $s = strtolower((string) preg_replace('~[^A-Za-z0-9]+~', '', $s));
        return $s;
    }
}
