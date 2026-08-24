<?php
/**
 * Partial: Tracks — "Out Now!" horizontal scroll grid.
 * Loops apollo_dj CPT. Each card is a standalone card-track.php.
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cards_dir = APOLLO_HUB_DIR . 'templates/home/cards/';

// Demo data — production will use WP_Query CPT=apollo_dj
$tracks = array(
    array( 'title' => 'Noite Analógica', 'artist' => 'Beatriz Santos', 'genre' => 'Deep House', 'plays' => '4.2k', 'img' => 'https://images.unsplash.com/photo-1571330735066-03aaa9429d89?w=400&q=75' ),
    array( 'title' => 'Zona Norte Groove', 'artist' => 'Caio Ferreira', 'genre' => 'Funk Carioca', 'plays' => '9.7k', 'img' => 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?w=400&q=75' ),
    array( 'title' => 'Lapa After', 'artist' => 'DJ Méton', 'genre' => 'Techno', 'plays' => '2.1k', 'img' => 'https://images.unsplash.com/photo-1563198804-b144dfc84796?w=400&q=75' ),
    array( 'title' => 'Ipanema Nights', 'artist' => 'Renata Luz', 'genre' => 'Baile / House', 'plays' => '7.5k', 'img' => 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=400&q=75' ),
    array( 'title' => 'Santa Teresa 3AM', 'artist' => 'Lucas Vieira', 'genre' => 'Ambient', 'plays' => '1.8k', 'img' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400&q=75' ),
);
?>
<section class="section" id="tracks" aria-labelledby="tracks-title">
    <div class="container">
        <div class="nh-section-head ai">
            <h2 id="tracks-title">Out Now!</h2>
            <a href="/djs" aria-label="Ver todos os lançamentos">Explore All →</a>
        </div>
        <div class="nh-tracks-grid">
            <?php
            foreach ( $tracks as $track ) {
                require $cards_dir . 'card-track.php';
            }
            ?>
            <a href="/djs" class="nh-track-card nh-explore-card ai" aria-label="Ver todos os DJs">
                <div class="xp-inner">
                    <i class="ri-arrow-right-up-line xp-icon" aria-hidden="true"></i>
                    <span class="xp-all">ALL</span>
                    <span class="xp-label">Releases</span>
                </div>
            </a>
        </div>
    </div>
</section>
