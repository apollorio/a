<?php

/**
 * ═══════════════════════════════════════════════════════════════════════
 * MODELO · EVENTS — the records that make the other two come alive
 * ═══════════════════════════════════════════════════════════════════════
 *
 * PHASE 2026-08-11b.
 *
 * ── Why these exist ───────────────────────────────────────────────────
 * `/dj/modelo` rendered "0 Eventos · 0 Cidades" and `/local/modelo` "0
 * Eventos". Those are not empty fields — they are DERIVED counts, honest
 * output over an empty relation. No amount of meta on the two records
 * could change them, because the number they count is the number of
 * linked `event` posts, and there were none.
 *
 * Four sections on the artist card and two on the venue page read from
 * that relation and nothing else:
 *
 *     dj    Tocou em      WP_Query event WHERE _event_dj_ids CONTAINS id, past
 *           Tocou com     co-DJs from those same line-ups
 *           Em números    COUNT events · COUNT DISTINCT venues · COUNT DISTINCT cities
 *           rodapé        newest cover from Tocou em  (_dj_footer_image fallback)
 *     local Agenda        WP_Query event WHERE _event_loc_id = id, upcoming
 *           Eventos       COUNT of the same
 *
 * So: 14 events, cross-linked to BOTH modelo records.
 *   · 6 in the past    → the artist's history, the venue's track record
 *   · 8 in the future  → the venue's agenda (rail shows 6 + "ver todas")
 *
 * Every one carries `_event_loc_id` = the modelo venue and `_event_dj_ids`
 * = [the modelo artist], which is what makes a single seed light up two
 * pages at once.
 *
 * ── Naming ────────────────────────────────────────────────────────────
 * `Nome Evento 1` … `Nome Evento 14`, exactly as specified. Numbered, not
 * invented: a demo event called "Concrete Nights" reads as a real party
 * somebody threw, and the moment it is indexed it becomes a small lie.
 * A number is unmistakably a slot waiting to be filled.
 *
 * Dates ARE realistic — they have to be, because "past" and "upcoming" is
 * the whole mechanism being demonstrated. They are computed relative to
 * the seed run, not hardcoded, so the modelo never rots into a page of
 * events that all happened in 2026.
 *
 * @package Apollo\Core
 * @since   6.2.9
 * @return  array<string,mixed>
 */

if (! defined('ABSPATH')) {
    exit;
}

$img = 'https://images.unsplash.com/photo-';

/* Covers cycle so consecutive cards never repeat side by side in a rail. */
$covers = array(
    $img . '1516450360452-9312f5e86fc7?w=1200&q=80',
    $img . '1470225620780-dba8ba36b745?w=1200&q=80',
    $img . '1534528741775-53994a69daeb?w=1200&q=80',
    $img . '1514525253161-7a46d19cd819?w=1200&q=80',
    $img . '1504898770365-14faca6a7320?w=1200&q=80',
    $img . '1492684223066-81342ee5ff30?w=1200&q=80',
);

/* Relative to now, so the modelo stays a live demonstration of past-vs-
   upcoming forever instead of becoming an archive of one month in 2026. */
$now      = current_time('timestamp');
$rows     = array();
$n        = 0;

/* ── 6 PAST — feeds dj.playedOn, dj.stats, dj footer image ───────────── */
$past_offsets = array(-14, -35, -63, -98, -140, -189); // dias atrás, espaçados
foreach ($past_offsets as $i => $days) {
    $n++;
    $ts = strtotime($days . ' days', $now);
    $rows[] = array(
        'post' => array(
            'post_title'   => 'Nome Evento ' . $n,
            'post_name'    => 'nome-evento-' . $n,
            'post_status'  => 'publish',
            'post_excerpt' => 'Evento modelo ' . $n . ' — já realizado. Alimenta "Tocou em", '
                . '"Tocou com" e "Em números" no cartão do artista.',
            'post_content' => 'Descrição do evento. É este texto que aparece na página do evento e '
                . 'no card expandido. Conta o que foi a noite: proposta, line-up, ambiente.',
        ),
        'meta' => array(
            '_event_start_date'   => wp_date('Y-m-d', $ts),
            '_event_end_date'     => wp_date('Y-m-d', strtotime('+1 day', $ts)),
            '_event_start_time'   => '23:00',
            '_event_end_time'     => array('07:00', '08:00', '06:00')[$i % 3],
            '_event_status'       => 'realizado',
            '_event_is_gone'      => '1',
            '_event_privacy'      => 'public',
            '_event_ticket_price' => array('R$ 60', 'R$ 80', 'R$ 45')[$i % 3],
            '_event_banner'       => $covers[$i % 6],
        ),
        'terms' => array(
            'event_category' => array('Categoria Modelo'),
            'sound'          => array('Sonoridade Modelo', 'Segunda Sonoridade'),
        ),
    );
}

/* ── 8 UPCOMING — feeds local.events (rail 6 + "ver todas") ──────────── */
$future_offsets = array(6, 13, 27, 41, 55, 76, 97, 132);
foreach ($future_offsets as $i => $days) {
    $n++;
    $ts = strtotime('+' . $days . ' days', $now);
    $rows[] = array(
        'post' => array(
            'post_title'   => 'Nome Evento ' . $n,
            'post_name'    => 'nome-evento-' . $n,
            'post_status'  => 'publish',
            'post_excerpt' => 'Evento modelo ' . $n . ' — confirmado. Alimenta a Agenda do espaço.',
            'post_content' => 'Descrição do evento. É este texto que aparece na página do evento e '
                . 'no card expandido. Conta o que será a noite: proposta, line-up, ambiente.',
        ),
        'meta' => array(
            '_event_start_date'   => wp_date('Y-m-d', $ts),
            '_event_end_date'     => wp_date('Y-m-d', strtotime('+1 day', $ts)),
            '_event_start_time'   => array('23:00', '23:59', '22:00')[$i % 3],
            '_event_end_time'     => array('07:00', '08:00', '10:00')[$i % 3],
            /* Três estados diferentes de propósito: a Agenda mostra badges
               distintos e o modelo precisa demonstrar os três. */
            '_event_status'       => array('confirmado', 'ingressos-em-breve', 'lineup-em-breve')[$i % 3],
            '_event_privacy'      => 'public',
            '_event_ticket_price' => array('R$ 70', 'R$ 90', 'A definir')[$i % 3],
            '_event_ticket_url'   => 0 === $i % 3 ? 'https://ingressos.exemplo.br/nome-evento-' . $n : '',
            '_event_banner'       => $covers[$i % 6],
        ),
        'terms' => array(
            'event_category' => array('Categoria Modelo'),
            'sound'          => array('Sonoridade Modelo', 'Terceira Sonoridade'),
        ),
    );
}

return array(
    /* The seeder reads this to know which keys carry the cross-links, and
       fills them with the real post IDs once both modelo records exist —
       IDs cannot be known at authoring time. */
    'link' => array(
        'local' => '_event_loc_id',   // integer
        'dj'    => '_event_dj_ids',   // array of ints
    ),
    'rows' => $rows,
);
