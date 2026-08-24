<?php
/**
 * URL Import — page styles (importer glue only; tokens from core.js).
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<link rel="stylesheet" href="<?php echo esc_url( \Apollo\Event\Import\UrlImportPage::asset_url( 'assets/css/apollo-events-url-import.css' ) ); ?>">
