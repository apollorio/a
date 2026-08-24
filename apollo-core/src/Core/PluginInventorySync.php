<?php
/**
 * Descobre plugins e MU-plugins instalados no WordPress e atualiza apenas a árvore
 * `runtime_wordpress_packages` em `apollo-registry.json` (mantém docs curadas intactas).
 *
 * @package Apollo\Core
 */

declare(strict_types=1);

namespace Apollo\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PluginInventorySync
 */
final class PluginInventorySync {

	/**
	 * Chave no JSON onde vive apenas o estado detetado no runtime WordPress.
	 */
	public const RUNTIME_PACKAGES_KEY = 'runtime_wordpress_packages';

	/**
	 * Garante WP plugin API quando fora do admin (seguro pedir apenas leituras).
	 */
	private static function ensure_plugin_functions(): void {
		if ( ! function_exists( 'get_plugins' ) ) {
			/** @psalm-suppress UnresolvableInclude */
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	/**
	 * Plugins em wp-content/plugins com estado de activação.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function scan_regular_plugins(): array {
		self::ensure_plugin_functions();

		/** @phpstan-ignore-next-line */
		$all = get_plugins();

		$installed = array();
		foreach ( $all as $plugin_file => $data ) {
			$folder = strtok( $plugin_file, '/' );
			if ( false === $folder ) {
				$folder = basename( $plugin_file );
			}
			$active = function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin_file );

			$installed[ $folder ] = array(
				'plugin_file' => $plugin_file,
				'name'        => $data['Name'] ?? '',
				'version'     => $data['Version'] ?? '',
				'active'      => $active,
			);

			if ( is_multisite() && function_exists( 'is_plugin_active_for_network' ) ) {
				/** @phpstan-ignore-next-line */
				$installed[ $folder ]['network_active'] = is_plugin_active_for_network( $plugin_file );
			}
		}

		ksort( $installed, SORT_NATURAL | SORT_FLAG_CASE );

		return $installed;
	}

	/**
	 * Must-use plugins.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function scan_mu_plugins(): array {
		self::ensure_plugin_functions();

		if ( ! function_exists( 'get_mu_plugins' ) ) {
			return array();
		}

		/** @phpstan-ignore-next-line */
		$mu = get_mu_plugins();
		ksort( $mu, SORT_NATURAL | SORT_FLAG_CASE );

		$out = array();
		foreach ( $mu as $file => $data ) {
			$out[ $file ] = array(
				'name'    => $data['Name'] ?? '',
				'version' => $data['Version'] ?? '',
			);
		}

		return $out;
	}

	/**
	 * Mesmo caminho que Apollo\Core\Registry (wp-content/apollo-registry.json ou constante).
	 */
	public static function resolve_registry_absolute_path(): string {
		return wp_normalize_path( Registry::resolve_registry_file_path() );
	}

	/**
	 * Payload completo para embutir no JSON.
	 *
	 * @return array<string, mixed>
	 */
	public static function build_runtime_payload(): array {
		global $wp_version;

		$regular = self::scan_regular_plugins();
		$mu      = self::scan_mu_plugins();

		$inactive = 0;
		foreach ( $regular as $row ) {
			if ( empty( $row['active'] ) ) {
				++$inactive;
			}
		}

		return array(
			'generated_iso'     => gmdate( 'c' ),
			'wordpress_version' => isset( $wp_version ) ? sanitize_text_field( (string) $wp_version ) : '',
			'paths'             => array(
				'plugins_dir'    => wp_normalize_path( WP_PLUGIN_DIR ),
				'mu_plugins_dir' => defined( 'WPMU_PLUGIN_DIR' ) ? wp_normalize_path( (string) WPMU_PLUGIN_DIR ) : '',
			),
			'regular_plugins'   => $regular,
			'must_use_plugins'  => $mu,
			'counts'            => array(
				'regular'  => count( $regular ),
				'must_use' => count( $mu ),
				'inactive' => $inactive,
			),
		);
	}

	/**
	 * Lê o documento JSON existente ou devolve esqueleto mínimo.
	 *
	 * @param string $path Caminho absoluto.
	 * @return array<string, mixed>
	 */
	public static function read_registry_document( string $path ): array {
		if ( ! file_exists( $path ) ) {
			return self::minimal_registry_skeleton();
		}

		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			return self::minimal_registry_skeleton();
		}

		$data = json_decode( $contents, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			return self::minimal_registry_skeleton();
		}

		return $data;
	}

	/**
	 * Estrutura mínima se o JSON estiver corrupto/inexistente.
	 *
	 * @return array<string, mixed>
	 */
	private static function minimal_registry_skeleton(): array {
		return array(
			'$schema'   => 'https://apollo.rio/schemas/registry-v3.json',
			'$version'  => 'auto',
			'cdn'       => array(),
			'plugins'   => array(),
		);
	}

	/**
	 * Mescla inventário e opcionalmente grava o ficheiro.
	 *
	 * @param bool $persist Falso apenas para pré-visualização.
	 * @return array{success:bool, path:string, message:string, merged:array<string,mixed>}
	 */
	public static function sync_registry_file( bool $persist = true ): array {
		$path = wp_normalize_path( self::resolve_registry_absolute_path() );

		$document = self::read_registry_document( $path );
		$payload  = self::build_runtime_payload();

		/** @phpstan-ignore-next-line */
		$payload = apply_filters( 'apollo/registry/runtime_packages', $payload, $document );

		$document[ self::RUNTIME_PACKAGES_KEY ] = $payload;

		/** @phpstan-ignore-next-line */
		$merged = apply_filters( 'apollo/registry/data_after_inventory_sync', $document, $path );

		if ( ! $persist ) {
			return array(
				'success' => true,
				'path'    => $path,
				'message' => __( 'Pré-visualização (persist=false).', 'apollo-core' ),
				'merged'  => $merged,
			);
		}

		$directory = dirname( $path );
		if ( ! wp_mkdir_p( $directory ) ) {
			return array(
				'success' => false,
				'path'    => $path,
				'message' => sprintf(
					/* translators: %s: directory path */
					__( 'Não foi possível criar o diretório: %s', 'apollo-core' ),
					$directory
				),
				'merged'  => $merged,
			);
		}

		if ( ! wp_is_writable( $directory ) ) {
			return array(
				'success' => false,
				'path'    => $path,
				'message' => sprintf(
					/* translators: %s: directory path */
					__( 'Diretório não gravável: %s', 'apollo-core' ),
					$directory
				),
				'merged'  => $merged,
			);
		}

		if ( file_exists( $path ) && ! is_writable( $path ) ) {
			return array(
				'success' => false,
				'path'    => $path,
				'message' => __( 'apollo-registry.json não é gravável (permissões).', 'apollo-core' ),
				'merged'  => $merged,
			);
		}

		$json = wp_json_encode( $merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return array(
				'success' => false,
				'path'    => $path,
				'message' => __( 'Falha ao codificar JSON do registry.', 'apollo-core' ),
				'merged'  => $merged,
			);
		}

		$bytes = file_put_contents( $path, $json, LOCK_EX );
		if ( false === $bytes ) {
			return array(
				'success' => false,
				'path'    => $path,
				'message' => __( 'Falha ao escrever apollo-registry.json.', 'apollo-core' ),
				'merged'  => $merged,
			);
		}

		Registry::clear_cache();

		return array(
			'success' => true,
			'path'    => $path,
			'message' => __( 'Inventário WordPress atualizado no registry.', 'apollo-core' ),
			'merged'  => $merged,
		);
	}
}
