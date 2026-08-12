<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array('id'=>'002_identities','version'=>'0.1.2','run'=>static function(){
 global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php';
 $t=$wpdb->prefix.'atlas_shipping_identities'; $c=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE {$t} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 email varchar(190) NOT NULL,
 full_name varchar(190) NOT NULL,
 role varchar(64) NOT NULL,
 enabled tinyint(1) NOT NULL DEFAULT 1,
 created_at datetime NOT NULL,
 updated_at datetime NOT NULL,
 last_login_at datetime NULL,
 PRIMARY KEY (id), UNIQUE KEY email (email), KEY enabled (enabled), KEY role (role)
 ) {$c};");
 $found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$t));
 return $found===$t ? true : new WP_Error('atlas_shipping_identity_table_missing',__('The approved identity table could not be verified.','atlas-shipping'));
});
