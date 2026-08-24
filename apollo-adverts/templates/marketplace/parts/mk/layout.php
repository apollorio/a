<?php
/**
 * Marketplace — screen layout (PHASE 003).
 *
 * Composes the screen from independent parts, one concern each:
 *   head     title + safety CTA (data-apollo-suporte)
 *   pills    filters from the real classified_domain taxonomy
 *   section  reusable section wrapper, driven by advert type
 *
 * Ticket and accommodation cards are the EXISTING hardened parts
 * (card-ticket.php / card-accommodation.php) — the ones carrying the seller
 * privacy and hostel lock rules. This screen reuses them rather than shipping
 * a second card implementation that could drift from those guarantees.
 *
 * @package Apollo\Adverts
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$mk = __DIR__ . '/';
?>
<div class="mk-screen">
    <?php require $mk . 'head.php'; ?>
    <?php require $mk . 'pills.php'; ?>

    <?php
    $mk_label = __( 'Repasses', 'apollo-adverts' );
    $mk_icon  = 'ri-ticket-2-fill';
    $mk_type  = 'ticket';
    $mk_card  = 'card-ticket.php';
    require $mk . 'section.php';

    $mk_label = __( 'Hospedagem', 'apollo-adverts' );
    $mk_icon  = 'ri-home-heart-line';
    $mk_type  = 'accommodation';
    $mk_card  = 'card-accommodation.php';
    require $mk . 'section.php';

    /* Catch-all: guarantees this page lists EVERY published advert. Renders
       nothing when the bucket is empty, which is the expected steady state. */
    $mk_label = __( 'Outros anúncios', 'apollo-adverts' );
    $mk_icon  = 'ri-price-tag-3-line';
    $mk_type  = 'other';
    $mk_card  = 'card-ticket.php';
    require $mk . 'section.php';
    ?>
</div>

<script>
    (function () {
        'use strict';
        /* Filter pills — client-side, operating on already-rendered cards.
           Cards carry their domain terms server-side, so filtering never
           requires a round trip and never re-reveals anything the server
           chose to withhold. */
        var pills = document.querySelectorAll('[data-mk-filter]');
        if (!pills.length) return;
        pills.forEach(function (p) {
            p.addEventListener('click', function () {
                pills.forEach(function (x) { x.classList.remove('is-active'); });
                p.classList.add('is-active');
                var f = p.getAttribute('data-mk-filter');
                document.querySelectorAll('[data-mk-domains]').forEach(function (card) {
                    var d = (card.getAttribute('data-mk-domains') || '').split(' ');
                    card.style.display = (f === 'all' || d.indexOf(f) !== -1) ? '' : 'none';
                });
            });
        });
    })();
</script>
