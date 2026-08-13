<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array(
    'id' => '008_shipper_responses',
    'version' => '0.2.1',
    'run' => static function() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = $wpdb->prefix . 'atlas_shipping_shipper_responses';
        $requests = $wpdb->prefix . 'atlas_shipping_requests';
        $collate = $wpdb->get_charset_collate();
        dbDelta( "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            request_id bigint(20) unsigned NOT NULL,
            coordinator_identity_id bigint(20) unsigned NOT NULL,
            response_status varchar(20) NOT NULL DEFAULT 'draft',
            carrier_name varchar(200) NULL,
            carrier_not_provided tinyint(1) NOT NULL DEFAULT 0,
            actual_equipment varchar(80) NULL,
            other_equipment varchar(200) NULL,
            contact_name varchar(200) NULL,
            contact_phone varchar(80) NULL,
            contact_email varchar(254) NULL,
            pickup_window_start datetime NULL,
            pickup_window_end datetime NULL,
            delivery_window_start datetime NULL,
            delivery_window_end datetime NULL,
            freight_cost decimal(12,2) NULL,
            freight_cost_pending tinyint(1) NOT NULL DEFAULT 0,
            currency char(3) NOT NULL DEFAULT 'USD',
            reference_number varchar(200) NULL,
            coordinator_notes text NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            confirmed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY request_id (request_id),
            KEY coordinator_identity_id (coordinator_identity_id),
            KEY response_status (response_status)
        ) $collate;" );
        $scheduled = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM $requests LIKE %s", 'scheduled_at' ) );
        if ( ! $scheduled && false === $wpdb->query( "ALTER TABLE $requests ADD scheduled_at datetime NULL AFTER sent_to_shipper_at" ) ) {
            return new WP_Error( 'atlas_scheduled_column_missing', __( 'The scheduled timestamp could not be installed.', 'atlas-shipping' ) );
        }
        return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table
            ? true : new WP_Error( 'atlas_shipper_response_table_missing', __( 'The shipper response table could not be installed.', 'atlas-shipping' ) );
    },
);
