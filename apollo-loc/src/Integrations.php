<?php

/**
 * Integrations — Cross-plugin hooks for Apollo Local
 *
 * Connects apollo-loc with apollo-core, apollo-templates,
 * apollo-social, apollo-statistics, etc.
 *
 * @package Apollo\Local
 */

declare(strict_types=1);

namespace Apollo\Local;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrations class for cross-plugin hook registration.
 */
class Integrations {

	/**
	 * Initialize integrations on plugin load.
	 */
	public static function init(): void {
		$instance = new self();
		$instance->register_hooks();
	}

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Register cross-plugin integration hooks.
	 */
	public function register_hooks(): void {

		// ── Apollo Core: loc CPT is registered by core, we add meta boxes ──
		/*
		 * apollo/core/initialized has ALREADY FIRED by the time this runs.
		 * apollo-core fires it at plugins_loaded:1; register_hooks() is reached
		 * from apollo_local_init() at plugins_loaded:15 (apollo-loc.php:109).
		 * A plain add_action() subscribes to a finished event -- that is why
		 * on_core_ready() below has never executed. Same defect, same fix and
		 * the same rationale as src/Integration/CoreIntegration.php:20.
		 */
		if ( \defined( 'APOLLO_CORE_VERSION' ) ) {
			if ( \did_action( 'apollo/core/initialized' ) ) {
				$this->on_core_ready();
			} else {
				add_action( 'apollo/core/initialized', array( $this, 'on_core_ready' ) );
			}
		}

		// ── Apollo Templates: register loc-specific template parts ──
		if ( \defined( 'APOLLO_TEMPLATES_VERSION' ) ) {
			add_filter( 'apollo/templates/parts', array( $this, 'register_template_parts' ) );
		}

		// ── Apollo Social: loc check-ins, shares ──
		if ( \defined( 'APOLLO_SOCIAL_VERSION' ) ) {
			add_action( 'apollo/social/checkin', array( $this, 'handle_checkin' ), 10, 2 );
		}
	}

	/**
	 * When apollo-core signals ready, hook into loc-specific features.
	 *
	 * @param array $info Core initialization info.
	 */
	public function on_core_ready( array $info = array() ): void {
		// Future: register additional loc meta, taxonomies, etc.
		unset( $info );
	}

	/**
	 * Register loc template parts with apollo-templates.
	 *
	 * @param array $parts Existing template parts.
	 * @return array Updated template parts.
	 */
	public function register_template_parts( array $parts ): array {
		$parts['loc-card']   = APOLLO_LOCAL_DIR . 'templates/template-parts/loc/card.php';
		$parts['loc-map']    = APOLLO_LOCAL_DIR . 'templates/template-parts/loc/map.php';
		$parts['loc-nearby'] = APOLLO_LOCAL_DIR . 'templates/template-parts/loc/nearby.php';
		return $parts;
	}

	/**
	 * Handle social check-in at a location.
	 *
	 * @param int $user_id User ID.
	 * @param int $loc_id Location ID.
	 */
	public function handle_checkin( int $user_id, int $loc_id ): void {
		// Future: increment visit count, trigger achievements, etc.
		unset( $user_id, $loc_id );
	}
}
