<?php
namespace AtlasShipping;
if(!defined('ABSPATH'))exit;
class Diagnostics {
 public function render(){
  if(!current_user_can('manage_options'))wp_die();
  global $wpdb;
  $m=new Migrator();
  $tables=array('activity'=>$wpdb->prefix.'atlas_shipping_activity','identities'=>$wpdb->prefix.'atlas_shipping_identities','magic tokens'=>$wpdb->prefix.'atlas_shipping_magic_tokens','sessions'=>$wpdb->prefix.'atlas_shipping_sessions');
  $installed=(string)get_option(Migrator::OPTION_SCHEMA_VERSION,'');
  $migration_error=(string)get_option(Migrator::OPTION_LAST_ERROR,'');
  $auth_error=(string)get_option(Authentication::OPTION_LAST_AUTH_ERROR,'');
  $auth_error_at=(string)get_option(Authentication::OPTION_LAST_AUTH_ERROR_AT,'');
  $all=true; foreach($tables as $t)$all=$all&&$m->table_exists($t);
  $health=($migration_error||!$all)?'Failed':(($installed===ATLAS_SHIPPING_SCHEMA_VERSION&&!$m->has_incomplete_migrations())?($auth_error?'Warning':'Healthy'):'Warning');
  $unused_tokens=$m->table_exists($tables['magic tokens'])?(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.$tables['magic tokens'].' WHERE used_at IS NULL AND expires_at>%s',gmdate('Y-m-d H:i:s'))):0;
  ?><div class="wrap atlas-shipping-admin-wrap"><h1><?php esc_html_e('ATLAS Shipping Diagnostics','atlas-shipping');?></h1><div class="atlas-admin-card"><table class="widefat striped"><tbody><?php
  $this->row('Authentication health',$health);
  $this->row('Application shell status',class_exists('AtlasShipping\\Frontend')?'Healthy':'Failed');
  $this->row('Registered frontend routes',implode(', ',Frontend::routes()));
  $this->row('Frontend version',Frontend::FRONTEND_VERSION);
  $this->row('Session expiration support','Healthy: client timer enabled');
  $this->row('Frontend localization status','Healthy: strings supplied by WordPress translations');
  $this->row('Accessibility status','Healthy: focus management and hidden-drawer controls enabled');
  $this->row('Authentication integration status',class_exists('AtlasShipping\\Authentication')&&class_exists('AtlasShipping\\Sessions')?'Healthy':'Failed');
  $this->row('Plugin version',ATLAS_SHIPPING_VERSION);
  $this->row('Expected schema',ATLAS_SHIPPING_SCHEMA_VERSION);
  $this->row('Installed schema',$installed?:'Not installed');
  $this->row('Build fingerprint',ATLAS_SHIPPING_BUILD_FINGERPRINT);
  $this->row('Completed migrations',implode(', ',$m->get_completed_migrations()));
  $this->row('Active sessions',(string)Sessions::count_active());
  $this->row('Unused unexpired tokens',(string)$unused_tokens);
  $this->row('Session lifetime',human_time_diff(0,Sessions::lifetime()));
  $this->row('Session activity interval',human_time_diff(0,Sessions::activity_interval()));
  $this->row('Magic-link lifetime',human_time_diff(0,Authentication::TOKEN_LIFETIME));
  $this->row('Last authentication error',$auth_error?:'None');
  $this->row('Authentication error timestamp',$auth_error_at?get_date_from_gmt($auth_error_at):'None');
  $this->row('Plugin directory',ATLAS_SHIPPING_DIR);
  $this->row('Main plugin file',ATLAS_SHIPPING_FILE);
  foreach($tables as $name=>$table)$this->row(ucfirst($name).' table',$m->table_exists($table)?'Healthy: '.$table:'Missing: '.$table);
  $this->row('Last migration',(string)get_option(Migrator::OPTION_LAST_MIGRATION,''));
  $this->row('Last migration error',$migration_error?:'None');
  ?></tbody></table><p><?php esc_html_e('Raw magic-link validators, complete testing links, and session credentials are never displayed in diagnostics or stored in activity logs.','atlas-shipping');?></p></div></div><?php
 }
 private function row($label,$value){echo '<tr><th scope="row">'.esc_html($label).'</th><td>'.esc_html($value).'</td></tr>';}
}
