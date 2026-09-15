<?php

/**
 * New Home — Tracks / Out Now! Section
 *
 * A horizontal rail of the 15 newest releases, rendered through the shared track
 * card. Falls back to simulated placeholder covers only while zero real tracks
 * exist (pre-launch) — restores automatically the moment the first release goes
 * live, no template change needed.
 *
 * ── WHAT CHANGED 2026-08-17, AND WHAT DID NOT ────────────────────────────────
 *
 * DID NOT CHANGE: the locked-guest design. Circular blur cover, the two vinyl
 * rings, the disabled ri-lock-2-line button, the ● ● ● masked labels — every
 * class and every aria attribute is byte-identical. It moved to
 * apollo-templates/templates/template-parts/track/card.php so it can be REUSED,
 * which is the opposite of replacing it. Verified: zero classes removed, zero
 * attributes removed, tag sequence identical; the only additions are the variant
 * class and data-track-id.
 *
 * DID CHANGE: this file contained the card markup TWICE — ~55 lines for real
 * tracks and the same ~55 again for the simulated placeholders, differing only
 * in where the data came from. Both paths now render through one part. That
 * duplication is the same defect that produced four accommodation cards and five
 * "Out Now" sections; it is gone here.
 *
 * ALSO: the data source. apollo_get_latest_dj_tracks() ran an unbounded N+1 —
 * every published DJ, one get_post_meta each, uncached, on every render — because
 * tracks used to live in meta. They are a CPT now, so this is one indexed query.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 * @see     _inventory/PLAN-track-releases.md
 */

if (! defined('ABSPATH')) {
    exit;
}

/* The 15 newest releases. One query — see apollo_track_query(). */
$track_ids = function_exists('apollo_track_query') ? apollo_track_query(array('posts_per_page' => 15)) : array();
$has_real  = ! empty($track_ids);

/* Simulated covers — on-brand placeholders; used only until real tracks exist. */
$simulated_covers = array(
    'https://images.unsplash.com/photo-1571330735066-03aaa9429d89?w=400&q=75',
    'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&q=75',
    'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=400&q=75',
    'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=400&q=75',
    'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=400&q=75',
    'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=400&q=75',
    'https://images.unsplash.com/photo-1506157786151-b8491531f063?w=400&q=75',
    'https://images.unsplash.com/photo-1524368535928-5b5e00ddc76b?w=400&q=75',
);

/*
 * Graceful degradation. If apollo-djs is inactive the card contract has no
 * renderer registered, apollo_card_render() returns '' for every id, and this
 * section would be an empty band. Checking once here lets it render nothing at
 * all instead — a missing section reads as "not yet", an empty bordered box
 * reads as broken.
 */
$can_render = function_exists('apollo_card_render') && function_exists('apollo_card_get') && apollo_card_get('track');
if (! $can_render) {
    return;
}

if ($has_real && function_exists('apollo_track_enqueue_listen_assets')) {
    apollo_track_enqueue_listen_assets($track_ids);
}
?>

<section class="section" id="tracks" aria-labelledby="tracks-title">
    <div class="container">
        <div class="nh-section-head ai nh-section-head--toolbar">
            <h2 id="tracks-title" split-chars>Out Now!</h2>
            <a class="nh-section-more" href="<?php echo esc_url(home_url('/tracks')); ?>"
                aria-label="<?php esc_attr_e('Ver todos os lançamentos', 'apollo-templates'); ?>">Ver todos</a>
        </div>

        <?php
        /*
         * data-lenis-prevent is REQUIRED, not decorative. Lenis is booted by
         * core.js on /casa and binds a non-passive wheel listener that
         * preventDefaults everything so it can drive scrolling itself — which
         * kills native scrolling in any nested overflow container, this rail
         * included. It is Lenis's own documented opt-out and the same one the
         * event lightbox uses for its scroll panel.
         */
        ?>
        <div class="nh-tracks-rail" data-lenis-prevent role="list"
             aria-label="<?php esc_attr_e('Lançamentos recentes', 'apollo-templates'); ?>">

            <?php if ($has_real) : ?>

                <?php foreach ($track_ids as $track_id) : ?>
                    <?php
                    // phpcs:ignore WordPress.Security.EscapeOutput -- the card part escapes its own output.
                    echo apollo_card_render('track', (int) $track_id, array('variant' => 'rail'));
                    ?>
                <?php endforeach; ?>

            <?php else : ?>

                <?php foreach ($simulated_covers as $cover) : ?>
                    <?php
                    /*
                     * The placeholder path goes through the SAME part. That is
                     * the whole point — before, it was a second copy of the
                     * locked design, and a second copy is where a change gets
                     * forgotten.
                     */
                    // phpcs:ignore WordPress.Security.EscapeOutput -- the card part escapes its own output.
                    echo apollo_card_render('track', 0, array(
                        'variant'     => 'rail',
                        'placeholder' => true,
                        'cover'       => $cover,
                    ));
                    ?>
                <?php endforeach; ?>

            <?php endif; ?>

        </div><!-- /.nh-tracks-rail -->
    </div>
</section>
