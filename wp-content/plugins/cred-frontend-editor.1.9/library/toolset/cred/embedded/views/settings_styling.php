<?php
$settings_model = CRED_Loader::get('MODEL/Settings');
$settings = $settings_model->getSettings();

if (!isset($settings['dont_load_bootstrap_cred_css'])) {
    $settings['dont_load_bootstrap_cred_css'] = 0;
}

/**
 * $settings['dont_load_cred_css'] = 1; for new installation (install)
 * $settings['dont_load_cred_css'] = 0; for old installation (update)
 */
if (!isset($settings['dont_load_cred_css'])) {
    $settings['dont_load_cred_css'] = 1;
}
?>
<div class="js-cred-settings-wrapper">
    <p>
        <label class='cred-label'>
            <input type="checkbox" autocomplete="off" class='cred-checkbox-invalid js-cred-bootstrap-styling-setting' name="cred_dont_load_bootstrap_cred_css" value="1"  <?php checked( $settings['dont_load_bootstrap_cred_css'], 1, true ); ?> />
            <span class='cred-checkbox-replace'></span>
            <span><?php _e('Do not load CRED style sheets on front-end', 'wp-cred'); ?></span>
        </label>
    </p>
	<p>
		<label class='cred-label'>
			<input type="checkbox" autocomplete="off" class='cred-checkbox-invalid js-cred-legacy-styling-setting' name="cred_dont_load_cred_css" value="0"  <?php checked( $settings['dont_load_cred_css'], 0, true ); ?> />
			<span class='cred-checkbox-replace'></span>
			<span><?php _e('Load CRED legacy style sheets on front-end (used for old CRED forms)', 'wp-cred'); ?></span>
		</label>
	</p>
</div>
<?php wp_nonce_field( 'cred-styling-settings', 'cred-styling-settings' ); ?>