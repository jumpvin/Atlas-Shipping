<?php
require 'C:/Users/govin/Local Sites/atlas/app/public/wp-load.php';
if(!class_exists('AtlasShipping\\Shipping\\Service'))require 'C:/Users/govin/Local Sites/atlas/app/public/wp-content/plugins/atlas-shipping/atlas-shipping.php';
use AtlasShipping\Shipping\Service;use AtlasShipping\Identities;
function m4check($value,$name){if(!$value)throw new RuntimeException($name);}
global $wpdb;$ids=array();
try{
 $service=new Service();$identities=Identities::all();$actor=absint($identities[0]->id??0);m4check($actor,'actor');
 $valid=$service->create_draft(array('project_id'=>'M004-'.wp_generate_uuid4(),'requested_ship_date'=>'2028-02-29','required_delivery_date'=>'2028-03-01'),$actor);
 m4check(!is_wp_error($valid),'valid-leap-date');$ids[]=$valid->id();
 m4check('2028-02-29'===$valid->get('requested_ship_date')&&'2028-03-01'===$valid->get('required_delivery_date'),'date-round-trip');
 $blank=$service->update_request($valid->id(),array('requested_ship_date'=>'','required_delivery_date'=>'2027-06-15'),$actor,$valid->get('row_version'));
 m4check(!is_wp_error($blank)&&null===$blank->get('requested_ship_date')&&'2027-06-15'===$blank->get('required_delivery_date'),'blank-and-valid-date');
 foreach(array(array('requested_ship_date'=>'2026-02-30','field'=>'requested_ship_date'),array('required_delivery_date'=>'02/28/2027','field'=>'required_delivery_date'),array('requested_ship_date'=>'2027-02-29','field'=>'requested_ship_date'))as$case){$bad=$service->update_request($blank->id(),array($case['field']=>$case[$case['field']]),$actor,$blank->get('row_version'));m4check(is_wp_error($bad)&&'atlas_request_date_invalid'===$bad->get_error_code()&&!empty($bad->get_error_data()['fields'][$case['field']]),'reject-'.$case['field']);}
 echo wp_json_encode(array('result'=>'passed','checks'=>array('valid_leap_date','exact_date_round_trip','blank_optional_date','malformed_rejected','impossible_date_rejected','non_leap_rejected','field_specific_error')),JSON_PRETTY_PRINT).PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,wp_json_encode(array('result'=>'failed','error'=>$e->getMessage())).PHP_EOL);exit(1);}finally{foreach($ids as$id){$wpdb->delete($wpdb->prefix.'atlas_shipping_activity',array('object_type'=>'shipping_request','object_id'=>$id));$wpdb->delete($wpdb->prefix.'atlas_shipping_requests',array('id'=>$id));}}
