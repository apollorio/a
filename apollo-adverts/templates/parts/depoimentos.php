<?php

/**
 * Template Part: Depoimentos (Testimonials) — accommodation adverts
 *
 * "Depoimentos" is the naming convention for the comment surface across the
 * whole Apollo ecosystem (registry 15-conventions: comment/review →
 * depoimento). It's also literally the pt-BR word for "testimonials", so the
 * label a Brazilian guest reads and the term the codebase uses are the same
 * word — no separate translation layer needed.
 *
 * Scope: accommodation adverts only. comments_open() already enforces this
 * via apollo_adverts_depoimentos_open() in includes/cpt.php, so this part
 * simply trusts it and bails when closed.
 *
 * Structure mirrors apollo-events' single/depoimentos.php timeline (same
 * design language across the ecosystem), re-prefixed apl-depo for adverts.
 *
 * Expected variables: $post_id
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = isset( $post_id ) ? (int) $post_id : get_the_ID();

if ( ! comments_open( $post_id ) && ! get_comments_number( $post_id ) ) {
	return;
}

$depoimentos = get_comments(
	array(
		'post_id' => $post_id,
		'status'  => 'approve',
		'number'  => 10,
		'orderby' => 'comment_date',
		'order'   => 'DESC',
	)
);

$depo_total = (int) get_comments_number( $post_id );
?>

<section class="apl-depo" aria-labelledby="apl-depo-title">
	<div class="apl-depo__header">
		<h3 class="apl-depo__title" id="apl-depo-title">
			<i class="ri-chat-quote-line" aria-hidden="true"></i>
			<?php esc_html_e( 'Depoimentos', 'apollo-adverts' ); ?>
		</h3>
		<span class="apl-depo__count"><?php echo esc_html( (string) $depo_total ); ?></span>
	</div>

	<?php if ( ! empty( $depoimentos ) ) : ?>
		<div class="apl-depo__timeline">
			<?php
			$last = count( $depoimentos ) - 1;
			foreach ( $depoimentos as $idx => $depo ) :
				$d_avatar = get_avatar_url( $depo->comment_author_email, array( 'size' => 80 ) );
				?>
				<article class="apl-depo__item<?php echo 0 === $idx ? ' is-latest' : ''; ?>">
					<div class="apl-depo__line" aria-hidden="true">
						<span class="apl-depo__dot"></span>
						<?php if ( $idx < $last ) : ?>
							<span class="apl-depo__connector"></span>
						<?php endif; ?>
					</div>
					<div class="apl-depo__card">
						<header class="apl-depo__card-header">
							<img src="<?php echo esc_url( $d_avatar ); ?>" alt="" class="apl-depo__avatar" loading="lazy" />
							<div class="apl-depo__meta">
								<span class="apl-depo__author"><?php echo esc_html( $depo->comment_author ); ?></span>
								<span class="apl-depo__time">
									<?php
									echo esc_html(
										sprintf(
											/* translators: %s: human-readable time difference, e.g. "3 dias". */
											__( 'há %s', 'apollo-adverts' ),
											human_time_diff( strtotime( $depo->comment_date ), current_time( 'timestamp' ) )
										)
									);
									?>
								</span>
							</div>
						</header>
						<p class="apl-depo__text"><?php echo wp_kses_post( $depo->comment_content ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="apl-depo__empty"><?php esc_html_e( 'Ainda não há depoimentos nesta hospedagem.', 'apollo-adverts' ); ?></p>
	<?php endif; ?>

	<?php if ( comments_open( $post_id ) && is_user_logged_in() ) : ?>
		<div class="apl-depo__form-wrap">
			<?php
			comment_form(
				array(
					'title_reply'          => '',
					'comment_notes_before' => '',
					'comment_notes_after'  => '',
					'label_submit'         => __( 'Enviar depoimento', 'apollo-adverts' ),
					'comment_field'        => '<div class="apl-depo__input-wrap"><textarea name="comment" class="apl-depo__input" rows="3" required placeholder="'
						. esc_attr__( 'Como foi sua estadia?', 'apollo-adverts' ) . '"></textarea></div>',
					'class_form'           => 'apl-depo__form',
					'class_submit'         => 'apl-depo__submit',
				),
				$post_id
			);
			?>
		</div>
	<?php elseif ( comments_open( $post_id ) ) : ?>
		<p class="apl-depo__login-cta">
			<a href="<?php echo esc_url( home_url( '/acesso?redirect=' . rawurlencode( (string) get_permalink( $post_id ) ) ) ); ?>">
				<i class="ri-lock-2-line" aria-hidden="true"></i>
				<?php esc_html_e( 'Entre para deixar um depoimento', 'apollo-adverts' ); ?>
			</a>
		</p>
	<?php endif; ?>
</section>

<style>
	/* Depoimentos — timeline, same design language as apollo-events'
	   single/depoimentos.php, re-scoped to the adverts prefix. Tokens only. */
	.apl-depo {
		margin-top: 28px;
		padding-top: 24px;
		border-top: 1px solid var(--border);
	}

	.apl-depo__header {
		display: flex;
		align-items: center;
		gap: 10px;
		margin-bottom: 18px;
	}

	.apl-depo__title {
		display: flex;
		align-items: center;
		gap: 8px;
		margin: 0;
		font-family: var(--ff-heading, var(--ff-main));
		font-size: calc(var(--fs-r, 1) * 1.05rem);
		font-weight: 700;
		color: var(--txt-heading);
	}

	.apl-depo__title i {
		color: var(--muted);
	}

	.apl-depo__count {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-width: 24px;
		height: 24px;
		padding: 0 8px;
		border-radius: var(--r-pill, 999px);
		background: var(--surface);
		font-family: var(--ff-mono);
		font-size: calc(var(--fs-r, 1) * 11px);
		color: var(--muted);
	}

	.apl-depo__timeline {
		display: flex;
		flex-direction: column;
		gap: 4px;
	}

	.apl-depo__item {
		display: flex;
		gap: 12px;
	}

	.apl-depo__line {
		display: flex;
		flex-direction: column;
		align-items: center;
		flex-shrink: 0;
		width: 12px;
		padding-top: 16px;
	}

	.apl-depo__dot {
		width: 7px;
		height: 7px;
		border-radius: 50%;
		background: var(--border);
		flex-shrink: 0;
	}

	.apl-depo__item.is-latest .apl-depo__dot {
		background: var(--accent);
	}

	.apl-depo__connector {
		flex: 1;
		width: 1px;
		background: var(--border);
		margin: 4px 0 0;
	}

	.apl-depo__card {
		flex: 1;
		min-width: 0;
		padding: 12px 14px 14px;
		margin-bottom: 8px;
		border-radius: var(--r, 12px);
		background: var(--surface);
		border: 1px solid rgba(var(--rgb-diff), .04);
	}

	.apl-depo__card-header {
		display: flex;
		align-items: center;
		gap: 10px;
		margin-bottom: 8px;
	}

	.apl-depo__avatar {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		object-fit: cover;
		flex-shrink: 0;
	}

	.apl-depo__meta {
		display: flex;
		flex-direction: column;
		min-width: 0;
	}

	.apl-depo__author {
		font-size: calc(var(--fs-r, 1) * 13px);
		font-weight: 600;
		color: var(--txt-heading);
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	.apl-depo__time {
		font-family: var(--ff-mono);
		font-size: calc(var(--fs-r, 1) * 10px);
		color: var(--muted);
	}

	.apl-depo__text {
		margin: 0;
		font-size: calc(var(--fs-r, 1) * 13px);
		line-height: 1.55;
		color: var(--txt-color);
	}

	.apl-depo__empty,
	.apl-depo__login-cta {
		margin: 0;
		font-size: calc(var(--fs-r, 1) * 12.5px);
		color: var(--muted);
	}

	.apl-depo__login-cta {
		margin-top: 14px;
	}

	.apl-depo__login-cta a {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		color: var(--txt-heading);
		text-decoration: none;
	}

	.apl-depo__login-cta a:hover {
		text-decoration: underline;
		text-underline-offset: 3px;
	}

	.apl-depo__form-wrap {
		margin-top: 18px;
	}

	.apl-depo__input {
		width: 100%;
		padding: 12px 14px;
		border-radius: var(--r, 12px);
		border: 1px solid var(--border);
		background: var(--bg);
		color: var(--txt-color);
		font-family: var(--ff-main);
		font-size: calc(var(--fs-r, 1) * 13px);
		line-height: 1.5;
		resize: vertical;
	}

	.apl-depo__input:focus {
		outline: none;
		border-color: color-mix(in srgb, var(--accent) 55%, transparent);
	}

	.apl-depo__submit {
		min-height: 42px;
		padding: 0 22px;
		margin-top: 10px;
		border: 0;
		border-radius: var(--r-pill, 999px);
		background: var(--accent);
		color: var(--bg);
		font-family: var(--ff-main);
		font-size: calc(var(--fs-r, 1) * 13px);
		font-weight: 600;
		cursor: pointer;
	}

	.apl-depo__submit:hover {
		opacity: .88;
	}
</style>
