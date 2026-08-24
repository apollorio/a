<?php
/**
 * CPT, meta, and hook registration via apollo-core GLOBAL BRIDGE.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry {

	public function __construct() {
		add_action( 'init', array( $this, 'register_cpts' ), 5 );
		add_filter( 'apollo_core_register_meta', array( $this, 'register_meta' ) );
		add_filter( 'apollo_core_register_user_meta', array( $this, 'register_user_meta' ) );
	}

	public function register_cpts(): void {
		$this->register_single_cpt(
			APOLLO_SCHEDULER_CPT_APPOINTMENT,
			array(
				'name'          => __( 'Appointments', 'apollo-scheduler' ),
				'singular_name' => __( 'Appointment', 'apollo-scheduler' ),
				'rewrite'       => 'agendamento',
				'archive'       => 'agendamentos',
				'rest_base'     => 'appointments',
				'menu_icon'     => 'dashicons-calendar',
			)
		);

		$this->register_single_cpt(
			APOLLO_SCHEDULER_CPT_SERVICE,
			array(
				'name'          => __( 'Services', 'apollo-scheduler' ),
				'singular_name' => __( 'Service', 'apollo-scheduler' ),
				'rewrite'       => 'servico',
				'archive'       => 'servicos',
				'rest_base'     => 'services',
				'menu_icon'     => 'dashicons-hammer',
			)
		);

		$this->register_single_cpt(
			APOLLO_SCHEDULER_CPT_RESOURCE,
			array(
				'name'          => __( 'Resources', 'apollo-scheduler' ),
				'singular_name' => __( 'Resource', 'apollo-scheduler' ),
				'rewrite'       => 'recurso',
				'archive'       => 'recursos',
				'rest_base'     => 'resources',
				'menu_icon'     => 'dashicons-building',
			)
		);
	}

	/**
	 * @param array<string, string> $config
	 */
	private function register_single_cpt( string $slug, array $config ): void {
		if ( post_type_exists( $slug ) ) {
			return;
		}

		register_post_type(
			$slug,
			array(
				'labels'              => array(
					'name'          => $config['name'],
					'singular_name' => $config['singular_name'],
				),
				'public'              => true,
				'has_archive'         => $config['archive'],
				'rewrite'             => array(
					'slug'       => $config['rewrite'],
					'with_front' => false,
				),
				'rest_base'           => $config['rest_base'],
				'show_in_rest'        => true,
				'supports'            => array( 'title', 'editor', 'author' ),
				'menu_icon'           => $config['menu_icon'],
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'show_in_admin_bar'   => true,
				'exclude_from_search' => false,
			)
		);
	}

	/**
	 * @param array<string, array<string, mixed>> $meta
	 * @return array<string, array<string, mixed>>
	 */
	public function register_meta( array $meta ): array {
		if ( ! isset( $meta[ APOLLO_SCHEDULER_CPT_APPOINTMENT ] ) ) {
			$meta[ APOLLO_SCHEDULER_CPT_APPOINTMENT ] = array();
		}
		foreach ( APOLLO_SCHEDULER_APPOINTMENT_META as $key ) {
			$meta[ APOLLO_SCHEDULER_CPT_APPOINTMENT ][ $key ] = array(
				'type'     => $this->meta_type_for_key( $key ),
				'sanitize' => $this->sanitize_for_key( $key ),
			);
		}

		if ( ! isset( $meta[ APOLLO_SCHEDULER_CPT_SERVICE ] ) ) {
			$meta[ APOLLO_SCHEDULER_CPT_SERVICE ] = array();
		}
		foreach ( APOLLO_SCHEDULER_SERVICE_META as $key ) {
			$meta[ APOLLO_SCHEDULER_CPT_SERVICE ][ $key ] = array(
				'type'     => $this->meta_type_for_key( $key ),
				'sanitize' => $this->sanitize_for_key( $key ),
			);
		}

		if ( ! isset( $meta[ APOLLO_SCHEDULER_CPT_RESOURCE ] ) ) {
			$meta[ APOLLO_SCHEDULER_CPT_RESOURCE ] = array();
		}
		foreach ( APOLLO_SCHEDULER_RESOURCE_META as $key ) {
			$meta[ APOLLO_SCHEDULER_CPT_RESOURCE ][ $key ] = array(
				'type'     => $this->meta_type_for_key( $key ),
				'sanitize' => $this->sanitize_for_key( $key ),
			);
		}

		return $meta;
	}

	/**
	 * @param array<string, array<string, mixed>> $meta
	 * @return array<string, array<string, mixed>>
	 */
	public function register_user_meta( array $meta ): array {
		$meta['_apollo_is_agent'] = array(
			'type'    => 'boolean',
			'single'  => true,
			'default' => false,
		);
		$meta['_apollo_agent_of_nucleo'] = array(
			'type'    => 'integer',
			'single'  => true,
			'default' => 0,
		);
		$meta['_apollo_agent_services'] = array(
			'type'    => 'array',
			'single'  => true,
			'default' => array(),
		);
		$meta['_apollo_agent_rooms'] = array(
			'type'    => 'array',
			'single'  => true,
			'default' => array(),
		);
		$meta['_apollo_working_plan'] = array(
			'type'         => 'object',
			'single'       => true,
			'show_in_rest' => array(
				'schema' => array(
					'type'                 => 'object',
					'additionalProperties' => array(
						'oneOf' => array(
							array( 'type' => 'null' ),
							array(
								'type'       => 'object',
								'properties' => array(
									'start'  => array( 'type' => 'string' ),
									'end'    => array( 'type' => 'string' ),
									'breaks' => array(
										'type'  => 'array',
										'items' => array( 'type' => 'object' ),
									),
								),
							),
						),
					),
				),
			),
			'default' => apollo_scheduler_default_working_plan(),
		);

		return $meta;
	}

	private function meta_type_for_key( string $key ): string {
		if ( str_contains( $key, '_price' ) ) {
			return 'number';
		}
		if ( str_contains( $key, '_id' ) || str_contains( $key, '_duration' ) || str_contains( $key, '_interval' ) || str_contains( $key, '_capacity' ) ) {
			return 'integer';
		}
		if ( str_contains( $key, '_agents' ) || str_contains( $key, '_resources' ) ) {
			return 'array';
		}
		return 'string';
	}

	private function sanitize_for_key( string $key ): string {
		$type = $this->meta_type_for_key( $key );
		return match ( $type ) {
			'integer' => 'absint',
			'number'  => 'floatval',
			'array'   => 'wp_parse_list',
			default   => 'sanitize_text_field',
		};
	}
}
