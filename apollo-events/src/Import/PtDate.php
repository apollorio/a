<?php

/**
 * Portuguese date/time parsing for the URL importers.
 *
 * Both supported platforms print human dates rather than machine ones, in two
 * different shapes, and neither is parseable by strtotime() (PHP has no
 * pt-BR month table). This is the single place that knows how to read them, so
 * BlueTicket and Shotgun cannot drift apart.
 *
 *   BlueTicket  .event-date  "Sáb, 08 de Agosto de 2026"
 *               .displayTime "Abertura: 23:00"
 *   Shotgun                  "quinta 6 ago de 18:00 a 01:00"
 *
 * Shotgun omits the YEAR entirely, which is the subtle trap: assuming the
 * current year silently files a January party eleven months in the past every
 * December. resolve_year() picks the year that puts the date in the nearest
 * future instead.
 *
 * @package Apollo\Event
 * @since   1.7.0
 */

declare(strict_types=1);

namespace Apollo\Event\Import;

if (! defined('ABSPATH')) {
    exit;
}

final class PtDate
{
    /** Full names, 3-letter abbreviations and the accented forms both sites use. */
    private const MONTHS = array(
        'jan' => 1, 'janeiro' => 1,
        'fev' => 2, 'fevereiro' => 2,
        'mar' => 3, 'marco' => 3, 'março' => 3,
        'abr' => 4, 'abril' => 4,
        'mai' => 5, 'maio' => 5,
        'jun' => 6, 'junho' => 6,
        'jul' => 7, 'julho' => 7,
        'ago' => 8, 'agosto' => 8,
        'set' => 9, 'setembro' => 9,
        'out' => 10, 'outubro' => 10,
        'nov' => 11, 'novembro' => 11,
        'dez' => 12, 'dezembro' => 12,
    );

    /**
     * Parse any pt-BR date string these platforms produce.
     *
     * Handles, in order:
     *   "Sáb, 08 de Agosto de 2026"   → 2026-08-08   (BlueTicket, year present)
     *   "quinta 6 ago"                → nearest future 6 Aug   (Shotgun, no year)
     *   "08/08/2026" and "08/08"      → dd/mm[/yyyy]
     *   "2026-08-08"                  → passed straight through
     *
     * @return string Y-m-d, or '' when nothing recognisable is present.
     */
    public static function date(string $text): string
    {
        $t = self::fold($text);
        if ('' === $t) {
            return '';
        }

        // Already ISO.
        if (preg_match('~(\d{4})-(\d{2})-(\d{2})~', $t, $m)) {
            return $m[1] . '-' . $m[2] . '-' . $m[3];
        }

        // "8 de agosto de 2026" / "8 agosto 2026" / "6 ago"
        $names = implode('|', array_keys(self::MONTHS));
        if (preg_match('~(\d{1,2})\s*(?:de\s+)?(' . $names . ')\b(?:\s*(?:de\s+)?(\d{4}))?~u', $t, $m)) {
            $day   = (int) $m[1];
            $month = self::MONTHS[$m[2]] ?? 0;
            if (! $month || $day < 1 || $day > 31) {
                return '';
            }
            $year = ! empty($m[3]) ? (int) $m[3] : self::resolve_year($month, $day);
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        // dd/mm/yyyy or dd/mm
        if (preg_match('~\b(\d{1,2})/(\d{1,2})(?:/(\d{2,4}))?\b~', $t, $m)) {
            $day   = (int) $m[1];
            $month = (int) $m[2];
            if ($month < 1 || $month > 12) {
                return '';
            }
            if (! empty($m[3])) {
                $year = (int) $m[3];
                if ($year < 100) {
                    $year += 2000;
                }
            } else {
                $year = self::resolve_year($month, $day);
            }
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return '';
    }

    /**
     * First HH:MM in the string.
     *
     * "Abertura: 23:00" → 23:00 · "de 18:00 a 01:00" → 18:00 (start).
     */
    public static function time(string $text): string
    {
        if (preg_match('~(\d{1,2})\s*[:h]\s*(\d{2})~u', $text, $m)) {
            $h = (int) $m[1];
            $i = (int) $m[2];
            if ($h < 24 && $i < 60) {
                return sprintf('%02d:%02d', $h, $i);
            }
        }
        return '';
    }

    /**
     * Both times from a range: "de 18:00 a 01:00" → ['18:00','01:00'].
     *
     * @return array{0:string,1:string} Second entry is '' when there is no range.
     */
    public static function time_range(string $text): array
    {
        if (preg_match_all('~(\d{1,2})\s*[:h]\s*(\d{2})~u', $text, $m, PREG_SET_ORDER) && count($m) >= 2) {
            $fmt = static function (array $x): string {
                return sprintf('%02d:%02d', (int) $x[1], (int) $x[2]);
            };
            return array($fmt($m[0]), $fmt($m[1]));
        }
        return array(self::time($text), '');
    }

    /**
     * End date for a range that crosses midnight.
     *
     * A party "de 18:00 a 01:00" ends the NEXT day. Storing the start date as
     * the end date would make it look like it ended seven hours before it began,
     * and the expiration job reads these.
     */
    public static function end_date(string $start_date, string $start_time, string $end_time): string
    {
        if ('' === $start_date || '' === $start_time || '' === $end_time) {
            return '';
        }
        if (strcmp($end_time, $start_time) >= 0) {
            return $start_date; // same night
        }
        $ts = strtotime($start_date . ' +1 day');
        return $ts ? gmdate('Y-m-d', $ts) : $start_date;
    }

    /**
     * Pick the year that places month/day nearest in the FUTURE.
     *
     * Shotgun prints "quinta 6 ago" with no year. Defaulting to the current
     * year breaks every December→January listing; this rolls forward instead,
     * with a 31-day grace window so an event that started yesterday is not
     * pushed a whole year out.
     */
    private static function resolve_year(int $month, int $day): int
    {
        $now  = (int) current_time('timestamp');
        $year = (int) wp_date('Y', $now);

        $candidate = mktime(0, 0, 0, $month, $day, $year);
        if (false === $candidate) {
            return $year;
        }
        if ($candidate < ($now - 31 * DAY_IN_SECONDS)) {
            return $year + 1;
        }
        return $year;
    }

    /**
     * Lowercase, collapse whitespace, strip the weekday prefix.
     *
     * Accents are NOT stripped — the month table carries both "marco" and
     * "março", so folding them away would only lose information.
     */
    private static function fold(string $text): string
    {
        $t = wp_strip_all_tags($text);
        $t = html_entity_decode($t, ENT_QUOTES, 'UTF-8');
        $t = mb_strtolower(trim((string) preg_replace('~\s+~u', ' ', $t)), 'UTF-8');
        // "sáb," / "quinta" / "qui." leading weekday, with or without comma.
        $t = (string) preg_replace(
            '~^(?:dom|seg|ter|qua|qui|sex|s[áa]b)[a-zç-]*\.?,?\s*~u',
            '',
            $t
        );
        return $t;
    }
}
