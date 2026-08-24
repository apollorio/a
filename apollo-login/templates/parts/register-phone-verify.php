<?php

/**
 * Phone + Telegram verification block (STEP 1b).
 * Visual SSOT: apollo-telegram/views/telegram-phone-support.php (/telegram) — do not edit that file.
 *
 * @package Apollo\Login
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="input-group" id="reg-phone-field">
    <p class="bday-field-label"><?php esc_html_e('Telefone', 'apollo-login'); ?> <span class="req-mark">*</span></p>
    <p class="small-note"><?php esc_html_e('Confirme via Telegram — mesmo fluxo de verificação do apollo::rio.', 'apollo-login'); ?></p>

    <div class="phone-group" id="regPhoneGroup">
        <div class="flag-selector" id="regFlagTrigger" tabindex="0" role="button" aria-label="<?php esc_attr_e('Selecionar país', 'apollo-login'); ?>">
            <img src="https://flagpedia.net/data/flags/icon/36x27/br.png" alt="BR" class="flag-icon" id="regSelectedFlag" width="22" height="16">
            <span class="prefix-text" id="regSelectedPrefix">+55</span>
            <i class="ri-arrow-down-s-line" aria-hidden="true"></i>
        </div>
        <input type="tel" class="phone-input" id="regPhoneInput" placeholder="(21) 9 9999-9999" autocomplete="off" inputmode="tel">
        <button type="button" class="validate-btn" id="regValidateBtn" aria-label="<?php esc_attr_e('Validar telefone', 'apollo-login'); ?>">
            <i class="ri-arrow-right-line" id="regValidateIcon" aria-hidden="true"></i>
        </button>
        <div class="country-dropdown" id="regCountryDropdown">
            <div class="search-wrap">
                <i class="ri-search-line" aria-hidden="true"></i>
                <input type="text" class="search-input" id="regCountrySearch" placeholder="<?php esc_attr_e('Buscar país...', 'apollo-login'); ?>" autocomplete="off">
            </div>
            <div class="country-list" id="regCountryList"></div>
        </div>
    </div>

    <div class="phone-feedback cpf-feedback" id="regPhoneFeedback" aria-live="polite"></div>

    <input type="hidden" name="phone" id="phone" value="">
    <input type="hidden" name="phone_request_id" id="phone_request_id" value="">
    <input type="hidden" name="phone_verified" id="phone_verified" value="0">
</div>

<div class="reg-phone-lightbox lightbox-overlay" id="regVerificationModal" aria-hidden="true">
    <div class="lightbox-modal" role="dialog" aria-modal="true" aria-labelledby="regPhoneModalTitle">
        <button type="button" class="close-modal" id="regCloseModalBtn" aria-label="<?php esc_attr_e('Fechar', 'apollo-login'); ?>">
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>
        <div class="lightbox-icon">
            <i class="ri-telegram-fill" style="color: #24A1DE;" aria-hidden="true"></i>
        </div>
        <div>
            <h3 class="lightbox-title" id="regPhoneModalTitle"><?php esc_html_e('É você mesmo?', 'apollo-login'); ?></h3>
            <p class="lightbox-desc" style="margin-bottom: 12px;"><?php esc_html_e('Acreditamos em conexões reais e em uma comunidade protegida contra perfis falsos, golpes e acessos automatizados.', 'apollo-login'); ?></p>
            <p class="lightbox-desc"><?php esc_html_e('Para validar seu número, clique abaixo, solicite seu código ao nosso bot oficial no Telegram e retorne aqui para inserir o código recebido.', 'apollo-login'); ?></p>
        </div>
        <input type="text" class="hidden-input" id="regHiddenCodeInput" maxlength="6" autocomplete="one-time-code" tabindex="-1" aria-hidden="true">
        <div class="interactive-area">
            <div class="telegram-prestep" id="regTelegramPreStep">
                <button type="button" class="btn-telegram" id="regGetTelegramCodeBtn"><?php esc_html_e('Solicitar código', 'apollo-login'); ?></button>
            </div>
            <div class="verification-core is-muted" id="regVerificationCore">
                <div class="code-inputs-container" id="regCodeContainer">
                    <?php for ($i = 0; $i < 6; $i++) : ?>
                        <input type="text" class="code-digit" maxlength="1" inputmode="numeric" pattern="[0-9]*" data-index="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr(sprintf(__('Dígito %d', 'apollo-login'), $i + 1)); ?>">
                    <?php endfor; ?>
                </div>
                <div class="lightbox-footer">
                    <button type="button" class="action-btn btn-verify" id="regSubmitCodeBtn" disabled><?php esc_html_e('Sou eu mesmo!', 'apollo-login'); ?></button>
                    <span class="resend-text"><?php esc_html_e('Não recebeu o código?', 'apollo-login'); ?> <button type="button" class="resend-link" id="regResendLink"><?php esc_html_e('Solicite novamente', 'apollo-login'); ?></button></span>
                </div>
            </div>
        </div>
    </div>
</div>
