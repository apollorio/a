<?php
/**
 * Dynamic Tag: REST Field
 *
 * Fetches a value from any Apollo REST endpoint at render time
 * (server-side, cached via transient).
 *
 * @package Apollo\Elementor\DynamicTags
 */

declare(strict_types=1);

namespace Apollo\Elementor\DynamicTags;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Tag;
use Elementor\Modules\DynamicTags\Module as Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RestField extends Tag {

	public function get_name(): string {
		return 'apollo_rest_field';
	}

	public function get_title(): string {
		return __( 'Apollo REST Field', 'apollo-elementor-pro' );
	}

	public function get_group(): string {
		return 'apollo';
	}

	public function get_categories(): array {
		return [ Module::TEXT_CATEGORY, Module::NUMBER_CATEGORY, Module::URL_CATEGORY ];
	}

	protected function register_controls(): void {
		$this->add_control( 'endpoint', [
			'label'       => __( 'Apollo REST endpoint', 'apollo-elementor-pro' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'apollo/v1/site/stats',
			'placeholder' => 'apollo/v1/…',
		] );

		$this->add_control( 'json_key', [
			'label'       => __( 'JSON key path (dot notation)', 'apollo-elementor-pro' ),
			'type'        => Controls_Manager::TEXT,
			'default'     => 'data.total',
			'placeholder' => 'data.value',
		] );

		$this->add_control( 'cache_seconds', [
			'label'   => __( 'Cache TTL (seconds)', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 60,
			'min'     => 0,
			'max'     => 86400,
		] );
	}

	public function render(): void {
		$endpoint = sanitize_text_field( $this->get_settings( 'endpoint' ) );
		$key_path = sanitize_text_field( $this->get_settings( 'json_key' ) );
		$ttl      = (int) $this->get_settings( 'cache_seconds' );

		$transient_key = 'aep_rf_' . md5( $endpoint . $key_path );
		$cached        = get_transient( $transient_key );

		if ( false !== $cached ) {
			echo esc_html( (string) $cached );
			return;
		}

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/' . ltrim( $endpoint, '/' ) ) );
		if ( is_wp_error( $response ) || 200 !== $response->get_status() ) {
			return;
		}

		$data  = $response->get_data();
		$parts = explode( '.', $key_path );
		foreach ( $parts as $part ) {
			if ( is_array( $data ) && isset( $data[ $part ] ) ) {
				$data = $data[ $part ];
			} else {
				$data = null;
				break;
			}
		}

		$value = null !== $data ? (string) $data : '';
		if ( $ttl > 0 ) {
			set_transient( $transient_key, $value, $ttl );
		}
		echo esc_html( $value );
	}
}
