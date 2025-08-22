<?php
$allowed = [
  'glossary','glossarystyle','category','tag','id',
  'hide_accordion','hide_title','masonry','color','style',
  'additional_class','lang','sort','order','hstart'
];

$atts = [];
$attrs = (array) ($attributes ?? []);

if ( ! empty($attrs['glossary']) ) {
    if ( empty($attrs['glossarystyle']) ) {
        $attrs['glossarystyle'] = 'a-z';
    }
} else {
    unset($attrs['glossarystyle']);
}

foreach ($attrs as $key => $value) {
    if ( ! in_array($key, $allowed, true) ) continue;
    if ($value === '' || $value === null) continue;           
    if (is_bool($value)) $value = $value ? '1' : '0';         
    if (is_array($value)) $value = implode(',', $value);      
    $atts[] = sprintf('%s="%s"', $key, esc_attr((string) $value));
}

echo do_shortcode('[faq ' . implode(' ', $atts) . ']');
