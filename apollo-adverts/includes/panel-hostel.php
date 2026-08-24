<?php
/**
 * Hostel — admin editing surface.
 *
 * `hostel` shipped on 2026-08-17 with five registered meta keys and ZERO
 * inputs, editable only as a bare title. This closes that, declaratively —
 * apollo_panel_register() takes an array, so there is no class here and no edit
 * to apollo-lux-panels.
 *
 * DELIBERATELY THIN, AND THAT IS THE DESIGN
 * -----------------------------------------
 * There is no address, no coordinates and no gallery on this panel, because
 * `local` already registers all three. A hostel that needs a map or a photo set
 * relates to a `local` through _hostel_loc_id instead of duplicating those keys.
 * Two copies of address meta is precisely the divergence the registry already
 * flags at apollo-adverts.json $accommodation_meta_and_depoimentos_2026_07_28,
 * and the cheapest moment to not create it is now.
 *
 * THE RELATION IS ADMIN-ONLY AND LIVES ON THE ADVERT, NOT HERE
 * -----------------------------------------------------------
 * _classified_hostel_id is a field on the `classified` panel/metabox, gated on
 * manage_options. An advert belongs EITHER to a member (post_author) OR to an
 * official hostel — never both — and only staff decide which, because that
 * decision is what lets a listing bypass the marketplace auth gate.
 *
 * @package Apollo\Adverts
 * @since   1.1.3
 * @see     apollo-lux-panels/includes/registry.php   the contract
 * @see     apollo-core/src/Core/MetaRegistry.php      where these keys are declared
 * @see     _inventory/PLAN-outnow-and-hostel.md       Track B
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare the Hostel panel.
 *
 * @return void
 */
function apollo_adverts_register_hostel_panel(): void {
	if ( ! function_exists( 'apollo_panel_register' ) ) {
		return;
	}

	apollo_panel_register(
		'hostel',
		array(
			'title'    => __( 'Hostel', 'apollo-adverts' ),
			'subtitle' => __( 'Hospedagem oficial — suas acomodações são as únicas visíveis para quem não tem conta', 'apollo-adverts' ),
			'tabs'     => array(

				'casa' => array(
					'label' => __( 'A casa', 'apollo-adverts' ),
					'icon'  => 'ri-building-line',
					'cards' => array(
						array(
							'label'  => __( 'Identidade', 'apollo-adverts' ),
							'icon'   => 'ri-home-smile-line',
							'fields' => array(
								array(
									'key'   => '_hostel_url',
									'type'  => 'url',
									'label' => __( 'Site do hostel', 'apollo-adverts' ),
									'ph'    => 'https://',
									'hint'  => __( 'Destino do botão "Reservar" nos anúncios ligados a este hostel. Substitui o chat — um hostel é um negócio público, não uma pessoa.', 'apollo-adverts' ),
								),
								array(
									'key'   => '_hostel_contact',
									'type'  => 'text',
									'label' => __( 'Contato de reservas', 'apollo-adverts' ),
									'hint'  => __( 'Linha comercial, NUNCA um número pessoal. Este campo é público por definição.', 'apollo-adverts' ),
								),
							),
						),
						array(
							'label'  => __( 'Endereço', 'apollo-adverts' ),
							'icon'   => 'ri-map-pin-line',
							'fields' => array(
								array(
									'key'   => '_hostel_loc_id',
									'type'  => 'number',
									'label' => __( 'Local relacionado (ID)', 'apollo-adverts' ),
									'hint'  => __( 'Endereço, coordenadas e galeria vivem no CPT `local`, que já os registra. Ligue o hostel a um local em vez de duplicar essas chaves aqui.', 'apollo-adverts' ),
								),
							),
						),
						array(
							'label'  => __( 'Estadia', 'apollo-adverts' ),
							'icon'   => 'ri-time-line',
							'fields' => array(
								array( 'key' => '_hostel_check_in', 'type' => 'text', 'label' => __( 'Check-in', 'apollo-adverts' ), 'grid' => 2, 'ph' => '14:00' ),
								array( 'key' => '_hostel_check_out', 'type' => 'text', 'label' => __( 'Check-out', 'apollo-adverts' ), 'grid' => 2, 'ph' => '11:00' ),
							),
						),
					),
				),
			),
		)
	);
}
add_action( 'init', 'apollo_adverts_register_hostel_panel', 20 );
