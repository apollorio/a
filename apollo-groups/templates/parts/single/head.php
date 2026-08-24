<?php

/**
 * Single Part — Head
 *
 * Canonical Apollo document head + single-group assets.
 * Expects: $group_name (string)
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined('ABSPATH') || exit;

$groups_css_url = defined('APOLLO_GROUPS_URL')
    ? APOLLO_GROUPS_URL . 'assets/css/single-group.css'
    : plugin_dir_url(dirname(__DIR__)) . 'assets/css/single-group.css';
$ver = defined('APOLLO_VERSION') ? APOLLO_VERSION : '3.0.0';

ob_start();
?>
    <?php if (defined('APOLLO_TEMPLATES_URL') && defined('APOLLO_TEMPLATES_VERSION')) : ?>
        <link rel="stylesheet" href="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/css/navbar.css'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>">
        <script src="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/js/navbar.js'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>" defer></script>
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo esc_url($groups_css_url); ?>?v=<?php echo esc_attr($ver); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Shrikhand&display=swap" rel="stylesheet">
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => 'Apollo · ' . ($group_name ?? 'Grupo'),
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

<body>
