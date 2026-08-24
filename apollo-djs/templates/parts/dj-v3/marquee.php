<?php
/**
 * DJ v3 Partial: Marquee
 *
 * Infinite horizontal text scroller with dynamic stats.
 * Variables: $dj_id, $dj_name, $dj_sounds, $dj_events_count, $dj_projects.
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;

// Build marquee items dynamically
$marquee_items = array();

if ( ! empty( $dj_sounds ) ) {
	$marquee_items[] = implode( ' · ', array_slice( $dj_sounds, 0, 3 ) );
}

if ( $dj_events_count > 0 ) {
	$marquee_items[] = sprintf(
		/* translators: %d: number of upcoming shows */
		_n( '%d Show na Agenda', '%d Shows na Agenda', $dj_events_count, 'apollo-djs' ),
		$dj_events_count
	);
}

if ( ! empty( $dj_projects ) ) {
	foreach ( $dj_projects as $project ) {
		$marquee_items[] = esc_html( $project );
	}
}

// Fallback items
if ( empty( $marquee_items ) ) {
	$marquee_items = array( esc_html( $dj_name ), 'Apollo::Rio' );
}

$separator = ' <i class="ri-flashlight-fill"></i> ';
$content   = '';
foreach ( $marquee_items as $item ) {
	$content .= '<span>' . esc_html( $item ) . '</span>' . $separator;
}
?>

<div class="dj-marquee-wrap">
	<div class="dj-marquee-track" id="dj-marquee">
		<div class="dj-marquee-content">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — items escaped above ?>
		</div>
		<div class="dj-marquee-content" aria-hidden="true">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
</div>
