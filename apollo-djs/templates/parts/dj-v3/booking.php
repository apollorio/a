<?php
/**
 * DJ v3 Partial: Booking / Contact Form
 *
 * Contact form with nonce protection, AJAX-ready submission.
 * Variables: $dj_id, $dj_name.
 *
 * @package Apollo\DJs
 */

defined( 'ABSPATH' ) || exit;
?>

<section class="dj-section" id="booking">
	<div class="dj-contact-inner">
		<div class="dj-contact-glow" aria-hidden="true"></div>
		<h2><?php esc_html_e( 'Booking', 'apollo-djs' ); ?> <span style="color:var(--dj-primary)">&amp;</span> <?php esc_html_e( 'Contato', 'apollo-djs' ); ?></h2>
		<p class="dj-contact-sub"><?php esc_html_e( 'Booking · Press · Collabs', 'apollo-djs' ); ?></p>

		<form class="dj-booking-form" id="dj-booking-form" method="post" novalidate>
			<?php wp_nonce_field( 'apollo_dj_booking_' . $dj_id, '_dj_booking_nonce' ); ?>
			<input type="hidden" name="dj_id" value="<?php echo esc_attr( $dj_id ); ?>">
			<input type="hidden" name="action" value="apollo_dj_booking">

			<div class="dj-form-row">
				<div class="input-group">
					<input
						class="apollo-input"
						type="text"
						name="booking_name"
						placeholder=" "
						required
						autocomplete="name"
						maxlength="200"
					>
					<label class="apollo-label"><?php esc_html_e( 'Nome / Agency', 'apollo-djs' ); ?></label>
				</div>
				<div class="input-group">
					<input
						class="apollo-input"
						type="email"
						name="booking_email"
						placeholder=" "
						required
						autocomplete="email"
						maxlength="320"
					>
					<label class="apollo-label"><?php esc_html_e( 'Email', 'apollo-djs' ); ?></label>
				</div>
			</div>

			<div class="input-group">
				<textarea
					class="apollo-input"
					name="booking_message"
					rows="3"
					placeholder=" "
					maxlength="2000"
				></textarea>
				<label class="apollo-label"><?php esc_html_e( 'Mensagem', 'apollo-djs' ); ?></label>
			</div>

			<!-- Honeypot anti-spam -->
			<div style="position:absolute; left:-9999px;" aria-hidden="true">
				<input type="text" name="booking_website" tabindex="-1" autocomplete="off" class="apollo-input">
			</div>

			<button type="submit" class="btn btn-primary" style="width:100%;">
				<?php esc_html_e( 'Enviar', 'apollo-djs' ); ?> <i class="ri-arrow-right-line"></i>
			</button>

			<div class="dj-booking-status" id="dj-booking-status" role="alert" aria-live="polite" style="display:none;"></div>
		</form>
	</div>
</section>