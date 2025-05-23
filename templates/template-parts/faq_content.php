<?php
/**
 * This is part of the templates for displaying the FAQ
 *
 * @package WordPress
 * @subpackage FAU
 * @since FAU 1.0
 */

namespace RRZE\FAQ;

use RRZE\FAQ\Tools;

$postID = get_the_ID();
$headerID = Tools::getHeaderID($postID);

$cats = Tools::getTermLinks($postID, 'faq_category');
$tags = Tools::getTermLinks($postID, 'faq_tag');

$content = '';
$content .= '<article>';
$content .= '<header>';
$content .= '<h1 id="' . esc_attr($headerID) . '">' . esc_html(get_the_title()) . '</h1>';
$content .= '</header>';
$content .= apply_filters('the_content', get_the_content());
$content .= '<footer><p class="meta-footer">';

if ($cats) {
    $content .= '<span class="post-meta-categories">' . esc_html__('Categories', 'rrze-faq') . ': ' . wp_kses_post($cats) . '</span> ';
}
if ($tags) {
    $content .= '<span class="post-meta-tags">' . esc_html__('Tags', 'rrze-faq') . ': ' . wp_kses_post($tags) . '</span>';
}

$content .= Tools::getLinkedPageHTML($postID);

$content .= '</p></footer>';
$content .= '</article>';

$masonry = false;
$color = '';
$additional_class = '';

echo Tools::renderFaqWrapper($content, $headerID, $masonry, $color, $additional_class);

