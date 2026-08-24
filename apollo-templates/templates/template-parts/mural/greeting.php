<?php

/**
 * Mural: Greeting
 *
 * Personalized hello + location + next event alert.
 *
 * @package Apollo\Templates
 */

if (! defined('ABSPATH')) {
    exit;
}

// Variables from page-mural.php:
// $first_name, $user_location, $next_event, $next_event_days

// ═══════════════════════════════════════════════════════════════════════════
// MODERN GREETING SYSTEM - Time + Weekday + Vibe
// ═══════════════════════════════════════════════════════════════════════════

// Get WordPress time with proper timezone
$hour    = (int) current_time('H'); // Use 'H' instead of 'G' for 24-hour format with leading zeros
$weekday = (int) current_time('N'); // 1=Mon, 5=Fri, 7=Sun

// DEBUG: Remove after fixing
// error_log( 'Apollo Greeting - Hour: ' . $hour . ' | Time: ' . current_time( 'Y-m-d H:i:s' ) );

// Base greeting by time
if ($hour < 6) {
    $greeting = 'Boa madrugada';
} elseif ($hour < 12) {
    $greeting = 'Bom dia';
} elseif ($hour < 18) {
    $greeting = 'Boa tarde';
} else {
    $greeting = 'Boa noite';
}

// Secondary message/vibe based on day + time
$vibe_message = '';
$vibe_icon    = '';

// THURSDAY NIGHT (after 18h) - Weekend warmup
if ($weekday === 4 && $hour >= 18) {
    $vibes        = array(
        'Esquenta de fim de semana! 🔥',
        'A noite é uma criança! ✨',
        'Quinta já é sexta no Rio! 🌆',
        'Bora pra rua! 🎉',
    );
    $vibe_message = $vibes[array_rand($vibes)];
    $vibe_icon    = 'ri-fire-fill';
}

// FRIDAY NIGHT (after 18h) - Party mode
elseif ($weekday === 5 && $hour >= 18) {
    $vibes        = array(
        'Sextou com força! 🎊',
        'A cidade te espera! 🌃',
        'Noite de balada! 💃',
        'Bora agitar! 🚀',
        'Energia total! ⚡',
    );
    $vibe_message = $vibes[array_rand($vibes)];
    $vibe_icon    = 'ri-rocket-fill';
}

// FRIDAY DAY - Pre-party energy
elseif ($weekday === 5 && $hour < 18) {
    $vibes        = array(
        'Sexta-feira florida! 🌺',
        'O fim de semana começa agora! 🎯',
        'Energia boa no ar! ☀️',
    );
    $vibe_message = $vibes[array_rand($vibes)];
    $vibe_icon    = 'ri-sparkling-fill';
}

// SATURDAY/SUNDAY - Weekend vibes
elseif ($weekday === 6 || $weekday === 7) {
    if ($hour < 12) {
        $vibes     = array(
            'Fim de semana merece descanso! 🛋️',
            'Respira fundo e aproveita! 🌊',
            'Momento de recarregar! 🔋',
        );
        $vibe_icon = 'ri-sun-cloudy-fill';
    } else {
        $vibes     = array(
            'Curte cada momento! 🎵',
            'O Rio está on! 🏖️',
            'Vibe de fim de semana! 🌴',
        );
        $vibe_icon = 'ri-music-2-fill';
    }
    $vibe_message = $vibes[array_rand($vibes)];
}

// MONDAY - Restart energy
elseif ($weekday === 1) {
    $vibes        = array(
        'Segunda com propósito! 💪',
        'Respira fundo. Nova semana! 🧘',
        'Um passo de cada vez! 🚶',
        'Recomeço é vida! 🌱',
    );
    $vibe_message = $vibes[array_rand($vibes)];
    $vibe_icon    = 'ri-seedling-fill';
}

// TUESDAY/WEDNESDAY - Calm & focus
elseif ($weekday === 2 || $weekday === 3) {
    $vibes        = array(
        'Respira e segue em frente! 🌬️',
        'Calma e foco! 🎯',
        'Medita no presente! 🧘‍♀️',
        'Energia equilibrada! ⚖️',
        'Pausa pra respirar! 💙',
    );
    $vibe_message = $vibes[array_rand($vibes)];
    $vibe_icon    = 'ri-heart-pulse-fill';
}

// Late night / madrugada chill message (23h–5h — matches greeting "Boa madrugada" range)
if ($hour >= 23 || $hour < 6) {
    $late_vibes   = array(
        'Momento de silêncio e música! 🎧',
        'A madrugada tem sua magia! ✨',
        'Respira fundo. Tá quase! 🌙',
        'O silêncio da noite inspira! 🌌',
        'Quem dorme perde a vibe! 🦉',
    );
    $vibe_message = $late_vibes[array_rand($late_vibes)];
    $vibe_icon    = 'ri-moon-fill';
}
?>

<header class="mural-greeting">
    <?php
    // ═══ WEATHER HERO AS MAIN GREETING ═══
    $parts_dir = APOLLO_TEMPLATES_DIR . 'templates/template-parts/mural/';
    require $parts_dir . 'weather-hero.php';
    ?>

    <?php if ($vibe_message) : ?>
        <div class="greet-vibe">
            <?php if ($vibe_icon) : ?>
                <i class="<?php echo esc_attr($vibe_icon); ?>"></i>
            <?php endif; ?>
            <span><?php echo esc_html($vibe_message); ?></span>
        </div>
    <?php endif; ?>

    <div class="greet-temp">
        <i class="ri-map-pin-2-fill"></i>
        <?php echo esc_html($user_location); ?>
    </div>

    <?php
    if ($next_event) :
        $ev_loc    = get_post_meta($next_event->ID, '_event_local_name', true) ?: '';
        $days_text = $next_event_days === 0
            ? 'hoje'
            : ($next_event_days === 1 ? 'amanhã' : "em {$next_event_days} dias");
    ?>
        <div class="greet-alert">
            Não perca <strong><?php echo esc_html($next_event->post_title); ?></strong>
            <?php echo esc_html($days_text); ?><?php if ($ev_loc) : ?>
            no <strong><?php echo esc_html($ev_loc); ?></strong><?php endif; ?>.
        </div>
    <?php endif; ?>
</header>