<?php

/**
 * Apollo Listing Header — part: 12-segment month scrubber.
 *
 * Lab source: `.Header02__scrub` (header-of-listing-events.html, line 1061),
 * where the twelve segments were appended by header-02-init's JS loop.
 *
 * They are server-rendered here instead, for three reasons that all matter on
 * a real page and not in a device mock: the control exists before the runtime
 * boots (no empty rail on slow connections), the month names/counts come from
 * PHP as the registry's data-flow rule requires, and each segment can be a
 * real <button> with its own accessible name rather than a click-only <div>.
 *
 * The per-month count rides along as a title/aria hint — it is real data from
 * the caller, and it is what makes the scrubber a map of the year rather than
 * twelve identical dashes.
 *
 * @package Apollo\Templates
 * @since   1.5.1
 *
 * @var array<string, mixed> $alh
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! $alh['scrub']) {
    return;
}
?>
<div class="alh__scrub" data-alh-scrub role="tablist"
    aria-label="<?php esc_attr_e('Meses do ano', 'apollo-templates'); ?>">
    <?php foreach ($alh['months'] as $alh_i => $alh_month) :
        $alh_on   = ($alh_i === $alh['month']['index']);
        $alh_name = $alh_month['label'] . ' ' . $alh['year'];
        if ($alh_month['count'] > 0) {
            /* translators: 1: month and year, 2: number of events. */
            $alh_name = sprintf(
                _n('%1$s · %2$d evento', '%1$s · %2$d eventos', $alh_month['count'], 'apollo-templates'),
                $alh_name,
                $alh_month['count']
            );
        }
        ?>
    <button type="button"
        class="alh__seg-hit<?php echo $alh_on ? ' is-active' : ''; ?>"
        role="tab"
        data-alh-seg="<?php echo esc_attr((string) $alh_i); ?>"
        data-alh-count="<?php echo esc_attr((string) $alh_month['count']); ?>"
        aria-selected="<?php echo $alh_on ? 'true' : 'false'; ?>"
        tabindex="<?php echo $alh_on ? '0' : '-1'; ?>"
        title="<?php echo esc_attr($alh_name); ?>"
        aria-label="<?php echo esc_attr($alh_name); ?>">
        <span class="alh__seg" aria-hidden="true"></span>
    </button>
    <?php endforeach; ?>
</div>
