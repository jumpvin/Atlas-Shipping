<?php
namespace AtlasShipping;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Authentication {
    const TOKEN_LIFETIME = 900;
    const OPTION_LAST_AUTH_ERROR = 'atlas_shipping_last_auth_error';
    const OPTION_LAST_AUTH_ERROR_AT = 'atlas_shipping_last_auth_error_timestamp';

    public function register() {
        add_action( 'template_redirect', array( $this, 'consume_or_logout' ), 1 );
    }

    public function consume_or_logout() {
        if ( isset( $_GET['atlas_shipping_magic'] ) ) {
            $this->consume( sanitize_text_field( wp_unslash( $_GET['atlas_shipping_magic'] ) ) );
        }

        if ( isset( $_POST['atlas_shipping_logout'] )
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'atlas_shipping_logout' ) ) {
            $identity = Sessions::current( false );
            Sessions::destroy_current();
            Migrator::log_activity(
                'logout',
                'Application session ended.',
                array( 'reason_code' => 'user_logout' ),
                $identity ? 'identity' : 'authentication',
                $identity ? (int) $identity->identity_id : null
            );
            wp_safe_redirect( $this->app_url( array( 'atlas_signed_out' => 1 ) ) );
            exit;
        }
    }

    public function request_link( $email ) {
        global $wpdb;

        $normalized_email = strtolower( sanitize_email( $email ) );
        $source_key       = 'atlas_auth_rate_ip_' . hash( 'sha256', Sessions::ip() );
        $email_key        = 'atlas_auth_rate_email_' . hash( 'sha256', $normalized_email );

        if ( get_transient( $source_key ) || get_transient( $email_key ) ) {
            return null;
        }

        set_transient( $source_key, 1, 30 );
        set_transient( $email_key, 1, 30 );

        $identity = Identities::find_by_email( $normalized_email );
        if ( ! $identity || ! (int) $identity->enabled ) {
            return null;
        }

        Migrator::log_activity(
            'login_link_requested',
            'A passwordless login link was requested.',
            array( 'reason_code' => 'approved_identity_request' ),
            'identity',
            (int) $identity->id
        );

        try {
            $selector  = bin2hex( random_bytes( 9 ) );
            $validator = bin2hex( random_bytes( 32 ) );
        } catch ( \Throwable $error ) {
            $this->record_auth_error( 'token_random_generation_failed' );
            Migrator::log_activity( 'login_link_generation_failed', 'A login link could not be created.', array( 'reason_code' => 'random_generation_failed' ), 'identity', (int) $identity->id );
            return new \WP_Error( 'atlas_shipping_token_generation_failed', __( 'A testing link could not be created.', 'atlas-shipping' ) );
        }

        $now        = time();
        $expires_at = gmdate( 'Y-m-d H:i:s', $now + self::TOKEN_LIFETIME );
        $inserted   = $wpdb->insert(
            $wpdb->prefix . 'atlas_shipping_magic_tokens',
            array(
                'identity_id'       => (int) $identity->id,
                'selector'          => $selector,
                'hashed_validator'  => password_hash( $validator, PASSWORD_DEFAULT ),
                'expires_at'        => $expires_at,
                'created_at'        => gmdate( 'Y-m-d H:i:s', $now ),
                'request_ip'        => Sessions::ip(),
                'request_user_agent'=> substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        $insert_id = (int) $wpdb->insert_id;
        $stored    = $inserted !== false && $insert_id > 0
            ? $wpdb->get_row( $wpdb->prepare(
                'SELECT id, identity_id, expires_at FROM ' . $wpdb->prefix . 'atlas_shipping_magic_tokens WHERE id=%d AND selector=%s',
                $insert_id,
                $selector
            ) )
            : null;

        if ( ! $stored
            || (int) $stored->identity_id !== (int) $identity->id
            || (string) $stored->expires_at !== $expires_at ) {
            if ( $insert_id > 0 ) {
                $wpdb->delete( $wpdb->prefix . 'atlas_shipping_magic_tokens', array( 'id' => $insert_id ), array( '%d' ) );
            }
            $this->record_auth_error( 'token_insert_verification_failed' );
            Migrator::log_activity( 'login_link_generation_failed', 'A login link could not be created.', array( 'reason_code' => 'token_storage_failed' ), 'identity', (int) $identity->id );
            return new \WP_Error( 'atlas_shipping_token_storage_failed', __( 'A testing link could not be created.', 'atlas-shipping' ) );
        }

        Migrator::log_activity( 'login_link_created', 'A one-time login link was created.', array( 'reason_code' => 'token_stored' ), 'identity', (int) $identity->id );
        return $this->app_url( array( 'atlas_shipping_magic' => $selector . '.' . $validator ) );
    }

    private function consume( $token ) {
        global $wpdb;

        $parts = explode( '.', $token, 2 );
        if ( count( $parts ) !== 2 ) {
            $this->reject( 'malformed_token' );
        }

        list( $selector, $validator ) = $parts;
        $table = $wpdb->prefix . 'atlas_shipping_magic_tokens';
        $row   = $wpdb->get_row( $wpdb->prepare(
            'SELECT t.*, i.enabled FROM ' . $table . ' t JOIN ' . $wpdb->prefix . 'atlas_shipping_identities i ON i.id=t.identity_id WHERE t.selector=%s',
            $selector
        ) );

        if ( ! $row || $row->used_at || ! (int) $row->enabled || strtotime( $row->expires_at ) <= time() || ! password_verify( $validator, $row->hashed_validator ) ) {
            $this->reject( 'token_validation_failed', $row ? (int) $row->identity_id : null );
        }

        $claimed_at = gmdate( 'Y-m-d H:i:s' );
        // used_at is intentionally the atomic claim marker. A conditional UPDATE
        // guarantees that concurrent requests cannot both claim the same token.
        $claimed = $wpdb->query( $wpdb->prepare(
            'UPDATE ' . $table . ' SET used_at=%s WHERE id=%d AND identity_id=%d AND used_at IS NULL AND expires_at>%s',
            $claimed_at,
            (int) $row->id,
            (int) $row->identity_id,
            $claimed_at
        ) );

        if ( 1 !== (int) $claimed ) {
            $this->reject( 'token_claim_failed', (int) $row->identity_id );
        }

        $session = Sessions::create( (int) $row->identity_id );
        if ( is_wp_error( $session ) || true !== $session ) {
            // Security takes priority over retry convenience. The token remains
            // consumed, no cookie is set, and the protected failure is recorded.
            $this->record_auth_error( 'session_creation_failed_after_claim' );
            Migrator::log_activity( 'session_creation_failed', 'An application session could not be created.', array( 'reason_code' => 'database_insert_failed' ), 'identity', (int) $row->identity_id );
            $this->invalid( 'atlas_signin_failed' );
        }

        $wpdb->update(
            Identities::table(),
            array( 'last_login_at' => gmdate( 'Y-m-d H:i:s' ), 'updated_at' => gmdate( 'Y-m-d H:i:s' ) ),
            array( 'id' => (int) $row->identity_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        delete_option( self::OPTION_LAST_AUTH_ERROR );
        delete_option( self::OPTION_LAST_AUTH_ERROR_AT );
        Migrator::log_activity( 'login_succeeded', 'Passwordless authentication succeeded.', array( 'reason_code' => 'magic_link_claimed' ), 'identity', (int) $row->identity_id );
        wp_safe_redirect( $this->app_url() );
        exit;
    }

    private function reject( $reason_code, $identity_id = null ) {
        // Restrain failure logging so automated abuse cannot flood activity records.
        $log_key = 'atlas_auth_reject_log_' . hash( 'sha256', Sessions::ip() );
        if ( ! get_transient( $log_key ) ) {
            set_transient( $log_key, 1, MINUTE_IN_SECONDS );
            Migrator::log_activity( 'magic_token_rejected', 'A magic-link authentication attempt was rejected.', array( 'reason_code' => sanitize_key( $reason_code ) ), 'identity', $identity_id );
        }
        $this->invalid();
    }

    private function record_auth_error( $reason_code ) {
        update_option( self::OPTION_LAST_AUTH_ERROR, sanitize_key( $reason_code ), false );
        update_option( self::OPTION_LAST_AUTH_ERROR_AT, gmdate( 'Y-m-d H:i:s' ), false );
    }

    private function invalid( $query_key = 'atlas_invalid_link' ) {
        wp_safe_redirect( $this->app_url( array( $query_key => 1 ) ) );
        exit;
    }

    public function app_url( $args = array() ) {
        $id  = absint( get_option( Activator::OPTION_PAGE_ID ) );
        $url = $id ? get_permalink( $id ) : home_url( '/' );
        return add_query_arg( $args, $url );
    }
}
