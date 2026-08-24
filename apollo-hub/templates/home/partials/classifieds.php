<?php
/**
 * Partial: Classifieds — Ticket resale marketplace.
 * Seller identity hidden until login.
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$classifieds = array(
    array( 'time' => '10:42', 'title' => 'Warehouse 99 (Entrada)', 'price' => 'R$ 40', 'badge' => 'Verificado', 'badge_class' => 'nh-badge--verified' ),
    array( 'time' => '09:15', 'title' => 'Gop Tun (Early Bird)', 'price' => 'R$ 80', 'badge' => 'Não verificado', 'badge_class' => '' ),
    array( 'time' => '08:50', 'title' => 'Analog Market (2×)', 'price' => 'R$ 60', 'badge' => 'Verificado', 'badge_class' => 'nh-badge--verified' ),
    array( 'time' => '07:30', 'title' => 'Neon Garden (VIP)', 'price' => 'R$ 120', 'badge' => 'Verificado', 'badge_class' => 'nh-badge--verified' ),
    array( 'time' => '06:10', 'title' => 'Void System (Pista)', 'price' => 'R$ 95', 'badge' => 'Urgente', 'badge_class' => 'nh-badge--accent' ),
);
?>
<section class="section" id="resell" aria-labelledby="resell-title">
    <div class="container">
        <div class="nh-section-head ai">
            <h2 id="resell-title">Classificados</h2>
            <a href="/acesso" aria-label="Anunciar ingresso">Sell Ticket +</a>
        </div>
        <p class="nh-resale-intro ai">Marketplace peer-to-peer. Identidade do vendedor visível apenas para membros.</p>

        <div class="nh-resale-list ai" role="list">
            <?php foreach ( $classifieds as $item ) : ?>
                <a href="/acesso" class="nh-resale-row" role="listitem">
                    <div class="nh-resale-time" aria-label="<?php echo esc_attr( $item['time'] ); ?>"><?php echo esc_html( $item['time'] ); ?></div>
                    <div class="nh-resale-details">
                        <h3><?php echo esc_html( $item['title'] ); ?></h3>
                        <span class="nh-resale-seller-hidden"><span class="nh-seller-text">●&ensp;●&ensp;●</span></span>
                    </div>
                    <div class="nh-resale-price-wrap">
                        <span class="nh-resale-price"><?php echo esc_html( $item['price'] ); ?></span>
                        <span class="nh-badge <?php echo esc_attr( $item['badge_class'] ); ?>"><?php echo esc_html( $item['badge'] ); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>

            <a href="/repasse" class="nh-resale-row nh-resale-row--all" role="listitem" aria-label="Ver todos os ingressos">
                <div>
                    <p class="nh-rall-label">Marketplace</p>
                    <p class="nh-rall-title">ALL Tickets Re-sell</p>
                </div>
                <i class="ri-arrow-right-up-line nh-rall-icon" aria-hidden="true"></i>
            </a>
        </div>

        <aside class="nh-disclaimer ai" role="note">
            <div class="nh-disclaimer-accent" aria-hidden="true"></div>
            <div class="nh-disclaimer-body">
                <div class="nh-disclaimer-header">
                    <i class="ri-shield-check-fill" aria-hidden="true"></i>
                    <h4>Segurança em Primeiro Lugar</h4>
                </div>
                <p>Apollo é uma ponte de conexão. Não processamos pagamentos. Sempre verifique a reputação do vendedor. Encontre-se em local público.</p>
            </div>
        </aside>
    </div>
</section>
