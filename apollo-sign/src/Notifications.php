<?php

namespace Apollo\Sign;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Email notifications for document signing events.
 *
 * Dispatches exclusively through apollo_send_email() → Sender::send() → TemplateEngine.
 * If apollo-email is not loaded, sends are aborted and logged — no raw wp_mail() fallback.
 */
final class Notifications {

	/* ── Public API ─────────────────────────────────────────── */

	/**
	 * Send a signing invitation to the signer referenced by $sig_id.
	 *
	 * @param  int $sig_id  Row ID in apollo_signatures.
	 * @return bool          True on successful dispatch.
	 */
	public static function invite( int $sig_id ): bool {
		$sig = self::get_sig( $sig_id );
		if ( ! $sig ) {
			return false;
		}

		$to        = $sig['signer_email'];
		$name      = $sig['signer_name'] ?: $to;
		$hash      = $sig['hash'];
		$doc_id    = (int) $sig['doc_id'];
		$doc_title = get_the_title( $doc_id ) ?: 'Documento #' . $doc_id;
		$sign_url  = home_url( '/assinar/' . $hash );
		$site_name = get_bloginfo( 'name' );
		$subject   = sprintf( '[%s] Assine o documento "%s"', $site_name, $doc_title );
		$html      = self::tpl_invite( $name, $doc_title, $sign_url, $site_name );

		return self::dispatch( $to, $subject, $html );
	}

	/**
	 * Notify document owner that a specific signer has signed.
	 *
	 * @param int $sig_id Row ID in apollo_signatures.
	 * @param int $doc_id WP post ID of the document.
	 */
	public static function notify_owner_signed( int $sig_id, int $doc_id ): void {
		$post = get_post( $doc_id );
		if ( ! $post ) {
			return;
		}

		$owner = get_userdata( (int) $post->post_author );
		if ( ! $owner ) {
			return;
		}

		$sig = self::get_sig( $sig_id );
		if ( ! $sig ) {
			return;
		}

		$to        = $owner->user_email;
		$doc_title = get_the_title( $doc_id ) ?: 'Documento #' . $doc_id;
		$site_name = get_bloginfo( 'name' );
		$signer    = $sig['signer_name'] ?: $sig['signer_email'];
		$subject   = sprintf( '[%s] "%s" foi assinado', $site_name, $doc_title );
		$doc_url   = home_url( '/documentos' );
		$html      = self::tpl_signed( $owner->display_name, $signer, $doc_title, $doc_url, $site_name );

		self::dispatch( $to, $subject, $html );
	}

	/**
	 * Notify the document owner that all signers have signed (document fully signed).
	 *
	 * @param int $doc_id WP post ID of the document.
	 */
	public static function notify_all_signed( int $doc_id ): void {
		$post = get_post( $doc_id );
		if ( ! $post ) {
			return;
		}

		$owner = get_userdata( (int) $post->post_author );
		if ( ! $owner ) {
			return;
		}

		$doc_title = get_the_title( $doc_id ) ?: 'Documento #' . $doc_id;
		$site_name = get_bloginfo( 'name' );
		$to        = $owner->user_email;
		$subject   = sprintf( '[%s] Documento "%s" totalmente assinado', $site_name, $doc_title );
		$doc_url   = home_url( '/documentos' );
		$html      = self::tpl_all_signed( $owner->display_name, $doc_title, $doc_url, $site_name );

		self::dispatch( $to, $subject, $html );
	}

	/* ── Internal helpers ────────────────────────────────────── */

	/**
	 * Fetch a signature row from the DB.
	 *
	 * @return array<string,mixed>|null
	 */
	private static function get_sig( int $sig_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}apollo_signatures WHERE id = %d",
				$sig_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	/**
	 * Dispatch email exclusively through apollo-email's Sender pipeline.
	 *
	 * Uses the 'notification' template so every signing email is wrapped in
	 * the V3 Apollo shell (from-address, logging, SMTP transport).
	 * If apollo-email is not loaded, the send is aborted and logged —
	 * no raw wp_mail() fallback exists by design.
	 */
	private static function dispatch( string $to, string $subject, string $html ): bool {
		/* Fire action so other plugins can intercept */
		do_action( 'apollo/sign/before_email', $to, $subject, $html );

		if ( ! function_exists( 'apollo_send_email' ) ) {
			error_log( sprintf(
				'[Apollo Sign] Email NOT sent — apollo-email plugin not loaded. To: %s | Subject: %s',
				$to,
				$subject
			) );
			return false;
		}

		// Route through apollo-email Sender (template = notification, message rendered via wp_kses_post).
		return apollo_send_email(
			$to,
			$subject,
			'notification',
			array(
				'user_name'   => '',
				'title'       => $subject,
				'message'     => $html,
				'action_url'  => '',
				'action_text' => '',
				'site_name'   => get_bloginfo( 'name' ),
			)
		);
	}

	/* ── Email templates ────────────────────────────────────── */

	/**
	 * Signing invitation email template.
	 */
	private static function tpl_invite(
		string $name,
		string $doc_title,
		string $sign_url,
		string $site_name
	): string {
		$esc_name = esc_html( $name );
		$esc_doc  = esc_html( $doc_title );
		$esc_site = esc_html( $site_name );
		$esc_url  = esc_url( $sign_url );

		return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:32px 0;background:#0a0a0a;font-family:system-ui,-apple-system,sans-serif">
  <div style="max-width:520px;margin:0 auto;background:#111;border:1px solid #222;border-radius:12px;overflow:hidden">
    <div style="background:#1a1a1a;padding:24px 32px;border-bottom:1px solid #222">
      <span style="font-size:18px;font-weight:700;color:#fff;letter-spacing:-0.5px">{$esc_site}</span>
    </div>
    <div style="padding:32px">
      <p style="color:#aaa;font-size:14px;margin:0 0 8px">Olá, <strong style="color:#fff">{$esc_name}</strong></p>
      <h2 style="color:#fff;font-size:20px;margin:0 0 16px;line-height:1.3">Você foi solicitado a assinar um documento</h2>
      <div style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:8px;padding:16px;margin-bottom:24px">
        <span style="color:#666;font-size:10px;text-transform:uppercase;letter-spacing:1.5px">DOCUMENTO</span>
        <p style="color:#fff;font-weight:600;margin:6px 0 0;font-size:15px">{$esc_doc}</p>
      </div>
      <a href="{$esc_url}"
         style="display:inline-block;background:#fff;color:#000;font-weight:700;font-size:14px;
                padding:14px 32px;border-radius:8px;text-decoration:none;letter-spacing:-0.3px">
        Assinar Documento →
      </a>
      <p style="color:#555;font-size:12px;margin:24px 0 0">
        Ou acesse diretamente:<br>
        <a href="{$esc_url}" style="color:#666;word-break:break-all">{$esc_url}</a>
      </p>
      <p style="color:#444;font-size:11px;margin:24px 0 0;padding-top:24px;border-top:1px solid #1f1f1f">
        Este link é válido apenas para você. Não compartilhe.
      </p>
    </div>
    <div style="background:#0d0d0d;padding:16px 32px;border-top:1px solid #1a1a1a">
      <p style="color:#444;font-size:11px;margin:0">{$esc_site} · Plataforma Apollo · Rio de Janeiro</p>
    </div>
  </div>
</body>
</html>
HTML;
	}

	/**
	 * "Signer X just signed" notification to the document owner.
	 */
	private static function tpl_signed(
		string $owner_name,
		string $signer,
		string $doc_title,
		string $doc_url,
		string $site_name
	): string {
		$esc_owner  = esc_html( $owner_name );
		$esc_signer = esc_html( $signer );
		$esc_doc    = esc_html( $doc_title );
		$esc_site   = esc_html( $site_name );
		$esc_url    = esc_url( $doc_url );

		return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:32px 0;background:#0a0a0a;font-family:system-ui,-apple-system,sans-serif">
  <div style="max-width:520px;margin:0 auto;background:#111;border:1px solid #222;border-radius:12px;overflow:hidden">
    <div style="background:#1a1a1a;padding:24px 32px;border-bottom:1px solid #222">
      <span style="font-size:18px;font-weight:700;color:#fff;letter-spacing:-0.5px">{$esc_site}</span>
    </div>
    <div style="padding:32px">
      <p style="color:#aaa;font-size:14px;margin:0 0 8px">Olá, <strong style="color:#fff">{$esc_owner}</strong></p>
      <h2 style="color:#fff;font-size:20px;margin:0 0 16px;line-height:1.3">Documento assinado ✓</h2>
      <div style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:8px;padding:16px;margin-bottom:24px">
        <span style="color:#666;font-size:10px;text-transform:uppercase;letter-spacing:1.5px">DOCUMENTO</span>
        <p style="color:#fff;font-weight:600;margin:6px 0 12px;font-size:15px">{$esc_doc}</p>
        <span style="color:#666;font-size:10px;text-transform:uppercase;letter-spacing:1.5px">ASSINADO POR</span>
        <p style="color:#aaa;margin:6px 0 0">{$esc_signer}</p>
      </div>
      <a href="{$esc_url}"
         style="display:inline-block;background:#fff;color:#000;font-weight:700;font-size:14px;
                padding:14px 32px;border-radius:8px;text-decoration:none;letter-spacing:-0.3px">
        Ver Documentos →
      </a>
    </div>
    <div style="background:#0d0d0d;padding:16px 32px;border-top:1px solid #1a1a1a">
      <p style="color:#444;font-size:11px;margin:0">{$esc_site} · Plataforma Apollo · Rio de Janeiro</p>
    </div>
  </div>
</body>
</html>
HTML;
	}

	/**
	 * "All signers completed" final notification to the document owner.
	 */
	private static function tpl_all_signed(
		string $owner_name,
		string $doc_title,
		string $doc_url,
		string $site_name
	): string {
		$esc_owner = esc_html( $owner_name );
		$esc_doc   = esc_html( $doc_title );
		$esc_site  = esc_html( $site_name );
		$esc_url   = esc_url( $doc_url );

		return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:32px 0;background:#0a0a0a;font-family:system-ui,-apple-system,sans-serif">
  <div style="max-width:520px;margin:0 auto;background:#111;border:1px solid #222;border-radius:12px;overflow:hidden">
    <div style="background:#1a1a1a;padding:24px 32px;border-bottom:1px solid #222">
      <span style="font-size:18px;font-weight:700;color:#fff;letter-spacing:-0.5px">{$esc_site}</span>
    </div>
    <div style="padding:32px">
      <p style="color:#aaa;font-size:14px;margin:0 0 8px">Olá, <strong style="color:#fff">{$esc_owner}</strong></p>
      <h2 style="color:#fff;font-size:20px;margin:0 0 16px;line-height:1.3">Assinaturas completas ✓✓</h2>
      <div style="background:#1a1a1a;border:1px solid #2a2a2a;border-radius:8px;padding:16px;margin-bottom:24px">
        <span style="color:#666;font-size:10px;text-transform:uppercase;letter-spacing:1.5px">DOCUMENTO</span>
        <p style="color:#fff;font-weight:600;margin:6px 0 0;font-size:15px">{$esc_doc}</p>
      </div>
      <p style="color:#aaa;font-size:14px;margin:0 0 24px">
        Todos os signatários assinaram. O documento está finalizado e com valor legal.
      </p>
      <a href="{$esc_url}"
         style="display:inline-block;background:#fff;color:#000;font-weight:700;font-size:14px;
                padding:14px 32px;border-radius:8px;text-decoration:none;letter-spacing:-0.3px">
        Ver Documentos →
      </a>
    </div>
    <div style="background:#0d0d0d;padding:16px 32px;border-top:1px solid #1a1a1a">
      <p style="color:#444;font-size:11px;margin:0">{$esc_site} · Plataforma Apollo · Rio de Janeiro</p>
    </div>
  </div>
</body>
</html>
HTML;
	}
}
