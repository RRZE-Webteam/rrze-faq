<?php

$atts = '';
foreach($attributes as $key => $value){
    $atts .= $key . '="' . $value . '" ';
}

// echo $atts;
echo do_shortcode('[faq ' . $atts . ']');
