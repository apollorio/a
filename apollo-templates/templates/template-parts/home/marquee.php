<?php
/**
 * Apollo Home — Marquee Section
 *
 * Scrolling text marquee with reveal animation.
 *
 * @package Apollo\Templates
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$args = $args ?? array();

$words = $args['words'] ?? array(
	__( 'Cultura', 'apollo-templates' ),
	__( 'Memória', 'apollo-templates' ),
	__( 'Inteligência', 'apollo-templates' ),
	__( 'Conexão', 'apollo-templates' ),
);

// Duplicate for seamless CSS loop.
$all_words = array_merge( $words, $words, $words );
?>

<div class="marquee-wrapper">
	<div class="marquee-content">
		<?php foreach ( $all_words as $word ) : ?>
			<span class="marquee-text"><?php echo esc_html( $word ); ?></span>
		<?php endforeach; ?>
	</div>
</div>
