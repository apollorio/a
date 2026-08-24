<?php
/**
 * Template: Event Card — Style 01
 *
 * Displays a single event card with cutout date overlay, banner, tags, DJ list,
 * location, and sounds meta.
 *
 * Available variables (injected via extract):
 *
 *   @var WP_Post $event  The event post object.
 *   @var array   $atts   Shortcode attributes.
 *
 * Theme override path:
 *   your-theme/apollo-templates/event/card-style-01.php
 *
 * @package Apollo\Templates
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Gather event meta ────────────────────────────────────────────────────

$event_id  = $event->ID;
$title     = get_the_title( $event );
$permalink = get_permalink( $event );

// Dates & times.
$start_date = get_post_meta( $event_id, '_event_start_date', true );
$end_date   = get_post_meta( $event_id, '_event_end_date', true );
$start_time = get_post_meta( $event_id, '_event_start_time', true );
$end_time   = get_post_meta( $event_id, '_event_end_time', true );

// Format date parts for the overlay box.
$day   = $start_date ? date_i18n( 'd', strtotime( $start_date ) ) : '--';
$month = $start_date ? date_i18n( 'M', strtotime( $start_date ) ) : '---';
$year  = $start_date ? date_i18n( 'Y', strtotime( $start_date ) ) : '----';

// Banner / thumbnail.
$banner_id = get_post_meta( $event_id, '_event_banner', true );
$banner    = '';
if ( $banner_id ) {
	$banner = wp_get_attachment_image_url( $banner_id, 'large' );
}
if ( ! $banner && has_post_thumbnail( $event_id ) ) {
	$banner = get_the_post_thumbnail_url( $event_id, 'large' );
}
if ( ! $banner ) {
	$banner = APOLLO_TEMPLATES_URL . 'assets/img/event-placeholder.webp';
}

// DJs.
$dj_ids  = get_post_meta( $event_id, '_event_dj_ids', true );
$dj_list = array();
if ( ! empty( $dj_ids ) ) {
	$dj_ids = is_array( $dj_ids ) ? $dj_ids : explode( ',', (string) $dj_ids );
	foreach ( $dj_ids as $dj_id ) {
		$dj_id = absint( $dj_id );
		if ( $dj_id ) {
			$dj_post = get_post( $dj_id );
			if ( $dj_post ) {
				$dj_list[] = array(
					'name' => get_the_title( $dj_post ),
					'url'  => get_permalink( $dj_post ),
				);
			}
		}
	}
}

// Location.
$loc_id   = get_post_meta( $event_id, '_event_loc_id', true );
$loc_name = '';
$loc_url  = '';
if ( $loc_id ) {
	$loc_post = get_post( absint( $loc_id ) );
	if ( $loc_post ) {
		$loc_name = get_the_title( $loc_post );
		$loc_url  = get_permalink( $loc_post );
	}
}

// Taxonomies.
$categories = wp_get_post_terms( $event_id, 'event_category', array( 'fields' => 'all' ) );
$types      = wp_get_post_terms( $event_id, 'event_type', array( 'fields' => 'all' ) );
$tags       = wp_get_post_terms( $event_id, 'event_tag', array( 'fields' => 'all' ) );
$sounds     = wp_get_post_terms( $event_id, 'sound', array( 'fields' => 'all' ) );

// Merge categories + types into a single "tags" array for the overlay.
$tag_items = array();
if ( ! is_wp_error( $categories ) ) {
	foreach ( $categories as $term ) {
		$tag_items[] = array(
			'name' => $term->name,
			'slug' => $term->slug,
			'url'  => get_term_link( $term ),
		);
	}
}
if ( ! is_wp_error( $types ) ) {
	foreach ( $types as $term ) {
		$tag_items[] = array(
			'name' => $term->name,
			'slug' => $term->slug,
			'url'  => get_term_link( $term ),
		);
	}
}

// Sounds list.
$sound_items = array();
if ( ! is_wp_error( $sounds ) ) {
	foreach ( $sounds as $term ) {
		$sound_items[] = array(
			'name' => $term->name,
			'slug' => $term->slug,
		);
	}
}

// Ticket.
$ticket_url   = get_post_meta( $event_id, '_event_ticket_url', true );
$ticket_price = get_post_meta( $event_id, '_event_ticket_price', true );
$event_status = get_post_meta( $event_id, '_event_status', true ) ?: 'published';

// Time display.
$time_display = '';
if ( $start_time ) {
	$time_display = esc_html( $start_time );
	if ( $end_time ) {
		$time_display .= ' – ' . esc_html( $end_time );
	}
}
?>

<article class="a-eve-card" data-event-id="<?php echo esc_attr( $event_id ); ?>" data-status="<?php echo esc_attr( $event_status ); ?>">

	<?php // ── Date overlay box ──────────────────────────────────────────── ?>
	<div class="a-eve-date">
		<span class="a-eve-date__day"><?php echo esc_html( $day ); ?></span>
		<span class="a-eve-date__month"><?php echo esc_html( strtoupper( $month ) ); ?></span>
		<span class="a-eve-date__year"><?php echo esc_html( $year ); ?></span>
	</div>

	<?php // ── Media / banner with cutout mask ───────────────────────────── ?>
	<div class="a-eve-media">
		<a href="<?php echo esc_url( $permalink ); ?>" class="a-eve-media__link" aria-label="<?php echo esc_attr( $title ); ?>">
			<img
				class="a-eve-media__img"
				src="<?php echo esc_url( $banner ); ?>"
				alt="<?php echo esc_attr( $title ); ?>"
				loading="lazy"
				decoding="async"
			/>
		</a>

		<?php if ( ! empty( $tag_items ) ) : ?>
			<div class="a-eve-tags">
				<?php foreach ( $tag_items as $tag ) : ?>
					<a href="<?php echo esc_url( $tag['url'] ); ?>" class="a-eve-tag"><?php echo esc_html( $tag['name'] ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php // ── Content ───────────────────────────────────────────────────── ?>
	<div class="a-eve-content">

		<h3 class="a-eve-title">
			<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
		</h3>

		<div class="a-eve-meta">

			<?php // ── DJs ──────────────── ?>
			<?php if ( ! empty( $dj_list ) ) : ?>
				<div class="a-eve-meta__row a-eve-meta--djs">
					<i class="ri-disc-line" aria-hidden="true"></i>
					<span>
						<?php
						$dj_links = array();
						foreach ( $dj_list as $dj ) {
							$dj_links[] = '<a href="' . esc_url( $dj['url'] ) . '">' . esc_html( $dj['name'] ) . '</a>';
						}
						echo implode( ', ', $dj_links ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</span>
				</div>
			<?php endif; ?>

			<?php // ── Location ─────────── ?>
			<?php if ( $loc_name ) : ?>
				<div class="a-eve-meta__row a-eve-meta--loc">
					<i class="ri-map-pin-line" aria-hidden="true"></i>
					<?php if ( $loc_url ) : ?>
						<a href="<?php echo esc_url( $loc_url ); ?>"><?php echo esc_html( $loc_name ); ?></a>
					<?php else : ?>
						<span><?php echo esc_html( $loc_name ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php // ── Time ─────────────── ?>
			<?php if ( $time_display ) : ?>
				<div class="a-eve-meta__row a-eve-meta--time">
					<i class="ri-time-line" aria-hidden="true"></i>
					<span><?php echo $time_display; ?></span>
				</div>
			<?php endif; ?>

			<?php // ── Sounds ───────────── ?>
			<?php if ( ! empty( $sound_items ) ) : ?>
				<div class="a-eve-meta__row a-eve-meta--sounds">
					<i class="ri-music-2-line" aria-hidden="true"></i>
					<span>
						<?php
						echo esc_html( implode( ' · ', array_column( $sound_items, 'name' ) ) );
						?>
					</span>
				</div>
			<?php endif; ?>

		</div>

		<?php // ── Actions bar ───────────────────────────────────────────── ?>
		<div class="a-eve-actions">
			<a href="<?php echo esc_url( $permalink ); ?>" class="a-btn a-btn--sm a-btn--outline">
				<?php esc_html_e( 'Ver evento', 'apollo-templates' ); ?>
			</a>
			<?php if ( $ticket_url ) : ?>
				<a href="<?php echo esc_url( $ticket_url ); ?>" class="a-btn a-btn--sm a-btn--primary" target="_blank" rel="noopener">
					<?php
					if ( $ticket_price ) {
						/* translators: %s: ticket price */
						printf( esc_html__( 'Ingressos %s', 'apollo-templates' ), esc_html( $ticket_price ) );
					} else {
						esc_html_e( 'Ingressos', 'apollo-templates' );
					}
					?>
				</a>
			<?php endif; ?>
		</div>

	</div>
</article>
