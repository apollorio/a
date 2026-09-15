<?php

/**
 * HTML / JSON-LD / OpenGraph event field extraction (Shotgun + fallbacks).
 *
 * @package Apollo\Event
 * @since   1.7.15
 */

declare(strict_types=1);

namespace Apollo\Event\Import\Html;

if (! defined('ABSPATH')) {
    exit;
}

final class HtmlEventParser
{
    /**
     * @return array<string,mixed>
     */
    public static function parse(string $html, string $source_url = ''): array
    {
        $out = array(
            'title'       => '',
            'cover'       => '',
            'about'       => '',
            'video_url'   => '',
            'start_date'  => '',
            'start_time'  => '',
            'end_date'    => '',
            'end_time'    => '',
            'loc_name'    => '',
            'performers'  => array(),
            'ticket_url'  => $source_url,
            'cover_sources' => array(),
        );

        if ('' === trim($html)) {
            return $out;
        }

        $ld = self::parse_json_ld_event($html);
        if ($ld) {
            $out['title'] = self::clean_text((string) ($ld['name'] ?? ''));
            $out['about'] = self::clean_text((string) ($ld['description'] ?? ''));
            $img          = self::first_http_url($ld['image'] ?? '');
            if ('' !== $img) {
                $out['cover'] = $img;
                $out['cover_sources'][] = array('source' => 'json_ld', 'url' => $img);
            }
            $loc = $ld['location'] ?? null;
            if (is_array($loc)) {
                $out['loc_name'] = self::clean_text((string) ($loc['name'] ?? ''));
            } elseif (is_string($loc)) {
                $out['loc_name'] = self::clean_text($loc);
            }
            $start = (string) ($ld['startDate'] ?? '');
            if ('' !== $start) {
                $parsed = self::iso_to_local($start);
                $out['start_date'] = $parsed['date'];
                $out['start_time'] = $parsed['time'];
            }
            $end = (string) ($ld['endDate'] ?? '');
            if ('' !== $end) {
                $parsed = self::iso_to_local($end);
                $out['end_date'] = $parsed['date'];
                $out['end_time'] = $parsed['time'];
            }
            $perf = $ld['performer'] ?? null;
            if (is_array($perf)) {
                foreach ($perf as $p) {
                    if (is_string($p) && '' !== trim($p)) {
                        $out['performers'][] = trim($p);
                    } elseif (is_array($p) && ! empty($p['name'])) {
                        $out['performers'][] = (string) $p['name'];
                    }
                }
            } elseif (is_string($perf) && '' !== trim($perf)) {
                $out['performers'][] = trim($perf);
            }
        }

        $next = self::parse_next_data($html);
        if ($next) {
            if ('' === $out['title'] && ! empty($next['title'])) {
                $out['title'] = (string) $next['title'];
            }
            if ('' === $out['cover'] && ! empty($next['cover'])) {
                $out['cover'] = (string) $next['cover'];
                $out['cover_sources'][] = array('source' => 'next_data', 'url' => $out['cover']);
            }
            if ('' === $out['about'] && ! empty($next['about'])) {
                $out['about'] = (string) $next['about'];
            }
            if ('' === $out['loc_name'] && ! empty($next['loc_name'])) {
                $out['loc_name'] = (string) $next['loc_name'];
            }
            if ('' === $out['start_date'] && ! empty($next['start_date'])) {
                $out['start_date'] = (string) $next['start_date'];
                $out['start_time'] = (string) ($next['start_time'] ?? '23:00');
            }
            if ('' === $out['end_date'] && ! empty($next['end_date'])) {
                $out['end_date'] = (string) $next['end_date'];
                $out['end_time'] = (string) ($next['end_time'] ?? '07:00');
            }
        }

        foreach (self::meta_tags($html) as $meta) {
            $prop = strtolower((string) ($meta['property'] ?? $meta['name'] ?? ''));
            $val  = trim((string) ($meta['content'] ?? ''));
            if ('' === $val) {
                continue;
            }
            if ('og:title' === $prop && '' === $out['title']) {
                $out['title'] = self::clean_text($val);
            }
            if (('og:image' === $prop || 'twitter:image' === $prop) && '' === $out['cover']) {
                $out['cover'] = esc_url_raw($val);
                $out['cover_sources'][] = array('source' => $prop, 'url' => $out['cover']);
            }
            if ('og:description' === $prop && '' === $out['about']) {
                $out['about'] = self::clean_text($val);
            }
            if (('og:video' === $prop || 'og:video:url' === $prop) && '' === $out['video_url']) {
                $out['video_url'] = esc_url_raw($val);
            }
            if ('og:url' === $prop && '' === $out['ticket_url']) {
                $out['ticket_url'] = esc_url_raw($val);
            }
        }

        if ('' === $out['cover']) {
            if (preg_match('~<video[^>]+poster=["\']([^"\']+)~i', $html, $m)) {
                $out['cover'] = esc_url_raw($m[1]);
                $out['cover_sources'][] = array('source' => 'video_poster', 'url' => $out['cover']);
            }
        }

        return $out;
    }

    /**
     * @return array<string,mixed>|null
     */
    private static function parse_json_ld_event(string $html): ?array
    {
        if (! preg_match_all('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is', $html, $blocks)) {
            return null;
        }

        $events = array();
        foreach ($blocks[1] as $raw) {
            $json = json_decode(html_entity_decode(trim($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            if (! is_array($json)) {
                continue;
            }
            self::collect_ld_events($json, $events);
        }

        if (! $events) {
            return null;
        }

        foreach ($events as $ev) {
            $types = self::ld_type_list($ev['@type'] ?? '');
            foreach ($types as $t) {
                $leaf = str_contains($t, '/') ? (string) substr(strrchr($t, '/'), 1) : $t;
                if ('MusicEvent' === $leaf) {
                    return $ev;
                }
            }
        }

        return $events[0];
    }

    /**
     * @param array<int,array<string,mixed>> $out
     */
    private static function collect_ld_events(mixed $node, array &$out): void
    {
        if (! is_array($node)) {
            return;
        }
        if (isset($node[0]) && array_is_list($node)) {
            foreach ($node as $child) {
                self::collect_ld_events($child, $out);
            }
            return;
        }
        $types = self::ld_type_list($node['@type'] ?? '');
        foreach ($types as $t) {
            $leaf = str_contains($t, '/') ? (string) substr(strrchr($t, '/'), 1) : $t;
            if ('Event' === $leaf || 'MusicEvent' === $leaf) {
                $out[] = $node;
                break;
            }
        }
        if (isset($node['@graph']) && is_array($node['@graph'])) {
            self::collect_ld_events($node['@graph'], $out);
        }
    }

    /**
     * @return string[]
     */
    private static function ld_type_list(mixed $t): array
    {
        if (! $t) {
            return array();
        }

        return array_map('strval', is_array($t) ? $t : array($t));
    }

    /**
     * @return array<string,mixed>
     */
    private static function parse_next_data(string $html): array
    {
        $empty = array();
        if (! preg_match('~<script[^>]+id=["\']__NEXT_DATA__["\'][^>]*>(.*?)</script>~is', $html, $m)) {
            return $empty;
        }
        $json = json_decode(trim($m[1]), true);
        if (! is_array($json)) {
            return $empty;
        }

        $found = array();
        self::walk_next($json, $found);

        return $found;
    }

    /**
     * @param array<string,mixed> $found
     */
    private static function walk_next(mixed $node, array &$found, int $depth = 0): void
    {
        if ($depth > 12 || ! is_array($node)) {
            return;
        }

        $keys = array('title', 'name', 'cover', 'imageUrl', 'image', 'description', 'venue', 'venueName', 'startDate', 'endDate', 'startTime', 'endTime');
        foreach ($keys as $k) {
            if (! isset($node[$k]) || is_array($node[$k])) {
                continue;
            }
            $v = $node[$k];
            if ('title' === $k || 'name' === $k) {
                if ('' === ($found['title'] ?? '') && is_string($v)) {
                    $found['title'] = self::clean_text($v);
                }
            }
            if (in_array($k, array('cover', 'imageUrl', 'image'), true) && '' === ($found['cover'] ?? '')) {
                $url = self::first_http_url($v);
                if ('' !== $url) {
                    $found['cover'] = $url;
                }
            }
            if ('description' === $k && '' === ($found['about'] ?? '') && is_string($v)) {
                $found['about'] = self::clean_text($v);
            }
            if (in_array($k, array('venue', 'venueName'), true) && '' === ($found['loc_name'] ?? '') && is_string($v)) {
                $found['loc_name'] = self::clean_text($v);
            }
            if ('startDate' === $k && '' === ($found['start_date'] ?? '')) {
                $p = self::iso_to_local((string) $v);
                if ('' !== $p['date']) {
                    $found['start_date'] = $p['date'];
                    $found['start_time'] = $p['time'];
                }
            }
            if ('endDate' === $k && '' === ($found['end_date'] ?? '')) {
                $p = self::iso_to_local((string) $v);
                if ('' !== $p['date']) {
                    $found['end_date'] = $p['date'];
                    $found['end_time'] = $p['time'];
                }
            }
        }

        foreach ($node as $child) {
            if (is_array($child)) {
                self::walk_next($child, $found, $depth + 1);
            }
        }
    }

    /**
     * @return array<int,array{property?:string,name?:string,content:string}>
     */
    private static function meta_tags(string $html): array
    {
        $tags = array();
        if (! preg_match_all('~<meta\s+([^>]+)>~i', $html, $matches)) {
            return $tags;
        }
        foreach ($matches[1] as $attrs) {
            $prop    = '';
            $name    = '';
            $content = '';
            if (preg_match('~\bproperty=["\']([^"\']+)~i', $attrs, $m)) {
                $prop = $m[1];
            }
            if (preg_match('~\bname=["\']([^"\']+)~i', $attrs, $m)) {
                $name = $m[1];
            }
            if (preg_match('~\bcontent=["\']([^"\']+)~i', $attrs, $m)) {
                $content = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            if ('' !== $content) {
                $tags[] = array(
                    'property' => $prop,
                    'name'     => $name,
                    'content'  => $content,
                );
            }
        }

        return $tags;
    }

    /**
     * @return array{date:string,time:string}
     */
    private static function iso_to_local(string $iso): array
    {
        $empty = array('date' => '', 'time' => '');
        if ('' === trim($iso)) {
            return $empty;
        }
        try {
            $dt = new \DateTimeImmutable($iso);
            $dt = $dt->setTimezone(wp_timezone());

            return array(
                'date' => $dt->format('Y-m-d'),
                'time' => $dt->format('H:i'),
            );
        } catch (\Exception $e) {
            return $empty;
        }
    }

    private static function first_http_url(mixed $val): string
    {
        if (is_string($val) && preg_match('~^https?://~i', $val)) {
            return esc_url_raw($val);
        }
        if (is_array($val)) {
            foreach ($val as $item) {
                $u = self::first_http_url($item);
                if ('' !== $u) {
                    return $u;
                }
            }
        }
        if (is_array($val) && isset($val['url'])) {
            return self::first_http_url($val['url']);
        }

        return '';
    }

    private static function clean_text(string $text): string
    {
        return trim(preg_replace('~\s+~u', ' ', wp_strip_all_tags($text)) ?? $text);
    }
}
