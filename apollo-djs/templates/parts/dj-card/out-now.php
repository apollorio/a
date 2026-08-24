<?php
/**
 * @partial out-now
 * @expects $ctx['tracks'] — meta._dj_tracks (repeater)
 * @render  First 5 rows + row 06 "Ver todos" → #outNowLb lightbox (JS)
 * @meta    title    → _dj_tracks[][title]
 *          url      → _dj_tracks[][url]
 *          year     → _dj_tracks[][year]  (ano de postagem)
 *          duration → _dj_tracks[][duration]
 * @display meta line = "{year} · RIO DE JANEIRO · {duration}" (genre hardcoded)
 *
 * Section heading: Out now! (was Sons). Anchor id=#sound for hero CTA.
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

$tracks = $ctx['tracks'] ?? array();
$sc     = (string) ( $ctx['soundcloud'] ?? '' );
$bc     = (string) ( $ctx['bandcamp'] ?? '' );
$sp     = (string) ( $ctx['spotify'] ?? '' );

if ( empty( $tracks ) && ! $sc && ! $bc && ! $sp ) {
	return;
}
?>
<section class="sec-tight" id="sound">
	<div class="wrap">
		<div class="sh">
			<div><span class="lbl"><?php esc_html_e( 'Trabalho selecionado', 'apollo-djs' ); ?></span><h2 class="serif sh-t"><?php esc_html_e( 'Out now!', 'apollo-djs' ); ?></h2></div>
			<div style="display:flex;gap:8px;flex-wrap:wrap;">
				<?php if ( $sc ) : ?>
					<a class="btn btn-line pill" id="scFollow" href="<?php echo esc_url( $sc ); ?>" target="_blank" rel="noopener"><i class="ri-soundcloud-line"></i> SoundCloud</a>
				<?php endif; ?>
				<?php if ( $bc ) : ?>
					<a class="btn btn-line pill" id="bcFollow" href="<?php echo esc_url( $bc ); ?>" target="_blank" rel="noopener"><i class="ri-bandcamp-line"></i> Bandcamp</a>
				<?php endif; ?>
				<?php if ( $sp ) : ?>
					<a class="btn btn-line pill" id="spFollow" href="<?php echo esc_url( $sp ); ?>" target="_blank" rel="noopener"><i class="ri-spotify-line"></i> Spotify</a>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( ! empty( $tracks ) ) : ?>
			<div id="trkList" class="rule-draw"></div>
			<div class="sc-shelf" id="scShelf"><iframe id="scPlayer" title="<?php esc_attr_e( 'Player SoundCloud', 'apollo-djs' ); ?>" allow="autoplay" loading="lazy"></iframe></div>
		<?php endif; ?>
	</div>
</section>
