<?php

/**
 * Registry — Registra CPT, meta keys e hooks via apollo-core
 *
 * CPT slug = "event", rewrite = "evento"
 * Taxonomias: event_category, event_type, event_tag, sound, season (GLOBAL BRIDGE via apollo-core)
 *
 * @package Apollo\Event
 */

declare(strict_types=1);

namespace Apollo\Event;

if (! defined('ABSPATH')) {
    exit;
}

class Registry
{



    public function __construct()
    {
        // CPT registration — fallback se apollo-core não registrou
        add_action('init', array($this, 'register_cpt'), 5);

        // Meta registration via apollo-core hook
        add_filter('apollo_core_register_meta', array($this, 'register_meta'));

        // Metaboxes delegated to Admin\Metabox (PSR-4)

        // Fire Apollo ecosystem hook on first publish (replaces publish_event in downstream plugins)
        add_action('transition_post_status', array($this, 'on_status_transition'), 10, 3);

        // Colunas customizadas no admin
        add_filter('manage_' . APOLLO_EVENT_CPT . '_posts_columns', array($this, 'admin_columns'));
        add_action('manage_' . APOLLO_EVENT_CPT . '_posts_custom_column', array($this, 'admin_column_content'), 10, 2);
        add_filter('manage_edit-' . APOLLO_EVENT_CPT . '_sortable_columns', array($this, 'sortable_columns'));
    }

    /**
     * Registra CPT "event" — apollo-core faz fallback, mas registramos aqui como owner
     */
    public function register_cpt(): void
    {
        // Se apollo-core já registrou, não fazer nada
        if (post_type_exists(APOLLO_EVENT_CPT)) {
            // Garantir que taxonomias existam mesmo se apollo-core já registrou o CPT
            $this->register_taxonomies_fallback();
            return;
        }

        $labels = array(
            'name'               => __('Eventos', 'apollo-events'),
            'singular_name'      => __('Evento', 'apollo-events'),
            'add_new'            => __('Novo Evento', 'apollo-events'),
            'add_new_item'       => __('Adicionar Novo Evento', 'apollo-events'),
            'edit_item'          => __('Editar Evento', 'apollo-events'),
            'new_item'           => __('Novo Evento', 'apollo-events'),
            'view_item'          => __('Ver Evento', 'apollo-events'),
            'search_items'       => __('Buscar Eventos', 'apollo-events'),
            'not_found'          => __('Nenhum evento encontrado', 'apollo-events'),
            'not_found_in_trash' => __('Nenhum evento na lixeira', 'apollo-events'),
        );

        register_post_type(
            APOLLO_EVENT_CPT,
            array(
                'labels'              => $labels,
                'public'              => true,
                'has_archive'         => 'eventos',
                'rewrite'             => array(
                    'slug'       => 'evento',
                    'with_front' => false,
                ),
                'rest_base'           => 'events',
                'show_in_rest'        => true,
                'supports'            => array('title', 'editor', 'thumbnail', 'author'),
                'menu_icon'           => 'dashicons-calendar-alt',
                'menu_position'       => 6,
                'taxonomies'          => array(
                    APOLLO_EVENT_TAX_CATEGORY,
                    APOLLO_EVENT_TAX_TYPE,
                    APOLLO_EVENT_TAX_TAG,
                    APOLLO_EVENT_TAX_SOUND,
                    APOLLO_EVENT_TAX_SEASON,
                ),
                'capability_type'     => 'post',
                'map_meta_cap'        => true,
                'show_in_admin_bar'   => true,
                'exclude_from_search' => false,
            )
        );

        $this->register_taxonomies_fallback();
    }

    /**
     * Registra taxonomias com fallback — conforme apollo-registry.json
     * Se apollo-core já registrou, pula. Senão, registra aqui.
     */
    private function register_taxonomies_fallback(): void
    {
        // event_category
        if (! taxonomy_exists(APOLLO_EVENT_TAX_CATEGORY)) {
            register_taxonomy(
                APOLLO_EVENT_TAX_CATEGORY,
                APOLLO_EVENT_CPT,
                array(
                    'labels'       => array(
                        'name'          => 'Categorias',
                        'singular_name' => 'Categoria',
                    ),
                    'hierarchical' => true,
                    'public'       => true,
                    'show_in_rest' => true,
                    'rewrite'      => array('slug' => 'categoria-evento'),
                )
            );
        }

        // event_type
        if (! taxonomy_exists(APOLLO_EVENT_TAX_TYPE)) {
            register_taxonomy(
                APOLLO_EVENT_TAX_TYPE,
                APOLLO_EVENT_CPT,
                array(
                    'labels'       => array(
                        'name'          => 'Tipos',
                        'singular_name' => 'Tipo',
                    ),
                    'hierarchical' => true,
                    'public'       => true,
                    'show_in_rest' => true,
                    'rewrite'      => array('slug' => 'tipo-evento'),
                )
            );
        }

        // event_tag
        if (! taxonomy_exists(APOLLO_EVENT_TAX_TAG)) {
            register_taxonomy(
                APOLLO_EVENT_TAX_TAG,
                APOLLO_EVENT_CPT,
                array(
                    'labels'       => array(
                        'name'          => 'Tags',
                        'singular_name' => 'Tag',
                    ),
                    'hierarchical' => false,
                    'public'       => true,
                    'show_in_rest' => true,
                    'rewrite'      => array('slug' => 'tag-evento'),
                )
            );
        }

        // sound — GLOBAL BRIDGE (shared with dj)
        if (! taxonomy_exists(APOLLO_EVENT_TAX_SOUND)) {
            register_taxonomy(
                APOLLO_EVENT_TAX_SOUND,
                array(APOLLO_EVENT_CPT, 'dj'),
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

        // season — shared with classified
        if (! taxonomy_exists(APOLLO_EVENT_TAX_SEASON)) {
            register_taxonomy(
                APOLLO_EVENT_TAX_SEASON,
                array(APOLLO_EVENT_CPT, 'classified'),
                array(
                    'labels'       => array(
                        'name'          => 'Temporadas',
                        'singular_name' => 'Temporada',
                    ),
                    'hierarchical' => true,
                    'public'       => true,
                    'show_in_rest' => true,
                    'rewrite'      => array('slug' => 'temporada'),
                )
            );
        }
    }

    /**
     * Registra meta keys via apollo-core — conforme apollo-registry.json
     *
     * @param array $meta_config Meta config acumulado.
     * @return array
     */
    public function register_meta(array $meta_config): array
    {
        if ( ! isset( $meta_config['event'] ) || ! is_array( $meta_config['event'] ) ) {
            $meta_config['event'] = array();
        }

        // Additive merge — other plugins (e.g. apollo-coauthor) also contribute
        // keys to 'event' on this same filter. A wholesale overwrite here would
        // silently drop their contributions depending on hook execution order.
        $meta_config['event'] = array_merge(
            $meta_config['event'],
            array(
            '_event_start_date'   => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_end_date'     => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_start_time'   => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_end_time'     => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_dj_ids'       => array(
                'type'              => 'array',
                'sanitize_callback' => static function ( $value ) {
                    return array_values( array_filter( array_map( 'absint', (array) $value ) ) );
                },
            ),
            '_event_dj_slots'     => array(
                'type'              => 'array',
                'sanitize_callback' => static function ( $value ) {
                    $clean = array();
                    foreach ( (array) $value as $slot ) {
                        if ( ! is_array( $slot ) ) {
                            continue;
                        }
                        $clean[] = array(
                            'dj_id'      => absint( $slot['dj_id'] ?? 0 ),
                            'start_time' => sanitize_text_field( (string) ( $slot['start_time'] ?? '' ) ),
                            'end_time'   => sanitize_text_field( (string) ( $slot['end_time'] ?? '' ) ),
                            'badge'      => isset( $slot['badge'] ) && '' !== $slot['badge']
                                ? sanitize_text_field( (string) $slot['badge'] )
                                : null,
                        );
                    }
                    return $clean;
                },
            ),
            '_event_loc_id'       => array(
                'type'     => 'integer',
                'sanitize' => 'absint',
            ),
            /*
             * Banner is an attachment id OR an absolute image URL — users
             * without upload_files may only reference an image hosted outside
             * Apollo. This registry entry is the SSOT: apollo-core applies it
             * on every update_post_meta(), so an 'absint' here silently turned
             * every external URL into 0 no matter what the REST controller did.
             */
            '_event_banner'       => array(
                'type'              => 'string',
                'sanitize_callback' => array( API\EventsController::class, 'sanitize_image_ref' ),
            ),
            '_event_bg_color'     => array(
                'type'              => 'string',
                'sanitize_callback' => static function ( $value ) {
                    return sanitize_hex_color( (string) $value ) ?: '#0a0a0a';
                },
            ),
            '_event_access_buttons' => array(
                'type'              => 'array',
                'sanitize_callback' => static function ( $value ) {
                    $clean = array();
                    foreach ( (array) $value as $btn ) {
                        if ( ! is_array( $btn ) ) {
                            continue;
                        }
                        $label = sanitize_text_field( (string) ( $btn['label'] ?? '' ) );
                        if ( '' === $label ) {
                            continue;
                        }
                        $kind  = in_array( ( $btn['kind'] ?? '' ), array( 'ticket', 'lista' ), true ) ? $btn['kind'] : 'ticket';
                        $style = in_array( ( $btn['style'] ?? '' ), array( 'main', 'soft', 'lista', 'fem', 'cta' ), true ) ? $btn['style'] : 'soft';
                        $clean[] = array(
                            'kind'  => $kind,
                            'style' => $style,
                            'label' => $label,
                            'sub'   => sanitize_text_field( (string) ( $btn['sub'] ?? '' ) ),
                            'url'   => esc_url_raw( (string) ( $btn['url'] ?? '' ) ),
                        );
                    }
                    return $clean;
                },
            ),
            '_event_ticket_url'   => array(
                'type'     => 'string',
                'sanitize' => 'esc_url_raw',
            ),
            '_event_ticket_price' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_privacy'      => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_status'       => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_is_gone'      => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            // Checkbox toggle ("Highlighted") — feeds the /eventos + /casa hero
            // sliders. Same '1'/'' string-boolean convention as _event_is_gone
            // above, for consistency with the rest of this registry.
            '_event_highlighted'  => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_video_url'    => array(
                'type'     => 'string',
                'sanitize' => 'Apollo\\Event\\apollo_event_sanitize_video_url',
            ),
            /* Same int-or-URL rule as _event_banner (see note above). */
            '_event_gallery'      => array(
                'type'              => 'array',
                'sanitize_callback' => static function ( $value ) {
                    $refs = array_map(
                        array( API\EventsController::class, 'sanitize_image_ref' ),
                        (array) $value
                    );
                    return array_values(
                        array_filter(
                            $refs,
                            static function ( $ref ): bool {
                                return '' !== $ref && 0 !== $ref;
                            }
                        )
                    );
                },
            ),
            '_event_coupon_code'  => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_list_url'     => array(
                'type'     => 'string',
                'sanitize' => 'esc_url_raw',
            ),
            '_event_audio_url'    => array(
                'type'     => 'string',
                'sanitize' => 'esc_url_raw',
            ),
            '_event_ticket_status' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_ticket_btn_style' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_list_btn_style' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_view_count'   => array(
                'type'     => 'integer',
                'sanitize' => 'absint',
            ),
            '_event_earlybird_enabled' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_earlybird_name' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_earlybird_sub' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_earlybird_url' => array(
                'type'     => 'string',
                'sanitize' => 'esc_url_raw',
            ),
            '_event_lista_geral_enabled' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_lista_geral_sub' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_lista_fem_enabled' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_lista_fem_sub' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            '_event_lista_cta_label' => array(
                'type'     => 'string',
                'sanitize' => 'sanitize_text_field',
            ),
            )
        );

        return $meta_config;
    }

    /**
     * Fires `apollo/event/published` when an event transitions to 'publish'.
     *
     * Allows downstream plugins (apollo-social, apollo-statistics, etc.) to
     * listen on the Apollo-namespaced hook instead of `publish_event`.
     *
     * @param string   $new_status New post status.
     * @param string   $old_status Old post status.
     * @param \WP_Post $post       The post object.
     */
    public function on_status_transition(string $new_status, string $old_status, \WP_Post $post): void
    {
        if ('publish' !== $new_status || 'publish' === $old_status) {
            return;
        }
        if ($post->post_type !== APOLLO_EVENT_CPT) {
            return;
        }
        if (wp_is_post_revision($post->ID)) {
            return;
        }
        /**
         * Fires when an Apollo event is first published.
         *
         * Ecosystem contract: argument #2 is ALWAYS a string action name.
         * Cross-plugin collectors (apollo-statistics HookCollector) declare
         * `string $action`; passing the WP_Post here raised a TypeError and
         * killed the request. The post object moved to argument #3.
         *
         * @param int      $post_id Event post ID.
         * @param string   $action  Action name.
         * @param \WP_Post $post    Event post object.
         */
        do_action('apollo/event/published', $post->ID, 'published', $post);
    }

    /**
     * Colunas customizadas no admin
     */
    public function admin_columns(array $columns): array
    {
        $new = array();
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ('title' === $key) {
                $new['event_date']   = __('Data', 'apollo-events');
                $new['event_loc']    = __('Local', 'apollo-events');
                $new['event_status'] = __('Status', 'apollo-events');
            }
        }
        return $new;
    }

    /**
     * Conteúdo das colunas customizadas
     */
    public function admin_column_content(string $column, int $post_id): void
    {
        switch ($column) {
            case 'event_date':
                $date = get_post_meta($post_id, '_event_start_date', true);
                $time = get_post_meta($post_id, '_event_start_time', true);
                echo esc_html($date ? $date . ($time ? ' ' . $time : '') : '—');
                break;

            case 'event_loc':
                $loc = apollo_event_get_loc($post_id);
                if ( ! $loc ) {
                    /* Distinguish "meta missing" from "orphan id" for admins. */
                    $raw_id = (int) get_post_meta( $post_id, '_event_loc_id', true );
                    if ( $raw_id > 0 ) {
                        echo '<span style="color:#b32d2e;" title="ID ' . esc_attr( (string) $raw_id ) . '">'
                            . esc_html__( 'Local inválido', 'apollo-events' )
                            . '</span>';
                    } else {
                        echo '—';
                    }
                    break;
                }
                echo esc_html( $loc['title'] );
                if ( ! empty( $loc['status'] ) && 'publish' !== $loc['status'] ) {
                    echo ' <span style="color:#996800;font-size:11px;">(' . esc_html( $loc['status'] ) . ')</span>';
                }
                break;

            case 'event_status':
                $status = get_post_meta($post_id, '_event_status', true) ?: 'scheduled';
                $gone   = apollo_event_is_gone($post_id);
                if ($gone) {
                    echo '<span style="color:#999;">⏰ ' . esc_html__('Gone', 'apollo-events') . '</span>';
                } else {
                    echo esc_html(ucfirst($status));
                }
                break;
        }
    }

    /**
     * Colunas ordenáveis
     */
    public function sortable_columns(array $columns): array
    {
        $columns['event_date'] = '_event_start_date';
        return $columns;
    }
}