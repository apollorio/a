<?php
/**
 * Mural: My Sounds
 *
 * Sound preference tag pills.
 *
 * @package Apollo\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Variable: $sound_tags (array of term names)
if ( empty( $sound_tags ) ) {
	return;
}
?>

<section class="section-my-sounds">
	<div class="section-header">
		<h3 class="section-title"><i class="ri-sound-module-fill"></i> My Sounds</h3>
		<a href="<?php echo esc_url( home_url( '/editar-perfil/#sounds' ) ); ?>" class="section-link">Manage Tags</a>
	</div>
	<div class="sound-cloud">
		<?php foreach ( $sound_tags as $tag ) : ?>
			<a href="<?php echo esc_url( home_url( '/eventos/?sound=' . urlencode( $tag ) ) ); ?>" class="sound-pill">
				#<?php echo esc_html( $tag ); ?>
			</a>
		<?php endforeach; ?>
	</div>
</section>
