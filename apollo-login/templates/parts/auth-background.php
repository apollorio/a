<?php
/**
 * Full-viewport background video for blank-canvas auth pages (/acesso, /registre).
 *
 * @package Apollo\Login
 */

if (! defined('ABSPATH')) {
    exit;
}

$sources = apply_filters(
    'apollo_login_auth_bg_video_sources',
    array(
        array(
            'src'  => 'https://assets.apollo.rio.br/vid/v2.mp4',
            'type' => 'video/mp4',
        ),
        array(
            'src'  => 'https://assets.apollo.rio.br/vid/v2.webm',
            'type' => 'video/webm',
        ),
    )
);

$poster = apply_filters('apollo_login_auth_bg_video_poster', '');
?>
<div class="acesso-bg" aria-hidden="true">
    <video
        class="acesso-bg__video"
        id="acessoBgVid"
        autoplay
        muted
        loop
        playsinline
        preload="auto"
        <?php if ($poster) : ?>
        poster="<?php echo esc_url($poster); ?>"
        <?php endif; ?>
    >
        <?php foreach ($sources as $source) : ?>
            <?php
            if (empty($source['src']) || empty($source['type'])) {
                continue;
            }
            ?>
            <source src="<?php echo esc_url($source['src']); ?>" type="<?php echo esc_attr($source['type']); ?>">
        <?php endforeach; ?>
    </video>
    <div class="acesso-bg__overlay"></div>
</div>
<div class="grid-overlay" aria-hidden="true"></div>
<div class="noise-overlay" aria-hidden="true"></div>
