<?php
/**
 * Plugin Name: Apollo WhatsApp
 * Plugin URI:  https://apollo.rio.br
 * Description: WAHA bridge — queue, pane, keyword DM. Cell of apollo-core.
 * Version:     0.1.0-pre
 * Author:      Apollo Rio
 * Text Domain: apollo-waha
 * Requires Plugins: apollo-core
 *
 * Boot: plugins_loaded only. Not a mu-plugin. Not force-load.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

define( 'APOLLO_WAHA_FILE', __FILE__ );
define( 'APOLLO_WAHA_DIR', plugin_dir_path( __FILE__ ) );
define( 'APOLLO_WAHA_VER', '0.1.0-pre' );

require_once APOLLO_WAHA_DIR . 'inc/class-plugin.php';

add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'APOLLO_CORE_VERSION' ) && ! class_exists( 'Apollo_Core', false ) ) {
			return;
		}
		Apollo_Waha_Plugin::instance()->boot();
	},
	20
);
