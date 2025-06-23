<?php
/* 
Template Name: Custom Taxonomy faq_tag Template
*/
get_header();

?>

<main id="main" class="site-main rrze-faq tag">

<?php

$taxonomy = 'faq_tag';
include_once('template-parts/faq_taxonomy.php');

?>
</main>

<?php
get_footer();