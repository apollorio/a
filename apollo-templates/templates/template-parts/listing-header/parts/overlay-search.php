<?php

/**
 * Apollo Listing Header — part: search overlay.
 *
 * Lab source: `.ApolloSearchOverlay` instance (header-of-listing-events.html,
 * lines 1064-1076). Same panel, same discreet "−" submit, same hint line.
 *
 * It is a real <form>: pressing Enter on a phone keyboard submits natively,
 * and the kernel intercepts `submit` rather than re-implementing the Enter key.
 * A screen that never boots the runtime therefore still degrades to a plain
 * GET, instead of an input that silently does nothing.
 *
 * @package Apollo\Templates
 * @since   1.5.1
 *
 * @var array<string, mixed> $alh
 */

if (! defined('ABSPATH')) {
    exit;
}

$alh_s = $alh['search'];
?>
<div class="alh-ov alh-ov--search"
    id="<?php echo esc_attr($alh['id']); ?>-search"
    data-alh-search-overlay
    role="dialog"
    aria-modal="true"
    aria-label="<?php echo esc_attr($alh_s['title']); ?>"
    aria-hidden="true">
    <form class="alh-search__panel" data-alh-search-form role="search" method="get" action="">
        <div class="alh-ov__hd">
            <h3><?php echo esc_html($alh_s['title']); ?></h3>
            <button type="button" class="alh-ov__close" data-alh-search-close
                aria-label="<?php esc_attr_e('Fechar', 'apollo-templates'); ?>">
                <i class="ri-close-line" aria-hidden="true"></i>
            </button>
        </div>
        <div class="alh-search__row">
            <input class="alh-search__input" type="search" name="s" data-alh-search-input
                value="<?php echo esc_attr($alh_s['value']); ?>"
                placeholder="<?php echo esc_attr($alh_s['placeholder']); ?>"
                enterkeyhint="search" autocomplete="off"
                aria-label="<?php echo esc_attr($alh_s['title']); ?>">
            <button type="submit" class="alh-search__go" data-alh-search-submit
                title="<?php echo esc_attr($alh_s['title']); ?>"
                aria-label="<?php esc_attr_e('Enviar', 'apollo-templates'); ?>">&minus;</button>
        </div>
        <p class="alh-search__hint"><?php echo esc_html($alh_s['hint']); ?></p>
    </form>
</div>
