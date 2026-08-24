<?php

/**
 * URL importer — REST surface.
 *
 * Two routes, deliberately separate so the operator always sees what will be
 * written BEFORE anything is written:
 *
 *   POST apollo/v1/eventos/importar-url/preview   → fetch + normalise only
 *   POST apollo/v1/eventos/importar-url           → preview + create/update the event
 *
 * Both run entirely server-side (PHP → provider API). The browser only ever
 * talks to apollo.rio.br, so there is no cross-origin request anywhere in the
 * chain and CORS cannot break the importer — which is exactly what used to.
 *
 * Strict CPT contract on import():
 *   cover → apollo_event_set_banner() → `_event_banner` === `_thumbnail_id`
 *   venue → match_loc() → `_event_loc_id` (hard-fail if missing)
 *   coupon → `_event_coupon_code`
 *   dates  → start + end (overnight derived when needed)
 *
 * @package Apollo\Event
 * @since   1.7.0
 */

declare(strict_types=1);

namespace Apollo\Event\Import;

if (! defined('ABSPATH')) {
    exit;
}

final class UrlImportController
{
    private const NS = 'apollo/v1';

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes(): void
    {
        $args = array(
            'url'    => array(
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'esc_url_raw',
                'validate_callback' => static function ($value): bool {
                    return is_string($value) && (bool) filter_var($value, FILTER_VALIDATE_URL);
                },
            ),
            'coupon' => array(
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );

        register_rest_route(
            self::NS,
            '/eventos/importar-url/preview',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'preview'),
                'permission_callback' => array($this, 'can_import'),
                'args'                => $args,
            )
        );

        register_rest_route(
            self::NS,
            '/eventos/importar-url',
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'import'),
                'permission_callback' => array($this, 'can_import'),
                'args'                => $args + array(
                    'status'   => array(
                        'type'              => 'string',
                        'default'           => 'draft',
                        'enum'              => array('draft', 'publish', 'pending'),
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'link_loc' => array(
                        'type'    => 'boolean',
                        'default' => true,
                    ),
                ),
            )
        );
    }

    /**
     * Importing creates real content, so it needs the same capability creating
     * an event by hand does — never __return_true.
     */
    public function can_import(): bool
    {
        return current_user_can('edit_posts');
    }

    /**
     * Resolve a URL to a normalised payload via whichever provider owns it.
     *
     * @return array<string,mixed>|\WP_Error
     */
    private function resolve(\WP_REST_Request $request): array|\WP_Error
    {
        $url    = (string) $request->get_param('url');
        $coupon = (string) $request->get_param('coupon');

        if (BlueTicketProvider::handles($url)) {
            return BlueTicketProvider::fetch($url, $coupon);
        }

        return new \WP_Error(
            'apollo_import_unsupported',
            sprintf(
                /* translators: %s: hostname */
                __('Ainda não sei importar de %s. Suportado hoje: blueticket.com.br.', 'apollo-events'),
                (string) wp_parse_url($url, PHP_URL_HOST)
            ),
            array('status' => 422)
        );
    }

    public function preview(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $data = $this->resolve($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $data['existing_id'] = $this->find_existing((string) $data['provider'], (string) $data['provider_id']);
        $data['matched_loc'] = $this->match_loc(is_array($data['loc'] ?? null) ? $data['loc'] : array());
        $data['ready']       = $this->contract_ready($data);

        return new \WP_REST_Response(array('ok' => true, 'data' => $data), 200);
    }

    public function import(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $data = $this->resolve($request);
        if (is_wp_error($data)) {
            return $data;
        }

        $gate = $this->assert_contract($data);
        if (is_wp_error($gate)) {
            return $gate;
        }

        /* _event_loc_id is mandatory — link_loc=false cannot skip the match. */
        $loc_id   = $this->match_loc(is_array($data['loc'] ?? null) ? $data['loc'] : array());
        $loc_name = (string) ($data['loc']['name'] ?? '');
        $loc_slug = (string) ($data['loc']['slug'] ?? '');
        if ($loc_id <= 0) {
            return new \WP_Error(
                'apollo_import_no_loc',
                sprintf(
                    /* translators: 1: venue name, 2: compact slug */
                    __('Não encontrei o CPT local para "%1$s" (slug %2$s). Crie/publique o local no WP ou ajuste o slug antes de importar.', 'apollo-events'),
                    $loc_name !== '' ? $loc_name : '—',
                    $loc_slug !== '' ? $loc_slug : '—'
                ),
                array(
                    'status' => 422,
                    'loc'    => $data['loc'] ?? array(),
                )
            );
        }

        $existing = $this->find_existing((string) $data['provider'], (string) $data['provider_id']);
        $was_new  = ! $existing;
        $postarr  = array(
            'post_type'    => defined('APOLLO_EVENT_CPT') ? APOLLO_EVENT_CPT : 'event',
            'post_title'   => $data['title'],
            'post_content' => $data['about'] ?? '',
            'post_status'  => (string) $request->get_param('status'),
            'post_author'  => get_current_user_id(),
        );

        if ($existing) {
            /* Re-import must never silently republish something the team put
               back to draft, nor duplicate the event. */
            $postarr['ID'] = $existing;
            unset($postarr['post_status'], $postarr['post_author']);
        }

        $post_id = $existing ? wp_update_post($postarr, true) : wp_insert_post($postarr, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        $post_id = (int) $post_id;

        /* Ticket CTA: provider ticket_url, else the URL the operator submitted. */
        $ticket_url = (string) ($data['ticket_url'] ?? '');
        if ('' === $ticket_url) {
            $ticket_url = (string) $request->get_param('url');
        }
        $ticket_title = (string) ($data['ticket_price'] ?? '');
        if ('' === $ticket_title || preg_match('~^https?://~i', $ticket_title)) {
            $ticket_title = __('Ingressos do Evento', 'apollo-events');
        }

        /* ── Full CPT field contract ── */
        $meta = array(
            '_event_start_date'    => (string) ($data['start_date'] ?? ''),
            '_event_start_time'    => (string) ($data['start_time'] ?? ''),
            '_event_end_date'      => (string) ($data['end_date'] ?? ''),
            '_event_end_time'      => (string) ($data['end_time'] ?? ''),
            '_event_ticket_url'    => $ticket_url,
            '_event_ticket_price'  => $ticket_title,
            '_event_ticket_status' => (string) ($data['ticket_status'] ?? 'available'),
            '_event_coupon_code'   => strtoupper((string) ($data['coupon'] ?? '')),
            '_event_video_url'     => (string) ($data['video_url'] ?? ''),
        );
        foreach ($meta as $k => $v) {
            if ('' !== $v) {
                update_post_meta($post_id, $k, $v);
            }
        }

        /* DJs only when provider actually supplies them (never wipe manual lineup). */
        if (! empty($data['dj_ids']) && is_array($data['dj_ids'])) {
            update_post_meta(
                $post_id,
                '_event_dj_ids',
                array_values(array_filter(array_map('absint', $data['dj_ids'])))
            );
        }
        if (! empty($data['dj_slots']) && is_array($data['dj_slots'])) {
            update_post_meta($post_id, '_event_dj_slots', $data['dj_slots']);
        }

        /* Provenance — lets a re-import find this post instead of duplicating. */
        update_post_meta($post_id, '_event_import_provider', $data['provider']);
        update_post_meta($post_id, '_event_import_id', $data['provider_id']);
        update_post_meta($post_id, '_event_import_url', $data['source_url']);
        update_post_meta($post_id, '_event_import_synced', current_time('mysql'));

        /* Optional map extras (not venue SSOT — that is _event_loc_id). */
        if (! empty($data['loc']['lat'])) {
            update_post_meta($post_id, '_event_lat', $data['loc']['lat']);
        }
        if (! empty($data['loc']['lng'])) {
            update_post_meta($post_id, '_event_lng', $data['loc']['lng']);
        }

        /* Cover — mandatory; banner meta === featured image attachment. */
        $att = function_exists('apollo_event_set_banner')
            ? \Apollo\Event\apollo_event_set_banner($post_id, (string) $data['cover'])
            : new \WP_Error('apollo_banner_missing', 'apollo_event_set_banner unavailable');

        if (is_wp_error($att) || (int) $att <= 0) {
            $err = is_wp_error($att)
                ? $att
                : new \WP_Error(
                    'apollo_import_banner_failed',
                    __('Falha ao gravar a capa como featured image.', 'apollo-events'),
                    array('status' => 422)
                );
            return $this->abort_import($post_id, $was_new, $err);
        }

        $att_id     = (int) $att;
        $thumb_id   = (int) get_post_thumbnail_id($post_id);
        $banner_meta = (int) get_post_meta($post_id, '_event_banner', true);
        $synced     = ($att_id === $thumb_id && $att_id === $banner_meta && $att_id > 0);

        if (! $synced) {
            return $this->abort_import(
                $post_id,
                $was_new,
                new \WP_Error(
                    'apollo_import_banner_desync',
                    __('Capa gravada mas _event_banner ≠ featured image — importação cancelada.', 'apollo-events'),
                    array(
                        'status'      => 422,
                        'banner_meta' => $banner_meta,
                        'thumb_id'    => $thumb_id,
                        'att_id'      => $att_id,
                    )
                )
            );
        }

        update_post_meta($post_id, '_event_loc_id', $loc_id);

        $banner = array(
            'attachment_id' => $att_id,
            'thumb_id'      => $thumb_id,
            'synced'        => true,
        );

        /* Debug log write removed 2026-08-17 — appended to
           D:/dev/_livro.rvalle.com.br/… on every strict import. */

        return new \WP_REST_Response(
            array(
                'ok'       => true,
                'created'  => $was_new,
                'post_id'  => $post_id,
                'edit_url' => get_edit_post_link($post_id, 'raw'),
                'view_url' => get_permalink($post_id),
                'loc_id'   => $loc_id,
                'banner'   => $banner,
                'thumb_id' => $thumb_id,
                'synced'   => true,
                'data'     => $data,
            ),
            $was_new ? 201 : 200
        );
    }

    /**
     * Pre-insert contract: title, start_date, non-empty cover.
     *
     * @param array<string,mixed> $data
     */
    private function assert_contract(array $data): true|\WP_Error
    {
        if (empty($data['start_date'])) {
            return new \WP_Error(
                'apollo_import_no_date',
                sprintf(
                    /* translators: %s: source URL */
                    __('Não consegui ler a data do evento em %s. Importação cancelada: um evento sem _event_start_date não aparece em /eventos.', 'apollo-events'),
                    (string) ($data['source_url'] ?? '')
                ),
                array('status' => 422, 'parsed' => $data)
            );
        }

        if (empty($data['title'])) {
            return new \WP_Error(
                'apollo_import_no_title',
                __('Não consegui ler o título do evento. Importação cancelada para não criar um evento sem nome.', 'apollo-events'),
                array('status' => 422, 'parsed' => $data)
            );
        }

        if (empty($data['cover']) || ! is_string($data['cover'])) {
            return new \WP_Error(
                'apollo_import_no_cover',
                __('A origem não trouxe capa (cover). Importação cancelada: banner deve virar featured image.', 'apollo-events'),
                array('status' => 422, 'parsed' => $data)
            );
        }

        $loc_name = (string) ($data['loc']['name'] ?? '');
        $loc_slug = (string) ($data['loc']['slug'] ?? '');
        if ('' === $loc_name && '' === $loc_slug) {
            return new \WP_Error(
                'apollo_import_no_venue',
                __('A origem não trouxe venue. Importação cancelada: _event_loc_id é obrigatório.', 'apollo-events'),
                array('status' => 422, 'parsed' => $data)
            );
        }

        return true;
    }

    /**
     * @param array<string,mixed> $data
     * @return array{cover:bool,loc:bool,date:bool,title:bool,ok:bool}
     */
    private function contract_ready(array $data): array
    {
        $loc_ok = $this->match_loc(is_array($data['loc'] ?? null) ? $data['loc'] : array()) > 0;
        $ready  = array(
            'cover' => ! empty($data['cover']),
            'loc'   => $loc_ok,
            'date'  => ! empty($data['start_date']),
            'title' => ! empty($data['title']),
        );
        $ready['ok'] = $ready['cover'] && $ready['loc'] && $ready['date'] && $ready['title'];
        return $ready;
    }

    /**
     * Roll back a brand-new post when a later contract step fails.
     */
    private function abort_import(int $post_id, bool $was_new, \WP_Error $error): \WP_Error
    {
        if ($was_new && $post_id > 0) {
            wp_delete_post($post_id, true);
        }
        if (! $error->get_error_data()) {
            $error->add_data(array('status' => 422));
        }
        return $error;
    }

    /**
     * Has this provider event already been imported?
     */
    private function find_existing(string $provider, string $provider_id): int
    {
        if ('' === $provider_id) {
            return 0;
        }
        $q = new \WP_Query(
            array(
                'post_type'      => defined('APOLLO_EVENT_CPT') ? APOLLO_EVENT_CPT : 'event',
                'post_status'    => array('publish', 'draft', 'pending', 'future', 'private'),
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => array(
                    'relation' => 'AND',
                    array('key' => '_event_import_provider', 'value' => $provider),
                    array('key' => '_event_import_id', 'value' => $provider_id),
                ),
            )
        );
        return $q->posts ? (int) $q->posts[0] : 0;
    }

    /**
     * Find the existing loc post for this venue.
     *
     * Never CREATES one: a loc carries editorial content that an importer has
     * no business inventing. Compact slug matching so "d-edge" / "dedge" /
     * "D-Edge Rio" all hit CPT slug `dedge`.
     *
     * @param array<string,mixed> $loc
     */
    private function match_loc(array $loc): int
    {
        $cpt = defined('APOLLO_CPT_LOCAL') ? APOLLO_CPT_LOCAL : 'local';
        if (! post_type_exists($cpt)) {
            return 0;
        }

        $slug = (string) ($loc['slug'] ?? '');
        $name = (string) ($loc['name'] ?? '');
        $key  = BlueTicketProvider::loc_slug('' !== $slug ? $slug : $name);
        if ('' === $key) {
            return 0;
        }

        /* 1) exact post_name (compact or as-stored) */
        foreach (array_unique(array_filter(array($key, $slug))) as $try) {
            $hit = get_page_by_path($try, OBJECT, $cpt);
            if ($hit instanceof \WP_Post) {
                return (int) $hit->ID;
            }
        }

        /* 2) compact-slug scan — publish + pending + private (same as get_loc). */
        $all = get_posts(
            array(
                'post_type'      => $cpt,
                'post_status'    => array('publish', 'pending', 'private'),
                'posts_per_page' => 300,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            )
        );
        foreach ($all as $lid) {
            $p = get_post($lid);
            if (! $p instanceof \WP_Post) {
                continue;
            }
            if (BlueTicketProvider::loc_slug($p->post_title) === $key
                || BlueTicketProvider::loc_slug($p->post_name) === $key) {
                return (int) $lid;
            }
        }

        return 0;
    }
}
