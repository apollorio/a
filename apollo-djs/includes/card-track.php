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
				'listen'  => function_exists( 'apollo_track_listen_none' )
					? apollo_track_listen_none()
					: array( 'can_play' => false ),
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
  flex:0 0 120px;scroll-snap-align:start;
  width:120px;height:auto;
}
@media (min-width:1024px){ .nh-tracks-rail > .nh-track-card{ width:128px; flex-basis:128px; height:auto; } }
@media (min-width:1400px){ .nh-tracks-rail > .nh-track-card{ width:132px; flex-basis:132px; height:auto; } }

/* A card that is currently previewing. Deliberately quiet — this sits inside a
   rail of 15 and a loud state would strobe as the user scrubs through them. */
.nh-track-card.is-playing .nh-track-play-btn i::before{ content:"\\ef19"; }
.nh-track-card.is-playing .nh-track-ring{ animation:nh-track-spin 3.2s linear infinite; }
@keyframes nh-track-spin{ to{ transform:rotate(360deg); } }

/* Platform row — hidden until card expands on preview play. Single CSS owner. */
.nh-track-expand{
  max-height:0;opacity:0;overflow:hidden;
  transition:max-height .38s var(--ease-out, ease), opacity .28s ease, margin .28s ease;
  margin-top:0;
}
.nh-track-card.is-expanded .nh-track-expand{
  max-height:44px;opacity:1;margin-top:8px;
}
.nh-track-card.is-expanded{
  z-index:2;
  box-shadow:var(--shadow-lg, 0 8px 24px rgba(0,0,0,.12));
}
.nh-track-card.is-expanded .nh-track-info{ overflow:visible; }
.nh-track-platforms{
  display:flex;align-items:center;justify-content:center;gap:8px;flex-wrap:wrap;
}
.nh-track-plat{
  display:inline-flex;align-items:center;justify-content:center;
  width:24px;height:24px;line-height:1;
  color:var(--ink, #111);opacity:.9;
  text-decoration:none;font-size:16px;font-weight:700;
  transition:opacity .15s ease, transform .15s ease;
}
.nh-track-plat:hover,.nh-track-plat:focus-visible{
  opacity:1;transform:translateY(-1px);outline:none;
}
.nh-track-plat i{ font-weight:700; }

/* Empty cover — centered disc glyph */
.nh-track-artwork--empty{ background:linear-gradient(145deg, var(--onyx-700, #2a2a2a), var(--onyx-800, #1a1a1a)); }
.nh-track-cover-fallback{
  position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
  z-index:3;display:flex;align-items:center;justify-content:center;
  width:50%;height:50%;border-radius:50%;
  color:rgba(255,255,255,.35);font-size:clamp(28px,40%,48px);
  pointer-events:none;
}
.nh-track-artwork[data-track-artwork-trigger]{ cursor:pointer; }

/* Transport is ALWAYS hidden on track cards — never SoundCloud chrome. */
.nh-track-sc-transport,
.nh-track-sc-transport.is-fallback,
.nh-track-card .apsc-transport,
.nh-track-card .apsc.is-fallback .apsc-transport{
  position:absolute !important;width:1px !important;height:1px !important;
  overflow:hidden !important;clip:rect(0,0,0,0) !important;
  opacity:0 !important;pointer-events:none !important;
  display:block !important;margin:0 !important;border:0 !important;
}

.nh-track-loader{
  position:absolute;width:28px;height:28px;border-radius:50%;
  border:2px solid rgba(255,255,255,.22);border-top-color:#fff;
  opacity:0;pointer-events:none;z-index:6;
}
.nh-track-card.is-loading .nh-track-play-overlay{ opacity:1; }
.nh-track-card.is-loading .nh-track-play-btn{ opacity:0; transform:scale(.6); }
.nh-track-card.is-loading .nh-track-loader{
  opacity:1;animation:nh-track-spin .7s linear infinite;
}
.nh-track-card.is-playing .nh-track-play-overlay{ opacity:1; }

@media (prefers-reduced-motion: reduce){
  .nh-tracks-rail{ scroll-behavior:auto; }
  .nh-track-card.is-playing .nh-track-ring{ animation:none; }
  .nh-track-plat{ transition:none; }
  .nh-track-expand{ transition:none; }
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
