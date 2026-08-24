<?php

/**
 * Model — CRUD operations for Sheets
 *
 * Stores data as JSON in post_content (CPT: apollo_sheet).
 * Options/visibility in post meta. Logical ID mapping in wp_options.
 *
 * Adapted from TablePress Model architecture.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Model {


	/**
	 * Option key for ID→post_id mapping
	 */
	private const MAPPING_OPTION = 'apollo_sheets_tables';

	/**
	 * Default table template
	 */
	public static function get_default_table(): array {
		$rows = APOLLO_SHEETS_DEFAULT_ROWS;
		$cols = APOLLO_SHEETS_DEFAULT_COLS;

		$data = array();
		for ( $r = 0; $r < $rows; $r++ ) {
			$data[ $r ] = array_fill( 0, $cols, '' );
		}

		return array(
			'id'            => '',
			'name'          => __( 'Nova Sheet', 'apollo-sheets' ),
			'description'   => '',
			'data'          => $data,
			'options'       => self::get_default_options(),
			'visibility'    => array(
				'rows'    => array_fill( 0, $rows, 1 ),
				'columns' => array_fill( 0, $cols, 1 ),
			),
			'author'        => get_current_user_id(),
			'last_modified' => current_time( 'mysql' ),
		);
	}

	/**
	 * Default table options
	 */
	public static function get_default_options(): array {
		return array(
			'table_head'                  => 1,
			'table_foot'                  => 0,
			'alternating_row_colors'      => true,
			'print_name'                  => false,
			'print_name_position'         => 'above',
			'print_description'           => false,
			'print_description_position'  => 'below',
			'extra_css_classes'           => '',
			// DataTables features
			'use_datatables'              => true,
			'datatables_sort'             => true,
			'datatables_filter'           => true,
			'datatables_paginate'         => true,
			'datatables_paginate_entries' => 25,
			'datatables_lengthchange'     => true,
			'datatables_info'             => true,
			'datatables_scrollx'          => false,
			'datatables_scrolly'          => '',
			// Column widths (empty = auto)
			'column_widths'               => array(),
			// Cache
			'cache_table_output'          => false,
		);
	}

	// ─── Mapping ────────────────────────────────────────────────────

	/**
	 * Get the full mapping array
	 */
	private function get_mapping(): array {
		$mapping = get_option( self::MAPPING_OPTION, array() );
		if ( ! is_array( $mapping ) ) {
			$mapping = array();
		}
		if ( ! isset( $mapping['last_id'] ) ) {
			$mapping['last_id'] = 0;
		}
		if ( ! isset( $mapping['table_post'] ) ) {
			$mapping['table_post'] = array();
		}
		return $mapping;
	}

	/**
	 * Save mapping
	 */
	private function save_mapping( array $mapping ): void {
		update_option( self::MAPPING_OPTION, $mapping, false );
	}

	/**
	 * Get post_id from logical sheet ID
	 */
	private function get_post_id( string $id ): int {
		$mapping = $this->get_mapping();
		return (int) ( $mapping['table_post'][ $id ] ?? 0 );
	}

	// ─── CRUD ───────────────────────────────────────────────────────

	/**
	 * Create a new sheet
	 *
	 * @param array $table Sheet data array.
	 * @return string|false New sheet ID or false on failure.
	 */
	public function add( array $table ): string|false {
		$table = array_merge( self::get_default_table(), $table );

		// Generate next ID
		$mapping = $this->get_mapping();
		++$mapping['last_id'];
		$new_id      = (string) $mapping['last_id'];
		$table['id'] = $new_id;

		// Create post
		$post_id = wp_insert_post(
			array(
				'post_type'    => APOLLO_SHEETS_CPT,
				'post_title'   => sanitize_text_field( $table['name'] ),
				'post_excerpt' => sanitize_text_field( $table['description'] ),
				'post_content' => wp_json_encode( $table['data'] ),
				'post_status'  => 'publish',
				'post_author'  => $table['author'] ?: get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Store meta
		update_post_meta( $post_id, '_apollo_sheet_options', wp_json_encode( $table['options'] ) );
		update_post_meta( $post_id, '_apollo_sheet_visibility', wp_json_encode( $table['visibility'] ) );
		update_post_meta( $post_id, '_apollo_sheet_id', $new_id );

		// Update mapping
		$mapping['table_post'][ $new_id ] = $post_id;
		$this->save_mapping( $mapping );

		do_action( 'apollo/sheets/added', $new_id, $table );

		return $new_id;
	}

	/**
	 * Load a sheet by logical ID
	 *
	 * @param string $id              Sheet ID.
	 * @param bool   $load_data       Whether to load data array.
	 * @param bool   $load_options    Whether to load options.
	 * @return array|false Sheet array or false if not found.
	 */
	public function load( string $id, bool $load_data = true, bool $load_options = true ): array|false {
		$post_id = $this->get_post_id( $id );
		if ( ! $post_id ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post || APOLLO_SHEETS_CPT !== $post->post_type ) {
			return false;
		}

		$table = array(
			'id'            => $id,
			'name'          => $post->post_title,
			'description'   => $post->post_excerpt,
			'author'        => (int) $post->post_author,
			'last_modified' => $post->post_modified,
			'last_editor'   => (int) get_post_meta( $post_id, '_edit_last', true ),
		);

		if ( $load_data ) {
			$raw  = $post->post_content;
			$data = json_decode( $raw, true );
			if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
				$data                  = array( array( '' ) );
				$table['is_corrupted'] = true;
			}
			$table['data'] = $data;
		}

		if ( $load_options ) {
			$opts_raw         = get_post_meta( $post_id, '_apollo_sheet_options', true );
			$opts             = $opts_raw ? json_decode( $opts_raw, true ) : array();
			$table['options'] = array_merge( self::get_default_options(), $opts ?: array() );

			$vis_raw             = get_post_meta( $post_id, '_apollo_sheet_visibility', true );
			$vis                 = $vis_raw ? json_decode( $vis_raw, true ) : array();
			$table['visibility'] = $vis ?: array(
				'rows'    => array(),
				'columns' => array(),
			);
		}

		return $table;
	}

	/**
	 * Save/update a sheet
	 *
	 * @param array $table Sheet data array.
	 * @return bool Success.
	 */
	public function save( array $table ): bool {
		$id      = $table['id'] ?? '';
		$post_id = $this->get_post_id( $id );

		if ( ! $post_id ) {
			return false;
		}

		$result = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => sanitize_text_field( $table['name'] ?? '' ),
				'post_excerpt' => sanitize_text_field( $table['description'] ?? '' ),
				'post_content' => wp_json_encode( $table['data'] ?? array() ),
			),
			true
		);

		if ( is_wp_error( $result ) ) {
			return false;
		}

		if ( isset( $table['options'] ) ) {
			update_post_meta( $post_id, '_apollo_sheet_options', wp_json_encode( $table['options'] ) );
		}

		if ( isset( $table['visibility'] ) ) {
			update_post_meta( $post_id, '_apollo_sheet_visibility', wp_json_encode( $table['visibility'] ) );
		}

		// Invalidate cache
		$this->invalidate_cache( $id );

		do_action( 'apollo/sheets/saved', $id, $table );

		return true;
	}

	/**
	 * Delete a sheet
	 *
	 * @param string $id Sheet ID.
	 * @return bool Success.
	 */
	public function delete( string $id ): bool {
		$post_id = $this->get_post_id( $id );
		if ( ! $post_id ) {
			return false;
		}

		do_action( 'apollo/sheets/pre_delete', $id );

		$result = wp_delete_post( $post_id, true );
		if ( ! $result ) {
			return false;
		}

		// Remove from mapping
		$mapping = $this->get_mapping();
		unset( $mapping['table_post'][ $id ] );
		$this->save_mapping( $mapping );

		$this->invalidate_cache( $id );

		do_action( 'apollo/sheets/deleted', $id );

		return true;
	}

	/**
	 * Copy a sheet
	 *
	 * @param string $id Source sheet ID.
	 * @return string|false New sheet ID or false.
	 */
	public function copy( string $id ): string|false {
		$table = $this->load( $id );
		if ( ! $table ) {
			return false;
		}

		unset( $table['id'] );
		$table['name']   = sprintf( __( '%s (Cópia)', 'apollo-sheets' ), $table['name'] );
		$table['author'] = get_current_user_id();

		$new_id = $this->add( $table );

		if ( $new_id ) {
			do_action( 'apollo/sheets/copied', $new_id, $id );
		}

		return $new_id;
	}

	/**
	 * Load all sheet IDs
	 *
	 * @return array Associative [ id => post_id, ... ]
	 */
	public function load_all_ids(): array {
		$mapping = $this->get_mapping();
		return $mapping['table_post'] ?? array();
	}

	/**
	 * Load all sheets (lightweight: no data)
	 *
	 * @return array Array of sheet arrays (without data).
	 */
	public function load_all(): array {
		$ids    = $this->load_all_ids();
		$sheets = array();

		foreach ( $ids as $id => $post_id ) {
			$sheet = $this->load( (string) $id, false, false );
			if ( $sheet ) {
				$sheets[] = $sheet;
			}
		}

		return $sheets;
	}

	// ─── Cache ──────────────────────────────────────────────────────

	/**
	 * Invalidate cached output for a sheet
	 */
	public function invalidate_cache( string $id ): void {
		// Delete transients matching this sheet
		global $wpdb;
		$like = $wpdb->esc_like( 'apollo_sheet_' . $id . '_' ) . '%';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . $like,
				'_transient_timeout_' . $like
			)
		);
	}
}
