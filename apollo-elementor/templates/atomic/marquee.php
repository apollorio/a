<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$items = $data['items'] ?? [];
$speed = $data['speed'] ?? 38;

if ( empty( $items ) ) {
	return;
}

$doubled = array_merge( $items, $items );
?>
<div id="<?php echo esc_attr( $uid ); ?>" class="ae-marquee-wrap" data-a-module="marquee" data-speed="<?php echo esc_attr( (string) $speed ); ?>">
	<div class="ae-marquee-track">
		<?php foreach ( $doubled as $item ) : ?>
			<span class="ae-marquee-pill ae-accent"><?php echo esc_html( $item ); ?></span>
		<?php endforeach; ?>
	</div>
</div>
