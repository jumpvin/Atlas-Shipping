<?php
namespace AtlasShipping;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Migrator {
    const OPTION_SCHEMA_VERSION       = 'atlas_shipping_schema_version';
    const OPTION_COMPLETED_MIGRATIONS = 'atlas_shipping_completed_migrations';
    const OPTION_LAST_MIGRATION       = 'atlas_shipping_last_migration';
    const OPTION_LAST_ERROR           = 'atlas_shipping_last_migration_error';
    const OPTION_LAST_TIMESTAMP       = 'atlas_shipping_last_migration_timestamp';

    /**
     * Ordered migration registry. Future migrations are appended here without
     * redesigning execution behavior.
     *
     * @return array<int,array{id:string,version:string,file:string}>
     */
    public function registry() {
        return array(
            array(
                'id'      => '001_initial_foundation',
                'version' => '0.1.0',
                'file'    => ATLAS_SHIPPING_DIR . 'includes/migrations/001-initial-foundation.php',
            ),
            array(
                'id' => '002_identities', 'version' => '0.1.2',
                'file' => ATLAS_SHIPPING_DIR . 'includes/migrations/002-identities.php',
            ),
            array(
                'id' => '003_magic_tokens', 'version' => '0.1.2',
                'file' => ATLAS_SHIPPING_DIR . 'includes/migrations/003-magic-tokens.php',
            ),
            array(
                'id' => '004_sessions', 'version' => '0.1.2',
                'file' => ATLAS_SHIPPING_DIR . 'includes/migrations/004-sessions.php',
            ),
            array(
                'id' => '005_shipping_domain', 'version' => '0.1.3',
                'file' => ATLAS_SHIPPING_DIR . 'includes/migrations/005-shipping-domain.php',
            ),
            array(
                'id' => '006_request_preferences', 'version' => '0.1.4',
                'file' => ATLAS_SHIPPING_DIR . 'includes/migrations/006-request-preferences.php',
            ),
            array(
                'id' => '007_coordinator_handoffs', 'version' => '0.2.0',
                'file' => ATLAS_SHIPPING_DIR . 'includes/migrations/007-coordinator-handoffs.php',
            ),
            array(
                'id' => '008_shipper_responses', 'version' => '0.2.1',
                'file' => ATLAS_SHIPPING_DIR . 'includes/migrations/008-shipper-responses.php',
            ),
        );
    }

    public function maybe_migrate() {
        $installed = (string) get_option( self::OPTION_SCHEMA_VERSION, '' );
        $completed = $this->reconcile_legacy_completion( $installed, $this->get_completed_migrations() );

        if ( version_compare( $installed ?: '0.0.0', ATLAS_SHIPPING_SCHEMA_VERSION, '<' ) || $this->has_incomplete_migrations( $completed ) ) {
            return $this->run();
        }

        return true;
    }

    public function run() {
        $installed = (string) get_option( self::OPTION_SCHEMA_VERSION, '' );
        $completed = $this->reconcile_legacy_completion( $installed, $this->get_completed_migrations() );
        $registry  = $this->registry();

        update_option( self::OPTION_LAST_ERROR, '', false );
        update_option( self::OPTION_LAST_TIMESTAMP, gmdate( 'Y-m-d H:i:s' ), false );

        foreach ( $registry as $definition ) {
            if ( in_array( $definition['id'], $completed, true ) ) {
                continue;
            }

            update_option( self::OPTION_LAST_MIGRATION, $definition['id'], false );
            update_option( self::OPTION_LAST_TIMESTAMP, gmdate( 'Y-m-d H:i:s' ), false );

            $migration = $this->load_migration( $definition );
            if ( is_wp_error( $migration ) ) {
                return $this->record_failure( $definition['id'], $migration->get_error_message() );
            }

            try {
                $result = call_user_func( $migration['run'] );
            } catch ( \Throwable $error ) {
                return $this->record_failure(
                    $definition['id'],
                    __( 'The migration could not be completed. Review protected diagnostics and retry activation.', 'atlas-shipping' )
                );
            }

            if ( is_wp_error( $result ) ) {
                return $this->record_failure( $definition['id'], $result->get_error_message() );
            }

            if ( true !== $result ) {
                return $this->record_failure(
                    $definition['id'],
                    __( 'The migration did not report successful completion.', 'atlas-shipping' )
                );
            }

            $completed[] = $definition['id'];
            $completed   = array_values( array_unique( $completed ) );
            update_option( self::OPTION_COMPLETED_MIGRATIONS, $completed, false );
            update_option( self::OPTION_SCHEMA_VERSION, $definition['version'], false );
        }

        if ( ! $this->required_tables_exist() ) {
            return $this->record_failure(
                'verification',
                __( 'A required ATLAS Shipping database table is missing after migration.', 'atlas-shipping' )
            );
        }

        update_option( self::OPTION_SCHEMA_VERSION, ATLAS_SHIPPING_SCHEMA_VERSION, false );
        update_option( self::OPTION_LAST_ERROR, '', false );
        update_option( self::OPTION_LAST_TIMESTAMP, gmdate( 'Y-m-d H:i:s' ), false );

        return true;
    }

    private function reconcile_legacy_completion( $installed, $completed ) {
        // Version 0.1.0 predated per-migration completion tracking. When its
        // schema and required table are intact, record migration 001 as already
        // complete rather than executing completed database work again.
        if ( ! in_array( '001_initial_foundation', $completed, true )
            && version_compare( $installed ?: '0.0.0', '0.1.0', '>=' )
            && $this->required_tables_exist() ) {
            $completed[] = '001_initial_foundation';
            $completed   = array_values( array_unique( $completed ) );
            update_option( self::OPTION_COMPLETED_MIGRATIONS, $completed, false );
        }

        return $completed;
    }

    private function load_migration( $definition ) {
        if ( empty( $definition['file'] ) || ! is_readable( $definition['file'] ) ) {
            return new \WP_Error( 'atlas_shipping_migration_missing', __( 'A required migration file is missing or unreadable.', 'atlas-shipping' ) );
        }

        $migration = require $definition['file'];
        if ( ! is_array( $migration ) || empty( $migration['id'] ) || empty( $migration['version'] ) || ! isset( $migration['run'] ) || ! is_callable( $migration['run'] ) ) {
            return new \WP_Error( 'atlas_shipping_migration_invalid', __( 'A migration definition is invalid.', 'atlas-shipping' ) );
        }

        if ( $migration['id'] !== $definition['id'] || $migration['version'] !== $definition['version'] ) {
            return new \WP_Error( 'atlas_shipping_migration_mismatch', __( 'A migration definition does not match the ordered registry.', 'atlas-shipping' ) );
        }

        return $migration;
    }

    private function record_failure( $migration_id, $message ) {
        $safe_message = sanitize_text_field( $message );
        update_option( self::OPTION_LAST_MIGRATION, sanitize_key( $migration_id ), false );
        update_option( self::OPTION_LAST_ERROR, $safe_message, false );
        update_option( self::OPTION_LAST_TIMESTAMP, gmdate( 'Y-m-d H:i:s' ), false );

        return new \WP_Error( 'atlas_shipping_migration_failed', $safe_message );
    }

    public function get_completed_migrations() {
        $completed = get_option( self::OPTION_COMPLETED_MIGRATIONS, array() );
        if ( ! is_array( $completed ) ) {
            return array();
        }

        return array_values( array_filter( array_map( 'sanitize_key', $completed ) ) );
    }

    public function has_incomplete_migrations( $completed = null ) {
        $completed = is_array( $completed ) ? $completed : $this->get_completed_migrations();
        foreach ( $this->registry() as $definition ) {
            if ( ! in_array( $definition['id'], $completed, true ) ) {
                return true;
            }
        }
        return false;
    }

    public function table_exists( $table ) {
        global $wpdb;
        $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        return $found === $table;
    }

    public function required_tables_exist() {
        global $wpdb;
        return $this->table_exists( $wpdb->prefix . 'atlas_shipping_activity' )
            && $this->table_exists( $wpdb->prefix . 'atlas_shipping_identities' )
            && $this->table_exists( $wpdb->prefix . 'atlas_shipping_magic_tokens' )
            && $this->table_exists( $wpdb->prefix . 'atlas_shipping_sessions' )
            && $this->table_exists( $wpdb->prefix . 'atlas_shipping_requests' )
            && $this->table_exists( $wpdb->prefix . 'atlas_shipping_stops' )
            && $this->table_exists( $wpdb->prefix . 'atlas_shipping_items' )
            && $this->table_exists( $wpdb->prefix . 'atlas_shipping_snapshots' )
            && $this->table_exists( $wpdb->prefix . 'atlas_shipping_handoffs' )
            && $this->table_exists( $wpdb->prefix . 'atlas_shipping_shipper_responses' );
    }

    public static function log_activity( $type, $summary, $metadata = array(), $object_type = 'plugin', $object_id = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'atlas_shipping_activity';

        $payload  = empty( $metadata ) ? null : wp_json_encode( $metadata );
        $actor_id = get_current_user_id();
        $data     = array(
            'object_type'   => sanitize_key( $object_type ),
            'activity_type' => sanitize_key( $type ),
            'actor_type'    => is_user_logged_in() ? 'wordpress_user' : 'system',
            'summary'       => sanitize_text_field( $summary ),
            'created_at'    => gmdate( 'Y-m-d H:i:s' ),
        );
        $formats  = array( '%s', '%s', '%s', '%s', '%s' );

        if ( null !== $object_id && absint( $object_id ) > 0 ) {
            $data['object_id'] = absint( $object_id );
            $formats[]         = '%d';
        }
        if ( $actor_id > 0 ) {
            $data['actor_id'] = $actor_id;
            $formats[]        = '%d';
        }
        if ( null !== $payload ) {
            $data['metadata'] = $payload;
            $formats[]        = '%s';
        }

        return $wpdb->insert( $table, $data, $formats );
    }
}
