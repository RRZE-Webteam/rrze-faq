<?php
/* 
Template Name: Custom Taxonomy faq_category Template
*/
namespace RRZE\FAQ;

use function RRZE\FAQ\Config\getConstants;

$cpt = getConstants('cpt');

get_header();

?>

<main id="main" class="site-main rrze-faq category">

<?php

$taxonomy = $cpt['category'];
include_once('template-parts/faq_taxonomy.php');
?>
</main>

<?php
get_footer();