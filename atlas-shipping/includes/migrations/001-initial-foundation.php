<?php
namespace AtlasShipping\Migrations;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Migration 001: create the original ATLAS Shipping activity table.
 *
 * The callback is intentionally idempotent so interrupted installations can
 * safely retry it without changing the 0.1.0 schema.
 */
return array(
    'id'      => '001_initial_foundation',
    'version' => '0.1.0',
    'run'     => static function () {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table           = $wpdb->prefix . 'atlas_shipping_activity';
        $charset_collate = $wpdb->get_charset_collate();
        $sql             = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            object_type varchar(64) NOT NULL DEFAULT '',
            object_id bigint(20) unsigned NULL,
            activity_type varchar(100) NOT NULL,
            actor_type varchar(64) NOT NULL DEFAULT 'system',
            actor_id bigint(20) unsigned NULL,
            summary text NOT NULL,
            metadata longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY object_lookup (object_type, object_id),
            KEY activity_type (activity_type),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta( $sql );

        $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $found !== $table ) {
            return new \WP_Error(
                'atlas_shipping_migration_failed',
                __( 'The ATLAS Shipping activity table could not be confirmed after migration.', 'atlas-shipping' )
            );
        }

        return true;
    },
);
