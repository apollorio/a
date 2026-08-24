<?php
/**
 * Shortcodes — [apollo_depoimentos] and [apollo_depoimento_form]
 *
 * @package Apollo\Comment
 */

namespace Apollo\Comment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Shortcodes {

	/**
	 * Register shortcodes.
	 */
	public static function init(): void {
		add_shortcode( 'apollo_depoimentos', array( __CLASS__, 'render_list' ) );
		add_shortcode( 'apollo_depoimento_form', array( __CLASS__, 'render_form' ) );
	}

	/**
	 * [apollo_depoimentos post_id="123" limit="10"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_list( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
				'limit'   => 10,
			),
			$atts,
			'apollo_depoimentos'
		);

		$post_id = absint( $atts['post_id'] );
		$limit   = absint( $atts['limit'] );

		if ( ! $post_id ) {
			return '';
		}

		$result      = Depoimento::query( $post_id, $limit );
		$depoimentos = $result['depoimentos'];
		$total       = $result['total'];

		ob_start();
		include Plugin::template_path( 'depoimento-list.php' );
		return ob_get_clean();
	}

	/**
	 * [apollo_depoimento_form post_id="123"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_form( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
			),
			$atts,
			'apollo_depoimento_form'
		);

		$post_id = absint( $atts['post_id'] );

		if ( ! $post_id ) {
			return '';
		}

		ob_start();
		include Plugin::template_path( 'depoimento-form.php' );
		return ob_get_clean();
	}
}
