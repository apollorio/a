<?php
/**
 * Weekly digest — transactional shell (white / minimal).
 *
 * @package Apollo\Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user          = esc_html( $user_name ?? 'usuário(a)' );
$notifications = $notifications ?? array();
$items_html    = '';

if ( is_array( $notifications ) && ! empty( $notifications ) ) {
	$items_html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
	foreach ( $notifications as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$label = esc_html( $item['title'] ?? ( $item['message'] ?? 'Atualização' ) );
		$url   = ! empty( $item['url'] ) ? esc_url( $item['url'] ) : '';
		$items_html .= '<tr><td style="padding:0 0 12px 0;">';
		$items_html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>';
		$items_html .= '<td style="background-color:#f6f6f6;border-radius:12px;padding:16px 20px;">';
		if ( $url ) {
			$items_html .= '<a href="' . $url . '" style="font-family:system-ui,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:14px;line-height:1.6;color:#181818;text-decoration:none;font-weight:600;">' . $label . '</a>';
		} else {
			$items_html .= '<span style="font-family:system-ui,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:14px;line-height:1.6;color:#181818;">' . $label . '</span>';
		}
		$items_html .= '</td></tr></table></td></tr>';
	}
	$items_html .= '</table>';
}

$middle_html = $items_html;

$doc_title     = 'Apollo::Rio — Resumo semanal';
$preview_text  = 'Seu resumo semanal na Apollo::Rio.';
$kicker        = 'Resumo Semanal';
$headline      = 'Seu resumo<br>da semana.';
$intro         = 'Olá, ' . $user . '! Aqui está o que aconteceu na plataforma:';
$cta_url       = $site_url ?? '#';
$cta_label     = 'Abrir Apollo::Rio';
$cta_merge_tag = 'site_url';
$show_notice   = false;

require __DIR__ . '/partials/transactional-layout.php';
