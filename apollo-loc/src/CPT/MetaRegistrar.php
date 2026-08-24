<?php
/**
 * MetaRegistrar — registra meta keys do CPT "local" no REST schema
 *
 * Expõe cada campo para a REST API e para apollo-core MetaRegistry.
 *
 * @package Apollo\Local\CPT
 */

declare(strict_types=1);

namespace Apollo\Local\CPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MetaRegistrar {

	/** @var array<string, array{type:string, single:bool, show_in_rest:bool}> */
	private const META = array(
		'_local_name'        => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_address'     => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_city'        => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_state'       => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_country'     => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_postal'      => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_lat'         => array( 'type' => 'number',  'single' => true, 'show_in_rest' => true ),
		'_local_lng'         => array( 'type' => 'number',  'single' => true, 'show_in_rest' => true ),
		'_local_phone'       => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_website'     => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_instagram'   => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_facebook'    => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_whatsapp'    => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_capacity'    => array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true ),
		'_local_price_range' => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_description' => array( 'type' => 'string',  'single' => true, 'show_in_rest' => true ),
		'_local_image_1'     => array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true ),
		'_local_image_2'     => array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true ),
		'_local_image_3'     => array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true ),
		'_local_image_4'     => array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true ),
		'_local_image_5'     => array( 'type' => 'integer', 'single' => true, 'show_in_rest' => true ),
	);

	public function __construct() {
		/* PHASE 1.1 — 2026-08-11 · DOUBLE REGISTRATION CLOSED.
		 *
		 * This class used to call register_post_meta() directly on init:10, for 21
		 * keys apollo-core ALSO defines in MetaRegistry::load_definitions(). Core
		 * registers on init:9, so this ran second and silently overwrote core's
		 * sanitize_callback with none at all — the later registration wins and
		 * nothing anywhere reports the conflict.
		 *
		 * It also hooked `apollo_core_register_meta`, which is not a filter that
		 * exists. The real name is `apollo_core_register_post_meta` (MetaRegistry
		 * line 1865, applied on init:8). So the one legal path was mis-spelled and
		 * the illegal path was the only one running.
		 *
		 * Now: contribute through the filter, register nothing here. apollo-core
		 * stays the single registrar, per 02-header.CRITICAL_apollo_core_centralization.
		 */
		add_filter( 'apollo_core_register_post_meta', array( $this, 'register_core_meta' ) );
	}

	/**
	 * @deprecated 2026-08-11 Superseded by register_core_meta(). apollo-core is the
	 *             only registrar. Retained as a no-op so any external caller that
	 *             still invokes it does not fatal; delete after one release.
	 */
	public function register(): void {
		_doing_it_wrong(
			__METHOD__,
			'apollo-loc no longer registers meta directly. It contributes through the apollo_core_register_post_meta filter.',
			'1.0.2'
		);
	}

	/** Original body, retained only as the reference shape for the two structured keys. */
	/* The former body of legacy_register_unused() is gone: it held four
	 * register_post_meta() calls that G1 forbids anywhere outside apollo-core, and
	 * keeping them as unreachable code still fails the grep that enforces the rule.
	 * The two structured definitions they carried now live in register_core_meta()
	 * below, which is the legal path. Shape preserved, call site removed. */
	/**
	 * Expõe metadados para apollo-core MetaRegistry centralizado.
	 */
	public function register_core_meta( array $meta_config ): array {
		$sanitizers = array(
			'string'  => 'sanitize_text_field',
			'number'  => 'floatval',
			'integer' => 'absint',
		);

		foreach ( self::META as $key => $def ) {
			$sanitize = $sanitizers[ $def['type'] ] ?? 'sanitize_text_field';
			if ( in_array( $key, array( '_local_website', '_local_instagram', '_local_facebook', '_local_whatsapp' ), true ) ) {
				$sanitize = 'esc_url_raw';
			}
			$meta_config[ APOLLO_LOCAL_CPT ][ $key ] = array(
				'type'         => $def['type'],
				'single'       => true,
				'show_in_rest' => true,
				'sanitize'     => $sanitize,
			);
		}

		/* PHASE 1.2 — the two structured keys. Both were being WRITTEN (LocPanel
		 * saves them) and registered NOWHERE core could see, so they had no REST
		 * schema and no sanitizer. They feed the Estrutura section and the hero
		 * hours line on /local/{slug}. */
		$meta_config[ APOLLO_LOCAL_CPT ]['_local_hours'] = array(
			'type'         => 'array',
			'description'  => 'Opening hours — per-day indexed array of time strings (0=Seg … 6=Dom).',
			'single'       => true,
			'show_in_rest' => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'string' ),
				),
			),
			'default'      => array(),
		);

		$meta_config[ APOLLO_LOCAL_CPT ]['_local_amenities'] = array(
			'type'         => 'array',
			'description'  => 'Estrutura repeater — { icon, name, sub }. Also the source of the marquee tags.',
			'single'       => true,
			'show_in_rest' => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'icon' => array( 'type' => 'string' ),
							'name' => array( 'type' => 'string' ),
							'sub'  => array( 'type' => 'string' ),
						),
					),
				),
			),
			'default'      => array(),
		);

		return $meta_config;
	}

	/**
	 * Retorna array de todas as meta keys (uso externo).
	 *
	 * @return string[]
	 */
	public static function get_keys(): array {
		return array_keys( self::META );
	}

	/**
	 * Retorna full META definitions (uso de CoreIntegration).
	 *
	 * @return array<string, array{type:string, single:bool, show_in_rest:bool}>
	 */
	public static function get_definitions(): array {
		return self::META;
	}
}
