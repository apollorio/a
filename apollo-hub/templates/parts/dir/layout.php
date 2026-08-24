<?php

/**
 * Hub.rio directory — screen layout (PHASE 005).
 *
 * Pixel-faithful port of _official_layout/js/view.hub.js for guests on /hub.
 * NOTICE text and SCREENS/APOLLO_HUB tile copy match the mockup 1:1; routes
 * map #/… → live WordPress permalinks. "Suporte" is a real
 * [data-apollo-suporte] trigger (house convention) instead of inert text.
 *
 * @package Apollo\Hub
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="hub-screen">
    <div class="hub-hero">
        <i class="<?php echo esc_attr($ahd_cfg['icon']); ?>" aria-hidden="true"></i>
        <h1><?php echo esc_html($ahd_cfg['title']); ?></h1>
        <p class="hub-tag"><?php echo esc_html($ahd_cfg['tag']); ?></p>
        <div class="hub-notice">
            <p>
                Este serviço ainda está fora do ar em testes ou em reparos para lançamento a todos no mais breve possivel!<br>
                Quer entrar no grupo de pré-teste antes dos serviços serem liberados? Mande um contato via
                <button type="button" class="hub-notice-link" data-apollo-suporte>Suporte</button>
                que você pode ser convidado!
            </p>
        </div>
    </div>

    <?php if ($ahd_cfg['grid']) : ?>
        <?php
        /* APOLLO_HUB tiles from simulated.data.js — labels/icons/descs verbatim;
           #/routes rewritten to live permalinks (Mapa fixed to /mapa, not #/anuncios). */
        $ahd_tiles = array(
            array(
                'icon'  => 'ri-ticket-2-line',
                'label' => 'Eventos',
                'desc'  => 'Portal, radar e painel do promoter',
                'url'   => home_url('/eventos'),
            ),
            array(
                'icon'  => 'ri-store-3-line',
                'label' => 'Anúncios',
                'desc'  => 'Revenda de ingressos e acomodações',
                'url'   => home_url('/anuncios'),
            ),
            array(
                'icon'  => 'ri-group-3-fill',
                'label' => 'Comunas',
                'desc'  => 'Comunidades públicas — entre e crie',
                'url'   => home_url('/comunas'),
            ),
            array(
                'icon'  => 'ri-building-3-line',
                'label' => 'Feed',
                'desc'  => 'A casa — o que a cena está postando',
                'url'   => home_url('/feed'),
            ),
            array(
                'icon'  => 'ri-map-pin-2-line',
                'label' => 'Mapa',
                'desc'  => 'Locais e regiões da noite carioca',
                'url'   => home_url('/mapa'),
            ),
            array(
                'icon'  => 'ri-body-scan-line',
                'label' => 'Hub.rio',
                'desc'  => 'Você está aqui',
                'url'   => home_url('/hub'),
            ),
        );
        ?>
        <div class="hub-grid">
            <?php foreach ($ahd_tiles as $t) : ?>
                <a class="hub-tile" href="<?php echo esc_url($t['url']); ?>">
                    <i class="<?php echo esc_attr($t['icon']); ?>" aria-hidden="true"></i>
                    <span><b><?php echo esc_html($t['label']); ?></b><span><?php echo esc_html($t['desc']); ?></span></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
