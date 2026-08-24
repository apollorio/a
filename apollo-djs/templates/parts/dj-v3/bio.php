<?php
/**
 * DJ v3 Partial: Bio + Timeline
 *
 * Two-column layout: sticky photo + social icons | bio text + timeline.
 * Variables: $dj_id, $dj_name, $dj_photo_url, $dj_bio_full, $all_links.
 *
 * Meta: _dj_bio, _dj_image, _dj_instagram, _dj_soundcloud, _dj_spotify, _dj_twitter,
 *       _dj_facebook, _dj_tiktok, _dj_youtube.
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;

$social_links = $all_links['social'] ?? array();
$music_links  = $all_links['music'] ?? array();

// Merge social + top music links for sidebar icons
$sidebar_icons = array();
foreach ( $social_links as $link ) {
	$sidebar_icons[] = $link;
}
// Add first 2 music links as sidebar icons
$music_for_sidebar = array_slice( $music_links, 0, 2 );
foreach ( $music_for_sidebar as $link ) {
	$sidebar_icons[] = $link;
}

if ( empty( $dj_bio_full ) ) {
	return;
}

// Parse bio content for timeline sections
// Convention: H3 headings in bio become timeline nodes
$bio_has_headings = preg_match( '/<h[23]/', $dj_bio_full );
?>

<section class="dj-section" id="bio">
	<div class="dj-bio-wrap">

		<!-- Photo Column -->
		<div class="dj-bio-photo-col">
			<div class="dj-bio-photo-sticky">
				<div class="dj-bio-photo">
					<img
						src="<?php echo esc_url( $dj_photo_url ); ?>"
						alt="<?php echo esc_attr( $dj_name ); ?>"
						loading="lazy"
						width="600"
						height="750"
					>
				</div>
				<?php if ( ! empty( $sidebar_icons ) ) : ?>
					<div class="dj-bio-social">
						<?php foreach ( $sidebar_icons as $link ) : ?>
							<a
								href="<?php echo esc_url( $link['url'] ); ?>"
								target="_blank"
								rel="noopener"
								aria-label="<?php echo esc_attr( $link['label'] ); ?>"
							>
								<i class="<?php echo esc_attr( $link['icon'] ); ?>"></i>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Bio / Timeline Column -->
		<div style="flex:1; min-width:0;">
			<span class="dj-section-label"><?php esc_html_e( 'Sobre', 'apollo-djs' ); ?></span>
			<h2 style="font-size:clamp(1.6rem,4vw,2.6rem); font-weight:800; margin-bottom:32px; color:var(--dj-txt-color-hover);">
				<?php echo esc_html( $dj_name ); ?>
			</h2>

			<div class="dj-bio-content" style="color:var(--dj-txt-muted); line-height:1.75; font-size:0.95rem;">
				<?php echo wp_kses_post( $dj_bio_full ); ?>
			</div>

			<?php
			/**
			 * Hook: apollo/djs/after_bio
			 *
			 * Allows plugins to inject content after bio (e.g. apollo-wow reactions, apollo-fav button).
			 *
			 * @param int $dj_id
			 */
			do_action( 'apollo/djs/after_bio', $dj_id );
			?>
		</div>
	</div>
</section>
