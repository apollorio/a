<?php

/**
 * Apollo Docs — Frontend File Manager (Canvas Template)
 *
 * Virtual page at /documentos for logged-in users.
 * Uses wp_head/wp_footer (Canvas pattern) + Apollo CDN.
 *
 * @package Apollo\Docs
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$cdn_url = defined('APOLLO_CDN_URL') ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/';

ob_start();
?>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            background: var(--bg, #0a0a0a);
            color: var(--ink, #fafafa);
            font-family: var(--ff, 'Space Grotesk', sans-serif);
            min-height: 100vh;
            min-height: 100dvh;
        }

        /* Push content below fixed navbar */
        .apollo-docs-page {
            padding-top: 60px;
            min-height: 100vh;
            min-height: 100dvh;
        }

        /* Full-width file manager */
        .apollo-docs-page .apollo-fm {
            max-width: 1400px;
            margin: 0 auto;
            border-radius: 0;
            border-left: 1px solid var(--brd, rgba(var(--rgb-t), .06));
            border-right: 1px solid var(--brd, rgba(var(--rgb-t), .06));
            min-height: calc(100vh - 60px);
        }
    </style>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => 'Documentos — Apollo',
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

<body <?php body_class('apollo-docs-frontend'); ?>>

    <?php apollo_render_navbar(); ?>

    <main class="apollo-docs-page">
        <?php require APOLLO_DOCS_DIR . 'templates/documents.php'; ?>
    </main>

<?php
if (function_exists('apollo_render_document_close')) {
    apollo_render_document_close();
} else {
    ?>
</body>

</html>
    <?php
}