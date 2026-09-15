<?php

/**
 * Sideload import cover into WP media with source-URL dedup.
 *
 * @package Apollo\Event
 * @since   1.7.15
 */

declare(strict_types=1);

namespace Apollo\Event\Import\Media;

if (! defined('ABSPATH')) {
    exit;
}

final class CoverSideloader
{
    public const SOURCE_META = '_apollo_import_source_url';

    /**
     * @return array{attachment_id:int,local_url:string,reused:bool,source_url:string}|\WP_Error
     */
    public static function attach(int $post_id, string $source_url, string $title = 'evento')
    {
        $source_url = esc_url_raw(trim($source_url));
        if ('' === $source_url || $post_id <= 0) {
            return new \WP_Error('apollo_cover_bad_args', __('Capa ou post inválido.', 'apollo-events'));
        }

        $existing = self::find_by_source_url($source_url);
        if ($existing > 0) {
            $attached = self::bind_to_post($post_id, $existing);
            if (is_wp_error($attached)) {
                return $attached;
            }

            return array(
                'attachment_id' => $existing,
                'local_url'     => (string) wp_get_attachment_url($existing),
                'reused'        => true,
                'source_url'    => $source_url,
            );
        }

        if (! function_exists('\\Apollo\\Event\\apollo_event_set_banner')) {
            return new \WP_Error('apollo_cover_missing_fn', 'apollo_event_set_banner unavailable');
        }

        $att = \Apollo\Event\apollo_event_set_banner($post_id, $source_url, true);
        if (is_wp_error($att)) {
            return $att;
        }

        $att_id = (int) $att;
        if ($att_id <= 0) {
            return new \WP_Error(
                'apollo_cover_sideload_failed',
                __('Falha ao gravar a capa como featured image.', 'apollo-events'),
                array('status' => 422)
            );
        }

        update_post_meta($att_id, self::SOURCE_META, $source_url);

        return array(
            'attachment_id' => $att_id,
            'local_url'     => (string) wp_get_attachment_url($att_id),
            'reused'        => false,
            'source_url'    => $source_url,
        );
    }

    public static function find_by_source_url(string $source_url): int
    {
        $source_url = esc_url_raw(trim($source_url));
        if ('' === $source_url) {
            return 0;
        }

        $q = new \WP_Query(
            array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => array(
                    array(
                        'key'   => self::SOURCE_META,
                        'value' => $source_url,
                    ),
                ),
            )
        );

        return $q->posts ? (int) $q->posts[0] : 0;
    }

    /**
     * @return true|\WP_Error
     */
    private static function bind_to_post(int $post_id, int $att_id)
    {
        if ('attachment' !== get_post_type($att_id)) {
            return new \WP_Error('apollo_cover_bad_attachment', 'Banner attachment not found.');
        }

        update_post_meta($post_id, '_event_banner', $att_id);
        set_post_thumbnail($post_id, $att_id);

        $thumb = (int) get_post_thumbnail_id($post_id);
        $meta  = (int) get_post_meta($post_id, '_event_banner', true);
        if ($att_id !== $thumb || $att_id !== $meta) {
            return new \WP_Error(
                'apollo_import_banner_desync',
                __('Capa gravada mas _event_banner ≠ featured image.', 'apollo-events'),
                array('status' => 422, 'banner_meta' => $meta, 'thumb_id' => $thumb, 'att_id' => $att_id)
            );
        }

        return true;
    }
}
