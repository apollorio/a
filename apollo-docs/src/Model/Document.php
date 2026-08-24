<?php
namespace Apollo\Docs\Model;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Apollo\Docs\Storage;

/**
 * Document model — CRUD operations on `doc` CPT + version tracking.
 *
 * Meta keys (from registry):
 *   _doc_file_id    → Attachment ID
 *   _doc_folder_id  → Folder term ID
 *   _doc_access     → public | private | group | industry
 *   _doc_version    → Current version string
 *   _doc_downloads  → Download count
 *   _doc_status     → draft | locked | finalized | signed
 *   _doc_checksum   → SHA-256 hash of current content
 *   _doc_cpf        → Owner CPF (for legal binding)
 */
final class Document {

	/**
	 * Create a new document.
	 *
	 * @param array $data {title, content, author_id, folder_id?, type?, access?, cpf?}
	 * @return int|false Post ID or false on failure.
	 */
	public static function create( array $data ): int|false {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'doc',
				'post_title'   => sanitize_text_field( $data['title'] ?? 'Sem título' ),
				'post_content' => wp_kses_post( $data['content'] ?? '' ),
				'post_status'  => 'publish',
				'post_author'  => absint( $data['author_id'] ?? get_current_user_id() ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		/* Set meta */
		update_post_meta( $post_id, '_doc_version', '1.0' );
		update_post_meta( $post_id, '_doc_access', sanitize_text_field( $data['access'] ?? 'private' ) );
		update_post_meta( $post_id, '_doc_downloads', 0 );
		update_post_meta( $post_id, '_doc_status', 'draft' );

		if ( ! empty( $data['cpf'] ) ) {
			update_post_meta( $post_id, '_doc_cpf', sanitize_text_field( $data['cpf'] ) );
		}

		if ( ! empty( $data['file_id'] ) ) {
			update_post_meta( $post_id, '_doc_file_id', absint( $data['file_id'] ) );
		}

		/* Folder taxonomy */
		if ( ! empty( $data['folder_id'] ) ) {
			$folder_id = absint( $data['folder_id'] );
			wp_set_object_terms( $post_id, array( $folder_id ), 'doc_folder' );
			update_post_meta( $post_id, '_doc_folder_id', $folder_id );
		}

		/* Doc type taxonomy */
		if ( ! empty( $data['type'] ) ) {
			wp_set_object_terms( $post_id, array( sanitize_text_field( $data['type'] ) ), 'doc_type' );
		}

		/* Store version record */
		self::create_version( $post_id, '1.0', $data['content'] ?? '', get_current_user_id(), 'Criação inicial' );

		/* Save content to filesystem */
		Storage::save_document(
			$post_id,
			'1.0',
			array(
				'title'   => $data['title'] ?? '',
				'content' => $data['content'] ?? '',
				'meta'    => array( 'access' => $data['access'] ?? 'private' ),
			)
		);

		do_action( 'apollo/docs/created', $post_id, $data );

		return $post_id;
	}

	/**
	 * Update document content — auto-increments version.
	 */
	public static function update( int $doc_id, array $data ): bool {
		$post = get_post( $doc_id );
		if ( ! $post || $post->post_type !== 'doc' ) {
			return false;
		}

		$status = get_post_meta( $doc_id, '_doc_status', true );
		if ( in_array( $status, array( 'locked', 'finalized', 'signed' ), true ) ) {
			return false; /* Immutable once locked */
		}

		$update = array( 'ID' => $doc_id );

		if ( isset( $data['title'] ) ) {
			$update['post_title'] = sanitize_text_field( $data['title'] );
		}
		if ( isset( $data['content'] ) ) {
			$update['post_content'] = wp_kses_post( $data['content'] );
		}

		$result = wp_update_post( $update, true );
		if ( is_wp_error( $result ) ) {
			return false;
		}

		/* Update meta */
		if ( isset( $data['access'] ) ) {
			$access = sanitize_text_field( $data['access'] );
			if ( in_array( $access, array( 'public', 'private', 'group', 'industry' ), true ) ) {
				update_post_meta( $doc_id, '_doc_access', $access );
			}
		}

		if ( isset( $data['file_id'] ) ) {
			update_post_meta( $doc_id, '_doc_file_id', absint( $data['file_id'] ) );
		}

		if ( isset( $data['folder_id'] ) ) {
			$folder_id = absint( $data['folder_id'] );
			wp_set_object_terms( $doc_id, array( $folder_id ), 'doc_folder' );
			update_post_meta( $doc_id, '_doc_folder_id', $folder_id );
		}

		/* Increment version */
		$current_version = get_post_meta( $doc_id, '_doc_version', true ) ?: '1.0';
		$new_version     = self::increment_version( $current_version );
		update_post_meta( $doc_id, '_doc_version', $new_version );

		/* Store version record */
		$content = $data['content'] ?? $post->post_content;
		self::create_version( $doc_id, $new_version, $content, get_current_user_id(), $data['changelog'] ?? '' );

		/* Save to filesystem */
		Storage::save_document(
			$doc_id,
			$new_version,
			array(
				'title'   => $data['title'] ?? $post->post_title,
				'content' => $content,
				'meta'    => array( 'access' => get_post_meta( $doc_id, '_doc_access', true ) ),
			)
		);

		/* Checksum */
		$path = Storage::doc_path( $doc_id, $new_version );
		if ( file_exists( $path ) ) {
			update_post_meta( $doc_id, '_doc_checksum', Storage::checksum( $path ) );
		}

		do_action( 'apollo/docs/updated', $doc_id, $new_version, $data );

		return true;
	}

	/**
	 * Get a single document with all meta.
	 */
	public static function get( int $doc_id ): ?array {
		$post = get_post( $doc_id );
		if ( ! $post || $post->post_type !== 'doc' ) {
			return null;
		}

		return self::format( $post );
	}

	/**
	 * List documents with filters.
	 *
	 * @param array $args {folder_id?, type?, access?, author_id?, status?, search?, per_page?, page?}
	 */
	public static function list( array $args = array() ): array {
		$query_args = array(
			'post_type'      => 'doc',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $args['per_page'] ?? 20 ),
			'paged'          => absint( $args['page'] ?? 1 ),
			'orderby'        => 'modified',
			'order'          => 'DESC',
		);

		if ( ! empty( $args['author_id'] ) ) {
			$query_args['author'] = absint( $args['author_id'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$query_args['s'] = sanitize_text_field( $args['search'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$query_args['meta_query'][] = array(
				'key'   => '_doc_status',
				'value' => sanitize_text_field( $args['status'] ),
			);
		}

		if ( ! empty( $args['access'] ) ) {
			$query_args['meta_query'][] = array(
				'key'   => '_doc_access',
				'value' => sanitize_text_field( $args['access'] ),
			);
		}

		/* Taxonomy filters */
		$tax_query = array();

		if ( ! empty( $args['folder_id'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'doc_folder',
				'terms'    => absint( $args['folder_id'] ),
			);
		}

		if ( ! empty( $args['type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'doc_type',
				'field'    => 'slug',
				'terms'    => sanitize_text_field( $args['type'] ),
			);
		}

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query;
		}

		$query = new \WP_Query( $query_args );
		$docs  = array();

		foreach ( $query->posts as $post ) {
			$docs[] = self::format( $post );
		}

		return array(
			'items'    => $docs,
			'total'    => (int) $query->found_posts,
			'pages'    => (int) $query->max_num_pages,
			'page'     => absint( $args['page'] ?? 1 ),
			'per_page' => absint( $args['per_page'] ?? 20 ),
		);
	}

	/**
	 * Delete a document and all versions.
	 */
	public static function delete( int $doc_id ): bool {
		$post = get_post( $doc_id );
		if ( ! $post || $post->post_type !== 'doc' ) {
			return false;
		}

		$status = get_post_meta( $doc_id, '_doc_status', true );
		if ( $status === 'signed' ) {
			return false; /* Signed documents cannot be deleted */
		}

		/* Remove version files */
		$versions_dir = Storage::base_dir() . '/documents/' . $doc_id;
		if ( is_dir( $versions_dir ) ) {
			$files = glob( $versions_dir . '/*' );
			foreach ( $files as $f ) {
				if ( is_file( $f ) ) {
					unlink( $f );
				}
			}
			rmdir( $versions_dir );
		}

		/* Remove PDF files */
		$pdfs_dir = Storage::base_dir() . '/pdfs/' . $doc_id;
		if ( is_dir( $pdfs_dir ) ) {
			$files = glob( $pdfs_dir . '/*' );
			foreach ( $files as $f ) {
				if ( is_file( $f ) ) {
					unlink( $f );
				}
			}
			rmdir( $pdfs_dir );
		}

		/* Remove version DB records */
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'apollo_doc_versions', array( 'doc_id' => $doc_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'apollo_doc_downloads', array( 'doc_id' => $doc_id ), array( '%d' ) );

		/* Delete attachment if any */
		$file_id = get_post_meta( $doc_id, '_doc_file_id', true );
		if ( $file_id ) {
			wp_delete_attachment( $file_id, true );
		}

		wp_delete_post( $doc_id, true );

		do_action( 'apollo/docs/deleted', $doc_id );

		return true;
	}

	/**
	 * Lock a document for signing.
	 */
	public static function lock( int $doc_id ): bool {
		$status = get_post_meta( $doc_id, '_doc_status', true );
		if ( $status !== 'draft' ) {
			return false;
		}

		update_post_meta( $doc_id, '_doc_status', 'locked' );
		do_action( 'apollo/docs/locked', $doc_id );

		return true;
	}

	/**
	 * Finalize a document (ready for signing).
	 */
	public static function finalize( int $doc_id ): bool {
		$status = get_post_meta( $doc_id, '_doc_status', true );
		if ( ! in_array( $status, array( 'draft', 'locked' ), true ) ) {
			return false;
		}

		update_post_meta( $doc_id, '_doc_status', 'finalized' );

		/* Generate checksum of final version */
		$version = get_post_meta( $doc_id, '_doc_version', true ) ?: '1.0';
		$path    = Storage::doc_path( $doc_id, $version );
		if ( file_exists( $path ) ) {
			update_post_meta( $doc_id, '_doc_checksum', Storage::checksum( $path ) );
		}

		do_action( 'apollo/docs/finalized', $doc_id );

		return true;
	}

	/**
	 * Mark document as signed.
	 */
	public static function mark_signed( int $doc_id ): bool {
		update_post_meta( $doc_id, '_doc_status', 'signed' );
		do_action( 'apollo/docs/signed', $doc_id );
		return true;
	}

	/* ── Version Management ────────────────────────────────── */

	/**
	 * Create version record in DB.
	 */
	public static function create_version( int $doc_id, string $version, string $content, int $author_id, string $changelog = '' ): int {
		global $wpdb;

		$checksum = hash( 'sha256', $content );

		$wpdb->insert(
			$wpdb->prefix . 'apollo_doc_versions',
			array(
				'doc_id'    => $doc_id,
				'version'   => $version,
				'content'   => $content,
				'checksum'  => $checksum,
				'author_id' => $author_id,
				'changelog' => sanitize_text_field( $changelog ),
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get all versions of a document.
	 */
	public static function get_versions( int $doc_id ): array {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.*, u.display_name as author_name
             FROM {$wpdb->prefix}apollo_doc_versions v
             LEFT JOIN {$wpdb->users} u ON v.author_id = u.ID
             WHERE v.doc_id = %d
             ORDER BY v.created_at DESC",
				$doc_id
			),
			ARRAY_A
		) ?: array();
	}

	/**
	 * Log a download event.
	 */
	public static function log_download( int $doc_id, int $user_id ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'apollo_doc_downloads',
			array(
				'doc_id'     => $doc_id,
				'user_id'    => $user_id,
				'ip'         => sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ),
				'user_agent' => sanitize_text_field( substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255 ) ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		/* Increment download counter */
		$count = (int) get_post_meta( $doc_id, '_doc_downloads', true );
		update_post_meta( $doc_id, '_doc_downloads', $count + 1 );
	}

	/* ── Helpers ───────────────────────────────────────────── */

	/**
	 * Format a post object into a document array.
	 */
	private static function format( \WP_Post $post ): array {
		$folders = wp_get_object_terms( $post->ID, 'doc_folder' );
		$types   = wp_get_object_terms( $post->ID, 'doc_type' );

		$author = get_userdata( $post->post_author );

		return array(
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'content'     => $post->post_content,
			'author_id'   => (int) $post->post_author,
			'author_name' => $author ? $author->display_name : '',
			'status'      => get_post_meta( $post->ID, '_doc_status', true ) ?: 'draft',
			'access'      => get_post_meta( $post->ID, '_doc_access', true ) ?: 'private',
			'version'     => get_post_meta( $post->ID, '_doc_version', true ) ?: '1.0',
			'file_id'     => (int) get_post_meta( $post->ID, '_doc_file_id', true ),
			'downloads'   => (int) get_post_meta( $post->ID, '_doc_downloads', true ),
			'checksum'    => get_post_meta( $post->ID, '_doc_checksum', true ) ?: '',
			'cpf'         => get_post_meta( $post->ID, '_doc_cpf', true ) ?: '',
			'folder'      => ! empty( $folders ) && ! is_wp_error( $folders ) ? array(
				'id'   => $folders[0]->term_id,
				'name' => $folders[0]->name,
				'slug' => $folders[0]->slug,
			) : null,
			'type'        => ! empty( $types ) && ! is_wp_error( $types ) ? array(
				'id'   => $types[0]->term_id,
				'name' => $types[0]->name,
				'slug' => $types[0]->slug,
			) : null,
			'created_at'  => $post->post_date,
			'updated_at'  => $post->post_modified,
		);
	}

	/**
	 * Increment version string (1.0 → 1.1, 1.9 → 2.0).
	 */
	private static function increment_version( string $version ): string {
		$parts = explode( '.', $version );
		$major = (int) ( $parts[0] ?? 1 );
		$minor = (int) ( $parts[1] ?? 0 );

		++$minor;
		if ( $minor > 9 ) {
			++$major;
			$minor = 0;
		}

		return $major . '.' . $minor;
	}

	/**
	 * Check if user can access a document.
	 */
	public static function can_access( int $doc_id, int $user_id = 0 ): bool {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$post = get_post( $doc_id );
		if ( ! $post || $post->post_type !== 'doc' ) {
			return false;
		}

		/* Author always has access */
		if ( (int) $post->post_author === $user_id ) {
			return true;
		}

		/* Admin always has access */
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$access = get_post_meta( $doc_id, '_doc_access', true ) ?: 'private';

		switch ( $access ) {
			case 'public':
				return true;
			case 'private':
				return (int) $post->post_author === $user_id;
			case 'group':
				return apply_filters( 'apollo/docs/group_access', false, $doc_id, $user_id );
			case 'industry':
				return (bool) get_user_meta( $user_id, '_apollo_cena_access', true );
			default:
				return false;
		}
	}
}
