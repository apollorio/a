<?php
/**
 * Minhas Comunas / Meus Núcleos — screen layout (PHASES 009 + 010).
 *
 * Shared by both screens; the caller sets $gmi_cfg before requiring this file:
 *   type        'comuna' | 'nucleo'   — which rows to read
 *   manage_only bool                  — admin/moderator/owner scope only
 *   kicker/title/icon/empty_*         — copy for this entry point
 *   create_url/create_label           — primary action, or '' for none
 *
 * All values come from apollo_grp_mine_list() (parts/mine/data.php) — real
 * rows from the apollo_groups tables, membership-scoped to the viewer.
 *
 * @package Apollo\Groups
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/data.php';

$gmi_cfg = isset( $gmi_cfg ) && is_array( $gmi_cfg ) ? $gmi_cfg : array();
$gmi_cfg = wp_parse_args(
	$gmi_cfg,
	array(
		'type'         => 'comuna',
		'manage_only'  => false,
		'kicker'       => '',
		'title'        => '',
		'icon'         => 'ri-group-3-fill',
		'notice'       => '',
		'empty_icon'   => 'ri-group-3-line',
		'empty_text'   => '',
		'create_url'   => '',
		'create_label' => '',
	)
);

$gmi_rows = apollo_grp_mine_list( (string) $gmi_cfg['type'], (bool) $gmi_cfg['manage_only'] );

$gmi_owned   = 0;
$gmi_members = 0;
foreach ( $gmi_rows as $gmi_r ) {
	if ( ! empty( $gmi_r['is_owner'] ) ) {
		$gmi_owned++;
	}
	$gmi_members += (int) $gmi_r['members'];
}
?>
<div class="gmi-screen">

	<div class="gmi-hero">
		<?php if ( $gmi_cfg['kicker'] ) : ?>
			<p class="gmi-kicker"><?php echo esc_html( $gmi_cfg['kicker'] ); ?></p>
		<?php endif; ?>
		<h1><?php echo esc_html( $gmi_cfg['title'] ); ?></h1>
		<div class="gmi-hero-kpis">
			<div class="gmi-hero-kpi"><strong><?php echo esc_html( (string) count( $gmi_rows ) ); ?></strong><span><?php esc_html_e( 'no total', 'apollo-groups' ); ?></span></div>
			<div class="gmi-hero-kpi"><strong><?php echo esc_html( (string) $gmi_owned ); ?></strong><span><?php esc_html_e( 'que eu fundei', 'apollo-groups' ); ?></span></div>
			<div class="gmi-hero-kpi"><strong><?php echo esc_html( (string) $gmi_members ); ?></strong><span><?php esc_html_e( 'pessoas reunidas', 'apollo-groups' ); ?></span></div>
		</div>
		<?php if ( $gmi_cfg['create_url'] ) : ?>
			<div class="gmi-hero-actions">
				<a class="btn btn-primary" href="<?php echo esc_url( $gmi_cfg['create_url'] ); ?>"><i class="ri-add-line"></i> <?php echo esc_html( $gmi_cfg['create_label'] ); ?></a>
			</div>
		<?php endif; ?>
		<?php if ( $gmi_cfg['notice'] ) : ?>
			<p class="gmi-notice">
				<?php echo esc_html( $gmi_cfg['notice'] ); ?>
				<button type="button" class="gmi-notice-link" data-apollo-suporte><?php esc_html_e( 'Suporte', 'apollo-groups' ); ?></button>.
			</p>
		<?php endif; ?>
	</div>

	<?php if ( empty( $gmi_rows ) ) : ?>
		<div class="gmi-empty">
			<i class="<?php echo esc_attr( $gmi_cfg['empty_icon'] ); ?>" aria-hidden="true"></i>
			<p><?php echo esc_html( $gmi_cfg['empty_text'] ); ?></p>
			<?php if ( $gmi_cfg['create_url'] ) : ?>
				<a class="btn btn-primary" href="<?php echo esc_url( $gmi_cfg['create_url'] ); ?>"><?php echo esc_html( $gmi_cfg['create_label'] ); ?></a>
			<?php endif; ?>
		</div>
	<?php else : ?>
		<div class="gmi-grid">
			<?php foreach ( $gmi_rows as $gmi_row ) : ?>
				<a class="gmi-card" href="<?php echo esc_url( $gmi_row['url'] ); ?>">
					<div class="gmi-cover">
						<div class="gmi-badges">
							<span class="gmi-badge<?php echo ! empty( $gmi_row['is_owner'] ) ? ' is-owner' : ''; ?>"><?php echo esc_html( apollo_grp_mine_role_label( (string) $gmi_row['role'] ) ); ?></span>
							<?php if ( 'public' !== $gmi_row['privacy'] ) : ?>
								<span class="gmi-badge"><i class="ri-lock-2-line" aria-hidden="true"></i><?php echo esc_html( apollo_grp_mine_privacy_label( (string) $gmi_row['privacy'] ) ); ?></span>
							<?php endif; ?>
						</div>
						<?php if ( $gmi_row['cover'] ) : ?>
							<img src="<?php echo esc_url( $gmi_row['cover'] ); ?>" alt="" loading="lazy" decoding="async">
						<?php else : ?>
							<span class="gmi-cover-empty"><i class="<?php echo esc_attr( $gmi_cfg['icon'] ); ?>" aria-hidden="true"></i></span>
						<?php endif; ?>
					</div>
					<div class="gmi-body">
						<h2><?php echo esc_html( $gmi_row['name'] ); ?></h2>
						<?php if ( $gmi_row['desc'] ) : ?>
							<p class="gmi-desc"><?php echo esc_html( wp_strip_all_tags( (string) $gmi_row['desc'] ) ); ?></p>
						<?php endif; ?>
						<div class="gmi-meta">
							<span><i class="ri-user-3-line" aria-hidden="true"></i><?php echo esc_html( (string) $gmi_row['members'] ); ?></span>
							<?php if ( $gmi_row['created'] ) : ?>
								<span><i class="ri-time-line" aria-hidden="true"></i><?php echo esc_html( date_i18n( 'M/Y', strtotime( (string) $gmi_row['created'] ) ) ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

</div>
