<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$tracks = $data['tracks'] ?? [];

if ( empty( $tracks ) ) {
	return;
}
?>
<div id="<?php echo esc_attr( $uid ); ?>" class="ae-discography">
	<?php foreach ( $tracks as $index => $track ) :
		$title    = $track['title'] ?? '';
		$year     = $track['year'] ?? '';
		$genre    = $track['genre'] ?? '';
		$duration = $track['duration'] ?? '';
	?>
		<div class="ae-discography__row">
			<span class="ae-discography__index"><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
			<span class="ae-discography__title"><?php echo esc_html( $title ); ?></span>
			<?php if ( '' !== $genre ) : ?>
				<span class="ae-discography__genre ae-accent"><?php echo esc_html( $genre ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $year ) : ?>
				<span class="ae-discography__year"><?php echo esc_html( $year ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $duration ) : ?>
				<span class="ae-discography__duration"><?php echo esc_html( $duration ); ?></span>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
