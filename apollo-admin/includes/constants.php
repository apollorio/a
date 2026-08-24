<?php
/**
 * Plugin Constants
 *
 * @package Apollo\Admin
 */

declare(strict_types=1);

namespace Apollo\Admin;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// REST API namespace
define( 'APOLLO_ADMIN_REST_NAMESPACE', 'apollo/v1' );

// Settings option key (single serialized option like uipress pattern)
define( 'APOLLO_ADMIN_OPTION_KEY', 'apollo_admin_settings' );

// Plugin layers
define(
	'APOLLO_ADMIN_LAYERS',
	array(
		'L0_foundation'    => 'Fundação',
		'L1_auth'          => 'Autenticação',
		'L2_content'       => 'Conteúdo',
		'L3_social'        => 'Social',
		'L4_communication' => 'Comunicação',
		'L5_documents'     => 'Documentos',
		'L6_frontend'      => 'Frontend',
		'L7_admin'         => 'Administração',
		'L8_industry'      => 'Industry',
		'L9_pwa'           => 'PWA',
	)
);
