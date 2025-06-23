<?php
/**
 * The template for displaying all FAQ
 *
 *
 * @package WordPress
 * @subpackage FAU
 * @since FAU 1.0
*/

get_header();

?>

<main id="main" class="site-main rrze-faq archive">

<?php


if ( have_posts() ) : while ( have_posts() ) : the_post();

include('template-parts/faq_content.php');

endwhile; endif;

?>
</main>

<?php
get_footer();

