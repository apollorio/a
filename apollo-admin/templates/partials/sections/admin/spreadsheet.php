<?php
/**
 * Admin Section — Spreadsheet (CPT data tables)
 *
 * Master page: page-admin-users-sheet with feed-tabs switching to
 * page-admin-events-sheet, page-admin-dj-sheet, page-admin-local-sheet,
 * page-admin-hub-sheet, page-admin-class-sheet, page-admin-class1-sheet,
 * page-admin-class2-sheet, page-admin-social-sheet, page-admin-depo-sheet.
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Pre-fetch all data once ──
$sheet_per_page = 25;

// Users
$user_count  = count_users();
$users_query = get_users(
	array(
		'number'  => $sheet_per_page,
		'orderby' => 'registered',
		'order'   => 'DESC',
	)
);

// Events
$event_cpt = 'event';
$events_q  = new WP_Query(
	array(
		'post_type'      => $event_cpt,
		'posts_per_page' => $sheet_per_page,
		'post_status'    => array( 'publish', 'draft', 'pending' ),
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

// DJs
$dj_cpt = 'dj';
$djs_q  = new WP_Query(
	array(
		'post_type'      => $dj_cpt,
		'posts_per_page' => $sheet_per_page,
		'post_status'    => array( 'publish', 'draft', 'pending' ),
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

// Locations
$local_cpt = defined( 'APOLLO_LOCAL_CPT' ) ? APOLLO_LOCAL_CPT : 'local';
$locals_q  = new WP_Query(
	array(
		'post_type'      => $local_cpt,
		'posts_per_page' => $sheet_per_page,
		'post_status'    => array( 'publish', 'draft', 'pending' ),
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

// Hub
$hub_cpt = 'hub';
$hubs_q  = new WP_Query(
	array(
		'post_type'      => $hub_cpt,
		'posts_per_page' => $sheet_per_page,
		'post_status'    => array( 'publish', 'draft', 'pending' ),
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

// Classifieds (all)
$class_cpt = 'classified';
$class_q   = new WP_Query(
	array(
		'post_type'      => $class_cpt,
		'posts_per_page' => $sheet_per_page,
		'post_status'    => array( 'publish', 'draft', 'pending' ),
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

// Helper for status badges
if ( ! function_exists( 'apollo_admin_status_badge' ) ) {
	function apollo_admin_status_badge( $status ) {
		$map = array(
			'publish' => array( 'Active', 'var(--primary)' ),
			'draft'   => array( 'Draft', 'var(--c-muted)' ),
			'pending' => array( 'Pending', '#e6a23c' ),
			'trash'   => array( 'Trash', 'var(--red)' ),
		);
		$s   = $map[ $status ] ?? array( ucfirst( $status ), 'var(--c-muted)' );
		return '<span class="pill" style="background:' . $s[1] . '20;color:' . $s[1] . ';font-size:10px;padding:2px 8px;border-radius:20px;font-family:var(--ff-mono);text-transform:uppercase">' . esc_html( $s[0] ) . '</span>';
	}
}
?>

<!-- ═══════════════ Users Spreadsheet (primary) ═══════════════ -->
<div class="page" id="page-admin-users-sheet">

	<div class="feed-tabs">
		<button class="feed-tab active" data-tab="user_listing_all spreadsheet" title="<?php esc_attr_e( 'Users', 'apollo-admin' ); ?>"><i class="ri-user-line"></i></button>
		<button class="tab-btn feed-tab" data-tab="admin-events-sheet" title="<?php esc_attr_e( 'Events', 'apollo-admin' ); ?>"><i class="ri-calendar-event-line"></i></button>
		<button class="tab-btn feed-tab" data-tab="admin-dj-sheet" title="<?php esc_attr_e( 'DJs', 'apollo-admin' ); ?>"><i class="ri-disc-line"></i></button>
		<button class="tab-btn feed-tab" data-tab="admin-local-sheet" title="<?php esc_attr_e( 'Locals', 'apollo-admin' ); ?>"><i class="ri-map-pin-line"></i></button>
		<button class="tab-btn feed-tab" data-tab="admin-hub-sheet" title="<?php esc_attr_e( 'Hub::rio', 'apollo-admin' ); ?>"><i class="ri-global-line"></i></button>
		<button class="tab-btn feed-tab" data-tab="admin-class-sheet" title="<?php esc_attr_e( 'Classifieds', 'apollo-admin' ); ?>"><i class="ri-exchange-box-line"></i></button>
		<button class="tab-btn feed-tab" data-tab="admin-class1-sheet" title="<?php esc_attr_e( 'Re-sell tickets', 'apollo-admin' ); ?>"><i class="ri-ticket-2-line"></i></button>
		<button class="tab-btn feed-tab" data-tab="admin-class2-sheet" title="<?php esc_attr_e( 'Accommodations', 'apollo-admin' ); ?>"><i class="ri-hotel-bed-line"></i></button>
	</div>

	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search users...', 'apollo-admin' ); ?>">
			<button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Export CSV', 'apollo-admin' ); ?>"><i class="ri-download-line"></i></button>
			<span class="spreadsheet-count">
				<?php echo esc_html( number_format_i18n( $user_count['total_users'] ) . ' ' . __( 'users', 'apollo-admin' ) ); ?>
			</span>
			<div class="spreadsheet-pagination">
				<button class="page-btn"><i class="ri-arrow-left-s-line"></i></button>
				<button class="page-btn active">1</button>
				<button class="page-btn">2</button>
				<button class="page-btn">3</button>
				<button class="page-btn">...</button>
				<button class="page-btn"><i class="ri-arrow-right-s-line"></i></button>
			</div>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th style="width:30px"><input type="checkbox"></th>
						<th><?php esc_html_e( 'User', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Email', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Role', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Rank', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Points', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Registered', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="apollo-users-sheet-body">
					<?php if ( ! empty( $users_query ) ) : ?>
						<?php
						foreach ( $users_query as $u ) :
							$roles_arr = $u->roles;
							$role      = ! empty( $roles_arr ) ? ucfirst( $roles_arr[0] ) : '—';
							$rank      = get_user_meta( $u->ID, '_apollo_rank', true ) ?: '—';
							$points    = get_user_meta( $u->ID, '_apollo_points', true ) ?: 0;
							$avatar    = get_avatar_url( $u->ID, array( 'size' => 28 ) );
							?>
						<tr>
							<td><input type="checkbox" value="<?php echo esc_attr( $u->ID ); ?>"></td>
							<td>
								<div style="display:flex;align-items:center;gap:8px">
									<img src="<?php echo esc_url( $avatar ); ?>" alt="" style="width:28px;height:28px;border-radius:50%;object-fit:cover">
									<span><?php echo esc_html( $u->display_name ); ?></span>
								</div>
							</td>
							<td style="font-family:var(--ff-mono);font-size:11px"><?php echo esc_html( $u->user_email ); ?></td>
							<td><?php echo esc_html( $role ); ?></td>
							<td><?php echo esc_html( $rank ); ?></td>
							<td style="font-family:var(--ff-mono)"><?php echo esc_html( number_format_i18n( (int) $points ) ); ?></td>
							<td style="font-size:11px;color:var(--c-muted)"><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $u->user_registered ) ) ); ?></td>
							<td>
								<?php
								$user_status = get_user_meta( $u->ID, '_apollo_status', true ) ?: 'active';
								if ( $user_status === 'active' ) {
									echo '<span class="pill" style="background:var(--primary)20;color:var(--primary);font-size:10px;padding:2px 8px;border-radius:20px;font-family:var(--ff-mono);text-transform:uppercase">Active</span>';
								} else {
									echo '<span class="pill" style="background:var(--red)20;color:var(--red);font-size:10px;padding:2px 8px;border-radius:20px;font-family:var(--ff-mono);text-transform:uppercase">' . esc_html( ucfirst( $user_status ) ) . '</span>';
								}
								?>
							</td>
							<td>
								<a href="<?php echo esc_url( get_edit_user_link( $u->ID ) ); ?>" class="btn btn-sm btn-outline" style="padding:2px 6px" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-pencil-line"></i></a>
							</td>
						</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="9" style="text-align:center;padding:40px;color:var(--c-muted)">
								<?php esc_html_e( 'No users found.', 'apollo-admin' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- ═══════════════ Events Spreadsheet ═══════════════ -->
<div class="page" id="page-admin-events-sheet">
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search events...', 'apollo-admin' ); ?>">
			<select class="select" style="height:32px;font-size:11px;width:140px">
				<option><?php esc_html_e( 'All Status', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Published', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Draft', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Cancelled', 'apollo-admin' ); ?></option>
			</select>
			<button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Export', 'apollo-admin' ); ?>"><i class="ri-download-line"></i></button>
			<button class="btn btn-sm btn-primary" data-apollo-form="new-event" title="<?php esc_attr_e( 'New Event', 'apollo-admin' ); ?>"><i class="ri-add-line"></i></button>
			<span class="spreadsheet-count">
				<?php
				$event_count = wp_count_posts( $event_cpt );
				$ev_total    = ( $event_count ? (int) $event_count->publish + (int) $event_count->draft : 0 );
				echo esc_html( $ev_total . ' ' . __( 'events', 'apollo-admin' ) );
				?>
			</span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th style="width:30px"><input type="checkbox"></th>
						<th><?php esc_html_e( 'Event Name', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Date', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Location', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Privacy', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Price', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="apollo-events-sheet-body">
					<?php if ( $events_q->have_posts() ) : ?>
						<?php
						while ( $events_q->have_posts() ) :
							$events_q->the_post();
							$eid        = get_the_ID();
							$start_date = get_post_meta( $eid, '_event_start_date', true );
							$start_time = get_post_meta( $eid, '_event_start_time', true );
							$loc_id     = (int) get_post_meta( $eid, '_event_loc_id', true );
							$loc_name   = $loc_id ? get_the_title( $loc_id ) : '—';
							$privacy    = get_post_meta( $eid, '_event_privacy', true ) ?: 'public';
							$price      = get_post_meta( $eid, '_event_ticket_price', true ) ?: '—';
							?>
						<tr>
							<td><input type="checkbox" value="<?php echo esc_attr( $eid ); ?>"></td>
							<td><strong><?php echo esc_html( get_the_title() ); ?></strong></td>
							<td style="font-family:var(--ff-mono);font-size:11px;white-space:nowrap">
								<?php
								if ( $start_date ) {
									echo esc_html( date_i18n( 'd/m/Y', strtotime( $start_date ) ) );
									if ( $start_time ) {
										echo ' ' . esc_html( $start_time );
									}
								} else {
									echo '—';
								}
								?>
							</td>
							<td><?php echo esc_html( $loc_name ); ?></td>
							<td style="text-transform:capitalize;font-size:11px"><?php echo esc_html( $privacy ); ?></td>
							<td style="font-family:var(--ff-mono);font-size:11px"><?php echo esc_html( $price ); ?></td>
							<td><?php echo apollo_admin_status_badge( get_post_status() ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $eid ) ); ?>" class="btn btn-sm btn-outline" style="padding:2px 6px" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-pencil-line"></i></a>
							</td>
						</tr>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					<?php else : ?>
						<tr>
							<td colspan="8" style="text-align:center;padding:40px;color:var(--c-muted)">
								<?php esc_html_e( 'No events found.', 'apollo-admin' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- ═══════════════ DJs Spreadsheet ═══════════════ -->
<div class="page" id="page-admin-dj-sheet">
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search DJs...', 'apollo-admin' ); ?>">
			<button class="btn btn-sm btn-outline" title="<?php esc_attr_e( 'Export', 'apollo-admin' ); ?>"><i class="ri-download-line"></i></button>
			<button class="btn btn-sm btn-primary" data-apollo-form="new-dj" title="<?php esc_attr_e( 'Add DJ', 'apollo-admin' ); ?>"><i class="ri-add-line"></i></button>
			<span class="spreadsheet-count">
				<?php
				$dj_count = wp_count_posts( $dj_cpt );
				$dj_total = $dj_count ? (int) $dj_count->publish + (int) $dj_count->draft : 0;
				echo esc_html( $dj_total . ' DJs' );
				?>
			</span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th style="width:30px"><input type="checkbox"></th>
						<th><?php esc_html_e( 'DJ', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Bio', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Instagram', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Verified', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Linked User', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="apollo-dj-sheet-body">
					<?php if ( $djs_q->have_posts() ) : ?>
						<?php
						while ( $djs_q->have_posts() ) :
							$djs_q->the_post();
							$did       = get_the_ID();
							$bio       = get_post_meta( $did, '_dj_bio_short', true );
							$insta     = get_post_meta( $did, '_dj_instagram', true ) ?: '—';
							$verified  = (int) get_post_meta( $did, '_dj_verified', true );
							$uid       = (int) get_post_meta( $did, '_dj_user_id', true );
							$user_name = $uid ? get_the_author_meta( 'display_name', $uid ) : '—';
							?>
						<tr>
							<td><input type="checkbox" value="<?php echo esc_attr( $did ); ?>"></td>
							<td><strong><?php echo esc_html( get_the_title() ); ?></strong></td>
							<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;color:var(--c-muted)">
								<?php echo esc_html( $bio ? mb_strimwidth( $bio, 0, 60, '…' ) : '—' ); ?>
							</td>
							<td style="font-family:var(--ff-mono);font-size:11px"><?php echo esc_html( $insta ); ?></td>
							<td style="text-align:center">
								<?php echo $verified ? '<i class="ri-verified-badge-fill" style="color:var(--primary);font-size:16px"></i>' : '<i class="ri-close-line" style="color:var(--c-muted)"></i>'; ?>
							</td>
							<td style="font-size:11px"><?php echo esc_html( $user_name ); ?></td>
							<td><?php echo apollo_admin_status_badge( get_post_status() ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $did ) ); ?>" class="btn btn-sm btn-outline" style="padding:2px 6px" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-pencil-line"></i></a>
							</td>
						</tr>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					<?php else : ?>
						<tr>
							<td colspan="8" style="text-align:center;padding:40px;color:var(--c-muted)">
								<?php esc_html_e( 'No DJs found.', 'apollo-admin' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- ═══════════════ Location Spreadsheet ═══════════════ -->
<div class="page" id="page-admin-local-sheet">
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search locations...', 'apollo-admin' ); ?>">
			<button class="btn btn-sm btn-primary" data-apollo-form="new-local" title="<?php esc_attr_e( 'Add Location', 'apollo-admin' ); ?>"><i class="ri-add-line"></i></button>
			<span class="spreadsheet-count">
				<?php
				$loc_count = wp_count_posts( $local_cpt );
				$loc_total = $loc_count ? (int) $loc_count->publish + (int) $loc_count->draft : 0;
				echo esc_html( $loc_total . ' ' . __( 'locations', 'apollo-admin' ) );
				?>
			</span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th style="width:30px"><input type="checkbox"></th>
						<th><?php esc_html_e( 'Name', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Address', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'City', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Capacity', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Price Range', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="apollo-local-sheet-body">
					<?php if ( $locals_q->have_posts() ) : ?>
						<?php
						while ( $locals_q->have_posts() ) :
							$locals_q->the_post();
							$lid      = get_the_ID();
							$addr     = get_post_meta( $lid, '_local_address', true ) ?: '—';
							$city     = get_post_meta( $lid, '_local_city', true ) ?: '—';
							$capacity = (int) get_post_meta( $lid, '_local_capacity', true );
							$price_r  = get_post_meta( $lid, '_local_price_range', true ) ?: '—';
							?>
						<tr>
							<td><input type="checkbox" value="<?php echo esc_attr( $lid ); ?>"></td>
							<td><strong><?php echo esc_html( get_the_title() ); ?></strong></td>
							<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px"><?php echo esc_html( $addr ); ?></td>
							<td style="font-size:11px"><?php echo esc_html( $city ); ?></td>
							<td style="font-family:var(--ff-mono);font-size:11px"><?php echo $capacity ? esc_html( number_format_i18n( $capacity ) ) : '—'; ?></td>
							<td style="font-family:var(--ff-mono)"><?php echo esc_html( $price_r ); ?></td>
							<td><?php echo apollo_admin_status_badge( get_post_status() ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $lid ) ); ?>" class="btn btn-sm btn-outline" style="padding:2px 6px" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-pencil-line"></i></a>
							</td>
						</tr>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					<?php else : ?>
						<tr>
							<td colspan="8" style="text-align:center;padding:40px;color:var(--c-muted)">
								<?php esc_html_e( 'No locations found.', 'apollo-admin' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- ═══════════════ HUB Spreadsheet ═══════════════ -->
<div class="page" id="page-admin-hub-sheet">
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search HUB entries...', 'apollo-admin' ); ?>">
			<button class="btn btn-sm btn-primary" data-apollo-form="new-hub" title="<?php esc_attr_e( 'Add Entry', 'apollo-admin' ); ?>"><i class="ri-add-line"></i></button>
			<span class="spreadsheet-count">
				<?php
				$hub_count = wp_count_posts( $hub_cpt );
				$hub_total = $hub_count ? (int) $hub_count->publish + (int) $hub_count->draft : 0;
				echo esc_html( $hub_total . ' ' . __( 'entries', 'apollo-admin' ) );
				?>
			</span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th style="width:30px"><input type="checkbox"></th>
						<th><?php esc_html_e( 'Title', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Author', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Date', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Views', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="apollo-hub-sheet-body">
					<?php if ( $hubs_q->have_posts() ) : ?>
						<?php
						while ( $hubs_q->have_posts() ) :
							$hubs_q->the_post();
							$hid    = get_the_ID();
							$author = get_the_author();
							$views  = (int) get_post_meta( $hid, '_hub_view_count', true );
							?>
						<tr>
							<td><input type="checkbox" value="<?php echo esc_attr( $hid ); ?>"></td>
							<td><strong><?php echo esc_html( get_the_title() ); ?></strong></td>
							<td style="font-size:11px"><?php echo esc_html( $author ); ?></td>
							<td style="font-family:var(--ff-mono);font-size:11px"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></td>
							<td style="font-family:var(--ff-mono);font-size:11px"><?php echo esc_html( number_format_i18n( $views ) ); ?></td>
							<td><?php echo apollo_admin_status_badge( get_post_status() ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $hid ) ); ?>" class="btn btn-sm btn-outline" style="padding:2px 6px" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-pencil-line"></i></a>
							</td>
						</tr>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					<?php else : ?>
						<tr>
							<td colspan="7" style="text-align:center;padding:40px;color:var(--c-muted)">
								<?php esc_html_e( 'No HUB entries found.', 'apollo-admin' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- ═══════════════ Classifieds Spreadsheet (All) ═══════════════ -->
<div class="page" id="page-admin-class-sheet">
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search classifieds...', 'apollo-admin' ); ?>">
			<select class="select" style="height:32px;font-size:11px;width:140px">
				<option><?php esc_html_e( 'All Types', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Ticket Resale', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Accommodation', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Rideshare', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Equipment', 'apollo-admin' ); ?></option>
			</select>
			<button class="btn btn-sm btn-primary" data-apollo-form="new-classified" title="<?php esc_attr_e( 'Add Listing', 'apollo-admin' ); ?>"><i class="ri-add-line"></i></button>
			<span class="spreadsheet-count">
				<?php
				$class_count = wp_count_posts( $class_cpt );
				$cls_total   = $class_count ? (int) $class_count->publish + (int) $class_count->draft : 0;
				echo esc_html( $cls_total . ' ' . __( 'listings', 'apollo-admin' ) );
				?>
			</span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th style="width:30px"><input type="checkbox"></th>
						<th><?php esc_html_e( 'Title', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Price', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Condition', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Author', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Date', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="apollo-class-sheet-body">
					<?php if ( $class_q->have_posts() ) : ?>
						<?php
						while ( $class_q->have_posts() ) :
							$class_q->the_post();
							$cid       = get_the_ID();
							$price     = get_post_meta( $cid, '_classified_price', true );
							$currency  = get_post_meta( $cid, '_classified_currency', true ) ?: 'BRL';
							$condition = get_post_meta( $cid, '_classified_condition', true ) ?: '—';
							?>
						<tr>
							<td><input type="checkbox" value="<?php echo esc_attr( $cid ); ?>"></td>
							<td><strong><?php echo esc_html( get_the_title() ); ?></strong></td>
							<td style="font-family:var(--ff-mono);font-size:11px;white-space:nowrap">
								<?php echo $price ? esc_html( $currency . ' ' . number_format( (float) $price, 2, ',', '.' ) ) : '—'; ?>
							</td>
							<td style="text-transform:capitalize;font-size:11px"><?php echo esc_html( $condition ); ?></td>
							<td style="font-size:11px"><?php echo esc_html( get_the_author() ); ?></td>
							<td style="font-family:var(--ff-mono);font-size:11px"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></td>
							<td><?php echo apollo_admin_status_badge( get_post_status() ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $cid ) ); ?>" class="btn btn-sm btn-outline" style="padding:2px 6px" title="<?php esc_attr_e( 'Edit', 'apollo-admin' ); ?>"><i class="ri-pencil-line"></i></a>
							</td>
						</tr>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					<?php else : ?>
						<tr>
							<td colspan="8" style="text-align:center;padding:40px;color:var(--c-muted)">
								<?php esc_html_e( 'No classifieds found.', 'apollo-admin' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- ═══════════════ Ticket Resale Sheet (Classified filter) ═══════════════ -->
<?php
$class1_q = new WP_Query(
	array(
		'post_type'      => $class_cpt,
		'posts_per_page' => $sheet_per_page,
		'post_status'    => array( 'publish', 'draft', 'pending' ),
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => array(
			array(
				'key'     => '_classified_type',
				'value'   => 'ticket',
				'compare' => '=',
			),
		),
	)
);
?>
<div class="page" id="page-admin-class1-sheet">
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search ticket resale...', 'apollo-admin' ); ?>">
			<button class="btn btn-sm btn-primary" data-apollo-form="new-classified" title="<?php esc_attr_e( 'Add Ticket Listing', 'apollo-admin' ); ?>"><i class="ri-add-line"></i></button>
			<span class="spreadsheet-count"><?php echo esc_html( $class1_q->found_posts . ' tickets' ); ?></span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th style="width:30px"><input type="checkbox"></th>
						<th><?php esc_html_e( 'Title', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Price', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Seller', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Date', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $class1_q->have_posts() ) : ?>
						<?php
						while ( $class1_q->have_posts() ) :
							$class1_q->the_post();
							$cid   = get_the_ID();
							$price = get_post_meta( $cid, '_classified_price', true );
							?>
						<tr>
							<td><input type="checkbox" value="<?php echo esc_attr( $cid ); ?>"></td>
							<td><strong><?php echo esc_html( get_the_title() ); ?></strong></td>
							<td style="font-family:var(--ff-mono);font-size:11px"><?php echo $price ? esc_html( 'R$ ' . number_format( (float) $price, 2, ',', '.' ) ) : '—'; ?></td>
							<td style="font-size:11px"><?php echo esc_html( get_the_author() ); ?></td>
							<td style="font-family:var(--ff-mono);font-size:11px"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></td>
							<td><?php echo apollo_admin_status_badge( get_post_status() ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $cid ) ); ?>" class="btn btn-sm btn-outline" style="padding:2px 6px"><i class="ri-pencil-line"></i></a>
							</td>
						</tr>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					<?php else : ?>
						<tr>
							<td colspan="7" style="text-align:center;padding:40px;color:var(--c-muted)">
								<?php esc_html_e( 'No ticket resale listings found.', 'apollo-admin' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- ═══════════════ Accommodations Sheet (Classified filter) ═══════════════ -->
<?php
$class2_q = new WP_Query(
	array(
		'post_type'      => $class_cpt,
		'posts_per_page' => $sheet_per_page,
		'post_status'    => array( 'publish', 'draft', 'pending' ),
		'orderby'        => 'date',
		'order'          => 'DESC',
		'meta_query'     => array(
			array(
				'key'     => '_classified_type',
				'value'   => 'accommodation',
				'compare' => '=',
			),
		),
	)
);
?>
<div class="page" id="page-admin-class2-sheet">
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search accommodations...', 'apollo-admin' ); ?>">
			<button class="btn btn-sm btn-primary" data-apollo-form="new-classified" title="<?php esc_attr_e( 'Add Accommodation', 'apollo-admin' ); ?>"><i class="ri-add-line"></i></button>
			<span class="spreadsheet-count"><?php echo esc_html( $class2_q->found_posts . ' accommodations' ); ?></span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th style="width:30px"><input type="checkbox"></th>
						<th><?php esc_html_e( 'Title', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Price', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Host', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Date', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( $class2_q->have_posts() ) : ?>
						<?php
						while ( $class2_q->have_posts() ) :
							$class2_q->the_post();
							$cid   = get_the_ID();
							$price = get_post_meta( $cid, '_classified_price', true );
							?>
						<tr>
							<td><input type="checkbox" value="<?php echo esc_attr( $cid ); ?>"></td>
							<td><strong><?php echo esc_html( get_the_title() ); ?></strong></td>
							<td style="font-family:var(--ff-mono);font-size:11px"><?php echo $price ? esc_html( 'R$ ' . number_format( (float) $price, 2, ',', '.' ) ) : '—'; ?></td>
							<td style="font-size:11px"><?php echo esc_html( get_the_author() ); ?></td>
							<td style="font-family:var(--ff-mono);font-size:11px"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></td>
							<td><?php echo apollo_admin_status_badge( get_post_status() ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_edit_post_link( $cid ) ); ?>" class="btn btn-sm btn-outline" style="padding:2px 6px"><i class="ri-pencil-line"></i></a>
							</td>
						</tr>
							<?php
						endwhile;
						wp_reset_postdata();
						?>
					<?php else : ?>
						<tr>
							<td colspan="7" style="text-align:center;padding:40px;color:var(--c-muted)">
								<?php esc_html_e( 'No accommodation listings found.', 'apollo-admin' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- ═══════════════ Social Posts Spreadsheet ═══════════════ -->
<div class="page" id="page-admin-social-sheet">
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search posts...', 'apollo-admin' ); ?>">
			<select class="select" style="height:32px;font-size:11px;width:140px">
				<option><?php esc_html_e( 'All Posts', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Flagged', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Hidden', 'apollo-admin' ); ?></option>
			</select>
			<span class="spreadsheet-count">— <?php esc_html_e( 'posts', 'apollo-admin' ); ?></span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th style="width:30px"><input type="checkbox"></th>
						<th><?php esc_html_e( 'Author', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Content', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Likes', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Comments', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Date', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="apollo-social-sheet-body">
					<tr>
						<td colspan="8" style="text-align:center;padding:40px;color:var(--c-muted)">
							<i class="ri-information-line" style="font-size:20px"></i><br>
							<?php esc_html_e( 'Social data loaded via REST API (apollo-social).', 'apollo-admin' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- ═══════════════ Depoimentos Spreadsheet ═══════════════ -->
<div class="page" id="page-admin-depo-sheet">
	<div class="panel">
		<div class="spreadsheet-toolbar">
			<input type="text" class="apollo-input spreadsheet-search" placeholder="<?php esc_attr_e( 'Search depoimentos...', 'apollo-admin' ); ?>">
			<select class="select" style="height:32px;font-size:11px;width:140px">
				<option><?php esc_html_e( 'All', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Pending Approval', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Approved', 'apollo-admin' ); ?></option>
				<option><?php esc_html_e( 'Rejected', 'apollo-admin' ); ?></option>
			</select>
			<span class="spreadsheet-count">— <?php esc_html_e( 'depoimentos', 'apollo-admin' ); ?></span>
		</div>
		<div style="overflow-x:auto">
			<table class="data-table">
				<thead>
					<tr>
						<th style="width:30px"><input type="checkbox"></th>
						<th><?php esc_html_e( 'From', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'To', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Content', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Rating', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Date', 'apollo-admin' ); ?></th>
						<th><?php esc_html_e( 'Status', 'apollo-admin' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="apollo-depo-sheet-body">
					<tr>
						<td colspan="8" style="text-align:center;padding:40px;color:var(--c-muted)">
							<i class="ri-information-line" style="font-size:20px"></i><br>
							<?php esc_html_e( 'Depoimentos loaded via REST API (apollo-comment).', 'apollo-admin' ); ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>