<?php
/**
 * Widget: User Radar
 *
 * Renders a radar / spider chart of Apollo user vibe scores.
 *
 * @package Apollo\Elementor\Widgets
 */

declare(strict_types=1);

namespace Apollo\Elementor\Widgets;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UserRadar extends Base {

	public function get_name(): string {
		return 'apollo_user_radar';
	}

	public function get_title(): string {
		return __( 'Apollo User Radar', 'apollo-elementor-pro' );
	}

	public function get_icon(): string {
		return 'eicon-radar';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', [
			'label' => __( 'User Radar', 'apollo-elementor-pro' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'user_id', [
			'label'   => __( 'User ID (0 = current)', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 0,
			'min'     => 0,
		] );

		$this->add_control( 'dimensions', [
			'label'       => __( 'Dimensions', 'apollo-elementor-pro' ),
			'type'        => Controls_Manager::TEXTAREA,
			'description' => __( 'Comma-separated vibe keys, e.g. energy,groove,vocal', 'apollo-elementor-pro' ),
			'default'     => 'energy,groove,vocal,tempo,creativity',
			'rows'        => 2,
		] );

		$this->add_control( 'chart_height', [
			'label'   => __( 'Chart height (px)', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::NUMBER,
			'default' => 260,
			'min'     => 100,
			'max'     => 600,
		] );

		$this->end_controls_section();
		$this->add_shared_style_section();
	}

	protected function render(): void {
		$s    = $this->get_settings_for_display();
		$dims = array_map( 'sanitize_key', explode( ',', $s['dimensions'] ) );
		$this->print_rest_config( 'users' );
		?>
		<div class="apollo-user-radar apollo-widget"
			data-user-id="<?php echo esc_attr( (string) $s['user_id'] ); ?>"
			data-dimensions="<?php echo esc_attr( implode( ',', $dims ) ); ?>"
			data-height="<?php echo esc_attr( (string) $s['chart_height'] ); ?>">
			<canvas class="apollo-radar-canvas" style="max-height:<?php echo esc_attr( (string) $s['chart_height'] ); ?>px"></canvas>
		</div>
		<?php
	}
}
