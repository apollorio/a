<?php
/**
 * Profile Integration — Aba "Meus Interesses" no perfil do usuário
 *
 * Integra com o sistema de perfil Apollo (/id/username) gerenciado pelo apollo-users.
 * Injeta uma aba "Favoritos" no perfil público mostrando o que o usuário curtiu.
 * Estilo: LinkedIn-style cards mostrando interesses do usuário.
 *
 * NOTA: Usa hooks do apollo-users para adicionar a tab.
 * O perfil é SEMPRE /id/username — NUNCA /user/username (conforme namingRules).
 *
 * @package Apollo\Fav
 */

declare(strict_types=1);

namespace Apollo\Fav;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Profile_Integration {

	/**
	 * Mapeamento de CPTs para labels de seção no perfil.
	 *
	 * @var array<string, array{label: string, icon: string, slug: string}>
	 */
	private array $sections = array(
		'event'      => array(
			'label' => 'Eventos',
			'icon'  => 'ri-calendar-event-line',
			'slug'  => 'eventos',
		),
		'dj'         => array(
			'label' => 'DJs',
			'icon'  => 'ri-disc-line',
			'slug'  => 'djs',
		),
		'classified' => array(
			'label' => 'Anúncios',
			'icon'  => 'ri-exchange-box-line',
			'slug'  => 'anuncios',
		),
		'loc'        => array(
			'label' => 'Locais',
			'icon'  => 'ri-map-pin-line',
			'slug'  => 'locais',
		),
		'hub'        => array(
			'label' => 'Hubs',
			'icon'  => 'ri-links-line',
			'slug'  => 'hubs',
		),
	);

	/**
	 * Inicializa a integração com o perfil.
	 */
	public function init(): void {
		// Hook para registrar tab no sistema de perfil do apollo-users
		add_filter( 'apollo/users/profile_tabs', array( $this, 'register_tab' ), 20 );

		// Hook para renderizar conteúdo da tab
		add_action( 'apollo/users/profile_tab_content', array( $this, 'render_tab_content' ), 10, 2 );

		// Hook alternativo — UsersWP nativo (caso exista)
		add_filter( 'uwp_profile_tabs', array( $this, 'uwp_register_tab' ), 30, 3 );

		// Adiciona contagem de favoritos ao perfil público
		add_filter( 'apollo/users/profile_stats', array( $this, 'add_profile_stats' ), 10, 2 );

		// Widget de favoritos no sidebar do perfil
		add_action( 'apollo/users/profile_sidebar', array( $this, 'render_sidebar_widget' ) );
	}

	/**
	 * Registra a tab "Favoritos" no perfil Apollo (/id/username).
	 *
	 * @param array $tabs  Array de tabs registradas.
	 * @return array        Tabs com a aba de favoritos.
	 */
	public function register_tab( array $tabs ): array {
		$tabs['favoritos'] = array(
			'title'    => __( 'Favoritos', 'apollo-fav' ),
			'icon'     => 'ri-fire-line',
			'priority' => 40,
			'public'   => true, // Visível para visitantes
			'callback' => array( $this, 'render_tab_content' ),
		);

		return $tabs;
	}

	/**
	 * Registra a tab no UsersWP (compatibilidade).
	 *
	 * @param array    $tabs    Tabs existentes.
	 * @param \WP_User $user    Usuário do perfil.
	 * @param string   $context Contexto (profile, account).
	 * @return array             Tabs atualizadas.
	 */
	public function uwp_register_tab( array $tabs, $user = null, string $context = '' ): array {
		$tabs['favoritos'] = array(
			'title'      => __( 'Favoritos', 'apollo-fav' ),
			'count'      => $user ? apollo_get_user_fav_total( (int) $user->ID ) : 0,
			'icon'       => 'fas fa-heart',
			'position'   => 40,
			'is_default' => false,
		);

		return $tabs;
	}

	/**
	 * Renderiza o conteúdo da tab de favoritos no perfil.
	 * Estilo LinkedIn: cards organizados por seção.
	 *
	 * @param string $tab_slug  Slug da tab ativa.
	 * @param int    $user_id   ID do usuário do perfil.
	 */
	public function render_tab_content( string $tab_slug, int $user_id ): void {
		if ( $tab_slug !== 'favoritos' ) {
			return;
		}

		// Verifica privacidade — respeita configuração do usuário
		$privacy = get_user_meta( $user_id, '_apollo_privacy_profile', true ) ?: 'public';

		// Se perfil é privado e visitante não é o dono ou admin
		if ( $privacy === 'private' && get_current_user_id() !== $user_id && ! current_user_can( 'manage_options' ) ) {
			echo '<div class="apollo-profile-private">';
			echo '<i class="ri-lock-line"></i>';
			echo '<p>' . esc_html__( 'Os favoritos deste perfil são privados.', 'apollo-fav' ) . '</p>';
			echo '</div>';
			return;
		}

		// Se "members" e visitante não é logado
		if ( $privacy === 'members' && ! is_user_logged_in() ) {
			echo '<div class="apollo-profile-private">';
			echo '<i class="ri-group-line"></i>';
			echo '<p>' . esc_html__( 'Faça login para ver os favoritos deste perfil.', 'apollo-fav' ) . '</p>';
			echo '</div>';
			return;
		}

		$total = apollo_get_user_fav_total( $user_id );

		if ( $total === 0 ) {
			echo '<div class="apollo-profile-favs-empty">';
			echo '<i class="ri-heart-add-line" style="font-size: 2rem; opacity: 0.3;"></i>';
			echo '<p>' . esc_html__( 'Nenhum favorito ainda.', 'apollo-fav' ) . '</p>';
			echo '</div>';
			return;
		}

		// Renderiza seções por tipo de CPT
		echo '<div class="apollo-profile-favs">';

		foreach ( $this->sections as $type => $section ) {
			$count = apollo_get_user_fav_count_by_type( $user_id, $type );

			if ( $count === 0 ) {
				continue;
			}

			// Busca favoritos deste tipo (máximo 6 no perfil público, com link "ver mais")
			$favs = apollo_get_user_favs( $user_id, $type, 6, 0 );

			if ( empty( $favs ) ) {
				continue;
			}

			echo '<div class="apollo-profile-favs__section">';
			echo '<h4 class="apollo-profile-favs__section-title">';
			echo '<i class="' . esc_attr( $section['icon'] ) . '"></i> ';
			echo esc_html( $section['label'] );
			echo ' <span class="apollo-count">(' . esc_html( (string) $count ) . ')</span>';
			echo '</h4>';

			echo '<div class="apollo-profile-favs__grid">';

			foreach ( $favs as $fav ) {
				$this->render_linkedin_card( $fav );
			}

			echo '</div>'; // .grid

			// Link "ver mais" se houver mais favoritos deste tipo
			if ( $count > 6 ) {
				echo '<a href="' . esc_url( add_query_arg( 'tipo', $type, home_url( '/painel/favoritos' ) ) ) . '" class="apollo-profile-favs__more">';
				echo esc_html__( 'Ver todos', 'apollo-fav' ) . ' →';
				echo '</a>';
			}

			echo '</div>'; // .section
		}

		echo '</div>'; // .apollo-profile-favs
	}

	/**
	 * Renderiza um card estilo LinkedIn para o perfil.
	 * Formato compacto com thumbnail, título e meta.
	 *
	 * @param object $fav  Objeto do favorito.
	 */
	private function render_linkedin_card( object $fav ): void {
		$post_id = (int) $fav->post_id;
		$post    = get_post( $post_id );

		if ( ! $post || $post->post_status !== 'publish' ) {
			return;
		}

		$thumb     = get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ?: '';
		$permalink = get_permalink( $post_id );
		$title     = $post->post_title;
		$type      = $fav->post_type;
		$section   = $this->sections[ $type ] ?? null;

		?>
		<a href="<?php echo esc_url( $permalink ); ?>" class="apollo-linkedin-card">
			<!-- Thumbnail -->
			<div class="apollo-linkedin-card__thumb">
				<?php if ( $thumb ) : ?>
					<img src="<?php echo esc_url( $thumb ); ?>"
						alt="<?php echo esc_attr( $title ); ?>"
						loading="lazy" />
				<?php else : ?>
					<div class="apollo-linkedin-card__placeholder">
						<i class="<?php echo esc_attr( $section['icon'] ?? 'ri-file-line' ); ?>"></i>
					</div>
				<?php endif; ?>
			</div>

			<!-- Info -->
			<div class="apollo-linkedin-card__info">
				<span class="apollo-linkedin-card__title"><?php echo esc_html( $title ); ?></span>
				<span class="apollo-linkedin-card__subtitle">
					<?php echo esc_html( $this->get_card_subtitle( $post_id, $type ) ); ?>
				</span>
			</div>
		</a>
		<?php
	}

	/**
	 * Gera subtítulo contextual para o card com base no tipo de CPT.
	 *
	 * @param int    $post_id  ID do post.
	 * @param string $type     Tipo de CPT.
	 * @return string           Texto do subtítulo.
	 */
	private function get_card_subtitle( int $post_id, string $type ): string {
		switch ( $type ) {
			case 'event':
				$date = get_post_meta( $post_id, '_event_start_date', true );
				return $date ? wp_date( 'd/m/Y', strtotime( $date ) ) : 'Evento';

			case 'dj':
				$sounds = wp_get_post_terms( $post_id, 'sound', array( 'fields' => 'names' ) );
				if ( ! is_wp_error( $sounds ) && ! empty( $sounds ) ) {
					return implode( ', ', array_slice( $sounds, 0, 2 ) );
				}
				return 'DJ';

			case 'classified':
				$price = get_post_meta( $post_id, '_classified_price', true );
				return $price ? 'R$ ' . number_format( (float) $price, 2, ',', '.' ) : 'Anúncio';

			case 'loc':
				$city = get_post_meta( $post_id, '_loc_city', true );
				return $city ?: 'Local';

			case 'hub':
				return 'Hub';

			default:
				return ucfirst( $type );
		}
	}

	/**
	 * Adiciona estatísticas de favoritos ao perfil público.
	 *
	 * @param array $stats    Stats atuais do perfil.
	 * @param int   $user_id  ID do usuário.
	 * @return array           Stats atualizadas.
	 */
	public function add_profile_stats( array $stats, int $user_id ): array {
		$stats['favs'] = array(
			'label' => __( 'Favoritos', 'apollo-fav' ),
			'count' => apollo_get_user_fav_total( $user_id ),
			'icon'  => 'ri-fire-line',
		);

		return $stats;
	}

	/**
	 * Renderiza widget lateral com top favoritos no perfil.
	 */
	public function render_sidebar_widget(): void {
		$user_id = get_query_var( 'apollo_profile_user_id', 0 );

		if ( ! $user_id ) {
			return;
		}

		$total = apollo_get_user_fav_total( (int) $user_id );

		if ( $total === 0 ) {
			return;
		}

		// Mostra os 3 favoritos mais recentes
		$recent = apollo_get_user_favs( (int) $user_id, null, 3, 0 );

		if ( empty( $recent ) ) {
			return;
		}

		?>
		<div class="apollo-sidebar-widget apollo-sidebar-widget--favs">
			<h4 class="apollo-sidebar-widget__title">
				<i class="ri-fire-line"></i>
				<?php esc_html_e( 'Favoritos Recentes', 'apollo-fav' ); ?>
			</h4>
			<ul class="apollo-sidebar-widget__list">
				<?php
				foreach ( $recent as $fav ) :
					$post = get_post( (int) $fav->post_id );
					if ( ! $post || $post->post_status !== 'publish' ) {
						continue;
					}
					$section = $this->sections[ $fav->post_type ] ?? null;
					?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>">
							<i class="<?php echo esc_attr( $section['icon'] ?? 'ri-file-line' ); ?>"></i>
							<?php echo esc_html( $post->post_title ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( $total > 3 ) : ?>
				<a href="<?php echo esc_url( home_url( '/painel/favoritos' ) ); ?>" class="apollo-sidebar-widget__more">
					<?php printf( esc_html__( 'Ver todos (%d)', 'apollo-fav' ), $total ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}
}
