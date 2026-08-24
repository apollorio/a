<?php

/**
 * Single DJ Template — Base
 *
 * @package Apollo\DJs
 */

if (! defined('ABSPATH')) {
    exit;
}

ob_start();
?>
    <!-- Page styles -->
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: var(--ff-main, 'Space Grotesk', system-ui, sans-serif);
            line-height: 1.5;
        }
    </style>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => get_the_title() . ' - Apollo::Rio',
            'extra_head' => $extra_head,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>

<body>
    <?php

    $dj_id     = get_the_ID();
    $dj_name   = get_the_title();
    $bio_short = get_post_meta($dj_id, '_dj_bio_short', true);
    $banner    = apollo_dj_get_banner($dj_id);
    $image     = apollo_dj_get_image($dj_id);
    $verified  = apollo_dj_is_verified($dj_id);
    $sounds    = apollo_dj_get_sounds($dj_id);
    $links     = apollo_dj_get_links($dj_id);
    $events    = apollo_dj_get_upcoming_events($dj_id, 10);
    ?>

    <div class="a-dj-single">
        <!-- Banner -->
        <div class="a-dj-single__banner" style="background-image: url('<?php echo esc_url($banner); ?>');">
            <div class="a-dj-single__banner-overlay"></div>
        </div>

        <!-- Header / Profile -->
        <div class="a-dj-single__header">
            <div class="a-dj-single__avatar">
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($dj_name); ?>">
            </div>

            <div class="a-dj-single__info">
                <h1 class="a-dj-single__name">
                    <?php echo esc_html($dj_name); ?>
                    <?php if ($verified) : ?>
                        <span class="a-dj-single__verified"><i class="ri-verified-badge-fill"></i></span>
                    <?php endif; ?>
                </h1>

                <?php if ($bio_short) : ?>
                    <p class="a-dj-single__bio-short"><?php echo esc_html($bio_short); ?></p>
                <?php endif; ?>

                <?php if (! empty($sounds)) : ?>
                    <div class="a-dj-single__sounds">
                        <?php foreach ($sounds as $sound) : ?>
                            <span class="a-dj-card__sound-tag"><?php echo esc_html($sound); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Social Links -->
            <?php if (! empty($links)) : ?>
                <div class="a-dj-single__links">
                    <?php foreach (array('music', 'social', 'platforms') as $group) : ?>
                        <?php if (! empty($links[$group])) : ?>
                            <div class="a-dj-single__link-group">
                                <?php foreach ($links[$group] as $link) : ?>
                                    <a href="<?php echo esc_url($link['url']); ?>"
                                        target="_blank" rel="noopener"
                                        class="a-dj-single__link"
                                        title="<?php echo esc_attr($link['label']); ?>">
                                        <i class="<?php echo esc_attr($link['icon']); ?>"></i>
                                        <span><?php echo esc_html($link['label']); ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Content -->
        <div class="a-dj-single__content">
            <div class="a-dj-single__main">
                <div class="a-dj-single__description">
                    <?php the_content(); ?>
                </div>
            </div>

            <aside class="a-dj-single__sidebar">
                <!-- Próximos Eventos -->
                <?php if (! empty($events)) : ?>
                    <div class="a-dj-single__upcoming">
                        <h3><?php esc_html_e('Próximos Eventos', 'apollo-djs'); ?></h3>
                        <ul class="a-dj-single__events-list">
                            <?php
                            foreach ($events as $event) :
                                $start_date = get_post_meta($event->ID, '_event_start_date', true);
                                $start_time = get_post_meta($event->ID, '_event_start_time', true);
                            ?>
                                <li class="a-dj-single__event-item">
                                    <a href="<?php echo esc_url(get_permalink($event->ID)); ?>">
                                        <span class="a-dj-single__event-date">
                                            <?php echo esc_html(date_i18n('d/m', strtotime($start_date))); ?>
                                        </span>
                                        <span class="a-dj-single__event-title">
                                            <?php echo esc_html($event->post_title); ?>
                                        </span>
                                        <?php if ($start_time) : ?>
                                            <span class="a-dj-single__event-time"><?php echo esc_html($start_time); ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </div>

    <?php if (is_user_logged_in()) { apollo_render_navbar(); } ?>

</body>

</html>