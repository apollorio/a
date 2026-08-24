<?php
/**
 * Genre immersion picker shell (quiz stage 2 — apollo-auth-genres.js).
 *
 * @package Apollo\Login
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<template id="apollo-genre-explosion-tpl">
    <div class="genre-explosion-wrap" data-genre-root>
        <p class="test-instruction genre-explosion-label"><?php esc_html_e('Toque um universo para explorar gêneros. Escolha de 3 a 5.', 'apollo-login'); ?></p>

        <div class="universe-filters" role="group" aria-label="<?php esc_attr_e('Filtrar por universo', 'apollo-login'); ?>">
            <button type="button" class="universe-filter-btn is-active" data-filter-group="underground" aria-pressed="true">underground</button>
            <button type="button" class="universe-filter-btn is-active" data-filter-group="both" aria-pressed="true">both</button>
            <button type="button" class="universe-filter-btn is-active" data-filter-group="mainstream" aria-pressed="true">mainstream</button>
        </div>

        <div class="genre-collapsed-summary" data-genre-pills>
            <span class="genre-placeholder"><?php esc_html_e('Escolha gêneros...', 'apollo-login'); ?></span>
        </div>

        <div class="genre-immersion-stage" data-genre-immersion hidden aria-hidden="true">
            <header class="genre-immersion-hd">
                <button type="button" class="genre-back-btn" data-genre-back>
                    <i class="ri-arrow-left-line" aria-hidden="true"></i>
                    <?php esc_html_e('Voltar', 'apollo-login'); ?>
                </button>
                <span class="genre-immersion-label" data-genre-immersion-label>underground</span>
            </header>
            <div class="genre-tags-scroll" data-genre-tags role="listbox" aria-multiselectable="true"></div>
            <footer class="genre-immersion-ft">
                <span class="genre-immersion-count">
                    <span data-genre-count-immersion>0</span>/5 · <?php esc_html_e('mín. 3', 'apollo-login'); ?>
                </span>
                <button type="button" class="genre-confirm-btn" data-genre-confirm><?php esc_html_e('Confirmar seleção', 'apollo-login'); ?></button>
            </footer>
        </div>

        <select class="visually-hidden" data-genre-native multiple name="sounds[]" aria-hidden="true" tabindex="-1"></select>

        <div class="sound-meta genre-meta">
            <span><span data-genre-count>0</span>/5 <?php esc_html_e('selecionados', 'apollo-login'); ?></span>
            <span><?php esc_html_e('Mínimo 3', 'apollo-login'); ?></span>
        </div>
    </div>
</template>
