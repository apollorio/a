<?php
/**
 * Plugin Name: Apollo Lux Panels
 * Description: Luxury-grade advanced data panels for the event, dj and local CPTs — one high-priority tabbed metabox per CPT, 1:1 with the Apollo add-new form design system.
 * Version:     1.2.0
 * Author:      Apollo::Rio
 * Requires PHP: 8.1
 * Text Domain: apollo-lux-panels
 *
 * @package Apollo\LuxPanels
 */

/*
 * ARCH: apollo-lux-panels
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-lux-panels   6 arquivos PHP, 1732 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        emite HTML (52); chrome é do apollo-templates
 * META      2 chaves tocadas
 * REST      nenhuma rota
 * REQUIRES  nenhuma dependência confirmada
 *
 * NÃO FAÇA
 *   - registrar CPT direto: apollo-core é o dono do init:5.
 *     Fallback do owner só com post_type_exists().
 *   - gravar meta de outro domínio (hoje 227 chaves não têm dono).
 *   - registrar um segundo namespace REST. Só apollo/v1.
 *     Já existe um namespace fora do padrão no apollo-telegram.
 *   - add_shortcode() sem shortcode_exists(): o último a registrar
 *     vence em silêncio e quem roda vira acidente de ordem de carga.
 *
 * VERIFICAR   node D:/dev/_cos/verify/plugin-audit.js
 */

declare(strict_types=1);

namespace Apollo\LuxPanels;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_LUX_VERSION', '1.2.0' ); /* 1.2.0 — realigned with the docblock (plan-001 · T-2). The constant had lagged at 1.1.0 while the header said 1.2.0: WordPress reads the docblock, cache-busting reads this, so 1.2.0 code was being served under 1.1.0 asset URLs. 1.1.0 shipped: schema validator; repeater cols fatal fixed; sanitize_callback for every type; select enum validated on save; per-field cap. */
define( 'APOLLO_LUX_FILE', __FILE__ );
define( 'APOLLO_LUX_URL', plugin_dir_url( __FILE__ ) );
define( 'APOLLO_LUX_DIR', plugin_dir_path( __FILE__ ) );

require_once APOLLO_LUX_DIR . 'includes/Panel.php';
/*
 * Panel Contract — apollo_panel_register(). Must load AFTER Panel.php, which it
 * extends. Lets any plugin declare an admin editing surface with a plain array,
 * no subclass and no edit to this file. Sibling of apollo_surface_register()
 * and apollo_card_register() in apollo-core.
 */
require_once APOLLO_LUX_DIR . 'includes/registry.php';
require_once APOLLO_LUX_DIR . 'includes/EventPanel.php';
require_once APOLLO_LUX_DIR . 'includes/DjPanel.php';
require_once APOLLO_LUX_DIR . 'includes/LocPanel.php';

final class Plugin {

	/** @var Panel[] */
	private array $panels = array();

	/** @var string[] CPTs handled by this plugin. */
	private const POST_TYPES = array( 'event', 'dj', 'local' );

	public function __construct() {
		$this->panels = array( new EventPanel(), new DjPanel(), new LocPanel() );
	}

	public function boot(): void {
		foreach ( $this->panels as $panel ) {
			$panel->boot();
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_head', array( $this, 'reorder_metabox' ) );
		/*
		 * Hide native block-editor + classic metabox UI for taxonomies the Lux
		 * chip picker owns. Prevents dual-write with Gutenberg REST / default
		 * checklists that raced Lux multi-select on save.
		 */
		add_filter( 'rest_prepare_taxonomy', array( $this, 'hide_lux_owned_tax_ui' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'remove_native_tax_metaboxes' ), 100 );
	}

	/**
	 * Keep show_in_rest, but hide sound/season from the document sidebar UI.
	 *
	 * @param \WP_REST_Response $response Taxonomy response.
	 * @param \WP_Taxonomy      $taxonomy Taxonomy object.
	 */
	public function hide_lux_owned_tax_ui( \WP_REST_Response $response, \WP_Taxonomy $taxonomy ): \WP_REST_Response {
		if ( ! in_array( $taxonomy->name, array( 'sound', 'season' ), true ) ) {
			return $response;
		}
		$data = $response->get_data();
		if ( isset( $data['visibility'] ) && is_array( $data['visibility'] ) ) {
			$data['visibility']['show_ui'] = false;
		}
		$response->set_data( $data );
		return $response;
	}

	/** Remove default hierarchical tax metaboxes duplicated by Lux pickers. */
	public function remove_native_tax_metaboxes(): void {
		foreach ( self::POST_TYPES as $cpt ) {
			remove_meta_box( 'sounddiv', $cpt, 'side' );
			remove_meta_box( 'seasondiv', $cpt, 'side' );
		}
	}

	/** Enqueue CSS/JS + WP media + Leaflet + RemixIcon only on the 3 CPT edit screens. */
	public function assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, self::POST_TYPES, true ) ) {
			return;
		}

		wp_enqueue_media();

		// RemixIcon (icons used across the panel).
		wp_enqueue_style( 'apollo-lux-remixicon', 'https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css', array(), '4.2.0' );

		// Leaflet (map preview) — loaded on all three, used by loc + event venue.
		// cdn.jsdelivr.net, not unpkg.com — unpkg isn't in the CSP allowlist.
		wp_enqueue_style( 'apollo-lux-leaflet', 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
		wp_enqueue_script( 'apollo-lux-leaflet', 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );

		wp_enqueue_style( 'apollo-lux-panel', APOLLO_LUX_URL . 'assets/lux-panel.css', array(), APOLLO_LUX_VERSION );
		wp_enqueue_script( 'apollo-lux-panel', APOLLO_LUX_URL . 'assets/lux-panel.js', array( 'apollo-lux-leaflet' ), APOLLO_LUX_VERSION, true );

		// Co-authors picker catalog (event screen only) — same source + shape as
		// the frontend /novo-evento widget (APOLLO_EVENT_FORM.users), so the two
		// pickers show identical results and stay in sync.
		if ( 'event' === $screen->post_type && function_exists( 'apollo_event_get_all_users_for_coauthor_picker' ) ) {
			wp_localize_script(
				'apollo-lux-panel',
				'apolloLuxUsers',
				apollo_event_get_all_users_for_coauthor_picker( get_current_user_id() )
			);
		}
	}

	/** Force the luxury panel above every other metabox (incl. SEO boxes). */
	public function reorder_metabox(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, self::POST_TYPES, true ) ) {
			return;
		}
		echo '<style>#apollo_lux_panel{order:-999;}#normal-sortables{display:flex;flex-direction:column;}#apollo_lux_panel{order:-1;}</style>';
	}
}

add_action(
	'plugins_loaded',
	static function () {
		( new Plugin() )->boot();
	}
);
