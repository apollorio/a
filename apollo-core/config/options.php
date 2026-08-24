<?php
/**
 * Apollo Ecosystem — wp_options Keys
 *
 * All get_option / update_option keys used across the ecosystem.
 * Prevents magic strings and ensures consistent naming.
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
	'apollo_debug_mode'           => array(
		'type'    => 'boolean',
		'default' => false,
		'owner'   => 'apollo-core',
	),
	'apollo_db_version'           => array(
		'type'    => 'string',
		'default' => '0',
		'owner'   => 'apollo-core',
	),
	'apollo_installed_at'         => array(
		'type'    => 'string',
		'default' => '',
		'owner'   => 'apollo-core',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * LOGIN / AUTH
	 * ═══════════════════════════════════════════════════════════════════ */
	'apollo_login_url'            => array(
		'type'    => 'string',
		'default' => 'acesso',
		'owner'   => 'apollo-login',
	),
	'apollo_registration_enabled' => array(
		'type'    => 'boolean',
		'default' => true,
		'owner'   => 'apollo-login',
	),
	'apollo_recaptcha_site_key'   => array(
		'type'    => 'string',
		'default' => '',
		'owner'   => 'apollo-login',
	),
	'apollo_recaptcha_secret_key' => array(
		'type'    => 'string',
		'default' => '',
		'owner'   => 'apollo-login',
	),
	'apollo_max_login_attempts'   => array(
		'type'    => 'integer',
		'default' => 5,
		'owner'   => 'apollo-login',
	),
	'apollo_lockout_duration'     => array(
		'type'    => 'integer',
		'default' => 900,
		'owner'   => 'apollo-login',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * PLUGIN DB VERSIONS  (upgrade check pattern)
	 * ═══════════════════════════════════════════════════════════════════ */
	'apollo_docs_db_version'      => array(
		'type'    => 'integer',
		'default' => 0,
		'owner'   => 'apollo-docs',
	),
	'apollo_sign_db_version'      => array(
		'type'    => 'integer',
		'default' => 0,
		'owner'   => 'apollo-sign',
	),
	'apollo_gestor_db_version'    => array(
		'type'    => 'integer',
		'default' => 0,
		'owner'   => 'apollo-gestor',
	),
	'apollo_chat_db_version'      => array(
		'type'    => 'integer',
		'default' => 0,
		'owner'   => 'apollo-chat',
	),
	'apollo_email_db_version'     => array(
		'type'    => 'integer',
		'default' => 0,
		'owner'   => 'apollo-email',
	),
	'apollo_sheets_db_version'    => array(
		'type'    => 'integer',
		'default' => 0,
		'owner'   => 'apollo-sheets',
	),
	'apollo_seo_db_version'       => array(
		'type'    => 'integer',
		'default' => 0,
		'owner'   => 'apollo-seo',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * SEO
	 * ═══════════════════════════════════════════════════════════════════ */
	'apollo_seo_settings'         => array(
		'type'    => 'array',
		'default' => array(),
		'owner'   => 'apollo-seo',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * EMAIL
	 * ═══════════════════════════════════════════════════════════════════ */
	'apollo_email_from_name'      => array(
		'type'    => 'string',
		'default' => 'Apollo',
		'owner'   => 'apollo-email',
	),
	'apollo_email_from_address'   => array(
		'type'    => 'string',
		'default' => '',
		'owner'   => 'apollo-email',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * CHAT
	 * ═══════════════════════════════════════════════════════════════════ */
	'apollo_chat_poll_interval'   => array(
		'type'    => 'integer',
		'default' => 3000,
		'owner'   => 'apollo-chat',
	),
	'apollo_chat_typing_ttl'      => array(
		'type'    => 'integer',
		'default' => 3000,
		'owner'   => 'apollo-chat',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * ADMIN
	 * ═══════════════════════════════════════════════════════════════════ */
	'apollo_admin_settings'       => array(
		'type'    => 'array',
		'default' => array(),
		'owner'   => 'apollo-admin',
	),
);
