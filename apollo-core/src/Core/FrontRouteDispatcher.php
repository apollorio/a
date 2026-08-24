<?php

/**
 * Central Apollo front-route resolver (registry / pages-rest driven).
 *
 * Priority 0 on template_redirect: records match, dev logging, theme dequeue.
 * Rendering stays with owning plugins (login P1, domain P5, templates P10)
 * until handlers register on apollo/routes/register.
 *
 * @package Apollo\Core
 * @since   6.1.0
 */

declare(strict_types=1);

namespace Apollo\Core;

if (! defined('ABSPATH')) {
	exit;
}

class FrontRouteDispatcher
{

	private static ?array $route_index = null;

	private static bool $booted = false;

	public static function init(): void
	{
		if (self::$booted) {
			return;
		}
		self::$booted = true;

		add_action('template_redirect', array(static::class, 'dispatch'), 0);
		add_filter('apollo/routes/register', array(static::class, 'register_builtin_handlers'), 5);
	}

	/**
	 * @param array<string, array<string, mixed>> $handlers slug => config.
	 * @return array<string, array<string, mixed>>
	 */
	public static function register_builtin_handlers(array $handlers): array
	{
		return $handlers;
	}

	public static function dispatch(): void
	{
		if (is_admin()) {
			return;
		}

		$path = function_exists('apollo_normalize_request_path')
			? apollo_normalize_request_path()
			: '';

		$match = self::resolve_path($path);

		if ($match !== null) {
			$GLOBALS['apollo_current_route'] = $match;
			do_action('apollo/route/matched', $match, $path);
			self::dev_log_head_compliance($match);
		}

		if (function_exists('apollo_is_dev_mode') && apollo_is_dev_mode()) {
			self::dev_log_route($path, $match);
		}

		if ($match !== null || (function_exists('apollo_is_reserved_virtual_path') && apollo_is_reserved_virtual_path($path))) {
			self::maybe_dequeue_theme_assets();
		}

		if ($match === null) {
			return;
		}

		$handlers = (array) apply_filters('apollo/routes/register', array());
		$slug     = $match['slug'] ?? '';
		$plugin   = $match['plugin'] ?? '';

		if ($slug !== '' && isset($handlers[ $slug ]) && is_callable($handlers[ $slug ]['callback'] ?? null)) {
			do_action('apollo/route/before_render', $match, $path);
			call_user_func($handlers[ $slug ]['callback'], $match, $path);
		}
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function resolve_path(string $path): ?array
	{
		if ($path === '') {
			$path = '/';
		}

		$index = self::get_route_index();

		if (isset($index[ $path ])) {
			return $index[ $path ];
		}

		$segment = explode('/', $path, 2)[0];
		if ($segment !== '' && isset($index[ $segment ])) {
			return $index[ $segment ];
		}

		if ($path === '/' && isset($index['/'])) {
			return $index['/'];
		}

		return null;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_route_index(): array
	{
		if (self::$route_index !== null) {
			return self::$route_index;
		}

		$cached = wp_cache_get('apollo_route_index', 'apollo');
		if (is_array($cached)) {
			self::$route_index = $cached;
			return self::$route_index;
		}

		self::$route_index = self::build_route_index();
		wp_cache_set('apollo_route_index', self::$route_index, 'apollo', HOUR_IN_SECONDS);

		return self::$route_index;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function build_route_index(): array
	{
		$index   = array();
		$paths   = array(
			WP_CONTENT_DIR . '/apollo-pages-rest.json',
			(defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : '') . '/_inventory/outdated v1/pages-rest.json',
		);

		$data = null;
		foreach ($paths as $file) {
			if (! is_string($file) || $file === '' || ! is_readable($file)) {
				continue;
			}
			$json = file_get_contents($file); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if (false === $json) {
				continue;
			}
			$decoded = json_decode($json, true);
			if (is_array($decoded)) {
				$data = $decoded;
				break;
			}
		}

		if (! is_array($data)) {
			return $index;
		}

		foreach ($data as $plugin_slug => $plugin_data) {
			if (! is_array($plugin_data) || ! isset($plugin_data['pages']) || ! is_array($plugin_data['pages'])) {
				continue;
			}
			if (str_starts_with((string) $plugin_slug, '_')) {
				continue;
			}

			foreach ($plugin_data['pages'] as $page) {
				if (! is_array($page) || empty($page['slug'])) {
					continue;
				}
				$slug_raw = (string) $page['slug'];
				if (str_contains($slug_raw, '{')) {
					continue;
				}

				$key = $slug_raw === '/' ? '/' : trim($slug_raw, '/');
				$index[ $key ] = array(
					'slug'          => $key,
					'plugin'        => (string) $plugin_slug,
					'template'      => $page['template'] ?? null,
					'type'          => $page['type'] ?? 'virtual',
					'blank_canvas'  => ! empty($page['blank_canvas']),
					'auth'          => $page['auth'] ?? null,
				);
			}
		}

		$index['/'] = array(
			'slug'     => '/',
			'plugin'   => 'apollo-templates',
			'template' => 'page-home.php',
			'type'     => 'virtual',
		);

		return (array) apply_filters('apollo/routes/index', $index);
	}

	public static function clear_cache(): void
	{
		wp_cache_delete('apollo_route_index', 'apollo');
		self::$route_index = null;
	}

	/**
	 * @param array<string, mixed> $match Route match.
	 */
	private static function dev_log_head_compliance(array $match): void
	{
		$entry = array(
			'sessionId'    => 'c0df4e',
			'hypothesisId' => 'B',
			'location'     => 'FrontRouteDispatcher::dispatch',
			'message'      => 'route matched — pages-rest registry',
			'data'         => array(
				'slug'         => $match['slug'] ?? null,
				'plugin'       => $match['plugin'] ?? null,
				'template'     => $match['template'] ?? null,
				'blank_canvas' => $match['blank_canvas'] ?? false,
				'uses_canonical_head' => function_exists('apollo_render_document_head'),
			),
			'timestamp'    => (int) round(microtime(true) * 1000),
		);

		// #region agent log
		@file_put_contents(
			WP_CONTENT_DIR . '/debug-c0df4e.log',
			wp_json_encode($entry) . "\n",
			FILE_APPEND | LOCK_EX
		);
		// #endregion

		if (! function_exists('apollo_is_dev_mode') || ! apollo_is_dev_mode()) {
			return;
		}

		$entry['sessionId'] = '4ad441';
		@file_put_contents(
			WP_CONTENT_DIR . '/debug-4ad441.log',
			wp_json_encode($entry) . "\n",
			FILE_APPEND | LOCK_EX
		);
	}

	/**
	 * @param array<string, mixed>|null $match Route match.
	 */
	private static function dev_log_route(string $path, ?array $match): void
	{
		$login_var = get_query_var('apollo_login_page', '');
		$context   = array(
			'path'              => $path,
			'match_plugin'      => $match['plugin'] ?? null,
			'match_slug'        => $match['slug'] ?? null,
			'apollo_login_page' => $login_var !== '' ? $login_var : null,
			'is_reserved'       => function_exists('apollo_is_reserved_virtual_path')
				? apollo_is_reserved_virtual_path($path)
				: null,
			'is_front_page'     => is_front_page(),
			'is_page'           => is_page(),
			'pagename'          => get_query_var('pagename', ''),
		);

		if (function_exists('apollo_debug_log')) {
			apollo_debug_log('FrontRouteDispatcher', $context, 'apollo-core');
		}
	}

	private static function maybe_dequeue_theme_assets(): void
	{
		if (! function_exists('apollo_is_dev_mode') || ! apollo_is_dev_mode()) {
			return;
		}

		add_action(
			'wp_enqueue_scripts',
			static function (): void {
				global $wp_styles, $wp_scripts;
				if ($wp_styles instanceof \WP_Styles) {
					foreach ($wp_styles->queue as $handle) {
						if (str_contains($handle, 'apollo') || str_contains($handle, 'elementor')) {
							continue;
						}
						if (! str_starts_with($handle, 'wp-')) {
							wp_dequeue_style($handle);
						}
					}
				}
				if ($wp_scripts instanceof \WP_Scripts) {
					foreach ($wp_scripts->queue as $handle) {
						if (str_contains($handle, 'apollo') || str_contains($handle, 'jquery')) {
							continue;
						}
						if (! str_starts_with($handle, 'wp-')) {
							wp_dequeue_script($handle);
						}
					}
				}
			},
			9999
		);
	}
}
