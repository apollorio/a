<?php

/**
 * Helper Functions
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get plugin instance
 */
function apollo_sheets(): \Apollo\Sheets\Plugin {
	return \Apollo\Sheets\Plugin::get_instance();
}

/**
 * Get a sheet by its logical ID and return HTML
 *
 * @param string|array $query Sheet ID or array of shortcode atts.
 * @return string Rendered HTML table.
 */
function apollo_sheets_get_table( $query ): string {
	if ( is_string( $query ) ) {
		$query = array( 'id' => $query );
	}
	$render = new \Apollo\Sheets\Render();
	return $render->shortcode_output( $query );
}

/**
 * Print a sheet directly
 *
 * @param string|array $query Sheet ID or array of shortcode atts.
 */
function apollo_sheets_print_table( $query ): void {
	echo apollo_sheets_get_table( $query ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Get sheet info without rendering
 *
 * @param string $id    Sheet ID.
 * @param string $field Field name (name|description|last_modified|author|number_rows|number_columns).
 * @return string|int|false
 */
function apollo_sheets_get_info( string $id, string $field = 'name' ) {
	$model = new \Apollo\Sheets\Model();
	$sheet = $model->load( $id );

	if ( ! $sheet ) {
		return false;
	}

	return match ( $field ) {
		'name'           => $sheet['name'],
		'description'    => $sheet['description'],
		'last_modified'  => $sheet['last_modified'],
		'author'         => get_the_author_meta( 'display_name', (int) $sheet['author'] ),
		'number_rows'    => count( $sheet['data'] ?? array() ),
		'number_columns' => ! empty( $sheet['data'][0] ) ? count( $sheet['data'][0] ) : 0,
		default          => false,
	};
}
