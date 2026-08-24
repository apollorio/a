<?php

/**
 * Formula — Excel-like formula evaluation for sheet cells
 *
 * Supports: SUM, AVG, AVERAGE, MIN, MAX, COUNT, COUNTA, IF, ROUND, ABS,
 *           CONCAT, UPPER, LOWER, LEN, cell references (A1), ranges (A1:B3)
 *
 * Adapted from TablePress formula evaluation architecture.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Formula {


	/**
	 * The full 2D data array (used for cell reference resolution)
	 *
	 * @var array<int, array<int, string>>
	 */
	private array $data = array();

	/**
	 * Sheet ID (for referencing and error messages)
	 */
	private string $sheet_id = '';

	/**
	 * Maximum recursion depth to prevent circular references
	 */
	private const MAX_DEPTH = 10;

	/**
	 * Evaluate all formula cells in a 2D data array.
	 *
	 * Cells starting with '=' are formulas.
	 * Cells starting with "'=" are displayed as text (escaped).
	 *
	 * @param array  $data     2D data array.
	 * @param string $sheet_id Sheet ID for context.
	 * @return array Evaluated 2D data array.
	 */
	public function evaluate_table_data( array $data, string $sheet_id ): array {
		$this->data     = $data;
		$this->sheet_id = $sheet_id;

		$num_rows = count( $data );
		if ( 0 === $num_rows ) {
			return $data;
		}

		// First pass — evaluate all formulas
		foreach ( $data as $row_idx => $row ) {
			foreach ( $row as $col_idx => $cell ) {
				$cell = (string) $cell;
				if ( str_starts_with( $cell, '=' ) && ! str_starts_with( $cell, "'=" ) ) {
					$this->data[ $row_idx ][ $col_idx ] = $this->evaluate_cell( $cell, $row_idx, $col_idx );
				}
			}
		}

		/**
		 * Filters the table data after formula evaluation.
		 *
		 * @param array  $data     Evaluated data.
		 * @param string $sheet_id Sheet ID.
		 */
		return apply_filters( 'apollo/sheets/formula_evaluated_data', $this->data, $this->sheet_id );
	}

	/**
	 * Evaluate a single formula cell.
	 *
	 * @param string $formula  Formula string (including leading =).
	 * @param int    $row_idx  Row index (0-based).
	 * @param int    $col_idx  Column index (0-based).
	 * @param int    $depth    Recursion depth.
	 * @return string Evaluated result as string.
	 */
	private function evaluate_cell( string $formula, int $row_idx, int $col_idx, int $depth = 0 ): string {
		if ( $depth > self::MAX_DEPTH ) {
			return '#REF!';
		}

		// Strip leading =
		$expr = ltrim( $formula, '=' );
		$expr = trim( $expr );

		try {
			$result = $this->evaluate_expression( $expr, $depth );
			// Format numbers — remove trailing zeros
			if ( is_float( $result ) ) {
				$result = rtrim( rtrim( number_format( $result, 10, '.', '' ), '0' ), '.' );
			}
			return (string) $result;
		} catch ( \Exception $e ) {
			return '#ERROR!';
		}
	}

	/**
	 * Evaluate an expression string.
	 *
	 * @param string $expr  Expression (without leading =).
	 * @param int    $depth Recursion depth.
	 * @return string|float|int Result.
	 */
	private function evaluate_expression( string $expr, int $depth = 0 ) {
		$expr = trim( $expr );

		// ─── Function call: NAME(args)
		if ( preg_match( '/^([A-Z]+)\s*\((.*)?\)$/s', $expr, $m ) ) {
			return $this->evaluate_function( $m[1], $m[2] ?? '', $depth );
		}

		// ─── String literal "text"
		if ( preg_match( '/^"(.*)"$/s', $expr, $m ) ) {
			return $m[1];
		}

		// ─── Quoted formula escape '=  → text
		if ( str_starts_with( $expr, "'=" ) ) {
			return substr( $expr, 1 );
		}

		// ─── Cell reference A1
		if ( preg_match( '/^([A-Z]+)(\d+)$/i', $expr, $m ) ) {
			return $this->resolve_cell_ref( strtoupper( $m[1] ), (int) $m[2], $depth );
		}

		// ─── Number
		if ( is_numeric( $expr ) ) {
			return (float) $expr;
		}

		// ─── Arithmetic with operator precedence (handle + - * / ^)
		// Walk parentheses recursively first
		$result = $this->evaluate_arithmetic( $expr, $depth );
		return $result;
	}

	/**
	 * Very simple arithmetic evaluator: handles +, -, *, /, ^, parentheses.
	 * Uses the standard recursive descent parser pattern.
	 *
	 * @param string $expr
	 * @param int    $depth
	 * @return float
	 */
	private function evaluate_arithmetic( string $expr, int $depth ): float {
		$expr = trim( $expr );

		// Replace cell references
		$expr = preg_replace_callback(
			'/([A-Z]+)(\d+)/i',
			function ( $m ) use ( $depth ) {
				$val = $this->resolve_cell_ref( strtoupper( $m[1] ), (int) $m[2], $depth + 1 );
				return is_numeric( $val ) ? $val : '0';
			},
			$expr
		);

		// Replace string literals with 0 (simple approach)
		$expr = preg_replace( '/"[^"]*"/', '0', $expr );

		// Evaluate using a safe arithmetic tokenizer
		return $this->safe_arithmetic( $expr );
	}

	/**
	 * Safe arithmetic evaluator — no eval().
	 * Handles +, -, *, /, ^, unary minus, parentheses.
	 *
	 * @param string $expr
	 * @return float
	 */
	private function safe_arithmetic( string $expr ): float {
		$expr   = trim( $expr );
		$tokens = $this->tokenize( $expr );
		$pos    = 0;
		$result = $this->parse_expr( $tokens, $pos );
		return (float) $result;
	}

	/** @param string[] $tokens */
	private function parse_expr( array $tokens, int &$pos ): float {
		$result = $this->parse_term( $tokens, $pos );
		while ( $pos < count( $tokens ) && in_array( $tokens[ $pos ], array( '+', '-' ), true ) ) {
			$op     = $tokens[ $pos++ ];
			$rhs    = $this->parse_term( $tokens, $pos );
			$result = '+' === $op ? $result + $rhs : $result - $rhs;
		}
		return $result;
	}

	/** @param string[] $tokens */
	private function parse_term( array $tokens, int &$pos ): float {
		$result = $this->parse_power( $tokens, $pos );
		while ( $pos < count( $tokens ) && in_array( $tokens[ $pos ], array( '*', '/' ), true ) ) {
			$op  = $tokens[ $pos++ ];
			$rhs = $this->parse_power( $tokens, $pos );
			if ( '/' === $op ) {
				$result = ( 0.0 !== $rhs ) ? $result / $rhs : 0.0;
			} else {
				$result *= $rhs;
			}
		}
		return $result;
	}

	/** @param string[] $tokens */
	private function parse_power( array $tokens, int &$pos ): float {
		$result = $this->parse_unary( $tokens, $pos );
		if ( $pos < count( $tokens ) && '^' === $tokens[ $pos ] ) {
			++$pos;
			$exp    = $this->parse_unary( $tokens, $pos );
			$result = pow( $result, $exp );
		}
		return $result;
	}

	/** @param string[] $tokens */
	private function parse_unary( array $tokens, int &$pos ): float {
		if ( $pos < count( $tokens ) && '-' === $tokens[ $pos ] ) {
			++$pos;
			return -$this->parse_atom( $tokens, $pos );
		}
		if ( $pos < count( $tokens ) && '+' === $tokens[ $pos ] ) {
			++$pos;
		}
		return $this->parse_atom( $tokens, $pos );
	}

	/** @param string[] $tokens */
	private function parse_atom( array $tokens, int &$pos ): float {
		if ( $pos >= count( $tokens ) ) {
			return 0.0;
		}
		$tok = $tokens[ $pos ];
		if ( '(' === $tok ) {
			++$pos; // consume (
			$result = $this->parse_expr( $tokens, $pos );
			if ( $pos < count( $tokens ) && ')' === $tokens[ $pos ] ) {
				++$pos; // consume )
			}
			return $result;
		}
		++$pos;
		return is_numeric( $tok ) ? (float) $tok : 0.0;
	}

	/**
	 * Tokenize an arithmetic expression into numbers and operator symbols.
	 *
	 * @param string $expr
	 * @return string[]
	 */
	private function tokenize( string $expr ): array {
		$tokens = array();
		$i      = 0;
		$len    = strlen( $expr );
		$expr   = str_replace( ' ', '', $expr );
		$len    = strlen( $expr );

		while ( $i < $len ) {
			$ch = $expr[ $i ];

			if ( in_array( $ch, array( '+', '-', '*', '/', '^', '(', ')' ), true ) ) {
				$tokens[] = $ch;
				++$i;
			} elseif ( is_numeric( $ch ) || '.' === $ch ) {
				$num = '';
				while ( $i < $len && ( is_numeric( $expr[ $i ] ) || '.' === $expr[ $i ] ) ) {
					$num .= $expr[ $i++ ];
				}
				$tokens[] = $num;
			} else {
				++$i; // skip unknown chars
			}
		}

		return $tokens;
	}

	// ─── Function evaluation ────────────────────────────────────────

	/**
	 * Evaluate a named function call.
	 *
	 * @param string $name  Function name (uppercase).
	 * @param string $args  Raw arguments string.
	 * @param int    $depth Recursion depth.
	 * @return string|float Result.
	 */
	private function evaluate_function( string $name, string $args, int $depth ) {
		$parsed_args = $this->parse_arguments( $args, $depth );

		switch ( $name ) {
			case 'SUM':
				return array_sum( $this->flatten_to_numbers( $parsed_args ) );

			case 'AVG':
			case 'AVERAGE':
				$nums = $this->flatten_to_numbers( $parsed_args );
				return ! empty( $nums ) ? array_sum( $nums ) / count( $nums ) : 0;

			case 'MIN':
				$nums = $this->flatten_to_numbers( $parsed_args );
				return ! empty( $nums ) ? min( $nums ) : 0;

			case 'MAX':
				$nums = $this->flatten_to_numbers( $parsed_args );
				return ! empty( $nums ) ? max( $nums ) : 0;

			case 'COUNT':
				$nums = $this->flatten_to_numbers( $parsed_args );
				return count( $nums );

			case 'COUNTA':
				// Count non-empty cells (including text)
				$all = $this->flatten_all( $parsed_args );
				return count( array_filter( $all, fn( $v ) => '' !== $v ) );

			case 'ROUND':
				$nums = $this->flatten_all( $parsed_args );
				$val  = (float) ( $nums[0] ?? 0 );
				$dec  = (int) ( $nums[1] ?? 0 );
				return round( $val, $dec );

			case 'ABS':
				$nums = $this->flatten_to_numbers( $parsed_args );
				return abs( $nums[0] ?? 0 );

			case 'SQRT':
				$nums = $this->flatten_to_numbers( $parsed_args );
				$val  = (float) ( $nums[0] ?? 0 );
				return $val >= 0 ? sqrt( $val ) : '#NUM!';

			case 'IF':
				// IF(condition, value_if_true, value_if_false)
				$all  = $this->flatten_all( $parsed_args );
				$cond = $this->evaluate_expression( $all[0] ?? '0', $depth + 1 );
				if ( is_numeric( $cond ) ) {
					$cond = (float) $cond !== 0.0;
				} else {
					$cond = ! empty( $cond ) && 'FALSE' !== strtoupper( (string) $cond );
				}
				return $cond ? ( $all[1] ?? '' ) : ( $all[2] ?? '' );

			case 'CONCAT':
			case 'CONCATENATE':
				$all = $this->flatten_all( $parsed_args );
				return implode( '', $all );

			case 'UPPER':
				$all = $this->flatten_all( $parsed_args );
				return mb_strtoupper( (string) ( $all[0] ?? '' ) );

			case 'LOWER':
				$all = $this->flatten_all( $parsed_args );
				return mb_strtolower( (string) ( $all[0] ?? '' ) );

			case 'LEN':
				$all = $this->flatten_all( $parsed_args );
				return mb_strlen( (string) ( $all[0] ?? '' ) );

			case 'TRIM':
				$all = $this->flatten_all( $parsed_args );
				return trim( (string) ( $all[0] ?? '' ) );

			case 'LEFT':
				$all = $this->flatten_all( $parsed_args );
				$str = (string) ( $all[0] ?? '' );
				$n   = (int) ( $all[1] ?? 1 );
				return mb_substr( $str, 0, $n );

			case 'RIGHT':
				$all = $this->flatten_all( $parsed_args );
				$str = (string) ( $all[0] ?? '' );
				$n   = (int) ( $all[1] ?? 1 );
				return mb_substr( $str, -$n );

			case 'MID':
				$all   = $this->flatten_all( $parsed_args );
				$str   = (string) ( $all[0] ?? '' );
				$start = max( 0, ( (int) ( $all[1] ?? 1 ) ) - 1 );
				$len   = (int) ( $all[2] ?? 1 );
				return mb_substr( $str, $start, $len );

			case 'ISNUMBER':
				$all = $this->flatten_all( $parsed_args );
				return is_numeric( $all[0] ?? '' ) ? 'TRUE' : 'FALSE';

			case 'ISEMPTY':
				$all = $this->flatten_all( $parsed_args );
				return '' === ( $all[0] ?? '' ) ? 'TRUE' : 'FALSE';

			default:
				/**
				 * Filters unknown formula function calls — allows plugins to extend formula support.
				 *
				 * @param string|null $result     Default null (unhandled).
				 * @param string      $name       Function name.
				 * @param array       $parsed_args Parsed arguments.
				 * @param string      $sheet_id   Sheet ID.
				 */
				$custom = apply_filters( 'apollo/sheets/formula_function', null, $name, $parsed_args, $this->sheet_id );
				if ( null !== $custom ) {
					return $custom;
				}
				return "#NAME?({$name})";
		}
	}

	/**
	 * Parse comma-separated arguments string into array of evaluated values.
	 * Handles cell ranges like A1:B3 by expanding them.
	 *
	 * @param string $args_str Raw arguments string from function call.
	 * @param int    $depth    Recursion depth.
	 * @return array Parsed arguments (each may be scalar or array from range).
	 */
	private function parse_arguments( string $args_str, int $depth ): array {
		if ( '' === trim( $args_str ) ) {
			return array();
		}

		// Split by comma, but not commas inside parentheses
		$args    = array();
		$depth2  = 0;
		$current = '';
		for ( $i = 0; $i < strlen( $args_str ); $i++ ) {
			$ch = $args_str[ $i ];
			if ( '(' === $ch ) {
				++$depth2;
				$current .= $ch;
			} elseif ( ')' === $ch ) {
				--$depth2;
				$current .= $ch;
			} elseif ( ',' === $ch && 0 === $depth2 ) {
				$args[]  = trim( $current );
				$current = '';
			} else {
				$current .= $ch;
			}
		}
		if ( '' !== trim( $current ) ) {
			$args[] = trim( $current );
		}

		$result = array();
		foreach ( $args as $arg ) {
			// Cell range like A1:B3
			if ( preg_match( '/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/i', $arg, $m ) ) {
				$result[] = $this->resolve_range(
					strtoupper( $m[1] ),
					(int) $m[2],
					strtoupper( $m[3] ),
					(int) $m[4],
					$depth
				);
			}
			// Single cell ref A1
			elseif ( preg_match( '/^([A-Z]+)(\d+)$/i', $arg, $m ) ) {
				$result[] = $this->resolve_cell_ref( strtoupper( $m[1] ), (int) $m[2], $depth );
			}
			// Number or expression
			else {
				$result[] = $this->evaluate_expression( $arg, $depth + 1 );
			}
		}

		return $result;
	}

	// ─── Cell Reference Resolution ──────────────────────────────────

	/**
	 * Resolve a single cell reference like A1 to its value.
	 *
	 * @param string $col_letter Column letter(s) like A, B, AA.
	 * @param int    $row_num    Row number (1-based).
	 * @param int    $depth      Recursion depth.
	 * @return string Cell value (evaluated if it's also a formula).
	 */
	private function resolve_cell_ref( string $col_letter, int $row_num, int $depth ): string {
		$col_idx = $this->column_letter_to_index( $col_letter );
		$row_idx = $row_num - 1;

		if ( ! isset( $this->data[ $row_idx ][ $col_idx ] ) ) {
			return '0';
		}

		$val = (string) $this->data[ $row_idx ][ $col_idx ];

		// If the referenced cell is itself a formula, evaluate it
		if ( str_starts_with( $val, '=' ) && ! str_starts_with( $val, "'=" ) ) {
			$val = $this->evaluate_cell( $val, $row_idx, $col_idx, $depth + 1 );
		}

		return $val;
	}

	/**
	 * Resolve a cell range A1:B3 into a flat array of values.
	 *
	 * @param string $col1 Start column letter.
	 * @param int    $row1 Start row number (1-based).
	 * @param string $col2 End column letter.
	 * @param int    $row2 End row number (1-based).
	 * @param int    $depth Recursion depth.
	 * @return string[] Flat array of cell values.
	 */
	private function resolve_range( string $col1, int $row1, string $col2, int $row2, int $depth ): array {
		$col_start = $this->column_letter_to_index( $col1 );
		$col_end   = $this->column_letter_to_index( $col2 );
		$row_start = $row1 - 1;
		$row_end   = $row2 - 1;

		// Normalize
		if ( $col_start > $col_end ) {
			[$col_start, $col_end] = array( $col_end, $col_start );
		}
		if ( $row_start > $row_end ) {
			[$row_start, $row_end] = array( $row_end, $row_start );
		}

		$values = array();
		for ( $r = $row_start; $r <= $row_end; $r++ ) {
			for ( $c = $col_start; $c <= $col_end; $c++ ) {
				if ( isset( $this->data[ $r ][ $c ] ) ) {
					$val = (string) $this->data[ $r ][ $c ];
					if ( str_starts_with( $val, '=' ) && ! str_starts_with( $val, "'=" ) ) {
						$val = $this->evaluate_cell( $val, $r, $c, $depth + 1 );
					}
					$values[] = $val;
				}
			}
		}

		return $values;
	}

	// ─── Helpers ────────────────────────────────────────────────────

	/**
	 * Convert column letters to 0-based index.
	 * A=0, B=1, ..., Z=25, AA=26, AB=27, ...
	 *
	 * @param string $letter Column letters (e.g., A, B, AA, AZ).
	 * @return int 0-based column index.
	 */
	private function column_letter_to_index( string $letter ): int {
		$letter = strtoupper( $letter );
		$result = 0;
		$len    = strlen( $letter );
		for ( $i = 0; $i < $len; $i++ ) {
			$result = $result * 26 + ( ord( $letter[ $i ] ) - ord( 'A' ) + 1 );
		}
		return $result - 1;
	}

	/**
	 * Flatten parsed args to numeric values only.
	 *
	 * @param array $args
	 * @return float[]
	 */
	private function flatten_to_numbers( array $args ): array {
		$numbers = array();
		array_walk_recursive(
			$args,
			function ( $v ) use ( &$numbers ) {
				if ( is_numeric( $v ) ) {
					$numbers[] = (float) $v;
				}
			}
		);
		return $numbers;
	}

	/**
	 * Flatten parsed args to all values (including strings).
	 *
	 * @param array $args
	 * @return string[]
	 */
	private function flatten_all( array $args ): array {
		$all = array();
		array_walk_recursive(
			$args,
			function ( $v ) use ( &$all ) {
				$all[] = (string) $v;
			}
		);
		return $all;
	}
}
