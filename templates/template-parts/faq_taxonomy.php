<?php
/* 
Template Name: Part of the Custom Taxonomy Templates
*/

namespace RRZE\FAQ;

use function RRZE\FAQ\Config\getConstants;

$cpt = getConstants('cpt');

$cat_slug = get_queried_object()->slug;
$cat_name = get_queried_object()->name;

?>
<article>
<div id="content"><div class="content-container">

<?php 
echo '<h2>' . esc_html($cat_name) . '</h2>';

$tax_post_args = array(
    'post_type' => $cpt['faq'],
    'posts_per_page' => 999,
    'order' => 'ASC',
    'tax_query' => array(// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
        array(
            'taxonomy' => $taxonomy,
            'field' => 'slug',
            'terms' => esc_attr($cat_slug)
        )
    )
);
$tax_post_query = new WP_Query($tax_post_args);

if ($tax_post_query->have_posts()){
    echo '<ul>';
    while($tax_post_query->have_posts()){
        $tax_post_query->the_post();
        echo '<li><a href="' . esc_url(get_the_permalink()) . '">' . esc_html(get_the_title()) . '</a></li>';
    }
    echo '</ul>';
}
?>
</div></div>
</article>