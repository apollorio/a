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

// Note: assets/css/chat.css is a superseded first-draft stylesheet (.app-shell/
// .sidebar class names, pre-dating the current .apollo-chat-wrap/.ac-* shell) —
// it was never linked into this page (the $chat_css var that used to build its
// URL was dead code) and chat-premium.css + chat-shell.css are the real styles.
$chat_js = esc_url(APOLLO_CHAT_URL . 'assets/js/chat.js?v=' . APOLLO_CHAT_VERSION);

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


    <style id="apollo-chat-critical">
        :root {
            --bg: #fff;
            --white-3: #f5f5f7;
        }

        html.apollo-chat-page,
        html {
            height: 100dvh !important;
            max-height: 100dvh !important;
            overflow: hidden !important;
            box-sizing: border-box !important;
            background: var(--ac-ground, #f0f0f3) !important;
        }

        body.apollo-chat-page,
        body {
            height: 100% !important;
            max-height: 100% !important;
            overflow: hidden !important;
            overscroll-behavior: none !important;
            background: var(--ac-ground, #f0f0f3) !important;
            margin: 0 !important;
        }

        /* Starfields removed from DOM — keep kill-switch if CDN injects leftovers */
        .ac-starfield {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }

        /* Lenis + Apollo custom scrollbars are hostile to viewport-locked chat */
        html.apollo-chat-page.lenis,
        html.apollo-chat-page {
            overflow: hidden !important;
            height: 100dvh !important;
            max-height: 100dvh !important;
        }
        #apollo-sb-y,
        #apollo-sb-x,
        .apollo-sb-y,
        .apollo-sb-x {
            display: none !important;
            opacity: 0 !important;
            pointer-events: none !important;
            visibility: hidden !important;
        }

        .apollo-chat-wrap,
        .ac-layout {
            height: 100% !important;
            max-height: 100% !important;
            overflow: hidden !important;
            background: var(--ac-ground, #f0f0f3) !important;
        }

        .ac-sidebar,
        .ac-main {
            min-height: 0 !important;
            overflow: hidden !important;
        }

        .ac-thread-list,
        .ac-messages {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            overscroll-behavior: contain !important;
            min-height: 0 !important;
            -webkit-overflow-scrolling: touch;
        }

        /* 2026-09-12 — this block is LAST in the cascade (the two <link>s are
           emitted just above it), so an !important here silently beats
           chat-shell.css at equal specificity. It used to hard-code
           `background:#fff` with the note "pane stays pure white so tinted
           pills/bubbles/composer contrast" — but the pills, the composer and
           the received bubbles were all white too, so the only thing dividing
           them from the pane was a 1px hairline. The pane is now the recessed
           GROUND and that chrome is elevated paper above it.

           So the rule is gone from here entirely rather than restated with a
           token: chat-shell.css `.ac-messages { background: var(--ac-ground) }`
           is now the only declaration of it, and html/body above still paint
           the same ground, so nothing flashes white before the sheet lands.
           Gutters owned by chat-shell.css tokens — do not force padding here.
           Verified by _sandbox/verify-luxe-surfaces.mjs (assertions B and C),
           which fails the build if this rule comes back. */

        /* Same cascade caveat as .ac-messages above: token, not a literal,
           so chat-shell.css stays the single owner of "paper". */
        .ac-sidebar-header {
            background: var(--ac-raise, #fff) !important;
            color: rgba(var(--txt-rgb, 29, 29, 31), 0.92) !important;
        }

        .nav-btn svg,
        .apollo-navbar,
        .clock-pill,
        .nav-btn,
        .nav-btn i {
            color: rgba(29, 29, 31, 0.88) !important;
            fill: rgba(29, 29, 31, 0.88) !important;
            z-index: 999999 !important;
        }

        @media (min-width: 768px) {
            .ac-layout {
                flex-direction: row !important;
                height: 100% !important;
                max-height: 100% !important;
            }
        }
    </style>
    <script>
    (function () {
      document.documentElement.classList.add('apollo-chat-page', 'ap-lenis-gated');
      window.__APOLLO_CHAT_NO_LENIS__ = true;

      /* Lenis smooth-scroll + Apollo's custom scrollbars fight a viewport-
         locked, section-scroll-only chat app — this forcibly evicts both if
         the shared runtime mounts them on this page. Kept (this is real
         defensive logic, not debug instrumentation). */
      function killLenis() {
        var L = window.lenis;
        if (L) {
          try { if (typeof L.stop === 'function') L.stop(); } catch (e1) {}
          try { if (typeof L.destroy === 'function') L.destroy(); } catch (e2) {}
          window.lenis = null;
        }
        document.documentElement.classList.remove('lenis');
        document.documentElement.classList.add('apollo-chat-page', 'ap-lenis-gated');
        ['apollo-sb-y', 'apollo-sb-x'].forEach(function (id) {
          var el = document.getElementById(id);
          if (el && el.parentNode) el.parentNode.removeChild(el);
        });
      }

      /* 2026-08-29: removed inventory() — it POSTed a full DOM/class audit to
         http://127.0.0.1:7754/ingest/… (a one-off debugging session's local
         listener) on every real visitor's page load. From apollo.rio.br that
         request can never succeed and just failed silently on every load;
         same class of leftover beacon already stripped from apollo-events
         (see apollo-events-create-bridge.js / create-form.js history). */
      killLenis();
      window.addEventListener('apollo:ready', killLenis, { once: true });
      window.addEventListener('apollo:lenis-ready', killLenis, { once: true });
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', killLenis);
      }
      var hits = 0;
      var mo = new MutationObserver(function () {
        if (document.documentElement.classList.contains('lenis') || window.lenis) {
          killLenis();
          hits += 1;
          if (hits >= 4) mo.disconnect();
        }
      });
      mo.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    })();
    </script>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => 'Bate-Papo::rio · Apollo',
            'extra_head' => $extra_head,
            'skip_seo'   => true,
            'html_class' => 'apollo-chat-page',
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="apollo-chat-page">
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>

<body class="apollo-chat-page">

    <!-- ═══ App Shell ═══ -->
    <!-- 2026-08-29: the old .pp-xpace strip above .apollo-chat-wrap is gone —
         it was an empty 55px bar (no nav content ever rendered into it, see
         chat-shell.css/chat-premium.css history notes). data-a-user /
         data-a-component are a page-view tracking tag used ecosystem-wide
         (not a nav hook — grep the other apollo-* plugins), so they move to
         the wrap instead of disappearing. -->
    <div class="apollo-chat-wrap" data-a-user="<?php echo esc_attr((string) $user_id); ?>" data-a-component="chat">
        <div class="ac-layout">

            <!-- ═══════════════════════════════════════════════════
			SIDEBAR — Thread List
			═══════════════════════════════════════════════════ -->
            <div class="ac-sidebar">

                <!-- Header -->
                <div class="ac-sidebar-header">
                    <div class="ac-header-top">
                        <h2 class="ac-header-title">Bate-Papo<span class="dim" style="opacity:.5">::rio</span></h2>
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

                <!-- Peer chip — compact floating identity (no dead header band) -->
                <div class="ac-chat-header" hidden aria-hidden="true">
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

                <!-- Composer dock — hidden until thread opened -->
                <div class="ac-compose" hidden aria-hidden="true">

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
                            <textarea class="apollo-textarea ac-compose-input" placeholder="Escreva uma mensagem…" rows="1"
                                autocomplete="off" enterkeyhint="send"></textarea>
                            <span class="ac-emoji-trigger" title="Emoji" aria-label="Emoji">
                                <i class="ri-emotion-happy-line"></i>
                            </span>
                            <div class="ac-emoji-picker">
                                <div class="ac-emoji-picker-search">
                                    <input type="text" placeholder="Buscar emoji..." autocomplete="off" class="apollo-input">
                                </div>
                                <div class="ac-emoji-grid"></div>
                            </div>
                        </div>
                        <button class="ac-send-btn" title="Enviar" type="button" aria-label="Enviar mensagem">
                            <i class="ri-send-plane-2-fill" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <!-- Search Overlay -->
                <div class="ac-search-overlay" aria-hidden="true">
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

    <!-- ═══ New Chat — bottom sheet ═══ -->
    <div class="ac-sheet-overlay" id="ac-new-chat-sheet" aria-hidden="true">
        <div class="ac-sheet ac-glass-panel" role="dialog" aria-modal="true" aria-labelledby="ac-nt-title">
            <div class="ac-sheet-handle" aria-hidden="true"></div>
            <div class="ac-sheet-header">
                <h3 id="ac-nt-title">Nova conversa</h3>
                <button class="ac-sheet-close" type="button" aria-label="Fechar"><i class="ri-close-line"></i></button>
            </div>
            <div class="ac-sheet-body">
                <input type="text" id="ac-nt-search" class="apollo-input ac-sheet-search" placeholder="Buscar usuário..."
                    autocomplete="off">
                <div id="ac-nt-results" class="ac-user-tags" role="list"></div>
                <p class="ac-sheet-hint">Toque em um contato para abrir o chat</p>
            </div>
        </div>
    </div>

    <!-- ═══ Generic modal (pinned / members / forward) ═══ -->
    <div class="ac-modal-overlay" id="ac-generic-modal">
        <div class="ac-modal ac-glass-panel">
            <div class="ac-modal-header">
                <h3></h3>
                <button class="ac-modal-close" type="button" aria-label="Fechar"><i class="ri-close-line"></i></button>
            </div>
            <div class="ac-modal-body"></div>
            <div class="ac-modal-footer"></div>
        </div>
    </div>

    <!-- ═══ Image Lightbox ═══ -->
    <div class="ac-lightbox"><img src="" alt="Preview"></div>

    <!-- ═══ Toast Container ═══ -->
    <div class="ac-toast-container"></div>

    <!-- ═══ Chat Engine ═══ -->
    <?php
    // wp_footer(); // Removed for blank canvas
    $tfx_js    = esc_url( APOLLO_CHAT_URL . 'assets/js/apollo-gsap-text-fx.js?v=' . APOLLO_CHAT_VERSION );
    $motion_js = esc_url( APOLLO_CHAT_URL . 'assets/js/apollo-chat-motion.js?v=' . APOLLO_CHAT_VERSION );
    ?>
    <!-- Apollo GSAP Text FX — typing, loading text, counters -->
    <script src="<?php echo $tfx_js; ?>" defer></script>
    <!-- Premium GSAP motion — overlay, menus, send-bubble flight -->
    <script src="<?php echo $motion_js; ?>" defer></script>
    <!-- Chat application engine -->
    <script src="<?php echo $chat_js; ?>" defer></script>
    <?php require $tpl_parts . 'scripts.php'; ?>

</body>

</html>