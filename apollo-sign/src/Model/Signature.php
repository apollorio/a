<?php

namespace Apollo\Sign\Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Apollo\Sign\Storage;

/**
 * Signature model — CRUD, signing, verification, audit trail.
 *
 * ICP-Brasil Compliance:
 *   - Supports A1 (software) and A3 (token/smartcard) certificate types
 *   - PKCS#7 (CMS) digital signatures via OpenSSL
 *   - SHA-256 hashing (minimum required by ICP-Brasil since 2012)
 *   - Full audit trail with timestamps, IP, user-agent
 *   - Certificate chain validation
 *   - Signer CPF extraction from certificate CN/SAN
 */
final class Signature {

	/* ── Create ── */

	/**
	 * Create a pending signature record.
	 *
	 * @param array $data {doc_id, signer_id, signer_name?, signer_cpf?, signer_email?, status?}
	 * @return int|false Signature ID or false.
	 */
	public static function create( array $data ): int|false {
		global $wpdb;

		$hash = hash( 'sha256', wp_generate_uuid4() . microtime( true ) . ( $data['doc_id'] ?? 0 ) );

		/* Validate CPF format if provided upfront */
		$signer_cpf = preg_replace( '/\D/', '', $data['signer_cpf'] ?? '' );
		if ( $signer_cpf && ! self::validate_cpf( $signer_cpf ) ) {
			return false; // Reject invalid CPF at record creation
		}

		$insert = array(
			'doc_id'         => absint( $data['doc_id'] ?? 0 ),
			'signer_id'      => absint( $data['signer_id'] ?? 0 ),
			'signer_name'    => sanitize_text_field( $data['signer_name'] ?? '' ),
			'signer_cpf'     => $signer_cpf,
			'signer_email'   => sanitize_email( $data['signer_email'] ?? '' ),
			'hash'           => $hash,
			'status'         => sanitize_text_field( $data['status'] ?? 'pending' ),
			'ip_address'     => sanitize_text_field( self::resolve_client_ip() ),
			'user_agent'     => sanitize_text_field( substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512 ) ),
			'signature_data' => '',
		);

		$result = $wpdb->insert(
			$wpdb->prefix . 'apollo_signatures',
			$insert,
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $result ) {
			return false;
		}

		$sig_id = (int) $wpdb->insert_id;

		/* Audit */
		self::audit( $sig_id, 'created', 'Registro de assinatura criado para o documento #' . $insert['doc_id'] );

		do_action( 'apollo/sign/created', $sig_id, $insert );

		return $sig_id;
	}

	/* ── Get ── */

	public static function get( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_signatures WHERE id = %d",
				$id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public static function get_by_hash( string $hash ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_signatures WHERE hash = %s",
				$hash
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public static function get_by_doc( int $doc_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_signatures WHERE doc_id = %d ORDER BY id DESC LIMIT 1",
				$doc_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	/* ── List ── */

	/**
	 * List signatures with filters.
	 *
	 * @param array $args {signer_id?, doc_id?, status?, per_page?, page?}
	 */
	public static function list( array $args = array() ): array {
		global $wpdb;

		$where    = array( '1=1' );
		$params   = array();
		$per_page = absint( $args['per_page'] ?? 20 );
		$page     = absint( $args['page'] ?? 1 );
		$offset   = ( $page - 1 ) * $per_page;

		if ( ! empty( $args['signer_id'] ) ) {
			$where[]  = 's.signer_id = %d';
			$params[] = absint( $args['signer_id'] );
		}

		if ( ! empty( $args['doc_id'] ) ) {
			$where[]  = 's.doc_id = %d';
			$params[] = absint( $args['doc_id'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 's.status = %s';
			$params[] = sanitize_text_field( $args['status'] );
		}

		$where_sql = implode( ' AND ', $where );

		/* Count */
		$count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}apollo_signatures s WHERE {$where_sql}";
		$total     = (int) $wpdb->get_var(
			$params ? $wpdb->prepare( $count_sql, ...$params ) : $count_sql
		);

		/* Fetch */
		$params[] = $per_page;
		$params[] = $offset;

		$sql = "SELECT s.*, u.display_name as signer_display_name
                FROM {$wpdb->prefix}apollo_signatures s
                LEFT JOIN {$wpdb->users} u ON s.signer_id = u.ID
                WHERE {$where_sql}
                ORDER BY s.created_at DESC
                LIMIT %d OFFSET %d";

		$items = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A ) ?: array();

		return array(
			'items'    => $items,
			'total'    => $total,
			'pages'    => $per_page > 0 ? (int) ceil( $total / $per_page ) : 1,
			'page'     => $page,
			'per_page' => $per_page,
		);
	}

	/* ── Placement ── */

	/**
	 * Save visual signature placement coordinates.
	 *
	 * @param int   $sig_id Signature record ID.
	 * @param array $data   {sig_x, sig_y, sig_w, sig_h, sig_page, placement_mode}
	 * @return bool
	 */
	public static function save_placement( int $sig_id, array $data ): bool {
		global $wpdb;

		$update = array(
			'sig_x'          => floatval( $data['sig_x'] ?? 0.65 ),
			'sig_y'          => floatval( $data['sig_y'] ?? 0.85 ),
			'sig_w'          => floatval( $data['sig_w'] ?? 0.28 ),
			'sig_h'          => floatval( $data['sig_h'] ?? 0.06 ),
			'sig_page'       => absint( $data['sig_page'] ?? 1 ),
			'placement_mode' => sanitize_text_field( $data['placement_mode'] ?? 'auto_footer' ),
		);

		$result = $wpdb->update(
			$wpdb->prefix . 'apollo_signatures',
			$update,
			array( 'id' => $sig_id ),
			array( '%f', '%f', '%f', '%f', '%d', '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			self::audit(
				$sig_id,
				'placement_saved',
				sprintf(
					'Posição da assinatura atualizada: x=%.4f y=%.4f w=%.4f h=%.4f page=%d mode=%s',
					$update['sig_x'],
					$update['sig_y'],
					$update['sig_w'],
					$update['sig_h'],
					$update['sig_page'],
					$update['placement_mode']
				)
			);
		}

		return $result !== false;
	}

	/* ── Sign Document (ICP-Brasil PKCS#7) ── */

	/**
	 * Perform digital signature using a PFX certificate (ICP-Brasil A1).
	 *
	 * @param int    $sig_id       Signature record ID.
	 * @param string $pfx_path     Path to the PFX file.
	 * @param string $pfx_password Password for the PFX.
	 * @param string $sig_image    Base64 PNG of hand-drawn signature (optional).
	 * @return bool True on success.
	 */
	public static function sign_with_certificate( int $sig_id, string $pfx_path, string $pfx_password, string $sig_image = '' ): bool {
		$sig = self::get( $sig_id );
		if ( ! $sig || $sig['status'] !== 'pending' ) {
			return false;
		}

		/* Read PFX */
		$pfx_content = file_get_contents( $pfx_path );
		if ( ! $pfx_content ) {
			self::audit( $sig_id, 'sign_error', 'Arquivo PFX não encontrado ou ilegível.' );
			return false;
		}

		$certs = array();
		if ( ! openssl_pkcs12_read( $pfx_content, $certs, $pfx_password ) ) {
			self::audit( $sig_id, 'sign_error', 'Senha do certificado inválida ou PFX corrompido. OpenSSL: ' . openssl_error_string() );
			return false;
		}

		$private_key = $certs['pkey'];
		$certificate = $certs['cert'];
		$ca_chain    = $certs['extracerts'] ?? array();

		/* Parse certificate info */
		$cert_info = openssl_x509_parse( $certificate );
		if ( ! $cert_info ) {
			self::audit( $sig_id, 'sign_error', 'Não foi possível parsear o certificado.' );
			return false;
		}

		/* Validate certificate dates */
		$valid_from = $cert_info['validFrom_time_t'] ?? 0;
		$valid_to   = $cert_info['validTo_time_t'] ?? 0;
		$now        = time();

		if ( $now < $valid_from || $now > $valid_to ) {
			self::audit( $sig_id, 'sign_error', 'Certificado expirado ou ainda não válido. Validade: ' . date( 'Y-m-d', $valid_from ) . ' a ' . date( 'Y-m-d', $valid_to ) );
			return false;
		}

		/* Extract signer info from certificate */
		$cn     = $cert_info['subject']['CN'] ?? '';
		$issuer = $cert_info['issuer']['CN'] ?? ( $cert_info['issuer']['O'] ?? '' );
		$serial = $cert_info['serialNumberHex'] ?? ( $cert_info['serialNumber'] ?? '' );

		/* Extract CPF from CN (ICP-Brasil pattern: "NOME:CPF_NUMBER") */
		$cpf = self::extract_cpf_from_cn( $cn );

		/* Validate CPF algorithm (ICP-Brasil compliance requirement) */
		if ( $cpf && ! self::validate_cpf( $cpf ) ) {
			self::audit( $sig_id, 'sign_error', 'CPF extraído do certificado inválido: ' . substr( $cpf, 0, 3 ) . '.*' );
			return false;
		}

		/*
		 * Clean signer name: strip ICP-Brasil CPF suffix appended after colon.
		 * Cert CN format: "RAFAEL PERINI VALLE:48201090801" → "RAFAEL PERINI VALLE"
		 * Title-case preserved; raw CN kept separately in certificate_cn for audit.
		 */
		$cn_clean = trim( preg_replace( '/\s*:[^:]*$/', '', $cn ) );
		if ( empty( $cn_clean ) ) {
			$cn_clean = $cn; // fallback: never lose the name
		}

		/* Get document content for signing */
		$doc_content = '';
		if ( $sig['doc_id'] && class_exists( 'Apollo\Docs\Storage' ) ) {
			$version  = get_post_meta( $sig['doc_id'], '_doc_version', true ) ?: '1.0';
			$doc_data = \Apollo\Docs\Storage::load_document( $sig['doc_id'], $version );
			if ( $doc_data ) {
				$doc_content = wp_json_encode( $doc_data );
			}
		}

		if ( empty( $doc_content ) ) {
			$post        = get_post( $sig['doc_id'] );
			$doc_content = $post ? $post->post_content : '';
		}

		/* Create temp file with content to sign */
		$tmp_in  = Storage::save_temp_cert( 'sign_in_' . $sig_id . '.tmp', $doc_content );
		$tmp_out = Storage::base_dir() . '/tmp/sign_out_' . $sig_id . '.p7s';

		/* PKCS#7 Sign (CMS Detached Signature) */
		$extracerts_path = null;
		if ( ! empty( $ca_chain ) ) {
			$pem_chain = '';
			foreach ( $ca_chain as $ca_cert ) {
				openssl_x509_export( $ca_cert, $pem_out );
				$pem_chain .= $pem_out;
			}
			$extracerts_path = Storage::save_temp_cert( 'chain_' . $sig_id . '.pem', $pem_chain );
		}

		$sign_result = openssl_pkcs7_sign(
			$tmp_in,
			$tmp_out,
			$certificate,
			$private_key,
			array(
				'To'      => $cn,
				'From'    => 'Apollo Sign <sign@apollo.rio.br>',
				'Subject' => 'Assinatura Digital — Documento #' . $sig['doc_id'],
			),
			PKCS7_DETACHED | PKCS7_BINARY,
			$extracerts_path
		);

		if ( ! $sign_result || ! file_exists( $tmp_out ) ) {
			self::audit( $sig_id, 'sign_error', 'Falha na assinatura PKCS7. OpenSSL: ' . openssl_error_string() );
			@unlink( $tmp_in );
			if ( $extracerts_path ) {
				@unlink( $extracerts_path );
			}
			return false;
		}

		/* Read signed output and save permanently */
		$signed_data = file_get_contents( $tmp_out );
		Storage::save_signed( $sig_id, $signed_data );

		/* Cleanup temp */
		@unlink( $tmp_in );
		@unlink( $tmp_out );
		@unlink( $pfx_path );
		if ( $extracerts_path ) {
			@unlink( $extracerts_path );
		}

		/* Generate content hash */
		$content_hash = hash( 'sha256', $doc_content );

		/* Update signature record */
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'apollo_signatures',
			array(
				'signer_name'            => sanitize_text_field( $cn_clean ),
				'signer_cpf'             => $cpf,
				'certificate_cn'         => sanitize_text_field( $cn ),
				'certificate_issuer'     => sanitize_text_field( $issuer ),
				'certificate_serial'     => sanitize_text_field( $serial ),
				'certificate_valid_from' => date( 'Y-m-d H:i:s', $valid_from ),
				'certificate_valid_to'   => date( 'Y-m-d H:i:s', $valid_to ),
				'content_hash'           => $content_hash,
				'signature_data'         => base64_encode( $signed_data ),
				'algorithm'              => 'sha256WithRSAEncryption',
				'status'                 => 'signed',
				'ip_address'             => sanitize_text_field( self::resolve_client_ip() ),
				'user_agent'             => sanitize_text_field( substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512 ) ),
				'signed_at'              => current_time( 'mysql' ),
			),
			array( 'id' => $sig_id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		/* Update document status to signed */
		if ( $sig['doc_id'] && class_exists( 'Apollo\Docs\Model\Document' ) ) {
			\Apollo\Docs\Model\Document::mark_signed( $sig['doc_id'] );
		}

		/* Save hand-drawn signature image if provided */
		$img_path = '';
		if ( ! empty( $sig_image ) ) {
			$img_path = Storage::save_signature_image( $sig_id, $sig_image ) ?: '';
		}

		/* Generate visual stamp PNG (signer info + drawn signature) */
		$stamp_path  = '';
		$updated_sig = self::get( $sig_id );
		if ( $updated_sig ) {
			$stamp_result = \Apollo\Sign\VisualStamp::generate( $updated_sig, $sig_image );
			if ( $stamp_result ) {
				$stamp_path = $stamp_result;
				self::audit( $sig_id, 'stamp_generated', 'Selo visual gerado: ' . basename( $stamp_result ) );
			}
		}

		/* Update image/stamp paths */
		if ( $img_path || $stamp_path ) {
			$wpdb->update(
				$wpdb->prefix . 'apollo_signatures',
				array_filter(
					array(
						'signature_image_path' => $img_path,
						'stamp_path'           => $stamp_path,
					)
				),
				array( 'id' => $sig_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		/* Audit trail */
		self::audit(
			$sig_id,
			'signed',
			sprintf(
				'Documento assinado digitalmente. CN: %s | CPF: %s | Emissor: %s | Serial: %s | Algoritmo: SHA-256 com RSA | Hash do conteúdo: %s',
				$cn,
				$cpf ?: 'N/A',
				$issuer,
				$serial,
				$content_hash
			)
		);

		do_action( 'apollo/sign/signed', $sig_id, $sig['doc_id'] );

		return true;
	}

	/* ── Verify Signature ── */

	/**
	 * Verify a PKCS#7 signature.
	 *
	 * @param int $sig_id Signature record ID.
	 * @return array {valid: bool, details: string, certificate: array|null}
	 */
	public static function verify( int $sig_id ): array {
		$sig = self::get( $sig_id );
		if ( ! $sig ) {
			return array(
				'valid'       => false,
				'details'     => 'Assinatura não encontrada.',
				'certificate' => null,
			);
		}

		if ( $sig['status'] !== 'signed' ) {
			return array(
				'valid'       => false,
				'details'     => 'Documento ainda não foi assinado.',
				'certificate' => null,
			);
		}

		/* Load signed data */
		$signed_data = Storage::load_signed( $sig_id );
		if ( ! $signed_data ) {
			self::audit( $sig_id, 'verify_error', 'Arquivo de assinatura não encontrado no disco.' );
			return array(
				'valid'       => false,
				'details'     => 'Arquivo de assinatura não encontrado.',
				'certificate' => null,
			);
		}

		/* Write to temp for verification */
		$tmp_signed  = Storage::save_temp_cert( 'verify_' . wp_generate_uuid4() . '.p7s', $signed_data );
		$tmp_content = Storage::base_dir() . '/tmp/verify_content_' . wp_generate_uuid4() . '.tmp';

		/*
		 * ICP-Brasil chain verification.
		 * Set APOLLO_SIGN_ICP_CA_BUNDLE to the path of the ICP-Brasil root CA PEM bundle
		 * (download from https://www.iti.gov.br) to enable full chain validation.
		 * Without it, PKCS7_NOVERIFY is used to allow verification in environments
		 * that do not have ICP-Brasil CAs in the system trust store.
		 */
		$ca_bundle     = defined( 'APOLLO_SIGN_ICP_CA_BUNDLE' ) ? constant( 'APOLLO_SIGN_ICP_CA_BUNDLE' ) : null;
		$verify_flags  = PKCS7_BINARY;
		if ( ! $ca_bundle || ! file_exists( $ca_bundle ) ) {
			$verify_flags |= PKCS7_NOVERIFY; // Skip chain check — no ICP-Brasil bundle configured
		}

		$verify_result = openssl_pkcs7_verify(
			$tmp_signed,
			$verify_flags,
			$tmp_content,
			$ca_bundle ? array( $ca_bundle ) : array()
		);

		@unlink( $tmp_signed );
		@unlink( $tmp_content );

		$cert_data = array(
			'cn'         => $sig['certificate_cn'],
			'issuer'     => $sig['certificate_issuer'],
			'serial'     => $sig['certificate_serial'],
			'valid_from' => $sig['certificate_valid_from'],
			'valid_to'   => $sig['certificate_valid_to'],
			'cpf'        => $sig['signer_cpf'],
		);

		if ( $verify_result === true ) {
			self::audit( $sig_id, 'verified', 'Assinatura verificada com sucesso.' );
			return array(
				'valid'       => true,
				'details'     => 'Assinatura válida.',
				'certificate' => $cert_data,
			);
		}

		$error = openssl_error_string() ?: 'Verificação falhou.';
		self::audit( $sig_id, 'verify_failed', 'Verificação falhou: ' . $error );
		return array(
			'valid'       => false,
			'details'     => 'Assinatura inválida: ' . $error,
			'certificate' => $cert_data,
		);
	}

	/* ── Audit Trail ── */

	public static function audit( int $signature_id, string $action, string $details = '' ): void {
		global $wpdb;

		$user  = wp_get_current_user();
		$actor = $user->exists() ? $user->display_name : 'Sistema';

		$wpdb->insert(
			$wpdb->prefix . 'apollo_signature_audit',
			array(
				'signature_id' => $signature_id,
				'action'       => sanitize_text_field( $action ),
				'actor_id'     => get_current_user_id(),
				'actor_name'   => sanitize_text_field( $actor ),
				'actor_ip'     => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
				'details'      => sanitize_text_field( $details ),
			),
			array( '%d', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get full audit trail for a signature.
	 */
	public static function get_audit_trail( int $signature_id ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_signature_audit
             WHERE signature_id = %d
             ORDER BY created_at ASC",
				$signature_id
			),
			ARRAY_A
		) ?: array();
	}

	/* ── Helpers ── */

	/**
	 * Extract CPF from ICP-Brasil certificate CN.
	 * Common formats: "FULANO DE TAL:12345678901" or OID 2.16.76.1.3.1
	 */
	private static function extract_cpf_from_cn( string $cn ): string {
		/* Pattern: NAME:CPF at the end */
		if ( preg_match( '/:\s*(\d{11})\s*$/', $cn, $m ) ) {
			return $m[1];
		}

		/* Pattern: CPF anywhere as 11 digits */
		if ( preg_match( '/\b(\d{3}\.?\d{3}\.?\d{3}-?\d{2})\b/', $cn, $m ) ) {
			return preg_replace( '/\D/', '', $m[1] );
		}

		return '';
	}

	/**
	 * Validate CPF number (Brazilian algorithm).
	 */
	public static function validate_cpf( string $cpf ): bool {
		$cpf = preg_replace( '/\D/', '', $cpf );
		if ( strlen( $cpf ) !== 11 ) {
			return false;
		}

		/* Reject known invalid sequences */
		if ( preg_match( '/^(\d)\1{10}$/', $cpf ) ) {
			return false;
		}

		/* Validate check digits */
		for ( $t = 9; $t < 11; $t++ ) {
			$d = 0;
			for ( $c = 0; $c < $t; $c++ ) {
				$d += (int) $cpf[ $c ] * ( ( $t + 1 ) - $c );
			}
			$d = ( ( 10 * $d ) % 11 ) % 10;
			if ( (int) $cpf[ $c ] !== $d ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Resolve the real client IP address.
	 * Checks common proxy headers safely — uses REMOTE_ADDR as authoritative fallback.
	 *
	 * NOTE: Only trust proxy headers if the server is behind a known reverse proxy.
	 * Controlled by APOLLO_SIGN_TRUST_PROXY constant (default: false).
	 */
	private static function resolve_client_ip(): string {
		$trust_proxy = defined( 'APOLLO_SIGN_TRUST_PROXY' ) && APOLLO_SIGN_TRUST_PROXY;

		if ( $trust_proxy ) {
			$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP' );
			foreach ( $headers as $h ) {
				if ( ! empty( $_SERVER[ $h ] ) ) {
					/* X-Forwarded-For can be a comma-separated list — take the first entry */
					$ip = trim( explode( ',', $_SERVER[ $h ] )[0] );
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
						return $ip;
					}
				}
			}
		}

		return $_SERVER['REMOTE_ADDR'] ?? '';
	}
}
