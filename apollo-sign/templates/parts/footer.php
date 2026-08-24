<?php
/**
 * Template Part: Footer
 * Hash display + verification URL + copyright.
 *
 * @package Apollo\Sign
 * @var string $hash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="sign-footer">
	<div class="sign-hash">Hash: <?php echo esc_html( $hash ); ?></div>
	<p>
		Verificação pública disponível em<br>
		<strong><?php echo esc_url( home_url( '/assinar/' . $hash ) ); ?></strong>
	</p>
	<p style="margin-top:6px">Apollo Platform © <?php echo esc_html( date( 'Y' ) ); ?> — ICP-Brasil Compliance</p>
</div>
