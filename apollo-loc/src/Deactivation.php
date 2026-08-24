<?php
/**
 * Desativação — apollo-local
 *
 * @package Apollo\Local
 */

declare(strict_types=1);

namespace Apollo\Local;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivation {

	public static function deactivate(): void {
		// Nada a limpar na desativação
	}
}
