<?php
namespace AtlasShipping;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Plugin {
 public function run() {
  load_plugin_textdomain( 'atlas-shipping', false, dirname( plugin_basename( ATLAS_SHIPPING_FILE ) ) . '/languages' );
  ( new Migrator() )->maybe_migrate();
  ( new Authentication() )->register();
  ( new Frontend() )->register();
  if ( is_admin() ) { ( new Admin() )->register(); }
 }
}
