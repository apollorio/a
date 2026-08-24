<?php
/**
 * EventPanel — luxury data panel for the `event` CPT (/evento/{id}).
 *
 * @package Apollo\LuxPanels
 */

declare(strict_types=1);

namespace Apollo\LuxPanels;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EventPanel extends Panel {

	public function post_type(): string { return 'event'; }

	public function schema(): array {
		return array(
			'title'    => 'Gerenciador de Evento',
			'subtitle' => 'Preencha os dados para publicar um evento na Apollo.',
			'tabs'     => array(
				'basic' => array(
					'label' => 'Básico', 'icon' => 'ri-information-line',
					'cards' => array(
						array(
							'label' => 'Data e Horário', 'icon' => 'ri-calendar-event-line',
							'fields' => array(
								array( 'key' => '_event_start_date', 'type' => 'date', 'label' => 'Data início', 'grid' => 2 ),
								array( 'key' => '_event_start_time', 'type' => 'time', 'label' => 'Hora início', 'grid' => 2 ),
								array( 'key' => '_event_end_date', 'type' => 'date', 'label' => 'Data fim', 'grid' => 2 ),
								array( 'key' => '_event_end_time', 'type' => 'time', 'label' => 'Hora fim', 'grid' => 2 ),
							),
						),
						array(
							'label' => 'Sobre o Evento', 'icon' => 'ri-file-text-line',
							'fields' => array(
								array( 'key' => '_post_content', 'type' => 'wysiwyg', 'label' => 'Descrição', 'ph' => 'Descreva a vibe, regras e expectativas...' ),
							),
						),
						array(
							'label' => 'Colaboradores', 'icon' => 'ri-team-line',
							'fields' => array(
								array(
									'key'  => '_coauthors',
									'type' => 'user_multiselect',
									'label' => 'Co-Autores',
									'hint' => 'seleção múltipla · todos os usuários',
									'ph'   => 'Nome, login ou e-mail…',
								),
							),
						),
					),
				),
				'media' => array(
					'label' => 'Mídia', 'icon' => 'ri-image-line',
					'cards' => array(
						array(
							'label' => 'Mídia e Links', 'icon' => 'ri-image-line',
							'fields' => array(
								array( 'key' => '_event_banner', 'type' => 'media_single', 'label' => 'Imagem de Capa', 'ph' => 'Clique para anexar a capa do evento' ),
								array( 'key' => '_event_bg_color', 'type' => 'color', 'label' => 'Cor de Fundo', 'hint' => 'fallback — capa e vídeo têm prioridade', 'ph' => '#0a0a0a' ),
								array( 'key' => '_event_video_url', 'type' => 'url', 'label' => 'URL do Vídeo (YouTube / .mp4 .webm .mov)', 'ph' => 'YouTube ou https://…/video.mp4|.webm|.mov', 'grid' => 2 ),
								array( 'key' => '_event_audio_url', 'type' => 'url', 'label' => 'Playlist Spotify / SoundCloud', 'ph' => 'https://open.spotify.com/playlist/...', 'grid' => 2 ),
								array( 'key' => '_event_gallery', 'type' => 'media_gallery', 'label' => 'Galeria de Fotos', 'hint' => '(até 3 imagens)', 'opts' => array( 'max' => 3 ) ),
							),
						),
					),
				),
				'tickets' => array(
					'label' => 'Ingressos', 'icon' => 'ri-price-tag-3-line',
					'cards' => array(
						array(
							'label' => 'Taxonomia e Status', 'icon' => 'ri-price-tag-3-line',
							'fields' => array(
								array( 'key' => '_event_ticket_status', 'type' => 'select', 'label' => 'Status dos Ingressos', 'grid' => 3, 'opts' => array( 'free' => 'Gratuito', 'available' => 'Disponível', 'soldout_soon' => 'Sold-out soon', 'sold_out' => 'Sold-out' ) ),
								array( 'key' => '_event_status', 'type' => 'select', 'label' => 'Status do Evento', 'grid' => 3, 'opts' => array( 'scheduled' => 'Agendado', 'ongoing' => 'Save the date', 'finished' => 'Finalizado', 'cancelled' => 'Cancelado', 'postponed' => 'Adiado' ) ),
								array( 'key' => '_event_privacy', 'type' => 'select', 'label' => 'Privacidade', 'grid' => 3, 'opts' => array( 'public' => 'Público', 'private' => 'Privado', 'invite' => 'Convidados' ) ),
								array( 'key' => '_event_ticket_price', 'type' => 'text', 'label' => 'Preço do Ingresso', 'ph' => 'R$ 50,00', 'grid' => 2 ),
								array( 'key' => '_event_coupon_code', 'type' => 'text', 'label' => 'Código de Cupom', 'ph' => 'APOLLO20', 'grid' => 2 ),
								array( 'key' => '_event_ticket_url', 'type' => 'url', 'label' => 'URL dos Ingressos', 'ph' => 'https://ingressos.com/meu-evento', 'grid' => 2 ),
								array( 'key' => '_event_list_url', 'type' => 'url', 'label' => 'URL da Lista Amiga', 'ph' => 'https://... (Lista Amiga)', 'grid' => 2 ),
								array( 'key' => '_event_ticket_btn_style', 'type' => 'select', 'label' => 'Estilo botão Ingresso', 'grid' => 2, 'opts' => array( 'main' => 'Principal', 'soft' => 'Suave' ) ),
								array( 'key' => '_event_list_btn_style', 'type' => 'select', 'label' => 'Estilo botão Lista', 'grid' => 2, 'opts' => array( 'lista' => 'Lista', 'fem' => 'Lista Fem' ) ),
							),
						),
						array(
							'label' => 'Early Bird e Listas', 'icon' => 'ri-vip-crown-2-line',
							'fields' => array(
								array(
									'key'  => '_event_access_buttons',
									'type' => 'repeater',
									'label' => 'Tickets e Listas',
									'hint' => 'mesmos botões do formulário front-end — linhas sem texto são descartadas',
									'opts' => array(
										'add'  => 'Novo Ticket ou Lista',
										'cols' => array(
											array( 'name' => 'kind', 'type' => 'select', 'opts' => array( 'ticket' => '🎟 Ingresso', 'lista' => '📋 Lista' ) ),
											array( 'name' => 'style', 'type' => 'select', 'opts' => array( 'soft' => 'Early Bird (suave)', 'main' => 'Principal', 'lista' => 'Lista', 'fem' => 'Lista Fem', 'cta' => 'CTA Lista (largo)' ) ),
											array( 'name' => 'label', 'ph' => 'Texto do botão' ),
											array( 'name' => 'sub', 'ph' => 'Condição (opcional)' ),
											array( 'name' => 'url', 'type' => 'url', 'ph' => 'https://...' ),
										),
									),
								),
							),
						),
						array(
							'label' => 'Sons / Gêneros', 'icon' => 'ri-music-2-line',
							'fields' => array(
								array( 'type' => 'taxonomy', 'label' => 'Sons / Gêneros (múltipla)', 'opts' => array( 'taxonomy' => 'sound', 'multiple' => true ) ),
								array( 'type' => 'taxonomy', 'label' => 'Temporada', 'opts' => array( 'taxonomy' => 'season', 'multiple' => false ) ),
							),
						),
					),
				),
				'venue' => array(
					'label' => 'Local', 'icon' => 'ri-map-pin-line',
					'cards' => array(
						array(
							'label' => 'Local e Endereço', 'icon' => 'ri-map-pin-line',
							'fields' => array(
								array( 'key' => '_event_loc_id', 'type' => 'number', 'label' => 'ID do Local (loc CPT)', 'ph' => 'Ex: 128' ),
							),
						),
					),
				),
				'lineup' => array(
					'label' => 'Line-up', 'icon' => 'ri-disc-line',
					'cards' => array(
						array(
							'label' => 'Line-up de DJs', 'icon' => 'ri-disc-line',
							'fields' => array(
								array( 'type' => 'lineup', 'label' => 'Line-up' ),
							),
						),
					),
				),
			),
		);
	}
}
