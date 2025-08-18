<?php
/* 
Template Name: Custom Taxonomy faq_category Template
*/
namespace RRZE\FAQ;

get_header();

?>

<main id="main" class="site-main rrze-faq category">

<?php

$taxonomy = 'rrze_faq_category';
include_once('template-parts/faq_taxonomy.php');
?>
</main>

<?php
get_footer();