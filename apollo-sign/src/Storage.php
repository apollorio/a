<?php
namespace Apollo\Sign;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage — handles certificate files and signature data on disk.
 */
final class Storage {

	public static function base_dir(): string {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['basedir'] ) . 'apollo-sign';
	}

	public static function base_url(): string {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['baseurl'] ) . 'apollo-sign';
	}

	public static function init_directories(): void {
		$dirs = array(
			self::base_dir(),
			self::base_dir() . '/certificates',
			self::base_dir() . '/signed',
			self::base_dir() . '/stamps',
			self::base_dir() . '/images',
			self::base_dir() . '/tmp',
		);

		foreach ( $dirs as $dir ) {
			if ( ! is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
		}

		/* Block direct access */
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
	 * Store a signed document (PKCS7 output).
	 */
	public static function save_signed( int $sig_id, string $data ): string {
		$path = self::base_dir() . '/signed/' . $sig_id . '.p7s';
		file_put_contents( $path, $data );
		return $path;
	}

	/**
	 * Load a signed document.
	 */
	public static function load_signed( int $sig_id ): ?string {
		$path = self::base_dir() . '/signed/' . $sig_id . '.p7s';
		return file_exists( $path ) ? file_get_contents( $path ) : null;
	}

	/**
	 * Store certificate PFX temporarily (for signing session).
	 */
	public static function save_temp_cert( string $filename, string $data ): string {
		$path = self::base_dir() . '/tmp/' . sanitize_file_name( $filename );
		file_put_contents( $path, $data );
		return $path;
	}

	/**
	 * Save hand-drawn/uploaded signature image (base64 PNG → file).
	 *
	 * @param int    $sig_id   Signature record ID.
	 * @param string $base64   Full data URL (data:image/png;base64,...) or raw base64.
	 * @return string|false    Path to saved PNG, or false on failure.
	 */
	public static function save_signature_image( int $sig_id, string $base64 ): string|false {
		/* Strip data URL prefix if present */
		if ( str_contains( $base64, ',' ) ) {
			$base64 = substr( $base64, strpos( $base64, ',' ) + 1 );
		}

		$data = base64_decode( $base64, true );
		if ( ! $data ) {
			return false;
		}

		$dir = self::base_dir() . '/images';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$path = $dir . '/sig_' . $sig_id . '.png';
		file_put_contents( $path, $data );

		return file_exists( $path ) ? $path : false;
	}

	/**
	 * Get path to saved signature image.
	 *
	 * @param int $sig_id Signature record ID.
	 * @return string|null Path or null.
	 */
	public static function get_signature_image_path( int $sig_id ): ?string {
		$path = self::base_dir() . '/images/sig_' . $sig_id . '.png';
		return file_exists( $path ) ? $path : null;
	}

	/**
	 * Get path to visual stamp PNG.
	 *
	 * @param int $sig_id Signature record ID.
	 * @return string|null Path or null.
	 */
	public static function get_stamp_path( int $sig_id ): ?string {
		$path = self::base_dir() . '/stamps/stamp_' . $sig_id . '.png';
		return file_exists( $path ) ? $path : null;
	}

	/**
	 * Cleanup temp files.
	 */
	public static function cleanup_tmp( int $hours = 1 ): int {
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
}
