<?php
/**
 * Directory Part — Create CTA
 *
 * Bottom CTA encouraging users to create a new comuna/núcleo.
 * Expects: $is_logged, $create_url, $is_nucleos
 *
 * @package Apollo\Groups
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! $is_logged ) {
	return;
}
?>
<div class="wrap">
	<a href="<?php echo esc_url( $create_url ); ?>" class="create-cta g-fade">
		<i class="ri-add-circle-line"></i>
		<h3>Crie <?php echo $is_nucleos ? 'seu núcleo' : 'sua comuna'; ?></h3>
		<p>Reúna a galera que compartilha a mesma vibe</p>
	</a>
</div>
