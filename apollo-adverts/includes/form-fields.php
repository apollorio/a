<?php

/**
 * Form Field Definitions, Validators, and Filters
 *
 * Defines the "publish" and "search" form schemes.
 * Adapted from WPAdverts includes/defaults.php field definitions.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register field types (renderers)
 * Adapted from WPAdverts field types registration
 */
function apollo_adverts_register_field_types(): void {
	$form = '\Apollo\Adverts\Form';

	$form::register_field_type( 'text', array( $form, 'render_text_field' ) );
	$form::register_field_type( 'select', array( $form, 'render_select_field' ) );
	$form::register_field_type( 'textarea', array( $form, 'render_textarea_field' ) );
	$form::register_field_type( 'checkbox', array( $form, 'render_checkbox_field' ) );
	$form::register_field_type( 'hidden', array( $form, 'render_hidden_field' ) );
}
add_action( 'init', 'apollo_adverts_register_field_types', 8 );

/**
 * Register validators
 * Adapted from WPAdverts validators: is_required, string_length, is_email, etc.
 */
function apollo_adverts_register_validators(): void {
	$form = '\Apollo\Adverts\Form';

	// Required validator
	$form::register_validator(
		'is_required',
		function ( $value, $params, $field ) {
			if ( $value === '' || $value === null || ( is_array( $value ) && empty( $value ) ) ) {
				return sprintf( __( '%s é obrigatório.', 'apollo-adverts' ), $field['label'] );
			}
			return '';
		}
	);

	// String length validator
	$form::register_validator(
		'string_length',
		function ( $value, $params, $field ) {
			$min = $params['min'] ?? 0;
			$max = $params['max'] ?? 0;
			$len = mb_strlen( (string) $value );

			if ( $min && $len < $min ) {
				return sprintf( __( '%1$s deve ter ao menos %2$d caracteres.', 'apollo-adverts' ), $field['label'], $min );
			}
			if ( $max && $len > $max ) {
				return sprintf( __( '%1$s não pode exceder %2$d caracteres.', 'apollo-adverts' ), $field['label'], $max );
			}
			return '';
		}
	);

	// Is email
	$form::register_validator(
		'is_email',
		function ( $value, $params, $field ) {
			if ( ! empty( $value ) && ! is_email( (string) $value ) ) {
				return sprintf( __( '%s não é um email válido.', 'apollo-adverts' ), $field['label'] );
			}
			return '';
		}
	);

	// Numeric
	$form::register_validator(
		'is_numeric',
		function ( $value, $params, $field ) {
			if ( ! empty( $value ) && ! is_numeric( str_replace( ',', '.', (string) $value ) ) ) {
				return sprintf( __( '%s deve ser numérico.', 'apollo-adverts' ), $field['label'] );
			}
			return '';
		}
	);

	// Max choices (for select/checkbox)
	$form::register_validator(
		'max_choices',
		function ( $value, $params, $field ) {
			$max = $params['max'] ?? 1;
			if ( is_array( $value ) && count( $value ) > $max ) {
				return sprintf( __( '%1$s aceita no máximo %2$d opções.', 'apollo-adverts' ), $field['label'], $max );
			}
			return '';
		}
	);
}
add_action( 'init', 'apollo_adverts_register_validators', 8 );

/**
 * Register filters
 * Adapted from WPAdverts filter pipeline
 */
function apollo_adverts_register_filters(): void {
	$form = '\Apollo\Adverts\Form';

	// Sanitize text
	$form::register_filter(
		'sanitize_text',
		function ( $value ) {
			return sanitize_text_field( (string) $value );
		}
	);

	// Sanitize textarea
	$form::register_filter(
		'sanitize_textarea',
		function ( $value ) {
			return sanitize_textarea_field( (string) $value );
		}
	);

	// Format price
	$form::register_filter(
		'format_price',
		function ( $value ) {
			$value = str_replace( array( '.', ',' ), array( '', '.' ), (string) $value );
			return (float) $value;
		}
	);

	// Strip tags
	$form::register_filter(
		'strip_tags',
		function ( $value ) {
			return wp_strip_all_tags( (string) $value );
		}
	);
}
add_action( 'init', 'apollo_adverts_register_filters', 8 );

/**
 * Define "publish" form scheme
 * Adapted from WPAdverts defaults.php adverts_form_add_field
 */
function apollo_adverts_form_scheme_publish( array $fields, string $scheme ): array {
	if ( $scheme !== 'publish' ) {
		return $fields;
	}

	// Build options for domain taxonomy
	$domain_options = array();
	$domain_terms   = get_terms(
		array(
			'taxonomy'   => APOLLO_TAX_CLASSIFIED_DOMAIN,
			'hide_empty' => false,
		)
	);
	if ( ! is_wp_error( $domain_terms ) ) {
		foreach ( $domain_terms as $term ) {
			$domain_options[] = array(
				'value' => $term->slug,
				'text'  => $term->name,
			);
		}
	}

	// Build options for intent taxonomy
	$intent_options = array();
	foreach ( APOLLO_ADVERTS_INTENTS as $slug => $label ) {
		$intent_options[] = array(
			'value' => $slug,
			'text'  => $label,
		);
	}

	// Build options for condition
	$condition_options = array();
	foreach ( APOLLO_ADVERTS_CONDITIONS as $slug => $label ) {
		$condition_options[] = array(
			'value' => $slug,
			'text'  => $label,
		);
	}

	$fields = array(
		// Title
		array(
			'name'        => 'post_title',
			'type'        => 'text',
			'label'       => __( 'Título', 'apollo-adverts' ),
			'order'       => 5,
			'is_required' => true,
			'placeholder' => __( 'Nome do anúncio', 'apollo-adverts' ),
			'max_length'  => 100,
			'validator'   => array(
				array(
					'name' => 'string_length',
					'min'  => 5,
					'max'  => 100,
				),
			),
			'filter'      => array( 'sanitize_text' ),
		),

		// Category (domain)
		array(
			'name'        => APOLLO_TAX_CLASSIFIED_DOMAIN,
			'type'        => 'select',
			'label'       => __( 'Categoria', 'apollo-adverts' ),
			'order'       => 10,
			'is_required' => true,
			'placeholder' => __( 'Selecione...', 'apollo-adverts' ),
			'options'     => $domain_options,
		),

		// Intent
		array(
			'name'        => APOLLO_TAX_CLASSIFIED_INTENT,
			'type'        => 'select',
			'label'       => __( 'Intenção', 'apollo-adverts' ),
			'order'       => 15,
			'is_required' => true,
			'placeholder' => __( 'Selecione...', 'apollo-adverts' ),
			'options'     => $intent_options,
		),

		// Description
		array(
			'name'        => 'post_content',
			'type'        => 'textarea',
			'label'       => __( 'Descrição', 'apollo-adverts' ),
			'order'       => 20,
			'is_required' => true,
			'placeholder' => __( 'Descreva seu anúncio', 'apollo-adverts' ),
			'validator'   => array(
				array(
					'name' => 'string_length',
					'min'  => 20,
					'max'  => 5000,
				),
			),
			'filter'      => array( 'sanitize_textarea' ),
		),

		// Reference value (informational only — Apollo does NOT process payments)
		array(
			'name'        => '_classified_price',
			'type'        => 'text',
			'label'       => __( 'Valor de Referência (R$)', 'apollo-adverts' ),
			'order'       => 25,
			'is_required' => false,
			'placeholder' => '0,00',
			'hint'        => __( 'Apenas informativo. A negociação acontece diretamente entre as partes via Chat.', 'apollo-adverts' ),
			'validator'   => array( 'is_numeric' ),
			'filter'      => array( 'format_price' ),
		),

		// Negotiable
		array(
			'name'  => '_classified_negotiable',
			'type'  => 'checkbox',
			'label' => __( 'Negociável', 'apollo-adverts' ),
			'order' => 30,
		),

		// Condition
		array(
			'name'        => '_classified_condition',
			'type'        => 'select',
			'label'       => __( 'Condição', 'apollo-adverts' ),
			'order'       => 35,
			'placeholder' => __( 'Selecione...', 'apollo-adverts' ),
			'options'     => $condition_options,
		),

		// Location
		array(
			'name'        => '_classified_loc',
			'type'        => 'text',
			'label'       => __( 'Localização', 'apollo-adverts' ),
			'order'       => 40,
			'placeholder' => __( 'Cidade, Estado', 'apollo-adverts' ),
			'filter'      => array( 'sanitize_text' ),
		),

		// Phone
		array(
			'name'        => '_classified_contact_phone',
			'type'        => 'text',
			'label'       => __( 'Telefone', 'apollo-adverts' ),
			'order'       => 45,
			'placeholder' => '(00) 00000-0000',
			'filter'      => array( 'sanitize_text' ),
		),

		// WhatsApp
		array(
			'name'        => '_classified_contact_whatsapp',
			'type'        => 'text',
			'label'       => __( 'WhatsApp', 'apollo-adverts' ),
			'order'       => 50,
			'placeholder' => '(00) 00000-0000',
			'filter'      => array( 'sanitize_text' ),
		),

		// Currency (registry: _classified_currency, default BRL)
		array(
			'name'        => '_classified_currency',
			'type'        => 'select',
			'label'       => __( 'Moeda', 'apollo-adverts' ),
			'order'       => 26,
			'placeholder' => __( 'Selecione...', 'apollo-adverts' ),
			'options'     => array(
				array(
					'value' => 'BRL',
					'text'  => 'R$ — Real (BRL)',
				),
				array(
					'value' => 'USD',
					'text'  => '$ — Dólar (USD)',
				),
				array(
					'value' => 'EUR',
					'text'  => '€ — Euro (EUR)',
				),
			),
		),

		// Expires at (registry: _classified_expires_at, format Y-m-d)
		array(
			'name'        => '_classified_expires_at',
			'type'        => 'text',
			'label'       => __( 'Data de Expiração', 'apollo-adverts' ),
			'order'       => 52,
			'placeholder' => 'AAAA-MM-DD',
			'attr'        => array( 'type' => 'date' ),
			'hint'        => __( 'Opcional. Após esta data o anúncio será desativado automaticamente.', 'apollo-adverts' ),
			'filter'      => array( 'sanitize_text' ),
		),

		// Gallery placeholder (rendered by gallery.php)
		array(
			'name'  => '_classified_gallery',
			'type'  => 'gallery',
			'label' => __( 'Fotos', 'apollo-adverts' ),
			'order' => 55,
		),
	);

	return $fields;
}
add_filter( 'apollo/adverts/form/load', 'apollo_adverts_form_scheme_publish', 10, 2 );

/**
 * Define "search" form scheme
 * Adapted from WPAdverts defaults.php search form
 */
function apollo_adverts_form_scheme_search( array $fields, string $scheme ): array {
	if ( $scheme !== 'search' ) {
		return $fields;
	}

	$domain_options = array();
	$domain_terms   = get_terms(
		array(
			'taxonomy'   => APOLLO_TAX_CLASSIFIED_DOMAIN,
			'hide_empty' => false,
		)
	);
	if ( ! is_wp_error( $domain_terms ) ) {
		foreach ( $domain_terms as $term ) {
			$domain_options[] = array(
				'value' => $term->slug,
				'text'  => $term->name,
			);
		}
	}

	$intent_options = array();
	foreach ( APOLLO_ADVERTS_INTENTS as $slug => $label ) {
		$intent_options[] = array(
			'value' => $slug,
			'text'  => $label,
		);
	}

	$fields = array(
		array(
			'name'        => 'query',
			'type'        => 'text',
			'label'       => __( 'Buscar', 'apollo-adverts' ),
			'order'       => 5,
			'placeholder' => __( 'O que você procura?', 'apollo-adverts' ),
		),
		array(
			'name'        => APOLLO_TAX_CLASSIFIED_DOMAIN,
			'type'        => 'select',
			'label'       => __( 'Categoria', 'apollo-adverts' ),
			'order'       => 10,
			'placeholder' => __( 'Todas', 'apollo-adverts' ),
			'options'     => $domain_options,
		),
		array(
			'name'        => APOLLO_TAX_CLASSIFIED_INTENT,
			'type'        => 'select',
			'label'       => __( 'Intenção', 'apollo-adverts' ),
			'order'       => 15,
			'placeholder' => __( 'Todas', 'apollo-adverts' ),
			'options'     => $intent_options,
		),
		array(
			'name'        => '_classified_loc',
			'type'        => 'text',
			'label'       => __( 'Localização', 'apollo-adverts' ),
			'order'       => 20,
			'placeholder' => __( 'Cidade, Estado', 'apollo-adverts' ),
		),
		array(
			'name'        => 'price_min',
			'type'        => 'text',
			'label'       => __( 'Valor mínimo', 'apollo-adverts' ),
			'order'       => 25,
			'placeholder' => '0',
		),
		array(
			'name'        => 'price_max',
			'type'        => 'text',
			'label'       => __( 'Valor máximo', 'apollo-adverts' ),
			'order'       => 30,
			'placeholder' => '0',
		),
	);

	return $fields;
}
add_filter( 'apollo/adverts/form/load', 'apollo_adverts_form_scheme_search', 10, 2 );
