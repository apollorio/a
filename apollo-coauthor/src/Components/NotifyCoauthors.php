<?php
/**
 * Apollo CoAuthor — Notify Co-Authors Component.
 *
 * Sends comment moderation and notification emails to ALL co-authors
 * of a post, not just the primary post_author.
 *
 * Adapted from Co-Authors Plus cap_filter_comment_moderation_email_recipients.
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
 * Comment notification to all co-authors.
 *
 * @since 1.1.0
 */
class NotifyCoauthors {

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		// Filter comment moderation email recipients.
		add_filter( 'comment_moderation_recipients', array( $this, 'filter_moderation_recipients' ), 10, 2 );

		// Filter comment notification recipients.
		add_filter( 'comment_notification_recipients', array( $this, 'filter_notification_recipients' ), 10, 2 );

		// Notify co-authors when a new comment is approved.
		add_action( 'comment_post', array( $this, 'notify_on_new_comment' ), 20, 3 );
	}

	/**
	 * Add co-author emails to comment moderation email recipients.
	 *
	 * Adapted from Co-Authors Plus cap_filter_comment_moderation_email_recipients.
	 *
	 * @param string[] $recipients Email addresses.
	 * @param int      $comment_id Comment ID.
	 * @return string[]
	 */
	public function filter_moderation_recipients( array $recipients, int $comment_id ): array {
		return $this->add_coauthor_emails( $recipients, $comment_id );
	}

	/**
	 * Add co-author emails to comment notification recipients.
	 *
	 * @param string[] $recipients Email addresses.
	 * @param int      $comment_id Comment ID.
	 * @return string[]
	 */
	public function filter_notification_recipients( array $recipients, int $comment_id ): array {
		return $this->add_coauthor_emails( $recipients, $comment_id );
	}

	/**
	 * Collect co-author emails and merge with existing recipients.
	 *
	 * @param string[] $recipients Existing email recipients.
	 * @param int      $comment_id Comment ID.
	 * @return string[]
	 */
	private function add_coauthor_emails( array $recipients, int $comment_id ): array {
		$comment = get_comment( $comment_id );
		if ( ! $comment || empty( $comment->comment_post_ID ) ) {
			return $recipients;
		}

		$post_id = (int) $comment->comment_post_ID;

		// Use the global function if available.
		if ( ! function_exists( 'apollo_get_coauthors' ) ) {
			return $recipients;
		}

		$coauthors = apollo_get_coauthors( $post_id );
		if ( empty( $coauthors ) ) {
			return $recipients;
		}

		$extra = array();
		foreach ( $coauthors as $author ) {
			$email = '';

			// apollo_get_coauthors() returns plain arrays (user_id, display_name, ...) —
			// no email key — so resolve the address via get_userdata(). Object shapes
			// (WP_User or stdClass) are also accepted defensively for other callers.
			if ( is_array( $author ) && ! empty( $author['user_id'] ) ) {
				$user = get_userdata( (int) $author['user_id'] );
				if ( $user instanceof \WP_User ) {
					$email = $user->user_email;
				}
			} elseif ( $author instanceof \WP_User ) {
				$email = $author->user_email;
			} elseif ( is_object( $author ) && ! empty( $author->user_email ) ) {
				$email = $author->user_email;
			}

			if ( $email && is_email( $email ) ) {
				$extra[] = $email;
			}
		}

		return array_unique( array_merge( $recipients, $extra ) );
	}

	/**
	 * Fire an action when a new approved comment is posted on a co-authored post.
	 *
	 * Other plugins (apollo-notif, apollo-email) can hook into this.
	 *
	 * @param int        $comment_id       Comment ID.
	 * @param int|string $comment_approved 1 if approved, 0 if pending, 'spam' if spam.
	 * @param array      $commentdata      Comment data.
	 */
	public function notify_on_new_comment( int $comment_id, $comment_approved, array $commentdata ): void {
		if ( 1 !== (int) $comment_approved ) {
			return;
		}

		$post_id = (int) ( $commentdata['comment_post_ID'] ?? 0 );
		if ( ! $post_id ) {
			return;
		}

		// Only fire if this post has co-authors.
		$coauthor_ids = get_post_meta( $post_id, APOLLO_COAUTHOR_META_KEY, true );
		if ( empty( $coauthor_ids ) || ! is_array( $coauthor_ids ) ) {
			return;
		}

		/**
		 * Fires when a comment is approved on a co-authored post.
		 *
		 * @since 1.1.0
		 *
		 * @param int   $comment_id   Comment ID.
		 * @param int   $post_id      Post ID.
		 * @param int[] $coauthor_ids Co-author user IDs.
		 * @param array $commentdata  Comment data.
		 */
		do_action( 'apollo/coauthor/comment_approved', $comment_id, $post_id, $coauthor_ids, $commentdata );
	}
}
