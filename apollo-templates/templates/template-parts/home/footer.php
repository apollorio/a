<?php

/**
 * Apollo Home — Footer
 *
 * Minimal footer with copyright, links, and social icons.
 *
 * @package Apollo\Templates
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$args = $args ?? array();

$current_year = gmdate( 'Y' );
$brand_name   = $args['brand_name'] ?? 'Apollo';
$tagline      = $args['tagline'] ?? __( 'a mão extra da cena', 'apollo-templates' );

$footer_links = $args['links'] ?? array(
	array(
		'label' => __( 'Termos de Uso', 'apollo-templates' ),
		'url'   => home_url( '/termos-de-uso' ),
	),
	array(
		'label' => __( 'Política de Privacidade', 'apollo-templates' ),
		'url'   => home_url( '/privacidade' ),
	),
	array(
		'label' => __( 'Contato', 'apollo-templates' ),
		'url'   => '#',
		'attrs' => 'data-apollo-report-trigger',
	),
);

$social_links = $args['social'] ?? array(
	array(
		'icon'  => 'ri-instagram-line',
		'url'   => 'https://instagram.com/apollo_rio',
		'label' => 'Instagram',
	),
	array(
		'icon'  => 'ri-twitter-x-line',
		'url'   => 'https://twitter.com/apollo_rio',
		'label' => 'Twitter',
	),
	array(
		'icon'  => 'ri-discord-line',
		'url'   => '#',
		'label' => 'Discord',
	),
);
?>

<footer class="apollo-footer">
	<div class="container">
		<div class="footer-grid">
			<div class="footer-brand">
				<span class="footer-logo"><?php echo esc_html( $brand_name ); ?></span>
				<span class="footer-tagline"><?php echo esc_html( $tagline ); ?></span>
			</div>
			<div class="footer-section">
				<span class="footer-section-title"><?php esc_html_e( 'Links', 'apollo-templates' ); ?></span>
				<nav class="footer-links">
					<?php foreach ( $footer_links as $link ) : ?>
						<a href="<?php echo esc_url( $link['url'] ); ?>" class="footer-link"
							<?php echo isset( $link['attrs'] ) ? $link['attrs'] : ''; ?>>
							<?php echo esc_html( $link['label'] ); ?>
						</a>
					<?php endforeach; ?>
				</nav>
			</div>
			<div class="footer-section">
				<span class="footer-section-title"><?php esc_html_e( 'Redes', 'apollo-templates' ); ?></span>
				<div class="footer-social">
					<?php foreach ( $social_links as $social ) : ?>
						<a href="<?php echo esc_url( $social['url'] ); ?>" class="footer-social-link" aria-label="<?php echo esc_attr( $social['label'] ); ?>" target="_blank" rel="noopener">
							<i class="<?php echo esc_attr( $social['icon'] ); ?>"></i>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<div class="footer-bottom">
			<span class="footer-copyright">
				&copy; <?php echo esc_html( $current_year ); ?> <?php echo esc_html( $brand_name ); ?>.
				<?php esc_html_e( 'Todos os direitos reservados.', 'apollo-templates' ); ?>
			</span>
			<span class="footer-made-with">
				<?php esc_html_e( 'Feito com', 'apollo-templates' ); ?> <i class="ri-heart-fill"></i> <?php esc_html_e( 'no Rio', 'apollo-templates' ); ?>
			</span>
		</div>
	</div>
</footer>

<?php
// Modal now rendered globally via wp_footer hook in apollo-core
// Trigger: [data-apollo-report-trigger] on any element
?>