<?php

/**
 * Template Part: Head — Blank Canvas <head> for /feed
 *
 * Canonical Apollo document head + explore/navbar assets.
 *
 * @package Apollo\Social
 * @since   3.0.0
 */

defined('ABSPATH') || exit;

ob_start();
?>
    <?php if (defined('APOLLO_TEMPLATES_URL') && defined('APOLLO_TEMPLATES_VERSION')) : ?>
        <link rel="stylesheet" href="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/css/navbar.css'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>">
        <script src="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/js/navbar.js'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>" defer></script>
    <?php endif; ?>
    <?php if (defined('APOLLO_SOCIAL_URL') && defined('APOLLO_SOCIAL_VERSION')) : ?>
        <link rel="stylesheet" href="<?php echo esc_url(APOLLO_SOCIAL_URL . 'assets/css/explore.css'); ?>?v=<?php echo esc_attr(APOLLO_SOCIAL_VERSION); ?>">
    <?php endif; ?>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => 'Apollo · Feed',
            'extra_head' => $extra_head,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>
