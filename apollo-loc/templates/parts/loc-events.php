<?php
/**
 * Template Part: loc-events — Lista de eventos futuros no local
 *
 * @var int   $local_id
 * @var array $upcoming_events  Lista de arrays com {id, title, url, date, image, tags}
 *
 * @package Apollo\Local
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $upcoming_events ) ) {
	return;
}
?>
<section class="loc-section loc-events-section" aria-labelledby="loc-events-title">
	<h2 id="loc-events-title" class="section-title">
		<i class="ri-calendar-event-line" aria-hidden="true"></i>
		<?php esc_html_e( 'Próximos eventos', 'apollo-local' ); ?>
		<span class="section-badge"><?php echo count( $upcoming_events ); ?></span>
	</h2>

	<div class="events-scroll-row">
		<?php foreach ( $upcoming_events as $ev ) : ?>
			<a href="<?php echo esc_url( $ev['url'] ); ?>" class="event-card-mini">
				<?php if ( ! empty( $ev['image'] ) ) : ?>
					<div class="event-card-mini__thumb">
						<img
							src="<?php echo esc_url( $ev['image'] ); ?>"
							alt="<?php echo esc_attr( $ev['title'] ); ?>"
							loading="lazy"
							decoding="async"
						>
					</div>
				<?php else : ?>
					<div class="event-card-mini__thumb event-card-mini__thumb--placeholder" aria-hidden="true">
						<i class="ri-music-2-line"></i>
					</div>
				<?php endif; ?>

				<div class="event-card-mini__body">
					<?php if ( ! empty( $ev['date'] ) ) : ?>
						<time class="event-card-mini__date" datetime="<?php echo esc_attr( $ev['date'] ); ?>">
							<?php
							$ts = strtotime( $ev['date'] );
							echo $ts ? esc_html( date_i18n( 'd M', $ts ) ) : esc_html( $ev['date'] );
							?>
						</time>
					<?php endif; ?>

					<p class="event-card-mini__title"><?php echo esc_html( $ev['title'] ); ?></p>

					<?php if ( ! empty( $ev['tags'] ) ) : ?>
						<div class="event-card-mini__tags">
							<?php foreach ( $ev['tags'] as $tag ) : ?>
								<span class="tag-pill"><?php echo esc_html( $tag ); ?></span>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</a>
		<?php endforeach; ?>
	</div>
</section>
