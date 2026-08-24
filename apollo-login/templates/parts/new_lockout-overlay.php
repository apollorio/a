<?php

/**
 * ================================================================================
 * APOLLO AUTH - Lockout Overlay Template Part
 * ================================================================================
 * Displays the security lockout overlay when too many failed attempts occur.
 * Only visible when body has data-state="danger"
 *
 * @package Apollo\Login
 * @since 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$lockout_duration = isset($auth_config['lockout_duration']) ? $auth_config['lockout_duration'] : 60;
$minutes          = floor($lockout_duration / 60);
$seconds          = $lockout_duration % 60;
$initial_timer    = sprintf('%d:%02d', $minutes, $seconds);
?>

<div class="lockout-overlay" data-tooltip="<?php esc_attr_e('Sistema bloqueado', 'apollo-login'); ?>">

    <div class="lockout-overlay__icon" data-tooltip="<?php esc_attr_e('Ícone de alerta', 'apollo-login'); ?>">
        <i class="ri-alarm-warning-fill" aria-hidden="true"></i>
    </div>

    <h2 data-tooltip="<?php esc_attr_e('Título do bloqueio', 'apollo-login'); ?>">
        <?php esc_html_e('Acesso bloqueado', 'apollo-login'); ?>
    </h2>

    <p class="lockout-overlay__msg" data-tooltip="<?php esc_attr_e('Mensagem de segurança', 'apollo-login'); ?>">
        <?php esc_html_e('Múltiplas tentativas de acesso detectadas.', 'apollo-login'); ?><br>
        <?php esc_html_e('Sistema temporariamente bloqueado por segurança.', 'apollo-login'); ?>
    </p>

    <div class="lockout-overlay__timer" data-tooltip="<?php esc_attr_e('Tempo restante', 'apollo-login'); ?>">
        <span id="lockout-timer"><?php echo esc_html($initial_timer); ?></span>
    </div>

    <p class="lockout-overlay__hint" data-tooltip="<?php esc_attr_e('Instrução', 'apollo-login'); ?>">
        <?php esc_html_e('Aguarde o timer zerar para tentar novamente.', 'apollo-login'); ?>
    </p>

</div>
