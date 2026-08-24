<?php

/**
 * URL importer page — boot config for /eventos/url.
 *
 * @package Apollo\Event
 * @since   1.7.0
 */

declare(strict_types=1);

namespace Apollo\Event\Import;

if (! defined('ABSPATH')) {
    exit;
}

final class UrlImportPage
{
    /**
     * REST + UI boot payload for the modular importer scripts.
     *
     * @return array<string,mixed>
     */
    public static function config(): array
    {
        return array(
            'restUrl'           => untrailingslashit((string) rest_url()),
            'nonce'             => wp_create_nonce('wp_rest'),
            'insertPath'        => 'apollo/v1/eventos',
            'importPreviewPath' => 'apollo/v1/eventos/importar-url/preview',
            'importPath'        => 'apollo/v1/eventos/importar-url',
            'locPath'           => 'apollo/v1/local',
            'defaultCoupon'     => 'apollo',
            'defaultStatus'     => 'draft',
            'promoterLocMap'    => array(
                'd-edge' => array('slug' => 'dedge', 'name' => 'D-EDGE'),
                'dedge'  => array('slug' => 'dedge', 'name' => 'D-EDGE'),
            ),
            'i18n'              => array(
                'pageTitle'    => __('Importador de Eventos', 'apollo-events'),
                'pageSubtitle' => __('Shotgun + BlueTicket', 'apollo-events'),
            ),
        );
    }

    /**
     * Ordered JS module handles (without .js) relative to assets/js/url-import/.
     *
     * @return string[]
     */
    public static function script_modules(): array
    {
        return array(
            'utils',
            'parse',
            'api',
            'ui',
            'app',
        );
    }

    /**
     * Versioned asset URL for url-import bundle files.
     */
    public static function asset_url(string $relative): string
    {
        $rel = ltrim($relative, '/');
        $ver = function_exists('apollo_event_asset_ver')
            ? apollo_event_asset_ver($rel)
            : (defined('APOLLO_EVENT_VERSION') ? APOLLO_EVENT_VERSION : '1.0.0');

        return APOLLO_EVENT_URL . $rel . '?v=' . rawurlencode((string) $ver);
    }
}
