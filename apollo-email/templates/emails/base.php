<?php

/**
 * Base email template wrapper — Apollo Design System V3.
 *
 * Official Apollo email shell: white background, Space Grotesk / Syne / Space Mono
 * typography, abstract arch modules, pill buttons, Apollo SVG footer crop.
 *
 * Variables available: $email_content, $brand_color, $brand_logo,
 * $site_name, $site_url, $footer_text, $footer_address,
 * $current_year, $unsubscribe_url, $preferences_url, $header_tag
 *
 * @package Apollo\Email
 * @since   2.0.0
 */

if (! defined('ABSPATH')) {
	exit;
}

$header_tag = $header_tag ?? 'Notificação do Sistema';
?>
<!DOCTYPE html>
<html lang="pt-BR" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<meta name="x-apple-disable-message-reformatting">
	<title><?php echo esc_html($site_name ?? 'Apollo Rio'); ?></title>
	<!--[if mso]>
	<style>
		table {border-collapse:collapse;border-spacing:0;border:none;margin:0;}
		div, td {padding:0;}
		div {margin:0 !important;}
	</style>
	<noscript>
		<xml>
			<o:OfficeDocumentSettings>
				<o:PixelsPerInch>96</o:PixelsPerInch>
			</o:OfficeDocumentSettings>
		</xml>
	</noscript>
	<![endif]-->
	<style>
		@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&family=Space+Mono:ital,wght@0,400;0,700;1,400;1,700&family=Syne:wght@400..700&display=swap');

		body,
		p,
		h1,
		h2,
		h3,
		h4,
		h5,
		h6 {
			margin: 0;
			padding: 0;
		}

		body {
			font-family: 'Space Grotesk', Arial, sans-serif;
			background-color: #f0ede6;
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
		}

		table {
			border-spacing: 0;
			border-collapse: collapse;
		}

		td {
			font-family: 'Space Grotesk', Arial, sans-serif;
		}

		img {
			border: 0;
			line-height: 100%;
			outline: none;
			text-decoration: none;
			display: block;
		}

		a {
			text-decoration: none;
		}

		/* Apollo Theme Classes */
		.syne-header {
			font-family: 'Syne', sans-serif;
			font-weight: 700;
			letter-spacing: -0.02em;
		}

		.mono-text {
			font-family: 'Space Mono', monospace;
		}

		/* Apollo Pill Button */
		.apollo-btn {
			display: inline-block;
			font-family: 'Space Grotesk', Arial, sans-serif;
			font-weight: 700;
			text-decoration: none;
			border-radius: 100px;
		}

		/* Footer SVG Crop */
		.aprio-ft-wrap {
			display: block;
			width: 100%;
			overflow: hidden;
			line-height: 0;
			margin-bottom: -2px;
		}

		.aprio-ft-wrap svg {
			display: block;
			width: 100%;
			height: auto;
			fill: rgba(var(--rgb-d), .055);
			stroke: rgba(var(--rgb-d), .125);
			stroke-width: 0.33px;
		}

		@media screen and (max-width: 600px) {
			.container {
				width: 100% !important;
				padding: 0 15px !important;
			}

			.stack-column {
				display: block !important;
				width: 100% !important;
				max-width: 100% !important;
				direction: ltr !important;
			}

			.stack-pad-bottom {
				padding-bottom: 20px !important;
			}

			.abstract-arch {
				border-radius: 120px 120px 20px 20px !important;
			}

			.two-col-mobile {
				display: table-cell !important;
				width: 48% !important;
			}
		}
	</style>
</head>

<body style="margin:0;padding:0;word-spacing:normal;background-color:#f0ede6;">
	<div role="article" aria-roledescription="email" lang="pt-BR" style="text-size-adjust:100%;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

		<!-- Main Wrapper -->
		<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
			<tr>
				<td align="center" style="padding: 40px 0 0 0;">

					<!-- Inner Container (Max 600px) -->
					<table class="container" width="600" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:600px;max-width:600px; background:#ffffff; border-radius:24px 24px 0 0; overflow:hidden;">
						<!-- TOP META BAR -->
						<tr>
							<td align="center" style="padding: 28px 40px 24px;">
								<p class="mono-text" style="color:#999; font-size:10px; margin:0; line-height:1.6; text-align:center; font-weight:200;">
									<?php if (! empty($unsubscribe_url)) : ?>
										<a href="<?php echo esc_url($unsubscribe_url); ?>" style="color:#666;text-decoration:none;font-weight:200;">Cancelar recebimentos</a>
										&nbsp;|&nbsp;
									<?php endif; ?>
									<?php if (! empty($site_url)) : ?>
										<a href="<?php echo esc_url($site_url); ?>" style="color:#666;text-decoration:none;font-weight:200;">Visualização alternativa</a>
									<?php else : ?>
										<span style="color:#666;">Apollo Rio</span>
									<?php endif; ?>
									<?php if (! empty($preferences_url)) : ?>
										&nbsp;|&nbsp;
										<a href="<?php echo esc_url($preferences_url); ?>" style="color:#666;text-decoration:none;font-weight:200;">Ajustes de usuárix</a>
									<?php endif; ?>
								</p>
							</td>
						</tr>

						<!-- EMAIL CONTENT MODULES -->
						<?php echo $email_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped in content templates 
						?>

						<!-- FOOTER -->
						<tr>
							<td style="padding: 36px 40px 40px; border-top: 1px solid #ebebeb;">
								<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
									<tr>
										<td valign="top" style="font-family:system-ui,-apple-system,sans-serif;font-size:11px;font-weight:500;color:#aaaaaa;line-height:1.7;letter-spacing:0.2px;">
											<?php echo esc_html($site_name ?? 'Apollo Rio'); ?><br>
											<?php echo esc_html($footer_address ?: 'Rio de Janeiro, RJ'); ?>
										</td>
										<td valign="top" align="right" style="font-family:system-ui,-apple-system,sans-serif;font-size:11px;font-weight:600;color:#aaaaaa;line-height:1.7;">
											Acesse <a href="<?php echo esc_url($site_url ?? 'https://apollo.rio.br'); ?>" style="text-decoration:none;font-weight:700;color:#181818;">apollo.rio.br</a><br>
											<?php if (! empty($unsubscribe_url)) : ?>
												N&atilde;o receber emails <a href="<?php echo esc_url($unsubscribe_url); ?>" style="text-decoration:none;font-weight:700;color:#181818;">aqui</a>
											<?php else : ?>
												&copy; <?php echo esc_html($current_year ?? gmdate('Y')); ?>
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
	</div>
</body>

</html>