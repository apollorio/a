<?php
/**
 * Widget: Membership Gate
 *
 * Shows its inner Elementor content only to users with the required
 * Apollo membership tier. Non-members see a configurable teaser.
 *
 * @package Apollo\Elementor\Widgets
 */

declare(strict_types=1);

namespace Apollo\Elementor\Widgets;

use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MembershipGate extends Base {

	public function get_name(): string {
		return 'apollo_membership_gate';
	}

	public function get_title(): string {
		return __( 'Apollo Membership Gate', 'apollo-elementor-pro' );
	}

	public function get_icon(): string {
		return 'eicon-lock-user';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'section_gate', [
			'label' => __( 'Gate Settings', 'apollo-elementor-pro' ),
			'tab'   => Controls_Manager::TAB_CONTENT,
		] );

		$this->add_control( 'required_tier', [
			'label'   => __( 'Required membership tier', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'amigz',
			'options' => [
				'amigz'     => 'amigz',
				'amigxs'    => 'amigxs',
				'greatdjs'  => 'greatdjs',
			],
		] );

		$this->add_control( 'teaser_text', [
			'label'   => __( 'Teaser / locked message', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::TEXTAREA,
			'default' => __( 'This content is available to members only. Join Apollo!', 'apollo-elementor-pro' ),
			'rows'    => 3,
		] );

		$this->add_control( 'cta_label', [
			'label'   => __( 'CTA button label', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::TEXT,
			'default' => __( 'Become a member', 'apollo-elementor-pro' ),
		] );

		$this->add_control( 'cta_url', [
			'label'   => __( 'CTA URL', 'apollo-elementor-pro' ),
			'type'    => Controls_Manager::URL,
			'default' => [ 'url' => '/membership' ],
		] );

		$this->end_controls_section();
		$this->add_shared_style_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		// Server-side check — never rely on JS alone for security.
		$user_id = get_current_user_id();
		$tier    = $user_id ? get_user_meta( $user_id, '_apollo_membership', true ) : '';
		$allowed = [ 'amigz', 'amigxs', 'greatdjs', 'administrator' ];

		// Check if the user meets the required tier.
		$tiers_rank  = [ '' => 0, 'amigxs' => 1, 'amigz' => 2, 'greatdjs' => 3, 'administrator' => 99 ];
		$required_rank = $tiers_rank[ $s['required_tier'] ] ?? 1;
		$user_rank     = $tiers_rank[ $tier ] ?? 0;

		if ( current_user_can( 'manage_options' ) || $user_rank >= $required_rank ) {
			?>
			<div class="apollo-membership-gate apollo-gate-open">
				<?php $this->print_child_content(); ?>
			</div>
			<?php
		} else {
			$cta_url = isset( $s['cta_url']['url'] ) ? esc_url( $s['cta_url']['url'] ) : '#';
			?>
			<div class="apollo-membership-gate apollo-gate-locked">
				<div class="apollo-gate-teaser">
					<i class="ri-lock-fill apollo-accent"></i>
					<p><?php echo wp_kses_post( $s['teaser_text'] ); ?></p>
					<?php if ( ! empty( $s['cta_label'] ) ) : ?>
					<a href="<?php echo $cta_url; ?>" class="apollo-btn apollo-accent-bg">
						<?php echo esc_html( $s['cta_label'] ); ?>
					</a>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
	}

	/** Print child Elementor template content (inner section). */
	private function print_child_content(): void {
		// For Section/Container widgets with inner content, this would be handled
		// by Elementor's template logic. As a standalone widget, we echo a slot comment.
		echo '<!-- apollo-gate-content -->';
	}
}
