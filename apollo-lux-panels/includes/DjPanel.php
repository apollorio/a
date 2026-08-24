<?php
/**
 * DjPanel — luxury data panel for the `dj` CPT (/dj/{id}).
 *
 * @package Apollo\LuxPanels
 */

declare(strict_types=1);

namespace Apollo\LuxPanels;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DjPanel extends Panel {

	public function post_type(): string { return 'dj'; }

	public function schema(): array {
		return array(
			'title'    => 'Cartão de Artista',
			'subtitle' => 'Perfil, sonoridades e booking do DJ.',
			'tabs'     => array(
				'profile' => array(
					'label' => 'Perfil', 'icon' => 'ri-user-star-line',
					'cards' => array(
						array(
							'label' => 'Identidade', 'icon' => 'ri-user-star-line',
							'fields' => array(
								array( 'key' => '_dj_name', 'type' => 'text', 'label' => 'Nome Artístico', 'hint' => '(se vazio, usa o título do post)' ),
								array( 'key' => '_dj_bio_short', 'type' => 'textarea', 'label' => 'Biografia Curta', 'hint' => '(máx. 280)', 'ph' => 'Uma linha que define o artista...' ),
								array( 'key' => '_dj_statement', 'type' => 'textarea', 'label' => 'Statement (seção word-fill)', 'ph' => 'Frase editorial pinada no scroll…' ),
								array( 'key' => '_dj_bio', 'type' => 'textarea', 'label' => 'Biografia Completa (Sobre)', 'ph' => 'História, residências, estilo...' ),
								array( 'key' => '_dj_original_project_1', 'type' => 'text', 'label' => 'Projeto original 1', 'grid' => 3 ),
								array( 'key' => '_dj_original_project_2', 'type' => 'text', 'label' => 'Projeto original 2', 'grid' => 3 ),
								array( 'key' => '_dj_original_project_3', 'type' => 'text', 'label' => 'Projeto original 3', 'grid' => 3 ),
							),
						),
						array(
							'label' => 'Mídia', 'icon' => 'ri-image-line',
							'fields' => array(
								array( 'key' => '_dj_image', 'type' => 'media_single', 'label' => 'Foto de Perfil / Hero', 'grid' => 2, 'ph' => 'Anexar foto principal' ),
								array( 'key' => '_dj_banner', 'type' => 'media_single', 'label' => 'Banner', 'grid' => 2, 'ph' => 'Anexar banner' ),
								array( 'key' => '_dj_about_photo', 'type' => 'media_single', 'label' => 'Foto "Sobre" (≠ hero)', 'grid' => 2, 'ph' => 'Anexar foto dos bastidores' ),
								array( 'key' => '_dj_about_video', 'type' => 'url', 'label' => 'Vídeo "Sobre" (opcional)', 'grid' => 2, 'ph' => 'https://... (mp4/webm)' ),
								array( 'key' => '_dj_gallery', 'type' => 'media_gallery', 'label' => 'Galeria', 'hint' => '(até 6)', 'opts' => array( 'max' => 6 ) ),
							),
						),
						array(
							'label' => 'Gêneros', 'icon' => 'ri-music-2-line',
							'fields' => array(
								array( 'type' => 'taxonomy', 'label' => 'Sonoridades (múltipla)', 'opts' => array( 'taxonomy' => 'sound', 'multiple' => true ) ),
							),
						),
					),
				),
				'links' => array(
					'label' => 'Links', 'icon' => 'ri-links-line',
					'cards' => array(
						array(
							'label' => 'Plataformas de Música', 'icon' => 'ri-play-circle-line',
							'fields' => array(
								array( 'key' => '_dj_soundcloud', 'type' => 'url', 'label' => 'SoundCloud', 'grid' => 2, 'ph' => 'https://soundcloud.com/...' ),
								array( 'key' => '_dj_spotify', 'type' => 'url', 'label' => 'Spotify', 'grid' => 2, 'ph' => 'https://open.spotify.com/...' ),
								array( 'key' => '_dj_bandcamp', 'type' => 'url', 'label' => 'Bandcamp', 'grid' => 2, 'ph' => 'https://artista.bandcamp.com' ),
								array( 'key' => '_dj_beatport', 'type' => 'url', 'label' => 'Beatport', 'grid' => 2, 'ph' => 'https://beatport.com/artist/...' ),
								array( 'key' => '_dj_mixcloud', 'type' => 'url', 'label' => 'Mixcloud', 'grid' => 2, 'ph' => 'https://mixcloud.com/...' ),
								array( 'key' => '_dj_youtube', 'type' => 'url', 'label' => 'YouTube', 'grid' => 2, 'ph' => 'https://youtube.com/...' ),
								array( 'key' => '_dj_set_url', 'type' => 'url', 'label' => 'DJ Set em destaque', 'grid' => 2, 'ph' => 'https://...' ),
								array( 'key' => '_dj_mix_url', 'type' => 'url', 'label' => 'Mix em destaque', 'grid' => 2, 'ph' => 'https://...' ),
							),
						),
						array(
							'label' => 'Redes Sociais', 'icon' => 'ri-global-line',
							'fields' => array(
								array( 'key' => '_dj_website', 'type' => 'url', 'label' => 'Website', 'grid' => 2, 'ph' => 'https://...' ),
								array( 'key' => '_dj_instagram', 'type' => 'text', 'label' => 'Instagram (@handle)', 'grid' => 2, 'ph' => '@djname' ),
								array( 'key' => '_dj_facebook', 'type' => 'url', 'label' => 'Facebook', 'grid' => 2, 'ph' => 'https://facebook.com/...' ),
								array( 'key' => '_dj_twitter', 'type' => 'url', 'label' => 'X / Twitter', 'grid' => 2, 'ph' => 'https://x.com/...' ),
								array( 'key' => '_dj_tiktok', 'type' => 'url', 'label' => 'TikTok', 'grid' => 2, 'ph' => 'https://tiktok.com/@...' ),
								array( 'key' => '_dj_resident_advisor', 'type' => 'url', 'label' => 'Resident Advisor', 'grid' => 2, 'ph' => 'https://ra.co/dj/...' ),
							),
						),
					),
				),
				'sounds' => array(
					'label' => 'Sonoridades', 'icon' => 'ri-disc-line',
					'cards' => array(
						array(
							'label' => 'Faixas', 'icon' => 'ri-disc-line',
							'fields' => array(
								array(
									'key' => '_dj_tracks', 'type' => 'repeater_card', 'label' => 'Faixas (Out Now)',
									'hint' => 'título, artistas e duração obrigatórios + ao menos 1 link',
									'opts' => array(
										'add'               => 'Adicionar faixa',
										'sanitize_callback' => 'apollo_dj_sanitize_tracks_meta',
										'fields'            => array(
											array( 'name' => 'title', 'label' => 'Título *', 'type' => 'text', 'ph' => 'Nome da faixa' ),
											array( 'name' => 'artists', 'label' => 'Artistas *', 'type' => 'text', 'ph' => 'Feat. / créditos' ),
											array( 'name' => 'duration', 'label' => 'Duração *', 'type' => 'text', 'ph' => '3:42' ),
											array( 'name' => 'release_date', 'label' => 'Lançamento', 'type' => 'date' ),
											array( 'name' => 'bpm', 'label' => 'BPM', 'type' => 'number', 'ph' => '128' ),
											array( 'name' => 'genre', 'label' => 'Gênero', 'type' => 'text' ),
											array( 'name' => 'album', 'label' => 'Álbum', 'type' => 'text' ),
											array( 'name' => 'label', 'label' => 'Gravadora', 'type' => 'text' ),
											array( 'name' => 'cover_id', 'label' => 'Capa', 'type' => 'media_single', 'ph' => 'Anexar capa' ),
											array( 'name' => 'cover_url', 'label' => 'Capa (URL externa)', 'type' => 'url', 'ph' => 'https://...' ),
											array( 'name' => 'url_soundcloud', 'label' => 'SoundCloud', 'type' => 'url' ),
											array( 'name' => 'url_spotify', 'label' => 'Spotify', 'type' => 'url' ),
											array( 'name' => 'url_bandcamp', 'label' => 'Bandcamp', 'type' => 'url' ),
											array( 'name' => 'url_download', 'label' => 'Download direto', 'type' => 'url' ),
										),
									),
								),
							),
						),
					),
				),
				'booking' => array(
					'label' => 'Booking', 'icon' => 'ri-mail-send-line',
					'cards' => array(
						array(
							'label' => 'Booking & EPK', 'icon' => 'ri-mail-send-line',
							'fields' => array(
								array( 'key' => '_dj_booking', 'type' => 'email', 'label' => 'E-mail de Booking', 'grid' => 2, 'ph' => 'booking@...' ),
								array( 'key' => '_dj_media_kit_url', 'type' => 'url', 'label' => 'Media Kit (Drive)', 'grid' => 2, 'ph' => 'https://drive.google.com/...' ),
								array( 'key' => '_dj_rider_url', 'type' => 'url', 'label' => 'Rider Técnico', 'grid' => 2, 'ph' => 'https://...' ),
								array( 'key' => '_dj_user_id', 'type' => 'number', 'label' => 'User ID vinculado', 'grid' => 2, 'ph' => 'ID WP' ),
								array( 'key' => '_dj_verified', 'type' => 'toggle', 'label' => 'DJ Verificado' ),

								/* ── PHASE 2.5 · 2026-08-11 — inputs for the six keys the
								   dj single-page mockup prints and nothing could write.
								   Audit: _inventory/registry/21-mockup-field-contract.json ── */
								array(
									'key' => '_dj_home_city', 'type' => 'text', 'grid' => 2,
									'label' => 'Cidade base',
									'ph'    => 'Rio de Janeiro',
									'hint'  => 'Aparece no eyebrow do hero. NÃO é derivável do histórico de shows — um artista carioca que toca mais em SP apareceria como paulista.',
								),
								array(
									'key' => '_dj_booking_status', 'type' => 'select', 'grid' => 2,
									'label' => 'Status de booking',
									'hint'  => 'É o ponto vivo do hero. Diferente de "Contato booking", que é o e-mail.',
									// `opts` É o mapa value => label, sem invólucro.
									// render_field() itera opts direto; com 'choices'
									// aninhado saía uma única <option value="choices">
									// tendo um array como rótulo (corrigido 2026-08-17).
									'opts'  => array(
										''          => '—',
										'open'      => 'Booking aberto',
										'selective' => 'Booking seletivo',
										'closed'    => 'Agenda fechada',
									),
								),
								array(
									'key' => '_dj_media_kit_stats', 'type' => 'repeater_card',
									'label' => 'Kit — números (máx. 4)',
									'hint'  => 'Ex.: "28 MB" · "Arquivo zip". O Drive não pode ser inspecionado daqui, então esses valores são declarados, não derivados.',
									'opts'  => array(
										'max'    => 4,
										// Sub-campos de repeater_card usam `name`, não
										// `key` — render_repeater_card_row() lê
										// $sf['name'] (Panel.php). Com `key`, todo input
										// saía nomeado `_dj_media_kit_stats[][]` e
										// save_repeater_card() gravava sempre um array
										// vazio: o campo renderizava e NUNCA persistia
										// (corrigido 2026-08-17).
										'fields' => array(
											array( 'name' => 'value', 'type' => 'text', 'label' => 'Valor', 'ph' => '28 MB' ),
											array( 'name' => 'label', 'type' => 'text', 'label' => 'Legenda', 'ph' => 'Arquivo zip' ),
										),
									),
								),

								/* ── Overrides — deixe vazio e o sistema deriva sozinho ── */
								array(
									'key' => '_dj_eyebrow', 'type' => 'text',
									'label' => 'Eyebrow (override)',
									'hint'  => 'VAZIO = derivado de Cidade base + as 2 primeiras sonoridades. Preencha só para forçar outro texto.',
								),
								array(
									'key' => '_dj_name_lines', 'type' => 'repeater',
									'label' => 'Quebra do nome no hero (override)',
									'hint'  => 'VAZIO = divide o nome no primeiro espaço. Use quando o nome tiver uma ou três palavras.',
									// `cols`, não `fields`, e cada coluna usa `name`.
									// render_repeater()/save_repeater() leem `cols`; a
									// forma anterior deixava $cols nulo e derrubava a
									// tela de edição do DJ com count(null) no PHP 8
									// (corrigido 2026-08-17).
									'opts'  => array( 'max' => 2, 'cols' => array(
										array( 'name' => 'line', 'ph' => 'Linha do nome' ),
									) ),
								),
								array(
									'key' => '_dj_footer_image', 'type' => 'media_single',
									'label' => 'Foto do rodapé (override)',
									'hint'  => 'VAZIO = usa a capa do evento mais recente em que o artista tocou. Nunca cai em foto de banco de imagens.',
								),
							),
						),
					),
				),
			),
		);
	}
}
