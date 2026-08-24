<?php
/**
 * Criar Comuna — /criar-comuna  ·  Blank Canvas Apollo+
 *
 * Mount point only: opens the shared shell, renders the create layout, closes.
 * Legacy URL /criar-grupo permanently redirects here (Plugin::redirect_legacy_create_route).
 *
 * Registry: { slug: "criar-comuna", template: "create-group.php", type: "virtual", auth: true }
 *
 * @package Apollo\Groups
 * @since   1.1.2
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/acesso/' ) );
	exit;
}

$create_parts = __DIR__ . '/parts/create/';

ob_start();
require $create_parts . 'styles.php';
$create_head = ob_get_clean();

if ( function_exists( 'apollo_plus_open' ) ) {
	apollo_plus_open(
		array(
			'title'      => 'Criar comuna — Apollo::Rio',
			'extra_head' => $create_head,
			'screen'     => 'criar-comuna',
		)
	);
	require $create_parts . 'layout.php';
	apollo_plus_close();
	return;
}

// ── Legacy fallback: apollo-templates inactive ──
if ( function_exists( 'apollo_render_document_open' ) ) {
	apollo_render_document_open(
		array(
			'title'      => 'Criar comuna — Apollo::Rio',
			'extra_head' => $create_head,
		)
	);
}
echo "</head>\n<body>\n";
if ( function_exists( 'apollo_render_navbar' ) ) {
	apollo_render_navbar();
}
echo '<main class="ax-main" data-screen="criar-comuna">';
require $create_parts . 'layout.php';
echo '</main>';
if ( function_exists( 'apollo_render_document_close' ) ) {
	apollo_render_document_close();
} else {
	echo '</body></html>';
}
