<?php
/**
 * Partial: Command Palette (⌘K)
 *
 * Quick navigation and action search overlay.
 *
 * @package Apollo\Gestor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="cmd-overlay" id="cmdPalette">
    <div class="cmd-box">
        <div class="cmd-input-wrap">
            <i class="ri-search-line"></i>
            <input type="text" class="apollo-input cmd-input" id="cmdInput" placeholder="Buscar comando…" autocomplete="off">
            <span class="cmd-esc">ESC</span>
        </div>
        <div class="cmd-results" id="cmdResults">
            <!-- JS populates command items -->
        </div>
    </div>
</div>