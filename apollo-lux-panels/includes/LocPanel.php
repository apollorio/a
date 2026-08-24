<?php
/**
 * LocPanel — luxury data panel for the `local` CPT (/loc/{id}).
 *
 * @package Apollo\LuxPanels
 */

declare(strict_types=1);

namespace Apollo\LuxPanels;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LocPanel extends Panel {

	public function post_type(): string { return 'local'; }

	public function schema(): array {
		return array(
			'title'    => 'Ficha do Local',
			'subtitle' => 'Identidade, endereço, contato e estrutura do espaço.',
			'tabs'     => array(
				'identity' => array(
					'label' => 'Identidade', 'icon' => 'ri-building-line',
					'cards' => array(
						array(
							'label' => 'Identidade', 'icon' => 'ri-building-line',
							'fields' => array(
								array( 'key' => '_local_name', 'type' => 'text', 'label' => 'Nome do Local', 'ph' => 'Ex: D-Edge' ),
								array( 'key' => '_local_description', 'type' => 'textarea', 'label' => 'Descrição (exibida no perfil)', 'ph' => 'Descreva o espaço em detalhes...' ),
								array( 'key' => '_local_capacity', 'type' => 'number', 'label' => 'Capacidade (pessoas)', 'grid' => 2, 'ph' => '800' ),

								/* ── PHASE 2.5 · 2026-08-11 — inputs for the three keys the
								   local single-page mockup prints and nothing could write. ── */
								array(
									'key' => '_local_tagline', 'type' => 'text',
									'label' => 'Tagline editorial',
									'ph'    => 'Templo do Techno',
									'hint'  => 'Metade editorial do kicker do hero. A outra metade (cidade · bairro) é derivada.',
								),
								array(
									'key' => '_local_founded_year', 'type' => 'number', 'grid' => 2,
									'label' => 'Ano de fundação',
									'ph'    => '2003',
									'hint'  => 'Os "23+ anos de pista" saem daqui por cálculo. Guardar o ano, não o intervalo — senão o número fica errado todo 1º de janeiro.',
								),
								array(
									'key' => '_local_rooms', 'type' => 'repeater',
									'label' => 'Ambientes',
									'hint'  => 'Nomes dos ambientes (pista, lounge, rooftop). A contagem é derivada. NÃO é capacidade — a Apollo não verifica nem exibe lotação.',
									// `cols`, não `fields` — mesma correção de
									// _dj_name_lines: a forma anterior deixava $cols nulo
									// e derrubava a tela de edição de Local com
									// count(null) no PHP 8 (corrigido 2026-08-17).
									'opts'  => array( 'cols' => array(
										array( 'name' => 'name', 'ph' => 'Pista principal' ),
									) ),
								),
								array( 'key' => '_local_price_range', 'type' => 'select', 'label' => 'Faixa de preço', 'grid' => 2, 'opts' => array( '' => '—', '$' => '$ Econômico', '$$' => '$$ Moderado', '$$$' => '$$$ Caro', '$$$$' => '$$$$ Luxo' ) ),
							),
						),
					),
				),
				'address' => array(
					'label' => 'Endereço', 'icon' => 'ri-map-pin-line',
					'cards' => array(
						array(
							'label' => 'Endereço', 'icon' => 'ri-map-pin-line',
							'fields' => array(
								array( 'key' => '_local_address', 'type' => 'text', 'label' => 'Endereço', 'ph' => 'Av. ... , nº' ),
								array( 'key' => '_local_city', 'type' => 'text', 'label' => 'Cidade', 'grid' => 3, 'ph' => 'Rio de Janeiro' ),
								array( 'key' => '_local_state', 'type' => 'text', 'label' => 'Estado', 'grid' => 3, 'ph' => 'RJ' ),
								array( 'key' => '_local_postal', 'type' => 'text', 'label' => 'CEP', 'grid' => 3, 'ph' => '20000-000' ),
								array( 'key' => '_local_country', 'type' => 'text', 'label' => 'País', 'grid' => 3, 'ph' => 'Brasil' ),
								array( 'key' => '_local_lat', 'type' => 'text', 'label' => 'Latitude', 'grid' => 3, 'ph' => '-22.9068' ),
								array( 'key' => '_local_lng', 'type' => 'text', 'label' => 'Longitude', 'grid' => 3, 'ph' => '-43.1729' ),
							),
						),
						array(
							'label' => 'Mapa', 'icon' => 'ri-road-map-line',
							'fields' => array(
								array( 'type' => 'map', 'label' => 'Mapa do Local', 'opts' => array( 'lat' => '_local_lat', 'lng' => '_local_lng' ) ),
							),
						),
					),
				),
				'contact' => array(
					'label' => 'Contato', 'icon' => 'ri-phone-line',
					'cards' => array(
						array(
							'label' => 'Contato & Redes', 'icon' => 'ri-phone-line',
							'fields' => array(
								array( 'key' => '_local_phone', 'type' => 'text', 'label' => 'Telefone', 'grid' => 2, 'ph' => '(21) ...' ),
								array( 'key' => '_local_whatsapp', 'type' => 'url', 'label' => 'WhatsApp (link)', 'grid' => 2, 'ph' => 'https://wa.me/...' ),
								array( 'key' => '_local_website', 'type' => 'url', 'label' => 'Website', 'grid' => 2, 'ph' => 'https://...' ),
								array( 'key' => '_local_instagram', 'type' => 'url', 'label' => 'Instagram', 'grid' => 2, 'ph' => 'https://instagram.com/...' ),
								array( 'key' => '_local_facebook', 'type' => 'url', 'label' => 'Facebook', 'grid' => 2, 'ph' => 'https://facebook.com/...' ),
							),
						),
					),
				),
				'structure' => array(
					'label' => 'Estrutura', 'icon' => 'ri-service-line',
					'cards' => array(
						array(
							'label' => 'Horários', 'icon' => 'ri-time-line',
							'fields' => array(
								array( 'key' => '_local_hours', 'type' => 'hours', 'label' => 'Horário de funcionamento' ),
							),
						),
						array(
							'label' => 'Estrutura & Comodidades', 'icon' => 'ri-service-line',
							'fields' => array(
								array(
									'key' => '_local_amenities', 'type' => 'repeater', 'label' => 'Comodidades', 'hint' => 'ícone · nome · descrição',
									'opts' => array(
										'add'  => 'Adicionar comodidade',
										'cols' => array(
											array( 'name' => 'icon', 'ph' => 'ri-speaker-line', 'type' => 'text' ),
											array( 'name' => 'name', 'ph' => 'Nome', 'type' => 'text' ),
											array( 'name' => 'sub', 'ph' => 'Descrição curta', 'type' => 'text' ),
										),
									),
								),
							),
						),
					),
				),
			),
		);
	}
}
