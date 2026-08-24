<?php
/**
 * Template Part: DJ Single - Hero Section
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;
?>
<section class="dj-hero" id="djHero">
	<div class="dj-hero-name">
		<?php if ( ! empty( $dj_tagline ) ) : ?>
		<div class="dj-tagline" id="dj-tagline"><?php echo esc_html( $dj_tagline ); ?></div>
		<?php endif; ?>

		<div class="dj-name-main" id="dj-name"><?php echo wp_kses_post( $dj_name_formatted ); ?></div>
		<div class="dj-name-sub" id="dj-roles"><?php echo esc_html( $dj_roles ); ?></div>

		<?php if ( ! empty( $dj_projects ) ) : ?>
		<div class="dj-projects" id="dj-projects">
			<?php foreach ( $dj_projects as $project ) : ?>
				<span><?php echo esc_html( $project ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $dj_photo_url ) ) : ?>
	<figure class="dj-hero-photo" id="djPhoto">
		<img id="dj-avatar" src="<?php echo esc_url( $dj_photo_url ); ?>"
			alt="<?php echo esc_attr( sprintf( __( 'Retrato de %s', 'apollo-djs' ), $dj_name ) ); ?>"
			loading="lazy" decoding="async">
	</figure>
	<?php endif; ?>
</section>
