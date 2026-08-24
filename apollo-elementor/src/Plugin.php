<?php declare(strict_types=1);

namespace Apollo\ElementorAE;

use Elementor\Elements_Manager;
use Elementor\Widgets_Manager;

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
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'elementor/frontend/after_register_styles', [ $this, 'register_styles' ] );
		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_scripts' ] );
	}

	public function register_category( Elements_Manager $manager ): void {
		$manager->add_category( 'apollo-elementor', [
			'title' => __( 'Apollo \u00b7 Elementor', 'apollo-elementor' ),
			'icon'  => 'eicon-favorite',
		] );
	}

	public function register_widgets( Widgets_Manager $manager ): void {
		$atomic = [
			Widgets\Atomic\Marquee::class,
			Widgets\Atomic\MetaStrip::class,
			Widgets\Atomic\BentoGrid::class,
			Widgets\Atomic\DiscographyList::class,
			Widgets\Atomic\PlayedWithList::class,
			Widgets\Atomic\EventLineup::class,
			Widgets\Atomic\EventRSVP::class,
			Widgets\Atomic\KanbanColumn::class,
			Widgets\Atomic\AppointmentCard::class,
			Widgets\Atomic\NowLine::class,
			Widgets\Atomic\ChartRadial::class,
			Widgets\Atomic\ChartBar::class,
			Widgets\Atomic\ChartLine::class,
		];

		$composites = [
			Widgets\Composites\DJSinglePage::class,
			Widgets\Composites\EventSinglePage::class,
			Widgets\Composites\WeeklyPlanner::class,
			Widgets\Composites\MonthlyCalendar::class,
			Widgets\Composites\ChartsDashboard::class,
			Widgets\Composites\GestorDashboard::class,
		];

		foreach ( array_merge( $atomic, $composites ) as $class ) {
			$manager->register( new $class() );
		}
	}

	public function register_styles(): void {
		wp_register_style(
			'apollo-elementor-base',
			APOLLO_AE_URL . 'assets/css/apollo-elementor.css',
			[],
			APOLLO_AE_VERSION
		);

		$composites = [
			'dj-single-page',
			'event-single-page',
			'weekly-planner',
			'monthly-calendar',
			'charts-dashboard',
			'gestor-dashboard',
		];
		foreach ( $composites as $slug ) {
			wp_register_style(
				'apollo-elementor-' . $slug,
				APOLLO_AE_URL . 'assets/css/composites/' . $slug . '.css',
				[ 'apollo-elementor-base' ],
				APOLLO_AE_VERSION
			);
		}
	}

	public function register_scripts(): void {
		wp_register_script(
			'apollo-elementor-base',
			APOLLO_AE_URL . 'assets/js/apollo-elementor.js',
			[],
			APOLLO_AE_VERSION,
			true
		);
	}

	public static function uninstall(): void {
		Cache\CacheManager::flush_group();
	}
}
