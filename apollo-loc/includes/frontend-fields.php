<?php
/**
 * Apollo Local — Frontend Editor Field Definitions
 *
 * Registers Local fields with the shared frontend editing system
 * (apollo-templates/FrontendEditor).
 *
 * - Registers 'local' as an editable CPT
 * - Defines all Local meta fields with types, labels, icons, sections
 * - Configures editor layout (hero with no cover, featured image as avatar)
 *
 * URL: /editar/local/{post_id}/
 *
 * @package Apollo\Local
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
═══════════════════════════════════════════════════════════════════════════
	REGISTER LOC AS EDITABLE CPT
	═══════════════════════════════════════════════════════════════════════════ */

add_filter(
	'apollo_editable_post_types',
	function ( array $types ): array {
		$types[] = 'local';
		return $types;
	}
);

/*
═══════════════════════════════════════════════════════════════════════════
	EDITOR CONFIGURATION
	═══════════════════════════════════════════════════════════════════════════ */

add_filter(
	'apollo_editor_config_local',
	function ( array $config ): array {
		$config['page_title']   = __( 'Editar Local', 'apollo-local' );
		$config['hero_enabled'] = true;
		$config['cover_field']  = '_local_image_1';
		$config['avatar_field'] = '';
		$config['sections']     = array( 'main', 'gallery', 'address', 'links', 'details' );

		$config['section_labels'] = array(
			'main'    => __( 'Informações', 'apollo-local' ),
			'gallery' => __( 'Galeria', 'apollo-local' ),
			'address' => __( 'Endereço & Coordenadas', 'apollo-local' ),
			'links'   => __( 'Links & Contato', 'apollo-local' ),
			'details' => __( 'Detalhes', 'apollo-local' ),
		);

		$config['section_icons'] = array(
			'main'    => 'ri-store-2-line',
			'gallery' => 'ri-image-line',
			'address' => 'ri-map-pin-line',
			'links'   => 'ri-links-line',
			'details' => 'ri-information-line',
		);

		$config['save_label'] = __( 'Salvar Local', 'apollo-local' );

		return $config;
	}
);

/*
═══════════════════════════════════════════════════════════════════════════
	FIELD DEFINITIONS
	═══════════════════════════════════════════════════════════════════════════ */

add_filter(
	'apollo_frontend_fields_local',
	function ( array $fields ): array {

		// ─── Main section ───────────────────────────────────────────────────

		$fields[] = array(
			'name'        => '_local_name',
			'type'        => 'text',
			'label'       => __( 'Nome do Local', 'apollo-local' ),
			'icon'        => 'ri-store-2-line',
			'placeholder' => __( 'Nome do espaço...', 'apollo-local' ),
			'required'    => true,
			'maxlength'   => 200,
			'section'     => 'main',
		);

		// Types Taxonomy
		$fields[] = array(
			'name'        => '_local_types',
			'type'        => 'taxonomy',
			'label'       => __( 'Tipos', 'apollo-local' ),
			'icon'        => 'ri-exchange-box-line',
			'section'     => 'main',
			'taxonomy'    => 'local_type',
			'tax_format'  => 'checkbox',
			'description' => __( 'Selecione os tipos deste espaço.', 'apollo-local' ),
		);

		// Area Taxonomy
		$fields[] = array(
			'name'       => '_local_areas',
			'type'       => 'taxonomy',
			'label'      => __( 'Áreas / Bairros', 'apollo-local' ),
			'icon'       => 'ri-map-2-line',
			'section'    => 'main',
			'taxonomy'   => 'local_area',
			'tax_format' => 'checkbox',
		);

		// ─── Gallery (_local_image_1 is also the hero cover) ────────────────
		for ( $i = 2; $i <= 5; $i++ ) {
			$fields[] = array(
				'name'        => '_local_image_' . $i,
				'type'        => 'image',
				'label'       => sprintf(
					/* translators: %d: gallery slot number */
					__( 'Foto %d', 'apollo-local' ),
					$i
				),
				'icon'        => 'ri-image-line',
				'section'     => 'gallery',
				'description' => __( 'Attachment id na biblioteca de mídia.', 'apollo-local' ),
			);
		}

		// ─── Address section ────────────────────────────────────────────────

		$fields[] = array(
			'name'        => '_local_address',
			'type'        => 'text',
			'label'       => __( 'Endereço', 'apollo-local' ),
			'icon'        => 'ri-road-map-line',
			'placeholder' => __( 'Rua, número, complemento', 'apollo-local' ),
			'section'     => 'address',
		);

		$fields[] = array(
			'name'        => '_local_city',
			'type'        => 'text',
			'label'       => __( 'Cidade', 'apollo-local' ),
			'icon'        => 'ri-building-line',
			'placeholder' => __( 'Rio de Janeiro', 'apollo-local' ),
			'section'     => 'address',
		);

		$fields[] = array(
			'name'        => '_local_state',
			'type'        => 'text',
			'label'       => __( 'Estado', 'apollo-local' ),
			'icon'        => 'ri-map-line',
			'placeholder' => __( 'RJ', 'apollo-local' ),
			'maxlength'   => 2,
			'section'     => 'address',
		);

		$fields[] = array(
			'name'        => '_local_country',
			'type'        => 'text',
			'label'       => __( 'País', 'apollo-local' ),
			'icon'        => 'ri-earth-line',
			'placeholder' => 'Brasil',
			'section'     => 'address',
		);

		$fields[] = array(
			'name'        => '_local_postal',
			'type'        => 'text',
			'label'       => __( 'CEP', 'apollo-local' ),
			'icon'        => 'ri-mail-send-line',
			'placeholder' => '00000-000',
			'maxlength'   => 10,
			'section'     => 'address',
		);

		// Section divider for coordinates
		$fields[] = array(
			'name'    => '_section_coords',
			'type'    => 'section',
			'label'   => __( 'Coordenadas', 'apollo-local' ),
			'icon'    => 'ri-compass-3-line',
			'section' => 'address',
		);

		$fields[] = array(
			'name'        => '_local_lat',
			'type'        => 'coordinates',
			'label'       => __( 'Latitude', 'apollo-local' ),
			'icon'        => 'ri-compass-line',
			'placeholder' => '-22.9068',
			'section'     => 'address',
			'description' => __( 'Ex: -22.9068 (Rio de Janeiro)', 'apollo-local' ),
		);

		$fields[] = array(
			'name'        => '_local_lng',
			'type'        => 'coordinates',
			'label'       => __( 'Longitude', 'apollo-local' ),
			'icon'        => 'ri-compass-line',
			'placeholder' => '-43.1729',
			'section'     => 'address',
			'description' => __( 'Ex: -43.1729 (Rio de Janeiro)', 'apollo-local' ),
		);

		// ─── Links section ──────────────────────────────────────────────────

		$fields[] = array(
			'name'        => '_local_phone',
			'type'        => 'tel',
			'label'       => __( 'Telefone', 'apollo-local' ),
			'icon'        => 'ri-phone-line',
			'placeholder' => '+55 21 99999-9999',
			'section'     => 'links',
		);

		$fields[] = array(
			'name'        => '_local_website',
			'type'        => 'url',
			'label'       => __( 'Website', 'apollo-local' ),
			'icon'        => 'ri-global-line',
			'placeholder' => 'https://...',
			'section'     => 'links',
		);

		$fields[] = array(
			'name'        => '_local_instagram',
			'type'        => 'url',
			'label'       => __( 'Instagram', 'apollo-local' ),
			'icon'        => 'ri-instagram-line',
			'placeholder' => 'https://instagram.com/...',
			'section'     => 'links',
		);

		// ─── Details section ────────────────────────────────────────────────

		$fields[] = array(
			'name'        => '_local_capacity',
			'type'        => 'number',
			'label'       => __( 'Capacidade', 'apollo-local' ),
			'icon'        => 'ri-group-line',
			'placeholder' => '500',
			'min'         => '0',
			'max'         => '100000',
			'section'     => 'details',
			'description' => __( 'Quantidade máxima de pessoas.', 'apollo-local' ),
		);

		$fields[] = array(
			'name'    => '_local_price_range',
			'type'    => 'select',
			'label'   => __( 'Faixa de preço', 'apollo-local' ),
			'icon'    => 'ri-money-dollar-circle-line',
			'section' => 'details',
			'options' => array(
				'$'    => '$ — Econômico',
				'$$'   => '$$ — Moderado',
				'$$$'  => '$$$ — Premium',
				'$$$$' => '$$$$ — Luxo',
			),
		);

		return $fields;
	}
);
