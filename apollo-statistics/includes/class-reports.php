<?php
/**
 * Relatórios e visualizações — shortcodes admin, widgets e render.
 *
 * @package Apollo\Statistics
 */

declare(strict_types=1);

namespace Apollo\Statistics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Reports {

	/** @var Metrics_Processor|null */
	private ?Metrics_Processor $processor = null;

	/**
	 * Construtor.
	 */
	public function __construct() {
		// Hooks registrados no init() para manter padrão Apollo.
	}

	/**
	 * Inicializa hooks de relatórios.
	 */
	public function init(): void {
		// Shortcode admin-only: [apollo_stats_widget]
		add_shortcode( 'apollo_stats_widget', array( $this, 'render_stats_widget' ) );

		// Widget no dashboard admin do WordPress
		add_action( 'wp_dashboard_setup', array( $this, 'register_admin_widget' ) );

		// Apollo admin dashboard integration
		add_filter( 'apollo/admin/dashboard_widgets', array( $this, 'register_apollo_admin_widget' ) );

		// Enqueue Chart.js quando necessário
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Obtém instância do processador.
	 *
	 * @return Metrics_Processor
	 */
	private function get_processor(): Metrics_Processor {
		if ( null === $this->processor ) {
			$this->processor = new Metrics_Processor();
		}

		return $this->processor;
	}

	/* ───────────────────────── SHORTCODE ───────────────────────── */

	/**
	 * Renderiza widget de estatísticas (admin-only).
	 *
	 * @param array|string $atts Atributos do shortcode.
	 * @return string HTML do widget.
	 */
	public function render_stats_widget( $atts = array() ): string {
		// Apenas admin pode ver
		if ( ! current_user_can( 'manage_options' ) ) {
			return '<p class="apollo-stats-noaccess">' . esc_html__( 'Acesso restrito a administradores.', 'apollo-statistics' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'period' => 30,
				'type'   => 'overview', // overview, events, content, users
			),
			$atts,
			'apollo_stats_widget'
		);

		$days = absint( $atts['period'] );
		$type = sanitize_text_field( $atts['type'] );

		ob_start();

		switch ( $type ) {
			case 'events':
				$this->render_events_report( $days );
				break;
			case 'users':
				$this->render_users_report( $days );
				break;
			case 'content':
				$this->render_content_report( $days );
				break;
			case 'health':
				$this->render_health_widget();
				break;
			case 'overview':
			default:
				$this->render_overview( $days );
				break;
		}

		return ob_get_clean();
	}

	/* ───────────────────────── OVERVIEW ───────────────────────── */

	/**
	 * Renderiza overview geral.
	 *
	 * @param int $days Período.
	 */
	private function render_overview( int $days ): void {
		// Usa cache
		$cache_key = 'apollo_stats_overview_' . $days;
		$overview  = get_transient( $cache_key );

		if ( false === $overview ) {
			$overview = apollo_stats_get_overview( $days );
			set_transient( $cache_key, $overview, 6 * HOUR_IN_SECONDS );
		}

		?>
		<div class="apollo-stats-overview" data-period="<?php echo esc_attr( (string) $days ); ?>">
			<h3 class="apollo-stats-title">
				<i class="ri-bar-chart-box-line"></i>
				Estatísticas — Últimos <?php echo esc_html( (string) $days ); ?> dias
			</h3>

			<div class="apollo-stats-grid">
				<?php
				$cards = array(
					array(
						'icon'  => 'ri-eye-line',
						'label' => 'Views',
						'value' => $overview['total_views'],
						'color' => '#2196f3',
					),
					array(
						'icon'  => 'ri-heart-3-fill',
						'label' => 'Favoritos',
						'value' => $overview['total_favs'],
						'color' => '#e91e63',
					),
					array(
						'icon'  => 'ri-user-add-line',
						'label' => 'Novos Usuários',
						'value' => $overview['new_users'],
						'color' => '#4caf50',
					),
					array(
						'icon'  => 'ri-login-box-line',
						'label' => 'Logins',
						'value' => $overview['total_logins'],
						'color' => '#ff9800',
					),
					array(
						'icon'  => 'ri-calendar-event-line',
						'label' => 'Eventos Criados',
						'value' => $overview['events_created'],
						'color' => '#9c27b0',
					),
				);

				foreach ( $cards as $card ) :
					$trend = $this->get_processor()->calculate_trend(
						strtolower( str_replace( ' ', '_', $card['label'] ) ),
						'content',
						$days
					);
					?>
					<div class="apollo-stats-card" style="--card-accent: <?php echo esc_attr( $card['color'] ); ?>">
						<div class="apollo-stats-card__icon">
							<i class="<?php echo esc_attr( $card['icon'] ); ?>"></i>
						</div>
						<div class="apollo-stats-card__body">
							<span class="apollo-stats-card__value">
								<?php echo esc_html( number_format_i18n( $card['value'] ) ); ?>
							</span>
							<span class="apollo-stats-card__label">
								<?php echo esc_html( $card['label'] ); ?>
							</span>
						</div>
						<?php if ( $trend['trend'] != 0 ) : ?>
							<span class="apollo-stats-card__trend apollo-stats-card__trend--<?php echo esc_attr( $trend['direction'] ); ?>">
								<?php echo $trend['direction'] === 'up' ? '↑' : '↓'; ?>
								<?php echo esc_html( abs( $trend['trend'] ) . '%' ); ?>
							</span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( ! empty( $overview['top_content'] ) ) : ?>
				<div class="apollo-stats-top">
					<h4><i class="ri-trophy-line"></i> Top 10 Conteúdos</h4>
					<table class="apollo-stats-table">
						<thead>
							<tr>
								<th>#</th>
								<th>Conteúdo</th>
								<th>Tipo</th>
								<th>Views</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $overview['top_content'] as $i => $item ) : ?>
								<tr>
									<td><?php echo esc_html( (string) ( $i + 1 ) ); ?></td>
									<td>
										<a href="<?php echo esc_url( get_permalink( (int) $item['post_id'] ) ); ?>">
											<?php echo esc_html( $item['post_title'] ); ?>
										</a>
									</td>
									<td>
										<span class="apollo-stats-badge apollo-stats-badge--<?php echo esc_attr( $item['post_type'] ); ?>">
											<?php echo esc_html( $item['post_type'] ); ?>
										</span>
									</td>
									<td><?php echo esc_html( number_format_i18n( (int) $item['views'] ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>

			<canvas id="apollo-stats-chart-overview" width="400" height="200"></canvas>
		</div>
		<?php
	}

	/* ───────────────────────── REPORTS ESPECÍFICOS ───────────────────────── */

	/**
	 * Renderiza relatório de eventos.
	 *
	 * @param int $days Período.
	 */
	private function render_events_report( int $days ): void {
		$engagement = get_transient( 'apollo_stats_event_engagement' );
		?>
		<div class="apollo-stats-report">
			<h3><i class="ri-calendar-line"></i> Relatório de Eventos</h3>

			<?php if ( empty( $engagement ) ) : ?>
				<p class="apollo-stats-empty">Nenhum dado de eventos disponível para o período.</p>
			<?php else : ?>
				<table class="apollo-stats-table">
					<thead>
						<tr>
							<th>Evento</th>
							<th>Views</th>
							<th>RSVPs</th>
							<th>Favs</th>
							<th>Shares</th>
							<th>Check-ins</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( array_slice( $engagement, 0, 20 ) as $event ) : ?>
							<tr>
								<td>
									<a href="<?php echo esc_url( get_permalink( (int) $event['event_id'] ) ); ?>">
										<?php echo esc_html( get_the_title( (int) $event['event_id'] ) ); ?>
									</a>
								</td>
								<td><?php echo esc_html( number_format_i18n( (int) $event['views'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) $event['rsvps'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) $event['favs'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) $event['shares'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) $event['checkins'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renderiza relatório de usuários.
	 *
	 * @param int $days Período.
	 */
	private function render_users_report( int $days ): void {
		$activity = get_transient( 'apollo_stats_user_activity' );
		?>
		<div class="apollo-stats-report">
			<h3><i class="ri-team-line"></i> Relatório de Usuários</h3>

			<?php if ( empty( $activity ) ) : ?>
				<p class="apollo-stats-empty">Nenhum dado de atividade de usuários disponível.</p>
			<?php else : ?>
				<table class="apollo-stats-table">
					<thead>
						<tr>
							<th>Usuário</th>
							<th>Logins</th>
							<th>Views Perfil</th>
							<th>Ações</th>
							<th>Dias Ativos</th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( array_slice( $activity, 0, 20 ) as $user ) :
							$user_data = get_userdata( (int) $user['user_id'] );
							?>
							<tr>
								<td>
									<?php echo esc_html( $user_data ? $user_data->display_name : '(Removido) #' . $user['user_id'] ); ?>
								</td>
								<td><?php echo esc_html( number_format_i18n( (int) $user['logins'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) $user['profile_views'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) $user['actions'] ) ); ?></td>
								<td><?php echo esc_html( (string) $user['active_days'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renderiza relatório de conteúdo.
	 *
	 * @param int $days Período.
	 */
	private function render_content_report( int $days ): void {
		$rankings = get_transient( 'apollo_stats_content_rankings' );
		?>
		<div class="apollo-stats-report">
			<h3><i class="ri-file-list-3-line"></i> Relatório de Conteúdo</h3>

			<?php if ( empty( $rankings ) ) : ?>
				<p class="apollo-stats-empty">Nenhum dado de conteúdo disponível. Execute a coleta diária.</p>
			<?php else : ?>
				<table class="apollo-stats-table">
					<thead>
						<tr>
							<th>#</th>
							<th>Conteúdo</th>
							<th>Tipo</th>
							<th>Views</th>
							<th>Favs</th>
							<th>Score</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rankings as $i => $item ) : ?>
							<tr>
								<td><?php echo esc_html( (string) ( $i + 1 ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( get_permalink( (int) $item['post_id'] ) ); ?>">
										<?php echo esc_html( get_the_title( (int) $item['post_id'] ) ); ?>
									</a>
								</td>
								<td>
									<span class="apollo-stats-badge apollo-stats-badge--<?php echo esc_attr( $item['post_type'] ); ?>">
										<?php echo esc_html( $item['post_type'] ); ?>
									</span>
								</td>
								<td><?php echo esc_html( number_format_i18n( (int) $item['views'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( (int) $item['favs'] ) ); ?></td>
								<td><strong><?php echo esc_html( number_format_i18n( (int) $item['score'] ) ); ?></strong></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renderiza widget de health.
	 */
	private function render_health_widget(): void {
		$health = $this->get_processor()->get_health_summary();

		$status_labels = array(
			'healthy'  => array(
				'label' => 'Saudável',
				'color' => '#4caf50',
				'icon'  => 'ri-heart-pulse-fill',
			),
			'warning'  => array(
				'label' => 'Atenção',
				'color' => '#ff9800',
				'icon'  => 'ri-alert-line',
			),
			'critical' => array(
				'label' => 'Crítico',
				'color' => '#f44336',
				'icon'  => 'ri-alarm-warning-line',
			),
		);

		$status = $status_labels[ $health['status'] ] ?? $status_labels['warning'];
		?>
		<div class="apollo-stats-health">
			<div class="apollo-stats-health__score" style="--health-color: <?php echo esc_attr( $status['color'] ); ?>">
				<i class="<?php echo esc_attr( $status['icon'] ); ?>"></i>
				<span class="apollo-stats-health__number"><?php echo esc_html( (string) $health['health_score'] ); ?></span>
				<span class="apollo-stats-health__label"><?php echo esc_html( $status['label'] ); ?></span>
			</div>

			<div class="apollo-stats-health__metrics">
				<?php
				$metrics = array(
					'views'  => array(
						'label' => 'Views',
						'icon'  => 'ri-eye-line',
					),
					'favs'   => array(
						'label' => 'Favoritos',
						'icon'  => 'ri-heart-3-line',
					),
					'logins' => array(
						'label' => 'Logins',
						'icon'  => 'ri-login-box-line',
					),
					'events' => array(
						'label' => 'Eventos',
						'icon'  => 'ri-calendar-line',
					),
				);

				foreach ( $metrics as $key => $meta ) :
					$data = $health[ $key ];
					?>
					<div class="apollo-stats-health__metric">
						<i class="<?php echo esc_attr( $meta['icon'] ); ?>"></i>
						<span class="apollo-stats-health__metric-label"><?php echo esc_html( $meta['label'] ); ?></span>
						<span class="apollo-stats-health__metric-value"><?php echo esc_html( number_format_i18n( $data['current'] ) ); ?></span>
						<span class="apollo-stats-health__metric-trend apollo-stats-health__metric-trend--<?php echo esc_attr( $data['direction'] ); ?>">
							<?php echo $data['direction'] === 'up' ? '↑' : ( $data['direction'] === 'down' ? '↓' : '→' ); ?>
							<?php echo esc_html( abs( $data['trend'] ) . '%' ); ?>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/* ───────────────────────── ADMIN WIDGETS ───────────────────────── */

	/**
	 * Registra widget no dashboard nativo do WordPress.
	 */
	public function register_admin_widget(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'apollo_stats_dashboard',
			'<i class="ri-bar-chart-box-line"></i> Apollo Statistics',
			array( $this, 'render_admin_dashboard_widget' )
		);
	}

	/**
	 * Renderiza widget no dashboard admin do WP.
	 */
	public function render_admin_dashboard_widget(): void {
		$overview = apollo_stats_get_overview( 7 );
		$health   = $this->get_processor()->get_health_summary();
		?>
		<div class="apollo-stats-admin-widget">
			<div class="apollo-stats-admin-widget__row">
				<div class="apollo-stats-admin-widget__kpi">
					<strong><?php echo esc_html( number_format_i18n( $overview['total_views'] ) ); ?></strong>
					<span>Views (7d)</span>
				</div>
				<div class="apollo-stats-admin-widget__kpi">
					<strong><?php echo esc_html( number_format_i18n( $overview['total_favs'] ) ); ?></strong>
					<span>Favs (7d)</span>
				</div>
				<div class="apollo-stats-admin-widget__kpi">
					<strong><?php echo esc_html( number_format_i18n( $overview['new_users'] ) ); ?></strong>
					<span>Novos Usuários</span>
				</div>
				<div class="apollo-stats-admin-widget__kpi">
					<strong><?php echo esc_html( (string) $health['health_score'] ); ?></strong>
					<span>Health Score</span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Registra widget no painel admin do Apollo.
	 *
	 * @param array $widgets Widgets existentes.
	 * @return array
	 */
	public function register_apollo_admin_widget( array $widgets ): array {
		$widgets['statistics'] = array(
			'title'    => 'Estatísticas',
			'icon'     => 'ri-bar-chart-box-line',
			'priority' => 5,
			'callback' => array( $this, 'render_admin_dashboard_widget' ),
		);

		return $widgets;
	}

	/* ───────────────────────── ASSETS ───────────────────────── */

	/**
	 * Enqueue Chart.js apenas nas páginas admin que precisam.
	 *
	 * @param string $hook Hook da página.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		// Dashboard ou páginas apollo
		if ( ! in_array( $hook, array( 'index.php', 'toplevel_page_apollo-admin' ), true ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js',
			array(),
			'4.4.0',
			true
		);

		wp_enqueue_style(
			'apollo-statistics-admin',
			APOLLO_STATISTICS_URL . 'assets/css/apollo-statistics.css',
			array(),
			APOLLO_STATISTICS_VERSION
		);

		wp_enqueue_script(
			'apollo-statistics-admin',
			APOLLO_STATISTICS_URL . 'assets/js/apollo-statistics.js',
			array( 'jquery', 'chartjs' ),
			APOLLO_STATISTICS_VERSION,
			true
		);

		wp_localize_script(
			'apollo-statistics-admin',
			'apolloStats',
			array(
				'restUrl' => esc_url_raw( rest_url( 'apollo/v1/stats/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
