<?php
/**
 * Widget: Profile Card
 *
 * Renders a user profile card (avatar, bio, membership badge, socials).
 *
 * @package Apollo\Elementor\Widgets
 */

declare(strict_types=1);

namespace Apollo\Elementor\Widgets;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ProfileCard extends Base {

	public function get_name(): string {
		return 'apollo_profile_card';
	}

	public function get_title(): string {
		return __( 'Apollo Profile Card', 'apollo-elementor-pro' );
	}

	public function get_icon(): string {
		return 'eicon-user-circle-o';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_content', [
			'label' => __( 'Profile Card', 'apollo-elementor-pro' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'user_id', [
			'label'       => __( 'User ID', 'apollo-elementor-pro' ),
			'type'        => Controls_Manager::NUMBER,
			'description' => __( 'Leave 0 to auto-detect from URL / current user.', 'apollo-elementor-pro' ),
			'default'     => 0,
			'min'         => 0,
		] );

		$this->add_control( 'show_membership', [
			'label'        => __( 'Show membership badge', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'show_social_links', [
			'label'        => __( 'Show social links', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'show_wow_fav', [
			'label'        => __( 'Show WoW / Fav counts', 'apollo-elementor-pro' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->end_controls_section();
		$this->add_shared_style_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();
		$this->print_rest_config( 'users' );
		?>
		<div class="apollo-profile-card apollo-widget"
			data-user-id="<?php echo esc_attr( (string) $s['user_id'] ); ?>"
			data-membership="<?php echo esc_attr( $s['show_membership'] ); ?>"
			data-social="<?php echo esc_attr( $s['show_social_links'] ); ?>"
			data-wow-fav="<?php echo esc_attr( $s['show_wow_fav'] ); ?>">
			<div class="apollo-loading"><span></span></div>
		</div>
		<?php
	}
}
