<?php

/**
 * Apollo Listing Header — orchestrator.
 *
 * The ONLY part a page includes. Everything else is pulled from here, and the
 * shared layers (kernel CSS, kernel JS, per-skin CSS) are guarded so N headers
 * on one page still emit each of them exactly once.
 *
 * Emission order is a dependency chain, not a preference:
 *   kernel CSS  → primitives the skin restyles
 *   skin CSS    → this instance's look
 *   markup      → <header> + the two overlays, siblings so the overlays can be
 *                 full-viewport without inheriting the header's stacking box
 *   kernel JS   → window.ApolloListingHeader
 *   boot        → this instance's config + create() call
 *
 * @package Apollo\Templates
 * @since   1.5.1
 *
 * @var array<string, mixed> $alh Normalised args from apollo_listing_header_args().
 */

if (! defined('ABSPATH')) {
    exit;
}

if (empty($alh) || ! is_array($alh)) {
    return;
}

/* Shared layers — once per request, whatever the instance count. */
if (! defined('APOLLO_LISTING_HEADER_KERNEL')) {
    define('APOLLO_LISTING_HEADER_KERNEL', true);
    apollo_plus_part('listing-header/kernel-styles');
}

$alh_skin_flag = 'APOLLO_LISTING_HEADER_SKIN_' . strtoupper(str_replace('-', '_', $alh['skin']));
if (! defined($alh_skin_flag)) {
    define($alh_skin_flag, true);
    apollo_plus_part($alh['part']);
}
?>
<div class="alh-host" data-alh-host="<?php echo esc_attr($alh['id']); ?>">

    <header id="<?php echo esc_attr($alh['id']); ?>"
        class="<?php echo esc_attr($alh['class']); ?>"
        data-alh-root
        role="navigation"
        aria-label="<?php echo esc_attr($alh['label']); ?>">

        <?php apollo_plus_part('listing-header/parts/actions', array('alh' => $alh)); ?>
        <?php apollo_plus_part('listing-header/parts/month', array('alh' => $alh)); ?>
        <?php apollo_plus_part('listing-header/parts/scrub', array('alh' => $alh)); ?>
    </header>

    <?php
    if (is_array($alh['search'])) {
        apollo_plus_part('listing-header/parts/overlay-search', array('alh' => $alh));
    }
    if (is_array($alh['filter'])) {
        apollo_plus_part('listing-header/parts/overlay-filter', array('alh' => $alh));
    }
    ?>
</div>
<?php

if (! defined('APOLLO_LISTING_HEADER_RUNTIME')) {
    define('APOLLO_LISTING_HEADER_RUNTIME', true);
    apollo_plus_part('listing-header/kernel-scripts');
}

apollo_plus_part('listing-header/boot', array('alh' => $alh));
