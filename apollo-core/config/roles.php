<?php
/**
 * Apollo Ecosystem — Roles & Capabilities
 *
 * Central definition of all WordPress roles, membership types,
 * and form access levels.
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
	 * STANDARD WP ROLES (re-branded display names)
	 * ═══════════════════════════════════════════════════════════════════ */
	'roles'            => array(
		'administrator' => array(
			'display' => 'apollo',
			'level'   => 10,
		),
		'editor'        => array(
			'display' => 'MOD',
			'level'   => 7,
		),
		'author'        => array(
			'display' => 'cena+',
			'level'   => 5,
		),
		'contributor'   => array(
			'display' => 'cena',
			'level'   => 3,
		),
		'subscriber'    => array(
			'display' => 'clubber',
			'level'   => 1,
		),
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * MEMBERSHIP TYPES — Visual badges, NOT roles
	 *
	 * @deprecated 6.1.0 Use apollo-core/config/registry.php via MembershipRegistry.
	 * Stored in user meta: _apollo_membership (array: [profile_badge, ...access_slugs])
	 * ═══════════════════════════════════════════════════════════════════ */
	'membership_types' => array(
		'nao-verificado',
		'apollo',
		'prod',
		'dj',
		'host',
		'govern',
		'business-pers',
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * FORM ACCESS LEVELS
	 *
	 * Used by frontend forms to determine field visibility / edit rights.
	 * ═══════════════════════════════════════════════════════════════════ */
	'form_levels'      => array(
		0 => array(
			'role'   => null,
			'access' => 'none',
		),
		1 => array(
			'role'   => 'subscriber',
			'access' => 'basic',
		),
		2 => array(
			'role'   => 'contributor',
			'access' => 'enhanced',
		),
		3 => array(
			'role'   => 'author',
			'access' => 'enhanced',
		),
		4 => array(
			'role'   => 'administrator',
			'access' => 'full',
		),
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * GESTOR TEAM ROLES — Event production hierarchy
	 * ═══════════════════════════════════════════════════════════════════ */
	'gestor_roles'     => array(
		'adm'     => array(
			'label'       => 'Administrador',
			'can_manage'  => true,
			'can_finance' => true,
		),
		'gestor'  => array(
			'label'       => 'Gestor',
			'can_manage'  => true,
			'can_finance' => true,
		),
		'tgestor' => array(
			'label'       => 'Tesoureiro',
			'can_manage'  => false,
			'can_finance' => true,
		),
		'team'    => array(
			'label'       => 'Equipe',
			'can_manage'  => false,
			'can_finance' => false,
		),
	),

	/*
	═══════════════════════════════════════════════════════════════════
	 * INDUSTRY ACCESS (Cena)
	 * ═══════════════════════════════════════════════════════════════════ */
	'cena_roles'       => array(
		'member'   => array(
			'label' => 'Membro',
			'level' => 1,
		),
		'verified' => array(
			'label' => 'Verificado',
			'level' => 2,
		),
		'admin'    => array(
			'label' => 'Admin',
			'level' => 3,
		),
	),
);
