<?php

declare(strict_types=1);

/**
 * Apollo Ecosystem — Central Constants
 *
 * Single source of truth for ALL constants across 32 plugins.
 * Loaded by apollo-core bootstrap BEFORE any plugin initializes.
 *
 * @package Apollo\Core
 * @since   6.1.0
 */

if (! defined('ABSPATH')) {
    exit;
}

return array(

    /*
	═══════════════════════════════════════════════════════════════════
	 * VERSION
	 * ═══════════════════════════════════════════════════════════════════ */

    'APOLLO_VERSION'               => '6.0.0',
    'APOLLO_CORE_VERSION'          => '6.0.0',
    'APOLLO_CDN_VERSION'           => '1.0.0',
    'APOLLO_REGISTRY_VER'          => '6.2.0',

    /*
	═══════════════════════════════════════════════════════════════════
	 * ENVIRONMENT
	 * ═══════════════════════════════════════════════════════════════════ */

    'APOLLO_MIN_PHP'               => '8.1',
    'APOLLO_MIN_WP'                => '6.4',
    'APOLLO_REST_NAMESPACE'        => 'apollo/v1',
    'APOLLO_TABLE_PREFIX'          => 'apollo_',
    'APOLLO_META_PREFIX'           => '_apollo_',

    /*
	═══════════════════════════════════════════════════════════════════
	 * CDN
	 * ═══════════════════════════════════════════════════════════════════ */

    'APOLLO_CDN_URL'               => 'https://cdn.apollo.rio.br/v1.0.0/',
    /** Bump one character to bust CDN/browser cache (also set in core.js CDN_Q). */
    'APOLLO_CDN_Q'                 => 'v=t0x7',
    /** Cache-bust suffix on core.js — bump to force browser reload of core.js. */
    'APOLLO_CDN_SUFFIX'            => '000',
    'APOLLO_CDN_CORE_JS'           => 'https://cdn.apollo.rio.br/v1.0.0/core.js?versao=bb',

    /*
	═══════════════════════════════════════════════════════════════════
	 * PATHS  (apollo-registry.json em wp-content; sem plugins/_inventory)
	 * ═══════════════════════════════════════════════════════════════════ */

    'APOLLO_REGISTRY_PATH'         => WP_CONTENT_DIR . '/apollo-registry.json',

    /*
	═══════════════════════════════════════════════════════════════════
	 * CPT SLUGS
	 * ═══════════════════════════════════════════════════════════════════ */

    'APOLLO_CPT_EVENT'             => 'event',
    'APOLLO_CPT_DJ'                => 'dj',
    'APOLLO_CPT_LOCAL'             => 'local',
    'APOLLO_CPT_CLASSIFIED'        => 'classified',
    'APOLLO_CPT_SUPPLIER'          => 'supplier',
    'APOLLO_CPT_DOC'               => 'doc',
    'APOLLO_CPT_EMAIL'             => 'email_aprio',
    'APOLLO_CPT_HUB'               => 'hub',
    'APOLLO_CPT_SHEET'             => 'apollo_sheet',

    /*
	═══════════════════════════════════════════════════════════════════
	 * TAXONOMY SLUGS
	 * ═══════════════════════════════════════════════════════════════════ */

    'APOLLO_TAX_SOUND'             => 'sound',
    'APOLLO_TAX_SEASON'            => 'season',
    'APOLLO_TAX_EVENT_CATEGORY'    => 'event_category',
    'APOLLO_TAX_EVENT_TYPE'        => 'event_type',
    'APOLLO_TAX_EVENT_TAG'         => 'event_tag',
    'APOLLO_TAX_LOCAL_TYPE'        => 'local_type',
    'APOLLO_TAX_LOCAL_AREA'        => 'local_area',
    'APOLLO_TAX_CLASSIFIED_DOMAIN' => 'classified_domain',
    'APOLLO_TAX_CLASSIFIED_INTENT' => 'classified_intent',
    'APOLLO_TAX_DOC_FOLDER'        => 'doc_folder',
    'APOLLO_TAX_DOC_TYPE'          => 'doc_type',
    'APOLLO_TAX_SUPPLIER_CATEGORY' => 'supplier_category',
    'APOLLO_TAX_SUPPLIER_SERVICE'  => 'supplier_service',
    'APOLLO_TAX_COAUTHOR'          => 'coauthor',

    /*
	═══════════════════════════════════════════════════════════════════
	 * URL PATTERNS
	 * ═══════════════════════════════════════════════════════════════════ */

    'APOLLO_URL_PROFILE'           => '/id/',
    'APOLLO_URL_HUB'               => '/hub/',
    'APOLLO_URL_CHAT'              => '/mensagens/',
    'APOLLO_URL_LOGIN'             => '/acesso',
    'APOLLO_URL_REGISTER'          => '/registre',
    'APOLLO_URL_EXPLORE'           => '/explore',
    'APOLLO_URL_DASHBOARD'         => '/painel',
    'APOLLO_URL_DOCUMENTS'         => '/documentos',
    'APOLLO_URL_NOTIFICATIONS'     => '/notificacoes',
);
