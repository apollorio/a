<?php

/**
 * Users Provider — fetches + saves user data for bulk spreadsheet
 *
 * Uses WP_User_Query for loading, wp_update_user / update_user_meta for saving.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets\Bulk;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UsersProvider {


	private ColumnRegistry $registry;

	public function __construct( ColumnRegistry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Load user rows
	 *
	 * @param array $args {
	 *     @type int    $per_page
	 *     @type int    $page
	 *     @type string $search
	 *     @type string $role
	 *     @type string $orderby
	 *     @type string $order
	 * }
	 * @return array { rows: array[], total: int, pages: int }
	 */
	public function load_rows( array $args = array() ): array {
		$per_page = absint( $args['per_page'] ?? 50 );
		$page     = absint( $args['page'] ?? 1 );
		$search   = sanitize_text_field( $args['search'] ?? '' );
		$role     = sanitize_text_field( $args['role'] ?? '' );
		$orderby  = sanitize_key( $args['orderby'] ?? 'ID' );
		$order    = in_array( strtoupper( $args['order'] ?? 'DESC' ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';

		$query_args = array(
			'number'  => $per_page,
			'paged'   => $page,
			'orderby' => $orderby,
			'order'   => $order,
		);

		if ( ! empty( $search ) ) {
			$query_args['search']         = '*' . $search . '*';
			$query_args['search_columns'] = array( 'user_login', 'user_email', 'display_name', 'user_nicename' );
		}

		if ( ! empty( $role ) ) {
			$query_args['role'] = $role;
		}

		/**
		 * Filter WP_User_Query args
		 *
		 * @param array $query_args
		 */
		$query_args = apply_filters( 'apollo/sheets/bulk/user_query_args', $query_args );

		$query   = new \WP_User_Query( $query_args );
		$columns = $this->registry->get_columns( 'users' );
		$users   = $query->get_results();
		$rows    = array();

		foreach ( $users as $user ) {
			$row = array();

			foreach ( $columns as $key => $config ) {
				$row[ $key ] = $this->get_cell_value( $user, $key, $config );
			}

			$rows[] = $row;
		}

		$total = (int) $query->get_total();

		return array(
			'rows'  => $rows,
			'total' => $total,
			'pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 1,
		);
	}

	/**
	 * Get individual cell value
	 *
	 * @param \WP_User $user
	 * @param string   $key
	 * @param array    $config
	 * @return mixed
	 */
	private function get_cell_value( \WP_User $user, string $key, array $config ): mixed {
		if ( $config['data_type'] === 'user_data' ) {
			return $this->get_user_field( $user, $key );
		}

		if ( $config['data_type'] === 'user_meta' ) {
			return get_user_meta( $user->ID, $key, true );
		}

		return '';
	}

	/**
	 * Get WP_User field
	 *
	 * @param \WP_User $user
	 * @param string   $key
	 * @return mixed
	 */
	private function get_user_field( \WP_User $user, string $key ): mixed {
		return match ( $key ) {
			'ID'              => $user->ID,
			'user_login'      => $user->user_login,
			'user_email'      => $user->user_email,
			'display_name'    => $user->display_name,
			'user_nicename'   => $user->user_nicename,
			'user_url'        => $user->user_url,
			'user_registered' => $user->user_registered,
			'role'            => implode( ', ', $user->roles ),
			default           => $user->$key ?? '',
		};
	}

	/**
	 * Save batch of modified user rows
	 *
	 * @param array $rows  Array of rows with 'ID' key
	 * @return array { saved: int, errors: string[] }
	 */
	public function save_rows( array $rows ): array {
		$columns = $this->registry->get_columns( 'users' );
		$saved   = 0;
		$errors  = array();

		foreach ( $rows as $row ) {
			$user_id = absint( $row['ID'] ?? 0 );
			if ( empty( $user_id ) ) {
				$errors[] = __( 'Linha sem ID ignorada.', 'apollo-sheets' );
				continue;
			}

			$user = get_userdata( $user_id );
			if ( ! $user ) {
				$errors[] = sprintf( __( 'Usuário ID %d não encontrado.', 'apollo-sheets' ), $user_id );
				continue;
			}

			// Only admins can edit users
			if ( ! current_user_can( 'edit_users' ) ) {
				$errors[] = sprintf( __( 'Sem permissão para editar usuário ID %d.', 'apollo-sheets' ), $user_id );
				continue;
			}

			$user_data  = array( 'ID' => $user_id );
			$has_update = false;

			foreach ( $columns as $key => $config ) {
				if ( ! $config['editable'] || ! isset( $row[ $key ] ) ) {
					continue;
				}

				$value = $row[ $key ];

				if ( $config['data_type'] === 'user_data' ) {
					if ( $key === 'role' ) {
						// Change role — careful, only admins
						$roles        = array_map( 'trim', explode( ',', (string) $value ) );
						$primary_role = $roles[0] ?? '';
						if ( ! empty( $primary_role ) && wp_roles()->is_role( $primary_role ) ) {
							$user->set_role( $primary_role );
						}
						$has_update = true;
					} else {
						$user_data[ $key ] = sanitize_text_field( (string) $value );
						$has_update        = true;
					}
				} elseif ( $config['data_type'] === 'user_meta' ) {
					update_user_meta( $user_id, $key, sanitize_text_field( (string) $value ) );
					$has_update = true;
				}
			}

			if ( count( $user_data ) > 1 ) {
				$result = wp_update_user( $user_data );
				if ( is_wp_error( $result ) ) {
					$errors[] = sprintf( __( 'Erro ao salvar usuário ID %1$d: %2$s', 'apollo-sheets' ), $user_id, $result->get_error_message() );
					continue;
				}
			}

			if ( $has_update ) {
				++$saved;
			}
		}

		return array( 'saved' => $saved, 'errors' => $errors );
	}

	/**
	 * Save user changes
	 *
	 * @param array $changes Array of {id, data: {key: value}}
	 * @return array { saved: int, errors: string[] }
	 */
	public function save_changes( array $changes ): array {
		$saved  = 0;
		$errors = array();
		$columns = $this->registry->get_columns( 'users' );

		foreach ( $changes as $change ) {
			$user_id = absint( $change['id'] ?? 0 );
			$data    = $change['data'] ?? array();

			if ( empty( $user_id ) ) {
				$errors[] = __( 'Alteração sem ID ignorada.', 'apollo-sheets' );
				continue;
			}

			$user = get_userdata( $user_id );
			if ( ! $user ) {
				$errors[] = sprintf( __( 'Usuário ID %d não encontrado.', 'apollo-sheets' ), $user_id );
				continue;
			}

			// Only admins can edit users
			if ( ! current_user_can( 'edit_users' ) ) {
				$errors[] = sprintf( __( 'Sem permissão para editar usuário ID %d.', 'apollo-sheets' ), $user_id );
				continue;
			}

			$user_data  = array( 'ID' => $user_id );
			$has_update = false;

			foreach ( $data as $key => $value ) {
				$config = $columns[ $key ] ?? null;
				if ( ! $config || ! $config['editable'] ) {
					continue;
				}

				if ( $config['data_type'] === 'user_data' ) {
					if ( $key === 'role' ) {
						// Change role — careful, only admins
						$roles        = array_map( 'trim', explode( ',', (string) $value ) );
						$primary_role = $roles[0] ?? '';
						if ( ! empty( $primary_role ) && wp_roles()->is_role( $primary_role ) ) {
							$user->set_role( $primary_role );
						}
						$has_update = true;
					} else {
						$user_data[ $key ] = sanitize_text_field( (string) $value );
						$has_update        = true;
					}
				} elseif ( $config['data_type'] === 'user_meta' ) {
					update_user_meta( $user_id, $key, sanitize_text_field( (string) $value ) );
					$has_update = true;
				}
			}

			if ( count( $user_data ) > 1 ) {
				$result = wp_update_user( $user_data );
				if ( is_wp_error( $result ) ) {
					$errors[] = sprintf( __( 'Erro ao salvar usuário ID %1$d: %2$s', 'apollo-sheets' ), $user_id, $result->get_error_message() );
					continue;
				}
			}

			if ( $has_update ) {
				++$saved;
			}
		}

		return array(
			'saved'  => $saved,
			'errors' => $errors,
		);
	}
}
