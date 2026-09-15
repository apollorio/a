<?php

/**
 * Rank + validate cover image candidates from an import payload.
 *
 * @package Apollo\Event
 * @since   1.7.15
 */

declare(strict_types=1);

namespace Apollo\Event\Import\Media;

use Apollo\Event\Import\Diagnostics\ImportDiagnostics;
use Apollo\Event\Import\Diagnostics\ProblemCode;

if (! defined('ABSPATH')) {
    exit;
}

final class CoverResolver
{
    /**
     * Resolve the best cover URL and enrich payload with diagnostics.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public static function resolve(array $data, ?ImportDiagnostics $diag = null): array
    {
        $candidates = self::collect_candidates($data);
        $probed     = array();
        $winner     = '';
        $winner_src = '';

        foreach ($candidates as $candidate) {
            $url    = (string) ($candidate['url'] ?? '');
            $source = (string) ($candidate['source'] ?? 'unknown');
            if ('' === $url) {
                continue;
            }

            $entry = array(
                'url'    => $url,
                'source' => $source,
                'ok'     => false,
                'error'  => '',
            );

            if (! function_exists('\\Apollo\\Event\\apollo_event_validate_remote_image')) {
                $entry['ok'] = true;
            } else {
                $valid = \Apollo\Event\apollo_event_validate_remote_image($url);
                if (is_wp_error($valid)) {
                    $entry['error'] = $valid->get_error_message();
                    $diag?->warn(
                        ProblemCode::COVER_NOT_IMAGE,
                        sprintf(
                            /* translators: 1: source label, 2: error message */
                            __('Capa (%1$s): %2$s', 'apollo-events'),
                            $source,
                            $valid->get_error_message()
                        ),
                        array('url' => $url, 'source' => $source)
                    );
                } else {
                    $entry['ok'] = true;
                    if ('' === $winner) {
                        $winner     = $url;
                        $winner_src = $source;
                    }
                }
            }

            $probed[] = $entry;
        }

        if ('' !== $winner) {
            $data['cover'] = $winner;
            $diag?->info(
                ProblemCode::INFO,
                sprintf(
                    /* translators: %s: source label */
                    __('Capa validada via %s.', 'apollo-events'),
                    $winner_src
                ),
                array('url' => $winner, 'source' => $winner_src)
            );
        } elseif (! empty($probed) && $diag) {
            $diag->error(
                ProblemCode::NO_COVER,
                __('Nenhuma capa passou na validação de imagem.', 'apollo-events'),
                array('candidates' => $probed)
            );
        } elseif ($diag && empty($data['cover'])) {
            $diag->error(
                ProblemCode::NO_COVER,
                __('Nenhuma URL de capa encontrada na origem.', 'apollo-events')
            );
        }

        $data['cover_candidates']  = $probed;
        $data['cover_diagnostics'] = array(
            'winner'     => $winner,
            'winner_src' => $winner_src,
            'total'      => count($probed),
            'valid'      => count(array_filter($probed, static fn(array $c): bool => ! empty($c['ok']))),
        );

        return $data;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<int,array{url:string,source:string,priority:int}>
     */
    private static function collect_candidates(array $data): array
    {
        $seen  = array();
        $items = array();

        $push = static function (string $url, string $source, int $priority) use (&$items, &$seen): void {
            $url = esc_url_raw(trim($url));
            if ('' === $url || isset($seen[$url])) {
                return;
            }
            $seen[$url] = true;
            $items[]    = array(
                'url'      => $url,
                'source'   => $source,
                'priority' => $priority,
            );
        };

        if (! empty($data['cover']) && is_string($data['cover'])) {
            $push($data['cover'], 'provider_primary', 1);
        }

        foreach ((array) ($data['cover_candidates_raw'] ?? array()) as $raw) {
            if (! is_array($raw)) {
                continue;
            }
            $push(
                (string) ($raw['url'] ?? ''),
                (string) ($raw['source'] ?? 'provider_media'),
                (int) ($raw['priority'] ?? 5)
            );
        }

        foreach ((array) ($data['cover_sources'] ?? array()) as $raw) {
            if (! is_array($raw)) {
                continue;
            }
            $push(
                (string) ($raw['url'] ?? ''),
                (string) ($raw['source'] ?? 'html'),
                10
            );
        }

        usort(
            $items,
            static fn(array $a, array $b): int => ($a['priority'] <=> $b['priority'])
        );

        return $items;
    }
}
