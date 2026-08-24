<?php
/**
 * The player markup — Apollo's UI, SoundCloud's transport.
 *
 * @package Apollo\SoundCloud
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'apollo_soundcloud_player' ) ) {
	/**
	 * Render a SoundCloud player.
	 *
	 * THE ONLY PUBLIC ENTRY POINT. Every surface calls this; none of them knows
	 * whether the transport is a Widget iframe or (one day) a direct stream.
	 *
	 *     echo apollo_soundcloud_player( $url, array( 'mode' => 'preview' ) );
	 *
	 * @param string              $url  SoundCloud URL.
	 * @param array<string,mixed> $args mode, variant, start_pct, end_pct,
	 *                                  autoplay, title, author, artwork, lazy_meta.
	 * @return string Markup, or '' when the URL is not playable.
	 */
	function apollo_soundcloud_player( string $url, array $args = array() ): string {
		$r = apollo_soundcloud_resolve( $url );
		if ( '' === $r['kind'] ) {
			return ''; // Fail-closed: never a broken iframe.
		}

		$args = wp_parse_args(
			$args,
			array(
				'mode'      => 'preview',   // preview | full
				'variant'   => 'card',      // card | inline | vinyl | bar
				'start_pct' => APOLLO_SC_PREVIEW_START_PCT,
				'end_pct'   => APOLLO_SC_PREVIEW_END_PCT,
				'autoplay'  => false,
				'title'     => '',
				'author'    => '',
				'artwork'   => '',
				/*
				 * lazy_meta defaults TRUE: no network call during render. A rail
				 * of fifteen players must not make fifteen blocking requests.
				 * The runtime fetches what it needs after paint; a caller that
				 * already has a title (a track post, say) should just pass it.
				 */
				'lazy_meta' => true,
			)
		);

		$mode = in_array( $args['mode'], array( 'preview', 'full' ), true ) ? $args['mode'] : 'preview';

		// Clamp the window. An inverted or absurd pair must not produce a
		// player that seeks past its own end and hangs.
		$start = max( 0, min( 95, (int) $args['start_pct'] ) );
		$end   = max( $start + 5, min( 100, (int) $args['end_pct'] ) );

		$title   = (string) $args['title'];
		$author  = (string) $args['author'];
		$artwork = (string) $args['artwork'];

		if ( '' === $title && ! $args['lazy_meta'] ) {
			$meta    = apollo_soundcloud_meta( $r['canonical'] );
			$title   = $meta['title'];
			$author  = $meta['author'];
			$artwork = $meta['artwork'];
		}

		wp_enqueue_style( 'apollo-sc' );
		wp_enqueue_script( 'apollo-sc' );

		$id = 'apsc-' . wp_generate_uuid4();

		ob_start();
		?>
<div
	class="apsc apsc--<?php echo esc_attr( (string) $args['variant'] ); ?>"
	id="<?php echo esc_attr( $id ); ?>"
	data-apsc
	data-apsc-mode="<?php echo esc_attr( $mode ); ?>"
	data-apsc-start="<?php echo esc_attr( (string) $start ); ?>"
	data-apsc-end="<?php echo esc_attr( (string) $end ); ?>"
	data-apsc-url="<?php echo esc_url( $r['canonical'] ); ?>"
	<?php echo $args['autoplay'] ? 'data-apsc-autoplay' : ''; ?>
>
	<button type="button" class="apsc-btn" data-apsc-toggle
		aria-label="<?php esc_attr_e( 'Tocar', 'apollo-soundcloud' ); ?>">
		<i class="ri-play-fill" aria-hidden="true"></i>
	</button>

	<div class="apsc-body">
		<div class="apsc-title" data-apsc-title><?php echo esc_html( $title ); ?></div>
		<div class="apsc-author" data-apsc-author><?php echo esc_html( $author ); ?></div>
		<div class="apsc-bar" data-apsc-bar aria-hidden="true">
			<?php
			/*
			 * In preview mode the bar shows the WHOLE track with the preview
			 * window marked, not a 0–100% bar of the excerpt. A listener should
			 * be able to see they are hearing the middle of something longer —
			 * a progress bar that fills completely on a 40% excerpt is a lie.
			 */
			?>
			<span class="apsc-bar-window"
				style="left:<?php echo esc_attr( (string) $start ); ?>%;width:<?php echo esc_attr( (string) ( $end - $start ) ); ?>%"></span>
			<span class="apsc-bar-fill" data-apsc-fill></span>
		</div>
	</div>

	<?php if ( '' !== $artwork ) : ?>
	<img class="apsc-art" src="<?php echo esc_url( $artwork ); ?>" alt="" loading="lazy" />
	<?php endif; ?>

	<?php
	/*
	 * THE TRANSPORT. Not rendered until first play — fifteen iframes on one
	 * page is fifteen SoundCloud connections before anyone clicks anything.
	 * The runtime injects it into this slot on demand, from data-apsc-embed.
	 */
	?>
	<div class="apsc-transport" data-apsc-transport hidden
		data-apsc-embed="<?php echo esc_url( $r['embed_url'] ); ?>"></div>

	<noscript>
		<?php /* No JS: the real embed, visible and usable. Never a dead button. */ ?>
		<iframe
			width="100%" height="120" frameborder="no" scrolling="no" allow="autoplay"
			title="<?php echo esc_attr( $title ? $title : __( 'SoundCloud', 'apollo-soundcloud' ) ); ?>"
			src="<?php echo esc_url( $r['embed_url'] ); ?>"></iframe>
	</noscript>
</div>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'apollo_soundcloud_shortcode' ) ) {
	/**
	 * [apollo_soundcloud url="…" mode="preview"]
	 *
	 * @param array<string,mixed>|string $atts Attributes.
	 * @return string
	 */
	function apollo_soundcloud_shortcode( $atts ): string {
		$a = shortcode_atts(
			array(
				'url'     => '',
				'mode'    => 'preview',
				'variant' => 'card',
				'start'   => APOLLO_SC_PREVIEW_START_PCT,
				'end'     => APOLLO_SC_PREVIEW_END_PCT,
			),
			(array) $atts,
			'apollo_soundcloud'
		);

		return apollo_soundcloud_player(
			(string) $a['url'],
			array(
				'mode'      => (string) $a['mode'],
				'variant'   => (string) $a['variant'],
				'start_pct' => (int) $a['start'],
				'end_pct'   => (int) $a['end'],
			)
		);
	}
}
add_shortcode( 'apollo_soundcloud', 'apollo_soundcloud_shortcode' );
