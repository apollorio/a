<?php

/**
 * Export — CSV, JSON, HTML table data export
 *
 * Adapted from TablePress export architecture.
 * Includes CSV injection protection.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Export {


	/**
	 * Supported export formats
	 */
	public const FORMATS = array( 'csv', 'json', 'html' );

	/**
	 * Characters that trigger CSV injection in spreadsheet apps
	 */
	private const CSV_INJECTION_CHARS = array( '=', '+', '-', '@', "\t", "\r", "\n" );

	/**
	 * Export a sheet by ID
	 *
	 * @param string $id     Sheet ID.
	 * @param string $format Export format: csv|json|html.
	 * @return string|false  Exported data string or false.
	 */
	public function export( string $id, string $format = 'csv' ): string|false {
		$model = new Model();
		$table = $model->load( $id );

		if ( ! $table || empty( $table['data'] ) ) {
			return false;
		}

		return $this->export_data( $table, $format );
	}

	/**
	 * Export table array to format
	 *
	 * @param array  $table  Full table array with data, name, description.
	 * @param string $format Export format.
	 * @return string|false Exported string or false.
	 */
	public function export_data( array $table, string $format = 'csv' ): string|false {
		return match ( $format ) {
			'csv'   => $this->export_csv( $table['data'] ),
			'json'  => $this->export_json( $table ),
			'html'  => $this->export_html( $table ),
			default => false,
		};
	}

	/**
	 * Generate download headers
	 *
	 * @param string $filename Base filename (without extension).
	 * @param string $format   Export format.
	 */
	public function send_download_headers( string $filename, string $format ): void {
		$ext  = $format;
		$mime = match ( $format ) {
			'csv'   => 'text/csv; charset=UTF-8',
			'json'  => 'application/json; charset=UTF-8',
			'html'  => 'text/html; charset=UTF-8',
			default => 'application/octet-stream',
		};

		$safe_name = sanitize_file_name( $filename ) . '.' . $ext;

		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Disposition: attachment; filename="' . $safe_name . '"' );
		header( 'Content-Transfer-Encoding: binary' );
	}

	// ─── CSV ────────────────────────────────────────────────────────

	/**
	 * Export data as CSV
	 *
	 * @param array $data 2D array.
	 * @return string CSV string.
	 */
	private function export_csv( array $data ): string {
		$handle = fopen( 'php://memory', 'r+' );

		// UTF-8 BOM for Excel compatibility
		fwrite( $handle, "\xEF\xBB\xBF" );

		foreach ( $data as $row ) {
			$safe_row = array_map( array( $this, 'sanitize_csv_cell' ), $row );
			fputcsv( $handle, $safe_row, ',', '"', '\\' );
		}

		rewind( $handle );
		$csv = stream_get_contents( $handle );
		fclose( $handle );

		return $csv;
	}

	/**
	 * Sanitize a cell value against CSV injection
	 *
	 * Prefixes dangerous characters with a single quote to prevent
	 * formula execution in spreadsheet applications.
	 */
	private function sanitize_csv_cell( string $cell ): string {
		$cell = (string) $cell;

		if ( '' === $cell ) {
			return $cell;
		}

		$first_char = $cell[0];
		if ( in_array( $first_char, self::CSV_INJECTION_CHARS, true ) ) {
			$cell = "'" . $cell;
		}

		return $cell;
	}

	// ─── JSON ───────────────────────────────────────────────────────

	/**
	 * Export data as JSON
	 *
	 * @param array $table Full table array.
	 * @return string JSON string.
	 */
	private function export_json( array $table ): string {
		$export = array(
			'name'        => $table['name'] ?? '',
			'description' => $table['description'] ?? '',
			'data'        => $table['data'] ?? array(),
			'options'     => $table['options'] ?? array(),
		);

		return wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}

	// ─── HTML ───────────────────────────────────────────────────────

	/**
	 * Export data as standalone HTML table
	 *
	 * @param array $table Full table array.
	 * @return string HTML string.
	 */
	private function export_html( array $table ): string {
		$data    = $table['data'] ?? array();
		$name    = esc_html( $table['name'] ?? '' );
		$options = $table['options'] ?? array();

		$head_rows = (int) ( $options['table_head'] ?? 1 );

		$html  = '<!DOCTYPE html>' . "\n";
		$html .= '<html lang="pt-BR">' . "\n";
		$html .= '<head><meta charset="UTF-8"><title>' . $name . '</title>' . "\n";
		$html .= '<style>';
		$html .= 'table{border-collapse:collapse;width:100%;font-family:sans-serif}';
		$html .= 'th,td{border:1px solid #ddd;padding:8px 12px;text-align:left}';
		$html .= 'thead th{background:#1a1a2e;color:#fff}';
		$html .= 'tbody tr:nth-child(even){background:#f8f8f8}';
		$html .= '</style>' . "\n";
		$html .= '</head>' . "\n";
		$html .= '<body>' . "\n";

		if ( $name ) {
			$html .= '<h1>' . $name . '</h1>' . "\n";
		}

		$html .= '<table>' . "\n";

		// Thead
		if ( $head_rows > 0 && ! empty( $data ) ) {
			$html .= '<thead>' . "\n";
			for ( $r = 0; $r < $head_rows && $r < count( $data ); $r++ ) {
				$html .= '<tr>';
				foreach ( $data[ $r ] as $cell ) {
					$html .= '<th>' . esc_html( (string) $cell ) . '</th>';
				}
				$html .= '</tr>' . "\n";
			}
			$html .= '</thead>' . "\n";
		}

		// Tbody
		$html .= '<tbody>' . "\n";
		for ( $r = $head_rows; $r < count( $data ); $r++ ) {
			$html .= '<tr>';
			foreach ( $data[ $r ] as $cell ) {
				$html .= '<td>' . esc_html( (string) $cell ) . '</td>';
			}
			$html .= '</tr>' . "\n";
		}
		$html .= '</tbody>' . "\n";

		$html .= '</table>' . "\n";
		$html .= '</body></html>';

		return $html;
	}

	// ─── Multi-sheet Export ─────────────────────────────────────────

	/**
	 * Export multiple sheets as individual files
	 *
	 * Returns an array of [filename => content] pairs.
	 *
	 * @param array  $ids    Array of sheet IDs.
	 * @param string $format Export format.
	 * @return array [ filename => content, ... ]
	 */
	public function export_multiple( array $ids, string $format = 'csv' ): array {
		$files = array();
		$model = new Model();

		foreach ( $ids as $id ) {
			$table = $model->load( (string) $id );
			if ( ! $table ) {
				continue;
			}

			$content = $this->export_data( $table, $format );
			if ( false === $content ) {
				continue;
			}

			$slug               = sanitize_title( $table['name'] ?: 'sheet-' . $id );
			$filename           = $slug . '.' . $format;
			$files[ $filename ] = $content;
		}

		return $files;
	}

	// ─── ZIP Export ─────────────────────────────────────────────────

	/**
	 * Export multiple sheets as a ZIP archive.
	 *
	 * Requires PHP ZipArchive extension.
	 * Each sheet becomes a separate file inside the ZIP.
	 *
	 * @param array  $ids          Array of sheet IDs to include. Empty = all sheets.
	 * @param string $format       Export format for each file: csv|json|html.
	 * @param string $zip_filename Base filename for the ZIP (without .zip).
	 * @return string|false        Path to the temp ZIP file, or false on failure.
	 */
	public function export_as_zip( array $ids = array(), string $format = 'csv', string $zip_filename = 'apollo-sheets-export' ): string|false {
		if ( ! class_exists( 'ZipArchive', false ) ) {
			return false;
		}

		// If no IDs given, export all sheets
		if ( empty( $ids ) ) {
			$model = new Model();
			$all   = $model->load_all();
			$ids   = array_column( $all ?: array(), 'id' );
		}

		$files = $this->export_multiple( $ids, $format );
		if ( empty( $files ) ) {
			return false;
		}

		$tmp_path = wp_tempnam( $zip_filename . '.zip' );

		$zip = new \ZipArchive();
		if ( $zip->open( $tmp_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) !== true ) {
			return false;
		}

		foreach ( $files as $filename => $content ) {
			$zip->addFromString( $filename, $content );
		}

		$zip->close();

		return $tmp_path;
	}

	/**
	 * Send a ZIP file as a download response and exit.
	 *
	 * @param string $zip_path   Absolute path to ZIP file.
	 * @param string $filename   Download filename (without .zip).
	 */
	public function send_zip_download( string $zip_path, string $filename = 'apollo-sheets-export' ): void {
		if ( ! is_readable( $zip_path ) ) {
			wp_die( esc_html__( 'Arquivo ZIP não encontrado.', 'apollo-sheets' ) );
		}

		$safe_name = sanitize_file_name( $filename ) . '.zip';

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . $safe_name . '"' );
		header( 'Content-Length: ' . filesize( $zip_path ) );
		header( 'Content-Transfer-Encoding: binary' );

		readfile( $zip_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile

		// Clean up temp file
		@unlink( $zip_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		exit;
	}

	/**
	 * Check if ZIP export is available on this server.
	 *
	 * @return bool
	 */
	public static function zip_available(): bool {
		return class_exists( 'ZipArchive', false );
	}
}
