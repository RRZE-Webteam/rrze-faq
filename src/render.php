<?php

$atts = '';
foreach($attributes as $key => $value){
    $atts .= $key . '="' . $value . '" ';
}

return do_shortcode('[faq ' . $atts . ']');
