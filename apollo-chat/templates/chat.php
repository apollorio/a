<?php

/**
 * Apollo Chat::Rio — Blank Canvas Template
 *
 * Virtual page for /mensagens, /mensagens/{id}.
 * Blank Canvas: NO get_header(), NO get_footer(), ZERO theme interference.
 * Apollo CDN + chat.css + chat.js loaded directly.
 *
 * @package Apollo\Chat
 * @since   2.2.0
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! is_user_logged_in()) {
    wp_redirect(home_url('/acesso'));
    exit;
}

/* ─── Data Prep ─────────────────────────────────────────────────── */
$user_id      = get_current_user_id();
$current_user = wp_get_current_user();
$thread_id    = (int) get_query_var('apollo_thread_id', 0);
// Use path-only URLs so JS always hits the browser's current origin
// This prevents host mismatch (localhost:port vs .local vs production)
$rest_url  = wp_parse_url(rest_url('apollo/v1/chat'), PHP_URL_PATH);
$ajax_url  = wp_parse_url(admin_url('admin-ajax.php'), PHP_URL_PATH);
$nonce         = wp_create_nonce('wp_rest');
/* apollo/v1/users, NOT wp/v2/users. The production root .htaccess (v3.1.0 §1.9)
 * refuses every request to /wp-json/wp/v2/users that has no
 * `Authorization: Bearer …` header — and this screen authenticates the normal
 * WordPress way, with a cookie plus X-WP-Nonce. The result was a hard 403 on
 * every user search in chat. apollo-groups already calls apollo/v1/users the
 * same way and works; this aligns chat with it. Fixed 2026-08-20, plan-003. */
$users_url = wp_parse_url(rest_url('apollo/v1/users'), PHP_URL_PATH);
$avatar    = '';

if (function_exists('apollo_get_user_avatar_url')) {
    $avatar = apollo_get_user_avatar_url($user_id);
} else {
    $avatar = get_avatar_url($user_id, array('size' => 80));
}

$chat_css = esc_url(APOLLO_CHAT_URL . 'assets/css/chat.css?v=' . APOLLO_CHAT_VERSION);
$chat_js  = esc_url(APOLLO_CHAT_URL . 'assets/js/chat.js?v=' . APOLLO_CHAT_VERSION);

$tpl_parts = __DIR__ . '/template-parts/chat/';

ob_start();
?>
    <script src="https://cdn.apollo.rio.br/v1.0.0/js/gsap-TextPlugin.chat.min.js" defer></script>
    <script>
        window.ApolloChat =
            <?php
            echo wp_json_encode(
                array(
                    'rest_url'    => $rest_url,
                    'ajax_url'    => $ajax_url,
                    'nonce'        => $nonce,
                    'user_id'     => $user_id,
                    'user_name'   => $current_user->display_name,
                    'user_avatar' => $avatar,
                    'thread_id'   => $thread_id,
                    'users_url'   => $users_url,
                )
            );
            ?>;
    </script>
    <?php require $tpl_parts . 'styles.php'; ?>


    <style>
        :root {
            --height-html: 55px;
            --bg: #fff;
        }

        html {
            padding-top: var(--height-html) !important;
        }

        .pp-xpace {
            position: absolute;
            width: 100vw;
            min-width: 100%;
            height: var(--height-html) !important;
            top: 0px;
            left: 0px;
            right: 0px;
        }

        .nav-btn svg,
        .apollo-navbar,
        .clock-pill,
        .nav-btn,
        .nav-btn i {
            color: var(--bg) !important;
            fill: var(--bg) !important;
            z-index: 999999 !important;
        }
    </style>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => 'Chat::Rio · Apollo',
            'extra_head' => $extra_head,
            'skip_seo'   => true,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>

<body>

    <div class="pp-xpace" data-a-user="<?php echo esc_attr((string) $user_id); ?>" data-a-component="chat">
        <div class="ac-starfield" aria-hidden="true">
            <div class="ac-stars-static"></div>
            <div class="ac-stars-moving ac-stars-layer-1"></div>
            <div class="ac-stars-moving ac-stars-layer-2"></div>
            <div class="ac-stars-moving ac-stars-layer-3"></div>
            <div class="ac-stars-flare"></div>
        </div>
    </div>
    <!-- ═══ App Shell ═══ -->
    <div class="apollo-chat-wrap">
        <div class="ac-layout">

            <!-- ═══════════════════════════════════════════════════
			SIDEBAR — Thread List
			═══════════════════════════════════════════════════ -->
            <div class="ac-sidebar">

                <!-- Header -->
                <div class="ac-sidebar-header">
                    <div class="ac-header-top">
                        <h2 class="ac-header-title">Chat<span class="dim">::Rio</span></h2>
                        <button class="ac-icon-btn ac-btn-new" id="ac-new-thread-btn" title="Nova conversa"
                            aria-label="Nova conversa">
                            <span class="navbar-highlighted"><i class="ri-chat-new-line"></i></span>
                        </button>
                    </div>
                    <div class="ac-sidebar-search">
                        <i class="ri-search-line ac-search-icon"></i>
                        <input type="text" placeholder="Buscar conversas..." autocomplete="off" class="apollo-input">
                    </div>
                </div>

                <!-- ═══ Starfield Transition ═══ -->
                <div class="ac-starfield" aria-hidden="true">
                    <div class="ac-stars-static"></div>
                    <div class="ac-stars-moving ac-stars-layer-1"></div>
                    <div class="ac-stars-moving ac-stars-layer-2"></div>
                    <div class="ac-stars-moving ac-stars-layer-3"></div>
                    <div class="ac-stars-flare"></div>
                </div>

                <!-- Thread List -->
                <div class="ac-thread-list">
                    <div class="ac-loading">
                        <p class="ac-loading-txt" aria-live="polite" aria-label="Carregando conversas">Carregando conversas...</p>
                    </div>
                </div>

            </div>

            <!-- ═══════════════════════════════════════════════════
			MAIN — Conversation Area
			═══════════════════════════════════════════════════ -->
            <div class="ac-main">

                <!-- Chat Header (hidden until thread opened) -->
                <div class="ac-chat-header" style="display:none;">
                    <!-- Populated by chat.js -->
                </div>

                <!-- Messages Area -->
                <div class="ac-messages">
                    <div class="ac-empty-chat">
                        <div class="ac-empty-icon">
                            <i class="ri-chat-smile-2-line"></i>
                        </div>
                        <p>Selecione uma conversa</p>
                        <small>Escolha alguém da lista ao lado</small>
                    </div>
                </div>

                <!-- Compose Bar (hidden until thread opened) -->
                <div class="ac-compose" style="display:none;">

                    <!-- Reply-to bar -->
                    <div class="ac-reply-bar">
                        <i class="ri-reply-line"></i>
                        <div class="ac-reply-bar-text">
                            <div class="ac-reply-bar-sender"></div>
                            <div class="ac-reply-bar-preview"></div>
                        </div>
                        <span class="ac-reply-bar-close" title="Cancelar"><i class="ri-close-line"></i></span>
                    </div>

                    <!-- Compose form (text-only — no attachment/GIF button, see src/Plugin.php docblock) -->
                    <div class="ac-compose-form">
                        <div class="ac-compose-center">
                            <textarea class="apollo-textarea ac-compose-input" placeholder="Digite sua mensagem..." rows="1"
                                autocomplete="off"></textarea>
                            <span class="ac-emoji-trigger" title="Emoji">
                                <i class="ri-emotion-happy-line"></i>
                            </span>
                            <div class="ac-emoji-picker">
                                <div class="ac-emoji-picker-search">
                                    <input type="text" placeholder="Buscar emoji..." autocomplete="off" class="apollo-input">
                                </div>
                                <div class="ac-emoji-grid"></div>
                            </div>
                        </div>
                        <button class="ac-send-btn" title="Enviar" type="button">
                            <i class="ri-send-plane-2-fill"></i>
                        </button>
                    </div>
                </div>

                <!-- Search Overlay -->
                <div class="ac-search-overlay">
                    <div class="ac-search-header">
                        <i class="ri-search-line"></i>
                        <input type="text" placeholder="Buscar mensagens..." autocomplete="off" class="apollo-input">
                        <button class="ac-icon-btn ac-search-close" type="button">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                    <div class="ac-search-results"></div>
                </div>

            </div>

        </div>
    </div>

    <!-- ═══ New Thread Modal ═══ -->
    <div class="ac-modal-overlay">
        <div class="ac-modal">
            <div class="ac-modal-header">
                <h3>Nova Conversa</h3>
                <button class="ac-modal-close" type="button"><i class="ri-close-line"></i></button>
            </div>
            <div class="ac-modal-body">
                <div id="ac-nt-tags" class="ac-selected-tags"></div>
                <input type="text" id="ac-nt-search" class="apollo-input ac-input" placeholder="Buscar usuário..."
                    autocomplete="off">
                <div id="ac-nt-results" class="ac-user-list"></div>
                <textarea id="ac-nt-message" class="apollo-textarea ac-input ac-textarea"
                    placeholder="Escreva sua mensagem..."></textarea>
            </div>
            <div class="ac-modal-footer">
                <button class="ac-btn ac-modal-close" type="button"><i class="ri-close-line"></i></button>
                <button class="ac-btn ac-btn-primary" id="ac-nt-send" type="button">
                    <i class="ri-send-plane-2-fill"></i> Enviar
                </button>
            </div>
        </div>
    </div>

    <!-- ═══ Image Lightbox ═══ -->
    <div class="ac-lightbox"><img src="" alt="Preview"></div>

    <!-- ═══ Toast Container ═══ -->
    <div class="ac-toast-container"></div>

    <!-- ═══ Chat Engine ═══ -->
    <?php
    // wp_footer(); // Removed for blank canvas
    $tfx_js = esc_url( APOLLO_CHAT_URL . 'assets/js/apollo-gsap-text-fx.js?v=' . APOLLO_CHAT_VERSION );
    ?>
    <!-- Apollo GSAP Text FX — typing, loading text, counters -->
    <script src="<?php echo $tfx_js; ?>" defer></script>
    <!-- Chat application engine -->
    <script src="<?php echo $chat_js; ?>" defer></script>
    <?php require $tpl_parts . 'scripts.php'; ?>

</body>

</html>