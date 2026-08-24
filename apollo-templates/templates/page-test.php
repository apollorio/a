<?php

/**
 * Apollo Test — Routes & Pages Spreadsheet
 *
 * Blank-canvas page at /test listing all pages, forms, and REST endpoints.
 * Interactive spreadsheet with checkboxes, comments, and row completion.
 *
 * @package Apollo\Templates
 * @since 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Admin only
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Acesso restrito.', 'Apollo Test', array( 'response' => 403 ) );
}

$site_url = home_url();

// ═══════════════════════════════════════════════════════════════
// DATA — All pages, forms, REST endpoints
// ═══════════════════════════════════════════════════════════════
$routes = array(

	// ── PAGES ──────────────────────────────────────────────────
	array( 'section' => 'PAGES — Frontend' ),

	array(
		'url'    => '/',
		'type'   => 'page',
		'plugin' => 'apollo-templates',
		'desc'   => 'Landing (guest) / Mural (logged)',
	),
	array(
		'url'    => '/eventos',
		'type'   => 'page',
		'plugin' => 'apollo-events',
		'desc'   => 'Events archive listing',
	),
	array(
		'url'    => '/evento/{slug}',
		'type'   => 'page',
		'plugin' => 'apollo-events',
		'desc'   => 'Single event page',
	),
	array(
		'url'    => '/djs',
		'type'   => 'page',
		'plugin' => 'apollo-djs',
		'desc'   => 'DJs archive listing',
	),
	array(
		'url'    => '/dj/{slug}',
		'type'   => 'page',
		'plugin' => 'apollo-djs',
		'desc'   => 'Single DJ page',
	),
	array(
		'url'    => '/locais',
		'type'   => 'page',
		'plugin' => 'apollo-loc',
		'desc'   => 'Locations archive listing',
	),
	array(
		'url'    => '/loc/{slug}',
		'type'   => 'page',
		'plugin' => 'apollo-loc',
		'desc'   => 'Single location page',
	),
	array(
		'url'    => '/classificados',
		'type'   => 'page',
		'plugin' => 'apollo-adverts',
		'desc'   => 'Classifieds archive listing',
	),
	array(
		'url'    => '/anuncio/{slug}',
		'type'   => 'page',
		'plugin' => 'apollo-adverts',
		'desc'   => 'Single classified page',
	),
	array(
		'url'    => '/id/{username}',
		'type'   => 'page',
		'plugin' => 'apollo-users',
		'desc'   => 'User profile page',
	),
	array(
		'url'    => '/radar',
		'type'   => 'page',
		'plugin' => 'apollo-users',
		'desc'   => 'Radar — discover all users',
	),
	array(
		'url'    => '/feed',
		'type'   => 'page',
		'plugin' => 'apollo-social',
		'desc'   => 'Social activity feed (auth)',
	),
	array(
		'url'    => '/grupos',
		'type'   => 'page',
		'plugin' => 'apollo-groups',
		'desc'   => 'Groups listing',
	),
	array(
		'url'    => '/grupo/{slug}',
		'type'   => 'page',
		'plugin' => 'apollo-groups',
		'desc'   => 'Single group page',
	),
	array(
		'url'    => '/nucleos',
		'type'   => 'page',
		'plugin' => 'apollo-groups',
		'desc'   => 'Núcleos listing',
	),
	array(
		'url'    => '/comunas',
		'type'   => 'page',
		'plugin' => 'apollo-groups',
		'desc'   => 'Comunas listing',
	),
	array(
		'url'    => '/mensagens',
		'type'   => 'page',
		'plugin' => 'apollo-chat',
		'desc'   => 'Chat inbox (auth)',
	),
	array(
		'url'    => '/mensagens/{id}',
		'type'   => 'page',
		'plugin' => 'apollo-chat',
		'desc'   => 'Single thread conversation',
	),
	array(
		'url'    => '/notificacoes',
		'type'   => 'page',
		'plugin' => 'apollo-notif',
		'desc'   => 'Notifications page (auth)',
	),
	array(
		'url'    => '/conquistas',
		'type'   => 'page',
		'plugin' => 'apollo-membership',
		'desc'   => 'Achievement gallery',
	),
	array(
		'url'    => '/conquista/{slug}',
		'type'   => 'page',
		'plugin' => 'apollo-membership',
		'desc'   => 'Single achievement',
	),
	array(
		'url'    => '/minhas-conquistas',
		'type'   => 'page',
		'plugin' => 'apollo-membership',
		'desc'   => 'User earned achievements (auth)',
	),
	array(
		'url'    => '/pontos',
		'type'   => 'page',
		'plugin' => 'apollo-membership',
		'desc'   => 'Points overview',
	),
	array(
		'url'    => '/niveis',
		'type'   => 'page',
		'plugin' => 'apollo-membership',
		'desc'   => 'Rank progression',
	),
	array(
		'url'    => '/nivel/{slug}',
		'type'   => 'page',
		'plugin' => 'apollo-membership',
		'desc'   => 'Single rank detail',
	),
	array(
		'url'    => '/placar',
		'type'   => 'page',
		'plugin' => 'apollo-membership',
		'desc'   => 'Points leaderboard',
	),
	array(
		'url'    => '/evidencia/{hash}',
		'type'   => 'page',
		'plugin' => 'apollo-membership',
		'desc'   => 'Open Badge evidence',
	),
	array(
		'url'    => '/documentos',
		'type'   => 'page',
		'plugin' => 'apollo-docs',
		'desc'   => 'Document library (auth)',
	),
	array(
		'url'    => '/hub/{username}',
		'type'   => 'page',
		'plugin' => 'apollo-hub',
		'desc'   => 'Public Linktree-style page',
	),
	array(
		'url'    => '/fornecedores',
		'type'   => 'page',
		'plugin' => 'apollo-suppliers',
		'desc'   => 'Suppliers archive (industry)',
	),
	array(
		'url'    => '/fornecedor/{slug}',
		'type'   => 'page',
		'plugin' => 'apollo-suppliers',
		'desc'   => 'Single supplier (industry)',
	),
	array(
		'url'    => '/cena',
		'type'   => 'page',
		'plugin' => 'apollo-cena',
		'desc'   => 'Industry area main',
	),
	array(
		'url'    => '/cena/calendario',
		'type'   => 'page',
		'plugin' => 'apollo-cena',
		'desc'   => 'Industry calendar',
	),
	array(
		'url'    => '/cena/membros',
		'type'   => 'page',
		'plugin' => 'apollo-cena',
		'desc'   => 'Industry members',
	),
	array(
		'url'    => '/offline',
		'type'   => 'page',
		'plugin' => 'apollo-pwa',
		'desc'   => 'Offline fallback',
	),

	// ── AUTH PAGES ─────────────────────────────────────────────
	array( 'section' => 'AUTH — Login/Register' ),

	array(
		'url'    => '/acesso',
		'type'   => 'page',
		'plugin' => 'apollo-login',
		'desc'   => 'Login page',
	),
	array(
		'url'    => '/registre',
		'type'   => 'page',
		'plugin' => 'apollo-login',
		'desc'   => '7-step registration',
	),
	array(
		'url'    => '/reset',
		'type'   => 'page',
		'plugin' => 'apollo-login',
		'desc'   => 'Password reset',
	),
	array(
		'url'    => '/verificar-email',
		'type'   => 'page',
		'plugin' => 'apollo-login',
		'desc'   => 'Email verification',
	),
	array(
		'url'    => '/sair',
		'type'   => 'page',
		'plugin' => 'apollo-login',
		'desc'   => 'Logout redirect',
	),

	// ── FORM PAGES ─────────────────────────────────────────────
	array( 'section' => 'FORMS — Creation & Editing' ),

	array(
		'url'    => '/criar-evento',
		'type'   => 'form',
		'plugin' => 'apollo-events',
		'desc'   => 'Event creation form (auth)',
	),
	array(
		'url'    => '/editar-perfil',
		'type'   => 'form',
		'plugin' => 'apollo-users',
		'desc'   => 'Edit profile form (auth)',
	),
	array(
		'url'    => '/criar-anuncio',
		'type'   => 'form',
		'plugin' => 'apollo-adverts',
		'desc'   => 'Classified creation form (auth)',
	),
	array(
		'url'    => '/criar-comuna',
		'type'   => 'form',
		'plugin' => 'apollo-groups',
		'desc'   => 'Group creation form (auth)',
	),
	array(
		'url'    => '/editar-hub',
		'type'   => 'form',
		'plugin' => 'apollo-hub',
		'desc'   => 'Hub editor (auth)',
	),
	array(
		'url'    => '/assinar/{hash}',
		'type'   => 'form',
		'plugin' => 'apollo-sign',
		'desc'   => 'Digital signature page',
	),
	array(
		'url'    => '/editar/{cpt}/{id}',
		'type'   => 'form',
		'plugin' => 'apollo-templates',
		'desc'   => 'Frontend editor (auth)',
	),

	// ── DASHBOARD ──────────────────────────────────────────────
	array( 'section' => 'DASHBOARD — User Panel' ),

	array(
		'url'    => '/painel',
		'type'   => 'page',
		'plugin' => 'apollo-dashboard',
		'desc'   => 'User dashboard main (auth)',
	),
	array(
		'url'    => '/painel/eventos',
		'type'   => 'page',
		'plugin' => 'apollo-dashboard',
		'desc'   => 'Dashboard — My Events',
	),
	array(
		'url'    => '/painel/favoritos',
		'type'   => 'page',
		'plugin' => 'apollo-dashboard',
		'desc'   => 'Dashboard — My Favorites',
	),
	array(
		'url'    => '/painel/grupos',
		'type'   => 'page',
		'plugin' => 'apollo-dashboard',
		'desc'   => 'Dashboard — My Groups',
	),
	array(
		'url'    => '/painel/configuracoes',
		'type'   => 'page',
		'plugin' => 'apollo-dashboard',
		'desc'   => 'Dashboard — Settings',
	),

	// ── REST API — apollo-core ─────────────────────────────────
	array( 'section' => 'REST API — apollo-core' ),

	array(
		'url'    => 'apollo/v1/health',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — Health check',
	),
	array(
		'url'    => 'apollo/v1/registry',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — Registry (admin)',
	),
	array(
		'url'    => 'apollo/v1/registry/cpts',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — CPTs (admin)',
	),
	array(
		'url'    => 'apollo/v1/registry/taxonomies',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — Taxonomies (admin)',
	),
	array(
		'url'    => 'apollo/v1/registry/status',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — Plugin status (admin)',
	),
	array(
		'url'    => 'apollo/v1/registry/tables',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — DB tables (admin)',
	),
	array(
		'url'    => 'apollo/v1/sounds',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — Sound terms',
	),
	array(
		'url'    => 'apollo/v1/sounds/tree',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — Hierarchical sounds',
	),
	array(
		'url'    => 'apollo/v1/sounds/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — Single sound term',
	),
	array(
		'url'    => 'apollo/v1/sounds/user',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET/POST — User prefs',
	),
	array(
		'url'    => 'apollo/v1/sounds/popular',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — Popular sounds',
	),

	// ── REST API — apollo-login ────────────────────────────────
	array( 'section' => 'REST API — apollo-login' ),

	array(
		'url'    => 'apollo/v1/auth/login',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'POST — Authenticate',
	),
	array(
		'url'    => 'apollo/v1/auth/register',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'POST — Create account',
	),
	array(
		'url'    => 'apollo/v1/auth/logout',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'POST — End session',
	),
	array(
		'url'    => 'apollo/v1/auth/reset-request',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'POST — Request reset',
	),
	array(
		'url'    => 'apollo/v1/auth/reset-confirm',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'POST — Confirm reset',
	),
	array(
		'url'    => 'apollo/v1/auth/verify-email',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'POST — Verify email',
	),
	array(
		'url'    => 'apollo/v1/auth/resend-verification',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'POST — Resend verify',
	),
	array(
		'url'    => 'apollo/v1/auth/check-username',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'GET — Check username',
	),
	array(
		'url'    => 'apollo/v1/auth/check-email',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'GET — Check email',
	),
	array(
		'url'    => 'apollo/v1/quiz/submit',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'POST — Submit quiz',
	),
	array(
		'url'    => 'apollo/v1/quiz/questions',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'GET — Quiz questions',
	),
	array(
		'url'    => 'apollo/v1/simon/submit',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'POST — Simon score',
	),
	array(
		'url'    => 'apollo/v1/simon/highscores',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'GET — Simon leaderboard',
	),
	array(
		'url'    => 'apollo/v1/security/rewrites',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'GET — URL rewrites (admin)',
	),
	array(
		'url'    => 'apollo/v1/security/attempts',
		'type'   => 'rest',
		'plugin' => 'apollo-login',
		'desc'   => 'GET — Login attempts (admin)',
	),

	// ── REST API — apollo-users ────────────────────────────────
	array( 'section' => 'REST API — apollo-users' ),

	array(
		'url'    => 'apollo/v1/users',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'GET — List users',
	),
	array(
		'url'    => 'apollo/v1/users/me',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'GET/PUT — Current user',
	),
	array(
		'url'    => 'apollo/v1/users/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'GET/PUT — User by ID',
	),
	array(
		'url'    => 'apollo/v1/users/{id}/preferences',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'GET/PUT — User prefs',
	),
	array(
		'url'    => 'apollo/v1/users/{id}/matchmaking',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'GET — Matches',
	),
	array(
		'url'    => 'apollo/v1/users/{id}/fields',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'GET/PUT — Custom fields',
	),
	array(
		'url'    => 'apollo/v1/users/search',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'GET — Search users',
	),
	array(
		'url'    => 'apollo/v1/users/radar',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'GET — Radar spots',
	),
	array(
		'url'    => 'apollo/v1/profile/{username}',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'GET — Public profile',
	),
	array(
		'url'    => 'apollo/v1/profile/{username}/view',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'POST — Record view',
	),
	array(
		'url'    => 'apollo/v1/profile/avatar',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'POST/DEL — Avatar',
	),
	array(
		'url'    => 'apollo/v1/profile/cover',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'POST/DEL — Cover image',
	),
	array(
		'url'    => 'apollo/v1/profile/views',
		'type'   => 'rest',
		'plugin' => 'apollo-users',
		'desc'   => 'GET — View count',
	),

	// ── REST API — apollo-membership ───────────────────────────
	array( 'section' => 'REST API — apollo-membership' ),

	array(
		'url'    => 'apollo/v1/membership/achievements',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — All achievements',
	),
	array(
		'url'    => 'apollo/v1/membership/achievements/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — Single achievement',
	),
	array(
		'url'    => 'apollo/v1/membership/user-achievements',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — User earned',
	),
	array(
		'url'    => 'apollo/v1/membership/achievements/award',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'POST — Award (admin)',
	),
	array(
		'url'    => 'apollo/v1/membership/achievements/revoke',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'POST — Revoke (admin)',
	),
	array(
		'url'    => 'apollo/v1/membership/points',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — Point totals',
	),
	array(
		'url'    => 'apollo/v1/membership/points/award',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'POST — Award pts (admin)',
	),
	array(
		'url'    => 'apollo/v1/membership/points/deduct',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'POST — Deduct pts (admin)',
	),
	array(
		'url'    => 'apollo/v1/membership/points/reset',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'POST — Reset pts (admin)',
	),
	array(
		'url'    => 'apollo/v1/membership/points/history',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — Pts history',
	),
	array(
		'url'    => 'apollo/v1/membership/ranks',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — All ranks',
	),
	array(
		'url'    => 'apollo/v1/membership/ranks/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — Single rank',
	),
	array(
		'url'    => 'apollo/v1/membership/ranks/award',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'POST — Award rank (admin)',
	),
	array(
		'url'    => 'apollo/v1/membership/user-rank',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — User rank',
	),
	array(
		'url'    => 'apollo/v1/membership/user-summary',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — Full summary',
	),
	array(
		'url'    => 'apollo/v1/membership/leaderboard',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — Leaderboard',
	),
	array(
		'url'    => 'apollo/v1/membership/leaderboard/user/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — User position',
	),
	array(
		'url'    => 'apollo/v1/membership/membership-badge',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET/POST — Badge',
	),
	array(
		'url'    => 'apollo/v1/membership/triggers',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — Available triggers',
	),
	array(
		'url'    => 'apollo/v1/membership/evidence/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — Badge evidence',
	),
	array(
		'url'    => 'apollo/v1/membership/verify/{hash}',
		'type'   => 'rest',
		'plugin' => 'apollo-membership',
		'desc'   => 'GET — Verify badge',
	),

	// ── REST API — apollo-events ───────────────────────────────
	array( 'section' => 'REST API — apollo-events' ),

	array(
		'url'    => 'apollo/v1/events',
		'type'   => 'rest',
		'plugin' => 'apollo-events',
		'desc'   => 'GET/POST — List/Create',
	),
	array(
		'url'    => 'apollo/v1/events/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-events',
		'desc'   => 'GET/PUT/DEL — CRUD',
	),
	array(
		'url'    => 'apollo/v1/events/upcoming',
		'type'   => 'rest',
		'plugin' => 'apollo-events',
		'desc'   => 'GET — Future events',
	),
	array(
		'url'    => 'apollo/v1/events/past',
		'type'   => 'rest',
		'plugin' => 'apollo-events',
		'desc'   => 'GET — Past events',
	),
	array(
		'url'    => 'apollo/v1/events/today',
		'type'   => 'rest',
		'plugin' => 'apollo-events',
		'desc'   => 'GET — Today events',
	),
	array(
		'url'    => 'apollo/v1/events/by-date/{date}',
		'type'   => 'rest',
		'plugin' => 'apollo-events',
		'desc'   => 'GET — Events by date',
	),
	array(
		'url'    => 'apollo/v1/events/by-loc/{loc_id}',
		'type'   => 'rest',
		'plugin' => 'apollo-events',
		'desc'   => 'GET — Events at loc',
	),
	array(
		'url'    => 'apollo/v1/events/by-dj/{dj_id}',
		'type'   => 'rest',
		'plugin' => 'apollo-events',
		'desc'   => 'GET — Events with DJ',
	),
	array(
		'url'    => 'apollo/v1/events/{id}/djs',
		'type'   => 'rest',
		'plugin' => 'apollo-events',
		'desc'   => 'GET/POST/DEL — Event DJs',
	),

	// ── REST API — apollo-djs ──────────────────────────────────
	array( 'section' => 'REST API — apollo-djs' ),

	array(
		'url'    => 'apollo/v1/djs',
		'type'   => 'rest',
		'plugin' => 'apollo-djs',
		'desc'   => 'GET/POST — List/Create',
	),
	array(
		'url'    => 'apollo/v1/djs/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-djs',
		'desc'   => 'GET/PUT/DEL — CRUD',
	),
	array(
		'url'    => 'apollo/v1/djs/{id}/events',
		'type'   => 'rest',
		'plugin' => 'apollo-djs',
		'desc'   => 'GET — DJ events',
	),
	array(
		'url'    => 'apollo/v1/djs/by-sound/{sound}',
		'type'   => 'rest',
		'plugin' => 'apollo-djs',
		'desc'   => 'GET — DJs by genre',
	),
	array(
		'url'    => 'apollo/v1/djs/search',
		'type'   => 'rest',
		'plugin' => 'apollo-djs',
		'desc'   => 'GET — Search DJs',
	),

	// ── REST API — apollo-loc ──────────────────────────────────
	array( 'section' => 'REST API — apollo-loc' ),

	array(
		'url'    => 'apollo/v1/locs',
		'type'   => 'rest',
		'plugin' => 'apollo-loc',
		'desc'   => 'GET/POST — List/Create',
	),
	array(
		'url'    => 'apollo/v1/locs/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-loc',
		'desc'   => 'GET/PUT/DEL — CRUD',
	),
	array(
		'url'    => 'apollo/v1/locs/{id}/events',
		'type'   => 'rest',
		'plugin' => 'apollo-loc',
		'desc'   => 'GET — Loc events',
	),
	array(
		'url'    => 'apollo/v1/locs/nearby',
		'type'   => 'rest',
		'plugin' => 'apollo-loc',
		'desc'   => 'GET — Nearby (lat,lng,r)',
	),
	array(
		'url'    => 'apollo/v1/locs/geocode',
		'type'   => 'rest',
		'plugin' => 'apollo-loc',
		'desc'   => 'POST — Geocode address',
	),
	array(
		'url'    => 'apollo/v1/locs/search',
		'type'   => 'rest',
		'plugin' => 'apollo-loc',
		'desc'   => 'GET — Search locations',
	),

	// ── REST API — apollo-adverts ──────────────────────────────
	array( 'section' => 'REST API — apollo-adverts' ),

	array(
		'url'    => 'apollo/v1/classifieds',
		'type'   => 'rest',
		'plugin' => 'apollo-adverts',
		'desc'   => 'GET/POST — List/Create',
	),
	array(
		'url'    => 'apollo/v1/classifieds/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-adverts',
		'desc'   => 'GET/PUT/DEL — CRUD',
	),
	array(
		'url'    => 'apollo/v1/classifieds/search',
		'type'   => 'rest',
		'plugin' => 'apollo-adverts',
		'desc'   => 'GET — Search ads',
	),
	array(
		'url'    => 'apollo/v1/classifieds/my',
		'type'   => 'rest',
		'plugin' => 'apollo-adverts',
		'desc'   => 'GET — My ads (auth)',
	),

	// ── REST API — apollo-social ───────────────────────────────
	array( 'section' => 'REST API — apollo-social' ),

	array(
		'url'    => 'apollo/v1/feed',
		'type'   => 'rest',
		'plugin' => 'apollo-social',
		'desc'   => 'GET — Feed (auth)',
	),
	array(
		'url'    => 'apollo/v1/feed/post',
		'type'   => 'rest',
		'plugin' => 'apollo-social',
		'desc'   => 'POST — Create post (auth)',
	),
	array(
		'url'    => 'apollo/v1/activity/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-social',
		'desc'   => 'DEL — Delete activity',
	),
	array(
		'url'    => 'apollo/v1/depo/{user_id}',
		'type'   => 'rest',
		'plugin' => 'apollo-social',
		'desc'   => 'GET — Depoimentos about a user',
	),

	// ── REST API — apollo-groups ───────────────────────────────
	array( 'section' => 'REST API — apollo-groups' ),

	array(
		'url'    => 'apollo/v1/groups',
		'type'   => 'rest',
		'plugin' => 'apollo-groups',
		'desc'   => 'GET/POST — List/Create',
	),
	array(
		'url'    => 'apollo/v1/groups/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-groups',
		'desc'   => 'GET/PUT/DEL — CRUD',
	),
	array(
		'url'    => 'apollo/v1/groups/{id}/members',
		'type'   => 'rest',
		'plugin' => 'apollo-groups',
		'desc'   => 'GET — Members',
	),
	array(
		'url'    => 'apollo/v1/groups/{id}/join',
		'type'   => 'rest',
		'plugin' => 'apollo-groups',
		'desc'   => 'POST — Join (auth)',
	),
	array(
		'url'    => 'apollo/v1/groups/{id}/leave',
		'type'   => 'rest',
		'plugin' => 'apollo-groups',
		'desc'   => 'DEL — Leave (auth)',
	),
	array(
		'url'    => 'apollo/v1/groups/nucleos',
		'type'   => 'rest',
		'plugin' => 'apollo-groups',
		'desc'   => 'GET — Núcleos',
	),
	array(
		'url'    => 'apollo/v1/groups/comunas',
		'type'   => 'rest',
		'plugin' => 'apollo-groups',
		'desc'   => 'GET — Comunas',
	),
	array(
		'url'    => 'apollo/v1/groups/my',
		'type'   => 'rest',
		'plugin' => 'apollo-groups',
		'desc'   => 'GET — My groups (auth)',
	),

	// ── REST API — apollo-fav ──────────────────────────────────
	array( 'section' => 'REST API — apollo-fav' ),

	array(
		'url'    => 'apollo/v1/favs',
		'type'   => 'rest',
		'plugin' => 'apollo-fav',
		'desc'   => 'GET/POST — Favorites',
	),
	array(
		'url'    => 'apollo/v1/favs/{post_id}',
		'type'   => 'rest',
		'plugin' => 'apollo-fav',
		'desc'   => 'DEL — Remove fav',
	),
	array(
		'url'    => 'apollo/v1/favs/toggle/{post_id}',
		'type'   => 'rest',
		'plugin' => 'apollo-fav',
		'desc'   => 'POST — Toggle fav',
	),
	array(
		'url'    => 'apollo/v1/favs/count/{post_id}',
		'type'   => 'rest',
		'plugin' => 'apollo-fav',
		'desc'   => 'GET — Fav count',
	),
	array(
		'url'    => 'apollo/v1/favs/check/{post_id}',
		'type'   => 'rest',
		'plugin' => 'apollo-fav',
		'desc'   => 'GET — Is favorited',
	),

	// ── REST API — apollo-wow ──────────────────────────────────
	array( 'section' => 'REST API — apollo-wow' ),

	array(
		'url'    => 'apollo/v1/wows',
		'type'   => 'rest',
		'plugin' => 'apollo-wow',
		'desc'   => 'POST — Add wow',
	),
	array(
		'url'    => 'apollo/v1/wows/{post_id}',
		'type'   => 'rest',
		'plugin' => 'apollo-wow',
		'desc'   => 'GET/DEL — Get/Remove',
	),
	array(
		'url'    => 'apollo/v1/wows/types',
		'type'   => 'rest',
		'plugin' => 'apollo-wow',
		'desc'   => 'GET — Wow types',
	),

	// ── REST API — apollo-comment ──────────────────────────────
	array( 'section' => 'REST API — apollo-comment' ),

	array(
		'url'    => 'apollo/v1/depoimentos',
		'type'   => 'rest',
		'plugin' => 'apollo-comment',
		'desc'   => 'GET/POST — Depoimentos',
	),
	array(
		'url'    => 'apollo/v1/depoimentos/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-comment',
		'desc'   => 'GET/PUT/DEL — CRUD',
	),

	// ── REST API — apollo-notif ────────────────────────────────
	array( 'section' => 'REST API — apollo-notif' ),

	array(
		'url'    => 'apollo/v1/notifications',
		'type'   => 'rest',
		'plugin' => 'apollo-notif',
		'desc'   => 'GET — Notifications',
	),
	array(
		'url'    => 'apollo/v1/notifications/{id}/read',
		'type'   => 'rest',
		'plugin' => 'apollo-notif',
		'desc'   => 'POST — Mark read',
	),
	array(
		'url'    => 'apollo/v1/notifications/read-all',
		'type'   => 'rest',
		'plugin' => 'apollo-notif',
		'desc'   => 'POST — Mark all read',
	),
	array(
		'url'    => 'apollo/v1/notifications/unread-count',
		'type'   => 'rest',
		'plugin' => 'apollo-notif',
		'desc'   => 'GET — Unread count',
	),
	array(
		'url'    => 'apollo/v1/notifications/preferences',
		'type'   => 'rest',
		'plugin' => 'apollo-notif',
		'desc'   => 'GET/PUT — Prefs',
	),

	// ── REST API — apollo-email ────────────────────────────────
	array( 'section' => 'REST API — apollo-email' ),

	array(
		'url'    => 'apollo/v1/email/send',
		'type'   => 'rest',
		'plugin' => 'apollo-email',
		'desc'   => 'POST — Send (admin)',
	),
	array(
		'url'    => 'apollo/v1/email/queue',
		'type'   => 'rest',
		'plugin' => 'apollo-email',
		'desc'   => 'GET — Queue (admin)',
	),
	array(
		'url'    => 'apollo/v1/email/templates',
		'type'   => 'rest',
		'plugin' => 'apollo-email',
		'desc'   => 'GET/POST — Templates (admin)',
	),
	array(
		'url'    => 'apollo/v1/email/templates/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-email',
		'desc'   => 'GET/PUT/DEL — Template CRUD',
	),
	array(
		'url'    => 'apollo/v1/email/log',
		'type'   => 'rest',
		'plugin' => 'apollo-email',
		'desc'   => 'GET — Log (admin)',
	),
	array(
		'url'    => 'apollo/v1/email/preferences',
		'type'   => 'rest',
		'plugin' => 'apollo-email',
		'desc'   => 'GET/PUT — User prefs',
	),

	// ── REST API — apollo-chat ─────────────────────────────────
	array( 'section' => 'REST API — apollo-chat' ),

	array(
		'url'    => 'apollo/v1/chat/threads',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'GET/POST — Threads',
	),
	array(
		'url'    => 'apollo/v1/chat/threads/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'GET/DEL — Thread',
	),
	array(
		'url'    => 'apollo/v1/chat/threads/{id}/messages',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'GET/POST — Messages',
	),
	array(
		'url'    => 'apollo/v1/chat/threads/{id}/read',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'POST — Mark read',
	),
	array(
		'url'    => 'apollo/v1/chat/threads/{id}/members',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'GET/POST/DEL — Members',
	),
	array(
		'url'    => 'apollo/v1/chat/messages/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'PUT/DEL — Edit/Delete msg',
	),
	array(
		'url'    => 'apollo/v1/chat/messages/{id}/react',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'POST — Emoji reaction',
	),
	array(
		'url'    => 'apollo/v1/chat/messages/{id}/pin',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'POST — Pin/Unpin',
	),
	array(
		'url'    => 'apollo/v1/chat/messages/{id}/forward',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'POST — Forward msg',
	),
	array(
		'url'    => 'apollo/v1/chat/typing',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'POST — Typing indicator',
	),
	array(
		'url'    => 'apollo/v1/chat/poll',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'GET — Poll new msgs',
	),
	array(
		'url'    => 'apollo/v1/chat/unread',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'GET — Unread count',
	),
	array(
		'url'    => 'apollo/v1/chat/more',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'GET — Load more threads',
	),
	array(
		'url'    => 'apollo/v1/chat/presence',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'PUT — Presence status',
	),
	array(
		'url'    => 'apollo/v1/chat/online',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'GET — Online contacts',
	),
	array(
		'url'    => 'apollo/v1/chat/search',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'GET — Search messages',
	),
	array(
		'url'    => 'apollo/v1/chat/upload',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'POST — Upload attachment',
	),
	array(
		'url'    => 'apollo/v1/chat/block',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'POST — Block user',
	),
	array(
		'url'    => 'apollo/v1/chat/unblock',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'POST — Unblock user',
	),
	array(
		'url'    => 'apollo/v1/chat/mute',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'POST — Mute thread',
	),
	array(
		'url'    => 'apollo/v1/chat/unmute',
		'type'   => 'rest',
		'plugin' => 'apollo-chat',
		'desc'   => 'POST — Unmute thread',
	),

	// ── REST API — apollo-docs ─────────────────────────────────
	array( 'section' => 'REST API — apollo-docs' ),

	array(
		'url'    => 'apollo/v1/docs',
		'type'   => 'rest',
		'plugin' => 'apollo-docs',
		'desc'   => 'GET/POST — Documents',
	),
	array(
		'url'    => 'apollo/v1/docs/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-docs',
		'desc'   => 'GET/DEL — Document',
	),
	array(
		'url'    => 'apollo/v1/docs/{id}/download',
		'type'   => 'rest',
		'plugin' => 'apollo-docs',
		'desc'   => 'GET — Download',
	),
	array(
		'url'    => 'apollo/v1/docs/folders',
		'type'   => 'rest',
		'plugin' => 'apollo-docs',
		'desc'   => 'GET/POST — Folders',
	),
	array(
		'url'    => 'apollo/v1/docs/folders/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-docs',
		'desc'   => 'PUT/DEL — Folder CRUD',
	),

	// ── REST API — apollo-sign ─────────────────────────────────
	array( 'section' => 'REST API — apollo-sign' ),

	array(
		'url'    => 'apollo/v1/signatures',
		'type'   => 'rest',
		'plugin' => 'apollo-sign',
		'desc'   => 'POST — Create signature',
	),
	array(
		'url'    => 'apollo/v1/signatures/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-sign',
		'desc'   => 'GET — Get signature',
	),
	array(
		'url'    => 'apollo/v1/signatures/verify/{hash}',
		'type'   => 'rest',
		'plugin' => 'apollo-sign',
		'desc'   => 'GET — Verify',
	),
	array(
		'url'    => 'apollo/v1/signatures/{id}/audit',
		'type'   => 'rest',
		'plugin' => 'apollo-sign',
		'desc'   => 'GET — Audit trail',
	),

	// ── REST API — apollo-templates/shortcodes ─────────────────
	array( 'section' => 'REST API — apollo-templates & shortcodes' ),

	array(
		'url'    => 'apollo/v1/templates',
		'type'   => 'rest',
		'plugin' => 'apollo-templates',
		'desc'   => 'GET — List templates',
	),
	array(
		'url'    => 'apollo/v1/templates/calendars',
		'type'   => 'rest',
		'plugin' => 'apollo-templates',
		'desc'   => 'GET — Calendar types',
	),
	array(
		'url'    => 'apollo/v1/canvas/save',
		'type'   => 'rest',
		'plugin' => 'apollo-templates',
		'desc'   => 'POST — Save canvas',
	),
	array(
		'url'    => 'apollo/v1/canvas/blocks',
		'type'   => 'rest',
		'plugin' => 'apollo-templates',
		'desc'   => 'GET — Blocks',
	),
	array(
		'url'    => 'apollo/v1/shortcodes',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — List shortcodes',
	),
	array(
		'url'    => 'apollo/v1/shortcodes/{tag}',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'GET — Shortcode info',
	),
	array(
		'url'    => 'apollo/v1/shortcodes/render',
		'type'   => 'rest',
		'plugin' => 'apollo-core',
		'desc'   => 'POST — Render shortcode',
	),

	// ── REST API — apollo-dashboard ────────────────────────────
	array( 'section' => 'REST API — apollo-dashboard' ),

	array(
		'url'    => 'apollo/v1/dashboard',
		'type'   => 'rest',
		'plugin' => 'apollo-dashboard',
		'desc'   => 'GET — Dashboard data',
	),
	array(
		'url'    => 'apollo/v1/dashboard/widgets',
		'type'   => 'rest',
		'plugin' => 'apollo-dashboard',
		'desc'   => 'GET — Widgets',
	),
	array(
		'url'    => 'apollo/v1/dashboard/settings',
		'type'   => 'rest',
		'plugin' => 'apollo-dashboard',
		'desc'   => 'GET/PUT — Settings',
	),
	array(
		'url'    => 'apollo/v1/dashboard/layout',
		'type'   => 'rest',
		'plugin' => 'apollo-dashboard',
		'desc'   => 'GET/PUT — Layout',
	),

	// ── REST API — apollo-hub ──────────────────────────────────
	array( 'section' => 'REST API — apollo-hub' ),

	array(
		'url'    => 'apollo/v1/hubs',
		'type'   => 'rest',
		'plugin' => 'apollo-hub',
		'desc'   => 'GET — List hubs',
	),
	array(
		'url'    => 'apollo/v1/hubs/{username}',
		'type'   => 'rest',
		'plugin' => 'apollo-hub',
		'desc'   => 'GET/PUT — Hub CRUD',
	),
	array(
		'url'    => 'apollo/v1/hubs/{username}/links',
		'type'   => 'rest',
		'plugin' => 'apollo-hub',
		'desc'   => 'GET/PUT — Manage links',
	),

	// ── REST API — apollo-coauthor ─────────────────────────────
	array( 'section' => 'REST API — apollo-coauthor' ),

	array(
		'url'    => 'apollo/v1/coauthors/{post_id}',
		'type'   => 'rest',
		'plugin' => 'apollo-coauthor',
		'desc'   => 'GET/PUT — Coauthors',
	),

	// ── REST API — apollo-mod ──────────────────────────────────
	array( 'section' => 'REST API — apollo-mod' ),

	array(
		'url'    => 'apollo/v1/mod/queue',
		'type'   => 'rest',
		'plugin' => 'apollo-mod',
		'desc'   => 'GET — Mod queue',
	),
	array(
		'url'    => 'apollo/v1/mod/queue/{id}/approve',
		'type'   => 'rest',
		'plugin' => 'apollo-mod',
		'desc'   => 'POST — Approve',
	),
	array(
		'url'    => 'apollo/v1/mod/queue/{id}/reject',
		'type'   => 'rest',
		'plugin' => 'apollo-mod',
		'desc'   => 'POST — Reject',
	),
	array(
		'url'    => 'apollo/v1/mod/queue/{id}/flag',
		'type'   => 'rest',
		'plugin' => 'apollo-mod',
		'desc'   => 'POST — Flag',
	),
	array(
		'url'    => 'apollo/v1/mod/log',
		'type'   => 'rest',
		'plugin' => 'apollo-mod',
		'desc'   => 'GET — Mod log',
	),
	array(
		'url'    => 'apollo/v1/mod/stats',
		'type'   => 'rest',
		'plugin' => 'apollo-mod',
		'desc'   => 'GET — Mod stats',
	),

	// ── REST API — apollo-admin ────────────────────────────────
	array( 'section' => 'REST API — apollo-admin' ),

	array(
		'url'    => 'apollo/v1/admin/settings',
		'type'   => 'rest',
		'plugin' => 'apollo-admin',
		'desc'   => 'GET/PUT — All settings',
	),
	array(
		'url'    => 'apollo/v1/admin/settings/{plugin}',
		'type'   => 'rest',
		'plugin' => 'apollo-admin',
		'desc'   => 'GET/PUT — Plugin settings',
	),

	// ── REST API — apollo-statistics ───────────────────────────
	array( 'section' => 'REST API — apollo-statistics' ),

	array(
		'url'    => 'apollo/v1/stats/overview',
		'type'   => 'rest',
		'plugin' => 'apollo-statistics',
		'desc'   => 'GET — Overview (admin)',
	),
	array(
		'url'    => 'apollo/v1/stats/events',
		'type'   => 'rest',
		'plugin' => 'apollo-statistics',
		'desc'   => 'GET — Event stats',
	),
	array(
		'url'    => 'apollo/v1/stats/users',
		'type'   => 'rest',
		'plugin' => 'apollo-statistics',
		'desc'   => 'GET — User stats',
	),
	array(
		'url'    => 'apollo/v1/stats/content',
		'type'   => 'rest',
		'plugin' => 'apollo-statistics',
		'desc'   => 'GET — Content stats',
	),
	array(
		'url'    => 'apollo/v1/stats/export',
		'type'   => 'rest',
		'plugin' => 'apollo-statistics',
		'desc'   => 'GET — Export CSV',
	),

	// ── REST API — apollo-suppliers ────────────────────────────
	array( 'section' => 'REST API — apollo-suppliers' ),

	array(
		'url'    => 'apollo/v1/suppliers',
		'type'   => 'rest',
		'plugin' => 'apollo-suppliers',
		'desc'   => 'GET/POST — Suppliers',
	),
	array(
		'url'    => 'apollo/v1/suppliers/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-suppliers',
		'desc'   => 'GET/PUT/DEL — CRUD',
	),
	array(
		'url'    => 'apollo/v1/suppliers/search',
		'type'   => 'rest',
		'plugin' => 'apollo-suppliers',
		'desc'   => 'GET — Search',
	),
	array(
		'url'    => 'apollo/v1/suppliers/categories',
		'type'   => 'rest',
		'plugin' => 'apollo-suppliers',
		'desc'   => 'GET — Categories',
	),

	// ── REST API — apollo-cena ─────────────────────────────────
	array( 'section' => 'REST API — apollo-cena' ),

	array(
		'url'    => 'apollo/v1/cena/calendar',
		'type'   => 'rest',
		'plugin' => 'apollo-cena',
		'desc'   => 'GET — Industry calendar',
	),
	array(
		'url'    => 'apollo/v1/cena/calendar/save-date',
		'type'   => 'rest',
		'plugin' => 'apollo-cena',
		'desc'   => 'POST — Save date',
	),
	array(
		'url'    => 'apollo/v1/cena/calendar/{id}',
		'type'   => 'rest',
		'plugin' => 'apollo-cena',
		'desc'   => 'PUT/DEL — Date CRUD',
	),
	array(
		'url'    => 'apollo/v1/cena/members',
		'type'   => 'rest',
		'plugin' => 'apollo-cena',
		'desc'   => 'GET — Industry members',
	),
	array(
		'url'    => 'apollo/v1/cena/access/request',
		'type'   => 'rest',
		'plugin' => 'apollo-cena',
		'desc'   => 'POST — Request access',
	),

	// ── REST API — apollo-pwa ──────────────────────────────────
	array( 'section' => 'REST API — apollo-pwa' ),

	array(
		'url'    => 'apollo/v1/pwa/manifest',
		'type'   => 'rest',
		'plugin' => 'apollo-pwa',
		'desc'   => 'GET — Web manifest',
	),
	array(
		'url'    => 'apollo/v1/pwa/sw',
		'type'   => 'rest',
		'plugin' => 'apollo-pwa',
		'desc'   => 'GET — Service worker',
	),
);

// Saved state (cookie or option)
$saved_state = get_option( 'apollo_test_spreadsheet', array() );

$row_index = 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Apollo — Routes Spreadsheet</title>
	<script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>
	<style>
		/* ═══════════════════════════════════════════════
			APOLLO ROUTES SPREADSHEET — CDN-ALIGNED CSS
			Uses Apollo Design System tokens from CDN
			═══════════════════════════════════════════════ */

		body {
			font: var(--font);
			background: var(--bg);
			color: rgba(var(--txt-rgb), 0.77);
			min-height: 100vh;
			min-height: 100dvh;
			-webkit-font-smoothing: antialiased;
		}

		/* ── HEADER ───────────────────────── */
		.sheet-header {
			position: sticky;
			top: 0;
			z-index: var(--z-popover);
			background: var(--black-1);
			color: #fff;
			padding: var(--space-3) var(--space-4);
			display: flex;
			align-items: center;
			justify-content: space-between;
			border-bottom: 3px solid var(--primary);
		}

		.sheet-header h1 {
			font-family: var(--ff-mono);
			font-size: var(--fs-h5);
			font-weight: 700;
			display: flex;
			align-items: center;
			gap: var(--space-2);
			color: #fff;
		}

		.sheet-header h1 span {
			color: var(--primary);
		}

		.header-stats {
			display: flex;
			gap: 20px;
			font-size: .8rem;
			color: rgba(var(--rgb-t), .5);
		}

		.header-stats .stat-val {
			font-weight: 700;
			color: #fff;
			font-family: var(--ff-mono);
		}

		.header-actions {
			display: flex;
			gap: var(--space-2);
		}

		.header-btn {
			background: transparent;
			border: 1px solid rgba(var(--rgb-t), .18);
			color: #fff;
			padding: var(--space-1) var(--space-3);
			border-radius: var(--r-xs);
			cursor: pointer;
			font-family: var(--ff-main);
			font-size: .75rem;
			font-weight: 600;
			transition: all var(--transition-ui);
			display: flex;
			align-items: center;
			gap: var(--space-1);
		}

		.header-btn:hover {
			background: var(--primary);
			border-color: var(--primary);
			transform: translateY(-1px);
		}

		.header-btn.save-btn {
			background: var(--primary);
			border-color: var(--primary);
		}

		.header-btn.save-btn:hover {
			background: #d94f00;
			box-shadow: 0 4px 16px rgba(244, 95, 0, .35);
		}

		/* ── FILTERS ──────────────────────── */
		.filter-bar {
			position: sticky;
			top: 58px;
			z-index: 99;
			background: var(--white-1);
			border-bottom: 1px solid var(--border);
			padding: var(--space-2) var(--space-4);
			display: flex;
			gap: var(--space-2);
			align-items: center;
			backdrop-filter: blur(12px);
		}

		.filter-pill {
			padding: var(--space-1) var(--space-3);
			border-radius: var(--r-lg);
			border: 1px solid var(--border);
			background: var(--white-1);
			cursor: pointer;
			font-family: var(--ff-main);
			font-size: .75rem;
			font-weight: 500;
			transition: all var(--transition-ui);
			color: rgba(var(--txt-rgb), 0.5);
		}

		.filter-pill:hover {
			border-color: var(--primary);
			color: var(--primary);
			background: rgba(244, 95, 0, .04);
		}

		.filter-pill.active {
			background: var(--black-1);
			color: #fff;
			border-color: var(--black-1);
		}

		.filter-pill .count {
			font-family: var(--ff-mono);
			font-size: .65rem;
			opacity: .55;
			margin-left: var(--space-1);
		}

		.filter-search {
			margin-left: auto;
			border: 1px solid var(--border);
			border-radius: var(--r-sm);
			padding: 6px 12px 6px 34px;
			font-family: var(--ff-main);
			font-size: var(--fs-p);
			width: 240px;
			background: var(--white-1) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%239e9ea0'%3E%3Cpath d='M18.031 16.6168L22.3137 20.8995L20.8995 22.3137L16.6168 18.031C15.0769 19.263 13.124 20 11 20C6.032 20 2 15.968 2 11C2 6.032 6.032 2 11 2C15.968 2 20 6.032 20 11C20 13.124 19.263 15.0769 18.031 16.6168ZM16.0247 15.8748C17.2475 14.6146 18 12.8956 18 11C18 7.1325 14.8675 4 11 4C7.1325 4 4 7.1325 4 11C4 14.8675 7.1325 18 11 18C12.8956 18 14.6146 17.2475 15.8748 16.0247L16.0247 15.8748Z'%3E%3C/path%3E%3C/svg%3E") no-repeat 10px center / 14px;
			outline: none;
			transition: border-color var(--transition-ui), box-shadow var(--transition-ui);
		}

		.filter-search:focus {
			border-color: var(--primary);
			box-shadow: 0 0 0 3px rgba(244, 95, 0, .08);
		}

		/* ── TABLE ────────────────────────── */
		.sheet-wrap {
			overflow-x: auto;
			padding: 0 var(--space-4) 80px;
		}

		table.sheet {
			width: 100%;
			border-collapse: collapse;
			background: var(--white-1);
			border: 1px solid var(--border);
			border-radius: var(--r);
			overflow: hidden;
		}

		/* Section headers */
		.section-row td {
			background: var(--black-1);
			color: #fff;
			font-family: var(--ff-mono);
			font-weight: 700;
			font-size: .75rem;
			text-transform: uppercase;
			letter-spacing: .08em;
			padding: var(--space-2) var(--space-3);
			border: none;
		}

		/* Table head */
		table.sheet thead th {
			position: sticky;
			top: 96px;
			z-index: 50;
			background: var(--white-3);
			border-bottom: 2px solid var(--border);
			padding: var(--space-2) var(--space-3);
			text-align: left;
			font-family: var(--ff-mono);
			font-size: .65rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .08em;
			color: rgba(var(--txt-rgb), 0.4);
			white-space: nowrap;
		}

		th.col-num {
			width: 40px;
			text-align: center;
		}

		th.col-type {
			width: 70px;
		}

		th.col-url {
			width: auto;
		}

		th.col-plugin {
			width: 140px;
		}

		th.col-desc {
			width: 200px;
		}

		th.col-check {
			width: 50px;
			text-align: center;
		}

		th.col-comments {
			width: 260px;
		}

		th.col-check-comment {
			width: 50px;
			text-align: center;
		}

		th.col-done {
			width: 50px;
			text-align: center;
		}

		/* Table body */
		table.sheet tbody td {
			padding: 6px var(--space-3);
			border-bottom: 1px solid var(--border);
			vertical-align: middle;
			font-size: var(--fs-p);
			transition: all var(--transition-ui);
		}

		table.sheet tbody tr:hover td {
			background: rgba(244, 95, 0, .02);
		}

		table.sheet tbody tr.row-done td {
			color: var(--muted) !important;
			text-decoration: line-through;
			opacity: .4;
		}

		table.sheet tbody tr.row-done td .type-badge {
			opacity: .3;
		}

		/* Row number */
		td.col-num {
			text-align: center;
			color: var(--muted);
			font-family: var(--ff-mono);
			font-size: .7rem;
		}

		/* Type badges — apollo palette */
		.type-badge {
			display: inline-block;
			padding: 2px 10px;
			border-radius: var(--r-lg);
			font-family: var(--ff-mono);
			font-size: .6rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: .04em;
		}

		.type-badge.type-page {
			background: rgba(101, 31, 255, .08);
			color: var(--accent-violet);
		}

		.type-badge.type-form {
			background: rgba(244, 95, 0, .08);
			color: var(--primary);
		}

		.type-badge.type-rest {
			background: rgba(var(--txt-rgb), 0.06);
			color: rgba(var(--txt-rgb), 0.55);
		}

		/* URL */
		td.col-url {
			font-family: var(--ff-mono);
			font-size: .75rem;
			color: rgba(var(--txt-rgb), 0.85);
		}

		td.col-url a {
			color: inherit;
			text-decoration: none;
			transition: color var(--transition-ui);
		}

		td.col-url a:hover {
			color: var(--primary);
		}

		/* Plugin */
		td.col-plugin {
			font-size: .75rem;
			color: rgba(var(--txt-rgb), 0.45);
		}

		/* Comment textarea */
		.comment-input {
			width: 100%;
			border: 1px solid transparent;
			background: transparent;
			resize: vertical;
			min-height: 28px;
			max-height: 80px;
			font-family: var(--ff-main);
			font-size: .75rem;
			padding: var(--space-1) 6px;
			border-radius: var(--r-xs);
			color: rgba(var(--txt-rgb), 0.77);
			transition: all var(--transition-ui);
		}

		.comment-input:hover {
			border-color: var(--border);
		}

		.comment-input:focus {
			outline: none;
			border-color: var(--primary);
			background: rgba(244, 95, 0, .02);
			box-shadow: 0 0 0 3px rgba(244, 95, 0, .06);
		}

		/* Checkboxes — custom Apollo style */
		.sheet-check {
			-webkit-appearance: none;
			appearance: none;
			width: 18px;
			height: 18px;
			border: 2px solid var(--border);
			border-radius: 5px;
			cursor: pointer;
			transition: all var(--transition-ui);
			position: relative;
			background: var(--white-1);
		}

		.sheet-check:hover {
			border-color: var(--primary);
		}

		.sheet-check:checked {
			background: var(--primary);
			border-color: var(--primary);
		}

		.sheet-check:checked::after {
			content: '';
			position: absolute;
			left: 4px;
			top: 1px;
			width: 6px;
			height: 10px;
			border: solid #fff;
			border-width: 0 2px 2px 0;
			transform: rotate(45deg);
		}

		.sheet-check.done-check:checked {
			background: #22c55e;
			border-color: #22c55e;
		}

		/* ── PROGRESS BAR ─────────────────── */
		.progress-wrap {
			position: fixed;
			bottom: 0;
			left: 0;
			right: 0;
			background: var(--white-1);
			border-top: 1px solid var(--border);
			padding: var(--space-2) var(--space-4);
			z-index: var(--z-popover);
			display: flex;
			align-items: center;
			gap: var(--space-3);
			backdrop-filter: blur(12px);
		}

		.progress-bar {
			flex: 1;
			height: 6px;
			background: var(--gray-4);
			border-radius: var(--r-lg);
			overflow: hidden;
		}

		.progress-fill {
			height: 100%;
			background: linear-gradient(90deg, var(--primary), #22c55e);
			border-radius: var(--r-lg);
			transition: width .4s var(--ease-smooth);
		}

		.progress-text {
			font-size: .75rem;
			font-weight: 600;
			color: rgba(var(--txt-rgb), 0.45);
			font-family: var(--ff-mono);
			white-space: nowrap;
		}

		/* ── SAVE TOAST ───────────────────── */
		.toast {
			position: fixed;
			bottom: 60px;
			right: var(--space-4);
			background: var(--black-1);
			color: #fff;
			padding: var(--space-2) var(--space-4);
			border-radius: var(--r-sm);
			font-size: .8rem;
			font-weight: 600;
			font-family: var(--ff-main);
			transform: translateY(100px);
			opacity: 0;
			transition: all .3s var(--ease-snappy);
			z-index: var(--z-modal);
			box-shadow: 0 8px 32px rgba(var(--rgb-d), .18);
		}

		.toast.show {
			transform: translateY(0);
			opacity: 1;
		}

		/* ── RESPONSIVE ───────────────────── */
		@media (max-width: 768px) {
			.sheet-header {
				flex-wrap: wrap;
				gap: var(--space-2);
			}

			.header-stats {
				display: none;
			}

			.filter-bar {
				flex-wrap: wrap;
			}

			.filter-search {
				width: 100%;
				margin: 0;
			}
		}
	</style>
</head>

<body>

	<div class="sheet-header">
		<h1><i class="ri-table-line"></i> Apollo <span>Routes</span> Spreadsheet</h1>
		<div class="header-stats">
			<div>Pages <span class="stat-val" id="stat-pages">0</span></div>
			<div>Forms <span class="stat-val" id="stat-forms">0</span></div>
			<div>REST <span class="stat-val" id="stat-rest">0</span></div>
			<div>Total <span class="stat-val" id="stat-total">0</span></div>
		</div>
		<div class="header-actions">
			<button class="header-btn" onclick="exportCSV()"><i class="ri-download-2-line"></i> CSV</button>
			<button class="header-btn save-btn" onclick="saveState()"><i class="ri-save-line"></i> Salvar</button>
		</div>
	</div>

	<div class="filter-bar">
		<button class="filter-pill active" data-filter="all">Todos</button>
		<button class="filter-pill" data-filter="page">Pages <span class="count" id="fc-page">0</span></button>
		<button class="filter-pill" data-filter="form">Forms <span class="count" id="fc-form">0</span></button>
		<button class="filter-pill" data-filter="rest">REST <span class="count" id="fc-rest">0</span></button>
		<button class="filter-pill" data-filter="unchecked">Pendentes</button>
		<button class="filter-pill" data-filter="done">Concluídos</button>
		<input type="text" class="apollo-input filter-search" id="search-input" placeholder="Buscar URL, plugin...">
	</div>

	<div class="sheet-wrap">
		<table class="sheet" id="sheet-table">
			<thead>
				<tr>
					<th class="col-num">#</th>
					<th class="col-type">TIPO</th>
					<th class="col-url">URL</th>
					<th class="col-plugin">PLUGIN</th>
					<th class="col-desc">DESCRIÇÃO</th>
					<th class="col-check"><i class="ri-checkbox-line"></i></th>
					<th class="col-comments">COMENTÁRIOS</th>
					<th class="col-check-comment"><i class="ri-chat-check-line"></i></th>
					<th class="col-done">DONE</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $routes as $route ) :
					// Section header
					if ( isset( $route['section'] ) ) :
						?>
						<tr class="section-row" data-section>
							<td colspan="9"><?php echo esc_html( $route['section'] ); ?></td>
						</tr>
						<?php
						continue;
					endif;

					++$row_index;
					$row_key            = sanitize_title( $route['url'] );
					$saved              = $saved_state[ $row_key ] ?? array();
					$is_checked         = ! empty( $saved['checked'] );
					$comment            = $saved['comment'] ?? '';
					$is_comment_checked = ! empty( $saved['comment_checked'] );
					$is_done            = ! empty( $saved['done'] );
					$type               = $route['type'];

					// Build link for pages/forms
					$display_url = esc_html( $route['url'] );
					if ( $type !== 'rest' && ! str_contains( $route['url'], '{' ) ) {
						$full_url    = $site_url . $route['url'];
						$display_url = '<a href="' . esc_url( $full_url ) . '" target="_blank">' . esc_html( $route['url'] ) . '</a>';
					} elseif ( $type === 'rest' ) {
						$full_url    = $site_url . '/wp-json/' . $route['url'];
						$display_url = '<a href="' . esc_url( $full_url ) . '" target="_blank">' . esc_html( $route['url'] ) . '</a>';
					}
					?>
					<tr class="data-row <?php echo $is_done ? 'row-done' : ''; ?>"
						data-key="<?php echo esc_attr( $row_key ); ?>"
						data-type="<?php echo esc_attr( $type ); ?>"
						data-search="<?php echo esc_attr( strtolower( $route['url'] . ' ' . $route['plugin'] . ' ' . $route['desc'] ) ); ?>">
						<td class="col-num"><?php echo $row_index; ?></td>
						<td class="col-type"><span class="type-badge type-<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type ); ?></span></td>
						<td class="col-url"><?php echo $display_url; ?></td>
						<td class="col-plugin"><?php echo esc_html( $route['plugin'] ); ?></td>
						<td class="col-desc"><?php echo esc_html( $route['desc'] ); ?></td>
						<td class="col-check" style="text-align:center;">
							<input type="checkbox" class="sheet-check check-main" <?php checked( $is_checked ); ?>>
						</td>
						<td class="col-comments">
							<textarea class="apollo-textarea comment-input" placeholder="..."><?php echo esc_textarea( $comment ); ?></textarea>
						</td>
						<td class="col-check-comment" style="text-align:center;">
							<input type="checkbox" class="sheet-check check-comment" <?php checked( $is_comment_checked ); ?>>
						</td>
						<td class="col-done" style="text-align:center;">
							<input type="checkbox" class="sheet-check done-check" <?php checked( $is_done ); ?>>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="progress-wrap">
		<span class="progress-text" id="progress-text">0 / 0</span>
		<div class="progress-bar">
			<div class="progress-fill" id="progress-fill" style="width:0%"></div>
		</div>
		<span class="progress-text" id="progress-pct">0%</span>
	</div>

	<div class="toast" id="toast">Salvo!</div>

	<script>
		(function() {
			const table = document.getElementById('sheet-table');
			const rows = table.querySelectorAll('.data-row');
			const totalRows = rows.length;

			// ── Stats ──
			function updateStats() {
				let pages = 0,
					forms = 0,
					rest = 0,
					doneCount = 0;

				rows.forEach(r => {
					const type = r.dataset.type;
					if (type === 'page') pages++;
					else if (type === 'form') forms++;
					else if (type === 'rest') rest++;

					if (r.querySelector('.done-check').checked) doneCount++;
				});

				document.getElementById('stat-pages').textContent = pages;
				document.getElementById('stat-forms').textContent = forms;
				document.getElementById('stat-rest').textContent = rest;
				document.getElementById('stat-total').textContent = totalRows;
				document.getElementById('fc-page').textContent = pages;
				document.getElementById('fc-form').textContent = forms;
				document.getElementById('fc-rest').textContent = rest;

				// Progress
				const pct = totalRows ? Math.round((doneCount / totalRows) * 100) : 0;
				document.getElementById('progress-text').textContent = doneCount + ' / ' + totalRows;
				document.getElementById('progress-fill').style.width = pct + '%';
				document.getElementById('progress-pct').textContent = pct + '%';
			}

			// ── Done checkbox → row-done class ──
			table.addEventListener('change', function(e) {
				if (e.target.classList.contains('done-check')) {
					const row = e.target.closest('.data-row');
					if (e.target.checked) {
						row.classList.add('row-done');
					} else {
						row.classList.remove('row-done');
					}
					updateStats();
				}
			});

			// ── Filter pills ──
			document.querySelectorAll('.filter-pill').forEach(pill => {
				pill.addEventListener('click', function() {
					document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
					this.classList.add('active');
					applyFilters();
				});
			});

			// ── Search ──
			document.getElementById('search-input').addEventListener('input', applyFilters);

			function applyFilters() {
				const activeFilter = document.querySelector('.filter-pill.active')?.dataset.filter || 'all';
				const searchTerm = document.getElementById('search-input').value.toLowerCase().trim();

				// Show/hide section rows based on whether they have visible children
				const sectionRows = table.querySelectorAll('.section-row');

				rows.forEach(row => {
					let show = true;

					// Type filter
					if (activeFilter === 'page' || activeFilter === 'form' || activeFilter === 'rest') {
						show = row.dataset.type === activeFilter;
					} else if (activeFilter === 'unchecked') {
						show = !row.querySelector('.done-check').checked;
					} else if (activeFilter === 'done') {
						show = row.querySelector('.done-check').checked;
					}

					// Search
					if (show && searchTerm) {
						show = row.dataset.search.includes(searchTerm);
					}

					row.style.display = show ? '' : 'none';
				});

				// Section visibility
				sectionRows.forEach(sr => {
					let nextRow = sr.nextElementSibling;
					let hasVisible = false;
					while (nextRow && !nextRow.classList.contains('section-row')) {
						if (nextRow.style.display !== 'none') {
							hasVisible = true;
							break;
						}
						nextRow = nextRow.nextElementSibling;
					}
					sr.style.display = hasVisible ? '' : 'none';
				});
			}

			// ── Save state via AJAX ──
			window.saveState = function() {
				const state = {};
				rows.forEach(row => {
					const key = row.dataset.key;
					state[key] = {
						checked: row.querySelector('.check-main').checked,
						comment: row.querySelector('.comment-input').value,
						comment_checked: row.querySelector('.check-comment').checked,
						done: row.querySelector('.done-check').checked
					};
				});

				fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams({
							action: 'apollo_save_test_spreadsheet',
							nonce: '<?php echo wp_create_nonce( 'apollo_test_spreadsheet' ); ?>',
							state: JSON.stringify(state)
						})
					})
					.then(r => r.json())
					.then(data => {
						const toast = document.getElementById('toast');
						toast.textContent = data.success ? 'Salvo!' : 'Erro ao salvar';
						toast.classList.add('show');
						setTimeout(() => toast.classList.remove('show'), 2000);
					});
			};

			// ── Auto-save on change ──
			let saveTimeout;
			table.addEventListener('change', function() {
				clearTimeout(saveTimeout);
				saveTimeout = setTimeout(window.saveState, 2000);
			});

			table.addEventListener('input', function(e) {
				if (e.target.classList.contains('comment-input')) {
					clearTimeout(saveTimeout);
					saveTimeout = setTimeout(window.saveState, 3000);
				}
			});

			// ── Export CSV ──
			window.exportCSV = function() {
				let csv = 'Num,Tipo,URL,Plugin,Descricao,Checked,Comentario,CommentChecked,Done\n';

				rows.forEach((row, i) => {
					const url = row.querySelector('.col-url').textContent.trim();
					const type = row.dataset.type;
					const plugin = row.querySelector('.col-plugin').textContent.trim();
					const desc = row.querySelector('.col-desc').textContent.trim();
					const checked = row.querySelector('.check-main').checked ? 'Sim' : 'Nao';
					const comment = row.querySelector('.comment-input').value.replace(/"/g, '""');
					const commentChecked = row.querySelector('.check-comment').checked ? 'Sim' : 'Nao';
					const done = row.querySelector('.done-check').checked ? 'Sim' : 'Nao';

					csv += `${i+1},"${type}","${url}","${plugin}","${desc}","${checked}","${comment}","${commentChecked}","${done}"\n`;
				});

				const blob = new Blob([csv], {
					type: 'text/csv;charset=utf-8;'
				});
				const link = document.createElement('a');
				link.href = URL.createObjectURL(blob);
				link.download = 'apollo-routes-' + new Date().toISOString().slice(0, 10) + '.csv';
				link.click();
			};

			// ── Ctrl+S ──
			document.addEventListener('keydown', function(e) {
				if ((e.ctrlKey || e.metaKey) && e.key === 's') {
					e.preventDefault();
					window.saveState();
				}
			});

			// Init
			updateStats();
		})();
	</script>

</body>

</html>
<?php
