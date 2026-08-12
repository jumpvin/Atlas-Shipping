<?php
namespace AtlasShipping\Shipping;
if ( ! defined( 'ABSPATH' ) ) { exit; }
abstract class Model {
 protected $data = array();
 public function __construct( $data = array() ) { $this->data = is_object( $data ) ? get_object_vars( $data ) : (array) $data; }
 public function get( $key, $default = null ) { return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default; }
 public function id() { return absint( $this->get( 'id' ) ); }
 public function to_array() { return $this->data; }
}
final class Request extends Model {
 const STATUSES = array( 'draft','submitted','sent_to_shipper','options_received','scheduled','in_transit','carrier_reported_delivered','delivery_issue','delivery_verified','complete','cancelled' );
 public $stops = array(); public $items = array(); public $snapshots = array();
}
final class Stop extends Model { const TYPES = array( 'pickup','delivery','intermediate' ); }
final class Item extends Model {}
final class Snapshot extends Model {}
