<?php

/**
 * Lang — per-chat bilingual (pt/en) support for the Telegram bot (2026-08-25,
 * voice pass 2026-08-25b).
 *
 * OWNS: which language a chat gets replied in, and the single string table
 * every bot-facing message pulls from. This is deliberately NOT WordPress's
 * `__()`/site-locale mechanism — `__()` translates against the site's ONE
 * global locale, but this bot serves many simultaneous chats that each need
 * their own language, independent of whatever locale WP itself is running.
 * Runtime-switching WP's locale per Telegram update (`switch_to_locale()`
 * around every string call) would work but is heavy and easy to get wrong
 * (forgetting to `restore_current_locale()` on an early return leaks the
 * switch into unrelated requests). A flat lookup table keyed by our own
 * language code is simpler, faster, and cannot leak.
 *
 * Resolution order, cheapest/most-specific first:
 *   1. Sticky per-chat choice, set explicitly ("fala em inglês" / "speak
 *      english") or implicitly on first contact — see forChat().
 *   2. Telegram's own `User.language_code` on first contact (passed in by
 *      the caller; this class never touches the Telegram entities directly,
 *      keeping it dependency-free and easy to unit-test in isolation).
 *   3. Portuguese (self::DEFAULT) — apollo.rio.br is a Brazil-first platform.
 *
 * VOICE (2026-08-25b): the bot talks like a close friend texting — warm,
 * a little excited, never a form letter. Every conversational line in
 * table() below carries 5 hand-written variants per language instead of one
 * fixed string, so two chats (or the same chat twice) don't get the exact
 * same sentence back to back. t() picks one at random and, when a $chat_id
 * is given, remembers the last variant shown for that (chat, key) pair for
 * an hour so it never repeats itself immediately — a person who says the
 * same thing to you twice in a row every time gets annoying fast, and a bot
 * is no different. Grammar fragments (weekday labels, weather conditions,
 * date-range labels spliced mid-sentence by other strings) stay single
 * strings on purpose: they're ingredients, not lines of dialogue, and
 * rotating them independently of whatever template picked them would risk
 * clashing genders/tenses.
 *
 * The vibe-quiz question/label text is NOT duplicated here — it lives in
 * apollo_event_vibe_quiz_definition($lang) in apollo-events (the CPT/rule
 * owner), which now accepts the same 'pt'|'en' code this class produces.
 * Everything else the bot ever says to a person lives in table() below.
 *
 * @package Apollo\Telegram\Services
 */

declare(strict_types=1);

namespace Apollo\Telegram\Services;

if (! defined('ABSPATH')) {
    exit;
}

final class Lang
{
    public const DEFAULT   = 'pt';
    public const SUPPORTED = array('pt', 'en');

    private const OPTION = 'apollo_tg_lang_prefs';

    /** Anti-repeat memory: last variant index shown per (chat_id, key), 1h TTL. */
    private const VARIANT_MEMORY_PREFIX = 'apollo_tg_lv_';
    private const VARIANT_MEMORY_TTL    = HOUR_IN_SECONDS;

    /**
     * Resolve the language to reply in for this chat.
     *
     * @param int         $chat_id        Telegram chat id.
     * @param string|null $telegram_code  Telegram User.language_code from
     *                                    the incoming update, if available
     *                                    (e.g. "en", "pt-br", "es"). Only
     *                                    consulted when nothing is stored
     *                                    yet — a returning chat always keeps
     *                                    its own explicit/previous choice.
     */
    public static function forChat(int $chat_id, ?string $telegram_code = null): string
    {
        $stored = self::getStored($chat_id);
        if (null !== $stored) {
            return $stored;
        }

        if (null !== $telegram_code && '' !== $telegram_code) {
            $code = strtolower(substr($telegram_code, 0, 2));
            if ('pt' === $code) {
                return self::DEFAULT;
            }
            if (in_array($code, self::SUPPORTED, true)) {
                return $code;
            }
            // Any other client language (es, fr, de, …) — we only speak
            // pt/en, and it's explicitly not pt, so English is the closer
            // guess for a first contact than assuming Portuguese.
            return 'en';
        }

        return self::DEFAULT;
    }

    /**
     * The normal entry point for message handlers: resolve() + persist in
     * one call. First contact for a chat writes its derived language
     * immediately, so it's stable from then on without needing the Telegram
     * client code again on every message. forChat() itself stays
     * side-effect-free (a plain resolve, useful anywhere a write isn't
     * wanted); this wraps it with the "make it sticky right away" behavior
     * every real caller in this plugin actually wants.
     */
    public static function resolveForChat(int $chat_id, ?string $telegram_code = null): string
    {
        $stored = self::getStored($chat_id);
        if (null !== $stored) {
            return $stored;
        }

        $lang = self::forChat($chat_id, $telegram_code);
        self::setForChat($chat_id, $lang);

        return $lang;
    }

    private static function getStored(int $chat_id): ?string
    {
        $prefs = get_option(self::OPTION, array());
        if (! is_array($prefs) || empty($prefs[$chat_id])) {
            return null;
        }

        $lang = (string) $prefs[$chat_id];

        return in_array($lang, self::SUPPORTED, true) ? $lang : null;
    }

    /** Persist an explicit (or first-contact-derived) language choice. Sticky, no expiry. */
    public static function setForChat(int $chat_id, string $lang): void
    {
        if (! in_array($lang, self::SUPPORTED, true)) {
            return;
        }

        $prefs = get_option(self::OPTION, array());
        if (! is_array($prefs)) {
            $prefs = array();
        }

        $prefs[$chat_id] = $lang;

        update_option(self::OPTION, $prefs, false); // non-autoloaded — one row for every chat, read on demand.
    }

    /**
     * Free-text "speak english" / "fala português" detection.
     *
     * @param string $normalized Already lowercased/accent-stripped text —
     *                            same normalization EventsBotService applies
     *                            before its own intent regexes, so callers
     *                            should reuse that, not re-normalize here.
     * @return string|null 'en'|'pt' if a switch was requested, else null.
     */
    public static function detectSwitchIntent(string $normalized): ?string
    {
        if (preg_match('/\b(fala|falar|responde|responder|manda) (em |pra mim em )?ingles\b|\bspeak (to me )?(in )?english\b|\bin english( please)?\b|\bswitch to english\b|\benglish please\b/', $normalized)) {
            return 'en';
        }
        if (preg_match('/\b(fala|falar|responde|responder|manda) (em |pra mim em )?portugues\b|\bspeak (in )?portuguese\b|\bswitch to portuguese\b|\bportuguese please\b/', $normalized)) {
            return 'pt';
        }

        return null;
    }

    /** Weekday abbreviations, Monday-first, matching DateTime's 'N' (1-7). */
    public static function days(string $lang): array
    {
        return 'en' === $lang
            ? array(1 => 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun')
            : array(1 => 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom');
    }

    /**
     * Look up one string, sprintf-substituted if $vars is given.
     *
     * When the table entry for $key/$lang is an array of variants, one is
     * picked (see pick()) instead of returning a single fixed string —
     * that's the "friend texting" behavior: the bot never parrots the exact
     * same sentence twice to the same chat in a row. Pass $chat_id whenever
     * it's in scope so the anti-repeat memory actually works; without it,
     * picking degrades gracefully to plain random (still varied, just no
     * per-chat memory of what was said last).
     *
     * @param array<int,string> $vars Positional %s replacements.
     */
    public static function t(string $key, string $lang, array $vars = array(), ?int $chat_id = null): string
    {
        $lang  = in_array($lang, self::SUPPORTED, true) ? $lang : self::DEFAULT;
        $table = self::table();
        $entry = $table[$key][$lang] ?? $table[$key][self::DEFAULT] ?? $key;

        $str = is_array($entry) ? self::pick($key, $entry, $chat_id) : $entry;

        return empty($vars) ? $str : vsprintf($str, $vars);
    }

    /**
     * Pick one variant, avoiding whatever was shown last for this exact
     * (chat_id, key) pair. The memory is a single short-lived transient per
     * pair — self-expiring (1h), never grows unbounded, and simply isn't
     * consulted at all when $chat_id is unknown (still random, just no
     * memory). This is intentionally lightweight: the goal is "don't say
     * the identical sentence back to back," not a full non-repeating
     * shuffle-bag across the whole conversation history.
     *
     * @param array<int,string> $variants
     */
    private static function pick(string $key, array $variants, ?int $chat_id): string
    {
        $count = count($variants);
        if ($count <= 1) {
            return $variants[0] ?? '';
        }

        if (null === $chat_id) {
            return $variants[array_rand($variants)];
        }

        $mem_key = self::VARIANT_MEMORY_PREFIX . $chat_id . '_' . substr(md5($key), 0, 12);
        $last    = get_transient($mem_key);
        $last    = (false !== $last) ? (int) $last : null;

        $idx = array_rand($variants);
        if (null !== $last) {
            $tries = 0;
            while ($idx === $last && $tries < 6) {
                $idx = array_rand($variants);
                $tries++;
            }
        }

        set_transient($mem_key, $idx, self::VARIANT_MEMORY_TTL);

        return $variants[$idx];
    }

    /**
     * Single source of truth for every bot-facing string outside the vibe
     * quiz (see class docblock for where that one lives).
     *
     * Conversational keys carry 5 variants per language ('pt'/'en' each map
     * to an array of strings) — the voice is a close friend texting: warm,
     * a little excited, casual contractions, emoji used like punctuation
     * not decoration. Grammar-fragment keys (label_*, cond_*) stay single
     * strings — see class docblock for why.
     *
     * @return array<string, array{pt:string|array<int,string>, en:string|array<int,string>}>
     */
    private static function table(): array
    {
        static $table = null;
        if (null !== $table) {
            return $table;
        }

        $table = array(
            'rate_limited_events'  => array(
                'pt' => array(
                    'Opa, calma aí! 😅 Muita pergunta de uma vez, me dá só uns segundos pra respirar.',
                    'Peraí, peraí! 🙈 Vai com calma que eu ainda tô processando as últimas perguntas.',
                    'Ei, devagar campeão! 😄 Espera um minutinho e manda de novo, prometo que valho a pena.',
                    'Calma, mó ansiedade boa! 😅 Dá um tempinho pra mim e já te respondo.',
                    'Segura a onda! 🌊 Muitas mensagens seguidas — tenta de novo daqui a pouco.',
                ),
                'en' => array(
                    'Whoa, slow down! 😅 That\'s a lot of questions at once — give me a sec to catch my breath.',
                    'Hang tight! 🙈 I\'m still catching up on your last few messages.',
                    'Easy there, speedster! 😄 Wait a minute and try again, I promise I\'m worth it.',
                    'Okay okay, I hear you! 😅 Give me a little breather and ask again.',
                    'One sec! 🌊 Too many messages back to back — try again in a bit.',
                ),
            ),
            'rate_limited_generic' => array(
                'pt' => array(
                    'Só um instantinho, já te atendo! 😊',
                    'Peraí, respira comigo, já já continuamos. 🙏',
                    'Dá um segundinho pra mim, tá quase lá!',
                    'Calma, tô aqui, só preciso de um minuto.',
                    'Segura essa, volto rapidinho! ⏳',
                ),
                'en' => array(
                    'Just a sec, I\'ve got you! 😊',
                    'Hang on with me, we\'ll continue in a moment. 🙏',
                    'Give me a second, almost there!',
                    'I\'m here, just need a minute.',
                    'Hold that thought, be right back! ⏳',
                ),
            ),
            'weather_unavailable'  => array(
                'pt' => array(
                    'Ih, não consegui espiar o tempo agora. 🌦 Tenta de novo daqui a pouquinho?',
                    'Deu ruim pra checar a previsão aqui. 🌧 Manda de novo em instantes.',
                    'O céu tá emburrado comigo e não me deixou ver a previsão. 😅 Tenta outra vez!',
                    'Não rolou de puxar o tempo agora. ☁️ Tenta novamente daqui a pouco, por favor.',
                    'Minha conexão com o céu falhou por um segundo. 🌦 Chama de novo já já!',
                ),
                'en' => array(
                    'Ugh, couldn\'t peek at the weather right now. 🌦 Try again in a bit?',
                    'Weather check failed on my end. 🌧 Give it another shot in a moment.',
                    'The sky\'s giving me the silent treatment. 😅 Try again in a sec!',
                    'Couldn\'t pull the forecast this time. ☁️ Try again shortly, please.',
                    'My connection to the clouds glitched for a sec. 🌦 Ping me again soon!',
                ),
            ),

            'vibe_reset_intro'     => array(
                'pt' => array(
                    'Bora recalibrar essa vibe! 🎛',
                    'Show, vamos redescobrir seu estilo! ✨',
                    'Perfeito, hora de mudar o rolê! 🔄',
                    'Beleza, bora ver o que combina com você agora! 🎧',
                    'Adorei, bora refazer o quiz e achar sua nova vibe! 🙌',
                ),
                'en' => array(
                    'Let\'s recalibrate your vibe! 🎛',
                    'Nice, let\'s rediscover your style! ✨',
                    'Perfect, time to switch things up! 🔄',
                    'Cool, let\'s find out what fits you now! 🎧',
                    'Love it, let\'s redo the quiz and find your new vibe! 🙌',
                ),
            ),
            'vibe_unknown_answer'  => array(
                'pt' => array(
                    'Hmm, não peguei essa. 😅 Manda só a letra: a, b, c ou d.',
                    'Não captei! Responde com a, b, c ou d, combinado?',
                    'Ops, não entendi. 🙈 Só a letra mesmo: a, b, c ou d.',
                    'Quase! Mas preciso só da letrinha: a, b, c ou d. 😄',
                    'Não rolou entender essa resposta. Tenta de novo com a, b, c ou d!',
                ),
                'en' => array(
                    'Hmm, didn\'t catch that. 😅 Just send the letter: a, b, c or d.',
                    'Didn\'t quite get it! Reply with a, b, c or d, okay?',
                    'Oops, didn\'t understand that. 🙈 Just the letter: a, b, c or d.',
                    'So close! Just need the letter: a, b, c or d. 😄',
                    'Couldn\'t make sense of that one. Try again with a, b, c or d!',
                ),
            ),
            'vibe_ack'             => array(
                'pt' => array(
                    'Anotado! Sua vibe agora é <b>%s</b>. 🎯 Já sei o que combina com você.',
                    'Perfeito, guardei aqui: <b>%s</b> é a sua praia. 🙌 Bora achar o rolê certo.',
                    'Fechado! <b>%s</b> registrado. 🎧 Agora é só perguntar "hoje" ou "esse fds".',
                    'Adorei saber! Você é <b>%s</b> e eu vou seguir esse estilo daqui pra frente. ✨',
                    'Combinado, <b>%s</b> é você! 🎉 Já pode perguntar o que quiser que eu sigo essa linha.',
                ),
                'en' => array(
                    'Got it! Your vibe is now <b>%s</b>. 🎯 I know exactly what fits you.',
                    'Perfect, saved it: <b>%s</b> is your thing. 🙌 Let\'s find the right party.',
                    'Locked in! <b>%s</b> noted. 🎧 Now just ask me "today" or "this weekend".',
                    'Love knowing this! You\'re <b>%s</b> and I\'ll stick to that from now on. ✨',
                    'Deal, <b>%s</b> it is! 🎉 Ask me anything and I\'ll keep that style in mind.',
                ),
            ),
            'vibe_quiz_intro'      => array(
                'pt' => array(
                    'Antes de indicar, deixa eu te conhecer melhor! 😊',
                    'Pra acertar em cheio, preciso saber seu estilo primeiro. ✨',
                    'Calma que antes de sugerir, quero entender sua vibe! 🎧',
                    'Deixa eu te fazer uma pergunta rapidinha antes de indicar algo. 😉',
                    'Quero acertar de primeira — me conta um pouco sobre você! 🙌',
                ),
                'en' => array(
                    'Before I recommend anything, let me get to know you! 😊',
                    'To nail this, I need to know your style first. ✨',
                    'Hold on, before suggesting, I wanna understand your vibe! 🎧',
                    'Let me ask you something quick before I recommend anything. 😉',
                    'I wanna get this right — tell me a bit about yourself! 🙌',
                ),
            ),
            'vibe_quiz_question'   => array(
                'pt' => array(
                    'Uma festa boa pra ti é aquela tipo qual?',
                    'Me conta, qual estilo de festa te faz feliz?',
                    'Qual dessas combina mais com você?',
                    'Se eu te chamasse pra sair, qual rolê você toparia na hora?',
                    'Qual dessas festas tá mais a sua cara?',
                ),
                'en' => array(
                    'What kind of party is a good one for you?',
                    'Tell me, which party style makes you happy?',
                    'Which of these fits you best?',
                    'If I invited you out, which of these would you say yes to instantly?',
                    'Which of these parties feels the most "you"?',
                ),
            ),

            'no_events'            => array(
                'pt' => array(
                    '😕 Não achei nada pra %s ainda.',
                    'Hmm, %s tá quietinho por enquanto. 😕',
                    'Ainda não rolou nada marcado pra %s. 🤔',
                    'Vasculhei tudo e não achei rolê pra %s. 😕',
                    'Pra %s ainda não tenho nada na manga. 🙁',
                ),
                'en' => array(
                    '😕 Couldn\'t find anything for %s yet.',
                    'Hmm, %s is looking quiet right now. 😕',
                    'Nothing\'s booked for %s just yet. 🤔',
                    'I dug around and found nothing for %s. 😕',
                    'Don\'t have anything up my sleeve for %s yet. 🙁',
                ),
            ),
            'no_events_cta'        => array(
                'pt' => array(
                    'Mas cola em https://apollo.rio.br pra ver a agenda completa — sempre entra coisa nova!',
                    'Dá uma olhada em https://apollo.rio.br, a agenda tá sempre bombando por lá!',
                    'Passa em https://apollo.rio.br que sempre tem novidade rolando!',
                    'Bora espiar https://apollo.rio.br? A agenda tá cheia de coisa boa!',
                    'Vale conferir https://apollo.rio.br — entra evento novo toda hora!',
                ),
                'en' => array(
                    'But check https://apollo.rio.br for the full agenda — new stuff drops all the time!',
                    'Take a peek at https://apollo.rio.br, the agenda\'s always popping!',
                    'Swing by https://apollo.rio.br, there\'s always something new!',
                    'Wanna check https://apollo.rio.br? The lineup\'s full of good stuff!',
                    'Worth checking https://apollo.rio.br — new events land constantly!',
                ),
            ),
            'events_header'        => array(
                'pt' => array(
                    '🎉 Olha só o que separei pra <b>%s</b>:',
                    '🎉 Achei umas coisas boas pra <b>%s</b>, olha aí:',
                    '🎉 Bora ver a boa pra <b>%s</b>:',
                    '🎉 Garimpei isso aqui pra <b>%s</b>:',
                    '🎉 Aqui vai o que tá rolando pra <b>%s</b>:',
                ),
                'en' => array(
                    '🎉 Check out what I found for <b>%s</b>:',
                    '🎉 Here\'s the good stuff for <b>%s</b>:',
                    '🎉 Let\'s see what\'s up for <b>%s</b>:',
                    '🎉 I dug this up for <b>%s</b>:',
                    '🎉 Here\'s what\'s happening for <b>%s</b>:',
                ),
            ),
            'filter_hint'          => array(
                'pt' => array(
                    'Quer filtrar por bairro? Manda tipo "só Botafogo" ou "Lapa esse fds". 😉',
                    'Se quiser, dá pra afunilar por bairro — tipo "só Lapa". 😉',
                    'Dica: manda o nome do bairro que eu filtro pra você. 😉',
                    'Quer ver só um cantinho da cidade? Fala o bairro que eu filtro! 😉',
                    'Se preferir algo mais específico, me diz o bairro. 😉',
                ),
                'en' => array(
                    'Want to filter by neighborhood? Try "just Botafogo" or "Lapa this weekend". 😉',
                    'If you want, I can narrow it down by neighborhood — like "just Lapa". 😉',
                    'Tip: send me a neighborhood and I\'ll filter for you. 😉',
                    'Want just one part of the city? Tell me the neighborhood! 😉',
                    'If you want something more specific, tell me the neighborhood. 😉',
                ),
            ),

            'weather_greeting_rain' => array(
                'pt' => array(
                    'Melhor priorizar rolê coberto hoje. ☔',
                    'Hoje pede um lugar fechadinho, viu? ☔',
                    'Guarda-chuva na mochila e foco em rolê indoor hoje. ☔',
                    'Tempo pedindo teto hoje — bora de rolê coberto. ☔',
                    'Dia molhado, então prioriza os lugares cobertos! ☔',
                ),
                'en' => array(
                    'Better stick to an indoor spot today. ☔',
                    'Today\'s calling for something under a roof. ☔',
                    'Grab an umbrella and lean indoor today. ☔',
                    'Rainy vibes today — indoor spots are the move. ☔',
                    'Wet day, so prioritize covered venues! ☔',
                ),
            ),
            'weather_greeting_good' => array(
                'pt' => array(
                    'Clima bom no Rio — perfeito pra rolê!',
                    'Tempo lindo hoje, dá pra aproveitar tudo!',
                    'Céu limpo e clima ótimo — bora sair!',
                    'Dia perfeito pra curtir a cidade!',
                    'Tempo colaborando 100% pro rolê hoje!',
                ),
                'en' => array(
                    'Nice weather in Rio — perfect for going out!',
                    'Beautiful weather today, you can enjoy everything!',
                    'Clear skies and great weather — let\'s go out!',
                    'Perfect day to enjoy the city!',
                    'Weather\'s fully on your side today!',
                ),
            ),
            'alert_storm'           => array(
                'pt' => array(
                    'Tempestade com raios na área — evite áreas abertas e orla.',
                    'Cuidado, tem tempestade rondando — fica longe de lugar aberto e da orla.',
                    'Raios na área, hein — melhor evitar espaços abertos agora.',
                    'Tempestade chegando — mantém distância de orla e áreas abertas.',
                    'Alerta de temporal — evite ficar exposto em áreas abertas.',
                ),
                'en' => array(
                    'Thunderstorm in the area — avoid open spaces and the waterfront.',
                    'Heads up, storm nearby — stay away from open areas and the waterfront.',
                    'Lightning around — better avoid open spaces right now.',
                    'Storm\'s rolling in — keep distance from the waterfront and open areas.',
                    'Storm alert — avoid exposed open areas.',
                ),
            ),
            'alert_heavy_rain'      => array(
                'pt' => array(
                    'Chuva forte agora — risco de alagamentos, redobre a atenção no deslocamento.',
                    'Tá caindo um temporal — cuidado com alagamento no caminho.',
                    'Chuva pesada rolando — vai com calma pra evitar áreas alagadas.',
                    'Aguaceiro forte agora, atenção redobrada no trajeto.',
                    'Muita chuva caindo — fica esperto com pontos de alagamento.',
                ),
                'en' => array(
                    'Heavy rain right now — flooding risk, take extra care getting around.',
                    'Pouring rain out there — watch for flooding on your way.',
                    'Heavy downpour happening — be careful with flooded spots.',
                    'Strong rain right now — extra caution getting around.',
                    'It\'s really coming down — stay alert for flooding.',
                ),
            ),
            'alert_wind'            => array(
                'pt' => array(
                    'Ventania forte — cuidado com estruturas ao ar livre.',
                    'Vento bem forte agora, atenção com coisas soltas por aí.',
                    'Ventos fortes rolando — cuidado ao passar perto de estruturas abertas.',
                    'Tá ventando forte, redobra o cuidado em áreas abertas.',
                    'Vento forte no ar — fica de olho em estruturas ao ar livre.',
                ),
                'en' => array(
                    'Strong winds — watch out for outdoor structures.',
                    'Really windy right now, watch for loose stuff around.',
                    'Strong gusts happening — be careful near open structures.',
                    'It\'s blowing hard, extra care in open areas.',
                    'Windy out there — keep an eye on outdoor structures.',
                ),
            ),
            'alert_heat'            => array(
                'pt' => array(
                    'Calor extremo — hidrate-se bem e procure sombra.',
                    'Tá quente demais lá fora — bebe água e busca sombra.',
                    'Calorão daqueles — se hidrata e evita sol direto.',
                    'Temperatura nas alturas — água sempre por perto e sombra sempre que der.',
                    'Calor forte no ar — cuida da hidratação, viu?',
                ),
                'en' => array(
                    'Extreme heat — stay hydrated and seek shade.',
                    'It\'s really hot out there — drink water and find shade.',
                    'Serious heat right now — hydrate and avoid direct sun.',
                    'Temps are high — keep water close and shade when you can.',
                    'Strong heat out there — stay on top of hydration!',
                ),
            ),
            'cond_sunny'            => array('pt' => 'ensolarado', 'en' => 'sunny'),
            'cond_partly_cloudy'    => array('pt' => 'parcialmente nublado', 'en' => 'partly cloudy'),
            'cond_cloudy'           => array('pt' => 'nublado', 'en' => 'cloudy'),
            'cond_fog'              => array('pt' => 'neblina', 'en' => 'fog'),
            'cond_drizzle'          => array('pt' => 'chuvisco/chuva', 'en' => 'drizzle/rain'),
            'cond_showers'          => array('pt' => 'pancadas de chuva', 'en' => 'rain showers'),
            'cond_storm'            => array('pt' => 'tempestade', 'en' => 'storm'),

            'label_weather'         => array('pt' => 'previsão do tempo', 'en' => 'weather forecast'),
            'label_vibe'            => array('pt' => 'sua vibe', 'en' => 'your vibe'),
            'label_random'          => array('pt' => 'surpresa', 'en' => 'a surprise'),
            'label_boa_cidade'      => array('pt' => 'a boa da cidade', 'en' => "what's good in the city"),
            'label_next_weekend'    => array('pt' => 'o próximo fim de semana', 'en' => 'next weekend'),
            'label_this_weekend'    => array('pt' => 'esse fim de semana', 'en' => 'this weekend'),
            'label_upcoming'        => array('pt' => 'os próximos dias', 'en' => 'the coming days'),
            'label_tonight'         => array('pt' => 'hoje à noite', 'en' => 'tonight'),
            'label_today'           => array('pt' => 'hoje', 'en' => 'today'),
            'label_tomorrow'        => array('pt' => 'amanhã', 'en' => 'tomorrow'),
            'label_next_week'       => array('pt' => 'a próxima semana', 'en' => 'next week'),
            'label_this_week'       => array('pt' => 'essa semana', 'en' => 'this week'),
            'label_this_month'      => array('pt' => 'esse mês', 'en' => 'this month'),

            'lang_switched_en'      => array(
                'pt' => array(
                    'Combinado, falo inglês daqui pra frente! 🇬🇧',
                    'Beleza, agora sigo em inglês! 🇬🇧',
                    'Fechado, inglês a partir de agora! 🇬🇧',
                    'Show, troquei pro inglês! 🇬🇧',
                    'Certo, de agora em diante é inglês! 🇬🇧',
                ),
                'en' => array(
                    'Done — I\'ll speak English from now on! 🇬🇧',
                    'Got it, switching to English! 🇬🇧',
                    'All set, English it is from here! 🇬🇧',
                    'Cool, I\'ve switched to English! 🇬🇧',
                    'Sure thing, English from now on! 🇬🇧',
                ),
            ),
            'lang_switched_pt'      => array(
                'pt' => array(
                    'Combinado, falo português daqui pra frente! 🇧🇷',
                    'Beleza, sigo em português agora! 🇧🇷',
                    'Fechado, português a partir de agora! 🇧🇷',
                    'Show, voltei pro português! 🇧🇷',
                    'Certo, de agora em diante é português! 🇧🇷',
                ),
                'en' => array(
                    'Done — I\'ll speak Portuguese from now on! 🇧🇷',
                    'Got it, switching to Portuguese! 🇧🇷',
                    'All set, Portuguese it is from here! 🇧🇷',
                    'Cool, I\'ve switched to Portuguese! 🇧🇷',
                    'Sure thing, Portuguese from now on! 🇧🇷',
                ),
            ),

            'start_welcome'         => array(
                'pt' => array(
                    '👋 <b>E aí! Bem-vindo ao Apollo Rio.</b>' . "\n\n" . 'Pra verificar seu telefone, começa pelo site (apollo.rio.br) — eu mando o código automaticamente assim que você abrir o link.',
                    '👋 <b>Oi, que bom te ver por aqui!</b>' . "\n\n" . 'Pra confirmar seu número, entra pelo site (apollo.rio.br) — o código chega sozinho assim que o link abre.',
                    '👋 <b>Fala, seja bem-vindo ao Apollo Rio!</b>' . "\n\n" . 'Pra validar seu telefone, é só ir pelo site (apollo.rio.br) — eu solto o código automaticamente.',
                    '👋 <b>Oi! Chegou no lugar certo.</b>' . "\n\n" . 'Pra verificar seu número, começa pelo apollo.rio.br — o código cai aqui sozinho quando você abre o link.',
                    '👋 <b>E aí, bem-vindo(a)!</b>' . "\n\n" . 'Pra confirmar seu telefone, entra no site (apollo.rio.br) — assim que abrir o link, já te mando o código.',
                ),
                'en' => array(
                    '👋 <b>Hey! Welcome to Apollo Rio.</b>' . "\n\n" . 'To verify your phone, start from the site (apollo.rio.br) — the bot sends the code automatically when you open the link.',
                    '👋 <b>Hi, great to have you here!</b>' . "\n\n" . 'To confirm your number, head to the site (apollo.rio.br) — the code shows up here automatically once you open the link.',
                    '👋 <b>Hey there, welcome to Apollo Rio!</b>' . "\n\n" . 'To verify your phone, just go through the site (apollo.rio.br) — I\'ll send the code automatically.',
                    '👋 <b>Hi! You\'re in the right place.</b>' . "\n\n" . 'To verify your number, start at apollo.rio.br — the code lands here automatically when you open the link.',
                    '👋 <b>Hey, welcome aboard!</b>' . "\n\n" . 'To confirm your phone, go to the site (apollo.rio.br) — as soon as you open the link, I\'ll send you the code.',
                ),
            ),
            'start_share_contact'   => array(
                'pt' => '📱 Compartilhar meu número de telefone',
                'en' => '📱 Share my phone number',
            ),
            'start_code_header'     => array(
                'pt' => array(
                    '✅ <b>Código de verificação Apollo</b>' . "\n\n" . 'Seu código de 6 dígitos é:',
                    '✅ <b>Prontinho! Aqui está seu código Apollo</b>' . "\n\n" . 'Seu código de 6 dígitos:',
                    '✅ <b>Chegou seu código de verificação!</b>' . "\n\n" . 'Aqui está, 6 dígitos:',
                    '✅ <b>Código Apollo na mão!</b>' . "\n\n" . 'Seu código de 6 dígitos é:',
                    '✅ <b>Aqui vai seu código de verificação</b>' . "\n\n" . '6 dígitos, olha só:',
                ),
                'en' => array(
                    '✅ <b>Apollo verification code</b>' . "\n\n" . 'Your 6-digit code is:',
                    '✅ <b>Here it is! Your Apollo code</b>' . "\n\n" . 'Your 6-digit code:',
                    '✅ <b>Your verification code has arrived!</b>' . "\n\n" . 'Here it is, 6 digits:',
                    '✅ <b>Apollo code in hand!</b>' . "\n\n" . 'Your 6-digit code is:',
                    '✅ <b>Here\'s your verification code</b>' . "\n\n" . '6 digits, take a look:',
                ),
            ),
            'start_code_footer'     => array(
                'pt' => array(
                    'Volte para a página e cole este código para finalizar.',
                    'Agora é só voltar pra página e colar o código, prontinho!',
                    'Cola esse código lá na página pra fechar o cadastro.',
                    'Bora finalizar? Volta pro site e cola esse código.',
                    'Falta pouco! Volta na página e cola esse código pra terminar.',
                ),
                'en' => array(
                    'Go back to the page and paste this code to finish.',
                    'Now just head back to the page and paste the code, all done!',
                    'Paste this code on the page to wrap up your signup.',
                    'Ready to finish? Go back to the site and paste this code.',
                    'Almost there! Go back to the page and paste this code to finish.',
                ),
            ),
            'start_invalid_request' => array(
                'pt' => array(
                    'Solicitação inválida. Volte ao site e peça um novo código.',
                    'Hmm, esse pedido não é válido. Volta lá no site e pede um código novo.',
                    'Essa solicitação não rolou. Tenta de novo pelo site, pedindo um código novo.',
                    'Algo não bateu aqui. Volta pro site e gera um código novo, por favor.',
                    'Não consegui validar esse pedido. Peça um novo código pelo site.',
                ),
                'en' => array(
                    'Invalid request. Go back to the site and ask for a new code.',
                    'Hmm, that request isn\'t valid. Head back to the site and request a new code.',
                    'That request didn\'t go through. Try again on the site with a fresh code.',
                    'Something didn\'t match here. Go back to the site and get a new code, please.',
                    'Couldn\'t validate that request. Ask for a new code on the site.',
                ),
            ),

            'generic_limit_reached' => array(
                'pt' => array(
                    'Limite de tentativas atingido. Aguarde alguns minutos.',
                    'Opa, você já tentou bastante! Espera uns minutinhos e tenta de novo.',
                    'Calma, chegamos no limite por agora. Volta em alguns minutos.',
                    'Muitas tentativas seguidas — dá uma pausa de uns minutos.',
                    'Bateu o limite por hoje aqui. Tenta de novo daqui a pouco.',
                ),
                'en' => array(
                    'Attempt limit reached. Please wait a few minutes.',
                    'Whoa, that\'s a lot of tries! Wait a few minutes and try again.',
                    'We hit the limit for now. Come back in a few minutes.',
                    'Too many attempts in a row — take a short break.',
                    'Hit the limit for now. Try again in a bit.',
                ),
            ),
            'generic_invalid_phone' => array(
                'pt' => array(
                    'Número de contato inválido.',
                    'Esse número não parece válido, dá uma conferida?',
                    'Hmm, não consegui reconhecer esse número.',
                    'Esse contato não bateu certinho aqui.',
                    'Número inválido — tenta compartilhar de novo?',
                ),
                'en' => array(
                    'Invalid contact number.',
                    'That number doesn\'t look valid, mind checking it?',
                    'Hmm, couldn\'t recognize that number.',
                    'That contact didn\'t quite match up here.',
                    'Invalid number — mind sharing it again?',
                ),
            ),
            'generic_verify_not_found' => array(
                'pt' => array(
                    'Verificação não encontrada.',
                    'Não achei essa verificação por aqui.',
                    'Hmm, não localizei esse pedido de verificação.',
                    'Essa verificação não tá no sistema aqui.',
                    'Não encontrei registro dessa verificação.',
                ),
                'en' => array(
                    'Verification request not found.',
                    'Couldn\'t find that verification here.',
                    'Hmm, couldn\'t locate that verification request.',
                    'That verification isn\'t in the system here.',
                    'No record found for that verification.',
                ),
            ),
            'generic_confirmed_header' => array(
                'pt' => array(
                    'Número confirmado com sucesso!' . "\n\n" . 'Seu código de verificação de 6 dígitos é:',
                    'Prontinho, número confirmado!' . "\n\n" . 'Aqui está seu código de 6 dígitos:',
                    'Feito! Seu número foi confirmado.' . "\n\n" . 'Código de 6 dígitos:',
                    'Show, confirmado com sucesso!' . "\n\n" . 'Seu código de verificação:',
                    'Confirmado! Deu tudo certo.' . "\n\n" . 'Seu código de 6 dígitos é:',
                ),
                'en' => array(
                    'Number confirmed successfully!' . "\n\n" . 'Your 6-digit verification code is:',
                    'All set, number confirmed!' . "\n\n" . 'Here\'s your 6-digit code:',
                    'Done! Your number\'s been confirmed.' . "\n\n" . '6-digit code:',
                    'Nice, confirmed successfully!' . "\n\n" . 'Your verification code:',
                    'Confirmed! Everything checked out.' . "\n\n" . 'Your 6-digit code is:',
                ),
            ),
            'generic_confirmed_footer' => array(
                'pt' => array(
                    'Volte para a página de registro e cole este código para finalizar.',
                    'Agora é só voltar na página de cadastro e colar o código!',
                    'Cola esse código na página de registro pra fechar tudo.',
                    'Falta pouco! Volta pro cadastro e cola esse código.',
                    'Quase lá! Volta na página de registro e finaliza com esse código.',
                ),
                'en' => array(
                    'Go back to the registration page and paste this code to finish.',
                    'Now just go back to the signup page and paste the code!',
                    'Paste this code on the registration page to wrap everything up.',
                    'Almost done! Go back to signup and paste this code.',
                    'So close! Go back to the registration page and finish with this code.',
                ),
            ),
            'generic_use_button'      => array(
                'pt' => array(
                    'Use o botão abaixo para compartilhar seu contato (não digite o número).',
                    'Usa o botãozinho aí embaixo pra compartilhar seu contato, tá?',
                    'Clica no botão abaixo pra mandar seu contato — sem digitar o número.',
                    'É só apertar o botão abaixo pra compartilhar o contato.',
                    'Usa o botão aqui embaixo — mais fácil que digitar o número!',
                ),
                'en' => array(
                    'Use the button below to share your contact (don\'t type the number).',
                    'Tap the button below to share your contact, okay?',
                    'Click the button below to send your contact — no need to type the number.',
                    'Just hit the button below to share your contact.',
                    'Use the button down here — easier than typing the number!',
                ),
            ),
        );

        return $table;
    }
}
