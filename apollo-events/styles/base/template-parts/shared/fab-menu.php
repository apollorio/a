<?php
/**
 * Shared — FAB Menu (Floating Action Button)
 *
 * Context-aware FAB that adapts links based on the current page type.
 * Uses GSAP (from Apollo CDN) for open/close animation.
 *
 * Expected variables:
 *   $fab_context (string) - 'single' | 'dashboard' | 'archive' (defaults to 'single')
 *   $post_id     (int)    - Current event ID (used in single context)
 *
 * @package Apollo\Event
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$fab_context = $fab_context ?? 'single';

// Build context-aware links
$fab_links = array();

if ( 'single' === $fab_context ) {
    $fab_links = array(
        array(
            'icon'  => 'ri-ticket-line',
            'label' => __( 'Ingressos', 'apollo-events' ),
            'href'  => '#ticket-widget',
        ),
        array(
            'icon'  => 'ri-music-2-line',
            'label' => __( 'Line-up', 'apollo-events' ),
            'href'  => '#timetable',
        ),
        array(
            'icon'  => 'ri-map-pin-line',
            'label' => __( 'Local', 'apollo-events' ),
            'href'  => '#location-mini',
        ),
        array(
            'icon'  => 'ri-image-line',
            'label' => __( 'Galeria', 'apollo-events' ),
            'href'  => '#gallery',
        ),
    );
} elseif ( 'dashboard' === $fab_context ) {
    $fab_links = array(
        array(
            'icon'  => 'ri-calendar-event-line',
            'label' => __( 'Eventos', 'apollo-events' ),
            'href'  => home_url( '/eventos' ),
        ),
        array(
            'icon'  => 'ri-dashboard-line',
            'label' => __( 'Painel', 'apollo-events' ),
            'href'  => home_url( '/meus-eventos' ),
        ),
        array(
            'icon'  => 'ri-bar-chart-box-line',
            'label' => __( 'Dados', 'apollo-events' ),
            'href'  => '#charts',
        ),
        array(
            'icon'  => 'ri-add-circle-line',
            'label' => __( 'Novo Evento', 'apollo-events' ),
            'href'  => home_url( '/novo-evento' ),
        ),
    );
} elseif ( 'archive' === $fab_context ) {
    $fab_links = array(
        array(
            'icon'  => 'ri-calendar-event-line',
            'label' => __( 'Eventos', 'apollo-events' ),
            'href'  => home_url( '/eventos' ),
        ),
        array(
            'icon'  => 'ri-filter-3-line',
            'label' => __( 'Filtrar', 'apollo-events' ),
            'href'  => '#toolbar',
        ),
        array(
            'icon'  => 'ri-map-pin-line',
            'label' => __( 'Mapa', 'apollo-events' ),
            'href'  => '#map',
        ),
    );
}

/**
 * Filter: apollo/event/fab_links
 * Allow other plugins to add/modify FAB links.
 */
$fab_links = apply_filters( 'apollo/event/fab_links', $fab_links, $fab_context );

if ( empty( $fab_links ) ) {
    return;
}
?>

<!-- FAB Menu -->
<div class="fab-overlay" id="fabOverlay"></div>

<nav class="fab" id="fabMenu" aria-label="<?php esc_attr_e( 'Menu rápido', 'apollo-events' ); ?>">
    <div class="fab__sheet" id="fabSheet">
        <?php foreach ( $fab_links as $link ) : ?>
            <a href="<?php echo esc_url( $link['href'] ); ?>" class="fab__item">
                <i class="<?php echo esc_attr( $link['icon'] ); ?>"></i>
                <span><?php echo esc_html( $link['label'] ); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <button class="fab__trigger" id="fabTrigger" aria-expanded="false" aria-controls="fabSheet">
        <i class="ri-apps-line fab__icon-open"></i>
        <i class="ri-close-line fab__icon-close"></i>
    </button>
</nav>

<style>
.fab-overlay {
    position: fixed; inset: 0; z-index: 998;
    background: rgba(var(--rgb-d),.45); backdrop-filter: blur(6px);
    opacity: 0; pointer-events: none; transition: opacity .3s;
}
.fab-overlay.is-active { opacity: 1; pointer-events: auto; }

.fab {
    position: fixed; bottom: 24px; right: 24px; z-index: 999;
    display: flex; flex-direction: column-reverse; align-items: flex-end; gap: 12px;
}
.fab__trigger {
    width: 56px; height: 56px; border-radius: 50%;
    background: var(--primary, FF9820); color: #fff;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; box-shadow: 0 4px 20px rgba(244,95,0,.35);
    position: relative;
}
.fab__trigger .fab__icon-close { display: none; }
.fab.is-open .fab__trigger .fab__icon-open { display: none; }
.fab.is-open .fab__trigger .fab__icon-close { display: block; }

.fab__sheet {
    display: flex; flex-direction: column; gap: 8px;
    opacity: 0; pointer-events: none;
    transform: translateY(12px);
}
.fab.is-open .fab__sheet { opacity: 1; pointer-events: auto; transform: translateY(0); }

.fab__item {
    display: flex; align-items: center; gap: 10px;
    background: #fff; color: #121214; text-decoration: none;
    padding: 10px 16px; border-radius: 12px;
    font-size: 14px; font-weight: 500;
    box-shadow: 0 2px 12px rgba(var(--rgb-d),.12);
    white-space: nowrap;
}
.fab__item:hover { background: var(--primary, FF9820); color: #fff; }
.fab__item i { font-size: 18px; }
</style>

<script>
(function() {
    'use strict';
    var trigger = document.getElementById('fabTrigger');
    var menu    = document.getElementById('fabMenu');
    var overlay = document.getElementById('fabOverlay');
    var sheet   = document.getElementById('fabSheet');

    if ( !trigger || !menu ) return;

    function toggleFab() {
        var isOpen = menu.classList.toggle('is-open');
        overlay.classList.toggle('is-active', isOpen);
        trigger.setAttribute('aria-expanded', isOpen);

        if (typeof gsap !== 'undefined' && sheet) {
            if (isOpen) {
                gsap.fromTo(sheet.children,
                    { y: 20, opacity: 0 },
                    { y: 0, opacity: 1, duration: .3, stagger: .06, ease: 'power2.out' }
                );
            }
        }
    }

    trigger.addEventListener('click', toggleFab);
    overlay.addEventListener('click', toggleFab);

    // Close on anchor click (scroll links)
    sheet.querySelectorAll('a[href^="#"]').forEach(function(a) {
        a.addEventListener('click', function() {
            if (menu.classList.contains('is-open')) toggleFab();
        });
    });
})();
</script>
