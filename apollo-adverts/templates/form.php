<?php

/**
 * Template: Classified Form (Create / Edit)
 *
 * Rendered by [apollo_classified_form] shortcode.
 * Override in theme: theme/apollo-adverts/form.php
 *
 * Available variables:
 *   $form    — Apollo\Adverts\Form instance (loaded as 'publish')
 *   $edit_id — int|null   (post ID when editing, null when creating)
 *   $errors  — array      (validation error messages)
 *   $message — string     (success / info notice)
 *   $post    — WP_Post|null (when editing)
 *
 * Adapted from WPAdverts templates/add.php
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="apollo-adverts-form-wrap">

	<?php if ( ! empty( $message ) ) : ?>
		<div class="apollo-adverts-notice success">
			<?php echo esc_html( $message ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $errors ) ) : ?>
		<div class="apollo-adverts-notice error">
			<ul>
				<?php foreach ( $errors as $err ) : ?>
					<li><?php echo esc_html( $err ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<h2>
		<?php
		if ( $edit_id ) {
			esc_html_e( 'Editar Anúncio', 'apollo-adverts' );
		} else {
			esc_html_e( 'Criar Anúncio', 'apollo-adverts' );
		}
		?>
	</h2>

	<form method="post" class="apollo-adverts-form" enctype="multipart/form-data">
		<?php wp_nonce_field( 'apollo_classified_form', 'apollo_classified_nonce' ); ?>

		<?php if ( $edit_id ) : ?>
			<input type="hidden" name="edit_id" value="<?php echo (int) $edit_id; ?>" />
		<?php endif; ?>

		<?php
		/**
		 * Render form fields.
		 * Each field is rendered by its registered renderer (text, select, textarea, checkbox, hidden, gallery).
		 */
		$form->render();
		?>

		<div class="apollo-adverts-form-bridge-note">
			<p><?php esc_html_e( 'Apollo conecta pessoas. Não intermediamos nem processamos transações. Após publicar, os interessados poderão entrar em contato via Chat para combinar os detalhes diretamente com você.', 'apollo-adverts' ); ?></p>
		</div>

		<div class="apollo-adverts-form-actions">
			<button type="submit" name="apollo_submit_classified" class="button button-primary">
				<span>
					<?php
					if ( $edit_id ) {
						esc_html_e( 'Salvar Alterações', 'apollo-adverts' );
					} else {
						esc_html_e( 'Publicar Anúncio', 'apollo-adverts' );
					}
					?>
				</span>
			</button>

			<?php if ( $edit_id ) : ?>
				<a href="<?php echo esc_url( get_permalink( $edit_id ) ); ?>" class="button">
					<?php esc_html_e( 'Cancelar', 'apollo-adverts' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php
		/*
		 * 'moderation' is a STRING, 'auto'|'manual' (corrected 2026-08-17).
		 *
		 * This read was `(bool) $settings['moderation']`, and (bool) 'auto' is
		 * TRUE — so the note below promised every user their advert would be
		 * reviewed, including on the default setting where it publishes
		 * instantly. Reading through apollo_adverts_config() also picks up the
		 * default when the option row has no value, instead of assuming true.
		 */
		$moderation = 'manual' === apollo_adverts_config( 'moderation', 'auto' );
		if ( $moderation && ! $edit_id ) :
			?>
			<p class="apollo-adverts-form-note">
				<em><?php esc_html_e( 'Seu anúncio será revisado antes de ser publicado.', 'apollo-adverts' ); ?></em>
			</p>
		<?php endif; ?>
	</form>

</div>