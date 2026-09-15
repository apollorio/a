<?php
/**
 * Template: Single Event — Base Style
 *
 * Thin document shell. ALL body markup comes from apollo_event_render_single()
 * (includes/render-single.php), the single renderer shared by this page, inline
 * embeds and the REST lightbox fragment — so the three can never drift apart.
 *
 * Design source: _dev web/screen/single cpt/event/single-event/event-single-page.html
 *
 * @package Apollo\Event
 * @since   2.2.0
 * @version 2.3.0 — modular renderer + lightbox-ready output
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'APOLLO_EVENT_SINGLE_RENDERING' ) ) {
	define( 'APOLLO_EVENT_SINGLE_RENDERING', true );
}

/*
 * Defensive load — blank-canvas / template-loader paths can reach this file
 * before (or without) the plugin bootstrap having loaded global helpers.
 */
$apollo_event_dir = defined( 'APOLLO_EVENT_DIR' )
	? APOLLO_EVENT_DIR
	: dirname( __DIR__, 2 ) . '/';

if ( is_readable( $apollo_event_dir . 'includes/bootstrap.php' ) ) {
	require_once $apollo_event_dir . 'includes/bootstrap.php';
} else {
	foreach ( array( 'includes/functions.php', 'includes/functions-global.php', 'includes/render-single.php' ) as $apollo_event_fallback ) {
		if ( is_readable( $apollo_event_dir . $apollo_event_fallback ) ) {
			require_once $apollo_event_dir . $apollo_event_fallback;
		}
	}
}

if ( function_exists( 'apollo_event_ensure_helpers' ) ) {
	apollo_event_ensure_helpers();
}

if ( ! function_exists( 'apollo_event_render_single' ) && is_readable( $apollo_event_dir . 'includes/render-single.php' ) ) {
	require_once $apollo_event_dir . 'includes/render-single.php';
}

$post_id = (int) get_the_ID();

if ( ! $post_id || ! function_exists( 'apollo_event_render_single' ) ) {
	status_header( 404 );
	echo '<!doctype html><meta charset="utf-8"><title>404</title>';
	return;
}

if ( ! apollo_event_single_can_view( $post_id ) ) {
	status_header( 404 );
	nocache_headers();
	echo '<!doctype html><meta charset="utf-8"><title>404</title>';
	return;
}

$ev    = apollo_event_single_context( $post_id );
$title = (string) $ev['title'];

/* <head> assets come from the styles part so the fragment path can reuse it. */
$extra_head = apollo_event_render_part( 'styles', $ev );

/* Body is rendered up-front: any part can register late <head> needs safely. */
$body_html = apollo_event_render_single(
	$post_id,
	array(
		'mode' => 'page',
		'uid'  => '',
	)
);

$doc_title = $title . ' — Apollo::Rio';
if ( class_exists( '\Apollo\SEO\Meta' ) ) {
	$doc_title = \Apollo\SEO\Meta::title_for( array( 'post_id' => $post_id ) );
}

if ( function_exists( 'apollo_render_document_open' ) ) {
	apollo_render_document_open(
		array(
			'title'      => $doc_title,
			'extra_head' => $extra_head,
		)
	);
} else {
	?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<link rel="preconnect" href="https://assets.apollo.rio.br">
	<link rel="preconnect" href="https://cdn.apollo.rio.br">
	<title><?php echo $doc_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Meta::title_for() escapes. ?></title>
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted template part output.
	echo $extra_head;
	?>
</head>
	<?php
}
?>
<body class="apollo-single-event">

<?php do_action( 'apollo_event_single_before', $post_id ); ?>

<?php
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part escapes its own output.
echo $body_html;
?>

<?php do_action( 'apollo_event_single_after', $post_id ); ?>

<?php
/* Lightbox shell — lets any card on this page open another event in place. */
apollo_event_lightbox_shell();

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted template part output.
echo apollo_event_render_part( 'scripts', $ev );

if ( function_exists( 'apollo_render_document_close' ) ) {
	apollo_render_document_close();
} else {
	echo '</body></html>';
}
