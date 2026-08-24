<?php
/**
 * Home Page — Blank Canvas Master Template
 *
 * Zero theme interference. Loads Apollo CDN + modular partials.
 * All navigation via FAB system (no navbar).
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cdn_url = defined( 'APOLLO_CDN_URL' ) ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/';
$partials = APOLLO_HUB_DIR . 'templates/home/partials/';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
    <meta name="theme-color" content="#0A0A0A"/>
    <title>APOLLO::RIO — Underground Culture Guide 2026</title>
    <script src="<?php echo esc_url( $cdn_url . 'core.min.js' ); ?>" fetchpriority="high"></script>
    <script src="<?php echo esc_url( $cdn_url . 'js/page-layout.js' ); ?>" defer></script>
    <?php wp_head(); ?>
</head>
<body class="apollo-home-page">

<?php
// Persistent UI — always visible, outside panels
require $partials . 'radio.php';
require $partials . 'fab.php';
?>

<section data-panel="home" data-glyph="H">
    <?php
    require $partials . 'hero.php';
    require $partials . 'marquee.php';
    require $partials . 'tracks.php';
    require $partials . 'events.php';
    require $partials . 'classifieds.php';
    require $partials . 'crash.php';
    require $partials . 'map.php';
    require $partials . 'footer.php';
    ?>
</section>

<section data-panel="chat" data-glyph="C">
    <div class="container" style="padding-top:calc(80px + var(--safe-top));">
        <button data-back="1" class="return-back" aria-label="Voltar">
            <i class="ri-corner-up-right-line"></i>
        </button>
        <p class="ai font-mono text-sm text-muted">Chat</p>
    </div>
</section>

<section data-panel="notif" data-glyph="N">
    <div class="container" style="padding-top:calc(80px + var(--safe-top));">
        <button data-back="1" class="return-back" aria-label="Fechar notificações">
            <i class="ri-corner-up-right-line"></i>
        </button>
        <p class="ai font-mono text-sm text-muted">Notificações</p>
    </div>
</section>

<?php wp_footer(); ?>
</body>
</html>
