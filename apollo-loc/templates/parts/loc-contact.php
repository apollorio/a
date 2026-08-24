<?php
/**
 * Template Part: loc-contact — Links de contato e redes sociais
 *
 * @var int   $local_id
 * @var array $social_links  Lista de arrays {url, icon, label}
 * @var string $phone
 *
 * @package Apollo\Local
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $social_links ) && empty( $phone ) ) {
	return;
}
?>
<div class="loc-contact-links">
	<?php foreach ( $social_links as $link ) : ?>
		<a
			href="<?php echo esc_url( $link['url'] ); ?>"
			class="contact-link"
			target="_blank"
			rel="noopener noreferrer"
			aria-label="<?php echo esc_attr( $link['label'] ); ?>"
		>
			<i class="<?php echo esc_attr( $link['icon'] ); ?>" aria-hidden="true"></i>
			<span><?php echo esc_html( $link['label'] ); ?></span>
		</a>
	<?php endforeach; ?>
</div>
