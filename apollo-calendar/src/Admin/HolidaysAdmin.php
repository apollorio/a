<?php
/**
 * Apollo Calendar — Admin: Holiday Manager
 *
 * Registers a WP-Admin screen under Apollo Admin to view, add, edit and
 * delete holiday rows in apollo_holidays. Also registers the yearly
 * WP-Cron event to auto-seed next year's data.
 *
 * @package Apollo\Calendar\Admin
 * @since   1.0.0
 */

declare(strict_types=1);

namespace Apollo\Calendar\Admin;

use Apollo\Calendar\Holidays\HolidayModel;
use Apollo\Calendar\Holidays\Seeder;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HolidaysAdmin {

	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_post_apollo_calendar_save_holiday', array( $this, 'handle_save' ) );
		add_action( 'admin_post_apollo_calendar_delete_holiday', array( $this, 'handle_delete' ) );

		// Register and schedule yearly cron.
		add_filter( 'cron_schedules', array( $this, 'add_yearly_schedule' ) );
		add_action( 'apollo_calendar_seed_holidays', array( $this, 'cron_seed' ) );
		$this->schedule_cron();
	}

	// ─── Menu ──────────────────────────────────────────────────────────

	public function register_menu(): void {
		add_submenu_page(
			'apollo-admin',
			__( 'Calendar — Holidays', 'apollo-calendar' ),
			__( 'Holidays', 'apollo-calendar' ),
			'manage_options',
			'apollo-calendar-holidays',
			array( $this, 'render_page' )
		);
	}

	// ─── Admin page ────────────────────────────────────────────────────

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission.', 'apollo-calendar' ) );
		}

		$year = isset( $_GET['cal_year'] ) ? absint( $_GET['cal_year'] ) : (int) gmdate( 'Y' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$data = HolidayModel::get_all_admin( $year );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Apollo Calendar — Holidays', 'apollo-calendar' ); ?></h1>

			<?php
			// Feedback notices.
			if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				echo '<div class="notice notice-success is-dismissible"><p>';
				esc_html_e( 'Holiday saved.', 'apollo-calendar' );
				echo '</p></div>';
			}
			if ( isset( $_GET['deleted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				echo '<div class="notice notice-success is-dismissible"><p>';
				esc_html_e( 'Holiday deleted.', 'apollo-calendar' );
				echo '</p></div>';
			}
			?>

			<!-- Year filter -->
			<form method="get" style="margin-bottom:16px">
				<input type="hidden" name="page" value="apollo-calendar-holidays">
				<label for="cal_year"><?php esc_html_e( 'Year:', 'apollo-calendar' ); ?></label>
				<input type="number" id="cal_year" name="cal_year" value="<?php echo esc_attr( (string) $year ); ?>" min="2020" max="2099" style="width:90px">
				<?php submit_button( __( 'Filter', 'apollo-calendar' ), 'secondary', '', false ); ?>
				&nbsp;
				<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=apollo_calendar_seed_year&year=' . $year . '&_wpnonce=' . wp_create_nonce( 'apollo_calendar_seed_year' ) ) ); ?>" class="button">
					<?php esc_html_e( 'Re-seed this year', 'apollo-calendar' ); ?>
				</a>
			</form>

			<!-- Add / Edit form -->
			<h2><?php esc_html_e( 'Add Holiday', 'apollo-calendar' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'apollo_calendar_save_holiday', 'apollo_calendar_holiday_nonce' ); ?>
				<input type="hidden" name="action" value="apollo_calendar_save_holiday">
				<input type="hidden" name="holiday_id" value="0">
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="holiday_date"><?php esc_html_e( 'Date', 'apollo-calendar' ); ?></label></th>
						<td><input type="date" id="holiday_date" name="holiday_date" required class="regular-text"></td>
					</tr>
					<tr>
						<th><label for="holiday_title"><?php esc_html_e( 'Title', 'apollo-calendar' ); ?></label></th>
						<td><input type="text" id="holiday_title" name="holiday_title" required class="regular-text" maxlength="255"></td>
					</tr>
					<tr>
						<th><label for="holiday_scope"><?php esc_html_e( 'Scope', 'apollo-calendar' ); ?></label></th>
						<td>
							<select id="holiday_scope" name="holiday_scope">
								<option value="national"><?php esc_html_e( 'National', 'apollo-calendar' ); ?></option>
								<option value="state"><?php esc_html_e( 'State', 'apollo-calendar' ); ?></option>
								<option value="municipal"><?php esc_html_e( 'Municipal', 'apollo-calendar' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="holiday_region"><?php esc_html_e( 'Region', 'apollo-calendar' ); ?></label></th>
						<td><input type="text" id="holiday_region" name="holiday_region" class="regular-text" maxlength="50" placeholder="e.g. RJ"></td>
					</tr>
				</table>
				<?php submit_button( __( 'Add Holiday', 'apollo-calendar' ) ); ?>
			</form>

			<hr>
			<h2><?php echo esc_html( sprintf( __( 'Holidays for %d', 'apollo-calendar' ), $year ) ); ?></h2>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'apollo-calendar' ); ?></th>
						<th><?php esc_html_e( 'Title', 'apollo-calendar' ); ?></th>
						<th><?php esc_html_e( 'Scope', 'apollo-calendar' ); ?></th>
						<th><?php esc_html_e( 'Region', 'apollo-calendar' ); ?></th>
						<th><?php esc_html_e( 'Source', 'apollo-calendar' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'apollo-calendar' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $data['rows'] ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No holidays found.', 'apollo-calendar' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $data['rows'] as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['holiday_date'] ); ?></td>
								<td><?php echo esc_html( $row['title'] ); ?></td>
								<td><?php echo esc_html( $row['scope'] ); ?></td>
								<td><?php echo esc_html( $row['region'] ); ?></td>
								<td><?php echo esc_html( $row['source'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=apollo_calendar_delete_holiday&holiday_id=' . (int) $row['id'] . '&cal_year=' . $year . '&_wpnonce=' . wp_create_nonce( 'apollo_calendar_delete_holiday_' . (int) $row['id'] ) ) ); ?>"
									   onclick="return confirm('<?php esc_attr_e( 'Delete this holiday?', 'apollo-calendar' ); ?>')">
										<?php esc_html_e( 'Delete', 'apollo-calendar' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	// ─── Form handlers ────────────────────────────────────────────────

	public function handle_save(): void {
		check_admin_referer( 'apollo_calendar_save_holiday', 'apollo_calendar_holiday_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission.', 'apollo-calendar' ) );
		}

		$date   = sanitize_text_field( wp_unslash( $_POST['holiday_date']   ?? '' ) );
		$title  = sanitize_text_field( wp_unslash( $_POST['holiday_title']  ?? '' ) );
		$scope  = sanitize_key( wp_unslash( $_POST['holiday_scope']  ?? 'national' ) );
		$region = sanitize_text_field( wp_unslash( $_POST['holiday_region'] ?? '' ) );
		$year   = $date ? (int) substr( $date, 0, 4 ) : (int) gmdate( 'Y' );

		HolidayModel::upsert( array(
			'holiday_date' => $date,
			'title'        => $title,
			'scope'        => $scope,
			'region'       => $region,
			'recurring'    => 0,
			'year'         => $year,
			'source'       => 'admin',
		) );

		wp_safe_redirect( add_query_arg( array(
			'page'     => 'apollo-calendar-holidays',
			'cal_year' => $year,
			'updated'  => '1',
		), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function handle_delete(): void {
		$id = absint( $_GET['holiday_id'] ?? 0 );
		check_admin_referer( 'apollo_calendar_delete_holiday_' . $id );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No permission.', 'apollo-calendar' ) );
		}

		HolidayModel::delete_admin( $id );

		$year = absint( $_GET['cal_year'] ?? gmdate( 'Y' ) );
		wp_safe_redirect( add_query_arg( array(
			'page'     => 'apollo-calendar-holidays',
			'cal_year' => $year,
			'deleted'  => '1',
		), admin_url( 'admin.php' ) ) );
		exit;
	}

	// ─── Cron ──────────────────────────────────────────────────────────

	public function add_yearly_schedule( array $schedules ): array {
		if ( ! isset( $schedules['yearly'] ) ) {
			$schedules['yearly'] = array(
				'interval' => YEAR_IN_SECONDS,
				'display'  => __( 'Once a Year', 'apollo-calendar' ),
			);
		}
		return $schedules;
	}

	public function cron_seed(): void {
		$next_year = (int) gmdate( 'Y' ) + 1;
		Seeder::seed_year( $next_year );
	}

	private function schedule_cron(): void {
		if ( ! wp_next_scheduled( 'apollo_calendar_seed_holidays' ) ) {
			wp_schedule_event( strtotime( 'first day of January next year' ), 'yearly', 'apollo_calendar_seed_holidays' );
		}
	}
}
