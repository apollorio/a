<?php
/**
 * Relabel WordPress "Comment" system → "Depoimento" globally.
 *
 * Filters gettext, admin columns, dashboard widgets, etc.
 *
 * @package Apollo\Comment
 */

namespace Apollo\Comment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CommentLabels {

	/**
	 * Register all relabel hooks.
	 */
	public static function init(): void {
		// 1. Translate strings
		add_filter( 'gettext', array( __CLASS__, 'relabel_gettext' ), 10, 3 );
		add_filter( 'ngettext', array( __CLASS__, 'relabel_ngettext' ), 10, 5 );

		// 2. Admin menu label
		add_action( 'admin_menu', array( __CLASS__, 'relabel_admin_menu' ), 999 );

		// 3. Admin bar
		add_action( 'admin_bar_menu', array( __CLASS__, 'relabel_admin_bar' ), 999 );

		// 4. Post type supports — rename comment meta box
		add_filter( 'add_meta_boxes_comment', array( __CLASS__, 'relabel_meta_box' ), 999 );

		// 5. Dashboard widget title
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'relabel_dashboard' ), 999 );

		// 6. Post columns
		add_filter( 'manage_posts_columns', array( __CLASS__, 'relabel_post_columns' ) );
		add_filter( 'manage_pages_columns', array( __CLASS__, 'relabel_post_columns' ) );
	}

	/**
	 * ── gettext — single strings ──
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public static function relabel_gettext( string $translation, string $text, string $domain ): string {
		static $map = null;

		if ( null === $map ) {
			$map = array(
				// Singular
				'Comment'                              => 'Depoimento',
				'comment'                              => 'depoimento',
				'Comments'                             => 'Depoimentos',
				'comments'                             => 'depoimentos',

				// Forms
				'Leave a Comment'                      => 'Deixe um Depoimento',
				'Leave a Reply'                        => 'Deixe um Depoimento',
				'Leave a Reply to %s'                  => 'Deixe um Depoimento para %s',
				'Reply'                                => 'Responder',
				'Reply to %s'                          => 'Responder a %s',

				// Actions
				'Add Comment'                          => 'Enviar Depoimento',
				'Submit Comment'                       => 'Enviar Depoimento',
				'Post Comment'                         => 'Enviar Depoimento',
				'Your comment is awaiting moderation.' => 'Seu depoimento está aguardando moderação.',
				'Your comment is awaiting moderation. This is a preview; your comment will be visible after it has been approved.' =>
					'Seu depoimento está aguardando moderação. Isto é um preview; seu depoimento ficará visível após aprovação.',

				// Admin
				'Edit Comment'                         => 'Editar Depoimento',
				'All Comments'                         => 'Todos os Depoimentos',
				'Pending Comments'                     => 'Depoimentos Pendentes',
				'Approved'                             => 'Aprovados',
				'Spam'                                 => 'Spam',
				'Trash'                                => 'Lixeira',
				'No comments yet.'                     => 'Nenhum depoimento ainda.',
				'No comments'                          => 'Nenhum depoimento',
				'No Comments'                          => 'Nenhum Depoimento',
				'1 Comment'                            => '1 Depoimento',
				'Comment (%s)'                         => 'Depoimento (%s)',
				'Comments (%s)'                        => 'Depoimentos (%s)',

				// Misc
				'Logged in as'                         => 'Logado como',
				'You must be <a href="%s">logged in</a> to post a comment.' =>
					'Você precisa estar <a href="%s">logado</a> para deixar um depoimento.',
			);
		}

		return $map[ $text ] ?? $translation;
	}

	/**
	 * ── ngettext — plural strings ──
	 *
	 * @param string $translation Translated text.
	 * @param string $single      Singular form.
	 * @param string $plural      Plural form.
	 * @param int    $number      Count.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	public static function relabel_ngettext( string $translation, string $single, string $plural, int $number, string $domain ): string {
		if ( $single === '% Comment' || $single === '%s Comment' ) {
			return ( 1 === $number ) ? '%s Depoimento' : '%s Depoimentos';
		}
		if ( $single === '1 Comment' ) {
			return ( 1 === $number ) ? '1 Depoimento' : $number . ' Depoimentos';
		}
		return $translation;
	}

	/**
	 * ── Admin menu ──
	 */
	public static function relabel_admin_menu(): void {
		global $menu;
		if ( ! is_array( $menu ) ) {
			return;
		}
		foreach ( $menu as &$item ) {
			if ( isset( $item[2] ) && $item[2] === 'edit-comments.php' ) {
				$item[0] = preg_replace( '/Comments/i', 'Depoimentos', $item[0] );
			}
		}
	}

	/**
	 * ── Admin bar ──
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public static function relabel_admin_bar( \WP_Admin_Bar $wp_admin_bar ): void {
		$node = $wp_admin_bar->get_node( 'comments' );
		if ( $node ) {
			$node->title = preg_replace( '/Comments/i', 'Depoimentos', $node->title ?? '' );
			$wp_admin_bar->add_node( (array) $node );
		}
	}

	/**
	 * ── Meta box title ──
	 */
	public static function relabel_meta_box(): void {
		remove_meta_box( 'commentsdiv', null, 'normal' );
	}

	/**
	 * ── Dashboard ──
	 */
	public static function relabel_dashboard(): void {
		global $wp_meta_boxes;
		if ( isset( $wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments'] ) ) {
			$wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']['title'] = 'Depoimentos Recentes';
		}
	}

	/**
	 * ── Post columns ──
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function relabel_post_columns( array $columns ): array {
		if ( isset( $columns['comments'] ) ) {
			$columns['comments'] = '<span class="vers comment-grey-bubble" title="Depoimentos"><span class="screen-reader-text">Depoimentos</span></span>';
		}
		return $columns;
	}
}
