<?php
/**
 * Widget: WoW Counter
 *
 * Animated counter showing live WoW (reaction) count for a post/user.
 * Optionally shows Fav count side by side.
 *
 * @package Apollo\Elementor\Widgets
 */

declare(strict_types=1);

namespace Apollo\Elementor\Widgets;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WoWCounter extends Base {

	public function get_name(): string {
		return 'apollo_wow_counter';
	}

	public function get_title(): string {
		return __( 'Apollo WoW Counter', 'apollo-elementor-pro' );
	}

	public function get_icon(): string {
		return 'eicon-counter';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', [
			'label' => __( 'WoW Counter', 'apollo-elementor-pro' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'counter_target', [
			'label'   => __( 'Counter target', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'post',
			'options' => [
				'post' => __( 'Current post', 'apollo-elementor-pro' ),
				'user' => __( 'User profile', 'apollo-elementor-pro' ),
			],
		] );

		$this->add_control( 'target_id', [
			'label'       => __( 'Target ID (0 = auto)', 'apollo-elementor-pro' ),
			'type'        => Controls_Manager::NUMBER,
			'default'     => 0,
			'min'         => 0,
		] );

		$this->add_control( 'show_fav', [
			'label'        => __( 'Show Fav count too', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'allow_click', [
			'label'        => __( 'Allow visitor to react', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->end_controls_section();
		$this->add_shared_style_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$this->print_rest_config( 'wow' );
		?>
		<div class="apollo-wow-counter apollo-widget"
			data-target="<?php echo esc_attr( $s['counter_target'] ); ?>"
			data-target-id="<?php echo esc_attr( (string) $s['target_id'] ); ?>"
			data-show-fav="<?php echo esc_attr( $s['show_fav'] ); ?>"
			data-allow-click="<?php echo esc_attr( $s['allow_click'] ); ?>">
			<button type="button" class="apollo-wow-btn apollo-accent" aria-label="<?php esc_attr_e( 'WoW', 'apollo-elementor-pro' ); ?>">
				<i class="ri-emotion-happy-line"></i>
				<span class="apollo-wow-count">—</span>
			</button>
			<?php if ( 'yes' === $s['show_fav'] ) : ?>
			<button type="button" class="apollo-fav-btn apollo-accent" aria-label="<?php esc_attr_e( 'Favourite', 'apollo-elementor-pro' ); ?>">
				<i class="ri-heart-line"></i>
				<span class="apollo-fav-count">—</span>
			</button>
			<?php endif; ?>
		</div>
		<?php
	}
}
