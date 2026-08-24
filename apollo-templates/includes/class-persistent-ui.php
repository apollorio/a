<?php

declare(strict_types=1);

namespace Apollo\Templates;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Apollo Persistent UI — Universal loader for navbar, FAB, and shared panels.
 *
 * Provides a single entry point for ALL canvas templates to load:
 * - Navbar v2 (top pill — chat/notif/apps/profile)
 * - Menu FAB (bottom-right — 9 apps grid)
 * - Panel Chat (LEFT slide)
 * - Panel Notif (UP slide)
 * - Panel Acesso/Forms (DOWN slide — login, register, report, create-event, etc.)
 * - Panel Detail (RIGHT slide — single event/dj/loc/post/group/classified)
 *
 * Usage in any canvas template:
 *   <?php \Apollo\Templates\PersistentUI::render(); ?>
 *   — or individual methods —
 *   <?php \Apollo\Templates\PersistentUI::navbar(); ?>
 *   <?php \Apollo\Templates\PersistentUI::fab(); ?>
 *   <?php \Apollo\Templates\PersistentUI::panels(); ?>
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/pages-layout.json
 */

class PersistentUI
{

    /**
     * Base path for template parts.
     *
     * @var string
     */
    private static $parts_dir = '';

    /**
     * Get template parts directory.
     *
     * @return string Absolute path to new-home template parts.
     */
    private static function get_parts_dir(): string
    {
        if (empty(self::$parts_dir)) {
            self::$parts_dir = dirname(__DIR__) . '/templates/template-parts/new-home/';
        }
        return self::$parts_dir;
    }

    /**
     * Render ALL persistent UI components.
     *
     * Call this once in any canvas template <body> — OUTSIDE data-panel sections.
     * It loads: navbar + radio (home only) + FAB + shared panels (chat, notif, acesso, detail).
     *
     * @param array $options {
     *     Optional configuration.
     *     @type bool   $radio       Whether to include radio widget. Default false.
     *     @type bool   $panels      Whether to include shared panels. Default true.
     *     @type bool   $fab         Whether to include FAB menu. Default true.
     *     @type string $context     Page context for conditional rendering. Default 'page'.
     *     @type array  $extra_panels Additional panel IDs to include.
     * }
     */
    public static function render(array $options = array()): void
    {
        $defaults = array(
            'radio'        => false,
            'panels'       => true,
            'fab'          => true,
            'context'      => 'page',
            'extra_panels' => array(),
        );
        $opts     = wp_parse_args($options, $defaults);

        // ── Persistent UI (always visible, outside panels) ──
        self::navbar();

        if ($opts['radio']) {
            self::radio();
        }

        if ($opts['fab']) {
            self::fab();
        }

        // ── Shared Panels (inside <body>, siblings of main panel) ──
        if ($opts['panels']) {
            self::panels($opts);
        }
    }

    /**
     * Render Navbar v2.
     *
     * Top pill with: Logo | Apps ▼ | Chat 💬 | Notif 📡 | Profile ▼
     * Panel triggers: chat→LEFT, notif→UP via data-to/data-dir attributes.
     */
    public static function navbar(): void
    {
        if (function_exists('apollo_render_navbar')) {
            apollo_render_navbar();
        }
    }

    /**
     * Render Radio widget (home only).
     */
    public static function radio(): void
    {
        $file = self::get_parts_dir() . 'radio.php';
        if (file_exists($file)) {
            include $file;
        }
    }

    /**
     * Render Menu FAB (bottom-right).
     *
     * 9-app grid for logged users, 5-app + login CTA for guests.
     */
    public static function fab(): void
    {
        $file = self::get_parts_dir() . 'menu-fab.php';
        if (file_exists($file)) {
            include $file;
        }
    }

    /**
     * Render shared slide panels: chat (LEFT), notif (UP), acesso (DOWN), detail (RIGHT).
     *
     * @param array $opts Options from render().
     */
    public static function panels(array $opts = array()): void
    {
        $parts     = self::get_parts_dir();
        $is_logged = is_user_logged_in();

        // ── LEFT: Chat panel (logged only) ──
        if ($is_logged && file_exists($parts . 'panel-chat.php')) {
            include $parts . 'panel-chat.php';
        }

        // ── UP: Notifications panel (logged only) ──
        if ($is_logged && file_exists($parts . 'panel-notif.php')) {
            include $parts . 'panel-notif.php';
        }

        // ── DOWN: Acesso / Forms panel ──
        if (file_exists($parts . 'panel-acesso.php')) {
            include $parts . 'panel-acesso.php';
        }

        // ── RIGHT: Detail panel (single CPT/table row) ──
        if (file_exists($parts . 'panel-detail.php')) {
            include $parts . 'panel-detail.php';
        }

        // ── Extra panels (plugin-specific) ──
        if (! empty($opts['extra_panels'])) {
            foreach ($opts['extra_panels'] as $panel_id) {
                $panel_file = $parts . 'panel-' . sanitize_file_name($panel_id) . '.php';
                if (file_exists($panel_file)) {
                    include $panel_file;
                }
            }
        }
    }

    /**
     * Render the CDN <head> block for canvas templates.
     *
     * Outputs: Apollo CDN core.js + SEO hook.
     * Call inside <head> of canvas templates.
     *
     * @param array $options {
     *     @type string $theme_color  Meta theme-color. Default '#0A0A0A'.
     *     @type string $title        Page <title>. Default site name.
     *     @type string $extra_css    Additional CSS URL to load.
     * }
     */
    public static function head(array $options = array()): void
    {
        $defaults = array(
            'theme_color' => '#0A0A0A',
            'title'       => get_bloginfo('name'),
            'extra_css'   => '',
        );
        $opts = wp_parse_args($options, $defaults);

        $extra_head = '';
        if (! empty($opts['extra_css'])) {
            $extra_head = '<link rel="stylesheet" href="' . esc_url($opts['extra_css']) . '" />';
        }

        if (function_exists('apollo_render_document_head')) {
            apollo_render_document_head(
                array(
                    'title'      => $opts['title'],
                    'extra_head' => $extra_head,
                )
            );
            return;
        }

        $cdn_url = defined('APOLLO_CDN_CORE_JS')
            ? APOLLO_CDN_CORE_JS
            : apollo_cdn_core_js_url();
?>
        <meta charset="<?php bloginfo('charset'); ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
        <meta name="theme-color" content="<?php echo esc_attr($opts['theme_color']); ?>" />
        <title><?php echo esc_html($opts['title']); ?></title>

        <?php do_action('apollo/seo/head'); ?>

        <!-- Apollo CDN — core.js auto-loads: CSS vars, GSAP, jQuery, Icons, page-layout, translate, tracker -->
        <script src="<?php echo esc_url($cdn_url); ?>" fetchpriority="high"></script>

        <?php if (! empty($opts['extra_css'])) : ?>
            <link rel="stylesheet" href="<?php echo esc_url($opts['extra_css']); ?>" />
        <?php endif; ?>

<?php
        do_action('apollo/canvas/head');
    }
}
