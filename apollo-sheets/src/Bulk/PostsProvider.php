<?php

/**
 * Posts Provider — fetches + saves post data for bulk spreadsheet
 *
 * Handles any post type (post, page, event, dj, loc, classified, etc.).
 * Uses WP_Query for loading, wp_update_post / update_post_meta / wp_set_object_terms for saving.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets\Bulk;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PostsProvider {


	private ColumnRegistry $registry;

	public function __construct( ColumnRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Load rows for a given post type
	 *
	 * @param string $post_type  CPT slug
	 * @param array  $args       {
	 *     @type int    $per_page    Items per page (default 50)
	 *     @type int    $page        Page number (default 1)
	 *     @type string $search      Search keyword
	 *     @type string $status      Filter by post status
	 *     @type string $orderby     Order by field
	 *     @type string $order       ASC|DESC
	 * }
	 * @return array { rows: array[], total: int, pages: int }
	 */
	public function load_rows( string $post_type, array $args = array() ): array {
		$per_page = absint( $args['per_page'] ?? 50 );
		$page     = absint( $args['page'] ?? 1 );
		$search   = sanitize_text_field( $args['search'] ?? '' );
		$status   = sanitize_text_field( $args['status'] ?? 'any' );
		$orderby  = sanitize_key( $args['orderby'] ?? 'ID' );
		$order    = in_array( strtoupper( $args['order'] ?? 'DESC' ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

		$query_args = array(
			'post_type'      => $post_type,
			'post_status'    => $status,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => $orderby,
			'order'          => $order,
		);

		if ( ! empty( $search ) ) {
			$query_args['s'] = $search;
		}

		/**
		 * Filter WP_Query args before loading bulk rows
		 *
		 * @param array  $query_args
		 * @param string $post_type
		 */
		$query_args = apply_filters( 'apollo/sheets/bulk/query_args', $query_args, $post_type );

		$query   = new \WP_Query( $query_args );
		$columns = $this->registry->get_columns( $post_type );
		$rows    = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$row = array();

				foreach ( $columns as $key => $config ) {
					$row[ $key ] = $this->get_cell_value( $post, $key, $config );
				}

				$rows[] = $row;
			}
		}

		wp_reset_postdata();

		return array(
			'rows'  => $rows,
			'total' => (int) $query->found_posts,
			'pages' => (int) $query->max_num_pages,
		);
	}

	/**
	 * Get a single cell value
	 *
	 * @param \WP_Post $post
	 * @param string   $key
	 * @param array    $config
	 * @return mixed
	 */
	private function get_cell_value( \WP_Post $post, string $key, array $config ): mixed {
		$data_type = $config['data_type'];

		if ( $data_type === 'post_data' ) {
			return $this->get_post_field( $post, $key );
		}

		if ( $data_type === 'meta_data' ) {
			return get_post_meta( $post->ID, $key, true );
		}

		if ( $data_type === 'post_terms' ) {
			$terms = wp_get_post_terms( $post->ID, $key, array( 'fields' => 'names' ) );
			if ( is_wp_error( $terms ) ) {
				return '';
			}
			return implode( ', ', $terms );
		}

		return '';
	}

	/**
	 * Get a WP_Post field value
	 *
	 * @param \WP_Post $post
	 * @param string   $key
	 * @return mixed
	 */
	private function get_post_field( \WP_Post $post, string $key ): mixed {
		return match ( $key ) {
			'ID'             => $post->ID,
			'post_title'     => $post->post_title,
			'post_name'      => $post->post_name,
			'post_status'    => $post->post_status,
			'post_author'    => $this->get_author_name( $post->post_author ),
			'post_date'      => $post->post_date,
			'post_modified'  => $post->post_modified,
			'post_excerpt'   => $post->post_excerpt,
			'post_content'   => wp_trim_words( wp_strip_all_tags( $post->post_content ), 30, '…' ),
			'post_parent'    => $post->post_parent,
			'comment_status' => $post->comment_status,
			'post_mime_type' => $post->post_mime_type,
			'menu_order'     => $post->menu_order,
			default          => $post->$key ?? '',
		};
	}

	/**
	 * Get author display name from ID
	 *
	 * @param int $author_id
	 * @return string
	 */
	private function get_author_name( int $author_id ): string {
		$user = get_userdata( $author_id );
		return $user ? $user->display_name : (string) $author_id;
	}

	/**
	 * Save batch of modified rows
	 *
	 * @param string $post_type
	 * @param array  $rows  Array of rows with 'ID' key and changed fields
	 * @return array { saved: int, errors: string[] }
	 */
	public function save_rows( string $post_type, array $rows ): array {
		$columns = $this->registry->get_columns( $post_type );
		$saved   = 0;
		$errors  = array();

		// Suspend cache invalidation for performance
		wp_suspend_cache_invalidation( true );

		foreach ( $rows as $row ) {
			$post_id = absint( $row['ID'] ?? 0 );
			if ( empty( $post_id ) ) {
				$errors[] = __( 'Linha sem ID ignorada.', 'apollo-sheets' );
				continue;
			}

			// Verify post exists and is the right type
			$existing = get_post( $post_id );
			if ( ! $existing || $existing->post_type !== $post_type ) {
				$errors[] = sprintf( __( 'Post ID %d não encontrado ou tipo incorreto.', 'apollo-sheets' ), $post_id );
				continue;
			}

			// Verify user can edit this post
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				$errors[] = sprintf( __( 'Sem permissão para editar post ID %d.', 'apollo-sheets' ), $post_id );
				continue;
			}

			$post_data  = array( 'ID' => $post_id );
			$has_update = false;

			foreach ( $columns as $key => $config ) {
				if ( ! $config['editable'] || ! isset( $row[ $key ] ) ) {
					continue;
				}

				$value = $row[ $key ];

				if ( $config['data_type'] === 'post_data' ) {
					$save_key = $this->map_save_key( $key );
					if ( $save_key === 'post_author' ) {
						$value = $this->resolve_author_id( $value );
						if ( ! $value ) {
							continue;
						}
					}
					$post_data[ $save_key ] = $value;
					$has_update             = true;
				} elseif ( $config['data_type'] === 'meta_data' ) {
					update_post_meta( $post_id, $key, sanitize_text_field( (string) $value ) );
					$has_update = true;
				} elseif ( $config['data_type'] === 'post_terms' ) {
					$term_names = array_map( 'trim', explode( ',', (string) $value ) );
					$term_names = array_filter( $term_names );
					wp_set_object_terms( $post_id, $term_names, $key );
					$has_update = true;
				}
			}

			// Update post data fields
			if ( count( $post_data ) > 1 ) {
				// Sanitize title
				if ( ! empty( $post_data['post_title'] ) ) {
					$post_data['post_title'] = sanitize_text_field( $post_data['post_title'] );
				}

				$result = wp_update_post( $post_data, true );
				if ( is_wp_error( $result ) ) {
					$errors[] = sprintf( __( 'Erro ao salvar post ID %1$d: %2$s', 'apollo-sheets' ), $post_id, $result->get_error_message() );
					continue;
				}
			}

			if ( $has_update ) {
				++$saved;
			}
		}

		wp_suspend_cache_invalidation( false );

		return array( 'saved' => $saved, 'errors' => $errors );
	}

	/**
	 * Save batch of modified changes
	 *
	 * @param string $post_type
	 * @param array  $changes  Array of {id, data: {key: value}}
	 * @return array { saved: int, errors: string[] }
	 */
	public function save_changes( string $post_type, array $changes ): array {
		$columns = $this->registry->get_columns( $post_type );
		$saved   = 0;
		$errors  = array();

		// Suspend cache invalidation for performance
		wp_suspend_cache_invalidation( true );

		foreach ( $changes as $change ) {
			$post_id = absint( $change['id'] ?? 0 );
			$data    = $change['data'] ?? array();

			if ( empty( $post_id ) ) {
				$errors[] = __( 'Alteração sem ID ignorada.', 'apollo-sheets' );
				continue;
			}

			// Verify post exists and is the right type
			$existing = get_post( $post_id );
			if ( ! $existing || $existing->post_type !== $post_type ) {
				$errors[] = sprintf( __( 'Post ID %d não encontrado ou tipo incorreto.', 'apollo-sheets' ), $post_id );
				continue;
			}

			// Verify user can edit this post
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				$errors[] = sprintf( __( 'Sem permissão para editar post ID %d.', 'apollo-sheets' ), $post_id );
				continue;
			}

			$post_data  = array( 'ID' => $post_id );
			$has_update = false;

			foreach ( $data as $key => $value ) {
				$config = $columns[ $key ] ?? null;
				if ( ! $config || ! $config['editable'] ) {
					continue;
				}

				if ( $config['data_type'] === 'post_data' ) {
					$save_key = $this->map_save_key( $key );
					if ( $save_key === 'post_author' ) {
						$value = $this->resolve_author_id( $value );
						if ( ! $value ) {
							continue;
						}
					}
					$post_data[ $save_key ] = $value;
					$has_update             = true;
				} elseif ( $config['data_type'] === 'meta_data' ) {
					update_post_meta( $post_id, $key, sanitize_text_field( (string) $value ) );
					$has_update = true;
				} elseif ( $config['data_type'] === 'post_terms' ) {
					$term_names = array_map( 'trim', explode( ',', (string) $value ) );
					$term_names = array_filter( $term_names );
					wp_set_object_terms( $post_id, $term_names, $key );
					$has_update = true;
				}
			}

			// Update post data fields
			if ( count( $post_data ) > 1 ) {
				// Sanitize title
				if ( ! empty( $post_data['post_title'] ) ) {
					$post_data['post_title'] = sanitize_text_field( $post_data['post_title'] );
				}

				$result = wp_update_post( $post_data, true );
				if ( is_wp_error( $result ) ) {
					$errors[] = sprintf( __( 'Erro ao salvar post ID %1$d: %2$s', 'apollo-sheets' ), $post_id, $result->get_error_message() );
					continue;
				}
			}

			if ( $has_update ) {
				++$saved;
			}
		}

		wp_suspend_cache_invalidation( false );

		return array(
			'saved'  => $saved,
			'errors' => $errors,
		);
	}
	public function insert_rows( string $post_type, int $count = 1 ): array {
		$ids = array();

		for ( $i = 0; $i < min( $count, 50 ); $i++ ) {
			$post_id = wp_insert_post(
				array(
					'post_title'   => __( '(sem título)', 'apollo-sheets' ),
					'post_type'    => $post_type,
					'post_status'  => 'draft',
					'post_author'  => get_current_user_id(),
					'post_content' => '',
				),
				true
			);

			if ( ! is_wp_error( $post_id ) ) {
				$ids[] = $post_id;
			}
		}

		return $ids;
	}

	/**
	 * Delete posts
	 *
	 * @param array $post_ids
	 * @param bool  $force_delete  Skip trash
	 * @return int Number of deleted
	 */
	public function delete_rows( array $post_ids, bool $force_delete = false ): int {
		$deleted = 0;

		foreach ( $post_ids as $id ) {
			$id = absint( $id );
			if ( ! $id || ! current_user_can( 'delete_post', $id ) ) {
				continue;
			}
			$result = wp_delete_post( $id, $force_delete );
			if ( $result ) {
				++$deleted;
			}
		}

		return $deleted;
	}

	/**
	 * Map column key to wp_update_post array key
	 *
	 * @param string $key
	 * @return string
	 */
	private function map_save_key( string $key ): string {
		// Most keys already have post_ prefix
		if ( in_array( $key, array( 'ID', 'comment_status', 'menu_order' ), true ) ) {
			return $key;
		}

		// Ensure post_ prefix for wp_update_post
		if ( strpos( $key, 'post_' ) !== 0 ) {
			return 'post_' . $key;
		}

		return $key;
	}

	/**
	 * Resolve author display name or login to user ID
	 *
	 * @param mixed $author  Display name, login, or ID
	 * @return int|false
	 */
	private function resolve_author_id( mixed $author ): int|false {
		if ( is_numeric( $author ) ) {
			return absint( $author );
		}

		// Try by login first
		$user = get_user_by( 'login', (string) $author );
		if ( $user ) {
			return $user->ID;
		}

		// Try by display name
		$users = get_users(
			array(
				'search'         => (string) $author,
				'search_columns' => array( 'display_name' ),
				'number'         => 1,
			)
		);

		return ! empty( $users ) ? $users[0]->ID : false;
	}
}
