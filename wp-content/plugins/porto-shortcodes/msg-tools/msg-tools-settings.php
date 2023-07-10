<?php if ( !defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap settings-msgtools">
    <div id="icon-themes" class="icon32"><br></div>
    <h2>RaiPlace TOOLS</h2>
    <form method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>
        <!-- <h3></h3> -->
        <table class="form-table">
            <!-- PEOPLE -->
            <tr>
                <th colspan="2"><h3>PEOPLE: parametri per le chiamate al WebService</h3></th>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">URL del servizio</th>
                <td><p>
                        <input type="text" required name="mpeople_url" id="mpeople_url" value="<?php echo get_option('mpeople_url'); ?>" size="55" />
                        <br />
                        Nella forma<br><em>http://indirizzo/path</em></p></td>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">Username</th>
                <td><input type="text" required name="mpeople_usr" id="mpeople_usr" value="<?php echo get_option('mpeople_usr'); ?>" size="55" />
                <br>(utenza per autentica Windows/NTLM)</td>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">Password</th>
                <td><input type="password" required name="mpeople_pwd" id="mpeople_pwd" value="<?php echo get_option('mpeople_pwd'); ?>" size="55" /></td>
            </tr>
            <!-- ESS/notifiche -->
            <tr>
                <th colspan="2"><h3>WebService REST - ESS e Notifiche</h3></th>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">Base URL del servizio<br>(solo base, esclusi i metodi)</th>
                <td><p>
                        <input type="text" required name="msg_ess_ws_base" id="msg_ess_ws_base" value="<?php echo get_option('msg_ess_ws_base', 'http://svilmy.intranet.rai.it/api/raiplace/'); ?>" size="55" />
                        <br />
                        Nella forma <em>http://indirizzo/path/</em><br>Es.: http://svilmy.intranet.rai.it/api/raiplace/</p></td>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">Username</th>
                <td><input type="text" required name="muserdata_usr" id="muserdata_usr" value="<?php echo get_option('muserdata_usr', 'srvapache1'); ?>" size="55" />
                    <br>(utenza per autentica Windows/NTLM)</td>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">Password</th>
                <td><input type="password" required name="muserdata_pwd" id="muserdata_pwd" value="<?php echo get_option('muserdata_pwd', 'apache11112015'); ?>" size="55" /></td>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">Chiave (<em>keystring</em>)</th>
                <td><input type="text" required name="muserdata_key" id="muserdata_key" value="<?php echo get_option('muserdata_key', '33540117672688738685'); ?>" size="55" />
                    </td>
            </tr>
            <tr>
                <th scope="row" style="width: 200px">Debug mode</th>
                <td><input name="msg_ess_debug" type="checkbox" value="1" <?php checked( get_option('msg_ess_debug', '0'), true ); ?> />
                    Modalità "sandbox" (se spuntato, il plugin restituirà valori finti locali anziché chiamare il Web Service remoto)</td>
            </tr>

            <tr valign="top">
                <th colspan="2"><h3>AVATAR: Servizio che restituisce le foto degli utenti</h3></th>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">URL del servizio</th>
                <td>
                    <p><input type="text" required name="msgtools_avatar_url" id="msgtools_avatar_url" value="<?php echo get_option('msgtools_avatar_url'); ?>" size="55" />
                        <br />
                        Nella forma <em>http://indirizzo/path</em><br>(es.: http://hrapp.servizi.rai.it/viewFace/view.aspx)</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">Dimensioni dell'immagine</th>
                <td>
                    <p>Larghezza: <input type="text" required name="msgtools_avatar_w" id="msgtools_avatar_w" value="<?php echo get_option('msgtools_avatar_w'); ?>" size="5" /> &nbsp;&nbsp;&nbsp;Altezza: <input type="text" required name="msgtools_avatar_h" id="msgtools_avatar_h" value="<?php echo get_option('msgtools_avatar_h'); ?>" size="5" />
                        <br />
                        I valori dovrebbero essere uguali (dando un'immagine quadrata); i valori di default sono 220x220</p>
                </td>
            </tr>
            <tr valign="top">
                <th colspan="2"><h3>TIPI di CONTENUTO: Controllo Menus</h3></th>
            </tr>
            <tr valign="top">
                <th scope="row" style="width: 200px">Menu attivi per tipi di contenuto</th>
                <td><p>
                        <textarea name="msgtools_types_menus" cols="55" rows="5" id="msgtools_types_menus"><?php echo get_option('msgtools_types_menus'); ?></textarea>
                        <br />
                        Metti (uno per riga) i tipi custom per cui vuoi un breadcrumb custom.<br>
                        Metti, divisi da <em>due punti (:)</em>, prima lo slug del Custom type, poi i due id:<br>
                        <ol>
                            <li>della voce di menù di primo livello che fa da <em>root</em></li>
                            <li>della voce di menù in spalla che vuoi sia segnalata come "attiva"</li>
                    </ol>
                        <b>N.B.:</b> gli id (numerici) devono essere quelli delle voci di menù, non quelli delle pagine associate<br>
                        Es.: per le News:<br>
                        news:33:36</p></td>
            </tr>
        </table>
        <input type="hidden" name="action" value="update" />
        <input type="hidden" name="page_options" value="msg_ess_ws_base,muserdata_usr,muserdata_pwd,muserdata_key,mpeople_url,mpeople_usr,mpeople_pwd,msgtools_types_menus,msgtools_avatar_url,msgtools_avatar_w,msgtools_avatar_h,msg_ess_debug" />
        <p><input type="submit" class="button-primary" value="SALVA" /></p>
    </form>
</div>