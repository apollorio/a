<?php
/**
 * Template Part: loc-sidebar — Sidebar com mapa, horários e residentes
 *
 * @var int     $local_id
 * @var bool    $has_coords
 * @var float   $lat
 * @var float   $lng
 * @var string  $full_address
 * @var string  $phone
 * @var string  $capacity
 * @var array   $social_links
 *
 * @package Apollo\Local
 */

defined( 'ABSPATH' ) || exit;
?>
<aside class="loc-sidebar">

	<?php if ( $has_coords ) : ?>
		<!-- Mapa interativo Leaflet -->
		<div class="sidebar-card sidebar-card--map">
			<div
				id="apolloLocSingleMap"
				class="loc-sidebar-map"
				data-lat="<?php echo esc_attr( $lat ); ?>"
				data-lng="<?php echo esc_attr( $lng ); ?>"
				data-zoom="15"
				data-name="<?php echo esc_attr( get_the_title( $local_id ) ); ?>"
				style="height:220px;"
				aria-label="<?php esc_attr_e( 'Mapa da localização', 'apollo-local' ); ?>"
			></div>

			<?php
			$route_url = apollo_local_route_url( (float) $lat, (float) $lng );
			?>
			<a
				href="<?php echo esc_url( $route_url ); ?>"
				class="btn-route"
				target="_blank"
				rel="noopener noreferrer"
			>
				<i class="ri-navigation-line" aria-hidden="true"></i>
				<?php esc_html_e( 'Como chegar', 'apollo-local' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<!-- Informações gerais -->
	<div class="sidebar-card sidebar-card--info">
		<ul class="info-list">
			<?php if ( $full_address ) : ?>
				<li class="info-item">
					<i class="ri-map-pin-line" aria-hidden="true"></i>
					<span><?php echo esc_html( $full_address ); ?></span>
				</li>
			<?php endif; ?>

			<?php if ( $phone ) : ?>
				<li class="info-item">
					<i class="ri-phone-line" aria-hidden="true"></i>
					<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
						<?php echo esc_html( $phone ); ?>
					</a>
				</li>
			<?php endif; ?>

			<?php if ( $capacity ) : ?>
				<li class="info-item">
					<i class="ri-group-line" aria-hidden="true"></i>
					<span><?php echo esc_html( number_format_i18n( (int) $capacity ) ); ?> <?php esc_html_e( 'pessoas', 'apollo-local' ); ?></span>
				</li>
			<?php endif; ?>
		</ul>
	</div>

	<!-- Contato / links sociais -->
	<?php if ( ! empty( $social_links ) ) : ?>
		<div class="sidebar-card sidebar-card--contact">
			<h3 class="sidebar-title"><?php esc_html_e( 'Contato', 'apollo-local' ); ?></h3>
			<?php include __DIR__ . '/loc-contact.php'; ?>
		</div>
	<?php endif; ?>

	<!-- Compartilhar -->
	<div class="sidebar-card sidebar-card--share">
		<button
			class="btn-share"
			data-action="share-loc"
			data-title="<?php echo esc_attr( get_the_title( $local_id ) ); ?>"
			data-url="<?php echo esc_attr( get_permalink( $local_id ) ); ?>"
			type="button"
		>
			<i class="ri-share-line" aria-hidden="true"></i>
			<?php esc_html_e( 'Compartilhar', 'apollo-local' ); ?>
		</button>
	</div>

</aside>
