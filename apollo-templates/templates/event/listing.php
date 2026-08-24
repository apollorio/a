<?php
/**
 * Template: Events Listing (grid wrapper)
 *
 * Loops a WP_Query of events and renders the chosen card style for each.
 *
 * Available variables (injected via extract):
 *
 *   @var WP_Query $events  The query result.
 *   @var array    $atts    Shortcode attributes.
 *   @var string   $style   Card style number (e.g. '01').
 *   @var int      $columns Number of grid columns (1-4).
 *
 * Theme override path:
 *   your-theme/apollo-templates/event/listing.php
 *
 * @package Apollo\Templates
 */

use function Apollo\Templates\apollo_get_template;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure we have a valid WP_Query.
if ( ! isset( $events ) || ! $events instanceof \WP_Query || ! $events->have_posts() ) {
	echo '<p class="a-eve-empty">' . esc_html__( 'Nenhum evento encontrado.', 'apollo-templates' ) . '</p>';
	return;
}

$columns = isset( $columns ) ? absint( $columns ) : 3;
$style   = isset( $style ) ? sanitize_file_name( $style ) : '01';

$card_template = 'event/card-style-' . $style . '.php';
?>

<div class="a-eve-grid" data-columns="<?php echo esc_attr( $columns ); ?>" style="--eve-columns: <?php echo esc_attr( $columns ); ?>;">

	<?php
	while ( $events->have_posts() ) :
		$events->the_post();
		global $post;

		apollo_get_template(
			$card_template,
			array(
				'event' => $post,
				'atts'  => $atts,
			)
		);

	endwhile;
	?>

</div>
