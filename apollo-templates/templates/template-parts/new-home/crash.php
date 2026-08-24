<?php

/**
 * New Home — Crash / Acomoda::Rio Section
 *
 * Image marquee of accommodations (`local` CPT filtered to the accommodation
 * types ONLY — never falls back to all `local`, which wrongly showed event
 * venues). The explorer map (map.php) renders immediately after this section.
 *
 * LOCK RULE — one switch decides everything here:
 *   · Hostel (_classified_hostel = '1') → public business, already listed
 *     worldwide. Card is OPEN to guests and its CTA goes to the hostel's own
 *     site (_classified_hostel_url) instead of Apollo's chat.
 *   · Anything else → somebody's home. Padlock shown, host identity withheld,
 *     and clicking the card opens the hub.rio auth lightbox.
 * Absent meta means "not a hostel", so the default is always locked.
 *
 * NOTE (cross-surface): the hostel switch is registered in apollo-core against
 * the `classified` CPT, because that's where accommodation ADVERTS live
 * (apollo-adverts, _classified_type = accommodation|rent_space). This /casa
 * strip instead lists the `local` CPT — a genuine divergence in how
 * accommodation is modelled across the two surfaces. Reading the same key here
 * means this section honours the switch the moment a `local` post carries it;
 * until then every stay shown here is locked, which is the safe default for a
 * privacy gate. Unifying the two surfaces is a product decision, not a fix to
 * make silently.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

$is_logged_in = is_user_logged_in();
$login_url    = home_url('/acesso');

/* Accommodations ONLY — strict tax filter, NO fallback to all `local`
   (the old fallback surfaced event venues as if they were stays). */
$crash_query = new WP_Query(
    array(
        'post_type'      => 'local',
        'posts_per_page' => 8,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'tax_query'      => array(
            array(
                'taxonomy' => 'local_type',
                'field'    => 'slug',
                'terms'    => array('crash', 'hospedagem', 'acomoda'),
                'operator' => 'IN',
            ),
        ),
    )
);

$mq_items = array();
if ($crash_query->have_posts()) {
    while ($crash_query->have_posts()) {
        $crash_query->the_post();
        $lid   = get_the_ID();
        $areas = wp_get_post_terms($lid, 'local_area', array('fields' => 'names'));
        $area  = ! is_wp_error($areas) && ! empty($areas) ? $areas[0] : '';
        $price = get_post_meta($lid, '_local_price_range', true);

        $is_hostel = function_exists('apollo_adverts_is_hostel')
            ? apollo_adverts_is_hostel($lid)
            : '1' === (string) get_post_meta($lid, '_classified_hostel', true);
        $hostel_url = $is_hostel ? (string) get_post_meta($lid, '_classified_hostel_url', true) : '';

        $mq_items[] = array(
            'img'    => get_the_post_thumbnail_url($lid, 'medium') ?: 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&q=75',
            'title'  => get_the_title(),
            'sub'    => $area ?: ($price ?: '—'),
            'chat'   => $is_logged_in ? get_permalink() : '',
            'hostel' => $is_hostel,
            // Hostel with no URL saved yet: still open (nothing private to
            // protect), just send them to the listing itself.
            'out'    => $is_hostel ? ($hostel_url ?: get_permalink()) : '',
        );
    }
    wp_reset_postdata();
}
?>

<section class="section" id="crash" aria-labelledby="crash-title">
    <div class="container">
        <div class="nh-section-head ai">
            <h2 id="crash-title" split-chars>Acomoda::Rio</h2>
        </div>

        <?php if (! empty($mq_items)) : ?>
            <div class="nh-mq ai" aria-label="<?php esc_attr_e('Acomodações', 'apollo-templates'); ?>">
                <div class="nh-mq-track">
                    <?php for ($pass = 0; $pass < 2; $pass++) : ?>
                        <?php foreach ($mq_items as $it) : ?>
                            <?php
                            // Guests only get the gate on non-hostel stays; a hostel is
                            // public, so it is never gated for anyone.
                            $gate = ! $is_logged_in && ! $it['hostel'];
                            ?>
                            <figure class="nh-mq-card" <?php echo $pass ? 'aria-hidden="true"' : ''; ?>
                                <?php echo $gate ? 'data-auth-required role="button" tabindex="0"' : ''; ?>>
                                <img src="<?php echo esc_url($it['img']); ?>" alt="<?php echo esc_attr($it['title']); ?>" loading="lazy" />
                                <div class="nh-mq-veil" aria-hidden="true"></div>
                                <figcaption class="nh-mq-body">
                                    <span class="nh-mq-meta">
                                        <span class="nh-mq-title"><?php echo esc_html($it['title']); ?></span>
                                        <span class="nh-mq-sub"><?php echo $gate ? '● ● ●' : esc_html($it['sub']); ?></span>
                                    </span>
                                    <?php if ($it['hostel']) : ?>
                                        <?php /* Public hostel — out to its own site, no chat, no padlock. */ ?>
                                        <a class="nh-mq-chat nh-mq-chat--hostel" href="<?php echo esc_url($it['out']); ?>"
                                            target="_blank" rel="noopener"><i class="ri-external-link-line"></i> Reservar</a>
                                    <?php elseif ($is_logged_in && $it['chat']) : ?>
                                        <a class="nh-mq-chat" href="<?php echo esc_url($it['chat']); ?>"><i class="ri-chat-3-line"></i> Chat</a>
                                    <?php else : ?>
                                        <?php /* NOT disabled — the click is what opens the gate. */ ?>
                                        <button type="button" class="nh-mq-chat is-locked" data-auth-required
                                            aria-label="<?php esc_attr_e('Entre para conversar', 'apollo-templates'); ?>"><i class="ri-lock-2-line"></i> Chat</button>
                                    <?php endif; ?>
                                </figcaption>
                            </figure>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </div>
            </div>
        <?php else : ?>
            <div class="nh-empty-state ai" style="grid-column:1/-1;">
                <i class="ri-home-heart-line" aria-hidden="true"></i>
                <p>Em breve..</p>
                <span class="nh-empty-sub">A rede Acomoda::Rio está sendo construída. Em breve você poderá hospedar ou encontrar um lugar.</span>
            </div>
        <?php endif; ?>
    </div>
</section>
