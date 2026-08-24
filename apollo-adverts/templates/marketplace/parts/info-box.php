<?php

/**
 * Template Part: Info Box (Security Warning)
 *
 * @package Apollo\Classifieds
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="info-box reveal-up">
	<i class="ri-shield-check-line"></i>
	<div class="info-content">
		<h3>Transações Seguras</h3>
		<p>O Apollo é uma ponte. Para sua segurança, verifique sempre o perfil do vendedor antes de transferir qualquer valor. <a href="<?php echo esc_url( home_url( '/seguranca' ) ); ?>">Ver dicas de segurança.</a></p>
	</div>
</div>
