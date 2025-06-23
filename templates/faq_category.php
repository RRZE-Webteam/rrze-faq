<?php
/* 
Template Name: Custom Taxonomy faq_category Template
*/

get_header();

?>

<main id="main" class="site-main rrze-faq category">

<?php

$taxonomy = 'faq_category';
include_once('template-parts/faq_taxonomy.php');
?>
</main>

<?php
get_footer();