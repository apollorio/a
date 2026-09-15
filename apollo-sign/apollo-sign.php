<?php

/**
 * Plugin Name:       Apollo Sign
 * Plugin URI:        https://apollo.rio.br
 * Description:       Digital signature module — ICP-Brasil compliant certificate signing, PKCS7, audit trail, verification.
 * Version:           1.2.1
 * Requires PHP:      8.1
 * Requires at least: 6.4
 * Author:            Apollo Platform
 * Author URI:        https://apollo.rio.br
 * License:           GPL-2.0-or-later
 * Text Domain:       apollo-sign
 * Domain Path:       /languages
 *
 * @package Apollo\Sign
 */

/*
 * ARCH: apollo-sign
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-sign   39 arquivos PHP, 7939 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (2); chrome é do apollo-templates
 * META      9 chaves tocadas, 7 SEM definição governante
 * REST      namespace não literal no código — 10 rotas (1 públicas)
 * REQUIRES  apollo-core, apollo-email
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

/* ── Constants ─────────────────────────────────────────────── */
define('APOLLO_SIGN_VERSION', '1.2.1');
define('APOLLO_SIGN_DB_VERSION', 4);
define('APOLLO_SIGN_FILE', __FILE__);
define('APOLLO_SIGN_DIR', plugin_dir_path(__FILE__));
define('APOLLO_SIGN_URL', plugin_dir_url(__FILE__));
define('APOLLO_SIGN_BASENAME', plugin_basename(__FILE__));

/* ── PSR-4 Autoloader ──────────────────────────────────────── */
spl_autoload_register(
    function (string $class): void {
        $prefix   = 'Apollo\\Sign\\';
        $base_dir = APOLLO_SIGN_DIR . 'src/';
        $len      = strlen($prefix);

        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relative = substr($class, $len);
        $file     = $base_dir . str_replace('\\', '/', $relative) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
);

/* ── Activation / Deactivation ─────────────────────────────── */
register_activation_hook(
    __FILE__,
    function (): void {
        if (! extension_loaded('openssl')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                '<strong>Apollo Sign</strong> requer a extensão PHP <code>openssl</code> habilitada para funcionar.<br>
             Isso é <strong>obrigatório</strong> para conformidade com ICP-Brasil.',
                'Requisito Ausente',
                array('back_link' => true)
            );
        }

        Apollo\Sign\Database::install();
        Apollo\Sign\Storage::init_directories();
        flush_rewrite_rules();
    }
);

register_deactivation_hook(
    __FILE__,
    function (): void {
        flush_rewrite_rules();
    }
);

/* ── Bootstrap ─────────────────────────────────────────────── */
add_action(
    'plugins_loaded',
    function (): void {
        /* Hard dependency: apollo-core */
        if (! defined('APOLLO_CORE_VERSION')) {
            add_action(
                'admin_notices',
                function (): void {
                    echo '<div class="notice notice-error"><p><strong>Apollo Sign</strong> requer o plugin <code>apollo-core</code> ativo.</p></div>';
                }
            );
            return;
        }

        /* Hard dependency: openssl */
        if (! extension_loaded('openssl')) {
            add_action(
                'admin_notices',
                function (): void {
                    echo '<div class="notice notice-error"><p><strong>Apollo Sign</strong>: extensão <code>openssl</code> não encontrada. O plugin <strong>não pode funcionar</strong> sem ela (ICP-Brasil).</p></div>';
                }
            );
            return;
        }

        Apollo\Sign\Plugin::get_instance()->init();

        /* Run DB migrations if needed */
        $installed = (int) get_option( 'apollo_sign_db_version', 0 );
        if ( $installed < APOLLO_SIGN_DB_VERSION ) {
            Apollo\Sign\Database::upgrade( $installed );
            update_option( 'apollo_sign_db_version', APOLLO_SIGN_DB_VERSION );
        }
    },
    16
);
