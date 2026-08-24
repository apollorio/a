<?php

/**
 * Admin — Columns, Meta Boxes, Settings, Filters
 *
 * @package Apollo\Journal
 */

declare(strict_types=1);

namespace Apollo\Journal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin handler.
 */
class Admin {


	/**
	 * Wire admin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Settings page.
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Posts list: NREP column for post, journal_news, journal_nota.
		add_filter( 'manage_post_posts_columns', array( $this, 'add_nrep_column' ) );
		add_action( 'manage_post_posts_custom_column', array( $this, 'render_nrep_column' ), 10, 2 );
		add_filter( 'manage_edit-post_sortable_columns', array( $this, 'sortable_nrep_column' ) );

		add_filter( 'manage_journal_news_posts_columns', array( $this, 'add_nrep_column' ) );
		add_action( 'manage_journal_news_posts_custom_column', array( $this, 'render_nrep_column' ), 10, 2 );
		add_filter( 'manage_edit-journal_news_sortable_columns', array( $this, 'sortable_nrep_column' ) );

		add_filter( 'manage_journal_nota_posts_columns', array( $this, 'add_nrep_column' ) );
		add_action( 'manage_journal_nota_posts_custom_column', array( $this, 'render_nrep_column' ), 10, 2 );
		add_filter( 'manage_edit-journal_nota_sortable_columns', array( $this, 'sortable_nrep_column' ) );

		add_action( 'pre_get_posts', array( $this, 'sort_by_nrep' ) );

		// Admin filters for custom taxonomies.
		add_action( 'restrict_manage_posts', array( $this, 'taxonomy_filters' ) );

		// Meta box for post, journal_news, and journal_nota.
		add_action( 'add_meta_boxes', array( $this, 'add_nrep_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_nrep_meta' ), 10, 2 );

		// Dashboard widget.
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );

		// Admin styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	/**
	 * Enqueue admin-only styles.
	 *
	 * @return void
	 */
	public function enqueue_admin_styles(): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$screens = array( 'settings_page_apollo-journal', 'dashboard', 'edit-post', 'post',
			'edit-journal_news', 'journal_news', 'edit-journal_nota', 'journal_nota' );

		if ( ! in_array( $screen->id, $screens, true ) ) {
			return;
		}

		wp_add_inline_style( 'wp-admin', $this->get_admin_css() );
	}

	/**
	 * Dashboard widget.
	 *
	 * @return void
	 */
	public function add_dashboard_widget(): void {
		wp_add_dashboard_widget(
			'aj_dashboard_widget',
			'<span style="display:inline-flex;align-items:center;gap:6px"><span style="color:#FF9820">&#9672;</span> Apollo Journal</span>',
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget content.
	 *
	 * @return void
	 */
	public function render_dashboard_widget(): void {
		global $wpdb;

		$year = gmdate( 'Y' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$nrep_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_nrep_year' AND meta_value = %s",
				$year
			)
		);

		$total_posts  = wp_count_posts( 'post' );
		$total_news   = wp_count_posts( 'journal_news' );
		$total_notas  = wp_count_posts( 'journal_nota' );
		$published    = (int) $total_posts->publish + (int) $total_news->publish + (int) $total_notas->publish;

		$recent = get_posts(
			array(
				'post_type'      => array( 'post', 'journal_news', 'journal_nota' ),
				'posts_per_page' => 5,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_nrep_code',
						'compare' => 'EXISTS',
					),
				),
			)
		);
		?>
		<div class="aj-dash">
			<div class="aj-dash__stats">
				<div class="aj-dash__stat">
					<span class="aj-dash__num"><?php echo esc_html( (string) $published ); ?></span>
					<span class="aj-dash__label">Artigos</span>
				</div>
				<div class="aj-dash__stat aj-dash__stat--nrep">
					<span class="aj-dash__num"><?php echo esc_html( (string) $nrep_count ); ?></span>
					<span class="aj-dash__label">NREP <?php echo esc_html( $year ); ?></span>
				</div>
			</div>
			<?php if ( ! empty( $recent ) ) : ?>
				<h4 style="margin:12px 0 6px;font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:#666">Últimos NREP</h4>
				<ul class="aj-dash__list">
					<?php foreach ( $recent as $r ) : ?>
						<li>
							<a href="<?php echo esc_url( get_edit_post_link( $r->ID ) ); ?>">
								<strong><?php echo esc_html( get_post_meta( $r->ID, '_nrep_code', true ) ); ?></strong>
								— <?php echo esc_html( wp_trim_words( get_the_title( $r ), 8 ) ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p style="margin-top:12px;text-align:right">
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=apollo-journal' ) ); ?>" class="button button-small">Configurações</a>
			</p>
		</div>
		<?php
	}

	// ─────────────────────────────────────────────────────────────────────
	// SETTINGS PAGE
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Add settings sub-menu.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_submenu_page(
			'options-general.php',
			'Apollo Journal',
			'Apollo Journal',
			'manage_options',
			'apollo-journal',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'aj_settings',
			'aj_nrep_prefix',
			array(
				'type'              => 'string',
				'default'           => 'NREP.',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'aj_settings',
			'aj_nrep_format',
			array(
				'type'              => 'string',
				'default'           => '%prefix%%year%-%seq%',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$year = gmdate( 'Y' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$nrep_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_nrep_year' AND meta_value = %s",
				$year
			)
		);

		$total_posts  = wp_count_posts( 'post' );
		$total_news   = wp_count_posts( 'journal_news' );
		$total_notas  = wp_count_posts( 'journal_nota' );
		$published    = (int) $total_posts->publish + (int) $total_news->publish + (int) $total_notas->publish;
		$tax_counts = array();
		foreach ( array( 'music', 'culture', 'rio', 'formato' ) as $tax ) {
			$tax_counts[ $tax ] = wp_count_terms(
				array(
					'taxonomy'   => $tax,
					'hide_empty' => false,
				)
			);
		}
		?>
		<div class="wrap aj-settings">
			<!-- Header Card -->
			<div class="aj-settings__header">
				<div class="aj-settings__brand">
					<span class="aj-settings__icon">&#9672;</span>
					<div>
						<h1>Apollo Journal</h1>
						<p class="aj-settings__version">v<?php echo esc_html( APOLLO_JOURNAL_VERSION ); ?> &middot; Layer L4</p>
					</div>
				</div>
				<div class="aj-settings__badges">
					<span class="aj-badge aj-badge--primary"><?php echo esc_html( (string) $published ); ?> artigos</span>
					<span class="aj-badge aj-badge--nrep"><?php echo esc_html( (string) $nrep_count ); ?> NREP <?php echo esc_html( $year ); ?></span>
				</div>
			</div>

			<!-- Stats Grid -->
			<div class="aj-settings__grid">
				<div class="aj-settings__card">
					<h3>Taxonomias</h3>
					<ul class="aj-settings__tax-list">
						<li><span class="aj-tax-dot" style="background:#FF9820"></span> Música <strong><?php echo esc_html( (string) $tax_counts['music'] ); ?></strong></li>
						<li><span class="aj-tax-dot" style="background:#7c3aed"></span> Cultura <strong><?php echo esc_html( (string) $tax_counts['culture'] ); ?></strong></li>
						<li><span class="aj-tax-dot" style="background:#0ea5e9"></span> Rio <strong><?php echo esc_html( (string) $tax_counts['rio'] ); ?></strong></li>
						<li><span class="aj-tax-dot" style="background:#10b981"></span> Formato <strong><?php echo esc_html( (string) $tax_counts['formato'] ); ?></strong></li>
					</ul>
				</div>
				<div class="aj-settings__card">
					<h3>Shortcodes</h3>
					<ul class="aj-settings__shortcode-list">
						<li><code>[apollo_journal]</code> News grid</li>
						<li><code>[apollo_journal_marquee]</code> Ticker</li>
						<li><code>[apollo_journal_card]</code> Card embed</li>
					</ul>
				</div>
				<div class="aj-settings__card">
					<h3>REST API</h3>
					<p><code>GET /apollo/v1/journal/posts</code></p>
					<p class="description">Paginação, filtros por category, taxonomy, term.</p>
				</div>
			</div>

			<!-- Settings Form -->
			<div class="aj-settings__card aj-settings__card--form">
				<h3>Configurações NREP</h3>
				<form method="post" action="options.php">
					<?php settings_fields( 'aj_settings' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="aj_nrep_prefix"><?php esc_html_e( 'Prefixo NREP', 'apollo-journal' ); ?></label>
							</th>
							<td>
								<input
									type="text"
									id="aj_nrep_prefix"
									name="aj_nrep_prefix"
									value="<?php echo esc_attr( get_option( 'aj_nrep_prefix', 'NREP.' ) ); ?>"
									class="regular-text">
								<p class="description"><?php esc_html_e( 'Prefixo usado no código (ex: NREP.)', 'apollo-journal' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Admin CSS for Apollo-branded settings page and dashboard widget.
	 *
	 * @return string
	 */
	private function get_admin_css(): string {
		return '
        /* ── Apollo Journal Settings ── */
        .aj-settings__header{display:flex;align-items:center;justify-content:space-between;padding:24px 28px;background:linear-gradient(135deg,#1a1a2e,#16213e);border-radius:12px;margin:20px 0 16px;color:#fff}
        .aj-settings__brand{display:flex;align-items:center;gap:14px}
        .aj-settings__icon{font-size:28px;color:#FF9820}
        .aj-settings__header h1{margin:0;font-size:22px;font-weight:700;letter-spacing:-.02em}
        .aj-settings__version{margin:4px 0 0;font-size:12px;opacity:.6}
        .aj-settings__badges{display:flex;gap:8px}
        .aj-badge{display:inline-flex;align-items:center;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;letter-spacing:.02em}
        .aj-badge--primary{background:rgba(244,95,0,.15);color:#FF9820}
        .aj-badge--nrep{background:rgba(229,57,53,.12);color:#e53935}
        .aj-settings__grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:16px}
        @media(max-width:960px){.aj-settings__grid{grid-template-columns:1fr}}
        .aj-settings__card{background:#fff;border:1px solid #e0e0e0;border-radius:10px;padding:20px 24px}
        .aj-settings__card h3{margin:0 0 12px;font-size:14px;font-weight:600;color:#1a1a2e;letter-spacing:-.01em}
        .aj-settings__card--form{grid-column:1/-1}
        .aj-settings__tax-list{list-style:none;padding:0;margin:0}
        .aj-settings__tax-list li{display:flex;align-items:center;gap:8px;padding:5px 0;font-size:13px;color:#444}
        .aj-settings__tax-list li strong{margin-left:auto;color:#1a1a2e}
        .aj-tax-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
        .aj-settings__shortcode-list{list-style:none;padding:0;margin:0}
        .aj-settings__shortcode-list li{padding:4px 0;font-size:13px;color:#555}
        .aj-settings__shortcode-list code{background:#f5f5f5;padding:2px 8px;border-radius:4px;font-size:11px;color:#e53935}
        /* ── NREP Column ── */
        .column-nrep{width:120px}
        .column-nrep .aj-nrep-col{display:inline-flex;align-items:center;gap:4px;font-family:monospace;font-size:11px;font-weight:700;color:#e53935;background:rgba(229,57,53,.08);padding:2px 8px;border-radius:4px}
        /* ── Dashboard Widget ── */
        .aj-dash__stats{display:flex;gap:12px;margin-bottom:8px}
        .aj-dash__stat{flex:1;text-align:center;padding:12px 8px;background:#f9f9f9;border-radius:8px}
        .aj-dash__stat--nrep{background:rgba(229,57,53,.06)}
        .aj-dash__num{display:block;font-size:24px;font-weight:700;color:#1a1a2e;line-height:1.2}
        .aj-dash__stat--nrep .aj-dash__num{color:#e53935}
        .aj-dash__label{font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.08em}
        .aj-dash__list{list-style:none;padding:0;margin:0}
        .aj-dash__list li{padding:3px 0;font-size:12px;border-bottom:1px solid #f0f0f0}
        .aj-dash__list li:last-child{border-bottom:none}
        .aj-dash__list a{text-decoration:none;color:#444}
        .aj-dash__list a:hover{color:#FF9820}
        .aj-dash__list strong{color:#e53935;margin-right:4px}
        ';
	}

	// ─────────────────────────────────────────────────────────────────────
	// ADMIN COLUMNS
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Add NREP code column to posts list.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public function add_nrep_column( array $columns ): array {
		$columns['nrep'] = 'NREP';
		return $columns;
	}

	/**
	 * Render NREP column value.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_nrep_column( string $column, int $post_id ): void {
		if ( 'nrep' !== $column ) {
			return;
		}

		$code = get_post_meta( $post_id, '_nrep_code', true );

		if ( $code ) {
			echo '<span class="aj-nrep-col">' . esc_html( $code ) . '</span>';
		} else {
			echo '<span style="color:#ccc">—</span>';
		}
	}

	/**
	 * Make NREP column sortable.
	 *
	 * @param array<string,string> $columns Sortable columns.
	 * @return array<string,string>
	 */
	public function sortable_nrep_column( array $columns ): array {
		$columns['nrep'] = '_nrep_seq';
		return $columns;
	}

	/**
	 * Handle sorting by NREP sequence.
	 *
	 * @param \WP_Query $query Main query.
	 * @return void
	 */
	public function sort_by_nrep( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( '_nrep_seq' !== $query->get( 'orderby' ) ) {
			return;
		}

		// Only apply to NREP-capable post types.
		$allowed = array( 'post', 'journal_news', 'journal_nota' );
		$post_type = (array) $query->get( 'post_type' );
		if ( ! array_intersect( $post_type ?: array( 'post' ), $allowed ) ) {
			return;
		}

		$query->set( 'meta_key', '_nrep_seq' );
		$query->set( 'orderby', 'meta_value_num' );
	}

	// ─────────────────────────────────────────────────────────────────────
	// TAXONOMY FILTERS
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Add custom taxonomy dropdowns to posts list filter bar.
	 *
	 * @return void
	 */
	public function taxonomy_filters(): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$allowed_screens = array( 'edit-post', 'edit-journal_news', 'edit-journal_nota' );
		if ( ! in_array( $screen->id, $allowed_screens, true ) ) {
			return;
		}

		$taxes = array( 'music', 'culture', 'rio', 'formato' );

		foreach ( $taxes as $tax ) {
			$taxonomy = get_taxonomy( $tax );
			if ( ! $taxonomy ) {
				continue;
			}

			wp_dropdown_categories(
				array(
					'taxonomy'        => $tax,
					'name'            => $tax,
					'show_option_all' => $taxonomy->labels->name,
					'selected'        => isset( $_GET[ $tax ] ) ? absint( $_GET[ $tax ] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
					'hierarchical'    => true,
					'show_count'      => true,
					'hide_empty'      => false,
				)
			);
		}
	}

	// ─────────────────────────────────────────────────────────────────────
	// META BOX
	// ─────────────────────────────────────────────────────────────────────

	/**
	 * Register NREP meta box on post editor.
	 *
	 * @return void
	 */
	public function add_nrep_meta_box(): void {
		$screens = array( 'post', 'journal_news', 'journal_nota' );
		add_meta_box(
			'aj_nrep_box',
			'Nota de Repúdio (NREP)',
			array( $this, 'render_nrep_meta_box' ),
			$screens,
			'side',
			'high'
		);
	}

	/**
	 * Render the NREP meta box content.
	 *
	 * @param \WP_Post $post Current post.
	 * @return void
	 */
	public function render_nrep_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'aj_nrep_save', 'aj_nrep_nonce' );

		$code = get_post_meta( $post->ID, '_nrep_code', true );
		$year = get_post_meta( $post->ID, '_nrep_year', true );
		$seq  = get_post_meta( $post->ID, '_nrep_seq', true );
		?>
		<p>
			<label for="aj_nrep_code"><strong><?php esc_html_e( 'Código NREP', 'apollo-journal' ); ?></strong></label>
			<input
				type="text"
				id="aj_nrep_code"
				name="aj_nrep_code"
				value="<?php echo esc_attr( $code ); ?>"
				class="widefat"
				placeholder="<?php esc_attr_e( 'Auto-gerado ao publicar', 'apollo-journal' ); ?>">
		</p>
		<?php if ( $year && $seq ) : ?>
			<p class="description">
				<?php
				printf(
					/* translators: 1: year, 2: sequence number */
					esc_html__( 'Ano: %1$s | Sequência: %2$s', 'apollo-journal' ),
					esc_html( $year ),
					esc_html( $seq )
				);
				?>
			</p>
		<?php endif; ?>
		<p class="description">
			<?php esc_html_e( 'O código é gerado automaticamente quando um post da categoria "nota-de-repudio" é publicado pela primeira vez. Use este campo para correção manual se necessário.', 'apollo-journal' ); ?>
		</p>
		<?php
	}

	/**
	 * Save NREP meta box data.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 * @return void
	 */
	public function save_nrep_meta( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['aj_nrep_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( $_POST['aj_nrep_nonce'] ), 'aj_nrep_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['aj_nrep_code'] ) ) {
			$code = sanitize_text_field( wp_unslash( $_POST['aj_nrep_code'] ) );
			update_post_meta( $post_id, '_nrep_code', $code );
		}
	}
}