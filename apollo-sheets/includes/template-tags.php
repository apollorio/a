<?php

/**
 * Template Tag Functions for Apollo Sheets
 *
 * Provides developer-friendly functions for use in WordPress themes and plugins.
 * Equivalent to TablePress template-tag-functions.php
 *
 * Usage in theme templates:
 *   <?php display_apollo_sheet( '1' ); ?>
 *   <?php $html = get_apollo_sheet( '1', array('datatables_filter' => false) ); ?>
 *   <?php $info = get_apollo_sheet_info( '1', 'number_rows' ); ?>
 *
 * @package Apollo\Sheets
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_apollo_sheet' ) ) {
	/**
	 * Get the HTML output for an Apollo Sheet.
	 *
	 * Equivalent to `do_shortcode( '[apollo-sheet id="N"]' )` but with a cleaner API.
	 *
	 * @param string $sheet_id   Sheet ID.
	 * @param array  $options    Optional render options to override. Same keys as shortcode attributes.
	 * @return string            Rendered HTML or empty string if not found.
	 *
	 * @example
	 *   echo get_apollo_sheet( '1' );
	 *   echo get_apollo_sheet( '2', array( 'use_datatables' => false, 'table_head' => 2 ) );
	 */
	function get_apollo_sheet( string $sheet_id, array $options = array() ): string {
		if ( '' === $sheet_id ) {
			return '';
		}

		$options['id'] = $sheet_id;

		$render = new \Apollo\Sheets\Render();
		return $render->shortcode_output( $options );
	}
}

if ( ! function_exists( 'display_apollo_sheet' ) ) {
	/**
	 * Echo the HTML output for an Apollo Sheet.
	 *
	 * @param string $sheet_id Sheet ID.
	 * @param array  $options  Optional render options.
	 *
	 * @example
	 *   display_apollo_sheet( '1' );
	 */
	function display_apollo_sheet( string $sheet_id, array $options = array() ): void {
		echo get_apollo_sheet( $sheet_id, $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped in Render
	}
}

if ( ! function_exists( 'get_apollo_sheet_info' ) ) {
	/**
	 * Get a specific piece of information about a sheet.
	 *
	 * @param string $sheet_id Sheet ID.
	 * @param string $field    Field name: name|description|last_modified|author|number_rows|number_columns|author_name.
	 * @return mixed           Field value or false if not found.
	 *
	 * @example
	 *   $rows = get_apollo_sheet_info( '1', 'number_rows' );
	 *   $name = get_apollo_sheet_info( '1', 'name' );
	 *   echo 'Author: ' . get_apollo_sheet_info( '1', 'author_name' );
	 */
	function get_apollo_sheet_info( string $sheet_id, string $field ) {
		if ( '' === $sheet_id ) {
			return false;
		}

		if ( function_exists( 'apollo_sheets_get_info' ) ) {
			return apollo_sheets_get_info( $sheet_id, $field );
		}

		$model = new \Apollo\Sheets\Model();
		$table = $model->load( $sheet_id, true, true );

		if ( ! $table ) {
			return false;
		}

		return match ( $field ) {
			'name'             => $table['name'] ?? '',
			'description'      => $table['description'] ?? '',
			'last_modified'    => $table['last_modified'] ?? '',
			'author'           => (int) ( $table['author'] ?? 0 ),
			'author_name'      => get_the_author_meta( 'display_name', (int) ( $table['author'] ?? 0 ) ),
			'number_rows'      => isset( $table['data'] ) ? count( $table['data'] ) : 0,
			'number_columns'   => isset( $table['data'][0] ) ? count( $table['data'][0] ) : 0,
			'shortcode'        => '[apollo-sheet id="' . $sheet_id . '" /]',
			default            => false,
		};
	}
}

if ( ! function_exists( 'apollo_sheet_exists' ) ) {
	/**
	 * Check if a sheet with the given ID exists.
	 *
	 * @param string $sheet_id Sheet ID.
	 * @return bool
	 *
	 * @example
	 *   if ( apollo_sheet_exists( '1' ) ) { display_apollo_sheet( '1' ); }
	 */
	function apollo_sheet_exists( string $sheet_id ): bool {
		if ( '' === $sheet_id ) {
			return false;
		}
		$model = new \Apollo\Sheets\Model();
		return false !== $model->load( $sheet_id, false, false );
	}
}

if ( ! function_exists( 'get_apollo_sheets_list' ) ) {
	/**
	 * Get a list of all sheets with basic metadata.
	 *
	 * @param array $args Optional filter args:
	 *                    - 'limit' (int): Max number of sheets to return. 0 = all.
	 *                    - 'orderby' (string): 'name'|'last_modified'|'id'. Default 'id'.
	 *                    - 'order' (string): 'ASC'|'DESC'. Default 'ASC'.
	 * @return array[]  Array of sheet summary arrays: [{id, name, description, last_modified, number_rows, number_columns}]
	 *
	 * @example
	 *   $sheets = get_apollo_sheets_list( array( 'limit' => 5, 'orderby' => 'last_modified', 'order' => 'DESC' ) );
	 *   foreach ( $sheets as $sheet ) { echo $sheet['name']; }
	 */
	function get_apollo_sheets_list( array $args = array() ): array {
		$defaults = array(
			'limit'   => 0,
			'orderby' => 'id',
			'order'   => 'ASC',
		);
		$args     = array_merge( $defaults, $args );

		$model  = new \Apollo\Sheets\Model();
		$sheets = $model->load_all() ?: array();

		// Build summary list
		$list = array_map(
			function ( $sheet ) {
				return array(
					'id'             => $sheet['id'] ?? '',
					'name'           => $sheet['name'] ?? '',
					'description'    => $sheet['description'] ?? '',
					'last_modified'  => $sheet['last_modified'] ?? '',
					'number_rows'    => isset( $sheet['data'] ) ? count( $sheet['data'] ) : 0,
					'number_columns' => isset( $sheet['data'][0] ) ? count( $sheet['data'][0] ) : 0,
					'author'         => (int) ( $sheet['author'] ?? 0 ),
				);
			},
			$sheets
		);

		// Sort
		$orderby = $args['orderby'];
		$order   = strtoupper( $args['order'] ) === 'DESC' ? -1 : 1;
		usort(
			$list,
			function ( $a, $b ) use ( $orderby, $order ) {
				$va = $a[ $orderby ] ?? '';
				$vb = $b[ $orderby ] ?? '';
				if ( is_numeric( $va ) && is_numeric( $vb ) ) {
					return $order * ( (float) $va <=> (float) $vb );
				}
				return $order * strcmp( (string) $va, (string) $vb );
			}
		);

		// Limit
		if ( $args['limit'] > 0 ) {
			$list = array_slice( $list, 0, (int) $args['limit'] );
		}

		return $list;
	}
}

if ( ! function_exists( 'get_apollo_sheet_data' ) ) {
	/**
	 * Get the raw 2D data array for a sheet.
	 *
	 * @param string $sheet_id Sheet ID.
	 * @return array[]|false  2D array of cell values or false if not found.
	 *
	 * @example
	 *   $data = get_apollo_sheet_data( '1' );
	 *   if ( $data ) {
	 *       foreach ( $data as $row ) { echo implode( ', ', $row ) . "\n"; }
	 *   }
	 */
	function get_apollo_sheet_data( string $sheet_id ) {
		if ( '' === $sheet_id ) {
			return false;
		}
		$model = new \Apollo\Sheets\Model();
		$table = $model->load( $sheet_id, true, false );
		return $table ? ( $table['data'] ?? false ) : false;
	}
}
