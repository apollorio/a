<?php

/**
 * Template Part: Signing Form
 * PFX upload + password form shown when status is pending.
 *
 * @package Apollo\Sign
 * @var string $status
 */

if (! defined('ABSPATH')) {
    exit;
}

if ($status !== 'pending') {
    return;
}
?>
<!-- ICP-Brasil badge -->
<div class="sign-icpbr">
    <i class="ri-shield-check-fill"></i>
    <span>Conformidade ICP-Brasil — Aceita certificados A1 (.pfx/.p12) e A3 (via token)</span>
</div>

<div class="sign-status pending">
    <i class="ri-time-line"></i> Aguardando Assinatura
</div>

<form id="sign-form" enctype="multipart/form-data">
    <div class="sign-form-group">
        <label for="sign-cert">Certificado Digital (.pfx ou .p12)</label>
        <input type="file" id="sign-cert" name="certificate" class="sign-form-input" accept=".pfx,.p12" required>
    </div>
    <div class="sign-form-group">
        <label for="sign-pass">Senha do Certificado</label>
        <input type="password" id="sign-pass" name="password" class="apollo-input sign-form-input" placeholder="Senha do certificado..." required>
        <!-- Hidden placement fields — filled by sign-placement.js -->
        <input type="hidden" id="sign-placement-x" name="sig_x" value="">
        <input type="hidden" id="sign-placement-y" name="sig_y" value="">
        <input type="hidden" id="sign-placement-w" name="sig_w" value="">
        <input type="hidden" id="sign-placement-h" name="sig_h" value="">
        <input type="hidden" id="sign-placement-page" name="sig_page" value="">
        <input type="hidden" id="sign-placement-mode" name="placement_mode" value="auto_footer">

        <button type="submit" class="sign-btn" id="sign-btn">
            <i class="ri-shield-keyhole-fill"></i> Assinar Documento
        </button>
</form>

<div class="sign-error" id="sign-error"></div>