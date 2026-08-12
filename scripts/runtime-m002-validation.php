<?php
require 'C:/Users/govin/Local Sites/atlas/app/public/wp-load.php';
use AtlasShipping\Migrator;
use AtlasShipping\Shipping\Service;
function atlas_m002_assert($value,$code){if(!$value)throw new RuntimeException($code);}
global $wpdb;$checks=array();
try{
 $migration=(new Migrator())->run();atlas_m002_assert(true===$migration,'migration-rerun-failed');$checks[]='migration_006_idempotent';
 atlas_m002_assert('0.1.4'===get_option(Migrator::OPTION_SCHEMA_VERSION),'schema-version-invalid');
 $submitted=$wpdb->get_row("SELECT * FROM {$wpdb->prefix}atlas_shipping_requests WHERE public_id='AS-000001'");atlas_m002_assert($submitted&&'submitted'===$submitted->status&&!empty($submitted->submitted_at),'submission-state-invalid');$checks[]='submitted_timestamp';
 atlas_m002_assert(0===(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}atlas_shipping_snapshots WHERE request_id=%d AND created_at>=%s",$submitted->id,$submitted->submitted_at)),'submission-created-snapshot');$checks[]='submission_no_snapshot';
 $activity=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}atlas_shipping_activity WHERE activity_type='request_submitted' AND object_id=%d ORDER BY id DESC LIMIT 1",$submitted->id));$metadata=$activity?json_decode($activity->metadata,true):array();atlas_m002_assert($activity&&1===absint($metadata['actor_identity_id']??0),'submission-actor-invalid');$checks[]='submission_actor_attributed';
 $draft=$wpdb->get_row("SELECT * FROM {$wpdb->prefix}atlas_shipping_requests WHERE project_id='M002-DRAFT' ORDER BY id DESC LIMIT 1");atlas_m002_assert($draft&&1===absint($draft->owner_identity_id),'draft-owner-invalid');$checks[]='session_owner_derived';
 $service=new Service();$unique='LOCK-'.wp_generate_uuid4();$probe=$service->create_draft(array('project_id'=>$unique),1);atlas_m002_assert(!is_wp_error($probe)&&$unique===$probe->get('project_id'),'lock-probe-create-failed');$probe_id=$probe->id();
 $wpdb->update($wpdb->prefix.'atlas_shipping_requests',array('status'=>'sent_to_shipper'),array('id'=>$probe_id));$locked=$service->update_request($probe_id,array('client'=>'must-not-save'),1,$probe->get('row_version'),false);atlas_m002_assert(is_wp_error($locked)&&'atlas_request_locked'===$locked->get_error_code(),'locked-status-mutation-allowed');$checks[]='sent_to_shipper_locked';
 $wpdb->delete($wpdb->prefix.'atlas_shipping_activity',array('object_type'=>'shipping_request','object_id'=>$probe_id));$wpdb->delete($wpdb->prefix.'atlas_shipping_requests',array('id'=>$probe_id));$checks[]='scoped_probe_cleaned';
 echo wp_json_encode(array('result'=>'passed','checks'=>$checks,'version'=>ATLAS_SHIPPING_VERSION,'schema'=>get_option(Migrator::OPTION_SCHEMA_VERSION)),JSON_PRETTY_PRINT).PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,wp_json_encode(array('result'=>'failed','error'=>$e->getMessage())).PHP_EOL);exit(1);}
