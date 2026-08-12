<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
return array('id'=>'004_sessions','version'=>'0.1.2','run'=>static function(){
 global $wpdb; require_once ABSPATH.'wp-admin/includes/upgrade.php';
 $t=$wpdb->prefix.'atlas_shipping_sessions'; $c=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE {$t} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 identity_id bigint(20) unsigned NOT NULL,
 session_token_hash varchar(255) NOT NULL,
 created_at datetime NOT NULL,
 expires_at datetime NOT NULL,
 last_activity_at datetime NOT NULL,
 ip varchar(64) NULL,
 user_agent varchar(255) NULL,
 PRIMARY KEY (id), UNIQUE KEY session_token_hash (session_token_hash), KEY identity_id (identity_id), KEY expires_at (expires_at)
 ) {$c};");
 $found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$t));
 return $found===$t ? true : new WP_Error('atlas_shipping_session_table_missing',__('The application session table could not be verified.','atlas-shipping'));
});
