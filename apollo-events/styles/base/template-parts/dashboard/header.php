<?php
/**
 * Meus Eventos — Page header (inside .ax-main)
 *
 * Expected: $current_user (WP_User)
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
        <div class="flex-between align-start section" style="margin-bottom: 28px;">
            <div>
                <h1 class="display-text" id="pageTitle"><?php esc_html_e( 'Meus Eventos', 'apollo-events' ); ?></h1>
                <p class="txt-secondary mt-2"><?php esc_html_e( 'Eventos que você criou ou co-produz na Apollo.', 'apollo-events' ); ?></p>
            </div>
            <div class="ev-dash-actions">
                <div style="position: relative;">
                    <i class="ri-search-line" style="position: absolute; left: 14px; top: 12px; color: var(--muted);"></i>
                    <input type="search" id="dashSearch" class="apollo-input" style="padding-left: 38px; min-width: 190px;" placeholder="<?php esc_attr_e( 'Buscar eventos...', 'apollo-events' ); ?>" autocomplete="off">
                </div>
                <a href="<?php echo esc_url( home_url( '/novo-evento/' ) ); ?>" target="_blank" rel="noopener" class="btn btn-primary"><i class="ri-add-line"></i> <?php esc_html_e( 'Novo Evento', 'apollo-events' ); ?></a>
            </div>
        </div>
