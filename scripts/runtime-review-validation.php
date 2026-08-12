<?php
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { fwrite( STDERR, "Usage: php runtime-review-validation.php <wp-load.php>\n" ); exit( 2 ); }
require $argv[1];
use AtlasShipping\Identities;
use AtlasShipping\Migrator;
use AtlasShipping\Shipping\Service;

final class AtlasCollisionWpdb {
 public $prefix; public $insert_id = 0; private $real; private $fail_snapshot = true;
 public function __construct( $real ) { $this->real = $real; $this->prefix = $real->prefix; }
 public function insert( $table, $data, $formats = null ) { if ( $this->fail_snapshot && str_ends_with( $table, 'atlas_shipping_snapshots' ) ) { $this->fail_snapshot = false; return false; } $result=$this->real->insert($table,$data,$formats);$this->insert_id=$this->real->insert_id;return$result; }
 public function __call( $name, $arguments ) { return $this->real->{$name}(...$arguments); }
}
function atlas_assert( $condition, $code ) { if ( ! $condition ) { throw new RuntimeException( $code ); } }
function atlas_error_code( $value ) { return is_wp_error( $value ) ? $value->get_error_code() : ''; }

global $wpdb;
$email='atlas-review-'.wp_generate_password(8,false,false).'@example.test';$identity_id=0;$request_id=0;$checks=array();
try {
 $identity_id=Identities::save(array('email'=>$email,'full_name'=>'ATLAS Review Runner','role'=>'Administrator','enabled'=>1));atlas_assert(!is_wp_error($identity_id),'identity-create-failed');
 $service=new Service();$stop=array('sequence_no'=>1,'stop_type'=>'pickup','site_name'=>'Origin','address_line_1'=>'1 Test Way','city'=>'Test','region'=>'IL','postal_code'=>'60000','country'=>'US','window_start'=>'2026-08-13 10:00:00','window_end'=>'2026-08-13 11:00:00');$item=array('sequence_no'=>1,'quantity'=>1,'description'=>'Test freight','length'=>1,'width'=>1,'height'=>1,'dimension_unit'=>'in','weight'=>1,'weight_unit'=>'lb');
 atlas_assert(atlas_error_code($service->add_stop(999999,$stop,$identity_id))==='atlas_request_parent_invalid','orphan-stop-not-rejected');$checks[]='orphan_stop_rejected';
 atlas_assert(atlas_error_code($service->add_item(999999,$item,$identity_id))==='atlas_request_parent_invalid','orphan-item-not-rejected');$checks[]='orphan_item_rejected';
 atlas_assert(atlas_error_code($service->add_stop(999999,$stop,999999))==='atlas_actor_identity_invalid','invalid-actor-not-rejected');$checks[]='invalid_actor_rejected';
 $request=$service->create_draft(array('owner_identity_id'=>$identity_id,'project_id'=>'REVIEW','client'=>'Test','project_name'=>'Runtime validation'),$identity_id,array($stop),array($item));atlas_assert(!is_wp_error($request),'aggregate-create-failed');$request_id=$request->id();$checks[]='aggregate_created';
 $invalid=$stop;$invalid['sequence_no']=2;$invalid['window_start']='not-a-date';atlas_assert(atlas_error_code($service->add_stop($request_id,$invalid,$identity_id))==='atlas_stop_datetime_invalid','invalid-datetime-not-rejected');$checks[]='invalid_datetime_rejected';
 $reversed=$stop;$reversed['sequence_no']=2;$reversed['window_start']='2026-08-13 12:00:00';atlas_assert(atlas_error_code($service->add_stop($request_id,$reversed,$identity_id))==='atlas_stop_window_reversed','reversed-window-not-rejected');$checks[]='reversed_window_rejected';
 $valid=$stop;$valid['sequence_no']=2;atlas_assert(!is_wp_error($service->add_stop($request_id,$valid,$identity_id)),'valid-datetime-rejected');$checks[]='canonical_datetime_accepted';
 $collision_service=new Service(new AtlasCollisionWpdb($wpdb));$snapshot1=$collision_service->create_snapshot($request_id,'review',$identity_id);atlas_assert(!is_wp_error($snapshot1)&&1===(int)$snapshot1->get('snapshot_no'),'snapshot-retry-failed');$snapshot2=$service->create_snapshot($request_id,'review',$identity_id);atlas_assert(!is_wp_error($snapshot2)&&2===(int)$snapshot2->get('snapshot_no'),'snapshot-sequence-failed');$checks[]='snapshot_collision_retry_and_sequence';
 $activity=$wpdb->get_results($wpdb->prepare("SELECT metadata FROM {$wpdb->prefix}atlas_shipping_activity WHERE object_type='shipping_request' AND object_id=%d",$request_id));atlas_assert(count($activity)>0,'activity-missing');foreach($activity as$row){$meta=json_decode($row->metadata,true);atlas_assert(isset($meta['actor_identity_id'])&&(int)$meta['actor_identity_id']===$identity_id,'activity-actor-missing');}$checks[]='activity_actor_attribution';
 atlas_assert(true===$service->archive($request_id,$identity_id),'archive-failed');$existing_stop=$request->stops[0];atlas_assert(atlas_error_code($service->update_stop($existing_stop->id(),$stop,$identity_id))==='atlas_request_parent_invalid','archived-parent-mutation-allowed');$checks[]='archived_parent_mutation_rejected';
 $migration=(new Migrator())->run();atlas_assert(true===$migration,'migration-rerun-failed');$checks[]='migration_rerun_idempotent';
 echo wp_json_encode(array('result'=>'passed','checks'=>$checks,'product_version'=>ATLAS_SHIPPING_VERSION,'schema_version'=>get_option(Migrator::OPTION_SCHEMA_VERSION)),JSON_PRETTY_PRINT).PHP_EOL;
} catch(Throwable $error){fwrite(STDERR,wp_json_encode(array('result'=>'failed','error'=>$error->getMessage())).PHP_EOL);exit(1);
} finally {
 if($request_id){foreach(array('stops','items','snapshots')as$suffix){$wpdb->delete($wpdb->prefix.'atlas_shipping_'.$suffix,array('request_id'=>$request_id),array('%d'));}$wpdb->delete($wpdb->prefix.'atlas_shipping_requests',array('id'=>$request_id),array('%d'));$wpdb->delete($wpdb->prefix.'atlas_shipping_activity',array('object_type'=>'shipping_request','object_id'=>$request_id),array('%s','%d'));}
 if($identity_id){Identities::delete($identity_id);$wpdb->delete($wpdb->prefix.'atlas_shipping_activity',array('object_type'=>'identity','object_id'=>$identity_id),array('%s','%d'));}
}
