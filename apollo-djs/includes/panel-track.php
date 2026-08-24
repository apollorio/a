<?php
/**
 * Track — admin editing surface.
 *
 * THE FIRST PANEL DECLARED WITHOUT A SUBCLASS. apollo_panel_register() takes a
 * plain array; there is no class here, no require in apollo-lux-panels, and no
 * edit to that plugin. That is the whole point of the Panel Contract — before
 * it, adding an edit screen meant touching a second plugin, which is why only
 * three of seventeen CPTs ever got one.
 *
 * WHY THIS FILE EXISTS AT ALL
 * ---------------------------
 * `track` shipped on 2026-08-17 with twelve registered meta keys and ZERO
 * inputs. Every field was declared in apollo-core's MetaRegistry, exposed in
 * REST, and unreachable from wp-admin — a DJ or an editor could create a Faixa
 * with a title and nothing else. This closes that.
 *
 * FIELD SHAPE MIRRORS `_dj_tracks` v2, DELIBERATELY. These are the same fields
 * the repeater held, unpacked onto a post. Keeping the names and the semantics
 * identical is what lets apollo_dj_get_tracks() merge CPT rows with legacy meta
 * rows during the migration without a translation layer.
 *
 * @package Apollo\DJs
 * @since   1.0.7
 * @see     apollo-lux-panels/includes/registry.php  the contract
 * @see     apollo-core/src/Core/MetaRegistry.php     where these keys are declared
 * @see     _inventory/PLAN-outnow-and-hostel.md      Track A
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declare the Track panel.
 *
 * @return void
 */
function apollo_dj_register_track_panel(): void {
	if ( ! function_exists( 'apollo_panel_register' ) ) {
		return; // apollo-lux-panels inactive — the CPT still edits with the stock editor.
	}

	apollo_panel_register(
		'track',
		array(
			'title'    => __( 'Faixa', 'apollo-djs' ),
			'subtitle' => __( 'Lançamento — aparece em Out Now, no perfil do DJ e em /tracks', 'apollo-djs' ),
			'tabs'     => array(

				'faixa' => array(
					'label' => __( 'Faixa', 'apollo-djs' ),
					'icon'  => 'ri-disc-line',
					'cards' => array(
						array(
							'label'  => __( 'Identidade', 'apollo-djs' ),
							'icon'   => 'ri-music-2-line',
							'fields' => array(
								array(
									'key'   => '_track_artists',
									'type'  => 'text',
									'label' => __( 'Artistas', 'apollo-djs' ),
									'ph'    => 'Leo Janeiro, Convidado',
									'hint'  => __( 'Obrigatório. Um lançamento sem artista creditado não é um lançamento — foi exatamente isso que o repeater em meta nunca conseguiu representar para B2B e remix.', 'apollo-djs' ),
								),
								array(
									'key'   => '_track_dj_ids',
									'type'  => 'user_multiselect',
									'label' => __( 'DJs creditados', 'apollo-djs' ),
									'hint'  => __( 'Relação com o CPT dj. Espelha _event_dj_ids: uma faixa pode creditar mais de um artista.', 'apollo-djs' ),
									'opts'  => array( 'post_type' => 'dj' ),
								),
								array(
									'key'   => '_track_ghost_artists',
									'type'  => 'text',
									'label' => __( 'Artistas convidados (sem perfil)', 'apollo-djs' ),
									'ph'    => 'Convidado, Remixer',
									'hint'  => __( 'Separados por vírgula. Para quem NÃO tem perfil de DJ na Apollo — um vocalista convidado, um remixer de fora. É crédito, não perfil: nenhum post é criado. Se a pessoa se cadastrar depois com esse mesmo nome, o crédito vira link sozinho.', 'apollo-djs' ),
								),
								array(
									'key'   => '_track_duration',
									'type'  => 'text',
									'label' => __( 'Duração', 'apollo-djs' ),
									'grid'  => 2,
									'ph'    => '6:42',
								),
								array(
									'key'   => '_track_release_date',
									'type'  => 'date',
									'label' => __( 'Data de lançamento', 'apollo-djs' ),
									'grid'  => 2,
									'hint'  => __( 'Chave de ordenação de TODAS as superfícies de faixa, incluindo o "mais recente" da /casa.', 'apollo-djs' ),
								),
							),
						),
						array(
							'label'  => __( 'Ficha técnica', 'apollo-djs' ),
							'icon'   => 'ri-equalizer-line',
							'fields' => array(
								array( 'key' => '_track_bpm', 'type' => 'number', 'label' => __( 'BPM', 'apollo-djs' ), 'grid' => 3 ),
								array( 'key' => '_track_album', 'type' => 'text', 'label' => __( 'Álbum / EP', 'apollo-djs' ), 'grid' => 3 ),
								array( 'key' => '_track_label', 'type' => 'text', 'label' => __( 'Selo', 'apollo-djs' ), 'grid' => 3 ),
								array(
									'key'   => 'sound',
									'type'  => 'taxonomy',
									'label' => __( 'Sonoridades', 'apollo-djs' ),
									'opts'  => array( 'taxonomy' => 'sound', 'multiple' => true ),
									'hint'  => __( 'Gênero é TERMO, não string. A taxonomy `sound` já é o vocabulário que a superfície de DJs filtra — registrar um segundo aqui seria bifurcá-la.', 'apollo-djs' ),
								),
							),
						),
						array(
							'label'  => __( 'Capa', 'apollo-djs' ),
							'icon'   => 'ri-image-line',
							'fields' => array(
								array(
									'key'   => '_track_cover_url',
									'type'  => 'text',
									'label' => __( 'URL da capa (fallback)', 'apollo-djs' ),
									'hint'  => __( 'A imagem destacada do post é a capa canônica. Este campo só carrega valores migrados do v2 que eram URL sem anexo.', 'apollo-djs' ),
								),
							),
						),
					),
				),

				'ouvir' => array(
					'label' => __( 'Ouvir', 'apollo-djs' ),
					'icon'  => 'ri-play-circle-line',
					'cards' => array(
						array(
							'label'  => __( 'Plataformas', 'apollo-djs' ),
							'icon'   => 'ri-links-line',
							'hint'   => __( 'Pelo menos uma é obrigatória no contrato v2 — um lançamento que ninguém consegue ouvir não é um lançamento.', 'apollo-djs' ),
							'fields' => array(
								array( 'key' => '_track_url_soundcloud', 'type' => 'url', 'label' => 'SoundCloud', 'grid' => 2 ),
								array( 'key' => '_track_url_spotify', 'type' => 'url', 'label' => 'Spotify', 'grid' => 2 ),
								array( 'key' => '_track_url_bandcamp', 'type' => 'url', 'label' => 'Bandcamp', 'grid' => 2 ),
								array( 'key' => '_track_url_download', 'type' => 'url', 'label' => __( 'Download direto', 'apollo-djs' ), 'grid' => 2 ),
							),
						),
						array(
							'label'  => __( 'Prévia', 'apollo-djs' ),
							'icon'   => 'ri-volume-up-line',
							'hint'   => __( 'Trecho curto tocado dentro da Apollo. É SEPARADO dos links acima: aqueles são o destino (ouvir a faixa inteira na plataforma do artista), este é a degustação na página. Só aparece para quem tem conta.', 'apollo-djs' ),
							'fields' => array(
								array(
									'key'   => '_track_preview_url',
									'type'  => 'url',
									'label' => __( 'URL da prévia', 'apollo-djs' ),
									'ph'    => 'https://files.catbox.moe/xxxxx.mp3',
									'hint'  => __( 'Catbox.moe (MP3 direto, grátis, sem conta, 200MB) é o melhor caso — toca nativo, sem script de terceiro. Archive.org também serve e é permanente. YouTube funciona como alternativa prática. Qualquer outro link só é aceito se terminar em .mp3/.ogg/.m4a.', 'apollo-djs' ),
								),
								array(
									'key'   => '_track_preview_start',
									'type'  => 'number',
									'label' => __( 'Começar em (s)', 'apollo-djs' ),
									'grid'  => 2,
									'hint'  => __( 'Pule a introdução — comece onde a faixa convence.', 'apollo-djs' ),
								),
								array(
									'key'   => '_track_preview_seconds',
									'type'  => 'number',
									'label' => __( 'Duração da prévia (s)', 'apollo-djs' ),
									'grid'  => 2,
									'hint'  => __( 'Vazio = 30s.', 'apollo-djs' ),
								),
							),
						),
					),
				),
			),
		)
	);
}
add_action( 'init', 'apollo_dj_register_track_panel', 20 );
