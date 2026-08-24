<?php

/**
 * Registration form — STEP 1a (identity) + STEP 1b (profile + phone).
 *
 * @package Apollo\Login
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('wp_nonce_field')) {
    require_once ABSPATH . 'wp-includes/formatting.php';
    require_once ABSPATH . 'wp-includes/plugin.php';
}

$terms_url        = isset($auth_config['terms_url']) ? $auth_config['terms_url'] : home_url('/termos-e-politica/');
$show_instagram   = isset($auth_config['show_instagram']) ? $auth_config['show_instagram'] : true;
?>
<div class="flavor-text" data-tooltip="<?php esc_attr_e('Instruções de registro', 'apollo-social'); ?>">
    <span><span class="terminal-prompt">&gt;</span> <?php esc_html_e('Registro de novo operador', 'apollo-social'); ?></span>
</div>
<div class="flavor-text flavor-text--spaced" id="register-step-hint">
    <span><span class="terminal-prompt">&gt;</span> <?php esc_html_e('ETAPA 1a — Identidade', 'apollo-login'); ?></span>
</div>

<form id="register-form" method="post" novalidate data-register-step="1a">

    <?php wp_nonce_field('apollo_register_nonce', 'apollo_register_nonce'); ?>

    <div id="register-step-1a" class="register-step-panel is-active">

        <div class="input-group">
            <div class="input-icon-wrap">
                <span class="input-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
                <input type="text" id="nome" name="nome" placeholder=" " class="apollo-input" autocomplete="name" required>
                <label class="apollo-label" for="nome"><?php esc_html_e('Nome social', 'apollo-social'); ?> <span class="req-mark">*</span></label>
            </div>
        </div>

        <?php if ($show_instagram) : ?>
            <div class="input-group">
                <div class="input-icon-wrap">
                    <span class="input-prefix" aria-hidden="true">@&nbsp;&nbsp;&nbsp;</span>
                    <input type="text" id="instagram" name="instagram" placeholder=" " class="apollo-input" autocomplete="off" required>
                    <label class="apollo-label" for="instagram"><?php esc_html_e('Instagram', 'apollo-social'); ?> <span class="req-mark">*</span></label>
                </div>
                <p class="small-note"><?php esc_html_e('Usuário unificado para apolloID e ao instagram', 'apollo-login'); ?><span class="auth-brand-sep">::</span><?php esc_html_e('rio.', 'apollo-login'); ?></p>
            </div>
        <?php endif; ?>

        <div class="input-group" id="doc-field">
            <div class="input-icon-wrap">
                <span class="input-prefix" id="doc-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
                <input type="text" id="documento" class="apollo-input" autocomplete="off" required maxlength="20" placeholder=" ">
                <label class="apollo-label" for="documento"><?php esc_html_e('CPF ou passaporte', 'apollo-login'); ?> <span class="req-mark">*</span></label>
            </div>
            <input type="hidden" name="doc_type" id="doc_type" value="">
            <input type="hidden" name="cpf" id="cpf" value="">
            <input type="hidden" name="passport" id="passport" value="">
            <div class="doc-type-meta" aria-live="polite">
                <div class="doc-type-pills">
                    <span class="doc-pill is-idle" id="doc-pill-cpf" data-type="cpf"><?php esc_html_e('CPF', 'apollo-login'); ?></span>
                    <span class="doc-pill is-idle" id="doc-pill-passport" data-type="passport"><?php esc_html_e('Passaporte', 'apollo-login'); ?></span>
                </div>
                <div class="cpf-feedback"></div>
            </div>
            <div class="input-group hidden" id="passport-country-field">
                <div class="input-icon-wrap">
                    <span class="input-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
                    <input type="text" id="passport_country" name="passport_country" placeholder=" " class="apollo-input" autocomplete="off">
                    <label class="apollo-label" for="passport_country"><?php esc_html_e('País de emissão', 'apollo-social'); ?></label>
                </div>
            </div>
            <div class="passport-warning" id="passport-warning" aria-live="polite">
                <p>
                    <i class="ri-alert-fill" aria-hidden="true"></i>
                    <?php esc_html_e('Usuários com passaporte não poderão assinar documentos digitais. A assinatura digital exige CPF válido (Lei 14.063/2020).', 'apollo-login'); ?>
                </p>
            </div>
        </div>

        <div class="input-group">
            <div class="input-icon-wrap">
                <span class="input-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
                <input type="email" id="email" name="email" placeholder=" " class="apollo-input" autocomplete="email" required>
                <label class="apollo-label" for="email"><?php esc_html_e('E-mail', 'apollo-social'); ?> <span class="req-mark">*</span></label>
            </div>
        </div>

        <button type="button" class="btn-primary" id="register-step-1a-btn">
            <span><?php esc_html_e('CONTINUAR', 'apollo-login'); ?></span>
            <i class="ri-arrow-right-line"></i>
        </button>
    </div>

    <div id="register-step-1b" class="register-step-panel" hidden>

        <div class="input-group" id="bday-field">
            <p class="bday-field-label"><?php esc_html_e('Data de nascimento', 'apollo-login'); ?> <span class="req-mark">*</span></p>
            <div class="bday-inputs">
                <div class="input-icon-wrap bday-input-wrap">
                    <span class="input-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
                    <input type="number" id="bday_day" name="bday_day" class="apollo-input bday-part" min="1" max="31" placeholder=" " inputmode="numeric" autocomplete="bday-day">
                    <label class="apollo-label" for="bday_day"><?php esc_html_e('Dia', 'apollo-login'); ?></label>
                </div>
                <div class="input-icon-wrap bday-input-wrap">
                    <span class="input-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
                    <input type="number" id="bday_month" name="bday_month" class="apollo-input bday-part" min="1" max="12" placeholder=" " inputmode="numeric" autocomplete="bday-month">
                    <label class="apollo-label" for="bday_month"><?php esc_html_e('Mês', 'apollo-login'); ?></label>
                </div>
                <div class="input-icon-wrap bday-input-wrap">
                    <span class="input-prefix" aria-hidden="true">&gt;&nbsp;&nbsp;&nbsp;</span>
                    <input type="number" id="bday_year" name="bday_year" class="apollo-input bday-part" min="1900" max="2100" placeholder=" " inputmode="numeric" autocomplete="bday-year">
                    <label class="apollo-label" for="bday_year"><?php esc_html_e('Ano', 'apollo-login'); ?></label>
                </div>
            </div>
            <input type="hidden" name="birth_date" id="birth_date" value="">
            <div class="bday-feedback cpf-feedback" aria-live="polite"></div>
        </div>

        <fieldset class="input-group party-role-fieldset" id="party-role-field">
            <legend class="party-role-legend"><?php esc_html_e('In a party, who are you?', 'apollo-login'); ?> <span class="req-mark">*</span></legend>
            <div class="party-role-options">
                <button type="button" class="clubber-option" data-value="smoking_gossip" aria-pressed="false">
                    <?php esc_html_e('Always smoking area, gossips and fun', 'apollo-login'); ?>
                </button>
                <button type="button" class="clubber-option" data-value="dancing_floor" aria-pressed="false">
                    <?php esc_html_e('Dancing on spaced areas', 'apollo-login'); ?>
                </button>
                <button type="button" class="clubber-option" data-value="front_row" aria-pressed="false">
                    <?php esc_html_e('Sure on Front as always', 'apollo-login'); ?>
                </button>
            </div>
            <input type="hidden" name="party_role" id="party_role" value="">
        </fieldset>

        <?php require APOLLO_LOGIN_DIR . 'templates/parts/register-phone-verify.php'; ?>

        <button type="button" class="btn-primary" id="register-step-1b-btn">
            <span><?php esc_html_e('PROSSEGUIR', 'apollo-social'); ?></span>
            <i class="ri-arrow-right-line"></i>
        </button>

        <button type="button" class="btn-text register-step-back" id="register-step-1b-back">
            <?php esc_html_e('Voltar', 'apollo-login'); ?>
        </button>
    </div>

    <input type="hidden" name="terms_accepted" value="0">
    <input type="hidden" name="marketing_opt_in" value="0">
    <input type="hidden" name="senha" id="senha" value="">
    <input type="hidden" name="clubber_universe" value="both">
    <input type="hidden" name="quiz_passed" value="0">
    <input type="hidden" id="apollo-quiz-token" name="apollo_quiz_token" value="">

</form>

<div class="auth-register-cta">
    <p>
        <?php esc_html_e('Já possui acesso?', 'apollo-social'); ?>
        <a href="<?php echo esc_url(\Apollo\Login\apollo_login_canonical_login_url()); ?>" class="btn-text" id="switch-to-login">
            <?php esc_html_e('Acessar terminal', 'apollo-social'); ?>
        </a>
    </p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const documento = document.getElementById('documento');
    const docTypeInput = document.getElementById('doc_type');
    const cpfHidden = document.getElementById('cpf');
    const passportHidden = document.getElementById('passport');
    const pillCpf = document.getElementById('doc-pill-cpf');
    const pillPassport = document.getElementById('doc-pill-passport');
    const docPrefix = document.getElementById('doc-prefix');
    const countryField = document.getElementById('passport-country-field');
    const passportWarning = document.getElementById('passport-warning');
    const prefixGap = '\u00a0\u00a0\u00a0';

    if (!documento || !docTypeInput) {
        return;
    }

    function setPills(type) {
        if (!pillCpf || !pillPassport) return;
        pillCpf.classList.remove('is-active', 'is-idle');
        pillPassport.classList.remove('is-active', 'is-idle');
        if ('cpf' === type) {
            pillCpf.classList.add('is-active');
            pillPassport.classList.add('is-idle');
        } else if ('passport' === type) {
            pillPassport.classList.add('is-active');
            pillCpf.classList.add('is-idle');
        } else {
            pillCpf.classList.add('is-idle');
            pillPassport.classList.add('is-idle');
        }
    }

    function showPassportExtras(show) {
        if (countryField) countryField.classList.toggle('hidden', !show);
        if (passportWarning) passportWarning.classList.toggle('is-visible', show);
    }

    function resetDoc() {
        docTypeInput.value = '';
        cpfHidden.value = '';
        passportHidden.value = '';
        setPills(null);
        showPassportExtras(false);
        if (docPrefix) docPrefix.textContent = '>' + prefixGap;
        documento.classList.remove('cpf-valid', 'cpf-invalid');
    }

    documento.addEventListener('input', function(e) {
        const raw = e.target.value;
        const trimmed = raw.trim();
        if ('' === trimmed) {
            resetDoc();
            return;
        }
        if (/^\d/.test(trimmed)) {
            const digits = raw.replace(/\D/g, '').slice(0, 11);
            e.target.value = digits;
            docTypeInput.value = 'cpf';
            cpfHidden.value = digits;
            passportHidden.value = '';
            setPills('cpf');
            showPassportExtras(false);
            documento.dispatchEvent(new Event('cpf-sync', { bubbles: true }));
            return;
        }
        if (/^[a-zA-Z]/.test(trimmed)) {
            const passport = raw.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0, 20);
            e.target.value = passport;
            docTypeInput.value = 'passport';
            passportHidden.value = passport;
            cpfHidden.value = '';
            setPills('passport');
            showPassportExtras(true);
            documento.classList.remove('cpf-valid', 'cpf-invalid');
        }
    });

    resetDoc();
});
</script>
