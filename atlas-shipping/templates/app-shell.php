<?php if(!defined('ABSPATH'))exit; ?>
<script>window.AtlasShippingApp=<?php echo wp_json_encode($app_config,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;</script>
<div class="atlas-shipping-app atlas-app-loading" data-atlas-shipping-app>
 <a class="atlas-skip-link" href="#atlas-main-content"><?php esc_html_e('Skip to content','atlas-shipping');?></a>
 <div class="atlas-app-shell">
  <header class="atlas-header">
   <div class="atlas-brand"><span class="atlas-brand-mark" aria-hidden="true">A</span><span><?php esc_html_e('ATLAS Shipping','atlas-shipping');?></span></div>
   <button class="atlas-menu-toggle" type="button" aria-label="<?php esc_attr_e('Open navigation','atlas-shipping');?>" aria-controls="atlas-sidebar" aria-expanded="false" data-atlas-menu-toggle><span></span><span></span><span></span></button>
   <h1 class="atlas-current-title" data-atlas-page-title><?php esc_html_e('Home','atlas-shipping');?></h1>
   <div class="atlas-user-area">
    <button class="atlas-user-button" type="button" aria-expanded="false" aria-controls="atlas-user-menu" aria-label="<?php esc_attr_e('Open user menu','atlas-shipping');?>" data-atlas-user-toggle><span class="atlas-avatar" aria-hidden="true"><?php echo esc_html(strtoupper(substr($identity->full_name,0,1)));?></span><span class="atlas-user-name"><?php echo esc_html($identity->full_name);?></span><span aria-hidden="true">⌄</span></button>
    <div class="atlas-user-menu" id="atlas-user-menu" hidden data-atlas-user-menu>
     <a href="#/profile" data-atlas-route="profile"><?php esc_html_e('Profile','atlas-shipping');?></a>
     <form method="post"><?php wp_nonce_field('atlas_shipping_logout');?><button type="submit" name="atlas_shipping_logout" value="1"><?php esc_html_e('Logout','atlas-shipping');?></button></form>
    </div>
   </div>
  </header>
  <div class="atlas-shell-body">
   <div class="atlas-sidebar-backdrop" hidden data-atlas-backdrop></div>
   <aside class="atlas-sidebar" id="atlas-sidebar" aria-label="<?php esc_attr_e('Application navigation','atlas-shipping');?>" data-atlas-sidebar>
    <nav class="atlas-nav">
     <a href="#/home" data-atlas-route="home"><span aria-hidden="true">⌂</span><?php esc_html_e('Home','atlas-shipping');?></a>
     <a href="#/my-requests" data-atlas-route="my-requests"><span aria-hidden="true">▤</span><?php esc_html_e('My Requests','atlas-shipping');?></a>
     <a href="#/all-requests" data-atlas-route="all-requests"><span aria-hidden="true">☷</span><?php esc_html_e('All Requests','atlas-shipping');?></a>
     <a href="#/new-request" data-atlas-route="new-request"><span aria-hidden="true">＋</span><?php esc_html_e('New Request','atlas-shipping');?></a>
     <a href="#/needs-attention" data-atlas-route="needs-attention"><span aria-hidden="true">◇</span><?php esc_html_e('Needs Attention','atlas-shipping');?></a>
     <a href="#/settings" data-atlas-route="settings"><span aria-hidden="true">⚙</span><?php esc_html_e('Settings','atlas-shipping');?></a>
    </nav>
    <div class="atlas-sidebar-footer"><span data-atlas-boot-status><?php esc_html_e('Initializing…','atlas-shipping');?></span></div>
   </aside>
   <main class="atlas-main" id="atlas-main-content" tabindex="-1"><div class="atlas-route-view" data-atlas-view><div class="atlas-loading-card"><?php esc_html_e('Loading application…','atlas-shipping');?></div></div></main>
  </div>
 </div>
</div>
