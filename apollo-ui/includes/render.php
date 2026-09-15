<?php
/**
 * Apollo UI — card rendering.
 *
 * One markup shape, six looks. The difference between type-1 and type-4 is a
 * class and a CSS custom property, not a different template. That is the whole
 * reason this plugin exists: 30+ card files across 12 plugins each rebuilt the
 * same <article><figure><h3> and then each shipped its own CSS.
 *
 * Every card carries the lightbox contract attributes:
 *   data-apollo-open="{id}"  data-apollo-cpt="{post_type}"
 * which includes/surfaces.php and assets/js/apollo-ui.js turn into a
 * full-viewport overlay. The markup does not care whether the lightbox is
 * enabled; if it is off, the <a href> is a normal link and the card still works.
 * That ordering — real link first, JS enhancement second — is deliberate.
 *
 * @package Apollo\UI
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render one card in a given style.
 *
 * @param string               $type    Style key (type-1 … type-6).
 * @param int                  $post_id Post id.
 * @param array<string,mixed>  $args    variant, class, lightbox (bool|null).
 */
function apollo_ui_card( string $type, int $post_id, array $args = array() ): string {
	if ( ! apollo_ui_is_type( $type ) ) {
		$type = 'type-1';
	}
	$post = get_post( $post_id );
	if ( ! $post || 'publish' !== $post->post_status ) {
		return '';
	}

	$def  = apollo_ui_type( $type );
	$args = wp_parse_args(
		$args,
		array(
			'variant'  => 'default',
			'class'    => '',
			'lightbox' => null,   // null = follow settings
		)
	);

	$cpt  = (string) $post->post_type;
	$link = (string) get_permalink( $post );
	$title = (string) get_the_title( $post );

	$lb = null === $args['lightbox']
		? apollo_ui_lightbox_enabled( $cpt )
		: (bool) $args['lightbox'];

	$classes = array(
		'aui-card',
		'aui-' . $type,
		'aui-media-' . $def['media'],
		'aui-layout-' . $def['layout'],
		'aui-variant-' . sanitize_html_class( (string) $args['variant'] ),
	);
	if ( $args['class'] ) {
		$classes[] = sanitize_html_class( (string) $args['class'] );
	}
	if ( $lb ) {
		$classes[] = 'is-lightbox';
	}

	$thumb = '';
	if ( 'none' !== $def['media'] ) {
		if ( has_post_thumbnail( $post ) ) {
			$thumb = get_the_post_thumbnail(
				$post,
				'poster' === $def['media'] ? 'large' : 'medium_large',
				array(
					'class'   => 'aui-img',
					'loading' => 'lazy',
					'alt'     => esc_attr( $title ),
				)
			);
		} else {
			$thumb = '<span class="aui-img aui-img--empty" aria-hidden="true"></span>';
		}
	}

	$meta = apollo_ui_card_meta( $post );

	$open_attrs = $lb
		? sprintf(
			' data-apollo-open="%d" data-apollo-cpt="%s"',
			(int) $post_id,
			esc_attr( $cpt )
		)
		: '';

	ob_start();
	?>
	<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
		style="--aui-ratio: <?php echo esc_attr( (string) $def['ratio'] ); ?>;"
		data-aui-type="<?php echo esc_attr( $type ); ?>"
		data-aui-id="<?php echo esc_attr( (string) $post_id ); ?>">
		<a class="aui-link" href="<?php echo esc_url( $link ); ?>"<?php echo $open_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr(). ?>>
			<?php if ( $thumb ) : ?>
				<figure class="aui-media"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?></figure>
			<?php endif; ?>
			<div class="aui-body">
				<h3 class="aui-title"><?php echo esc_html( $title ); ?></h3>
				<?php if ( $meta ) : ?>
					<p class="aui-meta"><?php echo esc_html( $meta ); ?></p>
				<?php endif; ?>
				<?php if ( 'bare' === $def['layout'] ) : ?>
					<p class="aui-excerpt"><?php echo esc_html( wp_trim_words( (string) $post->post_excerpt ?: (string) $post->post_content, 22 ) ); ?></p>
				<?php endif; ?>
			</div>
		</a>
	</article>
	<?php
	return (string) ob_get_clean();
}

/**
 * A single meta line, resolved per post type.
 *
 * Reads GOVERNED meta only (`apollo-core/config/meta.php`). Ungoverned keys are
 * deliberately not consulted: 224 of them exist with no type and no owner, and
 * a card is the wrong place to start trusting them.
 */
function apollo_ui_card_meta( \WP_Post $post ): string {
	switch ( $post->post_type ) {
		case 'event':
			$d = (string) get_post_meta( $post->ID, '_event_start_date', true );
			$t = (string) get_post_meta( $post->ID, '_event_start_time', true );
			$out = trim( $d . ( $t ? ' · ' . $t : '' ) );
			return $out;
		case 'dj':
			return (string) get_post_meta( $post->ID, '_dj_instagram', true );
		case 'local':
			return (string) get_post_meta( $post->ID, '_local_city', true );
		default:
			return (string) get_the_date( '', $post );
	}
}

/*
 * Named renderers, one per style.
 *
 * apollo_card_render() calls the renderer as ($post_id, $args) and never passes
 * the type back, so a shared callback could not distinguish the styles. These
 * six wrappers close that gap and keep stack traces readable.
 */
if ( ! function_exists( 'apollo_ui_render_type_1' ) ) {
	function apollo_ui_render_type_1( int $id, array $a = array() ): string { return apollo_ui_card( 'type-1', $id, $a ); }
	function apollo_ui_render_type_2( int $id, array $a = array() ): string { return apollo_ui_card( 'type-2', $id, $a ); }
	function apollo_ui_render_type_3( int $id, array $a = array() ): string { return apollo_ui_card( 'type-3', $id, $a ); }
	function apollo_ui_render_type_4( int $id, array $a = array() ): string { return apollo_ui_card( 'type-4', $id, $a ); }
	function apollo_ui_render_type_5( int $id, array $a = array() ): string { return apollo_ui_card( 'type-5', $id, $a ); }
	function apollo_ui_render_type_6( int $id, array $a = array() ): string { return apollo_ui_card( 'type-6', $id, $a ); }
}

/**
 * Contract hook for apollo_card_styles_once().
 *
 * Returns '' because apollo-ui ships a real stylesheet through wp_enqueue_style
 * rather than inlining CSS per card. The contract prints this once per type; an
 * empty string is the correct answer, not a missing one.
 */
function apollo_ui_card_styles(): string {
	return '';
}
