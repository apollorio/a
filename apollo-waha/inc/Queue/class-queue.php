<?php
/**
 * Join queue. Human process on Tela 3. JOIN-CHAIN.md.
 *
 * @package Apollo_Waha
 */

defined( 'ABSPATH' ) || exit;

final class Apollo_Waha_Queue {

	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function hooks(): void {
		add_action( 'apollo/login/phone_verified', array( $this, 'maybe_attach_phone' ), 10, 2 );
	}

	public function request_join( int $user_id ): int {
		return 0;
	}

	public function process( int $queue_id ): string {
		return 'session_down';
	}

	public function maybe_attach_phone( $user_id, $phone ): void {}
}
