<?php
/**
 * URL Import — config bootstrap + modular scripts.
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Apollo\Event\Import\UrlImportPage;
?>
<script>
window.APOLLO_URL_IMPORT = <?php echo wp_json_encode( $import_config ); ?>;
</script>
<?php
foreach ( UrlImportPage::script_modules() as $module ) {
	$rel = 'assets/js/url-import/' . $module . '.js';
	printf(
		'<script src="%s"></script>' . "\n",
		esc_url( UrlImportPage::asset_url( $rel ) )
	);
}
