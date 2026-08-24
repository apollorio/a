<?php

/**
 * Apollo Admin — Frontend Panel (thin façade)
 *
 * Delegates to:
 *   Frontend\Router                    — rewrite rules + template dispatch
 *   Frontend\Controller\PendingController   — pending drafts REST/AJAX
 *   Frontend\Controller\MembershipController — memberships REST
 *
 * @package Apollo\Admin
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Admin;

use Apollo\Admin\Frontend\Router;
use Apollo\Admin\Frontend\Controller\PendingController;
use Apollo\Admin\Frontend\Controller\MembershipController;

if (! \defined('ABSPATH')) {
    exit;
}

final class FrontendPanel
{

    private static ?FrontendPanel $instance = null;

    public static function get_instance(): FrontendPanel
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init(): void
    {
        (new Router())->init();
        (new PendingController())->init();
        (new MembershipController())->init();
    }
}
