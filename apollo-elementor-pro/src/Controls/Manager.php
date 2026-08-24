<?php
/**
 * Controls Manager — registers all Apollo custom Elementor controls.
 *
 * @package Apollo\Elementor\Controls
 */

declare(strict_types=1);

namespace Apollo\Elementor\Controls;

use Elementor\Controls_Manager as ElControls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Manager {

	public static function register_all( ElControls $manager ): void {
		$manager->register( new SoundsTaxonomy() );
		$manager->register( new ApolloCpt() );
	}
}
