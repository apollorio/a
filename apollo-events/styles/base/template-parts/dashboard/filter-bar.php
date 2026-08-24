<?php
/**
 * Meus Eventos — Filter pills + count
 *
 * Expected: $total_count, $counts (published, draft, scheduled)
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
        <div class="ev-filters">
            <div class="ev-filters__pills">
                <button type="button" class="ev-filter is-active" data-filter="all"><?php esc_html_e( 'Todos', 'apollo-events' ); ?></button>
                <button type="button" class="ev-filter" data-filter="published"><?php esc_html_e( 'Publicados', 'apollo-events' ); ?><span>(<?php echo esc_html( (string) ( $counts['published'] ?? 0 ) ); ?>)</span></button>
                <button type="button" class="ev-filter" data-filter="draft"><?php esc_html_e( 'Rascunhos', 'apollo-events' ); ?><span>(<?php echo esc_html( (string) ( $counts['draft'] ?? 0 ) ); ?>)</span></button>
                <button type="button" class="ev-filter" data-filter="scheduled"><?php esc_html_e( 'Agendados', 'apollo-events' ); ?><span>(<?php echo esc_html( (string) ( $counts['scheduled'] ?? 0 ) ); ?>)</span></button>
            </div>
            <span class="ev-filters__count" id="evCount">
				<?php
				/* translators: %d: number of events */
				printf( esc_html__( '%d eventos', 'apollo-events' ), absint( $total_count ) );
				?>
            </span>
        </div>
