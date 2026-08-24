<?php
/**
 * Apollo DJs — Frontend Editor Field Definitions
 *
 * Registers DJ fields with the shared frontend editing system
 * (apollo-templates/FrontendEditor).
 *
 * Mirrors admin metabox coverage for the DJ business card:
 * hero · main · about · releases (seed) · links · booking · settings
 *
 * URL: /editar/dj/{post_id}/
 *
 * @package Apollo\DJs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'apollo_editable_post_types',
	function ( array $types ): array {
		$types[] = 'dj';
		return $types;
	}
);

add_filter(
	'apollo_editor_config_dj',
	function ( array $config ): array {
		$config['page_title']   = __( 'Editar DJ', 'apollo-djs' );
		$config['hero_enabled'] = true;
		$config['cover_field']  = '_dj_banner';
		$config['avatar_field'] = '_dj_image';
		$config['sections']     = array( 'hero', 'main', 'about', 'releases', 'links', 'booking', 'settings' );

		$config['section_labels'] = array(
			'hero'     => __( 'Destaque', 'apollo-djs' ),
			'main'     => __( 'Informações', 'apollo-djs' ),
			'about'    => __( 'Sobre & mídia', 'apollo-djs' ),
			'releases' => __( 'Out now!', 'apollo-djs' ),
			'links'    => __( 'Links & Redes', 'apollo-djs' ),
			'booking'  => __( 'Booking & Kit Promo', 'apollo-djs' ),
			'settings' => __( 'Configurações', 'apollo-djs' ),
		);

		$config['section_icons'] = array(
			'hero'     => 'ri-disc-line',
			'main'     => 'ri-information-line',
			'about'    => 'ri-user-smile-line',
			'releases' => 'ri-music-2-line',
			'links'    => 'ri-links-line',
			'booking'  => 'ri-mail-send-line',
			'settings' => 'ri-compasses-2-line',
		);

		$config['save_label'] = __( 'Salvar DJ', 'apollo-djs' );

		return $config;
	}
);

add_filter(
	'apollo_frontend_fields_dj',
	function ( array $fields ): array {

		// ─── Main ───────────────────────────────────────────────────────────
		$fields[] = array(
			'name'        => '_dj_name',
			'type'        => 'text',
			'label'       => __( 'Nome artístico', 'apollo-djs' ),
			'icon'        => 'ri-user-star-line',
			'placeholder' => __( 'Se vazio, usa o título do post', 'apollo-djs' ),
			'section'     => 'main',
			'description' => __( 'Hero, ev-top e rodapé do cartão.', 'apollo-djs' ),
		);

		$fields[] = array(
			'name'        => '_dj_bio_short',
			'type'        => 'textarea',
			'label'       => __( 'Bio curta', 'apollo-djs' ),
			'icon'        => 'ri-quill-pen-line',
			'placeholder' => __( 'Escreva uma bio curta sobre o DJ...', 'apollo-djs' ),
			'required'    => false,
			'maxlength'   => 280,
			'section'     => 'main',
			'rows'        => 3,
			'description' => __( 'Hero do cartão. Máximo 280 caracteres.', 'apollo-djs' ),
		);

		$fields[] = array(
			'name'        => '_dj_statement',
			'type'        => 'textarea',
			'label'       => __( 'Statement (word-fill)', 'apollo-djs' ),
			'icon'        => 'ri-double-quotes-l',
			'placeholder' => __( 'Frase editorial pinada no scroll…', 'apollo-djs' ),
			'section'     => 'main',
			'rows'        => 3,
		);

		$fields[] = array(
			'name'        => '_dj_bio',
			'type'        => 'textarea',
			'label'       => __( 'Bio completa', 'apollo-djs' ),
			'icon'        => 'ri-file-text-line',
			'placeholder' => __( 'Escreva a bio completa do DJ...', 'apollo-djs' ),
			'section'     => 'main',
			'rows'        => 8,
			'description' => __( 'Seção "Sobre" do cartão.', 'apollo-djs' ),
		);

		$fields[] = array(
			'name'    => '_dj_original_project_1',
			'type'    => 'text',
			'label'   => __( 'Projeto original 1', 'apollo-djs' ),
			'icon'    => 'ri-star-line',
			'section' => 'main',
		);

		$fields[] = array(
			'name'    => '_dj_original_project_2',
			'type'    => 'text',
			'label'   => __( 'Projeto original 2', 'apollo-djs' ),
			'icon'    => 'ri-star-line',
			'section' => 'main',
		);

		$fields[] = array(
			'name'    => '_dj_original_project_3',
			'type'    => 'text',
			'label'   => __( 'Projeto original 3', 'apollo-djs' ),
			'icon'    => 'ri-star-line',
			'section' => 'main',
		);

		$fields[] = array(
			'name'        => '_dj_sounds',
			'type'        => 'taxonomy',
			'label'       => __( 'Sons / Estilos', 'apollo-djs' ),
			'icon'        => 'ri-music-2-line',
			'section'     => 'main',
			'taxonomy'    => 'sound',
			'tax_format'  => 'checkbox',
			'description' => __( 'Marquee + tags "Sobre" no cartão.', 'apollo-djs' ),
		);

		// ─── About media ────────────────────────────────────────────────────
		$fields[] = array(
			'name'        => '_dj_about_photo',
			'type'        => 'image',
			'label'       => __( 'Foto "Sobre"', 'apollo-djs' ),
			'icon'        => 'ri-image-line',
			'section'     => 'about',
			'description' => __( 'Deve ser diferente do hero. Meta: _dj_about_photo (attachment id).', 'apollo-djs' ),
		);

		$fields[] = array(
			'name'        => '_dj_about_video',
			'type'        => 'url',
			'label'       => __( 'Vídeo "Sobre" (URL mp4/webm)', 'apollo-djs' ),
			'icon'        => 'ri-video-line',
			'placeholder' => 'https://…',
			'section'     => 'about',
			'description' => __( 'Prioridade sobre a foto na seção Sobre.', 'apollo-djs' ),
		);

		// ─── Out now! (seed — full repeater lives in WP admin metabox) ───────
		$fields[] = array(
			'name'        => '_dj_set_url',
			'type'        => 'url',
			'label'       => __( 'DJ Set em destaque (URL)', 'apollo-djs' ),
			'icon'        => 'ri-play-circle-line',
			'placeholder' => 'https://soundcloud.com/…',
			'section'     => 'releases',
			'description' => __( 'Para a lista completa Out now! (título · URL · duração · ano), use o metabox no admin do CPT DJ.', 'apollo-djs' ),
		);

		$fields[] = array(
			'name'        => '_dj_mix_url',
			'type'        => 'url',
			'label'       => __( 'Mix / Playlist URL', 'apollo-djs' ),
			'icon'        => 'ri-play-list-2-line',
			'placeholder' => 'https://…',
			'section'     => 'releases',
		);

		// ─── Links ──────────────────────────────────────────────────────────
		$link_defs = array(
			'_dj_website'          => array( __( 'Website', 'apollo-djs' ), 'ri-global-line', 'https://' ),
			'_dj_instagram'        => array( __( 'Instagram', 'apollo-djs' ), 'ri-instagram-line', 'https://instagram.com/…' ),
			'_dj_soundcloud'       => array( __( 'SoundCloud', 'apollo-djs' ), 'ri-soundcloud-line', 'https://soundcloud.com/…' ),
			'_dj_spotify'          => array( __( 'Spotify', 'apollo-djs' ), 'ri-spotify-line', 'https://open.spotify.com/…' ),
			'_dj_bandcamp'         => array( __( 'Bandcamp', 'apollo-djs' ), 'ri-album-line', 'https://….bandcamp.com' ),
			'_dj_beatport'         => array( __( 'Beatport', 'apollo-djs' ), 'ri-vip-crown-line', 'https://beatport.com/…' ),
			'_dj_youtube'          => array( __( 'YouTube', 'apollo-djs' ), 'ri-youtube-line', 'https://youtube.com/…' ),
			'_dj_mixcloud'         => array( __( 'Mixcloud', 'apollo-djs' ), 'ri-disc-line', 'https://mixcloud.com/…' ),
			'_dj_resident_advisor' => array( __( 'Resident Advisor', 'apollo-djs' ), 'ri-radio-line', 'https://ra.co/dj/…' ),
			'_dj_facebook'         => array( __( 'Facebook', 'apollo-djs' ), 'ri-facebook-circle-line', 'https://facebook.com/…' ),
			'_dj_twitter'          => array( __( 'Twitter / X', 'apollo-djs' ), 'ri-twitter-x-line', 'https://x.com/…' ),
			'_dj_tiktok'           => array( __( 'TikTok', 'apollo-djs' ), 'ri-tiktok-line', 'https://tiktok.com/@…' ),
		);

		foreach ( $link_defs as $meta => $def ) {
			$fields[] = array(
				'name'        => $meta,
				'type'        => 'url',
				'label'       => $def[0],
				'icon'        => $def[1],
				'placeholder' => $def[2],
				'section'     => 'links',
			);
		}

		// ─── Booking & Kit Promo ────────────────────────────────────────────
		$fields[] = array(
			'name'        => '_dj_booking',
			'type'        => 'email',
			'label'       => __( 'E-mail de booking', 'apollo-djs' ),
			'icon'        => 'ri-mail-send-line',
			'placeholder' => 'booking@…',
			'section'     => 'booking',
			'description' => __( 'CTA Contato booking no cartão.', 'apollo-djs' ),
		);

		$fields[] = array(
			'name'        => '_dj_media_kit_url',
			'type'        => 'url',
			'label'       => __( 'Kit Promo (URL Drive)', 'apollo-djs' ),
			'icon'        => 'ri-folder-open-line',
			'placeholder' => 'https://drive.google.com/…',
			'section'     => 'booking',
			'description' => __( 'Botão "Acessar Kit Promo".', 'apollo-djs' ),
		);

		$fields[] = array(
			'name'        => '_dj_rider_url',
			'type'        => 'url',
			'label'       => __( 'Rider técnico', 'apollo-djs' ),
			'icon'        => 'ri-clipboard-fill',
			'placeholder' => 'https://…',
			'section'     => 'booking',
		);

		// ─── Settings ───────────────────────────────────────────────────────
		$fields[] = array(
			'name'        => '_dj_verified',
			'type'        => 'checkbox',
			'label'       => __( 'DJ Verificado', 'apollo-djs' ),
			'icon'        => 'ri-verified-badge-line',
			'section'     => 'settings',
			'readonly'    => true,
			'description' => __( 'Status de verificação (gerenciado pela equipe).', 'apollo-djs' ),
		);

		$fields[] = array(
			'name'    => '_dj_user_id',
			'type'    => 'hidden',
			'section' => 'settings',
		);

		return $fields;
	}
);

add_filter(
	'apollo_editor_can_edit_dj',
	function ( bool $can, int $post_id, int $user_id ): bool {
		if ( $can ) {
			return true;
		}

		$linked = (int) get_post_meta( $post_id, '_dj_user_id', true );
		return $linked === $user_id;
	},
	10,
	3
);
