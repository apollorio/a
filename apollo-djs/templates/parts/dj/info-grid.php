<?php
/**
 * Template Part: DJ Single - Info Grid (Bio & Links)
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'apollo_dj_render_link_section' ) ) {
	function apollo_dj_render_link_section( $label, $links, $id, $active = '' ) {
		if ( empty( $links ) ) {
			return;
		}
		?>
		<div>
			<div class="dj-links-label"><?php echo esc_html( $label ); ?></div>
			<div class="dj-links-row" id="<?php echo esc_attr( $id ); ?>">
				<?php foreach ( $links as $key => $link ) : ?>
					<?php
					if ( empty( $link['url'] ) ) {
						continue;}
					?>
					<a href="<?php echo esc_url( $link['url'] ); ?>"
						class="dj-link-pill<?php echo ( $key === $active ) ? ' active' : ''; ?>"
						target="_blank" rel="noopener noreferrer">
						<i class="<?php echo esc_attr( $link['icon'] ?? 'ri-link' ); ?>"></i>
						<span><?php echo esc_html( $link['label'] ?? ucfirst( $key ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}
?>

<section class="dj-info-grid">
	<div class="dj-info-block">
		<h2><?php esc_html_e( 'Sobre', 'apollo-djs' ); ?></h2>
		<?php if ( ! empty( $dj_bio_excerpt ) ) : ?>
		<div class="dj-bio-excerpt" id="dj-bio-excerpt"><?php echo wp_kses_post( $dj_bio_excerpt ); ?></div>
		<button type="button" class="dj-bio-toggle" id="bioToggle">
			<span><?php esc_html_e( 'ler bio completa', 'apollo-djs' ); ?></span>
			<i class="ri-arrow-right-up-line"></i>
		</button>
		<?php endif; ?>
	</div>

	<div class="dj-info-block">
		<h2><?php esc_html_e( 'Links principais', 'apollo-djs' ); ?></h2>
		<?php
		apollo_dj_render_link_section( __( 'Música', 'apollo-djs' ), $music_links, 'music-links', 'soundcloud' );
		apollo_dj_render_link_section( __( 'Social', 'apollo-djs' ), $social_links, 'social-links' );
		apollo_dj_render_link_section( __( 'Assets', 'apollo-djs' ), $asset_links, 'asset-links' );

		if ( ! empty( $platform_links ) ) :
			apollo_dj_render_link_section( __( 'Outras plataformas', 'apollo-djs' ), $platform_links, 'other-links' );
			$platform_names = array_map(
				function ( $l ) {
					return $l['label'] ?? '';
				},
				array_filter(
					$platform_links,
					function ( $l ) {
							return ! empty( $l['url'] );
					}
				)
			);
			if ( ! empty( $platform_names ) ) :
				?>
		<p class="more-platforms"><?php printf( esc_html__( 'Clique para abrir %s.', 'apollo-djs' ), esc_html( implode( ' · ', $platform_names ) ) ); ?></p>
				<?php
			endif;
		endif;
		?>
	</div>
</section>
