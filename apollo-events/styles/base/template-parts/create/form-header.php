<?php
/**
 * Create Event — Page title + save actions
 *
 * Edit mode adds Deletar (secondary, before Descartar) → confirm lightbox.
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
        <div class="flex-between align-start section create-header" style="margin-bottom: 32px;">
            <div>
                <h1 class="display-text" id="pageTitle"><?php echo $is_edit ? esc_html__( 'Editar Evento', 'apollo-events' ) : esc_html__( 'Criar Novo Evento', 'apollo-events' ); ?></h1>
                <p class="txt-secondary mt-2" id="pageSubtitle">
					<?php
					if ( $is_edit && $edit_event ) {
						printf(
							/* translators: %s: event name */
							esc_html__( 'Editando “%s”.', 'apollo-events' ),
							esc_html( get_the_title( $edit_event ) )
						);
					} else {
						esc_html_e( 'Preencha os dados para publicar um novo evento na Apollo.', 'apollo-events' );
					}
					?>
				</p>
            </div>
            <div class="flex-row gap-2" id="formHeaderActions">
                <input type="hidden" id="ev-highlighted" value="<?php echo ( $is_edit && ! empty( $edit_payload['highlighted'] ) ) ? '1' : '0'; ?>">
                <?php if ( $is_edit ) : ?>
                <button type="button" class="btn btn-secondary btn-icon" id="deleteBtn" onclick="openDeleteEventModal()" aria-label="<?php esc_attr_e( 'Deletar', 'apollo-events' ); ?>" title="<?php esc_attr_e( 'Deletar', 'apollo-events' ); ?>"><i class="ri-delete-bin-6-line" aria-hidden="true"></i></button>
                <button type="button" class="btn btn-secondary btn-icon<?php echo ( ! empty( $edit_payload['highlighted'] ) ) ? ' is-on' : ''; ?>" id="highlightBtn" onclick="toggleHighlighted('highlightBtn')" aria-pressed="<?php echo ( ! empty( $edit_payload['highlighted'] ) ) ? 'true' : 'false'; ?>" aria-label="<?php esc_attr_e( 'Destacar', 'apollo-events' ); ?>" title="<?php esc_attr_e( 'Destacar', 'apollo-events' ); ?>"><i class="ri-medal-fill" aria-hidden="true"></i></button>
                <?php else : ?>
                <button type="button" class="btn btn-secondary btn-icon" id="deleteBtn" onclick="openDeleteEventModal()" hidden aria-label="<?php esc_attr_e( 'Deletar', 'apollo-events' ); ?>" title="<?php esc_attr_e( 'Deletar', 'apollo-events' ); ?>"><i class="ri-delete-bin-6-line" aria-hidden="true"></i></button>
                <button type="button" class="btn btn-secondary btn-icon" id="highlightBtn" onclick="toggleHighlighted('highlightBtn')" aria-pressed="false" aria-label="<?php esc_attr_e( 'Destacar', 'apollo-events' ); ?>" title="<?php esc_attr_e( 'Destacar', 'apollo-events' ); ?>"><i class="ri-medal-fill" aria-hidden="true"></i></button>
                <?php endif; ?>
                <a class="btn btn-secondary" href="<?php echo esc_url( home_url( '/novo-evento/' ) ); ?>"><?php esc_html_e( 'Descartar', 'apollo-events' ); ?></a>
                <button type="button" class="btn btn-primary btn-icon" id="saveBtn" onclick="saveEvent('saveBtn')" aria-label="<?php echo $is_edit ? esc_attr__( 'Atualizar Evento', 'apollo-events' ) : esc_attr__( 'Salvar Evento', 'apollo-events' ); ?>" title="<?php echo $is_edit ? esc_attr__( 'Atualizar Evento', 'apollo-events' ) : esc_attr__( 'Salvar Evento', 'apollo-events' ); ?>"><i class="ri-save-line" aria-hidden="true"></i></button>
            </div>
        </div>
