<?php

/**
 * Apollo Admin Page — thin façade that delegates to Admin/ modules.
 *
 * Modules:
 *   Admin\TabManager      — tab ordering
 *   Admin\FieldRenderer   — field rendering by type
 *   Admin\StatusRenderer  — status overview grid
 *   Admin\SaveHandler     — CPanel save + SMTP encryption
 *   Admin\DraftHandler    — CPT draft creation
 *
 * @package Apollo\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin;

use Apollo\Admin\Admin\TabManager;
use Apollo\Admin\Admin\FieldRenderer;
use Apollo\Admin\Admin\StatusRenderer;
use Apollo\Admin\Admin\SaveHandler;
use Apollo\Admin\Admin\DraftHandler;
use Apollo\Admin\Settings\SchemaRegistry;

if (! defined('ABSPATH')) {
    exit;
}

final class AdminPage
{

    private static ?AdminPage $instance = null;

    public static function get_instance(): AdminPage
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'handle_save'));

        add_action('wp_ajax_apollo_cpanel_save', array($this, 'handle_cpanel_save'));
        add_action('admin_post_apollo_cpanel_save', array($this, 'handle_cpanel_save'));

        add_action('wp_ajax_apollo_admin_create_draft', array($this, 'handle_create_draft'));
    }

    /* ─────────────────────────── Menu ──────────────────────────── */

    public function register_menu(): void
    {
        add_menu_page(
            __('Apollo Settings', 'apollo-admin'),
            __('Apollo', 'apollo-admin'),
            'manage_options',
            'apollo',
            array($this, 'render_page'),
            'dashicons-superhero-alt',
            3
        );
    }

    /* ─────────────────────────── Tab Helpers ───────────────────── */

    public function get_tabs(): array
    {
        return TabManager::get_tabs();
    }

    /* ─────────────────────────── Render ────────────────────────── */

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão para acessar esta página.', 'apollo-admin'));
        }

        // Load the CPanel dashboard template (all partials assembled)
        require APOLLO_ADMIN_DIR . 'templates/dashboard.php';
    }

    /**
     * Render the form fields for a specific tab
     */
    private function render_tab_content(string $slug, array $tab): void
    {
        if ($slug === '_status') {
            StatusRenderer::render();
            return;
        }

        $settings = Settings::get_instance();
        $schema   = SchemaRegistry::get($slug);

?>
        <div class="apollo-tab-header">
            <span class="dashicons <?php echo esc_attr($tab['icon']); ?> apollo-tab-icon-large"></span>
            <div>
                <h2><?php echo esc_html($tab['name']); ?></h2>
                <?php if ($slug !== '_global') : ?>
                    <p class="description">
                        <?php
                        $info = Registry::get_instance()->get($slug);
                        echo esc_html($info['description'] ?? '');
                        if (! empty($info['version']) && $info['version'] !== '—') {
                            echo ' — <strong>v' . esc_html($info['version']) . '</strong>';
                        }
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($slug !== '_global' && ! ($tab['installed'] ?? false)) : ?>
            <div class="apollo-notice warning">
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e('Este plugin não está instalado. As configurações serão salvas mas não terão efeito até que o plugin seja instalado e ativado.', 'apollo-admin'); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($schema)) : ?>
            <div class="apollo-notice info">
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e('Nenhuma configuração disponível para este plugin ainda. Os campos de configuração serão adicionados conforme o plugin for desenvolvido.', 'apollo-admin'); ?>
            </div>
            <?php return; ?>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=apollo&tab=' . $slug)); ?>">
            <?php wp_nonce_field('apollo_admin_save_' . $slug, 'apollo_admin_nonce'); ?>
            <input type="hidden" name="apollo_admin_tab" value="<?php echo esc_attr($slug); ?>">

            <table class="form-table apollo-settings-table" role="presentation">
                <tbody>
                    <?php
                    foreach ($schema as $key => $field) :
                        $value    = $settings->get($slug, $key, $field['default']);
                        $field_id = 'apollo_' . $slug . '_' . $key;
                    ?>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($field_id); ?>">
                                    <?php echo esc_html($field['label']); ?>
                                </label>
                            </th>
                            <td>
                                <?php FieldRenderer::render($field_id, $key, $field, $value); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php submit_button(__('Salvar Configurações', 'apollo-admin'), 'primary apollo-save-btn', 'apollo_admin_submit'); ?>
        </form>
    <?php
    }

    /**
     * Render a single form field — delegates to FieldRenderer.
     */
    private function render_field(string $id, string $key, array $field, mixed $value): void
    {
        FieldRenderer::render($id, $key, $field, $value);
    }

    /* ─────────────────────────── CPanel Save Handler ──────────── */

    public function handle_cpanel_save(): void
    {
        SaveHandler::handle();
    }

    /* ─────────────────────────── Draft Creation Handler ──────────── */

    public function handle_create_draft(): void
    {
        DraftHandler::handle();
    }

    /**
     * Render the status overview grid showing all plugins
     */
    private function render_status_overview(): void
    {
        StatusRenderer::render();
    }

    /* ─────────────────────────── Legacy Save Handler ──────────── */

    public function handle_save(): void
    {
        if (! isset($_POST['apollo_admin_submit'])) {
            return;
        }
        if (! isset($_POST['apollo_admin_nonce'])) {
            return;
        }

        $tab = isset($_POST['apollo_admin_tab']) ? sanitize_key($_POST['apollo_admin_tab']) : '';
        if (empty($tab)) {
            return;
        }

        if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['apollo_admin_nonce'])), 'apollo_admin_save_' . $tab)) {
            wp_die(esc_html__('Nonce inválido.', 'apollo-admin'));
        }

        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Sem permissão.', 'apollo-admin'));
        }

        $raw_settings = isset($_POST['apollo_settings']) ? wp_unslash($_POST['apollo_settings']) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        if (! is_array($raw_settings)) {
            $raw_settings = array();
        }

        $schema    = SchemaRegistry::get($tab);
        $sanitized = array();

        foreach ($schema as $key => $field) {
            $raw = $raw_settings[$key] ?? $field['default'];

            switch ($field['type']) {
                case 'toggle':
                    $sanitized[$key] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
                    break;
                case 'number':
                    $sanitized[$key] = (int) $raw;
                    break;
                case 'email':
                    $sanitized[$key] = sanitize_email((string) $raw);
                    break;
                case 'color':
                    $sanitized[$key] = sanitize_hex_color((string) $raw) ?: $field['default'];
                    break;
                case 'textarea':
                    $sanitized[$key] = sanitize_textarea_field((string) $raw);
                    break;
                default:
                    $sanitized[$key] = sanitize_text_field((string) $raw);
            }
        }

        Settings::get_instance()->replace_plugin($tab, $sanitized);

        wp_safe_redirect(admin_url('admin.php?page=apollo&tab=' . $tab . '&settings-updated=1'));
        exit;
    }
}