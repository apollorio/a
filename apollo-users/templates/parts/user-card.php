<?php
/**
 * User Card Part
 *
 * Used by [apollo_user_card] shortcode
 *
 * @package Apollo\Users
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user = get_user_by( 'ID', $user_id );
if ( ! $user ) {
	return;
}

$display_name = get_user_meta( $user_id, '_apollo_social_name', true ) ?: $user->display_name;
$location     = get_user_meta( $user_id, 'user_location', true );
$avatar_url   = apollo_get_user_avatar_url( $user_id, 'medium' );
$profile_url  = apollo_get_profile_url( $user );
?>

<div class="apollo-user-card">
	<a href="<?php echo esc_url( $profile_url ); ?>" class="apollo-user-link">
		<div class="apollo-user-avatar">
			<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>" />
		</div>
		<div class="apollo-user-info">
			<h3 class="apollo-user-name"><?php echo esc_html( $display_name ); ?></h3>
			<p class="apollo-user-username">@<?php echo esc_html( $user->user_login ); ?></p>
			<?php if ( $location ) : ?>
				<p class="apollo-user-location">
					<span class="dashicons dashicons-location"></span>
					<?php echo esc_html( $location ); ?>
				</p>
			<?php endif; ?>
		</div>
	</a>
</div>
