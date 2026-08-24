<?php

/**
 * Part: safety/target — who, and about what.
 *
 * Naming the person and the listing on the warning screen is not decoration:
 * it is how a member catches the case where they clicked the wrong advert, or
 * where the seller in the chat is not the seller on the page.
 *
 * @var int $post_id
 * @var int $seller_id
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}

$ap_user   = get_userdata($seller_id);
$ap_handle = $ap_user ? $ap_user->user_login : '';
$ap_name   = $ap_user ? $ap_user->display_name : __('Anunciante', 'apollo-adverts');
$ap_price  = (float) get_post_meta($post_id, '_classified_price', true);
?>
<div class="ap-target">
	<span class="ap-av ap-target__av" data-av="seller"
		data-initials="<?php echo esc_attr(mb_substr($ap_name, 0, 1)); ?>">
		<?php echo esc_html(mb_substr($ap_name, 0, 1)); ?>
	</span>

	<span class="ap-target__id">
		<span class="ap-target__name"><?php echo esc_html($ap_name); ?></span>
		<?php if ($ap_handle) : ?>
			<a class="ap-target__handle" href="<?php echo esc_url(home_url('/id/' . $ap_handle)); ?>">
				@<?php echo esc_html($ap_handle); ?>
			</a>
		<?php endif; ?>
	</span>

	<span class="ap-target__ad">
		<?php if ($ap_price > 0) : ?>
			<b><?php echo esc_html('R$ ' . number_format_i18n($ap_price, 0)); ?></b>
		<?php endif; ?>
		<span><?php echo esc_html(get_the_title($post_id)); ?></span>
	</span>
</div>
