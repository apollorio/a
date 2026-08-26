<?php
/**
 * One-shot OPcache bust + disk check for apollo-events debug session e031aa.
 */
header( 'Content-Type: text/plain; charset=utf-8' );
header( 'Cache-Control: no-store' );

$base = __DIR__;
$paths = array(
	$base . '/styles/base/single-event.php',
	$base . '/styles/base/single-event-runtime.php',
	$base . '/styles/apollo-v2/single-event.php',
	$base . '/includes/bootstrap.php',
	$base . '/includes/functions.php',
	$base . '/includes/functions-global.php',
	$base . '/src/TemplateLoader.php',
	$base . '/apollo-events.php',
);

$f = $paths[0];
$c = is_file( $f ) ? file_get_contents( $f ) : '';
$lines = explode( "\n", $c );
echo 'single_size=' . ( is_file( $f ) ? filesize( $f ) : 0 ) . "\n";
echo 'single_mtime=' . ( is_file( $f ) ? date( 'c', filemtime( $f ) ) : '' ) . "\n";
echo 'is_stub=' . ( strpos( $c, 'single-event-runtime.php' ) !== false ? '1' : '0' ) . "\n";
echo 'line38=' . json_encode( isset( $lines[37] ) ? trim( $lines[37] ) : '' ) . "\n";
echo 'runtime_exists=' . ( is_file( $paths[1] ) ? '1' : '0' ) . "\n";

$inv = array();
if ( function_exists( 'opcache_invalidate' ) ) {
	foreach ( $paths as $p ) {
		if ( is_file( $p ) ) {
			$inv[ basename( $p ) ] = opcache_invalidate( $p, true );
		}
	}
}
if ( function_exists( 'opcache_reset' ) ) {
	$inv['reset'] = opcache_reset();
}
echo 'opcache=' . json_encode( $inv ) . "\n";
echo "DONE\n";
