<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var array  $settings
 * @var array  $data
 * @var string $uid
 */

$date_label = esc_html( $data['label'] ?? '' );
$day_num    = (int) ( $data['day'] ?? 0 );
$items      = $data['items'] ?? [];
$col_date   = esc_attr( $data['date'] ?? '' );
?>
<div class="ae-kanban-column" data-date="<?php echo $col_date; ?>">
	<div class="ae-kanban-column__header">
		<span class="ae-kanban-column__day-label"><?php echo $date_label; ?></span>
		<span class="ae-kanban-column__day-num ae-accent"><?php echo esc_html( (string) $day_num ); ?></span>
	</div>

	<div class="ae-kanban-column__body">
		<?php if ( empty( $items ) ) : ?>
			<p class="ae-kanban-column__empty"><?php esc_html_e( 'No events', 'apollo-elementor' ); ?></p>
		<?php else : ?>
			<?php foreach ( $items as $item ) :
				$type_class = 'ae-card--' . esc_attr( $item['type'] ?? 'appt' );
			?>
				<div class="ae-card <?php echo $type_class; ?>"
					draggable="true"
					data-id="<?php echo esc_attr( (string) ( $item['id'] ?? 0 ) ); ?>"
					style="-webkit-user-select: none !important; user-select: none !important; -webkit-touch-callout: none !important; touch-action: none !important;">
					<span class="ae-card__stripe ae-accent-bg"></span>
					<div class="ae-card__content">
						<span class="ae-card__time">
							<?php echo esc_html( $item['time_start'] ?? '' ); ?>&ndash;<?php echo esc_html( $item['time_end'] ?? '' ); ?>
						</span>
						<span class="ae-card__title"><?php echo esc_html( $item['title'] ?? '' ); ?></span>
						<?php if ( ! empty( $item['venue'] ) ) : ?>
							<span class="ae-card__venue"><?php echo esc_html( $item['venue'] ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</div>
