<?php
/**
 * Task deadline reminder — transactional shell (white / minimal).
 *
 * @package Apollo\Email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user      = esc_html( $user_name ?? 'usuário(a)' );
$task_name = esc_html( $task_name ?? 'Tarefa' );
$rows      = array();

if ( ! empty( $task_deadline_label ) ) {
	$rows[] = '<strong>Prazo:</strong> ' . esc_html( $task_deadline_label );
} elseif ( ! empty( $task_date ) ) {
	$rows[] = '<strong>Data:</strong> ' . esc_html( $task_date );
}
if ( ! empty( $task_priority ) ) {
	$rows[] = '<strong>Prioridade:</strong> ' . esc_html( $task_priority );
}
if ( ! empty( $task_project ) ) {
	$rows[] = '<strong>Projeto:</strong> ' . esc_html( $task_project );
}
if ( ! empty( $task_assigned_by ) ) {
	$rows[] = '<strong>Atribuída por:</strong> ' . esc_html( $task_assigned_by );
}

$middle_html = '';
if ( ! empty( $rows ) ) {
	$middle_html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td style="background-color:#f6f6f6;border-radius:12px;padding:20px 22px;">';
	foreach ( $rows as $row ) {
		$middle_html .= '<p style="font-family:system-ui,-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif;font-size:14px;line-height:1.7;color:#6e6e6e;margin:0 0 10px 0;">' . $row . '</p>';
	}
	$middle_html .= '</td></tr></table>';
}

$doc_title     = 'Apollo::Rio — Lembrete de tarefa';
$preview_text  = 'Lembrete: ' . $task_name;
$kicker        = 'Lembrete de Tarefa';
$headline      = $task_name;
$intro         = 'Olá, ' . $user . '! Esta tarefa precisa da sua atenção.';
$cta_url       = $task_url ?? ( $task_project_url ?? ( $site_url ?? '#' ) );
$cta_label     = 'Abrir tarefa';
$cta_merge_tag = 'task_url';
$show_notice   = false;

require __DIR__ . '/partials/transactional-layout.php';
