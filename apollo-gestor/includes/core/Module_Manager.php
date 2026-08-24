<?php
/**
 * Module Manager — loads and orchestrates all Gestor modules
 *
 * Each module lives in includes/modules/{slug}/ with backend/ and frontend/ subdirs.
 * Backend controllers handle AJAX/REST. Frontend files are template partials.
 *
 * @package Apollo\Gestor
 */

declare(strict_types=1);

namespace Apollo\Gestor\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Module_Manager {

    /** @var array<string, Base_Module> */
    private array $modules = [];

    /** @var string[] Module slugs in load order */
    private const REGISTERED = [
        'projetos',
        'tarefas',
        'proj-finance',
        'proj-team',
        'proj-staff',
        'proj-gantt',
        'proj-board',
        'proj-equip',
        'editor',
        'pdf',
    ];

    /**
     * Boot all registered modules
     */
    public function init(): void {
        foreach ( self::REGISTERED as $slug ) {
            $this->load_module( $slug );
        }

        do_action( 'apollo/gestor/modules_loaded', $this->modules );
    }

    /**
     * Load a single module by slug
     */
    private function load_module( string $slug ): void {
        $class = $this->slug_to_class( $slug );
        $file  = APOLLO_GESTOR_DIR . "includes/modules/{$slug}/backend/{$class}.php";

        if ( ! file_exists( $file ) ) {
            return;
        }

        require_once $file;

        $fqcn = "Apollo\\Gestor\\Modules\\{$class}";

        if ( ! class_exists( $fqcn ) ) {
            return;
        }

        /** @var Base_Module $instance */
        $instance = new $fqcn();
        $instance->register();

        $this->modules[ $slug ] = $instance;
    }

    /**
     * Convert slug to class name: proj-finance → Proj_Finance
     */
    private function slug_to_class( string $slug ): string {
        return str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $slug ) ) );
    }

    /**
     * Get a loaded module instance
     */
    public function get_module( string $slug ): ?Base_Module {
        return $this->modules[ $slug ] ?? null;
    }

    /**
     * Get all loaded modules
     *
     * @return array<string, Base_Module>
     */
    public function get_all(): array {
        return $this->modules;
    }

    /**
     * Check if a module is loaded
     */
    public function has( string $slug ): bool {
        return isset( $this->modules[ $slug ] );
    }

    /**
     * Get the frontend partial path for a module panel
     */
    public function get_partial( string $slug, string $partial ): string {
        $path = APOLLO_GESTOR_DIR . "includes/modules/{$slug}/frontend/{$partial}.php";
        return file_exists( $path ) ? $path : '';
    }

    /**
     * Include a frontend partial with optional data
     */
    public function render_partial( string $slug, string $partial, array $data = [] ): void {
        $path = $this->get_partial( $slug, $partial );
        if ( ! $path ) {
            return;
        }

        // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        extract( $data, EXTR_SKIP );
        include $path;
    }
}
