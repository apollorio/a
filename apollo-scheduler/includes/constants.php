<?php
/**
 * Apollo Scheduler constants.
 *
 * @package Apollo\Scheduler
 */

declare(strict_types=1);

namespace Apollo\Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'APOLLO_SCHEDULER_REST_NAMESPACE', 'apollo/v1' );
define( 'APOLLO_SCHEDULER_REST_BASE', 'scheduler' );

define( 'APOLLO_SCHEDULER_CPT_APPOINTMENT', 'appointment' );
define( 'APOLLO_SCHEDULER_CPT_SERVICE', 'service' );
define( 'APOLLO_SCHEDULER_CPT_RESOURCE', 'resource' );

define( 'APOLLO_SCHEDULER_CACHE_GROUP', 'apollo_scheduler' );
define( 'APOLLO_SCHEDULER_CACHE_TTL', 300 );

define(
	'APOLLO_SCHEDULER_USER_META_KEYS',
	array(
		'_apollo_is_agent',
		'_apollo_agent_of_nucleo',
		'_apollo_agent_services',
		'_apollo_agent_rooms',
		'_apollo_working_plan',
	)
);

define(
	'APOLLO_SCHEDULER_APPOINTMENT_META',
	array(
		'_apollo_appointment_start',
		'_apollo_appointment_end',
		'_apollo_appointment_service_id',
		'_apollo_appointment_agent_id',
		'_apollo_appointment_resource_id',
		'_apollo_appointment_nucleo_id',
		'_apollo_appointment_customer_id',
		'_apollo_appointment_status',
		'_apollo_appointment_event_id',
		'_apollo_appointment_payment_status',
		'_apollo_appointment_notes',
	)
);

define(
	'APOLLO_SCHEDULER_SERVICE_META',
	array(
		'_apollo_service_duration',
		'_apollo_service_price',
		'_apollo_service_slot_interval',
		'_apollo_service_agents',
		'_apollo_service_resources',
		'_apollo_service_nucleo_id',
	)
);

define(
	'APOLLO_SCHEDULER_RESOURCE_META',
	array(
		'_apollo_resource_capacity',
		'_apollo_resource_nucleo_id',
		'_apollo_resource_loc_id',
	)
);
