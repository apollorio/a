<?php
/**
 * Plugin singleton — bootstraps all Elementor integrations.
 *
 * @package Apollo\Elementor
 */

declare(strict_types=1);

namespace Apollo\Elementor;

use Elementor\Elements_Manager;
use Elementor\Widgets_Manager;
use Elementor\Controls_Manager as ElControls;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {

	private static ?Plugin $instance = null;

	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	public function init(): void {
		Integration::bootstrap();

		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );
		add_action( 'elementor/widgets/register',               [ $this, 'register_widgets' ] );
		add_action( 'elementor/dynamic_tags/register',          [ $this, 'register_dynamic_tags' ] );
		add_action( 'elementor/controls/register',              [ $this, 'register_controls' ] );
		add_action( 'elementor/frontend/after_register_styles',  [ $this, 'register_assets' ] );
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_assets_js' ] );
	}

	public function register_category( Elements_Manager $manager ): void {
		$manager->add_category( 'apollo', [
			'title' => __( 'Apollo', 'apollo-elementor-pro' ),
			'icon'  => 'eicon-favorite',
		] );
	}

	public function register_widgets( Widgets_Manager $manager ): void {
		$widgets = [
			Widgets\EventsGrid::class,
			Widgets\DJCard::class,
			Widgets\ClassifiedsGrid::class,
			Widgets\ProfileCard::class,
			Widgets\UserRadar::class,
			Widgets\MembershipGate::class,
			Widgets\WoWCounter::class,
			Widgets\HubListing::class,
		];
		foreach ( $widgets as $class ) {
			$manager->register( new $class() );
		}
	}

	public function register_dynamic_tags( \Elementor\Core\DynamicTags\Manager $manager ): void {
		$manager->register_group( 'apollo', [ 'title' => __( 'Apollo', 'apollo-elementor-pro' ) ] );
		DynamicTags\Manager::register_all( $manager );
	}

	public function register_controls( ElControls $manager ): void {
		Controls\Manager::register_all( $manager );
	}

	public function register_assets(): void {
		wp_register_style(
			'apollo-elementor-widgets',
			APOLLO_ELEMENTOR_URL . 'assets/css/widgets.css',
			[],
			APOLLO_ELEMENTOR_VERSION
		);
	}

	public function register_assets_js(): void {
		wp_register_script(
			'apollo-elementor-widgets',
			APOLLO_ELEMENTOR_URL . 'assets/js/widgets.js',
			[],
			APOLLO_ELEMENTOR_VERSION,
			true
		);
	}
}
