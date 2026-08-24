<?php
/**
 * Create / Edit Event — Equipe do Evento (Event's Team)
 *
 * Stored via apollo-coauthor `_coauthors`. Grants equal edit permission with the
 * author. Not rendered on the public single page. Never labeled as "admin".
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$selected_coauthors = array();
if ( ! empty( $edit_payload['coauthors'] ) && is_array( $edit_payload['coauthors'] ) ) {
	$selected_coauthors = array_map( 'intval', $edit_payload['coauthors'] );
}

$author_user = isset( $current_user ) && $current_user instanceof WP_User
	? $current_user
	: wp_get_current_user();
$author_avatar = get_avatar_url( $author_user->ID, array( 'size' => 64 ) ) ?: '';
?>
            <div class="card sh01 ax-coauthors-card" id="coauthorsCard">
                <div class="tref-sec-lbl">
                    <i class="ri-team-line" aria-hidden="true"></i>
                    <?php esc_html_e( 'Equipe do Evento', 'apollo-events' ); ?>
                    <span class="ax-coauthors-req"><?php esc_html_e( 'seleção múltipla', 'apollo-events' ); ?></span>
                </div>
                <p class="ax-coauthors-hint">
                    <?php
                    echo wp_kses(
                        __( 'Quem você escolher aqui <strong>ganha permissão para editar as informações deste evento</strong> (título, data, local, lineup e o restante) com o mesmo acesso que você. Essa lista <strong>não aparece</strong> na página pública do evento.', 'apollo-events' ),
                        array( 'strong' => array() )
                    );
                    ?>
                </p>

                <input type="hidden" id="ev-coauthors" name="coauthors" value="<?php echo esc_attr( wp_json_encode( array_values( $selected_coauthors ) ) ); ?>">

                <div class="field" style="margin-top: 16px;">
                    <label class="field-label" for="coauthorSearch"><?php esc_html_e( 'Buscar pessoas', 'apollo-events' ); ?></label>
                    <div style="position: relative;">
                        <i class="ri-search-line" style="position: absolute; left: 14px; top: 13px; color: var(--muted);" aria-hidden="true"></i>
                        <input type="search" id="coauthorSearch" class="apollo-input ax-coauthors-search" style="padding-left: 38px;"
                            placeholder="<?php esc_attr_e( 'Nome ou @usuário…', 'apollo-events' ); ?>"
                            autocomplete="off"
                            aria-controls="coauthorList">
                    </div>
                </div>

                <div class="ax-coauthors-selected" id="coauthorChips" aria-live="polite">
                    <span class="ax-coauthors-chip is-locked" data-author="1">
                        <?php if ( $author_avatar ) : ?>
                            <img src="<?php echo esc_url( $author_avatar ); ?>" alt="" loading="lazy">
                        <?php else : ?>
                            <i class="ri-user-line" aria-hidden="true"></i>
                        <?php endif; ?>
                        <span><?php echo esc_html( $author_user->display_name ); ?> · <?php esc_html_e( 'você', 'apollo-events' ); ?></span>
                    </span>
                </div>

                <div class="ax-coauthors-list" id="coauthorList" role="listbox" aria-multiselectable="true"
                    aria-label="<?php esc_attr_e( 'Selecionar equipe do evento', 'apollo-events' ); ?>">
                </div>

                <p class="ax-coauthors-status" id="coauthorStatus" aria-live="polite"></p>
            </div>
