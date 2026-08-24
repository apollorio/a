<?php
/**
 * Single Event — Depoimentos (Comments Timeline)
 *
 * Timeline-style comments display with avatar, author, time ago, text.
 * Includes comment form for logged-in users.
 *
 * Expected variables: $post_id
 *
 * @package Apollo\Event
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! comments_open( $post_id ) && ! get_comments_number( $post_id ) ) {
    return;
}

$depoimentos = get_comments(
    array(
        'post_id' => $post_id,
        'status'  => 'approve',
        'number'  => 10,
        'orderby' => 'comment_date',
        'order'   => 'DESC',
    )
);
?>

<div class="a-eve-single__section a-eve-depoimentos">
    <div class="a-eve-depo__header">
        <h3 class="a-eve-depo__title">
            <i class="ri-chat-3-line"></i>
            <?php esc_html_e( 'Depoimentos', 'apollo-events' ); ?>
        </h3>
        <span class="a-eve-depo__count"><?php echo esc_html( get_comments_number( $post_id ) ); ?></span>
    </div>

    <?php if ( ! empty( $depoimentos ) ) : ?>
        <div class="a-eve-depo__timeline">
            <?php foreach ( $depoimentos as $idx => $depo ) :
                $d_avatar = get_avatar_url( $depo->comment_author_email, array( 'size' => 80 ) );
            ?>
                <div class="a-eve-depo__item<?php echo 0 === $idx ? ' is-latest' : ''; ?>">
                    <div class="a-eve-depo__line">
                        <span class="a-eve-depo__dot"></span>
                        <?php if ( $idx < count( $depoimentos ) - 1 ) : ?>
                            <span class="a-eve-depo__connector"></span>
                        <?php endif; ?>
                    </div>
                    <div class="a-eve-depo__card">
                        <div class="a-eve-depo__card-header">
                            <img src="<?php echo esc_url( $d_avatar ); ?>" alt="" class="a-eve-depo__avatar" />
                            <div class="a-eve-depo__meta">
                                <span class="a-eve-depo__author"><?php echo esc_html( $depo->comment_author ); ?></span>
                                <span class="a-eve-depo__time"><?php echo esc_html( human_time_diff( strtotime( $depo->comment_date ), current_time( 'timestamp' ) ) ); ?></span>
                            </div>
                        </div>
                        <p class="a-eve-depo__text"><?php echo wp_kses_post( $depo->comment_content ); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ( comments_open( $post_id ) && is_user_logged_in() ) : ?>
        <div class="a-eve-depo__form-wrap">
            <?php
            comment_form(
                array(
                    'title_reply'          => '',
                    'comment_notes_before' => '',
                    'comment_notes_after'  => '',
                    'label_submit'         => __( 'Enviar Depoimento', 'apollo-events' ),
                    'comment_field'        => '<div class="a-eve-depo__input-wrap"><textarea name="comment" class="apollo-textarea a-eve-depo__input" placeholder="' . esc_attr__( 'Compartilhe sua experiência...', 'apollo-events' ) . '" rows="3" required></textarea></div>',
                    'class_form'           => 'a-eve-depo__form',
                    'class_submit'         => 'a-eve-depo__submit',
                ),
                $post_id
            );
            ?>
        </div>
    <?php elseif ( ! is_user_logged_in() && comments_open( $post_id ) ) : ?>
        <p class="a-eve-depo__login-cta">
            <a href="<?php echo esc_url( home_url( '/acesso' ) ); ?>">
                <?php esc_html_e( 'Faça login para deixar um depoimento', 'apollo-events' ); ?>
            </a>
        </p>
    <?php endif; ?>
</div>