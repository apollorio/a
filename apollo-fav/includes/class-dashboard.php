<?php

/**
 * Dashboard — Página painel/favoritos
 *
 * Cria a página de favoritos dentro do dashboard do usuário.
 * URL: site.com/painel/favoritos
 * Conforme registry: apollo-dashboard → pages → painel/favoritos
 *
 * Lista os favoritos do usuário com filtros por tipo de CPT:
 * Eventos, DJs, Anúncios, Locais, Hubs.
 *
 * @package Apollo\Fav
 */

declare(strict_types=1);

namespace Apollo\Fav;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dashboard {


	/**
	 * Labels amigáveis para cada CPT nos filtros do dashboard.
	 *
	 * @var array<string, string>
	 */
	private array $type_labels = array(
		'event'      => 'Eventos',
		'dj'         => 'DJs',
		'classified' => 'Anúncios',
		'loc'        => 'Locais',
		'hub'        => 'Hubs',
		'supplier'   => 'Fornecedores',
		'doc'        => 'Documentos',
	);

	/**
	 * Ícones Remix Icon para cada CPT.
	 *
	 * @var array<string, string>
	 */
	private array $type_icons = array(
		'event'      => 'ri-calendar-event-line',
		'dj'         => 'ri-disc-line',
		'classified' => 'ri-exchange-box-line',
		'loc'        => 'ri-map-pin-line',
		'hub'        => 'ri-links-line',
		'supplier'   => 'ri-store-2-line',
		'doc'        => 'ri-file-text-line',
	);

	/**
	 * Inicializa o módulo do dashboard.
	 */
	public function init(): void {
		// Registra shortcode para listagem de favoritos (conforme registry)
		add_shortcode( 'apollo_my_favs', array( $this, 'shortcode_my_favs' ) );

		// Registra shortcode para botão de favoritar
		add_shortcode( 'apollo_fav', array( $this, 'shortcode_fav_button' ) );

		// Hook para injetar conteúdo na página do dashboard painel/favoritos
		add_filter( 'apollo/dashboard/page_content', array( $this, 'dashboard_page' ), 10, 2 );

		// AJAX para carregar mais favoritos (paginação)
		add_action( 'wp_ajax_apollo_fav_load_more', array( $this, 'ajax_load_more' ) );

		// Hook para registrar widget no dashboard
		add_action( 'apollo/dashboard/widgets', array( $this, 'register_widget' ) );
	}

	/**
	 * Shortcode [apollo_my_favs] — Lista de favoritos do usuário.
	 *
	 * Atributos:
	 * - post_type: string (event|dj|classified|loc|hub) - filtro por tipo
	 * - limit: int - quantidade por página (default 12)
	 *
	 * @param array $atts  Atributos do shortcode.
	 * @return string       HTML da lista de favoritos.
	 */
	public function shortcode_my_favs( array $atts = array() ): string {
		if ( ! is_user_logged_in() ) {
			return '<div class="apollo-fav-login-notice">'
				. '<p>' . esc_html__( 'Faça login para ver seus favoritos.', 'apollo-fav' ) . '</p>'
				. '<a href="' . esc_url( home_url( '/acesso' ) ) . '" class="apollo-btn">'
				. esc_html__( 'Entrar', 'apollo-fav' ) . '</a>'
				. '</div>';
		}

		$atts = shortcode_atts(
			array(
				'post_type' => '',
				'limit'     => 12,
			),
			$atts,
			'apollo_my_favs'
		);

		$user_id   = get_current_user_id();
		$post_type = sanitize_text_field( $atts['post_type'] );
		$limit     = absint( $atts['limit'] );

		return $this->render_favs_list( $user_id, $post_type ?: null, $limit, 0 );
	}

	/**
	 * Shortcode [apollo_fav] — Botão de favoritar.
	 *
	 * @param array $atts  Atributos (post_id).
	 * @return string       HTML do botão.
	 */
	public function shortcode_fav_button( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'post_id' => 0,
			),
			$atts,
			'apollo_fav'
		);

		$post_id = absint( $atts['post_id'] ) ?: get_the_ID();

		return apollo_fav_button( $post_id, false ) ?: '';
	}

	/**
	 * Renderiza a lista completa de favoritos com filtros.
	 *
	 * @param int         $user_id    ID do usuário.
	 * @param string|null $post_type  Filtro por tipo.
	 * @param int         $limit      Limite.
	 * @param int         $offset     Offset.
	 * @return string                  HTML renderizado.
	 */
	public function render_favs_list( int $user_id, ?string $post_type, int $limit, int $offset ): string {
		ob_start();
		?>
		<div class="apollo-favs-dashboard" data-a-user="<?php echo esc_attr( (string) $user_id ); ?>" data-a-component="favs-dashboard">

			<!-- Filtros por tipo de CPT -->
			<div class="apollo-favs-filters">
				<button class="apollo-favs-filter <?php echo ! $post_type ? 'active' : ''; ?>"
					data-type="">
					<i class="ri-fire-line"></i>
					<?php esc_html_e( 'Todos', 'apollo-fav' ); ?>
					<span class="apollo-favs-count"><?php echo esc_html( (string) apollo_get_user_fav_total( $user_id ) ); ?></span>
				</button>

				<?php
				foreach ( $this->type_labels as $type => $label ) :
					$count = apollo_get_user_fav_count_by_type( $user_id, $type );
					if ( $count > 0 ) :
						?>
						<button class="apollo-favs-filter <?php echo $post_type === $type ? 'active' : ''; ?>"
							data-type="<?php echo esc_attr( $type ); ?>">
							<i class="<?php echo esc_attr( $this->type_icons[ $type ] ?? 'ri-file-line' ); ?>"></i>
							<?php echo esc_html( $label ); ?>
							<span class="apollo-favs-count"><?php echo esc_html( (string) $count ); ?></span>
						</button>
						<?php
					endif;
				endforeach;
				?>
			</div>

			<!-- Grid de favoritos -->
			<div class="apollo-favs-grid" data-limit="<?php echo esc_attr( (string) $limit ); ?>" data-offset="0">
				<?php
				$favs = apollo_get_user_favs( $user_id, $post_type, $limit, $offset );

				if ( empty( $favs ) ) {
					echo '<div class="apollo-favs-empty">';
					echo '<i class="ri-heart-add-line" style="font-size: 3rem; opacity: 0.3;"></i>';
					echo '<p>' . esc_html__( 'Nenhum favorito ainda. Explore e favorite conteúdos que você curte!', 'apollo-fav' ) . '</p>';
					echo '</div>';
				} else {
					foreach ( $favs as $fav ) {
                        echo $this->render_fav_card($fav); // phpcs:ignore
					}
				}
				?>
			</div>

			<!-- Botão Load More -->
			<?php if ( count( $favs ?? array() ) >= $limit ) : ?>
				<div class="apollo-favs-load-more">
					<button class="apollo-btn apollo-btn--outline" data-action="apollo-fav-load-more">
						<?php esc_html_e( 'Carregar mais', 'apollo-fav' ); ?>
					</button>
				</div>
			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Renderiza um card individual de favorito — estilo LinkedIn.
	 *
	 * @param object $fav  Objeto do favorito {post_id, post_type, created_at, post_title}.
	 * @return string       HTML do card.
	 */
	private function render_fav_card( object $fav ): string {
		$post_id = (int) $fav->post_id;
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_status !== 'publish' ) {
			return '';
		}

		$thumbnail = get_the_post_thumbnail_url( $post_id, 'medium' ) ?: '';
		$permalink = get_permalink( $post_id );
		$post_type = $fav->post_type;
		$label     = $this->type_labels[ $post_type ] ?? ucfirst( $post_type );
		$icon      = $this->type_icons[ $post_type ] ?? 'ri-file-line';
		$fav_count = apollo_get_fav_count( $post_id );
		$excerpt   = wp_trim_words( get_the_excerpt( $post_id ), 15, '...' );

		// Dados extras conforme o tipo de CPT
		$meta_html = $this->get_cpt_meta_html( $post_id, $post_type );

		ob_start();
		?>
		<article class="apollo-fav-card apollo-fav-card--<?php echo esc_attr( $post_type ); ?>">
			<?php if ( $thumbnail ) : ?>
				<a href="<?php echo esc_url( $permalink ); ?>" class="apollo-fav-card__image">
					<img src="<?php echo esc_url( $thumbnail ); ?>"
						alt="<?php echo esc_attr( $post->post_title ); ?>"
						loading="lazy" />
				</a>
			<?php endif; ?>

			<div class="apollo-fav-card__body">
				<!-- Badge de tipo -->
				<span class="apollo-fav-card__type">
					<i class="<?php echo esc_attr( $icon ); ?>"></i>
					<?php echo esc_html( $label ); ?>
				</span>

				<!-- Título -->
				<h3 class="apollo-fav-card__title">
					<a href="<?php echo esc_url( $permalink ); ?>">
						<?php echo esc_html( $post->post_title ); ?>
					</a>
				</h3>

				<!-- Excerpt -->
				<?php if ( $excerpt ) : ?>
					<p class="apollo-fav-card__excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>

				<!-- Meta info do CPT -->
				<?php if ( $meta_html ) : ?>
					<div class="apollo-fav-card__meta">
                        <?php echo $meta_html; // phpcs:ignore 
						?>
					</div>
				<?php endif; ?>

				<!-- Footer: favoritos + tempo -->
				<div class="apollo-fav-card__footer">
					<span class="apollo-fav-card__favcount">
						<i class="ri-fire-fill"></i>
						<?php echo esc_html( (string) $fav_count ); ?>
					</span>
					<span class="apollo-fav-card__time">
						<?php echo wp_kses_post( apollo_time_ago_html( $fav->created_at ) ); ?>
					</span>
					<?php apollo_fav_button( $post_id ); ?>
				</div>
			</div>
		</article>
		<?php
		return ob_get_clean();
	}

	/**
	 * Retorna HTML de meta info específica por CPT.
	 *
	 * @param int    $post_id    ID do post.
	 * @param string $post_type  Tipo de CPT.
	 * @return string             HTML de meta.
	 */
	private function get_cpt_meta_html( int $post_id, string $post_type ): string {
		$parts = array();

		switch ( $post_type ) {
			case 'event':
				$date = get_post_meta( $post_id, '_event_start_date', true );
				$time = get_post_meta( $post_id, '_event_start_time', true );
				if ( $date ) {
					$display = wp_date( 'd/m/Y', strtotime( $date ) );
					if ( $time ) {
						$display .= ' · ' . substr( $time, 0, 5 );
					}
					$parts[] = '<span><i class="ri-calendar-line"></i> ' . esc_html( $display ) . '</span>';
				}
				$loc_id = get_post_meta( $post_id, '_event_loc_id', true );
				if ( $loc_id ) {
					$loc_name = get_the_title( (int) $loc_id );
					if ( $loc_name ) {
						$parts[] = '<span><i class="ri-map-pin-line"></i> ' . esc_html( $loc_name ) . '</span>';
					}
				}
				break;

			case 'dj':
				$sounds = wp_get_post_terms( $post_id, 'sound', array( 'fields' => 'names' ) );
				if ( ! is_wp_error( $sounds ) && ! empty( $sounds ) ) {
					$parts[] = '<span><i class="ri-music-line"></i> ' . esc_html( implode( ', ', array_slice( $sounds, 0, 3 ) ) ) . '</span>';
				}
				break;

			case 'classified':
				$price = get_post_meta( $post_id, '_classified_price', true );
				if ( $price ) {
					$parts[] = '<span><i class="ri-money-dollar-circle-line"></i> R$ ' . esc_html( number_format( (float) $price, 2, ',', '.' ) ) . '</span>';
				}
				$condition = get_post_meta( $post_id, '_classified_condition', true );
				if ( $condition ) {
					$parts[] = '<span><i class="ri-checkbox-circle-line"></i> ' . esc_html( ucfirst( $condition ) ) . '</span>';
				}
				break;

			case 'loc':
				$city = get_post_meta( $post_id, '_loc_city', true );
				if ( $city ) {
					$parts[] = '<span><i class="ri-building-line"></i> ' . esc_html( $city ) . '</span>';
				}
				break;
		}

		return implode( ' ', $parts );
	}

	/**
	 * Injeta conteúdo de favoritos na página painel/favoritos do apollo-dashboard.
	 *
	 * @param string $content  Conteúdo atual da página.
	 * @param string $slug     Slug da sub-página do dashboard.
	 * @return string           Conteúdo modificado.
	 */
	public function dashboard_page( string $content, string $slug ): string {
		if ( $slug !== 'favoritos' ) {
			return $content;
		}

		if ( ! is_user_logged_in() ) {
			return $content;
		}

		$user_id   = get_current_user_id();
		$post_type = isset( $_GET['tipo'] ) ? sanitize_text_field( wp_unslash( $_GET['tipo'] ) ) : null;

		return $this->render_favs_list( $user_id, $post_type, 12, 0 );
	}

	/**
	 * AJAX handler para paginação infinita de favoritos.
	 */
	public function ajax_load_more(): void {
		check_ajax_referer( 'apollo_fav_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( array( 'message' => 'Não autenticado.' ), 403 );
		}

		$user_id   = get_current_user_id();
		$post_type = isset( $_POST['post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : null;
		$offset    = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
		$limit     = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 12;

		$favs = apollo_get_user_favs( $user_id, $post_type ?: null, $limit, $offset );

		$html = '';
		foreach ( $favs as $fav ) {
			$html .= $this->render_fav_card( $fav );
		}

		wp_send_json_success(
			array(
				'html'     => $html,
				'count'    => count( $favs ),
				'has_more' => count( $favs ) >= $limit,
			)
		);
	}

	/**
	 * Registra widget de favoritos no dashboard Apollo.
	 *
	 * @param array $widgets  Array de widgets registrados.
	 * @return array           Widgets com favoritos adicionado.
	 */
	public function register_widget( array $widgets ): array {
		if ( ! is_user_logged_in() ) {
			return $widgets;
		}

		$user_id = get_current_user_id();
		$total   = apollo_get_user_fav_total( $user_id );

		$widgets['apollo_favs'] = array(
			'title'    => __( 'Meus Favoritos', 'apollo-fav' ),
			'icon'     => 'ri-fire-line',
			'count'    => $total,
			'link'     => home_url( '/painel/favoritos' ),
			'priority' => 30,
		);

		return $widgets;
	}
}
