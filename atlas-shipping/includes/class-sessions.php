<?php
namespace AtlasShipping;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Sessions {
    const COOKIE = 'atlas_shipping_session';
    const ACTIVITY_INTERVAL = 300;

    public static function lifetime() {
        return (int) apply_filters( 'atlas_shipping_session_lifetime', 30 * DAY_IN_SECONDS );
    }

    public static function activity_interval() {
        return (int) apply_filters( 'atlas_shipping_session_activity_interval', self::ACTIVITY_INTERVAL );
    }

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'atlas_shipping_sessions';
    }

    public static function create( $identity_id ) {
        global $wpdb;

        try {
            $raw = bin2hex( random_bytes( 32 ) );
        } catch ( \Throwable $error ) {
            return new \WP_Error( 'atlas_shipping_session_random_failed', __( 'The application session could not be created.', 'atlas-shipping' ) );
        }

        $hash    = hash( 'sha256', $raw );
        $now     = time();
        $expires = $now + self::lifetime();
        $ok      = $wpdb->insert(
            self::table(),
            array(
                'identity_id'       => absint( $identity_id ),
                'session_token_hash'=> $hash,
                'created_at'        => gmdate( 'Y-m-d H:i:s', $now ),
                'expires_at'        => gmdate( 'Y-m-d H:i:s', $expires ),
                'last_activity_at'  => gmdate( 'Y-m-d H:i:s', $now ),
                'ip'                => self::ip(),
                'user_agent'        => substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $ok || (int) $wpdb->insert_id < 1 ) {
            return new \WP_Error( 'atlas_shipping_session_insert_failed', __( 'The application session could not be created.', 'atlas-shipping' ) );
        }

        self::set_cookie( $raw, $expires );
        return true;
    }

    public static function current( $update_activity = true ) {
        global $wpdb;

        $raw = $_COOKIE[ self::COOKIE ] ?? '';
        if ( ! is_string( $raw ) || strlen( $raw ) < 40 ) {
            return null;
        }

        $hash = hash( 'sha256', $raw );
        $row  = $wpdb->get_row( $wpdb->prepare(
            'SELECT s.*, i.email, i.full_name, i.role, i.enabled FROM ' . self::table() . ' s JOIN ' . $wpdb->prefix . 'atlas_shipping_identities i ON i.id=s.identity_id WHERE s.session_token_hash=%s',
            $hash
        ) );

        if ( ! $row || ! (int) $row->enabled || strtotime( $row->expires_at ) <= time() ) {
            self::destroy_current();
            return null;
        }

        if ( $update_activity && strtotime( $row->last_activity_at ) <= time() - self::activity_interval() ) {
            $now = gmdate( 'Y-m-d H:i:s' );
            $wpdb->update( self::table(), array( 'last_activity_at' => $now ), array( 'id' => (int) $row->id ), array( '%s' ), array( '%d' ) );
            $row->last_activity_at = $now;
        }

        return $row;
    }

    public static function destroy_current() {
        global $wpdb;
        $raw = $_COOKIE[ self::COOKIE ] ?? '';
        if ( is_string( $raw ) && '' !== $raw ) {
            $wpdb->delete( self::table(), array( 'session_token_hash' => hash( 'sha256', $raw ) ), array( '%s' ) );
        }
        self::set_cookie( '', time() - 3600 );
        unset( $_COOKIE[ self::COOKIE ] );
    }

    public static function destroy_for_identity( $id ) {
        global $wpdb;
        $deleted = $wpdb->delete( self::table(), array( 'identity_id' => absint( $id ) ), array( '%d' ) );
        return false === $deleted ? 0 : (int) $deleted;
    }

    public static function count_active() {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE expires_at>%s', gmdate( 'Y-m-d H:i:s' ) ) );
    }

    private static function set_cookie( $value, $expires ) {
        setcookie( self::COOKIE, $value, array(
            'expires'  => $expires,
            'path'     => COOKIEPATH ?: '/',
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ) );
    }

    public static function ip() {
        return substr( sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ), 0, 64 );
    }
}
