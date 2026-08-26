<?php
/**
 * Deactivate apollo-templates plugin
 */
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/wp-load.php';

if ( ! function_exists( 'deactivate_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$plugin = 'apollo-templates/apollo-templates.php';

if ( is_plugin_active( $plugin ) ) {
	deactivate_plugins( $plugin );
	echo '<h1 style="color: green;">✓ Plugin apollo-templates desativado com sucesso!</h1>';
	echo '<p>Agora teste o login em: <a href="/acesso/">http://localhost:10004/acesso/</a></p>';
	echo '<script>setTimeout(() => window.location.href = "/acesso/", 2000);</script>';
} else {
	echo '<h1 style="color: orange;">⚠ Plugin apollo-templates já está desativado</h1>';
	echo '<p>Teste o login em: <a href="/acesso/">http://localhost:10004/acesso/</a></p>';
}
