<?php
/**
 * Apollo Ecosystem — Central registry definitions.
 *
 * Membership badge + access slug SSOT. Consumed by MembershipRegistry.
 *
 * @package Apollo\Core
 * @since   6.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'membership' => array(
		'storage_version' => 2,

		/**
		 * Profile badges — index 0 in _apollo_membership array.
		 * Shown after display name in profile, feed, chat, etc.
		 */
		'profile_badges' => array(
			'nao-verificado' => array(
				'label'     => 'Não Verificado',
				'icon'      => 'ri-user-line',
				'color'     => '#999999',
				'html_icon' => '<i class="ri-user-line" title="Não Verificado"></i>',
				'hidden'    => true,
			),
			'verificado'     => array(
				'label'     => 'Verificado',
				'icon'      => 'ri-shield-check-fill',
				'color'     => '#4caf50',
				'html_icon' => '<i class="ri-shield-check-fill" title="Verificado"></i>',
			),
			'dj'             => array(
				'label'     => 'DJ',
				'icon'      => 'ri-disc-fill',
				'color'     => '#e91e63',
				'html_icon' => '<i class="ri-disc-fill" title="DJ"></i>',
			),
			'producer'       => array(
				'label'     => 'Producer',
				'icon'      => 'ri-sound-module-fill',
				'color'     => '#9c27b0',
				'html_icon' => '<i class="ri-sound-module-fill" title="Producer"></i>',
			),
			'music-prod'     => array(
				'label'     => 'Music Prod',
				'icon'      => 'ri-equalizer-fill',
				'color'     => '#673ab7',
				'html_icon' => '<i class="ri-equalizer-fill" title="Music Prod"></i>',
			),
			'visual-artist'  => array(
				'label'     => 'Visual Artist',
				'icon'      => 'ri-palette-fill',
				'color'     => '#ff9800',
				'html_icon' => '<i class="ri-palette-fill" title="Visual Artist"></i>',
			),
			'videomaker'     => array(
				'label'     => 'Videomaker',
				'icon'      => 'ri-film-fill',
				'color'     => '#f44336',
				'html_icon' => '<i class="ri-film-fill" title="Videomaker"></i>',
			),
			'designer'       => array(
				'label'     => 'Designer',
				'icon'      => 'ri-layout-masonry-fill',
				'color'     => '#00bcd4',
				'html_icon' => '<i class="ri-layout-masonry-fill" title="Designer"></i>',
			),
			'marketing'      => array(
				'label'     => 'Marketing',
				'icon'      => 'ri-megaphone-fill',
				'color'     => '#ff5722',
				'html_icon' => '<i class="ri-megaphone-fill" title="Marketing"></i>',
			),
			'governmt'       => array(
				'label'     => 'Government',
				'icon'      => 'ri-government-fill',
				'color'     => '#607d8b',
				'html_icon' => '<i class="ri-government-fill" title="Government"></i>',
			),
			'apollo'         => array(
				'label'     => 'Apollo',
				'icon'      => 'i-apollo-fill',
				'color'     => '#ffd700',
				'html_icon' => '<i class="i-apollo-fill icon-apollo-s" title="Apollo team" data-apollo-icon="apollo-s" style="--apollo-mask: url(&quot;https://assets.apollo.rio.br/i/apollo-s.svg&quot;) !important;"></i>',
			),
			'mod'            => array(
				'label'     => 'Moderator',
				'icon'      => 'ri-shield-star-fill',
				'color'     => '#2196f3',
				'html_icon' => '<i class="ri-shield-star-fill" title="Moderador"></i>',
			),
			'suspect'        => array(
				'label'     => 'Reportado',
				'icon'      => 'ri-alert-fill',
				'color'     => '#b71c1c',
				'html_icon' => '<i class="ri-alert-fill" title="Reportado — aguardando verificação"></i>',
			),
			'photographer'   => array(
				'label'     => 'Photographer',
				'icon'      => 'ri-camera-fill',
				'color'     => '#795548',
				'html_icon' => '<i class="ri-camera-fill" title="Photographer"></i>',
			),
			'cenario'        => array(
				'label'     => 'Cena::Rio',
				'icon'      => 'ri-disc-line',
				'color'     => '#00e5ff',
				'html_icon' => '<i class="ri-disc-line" title="Cena::Rio — Indústria"></i>',
			),
			'host'           => array(
				'label'      => 'Host',
				'icon'       => 'ri-mic-line',
				'color'      => '#ec4899',
				'html_icon'  => '<i class="ri-mic-line" title="Host (legacy)"></i>',
				'deprecated' => true,
			),
			'business-pers'  => array(
				'label'      => 'Business',
				'icon'       => 'ri-briefcase-line',
				'color'      => '#14b8a6',
				'html_icon'  => '<i class="ri-briefcase-line" title="Business (legacy)"></i>',
				'deprecated' => true,
			),
		),

		/**
		 * Access slugs — tail of _apollo_membership array (not shown as profile badge).
		 */
		'access_slugs' => array(
			'app-apollodj' => array(
				'label'     => 'App: apolloDJ',
				'icon'      => 'ri-disc-line',
				'color'     => '#E8820C',
				'html_icon' => '<i class="ri-disc-line" title="apolloDJ desktop"></i>',
				'access'    => 'desktop',
			),
			'amigz'        => array(
				'label'     => 'Amigz (Nicotine+)',
				'icon'      => 'ri-group-line',
				'color'     => '#00bcd4',
				'html_icon' => '<i class="ri-group-line" title="Amigz"></i>',
				'access'    => 'nicotine',
			),
			'greatdjs'     => array(
				'label'     => 'Great DJs (Nicotine+)',
				'icon'      => 'ri-vip-diamond-line',
				'color'     => '#ffd700',
				'html_icon' => '<i class="ri-vip-diamond-line" title="Great DJs"></i>',
				'access'    => 'nicotine',
			),
			'amigxs'       => array(
				'label'     => 'Amigxs (Nicotine+)',
				'icon'      => 'ri-group-2-line',
				'color'     => '#00bcd4',
				'html_icon' => '<i class="ri-group-2-line" title="Amigxs"></i>',
				'access'    => 'nicotine',
			),
		),

		/**
		 * Rewrite on migration (prod→producer, govern→governmt).
		 * host/business-pers map to themselves (deprecated read-only).
		 */
		'legacy_aliases' => array(
			'prod'          => 'producer',
			'govern'        => 'governmt',
			'host'          => 'host',
			'business-pers' => 'business-pers',
		),

		'nicotine_tier_rank' => array(
			''         => 0,
			'amigxs'   => 1,
			'amigz'    => 2,
			'greatdjs' => 3,
		),

		'deprecated_user_meta' => array(
			'_apollo_verified',
			'_apollo_team',
			'_apollo_cenario',
			'apollo_membership_badge',
			'apollo_verified',
		),

		/**
		 * Slugs blocked from agent delegation unless explicitly whitelisted in _apollo_agent_for.
		 */
		'agent_restricted_slugs' => array(
			'apollo',
			'mod',
			'app-apollodj',
			'greatdjs',
		),
	),
);
