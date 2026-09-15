<?php

namespace Apollo\Sign;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VisualStamp — Generates visual signature appearance on signed PDF.
 *
 * Adapted from:
 *   - _library/signing doc/signer-php-main/src/Application/DTO/SignatureAppearanceDto.php
 *   - _library/signing doc/pdf-signer-brazil-master/src/pdf/model/ (coordinate system)
 *   - _library/signing doc/advanced-pdf-esignature-main/assets/src/js/modules/pdfUtils.js (frame_signature)
 *
 * Coordinate conversion:
 *   Frontend canvas px (top-left) → DB fractions 0.0–1.0 → PDF points (72pt/in, bottom-left, Y inverted)
 *
 * The visual stamp is a PNG image composited from:
 *   1. Hand-drawn signature image (if provided)
 *   2. Signer metadata text (name, CPF, date, issuer)
 *   3. ICP-Brasil compliance badge
 *
 * @package Apollo\Sign
 * @version 1.2.0
 */
final class VisualStamp {

	/**
	 * Generate a visual stamp PNG from signature data + signer info.
	 *
	 * @param array  $sig_data  Signature record from DB.
	 * @param string $sig_image Base64 PNG of hand-drawn/uploaded signature (optional).
	 * @return string|false     Path to generated stamp PNG, or false on failure.
	 */
	public static function generate( array $sig_data, string $sig_image = '' ): string|false {
		if ( ! extension_loaded( 'gd' ) ) {
			return false;
		}

		$stamp_w = 600;
		$stamp_h = 200;

		/* Create canvas with transparent background */
		$im = imagecreatetruecolor( $stamp_w, $stamp_h );
		if ( ! $im ) {
			return false;
		}

		imagesavealpha( $im, true );
		imagealphablending( $im, false );
		$transparent = imagecolorallocatealpha( $im, 0, 0, 0, 127 );
		imagefill( $im, 0, 0, $transparent );
		imagealphablending( $im, true );

		/* Colors */
		$white      = imagecolorallocate( $im, 232, 232, 236 );
		$orange     = imagecolorallocate( $im, 244, 95, 0 );
		$gray       = imagecolorallocate( $im, 138, 138, 150 );
		$dark       = imagecolorallocate( $im, 26, 26, 30 );
		$border_clr = imagecolorallocatealpha( $im, 244, 95, 0, 80 );
		$bg_fill    = imagecolorallocatealpha( $im, 14, 14, 16, 40 );

		/* Background with subtle border */
		imagefilledrectangle( $im, 0, 0, $stamp_w - 1, $stamp_h - 1, $bg_fill );
		imagerectangle( $im, 0, 0, $stamp_w - 1, $stamp_h - 1, $border_clr );

		/* ── Left side: Signature image (if provided) ── */
		$text_start_x = 12;
		if ( ! empty( $sig_image ) ) {
			$sig_im = self::load_base64_image( $sig_image );
			if ( $sig_im ) {
				$sig_area_w = (int) ( $stamp_w * 0.38 );
				$sig_area_h = $stamp_h - 20;
				$sig_orig_w = imagesx( $sig_im );
				$sig_orig_h = imagesy( $sig_im );

				$scale = min( $sig_area_w / $sig_orig_w, $sig_area_h / $sig_orig_h );
				$dst_w = (int) ( $sig_orig_w * $scale );
				$dst_h = (int) ( $sig_orig_h * $scale );
				$dst_x = 10;
				$dst_y = (int) ( ( $stamp_h - $dst_h ) / 2 );

				imagecopyresampled( $im, $sig_im, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $sig_orig_w, $sig_orig_h );
				imagedestroy( $sig_im );

				/* Vertical separator */
				$sep_x = $sig_area_w + 16;
				imageline( $im, $sep_x, 10, $sep_x, $stamp_h - 10, $border_clr );
				$text_start_x = $sep_x + 10;
			}
		}

		/* ── Right side: Metadata text ── */
		$font_size = 3; // GD built-in font (1-5)
		$line_h    = 16;
		$y         = 14;

		/* Header: "ASSINATURA DIGITAL ICP-BRASIL" */
		imagestring( $im, 4, $text_start_x, $y, 'ASSINATURA DIGITAL ICP-BRASIL', $orange );
		$y += $line_h + 6;

		/* Separator line */
		imageline( $im, $text_start_x, $y, $stamp_w - 12, $y, $border_clr );
		$y += 8;

		/* Signer name */
		$name = mb_strtoupper( sanitize_text_field( $sig_data['signer_name'] ?? '' ), 'UTF-8' );
		if ( $name ) {
			imagestring( $im, $font_size, $text_start_x, $y, 'Assinado por: ' . self::ascii_safe( $name ), $white );
			$y += $line_h;
		}

		/* CPF (masked) */
		$cpf = $sig_data['signer_cpf'] ?? '';
		if ( $cpf && strlen( $cpf ) >= 11 ) {
			$masked = substr( $cpf, 0, 3 ) . '.***.***-' . substr( $cpf, 9, 2 );
			imagestring( $im, $font_size, $text_start_x, $y, 'CPF: ' . $masked, $gray );
			$y += $line_h;
		}

		/* Certificate issuer */
		$issuer = sanitize_text_field( $sig_data['certificate_issuer'] ?? '' );
		if ( $issuer ) {
			imagestring( $im, $font_size, $text_start_x, $y, 'Emissor: ' . self::ascii_safe( $issuer ), $gray );
			$y += $line_h;
		}

		/* Certificate serial */
		$serial = sanitize_text_field( $sig_data['certificate_serial'] ?? '' );
		if ( $serial ) {
			$serial_short = strlen( $serial ) > 24 ? substr( $serial, 0, 24 ) . '...' : $serial;
			imagestring( $im, 2, $text_start_x, $y, 'Serial: ' . $serial_short, $gray );
			$y += $line_h;
		}

		/* Signing date */
		$date = $sig_data['signed_at'] ?? current_time( 'mysql' );
		imagestring( $im, $font_size, $text_start_x, $y, 'Data: ' . wp_date( 'd/m/Y H:i:s', strtotime( $date ) ), $gray );
		$y += $line_h;

		/* Verification hash (shortened) */
		$hash = $sig_data['hash'] ?? '';
		if ( $hash ) {
			imagestring( $im, 1, $text_start_x, $y, 'Hash: ' . substr( $hash, 0, 32 ) . '...', $gray );
			$y += 12;
		}

		/* Verification URL */
		imagestring( $im, 1, $text_start_x, $y, 'Verificar: apollo.rio.br/assinar/' . substr( $hash, 0, 16 ) . '...', $orange );

		/* Save to disk */
		$sig_id = absint( $sig_data['id'] ?? 0 );
		$path   = Storage::base_dir() . '/stamps/stamp_' . $sig_id . '.png';

		/* Ensure stamps directory exists */
		$stamps_dir = Storage::base_dir() . '/stamps';
		if ( ! is_dir( $stamps_dir ) ) {
			wp_mkdir_p( $stamps_dir );
		}

		imagepng( $im, $path, 6 );
		imagedestroy( $im );

		return file_exists( $path ) ? $path : false;
	}

	/**
	 * Convert fractional placement coordinates → PDF points rect.
	 *
	 * PDF coordinate system:
	 *   - Origin at bottom-left
	 *   - Y axis goes UP (inverted from canvas)
	 *   - Units: points (72 points = 1 inch)
	 *
	 * Adapted from: _library/signing doc/pdf-signer-brazil-master/src/pdf/model/coordinate-data.ts
	 *   CoordinateData { left, bottom, right, top }
	 *
	 * @param array $placement {sig_x, sig_y, sig_w, sig_h} as fractions 0-1.
	 * @param float $page_w    PDF page width in points.
	 * @param float $page_h    PDF page height in points.
	 * @return array {left, bottom, right, top} in PDF points.
	 */
	public static function fractions_to_pdf_rect( array $placement, float $page_w = 595.28, float $page_h = 841.89 ): array {
		$x = floatval( $placement['sig_x'] ?? 0.65 );
		$y = floatval( $placement['sig_y'] ?? 0.85 );
		$w = floatval( $placement['sig_w'] ?? 0.28 );
		$h = floatval( $placement['sig_h'] ?? 0.06 );

		/* Canvas top-left → PDF bottom-left (Y inverted) */
		$left   = $x * $page_w;
		$top    = ( 1 - $y ) * $page_h;
		$right  = ( $x + $w ) * $page_w;
		$bottom = ( 1 - $y - $h ) * $page_h;

		return array(
			'left'   => round( $left, 2 ),
			'bottom' => round( $bottom, 2 ),
			'right'  => round( $right, 2 ),
			'top'    => round( $top, 2 ),
		);
	}

	/**
	 * Build the signer-php appearance rect array [llx, lly, urx, ury].
	 *
	 * Adapted from: _library/signing doc/signer-php-main/src/Application/DTO/SignatureAppearanceDto.php
	 *   rect = [llx, lly, urx, ury]
	 *
	 * @param array $placement Fractional placement data.
	 * @param float $page_w    PDF page width in points.
	 * @param float $page_h    PDF page height in points.
	 * @return array [llx, lly, urx, ury]
	 */
	public static function get_appearance_rect( array $placement, float $page_w = 595.28, float $page_h = 841.89 ): array {
		$coords = self::fractions_to_pdf_rect( $placement, $page_w, $page_h );

		return array(
			$coords['left'],
			$coords['bottom'],
			$coords['right'],
			$coords['top'],
		);
	}

	/**
	 * Load a base64-encoded PNG/JPEG image string into a GD resource.
	 *
	 * @param string $base64 Full data URL or raw base64 string.
	 * @return \GdImage|false
	 */
	private static function load_base64_image( string $base64 ): \GdImage|false {
		/* Strip data URL prefix if present */
		if ( str_contains( $base64, ',' ) ) {
			$base64 = substr( $base64, strpos( $base64, ',' ) + 1 );
		}

		$data = base64_decode( $base64, true );
		if ( ! $data ) {
			return false;
		}

		return @imagecreatefromstring( $data );
	}

	/**
	 * Convert string to ASCII-safe for GD imagestring (no multibyte).
	 *
	 * @param string $str
	 * @return string
	 */
	private static function ascii_safe( string $str ): string {
		$str = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $str );
		return $str ?: '';
	}
}
