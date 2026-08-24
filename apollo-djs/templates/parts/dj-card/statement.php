<?php
/**
 * @partial statement
 * @expects $ctx['statement'] — meta._dj_statement (fallback _dj_bio_short)
 * Word-fill scrub section; italic accent word wrapped in <em class="gold"> if present.
 *
 * @package Apollo\DJs
 */
defined( 'ABSPATH' ) || exit;

$statement = (string) ( $ctx['statement'] ?? '' );
if ( '' === $statement ) {
	return;
}
?>
<section class="stmt wrap" id="stmt">
	<p class="stmt-txt" id="stmtTxt"><?php echo esc_html( $statement ); ?></p>
</section>
