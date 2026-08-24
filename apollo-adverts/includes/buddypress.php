<?php
/**
 * BuddyPress Integration
 *
 * Adds "Meus Anúncios" tab to BP profile.
 * Users can view/manage their classifieds from their BP profile.
 * Adapted from WPAdverts BuddyPress extension + Apollo Social BP patterns.
 *
 * @package Apollo\Adverts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if BuddyPress is active and the profile tab is enabled
 */
function apollo_adverts_bp_is_active(): bool {
	return function_exists( 'bp_is_active' ) && bp_is_active( 'members' ) && apollo_adverts_config( 'bp_profile_tab', true );
}

/**
 * Register BP navigation tab
 * Adapted from apollo-social BP tab registration
 */
function apollo_adverts_bp_setup_nav(): void {
	if ( ! apollo_adverts_bp_is_active() ) {
		return;
	}

	$user_domain = bp_loggedin_user_domain();
	$slug        = 'anuncios';

	bp_core_new_nav_item(
		array(
			'name'                => __( 'Anúncios', 'apollo-adverts' ),
			'slug'                => $slug,
			'screen_function'     => 'apollo_adverts_bp_screen_list',
			'position'            => 75,
			'default_subnav_slug' => 'meus',
			'item_css_id'         => 'apollo-adverts',
		)
	);

	// Subnav: Meus Anúncios
	bp_core_new_subnav_item(
		array(
			'name'            => __( 'Meus Anúncios', 'apollo-adverts' ),
			'slug'            => 'meus',
			'parent_slug'     => $slug,
			'parent_url'      => trailingslashit( $user_domain . $slug ),
			'screen_function' => 'apollo_adverts_bp_screen_list',
			'position'        => 10,
		)
	);

	// Subnav: Criar
	bp_core_new_subnav_item(
		array(
			'name'            => __( 'Criar Anúncio', 'apollo-adverts' ),
			'slug'            => 'criar',
			'parent_slug'     => $slug,
			'parent_url'      => trailingslashit( $user_domain . $slug ),
			'screen_function' => 'apollo_adverts_bp_screen_create',
			'position'        => 20,
			'user_has_access' => bp_is_my_profile(),
		)
	);

	// Subnav: Favoritos (if apollo-fav active)
	if ( function_exists( 'apollo_fav_is_active' ) || defined( 'APOLLO_FAV_VERSION' ) ) {
		bp_core_new_subnav_item(
			array(
				'name'            => __( 'Favoritos', 'apollo-adverts' ),
				'slug'            => 'favoritos',
				'parent_slug'     => $slug,
				'parent_url'      => trailingslashit( $user_domain . $slug ),
				'screen_function' => 'apollo_adverts_bp_screen_favorites',
				'position'        => 30,
			)
		);
	}
}
add_action( 'bp_setup_nav', 'apollo_adverts_bp_setup_nav', 100 );

/**
 * Screen function: List user's classifieds
 */
function apollo_adverts_bp_screen_list(): void {
	add_action( 'bp_template_content', 'apollo_adverts_bp_content_list' );
	bp_core_load_template( 'members/single/plugins' );
}

/**
 * Content: List user's classifieds
 * Adapted from WPAdverts manage shortcode + BP profile tab pattern
 */
function apollo_adverts_bp_content_list(): void {
	$displayed_user_id = bp_displayed_user_id();
	$is_own_profile    = bp_is_my_profile();
	$paged             = max( 1, get_query_var( 'paged', 1 ) );

	$statuses = array( 'publish' );
	if ( $is_own_profile ) {
		$statuses = array_merge( $statuses, array( 'pending', 'draft', 'expired' ) );
	}

	$args = array(
		'post_type'      => APOLLO_CPT_CLASSIFIED,
		'post_status'    => $statuses,
		'author'         => $displayed_user_id,
		'posts_per_page' => APOLLO_ADVERTS_POSTS_PER_PAGE,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$query = new WP_Query( $args );

	wp_enqueue_style( 'apollo-adverts' );

	?>
	<div class="apollo-adverts-bp-list">
		<?php if ( $is_own_profile ) : ?>
			<div class="apollo-adverts-bp-header">
				<h3><?php esc_html_e( 'Meus Anúncios', 'apollo-adverts' ); ?></h3>
				<a href="<?php echo esc_url( trailingslashit( bp_loggedin_user_domain() . 'anuncios/criar' ) ); ?>" class="button">
					<?php esc_html_e( '+ Criar Anúncio', 'apollo-adverts' ); ?>
				</a>
			</div>
		<?php else : ?>
			<h3><?php printf( esc_html__( 'Anúncios de %s', 'apollo-adverts' ), bp_get_displayed_user_fullname() ); ?></h3>
		<?php endif; ?>

		<?php if ( $query->have_posts() ) : ?>
			<table class="apollo-adverts-manage-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Foto', 'apollo-adverts' ); ?></th>
						<th><?php esc_html_e( 'Título', 'apollo-adverts' ); ?></th>
						<th><?php esc_html_e( 'Valor Ref.', 'apollo-adverts' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-adverts' ); ?></th>
						<?php if ( $is_own_profile ) : ?>
							<th><?php esc_html_e( 'Ações', 'apollo-adverts' ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						?>
						<?php
						$post_id       = get_the_ID();
						$status        = get_post_status();
						$status_labels = array(
							'publish' => __( 'Ativo', 'apollo-adverts' ),
							'pending' => __( 'Pendente', 'apollo-adverts' ),
							'draft'   => __( 'Rascunho', 'apollo-adverts' ),
							'expired' => __( 'Expirado', 'apollo-adverts' ),
						);
						$status_label  = $status_labels[ $status ] ?? $status;
						?>
						<tr>
							<td class="col-image">
								<?php
								$img_url = apollo_adverts_get_main_image( $post_id, 'classified-thumb' );
								if ( $img_url ) {
									printf( '<img src="%s" alt="" width="60" height="60" />', esc_url( $img_url ) );
								}
								?>
							</td>
							<td class="col-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								<?php
								$expires = get_post_meta( $post_id, '_classified_expires_at', true );
								if ( $expires ) {
									printf( '<br><small>%s: %s</small>', esc_html__( 'Expira', 'apollo-adverts' ), esc_html( $expires ) );
								}
								?>
							</td>
							<td class="col-price"><?php echo esc_html( apollo_adverts_get_the_price( $post_id ) ?: '—' ); ?></td>
							<td class="col-status"><span class="status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
							<?php if ( $is_own_profile ) : ?>
								<td class="col-actions">
									<?php
									$submit_page = get_option( 'apollo_adverts_submit_page_id', 0 );
									$edit_url    = $submit_page
										? add_query_arg( 'edit', $post_id, get_permalink( $submit_page ) )
										: '#';
									?>
									<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small"><?php esc_html_e( 'Editar', 'apollo-adverts' ); ?></a>
									<?php if ( $status === 'expired' ) : ?>
										<a href="
										<?php
										echo esc_url(
											wp_nonce_url(
												add_query_arg(
													array(
														'action'  => 'apollo_renew_ad',
														'post_id' => $post_id,
													),
													admin_url( 'admin-post.php' )
												),
												'apollo_renew_' . $post_id
											)
										);
										?>
													" class="button button-small"><?php esc_html_e( 'Renovar', 'apollo-adverts' ); ?></a>
									<?php endif; ?>
									<a href="
									<?php
									echo esc_url(
										wp_nonce_url(
											add_query_arg(
												array(
													'action'  => 'apollo_delete_ad',
													'post_id' => $post_id,
												),
												admin_url( 'admin-post.php' )
											),
											'apollo_delete_' . $post_id
										)
									);
									?>
												" class="button button-small button-danger" onclick="return confirm('<?php esc_attr_e( 'Excluir este anúncio?', 'apollo-adverts' ); ?>')"><?php esc_html_e( 'Excluir', 'apollo-adverts' ); ?></a>
								</td>
							<?php endif; ?>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>

			<?php
			// Pagination
			$total_pages = $query->max_num_pages;
			if ( $total_pages > 1 ) {
				echo '<div class="apollo-adverts-pagination">';
				echo wp_kses_post(
					paginate_links(
						array(
							'total'   => $total_pages,
							'current' => $paged,
							'format'  => '?paged=%#%',
						)
					)
				);
				echo '</div>';
			}
			?>
		<?php else : ?>
			<p class="apollo-adverts-empty">
				<?php
				if ( $is_own_profile ) {
					esc_html_e( 'Você ainda não tem anúncios.', 'apollo-adverts' );
				} else {
					esc_html_e( 'Nenhum anúncio encontrado.', 'apollo-adverts' );
				}
				?>
			</p>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</div>
	<?php
}

/**
 * Screen function: Create classified from BP profile
 */
function apollo_adverts_bp_screen_create(): void {
	if ( ! bp_is_my_profile() ) {
		bp_core_redirect( bp_displayed_user_domain() );
		return;
	}
	add_action( 'bp_template_content', 'apollo_adverts_bp_content_create' );
	bp_core_load_template( 'members/single/plugins' );
}

/**
 * Content: Inline form for creating a classified
 */
function apollo_adverts_bp_content_create(): void {
	echo do_shortcode( '[apollo_classified_form]' );
}

/**
 * Screen function: Favorites
 */
function apollo_adverts_bp_screen_favorites(): void {
	add_action( 'bp_template_content', 'apollo_adverts_bp_content_favorites' );
	bp_core_load_template( 'members/single/plugins' );
}

/**
 * Content: Favorite classifieds (from apollo-fav)
 */
function apollo_adverts_bp_content_favorites(): void {
	$displayed_user_id = bp_displayed_user_id();

	wp_enqueue_style( 'apollo-adverts' );

	// Get favorite IDs from apollo-fav (if available)
	$fav_ids = array();
	if ( function_exists( 'apollo_fav_get_user_favorites' ) ) {
		$fav_ids = apollo_fav_get_user_favorites( $displayed_user_id, APOLLO_CPT_CLASSIFIED );
	}

	if ( empty( $fav_ids ) ) {
		echo '<p class="apollo-adverts-empty">' . esc_html__( 'Nenhum anúncio favoritado.', 'apollo-adverts' ) . '</p>';
		return;
	}

	$query = new WP_Query(
		array(
			'post_type'      => APOLLO_CPT_CLASSIFIED,
			'post_status'    => 'publish',
			'post__in'       => $fav_ids,
			'posts_per_page' => APOLLO_ADVERTS_POSTS_PER_PAGE,
			'orderby'        => 'post__in',
		)
	);

	if ( $query->have_posts() ) {
		echo '<div class="apollo-adverts-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			apollo_adverts_load_template(
				'list-item.php',
				array(
					'post_id' => get_the_ID(),
				)
			);
		}
		echo '</div>';
		wp_reset_postdata();
	} else {
		echo '<p class="apollo-adverts-empty">' . esc_html__( 'Nenhum anúncio favoritado.', 'apollo-adverts' ) . '</p>';
	}
}

/**
 * Add classified ad count to BP tab title
 */
function apollo_adverts_bp_nav_count(): void {
	if ( ! apollo_adverts_bp_is_active() ) {
		return;
	}

	$displayed_user_id = bp_displayed_user_id();
	if ( ! $displayed_user_id ) {
		return;
	}

	$count = apollo_adverts_user_ad_count( $displayed_user_id );

	buddypress()->members->nav->edit_nav(
		array(
			'name' => sprintf( __( 'Anúncios <span class="count">%d</span>', 'apollo-adverts' ), $count ),
		),
		'anuncios'
	);
}
add_action( 'bp_template_content', 'apollo_adverts_bp_nav_count', 1 );

/**
 * Where to send the user back after renew/delete/toggle-status (PHASE 008).
 *
 * These handlers originally hardcoded a redirect to the BuddyPress "Meus
 * Anúncios" tab (bp_loggedin_user_domain() . 'anuncios/meus/'). That breaks
 * two ways once a non-BP caller exists: it fatals if BuddyPress isn't active
 * (bp_loggedin_user_domain() undefined), and it silently bounces a user
 * acting from the new top-level /anuncios/meus Blank Canvas Apollo+ screen
 * back to the older BP-chrome tab instead of the page they were on. Prefer
 * the real referer (works for both entry points); fall back to whichever
 * "Meus Anúncios" surface actually exists on this install.
 *
 * @return string
 */
function apollo_adverts_my_listings_redirect(): string {
	$referer = wp_get_referer();
	if ( $referer ) {
		return $referer;
	}
	if ( function_exists( 'apollo_adverts_bp_is_active' ) && apollo_adverts_bp_is_active() && function_exists( 'bp_loggedin_user_domain' ) ) {
		return trailingslashit( bp_loggedin_user_domain() . 'anuncios/meus' );
	}
	return home_url( '/anuncios/meus/' );
}

/**
 * Handle ad renewal from BP profile
 */
function apollo_adverts_handle_renew_ad(): void {
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
	if ( ! $post_id || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'apollo_renew_' . $post_id ) ) {
		wp_die( __( 'Ação inválida.', 'apollo-adverts' ) );
	}

	$post = get_post( $post_id );
	if ( ! $post || $post->post_type !== APOLLO_CPT_CLASSIFIED ) {
		wp_die( __( 'Anúncio não encontrado.', 'apollo-adverts' ) );
	}

	if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Permissão negada.', 'apollo-adverts' ) );
	}

	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => 'publish',
		)
	);

	apollo_adverts_set_expiration( $post_id );
	do_action( 'apollo/classifieds/renewed', $post_id );

	wp_safe_redirect( apollo_adverts_my_listings_redirect() );
	exit;
}
add_action( 'admin_post_apollo_renew_ad', 'apollo_adverts_handle_renew_ad' );

/**
 * Handle ad deletion from BP profile
 */
function apollo_adverts_handle_delete_ad(): void {
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
	if ( ! $post_id || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'apollo_delete_' . $post_id ) ) {
		wp_die( __( 'Ação inválida.', 'apollo-adverts' ) );
	}

	$post = get_post( $post_id );
	if ( ! $post || $post->post_type !== APOLLO_CPT_CLASSIFIED ) {
		wp_die( __( 'Anúncio não encontrado.', 'apollo-adverts' ) );
	}

	if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Permissão negada.', 'apollo-adverts' ) );
	}

	wp_trash_post( $post_id );
	do_action( 'apollo/classifieds/deleted', $post_id );

	wp_safe_redirect( apollo_adverts_my_listings_redirect() );
	exit;
}
add_action( 'admin_post_apollo_delete_ad', 'apollo_adverts_handle_delete_ad' );

/**
 * Handle pause/activate toggle from the "Meus Anúncios" listing (PHASE 008).
 *
 * Mirrors apollo_adverts_handle_renew_ad()/apollo_adverts_handle_delete_ad()
 * exactly: nonce + ownership check, no admin capability required — this is a
 * self-service action for the ad's own author. Only ever flips between
 * 'publish' and 'draft' (the two states manage.php's row buttons offer);
 * pending/expired ads are not touched here.
 */
function apollo_adverts_handle_toggle_status(): void {
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
	if ( ! $post_id || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'apollo_toggle_status_' . $post_id ) ) {
		wp_die( __( 'Ação inválida.', 'apollo-adverts' ) );
	}

	$post = get_post( $post_id );
	if ( ! $post || $post->post_type !== APOLLO_CPT_CLASSIFIED ) {
		wp_die( __( 'Anúncio não encontrado.', 'apollo-adverts' ) );
	}

	if ( (int) $post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Permissão negada.', 'apollo-adverts' ) );
	}

	$new_status = isset( $_GET['to'] ) ? sanitize_key( $_GET['to'] ) : '';
	if ( ! in_array( $new_status, array( 'publish', 'draft' ), true ) ) {
		wp_die( __( 'Status inválido.', 'apollo-adverts' ) );
	}
	// Only a publish<->draft pair may be flipped this way — refuse to pull a
	// pending/expired ad into this path even if 'to' were spoofed to match.
	if ( ! in_array( $post->post_status, array( 'publish', 'draft' ), true ) ) {
		wp_die( __( 'Este anúncio não pode ser alternado por aqui.', 'apollo-adverts' ) );
	}

	wp_update_post(
		array(
			'ID'          => $post_id,
			'post_status' => $new_status,
		)
	);

	// Same rule the admin AJAX toggle uses: only backfill expiration if the
	// ad has never had one, don't reset an existing valid window.
	if ( 'publish' === $new_status && ! get_post_meta( $post_id, '_classified_expires_at', true ) ) {
		apollo_adverts_set_expiration( $post_id );
	}

	do_action( 'apollo/classifieds/status_toggled', $post_id, $new_status );

	wp_safe_redirect( apollo_adverts_my_listings_redirect() );
	exit;
}
add_action( 'admin_post_apollo_toggle_ad_status', 'apollo_adverts_handle_toggle_status' );
