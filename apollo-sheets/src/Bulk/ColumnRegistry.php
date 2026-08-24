<?php

/**
 * Bulk Column Registry — defines spreadsheet columns per content type
 *
 * Dynamically registers columns for posts (any CPT), users, and comments.
 * Each column has: key, title, data_type (post_data|meta_data|post_terms|user_data|user_meta|comment_data),
 * editable flag, width, renderer type.
 *
 * @package Apollo\Sheets
 */

declare(strict_types=1);

namespace Apollo\Sheets\Bulk;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ColumnRegistry {


	/**
	 * @var array<string, array<string, array>> $columns  [content_type => [key => config]]
	 */
	private array $columns = array();

	/**
	 * Register a column for a given content type (post_type slug, 'users', or 'comments')
	 *
	 * @param string $content_type  e.g. 'post', 'page', 'event', 'users', 'comments'
	 * @param string $key           Column key / meta key
	 * @param array  $config        {
	 *     @type string $title       Display header
	 *     @type string $data_type   post_data|meta_data|post_terms|user_data|user_meta|comment_data
	 *     @type bool   $editable    Whether cell is editable
	 *     @type int    $width       Column width in pixels
	 *     @type string $type        Renderer: text|numeric|dropdown|checkbox|date|readonly
	 *     @type array  $source      Dropdown options (for type=dropdown)
	 *     @type mixed  $default     Default value for empty cells
	 * }
	 */
	public function register( string $content_type, string $key, array $config ): void {
		$defaults = array(
			'title'     => $key,
			'data_type' => 'post_data',
			'editable'  => true,
			'width'     => 150,
			'type'      => 'text',
			'source'    => array(),
			'default'   => '',
		);

		$this->columns[ $content_type ][ $key ] = wp_parse_args( $config, $defaults );
	}

	/**
	 * Get all registered columns for a content type
	 *
	 * @param string $content_type
	 * @return array<string, array>
	 */
	public function get_columns( string $content_type ): array {
		return $this->columns[ $content_type ] ?? array();
	}

	/**
	 * Register default columns for a post type
	 *
	 * @param string $post_type  CPT slug
	 */
	public function register_post_type_columns( string $post_type ): void {
		// ID — always first, readonly
		$this->register(
			$post_type,
			'ID',
			array(
				'title'     => 'ID',
				'data_type' => 'post_data',
				'editable'  => false,
				'width'     => 60,
				'type'      => 'numeric',
			)
		);

		// Title
		$this->register(
			$post_type,
			'post_title',
			array(
				'title'     => __( 'Título', 'apollo-sheets' ),
				'data_type' => 'post_data',
				'editable'  => true,
				'width'     => 280,
				'type'      => 'text',
			)
		);

		// Slug
		$this->register(
			$post_type,
			'post_name',
			array(
				'title'     => __( 'Slug', 'apollo-sheets' ),
				'data_type' => 'post_data',
				'editable'  => true,
				'width'     => 180,
				'type'      => 'text',
			)
		);

		// Status
		$statuses = array_keys( get_post_stati( array( 'internal' => false ) ) );
		if ( empty( $statuses ) ) {
			$statuses = array( 'publish', 'draft', 'pending', 'private', 'trash' );
		}
		$this->register(
			$post_type,
			'post_status',
			array(
				'title'     => __( 'Status', 'apollo-sheets' ),
				'data_type' => 'post_data',
				'editable'  => true,
				'width'     => 110,
				'type'      => 'dropdown',
				'source'    => $statuses,
			)
		);

		// Author
		$this->register(
			$post_type,
			'post_author',
			array(
				'title'     => __( 'Autor', 'apollo-sheets' ),
				'data_type' => 'post_data',
				'editable'  => true,
				'width'     => 140,
				'type'      => 'text',
			)
		);

		// Date
		$this->register(
			$post_type,
			'post_date',
			array(
				'title'     => __( 'Data', 'apollo-sheets' ),
				'data_type' => 'post_data',
				'editable'  => true,
				'width'     => 150,
				'type'      => 'text',
			)
		);

		// Excerpt (if supports)
		if ( post_type_supports( $post_type, 'excerpt' ) ) {
			$this->register(
				$post_type,
				'post_excerpt',
				array(
					'title'     => __( 'Resumo', 'apollo-sheets' ),
					'data_type' => 'post_data',
					'editable'  => true,
					'width'     => 250,
					'type'      => 'text',
				)
			);
		}

		// Content (truncated preview)
		$this->register(
			$post_type,
			'post_content',
			array(
				'title'     => __( 'Conteúdo', 'apollo-sheets' ),
				'data_type' => 'post_data',
				'editable'  => true,
				'width'     => 300,
				'type'      => 'text',
			)
		);

		// Taxonomies
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		foreach ( $taxonomies as $tax_slug => $tax_obj ) {
			$this->register(
				$post_type,
				$tax_slug,
				array(
					'title'     => $tax_obj->labels->name,
					'data_type' => 'post_terms',
					'editable'  => true,
					'width'     => 180,
					'type'      => 'text',
				)
			);
		}

		// Featured image ID
		if ( post_type_supports( $post_type, 'thumbnail' ) ) {
			$this->register(
				$post_type,
				'_thumbnail_id',
				array(
					'title'     => __( 'Imagem Destaque', 'apollo-sheets' ),
					'data_type' => 'meta_data',
					'editable'  => true,
					'width'     => 100,
					'type'      => 'numeric',
				)
			);
		}

		// Comment status
		if ( post_type_supports( $post_type, 'comments' ) ) {
			$this->register(
				$post_type,
				'comment_status',
				array(
					'title'     => __( 'Comentários', 'apollo-sheets' ),
					'data_type' => 'post_data',
					'editable'  => true,
					'width'     => 110,
					'type'      => 'dropdown',
					'source'    => array( 'open', 'closed' ),
				)
			);
		}

		/**
		 * Allow other plugins to add columns for this post type
		 *
		 * @param ColumnRegistry $registry  This instance
		 * @param string         $post_type CPT slug
		 */
		do_action( 'apollo/sheets/bulk/register_columns', $this, $post_type );
	}

	/**
	 * Register default columns for users
	 */
	public function register_user_columns(): void {
		$content_type = 'users';

		$this->register(
			$content_type,
			'ID',
			array(
				'title'     => 'ID',
				'data_type' => 'user_data',
				'editable'  => false,
				'width'     => 60,
				'type'      => 'numeric',
			)
		);

		$this->register(
			$content_type,
			'user_login',
			array(
				'title'     => __( 'Login', 'apollo-sheets' ),
				'data_type' => 'user_data',
				'editable'  => false,
				'width'     => 150,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'user_email',
			array(
				'title'     => __( 'E-mail', 'apollo-sheets' ),
				'data_type' => 'user_data',
				'editable'  => true,
				'width'     => 220,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'display_name',
			array(
				'title'     => __( 'Nome Exibição', 'apollo-sheets' ),
				'data_type' => 'user_data',
				'editable'  => true,
				'width'     => 180,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'user_nicename',
			array(
				'title'     => __( 'Nicename', 'apollo-sheets' ),
				'data_type' => 'user_data',
				'editable'  => true,
				'width'     => 150,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'user_url',
			array(
				'title'     => __( 'Website', 'apollo-sheets' ),
				'data_type' => 'user_data',
				'editable'  => true,
				'width'     => 200,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'user_registered',
			array(
				'title'     => __( 'Registrado', 'apollo-sheets' ),
				'data_type' => 'user_data',
				'editable'  => false,
				'width'     => 150,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'role',
			array(
				'title'     => __( 'Perfil', 'apollo-sheets' ),
				'data_type' => 'user_data',
				'editable'  => true,
				'width'     => 130,
				'type'      => 'dropdown',
				'source'    => array_keys( wp_roles()->roles ),
			)
		);

		$this->register(
			$content_type,
			'first_name',
			array(
				'title'     => __( 'Nome', 'apollo-sheets' ),
				'data_type' => 'user_meta',
				'editable'  => true,
				'width'     => 150,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'last_name',
			array(
				'title'     => __( 'Sobrenome', 'apollo-sheets' ),
				'data_type' => 'user_meta',
				'editable'  => true,
				'width'     => 150,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'description',
			array(
				'title'     => __( 'Bio', 'apollo-sheets' ),
				'data_type' => 'user_meta',
				'editable'  => true,
				'width'     => 250,
				'type'      => 'text',
			)
		);

		/**
		 * Allow other plugins to add user columns
		 *
		 * @param ColumnRegistry $registry
		 */
		do_action( 'apollo/sheets/bulk/register_user_columns', $this );
	}

	/**
	 * Register default columns for comments (depoimentos)
	 */
	public function register_comment_columns(): void {
		$content_type = 'comments';

		$this->register(
			$content_type,
			'comment_ID',
			array(
				'title'     => 'ID',
				'data_type' => 'comment_data',
				'editable'  => false,
				'width'     => 60,
				'type'      => 'numeric',
			)
		);

		$this->register(
			$content_type,
			'comment_post_ID',
			array(
				'title'     => __( 'Post ID', 'apollo-sheets' ),
				'data_type' => 'comment_data',
				'editable'  => false,
				'width'     => 80,
				'type'      => 'numeric',
			)
		);

		$this->register(
			$content_type,
			'comment_author',
			array(
				'title'     => __( 'Autor', 'apollo-sheets' ),
				'data_type' => 'comment_data',
				'editable'  => true,
				'width'     => 150,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'comment_author_email',
			array(
				'title'     => __( 'E-mail Autor', 'apollo-sheets' ),
				'data_type' => 'comment_data',
				'editable'  => true,
				'width'     => 200,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'comment_content',
			array(
				'title'     => __( 'Conteúdo', 'apollo-sheets' ),
				'data_type' => 'comment_data',
				'editable'  => true,
				'width'     => 350,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'comment_date',
			array(
				'title'     => __( 'Data', 'apollo-sheets' ),
				'data_type' => 'comment_data',
				'editable'  => false,
				'width'     => 150,
				'type'      => 'text',
			)
		);

		$this->register(
			$content_type,
			'comment_approved',
			array(
				'title'     => __( 'Status', 'apollo-sheets' ),
				'data_type' => 'comment_data',
				'editable'  => true,
				'width'     => 110,
				'type'      => 'dropdown',
				'source'    => array( '1', '0', 'spam', 'trash' ),
			)
		);

		$this->register(
			$content_type,
			'comment_type',
			array(
				'title'     => __( 'Tipo', 'apollo-sheets' ),
				'data_type' => 'comment_data',
				'editable'  => false,
				'width'     => 110,
				'type'      => 'text',
			)
		);

		/**
		 * Allow other plugins to add comment columns
		 *
		 * @param ColumnRegistry $registry
		 */
		do_action( 'apollo/sheets/bulk/register_comment_columns', $this );
	}

	/**
	 * Build Handsontable column configuration array
	 *
	 * @param string $content_type
	 * @return array { colHeaders: string[], columns: array[], colWidths: int[] }
	 */
	public function build_handsontable_config( string $content_type ): array {
		$cols = $this->get_columns( $content_type );
		if ( empty( $cols ) ) {
			return array(
				'colHeaders' => array(),
				'columns'    => array(),
				'colWidths'  => array(),
			);
		}

		$headers = array();
		$columns = array();
		$widths  = array();

		foreach ( $cols as $key => $config ) {
			$headers[] = $config['title'];
			$widths[]  = $config['width'];

			$col_def = array(
				'data'     => $key,
				'readOnly' => ! $config['editable'],
			);

			switch ( $config['type'] ) {
				case 'numeric':
					$col_def['type'] = 'numeric';
					break;

				case 'dropdown':
					$col_def['type']         = 'dropdown';
					$col_def['source']       = $config['source'];
					$col_def['allowInvalid'] = false;
					break;

				case 'checkbox':
					$col_def['type']              = 'checkbox';
					$col_def['checkedTemplate']   = $config['checked'] ?? '1';
					$col_def['uncheckedTemplate'] = $config['unchecked'] ?? '0';
					break;

				case 'date':
					$col_def['type']          = 'date';
					$col_def['dateFormat']    = 'YYYY-MM-DD';
					$col_def['correctFormat'] = true;
					break;

				case 'readonly':
					$col_def['readOnly'] = true;
					break;

				default:
					$col_def['type'] = 'text';
					break;
			}

			$columns[] = $col_def;
		}

		return array(
			'colHeaders' => $headers,
			'columns'    => $columns,
			'colWidths'  => $widths,
		);
	}
}
