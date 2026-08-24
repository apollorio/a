<?php

/**
 * Apollo Admin — Editor Support (Gutenberg + Elementor)
 *
 * Ensures EVERY Apollo CPT and all pages/posts support:
 * - Gutenberg block editor (show_in_rest + 'editor' support)
 * - Elementor (post type support via elementor_cpt_support filter)
 *
 * 100% modular — loads only when editors are available.
 *
 * @package Apollo\Admin
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Admin;

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

final class EditorSupport {

    private static ?EditorSupport $instance = null;

    public static function get_instance(): EditorSupport {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * All Apollo CPT slugs that should support editors.
     */
    private const APOLLO_CPTS = array(
        'event',
        'dj',
        'local',
        'classified',
        'supplier',
        'doc',
        'email_aprio',
        'hub',
        'apollo_sheet',
    );

    /**
     * Initialize hooks
     */
    public function init(): void {
        // Ensure Gutenberg support on all CPTs (late priority to catch fallbacks)
        add_action( 'init', array( $this, 'ensure_gutenberg_support' ), 99 );

        // Elementor CPT support
        add_filter( 'elementor/utils/get_post_types', array( $this, 'add_elementor_post_types' ) );
        add_action( 'elementor/init', array( $this, 'register_elementor_cpt_support' ) );

        // Force Gutenberg on all registered Apollo CPTs
        add_filter( 'use_block_editor_for_post_type', array( $this, 'force_block_editor' ), 10, 2 );

        // Elementor template locations (so Elementor can override single/archive)
        add_action( 'elementor/theme/register_locations', array( $this, 'register_elementor_locations' ) );
    }

    /**
     * Force Gutenberg block editor for all Apollo CPTs
     */
    public function force_block_editor( bool $use_block_editor, string $post_type ): bool {
        if ( \in_array( $post_type, self::APOLLO_CPTS, true ) ) {
            return true;
        }
        return $use_block_editor;
    }

    /**
     * Ensure all Apollo CPTs have 'editor' and 'custom-fields' support (required for Gutenberg)
     */
    public function ensure_gutenberg_support(): void {
        foreach ( self::APOLLO_CPTS as $cpt ) {
            if ( ! post_type_exists( $cpt ) ) {
                continue;
            }

            // Add 'editor' support if missing
            if ( ! post_type_supports( $cpt, 'editor' ) ) {
                add_post_type_support( $cpt, 'editor' );
            }

            // Add 'custom-fields' support (required for Gutenberg meta in REST)
            if ( ! post_type_supports( $cpt, 'custom-fields' ) ) {
                add_post_type_support( $cpt, 'custom-fields' );
            }
        }
    }

    /**
     * Add Apollo CPTs to Elementor's supported post types list
     */
    public function add_elementor_post_types( array $post_types ): array {
        foreach ( self::APOLLO_CPTS as $cpt ) {
            if ( post_type_exists( $cpt ) && ! isset( $post_types[ $cpt ] ) ) {
                $pt_obj = get_post_type_object( $cpt );
                if ( $pt_obj ) {
                    $post_types[ $cpt ] = $pt_obj->label;
                }
            }
        }
        return $post_types;
    }

    /**
     * Register CPT support via Elementor option (if Elementor is active)
     */
    public function register_elementor_cpt_support(): void {
        $elementor_cpt_support = get_option( 'elementor_cpt_support', array( 'page', 'post' ) );

        if ( ! \is_array( $elementor_cpt_support ) ) {
            $elementor_cpt_support = array( 'page', 'post' );
        }

        $updated = false;
        foreach ( self::APOLLO_CPTS as $cpt ) {
            if ( ! \in_array( $cpt, $elementor_cpt_support, true ) ) {
                $elementor_cpt_support[] = $cpt;
                $updated                = true;
            }
        }

        if ( $updated ) {
            update_option( 'elementor_cpt_support', $elementor_cpt_support );
        }
    }

    /**
     * Register Elementor theme locations for Apollo CPTs
     */
    public function register_elementor_locations( $location_manager ): void {
        foreach ( self::APOLLO_CPTS as $cpt ) {
            $location_manager->register_location(
                'single-' . $cpt,
                array(
                    'label'           => 'Single ' . \ucfirst( $cpt ),
                    'multiple'        => false,
                    'edit_in_content' => true,
                )
            );

            $pt_obj = get_post_type_object( $cpt );
            if ( $pt_obj && $pt_obj->has_archive ) {
                $location_manager->register_location(
                    'archive-' . $cpt,
                    array(
                        'label'           => 'Archive ' . \ucfirst( $cpt ),
                        'multiple'        => false,
                        'edit_in_content' => true,
                    )
                );
            }
        }
    }
}