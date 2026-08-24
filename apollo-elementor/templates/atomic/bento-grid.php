<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$cells = $data['cells'] ?? [];
$dj    = $data['dj'] ?? [];

if ( empty( $cells ) ) {
	return;
}
?>
<div id="<?php echo esc_attr( $uid ); ?>" class="ae-bento-grid" data-a-module="flip">
	<?php foreach ( $cells as $i => $cell ) :
		$kicker = $cell['kicker'] ?? '';
		$num    = $cell['num'] ?? '';
		$body   = $cell['body'] ?? '';
	?>
		<div class="ae-bento-grid__cell ae-bento-grid__cell--<?php echo esc_attr( (string) $i ); ?>">
			<?php if ( '' !== $kicker ) : ?>
				<span class="ae-bento-grid__kicker"><?php echo esc_html( $kicker ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $num ) : ?>
				<span class="ae-bento-grid__num ae-accent"><?php echo esc_html( $num ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $body ) : ?>
				<div class="ae-bento-grid__body"><?php echo wp_kses_post( $body ); ?></div>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
