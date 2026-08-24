<?php
/**
 * Desativação — apollo-djs
 *
 * @package Apollo\DJs
 */

declare(strict_types=1);

namespace Apollo\DJs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Deactivation {

	public static function deactivate(): void {
		// Nada a limpar na desativação
	}
}
