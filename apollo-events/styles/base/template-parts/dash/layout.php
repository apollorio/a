<?php

/**
 * Meus Eventos — screen layout (PHASE 007).
 *
 * Structure ported from the mockup's #view-eventos-meus ("réplica integral
 * do Painel de Eventos": cover hero + KPI grid, ticket-status donut,
 * season/genre bars, monthly timeline, DJ/venue/coauthor leaderboards,
 * readiness checklist, upcoming-events table) — every number below comes
 * from apollo_event_dash_get_meus_data(), real event meta/taxonomies, not
 * the mockup's simulated.data.js / extra-events-simulation.js.
 *
 * Charts are plain CSS (conic-gradient donut, flex bars) — no charting
 * library dependency added for this pass.
 *
 * @package Apollo\Event
 */

if (! defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/data.php';
$aed = apollo_event_dash_get_meus_data(get_current_user_id());

$aed_donut_colors = array(
    'free'         => 'var(--muted)',
    'available'    => 'var(--accent)',
    'soldout_soon' => '#e0a83a',
    'sold_out'     => '#e0503a',
);

// Build the conic-gradient stop list for the ticket-status donut.
$aed_donut_stops = array();
$aed_deg         = 0;
foreach ($aed['ticket_buckets'] as $key => $n) {
    if (0 === $n || 0 === $aed['total']) {
        continue;
    }
    $slice = ($n / $aed['total']) * 360;
    $aed_donut_stops[] = ($aed_donut_colors[$key] ?? 'var(--muted)') . ' ' . $aed_deg . 'deg ' . ($aed_deg + $slice) . 'deg';
    $aed_deg += $slice;
}
$aed_donut_css = $aed_donut_stops ? implode(', ', $aed_donut_stops) : 'var(--surface) 0deg 360deg';

$aed_readiness_labels = array(
    'banner'  => array('ri-image-2-line', __('Capa', 'apollo-event')),
    'video'   => array('ri-video-line', __('Vídeo', 'apollo-event')),
    'audio'   => array('ri-music-2-line', __('Áudio', 'apollo-event')),
    'tickets' => array('ri-ticket-2-line', __('Link de ingressos', 'apollo-event')),
    'coupon'  => array('ri-coupon-3-line', __('Cupons', 'apollo-event')),
    'lineup'  => array('ri-mic-line', __('Line up completo', 'apollo-event')),
);

$aed_month_max = max(1, max(array_column($aed['month_buckets'], 'count')));
$aed_lb_max    = static function (array $rows): int {
    if (empty($rows)) {
        return 1;
    }
    $vals = array();
    foreach ($rows as $r) {
        $vals[] = is_array($r) ? (int) $r['count'] : (int) $r;
    }
    return max(1, max($vals));
};
?>
<div class="aed-screen">

    <div class="aed-hero">
        <p class="aed-kicker"><?php esc_html_e('Painel do Promoter', 'apollo-event'); ?></p>
        <h1><?php esc_html_e('Meus Eventos', 'apollo-event'); ?></h1>
        <div class="aed-hero-kpis">
            <div class="aed-hero-kpi"><strong><?php echo esc_html((string) $aed['total']); ?></strong><span><?php esc_html_e('eventos geridos', 'apollo-event'); ?></span></div>
            <div class="aed-hero-kpi"><strong><?php echo esc_html((string) $aed['upcoming_count']); ?></strong><span><?php esc_html_e('próximos', 'apollo-event'); ?></span></div>
            <div class="aed-hero-kpi"><strong><?php echo esc_html((string) $aed['published']); ?></strong><span><?php esc_html_e('publicados', 'apollo-event'); ?></span></div>
            <div class="aed-hero-kpi"><strong><?php echo esc_html((string) $aed['draft']); ?></strong><span><?php esc_html_e('rascunhos', 'apollo-event'); ?></span></div>
        </div>
        <div class="aed-hero-actions">
            <a class="btn btn-primary" href="<?php echo esc_url(home_url('/eventos/novo')); ?>"><i class="ri-add-line"></i> <?php esc_html_e('Criar Novo Evento', 'apollo-event'); ?></a>
        </div>
    </div>

    <div class="aed-grid aed-cols-3">
        <div class="aed-card">
            <div class="aed-card-hd"><i class="ri-ticket-2-fill"></i> <?php esc_html_e('Status dos Ingressos', 'apollo-event'); ?></div>
            <?php if (0 === $aed['total']) : ?>
                <p class="aed-empty"><?php esc_html_e('Sem eventos ainda.', 'apollo-event'); ?></p>
            <?php else : ?>
                <div class="aed-donut" style="background:conic-gradient(<?php echo esc_attr($aed_donut_css); ?>);"><div class="aed-donut-hole"><strong><?php echo esc_html((string) $aed['total']); ?></strong></div></div>
                <ul class="aed-legend">
                    <?php foreach ($aed['ticket_buckets'] as $key => $n) : ?>
                        <li><span class="aed-dot" style="background:<?php echo esc_attr($aed_donut_colors[$key] ?? 'var(--muted)'); ?>"></span><?php echo esc_html($aed['ticket_labels'][$key] ?? $key); ?><b><?php echo esc_html((string) $n); ?></b></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="aed-card">
            <div class="aed-card-hd"><i class="ri-calendar-2-fill"></i> <?php esc_html_e('Eventos por Temporada', 'apollo-event'); ?></div>
            <?php if (empty($aed['season_buckets'])) : ?>
                <p class="aed-empty"><?php esc_html_e('Nenhuma temporada definida.', 'apollo-event'); ?></p>
            <?php else : $aed_smax = $aed_lb_max($aed['season_buckets']); ?>
                <ul class="aed-barlist">
                    <?php foreach ($aed['season_buckets'] as $name => $n) : ?>
                        <li><span class="aed-bl-label"><?php echo esc_html($name); ?></span><span class="aed-bl-track"><span class="aed-bl-fill" style="width:<?php echo esc_attr((int) round(($n / $aed_smax) * 100)); ?>%"></span></span><b><?php echo esc_html((string) $n); ?></b></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="aed-card">
            <div class="aed-card-hd"><i class="ri-radio-fill"></i> <?php esc_html_e('Gêneros Mais Presentes', 'apollo-event'); ?></div>
            <?php if (empty($aed['genre_buckets'])) : ?>
                <p class="aed-empty"><?php esc_html_e('Nenhum gênero definido.', 'apollo-event'); ?></p>
            <?php else : $aed_gmax = $aed_lb_max($aed['genre_buckets']); ?>
                <ul class="aed-barlist">
                    <?php foreach ($aed['genre_buckets'] as $name => $n) : ?>
                        <li><span class="aed-bl-label"><?php echo esc_html($name); ?></span><span class="aed-bl-track"><span class="aed-bl-fill" style="width:<?php echo esc_attr((int) round(($n / $aed_gmax) * 100)); ?>%"></span></span><b><?php echo esc_html((string) $n); ?></b></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="aed-grid">
        <div class="aed-card">
            <div class="aed-card-hd"><i class="ri-calendar-todo-fill"></i> <?php esc_html_e('Calendário dos Meus Eventos', 'apollo-event'); ?> <span class="aed-card-sub"><?php esc_html_e('próximos 7 meses', 'apollo-event'); ?></span></div>
            <div class="aed-timeline">
                <?php foreach ($aed['month_buckets'] as $m) : ?>
                    <div class="aed-tl-col">
                        <div class="aed-tl-bar" style="height:<?php echo esc_attr(max(4, (int) round(($m['count'] / $aed_month_max) * 100))); ?>%"><b><?php echo esc_html((string) $m['count']); ?></b></div>
                        <span><?php echo esc_html($m['label']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="aed-grid aed-cols-2">
        <div class="aed-card">
            <div class="aed-card-hd"><i class="ri-mic-fill"></i> <?php esc_html_e('Line Up · Mais Escalados', 'apollo-event'); ?></div>
            <?php if (empty($aed['dj_leaderboard'])) : ?>
                <p class="aed-empty"><?php esc_html_e('Nenhum DJ escalado ainda.', 'apollo-event'); ?></p>
            <?php else : $aed_djmax = $aed_lb_max($aed['dj_leaderboard']); ?>
                <ul class="aed-barlist">
                    <?php foreach ($aed['dj_leaderboard'] as $row) : ?>
                        <li><span class="aed-bl-label"><?php echo esc_html($row['name']); ?></span><span class="aed-bl-track"><span class="aed-bl-fill" style="width:<?php echo esc_attr((int) round(($row['count'] / $aed_djmax) * 100)); ?>%"></span></span><b><?php echo esc_html((string) $row['count']); ?></b></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="aed-card">
            <div class="aed-card-hd"><i class="ri-checkbox-circle-fill"></i> <?php esc_html_e('Prontidão de Conteúdo', 'apollo-event'); ?></div>
            <?php if (0 === $aed['total']) : ?>
                <p class="aed-empty"><?php esc_html_e('Sem eventos ainda.', 'apollo-event'); ?></p>
            <?php else : ?>
                <ul class="aed-readiness">
                    <?php foreach ($aed_readiness_labels as $key => $meta) : ?>
                        <li>
                            <i class="<?php echo esc_attr($meta[0]); ?>"></i>
                            <span class="aed-bl-label"><?php echo esc_html($meta[1]); ?></span>
                            <span class="aed-bl-track"><span class="aed-bl-fill" style="width:<?php echo esc_attr($aed['readiness'][$key]); ?>%"></span></span>
                            <b><?php echo esc_html((string) $aed['readiness'][$key]); ?>%</b>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="aed-grid aed-cols-2">
        <div class="aed-card">
            <div class="aed-card-hd"><i class="ri-map-pin-2-fill"></i> <?php esc_html_e('Locais Mais Usados', 'apollo-event'); ?></div>
            <?php if (empty($aed['venue_leaderboard'])) : ?>
                <p class="aed-empty"><?php esc_html_e('Nenhum local vinculado ainda.', 'apollo-event'); ?></p>
            <?php else : $aed_vmax = $aed_lb_max($aed['venue_leaderboard']); ?>
                <ul class="aed-barlist">
                    <?php foreach ($aed['venue_leaderboard'] as $name => $n) : ?>
                        <li><span class="aed-bl-label"><?php echo esc_html($name); ?></span><span class="aed-bl-track"><span class="aed-bl-fill" style="width:<?php echo esc_attr((int) round(($n / $aed_vmax) * 100)); ?>%"></span></span><b><?php echo esc_html((string) $n); ?></b></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="aed-card">
            <div class="aed-card-hd"><i class="ri-group-2-fill"></i> <?php esc_html_e('Colaboração na Autoria', 'apollo-event'); ?></div>
            <?php if (empty($aed['coauthor_leaderboard'])) : ?>
                <p class="aed-empty"><?php esc_html_e('Nenhuma coautoria registrada.', 'apollo-event'); ?></p>
            <?php else : $aed_cmax = $aed_lb_max($aed['coauthor_leaderboard']); ?>
                <ul class="aed-barlist">
                    <?php foreach ($aed['coauthor_leaderboard'] as $name => $n) : ?>
                        <li><span class="aed-bl-label"><?php echo esc_html($name); ?></span><span class="aed-bl-track"><span class="aed-bl-fill" style="width:<?php echo esc_attr((int) round(($n / $aed_cmax) * 100)); ?>%"></span></span><b><?php echo esc_html((string) $n); ?></b></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="aed-grid">
        <div class="aed-card">
            <div class="aed-card-hd"><i class="ri-list-check-2"></i> <?php esc_html_e('Meus Próximos Eventos', 'apollo-event'); ?></div>
            <?php if (empty($aed['upcoming_table'])) : ?>
                <p class="aed-empty"><?php esc_html_e('Nenhum evento futuro agendado.', 'apollo-event'); ?></p>
            <?php else : ?>
                <table class="aed-table">
                    <thead><tr>
                        <th><?php esc_html_e('Evento', 'apollo-event'); ?></th>
                        <th><?php esc_html_e('Data', 'apollo-event'); ?></th>
                        <th><?php esc_html_e('Local', 'apollo-event'); ?></th>
                        <th><?php esc_html_e('Ingressos', 'apollo-event'); ?></th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($aed['upcoming_table'] as $row) : ?>
                            <tr>
                                <td><?php echo esc_html($row['title']); ?></td>
                                <td><?php echo esc_html($row['start_date'] ? date_i18n('d/m/Y', strtotime($row['start_date'])) : '—'); ?></td>
                                <td><?php echo esc_html($row['loc_name'] ?: '—'); ?></td>
                                <td><span class="aed-tag" style="background:<?php echo esc_attr($aed_donut_colors[$row['ticket_status']] ?? 'var(--muted)'); ?>"><?php echo esc_html($aed['ticket_labels'][$row['ticket_status']] ?? $row['ticket_status']); ?></span></td>
                                <td><a href="<?php echo esc_url($row['edit_url']); ?>"><i class="ri-edit-2-line"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <p class="aed-disclaimer"><?php esc_html_e('O apollo::rio é um hub de divulgação — não processa ingressos nem tem acesso à contagem de portaria. Estes números refletem apenas o que está cadastrado na plataforma.', 'apollo-event'); ?></p>
</div>
