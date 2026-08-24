<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$lineup = $data['lineup'] ?? [];

if ( empty( $lineup ) ) {
	return;
}
?>
<div id="<?php echo esc_attr( $uid ); ?>" class="ae-event-lineup">
	<?php foreach ( $lineup as $artist ) :
		$name       = $artist['name'] ?? '';
		$role       = $artist['role'] ?? '';
		$time_start = $artist['time_start'] ?? '';
		$time_end   = $artist['time_end'] ?? '';
		$avatar_url = $artist['avatar_url'] ?? '';
		$user_id    = (int) ( $artist['user_id'] ?? 0 );
		$profile    = 0 < $user_id ? get_author_posts_url( $user_id ) : '';
	?>
		<div class="ae-event-lineup__row">
			<?php if ( '' !== $avatar_url ) : ?>
				<img
					class="ae-event-lineup__avatar"
					src="<?php echo esc_url( $avatar_url ); ?>"
					alt="<?php echo esc_attr( $name ); ?>"
					width="48"
					height="48"
					loading="lazy"
				/>
			<?php endif; ?>

			<div class="ae-event-lineup__info">
				<?php if ( '' !== $profile ) : ?>
					<a href="<?php echo esc_url( $profile ); ?>" class="ae-event-lineup__name ae-accent">
						<?php echo esc_html( $name ); ?>
					</a>
				<?php else : ?>
					<span class="ae-event-lineup__name ae-accent"><?php echo esc_html( $name ); ?></span>
				<?php endif; ?>

				<?php if ( '' !== $role ) : ?>
					<span class="ae-event-lineup__role"><?php echo esc_html( $role ); ?></span>
				<?php endif; ?>
			</div>

			<?php if ( '' !== $time_start ) : ?>
				<span class="ae-event-lineup__time">
					<?php echo esc_html( $time_start ); ?>
					<?php if ( '' !== $time_end ) : ?>
						– <?php echo esc_html( $time_end ); ?>
					<?php endif; ?>
				</span>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
