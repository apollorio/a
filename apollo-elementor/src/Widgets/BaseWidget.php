<?php declare(strict_types=1);

namespace Apollo\ElementorAE\Widgets;

use Apollo\ElementorAE\Cache\CacheManager;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class BaseWidget extends Widget_Base {

	/** @return string[] */
	public function get_categories(): array {
		return [ 'apollo-elementor' ];
	}

	public function get_style_depends(): array {
		return [ 'apollo-elementor-base', 'apollo-elementor-' . $this->get_name() ];
	}

	public function get_script_depends(): array {
		return [ 'apollo-elementor-base' ];
	}

	abstract protected function template_slug(): string;

	/** @return array<string, mixed> */
	abstract protected function fetch_data( array $settings ): array;

	protected function render(): void {
		$settings  = $this->get_settings_for_display();
		$cache_key = $this->cache_key( $settings );
		$ttl       = absint( $settings['cache_ttl'] ?? 900 );

		$html = CacheManager::get( $cache_key );
		if ( false === $html ) {
			$data = $this->fetch_data( $settings );
			$html = $this->load_template( $this->template_slug(), [
				'settings' => $settings,
				'data'     => $data,
				'uid'      => wp_unique_id( 'ae-' ),
			] );
			CacheManager::set( $cache_key, $html, $ttl );
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- templates handle escaping
	}

	protected function register_cache_section(): void {
		$this->start_controls_section( 'section_ae_cache', [
			'label' => __( 'Cache', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'cache_ttl', [
			'label'   => __( 'Cache TTL (seconds)', 'apollo-elementor' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 900,
			'min'     => 0,
			'max'     => 86400,
		] );

		$this->end_controls_section();
	}

	protected function register_accent_section(): void {
		$this->start_controls_section( 'section_ae_style', [
			'label' => __( 'Apollo Style', 'apollo-elementor' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'accent_color', [
			'label'     => __( 'Accent colour', 'apollo-elementor' ),
			'type'      => Controls_Manager::COLOR,
			'default'   => '#FF5C00',
			'selectors' => [
				'{{WRAPPER}} .ae-accent'    => 'color: {{VALUE}}; border-color: {{VALUE}};',
				'{{WRAPPER}} .ae-accent-bg' => 'background: {{VALUE}};',
			],
		] );

		$this->end_controls_section();
	}

	protected function cache_key( array $settings ): string {
		$relevant = array_intersect_key( $settings, array_flip( $this->cache_key_fields() ) );
		return $this->get_name() . '_' . md5( (string) wp_json_encode( $relevant ) );
	}

	/** Override to specify which settings affect the cache key. */
	protected function cache_key_fields(): array {
		return [];
	}

	protected function load_template( string $slug, array $vars = [] ): string {
		$file = APOLLO_AE_DIR . 'templates/' . $slug . '.php';
		if ( ! is_file( $file ) ) {
			return '';
		}

		extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
		ob_start();
		include $file;
		return (string) ob_get_clean();
	}
}
