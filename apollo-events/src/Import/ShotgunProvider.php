<?php

/**
 * Shotgun URL importer — server-side fetch + HTML parse.
 *
 * @package Apollo\Event
 * @since   1.7.15
 */

declare(strict_types=1);

namespace Apollo\Event\Import;

use Apollo\Event\Import\Contracts\ImportProviderInterface;
use Apollo\Event\Import\Html\HtmlEventParser;

if (! defined('ABSPATH')) {
    exit;
}

final class ShotgunProvider implements ImportProviderInterface
{
    private const TIMEOUT = 20;

    public static function provider(): string
    {
        return 'shotgun';
    }

    public static function handles(string $url): bool
    {
        $host = (string) wp_parse_url($url, PHP_URL_HOST);
        $host = preg_replace('~^www\.~i', '', $host) ?? $host;

        return (bool) preg_match('~(^|\.)shotgun\.live$~i', $host);
    }

    /**
     * @return array<string,mixed>|\WP_Error
     */
    public static function fetch(string $url, string $coupon = ''): array|\WP_Error
    {
        $slug = self::event_slug($url);
        if ('' === $slug) {
            return new \WP_Error(
                'apollo_sg_no_slug',
                __('Não encontrei o slug do evento nessa URL do Shotgun. Esperado /events/nome-do-evento.', 'apollo-events')
            );
        }

        $html = self::request_html($url);
        if (is_wp_error($html)) {
            return $html;
        }

        return self::normalise($html, $slug, $url, $coupon);
    }

    public static function event_slug(string $url): string
    {
        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        if (preg_match('~/events/([^/?#]+)~i', $path, $m)) {
            return sanitize_title(rawurldecode($m[1]));
        }

        return '';
    }

    /**
     * @return string|\WP_Error
     */
    private static function request_html(string $url)
    {
        $res = wp_remote_get(
            $url,
            array(
                'timeout'     => self::TIMEOUT,
                'redirection' => 5,
                'headers'     => array(
                    'Accept'          => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'pt-BR,pt;q=0.9,en;q=0.8',
                    'User-Agent'      => 'apollo.rio.br event importer (+https://apollo.rio.br)',
                ),
            )
        );

        if (is_wp_error($res)) {
            return $res;
        }

        $code = (int) wp_remote_retrieve_response_code($res);
        $body = (string) wp_remote_retrieve_body($res);

        if (200 !== $code || strlen($body) < 200) {
            return new \WP_Error(
                'apollo_sg_http',
                sprintf(
                    /* translators: 1: HTTP status */
                    __('Shotgun respondeu HTTP %1$d ou HTML vazio.', 'apollo-events'),
                    $code
                )
            );
        }

        return $body;
    }

    /**
     * @return array<string,mixed>
     */
    private static function normalise(string $html, string $slug, string $source_url, string $coupon): array
    {
        $parsed = HtmlEventParser::parse($html, $source_url);

        $title = (string) ($parsed['title'] ?? '');
        if ('' === $title) {
            $title = ucwords(str_replace('-', ' ', $slug));
        }

        $start_date = (string) ($parsed['start_date'] ?? '');
        $start_time = (string) ($parsed['start_time'] ?? '');
        $end_date   = (string) ($parsed['end_date'] ?? '');
        $end_time   = (string) ($parsed['end_time'] ?? '');

        if ('' === $start_date) {
            $start_date = PtDate::date($title);
            if ('' === $start_date) {
                $start_date = PtDate::date((string) ($parsed['about'] ?? ''));
            }
        }
        if ('' === $start_time) {
            $range = PtDate::time_range($title . ' ' . (string) ($parsed['about'] ?? ''));
            $start_time = $range[0] !== '' ? $range[0] : '23:00';
            if ('' === $end_time && $range[1] !== '') {
                $end_time = $range[1];
            }
        }
        if ('' === $end_time) {
            $end_time = '07:00';
        }
        if ('' === $end_date && '' !== $start_date) {
            $end_date = PtDate::end_date($start_date, $start_time, $end_time);
            if ('' === $end_date) {
                $end_date = $start_date;
            }
        }

        $loc_name = (string) ($parsed['loc_name'] ?? '');
        $cover    = (string) ($parsed['cover'] ?? '');

        $cover_raw = array();
        foreach ((array) ($parsed['cover_sources'] ?? array()) as $src) {
            if (! is_array($src) || empty($src['url'])) {
                continue;
            }
            $cover_raw[] = array(
                'url'      => (string) $src['url'],
                'source'   => (string) ($src['source'] ?? 'html'),
                'priority' => 5,
            );
        }
        if ('' !== $cover) {
            array_unshift(
                $cover_raw,
                array('url' => $cover, 'source' => 'provider_primary', 'priority' => 1)
            );
        }

        $ticket_url = esc_url_raw(trim((string) ($parsed['ticket_url'] ?? $source_url)));

        return array(
            'provider'            => self::provider(),
            'provider_id'         => $slug,
            'source_url'          => $ticket_url,
            'coupon'              => strtoupper(sanitize_text_field($coupon)),

            'title'               => $title,
            'presenter'           => '',
            'raw_name'            => $title,

            'cover'               => $cover,
            'cover_candidates_raw' => $cover_raw,
            'cover_sources'       => (array) ($parsed['cover_sources'] ?? array()),
            'about'               => wp_kses_post((string) ($parsed['about'] ?? '')),
            'video_url'           => (string) ($parsed['video_url'] ?? ''),

            'start_date'          => $start_date,
            'start_time'          => $start_time,
            'end_date'            => $end_date,
            'end_time'            => $end_time,
            'display_date'        => '',

            'ticket_url'          => $ticket_url,
            'ticket_price'        => __('Ingressos do Evento', 'apollo-events'),
            'ticket_status'       => 'available',
            'cancelled'           => false,

            'dj_ids'              => array(),
            'dj_slots'            => array(),

            'loc'                 => array(
                'name'     => $loc_name,
                'slug'     => BlueTicketProvider::loc_slug($loc_name),
                'address'  => '',
                'city'     => '',
                'state'    => '',
                'lat'      => '',
                'lng'      => '',
                'capacity' => 0,
            ),
        );
    }
}
