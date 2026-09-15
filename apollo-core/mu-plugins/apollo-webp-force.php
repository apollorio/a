<?php
/**
 * Plugin Name: Apollo WebP Force (MU)
 * Description: Every image size WordPress generates is written as WebP. Phase 1 — subsizes only; the uploaded original is left untouched.
 * Version: 1.0.0
 * Author: Apollo
 *
 * Auto-installed from apollo-core. Do not edit; change the source under
 * plugins/apollo-core/mu-plugins/apollo-webp-force.php
 *
 * WHAT THIS DOES — AND WHAT IT DELIBERATELY DOES NOT
 * --------------------------------------------------
 * It maps image/jpeg and image/png to image/webp for every size WordPress
 * generates, at quality 80, and prefers Imagick over GD.
 *
 * It does NOT touch the uploaded original. Replacing the original is where the
 * storage actually drops, and it is Phase 2 — because Phase 2 DELETES files,
 * and this folder deploys on save with no staging tier, so it is gated on an
 * uploads + DB backup that nothing here can take on your behalf.
 * APOLLO_WEBP_REPLACE_ORIGINAL is therefore defined below but deliberately
 * unread: Phase 2 adds the only code that reads it.
 *
 * WHY MU AND NOT A NORMAL PLUGIN
 * ------------------------------
 * Nothing about these filters needs to run before plugins_loaded — the format
 * decision happens inside wp_generate_attachment_metadata(), far later. The MU
 * layer is chosen for a different reason: image conversion that silently stops
 * halfway through a library leaves a mixed-format mess, and a plugin someone
 * can deactivate from wp-admin is exactly how that happens. See
 * _inventory/registry/22-mu-plugins.json for the cost of this layer.
 *
 * WHY IT IS SAFE TO SHIP BEFORE THE BACKUP
 * ----------------------------------------
 * Three filters. No writes, no options, no CPT, no meta key, no REST route.
 * WordPress core guards it too: WP_Image_Editor::get_output_format() only
 * honours the mapping when $this->supports_mime_type() agrees, so an editor
 * without WebP quietly keeps JPEG rather than emitting a broken file. We probe
 * anyway, and say so in wp-admin, because a silent no-op is its own bug.
 *
 * MIME SKIP LIST
 * --------------
 * By omission, not by branching. Only three keys are ever added to the format
 * map, so gif / webp / avif / svg / ico / bmp / tiff / pdf are untouched by
 * construction — including the two AVIF and five WebP attachments already in
 * this library, which must never be re-encoded.
 *
 * Requires: Imagick or GD compiled with WebP · WP >= 6.4 · PHP >= 8.1
 *
 * @package Apollo\Core
 * @since   6.6.0
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

if (defined('APOLLO_WEBP_FORCE_LOADED')) {
    return;
}
define('APOLLO_WEBP_FORCE_LOADED', true);

// ═══════════════════════════════════════════════════════════════════════════
// CONSTANTS — override any of these in wp-config.php before WP loads.
// Locked after the Phase 5 calibration on real Apollo fixtures.
// ═══════════════════════════════════════════════════════════════════════════

foreach (
    array(
        'APOLLO_WEBP_Q_PHOTO'           => 80,
        'APOLLO_WEBP_Q_THUMB'           => 72,
        'APOLLO_WEBP_LOSSLESS_GRAPHICS' => true,
        'APOLLO_WEBP_REPLACE_ORIGINAL'  => true,
        'APOLLO_WEBP_REWRITE_CONTENT'   => false,
    ) as $apollo_webp_key => $apollo_webp_value
) {
    if (! defined($apollo_webp_key)) {
        define($apollo_webp_key, $apollo_webp_value);
    }
}
unset($apollo_webp_key, $apollo_webp_value);

// ═══════════════════════════════════════════════════════════════════════════
// CAPABILITY
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_webp_supported')) {
    /**
     * Can any image editor on this host write WebP?
     *
     * Probed lazily and cached per request: wp_image_editor_supports()
     * instantiates editors, so it must never run on the hot path of a request
     * that is not processing an image.
     *
     * @return bool
     */
    function apollo_webp_supported(): bool
    {
        static $supported = null;

        if (null === $supported) {
            $supported = (bool) wp_image_editor_supports(array('mime_type' => 'image/webp'));
        }

        return $supported;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// EXCLUSIONS
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_webp_is_site_icon_request')) {
    /**
     * Is this request WordPress cropping a new site icon?
     *
     * Core routes that through wp_ajax_crop_image() with context=site-icon,
     * and verifies its own nonce before reaching the image editor. We only
     * read the value to decide whether to skip a conversion — no state changes
     * on this path, so there is nothing here for a forged request to gain.
     *
     * @return bool
     */
    function apollo_webp_is_site_icon_request(): bool
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $context = isset($_REQUEST['context']) ? sanitize_key(wp_unslash($_REQUEST['context'])) : '';

        return 'site-icon' === $context;
    }
}

if (! function_exists('apollo_webp_site_icon_basenames')) {
    /**
     * Filenames belonging to the site icon currently in use.
     *
     * The site icon is the one image where a fallback nobody tested is visible
     * on every browser tab, and WP's favicon paths expect PNG. It stays PNG.
     *
     * @return string[]
     */
    function apollo_webp_site_icon_basenames(): array
    {
        static $names = null;

        if (null !== $names) {
            return $names;
        }

        $names   = array();
        $icon_id = (int) get_option('site_icon');

        if ($icon_id > 0) {
            $file = get_attached_file($icon_id);
            if (is_string($file) && '' !== $file) {
                $names[] = wp_basename($file);
            }
        }

        return $names;
    }
}

if (! function_exists('apollo_webp_skip_file')) {
    /**
     * Should this file be left in its original format?
     *
     * @param string $filename Absolute path or basename of the file being written.
     *
     * @return bool
     */
    function apollo_webp_skip_file(string $filename): bool
    {
        if (apollo_webp_is_site_icon_request()) {
            return true;
        }

        if ('' !== $filename && in_array(wp_basename($filename), apollo_webp_site_icon_basenames(), true)) {
            return true;
        }

        /**
         * Filters whether a path is excluded from WebP conversion.
         *
         * Use for lossless masters that must survive untouched, e.g. anything
         * under uploads/apollo-private/.
         *
         * @param bool   $skip     Whether to skip. Default false.
         * @param string $filename Absolute path of the file being written.
         */
        return (bool) apply_filters('apollo/media-webp/skip_path', false, $filename);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FILTERS
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_webp_output_format')) {
    /**
     * Map jpeg/png to webp for every generated size.
     *
     * Adds exactly three keys and never removes one, so any other consumer of
     * this filter keeps its own mapping.
     *
     * @param mixed $formats   Mime map from earlier filters.
     * @param mixed $filename  Path of the file being written.
     * @param mixed $mime_type Source mime type.
     *
     * @return array<string,string>
     */
    function apollo_webp_output_format($formats, $filename = '', $mime_type = ''): array
    {
        $formats = is_array($formats) ? $formats : array();

        if (! apollo_webp_supported()) {
            return $formats;
        }

        // Never re-encode a format that is already modern.
        if (in_array((string) $mime_type, array('image/webp', 'image/avif'), true)) {
            return $formats;
        }

        if (apollo_webp_skip_file((string) $filename)) {
            return $formats;
        }

        $formats['image/jpeg'] = 'image/webp';
        $formats['image/jpg']  = 'image/webp';
        $formats['image/png']  = 'image/webp';

        return $formats;
    }
}
add_filter('image_editor_output_format', 'apollo_webp_output_format', 10, 3);

if (! function_exists('apollo_webp_editor_quality')) {
    /**
     * Quality for WebP output.
     *
     * Core's default is 86, which is fat for photographs; 80 is the number the
     * field has converged on. One value covers every generated size, because
     * wp_editor_set_quality has no size context to distinguish a thumbnail
     * from a hero — APOLLO_WEBP_Q_THUMB is applied in Phase 2, where this
     * plugin does its own encoding and knows the dimensions.
     *
     * @param mixed $quality   Quality 1-100.
     * @param mixed $mime_type Mime type being written.
     *
     * @return int
     */
    function apollo_webp_editor_quality($quality, $mime_type = ''): int
    {
        if ('image/webp' === (string) $mime_type) {
            return (int) APOLLO_WEBP_Q_PHOTO;
        }

        return (int) $quality;
    }
}
add_filter('wp_editor_set_quality', 'apollo_webp_editor_quality', 10, 2);

if (! function_exists('apollo_webp_jpeg_quality')) {
    /**
     * Quality for any JPEG that still gets generated — a skipped file, or a
     * host whose editor cannot write WebP at all.
     *
     * @param mixed $quality Quality 1-100. Unused; the point is the override.
     *
     * @return int
     */
    function apollo_webp_jpeg_quality($quality): int
    {
        unset($quality);

        return 82;
    }
}
add_filter('jpeg_quality', 'apollo_webp_jpeg_quality', 10, 1);

if (! function_exists('apollo_webp_prefer_imagick')) {
    /**
     * Try Imagick before GD.
     *
     * Imagick is what this host actually has — the library already holds AVIF
     * subsizes, which GD cannot produce — and it is the only one of the two
     * that accepts the encoder options Phase 5 needs.
     *
     * @param mixed $editors Ordered editor class names.
     *
     * @return array<int,string>
     */
    function apollo_webp_prefer_imagick($editors): array
    {
        $editors = is_array($editors) ? $editors : array();

        if (! in_array('WP_Image_Editor_Imagick', $editors, true)) {
            return $editors;
        }

        $editors = array_values(array_diff($editors, array('WP_Image_Editor_Imagick')));
        array_unshift($editors, 'WP_Image_Editor_Imagick');

        return $editors;
    }
}
add_filter('wp_image_editors', 'apollo_webp_prefer_imagick', 10, 1);

// ═══════════════════════════════════════════════════════════════════════════
// ADMIN NOTICE — a silent no-op is its own bug.
// ═══════════════════════════════════════════════════════════════════════════

if (! function_exists('apollo_webp_admin_notice')) {
    /**
     * Warn, when WebP turns out to be unavailable, that uploads stay JPEG/PNG.
     *
     * @return void
     */
    function apollo_webp_admin_notice(): void
    {
        if (! current_user_can('upload_files') || apollo_webp_supported()) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>',
            esc_html__('Apollo WebP:', 'apollo-core'),
            esc_html__(
                'nenhum editor de imagem deste servidor grava WebP (Imagick ou GD). Os envios continuam em JPEG/PNG e nada foi convertido.',
                'apollo-core'
            )
        );
    }
}
add_action('admin_notices', 'apollo_webp_admin_notice');
