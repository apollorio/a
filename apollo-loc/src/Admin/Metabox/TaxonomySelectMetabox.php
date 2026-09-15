<?php
/**
 * TaxonomySelectMetabox — renders `local_type` and `local_area` as SELECTs.
 *
 * WHY THIS EXISTS (2026-08-25)
 * ---------------------------
 * Both taxonomies are registered `hierarchical => true`, so WordPress hands
 * them its default `post_categories_meta_box()` — an unbounded checkbox list.
 * That control implies "pick as many as you like", which is wrong for both:
 * a venue has ONE type and sits in ONE zone. The checkbox list also lets an
 * editor silently assign a loc to three zones at once, and the zone is what
 * the accommodation map filters on, so the pin would then answer to filters
 * it should not.
 *
 * Swapping to a single-choice <select> makes the data model visible in the UI
 * instead of relying on editors to self-police.
 *
 * TAXONOMY REGISTRATION IS NOT TOUCHED. `hierarchical => true` stays as it is
 * — it controls term STORAGE (parent/child terms are legitimate here, e.g.
 * "Zona Sul > Copacabana"), not how the edit screen must render. Only the
 * `meta_box_cb` is overridden, via the register_taxonomy_args filter, so the
 * taxonomy keeps its REST shape, its archive behaviour and its term hierarchy.
 *
 * @package Apollo\Local\Admin\Metabox
 */

declare(strict_types=1);

namespace Apollo\Local\Admin\Metabox;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TaxonomySelectMetabox {

	/** Taxonomies this class converts to single-choice selects. */
	private const TAXONOMIES = array(
		APOLLO_LOCAL_TAX_TYPE,
		APOLLO_LOCAL_TAX_AREA,
	);

	public function __construct() {
		add_filter( 'register_taxonomy_args', array( $this, 'force_select_metabox' ), 10, 2 );
	}

	/**
	 * Point the two loc taxonomies at our own metabox callback.
	 *
	 * Runs on register_taxonomy_args rather than replacing the box later with
	 * remove_meta_box()/add_meta_box(), because that pair races with whoever
	 * registered the taxonomy last and leaves the default box rendered on any
	 * screen that beat us to `add_meta_boxes`.
	 *
	 * @param array<string,mixed> $args     Taxonomy args.
	 * @param string              $taxonomy Taxonomy slug.
	 * @return array<string,mixed>
	 */
	public function force_select_metabox( array $args, string $taxonomy ): array {
		if ( ! in_array( $taxonomy, self::TAXONOMIES, true ) ) {
			return $args;
		}
		$args['meta_box_cb'] = array( $this, 'render' );

		return $args;
	}

	/**
	 * Render one taxonomy as a single <select>.
	 *
	 * @param \WP_Post             $post Current post.
	 * @param array<string,mixed>  $box  Metabox args; $box['args']['taxonomy'] holds the slug.
	 */
	public function render( \WP_Post $post, array $box ): void {
		$taxonomy = isset( $box['args']['taxonomy'] ) ? (string) $box['args']['taxonomy'] : '';
		if ( '' === $taxonomy ) {
			return;
		}

		$tax_obj = get_taxonomy( $taxonomy );
		if ( ! $tax_obj ) {
			return;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);
		if ( is_wp_error( $terms ) ) {
			$terms = array();
		}

		/* Current selection. Only the FIRST term is honoured — if legacy data
		   has several (the old checkbox box allowed it), saving through this
		   control collapses it to one, which is the intended migration. */
		$assigned = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
		$current  = ( ! is_wp_error( $assigned ) && ! empty( $assigned ) ) ? (int) $assigned[0] : 0;

		$field = 'apollo_loc_tax_' . $taxonomy;

		wp_nonce_field( 'apollo_loc_tax_save', 'apollo_loc_tax_nonce' );
		?>
		<select name="<?php echo esc_attr( $field ); ?>" class="widefat" style="margin-top:6px;">
			<option value="0"><?php esc_html_e( '— Selecione —', 'apollo-local' ); ?></option>
			<?php foreach ( $terms as $term ) : ?>
				<option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( $current, (int) $term->term_id ); ?>>
					<?php
					/* Indent children so a parent/child hierarchy is still legible
					   in a flat select — the terms themselves stay hierarchical. */
					echo esc_html( ( $term->parent ? '— ' : '' ) . $term->name );
					?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( empty( $terms ) ) : ?>
			<p class="description" style="margin-top:6px;">
				<?php esc_html_e( 'Nenhum termo cadastrado ainda.', 'apollo-local' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Persist the select.
	 *
	 * Called by MetaboxSaver (which already owns the nonce + capability gate
	 * for this screen) rather than hooking save_post again — two savers on one
	 * post type is how ordering bugs start.
	 *
	 * @param int $post_id Post being saved.
	 */
	public static function save( int $post_id ): void {
		if ( ! isset( $_POST['apollo_loc_tax_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apollo_loc_tax_nonce'] ) ), 'apollo_loc_tax_save' ) ) {
			return;
		}

		foreach ( self::TAXONOMIES as $taxonomy ) {
			$field = 'apollo_loc_tax_' . $taxonomy;
			if ( ! isset( $_POST[ $field ] ) ) {
				continue;
			}

			$term_id = absint( $_POST[ $field ] );

			if ( 0 === $term_id ) {
				// Explicit "— Selecione —" clears the assignment.
				wp_set_object_terms( $post_id, array(), $taxonomy, false );
				continue;
			}

			// Only accept a term that really belongs to this taxonomy.
			$term = get_term( $term_id, $taxonomy );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}

			// $append = false — this is the single-choice contract.
			wp_set_object_terms( $post_id, array( $term_id ), $taxonomy, false );
		}
	}
}
