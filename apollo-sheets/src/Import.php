<?php

/**
 * Import — CSV, JSON, HTML table data import
 *
 * Adapted from TablePress import architecture.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Import {


	/**
	 * Supported import formats
	 */
	public const FORMATS = array( 'csv', 'json', 'html' );

	/**
	 * Import data and return table array
	 *
	 * @param string $data   Raw data string.
	 * @param string $format Format: csv|json|html (or empty for auto-detect).
	 * @return array|false   Table data array (2D) or false on failure.
	 */
	public function import( string $data, string $format = '' ): array|false {
		$data = trim( $data );
		if ( '' === $data ) {
			return false;
		}

		if ( '' === $format ) {
			$format = $this->detect_format( $data );
		}

		return match ( $format ) {
			'csv'   => $this->import_csv( $data ),
			'json'  => $this->import_json( $data ),
			'html'  => $this->import_html( $data ),
			default => false,
		};
	}

	/**
	 * Import from file upload ($_FILES)
	 *
	 * @param array  $file   $_FILES entry.
	 * @param string $format Format (empty = auto-detect from extension).
	 * @return array|false Table data or false.
	 */
	public function import_from_file( array $file, string $format = '' ): array|false {
		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return false;
		}

		$data = file_get_contents( $file['tmp_name'] );
		if ( false === $data ) {
			return false;
		}

		// Auto-detect format from extension
		if ( '' === $format && ! empty( $file['name'] ) ) {
			$ext    = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
			$format = match ( $ext ) {
				'csv', 'tsv', 'txt' => 'csv',
				'json'              => 'json',
				'html', 'htm'       => 'html',
				default             => '',
			};
		}

		return $this->import( $data, $format );
	}

	/**
	 * Import from a manually entered text string (form field).
	 *
	 * Equivalent to TablePress "Manual Input" import source.
	 *
	 * @param string $text   Raw data text (CSV, JSON, or HTML).
	 * @param string $format Format (empty = auto-detect).
	 * @return array|false Table data or false.
	 */
	public function import_from_form_field( string $text, string $format = '' ): array|false {
		$text = trim( $text );
		if ( '' === $text ) {
			return false;
		}
		return $this->import( $text, $format );
	}

	/**
	 * Import from a server-side filesystem path.
	 *
	 * Equivalent to TablePress "Server" import source.
	 * Only accessible to users with manage_options capability.
	 *
	 * @param string $path   Absolute server path to the file.
	 * @param string $format Format (empty = auto-detect from extension).
	 * @return array|WP_Error Table data or WP_Error on failure.
	 */
	public function import_from_server_path( string $path, string $format = '' ) {
		// Security: require elevated capability
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error( 'import_server_forbidden', __( 'Permissão insuficiente para importar por caminho de servidor.', 'apollo-sheets' ) );
		}

		// Prevent access to root or ABSPATH itself
		if ( '' === $path || ABSPATH === $path ) {
			return new \WP_Error( 'import_server_invalid_path', __( 'Caminho de arquivo inválido.', 'apollo-sheets' ) );
		}

		if ( ! is_readable( $path ) ) {
			return new \WP_Error( 'import_server_not_readable', __( 'Arquivo não encontrado ou sem permissão de leitura.', 'apollo-sheets' ) );
		}

		// Security: validate path is within WordPress directory
		$real_path    = realpath( $path );
		$real_abspath = realpath( ABSPATH );
		if ( false === $real_path || false === $real_abspath || ! str_starts_with( $real_path, $real_abspath ) ) {
			return new \WP_Error( 'import_server_path_outside_wp', __( 'O caminho deve estar dentro da instalação WordPress.', 'apollo-sheets' ) );
		}

		// Auto-detect format from extension
		if ( '' === $format ) {
			$ext    = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
			$format = match ( $ext ) {
				'csv', 'tsv', 'txt' => 'csv',
				'json'              => 'json',
				'html', 'htm'       => 'html',
				default             => '',
			};
		}

		$data = file_get_contents( $real_path );
		if ( false === $data ) {
			return new \WP_Error( 'import_server_read_failed', __( 'Falha ao ler o arquivo.', 'apollo-sheets' ) );
		}

		$result = $this->import( $data, $format );
		if ( false === $result ) {
			return new \WP_Error( 'import_server_parse_failed', __( 'Falha ao processar o arquivo.', 'apollo-sheets' ) );
		}

		return $result;
	}

	/**
	 * Import multiple sheets from a ZIP archive.
	 *
	 * Extracts all CSV/JSON/HTML files from the ZIP and imports each.
	 *
	 * @param string $zip_path Path to ZIP file (temp file from upload).
	 * @return array{tables: array[], errors: array} Results array.
	 */
	public function import_from_zip( string $zip_path ): array {
		$results = array(
			'tables' => array(),
			'errors' => array(),
		);

		// Try PHP ZipArchive first, fall back to PclZip
		if ( class_exists( 'ZipArchive', false ) ) {
			$zip = new \ZipArchive();
			if ( $zip->open( $zip_path ) !== true ) {
				$results['errors'][] = __( 'Não foi possível abrir o arquivo ZIP.', 'apollo-sheets' );
				return $results;
			}

			$supported_exts = array( 'csv', 'tsv', 'json', 'html', 'htm' );
			for ( $i = 0; $i < $zip->numFiles; $i++ ) {
				$name = $zip->getNameIndex( $i );
				// Skip directories and macOS metadata
				if ( str_ends_with( $name, '/' ) || str_starts_with( basename( $name ), '.' ) ) {
					continue;
				}
				$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
				if ( ! in_array( $ext, $supported_exts, true ) ) {
					continue;
				}

				$content = $zip->getFromIndex( $i );
				if ( false === $content ) {
					$results['errors'][] = sprintf( __( 'Falha ao ler "%s" do ZIP.', 'apollo-sheets' ), esc_html( $name ) );
					continue;
				}

				$format = match ( $ext ) {
					'csv', 'tsv', 'txt' => 'csv',
					'json'              => 'json',
					'html', 'htm'       => 'html',
					default             => '',
				};

				$table = $this->import( $content, $format );
				if ( false === $table ) {
					$results['errors'][] = sprintf( __( 'Falha ao processar "%s".', 'apollo-sheets' ), esc_html( $name ) );
					continue;
				}

				$results['tables'][] = array(
					'filename' => basename( $name ),
					'data'     => $table,
				);
			}

			$zip->close();
		} else {
			$results['errors'][] = __( 'Extensão ZipArchive não disponível no servidor.', 'apollo-sheets' );
		}

		return $results;
	}

	/**
	 * Import from URL
	 *
	 * @param string $url    URL to fetch.
	 * @param string $format Format (empty = auto-detect).
	 * @return array|false Table data or false.
	 */
	public function import_from_url( string $url, string $format = '' ): array|false {
		$url = esc_url_raw( $url );

		// Fix common URL mistakes from popular cloud services
		$url = $this->fix_common_url_mistakes( $url );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'   => 30,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return false;
		}

		$data = wp_remote_retrieve_body( $response );
		if ( empty( $data ) ) {
			return false;
		}

		return $this->import( $data, $format );
	}

	/**
	 * Fix common URL mistakes from popular cloud services.
	 *
	 * Currently supports:
	 * - Google Sheets: sharing URL → CSV export URL
	 * - Microsoft OneDrive: shared link → direct download
	 * - Dropbox: shared link → direct download
	 *
	 * Adapted from TablePress import class.
	 *
	 * @param string $url Original URL.
	 * @return string Fixed URL or original if no fix applies.
	 */
	private function fix_common_url_mistakes( string $url ): string {
		/**
		 * Filters whether common URL mistakes should be auto-fixed.
		 *
		 * @param bool $fix Whether to fix URLs. Default true.
		 */
		if ( ! apply_filters( 'apollo/sheets/import_fix_common_url_mistakes', true ) ) {
			return $url;
		}

		// Google Sheets: sharing URL → CSV export
		if ( str_contains( $url, 'docs.google.com/spreadsheets/' ) ) {
			// Already handled by dedicated method below
			return $this->transform_google_sheets_url( $url );
		}

		// Microsoft OneDrive: /edit or sharing link → direct download
		if ( str_starts_with( $url, 'https://1drv.ms/' ) && ! str_ends_with( $url, '&download=1' ) ) {
			return $url . '&download=1';
		}
		if ( str_contains( $url, 'onedrive.live.com' ) && str_contains( $url, 'resid=' ) ) {
			// Convert to direct download
			$url = str_replace( 'view.aspx', 'download.aspx', $url );
			return $url;
		}

		// Dropbox: dl=0 → dl=1
		if ( str_starts_with( $url, 'https://www.dropbox.com/' ) ) {
			if ( str_ends_with( $url, '&dl=0' ) ) {
				return str_replace( '&dl=0', '&dl=1', $url );
			}
			if ( str_ends_with( $url, '?dl=0' ) ) {
				return str_replace( '?dl=0', '?dl=1', $url );
			}
			if ( ! str_contains( $url, 'dl=1' ) ) {
				$sep = str_contains( $url, '?' ) ? '&' : '?';
				return $url . $sep . 'dl=1';
			}
		}

		return $url;
	}

	/**
	 * Transform Google Sheets URL to CSV export
	 *
	 * Handles:
	 *   https://docs.google.com/spreadsheets/d/{id}/edit...
	 *   https://docs.google.com/spreadsheets/d/{id}/pub...
	 *
	 * @param string $url Original URL.
	 * @return string Transformed URL or original.
	 */
	private function transform_google_sheets_url( string $url ): string {
		if ( ! str_contains( $url, 'docs.google.com/spreadsheets/' ) ) {
			return $url;
		}

		// Extract the document ID
		if ( preg_match( '#/spreadsheets/d/([a-zA-Z0-9_-]+)#', $url, $matches ) ) {
			$doc_id = $matches[1];

			// Extract gid if present
			$gid = '0';
			if ( preg_match( '#gid=(\d+)#', $url, $gid_matches ) ) {
				$gid = $gid_matches[1];
			}

			return 'https://docs.google.com/spreadsheets/d/' . $doc_id . '/export?format=csv&gid=' . $gid;
		}

		return $url;
	}

	// ─── Format Detection ───────────────────────────────────────────

	/**
	 * Auto-detect format from data content
	 */
	private function detect_format( string $data ): string {
		$trimmed = ltrim( $data );

		// JSON: starts with [ or {
		if ( str_starts_with( $trimmed, '[' ) || str_starts_with( $trimmed, '{' ) ) {
			$decoded = json_decode( $trimmed, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return 'json';
			}
		}

		// HTML: contains <table
		if ( preg_match( '/<table[\s>]/i', $trimmed ) ) {
			return 'html';
		}

		// Default: CSV
		return 'csv';
	}

	// ─── CSV Import ─────────────────────────────────────────────────

	/**
	 * Parse CSV data into 2D array
	 */
	private function import_csv( string $data ): array|false {
		// Normalize line endings
		$data = str_replace( array( "\r\n", "\r" ), "\n", $data );

		// Detect delimiter
		$delimiter = $this->detect_csv_delimiter( $data );

		$rows   = array();
		$handle = fopen( 'php://memory', 'r+' );
		fwrite( $handle, $data );
		rewind( $handle );

		while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
			$rows[] = array_map( fn( $cell ) => $cell ?? '', $row );
		}

		fclose( $handle );

		if ( empty( $rows ) ) {
			return false;
		}

		// Normalize column count (pad shorter rows)
		$max_cols = max( array_map( 'count', $rows ) );
		foreach ( $rows as &$row ) {
			while ( count( $row ) < $max_cols ) {
				$row[] = '';
			}
		}

		return $rows;
	}

	/**
	 * Detect CSV delimiter by sampling first lines
	 */
	private function detect_csv_delimiter( string $data ): string {
		$lines      = array_slice( explode( "\n", $data ), 0, 5 );
		$delimiters = array(
			','  => 0,
			';'  => 0,
			"\t" => 0,
			'|'  => 0,
		);

		foreach ( $lines as $line ) {
			foreach ( $delimiters as $d => &$count ) {
				$count += substr_count( $line, $d );
			}
		}

		arsort( $delimiters );
		$best = array_key_first( $delimiters );

		// If no delimiter found, default to comma
		return $delimiters[ $best ] > 0 ? $best : ',';
	}

	// ─── JSON Import ────────────────────────────────────────────────

	/**
	 * Parse JSON data into 2D array
	 *
	 * Accepts:
	 *   - 2D array: [[cell, cell], [cell, cell]]
	 *   - Array of objects: [{col1: val, col2: val}, ...]
	 *   - Single object: {col1: val, col2: val}
	 *   - Apollo/TablePress format: { data: [[...]], ... }
	 */
	private function import_json( string $data ): array|false {
		$decoded = json_decode( $data, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded ) ) {
			return false;
		}

		// Apollo/TablePress format: { data: [...] }
		if ( isset( $decoded['data'] ) && is_array( $decoded['data'] ) ) {
			$decoded = $decoded['data'];
		}

		// Empty array
		if ( empty( $decoded ) ) {
			return false;
		}

		// Single object → wrap as row
		if ( ! isset( $decoded[0] ) ) {
			$decoded = array( $decoded );
		}

		// Array of objects → convert to 2D with header row
		if ( is_array( $decoded[0] ) && ! isset( $decoded[0][0] ) && ! empty( $decoded[0] ) ) {
			$headers = array_keys( $decoded[0] );
			$rows    = array( $headers );
			foreach ( $decoded as $obj ) {
				$row = array();
				foreach ( $headers as $key ) {
					$row[] = (string) ( $obj[ $key ] ?? '' );
				}
				$rows[] = $row;
			}
			return $rows;
		}

		// Already 2D array
		if ( is_array( $decoded[0] ) ) {
			// Cast all cells to strings
			foreach ( $decoded as &$row ) {
				$row = array_map( fn( $c ) => (string) ( $c ?? '' ), $row );
			}

			// Normalize column count
			$max_cols = max( array_map( 'count', $decoded ) );
			foreach ( $decoded as &$row ) {
				while ( count( $row ) < $max_cols ) {
					$row[] = '';
				}
			}

			return $decoded;
		}

		return false;
	}

	// ─── HTML Import ────────────────────────────────────────────────

	/**
	 * Parse HTML <table> into 2D array
	 */
	private function import_html( string $data ): array|false {
		// Suppress libxml errors
		$prev = libxml_use_internal_errors( true );

		$dom = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $data, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		libxml_use_internal_errors( $prev );

		$tables = $dom->getElementsByTagName( 'table' );
		if ( 0 === $tables->length ) {
			return false;
		}

		// Use first table found
		$table = $tables->item( 0 );
		$rows  = array();

		// Process thead, tbody, tfoot rows
		foreach ( array( 'thead', 'tbody', 'tfoot' ) as $section_tag ) {
			$sections = $table->getElementsByTagName( $section_tag );
			for ( $s = 0; $s < $sections->length; $s++ ) {
				$trs = $sections->item( $s )->getElementsByTagName( 'tr' );
				for ( $r = 0; $r < $trs->length; $r++ ) {
					$rows[] = $this->parse_html_row( $trs->item( $r ) );
				}
			}
		}

		// If no sections, get direct <tr> children
		if ( empty( $rows ) ) {
			$trs = $table->getElementsByTagName( 'tr' );
			for ( $r = 0; $r < $trs->length; $r++ ) {
				$rows[] = $this->parse_html_row( $trs->item( $r ) );
			}
		}

		if ( empty( $rows ) ) {
			return false;
		}

		// Normalize column count
		$max_cols = max( array_map( 'count', $rows ) );
		foreach ( $rows as &$row ) {
			while ( count( $row ) < $max_cols ) {
				$row[] = '';
			}
		}

		return $rows;
	}

	/**
	 * Parse a single <tr> into array of cell values
	 */
	private function parse_html_row( \DOMNode $tr ): array {
		$cells = array();
		foreach ( $tr->childNodes as $node ) {
			if ( $node->nodeName === 'td' || $node->nodeName === 'th' ) {
				$cells[] = trim( $node->textContent );
			}
		}
		return $cells;
	}
}
