<?php

/**
 * Apollo Sign — canonical document head + signing assets.
 *
 * Variables expected from parent:
 *   $doc_title  (string) — Browser tab title
 *
 * @package Apollo\Sign
 */

if (! defined('ABSPATH')) {
    exit;
}

$doc_title = $doc_title ?? 'Apollo Docs — Assinatura Digital';

ob_start();
?>
    <meta name="robots" content="noindex,nofollow">
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => $doc_title,
            'extra_head' => $extra_head,
            'skip_seo'   => true,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
