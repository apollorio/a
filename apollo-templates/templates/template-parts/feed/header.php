<?php
/**
 * Feed — greeting header (mockup: .fd-greet, fed by FEED_META).
 * Values derived server-side from real time/user, never a fixture.
 * @package Apollo\Templates
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$g = function_exists( 'apollo_feed_greeting' ) ? apollo_feed_greeting() : array( 'greeting' => '', 'location' => '', 'dateLabel' => '' );
?>
<header class="feed-page-hd fd-greet">
    <h1><?php echo esc_html( $g['greeting'] ); ?></h1>
    <p><?php echo esc_html( $g['location'] . ' · ' . $g['dateLabel'] ); ?></p>
</header>
