<?php

/**
 * Template: Archive — Classifieds Marketplace
 *
 * Blank-canvas template for /anuncios, /marketplace.
 * Ultra-modular: loads partials from marketplace/parts/.
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}

$parts = plugin_dir_path(__FILE__) . 'marketplace/parts/';
$v     = defined('APOLLO_ADVERTS_VERSION') ? APOLLO_ADVERTS_VERSION : '1.0.1';
$base  = defined('APOLLO_ADVERTS_URL') ? APOLLO_ADVERTS_URL : plugin_dir_url(__DIR__);

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title' => 'Classificados - Apollo::Rio',
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <?php
}
?>
<link rel="stylesheet" href="<?php echo esc_url($base . 'assets/css/marketplace.css?v=' . $v); ?>">
</head>

<body>

<main class="apl-marketplace-page" id="aplMarketplace">

    <?php load_template($parts . 'page-header.php', true); ?>
    <?php load_template($parts . 'info-box.php', true); ?>

    <!-- ═══ TICKETS (Carousel) ═══ -->
    <section class="marketplace-section" id="sectionTickets">
        <?php load_template($parts . 'ticket-carousel.php', false); ?>
    </section>

    <!-- ═══ ACCOMMODATIONS (Grid) ═══ -->
    <section class="marketplace-section" id="sectionAccommodation">
        <?php load_template($parts . 'accommodation-grid.php', false); ?>
    </section>

    <!-- ═══ MODAL DISCLAIMER ═══ -->
    <?php load_template($parts . 'modal-disclaimer.php', true); ?>

</main>

<script src="<?php echo esc_url($base . 'assets/js/marketplace.js?v=' . $v); ?>" defer></script>
<?php
if (function_exists('apollo_render_document_close')) {
    apollo_render_document_close();
} else {
    ?>
</body>
</html>
    <?php
}
