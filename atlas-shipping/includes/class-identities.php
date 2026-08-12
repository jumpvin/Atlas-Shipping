<?php
namespace AtlasShipping;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Identities {
    public static function roles() { return array( 'Administrator', 'Manager', 'Shipping Coordinator', 'Project Manager' ); }
    public static function table() { global $wpdb; return $wpdb->prefix . 'atlas_shipping_identities'; }
    public static function find_by_email( $email ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE email=%s', strtolower( sanitize_email( $email ) ) ) ); }
    public static function find( $id ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id=%d', absint( $id ) ) ); }
    public static function all() { global $wpdb; return $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY full_name ASC, email ASC' ); }

    public static function save( $data, $id = 0 ) {
        global $wpdb;

        $email = strtolower( sanitize_email( $data['email'] ?? '' ) );
        $name  = sanitize_text_field( $data['full_name'] ?? '' );
        $role  = sanitize_text_field( $data['role'] ?? 'Project Manager' );
        if ( ! $email || ! is_email( $email ) || ! $name || ! in_array( $role, self::roles(), true ) ) {
            return new \WP_Error( 'atlas_identity_invalid', __( 'Enter a valid name, email, and role.', 'atlas-shipping' ) );
        }

        $dupe = self::find_by_email( $email );
        if ( $dupe && (int) $dupe->id !== absint( $id ) ) {
            return new \WP_Error( 'atlas_identity_duplicate', __( 'That email address is already approved.', 'atlas-shipping' ) );
        }

        $existing = $id ? self::find( $id ) : null;
        $enabled  = empty( $data['enabled'] ) ? 0 : 1;
        $now      = gmdate( 'Y-m-d H:i:s' );
        $row      = array( 'email' => $email, 'full_name' => $name, 'role' => $role, 'enabled' => $enabled, 'updated_at' => $now );

        if ( $id ) {
            $ok = $wpdb->update( self::table(), $row, array( 'id' => absint( $id ) ), array( '%s', '%s', '%s', '%d', '%s' ), array( '%d' ) );
        } else {
            $row['created_at'] = $now;
            $ok = $wpdb->insert( self::table(), $row, array( '%s', '%s', '%s', '%d', '%s', '%s' ) );
            $id = $wpdb->insert_id;
        }

        if ( false === $ok ) {
            return new \WP_Error( 'atlas_identity_save_failed', __( 'The identity could not be saved.', 'atlas-shipping' ) );
        }

        $id = absint( $id );
        if ( ! $existing ) {
            Migrator::log_activity( 'identity_created', 'An approved identity was created.', array( 'enabled' => $enabled ), 'identity', $id );
        } else {
            Migrator::log_activity( 'identity_updated', 'An approved identity was updated.', array( 'enabled' => $enabled ), 'identity', $id );
            if ( (int) $existing->enabled !== $enabled ) {
                if ( 0 === $enabled ) {
                    $counts = self::revoke_authentication( $id );
                    Migrator::log_activity( 'identity_disabled', 'An approved identity was disabled.', $counts, 'identity', $id );
                } else {
                    Migrator::log_activity( 'identity_enabled', 'An approved identity was enabled.', array( 'reason_code' => 'administrator_action' ), 'identity', $id );
                }
            }
        }

        return $id;
    }

    public static function revoke_authentication( $id ) {
        global $wpdb;
        $id = absint( $id );
        $sessions = Sessions::destroy_for_identity( $id );
        $tokens   = $wpdb->query( $wpdb->prepare(
            'DELETE FROM ' . $wpdb->prefix . 'atlas_shipping_magic_tokens WHERE identity_id=%d AND used_at IS NULL',
            $id
        ) );
        $tokens = false === $tokens ? 0 : (int) $tokens;

        Migrator::log_activity( 'sessions_revoked_for_identity', 'Application authentication records were revoked.', array( 'sessions_revoked' => $sessions, 'tokens_invalidated' => $tokens ), 'identity', $id );
        return array( 'sessions_revoked' => $sessions, 'tokens_invalidated' => $tokens );
    }

    public static function delete( $id ) {
        global $wpdb;
        $id = absint( $id );
        if ( ! self::find( $id ) ) { return false; }

        $counts = self::revoke_authentication( $id );
        // Remove any already-used/expired authentication records as well, avoiding orphans.
        $wpdb->delete( $wpdb->prefix . 'atlas_shipping_magic_tokens', array( 'identity_id' => $id ), array( '%d' ) );
        $deleted = $wpdb->delete( self::table(), array( 'id' => $id ), array( '%d' ) );
        if ( false !== $deleted ) {
            Migrator::log_activity( 'identity_deleted', 'An approved identity was deleted.', $counts, 'identity', $id );
            return true;
        }
        return false;
    }
}
