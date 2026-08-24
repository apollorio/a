<?php
/**
 * Apollo Email — WP-CLI Commands.
 *
 * Usage:
 *   wp apollo email send <to> <subject> --template=<slug> --data=<json>
 *   wp apollo email test <to>
 *   wp apollo email queue:process [--batch=<n>]
 *   wp apollo email queue:stats
 *   wp apollo email queue:purge [--days=<n>]
 *   wp apollo email log:stats [--days=<n>]
 *   wp apollo email log:purge [--days=<n>]
 *   wp apollo email templates
 *
 * @package Apollo\Email\CLI
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email\CLI;

use Apollo\Email\Plugin;
use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EmailCommand {

	private Plugin $plugin;

	public function __construct() {
		$this->plugin = Plugin::instance();
	}

	/**
	 * Send an email.
	 *
	 * ## OPTIONS
	 *
	 * <to>
	 * : Recipient email address.
	 *
	 * <subject>
	 * : Email subject.
	 *
	 * [--template=<slug>]
	 * : Template slug to use.
	 *
	 * [--data=<json>]
	 * : JSON data for merge tags.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo email send user@example.com "Hello!" --template=welcome --data='{"username":"John"}'
	 *
	 * @synopsis <to> <subject> [--template=<slug>] [--data=<json>]
	 */
	public function send( array $args, array $assoc_args ): void {
		[$to, $subject] = $args;
		$template       = $assoc_args['template'] ?? '';
		$data           = array();

		if ( ! empty( $assoc_args['data'] ) ) {
			$data = json_decode( $assoc_args['data'], true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				WP_CLI::error( 'JSON de dados inválido: ' . json_last_error_msg() );
				return;
			}
		}

		if ( $template ) {
			$result = $this->plugin->sender()->sendTemplate( $to, $subject, $template, $data );
		} else {
			$message = \Apollo\Email\Mailer\Message::make( $to, $subject, '' );
			$result  = $this->plugin->sender()->send( $message );
		}

		if ( $result['success'] ) {
			WP_CLI::success( "E-mail enviado para {$to}" );
		} else {
			WP_CLI::error( 'Falha ao enviar: ' . ( $result['error'] ?? 'desconhecido' ) );
		}
	}

	/**
	 * Send a test email.
	 *
	 * ## OPTIONS
	 *
	 * <to>
	 * : Recipient email address.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo email test admin@example.com
	 *
	 * @synopsis <to>
	 */
	public function test( array $args ): void {
		$to     = $args[0];
		$result = $this->plugin->sender()->sendTest( $to );

		if ( $result['success'] ) {
			WP_CLI::success( "E-mail de teste enviado para {$to}" );
		} else {
			WP_CLI::error( 'Falha: ' . ( $result['error'] ?? 'desconhecido' ) );
		}
	}

	/**
	 * Process the email queue.
	 *
	 * ## OPTIONS
	 *
	 * [--batch=<n>]
	 * : Number of emails to process. Default: 50.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo email queue:process --batch=100
	 *
	 * @subcommand queue:process
	 * @synopsis [--batch=<n>]
	 */
	public function queue_process( array $args, array $assoc_args ): void {
		$batch = (int) ( $assoc_args['batch'] ?? APOLLO_EMAIL_BATCH_SIZE );

		WP_CLI::log( "Processando fila (batch={$batch})..." );

		$this->plugin->queue()->processNext();

		$stats = $this->plugin->queue()->getStats();
		WP_CLI::success(
			sprintf(
				'Fila processada. Pendentes: %d | Enviados: %d | Falhas: %d',
				$stats['pending'] ?? 0,
				$stats['sent'] ?? 0,
				$stats['failed'] ?? 0
			)
		);
	}

	/**
	 * Show queue statistics.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo email queue:stats
	 *
	 * @subcommand queue:stats
	 */
	public function queue_stats(): void {
		$stats = $this->plugin->queue()->getStats();

		WP_CLI::log( '─── Fila de Envio ───' );
		foreach ( $stats as $status => $count ) {
			WP_CLI::log( sprintf( '  %-12s %d', ucfirst( $status ) . ':', $count ) );
		}
	}

	/**
	 * Purge old queue entries.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<n>]
	 * : Remove items older than N days. Default: 30.
	 *
	 * @subcommand queue:purge
	 * @synopsis [--days=<n>]
	 */
	public function queue_purge( array $args, array $assoc_args ): void {
		$days    = (int) ( $assoc_args['days'] ?? 30 );
		$deleted = $this->plugin->queue()->purge( $days );

		WP_CLI::success( "{$deleted} itens removidos da fila (>{$days} dias)." );
	}

	/**
	 * Show log statistics.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<n>]
	 * : Stats for the last N days. Default: 30.
	 *
	 * @subcommand log:stats
	 * @synopsis [--days=<n>]
	 */
	public function log_stats( array $args, array $assoc_args ): void {
		$days  = (int) ( $assoc_args['days'] ?? 30 );
		$stats = $this->plugin->logger()->getStats( $days );

		WP_CLI::log( "─── Log de Envios (últimos {$days} dias) ───" );
		WP_CLI::log( sprintf( '  Total:      %d', $stats['total'] ?? 0 ) );
		WP_CLI::log( sprintf( '  Enviados:   %d', $stats['sent'] ?? 0 ) );
		WP_CLI::log( sprintf( '  Falhas:     %d', $stats['failed'] ?? 0 ) );
		WP_CLI::log( sprintf( '  Aberturas:  %d (%.1f%%)', $stats['opened'] ?? 0, $stats['open_rate'] ?? 0 ) );
		WP_CLI::log( sprintf( '  Cliques:    %d (%.1f%%)', $stats['clicked'] ?? 0, $stats['click_rate'] ?? 0 ) );
	}

	/**
	 * Purge old log entries.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<n>]
	 * : Remove entries older than N days. Default: 90.
	 *
	 * @subcommand log:purge
	 * @synopsis [--days=<n>]
	 */
	public function log_purge( array $args, array $assoc_args ): void {
		$days    = (int) ( $assoc_args['days'] ?? 90 );
		$deleted = $this->plugin->logger()->purge( $days );

		WP_CLI::success( "{$deleted} registros removidos do log (>{$days} dias)." );
	}

	/**
	 * List all email templates.
	 *
	 * ## EXAMPLES
	 *
	 *     wp apollo email templates
	 */
	public function templates(): void {
		$templates = $this->plugin->templates()->getTemplates();

		if ( empty( $templates ) ) {
			WP_CLI::warning( 'Nenhum template encontrado.' );
			return;
		}

		$items = array_map(
			function ( $tpl ) {
				return array(
					'ID'      => $tpl['id'],
					'Slug'    => $tpl['slug'],
					'Título'  => $tpl['title'],
					'Tipo'    => $tpl['type'] ?? '—',
					'Assunto' => $tpl['subject'] ?? '—',
				);
			},
			$templates
		);

		WP_CLI\Utils\format_items( 'table', $items, array( 'ID', 'Slug', 'Título', 'Tipo', 'Assunto' ) );
	}
}
