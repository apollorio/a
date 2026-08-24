<?php
/**
 * Single Event — Access (tickets + coupon + listas + CTA)
 *
 * Final mockup layout, top to bottom, with NO section divider between blocks:
 *   ev-ticket (dark)  →  ev-coupon glued flush beneath it  →  listas rendered
 *   as the same ticket geometry in inverted colours  →  ev-lista-cta pill.
 *
 * Data SSOT: apollo_event_build_access_payload().
 *
 * @package Apollo\Event
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uid = (string) ( $uid ?? '' );

$access = is_array( $access ?? null ) ? $access : array();
if ( empty( $access ) && function_exists( 'apollo_event_build_access_payload' ) ) {
	$access = apollo_event_build_access_payload( (int) $post_id );
}

$tickets   = is_array( $access['tickets'] ?? null ) ? $access['tickets'] : array();
$listas    = is_array( $access['listas'] ?? null ) ? $access['listas'] : array();
$coupon    = is_array( $access['coupon'] ?? null ) ? $access['coupon'] : null;
$lista_cta = is_array( $access['listaCta'] ?? null ) ? $access['listaCta'] : null;

/* A finished event sells nothing — only the coupon record may remain. */
if ( ! empty( $is_gone ) ) {
	$tickets   = array();
	$listas    = array();
	$lista_cta = null;
}

$has_coupon = ( $coupon && ! empty( $coupon['code'] ) );
$has_access = ! empty( $tickets ) || $has_coupon || ! empty( $listas ) || ( $lista_cta && ! empty( $lista_cta['url'] ) );

if ( ! $has_access ) {
	return;
}

/**
 * One ticket row — identical geometry for tickets and listas; only the
 * .is-{kind} modifier flips the palette.
 *
 * @param array<string,mixed> $item     Row data.
 * @param string              $fallback Fallback icon class.
 * @param string              $arrow    Trailing icon when the row has no URL.
 * @return void
 */
$apollo_render_ticket = static function ( array $item, string $fallback, string $arrow ): void {
	$kind  = sanitize_key( (string) ( $item['kind'] ?? 'main' ) );
	$class = 'ev-ticket' . ( ( 'main' !== $kind && '' !== $kind ) ? ' is-' . $kind : '' );
	$url   = (string) ( $item['url'] ?? '' );
	$icon  = (string) ( $item['icon'] ?? $fallback );
	$name  = (string) ( $item['name'] ?? '' );
	$sub   = (string) ( $item['sub'] ?? '' );
	$tag   = ( '' !== $url ) ? 'a' : 'div';
	?>
	<<?php echo esc_attr( $tag ); ?> class="<?php echo esc_attr( $class ); ?>"<?php
	if ( '' !== $url ) {
		echo ' href="' . esc_url( $url ) . '" target="_blank" rel="noopener"';
	}
	?>>
		<span class="ev-t-ico"><i class="<?php echo esc_attr( $icon ); ?>"></i></span>
		<span class="ev-t-info">
			<span class="ev-t-name"><?php echo esc_html( $name ); ?></span>
			<?php if ( '' !== $sub ) : ?>
			<span class="ev-t-sub"><?php echo esc_html( $sub ); ?></span>
			<?php endif; ?>
		</span>
		<i class="<?php echo esc_attr( ( '' !== $url ? 'ri-arrow-right-up-line' : $arrow ) ); ?> ev-t-arr" aria-hidden="true"></i>
	</<?php echo esc_attr( $tag ); ?>>
	<?php
};
?>
  <!-- ACCESS -->
  <section class="ev-sec" id="<?php echo esc_attr( apollo_ev_id( 'accessSec', $uid ) ); ?>" data-ev="access">

	<?php if ( ! empty( $tickets ) ) : ?>
    <p class="ev-label"><?php echo esc_html( (string) ( $access['ticketsLabel'] ?? __( 'Ingressos', 'apollo-events' ) ) ); ?></p>
    <div data-ev="tickets-list">
		<?php
		foreach ( $tickets as $apollo_ticket ) {
			$apollo_render_ticket( (array) $apollo_ticket, 'ri-ticket-2-line', 'ri-information-line' );
		}
		?>
    </div>
	<?php endif; ?>

	<?php if ( $has_coupon ) : ?>
    <!-- Glued flush under the last ticket (negative margin + flat top corners). -->
    <div class="ev-coupon" id="<?php echo esc_attr( apollo_ev_id( 'couponBox', $uid ) ); ?>"
         data-ev="coupon" role="button" tabindex="0"
         aria-label="<?php esc_attr_e( 'Copiar cupom', 'apollo-events' ); ?>">
      <i class="ri-coupon-3-line ev-c-ico" aria-hidden="true"></i>
      <!-- .ev-c-ico replaces the mockup's inline style attribute. -->
      <div>
        <code data-ev="coupon-code"><?php echo esc_html( (string) $coupon['code'] ); ?></code>
        <small><?php echo esc_html( (string) ( $coupon['hint'] ?? __( 'Toque para copiar', 'apollo-events' ) ) ); ?></small>
      </div>
      <span class="ev-coupon-act" data-ev="coupon-label"><?php esc_html_e( 'COPIAR', 'apollo-events' ); ?></span>
    </div>
	<?php endif; ?>

	<?php if ( ! empty( $listas ) ) : ?>
    <!-- No "LISTA" label or divider: same ticket style, inverted colours. -->
    <div data-ev="listas-list">
		<?php
		foreach ( $listas as $apollo_lista ) {
			$apollo_lista = (array) $apollo_lista;
			if ( empty( $apollo_lista['kind'] ) ) {
				$apollo_lista['kind'] = 'lista';
			}
			$apollo_render_ticket( $apollo_lista, 'ri-vip-line', 'ri-information-line' );
		}
		?>
    </div>
	<?php endif; ?>

	<?php if ( $lista_cta && ! empty( $lista_cta['url'] ) ) : ?>
    <a class="ev-lista-cta" href="<?php echo esc_url( (string) $lista_cta['url'] ); ?>" target="_blank" rel="noopener">
      <i class="ri-user-add-line"></i>
      <span><?php echo esc_html( (string) ( $lista_cta['label'] ?? __( 'Entrar para uma Lista', 'apollo-events' ) ) ); ?></span>
    </a>
	<?php endif; ?>
  </section>
