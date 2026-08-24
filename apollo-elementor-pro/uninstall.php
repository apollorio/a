<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }
// Only delete data if admin opted in (mirrors apollo-core pattern).
if ( get_option( 'apollo_delete_data_on_uninstall' ) ) {
    delete_option( 'apollo_elementor_settings' );
}
