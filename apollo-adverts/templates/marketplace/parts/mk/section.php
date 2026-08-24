<?php
/**
 * Marketplace — one section (label + grid of cards).
 *
 * Expects: $mk_label, $mk_icon, $mk_type ('ticket'|'accommodation'), $mk_card
 * (part filename under marketplace/parts/).
 *
 * Queries the canonical stored type value. Both legacy spellings are accepted
 * because apollo_adverts_canonical_type() only normalises on WRITE — rows
 * created before that existed may still carry ticket_sell / rent_space.
 *
 * @package Apollo\Adverts
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$mk_args = array(
	'post_type'      => APOLLO_CPT_CLASSIFIED,
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

if ( 'other' === $mk_type ) {
	/* CATCH-ALL — everything that is neither a ticket nor a stay.
	 *
	 * Without this section an advert is only visible if it carries a
	 * _classified_type this screen explicitly asks for. Two real populations
	 * fall outside that: type 'general', and adverts with NO type meta at all
	 * — which is EVERY advert created through the frontend before
	 * create_item() started persisting classified_type. Those would render in
	 * neither section and be silently invisible on the very page meant to list
	 * them. NOT EXISTS covers the missing-meta case, which an IN comparison
	 * cannot.
	 */
	$mk_args['meta_query'] = array(
		'relation' => 'OR',
		array(
			'key'     => '_classified_type',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_classified_type',
			'value'   => array_merge( APOLLO_ADVERTS_TICKET_TYPES, APOLLO_ADVERTS_ACCOMMODATION_TYPES ),
			'compare' => 'NOT IN',
		),
	);
} else {
	$mk_values = 'ticket' === $mk_type ? APOLLO_ADVERTS_TICKET_TYPES : APOLLO_ADVERTS_ACCOMMODATION_TYPES;
	$mk_args['meta_query'] = array(
		array(
			'key'     => '_classified_type',
			'value'   => $mk_values,
			'compare' => 'IN',
		),
	);
}

$mk_q = new WP_Query( $mk_args );

/* An empty catch-all is the healthy state — say nothing rather than print a
   section header for a bucket that should normally be empty. */
if ( 'other' === $mk_type && ! $mk_q->have_posts() ) {
	return;
}
?>
<div class="mk-sec-lbl">
    <i class="<?php echo esc_attr( $mk_icon ); ?>" aria-hidden="true"></i>
    <?php echo esc_html( $mk_label ); ?>
    <span class="mk-sec-count"><?php echo esc_html( (string) $mk_q->found_posts ); ?></span>
</div>

<?php if ( ! $mk_q->have_posts() ) : ?>
    <p class="mk-empty"><?php esc_html_e( 'Nenhum anúncio publicado ainda.', 'apollo-adverts' ); ?></p>
<?php else : ?>
    <div class="mk-grid">
        <?php
        $mk_card_file = dirname( __DIR__ ) . '/' . $mk_card;
        while ( $mk_q->have_posts() ) {
            $mk_q->the_post();
            if ( is_readable( $mk_card_file ) ) {
                include $mk_card_file;
            }
        }
        wp_reset_postdata();
        ?>
    </div>
<?php endif; ?>
