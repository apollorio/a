<?php

/**
 * Shared deferred scripts for blank-canvas auth pages (/acesso, /registre).
 *
 * GSAP, Lenis, RemixIcon, and icon.js are owned by core.js — do NOT duplicate here.
 *
 * Expects $js_config in scope (from login.php / register.php).
 *
 * @package Apollo\Login
 */

if (! defined('ABSPATH')) {
    exit;
}

$auth_script_urls = array();

$is_login_page = ! empty($js_config['authPage']) && 'login' === $js_config['authPage'];

$apollo_chat_bootstrap = WP_PLUGIN_DIR . '/apollo-chat/apollo-chat.php';
if (! $is_login_page && is_readable($apollo_chat_bootstrap)) {
    $auth_script_urls[] = plugins_url('assets/js/apollo-gsap-text-fx.js', $apollo_chat_bootstrap) . '?v=2.0.0';
}

$auth_script_urls[] = APOLLO_LOGIN_URL . 'assets/js/apollo-auth-genres.js?v=' . APOLLO_LOGIN_VERSION;
if (! empty($js_config['authPage']) && 'register' === $js_config['authPage']) {
    $auth_script_urls[] = APOLLO_LOGIN_URL . 'assets/js/apollo-auth-phone.js?v=' . APOLLO_LOGIN_VERSION;
}
$auth_script_urls[] = APOLLO_LOGIN_URL . 'assets/js/apollo-progress.js?v=' . APOLLO_LOGIN_VERSION;
$auth_script_urls[] = APOLLO_LOGIN_URL . 'assets/js/apollo-auth-scripts.js?v=' . APOLLO_LOGIN_VERSION;
$auth_script_urls[] = APOLLO_LOGIN_URL . 'assets/js/apollo-auth-scroll.js?v=' . APOLLO_LOGIN_VERSION;
?>
<script>
    window.apolloAuthConfig = <?php echo wp_json_encode($js_config); ?>;
</script>
<script>
    (function() {
        'use strict';

        var queue = <?php echo wp_json_encode(array_values($auth_script_urls)); ?>;

        function appendScript(src, done) {
            var s = document.createElement('script');
            s.src = src;
            s.crossOrigin = 'anonymous';
            s.onload = function() {
                if (typeof done === 'function') {
                    done();
                }
            };
            s.onerror = function() {
                console.warn('[Apollo Auth] failed to load', src);
                if (typeof done === 'function') {
                    done();
                }
            };
            document.body.appendChild(s);
        }

        function loadQueue() {
            var i = 0;
            (function next() {
                if (i >= queue.length) {
                    return;
                }
                appendScript(queue[i++], next);
            })();
        }

        function bootAuthScripts() {
            if (window.__APOLLO_AUTH_SCRIPTS_LOADED__) {
                return;
            }
            window.__APOLLO_AUTH_SCRIPTS_LOADED__ = true;
            loadQueue();
        }

        if (window.Apollo && (window.Apollo.loaded || window.Apollo.version)) {
            bootAuthScripts();
        } else {
            document.addEventListener('apollo:ready', bootAuthScripts, {
                once: true
            });
            document.addEventListener('apollo:gsap-ready', bootAuthScripts, {
                once: true
            });
            window.setTimeout(bootAuthScripts, 6000);
        }

        function playAuthBgVideo() {
            var video = document.getElementById('acessoBgVid');
            if (!video) {
                return;
            }
            video.muted = true;
            video.setAttribute('playsinline', '');
            // Slow background video to 85% speed (0.85). Re-applied on each call
            // (visibilitychange, apollo:ready) since the browser can reset rate on reload.
            video.playbackRate = 0.5;
            var attempt = video.play();
            if (attempt && typeof attempt.catch === 'function') {
                attempt.catch(function() {});
            }
            if (window.Apollo && typeof window.Apollo.forcePlay === 'function') {
                window.Apollo.forcePlay();
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', playAuthBgVideo, {
                once: true
            });
        } else {
            playAuthBgVideo();
        }
        window.addEventListener('apollo:ready', playAuthBgVideo, {
            once: true
        });
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                playAuthBgVideo();
            }
        });
    })();
</script>