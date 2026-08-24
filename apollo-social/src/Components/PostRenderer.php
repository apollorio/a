<?php
/**
 * Post Renderer — Transform post data into modular HTML
 *
 * @package Apollo\Social
 * @version 6.5.0
 */

namespace Apollo\Social\Components;

if (! defined('ABSPATH')) {
    exit;
}

class PostRenderer {
    /**
     * Render a single post with all media, actions, comments
     *
     * @param array $post Post data.
     * @param array $args Render options.
     * @return string HTML
     */
    public static function render_post(array $post, array $args = []): string {
        $defaults = [
            'show_comments' => true,
            'show_actions' => true,
            'variant' => 'default', // default|cinema|card
        ];
        $args = wp_parse_args($args, $defaults);

        $classes = ['post', 'gsap-el'];
        if ('cinema' === $args['variant']) {
            $classes[] = 'card-cinema';
        }

        ob_start();
        ?>
        <article class="<?php echo esc_attr(implode(' ', $classes)); ?>"
            data-a-user="<?php echo esc_attr((string) ($post['author_id'] ?? 0)); ?>"
            data-a-component="social-post">
            <?php
            echo self::render_header($post);
            echo self::render_body($post);
            echo self::render_media($post);
            if ($args['show_actions']) {
                echo self::render_footer($post);
            }
            if ($args['show_comments']) {
                echo self::render_comments($post);
            }
            ?>
        </article>
        <?php
        return ob_get_clean();
    }

    /**
     * Render post header (avatar, name, meta)
     */
    private static function render_header(array $post): string {
        ob_start();
        $author_id = (int) ($post['author_id'] ?? 1);
        $author = get_user_by('id', $author_id);
        if (!$author) {
            return '';
        }

        // Canonical SSOT user-display data: name, badge, membership, núcleos, handle, time.
        // (apollo-users/includes/functions.php::apollo_get_user_display_data)
        $data = function_exists('apollo_get_user_display_data')
            ? apollo_get_user_display_data($author_id)
            : array();

        $display_name = $data['display_name'] ?? $author->display_name;
        $avatar_url   = $data['avatar_url'] ?? get_avatar_url($author_id, ['size' => 100]);
        $handle       = $data['handle'] ?? '@' . $author->user_nicename;
        $badge        = $data['badge'] ?? array();
        $nucleos      = $data['nucleos'] ?? array();

        // Badge shows ONLY when the user actually holds a membership badge (SSOT rule).
        $badge_type = $badge['type'] ?? 'nao-verificado';
        $badge_icon = $badge['ri_icon'] ?? '';
        if ('' === $badge_icon && function_exists('apollo_membership_get_ri_icon')) {
            $badge_icon = apollo_membership_get_ri_icon($badge_type);
        }
        $has_badge = ('nao-verificado' !== $badge_type) && '' !== $badge_icon;

        // Post age for the byline time-ago.
        $time_ago = (!empty($post['date']) && function_exists('apollo_time_ago'))
            ? apollo_time_ago($post['date'])
            : '';
        ?>
        <div class="post-header">
            <div class="post-meta">
                <div class="av-md"><img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>"></div>
                <div class="profile-identity">
                    <h1 class="name">
                        <span><?php echo esc_html($display_name); ?></span>
                        <?php if ($has_badge) : ?>
                            <span class="user-membership-badge apollo-badge-<?php echo esc_attr($badge_type); ?>" title="<?php echo esc_attr($badge['label'] ?? ''); ?>">
                                <i class="<?php echo esc_attr($badge_icon); ?>"></i>
                            </span>
                        <?php endif; ?>
                    </h1>
                    <?php if (!empty($nucleos)) : ?>
                        <div class="user-nucleos">
                            <?php foreach ($nucleos as $nucleo) : ?>
                                <a class="tag-nucleo" href="<?php echo esc_url(home_url('/grupo/' . ($nucleo['slug'] ?? ''))); ?>">
                                    <i class="ri-team-line"></i><?php echo esc_html($nucleo['name'] ?? ''); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="handle">
                        <span class="uid"><?php echo esc_html($handle); ?></span>
                        <?php if ('' !== $time_ago) : ?>
                            &middot; <span class="time-ago"><?php echo esc_html($time_ago); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($post['category'])) : ?>
                        <span class="tag"><?php echo esc_html($post['category']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <button class="btn-more" data-post-id="<?php echo absint($post['id']); ?>" aria-label="<?php esc_attr_e('More options', 'apollo-social'); ?>" aria-haspopup="true">
                <i class="ri-more-2-fill"></i>
            </button>
            <!-- More Menu Dropdown -->
            <div class="post-more-menu" data-post-id="<?php echo absint($post['id']); ?>" role="menu" aria-hidden="true" style="display: none;">
                <button class="menu-item fav-item" data-action="favorite" role="menuitem">
                    <i class="ri-star-smile-line"></i><?php esc_html_e('Favoritar', 'apollo-social'); ?>
                </button>
                <button class="menu-item link-item" data-action="copy-link" role="menuitem">
                    <i class="ri-links-line"></i><?php esc_html_e('Pegar link disso', 'apollo-social'); ?>
                </button>
                <button class="menu-item share-item" data-action="share-external" role="menuitem">
                    <i class="ri-send-plane-line"></i><?php esc_html_e('Compartilhar fora daqui', 'apollo-social'); ?>
                </button>
                <?php if ($author_id === get_current_user_id()) : ?>
                    <div class="menu-divider"></div>
                    <button class="menu-item edit-item" data-action="edit" role="menuitem">
                        <i class="ri-pencil-line"></i><?php esc_html_e('Editar publicação', 'apollo-social'); ?>
                    </button>
                    <button class="menu-item delete-item danger" data-action="delete" role="menuitem">
                        <i class="ri-delete-bin-4-line"></i><?php esc_html_e('Apagar', 'apollo-social'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render post body text
     */
    private static function render_body(array $post): string {
        if (empty($post['content'])) {
            return '';
        }

        $content = wp_kses_post($post['content']);
        return sprintf(
            '<div class="post-text">%s</div>',
            $content
        );
    }

    /**
     * Render media (image, video, audio, embeds)
     */
    private static function render_media(array $post): string {
        if (empty($post['media'])) {
            return '';
        }

        $media = $post['media'];
        $type = $media['type'] ?? 'image';

        return match ($type) {
            'soundcloud' => self::render_soundcloud($media),
            'spotify' => self::render_spotify($media),
            'youtube' => self::render_youtube($media),
            'event' => self::render_event_card($media),
            'ticket' => self::render_ticket_card($media),
            default => self::render_image($media),
        };
    }

    /**
     * Render image media
     */
    private static function render_image(array $media): string {
        $url = $media['url'] ?? '';
        if (!$url) {
            return '';
        }

        return sprintf(
            '<div class="media-zone"><img src="%s" alt="%s"></div>',
            esc_url($url),
            esc_attr($media['alt'] ?? '')
        );
    }

    /**
     * Render SoundCloud native player
     */
    private static function render_soundcloud(array $media): string {
        $track_name = $media['track'] ?? 'Track';
        $artist_name = $media['artist'] ?? 'Artist';
        $art_url = $media['artwork'] ?? '';
        $duration = $media['duration'] ?? 180;

        ob_start();
        ?>
        <div class="sc-native">
            <div class="sc-art">
                <img src="<?php echo esc_url($art_url); ?>" alt="<?php echo esc_attr($track_name); ?>">
                <div class="play-hover"><i class="ri-play-circle-fill"></i></div>
            </div>
            <div class="sc-info">
                <div class="sc-track"><?php echo esc_html($track_name); ?></div>
                <div class="sc-artist"><?php echo esc_html($artist_name); ?></div>
                <div class="sc-wave">
                    <?php for($i = 0; $i < 8; $i++) : ?>
                        <div class="bar <?php if ($i < 3) echo 'played'; ?>"></div>
                    <?php endfor; ?>
                </div>
                <div class="sc-time">
                    <span>1:23</span>
                    <span><?php echo round($duration / 60); ?>:00</span>
                </div>
            </div>
            <div class="sc-badge"><i class="ri-music-2-line"></i></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render Spotify native player
     */
    private static function render_spotify(array $media): string {
        $track_name = $media['track'] ?? 'Track';
        $artist_name = $media['artist'] ?? 'Artist';
        $art_url = $media['artwork'] ?? '';

        ob_start();
        ?>
        <div class="sp-native">
            <div class="sp-art">
                <img src="<?php echo esc_url($art_url); ?>" alt="<?php echo esc_attr($track_name); ?>">
            </div>
            <div class="sp-info">
                <div class="sp-track"><?php echo esc_html($track_name); ?></div>
                <div class="sp-artist"><?php echo esc_html($artist_name); ?></div>
                <div class="sp-bar-wrap">
                    <div class="sp-bar-fill" style="width: 35%;"></div>
                </div>
                <div class="sp-time">
                    <span>1:24</span>
                    <span>3:45</span>
                </div>
            </div>
            <div class="sp-badge"><i class="ri-spotify-fill"></i></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render YouTube embed
     */
    private static function render_youtube(array $media): string {
        $video_id = $media['video_id'] ?? '';
        if (!$video_id) {
            return '';
        }

        return sprintf(
            '<div class="yt-frame"><iframe src="https://www.youtube.com/embed/%s" allowfullscreen></iframe></div>',
            esc_attr($video_id)
        );
    }

    /**
     * Render event share card
     */
    private static function render_event_card(array $media): string {
        $title = $media['title'] ?? 'Event';
        $date = $media['date'] ?? '';
        $loc = $media['location'] ?? ''; // Naming: variable uses 'loc' per registry; array key preserved for data contract
        $tags = $media['tags'] ?? [];

        $event_date = new \DateTime($date);
        $month = strtoupper($event_date->format('M'));
        $day = $event_date->format('d');

        ob_start();
        ?>
        <div class="event-share">
            <div class="ev-date-col">
                <div class="ev-month"><?php echo esc_html($month); ?></div>
                <div class="ev-day"><?php echo esc_html($day); ?></div>
            </div>
            <div class="ev-info-col">
                <div class="ev-title"><?php echo esc_html($title); ?></div>
                <div class="ev-venue"><i class="ri-map-pin-line"></i> <?php echo esc_html($loc); ?></div>
                <div class="ev-tags-row">
                    <?php foreach($tags as $tag) : ?>
                        <span class="ev-tag"><?php echo esc_html($tag); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render ticket/classified card
     */
    private static function render_ticket_card(array $media): string {
        $title = $media['title'] ?? 'Item';
        $image = $media['image'] ?? '';
        $price = $media['price'] ?? '0';
        $meta = $media['meta'] ?? '';

        ob_start();
        ?>
        <article type="ticket">
            <div class="top">
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" class="ticket-img">
                <div class="ticket-deetz">
                    <div>
                        <div class="ticket-event-title"><?php echo esc_html($title); ?></div>
                        <div class="ticket-meta-row"><?php echo esc_html($meta); ?></div>
                    </div>
                    <div class="ticket-price-tag">R$ <?php echo esc_html($price); ?></div>
                </div>
            </div>
            <div class="rip"></div>
            <div class="bottom">
                <div class="barcode"></div>
                <button class="btn-chat-ticket" data-item-id="<?php echo absint($media['id'] ?? 0); ?>">
                    <i class="ri-message-3-line"></i>
                </button>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    /**
     * Render post actions (wow, depoimento, fav, share)
     */
    private static function render_footer(array $post): string {
        $post_id = $post['id'] ?? 0;
        $wow_count = $post['wow_count'] ?? 0;
        $depoimento_count = $post['depoimento_count'] ?? 0;
        $fav_count = $post['fav_count'] ?? 0;

        ob_start();
        ?>
        <div class="post-footer">
            <button class="act-btn wow-btn" data-post-id="<?php echo absint($post_id); ?>" data-action="wow" title="<?php esc_attr_e('Wow', 'apollo-social'); ?>">
                <i class="ri-wow-line"></i>
                <span><?php echo absint($wow_count); ?></span>
            </button>
            <button class="act-btn depoimento-btn" data-post-id="<?php echo absint($post_id); ?>" data-action="depoimento" title="<?php esc_attr_e('Comment', 'apollo-social'); ?>">
                <i class="ri-message-3-line"></i>
                <span><?php echo absint($depoimento_count); ?></span>
            </button>
            <button class="act-btn share-btn" data-post-id="<?php echo absint($post_id); ?>" title="<?php esc_attr_e('Share', 'apollo-social'); ?>">
                <i class="ri-share-box-line"></i>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render comments section
     */
    private static function render_comments(array $post): string {
        $post_id = $post['id'] ?? 0;
        $comments = $post['comments'] ?? [];

        if (empty($comments)) {
            return '';
        }

        ob_start();
        ?>
        <div class="comments-section open">
            <div class="comment-header"><?php esc_html_e('Depoimentos', 'apollo-social'); ?></div>
            <?php foreach($comments as $comment) : ?>
                <div class="comment">
                    <div class="av-xs">
                        <img src="<?php echo esc_url(get_avatar_url($comment['author_id'])); ?>" alt="">
                    </div>
                    <div class="comm-body">
                        <div class="comm-user"><?php echo esc_html($comment['author_name'] ?? 'Anonymous'); ?></div>
                        <p><?php echo wp_kses_post($comment['content']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
