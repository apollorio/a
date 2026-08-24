<?php

/**
 * FrontendForm — Renders inline event creation form for frontend
 *
 * Hooks into `apollo/events/render_create_form`
 * Covers ALL 17 meta keys + 5 taxonomies from apollo-registry.json
 *
 * Auto-generated fields NOT shown (system-managed):
 *   _event_is_gone, _event_view_count
 *
 * @package Apollo\Event
 */

namespace Apollo\Event;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

final class FrontendForm {

	public function __construct() {
		add_action( 'apollo/events/render_create_form', array( $this, 'render' ), 10, 1 );
	}

	/**
	 * Render the inline event creation form inside panel-forms.php
	 *
	 * @param int $user_id Current user ID
	 */
	public function render( int $user_id ): void {
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Faça login para criar eventos.', 'apollo-events' ) . '</p>';
			return;
		}

		// Fetch data for selects
		$locals = get_posts(
			array(
				'post_type'      => 'loc',
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		// Fallback: try "local" if "loc" returns empty
		if ( empty( $locals ) ) {
			$locals = get_posts(
				array(
					'post_type'      => 'local',
					'post_status'    => 'publish',
					'posts_per_page' => 500,
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);
		}

		$djs = get_posts(
			array(
				'post_type'      => 'dj',
				'post_status'    => 'publish',
				'posts_per_page' => 500,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$taxonomies = array(
			'event_category' => $this->get_taxonomy_terms( 'event_category' ),
			'event_type'     => $this->get_taxonomy_terms( 'event_type' ),
			'event_tag'      => $this->get_taxonomy_terms( 'event_tag' ),
			'sound'          => $this->get_taxonomy_terms( 'sound' ),
			'season'         => $this->get_taxonomy_terms( 'season' ),
		);

		$rest_url = esc_url_raw( rest_url( 'apollo/v1/eventos' ) );
		$nonce    = wp_create_nonce( 'wp_rest' );
		?>
		<div class="apl-form apl-form--event" id="apolloEventForm">
			<div class="apl-form__header">
				<i class="ri-calendar-event-fill"></i>
				<h3><?php esc_html_e( 'Criar Evento', 'apollo-events' ); ?></h3>
			</div>

			<form id="apl-event-create" autocomplete="off" novalidate>

				<!-- ═══ BÁSICO ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-information-line"></i> <?php esc_html_e( 'Informações Básicas', 'apollo-events' ); ?></legend>

					<div class="apl-form__field">
						<label for="ef_title"><?php esc_html_e( 'Título do Evento', 'apollo-events' ); ?> <span class="req">*</span></label>
						<input type="text" id="ef_title" name="title" required maxlength="200" placeholder="<?php esc_attr_e( 'Nome do evento', 'apollo-events' ); ?>" class="apollo-input">
					</div>

					<div class="apl-form__field">
						<label for="ef_content"><?php esc_html_e( 'Descrição', 'apollo-events' ); ?></label>
						<textarea id="ef_content" name="content" rows="4" placeholder="<?php esc_attr_e( 'Descreva o evento...', 'apollo-events' ); ?>" class="apollo-textarea"></textarea>
					</div>
				</fieldset>

				<!-- ═══ DATA / HORA ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-time-line"></i> <?php esc_html_e( 'Data e Horário', 'apollo-events' ); ?></legend>

					<div class="apl-form__row">
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_start_date"><?php esc_html_e( 'Data Início', 'apollo-events' ); ?> <span class="req">*</span></label>
							<input type="date" id="ef_start_date" name="start_date" required data-apollo-date-picker="true">
						</div>
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_end_date"><?php esc_html_e( 'Data Fim', 'apollo-events' ); ?></label>
							<input type="date" id="ef_end_date" name="end_date" data-apollo-date-picker="true">
						</div>
					</div>

					<div class="apl-form__row">
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_start_time"><?php esc_html_e( 'Hora Início', 'apollo-events' ); ?></label>
							<input type="time" id="ef_start_time" name="start_time" data-apollo-date-picker="true">
						</div>
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_end_time"><?php esc_html_e( 'Hora Fim', 'apollo-events' ); ?></label>
							<input type="time" id="ef_end_time" name="end_time" data-apollo-date-picker="true">
						</div>
					</div>
				</fieldset>

				<!-- ═══ LOCAL ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-map-pin-line"></i> <?php esc_html_e( 'Local', 'apollo-events' ); ?></legend>

					<div class="apl-form__field">
						<label for="ef_loc_id"><?php esc_html_e( 'Local do Evento', 'apollo-events' ); ?></label>
						<div class="lu-search-wrap">
							<i class="ri-search-line"></i>
							<input type="text" class="apollo-input lu-search" id="ef_loc_search" placeholder="<?php esc_attr_e( 'Buscar local...', 'apollo-events' ); ?>">
						</div>
						<select id="ef_loc_id" name="loc_id">
							<option value=""><?php esc_html_e( 'Selecione um local...', 'apollo-events' ); ?></option>
							<?php foreach ( $locals as $loc ) : ?>
								<option value="<?php echo esc_attr( (string) $loc->ID ); ?>"><?php echo esc_html( $loc->post_title ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php if ( current_user_can( 'manage_options' ) ) : ?>
							<button type="button" class="apl-quick-add" id="btnOpenLocModal">
								<i class="ri-add-circle-line"></i> <?php esc_html_e( 'Cadastrar novo Local +', 'apollo-events' ); ?>
							</button>
						<?php endif; ?>
					</div>
				</fieldset>

				<!-- ═══ LINEUP (Drag & Drop Timetable Builder) ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-disc-line"></i> <?php esc_html_e( 'Lineup', 'apollo-events' ); ?></legend>

					<div class="apl-form__field">
						<label><?php esc_html_e( 'DJs', 'apollo-events' ); ?></label>
						<div class="lu-builder" id="lineup-builder">
							<div class="lu-search-wrap">
								<i class="ri-search-line"></i>
								<input type="text" class="apollo-input lu-search" placeholder="<?php esc_attr_e( 'Buscar DJ...', 'apollo-events' ); ?>">
							</div>
							<div class="lu-options">
								<?php foreach ( $djs as $dj ) :
									$dj_thumb = get_the_post_thumbnail_url( $dj->ID, 'thumbnail' ) ?: '';
								?>
									<div class="lu-opt"
										 data-id="<?php echo esc_attr( (string) $dj->ID ); ?>"
										 data-name="<?php echo esc_attr( $dj->post_title ); ?>"
										 data-thumb="<?php echo esc_url( $dj_thumb ); ?>">
										<?php if ( $dj_thumb ) : ?>
											<img src="<?php echo esc_url( $dj_thumb ); ?>" class="lu-opt__thumb" alt="">
										<?php endif; ?>
										<?php echo esc_html( $dj->post_title ); ?>
									</div>
								<?php endforeach; ?>
							</div>

							<div class="lu-selected"
								 data-empty="<?php esc_attr_e( 'Clique em um DJ acima para adicionar ao lineup', 'apollo-events' ); ?>">
							</div>

							<!-- Hidden fields synced by JS -->
							<input type="hidden" name="dj_ids" class="lu-hidden-ids" value="[]">
							<input type="hidden" name="dj_slots" class="lu-hidden-slots" value="[]">
						</div>

						<?php if ( current_user_can( 'manage_options' ) ) : ?>
							<button type="button" class="apl-quick-add" id="btnOpenDjModal">
								<i class="ri-add-circle-line"></i> <?php esc_html_e( 'Cadastrar novo DJ +', 'apollo-events' ); ?>
							</button>
						<?php endif; ?>
					</div>
				</fieldset>

				<!-- ═══ INGRESSOS ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-ticket-line"></i> <?php esc_html_e( 'Ingressos', 'apollo-events' ); ?></legend>

					<div class="apl-form__row">
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_ticket_url"><?php esc_html_e( 'URL dos Ingressos', 'apollo-events' ); ?></label>
							<input type="url" id="ef_ticket_url" name="ticket_url" placeholder="https://..." class="apollo-input">
						</div>
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_ticket_price"><?php esc_html_e( 'Preço', 'apollo-events' ); ?></label>
							<input type="text" id="ef_ticket_price" name="ticket_price" placeholder="R$ 50,00" class="apollo-input">
						</div>
					</div>

					<div class="apl-form__row">
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_coupon_code"><?php esc_html_e( 'Código de Cupom', 'apollo-events' ); ?></label>
							<input type="text" id="ef_coupon_code" name="coupon_code" placeholder="APOLLO20" class="apollo-input">
						</div>
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_list_url"><?php esc_html_e( 'URL Lista Amiga', 'apollo-events' ); ?></label>
							<input type="url" id="ef_list_url" name="list_url" placeholder="https://..." class="apollo-input">
						</div>
					</div>
				</fieldset>

				<!-- ═══ MÍDIA ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-image-line"></i> <?php esc_html_e( 'Mídia', 'apollo-events' ); ?></legend>

					<div class="apl-form__field">
						<label for="ef_banner"><?php esc_html_e( 'Banner (ID da imagem)', 'apollo-events' ); ?></label>
						<input type="number" id="ef_banner" name="banner" min="0" placeholder="0" class="apollo-input">
						<span class="apl-form__hint"><?php esc_html_e( 'ID do anexo da biblioteca de mídia', 'apollo-events' ); ?></span>
					</div>

					<div class="apl-form__field">
						<label for="ef_video_url"><?php esc_html_e( 'Vídeo Promocional (YouTube / .mp4 .webm .mov)', 'apollo-events' ); ?></label>
						<input type="url" id="ef_video_url" name="video_url" placeholder="YouTube ou https://…/video.mp4|.webm|.mov" class="apollo-input">
					</div>

					<div class="apl-form__field">
						<label for="ef_gallery"><?php esc_html_e( 'Galeria (IDs separados por vírgula, máx. 3)', 'apollo-events' ); ?></label>
						<input type="text" id="ef_gallery" name="gallery" placeholder="100,101,102" class="apollo-input">
					</div>
				</fieldset>

				<!-- ═══ TAXONOMIAS ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-exchange-box-line"></i> <?php esc_html_e( 'Classificação', 'apollo-events' ); ?></legend>

					<div class="apl-form__row">
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_categories"><?php esc_html_e( 'Categorias', 'apollo-events' ); ?></label>
							<select id="ef_categories" name="categories" multiple size="4">
								<?php foreach ( $taxonomies['event_category'] as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_types"><?php esc_html_e( 'Tipos', 'apollo-events' ); ?></label>
							<select id="ef_types" name="types" multiple size="4">
								<?php foreach ( $taxonomies['event_type'] as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="apl-form__row">
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_tags"><?php esc_html_e( 'Tags', 'apollo-events' ); ?></label>
							<select id="ef_tags" name="tags" multiple size="4">
								<?php foreach ( $taxonomies['event_tag'] as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_sounds"><?php esc_html_e( 'Gêneros Musicais', 'apollo-events' ); ?></label>
							<select id="ef_sounds" name="sounds" multiple size="4">
								<?php foreach ( $taxonomies['sound'] as $term ) : ?>
									<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>

					<div class="apl-form__field">
						<label for="ef_seasons"><?php esc_html_e( 'Temporadas', 'apollo-events' ); ?></label>
						<select id="ef_seasons" name="seasons" multiple size="3">
							<?php foreach ( $taxonomies['season'] as $term ) : ?>
								<option value="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</fieldset>

				<!-- ═══ CONFIGURAÇÕES ═══ -->
				<fieldset class="apl-form__fieldset">
					<legend><i class="ri-compasses-2-line"></i> <?php esc_html_e( 'Configurações', 'apollo-events' ); ?></legend>

					<div class="apl-form__row">
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_privacy"><?php esc_html_e( 'Privacidade', 'apollo-events' ); ?></label>
							<select id="ef_privacy" name="privacy">
								<option value="public"><?php esc_html_e( 'Público', 'apollo-events' ); ?></option>
								<option value="private"><?php esc_html_e( 'Privado', 'apollo-events' ); ?></option>
								<option value="invite"><?php esc_html_e( 'Apenas Convidados', 'apollo-events' ); ?></option>
							</select>
						</div>
						<div class="apl-form__field apl-form__field--half">
							<label for="ef_event_status"><?php esc_html_e( 'Status', 'apollo-events' ); ?></label>
							<select id="ef_event_status" name="event_status">
								<option value="scheduled"><?php esc_html_e( 'Agendado', 'apollo-events' ); ?></option>
								<option value="ongoing"><?php esc_html_e( 'Save the date', 'apollo-events' ); ?></option>
								<option value="postponed"><?php esc_html_e( 'Adiado', 'apollo-events' ); ?></option>
								<option value="cancelled"><?php esc_html_e( 'Cancelado', 'apollo-events' ); ?></option>
								<option value="finished"><?php esc_html_e( 'Finalizado', 'apollo-events' ); ?></option>
							</select>
						</div>
					</div>
				</fieldset>

				<!-- ═══ SUBMIT ═══ -->
				<div class="apl-form__error" id="efError" style="display:none;"></div>

				<button type="submit" class="apl-form__submit" id="efSubmit">
					<i class="ri-send-plane-fill"></i>
					<span><?php esc_html_e( 'Criar Evento', 'apollo-events' ); ?></span>
				</button>

			</form>
		</div><!-- /.apl-form--event -->

		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<!-- ═══════════ MODAL: CADASTRAR DJ ═══════════ -->
		<div class="apl-modal" id="aplModalDj" aria-hidden="true" role="dialog">
			<div class="apl-modal__overlay" id="aplDjOverlay"></div>
			<div class="apl-modal__box" style="max-height:min(90vh,780px);overflow-y:auto;">
				<div class="apl-modal__header">
					<h4><i class="ri-disc-line"></i> <?php esc_html_e( 'Cadastrar Novo DJ', 'apollo-events' ); ?></h4>
					<button type="button" class="apl-modal__close" data-modal="aplModalDj" aria-label="Fechar">×</button>
				</div>
				<form id="aplDjQuickForm" novalidate>
					<p class="apl-modal__hint" style="margin:0 0 14px;font-size:12px;color:var(--muted);line-height:1.45;">
						<?php esc_html_e( 'Campos alinhados ao cartão CPT dj. Lista Out now! completa (várias faixas) fica no admin do DJ.', 'apollo-events' ); ?>
					</p>
					<div class="input-group">
						<input type="text" id="qd_name" name="title" class="apollo-input" placeholder=" " required>
						<label for="qd_name" class="apollo-label"><?php esc_html_e( 'Nome do DJ', 'apollo-events' ); ?> *</label>
					</div>
					<div class="input-group">
						<textarea id="qd_bio" name="bio_short" class="apollo-input" placeholder=" " rows="2" maxlength="280"></textarea>
						<label for="qd_bio" class="apollo-label"><?php esc_html_e( 'Biografia Curta (máx. 280 chars)', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="text" id="qd_instagram" name="instagram" class="apollo-input" placeholder=" ">
						<label for="qd_instagram" class="apollo-label"><?php esc_html_e( 'Instagram (@handle)', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="email" id="qd_booking" name="booking" class="apollo-input" placeholder=" ">
						<label for="qd_booking" class="apollo-label"><?php esc_html_e( 'E-mail de booking', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="url" id="qd_soundcloud" name="soundcloud" class="apollo-input" placeholder=" ">
						<label for="qd_soundcloud" class="apollo-label"><?php esc_html_e( 'SoundCloud (URL)', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="url" id="qd_spotify" name="spotify" class="apollo-input" placeholder=" ">
						<label for="qd_spotify" class="apollo-label"><?php esc_html_e( 'Spotify (URL)', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="url" id="qd_bandcamp" name="bandcamp" class="apollo-input" placeholder=" ">
						<label for="qd_bandcamp" class="apollo-label"><?php esc_html_e( 'Bandcamp (URL)', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="text" id="qd_genres" name="genres" class="apollo-input" placeholder=" ">
						<label for="qd_genres" class="apollo-label"><?php esc_html_e( 'Estilos (vírgula)', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="url" id="qd_kit" name="media_kit_url" class="apollo-input" placeholder=" ">
						<label for="qd_kit" class="apollo-label"><?php esc_html_e( 'Kit Promo — Drive URL', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="text" id="qd_track_title" name="track_title" class="apollo-input" placeholder=" ">
						<label for="qd_track_title" class="apollo-label"><?php esc_html_e( 'Out now! — título da 1ª faixa', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="url" id="qd_track_url" name="track_url" class="apollo-input" placeholder=" ">
						<label for="qd_track_url" class="apollo-label"><?php esc_html_e( 'Out now! — URL da faixa', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="text" id="qd_track_duration" name="track_duration" class="apollo-input" placeholder=" ">
						<label for="qd_track_duration" class="apollo-label"><?php esc_html_e( 'Duração (6:12)', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="text" id="qd_track_year" name="track_year" class="apollo-input" placeholder=" " maxlength="4">
						<label for="qd_track_year" class="apollo-label"><?php esc_html_e( 'Ano de postagem', 'apollo-events' ); ?></label>
					</div>
					<div id="aplDjMsg" class="apl-modal__msg" style="display:none;"></div>
					<button type="submit" class="apl-modal__submit">
						<i class="ri-save-line"></i> <span><?php esc_html_e( 'Salvar DJ', 'apollo-events' ); ?></span>
					</button>
				</form>
			</div>
		</div>

		<?php endif; ?>

		<?php if ( current_user_can( 'manage_options' ) ) : ?>
		<!-- ═══════════ MODAL: CADASTRAR LOCAL ═══════════ -->
		<div class="apl-modal" id="aplModalLoc" aria-hidden="true" role="dialog">
			<div class="apl-modal__overlay" id="aplLocOverlay"></div>
			<div class="apl-modal__box">
				<div class="apl-modal__header">
					<h4><i class="ri-map-pin-line"></i> <?php esc_html_e( 'Cadastrar Novo Local', 'apollo-events' ); ?></h4>
					<button type="button" class="apl-modal__close" data-modal="aplModalLoc" aria-label="Fechar">×</button>
				</div>
				<form id="aplLocQuickForm" novalidate>
					<div class="input-group">
						<input type="text" id="ql_name" name="title" class="apollo-input" placeholder=" " required>
						<label for="ql_name" class="apollo-label"><?php esc_html_e( 'Nome do Local', 'apollo-events' ); ?> *</label>
					</div>
					<div class="input-group">
						<input type="text" id="ql_address" name="address" class="apollo-input" placeholder=" ">
						<label for="ql_address" class="apollo-label"><?php esc_html_e( 'Endereço (Rua, Nº)', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="text" id="ql_city" name="city" class="apollo-input" placeholder=" ">
						<label for="ql_city" class="apollo-label"><?php esc_html_e( 'Cidade', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="text" id="ql_state" name="state" class="apollo-input" placeholder=" " maxlength="2">
						<label for="ql_state" class="apollo-label"><?php esc_html_e( 'Estado (UF)', 'apollo-events' ); ?></label>
					</div>
					<div class="input-group">
						<input type="number" id="ql_capacity" name="capacity" class="apollo-input" placeholder=" " min="0">
						<label for="ql_capacity" class="apollo-label"><?php esc_html_e( 'Capacidade (pessoas)', 'apollo-events' ); ?></label>
					</div>
					<div id="aplLocMsg" class="apl-modal__msg" style="display:none;"></div>
					<button type="submit" class="apl-modal__submit">
						<i class="ri-save-line"></i> <span><?php esc_html_e( 'Salvar Local', 'apollo-events' ); ?></span>
					</button>
				</form>
			</div>
		</div>
		<?php endif; ?>

		<?php
		// Enqueue lineup builder assets via WordPress dependency management
		wp_enqueue_style( 'apollo-lineup-builder' );
		wp_enqueue_script( 'apollo-lineup-builder' );
		?>

		<style>
		.apl-form--event { max-width: 640px; }
		.apl-form__header { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
		.apl-form__header i { font-size: 22px; color: var(--primary, FF9820); }
		.apl-form__header h3 { margin: 0; font-size: 18px; font-weight: 700; color: var(--txt, #fff); }
		.apl-form__fieldset {
			border: 1px solid var(--border, rgba(var(--rgb-t),.08));
			border-radius: 12px;
			padding: 16px;
			margin: 0 0 16px;
		}
		.apl-form__fieldset legend {
			display: flex; align-items: center; gap: 6px;
			font-size: 14px; font-weight: 600; color: var(--txt-muted, #aaa);
			padding: 0 8px;
		}
		.apl-form__fieldset legend i { font-size: 16px; }
		.apl-form__field { margin-bottom: 12px; }
		.apl-form__field label {
			display: block; font-size: 13px; font-weight: 600;
			color: var(--txt, #fff); margin-bottom: 4px;
		}
		.apl-form__field .req { color: #ef4444; }
		.apl-form__field input,
		.apl-form__field select,
		.apl-form__field textarea {
			width: 100%; padding: 10px 12px;
			background: var(--bg-card, #1a1a2e);
			border: 1px solid var(--border, rgba(var(--rgb-t),.08));
			border-radius: 8px; color: var(--txt, #fff);
			font-size: 14px; transition: border-color .2s;
		}
		.apl-form__field input:focus,
		.apl-form__field select:focus,
		.apl-form__field textarea:focus {
			outline: none; border-color: var(--primary, FF9820);
		}
		.apl-form__field select[multiple] { min-height: 80px; }
		.apl-form__hint { display: block; font-size: 11px; color: var(--txt-muted, #888); margin-top: 4px; }
		.apl-form__row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
		@media (max-width: 480px) { .apl-form__row { grid-template-columns: 1fr; } }
		.apl-form__error {
			background: rgba(239,68,68,.12); color: #ef4444;
			padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 12px;
		}
		.apl-form__submit {
			display: flex; align-items: center; justify-content: center; gap: 8px;
			width: 100%; padding: 12px; border: none; border-radius: 10px;
			background: var(--primary, FF9820); color: #fff; font-size: 15px;
			font-weight: 700; cursor: pointer; transition: opacity .2s;
		}
		.apl-form__submit:hover { opacity: .9; }
		.apl-form__submit:disabled { opacity: .5; cursor: not-allowed; }
		.apl-form__submit i { font-size: 18px; }
		/* Quick-add button */
		.apl-quick-add {
			display: inline-flex; align-items: center; gap: 5px;
			margin-top: 6px; border: none; background: none; padding: 0;
			color: var(--primary, FF9820); font-size: 12px; font-weight: 600;
			cursor: pointer; text-decoration: underline; text-underline-offset: 3px;
		}
		.apl-quick-add i { font-size: 15px; }
		/* Modal */
		.apl-modal { display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; }
		.apl-modal.is-open { display: flex; }
		.apl-modal__overlay { position: absolute; inset: 0; background: rgba(var(--rgb-d),.65); backdrop-filter: blur(4px); }
		.apl-modal__box {
			position: relative; z-index: 1; width: \min(480px,92vw);
			background: var(--bg-card, #12121e); border-radius: 14px;
			padding: 24px; box-shadow: 0 8px 40px rgba(var(--rgb-d),.6);
		}
		.apl-modal__header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
		.apl-modal__header h4 { margin: 0; display: flex; align-items: center; gap: 8px; font-size: 16px; color: var(--txt, #fff); }
		.apl-modal__close {
			border: none; background: none; color: var(--txt-muted, #aaa);
			font-size: 22px; cursor: pointer; padding: 0 4px; line-height: 1;
		}
		.apl-modal__close:hover { color: var(--txt, #fff); }
		.apl-modal__msg { padding: 8px 12px; border-radius: 6px; font-size: 13px; margin-bottom: 12px; }
		.apl-modal__msg.ok { background: rgba(34,197,94,.12); color: #22c55e; }
		.apl-modal__msg.err { background: rgba(239,68,68,.12); color: #ef4444; }
		.apl-modal__submit {
			display: flex; align-items: center; justify-content: center; gap: 6px;
			width: 100%; padding: 11px; border: none; border-radius: 8px;
			background: var(--primary, FF9820); color: #fff; font-size: 14px;
			font-weight: 700; cursor: pointer; margin-top: 14px;
		}
		.apl-modal__submit:disabled { opacity: .5; cursor: not-allowed; }
		</style>

		<script>
		(function(){
			'use strict';
			var REST  = '<?php echo esc_js( $rest_url ); ?>';
			var NONCE = '<?php echo esc_js( $nonce ); ?>';
			var form  = document.getElementById('apl-event-create');
			var errEl = document.getElementById('efError');
			var btn   = document.getElementById('efSubmit');

			if (!form) return;

			form.addEventListener('submit', async function(e) {
				e.preventDefault();
				errEl.style.display = 'none';
				btn.disabled = true;
				btn.querySelector('span').textContent = 'Criando...';

				try {
					var data = {};

					/* Always submit as draft from frontend */
					data.post_status = 'draft';

					/* Simple text/url/date/time fields */
					['title','content','start_date','end_date','start_time','end_time',
					'ticket_url','ticket_price','coupon_code','list_url','video_url',
					'privacy','event_status'].forEach(function(k) {
						var el = form.querySelector('[name="'+k+'"]');
						if (el && el.value.\trim()) data[k] = el.value.\trim();
					});

					/* Number: banner, loc_id */
					['banner','loc_id'].forEach(function(k) {
						var el = form.querySelector('[name="'+k+'"]');
						if (el && el.value) data[k] = parseInt(el.value, 10) || 0;
					});

					/* Multi-select arrays: categories, types, tags, sounds, seasons */
					['categories','types','tags','sounds','seasons'].forEach(function(k) {
						var el = form.querySelector('[name="'+k+'"]');
						if (!el) return;
						var vals = Array.from(el.selectedOptions).map(function(o){ return o.value; });
						if (vals.length) data[k] = vals;
					});

					/* DJ IDs + Slots (from lineup builder hidden fields) */
					var hiddenIds = form.querySelector('.lu-hidden-ids');
					if (hiddenIds && hiddenIds.value) {
						try { var ids = JSON.parse(hiddenIds.value); if (ids.length) data.dj_ids = ids; } catch(ex) {}
					}
					var hiddenSlots = form.querySelector('.lu-hidden-slots');
					if (hiddenSlots && hiddenSlots.value) {
						try { var sl = JSON.parse(hiddenSlots.value); if (sl.length) data.dj_slots = sl; } catch(ex) {}
					}

					/* Gallery: comma-separated → array of ints */
					var galEl = form.querySelector('[name="gallery"]');
					if (galEl && galEl.value.\trim()) {
						data.gallery = galEl.value.split(',').map(function(v){ return parseInt(v.\trim(),10); }).filter(function(n){ return n > 0; });
					}

					if (!data.title) throw new Error('Título é obrigatório.');
					if (!data.start_date) throw new Error('Data de início é obrigatória.');

					var res = await fetch(REST, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
						credentials: 'same-origin',
						body: JSON.stringify(data)
					});

					var result = await res.json();

					if (res.ok && (result.id || result.permalink)) {
						btn.querySelector('span').textContent = 'Evento enviado para revisão!';
						btn.style.background = '#22c55e';
						setTimeout(function(){
							if (result.permalink) {
								window.location.href = result.permalink;
							} else {
								form.reset();
								btn.disabled = false;
								btn.querySelector('span').textContent = 'Criar Evento';
								btn.style.background = '';
							}
						}, 2000);
					} else {
						throw new Error(result.error || result.message || 'Erro ao criar evento.');
					}
				} catch(err) {
					errEl.textContent = err.message;
					errEl.style.display = '';
					btn.disabled = false;
					btn.querySelector('span').textContent = 'Criar Evento';
				}
			});

			/* ── Modal helpers ── */
			function openModal(id) {
				var m = document.getElementById(id);
				if (m) { m.classList.add('is-open'); m.removeAttribute('aria-hidden'); }
			}
			function closeModal(id) {
				var m = document.getElementById(id);
				if (m) { m.classList.remove('is-open'); m.setAttribute('aria-hidden','true'); }
			}
			document.querySelectorAll('.apl-modal__close').forEach(function(btn){
				btn.addEventListener('click', function(){ closeModal(btn.dataset.modal); });
			});
			['aplDjOverlay','aplLocOverlay'].forEach(function(id){
				var el = document.getElementById(id);
				if (el) el.addEventListener('click', function(){
					closeModal(el.closest('.apl-modal').id);
				});
			});

			var btnDjOpen = document.getElementById('btnOpenDjModal');
			if (btnDjOpen) btnDjOpen.addEventListener('click', function(){ openModal('aplModalDj'); });
			var btnLocOpen = document.getElementById('btnOpenLocModal');
			if (btnLocOpen) btnLocOpen.addEventListener('click', function(){ openModal('aplModalLoc'); });

			/* ── Quick-add DJ ── */
			var djForm = document.getElementById('aplDjQuickForm');
			if (djForm) djForm.addEventListener('submit', async function(e){
				e.preventDefault();
				var sb = djForm.querySelector('[type=submit]'), msg = document.getElementById('aplDjMsg');
				sb.disabled = true; sb.querySelector('span').textContent = 'Salvando...';
				msg.style.display = 'none';
				try {
					var d = { name: '' };
					[
						'title', 'bio_short', 'instagram', 'booking', 'soundcloud', 'spotify',
						'bandcamp', 'genres', 'media_kit_url',
						'track_title', 'track_url', 'track_duration', 'track_year'
					].forEach(function(k){
						var el = djForm.querySelector('[name="'+k+'"]');
						if (el && el.value.trim()) d[k] = el.value.trim();
					});
					if (!d.title) throw new Error('Nome do DJ obrigatório.');
					d.name = d.title;
					var r = await fetch('<?php echo esc_js( rest_url( 'apollo/v1/djs' ) ); ?>', {
						method: 'POST',
						headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
						credentials: 'same-origin',
						body: JSON.stringify(d)
					});
					var res = await r.json();
					if (!r.ok) throw new Error(res.message || 'Erro ao salvar DJ.');
					/* Add DJ chip to lineup builder options + auto-select */
					var optList = document.querySelector('#lineup-builder .lu-options');
					if (optList) {
						var chip = document.createElement('div');
						chip.className = 'lu-opt';
						chip.setAttribute('data-id', res.id);
						chip.setAttribute('data-name', d.title);
						chip.setAttribute('data-thumb', '');
						chip.textContent = d.title;
						optList.appendChild(chip);
						chip.click(); // auto-add to lineup
					}
					msg.className = 'apl-modal__msg ok'; msg.textContent = '✓ DJ cadastrado e selecionado!';
					msg.style.display = '';
					djForm.reset();
					setTimeout(function(){ closeModal('aplModalDj'); }, 1400);
				} catch(err) {
					msg.className = 'apl-modal__msg err'; msg.textContent = err.message; msg.style.display = '';
					sb.disabled = false; sb.querySelector('span').textContent = 'Salvar DJ';
				}
			});

			/* ── Quick-add Local ── */
			var locForm = document.getElementById('aplLocQuickForm');
			if (locForm) locForm.addEventListener('submit', async function(e){
				e.preventDefault();
				var sb = locForm.querySelector('[type=submit]'), msg = document.getElementById('aplLocMsg');
				sb.disabled = true; sb.querySelector('span').textContent = 'Salvando...';
				msg.style.display = 'none';
				try {
					var d = {};
					['title','address','city','state'].forEach(function(k){
						var el = locForm.querySelector('[name="'+k+'"]');
						if (el && el.value.\trim()) d[k] = el.value.\trim();
					});
					var capEl = locForm.querySelector('[name="capacity"]');
					if (capEl && capEl.value) d.capacity = parseInt(capEl.value, 10) || 0;
					if (!d.title) throw new Error('Nome do Local obrigatório.');
					var r = await fetch('<?php echo esc_js( rest_url( 'apollo/v1/local' ) ); ?>', {
						method: 'POST',
						headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
						credentials: 'same-origin',
						body: JSON.stringify(d)
					});
					var res = await r.json();
					if (!r.ok) throw new Error(res.message || 'Erro ao salvar Local.');
					/* Add new option to the main LOC select */
					var sel = document.getElementById('ef_loc_id');
					var opt = document.createElement('option');
					opt.value = res.id; opt.textContent = d.title; opt.selected = true;
					sel.appendChild(opt);
					msg.className = 'apl-modal__msg ok'; msg.textContent = '✓ Local cadastrado e selecionado!';
					msg.style.display = '';
					locForm.reset();
					setTimeout(function(){ closeModal('aplModalLoc'); }, 1400);
				} catch(err) {
					msg.className = 'apl-modal__msg err'; msg.textContent = err.message; msg.style.display = '';
					sb.disabled = false; sb.querySelector('span').textContent = 'Salvar Local';
				}
			});

			/* ── Loc search filter ── */
			var locSearch = document.getElementById('ef_loc_search');
			var locSelect = document.getElementById('ef_loc_id');
			if (locSearch && locSelect) {
				var locOpts = Array.from(locSelect.options);
				locSearch.addEventListener('input', function() {
					var q = this.value.toLowerCase().trim();
					locOpts.forEach(function(o) {
						if (!o.value) return; // keep placeholder
						o.style.display = (!q || o.textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
					});
				});
			}

			/* ── Init Lineup Builder ── */
			if (typeof ApolloLineupBuilder !== 'undefined') {
				var djData = [];
				document.querySelectorAll('#lineup-builder .lu-opt').forEach(function(el) {
					djData.push({
						id: parseInt(el.getAttribute('data-id'), 10),
						name: el.getAttribute('data-name'),
						thumb: el.getAttribute('data-thumb') || ''
					});
				});
				new ApolloLineupBuilder('#lineup-builder', djData, []);
			}
		})();
		</script>
		<?php
	}

	/**
	 * Helper: Get taxonomy terms safely
	 *
	 * @param string $taxonomy Taxonomy slug
	 * @return array Array of WP_Term objects
	 */
	private function get_taxonomy_terms( string $taxonomy ): array {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return array();
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			)
		);

		return is_wp_error( $terms ) ? array() : $terms;
	}
}