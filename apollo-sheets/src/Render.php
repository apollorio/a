<?php

/**
 * Render — HTML table rendering pipeline
 *
 * Pipeline: evaluate → prepare (hide rows/cols) → process (escaping, shortcodes) → render HTML
 *
 * Adapted from TablePress Render architecture.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Render {



	/**
	 * Collected DataTables init calls (printed in wp_footer)
	 *
	 * @var array
	 */
	private static array $dt_inits = array();

	/**
	 * Counter for unique HTML IDs when same sheet is rendered multiple times
	 */
	private static array $render_count = array();

	/**
	 * Sheet data input
	 */
	private array $table = array();

	/**
	 * Render options (merged shortcode atts + table options)
	 */
	private array $options = array();

	/**
	 * Span trigger keywords — matching TablePress convention.
	 * Cells with these values are merged with adjacent cells.
	 *
	 * @var array<string,string>
	 */
	private array $span_trigger = array(
		'colspan' => '#colspan#',
		'rowspan' => '#rowspan#',
		'span'    => '#span#',
	);

	/**
	 * Get default render options
	 */
	public static function get_default_render_options(): array {
		return array(
			'id'                          => '',
			'table_head'                  => 1,
			'table_foot'                  => 0,
			'alternating_row_colors'      => true,
			'print_name'                  => false,
			'print_name_position'         => 'above',
			'print_description'           => false,
			'print_description_position'  => 'below',
			'extra_css_classes'           => '',
			'use_datatables'              => true,
			'datatables_sort'             => true,
			'datatables_filter'           => true,
			'datatables_paginate'         => true,
			'datatables_paginate_entries' => 25,
			'datatables_lengthchange'     => true,
			'datatables_info'             => true,
			'datatables_scrollx'          => false,
			'datatables_scrolly'          => '',
			'show_rows'                   => '',
			'show_columns'                => '',
			'hide_rows'                   => '',
			'hide_columns'                => '',
			'column_widths'               => '',    // Pipe-delimited: '100px|150px|' or array
			'cache_table_output'          => false,
			'responsive'                  => true,
			'evaluate_formulas'           => true,  // Evaluate =SUM(), =AVG(), cell refs
			'convert_line_breaks'         => true,  // Convert \n to <br> in cells
		);
	}

	/**
	 * Get collected DataTables init scripts
	 */
	public static function get_datatables_inits(): array {
		return self::$dt_inits;
	}

	/**
	 * Main shortcode output method
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function shortcode_output( array $atts ): string {
		$id = $atts['id'] ?? '';
		if ( '' === $id ) {
			return '<!-- Apollo Sheets: no ID specified -->';
		}

		$model = new Model();
		$table = $model->load( $id );
		if ( ! $table ) {
			return '<!-- Apollo Sheets: sheet #' . esc_html( $id ) . ' not found -->';
		}

		// Merge: defaults ← table options ← shortcode atts
		$defaults      = self::get_default_render_options();
		$options       = array_merge( $defaults, $table['options'] ?? array(), $atts );
		$options['id'] = $id;

		// Check for cached output
		if ( $options['cache_table_output'] && ! is_user_logged_in() ) {
			$cache_key = 'apollo_sheet_' . $id . '_' . md5( wp_json_encode( $options ) );
			$cached    = get_transient( $cache_key );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$this->table   = $table;
		$this->options = $options;

		// Allow formula evaluation to be globally disabled via settings
		if ( class_exists( '\Apollo\Sheets\Settings' ) ) {
			$this->options['evaluate_formulas'] = $this->options['evaluate_formulas']
				&& \Apollo\Sheets\Settings::get( 'evaluate_formulas', true );
		}

		$output = $this->render();

		// Cache
		if ( $options['cache_table_output'] && ! is_user_logged_in() && ! empty( $output ) ) {
			set_transient( $cache_key, $output, DAY_IN_SECONDS );
		}

		return $output;
	}

	/**
	 * Full render pipeline
	 */
	private function render(): string {
		$data       = $this->table['data'];
		$options    = $this->options;
		$visibility = $this->table['visibility'] ?? array();
		$id         = $options['id'];

		// ─── 0. Evaluate formulas (must run on full data BEFORE hiding rows/cols)
		if ( ! empty( $options['evaluate_formulas'] ) ) {
			$formula_eval = new Formula();
			$data         = $formula_eval->evaluate_table_data( $data, $id );
			// Update table data in case other parts reference it
			$this->table['data'] = $data;
		}

		// Allow filters on span triggers
		$this->span_trigger = apply_filters( 'apollo/sheets/span_trigger_keywords', $this->span_trigger, $id );

		// ─── 1. Prepare: apply visibility & show/hide ranges
		$data = $this->prepare_data( $data, $visibility, $options );

		if ( empty( $data ) ) {
			return '<!-- Apollo Sheets: sheet #' . esc_html( $id ) . ' has no visible data -->';
		}

		$data = apply_filters( 'apollo/sheets/render_data', $data, $id, $options );

		// ─── 2. Process: escape, do_shortcode in cells
		$data = $this->process_data( $data );

		// ─── 3. Generate unique HTML ID
		if ( ! isset( self::$render_count[ $id ] ) ) {
			self::$render_count[ $id ] = 0;
		}
		++self::$render_count[ $id ];
		$html_id = 'apollo-sheet-' . esc_attr( $id );
		if ( self::$render_count[ $id ] > 1 ) {
			$html_id .= '-no-' . self::$render_count[ $id ];
		}

		// ─── 4. Build HTML
		$html = $this->build_table_html( $data, $html_id, $options );

		// ─── 5. Enqueue assets
		wp_enqueue_style( 'apollo-sheets' );

		// ─── 6. DataTables init
		if ( $options['use_datatables'] ) {
			$this->collect_datatables_init( $html_id, $options );
		}

		$html = apply_filters( 'apollo/sheets/table_output', $html, $id, $options );

		return $html;
	}

	/**
	 * Prepare data: remove hidden rows/columns
	 */
	private function prepare_data( array $data, array $visibility, array $options ): array {
		$total_rows = count( $data );
		$total_cols = ! empty( $data[0] ) ? count( $data[0] ) : 0;

		// Visibility arrays
		$vis_rows = $visibility['rows'] ?? array_fill( 0, $total_rows, 1 );
		$vis_cols = $visibility['columns'] ?? array_fill( 0, $total_cols, 1 );

		// Parse show/hide ranges
		$show_rows = $this->parse_range( $options['show_rows'] ?? '', $total_rows );
		$hide_rows = $this->parse_range( $options['hide_rows'] ?? '', $total_rows );
		$show_cols = $this->parse_range( $options['show_columns'] ?? '', $total_cols );
		$hide_cols = $this->parse_range( $options['hide_columns'] ?? '', $total_cols );

		// Determine visible rows
		$visible_rows = array();
		for ( $r = 0; $r < $total_rows; $r++ ) {
			$visible = (bool) ( $vis_rows[ $r ] ?? 1 );
			if ( ! empty( $show_rows ) ) {
				$visible = in_array( $r + 1, $show_rows, true );
			}
			if ( in_array( $r + 1, $hide_rows, true ) ) {
				$visible = false;
			}
			if ( $visible ) {
				$visible_rows[] = $r;
			}
		}

		// Determine visible columns
		$visible_cols = array();
		for ( $c = 0; $c < $total_cols; $c++ ) {
			$visible = (bool) ( $vis_cols[ $c ] ?? 1 );
			if ( ! empty( $show_cols ) ) {
				$visible = in_array( $c + 1, $show_cols, true );
			}
			if ( in_array( $c + 1, $hide_cols, true ) ) {
				$visible = false;
			}
			if ( $visible ) {
				$visible_cols[] = $c;
			}
		}

		// Build filtered data
		$filtered = array();
		foreach ( $visible_rows as $r ) {
			$row = array();
			foreach ( $visible_cols as $c ) {
				$row[] = $data[ $r ][ $c ] ?? '';
			}
			$filtered[] = $row;
		}

		return $filtered;
	}

	/**
	 * Parse a range string like "1-3,5,7-" into array of 1-based integers
	 */
	private function parse_range( string $range, int $max ): array {
		if ( '' === $range ) {
			return array();
		}

		$result = array();
		$parts  = explode( ',', $range );

		foreach ( $parts as $part ) {
			$part = trim( $part );
			if ( str_contains( $part, '-' ) ) {
				[$start, $end] = explode( '-', $part, 2 );
				$start         = (int) $start ?: 1;
				$end           = '' === $end ? $max : (int) $end;
				for ( $i = $start; $i <= min( $end, $max ); $i++ ) {
					$result[] = $i;
				}
			} else {
				$val = (int) $part;
				if ( $val >= 1 && $val <= $max ) {
					$result[] = $val;
				}
			}
		}

		return array_unique( $result );
	}

	/**
	 * Process data: sanitize output, run shortcodes in cells, apply nl2br.
	 *
	 * Span triggers (#colspan#, #rowspan#, #span#) are preserved AS-IS here
	 * (they are handled during HTML build, not escaped).
	 */
	private function process_data( array $data ): array {
		$convert_lb = (bool) ( $this->options['convert_line_breaks'] ?? true );

		foreach ( $data as $r => $row ) {
			foreach ( $row as $c => $cell ) {
				$cell = (string) $cell;

				// Preserve span triggers — skip processing
				if ( in_array( strtolower( trim( $cell ) ), $this->span_trigger, true ) ) {
					$data[ $r ][ $c ] = $cell;
					continue;
				}

				// Print formulas escaped with '= (like in Excel) as text
				if ( str_starts_with( $cell, "'=" ) ) {
					$cell = substr( $cell, 1 );
				}

				// Apply shortcodes inside cells
				$cell = do_shortcode( $cell );

				// Apply nl2br — convert line breaks to <br>
				if ( $convert_lb && str_contains( $cell, "\n" ) ) {
					$cell = nl2br( $cell );
				}

				// Allow filter per cell
				$cell = apply_filters( 'apollo/sheets/cell_content', $cell, $r, $c );

				$data[ $r ][ $c ] = $cell;
			}
		}
		return $data;
	}

	/**
	 * Build the <table> HTML
	 */
	private function build_table_html( array $data, string $html_id, array $options ): string {
		$total_rows = count( $data );
		$total_cols = ! empty( $data[0] ) ? count( $data[0] ) : 0;

		$head_rows  = (int) ( $options['table_head'] ?? 1 );
		$foot_rows  = (int) ( $options['table_foot'] ?? 0 );
		$body_start = $head_rows;
		$body_end   = $total_rows - $foot_rows;

		// CSS classes
		$classes   = array( 'apollo-sheet' );
		$classes[] = 'apollo-sheet-id-' . esc_attr( $options['id'] );
		if ( $options['use_datatables'] ) {
			$classes[] = 'apollo-sheet-dt';
		}
		if ( $options['alternating_row_colors'] ) {
			$classes[] = 'row-striped';
		}
		if ( $options['responsive'] ) {
			$classes[] = 'responsive';
		}
		if ( ! empty( $options['extra_css_classes'] ) ) {
			$extra   = array_map( 'sanitize_html_class', explode( ' ', $options['extra_css_classes'] ) );
			$classes = array_merge( $classes, $extra );
		}

		$classes = apply_filters( 'apollo/sheets/table_css_classes', $classes, $options['id'] );

		$output = '';

		// Caption (name/description)
		$caption_above = '';
		$caption_below = '';

		if ( $options['print_name'] ) {
			$name_html = '<h4 class="apollo-sheet-name">' . esc_html( $this->table['name'] ) . '</h4>';
			if ( 'above' === $options['print_name_position'] ) {
				$caption_above .= $name_html;
			} else {
				$caption_below .= $name_html;
			}
		}

		if ( $options['print_description'] && ! empty( $this->table['description'] ) ) {
			$desc_html = '<p class="apollo-sheet-description">' . esc_html( $this->table['description'] ) . '</p>';
			if ( 'above' === $options['print_description_position'] ) {
				$caption_above .= $desc_html;
			} else {
				$caption_below .= $desc_html;
			}
		}

		if ( $caption_above ) {
			$output .= '<div class="apollo-sheet-caption above">' . $caption_above . '</div>';
		}

		// Table tag
		$output .= '<div class="apollo-sheet-wrap">';
		$output .= '<table id="' . esc_attr( $html_id ) . '" class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		// Colgroup (column widths) — accepts pipe-delimited string OR array
		$raw_widths = $options['column_widths'] ?? '';
		if ( is_string( $raw_widths ) && '' !== $raw_widths ) {
			$widths = explode( '|', $raw_widths );
		} elseif ( is_array( $raw_widths ) ) {
			$widths = $raw_widths;
		} else {
			$widths = array();
		}

		if ( ! empty( array_filter( $widths ) ) ) {
			$output .= '<colgroup>';
			for ( $c = 0; $c < $total_cols; $c++ ) {
				$w = trim( $widths[ $c ] ?? '' );
				if ( $w ) {
					// Accept values like 150, 150px, 20%, 10em
					if ( is_numeric( $w ) ) {
						$w .= 'px';
					}
					$output .= '<col style="width:' . esc_attr( $w ) . '">';
				} else {
					$output .= '<col>';
				}
			}
			$output .= '</colgroup>';
		}

		// Thead
		if ( $head_rows > 0 ) {
			$head_data = array_slice( $data, 0, min( $head_rows, $total_rows ) );
			$output   .= '<thead>';
			$output   .= $this->render_section( $head_data, 'th', 0 );
			$output   .= '</thead>';
		}

		// Tfoot
		if ( $foot_rows > 0 ) {
			$foot_data = array_slice( $data, $body_end );
			$output   .= '<tfoot>';
			$output   .= $this->render_section( $foot_data, 'th', $body_end );
			$output   .= '</tfoot>';
		}

		// Tbody
		$body_data = array_slice( $data, $body_start, $body_end - $body_start );
		$output   .= '<tbody>';
		$output   .= $this->render_section( $body_data, 'td', $body_start, (bool) $options['alternating_row_colors'] );
		$output   .= '</tbody>';

		$output .= '</table>';
		$output .= '</div>'; // .apollo-sheet-wrap

		if ( $caption_below ) {
			$output .= '<div class="apollo-sheet-caption below">' . $caption_below . '</div>';
		}

		return $output;
	}

	/**
	 * Render a single <tr> with colspan/rowspan support.
	 *
	 * Span triggers:
	 *   #colspan# — skip this cell horizontally (merge with cell to the left)
	 *   #rowspan# — skip this cell vertically (merge with cell above)
	 *   #span#    — skip both directions
	 *
	 * @param array  $cells          Cell values.
	 * @param string $tag            'th' or 'td'.
	 * @param int    $row_index      0-based row index.
	 * @param string $extra_class    Additional row CSS class.
	 * @param int[]  $rowspan_counts Mutable array tracking active rowspans per column.
	 * @param array  $all_rows       All rows in the section (needed for rowspan counting).
	 * @param int    $section_row    Row index within section (for rowspan calc).
	 * @param int    $total_section  Total rows in section.
	 */
	private function render_row(
		array $cells,
		string $tag,
		int $row_index,
		string $extra_class = '',
		array &$rowspan_counts = array(),
		array $all_rows = array(),
		int $section_row = 0,
		int $total_section = 1
	): string {
		$classes = 'row-' . ( $row_index + 1 );
		if ( $extra_class ) {
			$classes .= ' ' . $extra_class;
		}

		$classes = apply_filters( 'apollo/sheets/row_css_class', $classes, $row_index );

		$html      = '<tr class="' . esc_attr( $classes ) . '">';
		$num_cells = count( $cells );

		for ( $c = 0; $c < $num_cells; $c++ ) {
			$cell = (string) $cells[ $c ];
			$lc   = strtolower( trim( $cell ) );

			// Skip cells consumed by a rowspan from above
			if ( ! empty( $rowspan_counts[ $c ] ) && $rowspan_counts[ $c ] > 1 ) {
				--$rowspan_counts[ $c ];
				continue;
			}

			// colspan-only trigger — skip (will be merged with left cell)
			if ( $lc === $this->span_trigger['colspan'] || $lc === $this->span_trigger['span'] ) {
				continue;
			}

			// rowspan-only trigger — skip (will be merged with cell above)
			if ( $lc === $this->span_trigger['rowspan'] ) {
				continue;
			}

			$cell_class = 'col-' . ( $c + 1 );
			$cell_class = apply_filters( 'apollo/sheets/cell_css_class', $cell_class, $row_index, $c );

			$attrs = '';

			// Calculate colspan: count consecutive #colspan#/#span# cells to the right
			$colspan = 1;
			for ( $cc = $c + 1; $cc < $num_cells; $cc++ ) {
				$next_lc = strtolower( trim( (string) ( $cells[ $cc ] ?? '' ) ) );
				if ( $next_lc === $this->span_trigger['colspan'] || $next_lc === $this->span_trigger['span'] ) {
					++$colspan;
				} else {
					break;
				}
			}
			if ( $colspan > 1 ) {
				$attrs .= ' colspan="' . $colspan . '"';
			}

			// Calculate rowspan: count consecutive #rowspan#/#span# cells below
			$rowspan = 1;
			if ( ! empty( $all_rows ) ) {
				for ( $rr = $section_row + 1; $rr < $total_section; $rr++ ) {
					$below_lc = strtolower( trim( (string) ( $all_rows[ $rr ][ $c ] ?? '' ) ) );
					if ( $below_lc === $this->span_trigger['rowspan'] || $below_lc === $this->span_trigger['span'] ) {
						++$rowspan;
					} else {
						break;
					}
				}
			}
			if ( $rowspan > 1 ) {
				$attrs               .= ' rowspan="' . $rowspan . '"';
				$rowspan_counts[ $c ] = $rowspan;
			}

			$html .= '<' . $tag . ' class="' . esc_attr( $cell_class ) . '"' . $attrs . '>' . $cell . '</' . $tag . '>';
		}

		$html .= '</tr>';

		return $html;
	}

	/**
	 * Render a section (thead/tbody/tfoot) with colspan/rowspan support.
	 *
	 * @param array  $rows  Rows in the section.
	 * @param string $tag   Cell tag: 'th' or 'td'.
	 * @param int    $base_row_index Global row index for CSS classes.
	 * @param bool   $alternating Whether to add odd/even classes.
	 * @return string HTML.
	 */
	private function render_section( array $rows, string $tag, int $base_row_index = 0, bool $alternating = false ): string {
		$html           = '';
		$rowspan_counts = array();
		$total_section  = count( $rows );

		foreach ( $rows as $i => $row ) {
			$extra = '';
			if ( $alternating ) {
				$extra = ( $i % 2 === 0 ) ? 'row-even' : 'row-odd';
			}
			$html .= $this->render_row(
				$row,
				$tag,
				$base_row_index + $i,
				$extra,
				$rowspan_counts,
				$rows,
				$i,
				$total_section
			);
		}

		return $html;
	}

	/**
	 * Collect DataTables init JS for this table
	 */
	private function collect_datatables_init( string $html_id, array $options ): void {
		$dt = array();

		if ( ! $options['datatables_sort'] ) {
			$dt['ordering'] = false;
		}

		if ( ! $options['datatables_filter'] ) {
			$dt['searching'] = false;
		}

		if ( $options['datatables_paginate'] ) {
			$dt['paging']     = true;
			$dt['pageLength'] = (int) $options['datatables_paginate_entries'];
		} else {
			$dt['paging'] = false;
		}

		if ( ! $options['datatables_lengthchange'] ) {
			$dt['lengthChange'] = false;
		}

		if ( ! $options['datatables_info'] ) {
			$dt['info'] = false;
		}

		if ( $options['datatables_scrollx'] ) {
			$dt['scrollX'] = true;
		}

		if ( $options['datatables_scrolly'] ) {
			$dt['scrollY'] = esc_js( $options['datatables_scrolly'] );
		}

		if ( $options['responsive'] ) {
			$dt['responsive'] = true;
		}

		// Language
		$locale         = determine_locale();
		$dt['language'] = array(
			'search'            => '',
			'searchPlaceholder' => __( 'Buscar...', 'apollo-sheets' ),
			'lengthMenu'        => __( 'Mostrar _MENU_ registros', 'apollo-sheets' ),
			'info'              => __( 'Mostrando _START_ a _END_ de _TOTAL_', 'apollo-sheets' ),
			'infoEmpty'         => __( 'Nenhum registro', 'apollo-sheets' ),
			'infoFiltered'      => __( '(filtrado de _MAX_ total)', 'apollo-sheets' ),
			'zeroRecords'       => __( 'Nenhum registro encontrado', 'apollo-sheets' ),
			'emptyTable'        => __( 'Nenhum dado disponível', 'apollo-sheets' ),
			'paginate'          => array(
				'first'    => '«',
				'previous' => '‹',
				'next'     => '›',
				'last'     => '»',
			),
		);

		$dt = apply_filters( 'apollo/sheets/datatables_params', $dt, $html_id, $options );

		$json = wp_json_encode( $dt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		self::$dt_inits[] = '$("#' . esc_js( $html_id ) . '").DataTable(' . $json . ');';
	}
}
