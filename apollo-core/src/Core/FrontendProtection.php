<?php

/**
 * Apollo Frontend Protection
 *
 * Implements Anti-Print CSS, Anti-Copy protection, and Anti-DevTools guards.
 * Injected via wp_head and wp_footer hooks.
 *
 * @package Apollo\Core
 */

declare(strict_types=1);

namespace Apollo\Core;

if (! defined('ABSPATH')) {
    exit;
}

class FrontendProtection
{
    /**
     * Initialize frontend protection hooks
     */
    public static function init(): void
    {
        add_action('wp_head', array(static::class, 'print_protection_css'), 5);
        add_action('wp_footer', array(static::class, 'print_protection_js'), 99);
    }

    /**
     * Print anti-print + anti-copy CSS in <head>
     */
    public static function print_protection_css(): void
    {
        if (is_admin()) {
            return;
        }

        $nonce = isset($GLOBALS['apollo_csp_nonce']) ? $GLOBALS['apollo_csp_nonce'] : '';
        $nonce_attr = $nonce ? ' nonce="' . esc_attr($nonce) . '"' : '';

        echo '<style id="apollo-protection-css"' . $nonce_attr . '>';
        echo '@media print{';
        echo 'body *:not(#apollo-seo):not(#apollo-seo *){display:none!important;visibility:hidden!important}';
        echo 'body::after{content:"apollo.rio.br — conteúdo protegido";display:block!important;visibility:visible!important;text-align:center;padding:40vh 0;font-size:24px;color:#666;font-family:sans-serif}';
        echo '#apollo-seo{display:none!important}';
        echo '}';
        echo '.apollo-no-copy{-webkit-user-select:none;-ms-user-select:none;user-select:none;-webkit-touch-callout:none}';
        echo '</style>' . "\n";
    }

    /**
     * Print anti-devtools + anti-copy JS in footer
     */
    public static function print_protection_js(): void
    {
        if (is_admin()) {
            return;
        }

        $nonce = isset($GLOBALS['apollo_csp_nonce']) ? $GLOBALS['apollo_csp_nonce'] : '';
        $nonce_attr = $nonce ? ' nonce="' . esc_attr($nonce) . '"' : '';

        echo '<script' . $nonce_attr . '>';
        // Anti-DevTools: one-shot detection on load (no interval overhead)
        echo '(function(){'
            . 'var _t=new Image();Object.defineProperty(_t,"id",{get:function(){'
            . 'document.body.innerHTML="";window.location.reload();'
            . '}});console.log("%c",_t);'
            . '})();';

        // Keyboard shortcut blocking (F12, Ctrl+Shift+I/J/C, Ctrl+U)
        echo 'document.addEventListener("keydown",function(e){';
        echo 'if(e.key==="F12"||(e.ctrlKey&&e.shiftKey&&/[IJC]/.test(e.key))||(e.ctrlKey&&e.key==="u")){e.preventDefault();}';
        echo '});';

        // Context menu disable
        echo 'document.addEventListener("contextmenu",function(e){e.preventDefault();});';

        // Before-print blanking
        echo 'window.addEventListener("beforeprint",function(){';
        echo 'document.querySelectorAll("body>*:not(#apollo-seo)").forEach(function(el){el.dataset._apoVis=el.style.visibility;el.style.visibility="hidden";});';
        echo '});';
        echo 'window.addEventListener("afterprint",function(){';
        echo 'document.querySelectorAll("body>*:not(#apollo-seo)").forEach(function(el){if(el.dataset._apoVis!==undefined){el.style.visibility=el.dataset._apoVis;delete el.dataset._apoVis;}});';
        echo '});';

        echo '</script>' . "\n";
    }
}
