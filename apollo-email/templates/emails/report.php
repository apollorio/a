<?php
/**
 * Contact / report form — transactional shell (white / minimal).
 *
 * @package Apollo\Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$name         = esc_html( $name ?? 'Anônimo' );
$email        = esc_html( $email ?? 'não informado' );
$subject_line = esc_html( $subject ?? 'Contato' );
$body_message = ! empty( $message ) ? nl2br( esc_html( $message ) ) : '';

$middle_html  = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
$middle_html .= '<tr><td style="background-color:#f6f6f6;border-radius:12px;padding:20px 22px;">';
$middle_html .= '<p style="font-family:system-ui,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:14px;line-height:1.7;color:#6e6e6e;margin:0 0 10px 0;"><strong>De:</strong> ' . $name . ' &lt;' . $email . '&gt;</p>';
if ( $body_message ) {
	$middle_html .= '<p style="font-family:system-ui,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:14px;line-height:1.7;color:#181818;margin:0;">' . $body_message . '</p>';
}
$middle_html .= '</td></tr></table>';

$doc_title     = 'Apollo::Rio — Nova mensagem';
$preview_text  = 'Nova mensagem de contato: ' . $subject_line;
$kicker        = 'Nova Mensagem';
$headline      = $subject_line;
$intro         = 'Você recebeu uma nova mensagem pelo formulário de contato.';
$show_cta      = false;
$show_fallback = false;
$show_notice   = false;

require __DIR__ . '/partials/transactional-layout.php';
