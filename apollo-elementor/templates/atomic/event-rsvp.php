<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$event_id    = (int) ( $data['event_id'] ?? 0 );
$event_title = $data['event_title'] ?? '';
$capacity    = (int) ( $data['capacity'] ?? 0 );
$rsvp_count  = (int) ( $data['rsvp_count'] ?? 0 );
$button_text = $data['button_text'] ?? '';
$is_full     = 0 < $capacity && $rsvp_count >= $capacity;
?>
<div id="<?php echo esc_attr( $uid ); ?>" class="ae-event-rsvp" data-a-module="rsvp" data-event-id="<?php echo esc_attr( (string) $event_id ); ?>">
	<?php if ( 0 < $capacity ) : ?>
		<div class="ae-event-rsvp__counter">
			<span class="ae-event-rsvp__count ae-accent"><?php echo esc_html( (string) $rsvp_count ); ?></span>
			<span class="ae-event-rsvp__separator">/</span>
			<span class="ae-event-rsvp__capacity"><?php echo esc_html( (string) $capacity ); ?></span>
		</div>
	<?php endif; ?>

	<button
		type="button"
		class="ae-event-rsvp__btn ae-accent-bg<?php echo $is_full ? ' ae-event-rsvp__btn--disabled' : ''; ?>"
		<?php disabled( $is_full ); ?>
		data-event-id="<?php echo esc_attr( (string) $event_id ); ?>"
	>
		<?php if ( $is_full ) : ?>
			<?php echo esc_html__( 'Sold out', 'apollo-elementor' ); ?>
		<?php else : ?>
			<?php echo esc_html( $button_text ); ?>
		<?php endif; ?>
	</button>
</div>
