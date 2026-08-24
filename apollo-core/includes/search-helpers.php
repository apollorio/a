<?php

/**
 * Apollo Composite Search — Helper Functions
 *
 * Provides reusable search component across all Apollo templates.
 * Options are ALWAYS CPTs or Taxonomies (context-specific).
 *
 * @package Apollo\Core
 * @since   6.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Apollo Composite Search
 *
 * @param array $args {
 *     @type string $context     Context identifier (events, djs, loc, classifieds, groups, users, explore, etc.)
 *     @type array  $options     Array of options [value => label]
 *     @type string $placeholder Input placeholder text
 *     @type string $action      Form action URL (optional)
 *     @type string $method      Form method (default: GET)
 *     @type string $input_name  Input field name (default: s)
 *     @type string $select_name Select field name (default: filter)
 *     @type string $class       Additional CSS classes
 * }
 */
function apollo_composite_search( $args = array() ) {
	$defaults = array(
		'context'     => '',
		'options'     => array(),
		'placeholder' => __( 'Search...', 'apollo-core' ),
		'action'      => '',
		'method'      => 'GET',
		'input_name'  => 's',
		'select_name' => 'filter',
		'class'       => '',
	);

	$args = wp_parse_args( $args, $defaults );

	// Auto-detect options if context provided
	if ( ! empty( $args['context'] ) && empty( $args['options'] ) ) {
		$args['options'] = apollo_get_search_options( $args['context'] );
	}

	// Sanitize
	$context     = esc_attr( $args['context'] );
	$placeholder = esc_attr( $args['placeholder'] );
	$action      = ! empty( $args['action'] ) ? esc_url( $args['action'] ) : '';
	$method      = in_array( strtoupper( $args['method'] ), array( 'GET', 'POST' ) ) ? strtoupper( $args['method'] ) : 'GET';
	$input_name  = esc_attr( $args['input_name'] );
	$select_name = esc_attr( $args['select_name'] );
	$class       = ! empty( $args['class'] ) ? ' ' . esc_attr( $args['class'] ) : '';

	// Get current values
	$current_filter = isset( $_GET[ $select_name ] ) ? sanitize_text_field( $_GET[ $select_name ] ) : '';
	$current_search = isset( $_GET[ $input_name ] ) ? sanitize_text_field( $_GET[ $input_name ] ) : '';

	// Start output
	?>
	<form
		class="apollo-search-composite-form<?php echo $class; ?>"
		<?php echo ! empty( $action ) ? 'action="' . $action . '"' : ''; ?>
		method="<?php echo $method; ?>"
		data-apollo-search-context="<?php echo $context; ?>"
		role="search">
		<div class="apollo-search-composite">
			<!-- Dropdown Selector -->
			<div class="apollo-composite-select-wrap">
				<select
					class="apollo-composite-select"
					name="<?php echo $select_name; ?>"
					aria-label="<?php esc_attr_e( 'Filter type', 'apollo-core' ); ?>">
					<?php foreach ( $args['options'] as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_filter, $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<!-- Text Input -->
			<input
				type="text"
				name="<?php echo esc_attr( $input_name ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				class="apollo-composite-input"
				value="<?php echo esc_attr( $current_search ); ?>"
				aria-label="<?php esc_attr_e( 'Search query', 'apollo-core' ); ?>">

			<!-- Search Button -->
			<button
				type="submit"
				class="apollo-composite-btn"
				aria-label="<?php esc_attr_e( 'Submit search', 'apollo-core' ); ?>">
				<i class="ri-user-search-line" aria-hidden="true"></i>
			</button>
		</div>
	</form>
	<?php
}

/**
 * Get search options based on context
 *
 * Always returns CPTs or Taxonomies relevant to the context.
 *
 * @param string $context Context identifier
 * @return array Options array [value => label]
 */
function apollo_get_search_options( $context ) {
	$options = array();

	switch ( $context ) {
		case 'events':
		case 'event':
			$options = array(
				''               => __( 'All', 'apollo-core' ),
				'event'          => __( 'Events', 'apollo-core' ),
				'event_category' => __( 'Category', 'apollo-core' ),
				'event_type'     => __( 'Type', 'apollo-core' ),
				'event_tag'      => __( 'Tag', 'apollo-core' ),
				'sound'          => __( 'Sound', 'apollo-core' ),
				'season'         => __( 'Season', 'apollo-core' ),
			);
			break;

		case 'djs':
		case 'dj':
			$options = array(
				''      => __( 'All', 'apollo-core' ),
				'dj'    => __( 'DJs', 'apollo-core' ),
				'sound' => __( 'Sound', 'apollo-core' ),
			);
			break;

		case 'locations':
		case 'loc':
			$options = array(
				''         => __( 'All', 'apollo-core' ),
				'loc'      => __( 'Locations', 'apollo-core' ),
				'loc_type' => __( 'Type', 'apollo-core' ),
				'loc_area' => __( 'Area', 'apollo-core' ),
			);
			break;

		case 'classifieds':
		case 'classified':
			$options = array(
				''                  => __( 'All', 'apollo-core' ),
				'classified'        => __( 'Classifieds', 'apollo-core' ),
				'classified_domain' => __( 'Domain', 'apollo-core' ),
				'classified_intent' => __( 'Intent', 'apollo-core' ),
			);
			break;

		case 'groups':
		case 'group':
		case 'comunas':
			$options = array(
				''          => __( 'All', 'apollo-core' ),
				'group'     => __( 'Comunas', 'apollo-core' ),
				'group_tag' => __( 'Tag', 'apollo-core' ),
			);
			break;

		case 'users':
		case 'user':
		case 'profiles':
		case 'radar':
			$options = array(
				''          => __( 'All', 'apollo-core' ),
				'user'      => __( 'Users', 'apollo-core' ),
				'sound'     => __( 'Sound', 'apollo-core' ),
				'user_role' => __( 'Role', 'apollo-core' ),
			);
			break;

		case 'explore':
		case 'social':
		case 'feed':
			$options = array(
				''      => __( 'All', 'apollo-core' ),
				'post'  => __( 'Posts', 'apollo-core' ),
				'event' => __( 'Events', 'apollo-core' ),
				'dj'    => __( 'DJs', 'apollo-core' ),
				'loc'   => __( 'Locations', 'apollo-core' ),
				'user'  => __( 'Users', 'apollo-core' ),
			);
			break;

		case 'docs':
		case 'doc':
			$options = array(
				''           => __( 'All', 'apollo-core' ),
				'doc'        => __( 'Documents', 'apollo-core' ),
				'doc_type'   => __( 'Type', 'apollo-core' ),
				'doc_folder' => __( 'Folder', 'apollo-core' ),
			);
			break;

		case 'suppliers':
		case 'supplier':
			$options = array(
				''                  => __( 'All', 'apollo-core' ),
				'supplier'          => __( 'Suppliers', 'apollo-core' ),
				'supplier_category' => __( 'Category', 'apollo-core' ),
				'supplier_service'  => __( 'Service', 'apollo-core' ),
			);
			break;

		default:
			// Fallback: generic search
			$options = array(
				'' => __( 'All', 'apollo-core' ),
			);
			break;
	}

	return apply_filters( "apollo_search_options_{$context}", $options, $context );
}

/**
 * Enqueue composite search CSS
 *
 * Call this in any template that uses the composite search.
 */
function apollo_enqueue_composite_search() {
	// Main component CSS
	wp_enqueue_style(
		'apollo-composite-search',
		APOLLO_CORE_URL . 'assets/css/composite-search.css',
		array(),
		APOLLO_VERSION
	);

	// Integration CSS (template-specific styling)
	wp_enqueue_style(
		'apollo-composite-search-integration',
		APOLLO_CORE_URL . 'assets/css/composite-search-integration.css',
		array( 'apollo-composite-search' ),
		APOLLO_VERSION
	);
}

