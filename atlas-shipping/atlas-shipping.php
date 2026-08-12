<?php
/**
 * Plugin Name: ATLAS Shipping Management
 * Description: Private frontend shipping-management application foundation.
 * Version: 0.1.7
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: ATLAS
 * Text Domain: atlas-shipping
 * Domain Path: /languages
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'ATLAS_SHIPPING_VERSION', '0.1.7' );
define( 'ATLAS_SHIPPING_SCHEMA_VERSION', '0.1.4' );
define( 'ATLAS_SHIPPING_BUILD_DATE', '2026-08-12' );
define( 'ATLAS_SHIPPING_BUILD_ID', 'atlas-m002' );
define( 'ATLAS_SHIPPING_BUILD_FINGERPRINT', ATLAS_SHIPPING_VERSION . '-' . str_replace( '-', '', ATLAS_SHIPPING_BUILD_DATE ) . '-' . ATLAS_SHIPPING_BUILD_ID );
define( 'ATLAS_SHIPPING_FILE', __FILE__ );
define( 'ATLAS_SHIPPING_DIR', plugin_dir_path( __FILE__ ) );
define( 'ATLAS_SHIPPING_URL', plugin_dir_url( __FILE__ ) );
require_once ATLAS_SHIPPING_DIR . 'includes/class-migrator.php';
require_once ATLAS_SHIPPING_DIR . 'includes/class-activator.php';
require_once ATLAS_SHIPPING_DIR . 'includes/class-deactivator.php';
require_once ATLAS_SHIPPING_DIR . 'includes/class-identities.php';
require_once ATLAS_SHIPPING_DIR . 'includes/shipping/class-models.php';
require_once ATLAS_SHIPPING_DIR . 'includes/shipping/class-repositories.php';
require_once ATLAS_SHIPPING_DIR . 'includes/shipping/class-service.php';
require_once ATLAS_SHIPPING_DIR . 'includes/class-editor-api.php';
require_once ATLAS_SHIPPING_DIR . 'includes/class-sessions.php';
require_once ATLAS_SHIPPING_DIR . 'includes/class-authentication.php';
require_once ATLAS_SHIPPING_DIR . 'includes/class-frontend.php';
require_once ATLAS_SHIPPING_DIR . 'includes/class-diagnostics.php';
require_once ATLAS_SHIPPING_DIR . 'includes/class-admin.php';
require_once ATLAS_SHIPPING_DIR . 'includes/class-plugin.php';
register_activation_hook( __FILE__, array( 'AtlasShipping\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AtlasShipping\\Deactivator', 'deactivate' ) );
function atlas_shipping_boot_plugin() { ( new AtlasShipping\Plugin() )->run(); }
add_action( 'plugins_loaded', 'atlas_shipping_boot_plugin' );
