<?php

/**
 * Plugin Name: Apollo Chat
 * Plugin URI: https://apollo.rio.br
 * Description: Premium instant messaging, text-only — real-time polling, typing indicators, read receipts, emoji reactions, message editing, reply-to threading, search, groups, user presence, notifications, mute/unmute, pinned messages, message forwarding, user info panels. No file, image, audio, video, or GIF attachments — see src/Plugin.php class docblock.
 * Version: 2.2.8
 * Author: Apollo::Rio
 * Author URI: https://apollo.rio.br
 * Text Domain: apollo-chat
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 *
 * @package Apollo\Chat
 */

/*
 * ARCH: apollo-chat / mensagens diretas (sem CPT)
 * ARCH-MANUAL: escrito a mao (2026-09-09). gen-arch-blocks.js recusa
 *   ficheiros dirty no git e 41 de 42 estao dirty. Ver nota em apollo-core.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-chat   9 arquivos PHP, 4618 LOC
 * BOOT      plugins_loaded:15 (:59)
 * RUNTIME   nao regista CPT; usa tabelas proprias
 * UI        nao emite HTML no ficheiro de entrada
 * REST      30 rotas, NENHUMA leitura publica - tudo autenticado
 * REQUIRES  apollo-core
 *
 * ROTAS     /mensagens - /mensagens/{id} - /mensagens/@{handle}
 *           As regras sao registadas por add_rewrite_rule e o flush vive em
 *           wp_loaded, NAO em init:1 - um flush em init:1 apagaria todas as
 *           regras que os outros plugins registam a partir de init:10.
 *
 * NAO FACA
 *   - confiar em reply_to_id sem verificar que a mensagem referida pertence
 *     ao mesmo thread. Defeito aberto e conhecido: hoje permite leitura
 *     cruzada entre threads.
 *   - deixar GET /chat/threads/{id} escrever estado. Um GET que muta e uma
 *     surpresa para qualquer cache e para qualquer crawler.
 *   - assumir que a guarda de seguranca cobre todas as rotas que criam
 *     thread: cobre 1 de 3.
 *
 * VERIFICAR   node D:/dev/_cos/verify/chat-authz-audit.js
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('APOLLO_CHAT_VERSION', '2.2.8'); /* 2.2.8 — bust CSS cache (premium + shell) */
// 2026-09-12: chat-premium.css / chat-shell.css / chat.js all changed in the
// same pass and every one of them is cache-busted by THIS constant (?v=).
// Shipping the CSS without bumping it serves the old stylesheet against the
// new markup — the peer chip would render unstyled for anyone with a warm
// cache. Gate: node apollo-chat/_sandbox/verify-luxe-surfaces.mjs
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
