<?php

namespace Apollo\Docs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage manager — filesystem directories for documents, PDFs, temp.
 */
final class Storage {

	/**
	 * Base upload directory for docs.
	 */
	public static function base_dir(): string {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['basedir'] ) . 'apollo-docs';
	}

	public static function base_url(): string {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['baseurl'] ) . 'apollo-docs';
	}

	/**
	 * Create all required directories.
	 */
	public static function init_directories(): void {
		$dirs = array(
			self::base_dir(),
			self::base_dir() . '/documents',
			self::base_dir() . '/pdfs',
			self::base_dir() . '/tmp',
		);

		foreach ( $dirs as $dir ) {
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
		}

		/* Protect directories with .htaccess + index.php */
		$htaccess = self::base_dir() . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Options -Indexes\nDeny from all\n" );
		}

		$index = self::base_dir() . '/index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
	}

	/**
	 * Get document storage path.
	 */
	public static function doc_path( int $doc_id, string $version = '1.0' ): string {
		$dir = self::base_dir() . '/documents/' . $doc_id;
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		return $dir . '/v' . $version . '.json';
	}

	/**
	 * Get PDF storage path.
	 */
	public static function pdf_path( int $doc_id, string $version = '1.0' ): string {
		$dir = self::base_dir() . '/pdfs/' . $doc_id;
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		return $dir . '/v' . $version . '.pdf';
	}

	/**
	 * Get temp directory path.
	 */
	public static function tmp_path( string $filename = '' ): string {
		return self::base_dir() . '/tmp/' . $filename;
	}

	/**
	 * Clean temp files older than N hours.
	 */
	public static function cleanup_tmp( int $hours = 24 ): int {
		$dir   = self::base_dir() . '/tmp';
		$count = 0;
		$now   = time();

		if ( ! is_dir( $dir ) ) {
			return 0;
		}

		$files = glob( $dir . '/*' );
		foreach ( $files as $file ) {
			if ( is_file( $file ) && ( $now - filemtime( $file ) ) > ( $hours * 3600 ) ) {
				unlink( $file );
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Store document content as versioned JSON.
	 */
	public static function save_document( int $doc_id, string $version, array $data ): string {
		$path = self::doc_path( $doc_id, $version );
		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		file_put_contents( $path, $json );
		return $path;
	}

	/**
	 * Load document content from versioned JSON.
	 */
	public static function load_document( int $doc_id, string $version ): ?array {
		$path = self::doc_path( $doc_id, $version );
		if ( ! file_exists( $path ) ) {
			return null;
		}
		$content = file_get_contents( $path );
		return json_decode( $content, true );
	}

	/**
	 * Calculate SHA-256 checksum for a file.
	 */
	public static function checksum( string $path ): string {
		if ( ! file_exists( $path ) ) {
			return '';
		}
		return hash_file( 'sha256', $path );
	}
}
