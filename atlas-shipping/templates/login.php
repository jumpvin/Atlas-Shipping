<?php if(!defined('ABSPATH'))exit; ?>
<div class="atlas-shipping-app atlas-auth-screen" data-atlas-shipping-app>
 <main class="atlas-auth-card" aria-labelledby="atlas-login-title">
  <div class="atlas-mark" aria-hidden="true">A</div><p class="atlas-eyebrow"><?php esc_html_e('ATLAS Shipping','atlas-shipping');?></p>
  <h1 id="atlas-login-title"><?php esc_html_e('Sign in with your work email','atlas-shipping');?></h1>
  <p><?php esc_html_e('We will create a secure, one-time link for approved team members. No password is required.','atlas-shipping');?></p>
  <?php if(isset($_GET['atlas_invalid_link'])):?><div class="atlas-notice atlas-error"><?php esc_html_e('This login link is no longer valid. Please request a new login link.','atlas-shipping');?></div><?php endif;?>
  <?php if(isset($_GET['atlas_signin_failed'])):?><div class="atlas-notice atlas-error"><?php esc_html_e('We could not complete sign-in. Please request a new link.','atlas-shipping');?></div><?php endif;?>
  <?php if(isset($_GET['atlas_signed_out'])):?><div class="atlas-notice"><?php esc_html_e('You have been signed out.','atlas-shipping');?></div><?php endif;?>
  <?php if($admin_test_error):?><div class="atlas-notice atlas-error"><?php echo esc_html($admin_test_error);?></div><?php endif;?>
  <?php if($message):?><div class="atlas-notice" role="status"><?php echo esc_html($message);?></div><?php endif;?>
  <form method="post" class="atlas-login-form"><?php wp_nonce_field('atlas_shipping_login');?><label for="atlas-work-email"><?php esc_html_e('Work Email','atlas-shipping');?></label><input id="atlas-work-email" name="work_email" type="email" autocomplete="email" required><button type="submit" name="atlas_shipping_login" value="1"><?php esc_html_e('Continue','atlas-shipping');?></button></form>
  <?php if($test_link&&current_user_can('manage_options')):?><div class="atlas-test-link"><strong><?php esc_html_e('Administrator testing link','atlas-shipping');?></strong><p><?php esc_html_e('Email delivery is intentionally disabled in this milestone. Copy this one-time link now; it is not stored in plaintext.','atlas-shipping');?></p><input readonly value="<?php echo esc_attr($test_link);?>" onclick="this.select()"></div><?php endif;?>
 </main>
</div>
