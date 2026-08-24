<?php

/**
 * Apollo Listing Header — part: actions (mark + search / filter / step).
 *
 * Lab source: `.Header02__top` (header-of-listing-events.html, lines 1050-1056).
 * The lab used <div>s for the three controls; real buttons are used here so the
 * header is keyboard-operable without any JS role shim.
 *
 * Icons stay as bare <i class="ri-*"> on purpose — core.js's icon runtime
 * hydrates them into <svg> itself. Never hand-write SVG here. The filter glyph
 * is the registry's canonical one (07-icons.json → `filter`).
 *
 * TWO THINGS THIS PART OWES THE REST OF THE SYSTEM
 * ─────────────────────────────────────────────────────────────────────────────
 * 1. STABLE IDS. Every control carries `{header-id}-btn-*`. The data-* hooks
 *    are the kernel's private wiring; an id is what a screen, a stylesheet or a
 *    test can target without reaching through the block's internals. They are
 *    prefixed with the instance id so two headers on one page never collide,
 *    and `-btn-` keeps them clear of the overlays' `{id}-search` / `{id}-filter`.
 * 2. THE MARK IS A SIBLING OF .alh__top, not a child. Skins lay the header out
 *    as ONE row with the action cluster absolutely parked at the far right; a
 *    mark nested inside that cluster would be dragged to the right edge with
 *    it. Outside, it is simply the first item of the row.
 *
 * @package Apollo\Templates
 * @since   1.5.1
 *
 * @var array<string, mixed> $alh
 */

if (! defined('ABSPATH')) {
    exit;
}

$alh_btn = $alh['id'] . '-btn-';

if ('' !== $alh['mark']) :
    ?>
<div class="alh__mark"><?php echo esc_html($alh['mark']); ?></div>
    <?php
endif;
?>
<div class="alh__top">
    <div class="alh__icons">
        <?php if (is_array($alh['search'])) : ?>
        <button type="button" class="alh__ic alh__ic--search" data-alh-search-open
            id="<?php echo esc_attr($alh_btn . 'search'); ?>"
            title="<?php echo esc_attr($alh['search']['title']); ?>"
            aria-label="<?php echo esc_attr($alh['search']['title']); ?>">
            <i class="ri-search-line" aria-hidden="true"></i>
        </button>
        <?php endif; ?>

        <?php if (is_array($alh['filter'])) : ?>
        <button type="button" class="alh__ic alh__ic--filter" data-alh-filter-open
            id="<?php echo esc_attr($alh_btn . 'filter'); ?>"
            title="<?php echo esc_attr($alh['filter']['title']); ?>"
            aria-label="<?php echo esc_attr($alh['filter']['title']); ?>"
            aria-expanded="false"
            aria-controls="<?php echo esc_attr($alh['id']); ?>-filter">
            <i class="ri-filter-3-line" aria-hidden="true"></i>
            <span class="alh__badge" data-alh-filter-badge aria-hidden="true">0</span>
        </button>
        <?php endif; ?>

        <?php if ($alh['prev']) : ?>
        <button type="button" class="alh__step alh__step--prev" data-alh-month-prev
            id="<?php echo esc_attr($alh_btn . 'prev'); ?>"
            title="<?php esc_attr_e('Mês anterior', 'apollo-templates'); ?>"
            aria-label="<?php esc_attr_e('Mês anterior', 'apollo-templates'); ?>">
            <i class="ri-arrow-left-s-line" aria-hidden="true"></i>
        </button>
        <?php endif; ?>

        <?php
        /*
         * NO `next` CONTROL — removed 2026-08-09, markup and all.
         *
         * It was rendered by default and hiding it in CSS was explicitly not
         * good enough: a display:none button is still tabbable-adjacent noise
         * in the accessibility tree and still ships its label to screen
         * readers. The scrubber below is the month navigator — twelve real
         * buttons that show WHERE the year's events are — and a single
         * one-step arrow parked above it only duplicated one twelfth of that
         * with none of the context.
         *
         * `$alh['next']` survives in the API (see listing-header-api.php) and
         * now defaults to false, so a caller that still passes it gets a
         * no-op instead of a fatal. The kernel already guards on the button's
         * absence — see the `[data-alh-month-next]` lookup in
         * kernel-scripts.php — so nothing downstream needs a change.
         */
        ?>
    </div>
</div>
