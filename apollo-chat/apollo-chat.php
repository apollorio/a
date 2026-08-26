<?php

/**
 * Plugin Name: Apollo Chat
 * Plugin URI: https://apollo.rio.br
 * Description: Premium instant messaging, text-only — real-time polling, typing indicators, read receipts, emoji reactions, message editing, reply-to threading, search, groups, user presence, notifications, mute/unmute, pinned messages, message forwarding, user info panels. No file, image, audio, video, or GIF attachments — see src/Plugin.php class docblock.
 * Version: 2.0.2
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * Text Domain: apollo-chat
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * @package Apollo\Chat
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('APOLLO_CHAT_VERSION', '2.0.2');
// 2026-08-25: text-only policy. /chat/upload and /chat/gif-search now
// always 403; rest_send_message() ignores any client-supplied 'type' and
// always writes message_type='text'. No file/image/audio/video/GIF/voice
// attachments anywhere in the send path. See src/Plugin.php class docblock.
define('APOLLO_CHAT_PATH', plugin_dir_path(__FILE__));
define('APOLLO_CHAT_URL', plugin_dir_url(__FILE__));
define('APOLLO_CHAT_FILE', __FILE__);

spl_autoload_register(
    function ($className) {
        $prefix = 'Apollo\\Chat\\';
        if (strncmp($className, $prefix, strlen($prefix)) !== 0) {
            return;
        }
        $relative = substr($className, strlen($prefix));
        $file     = APOLLO_CHAT_PATH . 'src/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
);

register_activation_hook(__FILE__, array('Apollo\\Chat\\Activation', 'activate'));
register_deactivation_hook(__FILE__, array('Apollo\\Chat\\Deactivation', 'deactivate'));

add_action(
    'plugins_loaded',
    function () {
        if (! defined('APOLLO_CORE_BOOTSTRAPPED')) {
            return;
        }
        require_once APOLLO_CHAT_PATH . 'includes/functions.php';
        \Apollo\Chat\Plugin::instance();
    },
    15
);
