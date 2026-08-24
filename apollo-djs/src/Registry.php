<?php

/**
 * Registry — CPT "dj", meta keys, metaboxes, admin columns
 *
 * CPT slug = "dj", rewrite = "dj", archive = "djs", rest_base = "djs"
 * Taxonomy: sound (GLOBAL BRIDGE via apollo-core, shared with event)
 *
 * @package Apollo\DJs
 */

declare(strict_types=1);

namespace Apollo\DJs;

if (! defined('ABSPATH')) {
    exit;
}

class Registry
{

    public function __construct()
    {
        add_action('init', array($this, 'register_cpt'), 5);
        add_action('init', array($this, 'register_track_cpt'), 5);
        add_filter('apollo_core_register_meta', array($this, 'register_meta'));
        // Metaboxes delegados para Admin\Metabox (PSR-4)
        add_filter('manage_' . APOLLO_DJ_CPT . '_posts_columns', array($this, 'admin_columns'));
        add_action('manage_' . APOLLO_DJ_CPT . '_posts_custom_column', array($this, 'admin_column_content'), 10, 2);
    }

    /**
     * Registra CPT "dj" — fallback se apollo-core não registrou
     */
    public function register_cpt(): void
    {
        if (post_type_exists(APOLLO_DJ_CPT)) {
            $this->register_taxonomy_fallback();
            return;
        }

        $labels = array(
            'name'               => __('DJs', 'apollo-djs'),
            'singular_name'      => __('DJ', 'apollo-djs'),
            'add_new'            => __('Novo DJ', 'apollo-djs'),
            'add_new_item'       => __('Adicionar Novo DJ', 'apollo-djs'),
            'edit_item'          => __('Editar DJ', 'apollo-djs'),
            'new_item'           => __('Novo DJ', 'apollo-djs'),
            'view_item'          => __('Ver DJ', 'apollo-djs'),
            'search_items'       => __('Buscar DJs', 'apollo-djs'),
            'not_found'          => __('Nenhum DJ encontrado', 'apollo-djs'),
            'not_found_in_trash' => __('Nenhum DJ na lixeira', 'apollo-djs'),
        );

        register_post_type(
            APOLLO_DJ_CPT,
            array(
                'labels'              => $labels,
                'public'              => true,
                'has_archive'         => 'djs',
                'rewrite'             => array(
                    'slug'       => 'dj',
                    'with_front' => false,
                ),
                'rest_base'           => 'djs',
                'show_in_rest'        => true,
                'supports'            => array('title', 'editor', 'thumbnail', 'author'),
                'menu_icon'           => 'dashicons-format-audio',
                'menu_position'       => 7,
                'taxonomies'          => array(APOLLO_DJ_TAX_SOUND),
                'capability_type'     => 'post',
                'map_meta_cap'        => true,
                'show_in_admin_bar'   => true,
                'exclude_from_search' => false,
            )
        );

        $this->register_taxonomy_fallback();
    }

    /**
     * Registra CPT "track" — as faixas "Out Now".
     *
     * MESMO PADRÃO DO CPT dj: apollo-core declara em config/cpts.php e registra
     * um fallback em CPTRegistry (init:5). Esta é a registration do OWNER, que
     * roda no mesmo hook e é guardada por post_type_exists() — quem chegar
     * primeiro vence e o outro não faz nada. O que o owner acrescenta sobre o
     * fallback do core é a ligação com a taxonomy `sound` e map_meta_cap.
     *
     * POR QUE `sound` E NÃO UM GÊNERO PRÓPRIO. A taxonomy já existe, já é o
     * vocabulário que a superfície de DJs filtra, e registrar um segundo
     * vocabulário de gênero aqui seria bifurcá-lo. O `genre` freeform do
     * schema v2 entra como `_track_genre_legacy` só para a migração e depois
     * é aposentado em favor de termos.
     *
     * AUTORIA. 'author' está em supports e map_meta_cap fica true porque a
     * regra do produto é "o DJ adiciona e a autoria é automática" — isso é
     * post_author, um fato de post. Era exatamente o que um repeater em meta
     * nunca conseguiu representar.
     */
    public function register_track_cpt(): void
    {
        if (post_type_exists('track')) {
            return;
        }

        $labels = array(
            'name'               => __('Faixas', 'apollo-djs'),
            'singular_name'      => __('Faixa', 'apollo-djs'),
            'add_new'            => __('Nova Faixa', 'apollo-djs'),
            'add_new_item'       => __('Adicionar Nova Faixa', 'apollo-djs'),
            'edit_item'          => __('Editar Faixa', 'apollo-djs'),
            'new_item'           => __('Nova Faixa', 'apollo-djs'),
            'view_item'          => __('Ver Faixa', 'apollo-djs'),
            'search_items'       => __('Buscar Faixas', 'apollo-djs'),
            'not_found'          => __('Nenhuma faixa encontrada', 'apollo-djs'),
            'not_found_in_trash' => __('Nenhuma faixa na lixeira', 'apollo-djs'),
            'menu_name'          => __('Out Now', 'apollo-djs'),
        );

        register_post_type(
            'track',
            array(
                'labels'              => $labels,
                'public'              => true,
                'has_archive'         => 'tracks',
                'rewrite'             => array(
                    'slug'       => 'track',
                    'with_front' => false,
                ),
                'rest_base'           => 'tracks',
                'show_in_rest'        => true,
                'supports'            => array('title', 'editor', 'thumbnail', 'author'),
                'menu_icon'           => 'dashicons-album',
                'menu_position'       => 8,
                'taxonomies'          => array(APOLLO_DJ_TAX_SOUND),
                'capability_type'     => 'post',
                'map_meta_cap'        => true,
                'show_in_admin_bar'   => true,
                'exclude_from_search' => false,
            )
        );
    }

    /**
     * Fallback: registra taxonomy sound se apollo-core/apollo-events não registraram
     */
    private function register_taxonomy_fallback(): void
    {
        if (! taxonomy_exists(APOLLO_DJ_TAX_SOUND)) {
            register_taxonomy(
                APOLLO_DJ_TAX_SOUND,
                array(APOLLO_DJ_CPT, 'event'),
                array(
                    'labels'       => array(
                        'name'          => 'Gêneros Musicais',
                        'singular_name' => 'Gênero Musical',
                    ),
                    'hierarchical' => true,
                    'public'       => true,
                    'show_in_rest' => true,
                    'rewrite'      => array('slug' => 'som'),
                )
            );
        }
    }

    /**
     * Meta keys via apollo-core — conforme apollo-registry.json
     */
    public function register_meta(array $meta_config): array
    {
        $url_keys = array(
            '_dj_website', '_dj_soundcloud', '_dj_spotify', '_dj_youtube',
            '_dj_mixcloud', '_dj_facebook', '_dj_bandcamp', '_dj_beatport',
            '_dj_resident_advisor', '_dj_set_url', '_dj_media_kit_url',
            '_dj_rider_url', '_dj_mix_url', '_dj_about_video',
        );

        $int_keys = array( '_dj_image', '_dj_banner', '_dj_user_id', '_dj_about_photo' );

        $dj_meta = array();
        foreach ( APOLLO_DJ_META_KEYS as $key ) {
            if ( in_array( $key, $int_keys, true ) ) {
                $dj_meta[ $key ] = array( 'type' => 'integer', 'sanitize' => 'absint' );
            } elseif ( $key === '_dj_verified' ) {
                $dj_meta[ $key ] = array( 'type' => 'boolean', 'sanitize' => 'rest_sanitize_boolean' );
            } elseif ( $key === '_dj_booking' ) {
                $dj_meta[ $key ] = array( 'type' => 'string', 'sanitize' => 'sanitize_email' );
            } elseif ( in_array( $key, $url_keys, true ) ) {
                $dj_meta[ $key ] = array( 'type' => 'string', 'sanitize' => 'esc_url_raw' );
            } elseif ( in_array( $key, array( '_dj_bio_short', '_dj_bio' ), true ) ) {
                $dj_meta[ $key ] = array( 'type' => 'string', 'sanitize' => 'sanitize_textarea_field' );
            } else {
                $dj_meta[ $key ] = array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' );
            }
        }

        // Additive merge — other plugins (e.g. apollo-coauthor) also contribute
        // keys to 'dj' on this same filter. A wholesale overwrite here would
        // silently drop their contributions depending on hook execution order.
        if ( ! isset( $meta_config['dj'] ) || ! is_array( $meta_config['dj'] ) ) {
            $meta_config['dj'] = array();
        }
        $meta_config['dj'] = array_merge( $meta_config['dj'], $dj_meta );

        return $meta_config;
    }

    /**
     * Colunas admin
     */
    public function admin_columns(array $columns): array
    {
        $new = array();
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ('title' === $key) {
                $new['dj_verified'] = __('Verificado', 'apollo-djs');
                $new['dj_sounds']   = __('Gêneros', 'apollo-djs');
                $new['dj_events']   = __('Eventos', 'apollo-djs');
            }
        }
        return $new;
    }

    /**
     * Conteúdo das colunas admin
     */
    public function admin_column_content(string $column, int $post_id): void
    {
        switch ($column) {
            case 'dj_verified':
                echo apollo_dj_is_verified($post_id) ? '✅' : '—';
                break;

            case 'dj_sounds':
                $sounds = apollo_dj_get_sounds($post_id);
                echo esc_html(implode(', ', $sounds) ?: '—');
                break;

            case 'dj_events':
                $count = apollo_dj_count_upcoming_events($post_id);
                echo esc_html($count);
                break;
        }
    }
}
