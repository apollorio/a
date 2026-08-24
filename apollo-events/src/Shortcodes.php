<?php
/**
 * Shortcodes — [a-eve] principal + [apollo_events] + [apollo_event] + [apollo_calendar] + [apollo_event_form]
 *
 * [a-eve] é o shortcode MASTER com suporte a:
 * - type:   month-cal | bimonth-cal | month-extra-cal | week-cal
 * - event:  card | list | map
 * - style:  apollo-v1 | ui-thim | ui-lis | base
 * - show:   upcoming | past | all
 * - season: slug da taxonomy season
 * - start:  dd/mm/yyyy
 * - limit:  número
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcodes {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Registra todos os shortcodes — integra com apollo-shortcodes (se ativo)
	 */
	public function register(): void {
		// Shortcode principal
		add_shortcode( 'a-eve', array( $this, 'render_main' ) );

		// Shortcodes do registry
		add_shortcode( 'apollo_events', array( $this, 'render_apollo_events' ) );
		add_shortcode( 'apollo_event', array( $this, 'render_apollo_event' ) );
		add_shortcode( 'apollo_calendar', array( $this, 'render_apollo_calendar' ) );
		add_shortcode( 'apollo_event_form', array( $this, 'render_event_form' ) );

		// Registra no apollo-shortcodes (se ativo)
		add_filter( 'apollo_shortcodes_registry', array( $this, 'register_in_apollo_shortcodes' ) );
	}

	/**
	 * [a-eve] — Shortcode MASTER
	 *
	 * @param array|string $atts Atributos do shortcode.
	 * @return string HTML renderizado.
	 */
	public function render_main( $atts ): string {
		$atts = shortcode_atts(
			array(
				'type'       => 'month-cal',     // bimonth-cal | month-extra-cal | month-cal | week-cal
				'event'      => 'card',           // card | list | map
				'style'      => '',               // apollo-v1 | ui-thim | ui-lis | base
				'show'       => 'upcoming',       // upcoming | past | all
				'season'     => '',               // filtro por taxonomy season
				'category'   => '',             // filtro taxonomy event_category
				'event_type' => '',           // filtro taxonomy event_type
				'sound'      => '',                // filtro taxonomy sound
				'loc_id'     => 0,                // filtro por local
				'dj_id'      => 0,                 // filtro por DJ
				'date_from'  => '',            // Y-m-d
				'date_to'    => '',              // Y-m-d
				'start'      => '',               // dd/mm/yyyy — data inicial custom
				'limit'      => 12,
			),
			$atts,
			'a-eve'
		);

		// Enqueue assets
		wp_enqueue_style( 'apollo-events' );
		wp_enqueue_style( 'apollo-events-cards' );
		wp_enqueue_script( 'apollo-events' );

		// Resolver estilo ativo
		$style = apollo_event_get_active_style( $atts['style'] );

		// Enqueue estilo específico
		$style_css = APOLLO_EVENT_DIR . 'styles/' . $style . '/style.css';
		if ( file_exists( $style_css ) ) {
			wp_enqueue_style(
				'apollo-events-style-' . $style,
				APOLLO_EVENT_URL . 'styles/' . $style . '/style.css',
				array( 'apollo-events' ),
				APOLLO_EVENT_VERSION
			);
		}

		// Preparar data de início
		$start_date = $this->parse_start_date( $atts['start'] );

		// Montar query
		$query_args = $this->build_query_args( $atts, $start_date );

		// Buscar eventos
		$query = apollo_event_query( $query_args );

		/**
		 * Hook antes de renderizar [a-eve]
		 *
		 * @param \WP_Query $query      Query de eventos.
		 * @param array     $atts       Atributos do shortcode.
		 * @param string    $style      Estilo ativo.
		 */
		do_action( 'apollo_event_before_render', $query, $atts, $style );

		// Renderizar conforme tipo
		ob_start();

		echo '<div class="a-eve-wrapper" data-type="' . esc_attr( $atts['type'] ) . '" data-event="' . esc_attr( $atts['event'] ) . '" data-style="' . esc_attr( $style ) . '" data-show="' . esc_attr( $atts['show'] ) . '">';

		// Renderizar calendário (se tipo inclui cal)
		if ( str_contains( $atts['type'], 'cal' ) ) {
			$this->render_calendar( $atts['type'], $query, $start_date, $style );
		}

		// Renderizar eventos
		echo '<div class="a-eve-events a-eve-events--' . esc_attr( $atts['event'] ) . '">';

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$event_data = $this->prepare_event_data( get_the_ID() );

				// Verificar se está gone
				$gone_class = $event_data['is_gone'] ? ' a-eve-gone' : '';

				// Verificar se deve mostrar eventos gone
				if ( $event_data['is_gone'] && ! apollo_event_option( 'show_gone_events', true ) ) {
					continue;
				}

				/**
				 * Hook antes de cada card de evento
				 *
				 * @param array  $event_data Dados do evento.
				 * @param string $style      Estilo ativo.
				 */
				do_action( 'apollo_event_before_card', $event_data, $style );

				// Carregar template do estilo
				$this->load_event_template( $atts['event'], $style, $event_data );

				/**
				 * Hook depois de cada card de evento
				 *
				 * @param array  $event_data Dados do evento.
				 * @param string $style      Estilo ativo.
				 */
				do_action( 'apollo_event_after_card', $event_data, $style );
			}
			wp_reset_postdata();
		} else {
			echo '<p class="a-eve-empty">' . esc_html__( 'Nenhum evento encontrado.', 'apollo-events' ) . '</p>';
		}

		echo '</div>'; // .a-eve-events

		// Mapa (se event=map)
		if ( 'map' === $atts['event'] ) {
			$this->render_map( $query, $style );
		}

		echo '</div>'; // .a-eve-wrapper

		/**
		 * Hook após renderizar [a-eve]
		 *
		 * @param \WP_Query $query Query de eventos.
		 * @param array     $atts  Atributos do shortcode.
		 */
		do_action( 'apollo_event_after_render', $query, $atts );

		return ob_get_clean();
	}

	/**
	 * [apollo_events] — Listagem simples
	 */
	public function render_apollo_events( $atts ): string {
		$atts = shortcode_atts(
			array(
				'limit'    => 12,
				'category' => '',
				'type'     => '',
				'upcoming' => 'true',
			),
			$atts,
			'apollo_events'
		);

		return $this->render_main(
			array(
				'type'       => 'month-cal',
				'event'      => 'card',
				'show'       => 'true' === $atts['upcoming'] ? 'upcoming' : 'all',
				'category'   => $atts['category'],
				'event_type' => $atts['type'],
				'limit'      => $atts['limit'],
			)
		);
	}

	/**
	 * [apollo_event] — Evento individual
	 */
	public function render_apollo_event( $atts ): string {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'apollo_event' );

		if ( ! $atts['id'] ) {
			return '';
		}

		wp_enqueue_style( 'apollo-events' );
		wp_enqueue_style( 'apollo-events-cards' );

		$event_data = $this->prepare_event_data( (int) $atts['id'] );
		$style      = apollo_event_get_active_style();

		ob_start();
		$this->load_event_template( 'card', $style, $event_data );
		return ob_get_clean();
	}

	/**
	 * [apollo_calendar] — Calendário
	 */
	public function render_apollo_calendar( $atts ): string {
		$atts = shortcode_atts(
			array(
				'type'  => 'month-cal',
				'month' => '',
				'year'  => '',
			),
			$atts,
			'apollo_calendar'
		);

		return $this->render_main(
			array(
				'type'  => $atts['type'],
				'event' => 'card',
				'show'  => 'upcoming',
			)
		);
	}

	/**
	 * [apollo_event_form] — Formulário de criação de evento
	 */
	public function render_event_form( $atts ): string {
		if ( ! is_user_logged_in() ) {
			return '<p class="a-eve-login-required">' . esc_html__( 'Faça login para criar eventos.', 'apollo-events' ) . '</p>';
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return '<p class="a-eve-no-permission">' . esc_html__( 'Você não tem permissão para criar eventos.', 'apollo-events' ) . '</p>';
		}

		wp_enqueue_style( 'apollo-events' );
		wp_enqueue_script( 'apollo-events' );

		ob_start();
		$style    = apollo_event_get_active_style();
		$template = APOLLO_EVENT_DIR . 'styles/' . $style . '/create-event.php';
		if ( ! file_exists( $template ) ) {
			$template = APOLLO_EVENT_DIR . 'styles/base/create-event.php';
		}
		if ( file_exists( $template ) ) {
			include $template;
		}
		return ob_get_clean();
	}

	/**
	 * Registra no sistema apollo-shortcodes
	 */
	public function register_in_apollo_shortcodes( array $shortcodes ): array {
		$shortcodes['a-eve'] = array(
			'label'       => __( 'Eventos', 'apollo-events' ),
			'description' => __( 'Calendário e listagem de eventos', 'apollo-events' ),
			'plugin'      => 'apollo-events',
			'attrs'       => array( 'type', 'event', 'style', 'show', 'season', 'category', 'event_type', 'sound', 'loc_id', 'dj_id', 'date_from', 'date_to', 'start', 'limit' ),
		);

		return $shortcodes;
	}

	// ─── Métodos Privados ──────────────────────────────────────────────

	/**
	 * Parseia data de início dd/mm/yyyy → Y-m-d
	 */
	private function parse_start_date( string $raw ): string {
		if ( empty( $raw ) ) {
			return current_time( 'Y-m-d' );
		}

		// dd/mm/yyyy → Y-m-d
		$parts = explode( '/', $raw );
		if ( 3 === count( $parts ) ) {
			return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
		}

		return current_time( 'Y-m-d' );
	}

	/**
	 * Monta argumentos do WP_Query baseado nos atributos
	 */
	private function build_query_args( array $atts, string $start_date ): array {
		$default_per_page = (int) apollo_event_option( 'events_per_page', 12 );
		$default_per_page = max( 1, min( 100, $default_per_page ) );

		$args = array(
			'post_type'      => APOLLO_EVENT_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, (int) ( $atts['limit'] ?: $default_per_page ) ),
			'orderby'        => 'meta_value',
			'meta_key'       => '_event_start_date',
			'order'          => 'ASC',
		);

		$meta_query = array();

		// Filtro upcoming / past / all
		switch ( $atts['show'] ) {
			case 'upcoming':
				$meta_query[] = array(
					'key'     => '_event_start_date',
					'value'   => $start_date,
					'compare' => '>=',
					'type'    => 'DATE',
				);
				break;

			case 'past':
				$meta_query[]  = array(
					'key'     => '_event_start_date',
					'value'   => $start_date,
					'compare' => '<',
					'type'    => 'DATE',
				);
				$args['order'] = 'DESC';
				break;
		}

		// Filtro por season (taxonomy)
		if ( ! empty( $atts['season'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => APOLLO_EVENT_TAX_SEASON,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( (string) $atts['season'] ),
			);
		}

		if ( ! empty( $atts['category'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => APOLLO_EVENT_TAX_CATEGORY,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( (string) $atts['category'] ),
			);
		}

		if ( ! empty( $atts['event_type'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => APOLLO_EVENT_TAX_TYPE,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( (string) $atts['event_type'] ),
			);
		}

		if ( ! empty( $atts['sound'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => APOLLO_EVENT_TAX_SOUND,
				'field'    => 'slug',
				'terms'    => sanitize_text_field( (string) $atts['sound'] ),
			);
		}

		$loc_id = absint( (string) $atts['loc_id'] );
		if ( $loc_id > 0 ) {
			$meta_query[] = array(
				'key'     => '_event_loc_id',
				'value'   => $loc_id,
				'compare' => '=',
				'type'    => 'NUMERIC',
			);
		}

		$dj_id = absint( (string) $atts['dj_id'] );
		if ( $dj_id > 0 ) {
			$meta_query[] = array(
				'key'     => '_event_dj_ids',
				'value'   => sprintf( '"%d"', $dj_id ),
				'compare' => 'LIKE',
			);
		}

		$date_from = sanitize_text_field( (string) $atts['date_from'] );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			$meta_query[] = array(
				'key'     => '_event_start_date',
				'value'   => $date_from,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}

		$date_to = sanitize_text_field( (string) $atts['date_to'] );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
			$meta_query[] = array(
				'key'     => '_event_start_date',
				'value'   => $date_to,
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$args['meta_query'] = $meta_query;
		}

		return $args;
	}

	/**
	 * Prepara dados de um evento para templates
	 */
	private function prepare_event_data( int $post_id ): array {
		$parsed_date = apollo_event_parse_date(
			get_post_meta( $post_id, '_event_start_date', true ) ?: current_time( 'Y-m-d' )
		);

		$djs = apollo_event_get_djs( $post_id );
		$loc = apollo_event_get_loc( $post_id );

		return array(
			'id'           => $post_id,
			'title'        => get_the_title( $post_id ),
			'permalink'    => get_permalink( $post_id ),
			'excerpt'      => get_the_excerpt( $post_id ),
			'banner'       => apollo_event_get_banner( $post_id ),
			'start_date'   => get_post_meta( $post_id, '_event_start_date', true ),
			'end_date'     => get_post_meta( $post_id, '_event_end_date', true ),
			'start_time'   => get_post_meta( $post_id, '_event_start_time', true ),
			'end_time'     => get_post_meta( $post_id, '_event_end_time', true ),
			'parsed_date'  => $parsed_date,
			'djs'          => $djs,
			'dj_names'     => implode( ', ', array_column( $djs, 'title' ) ),
			'loc'          => $loc,
			'loc_name'     => $loc ? $loc['title'] : '',
			'ticket_url'   => get_post_meta( $post_id, '_event_ticket_url', true ),
			'ticket_price' => get_post_meta( $post_id, '_event_ticket_price', true ),
			'privacy'      => get_post_meta( $post_id, '_event_privacy', true ) ?: 'public',
			'status'       => get_post_meta( $post_id, '_event_status', true ) ?: 'scheduled',
			'is_gone'      => apollo_event_is_gone( $post_id ),
			'sounds'       => wp_get_post_terms( $post_id, APOLLO_EVENT_TAX_SOUND, array( 'fields' => 'names' ) ),
			'categories'   => wp_get_post_terms( $post_id, APOLLO_EVENT_TAX_CATEGORY, array( 'fields' => 'names' ) ),
		);
	}

	/**
	 * Carrega template de evento do estilo ativo
	 *
	 * Prioridade: theme/apollo-events/{style}/ → styles/{style}/ → styles/base/
	 *
	 * @param string $type       Tipo de template (card | list | map-marker).
	 * @param string $style      Estilo ativo.
	 * @param array  $event_data Dados do evento.
	 */
	private function load_event_template( string $type, string $style, array $event_data ): void {
		$filename = 'event-' . $type . '.php';

		// 1. Tema override
		$theme_template = get_stylesheet_directory() . '/apollo-events/' . $style . '/' . $filename;
		if ( file_exists( $theme_template ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			extract( $event_data, EXTR_SKIP );
			include $theme_template;
			return;
		}

		// 2. Style folder
		$style_template = APOLLO_EVENT_DIR . 'styles/' . $style . '/' . $filename;
		if ( file_exists( $style_template ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			extract( $event_data, EXTR_SKIP );
			include $style_template;
			return;
		}

		// 3. Fallback base
		$base_template = APOLLO_EVENT_DIR . 'styles/base/' . $filename;
		if ( file_exists( $base_template ) ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			extract( $event_data, EXTR_SKIP );
			include $base_template;
			return;
		}
	}

	/**
	 * Renderiza calendário
	 */
	private function render_calendar( string $type, \WP_Query $query, string $start_date, string $style ): void {
		wp_enqueue_style( 'apollo-events-calendar' );
		wp_enqueue_script( 'apollo-events-calendar' );

		// Mapear eventos por data
		$events_by_date = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$date = get_post_meta( $post->ID, '_event_start_date', true );
				if ( $date ) {
					$events_by_date[ $date ][] = $post->ID;
				}
			}
		}

		// Dados do calendário
		$ts    = strtotime( $start_date );
		$year  = (int) date( 'Y', $ts );
		$month = (int) date( 'n', $ts );

		$template = APOLLO_EVENT_DIR . 'styles/' . $style . '/calendar-' . $type . '.php';
		if ( ! file_exists( $template ) ) {
			$template = APOLLO_EVENT_DIR . 'styles/base/calendar-' . $type . '.php';
		}

		if ( file_exists( $template ) ) {
			include $template;
		} else {
			// Fallback: calendário genérico mensal
			$this->render_fallback_calendar( $year, $month, $events_by_date, $type );
		}
	}

	/**
	 * Calendário fallback (mensal)
	 */
	private function render_fallback_calendar( int $year, int $month, array $events_by_date, string $type ): void {
		$meses_pt = array(
			1  => 'Janeiro',
			2  => 'Fevereiro',
			3  => 'Março',
			4  => 'Abril',
			5  => 'Maio',
			6  => 'Junho',
			7  => 'Julho',
			8  => 'Agosto',
			9  => 'Setembro',
			10 => 'Outubro',
			11 => 'Novembro',
			12 => 'Dezembro',
		);

		$dias_semana = array( 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom' );

		$first_day = mktime( 0, 0, 0, $month, 1, $year );
		$days_in   = (int) date( 't', $first_day );
		$start_dow = ( (int) date( 'N', $first_day ) ) - 1; // 0=Seg, 6=Dom
		$today     = current_time( 'Y-m-d' );

		// Quantos meses mostrar
		$months_to_show = 1;
		if ( 'bimonth-cal' === $type ) {
			$months_to_show = 2;
		}

		echo '<div class="a-eve-calendar a-eve-calendar--' . esc_attr( $type ) . '">';

		for ( $m = 0; $m < $months_to_show; $m++ ) {
			$cm = $month + $m;
			$cy = $year;
			if ( $cm > 12 ) {
				$cm -= 12;
				++$cy;
			}

			$first = mktime( 0, 0, 0, $cm, 1, $cy );
			$din   = (int) date( 't', $first );
			$sdow  = ( (int) date( 'N', $first ) ) - 1;

			echo '<div class="a-eve-cal-month">';
			echo '<div class="a-eve-cal-header">';
			echo '<h3 class="a-eve-cal-title">' . esc_html( $meses_pt[ $cm ] . ' ' . $cy ) . '</h3>';
			echo '</div>';

			echo '<div class="a-eve-cal-grid">';

			// Headers dos dias da semana
			foreach ( $dias_semana as $d ) {
				echo '<div class="a-eve-cal-dow">' . esc_html( $d ) . '</div>';
			}

			// Células vazias antes do dia 1
			for ( $i = 0; $i < $sdow; $i++ ) {
				echo '<div class="a-eve-cal-day a-eve-cal-day--empty"></div>';
			}

			// Dias do mês
			for ( $day = 1; $day <= $din; $day++ ) {
				$date_key  = sprintf( '%04d-%02d-%02d', $cy, $cm, $day );
				$has_event = isset( $events_by_date[ $date_key ] );
				$is_today  = $date_key === $today;

				$classes = 'a-eve-cal-day';
				if ( $has_event ) {
					$classes .= ' a-eve-cal-day--has-event';
				}
				if ( $is_today ) {
					$classes .= ' a-eve-cal-day--today';
				}

				echo '<div class="' . esc_attr( $classes ) . '" data-date="' . esc_attr( $date_key ) . '">';
				echo '<span class="a-eve-cal-day-num">' . esc_html( $day ) . '</span>';
				if ( $has_event ) {
					$count = count( $events_by_date[ $date_key ] );
					echo '<span class="a-eve-cal-dot" title="' . esc_attr( $count . ' evento(s)' ) . '"></span>';
				}
				echo '</div>';
			}

			echo '</div>'; // .a-eve-cal-grid
			echo '</div>'; // .a-eve-cal-month
		}

		echo '</div>'; // .a-eve-calendar
	}

	/**
	 * Renderiza mapa (OSM via Leaflet)
	 */
	private function render_map( \WP_Query $query, string $style ): void {
		if ( ! apollo_event_option( 'enable_osm_map', true ) ) {
			return;
		}

		wp_enqueue_style( 'leaflet' );
		wp_enqueue_script( 'leaflet' );
		wp_enqueue_script( 'apollo-events-map' );

		// Coletar coordenadas
		$markers = array();
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$loc = apollo_event_get_loc( $post->ID );
				if ( $loc && $loc['lat'] && $loc['lng'] ) {
					$markers[] = array(
						'id'    => $post->ID,
						'title' => $post->post_title,
						'lat'   => $loc['lat'],
						'lng'   => $loc['lng'],
						'loc'   => $loc['title'],
						'url'   => get_permalink( $post->ID ),
						'date'  => get_post_meta( $post->ID, '_event_start_date', true ),
					);
				}
			}
		}

		echo '<div class="a-eve-map-container">';
		echo '<div id="a-eve-map" class="a-eve-map" data-markers="' . esc_attr( wp_json_encode( $markers ) ) . '" style="height:400px;"></div>';

		/**
		 * Hook após renderizar mapa
		 *
		 * @param array  $markers  Marcadores do mapa.
		 * @param string $style    Estilo ativo.
		 */
		do_action( 'apollo_event_after_map', $markers, $style );

		echo '</div>';
	}
}
