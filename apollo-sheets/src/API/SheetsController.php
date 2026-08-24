<?php

/**
 * REST API Controller — apollo/v1/sheets
 *
 * Full CRUD + import/export endpoints for Sheets.
 * Extends Apollo Core RestBase.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets\API;

use Apollo\Core\API\RestBase;
use Apollo\Sheets\Model;
use Apollo\Sheets\Import;
use Apollo\Sheets\Export;

if (! defined('ABSPATH')) {
    exit;
}

class SheetsController extends RestBase
{


    /**
     * Route base
     */
    protected $rest_base = 'sheets';

    /**
     * Register REST routes
     */
    public function register_routes(): void
    {
        // Collection: GET (list), POST (create)
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                    'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_item'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
                    'args'                => $this->get_create_params(),
                ),
            )
        );

        // Single: GET, PUT/PATCH, DELETE
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\w]+)',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                    'args'                => array(
                        'id' => array(
                            'description'       => __('Sheet ID.', 'apollo-sheets'),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => array($this, 'update_item_permissions_check'),
                    'args'                => $this->get_update_params(),
                ),
                array(
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => array($this, 'delete_item'),
                    'permission_callback' => array($this, 'delete_item_permissions_check'),
                    'args'                => array(
                        'id' => array(
                            'description'       => __('Sheet ID.', 'apollo-sheets'),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Copy
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\w]+)/copy',
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'copy_item'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
                    'args'                => array(
                        'id' => array(
                            'description'       => __('Sheet ID to copy.', 'apollo-sheets'),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Import
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/import',
            array(
                array(
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'import_item'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
                    'args'                => $this->get_import_params(),
                ),
            )
        );

        // Export
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\w]+)/export',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'export_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                    'args'                => array(
                        'id'     => array(
                            'description'       => __('Sheet ID.', 'apollo-sheets'),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'format' => array(
                            'description'       => __('Export format.', 'apollo-sheets'),
                            'type'              => 'string',
                            'enum'              => array('csv', 'json', 'html'),
                            'default'           => 'json',
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        // Preview (render HTML without saving)
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\w]+)/preview',
            array(
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'preview_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                    'args'                => array(
                        'id' => array(
                            'description'       => __('Sheet ID.', 'apollo-sheets'),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // PERMISSIONS
    // ═══════════════════════════════════════════════════════════════

    public function get_items_permissions_check($request)
    {
        return $this->is_logged_in();
    }

    public function get_item_permissions_check($request)
    {
        return $this->is_logged_in();
    }

    public function create_item_permissions_check($request)
    {
        if (! current_user_can('edit_posts')) {
            return $this->prepare_error('rest_forbidden', __('Sem permissão para criar sheets.', 'apollo-sheets'), 403);
        }
        return true;
    }

    public function update_item_permissions_check($request)
    {
        if (! current_user_can('edit_posts')) {
            return $this->prepare_error('rest_forbidden', __('Sem permissão para editar sheets.', 'apollo-sheets'), 403);
        }
        return true;
    }

    public function delete_item_permissions_check($request)
    {
        if (! current_user_can('delete_posts')) {
            return $this->prepare_error('rest_forbidden', __('Sem permissão para excluir sheets.', 'apollo-sheets'), 403);
        }
        return true;
    }

	// ═══════════════════════════════════════════════════════════════
	// CALLBACKS
	// ═══════════════════════════════════════════════════════════════

    /**
     * GET /sheets — List all sheets (metadata only)
     */
    public function get_items($request): \WP_REST_Response|\WP_Error
    {
        $model  = new Model();
        $sheets = $model->load_all();

        $data = array_map(array($this, 'prepare_sheet_summary'), $sheets);

        $response = $this->prepare_response($data);
        $response->header('X-WP-Total', (string) count($data));

        return $response;
    }

    /**
     * GET /sheets/{id} — Get single sheet with full data
     */
    public function get_item($request): \WP_REST_Response|\WP_Error
    {
        $id    = sanitize_text_field($request->get_param('id'));
        $model = new Model();
        $table = $model->load($id);

        if (! $table) {
            return $this->prepare_error('sheet_not_found', __('Sheet não encontrada.', 'apollo-sheets'), 404);
        }

        return $this->prepare_response($this->prepare_sheet_full($table));
    }

    /**
     * POST /sheets — Create new sheet
     */
    public function create_item($request): \WP_REST_Response|\WP_Error
    {
        $model = new Model();
        $table = Model::get_default_table();

        if ($request->get_param('name')) {
            $table['name'] = sanitize_text_field($request->get_param('name'));
        }

        if ($request->get_param('description')) {
            $table['description'] = sanitize_text_field($request->get_param('description'));
        }

        if ($request->get_param('data')) {
            $data = $request->get_param('data');
            if (is_array($data)) {
                $table['data'] = $this->sanitize_data($data);
            }
        }

        if ($request->get_param('options')) {
            $options = $request->get_param('options');
            if (is_array($options)) {
                $table['options'] = array_merge($table['options'], $options);
            }
        }

        $new_id = $model->add($table);

        if (false === $new_id) {
            return $this->prepare_error('sheet_create_failed', __('Erro ao criar sheet.', 'apollo-sheets'), 500);
        }

        $created = $model->load($new_id);

        do_action('apollo/sheets/rest_created', $new_id, $created);

        return $this->prepare_response($this->prepare_sheet_full($created), 201);
    }

    /**
     * PUT/PATCH /sheets/{id} — Update sheet
     */
    public function update_item($request): \WP_REST_Response|\WP_Error
    {
        $id    = sanitize_text_field($request->get_param('id'));
        $model = new Model();
        $table = $model->load($id);

        if (! $table) {
            return $this->prepare_error('sheet_not_found', __('Sheet não encontrada.', 'apollo-sheets'), 404);
        }

        if ($request->has_param('name')) {
            $table['name'] = sanitize_text_field($request->get_param('name'));
        }

        if ($request->has_param('description')) {
            $table['description'] = sanitize_text_field($request->get_param('description'));
        }

        if ($request->has_param('data')) {
            $data = $request->get_param('data');
            if (is_array($data)) {
                $table['data'] = $this->sanitize_data($data);
            }
        }

        if ($request->has_param('options')) {
            $options = $request->get_param('options');
            if (is_array($options)) {
                $table['options'] = array_merge($table['options'], $options);
            }
        }

        if ($request->has_param('visibility')) {
            $vis = $request->get_param('visibility');
            if (is_array($vis)) {
                $table['visibility'] = $vis;
            }
        }

        $saved = $model->save($table);

        if (! $saved) {
            return $this->prepare_error('sheet_save_failed', __('Erro ao salvar sheet.', 'apollo-sheets'), 500);
        }

        $updated = $model->load($id);

        do_action('apollo/sheets/rest_updated', $id, $updated);

        return $this->prepare_response($this->prepare_sheet_full($updated));
    }

    /**
     * DELETE /sheets/{id}
     */
    public function delete_item($request): \WP_REST_Response|\WP_Error
    {
        $id    = sanitize_text_field($request->get_param('id'));
        $model = new Model();

        $table = $model->load($id, false, false);
        if (! $table) {
            return $this->prepare_error('sheet_not_found', __('Sheet não encontrada.', 'apollo-sheets'), 404);
        }

        $deleted = $model->delete($id);

        if (! $deleted) {
            return $this->prepare_error('sheet_delete_failed', __('Erro ao excluir sheet.', 'apollo-sheets'), 500);
        }

        do_action('apollo/sheets/rest_deleted', $id);

        return $this->prepare_response(
            array(
                'deleted' => true,
                'id'      => $id,
            )
        );
    }

    /**
     * POST /sheets/{id}/copy
     */
    public function copy_item(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $id    = sanitize_text_field($request->get_param('id'));
        $model = new Model();

        $new_id = $model->copy($id);

        if (false === $new_id) {
            return $this->prepare_error('sheet_copy_failed', __('Erro ao copiar sheet.', 'apollo-sheets'), 500);
        }

        $copy = $model->load($new_id);

        return $this->prepare_response($this->prepare_sheet_full($copy), 201);
    }

    /**
     * POST /sheets/import
     */
    public function import_item(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $import = new Import();
        $model  = new Model();
        $format = sanitize_text_field($request->get_param('format') ?? '');
        $name   = sanitize_text_field($request->get_param('name') ?? __('Importada', 'apollo-sheets'));
        $source = sanitize_text_field($request->get_param('source') ?? 'data');

        $data = null;

        if ('url' === $source) {
            $url  = esc_url_raw($request->get_param('url') ?? '');
            $data = $import->import_from_url($url, $format);
        } else {
            // Raw data in body
            $raw = $request->get_param('data');
            if (is_string($raw)) {
                $data = $import->import($raw, $format);
            } elseif (is_array($raw)) {
                // Already parsed 2D array
                $data = $raw;
            }
        }

        if (! $data || empty($data)) {
            return $this->prepare_error('import_failed', __('Falha ao importar dados.', 'apollo-sheets'), 400);
        }

        // Create sheet from imported data
        $table         = Model::get_default_table();
        $table['name'] = $name;
        $table['data'] = $this->sanitize_data($data);

        // Adjust visibility arrays to match data dimensions
        $rows                = count($table['data']);
        $cols                = ! empty($table['data'][0]) ? count($table['data'][0]) : 0;
        $table['visibility'] = array(
            'rows'    => array_fill(0, $rows, 1),
            'columns' => array_fill(0, $cols, 1),
        );

        $new_id = $model->add($table);

        if (false === $new_id) {
            return $this->prepare_error('import_save_failed', __('Erro ao salvar sheet importada.', 'apollo-sheets'), 500);
        }

        $created = $model->load($new_id);

        do_action('apollo/sheets/rest_imported', $new_id, $created);

        return $this->prepare_response($this->prepare_sheet_full($created), 201);
    }

    /**
     * GET /sheets/{id}/export
     */
    public function export_item(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $id     = sanitize_text_field($request->get_param('id'));
        $format = sanitize_text_field($request->get_param('format') ?? 'json');

        $export  = new Export();
        $content = $export->export($id, $format);

        if (false === $content) {
            return $this->prepare_error('export_failed', __('Erro ao exportar sheet.', 'apollo-sheets'), 500);
        }

        $model = new Model();
        $table = $model->load($id, false, false);
        $name  = sanitize_title($table['name'] ?? 'sheet-' . $id);

        return $this->prepare_response(
            array(
                'filename' => $name . '.' . $format,
                'format'   => $format,
                'content'  => $content,
            )
        );
    }

    /**
     * GET /sheets/{id}/preview — Render HTML preview
     */
    public function preview_item(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $id     = sanitize_text_field($request->get_param('id'));
        $render = new \Apollo\Sheets\Render();
        $html   = $render->shortcode_output(array('id' => $id));

        return $this->prepare_response(
            array(
                'id'   => $id,
                'html' => $html,
            )
        );
    }

	// ═══════════════════════════════════════════════════════════════
	// HELPERS
	// ═══════════════════════════════════════════════════════════════

    /**
     * Prepare sheet summary (for listing — no data)
     */
    private function prepare_sheet_summary(array $table): array
    {
        return array(
            'id'            => $table['id'],
            'name'          => $table['name'],
            'description'   => $table['description'] ?? '',
            'author'        => (int) ($table['author'] ?? 0),
            'author_name'   => get_the_author_meta('display_name', (int) ($table['author'] ?? 0)),
            'last_modified' => $table['last_modified'] ?? '',
        );
    }

    /**
     * Prepare full sheet data (for single view)
     */
    private function prepare_sheet_full(array $table): array
    {
        $summary = $this->prepare_sheet_summary($table);

        $summary['data']       = $table['data'] ?? array();
        $summary['options']    = $table['options'] ?? array();
        $summary['visibility'] = $table['visibility'] ?? array();
        $summary['rows']       = count($table['data'] ?? array());
        $summary['columns']    = ! empty($table['data'][0]) ? count($table['data'][0]) : 0;

        return $summary;
    }

    /**
     * Sanitize a 2D data array
     */
    private function sanitize_data(array $data): array
    {
        return array_map(
            function ($row) {
                if (! is_array($row)) {
                    return array((string) $row);
                }
                return array_map(
                    function ($cell) {
                        return wp_kses_post((string) $cell);
                    },
                    $row
                );
            },
            $data
        );
    }

	// ═══════════════════════════════════════════════════════════════
	// PARAMETER SCHEMAS
	// ═══════════════════════════════════════════════════════════════

    /**
     * Collection params (GET /sheets)
     */
    public function get_collection_params(): array
    {
        return array(
            'page'     => array(
                'description'       => __('Página.', 'apollo-sheets'),
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => __('Resultados por página.', 'apollo-sheets'),
                'type'              => 'integer',
                'default'           => 20,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
            ),
        );
    }

    /**
     * Create params (POST /sheets)
     */
    private function get_create_params(): array
    {
        return array(
            'name'        => array(
                'description'       => __('Nome da sheet.', 'apollo-sheets'),
                'type'              => 'string',
                'default'           => __('Nova Sheet', 'apollo-sheets'),
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'description' => array(
                'description'       => __('Descrição.', 'apollo-sheets'),
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'data'        => array(
                'description' => __('Dados da tabela (2D array).', 'apollo-sheets'),
                'type'        => 'array',
            ),
            'options'     => array(
                'description' => __('Opções da tabela.', 'apollo-sheets'),
                'type'        => 'object',
            ),
        );
    }

    /**
     * Update params (PUT/PATCH /sheets/{id})
     */
    private function get_update_params(): array
    {
        return array_merge(
            array(
                'id' => array(
                    'description'       => __('Sheet ID.', 'apollo-sheets'),
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
            $this->get_create_params(),
            array(
                'visibility' => array(
                    'description' => __('Visibilidade de linhas/colunas.', 'apollo-sheets'),
                    'type'        => 'object',
                ),
            )
        );
    }

    /**
     * Import params (POST /sheets/import)
     */
    private function get_import_params(): array
    {
        return array(
            'source' => array(
                'description'       => __('Fonte dos dados: data|url', 'apollo-sheets'),
                'type'              => 'string',
                'enum'              => array('data', 'url'),
                'default'           => 'data',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'data'   => array(
                'description' => __('Dados brutos (CSV/JSON/HTML string ou 2D array).', 'apollo-sheets'),
            ),
            'url'    => array(
                'description'       => __('URL para importar (Google Sheets, CSV, etc).', 'apollo-sheets'),
                'type'              => 'string',
                'format'            => 'uri',
                'sanitize_callback' => 'esc_url_raw',
            ),
            'format' => array(
                'description'       => __('Formato: csv|json|html (vazio = auto-detect).', 'apollo-sheets'),
                'type'              => 'string',
                'enum'              => array('', 'csv', 'json', 'html'),
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'name'   => array(
                'description'       => __('Nome da sheet importada.', 'apollo-sheets'),
                'type'              => 'string',
                'default'           => __('Importada', 'apollo-sheets'),
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }
}
