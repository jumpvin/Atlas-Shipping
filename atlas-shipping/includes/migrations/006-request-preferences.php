<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array(
 'id'=>'006_request_preferences','version'=>'0.1.4',
 'run'=>static function(){
  global $wpdb;$table=$wpdb->prefix.'atlas_shipping_requests';
  $columns=array(
   'preferred_equipment'=>"varchar(40) NOT NULL DEFAULT 'no_preference'",
   'transportation_comment'=>'text NULL','service_load_pickup'=>'tinyint(1) NOT NULL DEFAULT 0',
   'service_unload_delivery'=>'tinyint(1) NOT NULL DEFAULT 0','third_party_loading'=>'tinyint(1) NOT NULL DEFAULT 0',
   'third_party_unloading'=>'tinyint(1) NOT NULL DEFAULT 0',
  );
  foreach($columns as$name=>$definition){
   if(!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $table LIKE %s",$name))){
    if(false===$wpdb->query("ALTER TABLE $table ADD COLUMN $name $definition"))return new WP_Error('atlas_request_preferences_failed',__('A request preference field could not be installed.','atlas-shipping'));
   }
  }
  return true;
 }
);
