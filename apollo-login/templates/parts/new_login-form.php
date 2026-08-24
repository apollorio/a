<?php

/**
 * ================================================================================
 * APOLLO AUTH - Login Form Template Part
 * ================================================================================
 * Displays the login form with username/email and password fields.
 *
 * @package Apollo\Login
 * @since 1.0.0
 *
 * PLACEHOLDERS:
 * - {{username_label}} - Label for username field
 * - {{password_label}} - Label for password field
 * - {{remember_label}} - Label for remember me toggle
 * - {{login_button}} - Submit button text
 * - {{forgot_password_text}} - Forgot password link text
 * - {{register_text}} - Register link text
 *
 * Status/flavor-text lines removed (minimalism pass) — clean card, no filler copy.
 * ================================================================================
 */

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// Get URLs from config.
$bug_report_url      = '#';
$bug_report_is_modal = true;
?>

<!-- Login Form -->
<form id="login-form" method="post" autocomplete="on"
    action="<?php echo esc_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : home_url()); ?>">

    <?php wp_nonce_field('apollo_auth_nonce', 'nonce'); ?>

    <!-- Redirect field -->
    <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url()); ?>">

    <!-- Autofill targets the real fields below (#log / #pwd). No decoy trap:
         mobile password managers must fill the visible inputs the handler reads. -->

    <!-- Username/Email/CPF/Passport Field with Auto-Detection -->
    <div class="input-group">
        <div class="input-icon-wrap" id="login-doc-wrap">
            <span class="input-prefix" id="login-doc-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
            <input type="text" id="log" name="apollo_log" value="" placeholder="E-mail, usuário, CPF ou passaporte"
                class="apollo-input" autocomplete="username" autocapitalize="off" autocorrect="off" spellcheck="false"
                required maxlength="128"
                data-tooltip="<?php esc_attr_e('Digite seu e-mail, usuário, telefone, CPF ou passaporte', 'apollo-login'); ?>">
            <label class="apollo-label" for="log"><?php esc_html_e('Identificação', 'apollo-social'); ?></label>
        </div>
        <!--   <p class="small-note" id="login-doc-hint">
               <?php esc_html_e('', 'apollo-social'); ?>
        </p> -->
    </div>

    <!-- Password Field -->
    <div class="input-group">
        <div class="input-icon-wrap input-icon-wrap--password">
            <span class="input-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
            <input type="password" id="pwd" name="apollo_pwd" value="" placeholder=" " class="apollo-input"
                autocomplete="current-password" autocapitalize="off" autocorrect="off" spellcheck="false" required
                data-tooltip="<?php esc_attr_e('Digite sua senha', 'apollo-social'); ?>">
            <label class="apollo-label" for="pwd"><?php esc_html_e('Chave de Acesso', 'apollo-social'); ?></label>
            <button type="button" class="pwd-toggle-btn" id="pwd-toggle"
                aria-label="<?php esc_attr_e('Mostrar senha', 'apollo-login'); ?>" aria-pressed="false"
                data-tooltip="<?php esc_attr_e('Mostrar ou ocultar senha', 'apollo-login'); ?>">
                <i class="ri-eye-line" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <!-- BRUTAL inline override: kills the huge white ball (.toggle-track::after +
         oversized .toggle-thumb) and rebuilds ONE small knob. Inline in <body> so it
         beats every stacked auth stylesheet regardless of cache/enqueue order. -->
    <style>
        .custom-toggle.keep-toggle {
            gap: 8px !important;
        }

        .custom-toggle.keep-toggle .toggle-track::before,
        .custom-toggle.keep-toggle .toggle-track::after {
            content: none !important;
            display: none !important;
            background: none !important;
        }

        .custom-toggle.keep-toggle .toggle-track {
            position: relative !important;
            width: 34px !important;
            height: 18px !important;
            background: #e6e6e9 !important;
            border: 0 !important;
            border-radius: 999px !important;
            box-shadow: none !important;
            flex: 0 0 auto !important;
            padding: 0 !important;
            transition: background .3s ease !important;
        }

        .custom-toggle.keep-toggle .toggle-thumb {
            display: block !important;
            position: absolute !important;
            top: 50% !important;
            left: 2px !important;
            right: auto !important;
            transform: translateY(-50%) !important;
            width: 14px !important;
            height: 14px !important;
            min-width: 0 !important;
            min-height: 0 !important;
            max-width: 14px !important;
            max-height: 14px !important;
            margin: 0 !important;
            border: 0 !important;
            border-radius: 50% !important;
            background: #bbb !important;
            box-shadow: none !important;
            transition: left .35s ease, background .35s ease !important;
        }

        .custom-toggle.keep-toggle.active .toggle-track {
            background: #eee !important;
        }

        .custom-toggle.keep-toggle.active .toggle-thumb {
            left: calc(100% - 16px) !important;
            background: linear-gradient(40deg, #ff0080, #ff8c00 70%) !important;
        }
    </style>

    <!-- Remember Session Toggle -->
    <div class="form-group extra-xps" style="display: flex; justify-content: space-between; align-items: center;"
        data-tooltip="<?php esc_attr_e('Opções adicionais', 'apollo-social'); ?>">
        <div class="custom-toggle keep-toggle"
            data-tooltip="<?php esc_attr_e('Manter sessão ativa', 'apollo-social'); ?>">
            <div class="toggle-track">
                <div class="toggle-thumb bttm-extra"></div>
            </div>
            <span><?php esc_html_e('Continuar conectado', 'apollo-social'); ?></span>
            <input type="hidden" name="rememberme" value="0">
        </div>

    </div>

    <!-- Submit Button -->
    <button type="submit" class="btn-primary" data-tooltip="<?php esc_attr_e('Acessar o sistema', 'apollo-social'); ?>">
        <span><?php esc_html_e('ACESSAR TERMINAL', 'apollo-social'); ?></span>
        <i class="ri-arrow-right-line"></i>
    </button>

</form>

<!-- Register Link -->
<div class="auth-register-cta">
    <p>
        <!-- < ?php esc_html_e('Não possui acesso?', 'apollo-social'); ?> -->
        <a style="font-size:10.5px!important; " href="<?php echo esc_url(\Apollo\Login\apollo_login_register_url()); ?>"
            class="btn-text" id="switch-to-register">
            <?php esc_html_e('Registrar-se', 'apollo-social'); ?>
        </a>
        <span style="opacity:.5; margin: 0 3.5px;"> | </span>

        <a type="button" class="btn-text bttm-extra" style="font-size:10.5px!important;" id="forgot-password"
            data-tooltip="<?php esc_attr_e('Recuperar acesso', 'apollo-social'); ?>">
            <?php esc_html_e('Recuperar acesso', 'apollo-social'); ?>
        </a>
        <span style="opacity:.5; margin: 0 3.5px;"> | </span>
        <a href="#" data-apollo-report-trigger data-apollo-suporte style="font-size:10.5px!important; cursor: pointer;">
            <?php esc_html_e('Suporte', 'apollo-social'); ?>
        </a>
    </p>
</div>