<?php
namespace AtlasShipping;
use AtlasShipping\Shipping\Service;
if(!defined('ABSPATH')){exit;}
final class EditorApi{
 const NS='atlas-shipping/v1';
 public function register(){add_action('rest_api_init',array($this,'routes'));}
 public static function csrf(){return hash_hmac('sha256',(string)($_COOKIE[Sessions::COOKIE]??''),wp_salt('nonce'));}
 public function routes(){
  register_rest_route(self::NS,'/draft',array(array('methods'=>'GET','callback'=>array($this,'resume'),'permission_callback'=>array($this,'readable')),array('methods'=>'POST','callback'=>array($this,'create'),'permission_callback'=>array($this,'mutable'))));
  register_rest_route(self::NS,'/requests/(?P<public>[A-Za-z0-9-]+)',array(array('methods'=>'GET','callback'=>array($this,'load'),'permission_callback'=>array($this,'readable')),array('methods'=>'PATCH','callback'=>array($this,'update'),'permission_callback'=>array($this,'mutable'))));
  register_rest_route(self::NS,'/requests/(?P<public>[A-Za-z0-9-]+)/submit',array('methods'=>'POST','callback'=>array($this,'submit'),'permission_callback'=>array($this,'mutable')));
  foreach(array('stops','items')as$type){register_rest_route(self::NS,'/requests/(?P<public>[A-Za-z0-9-]+)/'.$type,array('methods'=>'POST','callback'=>function($r)use($type){return$this->create_child($r,$type);},'permission_callback'=>array($this,'mutable')));register_rest_route(self::NS,'/requests/(?P<public>[A-Za-z0-9-]+)/'.$type.'/(?P<id>\d+)',array(array('methods'=>'PATCH','callback'=>function($r)use($type){return$this->update_child($r,$type);},'permission_callback'=>array($this,'mutable')),array('methods'=>'DELETE','callback'=>function($r)use($type){return$this->delete_child($r,$type);},'permission_callback'=>array($this,'mutable'))));}
 }
 private function identity(){return Sessions::current(false);}
 public function readable(){return $this->identity()?true:new \WP_Error('atlas_auth_required',__('Sign in to continue.','atlas-shipping'),array('status'=>401));}
 public function mutable($r){$auth=$this->readable();if(is_wp_error($auth))return$auth;$sent=(string)$r->get_header('X-Atlas-CSRF');return $sent&&hash_equals(self::csrf(),$sent)?true:new \WP_Error('atlas_csrf_invalid',__('Refresh the page and try again.','atlas-shipping'),array('status'=>403));}
 private function service(){return new Service();}private function actor(){return absint($this->identity()->identity_id);}
 private function result($value,$resumed=false){if(is_wp_error($value)){if('atlas_request_conflict'===$value->get_error_code())$value->add_data(array('status'=>409));elseif('atlas_submission_invalid'===$value->get_error_code()){$data=(array)$value->get_error_data();$data['status']=422;$value->add_data($data);}return$value;}return rest_ensure_response(array('request'=>$value?$this->aggregate($value):null,'resumed'=>$resumed));}
 private function aggregate($r){$request=$r->to_array();foreach(array('client_requested','service_load_pickup','service_unload_delivery','third_party_loading','third_party_unloading')as$k)$request[$k]=absint($request[$k]??0);$stops=array_map(function($v){$row=$v->to_array();foreach(array('appointment_required','dock_available','forklift_available')as$k)$row[$k]=absint($row[$k]??0);return$row;},$r->stops);$items=array_map(function($v){$row=$v->to_array();foreach(array('stackable','fork_pockets','weather_sensitive')as$k)$row[$k]=absint($row[$k]??0);return$row;},$r->items);return array_merge($request,array('stops'=>$stops,'items'=>$items));}
 public function resume(){return$this->result($this->service()->resume($this->actor()),true);}
 public function create($r){$payload=(array)$r->get_json_params();$data=(array)($payload['request']??$payload);$stops=(array)($payload['stops']??array());$items=(array)($payload['items']??array());$meaningful=false;foreach(array('project_id','client','project_name','requested_ship_date','required_delivery_date','notes')as$k)$meaningful=$meaningful||!empty(trim((string)($data[$k]??'')));foreach($stops as$stop)$meaningful=$meaningful||''!==trim((string)($stop['site_name']??'').(string)($stop['address_line_1']??''));foreach($items as$item)$meaningful=$meaningful||!empty(trim((string)($item['description']??'')));if(!$meaningful)return new \WP_Error('atlas_draft_not_meaningful',__('Enter request, stop, or item information before saving a draft.','atlas-shipping'),array('status'=>422));return$this->result($this->service()->create_draft($data,$this->actor(),$stops,$items));}
 private function owned($r){return$this->service()->load_for_owner($r['public'],$this->actor());}
 public function load($r){return$this->result($this->owned($r));}
 public function update($r){$owned=$this->owned($r);if(is_wp_error($owned))return$owned;return$this->result($this->service()->update_request($owned->id(),(array)$r->get_json_params(),$this->actor(),absint($r->get_param('row_version')),false));}
 public function submit($r){$owned=$this->owned($r);if(is_wp_error($owned))return$owned;return$this->result($this->service()->submit($owned->id(),$this->actor(),absint($r->get_param('row_version'))));}
 public function create_child($r,$type){$owned=$this->owned($r);if(is_wp_error($owned))return$owned;return$this->result($this->service()->editor_child_create($owned->id(),$type,(array)$r->get_json_params(),$this->actor(),absint($r->get_param('row_version'))));}
 private function child_belongs($owned,$type,$id){foreach('stops'===$type?$owned->stops:$owned->items as$v)if($v->id()===absint($id))return true;return false;}
 public function update_child($r,$type){$owned=$this->owned($r);if(is_wp_error($owned))return$owned;return$this->result($this->service()->editor_child_update($owned->id(),$type,$r['id'],(array)$r->get_json_params(),$this->actor(),absint($r->get_param('row_version'))));}
 public function delete_child($r,$type){$owned=$this->owned($r);if(is_wp_error($owned))return$owned;return$this->result($this->service()->editor_child_delete($owned->id(),$type,$r['id'],$this->actor(),absint($r->get_param('row_version'))));}
}
