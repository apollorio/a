<?php
/**
 * Partial: Events — Grid with month dropdown.
 * Loops apollo_event CPT. Cards are standalone card-event.php.
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cards_dir = APOLLO_HUB_DIR . 'templates/home/cards/';

$events = array(
    array( 'day' => '14', 'month' => 'mar', 'title' => 'Warehouse 99', 'djs' => 'Marta Supernova, Leo Janeiro', 'loc' => 'Galpão Industrial · Zona Portuária', 'genres' => 'Techno, Hardgroove, Industrial', 'tags' => array( 'Hoje', 'Esgotado' ), 'img' => 'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=600&q=75' ),
    array( 'day' => '15', 'month' => 'mar', 'title' => 'Analog Market', 'djs' => 'DJ Méton, Caio Ferreira', 'loc' => 'Praça Tiradentes · Centro', 'genres' => 'Swap Meet, Vinyl, Lo-Fi', 'tags' => array( 'Amanhã' ), 'img' => 'https://images.unsplash.com/photo-1544211075-84620583cc72?w=600&q=75' ),
    array( 'day' => '22', 'month' => 'mar', 'title' => 'Lapa Arch Tour', 'djs' => 'Renata Luz, Beatriz Santos', 'loc' => 'Arcos da Lapa · Centro', 'genres' => 'Walking, History, Samba', 'tags' => array( 'Sáb 22' ), 'img' => 'https://images.unsplash.com/photo-1563198804-b144dfc84796?w=600&q=75' ),
    array( 'day' => '22', 'month' => 'mar', 'title' => 'Gop Tun Rio', 'djs' => 'Lucas Vieira, DJ Tato', 'loc' => 'Night Club Rio · Leblon', 'genres' => 'House, Disco, Funk', 'tags' => array( 'Sáb 22' ), 'img' => 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=600&q=75' ),
    array( 'day' => '23', 'month' => 'mar', 'title' => 'Acoustic Sundays', 'djs' => 'Banda Carioca, Zé Neto', 'loc' => 'Parque Lage · Jardim Botânico', 'genres' => 'Live, MPB, Bossa Nova', 'tags' => array( 'Dom 23' ), 'img' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=600&q=75' ),
    array( 'day' => '28', 'month' => 'mar', 'title' => 'Void System', 'djs' => 'Anderson Noise, Edu K', 'loc' => 'Void · Lapa', 'genres' => 'Techno, EBM, Industrial', 'tags' => array( 'Sex 28' ), 'img' => 'https://images.unsplash.com/photo-1506157786151-b8491531f063?w=600&q=75' ),
);
?>
<section class="section" id="events" aria-labelledby="events-title">
    <div class="container">
        <div class="nh-section-head ai">
            <span class="grouped-left">
                <h2 id="events-title">Events</h2>
                <div class="nh-month-dropdown">
                    <button id="nhMonthTrigger" class="nh-month-trigger" aria-label="Selecionar mês">
                        <span class="nh-month-text">March</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                    <ul id="nhMonthMenu" class="nh-month-row"></ul>
                </div>
            </span>
            <a href="/eventos" aria-label="Ver todos os eventos">Ver Todos →</a>
        </div>
        <div class="nh-events-grid">
            <?php
            foreach ( $events as $event ) {
                require $cards_dir . 'card-event.php';
            }
            ?>
            <a href="/eventos" class="a-eve-card a-eve-explore ai" aria-label="Ver todos os eventos">
                <div class="xp-inner">
                    <i class="ri-arrow-right-up-line xp-icon" aria-hidden="true"></i>
                    <span class="xp-all">ALL</span>
                    <span class="xp-label">Eventos</span>
                </div>
            </a>
        </div>
    </div>
</section>
