<?php
/**
 * Create Event — Taxonomy and status
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$selected_season = '';
$selected_sounds = array( 'tech_house', 'house' );
if ( ! empty( $edit_payload['season'] ) ) {
	$selected_season = (string) $edit_payload['season'];
}
if ( ! empty( $edit_payload['sounds'] ) && is_array( $edit_payload['sounds'] ) ) {
	$selected_sounds = $edit_payload['sounds'];
}

$fallback_seasons = array(
	'summer'    => __( 'Verão', 'apollo-events' ),
	'winter'    => __( 'Inverno', 'apollo-events' ),
	'rockinrio' => __( 'Rock in Rio', 'apollo-events' ),
	'nye'       => __( 'Réveillon', 'apollo-events' ),
	'carnival'  => __( 'Carnaval', 'apollo-events' ),
);

$fallback_sounds = array(
	'tech_house' => 'Tech House',
	'techno'     => 'Techno',
	'house'      => 'House',
	'disco'      => 'Disco',
	'trance'     => 'Trance',
);
?>
            <div class="card sh01">
                <div class="tref-sec-lbl"><i class="ri-exchange-box-line"></i> Marcações e Status</div>

                <div class="grid-3" style="margin-top: 16px;">
                    <div class="field">
                        <label class="field-label">Temporada / Tag do Evento</label>
                        <select id="ev-season" name="seasons" class="apollo-select">
							<option value="" <?php selected( $selected_season, '' ); ?>><?php esc_html_e( 'Selecione', 'apollo-events' ); ?></option>
							<?php if ( ! empty( $seasons ) ) : ?>
								<?php foreach ( $seasons as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $selected_season, $term->slug ); ?>><?php echo esc_html( $term->name ); ?></option>
								<?php endforeach; ?>
							<?php else : ?>
								<?php foreach ( $fallback_seasons as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $selected_season, $slug ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="field-label">Status dos Ingressos</label>
                        <select id="ev-tickets" name="ticket_status" class="apollo-select">
                            <option value="free">Gratuito</option>
                            <option value="available" selected>Disponível</option>
                            <option value="soldout_soon">Sold-out soon</option>
                            <option value="sold_out">Sold-out</option>
                        </select>
                    </div>
                    <div class="field">
                        <label class="field-label">Status do Evento</label>
                        <select id="ev-status" name="event_status" class="apollo-select">
                            <option value="scheduled" selected>Agendado</option>
                            <option value="ongoing">Save the date</option>
                            <option value="finished">Finalizado</option>
                            <option value="cancelled">Cancelado</option>
                            <option value="postponed">Adiado</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label class="field-label">Privacidade</label>
                        <select id="ev-privacy" name="privacy" class="apollo-select">
                            <option value="public" selected>Público</option>
                            <option value="private">Privado</option>
                            <option value="invite">Convidados</option>
                        </select>
                    </div>
                </div>

                <div class="field" style="margin-bottom: 0;">
                    <label class="field-label mb-3">Sons / Gêneros <span class="ax-coauthors-req">seleção múltipla</span></label>

					<div style="display:none;" aria-hidden="true">
						<?php
						$sound_options = array();
						if ( ! empty( $sounds ) && is_array( $sounds ) ) {
							foreach ( $sounds as $term ) {
								if ( ! $term instanceof WP_Term || '' === (string) $term->slug ) {
									continue;
								}
								$sound_options[ $term->slug ] = $term->name;
							}
						}
						if ( empty( $sound_options ) ) {
							$sound_options = $fallback_sounds;
						}
						foreach ( $sound_options as $slug => $label ) :
							$checked = in_array( $slug, $selected_sounds, true );
							?>
                        <input type="checkbox" class="ev-genre" name="sounds[]" value="<?php echo esc_attr( $slug ); ?>" data-label="<?php echo esc_attr( $label ); ?>" <?php checked( $checked ); ?>>
						<?php endforeach; ?>
                    </div>

                    <div style="position: relative; margin-top: 4px;">
                        <i class="ri-search-line" style="position: absolute; left: 14px; top: 13px; color: var(--muted);"></i>
                        <input type="search" id="genreSearch" class="apollo-input ax-coauthors-search" style="padding-left: 38px;" placeholder="Buscar gênero..." autocomplete="off">
                    </div>

                    <?php /* Selected chips always visible; #genreList only fills after typing (Equipe do Evento pattern). */ ?>
                    <div class="ax-coauthors-selected" id="genreChips" aria-live="polite"></div>

                    <div class="ax-coauthors-list" id="genreList" role="listbox" aria-multiselectable="true" aria-label="Selecionar gêneros"></div>
                </div>
            </div>
