<?php

/**
 * Apollo Listing Header — part: filter overlay (shell + footer).
 *
 * Lab source: `.ApolloFilterOverlay` instance (header-of-listing-events.html,
 * lines 1078-1088). One structural change, and it is the important one:
 *
 *   THE LAB BUILT ITS CHIPS IN JAVASCRIPT. `buildChipsHTML()` walked a
 *   hard-coded APOLLO_TAXONOMY literal in the page's own <script> — fine for a
 *   mock, forbidden here. Registry 03-apollo-rule.$apollo_rule.data_flow makes
 *   the PHP array the source of truth and the DOM a render of it, so the groups
 *   are emitted by filter-group.php from real terms the caller passed in. The
 *   kernel now only counts, clears and applies what PHP already drew.
 *
 * With zero groups the panel prints one empty state. It never invents a term.
 *
 * @package Apollo\Templates
 * @since   1.5.1
 *
 * @var array<string, mixed> $alh
 */

if (! defined('ABSPATH')) {
    exit;
}

$alh_f = $alh['filter'];
?>
<div class="alh-ov alh-ov--filter"
    id="<?php echo esc_attr($alh['id']); ?>-filter"
    data-alh-filter-overlay
    role="dialog"
    aria-modal="true"
    aria-label="<?php echo esc_attr($alh_f['title']); ?>"
    aria-hidden="true">

    <div class="alh-ov__hd">
        <h3><?php echo esc_html($alh_f['title']); ?></h3>
        <button type="button" class="alh-ov__close" data-alh-filter-close
            aria-label="<?php esc_attr_e('Fechar', 'apollo-templates'); ?>">
            <i class="ri-close-line" aria-hidden="true"></i>
        </button>
    </div>

    <div class="alh-ov__body" data-alh-filter-body>
        <?php
        if (empty($alh_f['groups'])) :
            ?>
        <div class="alh-fx__empty"><?php echo esc_html($alh_f['empty']); ?></div>
            <?php
        else :
            foreach ($alh_f['groups'] as $alh_group) {
                apollo_plus_part('listing-header/parts/filter-group', array('group' => $alh_group));
            }
        endif;
        ?>
    </div>

    <div class="alh-ov__footer alh-fx__footer">
        <button type="button" class="alh-fx__btn-clear" data-alh-filter-clear>
            <?php echo esc_html($alh_f['clear']); ?>
        </button>
        <button type="button" class="alh-fx__btn-apply" data-alh-filter-apply>
            <?php echo esc_html($alh_f['apply']); ?>
            <span class="alh-fx__n" data-alh-filter-n>0</span>
        </button>
    </div>
</div>
