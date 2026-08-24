<?php

/**
 * Constantes do Apollo Hub
 *
 * @package Apollo\Hub
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── REST ─────────────────────────────────────────────────────────────────────
define( 'APOLLO_HUB_REST_NAMESPACE', 'apollo/v1' );

// ─── CPT — conforme apollo-registry.json (hub CPT no MASTER_REGISTRY) ────────
define( 'APOLLO_HUB_CPT', 'hub' );

// ─── Rewrite slug ─────────────────────────────────────────────────────────────
define( 'APOLLO_HUB_SLUG', 'hub' );

// ─── Page slugs ───────────────────────────────────────────────────────────────
define( 'APOLLO_HUB_EDIT_SLUG', 'editar-hub' );

// ─── Cache ────────────────────────────────────────────────────────────────────
define( 'APOLLO_HUB_CACHE_GROUP', 'apollo_hub' );
define( 'APOLLO_HUB_CACHE_TTL', 300 );

// ─── Limits — conforme registry _hub_bio max:280 ─────────────────────────────
define( 'APOLLO_HUB_BIO_MAX_LEN', 280 );
define( 'APOLLO_HUB_LINKS_MAX', 30 );

// ─── Meta Keys — conforme apollo-registry.json ───────────────────────────────
define(
	'APOLLO_HUB_META_KEYS',
	array(
		'_hub_bio',
		'_hub_links',
		'_hub_socials',
		'_hub_theme',
		'_hub_avatar',
		'_hub_cover',
		'_hub_custom_css',
	)
);

// ─── Temas disponíveis ────────────────────────────────────────────────────────
define(
	'APOLLO_HUB_THEMES',
	array(
		'dark'     => 'Escuro',
		'light'    => 'Claro',
		'midnight' => 'Noturno',
		'aurora'   => 'Aurora',
		'rio'      => 'Rio',
	)
);

// ─── Tipos de bloco (conforme _library/hub/design apollo/blocks-hub.js) ──────
define(
	'APOLLO_HUB_BLOCK_TYPES',
	array(
		'header'    => array(
			'label' => 'Cabeçalho',
			'icon'  => 'ri-input-method-line',
			'group' => 'layout',
		),
		'link'      => array(
			'label' => 'Link / CTA',
			'icon'  => 'ri-link',
			'group' => 'content',
		),
		'social'    => array(
			'label' => 'Ícones Sociais',
			'icon'  => 'ri-group-line',
			'group' => 'content',
		),
		'youtube'   => array(
			'label' => 'YouTube',
			'icon'  => 'ri-youtube-line',
			'group' => 'media',
		),
		'spotify'   => array(
			'label' => 'Spotify',
			'icon'  => 'ri-spotify-line',
			'group' => 'media',
		),
		'image'     => array(
			'label' => 'Imagem',
			'icon'  => 'ri-image-line',
			'group' => 'media',
		),
		'text'      => array(
			'label' => 'Texto',
			'icon'  => 'ri-file-text-line',
			'group' => 'content',
		),
		'faq'       => array(
			'label' => 'FAQ / Accordion',
			'icon'  => 'ri-question-answer-line',
			'group' => 'content',
		),
		'countdown' => array(
			'label' => 'Countdown',
			'icon'  => 'ri-timer-line',
			'group' => 'content',
		),
		'map'       => array(
			'label' => 'Mapa',
			'icon'  => 'ri-map-pin-line',
			'group' => 'media',
		),
		'divider'   => array(
			'label' => 'Divisor',
			'icon'  => 'ri-separator',
			'group' => 'layout',
		),
		'embed'     => array(
			'label' => 'Embed / HTML',
			'icon'  => 'ri-code-s-slash-line',
			'group' => 'advanced',
		),
	)
);

// ─── Ícones de rede social permitidos (RemixIcon) ────────────────────────────
define(
	'APOLLO_HUB_SOCIAL_ICONS',
	array(
		'instagram'  => 'ri-instagram-line',
		'twitter'    => 'ri-twitter-x-line',
		'tiktok'     => 'ri-tiktok-line',
		'youtube'    => 'ri-youtube-line',
		'spotify'    => 'ri-spotify-line',
		'soundcloud' => 'ri-soundcloud-line',
		'facebook'   => 'ri-facebook-line',
		'twitch'     => 'ri-twitch-line',
		'linkedin'   => 'ri-linkedin-line',
		'github'     => 'ri-github-line',
		'website'    => 'ri-global-line',
		'whatsapp'   => 'ri-whatsapp-line',
		'telegram'   => 'ri-telegram-line',
		'email'      => 'ri-mail-line',
	)
);
