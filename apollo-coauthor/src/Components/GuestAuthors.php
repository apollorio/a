<?php

/**
 * Apollo CoAuthor — Guest Authors Component.
 *
 * Manages guest authors (non-WP-user co-authors) so that people who don't
 * have dashboard accounts can still be credited on posts.
 *
 * Guest authors are stored as terms in the `coauthor` taxonomy (registry-
 * defined) with profile data in term_meta. Terms use a `cap-` slug prefix
 * to distinguish them from regular WP-user terms.
 *
 * Adapted from Co-Authors Plus class-coauthors-guest-authors.php.
 * Registry-compliant: no CPT, no REST (coauthor.cpts = []).
 *
 * @package Apollo\CoAuthor
 * @since   1.1.0
 */

declare(strict_types=1);

namespace Apollo\CoAuthor\Components;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Guest Authors — taxonomy-term-based management.
 *
 * @since 1.1.0
 */
class GuestAuthors {


	/**
	 * Slug prefix that identifies a guest-author term.
	 */
	public const GUEST_SLUG_PREFIX = 'cap-';

	/**
	 * Cache group for guest authors.
	 */
	private const CACHE_GROUP = 'apollo-guest-authors';

	/**
	 * Term-meta keys for guest-author profile data.
	 *
	 * Every key is stored via `update_term_meta()` on the coauthor taxonomy term.
	 */
	private const META_FIELDS = array(
		'_guest_display_name'   => array(
			'label'    => 'Nome de exibição',
			'type'     => 'text',
			'required' => true,
		),
		'_guest_first_name'     => array(
			'label'    => 'Nome',
			'type'     => 'text',
			'required' => false,
		),
		'_guest_last_name'      => array(
			'label'    => 'Sobrenome',
			'type'     => 'text',
			'required' => false,
		),
		'_guest_user_email'     => array(
			'label'    => 'E-mail',
			'type'     => 'email',
			'required' => false,
		),
		'_guest_website'        => array(
			'label'    => 'Website',
			'type'     => 'url',
			'required' => false,
		),
		'_guest_description'    => array(
			'label'    => 'Biografia',
			'type'     => 'textarea',
			'required' => false,
		),
		'_guest_linked_account' => array(
			'label'    => 'Conta WP vinculada',
			'type'     => 'text',
			'required' => false,
		),
		'_guest_avatar_url'     => array(
			'label'    => 'URL do Avatar',
			'type'     => 'url',
			'required' => false,
		),
	);

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		// Admin page for managing guest authors.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );

		// Create guest author from user action.
		add_action( 'admin_init', array( $this, 'handle_create_from_user' ) );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// Allow guest author to be used as co-author in search.
		add_filter( 'apollo/coauthor/search_results', array( $this, 'include_guest_in_search' ), 10, 2 );

		// Avatar support via term_meta.
		add_filter( 'get_avatar_url', array( $this, 'filter_avatar_url' ), 10, 3 );

		// Add link in Users list for creating guest profiles.
		add_filter( 'user_row_actions', array( $this, 'user_row_actions' ), 10, 2 );
	}

	// -------------------------------------------------------------------------
	// Admin page
	// -------------------------------------------------------------------------

	/**
	 * Add "Guest Authors" under Users menu.
	 */
	public function admin_menu(): void {
		add_users_page(
			__( 'Autores Convidados', 'apollo-coauthor' ),
			__( 'Autores Convidados', 'apollo-coauthor' ),
			'list_users',
			'apollo-guest-authors',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render the guest authors admin page (list / add / edit).
	 */
	public function render_admin_page(): void {
		$action  = sanitize_key( $_GET['guest_action'] ?? 'list' );
		$term_id = absint( $_GET['term_id'] ?? 0 );

		echo '<div class="wrap">';

		if ( 'edit' === $action && $term_id ) {
			$this->render_edit_form( $term_id );
		} elseif ( 'add' === $action ) {
			$this->render_add_form();
		} else {
			$this->render_list();
		}

		echo '</div>';
	}

	/**
	 * Render guest authors list.
	 */
	private function render_list(): void {
		$guests  = $this->get_all_guest_authors();
		$add_url = admin_url( 'users.php?page=apollo-guest-authors&guest_action=add' );

		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Autores Convidados', 'apollo-coauthor' ) . '</h1>';
		echo ' <a href="' . esc_url( $add_url ) . '" class="page-title-action">'
			. esc_html__( 'Adicionar Novo', 'apollo-coauthor' ) . '</a>';
		echo '<hr class="wp-header-end">';

		if ( empty( $guests ) ) {
			echo '<p>' . esc_html__( 'Nenhum autor convidado encontrado.', 'apollo-coauthor' ) . '</p>';
			return;
		}

		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Nome', 'apollo-coauthor' ) . '</th>';
		echo '<th>' . esc_html__( 'Slug', 'apollo-coauthor' ) . '</th>';
		echo '<th>' . esc_html__( 'E-mail', 'apollo-coauthor' ) . '</th>';
		echo '<th>' . esc_html__( 'Posts', 'apollo-coauthor' ) . '</th>';
		echo '<th>' . esc_html__( 'Ações', 'apollo-coauthor' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $guests as $guest ) {
			$edit_url   = admin_url( 'users.php?page=apollo-guest-authors&guest_action=edit&term_id=' . $guest->term_id );
			$delete_url = wp_nonce_url(
				admin_url( 'users.php?page=apollo-guest-authors&admin_action=delete_guest&term_id=' . $guest->term_id ),
				'delete_guest_' . $guest->term_id
			);

			echo '<tr>';
			echo '<td><strong><a href="' . esc_url( $edit_url ) . '">' . esc_html( $guest->display_name ) . '</a></strong></td>';
			echo '<td>' . esc_html( $guest->slug ) . '</td>';
			echo '<td>' . esc_html( $guest->user_email ) . '</td>';
			echo '<td>' . absint( $guest->count ) . '</td>';
			echo '<td>';
			echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Editar', 'apollo-coauthor' ) . '</a>';
			echo ' | <a href="' . esc_url( $delete_url ) . '" class="delete" onclick="return confirm(\''
				. esc_js( __( 'Tem certeza?', 'apollo-coauthor' ) ) . '\')">'
				. esc_html__( 'Excluir', 'apollo-coauthor' ) . '</a>';
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render the add guest author form.
	 */
	private function render_add_form(): void {
		$back_url = admin_url( 'users.php?page=apollo-guest-authors' );
		echo '<h1>' . esc_html__( 'Adicionar Autor Convidado', 'apollo-coauthor' ) . '</h1>';
		echo '<a href="' . esc_url( $back_url ) . '">&larr; ' . esc_html__( 'Voltar', 'apollo-coauthor' ) . '</a>';

		echo '<form method="post" action="' . esc_url( admin_url( 'users.php?page=apollo-guest-authors' ) ) . '">';
		wp_nonce_field( 'apollo_guest_save', 'apollo_guest_nonce' );
		echo '<input type="hidden" name="admin_action" value="save_guest" />';

		$this->render_fields();

		submit_button( __( 'Criar Autor Convidado', 'apollo-coauthor' ) );
		echo '</form>';
	}

	/**
	 * Render the edit guest author form.
	 *
	 * @param int $term_id Term ID.
	 */
	private function render_edit_form( int $term_id ): void {
		$term = get_term( $term_id, APOLLO_COAUTHOR_TAX );
		if ( ! $term || is_wp_error( $term ) ) {
			echo '<div class="notice notice-error"><p>'
				. esc_html__( 'Autor convidado não encontrado.', 'apollo-coauthor' ) . '</p></div>';
			return;
		}

		$back_url = admin_url( 'users.php?page=apollo-guest-authors' );
		echo '<h1>' . esc_html__( 'Editar Autor Convidado', 'apollo-coauthor' ) . '</h1>';
		echo '<a href="' . esc_url( $back_url ) . '">&larr; ' . esc_html__( 'Voltar', 'apollo-coauthor' ) . '</a>';

		echo '<form method="post" action="' . esc_url( admin_url( 'users.php?page=apollo-guest-authors' ) ) . '">';
		wp_nonce_field( 'apollo_guest_save', 'apollo_guest_nonce' );
		echo '<input type="hidden" name="admin_action" value="save_guest" />';
		echo '<input type="hidden" name="term_id" value="' . absint( $term_id ) . '" />';

		$this->render_fields( $term_id );

		submit_button( __( 'Atualizar Autor Convidado', 'apollo-coauthor' ) );
		echo '</form>';
	}

	/**
	 * Render profile fields for add/edit form.
	 *
	 * @param int $term_id Existing term ID (0 for new).
	 */
	private function render_fields( int $term_id = 0 ): void {
		echo '<table class="form-table" role="presentation">';

		// Slug field.
		$slug_value = '';
		if ( $term_id ) {
			$term       = get_term( $term_id, APOLLO_COAUTHOR_TAX );
			$slug_value = $term ? str_replace( self::GUEST_SLUG_PREFIX, '', $term->slug ) : '';
		}
		echo '<tr><th scope="row"><label for="guest_slug">'
			. esc_html__( 'Slug', 'apollo-coauthor' ) . ' <span class="required">*</span></label></th>';
		echo '<td><input type="text" name="guest_slug" id="guest_slug" value="'
			. esc_attr( $slug_value ) . '" class="apollo-input regular-text" required />';
		echo '<p class="description">'
			. esc_html__( 'Identificador único (sem espaços).', 'apollo-coauthor' ) . '</p></td></tr>';

		// Profile meta fields.
		foreach ( self::META_FIELDS as $key => $field ) {
			$value    = $term_id ? (string) get_term_meta( $term_id, $key, true ) : '';
			$required = ! empty( $field['required'] ) ? ' <span class="required">*</span>' : '';
			$req_attr = ! empty( $field['required'] ) ? ' required' : '';

			echo '<tr><th scope="row"><label for="' . esc_attr( $key ) . '">'
				. esc_html( $field['label'] ) . $required . '</label></th>';
			echo '<td>';

			if ( 'textarea' === $field['type'] ) {
				echo '<textarea name="' . esc_attr( $key ) . '" id="' . esc_attr( $key )
					. '" rows="5" class="apollo-textarea large-text"' . $req_attr . '>'
					. esc_textarea( $value ) . '</textarea>';
			} else {
				echo '<input type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $key )
					. '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $value )
					. '" class="regular-text"' . $req_attr . ' />';
			}

			echo '</td></tr>';
		}

		echo '</table>';
	}

	// -------------------------------------------------------------------------
	// Admin action handlers
	// -------------------------------------------------------------------------

	/**
	 * Handle form submissions for creating / updating / deleting guest authors.
	 */
	public function handle_admin_actions(): void {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action = sanitize_key( $_REQUEST['admin_action'] ?? '' );

		if ( 'save_guest' === $action ) {
			$this->process_save_guest();
		} elseif ( 'delete_guest' === $action ) {
			$this->process_delete_guest();
		}
        // phpcs:enable
	}

	/**
	 * Process save (create/update) guest author form.
	 */
	private function process_save_guest(): void {
		if (
			! isset( $_POST['apollo_guest_nonce'] )
			|| ! wp_verify_nonce( $_POST['apollo_guest_nonce'], 'apollo_guest_save' )
		) {
			return;
		}

		if ( ! current_user_can( 'list_users' ) ) {
			wp_die( esc_html__( 'Permissão insuficiente.', 'apollo-coauthor' ) );
		}

		$term_id  = absint( $_POST['term_id'] ?? 0 );
		$slug_raw = sanitize_title( $_POST['guest_slug'] ?? '' );

		if ( ! $slug_raw ) {
			wp_safe_redirect(
				add_query_arg(
					'message',
					'missing-slug',
					admin_url( 'users.php?page=apollo-guest-authors&guest_action=add' )
				)
			);
			exit;
		}

		$term_slug    = self::GUEST_SLUG_PREFIX . $slug_raw;
		$display_name = sanitize_text_field( $_POST['_guest_display_name'] ?? $slug_raw );

		if ( $term_id ) {
			// Update existing term.
			wp_update_term(
				$term_id,
				APOLLO_COAUTHOR_TAX,
				array(
					'name' => $display_name,
					'slug' => $term_slug,
				)
			);
		} else {
			// Check for duplicates.
			$existing = get_term_by( 'slug', $term_slug, APOLLO_COAUTHOR_TAX );
			if ( $existing ) {
				wp_safe_redirect(
					add_query_arg(
						'message',
						'duplicate-slug',
						admin_url( 'users.php?page=apollo-guest-authors&guest_action=add' )
					)
				);
				exit;
			}

			$result = wp_insert_term(
				$display_name,
				APOLLO_COAUTHOR_TAX,
				array(
					'slug' => $term_slug,
				)
			);

			if ( is_wp_error( $result ) ) {
				wp_safe_redirect(
					add_query_arg(
						'message',
						'error',
						admin_url( 'users.php?page=apollo-guest-authors&guest_action=add' )
					)
				);
				exit;
			}
			$term_id = $result['term_id'];
		}

		// Mark as guest author.
		update_term_meta( $term_id, '_is_guest_author', '1' );

		// Save meta fields.
		foreach ( self::META_FIELDS as $key => $field ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$value = wp_unslash( $_POST[ $key ] );

			switch ( $field['type'] ) {
				case 'email':
					$value = sanitize_email( $value );
					break;
				case 'url':
					$value = esc_url_raw( $value );
					break;
				case 'textarea':
					$value = sanitize_textarea_field( $value );
					break;
				default:
					$value = sanitize_text_field( $value );
					break;
			}

			update_term_meta( $term_id, $key, $value );
		}

		wp_cache_delete( 'guest_' . $term_id, self::CACHE_GROUP );

		/**
		 * Fires after a guest author is saved.
		 *
		 * @param int $term_id Guest author term ID.
		 */
		do_action( 'apollo/coauthor/guest_saved', $term_id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'message'      => 'saved',
					'guest_action' => 'edit',
					'term_id'      => $term_id,
				),
				admin_url( 'users.php?page=apollo-guest-authors' )
			)
		);
		exit;
	}

	/**
	 * Process delete guest author.
	 */
	private function process_delete_guest(): void {
		$term_id = absint( $_GET['term_id'] ?? 0 );

		if ( ! $term_id ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'delete_guest_' . $term_id ) ) {
			return;
		}

		if ( ! current_user_can( 'delete_users' ) ) {
			wp_die( esc_html__( 'Permissão insuficiente.', 'apollo-coauthor' ) );
		}

		wp_delete_term( $term_id, APOLLO_COAUTHOR_TAX );
		wp_cache_delete( 'guest_' . $term_id, self::CACHE_GROUP );

		wp_safe_redirect(
			add_query_arg(
				'message',
				'deleted',
				admin_url( 'users.php?page=apollo-guest-authors' )
			)
		);
		exit;
	}

	// -------------------------------------------------------------------------
	// CRUD helpers
	// -------------------------------------------------------------------------

	/**
	 * Get all guest-author terms.
	 *
	 * @return array Array of guest-author objects.
	 */
	public function get_all_guest_authors(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => APOLLO_COAUTHOR_TAX,
				'hide_empty' => false,
				'meta_query' => array(
					array(
						'key'   => '_is_guest_author',
						'value' => '1',
					),
				),
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		return array_map( array( $this, 'build_guest_author_object' ), $terms );
	}

	/**
	 * Get a guest author by field.
	 *
	 * @param string $field Field name ('term_id', 'slug', 'user_login', 'user_email').
	 * @param mixed  $value Field value.
	 * @return object|null Guest author data object or null.
	 */
	public function get_guest_author_by( string $field, $value ): ?object {
		$cache_key = $field . '_' . md5( (string) $value );
		$cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached ?: null;
		}

		$term = null;

		switch ( $field ) {
			case 'term_id':
			case 'ID':
				$term = get_term( (int) $value, APOLLO_COAUTHOR_TAX );
				break;
			case 'slug':
				$term = get_term_by( 'slug', $value, APOLLO_COAUTHOR_TAX );
				break;
			case 'user_login':
				$slug = self::GUEST_SLUG_PREFIX . sanitize_title( $value );
				$term = get_term_by( 'slug', $slug, APOLLO_COAUTHOR_TAX );
				break;
			case 'user_email':
				$terms = get_terms(
					array(
						'taxonomy'   => APOLLO_COAUTHOR_TAX,
						'hide_empty' => false,
						'number'     => 1,
						'meta_query' => array(
							array(
								'key'   => '_guest_user_email',
								'value' => sanitize_email( $value ),
							),
						),
					)
				);
				$term  = ! empty( $terms ) && ! is_wp_error( $terms ) ? $terms[0] : null;
				break;
			default:
				return null;
		}

		if ( ! $term || is_wp_error( $term ) ) {
			wp_cache_set( $cache_key, '', self::CACHE_GROUP, 3600 );
			return null;
		}

		// Verify it's a guest author.
		if ( ! self::is_guest_term( $term ) ) {
			wp_cache_set( $cache_key, '', self::CACHE_GROUP, 3600 );
			return null;
		}

		$author = $this->build_guest_author_object( $term );
		wp_cache_set( $cache_key, $author, self::CACHE_GROUP, 3600 );
		return $author;
	}

	/**
	 * Build a standardized guest-author object from a taxonomy term.
	 *
	 * @param \WP_Term $term Coauthor taxonomy term.
	 * @return object
	 */
	private function build_guest_author_object( \WP_Term $term ): object {
		$data = array(
			'term_id'       => $term->term_id,
			'ID'            => $term->term_id,
			'type'          => 'guest-author',
			'slug'          => $term->slug,
			'user_nicename' => $term->slug,
			'count'         => $term->count,
		);

		// Read profile meta.
		$tid = $term->term_id;

		$data['display_name']   = (string) get_term_meta( $tid, '_guest_display_name', true ) ?: $term->name;
		$data['first_name']     = (string) get_term_meta( $tid, '_guest_first_name', true );
		$data['last_name']      = (string) get_term_meta( $tid, '_guest_last_name', true );
		$data['user_email']     = (string) get_term_meta( $tid, '_guest_user_email', true );
		$data['user_login']     = str_replace( self::GUEST_SLUG_PREFIX, '', $term->slug );
		$data['website']        = (string) get_term_meta( $tid, '_guest_website', true );
		$data['description']    = (string) get_term_meta( $tid, '_guest_description', true );
		$data['linked_account'] = (string) get_term_meta( $tid, '_guest_linked_account', true );

		// Avatar.
		$avatar_url = (string) get_term_meta( $tid, '_guest_avatar_url', true );
		if ( $avatar_url ) {
			$data['avatar_url'] = $avatar_url;
		} else {
			$data['avatar_url'] = ! empty( $data['user_email'] )
				? get_avatar_url( $data['user_email'] )
				: '';
		}

		return (object) $data;
	}

	/**
	 * Create a guest author from an existing WP user.
	 *
	 * Adapted from Co-Authors Plus create_guest_author_from_user_id.
	 *
	 * @param int $user_id WP User ID.
	 * @return int|\WP_Error Guest author term ID or WP_Error.
	 */
	public function create_from_user( int $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return new \WP_Error(
				'invalid_user',
				__( 'Usuário não encontrado.', 'apollo-coauthor' )
			);
		}

		// Check if guest author already exists for this user.
		$existing = $this->get_guest_author_by( 'user_login', $user->user_login );
		if ( $existing ) {
			return new \WP_Error(
				'already_exists',
				__( 'Já existe um autor convidado para este usuário.', 'apollo-coauthor' )
			);
		}

		$term_slug = self::GUEST_SLUG_PREFIX . sanitize_title( $user->user_login );

		$result = wp_insert_term(
			$user->display_name,
			APOLLO_COAUTHOR_TAX,
			array(
				'slug' => $term_slug,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$term_id = $result['term_id'];

		// Mark as guest author.
		update_term_meta( $term_id, '_is_guest_author', '1' );

		// Copy user data to term meta.
		update_term_meta( $term_id, '_guest_display_name', $user->display_name );
		update_term_meta( $term_id, '_guest_first_name', $user->first_name );
		update_term_meta( $term_id, '_guest_last_name', $user->last_name );
		update_term_meta( $term_id, '_guest_user_email', $user->user_email );
		update_term_meta( $term_id, '_guest_website', $user->user_url );
		update_term_meta( $term_id, '_guest_description', $user->description );
		update_term_meta( $term_id, '_guest_linked_account', $user->user_login );

		/**
		 * Fires after a guest author is created from a WP user.
		 *
		 * @param int $term_id Guest author term ID.
		 * @param int $user_id WP User ID.
		 */
		do_action( 'apollo/coauthor/guest_created', $term_id, $user_id );

		return $term_id;
	}

	// -------------------------------------------------------------------------
	// Search & avatar integration
	// -------------------------------------------------------------------------

	/**
	 * Include guest authors in co-author search results.
	 *
	 * @param array  $results Current search results.
	 * @param string $search  Search query.
	 * @return array
	 */
	public function include_guest_in_search( array $results, string $search ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => APOLLO_COAUTHOR_TAX,
				'hide_empty' => false,
				'number'     => 10,
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'   => '_is_guest_author',
						'value' => '1',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => '_guest_display_name',
							'value'   => $search,
							'compare' => 'LIKE',
						),
						array(
							'key'     => '_guest_user_email',
							'value'   => $search,
							'compare' => 'LIKE',
						),
					),
				),
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $results;
		}

		foreach ( $terms as $term ) {
			$guest     = $this->build_guest_author_object( $term );
			$results[] = array(
				'id'           => $term->term_id,
				'display_name' => $guest->display_name,
				'user_login'   => $guest->user_login,
				'avatar_url'   => $guest->avatar_url,
				'type'         => 'guest-author',
			);
		}

		return $results;
	}

	/**
	 * Filter avatar URL for guest authors.
	 *
	 * If the subject is a guest author with a stored avatar URL, use that.
	 *
	 * @param string $url         Avatar URL.
	 * @param mixed  $id_or_email User ID, email, or WP_Comment object.
	 * @param array  $args        Avatar arguments.
	 * @return string
	 */
	public function filter_avatar_url( string $url, $id_or_email, array $args ): string {
		if (
			is_object( $id_or_email )
			&& isset( $id_or_email->type )
			&& 'guest-author' === $id_or_email->type
		) {
			$avatar = ! empty( $id_or_email->avatar_url ) ? $id_or_email->avatar_url : '';
			if ( $avatar ) {
				return $avatar;
			}
		}

		return $url;
	}

	// -------------------------------------------------------------------------
	// Admin actions (create from user, user row)
	// -------------------------------------------------------------------------

	/**
	 * Handle admin action to create guest author from user.
	 */
	public function handle_create_from_user(): void {
		if ( ! isset( $_GET['action'], $_GET['nonce'], $_GET['user_id'] ) ) {
			return;
		}

		if ( 'apollo-create-guest-author' !== $_GET['action'] ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['nonce'], 'create-guest-author' ) ) {
			wp_die( esc_html__( 'Falha na verificação de segurança.', 'apollo-coauthor' ) );
		}

		if ( ! current_user_can( 'list_users' ) ) {
			wp_die( esc_html__( 'Permissão insuficiente.', 'apollo-coauthor' ) );
		}

		$user_id = absint( $_GET['user_id'] );
		$result  = $this->create_from_user( $user_id );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		$edit_url = admin_url(
			'users.php?page=apollo-guest-authors&guest_action=edit&term_id=' . $result
		);
		wp_safe_redirect( add_query_arg( 'message', 'guest-author-created', $edit_url ) );
		exit;
	}

	/**
	 * Add "Create Guest Profile" action in Users list.
	 *
	 * @param array    $actions Existing row actions.
	 * @param \WP_User $user    User object.
	 * @return array
	 */
	public function user_row_actions( array $actions, \WP_User $user ): array {
		if ( ! current_user_can( 'list_users' ) ) {
			return $actions;
		}

		// Check if guest author already exists for this user.
		$existing = $this->get_guest_author_by( 'user_login', $user->user_login );
		if ( $existing ) {
			$edit_url                     = admin_url(
				'users.php?page=apollo-guest-authors&guest_action=edit&term_id=' . $existing->term_id
			);
			$actions['edit-guest-author'] = '<a href="' . esc_url( $edit_url ) . '">'
				. esc_html__( 'Editar Perfil Convidado', 'apollo-coauthor' ) . '</a>';
		} else {
			$url                            = add_query_arg(
				array(
					'action'  => 'apollo-create-guest-author',
					'user_id' => $user->ID,
					'nonce'   => wp_create_nonce( 'create-guest-author' ),
				),
				admin_url( 'users.php' )
			);
			$actions['create-guest-author'] = '<a href="' . esc_url( $url ) . '">'
				. esc_html__( 'Criar Perfil Convidado', 'apollo-coauthor' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Admin notices for guest author operations.
	 */
	public function admin_notices(): void {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'apollo-guest-authors' !== $_GET['page'] ) {
			return;
		}

		$message = sanitize_key( $_GET['message'] ?? '' );
		if ( ! $message ) {
			return;
		}

		$notices = array(
			'saved'                => array( 'success', __( 'Autor convidado salvo com sucesso.', 'apollo-coauthor' ) ),
			'deleted'              => array( 'success', __( 'Autor convidado excluído.', 'apollo-coauthor' ) ),
			'guest-author-created' => array( 'success', __( 'Autor convidado criado com sucesso.', 'apollo-coauthor' ) ),
			'missing-slug'         => array( 'error', __( 'Slug é obrigatório.', 'apollo-coauthor' ) ),
			'duplicate-slug'       => array( 'error', __( 'Já existe um autor convidado com este slug.', 'apollo-coauthor' ) ),
			'error'                => array( 'error', __( 'Ocorreu um erro. Tente novamente.', 'apollo-coauthor' ) ),
		);

		if ( isset( $notices[ $message ] ) ) {
			list($type, $text) = $notices[ $message ];
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>'
				. esc_html( $text ) . '</p></div>';
		}
        // phpcs:enable
	}

	// -------------------------------------------------------------------------
	// Static helpers
	// -------------------------------------------------------------------------

	/**
	 * Check if a taxonomy term represents a guest author.
	 *
	 * @param \WP_Term $term Term object.
	 * @return bool
	 */
	public static function is_guest_term( \WP_Term $term ): bool {
		if ( APOLLO_COAUTHOR_TAX !== $term->taxonomy ) {
			return false;
		}

		return '1' === get_term_meta( $term->term_id, '_is_guest_author', true );
	}
}