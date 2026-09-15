<?php

/**
 * Apollo route helpers — normalized paths and reserved virtual slugs.
 *
 * @package Apollo\Core
 * @since   6.1.0
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Request path after site home path (subdirectory-safe).
 */
function apollo_normalize_request_path(): string
{
	$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
	$raw_path    = parse_url($request_uri, PHP_URL_PATH);
	if (! is_string($raw_path) || $raw_path === '') {
		$raw_path = '';
	}
	$path = trim($raw_path, '/');

	$home_path = parse_url(home_url('/'), PHP_URL_PATH);
	if (! is_string($home_path) || $home_path === '') {
		$home_path = '';
	}
	$home_path = trim($home_path, '/');

	if ($home_path !== '' && $path !== '') {
		if ($path === $home_path) {
			$path = '';
		} else {
			$prefix = $home_path . '/';
			if (str_starts_with($path, $prefix)) {
				$path = trim(substr($path, strlen($prefix)), '/');
			}
		}
	}

	return $path;
}

/**
 * First URL segment (static routes) or full path for matching.
 */
function apollo_request_path_segment(int $index = 0): string
{
	$path = apollo_normalize_request_path();
	if ($path === '') {
		return $index === 0 ? '' : '';
	}
	$parts = explode('/', $path);
	return $parts[ $index ] ?? '';
}

/**
 * Reserved first-segment slugs owned by Apollo plugins (not WP Pages).
 *
 * @return array<int, string>
 */
function apollo_get_reserved_virtual_slugs(): array
{
	$slugs = array(
		'acesso',
		'access',
		'acessar',
		'entrar',
		'registre',
		'reset',
		'verificar-email',
		'sair',
		'feed',
		'explore',
		'mural',
		'casa',
		'home',
		'sobre',
		'about-us',
		'test',
		'mapa',
		'eventos',
		'evento',
		'portal',
		'criar-evento',
		'novo-evento',
		'add-evento',
		'meus-eventos',
		'djs',
		'dj',
		'local',
		'anuncios',
		'anuncio',
		'marketplace',
		'criar-anuncio',
		'id',
		'radar',
		'editar-perfil',
		'editar',
		'editar-hub',
		'hub',
		'mensagens',
		'conquistas',
		'conquista',
		'minhas-conquistas',
		'pontos',
		'niveis',
		'nivel',
		'placar',
		'evidencia',
		'fornecedores',
		'fornecedor',
		'cena',
		'painel',
		'dashboard',
		'documentos',
		'assinar',
		'grupos',
		'grupo',
		'comunas',
		'nucleos',
		'criar-grupo',
		'criar-comuna',
		'notificacoes',
		'jornal',
		'artigo',
		'nota',
		'offline',
		'classificados',
		'fornecedores',
	);

	/**
	 * @param array<int, string> $slugs Reserved path segments.
	 */
	return (array) apply_filters('apollo/routes/reserved_slugs', $slugs);
}

/**
 * Whether the current request path is a reserved Apollo virtual route.
 */
function apollo_is_reserved_virtual_path(?string $path = null): bool
{
	$path = $path ?? apollo_normalize_request_path();
	if ($path === '') {
		return false;
	}

	$segment = explode('/', $path, 2)[0];
	$reserved = apollo_get_reserved_virtual_slugs();

	if (in_array($segment, $reserved, true)) {
		return true;
	}

	if (in_array($path, $reserved, true)) {
		return true;
	}

	return (bool) apply_filters('apollo/routes/is_reserved', false, $path, $segment);
}

/**
 * Whether the current request is served as a blank-canvas template (no wp_head CDN).
 */
function apollo_is_blank_canvas_request(): bool
{
	if (! empty(get_query_var('apollo_login_page', ''))) {
		return true;
	}

	$canvas_vars = array(
		'apollo_home_page',
		'apollo_feed_page',
		'apollo_sobre_page',
		'apollo_test_page',
		'apollo_user_page',
		'apollo_event_page',
		'apollo_dashboard_page',
		'apollo_adverts_page',
		'apollo_casa_page',
	);

	foreach ($canvas_vars as $var) {
		if (! empty(get_query_var($var, ''))) {
			return true;
		}
	}

	/* Reserved virtual slugs are plugin templates that skip wp_head.
	   Without this, /anuncios (and siblings) inherit the theme-page CSP
	   (nonce + strict-dynamic) and every helper script without a nonce
	   is held/blocked while core.js still fans out a CDN request storm. */
	if (apollo_is_reserved_virtual_path()) {
		return true;
	}

	return (bool) apply_filters('apollo/routes/is_blank_canvas', false);
}

/**
 * Whether APOLLO dev tooling should run on this request.
 */
function apollo_is_dev_mode(): bool
{
	return ( defined('APOLLO_DEV_MODE') && APOLLO_DEV_MODE )
		|| ( defined('WP_ENVIRONMENT_TYPE') && 'local' === WP_ENVIRONMENT_TYPE && defined('WP_DEBUG') && WP_DEBUG );
}
