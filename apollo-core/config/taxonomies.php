<?php
/**
 * Apollo Ecosystem — Taxonomy Definitions
 *
 * All 18 taxonomies defined centrally. SINGLE SOURCE OF TRUTH.
 * TaxonomyRegistry loads from this file via ConfigLoader.
 *
 * Structure: slug => [ owner, slug, rewrite, object_types, hierarchical, labels, default_terms, ... ]
 *
 * @package Apollo\Core
 * @since   6.1.0
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────────────────────────────────
// CPT groupings for object_types
// ─────────────────────────────────────────────────────────────────────────
$all_cpts        = array( 'event', 'dj', 'local', 'classified', 'supplier', 'doc', 'email_aprio', 'hub' );
/*
 * `track` added 2026-08-17 — this was a REGRESSION, caught by the CPT audit.
 *
 * The track CPT was registered the same day with `taxonomies => array( sound )`
 * on apollo-djs' owner registration, and the rationale for reusing `sound`
 * rather than minting a second genre vocabulary was written into
 * config/cpts.php. None of that took effect: apollo-core wins the init:5 race
 * (it hooks from plugins_loaded:1, owners from :15), and CPTRegistry::register_cpt()
 * never forwards `taxonomies`. So the ONLY thing that actually attaches a
 * taxonomy to a CPT is this array — and `track` was missing from it.
 *
 * Net effect until now: /track posts had no genre at all, and the DJ surface's
 * sound filter could never see them.
 */
$sound_cpts      = array( 'event', 'dj', 'track' );
$season_cpts     = array( 'event', 'classified' );
$event_cpts      = array( 'event' );
$loc_cpts        = array( 'local' );
$classified_cpts = array( 'classified' );
$doc_cpts        = array( 'doc' );
$supplier_cpts   = array( 'supplier' );

return array(

	// ═══════════════════════════════════════════════════════════════════
	// SOUND — Global music genre bridge (event + dj + user prefs)
	// ═══════════════════════════════════════════════════════════════════
	'sound'             => array(
		'owner'             => 'apollo-core',
		'slug'              => 'sound',
		'rewrite'           => 'som',
		'object_types'      => $sound_cpts,
		'hierarchical'      => true,
		'is_bridge'         => true,
		'show_admin_column' => true,
		'labels'            => array(
			'name'              => 'Gêneros Musicais',
			'singular_name'     => 'Gênero Musical',
			'search_items'      => 'Buscar Gêneros',
			'all_items'         => 'Todos os Gêneros',
			'parent_item'       => 'Gênero Pai',
			'parent_item_colon' => 'Gênero Pai:',
			'edit_item'         => 'Editar Gênero',
			'update_item'       => 'Atualizar Gênero',
			'add_new_item'      => 'Adicionar Novo Gênero',
			'new_item_name'     => 'Novo Nome de Gênero',
			'menu_name'         => 'Gêneros Musicais',
		),
		'default_terms'     => array(
			'Techno'          => array( 'Minimal Techno', 'Industrial Techno', 'Melodic Techno', 'Hard Techno', 'Detroit Techno' ),
			'House'           => array( 'Deep House', 'Tech House', 'Progressive House', 'Afro House', 'Organic House', 'Melodic House' ),
			'Trance'          => array( 'Progressive Trance', 'Psy Trance', 'Uplifting Trance', 'Tech Trance' ),
			'Drum & Bass'     => array( 'Liquid DnB', 'Neurofunk', 'Jump Up', 'Jungle' ),
			'Dubstep'         => array( 'Brostep', 'Riddim', 'Deep Dubstep' ),
			'Breaks'          => array( 'Breakbeat', 'Big Beat', 'Nu Breaks' ),
			'Downtempo'       => array( 'Chillout', 'Ambient', 'Trip Hop', 'Lo-Fi' ),
			'Bass Music'      => array( 'UK Bass', 'Future Bass', 'Trap', 'Grime' ),
			'Funk Brasileiro' => array( 'Funk Carioca', 'Funk Paulista', 'Funk Melody', 'Funk Proibidão' ),
			'Baile Funk'      => array(),
			'Piseiro'         => array(),
			'Forró'           => array( 'Forró Eletrônico', 'Forró Pé de Serra' ),
			'Reggaeton'       => array(),
			'Latin House'     => array(),
			'Hip Hop'         => array( 'Rap BR', 'Trap BR', 'Boom Bap' ),
			'Pop'             => array( 'Dance Pop', 'Electro Pop', 'Synth Pop' ),
			'R&B'             => array( 'Neo Soul', 'Contemporary R&B' ),
			'Disco'           => array( 'Nu Disco', 'Italo Disco', 'Space Disco' ),
			'Experimental'    => array( 'IDM', 'Glitch', 'Noise' ),
		),
	),

	// ═══════════════════════════════════════════════════════════════════
	// SEASON — Temporada (event + classified)
	// ═══════════════════════════════════════════════════════════════════
	'season'            => array(
		'owner'             => 'apollo-core',
		'slug'              => 'season',
		'rewrite'           => 'temporada',
		'object_types'      => $season_cpts,
		'hierarchical'      => true,
		'is_bridge'         => true,
		'show_admin_column' => true,
		'labels'            => array(
			'name'          => 'Temporadas',
			'singular_name' => 'Temporada',
			'search_items'  => 'Buscar Temporadas',
			'all_items'     => 'Todas as Temporadas',
			'edit_item'     => 'Editar Temporada',
			'update_item'   => 'Atualizar Temporada',
			'add_new_item'  => 'Adicionar Nova Temporada',
			'new_item_name' => 'Nova Temporada',
			'menu_name'     => 'Temporadas',
		),
		'default_terms'     => array(
			'Verão 2026'     => array(),
			'Carnaval 2026'  => array(),
			'Réveillon 2026' => array(),
			'Inverno 2026'   => array(),
		),
	),

	// ═══════════════════════════════════════════════════════════════════
	// EVENT CATEGORY
	// ═══════════════════════════════════════════════════════════════════
	'event_category'    => array(
		'owner'             => 'apollo-core',
		'slug'              => 'event_category',
		'rewrite'           => 'categoria-evento',
		'object_types'      => $event_cpts,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'labels'            => array(
			'name'              => 'Categorias de Evento',
			'singular_name'     => 'Categoria',
			'search_items'      => 'Buscar Categorias',
			'all_items'         => 'Todas as Categorias',
			'parent_item'       => 'Categoria Pai',
			'parent_item_colon' => 'Categoria Pai:',
			'edit_item'         => 'Editar Categoria',
			'update_item'       => 'Atualizar Categoria',
			'add_new_item'      => 'Adicionar Nova Categoria',
			'new_item_name'     => 'Nova Categoria',
			'menu_name'         => 'Categorias',
		),
		'default_terms'     => array(
			'Festival'    => array(),
			'Club Night'  => array(),
			'Rave'        => array(),
			'Pool Party'  => array(),
			'Boat Party'  => array(),
			'Rooftop'     => array(),
			'Underground' => array(),
			'Warehouse'   => array(),
			'Open Air'    => array(),
			'After Party' => array(),
		),
	),

	// ═══════════════════════════════════════════════════════════════════
	// EVENT TYPE
	// ═══════════════════════════════════════════════════════════════════
	'event_type'        => array(
		'owner'             => 'apollo-core',
		'slug'              => 'event_type',
		'rewrite'           => 'tipo-evento',
		'object_types'      => $event_cpts,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'labels'            => array(
			'name'          => 'Tipos de Evento',
			'singular_name' => 'Tipo',
			'search_items'  => 'Buscar Tipos',
			'all_items'     => 'Todos os Tipos',
			'edit_item'     => 'Editar Tipo',
			'update_item'   => 'Atualizar Tipo',
			'add_new_item'  => 'Adicionar Novo Tipo',
			'new_item_name' => 'Novo Tipo',
			'menu_name'     => 'Tipos',
		),
		'default_terms'     => array(
			'Público'  => array(),
			'Privado'  => array(),
			'Convite'  => array(),
			'Gratuito' => array(),
			'Pago'     => array(),
		),
	),

	// ═══════════════════════════════════════════════════════════════════
	// EVENT TAG
	// ═══════════════════════════════════════════════════════════════════
	'event_tag'         => array(
		'owner'             => 'apollo-core',
		'slug'              => 'event_tag',
		'rewrite'           => 'tag-evento',
		'object_types'      => $event_cpts,
		'hierarchical'      => false,
		'show_admin_column' => false,
		'labels'            => array(
			'name'          => 'Tags de Evento',
			'singular_name' => 'Tag',
			'search_items'  => 'Buscar Tags',
			'all_items'     => 'Todas as Tags',
			'edit_item'     => 'Editar Tag',
			'update_item'   => 'Atualizar Tag',
			'add_new_item'  => 'Adicionar Nova Tag',
			'new_item_name' => 'Nova Tag',
			'menu_name'     => 'Tags',
		),
		'default_terms'     => array(),
	),

	// ═══════════════════════════════════════════════════════════════════
	// LOCAL TYPE
	// ═══════════════════════════════════════════════════════════════════
	'local_type'        => array(
		'owner'             => 'apollo-core',
		'slug'              => 'local_type',
		'rewrite'           => 'tipo-local',
		'object_types'      => $loc_cpts,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'labels'            => array(
			'name'          => 'Tipos de Local',
			'singular_name' => 'Tipo de Local',
			'search_items'  => 'Buscar Tipos',
			'all_items'     => 'Todos os Tipos',
			'edit_item'     => 'Editar Tipo',
			'update_item'   => 'Atualizar Tipo',
			'add_new_item'  => 'Adicionar Novo Tipo',
			'new_item_name' => 'Novo Tipo',
			'menu_name'     => 'Tipos',
		),
		'default_terms'     => array(
			'Club'          => array(),
			'Bar'           => array(),
			'Restaurante'   => array(),
			'Praia'         => array(),
			'Rooftop'       => array(),
			'Galpão'        => array(),
			'Teatro'        => array(),
			'Arena'         => array(),
			'Espaço Aberto' => array(),
		),
	),

	// ═══════════════════════════════════════════════════════════════════
	// LOCAL AREA (Zona)
	// ═══════════════════════════════════════════════════════════════════
	'local_area'        => array(
		'owner'             => 'apollo-core',
		'slug'              => 'local_area',
		'rewrite'           => 'zona',
		'object_types'      => $loc_cpts,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'labels'            => array(
			'name'              => 'Zonas',
			'singular_name'     => 'Zona',
			'search_items'      => 'Buscar Zonas',
			'all_items'         => 'Todas as Zonas',
			'parent_item'       => 'Zona Pai',
			'parent_item_colon' => 'Zona Pai:',
			'edit_item'         => 'Editar Zona',
			'update_item'       => 'Atualizar Zona',
			'add_new_item'      => 'Adicionar Nova Zona',
			'new_item_name'     => 'Nova Zona',
			'menu_name'         => 'Zonas',
		),
		'default_terms'     => array(
			'Rio de Janeiro' => array( 'Zona Sul', 'Zona Norte', 'Zona Oeste', 'Centro', 'Barra da Tijuca', 'Niterói' ),
			'São Paulo'      => array( 'Pinheiros', 'Itaim', 'Vila Madalena', 'Centro', 'Moema', 'Jardins' ),
		),
	),

	// ═══════════════════════════════════════════════════════════════════
	// CLASSIFIED DOMAIN
	// ═══════════════════════════════════════════════════════════════════
	'classified_domain' => array(
		'owner'             => 'apollo-core',
		'slug'              => 'classified_domain',
		'rewrite'           => 'tipo-anuncio',
		'object_types'      => $classified_cpts,
		'hierarchical'      => false,
		'show_admin_column' => true,
		'labels'            => array(
			'name'          => 'Tipos de Anúncio',
			'singular_name' => 'Tipo',
			'search_items'  => 'Buscar Tipos',
			'all_items'     => 'Todos os Tipos',
			'edit_item'     => 'Editar Tipo',
			'update_item'   => 'Atualizar Tipo',
			'add_new_item'  => 'Adicionar Novo Tipo',
			'new_item_name' => 'Novo Tipo',
			'menu_name'     => 'Tipos',
		),
		'default_terms'     => array(
			'Equipamento' => array(),
			'Serviço'     => array(),
			'Emprego'     => array(),
			'Ingresso'    => array(),
			'Outros'      => array(),
		),
	),

	// ═══════════════════════════════════════════════════════════════════
	// CLASSIFIED INTENT
	// ═══════════════════════════════════════════════════════════════════
	'classified_intent' => array(
		'owner'             => 'apollo-core',
		'slug'              => 'classified_intent',
		'rewrite'           => 'intencao',
		'object_types'      => $classified_cpts,
		'hierarchical'      => false,
		'show_admin_column' => true,
		'labels'            => array(
			'name'          => 'Intenções',
			'singular_name' => 'Intenção',
			'search_items'  => 'Buscar Intenções',
			'all_items'     => 'Todas as Intenções',
			'edit_item'     => 'Editar Intenção',
			'update_item'   => 'Atualizar Intenção',
			'add_new_item'  => 'Adicionar Nova Intenção',
			'new_item_name' => 'Nova Intenção',
			'menu_name'     => 'Intenções',
		),
		'default_terms'     => array(
			'Vendo'   => array(),
			'Compro'  => array(),
			'Troco'   => array(),
			'Alugo'   => array(),
			'Procuro' => array(),
		),
	),

	// ═══════════════════════════════════════════════════════════════════
	// DOC FOLDER
	// ═══════════════════════════════════════════════════════════════════
	'doc_folder'        => array(
		'owner'             => 'apollo-core',
		'slug'              => 'doc_folder',
		'rewrite'           => 'pasta',
		'object_types'      => $doc_cpts,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'labels'            => array(
			'name'              => 'Pastas',
			'singular_name'     => 'Pasta',
			'search_items'      => 'Buscar Pastas',
			'all_items'         => 'Todas as Pastas',
			'parent_item'       => 'Pasta Pai',
			'parent_item_colon' => 'Pasta Pai:',
			'edit_item'         => 'Editar Pasta',
			'update_item'       => 'Atualizar Pasta',
			'add_new_item'      => 'Adicionar Nova Pasta',
			'new_item_name'     => 'Nova Pasta',
			'menu_name'         => 'Pastas',
		),
		'default_terms'     => array(),
	),

	// ═══════════════════════════════════════════════════════════════════
	// DOC TYPE
	// ═══════════════════════════════════════════════════════════════════
	'doc_type'          => array(
		'owner'             => 'apollo-core',
		'slug'              => 'doc_type',
		'rewrite'           => 'tipo-documento',
		'object_types'      => $doc_cpts,
		'hierarchical'      => false,
		'show_admin_column' => true,
		'labels'            => array(
			'name'          => 'Tipos de Documento',
			'singular_name' => 'Tipo',
			'search_items'  => 'Buscar Tipos',
			'all_items'     => 'Todos os Tipos',
			'edit_item'     => 'Editar Tipo',
			'update_item'   => 'Atualizar Tipo',
			'add_new_item'  => 'Adicionar Novo Tipo',
			'new_item_name' => 'Novo Tipo',
			'menu_name'     => 'Tipos',
		),
		'default_terms'     => array(
			'Contrato'     => array(),
			'Manual'       => array(),
			'Relatório'    => array(),
			'Apresentação' => array(),
		),
	),

	// ═══════════════════════════════════════════════════════════════════
	// SUPPLIER CATEGORY
	// ═══════════════════════════════════════════════════════════════════
	'supplier_category' => array(
		'owner'             => 'apollo-core',
		'slug'              => 'supplier_category',
		'rewrite'           => 'categoria-fornecedor',
		'object_types'      => $supplier_cpts,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'labels'            => array(
			'name'          => 'Categorias de Fornecedor',
			'singular_name' => 'Categoria',
			'search_items'  => 'Buscar Categorias',
			'all_items'     => 'Todas as Categorias',
			'edit_item'     => 'Editar Categoria',
			'update_item'   => 'Atualizar Categoria',
			'add_new_item'  => 'Adicionar Nova Categoria',
			'new_item_name' => 'Nova Categoria',
			'menu_name'     => 'Categorias',
		),
		'default_terms'     => array(
			'Iluminação' => array(),
			'Som'        => array(),
			'Decoração'  => array(),
			'Segurança'  => array(),
			'Buffet'     => array(),
			'Transporte' => array(),
		),
	),

	// ═══════════════════════════════════════════════════════════════════
	// SUPPLIER SERVICE
	// ═══════════════════════════════════════════════════════════════════
	'supplier_service'  => array(
		'owner'             => 'apollo-core',
		'slug'              => 'supplier_service',
		'rewrite'           => 'servico-fornecedor',
		'object_types'      => $supplier_cpts,
		'hierarchical'      => false,
		'show_admin_column' => true,
		'labels'            => array(
			'name'          => 'Serviços',
			'singular_name' => 'Serviço',
			'search_items'  => 'Buscar Serviços',
			'all_items'     => 'Todos os Serviços',
			'edit_item'     => 'Editar Serviço',
			'update_item'   => 'Atualizar Serviço',
			'add_new_item'  => 'Adicionar Novo Serviço',
			'new_item_name' => 'Novo Serviço',
			'menu_name'     => 'Serviços',
		),
		'default_terms'     => array(),
	),

	// ═══════════════════════════════════════════════════════════════════
	// MUSIC — Journal editorial taxonomy (post)
	// ═══════════════════════════════════════════════════════════════════
	'music'             => array(
		'owner'             => 'apollo-journal',
		'slug'              => 'music',
		'rewrite'           => 'music',
		'object_types'      => array( 'post' ),
		'hierarchical'      => true,
		'show_admin_column' => true,
		'labels'            => array(
			'name'              => 'Música',
			'singular_name'     => 'Gênero Musical',
			'search_items'      => 'Pesquisar Música',
			'all_items'         => 'Todos',
			'parent_item'       => 'Gênero Musical pai',
			'parent_item_colon' => 'Gênero Musical pai:',
			'edit_item'         => 'Editar Gênero Musical',
			'update_item'       => 'Atualizar Gênero Musical',
			'add_new_item'      => 'Adicionar Gênero Musical',
			'new_item_name'     => 'Novo Gênero Musical',
			'menu_name'         => 'Música',
		),
		'default_terms'     => array(),
	),

	// ═══════════════════════════════════════════════════════════════════
	// CULTURE — Journal editorial taxonomy (post)
	// ═══════════════════════════════════════════════════════════════════
	'culture'           => array(
		'owner'             => 'apollo-journal',
		'slug'              => 'culture',
		'rewrite'           => 'culture',
		'object_types'      => array( 'post' ),
		'hierarchical'      => true,
		'show_admin_column' => true,
		'labels'            => array(
			'name'              => 'Cultura',
			'singular_name'     => 'Cultura',
			'search_items'      => 'Pesquisar Cultura',
			'all_items'         => 'Todos',
			'parent_item'       => 'Cultura pai',
			'parent_item_colon' => 'Cultura pai:',
			'edit_item'         => 'Editar Cultura',
			'update_item'       => 'Atualizar Cultura',
			'add_new_item'      => 'Adicionar Cultura',
			'new_item_name'     => 'Nova Cultura',
			'menu_name'         => 'Cultura',
		),
		'default_terms'     => array(),
	),

	// ═══════════════════════════════════════════════════════════════════
	// RIO — Journal editorial taxonomy (post)
	// ═══════════════════════════════════════════════════════════════════
	'rio'               => array(
		'owner'             => 'apollo-journal',
		'slug'              => 'rio',
		'rewrite'           => 'rio',
		'object_types'      => array( 'post' ),
		'hierarchical'      => true,
		'show_admin_column' => true,
		'labels'            => array(
			'name'              => 'Rio',
			'singular_name'     => 'Região',
			'search_items'      => 'Pesquisar Rio',
			'all_items'         => 'Todos',
			'parent_item'       => 'Região pai',
			'parent_item_colon' => 'Região pai:',
			'edit_item'         => 'Editar Região',
			'update_item'       => 'Atualizar Região',
			'add_new_item'      => 'Adicionar Região',
			'new_item_name'     => 'Nova Região',
			'menu_name'         => 'Rio',
		),
		'default_terms'     => array(),
	),

	// ═══════════════════════════════════════════════════════════════════
	// FORMATO — Journal editorial taxonomy (post)
	// ═══════════════════════════════════════════════════════════════════
	'formato'           => array(
		'owner'             => 'apollo-journal',
		'slug'              => 'formato',
		'rewrite'           => 'formato',
		'object_types'      => array( 'post' ),
		'hierarchical'      => false,
		'show_admin_column' => true,
		'labels'            => array(
			'name'          => 'Formato',
			'singular_name' => 'Formato',
			'search_items'  => 'Pesquisar Formato',
			'all_items'     => 'Todos',
			'edit_item'     => 'Editar Formato',
			'update_item'   => 'Atualizar Formato',
			'add_new_item'  => 'Adicionar Formato',
			'new_item_name' => 'Novo Formato',
			'menu_name'     => 'Formato',
		),
		'default_terms'     => array(),
	),

	// ═══════════════════════════════════════════════════════════════════
	// COAUTHOR (hidden, for co-author system) — GLOBAL all CPTs
	// ═══════════════════════════════════════════════════════════════════
	'coauthor'          => array(
		'owner'             => 'apollo-core',
		'slug'              => 'coauthor',
		'rewrite'           => false,
		'object_types'      => $all_cpts,
		'hierarchical'      => false,
		'show_admin_column' => false,
		'show_ui'           => false,
		'labels'            => array(
			'name'          => 'Co-Autores',
			'singular_name' => 'Co-Autor',
			'search_items'  => 'Buscar Co-Autores',
			'all_items'     => 'Todos os Co-Autores',
			'edit_item'     => 'Editar Co-Autor',
			'update_item'   => 'Atualizar Co-Autor',
			'add_new_item'  => 'Adicionar Co-Autor',
			'new_item_name' => 'Novo Co-Autor',
			'menu_name'     => 'Co-Autores',
		),
		'default_terms'     => array(),
	),
);
