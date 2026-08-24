<?php

/**
 * ================================================================================
 * APOLLO AUTH - Aptitude Quiz Template Part
 * ================================================================================
 * Displays the aptitude quiz overlay for new user registration.
 * Four admission tests: visual pattern, Simon memory, ethics, reflex sync.
 *
 * @package Apollo\Login
 * @since 1.0.0
 *
 * QUIZ STAGES:
 * 1. Visual pattern — doubling sequence
 * 2. Simon — memory game (4 levels)
 * 3. Ethics — community respect
 * 4. Reflex sync — capture moving targets
 * ================================================================================
 */

// Prevent direct access
if (! defined('ABSPATH')) {
    exit;
}
?>

<div class="aptitude-overlay" id="aptitude-overlay" data-tooltip="<?php esc_attr_e('Teste de Aptidão', 'apollo-social'); ?>">

    <header class="quiz-hd" data-tooltip="<?php esc_attr_e('Cabeçalho do quiz', 'apollo-social'); ?>">
        <span class="quiz-hd-label"><?php esc_html_e('Teste de admissão', 'apollo-social'); ?></span>
        <span class="quiz-hd-progress" id="test-progress" data-tooltip="<?php esc_attr_e('Progresso do teste', 'apollo-social'); ?>">
            <?php esc_html_e('ETAPA 1 DE 4', 'apollo-social'); ?>
        </span>
    </header>

    <div class="quiz-scroll">

        <div id="test-content" data-tooltip="<?php esc_attr_e('Conteúdo do teste atual', 'apollo-social'); ?>">
            <div class="test-loading">
                <i class="ri-loader-4-line" aria-hidden="true"></i>
                <p><?php esc_html_e('Carregando teste...', 'apollo-social'); ?></p>
            </div>
        </div>

        <div class="quiz-actions">
            <button type="button" id="test-btn" class="btn-primary" disabled data-tooltip="<?php esc_attr_e('Botão de confirmação', 'apollo-social'); ?>">
                <span id="test-btn-text"><?php esc_html_e('CONFIRMAR', 'apollo-social'); ?></span>
                <i class="ri-arrow-right-line"></i>
            </button>
        </div>

    </div>

    <footer class="quiz-ft" data-tooltip="<?php esc_attr_e('Rodapé do quiz', 'apollo-social'); ?>">
        <p><?php esc_html_e('Três etapas rápidas — padrão, convivência e reflexo.', 'apollo-social'); ?></p>
        <p><?php esc_html_e('Responda com atenção. Erros reiniciam a pergunta atual.', 'apollo-social'); ?></p>
    </footer>

</div>
