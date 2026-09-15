<?php
/**
 * Track card — THE track card. One owner, every surface.
 *
 * Click play → preview + card expands (slide-down) revealing platform icons.
 * Icons hidden until expanded. SC-only tracks still get a SoundCloud save icon
 * from the listen canonical URL.
 *
 * @package Apollo\Templates
 * @since   1.5.3
 *
 * @var array<string,mixed> $data    From apollo_track_card_data(), or a
 *                                   placeholder shape for the pre-launch state.
 * @var string              $variant rail|grid|compact
 * @var bool                $is_placeholder Simulated card, no real track behind it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data           = isset( $data ) && is_array( $data ) ? $data : array();
$variant        = isset( $variant ) ? (string) $variant : 'grid';
$is_placeholder = ! empty( $is_placeholder );

$cover          = (string) ( $data['cover'] ?? '' );
$has_cover      = ! empty( $data['has_cover'] ) || '' !== $cover;
$title          = (string) ( $data['title'] ?? '' );
$artists        = (string) ( $data['artists'] ?? '' );
$genre          = (string) ( $data['genre'] ?? '' );
$duration       = (string) ( $data['duration'] ?? '' );
$url            = (string) ( $data['url'] ?? '' );
$listen         = is_array( $data['listen'] ?? null ) ? $data['listen'] : array();
$all_urls       = is_array( $data['urls'] ?? null ) ? $data['urls'] : array();

$sc_url = trim( (string) ( $all_urls['soundcloud'] ?? '' ) );
if ( '' === $sc_url ) {
	$sc_url = trim( (string) ( $listen['canonical'] ?? '' ) );
}

$can_play = ! $is_placeholder && ! empty( $listen['can_play'] );
if ( ! $can_play && ! $is_placeholder && '' !== $sc_url && function_exists( 'apollo_track_listen_from_soundcloud' ) ) {
	$listen   = apollo_track_listen_from_soundcloud( $sc_url );
	$can_play = ! empty( $listen['can_play'] );
}

$listen_chain_json = '';
if ( $can_play && ! empty( $listen['chain'] ) && function_exists( 'wp_json_encode' ) ) {
	$listen_chain_json = (string) wp_json_encode( $listen['chain'] );
}

$is_sc_listen = ( $can_play && 'soundcloud' === (string) ( $listen['provider'] ?? '' ) ) || '' !== $sc_url;

$platform_icons = array(
	'spotify'    => array( 'label' => 'Spotify', 'icon' => 'ri-spotify-fill' ),
	'bandcamp'   => array( 'label' => 'Bandcamp', 'icon' => 'ri-bandcamp-line' ),
	'soundcloud' => array( 'label' => 'SoundCloud', 'icon' => 'ri-soundcloud-fill' ),
	'youtube'    => array( 'label' => 'YouTube', 'icon' => 'ri-youtube-fill' ),
);

$shown_platforms = array();
foreach ( $platform_icons as $key => $meta ) {
	$href = trim( (string) ( $all_urls[ $key ] ?? '' ) );
	if ( 'soundcloud' === $key && '' === $href && $can_play && 'soundcloud' === (string) ( $listen['provider'] ?? '' ) ) {
		$href = trim( (string) ( $listen['canonical'] ?? '' ) );
	}
	if ( '' !== $href ) {
		$shown_platforms[ $key ] = array(
			'href'  => $href,
			'label' => $meta['label'],
			'icon'  => $meta['icon'],
		);
	}
}

$show_expand = $can_play && ! empty( $shown_platforms );
$debug_attr  = sprintf(
	'play:%d chain:%d plats:%d cover:%d',
	$can_play ? 1 : 0,
	is_array( $listen['chain'] ?? null ) ? count( $listen['chain'] ) : 0,
	count( $shown_platforms ),
	$has_cover ? 1 : 0
);

$display_title   = $is_placeholder || '' === $title
	? __( 'Em breve', 'apollo-templates' )
	: $title;
$display_artists = $is_placeholder || '' === $artists
	? __( 'Coming soon', 'apollo-templates' )
	: $artists;
$display_genre   = $is_placeholder || '' === $genre
	? __( 'Electronic', 'apollo-templates' )
	: $genre;

$artwork_classes = 'nh-track-artwork';
if ( ! $has_cover ) {
	$artwork_classes .= ' nh-track-artwork--empty';
}
?>
<article
	class="nh-track-card reveal-up ai is-unlocked<?php echo esc_attr( $variant ? ' nh-track-card--' . $variant : '' ); ?>"
	<?php if ( ! $is_placeholder && ! empty( $data['id'] ) ) : ?>
	data-track-id="<?php echo esc_attr( (string) $data['id'] ); ?>"
	<?php endif; ?>
	<?php if ( $can_play ) : ?>
	data-listen-provider="<?php echo esc_attr( (string) ( $listen['provider'] ?? '' ) ); ?>"
	data-listen-mode="<?php echo esc_attr( (string) ( $listen['mode'] ?? '' ) ); ?>"
	data-listen-src="<?php echo esc_url( (string) ( $listen['src'] ?? '' ) ); ?>"
	data-listen-canonical="<?php echo esc_url( (string) ( $listen['canonical'] ?? '' ) ); ?>"
	data-listen-embed-url="<?php echo esc_url( (string) ( $listen['embed_url'] ?? '' ) ); ?>"
	data-listen-start-pct="<?php echo esc_attr( (string) (int) ( $listen['start_pct'] ?? 20 ) ); ?>"
	data-listen-end-pct="<?php echo esc_attr( (string) (int) ( $listen['end_pct'] ?? 65 ) ); ?>"
	data-listen-start="<?php echo esc_attr( (string) (int) ( $listen['start'] ?? 0 ) ); ?>"
	data-listen-seconds="<?php echo esc_attr( (string) (int) ( $listen['seconds'] ?? 30 ) ); ?>"
	<?php if ( '' !== $listen_chain_json ) : ?>
	data-listen-chain="<?php echo esc_attr( $listen_chain_json ); ?>"
	<?php endif; ?>
	<?php endif; ?>
	data-apollo-debug="<?php echo esc_attr( $debug_attr ); ?>"
	<?php echo 'rail' === $variant ? ' data-casa-track-rail' : ''; ?>
>
	<div class="<?php echo esc_attr( $artwork_classes ); ?>"<?php echo ( ! $is_placeholder ) ? ' data-track-artwork-trigger' : ''; ?>>
		<?php if ( $has_cover ) : ?>
		<img class="nh-track-blur" src="<?php echo esc_url( $cover ); ?>" alt="" loading="lazy" aria-hidden="true" />
		<img
			class="nh-track-image"
			src="<?php echo esc_url( $cover ); ?>"
			alt="<?php echo esc_attr( $display_title ); ?>"
			loading="lazy"
		/>
		<?php else : ?>
		<span class="nh-track-cover-fallback" aria-hidden="true"><i class="ri-disc-line"></i></span>
		<?php endif; ?>
		<div class="nh-track-ring" aria-hidden="true"></div>
		<div class="nh-track-ring nh-track-ring--outer" aria-hidden="true"></div>
		<div class="nh-track-play-overlay">
			<?php if ( ! $is_placeholder ) : ?>
				<button
					class="nh-track-play-btn nh-track-preview-btn"
					type="button"
					data-track-preview
					aria-label="<?php echo esc_attr( sprintf( __( 'Ouvir prévia de %s', 'apollo-templates' ), $display_title ) ); ?>"
				>
					<i class="ri-play-fill"></i>
				</button>
				<span class="nh-track-loader" aria-hidden="true"></span>
			<?php endif; ?>
		</div>
	</div>
	<?php if ( $is_sc_listen ) : ?>
	<div
		class="nh-track-sc-transport apsc apsc--rail"
		hidden
		data-apsc
		data-apsc-manual-fallback
		data-apsc-never-reveal
		data-apsc-mode="preview"
		data-apsc-start="20"
		data-apsc-hold="60"
		data-apsc-vol="20"
		data-apsc-url="<?php echo esc_url( (string) ( $listen['canonical'] ?? $sc_url ) ); ?>"
	>
		<div
			class="apsc-transport"
			data-apsc-transport
			hidden
			data-apsc-embed="<?php echo esc_url( (string) ( $listen['embed_url'] ?? '' ) ); ?>"
		></div>
	</div>
	<?php endif; ?>
	<div class="nh-track-info">
		<h4><?php echo esc_html( $display_title ); ?></h4>
		<div class="nh-track-artist"><?php echo esc_html( $display_artists ); ?></div>
		<div class="nh-track-meta">
			<span><i class="ri-headphone-line"></i><?php echo esc_html( $display_genre ); ?></span>
			<?php if ( $is_placeholder ) : ?>
			<span><i class="ri-time-line"></i><?php esc_html_e( 'Em breve', 'apollo-templates' ); ?></span>
			<?php elseif ( '' !== $duration ) : ?>
			<span><i class="ri-time-line"></i><?php echo esc_html( $duration ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( $show_expand ) : ?>
		<div class="nh-track-expand" aria-hidden="true">
			<div class="nh-track-platforms" role="list" aria-label="<?php esc_attr_e( 'Ouvir nas plataformas', 'apollo-templates' ); ?>">
				<?php foreach ( $shown_platforms as $key => $plat ) : ?>
				<a
					class="nh-track-plat nh-track-plat--<?php echo esc_attr( $key ); ?>"
					role="listitem"
					href="<?php echo esc_url( $plat['href'] ); ?>"
					rel="noopener"
					aria-label="<?php echo esc_attr( sprintf( __( 'Abrir no %s', 'apollo-templates' ), $plat['label'] ) ); ?>"
					title="<?php echo esc_attr( $plat['label'] ); ?>"
					data-track-plat
				>
					<i class="<?php echo esc_attr( $plat['icon'] ); ?>" aria-hidden="true"></i>
				</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php endif; ?>
	</div>
</article>
