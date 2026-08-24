<?php
/**
 * Funções helper do Apollo Local — ponto de entrada
 *
 * Carrega os sub-arquivos especializados. Mantém apenas as funções
 * globais indivisíveis (plugin instance e opções).
 *
 * @package Apollo\Local
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

// ── Sub-módulos de funções ────────────────────────────────────────────────────
require_once __DIR__ . '/functions-address.php';
require_once __DIR__ . '/functions-events.php';
require_once __DIR__ . '/functions-geo.php';

// ── Core helpers ──────────────────────────────────────────────────────────────

/**
 * Retorna instância do plugin.
 */
function apollo_local(): ?\Apollo\Local\Plugin {
return $GLOBALS['apollo_local'] ?? null;
}

/**
 * Retorna opção do plugin.
 */
function apollo_local_option( string $key, $default = null ) {
$settings = get_option( 'apollo_local_settings', array() );
return $settings[ $key ] ?? $default;
}

/**
 * Limpa caches ao salvar posts relevantes.
 */
function apollo_local_flush_cache( int $post_id ): void {
if ( get_post_type( $post_id ) === APOLLO_LOCAL_CPT ) {
wp_cache_delete( 'local_upcoming_count_' . $post_id, APOLLO_LOCAL_CACHE_GROUP );
wp_cache_delete( 'local_event_count_' . $post_id, APOLLO_LOCAL_CACHE_GROUP );
}

if ( get_post_type( $post_id ) === 'event' ) {
$local_id = (int) get_post_meta( $post_id, '_event_local_id', true );
if ( $local_id ) {
wp_cache_delete( 'local_upcoming_count_' . $local_id, APOLLO_LOCAL_CACHE_GROUP );
wp_cache_delete( 'local_event_count_' . $local_id, APOLLO_LOCAL_CACHE_GROUP );
wp_cache_delete( 'local_events_' . $local_id . '_5', APOLLO_LOCAL_CACHE_GROUP );
}
}
}
add_action( 'save_post', 'apollo_local_flush_cache' );
