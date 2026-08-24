<?php

/**
 * Apollo Login Template (Standalone)
 * Self-contained /acesso page - NO apollo-templates dependency
 *
 * @package Apollo\Login
 * @since 2.0.0
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

nocache_headers();

// Logout is handled in apollo_login_template_redirect() BEFORE this template loads.
// Do not redirect logged-in users away when this is a logout request.
$apollo_login_action = isset($_GET['action']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    ? sanitize_key(wp_unslash($_GET['action']))
    : '';

// Redirect already-logged-in users — honour redirect_to when present.
if (is_user_logged_in() && 'logout' !== $apollo_login_action) {
    $redirect_to = isset($_GET['redirect_to']) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ? wp_sanitize_redirect(wp_unslash($_GET['redirect_to']))
        : home_url('/feed');
    wp_safe_redirect($redirect_to);
    exit;
}

// Get configuration.
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

// Get available sounds/genres for registration from apollo-core GLOBAL BRIDGE taxonomy
$available_sounds = array();
if (taxonomy_exists('sound')) {
    $sound_terms = get_terms(
        array(
            'taxonomy'   => 'sound',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    if (! is_wp_error($sound_terms) && ! empty($sound_terms)) {
        foreach ($sound_terms as $term) {
            $available_sounds[$term->slug] = $term->name;
        }
    }
}

// Fallback if taxonomy not available yet
if (empty($available_sounds)) {
    $available_sounds = apply_filters(
        'apollo_registration_sounds',
        array(
            // Techno & Derivatives
            'techno'                  => 'Techno',
            'detroit-techno'          => 'Detroit Techno',
            'minimal-techno'          => 'Minimal Techno',
            'acid-techno'             => 'Acid Techno',
            'industrial-techno'       => 'Industrial Techno',
            'schranz'                 => 'Schranz',
            'tekno'                   => 'Tekno',

            // House & Variants
            'house'                   => 'House',
            'deep-house'              => 'Deep House',
            'chicago-house'           => 'Chicago House',
            'progressive-house'       => 'Progressive House',
            'electro-house'           => 'Electro House',
            'funky-house'             => 'Funky House',
            'tech-house'              => 'Tech House',
            'micro-house'             => 'Micro House',
            'future-house'            => 'Future House',
            'tropical-house'          => 'Tropical House',

            // Trance & Psy
            'trance'                  => 'Trance',
            'progressive-trance'      => 'Progressive Trance',
            'uplifting-trance'        => 'Uplifting Trance',
            'psytrance'               => 'Psytrance',
            'full-on'                 => 'Full On',
            'goa-trance'              => 'Goa Trance',
            'dark-psy'                => 'Dark Psy',
            'forest-psy'              => 'Forest Psy',

            // Bass Music
            'drum-bass'               => 'Drum & Bass',
            'jungle'                  => 'Jungle',
            'liquid-funk'             => 'Liquid Funk',
            'neurofunk'               => 'Neurofunk',
            'dubstep'                 => 'Dubstep',
            'brostep'                 => 'Brostep',
            'post-dubstep'            => 'Post Dubstep',
            'trap'                    => 'Trap',
            'future-bass'             => 'Future Bass',

            // Breakbeat & Funk
            'funk'                    => 'Funk',
            'breakbeat'               => 'Breakbeat',
            'big-beat'                => 'Big Beat',
            'nu-funk'                 => 'Nu Funk',
            'broken-beat'             => 'Broken Beat',

            // Ambient & Experimental
            'ambient'                 => 'Ambient',
            'intelligent-dance-music' => 'Intelligent Dance Music',
            'glitch'                  => 'Glitch',
            'experimental'            => 'Experimental',
            'noise'                   => 'Noise',

            // Hard & Extreme
            'hard'                    => 'Hard',
            'hardcore'                => 'Hardcore',
            'hardstyle'               => 'Hardstyle',
            'gabber'                  => 'Gabber',
            'speedcore'               => 'Speedcore',

            // Other Electronic
            'electro'                 => 'Electro',
            'electroclash'            => 'Electroclash',
            'synthwave'               => 'Synthwave',
            'retrowave'               => 'Retrowave',
            'outrun'                  => 'Outrun',
            'vaporwave'               => 'Vaporwave',
            'chillwave'               => 'Chillwave',
        )
    );
}

// Localize script configuration
$js_config = array(
    'ajaxUrl'            => $auth_config['ajax_url'],
    'nonce'              => $auth_config['nonce'],
    'maxFailedAttempts'  => $auth_config['max_failed_attempts'],
    'lockoutDuration'    => $auth_config['lockout_duration'],
    'simonLevels'        => $auth_config['simon_levels'],
    'reactionTargets'    => $auth_config['reaction_targets'],
    'redirectAfterLogin' => $auth_config['redirect_after_login'],
    'restUrl'            => rest_url(APOLLO_LOGIN_REST_NAMESPACE),
    'authPage'           => 'login',
    'loginUrl'            => \Apollo\Login\apollo_login_canonical_login_url(),
    'registerUrl'        => \Apollo\Login\apollo_login_register_url(),
    'passwordRecoveryUrl' => \Apollo\Login\apollo_login_password_recovery_url(),
    'quizTotalStages'    => 4,
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
                'title'     => esc_html__('Apollo::Rio - Terminal de Acesso', 'apollo-login'),
                'auth_lite' => false,
                'skip_seo'  => true,
            )
        );
    }
    require APOLLO_LOGIN_DIR . 'templates/parts/auth-head.php';
    ?>
</head>

<body class="apollo-no-tooltips" data-apollo-page="acesso" data-apollo-auth="1" data-state="normal">

    <?php require APOLLO_LOGIN_DIR . 'templates/parts/auth-background.php'; ?>

    <div class="auth-stage">
        <div class="terminal-wrapper tl" id="auth-card">
            <div class="notification-area"></div>

            <?php require APOLLO_LOGIN_DIR . 'templates/parts/new_header.php'; ?>

            <div class="scroll-area">
                <section id="login-section">
                    <?php require APOLLO_LOGIN_DIR . 'templates/parts/new_login-form.php'; ?>
                </section>
            </div>

            <?php require APOLLO_LOGIN_DIR . 'templates/parts/new_footer.php'; ?>
            <?php require APOLLO_LOGIN_DIR . 'templates/parts/new_lockout-overlay.php'; ?>
            <?php require APOLLO_LOGIN_DIR . 'templates/parts/new_aptitude-quiz.php'; ?>
        </div>
    </div>

    <?php require APOLLO_LOGIN_DIR . 'templates/parts/password-recovery-overlay.php'; ?>

    <?php
    if (function_exists('apollo_render_report_modal')) {
        apollo_render_report_modal('apolloLoginBugReport', 'frontend');
    }
    ?>

    <?php require APOLLO_LOGIN_DIR . 'templates/parts/auth-scripts.php'; ?>


</body>

</html>