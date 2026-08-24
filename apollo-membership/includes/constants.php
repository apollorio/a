<?php

/**
 * Plugin Constants
 *
 * Table names, REST namespace, membership badge types.
 * Adapted from BadgeOS settings + apollo-registry.json spec.
 *
 * @package Apollo\Membership
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ═══════════════════════════════════════════════════════════════════════════
// REST API
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_MEMBERSHIP_REST_NAMESPACE', 'apollo/v1' );

// ═══════════════════════════════════════════════════════════════════════════
// DATABASE TABLES (without wp prefix)
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_TABLE_ACHIEVEMENTS', 'apollo_achievements' );
define( 'APOLLO_TABLE_POINTS', 'apollo_points' );
define( 'APOLLO_TABLE_RANKS', 'apollo_ranks' );
define( 'APOLLO_TABLE_TRIGGERS', 'apollo_triggers' );
define( 'APOLLO_TABLE_STEPS', 'apollo_steps' );
define( 'APOLLO_TABLE_MEMBERSHIP_LOG', 'apollo_membership_log' );

// ═══════════════════════════════════════════════════════════════════════════
// MEMBERSHIP BADGE TYPES (ADMIN-ONLY — no relation to gamification points)
// These are visual membership badges assigned exclusively by administrators.
// Stored in user meta '_apollo_membership' (defined in apollo-users).
// ═══════════════════════════════════════════════════════════════════════════

define(
	'APOLLO_MEMBERSHIP_BADGE_TYPES',
	array(
		'nao-verificado' => array(
			'label'     => 'Não Verificado',
			'icon'      => 'ri-user-line',
			'color'     => '#999999',
			'html_icon' => '<i class="ri-user-line" title="Não Verificado"></i>',
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
		'app-apollodj'   => array(
			'label'     => 'App: apolloDJ',
			'icon'      => 'ri-disc-line',
			'color'     => '#E8820C',
			'html_icon' => '<i class="ri-disc-line" title="apolloDJ desktop"></i>',
			'access'    => 'desktop',
		),
		'amigz'          => array(
			'label'     => 'Amigz (Nicotine+)',
			'icon'      => 'ri-group-line',
			'color'     => '#00bcd4',
			'html_icon' => '<i class="ri-group-line" title="Amigz"></i>',
			'access'    => 'nicotine',
		),
		'greatdjs'       => array(
			'label'     => 'Great DJs (Nicotine+)',
			'icon'      => 'ri-vip-diamond-line',
			'color'     => '#ffd700',
			'html_icon' => '<i class="ri-vip-diamond-line" title="Great DJs"></i>',
			'access'    => 'nicotine',
		),
	)
);

// ═══════════════════════════════════════════════════════════════════════════
// GAMIFICATION DEFAULTS
// ═══════════════════════════════════════════════════════════════════════════

define( 'APOLLO_MEMBERSHIP_DEFAULT_POINT_IMAGE', APOLLO_MEMBERSHIP_URL . 'assets/img/point-default.png' );
define( 'APOLLO_MEMBERSHIP_DEFAULT_BADGE_IMAGE', APOLLO_MEMBERSHIP_URL . 'assets/img/badge-default.png' );
define( 'APOLLO_MEMBERSHIP_DEFAULT_RANK_IMAGE', APOLLO_MEMBERSHIP_URL . 'assets/img/rank-default.png' );
