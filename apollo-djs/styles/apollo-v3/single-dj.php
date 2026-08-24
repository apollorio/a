<?php
/**
 * Single DJ — Style Entry Point (apollo-v3)
 *
 * Bridge file: loaded by TemplateLoader when APOLLO_DJ_DEFAULT_STYLE = 'apollo-v3'.
 * Delegates rendering to the v3 master template at templates/single-dj-v3.php.
 *
 * Fallback chain (TemplateLoader::locate):
 *   1. child-theme/apollo-djs/apollo-v3/single-dj.php
 *   2. parent-theme/apollo-djs/apollo-v3/single-dj.php
 *   3. plugin/styles/apollo-v3/single-dj.php  ← THIS FILE
 *   4. plugin/styles/base/single-dj.php
 *
 * @package Apollo\DJs
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

$v3_template = APOLLO_DJ_DIR . 'templates/single-dj-v3.php';

if ( file_exists( $v3_template ) ) {
    include $v3_template;
} else {
    // Fallback: let TemplateLoader chain continue to styles/base/
    status_header( 500 );
    wp_die(
        esc_html__( 'DJ v3 template not found. Verify plugin integrity.', 'apollo-djs' ),
        esc_html__( 'Template Error', 'apollo-djs' ),
        array( 'response' => 500 )
    );
}
