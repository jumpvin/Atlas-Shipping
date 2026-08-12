<?php
namespace AtlasShipping;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Activator {
    const MIN_PHP = '8.0';
    const MIN_WP = '6.4';
    const OPTION_PLUGIN_VERSION = 'atlas_shipping_plugin_version';
    const OPTION_PAGE_ID = 'atlas_shipping_app_page_id';
    const OPTION_SHOW_NOTICE = 'atlas_shipping_show_activation_notice';
    const OPTION_ACTIVATION_ERROR = 'atlas_shipping_activation_error';

    public static function activate() {
        self::check_requirements();
        delete_option( self::OPTION_ACTIVATION_ERROR );

        $migrator = new Migrator();
        $result   = $migrator->run();
        if ( is_wp_error( $result ) ) {
            self::fail_activation( $result->get_error_message() );
        }

        $page_result = self::create_or_reuse_app_page();
        if ( is_wp_error( $page_result ) ) {
            self::fail_activation( $page_result->get_error_message() );
        }

        update_option( self::OPTION_PLUGIN_VERSION, ATLAS_SHIPPING_VERSION, false );
        update_option( self::OPTION_SHOW_NOTICE, 1, false );
        delete_option( self::OPTION_ACTIVATION_ERROR );

        Migrator::log_activity(
            'plugin_activated',
            sprintf( 'ATLAS Shipping %s activated successfully.', ATLAS_SHIPPING_VERSION ),
            array( 'build_fingerprint' => ATLAS_SHIPPING_BUILD_FINGERPRINT )
        );
    }

    private static function fail_activation( $message ) {
        $safe_message = sanitize_text_field( $message );
        update_option( self::OPTION_ACTIVATION_ERROR, $safe_message, false );
        update_option( self::OPTION_SHOW_NOTICE, 1, false );
        deactivate_plugins( plugin_basename( ATLAS_SHIPPING_FILE ) );
        wp_die( esc_html( $safe_message ), esc_html__( 'ATLAS Shipping activation failed', 'atlas-shipping' ), array( 'back_link' => true ) );
    }

    private static function check_requirements() {
        global $wp_version;

        $errors = array();
        if ( version_compare( PHP_VERSION, self::MIN_PHP, '<' ) ) {
            $errors[] = sprintf( 'PHP %s or newer is required.', self::MIN_PHP );
        }
        if ( version_compare( $wp_version, self::MIN_WP, '<' ) ) {
            $errors[] = sprintf( 'WordPress %s or newer is required.', self::MIN_WP );
        }

        if ( $errors ) {
            self::fail_activation( implode( ' ', $errors ) );
        }
    }

    public static function create_or_reuse_app_page() {
        $stored_id = absint( get_option( self::OPTION_PAGE_ID ) );
        if ( self::is_valid_app_page( $stored_id ) ) {
            return $stored_id;
        }

        $existing = get_page_by_path( 'shipping', OBJECT, 'page' );
        if ( $existing instanceof \WP_Post && self::is_valid_app_page( $existing->ID ) ) {
            update_option( self::OPTION_PAGE_ID, $existing->ID, false );
            return $existing->ID;
        }

        $page_id = wp_insert_post(
            array(
                'post_title'   => __( 'Shipping', 'atlas-shipping' ),
                'post_name'    => 'shipping',
                'post_content' => '[atlas_shipping_app]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ),
            true
        );

        if ( is_wp_error( $page_id ) || ! $page_id ) {
            $message = is_wp_error( $page_id ) ? $page_id->get_error_message() : __( 'WordPress did not return a valid application page ID.', 'atlas-shipping' );
            $safe    = __( 'The ATLAS Shipping application page could not be created. Existing database work was preserved.', 'atlas-shipping' );
            update_option( self::OPTION_ACTIVATION_ERROR, $safe . ' ' . sanitize_text_field( $message ), false );
            return new \WP_Error( 'atlas_shipping_page_creation_failed', $safe );
        }

        if ( ! self::is_valid_app_page( $page_id ) ) {
            $message = __( 'The ATLAS Shipping application page was created but could not be validated.', 'atlas-shipping' );
            update_option( self::OPTION_ACTIVATION_ERROR, $message, false );
            return new \WP_Error( 'atlas_shipping_page_validation_failed', $message );
        }

        update_option( self::OPTION_PAGE_ID, absint( $page_id ), false );
        return absint( $page_id );
    }

    public static function is_valid_app_page( $page_id ) {
        if ( ! $page_id ) {
            return false;
        }

        $page = get_post( $page_id );
        return $page instanceof \WP_Post
            && 'page' === $page->post_type
            && 'trash' !== $page->post_status
            // Activation may run after plugins_loaded, before Frontend has had
            // an opportunity to register the shortcode in this request.
            && false !== strpos( $page->post_content, '[atlas_shipping_app' );
    }
}
