<?php

/**
 * Plugin Name: Apollo Fav
 * Plugin URI:  https://apollo.rio.br
 * Description: Sistema universal de favoritos para TODOS os CPTs do Apollo. Tabela própria apollo_favs para lookups de alta performance. Integração com notificações inteligentes (apollo-notif) e perfil do usuário.
 * Version:     1.0.1
 * Author:      Apollo RIO
 * Author URI:  https://apollo.rio.br
 * Text Domain: apollo-fav
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.4
 * License:     GPL-2.0+
 *
 * @package Apollo\Fav
 *
 * COMPLIANCE: apollo-registry.json
 * - Namespace: Apollo\Fav
 * - Layer: L3_social
 * - Priority: 3
 * - Table: apollo_favs
 * - Depends: apollo-core
 * - FORBIDDEN: interesse, interessado, interest, bookmark
 */

/*
 * ARCH: apollo-fav
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-fav   11 arquivos PHP, 4074 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (22); chrome é do apollo-templates
 * META      9 chaves tocadas, 2 SEM definição governante
 * REST      namespace não literal no código — 5 rotas (1 públicas)
 * REQUIRES  apollo-notif
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

declare(strict_types=1);

// Impede acesso direto
if (! defined('ABSPATH')) {
    exit;
}

// ─────────────────────────────────────────────────────────────
// Constantes do plugin — padrão Apollo
// ─────────────────────────────────────────────────────────────
define('APOLLO_FAV_VERSION', '1.0.1');
define('APOLLO_FAV_PATH', plugin_dir_path(__FILE__));
define('APOLLO_FAV_URL', plugin_dir_url(__FILE__));
define('APOLLO_FAV_FILE', __FILE__);
define('APOLLO_FAV_TABLE', 'apollo_favs');

// ─────────────────────────────────────────────────────────────
// Autoloader de classes — includes/class-*.php
// ─────────────────────────────────────────────────────────────
spl_autoload_register(
    function (string $class): void {
        $prefix   = 'Apollo\\Fav\\';
        $base_dir = APOLLO_FAV_PATH . 'includes/';

        if (strpos($class, $prefix) !== 0) {
            return;
        }

        // Apollo\Fav\CBX_Bridge → class-cbx-bridge.php
        $relative = substr($class, strlen($prefix));
        $file     = $base_dir . 'class-' . strtolower(str_replace(array('\\', '_'), array('/', '-'), $relative)) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
);

// ─────────────────────────────────────────────────────────────
// Ativação — cria tabela apollo_favs
// ─────────────────────────────────────────────────────────────
register_activation_hook(
    __FILE__,
    function (): void {
        global $wpdb;

        $table_name      = $wpdb->prefix . APOLLO_FAV_TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        // Tabela de alta performance para relações user → post
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
        post_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
        post_type   VARCHAR(60)     NOT NULL DEFAULT 'post',
        created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY  uk_user_post (user_id, post_id),
        KEY         idx_post_id (post_id),
        KEY         idx_post_type (post_type),
        KEY         idx_user_type (user_id, post_type),
        KEY         idx_created (created_at)
    ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Flag de versão para futuras migrações
        update_option('apollo_fav_db_version', APOLLO_FAV_VERSION);

        // Limpa rewrite rules
        flush_rewrite_rules();
    }
);

// ─────────────────────────────────────────────────────────────
// Desativação
// ─────────────────────────────────────────────────────────────
register_deactivation_hook(
    __FILE__,
    function (): void {
        flush_rewrite_rules();
    }
);

// ─────────────────────────────────────────────────────────────
// Verificação de dependência (apollo-core)
// ─────────────────────────────────────────────────────────────
add_action(
    'admin_init',
    function (): void {
        if (! is_plugin_active('apollo-core/apollo-core.php')) {
            add_action(
                'admin_notices',
                function (): void {
                    echo '<div class="notice notice-error"><p>';
                    echo '<strong>Apollo Fav:</strong> Requer o plugin <code>apollo-core</code> ativo.';
                    echo '</p></div>';
                }
            );
        }
    }
);

// ─────────────────────────────────────────────────────────────
// Inicialização principal — carrega todos os módulos
// ─────────────────────────────────────────────────────────────
add_action(
    'plugins_loaded',
    function (): void {

        // Carrega funções globais (API pública)
        require_once APOLLO_FAV_PATH . 'includes/functions.php';

        // Módulo 1: Bridge CBX — motor de favoritos
        $bridge = new Apollo\Fav\CBX_Bridge();
        $bridge->init();

        // Módulo 2: Triggers de notificação inteligente
        $notif = new Apollo\Fav\Notif_Triggers();
        $notif->init();

        // Módulo 3: Dashboard do usuário (painel/favoritos)
        $dashboard = new Apollo\Fav\Dashboard();
        $dashboard->init();

        // Módulo 4: Integração com perfil (/id/username)
        $profile = new Apollo\Fav\Profile_Integration();
        $profile->init();

        // Módulo 5: Merge de estatísticas (ponte para apollo-statistics)
        $stats = new Apollo\Fav\Statistics_Merge();
        $stats->init();

        // Módulo 6: Fav Ranking (migrated from apollo-shortcodes)
        Apollo\Fav\Fav_Ranking::init();

        // Módulo 7: Fav Sound Dashboard (migrated from apollo-shortcodes)
        $fav_sound = new Apollo\Fav\Fav_Sound_Dashboard();
        $fav_sound->init();

        // Registra endpoints REST API — namespace apollo/v1 conforme registry
        add_action('rest_api_init', array(new Apollo\Fav\REST_Controller(), 'register_routes'));
    },
    20
); // Priority 20 — depois do apollo-core (priority 0)

// ─────────────────────────────────────────────────────────────
// Enqueue de assets frontend (botão coração + CSS/JS)
// ─────────────────────────────────────────────────────────────
add_action(
    'wp_enqueue_scripts',
    function (): void {
        /**
         * Controls whether Apollo Fav assets (CSS/JS) are loaded on the current page.
         * Defaults to true on singular posts, pages, and front page where fav buttons
         * are most commonly rendered. Override via filter for custom conditions.
         *
         * @param bool $load Whether to load fav assets. Default true on singular/front.
         */
        if (! apply_filters('apollo_fav_enqueue_assets', is_singular() || is_front_page() || is_page())) {
            return;
        }

        // CSS do botão Apollo Heart
        wp_enqueue_style(
            'apollo-fav',
            APOLLO_FAV_URL . 'assets/css/apollo-fav.css',
            array(),
            APOLLO_FAV_VERSION
        );

        // JS do toggle de favorito (AJAX)
        wp_enqueue_script(
            'apollo-fav',
            APOLLO_FAV_URL . 'assets/js/apollo-fav.js',
            array('jquery'),
            APOLLO_FAV_VERSION,
            true
        );

        // Passa dados para o JS
        wp_localize_script(
            'apollo-fav',
            'apolloFav',
            array(
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'restUrl'   => rest_url('apollo/v1/favs'),
                'nonce'     => wp_create_nonce('apollo_fav_nonce'),
                'restNonce' => wp_create_nonce('wp_rest'),
                'logged'    => is_user_logged_in(),
                'i18n'      => array(
                    'added'   => __('Adicionado aos favoritos!', 'apollo-fav'),
                    'removed' => __('Removido dos favoritos.', 'apollo-fav'),
                    'login'   => __('Faça login para favoritar.', 'apollo-fav'),
                    'error'   => __('Erro ao processar favorito.', 'apollo-fav'),
                ),
            )
        );
    }
);
