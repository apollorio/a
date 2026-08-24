<?php

/**
 * Apollo SEO — Taxonomy Term SEO Fields
 *
 * Adds SEO fields (title, description, robots) to taxonomy term edit screens.
 *
 * @package Apollo\SEO
 */

declare(strict_types=1);

namespace Apollo\SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class TermMetabox {


	/**
	 * Render term fields on the edit form.
	 */
	public static function render_fields( \WP_Term $term ): void {
		$meta = get_term_meta( $term->term_id, APOLLO_SEO_TERM_META, true );
		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		$meta = wp_parse_args(
			$meta,
			array(
				'title'       => '',
				'description' => '',
				'noindex'     => '',
			)
		);

		wp_nonce_field( 'apollo_seo_term', 'apollo_seo_term_nonce' );
		?>
		<tr class="form-field">
			<th colspan="2" style="padding:20px 0 5px">
				<h3 style="margin:0;font-size:14px;color:#ff9820">◆ Apollo SEO</h3>
			</th>
		</tr>
		<tr class="form-field">
			<th><label for="aseo-term-title">Título SEO</label></th>
			<td>
				<input type="text" id="aseo-term-title"
					name="<?php echo APOLLO_SEO_TERM_META; ?>[title]"
					value="<?php echo esc_attr( $meta['title'] ); ?>" class="large-text"
					placeholder="Título customizado para este termo">
				<p class="description">Substitui o título gerado automaticamente.</p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label for="aseo-term-desc">Descrição SEO</label></th>
			<td>
				<textarea id="aseo-term-desc"
					name="<?php echo APOLLO_SEO_TERM_META; ?>[description]"
					rows="3" class="large-text"
					placeholder="Descrição customizada para buscadores"><?php echo esc_textarea( $meta['description'] ); ?></textarea>
				<p class="description">Até 160 caracteres recomendados.</p>
			</td>
		</tr>
		<tr class="form-field">
			<th><label>Visibilidade</label></th>
			<td>
				<label>
					<input type="checkbox" name="<?php echo APOLLO_SEO_TERM_META; ?>[noindex]" value="1"
						<?php checked( $meta['noindex'] ); ?>>
					<strong>noindex</strong> — Não indexar esta página de termo
				</label>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save term SEO meta.
	 */
	public static function save_fields( int $term_id ): void {
		if (
			! isset( $_POST['apollo_seo_term_nonce'] ) ||
			! wp_verify_nonce( $_POST['apollo_seo_term_nonce'], 'apollo_seo_term' )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$raw = $_POST[ APOLLO_SEO_TERM_META ] ?? array();
		if ( ! is_array( $raw ) ) {
			return;
		}

		$clean = array(
			'title'       => sanitize_text_field( $raw['title'] ?? '' ),
			'description' => sanitize_textarea_field( $raw['description'] ?? '' ),
			'noindex'     => ! empty( $raw['noindex'] ) ? '1' : '',
		);

		$clean = array_filter(
			$clean,
			function ( $v ) {
				return $v !== '';
			}
		);

		if ( $clean ) {
			update_term_meta( $term_id, APOLLO_SEO_TERM_META, $clean );
		} else {
			delete_term_meta( $term_id, APOLLO_SEO_TERM_META );
		}
	}
}