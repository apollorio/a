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
        <div class="flex-between align-start section" style="margin-bottom: 32px;">
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
                <?php if ( $is_edit ) : ?>
                <button type="button" class="btn btn-secondary" id="deleteBtn" onclick="openDeleteEventModal()"><?php esc_html_e( 'Deletar', 'apollo-events' ); ?></button>
                <?php else : ?>
                <button type="button" class="btn btn-secondary" id="deleteBtn" onclick="openDeleteEventModal()" hidden><?php esc_html_e( 'Deletar', 'apollo-events' ); ?></button>
                <?php endif; ?>
                <a class="btn btn-secondary" href="<?php echo esc_url( home_url( '/novo-evento/' ) ); ?>"><?php esc_html_e( 'Descartar', 'apollo-events' ); ?></a>
                <button type="button" class="btn btn-primary" id="saveBtn" onclick="saveEvent('saveBtn')"><i class="ri-save-line"></i> <?php echo $is_edit ? esc_html__( 'Atualizar Evento', 'apollo-events' ) : esc_html__( 'Salvar Evento', 'apollo-events' ); ?></button>
            </div>
        </div>
