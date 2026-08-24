<?php

/**
 * Apollo Listing Header — part: the kinetic mask (month + year).
 *
 * Lab source: `.Header02__mask` (header-of-listing-events.html, lines 1057-1060).
 *
 * The month name is rendered by PHP, not written by JS on boot: the correct
 * label must be in the first paint's HTML, or the screen flashes an em-dash
 * placeholder and the label is invisible to anything that doesn't run scripts.
 * The kernel only swaps it afterwards, on navigation.
 *
 * aria-live is polite on the wrapper so a month change is announced once, as a
 * single "Setembro 2026" string, instead of two separate node mutations.
 *
 * @package Apollo\Templates
 * @since   1.5.1
 *
 * @var array<string, mixed> $alh
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="alh__mask" aria-live="polite" aria-atomic="true">
    <div class="alh__month" data-alh-month><?php echo esc_html($alh['month']['label']); ?></div>
    <div class="alh__year" data-alh-year>&rsquo;<?php echo esc_html(substr((string) $alh['year'], -2)); ?></div>
</div>
