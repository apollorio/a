<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var array  $settings
 * @var array  $data
 * @var string $uid
 */

$hour   = (int) ( $data['hour'] ?? 0 );
$minute = (int) ( $data['minute'] ?? 0 );
$label  = sprintf( '%02d:%02d', $hour, $minute );
?>
<div class="ae-now-line ae-accent" data-a-module="now-line-tick" data-hour="<?php echo esc_attr( (string) $hour ); ?>" data-minute="<?php echo esc_attr( (string) $minute ); ?>">
	<span class="ae-now-line__dot ae-accent-bg"></span>
	<span class="ae-now-line__rule ae-accent-bg"></span>
	<span class="ae-now-line__label"><?php echo esc_html( $label ); ?></span>
</div>
