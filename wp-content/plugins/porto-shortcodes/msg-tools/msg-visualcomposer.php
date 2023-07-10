<?php

function msg_customize_vc(){


    /*** ELIMINO parametri standard ***/
    
    $vc_params_to_remove = [
        'vc_column' => [
            //GENERAL
            'css', 'el_class', 'css_animation'
            //PORTO
            ,'section_text_color', 'text_align', 'section_color_scale', 'section_skin_scale', 'remove_padding_top', 'remove_padding_bottom', 'remove_border', 'show_divider','divider_pos','divider_color','divider_height','show_divider_icon','divider_icon_type','divider_icon_image','divider_icon', 'divider_icon_simpleline', 'divider_icon_skin', 'divider_icon_color', 'divider_icon_bg_color', 'divider_icon_border_color', 'divider_icon_wrap_border_color', 'divider_icon_style', 'divider_icon_pos', 'divider_icon_size'
            //TAB Sticky Options
            , 'is_sticky', 'sticky_container_selector', 'sticky_min_width', 'sticky_top', 'sticky_bottom', 'sticky_active_class'

        ],
        'vc_column_text' => ['css', 'css_animation'], //['css', 'el_class'],
        'vc_tta_section' => ['add_icon'],
        'vc_row' => ['wrap_container', 'section_text_color', 'text_align', 'section_skin_scale',
           /* 'remove_margin_top', 'remove_margin_bottom',*/ 'remove_padding_top', 'remove_padding_bottom', 'remove_border', 'show_divider','divider_pos','divider_color','divider_height','show_divider_icon','divider_icon_type','divider_icon_image','divider_icon', 'divider_icon_simpleline', 'divider_icon_skin', 'divider_icon_color', 'divider_icon_bg_color', 'divider_icon_border_color', 'divider_icon_wrap_border_color', 'divider_icon_style', 'divider_icon_pos', 'divider_icon_size'
            //TAB Sticky Options
            , 'is_sticky', 'sticky_container_selector', 'sticky_min_width', 'sticky_top', 'sticky_bottom', 'sticky_active_class'
            // TAB General
            , 'full_width', 'gap', 'full_height', 'equal_height', 'content_placement', 'video_bg', 'video_bg_url', 'video_bg_parallax', 'parallax', 'parallax_image', 'parallax_speed_video', 'parallax_speed_bg', 'css_animation', 'disable_element', 'columns_placement'
            // TAB Animation
            , 'animation_type', 'animation_duration', 'animation_delay'
            // TABS Background + Effect
            , 'bg_type', 'bg_type', 'parallax_content', 'parallax_content_sense', 'fadeout_row', 'fadeout_start_effect', 'enable_overlay', 'overlay_color', 'overlay_pattern', 'overlay_pattern_opacity', 'overlay_pattern_size', 'overlay_pattern_attachment', 'multi_color_overlay', 'multi_color_overlay_opacity', 'seperator_enable', 'seperator_type', 'seperator_type', 'seperator_shape_size', 'seperator_svg_height', 'seperator_shape_background', 'seperator_shape_border', 'seperator_shape_border_color', 'seperator_shape_border_width', 'icon_type', 'icon', 'icon_size', 'icon_color', 'icon_style', 'icon_color_bg'

        ],
        'ultimate_icon_list' => ['icon_size', 'icon_margin'],
        'ultimate_icon_list_item' => ['icon_color', 'icon_color_bg', 'icon_img', 'icon_style'
            /*tab typography */,'title_text_typography', 'content_font_family', 'content_font_style', 'content_font_size', 'content_line_ht', 'content_font_color'
        ],
        'info_list' => ['style', 'position', 'icon_bg_color', 'icon_color', 'font_size_icon', 'icon_border_style', 'icon_border_size', 'border_color', 'notification'
            ,'connector_color', 'connector_animation'],
        'info_list_item' => [ 'icon_img', 'icon_style', 'animation'
            /*tab typography */,'title_text_typography','title_font', 'title_font_style','title_font_size','title_font_line_height','title_font_color',
            'desc_text_typography', 'desc_font', 'desc_font_style', 'desc_font_size', 'desc_font_line_height', 'desc_font_color'
        ],
    ];
    foreach($vc_params_to_remove as $comp => $params){
        foreach($params as $param){
            vc_remove_param($comp, $param);
        }
    }


    /*** MODIFICO paramsteri standard ***/

    // ACCORDION:STYLE
    $param = WPBMap::getParam( 'vc_tta_accordion', 'style' );
    //Append new value to the 'value' array
    //$param['value'][__( 'Super color', 'my-text-domain' )] = 'btn-super-color';
    $param['value'] = array(
        'Standard' => '',
        'Con colore' => 'color-1'
    );
    vc_update_shortcode_param( 'vc_tta_accordion', $param );

    // ROW:SKIN
    $param = WPBMap::getParam( 'vc_row', 'section_skin' );
    $param['value'] = array(
        'Primario (bianco)' => 'primary',
        'Secondario (sfum. verde)' => 'secondary',
        'Terziario (grigio scuro)' => 'tertiary',
        'Trasparente (grigio chiaro)' => 'quaternary'
    );
    vc_update_shortcode_param( 'vc_row', $param );

    // ROW:is section
    $param = WPBMap::getParam( 'vc_row', 'is_section' );
    $param['admin_label'] = false;
    vc_update_shortcode_param( 'vc_row', $param );


    // LIST ICON ITEM
    /*
    $param = WPBMap::getParam( 'ultimate_icon_list_item', 'icon_style'); //'icon_style' );info_list_item
    $param['value'] = array(
        'Standard (disco pieno)' => 'none',
        'Contorno (contorno colorato)' => 'contorno',
        'Contorno alt. (ombra sfumata)' => 'ombra',
        'Senza disco (solo icona)' => 'trasparente'
    );
    vc_update_shortcode_param( 'ultimate_icon_list_item', $param2 );
    $param = WPBMap::getParam( 'info_list', 'style');
    */

    // @TODO: niente da fare, i componenti creati da Ultimate Addons non li estrae con il metodo getParam


    /*** AGGIUNTA PARAMETRI ***/
    $custom_params = array(
        array(
            'type' => 'textfield',
            //'holder' => 'h4',
            'heading' => "Titolo sezione",
            'param_name' => 'section_title',
            'value' => '',
            'admin_label' => true,
            'description' => "Scrivi un titolo pe la sezione (se vuoi che compaia)",
            'weight' => 101,
            'group' => __('Porto Options', 'porto'),

        ),
        array(
            'type' => 'checkbox',
            'heading' => "Mostra chiusura",
            'param_name' => 'show_bottom_divider',
            'value' => array(
                "Sì" => 'on',
            ),
            'std' => 'on', //valore di default!! (non documentato...)
            'description' => "Mostra l'elemento grafico divisorio sotto la fascia/sezione",
            'weight' => 100,
            'group' => __('Porto Options', 'porto'),

        ),
        array(
            'type' => 'textfield',
            'heading' => "Link chiusura",
            'param_name' => 'link_bottom_divider',
            'value' => '',
            'description' => "Link dell'elemento grafico divisorio. Inserisci la url relativa (a partire dalla prima / compresa)",
            'weight' => 100,
            'dependency' => array(
                'element' => 'show_bottom_divider',
                'value' => array('on')
            ),
            'group' => __('Porto Options', 'porto'),

        )
    );
    vc_add_params( 'vc_row', $custom_params );

    $custom_params = [
        array(
            'type' => 'dropdown',
            'heading' => "Stile",
            "class" => "",
            'param_name' => 'icon_style',
            'value' => array( 'Standard (disco pieno)' => 'none',
                'Contorno (contorno colorato)' => 'contorno',
                'Contorno alt. (ombra sfumata)' => 'ombra',
                'Senza disco (solo icona)' => 'trasparente'),
            'description' => 'Seleziona lo stile'
        )
    ];
    vc_add_params( 'ultimate_icon_list_item', $custom_params );

    $custom_params = [
        array(
            'type' => 'dropdown',
            'heading' => "Stile",
            "class" => "",
            'param_name' => 'style',
            'value' => array(
                'Standard (disco pieno)' => 'none',
                'Contorno (contorno colorato)' => 'contorno',
                'Contorno alt. (ombra sfumata)' => 'ombra',
                'Senza disco (solo icona)' => 'trasparente'),
            'description' => 'Seleziona lo stile',
            'weight' => 1
        )
    ];
    vc_add_params( 'info_list', $custom_params );

    // modifico parametro custom appena aggiunto
    // ROW:Title
    $param = WPBMap::getParam( 'vc_row', 'section_title' );
    $param['admin_label'] = true;
    vc_update_shortcode_param( 'vc_row', $param );

}
add_action( 'vc_after_init', 'msg_customize_vc', 9999 );