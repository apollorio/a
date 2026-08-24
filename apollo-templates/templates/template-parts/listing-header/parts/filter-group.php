<?php

/**
 * Apollo Listing Header — part: one filter group (title + chips).
 *
 * The smallest unit of the block, and the reason the filter panel is reusable:
 * a caller describes its groups as plain PHP arrays and never touches markup.
 *
 * Checkbox groups are additive filters and count toward the Apply badge. Radio
 * groups are single-choice mode switches (e.g. "Período"), so they render with
 * the same chip skin but are excluded from the count — otherwise a pristine
 * panel would claim one active filter before the user touched anything.
 *
 * @package Apollo\Templates
 * @since   1.5.1
 *
 * @var array<string, mixed> $group
 */

if (! defined('ABSPATH')) {
    exit;
}

if (empty($group) || ! is_array($group)) {
    return;
}
?>
<div class="alh-fx__group"
    role="group"
    data-alh-group="<?php echo esc_attr($group['key']); ?>"
    data-alh-type="<?php echo esc_attr($group['type']); ?>"
    data-alh-counts="<?php echo $group['counts'] ? '1' : '0'; ?>"
    aria-label="<?php echo esc_attr($group['label']); ?>">

    <?php if ('' !== $group['label']) : ?>
    <span class="alh-fx__title"><?php echo esc_html($group['label']); ?></span>
    <?php endif; ?>

    <div class="alh-fx__chips">
        <?php foreach ($group['options'] as $alh_i => $alh_option) :
            $alh_input_id = $group['input_id'] . '-' . $alh_i;
            ?>
        <div class="alh-fx__chip">
            <input type="<?php echo esc_attr($group['type']); ?>"
                id="<?php echo esc_attr($alh_input_id); ?>"
                name="<?php echo esc_attr($group['name']); ?>"
                value="<?php echo esc_attr($alh_option['value']); ?>"
                <?php checked(in_array($alh_option['value'], $group['value'], true)); ?>>
            <label for="<?php echo esc_attr($alh_input_id); ?>">
                <i class="ri-check-line" aria-hidden="true"></i><?php echo esc_html($alh_option['label']); ?>
            </label>
        </div>
        <?php endforeach; ?>
    </div>
</div>
