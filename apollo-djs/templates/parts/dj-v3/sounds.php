<?php
/**
 * DJ v3 Partial: Sounds (Sonoridades)
 *
 * Spotify-style horizontal track cards grid.
 * Renders DJ music platform links + taxonomy sound genres.
 * Variables: $dj_id, $dj_sounds, $all_links.
 *
 * Meta: _dj_soundcloud, _dj_spotify, _dj_youtube, _dj_beatport, etc.
 * Taxonomy: sound (slug: som)
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;

$music_links = $all_links['music'] ?? array();

// Authored track list (repeater: title / meta / url)
$dj_tracks = get_post_meta( $dj_id, '_dj_tracks', true );
if ( ! is_array( $dj_tracks ) ) {
	$dj_tracks = array();
}

// Primary music platform link for "View" CTA
$primary_platform = '';
$primary_label    = '';
foreach ( array( 'beatport', 'spotify', 'soundcloud' ) as $key ) {
	if ( ! empty( $music_links[ $key ] ) ) {
		$primary_platform = $music_links[ $key ]['url'];
		$primary_label    = $music_links[ $key ]['label'];
		break;
	}
}

if ( empty( $dj_sounds ) && empty( $music_links ) && empty( $dj_tracks ) ) {
	return;
}
?>

<div class="dj-section-full-wrap" id="sonoridades">
	<div class="dj-section-full-head">
		<div class="dj-section-head">
			<div>
				<span class="dj-section-label"><?php esc_html_e( 'Sonoridades', 'apollo-djs' ); ?></span>
				<h2><?php esc_html_e( 'DJ Sounds', 'apollo-djs' ); ?></h2>
			</div>
			<?php if ( $primary_platform ) : ?>
				<a href="<?php echo esc_url( $primary_platform ); ?>" class="dj-section-link" target="_blank" rel="noopener">
					<?php
					/* translators: %s: platform name */
					printf( esc_html__( 'View %s', 'apollo-djs' ), esc_html( $primary_label ) );
					?>
					<i class="ri-arrow-right-s-line"></i>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( ! empty( $dj_sounds ) ) : ?>
		<div class="dj-sounds-tags" style="padding: 0 var(--dj-pad-x, 24px); margin-bottom: 20px; display: flex; gap: 8px; flex-wrap: wrap;">
			<?php foreach ( $dj_sounds as $genre ) : ?>
				<span class="dj-sound-tag" style="
					font-family: var(--dj-ff-mono, monospace);
					font-size: 0.68rem;
					text-transform: uppercase;
					letter-spacing: 0.08em;
					padding: 4px 12px;
					border: 1px solid var(--dj-border, rgba(var(--rgb-d),0.1));
					border-radius: 100px;
					color: var(--dj-txt-muted);
				"><?php echo esc_html( $genre ); ?></span>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $dj_tracks ) ) : ?>
		<div class="nh-tracks-grid" role="list">
			<?php
			foreach ( $dj_tracks as $i => $track ) :
				$t_title = (string) ( $track['title'] ?? '' );
				$t_meta  = (string) ( $track['meta'] ?? '' );
				$t_url   = (string) ( $track['url'] ?? '' );
				if ( '' === $t_title ) {
					continue;
				}
				?>
				<?php if ( '' !== $t_url ) : ?>
					<a href="<?php echo esc_url( $t_url ); ?>" target="_blank" rel="noopener" class="nh-track-card" role="listitem" style="text-decoration:none; color:inherit;">
				<?php else : ?>
					<div class="nh-track-card" role="listitem">
				<?php endif; ?>
					<div class="nh-track-artwork" style="display:flex; align-items:center; justify-content:center; color:var(--dj-primary);">
						<span style="font-family:var(--dj-ff-mono,monospace);font-size:0.95rem;"><?php echo esc_html( sprintf( '%02d', (int) $i + 1 ) ); ?></span>
					</div>
					<div class="nh-track-info">
						<h4><?php echo esc_html( $t_title ); ?></h4>
						<p class="nh-track-artist"><?php echo esc_html( $t_meta ?: $dj_name ); ?></p>
					</div>
					<?php if ( '' !== $t_url ) : ?>
						<i class="ri-play-fill" style="margin-left:auto;font-size:1.2rem;color:var(--dj-primary);"></i>
					</a>
				<?php else : ?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $music_links ) ) : ?>
		<div class="nh-tracks-grid" role="list">
			<?php foreach ( $music_links as $key => $link ) : ?>
				<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener" class="nh-track-card" role="listitem" style="text-decoration:none; color:inherit;">
					<div class="nh-track-artwork" style="display:flex; align-items:center; justify-content:center; font-size:2rem; color:var(--dj-primary);">
						<i class="<?php echo esc_attr( $link['icon'] ); ?>"></i>
					</div>
					<div class="nh-track-info">
						<h4><?php echo esc_html( $link['label'] ); ?></h4>
						<p class="nh-track-artist"><?php echo esc_html( $dj_name ); ?></p>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
