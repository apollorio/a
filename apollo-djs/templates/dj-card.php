<?php
/**
 * Template: DJ Card (Listing / Grid)
 * Enhanced ShadCN-inspired card for DJ listings
 * Ported from 1212 workspace
 *
 * Expects $post to be set (inside WP Loop or manual setup)
 *
 * @package Apollo\DJs
 * @version 2.0.0
 */

defined( 'ABSPATH' ) || exit;

$dj_id   = get_the_ID();
$dj_name = get_post_meta( $dj_id, '_dj_name', true ) ?: get_the_title( $dj_id );
$dj_url  = get_permalink( $dj_id );

// Avatar — featured image first, then _dj_image meta
$dj_avatar = get_the_post_thumbnail_url( $dj_id, 'medium' );
if ( ! $dj_avatar ) {
	$dj_avatar = get_post_meta( $dj_id, '_dj_image', true );
}
$avatar_size = apply_filters( 'apollo_dj_card_avatar_size', 'xl' );

// Bio
$dj_bio = get_post_meta( $dj_id, '_dj_bio_short', true );
if ( ! $dj_bio ) {
	$dj_bio = wp_trim_words( get_the_excerpt(), 20 );
}

// Genres from 'sound' taxonomy
$sounds      = get_the_terms( $dj_id, 'sound' );
$sound_names = array();
if ( ! is_wp_error( $sounds ) && ! empty( $sounds ) ) {
	$sound_names = wp_list_pluck( array_slice( $sounds, 0, 4 ), 'name' );
}

// Verified badge
$is_verified = (bool) get_post_meta( $dj_id, '_dj_verified', true );

// Social links (display 2 max)
$social_icons = array(
	'_dj_instagram'  => 'ri-instagram-line',
	'_dj_soundcloud' => 'ri-soundcloud-line',
	'_dj_spotify'    => 'ri-spotify-line',
);
$social_links = array();
foreach ( $social_icons as $meta_key => $icon ) {
	$url = get_post_meta( $dj_id, $meta_key, true );
	if ( $url ) {
		$social_links[] = array(
			'url'   => $url,
			'icon'  => $icon,
			'label' => str_replace( array( '_dj_', '_' ), array( '', ' ' ), $meta_key ),
		);
	}
	if ( count( $social_links ) >= 2 ) {
		break;
	}
}

// Upcoming events count
$upcoming_count = 0;
if ( post_type_exists( 'event' ) ) {
	$upcoming_query = new WP_Query(
		array(
			'post_type'      => 'event',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_event_dj_ids',
					'value'   => (string) $dj_id,
					'compare' => 'LIKE',
				),
			),
			'date_query'     => array( array( 'after' => 'now' ) ),
		)
	);
	$upcoming_count = $upcoming_query->found_posts;
	wp_reset_postdata();
}
?>
<article class="ap-card ap-card-dj" data-dj-id="<?php echo esc_attr( $dj_id ); ?>">
	<a href="<?php echo esc_url( $dj_url ); ?>" class="ap-card-dj__link" aria-label="<?php echo esc_attr( sprintf( __( 'Ver perfil de %s', 'apollo-djs' ), $dj_name ) ); ?>">
		<div class="ap-card-dj__avatar ap-avatar-<?php echo esc_attr( $avatar_size ); ?>">
			<?php if ( $dj_avatar ) : ?>
				<img src="<?php echo esc_url( $dj_avatar ); ?>" alt="" loading="lazy" decoding="async">
			<?php else : ?>
				<div class="ap-avatar-placeholder"><i class="ri-disc-line"></i></div>
			<?php endif; ?>
		</div>

		<?php if ( $upcoming_count > 0 ) : ?>
		<span class="ap-card-dj__badge">
			<?php printf( esc_html( _n( '%d evento', '%d eventos', $upcoming_count, 'apollo-djs' ) ), $upcoming_count ); ?>
		</span>
		<?php endif; ?>
	</a>

	<div class="ap-card-dj__body">
		<h3 class="ap-card-dj__name">
			<a href="<?php echo esc_url( $dj_url ); ?>">
				<?php echo esc_html( $dj_name ); ?>
				<?php if ( $is_verified ) : ?>
				<i class="ri-verified-badge-fill ap-verified" title="<?php esc_attr_e( 'Verificado', 'apollo-djs' ); ?>"></i>
				<?php endif; ?>
			</a>
		</h3>

		<?php if ( ! empty( $sound_names ) ) : ?>
		<div class="ap-card-dj__genres">
			<?php foreach ( $sound_names as $sname ) : ?>
				<span class="ap-tag"><?php echo esc_html( $sname ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( $dj_bio ) : ?>
		<p class="ap-card-dj__bio"><?php echo esc_html( $dj_bio ); ?></p>
		<?php endif; ?>

		<div class="ap-card-dj__footer">
			<?php if ( ! empty( $social_links ) ) : ?>
			<div class="ap-card-dj__social">
				<?php foreach ( $social_links as $link ) : ?>
				<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( ucfirst( $link['label'] ) ); ?>">
					<i class="<?php echo esc_attr( $link['icon'] ); ?>"></i>
				</a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<a href="<?php echo esc_url( $dj_url ); ?>" class="ap-card-dj__action">
				<?php esc_html_e( 'Ver Perfil', 'apollo-djs' ); ?> <i class="ri-arrow-right-up-line"></i>
			</a>
		</div>
	</div>
</article>
