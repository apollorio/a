<?php

/**
 * Apollo Admin — Admin Menu Customizer.
 *
 * Captures the WordPress admin menu, allows reordering, renaming, and hiding
 * items. Adapted from UiPress Lite AdminMenu class.
 *
 * @package Apollo\Admin
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Menu Customizer.
 *
 * Captures the WP admin menu array, merges submenus, and applies
 * custom configurations (reorder, rename, hide) stored in Settings.
 *
 * @since 1.1.0
 */
final class AdminMenuCustomizer {


	/** @var AdminMenuCustomizer|null */
	private static ?AdminMenuCustomizer $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return AdminMenuCustomizer
	 */
	public static function get_instance(): AdminMenuCustomizer {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Initialize hooks.
	 */
	public function init(): void {
		if ( ! is_admin() ) {
			return;
		}
		add_filter( 'parent_file', array( $this, 'capture_and_apply' ), 9999 );
	}

	/**
	 * Capture the WP admin menu and apply customizations.
	 *
	 * Adapted from UiPress AdminMenu::capture_wp_menu().
	 *
	 * @param string $parent_file Current parent file.
	 * @return string
	 */
	public function capture_and_apply( string $parent_file ): string {
		global $menu, $submenu;

		// Assign unique IDs to menu items.
		$menu = $this->push_unique_ids( $menu );

		// Get custom configuration from settings.
		$settings = Settings::get_instance();
		$config   = $settings->for_plugin( 'admin-menu' );

		if ( empty( $config ) || empty( $config['customizations'] ) ) {
			// Cache the raw menu for REST endpoint.
			$this->cache_menu( $menu, $submenu );
			return $parent_file;
		}

		$customizations = $config['customizations'];

		// Apply hide rules.
		if ( ! empty( $customizations['hidden'] ) && is_array( $customizations['hidden'] ) ) {
			$menu = $this->hide_items( $menu, $customizations['hidden'] );
		}

		// Apply rename rules.
		if ( ! empty( $customizations['renamed'] ) && is_array( $customizations['renamed'] ) ) {
			$menu = $this->rename_items( $menu, $customizations['renamed'] );
		}

		// Apply reorder rules.
		if ( ! empty( $customizations['order'] ) && is_array( $customizations['order'] ) ) {
			$menu = $this->reorder_items( $menu, $customizations['order'] );
		}

		// Cache the processed menu.
		$this->cache_menu( $menu, $submenu );

		return $parent_file;
	}

	/**
	 * Push unique IDs to menu items.
	 *
	 * Adapted from UiPress AdminMenu::push_unique_ids().
	 *
	 * @param array|null $menu The WP admin menu array.
	 * @return array
	 */
	private function push_unique_ids( ?array $menu ): array {
		if ( ! is_array( $menu ) ) {
			return array();
		}
		foreach ( $menu as $priority => &$item ) {
			if ( ! isset( $item[5] ) || empty( $item[5] ) ) {
				$item[5] = 'apollo-menu-' . $priority;
			}
		}
		return $menu;
	}

	/**
	 * Hide menu items by slug.
	 *
	 * @param array $menu  The WP admin menu.
	 * @param array $slugs Array of menu slugs to hide.
	 * @return array
	 */
	private function hide_items( array $menu, array $slugs ): array {
		foreach ( $menu as $priority => $item ) {
			$item_slug = $item[2] ?? '';
			if ( in_array( $item_slug, $slugs, true ) ) {
				unset( $menu[ $priority ] );
			}
		}
		return $menu;
	}

	/**
	 * Rename menu items.
	 *
	 * @param array $menu    The WP admin menu.
	 * @param array $renames Associative array [ slug => new_name ].
	 * @return array
	 */
	private function rename_items( array $menu, array $renames ): array {
		foreach ( $menu as $priority => &$item ) {
			$item_slug = $item[2] ?? '';
			if ( isset( $renames[ $item_slug ] ) ) {
				// Strip notification bubbles before renaming.
				$bubble = '';
				if ( preg_match( '/(<span[^>]*class="[^"]*update-plugins[^"]*"[^>]*>.*?<\/span>)/s', $item[0], $matches ) ) {
					$bubble = $matches[1];
				}
				$item[0] = sanitize_text_field( $renames[ $item_slug ] ) . $bubble;
			}
		}
		return $menu;
	}

	/**
	 * Reorder menu items by slug order.
	 *
	 * @param array $menu  The WP admin menu.
	 * @param array $order Array of slugs in desired order.
	 * @return array
	 */
	private function reorder_items( array $menu, array $order ): array {
		$indexed = array();
		foreach ( $menu as $item ) {
			$slug             = $item[2] ?? '';
			$indexed[ $slug ] = $item;
		}

		$reordered = array();
		$priority  = 0;

		// Place ordered items first.
		foreach ( $order as $slug ) {
			if ( isset( $indexed[ $slug ] ) ) {
				$reordered[ $priority ] = $indexed[ $slug ];
				unset( $indexed[ $slug ] );
				$priority += 1;
			}
		}

		// Append remaining items.
		foreach ( $indexed as $item ) {
			$reordered[ $priority ] = $item;
			$priority              += 1;
		}

		return $reordered;
	}

	/**
	 * Cache the menu for REST retrieval.
	 *
	 * @param array      $menu    The admin menu.
	 * @param array|null $submenu The admin submenu.
	 */
	private function cache_menu( array $menu, ?array $submenu ): void {
		$merged = $this->create_merged_menu( $menu, $submenu ?? array() );
		set_transient( 'apollo_admin_menu_cache', $merged, 12 * HOUR_IN_SECONDS );
	}

	/**
	 * Merge menu and submenu into a flat array.
	 *
	 * Adapted from UiPress AdminMenu::create_merged_menu().
	 *
	 * @param array $menu    Top-level menu items.
	 * @param array $submenu Submenu items keyed by parent slug.
	 * @return array
	 */
	private function create_merged_menu( array $menu, array $submenu ): array {
		$result = array();
		foreach ( $menu as $item ) {
			$formatted = array(
				'id'    => $item[5] ?? '',
				'title' => wp_strip_all_tags( $item[0] ?? '' ),
				'slug'  => $item[2] ?? '',
				'icon'  => $item[6] ?? '',
				'type'  => 'parent',
			);
			$result[]  = $formatted;

			$parent_slug   = $item[2] ?? '';
			$submenu_items = ! empty( $submenu[ $parent_slug ] ) ? (array) $submenu[ $parent_slug ] : array();
			foreach ( $submenu_items as $sub ) {
				$result[] = array(
					'id'     => $sub[2] ?? '',
					'title'  => wp_strip_all_tags( $sub[0] ?? '' ),
					'slug'   => $sub[2] ?? '',
					'parent' => $parent_slug,
					'type'   => 'child',
				);
			}
		}
		return $result;
	}

	/**
	 * Get the cached admin menu structure.
	 *
	 * Used internally by admin pages and templates.
	 *
	 * @return array
	 */
	public function get_cached_menu(): array {
		$cached = get_transient( 'apollo_admin_menu_cache' );
		return is_array( $cached ) ? $cached : array();
	}

	/**
	 * Save menu customizations.
	 *
	 * Used internally by admin settings pages.
	 *
	 * @param array $customizations Customization data.
	 */
	public function save_customizations( array $customizations ): void {
		$sanitized = array();

		if ( isset( $customizations['hidden'] ) && is_array( $customizations['hidden'] ) ) {
			$sanitized['hidden'] = array_map( 'sanitize_text_field', $customizations['hidden'] );
		}

		if ( isset( $customizations['renamed'] ) && is_array( $customizations['renamed'] ) ) {
			$renamed = array();
			foreach ( $customizations['renamed'] as $slug => $name ) {
				$renamed[ sanitize_key( $slug ) ] = sanitize_text_field( $name );
			}
			$sanitized['renamed'] = $renamed;
		}

		if ( isset( $customizations['order'] ) && is_array( $customizations['order'] ) ) {
			$sanitized['order'] = array_map( 'sanitize_text_field', $customizations['order'] );
		}

		Settings::get_instance()->set( 'admin-menu', 'customizations', $sanitized );
	}
}
