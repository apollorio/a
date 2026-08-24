<?php
/**
 * Event reminder — transactional shell (white / minimal).
 *
 * @package Apollo\Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user        = esc_html( $user_name ?? 'usuário(a)' );
$event_title = esc_html( $event_title ?? 'Evento' );
$details     = array();

if ( ! empty( $event_date ) ) {
	$line = '<strong>Data:</strong> ' . esc_html( $event_date );
	if ( ! empty( $event_time ) ) {
		$line .= ' &bull; ' . esc_html( $event_time );
	}
	$details[] = $line;
}
if ( ! empty( $loc_name ) ) {
	$details[] = '<strong>Local:</strong> ' . esc_html( $loc_name );
}

$middle_html = '';
if ( ! empty( $details ) ) {
	$middle_html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#f6f6f6;border-radius:12px;padding:20px 22px;">';
	foreach ( $details as $detail ) {
		$middle_html .= '<p style="font-family:system-ui,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:14px;line-height:1.7;color:#6e6e6e;margin:0 0 10px 0;">' . $detail . '</p>';
	}
	$middle_html .= '</td></tr></table>';
}

$doc_title     = 'Apollo::Rio — Lembrete de evento';
$preview_text  = 'Lembrete: ' . $event_title;
$kicker        = 'Lembrete de Evento';
$headline      = $event_title;
$intro         = 'Olá, ' . $user . '! Seu evento está chegando. Confira os detalhes abaixo.';
$cta_url       = $event_url ?? '#';
$cta_label     = 'Ver evento';
$cta_merge_tag = 'event_url';
$show_cta      = ! empty( $event_url );
$show_fallback = $show_cta;
$show_notice   = false;

require __DIR__ . '/partials/transactional-layout.php';
