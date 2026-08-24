<?php declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $settings */
/** @var array $data */
/** @var string $uid */

$dj          = $data['dj'] ?? [];
$played_with = $data['played_with'] ?? [];

$name       = $dj['display_name'] ?? 'DJ';
$bio        = $dj['bio'] ?? '';
$city       = $dj['city'] ?? '';
$membership = $dj['membership'] ?? '';
$avatar     = $dj['avatar_url'] ?? '';
$sounds     = $dj['sounds'] ?? [];
$instagram  = $dj['instagram'] ?? '';
$soundcloud = $dj['soundcloud'] ?? '';
$wow_count  = $dj['wow_count'] ?? 0;
$fav_count  = $dj['fav_count'] ?? 0;

if ( empty( $name ) ) {
	return;
}
?>
<div id="<?php echo esc_attr( $uid ); ?>" class="ae-dj-page" data-a-module="premium-singles">

	<div class="ae-dj-progress" aria-hidden="true"></div>

	<!-- hero -->
	<section class="ae-dj-hero" aria-label="<?php echo esc_attr( $name ); ?>">
		<?php if ( $avatar ) : ?>
			<img class="ae-dj-hero-img"
				 src="<?php echo esc_url( $avatar ); ?>"
				 alt="<?php echo esc_attr( $name ); ?>"
				 loading="eager">
		<?php endif; ?>
		<div class="ae-dj-hero-grad" aria-hidden="true"></div>

		<div class="ae-dj-hero-card">
			<span class="ae-dj-eyebrow">
				<?php if ( $city ) : ?>
					<?php echo esc_html( $city ); ?> &middot;
				<?php endif; ?>
				<?php if ( $membership ) : ?>
					<?php echo esc_html( $membership ); ?>
				<?php endif; ?>
			</span>
			<h1 class="ae-dj-title"><?php echo esc_html( $name ); ?></h1>
			<div class="ae-dj-stamp">
				<?php if ( $avatar ) : ?>
					<img class="ae-dj-avatar"
						 src="<?php echo esc_url( $avatar ); ?>"
						 alt="<?php echo esc_attr( $name ); ?>"
						 width="56" height="56" loading="lazy">
				<?php endif; ?>
				<div class="ae-dj-stamp-meta">
					<div class="ae-dj-name-line"><?php echo esc_html( $name ); ?></div>
					<?php if ( ! empty( $sounds ) ) : ?>
						<div class="ae-dj-role-line"><?php echo esc_html( implode( ' · ', array_slice( $sounds, 0, 3 ) ) ); ?></div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</section>

	<!-- marquee -->
	<?php if ( ! empty( $sounds ) ) : ?>
		<div class="ae-dj-marquee-wrap" aria-hidden="true">
			<div class="ae-dj-marquee-track">
				<?php
				$marquee_items = $sounds;
				$doubled       = array_merge( $marquee_items, $marquee_items );
				foreach ( $doubled as $genre ) : ?>
					<span class="ae-dj-genre-pill"><?php echo esc_html( $genre ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- meta-strip -->
	<section class="ae-dj-meta" aria-label="<?php esc_attr_e( 'Artist quick facts', 'apollo-elementor' ); ?>">
		<?php if ( $wow_count > 0 ) : ?>
			<div class="ae-dj-meta-cell">
				<span class="ae-dj-meta-k"><?php esc_html_e( 'Registered on Apollo', 'apollo-elementor' ); ?></span>
				<span class="ae-dj-meta-v"><?php echo esc_html( (string) $wow_count ); ?></span>
				<span class="ae-dj-meta-sub"><?php esc_html_e( 'registered events', 'apollo-elementor' ); ?></span>
			</div>
		<?php endif; ?>
		<?php if ( $city ) : ?>
			<div class="ae-dj-meta-cell">
				<span class="ae-dj-meta-k"><?php esc_html_e( 'Home base', 'apollo-elementor' ); ?></span>
				<span class="ae-dj-meta-v"><?php echo esc_html( $city ); ?></span>
			</div>
		<?php endif; ?>
		<?php if ( $fav_count > 0 ) : ?>
			<div class="ae-dj-meta-cell">
				<span class="ae-dj-meta-k"><?php esc_html_e( 'Community', 'apollo-elementor' ); ?></span>
				<span class="ae-dj-meta-v"><?php echo esc_html( (string) $fav_count ); ?></span>
				<span class="ae-dj-meta-sub"><?php esc_html_e( 'favourites', 'apollo-elementor' ); ?></span>
			</div>
		<?php endif; ?>
	</section>

	<div class="ae-dj-frame">

		<!-- bento: this-is-dj -->
		<section class="ae-dj-block">
			<span class="ae-dj-block-label"><?php esc_html_e( 'Performance & Story', 'apollo-elementor' ); ?></span>
			<h2><?php
				/* translators: %s: DJ name */
				printf( esc_html__( 'This is %s.', 'apollo-elementor' ), esc_html( $name ) );
			?></h2>
			<p class="ae-dj-block-lead"><?php esc_html_e( 'Not rankings. Not reach. Moments on the floor, tracks made together, and cities that felt it.', 'apollo-elementor' ); ?></p>

			<div class="ae-dj-bento">
				<?php if ( ! empty( $sounds ) ) : ?>
					<div class="ae-dj-bento-cell">
						<span class="ae-dj-bento-kicker"><?php esc_html_e( 'Sounds', 'apollo-elementor' ); ?></span>
						<div class="ae-dj-bento-tags">
							<?php foreach ( $sounds as $sound ) : ?>
								<span class="ae-dj-bento-tag"><?php echo esc_html( $sound ); ?></span>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( $city ) : ?>
					<div class="ae-dj-bento-cell">
						<span class="ae-dj-bento-kicker"><?php esc_html_e( 'Based in', 'apollo-elementor' ); ?></span>
						<span class="ae-dj-bento-num"><?php echo esc_html( $city ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $played_with ) ) : ?>
					<div class="ae-dj-bento-cell ae-dj-bento-wide">
						<span class="ae-dj-bento-kicker"><?php esc_html_e( 'Played with', 'apollo-elementor' ); ?></span>
						<p class="ae-dj-bento-body"><?php
							/* translators: %s: DJ name */
							printf( esc_html__( 'These DJs shared the night with %s —', 'apollo-elementor' ), esc_html( $name ) );
						?></p>
						<div class="ae-dj-played-list">
							<?php foreach ( $played_with as $partner ) : ?>
								<a class="ae-dj-dj-link" href="<?php echo esc_url( $partner['url'] ?? '#' ); ?>">
									<?php echo esc_html( $partner['name'] ?? '' ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<!-- about -->
		<?php if ( $bio ) : ?>
			<section class="ae-dj-block">
				<span class="ae-dj-block-label"><?php esc_html_e( 'About', 'apollo-elementor' ); ?></span>
				<h2><?php esc_html_e( 'The Artist.', 'apollo-elementor' ); ?></h2>
				<div class="ae-dj-about-grid">
					<?php if ( $avatar ) : ?>
						<img class="ae-dj-about-img"
							 src="<?php echo esc_url( $avatar ); ?>"
							 alt="<?php echo esc_attr( $name ); ?>"
							 loading="lazy">
					<?php endif; ?>
					<div class="ae-dj-about-body">
						<p><?php echo esc_html( $bio ); ?></p>
						<?php if ( ! empty( $sounds ) ) : ?>
							<div class="ae-dj-genre-chips">
								<?php foreach ( $sounds as $genre ) : ?>
									<span class="ae-dj-genre-chip"><?php echo esc_html( $genre ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<!-- booking-cta -->
		<section class="ae-dj-block ae-dj-booking">
			<h2><?php
				/* translators: %s: DJ name */
				printf( esc_html__( 'Book %s.', 'apollo-elementor' ), esc_html( $name ) );
			?></h2>
			<p><?php esc_html_e( 'For events, residencies, and press — reach the team through Apollo.', 'apollo-elementor' ); ?></p>
			<div class="ae-dj-btn-row">
				<button class="ae-dj-btn ae-accent-bg" type="button">
					<?php esc_html_e( 'Send Booking Request', 'apollo-elementor' ); ?>
				</button>
				<?php if ( $instagram ) : ?>
					<a class="ae-dj-btn ae-dj-btn-ghost" href="<?php echo esc_url( $instagram ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Instagram', 'apollo-elementor' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( $soundcloud ) : ?>
					<a class="ae-dj-btn ae-dj-btn-ghost" href="<?php echo esc_url( $soundcloud ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'SoundCloud', 'apollo-elementor' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</section>

	</div>

	<!-- footer -->
	<footer class="ae-dj-footer">
		<span class="ae-dj-footer-brand">Apollo &middot; <?php echo esc_html( $name ); ?></span>
	</footer>

</div>
