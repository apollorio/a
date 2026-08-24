<?php
/**
 * Profile Display Part
 *
 * Used by [apollo_profile] shortcode
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
$bio          = get_user_meta( $user_id, '_apollo_bio', true );
$location     = get_user_meta( $user_id, 'user_location', true );
$avatar_url   = apollo_get_user_avatar_url( $user_id, 'medium' );
$profile_url  = apollo_get_profile_url( $user );
?>

<div class="apollo-profile-widget">
	<div class="apollo-profile-widget-header">
		<img src="<?php echo esc_url( $avatar_url ); ?>"
			alt="<?php echo esc_attr( $display_name ); ?>"
			class="apollo-widget-avatar" />
		<div class="apollo-widget-info">
			<h3><a href="<?php echo esc_url( $profile_url ); ?>"><?php echo esc_html( $display_name ); ?></a></h3>
			<span class="apollo-widget-username">@<?php echo esc_html( $user->user_login ); ?></span>
		</div>
	</div>

	<?php if ( $bio ) : ?>
		<p class="apollo-widget-bio"><?php echo esc_html( wp_trim_words( $bio, 20 ) ); ?></p>
	<?php endif; ?>

	<?php if ( $location ) : ?>
		<p class="apollo-widget-location">
			<span class="dashicons dashicons-location"></span>
			<?php echo esc_html( $location ); ?>
		</p>
	<?php endif; ?>

	<a href="<?php echo esc_url( $profile_url ); ?>" class="apollo-btn apollo-btn-primary apollo-btn-small">
		<?php esc_html_e( 'Ver Perfil' ); ?>
	</a>
</div>
