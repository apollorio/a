<?php

/**
 * Shortcodes — [apollo-sheet] and [apollo-sheet-info]
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcodes {


	/**
	 * Register shortcodes
	 */
	public function register(): void {
		add_shortcode( 'apollo-sheet', array( $this, 'render_table' ) );
		add_shortcode( 'apollo-sheet-info', array( $this, 'render_info' ) );
	}

	/**
	 * [apollo-sheet] shortcode
	 *
	 * Usage:
	 *   [apollo-sheet id="1" /]
	 *   [apollo-sheet id="1" show_rows="1-5" hide_columns="3" datatables_filter="false" /]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string HTML table output.
	 */
	public function render_table( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'                          => '',
				'table_head'                  => null,
				'table_foot'                  => null,
				'alternating_row_colors'      => null,
				'print_name'                  => null,
				'print_name_position'         => null,
				'print_description'           => null,
				'print_description_position'  => null,
				'extra_css_classes'           => null,
				'use_datatables'              => null,
				'datatables_sort'             => null,
				'datatables_filter'           => null,
				'datatables_paginate'         => null,
				'datatables_paginate_entries' => null,
				'datatables_lengthchange'     => null,
				'datatables_info'             => null,
				'datatables_scrollx'          => null,
				'datatables_scrolly'          => null,
				'show_rows'                   => null,
				'show_columns'                => null,
				'hide_rows'                   => null,
				'hide_columns'                => null,
				'column_widths'               => null,
				'responsive'                  => null,
				'cache_table_output'          => null,
				'evaluate_formulas'           => null,
				'convert_line_breaks'         => null,
			),
			$atts,
			'apollo-sheet'
		);

		// Remove null values (not overridden by shortcode)
		$atts = array_filter( $atts, fn( $v ) => $v !== null );

		// Cast booleans
		$bool_keys = array(
			'alternating_row_colors',
			'print_name',
			'print_description',
			'use_datatables',
			'datatables_sort',
			'datatables_filter',
			'datatables_paginate',
			'datatables_lengthchange',
			'datatables_info',
			'datatables_scrollx',
			'responsive',
			'cache_table_output',
			'evaluate_formulas',
			'convert_line_breaks',
		);
		foreach ( $bool_keys as $key ) {
			if ( isset( $atts[ $key ] ) ) {
				$atts[ $key ] = filter_var( $atts[ $key ], FILTER_VALIDATE_BOOLEAN );
			}
		}

		// Cast integers
		$int_keys = array( 'table_head', 'table_foot', 'datatables_paginate_entries' );
		foreach ( $int_keys as $key ) {
			if ( isset( $atts[ $key ] ) ) {
				$atts[ $key ] = (int) $atts[ $key ];
			}
		}

		$render = new Render();
		return $render->shortcode_output( $atts );
	}

	/**
	 * [apollo-sheet-info] shortcode
	 *
	 * Usage:
	 *   [apollo-sheet-info id="1" field="name" /]
	 *   [apollo-sheet-info id="1" field="number_rows" /]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string Info value.
	 */
	public function render_info( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'    => '',
				'field' => 'name',
			),
			$atts,
			'apollo-sheet-info'
		);

		$id    = sanitize_text_field( $atts['id'] );
		$field = sanitize_key( $atts['field'] );

		if ( '' === $id ) {
			return '';
		}

		$allowed = array( 'name', 'description', 'last_modified', 'author', 'number_rows', 'number_columns' );
		if ( ! in_array( $field, $allowed, true ) ) {
			return '';
		}

		$value = apollo_sheets_get_info( $id, $field );

		if ( false === $value ) {
			return '';
		}

		return esc_html( (string) $value );
	}
}
