<?php
if (!defined('ABSPATH'))
    die('Security check');
?>

<div class="wrap">
<?php screen_icon('cred-frontend-editor'); ?>
    <h1><?php _e('CRED Help', 'wp-cred') ?>&nbsp;&nbsp;(<?php printf(__('Version %s', 'wp-cred'), CRED_FE_VERSION); ?>)</h1><br />


    <h3 style="margin-top:3em;"><?php _e('Documentation and Support', 'wp-cred'); ?></h3>
    <ul>
        <li><?php printf('<a target="_blank" href="http://wp-types.com/documentation/user-guides/#CRED Plugin"><strong>%s</strong></a>' . __(' - everything you need to know about using CRED', 'wp-cred'), __('User Guides', 'wp-cred')); ?></li>
        <li><?php printf('<a target="_blank" href="http://discover-wp.com/"><strong>%s</strong></a>' . __(' - learn to use CRED by experimenting with fully-functional learning sites', 'wp-cred'), __('Discover WP', 'wp-cred')); ?></li>
        <li><?php printf('<a target="_blank" href="http://wp-types.com/forums/forum/support-2/"><strong>%s</strong></a>' . __(' - online help by support staff', 'wp-cred'), __('Support forum', 'wp-cred')); ?></li>
    </ul>


    <h3 style="margin-top:3em;"><?php _e('Debug information', 'wp-cred'); ?></h3>
    <p><?php
        printf(
                __('For retrieving debug information if asked by a support person, use the <a href="%s">debug information</a> page.', 'wp-cred'), admin_url('admin.php?page=cred-debug-information')
        );
        ?></p>
</div>
