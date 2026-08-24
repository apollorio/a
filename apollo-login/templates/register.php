<?php

/**
 * Apollo Registration Template
 *
 * @package Apollo\Login
 * @since 2.0.0
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// Redirect already-logged-in users — honour redirect_to when present.
if (is_user_logged_in()) {
    $redirect_to = isset($_GET['redirect_to']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ? wp_sanitize_redirect(wp_unslash($_GET['redirect_to']))
        : home_url('/feed');
    wp_safe_redirect($redirect_to);
    exit;
}

// Get configuration (mirrored from login.php — required for apolloAuthConfig).
$auth_config = apply_filters(
    'apollo_auth_config',
    array(
        'ajax_url'             => admin_url('admin-ajax.php'),
        'nonce'                => wp_create_nonce('apollo_auth_nonce'),
        'max_failed_attempts'  => 3,
        'lockout_duration'     => 60,
        'simon_levels'         => 4,
        'reaction_targets'     => 4,
        'redirect_after_login' => home_url('/feed'),
        'terms_url'            => home_url('/termos-e-politica/'),
        'bug_report_url'       => 'https://apollo.rio.br/bug/',
        'show_instagram'       => true,
        'require_cpf'          => true,
    )
);

// Canonical sounds catalog (doc.apollo.rio.br/json/sounds.json).
$sounds_catalog   = \Apollo\Login\apollo_login_get_sounds_catalog();
$available_sounds = \Apollo\Login\apollo_login_sounds_catalog_flat();

// Localize script configuration (required for apolloAuthConfig nonce injection).
$js_config = array(
    'ajaxUrl'            => $auth_config['ajax_url'],
    'nonce'              => $auth_config['nonce'],
    'maxFailedAttempts'  => $auth_config['max_failed_attempts'],
    'lockoutDuration'    => $auth_config['lockout_duration'],
    'simonLevels'        => $auth_config['simon_levels'],
    'reactionTargets'    => $auth_config['reaction_targets'],
    'redirectAfterLogin' => $auth_config['redirect_after_login'],
    'loginUrl'            => \Apollo\Login\apollo_login_canonical_login_url(),
    'registerUrl'        => \Apollo\Login\apollo_login_register_url(),
    'passwordRecoveryUrl' => \Apollo\Login\apollo_login_password_recovery_url(),
    'verifyEmailUrl'     => home_url('/verificar-email/?pending=1'),
    'restUrl'            => rest_url(APOLLO_LOGIN_REST_NAMESPACE),
    'availableSounds'    => $available_sounds,
    'soundsCatalog'      => $sounds_catalog,
    'termsUrl'           => $auth_config['terms_url'],
    'quizTotalStages'    => 4,
    'authPage'           => 'register',
    'telegramRestUrl'    => rest_url('apollo-telegram/v1'),
    'telegramNonce'      => wp_create_nonce('apollo_telegram_verify'),
    'telegramBotUser'    => function_exists('apollo_telegram_bot_username') ? apollo_telegram_bot_username() : 'apolloRio_bot',
    'strings'            => array(
        'loginSuccess'   => __('Acesso autorizado. Redirecionando...', 'apollo-login'),
        'loginFailed'    => __('Credenciais incorretas. Tente novamente.', 'apollo-login'),
        'warningState'   => __('Atenção: última tentativa antes do bloqueio.', 'apollo-login'),
        'lockedOut'      => __('Sistema bloqueado por segurança.', 'apollo-login'),
        'quizComplete'   => __('Teste de aptidão concluído com sucesso!', 'apollo-login'),
        'quizFailed'     => __('Resposta incorreta. Reiniciando pergunta...', 'apollo-login'),
        'patternCorrect' => 'seq-double-16',
        'ethicsCorrect'  => __('É trabalho, renda, a sonoridade e arte favorita de alguem.', 'apollo-login'),
    ),
);

?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
    <?php
    if (function_exists('apollo_render_document_head')) {
        apollo_render_document_head(
            array(
                'title'     => esc_html(get_bloginfo('name')) . ' - Registro',
                'auth_lite' => false,
                'skip_seo'  => true,
                'extra_head' => '<meta name="robots" content="noindex,nofollow">',
            )
        );
    }
    require APOLLO_LOGIN_DIR . 'templates/parts/auth-head.php';
    ?>
</head>

<body data-apollo-page="registre" data-apollo-auth="1" data-state="normal">
<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
<!-- apollo-email: AJAX apollo_register → RegisterHandler → apollo/login/registered + verification_email -->
<?php endif; ?>

    <?php require APOLLO_LOGIN_DIR . 'templates/parts/auth-background.php'; ?>

    <div class="auth-stage">
        <div class="terminal-wrapper tl" id="auth-card">
            <div class="notification-area"></div>

            <?php
            $apollo_auth_page_subtitle = __('Registro de operador', 'apollo-login');
            require APOLLO_LOGIN_DIR . 'templates/parts/new_header.php';
            ?>

            <div class="scroll-area">
                <section id="register-section">
                    <?php require APOLLO_LOGIN_DIR . 'templates/parts/new_register-form.php'; ?>
                </section>
            </div>

            <?php require APOLLO_LOGIN_DIR . 'templates/parts/new_footer.php'; ?>
            <?php require APOLLO_LOGIN_DIR . 'templates/parts/new_aptitude-quiz.php'; ?>
        </div>
    </div>

    <?php
    if (function_exists('apollo_render_report_modal')) {
        apollo_render_report_modal('apolloReportTrigger', 'frontend');
    }
    ?>

    <?php require APOLLO_LOGIN_DIR . 'templates/parts/auth-scripts.php'; ?>


</body>

</html>