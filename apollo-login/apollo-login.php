<?php

/**
 * Plugin Name: Apollo Login
 * Plugin URI: https://apollo.rio.br/plugins/apollo-login
 * Description: Auth: Login, Register, Password Reset, MANDATORY Aptitude Quiz (Pattern, Simon, Ethics, Reaction), URL Protection (Hide My WP native), Rate Limiting
 * Version: 1.0.47
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * License: Proprietary
 * Text Domain: apollo-login
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Network: false
 *
 * @package Apollo\Login
 */

/*
 * ARCH: apollo-login / autenticacao e /acesso
 * ARCH-MANUAL: escrito a mao (2026-09-09). gen-arch-blocks.js recusa
 *   ficheiros dirty no git e 41 de 42 estao dirty. Ver nota em apollo-core.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-login   54 arquivos PHP, 15573 LOC
 * BOOT      plugins_loaded:10 (:101). Alem disso intercepta cedo:
 *           parse_request / wp / template_redirect todos em PHP_INT_MIN.
 * CARGA     E FORCE-LOADED por mu-plugin/force-load-apollo-login.php, que
 *           faz require no topo do ficheiro, ANTES de qualquer hook Apollo
 *           existir. Nenhuma politica de carregamento pode desligar isto.
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * REST      26 rotas (16 leituras publicas) - rever intencao por rota
 * REQUIRES  nenhuma dependencia confirmada
 *
 * RISCO     Classificado HIGH no registry junto com apollo-events e
 *           apollo-membership. Qualquer edicao aqui merece escrutinio
 *           extra, mesmo as que nao parecem de seguranca.
 *
 * NAO FACA
 *   - baixar a prioridade das intercepcoes PHP_INT_MIN sem provar quem
 *     depende de correr antes. Sao a razao de /acesso funcionar.
 *   - assumir que este plugin pode ser desativado para testar: o
 *     force-load ignora active_plugins.
 *   - registar um segundo namespace REST. So apollo/v1.
 *
 * VERIFICAR   node D:/dev/_cos/verify/plugin-audit.js
 */

declare(strict_types=1);

namespace Apollo\Login;

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('APOLLO_LOGIN_VERSION', '1.0.47');
define('APOLLO_LOGIN_FILE', __FILE__);
define('APOLLO_LOGIN_DIR', plugin_dir_path(__FILE__));
define('APOLLO_LOGIN_URL', plugin_dir_url(__FILE__));
define('APOLLO_LOGIN_BASENAME', plugin_basename(__FILE__));

if (defined('APOLLO_LOGIN_BOOTSTRAPPED')) {
    return;
}
define('APOLLO_LOGIN_BOOTSTRAPPED', true);

/**
 * Autoloader
 */
// require_once APOLLO_LOGIN_DIR . 'vendor/autoload.php';

/**
 * Include helper files
 */
require_once APOLLO_LOGIN_DIR . 'includes/constants.php';
require_once APOLLO_LOGIN_DIR . 'includes/functions.php';
require_once APOLLO_LOGIN_DIR . 'includes/logout.php';
require_once APOLLO_LOGIN_DIR . 'includes/sounds-catalog.php';
require_once APOLLO_LOGIN_DIR . 'includes/disable-conflicts.php'; // Prevent apollo-templates conflicts

/**
 * Manual class includes (autoloader disabled)
 */
require_once APOLLO_LOGIN_DIR . 'src/Core/Plugin.php';
require_once APOLLO_LOGIN_DIR . 'src/Auth/LoginHandler.php';
require_once APOLLO_LOGIN_DIR . 'src/Auth/RegisterHandler.php';
require_once APOLLO_LOGIN_DIR . 'src/Auth/PasswordReset.php';
require_once APOLLO_LOGIN_DIR . 'src/Auth/EmailVerification.php';
require_once APOLLO_LOGIN_DIR . 'src/Auth/PendingRegistrationCleanup.php';
require_once APOLLO_LOGIN_DIR . 'src/Quiz/QuizManager.php';
require_once APOLLO_LOGIN_DIR . 'src/Quiz/SimonGame.php';
require_once APOLLO_LOGIN_DIR . 'src/Security/URLRewriter.php';
require_once APOLLO_LOGIN_DIR . 'src/Security/RateLimiter.php';
require_once APOLLO_LOGIN_DIR . 'src/Security/Lockout.php';
require_once APOLLO_LOGIN_DIR . 'src/Security/Firewall.php';
require_once APOLLO_LOGIN_DIR . 'src/Security/SecurityHeaders.php';
require_once APOLLO_LOGIN_DIR . 'src/Security/WPHardening.php';
require_once APOLLO_LOGIN_DIR . 'src/Security/JWTAuth.php';

/**
 * API controllers (loaded for REST route registration)
 */
require_once APOLLO_LOGIN_DIR . 'src/API/AuthController.php';
require_once APOLLO_LOGIN_DIR . 'src/API/QuizController.php';
require_once APOLLO_LOGIN_DIR . 'src/API/SecurityController.php';
require_once APOLLO_LOGIN_DIR . 'src/API/AppAuthController.php';
require_once APOLLO_LOGIN_DIR . 'src/API/ActivityLogController.php';

/**
 * Initialize plugin
 * Priority 10 ensures apollo-core (priority 5) loads first
 */
function apollo_login_init(): void
{
    // Load text domain
    // Temporarily disabled to prevent just-in-time loading issues
    // load_plugin_textdomain(
    // 'apollo-login',
    // false,
    // dirname( APOLLO_LOGIN_BASENAME ) . '/languages/'
    // );

    require_once APOLLO_LOGIN_DIR . 'src/Core/Plugin.php';
    $plugin = Core\Plugin::get_instance();
    $plugin->init();
}
add_action('plugins_loaded', __NAMESPACE__ . '\\apollo_login_init', 10);

/**
 * Suppress textdomain loading notices for this plugin
 */
function apollo_login_suppress_textdomain_notice($message, $error_type): string
{
    if (strpos($message, '_load_textdomain_just_in_time') !== false && strpos($message, 'apollo-login') !== false) {
        return ''; // Suppress this specific notice
    }
    return $message;
}
add_filter('wp_php_error_message', __NAMESPACE__ . '\\apollo_login_suppress_textdomain_notice', 10, 2);

/**
 * Register query vars early - before init
 */
function apollo_login_register_query_vars($vars)
{
    $vars[] = 'apollo_login_page';
    return $vars;
}
add_filter('query_vars', __NAMESPACE__ . '\\apollo_login_register_query_vars');

/**
 * Handle virtual pages directly via parse_request
 */
function apollo_login_parse_request($wp)
{
    $path          = apollo_login_normalized_request_path();
    $virtual_pages = apollo_login_virtual_page_map();

    if (isset($virtual_pages[$path])) {
        $wp->query_vars['apollo_login_page'] = $virtual_pages[$path];
        apollo_login_session_log(
            'apollo-login.php:parse_request',
            'virtual_page_matched',
            array(
                'path'  => $path,
                'page'  => $virtual_pages[$path],
                'uri'   => isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '',
            ),
            'H1'
        );
        return;
    }

    apollo_login_session_log(
        'apollo-login.php:parse_request',
        'no_virtual_match',
        array('path' => $path),
        'H1'
    );
}
add_action('parse_request', __NAMESPACE__ . '\\apollo_login_parse_request', PHP_INT_MIN);

/**
 * Belt-and-suspenders: set query var after WP routing when rewrites are stale.
 */
function apollo_login_wp_fallback(): void
{
    if (! empty(get_query_var('apollo_login_page', ''))) {
        return;
    }

    $path = apollo_login_normalized_request_path();
    $map  = apollo_login_virtual_page_map();

    if (! isset($map[ $path ])) {
        return;
    }

    set_query_var('apollo_login_page', $map[ $path ]);
    apollo_login_session_log(
        'apollo-login.php:wp_fallback',
        'virtual_page_set_on_wp',
        array('path' => $path, 'page' => $map[ $path ]),
        'H1'
    );
}
add_action('wp', __NAMESPACE__ . '\\apollo_login_wp_fallback', PHP_INT_MIN);

/**
 * Serve template for virtual pages via template_redirect.
 * Priority 1 fires before any other plugin (including apollo-templates P10).
 */
function apollo_login_template_redirect(): void
{
    $page = get_query_var('apollo_login_page', '');

    // Fallback when rewrite rules are stale but URL matches login virtual slugs.
    if (empty($page)) {
        $path = apollo_login_normalized_request_path();
        $map  = apollo_login_virtual_page_map();
        if (isset($map[ $path ])) {
            $page = $map[ $path ];
            set_query_var('apollo_login_page', $page);
            apollo_login_session_log(
                'apollo-login.php:template_redirect',
                'fallback_virtual_page_set',
                array('path' => $path, 'page' => $page),
                'H1'
            );
        }
    }

    if (empty($page)) {
        $path = apollo_login_normalized_request_path();
        if (in_array($path, array('acesso', 'access', 'acessar', 'entrar'), true)) {
            apollo_login_session_log(
                'apollo-login.php:template_redirect',
                'auth_path_unclaimed',
                array(
                    'path'     => $path,
                    'is_404'   => function_exists('is_404') ? is_404() : null,
                    'pagename' => get_query_var('pagename', ''),
                ),
                'H1'
            );
        }
        return;
    }

    // /acesso?action=logout&redirect_to=…&_wpnonce=… (rewritten wp_logout_url)
    $action = isset($_GET['action']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ? sanitize_key(wp_unslash($_GET['action']))
        : '';
    if ('login' === $page && 'logout' === $action) {
        apollo_login_process_logout_request();
    }

    // /sair — logout then honour redirect_to, else site home.
    if ('logout' === $page) {
        apollo_login_process_logout_request();
    }

    $template_file = APOLLO_LOGIN_DIR . 'templates/' . $page . '.php';

    if (file_exists($template_file)) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
        nocache_headers();
        if (! headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        apollo_login_session_log(
            'apollo-login.php:template_redirect',
            'serving_auth_template',
            array('page' => $page, 'template' => $template_file),
            'H1'
        );

        include $template_file;
        exit;
    }

    apollo_login_session_log(
        'apollo-login.php:template_redirect',
        'template_missing',
        array('page' => $page, 'expected' => $template_file),
        'H1'
    );
}
// Absolute earliest priority — login must win the race against any other
// plugin's template_redirect hook, including the MU-level error handler that
// can otherwise intercept and kill unclaimed-looking routes with a blank body.
add_action('template_redirect', __NAMESPACE__ . '\\apollo_login_template_redirect', PHP_INT_MIN);

/**
 * Strict-mode error handler must not steal auth virtual routes (apollo-error-handler MU).
 */
add_filter(
    'apollo/error/should_intercept',
    static function (bool $should, string $uri): bool {
        if (! empty(get_query_var('apollo_login_page', ''))) {
            return false;
        }

        $path = apollo_login_normalized_request_path();
        $auth = array_keys(apollo_login_virtual_page_map());

        if (in_array($path, $auth, true)) {
            return false;
        }

        $segment = explode('/', $path, 2)[0];
        if (in_array($segment, $auth, true)) {
            return false;
        }

        return $should;
    },
    PHP_INT_MAX,
    2
);
function apollo_login_activate(): void
{
    require_once APOLLO_LOGIN_DIR . 'includes/activation.php';
    Core\Activation::activate();
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\\apollo_login_activate');

/**
 * Deactivation hook
 */
function apollo_login_deactivate(): void
{
    // Cleanup temporary data, flush rewrite rules, etc.
    flush_rewrite_rules(false);
}
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\\apollo_login_deactivate');
