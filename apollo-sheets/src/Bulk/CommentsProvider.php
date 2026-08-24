<?php

/**
 * Comments Provider — fetches + saves comment data for bulk spreadsheet
 *
 * Uses WP_Comment_Query for loading, wp_update_comment for saving.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets\Bulk;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CommentsProvider {


	private ColumnRegistry $registry;

	public function __construct( ColumnRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Load comment rows
	 *
	 * @param array $args {
	 *     @type int    $per_page
	 *     @type int    $page
	 *     @type string $search
	 *     @type string $status   approved|hold|spam|trash|all
	 *     @type string $orderby
	 *     @type string $order
	 * }
	 * @return array { rows: array[], total: int, pages: int }
	 */
	public function load_rows( array $args = array() ): array {
		$per_page = absint( $args['per_page'] ?? 50 );
		$page     = absint( $args['page'] ?? 1 );
		$search   = sanitize_text_field( $args['search'] ?? '' );
		$status   = sanitize_text_field( $args['status'] ?? 'all' );
		$orderby  = sanitize_key( $args['orderby'] ?? 'comment_date' );
		$order    = in_array( strtoupper( $args['order'] ?? 'DESC' ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

		$query_args = array(
			'number'  => $per_page,
			'offset'  => ( $page - 1 ) * $per_page,
			'orderby' => $orderby,
			'order'   => $order,
			'status'  => $status,
			'count'   => false,
		);

		if ( ! empty( $search ) ) {
			$query_args['search'] = $search;
		}

		/**
		 * Filter WP_Comment_Query args
		 *
		 * @param array $query_args
		 */
		$query_args = apply_filters( 'apollo/sheets/bulk/comment_query_args', $query_args );

		$comments = get_comments( $query_args );

		// Get total count
		$count_args          = $query_args;
		$count_args['count'] = true;
		unset( $count_args['number'], $count_args['offset'] );
		$total = (int) get_comments( $count_args );

		$columns = $this->registry->get_columns( 'comments' );
		$rows    = array();

		foreach ( $comments as $comment ) {
			$row = array();

			foreach ( $columns as $key => $config ) {
				$row[ $key ] = $this->get_cell_value( $comment, $key, $config );
			}

			$rows[] = $row;
		}

		return array(
			'rows'  => $rows,
			'total' => $total,
			'pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 1,
		);
	}

	/**
	 * Get individual cell value
	 *
	 * @param \WP_Comment $comment
	 * @param string      $key
	 * @param array       $config
	 * @return mixed
	 */
	private function get_cell_value( \WP_Comment $comment, string $key, array $config ): mixed {
		return match ( $key ) {
			'comment_ID'           => (int) $comment->comment_ID,
			'comment_post_ID'      => (int) $comment->comment_post_ID,
			'comment_author'       => $comment->comment_author,
			'comment_author_email' => $comment->comment_author_email,
			'comment_author_url'   => $comment->comment_author_url,
			'comment_content'      => wp_trim_words( wp_strip_all_tags( $comment->comment_content ), 40, '…' ),
			'comment_date'         => $comment->comment_date,
			'comment_approved'     => $comment->comment_approved,
			'comment_type'         => $comment->comment_type ?: 'comment',
			'comment_parent'       => (int) $comment->comment_parent,
			'user_id'              => (int) $comment->user_id,
			default                => $comment->$key ?? '',
		};
	}

	/**
	 * Save batch of modified comment rows
	 *
	 * @param array $rows  Array of rows with 'comment_ID' key
	 * @return array { saved: int, errors: string[] }
	 */
	public function save_rows( array $rows ): array {
		$columns = $this->registry->get_columns( 'comments' );
		$saved   = 0;
		$errors  = array();

		foreach ( $rows as $row ) {
			$comment_id = absint( $row['comment_ID'] ?? 0 );
			if ( empty( $comment_id ) ) {
				$errors[] = __( 'Linha sem comment_ID ignorada.', 'apollo-sheets' );
				continue;
			}

			$comment = get_comment( $comment_id );
			if ( ! $comment ) {
				$errors[] = sprintf( __( 'Comentário ID %d não encontrado.', 'apollo-sheets' ), $comment_id );
				continue;
			}

			if ( ! current_user_can( 'moderate_comments' ) ) {
				$errors[] = sprintf( __( 'Sem permissão para moderar comentário ID %d.', 'apollo-sheets' ), $comment_id );
				continue;
			}

			$comment_data = array( 'comment_ID' => $comment_id );
			$has_update   = false;

			foreach ( $columns as $key => $config ) {
				if ( ! $config['editable'] || ! isset( $row[ $key ] ) ) {
					continue;
				}

				$value = $row[ $key ];

				if ( $config['data_type'] === 'comment_data' ) {
					if ( $key === 'comment_content' ) {
						$comment_data[ $key ] = wp_kses_post( $value );
					} else {
						$comment_data[ $key ] = sanitize_text_field( (string) $value );
					}
					$has_update = true;
				}
			}

			if ( $has_update && count( $comment_data ) > 1 ) {
				$result = wp_update_comment( $comment_data, true );
				if ( is_wp_error( $result ) ) {
					$errors[] = sprintf( __( 'Erro ao salvar comentário ID %1$d: %2$s', 'apollo-sheets' ), $comment_id, $result->get_error_message() );
					continue;
				}
				++$saved;
			}
		}

		return array( 'saved' => $saved, 'errors' => $errors );
	}

	/**
	 * Save batch of modified changes
	 *
	 * @param array $changes Array of {id, data: {key: value}}
	 * @return array { saved: int, errors: string[] }
	 */
	public function save_changes( array $changes ): array {
		$columns = $this->registry->get_columns( 'comments' );
		$saved   = 0;
		$errors  = array();

		foreach ( $changes as $change ) {
			$comment_id = absint( $change['id'] ?? 0 );
			$data       = $change['data'] ?? array();

			if ( empty( $comment_id ) ) {
				$errors[] = __( 'Alteração sem ID ignorada.', 'apollo-sheets' );
				continue;
			}

			$comment = get_comment( $comment_id );
			if ( ! $comment ) {
				$errors[] = sprintf( __( 'Comentário ID %d não encontrado.', 'apollo-sheets' ), $comment_id );
				continue;
			}

			// Verify user can edit this comment
			if ( ! current_user_can( 'edit_comment', $comment_id ) ) {
				$errors[] = sprintf( __( 'Sem permissão para editar comentário ID %d.', 'apollo-sheets' ), $comment_id );
				continue;
			}

			$comment_data = array( 'comment_ID' => $comment_id );
			$has_update   = false;

			foreach ( $data as $key => $value ) {
				$config = $columns[ $key ] ?? null;
				if ( ! $config || ! $config['editable'] ) {
					continue;
				}

				if ( $config['data_type'] === 'comment_data' ) {
					if ( $key === 'comment_content' ) {
						$comment_data[ $key ] = wp_kses_post( $value );
					} else {
						$comment_data[ $key ] = sanitize_text_field( (string) $value );
					}
					$has_update = true;
				}
			}

			if ( $has_update && count( $comment_data ) > 1 ) {
				$result = wp_update_comment( $comment_data, true );
				if ( is_wp_error( $result ) ) {
					$errors[] = sprintf( __( 'Erro ao salvar comentário ID %1$d: %2$s', 'apollo-sheets' ), $comment_id, $result->get_error_message() );
					continue;
				}
				++$saved;
			}
		}

		return array(
			'saved'  => $saved,
			'errors' => $errors,
		);
	}
}
