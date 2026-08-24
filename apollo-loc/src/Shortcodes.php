<?php

/**
 * Shortcodes — compatibilidade retroativa
 *
 * Todos os shortcodes foram movidos para Apollo\Local\Shortcodes\*.
 * Esta classe é mantida apenas para não quebrar código externo.
 *
 * @deprecated 2.0.0 — use Apollo\Local\Shortcodes\ShortcodeRegistry
 * @package Apollo\Local
 */

namespace Apollo\Local;

if ( ! \defined( 'ABSPATH' ) ) {
exit;
}

/**
 * @deprecated 2.0.0
 */
class Shortcodes {

public function __construct() {
// Delega para o novo registry modular
new Shortcodes\ShortcodeRegistry();
}
}
