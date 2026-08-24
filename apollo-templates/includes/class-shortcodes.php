<?php
/**
 * Shortcode Registrations
 *
 * Registers all Apollo shortcodes and wires them to template files.
 * Pattern: shortcode_atts() → on-demand wp_enqueue → apollo_get_template_html()
 *
 * Registry compliance:
 *   - [apollo_events]    attrs: limit, category, type, upcoming, style
 *   - [apollo_event]     attrs: id, style
 *   - [apollo_calendar]  attrs: type, month, year
 *   - [apollo_event_form]
 *
 * @package Apollo\Templates
 */

declare(strict_types=1);

namespace Apollo\Templates;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Shortcodes
 */
final class Shortcodes {

	/**
	 * Singleton instance
	 *
	 * @var Shortcodes|null
	 */
	private static ?Shortcodes $instance = null;

	/**
	 * Get instance
	 *
	 * @return Shortcodes
	 */
	public static function get_instance(): Shortcodes {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register all shortcodes
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( 'apollo_events', array( $this, 'render_events' ) );
		add_shortcode( 'apollo_event', array( $this, 'render_event' ) );
		add_shortcode( 'apollo_calendar', array( $this, 'render_calendar' ) );
		add_shortcode( 'apollo_event_form', array( $this, 'render_event_form' ) );
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		[apollo_events] — Event listing grid
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render events listing/grid.
	 *
	 * Usage:
	 *   [apollo_events]
	 *   [apollo_events limit="6" category="techno" upcoming="true" style="01"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_events( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'limit'    => 12,
				'category' => '',
				'type'     => '',
				'upcoming' => 'true',
				'style'    => '01',
				'columns'  => 3,
				'orderby'  => 'meta_value',
				'order'    => 'ASC',
			),
			$atts,
			'apollo_events'
		);

		// On-demand asset loading (never loads CSS on pages without the shortcode).
		$this->enqueue_event_assets( $atts['style'] );

		// Build WP_Query args.
		$query_args = $this->build_events_query( $atts );
		$query      = new \WP_Query( $query_args );

		// Render via template engine.
		$html = apollo_get_template_html(
			'event/listing.php',
			array(
				'events'  => $query,
				'atts'    => $atts,
				'style'   => $atts['style'],
				'columns' => absint( $atts['columns'] ),
			)
		);

		wp_reset_postdata();

		return $html;
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		[apollo_event] — Single event card
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render a single event card.
	 *
	 * Usage:
	 *   [apollo_event id="123"]
	 *   [apollo_event id="123" style="01"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_event( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id'    => 0,
				'style' => '01',
			),
			$atts,
			'apollo_event'
		);

		$event_id = absint( $atts['id'] );
		if ( ! $event_id ) {
			return '<!-- apollo_event: id obrigatório -->';
		}

		$event = get_post( $event_id );
		if ( ! $event || 'event' !== $event->post_type ) {
			return '<!-- apollo_event: evento não encontrado -->';
		}

		$this->enqueue_event_assets( $atts['style'] );

		return apollo_get_template_html(
			'event/card-style-' . sanitize_file_name( $atts['style'] ) . '.php',
			array(
				'event' => $event,
				'atts'  => $atts,
			)
		);
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		[apollo_calendar] — Calendar view
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render calendar view.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_calendar( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'type'  => 'grid',
				'month' => (int) current_time( 'n' ),
				'year'  => (int) current_time( 'Y' ),
			),
			$atts,
			'apollo_calendar'
		);

		$this->enqueue_event_assets( 'calendar' );

		return apollo_get_template_html(
			'event/calendar.php',
			array(
				'atts' => $atts,
			)
		);
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		[apollo_event_form] — Event creation form
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * Render event creation/edit form.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_event_form( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			$atts,
			'apollo_event_form'
		);

		if ( ! is_user_logged_in() ) {
			return '<p class="a-notice">' . esc_html__( 'Você precisa estar logado para criar um evento.', 'apollo-templates' ) . '</p>';
		}

		return apollo_get_template_html(
			'event/form.php',
			array(
				'atts' => $atts,
			)
		);
	}

	/*
	═══════════════════════════════════════════════════════════════════════
		PRIVATE HELPERS
		═══════════════════════════════════════════════════════════════════════ */

	/**
	 * On-demand event CSS + JS loading.
	 *
	 * @param string $style Style variation identifier.
	 * @return void
	 */
	private function enqueue_event_assets( string $style = '01' ): void {
		// Main event card CSS — always loaded for event shortcodes.
		if ( ! wp_style_is( 'apollo-event-card', 'enqueued' ) ) {
			wp_enqueue_style( 'apollo-event-card' );
		}

		// Style-specific CSS (e.g. event-card-02.css) if exists.
		$style_handle = 'apollo-event-card-' . $style;
		$style_file   = APOLLO_TEMPLATES_DIR . 'assets/css/event-card-' . $style . '.css';

		if ( $style !== '01' && file_exists( $style_file ) && ! wp_style_is( $style_handle, 'enqueued' ) ) {
			wp_enqueue_style(
				$style_handle,
				APOLLO_TEMPLATES_URL . 'assets/css/event-card-' . $style . '.css',
				array( 'apollo-event-card' ),
				APOLLO_TEMPLATES_VERSION
			);
		}
	}

	/**
	 * Build WP_Query arguments from shortcode attributes.
	 *
	 * @param array $atts Parsed shortcode attributes.
	 * @return array WP_Query args.
	 */
	private function build_events_query( array $atts ): array {
		$args = array(
			'post_type'      => 'event',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
			'orderby'        => $atts['orderby'],
			'order'          => $atts['order'],
		);

		// Upcoming events filter: _event_start_date >= today.
		if ( filter_var( $atts['upcoming'], FILTER_VALIDATE_BOOLEAN ) ) {
			$args['meta_key']   = '_event_start_date';
			$args['meta_query'] = array(
				array(
					'key'     => '_event_start_date',
					'value'   => current_time( 'Y-m-d' ),
					'compare' => '>=',
					'type'    => 'DATE',
				),
			);
		}

		// Taxonomy filters.
		$tax_query = array();

		if ( ! empty( $atts['category'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'event_category',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['category'] ) ),
			);
		}

		if ( ! empty( $atts['type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'event_type',
				'field'    => 'slug',
				'terms'    => array_map( 'trim', explode( ',', $atts['type'] ) ),
			);
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		return $args;
	}
}
