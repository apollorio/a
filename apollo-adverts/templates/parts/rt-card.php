<?php
/**
 * Resale ticket / accommodation expandable card.
 *
 * @package Apollo\Adverts
 * @var array<string, mixed> $data From apollo_adverts_rt_card_data().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$d        = isset( $data ) && is_array( $data ) ? $data : array();
$locked   = ! empty( $d['locked'] );
$hostel   = ! empty( $d['hostel'] );
$is_accom = ( ( $d['kind'] ?? '' ) === 'accommodation' );
$sold     = ( ( $d['status'] ?? '' ) === 'encerrado' );
$id       = (int) ( $d['id'] ?? 0 );
$uid      = 'rt-' . $id . '-' . wp_unique_id();
$variant  = (string) ( $d['variant'] ?? 'grid' );
$title    = (string) ( $d['title'] ?? '' );
$venue    = (string) ( $d['venue'] ?? '' );
$date     = (string) ( $d['date'] ?? '' );
$eyebrow  = trim( $date . ( $date && $venue ? ' · ' : '' ) . $venue );
$qty      = (int) ( $d['qty'] ?? 1 );
$total    = (float) ( $d['total'] ?? 0 );
$each     = (float) ( $d['price'] ?? 0 );
$type_lbl = (string) ( $d['type_label'] ?? '' );
$has_price = $total > 0 || $each > 0;
$fmt      = static function ( float $n ): string {
	return 'R$ ' . number_format_i18n( $n, 0 );
};

$icon_check = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 13l4 4L19 7" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$icon_chev  = '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

$cta_label = $hostel
	? __( 'Reservar', 'apollo-adverts' )
	: ( $locked
		? __( 'Entre para falar com o vendedor', 'apollo-adverts' )
		: ( $is_accom ? __( 'Falar com o anfitrião', 'apollo-adverts' ) : __( 'Falar com o vendedor', 'apollo-adverts' ) ) );
if ( $sold ) {
	$cta_label = __( 'Anúncio encerrado', 'apollo-adverts' );
}

$seed = (string) $id . $title;
$bars = '';
for ( $i = 0; $i < 36; $i++ ) {
	$c = ord( $seed[ $i % max( 1, strlen( $seed ) ) ] ) + $i;
	$w = ( 0 === $c % 3 ) ? 3 : 1.5;
	$h = 14 + ( $c % 8 );
	$bars .= '<i style="width:' . esc_attr( (string) $w ) . 'px;height:' . esc_attr( (string) $h ) . 'px;"></i>';
}

$classes = array( 'rt-card' );
if ( 'rail' === $variant ) {
	$classes[] = 'rt-card--rail';
}
if ( $locked ) {
	$classes[] = 'is-guest';
}
if ( $sold ) {
	$classes[] = 'is-closed';
}

$qty_chip = $is_accom
	? ( $qty > 1 ? $qty . ' UND' : __( 'NOITE', 'apollo-adverts' ) )
	: $qty . ' UND';

$announced_fmt = '';
if ( ! empty( $d['announced'] ) ) {
	$ts = strtotime( (string) $d['announced'] );
	if ( $ts ) {
		$announced_fmt = wp_date( 'd M · H:i', $ts );
	}
}
?>
<article
	class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
	id="<?php echo esc_attr( $uid ); ?>"
	data-rt-card
	data-classified-id="<?php echo esc_attr( (string) $id ); ?>"
	data-mk-domains="<?php echo esc_attr( (string) ( $d['domains'] ?? '' ) ); ?>"
	aria-expanded="false"
	<?php echo ! empty( $d['aria_hidden'] ) ? 'aria-hidden="true"' : ''; ?>
>
	<div class="rt-main" role="button" tabindex="<?php echo ! empty( $d['aria_hidden'] ) ? '-1' : '0'; ?>">
		<div class="rt-media">
			<img src="<?php echo esc_url( (string) $d['image'] ); ?>" alt="" loading="lazy" decoding="async" />
			<?php if ( $locked ) : ?>
				<div class="rt-lock-av" aria-hidden="true"><i class="ri-lock-2-line"></i></div>
			<?php endif; ?>
			<div class="rt-status <?php echo $sold ? '' : 'available'; ?>">
				<?php echo $sold ? esc_html__( 'Encerrado', 'apollo-adverts' ) : esc_html__( 'Disponível', 'apollo-adverts' ); ?>
			</div>
			<div class="rt-mediafoot">
				<?php if ( $eyebrow ) : ?>
					<div class="eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
				<?php endif; ?>
				<h3><?php echo $locked ? esc_html( $is_accom ? __( 'Hospedagem', 'apollo-adverts' ) : __( 'Ingresso', 'apollo-adverts' ) ) : esc_html( $title ); ?></h3>
			</div>
		</div>

		<div class="rt-summary">
			<div class="rt-chips">
				<span class="rt-chip qty"><?php echo esc_html( $qty_chip ); ?></span>
				<?php if ( $type_lbl ) : ?>
					<span class="rt-chip type"><?php echo esc_html( $type_lbl ); ?></span>
				<?php endif; ?>
			</div>
			<div class="rt-price">
				<?php if ( $locked ) : ?>
					—<small><?php esc_html_e( 'Entre para ver', 'apollo-adverts' ); ?></small>
				<?php elseif ( $has_price ) : ?>
					<?php echo esc_html( $fmt( $is_accom ? $each : $total ) ); ?>
					<small>
						<?php
						echo $is_accom
							? esc_html( $fmt( $each ) . ' /noite' )
							: esc_html( $fmt( $each ) . ' cada' );
						?>
					</small>
				<?php else : ?>
					—<small><?php esc_html_e( 'A combinar', 'apollo-adverts' ); ?></small>
				<?php endif; ?>
			</div>
		</div>

		<div class="rt-toggle"><?php esc_html_e( 'Ver detalhes', 'apollo-adverts' ); ?> <?php echo $icon_chev; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

		<div class="rt-details">
			<div class="rt-details-inner">
				<div class="rt-desc">
					<?php echo esc_html( $locked ? __( 'Identidade e detalhes só para membros.', 'apollo-adverts' ) : (string) $d['desc'] ); ?>
					<?php if ( ! $locked && ! empty( $d['is_meia'] ) ) : ?>
						<div class="rt-lawnote"><b><?php esc_html_e( 'Meia-entrada', 'apollo-adverts' ); ?></b> — <?php esc_html_e( 'desconto de 50% (Lei nº 12.933/2013). O vendedor pode solicitar comprovante no encontro.', 'apollo-adverts' ); ?></div>
					<?php endif; ?>
				</div>

				<?php if ( ! $locked && ! empty( $d['seller']['name'] ) ) : ?>
					<div class="rt-author">
						<div class="rt-avatar">
							<?php if ( ! empty( $d['seller']['avatar'] ) ) : ?>
								<img src="<?php echo esc_url( (string) $d['seller']['avatar'] ); ?>" alt="" />
							<?php endif; ?>
						</div>
						<div class="rt-author-info">
							<div class="rt-author-name">
								<span><?php echo esc_html( (string) $d['seller']['name'] ); ?></span>
								<?php if ( ! empty( $d['seller']['verified'] ) ) : ?>
									<span class="rt-verified"><?php echo $icon_check; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<?php endif; ?>
							</div>
							<div class="rt-handle"><?php echo esc_html( (string) $d['seller']['handle'] ); ?></div>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $announced_fmt && ! $locked ) : ?>
					<div class="rt-timeline">
						<div class="rt-tl-row"><span class="label"><?php esc_html_e( 'Anunciado em', 'apollo-adverts' ); ?></span><span class="value"><?php echo esc_html( $announced_fmt ); ?></span></div>
						<div class="rt-tl-row"><span class="label"><?php esc_html_e( 'Status', 'apollo-adverts' ); ?></span><span class="value"><?php echo $sold ? esc_html__( 'Encerrado', 'apollo-adverts' ) : esc_html__( 'Aguardando', 'apollo-adverts' ); ?></span></div>
					</div>
				<?php endif; ?>

				<?php if ( $sold ) : ?>
					<span class="rt-cta" disabled><?php echo esc_html( $cta_label ); ?></span>
				<?php else : ?>
					<a
						class="rt-cta<?php echo $locked ? ' is-locked' : ''; ?>"
						href="<?php echo esc_url( (string) $d['contact'] ); ?>"
						<?php echo $hostel ? 'target="_blank" rel="noopener"' : ''; ?>
						<?php echo $locked ? 'data-auth-required' : ''; ?>
					>
						<i class="<?php echo $hostel ? 'ri-external-link-line' : ( $locked ? 'ri-lock-2-line' : 'ri-chat-3-line' ); ?>" aria-hidden="true"></i>
						<?php echo esc_html( $cta_label ); ?>
					</a>
				<?php endif; ?>
				<div class="rt-disclaimer">
					<?php
					echo $locked
						? esc_html__( 'Entre para ver o vendedor e abrir o chat com aviso de segurança.', 'apollo-adverts' )
						: wp_kses(
							__( 'Você fala <b>diretamente com o vendedor</b>. Valor, pagamento e entrega são combinados fora do sistema.', 'apollo-adverts' ),
							array( 'b' => array() )
						);
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="rt-seam" aria-hidden="true"><span class="rt-notch left"></span><span class="rt-notch right"></span></div>

	<div class="rt-stub">
		<div class="rt-barcode-row">
			<div class="rt-barcode"><?php echo $bars; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<div class="rt-code">#<?php echo esc_html( (string) $id ); ?></div>
			<?php
			if ( function_exists( 'apollo_adverts_share_button' ) ) {
				apollo_adverts_enqueue_share_assets();
				echo apollo_adverts_share_button( (string) $d['permalink'], array( 'class' => 'nh-mq-share rt-share' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
	</div>
</article>
