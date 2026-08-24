<?php
/**
 * Dynamic Tags Manager — registers all Apollo dynamic tags.
 *
 * @package Apollo\Elementor\DynamicTags
 */

declare(strict_types=1);

namespace Apollo\Elementor\DynamicTags;

use Elementor\Core\DynamicTags\Manager as ElManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Manager {

	public static function register_all( ElManager $manager ): void {
		$tags = [
			ProfileField::class,
			MatchScore::class,
			WowCount::class,
			FavCount::class,
			RestField::class,
		];
		foreach ( $tags as $class ) {
			$manager->register( new $class() );
		}
	}
}
