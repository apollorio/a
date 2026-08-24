<?php

/**
 * Events Section — Custom Meta Data Fields
 *
 * Page ID: page-evt-custom
 * Up to 10 custom fields with accordions
 *
 * @package Apollo\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$num_fields = intval( $apollo['cf_num_fields'] ?? 3 );

$content_types = array(
	'text'      => __( 'Single line Text', 'apollo-admin' ),
	'editor'    => __( 'Multiple lines (Editor)', 'apollo-admin' ),
	'trumbowig' => __( 'Multiple lines (Trumbowig)', 'apollo-admin' ),
	'textarea'  => __( 'Multiple lines (Text Field)', 'apollo-admin' ),
	'button'    => __( 'Button', 'apollo-admin' ),
);

$visibility_options = array(
	'everyone' => __( 'Everyone', 'apollo-admin' ),
	'admin'    => __( 'Admin Only', 'apollo-admin' ),
	'logged'   => __( 'Logged-in Users Only', 'apollo-admin' ),
);
?>
<div class="page" id="page-evt-custom">
	<div class="panel">
		<div class="panel-header"><i class="ri-database-2-line"></i> <?php esc_html_e( 'Custom Meta Data Fields', 'apollo-admin' ); ?> <span class="badge"><?php esc_html_e( 'Up to 10', 'apollo-admin' ); ?></span></div>
		<div class="panel-body">
			<p style="color:var(--gray-1);margin-bottom:16px;line-height:1.6">
				<?php esc_html_e( 'Once activated, go to EventCard settings to rearrange order. Custom field types Textarea is not supported for EventTop.', 'apollo-admin' ); ?>
				<?php esc_html_e( 'Dynamic values:', 'apollo-admin' ); ?>
				<code style="background:var(--card);padding:2px 6px;border-radius:3px;font-family:var(--ff-mono);font-size:11px">{startdate} {enddate} {eventid} {startunix} {endunix}</code>
			</p>

			<div class="field" style="margin-bottom:20px">
				<label class="field-label"><?php esc_html_e( 'Number of Custom Fields', 'apollo-admin' ); ?></label>
				<select class="select" style="max-width:200px" name="apollo[cf_num_fields]">
					<?php for ( $i = 1; $i <= 10; $i++ ) : ?>
						<option value="<?php echo $i; ?>" <?php selected( $num_fields, $i ); ?>><?php echo $i; ?></option>
					<?php endfor; ?>
				</select>
			</div>

			<?php
			// Pre-configured fields data
			$preset_fields = array(
				1 => array(
					'name'   => 'Ticket Link',
					'type'   => 'button',
					'icon'   => 'ri-ticket-line',
					'active' => true,
				),
				2 => array(
					'name'   => 'SoundCloud Embed',
					'type'   => 'text',
					'icon'   => 'ri-soundcloud-line',
					'active' => true,
				),
				3 => array(
					'name'   => '',
					'type'   => 'text',
					'icon'   => '',
					'active' => false,
				),
			);

			for ( $n = 1; $n <= $num_fields; $n++ ) :
				$preset      = $preset_fields[ $n ] ?? array();
				$field_name  = $apollo[ "cf_field_{$n}_name" ] ?? ( $preset['name'] ?? '' );
				$field_type  = $apollo[ "cf_field_{$n}_type" ] ?? ( $preset['type'] ?? 'text' );
				$field_icon  = $apollo[ "cf_field_{$n}_icon" ] ?? ( $preset['icon'] ?? '' );
				$field_vis   = $apollo[ "cf_field_{$n}_vis" ] ?? ( $preset['visibility'] ?? 'everyone' );
				$is_active   = ! empty( $apollo[ "cf_field_{$n}_active" ] ) || ! empty( $field_name );
				$is_open     = ( $n === 1 ) ? ' open' : '';
				$icon_class  = $is_active ? 'ri-checkbox-circle-fill' : 'ri-checkbox-blank-circle-line';
				$icon_color  = $is_active ? 'var(--green)' : 'var(--gray-10)';
				$label_extra = ! $is_active ? ' (' . esc_html__( 'Inactive', 'apollo-admin' ) . ')' : '';
				?>
				<div class="accordion<?php echo esc_attr( $is_open ); ?>">
					<div class="accordion-head" onclick="this.parentElement.classList.toggle('open')">
						<i class="<?php echo esc_attr( $icon_class ); ?>" style="color:<?php echo esc_attr( $icon_color ); ?>"></i>
						<?php
						printf( esc_html__( 'Custom Field #%d', 'apollo-admin' ), $n );
						echo esc_html( $label_extra );
						?>
						<i class="ri-arrow-down-s-line arrow"></i>
					</div>
					<div class="accordion-body">
						<?php if ( ! $is_active ) : ?>
							<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[cf_field_<?php echo $n; ?>_active]" value="1" <?php checked( $apollo[ "cf_field_{$n}_active" ] ?? false ); ?>><span class="switch-track"></span></label>
								<div class="toggle-text"><span class="toggle-title"><?php printf( esc_html__( 'Activate Additional Field #%d', 'apollo-admin' ), $n ); ?></span></div>
							</div>
						<?php else : ?>
							<div class="form-grid">
								<div class="field">
									<label class="field-label"><?php esc_html_e( 'Field Name', 'apollo-admin' ); ?> <span class="required">*</span></label>
									<input type="text" class="apollo-input input" name="apollo[cf_field_<?php echo $n; ?>_name]" value="<?php echo esc_attr( $field_name ); ?>" placeholder="<?php esc_attr_e( 'Field name', 'apollo-admin' ); ?>">
								</div>
								<div class="field">
									<label class="field-label"><?php esc_html_e( 'Content Type', 'apollo-admin' ); ?></label>
									<select class="select" name="apollo[cf_field_<?php echo $n; ?>_type]">
										<?php foreach ( $content_types as $val => $label ) : ?>
											<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $field_type, $val ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="field">
									<label class="field-label"><?php esc_html_e( 'Icon', 'apollo-admin' ); ?></label>
									<input type="text" class="apollo-input input" name="apollo[cf_field_<?php echo $n; ?>_icon]" value="<?php echo esc_attr( $field_icon ); ?>" placeholder="ri-icon-name">
								</div>
								<div class="field">
									<label class="field-label"><?php esc_html_e( 'Visibility', 'apollo-admin' ); ?></label>
									<select class="select" name="apollo[cf_field_<?php echo $n; ?>_vis]">
										<?php foreach ( $visibility_options as $val => $label ) : ?>
											<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $field_vis, $val ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[cf_field_<?php echo $n; ?>_hide]" value="1" <?php checked( $apollo[ "cf_field_{$n}_hide" ] ?? false ); ?>><span class="switch-track"></span></label>
								<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Hide this field from front-end calendar', 'apollo-admin' ); ?></span></div>
							</div>
							<div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[cf_field_<?php echo $n; ?>_login_msg]" value="1" <?php checked( $apollo[ "cf_field_{$n}_login_msg" ] ?? false ); ?>><span class="switch-track"></span></label>
								<div class="toggle-text"><span class="toggle-title"><?php esc_html_e( 'Show login required message for logged-in visibility', 'apollo-admin' ); ?></span></div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			<?php endfor; ?>

		</div>
	</div>
</div>