<?php
/**
 * Sandbox — renderiza o cabeçalho REAL de /eventos fora do WordPress.
 *
 * POR QUÊ ISSO EXISTE
 * ─────────────────────────────────────────────────────────────────────────────
 * Desde 1.7.1 o cabeçalho do portal não é mais markup montado em JavaScript
 * dentro de app.php::skeleton() — é o bloco PHP apollo_listing_header(), servido
 * no primeiro paint. O harness (build-portal-harness.mjs) é Node e lê arquivos:
 * ele não consegue executar PHP, e a alternativa seria colar um snapshot do HTML
 * do cabeçalho dentro do harness. Isso criaria exatamente o segundo dono que o
 * harness inteiro existe para proibir — a cópia envelheceria em silêncio e o
 * sandbox passaria a provar a geometria de um cabeçalho que não é o que roda.
 *
 * Então o harness chama ESTE arquivo via `php`, e o que ele imprime é a saída
 * das células enviadas: portal/header.php → apollo_listing_header() → as partes
 * em apollo-templates. Um dono só.
 *
 * O QUE É FIXTURE E O QUE É REAL
 * ─────────────────────────────────────────────────────────────────────────────
 * REAL:    todo o caminho de código (adaptador, API, orquestrador, partes,
 *          kernel CSS/JS, skin) — inclusive a normalização de argumentos.
 * FIXTURE: só os DADOS que no site vêm do banco — as linhas de evento (argv[1])
 *          e os termos de taxonomia (get_terms abaixo). É a mesma divisão do
 *          FIXTURE de eventos que o harness já usava.
 *
 * Os stubs de WordPress abaixo são deliberadamente burros: se uma parte começar
 * a depender de algo que não está aqui, o PHP vai gritar `undefined function` e
 * isso é a informação que queremos — uma parte de template ganhou uma
 * dependência nova que ninguém declarou.
 *
 * Uso:  php _sandbox/render-portal-header.php [events.json]
 * Saída: HTML no stdout (styles + markup + scripts, na ordem de emissão real).
 *
 * @package Apollo\Event
 * @since   1.7.1
 */

declare(strict_types=1);

if ('cli' !== PHP_SAPI) {
	exit(1);
}

define('ABSPATH', __DIR__ . '/');

$sandbox_dir       = __DIR__;
$sandbox_events    = $sandbox_dir . '/../';
$sandbox_templates = $sandbox_dir . '/../../apollo-templates/';

// ═══════════════════════════════════════════════════════════════════════════
// STUBS — só o que as células realmente chamam.
// ═══════════════════════════════════════════════════════════════════════════

/** Escapes: o sandbox não é uma superfície de ataque, mas a FORMA importa —
 *  usar htmlspecialchars de verdade mantém a contagem de aspas honesta. */
function esc_attr($t): string
{
	return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
}
function esc_html($t): string
{
	return htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
}
function esc_attr_e($t, $d = ''): void
{
	echo esc_attr($t);
}
function esc_html_e($t, $d = ''): void
{
	echo esc_html($t);
}
function __($t, $d = '')
{
	return $t;
}
function _n($single, $plural, $n, $d = '')
{
	return 1 === (int) $n ? $single : $plural;
}
function apply_filters($hook, $value)
{
	return $value;
}
function sanitize_key($k): string
{
	return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $k)) ?? '';
}
function sanitize_html_class($c): string
{
	return preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $c) ?? '';
}
function wp_json_encode($data, int $flags = 0)
{
	return json_encode($data, $flags);
}
function apollo_json_for_script($data): string
{
	$j = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	return false === $j ? 'null' : $j;
}
function wp_parse_args($args, $defaults = array()): array
{
	return array_merge($defaults, (array) $args);
}
function wp_list_pluck(array $rows, string $field): array
{
	return array_map(static fn($r) => is_array($r) ? ($r[$field] ?? null) : ($r->$field ?? null), $rows);
}
function is_wp_error($t): bool
{
	return false;
}
function checked($checked, $current = true, $echo = true): string
{
	$out = (string) $checked === (string) $current ? " checked='checked'" : '';
	if ($echo) {
		echo $out;
	}
	return $out;
}
function current_time(string $format)
{
	return 'n' === $format || 'Y' === $format ? (int) gmdate($format) : gmdate($format);
}

/** date_i18n em pt-BR sem carregar a i18n do WP: o cabeçalho é tipografia de
 *  mês, então "August" no lugar de "Agosto" mudaria a largura sob teste. */
function date_i18n(string $format, ?int $stamp = null): string
{
	$stamp = $stamp ?? time();
	$m     = (int) gmdate('n', $stamp);
	$long  = array(1 => 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro');
	$short = array(1 => 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez');
	if ('F' === $format) {
		return $long[$m];
	}
	if ('M' === $format) {
		return $short[$m];
	}
	return gmdate($format, $stamp);
}

/* Taxonomias: presentes, com termos de mentira. O objetivo é exercitar o
   caminho de código de get_terms() no adaptador e dar ao painel de filtro um
   volume realista de chips para o sandbox medir. */
function taxonomy_exists($tax): bool
{
	return true;
}
function get_terms($args)
{
	$fixtures = array(
		'event_category' => array('Festa', 'Show', 'Festival', 'Feira'),
		'event_type'     => array('Presencial', 'Online'),
		'event_tag'      => array('LGBTQIA+', 'Gratuito', 'Open bar', 'Ao ar livre'),
		'sound'          => array('Techno', 'House', 'Disco', 'Funk', 'Samba'),
	);
	$names = $fixtures[$args['taxonomy']] ?? array();
	return array_map(
		static function (string $name) {
			$t       = new stdClass();
			$t->name = $name;
			$t->slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? '');
			return $t;
		},
		$names
	);
}

/**
 * apollo_plus_part() de sandbox — sem tema pai/filho, resolve direto no plugin.
 *
 * @param string               $slug Caminho da parte, sem extensão.
 * @param array<string, mixed> $vars Extraído no escopo da parte.
 */
function apollo_plus_part(string $slug, array $vars = array()): void
{
	global $sandbox_templates;
	$file = $sandbox_templates . 'templates/template-parts/' . $slug . '.php';
	if (! is_file($file)) {
		fwrite(STDERR, "apollo_plus_part: parte inexistente → {$slug}\n");
		exit(2);
	}
	extract($vars, EXTR_SKIP); // phpcs:ignore WordPress.PHP.DontExtract -- espelha o apollo_plus_part real.
	require $file;
}

// ═══════════════════════════════════════════════════════════════════════════
// RENDER — o caminho de código enviado, do adaptador para baixo.
// ═══════════════════════════════════════════════════════════════════════════

require $sandbox_templates . 'includes/listing-header-api.php';

$portal_events = array();
if (isset($argv[1]) && is_file($argv[1])) {
	$portal_events = json_decode((string) file_get_contents($argv[1]), true) ?: array();
}

require $sandbox_events . 'styles/base/template-parts/archive/portal/header.php';
require $sandbox_events . 'styles/base/template-parts/archive/portal/header-bridge.php';
