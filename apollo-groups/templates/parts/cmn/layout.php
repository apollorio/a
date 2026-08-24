<?php
/**
 * Comunas — screen layout (PHASE 004).
 *
 * hero + search + grid, each an independent part. Data comes from
 * apollo-groups' own tables via apollo_cmn_list(); the mockup's
 * APOLLO_COMUNAS fixture global is deliberately not ported.
 *
 * @package Apollo\Groups
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/data.php';
$cmn_list = function_exists( 'apollo_cmn_list' ) ? apollo_cmn_list() : array();
?>
<div class="cmn-screen">
    <?php require __DIR__ . '/hero.php'; ?>

    <div class="cmn-search">
        <i class="ri-search-line" aria-hidden="true"></i>
        <input type="search" id="cmnSearch" placeholder="<?php esc_attr_e( 'Buscar comunas...', 'apollo-groups' ); ?>"
            aria-label="<?php esc_attr_e( 'Buscar comunas', 'apollo-groups' ); ?>">
    </div>

    <div class="cmn-sec-lbl"><i class="ri-group-3-line"></i> <?php esc_html_e( 'Todas as comunas', 'apollo-groups' ); ?></div>

    <?php if ( empty( $cmn_list ) ) : ?>
        <p class="cmn-empty"><?php esc_html_e( 'Nenhuma comuna criada ainda. Seja quem começa a primeira.', 'apollo-groups' ); ?></p>
        <?php if ( is_user_logged_in() ) : ?>
            <a class="btn btn-primary" href="<?php echo esc_url( home_url( '/criar-comuna' ) ); ?>"><i class="ri-add-circle-line"></i> <?php esc_html_e( 'Criar comuna', 'apollo-groups' ); ?></a>
        <?php endif; ?>
    <?php else : ?>
        <div class="grid-layout cmn-grid-block" id="cmnGrid">
            <?php foreach ( $cmn_list as $c ) { require __DIR__ . '/card.php'; } ?>
        </div>
    <?php endif; ?>
</div>

<script>
    (function () {
        'use strict';
        /* Search filters already-rendered cards — no round trip, and it can
           never surface a community the server did not already send. */
        var input = document.getElementById('cmnSearch');
        var grid = document.getElementById('cmnGrid');
        if (!input || !grid) return;
        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            grid.querySelectorAll('.gallery-card').forEach(function (card) {
                card.style.display = !q || card.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
            });
        });
    })();
</script>
