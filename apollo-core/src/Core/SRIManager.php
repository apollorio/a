<?php

/**
 * SRI (Subresource Integrity) Manager
 *
 * Injects integrity="sha384-..." and crossorigin="anonymous" attributes
 * on Apollo CDN script and style tags to prevent CDN tampering.
 *
 * Usage:
 *   SRIManager::init();
 *   WP-CLI: wp apollo sri regenerate
 *
 * @package Apollo\Core
 */

declare(strict_types=1);

namespace Apollo\Core;

if (! defined('ABSPATH')) {
    exit;
}

class SRIManager
{
    private const OPTION_KEY = 'apollo_sri_hashes';

    /**
     * Known Apollo CDN domains for auto-detection
     */
    private const CDN_DOMAINS = array(
        'cdn.apollo.rio.br',
        'assets.apollo.rio.br',
    );

    /**
     * @var array<string, string> URL => sha384 hash
     */
    private array $hashes = array();

    /**
     * Singleton
     */
    private static ?self $instance = null;

    private function __construct()
    {
        $this->hashes = (array) get_option(self::OPTION_KEY, array());
    }

    /**
     * Initialize SRI filtering
     */
    public static function init(): void
    {
        $self = self::get_instance();

        add_filter('script_loader_tag', array($self, 'filter_script_tag'), 999, 3);
        add_filter('style_loader_tag', array($self, 'filter_style_tag'), 999, 4);

        // WP-CLI command
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('apollo sri', array($self, 'cli_handler'));
        }
    }

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Filter <script> tags to add SRI
     *
     * @param string $tag    The full script tag.
     * @param string $handle The registered handle.
     * @param string $src    The script source URL.
     * @return string
     */
    public function filter_script_tag(string $tag, string $handle, string $src): string
    {
        if (! $this->is_apollo_cdn_url($src)) {
            return $tag;
        }

        // Already has integrity
        if (str_contains($tag, 'integrity=')) {
            return $tag;
        }

        $hash = $this->get_hash($src);
        if (empty($hash)) {
            return $tag;
        }

        $attrs = ' integrity="' . esc_attr($hash) . '" crossorigin="anonymous"';

        // Insert before closing > of opening <script tag, handling existing attributes safely
        if (preg_match('/<script\b/i', $tag)) {
            return preg_replace('/<script\b/i', '<script' . $attrs, $tag, 1);
        }
        return $tag;
    }

    /**
     * Filter <link> stylesheet tags to add SRI
     *
     * @param string $tag    The full link tag.
     * @param string $handle The registered handle.
     * @param string $href   The stylesheet URL.
     * @param string $media  The media attribute.
     * @return string
     */
    public function filter_style_tag(string $tag, string $handle, string $href, string $media): string
    {
        if (! $this->is_apollo_cdn_url($href)) {
            return $tag;
        }

        if (str_contains($tag, 'integrity=')) {
            return $tag;
        }

        $hash = $this->get_hash($href);
        if (empty($hash)) {
            return $tag;
        }

        $attrs = ' integrity="' . esc_attr($hash) . '" crossorigin="anonymous"';

        // Insert before closing > of opening <link tag, handling existing attributes safely
        if (preg_match('/<link\b/i', $tag)) {
            return preg_replace('/<link\b/i', '<link' . $attrs, $tag, 1);
        }
        return $tag;
    }

    /**
     * Check if URL belongs to Apollo CDN
     */
    private function is_apollo_cdn_url(string $url): bool
    {
        $host = wp_parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            return false;
        }
        return in_array($host, self::CDN_DOMAINS, true);
    }

    /**
     * Get SRI hash for a URL
     */
    private function get_hash(string $url): string
    {
        // Normalize URL (strip query string for matching)
        $normalized = strtok($url, '?');
        return $this->hashes[$normalized] ?? '';
    }

    /**
     * Register or update an SRI hash
     *
     * @param string $url  The full CDN URL (without query string).
     * @param string $hash The sha384-... hash string.
     */
    public function set_hash(string $url, string $hash): void
    {
        $this->hashes[$url] = $hash;
        update_option(self::OPTION_KEY, $this->hashes, false);
    }

    /**
     * Compute SHA-384 hash for a remote resource
     *
     * @param string $url The URL to fetch and hash.
     * @return string     The sha384-base64 hash, or empty on failure.
     */
    public function compute_hash(string $url): string
    {
        $response = wp_remote_get($url, array('timeout' => 15));
        if (is_wp_error($response)) {
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return '';
        }

        $raw_hash = hash('sha384', $body, true);
        return 'sha384-' . base64_encode($raw_hash);
    }

    /**
     * Regenerate SRI hashes for all known CDN URLs
     *
     * @param array $urls Array of CDN URLs to hash. If empty, uses stored URLs.
     * @return array<string, string> Results: url => hash or url => error message
     */
    public function regenerate(array $urls = array()): array
    {
        if (empty($urls)) {
            $urls = array_keys($this->hashes);
        }

        $results = array();
        foreach ($urls as $url) {
            $hash = $this->compute_hash($url);
            if (! empty($hash)) {
                $this->hashes[$url] = $hash;
                $results[$url] = $hash;
            } else {
                $results[$url] = 'FAILED';
            }
        }

        update_option(self::OPTION_KEY, $this->hashes, false);
        return $results;
    }

    /**
     * WP-CLI handler: wp apollo sri <subcommand>
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Named arguments.
     */
    public function cli_handler(array $args, array $assoc_args): void
    {
        $subcommand = $args[0] ?? 'list';

        switch ($subcommand) {
            case 'regenerate':
                $cdn_base = defined('APOLLO_CDN_URL') ? APOLLO_CDN_URL : 'https://cdn.apollo.rio.br/v1.0.0/';
                $urls = isset($assoc_args['urls'])
                    ? explode(',', $assoc_args['urls'])
                    : array_keys($this->hashes);

                if (empty($urls)) {
                    \WP_CLI::warning('No URLs to regenerate. Use --urls=url1,url2 or seed hashes first.');
                    return;
                }

                \WP_CLI::log("Regenerating SRI hashes for " . count($urls) . " URLs...");
                $results = $this->regenerate($urls);
                foreach ($results as $url => $hash) {
                    if ($hash === 'FAILED') {
                        \WP_CLI::warning("  ✗ {$url}");
                    } else {
                        \WP_CLI::success("  ✓ {$url} → {$hash}");
                    }
                }
                break;

            case 'add':
                $url = $assoc_args['url'] ?? '';
                if (empty($url)) {
                    \WP_CLI::error('Usage: wp apollo sri add --url=<CDN_URL>');
                    return;
                }
                $hash = $this->compute_hash($url);
                if (empty($hash)) {
                    \WP_CLI::error("Failed to compute hash for: {$url}");
                    return;
                }
                $this->set_hash($url, $hash);
                \WP_CLI::success("{$url} → {$hash}");
                break;

            case 'list':
            default:
                if (empty($this->hashes)) {
                    \WP_CLI::log('No SRI hashes stored.');
                    return;
                }
                foreach ($this->hashes as $url => $hash) {
                    \WP_CLI::log("{$url} → {$hash}");
                }
                break;
        }
    }
}
