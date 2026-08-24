<?php

/**
 * Admin Controller — admin menu pages, AJAX handlers
 *
 * Provides: List view, Add/Edit editor, Import page, Export page, Settings.
 * Uses AJAX for cell data save (spreadsheet editor).
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets\Admin;

use Apollo\Sheets\Model;
use Apollo\Sheets\Import;
use Apollo\Sheets\Export;
use Apollo\Sheets\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Controller {


	/**
	 * Init admin hooks
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'wp_ajax_apollo_sheets_save', array( $this, 'ajax_save' ) );
		add_action( 'wp_ajax_apollo_sheets_add_row', array( $this, 'ajax_add_row' ) );
		add_action( 'wp_ajax_apollo_sheets_add_col', array( $this, 'ajax_add_col' ) );
		add_action( 'wp_ajax_apollo_sheets_delete', array( $this, 'ajax_delete' ) );
		add_action( 'wp_ajax_apollo_sheets_import', array( $this, 'ajax_import' ) );
		add_action( 'wp_ajax_apollo_sheets_export', array( $this, 'ajax_export' ) );
		add_action( 'wp_ajax_apollo_sheets_preview', array( $this, 'ajax_preview' ) );
		add_action( 'wp_ajax_apollo_sheets_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_apollo_sheets_export_zip', array( $this, 'ajax_export_zip' ) );
		add_action( 'load-apollo-sheets_page_apollo-sheets', array( $this, 'handle_screen_options' ) );
	}

	/**
	 * Register admin menus
	 */
	public function register_menus(): void {
		$capability = 'edit_posts';

		// Main menu
		add_menu_page(
			__( 'Apollo Sheets', 'apollo-sheets' ),
			__( 'Sheets', 'apollo-sheets' ),
			$capability,
			'apollo-sheets',
			array( $this, 'page_list' ),
			'dashicons-grid-view',
			32
		);

		// List (same as main)
		add_submenu_page(
			'apollo-sheets',
			__( 'Todas as Sheets', 'apollo-sheets' ),
			__( 'Todas', 'apollo-sheets' ),
			$capability,
			'apollo-sheets',
			array( $this, 'page_list' )
		);

		// Add new
		add_submenu_page(
			'apollo-sheets',
			__( 'Nova Sheet', 'apollo-sheets' ),
			__( 'Nova', 'apollo-sheets' ),
			$capability,
			'apollo-sheets-add',
			array( $this, 'page_edit' )
		);

		// Import
		add_submenu_page(
			'apollo-sheets',
			__( 'Importar', 'apollo-sheets' ),
			__( 'Importar', 'apollo-sheets' ),
			$capability,
			'apollo-sheets-import',
			array( $this, 'page_import' )
		);

		// Export
		add_submenu_page(
			'apollo-sheets',
			__( 'Exportar', 'apollo-sheets' ),
			__( 'Exportar', 'apollo-sheets' ),
			$capability,
			'apollo-sheets-export',
			array( $this, 'page_export' )
		);

		// Settings
		add_submenu_page(
			'apollo-sheets',
			__( 'Configurações', 'apollo-sheets' ),
			__( 'Configurações', 'apollo-sheets' ),
			'manage_options',
			'apollo-sheets-settings',
			array( $this, 'page_settings' )
		);
	}

	// ═══════════════════════════════════════════════════════════════
	// ADMIN PAGES
	// ═══════════════════════════════════════════════════════════════

	/**
	 * List all sheets
	 */
	public function page_list(): void {
		$model  = new Model();
		$sheets = $model->load_all();

		wp_enqueue_style( 'apollo-sheets-admin' );

		?>
		<div class="wrap apollo-sheets-admin">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Apollo Sheets', 'apollo-sheets' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-sheets-add' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Nova Sheet', 'apollo-sheets' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-sheets-import' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Importar', 'apollo-sheets' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php $this->render_admin_notices(); ?>

			<?php if ( empty( $sheets ) ) : ?>
				<div class="apollo-sheets-empty">
					<p><?php esc_html_e( 'Nenhuma sheet criada ainda.', 'apollo-sheets' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-sheets-add' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Criar Primeira Sheet', 'apollo-sheets' ); ?>
					</a>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped apollo-sheets-table">
					<thead>
						<tr>
							<th class="column-id" style="width:60px"><?php esc_html_e( 'ID', 'apollo-sheets' ); ?></th>
							<th class="column-name"><?php esc_html_e( 'Nome', 'apollo-sheets' ); ?></th>
							<th class="column-shortcode" style="width:200px"><?php esc_html_e( 'Shortcode', 'apollo-sheets' ); ?></th>
							<th class="column-author" style="width:150px"><?php esc_html_e( 'Autor', 'apollo-sheets' ); ?></th>
							<th class="column-date" style="width:160px"><?php esc_html_e( 'Modificado', 'apollo-sheets' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $sheets as $sheet ) :
							$edit_url   = admin_url( 'admin.php?page=apollo-sheets-add&sheet_id=' . $sheet['id'] );
							$export_url = admin_url( 'admin.php?page=apollo-sheets-export&sheet_id=' . $sheet['id'] );
							?>
							<tr>
								<td class="column-id"><?php echo esc_html( $sheet['id'] ); ?></td>
								<td class="column-name">
									<strong>
										<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $sheet['name'] ); ?></a>
									</strong>
									<div class="row-actions">
										<span class="edit">
											<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Editar', 'apollo-sheets' ); ?></a> |
										</span>
										<span class="copy">
											<a href="#" data-action="copy" data-id="<?php echo esc_attr( $sheet['id'] ); ?>">
												<?php esc_html_e( 'Copiar', 'apollo-sheets' ); ?>
											</a> |
										</span>
										<span class="export">
											<a href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Exportar', 'apollo-sheets' ); ?></a> |
										</span>
										<span class="delete">
											<a href="#" data-action="delete" data-id="<?php echo esc_attr( $sheet['id'] ); ?>" class="submitdelete">
												<?php esc_html_e( 'Excluir', 'apollo-sheets' ); ?>
											</a>
										</span>
									</div>
								</td>
								<td class="column-shortcode">
									<code>[apollo-sheet id="<?php echo esc_attr( $sheet['id'] ); ?>" /]</code>
								</td>
								<td class="column-author">
									<?php echo esc_html( get_the_author_meta( 'display_name', (int) $sheet['author'] ) ); ?>
								</td>
								<td class="column-date">
									<?php
									echo esc_html(
										! empty( $sheet['last_modified'] )
											? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $sheet['last_modified'] ) )
											: '—'
									);
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<script>
			jQuery(function($) {
				// Delete action
				$('.apollo-sheets-table').on('click', '[data-action="delete"]', function(e) {
					e.preventDefault();
					if (!confirm('<?php echo esc_js( __( 'Excluir esta sheet permanentemente?', 'apollo-sheets' ) ); ?>')) {
						return;
					}
					var id = $(this).data('id');
					$.post(ajaxurl, {
						action: 'apollo_sheets_delete',
						sheet_id: id,
						_wpnonce: '<?php echo wp_create_nonce( 'apollo_sheets_admin' ); ?>'
					}, function(r) {
						if (r.success) {
							location.reload();
						} else {
							alert(r.data || 'Erro');
						}
					});
				});
				// Copy action
				$('.apollo-sheets-table').on('click', '[data-action="copy"]', function(e) {
					e.preventDefault();
					var id = $(this).data('id');
					$.post(ajaxurl, {
						action: 'apollo_sheets_save',
						do: 'copy',
						sheet_id: id,
						_wpnonce: '<?php echo wp_create_nonce( 'apollo_sheets_admin' ); ?>'
					}, function(r) {
						if (r.success) {
							location.reload();
						} else {
							alert(r.data || 'Erro');
						}
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Add / Edit sheet page (spreadsheet editor)
	 */
	public function page_edit(): void {
		$model    = new Model();
		$sheet_id = sanitize_text_field( $_GET['sheet_id'] ?? '' );
		$is_new   = empty( $sheet_id );

		if ( $is_new ) {
			$table = Model::get_default_table();
		} else {
			$table = $model->load( $sheet_id );
			if ( ! $table ) {
				wp_die( __( 'Sheet não encontrada.', 'apollo-sheets' ) );
			}
		}

		wp_enqueue_style( 'apollo-sheets-admin' );
		wp_enqueue_script( 'apollo-sheets-admin' );
		wp_localize_script(
			'apollo-sheets-admin',
			'apolloSheetsAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'apollo_sheets_admin' ),
				'sheetId' => $sheet_id,
				'isNew'   => $is_new,
				'i18n'    => array(
					'saved'         => __( 'Sheet salva!', 'apollo-sheets' ),
					'error'         => __( 'Erro ao salvar.', 'apollo-sheets' ),
					'confirmDelete' => __( 'Excluir esta célula?', 'apollo-sheets' ),
					'addRow'        => __( 'Linha adicionada.', 'apollo-sheets' ),
					'addCol'        => __( 'Coluna adicionada.', 'apollo-sheets' ),
				),
			)
		);

		$data    = $table['data'] ?? array( array( '' ) );
		$options = $table['options'] ?? Model::get_default_options();

		?>
		<div class="wrap apollo-sheets-admin apollo-sheets-editor">
			<h1>
				<?php echo $is_new ? esc_html__( 'Nova Sheet', 'apollo-sheets' ) : esc_html__( 'Editar Sheet', 'apollo-sheets' ); ?>
				<?php if ( ! $is_new ) : ?>
					<span class="apollo-sheets-id">ID: <?php echo esc_html( $sheet_id ); ?></span>
				<?php endif; ?>
			</h1>
			<hr class="wp-header-end">

			<form id="apollo-sheets-form" method="post">
				<input type="hidden" name="sheet_id" value="<?php echo esc_attr( $sheet_id ); ?>">
				<?php wp_nonce_field( 'apollo_sheets_admin', '_apollo_sheets_nonce' ); ?>

				<!-- Meta -->
				<div class="apollo-sheets-meta">
					<div class="apollo-sheets-meta-field">
						<label for="sheet-name"><?php esc_html_e( 'Nome', 'apollo-sheets' ); ?></label>
						<input type="text" id="sheet-name" name="name" value="<?php echo esc_attr( $table['name'] ?? '' ); ?>" class="regular-text" required>
					</div>
					<div class="apollo-sheets-meta-field">
						<label for="sheet-description"><?php esc_html_e( 'Descrição', 'apollo-sheets' ); ?></label>
						<input type="text" id="sheet-description" name="description" value="<?php echo esc_attr( $table['description'] ?? '' ); ?>" class="regular-text">
					</div>
					<?php if ( ! $is_new ) : ?>
						<div class="apollo-sheets-meta-field apollo-sheets-shortcode-display">
							<label><?php esc_html_e( 'Shortcode', 'apollo-sheets' ); ?></label>
							<code>[apollo-sheet id="<?php echo esc_attr( $sheet_id ); ?>" /]</code>
						</div>
					<?php endif; ?>
				</div>

				<!-- Spreadsheet Grid -->
				<div class="apollo-sheets-grid-wrap">
					<div class="apollo-sheets-toolbar">
						<button type="button" id="add-row" class="button"><?php esc_html_e( '+ Linha', 'apollo-sheets' ); ?></button>
						<button type="button" id="add-col" class="button"><?php esc_html_e( '+ Coluna', 'apollo-sheets' ); ?></button>
						<button type="button" id="remove-last-row" class="button"><?php esc_html_e( '- Linha', 'apollo-sheets' ); ?></button>
						<button type="button" id="remove-last-col" class="button"><?php esc_html_e( '- Coluna', 'apollo-sheets' ); ?></button>
					</div>

					<div class="apollo-sheets-grid" id="sheet-grid">
						<table class="apollo-sheets-edit-table">
							<thead>
								<tr>
									<th class="row-num">#</th>
									<?php for ( $c = 0; $c < count( $data[0] ?? array() ); $c++ ) : ?>
										<th><?php echo esc_html( $this->col_letter( $c ) ); ?></th>
									<?php endfor; ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $data as $r => $row ) : ?>
									<tr>
										<td class="row-num"><?php echo (int) ( $r + 1 ); ?></td>
										<?php foreach ( $row as $c => $cell ) : ?>
											<td>
												<input type="text"
													name="data[<?php echo (int) $r; ?>][<?php echo (int) $c; ?>]"
													value="<?php echo esc_attr( (string) $cell ); ?>"
													class="cell-input"
													data-row="<?php echo (int) $r; ?>"
													data-col="<?php echo (int) $c; ?>">
											</td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Options -->
				<div class="apollo-sheets-options">
					<h3><?php esc_html_e( 'Opções de Exibição', 'apollo-sheets' ); ?></h3>
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Cabeçalho', 'apollo-sheets' ); ?></th>
							<td>
								<label>
									<input type="number" name="options[table_head]" value="<?php echo (int) ( $options['table_head'] ?? 1 ); ?>" min="0" max="10" style="width:60px">
									<?php esc_html_e( 'linha(s) como cabeçalho', 'apollo-sheets' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Rodapé', 'apollo-sheets' ); ?></th>
							<td>
								<label>
									<input type="number" name="options[table_foot]" value="<?php echo (int) ( $options['table_foot'] ?? 0 ); ?>" min="0" max="10" style="width:60px">
									<?php esc_html_e( 'linha(s) como rodapé', 'apollo-sheets' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Listras alternadas', 'apollo-sheets' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="options[alternating_row_colors]" value="1" <?php checked( ! empty( $options['alternating_row_colors'] ) ); ?>>
									<?php esc_html_e( 'Cores alternadas nas linhas', 'apollo-sheets' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Mostrar nome', 'apollo-sheets' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="options[print_name]" value="1" <?php checked( ! empty( $options['print_name'] ) ); ?>>
									<?php esc_html_e( 'Exibir nome da sheet', 'apollo-sheets' ); ?>
								</label>
								<select name="options[print_name_position]">
									<option value="above" <?php selected( $options['print_name_position'] ?? 'above', 'above' ); ?>><?php esc_html_e( 'Acima', 'apollo-sheets' ); ?></option>
									<option value="below" <?php selected( $options['print_name_position'] ?? 'above', 'below' ); ?>><?php esc_html_e( 'Abaixo', 'apollo-sheets' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'DataTables JS', 'apollo-sheets' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="options[use_datatables]" value="1" <?php checked( ! empty( $options['use_datatables'] ) ); ?>>
									<?php esc_html_e( 'Ativar DataTables (ordenação, filtro, paginação)', 'apollo-sheets' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Ordenação', 'apollo-sheets' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="options[datatables_sort]" value="1" <?php checked( ! empty( $options['datatables_sort'] ) ); ?>>
									<?php esc_html_e( 'Permitir ordenação por coluna', 'apollo-sheets' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Busca', 'apollo-sheets' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="options[datatables_filter]" value="1" <?php checked( ! empty( $options['datatables_filter'] ) ); ?>>
									<?php esc_html_e( 'Mostrar campo de busca', 'apollo-sheets' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Paginação', 'apollo-sheets' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="options[datatables_paginate]" value="1" <?php checked( ! empty( $options['datatables_paginate'] ) ); ?>>
									<?php esc_html_e( 'Ativar paginação', 'apollo-sheets' ); ?>
								</label>
								<input type="number" name="options[datatables_paginate_entries]" value="<?php echo (int) ( $options['datatables_paginate_entries'] ?? 25 ); ?>" min="5" max="100" style="width:60px">
								<?php esc_html_e( 'por página', 'apollo-sheets' ); ?>
							</td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'CSS extra', 'apollo-sheets' ); ?></th>
							<td>
								<input type="text" name="options[extra_css_classes]" value="<?php echo esc_attr( $options['extra_css_classes'] ?? '' ); ?>" class="regular-text" placeholder="classe1 classe2">
							</td>
						</tr>
					</table>
				</div>

				<!-- Action buttons -->
				<div class="apollo-sheets-actions">
					<button type="submit" id="save-sheet" class="button button-primary button-large">
						<?php echo $is_new ? esc_html__( 'Criar Sheet', 'apollo-sheets' ) : esc_html__( 'Salvar Alterações', 'apollo-sheets' ); ?>
					</button>
					<?php if ( ! $is_new ) : ?>
						<button type="button" id="preview-sheet" class="button button-secondary">
							<?php esc_html_e( 'Preview', 'apollo-sheets' ); ?>
						</button>
					<?php endif; ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=apollo-sheets' ) ); ?>" class="button">
						<?php esc_html_e( 'Voltar', 'apollo-sheets' ); ?>
					</a>
					<span id="save-status" class="apollo-sheets-save-status"></span>
				</div>
			</form>

			<!-- Preview area -->
			<div id="sheet-preview" class="apollo-sheets-preview" style="display:none;">
				<h3><?php esc_html_e( 'Preview', 'apollo-sheets' ); ?></h3>
				<div id="sheet-preview-content"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Import page
	 */
	public function page_import(): void {
		wp_enqueue_style( 'apollo-sheets-admin' );
		wp_enqueue_script( 'apollo-sheets-admin' );

		?>
		<div class="wrap apollo-sheets-admin">
			<h1><?php esc_html_e( 'Importar Sheet', 'apollo-sheets' ); ?></h1>
			<hr class="wp-header-end">

			<form id="apollo-sheets-import-form" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'apollo_sheets_admin', '_apollo_sheets_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th><label for="import-name"><?php esc_html_e( 'Nome', 'apollo-sheets' ); ?></label></th>
						<td>
							<input type="text" id="import-name" name="name" class="regular-text" value="" placeholder="<?php esc_attr_e( 'Nome da nova sheet', 'apollo-sheets' ); ?>">
						</td>
					</tr>
					<tr>
						<th><label><?php esc_html_e( 'Fonte', 'apollo-sheets' ); ?></label></th>
						<td>
							<fieldset>
								<label>
									<input type="radio" name="import_source" value="file" checked>
									<?php esc_html_e( 'Upload de arquivo (CSV, JSON, HTML)', 'apollo-sheets' ); ?>
								</label><br>
								<label>
									<input type="radio" name="import_source" value="url">
									<?php esc_html_e( 'URL (Google Sheets, CSV online)', 'apollo-sheets' ); ?>
								</label><br>
								<label>
									<input type="radio" name="import_source" value="paste">
									<?php esc_html_e( 'Colar dados', 'apollo-sheets' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					<tr class="import-source-file">
						<th><label for="import-file"><?php esc_html_e( 'Arquivo', 'apollo-sheets' ); ?></label></th>
						<td>
							<input type="file" id="import-file" name="import_file" accept=".csv,.json,.html,.htm,.tsv,.txt">
						</td>
					</tr>
					<tr class="import-source-url" style="display:none">
						<th><label for="import-url"><?php esc_html_e( 'URL', 'apollo-sheets' ); ?></label></th>
						<td>
							<input type="url" id="import-url" name="import_url" class="regular-text" placeholder="https://docs.google.com/spreadsheets/d/...">
							<p class="description"><?php esc_html_e( 'URLs do Google Sheets são convertidas automaticamente.', 'apollo-sheets' ); ?></p>
						</td>
					</tr>
					<tr class="import-source-paste" style="display:none">
						<th><label for="import-data"><?php esc_html_e( 'Dados', 'apollo-sheets' ); ?></label></th>
						<td>
							<textarea id="import-data" name="import_data" rows="10" class="large-text code" placeholder="col1,col2,col3&#10;val1,val2,val3"></textarea>
						</td>
					</tr>
					<tr>
						<th><label for="import-format"><?php esc_html_e( 'Formato', 'apollo-sheets' ); ?></label></th>
						<td>
							<select id="import-format" name="format">
								<option value=""><?php esc_html_e( 'Auto-detectar', 'apollo-sheets' ); ?></option>
								<option value="csv">CSV</option>
								<option value="json">JSON</option>
								<option value="html">HTML</option>
							</select>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Importar', 'apollo-sheets' ); ?></button>
				</p>
			</form>
		</div>

		<script>
			jQuery(function($) {
				$('input[name="import_source"]').on('change', function() {
					var v = $(this).val();
					$('.import-source-file, .import-source-url, .import-source-paste').hide();
					$('.import-source-' + v).show();
				});
			});
		</script>
		<?php
	}

	/**
	 * Export page
	 */
	public function page_export(): void {
		$model       = new Model();
		$sheets      = $model->load_all();
		$preselected = sanitize_text_field( $_GET['sheet_id'] ?? '' );

		wp_enqueue_style( 'apollo-sheets-admin' );

		?>
		<div class="wrap apollo-sheets-admin">
			<h1><?php esc_html_e( 'Exportar Sheet', 'apollo-sheets' ); ?></h1>
			<hr class="wp-header-end">

			<form id="apollo-sheets-export-form" method="post">
				<?php wp_nonce_field( 'apollo_sheets_admin', '_apollo_sheets_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th><label for="export-sheet"><?php esc_html_e( 'Sheet', 'apollo-sheets' ); ?></label></th>
						<td>
							<select id="export-sheet" name="sheet_id">
								<?php foreach ( $sheets as $sheet ) : ?>
									<option value="<?php echo esc_attr( $sheet['id'] ); ?>" <?php selected( $sheet['id'], $preselected ); ?>>
										<?php echo esc_html( $sheet['name'] . ' (ID: ' . $sheet['id'] . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="export-format"><?php esc_html_e( 'Formato', 'apollo-sheets' ); ?></label></th>
						<td>
							<select id="export-format" name="format">
								<option value="csv">CSV</option>
								<option value="json">JSON</option>
								<option value="html">HTML</option>
							</select>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Exportar', 'apollo-sheets' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	// ═══════════════════════════════════════════════════════════════
	// AJAX HANDLERS
	// ═══════════════════════════════════════════════════════════════

	/**
	 * AJAX: Save sheet (create or update)
	 */
	public function ajax_save(): void {
		check_ajax_referer( 'apollo_sheets_admin', '_wpnonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Sem permissão.', 'apollo-sheets' ), 403 );
		}

		$model    = new Model();
		$sheet_id = sanitize_text_field( $_POST['sheet_id'] ?? '' );
		$do       = sanitize_key( $_POST['do'] ?? 'save' );

		// Copy action
		if ( 'copy' === $do && $sheet_id ) {
			$new_id = $model->copy( $sheet_id );
			if ( $new_id ) {
				wp_send_json_success( array( 'id' => $new_id ) );
			}
			wp_send_json_error( __( 'Erro ao copiar.', 'apollo-sheets' ) );
		}

		// Parse form data
		$name        = sanitize_text_field( $_POST['name'] ?? '' );
		$description = sanitize_text_field( $_POST['description'] ?? '' );
		$data_raw    = $_POST['data'] ?? array();
		$options_raw = $_POST['options'] ?? array();

		// Build data array
		$data = array();
		if ( is_array( $data_raw ) ) {
			ksort( $data_raw );
			foreach ( $data_raw as $r => $row ) {
				if ( is_array( $row ) ) {
					ksort( $row );
					$data[ (int) $r ] = array_map( fn( $cell ) => wp_kses_post( (string) $cell ), $row );
				}
			}
			$data = array_values( $data );
		}

		if ( empty( $data ) ) {
			$data = array( array( '' ) );
		}

		// Build options
		$options = Model::get_default_options();
		if ( is_array( $options_raw ) ) {
			foreach ( $options_raw as $key => $val ) {
				$key = sanitize_key( $key );
				if ( array_key_exists( $key, $options ) ) {
					if ( is_bool( $options[ $key ] ) ) {
						$options[ $key ] = (bool) $val;
					} elseif ( is_int( $options[ $key ] ) ) {
						$options[ $key ] = (int) $val;
					} else {
						$options[ $key ] = sanitize_text_field( (string) $val );
					}
				}
			}
		}

		// Handle unchecked checkboxes (not sent in POST)
		$checkbox_keys = array(
			'alternating_row_colors',
			'print_name',
			'print_description',
			'use_datatables',
			'datatables_sort',
			'datatables_filter',
			'datatables_paginate',
			'datatables_lengthchange',
			'datatables_info',
			'datatables_scrollx',
		);
		foreach ( $checkbox_keys as $key ) {
			if ( ! isset( $options_raw[ $key ] ) ) {
				$options[ $key ] = false;
			}
		}

		// Visibility
		$rows_count = count( $data );
		$cols_count = ! empty( $data[0] ) ? count( $data[0] ) : 0;
		$visibility = array(
			'rows'    => array_fill( 0, $rows_count, 1 ),
			'columns' => array_fill( 0, $cols_count, 1 ),
		);

		if ( empty( $sheet_id ) ) {
			// Create
			$table = array(
				'name'        => $name,
				'description' => $description,
				'data'        => $data,
				'options'     => $options,
				'visibility'  => $visibility,
			);

			$new_id = $model->add( $table );

			if ( false === $new_id ) {
				wp_send_json_error( __( 'Erro ao criar sheet.', 'apollo-sheets' ) );
			}

			wp_send_json_success(
				array(
					'id'       => $new_id,
					'redirect' => admin_url( 'admin.php?page=apollo-sheets-add&sheet_id=' . $new_id . '&saved=1' ),
				)
			);
		} else {
			// Update
			$table = array(
				'id'          => $sheet_id,
				'name'        => $name,
				'description' => $description,
				'data'        => $data,
				'options'     => $options,
				'visibility'  => $visibility,
			);

			$saved = $model->save( $table );

			if ( ! $saved ) {
				wp_send_json_error( __( 'Erro ao salvar.', 'apollo-sheets' ) );
			}

			wp_send_json_success( array( 'id' => $sheet_id ) );
		}
	}

	/**
	 * AJAX: Delete sheet
	 */
	public function ajax_delete(): void {
		check_ajax_referer( 'apollo_sheets_admin', '_wpnonce' );

		if ( ! current_user_can( 'delete_posts' ) ) {
			wp_send_json_error( __( 'Sem permissão.', 'apollo-sheets' ), 403 );
		}

		$model    = new Model();
		$sheet_id = sanitize_text_field( $_POST['sheet_id'] ?? '' );

		if ( ! $sheet_id || ! $model->delete( $sheet_id ) ) {
			wp_send_json_error( __( 'Erro ao excluir.', 'apollo-sheets' ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: Import sheet
	 */
	public function ajax_import(): void {
		check_ajax_referer( 'apollo_sheets_admin', '_wpnonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Sem permissão.', 'apollo-sheets' ), 403 );
		}

		$import = new Import();
		$model  = new Model();
		$source = sanitize_key( $_POST['import_source'] ?? 'file' );
		$format = sanitize_key( $_POST['format'] ?? '' );
		$name   = sanitize_text_field( $_POST['name'] ?? __( 'Importada', 'apollo-sheets' ) );

		$data = null;

		switch ( $source ) {
			case 'file':
				if ( ! empty( $_FILES['import_file'] ) ) {
					$data = $import->import_from_file( $_FILES['import_file'], $format );
				}
				break;
			case 'url':
				$url  = esc_url_raw( $_POST['import_url'] ?? '' );
				$data = $import->import_from_url( $url, $format );
				break;
			case 'paste':
				$raw  = wp_unslash( $_POST['import_data'] ?? '' );
				$data = $import->import( $raw, $format );
				break;
		}

		if ( ! $data || empty( $data ) ) {
			wp_send_json_error( __( 'Falha ao importar. Verifique o formato dos dados.', 'apollo-sheets' ) );
		}

		$table         = Model::get_default_table();
		$table['name'] = $name ?: __( 'Importada', 'apollo-sheets' );
		$table['data'] = array_map(
			function ( $row ) {
				return array_map( fn( $cell ) => wp_kses_post( (string) $cell ), $row );
			},
			$data
		);

		$rows                = count( $table['data'] );
		$cols                = ! empty( $table['data'][0] ) ? count( $table['data'][0] ) : 0;
		$table['visibility'] = array(
			'rows'    => array_fill( 0, $rows, 1 ),
			'columns' => array_fill( 0, $cols, 1 ),
		);

		$new_id = $model->add( $table );

		if ( false === $new_id ) {
			wp_send_json_error( __( 'Erro ao salvar sheet importada.', 'apollo-sheets' ) );
		}

		wp_send_json_success(
			array(
				'id'       => $new_id,
				'redirect' => admin_url( 'admin.php?page=apollo-sheets-add&sheet_id=' . $new_id . '&imported=1' ),
			)
		);
	}

	/**
	 * AJAX: Export sheet (download)
	 */
	public function ajax_export(): void {
		check_ajax_referer( 'apollo_sheets_admin', '_wpnonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Sem permissão.', 'apollo-sheets' ), 403 );
		}

		$sheet_id = sanitize_text_field( $_POST['sheet_id'] ?? '' );
		$format   = sanitize_key( $_POST['format'] ?? 'csv' );

		$export  = new Export();
		$content = $export->export( $sheet_id, $format );

		if ( false === $content ) {
			wp_send_json_error( __( 'Erro ao exportar.', 'apollo-sheets' ) );
		}

		$model = new Model();
		$table = $model->load( $sheet_id, false, false );
		$name  = sanitize_title( $table['name'] ?? 'sheet-' . $sheet_id );

		wp_send_json_success(
			array(
				'filename' => $name . '.' . $format,
				'content'  => $content,
				'mime'     => match ( $format ) {
					'csv'  => 'text/csv',
					'json' => 'application/json',
					'html' => 'text/html',
					default => 'text/plain',
				},
			)
		);
	}

	// ═══════════════════════════════════════════════════════════════
	// SETTINGS PAGE
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Screen Options: per-page count for the list table
	 */
	public function handle_screen_options(): void {
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Sheets por página', 'apollo-sheets' ),
				'default' => 20,
				'option'  => 'apollo_sheets_per_page',
			)
		);
	}

	/**
	 * Render the Settings page
	 */
	public function page_settings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sem permissão.', 'apollo-sheets' ) );
		}

		wp_enqueue_style( 'apollo-sheets-admin' );

		$settings = new Settings();
		$all      = $settings->get_all();
		$nonce    = wp_create_nonce( 'apollo_sheets_admin' );
		?>
		<div class="wrap apollo-sheets-admin">
			<h1><?php esc_html_e( 'Apollo Sheets — Configurações', 'apollo-sheets' ); ?></h1>
			<hr class="wp-header-end">

			<form id="apollo-sheets-settings-form" method="post">
				<?php wp_nonce_field( 'apollo_sheets_settings_save', '_settings_nonce' ); ?>

				<h2 class="title"><?php esc_html_e( 'CSS Personalizado', 'apollo-sheets' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Ativar CSS padrão', 'apollo-sheets' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="use_default_css" value="1" <?php checked( $all['use_default_css'] ); ?> />
								<?php esc_html_e( 'Incluir estilo padrão do Apollo Sheets', 'apollo-sheets' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Ativar CSS personalizado', 'apollo-sheets' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="use_custom_css" value="1" <?php checked( $all['use_custom_css'] ); ?> />
								<?php esc_html_e( 'Incluir CSS personalizado abaixo', 'apollo-sheets' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'CSS personalizado', 'apollo-sheets' ); ?></th>
						<td>
							<textarea name="custom_css" rows="12" class="large-text code" style="font-family:monospace"><?php echo esc_textarea( $all['custom_css'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'CSS aplicado em todas as tabelas do Apollo Sheets.', 'apollo-sheets' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'DataTables', 'apollo-sheets' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'DataTables padrão', 'apollo-sheets' ); ?></th>
						<td>
							<label><input type="checkbox" name="default_datatables" value="1" <?php checked( $all['default_datatables'] ); ?> /> <?php esc_html_e( 'Ativar DataTables por padrão', 'apollo-sheets' ); ?></label><br>
							<label><input type="checkbox" name="default_datatables_sort" value="1" <?php checked( $all['default_datatables_sort'] ); ?> /> <?php esc_html_e( 'Ordenação', 'apollo-sheets' ); ?></label><br>
							<label><input type="checkbox" name="default_datatables_filter" value="1" <?php checked( $all['default_datatables_filter'] ); ?> /> <?php esc_html_e( 'Filtro/busca', 'apollo-sheets' ); ?></label><br>
							<label><input type="checkbox" name="default_datatables_paginate" value="1" <?php checked( $all['default_datatables_paginate'] ); ?> /> <?php esc_html_e( 'Paginação', 'apollo-sheets' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Entradas por página', 'apollo-sheets' ); ?></th>
						<td>
							<input type="number" name="default_paginate_entries" value="<?php echo esc_attr( $all['default_paginate_entries'] ); ?>" min="1" max="500" class="small-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Responsivo', 'apollo-sheets' ); ?></th>
						<td><label><input type="checkbox" name="default_responsive" value="1" <?php checked( $all['default_responsive'] ); ?> /> <?php esc_html_e( 'Modo responsivo por padrão', 'apollo-sheets' ); ?></label></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Fórmulas e Renderização', 'apollo-sheets' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Avaliar fórmulas', 'apollo-sheets' ); ?></th>
						<td><label><input type="checkbox" name="evaluate_formulas" value="1" <?php checked( $all['evaluate_formulas'] ); ?> /> <?php esc_html_e( 'Processar fórmulas (=SUM, =IF…) nas células', 'apollo-sheets' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Cabeçalho de tabela', 'apollo-sheets' ); ?></th>
						<td>
							<select name="default_table_head">
								<option value="" <?php selected( $all['default_table_head'], '' ); ?>><?php esc_html_e( 'Nenhum', 'apollo-sheets' ); ?></option>
								<option value="first_row" <?php selected( $all['default_table_head'], 'first_row' ); ?>><?php esc_html_e( 'Primeira linha', 'apollo-sheets' ); ?></option>
								<option value="last_row" <?php selected( $all['default_table_head'], 'last_row' ); ?>><?php esc_html_e( 'Última linha (tfoot)', 'apollo-sheets' ); ?></option>
								<option value="both_rows" <?php selected( $all['default_table_head'], 'both_rows' ); ?>><?php esc_html_e( 'Primeira + Última', 'apollo-sheets' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Cores alternadas', 'apollo-sheets' ); ?></th>
						<td><label><input type="checkbox" name="default_alternating_colors" value="1" <?php checked( $all['default_alternating_colors'] ); ?> /> <?php esc_html_e( 'Linhas com cor alternada por padrão', 'apollo-sheets' ); ?></label></td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'Integração', 'apollo-sheets' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Busca WordPress', 'apollo-sheets' ); ?></th>
						<td><label><input type="checkbox" name="wp_search_integration" value="1" <?php checked( $all['wp_search_integration'] ); ?> /> <?php esc_html_e( 'Incluir dados das Sheets nos resultados de busca do WordPress', 'apollo-sheets' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Cache de output', 'apollo-sheets' ); ?></th>
						<td><label><input type="checkbox" name="cache_table_output" value="1" <?php checked( $all['cache_table_output'] ); ?> /> <?php esc_html_e( 'Cachear HTML renderizado (melhora performance)', 'apollo-sheets' ); ?></label></td>
					</tr>
				</table>

				<?php submit_button( __( 'Salvar Configurações', 'apollo-sheets' ) ); ?>
			</form>
		</div>

		<script>
		JQuery(function ($) {
			$('#apollo-sheets-settings-form').on('submit', function (e) {
				e.preventDefault();
				var data = $(this).serializeArray();
				data.push({ name: 'action', value: 'apollo_sheets_save_settings' });
				data.push({ name: '_wpnonce', value: '<?php echo esc_js( $nonce ); ?>' });
				$.post(ajaxurl, data, function (res) {
					if (res.success) {
						$('.notice-success').fadeIn();
					}
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX: Save settings
	 */
	public function ajax_save_settings(): void {
		check_ajax_referer( 'apollo_sheets_admin', '_wpnonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Sem permissão.', 'apollo-sheets' ), 403 );
		}

		$bool_fields = array(
			'use_default_css',
			'use_custom_css',
			'default_datatables',
			'default_datatables_sort',
			'default_datatables_filter',
			'default_datatables_paginate',
			'default_responsive',
			'default_alternating_colors',
			'evaluate_formulas',
			'wp_search_integration',
			'cache_table_output',
		);

		$new = array();
		foreach ( $bool_fields as $key ) {
			$new[ $key ] = ! empty( $_POST[ $key ] );
		}

		$new['custom_css']               = wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ?? '' ) );
		$new['default_paginate_entries'] = absint( $_POST['default_paginate_entries'] ?? 10 );
		$new['default_table_head']       = sanitize_key( $_POST['default_table_head'] ?? '' );

		$settings = new Settings();
		$settings->update( $new );

		wp_send_json_success( array( 'message' => __( 'Configurações salvas.', 'apollo-sheets' ) ) );
	}

	/**
	 * AJAX: Export multiple sheets as ZIP
	 */
	public function ajax_export_zip(): void {
		check_ajax_referer( 'apollo_sheets_admin', '_wpnonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Sem permissão.', 'apollo-sheets' ), 403 );
		}

		if ( ! Export::zip_available() ) {
			wp_send_json_error( __( 'PHP ZipArchive não disponível neste servidor.', 'apollo-sheets' ), 500 );
		}

		$raw_ids = wp_unslash( $_POST['sheet_ids'] ?? '' );
		$ids     = array_filter( array_map( 'sanitize_text_field', explode( ',', (string) $raw_ids ) ) );
		$format  = sanitize_key( $_POST['format'] ?? 'csv' );

		$export   = new Export();
		$zip_path = $export->export_as_zip( $ids, $format );

		if ( ! $zip_path ) {
			wp_send_json_error( __( 'Erro ao criar arquivo ZIP.', 'apollo-sheets' ) );
		}

		$export->send_zip_download( $zip_path, 'apollo-sheets-export' );
	}

	/**
	 * AJAX: Preview rendered table
	 */
	public function ajax_preview(): void {
		check_ajax_referer( 'apollo_sheets_admin', '_wpnonce' );

		$sheet_id = sanitize_text_field( $_POST['sheet_id'] ?? '' );
		if ( ! $sheet_id ) {
			wp_send_json_error( __( 'ID inválido.', 'apollo-sheets' ) );
		}

		$render = new \Apollo\Sheets\Render();
		$html   = $render->shortcode_output( array( 'id' => $sheet_id ) );

		wp_send_json_success( array( 'html' => $html ) );
	}

	// ═══════════════════════════════════════════════════════════════
	// HELPERS
	// ═══════════════════════════════════════════════════════════════

	/**
	 * Convert column index to letter (0→A, 1→B, ..., 25→Z, 26→AA)
	 */
	private function col_letter( int $index ): string {
		$letter = '';
		while ( $index >= 0 ) {
			$letter = chr( 65 + ( $index % 26 ) ) . $letter;
			$index  = intdiv( $index, 26 ) - 1;
		}
		return $letter;
	}

	/**
	 * Render admin notices from query params
	 */
	private function render_admin_notices(): void {
		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Sheet salva com sucesso!', 'apollo-sheets' ) . '</p></div>';
		}
		if ( isset( $_GET['imported'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Sheet importada com sucesso!', 'apollo-sheets' ) . '</p></div>';
		}
		if ( isset( $_GET['deleted'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Sheet excluída.', 'apollo-sheets' ) . '</p></div>';
		}
	}
}