<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array('id'=>'003_magic_tokens','version'=>'0.1.2','run'=>static function(){
 global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php';
 $t=$wpdb->prefix.'atlas_shipping_magic_tokens'; $c=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE {$t} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 identity_id bigint(20) unsigned NOT NULL,
 selector varchar(64) NOT NULL,
 hashed_validator varchar(255) NOT NULL,
 expires_at datetime NOT NULL,
 used_at datetime NULL,
 created_at datetime NOT NULL,
 request_ip varchar(64) NULL,
 request_user_agent varchar(255) NULL,
 PRIMARY KEY (id), UNIQUE KEY selector (selector), KEY identity_id (identity_id), KEY expires_at (expires_at)
 ) {$c};");
 $found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$t));
 return $found===$t ? true : new WP_Error('atlas_shipping_magic_table_missing',__('The magic-link table could not be verified.','atlas-shipping'));
});
