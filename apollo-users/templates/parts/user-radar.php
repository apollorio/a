<?php
/**
 * User Radar Part (for shortcode)
 *
 * Used by [apollo_radar] shortcode
 *
 * @package Apollo\Users
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// $atts available from shortcode
$limit   = isset( $atts['limit'] ) ? intval( $atts['limit'] ) : 24;
$role    = isset( $atts['role'] ) ? sanitize_text_field( $atts['role'] ) : '';
$orderby = isset( $atts['orderby'] ) ? sanitize_text_field( $atts['orderby'] ) : 'registered';
$layout  = isset( $atts['layout'] ) ? sanitize_text_field( $atts['layout'] ) : 'grid';

$args = array(
	'number'  => $limit,
	'orderby' => $orderby,
	'order'   => 'DESC',
);

if ( $role ) {
	$args['role'] = $role;
}

$users = get_users( $args );
?>

<div class="apollo-radar-widget apollo-layout-<?php echo esc_attr( $layout ); ?>">
	<?php if ( empty( $users ) ) : ?>
		<p class="apollo-no-users"><?php echo 'Nenhum User<font style="font-family:system-ui; font-size:110%">::</font>rio encontrado.'; ?></p>
	<?php else : ?>
		<div class="apollo-user-grid">
			<?php
			foreach ( $users as $user ) :
				$user_id      = $user->ID;
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
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
