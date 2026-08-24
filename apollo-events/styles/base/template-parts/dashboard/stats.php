<?php
/**
 * Meus Eventos — Stat cards (real data)
 *
 * Expected: $stats (total, published, draft, coauthor)
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stat_items = array(
	array(
		'lbl' => __( 'Total de Eventos', 'apollo-events' ),
		'val' => (int) ( $stats['total'] ?? 0 ),
		'sub' => __( 'autor + co-autor', 'apollo-events' ),
	),
	array(
		'lbl' => __( 'Publicados', 'apollo-events' ),
		'val' => (int) ( $stats['published'] ?? 0 ),
		'sub' => __( 'no ar', 'apollo-events' ),
	),
	array(
		'lbl' => __( 'Rascunhos', 'apollo-events' ),
		'val' => (int) ( $stats['draft'] ?? 0 ),
		'sub' => __( 'em edição', 'apollo-events' ),
	),
	array(
		'lbl' => __( 'Como Co-autor', 'apollo-events' ),
		'val' => (int) ( $stats['coauthor'] ?? 0 ),
		'sub' => __( 'colaborações', 'apollo-events' ),
	),
);
?>
        <div class="ev-stats">
			<?php foreach ( $stat_items as $item ) : ?>
                <div class="card sh01 ev-stat">
                    <div class="ev-stat__lbl"><?php echo esc_html( $item['lbl'] ); ?></div>
                    <div class="ev-stat__val"><?php echo esc_html( (string) $item['val'] ); ?></div>
                    <div class="ev-stat__sub"><?php echo esc_html( $item['sub'] ); ?></div>
                </div>
			<?php endforeach; ?>
        </div>
