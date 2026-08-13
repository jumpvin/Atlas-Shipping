<?php
namespace AtlasShipping;
if ( ! defined( 'ABSPATH' ) ) { exit; }
class Frontend {
 const FRONTEND_VERSION = '0.4.0';
 public static function routes(){ return array('home','my-requests','all-requests','request-detail','new-request','needs-attention','coordinator-handoff','shipper-response','settings','profile'); }
 public function register(){ add_shortcode('atlas_shipping_app',array($this,'render_shortcode')); add_action('wp_enqueue_scripts',array($this,'register_assets')); add_filter('body_class',array($this,'body_classes')); }
 public function body_classes($classes){global $post;$id=absint(get_option(Activator::OPTION_PAGE_ID));if(($post instanceof \WP_Post&&has_shortcode($post->post_content,'atlas_shipping_app'))||($id&&get_queried_object_id()===$id))$classes[]='atlas-application-page';return$classes;}
 public function register_assets(){
  wp_register_style('atlas-shipping-layout',ATLAS_SHIPPING_URL.'assets/css/layout.css',array(),self::FRONTEND_VERSION);
  wp_register_style('atlas-shipping-pages',ATLAS_SHIPPING_URL.'assets/css/pages.css',array('atlas-shipping-layout'),self::FRONTEND_VERSION);
  wp_register_style('atlas-shipping-app',ATLAS_SHIPPING_URL.'assets/css/app.css',array('atlas-shipping-pages'),self::FRONTEND_VERSION);
  wp_register_style('atlas-shipping-editor',ATLAS_SHIPPING_URL.'assets/css/new-request.css',array('atlas-shipping-app'),self::FRONTEND_VERSION);
  wp_register_style('atlas-shipping-management',ATLAS_SHIPPING_URL.'assets/css/request-management.css',array('atlas-shipping-editor'),self::FRONTEND_VERSION);
  wp_register_script('atlas-shipping-router',ATLAS_SHIPPING_URL.'assets/js/router.js',array(),self::FRONTEND_VERSION,true);
  wp_register_script('atlas-shipping-navigation',ATLAS_SHIPPING_URL.'assets/js/navigation.js',array('atlas-shipping-router'),self::FRONTEND_VERSION,true);
  wp_register_script('atlas-shipping-pages',ATLAS_SHIPPING_URL.'assets/js/pages.js',array('atlas-shipping-router'),self::FRONTEND_VERSION,true);
  wp_register_script('atlas-shipping-editor',ATLAS_SHIPPING_URL.'assets/js/new-request.js',array('atlas-shipping-pages'),self::FRONTEND_VERSION,true);
  wp_register_script('atlas-shipping-management',ATLAS_SHIPPING_URL.'assets/js/request-management.js',array('atlas-shipping-pages'),self::FRONTEND_VERSION,true);
  wp_register_script('atlas-shipping-coordinator',ATLAS_SHIPPING_URL.'assets/js/coordinator.js',array('atlas-shipping-pages'),self::FRONTEND_VERSION,true);
  wp_register_script('atlas-shipping-app',ATLAS_SHIPPING_URL.'assets/js/app.js',array('atlas-shipping-navigation','atlas-shipping-pages','atlas-shipping-editor','atlas-shipping-management','atlas-shipping-coordinator'),self::FRONTEND_VERSION,true);
  global $post; $id=absint(get_option(Activator::OPTION_PAGE_ID));
  if((is_page()&&$id&&get_queried_object_id()===$id)||($post instanceof \WP_Post&&has_shortcode($post->post_content,'atlas_shipping_app'))){$this->enqueue_assets();}
 }
 private function enqueue_assets(){ wp_enqueue_style('atlas-shipping-management'); wp_enqueue_script('atlas-shipping-app'); }
 public function render_shortcode(){
  $this->enqueue_assets();
  $auth=new Authentication(); $message=''; $test_link=''; $admin_test_error='';
  if(isset($_POST['atlas_shipping_login'])&&wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce']??'')),'atlas_shipping_login')){
   $result=$auth->request_link(sanitize_email(wp_unslash($_POST['work_email']??'')));
   $message=__('If this email is authorized, a sign-in link has been prepared.','atlas-shipping');
   if(is_wp_error($result)){ if(current_user_can('manage_options'))$admin_test_error=__('The administrator testing link could not be created. Review ATLAS Shipping diagnostics.','atlas-shipping'); }
   elseif(is_string($result)&&current_user_can('manage_options')){$test_link=$result;}
  }
  if(isset($_GET['atlas_session_expired'])){$message=__('Your session has expired. Please sign in again.','atlas-shipping');}
  $identity=Sessions::current();
  if($identity){
   $strings=array(
    'home'=>__('Home','atlas-shipping'),'myRequests'=>__('My Requests','atlas-shipping'),'allRequests'=>__('All Requests','atlas-shipping'),
    'newRequest'=>__('New Request','atlas-shipping'),'needsAttention'=>__('Needs Attention','atlas-shipping'),'settings'=>__('Settings','atlas-shipping'),
    'profile'=>__('Profile','atlas-shipping'),'logout'=>__('Logout','atlas-shipping'),'welcome'=>__('Welcome','atlas-shipping'),
    'welcomeUser'=>__('Welcome, %s','atlas-shipping'),'applicationReady'=>__('Your ATLAS Shipping application is ready.','atlas-shipping'),
    'role'=>__('Role','atlas-shipping'),'pluginVersion'=>__('Plugin version','atlas-shipping'),'buildFingerprint'=>__('Build fingerprint','atlas-shipping'),
    'account'=>__('Account','atlas-shipping'),'name'=>__('Name','atlas-shipping'),'email'=>__('Email','atlas-shipping'),
    'lastLogin'=>__('Last login','atlas-shipping'),'sessionExpiration'=>__('Session expiration','atlas-shipping'),
    'profileDescription'=>__('Your application identity and current session details are read-only.','atlas-shipping'),
    'settingsDescription'=>__('This area will contain user preferences and application configuration in future releases.','atlas-shipping'),
    'myRequestsDescription'=>__('View and manage the shipping requests assigned to you.','atlas-shipping'),
    'allRequestsDescription'=>__('Review shipping requests across the organization.','atlas-shipping'),
    'newRequestDescription'=>__('Create a new shipping request for an upcoming project.','atlas-shipping'),
    'needsAttentionDescription'=>__('Review shipping requests that require action or follow-up.','atlas-shipping'),
    'futureModuleDescription'=>__('This area is reserved for a future application module.','atlas-shipping'),
    'comingSoon'=>__('Coming in a future milestone.','atlas-shipping'),'applicationReadyStatus'=>__('Application ready','atlas-shipping'),
    'openNavigation'=>__('Open navigation','atlas-shipping'),'closeNavigation'=>__('Close navigation','atlas-shipping'),
    'sessionExpired'=>__('Your session has expired. Please sign in again.','atlas-shipping'),'atlasShipping'=>__('ATLAS Shipping','atlas-shipping'),
    'loadingDraft'=>__('Loading your draft…','atlas-shipping'),'saving'=>__('Saving…','atlas-shipping'),'saved'=>__('Saved','atlas-shipping'),
    'saveFailed'=>__('Save failed. Try again.','atlas-shipping'),'conflict'=>__('This request changed elsewhere. Reload to continue safely.','atlas-shipping'),
    'resumedDraft'=>__('Your most recently updated draft was resumed.','atlas-shipping'),'submitRequest'=>__('Submit Shipping Request','atlas-shipping')
   );
   $editor_strings=array();
   foreach(array('ATLAS Shipping','New Request','Enter the shipment details below. Your meaningful work saves automatically.','Project / Request Details','Project ID','Internal ID','Client','Project / Job Site Name','Requested Ship Date','Required Delivery Date','Delivery date firmness','General request / project notes','Client requested','Stops','Times are entered in your local timezone and stored canonically.','Pickup','Delivery','Additional stop','Stop type','Site / company','Address line 1','Address line 2','City','State / region','Postal code','Country','Contact name','Contact phone','Contact email','Window start','Window end','Handling responsibility','Equipment notes','Stop instructions','Appointment required','Dock available','Forklift available','Move stop up','Move stop down','Remove','Remove this saved record? This cannot be undone.','Add stop','Shipment Items','Shipment item','Quantity','Description','Length','Width','Height','Dimension unit','Weight','Weight basis','Weight unit','Packaging type','Special handling / notes','Stackable','Fork pockets','Weather sensitive','Move item up','Move item down','Add item','Transportation / Handling Preferences','Preferred vehicle / equipment','Transportation comment','Shipping service loads at pickup','Shipping service unloads at delivery','Third-party loading help may be needed','Third-party unloading help may be needed','Please review:','Request submitted','Your shipping request %s was submitted successfully.')as$text){$editor_strings[$text]=__($text,'atlas-shipping');}
   foreach(array('Request Management','Upcoming / Active','Past / Completed','Search requests','All statuses','All owners','Sort by','Relevant date','Created date','Last updated','Status','Owner','Apply filters','Loading requests…','No requests found.','Create a New Request','Previous','Next','View request','Request list pagination','Project','Client','Requested ship date','Required delivery date','Pickup','Delivery','Updated','Request Details','Back to requests','Continue editing','Stop %s','Full address','Dimensions','Shipment item %s','Shipment Items','Transportation / Handling Preferences','Notes','Preferred vehicle / equipment','Transportation comment','Shipping service loads at pickup','Shipping service unloads at delivery','Third-party loading help may be needed','Third-party unloading help may be needed','Draft','Submitted','Sent To Shipper','Options Received','Scheduled','In Transit','Carrier Reported Delivered','Delivery Issue','Delivery Verified','Complete','Cancelled','Intermediate','No Preference','Yes','No','No value supplied','Unknown owner','Page %1$s of %2$s','requests total','Unable to load requests.')as$text){$editor_strings[$text]=__($text,'atlas-shipping');}
   foreach(array('Coordinator Handoff','Coordinator authorization is required.','Submitted requests ready for shipper handoff.','No submitted requests need attention.','Review handoff','Back to Needs Attention','Operational request','Items','PM transportation preference','Final outbound decision','Final vehicle / equipment','Choose…','Cargo Van','Sprinter Van','Box Truck','Flatbed','Dry Van','Reefer','Other','Other equipment','Coordinator notes','Sent at','Shipper loads at pickup','Shipper unloads at delivery','Confirm Outbound Handoff','Review the exact outbound handoff before sending.','Coordinator outbound decision','Back to Edit','Preview Outbound Handoff','Send to Shipper','Request sent to shipper. Immutable snapshot created.','Unable to load coordinator workflow.','Choose the final requested vehicle or equipment.','Immutable shipper-handoff snapshot created')as$text){$editor_strings[$text]=__($text,'atlas-shipping');}
   foreach(array('Shipper Response','Needs outbound handoff','Needs shipper response details','Awaiting shipper response','Record response','No requests need coordinator attention.','Outbound handoff summary','Actual Shipment Details','Record the confirmed details returned by the shipper. Saving a draft does not schedule the request.','Carrier / provider','Carrier not yet provided','Actual vehicle / equipment','Carrier / driver contact name','Carrier / driver phone','Carrier / driver email','Pickup window start','Pickup window end','Delivery window start','Delivery window end','Freight cost','Cost pending / not provided','Currency','Reference / confirmation / load number','Shipper response notes','Save Response Draft','Confirm Shipment Details','Response draft saved.','Shipment details confirmed. Request is scheduled.','Complete the required details before confirming.','PM Request','Shipper Handoff','Details confirmed','Freight cost pending','Not yet provided','Scheduled at')as$text){$editor_strings[$text]=__($text,'atlas-shipping');}
   $app_config=array(
    'version'=>ATLAS_SHIPPING_VERSION,'frontendVersion'=>self::FRONTEND_VERSION,'buildFingerprint'=>ATLAS_SHIPPING_BUILD_FINGERPRINT,
    'defaultRoute'=>'home','routes'=>self::routes(),'loginUrl'=>add_query_arg('atlas_session_expired','1',get_permalink()),'restUrl'=>esc_url_raw(rest_url(EditorApi::NS.'/')),'csrf'=>EditorApi::csrf(),
    'user'=>array('id'=>absint($identity->identity_id),'name'=>(string)$identity->full_name,'email'=>(string)$identity->email,'role'=>(string)$identity->role,'isCoordinator'=>in_array($identity->role,array('Shipping Coordinator','Manager','Administrator'),true),
     'lastLogin'=>!empty($identity->last_login_at)?get_date_from_gmt($identity->last_login_at,get_option('date_format').' '.get_option('time_format')):__('Not available','atlas-shipping'),
     'sessionExpiration'=>get_date_from_gmt($identity->expires_at,get_option('date_format').' '.get_option('time_format')),
     'sessionExpirationUnix'=>(int)strtotime($identity->expires_at.' UTC')),
    'strings'=>$strings,'editorStrings'=>$editor_strings);
   wp_localize_script('atlas-shipping-app','AtlasShippingApp',$app_config);
  }
  ob_start(); if($identity){include ATLAS_SHIPPING_DIR.'templates/app-shell.php';}else{include ATLAS_SHIPPING_DIR.'templates/login.php';} return ob_get_clean();
 }
}
