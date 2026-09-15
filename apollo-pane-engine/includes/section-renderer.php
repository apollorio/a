<?php
/**
 * Apollo Pane Engine — Section Renderer (v2.0)
 *
 * Server-side HTML rendering for HTMX section swaps.
 * GET apollo/v1/pane/section/{slug} → returns pure HTML (not JSON).
 * Uses rest_do_request() internally to fetch data from other plugins (zero HTTP overhead).
 *
 * @package Apollo\PaneEngine
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function (): void {
    register_rest_route('apollo/v1', '/pane/section/(?P<slug>[a-z0-9-]+)', [
        'methods'             => 'GET',
        'callback'            => 'apollo_pane_section_callback',
        'permission_callback' => function (): bool {
            return is_user_logged_in();
        },
        'args' => [
            'slug' => [
                'required'          => true,
                'sanitize_callback' => 'sanitize_key',
                'validate_callback' => function (string $value): bool {
                    $allowed = ['casa', 'gigs', 'sounds', 'spots', 'social', 'tools', 'chat-inbox', 'chat-thread'];
                    return in_array($value, $allowed, true);
                },
            ],
        ],
    ]);

    // Chat thread messages (parameterized)
    register_rest_route('apollo/v1', '/pane/section/chat-thread/(?P<thread_id>\d+)', [
        'methods'             => 'GET',
        'callback'            => 'apollo_pane_chat_thread_callback',
        'permission_callback' => function (): bool {
            return is_user_logged_in();
        },
        'args' => [
            'thread_id' => [
                'required'          => true,
                'sanitize_callback' => 'absint',
                'validate_callback' => function ($value): bool {
                    return is_numeric($value) && (int) $value > 0;
                },
            ],
        ],
    ]);

    // ── CPT detail sections ────────────────────────────────────────────
    // Filterable so other plugins can register additional types.
    $cpt_sections = apply_filters('apollo/pane/cpt_sections', [
        'evento' => [
            'post_type' => 'event',
            'renderer'  => 'apollo_pane_render_evento_detail',
            'back'      => 'gigs',
        ],
        'dj' => [
            'post_type' => 'dj',
            'renderer'  => 'apollo_pane_render_dj_detail',
            'back'      => 'sounds',
        ],
        'local' => [
            'post_type' => 'local',
            'renderer'  => 'apollo_pane_render_local_detail',
            'back'      => 'spots',
        ],
    ]);

    foreach ($cpt_sections as $slug => $cfg) {
        $safe_slug = preg_quote($slug, '/');
        register_rest_route('apollo/v1', '/pane/section/' . $safe_slug . '/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => function (\WP_REST_Request $req) use ($cfg): \WP_REST_Response {
                $id = absint($req->get_param('id'));
                return call_user_func($cfg['renderer'], $id, $cfg['back']);
            },
            'permission_callback' => function (): bool {
                return is_user_logged_in();
            },
            'args' => [
                'id' => [
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($v): bool {
                        return is_numeric($v) && (int) $v > 0;
                    },
                ],
            ],
        ]);
    }
});

/**
 * Main section callback — dispatches to section renderer by slug.
 */
function apollo_pane_section_callback(\WP_REST_Request $request): \WP_REST_Response
{
    $slug = $request->get_param('slug');

    $renderers = [
        'casa'       => 'apollo_pane_render_casa',
        'gigs'       => 'apollo_pane_render_gigs',
        'sounds'     => 'apollo_pane_render_sounds',
        'spots'      => 'apollo_pane_render_spots',
        'social'     => 'apollo_pane_render_social',
        'tools'      => 'apollo_pane_render_tools',
        'chat-inbox' => 'apollo_pane_render_chat_inbox',
    ];

    if (! isset($renderers[$slug])) {
        return apollo_pane_html_response(
            '<div class="pane-card" style="border-color:var(--pane-error)"><div class="pane-card__title"><i class="ri-error-warning-line"></i> Seção inválida</div></div>',
            404
        );
    }

    $html = call_user_func($renderers[$slug]);
    return apollo_pane_html_response($html);
}

/**
 * Build an HTML WP_REST_Response.
 */
function apollo_pane_html_response(string $html, int $status = 200): \WP_REST_Response
{
    $response = new \WP_REST_Response($html, $status);
    $response->header('Content-Type', 'text/html; charset=UTF-8');
    return $response;
}

/**
 * Serve pane section responses as raw HTML — prevent WP REST JSON-encoding.
 *
 * WordPress REST API wraps all response data in json_encode(). For pane
 * sections that return HTML strings, we intercept the response and output
 * the HTML directly, returning true so WP skips its normal JSON output.
 */
add_filter(
    'rest_pre_serve_request',
    function (bool $served, \WP_HTTP_Response $result, \WP_REST_Request $request, \WP_REST_Server $server): bool {
        if ($served) {
            return $served;
        }

        // Only intercept pane section routes
        $route = (string) $request->get_route();
        if (strpos($route, '/apollo/v1/pane/section') !== 0) {
            return $served;
        }

        // Only serve as HTML when response has text/html Content-Type
        $headers      = $result->get_headers();
        $content_type = $headers['Content-Type'] ?? '';
        if (strpos($content_type, 'text/html') === false) {
            return $served;
        }

        $status = $result->get_status();
        status_header($status);
        header('Content-Type: text/html; charset=UTF-8');

        // Forward any extra headers (skip Content-Type — already set above)
        foreach ($headers as $key => $value) {
            if (strtolower($key) !== 'content-type') {
                header(esc_attr($key) . ': ' . esc_attr($value));
            }
        }

        echo $result->get_data();
        return true; // Tell WP: already served, skip JSON output
    },
    10,
    4
);

/**
 * Internal REST fetch — zero HTTP overhead.
 *
 * @param string $path REST path under apollo/v1 (e.g. '/events/upcoming')
 * @return array{ok: bool, status: int, data: mixed}
 */
function apollo_pane_internal_fetch(string $path): array
{
    try {
        $request  = new \WP_REST_Request('GET', '/apollo/v1' . $path);
        $response = rest_do_request($request);
        $data     = $response->get_data();
        $status   = $response->get_status();

        return [
            'ok'     => $status >= 200 && $status < 300,
            'status' => $status,
            'data'   => $data,
            'path'   => $path,
        ];
    } catch (\Throwable $e) {
        return [
            'ok'     => false,
            'status' => 500,
            'data'   => null,
            'path'   => $path,
            'error'  => $e->getMessage(),
        ];
    }
}

/**
 * Fail-soft multi-fetch — isolated units, never aborts siblings.
 *
 * PHP cannot true-parallel in-process REST, but each path is try/catch
 * isolated and returns an allSettled-shaped map keyed by path.
 *
 * @param string[] $paths REST paths under apollo/v1
 * @return array<string, array{ok:bool,status:int,data:mixed,path:string}>
 */
function apollo_pane_fetch_many(array $paths): array
{
    $out = [];
    foreach ($paths as $path) {
        $path = (string) $path;
        $out[$path] = apollo_pane_internal_fetch($path);
    }
    return $out;
}

/**
 * Render a status dot indicator.
 */
function apollo_pane_status_dot(bool $ok): string
{
    $class = $ok ? 'pane-endpoint__status--ok' : 'pane-endpoint__status--error';
    return '<span class="pane-endpoint__status ' . $class . '"></span>';
}

/* ══════════════════════════════════════════════════════════
 *  SECTION RENDERERS
 * ══════════════════════════════════════════════════════════ */

/**
 * Casa — health probe, plugin status, radio.
 */
function apollo_pane_render_casa(): string
{
    $bundle  = apollo_pane_fetch_many(['/health', '/pane/plugins', '/radio/status']);
    $health  = $bundle['/health'];
    $plugins = $bundle['/pane/plugins'];
    $radio   = $bundle['/radio/status'];

    $html = '<div class="pane-section pane-section--casa">';

    // Health card
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title">' . apollo_pane_status_dot($health['ok']) . ' <i class="ri-heart-pulse-line"></i> Sistema</div>';
    $html .= '<div class="pane-card__body">';
    if ($health['ok'] && is_array($health['data'])) {
        $html .= '<span class="pane-badge pane-badge--ok">Online</span>';
        if (isset($health['data']['version'])) {
            $html .= ' <span class="pane-badge pane-badge--info">v' . esc_html($health['data']['version']) . '</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Offline</span>';
    }
    $html .= '</div></div>';

    // Plugins card
    if ($plugins['ok'] && is_array($plugins['data'])) {
        $active = (int) ($plugins['data']['count_active'] ?? 0);
        $total  = (int) ($plugins['data']['count_total'] ?? 0);
        $html  .= '<div class="pane-card">';
        $html  .= '<div class="pane-card__title"><i class="ri-plug-line"></i> Plugins Apollo</div>';
        $html  .= '<div class="pane-card__body">';
        $html  .= '<span class="pane-badge pane-badge--info">' . $active . '/' . $total . ' ativos</span>';
        if (isset($plugins['data']['plugins']) && is_array($plugins['data']['plugins'])) {
            $html .= '<div class="pane-probe-grid" style="margin-top:10px">';
            foreach ($plugins['data']['plugins'] as $p) {
                $ok_class = ! empty($p['active']) ? 'pane-probe-item--ok' : '';
                $html .= '<div class="pane-probe-item ' . $ok_class . '">';
                $html .= '<span class="pane-probe-dot"></span>';
                $html .= '<span>' . esc_html($p['label'] ?? $p['slug'] ?? '') . '</span>';
                $html .= '<span style="margin-left:auto;font-size:11px;color:var(--pane-text-dim)">' . esc_html($p['layer'] ?? '') . '</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div></div>';
    }

    // Radio card
    if ($radio['ok'] && is_array($radio['data'])) {
        $html .= '<div class="pane-card">';
        $html .= '<div class="pane-card__title"><i class="ri-radio-line"></i> Rádio Apollo</div>';
        $html .= '<div class="pane-card__body">';
        $status_text = ! empty($radio['data']['is_live']) ? 'Ao vivo' : 'Off-air';
        $badge_class = ! empty($radio['data']['is_live']) ? 'pane-badge--ok' : 'pane-badge--warn';
        $html .= '<span class="pane-badge ' . $badge_class . '">' . esc_html($status_text) . '</span>';
        if (! empty($radio['data']['current_track'])) {
            $html .= ' <span style="color:var(--pane-text-dim);font-size:12px">' . esc_html($radio['data']['current_track']) . '</span>';
        }
        $html .= '</div></div>';
    }

    $html .= '</div>';
    return $html;
}

/**
 * Gigs — upcoming events (clickable → HTMX opens in pane), classifieds.
 */
function apollo_pane_render_gigs(): string
{
    $bundle      = apollo_pane_fetch_many(['/events/upcoming', '/classifieds']);
    $events      = $bundle['/events/upcoming'];
    $classifieds = $bundle['/classifieds'];

    $base = esc_url_raw(rest_url('apollo/v1/pane/section/evento/'));

    $html = '<div class="pane-section pane-section--gigs">';

    // Upcoming events
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-calendar-line"></i> Próximos Eventos</div>';
    $html .= '<div class="pane-card__body">';
    if ($events['ok'] && is_array($events['data'])) {
        $items = $events['data'];
        if (isset($items['events']) && is_array($items['events'])) {
            $items = $items['events'];
        }
        if (! empty($items)) {
            $html .= '<div class="pane-probe-grid">';
            foreach (array_slice($items, 0, 10) as $ev) {
                $ev_id    = absint($ev['id'] ?? $ev['ID'] ?? 0);
                $title    = esc_html($ev['title'] ?? $ev['post_title'] ?? '—');
                $date     = esc_html($ev['start_date'] ?? $ev['date'] ?? '');
                $permalink = $ev_id ? esc_url(get_permalink($ev_id)) : '';

                if ($ev_id) {
                    $html .= '<div class="pane-probe-item pane-probe-item--ok pane-card--clickable"'
                        . ' hx-get="' . $base . $ev_id . '"'
                        . ' hx-target="#casa-root"'
                        . ' hx-swap="innerHTML"'
                        . ($permalink ? ' hx-push-url="' . $permalink . '"' : '')
                        . ' hx-headers=\'{"Accept":"text/html"}\'>';
                } else {
                    $html .= '<div class="pane-probe-item pane-probe-item--ok">';
                }
                $html .= '<span class="pane-probe-dot"></span>';
                $html .= '<span>' . $title . '</span>';
                if ($date) {
                    $html .= '<span style="margin-left:auto;font-size:11px;color:var(--pane-text-dim)">' . $date . '</span>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            $html .= '<span class="pane-badge pane-badge--warn">Nenhum evento próximo</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Endpoint indisponível</span>';
    }
    $html .= '</div></div>';

    // Classifieds
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-megaphone-line"></i> Classificados</div>';
    $html .= '<div class="pane-card__body">';
    if ($classifieds['ok'] && is_array($classifieds['data'])) {
        $items = $classifieds['data'];
        if (isset($items['classifieds']) && is_array($items['classifieds'])) {
            $items = $items['classifieds'];
        }
        if (! empty($items)) {
            $count = is_countable($items) ? count($items) : 0;
            $html .= '<span class="pane-badge pane-badge--info">' . $count . ' anúncio(s)</span>';
        } else {
            $html .= '<span class="pane-badge pane-badge--warn">Nenhum classificado</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Endpoint indisponível</span>';
    }
    $html .= '</div></div>';

    $html .= '</div>';
    return $html;
}

/**
 * Sounds — DJs (clickable → HTMX opens DJ detail), radio.
 */
function apollo_pane_render_sounds(): string
{
    $bundle = apollo_pane_fetch_many(['/djs', '/radio/status']);
    $djs    = $bundle['/djs'];
    $radio  = $bundle['/radio/status'];

    $dj_base = esc_url_raw(rest_url('apollo/v1/pane/section/dj/'));

    $html = '<div class="pane-section pane-section--sounds">';

    // DJs
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-disc-line"></i> DJs</div>';
    $html .= '<div class="pane-card__body">';
    if ($djs['ok'] && is_array($djs['data'])) {
        $items = $djs['data'];
        if (isset($items['djs']) && is_array($items['djs'])) {
            $items = $items['djs'];
        }
        if (! empty($items)) {
            $html .= '<div class="pane-probe-grid">';
            foreach (array_slice($items, 0, 12) as $dj) {
                $dj_id     = absint($dj['id'] ?? $dj['ID'] ?? 0);
                $name      = esc_html($dj['title'] ?? $dj['name'] ?? $dj['post_title'] ?? '—');
                $sound     = esc_html($dj['sound'] ?? $dj['genre'] ?? '');
                $permalink = $dj_id ? esc_url(get_permalink($dj_id)) : '';

                if ($dj_id) {
                    $html .= '<div class="pane-probe-item pane-probe-item--ok pane-card--clickable"'
                        . ' hx-get="' . $dj_base . $dj_id . '"'
                        . ' hx-target="#casa-root"'
                        . ' hx-swap="innerHTML"'
                        . ($permalink ? ' hx-push-url="' . $permalink . '"' : '')
                        . ' hx-headers=\'{"Accept":"text/html"}\'>';
                } else {
                    $html .= '<div class="pane-probe-item pane-probe-item--ok">';
                }
                $html .= '<span class="pane-probe-dot"></span>';
                $html .= '<span>' . $name . '</span>';
                if ($sound) {
                    $html .= '<span style="margin-left:auto;font-size:11px;color:var(--pane-text-dim)">' . $sound . '</span>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            $html .= '<span class="pane-badge pane-badge--warn">Nenhum DJ encontrado</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Endpoint indisponível</span>';
    }
    $html .= '</div></div>';

    // Radio
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-radio-line"></i> Rádio Apollo</div>';
    $html .= '<div class="pane-card__body">';
    if ($radio['ok'] && is_array($radio['data'])) {
        $live = ! empty($radio['data']['is_live']);
        $html .= '<span class="pane-badge ' . ($live ? 'pane-badge--ok' : 'pane-badge--warn') . '">';
        $html .= $live ? 'Ao vivo' : 'Off-air';
        $html .= '</span>';
        if (! empty($radio['data']['current_track'])) {
            $html .= ' <span style="color:var(--pane-text-dim);font-size:12px">' . esc_html($radio['data']['current_track']) . '</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Endpoint indisponível</span>';
    }
    $html .= '</div></div>';

    $html .= '</div>';
    return $html;
}

/**
 * Spots — locations (clickable → HTMX opens loc detail).
 */
function apollo_pane_render_spots(): string
{
    $local = apollo_pane_internal_fetch('/local');

    $loc_base = esc_url_raw(rest_url('apollo/v1/pane/section/local/'));

    $html = '<div class="pane-section pane-section--spots">';
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-map-pin-line"></i> Spots</div>';
    $html .= '<div class="pane-card__body">';

    if ($local['ok'] && is_array($local['data'])) {
        $items = $local['data'];
        if (isset($items['locations']) && is_array($items['locations'])) {
            $items = $items['locations'];
        }
        if (isset($items['local']) && is_array($items['local'])) {
            $items = $items['local'];
        }
        if (! empty($items) && is_array($items)) {
            $html .= '<div class="pane-probe-grid">';
            foreach (array_slice($items, 0, 12) as $loc) {
                $loc_id    = absint($loc['id'] ?? $loc['ID'] ?? 0);
                $name      = esc_html($loc['title'] ?? $loc['name'] ?? $loc['post_title'] ?? '—');
                $area      = esc_html($loc['area'] ?? $loc['neighborhood'] ?? '');
                $permalink = $loc_id ? esc_url(get_permalink($loc_id)) : '';

                if ($loc_id) {
                    $html .= '<div class="pane-probe-item pane-probe-item--ok pane-card--clickable"'
                        . ' hx-get="' . $loc_base . $loc_id . '"'
                        . ' hx-target="#casa-root"'
                        . ' hx-swap="innerHTML"'
                        . ($permalink ? ' hx-push-url="' . $permalink . '"' : '')
                        . ' hx-headers=\'{"Accept":"text/html"}\'>';
                } else {
                    $html .= '<div class="pane-probe-item pane-probe-item--ok">';
                }
                $html .= '<span class="pane-probe-dot"></span>';
                $html .= '<span>' . $name . '</span>';
                if ($area) {
                    $html .= '<span style="margin-left:auto;font-size:11px;color:var(--pane-text-dim)">' . $area . '</span>';
                }
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            $html .= '<span class="pane-badge pane-badge--warn">Nenhum spot encontrado</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Endpoint indisponível</span>';
    }

    $html .= '</div></div>';
    $html .= '</div>';
    return $html;
}

/**
 * Social — feed, notifications, chat.
 */
function apollo_pane_render_social(): string
{
    $bundle  = apollo_pane_fetch_many(['/feed', '/notifications/unread-count', '/chat/threads']);
    $feed    = $bundle['/feed'];
    $unread  = $bundle['/notifications/unread-count'];
    $threads = $bundle['/chat/threads'];

    $html = '<div class="pane-section pane-section--social">';

    // Feed
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-rss-line"></i> Feed</div>';
    $html .= '<div class="pane-card__body">';
    if ($feed['ok'] && is_array($feed['data'])) {
        $items = $feed['data'];
        if (isset($items['feed']) && is_array($items['feed'])) {
            $items = $items['feed'];
        }
        if (isset($items['activities']) && is_array($items['activities'])) {
            $items = $items['activities'];
        }
        if (! empty($items) && is_array($items)) {
            $html .= '<div class="pane-probe-grid">';
            foreach (array_slice($items, 0, 8) as $item) {
                $text = esc_html($item['content'] ?? $item['text'] ?? $item['title'] ?? '—');
                if (mb_strlen($text) > 80) {
                    $text = mb_substr($text, 0, 80) . '…';
                }
                $html .= '<div class="pane-probe-item">';
                $html .= '<span class="pane-probe-dot" style="background:var(--pane-accent)"></span>';
                $html .= '<span>' . $text . '</span>';
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            $html .= '<span class="pane-badge pane-badge--warn">Feed vazio</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Endpoint indisponível</span>';
    }
    $html .= '</div></div>';

    // Notifications
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-notification-3-line"></i> Notificações</div>';
    $html .= '<div class="pane-card__body">';
    if ($unread['ok']) {
        $count = 0;
        if (is_array($unread['data']) && isset($unread['data']['count'])) {
            $count = (int) $unread['data']['count'];
        } elseif (is_numeric($unread['data'])) {
            $count = (int) $unread['data'];
        }
        $badge_class = $count > 0 ? 'pane-badge--warn' : 'pane-badge--ok';
        $html .= '<span class="pane-badge ' . $badge_class . '">' . $count . ' não lida(s)</span>';
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Endpoint indisponível</span>';
    }
    $html .= '</div></div>';

    // Chat threads
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-chat-3-line"></i> Chat</div>';
    $html .= '<div class="pane-card__body">';
    if ($threads['ok'] && is_array($threads['data'])) {
        $items = $threads['data'];
        if (isset($items['threads']) && is_array($items['threads'])) {
            $items = $items['threads'];
        }
        if (! empty($items) && is_array($items)) {
            $count = is_countable($items) ? count($items) : 0;
            $html .= '<span class="pane-badge pane-badge--info">' . $count . ' conversa(s)</span>';
        } else {
            $html .= '<span class="pane-badge pane-badge--warn">Nenhuma conversa</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Endpoint indisponível</span>';
    }
    $html .= '</div></div>';

    $html .= '</div>';
    return $html;
}

/**
 * Tools — user profile, membership, leaderboard.
 */
function apollo_pane_render_tools(): string
{
    $bundle      = apollo_pane_fetch_many(['/users/me', '/membership/leaderboard']);
    $me          = $bundle['/users/me'];
    $leaderboard = $bundle['/membership/leaderboard'];

    $html = '<div class="pane-section pane-section--tools">';

    // User info
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-user-line"></i> Meu Perfil</div>';
    $html .= '<div class="pane-card__body">';
    if ($me['ok'] && is_array($me['data'])) {
        $name = esc_html($me['data']['display_name'] ?? $me['data']['name'] ?? '—');
        $html .= '<span style="font-weight:600">' . $name . '</span>';
        if (! empty($me['data']['email'])) {
            $html .= ' <span style="color:var(--pane-text-dim);font-size:12px">(' . esc_html($me['data']['email']) . ')</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Endpoint indisponível</span>';
    }
    $html .= '</div></div>';

    // Leaderboard
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-trophy-line"></i> Leaderboard</div>';
    $html .= '<div class="pane-card__body">';
    if ($leaderboard['ok'] && is_array($leaderboard['data'])) {
        $items = $leaderboard['data'];
        if (isset($items['leaderboard']) && is_array($items['leaderboard'])) {
            $items = $items['leaderboard'];
        }
        if (! empty($items) && is_array($items)) {
            $html .= '<div class="pane-probe-grid">';
            $rank = 1;
            foreach (array_slice($items, 0, 10) as $entry) {
                $name   = esc_html($entry['display_name'] ?? $entry['name'] ?? '—');
                $points = isset($entry['points']) ? (int) $entry['points'] : 0;
                $html  .= '<div class="pane-probe-item pane-probe-item--ok">';
                $html  .= '<span style="font-weight:700;color:var(--pane-accent);min-width:20px">#' . $rank . '</span>';
                $html  .= '<span>' . $name . '</span>';
                $html  .= '<span style="margin-left:auto;font-size:11px;color:var(--pane-text-dim)">' . number_format($points) . ' pts</span>';
                $html  .= '</div>';
                $rank++;
            }
            $html .= '</div>';
        } else {
            $html .= '<span class="pane-badge pane-badge--warn">Sem dados</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Endpoint indisponível</span>';
    }
    $html .= '</div></div>';

    $html .= '</div>';
    return $html;
}

/**
 * Chat Inbox — list of chat threads with last message preview.
 */
function apollo_pane_render_chat_inbox(): string
{
    $threads = apollo_pane_internal_fetch('/chat/threads');

    $html = '<div class="pane-section pane-section--chat-inbox">';
    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-chat-3-line"></i> Chat Inbox</div>';
    $html .= '<div class="pane-card__body">';

    if ($threads['ok'] && is_array($threads['data'])) {
        $items = $threads['data'];
        if (isset($items['threads']) && is_array($items['threads'])) {
            $items = $items['threads'];
        }
        if (! empty($items) && is_array($items)) {
            foreach ($items as $thread) {
                $thread_id = isset($thread['id']) ? absint($thread['id']) : 0;
                $name      = esc_html($thread['participant_name'] ?? $thread['title'] ?? 'Conversa #' . $thread_id);
                $preview   = esc_html($thread['last_message'] ?? $thread['preview'] ?? '');
                if (mb_strlen($preview) > 60) {
                    $preview = mb_substr($preview, 0, 60) . '…';
                }
                $unread    = ! empty($thread['unread']);
                $unread_cl = $unread ? 'border-color:var(--pane-accent)' : '';

                $section_base = esc_url_raw(rest_url('apollo/v1/pane/section/chat-thread/' . $thread_id));

                $html .= '<div class="pane-card" style="margin-bottom:8px;padding:10px;cursor:pointer;' . $unread_cl . '"';
                $html .= ' hx-get="' . $section_base . '"';
                $html .= ' hx-target="#casa-root"';
                $html .= ' hx-swap="innerHTML"';
                $html .= ' hx-headers=\'{"Accept":"text/html"}\'>';
                $html .= '<div style="font-weight:600;font-size:13px">';
                if ($unread) {
                    $html .= '<span class="pane-badge pane-badge--info" style="margin-right:6px">novo</span>';
                }
                $html .= $name . '</div>';
                if ($preview) {
                    $html .= '<div style="color:var(--pane-text-dim);font-size:12px;margin-top:4px">' . $preview . '</div>';
                }
                $html .= '</div>';
            }
        } else {
            $html .= '<span class="pane-badge pane-badge--warn">Nenhuma conversa</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Chat indisponível</span>';
    }

    $html .= '</div></div>';
    $html .= '</div>';
    return $html;
}

/**
 * Chat Thread — individual thread messages with send form.
 */
function apollo_pane_chat_thread_callback(\WP_REST_Request $request): \WP_REST_Response
{
    $thread_id = absint($request->get_param('thread_id'));

    // Fetch messages for this thread
    $messages = apollo_pane_internal_fetch('/chat/threads/' . $thread_id . '/messages');

    $html = '<div class="pane-section pane-section--chat-thread">';

    // Back button
    $inbox_url = esc_url_raw(rest_url('apollo/v1/pane/section/chat-inbox'));
    $html .= '<button type="button" class="pane-header__back" style="margin-bottom:12px"'
        . ' hx-get="' . $inbox_url . '"'
        . ' hx-target="#casa-root"'
        . ' hx-swap="innerHTML"'
        . ' hx-headers=\'{"Accept":"text/html"}\'>'
        . '<i class="ri-arrow-left-line"></i>'
        . '</button>';

    $html .= '<div class="pane-card">';
    $html .= '<div class="pane-card__title"><i class="ri-chat-3-line"></i> Conversa #' . $thread_id . '</div>';
    $html .= '<div class="pane-card__body" id="chat-messages-' . $thread_id . '">';

    if ($messages['ok'] && is_array($messages['data'])) {
        $items = $messages['data'];
        if (isset($items['messages']) && is_array($items['messages'])) {
            $items = $items['messages'];
        }
        if (! empty($items) && is_array($items)) {
            foreach ($items as $msg) {
                $sender  = esc_html($msg['sender_name'] ?? $msg['author'] ?? '—');
                $content = esc_html($msg['content'] ?? $msg['message'] ?? '');
                $time    = esc_html($msg['created_at'] ?? $msg['date'] ?? '');
                $is_me   = ! empty($msg['is_mine']);

                $align = $is_me ? 'margin-left:auto;background:rgba(167,139,250,0.1)' : '';
                $html .= '<div class="pane-card" style="margin-bottom:6px;padding:8px;max-width:80%;' . $align . '">';
                $html .= '<div style="font-size:11px;color:var(--pane-text-dim);margin-bottom:4px">' . $sender;
                if ($time) {
                    $html .= ' · ' . $time;
                }
                $html .= '</div>';
                $html .= '<div style="font-size:13px">' . $content . '</div>';
                $html .= '</div>';
            }
        } else {
            $html .= '<span class="pane-badge pane-badge--warn">Sem mensagens</span>';
        }
    } else {
        $html .= '<span class="pane-badge pane-badge--error">Erro ao carregar mensagens</span>';
    }

    $html .= '</div></div>';

    // Send message form (hx-post)
    $send_url = esc_url_raw(rest_url('apollo/v1/chat/threads/' . $thread_id . '/messages'));
    $html .= '<form class="pane-card" style="margin-top:12px;display:flex;gap:8px;padding:10px"'
        . ' hx-post="' . $send_url . '"'
        . ' hx-target="#chat-messages-' . $thread_id . '"'
        . ' hx-swap="beforeend"'
        . ' hx-headers=\'{"Accept":"text/html"}\'>';
    $html .= '<input type="text" name="message" placeholder="Escreva uma mensagem…"'
        . ' required autocomplete="off"'
        . ' style="flex:1;background:var(--pane-bg);border:1px solid var(--pane-border);border-radius:8px;padding:8px 12px;color:var(--pane-text);font-size:13px;outline:none">';
    $html .= '<button type="submit" class="pane-badge pane-badge--info" style="padding:8px 16px;cursor:pointer;border:none;border-radius:8px;font-weight:600">';
    $html .= '<i class="ri-send-plane-fill"></i>';
    $html .= '</button>';
    $html .= '</form>';

    $html .= '</div>';

    return apollo_pane_html_response($html);
}

/* ══════════════════════════════════════════════════════════
 *  CPT DETAIL RENDERERS
 * ══════════════════════════════════════════════════════════ */

/**
 * Shared helper — build meta chip HTML.
 *
 * @param string $icon  RemixIcon class (e.g. 'ri-calendar-line')
 * @param string $value Escaped value
 */
function apollo_pane_meta_chip(string $icon, string $value): string
{
    if ('' === $value) {
        return '';
    }
    return '<span class="pane-detail__meta-item"><i class="' . $icon . '"></i> ' . $value . '</span>';
}

/**
 * Shared head for detail views — back button + title.
 *
 * @param string $back    Section slug to return to (e.g. 'gigs')
 * @param string $title   Escaped post title
 * @param string $section Human-readable section name
 */
function apollo_pane_detail_head(string $back, string $title, string $section): string
{
    $back_url = esc_url_raw(rest_url('apollo/v1/pane/section/' . $back));

    $html  = '<div class="pane-detail__nav">';
    $html .= '<button class="pane-detail__back"'
        . ' hx-get="' . $back_url . '"'
        . ' hx-target="#casa-root"'
        . ' hx-swap="innerHTML"'
        . ' hx-push-url="/casa"'
        . ' hx-headers=\'{"Accept":"text/html"}\'>';
    $html .= '<i class="ri-arrow-left-line"></i> ' . esc_html($section);
    $html .= '</button>';
    $html .= '</div>';
    $html .= '<h2 class="pane-detail__title">' . $title . '</h2>';

    return $html;
}

/**
 * Render evento (event) detail view.
 *
 * @param int    $post_id Event post ID.
 * @param string $back    Section slug to navigate back to ('gigs').
 */
function apollo_pane_render_evento_detail(int $post_id, string $back = 'gigs'): \WP_REST_Response
{
    $post = get_post($post_id);

    if (! $post || 'event' !== $post->post_type || 'publish' !== $post->post_status) {
        return apollo_pane_html_response(
            '<div class="pane-detail pane-detail--error" data-pane-view="cpt" data-pane-back-route="' . esc_attr($back) . '">'
            . '<div class="pane-card"><div class="pane-card__body"><span class="pane-badge pane-badge--error">Evento não encontrado</span></div></div>'
            . '</div>',
            404
        );
    }

    $title     = esc_html($post->post_title);
    $excerpt   = ! empty($post->post_excerpt) ? wp_kses_post($post->post_excerpt) : wp_trim_words(wp_strip_all_tags($post->post_content), 30);
    $content   = wp_kses_post($post->post_content);
    $thumbnail = get_the_post_thumbnail($post_id, 'large', ['class' => 'pane-detail__img']);

    // Meta — try REST first, fall back to post meta
    $rest      = apollo_pane_internal_fetch('/events/' . $post_id);
    $rest_data = ($rest['ok'] && is_array($rest['data'])) ? $rest['data'] : [];

    $start  = esc_html($rest_data['start_date'] ?? get_post_meta($post_id, '_event_start_date', true));
    $loc_id = absint($rest_data['location_id'] ?? get_post_meta($post_id, '_event_loc_id', true));
    $loc    = $loc_id ? esc_html(get_the_title($loc_id)) : '';
    $price  = esc_html($rest_data['price'] ?? get_post_meta($post_id, '_event_price', true));

    $html  = '<div class="pane-detail pane-section" data-pane-view="cpt" data-pane-back-route="' . esc_attr($back) . '" data-pane-title="' . esc_attr($title) . '">';
    $html .= apollo_pane_detail_head($back, $title, 'Eventos');

    if ($thumbnail) {
        $html .= '<div class="pane-detail__cover">' . $thumbnail . '</div>';
    }

    // Meta row
    $html .= '<div class="pane-detail__meta">';
    $html .= apollo_pane_meta_chip('ri-calendar-line', $start);
    $html .= apollo_pane_meta_chip('ri-map-pin-line', $loc);
    $html .= apollo_pane_meta_chip('ri-ticket-line', $price);
    $html .= '</div>';

    if ($excerpt) {
        $html .= '<p class="pane-detail__excerpt">' . $excerpt . '</p>';
    }
    if ($content) {
        $html .= '<div class="pane-detail__body">' . $content . '</div>';
    }

    $html .= '</div>';

    return apollo_pane_html_response($html);
}

/**
 * Render DJ detail view.
 *
 * @param int    $post_id DJ post ID.
 * @param string $back    Section slug to navigate back to ('sounds').
 */
function apollo_pane_render_dj_detail(int $post_id, string $back = 'sounds'): \WP_REST_Response
{
    $post = get_post($post_id);

    if (! $post || 'dj' !== $post->post_type || 'publish' !== $post->post_status) {
        return apollo_pane_html_response(
            '<div class="pane-detail pane-detail--error" data-pane-view="cpt" data-pane-back-route="' . esc_attr($back) . '">'
            . '<div class="pane-card"><div class="pane-card__body"><span class="pane-badge pane-badge--error">DJ não encontrado</span></div></div>'
            . '</div>',
            404
        );
    }

    $title     = esc_html($post->post_title);
    $excerpt   = ! empty($post->post_excerpt) ? wp_kses_post($post->post_excerpt) : wp_trim_words(wp_strip_all_tags($post->post_content), 30);
    $content   = wp_kses_post($post->post_content);
    $thumbnail = get_the_post_thumbnail($post_id, 'large', ['class' => 'pane-detail__img']);

    // Meta — try REST first
    $rest      = apollo_pane_internal_fetch('/djs/' . $post_id);
    $rest_data = ($rest['ok'] && is_array($rest['data'])) ? $rest['data'] : [];

    // Sound genres from taxonomy
    $sounds = wp_get_post_terms($post_id, 'sound', ['fields' => 'names']);
    $sound  = (! is_wp_error($sounds) && ! empty($sounds)) ? esc_html(implode(', ', $sounds)) : '';
    $city   = esc_html($rest_data['city'] ?? get_post_meta($post_id, '_dj_city', true));

    $html  = '<div class="pane-detail pane-section" data-pane-view="cpt" data-pane-back-route="' . esc_attr($back) . '" data-pane-title="' . esc_attr($title) . '">';
    $html .= apollo_pane_detail_head($back, $title, 'DJs');

    if ($thumbnail) {
        $html .= '<div class="pane-detail__cover">' . $thumbnail . '</div>';
    }

    $html .= '<div class="pane-detail__meta">';
    $html .= apollo_pane_meta_chip('ri-disc-line', $sound);
    $html .= apollo_pane_meta_chip('ri-map-pin-line', $city);
    $html .= '</div>';

    if ($excerpt) {
        $html .= '<p class="pane-detail__excerpt">' . $excerpt . '</p>';
    }
    if ($content) {
        $html .= '<div class="pane-detail__body">' . $content . '</div>';
    }

    $html .= '</div>';

    return apollo_pane_html_response($html);
}

/**
 * Render local (location/spot) detail view.
 *
 * @param int    $post_id Location post ID.
 * @param string $back    Section slug to navigate back to ('spots').
 */
function apollo_pane_render_local_detail(int $post_id, string $back = 'spots'): \WP_REST_Response
{
    $post = get_post($post_id);

    if (! $post || 'local' !== $post->post_type || 'publish' !== $post->post_status) {
        return apollo_pane_html_response(
            '<div class="pane-detail pane-detail--error" data-pane-view="cpt" data-pane-back-route="' . esc_attr($back) . '">'
            . '<div class="pane-card"><div class="pane-card__body"><span class="pane-badge pane-badge--error">Spot não encontrado</span></div></div>'
            . '</div>',
            404
        );
    }

    $title     = esc_html($post->post_title);
    $excerpt   = ! empty($post->post_excerpt) ? wp_kses_post($post->post_excerpt) : wp_trim_words(wp_strip_all_tags($post->post_content), 30);
    $content   = wp_kses_post($post->post_content);
    $thumbnail = get_the_post_thumbnail($post_id, 'large', ['class' => 'pane-detail__img']);

    // Meta — try REST first
    $rest      = apollo_pane_internal_fetch('/local/' . $post_id);
    $rest_data = ($rest['ok'] && is_array($rest['data'])) ? $rest['data'] : [];

    // Area from taxonomy
    $areas = wp_get_post_terms($post_id, 'loc_area', ['fields' => 'names']);
    $area  = (! is_wp_error($areas) && ! empty($areas)) ? esc_html($areas[0]) : '';
    $addr  = esc_html($rest_data['address'] ?? get_post_meta($post_id, '_loc_address', true));

    $html  = '<div class="pane-detail pane-section" data-pane-view="cpt" data-pane-back-route="' . esc_attr($back) . '" data-pane-title="' . esc_attr($title) . '">';
    $html .= apollo_pane_detail_head($back, $title, 'Spots');

    if ($thumbnail) {
        $html .= '<div class="pane-detail__cover">' . $thumbnail . '</div>';
    }

    $html .= '<div class="pane-detail__meta">';
    $html .= apollo_pane_meta_chip('ri-map-pin-2-line', $area);
    $html .= apollo_pane_meta_chip('ri-road-map-line', $addr);
    $html .= '</div>';

    if ($excerpt) {
        $html .= '<p class="pane-detail__excerpt">' . $excerpt . '</p>';
    }
    if ($content) {
        $html .= '<div class="pane-detail__body">' . $content . '</div>';
    }

    $html .= '</div>';

    return apollo_pane_html_response($html);
}
