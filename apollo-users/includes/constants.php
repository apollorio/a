<?php
/**
 * Plugin Constants
 *
 * @package Apollo\Users
 */

declare(strict_types=1);

namespace Apollo\Users;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// REST API namespace
define( 'APOLLO_USERS_REST_NAMESPACE', 'apollo/v1' );

// Database tables (without prefix)
define( 'APOLLO_USERS_TABLE_MATCHMAKING', 'apollo_matchmaking' );
define( 'APOLLO_USERS_TABLE_FIELDS', 'apollo_user_fields' );
define( 'APOLLO_USERS_TABLE_PROFILE_VIEWS', 'apollo_profile_views' );
define( 'APOLLO_USERS_TABLE_RATINGS', 'apollo_user_ratings' );
define( 'APOLLO_USERS_TABLE_MATCHMAKING_SCORES', 'apollo_matchmaking_scores' );

// Profile URL pattern - ALWAYS /id/username
define( 'APOLLO_USERS_PROFILE_SLUG', 'id' );

// Membership types
define(
	'APOLLO_USERS_MEMBERSHIP_TYPES',
	array(
		'nao-verificado' => 'Não Verificado',
		'apollo'         => 'Apollo',
		'prod'           => 'Produtor',
		'dj'             => 'DJ',
		'host'           => 'Host',
		'govern'         => 'Governança',
		'business-pers'  => 'Business Personal',
	)
);

// Privacy levels
define(
	'APOLLO_USERS_PRIVACY_LEVELS',
	array(
		'public'  => 'Público',
		'members' => 'Apenas Membros',
		'private' => 'Privado',
	)
);
