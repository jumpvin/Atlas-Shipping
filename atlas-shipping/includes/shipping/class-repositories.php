<?php
namespace AtlasShipping\Shipping;
if ( ! defined( 'ABSPATH' ) ) { exit; }
abstract class Repository { protected $wpdb; protected $table; public function __construct($wpdb=null){$this->wpdb=$wpdb?:$GLOBALS['wpdb'];} protected function now(){return gmdate('Y-m-d H:i:s');} }
final class RequestRepository extends Repository {
 public function __construct($wpdb=null){parent::__construct($wpdb);$this->table=$this->wpdb->prefix.'atlas_shipping_requests';}
 public function find($id,$include_archived=false){$sql='SELECT * FROM '.$this->table.' WHERE id=%d'.($include_archived?'':' AND archived_at IS NULL');$row=$this->wpdb->get_row($this->wpdb->prepare($sql,absint($id)));return $row?new Request($row):null;}
 public function find_by_public_id($public_id,$include_archived=false){$sql='SELECT * FROM '.$this->table.' WHERE public_id=%s'.($include_archived?'':' AND archived_at IS NULL');$row=$this->wpdb->get_row($this->wpdb->prepare($sql,sanitize_text_field($public_id)));return $row?new Request($row):null;}
 public function create($data){$now=$this->now();$row=array_merge(array('public_id'=>null,'status'=>'draft','project_id'=>'','client'=>'','project_name'=>'','delivery_date_firmness'=>'unknown','client_requested'=>0,'row_version'=>1,'created_at'=>$now,'updated_at'=>$now),$data);$ok=$this->wpdb->insert($this->table,$row);if(false===$ok)return new \WP_Error('atlas_request_create_failed',__('The shipping request could not be created.','atlas-shipping'));$id=(int)$this->wpdb->insert_id;$public='AS-'.str_pad((string)$id,6,'0',STR_PAD_LEFT);$updated=$this->wpdb->query($this->wpdb->prepare('UPDATE '.$this->table.' SET public_id=%s WHERE id=%d AND public_id IS NULL',$public,$id));return 1===$updated?$this->find($id,true):new \WP_Error('atlas_request_public_id_failed',__('The request identifier could not be assigned.','atlas-shipping'));}
 public function update($id,$data,$expected_version){$data['updated_at']=$this->now();$sets=array();$values=array();foreach($data as$key=>$value){$sets[]=$key.'=%s';$values[]=$value;}$sets[]='row_version=row_version+1';$values[]=absint($id);$values[]=absint($expected_version);return 1===$this->wpdb->query($this->wpdb->prepare('UPDATE '.$this->table.' SET '.implode(',',$sets).' WHERE id=%d AND row_version=%d AND archived_at IS NULL',$values));}
 public function archive($id,$actor){return 1===$this->wpdb->query($this->wpdb->prepare('UPDATE '.$this->table.' SET archived_at=%s,updated_at=%s,updated_by_identity_id=%d,row_version=row_version+1 WHERE id=%d AND archived_at IS NULL',$this->now(),$this->now(),absint($actor),absint($id)));}
}
abstract class OrderedRepository extends Repository {
 protected $model;
 public function for_request($request){$rows=$this->wpdb->get_results($this->wpdb->prepare('SELECT * FROM '.$this->table.' WHERE request_id=%d ORDER BY sequence_no ASC,id ASC',absint($request)));return array_map(function($r){$c=$this->model;return new $c($r);},$rows);}
 public function find($id){$r=$this->wpdb->get_row($this->wpdb->prepare('SELECT * FROM '.$this->table.' WHERE id=%d',absint($id)));$c=$this->model;return$r?new $c($r):null;}
 public function create($data){$now=$this->now();$data['created_at']=$now;$data['updated_at']=$now;$ok=$this->wpdb->insert($this->table,$data);return false===$ok?new \WP_Error('atlas_relationship_create_failed',__('The related shipping record could not be created.','atlas-shipping')):$this->find($this->wpdb->insert_id);}
 public function update($id,$data){$data['updated_at']=$this->now();return false!==$this->wpdb->update($this->table,$data,array('id'=>absint($id)));}
 public function delete($id){return false!==$this->wpdb->delete($this->table,array('id'=>absint($id)),array('%d'));}
}
final class StopRepository extends OrderedRepository {public function __construct($wpdb=null){parent::__construct($wpdb);$this->table=$this->wpdb->prefix.'atlas_shipping_stops';$this->model=Stop::class;}}
final class ItemRepository extends OrderedRepository {public function __construct($wpdb=null){parent::__construct($wpdb);$this->table=$this->wpdb->prefix.'atlas_shipping_items';$this->model=Item::class;}}
final class SnapshotRepository extends Repository {
 public function __construct($wpdb=null){parent::__construct($wpdb);$this->table=$this->wpdb->prefix.'atlas_shipping_snapshots';}
 public function for_request($request){$rows=$this->wpdb->get_results($this->wpdb->prepare('SELECT * FROM '.$this->table.' WHERE request_id=%d ORDER BY snapshot_no ASC',absint($request)));return array_map(fn($r)=>new Snapshot($r),$rows);}
 public function create($data){$data['created_at']=$this->now();$ok=$this->wpdb->insert($this->table,$data);if(false===$ok)return new \WP_Error('atlas_snapshot_create_failed',__('The immutable snapshot could not be stored.','atlas-shipping'));$row=$this->wpdb->get_row($this->wpdb->prepare('SELECT * FROM '.$this->table.' WHERE id=%d',$this->wpdb->insert_id));return new Snapshot($row);}
}
