<?php

/**
 * RankMetabox — internal editorial ranking + vibe-tag controls for `event`.
 *
 * OWNS: the wp-admin UI + save handler for `_event_int_rank` AND the five
 * `_event_tag_*` checkboxes (Underground/Mainstream/Comercial/LGBTQIA+/Sex
 * Party) ONLY. Every other event field is owned by Admin\Metabox — this file
 * exists separately on purpose (one file, one concern) because these
 * controls are placed inside the Publish box itself, right below the
 * Publish/Update button, not in a normal meta box panel like the rest of the
 * edit screen.
 *
 * Every field here is INTERNAL USE ONLY, 2026-08-24. `_event_int_rank` is
 * 0-10, defaults to 0 (worst/baseline). The five `_event_tag_*` keys are
 * independent booleans, default unchecked — canonical slug=>meta_key map in
 * apollo_event_internal_tags() (includes/functions.php), so a new tag is
 * added in exactly one place, not three. NONE of it is ever rendered on any
 * public/frontend surface — it feeds apollo_event_find_best_match() /
 * apollo_event_get_top_ranked(), the plug-and-play "best event for the
 * moment" engine any Apollo plugin (apollo-telegram first) can call.
 *
 * Admin-only, twice over:
 *   1. render() bails for anyone without manage_options — non-admins never
 *      even see the controls exist.
 *   2. save() bails for anyone without manage_options AND
 *      apollo_event_set_int_rank() / apollo_event_set_tag() re-check the
 *      same capability, so a programmatic caller (a future admin tool, a
 *      WP-CLI command) cannot bypass the gate just by skipping this form.
 * Every meta key here is also registered with show_in_rest => false in
 * apollo-core/src/Core/MetaRegistry.php, so all of it is structurally
 * invisible to REST and to the Gutenberg meta panel — this classic-submitbox
 * form is the only write path that exists.
 *
 * @package Apollo\Event\Admin
 */

namespace Apollo\Event\Admin;

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

final class RankMetabox {

	private const POST_TYPE = 'event';
	private const NONCE     = 'apollo_event_rank_nonce';
	private const ACTION    = 'apollo_event_save_rank';

	public function __construct() {
		// post_submitbox_misc_actions is the last official hook inside the
		// Publish box before the button row renders. We render the field
		// here (so it is guaranteed to be part of the #post form and POST
		// correctly) and a tiny inline script physically moves the row to
		// right after #publishing-action — i.e. visually below the button,
		// as asked, instead of above it where this hook naturally prints.
		add_action( 'post_submitbox_misc_actions', array( $this, 'render' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save' ), 10, 2 );
	}

	// ─── Render ───────────────────────────────────────────────────────────────

	public function render(): void {
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Admin-only to SEE it, not just to set it.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $post;
		$post_id = $post instanceof \WP_Post ? $post->ID : 0;
		$rank    = $post_id && \function_exists( '\\Apollo\\Event\\apollo_event_get_int_rank' )
			? \Apollo\Event\apollo_event_get_int_rank( $post_id )
			: 0;

		wp_nonce_field( self::ACTION, self::NONCE );
		?>
		<style>
			.apollo-event-rank-row {
				margin-top: 10px;
				padding: 10px 12px;
				border: 1px solid #d9a86c;
				border-radius: 4px;
				background: linear-gradient(135deg, #fff8ee, #fff);
			}
			.apollo-event-rank-row .apollo-event-rank-label {
				display: block;
				font-weight: 600;
				font-size: 12px;
				text-transform: uppercase;
				letter-spacing: .04em;
				color: #8a5a1e;
				margin-bottom: 4px;
			}
			.apollo-event-rank-row select {
				width: 100%;
			}
			.apollo-event-rank-row .apollo-event-rank-hint {
				display: block;
				margin-top: 4px;
				font-size: 11px;
				color: #666;
				line-height: 1.4;
			}
			.apollo-event-tags-block {
				margin-top: 10px;
				padding-top: 10px;
				border-top: 1px dashed #d9a86c;
			}
			.apollo-event-tags-block .apollo-event-tag-item {
				display: flex;
				align-items: center;
				gap: 6px;
				font-size: 12px;
				padding: 3px 0;
			}
		</style>
		<div id="apollo-event-rank-row" class="apollo-event-rank-row">
			<label class="apollo-event-rank-label" for="apollo-event-rank-select">
				<?php esc_html_e( '🏆 Ranking interno (uso do bot Telegram)', 'apollo-events' ); ?>
			</label>
			<select name="_event_int_rank" id="apollo-event-rank-select">
				<?php for ( $i = 0; $i <= 10; $i++ ) : ?>
					<option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( $rank, $i ); ?>>
						<?php
						echo esc_html( (string) $i );
						if ( 0 === $i ) {
							echo ' — ' . esc_html__( 'padrão / pior', 'apollo-events' );
						} elseif ( 10 === $i ) {
							echo ' — ' . esc_html__( 'excepcional', 'apollo-events' );
						}
						?>
					</option>
				<?php endfor; ?>
			</select>
			<span class="apollo-event-rank-hint">
				<?php esc_html_e( 'Somente administradores veem e definem este campo. NUNCA exibido no site — uso interno para o bot do Telegram decidir qual evento indicar.', 'apollo-events' ); ?>
			</span>

			<div class="apollo-event-tags-block">
				<span class="apollo-event-rank-label"><?php esc_html_e( '🎭 Vibe do evento (uso interno)', 'apollo-events' ); ?></span>
				<?php
				$tags   = $post_id && \function_exists( '\\Apollo\\Event\\apollo_event_get_tags' )
					? \Apollo\Event\apollo_event_get_tags( $post_id )
					: array();
				$labels = \function_exists( '\\Apollo\\Event\\apollo_event_internal_tag_labels' )
					? \Apollo\Event\apollo_event_internal_tag_labels()
					: array();
				foreach ( $labels as $slug => $label ) :
					$checked = ! empty( $tags[ $slug ] );
					?>
					<label class="apollo-event-tag-item">
						<input
							type="checkbox"
							name="_event_tags[]"
							value="<?php echo esc_attr( $slug ); ?>"
							<?php checked( $checked ); ?>
						>
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
				<span class="apollo-event-rank-hint">
					<?php esc_html_e( 'Marque quantas se aplicarem. Também interno — nunca exibido no site.', 'apollo-events' ); ?>
				</span>
			</div>
		</div>
		<script>
		( function () {
			var row    = document.getElementById( 'apollo-event-rank-row' );
			var anchor = document.getElementById( 'publishing-action' );
			if ( row && anchor && anchor.parentNode ) {
				// Move to right after the Publish/Update button, still inside
				// #major-publishing-actions — same submitbox, below the button.
				anchor.parentNode.insertBefore( row, anchor.nextSibling );
			}
		} )();
		</script>
		<?php
	}

	// ─── Save ─────────────────────────────────────────────────────────────────

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::ACTION ) ) {
			return;
		}
		if ( \defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( $post->post_type !== self::POST_TYPE ) {
			return;
		}
		// Admin-only at the write boundary too — same reasoning as
		// _classified_hostel_id: this is an editorial judgment call staff
		// make, not a preference the event's own author/coauthor controls.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! isset( $_POST['_event_int_rank'] ) ) {
			return;
		}

		$rank = absint( wp_unslash( $_POST['_event_int_rank'] ) );

		if ( \function_exists( '\\Apollo\\Event\\apollo_event_set_int_rank' ) ) {
			\Apollo\Event\apollo_event_set_int_rank( $post_id, $rank );
		} else {
			// Fallback if functions.php somehow was not loaded — clamp inline
			// rather than trust the raw POST value.
			update_post_meta( $post_id, '_event_int_rank', max( 0, min( 10, $rank ) ) );
		}

		// Vibe tags — ALWAYS processed once we get this far (mirrors the
		// _event_is_gone / _event_highlighted convention elsewhere in this
		// plugin): an unchecked checkbox never appears in $_POST at all, so
		// the only reliable way to let "uncheck everything" actually clear
		// the meta is to loop every known slug and set it explicitly rather
		// than gate on isset( $_POST['_event_tags'] ).
		$posted_tags = isset( $_POST['_event_tags'] ) && \is_array( $_POST['_event_tags'] )
			? array_map( 'sanitize_key', wp_unslash( $_POST['_event_tags'] ) )
			: array();

		if ( \function_exists( '\\Apollo\\Event\\apollo_event_internal_tags' ) && \function_exists( '\\Apollo\\Event\\apollo_event_set_tag' ) ) {
			foreach ( \Apollo\Event\apollo_event_internal_tags() as $slug => $meta_key ) {
				\Apollo\Event\apollo_event_set_tag( $post_id, $slug, \in_array( $slug, $posted_tags, true ) );
			}
		} else {
			// Fallback — same hardcoded map as apollo_event_internal_tags(),
			// only reached if functions.php somehow was not loaded.
			$fallback_tags = array(
				'underground' => '_event_tag_underground',
				'mainstream'  => '_event_tag_mainstream',
				'comercial'   => '_event_tag_comercial',
				'lgbtqia'     => '_event_tag_lgbtqia',
				'sexparty'    => '_event_tag_sexparty',
			);
			foreach ( $fallback_tags as $slug => $meta_key ) {
				update_post_meta( $post_id, $meta_key, \in_array( $slug, $posted_tags, true ) ? '1' : '' );
			}
		}
	}
}
