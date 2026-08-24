<?php
/**
 * Single Event — Location Mini Card
 *
 * Location name, address, embedded OSM map (grayscale → color hover),
 * and directions form linking to Google Maps.
 *
 * Expected variables: $loc (array with id, title, address, lat, lng)
 *
 * @package Apollo\Event
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $loc ) ) {
    return;
}

$loc_title   = $loc['title'] ?? '';
$loc_address = $loc['address'] ?? '';
$loc_lat     = $loc['lat'] ?? 0;
$loc_lng     = $loc['lng'] ?? 0;
$has_coords  = ( $loc_lat && $loc_lng );

// Build OSM embed bbox
$bbox = '';
if ( $has_coords ) {
    $bbox = ( $loc_lng - 0.025 ) . ',' . ( $loc_lat - 0.025 ) . ',' . ( $loc_lng + 0.025 ) . ',' . ( $loc_lat + 0.025 );
}
?>

<div class="location-mini" id="local">
    <h3 style="font-size:20px;">
        <a href="<?php echo esc_url( get_permalink( $loc['id'] ) ); ?>">
            <?php echo esc_html( $loc_title ); ?>
        </a>
    </h3>

    <?php if ( $loc_address ) : ?>
        <p style="font-size:14px; color:#666; margin-top:4px;">
            <?php echo esc_html( $loc_address ); ?>
        </p>
    <?php endif; ?>

    <?php if ( $has_coords ) : ?>
        <div class="loc-map-frame">
            <iframe
                src="<?php echo esc_url( 'https://www.openstreetmap.org/export/embed.html?bbox=' . $bbox . '&layer=mapnik' ); ?>"
                width="100%"
                height="100%"
                frameborder="0"
                loading="lazy"
                title="<?php echo esc_attr( $loc_title ); ?>">
            </iframe>
        </div>

        <form class="directions-box"
              action="https://www.google.com/maps/dir/"
              method="get"
              target="_blank"
              rel="noopener">
            <input type="text"
                   name="saddr"
                   placeholder="<?php esc_attr_e( 'De onde você vem? Digite para ver o caminho', 'apollo-events' ); ?>" class="apollo-input"
                   class="dir-input">
            <input type="hidden" name="daddr" value="<?php echo esc_attr( $loc_title . ', ' . $loc_address ); ?>">
            <button type="submit" class="dir-go" title="<?php esc_attr_e( 'Obter direções', 'apollo-events' ); ?>">
                <i class="ri-guide-line"></i>
            </button>
        </form>
    <?php endif; ?>
</div>