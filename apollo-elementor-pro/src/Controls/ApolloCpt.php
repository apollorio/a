<?php
/**
 * Custom Control: Apollo CPT Select
 *
 * A select control that lists all Apollo-registered custom post types
 * read from Integration::cpts() (sourced from apollo-core registry).
 *
 * @package Apollo\Elementor\Controls
 */

declare(strict_types=1);

namespace Apollo\Elementor\Controls;

use Elementor\Base_Data_Control;
use Apollo\Elementor\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ApolloCpt extends Base_Data_Control {

	public function get_type(): string {
		return 'apollo_cpt_select';
	}

	public function get_default_value(): mixed {
		return '';
	}

	private function build_options(): array {
		$cpts    = Integration::cpts();
		$options = [ '' => __( '— Select CPT —', 'apollo-elementor-pro' ) ];

		foreach ( $cpts as $slug => $config ) {
			$label             = $config['labels']['name'] ?? $slug;
			$options[ $slug ]  = $label;
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
					data-setting="{{ data.name }}"
					class="apollo-cpt-select">
					<?php foreach ( $this->build_options() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<# if ( data.description ) { #>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
			<# } #>
		</div>
		<?php
	}
}
