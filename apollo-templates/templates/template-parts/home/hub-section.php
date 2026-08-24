<?php
/**
 * Apollo Home — HUB Section
 *
 * Showcase HUB::rio feature with 3D card effect.
 *
 * @package Apollo\Templates
 * @since   3.0.0
 */

defined( 'ABSPATH' ) || exit;

$args = $args ?? array();

// Get a featured DJ or use defaults.
$featured_dj = $args['featured_dj'] ?? null;

if ( ! $featured_dj ) {
	$dj_query = new WP_Query(
		array(
			'post_type'      => 'dj_profile',
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'orderby'        => 'rand',
		)
	);

	if ( $dj_query->have_posts() ) {
		$dj_query->the_post();
		$featured_dj = array(
			'name'       => get_the_title(),
			'handle'     => get_post_meta( get_the_ID(), '_instagram_handle', true ) ?: '@' . sanitize_title( get_the_title() ),
			'role'       => get_post_meta( get_the_ID(), '_dj_role', true ) ?: 'DJ & Producer',
			'avatar'     => get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' ),
			'soundcloud' => get_post_meta( get_the_ID(), '_soundcloud_url', true ),
			'agenda_url' => get_permalink(),
			'press_kit'  => get_post_meta( get_the_ID(), '_press_kit_url', true ),
		);
		wp_reset_postdata();
	}
}

// Fallback defaults.
$featured_dj = wp_parse_args(
	$featured_dj ?? array(),
	array(
		'name'       => 'Discarada',
		'handle'     => '@anaclara_rio',
		'role'       => 'DJ & Producer',
		'avatar'     => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=200&auto=format&fit=crop',
		'soundcloud' => '#',
		'agenda_url' => '#',
		'press_kit'  => '#',
	)
);
?>

<section id="hub" class="hub-section">
	<div class="hub-bg-circle"></div>
	<div class="container">
		<div class="hub-grid">
			<div class="hub-card-wrapper reveal-up">
				<div class="hub-card" id="hubCard">
					<div class="hub-profile">
						<div class="hub-avatar">
							<img src="<?php echo esc_url( $featured_dj['avatar'] ); ?>" alt="<?php echo esc_attr( $featured_dj['name'] ); ?>">
						</div>
					</div>
					<div class="hub-content-card">
						<p class="hub-artist">
							<span class="hub-card-sup-title"><?php echo esc_html( $featured_dj['role'] ); ?></span>
						</p>
						<h3 class="hub-card-title"><?php echo esc_html( $featured_dj['name'] ); ?></h3>
						<p class="hub-handle-meta">
							<i class="ph-bold ph-at"></i>
							<span><?php echo esc_html( $featured_dj['handle'] ); ?></span>
						</p>
					</div>
					<div class="hub-links">
						<a href="<?php echo esc_url( $featured_dj['soundcloud'] ); ?>" class="hub-link hub-link-primary" target="_blank" rel="noopener">
							<span>SoundCloud Set</span>
							<i class="ri-arrow-right-up-long-line"></i>
						</a>
						<a href="<?php echo esc_url( $featured_dj['agenda_url'] ); ?>" class="hub-link hub-link-secondary">
							<span><?php esc_html_e( 'Agenda 2026', 'apollo-templates' ); ?></span>
							<i class="ri-arrow-right-up-long-line"></i>
						</a>
						<a href="<?php echo esc_url( $featured_dj['press_kit'] ); ?>" class="hub-link hub-link-secondary">
							<span>Press Kit</span>
							<i class="ri-arrow-right-up-long-line"></i>
						</a>
					</div>
					<div class="hub-footer"><span>Powered by Apollo</span></div>
				</div>
			</div>
			<div class="hub-content reveal-up delay-100">
				<div class="hub-status">
					<span class="hub-pulse"></span>
					<span class="hub-status-text"><?php esc_html_e( 'Ferramenta', 'apollo-templates' ); ?></span>
				</div>
				<h2>HUB::rio</h2>
				<p class="hub-description">
					<?php esc_html_e( 'Uma página simples para todos os seus links. O Apollo é a mão extra que apoia toda a indústria, centralizando sua presença digital em um único ponto de contato.', 'apollo-templates' ); ?>
				</p>
				<?php if ( ! is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( home_url( '/acesso' ) ); ?>" class="hub-cta smooth-transition">
						<?php esc_html_e( 'Criar minha conta', 'apollo-templates' ); ?>
						<i class="ri-arrow-right-up-long-line"></i>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/id/' . wp_get_current_user()->user_login ) ); ?>" class="hub-cta smooth-transition">
						<?php esc_html_e( 'Gerenciar meu HUB', 'apollo-templates' ); ?>
						<i class="ri-arrow-right-up-long-line"></i>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<script>
(function(){
	var h=document.getElementById('hubCard');
	if(h){
		h.onmousemove=function(e){
			var r=h.getBoundingClientRect();
			var x=(e.clientX-r.left-r.width/2)/(r.width/2)*5;
			var y=(e.clientY-r.top-r.height/2)/(r.height/2)*-5;
			h.style.transform='perspective(1000px) rotateX('+y+'deg) rotateY('+x+'deg) scale(1.02)';
		};
		h.onmouseleave=function(){
			h.style.transform='perspective(1000px) rotateX(0) rotateY(0) scale(1)';
		};
	}
})();
</script>
