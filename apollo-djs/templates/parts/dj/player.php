<?php
/**
 * Template Part: DJ Single - Vinyl Player
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;
if ( empty( $sc_embed_url ) ) {
	return;
}
?>
<section class="dj-player-block" id="djPlayerBlock">
	<div>
		<div class="dj-player-title"><?php esc_html_e( 'Feature set para escuta', 'apollo-djs' ); ?></div>
		<?php if ( ! empty( $dj_track_title ) ) : ?>
		<div class="dj-player-sub" id="track-title"><?php echo esc_html( $dj_track_title ); ?></div>
		<?php endif; ?>
	</div>

	<main class="vinyl-zone">
		<div class="vinyl-player is-paused" id="vinylPlayer" role="button" aria-label="<?php esc_attr_e( 'Play / Pause set', 'apollo-djs' ); ?>" tabindex="0">
			<div class="vinyl-shadow"></div>
			<div class="vinyl-disc">
				<div class="vinyl-beam"></div>
				<div class="vinyl-rings"></div>
				<div class="vinyl-label">
					<div class="vinyl-label-text" id="vinylLabelText"><?php echo wp_kses_post( $dj_name_formatted ); ?></div>
				</div>
				<div class="vinyl-hole"></div>
			</div>
			<div class="tonearm">
				<div class="tonearm-base"></div>
				<div class="tonearm-shaft"></div>
				<div class="tonearm-head"></div>
			</div>
		</div>
	</main>

	<p class="now-playing">
		<?php printf( esc_html__( 'Set de referência em destaque no %s.', 'apollo-djs' ), '<strong>SoundCloud</strong>' ); ?>
	</p>

	<iframe id="scPlayer" class="dj-sc-player-hidden" scrolling="no" frameborder="no" allow="autoplay"
		src="<?php echo esc_url( $sc_embed_url ); ?>" title="SoundCloud Player"></iframe>

	<div class="player-cta-row">
		<button class="btn-player-main" id="vinylToggle" type="button">
			<i class="ri-play-fill" id="vinylIcon"></i>
			<span><?php esc_html_e( 'Play / Pause set', 'apollo-djs' ); ?></span>
		</button>
		<p class="player-note"><?php esc_html_e( 'Contato e condições completas no media kit e rider técnico.', 'apollo-djs' ); ?></p>
	</div>
</section>
