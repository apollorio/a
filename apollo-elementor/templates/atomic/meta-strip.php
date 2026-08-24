<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$cells = $data['cells'] ?? [];

if ( empty( $cells ) ) {
	return;
}
?>
<div id="<?php echo esc_attr( $uid ); ?>" class="ae-meta-strip">
	<?php foreach ( $cells as $cell ) :
		$label    = $cell['label'] ?? '';
		$value    = $cell['value'] ?? '';
		$sublabel = $cell['sublabel'] ?? '';
	?>
		<div class="ae-meta-strip__cell">
			<?php if ( '' !== $label ) : ?>
				<span class="ae-meta-strip__label"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
			<span class="ae-meta-strip__value ae-accent"><?php echo esc_html( $value ); ?></span>
			<?php if ( '' !== $sublabel ) : ?>
				<span class="ae-meta-strip__sublabel"><?php echo esc_html( $sublabel ); ?></span>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
