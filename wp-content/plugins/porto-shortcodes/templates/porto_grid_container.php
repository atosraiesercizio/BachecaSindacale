<?php
$output = $grid_size = $gutter_size = $max_width = $animation_type = $animation_duration = $animation_delay = $el_class = '';
extract(shortcode_atts(array(
    'grid_size' => '0',
    'gutter_size' => '2%',
    'max_width' => '767px',
    'animation_type' => '',
    'animation_duration' => 1000,
    'animation_delay' => 0,
    'el_class' => ''
), $atts));

$validCharacters = 'abcdefghijklmnopqrstuvwxyz0123456789';
$rand = '';
$length = 32;
for ($n = 1; $n < $length; $n++) {
    $whichCharacter = rand(0, strlen($validCharacters)-1);
    $rand .= $validCharacters{$whichCharacter};
}

$el_class = porto_shortcode_extract_class( $el_class );

$output = '<div class="porto-grid-container"';
if ($animation_type) {
    $output .= ' data-appear-animation="'.$animation_type.'"';
    if ($animation_delay)
        $output .= ' data-appear-animation-delay="'.$animation_delay.'"';
    if ($animation_duration && $animation_duration != 1000)
        $output .= ' data-appear-animation-duration="'.$animation_duration.'"';
}
$output .= '>';

$output .= '<div id="grid-' . $rand . '" class="packery ' . $el_class . ' wpb_content_element clearfix">';
$output .= do_shortcode($content);
$output .= '<div class="grid-sizer" style="width:' . $grid_size . '"></div><div class="gutter-sizer" style="width:' . $gutter_size . '"></div></div>';

$max_width = esc_js($max_width);
$rand = esc_js($rand);
$gutter_size = esc_js($gutter_size);

$output .= '<script type="text/javascript">
                jQuery(document).ready(function($) {
                    function initPortoGrid() {
                        var $grid = $("#grid-' . $rand . '");
                        if (typeof $grid.waitForImages !== "undefined") {
                            $grid.waitForImages(function() {
                                $grid.packery({
                                    itemSelector: ".porto-grid-item",
                                    columnWidth: ".grid-sizer",
                                    gutter: ".gutter-sizer"
                                });
                            });
                        } else {
                            $grid.packery({
                                itemSelector: ".porto-grid-item",
                                columnWidth: ".grid-sizer",
                                gutter: ".gutter-sizer"
                            });
                        }
                    }
                    $(document).bind("vc-full-width-row", function() {
                        initPortoGrid();
                    });
                    initPortoGrid();
                });
            </script>
            <style type="text/css">
                @media (max-width:' . $max_width . ') {
                    #grid-' . $rand. ' {
                        height: auto !important;
                    }
                    #grid-' . $rand. ' .porto-grid-item:first-child {
                        margin-top: 0;
                    }
                    #grid-' . $rand. ' .porto-grid-item {
                        width: 100% !important;
                        position: static !important;
                        float: none;
                        margin-top: ' . $gutter_size . ';
                    }
                }
            </style>';

$output .= '</div>';

echo $output;