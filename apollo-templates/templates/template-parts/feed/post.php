<?php
/**
 * Feed — single post card (mockup: .fd-post).
 *
 * Vocabulary is law here: the reaction is a WOW, never a "like"; the comment
 * thread is DEPOIMENTOS, never "comments" (registry 15-conventions).
 *
 * Expects: $post_item (array from the feed provider).
 *
 * @package Apollo\Templates
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( empty( $post_item ) || ! is_array( $post_item ) ) { return; }

$a       = (array) ( $post_item['author'] ?? array() );
$eng     = (array) ( $post_item['engagement'] ?? array() );
$wow_on  = ! empty( $eng['wowActive'] );
$wow_n   = (int) ( $eng['wows'] ?? 0 );
$depo_n  = (int) ( $eng['comments'] ?? 0 );
?>
<article class="fd-post" data-post="<?php echo esc_attr( (string) ( $post_item['id'] ?? '' ) ); ?>">
    <div class="fd-head">
        <span class="avt"><img src="<?php echo esc_url( (string) ( $a['avatar'] ?? '' ) ); ?>" alt="" loading="lazy" /></span>
        <div class="fd-who">
            <b><?php echo esc_html( (string) ( $a['name'] ?? __( 'Alguém da cena', 'apollo-templates' ) ) ); ?></b>
            <span><?php echo esc_html( trim( (string) ( $a['handle'] ?? '' ) . ' · ' . (string) ( $post_item['time'] ?? '' ), ' ·' ) ); ?></span>
        </div>
        <?php if ( ! empty( $a['badge'] ) ) : ?>
            <span class="fd-badge"><?php echo esc_html( (string) $a['badge'] ); ?></span>
        <?php endif; ?>
    </div>

    <?php if ( ! empty( $post_item['text'] ) ) : ?>
        <p class="fd-txt"><?php echo esc_html( (string) $post_item['text'] ); ?></p>
    <?php endif; ?>

    <?php if ( ! empty( $post_item['image'] ) ) : ?>
        <div class="fd-media"><img src="<?php echo esc_url( (string) $post_item['image'] ); ?>" alt="" loading="lazy" /></div>
    <?php endif; ?>

    <div class="fd-foot">
        <button type="button" class="fd-wow<?php echo $wow_on ? ' is-active' : ''; ?>" data-wow
            <?php echo is_user_logged_in() ? '' : 'data-auth-required'; ?>>
            <i class="ri-flashlight-<?php echo $wow_on ? 'fill' : 'line'; ?>"></i><span data-wow-n><?php echo esc_html( (string) $wow_n ); ?></span> wow
        </button>
        <span class="fd-dep">
            <i class="ri-chat-quote-line"></i><?php
            printf(
                esc_html( _n( '%d depoimento', '%d depoimentos', $depo_n, 'apollo-templates' ) ),
                (int) $depo_n
            );
            ?>
        </span>
    </div>
</article>
