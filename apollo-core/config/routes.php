<?php
/**
 * Apollo Ecosystem — REST API Routes
 *
 * ALL 197+ REST endpoints defined centrally.
 * Namespace: apollo/v1
 *
 * Structure: prefix => [ owner, endpoints[] ]
 *
 * @package Apollo\Core
 * @since   6.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(

	/*
	═══════════════════════════════════════════════════════════════════
	 * CORE
	 * ═══════════════════════════════════════════════════════════════════ */
	'/health'                         => array(
		'owner'   => 'apollo-core',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/registry'                       => array(
		'owner'   => 'apollo-core',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/registry/cpts'                  => array(
		'owner'   => 'apollo-core',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/registry/taxonomies'            => array(
		'owner'   => 'apollo-core',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/registry/status'                => array(
		'owner'   => 'apollo-core',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/registry/tables'                => array(
		'owner'   => 'apollo-core',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/sounds'                         => array(
		'owner'   => 'apollo-core',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/sounds/tree'                    => array(
		'owner'   => 'apollo-core',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/sounds/{id}'                    => array(
		'owner'   => 'apollo-core',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/sounds/user'                    => array(
		'owner'   => 'apollo-core',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => true,
	),
	'/sounds/popular'                 => array(
		'owner'   => 'apollo-core',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * AUTH
	 * ═══════════════════════════════════════════════════════════════════ */
	'/auth/login'                     => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'POST' ),
		'auth'    => false,
	),
	'/auth/register'                  => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'POST' ),
		'auth'    => false,
	),
	'/auth/logout'                    => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/auth/reset-request'             => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'POST' ),
		'auth'    => false,
	),
	'/auth/reset-confirm'             => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'POST' ),
		'auth'    => false,
	),
	'/auth/verify-email'              => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'POST' ),
		'auth'    => false,
	),
	'/auth/resend-verification'       => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'POST' ),
		'auth'    => false,
	),
	'/auth/check-username'            => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/auth/check-email'               => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/quiz/submit'                    => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'POST' ),
		'auth'    => false,
	),
	'/quiz/questions'                 => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/simon/submit'                   => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'POST' ),
		'auth'    => false,
	),
	'/simon/highscores'               => array(
		'owner'   => 'apollo-login',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * USERS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/users'                          => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/users/me'                       => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'GET', 'PUT' ),
		'auth'    => true,
	),
	'/users/{id}'                     => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'GET', 'PUT' ),
		'auth'    => false,
	),
	'/users/{id}/preferences'         => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'GET', 'PUT' ),
		'auth'    => true,
	),
	'/users/{id}/matchmaking'         => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/users/{id}/fields'              => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'GET', 'PUT' ),
		'auth'    => true,
	),
	'/users/search'                   => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/users/radar'                    => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/profile/{username}'             => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/profile/{username}/view'        => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'POST' ),
		'auth'    => false,
	),
	'/profile/avatar'                 => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'POST', 'DELETE' ),
		'auth'    => true,
	),
	'/profile/cover'                  => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'POST', 'DELETE' ),
		'auth'    => true,
	),
	'/profile/views'                  => array(
		'owner'   => 'apollo-users',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * EVENTS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/events'                         => array(
		'owner'   => 'apollo-events',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => 'mixed',
	),
	'/events/{id}'                    => array(
		'owner'   => 'apollo-events',
		'methods' => array( 'GET', 'PUT', 'DELETE' ),
		'auth'    => 'mixed',
	),
	'/events/upcoming'                => array(
		'owner'   => 'apollo-events',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/events/past'                    => array(
		'owner'   => 'apollo-events',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/events/today'                   => array(
		'owner'   => 'apollo-events',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/events/by-date/{date}'          => array(
		'owner'   => 'apollo-events',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/events/by-loc/{loc_id}'         => array(
		'owner'   => 'apollo-events',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/events/by-dj/{dj_id}'           => array(
		'owner'   => 'apollo-events',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/events/{id}/djs'                => array(
		'owner'   => 'apollo-events',
		'methods' => array( 'GET', 'POST', 'DELETE' ),
		'auth'    => 'mixed',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * DJS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/djs'                            => array(
		'owner'   => 'apollo-djs',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => 'mixed',
	),
	'/djs/{id}'                       => array(
		'owner'   => 'apollo-djs',
		'methods' => array( 'GET', 'PUT', 'DELETE' ),
		'auth'    => 'mixed',
	),
	'/djs/{id}/events'                => array(
		'owner'   => 'apollo-djs',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/djs/by-sound/{sound}'           => array(
		'owner'   => 'apollo-djs',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/djs/search'                     => array(
		'owner'   => 'apollo-djs',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * LOCALS (LOC)
	 * ═══════════════════════════════════════════════════════════════════ */
	'/locals'                         => array(
		'owner'   => 'apollo-loc',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => 'mixed',
	),
	'/locals/{id}'                    => array(
		'owner'   => 'apollo-loc',
		'methods' => array( 'GET', 'PUT', 'DELETE' ),
		'auth'    => 'mixed',
	),
	'/locals/nearby'                  => array(
		'owner'   => 'apollo-loc',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * CLASSIFIEDS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/classifieds'                    => array(
		'owner'   => 'apollo-adverts',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => 'mixed',
	),
	'/classifieds/{id}'               => array(
		'owner'   => 'apollo-adverts',
		'methods' => array( 'GET', 'PUT', 'DELETE' ),
		'auth'    => 'mixed',
	),
	'/classifieds/search'             => array(
		'owner'   => 'apollo-adverts',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/classifieds/my'                 => array(
		'owner'   => 'apollo-adverts',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * SOCIAL / FEED
	 * ═══════════════════════════════════════════════════════════════════ */
	'/feed'                           => array(
		'owner'   => 'apollo-social',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/feed/post'                      => array(
		'owner'   => 'apollo-social',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/activity/{id}'                  => array(
		'owner'   => 'apollo-social',
		'methods' => array( 'DELETE' ),
		'auth'    => true,
	),
	'/followers/{user_id}'            => array(
		'owner'   => 'apollo-social',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/following/{user_id}'            => array(
		'owner'   => 'apollo-social',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * FAVORITES
	 * ═══════════════════════════════════════════════════════════════════ */
	'/favs'                           => array(
		'owner'   => 'apollo-fav',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => true,
	),
	'/favs/{post_id}'                 => array(
		'owner'   => 'apollo-fav',
		'methods' => array( 'DELETE' ),
		'auth'    => true,
	),
	'/favs/toggle/{post_id}'          => array(
		'owner'   => 'apollo-fav',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/favs/count/{post_id}'           => array(
		'owner'   => 'apollo-fav',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/favs/check/{post_id}'           => array(
		'owner'   => 'apollo-fav',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * WOW (REACTIONS)
	 * ═══════════════════════════════════════════════════════════════════ */
	'/wows'                           => array(
		'owner'   => 'apollo-wow',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/wows/{post_id}'                 => array(
		'owner'   => 'apollo-wow',
		'methods' => array( 'GET', 'DELETE' ),
		'auth'    => 'mixed',
	),
	'/wows/types'                     => array(
		'owner'   => 'apollo-wow',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * DEPOIMENTOS (COMMENTS)
	 * ═══════════════════════════════════════════════════════════════════ */
	'/depoimentos'                    => array(
		'owner'   => 'apollo-comment',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => 'mixed',
	),
	'/depoimentos/{id}'               => array(
		'owner'   => 'apollo-comment',
		'methods' => array( 'GET', 'PUT', 'DELETE' ),
		'auth'    => 'mixed',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * DOCUMENTS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/docs'                           => array(
		'owner'   => 'apollo-docs',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => true,
	),
	'/docs/{id}'                      => array(
		'owner'   => 'apollo-docs',
		'methods' => array( 'GET', 'PUT', 'DELETE' ),
		'auth'    => true,
	),
	'/docs/{id}/download'             => array(
		'owner'   => 'apollo-docs',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/docs/{id}/versions'             => array(
		'owner'   => 'apollo-docs',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/docs/{id}/lock'                 => array(
		'owner'   => 'apollo-docs',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/docs/{id}/finalize'             => array(
		'owner'   => 'apollo-docs',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/docs/{id}/upload'               => array(
		'owner'   => 'apollo-docs',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/docs/folders'                   => array(
		'owner'   => 'apollo-docs',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => true,
	),
	'/docs/folders/{id}'              => array(
		'owner'   => 'apollo-docs',
		'methods' => array( 'PUT', 'DELETE' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * SIGNATURES
	 * ═══════════════════════════════════════════════════════════════════ */
	'/signatures'                     => array(
		'owner'   => 'apollo-sign',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/signatures/{id}'                => array(
		'owner'   => 'apollo-sign',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/signatures/{id}/sign'           => array(
		'owner'   => 'apollo-sign',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/signatures/{id}/audit'          => array(
		'owner'   => 'apollo-sign',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/signatures/verify/{hash}'       => array(
		'owner'   => 'apollo-sign',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * CHAT
	 * ═══════════════════════════════════════════════════════════════════ */
	'/chat/threads'                   => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => true,
	),
	'/chat/threads/{id}'              => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'GET', 'DELETE' ),
		'auth'    => true,
	),
	'/chat/threads/{id}/messages'     => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => true,
	),
	'/chat/threads/{id}/read'         => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/chat/threads/{id}/members'      => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'GET', 'POST', 'DELETE' ),
		'auth'    => true,
	),
	'/chat/messages/{id}'             => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'PUT', 'DELETE' ),
		'auth'    => true,
	),
	'/chat/messages/{id}/react'       => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/chat/messages/{id}/pin'         => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/chat/messages/{id}/forward'     => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/chat/typing'                    => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/chat/poll'                      => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/chat/unread'                    => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/chat/more'                      => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/chat/presence'                  => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'PUT' ),
		'auth'    => true,
	),
	'/chat/online'                    => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/chat/search'                    => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/chat/upload'                    => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/chat/block'                     => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/chat/unblock'                   => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/chat/mute'                      => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/chat/unmute'                    => array(
		'owner'   => 'apollo-chat',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * NOTIFICATIONS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/notifications'                  => array(
		'owner'   => 'apollo-notif',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/notifications/{id}/read'        => array(
		'owner'   => 'apollo-notif',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/notifications/read-all'         => array(
		'owner'   => 'apollo-notif',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/notifications/unread-count'     => array(
		'owner'   => 'apollo-notif',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/notifications/preferences'      => array(
		'owner'   => 'apollo-notif',
		'methods' => array( 'GET', 'PUT' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * EMAIL
	 * ═══════════════════════════════════════════════════════════════════ */
	'/email/send'                     => array(
		'owner'   => 'apollo-email',
		'methods' => array( 'POST' ),
		'auth'    => 'admin',
	),
	'/email/test'                     => array(
		'owner'   => 'apollo-email',
		'methods' => array( 'POST' ),
		'auth'    => 'admin',
	),
	'/email/stats'                    => array(
		'owner'   => 'apollo-email',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/email/queue'                    => array(
		'owner'   => 'apollo-email',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/email/templates'                => array(
		'owner'   => 'apollo-email',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => 'admin',
	),
	'/email/templates/{id}'           => array(
		'owner'   => 'apollo-email',
		'methods' => array( 'GET', 'PUT', 'DELETE' ),
		'auth'    => 'admin',
	),
	'/email/log'                      => array(
		'owner'   => 'apollo-email',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/email/preferences'              => array(
		'owner'   => 'apollo-email',
		'methods' => array( 'GET', 'PUT' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * MEMBERSHIP / GAMIFICATION
	 * ═══════════════════════════════════════════════════════════════════ */
	'/membership/achievements'        => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/membership/achievements/{id}'   => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/membership/user-achievements'   => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'GET' ),
		'auth'    => 'optional',
	),
	'/membership/achievements/award'  => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'POST' ),
		'auth'    => 'admin',
	),
	'/membership/achievements/revoke' => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'POST' ),
		'auth'    => 'admin',
	),
	'/membership/points'              => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/membership/points/award'        => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'POST' ),
		'auth'    => 'admin',
	),
	'/membership/points/deduct'       => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'POST' ),
		'auth'    => 'admin',
	),
	'/membership/ranks'               => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/membership/ranks/{id}'          => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/membership/leaderboard'         => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/membership/user-summary'        => array(
		'owner'   => 'apollo-membership',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * SUPPLIERS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/suppliers'                      => array(
		'owner'   => 'apollo-suppliers',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => 'industry',
	),
	'/suppliers/{id}'                 => array(
		'owner'   => 'apollo-suppliers',
		'methods' => array( 'GET', 'PUT', 'DELETE' ),
		'auth'    => 'industry',
	),
	'/suppliers/search'               => array(
		'owner'   => 'apollo-suppliers',
		'methods' => array( 'GET' ),
		'auth'    => 'industry',
	),
	'/suppliers/categories'           => array(
		'owner'   => 'apollo-suppliers',
		'methods' => array( 'GET' ),
		'auth'    => 'industry',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * GROUPS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/groups'                         => array(
		'owner'   => 'apollo-groups',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => 'mixed',
	),
	'/groups/{id}'                    => array(
		'owner'   => 'apollo-groups',
		'methods' => array( 'GET', 'PUT', 'DELETE' ),
		'auth'    => 'mixed',
	),
	'/groups/{id}/members'            => array(
		'owner'   => 'apollo-groups',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/groups/{id}/join'               => array(
		'owner'   => 'apollo-groups',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/groups/{id}/leave'              => array(
		'owner'   => 'apollo-groups',
		'methods' => array( 'DELETE' ),
		'auth'    => true,
	),
	'/groups/my'                      => array(
		'owner'   => 'apollo-groups',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * ADMIN / SETTINGS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/settings'                       => array(
		'owner'   => 'apollo-admin',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/settings/{slug}'                => array(
		'owner'   => 'apollo-admin',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => 'admin',
	),
	'/settings/export'                => array(
		'owner'   => 'apollo-admin',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/settings/import'                => array(
		'owner'   => 'apollo-admin',
		'methods' => array( 'POST' ),
		'auth'    => 'admin',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * STATISTICS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/stats/overview'                 => array(
		'owner'   => 'apollo-statistics',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/stats/events'                   => array(
		'owner'   => 'apollo-statistics',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/stats/users'                    => array(
		'owner'   => 'apollo-statistics',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/stats/content'                  => array(
		'owner'   => 'apollo-statistics',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),
	'/stats/export'                   => array(
		'owner'   => 'apollo-statistics',
		'methods' => array( 'GET' ),
		'auth'    => 'admin',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * INDUSTRY (CENA)
	 * ═══════════════════════════════════════════════════════════════════ */
	'/cena/calendar'                  => array(
		'owner'   => 'apollo-cena',
		'methods' => array( 'GET' ),
		'auth'    => 'industry',
	),
	'/cena/calendar/save-date'        => array(
		'owner'   => 'apollo-cena',
		'methods' => array( 'POST' ),
		'auth'    => 'industry',
	),
	'/cena/calendar/{id}'             => array(
		'owner'   => 'apollo-cena',
		'methods' => array( 'PUT', 'DELETE' ),
		'auth'    => 'industry',
	),
	'/cena/members'                   => array(
		'owner'   => 'apollo-cena',
		'methods' => array( 'GET' ),
		'auth'    => 'industry',
	),
	'/cena/access/request'            => array(
		'owner'   => 'apollo-cena',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * SHEETS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/sheets'                         => array(
		'owner'   => 'apollo-sheets',
		'methods' => array( 'GET', 'POST' ),
		'auth'    => true,
	),
	'/sheets/{id}'                    => array(
		'owner'   => 'apollo-sheets',
		'methods' => array( 'GET', 'PUT', 'DELETE' ),
		'auth'    => true,
	),
	'/sheets/{id}/copy'               => array(
		'owner'   => 'apollo-sheets',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/sheets/import'                  => array(
		'owner'   => 'apollo-sheets',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/sheets/{id}/export'             => array(
		'owner'   => 'apollo-sheets',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/sheets/{id}/preview'            => array(
		'owner'   => 'apollo-sheets',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * SHORTCODES / SEARCH / NEWSLETTER
	 * ═══════════════════════════════════════════════════════════════════ */
	'/shortcodes'                     => array(
		'owner'   => 'apollo-shortcodes',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/shortcodes/render'              => array(
		'owner'   => 'apollo-shortcodes',
		'methods' => array( 'POST' ),
		'auth'    => false,
	),
	'/search'                         => array(
		'owner'   => 'apollo-shortcodes',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/newsletter/subscribe'           => array(
		'owner'   => 'apollo-shortcodes',
		'methods' => array( 'POST' ),
		'auth'    => false,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * TEMPLATES / CANVAS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/templates'                      => array(
		'owner'   => 'apollo-templates',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/canvas/save'                    => array(
		'owner'   => 'apollo-templates',
		'methods' => array( 'POST' ),
		'auth'    => true,
	),
	'/canvas/blocks'                  => array(
		'owner'   => 'apollo-templates',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * COAUTHORS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/coauthors/{post_id}'            => array(
		'owner'   => 'apollo-coauthor',
		'methods' => array( 'GET', 'PUT' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * DASHBOARD
	 * ═══════════════════════════════════════════════════════════════════ */
	'/dashboard'                      => array(
		'owner'   => 'apollo-dashboard',
		'methods' => array( 'GET' ),
		'auth'    => true,
	),
	'/dashboard/widgets'              => array(
		'owner'   => 'apollo-dashboard',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/dashboard/settings'             => array(
		'owner'   => 'apollo-dashboard',
		'methods' => array( 'GET', 'PUT' ),
		'auth'    => true,
	),
	'/dashboard/layout'               => array(
		'owner'   => 'apollo-dashboard',
		'methods' => array( 'GET', 'PUT' ),
		'auth'    => true,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * HUBS
	 * ═══════════════════════════════════════════════════════════════════ */
	'/hubs'                           => array(
		'owner'   => 'apollo-hub',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/hubs/{username}'                => array(
		'owner'   => 'apollo-hub',
		'methods' => array( 'GET', 'PUT' ),
		'auth'    => 'mixed',
	),
	'/hubs/{username}/links'          => array(
		'owner'   => 'apollo-hub',
		'methods' => array( 'GET', 'PUT' ),
		'auth'    => 'mixed',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * MODERATION
	 * ═══════════════════════════════════════════════════════════════════ */
	'/mod/queue'                      => array(
		'owner'   => 'apollo-mod',
		'methods' => array( 'GET' ),
		'auth'    => 'mod',
	),
	'/mod/queue/{id}/approve'         => array(
		'owner'   => 'apollo-mod',
		'methods' => array( 'POST' ),
		'auth'    => 'mod',
	),
	'/mod/queue/{id}/reject'          => array(
		'owner'   => 'apollo-mod',
		'methods' => array( 'POST' ),
		'auth'    => 'mod',
	),
	'/mod/queue/{id}/flag'            => array(
		'owner'   => 'apollo-mod',
		'methods' => array( 'POST' ),
		'auth'    => 'mod',
	),
	'/mod/log'                        => array(
		'owner'   => 'apollo-mod',
		'methods' => array( 'GET' ),
		'auth'    => 'mod',
	),
	'/mod/stats'                      => array(
		'owner'   => 'apollo-mod',
		'methods' => array( 'GET' ),
		'auth'    => 'mod',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * SEO
	 * ═══════════════════════════════════════════════════════════════════ */
	'/seo/sitemap'                    => array(
		'owner'   => 'apollo-seo',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * PWA
	 * ═══════════════════════════════════════════════════════════════════ */
	'/pwa/manifest'                   => array(
		'owner'   => 'apollo-pwa',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
	'/pwa/sw'                         => array(
		'owner'   => 'apollo-pwa',
		'methods' => array( 'GET' ),
		'auth'    => false,
	),
);
