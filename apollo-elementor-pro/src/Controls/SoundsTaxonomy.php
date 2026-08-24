<?php
/**
 * Custom Control: Sounds Taxonomy Select
 *
 * Renders a multi-select dropdown populated with all terms from the
 * Apollo "sounds" taxonomy (registered by apollo-core).
 *
 * @package Apollo\Elementor\Controls
 */

declare(strict_types=1);

namespace Apollo\Elementor\Controls;

use Elementor\Base_Data_Control;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SoundsTaxonomy extends Base_Data_Control {

	public function get_type(): string {
		return 'apollo_sounds_taxonomy';
	}

	public function get_default_value(): mixed {
		return [];
	}

	/**
	 * Build the options array from live taxonomy terms.
	 */
	private function build_options(): array {
		$terms = get_terms( [
			'taxonomy'   => 'sounds',
			'hide_empty' => false,
			'number'     => 200,
		] );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}

		$options = [];
		foreach ( $terms as $term ) {
			$options[ $term->slug ] = $term->name;
		}
		return $options;
	}

	public function content_template(): void {
		$control_uid = $this->get_control_uid();
		?>
		<div class="elementor-control-field">
			<label for="<?php echo esc_attr( $control_uid ); ?>" class="elementor-control-title">{{{ data.label }}}</label>
			<div class="elementor-control-input-wrapper">
				<select id="<?php echo esc_attr( $control_uid ); ?>"
					multiple
					data-setting="{{ data.name }}"
					class="apollo-sounds-select">
					<?php foreach ( $this->build_options() as $slug => $name ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<# if ( data.description ) { #>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
			<# } #>
		</div>
		<?php
	}

	public function enqueue(): void {
		// Styles for the multi-select are part of the shared widgets.css.
	}
}
