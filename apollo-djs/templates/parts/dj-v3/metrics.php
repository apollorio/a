<?php
/**
 * DJ v3 Partial: Metrics Bar
 *
 * Quick stats strip: sounds, projects, events count, verified status.
 * Variables: $dj_sounds, $dj_projects, $dj_events_count, $dj_verified.
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;

$metrics = array();

// Sounds / Genres
if ( ! empty( $dj_sounds ) ) {
	$metrics[] = array(
		'label' => __( 'Sonoridades', 'apollo-djs' ),
		'value' => implode( ', ', array_slice( $dj_sounds, 0, 3 ) ),
		'accent' => true,
	);
}

// Projects
if ( ! empty( $dj_projects ) ) {
	$metrics[] = array(
		'label' => __( 'Projetos', 'apollo-djs' ),
		'value' => implode( ', ', array_map( 'esc_html', $dj_projects ) ),
		'accent' => false,
	);
}

// Upcoming events
if ( $dj_events_count > 0 ) {
	$metrics[] = array(
		'label' => __( 'Agenda', 'apollo-djs' ),
		'value' => sprintf(
			/* translators: %d: number of upcoming events */
			_n( '%d show', '%d shows', $dj_events_count, 'apollo-djs' ),
			$dj_events_count
		),
		'accent' => false,
	);
}

// Verified
$metrics[] = array(
	'label' => __( 'Status', 'apollo-djs' ),
	'value' => $dj_verified
		? __( 'Verificado', 'apollo-djs' )
		: __( 'Artista', 'apollo-djs' ),
	'accent' => $dj_verified,
);

if ( empty( $metrics ) ) {
	return;
}
?>

<section class="dj-metrics">
	<div class="dj-metrics-grid">
		<?php foreach ( $metrics as $m ) : ?>
			<div>
				<div class="dj-metric-label"><?php echo esc_html( $m['label'] ); ?></div>
				<div class="dj-metric-value<?php echo $m['accent'] ? ' accent' : ''; ?>">
					<?php echo esc_html( $m['value'] ); ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
