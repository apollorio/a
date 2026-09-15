<?php
/**
 * Plugin Name: Apollo Pane Engine
 * Plugin URI:  https://apollo.rio.br
 * Description: Isolated test harness — read-only "aquarium bubble" that probes all 220+ Apollo REST endpoints via a side-by-slide panel UI at /casa.
 * Version:     2.0.1
 * Author:      Apollo Team
 * Author URI:  https://apollo.rio.br
 * License:     GPL-2.0-or-later
 * Text Domain: apollo-pane-engine
 * Requires PHP: 8.1
 * Requires at least: 6.4
 *
 * @package Apollo\PaneEngine
 */

/*
 * ARCH: apollo-pane-engine
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-pane-engine   7 arquivos PHP, 1693 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        não emite HTML
 * META      5 chaves tocadas, 3 SEM definição governante
 * REST      apollo/v1 — 5 rotas (0 públicas)
 * REQUIRES  apollo-core
 *
 * NÃO FAÇA
 *   - registrar CPT direto: apollo-core é o dono do init:5.
 *     Fallback do owner só com post_type_exists().
 *   - gravar meta de outro domínio (hoje 227 chaves não têm dono).
 *   - registrar um segundo namespace REST. Só apollo/v1.
 *     Já existe um namespace fora do padrão no apollo-telegram.
 *   - add_shortcode() sem shortcode_exists(): o último a registrar
 *     vence em silêncio e quem roda vira acidente de ordem de carga.
 *
 * VERIFICAR   node D:/dev/_cos/verify/plugin-audit.js
 */

if (! defined('ABSPATH')) {
    exit;
}

/* ── Constants ────────────────────────────────────────────────────── */
define('APOLLO_PANE_ENGINE_VERSION', '2.0.1');
define('APOLLO_PANE_ENGINE_PATH', plugin_dir_path(__FILE__));
define('APOLLO_PANE_ENGINE_URL', plugin_dir_url(__FILE__));
define('APOLLO_PANE_ENGINE_MANIFEST', APOLLO_PANE_ENGINE_PATH . 'pane-engine-casa.json');

/* ── Includes ─────────────────────────────────────────────────────── */
require_once APOLLO_PANE_ENGINE_PATH . 'includes/functions.php';
require_once APOLLO_PANE_ENGINE_PATH . 'includes/fragment-helper.php';
require_once APOLLO_PANE_ENGINE_PATH . 'includes/section-renderer.php';

/* ── Activation: flush rewrites ───────────────────────────────────── */
register_activation_hook(__FILE__, function (): void {
    apollo_pane_engine_add_rewrite_rules();
    flush_rewrite_rules();
    update_option('apollo_pane_engine_version', APOLLO_PANE_ENGINE_VERSION);
});

/* ── Deactivation: flush rewrites ─────────────────────────────────── */
register_deactivation_hook(__FILE__, function (): void {
    flush_rewrite_rules();
});
