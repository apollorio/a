<?php

/**
 * Apollo Ecosystem — CPT Definitions
 *
 * All 9 Custom Post Types defined centrally.
 * Used by CPTRegistry for fallback registration.
 *
 * Structure: slug => [ owner, rewrite, archive, rest_base, public, supports, labels, menu_icon ]
 *
 * @package Apollo\Core
 * @since   6.1.0
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

return array(

    /*
	═══════════════════════════════════════════════════════════════════
	 * EVENT — Owner: apollo-events
	 * ═══════════════════════════════════════════════════════════════════ */
    'event'        => array(
        'owner'       => 'apollo-events',
        'slug'        => 'event',
        'rewrite'     => 'evento',
        'archive'     => 'eventos',
        'rest_base'   => 'events',
        'public'      => true,
        'has_archive' => true,
        'supports'    => array('title', 'editor', 'thumbnail', 'author'),
        'labels'      => array(
            'name'               => 'Eventos',
            'singular_name'      => 'Evento',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Evento',
            'edit_item'          => 'Editar Evento',
            'new_item'           => 'Novo Evento',
            'view_item'          => 'Ver Evento',
            'search_items'       => 'Buscar Eventos',
            'not_found'          => 'Nenhum evento encontrado',
            'not_found_in_trash' => 'Nenhum evento na lixeira',
            'menu_name'          => 'Eventos',
        ),
        'menu_icon'   => 'dashicons-calendar-alt',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * DJ — Owner: apollo-djs
	 * ═══════════════════════════════════════════════════════════════════ */
    'dj'           => array(
        'owner'       => 'apollo-djs',
        'slug'        => 'dj',
        'rewrite'     => 'dj',
        'archive'     => 'djs',
        'rest_base'   => 'djs',
        'public'      => true,
        'has_archive' => true,
        'supports'    => array('title', 'editor', 'thumbnail', 'author'),
        'labels'      => array(
            'name'               => 'DJs',
            'singular_name'      => 'DJ',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo DJ',
            'edit_item'          => 'Editar DJ',
            'new_item'           => 'Novo DJ',
            'view_item'          => 'Ver DJ',
            'search_items'       => 'Buscar DJs',
            'not_found'          => 'Nenhum DJ encontrado',
            'not_found_in_trash' => 'Nenhum DJ na lixeira',
            'menu_name'          => 'DJs',
        ),
        'menu_icon'   => 'dashicons-format-audio',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * TRACK — Owner: apollo-djs  (URL: /track/{slug}, archive /tracks)
	 *
	 * "Out Now" releases. Was array-of-object post meta on the dj CPT
	 * (`_dj_tracks`, schema v2). Promoted to a CPT on 2026-08-17 for four
	 * structural reasons, not for tidiness:
	 *
	 *  1. AUTHORSHIP. "DJs can add, author automatic" is post_author — a
	 *     post-level fact. A meta row on someone else's post has no author
	 *     and cannot be given one.
	 *  2. FRONTEND EDITING WAS IMPOSSIBLE. FrontendEditor::sanitize_field()
	 *     (apollo-templates/src/FrontendEditor.php:900) returns string and
	 *     has no array branch, so a repeater can never be edited from the
	 *     front end. Flat scalars on a post need nothing new.
	 *  3. A RELEASE CREDITS MORE THAN ONE ARTIST. Meta on one DJ cannot
	 *     express a B2B or a remix without duplicating the row onto both,
	 *     and two copies drift.
	 *  4. THE ARCHIVE WAS ALREADY LINKED. apollo-templates
	 *     new-home/tracks.php pointed at a releases archive that existed
	 *     nowhere. has_archive supplies it.
	 *
	 * `_dj_tracks` stays registered and readable through the migration —
	 * apollo_dj_get_tracks() merges both sources — so nothing on screen
	 * changes the day this ships.
	 * ═══════════════════════════════════════════════════════════════════ */
    'track'        => array(
        'owner'       => 'apollo-djs',
        'slug'        => 'track',
        'rewrite'     => 'track',
        'archive'     => 'tracks',
        'rest_base'   => 'tracks',
        'public'      => true,
        'has_archive' => true,
        // 'author' is the whole point — see reason 1 above.
        'supports'    => array('title', 'editor', 'thumbnail', 'author'),
        'labels'      => array(
            'name'               => 'Faixas',
            'singular_name'      => 'Faixa',
            'add_new'            => 'Adicionar Nova',
            'add_new_item'       => 'Adicionar Nova Faixa',
            'edit_item'          => 'Editar Faixa',
            'new_item'           => 'Nova Faixa',
            'view_item'          => 'Ver Faixa',
            'search_items'       => 'Buscar Faixas',
            'not_found'          => 'Nenhuma faixa encontrada',
            'not_found_in_trash' => 'Nenhuma faixa na lixeira',
            'menu_name'          => 'Out Now',
        ),
        'menu_icon'   => 'dashicons-album',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * HOSTEL — Owner: apollo-adverts  (URL: /hostel/{slug})
	 *
	 * Official accommodation providers. An advert relates EITHER to a user
	 * (a member's own offer) OR to a hostel — never both — through
	 * `_classified_hostel_id`, and ONLY an administrator can set that
	 * relation. It is an editorial claim, not a seller preference: it
	 * decides whether a listing bypasses the auth gate and is shown to the
	 * whole internet, so it lives in APOLLO_ADVERTS_ADMIN_ONLY_META beside
	 * the flag it replaces.
	 *
	 * Supersedes the `_classified_hostel` boolean, which could mark a
	 * listing as official but could not answer "show me THIS hostel's
	 * page". The boolean is kept as a read fallback for one release.
	 *
	 * NOTE, recorded rather than silently resolved: `local` already
	 * registers address, coords and gallery meta. A hostel is a distinct
	 * business entity from a venue, so this is a separate CPT by decision
	 * (2026-08-17) — but if a hostel ever needs a map or a gallery, relate
	 * it to a `local` rather than re-registering those keys here. Two
	 * copies of address meta is the divergence 09-plugins/apollo-adverts.json
	 * already flags at $accommodation_meta_and_depoimentos_2026_07_28.
	 * ═══════════════════════════════════════════════════════════════════ */
    'hostel'       => array(
        'owner'       => 'apollo-adverts',
        'slug'        => 'hostel',
        'rewrite'     => 'hostel',
        'archive'     => 'hostels',
        'rest_base'   => 'hostels',
        'public'      => true,
        'has_archive' => true,
        'supports'    => array('title', 'editor', 'thumbnail'),
        'labels'      => array(
            'name'               => 'Hostels',
            'singular_name'      => 'Hostel',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Hostel',
            'edit_item'          => 'Editar Hostel',
            'new_item'           => 'Novo Hostel',
            'view_item'          => 'Ver Hostel',
            'search_items'       => 'Buscar Hostels',
            'not_found'          => 'Nenhum hostel encontrado',
            'not_found_in_trash' => 'Nenhum hostel na lixeira',
            'menu_name'          => 'Hostels',
        ),
        'menu_icon'   => 'dashicons-building',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * LOCAL — Owner: apollo-loc  (URL: /local/{slug})
	 * ═════════════════════════════════════════════════════════════════ */
    'local'        => array(
        'owner'       => 'apollo-loc',
        'slug'        => 'local',
        'rewrite'     => 'local',
        'archive'     => 'locais',
        'rest_base'   => 'local',
        'public'      => true,
        'has_archive' => true,
        'supports'    => array('title', 'editor', 'thumbnail'),
        'labels'      => array(
            'name'               => 'Locais',
            'singular_name'      => 'Local',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Local',
            'edit_item'          => 'Editar Local',
            'new_item'           => 'Novo Local',
            'view_item'          => 'Ver Local',
            'search_items'       => 'Buscar Locais',
            'not_found'          => 'Nenhum local encontrado',
            'not_found_in_trash' => 'Nenhum local na lixeira',
            'menu_name'          => 'Locais',
        ),
        'menu_icon'   => 'dashicons-location',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * CLASSIFIED — Owner: apollo-adverts
	 * ═══════════════════════════════════════════════════════════════════ */
    'classified'   => array(
        'owner'       => 'apollo-adverts',
        'slug'        => 'classified',
        'rewrite'     => 'anuncio',
        'archive'     => 'anuncios',
        'rest_base'   => 'classifieds',
        'public'      => true,
        'has_archive' => true,
        'supports'    => array('title', 'editor', 'thumbnail', 'author'),
        'labels'      => array(
            'name'               => 'Anúncios',
            'singular_name'      => 'Anúncio',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Anúncio',
            'edit_item'          => 'Editar Anúncio',
            'new_item'           => 'Novo Anúncio',
            'view_item'          => 'Ver Anúncio',
            'search_items'       => 'Buscar Anúncios',
            'not_found'          => 'Nenhum anúncio encontrado',
            'not_found_in_trash' => 'Nenhum anúncio na lixeira',
            'menu_name'          => 'Classificados',
        ),
        'menu_icon'   => 'dashicons-megaphone',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * SUPPLIER — Owner: apollo-suppliers  (industry-only)
	 * ═══════════════════════════════════════════════════════════════════ */
    'supplier'     => array(
        'owner'       => 'apollo-suppliers',
        'slug'        => 'supplier',
        'rewrite'     => 'fornecedor',
        'archive'     => 'fornecedores',
        'rest_base'   => 'suppliers',
        'public'      => false,
        'has_archive' => false,
        'supports'    => array('title', 'editor', 'thumbnail'),
        'labels'      => array(
            'name'               => 'Fornecedores',
            'singular_name'      => 'Fornecedor',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Fornecedor',
            'edit_item'          => 'Editar Fornecedor',
            'new_item'           => 'Novo Fornecedor',
            'view_item'          => 'Ver Fornecedor',
            'search_items'       => 'Buscar Fornecedores',
            'not_found'          => 'Nenhum fornecedor encontrado',
            'not_found_in_trash' => 'Nenhum fornecedor na lixeira',
            'menu_name'          => 'Fornecedores',
        ),
        'menu_icon'   => 'dashicons-store',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * DOC — Owner: apollo-docs
	 * ═══════════════════════════════════════════════════════════════════ */
    'doc'          => array(
        'owner'       => 'apollo-docs',
        'slug'        => 'doc',
        'rewrite'     => 'documento',
        'archive'     => false,
        'rest_base'   => 'docs',
        'public'      => false,
        'has_archive' => false,
        'supports'    => array('title', 'editor', 'author'),
        'labels'      => array(
            'name'               => 'Documentos',
            'singular_name'      => 'Documento',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Documento',
            'edit_item'          => 'Editar Documento',
            'new_item'           => 'Novo Documento',
            'view_item'          => 'Ver Documento',
            'search_items'       => 'Buscar Documentos',
            'not_found'          => 'Nenhum documento encontrado',
            'not_found_in_trash' => 'Nenhum documento na lixeira',
            'menu_name'          => 'Documentos',
        ),
        'menu_icon'   => 'dashicons-media-document',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * EMAIL_APRIO — Owner: apollo-email  (aprio = ApolloRIO)
	 * ═══════════════════════════════════════════════════════════════════ */
    'email_aprio'  => array(
        'owner'       => 'apollo-email',
        'slug'        => 'email_aprio',
        'rewrite'     => false,
        'archive'     => false,
        'rest_base'   => 'email-templates',
        'public'      => false,
        'has_archive' => false,
        'supports'    => array('title', 'editor'),
        'labels'      => array(
            'name'               => 'Email Templates',
            'singular_name'      => 'Email Template',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Template',
            'edit_item'          => 'Editar Template',
            'new_item'           => 'Novo Template',
            'view_item'          => 'Ver Template',
            'search_items'       => 'Buscar Templates',
            'not_found'          => 'Nenhum template encontrado',
            'not_found_in_trash' => 'Nenhum template na lixeira',
            'menu_name'          => 'Email Templates',
        ),
        'menu_icon'   => 'dashicons-email-alt',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * HUB — Owner: apollo-hub  (Linktree-style /hub/{username})
	 * ═══════════════════════════════════════════════════════════════════ */
    'hub'          => array(
        'owner'       => 'apollo-hub',
        'slug'        => 'hub',
        'rewrite'     => 'hub',
        'archive'     => false,
        'rest_base'   => 'hubs',
        'public'      => true,
        'has_archive' => false,
        'supports'    => array('title', 'author'),
        'labels'      => array(
            'name'               => 'Hubs',
            'singular_name'      => 'Hub',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Hub',
            'edit_item'          => 'Editar Hub',
            'new_item'           => 'Novo Hub',
            'view_item'          => 'Ver Hub',
            'search_items'       => 'Buscar Hubs',
            'not_found'          => 'Nenhum hub encontrado',
            'not_found_in_trash' => 'Nenhum hub na lixeira',
            'menu_name'          => 'Hubs',
        ),
        'menu_icon'   => 'dashicons-admin-links',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * APOLLO_SHEET — Owner: apollo-sheets
	 * ═══════════════════════════════════════════════════════════════════ */
    'apollo_sheet' => array(
        'owner'       => 'apollo-sheets',
        'slug'        => 'apollo_sheet',
        'rewrite'     => false,
        'archive'     => false,
        'rest_base'   => 'sheets',
        'public'      => false,
        /* Mirrors apollo-sheets/src/Plugin.php::register_post_type(). Sheets are
           reached through apollo-sheets' own SheetsController on apollo/v1, never
           the stock wp/v2 posts controller. Without these three keys core's
           init:5 fallback published apollo_sheet at /wp-json/wp/v2/sheets. */
        'show_in_rest' => false,
        'show_ui'      => false,
        'map_meta_cap' => true,
        'has_archive' => false,
        'supports'    => array('title', 'editor', 'excerpt', 'revisions', 'author'),
        'labels'      => array(
            'name'               => 'Sheets',
            'singular_name'      => 'Sheet',
            'add_new'            => 'Adicionar Nova',
            'add_new_item'       => 'Adicionar Nova Sheet',
            'edit_item'          => 'Editar Sheet',
            'new_item'           => 'Nova Sheet',
            'view_item'          => 'Ver Sheet',
            'search_items'       => 'Buscar Sheets',
            'not_found'          => 'Nenhuma sheet encontrada',
            'not_found_in_trash' => 'Nenhuma sheet na lixeira',
            'menu_name'          => 'Sheets',
        ),
        'menu_icon'   => 'dashicons-editor-table',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * APPOINTMENT — Owner: apollo-scheduler
	 * ═══════════════════════════════════════════════════════════════════ */
    'appointment'  => array(
        'owner'       => 'apollo-scheduler',
        'slug'        => 'appointment',
        'rewrite'     => 'agendamento',
        'archive'     => 'agendamentos',
        'rest_base'   => 'appointments',
        'public'      => true,
        'has_archive' => true,
        'supports'    => array( 'title', 'editor', 'author' ),
        'labels'      => array(
            'name'               => 'Agendamentos',
            'singular_name'      => 'Agendamento',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Agendamento',
            'edit_item'          => 'Editar Agendamento',
            'new_item'           => 'Novo Agendamento',
            'view_item'          => 'Ver Agendamento',
            'search_items'       => 'Buscar Agendamentos',
            'not_found'          => 'Nenhum agendamento encontrado',
            'not_found_in_trash' => 'Nenhum agendamento na lixeira',
            'menu_name'          => 'Agendamentos',
        ),
        'menu_icon'   => 'dashicons-calendar',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * SERVICE — Owner: apollo-scheduler
	 * ═══════════════════════════════════════════════════════════════════ */
    'service'      => array(
        'owner'       => 'apollo-scheduler',
        'slug'        => 'service',
        'rewrite'     => 'servico',
        'archive'     => 'servicos',
        'rest_base'   => 'services',
        'public'      => true,
        'has_archive' => true,
        'supports'    => array( 'title', 'editor', 'author' ),
        'labels'      => array(
            'name'               => 'Serviços',
            'singular_name'      => 'Serviço',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Serviço',
            'edit_item'          => 'Editar Serviço',
            'new_item'           => 'Novo Serviço',
            'view_item'          => 'Ver Serviço',
            'search_items'       => 'Buscar Serviços',
            'not_found'          => 'Nenhum serviço encontrado',
            'not_found_in_trash' => 'Nenhum serviço na lixeira',
            'menu_name'          => 'Serviços',
        ),
        'menu_icon'   => 'dashicons-hammer',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * RESOURCE — Owner: apollo-scheduler (rooms)
	 * ═══════════════════════════════════════════════════════════════════ */
    'resource'     => array(
        'owner'       => 'apollo-scheduler',
        'slug'        => 'resource',
        'rewrite'     => 'recurso',
        'archive'     => 'recursos',
        'rest_base'   => 'resources',
        'public'      => true,
        'has_archive' => true,
        'supports'    => array( 'title', 'editor', 'author' ),
        'labels'      => array(
            'name'               => 'Recursos',
            'singular_name'      => 'Recurso',
            'add_new'            => 'Adicionar Novo',
            'add_new_item'       => 'Adicionar Novo Recurso',
            'edit_item'          => 'Editar Recurso',
            'new_item'           => 'Novo Recurso',
            'view_item'          => 'Ver Recurso',
            'search_items'       => 'Buscar Recursos',
            'not_found'          => 'Nenhum recurso encontrado',
            'not_found_in_trash' => 'Nenhum recurso na lixeira',
            'menu_name'          => 'Recursos',
        ),
        'menu_icon'   => 'dashicons-building',
    ),

    /*
	═══════════════════════════════════════════════════════════════════
	 * APOLLO_AGENT_LOG — Owner: apollo-membership (immutable audit)
	 * ═══════════════════════════════════════════════════════════════════ */
    'apollo_agent_log' => array(
        'owner'       => 'apollo-membership',
        'slug'        => 'apollo_agent_log',
        'rewrite'     => false,
        'archive'     => false,
        'rest_base'   => 'agent-log',
        'public'      => false,
        'has_archive' => false,
        'supports'    => array( 'title' ),
        'labels'      => array(
            'name'               => 'Agent Action Log',
            'singular_name'      => 'Agent Action',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Agent Action',
            'edit_item'          => 'View Agent Action',
            'new_item'           => 'New Agent Action',
            'view_item'          => 'View Agent Action',
            'search_items'       => 'Search Agent Actions',
            'not_found'          => 'No agent actions found',
            'not_found_in_trash' => 'No agent actions in trash',
            'menu_name'          => 'Agent Actions',
        ),
        'menu_icon'   => 'dashicons-list-view',
    ),
);
