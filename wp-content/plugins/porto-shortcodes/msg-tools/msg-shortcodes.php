<?php
/*** SHORTCODES Generici ***/

add_shortcode('blocco_pagina', 'msg_sc_blocco_pagina');
function msg_sc_blocco_pagina($atts, $content){

    $atts = shortcode_atts(array(
        'id' => 0,
        'footer' => 'Vai',
        'footer_link' => false
    ), $atts);
    $id = intval($atts['id']);
    $out = '';
    if($id){

        $titolo = get_the_title($id);
        if($content){
            $testo = $content;
        } else {
            $testo = do_shortcode("[types field='sottotitolo' id='$id']");
        }

        $url = get_page_link($id);

        $footer_link = filter_var($atts['footer_link'] ,FILTER_VALIDATE_BOOLEAN);
        $footer_band = '';
        if($footer_link){
            $footer_band = '<div class="section-bottom"><a href="'.$url.'" title="Vedi tutto">&nbsp;</a></div>';
        }

        $out = '<div class="blocco-pagina">
            <div class="contenuto">
                <div class="titolo"><a href="'.$url.'">'.$titolo.'</a></div>
                <div class="sottotitolo">'.$testo.'</div>
            </div>
            <div class="footer">'.$atts['footer'].'</div>
            '.$footer_band.'
        </div>';
    }

    return $out;
}