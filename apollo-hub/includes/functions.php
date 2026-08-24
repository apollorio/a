<?php

/**
 * Funções auxiliares do Apollo Hub
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retorna o post hub de um usuário pelo username (post slug = username).
 *
 * @param  string $username Username WP (slug do post hub).
 * @return \WP_Post|null
 */
function apollo_hub_get_by_username( string $username ): ?\WP_Post {
	$cached = wp_cache_get( 'hub_user_' . $username, APOLLO_HUB_CACHE_GROUP );
	if ( false !== $cached ) {
		return $cached ?: null;
	}

	$posts = get_posts(
		array(
			'post_type'      => APOLLO_HUB_CPT,
			'name'           => sanitize_title( $username ),
			'posts_per_page' => 1,
			'post_status'    => 'publish',
		)
	);

	$hub = ! empty( $posts ) ? $posts[0] : null;
	wp_cache_set( 'hub_user_' . $username, $hub ?? false, APOLLO_HUB_CACHE_GROUP, APOLLO_HUB_CACHE_TTL );
	return $hub;
}

/**
 * Retorna dados completos do hub (links, sociais, tema, etc.).
 *
 * @param  int $post_id Post ID do hub.
 * @return array{bio:string, blocks:array, links:array, socials:array, theme:string, avatar:int, cover:int, custom_css:string}
 */
function apollo_hub_get_data( int $post_id ): array {
	return array(
		'bio'         => (string) get_post_meta( $post_id, '_hub_bio', true ),
		'blocks'      => apollo_hub_get_blocks( $post_id ),
		'links'       => (array) json_decode( (string) get_post_meta( $post_id, '_hub_links', true ), true ) ?: array(),
		'socials'     => (array) json_decode( (string) get_post_meta( $post_id, '_hub_socials', true ), true ) ?: array(),
		'theme'       => (string) get_post_meta( $post_id, '_hub_theme', true ) ?: 'dark',
		'avatar'      => (int) get_post_meta( $post_id, '_hub_avatar', true ),
		'avatar_type' => (string) get_post_meta( $post_id, '_hub_avatar_type', true ) ?: 'normal',
		'cover'       => (int) get_post_meta( $post_id, '_hub_cover', true ),
		'custom_css'  => (string) get_post_meta( $post_id, '_hub_custom_css', true ),
	);
}

/**
 * Retorna blocos do hub (auto-migrate de _hub_links se necessário).
 *
 * @param  int $post_id Hub post ID.
 * @return array Array de blocos tipados.
 */
function apollo_hub_get_blocks( int $post_id ): array {
	$raw    = get_post_meta( $post_id, '_hub_blocks', true );
	$blocks = is_string( $raw ) ? ( json_decode( $raw, true ) ?: array() ) : ( is_array( $raw ) ? $raw : array() );

	// Auto-migrate from _hub_links if blocks empty but links exist
	if ( empty( $blocks ) ) {
		$links = get_post_meta( $post_id, '_hub_links', true );
		if ( ! empty( $links ) ) {
			\Apollo\Hub\Migration::maybe_migrate( $post_id );
			$raw    = get_post_meta( $post_id, '_hub_blocks', true );
			$blocks = is_string( $raw ) ? ( json_decode( $raw, true ) ?: array() ) : ( is_array( $raw ) ? $raw : array() );
		}
	}

	return $blocks;
}

/**
 * Salva blocos do hub (com sanitização completa por tipo).
 *
 * @param  int   $post_id Hub post ID.
 * @param  array $blocks  Array de blocos tipados.
 * @return bool
 */
function apollo_hub_save_blocks( int $post_id, array $blocks ): bool {
	$allowed_types = array_keys( APOLLO_HUB_BLOCK_TYPES );
	$max_blocks    = 50;

	$blocks = array_slice( $blocks, 0, $max_blocks );

	$clean = array();
	foreach ( $blocks as $block ) {
		// Schema guard: reject malformed entries that are not key/value structures.
		if ( ! is_array( $block ) ) {
			continue;
		}
		$type = sanitize_key( $block['type'] ?? '' );
		if ( ! in_array( $type, $allowed_types, true ) ) {
			continue;
		}

		$clean[] = array(
			'type'   => $type,
			'id'     => sanitize_text_field( $block['id'] ?? wp_generate_uuid4() ),
			'active' => (bool) ( $block['active'] ?? true ),
			'data'   => apollo_hub_sanitize_block_data( $type, (array) ( $block['data'] ?? array() ) ),
		);
	}

	update_post_meta( $post_id, '_hub_blocks', wp_json_encode( $clean ) );
	apollo_hub_bust_cache( $post_id );

	return true;
}

/**
 * Sanitiza dados de um bloco conforme seu tipo.
 *
 * @param  string $type Block type key.
 * @param  array  $data Block data.
 * @return array Sanitized data.
 */
function apollo_hub_sanitize_block_data( string $type, array $data ): array {
	switch ( $type ) {
		case 'header':
			return array( 'text' => sanitize_text_field( $data['text'] ?? '' ) );

		case 'link':
			return array(
				'title'     => sanitize_text_field( $data['title'] ?? '' ),
				'sub'       => sanitize_text_field( $data['sub'] ?? '' ),
				'url'       => esc_url_raw( $data['url'] ?? '' ),
				'icon'      => sanitize_text_field( $data['icon'] ?? 'ri-link-m' ),
				'variant'   => sanitize_key( $data['variant'] ?? 'default' ),
				'bgColor'   => sanitize_hex_color( $data['bgColor'] ?? '' ) ?: '',
				'textColor' => sanitize_hex_color( $data['textColor'] ?? '' ) ?: '',
				'iconBg'    => sanitize_hex_color( $data['iconBg'] ?? '' ) ?: '',
				'badge'     => sanitize_text_field( $data['badge'] ?? '' ),
			);

		case 'social':
			$icons = array();
			foreach ( (array) ( $data['icons'] ?? array() ) as $ic ) {
				$icons[] = array(
					'icon'  => sanitize_text_field( $ic['icon'] ?? 'ri-link-m' ),
					'url'   => esc_url_raw( $ic['url'] ?? '' ),
					'label' => sanitize_text_field( $ic['label'] ?? '' ),
				);
			}
			return array(
				'icons'     => $icons,
				'size'      => sanitize_key( $data['size'] ?? 'md' ),
				'alignment' => sanitize_key( $data['alignment'] ?? 'center' ),
			);

		case 'youtube':
			return array(
				'url'   => esc_url_raw( $data['url'] ?? '' ),
				'title' => sanitize_text_field( $data['title'] ?? 'YouTube' ),
			);

		case 'spotify':
			return array(
				'url'         => esc_url_raw( $data['url'] ?? '' ),
				'spotifyType' => in_array( $data['spotifyType'] ?? '', array( 'track', 'album', 'playlist', 'artist' ), true )
					? $data['spotifyType'] : 'track',
			);

		case 'image':
			return array(
				'url'           => esc_url_raw( $data['url'] ?? '' ),
				'alt'           => sanitize_text_field( $data['alt'] ?? '' ),
				'link'          => esc_url_raw( $data['link'] ?? '' ),
				'fit'           => in_array( $data['fit'] ?? '', array( 'cover', 'contain', 'fill' ), true ) ? $data['fit'] : 'cover',
				'radius'        => sanitize_text_field( $data['radius'] ?? '12px' ),
				'attachment_id' => absint( $data['attachment_id'] ?? 0 ),
			);

		case 'text':
			return array(
				'content' => wp_kses_post( $data['content'] ?? '' ),
				'align'   => in_array( $data['align'] ?? '', array( 'left', 'center', 'right' ), true ) ? $data['align'] : 'left',
			);

		case 'faq':
			$items = array();
			foreach ( (array) ( $data['items'] ?? array() ) as $item ) {
				$items[] = array(
					'question' => sanitize_text_field( $item['question'] ?? '' ),
					'answer'   => wp_kses_post( $item['answer'] ?? '' ),
				);
			}
			return array( 'items' => $items );

		case 'countdown':
			return array(
				'target' => sanitize_text_field( $data['target'] ?? '' ),
				'label'  => sanitize_text_field( $data['label'] ?? '' ),
			);

		case 'map':
			return array(
				'embed'  => esc_url_raw( $data['embed'] ?? '' ),
				'height' => absint( $data['height'] ?? 250 ),
			);

		case 'divider':
			return array(
				'style'  => in_array( $data['style'] ?? '', array( 'line', 'space' ), true ) ? $data['style'] : 'line',
				'height' => min( absint( $data['height'] ?? 24 ), 200 ),
			);

		case 'embed':
			$allowed           = wp_kses_allowed_html( 'post' );
			$allowed['iframe'] = array(
				'src'             => true,
				'width'           => true,
				'height'          => true,
				'frameborder'     => true,
				'allow'           => true,
				'allowfullscreen' => true,
				'loading'         => true,
				'style'           => true,
				'title'           => true,
			);
			return array( 'code' => wp_kses( $data['code'] ?? '', $allowed ) );

		default:
			return array();
	}
}

/**
 * Retorna o hub do usuário logado ou null.
 *
 * @return \WP_Post|null
 */
function apollo_hub_get_current_user_hub(): ?\WP_Post {
	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		return null;
	}
	return apollo_hub_get_by_username( $user->user_login );
}

/**
 * Cria ou retorna o hub do usuário atual (auto-provisioning).
 *
 * @return int|\WP_Error Post ID ou WP_Error.
 */
function apollo_hub_ensure_current_user_hub(): int|\WP_Error {
	$user = wp_get_current_user();
	if ( ! $user->exists() ) {
		return new \WP_Error( 'not_logged_in', __( 'Faça login para gerenciar seu Hub.', 'apollo-hub' ) );
	}

	$existing = apollo_hub_get_by_username( $user->user_login );
	if ( $existing ) {
		return $existing->ID;
	}

	// Cria post hub para o usuário
	$post_id = wp_insert_post(
		array(
			'post_type'   => APOLLO_HUB_CPT,
			'post_title'  => $user->display_name ?: $user->user_login,
			'post_name'   => $user->user_login,
			'post_status' => 'publish',
			'post_author' => $user->ID,
		),
		true
	);

	if ( ! is_wp_error( $post_id ) ) {
		// Inicializa tema padrão
		update_post_meta( $post_id, '_hub_theme', 'dark' );
		wp_cache_delete( 'hub_user_' . $user->user_login, APOLLO_HUB_CACHE_GROUP );
	}

	return $post_id;
}

/**
 * Gera URL de compartilhamento nativa (sem AddToAny).
 *
 * @param  string $url   URL a compartilhar.
 * @param  string $text  Texto/título.
 * @param  string $network Rede: whatsapp|telegram|twitter|facebook.
 * @return string URL de compartilhamento.
 */
function apollo_hub_share_url( string $url, string $text = '', string $network = 'whatsapp' ): string {
	$url  = rawurlencode( $url );
	$text = rawurlencode( $text );

	switch ( $network ) {
		case 'whatsapp':
			return "https://wa.me/?text={$text}%20{$url}";
		case 'telegram':
			return "https://t.me/share/url?url={$url}&text={$text}";
		case 'twitter':
			return "https://x.com/intent/tweet?url={$url}&text={$text}";
		case 'facebook':
			return "https://www.facebook.com/sharer/sharer.php?u={$url}";
		default:
			return rawurldecode( $url );
	}
}

/**
 * Gera URLs de compartilhamento para um evento Apollo.
 *
 * @param  int $event_id Post ID do evento.
 * @return array<string,string> Array de network => share_url.
 */
function apollo_hub_event_share_urls( int $event_id ): array {
	$url   = get_permalink( $event_id );
	$title = get_the_title( $event_id );

	if ( ! $url ) {
		return array();
	}

	return array(
		'whatsapp' => apollo_hub_share_url( $url, $title, 'whatsapp' ),
		'telegram' => apollo_hub_share_url( $url, $title, 'telegram' ),
		'twitter'  => apollo_hub_share_url( $url, $title, 'twitter' ),
		'facebook' => apollo_hub_share_url( $url, $title, 'facebook' ),
	);
}

/**
 * Limpa cache de um hub pelo post ID.
 *
 * @param  int $post_id Post ID.
 */
function apollo_hub_bust_cache( int $post_id ): void {
	$post = get_post( $post_id );
	if ( $post && APOLLO_HUB_CPT === $post->post_type ) {
		wp_cache_delete( 'hub_user_' . $post->post_name, APOLLO_HUB_CACHE_GROUP );
		wp_cache_delete( 'hub_data_' . $post_id, APOLLO_HUB_CACHE_GROUP );
	}
}
add_action( 'save_post_' . APOLLO_HUB_CPT, 'apollo_hub_bust_cache' );
