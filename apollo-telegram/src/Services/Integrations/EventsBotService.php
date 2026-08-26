<?php

/**
 * Events intelligence for the Telegram bot — keyword/intent detection (pt-BR
 * slang + English), date-range resolution, apollo-events CPT querying and
 * weather-aware reply formatting.
 *
 * Zero hard dependency: every capability degrades gracefully when
 * apollo-events (CPT `event`) or the weather endpoint is unavailable.
 *
 * VIBE QUIZ (2026-08-25): before the FIRST real event suggestion for a chat,
 * handle() asks the a/b/c/d "what kind of party suits you" quiz (question
 * text + matching rule both owned by apollo_event_vibe_quiz_definition() in
 * apollo-events — never duplicated here) via sendVibeQuiz(), and stashes the
 * intent that triggered it (stashPendingIntent()) so answering resumes
 * exactly what was asked instead of re-prompting. The answer is sticky per
 * chat_id (getKnownVibe()/setKnownVibe(), one non-autoloaded option row) —
 * once known, every subsequent getEvents() call is filtered to that vibe.
 * "Muda minha vibe" (detectIntent()'s 'vibe_reset' branch) resets it.
 * getEvents() also sorts by _event_int_rank descending before capping the
 * display list — the golden-trophy 🏆 in buildReply() always marks the
 * best-ranked event of the batch, never an arbitrary chronological pick.
 * VibeQuizCallback (src/Telegram/CallbackQueries/VibeQuizCallback.php)
 * is the inline-keyboard tap handler; replyForVibeAnswer() is its one entry
 * point, also reused by GenericCommand for a bare "a"/"b"/"c"/"d" text reply
 * while a quiz is pending (hasPendingIntent()).
 *
 * BILINGUAL (2026-08-25): every method here takes a $lang ('pt'|'en')
 * resolved by the caller via Apollo\Telegram\Services\Lang::forChat() — this
 * class never resolves it itself, it only renders in whatever language it's
 * told. All display text comes from Lang::t(); the vibe-quiz question/label
 * text comes from apollo_event_vibe_quiz_definition($lang) in apollo-events.
 * detectIntent()'s regexes already accept both pt-BR and English trigger
 * phrases regardless of $lang (a Portuguese speaker typing "tonight" still
 * works) — $lang only controls what the BOT says back, never what it
 * understands.
 *
 * @package Apollo\Telegram\Services\Integrations
 */

declare(strict_types=1);

namespace Apollo\Telegram\Services\Integrations;

use Apollo\Telegram\Security\RateLimiter;
use Apollo\Telegram\Services\Lang;

if (! defined('ABSPATH')) {
    exit;
}

final class EventsBotService
{
    private const CPT             = 'event';
    private const WEATHER_CACHE   = 'apollo_tg_weather';
    private const WEATHER_TTL     = 15 * MINUTE_IN_SECONDS;
    private const MAX_EVENTS      = 8;

    /**
     * "Que tipo de festa combina com você" — vibe quiz state (2026-08-25).
     *
     * VIBE_OPTION: single non-autoloaded option row, {chat_id: {answer,set_at}}.
     * One option row instead of one per chat — a Telegram chat_id-per-row
     * scheme would grow the options table unbounded with every new chat.
     * Sticky on purpose (no expiry): asking the quiz on every single message
     * is not how a person recommending parties to a friend behaves. The user
     * can always retrigger it by asking to change their vibe (see
     * detectIntent()'s 'vibe_reset' branch) or by replying a bare a/b/c/d
     * while no quiz is pending (see hasPendingIntent()).
     *
     * PENDING_INTENT_*: short-lived transient holding the events intent that
     * was interrupted by the quiz (period/neighborhood/label), so answering
     * the quiz resumes exactly what the person originally asked for instead
     * of re-asking or defaulting blindly.
     */
    private const VIBE_OPTION           = 'apollo_tg_vibe_prefs';
    private const PENDING_INTENT_PREFIX = 'apollo_tg_pending_intent_';
    private const PENDING_INTENT_TTL    = 10 * MINUTE_IN_SECONDS;
    private const VIBE_ANSWERS          = array('a', 'b', 'c', 'd');

    /**
     * Rio de Janeiro fallback coordinates for Open-Meteo.
     */
    private const RIO_LAT = -22.9068;
    private const RIO_LON = -43.1729;

    /**
     * Neighborhood / region keywords → canonical filter label.
     *
     * @var array<string, string>
     */
    private const NEIGHBORHOODS = array(
        'botafogo'      => 'Botafogo',
        'copacabana'    => 'Copacabana',
        'copa'          => 'Copacabana',
        'ipanema'       => 'Ipanema',
        'leblon'        => 'Leblon',
        'lapa'          => 'Lapa',
        'centro'        => 'Centro',
        'gavea'         => 'Gávea',
        'flamengo'      => 'Flamengo',
        'laranjeiras'   => 'Laranjeiras',
        'tijuca'        => 'Tijuca',
        'barra'         => 'Barra',
        'santa teresa'  => 'Santa Teresa',
        'madureira'     => 'Madureira',
        'niteroi'       => 'Niterói',
        'zona sul'      => 'Zona Sul',
        'zona norte'    => 'Zona Norte',
        'zona oeste'    => 'Zona Oeste',
    );

    /**
     * Entry point used by GenericCommand. Returns a ready-to-send HTML reply
     * or null when the text carries no events intent (verification flow and
     * other handlers stay untouched).
     */
    public static function handle(string $text, int $chat_id, string $lang = Lang::DEFAULT): ?string
    {
        // Explicit language switch ("fala em inglês" / "speak english") wins
        // over everything else — a person asking to change language mid-chat
        // doesn't want that sentence ALSO parsed as an event question.
        $switch = Lang::detectSwitchIntent(self::normalize($text));
        if (null !== $switch) {
            Lang::setForChat($chat_id, $switch);

            return Lang::t('en' === $switch ? 'lang_switched_en' : 'lang_switched_pt', $switch, array(), $chat_id);
        }

        $intent = self::detectIntent($text, $lang);
        if (null === $intent) {
            return null;
        }

        if (! RateLimiter::allow('events_chat:' . $chat_id, 10, MINUTE_IN_SECONDS)) {
            return Lang::t('rate_limited_events', $lang, array(), $chat_id);
        }

        if ('weather' === $intent['type']) {
            $weather = self::getWeather($lang, $chat_id);

            if (! $weather) {
                return Lang::t('weather_unavailable', $lang, array(), $chat_id);
            }

            $reply = sprintf('%s %s (%d°C). %s', $weather['emoji'], ucfirst($weather['condition']), $weather['temp'], $weather['greeting']);
            if ('' !== $weather['alert']) {
                $reply .= "\n\n⚠️ " . $weather['alert'];
            }

            return $reply;
        }

        // "Muda minha vibe" — explicit re-ask, always sends a fresh quiz even
        // if one is already known. Handled before the gate below so it never
        // gets short-circuited by an existing preference.
        if ('vibe_reset' === $intent['type']) {
            self::forgetVibe($chat_id);
            self::sendVibeQuiz($chat_id, $lang, Lang::t('vibe_reset_intro', $lang, array(), $chat_id));

            return null;
        }

        // Vibe quiz gate — every real "show me events" ask (not the one-off
        // "me surpreende", which explicitly opts OUT of curation) is filtered
        // by the a/b/c/d vibe once we know it. First time for this chat: ask
        // before suggesting, exactly as requested, and resume this same
        // intent once answered (see popPendingIntent() in replyForVibeAnswer()).
        if ('random' !== $intent['type'] && function_exists('apollo_event_vibe_quiz_definition')) {
            $known_vibe = self::getKnownVibe($chat_id);
            if (null === $known_vibe) {
                self::stashPendingIntent($chat_id, $intent);
                self::sendVibeQuiz($chat_id, $lang);

                return null; // Quiz already sent directly — nothing more to reply.
            }
            $intent['vibe'] = $known_vibe;
        }

        $events = self::getEvents($intent['start'], $intent['end'], $intent['neighborhood'], $intent['vibe'] ?? null);

        if ('random' === $intent['type'] && count($events) > 2) {
            shuffle($events);
            $events = array_slice($events, 0, 2);
        }

        return self::buildReply($events, $intent, $lang, $chat_id);
    }

    /**
     * Callback-query entry point (VibeQuizCallback) — the user just tapped
     * a/b/c/d, or typed the bare letter while a quiz was pending (see
     * GenericCommand's bare-letter branch, gated by hasPendingIntent()).
     * Persists the answer, resumes whatever intent the quiz interrupted (or
     * falls back to "the good stuff this weekend/next 7 days" — same default
     * "qual a boa" already uses — if nothing was pending), and returns the
     * ready-to-send reply.
     */
    public static function replyForVibeAnswer(int $chat_id, string $answer, string $lang = Lang::DEFAULT): string
    {
        $answer = strtolower(trim($answer));
        if (! in_array($answer, self::VIBE_ANSWERS, true)) {
            return Lang::t('vibe_unknown_answer', $lang, array(), $chat_id);
        }

        self::setKnownVibe($chat_id, $answer);

        $intent = self::popPendingIntent($chat_id);
        if (null === $intent) {
            $period = self::isWeekendish() ? 'weekend' : 'next7';
            $intent = self::intent('events', Lang::t('label_boa_cidade', $lang), $period, null);
        }
        $intent['vibe'] = $answer;

        $events = self::getEvents($intent['start'], $intent['end'], $intent['neighborhood'], $answer);

        $definition = function_exists('apollo_event_vibe_quiz_definition') ? apollo_event_vibe_quiz_definition($lang) : array();
        $vibe_label = isset($definition[$answer]['label']) ? (string) $definition[$answer]['label'] : strtoupper($answer);

        $preamble = Lang::t('vibe_ack', $lang, array(esc_html($vibe_label)), $chat_id) . "\n\n";

        return $preamble . self::buildReply($events, $intent, $lang, $chat_id);
    }

    /** Is there a quiz actively pending for this chat right now? */
    public static function hasPendingIntent(int $chat_id): bool
    {
        return false !== get_transient(self::PENDING_INTENT_PREFIX . $chat_id);
    }

    /**
     * Keyword/intent detection: strong triggers first, then period table,
     * then random/weather. Accent-insensitive, slang-tolerant.
     *
     * @return array{type:string,label:string,start:string,end:string,neighborhood:?string}|null
     */
    public static function detectIntent(string $text, string $lang = Lang::DEFAULT): ?array
    {
        $t = self::normalize($text);
        if ('' === $t || strlen($t) > 200) {
            return null;
        }

        $neighborhood = self::detectNeighborhood($t);

        // Weather-only question.
        if (preg_match('/\bvai chover\b|\bcomo (ta|esta) o tempo\b|\bprevisao\b|\bweather\b/', $t)) {
            return self::intent('weather', Lang::t('label_weather', $lang), 'today', $neighborhood);
        }

        // "Muda minha vibe" / "outro tipo de festa" — explicit re-ask of the
        // a/b/c/d quiz. Checked early, before any events keyword, so it
        // always wins even inside a sentence that also mentions "festa".
        if (preg_match('/\bmuda(r)? (a )?(minha )?vibe\b|\btroca(r)? (a )?(minha )?vibe\b|\bmuda(r)? (o )?(meu )?perfil\b|\boutro tipo de festa\b|\bnao curto isso\b|\bchange my vibe\b|\bdifferent (kind of )?party\b/', $t)) {
            return self::intent('vibe_reset', Lang::t('label_vibe', $lang), 'today', $neighborhood);
        }

        // Random pick: "me surpreende", "escolhe pra mim", "qualquer coisa".
        if (preg_match('/\bme surpreende\b|\bescolhe (pra|para) mim\b|\bqualquer coisa\b|\bsurprise me\b|\bpick for me\b|\banything\b/', $t)) {
            return self::intent('random', Lang::t('label_random', $lang), 'next14', $neighborhood);
        }

        // ── Strong triggers (Very High priority) ──
        // "qual a boa" and friends → this weekend (Fri-Sun) or next 7 days.
        if (preg_match('/\bqual (e |é )?a boa\b|\bme fala a boa\b|\bquais festas\b|\bque festas\b|\blista de festas\b|\bwhat.?s good\b|\bwhat.?s the move\b/', $t)) {
            $period = self::isWeekendish() ? 'weekend' : 'next7';

            return self::intent('events', Lang::t('label_boa_cidade', $lang), $period, $neighborhood);
        }

        // "esse fds" + typos → this weekend.
        if (preg_match('/\b(esse|este|nesse|nessi|ness|no) f(d|in)s\b|\bfds\b|\besse (final|fim|finalzinho) de semana\b|\bthis weekend\b/', $t)) {
            // "próximo fds" / "fds que vem" wins over plain "fds".
            if (preg_match('/\b(proximo|prox) f(d|in)s\b|\bfds (que|q) vem\b|\bnext weekend\b|\bproximo (final|fim) de semana\b/', $t)) {
                return self::intent('events', Lang::t('label_next_weekend', $lang), 'next_weekend', $neighborhood);
            }

            return self::intent('events', Lang::t('label_this_weekend', $lang), 'weekend', $neighborhood);
        }

        if (preg_match('/\b(proximo|prox) f(d|in)s\b|\bfds (que|q) vem\b|\bnext weekend\b/', $t)) {
            return self::intent('events', Lang::t('label_next_weekend', $lang), 'next_weekend', $neighborhood);
        }

        // "tem festa", "o que tem na cidade", "o que vai rolar", "agenda" → upcoming.
        if (preg_match('/\btem (festa|festas|festinha|role|algo)\b|\bvai ter (festa|algo)\b|\bo que (tem|vai ter|vai rolar|rola|ta rolando|esta rolando)\b|\bagenda\b|\bupcoming\b|\bwhat.?s (happening|up|on)\b/', $t)) {
            $period = self::isWeekendish() ? 'weekend' : 'next7';

            return self::intent('events', Lang::t('label_upcoming', $lang), $period, $neighborhood);
        }

        // ── Period table ──
        if (preg_match('/\bhoje a noite\b|\bhj a noite\b|\btonight\b/', $t)) {
            return self::intent('events', Lang::t('label_tonight', $lang), 'today', $neighborhood);
        }
        if (preg_match('/\bhoje\b|\bhj\b|\btoday\b/', $t)) {
            return self::intent('events', Lang::t('label_today', $lang), 'today', $neighborhood);
        }
        if (preg_match('/\bamanha\b|\bamnh\b|\btomorrow\b/', $t)) {
            return self::intent('events', Lang::t('label_tomorrow', $lang), 'tomorrow', $neighborhood);
        }
        if (preg_match('/\b(proxima|prox) semana\b|\bsemana (que|q) vem\b|\bnext week\b/', $t)) {
            return self::intent('events', Lang::t('label_next_week', $lang), 'next_week', $neighborhood);
        }
        if (preg_match('/\b(essa|esta|nessa) semana?\b|\bthis week\b/', $t)) {
            return self::intent('events', Lang::t('label_this_week', $lang), 'week', $neighborhood);
        }
        if (preg_match('/\b(esse|este|nesse) mes\b|\bmes (que|q) vem\b|\b(essa|esta) temporada\b|\bthis (month|season)\b/', $t)) {
            return self::intent('events', Lang::t('label_this_month', $lang), 'month', $neighborhood);
        }

        // Neighborhood-only question ("só botafogo", "perto de mim" handled as generic upcoming).
        if (null !== $neighborhood && preg_match('/\bso \w+|\bperto de mim\b|\bna regiao\b|\baqui perto\b|\bnear me\b|\bjust \w+\b/', $t)) {
            return self::intent('events', $neighborhood, 'next7', $neighborhood);
        }

        return null;
    }

    /**
     * Query apollo-events CPT for published events inside the range.
     *
     * Golden-trophy ordering (2026-08-25): the candidate pool is fetched
     * uncapped (up to the WP_Query's own 50-row ceiling) and only capped to
     * MAX_EVENTS AFTER sorting by _event_int_rank desc — capping mid-loop
     * (the old behaviour) could fill all 8 display slots with early-dated,
     * unranked events before a same-week 10-ranked event ever got a chance
     * to be considered. $vibe_answer, when given, filters to events matching
     * that a/b/c/d quiz letter (apollo_event_matches_vibe_quiz(), owned by
     * apollo-events) — graceful no-op filter if apollo-events isn't loaded.
     *
     * @return array<int, array{id:int,title:string,date:string,time:string,venue:string,neighborhood:string,price:string,url:string,rank:int}>
     */
    public static function getEvents(string $start, string $end, ?string $neighborhood = null, ?string $vibe_answer = null): array
    {
        if (! post_type_exists(self::CPT)) {
            return array();
        }

        $query = new \WP_Query(
            array(
                'post_type'      => self::CPT,
                'post_status'    => 'publish',
                'posts_per_page' => 50,
                'no_found_rows'  => true,
                'meta_key'       => '_event_start_date',
                'orderby'        => 'meta_value',
                'order'          => 'ASC',
                'meta_query'     => array(
                    array(
                        'key'     => '_event_start_date',
                        'value'   => array($start, $end),
                        'compare' => 'BETWEEN',
                        'type'    => 'DATE',
                    ),
                ),
            )
        );

        $events = array();

        foreach ($query->posts as $post) {
            $loc_id    = (int) get_post_meta($post->ID, '_event_loc_id', true);
            $venue     = $loc_id ? (string) get_the_title($loc_id) : '';
            $areas     = $loc_id ? get_the_terms($loc_id, 'local_area') : false;
            $area_name = (is_array($areas) && ! empty($areas)) ? $areas[0]->name : '';

            if (null !== $neighborhood) {
                $haystack = self::normalize($venue . ' ' . $area_name);
                if (! str_contains($haystack, self::normalize($neighborhood))) {
                    continue;
                }
            }

            if (null !== $vibe_answer && function_exists('apollo_event_matches_vibe_quiz')) {
                if (! apollo_event_matches_vibe_quiz($post->ID, $vibe_answer)) {
                    continue;
                }
            }

            $events[] = array(
                'id'           => $post->ID,
                'title'        => (string) get_the_title($post),
                'date'         => (string) get_post_meta($post->ID, '_event_start_date', true),
                'time'         => (string) get_post_meta($post->ID, '_event_start_time', true),
                'venue'        => $venue,
                'neighborhood' => $area_name,
                'price'        => (string) get_post_meta($post->ID, '_event_ticket_price', true),
                'url'          => (string) get_permalink($post),
                'rank'         => function_exists('apollo_event_get_int_rank') ? apollo_event_get_int_rank($post->ID) : 0,
            );
        }

        // Best int-rank first; same rank keeps date order (soonest first) —
        // same tie-break rule as apollo-events' own apollo_event_get_top_ranked(),
        // for one consistent "what's best" ordering across the whole ecosystem.
        usort($events, static function (array $a, array $b): int {
            if ($a['rank'] === $b['rank']) {
                return strcmp($a['date'], $b['date']);
            }
            return $b['rank'] <=> $a['rank'];
        });

        return array_slice($events, 0, self::MAX_EVENTS);
    }

    /**
     * Telegram HTML reply: header + weather line (when relevant) + event list.
     *
     * @param array<int, array<string, string>> $events Event rows.
     * @param array<string, mixed>              $intent Detected intent.
     */
    private static function buildReply(array $events, array $intent, string $lang = Lang::DEFAULT, ?int $chat_id = null): string
    {
        $lines = array();

        if (empty($events)) {
            $lines[] = Lang::t('no_events', $lang, array((string) $intent['label']), $chat_id);
            $lines[] = Lang::t('no_events_cta', $lang, array(), $chat_id);

            return implode("\n", $lines);
        }

        $lines[] = Lang::t('events_header', $lang, array(esc_html((string) $intent['label'])), $chat_id);

        // Weather line for near-term periods; adverse alert shown for any period.
        $weather = self::getWeather($lang, $chat_id);
        if ($weather && in_array($intent['period'], array('today', 'weekend'), true)) {
            $lines[] = sprintf('%s %s (%d°C) — %s', $weather['emoji'], ucfirst($weather['condition']), $weather['temp'], $weather['greeting']);
        }
        if ($weather && '' !== $weather['alert']) {
            $lines[] = '⚠️ <b>' . esc_html($weather['alert']) . '</b>';
        }

        $lines[] = '';

        foreach ($events as $i => $event) {
            $when = self::humanDate($event['date'], $lang);
            if ('' !== $event['time']) {
                $when .= ' · ' . $event['time'];
            }

            $where = $event['venue'];
            if ('' !== $event['neighborhood']) {
                $where .= ('' !== $where ? ', ' : '') . $event['neighborhood'];
            }

            // Golden trophy — always the best int-rank first (getEvents()
            // already sorted the list that way). Only shown when the top
            // event actually carries a real ranking (rank > 0): an all-zero
            // pool means no admin has curated anything here yet, and a
            // trophy on an arbitrary chronological pick would be a lie.
            $icon = (0 === $i && ($event['rank'] ?? 0) > 0) ? '🏆' : '🗓';

            $line = sprintf('%s <b>%s</b>', $icon, esc_html($event['title']));
            $line .= "\n     " . esc_html($when);
            if ('' !== $where) {
                $line .= ' — ' . esc_html($where);
            }
            if ('' !== $event['price']) {
                $line .= ' · ' . esc_html($event['price']);
            }
            if ('' !== $event['url']) {
                $line .= "\n     " . esc_url($event['url']);
            }

            $lines[] = $line;
        }

        $lines[] = '';
        $lines[] = Lang::t('filter_hint', $lang, array(), $chat_id);

        return implode("\n", $lines);
    }

    /**
     * Weather snapshot — apollo/v1/weather internal route when available,
     * Open-Meteo (Rio) otherwise. The RAW numeric reading is cached for 15
     * minutes (language-neutral: temp/code/rain/wind never change with
     * $lang); condition/greeting/alert TEXT is generated fresh on every
     * call from that raw data, in the requested language. This matters —
     * baking localized text into the cached value would let whichever
     * language asked first "win" the cache for the next 15 minutes for
     * every other chat, regardless of their own language.
     *
     * The apollo/v1/weather route (when present) returns its OWN
     * pre-formatted condition/greeting/alert text that we don't control and
     * can't safely re-localize — used only for $lang === 'pt' (today's only
     * consumer of that route); 'en' requests go straight to Open-Meteo,
     * which we fully control and DO localize.
     *
     * @return array{temp:int,condition:string,emoji:string,greeting:string,is_raining:bool,alert:string}|null
     */
    public static function getWeather(string $lang = Lang::DEFAULT, ?int $chat_id = null): ?array
    {
        $raw = get_transient(self::WEATHER_CACHE);
        if (! is_array($raw)) {
            $raw = ('pt' === $lang ? self::weatherRawFromApolloEndpoint() : null) ?? self::weatherRawFromOpenMeteo();
            if (null === $raw) {
                return null;
            }
            set_transient(self::WEATHER_CACHE, $raw, self::WEATHER_TTL);
        }

        return self::localizeWeather($raw, $lang, $chat_id);
    }

    /** Turn a raw weather reading into display text for $lang. */
    private static function localizeWeather(array $raw, string $lang, ?int $chat_id = null): array
    {
        if (! empty($raw['pre_localized'])) {
            // apollo/v1/weather's own text — see getWeather() docblock.
            unset($raw['pre_localized']);

            return $raw;
        }

        $code       = (int) ($raw['code'] ?? 0);
        $temp       = (float) ($raw['temp'] ?? 0);
        $wind       = (float) ($raw['wind'] ?? 0);
        $rain_mm    = (float) ($raw['rain_mm'] ?? 0);
        $is_raining = ! empty($raw['is_raining']);

        list($emoji, $condition) = self::wmoToCondition($code, $lang);

        return array(
            'temp'       => (int) round($temp),
            'condition'  => $condition,
            'emoji'      => $emoji,
            'greeting'   => Lang::t($is_raining ? 'weather_greeting_rain' : 'weather_greeting_good', $lang, array(), $chat_id),
            'is_raining' => $is_raining,
            'alert'      => self::adverseAlert($temp, $wind, $rain_mm, $code, $lang, $chat_id),
        );
    }

    /** Internal REST dispatch to /apollo/v1/weather when a plugin provides it. Pre-localized (pt), see getWeather(). */
    private static function weatherRawFromApolloEndpoint(): ?array
    {
        if (! function_exists('rest_get_server')) {
            return null;
        }

        $routes = rest_get_server()->get_routes('apollo/v1');
        if (empty($routes['/apollo/v1/weather'])) {
            return null;
        }

        $response = rest_do_request(new \WP_REST_Request('GET', '/apollo/v1/weather'));
        if ($response->is_error() || 200 !== $response->get_status()) {
            return null;
        }

        $data = (array) $response->get_data();
        if (! isset($data['temp'])) {
            return null;
        }

        return array(
            'temp'           => (int) $data['temp'],
            'condition'      => (string) ($data['condition'] ?? $data['comment'] ?? ''),
            'emoji'          => (string) ($data['emoji'] ?? '🌤'),
            'greeting'       => (string) ($data['greeting'] ?? ''),
            'is_raining'     => ! empty($data['is_raining']),
            'alert'          => (string) ($data['alert'] ?? ''),
            'pre_localized'  => true,
        );
    }

    /** Open-Meteo current weather for Rio (no key required). Raw/language-neutral, see getWeather(). */
    private static function weatherRawFromOpenMeteo(): ?array
    {
        $url = add_query_arg(
            array(
                'latitude'        => self::RIO_LAT,
                'longitude'       => self::RIO_LON,
                'current'         => 'temperature_2m,weather_code,precipitation,wind_speed_10m',
                'timezone'        => 'America/Sao_Paulo',
            ),
            'https://api.open-meteo.com/v1/forecast'
        );

        $response = wp_remote_get($url, array('timeout' => 8));
        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return null;
        }

        $data    = json_decode(wp_remote_retrieve_body($response), true);
        $current = $data['current'] ?? null;
        if (! is_array($current) || ! isset($current['temperature_2m'])) {
            return null;
        }

        $code    = (int) ($current['weather_code'] ?? 0);
        $temp    = (float) $current['temperature_2m'];
        $rain_mm = (float) ($current['precipitation'] ?? 0);
        $wind    = (float) ($current['wind_speed_10m'] ?? 0);

        return array(
            'code'          => $code,
            'temp'          => $temp,
            'rain_mm'       => $rain_mm,
            'wind'          => $wind,
            'is_raining'    => $rain_mm > 0 || ($code >= 51 && $code <= 99),
            'pre_localized' => false,
        );
    }

    /**
     * Adverse weather alert line (empty string when conditions are safe).
     * Thresholds: temp ≥ 37°C, wind ≥ 40 km/h, rain ≥ 20 mm, thunderstorm (WMO ≥ 95).
     */
    private static function adverseAlert(float $temp, float $wind, float $rain_mm, int $code, string $lang = Lang::DEFAULT, ?int $chat_id = null): string
    {
        $alerts = array();

        if ($code >= 95) {
            $alerts[] = Lang::t('alert_storm', $lang, array(), $chat_id);
        }
        if ($rain_mm >= 20) {
            $alerts[] = Lang::t('alert_heavy_rain', $lang, array(), $chat_id);
        }
        if ($wind >= 40) {
            $alerts[] = Lang::t('alert_wind', $lang, array(), $chat_id);
        }
        if ($temp >= 37) {
            $alerts[] = Lang::t('alert_heat', $lang, array(), $chat_id);
        }

        return implode(' ', $alerts);
    }

    /**
     * WMO weather code → [emoji, condition text in $lang].
     *
     * @return array{0:string,1:string}
     */
    private static function wmoToCondition(int $code, string $lang = Lang::DEFAULT): array
    {
        return match (true) {
            0 === $code => array('☀️', Lang::t('cond_sunny', $lang)),
            $code <= 2  => array('🌤', Lang::t('cond_partly_cloudy', $lang)),
            $code <= 3  => array('☁️', Lang::t('cond_cloudy', $lang)),
            $code <= 48 => array('🌫', Lang::t('cond_fog', $lang)),
            $code <= 67 => array('🌧', Lang::t('cond_drizzle', $lang)),
            $code <= 82 => array('🌧', Lang::t('cond_showers', $lang)),
            default     => array('⛈', Lang::t('cond_storm', $lang)),
        };
    }

	/* ── vibe quiz state (2026-08-25) ── */

    /** Known vibe answer for this chat, or null if never answered. */
    private static function getKnownVibe(int $chat_id): ?string
    {
        $prefs = get_option(self::VIBE_OPTION, array());
        if (! is_array($prefs) || empty($prefs[$chat_id]['answer'])) {
            return null;
        }

        $answer = (string) $prefs[$chat_id]['answer'];

        return in_array($answer, self::VIBE_ANSWERS, true) ? $answer : null;
    }

    /** Persist the chosen vibe for this chat. Sticky — see class docblock. */
    private static function setKnownVibe(int $chat_id, string $answer): void
    {
        if (! in_array($answer, self::VIBE_ANSWERS, true)) {
            return;
        }

        $prefs = get_option(self::VIBE_OPTION, array());
        if (! is_array($prefs)) {
            $prefs = array();
        }

        $prefs[$chat_id] = array(
            'answer' => $answer,
            'set_at' => time(),
        );

        update_option(self::VIBE_OPTION, $prefs, false); // non-autoloaded — read only on demand.
    }

    /** Clear a stored vibe (user asked to change it). */
    private static function forgetVibe(int $chat_id): void
    {
        $prefs = get_option(self::VIBE_OPTION, array());
        if (is_array($prefs) && isset($prefs[$chat_id])) {
            unset($prefs[$chat_id]);
            update_option(self::VIBE_OPTION, $prefs, false);
        }
    }

    /** Stash the intent the quiz interrupted, so answering resumes it. */
    private static function stashPendingIntent(int $chat_id, array $intent): void
    {
        set_transient(self::PENDING_INTENT_PREFIX . $chat_id, $intent, self::PENDING_INTENT_TTL);
    }

    /** Pop (read + clear) the pending intent for this chat, if any. */
    private static function popPendingIntent(int $chat_id): ?array
    {
        $key    = self::PENDING_INTENT_PREFIX . $chat_id;
        $intent = get_transient($key);
        delete_transient($key);

        return is_array($intent) ? $intent : null;
    }

    /**
     * Send the a/b/c/d quiz as its own message with an inline keyboard.
     * Question text comes from apollo_event_vibe_quiz_definition() — one
     * source shared with the event-classification rule, so the words the
     * person reads always match the rule the bot actually applies.
     */
    private static function sendVibeQuiz(int $chat_id, string $lang = Lang::DEFAULT, ?string $intro = null): void
    {
        if (! function_exists('apollo_event_vibe_quiz_definition')) {
            return;
        }

        $definition = apollo_event_vibe_quiz_definition($lang);
        if (empty($definition)) {
            return;
        }

        $lines   = array();
        $lines[] = null !== $intro ? $intro : Lang::t('vibe_quiz_intro', $lang, array(), $chat_id);
        $lines[] = '<b>' . Lang::t('vibe_quiz_question', $lang, array(), $chat_id) . '</b>';
        $lines[] = '';

        $buttons = array();
        foreach ($definition as $letter => $rule) {
            $lines[]   = sprintf('<b>%s.</b> %s', strtoupper($letter), esc_html((string) $rule['question']));
            $buttons[] = array(
                'text'          => strtoupper($letter),
                'callback_data' => 'vibequiz:' . $letter,
            );
        }

        \Apollo\Telegram\Telegram\ExtendedClasses\Request::sendMessage(array(
            'chat_id'      => $chat_id,
            'text'         => implode("\n", $lines),
            'parse_mode'   => 'HTML',
            'reply_markup' => wp_json_encode(array('inline_keyboard' => array($buttons))),
        ));
    }

	/* ── helpers ── */

    /**
     * Build the intent array with the resolved date range.
     *
     * @return array{type:string,label:string,period:string,start:string,end:string,neighborhood:?string}
     */
    private static function intent(string $type, string $label, string $period, ?string $neighborhood): array
    {
        list($start, $end) = self::resolveRange($period);

        return array(
            'type'         => $type,
            'label'        => $neighborhood ? $label . ' (' . $neighborhood . ')' : $label,
            'period'       => $period,
            'start'        => $start,
            'end'          => $end,
            'neighborhood' => $neighborhood,
        );
    }

    /**
     * Period keyword → [start Y-m-d, end Y-m-d] in the site timezone.
     *
     * @return array{0:string,1:string}
     */
    private static function resolveRange(string $period): array
    {
        $tz  = wp_timezone();
        $now = new \DateTimeImmutable('now', $tz);

        switch ($period) {
            case 'today':
                return array($now->format('Y-m-d'), $now->format('Y-m-d'));

            case 'tomorrow':
                $d = $now->modify('+1 day');
                return array($d->format('Y-m-d'), $d->format('Y-m-d'));

            case 'weekend':
                // Fri counts as weekend kickoff; Sat/Sun = rest of it.
                $dow = (int) $now->format('N'); // 1=Mon … 7=Sun
                $sat = ($dow >= 6) ? $now->modify('saturday this week') : $now->modify('next saturday');
                if (7 === $dow) {
                    return array($now->format('Y-m-d'), $now->format('Y-m-d'));
                }
                $start = (5 === $dow || 6 === $dow) ? $now : $sat;
                $sun   = $start->modify('sunday this week');
                if ($sun < $start) {
                    $sun = $start->modify('next sunday');
                }
                return array($start->format('Y-m-d'), $sun->format('Y-m-d'));

            case 'next_weekend':
                $sat = $now->modify('saturday this week')->modify('+7 days');
                return array($sat->format('Y-m-d'), $sat->modify('+1 day')->format('Y-m-d'));

            case 'week':
                $sun = $now->modify('sunday this week');
                if ($sun < $now) {
                    $sun = $now;
                }
                return array($now->format('Y-m-d'), $sun->format('Y-m-d'));

            case 'next_week':
                $mon = $now->modify('next monday');
                return array($mon->format('Y-m-d'), $mon->modify('+6 days')->format('Y-m-d'));

            case 'month':
                return array($now->format('Y-m-d'), $now->modify('+30 days')->format('Y-m-d'));

            case 'next14':
                return array($now->format('Y-m-d'), $now->modify('+14 days')->format('Y-m-d'));

            case 'next7':
            default:
                return array($now->format('Y-m-d'), $now->modify('+7 days')->format('Y-m-d'));
        }
    }

    /** Fri/Sat/Sun → "qual a boa" defaults to this weekend. */
    private static function isWeekendish(): bool
    {
        return (int) (new \DateTimeImmutable('now', wp_timezone()))->format('N') >= 5;
    }

    private static function detectNeighborhood(string $normalized): ?string
    {
        foreach (self::NEIGHBORHOODS as $keyword => $label) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/', $normalized)) {
                return $label;
            }
        }

        return null;
    }

    /** Lowercase, accent-stripped, squashed whitespace (mbstring-free). */
    private static function normalize(string $text): string
    {
        $text = strtolower(remove_accents(trim($text)));

        return (string) preg_replace('/\s+/', ' ', $text);
    }

    /** "2026-07-11" → "Sáb, 11/07" (pt) or "Sat, 07/11" (en). */
    private static function humanDate(string $ymd, string $lang = Lang::DEFAULT): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $ymd, wp_timezone());
        if (false === $date) {
            return $ymd;
        }

        $days   = Lang::days($lang);
        $format = 'en' === $lang ? 'm/d' : 'd/m';

        return $days[(int) $date->format('N')] . ', ' . $date->format($format);
    }
}
