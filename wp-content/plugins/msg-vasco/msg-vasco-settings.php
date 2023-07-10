<?php if ( !defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap settings-msgtools">
    <div id="icon-themes" class="icon32"><br></div>
    <h2>VASCO: parametri per le chiamate al WebService</h2>
    <form method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>
        <!-- <h3></h3> -->
        <table class="form-table">
            <tr valign="top">
                <th scope="row" style="width: 200px">Indirizzo del servizio Vasco</th>
                <td><p>
                        <input type="text" required name="mvasco_url" id="mvasco_url" value="<?php echo get_option('mvasco_url'); ?>" size="55" /><br />
                        Nella forma<br><em>https://indirizzo:nnnn</em><br> dove <em>nnnn</em> è la porta</p></td>
            </tr>
            <tr valign="top">
                <th scope="row" colspan="2"><h3>Utenza amministrativa di Vasco</h3></th>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">Username</th>
                <td><input type="text" required name="mvasco_usr" id="mvasco_usr" value="<?php echo get_option('mvasco_usr'); ?>" size="55" /></td>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">Password</th>
                <td><input type="password" required name="mvasco_pwd" id="mvasco_pwd" value="<?php echo get_option('mvasco_pwd'); ?>" size="55" /></td>
            </tr>
            <tr valign="top">
                <th scope="row" colspan="2"><h3>Utenti non abilitati all'uso di Vasco</h3></th>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">Path del file CSV</th>
                <td><input type="text" name="mvasco_rsa_path" id="mvasco_rsa_path" value="<?php echo get_option('mvasco_rsa_path'); ?>" size="55" /><?php
					if(file_exists(MVASCO_VASCO_CSV_PATH)){
						echo ' <span style="color:green;">Il file esiste</span>';
					} else {
						echo ' <span style="color:red;">ATTENZIONE: il file <b>non</b> esiste!</span>';
					}
					?><br />
                    Usa il webpath relativo alla root del sito, p.e.:<br><em>wp-content/uploads/RSA_AD.CSV</em><br><b>N.B.!:</b> questa variabile è <b>Case Sensitive</b> (anche rispetto all'estensione del file: .csv != .CSV)</td>
            </tr>
        </table>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="page_options" value="mvasco_url,mvasco_usr,mvasco_pwd,mvasco_rsa_path" />
        <p><input type="submit" class="button-primary" value="SALVA" /></p>
    </form>
</div>