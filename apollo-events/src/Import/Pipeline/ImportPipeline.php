<?php

/**
 * Orchestrates preview + import runs with cover sideload and diagnostics.
 *
 * @package Apollo\Event
 * @since   1.7.15
 */

declare(strict_types=1);

namespace Apollo\Event\Import\Pipeline;

use Apollo\Event\Import\BlueTicketProvider;
use Apollo\Event\Import\Cache\ImportRunCache;
use Apollo\Event\Import\Diagnostics\ImportDiagnostics;
use Apollo\Event\Import\Diagnostics\ProblemCode;
use Apollo\Event\Import\Media\CoverResolver;
use Apollo\Event\Import\Media\CoverSideloader;
use Apollo\Event\Import\ProviderRegistry;

if (! defined('ABSPATH')) {
    exit;
}

final class ImportPipeline
{
    /**
     * Fetch + validate without writing the post.
     */
    public function preview(string $url, string $coupon = ''): ImportResult|\WP_Error
    {
        $diag = new ImportDiagnostics();
        $data = $this->resolve_payload($url, $coupon, $diag, true);
        if (is_wp_error($data)) {
            return $data;
        }

        $data['existing_id'] = $this->find_existing((string) $data['provider'], (string) $data['provider_id']);
        $data['matched_loc'] = $this->match_loc(is_array($data['loc'] ?? null) ? $data['loc'] : array());
        $ready               = $this->contract_ready($data);

        if (! $ready['cover']) {
            $diag->error(ProblemCode::NO_COVER, __('Capa ausente ou inválida.', 'apollo-events'));
        }
        if (! $ready['loc']) {
            $diag->warn(
                ProblemCode::LOC_MISSING,
                __('Local não resolvido para CPT local.', 'apollo-events'),
                array('loc' => $data['loc'] ?? array())
            );
        }

        return new ImportResult(
            ok: $ready['ok'] && ! $diag->has_errors(),
            data: $data,
            diagnostics: $diag,
            ready: $ready,
        );
    }

    /**
     * Full import: create/update event + sideload cover.
     */
    public function import(string $url, string $coupon, string $status): ImportResult|\WP_Error
    {
        $diag = new ImportDiagnostics();
        $data = $this->resolve_payload($url, $coupon, $diag, false);
        if (is_wp_error($data)) {
            return $data;
        }

        $gate = $this->assert_contract($data, $diag);
        if (is_wp_error($gate)) {
            return $gate;
        }

        $loc_id   = $this->match_loc(is_array($data['loc'] ?? null) ? $data['loc'] : array());
        $loc_name = (string) ($data['loc']['name'] ?? '');
        $loc_slug = (string) ($data['loc']['slug'] ?? '');
        if ($loc_id <= 0) {
            $diag->error(ProblemCode::LOC_MISSING, __('Venue não encontrado no CPT local.', 'apollo-events'));

            return new \WP_Error(
                'apollo_import_no_loc',
                sprintf(
                    __('Não encontrei o CPT local para "%1$s" (slug %2$s).', 'apollo-events'),
                    $loc_name !== '' ? $loc_name : '—',
                    $loc_slug !== '' ? $loc_slug : '—'
                ),
                array('status' => 422, 'loc' => $data['loc'] ?? array(), 'diagnostics' => $diag->to_array())
            );
        }

        $existing = $this->find_existing((string) $data['provider'], (string) $data['provider_id']);
        $was_new  = ! $existing;
        $postarr  = array(
            'post_type'    => defined('APOLLO_EVENT_CPT') ? APOLLO_EVENT_CPT : 'event',
            'post_title'   => $data['title'],
            'post_content' => $data['about'] ?? '',
            'post_status'  => $status,
            'post_author'  => get_current_user_id(),
        );

        if ($existing) {
            $postarr['ID'] = $existing;
            unset($postarr['post_status'], $postarr['post_author']);
        }

        $post_id = $existing ? wp_update_post($postarr, true) : wp_insert_post($postarr, true);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        $post_id = (int) $post_id;

        $this->write_meta($post_id, $data, $url);

        $cover_result = CoverSideloader::attach($post_id, (string) $data['cover'], (string) $data['title']);
        if (is_wp_error($cover_result)) {
            $diag->error(ProblemCode::SIDELOAD_FAILED, $cover_result->get_error_message());

            return $this->abort_import($post_id, $was_new, $cover_result, $diag);
        }

        $att_id      = (int) $cover_result['attachment_id'];
        $thumb_id    = (int) get_post_thumbnail_id($post_id);
        $banner_meta = (int) get_post_meta($post_id, '_event_banner', true);
        $synced      = ($att_id === $thumb_id && $att_id === $banner_meta && $att_id > 0);

        if (! $synced) {
            $err = new \WP_Error(
                'apollo_import_banner_desync',
                __('Capa gravada mas _event_banner ≠ featured image — importação cancelada.', 'apollo-events'),
                array(
                    'status'      => 422,
                    'banner_meta' => $banner_meta,
                    'thumb_id'    => $thumb_id,
                    'att_id'      => $att_id,
                )
            );
            $diag->error(ProblemCode::BANNER_DESYNC, $err->get_error_message());

            return $this->abort_import($post_id, $was_new, $err, $diag);
        }

        update_post_meta($post_id, '_event_loc_id', $loc_id);

        $cover_result['synced'] = true;
        $diag->info(
            ProblemCode::INFO,
            sprintf(
                /* translators: 1: attachment id, 2: reused yes/no */
                __('Capa gravada (#%1$d, reused=%2$s).', 'apollo-events'),
                $att_id,
                ! empty($cover_result['reused']) ? 'sim' : 'não'
            ),
            $cover_result
        );

        ImportRunCache::forget($url, $coupon);

        return new ImportResult(
            ok: true,
            data: $data,
            diagnostics: $diag,
            ready: $this->contract_ready($data),
            post_id: $post_id,
            created: $was_new,
            loc_id: $loc_id,
            cover: $cover_result,
        );
    }

    /**
     * @return array<string,mixed>|\WP_Error
     */
    private function resolve_payload(string $url, string $coupon, ImportDiagnostics $diag, bool $cache_write): array|\WP_Error
    {
        $cached = ImportRunCache::get($url, $coupon);
        if (is_array($cached) && ! empty($cached['provider'])) {
            $diag->info(ProblemCode::INFO, __('Payload reutilizado do cache de preview.', 'apollo-events'));

            return CoverResolver::resolve($cached, $diag);
        }

        $raw = ProviderRegistry::fetch($url, $coupon);
        if (is_wp_error($raw)) {
            $diag->error(ProblemCode::PROVIDER_ERROR, $raw->get_error_message());

            return $raw;
        }

        $data = CoverResolver::resolve($raw, $diag);

        if ($cache_write) {
            ImportRunCache::set($url, $coupon, $data);
        }

        return $data;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function write_meta(int $post_id, array $data, string $request_url): void
    {
        $ticket_url = (string) ($data['ticket_url'] ?? '');
        if ('' === $ticket_url) {
            $ticket_url = $request_url;
        }
        $ticket_title = (string) ($data['ticket_price'] ?? '');
        if ('' === $ticket_title || preg_match('~^https?://~i', $ticket_title)) {
            $ticket_title = __('Ingressos do Evento', 'apollo-events');
        }

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

        update_post_meta($post_id, '_event_import_provider', $data['provider']);
        update_post_meta($post_id, '_event_import_id', $data['provider_id']);
        update_post_meta($post_id, '_event_import_url', $data['source_url']);
        update_post_meta($post_id, '_event_import_synced', current_time('mysql'));

        if (! empty($data['loc']['lat'])) {
            update_post_meta($post_id, '_event_lat', $data['loc']['lat']);
        }
        if (! empty($data['loc']['lng'])) {
            update_post_meta($post_id, '_event_lng', $data['loc']['lng']);
        }
    }

    /**
     * @param array<string,mixed> $data
     * @return true|\WP_Error
     */
    private function assert_contract(array $data, ImportDiagnostics $diag): true|\WP_Error
    {
        if (empty($data['start_date'])) {
            $diag->error(ProblemCode::NO_DATE, __('Data de início ausente.', 'apollo-events'));

            return new \WP_Error(
                'apollo_import_no_date',
                sprintf(
                    __('Não consegui ler a data do evento em %s.', 'apollo-events'),
                    (string) ($data['source_url'] ?? '')
                ),
                array('status' => 422, 'parsed' => $data, 'diagnostics' => $diag->to_array())
            );
        }

        if (empty($data['title'])) {
            $diag->error(ProblemCode::NO_TITLE, __('Título ausente.', 'apollo-events'));

            return new \WP_Error(
                'apollo_import_no_title',
                __('Não consegui ler o título do evento.', 'apollo-events'),
                array('status' => 422, 'parsed' => $data, 'diagnostics' => $diag->to_array())
            );
        }

        if (empty($data['cover']) || ! is_string($data['cover'])) {
            $diag->error(ProblemCode::NO_COVER, __('Capa ausente.', 'apollo-events'));

            return new \WP_Error(
                'apollo_import_no_cover',
                __('A origem não trouxe capa válida. Importação cancelada.', 'apollo-events'),
                array('status' => 422, 'parsed' => $data, 'diagnostics' => $diag->to_array())
            );
        }

        $loc_name = (string) ($data['loc']['name'] ?? '');
        $loc_slug = (string) ($data['loc']['slug'] ?? '');
        if ('' === $loc_name && '' === $loc_slug) {
            $diag->error(ProblemCode::NO_VENUE, __('Venue ausente na origem.', 'apollo-events'));

            return new \WP_Error(
                'apollo_import_no_venue',
                __('A origem não trouxe venue.', 'apollo-events'),
                array('status' => 422, 'parsed' => $data, 'diagnostics' => $diag->to_array())
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

    private function abort_import(int $post_id, bool $was_new, \WP_Error $error, ImportDiagnostics $diag): \WP_Error
    {
        if ($was_new && $post_id > 0) {
            wp_delete_post($post_id, true);
        }
        $data = $error->get_error_data();
        if (! is_array($data)) {
            $data = array('status' => 422);
        }
        $data['diagnostics'] = $diag->to_array();
        $error->add_data($data);

        return $error;
    }

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

        foreach (array_unique(array_filter(array($key, $slug))) as $try) {
            $hit = get_page_by_path($try, OBJECT, $cpt);
            if ($hit instanceof \WP_Post) {
                return (int) $hit->ID;
            }
        }

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
