<?php if (!defined('ABSPATH')) die('Security check'); ?>

<!-- templates here-->
<script type="text/html-template" id="cred_notification_field_condition_template">
<?php
echo CRED_Loader::tpl('notification-condition', array(
    'condition' => null,
    'ii' => '__i__',
    'jj' => '__j__'
)); // not cache 
?>
</script>
<script type="text/html-template" id="cred_notification_template">
<?php
echo CRED_Loader::tpl('notification-user', array(
    'form' => $form,
    'ii' => '__i__',
    'enableTestMail' => $enableTestMail,
    'notification' => array()
)); // not cache
?>
</script>
<!-- /end templates -->

<!-- Tips texts -->
<div style="display:none">
    <div id="recipients_tip">
        <h3><?php _e('Notification recipients', 'wp-cred'); ?></h3>
        <p><?php _e('You can select multiple recipients for email notifications. Select the check-boxes for different recipient types and their target type (to/cc/bcc).', 'wp-cred'); ?></p>
    </div>
    <div id="additional_recipients_tip">
        <h3><?php _e('Additional notification recipients', 'wp-cred'); ?></h3>
        <p><?php _e('You can enter additional recipients as:<br />email<br />name &lt;email&gt;<br />to/cc/bcc: name &lt;email&gt;<br /><br />If no recipient type is specified, the recipient will be added as \'to\'.<br />Separate multiple recipients with commas.', 'wp-cred'); ?></p>
    </div>
</div>
<!-- /End tips texts -->


<?php /*
  //remove the checkbox for 'Enable sending notifications for this form'
  <p>
  <label class='cred-label'>
  <input type='checkbox' class='cred_cred_input cred_cred_checkbox cred-checkbox-10' name='_cred[notification][enable]' id='cred_notification_enable' value='1' <?php echo esc_attr($enable); ?>/>
  <span><?php _e('Enable sending notifications for this form','wp-cred'); ?></span>
  </label>
  </p>
 */ ?>


<div id='cred_notification_settings_panel_container'>
    <div class="clearfix cred-notification-settings-panel-header">
        <p class='cred-explain-text alignleft'><?php _e('Add notifications to automatically send email after submitting this form.', 'wp-cred'); ?></p>


        <a id='cred-notification-add-button' class='button button-secondary cred-notification-add-button alignright' href='javascript:;' data-cred-bind="{
           event: 'click',
           action: 'addItem',
           tmplRef: '#cred_notification_template',
           modelRef: '_cred[notification][notifications][__i__]',
           domRef: '#cred_notification_settings_panel_container',
           replace: [
           '__i__', {next: '_cred[notification][notifications]'}
           ]
           }">
    <?php _e('Add new notification', 'wp-cred'); ?>
        </a>

    </div>

    <?php /* <div data-cred-bind="{ action: 'fadeIn', condition: '_cred[notification][enable]<>1' }" class="cred_disabled_overlay"><br /></div> */ ?>
    <?php
    foreach ($notifications as $ii => $notification) {
        // new format
        echo CRED_Loader::tpl('notification-user', array(
            'form' => $form,
            'ii' => $ii,
            'enableTestMail' => $enableTestMail,
            'notification' => $notification
        )); // not cache
    }
    ?>
</div>

<?php /* <p id='cred_notification_add_container' class='cred-notification-add-container' <?php echo $enable?'':'style="display:none;"'?>> */ ?>
