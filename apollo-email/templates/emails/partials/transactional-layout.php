<?php
/**
 * Apollo transactional email shell — white, minimal, system-ui (matches verification).
 *
 * Expected variables (set by each templates/emails/{slug}.php before include):
 * - $doc_title, $preview_text, $kicker, $headline, $intro
 * - $cta_url, $cta_label, $cta_merge_tag (optional, for CPT seeding)
 * - $show_cta (bool), $show_fallback (bool), $show_notice (bool)
 * - $notice_title, $notice_body
 * - $middle_html (optional extra block between intro and CTA)
 * - $site_name, $brand_logo, $unsubscribe_url (from TemplateEngine)
 *
 * @package Apollo\Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doc_title       = $doc_title ?? 'Apollo::Rio';
$preview_text    = $preview_text ?? '';
$kicker          = $kicker ?? '';
$headline        = $headline ?? '';
$intro           = $intro ?? '';
$cta_url         = $cta_url ?? '#';
$cta_label       = $cta_label ?? 'Continuar';
$cta_merge_tag   = $cta_merge_tag ?? '';
$show_cta        = $show_cta ?? ( '' !== $cta_url && '#' !== $cta_url );
$show_fallback   = $show_fallback ?? $show_cta;
$show_notice     = $show_notice ?? ( ! empty( $notice_title ) );
$notice_title    = $notice_title ?? '';
$notice_body     = $notice_body ?? '';
$middle_html     = $middle_html ?? '';
$fallback_url    = $fallback_url ?? $cta_url;

$logo_url = function_exists( 'apollo_email_brand_logo_url' )
	? apollo_email_brand_logo_url( (string) ( $brand_logo ?? '' ) )
	: 'https://assets.apollo.rio.br/img/logo/logo-apollo.png';

$cta_href = function_exists( 'apollo_email_template_href' )
	? apollo_email_template_href( $cta_url, $cta_merge_tag )
	: esc_url( $cta_url );

$fallback_href = function_exists( 'apollo_email_template_href' )
	? apollo_email_template_href( $fallback_url, $cta_merge_tag )
	: esc_url( $fallback_url );

$fallback_display = function_exists( 'apollo_email_template_link_display' )
	? apollo_email_template_link_display( $fallback_url, $cta_merge_tag )
	: (string) $fallback_url;
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="pt-BR">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?php echo esc_html( $doc_title ); ?></title>
	<!--[if mso]>
	<noscript>
		<xml>
			<o:OfficeDocumentSettings>
				<o:AllowPNG/>
				<o:PixelsPerInch>96</o:PixelsPerInch>
			</o:OfficeDocumentSettings>
		</xml>
	</noscript>
	<![endif]-->
	<style type="text/css">
		body, #bodyTable { margin:0!important; padding:0!important; width:100%!important; background-color:#ffffff!important; }
		img { border:0; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
		table { border-collapse:collapse!important; mso-table-lspace:0pt; mso-table-rspace:0pt; }
		.preview-hide { display:none!important; max-height:0!important; overflow:hidden!important; mso-hide:all!important; }
		@media only screen and (max-width:600px) {
			.mobile-pad { padding-left:24px!important; padding-right:24px!important; }
			h1.hero { font-size:36px!important; }
		}
	</style>
</head>
<body style="margin:0;padding:0;background-color:#ffffff;-webkit-font-smoothing:antialiased;" bgcolor="#ffffff">

<img src="https://assets.apollo.rio.br/img/avatar/email.png" width="48" height="48" style="border-radius:50%;vertical-align:middle;margin-right:8px;" alt="Apollo">

<?php if ( '' !== $preview_text ) : ?>
	<span class="preview-hide"><?php echo esc_html( $preview_text ); ?> &#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</span>
<?php endif; ?>

<table id="bodyTable" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="background-color:#ffffff!important;">
	<tr>
		<td align="center" valign="top" bgcolor="#ffffff" style="padding:40px 16px 48px 16px;background-color:#ffffff!important;">
			<table role="presentation" width="580" cellpadding="0" cellspacing="0" border="0" style="width:580px;max-width:580px;">

				<tr>
					<td style="padding:0 0 32px 0;">
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="Apollo::Rio" width="68" height="68" style="display:block;width:68px;height:68px;border:0;">
					</td>
				</tr>

				<tr>
					<td class="mobile-pad" style="padding:0 0 36px 0;border-bottom:1px solid #ebebeb;">
						<?php if ( '' !== $kicker ) : ?>
							<p style="font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#afafaf;margin:0 0 18px 0;mso-line-height-rule:exactly;"><?php echo esc_html( $kicker ); ?></p>
						<?php endif; ?>
						<?php if ( '' !== $headline ) : ?>
							<h1 class="hero" style="font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:46px;font-weight:900;letter-spacing:-1px;line-height:1.15;color:#181818;margin:0 0 20px 0;mso-line-height-rule:exactly;"><?php echo wp_kses( $headline, array( 'br' => array() ) ); ?></h1>
						<?php endif; ?>
						<?php if ( '' !== $intro ) : ?>
							<p style="font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;line-height:1.75;color:#6e6e6e;margin:0;mso-line-height-rule:exactly;max-width:460px;"><?php echo wp_kses_post( $intro ); ?></p>
						<?php endif; ?>
					</td>
				</tr>

				<?php if ( '' !== $middle_html ) : ?>
				<tr>
					<td class="mobile-pad" style="padding:36px 0 0 0;border-bottom:1px solid #ebebeb;">
						<?php echo $middle_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped fragments in callers ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if ( $show_cta || $show_fallback ) : ?>
				<tr>
					<td class="mobile-pad" style="padding:36px 0 40px 0;<?php echo $show_notice ? 'border-bottom:1px solid #ebebeb;' : ''; ?>">

						<?php if ( $show_cta ) : ?>
						<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td align="center" style="padding:0;">
									<table role="presentation" border="0" cellspacing="0" cellpadding="0" align="center" style="margin:0 auto;">
										<tr>
											<td align="center" bgcolor="#181818" style="border-radius:10px;mso-padding-alt:16px 32px;">
												<a href="<?php echo esc_url( $cta_href ); ?>" target="_blank" style="font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;padding:16px 32px;display:inline-block;letter-spacing:0.5px;border-radius:10px;"><?php echo esc_html( $cta_label ); ?></a>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
						<?php endif; ?>

						<?php if ( $show_fallback ) : ?>
						<p style="font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:14px;line-height:1.75;color:#6e6e6e;margin:<?php echo $show_cta ? '32px' : '0'; ?> 0 12px 0;mso-line-height-rule:exactly;">Se o botão não funcionar, clique no link abaixo ou copie e cole no seu navegador:</p>
						<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td style="background-color:#f6f6f6;border-radius:12px;padding:16px 20px;">
									<a href="<?php echo esc_url( $fallback_href ); ?>" target="_blank" style="font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:14px;line-height:1.5;color:#181818;text-decoration:none;word-break:break-all;display:block;"><?php echo esc_html( $fallback_display ); ?></a>
								</td>
							</tr>
						</table>
						<?php endif; ?>

					</td>
				</tr>
				<?php endif; ?>

				<?php if ( $show_notice ) : ?>
				<tr>
					<td class="mobile-pad" style="padding:36px 0 36px 0;border-bottom:1px solid #ebebeb;">
						<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#141414" style="background-color:#141414;border-radius:18px;">
							<tr>
								<td align="center" style="background-color:#141414;border-radius:18px;padding:28px 30px;">
									<p style="font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:16px;font-weight:500;letter-spacing:0.1px;line-height:1.3;color:#e8e8e8;margin:0 0 8px 0;mso-line-height-rule:exactly;"><?php echo esc_html( $notice_title ); ?></p>
									<p style="font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:14px;font-weight:400;color:#777677;margin:0;line-height:1.6;mso-line-height-rule:exactly;"><?php echo esc_html( $notice_body ); ?></p>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<?php endif; ?>

				<tr>
					<td style="padding:32px 0 0 0;">
						<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
							<tr>
								<td valign="top" style="font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:11px;font-weight:500;color:#aaaaaa;line-height:1.7;letter-spacing:0.2px;">
									<?php echo esc_html( $site_name ?? 'Apollo Rio' ); ?><br>Rio de Janeiro, RJ
								</td>
								<td valign="top" align="right" style="font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:11px;font-weight:600;color:#aaaaaa;line-height:1.7;">
									Acesse <a href="https://www.apollo.rio.br" style="text-decoration:none;font-weight:700;color:#181818;">apollo.rio.br</a><br>
									<?php if ( ! empty( $unsubscribe_url ) ) : ?>
										N&atilde;o receber emails <a href="<?php echo esc_url( $unsubscribe_url ); ?>" style="text-decoration:none;font-weight:700;color:#181818;">aqui</a>
									<?php endif; ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>

			</table>
		</td>
	</tr>
</table>

</body>
</html>
