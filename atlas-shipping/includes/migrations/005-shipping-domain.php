<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array(
 'id' => '005_shipping_domain', 'version' => '0.1.3',
 'run' => static function() {
  global $wpdb;
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  $c = $wpdb->get_charset_collate();
  $requests = $wpdb->prefix . 'atlas_shipping_requests';
  $stops = $wpdb->prefix . 'atlas_shipping_stops';
  $items = $wpdb->prefix . 'atlas_shipping_items';
  $snapshots = $wpdb->prefix . 'atlas_shipping_snapshots';
  dbDelta("CREATE TABLE $requests (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
   public_id varchar(32) NULL,
   owner_identity_id bigint(20) unsigned NOT NULL,
   created_by_identity_id bigint(20) unsigned NOT NULL,
   updated_by_identity_id bigint(20) unsigned NOT NULL,
   status varchar(40) NOT NULL DEFAULT 'draft',
   project_id varchar(100) NOT NULL DEFAULT '', internal_id varchar(100) NULL,
   client varchar(200) NOT NULL DEFAULT '', project_name varchar(200) NOT NULL DEFAULT '',
   requested_ship_date date NULL, required_delivery_date date NULL,
   delivery_date_firmness varchar(30) NOT NULL DEFAULT 'unknown', client_requested tinyint(1) NOT NULL DEFAULT 0,
   notes longtext NULL, freight_cost decimal(14,2) NULL,
   row_version bigint(20) unsigned NOT NULL DEFAULT 1,
   created_at datetime NOT NULL, updated_at datetime NOT NULL,
   submitted_at datetime NULL, sent_to_shipper_at datetime NULL, completed_at datetime NULL, archived_at datetime NULL,
   PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY owner_identity (owner_identity_id), KEY status (status),
   KEY requested_ship_date (requested_ship_date), KEY required_delivery_date (required_delivery_date),
   KEY created_at (created_at), KEY updated_at (updated_at), KEY archived_at (archived_at)
  ) $c;");
  dbDelta("CREATE TABLE $stops (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT, request_id bigint(20) unsigned NOT NULL,
   sequence_no int unsigned NOT NULL, stop_type varchar(20) NOT NULL,
   site_name varchar(200) NOT NULL DEFAULT '', address_line_1 varchar(200) NOT NULL DEFAULT '', address_line_2 varchar(200) NULL,
   city varchar(100) NOT NULL DEFAULT '', region varchar(100) NOT NULL DEFAULT '', postal_code varchar(30) NOT NULL DEFAULT '', country varchar(2) NOT NULL DEFAULT 'US',
   contact_name varchar(200) NULL, contact_phone varchar(50) NULL, contact_email varchar(254) NULL,
   window_start datetime NULL, window_end datetime NULL, appointment_required tinyint(1) NOT NULL DEFAULT 0,
   handling_responsibility varchar(100) NULL, dock_available tinyint(1) NOT NULL DEFAULT 0, forklift_available tinyint(1) NOT NULL DEFAULT 0,
   equipment_notes text NULL, instructions text NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL,
   PRIMARY KEY (id), UNIQUE KEY request_sequence (request_id,sequence_no), KEY request_id (request_id)
  ) $c;");
  dbDelta("CREATE TABLE $items (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT, request_id bigint(20) unsigned NOT NULL,
   sequence_no int unsigned NOT NULL, quantity decimal(12,3) NOT NULL, description varchar(255) NOT NULL,
   length decimal(12,3) NULL, width decimal(12,3) NULL, height decimal(12,3) NULL, dimension_unit varchar(10) NULL,
   weight decimal(14,3) NULL, weight_basis varchar(20) NULL, weight_unit varchar(10) NULL,
   packaging_type varchar(60) NULL, stackable tinyint(1) NOT NULL DEFAULT 0, fork_pockets tinyint(1) NOT NULL DEFAULT 0,
   weather_sensitive tinyint(1) NOT NULL DEFAULT 0, special_handling text NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL,
   PRIMARY KEY (id), UNIQUE KEY request_sequence (request_id,sequence_no), KEY request_id (request_id)
  ) $c;");
  dbDelta("CREATE TABLE $snapshots (
   id bigint(20) unsigned NOT NULL AUTO_INCREMENT, request_id bigint(20) unsigned NOT NULL,
   snapshot_no int unsigned NOT NULL, snapshot_type varchar(40) NOT NULL, created_by_identity_id bigint(20) unsigned NOT NULL,
   parent_snapshot_id bigint(20) unsigned NULL, reason text NULL, content longtext NOT NULL, content_hash char(64) NOT NULL, created_at datetime NOT NULL,
   PRIMARY KEY (id), UNIQUE KEY request_snapshot (request_id,snapshot_no), KEY request_id (request_id), KEY parent_snapshot_id (parent_snapshot_id), KEY content_hash (content_hash)
  ) $c;");
  foreach ( array( $requests, $stops, $items, $snapshots ) as $table ) {
   if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
    return new WP_Error( 'atlas_shipping_domain_table_missing', __( 'A shipping-domain table could not be verified.', 'atlas-shipping' ) );
   }
  }
  return true;
 },
);
