<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$partners = $data['partners'] ?? [];

if ( empty( $partners ) ) {
	return;
}
?>
<div id="<?php echo esc_attr( $uid ); ?>" class="ae-played-with">
	<?php foreach ( $partners as $partner ) :
		$name = $partner['name'] ?? '';
		$url  = $partner['url'] ?? '';
	?>
		<?php if ( '' !== $url ) : ?>
			<a href="<?php echo esc_url( $url ); ?>" class="ae-played-with__pill ae-accent">
				<?php echo esc_html( $name ); ?>
			</a>
		<?php else : ?>
			<span class="ae-played-with__pill ae-accent">
				<?php echo esc_html( $name ); ?>
			</span>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
