<?php

/**
 * Import run result (preview or commit).
 *
 * @package Apollo\Event
 * @since   1.7.15
 */

declare(strict_types=1);

namespace Apollo\Event\Import\Pipeline;

use Apollo\Event\Import\Diagnostics\ImportDiagnostics;

if (! defined('ABSPATH')) {
    exit;
}

final class ImportResult
{
    /**
     * @param array<string,mixed> $data
     * @param array{cover:bool,loc:bool,date:bool,title:bool,ok:bool} $ready
     * @param array<string,mixed>|null $cover
     */
    public function __construct(
        public readonly bool $ok,
        public readonly array $data,
        public readonly ImportDiagnostics $diagnostics,
        public readonly array $ready = array(),
        public readonly ?int $post_id = null,
        public readonly bool $created = false,
        public readonly ?int $loc_id = null,
        public readonly ?array $cover = null,
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function preview_response(): array
    {
        return array(
            'ok'          => $this->ok,
            'data'        => $this->data,
            'diagnostics' => $this->diagnostics->to_array(),
            'ready'       => $this->ready,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function import_response(): array
    {
        $body = array(
            'ok'          => $this->ok,
            'created'     => $this->created,
            'post_id'     => $this->post_id,
            'edit_url'    => $this->post_id ? get_edit_post_link($this->post_id, 'raw') : '',
            'view_url'    => $this->post_id ? get_permalink($this->post_id) : '',
            'loc_id'      => $this->loc_id,
            'diagnostics' => $this->diagnostics->to_array(),
            'data'        => $this->data,
        );

        if ($this->cover) {
            $body['banner']   = $this->cover;
            $body['thumb_id'] = (int) ($this->cover['attachment_id'] ?? 0);
            $body['synced']   = ! empty($this->cover['synced']);
        }

        return $body;
    }
}
