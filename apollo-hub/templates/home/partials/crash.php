<?php
/**
 * Partial: Crash — Accommodation grid.
 * Login-gated host identity. Cards are standalone card-crash.php.
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cards_dir = APOLLO_HUB_DIR . 'templates/home/cards/';

$crashes = array(
    array( 'title' => 'Loft Artístico — Sofá', 'price' => 'R$ 90', 'meta' => 'Santa Teresa · Sofá', 'img' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&q=75' ),
    array( 'title' => 'Quarto Ensolarado', 'price' => 'R$ 150', 'meta' => 'Copacabana · Quarto Privativo', 'img' => 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?w=600&q=75' ),
    array( 'title' => 'Colchão no Chão', 'price' => 'R$ 50', 'meta' => 'Botafogo · Espaço no Chão', 'img' => 'https://images.unsplash.com/photo-1505693416388-b0346ef414b9?w=600&q=75' ),
);
?>
<section class="section" id="crash" aria-labelledby="crash-title">
    <div class="container">
        <div class="nh-section-head ai">
            <h2 id="crash-title">Acomoda::Rio</h2>
            <a href="/acomoda" aria-label="Ver todos os espaços">Ver Todos →</a>
        </div>
        <div class="nh-crash-grid">
            <?php
            foreach ( $crashes as $crash ) {
                require $cards_dir . 'card-crash.php';
            }
            ?>
            <a href="/acesso" class="nh-crash-card nh-crash-card--cta ai" aria-label="Anuncie seu espaço" translate="no">
                <span class="nh-cta-title" translate="no">Host Your Spot</span>
                <span class="nh-cta-sub" translate="no">Join the network.</span>
                <span class="nh-cta-ptbr" translate="no">Anuncie seu espaço.</span>
            </a>
        </div>
    </div>
</section>
