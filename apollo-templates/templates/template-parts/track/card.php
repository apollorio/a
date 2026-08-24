<?php
/**
 * Track card — THE track card. One owner, every surface.
 *
 * ── THE LOCKED-GUEST DESIGN LIVES HERE, UNCHANGED ────────────────────────────
 *
 * This markup was lifted verbatim out of new-home/tracks.php. Every class, every
 * ring, every ● ● ● and every aria attribute is the same. Nothing was redesigned
 * and nothing was re-tuned — the extraction exists so the design can be REUSED,
 * which is the opposite of replacing it.
 *
 * The guest treatment, exactly:
 *
 *   .nh-track-card.is-guest + data-auth-required + aria-disabled="true"
 *     .nh-track-blur              duplicate cover, blurred, aria-hidden
 *     .nh-track-image             empty alt + aria-hidden
 *     .nh-track-ring ×2           vinyl rings (one --outer)
 *     .nh-track-play-btn.is-locked  disabled, ri-lock-2-line
 *     .nh-track-locked-lbl ×3     ● ● ● for title, artist and meta
 *
 * WHY IT WAS EXTRACTED
 * --------------------
 * new-home/tracks.php contained this markup TWICE — once for real tracks and
 * once for the simulated placeholders — ~55 duplicated lines differing only in
 * where the data came from. Any change to the locked design had to be made in
 * both, and the second copy is where it would have been forgotten. That is the
 * same defect that produced four accommodation cards and five "Out Now"
 * sections.
 *
 * Now: one part, both states, rendered through apollo_card_render( 'track', … ).
 *
 * WHERE IT IS USED
 * ----------------
 *   rail     /casa horizontal scroller (15 newest)
 *   grid     /tracks archive
 *   compact  /dj/{id} Out Now block
 *
 * Each is a VARIANT of this file — never a copy.
 *
 * @package Apollo\Templates
 * @since   1.5.3
 *
 * @var array<string,mixed> $data    From apollo_track_card_data(), or a
 *                                   placeholder shape for the pre-launch state.
 * @var string              $variant rail|grid|compact
 * @var bool                $is_placeholder Simulated card, no real track behind it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data           = isset( $data ) && is_array( $data ) ? $data : array();
$variant        = isset( $variant ) ? (string) $variant : 'grid';
$is_placeholder = ! empty( $is_placeholder );

$is_logged_in = is_user_logged_in();
$card_state   = $is_logged_in ? 'is-member' : 'is-guest';

$cover    = (string) ( $data['cover'] ?? '' );
$title    = (string) ( $data['title'] ?? '' );
$artists  = (string) ( $data['artists'] ?? '' );
$genre    = (string) ( $data['genre'] ?? '' );
$duration = (string) ( $data['duration'] ?? '' );
$url      = (string) ( $data['url'] ?? '' );
$preview  = is_array( $data['preview'] ?? null ) ? $data['preview'] : array();

/*
 * A preview is a MEMBER affordance. A guest gets the locked design in its place
 * — that is the existing rule, not a new one, and it is why the source is never
 * emitted into guest markup at all rather than merely hidden with CSS.
 */
$has_preview = $is_logged_in
	&& ! $is_placeholder
	&& ! empty( $preview['provider'] )
	&& ! empty( $preview['src'] );
?>
<article
	class="nh-track-card reveal-up ai <?php echo esc_attr( $card_state ); ?><?php echo esc_attr( $variant ? ' nh-track-card--' . $variant : '' ); ?>"
	<?php if ( ! $is_placeholder && ! empty( $data['id'] ) ) : ?>
	data-track-id="<?php echo esc_attr( (string) $data['id'] ); ?>"
	<?php endif; ?>
	<?php if ( $has_preview ) : ?>
	data-preview-provider="<?php echo esc_attr( (string) $preview['provider'] ); ?>"
	data-preview-src="<?php echo esc_url( (string) $preview['src'] ); ?>"
	data-preview-mode="<?php echo esc_attr( (string) $preview['mode'] ); ?>"
	data-preview-start="<?php echo esc_attr( (string) (int) $preview['start'] ); ?>"
	data-preview-seconds="<?php echo esc_attr( (string) (int) $preview['seconds'] ); ?>"
	<?php endif; ?>
	<?php echo $is_logged_in ? '' : 'data-auth-required aria-disabled="true"'; ?>
>
	<div class="nh-track-artwork">
		<img class="nh-track-blur" src="<?php echo esc_url( $cover ); ?>" alt="" loading="lazy" aria-hidden="true" />
		<img
			class="nh-track-image"
			src="<?php echo esc_url( $cover ); ?>"
			alt="<?php echo $is_logged_in ? esc_attr( $is_placeholder ? __( 'Coming soon', 'apollo-templates' ) : $title ) : ''; ?>"
			loading="lazy"
			<?php echo $is_logged_in ? '' : 'aria-hidden="true"'; ?>
		/>
		<div class="nh-track-ring" aria-hidden="true"></div>
		<div class="nh-track-ring nh-track-ring--outer" aria-hidden="true"></div>
		<div class="nh-track-play-overlay" aria-hidden="true">
			<?php if ( $has_preview ) : ?>
				<?php /* Member with a preview — the card itself is the player control. */ ?>
				<button
					class="nh-track-play-btn nh-track-preview-btn"
					type="button"
					data-track-preview
					aria-label="<?php echo esc_attr( sprintf( __( 'Ouvir prévia de %s', 'apollo-templates' ), $title ) ); ?>"
				>
					<i class="ri-play-fill"></i>
				</button>
			<?php elseif ( $is_logged_in && ! $is_placeholder && '' !== $url ) : ?>
				<?php /* Member, no preview — out to the release, exactly as before. */ ?>
				<a
					class="nh-track-play-btn"
					href="<?php echo esc_url( $url ); ?>"
					target="_blank" rel="noopener"
					aria-label="<?php echo esc_attr( sprintf( __( 'Ouvir %s', 'apollo-templates' ), $title ) ); ?>"
				>
					<i class="ri-play-fill"></i>
				</a>
			<?php else : ?>
				<?php /* Guest, or member with nothing to play — the locked design. */ ?>
				<button
					class="nh-track-play-btn<?php echo $is_logged_in ? '' : ' is-locked'; ?>"
					type="button"
					disabled
					aria-label="<?php echo $is_logged_in
						? esc_attr__( 'Em breve', 'apollo-templates' )
						: esc_attr__( 'Entre para ouvir', 'apollo-templates' ); ?>"
				>
					<i class="<?php echo $is_logged_in ? 'ri-lock-line' : 'ri-lock-2-line'; ?>"></i>
				</button>
			<?php endif; ?>
		</div>
	</div>
	<div class="nh-track-info">
		<?php if ( $is_logged_in ) : ?>
			<h4><?php echo esc_html( $is_placeholder ? __( 'Em breve', 'apollo-templates' ) : $title ); ?></h4>
			<div class="nh-track-artist"><?php echo esc_html( $is_placeholder ? __( 'Coming soon', 'apollo-templates' ) : $artists ); ?></div>
			<div class="nh-track-meta">
				<span><i class="ri-headphone-line"></i><?php echo esc_html( $is_placeholder ? __( 'Electronic', 'apollo-templates' ) : $genre ); ?></span>
				<?php if ( $is_placeholder ) : ?>
				<span><i class="ri-time-line"></i><?php esc_html_e( 'Em breve', 'apollo-templates' ); ?></span>
				<?php elseif ( '' !== $duration ) : ?>
				<span><i class="ri-time-line"></i><?php echo esc_html( $duration ); ?></span>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<h4 class="nh-track-locked-lbl">● ● ●</h4>
			<div class="nh-track-artist nh-track-locked-lbl">● ● ●</div>
			<div class="nh-track-meta nh-track-locked-lbl">
				<span><i class="ri-lock-2-line"></i>● ● ●</span>
			</div>
		<?php endif; ?>
	</div>
</article>
