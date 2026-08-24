<?php
/**
 * Server-side render callback for the apollo-sheets/sheet Gutenberg block.
 *
 * $attributes is populated from block.json attribute definitions.
 *
 * @package Apollo\Sheets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Map block attributes to shortcode-compatible array
$atts = array(
	'id'                  => $attributes['id'] ?? '',
	'use_datatables'      => ! empty( $attributes['use_datatables'] ) ? '1' : '0',
	'datatables_sort'     => ! empty( $attributes['datatables_sort'] ) ? '1' : '0',
	'datatables_filter'   => ! empty( $attributes['datatables_filter'] ) ? '1' : '0',
	'datatables_paginate' => ! empty( $attributes['datatables_paginate'] ) ? '1' : '0',
	'paginate_entries'    => isset( $attributes['paginate_entries'] ) ? (string) absint( $attributes['paginate_entries'] ) : '10',
	'table_head'          => sanitize_key( $attributes['table_head'] ?? 'first_row' ),
	'show_rows'           => sanitize_text_field( $attributes['show_rows'] ?? '' ),
	'hide_columns'        => sanitize_text_field( $attributes['hide_columns'] ?? '' ),
	'alternating_colors'  => ! empty( $attributes['alternating_colors'] ) ? '1' : '0',
	'responsive'          => ! empty( $attributes['responsive'] ) ? '1' : '0',
	'evaluate_formulas'   => ! empty( $attributes['evaluate_formulas'] ) ? '1' : '0',
	'convert_line_breaks' => ! empty( $attributes['convert_line_breaks'] ) ? '1' : '0',
	'column_widths'       => sanitize_text_field( $attributes['column_widths'] ?? '' ),
	'extra_css'           => sanitize_text_field( $attributes['extra_css'] ?? '' ),
);

if ( empty( $atts['id'] ) ) {
	if ( is_admin() ) {
		echo '<p style="color:#aaa;padding:1em;border:1px dashed #ccc;">'
			. esc_html__( 'Apollo Sheet — selecione uma Sheet no painel lateral.', 'apollo-sheets' )
			. '</p>';
	}
	return;
}

$render = new \Apollo\Sheets\Render();
$html   = $render->shortcode_output( $atts );

// Wrap in block align class if present
$wrapper_attrs = get_block_wrapper_attributes();
echo '<div ' . $wrapper_attrs . '>' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
