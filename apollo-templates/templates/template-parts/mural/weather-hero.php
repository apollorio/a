<?php

/**
 * Mural: Weather Hero
 *
 * Full-width weather card with live cam video background.
 * Positioned at the very top of the mural (ABOVE greeting).
 *
 * @package Apollo\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Real-time weather data from OpenMeteo API (cached 30min via transients)
// See: includes/weather-helpers.php for implementation
$weather_temp      = apply_filters( 'apollo_mural_weather_temp', '28°' );
$weather_condition = apply_filters( 'apollo_mural_weather_condition', 'Sunny' );
$weather_icon      = apply_filters( 'apollo_mural_weather_icon', 'ri-sun-fill' );
$weather_location  = apply_filters( 'apollo_mural_weather_location', 'Copacabana' );
$weather_video_id  = apply_filters( 'apollo_mural_weather_video', '8nLRIZhq_0Y' );
?>

<section class="weather-hero" style="width:100%; margin:0;">
	<div class="a-fore-card">
		<div class="a-fore-video-layer">
			<iframe
				src="https://www.youtube.com/embed/<?php echo esc_attr( $weather_video_id ); ?>?autoplay=1&mute=1&controls=0&loop=1&playlist=<?php echo esc_attr( $weather_video_id ); ?>&showinfo=0&rel=0&iv_load_policy=3&disablekb=1&modestbranding=1"
				allow="autoplay; encrypted-media" allowfullscreen loading="lazy"></iframe>
		</div>
		<div class="a-fore-overlay"></div>
		<div class="a-fore-content">
			<div class="a-fore-header">
				<div class="a-fore-icon-box">
					<i class="<?php echo esc_attr( $weather_icon ); ?>"></i>
				</div>
				<div class="a-fore-meta">
					<span class="a-fore-label">LIVE CAM</span>
					<span class="a-fore-location"><?php echo esc_html( $weather_location ); ?></span>
				</div>
			</div>
			<div class="a-fore-footer">
				<div class="a-fore-temp-wrap">
					<span class="a-fore-temp"><?php echo esc_html( $weather_temp ); ?></span>
				</div>
				<div class="a-fore-condition"><?php echo esc_html( $weather_condition ); ?></div>
			</div>
		</div>
	</div>
</section>
