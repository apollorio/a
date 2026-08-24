<?php
/**
 * Track card — registration against apollo-core's Card Contract.
 *
 * THE FIRST CONSUMER OF apollo_card_register(). The contract shipped with zero
 * consumers on purpose — a contract proves itself on the second surface, not the
 * first — and this is that second surface: the same card now renders on /casa,
 * on /tracks and on /dj/{id}, from one file, with its CSS carried by the
 * contract's print-once ledger.
 *
 * That ledger is the part that matters. `.accom-*` styles were never loaded on
 * /anuncios because a template stopped linking a stylesheet and nothing noticed;
 * a card that brings its own styles cannot be deployed unstyled.
 *
 * The renderer lives in apollo-templates (it is markup), the registration lives
 * here (apollo-djs owns `track`), and the data comes from apollo_track_card_data().
 * Three files, three concerns, one card.
 *
 * @package Apollo\DJs
 * @since   1.0.8
 * @see     apollo-core/includes/card-contract.php
 * @see     apollo-templates/templates/template-parts/track/card.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_track_render_card' ) ) {
	/**
	 * Render one track card.
	 *
	 * @param int                 $track_id Track post ID, or 0 for a placeholder.
	 * @param array<string,mixed> $args     variant, placeholder, cover.
	 * @return string
	 */
	function apollo_track_render_card( int $track_id, array $args = array() ): string {
		$part = apollo_track_card_part_path();
		if ( '' === $part ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'variant'     => 'grid',
				'placeholder' => false,
				'cover'       => '',
			)
		);

		$is_placeholder = ! empty( $args['placeholder'] );

		if ( $is_placeholder ) {
			/*
			 * Pre-launch state: no track behind the card, only a cover. Rendered
			 * through the SAME part so the locked design cannot diverge between
			 * the real and simulated paths — which is exactly how it diverged
			 * before, as two copies in one file.
			 */
			$data = array(
				'id'      => 0,
				'cover'   => (string) $args['cover'],
				'preview' => array(),
			);
		} else {
			if ( $track_id <= 0 || 'track' !== get_post_type( $track_id ) ) {
				return '';
			}
			$data = apollo_track_card_data( $track_id );
		}

		$variant = (string) $args['variant'];

		ob_start();
		include $part;
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'apollo_track_card_part_path' ) ) {
	/**
	 * Locate the card part, theme-overridable.
	 *
	 * A theme replaces the card by shipping the same filename under its own
	 * apollo-templates/ tree — the override shape apollo-events and apollo-djs
	 * already use for their cells.
	 *
	 * @return string Absolute path, or '' when absent.
	 */
	function apollo_track_card_part_path(): string {
		$rel = 'templates/template-parts/track/card.php';

		$theme = locate_template( array( 'apollo-templates/track/card.php' ) );
		if ( $theme ) {
			return $theme;
		}

		if ( defined( 'APOLLO_TEMPLATES_DIR' ) && is_readable( APOLLO_TEMPLATES_DIR . $rel ) ) {
			return APOLLO_TEMPLATES_DIR . $rel;
		}

		return '';
	}
}

if ( ! function_exists( 'apollo_track_card_styles' ) ) {
	/**
	 * The card's own CSS, emitted once per request by the contract's ledger.
	 *
	 * ONLY THE RAIL AND VARIANT RULES LIVE HERE. The `.nh-track-*` base styling —
	 * the artwork, the rings, the blur, the ● ● ● locked labels — already ships
	 * in the /casa stylesheet and is UNTOUCHED. Re-declaring it here would give
	 * those selectors two owners, which is the cardinal sin this ecosystem keeps
	 * paying for. What is added is only what did not exist before: the horizontal
	 * rail and the preview-playing state.
	 *
	 * @return string
	 */
	function apollo_track_card_styles(): string {
		return <<<CSS
/* Horizontal rail — /casa. The card itself is unchanged; only its container
   is new. scroll-snap keeps a card edge aligned after a flick. */
.nh-tracks-rail{
  display:flex;gap:clamp(12px,2vw,20px);
  overflow-x:auto;overscroll-behavior-x:contain;
  scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;
  padding-block:4px;margin-inline:calc(var(--nh-gutter, 0px) * -1);
  padding-inline:var(--nh-gutter, 0px);
  scrollbar-width:none;
}
.nh-tracks-rail::-webkit-scrollbar{display:none;}
.nh-tracks-rail > .nh-track-card{
  flex:0 0 auto;scroll-snap-align:start;
  width:clamp(150px,42vw,196px);
}
@media (min-width:900px){ .nh-tracks-rail > .nh-track-card{ width:196px; } }

/* A card that is currently previewing. Deliberately quiet — this sits inside a
   rail of 15 and a loud state would strobe as the user scrubs through them. */
.nh-track-card.is-playing .nh-track-play-btn i::before{ content:"\\ef19"; }
.nh-track-card.is-playing .nh-track-ring{ animation:nh-track-spin 3.2s linear infinite; }
@keyframes nh-track-spin{ to{ transform:rotate(360deg); } }

@media (prefers-reduced-motion: reduce){
  .nh-tracks-rail{ scroll-behavior:auto; }
  .nh-track-card.is-playing .nh-track-ring{ animation:none; }
}
CSS;
	}
}

if ( ! function_exists( 'apollo_track_register_card' ) ) {
	/**
	 * Declare the track card.
	 *
	 * @return void
	 */
	function apollo_track_register_card(): void {
		if ( ! function_exists( 'apollo_card_register' ) ) {
			return; // Contract absent — surfaces fall back to their own markup.
		}

		apollo_card_register(
			'track',
			array(
				'renderer'  => 'apollo_track_render_card',
				'styles'    => 'apollo_track_card_styles',
				'post_type' => 'track',
				'owner'     => 'apollo-djs',
				'variants'  => array( 'rail', 'grid', 'compact' ),
			)
		);
	}
}
add_action( 'init', 'apollo_track_register_card', 20 );
