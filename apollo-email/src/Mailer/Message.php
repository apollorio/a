<?php

/**
 * Message builder — fluent API to construct emails.
 *
 * @package Apollo\Email\Mailer
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Email\Mailer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Message {

	private string $to         = '';
	private string $toName     = '';
	private string $from       = '';
	private string $fromName   = '';
	private string $replyTo    = '';
	private string $subject    = '';
	private string $html       = '';
	private string $text       = '';
	private string $template   = '';
	private array $data        = array();
	private array $headers     = array();
	private array $attachments = array();
	private bool $trackOpens   = true;
	private bool $trackClicks  = true;

	/**
	 * Static factory.
	 */
	public static function make( string $to, string $subject, string $html = '', string $from = '' ): self {
		$message          = new self();
		$message->to      = $to;
		$message->subject = $subject;
		$message->html    = $html;
		$message->from    = $from;
		return $message;
	}

	/**
	 * Create a Message from a template.
	 */
	public static function fromTemplate( string $to, string $template, array $data = array() ): self {
		$message           = new self();
		$message->to       = $to;
		$message->template = $template;
		$message->data     = $data;
		return $message;
	}

	// ── Setters (fluent) ─────────────────────────────────────────

	public function setTo( string $to ): self {
		$this->to = $to;
		return $this;
	}
	public function setToName( string $name ): self {
		$this->toName = $name;
		return $this;
	}
	public function setFrom( string $from ): self {
		$this->from = $from;
		return $this;
	}
	public function setFromName( string $name ): self {
		$this->fromName = $name;
		return $this;
	}
	public function setReplyTo( string $reply ): self {
		$this->replyTo = $reply;
		return $this;
	}
	public function setSubject( string $subject ): self {
		$this->subject = $subject;
		return $this;
	}
	public function setHtml( string $html ): self {
		$this->html = $html;
		return $this;
	}
	public function setText( string $text ): self {
		$this->text = $text;
		return $this;
	}
	public function setTemplate( string $tpl ): self {
		$this->template = $tpl;
		return $this;
	}
	public function setData( array $data ): self {
		$this->data = $data;
		return $this;
	}
	public function setTrackOpens( bool $v ): self {
		$this->trackOpens = $v;
		return $this;
	}
	public function setTrackClicks( bool $v ): self {
		$this->trackClicks = $v;
		return $this;
	}
	public function addHeader( string $k, string $v ): self {
		$this->headers[ $k ] = $v;
		return $this;
	}
	public function addAttachment( string $path ): self {
		$this->attachments[] = $path;
		return $this;
	}

	// ── Getters ──────────────────────────────────────────────────

	public function getTo(): string {
		return $this->to;
	}
	public function getToName(): string {
		return $this->toName;
	}
	public function getFrom(): string {
		return $this->from;
	}
	public function getFromName(): string {
		return $this->fromName;
	}
	public function getReplyTo(): string {
		return $this->replyTo;
	}
	public function getSubject(): string {
		return $this->subject;
	}
	public function getHtml(): string {
		return $this->html;
	}
	public function getText(): string {
		return $this->text;
	}
	public function getTemplate(): string {
		return $this->template;
	}
	public function getData(): array {
		return $this->data;
	}
	public function getHeaders(): array {
		return $this->headers;
	}
	public function getAttachments(): array {
		return $this->attachments;
	}
	public function shouldTrackOpens(): bool {
		return $this->trackOpens;
	}
	public function shouldTrackClicks(): bool {
		return $this->trackClicks;
	}

	/**
	 * Build full headers array for wp_mail.
	 *
	 * @return string[]
	 */
	public function buildHeaders(): array {
		$headers = array();

		if ( $this->from ) {
			$name      = $this->fromName ?: $this->from;
			$headers[] = "From: {$name} <{$this->from}>";
		}

		if ( $this->replyTo ) {
			$headers[] = "Reply-To: {$this->replyTo}";
		}

		$headers[] = 'Content-Type: text/html; charset=UTF-8';

		foreach ( $this->headers as $key => $value ) {
			$headers[] = "{$key}: {$value}";
		}

		return $headers;
	}

	/**
	 * Validate the message.
	 *
	 * @return string[] Array of errors (empty if valid).
	 */
	public function validate(): array {
		$errors = array();

		if ( empty( $this->to ) || ! is_email( $this->to ) ) {
			$errors[] = __( 'Destinatário inválido.', 'apollo-email' );
		}

		if ( empty( $this->subject ) && empty( $this->template ) ) {
			$errors[] = __( 'Assunto é obrigatório.', 'apollo-email' );
		}

		if ( empty( $this->html ) && empty( $this->template ) ) {
			$errors[] = __( 'Corpo do email ou template é obrigatório.', 'apollo-email' );
		}

		return $errors;
	}
}
