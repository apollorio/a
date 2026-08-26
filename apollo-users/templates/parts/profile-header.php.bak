<?php
/**
 * Profile Header Part
 *
 * @package Apollo\Users
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Variables available: $user, $user_id, $is_own_profile, $display_name, $bio, $location, $instagram, $website, $avatar_url, $cover_url, $show_email
?>

<!-- HEADER -->
<header class="a-user-profile-header">

    <!-- Edit Toggle Button (only for own profile) -->
    <?php if ( $is_own_profile ) : ?>
        <button class="a-user-btn-reset a-user-edit-toggle" id="edit-toggle-btn" onclick="toggleEditMode()">
            Edit Profile
        </button>
    <?php endif; ?>

    <div class="a-user-avatar-frame">
        <!-- UPLOAD ICON (Edit Only) -->
        <div class="a-user-avatar-upload edit-only">
            <i class="ri-upload-2-line"></i>
        </div>
        <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>" class="a-user-avatar-img">
    </div>

    <div class="a-user-info">
        <!-- Name Title Block -->
        <h1 class="a-user-name view-only"><?php echo esc_html( $display_name ); ?></h1>
        <input type="text" class="a-user-input-edit a-user-name-input edit-only" value="<?php echo esc_attr( $display_name ); ?>">

        <!-- UserID Block -->
        <span class="a-user-handle" style="display: block; margin-bottom: 8px;">@<?php echo esc_html( $user->user_login ); ?></span>

        <div class="a-user-meta-row" style="margin-top: 0;">
            <!-- Nucleo tags will be added here -->
            <span class="a-user-nucleo-tag">Nucleo Rara</span>
            <span class="a-user-nucleo-tag">Nucleo Selvagem</span>
        </div>

        <!-- Bio Block -->
        <p class="a-user-bio view-only"><?php echo wp_kses_post( nl2br( esc_html( $bio ?: 'Nenhuma bio ainda.' ) ) ); ?></p>
        <textarea class="a-user-input-edit a-user-bio-input edit-only"><?php echo esc_html( $bio ); ?></textarea>

        <!-- Hub Link -->
        <a href="<?php echo esc_url( home_url( '/hub/' . $user->user_login ) ); ?>" target="_blank" class="a-user-hub-link" style="font-size:11px; color:rgb(244, 95, 0)">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:14px; height:14px;"><path d="M21 17C21 19.2091 19.2091 21 17 21C14.7909 21 13 19.2091 13 17C13 14.7909 14.7909 13 17 13C19.2091 13 21 14.7909 21 17ZM11 7C11 9.20914 9.20914 11 7 11C4.79086 11 3 9.20914 3 7C3 4.79086 4.79086 3 7 3C9.20914 3 11 4.79086 11 7ZM21 7C21 9.20914 19.2091 11 17 11C16.2584 11 15.5634 10.7972 14.9678 10.4453L10.4453 14.9678C10.7972 15.5634 11 16.2584 11 17C11 19.2091 9.20914 21 7 21C4.79086 21 3 19.2091 3 17C3 14.7909 4.79086 13 7 13C7.74116 13 8.43593 13.2022 9.03125 13.5537L13.5537 9.03125C13.2022 8.43593 13 7.74116 13 7C13 4.79086 14.7909 3 17 3C19.2091 3 21 4.79086 21 7Z"></path></svg> apollo.rio.br/hub/<?php echo esc_html( $user->user_login ); ?>
        </a>

        <div class="a-user-member-since">
            [ Member since <?php echo date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ); ?> ]
        </div>
    </div>

    <!-- LISTING OF PUBLICATIONS (MOVED TO BOTTOM OF HEADER, SPANNING FULL WIDTH) -->
    <div class="a-user-pub-section">
        <h4 class="a-user-pub-heading">Últimas Publicações e Notícias</h4>
        <div class="a-user-pub-list">
            <?php
            $user_posts = get_posts( [
                'author'         => $user_id,
                'posts_per_page' => 5,
                'post_status'    => 'publish',
            ] );

            if ( $user_posts ) :
                foreach ( $user_posts as $post ) :
            ?>
                <a href="<?php echo get_permalink( $post ); ?>" class="a-user-pub-item">
                    <span class="a-user-pub-title"><?php echo esc_html( $post->post_title ); ?></span>
                    <div class="a-user-pub-meta">
                        <span class="a-user-pub-tag">Post</span> • <?php echo esc_html( get_the_author_meta( 'display_name', $post->post_author ) ); ?> • <?php echo human_time_diff( get_the_time( 'U', $post ), current_time( 'timestamp' ) ) . ' atrás'; ?>
                    </div>
                </a>
            <?php
                endforeach;
            else :
            ?>
                <p class="a-user-pub-empty">Nenhuma publicação ainda.</p>
            <?php endif; ?>
        </div>
    </div>
</header>
